<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? str_replace('_', '-', app()->getLocale()) }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#f1f6fb">
      <title>@yield('title', $systemName ?? 'SpeakReady AI')</title>
      @include('shared.early-layout-shell-guard', ['serverLayout' => 'desktop'])
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset($systemFavicon ?? 'img/logo.png') }}" type="image/png">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
      <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/desktop/style.css?v=8') }}">
      <link rel="stylesheet" href="{{ asset('css/desktop/auth/auth.css?v=2') }}" data-page-style="auth-desktop">
      <link rel="stylesheet" href="{{ asset('css/shared/auth-page.css?v=15') }}" data-page-style="auth-page-shared">
      <script src="{{ asset('js/auth-page.js?v=2') }}" defer></script>
      <style>
         :root, .lm, body.auth-desktop-shell {
            --pur: {{ $systemPrimaryColor ?? '#2563eb' }};
            --blu: {{ $systemSecondaryColor ?? '#06b6d4' }};
            --grad: linear-gradient(135deg, {{ $systemPrimaryColor ?? '#2563eb' }}, {{ $systemSecondaryColor ?? '#06b6d4' }});
         }
      </style>
   </head>
   <body class="auth-desktop-shell desktop-shell @yield('body_class')" data-layout-shell="desktop" data-auth-layout="desktop">
      @include('desktop.partials.viewport-mobile-cookie')
      @hasSection('auth_page')
         @yield('auth_page')
      @else
      <main class="auth-shell">
         <section class="auth-card">
            <a class="auth-brand" href="{{ url('/') }}">
               <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="{{ $systemName ?? 'SpeakReady AI' }}">
               <span>{{ $systemName ?? 'SpeakReady AI' }}</span>
            </a>

            @yield('content')
         </section>
      </main>
      @endif
   </body>
</html>
