<?php

namespace App\Notifications;

use App\Notifications\Channels\WebPushChannel;
use App\Services\WebPushNotificationService;
use App\Support\SystemSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserActivityNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $icon;
    protected $type;
    protected $sendPush;
    protected $url;
    protected $tag;
    protected $requireInteraction;
    protected $sendEmail;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $icon = 'fa-bell', $type = 'info', $sendPush = false, $url = null, $tag = null, $requireInteraction = false, $sendEmail = true)
    {
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->type = $type;
        $this->sendPush = (bool) $sendPush;
        $this->url = $url;
        $this->tag = $tag;
        $this->requireInteraction = (bool) $requireInteraction;
        $this->sendEmail = (bool) $sendEmail;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->sendEmail && SystemSettings::enabled('notif_email', false)) {
            $channels[] = 'mail';
        }

        if ($this->sendPush && app(WebPushNotificationService::class)->canNotify($notifiable)) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'type' => $this->type,
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
            ->salutation('Keep building your confidence,');

        $mail->action('Open '.$appName, $this->url ?: route('user.notifications'));

        return $mail;
    }

    public function toWebPush(object $notifiable): array
    {
        $url = $this->url ?: url('/notifications');

        return [
            'title' => $this->title,
            'body' => $this->message,
            'iconUrl' => WebPushNotificationService::DEFAULT_NOTIFICATION_ICON,
            'badgeUrl' => WebPushNotificationService::DEFAULT_NOTIFICATION_BADGE,
            'tag' => $this->tag ?: 'speakready-important-'.$this->type,
            'urgency' => 'high',
            'requireInteraction' => $this->requireInteraction,
            'url' => $url,
            'data' => [
                'url' => $url,
                'type' => $this->type,
            ],
        ];
    }
}
