<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password recovery code</title>
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;">
        <tr>
            <td style="padding:36px 32px;">
                <p style="margin:0 0 10px;color:#2563eb;font-size:14px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">{{ config('app.name', 'SpeakReady AI') }}</p>
                <h1 style="margin:0 0 20px;font-size:26px;line-height:1.25;color:#0f172a;">Password recovery code</h1>

                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Hello {{ $user->name ?: 'there' }},</p>
                <p style="margin:0 0 24px;font-size:16px;line-height:1.6;">Someone requested a password recovery code for your local {{ config('app.name', 'SpeakReady AI') }} account.</p>

                <div style="margin:0 0 24px;padding:20px 12px;border:1px solid #bfdbfe;border-radius:12px;background:#eff6ff;color:#1d4ed8;font-size:26px;font-weight:700;letter-spacing:4px;line-height:1.2;text-align:center;">
                    {{ $recoveryCode }}
                </div>

                <p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Use this code with your username on the password reset page. It expires in {{ $expiresInMinutes }} minutes and works only once.</p>
                <p style="margin:0;font-size:14px;line-height:1.6;color:#475569;">If you did not request this code, you can safely ignore this email. Your password will not change unless the code is used.</p>
            </td>
        </tr>
    </table>
</body>
</html>
