<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Contact Message</title>
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f4f7f6; margin: 0; padding: 40px 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); overflow: hidden;">
        
        <!-- Header -->
        <div style="background-color: #8b5cf6; padding: 30px 40px; text-align: center;">
            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600; letter-spacing: 0.5px;">SpeakReady AI</h1>
        </div>
        
        <!-- Content -->
        <div style="padding: 40px;">
            <p style="font-size: 16px; color: #475569; margin-top: 0; margin-bottom: 25px;">You have received a new message from the website contact form.</p>
            
            <!-- Info Block -->
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 25px; margin-bottom: 35px;">
                <div style="margin-bottom: 15px;">
                    <span style="display: block; font-weight: 600; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Sender Name</span>
                    <span style="font-size: 16px; color: #0f172a;">{{ $contactData['name'] }}</span>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <span style="display: block; font-weight: 600; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Email Address</span>
                    <span style="font-size: 16px; color: #0f172a;">
                        <a href="mailto:{{ $contactData['email'] }}" style="color: #8b5cf6; text-decoration: none;">{{ $contactData['email'] }}</a>
                    </span>
                </div>
                
                <div>
                    <span style="display: block; font-weight: 600; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Subject</span>
                    <span style="font-size: 16px; color: #0f172a; font-weight: 500;">{{ $contactData['subject'] }}</span>
                </div>
            </div>
            
            <!-- Message Body -->
            <div>
                <h2 style="font-size: 18px; color: #1e293b; margin-top: 0; margin-bottom: 15px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">Message Details</h2>
                <div style="font-size: 16px; color: #334155; line-height: 1.8; white-space: pre-wrap;">{{ $contactData['message'] }}</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #f8fafc; padding: 20px 40px; text-align: center; border-top: 1px solid #e2e8f0;">
            <p style="margin: 0; font-size: 13px; color: #94a3b8;">This is an automated message sent from the SpeakReady AI contact form.</p>
            <p style="margin: 5px 0 0 0; font-size: 13px; color: #94a3b8;">&copy; {{ date('Y') }} SpeakReady AI. All rights reserved.</p>
        </div>
        
    </div>
</body>
</html>

