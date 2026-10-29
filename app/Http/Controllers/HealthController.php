<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'php' => PHP_VERSION_ID >= 80200,
            'laravel' => app()->bound('router'),
            'startup_maintenance' => ! File::exists(storage_path('framework/sr-maintenance.flag'))
                && ! File::exists(storage_path('framework/sr-maintenance.failed')),
            'storage' => $this->storageIsWritable(),
            'queue_worker' => $this->heartbeatIsFresh('queue'),
            'scheduler' => $this->heartbeatIsFresh('scheduler'),
            'queue_backlog' => true,
            'failed_jobs' => true,
            'database' => false,
        ];

        try {
            DB::selectOne('SELECT 1 AS ready');
            $checks['database'] = true;
        } catch (Throwable) {
            // A readiness response must not expose database or credential details.
        }

        if ($checks['database'] && config('operations.queue.database_metrics_enabled', false)) {
            try {
                $checks['queue_backlog'] = $this->queueBacklogIsHealthy();
                $checks['failed_jobs'] = $this->recentFailedJobsAreWithinThreshold();
            } catch (Throwable) {
                $checks['queue_backlog'] = false;
                $checks['failed_jobs'] = false;
            }
        }

        // A dead dependency or worker makes the instance unready. Backlog and
        // failed-job thresholds are operational degradation signals: expose
        // them and fail deployment verification without making Docker evict a
        // still-serving instance that may be needed for recovery.
        $ready = ! in_array(false, [
            $checks['php'],
            $checks['laravel'],
            $checks['startup_maintenance'],
            $checks['storage'],
            $checks['queue_worker'],
            $checks['scheduler'],
            $checks['database'],
        ], true);
        $degraded = $ready && (! $checks['queue_backlog'] || ! $checks['failed_jobs']);

        return response()
            ->json([
                'status' => ! $ready ? 'unhealthy' : ($degraded ? 'degraded' : 'ok'),
                'checks' => $checks,
            ], $ready ? 200 : 503)
            ->header('Cache-Control', 'no-store, private');
    }

    private function storageIsWritable(): bool
    {
        foreach ((array) config('operations.storage.paths', []) as $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                return false;
            }
        }

        return true;
    }

    private function heartbeatIsFresh(string $process): bool
    {
        if ($process === 'queue' && in_array(config('queue.default'), ['sync', 'null'], true)) {
            return true;
        }

        if ($process === 'scheduler' && ! config('operations.scheduler.enabled', true)) {
            return true;
        }

        if (! config("operations.{$process}.heartbeat_required", false)) {
            return true;
        }

        $relativePath = trim((string) config("operations.{$process}.heartbeat_file", ''), '/\\');
        if ($relativePath === '') {
            return false;
        }

        $path = storage_path($relativePath);
        $modifiedAt = @filemtime($path);

        return $modifiedAt !== false
            && $modifiedAt >= time() - (int) config("operations.{$process}.max_age_seconds", 300);
    }

    private function queueBacklogIsHealthy(): bool
    {
        if (config('queue.default') !== 'database' || ! Schema::hasTable('jobs')) {
            return true;
        }

        $pending = DB::table('jobs')->count();
        $oldestCreatedAt = DB::table('jobs')->min('created_at');

        if ($pending > (int) config('operations.queue.max_pending_jobs', 1000)) {
            return false;
        }

        return $oldestCreatedAt === null
            || (int) $oldestCreatedAt >= time() - (int) config('operations.queue.max_oldest_job_age_seconds', 900);
    }

    private function recentFailedJobsAreWithinThreshold(): bool
    {
        if (config('queue.default') !== 'database' || ! Schema::hasTable('failed_jobs')) {
            return true;
        }

        $failedSince = now()->subMinutes(
            (int) config('operations.queue.failed_job_window_minutes', 60)
        );

        return DB::table('failed_jobs')
            ->where('failed_at', '>=', $failedSince)
            ->count() <= (int) config('operations.queue.max_recent_failed_jobs', 5);
    }
}
