<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class BrevoTransactionalMail
{
    public function isConfigured(): bool
    {
        return filled(config('services.brevo.api_key'));
    }

    public function sendPasswordReset(User $user, string $token): void
    {
        $appName = config('app.name', 'SpeakReady AI');
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]);
        $expireMinutes = config('auth.passwords.users.expire', 60);

        $payload = [
            'sender' => [
                'name' => config('mail.from.name', $appName),
                'email' => config('mail.from.address'),
            ],
            'to' => [
                array_filter([
                    'email' => $user->getEmailForPasswordReset(),
                    'name' => $user->name,
                ]),
            ],
            'subject' => "Reset your {$appName} password",
            'htmlContent' => $this->passwordResetHtml($user, $resetUrl, $expireMinutes, $appName),
            'textContent' => $this->passwordResetText($resetUrl, $expireMinutes, $appName),
        ];

        Http::timeout((int) config('services.brevo.timeout', 15))
            ->acceptJson()
            ->withHeaders([
                'api-key' => config('services.brevo.api_key'),
            ])
            ->post(config('services.brevo.endpoint'), $payload)
            ->throw();
    }

    private function passwordResetHtml(User $user, string $resetUrl, int $expireMinutes, string $appName): string
    {
        $name = e($user->name ?: 'there');
        $safeAppName = e($appName);
        $safeUrl = e($resetUrl);

        return <<<HTML
            <div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a">
                <h2 style="margin:0 0 16px">Reset your {$safeAppName} password</h2>
                <p>Hello {$name},</p>
                <p>We received a request to reset your password. Click the button below to create a new one.</p>
                <p style="margin:24px 0">
                    <a href="{$safeUrl}" style="display:inline-block;background:#3b82f6;color:#ffffff;padding:12px 18px;border-radius:8px;text-decoration:none;font-weight:700">Reset Password</a>
                </p>
                <p>This link expires in {$expireMinutes} minutes.</p>
                <p>If you did not request this, you can safely ignore this email.</p>
                <p style="font-size:12px;color:#64748b">If the button does not work, copy and paste this link into your browser:<br>{$safeUrl}</p>
            </div>
        HTML;
    }

    private function passwordResetText(string $resetUrl, int $expireMinutes, string $appName): string
    {
        return "Reset your {$appName} password\n\n"
            ."Open this link to create a new password:\n{$resetUrl}\n\n"
            ."This link expires in {$expireMinutes} minutes.\n\n"
            .'If you did not request this, you can safely ignore this email.';
    }
}
