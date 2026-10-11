<?php

namespace App\Notifications\Channels;

use App\Services\WebPushNotificationService;
use Illuminate\Notifications\Notification;

class WebPushChannel
{
    public function __construct(private WebPushNotificationService $webPush)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $this->webPush->sendToUser($notifiable, $notification->toWebPush($notifiable));
    }
}
