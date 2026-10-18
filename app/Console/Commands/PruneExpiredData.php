<?php

namespace App\Console\Commands;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Support\SystemSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PruneExpiredData extends Command
{
    protected $signature = 'privacy:prune
        {--force : Allow deletion of expired records}
        {--dry-run : Report what would be removed without deleting anything}';

    protected $description = 'Remove expired interview, coaching, and push-subscription data using configured retention periods';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force')) {
            $this->error('Refusing to delete data without --force. Use --dry-run to preview the cleanup.');

            return self::FAILURE;
        }

        $interviewDays = max(1, (int) SystemSettings::value('retention_interview', 365));
        $archiveDays = max($interviewDays, (int) SystemSettings::value('retention_archive', 730));
        $chatDays = max(1, (int) SystemSettings::value('retention_feedback', 365));

        $deletedSessions = $this->pruneInterviewSessions($interviewDays, $archiveDays, $dryRun);
        $deletedConversations = $this->pruneChatbotConversations($chatDays, $dryRun);
        $deletedPushSubscriptions = $this->prunePushSubscriptions($dryRun);

        $mode = $dryRun ? 'would remove' : 'removed';
        $this->info("Privacy cleanup complete: {$mode} {$deletedSessions} interview sessions, {$deletedConversations} coaching conversations, and {$deletedPushSubscriptions} push subscriptions.");

        return self::SUCCESS;
    }

    private function pruneInterviewSessions(int $interviewDays, int $archiveDays, bool $dryRun): int
    {
        if (! Schema::hasTable('interview_sessions')) {
            return 0;
        }

        $interviewCutoff = now()->subDays($interviewDays);
        $archiveCutoff = now()->subDays($archiveDays);

        $query = InterviewSession::query()
            ->whereIn('status', ['completed', 'aborted', 'cancelled'])
            ->where(function ($builder) use ($interviewCutoff, $archiveCutoff): void {
                if (InterviewSession::hasColumn('is_archived')) {
                    $builder
                        ->where(function ($archived) use ($archiveCutoff): void {
                            $archived->where('is_archived', true)->where('updated_at', '<', $archiveCutoff);
                        })
                        ->orWhere(function ($active) use ($interviewCutoff): void {
                            $active->where(function ($notArchived): void {
                                $notArchived->where('is_archived', false)->orWhereNull('is_archived');
                            })->where('updated_at', '<', $interviewCutoff);
                        });

                    return;
                }

                $builder->where('updated_at', '<', $interviewCutoff);
            });

        $count = (int) $query->count();

        if ($dryRun || $count === 0) {
            return $count;
        }

        $query->select(['id'])->orderBy('id')->chunkById(50, function ($sessions): void {
            $sessionIds = $sessions->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $this->deleteInterviewArtifacts($sessionIds);
            InterviewSession::query()->whereIn('id', $sessionIds)->delete();
        });

        return $count;
    }

    private function deleteInterviewArtifacts(array $sessionIds): void
    {
        if ($sessionIds === [] || ! Schema::hasTable('interview_answers')
            || ! Schema::hasColumn('interview_answers', 'voice_recording_path')) {
            return;
        }

        $columns = ['voice_recording_path'];
        if (Schema::hasColumn('interview_answers', 'voice_recording_disk')) {
            $columns[] = 'voice_recording_disk';
        }

        InterviewAnswer::query()
            ->whereIn('interview_session_id', $sessionIds)
            ->get($columns)
            ->each(function (InterviewAnswer $answer): void {
                $path = trim((string) $answer->voice_recording_path);
                if ($path === '') {
                    return;
                }

                $disk = trim((string) ($answer->voice_recording_disk ?: 'local'));

                try {
                    Storage::disk($disk)->delete($path);
                } catch (Throwable $error) {
                    $this->warn('Unable to remove one interview recording: '.$error::class);
                }
            });
    }

    private function pruneChatbotConversations(int $days, bool $dryRun): int
    {
        if (! Schema::hasTable('chatbot_conversations')) {
            return 0;
        }

        $conversationIds = DB::table('chatbot_conversations')
            ->where('updated_at', '<', now()->subDays($days))
            ->pluck('id');

        $count = $conversationIds->count();

        if (! $dryRun && $count > 0) {
            $conversationIds->chunk(500)->each(function ($ids): void {
                if (Schema::hasTable('chatbot_messages')) {
                    DB::table('chatbot_messages')->whereIn('chatbot_conversation_id', $ids->all())->delete();
                }

                DB::table('chatbot_conversations')->whereIn('id', $ids->all())->delete();
            });
        }

        return $count;
    }

    private function prunePushSubscriptions(bool $dryRun): int
    {
        if (! Schema::hasTable('push_subscriptions')) {
            return 0;
        }

        $query = DB::table('push_subscriptions')->where(function ($builder): void {
            $builder
                ->where('failed_at', '<', now()->subDays(30))
                ->orWhere(function ($inactive): void {
                    $inactive->whereNull('failed_at')->where(function ($lastUsed): void {
                        $lastUsed->where('last_used_at', '<', now()->subDays(180))
                            ->orWhere(function ($neverUsed): void {
                                $neverUsed->whereNull('last_used_at')->where('created_at', '<', now()->subDays(180));
                            });
                    });
                });
        });

        $count = (int) $query->count();

        if (! $dryRun && $count > 0) {
            $query->delete();
        }

        return $count;
    }
}
