<?php

namespace App\Jobs;

use App\Services\AiProviderEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Run one provider comparison case without holding the admin request open. */
class RunAiProviderEvaluationCase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 170;

    public int $backoff = 10;

    public function __construct(
        public readonly int $runId,
        public readonly string $providerKey,
        public readonly string $caseKey
    ) {
        $defaultConnection = (string) config('queue.default', 'database');
        $this->onConnection(in_array($defaultConnection, ['sync', 'null', ''], true)
            ? 'database'
            : $defaultConnection);
        $this->onQueue('default');
    }

    public function handle(AiProviderEvaluationService $evaluationService): void
    {
        $evaluationService->runQueuedUserRequestComparisonCase($this->runId, $this->providerKey, $this->caseKey);
    }

    public function failed(Throwable $error): void
    {
        Log::error('AI provider comparison case exhausted its queue attempts.', [
            'run_id' => $this->runId,
            'provider' => $this->providerKey,
            'case_key' => $this->caseKey,
            'error_type' => $error::class,
        ]);

        try {
            app(AiProviderEvaluationService::class)->failQueuedUserRequestComparison($this->runId);
        } catch (Throwable $failure) {
            Log::error('AI provider comparison run could not be marked failed.', [
                'run_id' => $this->runId,
                'error_type' => $failure::class,
            ]);
        }
    }
}
