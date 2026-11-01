<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#f1f6fb">
      <meta name="description" content="Practice role-focused job interviews by voice or text and receive clear, evidence-based feedback with SpeakReady AI.">
      <meta name="robots" content="index,follow,max-image-preview:large">
      <meta property="og:type" content="website">
      <meta property="og:title" content="{{ $systemName ?? 'SpeakReady AI' }} — Practice your next interview">
      <meta property="og:description" content="Role-focused interview practice with flexible response modes, coaching, and actionable feedback.">
      <meta property="og:url" content="{{ url()->current() }}">
      <meta property="og:image" content="{{ asset('img/desktop-preview/01_Overview_Desktop_Preview_4K.webp') }}">
      <meta name="twitter:card" content="summary_large_image">
      <link rel="canonical" href="{{ url('/') }}">
      <title>@yield('title', ($systemName ?? 'SpeakReady AI').' — Practice your next interview')</title>
      @include('shared.early-layout-shell-guard', ['serverLayout' => 'desktop'])
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset($systemFavicon ?? 'favicon.ico') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json?v=7') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preload" href="{{ asset('img/desktop-preview/01_Overview_Desktop_Preview_4K.webp') }}?v=20261020" as="image" type="image/webp" media="(min-width: 768px)" fetchpriority="high">
      <link rel="preload" href="{{ asset('img/mobile-preview/01-home-dashboard-hd.webp') }}?v=20261020" as="image" type="image/webp" media="(max-width: 767px)" fetchpriority="high">
      <link rel="stylesheet" href="{{ asset('css/shared/landing-page.css?v=1') }}">
      <style>
         :root,
         .lm,
         body.guest-desktop-shell {
            --pur: {{ $systemPrimaryColor ?? '#2563eb' }};
            --blu: {{ $systemSecondaryColor ?? '#06b6d4' }};
            --grad: linear-gradient(135deg, {{ $systemPrimaryColor ?? '#2563eb' }}, {{ $systemSecondaryColor ?? '#06b6d4' }});
         }
      </style>
   </head>
   <body class="guest-shell guest-desktop-shell sr-guest" data-layout-shell="desktop" data-guest-layout="desktop">
      @include('desktop.partials.viewport-mobile-cookie')
      @include('shared.landing.page', ['landingVariant' => 'desktop'])
      <script src="{{ asset('js/landing-page.js?v=1') }}" defer></script>
   </body>
</html>
