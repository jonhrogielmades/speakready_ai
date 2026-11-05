<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#f1f6fb">
      <title>{{ $title }} - {{ $systemName ?? 'SpeakReady AI' }}</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset($systemFavicon ?? 'favicon.ico') }}">
      @include('shared.pwa-meta')
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
      <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/desktop/style.css?v=8') }}">
      <link rel="stylesheet" href="{{ asset('css/shared/auth-page.css?v=36') }}" data-page-style="auth-page-shared">
      <link rel="stylesheet" href="{{ asset('css/shared/legal-page.css?v=2') }}" data-page-style="legal-page">
      <script src="{{ asset('js/auth-page.js?v=5') }}" defer></script>
   </head>
   <body class="auth-page-body auth-desktop-shell legal-page-body" data-layout-shell="desktop" data-auth-layout="desktop">
      @include('shared.pwa-launch')
      <main id="authPage" class="auth-page-shell legal-page-shell" data-auth-mode="legal">
         <header class="auth-page-topbar">
            <a class="auth-page-brand" href="{{ url('/') }}" aria-label="{{ $systemName ?? 'SpeakReady AI' }} home">
               <span class="auth-page-brand-mark"><img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt=""></span>
               <span>{{ $systemName ?? 'SpeakReady AI' }}</span>
            </a>
            <div class="auth-page-topbar-actions">
               <button type="button" class="auth-page-theme-toggle" id="authPageThemeToggle" aria-label="Switch color theme" title="Switch color theme">
                  <i class="fa-regular fa-sun" data-auth-theme-icon="sun" aria-hidden="true"></i>
                  <i class="fa-regular fa-moon" data-auth-theme-icon="moon" aria-hidden="true"></i>
               </button>
               <a class="auth-page-back" href="{{ url('/') }}" aria-label="Exit to home" title="Exit to home">
                  <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                  <span>Back to home</span>
               </a>
            </div>
         </header>

         <article class="legal-page-card" aria-labelledby="legalPageTitle">
            <header class="legal-page-header">
               @if($kicker !== '')
                  <p class="legal-page-kicker">{{ $kicker }}</p>
               @endif
               <h1 class="legal-page-title" id="legalPageTitle">{{ $title }}</h1>
               <p class="legal-page-intro">{{ $intro }}</p>
            </header>

            @foreach($sections as $section)
               <section class="legal-page-section">
                  <h2>{{ $section['heading'] }}</h2>
                  <p>{{ $section['body'] }}</p>
                  @if(isset($section['contact_email']))
                     <a class="legal-page-contact" href="mailto:{{ $section['contact_email'] }}">{{ $section['contact_email'] }}</a>
                  @endif
               </section>
            @endforeach

            <p class="legal-page-updated">Last updated: {{ $lastUpdated }}</p>
         </article>

         <footer class="auth-page-footer legal-page-footer">
            <span class="auth-page-footer-brand"><span>&copy; {{ date('Y') }} {{ $systemName ?? 'SpeakReady AI' }}</span></span>
            <span class="auth-page-footer-divider" aria-hidden="true"></span>
            <a href="{{ route('legal.privacy') }}"><i class="fa-solid fa-shield-check" aria-hidden="true"></i><span>Privacy</span></a>
            <a href="{{ route('legal.terms') }}"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><span>Terms</span></a>
         </footer>
      </main>
      @include('shared.pwa-install-prompt')
   </body>
</html>
