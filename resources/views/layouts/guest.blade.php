<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
      <meta name="theme-color" content="#f7fbff">
      <title>@yield('title', 'SpeakReady AI - Practice Smarter. Interview Better.')</title>
      <script src="{{ asset('js/theme-boot.js?v=1') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <!-- Bootstrap 5.3 -->
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"/>
      <!-- AOS Animate on Scroll -->
      <link href="{{ asset('css/aos.css') }}" rel="stylesheet"/>
      <!-- Swiper CSS -->
      <link href="{{ asset('css/swiper-bundle.min.css') }}" rel="stylesheet"/>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
      <!-- all min css -->
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}"/>
      <!-- magnific CSS -->
      <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}"/>
      <!-- Style CSS -->
      <link rel="stylesheet" href="{{ asset('css/style.css?v=1') }}" />
      <style>
         html, body {
            overflow-x: hidden;
            width: 100%;
            position: relative;
         }
         .feature-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
            border-color: var(--pur);
         }
         .hnum {
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(139, 92, 246, 0.15);
            color: var(--pur); font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px; font-size: 1.2rem;
         }

         /* --- PWA Install Prompt --- */
         #pwa-install-prompt {
            display: none; position: fixed;
            bottom: 20px;
            left: 16px; right: 16px; z-index: 9999;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid #e5e7eb; border-radius: 16px;
            padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center; animation: mobFadeIn 0.3s ease;
         }
         html:not(.lm) #pwa-install-prompt { background: rgba(8, 8, 15, 0.95); box-shadow: 0 10px 30px rgba(0,0,0,0.5); border-color: #333; }
         #pwa-install-prompt h5 { color: #111; font-weight: 700; margin-bottom: 8px; font-size: 1.1rem; }
         html:not(.lm) #pwa-install-prompt h5 { color: #fff; }
         #pwa-install-prompt p { color: #555; font-size: 0.85rem; margin-bottom: 16px; }
         html:not(.lm) #pwa-install-prompt p { color: #aaa; }
         .pwa-btn-wrap { display: flex; gap: 12px; justify-content: center; }
         .pwa-btn-no { flex: 1; padding: 10px; border-radius: 10px; border: 1px solid #ccc; background: transparent; color: #333; font-weight: 600; cursor: pointer; }
         html:not(.lm) .pwa-btn-no { border-color: #444; color: #fff; }
         .pwa-btn-yes { flex: 1; padding: 10px; border-radius: 10px; border: none; background: var(--pur, #8b5cf6); color: #fff; font-weight: 600; cursor: pointer; }

         .guest-brand,
         .guest-brand-copy {
            min-width: 0;
         }

         .guest-brand-copy {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.05;
         }

         .guest-brand-name {
            display: block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
         }

         .guest-header-clock {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            max-width: 100%;
            margin-top: 4px;
            overflow: hidden;
            color: var(--tx3);
            font-size: 0.58rem;
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: 0.015em;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
         }

         .guest-header-clock-separator {
            opacity: 0.55;
         }

         .auth-modal-close {
            width: 10px;
            height: 10px;
            padding: 6px;
            margin: 0;
            opacity: 0.75;
         }

         html.lm .auth-modal-close {
            filter: none;
         }

         html:not(.lm) .auth-modal-close {
            filter: invert(1) grayscale(100%) brightness(200%);
         }

         #lofc {
            position: fixed !important;
            inset: 0 !important;
            width: 100vw !important;
            max-width: none !important;
            height: 100vh !important;
            height: 100dvh !important;
            padding-left: 16px !important;
            padding-right: 16px !important;
            background: rgba(15, 23, 42, 0.16) !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-left: 0 !important;
            overflow-y: auto;
            pointer-events: none;
         }

         #lofc.show {
            display: grid !important;
            place-items: center;
         }

         #lofc .modal-dialog {
            width: min(100%, 390px);
            max-width: 390px;
            margin: 14px auto !important;
            pointer-events: auto;
         }

         #lofc .modal-content {
            max-height: calc(100vh - 28px);
            max-height: calc(100dvh - 28px);
            border-radius: 14px !important;
            border-color: rgba(148, 163, 184, 0.28) !important;
            box-shadow: 0 14px 42px rgba(15, 23, 42, 0.2) !important;
            overflow: hidden;
         }

         #lofc .modal-header {
            min-height: 58px;
            padding: 14px 18px;
         }

         #lofc .modal-title {
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.1;
         }

         #lofc .modal-body {
            padding: 18px 22px 22px !important;
            overflow-y: auto;
         }

         #lofc .tab-switch {
            padding: 3px;
            margin-bottom: 18px;
            border-radius: 10px;
         }

         #lofc .tab-sw-btn {
            min-height: 36px;
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 0.84rem;
         }

         #lofc .olbl {
            margin-bottom: 5px;
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--tx);
         }

         #lofc .oinp {
            min-height: 40px;
            padding: 8px 12px;
            margin-bottom: 12px;
            border-radius: 9px !important;
            border-color: rgba(148, 163, 184, 0.42) !important;
            font-size: 0.86rem;
         }

         #lofc .password-field {
            margin-bottom: 12px !important;
         }

         #lofc .password-field .oinp {
            margin-bottom: 0;
            padding-right: 40px;
         }

         #lofc .password-toggle {
            right: 7px;
            width: 30px;
            height: 30px;
            border-radius: 8px;
         }

         #lofc .bgrd.btn {
            min-height: 42px;
            padding: 9px 14px !important;
            border-radius: 10px !important;
            font-size: 0.88rem !important;
         }

         #lofc .oauth {
            min-height: 40px;
            border-radius: 9px;
            font-size: 0.86rem;
         }

         #lofc .odiv {
            margin: 16px 0 14px;
            font-size: 0.76rem;
         }

         #lofc p {
            font-size: 0.76rem !important;
         }

         #lofc .auth-panel {
            animation: authPanelIn 0.26s ease both;
            transform-origin: center top;
         }

         #lofc .auth-panel.is-switching-out {
            animation: authPanelOut 0.16s ease both;
         }

         @keyframes authPanelIn {
            from {
               opacity: 0;
               transform: translateY(8px) scale(0.985);
            }
            to {
               opacity: 1;
               transform: translateY(0) scale(1);
            }
         }

         @keyframes authPanelOut {
            from {
               opacity: 1;
               transform: translateY(0) scale(1);
            }
            to {
               opacity: 0;
               transform: translateY(-6px) scale(0.99);
            }
         }

         @media (prefers-reduced-motion: reduce) {
            #lofc .auth-panel,
            #lofc .auth-panel.is-switching-out {
               animation: none !important;
            }
         }

         @media (max-width: 575.98px) {
            #lofc {
               padding: max(10px, env(safe-area-inset-top)) 12px max(12px, env(safe-area-inset-bottom)) !important;
               background: rgba(2, 6, 23, 0.62) !important;
               backdrop-filter: blur(12px) saturate(1.08);
               -webkit-backdrop-filter: blur(12px) saturate(1.08);
               place-items: center;
               align-content: center;
            }

            #lofc .modal-dialog {
               width: min(100%, 350px);
               min-height: 0 !important;
               margin: 0 auto !important;
            }

            #lofc .modal-content {
               max-height: calc(100dvh - 22px);
               border-radius: 13px !important;
            }

            #lofc .modal-header {
               min-height: 52px;
               padding: 12px 15px;
            }

            #lofc .modal-title {
               font-size: 0.96rem;
            }

            #lofc .modal-header .logo-i {
               width: 26px !important;
               height: 26px !important;
            }

            #lofc .modal-body {
               padding: 14px 16px 18px !important;
            }

            #lofc .tab-switch {
               margin-bottom: 14px;
            }

            #lofc .tab-sw-btn {
               min-height: 34px;
               font-size: 0.8rem;
            }

            #lofc .olbl {
               font-size: 0.73rem;
            }

            #lofc .oinp,
            #lofc .oauth {
               min-height: 38px;
               font-size: 0.82rem;
            }

            #lofc .bgrd.btn {
               min-height: 40px;
               font-size: 0.84rem !important;
            }

            #lofc .odiv {
               margin: 14px 0 12px;
            }

            #lofc p {
               margin-top: 14px !important;
               margin-bottom: 0 !important;
            }
         }

         @media (max-width: 380px) {
            #lofc {
               padding-left: 10px !important;
               padding-right: 10px !important;
            }

            #lofc .modal-dialog {
               width: 100%;
            }

            #lofc .modal-body {
               padding-left: 14px !important;
               padding-right: 14px !important;
            }
         }

         #lofc + .modal-backdrop,
         .modal-backdrop.show {
            display: none;
         }

         .guest-tour-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000000;
            display: none;
            background: rgba(15, 23, 42, 0.18);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
         }

         .guest-tour-overlay.show {
            display: block;
         }

         .guest-tour-card {
            position: fixed;
            z-index: 1000004;
            width: min(300px, calc(100vw - 32px));
            display: none;
            padding: 14px;
            border-radius: 12px;
            background: var(--sf);
            border: 1px solid var(--bd);
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.24);
            color: var(--tx);
            animation: guestTourIn 0.22s ease both;
         }

         .guest-tour-card h6 {
            margin: 0 0 6px;
            font-size: 0.92rem;
            font-weight: 800;
         }

         .guest-tour-card p {
            margin: 0 0 12px;
            color: var(--tx2);
            font-size: 0.78rem;
            line-height: 1.45;
         }

         .guest-tour-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
         }

         .guest-tour-actions button {
            min-height: 32px;
            padding: 7px 11px;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 700;
         }

         .guest-tour-skip {
            border: 1px solid var(--bd);
            background: transparent;
            color: var(--tx2);
         }

         .guest-tour-next {
            border: 0;
            background: var(--grad);
            color: #fff;
         }

         .guest-tour-highlight {
            position: relative;
            z-index: 1000003 !important;
            box-shadow: 0 0 0 5px rgba(59, 130, 246, 0.18), 0 16px 38px rgba(37, 99, 235, 0.24) !important;
         }

         @keyframes guestTourIn {
            from { opacity: 0; transform: translateY(8px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
         }

         @media (max-width: 575.98px) {
            .guest-tour-card {
               left: 16px !important;
               right: 16px !important;
               bottom: 18px !important;
               top: auto !important;
               width: auto;
            }
         }

         @media (max-width: 575.98px) {
            .guest-header-clock { font-size: 0.53rem; }
         }

         @media (max-width: 360px) {
            .guest-header-clock {
               gap: 3px;
               font-size: 0.49rem;
            }
         }

         /* --- Mobile App Launch Splash --- */
         .sr-launch-screen {
            display: none;
         }

         @media (max-width: 820px), (display-mode: standalone) {
            body.guest-splash-pending {
               overflow: hidden;
               background: #f7fbff;
            }

            body.guest-splash-pending #landing,
            body.guest-splash-pending #lofc,
            body.guest-splash-pending #pwa-install-prompt {
               visibility: hidden;
            }

            .sr-launch-screen {
               position: fixed;
               inset: 0;
               z-index: 1000000;
               display: flex;
               align-items: center;
               justify-content: center;
               min-height: 100vh;
               min-height: 100svh;
               padding: max(28px, env(safe-area-inset-top)) 24px max(26px, env(safe-area-inset-bottom));
               background:
                  linear-gradient(180deg, #ffffff 0%, #f7fbff 48%, #edf7ff 100%);
               color: #0f172a;
               text-align: center;
               opacity: 1;
               visibility: visible;
               transition: opacity 0.42s ease, visibility 0.42s ease;
            }

            .sr-launch-screen.is-hiding {
               opacity: 0;
               visibility: hidden;
               pointer-events: none;
            }

            .sr-launch-content {
               width: min(100%, 360px);
               display: flex;
               flex-direction: column;
               align-items: center;
            }

            .sr-launch-mark {
               position: relative;
               width: clamp(118px, 34vw, 158px);
               aspect-ratio: 1;
               border-radius: 32px;
               display: grid;
               place-items: center;
               margin-bottom: 26px;
               background: rgba(255, 255, 255, 0.92);
               border: 1px solid rgba(59, 130, 246, 0.16);
               box-shadow:
                  0 22px 48px rgba(15, 23, 42, 0.10),
                  inset 0 1px 0 rgba(255, 255, 255, 0.92);
            }

            .sr-launch-mark::after {
               content: "";
               position: absolute;
               inset: 11px;
               border-radius: 24px;
               border: 1px solid rgba(20, 184, 166, 0.16);
               pointer-events: none;
            }

            .sr-launch-mark img {
               width: 76%;
               height: 76%;
               object-fit: contain;
               filter: drop-shadow(0 10px 16px rgba(37, 99, 235, 0.16));
            }

            .sr-launch-kicker {
               margin: 0 0 9px;
               color: #2563eb;
               font-size: clamp(0.7rem, 2.4vw, 0.78rem);
               font-weight: 700;
               letter-spacing: 0.15em;
               text-transform: uppercase;
            }

            .sr-launch-title {
               margin: 0;
               color: #0f172a;
               font-size: clamp(2rem, 9vw, 2.75rem);
               font-weight: 800;
               line-height: 1.05;
               letter-spacing: 0;
            }

            .sr-launch-copy {
               max-width: 300px;
               margin: 12px auto 28px;
               color: #475569;
               font-size: clamp(0.9rem, 3.45vw, 1rem);
               line-height: 1.55;
            }

            .sr-launch-progress {
               width: min(100%, 238px);
               height: 5px;
               overflow: hidden;
               border-radius: 999px;
               background: #dbeafe;
            }

            .sr-launch-progress span {
               display: block;
               width: 42%;
               height: 100%;
               border-radius: inherit;
               background: linear-gradient(90deg, #2563eb, #14b8a6);
               animation: srLaunchLoad 1.28s ease-in-out infinite;
            }

            .sr-launch-status {
               min-height: 18px;
               margin-top: 15px;
               color: #64748b;
               font-size: clamp(0.72rem, 2.8vw, 0.82rem);
               font-weight: 600;
            }
         }

         @media (max-width: 360px) {
            .sr-launch-mark {
               border-radius: 26px;
               margin-bottom: 22px;
            }

            .sr-launch-copy {
               margin-bottom: 24px;
            }
         }

         @media (prefers-reduced-motion: reduce) {
            .sr-launch-screen,
            .sr-launch-progress span {
               animation: none !important;
               transition: none !important;
            }
         }

         @media (max-width: 767px) {
            #hero {
               min-height: auto;
               padding-top: calc(var(--nav) + 18px);
               justify-content: flex-start;
            }

            #hero .container {
               max-width: 100%;
               padding-left: 18px;
               padding-right: 18px;
            }

            #hero .hbadge {
               display: inline-block !important;
               max-width: min(100%, 320px);
               margin-bottom: 18px;
               padding: 8px 12px;
               white-space: normal;
               text-align: center;
               line-height: 1.35;
               font-size: clamp(0.64rem, 2.55vw, 0.72rem);
               letter-spacing: 0;
            }

            #hero .h1 {
               max-width: 330px;
               margin-left: auto;
               margin-right: auto;
               font-size: clamp(1.86rem, 8.1vw, 2.05rem) !important;
               line-height: 1.08;
               letter-spacing: 0;
            }

            #hero .h1 span {
               display: inline-block;
            }

            #hero p.mx-auto {
               max-width: 340px !important;
               margin-bottom: 26px !important;
               font-size: clamp(0.86rem, 3.6vw, 0.98rem) !important;
               line-height: 1.55;
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row {
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr));
               align-items: stretch !important;
               justify-content: center !important;
               gap: 8px !important;
               width: min(100%, 350px);
               margin-left: auto;
               margin-right: auto;
               flex-wrap: nowrap !important;
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row > .btn {
               width: 100% !important;
               max-width: none !important;
               min-width: 0;
               min-height: 42px;
               padding: 9px 8px !important;
               border-radius: 8px;
               display: inline-flex;
               align-items: center;
               justify-content: center;
               gap: 4px;
               font-size: clamp(0.72rem, 3vw, 0.82rem) !important;
               line-height: 1.1;
               white-space: nowrap;
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row > .btn:first-child {
               grid-column: 1 / -1;
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row i {
               margin-right: 0 !important;
               font-size: 0.75rem;
               flex: 0 0 auto;
            }
         }

         .ui-showcase {
            display: block;
            width: 100%;
         }

         .ui-device {
            overflow: hidden;
            background: var(--sf);
            border: 1px solid var(--bd2);
            box-shadow: 0 18px 44px rgba(0, 0, 0, 0.28);
         }

         .ui-device-mobile {
            max-width: 318px;
            margin-inline: auto;
            border-radius: 14px;
         }

         .ui-device-desktop {
            width: min(100%, 1280px);
            margin-inline: auto;
            border-radius: 14px;
         }

         .ui-device-bar {
            height: 33px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 14px;
            background: var(--bg3);
            border-bottom: 1px solid var(--bd2);
         }

         .ui-device-title {
            min-width: 0;
            flex: 1;
            max-width: 360px;
            height: 20px;
            margin-left: 8px;
            padding: 0 12px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            color: var(--tx3);
            background: rgba(148, 163, 184, 0.12);
            border: 1px solid var(--bd);
            font-size: 0.64rem;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
         }

         .ui-device-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
         }

         .ui-dashboard {
            padding: 10px;
            color: var(--tx);
         }

         .ui-dashboard-desktop {
            display: grid;
            grid-template-columns: 230px minmax(0, 1fr);
            gap: 20px;
            padding: 24px;
         }

         .ui-sidebar {
            padding: 12px;
            border-radius: 8px;
            background: var(--bg3);
            border: 1px solid var(--bd);
         }

         .ui-side-title {
            margin-bottom: 14px;
            color: var(--tx3);
            font-size: 0.63rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
         }

         .ui-side-item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            min-height: 38px;
            margin-bottom: 8px;
            padding: 9px 10px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: var(--tx2);
            font-size: 0.78rem;
            font-weight: 700;
            text-align: left;
         }

         .ui-side-item.active {
            color: var(--tx);
            background: rgba(59, 130, 246, 0.16);
            box-shadow: inset 3px 0 0 #3b82f6;
         }

         .ui-main {
            min-width: 0;
         }

         .ui-main-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
         }

         .ui-main-title {
            margin: 0;
            color: var(--tx);
            font-size: 1.2rem;
            font-weight: 800;
         }

         .ui-main-subtitle {
            margin: 3px 0 0;
            color: var(--tx3);
            font-size: 0.72rem;
            font-weight: 600;
         }

         .ui-main-chip {
            padding: 5px 9px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.16);
            color: #2563eb;
            font-size: 0.68rem;
            font-weight: 800;
         }

         .ui-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 10px;
         }

         .ui-device-desktop .ui-stat-grid {
            gap: 12px;
            margin-bottom: 14px;
         }

         .ui-device-mobile .ui-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-bottom: 14px;
         }

         .ui-stat {
            min-height: 70px;
            padding: 11px;
            border-radius: 8px;
            background: var(--bg3);
            border: 1px solid var(--bd2);
            box-shadow: 0 2px 0 #38bdf8;
         }

         .ui-device-desktop .ui-stat {
            min-height: 92px;
            padding: 15px;
            box-shadow: 0 3px 0 rgba(56, 189, 248, 0.9);
         }

         .ui-stat-value {
            color: var(--tx);
            font-size: 1.28rem;
            font-weight: 800;
            line-height: 1.05;
         }

         .ui-device-desktop .ui-stat-value {
            font-size: 1.62rem;
         }

         .ui-device-desktop .ui-stat-label {
            font-size: 0.74rem;
         }

         .ui-stat-value.accent {
            color: #2563eb;
         }

         .ui-stat-label {
            margin-top: 6px;
            color: var(--tx3);
            font-size: 0.66rem;
            line-height: 1.15;
         }

         .ui-stat-note {
            margin-top: 3px;
            color: #10b981;
            font-size: 0.64rem;
            font-weight: 800;
         }

         .ui-panel-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(260px, 0.88fr);
            gap: 12px;
         }

         .ui-device-mobile .ui-panel-grid {
            grid-template-columns: 1fr;
         }

         .ui-panel {
            padding: 12px;
            border-radius: 8px;
            background: var(--bg3);
            border: 1px solid var(--bd2);
         }

         .ui-device-desktop .ui-panel {
            min-height: 168px;
            padding: 16px;
         }

         .ui-panel-title {
            margin-bottom: 10px;
            color: var(--tx3);
            font-size: 0.72rem;
            font-weight: 800;
         }

         .ui-bars {
            display: flex;
            align-items: end;
            gap: 6px;
            height: 72px;
         }

         .ui-device-desktop .ui-bars {
            gap: 9px;
            height: 104px;
         }

         .ui-bars span {
            flex: 1;
            min-width: 0;
            border-radius: 4px 4px 0 0;
            background: #6097ee;
         }

         .ui-axis {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            color: var(--tx3);
            font-size: 0.61rem;
         }

         .ui-chat {
            display: flex;
            flex-direction: column;
            gap: 8px;
         }

         .ui-pulse {
            display: inline-block;
            width: 7px;
            height: 7px;
            margin-right: 6px;
            border-radius: 999px;
            background: #3b82f6;
            box-shadow: 0 0 7px #3b82f6;
            animation: bpls 2s infinite;
         }

         .ui-bubble {
            max-width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.72rem;
            line-height: 1.35;
         }

         .ui-device-desktop .ui-bubble {
            font-size: 0.78rem;
         }

         .ui-bubble.user {
            align-self: flex-end;
            background: #1495ee;
            color: #fff;
            font-weight: 700;
         }

         .ui-bubble.ai {
            background: rgba(99, 102, 241, 0.16);
            color: var(--tx2);
            border: 1px solid rgba(129, 140, 248, 0.24);
         }

         .lm .ui-device {
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.14);
         }

         .lm .ui-main-chip {
            color: #2563eb;
         }

         @media (max-width: 991.98px) {
            .ui-device-desktop {
               display: none;
            }
         }

         @media (min-width: 992px) {
            .ui-showcase {
               width: min(calc(100vw - 32px), 1300px);
               margin-left: 50%;
               transform: translateX(-50%);
            }

            .ui-device-mobile {
               display: none;
            }
         }

         @media (min-width: 992px) and (max-width: 1199.98px) {
            .ui-dashboard-desktop {
               grid-template-columns: 190px minmax(0, 1fr);
               gap: 14px;
               padding: 18px;
            }

            .ui-device-desktop .ui-stat {
               padding: 13px;
            }

            .ui-panel-grid {
               grid-template-columns: minmax(0, 1.25fr) minmax(230px, 0.85fr);
            }
         }

         @media (max-width: 575.98px) {
            .ui-device-mobile {
               max-width: 100%;
            }
         }

         @media (max-width: 360px) {
            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row {
               gap: 7px !important;
               grid-template-columns: 1fr;
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row > .btn {
               min-height: 40px;
               padding-left: 8px !important;
               padding-right: 8px !important;
               font-size: 0.74rem !important;
            }
         }

         @keyframes mobFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
         }

         @keyframes srLaunchLoad {
            0% { transform: translateX(-115%); }
            55% { transform: translateX(88%); }
            100% { transform: translateX(245%); }
         }
      </style>
   </head>
   <body @if(!$errors->any()) class="guest-splash-pending" @endif>
      @include('partials.viewport-mobile-cookie')
      @if(!$errors->any())
      <div id="srLaunchScreen" class="sr-launch-screen" role="status" aria-live="polite" aria-label="Opening SpeakReady AI">
         <div class="sr-launch-content">
            <div class="sr-launch-mark">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
            </div>
            <p class="sr-launch-kicker">AI Interview Coach</p>
            <h1 class="sr-launch-title">SpeakReady AI</h1>
            <p class="sr-launch-copy">Practice. Improve. Speak with confidence.</p>
            <div class="sr-launch-progress" aria-hidden="true"><span></span></div>
            <div class="sr-launch-status">Preparing your practice space</div>
         </div>
      </div>
      <script>
         (function () {
            const splash = document.getElementById('srLaunchScreen');
            if (!splash) return;

            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const isMobile = window.matchMedia('(max-width: 820px)').matches;
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const seenKey = 'speakready_guest_splash_seen';
            const shouldShow = (isStandalone || isMobile) && !sessionStorage.getItem(seenKey);

            function clearSplash() {
               document.body.classList.remove('guest-splash-pending');
               splash.classList.add('is-hiding');
               window.setTimeout(function () {
                  splash.remove();
               }, reduceMotion ? 0 : 460);
            }

            if (!shouldShow) {
               clearSplash();
               return;
            }

            sessionStorage.setItem(seenKey, '1');
            const startedAt = window.performance ? performance.now() : Date.now();
            const minimumDuration = reduceMotion ? 240 : 1650;

            function finishWhenReady() {
               const now = window.performance ? performance.now() : Date.now();
               const remaining = Math.max(0, minimumDuration - (now - startedAt));
               window.setTimeout(clearSplash, remaining);
            }

            if (document.readyState === 'loading') {
               document.addEventListener('DOMContentLoaded', finishWhenReady, { once: true });
            } else {
               finishWhenReady();
            }

            window.setTimeout(clearSplash, 3600);
         })();
      </script>
      @endif
<!-- ======================== LANDING PAGE ======================== -->
      <div id="landing">
         <!-- NAVBAR -->
         <nav id="nbar">
            <div class="container">
               <div class="d-flex align-items-center justify-content-between w-100">
                  <a href="#" class="guest-brand d-flex align-items-center gap-2 text-truncate" style="font-size:1.2rem;font-weight:700;color:var(--tx); max-width: calc(100vw - 120px);">
                     <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: transparent; padding: 0; flex-shrink: 0;">
                     <span class="guest-brand-copy">
                        <span class="guest-brand-name">SpeakReady AI</span>
                        @php
                           $guestHeaderNow = now();
                        @endphp
                        <time class="guest-header-clock" id="guestHeaderClock" datetime="{{ $guestHeaderNow->toIso8601String() }}" aria-label="Current date and time">
                           <span id="guestHeaderDate">{{ $guestHeaderNow->format('D, M j') }}</span>
                           <span class="guest-header-clock-separator" aria-hidden="true">&bull;</span>
                           <span id="guestHeaderTime">{{ $guestHeaderNow->format('g:i A') }}</span>
                        </time>
                     </span>
                  </a>
                  <div class="d-none d-lg-flex align-items-center gap-1 mx-auto">
                     <a href="#" class="nav-link">Home</a>
                     <a href="#features" class="nav-link">Features</a>
                     <a href="#how" class="nav-link">How It Works</a>
                     <a href="#benefits" class="nav-link">Interview Categories</a>
                     <a href="#developers" class="nav-link">Developers</a>
                     <a href="#faq" class="nav-link">FAQ</a>
                     <a href="#contact" class="nav-link">Contact Us</a>
                  </div>
                  <div class="d-flex align-items-center gap-2 flex-shrink-0">
                     <button class="boc d-flex align-items-center justify-content-center" id="thbtn" style="width:38px;height:38px;padding:0;border-radius:12px" aria-label="Toggle theme">
                     <i class="fa-solid fa-sun" id="suni" style="display:none"></i>
                     <i class="fa-solid fa-moon" id="mooni"></i>
                     </button>
                     <button class="boc px-3 py-2 d-none d-sm-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('login')">
                     <i class="fa-regular fa-user fa-sm"></i> Login
                     </button>
                     <button class="bgrd btn px-3 py-2 d-none d-sm-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('signup')">
                     Register <i class="fa-solid fa-arrow-right fa-sm"></i>
                     </button>
                     <button class="boc d-lg-none d-flex align-items-center justify-content-center" id="mbtog" style="width:38px;height:38px;padding:0;border-radius:10px" type="button" data-ucp-open aria-label="Open quick navigation" aria-haspopup="dialog" aria-controls="userCommandPalette" aria-expanded="false">
                     <i class="fa-solid fa-bars" aria-hidden="true"></i>
                     </button>
                  </div>
               </div>
            </div>
         </nav>
         @include('partials.user-command-palette', ['guestQuickNavigation' => true])

         <!-- HERO -->
         <section id="hero">
            <div class="aur aur-a" style="top:-80px;left:-120px"></div>
            <div class="aur aur-b" style="top:180px;right:-180px"></div>
            <div class="aur aur-a" style="bottom:-80px;left:45%;transform:translateX(-50%);opacity:.4"></div>
             <div class="container position-relative" style="z-index:2">
                <div class="text-center mt-3 pt-3 afu" style="animation-delay:.05s">
                    <span class="hbadge">
                 AI-Powered Practice | Real-Time Feedback
                    </span>
                </div>
                <div class="row align-items-center mt-4">
                  <div class="col-lg-5 col-md-6 mb-4 mb-lg-0 text-center position-relative order-1 order-lg-2">
                     <style>
                        .mic-3d-anim {
                           animation: float3d 4s ease-in-out infinite;
                           filter: drop-shadow(0 20px 30px rgba(0,0,0,0.2));
                           transform-style: preserve-3d;
                           transition: transform 0.5s ease;
                        }
                        .mic-3d-anim:hover {
                           transform: scale(1.05) rotateY(10deg) rotateX(5deg);
                        }
                        @keyframes float3d {
                           0% { transform: translateY(0px) rotateY(0deg); }
                           50% { transform: translateY(-15px) rotateY(5deg); }
                           100% { transform: translateY(0px) rotateY(0deg); }
                        }
                     </style>
                     <img src="{{ asset('img/hero_boy.png') }}" class="img-fluid mic-3d-anim afu" alt="SpeakReady AI Interview Practice" style="max-height: 450px; animation-delay: .1s; mix-blend-mode: multiply;">
                  </div>
                  <div class="col-lg-7 col-md-6 text-center order-2 order-lg-1">
                     <h1 class="h1 afu" style="animation-delay:.12s">Practice Smarter.<br><span class="gt">Interview Better.</span></h1>
                     <p class="mx-auto afu" style="max-width:580px;font-size:clamp(.95rem,1.8vw,1.2rem);color:var(--tx2);margin-bottom:36px;animation-delay:.2s">SpeakReady AI offers simulated mock interviews, personalized feedback, and comprehensive coaching to help you land your dream opportunity.</p>
                     <div class="hero-cta-row d-flex align-items-center justify-content-center gap-3 flex-wrap afu" style="animation-delay:.28s">
                        <button class="bgrd btn px-4 py-3 fs-6" id="guestGetStartedTourBtn" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('signup')">Get Started Free</button>
                        <button class="boc btn px-4 py-3 fs-6" id="heroInstallBtn"><i class="fa-solid fa-download me-2" style="color:var(--pur)"></i>Install App</button>
                        <a href="#features" class="boc btn px-4 py-3 fs-6">Learn More</a>
                     </div>

                   </div>
                </div>

                <div class="mt-3 mb-3 afu text-center" style="animation-delay:.4s">
                   <p style="font-size:.71rem;color:var(--tx3);text-transform:uppercase;letter-spacing:.12em;margin-bottom:14px">Featured Technologies</p>
                   <style>
                       .tech-icons a { color: inherit; text-decoration: none; display: flex; align-items: center; transition: all 0.2s ease; }
                       .tech-icons a:hover { transform: translateY(-3px) scale(1.1); color: var(--pur); }
                   </style>
                   <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap tech-icons" style="color:var(--tx2); font-size:1.5rem;">
                      <a href="https://laravel.com" target="_blank" rel="noopener noreferrer" title="Laravel"><i class="fa-brands fa-laravel"></i></a>
                      <a href="https://php.net" target="_blank" rel="noopener noreferrer" title="PHP"><i class="fa-brands fa-php"></i></a>
                      <a href="https://www.mysql.com/" target="_blank" rel="noopener noreferrer" title="MySQL"><i class="fa-solid fa-database"></i></a>
                      @php
                          $primaryAi = \App\Models\AiProvider::where('is_primary', true)->first() ?? \App\Models\AiProvider::where('status', 'active')->first();
                          $slug = 'openai';
                          $title = 'OpenAI';
                          $link = 'https://openai.com';
                          if ($primaryAi) {
                              $name = strtolower($primaryAi->name);
                              if (str_contains($name, 'openai')) { $slug = 'openai'; $title = 'OpenAI'; $link = 'https://openai.com'; }
                              elseif (str_contains($name, 'gemini')) { $slug = 'googlegemini'; $title = 'Gemini'; $link = 'https://deepmind.google/technologies/gemini/'; }
                              elseif (str_contains($name, 'cohere')) { $slug = 'cohere'; $title = 'Cohere'; $link = 'https://cohere.com'; }
                              elseif (str_contains($name, 'anthropic')) { $slug = 'anthropic'; $title = 'Anthropic'; $link = 'https://anthropic.com'; }
                              elseif (str_contains($name, 'groq')) { $slug = 'groq'; $title = 'Groq'; $link = 'https://groq.com'; }
                          }
                      @endphp
                      <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" title="{{ $title }}">
                          <i class="fa-solid fa-robot"></i>
                      </a>
                      <a href="https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API" target="_blank" rel="noopener noreferrer" title="Web Speech API"><i class="fa-solid fa-microphone"></i></a>
                   </div>
                </div>

                @php
                   $previewReadiness = number_format(\App\Models\Score::avg('overall_readiness_score') ?? 85, 0);
                   $previewInterviews = number_format(\App\Models\InterviewSession::count() ?: 6);
                   $previewClarity = number_format(\App\Models\Score::avg('clarity_score') ?? 92, 0);
                   $previewGrammar = number_format(\App\Models\Score::avg('grammar_score') ?? 95, 0);
                @endphp
                <div class="row justify-content-center mt-3 mb-3">
                  <div class="col-lg-12 adi">
                     <div class="ui-showcase">
                        <div class="ui-device ui-device-mobile" aria-label="Mobile UI preview">
                           <div class="ui-device-bar">
                              <span class="ui-device-dot" style="background:#ff5f57"></span>
                              <span class="ui-device-dot" style="background:#ffbd2e"></span>
                              <span class="ui-device-dot" style="background:#28c840"></span>
                           </div>
                           <div class="ui-dashboard">
                              <div class="ui-stat-grid">
                                 <div class="ui-stat">
                                    <div class="ui-stat-value accent">{{ $previewReadiness }}%</div>
                                    <div class="ui-stat-label">PH Readiness</div>
                                    <div class="ui-stat-note"><i class="fa-solid fa-caret-up"></i> Avg</div>
                                 </div>
                                 <div class="ui-stat">
                                    <div class="ui-stat-value">{{ $previewInterviews }}</div>
                                    <div class="ui-stat-label">Interviews Done</div>
                                 </div>
                                 <div class="ui-stat">
                                    <div class="ui-stat-value">{{ $previewClarity }}%</div>
                                    <div class="ui-stat-label">Clarity Score</div>
                                 </div>
                                 <div class="ui-stat">
                                    <div class="ui-stat-value">{{ $previewGrammar }}%</div>
                                    <div class="ui-stat-label">Grammar Score</div>
                                 </div>
                              </div>
                              <div class="ui-panel-grid">
                                 <div class="ui-panel">
                                    <div class="ui-panel-title"><i class="fa-solid fa-chart-bar me-1"></i>Performance Trend</div>
                                    <div class="ui-bars" aria-hidden="true">
                                       <span style="height:45%"></span><span style="height:55%"></span><span style="height:60%"></span><span style="height:70%"></span><span style="height:75%"></span><span style="height:85%"></span><span style="height:92%"></span>
                                    </div>
                                    <div class="ui-axis"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span></div>
                                 </div>
                                 <div class="ui-panel ui-chat">
                                    <div class="ui-panel-title"><span class="ui-pulse"></span>AI Feedback</div>
                                    <div class="ui-bubble user">Tell me about a challenge you faced.</div>
                                    <div class="ui-bubble ai"><strong>Good STAR structure for a Philippine interview.</strong> Add the specific result or customer impact to make it stronger.</div>
                                 </div>
                              </div>
                           </div>
                        </div>

                        <div class="ui-device ui-device-desktop" aria-label="Desktop UI preview">
                           <div class="ui-device-bar">
                              <span class="ui-device-dot" style="background:#ff5f57"></span>
                              <span class="ui-device-dot" style="background:#ffbd2e"></span>
                              <span class="ui-device-dot" style="background:#28c840"></span>
                              <span class="ui-device-title">speakready.ai/dashboard</span>
                           </div>
                           <div class="ui-dashboard ui-dashboard-desktop">
                              <div class="ui-sidebar">
                                 <div class="ui-side-title">Interview Hub</div>
                                 <button class="ui-side-item active" type="button"><i class="fa-solid fa-chart-pie"></i> Analytics</button>
                                 <button class="ui-side-item" type="button"><i class="fa-solid fa-video"></i> Sessions</button>
                                 <button class="ui-side-item" type="button"><i class="fa-solid fa-comment-medical"></i> Feedback</button>
                                 <button class="ui-side-item" type="button"><i class="fa-solid fa-graduation-cap"></i> Learning</button>
                              </div>
                              <div class="ui-main">
                                 <div class="ui-main-head">
                                    <div>
                                       <h3 class="ui-main-title">Readiness Dashboard</h3>
                                       <p class="ui-main-subtitle">Live interview analytics, progress, and AI coaching feedback.</p>
                                    </div>
                                    <span class="ui-main-chip">Desktop UI</span>
                                 </div>
                                 <div class="ui-stat-grid">
                                    <div class="ui-stat">
                                       <div class="ui-stat-value accent">{{ $previewReadiness }}%</div>
                                       <div class="ui-stat-label">PH Readiness</div>
                                       <div class="ui-stat-note"><i class="fa-solid fa-caret-up"></i> Avg</div>
                                    </div>
                                    <div class="ui-stat">
                                       <div class="ui-stat-value">{{ $previewInterviews }}</div>
                                       <div class="ui-stat-label">Interviews Done</div>
                                    </div>
                                    <div class="ui-stat">
                                       <div class="ui-stat-value">{{ $previewClarity }}%</div>
                                       <div class="ui-stat-label">Clarity Score</div>
                                    </div>
                                    <div class="ui-stat">
                                       <div class="ui-stat-value">{{ $previewGrammar }}%</div>
                                       <div class="ui-stat-label">Grammar Score</div>
                                    </div>
                                 </div>
                                 <div class="ui-panel-grid">
                                    <div class="ui-panel">
                                       <div class="ui-panel-title"><i class="fa-solid fa-chart-bar me-1"></i>Performance Trend</div>
                                       <div class="ui-bars" aria-hidden="true">
                                          <span style="height:45%"></span><span style="height:55%"></span><span style="height:60%"></span><span style="height:70%"></span><span style="height:75%"></span><span style="height:85%"></span><span style="height:92%"></span>
                                       </div>
                                       <div class="ui-axis"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span></div>
                                    </div>
                                    <div class="ui-panel ui-chat">
                                       <div class="ui-panel-title"><span class="ui-pulse"></span>AI Feedback</div>
                                       <div class="ui-bubble user">Tell me about a challenge you faced.</div>
                                       <div class="ui-bubble ai"><strong>Good STAR structure for a Philippine interview.</strong> Add the specific result or customer impact to make it stronger.</div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- ABOUT THE SYSTEM & SYSTEM STATS -->
         <section id="about" class="sp position-relative" style="background:var(--bg2)">
            <div class="container position-relative" style="z-index:1">
               <div class="landing-section-heading mb-5 rv">
                  <span class="slbl">About the System</span>
                  <h2 class="stitle">Empowering you to <span class="gt">shine in interviews</span></h2>
               </div>
               <div class="row align-items-center g-5">
                  <div class="col-lg-6 rv">
                     <p style="font-size:1.05rem;color:var(--tx2);margin-bottom:20px;">SpeakReady AI is an advanced, intelligent platform designed to help you prepare for Philippine interview scenarios, including job, BPO, IT, fresh graduate, scholarship, and college admission interviews. It provides immediate, evidence-linked feedback on answer quality and optional, non-scoring delivery coaching to reduce interview anxiety and make practice more focused.</p>

                     <h4 class="fs-5 mb-3 mt-4">Target Users</h4>
                     <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-user-graduate me-2"></i>Students</span>
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-graduation-cap me-2"></i>Fresh Graduates</span>
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-briefcase me-2"></i>Job Seekers</span>
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-award me-2"></i>Scholarship Applicants</span>
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-university me-2"></i>College Applicants</span>
                     </div>
                  </div>
                  <div class="col-lg-6 rv" style="transition-delay:.1s">
                     <!-- STATISTICS -->
                     <div class="row g-3 text-center">
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:var(--pur);">{{ \App\Models\User::count() }}</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Registered Users</div>
                           </div>
                        </div>
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:#34d399;">{{ \App\Models\InterviewSession::count() }}</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Interview Sessions</div>
                           </div>
                        </div>
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:#f59e0b;">{{ \App\Models\Question::count() }}</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Questions Available</div>
                           </div>
                        </div>
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:#3b82f6;">{{ \App\Models\Feedback::count() }}</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">AI Feedback Generated</div>
                           </div>
                        </div>
                        <div class="col-12 mt-3">
                           <div class="gc p-4">
                              <div class="d-flex justify-content-center align-items-center gap-2">
                                <div class="pnum" style="font-size:3rem; color:var(--pur);"><span class="counter">{{ number_format(\App\Models\Score::avg('overall_readiness_score') ?? 0, 0) }}</span>%</div>
                                <div class="text-start plbl text-uppercase" style="font-size:0.9rem; letter-spacing:1px;">Success<br>Rate</div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- CORE FEATURES -->
         <section id="features" class="sp">
            <div class="container">
               <div class="text-center mb-5 rv">
                  <span class="slbl">Core Features</span>
                  <h2 class="stitle">Everything you need to <span class="gt">succeed</span></h2>
               </div>
               <div class="row g-4">
                  <div class="col-md-3 col-sm-6 rv">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(168,85,247,.15);color:var(--pur)"><i class="fa-solid fa-gauge-high fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Dashboard Overview</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Monitor readiness scores, recent sessions, learning progress, and AI feedback summaries from one home base.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.05s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(52,211,153,.15);color:#34d399"><i class="fa-solid fa-microphone-lines fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Philippine AI Mock Interviews</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Practice with a realistic AI interviewer using role, category, difficulty, focus, and timed question settings.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(168,85,247,.15);color:#a855f7"><i class="fa-solid fa-file-lines fa-lg"></i></div>
                         <h3 class="fs-6 fw-bold mb-2">Job Evidence Mapping</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Compare your resume and role details to focus practice on the skills a job asks for.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.15s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(168,85,247,.15);color:#a855f7"><i class="fa-solid fa-ear-listen fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Voice Rehearsal Studio</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Improve pacing, clarity, delivery stability, and filler-word control without treating speaking style as personality.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(239,68,68,.15);color:#ef4444"><i class="fa-solid fa-book-open-reader fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Interview Modules</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Study structured modules with chapters, resources, quizzes, and practice activities tied to interview skills.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.25s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(236,72,153,.15);color:#ec4899"><i class="fa-solid fa-gamepad fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Learning Games</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Complete challenge paths with levels, energy, lives, target tones, banned words, and score goals.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(245,158,11,.15);color:#f59e0b"><i class="fa-solid fa-robot fa-lg"></i></div>
                         <h3 class="fs-6 fw-bold mb-2">AI Practice Coach</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Get focused prep guidance, score explanations, and grounded advice without invented achievements.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.35s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(16,185,129,.15);color:#10b981"><i class="fa-solid fa-clipboard-check fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Feedback Center</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">See evidence-linked rubrics, score confidence, fact-grounded revision templates, and targeted follow-ups.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.4s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(59,130,246,.15);color:#3b82f6"><i class="fa-solid fa-chart-line fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Progress Tracking</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Visualize readiness, STAR structure, skill breakdowns, learning progress, and voice rehearsal growth.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.45s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(6,182,212,.15);color:#06b6d4"><i class="fa-solid fa-folder-open fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Reports &amp; Sharing</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Print detailed reviews and create expiring, password-protected links with reviewer permissions.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.5s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(99,102,241,.15);color:#6366f1"><i class="fa-solid fa-network-wired fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Skill Trees</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Earn leadership, communication, technical, and problem-solving XP, then unlock perks as you improve.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.55s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(234,179,8,.15);color:#eab308"><i class="fa-solid fa-trophy fa-lg"></i></div>
                         <h3 class="fs-6 fw-bold mb-2">Personal Mastery</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Compare only against your own assessment baseline, personal best, and competency growth.</p>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- HOW IT WORKS -->
         <section id="how" class="sp" style="background:var(--bg3)">
            <div class="container">
               <div class="landing-section-heading mb-5 rv">
                  <span class="slbl">How It Works</span>
                  <h2 class="stitle">Your journey to <span class="gt">Philippine interview mastery</span></h2>
               </div>

               <div class="row g-4 justify-content-center">
                  <div class="col-md-4 col-sm-6 rv">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">1</div>
                        <h3 class="fs-5 fw-semibold mb-2">Create an Account</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Join the community and access your personalized dashboard.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">2</div>
                        <h3 class="fs-5 fw-semibold mb-2">Configure Your Setup</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Choose your target role, difficulty, and Philippine interview scenario.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">3</div>
                        <h3 class="fs-5 fw-semibold mb-2">Take a Philippine Mock Interview</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Face our interactive AI avatar with Philippine HR, BPO, IT, and fresh graduate questions.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">4</div>
                        <h3 class="fs-5 fw-semibold mb-2">Review AI Feedback</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Get instant, actionable evaluations on your performance.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.4s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">5</div>
                        <h3 class="fs-5 fw-semibold mb-2">Train & Rehearse</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Refine your skills using Voice Rehearsal and the AI Coach.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.5s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">6</div>
                        <h3 class="fs-5 fw-semibold mb-2">Track Your Progress</h3>
                         <p style="font-size:.875rem;color:var(--tx2)">Monitor competency growth, real interview outcomes, and your personal assessment baseline.</p>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- INTERVIEW CATEGORIES -->
         <section id="benefits" class="sp position-relative">
            <div class="aur aur-b" style="top:50%;right:-200px;transform:translateY(-50%)"></div>
            <div class="container position-relative" style="z-index:1">
               <div class="row justify-content-center">
                  <div class="col-lg-10 rv">
                     <div class="landing-section-heading mb-4">
                        <span class="slbl">Interview Categories</span>
                        <h2 class="stitle">Tailored to your <span class="gt">goals</span></h2>
                     </div>
                     <div class="row g-3">
                        <div class="col-sm-6">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid var(--pur);">
                              <div style="font-size:2rem; margin-bottom:15px; color:var(--pur)"><i class="fa-solid fa-briefcase"></i></div>
                              <h4 class="fs-5 fw-bold">Job Interview</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Practice employment interviews across various industries.</p>
                           </div>
                        </div>
                        <div class="col-sm-6">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #34d399;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#34d399"><i class="fa-solid fa-award"></i></div>
                              <h4 class="fs-5 fw-bold">Scholarship Interview</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Prepare for rigorous scholarship and grant applications.</p>
                           </div>
                        </div>
                        <div class="col-sm-6">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #f59e0b;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#f59e0b"><i class="fa-solid fa-university"></i></div>
                              <h4 class="fs-5 fw-bold">College Admission</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Improve admission interview performance for top universities.</p>
                           </div>
                        </div>
                        <div class="col-sm-6">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #3b82f6;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#3b82f6"><i class="fa-solid fa-laptop-code"></i></div>
                              <h4 class="fs-5 fw-bold">IT/Programming</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Practice technical, coding, and system design interviews.</p>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- DEVELOPERS -->
         <section id="developers" class="sp" style="background:var(--bg2)">
            <div class="container">
               <div class="text-center mb-5 rv">
                  <span class="slbl">Developers</span>
                  <h2 class="stitle">Meet Our <span class="gt">Team</span></h2>
               </div>
               <div class="row g-3 justify-content-center">
                  <div class="col-md-4 rv">
                     <div class="gc p-4 h-100 text-center d-flex flex-column align-items-center justify-content-center">
                        <img src="{{ asset('img/dev1.png') }}" alt="Developer" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid var(--pur);">
                        <h6 class="fw-bold mb-1">Jonh Rogiel M. Tumanda</h6>
                        <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Lead Programmer</p>
                        <p style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Core Code, Databases, and APIs.</p>
                     </div>
                  </div>
                  <div class="col-md-4 rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center d-flex flex-column align-items-center justify-content-center">
                        <img src="{{ asset('img/dev2.png') }}" alt="Developer" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #34d399;">
                        <h6 class="fw-bold mb-1">Karyl G. Gesto</h6>
                        <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Manuscript Editor</p>
                        <p style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Technical Writing, Documentation, and Compliance.</p>
                     </div>
                  </div>
                  <div class="col-md-4 rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center d-flex flex-column align-items-center justify-content-center">
                        <img src="{{ asset('img/dev3.png') }}" alt="Developer" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #f59e0b;">
                        <h6 class="fw-bold mb-1">Eva Mae C. Cabilic</h6>
                        <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">QA Tester</p>
                        <p style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Bug Hunting, Test Cases, and UX Stability.</p>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- DEMO PREVIEW GALLERY -->
         <section id="demo-preview" class="sp position-relative">
            <div class="aur aur-a" style="top:50%;left:50%;transform:translate(-50%,-50%)"></div>
            <div class="container position-relative" style="z-index:1">
               <div class="text-center mb-5 rv">
                  <span class="slbl">Demo Preview</span>
                  <h2 class="stitle">Inside <span class="gt">SpeakReady AI</span></h2>
               </div>

               <div class="row justify-content-center">
                   <div class="col-lg-10">
                       <div class="gc p-2">
                           <div class="swiper demoSwiper rounded" style="overflow:hidden">
                               <div class="swiper-wrapper">
                                   <!-- Slide 1: Overview -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-gauge-high fa-4x mb-4" style="color:var(--pur)"></i>
                                           <h3 class="fs-3 fw-bold">Overview</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Track all your Philippine interview sessions and readiness.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 2: Mock Interview -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-microphone-lines fa-4x mb-4" style="color:#34d399"></i>
                                           <h3 class="fs-3 fw-bold">Philippine Mock Interview</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Interactive AI avatar asking Philippine HR, BPO, IT, and fresh graduate questions.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 3: Voice Rehearsal -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-ear-listen fa-4x mb-4" style="color:#a855f7"></i>
                                           <h3 class="fs-3 fw-bold">Voice Rehearsal</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Practice your enunciation and pacing with real-time feedback.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 4: Learning Lab -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-book-open fa-4x mb-4" style="color:#ef4444"></i>
                                           <h3 class="fs-3 fw-bold">Learning Lab</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Access curated resources and tutorials for Philippine interview scenarios.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 5: AI Coach -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-robot fa-4x mb-4" style="color:#f59e0b"></i>
                                           <h3 class="fs-3 fw-bold">AI Coach</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Get personalized advice and strategies from your AI mentor.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 6: Progress Tracking -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-chart-line fa-4x mb-4" style="color:#3b82f6"></i>
                                           <h3 class="fs-3 fw-bold">Progress Tracking</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Visual charts tracking your improvement over time.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 7: Feedback Center -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-clipboard-check fa-4x mb-4" style="color:#10b981"></i>
                                           <h3 class="fs-3 fw-bold">Feedback Center</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Actionable insights on content, tone, and delivery.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 8: Reports -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-file-invoice fa-4x mb-4" style="color:#6366f1"></i>
                                           <h3 class="fs-3 fw-bold">Reports</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Detailed summaries and exportable reports of your performance.</p>
                                       </div>
                                   </div>
                                    <!-- Slide 9: Personal Mastery -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-trophy fa-4x mb-4" style="color:#eab308"></i>
                                            <h3 class="fs-3 fw-bold">Personal Mastery</h3>
                                            <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Track your own baseline, personal best, and score-eligible assessment growth.</p>
                                       </div>
                                   </div>
                               </div>
                               <div class="swiper-pagination"></div>
                               <div class="swiper-button-next" style="color:var(--pur)"></div>
                               <div class="swiper-button-prev" style="color:var(--pur)"></div>
                           </div>
                       </div>
                   </div>
               </div>
            </div>
         </section>

         <!-- FAQ -->
         <section id="faq" class="sp" style="background:var(--bg2)">
            <div class="container">
               <div class="text-center mb-5 rv">
                  <span class="slbl">FAQ</span>
                  <h2 class="stitle">Common <span class="gt">Questions</span></h2>
               </div>
               <div class="row justify-content-center rv">
                  <div class="col-lg-8">
                     <div class="accordion acco" id="faqAcc">
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#f1">What is SpeakReady AI?</button></h2>
                           <div id="f1" class="accordion-collapse collapse show" data-bs-parent="#faqAcc">
                              <div class="accordion-body">SpeakReady AI is an intelligent mock interview platform designed to help students, job seekers, and applicants practice their interview skills using advanced AI simulations.</div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f2">How does AI feedback work?</button></h2>
                           <div id="f2" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                               <div class="accordion-body">SpeakReady evaluates answer relevance, clarity, professionalism, applicable STAR evidence, and job evidence using a versioned rubric. Delivery signals and optional body-language prompts are coaching aids, do not affect readiness scores, and do not infer confidence, honesty, or personality.</div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f3">Is my data secure?</button></h2>
                           <div id="f3" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                               <div class="accordion-body">Interview records are private by default. When you choose to share a review, you can set an expiry, optional password, reviewer permissions, and hide sensitive identity or application context.</div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f4">Can I practice multiple interview types?</button></h2>
                           <div id="f4" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                              <div class="accordion-body">Absolutely. You can choose from Job Interviews, Scholarship Interviews, College Admissions, or specific IT/Programming technical interviews.</div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f5">Is the system free to use?</button></h2>
                           <div id="f5" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                              <div class="accordion-body">SpeakReady AI offers a free basic tier so you can start practicing immediately. We also offer premium plans with unlimited sessions and advanced analytics.</div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- CONTACT US -->
         <section id="contact" class="sp position-relative">
            <div class="container position-relative" style="z-index:1">
               <div class="landing-section-heading mb-5 rv">
                  <span class="slbl">Contact Us</span>
                  <h2 class="stitle">Get in <span class="gt">Touch</span></h2>
               </div>
               <div class="row g-5 justify-content-center">
                  <div class="col-lg-5 rv">
                     <p style="font-size:1.05rem;color:var(--tx2);margin-bottom:30px">Have questions or need support? We're here to help you on your journey to interview success.</p>

                     <div class="d-flex flex-column gap-4">
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-envelope" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Email Address</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">{{ \App\Models\User::where('is_admin', 1)->value('email') ?? 'admin@speakready.ai' }}</p>
                             </div>
                         </div>
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-phone" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Contact Number</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">09066544727</p>
                             </div>
                         </div>
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-location-dot" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Location</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">Pinut-an, San Ricardo, Southern Leyte, Philippines</p>
                             </div>
                         </div>
                     </div>
                  </div>
                  <div class="col-lg-5 rv" style="transition-delay:.1s">
                     <div class="gc p-4 p-md-5 h-100">
                         @if(session('contact_success'))
                             <script>
                                 window.onload = function() {
                                     alert("Success! " + "{{ session('contact_success') }}");
                                 };
                             </script>
                         @endif
                         @if(session('contact_error'))
                             <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; border-radius: 12px; padding: 15px;">
                                 <i class="fa-solid fa-circle-xmark fs-5 me-3"></i>
                                 <div>
                                     <strong>Error:</strong> {{ session('contact_error') }}
                                 </div>
                                 <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" style="filter: brightness(0.5);"></button>
                             </div>
                         @endif
                         <form action="{{ route('contact.send') }}" method="POST">
                             @csrf
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Name</label>
                                 <input type="text" name="name" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your Full Name" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Email</label>
                                 <input type="email" name="email" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="you@example.com" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Subject</label>
                                 <input type="text" name="subject" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="How can we help?" required>
                             </div>
                             <div class="mb-4">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Message</label>
                                 <textarea name="message" class="form-control" rows="4" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your message here..." required></textarea>
                             </div>
                             <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">Send Message</button>
                         </form>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- FOOTER -->
         <style>
            #foot {
                background: linear-gradient(to bottom, var(--bg2), var(--bg3));
                position: relative;
                overflow: hidden;
            }
            #foot::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 1px;
                background: linear-gradient(90deg, transparent, var(--pur), transparent);
                opacity: 0.3;
            }
            .footer-heading {
                font-size: 0.95rem;
                font-weight: 700;
                color: var(--tx);
                margin-bottom: 1.25rem;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }
            .footer-links {
                margin: 0;
                padding: 0;
            }
            .footer-links li {
                margin-bottom: 0.75rem;
            }
            .footer-links a {
                color: var(--tx2);
                text-decoration: none;
                font-size: 0.9rem;
                transition: all 0.2s ease;
                display: inline-block;
            }
            .footer-links a:hover {
                color: var(--pur);
                transform: translateX(4px);
            }
            .footer-social-link {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                background: var(--bg);
                border: 1px solid var(--bd);
                border-radius: 50%;
                color: var(--tx2);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                text-decoration: none;
                font-size: 1rem;
            }
            .footer-social-link:hover {
                background: var(--pur);
                color: #fff;
                border-color: var(--pur);
                transform: translateY(-4px);
                box-shadow: 0 6px 15px rgba(124, 58, 237, 0.35);
            }
            .footer-bottom {
                border-top: 1px solid var(--bd);
                padding-top: 1.5rem;
                padding-bottom: 1.5rem;
                margin-top: 2rem;
            }
            .footer-newsletter input:focus {
                border-color: var(--pur) !important;
                box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15) !important;
            }
            .footer-newsletter-btn {
                background: linear-gradient(135deg, var(--pur), #9333ea);
                color: #fff;
                border: none;
                border-radius: 10px;
                transition: all 0.2s;
            }
            .footer-newsletter-btn:hover {
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
                color: #fff;
            }
         </style>
         <footer id="foot">
            <div class="container pt-5">
               <div class="row g-5 mb-5">
                  <div class="col-lg-4 pe-lg-5">
                     <a class="d-flex align-items-center gap-2 mb-3 text-decoration-none" href="#">
                        <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="width:32px; height:32px; background:transparent; padding:0;">
                        <span style="font-size:1.3rem;font-weight:800;letter-spacing:-0.5px;color:var(--tx)">SpeakReady AI</span>
                     </a>
                     <p style="font-size:.95rem;color:var(--tx2);line-height:1.7;margin-bottom:1.75rem">Your personal Philippine interview coach. Practice smarter, interview better, and secure your dream opportunity with confidence.</p>
                     <div class="d-flex gap-3">
                         <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                         <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="Twitter"><i class="fa-brands fa-twitter"></i></a>
                         <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                         <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="GitHub"><i class="fa-brands fa-github"></i></a>
                     </div>
                  </div>
                  <div class="col-6 col-md-2 col-lg-2">
                     <h5 class="footer-heading">Company</h5>
                     <ul class="list-unstyled footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how">How It Works</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="#faq">FAQ</a></li>
                     </ul>
                  </div>
                  <div class="col-6 col-md-3 col-lg-2">
                     <h5 class="footer-heading">Platform</h5>
                     <ul class="list-unstyled footer-links">
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('login')">Log In</a></li>
                        <li><a href="#" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('signup')">Register</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                     </ul>
                  </div>
                  <div class="col-12 col-md-7 col-lg-4">
                     <h5 class="footer-heading">Stay Updated</h5>
                     <p style="font-size:.9rem;color:var(--tx2);line-height:1.6;margin-bottom:1.25rem">Get the latest interview tips and platform updates directly in your inbox.</p>
                     <form class="footer-newsletter d-flex gap-2" onsubmit="event.preventDefault(); alert('Thanks for subscribing!');">
                         <input type="email" placeholder="Enter your email" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:12px 15px;border-radius:10px;font-size:0.95rem;box-shadow:none;" required>
                         <button type="submit" class="btn footer-newsletter-btn fw-semibold px-3"><i class="fa-solid fa-paper-plane"></i></button>
                     </form>
                  </div>
               </div>
               <div class="footer-bottom d-flex align-items-center justify-content-between flex-wrap gap-3">
                  <p style="font-size:.85rem;color:var(--tx3);margin:0">&copy; {{ date('Y') }} SpeakReady AI. All rights reserved.</p>
                  <div class="d-flex gap-3" style="font-size:.85rem;">
                      <a href="#" style="color:var(--tx3);text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='var(--pur)'" onmouseout="this.style.color='var(--tx3)'">Security</a>
                      <span style="color:var(--bd)">|</span>
                      <a href="#" style="color:var(--tx3);text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='var(--pur)'" onmouseout="this.style.color='var(--tx3)'">Cookie Preferences</a>
                  </div>
               </div>
            </div>
         </footer>
      </div>
      <!-- /landing -->



      <!-- ======================== LOGIN MODAL ======================== -->
      <div class="modal fade" tabindex="-1" id="lofc" aria-labelledby="authModalTitle" aria-hidden="true" data-bs-backdrop="false">
         <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:var(--sf);color:var(--tx);border:1px solid var(--bd);border-radius:18px;box-shadow:0 24px 80px rgba(0,0,0,.35);overflow:hidden;">
               <div class="modal-header" style="border-bottom:1px solid var(--bd);">
                  <div class="d-flex align-items-center gap-2">
                     <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="width:30px;height:30px;background: transparent; padding: 0;">
                     <h5 class="modal-title mb-0" id="authModalTitle">SpeakReady AI</h5>
                  </div>
                  <button type="button" class="btn-close auth-modal-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body p-4">
            <div class="tab-switch"><button class="tab-sw-btn on" id="tabLogin" onclick="swTab('login')">Log In</button><button class="tab-sw-btn" id="tabSignup" onclick="swTab('signup')">Register</button></div>
            <!-- Login -->
            <div id="fLogin" class="auth-panel">
               @if(session('success'))
                  <div style="background:rgba(52,211,153,0.1);color:#34d399;border:1px solid rgba(52,211,153,0.2);padding:10px;border-radius:12px;font-size:0.85rem;margin-bottom:15px;">
                     <i class="fa-solid fa-check-circle me-1"></i> {{ session('success') }}
                  </div>
               @endif
               @if($errors->has('account_inactive'))
                  <div class="err-msg" style="display:block; padding:12px; margin-bottom:15px; text-align:left; line-height: 1.4;">
                     <div class="mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first('account_inactive') }}</span></div>
                     <form action="{{ route('request.reactivation') }}" method="POST">
                        @csrf
                        <input type="hidden" name="email" value="{{ old('email') }}">
                        <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold" style="border-radius:8px; background: #f59e0b; border: none; color: #fff;">Request Reactivation</button>
                     </form>
                  </div>
               @endif
               <form id="loginForm" action="{{ route('login') }}" method="POST">
                  @csrf
                  @if($errors->any() && !$errors->has('account_inactive') && !old('name'))
                     <div class="err-msg" style="display:block;"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first() }}</span></div>
                  @endif
                  <label class="olbl"><i class="fa-regular fa-envelope me-1"></i>Email address</label>
                  <input class="oinp" type="email" name="email" id="loginEmail" placeholder="you@example.com" required value="{{ old('email') }}">
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Password</label>
                  <div class="password-field mb-3">
                     <input class="oinp" type="password" name="password" id="loginPass" placeholder="********" required>
                     <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('loginPass', this)" aria-label="Show password">
                        <i class="fa-solid fa-eye-slash"></i>
                     </button>
                  </div>
                  <div class="form-check mb-3" style="margin-top:-4px;">
                     <input type="hidden" name="remember" value="0">
                     <input class="form-check-input" type="checkbox" name="remember" value="1" id="loginRemember" checked>
                     <label class="form-check-label" for="loginRemember" style="font-size:.8rem;color:var(--tx2);">Keep me signed in on this device</label>
                  </div>
                  <div class="text-end mb-3" style="margin-top:-8px"><a href="#" style="font-size:.8rem;color:var(--pur)">Forgot password?</a></div>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="loginBtn">Log In <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or continue with</div>
               <a href="{{ route('auth.google') }}" class="oauth" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Continue with Google</a>
            </div>
            <!-- Sign Up -->
            <div id="fSignup" class="auth-panel" style="display:none">
               <form action="{{ route('register') }}" method="POST">
                  @csrf
                  @if($errors->any() && old('name'))
                     <div class="err-msg" style="display:block;"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first() }}</span></div>
                  @endif
                  <label class="olbl"><i class="fa-regular fa-user me-1"></i>Full name</label>
                  <input class="oinp" type="text" name="name" id="signupName" placeholder="John Doe" required value="{{ old('name') }}">
                  <label class="olbl"><i class="fa-regular fa-envelope me-1"></i>Email address</label>
                  <input class="oinp" type="email" name="email" id="signupEmail" placeholder="you@example.com" required>
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Password</label>
                  <div class="password-field mb-3">
                     <input class="oinp" type="password" name="password" id="signupPass" placeholder="Min. 8 characters" required>
                     <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('signupPass', this)" aria-label="Show password">
                        <i class="fa-solid fa-eye-slash"></i>
                     </button>
                  </div>
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Confirm Password</label>
                  <div class="password-field mb-3">
                     <input class="oinp" type="password" name="password_confirmation" id="signupPassConfirm" placeholder="Confirm your password" required>
                     <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('signupPassConfirm', this)" aria-label="Show password">
                        <i class="fa-solid fa-eye-slash"></i>
                     </button>
                  </div>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="signupBtn">Create Free Account <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or sign up with</div>
               <a href="{{ route('auth.google') }}" class="oauth" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Continue with Google</a>
            </div>
               </div>
            </div>
         </div>
      </div>

      <!-- ===== PWA INSTALL PROMPT ===== -->
      <div id="pwa-install-prompt">
         <h5>Install SpeakReady AI</h5>
         <p>Do you want to install this app for a better and faster experience?</p>
         <div class="pwa-btn-wrap">
            <button id="pwa-btn-no" class="pwa-btn-no">No</button>
            <button id="pwa-btn-yes" class="pwa-btn-yes">Yes</button>
         </div>
      </div>

      <div class="guest-tour-overlay" id="guestAuthTourOverlay" aria-hidden="true"></div>
      <div class="guest-tour-card" id="guestAuthTourCard" role="dialog" aria-modal="true" aria-labelledby="guestAuthTourTitle">
         <h6 id="guestAuthTourTitle">Click Get Started Free</h6>
         <p id="guestAuthTourText">Start here to open Login/Register and create your free practice account.</p>
         <div class="guest-tour-actions">
            <button type="button" class="guest-tour-skip" id="guestAuthTourSkip">Skip</button>
            <button type="button" class="guest-tour-next" id="guestAuthTourNext">Got it</button>
         </div>
      </div>

<!-- ======================== SCRIPTS ======================== -->
      <!-- jQuery -->
      <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
      <!-- Bootstrap 5 -->
      <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
      <!-- AOS -->
      <script src="{{ asset('js/aos.js') }}"></script>
      <!-- Swiper -->
      <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
      <script src="{{ asset('js/chart.umd.min.js') }}"></script>
      <!-- Magnific -->
      <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
      <!-- Counter Up and Waypoints -->
      <script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/Counter-Up/1.0.0/jquery.counterup.min.js"></script>

      <script src="{{ asset('js/main.js?v=6') }}"></script>
      @if($errors->any())
      <script>
         document.addEventListener('DOMContentLoaded', function() {
            var authModal = document.getElementById('lofc');
            var bsModal = new bootstrap.Modal(authModal);
            bsModal.show();
            @if(old('name'))
               swTab('signup');
            @endif
         });
      </script>
      @endif

      <script>
         // Initialize CounterUp and Swiper when document is ready
         $(document).ready(function() {
             if($.fn.counterUp) {
                 $('.counter').counterUp({
                     delay: 10,
                     time: 1500
                 });
             }

             if(typeof Swiper !== 'undefined') {
                 var swiper = new Swiper(".demoSwiper", {
                     slidesPerView: 1,
                     spaceBetween: 30,
                     loop: true,
                     autoplay: {
                         delay: 3000,
                         disableOnInteraction: false,
                     },
                     pagination: {
                         el: ".swiper-pagination",
                         clickable: true,
                     },
                     navigation: {
                         nextEl: ".swiper-button-next",
                         prevEl: ".swiper-button-prev",
                     },
                 });
             }
         });
      </script>
      <script>
         document.addEventListener('DOMContentLoaded', function() {
            const startBtn = document.getElementById('guestGetStartedTourBtn');
            const authModal = document.getElementById('lofc');
            const overlay = document.getElementById('guestAuthTourOverlay');
            const card = document.getElementById('guestAuthTourCard');
            const title = document.getElementById('guestAuthTourTitle');
            const text = document.getElementById('guestAuthTourText');
            const next = document.getElementById('guestAuthTourNext');
            const skip = document.getElementById('guestAuthTourSkip');
            let tourActive = false;

            function positionTourCard(target) {
               if (!card || !target) return;
               const rect = target.getBoundingClientRect();
               const cardWidth = Math.min(300, window.innerWidth - 32);
               const left = Math.min(Math.max(16, rect.left + rect.width / 2 - cardWidth / 2), window.innerWidth - cardWidth - 16);
               const top = Math.min(rect.bottom + 12, window.innerHeight - 170);
               card.style.left = `${left}px`;
               card.style.top = `${top}px`;
            }

            function clearTourHighlight() {
               document.querySelectorAll('.guest-tour-highlight').forEach(el => el.classList.remove('guest-tour-highlight'));
            }

            function closeGuestTour() {
               tourActive = false;
               overlay?.classList.remove('show');
               overlay?.setAttribute('aria-hidden', 'true');
               if (card) card.style.display = 'none';
               clearTourHighlight();
            }

            function showGuestTourStep(target, heading, copy, buttonText) {
               if (!target || !overlay || !card) return;
               tourActive = true;
               clearTourHighlight();
               target.classList.add('guest-tour-highlight');
               title.textContent = heading;
               text.textContent = copy;
               next.textContent = buttonText;
               overlay.classList.add('show');
               overlay.setAttribute('aria-hidden', 'false');
               card.style.display = 'block';
               positionTourCard(target);
            }

            function showStartGuide() {
               showGuestTourStep(
                  startBtn,
                  'Click Get Started Free',
                  'Use this button to open Login/Register and begin your free interview practice.',
                  'Got it'
               );
            }

            function showAuthGuide() {
               const tabSwitch = authModal?.querySelector('.tab-switch');
               showGuestTourStep(
                  tabSwitch,
                  'Login or Register',
                  'Choose Register for a new account, or Log In if you already have one.',
                  'Done'
               );
            }

            startBtn?.addEventListener('click', function() {
               if (tourActive) {
                  sessionStorage.setItem('guest_get_started_tour_seen', 'true');
               }
            });

            authModal?.addEventListener('shown.bs.modal', function() {
               if (!sessionStorage.getItem('guest_get_started_tour_seen')) return;
               if (sessionStorage.getItem('guest_auth_modal_tour_seen')) return;
               sessionStorage.setItem('guest_auth_modal_tour_seen', 'true');
               window.setTimeout(showAuthGuide, 140);
            });

            next?.addEventListener('click', closeGuestTour);
            skip?.addEventListener('click', closeGuestTour);
            overlay?.addEventListener('click', closeGuestTour);

            window.addEventListener('resize', function() {
               if (!tourActive || !card || card.style.display === 'none') return;
               const target = document.querySelector('.guest-tour-highlight');
               positionTourCard(target);
            });

            window.setTimeout(function waitForSplash() {
               if (!startBtn || sessionStorage.getItem('guest_get_started_tour_seen')) return;
               if (document.body.classList.contains('guest-splash-pending')) {
                  window.setTimeout(waitForSplash, 500);
                  return;
               }
               showStartGuide();
            }, 900);
         });
      </script>
      <!-- PWA Service Worker Registration -->
      <script>
         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js').then(function(registration) {
                  console.log('ServiceWorker registration successful with scope: ', registration.scope);
               }, function(err) {
                  console.log('ServiceWorker registration failed: ', err);
               });
            });
         }

         // PWA Install Prompt Logic
         let deferredPrompt;
         window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (!localStorage.getItem('pwa_prompt_dismissed')) {
               queuePwaInstallPrompt();
            }
         });

         function queuePwaInstallPrompt() {
            const prompt = document.getElementById('pwa-install-prompt');
            if (!prompt) return;

            window.setTimeout(() => {
               if (document.body.classList.contains('guest-splash-pending')) {
                  queuePwaInstallPrompt();
                  return;
               }

               if (!localStorage.getItem('pwa_prompt_dismissed')) {
                  prompt.style.display = 'block';
               }
            }, 4200);
         }

         async function triggerInstall() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());

            if (isStandalone) {
                alert("This app has already been installed on your device.");
                return;
            }

            if (deferredPrompt) {
               deferredPrompt.prompt();
               const { outcome } = await deferredPrompt.userChoice;
               console.log(`User response to the install prompt: ${outcome}`);
               deferredPrompt = null;
               document.getElementById('pwa-install-prompt').style.display = 'none';
            } else {
               if (isIos) {
                   alert("To install on iOS, tap the 'Share' icon at the bottom of Safari and select 'Add to Home Screen'.");
               } else {
                   alert("This app has already been installed on your device.");
               }
            }
         }

         document.getElementById('pwa-btn-yes')?.addEventListener('click', triggerInstall);
         document.getElementById('heroInstallBtn')?.addEventListener('click', triggerInstall);

         document.getElementById('pwa-btn-no')?.addEventListener('click', () => {
            document.getElementById('pwa-install-prompt').style.display = 'none';
            localStorage.setItem('pwa_prompt_dismissed', 'true');
         });
      </script>

      <!-- LOGIN TRANSITION OVERLAY -->
      <style>
      #loginTransitionOverlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100vw;
          height: 100vh;
          background: var(--bg, #ffffff);
          z-index: 999999;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          opacity: 0;
          visibility: hidden;
          transition: opacity 0.3s ease, visibility 0.3s ease;
      }
      #loginTransitionOverlay.active {
          opacity: 1;
          visibility: visible;
      }
      .logo-loading-wrapper {
          position: relative;
          width: 120px;
          height: 120px;
          margin-bottom: 20px;
          display: flex;
          align-items: center;
          justify-content: center;
      }
      .logo-loading-circle {
          position: absolute;
          width: 100%;
          height: 100%;
          border-radius: 50%;
          border: 4px solid var(--bd, #e2e8f0);
          border-top: 4px solid var(--pur, #7c3aed);
          animation: spin 1s linear infinite;
      }
      .logo-loading-wrapper img {
          width: 70px;
          height: 70px;
          object-fit: contain;
          animation: pulse 1.5s ease-in-out infinite;
      }
      @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
      }
      @keyframes pulse {
          0% { transform: scale(0.9); opacity: 0.8; }
          50% { transform: scale(1.1); opacity: 1; }
          100% { transform: scale(0.9); opacity: 0.8; }
      }
      </style>

      <div id="loginTransitionOverlay">
          <div class="logo-loading-wrapper">
              <div class="logo-loading-circle"></div>
              <img src="{{ asset('img/logo.png') }}" alt="Loading...">
          </div>
          <h4 style="color:var(--tx); font-weight:600; font-size:1.2rem; letter-spacing:0.5px;">Authenticating...</h4>
          <p style="color:var(--tx3); font-size:0.9rem;">Please wait while we log you in</p>
      </div>

      <script>
          function showLoginTransition() {
              const overlay = document.getElementById('loginTransitionOverlay');
              if (overlay) overlay.classList.add('active');
          }

          document.addEventListener('DOMContentLoaded', function() {
              const loginForm = document.getElementById('loginForm');
              if (loginForm) {
                  loginForm.addEventListener('submit', function(e) {
                      if (this.checkValidity()) {
                          showLoginTransition();
                      }
                  });
              }
          });

          function togglePasswordVisibility(inputId, btn) {
             const input = document.getElementById(inputId);
             const icon = btn.querySelector('i');
             if (!input || !icon) return;
             if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                btn.setAttribute('aria-label', 'Hide password');
             } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                btn.setAttribute('aria-label', 'Show password');
             }
          }
      </script>
   </body>
</html>
