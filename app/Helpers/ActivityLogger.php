<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use App\Notifications\UserActivityNotification;
use App\Support\AccountNotificationSchema;
use App\Support\SystemSettings;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    /**
     * Log user activity and optionally send a notification.
     *
     * @param \App\Models\User $user
     * @param string $action
     * @param string $description
     * @param string|null $ipAddress
     * @param bool $notify
     * @param array $notificationOptions
     * @return \App\Models\ActivityLog
     */
    public static function log($user, $action, $description = '', $ipAddress = null, $notify = true, $notificationOptions = [])
    {
        AccountNotificationSchema::ensure();

        $log = ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress,
        ]);

        $defaults = self::importantNotificationDefaults($action);
        $shouldNotify = $notify || $defaults !== [];

        $canNotifyUser = (bool) ($user->is_admin ?? false)
            || in_array($action, ActivityLog::USER_IMPORTANT_ACTIONS, true);

        if ($shouldNotify && $canNotifyUser && self::notificationsEnabledFor($action)) {
            $notificationOptions = array_merge($defaults, $notificationOptions);
            $title = $notificationOptions['title'] ?? ucfirst(str_replace('_', ' ', $action));
            $message = $notificationOptions['message'] ?? $description;
            $icon = $notificationOptions['icon'] ?? 'fa-info-circle';
            $type = $notificationOptions['type'] ?? 'info';
            $sendPush = (bool) ($notificationOptions['push'] ?? false);
            $url = $notificationOptions['url'] ?? null;
            $tag = $notificationOptions['tag'] ?? null;
            $requireInteraction = (bool) ($notificationOptions['requireInteraction'] ?? $sendPush);

            try {
                $user->notify(new UserActivityNotification(
                    $title,
                    $message,
                    $icon,
                    $type,
                    $sendPush,
                    $url,
                    $tag,
                    $requireInteraction
                ));
            } catch (\Throwable $error) {
                Log::warning('Unable to write user activity notification.', [
                    'user_id' => $user->id ?? null,
                    'action' => $action,
                    'error_type' => $error::class,
                    'message' => $error->getMessage(),
                ]);
            }
        }

        return $log;
    }

    private static function importantNotificationDefaults(string $action): array
    {
        return match ($action) {
            'user_logged_in' => [
                'title' => 'New Sign-In',
                'message' => 'Your account was signed in.',
                'icon' => 'fa-right-to-bracket',
                'type' => 'info',
            ],
            'user_logged_out' => [
                'title' => 'Signed Out',
                'message' => 'Your account was signed out.',
                'icon' => 'fa-right-from-bracket',
                'type' => 'info',
            ],
            'language_updated' => [
                'title' => 'Language Updated',
                'icon' => 'fa-language',
                'type' => 'success',
            ],
            'account_deleted' => [
                'title' => 'Account Deleted',
                'message' => 'Your account deletion request was processed.',
                'icon' => 'fa-user-slash',
                'type' => 'warning',
            ],
            'interview_answer_retry_saved' => [
                'title' => 'Retry Saved',
                'message' => 'Your improved interview answer was saved.',
                'icon' => 'fa-rotate-right',
                'type' => 'success',
            ],
            'learning_game_started' => [
                'title' => 'Challenge Started',
                'icon' => 'fa-gamepad',
                'type' => 'info',
            ],
            'learning_game_certificate_issued' => [
                'title' => 'Certificate Earned',
                'icon' => 'fa-certificate',
                'type' => 'success',
            ],
            'learning_game_certificate_downloaded' => [
                'title' => 'Certificate Downloaded',
                'icon' => 'fa-certificate',
                'type' => 'success',
            ],
            'learning_module_progress_updated' => [
                'title' => 'Learning Progress Updated',
                'icon' => 'fa-book-open-reader',
                'type' => 'success',
            ],
            'ai_coach_conversation_started' => [
                'title' => 'AI Coach Started',
                'icon' => 'fa-comments',
                'type' => 'info',
            ],
            'ai_coach_conversation_deleted' => [
                'title' => 'Coach Conversation Deleted',
                'icon' => 'fa-trash-can',
                'type' => 'warning',
            ],
            'ai_coach_conversations_cleared' => [
                'title' => 'Coach Conversations Cleared',
                'icon' => 'fa-broom',
                'type' => 'warning',
            ],
            'job_application_updated' => [
                'title' => 'Application Updated',
                'icon' => 'fa-briefcase',
                'type' => 'success',
            ],
            'job_application_deleted' => [
                'title' => 'Application Removed',
                'icon' => 'fa-briefcase',
                'type' => 'warning',
            ],
            'admin_settings_updated' => [
                'title' => 'System Settings Updated',
                'message' => 'System settings were updated.',
                'icon' => 'fa-sliders',
                'type' => 'warning',
            ],
            'admin_settings_backup_downloaded' => [
                'title' => 'Settings Backup Downloaded',
                'message' => 'A system settings backup was downloaded.',
                'icon' => 'fa-download',
                'type' => 'info',
            ],
            'admin_settings_backup_restored' => [
                'title' => 'Settings Backup Restored',
                'message' => 'A system settings backup was restored.',
                'icon' => 'fa-upload',
                'type' => 'warning',
            ],
            default => [],
        };
    }

    private static function notificationsEnabledFor(string $action): bool
    {
        if (! SystemSettings::enabled('notif_sys', true)) {
            return false;
        }

        if (str_contains($action, 'achievement') || str_contains($action, 'certificate') || str_contains($action, 'perk')) {
            return SystemSettings::enabled('notif_achieve', true);
        }

        if (str_contains($action, 'reminder')) {
            return SystemSettings::enabled('notif_reminders', true);
        }

        return true;
    }
}
