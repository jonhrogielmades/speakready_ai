<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#08080f">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
      <title>@yield('title', 'SpeakReady AI - AI-Based Interview Practice System')</title>
      <script src="{{ asset('js/theme-boot.js?v=1') }}"></script>
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
      <link rel="stylesheet" href="{{ asset('css/style.css?v=11') }}" />
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
      @include('partials.onboarding-styles')
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
            --mob-nav-h: 78px;
            --mob-top-h: 56px;
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
            border-radius: 13px;
            padding: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(96, 165, 250, 0.32);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.46), 0 8px 20px rgba(37, 99, 235, 0.12);
         }
         .lm .mob-logo-ring {
            background: #ffffff;
            border-color: rgba(37, 99, 235, 0.22);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.06), 0 8px 18px rgba(15, 23, 42, 0.08);
         }
         .mob-header-logo img {
            width: 100%;
            height: 100%;
            border-radius: 10px;
            background: #fff;
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

         .mob-header-right { display: flex; align-items: center; gap: 7px; flex: 0 0 auto; }

         .mob-icon-btn {
            width: 34px; height: 34px;
            border-radius: 11px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            background: rgba(255, 255, 255, 0.04); color: var(--tx);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.84rem; cursor: pointer; transition: background-color 0.2s, transform 0.2s, color 0.2s, border-color 0.2s, box-shadow 0.2s;
            -webkit-tap-highlight-color: transparent;
         }
         .mob-icon-btn:active { background: rgba(96,165,250,0.15); transform: scale(0.92); }
         .lm .mob-icon-btn { background: rgba(255, 255, 255, 0.72); border-color: rgba(37, 99, 235, 0.14); }

         .mob-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--grad);
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
            background: var(--grad);
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

         .mob-profile-language {
            border: 1px solid var(--bd);
            border-radius: 13px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.035);
         }

         .mob-profile-language label {
            display: flex;
            align-items: center;
            gap: 9px;
            color: var(--tx);
            font-size: 0.76rem;
            font-weight: 800;
            margin-bottom: 8px;
         }

         .mob-profile-language label i {
            color: #60a5fa;
         }

         .mob-profile-language select {
            background: var(--bg3);
            border-color: var(--bd);
            color: var(--tx);
            border-radius: 11px;
            min-height: 42px;
            font-size: 0.82rem;
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
            min-width: 34px;
            min-height: 32px;
            border: 1px solid var(--bd2);
            border-radius: 10px;
            background: transparent;
            color: var(--tx2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 6px 8px;
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
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 11px;
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
            gap: 12px;
            margin-top: 8px;
         }

         .mob-notif-link-btn {
            border: 0;
            background: transparent;
            padding: 0;
            color: var(--pur);
            font-size: 0.73rem;
            font-weight: 800;
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

            .mob-header-right {
               gap: 7px;
            }

            .mob-icon-btn {
               width: 34px;
               height: 34px;
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
            min-height: 100dvh;
            overflow-x: hidden !important;
            width: 100vw;
            max-width: 100%;
         }
         .db-content { padding: 10px 12px 12px !important; }

         @include('partials.mobile-card-rhythm')

         /* ---- Bottom Navigation Bar ---- */
         #mob-bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 990;
            height: calc(var(--mob-nav-h) + var(--mob-safe-bottom));
            padding-bottom: var(--mob-safe-bottom);
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.88) 0%, rgba(8, 8, 15, 0.98) 100%);
            backdrop-filter: blur(22px) saturate(160%);
            -webkit-backdrop-filter: blur(22px) saturate(160%);
            border-top: 1px solid rgba(148, 163, 184, 0.16);
            display: flex;
            align-items: center;
            overflow: visible;
            box-shadow: 0 -18px 42px rgba(0, 0, 0, 0.3);
            isolation: isolate;
         }
         #mob-bottom-nav::before {
            content: "";
            position: absolute;
            top: 0;
            left: 16px;
            right: 16px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.42), transparent);
            pointer-events: none;
         }
         .lm #mob-bottom-nav {
            background: rgba(255, 255, 255, 0.94);
            border-top-color: rgba(15, 23, 42, 0.08);
            box-shadow: 0 -16px 34px rgba(15, 23, 42, 0.1);
         }

         .mob-nav-items {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 2px;
            padding: 6px 10px 8px;
         }
         .mob-nav-item {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            min-width: 0;
            min-height: 58px;
            flex: 1 1 0;
            padding: 6px 2px 5px;
            border-radius: 16px;
            text-decoration: none;
            color: var(--tx3);
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0;
            line-height: 1.1;
            transition: color 0.18s ease, transform 0.18s ease, background-color 0.18s ease;
            -webkit-tap-highlight-color: transparent;
            border: none;
            background: transparent;
            cursor: pointer;
            font-family: "Poppins", sans-serif;
         }
         .mob-nav-icon {
            width: 34px;
            height: 34px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
         }
         .mob-nav-icon i,
         .mob-nav-primary-icon i {
            font-size: 1.12rem;
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
            color: #60a5fa;
         }
         .mob-nav-item.active .mob-nav-icon {
            background: rgba(96, 165, 250, 0.12);
            box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.18);
         }
         .mob-nav-primary {
            color: #2563eb;
            font-weight: 800;
            align-self: flex-start;
            min-height: 72px;
            padding-top: 0;
            transform: translateY(-15px);
         }
         .mob-nav-primary-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 48%, #0ea5e9 100%);
            border: 4px solid rgba(15, 23, 42, 0.96);
            box-shadow: 0 16px 28px rgba(37, 99, 235, 0.38), 0 0 0 7px rgba(37, 99, 235, 0.12);
            margin-bottom: -3px;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
         }
         .mob-nav-primary .mob-nav-primary-icon i {
            font-size: 1.45rem;
            filter: none;
         }
         .mob-nav-primary.active,
         .mob-nav-primary:active {
            color: #2563eb;
         }
         .mob-nav-primary.active .mob-nav-primary-icon {
            box-shadow: 0 18px 34px rgba(37, 99, 235, 0.48), 0 0 0 8px rgba(37, 99, 235, 0.16);
         }
         .lm .mob-nav-item.active .mob-nav-icon {
            background: rgba(37, 99, 235, 0.1);
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.12);
         }
         .lm .mob-nav-primary {
            color: #2563eb;
         }
         .lm .mob-nav-primary-icon {
            border-color: rgba(255, 255, 255, 0.96);
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
      </style>
   </head>
   <body>

      <!-- ===== MOBILE TOP HEADER ===== -->
      <header id="mob-header">
         <a href="{{ route('dashboard') }}" class="mob-header-logo">
            <span class="mob-logo-ring">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
            </span>
            <span>SpeakReady AI</span>
         </a>
         <div class="mob-header-right">
            <button class="mob-icon-btn" id="mobTutorialBtn" onclick="triggerMobTutorial()" title="Start Tutorial" style="color: #60a5fa; border-color: rgba(96,165,250,0.3);">
               <i class="fa-solid fa-circle-play"></i>
            </button>
            <button class="mob-icon-btn" id="mobThBtn" onclick="toggleTheme()" title="Toggle theme">
               <i class="fa-solid fa-sun" id="mobSunI" style="display:none"></i>
               <i class="fa-solid fa-moon" id="mobMoonI"></i>
            </button>
            <div class="mob-notif-wrap" id="mobNotifWrap">
               <button class="mob-icon-btn" id="mobBellBtn" type="button" aria-label="Open notifications" aria-controls="mobNotifDropdown" aria-expanded="false" onclick="toggleMobileNotif(event)" style="position:relative;">
                  <i class="fa-regular fa-bell"></i>
                  <span id="mobNotifBadge" style="position:absolute;top:5px;right:5px;width:9px;height:9px;border-radius:50%;background:#f87171;border:2px solid var(--bg);display:none;"></span>
               </button>
            </div>
            <div class="mob-profile-wrap" id="mobProfileWrap">
               <button class="mob-avatar" id="mobProfileBtn" type="button" aria-label="Open account menu" aria-controls="mobProfileDropdown" aria-expanded="false" onclick="toggleMobileProfile(event, 'account')" title="Profile" style="padding:0;overflow:hidden;border:1px solid var(--bd);">
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

      <div class="mob-profile-dropdown" id="mobProfileDropdown" aria-hidden="true" data-mode="pages" data-origin="top">
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
               <a href="{{ route('user.applications.index') }}" class="mob-profile-link profile-nav-cyan {{ request()->routeIs('user.applications.*') ? 'active' : '' }}"><i class="fa-solid fa-briefcase"></i><span>Job Tracker</span></a>
               <a href="{{ route('user.packs.index') }}" class="mob-profile-link profile-nav-indigo {{ request()->routeIs('user.packs.*') ? 'active' : '' }}"><i class="fa-solid fa-layer-group"></i><span>Packs</span></a>
               <a href="{{ route('user.drills.voice') }}" class="mob-profile-link profile-nav-rose {{ request()->routeIs('user.drills.voice') ? 'active' : '' }}"><i class="fa-solid fa-ear-listen"></i><span>Voice Drill</span></a>
               <a href="{{ route('user.learning') }}" class="mob-profile-link profile-nav-amber {{ request()->routeIs('user.learning*') ? 'active' : '' }}"><i class="fa-solid fa-gamepad"></i><span>Games</span></a>
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
               <form action="{{ route('user.language.update') }}" method="POST" class="mob-profile-language mb-2">
                  @csrf
                  <label for="mobileProfileLanguageSelect"><i class="fa-solid fa-language"></i>Language</label>
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

      <div class="mob-notif-dropdown" id="mobNotifDropdown" aria-hidden="true">
         <div class="mob-notif-header">
            <div class="mob-notif-title">
               <i class="fa-regular fa-bell" style="color:var(--pur)"></i>
               <span>Notifications</span>
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
      <div id="mob-content">
         <div class="db-content">
            @yield('content')
         </div>
      </div>

      <div id="mobMoreBackdrop" class="mob-more-backdrop" aria-hidden="true" onclick="closeMobileProfile()"></div>

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
               <span class="mob-nav-primary-icon"><i class="fa-solid fa-microphone-lines"></i></span>
               <span>Interview</span>
            </a>
            <a href="{{ route('user.feedback') }}"
               class="mob-nav-item {{ request()->routeIs('user.feedback', 'user.review') ? 'active' : '' }}"
               id="mobnav-feedback">
               <span class="mob-nav-icon"><i class="fa-solid fa-clipboard-check"></i></span>
               <span>Feedback</span>
            </a>
            <button class="mob-nav-item {{ request()->routeIs('user.account', 'user.notifications', 'user.modules.*', 'user.applications.*', 'user.packs.*', 'user.drills.voice', 'user.learning*', 'user.coach*', 'user.reports', 'user.leaderboard') ? 'active' : '' }}"
                    id="mobnav-more"
                    type="button"
                    aria-controls="mobProfileDropdown"
                    aria-expanded="false"
                    aria-label="Open profile menu"
                    onclick="toggleMobileProfile(event, 'pages')">
               <span class="mob-nav-icon"><i class="fa-solid fa-ellipsis"></i></span>
               <span>Profile</span>
            </button>
         </div>
      </nav>

      @include('partials.viewport-mobile-cookie')


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
      <script src="{{ asset('js/aos.js') }}"></script>
      <script src="{{ asset('js/chart.umd.min.js') }}"></script>
      <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
      <script src="{{ asset('js/main.js?v=6') }}"></script>
      <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
      @include('partials.onboarding-script')
      @include('partials.language-translation')

      <script>
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
               navigator.serviceWorker.register('/sw.js').then(function(registration) {
                  console.log('ServiceWorker registration successful');
               }, function(err) {
                  console.log('ServiceWorker registration failed: ', err);
               });
            });
         }
         
         function triggerMobTutorial() {
             if (typeof window.startOnboardingTour === 'function') {
                 window.startOnboardingTour();
             } else {
                 alert('A tutorial is not available for this specific page.');
             }
         }

         // PWA Install Prompt Logic
         let deferredPrompt;
         const suppressPwaInstallPrompt = @json(request()->routeIs('interview.session'));
         window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (suppressPwaInstallPrompt) return;
            if (!localStorage.getItem('pwa_prompt_dismissed')) {
               document.getElementById('pwa-install-prompt').style.display = 'block';
            }
         });

         document.getElementById('pwa-btn-yes')?.addEventListener('click', async () => {
            document.getElementById('pwa-install-prompt').style.display = 'none';
            if (deferredPrompt) {
               deferredPrompt.prompt();
               const { outcome } = await deferredPrompt.userChoice;
               console.log(`User response to the install prompt: ${outcome}`);
               deferredPrompt = null;
            }
         });

         document.getElementById('pwa-btn-no')?.addEventListener('click', () => {
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
               item.addEventListener('pointerdown', () => playMobileNavIconMotion(item), { passive: true });
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

         function fetchMobileNotifications(forceRender = false) {
            fetch('/notifications/fetch')
               .then(res => res.json())
               .then(data => {
                  updateMobileNotifUI(data, forceRender);
               })
               .catch(err => console.error('Error fetching notifications:', err));
         }

         function updateMobileNotifUI(data, forceRender = false) {
            const badge = document.getElementById('mobNotifBadge');
            const unreadBadge = document.getElementById('mobUnreadCountBadge');
            const listContainer = document.getElementById('mobNotifListContainer');

            if (badge) {
               if (data.unreadCount > 0) {
                  badge.style.display = 'block';
               } else {
                  badge.style.display = 'none';
               }
            }

            if (unreadBadge) {
               if (data.unreadCount > 0) {
                  unreadBadge.style.display = 'inline-block';
                  unreadBadge.textContent = data.unreadCount + ' new';
               } else {
                  unreadBadge.style.display = 'none';
               }
            }

            if (!listContainer || (!forceRender && !document.getElementById('mobNotifDropdown')?.classList.contains('open'))) return;

            if (!data.notifications || data.notifications.length === 0) {
               listContainer.innerHTML = '<div class="text-center py-4" style="color:var(--tx3);font-size:0.85rem;">No notifications to show.</div>';
               return;
            }

            listContainer.innerHTML = data.notifications.map(n => {
               const title = escapeMobileNotifHtml(n.data?.title || 'Notification');
               const message = escapeMobileNotifHtml(n.data?.message || '');
               const icon = escapeMobileNotifHtml(n.data?.icon || 'fa-bell');
               const date = escapeMobileNotifHtml(new Date(n.created_at).toLocaleString());
               const unreadClass = n.read_at ? '' : 'unread';
               const markRead = n.read_at ? '' : `<button class="mob-notif-link-btn" type="button" onclick="markMobileNotificationRead('${n.id}', event)">Mark as read</button>`;

               return `
                  <div class="mob-notif-item ${unreadClass}">
                     <button class="mob-notif-ico" type="button" onclick="window.location.href='/notifications'" aria-label="Open notifications page">
                        <i class="fa-solid ${icon}"></i>
                     </button>
                     <div class="mob-notif-copy">
                        <div onclick="window.location.href='/notifications'" style="cursor:pointer;">
                           <strong>${title}</strong>
                           <span>${message}</span>
                           <small><i class="fa-regular fa-clock me-1"></i>${date}</small>
                        </div>
                        <div class="mob-notif-row-actions">
                           ${markRead}
                           <button class="mob-notif-link-btn danger" type="button" onclick="deleteMobileNotification('${n.id}', event)">Delete</button>
                        </div>
                     </div>
                  </div>
               `;
            }).join('');
         }

         function markAllMobileNotificationsRead(e) {
            if (e) e.stopPropagation();
            fetch('/notifications/read-all', {
               method: 'POST',
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Content-Type': 'application/json'
               }
            })
            .then(res => res.json())
            .then(data => {
               if (data.success) {
                  fetchMobileNotifications(true);
                  if (typeof reloadNotificationsPage === 'function') reloadNotificationsPage();
               }
            });
         }

         function clearAllMobileNotifications(e) {
            if (e) e.stopPropagation();
            if (confirm('Are you sure you want to clear all notifications?')) {
               fetch('/notifications/clear-all', {
                  method: 'DELETE',
                  headers: {
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Content-Type': 'application/json'
                  }
               })
               .then(res => res.json())
               .then(data => {
                  if (data.success) {
                     fetchMobileNotifications(true);
                     if (typeof reloadNotificationsPage === 'function') reloadNotificationsPage();
                  }
               });
            }
         }

         function markMobileNotificationRead(id, e) {
            if (e) e.stopPropagation();
            fetch('/notifications/' + id + '/read', {
               method: 'POST',
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Content-Type': 'application/json'
               }
            })
            .then(res => res.json())
            .then(data => {
               if (data.success) fetchMobileNotifications(true);
            });
         }

         function deleteMobileNotification(id, e) {
            if (e) e.stopPropagation();
            fetch('/notifications/' + id, {
               method: 'DELETE',
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Content-Type': 'application/json'
               }
            })
            .then(res => res.json())
            .then(data => {
               if (data.success) fetchMobileNotifications(true);
            });
         }

         document.addEventListener('DOMContentLoaded', function() {
            fetchMobileNotifications();
            setInterval(fetchMobileNotifications, 60000);
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

      @stack('scripts')
      @include('layouts.logout-transition')
   </body>
</html>
