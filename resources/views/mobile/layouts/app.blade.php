<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#08080f">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <title>@yield('title', 'SpeakReady AI - AI-Based Interview Practice System')</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"/>
      <link href="{{ asset('css/aos.css') }}" rel="stylesheet"/>
      <link href="{{ asset('css/swiper-bundle.min.css') }}" rel="stylesheet"/>
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}"/>
      <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}"/>
      <link rel="stylesheet" href="{{ asset('css/mobile/style.css?v=32') }}" />
      @include('mobile.partials.onboarding-styles')
      <style>
         /* Global Mobile Responsiveness for Premium UI Updates */
         .premium-panel:not(.p-0):not(.accordion-item),
         .panel:not(.p-0):not(.accordion-item),
         .setup-panel:not(.p-0):not(.accordion-item) {
             border-radius: 16px !important;
             padding: 16px !important;
         }
         .stat-card.premium-panel:not(.p-0):not(.accordion-item),
         .perk-card.premium-panel:not(.p-0):not(.accordion-item),
         .module-card.premium-panel:not(.p-0):not(.accordion-item),
         .print-card:not(.p-0):not(.accordion-item) {
             padding: 16px !important;
         }
         h4.text-gradient-primary, .text-gradient-primary {
             font-size: 1.25rem !important;
         }
         .db-section {
             padding: 0 !important;
         }
         .accordion-item.premium-panel {
             padding: 0 !important;
         }
         .accordion-button {
             padding: 16px !important;
         }
         
         /* STRICT MOBILE OVERFLOW CONTROL (Prevent Horizontal Scrolling) */
         html, body {
             max-width: 100vw !important;
             overflow-x: hidden !important;
             width: 100%;
         }
         #mob-content, .db-content, .db-section {
             max-width: 100vw !important;
             overflow-x: hidden !important;
             box-sizing: border-box !important;
         }
         #mob-content .db-content,
         #mob-content .db-section,
         #mob-content .db-section > * {
             width: 100%;
             max-width: 100%;
             box-sizing: border-box;
         }
         #mob-content .row {
             width: auto;
             max-width: none !important;
             margin-left: calc(var(--bs-gutter-x, 1.5rem) * -0.5);
             margin-right: calc(var(--bs-gutter-x, 1.5rem) * -0.5);
         }
         #mob-content .row > * {
             min-width: 0;
             max-width: 100%;
             box-sizing: border-box;
         }
         #mob-content .premium-panel,
         #mob-content .panel,
         #mob-content .setup-panel,
         #mob-content .premium-card,
         #mob-content .print-card,
         #mob-content .module-card,
         #mob-content .perk-card,
         #mob-content .ll-stat-card,
         #mob-content .ll-module-card,
         #mob-content .level-card,
         #mob-content .db-stat-card,
         #mob-content .stat-card,
         #mob-content .sr-card,
         #mob-content .sr-stat-card,
         #mob-content .card,
         #mob-content .alert,
         #mob-content .accordion,
         #mob-content .accordion-item {
             width: 100% !important;
             max-width: 100% !important;
             box-sizing: border-box !important;
         }
         #mob-content .table-responsive {
             width: 100% !important;
             max-width: 100% !important;
             overflow-x: visible !important;
             margin-left: 0 !important;
             margin-right: 0 !important;
         }
         canvas {
             max-width: 100% !important;
             height: auto !important;
         }
         #mob-content .table,
         #mob-content .custom-table {
             width: 100% !important;
             min-width: 0 !important;
             max-width: 100% !important;
             table-layout: fixed !important;
         }
         #mob-content .table th,
         #mob-content .table td,
         #mob-content .custom-table th,
         #mob-content .custom-table td {
             min-width: 0 !important;
             max-width: 100% !important;
             white-space: normal !important;
             overflow-wrap: anywhere !important;
             word-break: normal !important;
             padding: 10px 8px !important;
             vertical-align: middle !important;
         }
         #mob-content .table .btn,
         #mob-content .custom-table .btn {
             width: 100% !important;
             max-width: 100% !important;
             white-space: normal !important;
             padding: 7px 8px !important;
             font-size: 0.72rem !important;
         }
         
         /* ===== MOBILE LAYOUT SHELL ===== */
         html, body {
            overflow-x: hidden;
            width: 100%;
            position: relative;
         }
         :root {
            --mob-nav-h: 72px;
            --mob-top-h: 64px;
            --mob-safe-top: env(safe-area-inset-top, 0px);
            --mob-safe-bottom: env(safe-area-inset-bottom, 0px);
         }

         html, body {
            margin: 0; padding: 0;
            overflow-x: hidden !important;
         }

         /* ---- Reset desktop layout for mobile ---- */
         #dashboard    { display: block !important; }
         .db-sidebar   { display: none !important; }
         .db-main      { margin-left: 0 !important; padding-top: 0 !important; min-height: unset !important; }
         .db-top       { display: none !important; }
         .db-section   { height: auto !important; overflow: visible !important; }

         /* ---- Mobile Top Header ---- */
         #mob-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            height: calc(var(--mob-top-h) + var(--mob-safe-top));
            padding-top: var(--mob-safe-top);
            background: linear-gradient(180deg, rgba(12, 16, 28, 0.94), rgba(8, 8, 15, 0.88));
            backdrop-filter: blur(20px) saturate(1.15);
            -webkit-backdrop-filter: blur(20px) saturate(1.15);
            border-bottom: 1px solid rgba(96, 165, 250, 0.16);
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-left: 12px;
            padding-right: 12px;
         }
         .lm #mob-header {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.92));
            border-bottom-color: rgba(37, 99, 235, 0.16);
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
         }

         .mob-header-logo {
            display: flex; align-items: center; gap: 9px;
            font-size: 0.92rem; font-weight: 800;
            color: var(--tx); text-decoration: none;
            min-width: 0; flex: 1 1 auto;
            letter-spacing: 0;
            -webkit-tap-highlight-color: transparent;
         }
         .mob-logo-ring {
            width: 34px;
            height: 34px;
            border-radius: 16%;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: #ffffff;
            border: 2px solid #ffffff;
            box-shadow: none;
            overflow: hidden;
         }
         .lm .mob-logo-ring {
            background: #ffffff;
            border-color: #ffffff;
            box-shadow: none;
         }
         .mob-header-logo img {
            width: 100%;
            height: 100%;
            border-radius: 16%;
            background: transparent;
            object-fit: contain;
         }
         .mob-header-logo span {
            min-width: 0;
            max-width: 128px;
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
         }

         .mob-header-right { display: flex; align-items: center; gap: 6px; flex: 0 0 auto; }

         .mob-icon-btn {
            width: 44px; height: 44px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            background: rgba(255, 255, 255, 0.04); color: var(--tx);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.84rem; cursor: pointer; transition: background-color 0.2s, transform 0.2s, color 0.2s, border-color 0.2s, box-shadow 0.2s;
            -webkit-tap-highlight-color: transparent;
         }
         .mob-icon-btn:active { background: rgba(96,165,250,0.15); transform: scale(0.92); }
         .lm .mob-icon-btn { background: rgba(255, 255, 255, 0.72); border-color: rgba(37, 99, 235, 0.14); }

         .mob-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: #1d4ed8;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 0.8rem; font-weight: 700;
            flex-shrink: 0; cursor: pointer;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.2), 0 0 16px rgba(96, 165, 250, 0.24);
            -webkit-tap-highlight-color: transparent;
         }

         .mob-profile-wrap {
            position: relative;
         }

         .mob-profile-dropdown {
            position: fixed;
            top: calc(var(--mob-top-h) + var(--mob-safe-top) + 8px);
            left: max(10px, env(safe-area-inset-left, 0px));
            right: max(10px, env(safe-area-inset-right, 0px));
            z-index: 1100;
            display: none;
            width: auto;
            max-width: 440px;
            margin: 0 auto;
            background: rgba(12, 16, 28, 0.98);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 16px;
            box-shadow: 0 22px 56px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            overflow: hidden;
         }

         .mob-profile-dropdown.open {
            display: block;
            animation: mobFadeIn 0.18s ease;
         }

         .mob-profile-dropdown[data-origin="bottom"] {
            top: auto;
            bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 10px);
            max-height: min(72dvh, 560px);
         }

         .mob-more-backdrop {
            position: fixed;
            inset: 0;
            z-index: 980;
            display: none;
            background: rgba(2, 6, 23, 0.22);
            backdrop-filter: blur(7px);
            -webkit-backdrop-filter: blur(7px);
         }

         .mob-more-backdrop.open {
            display: block;
            animation: mobFadeIn 0.18s ease;
         }

         .lm .mob-more-backdrop {
            background: rgba(248, 250, 252, 0.34);
         }

         .lm .mob-profile-dropdown {
            background: rgba(255, 255, 255, 0.98);
            border-color: rgba(37, 99, 235, 0.16);
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.16);
         }

         .mob-profile-head {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 14px;
            border-bottom: 1px solid var(--bd);
         }

         .mob-profile-head-avatar {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: #1d4ed8;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            overflow: hidden;
            font-weight: 800;
         }

         .mob-profile-head-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
         }

         .mob-profile-head-meta {
            min-width: 0;
            flex: 1 1 auto;
         }

         .mob-profile-name {
            color: var(--tx);
            font-size: 0.9rem;
            font-weight: 800;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
         }

         .mob-profile-role {
            color: var(--tx3);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 2px;
         }

         .mob-profile-close {
            width: 34px;
            height: 34px;
            border: 1px solid var(--bd2);
            border-radius: 10px;
            background: transparent;
            color: var(--tx2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
         }

         .mob-profile-menu {
            max-height: min(66dvh, 500px);
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 10px;
         }

         .mob-profile-dropdown[data-mode="account"] .mob-profile-pages,
         .mob-profile-dropdown[data-mode="pages"] .mob-profile-account {
            display: none;
         }

         .mob-profile-dropdown[data-mode="pages"] .mob-profile-head {
            display: none;
         }

         .mob-profile-pages-close {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--bd);
            color: var(--tx);
            font-size: 0.88rem;
            font-weight: 800;
         }

         .mob-profile-dropdown[data-mode="pages"] .mob-profile-pages-close {
            display: flex;
         }

         .mob-profile-section-title {
            color: var(--tx3);
            font-size: 0.66rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 8px 6px 7px;
         }

         .mob-profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
         }

         .mob-profile-link,
         .mob-profile-action {
            min-width: 0;
            min-height: 48px;
            border: 1px solid var(--bd);
            border-radius: 13px;
            background: rgba(255, 255, 255, 0.035);
            color: var(--tx);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 10px;
            font-size: 0.76rem;
            font-weight: 800;
            line-height: 1.2;
         }

         .lm .mob-profile-link,
         .lm .mob-profile-action {
            background: rgba(248, 250, 252, 0.86);
         }

         .mob-profile-link.active {
            border-color: rgba(96, 165, 250, 0.38);
            background: rgba(96, 165, 250, 0.12);
         }

         .mob-profile-link i,
         .mob-profile-action i {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            color: #fff;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
         }

         .mob-profile-link.profile-nav-emerald i { background: linear-gradient(135deg, #059669, #10b981); }
         .mob-profile-link.profile-nav-cyan i { background: linear-gradient(135deg, #0891b2, #22d3ee); }
         .mob-profile-link.profile-nav-indigo i { background: linear-gradient(135deg, #4f46e5, #818cf8); }
         .mob-profile-link.profile-nav-rose i { background: linear-gradient(135deg, #e11d48, #fb7185); }
         .mob-profile-link.profile-nav-amber i { background: linear-gradient(135deg, #d97706, #fbbf24); }
         .mob-profile-link.profile-nav-purple i { background: linear-gradient(135deg, #7c3aed, #c084fc); }
         .mob-profile-link.profile-nav-blue i { background: linear-gradient(135deg, #2563eb, #60a5fa); }
         .mob-profile-link.profile-nav-slate i { background: linear-gradient(135deg, #475569, #94a3b8); }

         .mob-profile-link span,
         .mob-profile-action span {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
         }

         .mob-profile-action {
            width: 100%;
            border-color: var(--bd2);
            text-align: left;
         }

         .mob-profile-action.danger {
            color: #f87171;
            border-color: rgba(248, 113, 113, 0.28);
            background: rgba(248, 113, 113, 0.08);
         }

         .mob-profile-action.danger i {
            background: linear-gradient(135deg, #ef4444, #f97316);
         }

         .mob-profile-settings-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 8px;
            align-items: start;
         }

         .mob-profile-settings-row > form {
            min-width: 0;
         }

         .mob-profile-language {
            border: 1px solid var(--bd);
            border-radius: 13px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.035);
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            gap: 9px;
            min-height: 48px;
            position: relative;
            overflow: hidden;
         }

         .mob-profile-language label {
            display: none;
         }

         .mob-profile-language-trigger {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            color: #fff;
            pointer-events: none;
         }

         .mob-profile-language-trigger i {
            font-size: 0.82rem;
         }

         .mob-profile-language-text {
            color: var(--tx);
            font-size: 0.76rem;
            font-weight: 800;
            line-height: 1.2;
            pointer-events: none;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
         }

         .mob-profile-language select {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
         }

         .mob-profile-language label {
            align-items: center;
            gap: 9px;
            color: var(--tx);
            font-size: 0.76rem;
            font-weight: 800;
            margin-bottom: 0;
            white-space: nowrap;
         }

         .mob-profile-language label i {
            color: #60a5fa;
         }

         .mob-profile-language select {
            background: var(--bg3);
            border-color: var(--bd);
            color: var(--tx);
            border-radius: 11px;
            min-height: 36px;
            font-size: 0.74rem;
            padding-top: 5px;
            padding-bottom: 5px;
            min-width: 0;
         }

         .mob-profile-settings-row .mob-profile-action {
            min-height: 48px;
            align-items: center;
            padding: 10px;
         }

         @media (max-width: 360px) {
            .mob-profile-grid {
               grid-template-columns: 1fr;
            }
         }

         .mob-notif-wrap {
            position: relative;
         }

         .mob-notif-dropdown {
            position: fixed;
            top: calc(var(--mob-top-h) + var(--mob-safe-top) + 8px);
            left: max(10px, env(safe-area-inset-left, 0px));
            right: max(10px, env(safe-area-inset-right, 0px));
            z-index: 1100;
            display: none;
            width: auto;
            max-width: 440px;
            margin: 0 auto;
            background: rgba(12, 16, 28, 0.98);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 16px;
            box-shadow: 0 22px 56px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            overflow: hidden;
         }

         .mob-notif-dropdown.open {
            display: block;
            animation: mobFadeIn 0.18s ease;
         }

         .lm .mob-notif-dropdown {
            background: rgba(255, 255, 255, 0.98);
            border-color: rgba(37, 99, 235, 0.16);
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.16);
         }

         .mob-notif-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 14px;
            border-bottom: 1px solid var(--bd);
         }

         .mob-notif-title {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--tx);
            font-size: 0.92rem;
            font-weight: 800;
         }

         .mob-notif-count {
            display: none;
            flex: 0 0 auto;
            color: #f87171;
            background: rgba(248, 113, 113, 0.14);
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 0.68rem;
            font-weight: 800;
         }

         .mob-notif-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
         }

         .mob-notif-action {
            min-width: 44px;
            min-height: 44px;
            border: 1px solid var(--bd2);
            border-radius: 10px;
            background: transparent;
            color: var(--tx2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 10px 12px;
            font-size: 0.72rem;
            font-weight: 800;
         }

         .mob-notif-action.danger {
            color: #f87171;
            border-color: rgba(248, 113, 113, 0.28);
         }

         .mob-notif-list {
            max-height: min(60dvh, 430px);
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 10px;
         }

         .mob-notif-item {
            display: flex;
            gap: 10px;
            padding: 12px;
            border: 1px solid var(--bd);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.035);
            margin-bottom: 8px;
         }

         .lm .mob-notif-item {
            background: rgba(248, 250, 252, 0.86);
         }

         .mob-notif-item.unread {
            border-color: rgba(96, 165, 250, 0.32);
            background: rgba(96, 165, 250, 0.1);
         }

         .mob-notif-ico {
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 12px;
            background: rgba(96, 165, 250, 0.14);
            color: #60a5fa;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
         }

         .mob-notif-copy {
            min-width: 0;
            flex: 1 1 auto;
         }

         .mob-notif-copy strong,
         .mob-notif-copy span,
         .mob-notif-copy small {
            display: block;
            overflow-wrap: anywhere;
            word-break: normal;
         }

         .mob-notif-copy strong {
            color: var(--tx);
            font-size: 0.84rem;
            line-height: 1.3;
            margin-bottom: 4px;
         }

         .mob-notif-copy span {
            color: var(--tx2);
            font-size: 0.76rem;
            line-height: 1.45;
         }

         .mob-notif-copy small {
            color: var(--tx3);
            font-size: 0.68rem;
            margin-top: 6px;
         }

         .mob-notif-row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
            justify-content: flex-end;
            align-items: center;
         }

         .mob-notif-link-btn {
            min-height: 32px;
            border: 1px solid transparent;
            border-radius: 8px;
            background: rgba(96, 165, 250, 0.1);
            padding: 7px 10px;
            color: var(--pur);
            font-size: 0.65rem;
            font-weight: 800;
            line-height: 1.1;
         }

         .mob-notif-link-btn.danger {
            color: #f87171;
         }

         .mob-notif-footer {
            padding: 12px 14px 14px;
            border-top: 1px solid var(--bd);
         }

         .mob-notif-view-all {
            width: 100%;
            min-height: 40px;
            border-radius: 12px;
            border: 1px solid var(--bd2);
            color: var(--tx);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 0.8rem;
            font-weight: 800;
         }

         @media (max-width: 360px) {
            .mob-notif-header {
               align-items: flex-start;
               flex-direction: column;
            }

            .mob-notif-actions {
               width: 100%;
            }

            .mob-notif-action {
               flex: 1 1 0;
            }
         }

         @media (max-width: 380px) {
            .mob-header-logo span {
               max-width: 104px;
            }

            .mob-header-right { gap: 5px; }

            .mob-icon-btn {
               width: 32px;
               height: 32px;
            }

            .mob-avatar {
               width: 31px;
               height: 31px;
            }
         }

         /* ---- Page Content ---- */
         #mob-content {
            padding-top: calc(var(--mob-top-h) + var(--mob-safe-top));
            padding-bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 8px);
            min-height: 100vh;
            min-height: var(--sr-visual-vh, 100dvh);
            overflow-x: hidden !important;
            width: 100%;
            max-width: 100%;
         }
         .db-content { padding: 10px 12px 12px !important; }

         body.mobile-interview-fullscreen #mob-header,
         body.mobile-interview-fullscreen #mob-bottom-nav {
            display: none !important;
         }

         body.mobile-interview-fullscreen #mob-content {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            min-height: var(--sr-visual-vh, 100dvh);
            overflow-y: auto;
         }

         body.mobile-interview-fullscreen #mob-content > .db-content {
            min-height: var(--sr-visual-vh, 100dvh);
            padding: 0 !important;
         }

         @include('mobile.partials.mobile-card-rhythm')
         @include('mobile.partials.mobile-shell-viewport')

         /* ---- Bottom Navigation Bar ---- */
         #mob-bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 990;
            height: calc(var(--mob-nav-h) + var(--mob-safe-bottom));
            padding-bottom: var(--mob-safe-bottom);
            background: rgba(18, 25, 39, 0.96);
            backdrop-filter: blur(18px) saturate(150%);
            -webkit-backdrop-filter: blur(18px) saturate(150%);
            border-top: 1px solid rgba(96, 165, 250, 0.16);
            display: flex;
            align-items: center;
            overflow: visible;
            box-shadow: 0 -12px 30px rgba(2, 6, 23, 0.26);
            isolation: isolate;
         }
         #mob-bottom-nav::before {
            content: "";
            position: absolute;
            top: -1px;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.34), transparent);
            pointer-events: none;
         }
         .lm #mob-bottom-nav {
            background: rgba(255, 255, 255, 0.97);
            border-top-color: rgba(226, 232, 240, 0.95);
            box-shadow: 0 -8px 26px rgba(15, 23, 42, 0.1);
         }

         .mob-nav-items {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 0;
            padding: 6px max(8px, env(safe-area-inset-left, 0px)) 7px max(8px, env(safe-area-inset-right, 0px));
         }
         .mob-nav-item {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 0;
            min-height: 54px;
            flex: 1 1 0;
            padding: 5px 2px 4px;
            border-radius: 12px;
            text-decoration: none;
            color: #8a96a8;
            font-size: 0.66rem;
            font-weight: 600;
            letter-spacing: 0;
            line-height: 1.1;
            transition: color 0.18s ease, transform 0.18s ease, background-color 0.18s ease;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            border: none;
            background: transparent;
            cursor: pointer;
            font-family: "Poppins", sans-serif;
         }
         .lm .mob-nav-item {
            color: #8b95a5;
         }
         .mob-nav-icon {
            width: 28px;
            height: 28px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
         }
         .mob-nav-icon i,
         .mob-nav-primary-icon i {
            font-size: 1.26rem;
            line-height: 1;
         }
         .mob-nav-item > span:last-child {
            display: block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
         }
         .mob-nav-item:active .mob-nav-icon,
         .mob-nav-item:active .mob-nav-primary-icon {
            transform: scale(0.9);
         }
         .mob-nav-item.nav-icon-moving .mob-nav-icon {
            animation: mobNavIconTap 0.48s cubic-bezier(0.22, 1, 0.36, 1);
         }
         .mob-nav-item.nav-icon-moving .mob-nav-primary-icon {
            animation: mobNavPrimaryTap 0.56s cubic-bezier(0.22, 1, 0.36, 1);
         }
         .mob-nav-item.nav-icon-moving > span:last-child {
            animation: mobNavLabelTap 0.48s ease;
         }
         .mob-nav-item:focus-visible {
            outline: 2px solid rgba(96, 165, 250, 0.9);
            outline-offset: 2px;
         }
         .mob-nav-item.active {
            color: #1f6fff;
            font-weight: 800;
         }
         .mob-nav-item.active .mob-nav-icon {
            background: transparent;
            box-shadow: none;
         }
         .mob-nav-primary {
            color: #1f6fff;
            font-weight: 800;
            align-self: flex-start;
            min-height: 74px;
            padding-top: 0;
            transform: translateY(-22px);
         }
         .mob-nav-primary-icon {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(180deg, #2f80ff 0%, #1767f2 100%);
            border: 5px solid rgba(18, 25, 39, 0.98);
            box-shadow: 0 11px 20px rgba(37, 99, 235, 0.34), 0 0 0 1px rgba(96, 165, 250, 0.28);
            margin-bottom: 1px;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
         }
         .mob-nav-primary .mob-nav-primary-icon i {
            font-size: 1.64rem;
            filter: none;
         }
         .mob-nav-primary.active,
         .mob-nav-primary:active {
            color: #1f6fff;
         }
         .mob-nav-primary.active .mob-nav-primary-icon {
            box-shadow: 0 13px 24px rgba(37, 99, 235, 0.42), 0 0 0 1px rgba(96, 165, 250, 0.34);
         }
         .lm .mob-nav-item.active .mob-nav-icon {
            background: transparent;
            box-shadow: none;
         }
         .lm .mob-nav-primary {
            color: #1f6fff;
         }
         .lm .mob-nav-primary-icon {
            border-color: rgba(255, 255, 255, 0.98);
         }

         .ucp-mobile-launcher {
            position: fixed;
            right: 16px;
            bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 16px);
            z-index: 1001;
            width: 48px;
            height: 48px;
            border: 1px solid rgba(96, 165, 250, 0.36);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(135deg, #2563eb 0%, #0f766e 100%);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.32);
            cursor: grab;
            touch-action: none;
            -webkit-tap-highlight-color: transparent;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
         }
         .ucp-mobile-launcher i {
            font-size: 1rem;
            line-height: 1;
         }
         .ucp-mobile-launcher:active,
         .ucp-mobile-launcher.is-dragging {
            cursor: grabbing;
            transform: scale(0.96);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.28);
         }
         .ucp-mobile-launcher:focus-visible {
            outline: 3px solid rgba(96, 165, 250, 0.45);
            outline-offset: 3px;
         }
         .lm .ucp-mobile-launcher {
            border-color: rgba(37, 99, 235, 0.18);
            box-shadow: 0 16px 30px rgba(15, 23, 42, 0.16);
         }

         @keyframes mobNavIconTap {
            0% { transform: translateY(0) scale(1) rotate(0deg); }
            32% { transform: translateY(-7px) scale(1.12) rotate(-5deg); }
            58% { transform: translateY(2px) scale(0.94) rotate(3deg); }
            100% { transform: translateY(0) scale(1) rotate(0deg); }
         }
         @keyframes mobNavPrimaryTap {
            0% { transform: translateY(0) scale(1); box-shadow: 0 16px 28px rgba(37, 99, 235, 0.38), 0 0 0 7px rgba(37, 99, 235, 0.12); }
            34% { transform: translateY(-9px) scale(1.1); box-shadow: 0 22px 38px rgba(37, 99, 235, 0.52), 0 0 0 11px rgba(37, 99, 235, 0.18); }
            64% { transform: translateY(2px) scale(0.96); }
            100% { transform: translateY(0) scale(1); box-shadow: 0 16px 28px rgba(37, 99, 235, 0.38), 0 0 0 7px rgba(37, 99, 235, 0.12); }
         }
         @keyframes mobNavLabelTap {
            0%, 100% { transform: translateY(0); opacity: 1; }
            40% { transform: translateY(2px); opacity: 0.78; }
         }

         @media (prefers-reduced-motion: reduce) {
            .mob-nav-item.nav-icon-moving .mob-nav-icon,
            .mob-nav-item.nav-icon-moving .mob-nav-primary-icon,
            .mob-nav-item.nav-icon-moving > span:last-child {
               animation: none !important;
            }
         }

         @media (hover: hover) {
            .mob-nav-item:hover {
               color: #93c5fd;
               background: rgba(96, 165, 250, 0.06);
            }
            .lm .mob-nav-item:hover {
               color: #2563eb;
               background: rgba(37, 99, 235, 0.06);
            }
         }

         @media (max-width: 360px) {
            .mob-nav-items {
               padding-left: 6px;
               padding-right: 6px;
               gap: 0;
            }

            .mob-nav-item {
               font-size: 0.58rem;
               min-height: 56px;
            }

            .mob-nav-icon {
               width: 31px;
               height: 31px;
               border-radius: 12px;
            }

            .mob-nav-primary-icon {
               width: 56px;
               height: 56px;
            }
         }

         @media (prefers-reduced-motion: reduce) {
            .mob-nav-item,
            .mob-nav-icon,
            .mob-nav-primary-icon {
               transition: none;
            }
         }

         @keyframes mobFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
         }

         /* =============================================================
            GLOBAL MOBILE FIXES FOR ALL VIEWS
         ============================================================= */

         /* --- Un-stick sticky panels on mobile (e.g. interview setup summary) --- */
         @media (max-width: 991px) {
            [style*="position:sticky"], [style*="position: sticky"] {
               position: relative !important;
               top: auto !important;
            }
         }

         /* --- Coach view: fix chat container height --- */
         @media (max-width: 767px) {
            .chat-container {
               height: calc(100dvh - var(--mob-top-h) - var(--mob-nav-h) - 40px) !important;
               min-height: 300px;
               border-radius: 12px !important;
               flex-direction: column !important;
            }
            /* Hide chat sidebar on mobile (already d-none d-md-flex but safety net) */
            .chat-sidebar { display: none !important; }
            .chat-messages { padding: 14px !important; gap: 12px !important; }
            .chat-input-area { padding: 10px 12px !important; }
            .chat-bubble {
               max-width: 92% !important;
               padding: 10px 14px !important;
               font-size: 0.875rem !important;
            }
         }

         /* --- Interview session: Messenger-Style Video Call UI on mobile --- */
         @media (max-width: 991px) {
            /* Keep workspace wrapper stacking */
            #workspaceRow { display: flex !important; flex-direction: column; }
            
            #workspaceRow > .col-lg-8,
            #workspaceRow > .col-lg-4 {
               width: 100% !important;
               max-width: 100% !important;
               min-width: 0 !important;
               padding-left: 0 !important;
               padding-right: 0 !important;
            }
            
            #workspaceRow > .col-lg-8 { order: 1; }
            #workspaceRow > .col-lg-4 { display: block !important; order: 2; margin-bottom: 0; }
            
            /* Pages already render the mobile camera inside the avatar panel. */
            #cameraPanel {
               display: none !important;
            }
            
            /* Keep standard panel styles for main content, just add bottom padding */
            #workspaceRow > .col-lg-8 {
               position: relative;
               display: flex; flex-direction: column; gap: 15px;
               padding-bottom: 10px;
            }
            
            /* Responsive question text for mobile */
            #aiQuestionText {
               font-size: 0.85rem !important;
               line-height: 1.3 !important;
            }
         }

         /* --- Interview setup: summary below form on mobile --- */
         @media (max-width: 991px) {
            .row > .col-lg-8 { order: 1; }
            .row > .col-lg-4 { order: 2; }
         }

         /* --- Fix interview nav buttons on mobile --- */
         @media (max-width: 575px) {
            .d-flex.justify-content-between.border-top.pt-4 {
               flex-direction: column !important; gap: 10px !important;
            }
            .d-flex.justify-content-between.border-top.pt-4 > div,
            .d-flex.justify-content-between.border-top.pt-4 > button {
               width: 100% !important; justify-content: center;
            }
            .d-flex.justify-content-between.border-top.pt-4 > div {
               display: flex !important; gap: 8px;
            }
            .d-flex.justify-content-between.border-top.pt-4 > div .btn {
               flex: 1;
            }
         }

         /* --- Fix voice controls buttons wrapping --- */
         @media (max-width: 575px) {
            #voiceControls .d-flex.gap-2 { flex-wrap: wrap; }
            #voiceControls .btn { font-size: 0.8rem; padding: 7px 12px; }
            #answerTextarea { min-height: 100px !important; }
         }

         /* --- Progress page export buttons --- */
         @media (max-width: 575px) {
            .mb-4.d-flex.justify-content-between .d-flex.gap-2 { flex-wrap: wrap; }
            .mb-4.d-flex.justify-content-between .btn {
               font-size: 0.75rem; padding: 6px 10px;
            }
         }

         /* --- Fix search input width in table headers --- */
         @media (max-width: 575px) {
            .input-group[style*="width:250px"],
            .input-group[style*="width: 250px"] { width: 100% !important; }
            .d-flex.justify-content-between.align-items-center.flex-wrap {
               flex-direction: column !important; align-items: flex-start !important;
            }
         }

         /* --- Make stat cards 2-per-row on mobile --- */
         @media (max-width: 575px) {
            #progress-stats,
            #dashboard-stats {
               --bs-gutter-x: 10px;
               --bs-gutter-y: 10px;
            }

            .stat-grid {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
               gap: 10px !important;
            }

            #progress-stats > .col-md-3.col-sm-6,
            #dashboard-stats > .col-12.col-sm-6,
            .col-md-3.col-sm-6 {
               width: 50% !important;
               flex: 0 0 auto !important;
            }

            #progress-stats .premium-panel,
            #dashboard-stats .ll-stat-card,
            .stat-card.premium-panel {
               min-height: 106px !important;
               padding: var(--mob-card-pad) !important;
               border-radius: 14px !important;
            }

            #dashboard-stats .ll-stat-card.gap-3,
            #dashboard-stats .ll-stat-card.d-flex {
               gap: 8px !important;
            }

            #dashboard-stats .ll-stat-card [style*="width:55px"] {
               width: 38px !important;
               height: 38px !important;
               flex: 0 0 38px !important;
               border-radius: 12px !important;
            }

            #progress-stats .premium-panel .fs-1,
            #dashboard-stats .ll-stat-card [style*="font-size:1.8rem"] {
               font-size: 1.35rem !important;
            }

            #progress-stats .premium-panel h3,
            #dashboard-stats .ll-stat-val {
               font-size: 1.12rem !important;
               line-height: 1.12 !important;
            }

            #dashboard-stats .ll-stat-val span {
               font-size: 0.75rem !important;
            }

            #progress-stats .premium-panel p,
            #dashboard-stats .ll-stat-card [style*="text-transform:uppercase"] {
               font-size: 0.68rem !important;
               line-height: 1.25 !important;
            }
         }

         /* --- Question types checkbox grid: 1 col on small screens --- */
         @media (max-width: 575px) {
            .cbx-grid { grid-template-columns: 1fr !important; }
            .row.g-4  { --bs-gutter-x: 0.75rem; --bs-gutter-y: 0.75rem; }
            .db-content { padding: 10px 12px 12px !important; }
         }

         @media (max-width: 380px) {
            .stat-grid {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            #progress-stats .premium-panel,
            #dashboard-stats .ll-stat-card,
            .stat-card.premium-panel {
               min-height: 100px !important;
               padding: var(--mob-card-pad) !important;
            }
         }

         /* --- Table mobile scrolling --- */
         .table-responsive { -webkit-overflow-scrolling: touch; }

         /* --- Cross-page mobile polish for user tools --- */
         @media (max-width: 767px) {
            #mob-content :is(.sr-page-hero, .progress-hero, .setup-hero) {
               min-height: 104px !important;
               margin-bottom: 12px !important;
               border-radius: 14px !important;
               box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08) !important;
            }

            #mob-content :is(.sr-page-hero-inner, .progress-hero-inner, .setup-hero-inner) {
               justify-content: flex-start !important;
               min-height: 104px !important;
               padding: 14px 96px 14px 14px !important;
            }

            #mob-content :is(.sr-page-hero-title, .progress-hero-title, .setup-hero-title) {
               justify-content: flex-start !important;
               gap: 7px !important;
               font-size: 1.08rem !important;
               line-height: 1.15 !important;
               margin-bottom: 4px !important;
               letter-spacing: 0 !important;
            }

            #mob-content :is(.sr-page-hero-title svg, .progress-hero-title svg, .setup-hero-title svg) {
               width: 20px !important;
               height: 20px !important;
               flex: 0 0 auto !important;
            }

            #mob-content :is(.sr-page-hero-subtitle, .progress-hero-subtitle, .setup-hero-subtitle) {
               max-width: 100% !important;
               font-size: 0.74rem !important;
               line-height: 1.4 !important;
            }

            #mob-content :is(.sr-page-hero-art, .progress-hero-art, .setup-hero-art) {
               right: -10px !important;
               bottom: -1px !important;
               width: 112px !important;
            }

            #mob-content .sr-page-actions,
            #mob-content :is(.progress-actions, .tracker-actions) {
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
               gap: 8px !important;
               margin-bottom: 12px !important;
               width: 100% !important;
            }

            #mob-content .sr-page-actions > *,
            #mob-content :is(.progress-actions, .tracker-actions) > * {
               width: 100% !important;
               min-width: 0 !important;
            }

            #mob-content .sr-page-actions > :only-child,
            #mob-content :is(.progress-actions, .tracker-actions) > :only-child {
               grid-column: 1 / -1 !important;
            }

            #mob-content :is(.sr-page-actions, .progress-actions, .tracker-actions) .btn {
               min-height: 44px !important;
               display: inline-flex !important;
               align-items: center !important;
               justify-content: center !important;
               gap: 6px !important;
               padding: 9px 10px !important;
               border-radius: 12px !important;
               font-size: 0.8rem !important;
               line-height: 1.2 !important;
               white-space: normal !important;
            }

            #mob-content :is(.sr-page-actions, .progress-actions, .tracker-actions) .btn i {
               margin-right: 0 !important;
               margin-left: 0 !important;
            }

            #mob-content :is(
               .premium-card,
               .premium-panel,
               .panel,
               .tracker-panel,
               .tracker-card,
               .pack-card,
               .module-card,
               .ll-module-card,
               .level-card,
               .perk-card,
               .print-card,
               .sr-card,
               .sr-stat-card,
               .db-stat-card,
               .stat-card,
               .card
            ) {
               border-radius: 14px !important;
               box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08) !important;
            }

            #mob-content :is(
               .premium-card,
               .premium-panel,
               .panel,
               .tracker-panel,
               .tracker-card,
               .pack-card,
               .module-card,
               .ll-module-card,
               .level-card,
               .perk-card,
               .print-card,
               .sr-card,
               .sr-stat-card,
               .db-stat-card,
               .stat-card,
               .card
            ):hover {
               transform: none !important;
            }

            #mob-content :is(.premium-panel, .panel, .tracker-panel, .tracker-card, .card) :is(h4, h5, h6) {
               line-height: 1.25 !important;
               letter-spacing: 0 !important;
            }

            #mob-content :is(input, select, textarea, .form-control, .form-select, .tracker-field, .oinp) {
               min-height: 44px !important;
               border-radius: 11px !important;
               font-size: 0.86rem !important;
            }

            #mob-content textarea:is(.form-control, .tracker-field, .oinp) {
               min-height: 96px !important;
               line-height: 1.45 !important;
            }

            #mob-content :is(.btn, button.btn) {
               touch-action: manipulation;
            }

            #mob-content :is(.btn-sm, .btn.btn-sm) {
               min-height: 36px;
               display: inline-flex;
               align-items: center;
               justify-content: center;
               gap: 5px;
            }

            #mob-content :is(.table-responsive) {
               overflow-x: visible !important;
            }

            #mob-content :is(#feedbackTable, #history-table table) thead {
               display: none !important;
            }

            #mob-content :is(#feedbackTable, #history-table table),
            #mob-content :is(#feedbackTable, #history-table table) tbody,
            #mob-content :is(#feedbackTable, #history-table table) tr,
            #mob-content :is(#feedbackTable, #history-table table) td {
               display: block !important;
               width: 100% !important;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody tr {
               border: 1px solid var(--bd) !important;
               border-radius: 12px !important;
               padding: 10px !important;
               margin-bottom: 10px !important;
               background: var(--bg3) !important;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody td {
               border: 0 !important;
               padding: 5px 0 !important;
               text-align: left !important;
               font-size: 0.82rem !important;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody td:nth-child(1) {
               color: var(--tx3) !important;
               font-size: 0.74rem !important;
               padding-bottom: 2px !important;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody td:nth-child(2) {
               color: var(--tx) !important;
               font-size: 0.94rem !important;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody td:nth-child(3)::before {
               content: "Score: ";
               color: var(--tx3);
               font-weight: 700;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody td:nth-child(4)::before {
               content: "Rating: ";
               color: var(--tx3);
               font-weight: 700;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody td:nth-child(5) .d-flex {
               justify-content: stretch !important;
               margin-top: 6px !important;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody td:nth-child(5) .btn-primary,
            #mob-content :is(#feedbackTable, #history-table table) tbody td:nth-child(5) .btn-outline-primary {
               flex: 1 1 auto !important;
               min-height: 38px !important;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody td:nth-child(5) form {
               flex: 0 0 42px !important;
            }

            #mob-content :is(#feedbackTable, #history-table table) tbody td:nth-child(5) .btn-outline-danger {
               width: 42px !important;
               min-height: 38px !important;
            }

            #mob-content #feedback-filters {
               display: grid !important;
               grid-template-columns: 1fr !important;
               width: 100% !important;
               gap: 8px !important;
            }

            #mob-content #feedback-filters > *,
            #mob-content #feedback-filters form,
            #mob-content #feedback-filters .input-group,
            #mob-content #feedback-filters select,
            #mob-content #feedback-filters button {
               width: 100% !important;
            }

            #mob-content .tracker-stats {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
               gap: 10px !important;
               margin-bottom: 12px !important;
            }

            #mob-content .tracker-stat {
               border-radius: 14px !important;
               padding: 13px !important;
               min-height: 92px !important;
            }

            #mob-content .tracker-stat-label {
               font-size: 0.66rem !important;
               line-height: 1.2 !important;
            }

            #mob-content .tracker-stat-value {
               font-size: 1.18rem !important;
            }

            #mob-content .tracker-grid {
               display: grid !important;
               grid-template-columns: 1fr !important;
               gap: 12px !important;
            }

            #mob-content .tracker-card .tracker-actions {
               grid-template-columns: 72px minmax(0, 1fr) !important;
               align-items: center !important;
            }

            #mob-content .match-ring {
               width: 62px !important;
               height: 62px !important;
            }

            #mob-content .match-ring span {
               width: 48px !important;
               height: 48px !important;
               font-size: 0.82rem !important;
            }

            #mob-content .plan-item {
               grid-template-columns: auto minmax(0, 1fr) !important;
               gap: 9px !important;
               padding: 11px !important;
            }

            #mob-content .plan-item > span:last-child {
               grid-column: 2;
               font-size: 0.72rem !important;
               white-space: normal !important;
            }

            #mob-content .keyword-chip,
            #mob-content .status-pill,
            #mob-content .badge {
               max-width: 100%;
               white-space: normal !important;
               overflow-wrap: anywhere;
               line-height: 1.2;
            }

            #mob-content :is(.pack-chip, .keyword-chip, .status-pill) {
               padding: 6px 9px !important;
               font-size: 0.7rem !important;
            }

            #mob-content :is(.pack-card, .module-card, .ll-module-card, .level-card, .perk-card) .btn {
               width: 100%;
               min-height: 42px;
            }

            #mob-content :is(.chat-container) {
               height: calc(100dvh - var(--mob-top-h) - var(--mob-nav-h) - 142px) !important;
               min-height: 360px !important;
               border-radius: 14px !important;
            }

            #mob-content .chat-main > div:first-child {
               padding: 12px 14px !important;
            }

            #mob-content .chat-main > div:first-child [style*="width:40px"] {
               width: 34px !important;
               height: 34px !important;
               margin-right: 10px !important;
               border-radius: 10px !important;
            }

            #mob-content .chat-messages {
               padding: 12px !important;
               gap: 10px !important;
            }

            #mob-content .chat-bubble {
               max-width: 92% !important;
               padding: 10px 13px !important;
               border-radius: 16px !important;
               font-size: 0.84rem !important;
               line-height: 1.45 !important;
            }

            #mob-content .chat-input-area {
               padding: 10px 12px !important;
            }

            #mob-content .chat-input-wrapper {
               border-radius: 14px !important;
               padding: 7px 8px 7px 12px !important;
            }

            #mob-content .chat-send-btn {
               width: 38px !important;
               height: 38px !important;
               border-radius: 12px !important;
               margin-left: 8px !important;
            }

            #mob-content :is(.notification-card, .notification-item, .account-card, .leaderboard-card, .report-card) {
               border-radius: 14px !important;
               padding: 14px !important;
            }
         }

         @media (max-width: 390px) {
            #mob-content :is(.sr-page-hero-inner, .progress-hero-inner, .setup-hero-inner) {
               padding-right: 82px !important;
            }

            #mob-content :is(.sr-page-hero-title, .progress-hero-title, .setup-hero-title) {
               font-size: 1rem !important;
            }

            #mob-content :is(.sr-page-hero-art, .progress-hero-art, .setup-hero-art) {
               width: 98px !important;
            }

            #mob-content .tracker-stats {
               grid-template-columns: 1fr 1fr !important;
            }
         }

         /* --- Page headers: center title/subtitle on mobile --- */
         @media (max-width: 767px) {
            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) {
               flex-direction: column !important;
               gap: 12px !important;
               justify-content: center !important;
               align-items: center !important;
               text-align: center !important;
            }

            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:first-child {
               width: 100%;
               max-width: 100%;
               text-align: center !important;
            }

            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:first-child > .d-flex {
               justify-content: center !important;
            }

            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:first-child :is(h1, h2, h3, h4, h5, h6, p) {
               text-align: center !important;
               margin-left: auto !important;
               margin-right: auto !important;
            }

            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:first-child p {
               max-width: 34rem;
               margin-bottom: 0 !important;
            }

            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:not(:first-child) {
               justify-content: center !important;
            }

            #mob-content > .db-content > :is(.db-section, .container-fluid) > .mb-4:not(.d-flex) > :is(h1, h2, h3, h4, h5, h6, p) {
               text-align: center !important;
               margin-left: auto !important;
               margin-right: auto !important;
            }

         #mob-content > .db-content > :is(.db-section, .container-fluid) > .mb-4:not(.d-flex) > p {
               max-width: 34rem;
            }
         }

         /* --- SaaSPro mobile app chrome --- */
         @media (max-width: 991px) {
            :root {
               --mob-top-h: 58px;
               --mob-nav-h: 70px;
               --mob-chrome-radius: 16px;
               --mob-chrome-border: rgba(148, 163, 184, 0.2);
               --mob-chrome-shadow: 0 12px 28px rgba(2, 6, 23, 0.24);
               --mob-chrome-soft: rgba(255, 255, 255, 0.06);
               --mob-chrome-active: rgba(59, 130, 246, 0.15);
            }

            .lm {
               --mob-chrome-border: rgba(37, 99, 235, 0.14);
               --mob-chrome-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
               --mob-chrome-soft: rgba(248, 250, 252, 0.9);
               --mob-chrome-active: rgba(37, 99, 235, 0.1);
            }

            #mob-header {
               height: calc(var(--mob-top-h) + var(--mob-safe-top)) !important;
               padding: var(--mob-safe-top) 10px 0 !important;
               background: rgba(10, 15, 25, 0.88) !important;
               border-bottom-color: var(--mob-chrome-border) !important;
               box-shadow: var(--mob-chrome-shadow) !important;
            }

            .lm #mob-header {
               background: rgba(255, 255, 255, 0.92) !important;
            }

            .mob-header-logo {
               gap: 8px !important;
               flex: 0 1 auto !important;
               max-width: 44vw !important;
               font-size: 0.82rem !important;
            }

            .mob-logo-ring {
               width: 32px !important;
               height: 32px !important;
               border-radius: 16% !important;
               padding: 0 !important;
               background: #ffffff !important;
               border: 2px solid #ffffff !important;
               box-shadow: none !important;
            }

            .mob-header-logo img {
               border-radius: 16% !important;
            }

            .mob-header-logo span:last-child {
               max-width: 116px !important;
               font-size: 0.78rem !important;
               line-height: 1.1 !important;
               font-weight: 900 !important;
            }

            .mob-header-right {
               gap: 6px !important;
            }

            .mob-icon-btn,
            .mob-avatar,
            .mob-profile-close,
            .mob-notif-action {
               box-shadow: none !important;
               -webkit-tap-highlight-color: transparent;
            }

            .mob-icon-btn {
               width: 34px !important;
               height: 34px !important;
               border-radius: 11px !important;
               border-color: var(--mob-chrome-border) !important;
               background: var(--mob-chrome-soft) !important;
               color: var(--tx) !important;
               font-size: 0.78rem !important;
            }

            .mob-icon-btn:active {
               transform: scale(0.94) !important;
               background: var(--mob-chrome-active) !important;
            }

            .mob-avatar {
               width: 34px !important;
               height: 34px !important;
               border-radius: 11px !important;
               border-color: rgba(96, 165, 250, 0.28) !important;
               font-size: 0.74rem !important;
               box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18) !important;
            }

            #mobNotifBadge {
               top: 6px !important;
               right: 6px !important;
               width: 8px !important;
               height: 8px !important;
               border-width: 2px !important;
            }

            #mob-bottom-nav {
               height: calc(var(--mob-nav-h) + var(--mob-safe-bottom)) !important;
               padding-bottom: var(--mob-safe-bottom) !important;
               background: rgba(10, 15, 25, 0.9) !important;
               border-top-color: var(--mob-chrome-border) !important;
               box-shadow: 0 -12px 28px rgba(2, 6, 23, 0.24) !important;
            }

            .lm #mob-bottom-nav {
               background: rgba(255, 255, 255, 0.94) !important;
            }

            .mob-nav-items {
               gap: 4px !important;
               padding: 7px max(8px, env(safe-area-inset-left, 0px)) 7px max(8px, env(safe-area-inset-right, 0px)) !important;
            }

            .mob-nav-item {
               min-height: 52px !important;
               gap: 3px !important;
               padding: 5px 2px !important;
               border-radius: 13px !important;
               color: #94a3b8 !important;
               font-size: 0.58rem !important;
               font-weight: 800 !important;
            }

            .lm .mob-nav-item {
               color: #64748b !important;
            }

            .mob-nav-icon {
               width: 28px !important;
               height: 28px !important;
               border-radius: 10px !important;
               background: transparent !important;
            }

            .mob-nav-icon i,
            .mob-nav-primary-icon i {
               font-size: 1rem !important;
            }

            .mob-nav-item.active:not(.mob-nav-primary) {
               color: #60a5fa !important;
               background: var(--mob-chrome-active) !important;
            }

            .lm .mob-nav-item.active:not(.mob-nav-primary) {
               color: #2563eb !important;
            }

            .mob-nav-item.active .mob-nav-icon {
               background: rgba(96, 165, 250, 0.16) !important;
               color: currentColor !important;
            }

            .mob-nav-item > span:last-child {
               max-width: 58px !important;
               line-height: 1.05 !important;
            }

            .mob-nav-primary {
               min-height: 64px !important;
               transform: translateY(-18px) !important;
               color: #60a5fa !important;
               background: transparent !important;
            }

            .lm .mob-nav-primary {
               color: #2563eb !important;
            }

            .mob-nav-primary-icon {
               width: 54px !important;
               height: 54px !important;
               border-width: 4px !important;
               border-radius: 18px !important;
               background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%) !important;
               box-shadow: 0 14px 24px rgba(37, 99, 235, 0.34) !important;
            }

            .mob-nav-primary.active .mob-nav-primary-icon {
               box-shadow: 0 16px 28px rgba(37, 99, 235, 0.4), 0 0 0 1px rgba(125, 211, 252, 0.34) !important;
            }

            .mob-profile-dropdown,
            .mob-notif-dropdown {
               top: calc(var(--mob-top-h) + var(--mob-safe-top) + 8px) !important;
               left: max(8px, env(safe-area-inset-left, 0px)) !important;
               right: max(8px, env(safe-area-inset-right, 0px)) !important;
               max-width: 430px !important;
               border-radius: var(--mob-chrome-radius) !important;
               border-color: var(--mob-chrome-border) !important;
               box-shadow: 0 22px 52px rgba(2, 6, 23, 0.34) !important;
            }

            .mob-profile-dropdown[data-origin="bottom"] {
               top: auto !important;
               bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 8px) !important;
            }

            .mob-profile-dropdown {
               --mob-menu-panel: rgba(15, 23, 42, 0.96);
               --mob-menu-soft: rgba(30, 41, 59, 0.82);
               --mob-menu-tile: rgba(30, 41, 59, 0.72);
               --mob-menu-tile-border: rgba(148, 163, 184, 0.24);
               --mob-menu-title: #f8fafc;
               --mob-menu-muted: #cbd5e1;
               --mob-menu-active: rgba(37, 99, 235, 0.24);
               --mob-menu-active-border: rgba(96, 165, 250, 0.46);
               --mob-menu-danger: #fca5a5;
               --mob-menu-danger-bg: rgba(239, 68, 68, 0.14);
               --mob-menu-danger-border: rgba(248, 113, 113, 0.34);
               background: var(--mob-menu-panel) !important;
               border-color: var(--mob-menu-tile-border) !important;
               border-radius: 16px !important;
               box-shadow: 0 22px 52px rgba(2, 6, 23, 0.38) !important;
               color: var(--mob-menu-title) !important;
            }

            .lm .mob-profile-dropdown {
               --mob-menu-panel: rgba(255, 255, 255, 0.98);
               --mob-menu-soft: #f8fafc;
               --mob-menu-tile: #ffffff;
               --mob-menu-tile-border: rgba(148, 163, 184, 0.28);
               --mob-menu-title: #0f172a;
               --mob-menu-muted: #64748b;
               --mob-menu-active: #eff6ff;
               --mob-menu-active-border: rgba(37, 99, 235, 0.32);
               --mob-menu-danger: #ef4444;
               --mob-menu-danger-bg: #fff1f2;
               --mob-menu-danger-border: rgba(248, 113, 113, 0.28);
               box-shadow: 0 18px 44px rgba(15, 23, 42, 0.16) !important;
            }

            .mob-profile-head,
            .mob-profile-pages-close {
               min-height: 56px !important;
               padding: 12px 14px !important;
               border-bottom: 1px solid var(--mob-menu-tile-border) !important;
               background: var(--mob-menu-panel) !important;
               color: var(--mob-menu-title) !important;
            }

            .mob-profile-pages-close span {
               font-size: 0.9rem !important;
               font-weight: 900 !important;
               line-height: 1.12 !important;
               color: var(--mob-menu-title) !important;
            }

            .mob-profile-close {
               width: 30px !important;
               height: 30px !important;
               border-radius: 8px !important;
               border-color: var(--mob-menu-tile-border) !important;
               background: var(--mob-menu-soft) !important;
               color: var(--mob-menu-title) !important;
               font-size: 0.72rem !important;
            }

            .mob-profile-close:active {
               transform: scale(0.94) !important;
            }

            .mob-profile-head-avatar {
               width: 42px !important;
               height: 42px !important;
               border-radius: 12px !important;
               border: 1px solid rgba(255, 255, 255, 0.22) !important;
               background: linear-gradient(135deg, #2563eb, #1d4ed8) !important;
               color: #ffffff !important;
               box-shadow: 0 10px 22px rgba(37, 99, 235, 0.26) !important;
            }

            .lm .mob-profile-head-avatar {
               border-color: rgba(191, 219, 254, 0.82) !important;
            }

            .mob-profile-name {
               color: var(--mob-menu-title) !important;
               font-size: 0.82rem !important;
               font-weight: 900 !important;
               line-height: 1.18 !important;
            }

            .mob-profile-role {
               display: inline-flex !important;
               align-items: center !important;
               min-height: 16px !important;
               margin-top: 4px !important;
               padding: 1px 6px !important;
               border-radius: 6px !important;
               background: rgba(37, 99, 235, 0.12) !important;
               color: var(--mob-menu-muted) !important;
               font-size: 0.56rem !important;
               font-weight: 900 !important;
               letter-spacing: 0 !important;
            }

            .mob-profile-menu {
               padding: 10px !important;
               background: var(--mob-menu-panel) !important;
            }

            .mob-profile-section-title {
               padding: 7px 4px 6px !important;
               color: var(--mob-menu-muted) !important;
               font-size: 0.58rem !important;
               font-weight: 900 !important;
               letter-spacing: 0.02em !important;
            }

            .mob-profile-grid,
            .mob-profile-settings-row {
               gap: 8px !important;
            }

            .mob-profile-link,
            .mob-profile-action,
            .mob-profile-language {
               min-height: 44px !important;
               border: 1px solid var(--mob-menu-tile-border) !important;
               border-radius: 8px !important;
               background: var(--mob-menu-tile) !important;
               color: var(--mob-menu-title) !important;
               box-shadow: 0 1px 0 rgba(148, 163, 184, 0.08) !important;
            }

            .mob-profile-link,
            .mob-profile-action {
               gap: 8px !important;
               padding: 8px !important;
               font-size: 0.68rem !important;
               font-weight: 900 !important;
            }

            .mob-profile-link.active {
               border-color: var(--mob-menu-active-border) !important;
               background: var(--mob-menu-active) !important;
               color: var(--mob-menu-title) !important;
               box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.08) !important;
            }

            .mob-profile-link i,
            .mob-profile-action i,
            .mob-profile-language-trigger {
               width: 30px !important;
               height: 30px !important;
               border-radius: 8px !important;
               color: #ffffff !important;
               -webkit-text-fill-color: #ffffff !important;
               font-size: 0.78rem !important;
            }

            .mob-profile-link span,
            .mob-profile-action span,
            .mob-profile-language-text {
               color: inherit !important;
               line-height: 1.16 !important;
               overflow-wrap: anywhere !important;
            }

            .mob-profile-action.danger {
               border-color: var(--mob-menu-danger-border) !important;
               background: var(--mob-menu-danger-bg) !important;
               color: var(--mob-menu-danger) !important;
            }

            .mob-profile-action.danger i {
               background: linear-gradient(135deg, #ef4444, #f97316) !important;
            }

            .mob-profile-language {
               padding: 8px !important;
               gap: 8px !important;
            }

            .mob-profile-language-text {
               color: var(--mob-menu-title) !important;
               font-size: 0.68rem !important;
               font-weight: 900 !important;
            }

            .mob-notif-dropdown {
               --mob-notif-panel: rgba(15, 23, 42, 0.96);
               --mob-notif-card: rgba(30, 41, 59, 0.72);
               --mob-notif-soft: rgba(30, 41, 59, 0.82);
               --mob-notif-border: rgba(148, 163, 184, 0.24);
               --mob-notif-title: #f8fafc;
               --mob-notif-text: #e2e8f0;
               --mob-notif-muted: #cbd5e1;
               --mob-notif-accent: #93c5fd;
               --mob-notif-danger: #fca5a5;
               --mob-notif-danger-bg: rgba(239, 68, 68, 0.14);
               --mob-notif-danger-border: rgba(248, 113, 113, 0.34);
               background: var(--mob-notif-panel) !important;
               border-color: var(--mob-notif-border) !important;
               border-radius: 14px !important;
               color: var(--mob-notif-title) !important;
               overflow: hidden !important;
            }

            .lm .mob-notif-dropdown {
               --mob-notif-panel: rgba(255, 255, 255, 0.98);
               --mob-notif-card: #ffffff;
               --mob-notif-soft: #f8fafc;
               --mob-notif-border: rgba(148, 163, 184, 0.28);
               --mob-notif-title: #0f172a;
               --mob-notif-text: #334155;
               --mob-notif-muted: #64748b;
               --mob-notif-accent: #2563eb;
               --mob-notif-danger: #ef4444;
               --mob-notif-danger-bg: #fff1f2;
               --mob-notif-danger-border: rgba(248, 113, 113, 0.28);
            }

            .mob-notif-header {
               min-height: 56px !important;
               padding: 10px 10px 10px 12px !important;
               gap: 8px !important;
               border-bottom: 1px solid var(--mob-notif-border) !important;
               background: var(--mob-notif-panel) !important;
            }

            .mob-notif-title {
               gap: 7px !important;
               color: var(--mob-notif-title) !important;
               font-size: 0.84rem !important;
               font-weight: 900 !important;
               line-height: 1.12 !important;
            }

            .mob-notif-title > i {
               width: 24px !important;
               height: 24px !important;
               display: inline-flex !important;
               align-items: center !important;
               justify-content: center !important;
               border-radius: 8px !important;
               background: rgba(37, 99, 235, 0.1) !important;
               color: var(--mob-notif-accent) !important;
               -webkit-text-fill-color: var(--mob-notif-accent) !important;
               font-size: 0.78rem !important;
            }

            .mob-notif-count {
               border: 1px solid var(--mob-notif-danger-border) !important;
               background: var(--mob-notif-danger-bg) !important;
               color: var(--mob-notif-danger) !important;
               font-size: 0.58rem !important;
               font-weight: 900 !important;
               padding: 2px 6px !important;
            }

            .mob-notif-actions {
               gap: 5px !important;
            }

            .mob-notif-action {
               min-width: 44px !important;
               min-height: 44px !important;
               padding: 10px 12px !important;
               border: 1px solid var(--mob-notif-border) !important;
               border-radius: 10px !important;
               background: var(--mob-notif-soft) !important;
               color: var(--mob-notif-title) !important;
               font-size: 0.62rem !important;
               font-weight: 900 !important;
               line-height: 1 !important;
            }

            .mob-notif-action i {
               color: inherit !important;
               font-size: 0.68rem !important;
            }

            .mob-notif-action.danger {
               border-color: var(--mob-notif-danger-border) !important;
               background: var(--mob-notif-danger-bg) !important;
               color: var(--mob-notif-danger) !important;
            }

            .mob-notif-action:active,
            .mob-notif-view-all:active {
               transform: scale(0.98) !important;
            }

            .mob-notif-list {
               min-height: 72px !important;
               max-height: min(58dvh, 390px) !important;
               padding: 10px !important;
               background: var(--mob-notif-panel) !important;
            }

            .mob-notif-empty {
               min-height: 72px !important;
               display: flex !important;
               align-items: center !important;
               justify-content: center !important;
               padding: 18px 10px !important;
               color: var(--mob-notif-muted) !important;
               font-size: 0.74rem !important;
               font-weight: 700 !important;
               line-height: 1.25 !important;
               text-align: center !important;
            }

            .mob-notif-item {
               display: grid !important;
               grid-template-columns: 44px minmax(0, 1fr) !important;
               gap: 9px !important;
               padding: 10px !important;
               border: 1px solid var(--mob-notif-border) !important;
               border-radius: 8px !important;
               background: var(--mob-notif-card) !important;
               box-shadow: 0 1px 0 rgba(148, 163, 184, 0.08) !important;
               margin-bottom: 8px !important;
            }

            .mob-notif-item.unread {
               border-color: rgba(96, 165, 250, 0.42) !important;
               background: color-mix(in srgb, var(--mob-notif-card) 86%, #2563eb 14%) !important;
            }

            .mob-notif-ico {
               width: 44px !important;
               height: 44px !important;
               min-width: 44px !important;
               min-height: 44px !important;
               border-radius: 12px !important;
               background: rgba(37, 99, 235, 0.12) !important;
               color: var(--mob-notif-accent) !important;
               font-size: 0.84rem !important;
            }

            .mob-notif-copy strong {
               color: var(--mob-notif-title) !important;
               font-size: 0.76rem !important;
               font-weight: 900 !important;
               line-height: 1.22 !important;
            }

            .mob-notif-copy span {
               color: var(--mob-notif-text) !important;
               font-size: 0.68rem !important;
               font-weight: 700 !important;
               line-height: 1.32 !important;
            }

            .mob-notif-copy small {
               color: var(--mob-notif-muted) !important;
               font-size: 0.58rem !important;
               font-weight: 800 !important;
            }

            .mob-notif-link-btn {
               min-height: 30px !important;
               display: inline-flex !important;
               align-items: center !important;
               justify-content: center !important;
               padding: 6px 9px !important;
               border: 1px solid rgba(37, 99, 235, 0.2) !important;
               border-radius: 8px !important;
               background: rgba(37, 99, 235, 0.1) !important;
               color: var(--mob-notif-accent) !important;
               font-size: 0.6rem !important;
               font-weight: 900 !important;
               line-height: 1.12 !important;
            }

            .mob-notif-row-actions {
               justify-content: flex-end !important;
               align-items: center !important;
               gap: 6px !important;
               margin-top: 7px !important;
            }

            .mob-notif-link-btn.danger {
               color: var(--mob-notif-danger) !important;
            }

            .mob-notif-footer {
               padding: 10px !important;
               border-top: 1px solid var(--mob-notif-border) !important;
               background: var(--mob-notif-panel) !important;
            }

            .mob-notif-view-all {
               min-height: 44px !important;
               border: 1px solid var(--mob-notif-border) !important;
               border-radius: 8px !important;
               background: var(--mob-notif-card) !important;
               color: var(--mob-notif-title) !important;
               font-size: 0.7rem !important;
               font-weight: 900 !important;
               gap: 6px !important;
               box-shadow: 0 1px 0 rgba(148, 163, 184, 0.08) !important;
            }

            .mob-notif-view-all i {
               color: var(--mob-notif-accent) !important;
               font-size: 0.72rem !important;
            }

            /* Final SaaSPro topbar and mobile navigation polish. */
            :root {
               --mob-top-h: 64px;
               --mob-nav-h: 74px;
            }

            #mob-header {
               --mob-shell-bg: rgba(15, 23, 42, 0.92);
               --mob-shell-bg-2: rgba(15, 23, 42, 0.82);
               --mob-shell-card: rgba(30, 41, 59, 0.78);
               --mob-shell-border: rgba(148, 163, 184, 0.22);
               --mob-shell-text: #f8fafc;
               --mob-shell-muted: #cbd5e1;
               --mob-shell-accent: #60a5fa;
               --mob-shell-accent-2: #22d3ee;
               height: calc(var(--mob-top-h) + var(--mob-safe-top)) !important;
               padding: var(--mob-safe-top) 10px 0 !important;
               background:
                  linear-gradient(180deg, var(--mob-shell-bg), var(--mob-shell-bg-2)) !important;
               border-bottom: 1px solid var(--mob-shell-border) !important;
               box-shadow: 0 12px 30px rgba(2, 6, 23, 0.26) !important;
               color: var(--mob-shell-text) !important;
            }

            .lm #mob-header {
               --mob-shell-bg: rgba(255, 255, 255, 0.96);
               --mob-shell-bg-2: rgba(248, 250, 252, 0.92);
               --mob-shell-card: rgba(255, 255, 255, 0.9);
               --mob-shell-border: rgba(148, 163, 184, 0.22);
               --mob-shell-text: #0f172a;
               --mob-shell-muted: #64748b;
               --mob-shell-accent: #2563eb;
               --mob-shell-accent-2: #0891b2;
               box-shadow: 0 10px 26px rgba(15, 23, 42, 0.1) !important;
            }

            .mob-header-logo {
               min-height: 44px !important;
               max-width: clamp(128px, 44vw, 184px) !important;
               padding: 4px 8px 4px 4px !important;
               border: 1px solid var(--mob-shell-border) !important;
               border-radius: 12px !important;
               background: var(--mob-shell-card) !important;
               color: var(--mob-shell-text) !important;
               box-shadow: 0 1px 0 rgba(148, 163, 184, 0.08) !important;
            }

            .mob-logo-ring {
               width: 32px !important;
               height: 32px !important;
               border-radius: 16% !important;
               padding: 0 !important;
               border: 2px solid #ffffff !important;
               background: #ffffff !important;
               box-shadow: none !important;
            }

            .mob-header-logo img {
               border-radius: 16% !important;
            }

            .mob-header-logo span:last-child {
               max-width: 118px !important;
               color: var(--mob-shell-text) !important;
               font-size: 0.72rem !important;
               font-weight: 900 !important;
               line-height: 1.08 !important;
            }

            .mob-header-right {
               gap: 5px !important;
            }

            .mob-icon-btn,
            .mob-avatar {
               width: 44px !important;
               min-width: 44px !important;
               height: 44px !important;
               min-height: 44px !important;
               flex: 0 0 44px !important;
               border: 1px solid var(--mob-shell-border) !important;
               border-radius: 10px !important;
               background: var(--mob-shell-card) !important;
               color: var(--mob-shell-text) !important;
               box-shadow: 0 1px 0 rgba(148, 163, 184, 0.08) !important;
            }

            .mob-icon-btn i {
               color: inherit !important;
               -webkit-text-fill-color: currentColor !important;
               font-size: 0.84rem !important;
            }

            .mob-icon-btn:active,
            .mob-avatar:active {
               transform: scale(0.94) !important;
               background: color-mix(in srgb, var(--mob-shell-card) 82%, var(--mob-shell-accent) 18%) !important;
            }

            .mob-avatar {
               background: linear-gradient(135deg, #2563eb, #0891b2) !important;
               color: #ffffff !important;
               font-size: 0.72rem !important;
               font-weight: 900 !important;
            }

            .mob-avatar img {
               border-radius: 9px !important;
            }

            #mobNotifBadge {
               top: 6px !important;
               right: 6px !important;
               width: 8px !important;
               height: 8px !important;
               background: #ef4444 !important;
               border: 2px solid var(--mob-shell-bg) !important;
               box-shadow: 0 0 0 1px rgba(248, 113, 113, 0.24) !important;
            }

            .lm #mobNotifBadge {
               border-color: #ffffff !important;
            }

            #mob-bottom-nav {
               --mob-dock-bg: rgba(15, 23, 42, 0.94);
               --mob-dock-card: rgba(30, 41, 59, 0.72);
               --mob-dock-border: rgba(148, 163, 184, 0.22);
               --mob-dock-text: #94a3b8;
               --mob-dock-active: #e0f2fe;
               --mob-dock-accent: #60a5fa;
               --mob-dock-active-bg: rgba(37, 99, 235, 0.2);
               height: calc(74px + var(--mob-safe-bottom)) !important;
               padding-bottom: var(--mob-safe-bottom) !important;
               background:
                  linear-gradient(180deg, rgba(15, 23, 42, 0.86), var(--mob-dock-bg)) !important;
               border-top: 1px solid var(--mob-dock-border) !important;
               box-shadow: 0 -14px 32px rgba(2, 6, 23, 0.3) !important;
            }

            .lm #mob-bottom-nav {
               --mob-dock-bg: rgba(255, 255, 255, 0.96);
               --mob-dock-card: rgba(248, 250, 252, 0.92);
               --mob-dock-border: rgba(148, 163, 184, 0.24);
               --mob-dock-text: #64748b;
               --mob-dock-active: #1d4ed8;
               --mob-dock-accent: #2563eb;
               --mob-dock-active-bg: rgba(37, 99, 235, 0.1);
               background:
                  linear-gradient(180deg, rgba(255, 255, 255, 0.9), var(--mob-dock-bg)) !important;
               box-shadow: 0 -10px 28px rgba(15, 23, 42, 0.12) !important;
            }

            .mob-nav-items {
               max-width: 520px !important;
               margin: 0 auto !important;
               gap: 6px !important;
               align-items: center !important;
               padding: 8px max(8px, env(safe-area-inset-left, 0px)) 8px max(8px, env(safe-area-inset-right, 0px)) !important;
            }

            .mob-nav-item {
               height: 50px !important;
               min-height: 50px !important;
               padding: 4px 2px !important;
               border: 1px solid transparent !important;
               border-radius: 11px !important;
               color: var(--mob-dock-text) !important;
               font-size: 0.58rem !important;
               font-weight: 900 !important;
               line-height: 1.05 !important;
               background: transparent !important;
               align-self: center !important;
               transform: none !important;
            }

            .mob-nav-icon {
               width: 24px !important;
               height: 24px !important;
               border-radius: 9px !important;
               background: transparent !important;
               color: currentColor !important;
            }

            .mob-nav-icon i {
               color: inherit !important;
               -webkit-text-fill-color: currentColor !important;
               font-size: 0.98rem !important;
            }

            .mob-nav-item > span:last-child {
               max-width: 58px !important;
               color: inherit !important;
               line-height: 1.05 !important;
            }

            .mob-nav-item.active:not(.mob-nav-primary) {
               border-color: color-mix(in srgb, var(--mob-dock-accent) 34%, transparent) !important;
               background: var(--mob-dock-active-bg) !important;
               color: var(--mob-dock-active) !important;
            }

            .mob-nav-item.active:not(.mob-nav-primary) .mob-nav-icon {
               background: color-mix(in srgb, var(--mob-dock-accent) 18%, transparent) !important;
               color: inherit !important;
            }

            .mob-nav-primary {
               height: 50px !important;
               min-height: 50px !important;
               padding: 4px 2px !important;
               transform: none !important;
               color: var(--mob-dock-active) !important;
               background: transparent !important;
            }

            .mob-nav-primary-icon {
               width: 34px !important;
               height: 34px !important;
               border: 0 !important;
               border-radius: 12px !important;
               background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%) !important;
               color: #ffffff !important;
               box-shadow:
                  0 8px 16px rgba(37, 99, 235, 0.28),
                  inset 0 1px 0 rgba(255, 255, 255, 0.22) !important;
            }

            .mob-nav-primary-icon i {
               color: #ffffff !important;
               -webkit-text-fill-color: #ffffff !important;
               font-size: 1.1rem !important;
            }

            .mob-nav-primary.active .mob-nav-primary-icon {
               box-shadow:
                  0 9px 18px rgba(37, 99, 235, 0.36),
                  0 0 0 1px rgba(125, 211, 252, 0.34),
                  inset 0 1px 0 rgba(255, 255, 255, 0.28) !important;
            }
         }

         /* --- Difficulty radio columns: stack on mobile --- */
         @media (max-width: 767px) {
            .col-md-4 .custom-radio { margin-bottom: 8px; }
         }

         /* --- PWA Install Prompt --- */
         #pwa-install-prompt {
            display: none; position: fixed;
            bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 20px);
            left: 16px; right: 16px; z-index: 1050;
            background: rgba(8, 8, 15, 0.95);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--bd2, #333); border-radius: 16px;
            padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            text-align: center; animation: mobFadeIn 0.3s ease;
         }
         .lm #pwa-install-prompt { background: rgba(250, 250, 254, 0.95); box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-color: #e5e7eb; }
         #pwa-install-prompt h5 { color: #fff; font-weight: 700; margin-bottom: 8px; font-size: 1.1rem; }
         .lm #pwa-install-prompt h5 { color: #111; }
         #pwa-install-prompt p { color: #aaa; font-size: 0.85rem; margin-bottom: 16px; }
         .lm #pwa-install-prompt p { color: #555; }
         .pwa-btn-wrap { display: flex; gap: 12px; justify-content: center; }
         .pwa-btn-no { flex: 1; padding: 10px; border-radius: 10px; border: 1px solid #444; background: transparent; color: #fff; font-weight: 600; cursor: pointer; }
         .lm .pwa-btn-no { border-color: #ccc; color: #333; }
         .pwa-btn-yes { flex: 1; padding: 10px; border-radius: 10px; border: none; background: #60a5fa; color: #fff; font-weight: 600; cursor: pointer; }

         .mob-logo-ring {
            background: #ffffff !important;
            border: 2px solid #ffffff !important;
            box-shadow: none !important;
            padding: 0 !important;
         }
         .mob-header-logo img {
             background: transparent !important;
             object-fit: contain !important;
             filter: drop-shadow(0 6px 10px rgba(37, 99, 235, 0.18));
          }

         body.user-mobile-shell #mob-header {
            justify-content: space-between !important;
         }

            body.user-mobile-shell #mob-header .mob-header-brand-pill {
               flex: 0 0 auto !important;
               width: clamp(112px, calc(100vw - 282px), 170px) !important;
               max-width: none !important;
               height: 44px !important;
               min-height: 44px !important;
               box-sizing: border-box !important;
               padding: 5px 10px 5px 5px !important;
            gap: 5px !important;
            overflow: hidden !important;
            border: 1px solid rgba(191, 219, 254, 0.72) !important;
            border-radius: 10px !important;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 62%, #06b6d4 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.22) !important;
         }

         body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-logo-ring {
            width: 24px !important;
            height: 24px !important;
            border: 1px solid rgba(255, 255, 255, 0.94) !important;
            border-radius: 7px !important;
            background: #ffffff !important;
         }

         body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-header-brand-text {
            display: inline-flex !important;
            align-items: baseline !important;
            flex: 1 1 auto !important;
            gap: 2px !important;
            margin: 0 !important;
            min-width: 0 !important;
            max-width: none !important;
            overflow: visible !important;
            text-overflow: clip !important;
            white-space: nowrap !important;
            color: #ffffff !important;
            -webkit-text-fill-color: currentColor !important;
            font-size: 0.84rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            letter-spacing: 0 !important;
            text-transform: capitalize !important;
            text-shadow: 0 1px 5px rgba(15, 23, 42, 0.16) !important;
         }

         body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-header-brand-text span {
            display: inline !important;
            max-width: none !important;
            overflow: visible !important;
            text-overflow: clip !important;
            white-space: nowrap !important;
            color: #67e8f9 !important;
            -webkit-text-fill-color: #67e8f9 !important;
            font-size: inherit !important;
            font-weight: inherit !important;
            line-height: inherit !important;
            letter-spacing: inherit !important;
            text-transform: inherit !important;
         }

         @media (max-width: 380px) {
            body.user-mobile-shell #mob-header .mob-header-brand-pill {
               width: 108px !important;
               padding-right: 7px !important;
               gap: 5px !important;
            }

            body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-header-brand-text {
               font-size: 0.74rem !important;
            }
         }

         @media (max-width: 340px) {
            body.user-mobile-shell #mob-header .mob-header-brand-pill {
               width: 102px !important;
               padding-right: 6px !important;
            }

            body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-logo-ring {
               width: 21px !important;
               height: 21px !important;
            }

            body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-header-brand-text {
               font-size: 0.64rem !important;
            }
         }

         @media (max-width: 991px) {
            body.user-mobile-shell #mob-header {
               --mob-top-h: 64px;
               height: calc(var(--mob-top-h) + var(--mob-safe-top)) !important;
               min-height: calc(var(--mob-top-h) + var(--mob-safe-top)) !important;
               padding: var(--mob-safe-top) 8px 0 10px !important;
               gap: 7px !important;
               align-items: center !important;
               background: rgba(238, 249, 249, 0.94) !important;
               border-bottom: 1px solid rgba(203, 213, 225, 0.72) !important;
               box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08) !important;
            }

            html:not(.lm) body.user-mobile-shell #mob-header {
               background: rgba(10, 15, 25, 0.9) !important;
               border-bottom-color: rgba(148, 163, 184, 0.18) !important;
               box-shadow: 0 10px 24px rgba(2, 6, 23, 0.22) !important;
            }

            body.user-mobile-shell #mob-header .mob-header-brand-pill {
               width: clamp(112px, 34vw, 148px) !important;
               height: 44px !important;
               min-height: 44px !important;
               padding: 5px 9px 5px 5px !important;
               gap: 5px !important;
               border-radius: 12px !important;
            }

            body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-logo-ring {
               width: 30px !important;
               height: 30px !important;
               min-width: 30px !important;
               flex-basis: 30px !important;
               border-radius: 8px !important;
            }

            body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-header-brand-text {
               font-size: clamp(0.68rem, 2.35vw, 0.78rem) !important;
               line-height: 1 !important;
            }

            body.user-mobile-shell #mob-header .mob-header-right {
               flex: 0 0 auto !important;
               gap: 4px !important;
               min-width: 0 !important;
            }

            body.user-mobile-shell #mob-header .mob-icon-btn,
            body.user-mobile-shell #mob-header .mob-avatar {
               width: 44px !important;
               min-width: 44px !important;
               height: 44px !important;
               min-height: 44px !important;
               flex: 0 0 44px !important;
               padding: 0 !important;
               border-radius: 12px !important;
               border: 1px solid rgba(203, 213, 225, 0.78) !important;
               background: rgba(255, 255, 255, 0.94) !important;
               color: #0f172a !important;
               box-shadow: 0 5px 12px rgba(15, 23, 42, 0.08) !important;
            }

            html:not(.lm) body.user-mobile-shell #mob-header .mob-icon-btn,
            html:not(.lm) body.user-mobile-shell #mob-header .mob-avatar {
               border-color: rgba(148, 163, 184, 0.22) !important;
               background: rgba(255, 255, 255, 0.08) !important;
               color: #f8fafc !important;
               box-shadow: none !important;
            }

            body.user-mobile-shell #mob-header .mob-icon-btn i {
               font-size: 0.9rem !important;
               line-height: 1 !important;
            }

            body.user-mobile-shell #mob-header .mob-avatar {
               overflow: visible !important;
               position: relative !important;
            }

            body.user-mobile-shell #mob-header .mob-avatar img {
               display: block !important;
               width: 100% !important;
               height: 100% !important;
               object-fit: cover !important;
               border-radius: 11px !important;
            }

            body.user-mobile-shell #mob-header .mob-avatar::after {
               content: "";
               position: absolute;
               right: -1px;
               bottom: -1px;
               width: 8px;
               height: 8px;
               border-radius: 999px;
               background: #22c55e;
               border: 2px solid #ffffff;
               box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.18);
            }

            html:not(.lm) body.user-mobile-shell #mob-header .mob-avatar::after {
               border-color: #0f172a;
            }

            body.user-mobile-shell #mob-header #mobNotifBadge {
               top: 8px !important;
               right: 8px !important;
               width: 7px !important;
               height: 7px !important;
               border-width: 2px !important;
               background: #ef4444 !important;
            }

            body.user-mobile-shell #mob-header #mobFullscreenBtn {
               display: inline-flex !important;
            }
         }

         @media (max-width: 420px) {
            body.user-mobile-shell #mob-header #mobFullscreenBtn {
               display: none !important;
            }

            body.user-mobile-shell #mob-header .mob-header-brand-pill {
               width: 132px !important;
            }
         }

         @media (max-width: 380px) {
            body.user-mobile-shell #mob-header .mob-header-brand-pill {
               width: 132px !important;
            }

            body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-header-brand-text {
               font-size: 0.68rem !important;
            }
         }

         @media (max-width: 350px) {
            body.user-mobile-shell #mob-header .mob-header-brand-pill {
               width: 54px !important;
               padding-right: 5px !important;
               justify-content: center !important;
            }

            body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-header-brand-text {
               display: none !important;
            }
         }

       </style>
      @stack('styles')
   </head>
   <body class="user-mobile-shell mobile-shell" data-layout-shell="mobile" data-app-surface="user">

      @php
         $mobileHeaderPageTitle = trim($__env->yieldContent('page-title')) ?: (trim($__env->yieldContent('title')) ?: 'Overview');
      @endphp

      <!-- ===== MOBILE TOP HEADER ===== -->
      <header id="mob-header">
         <a href="{{ route('dashboard') }}" class="mob-header-logo mob-header-brand-pill" aria-label="Go to dashboard">
            <span class="mob-logo-ring">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
            </span>
            <h6 class="mob-header-brand-text">SpeakReady <span>AI</span></h6>
         </a>
         <div class="mob-header-right">
            <button class="mob-icon-btn" id="mobTutorialBtn" type="button" aria-label="Start tutorial" onclick="triggerMobTutorial()" title="Start Tutorial" style="color: #60a5fa; border-color: rgba(96,165,250,0.3);">
               <i class="fa-solid fa-circle-play"></i>
            </button>
            <button class="mob-icon-btn" id="mobFullscreenBtn" type="button" aria-label="Enter fullscreen" title="Enter fullscreen" data-user-fullscreen-toggle>
               <i class="fa-solid fa-expand" id="mobFullscreenIcon"></i>
            </button>
            <button class="mob-icon-btn" id="mobThBtn" type="button" aria-label="Toggle color theme" onclick="toggleTheme()" title="Toggle theme">
               <i class="fa-solid fa-sun" id="mobSunI" style="display:none"></i>
               <i class="fa-solid fa-moon" id="mobMoonI"></i>
            </button>
            <div class="mob-notif-wrap" id="mobNotifWrap">
               <button class="mob-icon-btn" id="mobBellBtn" type="button" aria-label="Open notifications" aria-controls="mobNotifDropdown" aria-expanded="false" aria-haspopup="true" onclick="toggleMobileNotif(event)" style="position:relative;">
                  <i class="fa-regular fa-bell"></i>
                  <span id="mobNotifBadge" style="position:absolute;top:5px;right:5px;width:9px;height:9px;border-radius:50%;background:#f87171;border:2px solid var(--bg);display:none;"></span>
               </button>
            </div>
            <div class="mob-profile-wrap" id="mobProfileWrap">
               <button class="mob-avatar" id="mobProfileBtn" type="button" aria-label="Open account menu" aria-controls="mobProfileDropdown" aria-expanded="false" aria-haspopup="true" onclick="toggleMobileProfile(event, 'account')" title="Profile" style="padding:0;overflow:hidden;border:1px solid var(--bd);">
                  @if(Auth::check() && Auth::user()->profile_photo_path)
                     @php
                         $photoPath = Auth::user()->profile_photo_path;
                         $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                     @endphp
                     <img src="{{ $photoUrl }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                  @else
                     {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U' }}
                  @endif
               </button>
            </div>
         </div>
      </header>

      <div class="mob-profile-dropdown" id="mobProfileDropdown" aria-hidden="true" role="dialog" aria-modal="false" aria-labelledby="mobProfileBtn" data-mode="pages" data-origin="top">
         <div class="mob-profile-head">
            <div class="mob-profile-head-avatar">
               @if(Auth::check() && Auth::user()->profile_photo_path)
                  <img src="{{ $photoUrl }}" alt="Avatar">
               @else
                  {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U' }}
               @endif
            </div>
            <div class="mob-profile-head-meta">
               <div class="mob-profile-name">{{ Auth::user()->name ?? 'User' }}</div>
               <div class="mob-profile-role">{{ Auth::check() && Auth::user()->is_admin ? 'ADMIN' : 'USER' }}</div>
            </div>
            <button class="mob-profile-close" type="button" onclick="event.stopPropagation(); closeMobileProfile();" aria-label="Close profile menu"><i class="fa-solid fa-xmark"></i></button>
         </div>
         <div class="mob-profile-menu" id="mobProfileMenu">
            <div class="mob-profile-pages">
               <div class="mob-profile-pages-close">
                  <span>More</span>
                  <button class="mob-profile-close" type="button" onclick="event.stopPropagation(); closeMobileProfile();" aria-label="Close more menu"><i class="fa-solid fa-xmark"></i></button>
               </div>
                <div class="mob-profile-section-title">Pages</div>
                <div class="mob-profile-grid">
                <a href="{{ route('user.modules.index') }}" class="mob-profile-link profile-nav-emerald {{ request()->routeIs('user.modules.*') ? 'active' : '' }}"><i class="fa-solid fa-book-open-reader"></i><span>Modules</span></a>
                <a href="{{ route('user.drills.voice') }}" class="mob-profile-link profile-nav-rose {{ request()->routeIs('user.drills.voice') ? 'active' : '' }}"><i class="fa-solid fa-ear-listen"></i><span>Voice</span></a>
               <a href="{{ route('user.missions') }}" class="mob-profile-link profile-nav-cyan {{ request()->routeIs('user.missions') ? 'active' : '' }}"><i class="fa-solid fa-route"></i><span>Missions</span></a>
               <a href="{{ route('user.learning') }}" class="mob-profile-link profile-nav-amber {{ request()->routeIs('user.learning*') ? 'active' : '' }}"><i class="fa-solid fa-gamepad"></i><span>Challenges</span></a>
               <a href="{{ route('user.coach') }}" class="mob-profile-link profile-nav-purple {{ request()->routeIs('user.coach*') ? 'active' : '' }}"><i class="fa-solid fa-robot"></i><span>Coach</span></a>
               <a href="{{ route('user.reports') }}" class="mob-profile-link profile-nav-blue {{ request()->routeIs('user.reports') ? 'active' : '' }}"><i class="fa-solid fa-folder-open"></i><span>Reports</span></a>
               <a href="{{ route('user.leaderboard') }}" class="mob-profile-link profile-nav-amber {{ request()->routeIs('user.leaderboard') ? 'active' : '' }}"><i class="fa-solid fa-medal"></i><span>Mastery</span></a>
               <a href="{{ route('user.notifications') }}" class="mob-profile-link profile-nav-rose {{ request()->routeIs('user.notifications') ? 'active' : '' }}"><i class="fa-solid fa-bell"></i><span>Notifications</span></a>
               <a href="{{ route('user.account') }}" class="mob-profile-link profile-nav-slate {{ request()->routeIs('user.account') ? 'active' : '' }}"><i class="fa-solid fa-user-gear"></i><span>Account</span></a>
               </div>
            </div>

            <div class="mob-profile-account">
               <div class="mob-profile-section-title">Account</div>
               <div class="mob-profile-grid mb-2">
                  <a href="{{ route('user.account') }}" class="mob-profile-link profile-nav-slate {{ request()->routeIs('user.account') ? 'active' : '' }}"><i class="fa-solid fa-user-gear"></i><span>Account Management</span></a>
                  <a href="{{ route('user.notifications') }}" class="mob-profile-link profile-nav-rose {{ request()->routeIs('user.notifications') ? 'active' : '' }}"><i class="fa-solid fa-bell"></i><span>Notifications</span></a>
               </div>
               <div class="mob-profile-section-title">Settings</div>
               <div class="mob-profile-settings-row">
                  <form action="{{ route('user.language.update') }}" method="POST" class="mob-profile-language">
                     @csrf
                     <label for="mobileProfileLanguageSelect"><i class="fa-solid fa-language"></i>Language</label>
                     <span class="mob-profile-language-trigger" aria-hidden="true"><i class="fa-solid fa-language"></i></span>
                     <span class="mob-profile-language-text">Language</span>
                     <select id="mobileProfileLanguageSelect" name="preferred_language" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($supportedLanguages as $languageCode => $language)
                           <option value="{{ $languageCode }}" {{ ($currentLanguageCode ?? 'en') === $languageCode ? 'selected' : '' }}>{{ $language['native_label'] ?? $language['label'] }}</option>
                        @endforeach
                     </select>
                  </form>
                  <form action="{{ route('logout') }}" method="POST">
                     @csrf
                     <button type="submit" class="mob-profile-action danger"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></button>
                  </form>
               </div>
            </div>
         </div>
      </div>

      <div class="mob-notif-dropdown" id="mobNotifDropdown" aria-hidden="true" role="dialog" aria-modal="false" aria-labelledby="mobNotifDropdownTitle">
         <div class="mob-notif-header">
            <div class="mob-notif-title">
               <i class="fa-regular fa-bell" style="color:var(--pur)"></i>
               <span id="mobNotifDropdownTitle">Notifications</span>
               <span class="mob-notif-count" id="mobUnreadCountBadge">0 new</span>
            </div>
            <div class="mob-notif-actions">
               <button class="mob-notif-action" type="button" onclick="markAllMobileNotificationsRead(event)" title="Mark all as read"><i class="fa-solid fa-check"></i><span>Read</span></button>
               <button class="mob-notif-action danger" type="button" onclick="clearAllMobileNotifications(event)" title="Clear all"><i class="fa-solid fa-trash"></i><span>Clear</span></button>
               <button class="mob-notif-action" type="button" onclick="toggleMobileNotif(event)" aria-label="Close notifications"><i class="fa-solid fa-xmark"></i></button>
            </div>
         </div>
         <div class="mob-notif-list" id="mobNotifListContainer">
            <div class="text-center py-4" style="color:var(--tx3);font-size:0.85rem;">Loading notifications...</div>
         </div>
         <div class="mob-notif-footer">
            <a href="{{ route('user.notifications') }}" class="mob-notif-view-all"><i class="fa-solid fa-list"></i>View All Notifications</a>
         </div>
      </div>

      <!-- ===== PAGE CONTENT ===== -->
      <div id="mob-content" data-layout-shell="mobile">
         <div class="db-content" id="userAppContent" data-layout-shell="mobile" data-user-ajax-content data-page-title="{{ $mobileHeaderPageTitle }}">
            @yield('content')
         </div>
      </div>

      <div id="mobMoreBackdrop" class="mob-more-backdrop" aria-hidden="true" onclick="closeMobileProfile()"></div>

      <div class="modal fade sr-mobile-confirm-modal" id="srMobileConfirmModal" tabindex="-1" aria-labelledby="srMobileConfirmModalTitle" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
               <div class="modal-header">
                  <div class="sr-mobile-confirm-icon" id="srMobileConfirmModalIcon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></div>
                  <h5 class="modal-title" id="srMobileConfirmModalTitle">Confirm Action</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                  <p id="srMobileConfirmModalMessage">Are you sure you want to continue?</p>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn sr-mobile-confirm-cancel" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn sr-mobile-confirm-action" id="srMobileConfirmModalAction">Confirm</button>
               </div>
            </div>
         </div>
      </div>

      <!-- ===== BOTTOM NAVIGATION ===== -->
      <nav id="mob-bottom-nav" aria-label="Main navigation">
         <div class="mob-nav-items">
            <a href="{{ route('dashboard') }}"
               class="mob-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               id="mobnav-home">
               <span class="mob-nav-icon"><i class="fa-solid fa-house"></i></span>
               <span>Home</span>
            </a>
            <a href="{{ route('user.progress') }}"
               class="mob-nav-item {{ request()->routeIs('user.progress') ? 'active' : '' }}"
               id="mobnav-progress">
               <span class="mob-nav-icon"><i class="fa-solid fa-chart-line"></i></span>
               <span>Progress</span>
            </a>
            <a href="{{ route('interview.setup') }}"
               class="mob-nav-item mob-nav-primary {{ request()->routeIs('interview.*') ? 'active' : '' }}"
               id="mobnav-interview">
               <span class="mob-nav-primary-icon"><i class="fa-solid fa-microphone"></i></span>
               <span>Interview</span>
            </a>
            <a href="{{ route('user.feedback') }}"
               class="mob-nav-item {{ request()->routeIs('user.feedback', 'user.review') ? 'active' : '' }}"
               id="mobnav-feedback">
               <span class="mob-nav-icon"><i class="fa-solid fa-clipboard-check"></i></span>
               <span>Feedback</span>
            </a>
            <button class="mob-nav-item {{ request()->routeIs('user.account', 'user.notifications', 'user.modules.*', 'user.drills.voice', 'user.missions', 'user.learning*', 'user.coach*', 'user.reports', 'user.leaderboard') ? 'active' : '' }}"
                    id="mobnav-more"
                    type="button"
                    aria-controls="mobProfileDropdown"
                    aria-expanded="false"
                    aria-label="Open more menu"
                    onclick="toggleMobileProfile(event, 'pages')">
               <span class="mob-nav-icon"><i class="fa-solid fa-grid-2"></i></span>
               <span>More</span>
            </button>
         </div>
      </nav>

      @include('mobile.partials.viewport-mobile-cookie')


      <!-- ===== PWA INSTALL PROMPT ===== -->
      <div id="pwa-install-prompt">
         <h5>Install SpeakReady AI</h5>
         <p>Do you want to install this app for a better and faster experience?</p>
         <div class="pwa-btn-wrap">
            <button id="pwa-btn-no" class="pwa-btn-no">No</button>
            <button id="pwa-btn-yes" class="pwa-btn-yes">Yes</button>
         </div>
      </div>

      <!-- ======================== SCRIPTS ======================== -->
      <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
      <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
      @include('mobile.partials.flash-modal')
      <script src="{{ asset('js/aos.js') }}"></script>
      <script src="{{ asset('js/chart.umd.min.js') }}"></script>
      <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
      <script src="{{ asset('js/main.js?v=7') }}"></script>
      @include('mobile.partials.onboarding-script')
      @include('mobile.partials.language-translation')
      <script src="{{ asset('js/user-ui.js') }}?v=13" defer></script>

      <script>
         (function initializeSpeakReadyMobileConfirm() {
            const getConfirmParts = () => ({
               modalElement: document.getElementById('srMobileConfirmModal'),
               title: document.getElementById('srMobileConfirmModalTitle'),
               message: document.getElementById('srMobileConfirmModalMessage'),
               action: document.getElementById('srMobileConfirmModalAction'),
               icon: document.getElementById('srMobileConfirmModalIcon')
            });

            window.SpeakReadyMobileConfirm = {
               show(options = {}) {
                  const parts = getConfirmParts();
                  if (!parts.modalElement || !parts.action || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
                     return Promise.resolve(false);
                  }

                  const title = options.title || 'Confirm Action';
                  const message = options.message || 'Are you sure you want to continue?';
                  const actionText = options.actionText || options.action || 'Confirm';
                  const variant = options.variant || 'default';

                  if (parts.title) parts.title.textContent = title;
                  if (parts.message) parts.message.textContent = message;
                  parts.action.textContent = actionText;
                  parts.action.classList.toggle('is-danger', variant === 'danger');
                  if (parts.icon) parts.icon.classList.toggle('is-danger', variant === 'danger');

                  const modal = bootstrap.Modal.getOrCreateInstance(parts.modalElement);

                  return new Promise(resolve => {
                     let settled = false;
                     const cleanup = () => {
                        parts.action.removeEventListener('click', handleAction);
                        parts.modalElement.removeEventListener('hidden.bs.modal', handleHidden);
                     };
                     const finish = (confirmed) => {
                        if (settled) return;
                        settled = true;
                        cleanup();
                        resolve(confirmed);
                     };
                     const handleAction = () => {
                        modal.hide();
                        finish(true);
                     };
                     const handleHidden = () => finish(false);

                     parts.action.addEventListener('click', handleAction);
                     parts.modalElement.addEventListener('hidden.bs.modal', handleHidden);
                     if (typeof closeMobileNotif === 'function') closeMobileNotif();
                     if (typeof closeMobileProfile === 'function') closeMobileProfile();
                     modal.show();
                  });
               }
            };

            document.addEventListener('submit', function(event) {
               const form = event.target?.closest?.('form[data-sr-confirm-form]');
               if (!form || form.dataset.srConfirmSubmitting === 'true') return;

               event.preventDefault();
               window.SpeakReadyMobileConfirm.show({
                  title: form.dataset.srConfirmTitle,
                  message: form.dataset.srConfirmMessage,
                  action: form.dataset.srConfirmAction,
                  variant: form.dataset.srConfirmVariant
               }).then(confirmed => {
                  if (!confirmed) return;

                  form.dataset.srConfirmSubmitting = 'true';
                  HTMLFormElement.prototype.submit.call(form);
               });
            });
         })();

         // Close open header menus with Escape
         (function() {
            document.addEventListener('keydown', e => {
               if (e.key === 'Escape') {
                  closeMobileNotif();
                  closeMobileProfile();
               }
            });
         })();

         // PWA Service Worker
         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js?v=11').then(function(registration) {
                  console.log('ServiceWorker registration successful');
               }, function(err) {
                  console.log('ServiceWorker registration failed: ', err);
               });
            });
         }
         
         function triggerMobTutorial(attempt = 0) {
            if (typeof window.startOnboardingTour === 'function') {
               const started = window.startOnboardingTour();
               if (started !== false) return;
            }

            if (typeof window.startOnboardingTour !== 'function' && typeof window.initSpeakReadyFallbackTour === 'function') {
               window.initSpeakReadyFallbackTour(window.SpeakReadyTourContext || {});
            }

            if (typeof window.startOnboardingTour === 'function') {
               const started = window.startOnboardingTour();
               if (started !== false) return;
            }

            if (attempt < 20) {
               window.setTimeout(function() {
                  triggerMobTutorial(attempt + 1);
               }, 100);
               return;
            }

            console.warn('Tutorial could not initialize on this page.');
         }

         // PWA Install Prompt Logic
         let deferredPrompt;
         const suppressPwaInstallPrompt = @json(request()->routeIs('interview.session'));
         let pwaPromptRetryTimer = null;

         function isPwaInstallPromptBlocked() {
            return Boolean(
               document.body.classList.contains('sr-native-tour-active') ||
               document.querySelector('.sr-native-tour-overlay, .sr-native-tour-popover, .driver-popover, .modal.show, .dropdown-menu.show, [aria-modal="true"]')
            );
         }

         function schedulePwaInstallPrompt(attempt = 0) {
            window.clearTimeout(pwaPromptRetryTimer);

            pwaPromptRetryTimer = window.setTimeout(function() {
               const prompt = document.getElementById('pwa-install-prompt');
               if (!prompt || !deferredPrompt || suppressPwaInstallPrompt || localStorage.getItem('pwa_prompt_dismissed')) return;

               if (isPwaInstallPromptBlocked()) {
                  if (attempt < 24) {
                     schedulePwaInstallPrompt(attempt + 1);
                  }
                  return;
               }

               prompt.style.display = 'block';
            }, attempt === 0 ? 2600 : 1800);
         }

         window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            schedulePwaInstallPrompt();
         });

         document.getElementById('pwa-btn-yes')?.addEventListener('click', async () => {
            window.clearTimeout(pwaPromptRetryTimer);
            document.getElementById('pwa-install-prompt').style.display = 'none';
            if (deferredPrompt) {
               deferredPrompt.prompt();
               const { outcome } = await deferredPrompt.userChoice;
               console.log(`User response to the install prompt: ${outcome}`);
               deferredPrompt = null;
            }
         });

         document.getElementById('pwa-btn-no')?.addEventListener('click', () => {
            window.clearTimeout(pwaPromptRetryTimer);
            document.getElementById('pwa-install-prompt').style.display = 'none';
            localStorage.setItem('pwa_prompt_dismissed', 'true');
         });

         function escapeMobileNotifHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, char => ({
               '&': '&amp;',
               '<': '&lt;',
               '>': '&gt;',
               '"': '&quot;',
               "'": '&#039;'
            }[char]));
         }

         function toggleMobileNotif(e) {
            if (e) e.stopPropagation();
            const dropdown = document.getElementById('mobNotifDropdown');
            const button = document.getElementById('mobBellBtn');
            if (!dropdown) return;
            const willOpen = !dropdown.classList.contains('open');
            dropdown.classList.toggle('open', willOpen);
            dropdown.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
            if (button) button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (willOpen) {
               closeMobileProfile();
               fetchMobileNotifications(true);
            }
         }

         function closeMobileNotif() {
            const dropdown = document.getElementById('mobNotifDropdown');
            const button = document.getElementById('mobBellBtn');
            if (!dropdown) return;
            dropdown.classList.remove('open');
            dropdown.setAttribute('aria-hidden', 'true');
            if (button) button.setAttribute('aria-expanded', 'false');
         }

         function playMobileNavIconMotion(item) {
            if (!item) return;
            if (item._mobileNavMotionTimer) {
               window.clearTimeout(item._mobileNavMotionTimer);
            }
            item.classList.remove('nav-icon-moving');
            void item.offsetWidth;
            item.classList.add('nav-icon-moving');
            item._mobileNavMotionTimer = window.setTimeout(() => {
               item.classList.remove('nav-icon-moving');
               item._mobileNavMotionTimer = null;
            }, item.classList.contains('mob-nav-primary') ? 620 : 540);
         }

         document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('#mob-bottom-nav .mob-nav-item').forEach(item => {
               item.addEventListener('click', () => playMobileNavIconMotion(item), { passive: true });
               item.addEventListener('keydown', event => {
                  if (event.key === 'Enter' || event.key === ' ') {
                     playMobileNavIconMotion(item);
                  }
               });
            });
         });

         function toggleMobileProfile(e, mode = 'pages') {
            if (e) e.stopPropagation();
            const dropdown = document.getElementById('mobProfileDropdown');
            const button = document.getElementById('mobProfileBtn');
            const bottomButton = document.getElementById('mobnav-more');
            const moreBackdrop = document.getElementById('mobMoreBackdrop');
            if (!dropdown) return;
            const origin = mode === 'pages' ? 'bottom' : 'top';
            const currentMode = dropdown.getAttribute('data-mode') || 'pages';
            dropdown.setAttribute('data-mode', mode);
            dropdown.setAttribute('data-origin', origin);
            const willOpen = !dropdown.classList.contains('open');
            const shouldStayOpen = dropdown.classList.contains('open') && currentMode !== mode;
            if (shouldStayOpen) {
               if (moreBackdrop) moreBackdrop.classList.add('open');
               resetMobileProfileMenuScroll();
               return;
            }
            dropdown.classList.toggle('open', willOpen);
            dropdown.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
            if (button) button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (bottomButton) bottomButton.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            if (moreBackdrop) moreBackdrop.classList.toggle('open', willOpen);
            if (willOpen) {
               closeMobileNotif();
               resetMobileProfileMenuScroll();
            }
         }

         function resetMobileProfileMenuScroll() {
            const menu = document.getElementById('mobProfileMenu');
            if (!menu) return;
            const reset = () => {
               menu.scrollTop = 0;
               menu.scrollTo({ top: 0, left: 0, behavior: 'auto' });
            };
            reset();
            requestAnimationFrame(reset);
            setTimeout(reset, 80);
         }

         function closeMobileProfile() {
            const dropdown = document.getElementById('mobProfileDropdown');
            const button = document.getElementById('mobProfileBtn');
            const bottomButton = document.getElementById('mobnav-more');
            const moreBackdrop = document.getElementById('mobMoreBackdrop');
            if (!dropdown) return;
            dropdown.classList.remove('open');
            dropdown.setAttribute('aria-hidden', 'true');
            if (button) button.setAttribute('aria-expanded', 'false');
            if (bottomButton) bottomButton.setAttribute('aria-expanded', 'false');
            if (moreBackdrop) moreBackdrop.classList.remove('open');
         }

         const mobileNotificationsUrl = @json(route('user.notifications'));
         const mobileNotifJsonHeaders = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
         };

         function safeMobileNotificationIcon(value) {
            const icon = String(value ?? '').trim();

            return /^fa-[a-z0-9-]+$/.test(icon) ? icon : 'fa-bell';
         }

         function isMobileNotificationDrawerOpen() {
            return Boolean(document.getElementById('mobNotifDropdown')?.classList.contains('open'));
         }

         async function requestMobileNotificationJson(url, options = {}) {
            const response = await fetch(url, {
               ...options,
               headers: {
                  ...mobileNotifJsonHeaders,
                  ...(options.headers || {})
               }
            });
            const contentType = response.headers.get('content-type') || '';
            const payload = contentType.includes('application/json') ? await response.json().catch(() => null) : null;

            if (!response.ok) {
               const message = payload?.message || (response.status === 401
                  ? 'Please sign in again to view notifications.'
                  : 'Notifications could not be loaded.');
               throw new Error(message);
            }

            return payload || {};
         }

         function renderMobileNotificationStatus(message, isError = false) {
            const listContainer = document.getElementById('mobNotifListContainer');
            if (!listContainer) return;

            listContainer.innerHTML = `
               <div class="mob-notif-status ${isError ? 'is-error' : ''}">
                  <i class="fa-solid ${isError ? 'fa-circle-exclamation' : 'fa-bell-slash'}"></i>
                  <span>${escapeMobileNotifHtml(message)}</span>
                  ${isError ? '<button class="mob-notif-retry" type="button" data-mob-notif-retry>Retry</button>' : ''}
               </div>
            `;
         }

         function handleMobileNotificationError(error, fallbackMessage = 'Notifications could not be loaded.', shouldReport = true) {
            if (shouldReport) {
               console.warn('Mobile notification action failed:', error);
            }

            if (shouldReport && isMobileNotificationDrawerOpen()) {
               renderMobileNotificationStatus(error?.message || fallbackMessage, true);
            }
         }

         function fetchMobileNotifications(forceRender = false, options = {}) {
            const quiet = options.quiet === true;
            const shouldRender = forceRender || (!quiet && isMobileNotificationDrawerOpen());
            if (shouldRender) {
               renderMobileNotificationStatus('Loading notifications...');
            }

            requestMobileNotificationJson('/notifications/fetch')
               .then(data => updateMobileNotifUI(data, forceRender))
               .catch(error => handleMobileNotificationError(error, 'Notifications could not be loaded.', !quiet && shouldRender));
         }

         function updateMobileNotifUI(data = {}, forceRender = false) {
            const badge = document.getElementById('mobNotifBadge');
            const unreadBadge = document.getElementById('mobUnreadCountBadge');
            const listContainer = document.getElementById('mobNotifListContainer');
            const notifications = Array.isArray(data.notifications) ? data.notifications : [];
            const unreadCount = Number.isFinite(Number(data.unreadCount))
               ? Math.max(0, Number(data.unreadCount))
               : notifications.filter(notification => !notification.read_at).length;

            if (badge) {
               badge.style.display = unreadCount > 0 ? 'block' : 'none';
            }

            if (unreadBadge) {
               if (unreadCount > 0) {
                  unreadBadge.style.display = 'inline-block';
                  unreadBadge.textContent = unreadCount + ' new';
               } else {
                  unreadBadge.style.display = 'none';
               }
            }

            if (!listContainer || (!forceRender && !isMobileNotificationDrawerOpen())) return;

            if (notifications.length === 0) {
               renderMobileNotificationStatus('No notifications to show.');
               return;
            }

            listContainer.innerHTML = notifications.map(notification => {
               const notificationId = escapeMobileNotifHtml(notification.id || '');
               const title = escapeMobileNotifHtml(notification.data?.title || 'Notification');
               const message = escapeMobileNotifHtml(notification.data?.message || '');
               const icon = safeMobileNotificationIcon(notification.data?.icon);
               const createdAt = notification.created_at ? new Date(notification.created_at) : null;
               const date = createdAt && !Number.isNaN(createdAt.getTime())
                  ? escapeMobileNotifHtml(createdAt.toLocaleString())
                  : '';
               const unreadClass = notification.read_at ? '' : 'unread';
               const actionAttr = notificationId ? `data-mob-notif-id="${notificationId}"` : 'disabled aria-disabled="true"';
               const markRead = notification.read_at ? '' : `<button class="mob-notif-link-btn" type="button" data-mob-notif-action="mark-read" ${actionAttr}>Mark as read</button>`;

               return `
                  <div class="mob-notif-item ${unreadClass}">
                     <button class="mob-notif-ico" type="button" data-mob-notif-action="open" aria-label="Open notifications page">
                        <i class="fa-solid ${icon}"></i>
                     </button>
                     <div class="mob-notif-copy">
                        <div class="mob-notif-copy-main" data-mob-notif-action="open" role="link" tabindex="0">
                           <strong>${title}</strong>
                           <span>${message}</span>
                           ${date ? `<small><i class="fa-regular fa-clock me-1"></i>${date}</small>` : ''}
                        </div>
                        <div class="mob-notif-row-actions">
                           ${markRead}
                           <button class="mob-notif-link-btn danger" type="button" data-mob-notif-action="delete" ${actionAttr}>Delete</button>
                        </div>
                     </div>
                  </div>
               `;
            }).join('');
         }

         function refreshMobileNotificationsAfterAction(data) {
            if (data.success === false) {
               throw new Error(data.message || 'Notification action could not be completed.');
            }

            fetchMobileNotifications(true);
            if (typeof reloadNotificationsPage === 'function') reloadNotificationsPage();
         }

         function markAllMobileNotificationsRead(e) {
            if (e) e.stopPropagation();
            requestMobileNotificationJson('/notifications/read-all', { method: 'POST' })
               .then(refreshMobileNotificationsAfterAction)
               .catch(error => handleMobileNotificationError(error, 'Notifications could not be marked as read.'));
         }

         function clearAllMobileNotifications(e) {
            if (e) e.stopPropagation();
            window.SpeakReadyMobileConfirm.show({
               title: 'Clear all notifications?',
               message: 'This will permanently remove every notification from your account.',
               action: 'Clear All',
               variant: 'danger'
            }).then(confirmed => {
               if (!confirmed) return;

               requestMobileNotificationJson('/notifications/clear-all', { method: 'DELETE' })
                  .then(refreshMobileNotificationsAfterAction)
                  .catch(error => handleMobileNotificationError(error, 'Notifications could not be cleared.'));
            });
         }

         function markMobileNotificationRead(id, e) {
            if (e) e.stopPropagation();
            if (!id) return;

            requestMobileNotificationJson('/notifications/' + encodeURIComponent(id) + '/read', { method: 'POST' })
               .then(refreshMobileNotificationsAfterAction)
               .catch(error => handleMobileNotificationError(error, 'Notification could not be marked as read.'));
         }

         function deleteMobileNotification(id, e) {
            if (e) e.stopPropagation();
            if (!id) return;

            window.SpeakReadyMobileConfirm.show({
               title: 'Delete notification?',
               message: 'This notification will be permanently removed.',
               action: 'Delete',
               variant: 'danger'
            }).then(confirmed => {
               if (!confirmed) return;

               requestMobileNotificationJson('/notifications/' + encodeURIComponent(id), { method: 'DELETE' })
                  .then(refreshMobileNotificationsAfterAction)
                  .catch(error => handleMobileNotificationError(error, 'Notification could not be deleted.'));
            });
         }

         document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('mobNotifDropdown')?.addEventListener('click', function(event) {
               const clickedElement = event.target instanceof Element ? event.target : event.target?.parentElement;
               const retryButton = clickedElement?.closest('[data-mob-notif-retry]');
               if (retryButton) {
                  event.preventDefault();
                  event.stopPropagation();
                  fetchMobileNotifications(true);
                  return;
               }

               const actionButton = clickedElement?.closest('[data-mob-notif-action]');
               if (!actionButton) return;

               event.preventDefault();
               event.stopPropagation();
               const action = actionButton.dataset.mobNotifAction;
               const notificationId = actionButton.dataset.mobNotifId;

               if (action === 'open') {
                  window.location.href = mobileNotificationsUrl;
               } else if (action === 'mark-read') {
                  markMobileNotificationRead(notificationId, event);
               } else if (action === 'delete') {
                  deleteMobileNotification(notificationId, event);
               }
            });

            document.getElementById('mobNotifDropdown')?.addEventListener('keydown', function(event) {
               const focusedElement = event.target instanceof Element ? event.target : null;
               if ((event.key === 'Enter' || event.key === ' ') && focusedElement?.matches('[role="link"][data-mob-notif-action="open"]')) {
                  event.preventDefault();
                  focusedElement.click();
               }
            });

            fetchMobileNotifications(false, { quiet: true });
            setInterval(() => fetchMobileNotifications(false, { quiet: true }), 60000);
            document.addEventListener('click', function(e) {
               const notifDropdown = document.getElementById('mobNotifDropdown');
               const notifWrap = document.getElementById('mobNotifWrap');
               if (notifDropdown?.classList.contains('open') && !notifDropdown.contains(e.target) && !notifWrap?.contains(e.target)) {
                  closeMobileNotif();
               }

               const profileDropdown = document.getElementById('mobProfileDropdown');
               const profileWrap = document.getElementById('mobProfileWrap');
               if (profileDropdown?.classList.contains('open') && !profileDropdown.contains(e.target) && !profileWrap?.contains(e.target)) {
                  closeMobileProfile();
               }
            });
         });
      </script>

      <style>
         #progressModulesLikeHero.progress-hero,
         #feedbackModulesLikeHero.feedback-hero,
         #sec-interview-setup .setup-hero,
         #interview-modules-page .modules-hero,
         #voice-rehearsal-page .sr-page-hero.vr-hero,
         #mission-mode-page .mission-progress-hero.sr-page-hero,
         #learning-games-page .sr-learning-hero,
         #ai-coach-page .sr-page-hero.coach-progress-hero,
         #portfolioReport .sr-page-hero,
         #personal-mastery-page .mastery-hero-card,
         #notifications-page .notif-hero,
         #account-page .sr-page-hero,
         #skill-trees-page .sr-page-hero.skill-tree-hero,
         .sr-page-hero {
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         :root:not(.lm) #progressModulesLikeHero.progress-hero,
         :root:not(.lm) #feedbackModulesLikeHero.feedback-hero,
         :root:not(.lm) #sec-interview-setup .setup-hero,
         :root:not(.lm) #interview-modules-page .modules-hero,
         :root:not(.lm) #voice-rehearsal-page .sr-page-hero.vr-hero,
         :root:not(.lm) #mission-mode-page .mission-progress-hero.sr-page-hero,
         :root:not(.lm) #learning-games-page .sr-learning-hero,
         :root:not(.lm) #ai-coach-page .sr-page-hero.coach-progress-hero,
         :root:not(.lm) #portfolioReport .sr-page-hero,
         :root:not(.lm) #personal-mastery-page .mastery-hero-card,
         :root:not(.lm) #notifications-page .notif-hero,
         :root:not(.lm) #account-page .sr-page-hero,
         :root:not(.lm) #skill-trees-page .sr-page-hero.skill-tree-hero,
         :root:not(.lm) .sr-page-hero,
         .dm #progressModulesLikeHero.progress-hero,
         .dm #feedbackModulesLikeHero.feedback-hero,
         .dm #sec-interview-setup .setup-hero,
         .dm #interview-modules-page .modules-hero,
         .dm #voice-rehearsal-page .sr-page-hero.vr-hero,
         .dm #mission-mode-page .mission-progress-hero.sr-page-hero,
         .dm #learning-games-page .sr-learning-hero,
         .dm #ai-coach-page .sr-page-hero.coach-progress-hero,
         .dm #portfolioReport .sr-page-hero,
         .dm #personal-mastery-page .mastery-hero-card,
         .dm #notifications-page .notif-hero,
         .dm #account-page .sr-page-hero,
         .dm #skill-trees-page .sr-page-hero.skill-tree-hero,
         .dm .sr-page-hero {
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         #progressModulesLikeHero :is(.progress-hero-title, .progress-hero-subtitle, .progress-hero-icon),
         #feedbackModulesLikeHero :is(.feedback-title, .feedback-subtitle, .feedback-chat-mark),
         #sec-interview-setup .setup-hero :is(.setup-hero-title, .setup-hero-subtitle, .setup-hero-icon),
         #interview-modules-page .modules-hero :is(.modules-hero-title, .modules-hero-subtitle, .modules-hero-icon),
         #voice-rehearsal-page .vr-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .vr-hero-icon),
         #mission-mode-page .mission-progress-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .mission-hero-icon),
         #learning-games-page .sr-learning-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .learning-hero-icon),
         #ai-coach-page .coach-progress-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .coach-hero-icon),
         #portfolioReport .sr-page-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .reports-hero-icon),
         #personal-mastery-page .mastery-hero-card :is(.mastery-title, .mastery-subtitle, .mastery-badge),
         #notifications-page .notif-hero :is(.notif-hero-title, .notif-hero-subtitle, .notif-hero-icon),
         #account-page .sr-page-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .account-hero-icon),
         #skill-trees-page .skill-tree-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .skill-tree-hero-icon),
         .sr-page-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle) {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
         }

         #feedbackModulesLikeHero .feedback-title {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         #sec-interview-setup .setup-hero .setup-hero-title {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            text-transform: uppercase !important;
         }

         #sec-interview-setup .setup-hero .setup-hero-subtitle {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         #progressModulesLikeHero .progress-hero-subtitle,
         #feedbackModulesLikeHero .feedback-subtitle,
         #sec-interview-setup .setup-hero-subtitle,
         #interview-modules-page .modules-hero-subtitle,
         #voice-rehearsal-page .vr-hero .sr-page-hero-subtitle,
         #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle,
         #learning-games-page .sr-learning-hero .sr-page-hero-subtitle,
         #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle,
         #portfolioReport .sr-page-hero-subtitle,
         #personal-mastery-page .mastery-subtitle,
         #notifications-page .notif-hero-subtitle,
         #account-page .sr-page-hero-subtitle,
         #skill-trees-page .skill-tree-hero .sr-page-hero-subtitle,
         .sr-page-hero .sr-page-hero-subtitle {
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
         }

         #progressModulesLikeHero .progress-hero-icon,
         #sec-interview-setup .setup-hero-icon,
         #interview-modules-page .modules-hero-icon,
         #voice-rehearsal-page .vr-hero-icon,
         #mission-mode-page .mission-hero-icon,
         #learning-games-page .learning-hero-icon,
         #ai-coach-page .coach-hero-icon,
         #portfolioReport .reports-hero-icon,
         #personal-mastery-page .mastery-badge,
         #notifications-page .notif-hero-icon,
         #account-page .account-hero-icon,
         #skill-trees-page .skill-hero-icon,
         #skill-trees-page .skill-tree-hero-icon,
         #feedbackModulesLikeHero .feedback-chat-mark {
            background: rgba(15, 23, 42, 0.16) !important;
            border-color: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         #progressModulesLikeHero.progress-hero::before,
         #progressModulesLikeHero.progress-hero::after,
         #interview-modules-page .modules-hero::before,
         #interview-modules-page .modules-hero::after,
         #learning-games-page .sr-learning-hero::before,
         #learning-games-page .sr-learning-hero::after {
            content: none !important;
            display: none !important;
         }

         html body #notifications-page #notificationsPageList.notifications-list-panel {
            width: 100% !important;
            min-width: 100% !important;
            max-width: none !important;
            justify-self: stretch !important;
            align-self: stretch !important;
            margin: 0 !important;
            padding: 0 !important;
            box-sizing: border-box !important;
         }

         html body #notifications-page #notificationsPageList .notifications-empty-state.notifications-empty-state-wide {
            grid-column: 1 / -1 !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            box-sizing: border-box !important;
         }

         html body #progressModulesLikeHero.progress-hero {
            grid-template-columns: 30px minmax(0, 1fr) !important;
            gap: 8px !important;
            min-height: 69px !important;
            padding: 8px 72px 8px 10px !important;
            margin-bottom: 10px !important;
            border-radius: 8px !important;
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         html body #progressModulesLikeHero.progress-hero :is(.progress-hero-title, .progress-hero-subtitle, .progress-hero-icon) {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-subtitle {
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-icon {
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
            font-size: 0.8rem !important;
            background: rgba(15, 23, 42, 0.16) !important;
            border-color: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-title {
            font-size: 0.72rem !important;
            line-height: 1.15 !important;
            margin: 0 0 3px !important;
            white-space: nowrap !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-subtitle {
            font-size: 0.49rem !important;
            line-height: 1.32 !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-art {
            right: -5px !important;
            bottom: -2px !important;
            width: 72px !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero {
            grid-template-columns: 30px minmax(0, 1fr) !important;
            gap: 8px !important;
            min-height: 69px !important;
            padding: 8px 72px 8px 10px !important;
            margin-bottom: 10px !important;
            border-radius: 8px !important;
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero :is(.modules-hero-title, .modules-hero-subtitle, .modules-hero-icon) {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-icon {
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
            font-size: 0.8rem !important;
            background: rgba(15, 23, 42, 0.16) !important;
            border-color: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-icon svg {
            width: 15px !important;
            height: 15px !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-title {
            font-size: 0.72rem !important;
            line-height: 1.15 !important;
            margin: 0 0 3px !important;
            white-space: nowrap !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
            font-size: 0.49rem !important;
            line-height: 1.32 !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-art {
            right: -5px !important;
            bottom: -2px !important;
            width: 72px !important;
         }

         @media (max-width: 390px) {
            html body #interview-modules-page .modules-hero.modules-hero {
               grid-template-columns: 28px minmax(0, 1fr) !important;
               gap: 7px !important;
               padding: 8px 66px 8px 9px !important;
            }

            html body #interview-modules-page .modules-hero.modules-hero .modules-hero-icon {
               width: 27px !important;
               height: 27px !important;
            }

            html body #interview-modules-page .modules-hero.modules-hero .modules-hero-title {
               font-size: 0.68rem !important;
            }

            html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
               font-size: 0.46rem !important;
            }

            html body #interview-modules-page .modules-hero.modules-hero .modules-hero-art {
               width: 66px !important;
            }
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero {
            grid-template-columns: 30px minmax(0, 1fr) !important;
            gap: 8px !important;
            min-height: 69px !important;
            padding: 8px 72px 8px 10px !important;
            margin-bottom: 10px !important;
            border-radius: 8px !important;
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .learning-hero-icon) {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-title {
            font-size: 0.72rem !important;
            line-height: 1.15 !important;
            margin: 0 0 3px !important;
            white-space: nowrap !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-subtitle {
            max-width: 13.5rem !important;
            font-size: 0.49rem !important;
            line-height: 1.32 !important;
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero .learning-hero-icon {
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
            font-size: 0.8rem !important;
            background: rgba(15, 23, 42, 0.16) !important;
            border-color: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-art {
            right: -5px !important;
            bottom: -2px !important;
            width: 72px !important;
         }

         @media (max-width: 390px) {
            html body #learning-games-page .sr-learning-hero.sr-learning-hero {
               grid-template-columns: 28px minmax(0, 1fr) !important;
               gap: 7px !important;
               padding: 8px 66px 8px 9px !important;
            }

            html body #learning-games-page .sr-learning-hero.sr-learning-hero .learning-hero-icon {
               width: 27px !important;
               height: 27px !important;
            }

            html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-title {
               font-size: 0.68rem !important;
            }

            html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-subtitle {
               font-size: 0.46rem !important;
            }

            html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-art {
               width: 66px !important;
            }
         }

         @media (max-width: 991px) {
            #mob-content {
               --sr-unified-hero-bg:
                  radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.26), transparent 24%),
                  radial-gradient(circle at 73% 86%, rgba(125, 211, 252, 0.24), transparent 28%),
                  linear-gradient(112deg, #2563eb 0%, #1d7fe4 50%, #38a9dc 100%);
               --sr-unified-hero-border: rgba(147, 197, 253, 0.5);
               --sr-unified-hero-shadow: 0 10px 26px rgba(37, 99, 235, 0.18);
               --sr-unified-hero-text: #f8fbff;
               --sr-unified-hero-muted: rgba(248, 251, 255, 0.9);
               --sr-unified-hero-icon: rgba(15, 23, 42, 0.16);
            }

            html body #mob-content :is(
               .sr-hero-card,
               .sr-page-hero,
               .progress-hero,
               .feedback-hero,
               .setup-hero,
               .modules-hero,
               .vr-hero,
               .mission-progress-hero,
               .sr-learning-hero,
               .coach-progress-hero,
               .mastery-hero-card,
               .notif-hero,
               .skill-tree-hero,
               .mod-hero
            ) {
               background: var(--sr-unified-hero-bg) !important;
               border-color: var(--sr-unified-hero-border) !important;
               box-shadow: var(--sr-unified-hero-shadow) !important;
            }

            html body #mob-content :is(
               .sr-hero-card,
               .sr-page-hero,
               .progress-hero,
               .feedback-hero,
               .setup-hero,
               .modules-hero,
               .vr-hero,
               .mission-progress-hero,
               .sr-learning-hero,
               .coach-progress-hero,
               .mastery-hero-card,
               .notif-hero,
               .skill-tree-hero,
               .mod-hero
            )::before {
               background-image:
                  linear-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                  linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px) !important;
               background-size: 24px 24px !important;
               opacity: 0.2 !important;
            }

            html body #mob-content :is(
               .sr-hero-card,
               .sr-page-hero,
               .progress-hero,
               .feedback-hero,
               .setup-hero,
               .modules-hero,
               .vr-hero,
               .mission-progress-hero,
               .sr-learning-hero,
               .coach-progress-hero,
               .mastery-hero-card,
               .notif-hero,
               .skill-tree-hero,
               .mod-hero
            ) :is(
               h1,
               h2,
               h3,
               h4,
               .sr-title,
               .sr-title-name,
               .sr-page-hero-title,
               .progress-hero-title,
               .feedback-title,
               .setup-hero-title,
               .modules-hero-title,
               .mastery-title,
               .notif-hero-title
            ) {
               color: var(--sr-unified-hero-text) !important;
               -webkit-text-fill-color: var(--sr-unified-hero-text) !important;
               background: none !important;
            }

            html body #mob-content :is(
               .sr-hero-card,
               .sr-page-hero,
               .progress-hero,
               .feedback-hero,
               .setup-hero,
               .modules-hero,
               .vr-hero,
               .mission-progress-hero,
               .sr-learning-hero,
               .coach-progress-hero,
               .mastery-hero-card,
               .notif-hero,
               .skill-tree-hero,
               .mod-hero
            ) :is(
               p,
               .sr-subtitle,
               .sr-page-hero-subtitle,
               .progress-hero-subtitle,
               .feedback-subtitle,
               .setup-hero-subtitle,
               .modules-hero-subtitle,
               .mastery-subtitle,
               .notif-hero-subtitle
            ) {
               color: var(--sr-unified-hero-muted) !important;
               -webkit-text-fill-color: var(--sr-unified-hero-muted) !important;
            }

            html body #mob-content .sr-hero-card .sr-subtitle .sr-subtitle-accent {
               color: #fde047 !important;
               -webkit-text-fill-color: #fde047 !important;
               text-shadow: 0 2px 10px rgba(15, 23, 42, 0.22) !important;
            }

            html body #mob-content .sr-hero-card .sr-subtitle .sr-subtitle-accent.is-sky {
               color: #7dd3fc !important;
               -webkit-text-fill-color: #7dd3fc !important;
            }

            html body #mob-content .sr-hero-card .sr-subtitle .sr-subtitle-accent.is-mint {
               color: #86efac !important;
               -webkit-text-fill-color: #86efac !important;
            }

            html body #mob-content :is(
               .sr-hero-card,
               .sr-page-hero,
               .progress-hero,
               .feedback-hero,
               .setup-hero,
               .modules-hero,
               .vr-hero,
               .mission-progress-hero,
               .sr-learning-hero,
               .coach-progress-hero,
               .mastery-hero-card,
               .notif-hero,
               .skill-tree-hero,
               .mod-hero
            ) :is(
               h1,
               h2,
               h3,
               h4,
               .sr-title,
               .sr-title-name,
               .sr-page-hero-title,
               .progress-hero-title,
               .feedback-title,
               .setup-hero-title,
               .modules-hero-title,
               .mastery-title,
               .notif-hero-title
            ) {
               color: var(--sr-page-title-accent, #ffffff) !important;
               -webkit-text-fill-color: var(--sr-page-title-accent, #ffffff) !important;
               text-transform: uppercase !important;
               text-shadow: 0 2px 12px rgba(15, 23, 42, 0.18) !important;
            }

            html body #mob-content :is(
               .sr-page-hero,
               .progress-hero,
               .feedback-hero,
               .setup-hero,
               .modules-hero,
               .vr-hero,
               .mission-progress-hero,
               .sr-learning-hero,
               .coach-progress-hero,
               .notif-hero,
               .skill-tree-hero
            ) :is(
               .sr-page-hero-icon,
               .progress-hero-icon,
               .feedback-chat-mark,
               .setup-hero-icon,
               .modules-hero-icon,
               .vr-hero-icon,
               .mission-hero-icon,
               .learning-hero-icon,
               .coach-hero-icon,
               .reports-hero-icon,
               .account-hero-icon,
               .skill-hero-icon,
               .skill-tree-hero-icon,
               .notif-hero-icon,
               .mastery-badge
            ) {
               background: var(--sr-unified-hero-icon) !important;
               border-color: rgba(255, 255, 255, 0.28) !important;
               color: #ffffff !important;
               -webkit-text-fill-color: #ffffff !important;
            }
         }
      </style>

      @include('mobile.partials.user-theme-contrast')
      <!-- USER_PAGE_SCRIPTS_START -->
      @stack('scripts')
      @include('mobile.partials.onboarding-fallback-init')
      <!-- USER_PAGE_SCRIPTS_END -->
      @include('mobile.partials.page-transition')
      @include('mobile.layouts.logout-transition')
   </body>
</html>
