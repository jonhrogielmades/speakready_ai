<?php

namespace App\Jobs;

use App\Models\InterviewSession;
use App\Services\DetailedFeedbackReviewService;
use App\Support\DetailedReviewProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RefreshDetailedFeedbackReview implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 150;

    public int $uniqueFor = 600;

    public function __construct(
        public readonly int $sessionId,
        public readonly int $userId
    ) {
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return (string) $this->sessionId;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function handle(
        DetailedFeedbackReviewService $reviewService
    ): void {
        $session = InterviewSession::query()
            ->whereKey($this->sessionId)
            ->where('user_id', $this->userId)
            ->where('status', 'completed')
            ->first();

        if (! $session || (bool) data_get($session->action_plan ?? [], 'ended_early', false)) {
            return;
        }

        // dispatchAfterResponse uses SyncQueue, which does not honor worker
        // attempts/backoff. Give it a bounded recovery pass inside this job.
        $sync = $this->job instanceof \Illuminate\Queue\Jobs\SyncJob
            || ($this->job === null && config('queue.default') === 'sync');
        $deadline = microtime(true) + 130;
        // Outlive the 130-second work budget, but expire before a timed-out
        // worker's next attempt so an abandoned lock cannot swallow its retry.
        $lock = Cache::lock('feedback-review-running:'.$this->sessionId, $this->timeout - 5);
        if (! $lock->get()) {
            return;
        }

        try {
            DetailedReviewProgress::mark($this->sessionId, 'processing');
            $passes = $sync ? 2 : 1;
            for ($pass = 0; $pass < $passes; $pass++) {
                $remaining = (int) floor($deadline - microtime(true));
                if ($remaining < 5) {
                    break;
                }
                $providerRefreshed = $reviewService->refreshWithProvidersIfNeeded($session, null, $remaining);
                if ($providerRefreshed) {
                    DetailedReviewProgress::mark($this->sessionId, 'ready');
                    return;
                }

                // Never replace rejected API prose with a local substitute.
                $session->refresh();
                if (! $reviewService->hasConfiguredProvider()) {
                    DetailedReviewProgress::mark($this->sessionId, 'unavailable');
                    return;
                }
                if (! $reviewService->shouldRefreshWithProvider($session)) {
                    DetailedReviewProgress::mark($this->sessionId, 'ready');
                    return;
                }
            }
            throw new \RuntimeException('AI detailed review is still pending after the provider attempt.');
        } catch (\Throwable $exception) {
            DetailedReviewProgress::mark($this->sessionId, $sync || $this->attempts() >= $this->tries ? 'failed' : 'waiting');
            Log::warning('Queued detailed feedback refresh failed.', [
                'session_id' => $this->sessionId,
                'user_id' => $this->userId,
                'error_type' => $exception::class,
            ]);

            throw $exception;
        } finally {
            $lock->release();
        }
    }

    public function failed(?\Throwable $exception): void
    {
        DetailedReviewProgress::mark($this->sessionId, 'failed');
    }
}
