<?php

namespace App\Notifications;

use App\Support\SystemSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $type = 'info')
    {
        $this->title = $title;
        $this->message = $message;
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
            'icon' => $icon
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message);
    }
}
