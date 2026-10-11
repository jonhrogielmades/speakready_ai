<?php

namespace App\Services;

use App\Models\Category;
use App\Models\GameLevel;
use App\Models\LearningModule;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Support\AccountNotificationSchema;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserContentUpdateNotificationService
{
    public function categoryCreated(Category $category): int
    {
        if ((string) $category->status !== 'active') {
            return 0;
        }

        $type = strtolower((string) $category->type);
        $title = match ($type) {
            'game' => 'New Challenge Category',
            'learning' => 'New Learning Category',
            default => 'New Interview Category',
        };

        $url = match ($type) {
            'game' => route('user.learning', ['category_id' => $category->id]),
            'learning' => route('user.modules.index', ['category' => $category->title]),
            default => route('interview.setup'),
        };

        return $this->notifyUsers(
            $title,
            "{$category->title} is now available in SpeakReady AI.",
            $url,
            'speakready-content-category-'.$category->id
        );
    }

    public function learningModulePublished(LearningModule $module): int
    {
        if ((string) $module->status !== 'published') {
            return 0;
        }

        return $this->notifyUsers(
            'New Learning Module',
            "{$module->title} is now available in Learning Modules.",
            route('user.modules.show', $module->id),
            'speakready-content-module-'.$module->id
        );
    }

    public function learningGameCreated(GameLevel $level): int
    {
        if ((bool) $level->is_hidden) {
            return 0;
        }

        return $this->notifyUsers(
            'New Learning Challenge',
            'Level '.$level->level_number.': '.$level->title.' is now available.',
            $this->learningGameUrl($level),
            'speakready-content-game-'.$level->id
        );
    }

    public function learningGamesGenerated(int $count, string $topic, ?Category $category = null): int
    {
        if ($count <= 0) {
            return 0;
        }

        $label = $count === 1 ? 'challenge' : 'challenges';
        $topic = Str::of($topic)->squish()->limit(120, '')->toString();
        $context = $topic !== '' ? " for {$topic}" : '';

        return $this->notifyUsers(
            'New Learning Challenges',
            "{$count} new learning {$label}{$context} are now available.",
            $category ? route('user.learning', ['category_id' => $category->id]) : route('user.learning'),
            'speakready-content-games-'.($category?->id ?? 'all').'-'.now()->timestamp
        );
    }

    private function notifyUsers(string $title, string $message, string $url, string $tag): int
    {
        if (! SystemSettings::enabled('notif_sys', true)) {
            return 0;
        }

        AccountNotificationSchema::ensure();

        $notification = new SystemNotification($title, $message, 'info', $url, $tag, true);
        $sent = 0;

        User::query()
            ->where('is_admin', false)
            ->where('status', 'active')
            ->select(['id', 'name', 'email', 'is_admin', 'status'])
            ->chunkById(100, function ($users) use ($notification, &$sent): void {
                foreach ($users as $user) {
                    try {
                        $user->notify($notification);
                        $sent++;
                    } catch (\Throwable $error) {
                        Log::warning('Unable to notify user about new admin content.', [
                            'user_id' => $user->id,
                            'error_type' => $error::class,
                            'message' => $error->getMessage(),
                        ]);
                    }
                }
            });

        return $sent;
    }

    private function learningGameUrl(GameLevel $level): string
    {
        return $level->category_id
            ? route('user.learning', ['category_id' => $level->category_id])
            : route('user.learning');
    }
}
