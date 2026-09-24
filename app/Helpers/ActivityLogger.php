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

        if ($notify && self::notificationsEnabledFor($action)) {
            $title = $notificationOptions['title'] ?? ucfirst(str_replace('_', ' ', $action));
            $message = $notificationOptions['message'] ?? $description;
            $icon = $notificationOptions['icon'] ?? 'fa-info-circle';
            $type = $notificationOptions['type'] ?? 'info';

            try {
                $user->notify(new UserActivityNotification($title, $message, $icon, $type));
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
