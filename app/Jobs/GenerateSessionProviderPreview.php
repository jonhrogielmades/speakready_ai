<?php

namespace App\Jobs;

use App\Models\InterviewSession;
use App\Services\ProviderSessionPreviewService;
use App\Support\SystemSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Request only the four provider-written overall review fields. */
class GenerateSessionProviderPreview implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 85;

    public int $uniqueFor = 180;

    public function __construct(public readonly int $sessionId, public readonly int $userId)
    {
        $defaultConnection = (string) config('queue.default', 'database');
        $this->onConnection(in_array($defaultConnection, ['sync', 'null', ''], true) ? 'database' : $defaultConnection);
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return (string) $this->sessionId;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [20, 60];
    }

    public function handle(ProviderSessionPreviewService $previews): void
    {
        $session = InterviewSession::query()
            ->whereKey($this->sessionId)
            ->where('user_id', $this->userId)
            ->where('status', 'completed')
            ->whereNull('game_level_id')
            ->first();
        if (! $session || ! SystemSettings::enabled('int_ai_eval', true)
            || (bool) data_get($session->action_plan ?? [], 'ended_early', false)
            || $previews->forSession($session) !== null) {
            return;
        }

        $lock = Cache::lock('session-provider-preview:'.$this->sessionId, 100);
        if (! $lock->get()) {
            $this->release(10);
            return;
        }

        try {
            $previews->generateAndSave($session);
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $error): void
    {
        Log::warning('Provider session preview could not be generated.', [
            'session_id' => $this->sessionId,
            'error_type' => $error::class,
        ]);
    }
}
