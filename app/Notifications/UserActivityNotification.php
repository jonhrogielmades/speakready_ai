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

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $icon = 'fa-bell', $type = 'info')
    {
        $this->title = $title;
        $this->message = $message;
        $this->icon = $icon;
        $this->type = $type;
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

        if (app(WebPushNotificationService::class)->canNotify($notifiable)) {
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
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message);
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->message,
            'tag' => 'speakready-user-activity',
            'url' => url('/notifications'),
            'data' => [
                'url' => url('/notifications'),
                'type' => $this->type,
            ],
        ];
    }
}
