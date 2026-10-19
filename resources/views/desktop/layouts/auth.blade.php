<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#f7fbff">
      <title>@yield('title', 'SpeakReady AI')</title>
      @include('desktop.partials.early-mobile-shell-repair')
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/desktop/style.css?v=7') }}">
      <link rel="stylesheet" href="{{ asset('css/desktop/auth/auth.css?v=5') }}" data-page-style="auth-desktop">
   </head>
   <body class="auth-desktop-shell desktop-shell @yield('body_class')" data-layout-shell="desktop" data-auth-layout="desktop">
      @include('desktop.partials.viewport-mobile-cookie')
      <main class="auth-shell">
         <section class="auth-card">
            <a class="auth-brand" href="{{ url('/') }}">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
               <span class="auth-brand-name">SpeakReady AI</span>
               <small class="auth-brand-tagline">AI Interview Practice</small>
            </a>

            @yield('content')
         </section>
      </main>
      <script>
         document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
               var input = document.getElementById(toggle.getAttribute('data-password-toggle'));
               var icon = toggle.querySelector('i');
               if (!input || !icon) return;
               var isHidden = input.type === 'password';
               input.type = isHidden ? 'text' : 'password';
               icon.classList.toggle('fa-eye-slash', !isHidden);
               icon.classList.toggle('fa-eye', isHidden);
               toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
         });
      </script>
   </body>
</html>
