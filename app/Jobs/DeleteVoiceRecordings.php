<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class DeleteVoiceRecordings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 60;

    /**
     * @param  array<int, array{disk: string, path: string}>  $recordings
     */
    public function __construct(
        public readonly array $recordings,
        public readonly string $context
    ) {
        $this->onQueue('default');
        $this->afterCommit();
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(): void
    {
        $failures = [];

        foreach ($this->recordings as $recording) {
            $disk = trim($recording['disk']) ?: 'local';
            $path = trim($recording['path']);

            if ($path === '' || str_contains($path, '..') || str_contains($path, '\\')) {
                continue;
            }

            try {
                $storage = Storage::disk($disk);

                // Deletion is intentionally idempotent. A retry may run after
                // an earlier attempt removed the object but failed before the
                // queue could acknowledge the job.
                if (! $storage->exists($path)) {
                    continue;
                }

                if (! $storage->delete($path)) {
                    $failures[] = [$disk, $path, 'delete_returned_false'];
                }
            } catch (Throwable $error) {
                $failures[] = [$disk, $path, $error::class];
            }
        }

        if ($failures === []) {
            return;
        }

        Log::warning('Voice recording cleanup will be retried.', [
            'context' => $this->context,
            'failed_count' => count($failures),
            'path_hashes' => array_map(
                fn (array $failure): string => hash('sha256', $failure[0].'|'.$failure[1]),
                $failures
            ),
            'failure_types' => array_values(array_unique(array_column($failures, 2))),
        ]);

        throw new RuntimeException('One or more voice recordings could not be removed.');
    }
}
