@props(['url'])
@php($emailLogo = $systemLogo ?? 'img/logo.png')
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-align: center; text-decoration: none;">
<img class="email-brand-mark" src="{{ asset($emailLogo) }}" alt="{{ config('app.name', 'SpeakReady AI') }} logo" width="72" height="72" style="height: 72px; width: 72px; max-width: 100%; display: block; margin: 0 auto 14px auto; border-radius: 50%; background-color: #ffffff; border: 1px solid #d7e6f5; padding: 7px;">
<span class="email-brand-name" style="display: block;">{{ config('app.name', 'SpeakReady AI') }}</span>
<span class="email-brand-tagline" style="display: block;">Practice smarter. Interview better.</span>
</a>
</td>
</tr>

