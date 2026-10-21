<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AdminActivityAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $activityId,
        public string $action,
        public string $description,
        public string $actorName,
        public string $actorIdentifier,
        public ?string $ipAddress,
        public string $recordedAt,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'SpeakReady AI');
        $actionLabel = Str::headline($this->action);
        $message = trim($this->description) ?: 'An important platform activity was recorded.';

        $mail = (new MailMessage)
            ->subject("[Admin Alert] {$actionLabel} | {$appName}")
            ->greeting('Hello '.config('mail.admin_alerts.name', 'System Admin').',')
            ->line($message)
            ->line("Account: {$this->actorName} ({$this->actorIdentifier})")
            ->line("Activity: {$actionLabel}")
            ->line("Recorded: {$this->recordedAt}");

        if ($this->ipAddress !== null) {
            $mail->line("IP address: {$this->ipAddress}");
        }

        return $mail
            ->action('Review Admin Activity', route('admin.notifications.index'))
            ->salutation('SpeakReady AI');
    }
}
