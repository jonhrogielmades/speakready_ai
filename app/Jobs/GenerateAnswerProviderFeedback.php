<?php

namespace App\Jobs;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Models\InterviewAnswer;
use App\Models\InterviewAnswerProviderReview;
use App\Models\InterviewSession;
use App\Services\AIService;
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

/** Prepare one provider-validated answer while the interview continues. */
class GenerateAnswerProviderFeedback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 100;

    public string $runToken;

    public function __construct(public readonly int $sessionId, public readonly int $answerId)
    {
        $this->runToken = (string) Str::uuid();
        $defaultConnection = (string) config('queue.default', 'database');
        $this->onConnection(in_array($defaultConnection, ['sync', 'null', ''], true) ? 'database' : $defaultConnection);
        $this->onQueue('review-answers');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(PreparedAnswerReviewService $prepared): void
    {
        $session = InterviewSession::query()->whereKey($this->sessionId)
            ->whereNull('game_level_id')
            ->whereIn('status', ['in_progress', 'processing', 'completed'])
            ->first();
        $answer = InterviewAnswer::with('question')->whereKey($this->answerId)
            ->where('interview_session_id', $this->sessionId)
            ->whereNull('retry_of_answer_id')
            ->first();
        if (! $session || ! $answer || ! SystemSettings::enabled('int_ai_eval', true)
            || (bool) data_get($session->action_plan ?? [], 'ended_early', false)) {
            return;
        }

        $existing = InterviewAnswerProviderReview::where('interview_answer_id', $answer->id)->first();
        if ($prepared->isCurrentVerified($session, $answer, $existing)) {
            return;
        }

        $lock = Cache::lock('answer-provider-review:'.$this->answerId, 110);
        if (! $lock->get()) {
            $this->release(10);
            return;
        }

        try {
            $claimed = DB::transaction(function () use ($prepared, $session, $answer): ?string {
                $currentSession = InterviewSession::whereKey($session->id)->first();
                $currentAnswer = InterviewAnswer::with('question')->whereKey($answer->id)->first();
                if (! $currentSession || ! $currentAnswer
                    || (int) $currentAnswer->interview_session_id !== (int) $currentSession->id) {
                    return null;
                }
                $currentFingerprint = $prepared->inputFingerprint($currentSession, $currentAnswer);
                $row = InterviewAnswerProviderReview::firstOrCreate(
                    ['interview_answer_id' => $currentAnswer->id],
                    ['interview_session_id' => $currentSession->id, 'status' => 'pending']
                );
                $row = InterviewAnswerProviderReview::whereKey($row->id)->lockForUpdate()->firstOrFail();
                if ($prepared->isCurrentVerified($currentSession, $currentAnswer, $row)) {
                    return null;
                }
                $row->forceFill([
                    'status' => 'processing',
                    'input_fingerprint' => $currentFingerprint,
                    'provider_key' => null,
                    'processing_token' => $this->runToken,
                    'attempts' => (int) $row->attempts + 1,
                    'failure_code' => null,
                    'payload' => null,
                    'started_at' => now(),
                    'completed_at' => null,
                ])->save();

                return $currentFingerprint;
            });
            if ($claimed === null) {
                return;
            }

            $session->refresh();
            $answer->refresh()->load('question');
            $feedback = AIService::generateFeedback(
                $prepared->sessionData($session),
                [$prepared->answerData($answer)],
                AIService::detailedReviewProviderPriority()[0] ?? null,
                false,
                false,
                [
                    'require_provider_narratives' => true,
                    'answer_only_completion' => true,
                    'allow_provider_completion' => false,
                    'timeout_seconds' => 25,
                    'deadline_seconds' => 70,
                    'max_attempts' => 2,
                    'http_attempts' => 1,
                    'retry_delay_ms' => 200,
                ]
            );
            $item = $feedback['per_question_feedback'][0] ?? null;
            $provider = AIService::normalizeProviderKey($feedback['_provider_key'] ?? null);
            $quality = is_array($item['feedback_quality'] ?? null) ? $item['feedback_quality'] : [];
            if (! is_array($item) || (string) ($item['id'] ?? '') !== (string) $answer->id
                || $provider === '' || in_array($provider, ['local', 'localmodel'], true)
                || ($item['evaluation_source'] ?? null) !== 'ai_evidence_validated'
                || ($item['answer_review_source'] ?? null) !== 'ai_provider'
                || ($item['provider_narratives_verified'] ?? null) !== true
                || ($quality['status'] ?? null) !== 'verified'
                || (int) ($quality['completeness_percent'] ?? 0) !== 100) {
                throw new \RuntimeException('Provider did not return a validated answer item.');
            }

            $saved = DB::transaction(function () use ($prepared, $claimed, $item, $provider): bool {
                $row = InterviewAnswerProviderReview::where('interview_answer_id', $this->answerId)
                    ->lockForUpdate()->first();
                if (! $row || $row->status !== 'processing' || $row->processing_token !== $this->runToken) {
                    return false;
                }
                $currentSession = InterviewSession::whereKey($this->sessionId)->first();
                $currentAnswer = InterviewAnswer::with('question')->whereKey($this->answerId)->first();
                if (! $currentSession || ! $currentAnswer
                    || ! hash_equals($claimed, $prepared->inputFingerprint($currentSession, $currentAnswer))) {
                    $row->forceFill([
                        'status' => 'pending',
                        'failure_code' => 'input_changed',
                        'processing_token' => null,
                    ])->save();
                    return false;
                }
                $row->forceFill([
                    'status' => 'verified',
                    'provider_key' => $provider,
                    'payload' => $item,
                    'processing_token' => null,
                    'failure_code' => null,
                    'completed_at' => now(),
                ])->save();
                return true;
            });
            if ($saved) {
                $completedSession = InterviewSession::whereKey($this->sessionId)
                    ->where('status', 'completed')
                    ->where(function ($query): void {
                        $query->whereNull('feedback_status')->orWhere('feedback_status', '!=', 'verified');
                    })->first();
                if ($completedSession && $prepared->preparedReviewsForSession($completedSession) !== null
                    && app(DetailedFeedbackReviewService::class)->shouldRefreshWithProvider($completedSession)) {
                    try {
                        GenerateInterviewFeedback::dispatch((int) $completedSession->id, (int) $completedSession->user_id);
                    } catch (Throwable $dispatchError) {
                        Log::warning('Prepared answers could not resume final provider review.', [
                            'session_id' => $this->sessionId,
                            'error_type' => $dispatchError::class,
                        ]);
                    }
                }
            }
        } catch (Throwable $error) {
            $failureCode = $error instanceof AiFeedbackProviderFailureException
                ? $error->failureCode() : 'provider_answer_failed';
            InterviewAnswerProviderReview::where('interview_answer_id', $this->answerId)
                ->where('processing_token', $this->runToken)
                ->where('status', 'processing')
                ->update(['status' => 'pending', 'failure_code' => $failureCode]);
            Log::warning('Per-answer provider review attempt failed.', [
                'session_id' => $this->sessionId,
                'answer_id' => $this->answerId,
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
        InterviewAnswerProviderReview::where('interview_answer_id', $this->answerId)
            ->where('processing_token', $this->runToken)
            ->whereIn('status', ['pending', 'processing'])
            ->update([
                'status' => 'failed',
                'failure_code' => $error instanceof AiFeedbackProviderFailureException
                    ? $error->failureCode() : 'provider_answer_failed',
            ]);
    }
}
