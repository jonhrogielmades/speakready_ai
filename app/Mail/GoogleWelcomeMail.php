<?php

namespace App\Mail;

use App\Models\User;
use App\Support\SystemSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GoogleWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('app.name', 'SpeakReady AI').' — your privacy essentials',
        );
    }

    /** @return array{recipientName: string, logoUrl: string, privacyUrl: string, termsUrl: string, securityUrl: string} */
    public function templateData(): array
    {
        $logoPath = trim((string) SystemSettings::value('system_logo', 'img/logo.png')) ?: 'img/logo.png';

        return [
            'recipientName' => trim((string) $this->user->name) ?: 'there',
            'logoUrl' => asset($logoPath),
            'privacyUrl' => route('legal.privacy'),
            'termsUrl' => route('legal.terms'),
            'securityUrl' => route('legal.security'),
        ];
    }

    public function content(): Content
    {
        return new Content(
            view: 'desktop.emails.google-welcome',
            text: 'desktop.emails.google-welcome-text',
            with: $this->templateData(),
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
