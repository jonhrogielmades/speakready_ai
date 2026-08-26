<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#f7fbff">
      <title>{{ $title }} - SpeakReady AI</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/desktop/style.css?v=7') }}">
      <style>
         .legal-shell { min-height: 100vh; background: var(--bg); color: var(--tx); }
         .legal-nav { border-bottom: 1px solid var(--bd); background: color-mix(in srgb, var(--sf) 94%, transparent); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); }
         .legal-brand { display: inline-flex; align-items: center; gap: 10px; color: var(--tx); font-weight: 800; text-decoration: none; }
         .legal-brand img { width: 38px; height: 38px; object-fit: contain; background: #fff; border-radius: 8px; }
         .legal-card { width: min(100%, 860px); margin: 0 auto; background: var(--sf); border: 1px solid var(--bd); border-radius: 8px; box-shadow: 0 18px 46px rgba(15, 23, 42, 0.12); }
         .legal-kicker { color: var(--pur); font-size: 0.78rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
         .legal-title { color: var(--tx); font-weight: 900; letter-spacing: 0; }
         .legal-intro, .legal-section p, .legal-updated { color: var(--tx2); }
         .legal-section { border-top: 1px solid var(--bd); }
         .legal-section h2 { color: var(--tx); font-size: 1rem; font-weight: 800; }
         @supports not (background: color-mix(in srgb, #fff 50%, transparent)) {
            .legal-nav { background: var(--sf); }
         }
      </style>
   </head>
   <body class="legal-shell desktop-shell" data-layout-shell="desktop">
      <nav class="legal-nav py-3">
         <div class="container d-flex align-items-center justify-content-between gap-3">
            <a class="legal-brand" href="{{ url('/') }}">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
               <span>SpeakReady AI</span>
            </a>
            <a class="boc btn px-3 py-2" href="{{ url('/') }}">Back Home</a>
         </div>
      </nav>

      <main class="container py-5">
         <article class="legal-card p-4 p-md-5">
            <p class="legal-kicker mb-2">{{ $kicker }}</p>
            <h1 class="legal-title h2 mb-3">{{ $title }}</h1>
            <p class="legal-intro mb-4">{{ $intro }}</p>

            @foreach($sections as $section)
               <section class="legal-section pt-4 mt-4">
                  <h2 class="mb-2">{{ $section['heading'] }}</h2>
                  <p class="mb-0">{{ $section['body'] }}</p>
               </section>
            @endforeach

            <p class="legal-updated mt-4 mb-0 small">Last updated: {{ now()->format('F j, Y') }}</p>
         </article>
      </main>
   </body>
</html>
