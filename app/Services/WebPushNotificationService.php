<?php

namespace App\Services;

use App\Models\PushSubscription as StoredPushSubscription;
use App\Models\User;
use Base64Url\Base64Url;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use phpseclib3\Crypt\EC;

class WebPushNotificationService
{
    public function publicKey(): ?string
    {
        $key = trim((string) config('services.webpush.public_key'));

        return $key !== '' ? $key : null;
    }

    public function hasPublicKey(): bool
    {
        return $this->publicKey() !== null;
    }

    public function isConfigured(): bool
    {
        return $this->hasPublicKey()
            && trim((string) config('services.webpush.private_key')) !== ''
            && trim((string) config('services.webpush.subject')) !== '';
    }

    public function canNotify(object $notifiable): bool
    {
        if (! $notifiable instanceof User || ! $this->isConfigured()) {
            return false;
        }

        if (! Schema::hasTable('push_subscriptions')) {
            return false;
        }

        return $notifiable->pushSubscriptions()
            ->whereNull('failed_at')
            ->exists();
    }

    public function sendToUser(object $notifiable, array $payload): int
    {
        if (! $notifiable instanceof User || ! $this->isConfigured()) {
            return 0;
        }

        if (! Schema::hasTable('push_subscriptions')) {
            return 0;
        }

        $subscriptions = $notifiable->pushSubscriptions()
            ->whereNull('failed_at')
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => (string) config('services.webpush.subject'),
                    'publicKey' => (string) config('services.webpush.public_key'),
                    'privateKey' => (string) config('services.webpush.private_key'),
                ],
            ], [
                'TTL' => 86400,
                'urgency' => $payload['urgency'] ?? 'normal',
                'batchSize' => 100,
            ]);

            $encodedPayload = json_encode($this->normalizePayload($payload), JSON_THROW_ON_ERROR);
        } catch (\Throwable $error) {
            Log::warning('Unable to prepare web push notification.', [
                'user_id' => $notifiable->id,
                'error_type' => $error::class,
                'message' => $error->getMessage(),
            ]);

            return 0;
        }

        $subscriptionsByEndpoint = $subscriptions->keyBy('endpoint');

        foreach ($subscriptions as $subscription) {
            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                    ]),
                    $encodedPayload
                );
            } catch (\Throwable $error) {
                $this->markSubscriptionFailed($subscription, $error);
            }
        }

        $sent = 0;

        try {
            foreach ($webPush->flush() as $report) {
                $subscription = $subscriptionsByEndpoint->get($report->getEndpoint());

                if (! $subscription) {
                    continue;
                }

                if ($report->isSuccess()) {
                    $sent++;
                    $subscription->forceFill([
                        'last_used_at' => now(),
                        'failed_at' => null,
                    ])->save();

                    continue;
                }

                $this->handleFailedReport($subscription, $report);
            }
        } catch (\Throwable $error) {
            Log::warning('Unable to send web push notification.', [
                'user_id' => $notifiable->id,
                'error_type' => $error::class,
                'message' => $error->getMessage(),
            ]);
        }

        return $sent;
    }

    /**
     * @return array{publicKey: string, privateKey: string}
     */
    public function generateKeys(): array
    {
        $this->configureOpenSslForKeyGeneration();

        try {
            return VAPID::createVapidKeys();
        } catch (\Throwable $error) {
            Log::info('OpenSSL VAPID key generation failed; falling back to phpseclib.', [
                'error_type' => $error::class,
                'message' => $error->getMessage(),
            ]);

            return $this->generateKeysWithPhpseclib();
        }
    }

    /**
     * @return array{publicKey: string, privateKey: string}
     */
    private function generateKeysWithPhpseclib(): array
    {
        $jwk = json_decode(EC::createKey('nistp256')->toString('JWK'), true, 512, JSON_THROW_ON_ERROR);
        $key = $jwk['keys'][0] ?? $jwk;
        $x = str_pad(Base64Url::decode((string) $key['x']), 32, "\0", STR_PAD_LEFT);
        $y = str_pad(Base64Url::decode((string) $key['y']), 32, "\0", STR_PAD_LEFT);
        $privateKey = str_pad(Base64Url::decode((string) $key['d']), 32, "\0", STR_PAD_LEFT);

        return [
            'publicKey' => Base64Url::encode("\x04".$x.$y),
            'privateKey' => Base64Url::encode($privateKey),
        ];
    }

    private function configureOpenSslForKeyGeneration(): void
    {
        $configuredPath = getenv('OPENSSL_CONF');
        if (is_string($configuredPath) && $configuredPath !== '' && is_file($configuredPath)) {
            return;
        }

        foreach ($this->openSslConfigCandidates() as $candidate) {
            if (! is_file($candidate)) {
                continue;
            }

            putenv('OPENSSL_CONF='.$candidate);
            $_ENV['OPENSSL_CONF'] = $candidate;
            $_SERVER['OPENSSL_CONF'] = $candidate;

            return;
        }
    }

    /**
     * @return array<int, string>
     */
    private function openSslConfigCandidates(): array
    {
        $phpDirectory = dirname(PHP_BINARY);
        $baseDirectory = base_path();
        $laragonDirectory = dirname($baseDirectory, 2);
        $candidates = [
            $phpDirectory.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            $phpDirectory.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cfg',
            $laragonDirectory.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'apache',
            $laragonDirectory.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'git'.DIRECTORY_SEPARATOR.'mingw64'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
            $laragonDirectory.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'git'.DIRECTORY_SEPARATOR.'usr'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf',
        ];

        foreach (glob($laragonDirectory.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'php'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'openssl.cnf') ?: [] as $path) {
            $candidates[] = $path;
        }

        foreach (glob($laragonDirectory.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'apache'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'openssl.cnf') ?: [] as $path) {
            $candidates[] = $path;
        }

        return array_values(array_unique(array_filter(
            $candidates,
            fn (string $path): bool => ! is_dir($path)
        )));
    }

    private function normalizePayload(array $payload): array
    {
        $title = trim((string) ($payload['title'] ?? config('app.name', 'SpeakReady AI')));
        $body = trim((string) ($payload['body'] ?? $payload['message'] ?? 'You have a new notification.'));
        $url = (string) ($payload['url'] ?? data_get($payload, 'data.url', url('/notifications')));

        return [
            'title' => $title !== '' ? $title : 'SpeakReady AI',
            'body' => $body !== '' ? $body : 'You have a new notification.',
            'icon' => $payload['iconUrl'] ?? '/img/app-icon-192.png',
            'badge' => $payload['badgeUrl'] ?? '/img/app-icon-192.png',
            'tag' => $payload['tag'] ?? 'speakready-notification',
            'renotify' => $payload['renotify'] ?? true,
            'url' => $url,
            'data' => array_merge(
                ['url' => $url],
                is_array($payload['data'] ?? null) ? $payload['data'] : []
            ),
        ];
    }

    private function handleFailedReport(StoredPushSubscription $subscription, MessageSentReport $report): void
    {
        if ($report->isSubscriptionExpired()) {
            $subscription->delete();

            return;
        }

        $subscription->forceFill(['failed_at' => now()])->save();

        Log::info('Web push notification was rejected by the push service.', [
            'subscription_id' => $subscription->id,
            'reason' => $report->getReason(),
        ]);
    }

    private function markSubscriptionFailed(StoredPushSubscription $subscription, \Throwable $error): void
    {
        $subscription->forceFill(['failed_at' => now()])->save();

        Log::warning('Unable to queue web push notification.', [
            'subscription_id' => $subscription->id,
            'error_type' => $error::class,
            'message' => $error->getMessage(),
        ]);
    }
}
