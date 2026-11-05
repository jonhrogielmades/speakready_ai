<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name', 'SpeakReady AI') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f6fb;color:#172033;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;font-size:1px;line-height:1px;color:#f3f6fb;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        A quick guide to your SpeakReady AI account, privacy, terms, and security practices.
    </div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-collapse:collapse;table-layout:fixed;background-color:#f3f6fb;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:600px;table-layout:fixed;border-collapse:separate;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:16px;">
                    <tr>
                        <td style="padding:24px 32px;background-color:#142b4f;border-radius:16px 16px 0 0;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td valign="middle" style="width:38px;height:38px;background-color:#ffffff;border-radius:10px;">
                                        <img src="{{ $logoUrl }}" alt="{{ config('app.name', 'SpeakReady AI') }} logo" width="38" height="38" style="display:block;width:38px;height:38px;border:0;border-radius:10px;object-fit:contain;background-color:#ffffff;">
                                    </td>
                                    <td valign="middle" style="padding-left:12px;color:#ffffff;font-size:18px;line-height:24px;font-weight:700;letter-spacing:-.3px;">{{ config('app.name', 'SpeakReady AI') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 12px;word-break:break-word;">
                            <p style="margin:0 0 12px;color:#1d4ed8;font-size:12px;line-height:18px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;">Your account essentials</p>
                            <h1 style="margin:0 0 18px;color:#142b4f;font-size:28px;line-height:36px;font-weight:700;">Welcome, {{ $recipientName }}.</h1>
                            <p style="margin:0 0 16px;color:#334155;font-size:16px;line-height:25px;">You recently signed in to {{ config('app.name', 'SpeakReady AI') }} with Google. We want you to know how your account and practice information are used.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 8px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;table-layout:fixed;border-collapse:collapse;background-color:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <h2 style="margin:0 0 14px;color:#142b4f;font-size:17px;line-height:24px;">How we handle your information</h2>
                                        <p style="margin:0 0 12px;color:#334155;font-size:14px;line-height:22px;"><strong style="color:#142b4f;">For your practice:</strong> Account details, interview setup, answers, and feedback help us run mock interviews and show your progress.</p>
                                        <p style="margin:0;color:#334155;font-size:14px;line-height:22px;"><strong style="color:#142b4f;">For account access:</strong> The app checks account access before showing saved interview reviews. Our Security page explains the safeguards in more detail.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px 26px;word-break:break-word;">
                            <p style="margin:0 0 18px;color:#334155;font-size:14px;line-height:22px;">Our Privacy Policy explains what we collect, why we use it, and how long we keep it. You can also review the Terms of Service and our Security practices at any time.</p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="background-color:#2563eb;border-radius:8px;">
                                        <a href="{{ $privacyUrl }}" style="display:inline-block;padding:12px 20px;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;text-decoration:none;">Read Privacy Policy</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:17px 0 0;color:#1d4ed8;font-size:14px;line-height:22px;"><a href="{{ $termsUrl }}" style="color:#1d4ed8;text-decoration:underline;font-weight:700;">Terms of Service</a><span style="color:#94a3b8;">&nbsp;&nbsp;·&nbsp;&nbsp;</span><a href="{{ $securityUrl }}" style="color:#1d4ed8;text-decoration:underline;font-weight:700;">Security practices</a></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 32px 26px;word-break:break-word;background-color:#f8fafc;border-top:1px solid #e2e8f0;border-radius:0 0 16px 16px;">
                            <p style="margin:0 0 8px;color:#475569;font-size:12px;line-height:19px;">You received this email because your Google account was used to sign in to {{ config('app.name', 'SpeakReady AI') }} for the first time.</p>
                            <p style="margin:0;color:#64748b;font-size:12px;line-height:19px;">If this was not you, review your Google account access and contact <a href="mailto:capstonespeakreadyai@gmail.com" style="color:#1d4ed8;text-decoration:underline;">capstonespeakreadyai@gmail.com</a>.</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0;color:#94a3b8;font-size:12px;line-height:18px;">&copy; {{ date('Y') }} {{ config('app.name', 'SpeakReady AI') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
