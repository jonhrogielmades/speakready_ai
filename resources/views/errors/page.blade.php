@php
    $errorCode = (string) ($code ?? 500);
    $errorEyebrow = $eyebrow ?? 'Something needs attention';
    $errorTitle = $title ?? 'We hit a small roadblock.';
    $errorMessage = $message ?? 'Please try again in a moment. Your SpeakReady practice space will be here when you return.';
    $errorTone = $tone ?? 'blue';
    $errorIcon = $icon ?? 'alert';
    $primaryLabel = $primaryLabel ?? 'Back to SpeakReady AI';
    $primaryUrl = $primaryUrl ?? url('/');
    $secondaryLabel = $secondaryLabel ?? 'Go back';
    $secondaryMode = $secondaryMode ?? 'back';
    $errorHint = $hint ?? 'If the problem continues, please try refreshing the page or return a little later.';
    $systemName = config('app.name', 'SpeakReady AI');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f1f6fb">
    <title>{{ $errorCode }} &middot; {{ $systemName }}</title>
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
    <style>
        :root {
            color-scheme: light;
            --ink: #153b63;
            --muted: #607995;
            --soft: #edf5fc;
            --line: #dceaf5;
            --accent: #2563eb;
            --accent-dark: #174ea6;
            --accent-soft: #dceafe;
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100svh;
            min-height: 100dvh;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 12%, rgba(37, 99, 235, .14), transparent 27rem),
                radial-gradient(circle at 92% 88%, rgba(34, 211, 238, .15), transparent 25rem),
                #f1f6fb;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .error-shell {
            min-height: 100vh;
            min-height: 100svh;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: max(28px, env(safe-area-inset-top)) max(18px, env(safe-area-inset-right)) max(28px, env(safe-area-inset-bottom)) max(18px, env(safe-area-inset-left));
            position: relative;
            overflow: hidden;
        }

        .error-shell::before,
        .error-shell::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(37, 99, 235, .10);
            border-radius: 50%;
            pointer-events: none;
        }

        .error-shell::before {
            width: 430px;
            height: 430px;
            top: -220px;
            right: -150px;
        }

        .error-shell::after {
            width: 300px;
            height: 300px;
            bottom: -170px;
            left: -120px;
        }

        .error-card {
            width: min(760px, 100%);
            position: relative;
            z-index: 1;
            padding: clamp(32px, 7vw, 64px) clamp(22px, 7vw, 72px) 34px;
            text-align: center;
            background: rgba(255, 255, 255, .92);
            border: 1px solid rgba(220, 234, 245, .96);
            border-radius: 30px;
            box-shadow: 0 24px 70px rgba(35, 77, 119, .14), 0 3px 12px rgba(35, 77, 119, .05);
            backdrop-filter: blur(14px);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            color: var(--ink);
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -.15px;
            text-decoration: none;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            padding: 5px;
            object-fit: cover;
            background: #fff;
            border: 1px solid #d7e6f5;
            border-radius: 50%;
            box-shadow: 0 8px 18px rgba(38, 91, 145, .14);
        }

        .brand-name { display: block; }

        .brand > span { min-width: 0; }

        .brand-name { overflow-wrap: anywhere; }

        .brand-tagline {
            display: block;
            margin-top: 2px;
            color: #7b91aa;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .85px;
            text-transform: uppercase;
        }

        .error-visual {
            display: grid;
            place-items: center;
            width: 116px;
            height: 116px;
            margin: 38px auto 22px;
            color: var(--accent-dark);
            background: linear-gradient(145deg, #fff, var(--accent-soft));
            border: 1px solid rgba(37, 99, 235, .14);
            border-radius: 34px;
            box-shadow: inset 0 1px rgba(255, 255, 255, .85), 0 16px 34px rgba(37, 99, 235, .13);
        }

        .error-visual svg {
            width: 48px;
            height: 48px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.8;
        }

        .error-code {
            margin: 0;
            color: var(--accent-dark);
            font-size: clamp(3.9rem, 11vw, 6.8rem);
            font-weight: 850;
            letter-spacing: -.08em;
            line-height: .86;
        }

        .error-kicker {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 26px 0 12px;
            color: var(--accent-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .error-kicker::before {
            content: "";
            width: 7px;
            height: 7px;
            background: var(--accent);
            border-radius: 50%;
            box-shadow: 0 0 0 5px var(--accent-soft);
        }

        .error-title {
            max-width: 610px;
            margin: 0 auto;
            color: var(--ink);
            font-size: clamp(1.75rem, 4vw, 2.8rem);
            font-weight: 800;
            letter-spacing: -.045em;
            line-height: 1.08;
        }

        .error-message {
            max-width: 550px;
            margin: 17px auto 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.72;
        }

        .error-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .error-button {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 12px 20px;
            border: 1px solid transparent;
            border-radius: 13px;
            font: inherit;
            font-size: .92rem;
            font-weight: 750;
            cursor: pointer;
            touch-action: manipulation;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease;
        }

        .error-button:hover { transform: translateY(-2px); }

        .error-button-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            box-shadow: 0 10px 20px rgba(37, 99, 235, .22);
        }

        .error-button-primary:hover { box-shadow: 0 14px 26px rgba(37, 99, 235, .28); }

        .error-button-secondary {
            color: #355574;
            background: #f7fbff;
            border-color: #d8e7f4;
        }

        .error-button-secondary:hover { background: #eef6fd; }

        .error-button:focus-visible,
        .brand:focus-visible {
            outline: 3px solid rgba(37, 99, 235, .28);
            outline-offset: 4px;
        }

        .error-hint {
            max-width: 500px;
            margin: 28px auto 0;
            padding-top: 20px;
            color: #879bb0;
            border-top: 1px solid #e6eff7;
            font-size: .82rem;
            line-height: 1.55;
        }

        .error-footer {
            margin-top: 22px;
            color: #9aabbb;
            font-size: .73rem;
        }

        .tone-amber { --accent: #d97706; --accent-dark: #a35405; --accent-soft: #fff0d7; }
        .tone-coral { --accent: #e05252; --accent-dark: #b83232; --accent-soft: #ffe3e3; }
        .tone-cyan { --accent: #0891b2; --accent-dark: #0e7490; --accent-soft: #cffafe; }

        @media (max-width: 560px) {
            .error-shell { padding: max(14px, env(safe-area-inset-top)) max(14px, env(safe-area-inset-right)) max(14px, env(safe-area-inset-bottom)) max(14px, env(safe-area-inset-left)); }
            .error-card { padding: 30px 20px 26px; border-radius: 24px; }
            .brand { font-size: 15px; }
            .brand-mark { width: 42px; height: 42px; }
            .brand-tagline { font-size: 9px; letter-spacing: .65px; }
            .error-visual { width: 96px; height: 96px; margin-top: 30px; border-radius: 28px; }
            .error-visual svg { width: 42px; height: 42px; }
            .error-button { width: 100%; }
            .error-actions { gap: 9px; }
        }

        @media (max-height: 640px) and (orientation: landscape) {
            .error-shell { place-items: start center; }
            .error-card { margin: 12px 0; padding-top: 24px; padding-bottom: 22px; }
            .error-visual { width: 78px; height: 78px; margin: 20px auto 16px; border-radius: 24px; }
            .error-visual svg { width: 36px; height: 36px; }
            .error-kicker { margin-top: 18px; }
            .error-message { margin-top: 12px; }
            .error-actions { margin-top: 20px; }
            .error-hint { margin-top: 20px; padding-top: 14px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .error-button { transition: none; }
            .error-button:hover { transform: none; }
        }
    </style>
</head>
<body class="tone-{{ $errorTone }}">
    <main class="error-shell">
        <section class="error-card" aria-labelledby="error-title">
            <a class="brand" href="{{ url('/') }}" aria-label="Return to {{ $systemName }}">
                <img class="brand-mark" src="{{ asset('img/logo.png') }}" alt="">
                <span>
                    <span class="brand-name">{{ $systemName }}</span>
                    <span class="brand-tagline">Practice smarter. Interview better.</span>
                </span>
            </a>

            <div class="error-visual" aria-hidden="true">
                @if ($errorIcon === 'search')
                    <svg viewBox="0 0 48 48"><circle cx="21" cy="21" r="11"></circle><path d="m29.5 29.5 9 9"></path><path d="M17 21h8"></path></svg>
                @elseif ($errorIcon === 'lock')
                    <svg viewBox="0 0 48 48"><rect x="12" y="21" width="24" height="18" rx="4"></rect><path d="M17 21v-5a7 7 0 0 1 14 0v5"></path><path d="M24 28v5"></path></svg>
                @elseif ($errorIcon === 'refresh')
                    <svg viewBox="0 0 48 48"><path d="M38 18a15 15 0 0 0-26-3l-3 4"></path><path d="M9 12v7h7"></path><path d="M10 30a15 15 0 0 0 26 3l3-4"></path><path d="M39 36v-7h-7"></path></svg>
                @elseif ($errorIcon === 'offline')
                    <svg viewBox="0 0 48 48"><path d="M7 18a25 25 0 0 1 34 0"></path><path d="M13 25a16 16 0 0 1 22 0"></path><path d="M20 32a7 7 0 0 1 8 0"></path><path d="m8 8 32 32"></path><circle cx="24" cy="38" r="1.5" fill="currentColor" stroke="none"></circle></svg>
                @else
                    <svg viewBox="0 0 48 48"><circle cx="24" cy="24" r="16"></circle><path d="M24 14v12"></path><path d="M24 32h.01"></path></svg>
                @endif
            </div>

            <p class="error-code" aria-label="Error {{ $errorCode }}">{{ $errorCode }}</p>
            <p class="error-kicker">{{ $errorEyebrow }}</p>
            <h1 class="error-title" id="error-title">{{ $errorTitle }}</h1>
            <p class="error-message">{{ $errorMessage }}</p>

            <div class="error-actions">
                <a class="error-button error-button-primary" href="{{ $primaryUrl }}">{{ $primaryLabel }} <span aria-hidden="true">&rarr;</span></a>
                @if ($secondaryMode === 'reload')
                    <button class="error-button error-button-secondary" type="button" onclick="window.location.reload()">{{ $secondaryLabel }}</button>
                @else
                    <button class="error-button error-button-secondary" type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = @js(url('/')); }">{{ $secondaryLabel }}</button>
                @endif
            </div>

            <p class="error-hint">{{ $errorHint }}</p>
            <p class="error-footer">&copy; {{ date('Y') }} {{ $systemName }} &middot; Your next confident answer starts with practice.</p>
        </section>
    </main>
</body>
</html>
