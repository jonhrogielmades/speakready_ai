<?php

namespace App\Console\Commands;

use App\Services\VoiceRecordingCleanupService;
use Illuminate\Console\Command;

class RetryVoiceRecordingCleanup extends Command
{
    protected $signature = 'privacy:retry-recording-cleanup {--limit=100 : Maximum pending entries to queue}';

    protected $description = 'Queue durable retries for voice recordings that could not be deleted immediately';

    public function handle(VoiceRecordingCleanupService $cleanup): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $result = $cleanup->retryPending($limit);

        $this->info(
            "Recording cleanup retry complete: {$result['queued']} queued, "
            ."{$result['pending']} pending, {$result['invalid']} quarantined."
        );

        return self::SUCCESS;
    }
}
