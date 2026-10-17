@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-align: center; text-decoration: none;">
<img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name', 'SpeakReady AI') }} logo" width="58" height="58" style="height: 58px; width: 58px; max-width: 100%; display: block; margin: 0 auto 13px auto; border-radius: 16px;">
<span class="email-brand-name" style="display: block;">{{ config('app.name', 'SpeakReady AI') }}</span>
<span class="email-brand-tagline" style="display: block;">Practice smarter. Interview better.</span>
</a>
</td>
</tr>

