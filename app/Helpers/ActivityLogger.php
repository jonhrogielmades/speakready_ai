<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use App\Notifications\UserActivityNotification;
use App\Support\AccountNotificationSchema;
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

        if ($notify) {
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
}
