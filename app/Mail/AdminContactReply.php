<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminContactReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contact $contact,
        public string $replySubject,
        public string $replyBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->replySubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-contact-reply',
            text: 'emails.admin-contact-reply-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
