<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#f7fbff">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <title>Terms and Conditions - SpeakReady AI</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <link rel="stylesheet" href="{{ asset('css/desktop/style.css?v=35') }}">
      <style>
         body.terms-accept-shell {
            min-height: 100vh;
            background:
               linear-gradient(180deg, color-mix(in srgb, var(--bg, #f7fbff) 88%, #ffffff), var(--bg, #f7fbff) 48%, color-mix(in srgb, var(--bg2, #f8fafc) 84%, var(--sf, #ffffff)));
            color: var(--tx, #111827);
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0;
         }

         .terms-topbar {
            border-bottom: 1px solid var(--bd, #e5e7eb);
            background: color-mix(in srgb, var(--sf, #ffffff) 94%, transparent);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
         }

         .terms-brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: var(--tx, #111827);
            font-weight: 800;
            text-decoration: none;
            min-width: 0;
         }

         .terms-brand img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            flex: 0 0 auto;
         }

         .terms-brand span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
         }

         .terms-logout-button {
            border: 1px solid var(--bd, #e5e7eb);
            background: var(--sf, #ffffff);
            color: var(--tx, #111827);
            border-radius: 8px;
            font-weight: 700;
            min-height: 42px;
         }

         .terms-main {
            min-height: calc(100vh - 73px);
            display: flex;
            align-items: center;
            padding: 32px 0;
         }

         .terms-layout {
            display: grid;
            grid-template-columns: minmax(360px, 0.86fr) minmax(460px, 1fr);
            gap: 24px;
            align-items: start;
         }

         .terms-context {
            border: 1px solid var(--bd, #e5e7eb);
            background: var(--sf, #ffffff);
            border-radius: 8px;
            padding: 26px;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 20px;
         }

         .terms-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--pur, #7c3aed);
            font-size: 0.84rem;
            font-weight: 800;
            margin-bottom: 14px;
         }

         .terms-title {
            font-size: 2.25rem;
            line-height: 1.12;
            color: var(--tx, #111827);
            font-weight: 900;
            letter-spacing: 0;
            margin: 0 0 14px;
         }

         .terms-copy {
            color: var(--tx2, #475569);
            line-height: 1.75;
            margin: 0;
            max-width: 58ch;
         }

         .terms-progress-card {
            border: 1px solid var(--bd, #e5e7eb);
            background: var(--bg, #f7fbff);
            border-radius: 8px;
            padding: 16px;
            margin-top: 18px;
         }

         .terms-progress-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: var(--tx, #111827);
            font-size: 0.86rem;
            font-weight: 900;
            margin-bottom: 12px;
         }

         .terms-progress-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid rgba(124, 58, 237, 0.22);
            background: rgba(124, 58, 237, 0.1);
            color: var(--pur, #7c3aed);
            border-radius: 8px;
            padding: 7px 10px;
            font-size: 0.75rem;
            font-weight: 900;
            white-space: nowrap;
         }

         .terms-progress-steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
         }

         .terms-progress-step {
            min-width: 0;
            border: 1px solid var(--bd, #e5e7eb);
            background: var(--sf, #ffffff);
            border-radius: 8px;
            padding: 11px;
         }

         .terms-progress-step span {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(14, 165, 233, 0.1);
            color: #0284c7;
            margin-bottom: 9px;
         }

         .terms-progress-step.is-current span {
            background: rgba(124, 58, 237, 0.12);
            color: var(--pur, #7c3aed);
         }

         .terms-progress-step strong {
            display: block;
            color: var(--tx, #111827);
            font-size: 0.8rem;
            margin-bottom: 3px;
            overflow-wrap: anywhere;
         }

         .terms-progress-step small {
            display: block;
            color: var(--tx2, #475569);
            font-size: 0.72rem;
            line-height: 1.4;
         }

         .terms-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
         }

         .terms-summary-item {
            border: 1px solid var(--bd, #e5e7eb);
            background: var(--bg, #f7fbff);
            border-radius: 8px;
            padding: 16px;
         }

         .terms-summary-item i {
            color: var(--pur, #7c3aed);
            font-size: 1rem;
            margin-bottom: 10px;
         }

         .terms-summary-item strong {
            display: block;
            color: var(--tx, #111827);
            font-size: 0.9rem;
            margin-bottom: 4px;
         }

         .terms-summary-item span {
            display: block;
            color: var(--tx2, #475569);
            font-size: 0.82rem;
            line-height: 1.5;
         }

         .terms-panel {
            align-self: start;
            border: 1px solid var(--bd, #e5e7eb);
            background: var(--sf, #ffffff);
            border-radius: 8px;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.1);
            overflow: hidden;
         }

         .terms-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            padding: 26px 28px 20px;
            border-bottom: 1px solid var(--bd, #e5e7eb);
         }

         .terms-panel-title {
            color: var(--tx, #111827);
            font-size: 1.25rem;
            font-weight: 900;
            margin: 0 0 6px;
         }

         .terms-panel-meta {
            color: var(--tx2, #475569);
            font-size: 0.86rem;
            margin: 0;
         }

         .terms-version {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--tx, #111827);
            border: 1px solid var(--bd, #e5e7eb);
            background: var(--bg, #f7fbff);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.8rem;
            font-weight: 800;
            white-space: nowrap;
         }

         .terms-scroll {
            max-height: 394px;
            overflow: auto;
            padding: 8px 28px;
            scrollbar-width: thin;
         }

         .terms-section {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            gap: 14px;
            padding: 20px 0;
            border-bottom: 1px solid var(--bd, #e5e7eb);
         }

         .terms-section:last-child {
            border-bottom: 0;
         }

         .terms-section-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(124, 58, 237, 0.1);
            color: var(--pur, #7c3aed);
         }

         .terms-section h2 {
            color: var(--tx, #111827);
            font-size: 1rem;
            font-weight: 900;
            margin: 0 0 6px;
         }

         .terms-section p {
            color: var(--tx2, #475569);
            font-size: 0.92rem;
            line-height: 1.65;
            margin: 0;
         }

         .terms-form {
            padding: 22px 28px 28px;
            border-top: 1px solid var(--bd, #e5e7eb);
            background: color-mix(in srgb, var(--bg, #f7fbff) 74%, var(--sf, #ffffff));
         }

         .terms-check {
            display: grid;
            grid-template-columns: 22px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            border: 1px solid var(--bd, #e5e7eb);
            background: var(--sf, #ffffff);
            border-radius: 8px;
            padding: 16px;
         }

         .terms-check input {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            accent-color: var(--pur, #7c3aed);
         }

         .terms-check label {
            color: var(--tx, #111827);
            font-size: 0.92rem;
            line-height: 1.6;
            margin: 0;
         }

         .terms-check a {
            color: var(--pur, #7c3aed);
            font-weight: 800;
            text-decoration: none;
         }

         .terms-error {
            color: #dc2626;
            font-size: 0.86rem;
            font-weight: 700;
            margin-top: 10px;
         }

         .terms-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 8px;
            background: var(--pur, #7c3aed);
            color: #ffffff;
            font-weight: 900;
            margin-top: 18px;
            transition: transform 0.16s ease, opacity 0.16s ease;
         }

         .terms-submit:not(:disabled):hover {
            transform: translateY(-1px);
         }

         .terms-submit:disabled {
            opacity: 0.54;
            cursor: not-allowed;
         }

         .terms-success-modal {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
         }

         .terms-success-modal.is-open {
            display: flex;
         }

         .terms-success-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(2, 6, 23, 0.58);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
         }

         .terms-success-dialog {
            position: relative;
            width: min(100%, 440px);
            border: 1px solid rgba(34, 197, 94, 0.28);
            background: var(--sf, #ffffff);
            color: var(--tx, #111827);
            border-radius: 8px;
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
            padding: 30px;
            text-align: center;
         }

         .terms-success-icon {
            width: 58px;
            height: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(34, 197, 94, 0.12);
            color: #16a34a;
            font-size: 1.75rem;
            margin-bottom: 16px;
         }

         .terms-success-kicker {
            color: #16a34a;
            font-size: 0.76rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 0 8px;
         }

         .terms-success-title {
            color: var(--tx, #111827);
            font-size: 1.35rem;
            font-weight: 900;
            margin: 0 0 10px;
         }

         .terms-success-copy,
         .terms-success-note {
            color: var(--tx2, #475569);
            line-height: 1.6;
            margin: 0;
         }

         .terms-success-note {
            font-size: 0.88rem;
            margin-top: 10px;
         }

         .terms-success-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            min-height: 48px;
            border: 0;
            border-radius: 8px;
            background: var(--pur, #7c3aed);
            color: #ffffff;
            font-weight: 900;
            margin-top: 20px;
         }

         @media (max-width: 991.98px) {
            .terms-main {
               align-items: flex-start;
               padding: 24px 0;
            }

            .terms-layout {
               grid-template-columns: 1fr;
            }

            .terms-context {
               padding: 24px;
            }

            .terms-progress-steps {
               grid-template-columns: 1fr;
            }
         }

         @supports not (background: color-mix(in srgb, #fff 50%, transparent)) {
            .terms-topbar,
            .terms-form {
               background: var(--sf, #ffffff);
            }
         }
      </style>
   </head>
   <body class="terms-accept-shell desktop-shell" data-layout-shell="desktop">
      <header class="terms-topbar py-3">
         <div class="container d-flex align-items-center justify-content-between gap-3">
            <a class="terms-brand" href="{{ url('/') }}">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
               <span>SpeakReady AI</span>
            </a>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
               @csrf
               <button class="terms-logout-button px-3" type="submit">
                  <i class="fa-solid fa-right-from-bracket me-2" aria-hidden="true"></i>Sign out
               </button>
            </form>
         </div>
      </header>

      <main class="terms-main">
         <div class="container">
            <div class="terms-layout">
               <section class="terms-context" aria-labelledby="termsWelcomeTitle">
                  <div>
                     <p class="terms-kicker"><i class="fa-solid fa-file-signature" aria-hidden="true"></i> Required before dashboard access</p>
                     <h1 class="terms-title" id="termsWelcomeTitle">Review the Terms and Conditions</h1>
                     <p class="terms-copy">Before entering your SpeakReady AI dashboard, please review and accept the current terms. This keeps account access clear, auditable, and consistent across desktop and mobile.</p>
                     <div class="terms-progress-card" aria-label="Account setup progress">
                        <div class="terms-progress-title">
                           <span>Account Setup</span>
                           <span class="terms-progress-pill"><i class="fa-solid fa-shield-check" aria-hidden="true"></i> Step 2 of 3</span>
                        </div>
                        <div class="terms-progress-steps">
                           <div class="terms-progress-step">
                              <span><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                              <strong>Registered</strong>
                              <small>Your account is ready.</small>
                           </div>
                           <div class="terms-progress-step is-current">
                              <span><i class="fa-solid fa-file-signature" aria-hidden="true"></i></span>
                              <strong>Agreement</strong>
                              <small>Review and confirm.</small>
                           </div>
                           <div class="terms-progress-step">
                              <span><i class="fa-solid fa-gauge-high" aria-hidden="true"></i></span>
                              <strong>Dashboard</strong>
                              <small>Start practicing.</small>
                           </div>
                        </div>
                     </div>
                  </div>

                  <div class="terms-summary-grid" aria-label="Agreement summary">
                     <div class="terms-summary-item">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        <strong>Protected access</strong>
                        <span>The dashboard opens only after acceptance is saved.</span>
                     </div>
                     <div class="terms-summary-item">
                        <i class="fa-solid fa-clock" aria-hidden="true"></i>
                        <strong>Recorded consent</strong>
                        <span>We store the acceptance time, version, and IP address.</span>
                     </div>
                     <div class="terms-summary-item">
                        <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                        <strong>Your account</strong>
                        <span>Terms apply to practice sessions, feedback, and account use.</span>
                     </div>
                     <div class="terms-summary-item">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <strong>Version {{ $termsVersion }}</strong>
                        <span>Last updated {{ $lastUpdated }}.</span>
                     </div>
                  </div>
               </section>

               <section class="terms-panel" aria-labelledby="termsPanelTitle">
                  <div class="terms-panel-head">
                     <div>
                        <h2 class="terms-panel-title" id="termsPanelTitle">SpeakReady AI Terms</h2>
                        <p class="terms-panel-meta">Read the key terms below, then confirm your agreement.</p>
                     </div>
                     <span class="terms-version"><i class="fa-regular fa-file-lines" aria-hidden="true"></i>{{ $termsVersion }}</span>
                  </div>

                  <div class="terms-scroll" tabindex="0">
                     @foreach($sections as $section)
                        <article class="terms-section">
                           <span class="terms-section-icon"><i class="{{ $section['icon'] }}" aria-hidden="true"></i></span>
                           <div>
                              <h2>{{ $section['heading'] }}</h2>
                              <p>{{ $section['body'] }}</p>
                           </div>
                        </article>
                     @endforeach
                  </div>

                  <form class="terms-form" method="POST" action="{{ route('terms.acceptance.store') }}">
                     @csrf
                     <div class="terms-check">
                        <input type="checkbox" name="terms_accepted" value="1" id="termsAccepted" required @checked(old('terms_accepted'))>
                        <label for="termsAccepted">
                           I have read and agree to the
                           <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer">Terms of Service</a>
                           and
                           <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.
                        </label>
                     </div>
                     @error('terms_accepted')
                        <div class="terms-error" role="alert">{{ $message }}</div>
                     @enderror
                     <button class="terms-submit" id="termsSubmit" type="submit" disabled>
                        Continue to Dashboard <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                     </button>
                  </form>
               </section>
            </div>
         </div>
      </main>

      <script>
         (function () {
            const checkbox = document.getElementById('termsAccepted');
            const button = document.getElementById('termsSubmit');

            if (!checkbox || !button) {
               return;
            }

            function syncButton() {
               button.disabled = !checkbox.checked;
            }

            checkbox.addEventListener('change', syncButton);
            syncButton();
         })();
      </script>
      @include('shared.terms.registration-success-modal')
   </body>
</html>
