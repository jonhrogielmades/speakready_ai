Hello {{ $contact->name }},

{{ $replyBody }}

Regards,
{{ config('app.name', 'SpeakReady AI') }} Support Team

--- Your original message ---
Subject: {{ $contact->subject }}

{{ $contact->message }}
