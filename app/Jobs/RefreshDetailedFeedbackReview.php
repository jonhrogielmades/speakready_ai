<?php

namespace App\Jobs;

use App\Models\InterviewSession;
use App\Services\DetailedFeedbackReviewService;
use App\Support\FeedbackCoachingRepair;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        DetailedFeedbackReviewService $reviewService,
        FeedbackCoachingRepair $coachingRepair
    ): void {
        $session = InterviewSession::query()
            ->whereKey($this->sessionId)
            ->where('user_id', $this->userId)
            ->where('status', 'completed')
            ->first();

        if (! $session || (bool) data_get($session->action_plan ?? [], 'ended_early', false)) {
            return;
        }

        try {
            $providerRefreshed = $reviewService->refreshWithProvidersIfNeeded($session);

            if (! $providerRefreshed) {
                $coachingRepair->repairSession($session);
            }
        } catch (\Throwable $exception) {
            Log::warning('Queued detailed feedback refresh failed.', [
                'session_id' => $this->sessionId,
                'user_id' => $this->userId,
                'error_type' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
