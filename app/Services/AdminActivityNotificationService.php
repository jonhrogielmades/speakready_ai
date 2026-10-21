<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\AdminActivityAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AdminActivityNotificationService
{
    public function notify(ActivityLog $activity, User $actor): bool
    {
        if (! (bool) config('mail.admin_alerts.enabled', true)
            || ! in_array($activity->action, ActivityLog::ADMIN_IMPORTANT_ACTIONS, true)) {
            return false;
        }

        $address = strtolower(trim((string) config('mail.admin_alerts.address')));

        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Administrator activity email was skipped because its recipient is invalid.', [
                'activity_log_id' => $activity->id,
                'action' => $activity->action,
            ]);

            return false;
        }

        $recipientName = trim((string) config('mail.admin_alerts.name', 'System Admin')) ?: 'System Admin';
        $actorName = trim((string) $actor->name) ?: 'Unknown user';
        $actorIdentifier = trim((string) $actor->email)
            ?: (trim((string) $actor->username) ?: '#'.$actor->id);
        $recordedAt = $activity->created_at
            ? $activity->created_at->copy()->timezone(config('app.timezone'))->format('M j, Y g:i A T')
            : now()->format('M j, Y g:i A T');

        Notification::route('mail', [$address => $recipientName])
            ->notify(new AdminActivityAlert(
                activityId: (int) $activity->id,
                action: (string) $activity->action,
                description: (string) $activity->description,
                actorName: $actorName,
                actorIdentifier: $actorIdentifier,
                ipAddress: filled($activity->ip_address) ? (string) $activity->ip_address : null,
                recordedAt: $recordedAt,
            ));

        return true;
    }
}
