<?php

namespace Tests\Unit;

use App\Jobs\DeleteVoiceRecordings;
use App\Services\VoiceRecordingCleanupService;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VoiceRecordingCleanupServiceTest extends TestCase
{
    public function test_cleanup_is_queued_with_the_recording_locations_for_durable_retries(): void
    {
        Queue::fake();
        $recordings = [[
            'disk' => 'local',
            'path' => 'private/interviews/recording.webm',
        ]];

        (new VoiceRecordingCleanupService())->delete($recordings, 'test_cleanup');

        Queue::assertPushed(
            DeleteVoiceRecordings::class,
            fn (DeleteVoiceRecordings $job): bool => $job->recordings === $recordings
                && $job->context === 'test_cleanup'
        );
    }

    public function test_cleanup_job_removes_a_stored_recording(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('private/interviews/recording.webm', 'audio');

        (new DeleteVoiceRecordings([[
            'disk' => 'local',
            'path' => 'private/interviews/recording.webm',
        ]], 'test_cleanup'))->handle();

        Storage::disk('local')->assertMissing('private/interviews/recording.webm');
    }

    public function test_cleanup_job_treats_an_already_missing_recording_as_success(): void
    {
        Storage::fake('local');

        (new DeleteVoiceRecordings([[
            'disk' => 'local',
            'path' => 'private/interviews/already-removed.webm',
        ]], 'retry_cleanup'))->handle();

        Storage::disk('local')->assertMissing('private/interviews/already-removed.webm');
    }

    public function test_dispatch_failure_falls_back_to_immediate_idempotent_deletion(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('private/interviews/fallback.webm', 'audio');
        $this->mock(Dispatcher::class, function ($mock): void {
            $mock->shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('queue unavailable'));
        });

        (new VoiceRecordingCleanupService())->delete([[
            'disk' => 'local',
            'path' => 'private/interviews/fallback.webm',
        ]], 'dispatch_failure');

        Storage::disk('local')->assertMissing('private/interviews/fallback.webm');
    }

    public function test_durable_cleanup_outbox_is_requeued_and_removed(): void
    {
        Queue::fake();
        $directory = storage_path('framework/operations/recording-cleanup-outbox');
        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);
        File::put($directory.'/pending.json', json_encode([
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'context' => 'durable_retry',
            'recordings' => [[
                'disk' => 'local',
                'path' => 'private/interviews/pending.webm',
            ]],
        ], JSON_THROW_ON_ERROR));

        try {
            $result = (new VoiceRecordingCleanupService())->retryPending();

            $this->assertSame(['queued' => 1, 'pending' => 0, 'invalid' => 0], $result);
            $this->assertFileDoesNotExist($directory.'/pending.json');
            Queue::assertPushed(
                DeleteVoiceRecordings::class,
                fn (DeleteVoiceRecordings $job): bool => $job->context === 'durable_retry'
            );
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
