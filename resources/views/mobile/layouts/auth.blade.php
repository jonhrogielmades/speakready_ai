<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta name="theme-color" content="#f7fbff">
      <title>@yield('title', 'SpeakReady AI')</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/mobile/style.css?v=7') }}">
      <link rel="stylesheet" href="{{ asset('css/mobile/auth/auth.css?v=1') }}" data-page-style="auth-mobile">
   </head>
   <body>
      <main class="auth-shell">
         <section class="auth-card">
            <a class="auth-brand" href="{{ url('/') }}">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
               <span>SpeakReady AI</span>
            </a>

            @yield('content')
         </section>
      </main>
   </body>
</html>
