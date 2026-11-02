<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#f1f6fb">
      <title>{{ $title }} - SpeakReady AI</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
      <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/desktop/style.css?v=8') }}">
      <link rel="stylesheet" href="{{ asset('css/shared/auth-page.css?v=24') }}" data-page-style="auth-page-shared">
      <link rel="stylesheet" href="{{ asset('css/shared/legal-page.css?v=1') }}" data-page-style="legal-page">
      <script src="{{ asset('js/auth-page.js?v=5') }}" defer></script>
   </head>
   <body class="auth-page-body auth-desktop-shell legal-page-body" data-layout-shell="desktop" data-auth-layout="desktop">
      <main id="authPage" class="auth-page-shell legal-page-shell" data-auth-mode="legal">
         <header class="auth-page-topbar">
            <a class="auth-page-brand" href="{{ url('/') }}" aria-label="SpeakReady AI home">
               <span class="auth-page-brand-mark"><img src="{{ asset('img/logo.png') }}" alt=""></span>
               <span>SpeakReady AI</span>
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
               <p class="legal-page-kicker">{{ $kicker }}</p>
               <h1 class="legal-page-title" id="legalPageTitle">{{ $title }}</h1>
               <p class="legal-page-intro">{{ $intro }}</p>
            </header>

            @foreach($sections as $section)
               <section class="legal-page-section">
                  <h2>{{ $section['heading'] }}</h2>
                  <p>{{ $section['body'] }}</p>
               </section>
            @endforeach

            <p class="legal-page-updated">Last updated: {{ now()->format('F j, Y') }}</p>
         </article>

         <footer class="auth-page-footer legal-page-footer">
            <span>&copy; {{ date('Y') }} SpeakReady AI</span><span aria-hidden="true">&bull;</span>
            <a href="{{ route('legal.privacy') }}">Privacy</a><a href="{{ route('legal.terms') }}">Terms</a>
         </footer>
      </main>
   </body>
</html>
