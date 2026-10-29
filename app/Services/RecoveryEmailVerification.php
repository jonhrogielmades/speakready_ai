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
        $targetEmail = $this->targetEmail($user);
        $hasPendingChange = filled($user->pending_recovery_email);

        if (! $user->isLocalAccount()
            || blank($targetEmail)
            || (! $hasPendingChange && $user->hasVerifiedEmail())) {
            return false;
        }

        $expiresInMinutes = max(5, (int) config('auth.verification.expire', 60));
        $recipient = clone $user;
        $recipient->forceFill([
            'email' => $targetEmail,
            'email_verified_at' => null,
        ]);

        if ($this->brevoMailer->isConfigured()) {
            $this->brevoMailer->sendRecoveryEmailVerification(
                $recipient,
                $this->verificationUrl($user, $targetEmail, $expiresInMinutes),
                $expiresInMinutes
            );
        } else {
            $recipient->sendEmailVerificationNotification();
        }

        return true;
    }

    public function targetEmail(User $user): ?string
    {
        $email = filled($user->pending_recovery_email)
            ? $user->pending_recovery_email
            : $user->email;

        $email = strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    private function verificationUrl(User $user, string $targetEmail, int $expiresInMinutes): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes($expiresInMinutes),
            [
                'id' => $user->getKey(),
                'hash' => sha1($targetEmail),
            ]
        );
    }
}
