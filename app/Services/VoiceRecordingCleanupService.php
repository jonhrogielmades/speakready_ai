<?php

namespace App\Services;

use App\Jobs\DeleteVoiceRecordings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;

final class VoiceRecordingCleanupService
{
    private const OUTBOX_DIRECTORY = 'framework/operations/recording-cleanup-outbox';

    /**
     * @param array<int, int> $sessionIds
     * @return array<int, array{disk: string, path: string}>
     */
    public function interviewRecordings(array $sessionIds): array
    {
        return $this->recordings('interview_answers', 'interview_session_id', $sessionIds);
    }

    /**
     * @param array<int, int> $sessionIds
     * @return array<int, array{disk: string, path: string}>
     */
    public function gameRecordings(array $sessionIds): array
    {
        return $this->recordings('game_answers', 'game_session_id', $sessionIds);
    }

    /** @param array<int, array{disk: string, path: string}> $recordings */
    public function delete(array $recordings, string $context): void
    {
        if ($recordings === []) {
            return;
        }

        try {
            DeleteVoiceRecordings::dispatch($recordings, $context);
        } catch (Throwable $error) {
            Log::warning('Voice recording cleanup could not be queued; trying it synchronously.', [
                'context' => $context,
                'recording_count' => count($recordings),
                'exception_type' => $error::class,
            ]);

            try {
                (new DeleteVoiceRecordings($recordings, $context))->handle();

                return;
            } catch (Throwable $fallbackError) {
                $outboxId = $this->persistForRetry($recordings, $context);

                Log::error('Voice recording cleanup requires a durable retry.', [
                    'context' => $context,
                    'recording_count' => count($recordings),
                    'exception_type' => $fallbackError::class,
                    'outbox_id' => $outboxId,
                ]);
            }
        }
    }

    /** @return array{queued: int, pending: int, invalid: int} */
    public function retryPending(int $limit = 100): array
    {
        $result = ['queued' => 0, 'pending' => 0, 'invalid' => 0];
        $directory = $this->outboxDirectory();

        if (! File::isDirectory($directory)) {
            return $result;
        }

        $lock = Cache::lock('recording-cleanup-outbox:retry', 120);
        if (! $lock->get()) {
            $result['pending'] = $this->pendingFiles($directory)->count();

            return $result;
        }

        try {
            $files = $this->pendingFiles($directory)->take(max(1, $limit));

            foreach ($files as $file) {
                try {
                    $payload = json_decode(File::get($file->getPathname()), true, 32, JSON_THROW_ON_ERROR);
                    $recordings = is_array($payload['recordings'] ?? null) ? $payload['recordings'] : [];
                    $context = trim((string) ($payload['context'] ?? 'recording_cleanup_retry'));

                    if ($recordings === []) {
                        throw new \UnexpectedValueException('Recording cleanup outbox entry is empty.');
                    }

                    DeleteVoiceRecordings::dispatch($recordings, $context);
                    File::delete($file->getPathname());
                    $result['queued']++;
                } catch (\JsonException|\UnexpectedValueException $error) {
                    $invalidDirectory = $directory.DIRECTORY_SEPARATOR.'invalid';
                    File::ensureDirectoryExists($invalidDirectory);
                    File::move(
                        $file->getPathname(),
                        $invalidDirectory.DIRECTORY_SEPARATOR.$file->getFilename()
                    );
                    $result['invalid']++;

                    Log::error('Invalid voice recording cleanup outbox entry was quarantined.', [
                        'outbox_id' => $file->getBasename('.json'),
                        'exception_type' => $error::class,
                    ]);
                } catch (Throwable $error) {
                    Log::warning('Voice recording cleanup outbox entry remains pending.', [
                        'outbox_id' => $file->getBasename('.json'),
                        'exception_type' => $error::class,
                    ]);
                }
            }

            $result['pending'] = $this->pendingFiles($directory)->count();
        } finally {
            $lock->release();
        }

        return $result;
    }

    /**
     * @param array<int, int> $sessionIds
     * @return array<int, array{disk: string, path: string}>
     */
    private function recordings(string $table, string $sessionColumn, array $sessionIds): array
    {
        if ($sessionIds === []
            || ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $sessionColumn)
            || ! Schema::hasColumn($table, 'voice_recording_path')) {
            return [];
        }

        $diskExpression = Schema::hasColumn($table, 'voice_recording_disk')
            ? 'voice_recording_disk as disk'
            : DB::raw("'local' as disk");

        return DB::table($table)
            ->whereIn($sessionColumn, $sessionIds)
            ->whereNotNull('voice_recording_path')
            ->where('voice_recording_path', '!=', '')
            ->get([$diskExpression, 'voice_recording_path as path'])
            ->map(fn ($recording): array => [
                'disk' => trim((string) ($recording->disk ?: 'local')),
                'path' => trim((string) $recording->path),
            ])
            ->filter(fn (array $recording): bool => $recording['path'] !== '')
            ->unique(fn (array $recording): string => $recording['disk'].'|'.$recording['path'])
            ->values()
            ->all();
    }

    /**
     * @param array<int, array{disk: string, path: string}> $recordings
     */
    private function persistForRetry(array $recordings, string $context): ?string
    {
        try {
            $directory = $this->outboxDirectory();
            File::ensureDirectoryExists($directory);
            $id = (string) Str::uuid();
            $temporary = $directory.DIRECTORY_SEPARATOR.'.'.$id.'.tmp';
            $destination = $directory.DIRECTORY_SEPARATOR.$id.'.json';
            $payload = json_encode([
                'version' => 1,
                'created_at' => now()->toIso8601String(),
                'context' => $context,
                'recordings' => $recordings,
            ], JSON_THROW_ON_ERROR);

            File::put($temporary, $payload, true);
            File::move($temporary, $destination);

            return $id;
        } catch (Throwable $error) {
            Log::critical('Voice recording cleanup retry record could not be persisted.', [
                'context' => $context,
                'recording_count' => count($recordings),
                'exception_type' => $error::class,
            ]);

            return null;
        }
    }

    /** @return Collection<int, SplFileInfo> */
    private function pendingFiles(string $directory): Collection
    {
        return collect(File::files($directory))
            ->filter(fn ($file): bool => $file->getExtension() === 'json')
            ->sortBy(fn ($file): string => $file->getFilename())
            ->values();
    }

    private function outboxDirectory(): string
    {
        return storage_path(self::OUTBOX_DIRECTORY);
    }
}
