<?php

namespace App\Notifications;

use App\Notifications\Channels\WebPushChannel;
use App\Services\WebPushNotificationService;
use App\Support\SystemSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $message;
    protected $type;
    protected $url;
    protected $tag;
    protected $requireInteraction;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $type = 'info', $url = null, $tag = null, $requireInteraction = true)
    {
        $this->afterCommit();
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->url = $url;
        $this->tag = $tag;
        $this->requireInteraction = (bool) $requireInteraction;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (SystemSettings::enabled('notif_email', false)) {
            $channels[] = 'mail';
        }

        if (! (bool) ($notifiable->is_admin ?? false) && app(WebPushNotificationService::class)->canNotify($notifiable)) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $icon = 'fa-info-circle';
        if ($this->type === 'success') $icon = 'fa-check-circle';
        if ($this->type === 'warning') $icon = 'fa-exclamation-triangle';
        if ($this->type === 'danger') $icon = 'fa-times-circle';

        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'icon' => $icon,
            'importance' => 'important',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'SpeakReady AI');
        $recipientName = trim((string) ($notifiable->name ?? ''));
        $greeting = $recipientName !== '' ? 'Hello '.$recipientName.',' : 'Hello there,';

        $mail = (new MailMessage)
            ->subject($this->title.' | '.$appName)
            ->greeting($greeting)
            ->line($this->message)
            ->salutation('Keep moving forward,');

        $mail->action('View update', $this->url ?: route('user.notifications'));

        return $mail;
    }

    public function toWebPush(object $notifiable): array
    {
        $url = $this->url ?: route('user.notifications');

        return [
            'title' => $this->title,
            'body' => $this->message,
            'iconUrl' => WebPushNotificationService::DEFAULT_NOTIFICATION_ICON,
            'badgeUrl' => WebPushNotificationService::DEFAULT_NOTIFICATION_BADGE,
            'tag' => $this->tag ?: 'speakready-system-'.$this->type,
            'urgency' => 'high',
            'requireInteraction' => $this->requireInteraction,
            'url' => $url,
            'data' => [
                'url' => $url,
                'type' => $this->type,
                'category' => 'system',
            ],
        ];
    }
}
