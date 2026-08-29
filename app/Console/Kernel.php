<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        if (filter_var(config('services.local_feedback_model.auto_train_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            $schedule->command('ai:auto-train-feedback-model')
                ->dailyAt((string) config('services.local_feedback_model.auto_train_time', '02:30'))
                ->withoutOverlapping(120);
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
