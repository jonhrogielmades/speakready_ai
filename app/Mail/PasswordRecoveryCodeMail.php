<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordRecoveryCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $recoveryCode,
        public int $expiresInMinutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name', 'SpeakReady AI').' recovery code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'desktop.emails.password-recovery-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
