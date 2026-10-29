<?php

namespace App\Services;

use App\Notifications\RecoveryEmailChangeAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class RecoveryEmailChangeNotifier
{
    public function requested(string $oldEmail, string $newEmail): void
    {
        $this->send($oldEmail, $newEmail, 'requested');
    }

    public function completed(string $oldEmail, string $newEmail): void
    {
        $this->send($oldEmail, $newEmail, 'completed');
    }

    private function send(string $oldEmail, string $newEmail, string $stage): void
    {
        $oldEmail = strtolower(trim($oldEmail));
        $newEmail = strtolower(trim($newEmail));

        if ($oldEmail === '' || $newEmail === '' || hash_equals($oldEmail, $newEmail)) {
            return;
        }

        try {
            Notification::route('mail', $oldEmail)->notify(
                new RecoveryEmailChangeAlert($stage, $this->mask($newEmail))
            );
        } catch (Throwable $error) {
            Log::warning('Recovery email security alert could not be delivered.', [
                'stage' => $stage,
                'old_email_hash' => hash('sha256', $oldEmail),
                'new_email_hash' => hash('sha256', $newEmail),
                'exception_type' => $error::class,
            ]);
        }
    }

    private function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return 'the replacement address';
        }

        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));

        return $visible.str_repeat('*', max(3, mb_strlen($local) - mb_strlen($visible))).'@'.$domain;
    }
}
