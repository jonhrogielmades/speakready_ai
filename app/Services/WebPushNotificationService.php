<?php

namespace App\Services;

use App\Models\PushSubscription as StoredPushSubscription;
use App\Models\User;
use Base64Url\Base64Url;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use phpseclib3\Crypt\EC;

class WebPushNotificationService
{
    public const DEFAULT_NOTIFICATION_ICON = '/img/notification-icon-192.png?v=3';

    public const DEFAULT_NOTIFICATION_BADGE = '/img/notification-badge.png?v=4';

    private const DEFAULT_NOTIFICATION_URGENCY = 'high';

    private const DEFAULT_NOTIFICATION_ACTIONS = [
        ['action' => 'open', 'title' => 'Open'],
        ['action' => 'view-notifications', 'title' => 'View all'],
    ];

    /**
     * @var array{publicKey: string, privateKey: string}|null
     */
    private ?array $activeKeyPair = null;

    private bool $activeKeyPairResolved = false;

    private bool $activeKeyPairUsesStorage = false;

    public function publicKey(): ?string
    {
        return $this->activeKeyPair()['publicKey'] ?? null;
    }

    public function hasPublicKey(): bool
    {
        return $this->publicKey() !== null;
    }

    public function isConfigured(): bool
    {
        return $this->hasPublicKey()
            && $this->privateKey() !== null
            && $this->subject() !== null;
    }

    public function usesGeneratedKeys(): bool
    {
        $this->activeKeyPair();

        return $this->activeKeyPairUsesStorage;
    }

    public function hasPartialEnvironmentKeys(): bool
    {
        return ($this->configuredPublicKey() === null) !== ($this->configuredPrivateKey() === null);
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
                    'subject' => (string) $this->subject(),
                    'publicKey' => (string) $this->publicKey(),
                    'privateKey' => (string) $this->privateKey(),
                ],
            ], [
                'TTL' => 86400,
                'urgency' => $payload['urgency'] ?? self::DEFAULT_NOTIFICATION_URGENCY,
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

    private function privateKey(): ?string
    {
        return $this->activeKeyPair()['privateKey'] ?? null;
    }

    private function subject(): ?string
    {
        $subject = trim((string) config('services.webpush.subject'));

        if ($subject !== '') {
            return $subject;
        }

        $appUrl = trim((string) config('app.url'));

        return $appUrl !== '' ? $appUrl : url('/');
    }

    /**
     * @return array{publicKey: string, privateKey: string}|null
     */
    private function activeKeyPair(): ?array
    {
        if ($this->activeKeyPairResolved) {
            return $this->activeKeyPair;
        }

        $this->activeKeyPairResolved = true;

        $publicKey = $this->configuredPublicKey();
        $privateKey = $this->configuredPrivateKey();

        if ($publicKey !== null && $privateKey !== null) {
            return $this->activeKeyPair = [
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ];
        }

        if ($publicKey !== null || $privateKey !== null || ! $this->shouldAutoGenerateKeys()) {
            return null;
        }

        $this->activeKeyPairUsesStorage = true;

        return $this->activeKeyPair = $this->storedOrGeneratedKeyPair();
    }

    private function configuredPublicKey(): ?string
    {
        return $this->filledConfigString('services.webpush.public_key');
    }

    private function configuredPrivateKey(): ?string
    {
        return $this->filledConfigString('services.webpush.private_key');
    }

    private function filledConfigString(string $key): ?string
    {
        $value = trim((string) config($key));

        if ($value === '' || str_starts_with($value, 'CHANGE_ME')) {
            return null;
        }

        return $value;
    }

    private function shouldAutoGenerateKeys(): bool
    {
        return filter_var(config('services.webpush.auto_generate', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array{publicKey: string, privateKey: string}|null
     */
    private function storedOrGeneratedKeyPair(): ?array
    {
        $path = $this->keyStoragePath();
        $stored = $this->readStoredKeyPair($path);

        if ($stored !== null) {
            return $stored;
        }

        try {
            $keys = $this->generateKeys();
            $this->writeStoredKeyPair($path, $keys);

            return $keys;
        } catch (\Throwable $error) {
            Log::warning('Unable to auto-generate web push VAPID keys.', [
                'path' => $path,
                'error_type' => $error::class,
                'message' => $error->getMessage(),
            ]);

            return null;
        }
    }

    private function keyStoragePath(): string
    {
        $configuredPath = trim((string) config('services.webpush.storage_path'));

        return $configuredPath !== ''
            ? $configuredPath
            : storage_path('app/webpush-vapid.json');
    }

    /**
     * @return array{publicKey: string, privateKey: string}|null
     */
    private function readStoredKeyPair(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $error) {
            Log::warning('Unable to read stored web push VAPID keys.', [
                'path' => $path,
                'error_type' => $error::class,
                'message' => $error->getMessage(),
            ]);

            return null;
        }

        return $this->validKeyPair($data) ? [
            'publicKey' => (string) $data['publicKey'],
            'privateKey' => (string) $data['privateKey'],
        ] : null;
    }

    /**
     * @param  array{publicKey: string, privateKey: string}  $keys
     */
    private function writeStoredKeyPair(string $path, array $keys): void
    {
        File::ensureDirectoryExists(dirname($path), 0755, true);

        file_put_contents(
            $path,
            json_encode($keys, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );

        @chmod($path, 0600);
    }

    private function validKeyPair(mixed $data): bool
    {
        return is_array($data)
            && trim((string) ($data['publicKey'] ?? '')) !== ''
            && trim((string) ($data['privateKey'] ?? '')) !== '';
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
            'icon' => $payload['iconUrl'] ?? self::DEFAULT_NOTIFICATION_ICON,
            'badge' => $payload['badgeUrl'] ?? self::DEFAULT_NOTIFICATION_BADGE,
            'image' => $payload['imageUrl'] ?? null,
            'tag' => $payload['tag'] ?? 'speakready-notification',
            'renotify' => $payload['renotify'] ?? true,
            'requireInteraction' => $payload['requireInteraction'] ?? false,
            'silent' => $payload['silent'] ?? false,
            'timestamp' => $payload['timestamp'] ?? (int) round(microtime(true) * 1000),
            'vibrate' => $payload['vibrate'] ?? [120, 50, 120],
            'actions' => $payload['actions'] ?? self::DEFAULT_NOTIFICATION_ACTIONS,
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
