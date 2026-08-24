<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
      <meta name="theme-color" content="#f7fbff">
      <title>@yield('title', 'SpeakReady AI - Practice Smarter. Interview Better.')</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://accounts.google.com">
      <link rel="dns-prefetch" href="//accounts.google.com">
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
      <link rel="stylesheet" href="{{ asset('css/mobile/style.css?v=7') }}" />
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

         #developers .developers-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
         }

         #developers .developer-card-wrap,
         #developers .developer-card {
            min-width: 0;
         }

         #developers .developer-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
         }

         #developers .developer-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
         }

         #developers .developer-card h6,
         #developers .developer-card p {
            max-width: 100%;
            overflow-wrap: anywhere;
         }

         @media (max-width: 767.98px) {
            #developers .developers-grid {
               gap: 8px;
            }

            #developers .developer-card {
               padding: 14px 8px !important;
            }

            #developers .developer-photo {
               width: clamp(72px, 24vw, 96px);
               height: clamp(72px, 24vw, 96px);
               margin-bottom: 10px !important;
               border-width: 3px !important;
            }

            #developers .developer-card h6 {
               font-size: clamp(0.66rem, 2.4vw, 0.78rem);
               line-height: 1.18;
            }

            #developers .developer-role {
               font-size: clamp(0.6rem, 2.2vw, 0.72rem) !important;
               line-height: 1.2;
               margin-bottom: 8px !important;
            }

            #developers .developer-bio {
               font-size: clamp(0.58rem, 2vw, 0.68rem) !important;
               line-height: 1.35 !important;
               margin-bottom: 0 !important;
            }
         }

         @media (max-width: 380px) {
            #developers .developers-grid {
               gap: 6px;
            }

            #developers .developer-card {
               padding: 10px 5px !important;
            }
         }

         #landing #faq .acco {
            display: grid;
            gap: 10px;
         }

         #landing #faq .acco .accordion-item {
            margin-bottom: 0;
            overflow: hidden;
            background: var(--sf);
            border: 1px solid var(--bd);
            border-radius: 8px !important;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
         }

         #landing #faq .acco .accordion-header {
            margin: 0;
         }

         #landing #faq .acco .accordion-button {
            min-height: 52px;
            padding: 14px 16px;
            gap: 12px;
            color: var(--tx) !important;
            background: var(--sf) !important;
            border: 0;
            border-radius: 8px !important;
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: 0;
            box-shadow: none !important;
         }

         #landing #faq .acco .accordion-button:not(.collapsed) {
            color: var(--tx) !important;
            background: rgba(59, 130, 246, 0.12) !important;
            border-bottom: 1px solid var(--bd);
            border-radius: 8px 8px 0 0 !important;
         }

         #landing #faq .acco .accordion-button::after {
            width: 0.9rem;
            height: 0.9rem;
            flex: 0 0 0.9rem;
            margin-left: auto;
            background-size: 0.9rem;
         }

         #landing #faq .acco .accordion-body {
            padding: 12px 16px 16px;
            color: var(--tx2) !important;
            background: var(--sf) !important;
            border-top: 0;
            font-size: 0.86rem;
            line-height: 1.58;
            text-align: justify;
            text-align-last: left;
         }

         html.lm #landing #faq .acco .accordion-item,
         html.lm #landing #faq .acco .accordion-body,
         html.lm #landing #faq .acco .accordion-button {
            background: #ffffff !important;
         }

         html.lm #landing #faq .acco .accordion-button:not(.collapsed) {
            background: #eaf2ff !important;
         }

         @media (max-width: 575.98px) {
            #landing #faq .row {
               --bs-gutter-x: 0;
            }

            #landing #faq .text-center.mb-5 {
               margin-bottom: 18px !important;
            }

            #landing #faq .acco {
               gap: 8px;
            }

            #landing #faq .acco .accordion-item {
               border-radius: 8px !important;
               box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
            }

            #landing #faq .acco .accordion-button {
               min-height: 46px;
               padding: 12px 14px;
               font-size: 0.78rem;
               line-height: 1.2;
            }

            #landing #faq .acco .accordion-body {
               padding: 10px 14px 14px;
               font-size: 0.76rem;
               line-height: 1.55;
            }
         }

         .back-to-top-btn {
            position: fixed;
            right: max(18px, env(safe-area-inset-right));
            bottom: max(22px, env(safe-area-inset-bottom));
            z-index: 1040;
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background: linear-gradient(135deg, var(--pur), #06b6d4);
            border: 0;
            border-radius: 8px;
            box-shadow: 0 16px 34px rgba(37, 99, 235, 0.3);
            cursor: grab;
            opacity: 0;
            visibility: hidden;
            transform: translateY(14px) scale(0.92);
            pointer-events: none;
            touch-action: none;
            transition: opacity 0.22s ease, visibility 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease;
            user-select: none;
         }

         .back-to-top-btn i {
            font-size: 1rem;
            line-height: 1;
         }

         .back-to-top-btn.is-visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
            pointer-events: auto;
            animation: backToTopFloat 2.4s ease-in-out infinite;
         }

         .back-to-top-btn:hover,
         .back-to-top-btn:focus-visible {
            color: #ffffff;
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 20px 42px rgba(37, 99, 235, 0.38);
            outline: 0;
         }

         .back-to-top-btn:focus-visible {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.22), 0 20px 42px rgba(37, 99, 235, 0.38);
         }

         .back-to-top-btn.is-dragging,
         .back-to-top-btn.is-dragging:hover,
         .back-to-top-btn.is-dragging:focus-visible {
            animation: none !important;
            cursor: grabbing;
            transform: translateY(0) scale(1);
            box-shadow: 0 22px 48px rgba(37, 99, 235, 0.42);
         }

         @keyframes backToTopFloat {
            0%, 100% {
               translate: 0 0;
            }
            50% {
               translate: 0 -6px;
            }
         }

         @media (max-width: 575.98px) {
            .back-to-top-btn {
               right: max(14px, env(safe-area-inset-right));
               bottom: max(78px, calc(env(safe-area-inset-bottom) + 70px));
               width: 42px;
               height: 42px;
            }
         }

         @media (prefers-reduced-motion: reduce) {
            .back-to-top-btn,
            .back-to-top-btn.is-visible {
               animation: none !important;
               transition: opacity 0.01ms linear, visibility 0.01ms linear;
            }
         }

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
               background: transparent;
               border: 0;
               box-shadow: none;
            }

            .sr-launch-mark::after {
               display: none;
            }

            .sr-launch-mark img {
               width: 100%;
               height: 100%;
               object-fit: contain;
               border-radius: 24px;
               filter: drop-shadow(0 12px 18px rgba(37, 99, 235, 0.24));
            }

            .sr-launch-kicker {
               margin: 0 0 9px;
               color: var(--pur);
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
            :root {
               --nav: 64px;
            }

            body {
               background:
                  radial-gradient(circle at 20% 0%, rgba(20, 184, 166, 0.12), transparent 32%),
                  radial-gradient(circle at 88% 8%, rgba(59, 130, 246, 0.14), transparent 30%),
                  var(--bg);
            }

            #nbar {
               height: calc(var(--nav) + env(safe-area-inset-top, 0px));
               padding-top: env(safe-area-inset-top, 0px);
               background: rgba(255, 255, 255, 0.82);
               border-bottom: 1px solid rgba(37, 99, 235, 0.13);
               box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
               backdrop-filter: blur(20px) saturate(1.18);
               -webkit-backdrop-filter: blur(20px) saturate(1.18);
            }

            html:not(.lm) #nbar {
               background: rgba(8, 13, 26, 0.86);
               border-bottom-color: rgba(96, 165, 250, 0.18);
               box-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
            }

            #nbar .container {
               padding-left: 14px;
               padding-right: 14px;
            }

            .guest-brand {
               max-width: calc(100vw - 108px) !important;
               gap: 8px !important;
               font-size: 0.92rem !important;
            }

            .guest-brand .logo-i {
               width: 34px !important;
               height: 34px !important;
               border-radius: 16%;
               background: #ffffff !important;
               border: 2px solid #ffffff;
               box-shadow: none;
               object-fit: contain;
               filter: drop-shadow(0 8px 14px rgba(37, 99, 235, 0.18));
            }

            .guest-brand-name {
               font-size: 0.9rem;
               font-weight: 800;
               letter-spacing: 0;
            }

            #thbtn,
            #mbtog {
               width: 36px !important;
               height: 36px !important;
               border-radius: 10px !important;
               background: rgba(255, 255, 255, 0.72);
               border-color: rgba(37, 99, 235, 0.14);
               box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
            }

            html:not(.lm) #thbtn,
            html:not(.lm) #mbtog {
               background: rgba(15, 23, 42, 0.72);
               border-color: rgba(148, 163, 184, 0.16);
            }

            #hero {
               min-height: auto;
               padding-top: calc(var(--nav) + env(safe-area-inset-top, 0px) + 14px);
               padding-bottom: 20px;
               justify-content: flex-start;
               background:
                  linear-gradient(180deg, rgba(248, 250, 252, 0.82) 0%, rgba(239, 246, 255, 0.36) 54%, transparent 100%);
            }

            html:not(.lm) #hero {
               background:
                  linear-gradient(180deg, rgba(15, 23, 42, 0.44) 0%, rgba(8, 8, 15, 0) 72%);
            }

            #hero .container {
               max-width: 100%;
               padding-left: 16px;
               padding-right: 16px;
            }

            #hero .text-center.mt-3.pt-3 {
               margin-top: 0 !important;
               padding-top: 0 !important;
            }

            #hero .hbadge {
               display: inline-flex !important;
               align-items: center;
               justify-content: center;
               gap: 7px;
               max-width: min(100%, 320px);
               margin-bottom: 14px;
               padding: 7px 11px;
               border-radius: 999px;
               background: rgba(59, 130, 246, 0.09);
               border: 1px solid rgba(59, 130, 246, 0.18);
               white-space: normal;
               text-align: center;
               line-height: 1.25;
               font-size: 0.66rem;
               font-weight: 800;
               letter-spacing: 0;
               color: #2563eb;
            }

            #hero .hbadge::before {
               content: "";
               width: 7px;
               height: 7px;
               border-radius: 999px;
               background: #14b8a6;
               box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.12);
               flex: 0 0 auto;
            }

            #hero .h1 {
               max-width: 350px;
               margin-left: auto;
               margin-right: auto;
               font-size: clamp(2rem, 8.4vw, 2.35rem) !important;
               line-height: 1.04;
               letter-spacing: 0;
            }

            #hero .h1 span {
               display: inline-block;
            }

            #hero p.mx-auto {
               max-width: 352px !important;
               margin-bottom: 18px !important;
               font-size: 0.9rem !important;
               line-height: 1.5;
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row {
               display: grid !important;
               grid-template-columns: repeat(3, minmax(0, 1fr));
               align-items: stretch !important;
               justify-content: center !important;
               gap: 6px !important;
               width: min(100%, 390px);
               margin-left: auto;
               margin-right: auto;
               margin-bottom: 14px;
               flex-wrap: nowrap !important;
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row > .btn {
               width: 100% !important;
               max-width: none !important;
               min-width: 0;
               min-height: 40px;
               padding: 8px 5px !important;
               border-radius: 8px;
               display: inline-flex;
               align-items: center;
               justify-content: center;
               gap: 3px;
               font-size: 0.68rem !important;
               line-height: 1.1;
               white-space: nowrap;
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row > .btn:first-child {
               grid-column: auto;
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row i {
               margin-right: 0 !important;
               font-size: 0.62rem;
               flex: 0 0 auto;
            }

            #hero .row.align-items-center.mt-4 {
               margin-top: 0 !important;
            }

            #hero .col-lg-5 img {
               max-height: 210px !important;
               margin-top: 0;
               margin-bottom: 4px;
               filter: drop-shadow(0 18px 28px rgba(37, 99, 235, 0.16));
            }

            #hero .col-lg-5 {
               margin-bottom: 6px !important;
            }

            #hero .hero-tech-card {
               --hero-tech-color: #000000;
               width: 100%;
               margin: 12px auto 10px !important;
               padding: 10px 0;
               border-radius: 0;
               background: transparent;
               border: 0;
               box-shadow: none;
            }

            html:not(.lm) #hero .hero-tech-card {
               --hero-tech-color: #ffffff;
               background: transparent;
               border-color: transparent;
            }

            #hero .hero-tech-title {
               color: var(--hero-tech-color) !important;
               margin-bottom: 8px !important;
               font-size: 0.58rem !important;
               letter-spacing: 0.09em !important;
            }

            #hero .tech-icons {
               gap: 18px !important;
               font-size: 1.12rem !important;
            }

            #hero .row.justify-content-center.mt-3.mb-3 {
               margin-top: 12px !important;
               margin-bottom: 6px !important;
            }

            #landing .sp {
               padding: 54px 0;
            }

            .landing-section-heading {
               align-items: flex-start;
               text-align: left;
            }

         .landing-section-heading .slbl {
            justify-content: flex-start;
         }

         .mobile-demo-preview-heading {
            align-items: center;
            text-align: center;
         }

         .mobile-demo-preview-heading .slbl {
            justify-content: center;
            margin-left: auto;
            margin-right: auto;
         }

         .mobile-demo-preview-heading .stitle {
            margin-left: auto;
            margin-right: auto;
            text-align: center;
         }

            .slbl {
               margin-bottom: 10px;
               font-size: 0.66rem;
               letter-spacing: 0.1em;
            }

            .stitle {
               font-size: 1.7rem;
               line-height: 1.12;
               margin-bottom: 10px;
            }

            #about .landing-section-heading {
               align-items: center;
               text-align: center;
            }

            #about .landing-section-heading .slbl {
               justify-content: center;
            }

            #about .landing-section-heading .stitle {
               max-width: 340px;
               margin-left: auto;
               margin-right: auto;
               text-align: center;
            }

            #how .landing-section-heading {
               align-items: center;
               text-align: center;
            }

            #how .landing-section-heading .slbl {
               justify-content: center;
            }

            #how .landing-section-heading .stitle {
               max-width: 340px;
               margin-left: auto;
               margin-right: auto;
               text-align: center;
            }

            #benefits .landing-section-heading {
               align-items: center;
               text-align: center;
            }

            #benefits .landing-section-heading .slbl {
               justify-content: center;
            }

            #benefits .landing-section-heading .stitle {
               max-width: 340px;
               margin-left: auto;
               margin-right: auto;
               text-align: center;
            }

            #contact .landing-section-heading {
               align-items: center;
               text-align: center;
            }

            #contact .landing-section-heading .slbl {
               justify-content: center;
            }

            #contact .landing-section-heading .stitle {
               max-width: 340px;
               margin-left: auto;
               margin-right: auto;
               text-align: center;
            }

            #about .about-system-copy {
               text-align: justify;
               text-indent: 3.00rem;
               text-align-last: left;
               line-height: 1.62;
            }

            #about .target-users-grid {
               display: grid !important;
               grid-template-columns: repeat(3, minmax(0, 1fr));
               gap: 8px !important;
            }

            #about .target-users-grid .ftag {
               width: 100%;
               min-width: 0;
               margin-top: 0;
               padding: 7px 5px !important;
               display: inline-flex;
               align-items: center;
               justify-content: center;
               gap: 4px;
               font-size: 0.58rem;
               line-height: 1.1;
               text-align: center;
               white-space: nowrap;
            }

            #about .target-users-grid .ftag i {
               margin-right: 0 !important;
               font-size: 0.58rem;
               flex: 0 0 auto;
            }

            .gc {
               border-radius: 10px;
               box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            }

            .gc.p-4 {
               padding: 17px !important;
            }

            .landing-stats-row {
               --bs-gutter-x: 0.5rem;
               --bs-gutter-y: 0.5rem;
            }

            .landing-stats-row > [data-landing-stat]:not([data-landing-stat="success-rate"]) .gc.p-4 {
               padding: 10px 4px !important;
            }

            .landing-stats-row > [data-landing-stat]:not([data-landing-stat="success-rate"]) .pnum {
               font-size: 1.55rem !important;
            }

            .landing-stats-row > [data-landing-stat]:not([data-landing-stat="success-rate"]) .plbl {
               margin-top: 6px !important;
               font-size: 0.52rem !important;
               letter-spacing: 0.02em !important;
               line-height: 1.2;
            }
         }

         #about .about-system-panel {
            position: relative;
            overflow: hidden;
            padding: 24px;
            border: 1px solid rgba(37, 99, 235, 0.18);
            border-radius: 16px;
            background:
               linear-gradient(135deg, color-mix(in srgb, var(--sf, #ffffff) 94%, #2563eb 6%), color-mix(in srgb, var(--bg3, #f8fafc) 88%, #06b6d4 12%));
            box-shadow: 0 20px 46px rgba(15, 23, 42, 0.12);
         }

         #about .about-system-panel::before {
            content: "";
            position: absolute;
            top: 18px;
            bottom: 18px;
            left: 0;
            width: 4px;
            border-radius: 0 999px 999px 0;
            background: linear-gradient(180deg, var(--pur, #2563eb), #06b6d4, #34d399);
         }

         #about .about-system-panel::after {
            content: "";
            position: absolute;
            top: 18px;
            bottom: 18px;
            right: 0;
            width: 4px;
            border-radius: 999px 0 0 999px;
            background: linear-gradient(180deg, #2563eb 0%, #06b6d4 50%, #34d399 100%);
            box-shadow: -8px 0 18px rgba(14, 165, 233, 0.14);
         }

         html.lm #about .about-system-panel {
            background: linear-gradient(135deg, #ffffff, #f3f8ff);
            border-color: rgba(37, 99, 235, 0.18);
            box-shadow: 0 20px 46px rgba(22, 34, 58, 0.12);
         }

         html:not(.lm) #about .about-system-panel {
            background: linear-gradient(135deg, rgba(18, 18, 31, 0.98), rgba(15, 23, 42, 0.92));
            border-color: rgba(148, 163, 184, 0.24);
            box-shadow: 0 22px 52px rgba(0, 0, 0, 0.34);
         }

         #about .about-system-panel .about-system-copy {
            margin-bottom: 22px !important;
         }

         #about .about-system-panel h4 {
            color: var(--tx);
            font-weight: 800;
         }

         #about .about-system-panel .target-users-grid {
            margin-bottom: 0 !important;
         }

         @media (max-width: 575.98px) {
            #about .about-system-panel {
               padding: 20px 16px 18px;
               border-radius: 14px;
            }

            #about .about-system-panel::before {
               top: 16px;
               bottom: 16px;
               width: 3px;
            }

            #about .about-system-panel::after {
               top: 16px;
               bottom: 16px;
               width: 3px;
            }

            #about .about-system-panel h4 {
               margin-top: 18px !important;
               font-size: 0.96rem !important;
            }
         }

         @media (max-width: 390px) {
            #about .about-system-panel .target-users-grid {
               grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #about .about-system-panel .target-users-grid .ftag {
               min-height: 32px;
               white-space: normal;
            }

            #about .about-system-panel .target-users-grid .ftag:last-child {
               grid-column: 1 / -1;
            }
         }

         .ui-showcase {
            display: block;
            width: 100%;
         }

         .landing-stats-row > [data-landing-stat]:not([data-landing-stat="success-rate"]) {
            flex: 0 0 25%;
            max-width: 25%;
         }

         .landing-stats-row > [data-landing-stat]:not([data-landing-stat="success-rate"]) .gc {
            min-height: 100%;
         }

         .ui-device {
            overflow: hidden;
            background: var(--sf);
            border: 1px solid var(--bd2);
            box-shadow: 0 18px 44px rgba(0, 0, 0, 0.28);
         }

         .ui-device-mobile {
            --mobile-preview-surface: #eef4fb;
            --mobile-preview-outline: rgba(37, 99, 235, 0.24);
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
            position: relative;
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

         .ui-device-mobile .ui-device-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: min(238px, calc(100% - 72px));
            max-width: calc(100% - 72px);
            height: auto;
            margin: 0;
            padding: 0;
            justify-content: center;
            text-align: center;
            background: transparent;
            border: 0;
            box-shadow: none;
            font-size: 0.62rem;
            font-weight: 800;
            line-height: 1.1;
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

         .ui-device-mobile .ui-dashboard-preview {
            position: relative;
            padding: 0;
            background: var(--mobile-preview-surface);
            overflow: hidden;
            pointer-events: none;
            box-shadow: inset 0 0 0 1px var(--mobile-preview-outline);
         }

         .ui-device-mobile .ui-preview-video {
            display: block;
            width: 100%;
            height: auto;
            background: var(--mobile-preview-surface);
            filter: none;
            mix-blend-mode: normal;
            opacity: 1;
            pointer-events: none;
            user-select: none;
         }

         .ui-device-mobile.ui-device-mobile-image {
            width: 100%;
            max-width: 100% !important;
            border: 0;
            background: transparent !important;
            box-shadow: none;
            overflow: visible;
         }

         .ui-device-mobile-image .ui-device-bar {
            display: none;
         }

         .mobile-preview-image-swiper {
            --mobile-preview-card-bg: var(--sf, #ffffff);
            --mobile-preview-card-border: #0f172a;
            --mobile-preview-title: var(--tx, #0f172a);
            --mobile-preview-body: var(--tx2, #334155);
            --mobile-preview-list: var(--tx, #0f172a);
            --mobile-preview-kicker: color-mix(in srgb, var(--tx2, #334155) 58%, var(--pur, #2563eb) 42%);
            --mobile-preview-badge-bg: color-mix(in srgb, var(--tx, #0f172a) 78%, #2563eb 22%);
            --mobile-preview-check-bg: color-mix(in srgb, var(--bg3, #e8eef7) 72%, var(--pur, #2563eb) 28%);
            --mobile-preview-check: color-mix(in srgb, var(--pur, #2563eb) 76%, var(--tx, #0f172a) 24%);
            --mobile-preview-dot: color-mix(in srgb, var(--tx3, #64748b) 22%, transparent);
            --mobile-preview-dot-active: color-mix(in srgb, var(--pur, #2563eb) 68%, #06b6d4 32%);
            --mobile-preview-control-bg: color-mix(in srgb, var(--sf, #ffffff) 92%, transparent);
            --mobile-preview-control: var(--tx, #0f172a);
            --mobile-preview-shadow: rgba(15, 23, 42, 0.14);
            width: min(calc(100vw - 8px), 410px);
            max-width: none;
            margin-left: 50%;
            padding: 4px 0 44px;
            overflow: visible;
            isolation: isolate;
            transform: translateX(-50%);
            perspective: 1200px;
         }

         .mobile-preview-image-swiper::before,
         .mobile-preview-image-swiper::after {
            display: none;
         }

         .mobile-preview-image-swiper .swiper-wrapper {
            align-items: stretch;
            position: relative;
            z-index: 2;
         }

         html.lm #landing .mobile-preview-image-swiper,
         html[data-theme="light"] #landing .mobile-preview-image-swiper,
         body.lm #landing .mobile-preview-image-swiper {
            --mobile-preview-card-bg: #ffffff;
            --mobile-preview-card-border: #0f172a;
            --mobile-preview-title: var(--tx, #0f172a);
            --mobile-preview-body: var(--tx2, #334155);
            --mobile-preview-list: var(--tx, #0f172a);
            --mobile-preview-kicker: color-mix(in srgb, var(--tx2, #334155) 48%, var(--pur, #2563eb) 52%);
            --mobile-preview-badge-bg: color-mix(in srgb, var(--tx, #0f172a) 82%, #2563eb 18%);
            --mobile-preview-check-bg: color-mix(in srgb, #eff6ff 78%, var(--pur, #2563eb) 22%);
            --mobile-preview-check: color-mix(in srgb, var(--pur, #2563eb) 86%, #0f172a 14%);
            --mobile-preview-dot: #d9e7f8;
            --mobile-preview-dot-active: color-mix(in srgb, var(--pur, #2563eb) 66%, #06b6d4 34%);
            --mobile-preview-control-bg: rgba(255, 255, 255, 0.96);
            --mobile-preview-shadow: rgba(22, 34, 58, 0.14);
         }

         html.dm #landing .mobile-preview-image-swiper,
         html[data-theme="dark"] #landing .mobile-preview-image-swiper,
         body.dm #landing .mobile-preview-image-swiper,
         html:not(.lm) body:not(.lm) #landing .mobile-preview-image-swiper {
            --mobile-preview-card-bg: color-mix(in srgb, var(--sf, #171d2d) 92%, #ffffff 8%);
            --mobile-preview-card-border: rgba(248, 250, 252, 0.72);
            --mobile-preview-title: #f8fafc;
            --mobile-preview-body: #cbd5e1;
            --mobile-preview-list: #f1f5f9;
            --mobile-preview-kicker: color-mix(in srgb, #93c5fd 70%, #cbd5e1 30%);
            --mobile-preview-badge-bg: color-mix(in srgb, var(--pur, #60a5fa) 46%, #020617 54%);
            --mobile-preview-check-bg: rgba(96, 165, 250, 0.16);
            --mobile-preview-check: #93c5fd;
            --mobile-preview-dot: rgba(148, 163, 184, 0.28);
            --mobile-preview-dot-active: color-mix(in srgb, #93c5fd 54%, var(--pur, #60a5fa) 46%);
            --mobile-preview-control-bg: color-mix(in srgb, var(--sf, #171d2d) 88%, transparent);
            --mobile-preview-shadow: rgba(0, 0, 0, 0.36);
         }

         .mobile-preview-image-slide {
            position: relative;
            z-index: 1;
            box-sizing: border-box;
            width: min(calc(100vw - 52px), 345px) !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 548px;
            gap: 15px;
            padding: 19px 20px 26px;
            border: 0;
            border-radius: 15px;
            background: var(--mobile-preview-card-bg);
            box-shadow: 0 28px 58px var(--mobile-preview-shadow);
            opacity: 0.2;
            filter: blur(1px) saturate(0.78);
            transition: opacity 240ms ease, transform 240ms ease, filter 240ms ease, box-shadow 240ms ease;
            pointer-events: none;
            overflow: hidden;
            will-change: transform, opacity, filter;
         }

         .mobile-preview-image-slide::before,
         .mobile-preview-image-slide::after {
            content: "";
            position: absolute;
            top: 14px;
            bottom: 14px;
            z-index: 2;
            width: 4px;
            border-radius: 999px;
            background: linear-gradient(180deg, #2563eb 0%, #06b6d4 50%, #34d399 100%);
            pointer-events: none;
         }

         .mobile-preview-image-slide::before {
            left: 0;
            box-shadow: 8px 0 18px rgba(14, 165, 233, 0.12);
         }

         .mobile-preview-image-slide::after {
            right: 0;
            box-shadow: -8px 0 18px rgba(14, 165, 233, 0.12);
         }

         .mobile-preview-image-slide.swiper-slide-active {
            z-index: 5;
            opacity: 1;
            filter: none;
            pointer-events: auto;
            box-shadow: 0 30px 62px var(--mobile-preview-shadow);
         }

         .mobile-preview-image-slide.swiper-slide-active:hover,
         .mobile-preview-image-slide.swiper-slide-active:focus-within {
            transform: translateY(-8px);
            box-shadow: 0 38px 76px rgba(15, 23, 42, 0.26), 0 18px 36px rgba(14, 165, 233, 0.18);
         }

         .mobile-preview-image-slide.swiper-slide-prev,
         .mobile-preview-image-slide.swiper-slide-next {
            z-index: 3;
            opacity: 0.48;
            filter: blur(0.35px) saturate(0.9);
         }

         .mobile-preview-image-slide.swiper-slide-prev {
            transform-origin: right center;
         }

         .mobile-preview-image-slide.swiper-slide-next {
            transform-origin: left center;
         }

         .mobile-preview-shell-img {
            display: block;
            width: 100%;
            max-width: 100%;
            aspect-ratio: 2 / 3;
            height: auto;
            object-fit: contain;
            border-radius: 28px;
            filter: drop-shadow(0 15px 24px rgba(15, 23, 42, 0.22));
            transition: filter 240ms ease, transform 240ms ease;
            user-select: none;
         }

         .mobile-preview-image-slide.swiper-slide-active:hover .mobile-preview-shell-img,
         .mobile-preview-image-slide.swiper-slide-active:focus-within .mobile-preview-shell-img {
            transform: translateY(-2px);
            filter: drop-shadow(0 24px 34px rgba(15, 23, 42, 0.34)) drop-shadow(0 12px 24px rgba(14, 165, 233, 0.2));
         }

         .mobile-preview-copy {
            width: min(100%, 760px);
            margin: 0 auto;
            padding: 0;
            text-align: center;
            color: var(--tx);
         }

         .mobile-preview-copy-kicker {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            max-width: 100%;
            margin-bottom: 11px;
            color: var(--mobile-preview-kicker) !important;
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            line-height: 1.25;
            text-align: center;
            text-transform: uppercase;
         }

         .mobile-preview-copy-kicker span {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: var(--mobile-preview-badge-bg);
            color: #ffffff;
            font-size: 0.72rem;
            letter-spacing: 0;
         }

         .mobile-preview-copy-title {
            margin: 0 auto 10px;
            max-width: 310px;
            color: var(--mobile-preview-title) !important;
            font-size: 1.16rem;
            font-weight: 900;
            line-height: 1.22;
         }

         .mobile-preview-copy-text {
            max-width: 302px;
            margin: 0 auto 16px;
            color: var(--mobile-preview-body) !important;
            font-size: 0.85rem;
            line-height: 1.55;
         }

         .mobile-preview-copy-list {
            display: grid;
            gap: 11px;
            max-width: 306px;
            margin: 0 auto;
            padding: 0;
            list-style: none;
            text-align: left;
         }

         .mobile-preview-copy-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--mobile-preview-list) !important;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.35;
         }

         .mobile-preview-copy-list i {
            width: 22px;
            height: 22px;
            flex: 0 0 22px;
            margin-top: -1px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: var(--mobile-preview-check-bg);
            color: var(--mobile-preview-check);
            font-size: 0.62rem;
         }

         .mobile-preview-image-swiper .mobile-preview-next,
         .mobile-preview-image-swiper .mobile-preview-prev {
            display: none !important;
         }

         .mobile-preview-image-swiper .mobile-preview-next::after,
         .mobile-preview-image-swiper .mobile-preview-prev::after {
            font-size: 0.78rem;
            font-weight: 900;
         }

         .mobile-preview-image-swiper .mobile-preview-pagination {
            position: absolute !important;
            left: 50% !important;
            bottom: 10px !important;
            transform: translateX(-50%) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 18px;
            width: 140px !important;
            margin: 0;
            z-index: 11;
         }

         .mobile-preview-image-swiper .swiper-pagination-bullet {
            width: 6px;
            height: 6px;
            background: var(--mobile-preview-dot);
            opacity: 1;
            margin: 0 !important;
         }

         .mobile-preview-image-swiper .swiper-pagination-bullet-active {
            width: 20px;
            background: var(--mobile-preview-dot-active);
            border-radius: 999px;
            opacity: 1;
         }

         .mobile-preview-autoplay-toggle {
            position: absolute;
            right: calc(50% - 116px);
            bottom: 5px;
            z-index: 12;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid var(--mobile-preview-card-border);
            border-radius: 999px;
            background: var(--mobile-preview-control-bg);
            color: var(--mobile-preview-control);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
         }

         .mobile-preview-autoplay-toggle i {
            font-size: 0.64rem;
            line-height: 1;
         }

         #landing .landing-auto-carousel {
            --landing-carousel-card-width: min(calc(100vw - 54px), 340px);
            --landing-carousel-dot: color-mix(in srgb, var(--tx3, #64748b) 24%, transparent);
            --landing-carousel-dot-active: color-mix(in srgb, var(--pur, #2563eb) 68%, #06b6d4 32%);
            --landing-carousel-control-bg: color-mix(in srgb, var(--sf, #ffffff) 92%, transparent);
            --landing-carousel-control: var(--tx, #0f172a);
            --landing-carousel-control-border: rgba(15, 23, 42, 0.24);
            width: min(calc(100vw - 8px), 430px);
            max-width: none;
            margin: 0 auto;
            padding: 6px 0 44px;
            overflow: visible;
            isolation: isolate;
            perspective: 1100px;
         }

         #landing .landing-auto-carousel::before,
         #landing .landing-auto-carousel::after {
            display: none;
         }

         html.lm #landing .landing-auto-carousel {
            --landing-carousel-dot: #d9e7f8;
            --landing-carousel-control-bg: rgba(255, 255, 255, 0.96);
            --landing-carousel-control-border: rgba(37, 99, 235, 0.18);
         }

         html:not(.lm) #landing .landing-auto-carousel {
            --landing-carousel-dot: rgba(148, 163, 184, 0.28);
            --landing-carousel-control-bg: color-mix(in srgb, var(--sf, #171d2d) 88%, transparent);
            --landing-carousel-control-border: rgba(248, 250, 252, 0.42);
         }

         #landing .landing-auto-carousel .swiper-wrapper.row {
            --bs-gutter-x: 0;
            --bs-gutter-y: 0;
            position: relative;
            z-index: 2;
            flex-wrap: nowrap;
            align-items: stretch;
            justify-content: flex-start !important;
            width: 100%;
            max-width: none !important;
            margin-left: 0;
            margin-right: 0;
         }

         #landing .landing-auto-carousel .swiper-slide {
            width: var(--landing-carousel-card-width) !important;
            max-width: var(--landing-carousel-card-width) !important;
            height: auto;
            flex: 0 0 auto !important;
            display: flex;
            padding: 0 !important;
            opacity: 0.26;
            filter: blur(0.5px) saturate(0.82);
            transform: scale(0.94);
            transition: opacity 240ms ease, transform 240ms ease, filter 240ms ease;
            pointer-events: none;
         }

         #landing .landing-auto-carousel .swiper-slide-prev,
         #landing .landing-auto-carousel .swiper-slide-next {
            opacity: 0.58;
            filter: blur(0.2px) saturate(0.9);
            transform: scale(0.97);
         }

         #landing .landing-auto-carousel .swiper-slide-active {
            opacity: 1;
            filter: none;
            transform: scale(1);
            pointer-events: auto;
         }

         #landing .landing-auto-carousel .swiper-slide > .gc {
            position: relative;
            width: 100% !important;
            min-height: 248px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
         }

         #landing .landing-auto-carousel .swiper-slide > .gc::before,
         #landing .landing-auto-carousel .swiper-slide > .gc::after {
            content: "";
            position: absolute;
            top: 12px;
            bottom: 12px;
            z-index: 2;
            width: 4px;
            border-radius: 999px;
            background: linear-gradient(180deg, #2563eb 0%, #06b6d4 50%, #34d399 100%);
            pointer-events: none;
         }

         #landing .landing-auto-carousel .swiper-slide > .gc::before {
            left: 0;
            box-shadow: 8px 0 18px rgba(14, 165, 233, 0.12);
         }

         #landing .landing-auto-carousel .swiper-slide > .gc::after {
            right: 0;
            box-shadow: -8px 0 18px rgba(14, 165, 233, 0.12);
         }

         #landing .features-auto-carousel .feature-card {
            min-height: 270px;
         }

         #landing .how-auto-carousel .gc {
            min-height: 222px;
         }

         #landing .landing-auto-carousel .gc p {
            margin-bottom: 0;
         }

         #landing .landing-auto-carousel .landing-carousel-next,
         #landing .landing-auto-carousel .landing-carousel-prev {
            display: none !important;
         }

         #landing .landing-auto-carousel .landing-carousel-pagination {
            position: absolute !important;
            left: 50% !important;
            bottom: 10px !important;
            transform: translateX(-50%) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 18px;
            width: 140px !important;
            margin: 0;
            z-index: 11;
         }

         #landing .landing-auto-carousel .swiper-pagination-bullet {
            width: 6px;
            height: 6px;
            margin: 0 !important;
            background: var(--landing-carousel-dot);
            opacity: 1;
         }

         #landing .landing-auto-carousel .swiper-pagination-bullet-active {
            width: 20px;
            border-radius: 999px;
            background: var(--landing-carousel-dot-active);
            opacity: 1;
         }

         #landing .landing-carousel-autoplay-toggle {
            position: absolute;
            right: calc(50% - 116px);
            bottom: 5px;
            z-index: 12;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid var(--landing-carousel-control-border);
            border-radius: 999px;
            background: var(--landing-carousel-control-bg);
            color: var(--landing-carousel-control);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
         }

         #landing .landing-carousel-autoplay-toggle i {
            font-size: 0.64rem;
            line-height: 1;
         }

         @media (max-width: 374.98px) {
            .mobile-preview-image-slide {
               min-height: 528px;
               padding: 18px 16px 24px;
            }

            .mobile-preview-copy-title {
               font-size: 1.06rem;
            }

            .mobile-preview-copy-text,
            .mobile-preview-copy-list li {
               font-size: 0.8rem;
            }

            .mobile-preview-autoplay-toggle {
               right: calc(50% - 106px);
            }

            #landing .landing-auto-carousel {
               --landing-carousel-card-width: min(calc(100vw - 42px), 318px);
            }

            #landing .landing-carousel-autoplay-toggle {
               right: calc(50% - 106px);
            }

            #landing .landing-auto-carousel .swiper-slide > .gc::before,
            #landing .landing-auto-carousel .swiper-slide > .gc::after,
            .mobile-preview-image-slide::before,
            .mobile-preview-image-slide::after {
               width: 3px;
            }
         }

         .ui-mobile-wire {
            --wire-bg: #f8fafc;
            --wire-panel: #ffffff;
            --wire-panel-soft: #f1f5f9;
            --wire-line: #cbd5e1;
            --wire-line-strong: #94a3b8;
            --wire-fill: #e2e8f0;
            --wire-text: #64748b;
            --wire-ink: #334155;
            --wire-chip-bg: rgba(255, 255, 255, 0.72);
            --wire-speech-bg: rgba(255, 255, 255, 0.86);
            --wire-grid-opacity: 0.24;
            --wire-robot-opacity: 0.22;
            padding: 0;
            background: var(--wire-bg);
            color: var(--wire-text);
            font-size: 0.7rem;
            line-height: 1.2;
         }

         html.lm #landing .ui-mobile-wire {
            --wire-bg: #f8fafc;
            --wire-panel: #ffffff;
            --wire-panel-soft: #f1f5f9;
            --wire-line: #cbd5e1;
            --wire-line-strong: #94a3b8;
            --wire-fill: #e2e8f0;
            --wire-text: #64748b;
            --wire-ink: #334155;
            --wire-chip-bg: rgba(255, 255, 255, 0.72);
            --wire-speech-bg: rgba(255, 255, 255, 0.86);
            --wire-grid-opacity: 0.24;
            --wire-robot-opacity: 0.22;
         }

         html:not(.lm) #landing .ui-mobile-wire {
            --wire-bg: #101827;
            --wire-panel: #111827;
            --wire-panel-soft: #172033;
            --wire-line: #334155;
            --wire-line-strong: #7c8da6;
            --wire-fill: #253247;
            --wire-text: #a7b4c8;
            --wire-ink: #e5edf7;
            --wire-chip-bg: rgba(15, 23, 42, 0.72);
            --wire-speech-bg: rgba(23, 32, 51, 0.92);
            --wire-grid-opacity: 0.34;
            --wire-robot-opacity: 0.32;
         }

         .ui-mobile-wire *,
         .ui-mobile-wire *::before,
         .ui-mobile-wire *::after {
            box-sizing: border-box;
         }

         .ui-mobile-wire-topbar {
            height: 38px;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px 7px;
            background: var(--wire-panel);
            border-bottom: 1px solid var(--wire-line);
         }

         .ui-mobile-wire-brand {
            min-width: 0;
            flex: 1 1 auto;
            height: 28px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px 4px 4px;
            border: 1px solid var(--wire-line);
            border-radius: 9px;
            color: var(--wire-ink);
            font-weight: 800;
            overflow: hidden;
            white-space: nowrap;
         }

         .ui-mobile-wire-logo,
         .ui-mobile-wire-dot {
            flex: 0 0 auto;
            border-radius: 999px;
            background: var(--wire-fill);
            border: 1px solid var(--wire-line);
         }

         .ui-mobile-wire-logo {
            width: 20px;
            height: 20px;
         }

         .ui-mobile-wire-actions {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 4px;
         }

         .ui-mobile-wire-action,
         .ui-mobile-wire-avatar,
         .ui-mobile-wire-icon {
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--wire-line);
            border-radius: 8px;
            background: var(--wire-panel);
            color: var(--wire-text);
            font-size: 0.7rem;
         }

         .ui-mobile-wire-avatar {
            font-weight: 800;
         }

         .ui-mobile-wire-body {
            padding: 6px;
         }

         .ui-mobile-wire-hero {
            position: relative;
            min-height: 88px;
            padding: 9px 78px 8px 10px;
            overflow: hidden;
            border: 1px solid var(--wire-line);
            border-radius: 8px;
            background: var(--wire-panel);
         }

         .ui-mobile-wire-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
               linear-gradient(var(--wire-line) 1px, transparent 1px) 0 0 / 100% 28px,
               linear-gradient(90deg, var(--wire-line) 1px, transparent 1px) 0 0 / 34px 100%;
            opacity: var(--wire-grid-opacity);
            pointer-events: none;
         }

         .ui-mobile-wire-hero-title {
            position: relative;
            z-index: 2;
            margin: 0 0 5px;
            padding-left: 8px;
            color: var(--wire-ink);
            font-size: 0.62rem;
            font-weight: 900;
            line-height: 1.08;
            text-transform: uppercase;
         }

         .ui-mobile-wire-hero-title::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            height: 100%;
            border-radius: 999px;
            background: var(--wire-fill);
         }

         .ui-mobile-wire-hero-title span {
            display: block;
            color: var(--wire-line-strong);
         }

         .ui-mobile-wire-keywords {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 3px 7px;
            width: 104px;
            margin: 0 0 6px;
            padding: 0;
            list-style: none;
         }

         .ui-mobile-wire-keywords li {
            display: flex;
            align-items: center;
            gap: 4px;
            min-width: 0;
         }

         .ui-mobile-wire-keywords li::before {
            content: "";
            width: 4px;
            height: 4px;
            flex: 0 0 4px;
            border-radius: 999px;
            background: var(--wire-line-strong);
         }

         .ui-mobile-wire-line {
            display: block;
            height: 4px;
            min-width: 0;
            border-radius: 999px;
            background: var(--wire-fill);
         }

         .ui-mobile-wire-chips {
            position: relative;
            z-index: 2;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
         }

         .ui-mobile-wire-chip {
            min-height: 16px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            border: 1px solid var(--wire-line);
            border-radius: 6px;
            background: var(--wire-chip-bg);
            color: var(--wire-text);
            font-size: 0.48rem;
            font-weight: 700;
            white-space: nowrap;
         }

         .ui-mobile-wire-dot {
            width: 7px;
            height: 7px;
         }

         .ui-mobile-wire-speech {
            position: absolute;
            z-index: 3;
            top: 23px;
            right: 54px;
            width: 66px;
            min-height: 34px;
            padding: 7px;
            border: 1px solid var(--wire-line);
            border-radius: 12px;
            background: var(--wire-speech-bg);
         }

         .ui-mobile-wire-speech::after {
            content: "";
            position: absolute;
            right: -10px;
            top: 18px;
            width: 10px;
            height: 12px;
            background: inherit;
            border-top: 1px solid var(--wire-line);
            border-right: 1px solid var(--wire-line);
            transform: skewX(35deg);
         }

         .ui-mobile-wire-speech .ui-mobile-wire-line + .ui-mobile-wire-line {
            margin-top: 5px;
         }

         .ui-mobile-wire-robot {
            position: absolute;
            z-index: 2;
            right: -5px;
            bottom: -2px;
            width: 68px;
            max-width: 34%;
            opacity: var(--wire-robot-opacity);
            filter: grayscale(1) saturate(0);
         }

         .ui-mobile-wire-progress {
            height: 5px;
            margin: 6px 10px;
            border-radius: 999px;
            background: var(--wire-fill);
            overflow: hidden;
         }

         .ui-mobile-wire-progress span {
            display: block;
            width: 48%;
            height: 100%;
            border-radius: inherit;
            background: var(--wire-line-strong);
         }

         .ui-mobile-wire-summary {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.88fr) minmax(0, 0.88fr);
            gap: 5px;
         }

         .ui-mobile-wire-card {
            min-width: 0;
            border: 1px solid var(--wire-line);
            border-radius: 8px;
            background: var(--wire-panel);
         }

         .ui-mobile-wire-score {
            grid-row: span 2;
            min-height: 168px;
            padding: 8px 7px;
         }

         .ui-mobile-wire-card-title,
         .ui-mobile-wire-stat-head {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--wire-ink);
            font-weight: 800;
         }

         .ui-mobile-wire-card-title {
            margin-bottom: 7px;
            font-size: 0.52rem;
         }

         .ui-mobile-wire-card-title i {
            color: var(--wire-line-strong);
         }

         .ui-mobile-wire-ring {
            width: 68px;
            height: 68px;
            margin: 0 auto 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 8px solid var(--wire-fill);
            border-radius: 999px;
            color: var(--wire-ink);
            text-align: center;
         }

         .ui-mobile-wire-ring strong {
            display: block;
            font-size: 1.05rem;
            line-height: 1;
         }

         .ui-mobile-wire-ring span {
            display: block;
            margin-top: 3px;
            font-size: 0.42rem;
            font-weight: 700;
            color: var(--wire-text);
         }

         .ui-mobile-wire-mini-list {
            display: grid;
            gap: 5px;
         }

         .ui-mobile-wire-mini {
            min-height: 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 6px;
            border: 1px solid var(--wire-line);
            border-radius: 7px;
            background: var(--wire-bg);
         }

         .ui-mobile-wire-mini span {
            display: block;
            color: var(--wire-text);
            font-size: 0.47rem;
            font-weight: 700;
         }

         .ui-mobile-wire-mini strong {
            display: block;
            color: var(--wire-ink);
            font-size: 0.78rem;
            line-height: 1.05;
         }

         .ui-mobile-wire-mini i {
            width: 22px;
            height: 22px;
            flex: 0 0 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: var(--wire-fill);
            color: var(--wire-text);
         }

         .ui-mobile-wire-stat {
            min-height: 82px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 7px;
            overflow: hidden;
         }

         .ui-mobile-wire-stat-head {
            justify-content: space-between;
            gap: 5px;
            font-size: 0.47rem;
         }

         .ui-mobile-wire-stat .ui-mobile-wire-icon {
            width: 21px;
            height: 21px;
            background: var(--wire-fill);
         }

         .ui-mobile-wire-pill {
            min-width: 0;
            padding: 2px 6px;
            border: 1px solid var(--wire-line);
            border-radius: 999px;
            background: var(--wire-bg);
            color: var(--wire-text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
         }

         .ui-mobile-wire-stat-mark {
            width: 28px;
            height: 28px;
            margin: 5px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--wire-line);
            border-radius: 999px;
            color: var(--wire-text);
            font-weight: 800;
         }

         .ui-mobile-wire-stat-value {
            color: var(--wire-ink);
            font-size: 0.84rem;
            font-weight: 900;
            line-height: 1.05;
         }

         .ui-mobile-wire-stat-label {
            margin-top: 2px;
            color: var(--wire-text);
            font-size: 0.46rem;
            font-weight: 700;
            line-height: 1.12;
         }

         .ui-mobile-wire-underbar {
            height: 3px;
            margin: 5px -7px -7px;
            border-radius: 999px;
            background: var(--wire-line-strong);
         }

         .ui-mobile-wire-trend {
            margin-top: 6px;
            padding: 8px;
            border: 1px solid var(--wire-line);
            border-radius: 8px;
            background: var(--wire-panel);
         }

         .ui-mobile-wire-trend-head {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 6px;
         }

         .ui-mobile-wire-trend-head .ui-mobile-wire-icon {
            width: 26px;
            height: 26px;
            background: var(--wire-bg);
         }

         .ui-mobile-wire-trend-title {
            margin: 0;
            color: var(--wire-ink);
            font-size: 0.72rem;
            font-weight: 900;
         }

         .ui-mobile-wire-trend-copy {
            margin: 0 0 7px;
            color: var(--wire-text);
            font-size: 0.53rem;
            font-weight: 600;
            line-height: 1.35;
         }

         .ui-mobile-wire-trend-actions,
         .ui-mobile-wire-trend-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
         }

         .ui-mobile-wire-trend-metrics {
            display: none;
         }

         .ui-mobile-wire-button,
         .ui-mobile-wire-metric {
            min-height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            border: 1px solid var(--wire-line);
            border-radius: 7px;
            background: var(--wire-bg);
            color: var(--wire-text);
            font-weight: 800;
         }

         .ui-mobile-wire-metric {
            justify-content: flex-start;
            display: none;
            margin-top: 0;
            padding: 0;
            font-size: 0.5rem;
            line-height: 1.15;
         }

         .ui-mobile-wire-metric i {
            width: 26px;
            height: 26px;
            flex: 0 0 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: var(--wire-fill);
         }

         .ui-mobile-wire-metric strong {
            display: block;
            color: var(--wire-ink);
            font-size: 0.74rem;
         }

         .ui-mobile-wire-chart {
            display: none;
            height: 42px;
            margin-top: 7px;
            padding-left: 24px;
            align-items: stretch;
            gap: 10px;
            background:
               linear-gradient(var(--wire-line) 1px, transparent 1px) left top / 100% 18px,
               linear-gradient(90deg, var(--wire-line) 1px, transparent 1px) 0 0 / 24px 100%;
            opacity: 0.88;
         }

         .ui-mobile-wire-nav {
            height: 46px;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 2px;
            padding: 4px 6px;
            border-top: 1px solid var(--wire-line);
            background: var(--wire-panel);
         }

         .ui-mobile-wire-nav-item {
            min-width: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            border-radius: 8px;
            color: var(--wire-text);
            font-size: 0.44rem;
            font-weight: 700;
         }

         .ui-mobile-wire-nav-item.active {
            border: 1px solid var(--wire-line);
            background: var(--wire-bg);
         }

         .ui-mobile-wire-nav-item i {
            font-size: 0.78rem;
         }

         .ui-device-desktop.ui-desktop-shell {
            --desktop-preview-surface: #f8fafc;
            --desktop-preview-outline: rgba(37, 99, 235, 0.18);
            background: var(--desktop-preview-surface);
         }

         .ui-desktop-wire {
            --wire-bg: #f8fafc;
            --wire-panel: #ffffff;
            --wire-panel-soft: #f1f5f9;
            --wire-bg-fade-start: rgba(248, 250, 252, 0);
            --wire-line: #cbd5e1;
            --wire-line-strong: #94a3b8;
            --wire-fill: #e2e8f0;
            --wire-text: #64748b;
            --wire-ink: #334155;
            --wire-chip-bg: rgba(255, 255, 255, 0.76);
            --wire-speech-bg: rgba(255, 255, 255, 0.88);
            --wire-grid-opacity: 0.26;
            --wire-robot-opacity: 0.18;
            display: grid;
            grid-template-columns: 224px minmax(0, 1fr);
            position: relative;
            height: 500px;
            min-height: 0;
            overflow: hidden;
            padding: 0;
            background: var(--wire-bg);
            color: var(--wire-text);
            pointer-events: none;
         }

         .ui-desktop-wire::after {
            content: "";
            position: absolute;
            left: 224px;
            right: 0;
            bottom: 0;
            z-index: 5;
            height: 52px;
            background: linear-gradient(180deg, var(--wire-bg-fade-start), var(--wire-bg) 78%);
            pointer-events: none;
         }

         html.lm #landing .ui-desktop-wire {
            --wire-bg: #f8fafc;
            --wire-panel: #ffffff;
            --wire-panel-soft: #f1f5f9;
            --wire-bg-fade-start: rgba(248, 250, 252, 0);
            --wire-line: #cbd5e1;
            --wire-line-strong: #94a3b8;
            --wire-fill: #e2e8f0;
            --wire-text: #64748b;
            --wire-ink: #334155;
            --wire-chip-bg: rgba(255, 255, 255, 0.76);
            --wire-speech-bg: rgba(255, 255, 255, 0.88);
            --wire-grid-opacity: 0.26;
            --wire-robot-opacity: 0.18;
         }

         html:not(.lm) #landing .ui-device-desktop.ui-desktop-shell {
            --desktop-preview-surface: #101827;
            --desktop-preview-outline: rgba(148, 163, 184, 0.28);
            border-color: rgba(148, 163, 184, 0.24);
         }

         html:not(.lm) #landing .ui-desktop-wire {
            --wire-bg: #101827;
            --wire-panel: #111827;
            --wire-panel-soft: #172033;
            --wire-bg-fade-start: rgba(16, 24, 39, 0);
            --wire-line: #334155;
            --wire-line-strong: #7c8da6;
            --wire-fill: #253247;
            --wire-text: #a7b4c8;
            --wire-ink: #e5edf7;
            --wire-chip-bg: rgba(15, 23, 42, 0.72);
            --wire-speech-bg: rgba(23, 32, 51, 0.92);
            --wire-grid-opacity: 0.34;
            --wire-robot-opacity: 0.28;
         }

         .ui-desktop-wire *,
         .ui-desktop-wire *::before,
         .ui-desktop-wire *::after {
            box-sizing: border-box;
         }

         .ui-desktop-wire-sidebar {
            min-width: 0;
            padding: 18px 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: var(--wire-panel);
            border-right: 1px solid var(--wire-line);
         }

         .ui-desktop-wire-brand {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--wire-line);
         }

         .ui-desktop-wire-mark,
         .ui-desktop-wire-avatar,
         .ui-desktop-wire-icon {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--wire-line);
            background: var(--wire-fill);
            color: var(--wire-text);
         }

         .ui-desktop-wire-mark {
            width: 36px;
            height: 36px;
            border-radius: 10px;
         }

         .ui-desktop-wire-brand strong,
         .ui-desktop-wire-title,
         .ui-desktop-wire-card-title,
         .ui-desktop-wire-stat-value,
         .ui-desktop-wire-session strong,
         .ui-desktop-wire-feedback strong {
            color: var(--wire-ink);
         }

         .ui-desktop-wire-brand span {
            display: block;
            color: var(--wire-text);
            font-size: 0.72rem;
            font-weight: 700;
         }

         .ui-desktop-wire-nav {
            display: grid;
            gap: 8px;
         }

         .ui-desktop-wire-nav-section {
            margin: 8px 2px 2px;
            color: var(--wire-line-strong);
            font-size: 0.58rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
         }

         .ui-desktop-wire-nav-item {
            min-height: 38px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 10px;
            border: 1px solid transparent;
            border-radius: 8px;
            color: var(--wire-text);
            font-size: 0.78rem;
            font-weight: 800;
         }

         .ui-desktop-wire-nav-item.active {
            background: var(--wire-bg);
            border-color: var(--wire-line);
            color: var(--wire-ink);
            box-shadow: inset 3px 0 0 var(--wire-line-strong);
         }

         .ui-desktop-wire-nav-item i {
            width: 16px;
            text-align: center;
         }

         .ui-desktop-wire-sidebar-card {
            margin-top: auto;
            padding: 13px;
            border: 1px solid var(--wire-line);
            border-radius: 8px;
            background: var(--wire-bg);
         }

         .ui-desktop-wire-avatar {
            width: 42px;
            height: 42px;
            margin-bottom: 10px;
            border-radius: 999px;
         }

         .ui-desktop-wire-line {
            display: block;
            height: 7px;
            border-radius: 999px;
            background: var(--wire-fill);
         }

         .ui-desktop-wire-line + .ui-desktop-wire-line {
            margin-top: 7px;
         }

         .ui-desktop-wire-main {
            min-width: 0;
            overflow: hidden;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background:
               linear-gradient(var(--wire-line) 1px, transparent 1px) 0 0 / 100% 42px,
               linear-gradient(90deg, var(--wire-line) 1px, transparent 1px) 0 0 / 48px 100%,
               var(--wire-bg);
         }

         .ui-desktop-wire-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
         }

         .ui-desktop-wire-kicker {
            margin-bottom: 4px;
            color: var(--wire-line-strong);
            font-size: 0.62rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
         }

         .ui-desktop-wire-title {
            margin: 0;
            font-size: 1.28rem;
            font-weight: 900;
            line-height: 1.1;
         }

         .ui-desktop-wire-subtitle {
            margin: 5px 0 0;
            color: var(--wire-text);
            font-size: 0.78rem;
            font-weight: 650;
         }

         .ui-desktop-wire-tools {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 8px;
         }

         .ui-desktop-wire-search {
            width: 210px;
            height: 36px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 11px;
            border: 1px solid var(--wire-line);
            border-radius: 999px;
            background: var(--wire-panel);
            color: var(--wire-text);
            font-size: 0.72rem;
            font-weight: 700;
         }

         .ui-desktop-wire-tool {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--wire-line);
            border-radius: 9px;
            background: var(--wire-panel);
            color: var(--wire-text);
         }

         .ui-desktop-wire-user-pill {
            min-width: 132px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 0 10px 0 6px;
            border: 1px solid var(--wire-line);
            border-radius: 999px;
            background: var(--wire-panel);
            color: var(--wire-text);
            font-size: 0.7rem;
            font-weight: 800;
         }

         .ui-desktop-wire-user-pill .ui-desktop-wire-avatar {
            width: 26px;
            height: 26px;
            margin: 0;
         }

         .ui-desktop-wire-content {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 322px;
            gap: 14px;
            min-width: 0;
         }

         .ui-desktop-wire-primary,
         .ui-desktop-wire-rail {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
         }

         .ui-desktop-wire-panel {
            min-width: 0;
            border: 1px solid var(--wire-line);
            border-radius: 8px;
            background: var(--wire-panel);
            overflow: hidden;
         }

         .ui-desktop-wire-welcome {
            position: relative;
            min-height: 168px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 270px;
            gap: 16px;
            padding: 18px;
         }

         .ui-desktop-wire-welcome::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
               linear-gradient(var(--wire-line) 1px, transparent 1px) 0 0 / 100% 34px,
               linear-gradient(90deg, var(--wire-line) 1px, transparent 1px) 0 0 / 42px 100%;
            opacity: var(--wire-grid-opacity);
            pointer-events: none;
         }

         .ui-desktop-wire-welcome-copy,
         .ui-desktop-wire-welcome-visual {
            position: relative;
            z-index: 2;
         }

         .ui-desktop-wire-welcome-title {
            margin: 0 0 12px;
            padding-left: 14px;
            border-left: 5px solid var(--wire-fill);
            color: var(--wire-ink);
            font-size: 1.18rem;
            font-weight: 900;
            line-height: 1.08;
            text-transform: uppercase;
         }

         .ui-desktop-wire-welcome-title span {
            display: block;
            color: var(--wire-line-strong);
         }

         .ui-desktop-wire-bullets {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 140px));
            gap: 8px 14px;
            margin: 0 0 14px;
            padding: 0;
            list-style: none;
         }

         .ui-desktop-wire-bullets li {
            display: flex;
            align-items: center;
            gap: 8px;
         }

         .ui-desktop-wire-bullets li::before {
            content: "";
            width: 6px;
            height: 6px;
            flex: 0 0 6px;
            border-radius: 999px;
            background: var(--wire-line-strong);
         }

         .ui-desktop-wire-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
         }

         .ui-desktop-wire-chip {
            min-height: 26px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 5px 10px;
            border: 1px solid var(--wire-line);
            border-radius: 7px;
            background: var(--wire-chip-bg);
            color: var(--wire-text);
            font-size: 0.68rem;
            font-weight: 800;
         }

         .ui-desktop-wire-chip::before {
            content: "";
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: var(--wire-fill);
            border: 1px solid var(--wire-line);
         }

         .ui-desktop-wire-speech {
            width: 190px;
            min-height: 76px;
            margin: 16px 0 0 auto;
            padding: 17px 16px;
            border: 1px solid var(--wire-line);
            border-radius: 15px;
            background: var(--wire-speech-bg);
         }

         .ui-desktop-wire-speech .ui-desktop-wire-line {
            height: 8px;
         }

         .ui-desktop-wire-robot {
            position: absolute;
            right: 6px;
            bottom: -8px;
            width: 132px;
            opacity: var(--wire-robot-opacity);
            filter: grayscale(1) saturate(0);
         }

         .ui-desktop-wire-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
         }

         .ui-desktop-wire-stat {
            min-height: 104px;
            padding: 11px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
         }

         .ui-desktop-wire-stat-head,
         .ui-desktop-wire-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
         }

         .ui-desktop-wire-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
         }

         .ui-desktop-wire-pill {
            min-width: 0;
            padding: 4px 10px;
            border: 1px solid var(--wire-line);
            border-radius: 999px;
            background: var(--wire-bg);
            color: var(--wire-text);
            font-size: 0.68rem;
            font-weight: 800;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
         }

         .ui-desktop-wire-stat-mark {
            width: 52px;
            height: 52px;
            margin: 12px auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--wire-line);
            border-radius: 999px;
            color: var(--wire-text);
            font-weight: 900;
         }

         .ui-desktop-wire-stat-value {
            font-size: 1.36rem;
            font-weight: 900;
            line-height: 1.05;
         }

         .ui-desktop-wire-stat-label {
            margin-top: 3px;
            color: var(--wire-text);
            font-size: 0.72rem;
            font-weight: 750;
            line-height: 1.15;
         }

         .ui-desktop-wire-underbar {
            height: 4px;
            margin: 11px -14px -14px;
            border-radius: 999px;
            background: var(--wire-line-strong);
         }

         .ui-desktop-wire-chart-panel,
         .ui-desktop-wire-feedback {
            padding: 16px;
         }

         .ui-desktop-wire-card-title {
            margin: 0;
            font-size: 0.88rem;
            font-weight: 900;
         }

         .ui-desktop-wire-card-subtitle {
            margin: 5px 0 0;
            color: var(--wire-text);
            font-size: 0.7rem;
            font-weight: 650;
         }

         .ui-desktop-wire-chart {
            height: 112px;
            margin-top: 12px;
            display: flex;
            align-items: end;
            gap: 12px;
            padding: 16px 14px 0 40px;
            background:
               linear-gradient(var(--wire-line) 1px, transparent 1px) left top / 100% 30px,
               linear-gradient(90deg, var(--wire-line) 1px, transparent 1px) 0 0 / 40px 100%;
         }

         .ui-desktop-wire-chart span {
            flex: 1;
            min-width: 0;
            border: 1px solid var(--wire-line);
            border-bottom: 0;
            border-radius: 6px 6px 0 0;
            background: var(--wire-fill);
         }

         .ui-desktop-wire-rail-card {
            padding: 15px;
         }

         .ui-desktop-wire-readiness {
            display: grid;
            grid-template-columns: 98px minmax(0, 1fr);
            gap: 14px;
            align-items: center;
         }

         .ui-desktop-wire-ring {
            width: 92px;
            height: 92px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 11px solid var(--wire-fill);
            border-radius: 999px;
            color: var(--wire-ink);
            text-align: center;
         }

         .ui-desktop-wire-ring strong {
            display: block;
            font-size: 1.22rem;
            line-height: 1;
         }

         .ui-desktop-wire-ring span {
            display: block;
            margin-top: 2px;
            color: var(--wire-text);
            font-size: 0.55rem;
            font-weight: 750;
         }

         .ui-desktop-wire-goals {
            display: grid;
            gap: 8px;
         }

         .ui-desktop-wire-goal {
            min-height: 38px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 10px;
            border: 1px solid var(--wire-line);
            border-radius: 7px;
            background: var(--wire-bg);
            color: var(--wire-text);
            font-size: 0.7rem;
            font-weight: 800;
         }

         .ui-desktop-wire-goal strong {
            color: var(--wire-ink);
         }

         .ui-desktop-wire-sessions {
            display: grid;
            gap: 8px;
            margin-top: 12px;
         }

         .ui-desktop-wire-session {
            min-height: 46px;
            display: grid;
            grid-template-columns: 32px minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border: 1px solid var(--wire-line);
            border-radius: 7px;
            background: var(--wire-bg);
            color: var(--wire-text);
         }

         .ui-desktop-wire-session span {
            display: block;
            font-size: 0.66rem;
            font-weight: 700;
         }

         .ui-desktop-wire-score {
            color: var(--wire-ink);
            font-weight: 900;
         }

         .ui-desktop-wire-feedback {
            display: grid;
            gap: 10px;
         }

         .ui-desktop-wire-bubble {
            padding: 11px 12px;
            border: 1px solid var(--wire-line);
            border-radius: 8px;
            background: var(--wire-bg);
            color: var(--wire-text);
            font-size: 0.72rem;
            font-weight: 650;
            line-height: 1.35;
         }

         .ui-desktop-wire-bubble.user {
            margin-left: 34px;
         }

         .ui-desktop-wire-bubble.ai {
            margin-right: 20px;
            background: var(--wire-panel-soft);
         }

         .ui-desktop-wire-dashboard-preview {
            display: flex;
            flex-direction: column;
            gap: 14px;
         }

         .ui-desktop-wire-summary-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 322px;
            gap: 14px;
         }

         .ui-desktop-wire-welcome-stack,
         .ui-desktop-wire-main-stack,
         .ui-desktop-wire-side-stack {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 14px;
         }

         .ui-desktop-wire-score-panel {
            min-height: 286px;
            padding: 13px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
         }

         .ui-desktop-wire-score-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
         }

         .ui-desktop-wire-status {
            min-width: 0;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 10px;
            border: 1px solid var(--wire-line);
            border-radius: 999px;
            background: var(--wire-bg);
            color: var(--wire-ink);
            font-size: 0.72rem;
            font-weight: 900;
         }

         .ui-desktop-wire-score-layout {
            display: grid;
            grid-template-columns: 116px minmax(0, 1fr);
            gap: 14px;
            align-items: center;
            margin: 12px 0 10px;
         }

         .ui-desktop-wire-score-layout .ui-desktop-wire-ring {
            width: 112px;
            height: 112px;
         }

         .ui-desktop-wire-score-meta {
            display: grid;
            gap: 10px;
         }

         .ui-desktop-wire-note {
            min-height: 38px;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 10px;
            border: 1px solid var(--wire-line);
            border-radius: 8px;
            background: var(--wire-bg);
            color: var(--wire-text);
            font-size: 0.72rem;
            font-weight: 750;
         }

         .ui-desktop-wire-dashboard-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 322px;
            gap: 14px;
         }

         .ui-desktop-wire-plan,
         .ui-desktop-wire-polished,
         .ui-desktop-wire-recommendations,
         .ui-desktop-wire-table-panel {
            padding: 13px;
         }

         .ui-desktop-wire-plan-head,
         .ui-desktop-wire-polished-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
         }

         .ui-desktop-wire-plan-list,
         .ui-desktop-wire-progress-list,
         .ui-desktop-wire-rec-list,
         .ui-desktop-wire-table-list {
            display: grid;
            gap: 9px;
         }

         .ui-desktop-wire-plan-row,
         .ui-desktop-wire-progress-row,
         .ui-desktop-wire-rec-row,
         .ui-desktop-wire-table-row {
            min-height: 46px;
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border: 1px solid var(--wire-line);
            border-radius: 8px;
            background: var(--wire-bg);
            color: var(--wire-text);
         }

         .ui-desktop-wire-progress-row {
            grid-template-columns: minmax(0, 1fr) 52px;
         }

         .ui-desktop-wire-progress-track {
            grid-column: 1 / -1;
            height: 6px;
            border-radius: 999px;
            background: var(--wire-fill);
            overflow: hidden;
         }

         .ui-desktop-wire-progress-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: var(--wire-line-strong);
         }

         .ui-desktop-wire-two-col {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
         }

         @media (min-width: 992px) and (max-width: 1199.98px) {
            .ui-desktop-wire {
               grid-template-columns: 190px minmax(0, 1fr);
               height: 470px;
               min-height: 0;
            }

            .ui-desktop-wire::after {
               left: 190px;
            }

            .ui-desktop-wire-sidebar {
               padding: 14px 12px;
            }

            .ui-desktop-wire-main {
               padding: 14px;
               gap: 12px;
            }

            .ui-desktop-wire-title {
               font-size: 1.1rem;
            }

            .ui-desktop-wire-subtitle {
               max-width: 440px;
            }

            .ui-desktop-wire-search {
               width: 160px;
            }

            .ui-desktop-wire-content {
               gap: 12px;
            }

            .ui-desktop-wire-summary-grid,
            .ui-desktop-wire-dashboard-shell {
               grid-template-columns: minmax(0, 1fr) 276px;
               gap: 12px;
            }

            .ui-desktop-wire-welcome {
               grid-template-columns: minmax(0, 1fr) 210px;
               min-height: 150px;
               padding: 15px;
            }

            .ui-desktop-wire-welcome-title {
               font-size: 1.02rem;
            }

            .ui-desktop-wire-speech {
               width: 160px;
            }

            .ui-desktop-wire-robot {
               width: 112px;
            }

            .ui-desktop-wire-stats {
               gap: 9px;
            }

            .ui-desktop-wire-stat {
               min-height: 94px;
               padding: 10px;
            }

            .ui-desktop-wire-stat-value {
               font-size: 1.1rem;
            }

            .ui-desktop-wire-underbar {
               margin: 9px -11px -11px;
            }

            .ui-desktop-wire-chart {
               height: 94px;
               gap: 8px;
            }

            .ui-desktop-wire-score-panel {
               min-height: 260px;
               padding: 12px;
            }

            .ui-desktop-wire-score-layout {
               grid-template-columns: 92px minmax(0, 1fr);
               gap: 11px;
            }

            .ui-desktop-wire-score-layout .ui-desktop-wire-ring {
               width: 88px;
               height: 88px;
            }

            .ui-desktop-wire-two-col {
               grid-template-columns: 1fr;
               gap: 12px;
            }
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

         html.lm #landing .ui-device-mobile {
            --mobile-preview-surface: #eef4fb;
            --mobile-preview-outline: rgba(37, 99, 235, 0.2);
            border-color: rgba(37, 99, 235, 0.18);
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.14);
         }

         html:not(.lm) #landing .ui-device-mobile {
            --mobile-preview-surface: #101827;
            --mobile-preview-outline: rgba(148, 163, 184, 0.34);
            border-color: rgba(148, 163, 184, 0.28);
            box-shadow: 0 18px 48px rgba(0, 0, 0, 0.44), 0 0 0 1px rgba(255, 255, 255, 0.08);
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
               gap: 5px !important;
               grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            #hero .d-flex.align-items-center.justify-content-center.gap-3.flex-wrap.hero-cta-row > .btn {
               min-height: 38px;
               padding-left: 4px !important;
               padding-right: 4px !important;
               font-size: 0.62rem !important;
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
      @include('mobile.partials.viewport-mobile-cookie')
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
                     <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: #ffffff; padding: 0; flex-shrink: 0;">
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
         @include('mobile.partials.user-command-palette', ['guestQuickNavigation' => true])

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
                @php
                   $previewReadiness = 85;
                   $previewInterviews = 6;
                   $previewClarity = 92;
                   $previewGrammar = 95;
                @endphp
                <div class="row align-items-center justify-content-center mt-4">
                  <div class="col-lg-7 col-md-10 text-center">
                     <h1 class="h1 afu" style="animation-delay:.12s">Practice Smarter.<br><span class="gt">Interview Better.</span></h1>
                     <p class="mx-auto afu" style="max-width:580px;font-size:clamp(.95rem,1.8vw,1.2rem);color:var(--tx2);margin-bottom:36px;animation-delay:.2s">SpeakReady AI offers simulated mock interviews, personalized feedback, and comprehensive coaching to help you land your dream opportunity.</p>
                     <div class="hero-cta-row d-flex align-items-center justify-content-center gap-3 flex-wrap afu" style="animation-delay:.28s">
                        <button class="bgrd btn px-4 py-3 fs-6" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('signup')">Get Started Free</button>
                        <button class="boc btn px-4 py-3 fs-6" id="heroInstallBtn"><i class="fa-solid fa-download me-2" style="color:var(--pur)"></i>Install App</button>
                        <a href="#features" class="boc btn px-4 py-3 fs-6">Learn More</a>
                     </div>
                    </div>
                 </div>

                <div class="hero-tech-card mt-3 mb-3 afu text-center" style="animation-delay:.4s">
                  <p class="hero-tech-title" style="font-size:.71rem;color:var(--hero-tech-color, #000000);text-transform:uppercase;letter-spacing:.12em;margin-bottom:14px">Featured Technologies</p>
                  <style>
                        .tech-icons a { color: inherit; text-decoration: none; display: flex; align-items: center; transition: all 0.2s ease; }
                        .tech-icons a:hover { transform: translateY(-3px) scale(1.1); color: var(--hero-tech-color, #000000); }
                  </style>
                  <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap tech-icons" style="color:var(--hero-tech-color, #000000); font-size:1.5rem;">
                      <a href="https://laravel.com" target="_blank" rel="noopener noreferrer" title="Laravel"><i class="fa-brands fa-laravel"></i></a>
                      <a href="https://php.net" target="_blank" rel="noopener noreferrer" title="PHP"><i class="fa-brands fa-php"></i></a>
                      <a href="https://www.mysql.com/" target="_blank" rel="noopener noreferrer" title="MySQL"><i class="fa-solid fa-database"></i></a>
                      @php
                          $title = 'OpenAI';
                          $link = 'https://openai.com';
                      @endphp
                      <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" title="{{ $title }}">
                          <i class="fa-solid fa-robot"></i>
                      </a>
                      <a href="https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API" target="_blank" rel="noopener noreferrer" title="Web Speech API"><i class="fa-solid fa-microphone"></i></a>
                   </div>
                </div>

                <div id="demo-preview" class="landing-section-heading mobile-demo-preview-heading text-center mt-4 mb-3 afu" style="animation-delay:.48s">
                  <span class="slbl">Demo Preview</span>
                  <h2 class="stitle">Inside <span class="gt">SpeakReady AI</span></h2>
                </div>

                <div class="row justify-content-center mt-3 mb-3">
                  <div class="col-lg-12 adi">
                     <div class="ui-showcase">
                        <div class="ui-device ui-device-mobile ui-device-mobile-image" aria-label="Mobile UI preview">
                           <div class="ui-device-bar">
                              <span class="ui-device-dot" style="background:#ff5f57"></span>
                              <span class="ui-device-dot" style="background:#ffbd2e"></span>
                              <span class="ui-device-dot" style="background:#28c840"></span>
                              <span class="ui-device-title">SpeakReady AI Mobile Dashboard</span>
                           </div>
                           <div class="swiper mobilePreviewSwiper mobile-preview-image-swiper">
                              @php
                                 $mobilePreviewSlides = [
                                    [
                                       'image' => 'img/mobile-preview-home-shell.png',
                                       'alt' => 'SpeakReady AI mobile home preview',
                                       'kicker' => 'Dashboard Overview',
                                       'title' => 'See your readiness at a glance.',
                                       'text' => 'Track your interview progress, practice streak, rating, and next goal from one clean mobile dashboard.',
                                       'points' => [
                                          'Check your overall readiness score',
                                          'View practice sessions and ratings',
                                          'Follow your next improvement goal',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-progress-shell.png',
                                       'alt' => 'SpeakReady AI mobile progress preview',
                                       'kicker' => 'Progress Tracking',
                                       'title' => 'Know what to improve next.',
                                       'text' => 'Review your streak, exported reports, AI insights, and a simple practice plan made for your interview growth.',
                                       'points' => [
                                          'Monitor streaks and total practice days',
                                          'Export progress as PDF or Excel',
                                          'Follow a personalized practice plan',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-setup-shell.png',
                                       'alt' => 'SpeakReady AI mobile interview setup preview',
                                       'kicker' => 'Interview Setup',
                                       'title' => 'Configure a focused mock interview.',
                                       'text' => 'Set your practice scenario, target position, and interview details before starting a tailored session.',
                                       'points' => [
                                          'Choose the interview scenario',
                                          'Add your target position',
                                          'Review each setup step clearly',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-feedback-center-shell.png',
                                       'alt' => 'SpeakReady AI mobile feedback center preview',
                                       'kicker' => 'Feedback Center',
                                       'title' => 'Review coaching feedback after practice.',
                                       'text' => 'Browse feedback summaries, priority recommendations, answer coaching, and history from the mobile shell.',
                                       'points' => [
                                          'Read AI feedback summaries',
                                          'See recommended next practice',
                                          'Review answer-by-answer coaching',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-modules-shell.png',
                                       'alt' => 'SpeakReady AI mobile interview modules preview',
                                       'kicker' => 'Interview Modules',
                                       'title' => 'Explore guided preparation modules.',
                                       'text' => 'Open learning paths and recommended lessons that keep interview preparation organized by topic.',
                                       'points' => [
                                          'Filter module topics',
                                          'Follow recommended lessons',
                                          'Track learning path progress',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-voice-rehearsal-shell.png',
                                       'alt' => 'SpeakReady AI mobile voice rehearsal preview',
                                       'kicker' => 'Voice Rehearsal',
                                       'title' => 'Practice answers out loud.',
                                       'text' => 'Record responses, check pacing, and review speaking metrics from the mobile interview practice screen.',
                                       'points' => [
                                          'Record spoken interview answers',
                                          'Switch prompts and confidence level',
                                          'Track duration, WPM, stability, and fillers',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-mission-mode-shell.png',
                                       'alt' => 'SpeakReady AI mobile mission mode preview',
                                       'kicker' => 'Mission Mode',
                                       'title' => 'Generate real-life practice tasks.',
                                       'text' => 'Turn target situations into mission tasks, then score written or spoken answers against the prompt.',
                                       'points' => [
                                          'Generate personalized mission tasks',
                                          'Practice with text or voice',
                                          'Score mission-specific answers',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-challenges-shell.png',
                                       'alt' => 'SpeakReady AI mobile interview challenges preview',
                                       'kicker' => 'Interview Challenges',
                                       'title' => 'Build skill through challenge journeys.',
                                       'text' => 'Complete gamified interview challenges with goals, question sets, skill rewards, and progress stats.',
                                       'points' => [
                                          'View level, XP, energy, and accuracy',
                                          'Follow challenge goals',
                                          'Complete success checklist items',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-readiness-coach-shell.png',
                                       'alt' => 'SpeakReady AI mobile readiness coach preview',
                                       'kicker' => 'Readiness Coach',
                                       'title' => 'Ask for focused interview help.',
                                       'text' => 'Use the coach chat for interview, resume, certificate, and practice guidance while keeping claims truthful.',
                                       'points' => [
                                          'Chat with the readiness coach',
                                          'Attach context when needed',
                                          'Send focused preparation questions',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-reports-shell.png',
                                       'alt' => 'SpeakReady AI mobile interview reports preview',
                                       'kicker' => 'Interview Reports',
                                       'title' => 'Review and export interview reports.',
                                       'text' => 'See report availability, start a scored interview, and access export actions from the mobile report screen.',
                                       'points' => [
                                          'Start a scored interview',
                                          'Review generated report status',
                                          'Export reports as PDF or Excel',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-personal-mastery-shell.png',
                                       'alt' => 'SpeakReady AI mobile personal mastery preview',
                                       'kicker' => 'Personal Mastery',
                                       'title' => 'Track private growth over time.',
                                       'text' => 'Follow personal bests, baseline growth, practice streaks, and recommended drills from one progress hub.',
                                       'points' => [
                                          'Compare baseline and latest score',
                                          'Start a scored mock interview',
                                          'Drill recommended weak areas',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-notifications-shell.png',
                                       'alt' => 'SpeakReady AI mobile notifications preview',
                                       'kicker' => 'Notifications',
                                       'title' => 'Stay current on activity and alerts.',
                                       'text' => 'View notification states and recent account activity in a mobile-friendly timeline.',
                                       'points' => [
                                          'Check progress alerts',
                                          'Review activity history',
                                          'Scan recent login events',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-account-shell.png',
                                       'alt' => 'SpeakReady AI mobile account management preview',
                                       'kicker' => 'Account Management',
                                       'title' => 'Manage profile and security settings.',
                                       'text' => 'Update profile details, target role, profile photo, and password fields from the mobile account screen.',
                                       'points' => [
                                          'Update account details',
                                          'Upload a profile picture',
                                          'Manage password settings',
                                       ],
                                    ],
                                 ];
                              @endphp
                              <div class="swiper-wrapper">
                                 @foreach($mobilePreviewSlides as $slide)
                                    <div class="swiper-slide mobile-preview-image-slide">
                                       <img class="mobile-preview-shell-img" src="{{ asset($slide['image']) }}" alt="{{ $slide['alt'] }}">
                                       <div class="mobile-preview-copy">
                                          <div class="mobile-preview-copy-kicker"><span>{{ $loop->iteration }}</span> {{ $slide['kicker'] }}</div>
                                          <h3 class="mobile-preview-copy-title">{{ $slide['title'] }}</h3>
                                          <p class="mobile-preview-copy-text">{{ $slide['text'] }}</p>
                                          <ul class="mobile-preview-copy-list">
                                             @foreach($slide['points'] as $point)
                                                <li><i class="fa-solid fa-check"></i>{{ $point }}</li>
                                             @endforeach
                                          </ul>
                                       </div>
                                    </div>
                                 @endforeach
                              </div>
                              <div class="swiper-pagination mobile-preview-pagination"></div>
                              <button type="button" class="mobile-preview-autoplay-toggle" aria-label="Pause demo preview">
                                 <i class="fa-solid fa-pause" aria-hidden="true"></i>
                              </button>
                              <div class="swiper-button-next mobile-preview-next"></div>
                              <div class="swiper-button-prev mobile-preview-prev"></div>
                           </div>
                            <div class="ui-dashboard ui-dashboard-preview ui-mobile-wire d-none" aria-hidden="true">
                               <div class="ui-mobile-wire-topbar">
                                  <div class="ui-mobile-wire-brand">
                                     <span class="ui-mobile-wire-logo" aria-hidden="true"></span>
                                     <span>SpeakReady AI</span>
                                  </div>
                                  <div class="ui-mobile-wire-actions" aria-hidden="true">
                                     <span class="ui-mobile-wire-action"><i class="fa-solid fa-play"></i></span>
                                     <span class="ui-mobile-wire-action"><i class="fa-solid fa-expand"></i></span>
                                     <span class="ui-mobile-wire-action"><i class="fa-solid fa-gear"></i></span>
                                     <span class="ui-mobile-wire-action"><i class="fa-regular fa-bell"></i></span>
                                     <span class="ui-mobile-wire-avatar">U</span>
                                  </div>
                               </div>

                               <div class="ui-mobile-wire-body">
                                  <section class="ui-mobile-wire-hero">
                                     <h3 class="ui-mobile-wire-hero-title">Practice Smarter.<span>Interview Better.</span></h3>
                                     <ul class="ui-mobile-wire-keywords" aria-hidden="true">
                                        <li><span class="ui-mobile-wire-line" style="width:36px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:34px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:42px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:28px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:38px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:44px"></span></li>
                                     </ul>
                                     <div class="ui-mobile-wire-chips" aria-hidden="true">
                                        <span class="ui-mobile-wire-chip"><span class="ui-mobile-wire-dot"></span>Job</span>
                                        <span class="ui-mobile-wire-chip"><span class="ui-mobile-wire-dot"></span>OJT</span>
                                        <span class="ui-mobile-wire-chip"><span class="ui-mobile-wire-dot"></span>Scholarship</span>
                                     </div>
                                     <div class="ui-mobile-wire-speech" aria-hidden="true">
                                        <span class="ui-mobile-wire-line" style="width:44px"></span>
                                        <span class="ui-mobile-wire-line" style="width:58px"></span>
                                        <span class="ui-mobile-wire-line" style="width:50px"></span>
                                     </div>
                                     <img class="ui-mobile-wire-robot" src="{{ asset('img/dashboard-welcome-robot-transparent.png') }}" alt="" aria-hidden="true">
                                  </section>

                                  <div class="ui-mobile-wire-progress" aria-hidden="true"><span></span></div>

                                  <div class="ui-mobile-wire-summary">
                                     <section class="ui-mobile-wire-card ui-mobile-wire-score">
                                        <div class="ui-mobile-wire-card-title"><i class="fa-solid fa-arrow-trend-up"></i> Building Momentum</div>
                                        <div class="ui-mobile-wire-ring">
                                           <div>
                                              <strong>0%</strong>
                                              <span>Overall Readiness</span>
                                           </div>
                                        </div>
                                        <div class="ui-mobile-wire-mini-list">
                                           <div class="ui-mobile-wire-mini">
                                              <div>
                                                 <span>Average Rating</span>
                                                 <strong>0/5</strong>
                                              </div>
                                              <i class="fa-regular fa-star"></i>
                                           </div>
                                           <div class="ui-mobile-wire-mini">
                                              <div>
                                                 <span>Next Goal</span>
                                                 <strong>50%</strong>
                                              </div>
                                              <i class="fa-solid fa-bullseye"></i>
                                           </div>
                                        </div>
                                     </section>

                                     <section class="ui-mobile-wire-card ui-mobile-wire-stat">
                                        <div class="ui-mobile-wire-stat-head">
                                           <span class="ui-mobile-wire-icon"><i class="fa-solid fa-microphone"></i></span>
                                           <span class="ui-mobile-wire-pill">Practice</span>
                                        </div>
                                        <div class="ui-mobile-wire-stat-mark"><i class="fa-solid fa-arrow-trend-up"></i></div>
                                        <div>
                                           <div class="ui-mobile-wire-stat-value">0</div>
                                           <div class="ui-mobile-wire-stat-label">Completed sessions</div>
                                        </div>
                                        <div class="ui-mobile-wire-underbar"></div>
                                     </section>

                                     <section class="ui-mobile-wire-card ui-mobile-wire-stat">
                                        <div class="ui-mobile-wire-stat-head">
                                           <span class="ui-mobile-wire-icon"><i class="fa-regular fa-star"></i></span>
                                           <span class="ui-mobile-wire-pill">Quality</span>
                                        </div>
                                        <div class="ui-mobile-wire-stat-mark">0</div>
                                        <div>
                                           <div class="ui-mobile-wire-stat-value">0/5</div>
                                           <div class="ui-mobile-wire-stat-label">Average rating</div>
                                        </div>
                                        <div class="ui-mobile-wire-underbar"></div>
                                     </section>

                                     <section class="ui-mobile-wire-card ui-mobile-wire-stat">
                                        <div class="ui-mobile-wire-stat-head">
                                           <span class="ui-mobile-wire-icon"><i class="fa-solid fa-bolt"></i></span>
                                           <span class="ui-mobile-wire-pill">Growth</span>
                                        </div>
                                        <div class="ui-mobile-wire-stat-mark">Lv. 1</div>
                                        <div>
                                           <div class="ui-mobile-wire-stat-value">0</div>
                                           <div class="ui-mobile-wire-stat-label">Experience points</div>
                                        </div>
                                        <div class="ui-mobile-wire-underbar"></div>
                                     </section>

                                     <section class="ui-mobile-wire-card ui-mobile-wire-stat">
                                        <div class="ui-mobile-wire-stat-head">
                                           <span class="ui-mobile-wire-icon"><i class="fa-solid fa-fire"></i></span>
                                           <span class="ui-mobile-wire-pill">Streak</span>
                                        </div>
                                        <div class="ui-mobile-wire-stat-mark"><i class="fa-regular fa-calendar-days"></i></div>
                                        <div>
                                           <div class="ui-mobile-wire-stat-value">0</div>
                                           <div class="ui-mobile-wire-stat-label">Active practice days</div>
                                        </div>
                                        <div class="ui-mobile-wire-underbar"></div>
                                     </section>
                                  </div>

                                  <section class="ui-mobile-wire-trend">
                                     <div class="ui-mobile-wire-trend-head">
                                        <span class="ui-mobile-wire-icon"><i class="fa-solid fa-chart-line"></i></span>
                                        <h3 class="ui-mobile-wire-trend-title">Readiness Trend</h3>
                                     </div>
                                     <p class="ui-mobile-wire-trend-copy">Recent completed Philippine interview sessions, scored from 0 to 100.</p>
                                     <div class="ui-mobile-wire-trend-actions">
                                        <span class="ui-mobile-wire-button">View Details <i class="fa-solid fa-chevron-right"></i></span>
                                        <span class="ui-mobile-wire-button">Recent 5 Sessions</span>
                                     </div>
                                     <div class="ui-mobile-wire-trend-metrics">
                                        <div class="ui-mobile-wire-metric">
                                           <i class="fa-solid fa-award"></i>
                                           <div>Average Score<strong>0/100</strong></div>
                                        </div>
                                        <div class="ui-mobile-wire-metric">
                                           <i class="fa-solid fa-arrow-up"></i>
                                           <div>Improvement<strong>+0%</strong></div>
                                        </div>
                                     </div>
                                     <div class="ui-mobile-wire-chart" aria-hidden="true"></div>
                                  </section>
                               </div>

                               <div class="ui-mobile-wire-nav" aria-hidden="true">
                                  <span class="ui-mobile-wire-nav-item active"><i class="fa-solid fa-house"></i>Home</span>
                                  <span class="ui-mobile-wire-nav-item"><i class="fa-solid fa-chart-line"></i>Progress</span>
                                  <span class="ui-mobile-wire-nav-item"><i class="fa-solid fa-microphone"></i>Interview</span>
                                  <span class="ui-mobile-wire-nav-item"><i class="fa-solid fa-clipboard-check"></i>Feedback</span>
                                  <span class="ui-mobile-wire-nav-item"><i class="fa-solid fa-table-cells-large"></i>More</span>
                               </div>
                            </div>
                         </div>

                        <div class="ui-device ui-device-desktop ui-desktop-shell" aria-label="Desktop UI preview">
                           <div class="ui-device-bar">
                              <span class="ui-device-dot" style="background:#ff5f57"></span>
                              <span class="ui-device-dot" style="background:#ffbd2e"></span>
                              <span class="ui-device-dot" style="background:#28c840"></span>
                              <span class="ui-device-title">SpeakReady AI Desktop Dashboard</span>
                           </div>
                           <div class="ui-dashboard ui-desktop-wire" aria-label="SpeakReady AI desktop dashboard wireframe preview">
                              <aside class="ui-desktop-wire-sidebar" aria-hidden="true">
                                 <div class="ui-desktop-wire-brand">
                                    <span class="ui-desktop-wire-mark"><i class="fa-solid fa-microphone-lines"></i></span>
                                    <div>
                                       <strong>SpeakReady AI</strong>
                                       <span>Interview Hub</span>
                                    </div>
                                 </div>

                                 <nav class="ui-desktop-wire-nav">
                                    <span class="ui-desktop-wire-nav-section">Dashboard</span>
                                    <span class="ui-desktop-wire-nav-item active"><i class="fa-solid fa-gauge-high"></i>Overview</span>
                                    <span class="ui-desktop-wire-nav-section">Interview Practice</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-microphone-lines"></i>Mock Interview</span>
                                    <span class="ui-desktop-wire-nav-section">Specialized Training</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-book-open-reader"></i>Modules</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-ear-listen"></i>Voice Rehearsal</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-route"></i>Missions</span>
                                    <span class="ui-desktop-wire-nav-section">Performance</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-chart-line"></i>Progress</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-clipboard-check"></i>Feedback</span>
                                 </nav>

                                 <div class="ui-desktop-wire-sidebar-card">
                                    <span class="ui-desktop-wire-avatar"><i class="fa-regular fa-user"></i></span>
                                    <span class="ui-desktop-wire-line" style="width:72%"></span>
                                    <span class="ui-desktop-wire-line" style="width:92%"></span>
                                    <span class="ui-desktop-wire-line" style="width:56%"></span>
                                 </div>
                              </aside>

                              <main class="ui-desktop-wire-main">
                                 <div class="ui-desktop-wire-top" aria-hidden="true">
                                    <span class="ui-desktop-wire-tool"><i class="fa-solid fa-bars"></i></span>
                                    <div class="ui-desktop-wire-tools" aria-hidden="true">
                                       <span class="ui-desktop-wire-tool"><i class="fa-solid fa-expand"></i></span>
                                       <span class="ui-desktop-wire-tool"><i class="fa-solid fa-circle-play"></i></span>
                                       <span class="ui-desktop-wire-tool"><i class="fa-solid fa-sun"></i></span>
                                       <span class="ui-desktop-wire-tool"><i class="fa-regular fa-bell"></i></span>
                                       <span class="ui-desktop-wire-user-pill">
                                          <span class="ui-desktop-wire-avatar">U</span>
                                          <span>User</span>
                                          <i class="fa-solid fa-chevron-down fa-xs"></i>
                                       </span>
                                    </div>
                                 </div>

                                 <div class="ui-desktop-wire-dashboard-preview">
                                    <section class="ui-desktop-wire-summary-grid">
                                       <div class="ui-desktop-wire-welcome-stack">
                                          <section class="ui-desktop-wire-panel ui-desktop-wire-welcome">
                                             <div class="ui-desktop-wire-welcome-copy">
                                                <h3 class="ui-desktop-wire-welcome-title">Practice Smarter.<span>Interview Better.</span></h3>
                                                <ul class="ui-desktop-wire-bullets" aria-hidden="true">
                                                   <li><span class="ui-desktop-wire-line" style="width:92px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:84px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:104px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:74px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:88px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:108px"></span></li>
                                                </ul>
                                                <div class="ui-desktop-wire-chips">
                                                   <span class="ui-desktop-wire-chip">Job</span>
                                                   <span class="ui-desktop-wire-chip">BPO</span>
                                                   <span class="ui-desktop-wire-chip">IT</span>
                                                   <span class="ui-desktop-wire-chip">Scholarship</span>
                                                   <span class="ui-desktop-wire-chip">Admission</span>
                                                </div>
                                             </div>
                                             <div class="ui-desktop-wire-welcome-visual" aria-hidden="true">
                                                <div class="ui-desktop-wire-speech">
                                                   <span class="ui-desktop-wire-line" style="width:62%"></span>
                                                   <span class="ui-desktop-wire-line" style="width:86%"></span>
                                                   <span class="ui-desktop-wire-line" style="width:72%"></span>
                                                </div>
                                                <img class="ui-desktop-wire-robot" src="{{ asset('img/dashboard-welcome-robot-transparent.png') }}" alt="">
                                             </div>
                                          </section>

                                          <div class="ui-desktop-wire-stats">
                                             <section class="ui-desktop-wire-panel ui-desktop-wire-stat">
                                                <div class="ui-desktop-wire-stat-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-microphone"></i></span>
                                                   <span class="ui-desktop-wire-pill">Practice</span>
                                                </div>
                                                <div class="ui-desktop-wire-stat-mark"><i class="fa-solid fa-arrow-trend-up"></i></div>
                                                <div>
                                                   <div class="ui-desktop-wire-stat-value">{{ $previewInterviews }}</div>
                                                   <div class="ui-desktop-wire-stat-label">Completed sessions</div>
                                                </div>
                                                <div class="ui-desktop-wire-underbar"></div>
                                             </section>

                                             <section class="ui-desktop-wire-panel ui-desktop-wire-stat">
                                                <div class="ui-desktop-wire-stat-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-regular fa-star"></i></span>
                                                   <span class="ui-desktop-wire-pill">Quality</span>
                                                </div>
                                                <div class="ui-desktop-wire-stat-mark"><i class="fa-solid fa-award"></i></div>
                                                <div>
                                                   <div class="ui-desktop-wire-stat-value">4<span style="font-size:.72rem">/5</span></div>
                                                   <div class="ui-desktop-wire-stat-label">Average rating</div>
                                                </div>
                                                <div class="ui-desktop-wire-underbar"></div>
                                             </section>

                                             <section class="ui-desktop-wire-panel ui-desktop-wire-stat">
                                                <div class="ui-desktop-wire-stat-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-bolt"></i></span>
                                                   <span class="ui-desktop-wire-pill">Growth</span>
                                                </div>
                                                <div class="ui-desktop-wire-stat-mark">Lv. 1</div>
                                                <div>
                                                   <div class="ui-desktop-wire-stat-value">320</div>
                                                   <div class="ui-desktop-wire-stat-label">Experience points</div>
                                                </div>
                                                <div class="ui-desktop-wire-underbar"></div>
                                             </section>

                                             <section class="ui-desktop-wire-panel ui-desktop-wire-stat">
                                                <div class="ui-desktop-wire-stat-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-fire"></i></span>
                                                   <span class="ui-desktop-wire-pill">Streak</span>
                                                </div>
                                                <div class="ui-desktop-wire-stat-mark"><i class="fa-regular fa-calendar-days"></i></div>
                                                <div>
                                                   <div class="ui-desktop-wire-stat-value">5</div>
                                                   <div class="ui-desktop-wire-stat-label">Active practice days</div>
                                                </div>
                                                <div class="ui-desktop-wire-underbar"></div>
                                             </section>
                                          </div>
                                       </div>

                                       <section class="ui-desktop-wire-panel ui-desktop-wire-score-panel">
                                          <div class="ui-desktop-wire-score-top">
                                             <span class="ui-desktop-wire-status"><i class="fa-solid fa-circle-check"></i> Interview Ready</span>
                                             <span class="ui-desktop-wire-pill"><i class="fa-solid fa-location-dot"></i> PH Focus</span>
                                          </div>
                                          <div class="ui-desktop-wire-score-layout">
                                             <div class="ui-desktop-wire-ring">
                                                <div>
                                                   <strong>{{ $previewReadiness }}%</strong>
                                                   <span>Overall Readiness</span>
                                                </div>
                                             </div>
                                             <div class="ui-desktop-wire-score-meta">
                                                <div class="ui-desktop-wire-goal"><span>Average Rating</span><strong>4/5</strong></div>
                                                <div class="ui-desktop-wire-goal"><span>Next Goal</span><strong>90%</strong></div>
                                             </div>
                                          </div>
                                          <div class="ui-desktop-wire-note"><i class="fa-regular fa-star"></i> Keep practicing. You're on your way!</div>
                                       </section>
                                    </section>

                                    <section class="ui-desktop-wire-dashboard-shell">
                                       <div class="ui-desktop-wire-main-stack">
                                          <section class="ui-desktop-wire-panel ui-desktop-wire-chart-panel">
                                             <div class="ui-desktop-wire-card-head">
                                                <div>
                                                   <div class="ui-desktop-wire-card-head">
                                                      <span class="ui-desktop-wire-icon"><i class="fa-solid fa-chart-line"></i></span>
                                                      <h3 class="ui-desktop-wire-card-title">Readiness Trend</h3>
                                                   </div>
                                                   <p class="ui-desktop-wire-card-subtitle">Recent completed Philippine interview sessions, scored from 0 to 100.</p>
                                                </div>
                                                <span class="ui-desktop-wire-pill">Recent 10 Sessions</span>
                                             </div>
                                             <div class="ui-desktop-wire-goals" style="grid-template-columns:repeat(2,minmax(0,1fr));margin-top:12px;">
                                                <div class="ui-desktop-wire-goal"><span>Average Score</span><strong>{{ $previewReadiness }}/100</strong></div>
                                                <div class="ui-desktop-wire-goal"><span>Improvement</span><strong>+18%</strong></div>
                                             </div>
                                             <div class="ui-desktop-wire-chart" aria-hidden="true">
                                                <span style="height:44%"></span>
                                                <span style="height:52%"></span>
                                                <span style="height:58%"></span>
                                                <span style="height:68%"></span>
                                                <span style="height:72%"></span>
                                                <span style="height:84%"></span>
                                                <span style="height:92%"></span>
                                             </div>
                                          </section>

                                          <section class="ui-desktop-wire-panel ui-desktop-wire-plan">
                                             <div class="ui-desktop-wire-plan-head">
                                                <span class="ui-desktop-wire-icon"><i class="fa-solid fa-calendar-check"></i></span>
                                                <div>
                                                   <h3 class="ui-desktop-wire-card-title">Personalized Practice Plan</h3>
                                                   <p class="ui-desktop-wire-card-subtitle">A plan built from latest scores, voice work, and learning progress.</p>
                                                </div>
                                             </div>
                                             <div class="ui-desktop-wire-plan-list">
                                                <div class="ui-desktop-wire-plan-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-microphone"></i></span>
                                                   <div><strong>Day 1</strong><span>Practice STAR answer structure</span></div>
                                                   <span class="ui-desktop-wire-pill">12 min</span>
                                                </div>
                                                <div class="ui-desktop-wire-plan-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-ear-listen"></i></span>
                                                   <div><strong>Day 2</strong><span>Voice rehearsal and pacing</span></div>
                                                   <span class="ui-desktop-wire-pill">8 min</span>
                                                </div>
                                             </div>
                                          </section>

                                          <div class="ui-desktop-wire-two-col">
                                             <section class="ui-desktop-wire-panel ui-desktop-wire-polished">
                                                <div class="ui-desktop-wire-polished-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-layer-group"></i></span>
                                                   <div>
                                                      <h3 class="ui-desktop-wire-card-title">Category Performance</h3>
                                                      <p class="ui-desktop-wire-card-subtitle">Where interview scores are strongest.</p>
                                                   </div>
                                                </div>
                                                <div class="ui-desktop-wire-progress-list">
                                                   <div class="ui-desktop-wire-progress-row"><span>Job Interview</span><strong>88%</strong><div class="ui-desktop-wire-progress-track"><span style="width:88%"></span></div></div>
                                                   <div class="ui-desktop-wire-progress-row"><span>BPO Interview</span><strong>82%</strong><div class="ui-desktop-wire-progress-track"><span style="width:82%"></span></div></div>
                                                </div>
                                             </section>

                                             <section class="ui-desktop-wire-panel ui-desktop-wire-polished">
                                                <div class="ui-desktop-wire-polished-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-book-open-reader"></i></span>
                                                   <div>
                                                      <h3 class="ui-desktop-wire-card-title">Learning Progress</h3>
                                                      <p class="ui-desktop-wire-card-subtitle">Latest modules in progress.</p>
                                                   </div>
                                                </div>
                                                <div class="ui-desktop-wire-progress-list">
                                                   <div class="ui-desktop-wire-progress-row"><span>Interview Basics</span><strong>72%</strong><div class="ui-desktop-wire-progress-track"><span style="width:72%"></span></div></div>
                                                   <div class="ui-desktop-wire-progress-row"><span>Professional Tone</span><strong>64%</strong><div class="ui-desktop-wire-progress-track"><span style="width:64%"></span></div></div>
                                                </div>
                                             </section>
                                          </div>
                                       </div>

                                       <aside class="ui-desktop-wire-side-stack">
                                          <section class="ui-desktop-wire-panel ui-desktop-wire-feedback">
                                             <div class="ui-desktop-wire-card-head">
                                                <h3 class="ui-desktop-wire-card-title">AI Feedback Summary</h3>
                                                <span class="ui-desktop-wire-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                                             </div>
                                             <div class="ui-desktop-wire-bubble ai"><strong>Top Strengths</strong><br>Clear examples, good structure, and calm delivery.</div>
                                             <div class="ui-desktop-wire-bubble"><strong>Improve Next</strong><br>Add measurable results and tighter closing lines.</div>
                                          </section>

                                          <section class="ui-desktop-wire-panel ui-desktop-wire-recommendations">
                                             <div class="ui-desktop-wire-card-head">
                                                <h3 class="ui-desktop-wire-card-title">AI Recommendations</h3>
                                                <span class="ui-desktop-wire-pill">Personalized</span>
                                             </div>
                                             <div class="ui-desktop-wire-rec-list" style="margin-top:12px;">
                                                <div class="ui-desktop-wire-rec-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-lightbulb"></i></span>
                                                   <div><strong>Practice evidence mapping</strong><span>Use job details in answers.</span></div>
                                                   <i class="fa-solid fa-chevron-right"></i>
                                                </div>
                                                <div class="ui-desktop-wire-rec-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-robot"></i></span>
                                                   <div><strong>Ask Readiness Coach</strong><span>Refine one weak answer.</span></div>
                                                   <i class="fa-solid fa-chevron-right"></i>
                                                </div>
                                             </div>
                                          </section>

                                          <section class="ui-desktop-wire-panel ui-desktop-wire-table-panel">
                                             <div class="ui-desktop-wire-card-head">
                                                <h3 class="ui-desktop-wire-card-title">Recent Sessions</h3>
                                                <span class="ui-desktop-wire-pill">View Reports</span>
                                             </div>
                                             <div class="ui-desktop-wire-table-list" style="margin-top:12px;">
                                                <div class="ui-desktop-wire-table-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-briefcase"></i></span>
                                                   <div><strong>Job Interview</strong><span>Behavioral practice</span></div>
                                                   <span class="ui-desktop-wire-score">92</span>
                                                </div>
                                                <div class="ui-desktop-wire-table-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-headset"></i></span>
                                                   <div><strong>BPO Interview</strong><span>Customer scenario</span></div>
                                                   <span class="ui-desktop-wire-score">84</span>
                                                </div>
                                             </div>
                                          </section>
                                       </aside>
                                    </section>
                                 </div>
                              </main>
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
                     <div class="about-system-panel">
                        <p class="about-system-copy" style="font-size:1.05rem;color:var(--tx2);margin-bottom:20px;">SpeakReady AI is an advanced, intelligent platform designed to help you prepare for Philippine interview scenarios, including job, BPO, IT, fresh graduate, scholarship, and college admission interviews. It provides immediate, evidence-linked feedback on answer quality and optional, non-scoring delivery coaching to reduce interview anxiety and make practice more focused.</p>

                        <h4 class="fs-5 mb-3 mt-4">Target Users</h4>
                        <div class="target-users-grid d-flex flex-wrap gap-2 mb-4">
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-user-graduate me-2"></i>Students</span>
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-graduation-cap me-2"></i>Fresh Graduates</span>
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-briefcase me-2"></i>Job Seekers</span>
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-award me-2"></i>Scholarship Applicants</span>
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-university me-2"></i>College Applicants</span>
                        </div>
                     </div>
                  </div>
                  <div class="col-lg-6 rv" style="transition-delay:.1s">
                     <!-- STATISTICS -->
                      <div class="row g-3 text-center landing-stats-row">
                         <div class="col-3" data-landing-stat="registered-users">
                            <div class="gc p-4 h-100">
                               <div class="pnum counter" style="font-size:2.5rem; color:var(--pur);">{{ data_get($landingStats ?? [], 'registered_users.display', '0') }}</div>
                               <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Registered Users</div>
                            </div>
                         </div>
                         <div class="col-3" data-landing-stat="interview-sessions">
                            <div class="gc p-4 h-100">
                               <div class="pnum counter" style="font-size:2.5rem; color:#34d399;">{{ data_get($landingStats ?? [], 'interview_sessions.display', '0') }}</div>
                               <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Interview Sessions</div>
                            </div>
                         </div>
                         <div class="col-3" data-landing-stat="questions-available">
                            <div class="gc p-4 h-100">
                               <div class="pnum counter" style="font-size:2.5rem; color:#f59e0b;">{{ data_get($landingStats ?? [], 'questions_available.display', '0') }}</div>
                               <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Questions Available</div>
                            </div>
                         </div>
                         <div class="col-3" data-landing-stat="feedback-generated">
                            <div class="gc p-4 h-100">
                               <div class="pnum counter" style="font-size:2.5rem; color:#3b82f6;">{{ data_get($landingStats ?? [], 'feedback_generated.display', '0') }}</div>
                               <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">AI Feedback Generated</div>
                            </div>
                         </div>
                         <div class="col-12 mt-3" data-landing-stat="success-rate">
                            <div class="gc p-4">
                               <div class="d-flex justify-content-center align-items-center gap-2">
                                 <div class="pnum" style="font-size:3rem; color:var(--pur);"><span class="counter">{{ data_get($landingStats ?? [], 'success_rate.display', '0') }}</span>%</div>
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
               <div class="swiper landingFeatureSwiper landing-auto-carousel features-auto-carousel" aria-label="Core features carousel">
                  <div class="row g-4 swiper-wrapper">
                  <div class="col-md-3 col-sm-6 rv swiper-slide">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#2563eb;--feature-icon-bg:rgba(37,99,235,.14);--feature-icon-border:rgba(37,99,235,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-gauge-high fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Dashboard Overview</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Monitor readiness scores, recent sessions, learning progress, and AI feedback summaries from one home base.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.05s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#10b981;--feature-icon-bg:rgba(16,185,129,.14);--feature-icon-border:rgba(16,185,129,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-microphone-lines fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Philippine AI Mock Interviews</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Practice with a realistic AI interviewer using role, category, difficulty, focus, and timed question settings.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#f59e0b;--feature-icon-bg:rgba(245,158,11,.14);--feature-icon-border:rgba(245,158,11,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-file-lines fa-lg"></i></div>
                         <h3 class="fs-6 fw-bold mb-2">Job Evidence Mapping</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Compare your resume and role details to focus practice on the skills a job asks for.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.15s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#8b5cf6;--feature-icon-bg:rgba(139,92,246,.14);--feature-icon-border:rgba(139,92,246,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-ear-listen fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Voice Rehearsal Studio</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Improve pacing, clarity, delivery stability, and filler-word control without treating speaking style as personality.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#ef4444;--feature-icon-bg:rgba(239,68,68,.14);--feature-icon-border:rgba(239,68,68,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-book-open-reader fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Interview Modules</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Study structured modules with chapters, resources, quizzes, and practice activities tied to interview skills.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.25s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#ec4899;--feature-icon-bg:rgba(236,72,153,.14);--feature-icon-border:rgba(236,72,153,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-gamepad fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Learning Games</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Complete challenge paths with levels, energy, lives, target tones, banned words, and score goals.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#06b6d4;--feature-icon-bg:rgba(6,182,212,.14);--feature-icon-border:rgba(6,182,212,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-robot fa-lg"></i></div>
                         <h3 class="fs-6 fw-bold mb-2">AI Practice Coach</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Get focused prep guidance, score explanations, and grounded advice without invented achievements.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.35s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#22c55e;--feature-icon-bg:rgba(34,197,94,.14);--feature-icon-border:rgba(34,197,94,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-clipboard-check fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Feedback Center</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">See evidence-linked rubrics, score confidence, fact-grounded revision templates, and targeted follow-ups.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.4s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#3b82f6;--feature-icon-bg:rgba(59,130,246,.14);--feature-icon-border:rgba(59,130,246,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-chart-line fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Progress Tracking</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Visualize readiness, STAR structure, skill breakdowns, learning progress, and voice rehearsal growth.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.45s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#0ea5e9;--feature-icon-bg:rgba(14,165,233,.14);--feature-icon-border:rgba(14,165,233,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-folder-open fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Reports &amp; Sharing</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Print detailed reviews and create expiring, password-protected links with reviewer permissions.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.5s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#6366f1;--feature-icon-bg:rgba(99,102,241,.14);--feature-icon-border:rgba(99,102,241,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-network-wired fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Skill Trees</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Earn leadership, communication, technical, and problem-solving XP, then unlock perks as you improve.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.55s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#eab308;--feature-icon-bg:rgba(234,179,8,.14);--feature-icon-border:rgba(234,179,8,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-trophy fa-lg"></i></div>
                         <h3 class="fs-6 fw-bold mb-2">Personal Mastery</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Compare only against your own assessment baseline, personal best, and competency growth.</p>
                     </div>
                  </div>
                  </div>
                  <div class="swiper-pagination landing-carousel-pagination landing-features-pagination"></div>
                  <button type="button" class="landing-carousel-autoplay-toggle landing-features-autoplay-toggle" aria-label="Pause core features carousel" data-pause-label="Pause core features carousel" data-play-label="Play core features carousel">
                     <i class="fa-solid fa-pause" aria-hidden="true"></i>
                  </button>
                  <div class="swiper-button-next landing-carousel-next landing-features-next"></div>
                  <div class="swiper-button-prev landing-carousel-prev landing-features-prev"></div>
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

               <div class="swiper landingHowSwiper landing-auto-carousel how-auto-carousel" aria-label="How it works carousel">
                  <div class="row g-4 justify-content-center swiper-wrapper">
                  <div class="col-md-4 col-sm-6 rv swiper-slide">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">1</div>
                        <h3 class="fs-5 fw-semibold mb-2">Create an Account</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Join the community and access your personalized dashboard.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">2</div>
                        <h3 class="fs-5 fw-semibold mb-2">Configure Your Setup</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Choose your target role, difficulty, and Philippine interview scenario.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">3</div>
                        <h3 class="fs-5 fw-semibold mb-2">Take a Philippine Mock Interview</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Face our interactive AI avatar with Philippine HR, BPO, IT, and fresh graduate questions.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">4</div>
                        <h3 class="fs-5 fw-semibold mb-2">Review AI Feedback</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Get instant, actionable evaluations on your performance.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.4s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">5</div>
                        <h3 class="fs-5 fw-semibold mb-2">Train & Rehearse</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Refine your skills using Voice Rehearsal and the AI Coach.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.5s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">6</div>
                        <h3 class="fs-5 fw-semibold mb-2">Track Your Progress</h3>
                         <p style="font-size:.875rem;color:var(--tx2)">Monitor competency growth, real interview outcomes, and your personal assessment baseline.</p>
                     </div>
                  </div>
                  </div>
                  <div class="swiper-pagination landing-carousel-pagination landing-how-pagination"></div>
                  <button type="button" class="landing-carousel-autoplay-toggle landing-how-autoplay-toggle" aria-label="Pause how it works carousel" data-pause-label="Pause how it works carousel" data-play-label="Play how it works carousel">
                     <i class="fa-solid fa-pause" aria-hidden="true"></i>
                  </button>
                  <div class="swiper-button-next landing-carousel-next landing-how-next"></div>
                  <div class="swiper-button-prev landing-carousel-prev landing-how-prev"></div>
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
                     <div class="swiper landingCategorySwiper landing-auto-carousel category-auto-carousel" aria-label="Interview categories carousel">
                        <div class="row g-3 swiper-wrapper">
                        <div class="col-sm-6 swiper-slide">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid var(--pur);">
                              <div style="font-size:2rem; margin-bottom:15px; color:var(--pur)"><i class="fa-solid fa-briefcase"></i></div>
                              <h4 class="fs-5 fw-bold">Job Interview</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Practice employment interviews across various industries.</p>
                           </div>
                        </div>
                        <div class="col-sm-6 swiper-slide">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #34d399;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#34d399"><i class="fa-solid fa-award"></i></div>
                              <h4 class="fs-5 fw-bold">Scholarship Interview</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Prepare for rigorous scholarship and grant applications.</p>
                           </div>
                        </div>
                        <div class="col-sm-6 swiper-slide">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #f59e0b;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#f59e0b"><i class="fa-solid fa-university"></i></div>
                              <h4 class="fs-5 fw-bold">College Admission</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Improve admission interview performance for top universities.</p>
                           </div>
                        </div>
                        <div class="col-sm-6 swiper-slide">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #3b82f6;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#3b82f6"><i class="fa-solid fa-laptop-code"></i></div>
                              <h4 class="fs-5 fw-bold">IT/Programming</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Practice technical, coding, and system design interviews.</p>
                           </div>
                        </div>
                        </div>
                        <div class="swiper-pagination landing-carousel-pagination landing-category-pagination"></div>
                        <button type="button" class="landing-carousel-autoplay-toggle landing-category-autoplay-toggle" aria-label="Pause interview categories carousel" data-pause-label="Pause interview categories carousel" data-play-label="Play interview categories carousel">
                           <i class="fa-solid fa-pause" aria-hidden="true"></i>
                        </button>
                        <div class="swiper-button-next landing-carousel-next landing-category-next"></div>
                        <div class="swiper-button-prev landing-carousel-prev landing-category-prev"></div>
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
               <div class="developers-grid">
                  <div class="developer-card-wrap rv">
                     <div class="gc p-4 h-100 developer-card">
                        <img src="{{ asset('img/dev1.png') }}" alt="Developer" class="developer-photo img-fluid rounded-circle mb-3" style="border: 4px solid var(--pur);">
                        <h6 class="fw-bold mb-1">Jonh Rogiel M. Tumanda</h6>
                        <p class="developer-role" style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Lead Programmer</p>
                        <p class="developer-bio" style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Core Code, Databases, and APIs.</p>
                     </div>
                  </div>
                  <div class="developer-card-wrap rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 developer-card">
                        <img src="{{ asset('img/dev2.png') }}" alt="Developer" class="developer-photo img-fluid rounded-circle mb-3" style="border: 4px solid #34d399;">
                        <h6 class="fw-bold mb-1">Karyl G. Gesto</h6>
                        <p class="developer-role" style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Manuscript Editor</p>
                        <p class="developer-bio" style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Technical Writing, Documentation, and Compliance.</p>
                     </div>
                  </div>
                  <div class="developer-card-wrap rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 developer-card">
                        <img src="{{ asset('img/dev3.png') }}" alt="Developer" class="developer-photo img-fluid rounded-circle mb-3" style="border: 4px solid #f59e0b;">
                        <h6 class="fw-bold mb-1">Eva Mae C. Cabilic</h6>
                        <p class="developer-role" style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">QA Tester</p>
                        <p class="developer-bio" style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Bug Hunting, Test Cases, and UX Stability.</p>
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
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">admin@speakready.ai</p>
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
                background: linear-gradient(180deg, var(--bg2) 0%, var(--bg3) 100%);
                position: relative;
                overflow: hidden;
                border-top: 1px solid var(--bd);
            }

            #foot::before {
                content: '';
                position: absolute;
                top: 0;
                left: 50%;
                width: min(620px, 82vw);
                height: 2px;
                transform: translateX(-50%);
                background: linear-gradient(90deg, transparent, var(--pur), #06b6d4, transparent);
                opacity: 0.55;
            }

            #foot .footer-shell {
                padding: 30px 0 16px;
            }

            #foot .footer-panel {
                display: grid;
                grid-template-columns: minmax(240px, 1.15fr) minmax(280px, 1.45fr) minmax(260px, 0.95fr);
                align-items: stretch;
                gap: 18px;
                padding: 20px;
                background: rgba(255, 255, 255, 0.035);
                border: 1px solid var(--bd);
                border-radius: 8px;
                box-shadow: 0 18px 48px rgba(2, 6, 23, 0.16);
            }

            html.lm #foot .footer-panel {
                background: rgba(255, 255, 255, 0.9);
                box-shadow: 0 18px 46px rgba(37, 99, 235, 0.08);
            }

            .footer-brand,
            .footer-action {
                min-width: 0;
            }

            .footer-brand-link {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 12px;
                color: var(--tx);
                text-decoration: none;
            }

            .footer-brand-link span {
                font-size: 1.18rem;
                font-weight: 800;
                line-height: 1;
                letter-spacing: 0;
            }

            .footer-logo {
                width: 34px !important;
                height: 34px !important;
                background: #ffffff !important;
                padding: 0 !important;
            }

            .footer-copy {
                max-width: 330px;
                margin: 0;
                color: var(--tx2);
                font-size: 0.88rem;
                line-height: 1.62;
            }

            .footer-nav-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
                min-width: 0;
            }
            .footer-heading {
                font-size: 0.76rem;
                font-weight: 800;
                color: var(--tx);
                margin-bottom: 0.72rem;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .footer-links {
                margin: 0;
                padding: 0;
            }

            .footer-links li {
                margin-bottom: 0.48rem;
            }

            .footer-links a {
                color: var(--tx2);
                text-decoration: none;
                font-size: 0.84rem;
                line-height: 1.35;
                transition: color 0.2s ease, transform 0.2s ease;
                display: inline-block;
            }

            .footer-links a:hover {
                color: var(--pur);
                transform: translateX(3px);
            }

            .footer-action {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 14px;
                padding: 14px;
                background: var(--bg);
                border: 1px solid var(--bd);
                border-radius: 8px;
            }

            .footer-action p {
                color: var(--tx2);
                font-size: 0.84rem;
                line-height: 1.55;
                margin: 0;
            }

            .footer-newsletter {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 42px;
                gap: 8px !important;
                margin: 0;
            }

            .footer-newsletter input {
                min-width: 0;
                height: 42px;
                background: var(--sf) !important;
                border: 1px solid var(--bd) !important;
                color: var(--tx) !important;
                padding: 0 12px !important;
                border-radius: 8px !important;
                font-size: 0.84rem !important;
                box-shadow: none !important;
            }

            .footer-newsletter input:focus {
                border-color: var(--pur) !important;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.16) !important;
            }

            .footer-newsletter-btn {
                width: 42px;
                height: 42px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0 !important;
                background: linear-gradient(135deg, var(--pur), #06b6d4);
                color: #fff;
                border: none;
                border-radius: 8px;
                transition: transform 0.2s, box-shadow 0.2s;
            }

            .footer-newsletter-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 10px 24px rgba(59, 130, 246, 0.28);
                color: #fff;
            }

            .footer-socials {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .footer-social-link {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                background: var(--bg3);
                border: 1px solid var(--bd);
                border-radius: 8px;
                color: var(--tx2);
                transition: transform 0.2s ease, border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
                text-decoration: none;
                font-size: 0.9rem;
            }

            .footer-social-link:hover {
                background: var(--pur);
                color: #fff;
                border-color: var(--pur);
                transform: translateY(-2px);
                box-shadow: 0 10px 22px rgba(59, 130, 246, 0.24);
            }

            .footer-bottom {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                border-top: 1px solid var(--bd);
                padding: 14px 2px 0;
                margin-top: 14px;
            }

            .footer-bottom p {
                color: var(--tx3);
                margin: 0;
                font-size: 0.8rem;
            }

            .footer-legal {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 9px;
                font-size: 0.8rem;
            }

            .footer-legal-link {
                color: var(--tx3);
                text-decoration: none;
                transition: color 0.2s ease;
            }

            .footer-legal-link:hover {
                color: var(--pur);
            }

            .footer-dot {
                width: 4px;
                height: 4px;
                border-radius: 50%;
                background: var(--bd2);
            }

            @media (max-width: 991.98px) {
                #foot .footer-panel {
                    grid-template-columns: 1fr;
                    gap: 16px;
                    padding: 18px;
                }

                .footer-copy {
                    max-width: none;
                }
            }

            @media (max-width: 575.98px) {
                #foot .footer-shell {
                    padding: 18px 0 12px;
                }

                #foot .footer-panel {
                    gap: 13px;
                    padding: 14px;
                }

                .footer-brand-link {
                    width: 100%;
                    justify-content: center;
                    margin-bottom: 10px;
                }

                .footer-copy {
                    text-align: justify;
                    text-align-last: center;
                    font-size: 0.8rem;
                    line-height: 1.5;
                }

                .footer-nav-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 12px;
                }

                .footer-heading {
                    margin-bottom: 0.55rem;
                    font-size: 0.66rem;
                }

                .footer-links li {
                    margin-bottom: 0.36rem;
                }

                .footer-links a {
                    font-size: 0.72rem;
                }

                .footer-action {
                    gap: 10px;
                    padding: 12px;
                }

                .footer-action p {
                    font-size: 0.76rem;
                }

                .footer-socials {
                    justify-content: center;
                }

                .footer-bottom {
                    justify-content: center;
                    text-align: center;
                    padding-top: 12px;
                    margin-top: 12px;
                }

                .footer-legal {
                    justify-content: center;
                    gap: 8px;
                }
            }
         </style>
         <footer id="foot">
            <div class="container footer-shell">
               <div class="footer-panel">
                  <div class="footer-brand">
                     <a class="footer-brand-link" href="#">
                        <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i footer-logo">
                        <span>SpeakReady AI</span>
                     </a>
                     <p class="footer-copy">Your personal Philippine interview coach for smarter practice, clearer feedback, and confident interview preparation.</p>
                  </div>
                  <nav class="footer-nav-grid" aria-label="Footer navigation">
                     <div>
                        <h5 class="footer-heading">Company</h5>
                        <ul class="list-unstyled footer-links">
                           <li><a href="#features">Features</a></li>
                           <li><a href="#about">About</a></li>
                           <li><a href="#how">How It Works</a></li>
                           <li><a href="#contact">Contact</a></li>
                           <li><a href="#faq">FAQ</a></li>
                        </ul>
                     </div>
                     <div>
                        <h5 class="footer-heading">Platform</h5>
                        <ul class="list-unstyled footer-links">
                           <li><a href="#" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('login')">Log In</a></li>
                           <li><a href="#" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('signup')">Register</a></li>
                           <li><a href="#benefits">Benefits</a></li>
                           <li><a href="#">Privacy Policy</a></li>
                           <li><a href="#">Terms of Service</a></li>
                        </ul>
                     </div>
                  </nav>
                  <div class="footer-action">
                     <div>
                        <h5 class="footer-heading">Stay Updated</h5>
                        <p>Get interview tips, feature updates, and practice reminders in one clean digest.</p>
                     </div>
                     <form class="footer-newsletter d-flex gap-2" onsubmit="event.preventDefault(); alert('Thanks for subscribing!');">
                         <input type="email" placeholder="Email address" class="form-control" required>
                         <button type="submit" class="btn footer-newsletter-btn fw-semibold px-3"><i class="fa-solid fa-paper-plane"></i></button>
                     </form>
                     <div class="footer-socials" aria-label="Social links">
                         <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                         <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="Twitter"><i class="fa-brands fa-twitter"></i></a>
                         <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                         <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="GitHub"><i class="fa-brands fa-github"></i></a>
                     </div>
                  </div>
               </div>
               <div class="footer-bottom">
                  <p>&copy; {{ date('Y') }} SpeakReady AI. All rights reserved.</p>
                  <div class="footer-legal">
                      <a href="#" class="footer-legal-link">Security</a>
                      <span class="footer-dot" aria-hidden="true"></span>
                      <a href="#" class="footer-legal-link">Cookie Preferences</a>
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
                     <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="width:30px;height:30px;background: #ffffff; padding: 0;">
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
               <a href="{{ route('auth.google.login') }}" class="oauth" data-auth-transition="google" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Log in with Google</a>
            </div>
            <!-- Sign Up -->
            <div id="fSignup" class="auth-panel" style="display:none">
               <form id="signupForm" action="{{ route('register') }}" method="POST">
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
               <a href="{{ route('auth.google.register') }}" class="oauth" data-auth-transition="google-register" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Sign up with Google</a>
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

      <button type="button" id="backToTopBtn" class="back-to-top-btn" aria-label="Back to top. Drag to move." title="Drag or tap to go back to top">
         <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
      </button>

      <style>
         /* Guest contrast guard: keeps landing, auth, splash, and mobile UI readable in both themes. */
         #landing,
         #landing :where(section, footer, .gc, .ui-device, .ui-sidebar, .ui-panel, .accordion-item),
         #lofc .modal-content,
         #pwa-install-prompt,
         #loginTransitionOverlay {
            color: var(--tx);
         }

         #landing :where(h1, h2, h3, h4, h5, h6, .stitle, .ui-main-title, .ui-panel-title, .footer-heading),
         #lofc :where(.modal-title, .olbl, .tab-sw-btn, label),
         #loginTransitionOverlay h4 {
            color: var(--tx) !important;
            -webkit-text-fill-color: var(--tx) !important;
         }

         #landing :where(p, .ssub, .ui-main-subtitle, .ui-stat-label, .ui-stat-note, .plbl, .footer-links a),
         #landing :where([style*="color:var(--tx2)"], [style*="color: var(--tx2)"]),
         #lofc :where(p, .odiv, .form-check-label),
         #loginTransitionOverlay p {
            color: var(--tx2) !important;
            -webkit-text-fill-color: var(--tx2) !important;
         }

         #landing :where(.guest-header-clock, [style*="color:var(--tx3)"], [style*="color: var(--tx3)"]),
         #lofc :where(.odiv) {
            color: var(--tx3) !important;
            -webkit-text-fill-color: var(--tx3) !important;
         }

         #landing .gt,
         #landing .slbl,
         #landing .slbl *,
         #landing .hbadge,
         #landing .pnum,
         #landing .ui-stat-value,
         #landing :where(.ftico, .ftico i, .tech-icons a:hover),
         #lofc :where(a, .password-toggle:hover i) {
            -webkit-text-fill-color: initial !important;
         }

         #landing .hbadge {
            color: var(--pur) !important;
            -webkit-text-fill-color: var(--pur) !important;
         }

         #landing .gt {
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent !important;
         }

         #landing :where(.gc, .feature-card, .accordion-item),
         #lofc .modal-content {
            background: var(--sf) !important;
            border-color: var(--bd) !important;
            box-shadow: 0 14px 34px rgba(2, 6, 23, 0.12);
         }

         html:not(.lm) #landing :where(.gc, .feature-card, .accordion-item),
         html:not(.lm) #lofc .modal-content {
            background: rgba(18, 18, 31, 0.96) !important;
            border-color: rgba(148, 163, 184, 0.18) !important;
            box-shadow: 0 18px 48px rgba(0, 0, 0, 0.32);
         }

         html.lm #landing :where(.gc, .feature-card, .accordion-item),
         html.lm #lofc .modal-content {
            background: #ffffff !important;
            border-color: rgba(37, 99, 235, 0.14) !important;
         }

         #landing .ftag,
         #landing .hnum,
         #landing .ftico,
         #landing .footer-social-link,
         #landing .ui-device-title,
         #landing .ui-side-item,
         #landing .contact-card,
         #lofc :where(.tab-switch, .oauth, .password-toggle) {
            background: var(--bg3) !important;
            border-color: var(--bd) !important;
            color: var(--tx) !important;
         }

         html:not(.lm) #landing :where(.ftag, .hnum, .ftico, .footer-social-link, .ui-device-title, .ui-side-item),
         html:not(.lm) #lofc :where(.tab-switch, .oauth, .password-toggle) {
            background: rgba(30, 41, 59, 0.74) !important;
            border-color: rgba(148, 163, 184, 0.18) !important;
         }

         html.lm #landing :where(.ftag, .hnum, .ftico, .footer-social-link, .ui-device-title, .ui-side-item),
         html.lm #lofc :where(.tab-switch, .oauth, .password-toggle) {
            background: #f8fafc !important;
            border-color: #dbeafe !important;
         }

         #landing .feature-card .ftico {
            background: var(--feature-icon-bg) !important;
            border-color: var(--feature-icon-border) !important;
            color: var(--feature-icon-color) !important;
         }

         #landing .feature-card .ftico i {
            color: var(--feature-icon-color) !important;
            -webkit-text-fill-color: var(--feature-icon-color) !important;
         }

         #landing :where(.boc, #thbtn, #mbtog, .nav-link),
         #pwa-install-prompt .pwa-btn-no {
            color: var(--tx) !important;
            border-color: var(--bd2) !important;
         }

         #landing :where(.boc, #thbtn, #mbtog):hover,
         #landing .nav-link.active,
         #landing .nav-link:hover,
         #pwa-install-prompt .pwa-btn-no:hover {
            background: rgba(59, 130, 246, 0.12) !important;
            color: var(--tx) !important;
            border-color: rgba(59, 130, 246, 0.38) !important;
         }

         #landing :where(.bgrd, .footer-newsletter-btn),
         #lofc :where(.bgrd, .btn-warning),
         #pwa-install-prompt .pwa-btn-yes {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         #landing :where(.form-control, .form-select, textarea),
         #lofc :where(.oinp, .form-control) {
            background: var(--bg) !important;
            border-color: var(--bd) !important;
            color: var(--tx) !important;
            caret-color: var(--pur);
         }

         html.lm #landing :where(.form-control, .form-select, textarea),
         html.lm #lofc :where(.oinp, .form-control) {
            background: #ffffff !important;
            border-color: #cbd5e1 !important;
         }

         #landing :where(.form-control, .form-select, textarea)::placeholder,
         #lofc :where(.oinp, .form-control)::placeholder {
            color: var(--tx3) !important;
            font-weight: 400 !important;
            opacity: 1;
         }

         #landing :where(.form-control, .form-select, textarea):focus,
         #lofc :where(.oinp, .form-control):focus {
            border-color: var(--pur) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18) !important;
         }

         #landing .acco .accordion-button {
            background: var(--sf) !important;
            color: var(--tx) !important;
            -webkit-text-fill-color: var(--tx) !important;
            border-color: var(--bd) !important;
            box-shadow: none !important;
         }

         #landing .acco .accordion-button:not(.collapsed) {
            background: rgba(59, 130, 246, 0.14) !important;
            color: var(--tx) !important;
         }

         #landing .acco .accordion-body {
            background: var(--bg3) !important;
            color: var(--tx2) !important;
            -webkit-text-fill-color: var(--tx2) !important;
            border-top: 1px solid var(--bd);
         }

         html.lm #landing .acco .accordion-body {
            background: #f8fafc !important;
         }

         #landing .accordion-button::after {
            filter: none;
         }

         html:not(.lm) #landing .accordion-button::after {
            filter: invert(1) grayscale(1) brightness(1.7);
         }

         #landing .tech-icons a,
         #landing .footer-social-link {
            color: var(--tx2) !important;
            -webkit-text-fill-color: var(--tx2) !important;
         }

         #landing .hero-tech-title,
         #landing .hero-tech-card .tech-icons a {
            color: var(--hero-tech-color, #000000) !important;
            -webkit-text-fill-color: var(--hero-tech-color, #000000) !important;
         }

         #landing .footer-social-link:hover,
         #landing .footer-social-link:hover i {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         #landing .ui-device {
            background: var(--sf) !important;
            border-color: var(--bd2) !important;
         }

         #landing :where(.ui-dashboard, .ui-main, .ui-panel, .ui-stat, .ui-mini-card, .ui-chart-card) {
            color: var(--tx) !important;
         }

         #landing .ui-device-mobile:not(.ui-device-mobile-image) {
            background: var(--mobile-preview-surface) !important;
            border-color: var(--mobile-preview-outline) !important;
         }

         #landing .ui-device-mobile.ui-device-mobile-image {
            background: transparent !important;
            border-color: transparent !important;
            box-shadow: none !important;
         }

         #landing .ui-device-mobile .ui-device-title {
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
         }

         #landing .ui-device-mobile .ui-dashboard-preview {
            background: var(--mobile-preview-surface) !important;
            box-shadow: inset 0 0 0 1px var(--mobile-preview-outline) !important;
         }

         #landing .ui-mobile-wire {
            background: var(--wire-bg) !important;
            color: var(--wire-text) !important;
            -webkit-text-fill-color: var(--wire-text) !important;
         }

         #landing .ui-mobile-wire :where(.ui-mobile-wire-topbar, .ui-mobile-wire-card, .ui-mobile-wire-trend, .ui-mobile-wire-nav, .ui-mobile-wire-action, .ui-mobile-wire-avatar) {
            background: var(--wire-panel) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-mobile-wire :where(.ui-mobile-wire-mini, .ui-mobile-wire-pill, .ui-mobile-wire-button, .ui-mobile-wire-metric, .ui-mobile-wire-trend-head .ui-mobile-wire-icon, .ui-mobile-wire-nav-item.active) {
            background: var(--wire-bg) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-mobile-wire :where(.ui-mobile-wire-logo, .ui-mobile-wire-dot, .ui-mobile-wire-line, .ui-mobile-wire-mini i, .ui-mobile-wire-stat .ui-mobile-wire-icon) {
            background: var(--wire-fill) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-mobile-wire :where(.ui-mobile-wire-brand, .ui-mobile-wire-hero-title, .ui-mobile-wire-card-title, .ui-mobile-wire-stat-head, .ui-mobile-wire-ring, .ui-mobile-wire-mini strong, .ui-mobile-wire-stat-value, .ui-mobile-wire-trend-title, .ui-mobile-wire-metric strong) {
            color: var(--wire-ink) !important;
            -webkit-text-fill-color: var(--wire-ink) !important;
         }

         #landing .ui-mobile-wire :where(.ui-mobile-wire-hero-title span, .ui-mobile-wire-card-title i) {
            color: var(--wire-line-strong) !important;
            -webkit-text-fill-color: var(--wire-line-strong) !important;
         }

         #landing .ui-mobile-wire :where(.ui-mobile-wire-action, .ui-mobile-wire-avatar, .ui-mobile-wire-icon, .ui-mobile-wire-chip, .ui-mobile-wire-mini span, .ui-mobile-wire-ring span, .ui-mobile-wire-stat-mark, .ui-mobile-wire-stat-label, .ui-mobile-wire-trend-copy, .ui-mobile-wire-button, .ui-mobile-wire-metric, .ui-mobile-wire-nav-item) {
            color: var(--wire-text) !important;
            -webkit-text-fill-color: var(--wire-text) !important;
         }

         #landing .ui-mobile-wire .ui-mobile-wire-chip {
            background: var(--wire-chip-bg) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-mobile-wire .ui-mobile-wire-speech {
            background: var(--wire-speech-bg) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-device-desktop.ui-desktop-shell {
            background: var(--desktop-preview-surface) !important;
            border-color: var(--desktop-preview-outline) !important;
         }

         #landing .ui-desktop-wire {
            background: var(--wire-bg) !important;
            color: var(--wire-text) !important;
            -webkit-text-fill-color: var(--wire-text) !important;
         }

         #landing .ui-desktop-wire .ui-desktop-wire-main {
            background:
               linear-gradient(var(--wire-line) 1px, transparent 1px) 0 0 / 100% 42px,
               linear-gradient(90deg, var(--wire-line) 1px, transparent 1px) 0 0 / 48px 100%,
               var(--wire-bg) !important;
         }

         #landing .ui-desktop-wire :where(.ui-desktop-wire-sidebar, .ui-desktop-wire-panel, .ui-desktop-wire-search, .ui-desktop-wire-tool, .ui-desktop-wire-user-pill) {
            background: var(--wire-panel) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-desktop-wire :where(.ui-desktop-wire-sidebar-card, .ui-desktop-wire-nav-item.active, .ui-desktop-wire-pill, .ui-desktop-wire-goal, .ui-desktop-wire-session, .ui-desktop-wire-bubble, .ui-desktop-wire-status, .ui-desktop-wire-note, .ui-desktop-wire-plan-row, .ui-desktop-wire-progress-row, .ui-desktop-wire-rec-row, .ui-desktop-wire-table-row, .ui-desktop-wire-progress-track) {
            background: var(--wire-bg) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-desktop-wire :where(.ui-desktop-wire-mark, .ui-desktop-wire-avatar, .ui-desktop-wire-icon, .ui-desktop-wire-line, .ui-desktop-wire-chart span) {
            background: var(--wire-fill) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-desktop-wire .ui-desktop-wire-chip {
            background: var(--wire-chip-bg) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-desktop-wire .ui-desktop-wire-chip::before {
            background: var(--wire-fill) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-desktop-wire .ui-desktop-wire-progress-track span {
            background: var(--wire-line-strong) !important;
         }

         #landing .ui-desktop-wire .ui-desktop-wire-speech {
            background: var(--wire-speech-bg) !important;
            border-color: var(--wire-line) !important;
         }

         #landing .ui-desktop-wire .ui-desktop-wire-bubble.ai {
            background: var(--wire-panel-soft) !important;
         }

         #landing .ui-desktop-wire :where(.ui-desktop-wire-brand strong, .ui-desktop-wire-title, .ui-desktop-wire-welcome-title, .ui-desktop-wire-card-title, .ui-desktop-wire-stat-value, .ui-desktop-wire-ring, .ui-desktop-wire-goal strong, .ui-desktop-wire-session strong, .ui-desktop-wire-score, .ui-desktop-wire-feedback strong, .ui-desktop-wire-status, .ui-desktop-wire-plan-row strong, .ui-desktop-wire-progress-row strong, .ui-desktop-wire-rec-row strong, .ui-desktop-wire-table-row strong) {
            color: var(--wire-ink) !important;
            -webkit-text-fill-color: var(--wire-ink) !important;
         }

         #landing .ui-desktop-wire :where(.ui-desktop-wire-kicker, .ui-desktop-wire-welcome-title span, .ui-desktop-wire-nav-section, .ui-desktop-wire-nav-item.active, .ui-desktop-wire-stat-mark) {
            color: var(--wire-line-strong) !important;
            -webkit-text-fill-color: var(--wire-line-strong) !important;
         }

         #landing .ui-desktop-wire :where(.ui-desktop-wire-brand span, .ui-desktop-wire-subtitle, .ui-desktop-wire-search, .ui-desktop-wire-tool, .ui-desktop-wire-user-pill, .ui-desktop-wire-nav-item, .ui-desktop-wire-chip, .ui-desktop-wire-pill, .ui-desktop-wire-stat-label, .ui-desktop-wire-card-subtitle, .ui-desktop-wire-ring span, .ui-desktop-wire-goal, .ui-desktop-wire-session, .ui-desktop-wire-bubble, .ui-desktop-wire-note, .ui-desktop-wire-plan-row, .ui-desktop-wire-progress-row, .ui-desktop-wire-rec-row, .ui-desktop-wire-table-row) {
            color: var(--wire-text) !important;
            -webkit-text-fill-color: var(--wire-text) !important;
         }

         #landing .ui-side-item.active,
         #landing .ui-main-chip {
            color: #bfdbfe !important;
            background: rgba(59, 130, 246, 0.18) !important;
         }

         html.lm #landing .ui-side-item.active,
         html.lm #landing .ui-main-chip {
            color: #1d4ed8 !important;
            background: #dbeafe !important;
         }

         #lofc .tab-sw-btn {
            color: var(--tx2) !important;
         }

         #lofc .tab-sw-btn.on {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            background: var(--grad) !important;
         }

         #lofc .password-toggle i {
            color: var(--tx2) !important;
         }

         #lofc .oauth {
            color: var(--tx) !important;
            -webkit-text-fill-color: var(--tx) !important;
         }

         #lofc .oauth i {
            -webkit-text-fill-color: #ea4335 !important;
         }

         #loginTransitionOverlay {
            background: var(--bg) !important;
         }

         #pwa-install-prompt {
            background: color-mix(in srgb, var(--sf) 94%, transparent) !important;
            border-color: var(--bd) !important;
         }

         @supports not (background: color-mix(in srgb, #fff 50%, transparent)) {
            #pwa-install-prompt {
               background: var(--sf) !important;
            }
         }
      </style>

<!-- ======================== SCRIPTS ======================== -->
      <!-- jQuery -->
      <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
      <!-- Bootstrap 5 -->
      <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
      @include('mobile.partials.flash-modal', ['includeValidationErrors' => false])
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

      <script src="{{ asset('js/main.js?v=7') }}"></script>
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
                 document.querySelectorAll(".mobilePreviewSwiper").forEach(function(previewEl) {
                     const previewSwiper = new Swiper(previewEl, {
                         slidesPerView: "auto",
                         centeredSlides: true,
                         spaceBetween: 0,
                         effect: "coverflow",
                         coverflowEffect: {
                             rotate: 0,
                             stretch: 96,
                             depth: 132,
                             modifier: 1,
                             scale: 0.92,
                             slideShadows: false,
                         },
                         loop: true,
                         watchSlidesProgress: true,
                         autoplay: {
                             delay: 3000,
                             disableOnInteraction: false,
                         },
                         pagination: {
                             el: previewEl.querySelector(".mobile-preview-pagination"),
                             clickable: true,
                         },
                         navigation: {
                             nextEl: previewEl.querySelector(".mobile-preview-next"),
                             prevEl: previewEl.querySelector(".mobile-preview-prev"),
                         },
                     });

                     const autoplayToggle = previewEl.querySelector(".mobile-preview-autoplay-toggle");
                     if (autoplayToggle) {
                         autoplayToggle.addEventListener("click", function() {
                             const icon = autoplayToggle.querySelector("i");
                             const paused = autoplayToggle.classList.toggle("is-paused");
                             if (paused) {
                                 previewSwiper.autoplay?.stop();
                                 autoplayToggle.setAttribute("aria-label", "Play demo preview");
                                 icon?.classList.remove("fa-pause");
                                 icon?.classList.add("fa-play");
                             } else {
                                 previewSwiper.autoplay?.start();
                                 autoplayToggle.setAttribute("aria-label", "Pause demo preview");
                                 icon?.classList.remove("fa-play");
                                 icon?.classList.add("fa-pause");
                             }
                         });
                     }
                 });

                 document.querySelectorAll(".landingFeatureSwiper, .landingHowSwiper, .landingCategorySwiper").forEach(function(carouselEl) {
                     const landingSwiper = new Swiper(carouselEl, {
                         slidesPerView: "auto",
                         centeredSlides: true,
                         spaceBetween: 16,
                         effect: "coverflow",
                         coverflowEffect: {
                             rotate: 0,
                             stretch: 62,
                             depth: 92,
                             modifier: 1,
                             scale: 0.94,
                             slideShadows: false,
                         },
                         loop: true,
                         watchSlidesProgress: true,
                         autoplay: {
                             delay: 2800,
                             disableOnInteraction: false,
                             pauseOnMouseEnter: true,
                         },
                         pagination: {
                             el: carouselEl.querySelector(".landing-carousel-pagination"),
                             clickable: true,
                         },
                         navigation: {
                             nextEl: carouselEl.querySelector(".landing-carousel-next"),
                             prevEl: carouselEl.querySelector(".landing-carousel-prev"),
                         },
                     });

                     const carouselToggle = carouselEl.querySelector(".landing-carousel-autoplay-toggle");
                     if (carouselToggle) {
                         carouselToggle.addEventListener("click", function() {
                             const icon = carouselToggle.querySelector("i");
                             const paused = carouselToggle.classList.toggle("is-paused");
                             if (paused) {
                                 landingSwiper.autoplay?.stop();
                                 carouselToggle.setAttribute("aria-label", carouselToggle.dataset.playLabel || "Play carousel");
                                 icon?.classList.remove("fa-pause");
                                 icon?.classList.add("fa-play");
                             } else {
                                 landingSwiper.autoplay?.start();
                                 carouselToggle.setAttribute("aria-label", carouselToggle.dataset.pauseLabel || "Pause carousel");
                                 icon?.classList.remove("fa-play");
                                 icon?.classList.add("fa-pause");
                             }
                         });
                     }
                 });

             }
         });
      </script>
      <!-- PWA Service Worker Registration -->
      <script>
         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js?v=10').then(function(registration) {
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

      <script>
         document.addEventListener('DOMContentLoaded', function() {
            const backToTopBtn = document.getElementById('backToTopBtn');
            if (!backToTopBtn) return;

            const edgePadding = 10;
            let hasCustomPosition = false;
            let suppressNextClick = false;
            const drag = {
               active: false,
               moved: false,
               pointerId: null,
               startX: 0,
               startY: 0,
               offsetX: 0,
               offsetY: 0
            };

            const clamp = function(value, min, max) {
               return Math.min(Math.max(value, min), max);
            };

            const placeBackToTop = function(left, top) {
               const width = backToTopBtn.offsetWidth || 48;
               const height = backToTopBtn.offsetHeight || 48;
               const maxLeft = Math.max(edgePadding, window.innerWidth - width - edgePadding);
               const maxTop = Math.max(edgePadding, window.innerHeight - height - edgePadding);

               backToTopBtn.style.left = clamp(left, edgePadding, maxLeft) + 'px';
               backToTopBtn.style.top = clamp(top, edgePadding, maxTop) + 'px';
               backToTopBtn.style.right = 'auto';
               backToTopBtn.style.bottom = 'auto';
               hasCustomPosition = true;
            };

            const keepBackToTopInView = function() {
               if (!hasCustomPosition) return;

               const rect = backToTopBtn.getBoundingClientRect();
               placeBackToTop(rect.left, rect.top);
            };

            const toggleBackToTop = function() {
               const demoTargets = [
                  document.getElementById('demo-preview'),
                  document.querySelector('.mobilePreviewSwiper')
               ].filter(Boolean);
               const demoPreviewVisible = demoTargets.some(function(target) {
                  const rect = target.getBoundingClientRect();
                  return rect.bottom > 72 && rect.top < window.innerHeight - 72;
               });

               backToTopBtn.classList.toggle('is-visible', window.scrollY > 420 && !demoPreviewVisible);
            };

            toggleBackToTop();
            window.addEventListener('scroll', toggleBackToTop, { passive: true });
            window.addEventListener('resize', keepBackToTopInView, { passive: true });

            backToTopBtn.addEventListener('pointerdown', function(event) {
               if (event.button !== undefined && event.button !== 0) return;

               const rect = backToTopBtn.getBoundingClientRect();
               drag.active = true;
               drag.moved = false;
               drag.pointerId = event.pointerId;
               drag.startX = event.clientX;
               drag.startY = event.clientY;
               drag.offsetX = event.clientX - rect.left;
               drag.offsetY = event.clientY - rect.top;

               backToTopBtn.classList.add('is-dragging');
               backToTopBtn.setPointerCapture?.(event.pointerId);
            });

            backToTopBtn.addEventListener('pointermove', function(event) {
               if (!drag.active || event.pointerId !== drag.pointerId) return;

               const movedX = Math.abs(event.clientX - drag.startX);
               const movedY = Math.abs(event.clientY - drag.startY);

               if (movedX + movedY > 4) {
                  drag.moved = true;
               }

               if (!drag.moved) return;

               event.preventDefault();
               placeBackToTop(event.clientX - drag.offsetX, event.clientY - drag.offsetY);
            });

            const finishDrag = function(event) {
               if (!drag.active || event.pointerId !== drag.pointerId) return;

               if (drag.moved) {
                  suppressNextClick = true;
                  window.setTimeout(function() {
                     suppressNextClick = false;
                  }, 350);
               }

               drag.active = false;
               drag.pointerId = null;
               backToTopBtn.classList.remove('is-dragging');
               backToTopBtn.releasePointerCapture?.(event.pointerId);
            };

            backToTopBtn.addEventListener('pointerup', finishDrag);
            backToTopBtn.addEventListener('pointercancel', finishDrag);

            backToTopBtn.addEventListener('click', function(event) {
               if (suppressNextClick) {
                  event.preventDefault();
                  event.stopPropagation();
                  suppressNextClick = false;
                  return;
               }

               window.scrollTo({
                  top: 0,
                  behavior: 'smooth'
               });
            });
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
          border-radius: 30px;
          background: transparent;
          border: 0;
          isolation: isolate;
          overflow: visible;
          box-shadow: none;
      }
      .logo-loading-circle {
          position: absolute;
          inset: 0;
          border-radius: 30px;
          border: 4px solid var(--bd, #e2e8f0);
          border-top: 4px solid var(--pur, #7c3aed);
          border-right-color: rgba(14, 165, 233, 0.78);
          animation: spin 1s linear infinite;
      }
      .logo-loading-wrapper img {
          width: 96px;
          height: 96px;
          object-fit: contain;
          border-radius: 22px;
          filter: drop-shadow(0 12px 18px rgba(37, 99, 235, 0.2));
          animation: pulse 1.5s ease-in-out infinite;
      }
      @media (max-width: 575px) {
          .logo-loading-wrapper {
              width: 104px;
              height: 104px;
              border-radius: 26px;
          }
          .logo-loading-circle {
              border-width: 3px;
              border-radius: 26px;
          }
          .logo-loading-wrapper img {
              width: 84px;
              height: 84px;
              border-radius: 18px;
          }
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
          <h4 id="authTransitionTitle" style="color:var(--tx); font-weight:600; font-size:1.2rem; letter-spacing:0.5px;">Authenticating...</h4>
          <p id="authTransitionCopy" style="color:var(--tx3); font-size:0.9rem;">Please wait while we log you in</p>
      </div>

      <script>
          function showLoginTransition(mode) {
              const overlay = document.getElementById('loginTransitionOverlay');
              const title = document.getElementById('authTransitionTitle');
              const copy = document.getElementById('authTransitionCopy');

              if (mode === 'register') {
                  if (title) title.textContent = 'Creating your account...';
                  if (copy) copy.textContent = 'Please wait while we set up your dashboard';
              } else if (mode === 'google-register') {
                  if (title) title.textContent = 'Signing up with Google...';
                  if (copy) copy.textContent = 'Opening secure Google registration';
              } else if (mode === 'google' || mode === 'google-login') {
                  if (title) title.textContent = 'Connecting to Google...';
                  if (copy) copy.textContent = 'Opening secure Google sign-in';
              } else {
                  if (title) title.textContent = 'Authenticating...';
                  if (copy) copy.textContent = 'Please wait while we log you in';
              }

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

              const signupForm = document.getElementById('signupForm');
              if (signupForm) {
                  signupForm.addEventListener('submit', function() {
                      if (this.checkValidity()) {
                          showLoginTransition('register');
                      }
                  });
              }

              const googleAuthLinks = document.querySelectorAll('a[data-auth-transition^="google"]');
              const resetGoogleAuthLinks = function() {
                  googleAuthLinks.forEach(function(link) {
                      link.style.pointerEvents = '';
                      link.removeAttribute('aria-disabled');
                      const icon = link.querySelector('i');
                      if (icon) {
                          icon.className = 'fa-brands fa-google me-2';
                          icon.style.color = '#EA4335';
                      }
                  });
              };

              googleAuthLinks.forEach(function(link) {
                  link.addEventListener('click', function(event) {
                      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                          return;
                      }

                      event.preventDefault();
                      showLoginTransition(link.dataset.authTransition || 'google-login');
                      link.setAttribute('aria-disabled', 'true');
                      link.style.pointerEvents = 'none';

                      const icon = link.querySelector('i');
                      if (icon) {
                          icon.className = 'fa-solid fa-spinner fa-spin me-2';
                          icon.style.color = '';
                      }

                      window.setTimeout(function() {
                          window.location.href = link.href;
                      }, 80);
                  });
              });

              window.addEventListener('pageshow', function() {
                  const overlay = document.getElementById('loginTransitionOverlay');
                  if (overlay) overlay.classList.remove('active');
                  resetGoogleAuthLinks();
              });
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
      @include('mobile.partials.page-transition')
   </body>
</html>
