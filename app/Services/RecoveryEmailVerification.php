<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class RecoveryEmailVerification
{
    public function __construct(private readonly BrevoTransactionalMail $brevoMailer)
    {
    }

    public function send(User $user): bool
    {
        if (blank($user->email) || $user->hasVerifiedEmail()) {
            return false;
        }

        $expiresInMinutes = max(5, (int) config('auth.verification.expire', 60));

        if ($this->brevoMailer->isConfigured()) {
            $this->brevoMailer->sendRecoveryEmailVerification(
                $user,
                $this->verificationUrl($user, $expiresInMinutes),
                $expiresInMinutes
            );
        } else {
            $user->sendEmailVerificationNotification();
        }

        return true;
    }

    private function verificationUrl(User $user, int $expiresInMinutes): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes($expiresInMinutes),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }
}
