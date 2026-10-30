<?php

return [
    'startup' => [
        // Containers override these with paths under /tmp/speakready. Keep
        // storage-based defaults for local PHP/Apache development and tests.
        'maintenance_flag' => env(
            'STARTUP_MAINTENANCE_FLAG_PATH',
            storage_path('framework/sr-maintenance.flag')
        ),
        'maintenance_failure' => env(
            'STARTUP_MAINTENANCE_FAILURE_PATH',
            storage_path('framework/sr-maintenance.failed')
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Runtime process health
    |--------------------------------------------------------------------------
    |
    | The container writes these heartbeat files from its supervised queue and
    | scheduler loops. They are disabled by default for non-container installs;
    | production Compose enables them explicitly.
    |
    */
    'queue' => [
        'heartbeat_required' => (bool) env('QUEUE_HEALTHCHECK_ENABLED', false),
        'heartbeat_file' => env('QUEUE_HEARTBEAT_FILE', 'framework/operations/queue-worker.heartbeat'),
        'max_age_seconds' => max(60, (int) env('QUEUE_HEARTBEAT_MAX_AGE_SECONDS', 420)),
        'database_metrics_enabled' => (bool) env('QUEUE_DATABASE_HEALTHCHECK_ENABLED', false),
        'max_pending_jobs' => max(1, (int) env('QUEUE_MAX_PENDING_JOBS', 1000)),
        'max_oldest_job_age_seconds' => max(60, (int) env('QUEUE_MAX_OLDEST_JOB_AGE_SECONDS', 900)),
        'max_recent_failed_jobs' => max(0, (int) env('QUEUE_MAX_RECENT_FAILED_JOBS', 5)),
        'failed_job_window_minutes' => max(1, (int) env('QUEUE_FAILED_JOB_WINDOW_MINUTES', 60)),
    ],

    'scheduler' => [
        'enabled' => (bool) env('RUN_SCHEDULER_ON_START', true),
        'heartbeat_required' => (bool) env('SCHEDULER_HEALTHCHECK_ENABLED', false),
        'heartbeat_file' => env('SCHEDULER_HEARTBEAT_FILE', 'framework/operations/scheduler.heartbeat'),
        'max_age_seconds' => max(60, (int) env('SCHEDULER_HEARTBEAT_MAX_AGE_SECONDS', 180)),
    ],

    'storage' => [
        'paths' => [
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ],
    ],

    'privacy' => [
        'pending_recovery_email_days' => max(1, (int) env('PENDING_RECOVERY_EMAIL_RETENTION_DAYS', 7)),
        'pending_newsletter_days' => max(1, (int) env('PENDING_NEWSLETTER_RETENTION_DAYS', 7)),
    ],
];
