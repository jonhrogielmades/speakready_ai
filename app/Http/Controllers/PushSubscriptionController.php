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
        return response()->json([
            'supported' => $webPush->hasPublicKey(),
            'configured' => $webPush->isConfigured(),
            'publicKey' => $webPush->publicKey(),
        ]);
    }

    public function store(Request $request, WebPushNotificationService $webPush)
    {
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

    public function destroy(Request $request)
    {
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
}
