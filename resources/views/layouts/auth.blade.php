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
      <link rel="stylesheet" href="{{ asset('css/' . (($isMobile ?? false) ? 'mobile' : 'desktop') . '/style.css?v=7') }}">
      <style>
         body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
               radial-gradient(circle at 18% 8%, rgba(20, 184, 166, 0.16), transparent 28%),
               radial-gradient(circle at 86% 16%, rgba(59, 130, 246, 0.18), transparent 30%),
               var(--bg);
         }

         .auth-shell {
            width: min(100%, 430px);
         }

         .auth-card {
            padding: 28px;
            color: var(--tx);
            background: var(--sf);
            border: 1px solid var(--bd);
            border-radius: 12px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
         }

         .auth-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--tx);
            font-weight: 800;
         }

         .auth-brand img {
            width: 38px;
            height: 38px;
            object-fit: contain;
            background: #fff;
            border-radius: 8px;
         }

         .auth-title {
            margin: 26px 0 8px;
            color: var(--tx);
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: 0;
         }

         .auth-copy {
            margin-bottom: 22px;
            color: var(--tx2);
            font-size: 0.92rem;
            line-height: 1.6;
         }

         .auth-label {
            margin-bottom: 7px;
            color: var(--tx);
            font-size: 0.78rem;
            font-weight: 700;
         }

         .auth-input {
            min-height: 44px;
            color: var(--tx);
            background: var(--bg2);
            border: 1px solid var(--bd2);
            border-radius: 9px;
         }

         .auth-input:focus {
            color: var(--tx);
            background: var(--bg2);
            border-color: var(--pur);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.14);
         }

         .auth-muted-link {
            color: var(--pur);
            font-size: 0.84rem;
            font-weight: 700;
         }

         .auth-alert {
            padding: 11px 13px;
            border-radius: 9px;
            font-size: 0.84rem;
            line-height: 1.45;
         }

         .auth-alert-success {
            color: #047857;
            background: rgba(52, 211, 153, 0.13);
            border: 1px solid rgba(52, 211, 153, 0.24);
         }

         .auth-alert-error {
            color: var(--danger-tx);
            background: var(--danger-bg);
            border: 1px solid rgba(248, 113, 113, 0.22);
         }

         @media (max-width: 480px) {
            body {
               padding: 16px;
            }

            .auth-card {
               padding: 22px;
            }
         }
      </style>
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
