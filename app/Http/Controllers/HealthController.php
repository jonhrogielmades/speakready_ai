<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
            'database' => false,
        ];

        try {
            DB::selectOne('SELECT 1 AS ready');
            $checks['database'] = true;
        } catch (Throwable) {
            // A readiness response must not expose database or credential details.
        }

        $healthy = ! in_array(false, $checks, true);

        return response()
            ->json([
                'status' => $healthy ? 'ok' : 'unhealthy',
                'checks' => $checks,
            ], $healthy ? 200 : 503)
            ->header('Cache-Control', 'no-store, private');
    }
}
