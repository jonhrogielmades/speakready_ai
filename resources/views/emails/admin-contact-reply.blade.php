<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $replySubject }}</title>
</head>
<body style="margin:0;padding:32px 16px;background:#f1f5f9;color:#1e293b;font-family:Arial,Helvetica,sans-serif;line-height:1.6;">
    <div style="max-width:640px;margin:0 auto;overflow:hidden;border:1px solid #e2e8f0;border-radius:14px;background:#ffffff;box-shadow:0 8px 24px rgba(15,23,42,0.08);">
        <div style="padding:24px 32px;background:linear-gradient(135deg,#3b82f6,#14b8a6);color:#ffffff;">
            <h1 style="margin:0;font-size:22px;line-height:1.3;">{{ config('app.name', 'SpeakReady AI') }}</h1>
            <p style="margin:6px 0 0;font-size:14px;opacity:0.92;">Support Team</p>
        </div>

        <div style="padding:32px;">
            <p style="margin:0 0 20px;">Hello {{ $contact->name }},</p>

            <div style="margin:0 0 28px;white-space:pre-wrap;overflow-wrap:anywhere;">{{ $replyBody }}</div>

            <p style="margin:0;">Regards,<br>{{ config('app.name', 'SpeakReady AI') }} Support Team</p>

            <div style="margin-top:30px;padding:18px;border-left:4px solid #cbd5e1;border-radius:8px;background:#f8fafc;color:#64748b;">
                <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;">Your original message</p>
                <p style="margin:0 0 8px;font-weight:700;color:#475569;overflow-wrap:anywhere;">{{ $contact->subject }}</p>
                <div style="font-size:14px;white-space:pre-wrap;overflow-wrap:anywhere;">{{ $contact->message }}</div>
            </div>
        </div>

        <div style="padding:18px 32px;border-top:1px solid #e2e8f0;background:#f8fafc;text-align:center;color:#94a3b8;font-size:12px;">
            This email was sent in response to your contact request to {{ config('app.name', 'SpeakReady AI') }}.
        </div>
    </div>
</body>
</html>
