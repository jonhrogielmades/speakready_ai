<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushNotificationService;
use App\Support\AccountNotificationSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function publicKey(WebPushNotificationService $webPush)
    {
        $this->ensureUserSidePushRecipient();
        AccountNotificationSchema::ensure();
        $user = Auth::user();

        return response()->json([
            'supported' => $webPush->hasPublicKey(),
            'configured' => $webPush->isConfigured(),
            'publicKey' => $webPush->publicKey(),
            'schemaReady' => AccountNotificationSchema::hasRequiredPushSubscriptionsTable(),
            'subscriptionCount' => $user
                ? $user->pushSubscriptions()->whereNull('failed_at')->count()
                : 0,
            'generatedKeys' => $webPush->usesGeneratedKeys(),
            'partialEnvironmentKeys' => $webPush->hasPartialEnvironmentKeys(),
        ]);
    }

    public function store(Request $request, WebPushNotificationService $webPush)
    {
        $this->ensureUserSidePushRecipient();
        AccountNotificationSchema::ensure();

        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:4096'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:1024'],
            'keys.auth' => ['required', 'string', 'max:512'],
            'contentEncoding' => ['nullable', 'string', 'in:aes128gcm,aesgcm'],
        ]);

        $endpoint = $validated['endpoint'];

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            [
                'user_id' => Auth::id(),
                'endpoint' => $endpoint,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
                'last_used_at' => now(),
                'failed_at' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'configured' => $webPush->isConfigured(),
        ]);
    }

    public function test(WebPushNotificationService $webPush)
    {
        $this->ensureUserSidePushRecipient();
        AccountNotificationSchema::ensure();

        $user = Auth::user();

        if (! $user) {
            abort(401);
        }

        if (! $webPush->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Device push notifications are not configured on this server.',
            ], 422);
        }

        if (! $webPush->canNotify($user)) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not subscribed to push notifications yet.',
            ], 422);
        }

        $notificationUrl = url('/notifications');

        $sent = $webPush->sendToUser($user, [
            'title' => 'SpeakReady AI',
            'body' => 'Device notifications are ready.',
            'iconUrl' => WebPushNotificationService::DEFAULT_NOTIFICATION_ICON,
            'badgeUrl' => WebPushNotificationService::DEFAULT_NOTIFICATION_BADGE,
            'tag' => 'speakready-push-test',
            'urgency' => 'high',
            'requireInteraction' => true,
            'vibrate' => [120, 50, 120],
            'url' => $notificationUrl,
            'data' => [
                'url' => $notificationUrl,
                'type' => 'success',
            ],
        ]);

        return response()->json([
            'success' => $sent > 0,
            'sent' => $sent,
            'message' => $sent > 0
                ? 'Test push notification sent.'
                : 'No push service accepted the test notification.',
        ], $sent > 0 ? 200 : 422);
    }

    public function destroy(Request $request)
    {
        $this->ensureUserSidePushRecipient();
        AccountNotificationSchema::ensure();

        $validated = $request->validate([
            'endpoint' => ['nullable', 'string', 'max:4096'],
        ]);

        if (! empty($validated['endpoint'])) {
            PushSubscription::where('user_id', Auth::id())
                ->where('endpoint_hash', hash('sha256', $validated['endpoint']))
                ->delete();
        }

        return response()->json(['success' => true]);
    }

    private function ensureUserSidePushRecipient(): void
    {
        $user = Auth::user();

        if ($user && (bool) ($user->is_admin ?? false)) {
            abort(404);
        }
    }
}
