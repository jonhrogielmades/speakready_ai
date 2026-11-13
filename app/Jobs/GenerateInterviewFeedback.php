<?php

namespace App\Jobs;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Helpers\ActivityLogger;
use App\Models\InterviewSession;
use App\Models\UserOnboardingProgress;
use App\Services\DetailedFeedbackReviewService;
use App\Services\PreparedAnswerReviewService;
use App\Support\SystemSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/** Generate the first provider-verified report without holding an HTTP request open. */
class GenerateInterviewFeedback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 190;

    public string $runToken;

    public function __construct(
        public readonly int $sessionId,
        public readonly int $userId
    ) {
        $this->runToken = (string) Str::uuid();
        // Sync would hold the interview request open; null would discard the review.
        $defaultConnection = (string) config('queue.default', 'database');
        $this->onConnection(in_array($defaultConnection, ['sync', 'null', ''], true)
            ? 'database'
            : $defaultConnection);
        $this->onQueue('default');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [15, 60, 180];
    }

    public function handle(DetailedFeedbackReviewService $reviewService): void
    {
        $session = InterviewSession::query()
            ->whereKey($this->sessionId)
            ->where('user_id', $this->userId)
            ->where('status', 'completed')
            ->whereNull('game_level_id')
            ->first();

        if (! $session || (bool) data_get($session->action_plan ?? [], 'ended_early', false)) {
            return;
        }

        if (! $reviewService->shouldRefreshWithProvider($session)) {
            $this->markVerified($session);
            return;
        }

        if (! SystemSettings::enabled('int_ai_eval', true)) {
            InterviewSession::query()->whereKey($this->sessionId)
                ->where('status', 'completed')
                ->where(function ($query): void {
                    $query->whereNull('feedback_status')->orWhere('feedback_status', '!=', 'verified');
                })
                ->update([
                    'feedback_status' => 'failed',
                    'feedback_failure_code' => 'ai_evaluation_disabled',
                ]);
            return;
        }

        $lock = Cache::lock('feedback-generation:'.$this->sessionId, 200);
        if (! $lock->get()) {
            // Another worker owns this review. Keep this attempt available if it crashes.
            $this->release(15);
            return;
        }

        try {
            $session->refresh();
            if (! $reviewService->shouldRefreshWithProvider($session)) {
                $this->markVerified($session);
                return;
            }

            $prepared = app(PreparedAnswerReviewService::class);
            $progress = $prepared->progressForSession($session);
            if ($prepared->preparedReviewsForSession($session) === null
                && ($progress['verified'] > 0 || $progress['processing'] > 0)
                && $prepared->hasPendingPreparationsForSession($session)
                && (int) $session->feedback_attempts === 0
                && $session->updated_at?->gte(now()->subSeconds(90))) {
                // Let in-flight answer reviews finish before requesting a full
                // interview review. A delayed attempt recovers if they stall.
                self::dispatch($this->sessionId, $this->userId)
                    ->delay(now()->addSeconds(10));
                return;
            }

            $claimed = InterviewSession::query()
                ->whereKey($this->sessionId)
                ->where('status', 'completed')
                ->where(function ($query): void {
                    $query->whereNull('feedback_status')->orWhere('feedback_status', '!=', 'processing');
                })
                ->update([
                    'feedback_status' => 'processing',
                    'feedback_started_at' => now(),
                    'feedback_failure_code' => null,
                    'feedback_processing_token' => $this->runToken,
                    'feedback_attempts' => DB::raw('COALESCE(feedback_attempts, 0) + 1'),
                ]);
            if ($claimed !== 1) {
                // A worker using a different cache store already owns the DB claim.
                return;
            }

            // Use provider-verified answer reviews when all current answers are
            // ready. A full provider request remains the recovery route.
            $completedFromAnswers = false;
            if ($prepared->preparedReviewsForSession($session) !== null) {
                try {
                    $completedFromAnswers = $reviewService->finalizeFromPreparedAnswers($session, true);
                } catch (Throwable $preparedError) {
                    Log::warning('Prepared answer summary failed; trying full provider review.', [
                        'session_id' => $this->sessionId,
                        'error_type' => $preparedError::class,
                    ]);
                }
            }
            if (! $completedFromAnswers) {
                $reviewService->refreshWithProvidersIfNeeded($session, null, 120, true);
            }
            $session->refresh()->load(['score', 'feedback']);
            if ($reviewService->shouldRefreshWithProvider($session)
                || ! $session->score || ! $session->feedback) {
                throw new \RuntimeException('No complete provider-verified interview report was saved.');
            }

            $this->markVerified($session);
        } catch (Throwable $error) {
            $failureCode = $error instanceof AiFeedbackProviderFailureException
                ? $error->failureCode()
                : 'provider_review_failed';
            InterviewSession::query()
                ->whereKey($this->sessionId)
                ->where('feedback_status', 'processing')
                ->where('feedback_processing_token', $this->runToken)
                ->update([
                    'feedback_status' => 'pending',
                    'feedback_failure_code' => $failureCode,
                ]);
            Log::warning('Queued provider interview feedback attempt failed.', [
                'session_id' => $this->sessionId,
                'user_id' => $this->userId,
                'failure_code' => $failureCode,
                'error_type' => $error::class,
            ]);
            throw $error;
        } finally {
            $lock->release();
        }
    }

    public function failed(?Throwable $error): void
    {
        $markedFailed = InterviewSession::query()
            ->whereKey($this->sessionId)
            ->where('user_id', $this->userId)
            ->where('status', 'completed')
            // Only this job's claim may be failed; a newer retry may already own
            // the review after a timeout or an abandoned worker.
            ->where('feedback_processing_token', $this->runToken)
            ->whereIn('feedback_status', ['pending', 'processing'])
            ->update([
                'feedback_status' => 'failed',
                'feedback_failure_code' => $error instanceof AiFeedbackProviderFailureException
                    ? $error->failureCode()
                    : 'provider_review_failed',
            ]);

        if ($markedFailed !== 1 || ! $error instanceof AiFeedbackProviderFailureException
            || ! SystemSettings::enabled('int_ai_eval', true)) {
            return;
        }

        // A full provider response can fail validation while the smaller
        // provider-only answer path still succeeds. Prepare only missing
        // answers; the last verified answer queues a fresh session summary.
        try {
            $session = InterviewSession::query()->whereKey($this->sessionId)
                ->where('user_id', $this->userId)
                ->where('status', 'completed')
                ->whereNull('game_level_id')
                ->first();
            if (! $session || (bool) data_get($session->action_plan ?? [], 'ended_early', false)) {
                return;
            }

            $missingAnswerIds = app(PreparedAnswerReviewService::class)->missingAnswerIdsForSession($session);
            foreach ($missingAnswerIds as $answerId) {
                GenerateAnswerProviderFeedback::dispatch((int) $session->id, (int) $answerId);
            }
        } catch (Throwable $recoveryError) {
            Log::warning('Provider-only answer recovery could not be queued.', [
                'session_id' => $this->sessionId,
                'error_type' => $recoveryError::class,
            ]);
        }
    }

    private function markVerified(InterviewSession $session): void
    {
        $session->refresh()->loadMissing('feedback');
        $fingerprint = trim((string) data_get(
            $session->feedback?->coaching_summary ?? [],
            'review_context.detailed_review_validation.input_fingerprint',
            ''
        ));
        $changed = InterviewSession::query()
            ->whereKey($session->id)
            ->where('status', 'completed')
            ->where(function ($query): void {
                $query->whereNull('feedback_status')->orWhere('feedback_status', '!=', 'verified');
            })
            ->update([
                'feedback_status' => 'verified',
                'feedback_failure_code' => null,
                'feedback_completed_at' => now(),
                'feedback_input_fingerprint' => $fingerprint !== '' ? $fingerprint : null,
                'feedback_processing_token' => null,
            ]);

        if ($changed !== 1) {
            return;
        }

        try {
            $user = $session->user;
            if ($user) {
                UserOnboardingProgress::forUser($user)->syncFromUserState($user);
                ActivityLogger::log($user, 'interview_completed', 'Your AI interview feedback is ready.', null, true, [
                    'title' => 'AI Feedback Ready',
                    'message' => 'Your provider-reviewed interview feedback is ready.',
                    'icon' => 'fa-flag-checkered',
                    'type' => 'success',
                    'push' => true,
                    'url' => route('user.review', $session->id),
                    'tag' => 'speakready-ai-feedback-'.$session->id,
                ]);
            }
        } catch (Throwable $error) {
            // Notification and onboarding telemetry cannot undo a verified report.
            Log::warning('Verified provider review could not send its completion notice.', [
                'session_id' => $session->id,
                'error_type' => $error::class,
            ]);
        }
    }
}
