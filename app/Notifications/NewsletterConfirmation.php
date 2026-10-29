<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class NewsletterConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly string $encryptedToken)
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $token = Crypt::decryptString($this->encryptedToken);

        return (new MailMessage)
            ->subject('Confirm your SpeakReady AI updates subscription')
            ->greeting('Confirm your subscription')
            ->line('Someone entered this email address to receive SpeakReady AI updates.')
            ->line('Confirm only if you made this request. The link expires in 24 hours.')
            ->action('Confirm subscription', route('newsletter.confirm', ['token' => $token]))
            ->line('If you did not request this, you can safely ignore this email.');
    }
}
