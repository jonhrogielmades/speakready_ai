<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecoveryEmailChangeAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $stage,
        public readonly string $maskedNewAddress
    ) {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = (string) config('app.name', 'SpeakReady AI');
        $completed = $this->stage === 'completed';

        $mail = (new MailMessage)
            ->subject(($completed ? 'Recovery email changed' : 'Recovery email change requested').' | '.$appName)
            ->greeting('Security notice')
            ->line($completed
                ? "The recovery email for your {$appName} account was changed to {$this->maskedNewAddress}."
                : "A request was made to change the recovery email for your {$appName} account to {$this->maskedNewAddress}.")
            ->line($completed
                ? 'The new address was verified before the change became active.'
                : 'Your current verified recovery email remains active until the new address is verified.')
            ->line('If you did not make this change, sign in immediately, change your password, and contact support.')
            ->action('Review account security', route('user.account'))
            ->salutation('SpeakReady AI Security');

        return $mail;
    }
}
