<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#08080f">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <title>Terms and Conditions - SpeakReady AI</title>
      @include('shared.early-layout-shell-guard', ['serverLayout' => 'mobile'])
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json?v=7') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
      <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/mobile/style.css?v=34') }}">
      <style>
         body.terms-accept-shell {
            --terms-primary: #60a5fa;
            --terms-primary-strong: #3b82f6;
            --terms-secondary: #22d3ee;
            --terms-primary-soft: rgba(96, 165, 250, 0.14);
            --terms-primary-border: rgba(96, 165, 250, 0.3);
            --terms-secondary-soft: rgba(34, 211, 238, 0.12);
            --terms-focus-ring: rgba(96, 165, 250, 0.32);
            --terms-gradient: linear-gradient(135deg, var(--terms-primary), var(--terms-secondary));
            min-height: 100vh;
            background:
               linear-gradient(180deg, color-mix(in srgb, var(--bg, #08080f) 90%, #111827), var(--bg, #08080f) 58%, color-mix(in srgb, var(--bg2, #0f172a) 70%, var(--bg, #08080f)));
            color: var(--tx, #f8fafc);
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0;
         }

         .lm body.terms-accept-shell {
            --terms-primary: #2563eb;
            --terms-primary-strong: #1d4ed8;
            --terms-secondary: #06b6d4;
            --terms-primary-soft: rgba(37, 99, 235, 0.12);
            --terms-primary-border: rgba(37, 99, 235, 0.24);
            --terms-secondary-soft: rgba(6, 182, 212, 0.11);
            --terms-focus-ring: rgba(37, 99, 235, 0.26);
         }

         .terms-mobile-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
         }

         .terms-mobile-topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            border-bottom: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            background: color-mix(in srgb, var(--sf, #111827) 94%, transparent);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            padding: max(12px, env(safe-area-inset-top, 0px)) 14px 12px;
         }

         .terms-mobile-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            color: var(--tx, #f8fafc);
            text-decoration: none;
            font-weight: 900;
         }

         .terms-mobile-brand img {
            width: 36px;
            height: 36px;
            object-fit: contain;
            background: #ffffff;
            border-radius: 8px;
            flex: 0 0 auto;
         }

         .terms-mobile-brand span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
         }

         .terms-mobile-logout {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            background: var(--bg, #08080f);
            color: var(--tx, #f8fafc);
            border-radius: 8px;
         }

         .terms-mobile-main {
            flex: 1;
            padding: 18px max(14px, env(safe-area-inset-right, 0px)) max(24px, env(safe-area-inset-bottom, 0px)) max(14px, env(safe-area-inset-left, 0px));
         }

         .terms-mobile-intro {
            border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            background: var(--sf, #111827);
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 14px;
            box-shadow: 0 16px 34px rgba(0, 0, 0, 0.22);
         }

         .terms-mobile-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--terms-primary);
            font-size: 0.78rem;
            font-weight: 900;
            margin-bottom: 10px;
         }

         .terms-mobile-title {
            color: var(--tx, #f8fafc);
            font-size: 1.55rem;
            line-height: 1.18;
            font-weight: 900;
            letter-spacing: 0;
            margin: 0 0 10px;
         }

         .terms-mobile-copy {
            color: var(--tx2, #cbd5e1);
            font-size: 0.92rem;
            line-height: 1.65;
            margin: 0;
         }

         .terms-mobile-progress {
            border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            background: var(--bg, #08080f);
            border-radius: 8px;
            padding: 12px;
            margin-top: 14px;
         }

         .terms-mobile-progress-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: var(--tx, #f8fafc);
            font-size: 0.8rem;
            font-weight: 900;
            margin-bottom: 10px;
         }

         .terms-mobile-progress-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--terms-primary);
            border: 1px solid var(--terms-primary-border);
            background: var(--terms-primary-soft);
            border-radius: 8px;
            padding: 6px 9px;
            font-size: 0.7rem;
            font-weight: 900;
            white-space: nowrap;
         }

         .terms-mobile-progress-track {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 7px;
         }

         .terms-mobile-progress-step {
            min-width: 0;
            border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            background: var(--sf, #111827);
            border-radius: 8px;
            padding: 10px 8px;
         }

         .terms-mobile-progress-step span {
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: var(--terms-secondary-soft);
            color: var(--terms-secondary);
            margin-bottom: 8px;
         }

         .terms-mobile-progress-step.is-current span {
            background: var(--terms-primary-soft);
            color: var(--terms-primary);
         }

         .terms-mobile-progress-step strong {
            display: block;
            color: var(--tx, #f8fafc);
            font-size: 0.72rem;
            line-height: 1.25;
         }

         .terms-mobile-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
         }

         .terms-mobile-meta-item {
            border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            background: var(--bg, #08080f);
            border-radius: 8px;
            padding: 12px;
         }

         .terms-mobile-meta-item i {
            color: var(--terms-primary);
            margin-bottom: 8px;
         }

         .terms-mobile-meta-item strong {
            display: block;
            color: var(--tx, #f8fafc);
            font-size: 0.8rem;
            margin-bottom: 3px;
         }

         .terms-mobile-meta-item span {
            display: block;
            color: var(--tx2, #cbd5e1);
            font-size: 0.74rem;
            line-height: 1.45;
         }

         .terms-mobile-panel {
            border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            background: var(--sf, #111827);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 16px 34px rgba(0, 0, 0, 0.22);
         }

         .terms-mobile-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
         }

         .terms-mobile-panel-title {
            color: var(--tx, #f8fafc);
            font-size: 1rem;
            font-weight: 900;
            margin: 0 0 5px;
         }

         .terms-mobile-panel-meta {
            color: var(--tx2, #cbd5e1);
            font-size: 0.78rem;
            line-height: 1.45;
            margin: 0;
         }

         .terms-mobile-version {
            border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            background: var(--bg, #08080f);
            color: var(--tx, #f8fafc);
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 0.72rem;
            font-weight: 900;
            white-space: nowrap;
         }

         .terms-mobile-sections {
            max-height: 42vh;
            overflow: auto;
            scrollbar-width: thin;
         }

         .terms-mobile-section {
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
         }

         .terms-mobile-section:last-child {
            border-bottom: 0;
         }

         .terms-mobile-section-icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: var(--terms-primary-soft);
            color: var(--terms-primary);
         }

         .terms-mobile-section h2 {
            color: var(--tx, #f8fafc);
            font-size: 0.92rem;
            font-weight: 900;
            margin: 0 0 6px;
         }

         .terms-mobile-section p {
            color: var(--tx2, #cbd5e1);
            font-size: 0.82rem;
            line-height: 1.58;
            margin: 0;
         }

         .terms-mobile-form {
            border-top: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            padding: 16px;
            background: var(--sf, #111827);
         }

         .terms-mobile-check {
            display: grid;
            grid-template-columns: 24px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
            background: var(--bg, #08080f);
            border-radius: 8px;
            padding: 14px;
         }

         .terms-mobile-check input {
            width: 22px;
            height: 22px;
            margin-top: 2px;
            accent-color: var(--terms-primary);
         }

         .terms-mobile-check label {
            color: var(--tx, #f8fafc);
            font-size: 0.84rem;
            line-height: 1.6;
            margin: 0;
         }

         .terms-mobile-check a {
            color: var(--terms-primary);
            font-weight: 900;
            text-decoration: none;
         }

         .terms-mobile-error {
            color: #fca5a5;
            font-size: 0.8rem;
            font-weight: 800;
            margin-top: 9px;
         }

         .terms-mobile-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 8px;
            background: var(--terms-gradient);
            color: #ffffff;
            font-weight: 900;
            margin-top: 14px;
         }

         .terms-mobile-submit:disabled {
            opacity: 0.52;
            cursor: not-allowed;
         }

         .terms-success-modal {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: max(18px, env(safe-area-inset-top, 0px)) 14px max(18px, env(safe-area-inset-bottom, 0px));
         }

         .terms-success-modal.is-open {
            display: flex;
         }

         .terms-success-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(2, 6, 23, 0.68);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
         }

         .terms-success-dialog {
            position: relative;
            width: min(100%, 360px);
            border: 1px solid rgba(34, 197, 94, 0.28);
            background: var(--sf, #111827);
            color: var(--tx, #f8fafc);
            border-radius: 8px;
            box-shadow: 0 28px 72px rgba(0, 0, 0, 0.34);
            padding: 22px;
            text-align: center;
         }

         .terms-success-icon {
            width: 54px;
            height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(34, 197, 94, 0.12);
            color: #4ade80;
            font-size: 1.55rem;
            margin-bottom: 14px;
         }

         .terms-success-kicker {
            color: #4ade80;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 0 8px;
         }

         .terms-success-title {
            color: var(--tx, #f8fafc);
            font-size: 1.15rem;
            font-weight: 900;
            margin: 0 0 9px;
         }

         .terms-success-copy,
         .terms-success-note {
            color: var(--tx2, #cbd5e1);
            font-size: 0.86rem;
            line-height: 1.58;
            margin: 0;
         }

         .terms-success-note {
            font-size: 0.78rem;
            margin-top: 9px;
         }

         .terms-success-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 8px;
            background: var(--terms-gradient);
            color: #ffffff;
            font-weight: 900;
            margin-top: 18px;
         }

         .terms-mobile-logout:focus-visible,
         .terms-mobile-check input:focus-visible,
         .terms-mobile-check a:focus-visible,
         .terms-mobile-submit:focus-visible,
         .terms-success-button:focus-visible {
            outline: 3px solid var(--terms-focus-ring);
            outline-offset: 3px;
         }

         @media (max-width: 359.98px) {
            .terms-mobile-meta {
               grid-template-columns: 1fr;
            }

            .terms-mobile-progress-track {
               grid-template-columns: 1fr;
            }

            .terms-mobile-title {
               font-size: 1.35rem;
            }
         }

         @supports not (background: color-mix(in srgb, #fff 50%, transparent)) {
            .terms-mobile-topbar {
               background: var(--sf, #111827);
            }
         }
      </style>
   </head>
   <body class="terms-accept-shell mobile-shell" data-layout-shell="mobile">
      @include('mobile.partials.viewport-mobile-cookie')
      <div class="terms-mobile-shell">
         <header class="terms-mobile-topbar">
            <div class="d-flex align-items-center justify-content-between gap-3">
               <a class="terms-mobile-brand" href="{{ url('/') }}">
                  <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" width="64" height="64" decoding="async">
                  <span>SpeakReady AI</span>
               </a>
               <form action="{{ route('logout') }}" method="POST" class="m-0">
                  @csrf
                  <button class="terms-mobile-logout" type="submit" aria-label="Sign out">
                     <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                  </button>
               </form>
            </div>
         </header>

         <main class="terms-mobile-main">
            <section class="terms-mobile-intro" aria-labelledby="termsMobileTitle">
               <p class="terms-mobile-kicker"><i class="fa-solid fa-file-signature" aria-hidden="true"></i> Required before dashboard</p>
               <h1 class="terms-mobile-title" id="termsMobileTitle">Review the Terms and Conditions</h1>
               <p class="terms-mobile-copy">Accept the current terms to open your SpeakReady AI dashboard on this device.</p>
               <div class="terms-mobile-progress" aria-label="Account setup progress">
                  <div class="terms-mobile-progress-head">
                     <span>Account Setup</span>
                     <span class="terms-mobile-progress-pill"><i class="fa-solid fa-shield-check" aria-hidden="true"></i> Step 2</span>
                  </div>
                  <div class="terms-mobile-progress-track">
                     <div class="terms-mobile-progress-step">
                        <span><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                        <strong>Registered</strong>
                     </div>
                     <div class="terms-mobile-progress-step is-current">
                        <span><i class="fa-solid fa-file-signature" aria-hidden="true"></i></span>
                        <strong>Agreement</strong>
                     </div>
                     <div class="terms-mobile-progress-step">
                        <span><i class="fa-solid fa-gauge-high" aria-hidden="true"></i></span>
                        <strong>Dashboard</strong>
                     </div>
                  </div>
               </div>
               <div class="terms-mobile-meta" aria-label="Agreement summary">
                  <div class="terms-mobile-meta-item">
                     <i class="fa-solid fa-lock" aria-hidden="true"></i>
                     <strong>Access gate</strong>
                     <span>Dashboard stays locked until acceptance.</span>
                  </div>
                  <div class="terms-mobile-meta-item">
                     <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                     <strong>Version {{ $termsVersion }}</strong>
                     <span>Updated {{ $lastUpdated }}.</span>
                  </div>
               </div>
            </section>

            <section class="terms-mobile-panel" aria-labelledby="termsMobilePanelTitle">
               <div class="terms-mobile-panel-head">
                  <div>
                     <h2 class="terms-mobile-panel-title" id="termsMobilePanelTitle">SpeakReady AI Terms</h2>
                     <p class="terms-mobile-panel-meta">Read the key terms, then confirm your agreement.</p>
                  </div>
                  <span class="terms-mobile-version">{{ $termsVersion }}</span>
               </div>

               <div class="terms-mobile-sections" tabindex="0">
                  @foreach($sections as $section)
                     <article class="terms-mobile-section">
                        <span class="terms-mobile-section-icon"><i class="{{ $section['icon'] }}" aria-hidden="true"></i></span>
                        <div>
                           <h2>{{ $section['heading'] }}</h2>
                           <p>{{ $section['body'] }}</p>
                        </div>
                     </article>
                  @endforeach
               </div>

               <form class="terms-mobile-form" method="POST" action="{{ route('terms.acceptance.store') }}">
                  @csrf
                  @if(! empty($recoveryCodeAcknowledgementRequired) && session('registration_success') && filled(session('recovery_code')))
                     <input type="hidden" name="recovery_code_saved" id="recoveryCodeSaved" value="{{ old('recovery_code_saved', '0') }}">
                  @endif
                  <div class="terms-mobile-check">
                     <input type="checkbox" name="terms_accepted" value="1" id="termsAccepted" required @checked(old('terms_accepted'))>
                     <label for="termsAccepted">
                        I have read and agree to the
                        <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer">Terms of Service</a>
                        and
                        <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.
                     </label>
                  </div>
                  @if(! empty($recoveryCodeAcknowledgementRequired) && (! session('registration_success') || blank(session('recovery_code'))))
                     <div class="terms-mobile-check">
                        <input type="checkbox" name="recovery_code_saved" value="1" id="recoveryCodeSavedInline" data-recovery-code-saved-inline required @checked(old('recovery_code_saved'))>
                        <label for="recoveryCodeSavedInline">
                           I confirm that I saved the one-time recovery code shown during registration. The original code cannot be displayed again; I can replace it from Account after continuing if needed.
                        </label>
                     </div>
                  @endif
                  @error('terms_accepted')
                     <div class="terms-mobile-error" role="alert">{{ $message }}</div>
                  @enderror
                  @error('recovery_code_saved')
                     <div class="terms-mobile-error" role="alert">{{ $message }}</div>
                  @enderror
                  <button class="terms-mobile-submit" id="termsSubmit" type="submit" disabled>
                     Continue <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                  </button>
               </form>
            </section>
         </main>
      </div>

      <script>
         (function () {
            const checkbox = document.getElementById('termsAccepted');
            const button = document.getElementById('termsSubmit');
             const recoveryAcknowledgement = document.querySelector('[data-recovery-code-saved-inline]');

            if (!checkbox || !button) {
               return;
            }

            function syncButton() {
                button.disabled = !checkbox.checked || Boolean(recoveryAcknowledgement && !recoveryAcknowledgement.checked);
            }

            checkbox.addEventListener('change', syncButton);
             recoveryAcknowledgement?.addEventListener('change', syncButton);
            syncButton();
         })();
      </script>
      @include('shared.terms.registration-success-modal')
   </body>
</html>
