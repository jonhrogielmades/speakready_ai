@component('mail::message')
# Password recovery code

Hello {{ $user->name ?: 'there' }},

Someone requested a password recovery code for your SpeakReady AI local account.

@component('mail::panel')
<div style="font-size:28px;font-weight:700;letter-spacing:4px;text-align:center">{{ $recoveryCode }}</div>
@endcomponent

Use this code with your username on the password reset page. It expires in {{ $expiresInMinutes }} minutes and works only once.

If you did not request this code, you can safely ignore this email. Your password will not change unless the code is used.

Thanks,<br>
{{ config('app.name', 'SpeakReady AI') }}
@endcomponent
