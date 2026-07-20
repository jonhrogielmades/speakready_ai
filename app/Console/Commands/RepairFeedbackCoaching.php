<?php

namespace App\Console\Commands;

use App\Models\InterviewSession;
use App\Support\FeedbackCoachingRepair;
use Illuminate\Console\Command;

class RepairFeedbackCoaching extends Command
{
    protected $signature = 'app:repair-feedback-coaching
        {--limit=1000 : Maximum completed sessions to inspect. Use 0 for all.}
        {--dry-run : Report how many sessions would be repaired without saving changes.}';

    protected $description = 'Backfill missing evidence-based coaching report data from saved interview answers.';

    public function handle(FeedbackCoachingRepair $repair): int
    {
        if (! $repair->canPersistSummary() && ! $repair->canPersistAnswerCoaching()) {
            $this->warn('Coaching storage columns are missing. Run migrations first; report pages will still use display fallbacks.');

            return self::SUCCESS;
        }

        $limit = max(0, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $repaired = 0;
        $skipped = 0;

        InterviewSession::query()
            ->where('status', 'completed')
            ->whereHas('feedback')
            ->with([
                'feedback',
                'answers' => fn ($query) => $query
                    ->whereNull('retry_of_answer_id')
                    ->with('question'),
            ])
            ->orderBy('id')
            ->chunkById(100, function ($sessions) use ($repair, $limit, $dryRun, &$checked, &$repaired, &$skipped): bool {
                foreach ($sessions as $session) {
                    if ($limit > 0 && $checked >= $limit) {
                        return false;
                    }

                    $checked++;
                    $needsRepair = $repair->summaryNeedsRepair($session->feedback?->coaching_summary ?? null)
                        || $session->answers->contains(
                            fn ($answer): bool => $repair->answerCoachingNeedsRepair($answer->coaching_feedback ?? null)
                        );

                    if (! $needsRepair) {
                        $skipped++;
                        continue;
                    }

                    if ($dryRun) {
                        $repaired++;
                        continue;
                    }

                    if ($repair->repairSession($session)) {
                        $repaired++;
                    } else {
                        $skipped++;
                    }
                }

                return true;
            });

        $verb = $dryRun ? 'would repair' : 'repaired';
        $this->info("Checked {$checked} completed sessions; {$verb} {$repaired}; skipped {$skipped}.");

        return self::SUCCESS;
    }
}
