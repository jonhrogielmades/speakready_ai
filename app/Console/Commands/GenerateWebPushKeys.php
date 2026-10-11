<?php

namespace App\Console\Commands;

use App\Services\WebPushNotificationService;
use Illuminate\Console\Command;

class GenerateWebPushKeys extends Command
{
    protected $signature = 'webpush:generate-keys';

    protected $description = 'Generate VAPID keys for PWA device push notifications.';

    public function handle(WebPushNotificationService $webPush): int
    {
        $keys = $webPush->generateKeys();

        $this->line('Add these values to your environment:');
        $this->newLine();
        $this->line('WEBPUSH_VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('WEBPUSH_VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('WEBPUSH_VAPID_SUBJECT='.config('app.url'));

        return self::SUCCESS;
    }
}
