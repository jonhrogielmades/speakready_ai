<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#0f172a">
      <title>Admin Desktop Only | SpeakReady AI</title>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <style>
         :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --ink: #111827;
            --muted: #64748b;
            --line: #e2e8f0;
            --accent: #dc2626;
            --accent-soft: #fee2e2;
         }

         * { box-sizing: border-box; }

         html,
         body {
            min-height: 100%;
            margin: 0;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--ink);
         }

         body {
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 24px;
         }

         .admin-mobile-lock {
            width: min(100%, 420px);
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 28px;
            box-shadow: 0 18px 55px rgba(15, 23, 42, 0.10);
         }

         .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
         }

         .brand img {
            width: 42px;
            height: 42px;
            border-radius: 8px;
         }

         .brand strong {
            display: block;
            font-size: 1rem;
            line-height: 1.2;
         }

         .brand span {
            display: block;
            color: var(--muted);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
         }

         .lock-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 8px;
            background: var(--accent-soft);
            color: var(--accent);
            margin: 0 auto 18px;
            font-size: 1.25rem;
         }

         h1 {
            font-size: 1.45rem;
            line-height: 1.25;
            margin: 0 0 10px;
            letter-spacing: 0;
         }

         p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 0.95rem;
         }

         .actions { margin-top: 24px; }

         .btn-lock {
            min-height: 44px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            font-weight: 700;
            text-decoration: none;
         }

         .btn-secondary-lock {
            background: transparent;
            color: var(--ink);
            border: 1px solid var(--line);
         }
      </style>
   </head>
   <body>
      <main class="admin-mobile-lock" role="main" aria-labelledby="adminMobileLockTitle">
         <div class="brand">
            <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
            <div>
               <strong>SpeakReady AI</strong>
               <span>Admin Portal</span>
            </div>
         </div>

         <div class="lock-icon" aria-hidden="true">
            <i class="fa-solid fa-desktop"></i>
         </div>

         <h1 id="adminMobileLockTitle">Admin is temporarily desktop only.</h1>
         <p>
            Mobile admin access is locked while the mobile interface is being fixed.
            Please open the admin portal on a desktop or laptop browser.
         </p>

         <div class="actions">
            <form action="{{ route('logout') }}" method="POST">
               @csrf
               <button class="btn-lock btn-secondary-lock w-100" type="submit">
                  <i class="fa-solid fa-right-from-bracket"></i>
                  Log out
               </button>
            </form>
         </div>
      </main>
   </body>
</html>
