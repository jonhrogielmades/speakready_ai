<?php

namespace App\Console\Commands;

use App\Jobs\GenerateInterviewFeedback;
use App\Jobs\GenerateSessionProviderPreview;
use App\Models\InterviewSession;
use App\Support\SystemSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequeueStaleInterviewFeedback extends Command
{
    protected $signature = 'feedback:requeue-stale
        {--limit=50 : Maximum reviews to recover per run}
        {--reset-config-failures : Requeue terminal credential or model failures after fixing provider configuration}';

    protected $description = 'Requeue provider feedback when a worker or provider attempt did not finish';

    public function handle(): int
    {
        if (! SystemSettings::enabled('int_ai_eval', true)) {
            $this->info('AI interview evaluation is disabled; no provider reviews were requeued.');
            return self::SUCCESS;
        }

        $limit = max(1, min(500, (int) $this->option('limit')));
        $pendingCutoff = now()->subMinutes(3);
        $processingCutoff = now()->subMinutes(5);
        $failedCutoff = now()->subMinutes(15);
        $resetConfigFailures = (bool) $this->option('reset-config-failures');
        $configurationFailureCodes = ['no_provider_credentials', 'provider_authentication', 'provider_model'];
        $queued = 0;

        // A worker may die after starting its ninth attempt, before Laravel
        // can invoke failed(). Surface that exhausted review as failed rather
        // than leaving the review page in a permanent processing state.
        InterviewSession::query()
            ->where('status', 'completed')
            ->whereNull('game_level_id')
            ->where('feedback_attempts', '>=', 9)
            ->where(function ($query) use ($processingCutoff): void {
                $query->where(fn ($pending) => $pending->where('feedback_status', 'pending')->where('updated_at', '<=', $processingCutoff))
                    ->orWhere(fn ($processing) => $processing->where('feedback_status', 'processing')->where('feedback_started_at', '<=', $processingCutoff));
            })
            ->update([
                'feedback_status' => 'failed',
                // Retain an authentication or model error so an operator can
                // recover it after correcting the provider configuration.
                'feedback_failure_code' => DB::raw("COALESCE(feedback_failure_code, 'retry_limit_exceeded')"),
            ]);

        $sessions = InterviewSession::query()
            ->where('status', 'completed')
            ->whereNull('game_level_id')
            ->where(function ($query) use ($resetConfigFailures, $configurationFailureCodes): void {
                $query->where('feedback_attempts', '<', 9);
                if ($resetConfigFailures) {
                    $query->orWhere(fn ($failed) => $failed->where('feedback_status', 'failed')
                        ->where('feedback_attempts', '>=', 9)
                        ->whereIn('feedback_failure_code', $configurationFailureCodes));
                }
            })
            ->where(function ($query) use ($pendingCutoff, $processingCutoff, $failedCutoff, $resetConfigFailures, $configurationFailureCodes): void {
                $query->where(fn ($pending) => $pending->where('feedback_status', 'pending')->where('updated_at', '<=', $pendingCutoff))
                    ->orWhere(fn ($processing) => $processing->where('feedback_status', 'processing')->where('feedback_started_at', '<=', $processingCutoff))
                    ->orWhere(fn ($failed) => $failed->where('feedback_status', 'failed')->where('updated_at', '<=', $failedCutoff));
                if ($resetConfigFailures) {
                    $query->orWhere(fn ($failed) => $failed->where('feedback_status', 'failed')
                        ->where('feedback_attempts', '>=', 9)
                        ->whereIn('feedback_failure_code', $configurationFailureCodes));
                }
            })
            ->orderBy('id')
            // Stream candidates so early ended sessions cannot consume the limit
            // and starve valid reviews with larger IDs.
            ->cursor();

        foreach ($sessions as $session) {
            if ($queued >= $limit) {
                break;
            }
            if ((bool) data_get($session->action_plan ?? [], 'ended_early', false)) {
                continue;
            }

            $resetAttempts = $resetConfigFailures && $session->feedback_status === 'failed'
                && $session->feedback_attempts >= 9
                && in_array($session->feedback_failure_code, $configurationFailureCodes, true);

            $claimed = InterviewSession::query()
                ->whereKey($session->id)
                ->where('feedback_status', $session->feedback_status)
                ->where('feedback_attempts', $session->feedback_attempts)
                // A token claim stays exclusive even when database timestamps
                // have only one-second precision.
                ->when(
                    $session->feedback_processing_token !== null,
                    fn ($query) => $query->where('feedback_processing_token', $session->feedback_processing_token),
                    fn ($query) => $query->whereNull('feedback_processing_token')
                )
                ->update([
                    'feedback_status' => 'pending',
                    'feedback_failure_code' => null,
                    'feedback_attempts' => $resetAttempts ? 0 : $session->feedback_attempts,
                    'feedback_processing_token' => (string) Str::uuid(),
                    'updated_at' => now(),
                ]);
            if ($claimed !== 1) {
                continue;
            }

            try {
                GenerateSessionProviderPreview::dispatch((int) $session->id, (int) $session->user_id);
            } catch (\Throwable $error) {
                Log::warning('Stale provider summary preview could not be requeued.', [
                    'session_id' => $session->id,
                    'error_type' => $error::class,
                ]);
            }

            try {
                GenerateInterviewFeedback::dispatch((int) $session->id, (int) $session->user_id);
                $queued++;
            } catch (\Throwable $error) {
                InterviewSession::query()->whereKey($session->id)->where('feedback_status', 'pending')
                    ->update(['feedback_status' => 'failed', 'feedback_failure_code' => 'queue_dispatch_failed']);
                Log::warning('Stale provider feedback could not be requeued.', [
                    'session_id' => $session->id,
                    'error_type' => $error::class,
                ]);
            }
        }

        $this->info("Requeued {$queued} provider feedback reviews.");

        return self::SUCCESS;
    }
}
