<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#1a0a0a">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
      <title>{{ $systemName ?? 'SpeakReady AI' }} Interview Admin Portal</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset($systemFavicon ?? 'favicon.ico') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"/>
      <link href="{{ asset('css/aos.css') }}" rel="stylesheet"/>
      <link href="{{ asset('css/swiper-bundle.min.css') }}" rel="stylesheet"/>
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}"/>
      <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}"/>
      <link rel="stylesheet" href="{{ asset('css/mobile/style.css?v=30') }}" />
      <style>
         :root,
         .lm {
            --pur: {{ $systemPrimaryColor ?? '#3b82f6' }};
            --blu: {{ $systemPrimaryColor ?? '#3b82f6' }};
            --grad: linear-gradient(135deg, {{ $systemPrimaryColor ?? '#3b82f6' }}, {{ $systemSecondaryColor ?? '#34d399' }});
         }
         /* ===== ADMIN MOBILE LAYOUT SHELL ===== */
         html, body {
            overflow-x: hidden;
            width: 100%;
            position: relative;
         }
         :root {
            --mob-nav-h: 64px;
            --mob-top-h: 56px;
            --mob-safe-top: env(safe-area-inset-top, 0px);
            --mob-safe-bottom: env(safe-area-inset-bottom, 0px);
            --adm: #f87171;
            --adm-bg: rgba(248,113,113,0.1);
            --adm-bd: rgba(248,113,113,0.22);
         }

         html, body { margin: 0; padding: 0; overflow-x: hidden !important; }

         /* ---- Reset desktop layout ---- */
         #dashboard   { display: block !important; }
         .db-sidebar  { display: none !important; }
         .db-main     { margin-left: 0 !important; padding-top: 0 !important; min-height: unset !important; }
         .db-top      { display: none !important; }
         .db-section  { height: auto !important; overflow: visible !important; }

         /* ---- Admin Mobile Top Header ---- */
         #mob-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            height: calc(var(--mob-top-h) + var(--mob-safe-top));
            padding-top: var(--mob-safe-top);
            padding-left: 16px; padding-right: 16px;
            background: rgba(10, 4, 4, 0.92);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--adm-bd);
            display: flex; align-items: center; justify-content: space-between;
         }
         .lm #mob-header { background: rgba(255, 248, 248, 0.95); }

         body.admin-mobile-shell .modal {
            --bs-modal-bg: var(--sf);
            --bs-modal-color: var(--tx);
         }
         body.admin-mobile-shell .modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
            padding: calc(var(--mob-safe-top) + 12px) 12px calc(var(--mob-safe-bottom) + 84px);
         }
         body.admin-mobile-shell .modal-dialog {
            width: min(100%, 440px);
            max-width: 440px;
            margin: 0 auto !important;
         }
         body.admin-mobile-shell .modal-dialog.modal-lg,
         body.admin-mobile-shell .modal-dialog.modal-xl {
            width: min(100%, 520px);
            max-width: 520px;
         }
         body.admin-mobile-shell .modal-content {
            width: 100%;
            max-height: min(82vh, 720px);
            overflow: hidden;
            background: var(--sf) !important;
            color: var(--tx) !important;
            border: 1px solid var(--bd) !important;
            border-radius: 16px !important;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .28);
         }
         body.admin-mobile-shell .modal-header,
         body.admin-mobile-shell .modal-footer {
            flex-shrink: 0;
            background: var(--sf) !important;
            color: var(--tx) !important;
            border-color: var(--bd) !important;
            padding-inline: 16px;
         }
         body.admin-mobile-shell .modal-title {
            color: var(--tx) !important;
            font-size: 1rem;
            line-height: 1.25;
         }
         body.admin-mobile-shell .modal-body {
            max-height: calc(min(82vh, 720px) - 124px);
            overflow-y: auto !important;
            background: var(--sf) !important;
            color: var(--tx) !important;
            padding: 16px !important;
         }
         body.admin-mobile-shell .modal label,
         body.admin-mobile-shell .modal .form-label,
         body.admin-mobile-shell .modal p,
         body.admin-mobile-shell .modal small,
         body.admin-mobile-shell .modal h1,
         body.admin-mobile-shell .modal h2,
         body.admin-mobile-shell .modal h3,
         body.admin-mobile-shell .modal h4,
         body.admin-mobile-shell .modal h5,
         body.admin-mobile-shell .modal h6,
         body.admin-mobile-shell .modal span:not(.badge):not([class*="text-"]) {
            color: var(--tx) !important;
         }
         body.admin-mobile-shell .modal .text-muted {
            color: var(--tx2) !important;
         }
         body.admin-mobile-shell .modal .form-control,
         body.admin-mobile-shell .modal .form-select,
         body.admin-mobile-shell .modal textarea,
         body.admin-mobile-shell .modal input {
            background: var(--bg3) !important;
            color: var(--tx) !important;
            border: 1px solid var(--bd) !important;
            border-radius: 10px !important;
            min-height: 42px;
         }
         body.admin-mobile-shell .modal .form-control::placeholder,
         body.admin-mobile-shell .modal textarea::placeholder,
         body.admin-mobile-shell .modal input::placeholder {
            color: var(--tx3) !important;
            font-weight: 400 !important;
         }
         body.admin-mobile-shell .modal .btn-close {
            opacity: .85;
            filter: var(--admin-close-filter, invert(1));
         }
         .lm body.admin-mobile-shell .modal .btn-close,
         html.lm body.admin-mobile-shell .modal .btn-close {
            --admin-close-filter: none;
         }
         body.admin-mobile-shell .modal-footer {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
            padding: 14px 16px 16px !important;
         }
         body.admin-mobile-shell .modal-footer .btn,
         body.admin-mobile-shell .modal-footer button {
            width: 100%;
            min-width: 0;
            min-height: 42px;
            border-radius: 10px !important;
            font-size: .82rem;
            font-weight: 700;
            white-space: nowrap;
         }
         body.admin-mobile-shell .modal-footer .btn-outline-secondary,
         body.admin-mobile-shell .modal-footer [data-bs-dismiss="modal"] {
            border: 1px solid var(--bd) !important;
            color: var(--tx) !important;
            background: var(--bg3) !important;
         }
         body.admin-mobile-shell .modal-footer .btn:only-child {
            grid-column: 1 / -1;
         }

         .mob-header-brand {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.88rem; font-weight: 700;
            color: var(--tx); text-decoration: none;
            min-width: 0;
            flex: 1 1 auto;
         }
         .mob-header-brand > span:not(.mob-admin-logo-ring):not(.adm-badge) {
            min-width: 0;
            max-width: min(42vw, 12rem);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
         }
         .mob-admin-logo-ring {
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
         .lm .mob-admin-logo-ring {
            background: #ffffff;
            border-color: #ffffff;
            box-shadow: none;
         }
         .mob-header-brand img { width: 100%; height: 100%; border-radius: 16%; background:transparent; object-fit:contain; }
         .mob-header-brand .adm-badge {
            font-size: 0.58rem; font-weight: 700;
            background: var(--adm-bg); color: var(--adm);
            border: 1px solid var(--adm-bd);
            padding: 2px 7px; border-radius: 6px;
            text-transform: uppercase; letter-spacing: 0.05em;
            flex-shrink: 0;
         }
         .mob-header-right { display: flex; align-items: center; gap: 8px; min-width: 0; flex: 0 0 auto; }
         .mob-icon-btn {
            width: 36px; height: 36px; border-radius: 10px;
            border: 1px solid var(--adm-bd);
            background: transparent; color: var(--tx);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; cursor: pointer; transition: 0.2s;
            -webkit-tap-highlight-color: transparent;
            flex: 0 0 36px;
         }
         .mob-icon-btn:active { background: var(--adm-bg); transform: scale(0.92); }
         .mob-avatar-adm {
            width: 32px; height: 32px; border-radius: 50%;
            background: #f87171; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700; flex-shrink: 0;
            cursor: pointer; border: 2px solid var(--adm-bd);
            -webkit-tap-highlight-color: transparent;
         }

         body.admin-mobile-shell.user-app-fullscreen {
            height: var(--sr-visual-vh);
            min-height: var(--sr-visual-vh);
            overflow: hidden !important;
            overscroll-behavior: none;
         }

         body.admin-mobile-shell.user-app-fullscreen #dashboard,
         body.admin-mobile-shell.user-app-fullscreen .db-main,
         body.admin-mobile-shell.user-app-fullscreen #mob-content {
            height: var(--sr-visual-vh) !important;
            min-height: var(--sr-visual-vh) !important;
         }

         body.admin-mobile-shell.user-app-fullscreen #mob-content {
            overflow-y: auto !important;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
         }

         /* ---- Page Content ---- */
         #mob-content {
            padding-top: calc(var(--mob-top-h) + var(--mob-safe-top));
            padding-bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 16px);
            min-height: 100vh;
            min-height: var(--sr-visual-vh, 100dvh);
            overflow-x: hidden !important;
            width: 100%;
            max-width: 100%;
         }
         .db-content { padding: 14px !important; }

         @include('mobile.partials.mobile-card-rhythm')
         @include('mobile.partials.mobile-shell-viewport')

         /* ---- Admin Bottom Navigation Bar ---- */
         #mob-bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 990;
            height: calc(var(--mob-nav-h) + var(--mob-safe-bottom));
            padding-bottom: var(--mob-safe-bottom);
            background: rgba(10, 4, 4, 0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--adm-bd);
            display: flex; align-items: center;
         }
         .lm #mob-bottom-nav { background: rgba(255, 248, 248, 0.97); }
         .mob-nav-items {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            width: 100%;
            align-items: center;
            gap: 2px;
            padding: 0 6px;
         }
         .mob-nav-item {
            display: flex; flex-direction: column; align-items: center; gap: 3px;
            padding: 7px 2px; border-radius: 12px;
            text-decoration: none; color: var(--tx3);
            font-size: 0.6rem; font-weight: 600; letter-spacing: 0.02em;
            min-width: 0;
            width: 100%;
            transition: color 0.2s;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            border: none; background: transparent; cursor: pointer;
            font-family: "Poppins", sans-serif;
         }
         .mob-nav-item span {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
         }
         .mob-nav-item i { font-size: 1.2rem; transition: transform 0.18s; }
         .mob-nav-item:active i { transform: scale(0.82); }
         .mob-nav-item.active { color: var(--adm); }
         .mob-nav-item.active i { filter: drop-shadow(0 0 5px rgba(248,113,113,0.55)); }

         .mob-more-backdrop {
            position: fixed;
            inset: 0;
            z-index: 980;
            display: none;
            background: rgba(20, 4, 4, 0.28);
            backdrop-filter: blur(7px);
            -webkit-backdrop-filter: blur(7px);
         }
         .mob-more-backdrop.open { display: block; animation: mobFadeIn 0.18s ease; }
         .lm .mob-more-backdrop { background: rgba(255, 248, 248, 0.38); }

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
            background: rgba(18, 8, 8, 0.98);
            border: 1px solid var(--adm-bd);
            border-radius: 16px;
            box-shadow: 0 22px 56px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            overflow: hidden;
         }
         .mob-profile-dropdown.open { display: block; animation: mobFadeIn 0.18s ease; }
         .mob-profile-dropdown[data-origin="bottom"] {
            top: auto;
            left: 50%;
            right: auto;
            bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 12px);
            width: min(92vw, 420px);
            max-height: calc(var(--sr-visual-vh, 100dvh) - var(--mob-nav-h) - var(--mob-safe-bottom) - var(--mob-safe-top) - 24px);
            transform: translateX(-50%);
         }
         .lm .mob-profile-dropdown {
            background: rgba(255, 255, 255, 0.98);
            border-color: var(--adm-bd);
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
            background: linear-gradient(135deg, #dc2626, #fb7185);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            overflow: hidden;
            font-weight: 800;
         }
         .mob-profile-head-avatar img { width: 100%; height: 100%; object-fit: cover; }
         .mob-profile-head-meta { min-width: 0; flex: 1 1 auto; }
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
            color: var(--adm);
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
         .mob-profile-dropdown[data-origin="bottom"] .mob-profile-menu {
            max-height: calc(var(--sr-visual-vh, 100dvh) - var(--mob-nav-h) - var(--mob-safe-bottom) - var(--mob-safe-top) - 48px);
         }
         .mob-profile-dropdown[data-mode="account"] .mob-profile-pages,
         .mob-profile-dropdown[data-mode="pages"] .mob-profile-account {
            display: none;
         }
         .mob-profile-dropdown[data-mode="pages"] .mob-profile-head {
            display: none;
         }
         .mob-profile-pages-close {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            border-bottom: 1px solid var(--bd);
            color: var(--tx);
            font-size: 0.88rem;
            font-weight: 800;
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
         .mob-profile-dropdown[data-mode="pages"] .mob-profile-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
         }
         .mob-profile-dropdown[data-mode="pages"] .mob-profile-link {
            min-height: 42px;
            padding: 8px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
         }
         .mob-profile-dropdown[data-mode="pages"] .mob-profile-link i {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            font-size: 0.78rem;
         }
         .mob-profile-dropdown[data-mode="pages"] .mob-profile-section-title {
            padding-top: 8px;
            padding-bottom: 6px;
         }
         @media (max-width: 340px) {
            .mob-profile-dropdown[data-mode="pages"] .mob-profile-grid {
               grid-template-columns: 1fr;
            }
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
         .lm .mob-profile-action { background: rgba(248, 250, 252, 0.86); }
         .mob-profile-link.active {
            border-color: var(--adm-bd);
            background: var(--adm-bg);
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
            background: linear-gradient(135deg, #dc2626, #fb7185);
         }
         .mob-profile-link.profile-nav-blue i { background: linear-gradient(135deg, #2563eb, #60a5fa); }
         .mob-profile-link.profile-nav-purple i { background: linear-gradient(135deg, #7c3aed, #c084fc); }
         .mob-profile-link.profile-nav-cyan i { background: linear-gradient(135deg, #0891b2, #22d3ee); }
         .mob-profile-link.profile-nav-amber i { background: linear-gradient(135deg, #d97706, #fbbf24); }
         .mob-profile-link.profile-nav-emerald i { background: linear-gradient(135deg, #059669, #10b981); }
         .mob-profile-link.profile-nav-rose i { background: linear-gradient(135deg, #e11d48, #fb7185); }
         .mob-profile-link.profile-nav-indigo i { background: linear-gradient(135deg, #4f46e5, #818cf8); }
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
         .mob-profile-action.danger i { background: linear-gradient(135deg, #ef4444, #f97316); }
         @media (max-width: 360px) {
            .mob-profile-grid { grid-template-columns: 1fr; }
         }

         @keyframes mobFadeIn { from { opacity: 0; } to { opacity: 1; } }

         /* =================================================================
            GLOBAL ADMIN MOBILE BUG FIXES
         ================================================================= */

         /* --- 1. Overflow prevention --- */
         html, body { overflow-x: hidden !important; }

         /* --- 2. All tables: horizontal scroll, compressed text --- */
         .table-responsive { -webkit-overflow-scrolling: touch; }
         @media (max-width: 575px) {

            /* Hide less important columns on very small screens */

         }

         /* --- 3. Action buttons in tables: wrap tightly --- */
         @media (max-width: 575px) {
            .action-btn { width: 28px !important; height: 28px !important; font-size: 0.7rem !important; }
            td .d-flex.gap-1 { gap: 4px !important; flex-wrap: wrap; }
            td .btn-sm { font-size: 0.65rem !important; padding: 3px 6px !important; }
         }

         /* --- 4. Page title + action buttons row: stack vertically --- */
         @media (max-width: 767px) {
            .d-flex.flex-column.flex-md-row { flex-direction: column !important; }
            .d-flex.flex-column.flex-md-row .d-flex.flex-wrap.gap-2 {
               flex-wrap: wrap !important;
               width: 100%;
            }
            .d-flex.flex-column.flex-md-row .d-flex.flex-wrap.gap-2 > * {
               flex: 1 1 calc(50% - 4px);
               font-size: 0.8rem !important;
               text-align: center;
               justify-content: center;
            }
         }

         /* --- 5. Question bank header action buttons: stack 2x2 --- */
         @media (max-width: 575px) {
            .mb-4.d-flex.justify-content-between.align-items-center {
               flex-direction: column !important;
               align-items: flex-start !important;
               gap: 12px;
            }
            .mb-4.d-flex.justify-content-between.align-items-center .d-flex.gap-2 {
               flex-wrap: wrap !important; width: 100%;
            }
            .mb-4.d-flex.justify-content-between.align-items-center .d-flex.gap-2 > * {
               flex: 1 1 calc(50% - 4px);
               font-size: 0.75rem !important;
               text-align: center; justify-content: center;
            }
         }

         /* --- 6. Dashboard stat cards: 2-per-row minimum --- */
         @media (max-width: 575px) {
            .col-6.col-md-4.col-xl-2 { width: 50% !important; }
            .col-md-3 { width: 50% !important; }
         }
         @media (max-width: 360px) {
            .col-6.col-md-4.col-xl-2 { width: 100% !important; }
         }

         /* --- 7. Chart containers: reduce height on mobile --- */
         @media (max-width: 575px) {
            .chart-container { height: 180px !important; }
            .chart-container[style*="height: 200px"] { height: 160px !important; }
            .chart-container[style*="height: 250px"] { height: 180px !important; }
         }

         /* --- 9. User filter row (search + selects): full width each --- */
         @media (max-width: 575px) {
            .row.g-3 .col-md-4,
            .row.g-3 .col-md-3 { width: 100% !important; }
         }

         /* --- 10. Pagination: wrap & shrink on mobile --- */
         @media (max-width: 575px) {
            .d-flex.justify-content-between.align-items-center.mt-3 {
               flex-direction: column !important; align-items: flex-start !important; gap: 10px;
            }
            .pagination { flex-wrap: wrap; }
            .page-link { font-size: 0.78rem !important; padding: 5px 9px !important; }
         }

         /* --- 11. Modal: fullscreen on mobile for large modals --- */
         @media (max-width: 575px) {
            body:not(.admin-mobile-shell) .modal-dialog:not(.modal-sm) {
               margin: 0 !important;
               max-width: 100% !important;
               height: var(--sr-visual-vh, 100dvh);
            }
            body:not(.admin-mobile-shell) .modal-dialog:not(.modal-sm) .modal-content {
               border-radius: 0 !important;
               height: var(--sr-visual-vh, 100dvh);
               border: none !important;
            }
            body:not(.admin-mobile-shell) .modal-xl .modal-dialog { margin: 0 !important; }
            body:not(.admin-mobile-shell) .modal-body { overflow-y: auto; }
            /* Nav tabs in modal: scrollable */
            .modal .nav-tabs { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .modal .nav-tabs .nav-link { white-space: nowrap; font-size: 0.8rem; padding: 8px 12px; }
            /* Modal footer: stack buttons */
            body:not(.admin-mobile-shell) .modal-footer { flex-wrap: wrap; gap: 8px; }
            body:not(.admin-mobile-shell) .modal-footer .btn { flex: 1 1 auto; font-size: 0.82rem; }
         }

         /* --- 12. Quick action buttons on dashboard (wrap nicely) --- */
         @media (max-width: 575px) {
            .quick-action-btn {
               font-size: 0.78rem !important;
               padding: 8px 12px !important;
               flex: 1 1 calc(50% - 4px);
               justify-content: center;
            }
         }

         /* --- 13. Nav pills / tabs in content: scrollable --- */
         @media (max-width: 575px) {
            .nav-pills, .nav-tabs {
               flex-wrap: nowrap !important;
               overflow-x: auto !important;
               -webkit-overflow-scrolling: touch;
               padding-bottom: 2px;
            }

         }

         /* --- 14. row col-lg-8 / col-lg-4 order on mobile --- */
         @media (max-width: 991px) {
            .row > .col-lg-8 { order: 1; }
            .row > .col-lg-4 { order: 2; }
         }

         /* --- 15. Dropdown menu: don't clip off screen edge --- */
         @media (max-width: 575px) {
            .dropdown-menu { font-size: 0.85rem; min-width: 140px; }
            .dropdown-menu-end { right: 0 !important; left: auto !important; }
         }

         .mob-notification-dropdown {
            width: min(340px, calc(100vw - 24px));
            max-width: calc(100vw - 24px);
            border-radius: 18px;
            border: 1px solid rgba(96, 165, 250, 0.18);
            background: rgba(12, 16, 28, 0.98);
            padding: 0;
            overflow: hidden;
            margin-top: 10px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.42);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
         }
         .lm .mob-notification-dropdown {
            background: rgba(255, 255, 255, 0.98);
            border-color: rgba(37, 99, 235, 0.14);
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.16);
         }
         .admin-mob-notif-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px;
            border-bottom: 1px solid var(--bd);
            background: var(--sf);
         }
         .admin-mob-notif-title {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            color: var(--tx);
            font-weight: 800;
            font-size: 0.9rem;
            line-height: 1.2;
         }
         .admin-mob-notif-count {
            display: none;
            flex: 0 0 auto;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(248, 113, 113, 0.14);
            color: #f87171;
            font-size: 0.68rem;
            font-weight: 800;
         }
         .admin-mob-notif-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
         }
         .admin-mob-notif-action {
            min-width: 34px;
            min-height: 34px;
            border: 1px solid var(--bd);
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            color: var(--tx2);
            background: var(--bg3);
            font-size: 0.72rem;
            font-weight: 800;
            padding: 0 9px;
         }
         .admin-mob-notif-action.danger {
            color: #f87171;
            border-color: rgba(248, 113, 113, 0.24);
         }
         .mob-notification-list {
            max-height: min(360px, calc(100dvh - var(--mob-top-h) - var(--mob-safe-top) - var(--mob-nav-h) - var(--mob-safe-bottom) - 132px));
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            background: var(--bg);
            padding: 8px;
         }
         .mob-notification-list .admin-activity-item {
            display: flex !important;
            gap: 8px !important;
            margin: 0 0 8px !important;
            padding: 11px !important;
            border: 1px solid var(--bd) !important;
            border-radius: 14px !important;
            background: var(--sf) !important;
            color: var(--tx) !important;
            white-space: normal !important;
         }
         .mob-notification-list .admin-activity-item:hover {
            background: var(--sf2, var(--sf)) !important;
         }
         .mob-notification-list .admin-activity-item > div:first-child {
            align-items: flex-start !important;
            gap: 8px !important;
         }
         .mob-notification-list .admin-activity-item .fw-bold {
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
            font-size: 0.82rem !important;
            line-height: 1.25 !important;
         }
         .mob-notification-list .admin-activity-item > div:last-child {
            font-size: 0.76rem !important;
            line-height: 1.45 !important;
            color: var(--tx2) !important;
         }
         .mob-notification-list .admin-activity-item .act-delete,
         .mob-notification-list .admin-activity-item .act-mark-read {
            width: 28px;
            height: 28px;
            min-width: 28px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
         }
         .admin-mob-notif-footer {
            padding: 10px;
            border-top: 1px solid var(--bd);
            background: var(--sf);
         }
         .admin-mob-notif-view-all {
            width: 100%;
            min-height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.14);
            font-size: 0.78rem;
            font-weight: 800;
         }

         @media (max-width: 575px) {
            .mob-notification-wrap {
               position: static;
            }
            #mob-header .mob-notification-dropdown {
               position: fixed !important;
               top: calc(var(--mob-top-h) + var(--mob-safe-top) + 8px) !important;
               right: max(10px, env(safe-area-inset-right, 0px)) !important;
               left: max(10px, env(safe-area-inset-left, 0px)) !important;
               width: auto !important;
               max-width: none !important;
               transform: none !important;
               margin-top: 0;
            }
            .mob-notification-list {
               max-height: min(55dvh, 360px);
            }
            .admin-mob-notif-header {
               align-items: flex-start;
               flex-direction: column;
            }
            .admin-mob-notif-actions {
               width: 100%;
            }
            .admin-mob-notif-action {
               flex: 1 1 0;
            }
         }

         /* --- 16. Question bank table inside card: ensure overflow scrolls --- */
         @media (max-width: 767px) {
            [style*="overflow-x:auto"], [style*="overflow-x: auto"] {
               overflow-x: auto !important;
               -webkit-overflow-scrolling: touch;
            }
            /* Min column widths to prevent squish */
            .table td:first-child, .table th:first-child { min-width: 40px; }
            .table td:nth-child(2), .table th:nth-child(2) { min-width: 140px; max-width: 160px; }
         }

         /* --- 17. Communications / Announcements tab items wrap --- */
         @media (max-width: 575px) {
            .d-flex.justify-content-between.align-items-center.p-2 {
               flex-direction: column !important;
               align-items: flex-start !important;
               gap: 8px;
            }
         }

         /* --- 18. Stat badges: don't overflow --- */
         @media (max-width: 575px) {
            .stat-badge { font-size: 0.68rem !important; padding: 4px 8px !important; }
         }

         /* --- 20. Settings page: form labels + inputs stack --- */
         @media (max-width: 575px) {
            .col-lg-3, .col-lg-4 { width: 100% !important; }
         }

         /* --- 21. Admin mobile: remove internal horizontal table/card scroll --- */
         @media (max-width: 767px) {
            #mob-content,
            #mob-content .db-content,
            #mob-content .db-section,
            #mob-content .container,
            #mob-content .container-fluid,
            #mob-content .row,
            #mob-content [class*="col-"],
            #mob-content .premium-card,
            #mob-content .card,
            #mob-content .modal-content,
            #mob-content .tab-content {
               max-width: 100% !important;
               min-width: 0 !important;
            }

            #mob-content .table-responsive,
            #mob-content [style*="overflow-x:auto"],
            #mob-content [style*="overflow-x: auto"] {
               width: 100% !important;
               max-width: 100% !important;
               overflow-x: hidden !important;
               -webkit-overflow-scrolling: auto !important;
            }

            #mob-content table,
            #mob-content .table,
            #mob-content .custom-table {
               width: 100% !important;
               min-width: 0 !important;
               max-width: 100% !important;
               table-layout: auto !important;
            }

            #mob-content .custom-table,
            #mob-content #mainTable,
            #mob-content .table-responsive > table {
               display: block !important;
            }

            #mob-content .custom-table thead,
            #mob-content #mainTable thead,
            #mob-content .table-responsive > table thead {
               display: none !important;
            }

            #mob-content .custom-table tbody,
            #mob-content #mainTable tbody,
            #mob-content .table-responsive > table tbody {
               display: block !important;
               width: 100% !important;
               max-width: 100% !important;
            }

            #mob-content .custom-table tbody tr,
            #mob-content #mainTable tbody tr,
            #mob-content .table-responsive > table tbody tr {
               display: flex !important;
               flex-direction: column !important;
               width: 100% !important;
               max-width: 100% !important;
               min-width: 0 !important;
               margin-bottom: 12px !important;
               padding: 12px !important;
               border: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
               border-radius: 12px !important;
               background: var(--bg3, rgba(255,255,255,0.03)) !important;
            }

            #mob-content .custom-table tbody tr,
            #mob-content .custom-table tbody tr:hover,
            #mob-content .custom-table tbody tr:active,
            #mob-content .custom-table tbody tr:focus-within,
            #mob-content #mainTable tbody tr,
            #mob-content #mainTable tbody tr:hover,
            #mob-content #mainTable tbody tr:active,
            #mob-content #mainTable tbody tr:focus-within,
            #mob-content .table-responsive > table tbody tr,
            #mob-content .table-responsive > table tbody tr:hover,
            #mob-content .table-responsive > table tbody tr:active,
            #mob-content .table-responsive > table tbody tr:focus-within {
               --bs-table-bg: transparent !important;
               --bs-table-hover-bg: transparent !important;
               --bs-table-active-bg: transparent !important;
               --bs-table-accent-bg: transparent !important;
               background-color: var(--bg3, rgba(255,255,255,0.03)) !important;
            }

            #mob-content .custom-table tbody tr:last-child,
            #mob-content #mainTable tbody tr:last-child,
            #mob-content .table-responsive > table tbody tr:last-child {
               margin-bottom: 0 !important;
            }

            #mob-content .custom-table tbody td,
            #mob-content #mainTable tbody td,
            #mob-content .table-responsive > table tbody td {
               display: flex !important;
               align-items: center !important;
               justify-content: space-between !important;
               gap: 12px !important;
               width: 100% !important;
               min-width: 0 !important;
               max-width: 100% !important;
               padding: 8px 0 !important;
               border-top: 0 !important;
               border-bottom: 1px solid rgba(255,255,255,0.06) !important;
               color: var(--tx2) !important;
               text-align: right !important;
               white-space: normal !important;
               overflow-wrap: anywhere !important;
               word-break: normal !important;
               background: transparent !important;
               background-color: transparent !important;
               box-shadow: none !important;
            }

            #mob-content .custom-table tbody td:hover,
            #mob-content .custom-table tbody td:active,
            #mob-content .custom-table tbody td:focus-within,
            #mob-content #mainTable tbody td:hover,
            #mob-content #mainTable tbody td:active,
            #mob-content #mainTable tbody td:focus-within,
            #mob-content .table-responsive > table tbody td:hover,
            #mob-content .table-responsive > table tbody td:active,
            #mob-content .table-responsive > table tbody td:focus-within {
               --bs-table-bg: transparent !important;
               --bs-table-hover-bg: transparent !important;
               --bs-table-active-bg: transparent !important;
               --bs-table-accent-bg: transparent !important;
               color: var(--tx2) !important;
               background: transparent !important;
               background-color: transparent !important;
               box-shadow: none !important;
            }

            #mob-content .custom-table tbody td:last-child,
            #mob-content #mainTable tbody td:last-child,
            #mob-content .table-responsive > table tbody td:last-child {
               border-bottom: 0 !important;
               padding-bottom: 0 !important;
            }

            #mob-content .custom-table tbody td[data-label]::before,
            #mob-content #mainTable tbody td[data-label]::before,
            #mob-content .table-responsive > table tbody td[data-label]::before {
               content: attr(data-label);
               flex: 0 0 auto;
               max-width: 44%;
               color: var(--tx3, #8b8b98);
               font-size: 0.76rem;
               font-weight: 700;
               text-align: left;
            }

            #mob-content .custom-table tbody td[colspan],
            #mob-content #mainTable tbody td[colspan],
            #mob-content .table-responsive > table tbody td[colspan] {
               display: block !important;
               text-align: center !important;
               border-bottom: 0 !important;
            }

            #mob-content .custom-table tbody td > *,
            #mob-content #mainTable tbody td > *,
            #mob-content .table-responsive > table tbody td > * {
               max-width: 100% !important;
               min-width: 0 !important;
            }

            #mob-content .custom-table tbody td .d-flex,
            #mob-content #mainTable tbody td .d-flex,
            #mob-content .table-responsive > table tbody td .d-flex {
               flex-wrap: wrap !important;
               justify-content: flex-end;
            }

            #mob-content .custom-table tbody td.text-end,
            #mob-content #mainTable tbody td.text-end,
            #mob-content .table-responsive > table tbody td.text-end {
               justify-content: flex-end !important;
               text-align: right !important;
            }

            #mob-content .table-responsive > table tbody :is(td, th).d-none {
               display: none !important;
            }

            #mob-content #sec-admin-questions .category-filter-mobile {
               display: block !important;
               width: 100% !important;
               max-width: 100% !important;
            }

            #mob-content #sec-admin-questions .category-filter-mobile select {
               width: 100% !important;
               max-width: 100% !important;
               min-width: 0 !important;
            }

            #mob-content #sec-admin-questions .category-filter-cards {
               display: none !important;
            }

            #mob-content .nav-pills,
            #mob-content .nav-tabs,
            #mob-content #nav-pills-container,
            #mob-content .game-categories-scroll {
               display: flex !important;
               flex-wrap: wrap !important;
               width: 100% !important;
               max-width: 100% !important;
               gap: 8px !important;
               overflow-x: hidden !important;
               white-space: normal !important;
               padding-bottom: 0 !important;
            }

            #mob-content .nav-pills .nav-item,
            #mob-content .nav-tabs .nav-item,
            #mob-content .game-categories-scroll .nav-item {
               flex: 1 1 calc(50% - 4px) !important;
               min-width: 0 !important;
               max-width: 100% !important;
            }

            #mob-content .nav-pills .nav-link,
            #mob-content .nav-tabs .nav-link,
            #mob-content .game-categories-scroll .nav-link,
            #mob-content .game-cat-pill {
               width: 100% !important;
               min-width: 0 !important;
               max-width: 100% !important;
               justify-content: center !important;
               text-align: center !important;
               white-space: normal !important;
               line-height: 1.2 !important;
            }
         }

         @media (max-width: 340px) {
            #mob-content .nav-pills .nav-item,
            #mob-content .nav-tabs .nav-item,
            #mob-content .game-categories-scroll .nav-item {
               flex-basis: 100% !important;
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

            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:first-child :is(h1, h2, h3, h4),
            #mob-content :is(.feedback-page-title, .session-page-title, .admin-dashboard-title) {
               width: 100%;
               max-width: min(100%, 22rem);
               font-size: clamp(1.08rem, 5.2vw, 1.28rem) !important;
               line-height: 1.16 !important;
               letter-spacing: 0 !important;
               text-wrap: balance;
            }

            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:first-child :is(h1, h2, h3, h4) i,
            #mob-content :is(.feedback-page-title, .session-page-title, .admin-dashboard-title) i {
               font-size: 0.9em !important;
               margin-right: 0.35rem !important;
            }

            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:first-child p {
               max-width: 34rem;
               margin-bottom: 0 !important;
               font-size: 0.8rem !important;
               line-height: 1.35 !important;
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

         @media (max-width: 380px) {
            #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:first-child :is(h1, h2, h3, h4),
            #mob-content :is(.feedback-page-title, .session-page-title, .admin-dashboard-title) {
               max-width: 18.5rem;
               font-size: 1.04rem !important;
            }
         }

         /* --- Admin content management pages: mobile action polish --- */
         @media (max-width: 767px) {
            #mob-content :is(#sec-admin-categories, #sec-admin-questions, #sec-admin-modules, #sec-admin-game) > .mb-4.d-flex {
               align-items: stretch !important;
            }

            #mob-content :is(#sec-admin-categories, #sec-admin-questions, #sec-admin-modules, #sec-admin-game) > .mb-4.d-flex > div:last-child {
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr));
               gap: 8px !important;
               width: 100%;
            }

            #mob-content :is(#sec-admin-categories, #sec-admin-questions, #sec-admin-modules, #sec-admin-game) > .mb-4.d-flex > div:last-child > :is(a, button),
            #mob-content #sec-admin-categories > .mb-4.d-flex > button {
               width: 100% !important;
               min-height: 44px;
               display: inline-flex;
               align-items: center;
               justify-content: center;
               gap: 7px;
               border-radius: 10px !important;
               padding: 10px 12px !important;
               font-size: 0.78rem !important;
               line-height: 1.15;
               white-space: normal;
            }

            #mob-content #sec-admin-categories > .mb-4.d-flex > button {
               max-width: 18rem;
               margin-inline: auto;
            }

            #mob-content #sec-admin-questions #btnBulkDelete {
               grid-column: 1 / -1;
            }

            #mob-content #sec-admin-questions > .mb-4.d-flex > div:last-child > :last-child {
               grid-column: 1 / -1;
               justify-self: center;
               width: min(100%, 180px) !important;
            }

            #mob-content #sec-admin-modules > .mb-4.d-flex > div:last-child {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            #mob-content #sec-admin-game > .mb-4.d-flex > div:last-child {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:nth-child(1) {
               width: 100% !important;
               margin-right: 0 !important;
               justify-content: center !important;
               align-items: center !important;
               text-align: center !important;
               border-bottom: 0 !important;
               padding-bottom: 4px !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:nth-child(2) {
               width: 100% !important;
               flex-grow: 0 !important;
               align-items: center !important;
               justify-content: center !important;
               text-align: center !important;
               padding-top: 4px !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:nth-child(2) > div {
               width: 100%;
               text-align: center !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:nth-child(2) .d-flex {
               justify-content: center !important;
               text-align: center !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:nth-child(n+3):nth-child(-n+5) {
               display: grid !important;
               grid-template-columns: minmax(86px, 34%) minmax(0, 1fr);
               align-items: center !important;
               gap: 12px !important;
               text-align: right !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:nth-child(n+3):nth-child(-n+5)::before {
               margin-right: 0 !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:nth-child(n+3):nth-child(-n+5) > .d-flex {
               align-items: flex-end !important;
               text-align: right !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child {
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr));
               align-items: stretch !important;
               justify-content: stretch !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child::before {
               display: none !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child > .d-flex {
               display: contents !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child form {
               display: flex !important;
               width: 100%;
            }

            #mob-content :is(#mainCategoriesTable, #mainTable, #modulesTable, .game-table) tbody tr {
               border-radius: 14px !important;
               padding: 14px !important;
               box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
            }

            #mob-content :is(#mainCategoriesTable, #mainTable, #modulesTable, .game-table) tbody td:first-child,
            #mob-content :is(#mainCategoriesTable, #mainTable, #modulesTable, .game-table) tbody td:nth-child(2) {
               color: var(--tx) !important;
            }

            #mob-content :is(#mainCategoriesTable, #mainTable, #modulesTable, .game-table) tbody td:last-child {
               background: rgba(255,255,255,0.025);
               border-radius: 10px;
               margin-top: 4px;
               padding: 10px !important;
            }

            #mob-content :is(#mainCategoriesTable, #mainTable, #modulesTable, .game-table) tbody td:last-child:hover,
            #mob-content :is(#mainCategoriesTable, #mainTable, #modulesTable, .game-table) tbody td:last-child:active,
            #mob-content :is(#mainCategoriesTable, #mainTable, #modulesTable, .game-table) tbody td:last-child:focus-within {
               background: rgba(255,255,255,0.025) !important;
               background-color: rgba(255,255,255,0.025) !important;
            }

            #mob-content :is(.question-actions, .module-actions, .game-table tbody td:last-child > .d-flex),
            #mob-content #mainCategoriesTable tbody td:last-child {
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr));
               gap: 8px !important;
               width: 100%;
               justify-content: stretch !important;
            }

            #mob-content #mainCategoriesTable tbody td:last-child {
               grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            #mob-content #sec-admin-questions .question-actions {
               grid-template-columns: repeat(5, minmax(0, 1fr));
            }

            #mob-content #sec-admin-modules #modulesTable tbody td:first-child {
               align-items: center !important;
               justify-content: center !important;
               text-align: center !important;
            }

            #mob-content #sec-admin-modules .module-title-text {
               text-align: center !important;
               width: 100%;
            }

            #mob-content #sec-admin-modules #modulesTable .badge.bg-warning.ms-1 {
               margin-left: auto !important;
               margin-right: auto !important;
            }

            #mob-content #sec-admin-modules .module-actions {
               grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #mob-content :is(.question-actions, .module-actions, .game-table tbody td:last-child > .d-flex) form,
            #mob-content #mainCategoriesTable tbody td:last-child form {
               display: flex !important;
               width: 100%;
            }

            #mob-content :is(.question-actions, .module-actions, .game-table tbody td:last-child > .d-flex) :is(a, button),
            #mob-content #mainCategoriesTable tbody td:last-child :is(a, button) {
               width: 100%;
               min-height: 40px;
               display: inline-flex;
               align-items: center;
               justify-content: center;
               gap: 6px;
               border-radius: 9px !important;
               font-size: 0.74rem !important;
               line-height: 1.1;
               padding: 8px 10px !important;
            }

            #mob-content #mainCategoriesTable tbody td:last-child :is(a, button) {
               min-height: 36px;
               padding: 7px 6px !important;
               font-size: 0.68rem !important;
            }

            #mob-content #sec-admin-questions .question-actions :is(a, button) {
               min-height: 34px;
               padding: 7px 5px !important;
               font-size: 0.66rem !important;
            }

            #mob-content #sec-admin-questions .question-actions :is(a, button) i {
               margin-right: 0 !important;
            }

            #mob-content #sec-admin-questions .question-actions :is(a, button) {
               font-size: 0 !important;
            }

            #mob-content #sec-admin-questions .question-actions :is(a, button) i {
               font-size: 0.78rem !important;
            }

            #mob-content :is(#mainCategoriesTable, #mainTable, #modulesTable, .game-table) .badge {
               display: inline-flex;
               align-items: center;
               max-width: 100%;
               white-space: normal !important;
               text-align: left;
               line-height: 1.15;
            }

            #mob-content #sec-admin-modules .modules-panel {
               border-radius: 14px;
            }

            #mob-content #sec-admin-modules .modules-panel-header {
               padding: 14px !important;
            }

            #mob-content #sec-admin-modules .modules-stats-row {
               display: grid;
               grid-template-columns: repeat(2, minmax(0, 1fr));
               gap: 10px;
            }

            #mob-content #sec-admin-modules .modules-stats-row > [class*="col-"] {
               width: 100% !important;
               padding: 0 !important;
            }

            #mob-content #sec-admin-modules .modules-stats-row > [class*="col-"] > div {
               min-height: 94px !important;
               padding: 16px !important;
               border-radius: 14px !important;
            }

            #mob-content #sec-admin-modules .modules-stats-row h2 {
               font-size: 1.55rem;
            }

            #mob-content #sec-admin-game .game-categories-scroll {
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr));
            }
         }

         @media (max-width: 380px) {
            #mob-content :is(#sec-admin-categories, #sec-admin-questions, #sec-admin-modules, #sec-admin-game) > .mb-4.d-flex > div:last-child,
            #mob-content #sec-admin-game .game-categories-scroll,
            #mob-content :is(.question-actions, .module-actions, .game-table tbody td:last-child > .d-flex) {
               grid-template-columns: 1fr !important;
            }

            #mob-content #sec-admin-modules .modules-stats-row {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            #mob-content #sec-admin-questions > .mb-4.d-flex > div:last-child {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            #mob-content #sec-admin-modules > .mb-4.d-flex > div:last-child {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            #mob-content #sec-admin-game > .mb-4.d-flex > div:last-child {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            #mob-content #sec-admin-questions .question-actions {
               grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            }

            #mob-content #sec-admin-modules .module-actions {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
         }

         /* --- PWA Install Prompt --- */
         #pwa-install-prompt {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 1070;
            min-height: 100vh;
            min-height: 100dvh;
            padding: max(18px, env(safe-area-inset-top, 0px)) max(14px, env(safe-area-inset-right, 0px)) max(18px, env(safe-area-inset-bottom, 0px)) max(14px, env(safe-area-inset-left, 0px));
            background: rgba(2, 6, 23, 0.58);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            text-align: center;
            animation: mobFadeIn 0.24s ease;
         }
         #pwa-install-prompt .pwa-install-dialog {
            position: absolute;
            top: 50%;
            left: 50%;
            width: min(calc(100vw - 32px), 390px);
            max-height: calc(100vh - 36px);
            max-height: calc(100dvh - 36px);
            overflow-y: auto;
            transform: translate(-50%, -50%);
            background: rgba(10, 4, 4, 0.95);
            border: 1px solid var(--adm-bd);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 24px 70px rgba(0,0,0,0.5);
         }
         .lm #pwa-install-prompt { background: rgba(15, 23, 42, 0.32); }
         .lm #pwa-install-prompt .pwa-install-dialog { background: rgba(255, 248, 248, 0.97); box-shadow: 0 24px 70px rgba(15,23,42,0.18); }
         #pwa-install-prompt h5 { color: #fff; font-weight: 700; margin-bottom: 8px; font-size: 1.1rem; }
         .lm #pwa-install-prompt h5 { color: #111; }
         #pwa-install-prompt p { color: #aaa; font-size: 0.88rem; line-height: 1.5; margin-bottom: 18px; }
         .lm #pwa-install-prompt p { color: #555; }
         .pwa-btn-wrap { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; justify-content: center; }
         .pwa-btn-no { width: 100%; min-height: 44px; padding: 10px 14px; border-radius: 10px; border: 1px solid #444; background: transparent; color: #fff; font-weight: 600; cursor: pointer; }
         .lm .pwa-btn-no { border-color: #ccc; color: #333; }
         .pwa-btn-yes { width: 100%; min-height: 44px; padding: 10px 14px; border-radius: 10px; border: none; background: var(--adm, #f87171); color: #fff; font-weight: 600; cursor: pointer; }
         #pwa-install-prompt[data-mode="message"] .pwa-btn-no { grid-column: 1 / -1; }

         .mob-admin-logo-ring {
            background: #ffffff !important;
            border: 2px solid #ffffff !important;
            box-shadow: none !important;
            padding: 0 !important;
         }
         .mob-header-brand img {
            background: transparent !important;
            object-fit: contain !important;
            filter: drop-shadow(0 6px 10px rgba(37, 99, 235, 0.18));
         }
      </style>
      @stack('styles')
      <style data-admin-mobile-topbar-drawer-fix>
         body.admin-mobile-shell {
            --admin-drawer-bg: rgba(255, 255, 255, 0.98);
            --admin-drawer-panel: rgba(255, 255, 255, 0.96);
            --admin-drawer-card: rgba(248, 250, 252, 0.96);
            --admin-drawer-card-hover: rgba(239, 246, 255, 0.98);
            --admin-drawer-border: rgba(15, 23, 42, 0.12);
            --admin-drawer-text: #0f172a;
            --admin-drawer-muted: #475569;
            --admin-drawer-subtle: #64748b;
            --admin-drawer-active-bg: rgba(248, 113, 113, 0.14);
            --admin-drawer-backdrop: rgba(15, 23, 42, 0.32);
            --admin-notif-bg: rgba(255, 255, 255, 0.98);
            --admin-notif-panel: rgba(248, 250, 252, 0.98);
            --admin-notif-text: #0f172a;
            --admin-notif-muted: #475569;
            --admin-notif-border: rgba(15, 23, 42, 0.12);
         }

         html:not(.lm) body.admin-mobile-shell,
         html.dm body.admin-mobile-shell,
         body.admin-mobile-shell.dm {
            --admin-drawer-bg: rgba(15, 10, 10, 0.98);
            --admin-drawer-panel: rgba(24, 16, 17, 0.98);
            --admin-drawer-card: rgba(255, 255, 255, 0.075);
            --admin-drawer-card-hover: rgba(255, 255, 255, 0.12);
            --admin-drawer-border: rgba(255, 255, 255, 0.14);
            --admin-drawer-text: #f8fafc;
            --admin-drawer-muted: #cbd5e1;
            --admin-drawer-subtle: #94a3b8;
            --admin-drawer-active-bg: rgba(248, 113, 113, 0.22);
            --admin-drawer-backdrop: rgba(2, 6, 23, 0.56);
            --admin-notif-bg: rgba(15, 10, 10, 0.98);
            --admin-notif-panel: rgba(24, 16, 17, 0.98);
            --admin-notif-text: #f8fafc;
            --admin-notif-muted: #cbd5e1;
            --admin-notif-border: rgba(255, 255, 255, 0.14);
         }

         html.lm body.admin-mobile-shell,
         body.admin-mobile-shell.lm {
            --admin-drawer-bg: rgba(255, 255, 255, 0.98);
            --admin-drawer-panel: rgba(255, 255, 255, 0.96);
            --admin-drawer-card: rgba(248, 250, 252, 0.96);
            --admin-drawer-card-hover: rgba(239, 246, 255, 0.98);
            --admin-drawer-border: rgba(15, 23, 42, 0.12);
            --admin-drawer-text: #0f172a;
            --admin-drawer-muted: #475569;
            --admin-drawer-subtle: #64748b;
            --admin-drawer-active-bg: rgba(248, 113, 113, 0.14);
            --admin-drawer-backdrop: rgba(15, 23, 42, 0.32);
            --admin-notif-bg: rgba(255, 255, 255, 0.98);
            --admin-notif-panel: rgba(248, 250, 252, 0.98);
            --admin-notif-text: #0f172a;
            --admin-notif-muted: #475569;
            --admin-notif-border: rgba(15, 23, 42, 0.12);
         }

         body.admin-mobile-shell .mob-more-backdrop {
            z-index: 1090;
            background: var(--admin-drawer-backdrop);
         }

         body.admin-mobile-shell #mobProfileDropdown {
            z-index: 1110;
            background: var(--admin-drawer-bg) !important;
            border-color: var(--admin-drawer-border) !important;
            color: var(--admin-drawer-text);
            box-shadow: 0 24px 72px rgba(0, 0, 0, 0.34);
         }

         body.admin-mobile-shell #mobProfileDropdown[data-origin="top"] {
            top: calc(var(--mob-top-h) + var(--mob-safe-top) + 8px) !important;
            right: max(10px, env(safe-area-inset-right, 0px)) !important;
            bottom: auto !important;
            left: auto !important;
            width: min(310px, calc(100vw - 20px)) !important;
            max-width: calc(100vw - 20px) !important;
            max-height: min(62dvh, 430px);
            margin: 0 !important;
            transform: none !important;
         }

         body.admin-mobile-shell #mobProfileDropdown[data-origin="bottom"] {
            top: auto !important;
            right: max(10px, env(safe-area-inset-right, 0px)) !important;
            bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 10px) !important;
            left: max(10px, env(safe-area-inset-left, 0px)) !important;
            width: auto !important;
            max-width: none !important;
            max-height: calc(var(--sr-visual-vh, 100dvh) - var(--mob-nav-h) - var(--mob-safe-bottom) - var(--mob-safe-top) - 22px);
            margin: 0 !important;
            transform: none !important;
         }

         body.admin-mobile-shell #mobMoreDropdown {
            z-index: 1110;
            background: var(--admin-drawer-bg) !important;
            border-color: var(--admin-drawer-border) !important;
            color: var(--admin-drawer-text);
            box-shadow: 0 24px 72px rgba(0, 0, 0, 0.34);
         }

         body.admin-mobile-shell #mobMoreDropdown[data-origin="bottom"] {
            top: auto !important;
            right: max(10px, env(safe-area-inset-right, 0px)) !important;
            bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 10px) !important;
            left: max(10px, env(safe-area-inset-left, 0px)) !important;
            width: auto !important;
            max-width: none !important;
            max-height: calc(var(--sr-visual-vh, 100dvh) - var(--mob-nav-h) - var(--mob-safe-bottom) - var(--mob-safe-top) - 22px);
            margin: 0 !important;
            transform: none !important;
         }

         body.admin-mobile-shell #mobMoreDropdown .mob-profile-account,
         body.admin-mobile-shell #mobMoreDropdown .mob-profile-account-head {
            display: none !important;
         }

         body.admin-mobile-shell #mobMoreDropdown .mob-profile-pages,
         body.admin-mobile-shell #mobMoreDropdown .mob-profile-pages-head {
            display: flex !important;
         }

         body.admin-mobile-shell #mobMoreDropdown .mob-profile-pages {
            display: block !important;
         }

         body.admin-mobile-shell #mobProfileDropdown[data-mode="pages"] > .mob-profile-account-head,
         body.admin-mobile-shell #mobProfileDropdown.mob-profile-is-pages > .mob-profile-account-head,
         body.admin-mobile-shell #mobProfileDropdown[data-mode="account"] .mob-profile-pages,
         body.admin-mobile-shell #mobProfileDropdown.mob-profile-is-account .mob-profile-pages,
         body.admin-mobile-shell #mobProfileDropdown[data-mode="account"] .mob-profile-pages-head,
         body.admin-mobile-shell #mobProfileDropdown.mob-profile-is-account .mob-profile-pages-head,
         body.admin-mobile-shell #mobProfileDropdown[data-mode="pages"] .mob-profile-account,
         body.admin-mobile-shell #mobProfileDropdown.mob-profile-is-pages .mob-profile-account {
            display: none !important;
         }

         body.admin-mobile-shell #mobProfileDropdown[data-mode="account"] > .mob-profile-account-head,
         body.admin-mobile-shell #mobProfileDropdown.mob-profile-is-account > .mob-profile-account-head,
         body.admin-mobile-shell #mobProfileDropdown.mob-profile-is-account .mob-profile-account,
         body.admin-mobile-shell #mobProfileDropdown[data-mode="pages"] .mob-profile-pages-head {
            display: flex !important;
         }

         body.admin-mobile-shell #mobProfileDropdown.mob-profile-is-account .mob-profile-account {
            display: block !important;
         }

         body.admin-mobile-shell #mobProfileDropdown.mob-profile-is-pages .mob-profile-pages {
            display: block !important;
         }

         body.admin-mobile-shell #mobProfileDropdown .mob-profile-head,
         body.admin-mobile-shell #mobProfileDropdown .mob-profile-pages-head {
            background: var(--admin-drawer-panel);
            border-color: var(--admin-drawer-border);
         }

         body.admin-mobile-shell #mobProfileDropdown[data-mode="pages"] .mob-profile-menu {
            padding: 0 10px 10px;
         }

         body.admin-mobile-shell #mobProfileDropdown[data-mode="account"] .mob-profile-menu {
            padding: 10px;
         }

         body.admin-mobile-shell #mobProfileDropdown[data-origin="top"] .mob-profile-menu {
            max-height: calc(62dvh - 72px);
         }

         body.admin-mobile-shell #mobProfileDropdown[data-origin="bottom"] .mob-profile-menu {
            max-height: calc(var(--sr-visual-vh, 100dvh) - var(--mob-nav-h) - var(--mob-safe-bottom) - var(--mob-safe-top) - 72px);
         }

         body.admin-mobile-shell #mobProfileDropdown .mob-profile-name,
         body.admin-mobile-shell #mobProfileDropdown .mob-profile-pages-head,
         body.admin-mobile-shell #mobProfileDropdown .mob-profile-link,
         body.admin-mobile-shell #mobProfileDropdown .mob-profile-action {
            color: var(--admin-drawer-text) !important;
         }

         body.admin-mobile-shell #mobProfileDropdown .mob-profile-role,
         body.admin-mobile-shell #mobProfileDropdown .mob-profile-section-title {
            color: var(--admin-drawer-subtle) !important;
         }

         body.admin-mobile-shell #mobProfileDropdown .mob-profile-close {
            background: var(--admin-drawer-card);
            border-color: var(--admin-drawer-border);
            color: var(--admin-drawer-muted);
         }

         body.admin-mobile-shell #mobProfileDropdown .mob-profile-link,
         body.admin-mobile-shell #mobProfileDropdown .mob-profile-action {
            background: var(--admin-drawer-card) !important;
            border-color: var(--admin-drawer-border) !important;
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.05) inset;
         }

         body.admin-mobile-shell #mobProfileDropdown .mob-profile-link:active,
         body.admin-mobile-shell #mobProfileDropdown .mob-profile-link:hover,
         body.admin-mobile-shell #mobProfileDropdown .mob-profile-action:active {
            background: var(--admin-drawer-card-hover) !important;
         }

         body.admin-mobile-shell #mobProfileDropdown .mob-profile-link.active {
            background: var(--admin-drawer-active-bg) !important;
            border-color: rgba(248, 113, 113, 0.42) !important;
            outline: 1px solid rgba(248, 113, 113, 0.22);
         }

         body.admin-mobile-shell #mobProfileDropdown .mob-profile-link span,
         body.admin-mobile-shell #mobProfileDropdown .mob-profile-action span {
            color: inherit !important;
            font-weight: 800;
            line-height: 1.25;
         }

         body.admin-mobile-shell #mobProfileDropdown[data-mode="account"] .mob-profile-grid {
            grid-template-columns: 1fr;
         }

         body.admin-mobile-shell #mobProfileDropdown[data-mode="pages"] .mob-profile-link {
            min-height: 44px;
         }

         body.admin-mobile-shell .mob-notification-wrap {
            position: static;
         }

         body.admin-mobile-shell #mob-header .mob-notification-dropdown {
            position: fixed !important;
            top: calc(var(--mob-top-h) + var(--mob-safe-top) + 8px) !important;
            right: max(10px, env(safe-area-inset-right, 0px)) !important;
            left: auto !important;
            z-index: 1125;
            width: min(360px, calc(100vw - 20px)) !important;
            max-width: calc(100vw - 20px) !important;
            max-height: calc(var(--sr-visual-vh, 100dvh) - var(--mob-top-h) - var(--mob-safe-top) - 18px);
            margin: 0 !important;
            transform: none !important;
            background: var(--admin-notif-bg) !important;
            border-color: var(--admin-notif-border) !important;
            color: var(--admin-notif-text);
         }

         body.admin-mobile-shell #mob-header .admin-mob-notif-header,
         body.admin-mobile-shell #mob-header .admin-mob-notif-footer {
            background: var(--admin-notif-panel) !important;
            border-color: var(--admin-notif-border) !important;
         }

         body.admin-mobile-shell #mob-header .admin-mob-notif-title,
         body.admin-mobile-shell #mob-header .mob-notification-list .admin-activity-item,
         body.admin-mobile-shell #mob-header .mob-notification-list .fw-bold {
            color: var(--admin-notif-text) !important;
         }

         body.admin-mobile-shell #mob-header .mob-notification-list {
            background: var(--admin-notif-bg) !important;
            max-height: min(55dvh, 380px);
         }

         body.admin-mobile-shell #mob-header .mob-notification-list .admin-activity-item {
            background: var(--admin-notif-panel) !important;
            border-color: var(--admin-notif-border) !important;
         }

         body.admin-mobile-shell #mob-header .mob-notification-list .text-muted,
         body.admin-mobile-shell #mob-header .mob-notification-list .admin-activity-item > div:last-child {
            color: var(--admin-notif-muted) !important;
         }

         body.admin-mobile-shell #mob-header .admin-mob-notif-action {
            flex: 0 0 auto;
            background: var(--admin-drawer-card) !important;
            border-color: var(--admin-notif-border) !important;
            color: var(--admin-notif-text) !important;
         }

         body.admin-mobile-shell #mob-header .admin-mob-notif-action.danger {
            color: #f87171 !important;
            border-color: rgba(248, 113, 113, 0.34) !important;
         }

         @media (max-width: 575px) {
            body.admin-mobile-shell #mob-header .admin-mob-notif-header {
               align-items: center;
               flex-direction: row;
            }

            body.admin-mobile-shell #mob-header .admin-mob-notif-actions {
               width: auto;
            }

            body.admin-mobile-shell #mob-header .admin-mob-notif-action {
               width: 36px;
               min-width: 36px;
               padding: 0;
            }

            body.admin-mobile-shell #mob-header .admin-mob-notif-action span {
               display: none;
            }
         }

         @media (max-width: 380px) {
            body.admin-mobile-shell #mobProfileDropdown[data-origin="top"],
            body.admin-mobile-shell #mob-header .mob-notification-dropdown {
               right: max(8px, env(safe-area-inset-right, 0px)) !important;
               left: max(8px, env(safe-area-inset-left, 0px)) !important;
               width: auto !important;
               max-width: none !important;
            }
         }
      </style>
      <style data-mobile-side-gutter="10px">
         @media (max-width: 991.98px) {
            body.admin-mobile-shell {
               --sr-mobile-inline: 10px !important;
            }

            body.admin-mobile-shell #mob-content > .db-content {
               padding-left: max(10px, env(safe-area-inset-left, 0px)) !important;
               padding-right: max(10px, env(safe-area-inset-right, 0px)) !important;
            }

            body.admin-mobile-shell #mob-content > .db-content > :is(.db-section, .container, .container-fluid, [id]) {
               padding-left: 0 !important;
               padding-right: 0 !important;
               margin-left: 0 !important;
               margin-right: 0 !important;
            }

            body.admin-mobile-shell #mob-content > .db-content > .row {
               margin-left: 0 !important;
               margin-right: 0 !important;
            }
         }
      </style>
      <style data-admin-mobile-ui-repair="dashboard-pages">
         @media (max-width: 767.98px) {
            body.admin-mobile-shell,
            body.admin-mobile-shell * {
               box-sizing: border-box;
            }

            body.admin-mobile-shell {
               --admin-page-gap: 12px;
               --admin-card-radius: 12px;
               --admin-control-h: 42px;
               --admin-label-w: minmax(82px, 34%);
            }

            body.admin-mobile-shell #mob-content,
            body.admin-mobile-shell #mob-content > .db-content,
            body.admin-mobile-shell #mob-content .db-section {
               width: 100% !important;
               max-width: 100% !important;
               overflow-x: clip !important;
            }

            body.admin-mobile-shell #mob-content :is(.row, .container, .container-fluid) {
               min-width: 0 !important;
               max-width: 100% !important;
            }

            body.admin-mobile-shell #mob-content .row {
               margin-left: 0 !important;
               margin-right: 0 !important;
            }

            body.admin-mobile-shell #mob-content .row > [class*="col-"] {
               min-width: 0 !important;
               max-width: 100% !important;
            }

            body.admin-mobile-shell #mob-content :is(.premium-card, .card, [class*="panel"], [class*="wrapper"]) {
               max-width: 100% !important;
            }

            body.admin-mobile-shell #mob-content .db-section > .d-flex:first-child,
            body.admin-mobile-shell #mob-content :is(.admin-dashboard-header, .feedback-header, .ai-providers-header) {
               align-items: stretch !important;
               gap: var(--admin-page-gap) !important;
               margin-bottom: 14px !important;
               min-width: 0 !important;
            }

            body.admin-mobile-shell #mob-content .db-section > .d-flex:first-child > *,
            body.admin-mobile-shell #mob-content :is(.admin-dashboard-header, .feedback-header, .ai-providers-header) > * {
               min-width: 0 !important;
               max-width: 100% !important;
            }

            body.admin-mobile-shell #mob-content :is(h1, h2, h3, h4, h5, h6, p, span, a, button, label, small, td, th) {
               overflow-wrap: anywhere;
            }

            body.admin-mobile-shell #mob-content :is(.btn, button, .badge, .stat-badge) {
               max-width: 100%;
               white-space: normal !important;
               line-height: 1.15;
            }

            body.admin-mobile-shell #mob-content :is(.btn, button, .form-control, .form-select) {
               min-height: var(--admin-control-h);
            }

            body.admin-mobile-shell #mob-content :is(form.row, .session-filter-form, .feedback-filter-form) {
               --bs-gutter-x: 8px;
               --bs-gutter-y: 8px;
               margin-left: 0 !important;
               margin-right: 0 !important;
            }

            body.admin-mobile-shell #mob-content :is(form.row, .session-filter-form, .feedback-filter-form) > [class*="col-"] {
               width: 100% !important;
               max-width: 100% !important;
               padding-left: 0 !important;
               padding-right: 0 !important;
            }

            body.admin-mobile-shell #mob-content :is([style*="overflow-x:auto"], [style*="overflow-x: auto"], .table-responsive) {
               width: 100% !important;
               max-width: 100% !important;
               overflow-x: visible !important;
               -webkit-overflow-scrolling: auto !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table,
            body.admin-mobile-shell #mob-content table {
               width: 100% !important;
               min-width: 0 !important;
               max-width: 100% !important;
               table-layout: auto !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table thead,
            body.admin-mobile-shell #mob-content table.admin-mobile-card-table thead {
               display: none !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody,
            body.admin-mobile-shell #mob-content .table-responsive > table tbody tr,
            body.admin-mobile-shell #mob-content .table-responsive > table tbody td {
               display: block;
               width: 100% !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody tr {
               border: 1px solid var(--bd) !important;
               border-radius: var(--admin-card-radius) !important;
               background: var(--bg3) !important;
               padding: 11px !important;
               margin-bottom: 10px !important;
               box-shadow: none !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td {
               display: grid !important;
               grid-template-columns: var(--admin-label-w) minmax(0, 1fr);
               align-items: center;
               gap: 10px;
               min-width: 0 !important;
               padding: 7px 0 !important;
               border: 0 !important;
               border-bottom: 1px solid rgba(148, 163, 184, 0.14) !important;
               text-align: right !important;
               white-space: normal !important;
               overflow: visible !important;
               text-overflow: clip !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td:last-child {
               border-bottom: 0 !important;
               padding-top: 10px !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td[data-label]::before {
               content: attr(data-label);
               color: var(--tx3);
               font-size: 0.66rem;
               font-weight: 800;
               letter-spacing: 0.04em;
               line-height: 1.15;
               text-align: left;
               text-transform: uppercase;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td[colspan] {
               display: block !important;
               text-align: center !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td[colspan]::before {
               display: none !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td > * {
               min-width: 0 !important;
               max-width: 100% !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td .d-flex {
               min-width: 0 !important;
               max-width: 100% !important;
               flex-wrap: wrap;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td.text-end,
            body.admin-mobile-shell #mob-content .table-responsive > table tbody td:last-child {
               text-align: right !important;
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td:last-child :is(.btn, button, a.btn) {
               min-width: 36px;
            }

            body.admin-mobile-shell #mob-content :is(.pagination, [class*="pagination"], [class*="page-actions"]) {
               max-width: 100%;
               flex-wrap: wrap;
            }

            body.admin-mobile-shell #mob-content :is(.text-truncate, [style*="text-overflow"]) {
               white-space: normal !important;
               overflow: visible !important;
               text-overflow: clip !important;
            }
         }

         @media (max-width: 380px) {
            body.admin-mobile-shell {
               --admin-label-w: minmax(76px, 32%);
            }

            body.admin-mobile-shell #mob-content .table-responsive > table tbody td {
               gap: 8px;
            }
         }
      </style>
   </head>
   <body class="admin-mobile-shell mobile-shell" data-layout-shell="mobile" data-app-surface="admin">

      <!-- ===== ADMIN MOBILE TOP HEADER ===== -->
      <header id="mob-header">
         <a href="{{ route('admin.dashboard') }}" class="mob-header-brand">
            <span class="mob-admin-logo-ring">
               <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="{{ $systemName ?? 'SpeakReady AI' }}">
            </span>
            <span>{{ $systemName ?? 'SpeakReady AI' }} Admin</span>
         </a>
         <div class="mob-header-right">
            <div class="dropdown mob-notification-wrap">
                <a href="#" id="mobNotificationBtn" class="mob-icon-btn position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Live Activity" style="text-decoration:none;" onclick="resetAdminActivityBadge('mobile'); closeMobileProfile();">
                   <i class="fa-regular fa-bell"></i>
                   <span id="admin-activity-badge-mobile" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-dark rounded-circle" style="display:none; width: 8px; height: 8px; margin-left: -5px; margin-top: 5px;">
                      <span class="visually-hidden">New alerts</span>
                   </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end mob-notification-dropdown">
                    <div class="admin-mob-notif-header">
                        <div class="admin-mob-notif-title">
                            <i class="fa-regular fa-bell" style="color:var(--pur)"></i>
                            <span>Notifications</span>
                            <span id="admin-activity-count-mobile" class="admin-mob-notif-count">0 new</span>
                        </div>
                        <div class="admin-mob-notif-actions">
                            <button class="admin-mob-notif-action" type="button" onclick="markAllActivitiesRead(event)" title="Mark all as read"><i class="fa-solid fa-check"></i><span>Read</span></button>
                            <button class="admin-mob-notif-action danger" type="button" onclick="clearAllActivities(event)" title="Clear all"><i class="fa-solid fa-trash"></i><span>Clear</span></button>
                            <button class="admin-mob-notif-action admin-mob-notif-close" type="button" data-admin-notif-close aria-label="Close notifications"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>
                    <div id="admin-activity-list-mobile" class="mob-notification-list">
                        <div class="p-3 text-center text-muted" style="font-size:0.85rem;">Loading activities...</div>
                    </div>
                    <div class="admin-mob-notif-footer">
                        <a href="{{ route('admin.notifications.index') }}" class="admin-mob-notif-view-all">
                            <i class="fa-solid fa-list-check me-2"></i>View All Activities
                        </a>
                    </div>
                </div>
            </div>
            <button class="mob-icon-btn" id="mobFullscreenBtn" type="button" aria-label="Enter fullscreen" title="Enter fullscreen" data-user-fullscreen-toggle>
               <i class="fa-solid fa-expand" id="mobFullscreenIcon"></i>
            </button>
            <button class="mob-icon-btn" onclick="toggleTheme()" title="Toggle theme">
               <i class="fa-solid fa-sun" id="mobSunI" style="display:none"></i>
               <i class="fa-solid fa-moon" id="mobMoonI"></i>
            </button>
            <div class="mob-avatar-adm"
                 id="mobProfileBtn"
                 onclick="toggleMobileProfile(event, 'account')"
                 aria-controls="mobProfileDropdown"
                 aria-expanded="false"
                 role="button"
                 tabindex="0"
                 title="Account"
                 style="padding:0;overflow:hidden;">
               @if(Auth::check() && Auth::user()->profile_photo_path)
                  @php
                      $photoPath = Auth::user()->profile_photo_path;
                      $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                  @endphp
                  <img src="{{ $photoUrl }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
               @else
                  {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'A' }}
               @endif
            </div>
         </div>
      </header>

      <!-- ===== PAGE CONTENT ===== -->
      <div id="mob-content" data-layout-shell="mobile">
         <div class="db-content" data-layout-shell="mobile">
            @yield('content')
            @include('mobile.partials.admin-motion-title-svg')
         </div>
      </div>
      @stack('modals')

      <style>
         @media (max-width: 767px) {
            body .modal[id^="modal-"] {
               padding: calc(var(--mob-top-h, 56px) + env(safe-area-inset-top, 0px) + 12px) 14px calc(var(--mob-nav-h, 64px) + env(safe-area-inset-bottom, 0px) + 14px) !important;
            }

            body .modal[id^="modal-"].show {
               display: flex !important;
               align-items: center !important;
               justify-content: center !important;
            }

            body .modal[id^="modal-"] .modal-dialog,
            body .modal[id^="modal-"] .modal-dialog.modal-lg,
            body .modal[id^="modal-"] .modal-dialog.modal-dialog-centered {
               width: min(90vw, 390px) !important;
               max-width: min(90vw, 390px) !important;
               min-height: 0 !important;
               height: auto !important;
               max-height: none !important;
               margin: auto !important;
               display: block !important;
               align-items: initial !important;
               transform: none !important;
            }

            body .modal[id^="modal-"] .modal-content {
               width: 100% !important;
               height: auto !important;
               min-height: 0 !important;
               max-height: min(64dvh, 540px) !important;
               border-radius: 18px !important;
               overflow: hidden !important;
               background: var(--sf) !important;
               border: 1px solid var(--bd) !important;
               box-shadow: 0 24px 70px rgba(2, 6, 23, 0.34) !important;
            }

            body .modal[id^="modal-"] .modal-header {
               padding: 14px 16px 8px !important;
               flex: 0 0 auto !important;
            }

            body .modal[id^="modal-"] .modal-body {
               flex: 0 1 auto !important;
               max-height: calc(min(64dvh, 540px) - 112px) !important;
               overflow-y: auto !important;
               padding: 8px 16px 14px !important;
            }

            body .modal[id^="modal-"] .modal-footer {
               position: static !important;
               flex: 0 0 auto !important;
               padding: 10px 16px 14px !important;
               margin-top: 0 !important;
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
               gap: 8px !important;
            }

            body .modal[id^="modal-"] .btn-save-fixed {
               position: static !important;
               bottom: auto !important;
               z-index: auto !important;
               margin-top: 10px !important;
               padding: 10px !important;
               border-radius: 12px !important;
               box-shadow: none !important;
            }

            body .modal[id^="modal-"] .modal-content {
               --modal-row-bg: rgba(15, 23, 42, 0.04);
               --modal-row-bd: rgba(15, 23, 42, 0.12);
               --modal-label-tx: #0f172a;
               --modal-value-tx: #334155;
               color: var(--modal-label-tx) !important;
            }

            body:not(.lm) .modal[id^="modal-"] .modal-content {
               --modal-row-bg: rgba(255, 255, 255, 0.06);
               --modal-row-bd: rgba(255, 255, 255, 0.14);
               --modal-label-tx: #f8fafc;
               --modal-value-tx: #cbd5e1;
            }

            .lm body .modal[id^="modal-"] .modal-content,
            html.lm body .modal[id^="modal-"] .modal-content {
               --modal-row-bg: rgba(15, 23, 42, 0.04);
               --modal-row-bd: rgba(15, 23, 42, 0.12);
               --modal-label-tx: #0f172a;
               --modal-value-tx: #334155;
            }

            body .modal[id^="modal-"] .modal-content :is(.text-white, .form-check-label, .form-label, label, h1, h2, h3, h4, h5, h6, strong) {
               color: var(--modal-label-tx) !important;
               -webkit-text-fill-color: var(--modal-label-tx) !important;
            }

            body .modal[id^="modal-"] .modal-content :is(p, span, li, small, .text-muted) {
               color: var(--modal-value-tx) !important;
               -webkit-text-fill-color: var(--modal-value-tx) !important;
            }

            body .modal[id^="modal-"] .modal-content .list-group,
            body .modal[id^="modal-"] .modal-content .list-group-item,
            body .modal[id^="modal-"] .modal-content .custom-switch-container {
               background: var(--modal-row-bg) !important;
               border-color: var(--modal-row-bd) !important;
            }

            body .modal[id^="modal-"] .modal-content .list-group-item {
               color: var(--modal-label-tx) !important;
               -webkit-text-fill-color: var(--modal-label-tx) !important;
            }

            body .modal[id^="modal-"] .modal-content .list-group-item :is(span, div, small):not(.badge) {
               color: var(--modal-value-tx) !important;
               -webkit-text-fill-color: var(--modal-value-tx) !important;
            }

            body .modal[id^="modal-"] .modal-content .list-group-item > :first-child,
            body .modal[id^="modal-"] .modal-content .custom-switch-container h6 {
               color: var(--modal-label-tx) !important;
               -webkit-text-fill-color: var(--modal-label-tx) !important;
               font-weight: 800;
            }

            body .modal[id^="modal-"] .modal-content .badge {
               -webkit-text-fill-color: currentColor !important;
            }

            body .modal[id^="modal-"] .modal-footer .btn,
            body .modal[id^="modal-"] .modal-footer button {
               width: 100% !important;
               min-height: 40px !important;
               border-radius: 11px !important;
               margin: 0 !important;
               display: inline-flex !important;
               align-items: center !important;
               justify-content: center !important;
               gap: 6px !important;
               font-size: 0.78rem !important;
               font-weight: 800 !important;
            }

            body .modal[id^="modal-"] .modal-footer [data-bs-dismiss="modal"],
            body .modal[id^="modal-"] .modal-footer .btn-secondary,
            body .modal[id^="modal-"] .modal-footer .btn-outline-secondary {
               border: 1px solid var(--bd) !important;
               background: var(--bg3) !important;
               color: var(--tx) !important;
               -webkit-text-fill-color: var(--tx) !important;
            }

            #mob-content :is(.table, .custom-table, #mainTable, #mainCategoriesTable, #modulesTable, #mainFeedbackTable, #mainSessionsTable, .game-table) {
               --bs-table-bg: transparent !important;
               --bs-table-striped-bg: transparent !important;
               --bs-table-hover-bg: transparent !important;
               --bs-table-active-bg: transparent !important;
               --bs-table-accent-bg: transparent !important;
            }

            #mob-content :is(.table, .custom-table, #mainTable, #mainCategoriesTable, #modulesTable, #mainFeedbackTable, #mainSessionsTable, .game-table) :is(tbody, tr, td, th),
            #mob-content :is(.table, .custom-table, #mainTable, #mainCategoriesTable, #modulesTable, #mainFeedbackTable, #mainSessionsTable, .game-table) :is(tbody, tr, td, th):is(:hover, :active, :focus, :focus-within) {
               --bs-table-bg: transparent !important;
               --bs-table-striped-bg: transparent !important;
               --bs-table-hover-bg: transparent !important;
               --bs-table-active-bg: transparent !important;
               --bs-table-accent-bg: transparent !important;
               background: transparent !important;
               background-color: transparent !important;
               box-shadow: none !important;
            }

            #mob-content :is(.table, .custom-table, #mainTable, #mainCategoriesTable, #modulesTable, #mainFeedbackTable, #mainSessionsTable, .game-table) tbody tr {
               background: var(--bg3, rgba(255,255,255,0.03)) !important;
               background-color: var(--bg3, rgba(255,255,255,0.03)) !important;
            }

            #mob-content :is(#mainCategoriesTable, #mainTable, #modulesTable, #mainFeedbackTable, #mainSessionsTable, .game-table) tbody td:last-child {
               background: rgba(255,255,255,0.025) !important;
               background-color: rgba(255,255,255,0.025) !important;
            }

            #mob-content :is(.table, .custom-table, #mainTable, #mainCategoriesTable, #modulesTable, #mainFeedbackTable, #mainSessionsTable, .game-table) :is(a, button, input, select, textarea):is(:hover, :active, :focus) {
               box-shadow: none;
            }

            #mob-content #sec-admin-game .game-categories-scroll {
               display: none !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child {
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
               gap: 8px !important;
               align-items: stretch !important;
               justify-content: stretch !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child::before {
               display: none !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child > .d-flex {
               display: contents !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child form {
               display: flex !important;
               width: 100%;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child :is(a, button) {
               min-height: 38px;
               border-radius: 10px !important;
               border-width: 1px !important;
               border-style: solid !important;
               background: rgba(255,255,255,0.025) !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child .btn-outline-secondary {
               border-color: rgba(15, 23, 42, 0.45) !important;
               color: var(--tx) !important;
            }

            #mob-content #sec-admin-game .game-table tbody td:last-child .btn-outline-danger {
               border-color: #f43f5e !important;
               color: #f43f5e !important;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers, #sec-admin-settings) > :is(.d-flex.justify-content-between, .d-flex.flex-column.flex-md-row, .mb-4.d-flex) {
               flex-direction: column !important;
               align-items: center !important;
               justify-content: center !important;
               gap: 12px !important;
               text-align: center !important;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers, #sec-admin-settings) > :is(.d-flex.justify-content-between, .d-flex.flex-column.flex-md-row, .mb-4.d-flex) h4 {
               max-width: 22rem;
               margin-inline: auto !important;
               font-size: clamp(1.08rem, 5.2vw, 1.28rem) !important;
               line-height: 1.16 !important;
               letter-spacing: 0 !important;
               text-wrap: balance;
            }

            #mob-content #sec-admin-archive .archive-page-title {
               display: inline-flex !important;
               width: auto !important;
               max-width: 100% !important;
               align-items: center !important;
               justify-content: center !important;
               gap: 6px !important;
               white-space: nowrap !important;
               text-wrap: nowrap !important;
               font-size: clamp(.92rem, 4.4vw, 1.12rem) !important;
            }

            #mob-content #sec-admin-archive .archive-page-title i {
               margin-right: 0 !important;
               flex: 0 0 auto;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers, #sec-admin-settings) > :is(.d-flex.justify-content-between, .d-flex.flex-column.flex-md-row, .mb-4.d-flex) p {
               max-width: 23rem;
               margin-inline: auto !important;
               font-size: 0.8rem !important;
               line-height: 1.4 !important;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers, #sec-admin-settings) > :is(.d-flex.justify-content-between, .d-flex.flex-column.flex-md-row, .mb-4.d-flex) :is(a, button) {
               min-height: 42px;
               border-radius: 11px !important;
               display: inline-flex;
               align-items: center;
               justify-content: center;
               gap: 7px;
               font-size: 0.8rem !important;
               font-weight: 700;
            }

            #mob-content #sec-admin-archive > .d-flex:first-of-type a {
               width: fit-content;
               min-height: 34px;
               padding: 6px 10px;
               border: 1px solid var(--bd);
               background: var(--sf);
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers) :is(.premium-card, .card) {
               border-radius: 14px !important;
               padding: 14px !important;
               border: 1px solid var(--bd) !important;
               box-shadow: 0 10px 26px rgba(2, 6, 23, 0.1) !important;
            }

            #mob-content :is(#sec-admin-notifications, #sec-admin-ai-providers) > .row.g-3 {
               display: grid;
               grid-template-columns: repeat(2, minmax(0, 1fr));
               gap: 10px !important;
            }

            #mob-content :is(#sec-admin-notifications, #sec-admin-ai-providers) > .row.g-3 > [class*="col-"] {
               width: 100% !important;
               padding: 0 !important;
            }

            #mob-content :is(#sec-admin-notifications, #sec-admin-ai-providers) > .row.g-3 .premium-card {
               min-height: 104px;
               padding: 14px 10px !important;
            }

            #mob-content :is(#sec-admin-notifications, #sec-admin-ai-providers) > .row.g-3 .premium-card div[style*="font-size:2rem"],
            #mob-content :is(#sec-admin-notifications, #sec-admin-ai-providers) > .row.g-3 .premium-card div[style*="font-size:1.5rem"] {
               font-size: 1.25rem !important;
               line-height: 1.1;
               overflow-wrap: anywhere;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers) :is(.table, .custom-table) tbody tr {
               border-radius: 14px !important;
               padding: 13px !important;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers) :is(.table, .custom-table) tbody td:last-child {
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr));
               gap: 8px !important;
               align-items: stretch !important;
               justify-content: stretch !important;
               background: rgba(255,255,255,0.025) !important;
               border-radius: 10px;
               margin-top: 4px;
               padding: 10px !important;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers) :is(.table, .custom-table) tbody td:last-child::before {
               display: none !important;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers) :is(.table, .custom-table) tbody td:last-child > .d-flex {
               display: contents !important;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers) :is(.table, .custom-table) tbody td:last-child form {
               display: flex !important;
               width: 100%;
               margin: 0 !important;
            }

            #mob-content :is(#sec-admin-archive, #sec-admin-contacts, #sec-admin-notifications, #sec-admin-ai-providers) :is(.table, .custom-table) tbody td:last-child :is(a, button) {
               width: 100%;
               min-height: 38px;
               border-radius: 10px !important;
               border-width: 1px !important;
               border-style: solid !important;
               display: inline-flex;
               align-items: center;
               justify-content: center;
               gap: 6px;
               padding: 7px 9px !important;
               font-size: 0.72rem !important;
               font-weight: 700;
            }

            #mob-content #sec-admin-notifications .custom-table tbody td:last-child {
               grid-template-columns: 1fr !important;
            }

            #mob-content #sec-admin-contacts .table tbody td:nth-child(2) {
               align-items: center !important;
               justify-content: center !important;
               text-align: center !important;
            }

            #mob-content #sec-admin-contacts .table tbody td:nth-child(2) .d-flex {
               justify-content: center !important;
               text-align: left;
            }

            #mob-content #sec-admin-settings .settings-grid {
               display: grid;
               grid-template-columns: repeat(4, minmax(0, 1fr));
               gap: 8px !important;
               margin: 0 !important;
            }

            #mob-content #sec-admin-settings .settings-grid > [class*="col-"] {
               width: 100% !important;
               padding: 0 !important;
            }

            #mob-content #sec-admin-settings .settings-grid button[data-bs-toggle="modal"] {
               min-height: 88px !important;
               border-radius: 12px !important;
               padding: 9px 5px !important;
               box-shadow: 0 10px 24px rgba(2, 6, 23, 0.08) !important;
            }

            #mob-content #sec-admin-settings .settings-grid button[data-bs-toggle="modal"] > div:nth-child(2) {
               width: 30px !important;
               height: 30px !important;
               border-radius: 9px !important;
               margin-bottom: 6px !important;
            }

            #mob-content #sec-admin-settings .settings-grid button[data-bs-toggle="modal"] i {
               font-size: 0.86rem !important;
            }

            #mob-content #sec-admin-settings .settings-grid button[data-bs-toggle="modal"] .fw-bold {
               font-size: 0.62rem !important;
               line-height: 1.08 !important;
               margin-bottom: 2px !important;
               overflow-wrap: anywhere;
            }

            #mob-content #sec-admin-settings .settings-grid button[data-bs-toggle="modal"] span:last-child {
               font-size: 0.42rem !important;
               line-height: 1 !important;
               letter-spacing: 0.06em !important;
            }

            #mob-content #sec-admin-settings .modal-dialog {
               width: min(92vw, 420px) !important;
               max-width: min(92vw, 420px) !important;
               min-height: calc(100dvh - var(--mob-top-h) - var(--mob-nav-h) - var(--mob-safe-top) - var(--mob-safe-bottom) - 20px) !important;
               margin: 10px auto !important;
               display: flex !important;
               align-items: center !important;
            }

            #mob-content #sec-admin-settings .modal-content {
               max-height: min(76dvh, 640px) !important;
               overflow: hidden !important;
               border-radius: 18px !important;
               background: var(--sf) !important;
               border: 1px solid var(--bd) !important;
               color: var(--tx) !important;
               box-shadow: 0 24px 70px rgba(2, 6, 23, 0.32) !important;
            }

            #mob-content #sec-admin-settings .modal-header,
            #mob-content #sec-admin-settings .modal-footer {
               flex: 0 0 auto;
               background: var(--sf) !important;
               border-color: var(--bd) !important;
            }

            #mob-content #sec-admin-settings .modal-title,
            #mob-content #sec-admin-settings .modal-content h1,
            #mob-content #sec-admin-settings .modal-content h2,
            #mob-content #sec-admin-settings .modal-content h3,
            #mob-content #sec-admin-settings .modal-content h4,
            #mob-content #sec-admin-settings .modal-content h5,
            #mob-content #sec-admin-settings .modal-content h6,
            #mob-content #sec-admin-settings .modal-content label,
            #mob-content #sec-admin-settings .modal-content p,
            #mob-content #sec-admin-settings .modal-content span,
            #mob-content #sec-admin-settings .modal-content li {
               color: var(--tx) !important;
               -webkit-text-fill-color: var(--tx) !important;
            }

            #mob-content #sec-admin-settings .modal-content small,
            #mob-content #sec-admin-settings .modal-content .text-muted {
               color: var(--tx3) !important;
               -webkit-text-fill-color: var(--tx3) !important;
            }

            #mob-content #sec-admin-settings .modal-body {
               max-height: calc(min(76dvh, 640px) - 118px) !important;
               padding: 14px !important;
               overflow-y: auto !important;
               background: var(--sf) !important;
               color: var(--tx) !important;
            }

            #mob-content #sec-admin-settings .modal-content :is(.form-control, .form-select, .oinp, input, select, textarea) {
               background: var(--bg3) !important;
               border: 1px solid var(--bd) !important;
               color: var(--tx) !important;
               -webkit-text-fill-color: var(--tx) !important;
            }

            #mob-content #sec-admin-settings .modal-content :is(.form-control, .form-select, .oinp, input, select, textarea)::placeholder {
               color: var(--tx3) !important;
               -webkit-text-fill-color: var(--tx3) !important;
               font-weight: 400 !important;
               opacity: 1;
            }

            #mob-content #sec-admin-settings .modal-content option {
               background: var(--bg3) !important;
               color: var(--tx) !important;
            }

            #mob-content #sec-admin-settings .btn-save-fixed {
               bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 10px) !important;
               padding: 12px !important;
               border-radius: 14px !important;
               flex-direction: column;
               gap: 10px;
               text-align: center;
            }

            #mob-content #sec-admin-settings > form > .row > .modal-footer,
            #mob-content #sec-admin-settings > form .settings-grid + .modal-footer {
               position: static !important;
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
               gap: 8px !important;
               width: 100% !important;
               padding: 12px 0 0 !important;
               margin: 0 !important;
               background: transparent !important;
               border: 0 !important;
            }

            #mob-content #sec-admin-settings > form > .row > .modal-footer :is(a, button),
            #mob-content #sec-admin-settings > form .settings-grid + .modal-footer :is(a, button) {
               width: 100% !important;
               min-height: 42px !important;
               margin: 0 !important;
               border-radius: 11px !important;
               display: inline-flex !important;
               align-items: center !important;
               justify-content: center !important;
               gap: 6px !important;
               font-size: 0.78rem !important;
               font-weight: 800 !important;
            }

            #mob-content #sec-admin-settings > form > .row > .modal-footer [data-bs-dismiss="modal"],
            #mob-content #sec-admin-settings > form .settings-grid + .modal-footer [data-bs-dismiss="modal"] {
               border: 1px solid var(--bd) !important;
               background: var(--bg3) !important;
               color: var(--tx) !important;
               -webkit-text-fill-color: var(--tx) !important;
            }

            #mob-content #sec-admin-settings .modal[id^="modal-"] {
               padding: calc(var(--mob-top-h) + var(--mob-safe-top) + 10px) 12px calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 12px) !important;
            }

            #mob-content #sec-admin-settings .modal[id^="modal-"].show {
               display: flex !important;
               align-items: center !important;
               justify-content: center !important;
            }

            #mob-content #sec-admin-settings .modal[id^="modal-"] .modal-dialog {
               width: min(90vw, 390px) !important;
               max-width: min(90vw, 390px) !important;
               min-height: 0 !important;
               max-height: none !important;
               margin: auto !important;
               display: block !important;
               transform: none !important;
            }

            #mob-content #sec-admin-settings .modal[id^="modal-"] .modal-content {
               width: 100% !important;
               max-height: min(64dvh, 540px) !important;
               border-radius: 18px !important;
               overflow: hidden !important;
               background: var(--sf) !important;
               border: 1px solid var(--bd) !important;
               box-shadow: 0 24px 70px rgba(2, 6, 23, 0.34) !important;
            }

            #mob-content #sec-admin-settings .modal[id^="modal-"] .modal-header {
               padding: 14px 16px 8px !important;
            }

            #mob-content #sec-admin-settings .modal[id^="modal-"] .modal-body {
               max-height: calc(min(64dvh, 540px) - 112px) !important;
               overflow-y: auto !important;
               padding: 8px 16px 14px !important;
            }

            #mob-content #sec-admin-settings .modal[id^="modal-"] .modal-footer {
               padding: 10px 16px 14px !important;
            }
         }
      </style>

      <style>
         @media (max-width: 767px) {
            body.admin-mobile-shell .modal.show {
               display: flex !important;
               align-items: center !important;
               justify-content: center !important;
               padding: calc(var(--mob-safe-top, 0px) + 12px) 12px calc(var(--mob-nav-h, 64px) + var(--mob-safe-bottom, 0px) + 14px) !important;
            }

            body.admin-mobile-shell .modal .modal-dialog,
            body.admin-mobile-shell .modal .modal-dialog.modal-sm,
            body.admin-mobile-shell .modal .modal-dialog.modal-lg,
            body.admin-mobile-shell .modal .modal-dialog.modal-xl,
            body.admin-mobile-shell .modal .modal-dialog.modal-dialog-centered,
            body.admin-mobile-shell .modal .modal-dialog.modal-dialog-scrollable {
               width: min(calc(100vw - 24px), 440px) !important;
               max-width: min(calc(100vw - 24px), 440px) !important;
               min-height: 0 !important;
               height: auto !important;
               max-height: none !important;
               margin: auto !important;
               display: block !important;
               transform: none !important;
            }

            body.admin-mobile-shell .modal .modal-dialog.modal-lg,
            body.admin-mobile-shell .modal .modal-dialog.modal-xl {
               width: min(calc(100vw - 24px), 520px) !important;
               max-width: min(calc(100vw - 24px), 520px) !important;
            }

            body.admin-mobile-shell .modal .modal-content {
               width: 100% !important;
               height: auto !important;
               min-height: 0 !important;
               max-height: min(82dvh, 720px) !important;
               overflow: hidden !important;
               background: var(--sf) !important;
               color: var(--tx) !important;
               border: 1px solid var(--bd) !important;
               border-radius: 16px !important;
               box-shadow: 0 24px 70px rgba(0, 0, 0, .32) !important;
            }

            body.admin-mobile-shell .modal .modal-header,
            body.admin-mobile-shell .modal .modal-footer {
               flex: 0 0 auto !important;
               background: var(--sf) !important;
               color: var(--tx) !important;
               border-color: var(--bd) !important;
               padding-inline: 16px !important;
            }

            body.admin-mobile-shell .modal .modal-body {
               flex: 1 1 auto !important;
               min-height: 0 !important;
               max-height: calc(min(82dvh, 720px) - 124px) !important;
               overflow-y: auto !important;
               overflow-x: hidden !important;
               background: var(--sf) !important;
               color: var(--tx) !important;
               padding: 16px !important;
            }

            body.admin-mobile-shell .modal :is(h1, h2, h3, h4, h5, h6, label, .form-label, p, small, li, div, td, th):not(.badge):not([class*="text-"]) {
               color: var(--tx) !important;
               -webkit-text-fill-color: var(--tx) !important;
            }

            body.admin-mobile-shell .modal :is(.text-muted, .text-secondary) {
               color: var(--tx2) !important;
               -webkit-text-fill-color: var(--tx2) !important;
            }

            body.admin-mobile-shell .modal :is(.form-control, .form-select, .oinp, input, select, textarea) {
               width: 100%;
               min-width: 0;
               max-width: 100%;
               min-height: 42px;
               background: var(--bg3) !important;
               color: var(--tx) !important;
               -webkit-text-fill-color: var(--tx) !important;
               border: 1px solid var(--bd) !important;
               border-radius: 10px !important;
            }

            body.admin-mobile-shell .modal textarea {
               min-height: 96px;
            }

            body.admin-mobile-shell .modal :is(.form-control, .form-select, .oinp, input, select, textarea)::placeholder {
               color: var(--tx3) !important;
               -webkit-text-fill-color: var(--tx3) !important;
               font-weight: 400 !important;
            }

            body.admin-mobile-shell .modal option {
               color: var(--tx) !important;
               background: var(--bg3) !important;
            }

            body.admin-mobile-shell .modal .row {
               margin-left: -6px !important;
               margin-right: -6px !important;
            }

            body.admin-mobile-shell .modal .row > [class*="col-"] {
               min-width: 0;
               padding-left: 6px !important;
               padding-right: 6px !important;
            }

            body.admin-mobile-shell .modal .mb-3 {
               margin-bottom: 12px !important;
            }

            body.admin-mobile-shell .modal .premium-card,
            body.admin-mobile-shell .modal .card,
            body.admin-mobile-shell .modal .list-group-item,
            body.admin-mobile-shell .modal .custom-switch-container {
               max-width: 100%;
               overflow: hidden;
               background: var(--bg3) !important;
               color: var(--tx) !important;
               border: 1px solid var(--bd) !important;
               border-radius: 12px !important;
            }

            body.admin-mobile-shell .modal :is(.table-responsive, .table-responsive-sm, .table-responsive-md, .table-responsive-lg, .table-responsive-xl) {
               max-width: 100%;
               overflow-x: visible !important;
            }

            body.admin-mobile-shell .modal table {
               width: 100% !important;
               table-layout: fixed;
            }

            body.admin-mobile-shell .modal table :is(td, th) {
               min-width: 0;
               max-width: 100%;
               overflow-wrap: anywhere;
               word-break: break-word;
               white-space: normal !important;
            }

            body.admin-mobile-shell .modal :is(a, span, strong, div, p, td, th):not(.badge) {
               overflow-wrap: anywhere;
               word-break: break-word;
            }

            body.admin-mobile-shell .modal .nav-tabs,
            body.admin-mobile-shell .modal .nav-pills {
               max-width: 100%;
               overflow-x: auto;
               flex-wrap: nowrap !important;
               -webkit-overflow-scrolling: touch;
            }

            body.admin-mobile-shell .modal .nav-tabs .nav-link,
            body.admin-mobile-shell .modal .nav-pills .nav-link {
               white-space: nowrap;
               font-size: .78rem !important;
               padding: 8px 10px !important;
            }

            body.admin-mobile-shell .modal .modal-footer {
               display: grid !important;
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
               gap: 8px !important;
               padding: 12px 16px 16px !important;
            }

            body.admin-mobile-shell .modal .modal-footer .btn,
            body.admin-mobile-shell .modal .modal-footer button {
               width: 100% !important;
               min-width: 0 !important;
               min-height: 42px !important;
               margin: 0 !important;
               border-radius: 10px !important;
               display: inline-flex !important;
               align-items: center !important;
               justify-content: center !important;
               gap: 6px !important;
               white-space: nowrap;
               font-size: .8rem !important;
               font-weight: 800 !important;
            }

            body.admin-mobile-shell .modal .modal-footer .btn:only-child,
            body.admin-mobile-shell .modal .modal-footer button:only-child {
               grid-column: 1 / -1;
            }

            body.admin-mobile-shell .modal .modal-footer [data-bs-dismiss="modal"],
            body.admin-mobile-shell .modal .modal-footer .btn-secondary,
            body.admin-mobile-shell .modal .modal-footer .btn-outline-secondary {
               border: 1px solid var(--bd) !important;
               background: var(--bg3) !important;
               color: var(--tx) !important;
               -webkit-text-fill-color: var(--tx) !important;
            }

            body.admin-mobile-shell .modal .btn-close {
               opacity: .9;
               filter: invert(1);
            }

            html.lm body.admin-mobile-shell .modal .btn-close {
               filter: none;
            }

            body.admin-mobile-shell #mob-content {
               --admin-mobile-card-width: 100%;
            }

            body.admin-mobile-shell #mob-content .db-content > *,
            body.admin-mobile-shell #mob-content .db-section > *,
            body.admin-mobile-shell #mob-content :is(
               .premium-card,
               .premium-panel,
               .setup-panel,
               .panel,
               .db-stat-card,
               .ll-stat-card,
               .ll-module-card,
               .module-card,
               .perk-card,
               .level-card,
               .print-card,
               .sr-card,
               .sr-stat-card,
               .tracker-panel,
               .chapter-card,
               .mod-hero,
               .ll-category-list,
               .retry-panel,
               .stat-box,
               .category-filter-mobile,
               .custom-switch-container,
               .list-group,
               .list-group-item,
               .alert,
               .card
            ),
            body.admin-mobile-shell #mob-content :is(
               .custom-table,
               #mainTable,
               #mainUsersTable,
               #mainSessionsTable,
               #mainArchiveTable,
               #mainAuditLogsTable,
               #mainFeedbackTable,
               #mainCategoriesTable,
               #modulesTable,
               #mainProvidersTable,
               #moduleUsageTable,
               .game-table
            ) tbody tr,
            body.admin-mobile-shell #mob-content .settings-grid button[data-bs-toggle="modal"] {
               width: var(--admin-mobile-card-width) !important;
               max-width: var(--admin-mobile-card-width) !important;
               min-width: 0 !important;
               margin-left: 0 !important;
               margin-right: 0 !important;
               box-sizing: border-box !important;
            }

            body.admin-mobile-shell #mob-content :is(.row, .container, .container-fluid, .table-responsive) {
               width: 100% !important;
               max-width: 100% !important;
               min-width: 0 !important;
               margin-left: 0 !important;
               margin-right: 0 !important;
               box-sizing: border-box !important;
            }

            body.admin-mobile-shell #mob-content > .db-content > :is(.db-section, .container-fluid) > :is(.d-flex.justify-content-between.mb-4, .mb-4.d-flex.justify-content-between) > div:first-child :is(h1, h2, h3, h4),
            body.admin-mobile-shell #mob-content :is(.feedback-page-title, .session-page-title, .archive-page-title, .admin-dashboard-title) {
               color: var(--sr-page-title-accent, #ffffff) !important;
               -webkit-text-fill-color: var(--sr-page-title-accent, #ffffff) !important;
               text-transform: uppercase !important;
               text-shadow: 0 2px 12px rgba(15, 23, 42, 0.18) !important;
            }
         }
      </style>

      <!-- ===== ADMIN BOTTOM NAVIGATION ===== -->
      <nav id="mob-bottom-nav" aria-label="Admin navigation">
         <div class="mob-nav-items">
            <a href="{{ route('admin.dashboard') }}"
               class="mob-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
               <i class="fa-solid fa-gauge-high"></i>
               <span>Admin</span>
            </a>
            <a href="{{ route('admin.users.index') }}"
               class="mob-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
               <i class="fa-solid fa-users"></i>
               <span>Users</span>
            </a>
            <a href="{{ route('admin.sessions.index') }}"
               class="mob-nav-item {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}">
               <i class="fa-solid fa-video"></i>
               <span>Sessions</span>
            </a>
            <a href="{{ route('admin.feedback.index') }}"
               class="mob-nav-item {{ request()->routeIs('admin.feedback.*') ? 'active' : '' }}">
               <i class="fa-solid fa-clipboard-check"></i>
               <span>Audit</span>
            </a>
            <button class="mob-nav-item {{ request()->routeIs('admin.account', 'admin.categories', 'admin.questions', 'admin.modules*', 'admin.game*', 'admin.sessions.archive', 'admin.contacts.*', 'admin.ai.*', 'admin.settings.*') ? 'active' : '' }}"
                    id="mobnav-more"
                    type="button"
                    aria-controls="mobMoreDropdown"
                    aria-expanded="false"
                    aria-label="Open more menu"
                    onclick="toggleMobileProfile(event, 'pages')">
               <i class="fa-solid fa-ellipsis"></i>
               <span>More</span>
            </button>
         </div>
      </nav>

      <div id="mobMoreBackdrop" class="mob-more-backdrop" aria-hidden="true" onclick="closeMobileProfile()"></div>

      <div class="mob-profile-dropdown mob-profile-account-dropdown mob-profile-is-account" id="mobProfileDropdown" aria-hidden="true" data-mode="account" data-origin="top">
         <div class="mob-profile-head mob-profile-account-head">
            <div class="mob-profile-head-avatar">
               @if(Auth::check() && Auth::user()->profile_photo_path)
                  @php
                      $photoPath = Auth::user()->profile_photo_path;
                      $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                  @endphp
                  <img src="{{ $photoUrl }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
               @else
                  {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'A' }}
               @endif
            </div>
            <div class="mob-profile-head-meta">
               <div class="mob-profile-name">{{ Auth::user()->name ?? 'Admin' }}</div>
               <div class="mob-profile-role">Administrator</div>
            </div>
            <button class="mob-profile-close" type="button" onclick="event.stopPropagation(); closeMobileProfile();" aria-label="Close admin menu"><i class="fa-solid fa-xmark"></i></button>
         </div>
         <div class="mob-profile-menu" id="mobProfileMenu">
            <div class="mob-profile-account">
               <div class="mob-profile-section-title">Profile</div>
               <div class="mob-profile-grid mb-2">
                  <a href="{{ route('admin.account') }}" class="mob-profile-link profile-nav-slate {{ request()->routeIs('admin.account') ? 'active' : '' }}"><i class="fa-solid fa-user-shield"></i><span>Account</span></a>
                  <a href="{{ route('admin.settings.index') }}" class="mob-profile-link profile-nav-blue {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
               </div>
               <form action="{{ route('logout') }}" method="POST">
                  @csrf
                  <button type="submit" class="mob-profile-action danger"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></button>
               </form>
            </div>
         </div>
      </div>

      <div class="mob-profile-dropdown mob-more-dropdown mob-profile-is-pages" id="mobMoreDropdown" aria-hidden="true" data-mode="pages" data-origin="bottom">
         <div class="mob-profile-menu" id="mobMoreMenu">
            <div class="mob-profile-pages">
               <div class="mob-profile-pages-close mob-profile-pages-head">
                  <span>More</span>
                  <button class="mob-profile-close mob-profile-pages-close-btn" type="button" onclick="event.stopPropagation(); closeMobileProfile();" aria-label="Close more menu"><i class="fa-solid fa-xmark"></i></button>
               </div>
               <div class="mob-profile-section-title">Interview Modules</div>
               <div class="mob-profile-grid">
                  <a href="{{ route('admin.dashboard') }}" data-mobile-more-link="dashboard" class="mob-profile-link profile-nav-blue {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
                   <a href="{{ route('admin.categories') }}" data-mobile-more-link="categories" class="mob-profile-link profile-nav-cyan {{ request()->routeIs('admin.categories') ? 'active' : '' }}"><i class="fa-solid fa-list"></i><span>Categories</span></a>
                   <a href="{{ route('admin.questions') }}" data-mobile-more-link="questions" class="mob-profile-link profile-nav-amber {{ request()->routeIs('admin.questions') ? 'active' : '' }}"><i class="fa-solid fa-circle-question"></i><span>Questions</span></a>
                   <a href="{{ route('admin.modules') }}" data-mobile-more-link="lessons" class="mob-profile-link profile-nav-emerald {{ request()->routeIs('admin.modules') || request()->routeIs('admin.modules.*') ? 'active' : '' }}"><i class="fa-solid fa-book-open"></i><span>Lessons</span></a>
                  <a href="{{ route('admin.game') }}" data-mobile-more-link="games" class="mob-profile-link profile-nav-rose {{ request()->routeIs('admin.game') || request()->routeIs('admin.game.*') ? 'active' : '' }}"><i class="fa-solid fa-gamepad"></i><span>Games</span></a>
               </div>

               <div class="mob-profile-section-title">Interview Monitoring</div>
               <div class="mob-profile-grid">
                  <a href="{{ route('admin.sessions.archive') }}" data-mobile-more-link="archive" class="mob-profile-link profile-nav-slate {{ request()->routeIs('admin.sessions.archive') ? 'active' : '' }}"><i class="fa-solid fa-box-archive"></i><span>Archive</span></a>
                  <a href="{{ route('admin.contacts.index') }}" data-mobile-more-link="contacts" class="mob-profile-link profile-nav-cyan {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}"><i class="fa-solid fa-envelope"></i><span>Contacts</span></a>
                  <a href="{{ route('admin.notifications.index') }}" data-mobile-more-link="notifications" class="mob-profile-link profile-nav-amber {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}"><i class="fa-solid fa-bell"></i><span>Notifications</span></a>
               </div>

               <div class="mob-profile-section-title">System</div>
               <div class="mob-profile-grid mb-2">
                  <a href="{{ route('admin.ai.providers') }}" data-mobile-more-link="ai-providers" class="mob-profile-link profile-nav-purple {{ request()->routeIs('admin.ai.providers*') || request()->routeIs('admin.ai.evaluation*') ? 'active' : '' }}"><i class="fa-solid fa-microchip"></i><span>AI Providers</span></a>
                  <a href="{{ route('admin.settings.index') }}" data-mobile-more-link="settings" class="mob-profile-link profile-nav-blue {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
               </div>
            </div>
         </div>
      </div>

      <!-- ===== EXIT APP CONFIRMATION MODAL ===== -->
      <div id="exitAppModal" style="display:none; position:fixed; inset:0; z-index:1060; background:rgba(0,0,0,0.6); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); align-items:center; justify-content:center; animation: mobFadeIn 0.2s ease;">
         <div style="background:var(--bg2); width:85%; max-width:320px; border-radius:20px; padding:24px; text-align:center; box-shadow:0 10px 40px rgba(0,0,0,0.5); border:1px solid var(--bd);">
            <i class="fa-solid fa-person-walking-arrow-right mb-3" style="font-size:2.5rem; color:#f87171;"></i>
            <h4 style="color:var(--tx); font-weight:700; margin-bottom:10px; font-size:1.25rem;">Exit App?</h4>
            <p style="color:var(--tx3); font-size:0.9rem; margin-bottom:24px;">Are you sure you want to exit SpeakReady AI?</p>
            <div class="d-flex gap-3">
               <button class="btn w-100" style="border-radius:12px; font-weight:600; background:transparent; border:1px solid var(--bd2); color:var(--tx2);" onclick="confirmExitApp('no')">No</button>
               <button class="btn btn-danger w-100" style="border-radius:12px; font-weight:600; background:#f87171; border:none; color:#fff;" onclick="confirmExitApp('yes')">Yes, Exit</button>
            </div>
         </div>
      </div>

      <!-- ===== PWA INSTALL PROMPT ===== -->
      <div id="pwa-install-prompt" role="dialog" aria-labelledby="pwaPromptTitle" aria-describedby="pwaPromptCopy">
         <div class="pwa-install-dialog">
            <h5 id="pwaPromptTitle">Install SpeakReady AI</h5>
            <p id="pwaPromptCopy">Do you want to install this app for a better and faster experience?</p>
            <div class="pwa-btn-wrap">
               <button id="pwa-btn-no" class="pwa-btn-no">No</button>
               <button id="pwa-btn-yes" class="pwa-btn-yes">Yes</button>
            </div>
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
      <script src="{{ asset('js/user-ui.js') }}?v=21" defer></script>

      <script>
         function getMobileProfileDropdown(mode = 'pages') {
            return document.getElementById(mode === 'pages' ? 'mobMoreDropdown' : 'mobProfileDropdown');
         }

         function toggleMobileProfile(e, mode = 'pages') {
            if (e) e.stopPropagation();
            const dropdown = getMobileProfileDropdown(mode);
            const otherDropdown = getMobileProfileDropdown(mode === 'pages' ? 'account' : 'pages');
            const profileButton = document.getElementById('mobProfileBtn');
            const bottomButton = document.getElementById('mobnav-more');
            const moreBackdrop = document.getElementById('mobMoreBackdrop');
            if (!dropdown) return;

            const currentMode = dropdown.getAttribute('data-mode') || 'pages';
            const isOpen = dropdown.classList.contains('open');
            hideMobileNotificationDropdown();
            syncMobileProfileMode(mode, dropdown);
            if (otherDropdown && otherDropdown !== dropdown) {
               otherDropdown.classList.remove('open');
               otherDropdown.setAttribute('aria-hidden', 'true');
            }

            const willOpen = !isOpen || currentMode !== mode;
            dropdown.classList.toggle('open', willOpen);
            dropdown.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
            if (profileButton) profileButton.setAttribute('aria-expanded', willOpen && mode === 'account' ? 'true' : 'false');
            if (bottomButton) bottomButton.setAttribute('aria-expanded', willOpen && mode === 'pages' ? 'true' : 'false');
            if (moreBackdrop) moreBackdrop.classList.toggle('open', willOpen);
            if (willOpen) resetMobileProfileMenuScroll();
         }

         function syncMobileProfileMode(mode = 'pages', dropdown = null) {
            dropdown = dropdown || getMobileProfileDropdown(mode);
            if (!dropdown) return;

            const isAccountMode = mode === 'account';
            const accountHead = dropdown.querySelector('.mob-profile-account-head');
            const accountMenu = dropdown.querySelector('.mob-profile-account');
            const pagesMenu = dropdown.querySelector('.mob-profile-pages');
            const pagesHead = dropdown.querySelector('.mob-profile-pages-head');

            dropdown.setAttribute('data-mode', isAccountMode ? 'account' : 'pages');
            dropdown.setAttribute('data-origin', isAccountMode ? 'top' : 'bottom');
            dropdown.classList.toggle('mob-profile-is-account', isAccountMode);
            dropdown.classList.toggle('mob-profile-is-pages', !isAccountMode);

            if (accountHead) {
               accountHead.hidden = !isAccountMode;
               accountHead.style.display = isAccountMode ? 'flex' : 'none';
            }
            if (accountMenu) {
               accountMenu.hidden = !isAccountMode;
               accountMenu.style.display = isAccountMode ? 'block' : 'none';
            }
            if (pagesMenu) {
               pagesMenu.hidden = isAccountMode;
               pagesMenu.style.display = isAccountMode ? 'none' : 'block';
            }
            if (pagesHead) {
               pagesHead.hidden = isAccountMode;
               pagesHead.style.display = isAccountMode ? 'none' : 'flex';
            }
         }

         function hideMobileNotificationDropdown() {
            const notificationButton = document.getElementById('mobNotificationBtn');
            if (!notificationButton || typeof bootstrap === 'undefined' || !bootstrap.Dropdown) return;
            const dropdown = bootstrap.Dropdown.getInstance(notificationButton) || bootstrap.Dropdown.getOrCreateInstance(notificationButton);
            dropdown.hide();
         }

         function resetMobileProfileMenuScroll() {
            const menus = [
               document.getElementById('mobProfileMenu'),
               document.getElementById('mobMoreMenu')
            ].filter(Boolean);
            if (!menus.length) return;
            const reset = () => {
               menus.forEach(function(menu) {
                  menu.scrollTop = 0;
                  menu.scrollTo({ top: 0, left: 0, behavior: 'auto' });
               });
            };
            reset();
            requestAnimationFrame(reset);
            setTimeout(reset, 80);
         }

         function closeMobileProfile() {
            const dropdowns = [
               document.getElementById('mobProfileDropdown'),
               document.getElementById('mobMoreDropdown')
            ].filter(Boolean);
            const profileButton = document.getElementById('mobProfileBtn');
            const bottomButton = document.getElementById('mobnav-more');
            const moreBackdrop = document.getElementById('mobMoreBackdrop');
            dropdowns.forEach(function(dropdown) {
               dropdown.classList.remove('open');
               dropdown.setAttribute('aria-hidden', 'true');
            });
            if (profileButton) profileButton.setAttribute('aria-expanded', 'false');
            if (bottomButton) bottomButton.setAttribute('aria-expanded', 'false');
            if (moreBackdrop) moreBackdrop.classList.remove('open');
         }

         document.addEventListener('click', function(e) {
            const profileDropdown = document.getElementById('mobProfileDropdown');
            const moreDropdown = document.getElementById('mobMoreDropdown');
            const profileButton = document.getElementById('mobProfileBtn');
            const moreButton = document.getElementById('mobnav-more');
            const clickedOutsideProfile = !profileDropdown?.contains(e.target);
            const clickedOutsideMore = !moreDropdown?.contains(e.target);
            const anyDropdownOpen = profileDropdown?.classList.contains('open') || moreDropdown?.classList.contains('open');
            if (anyDropdownOpen && clickedOutsideProfile && clickedOutsideMore && !moreButton?.contains(e.target) && !profileButton?.contains(e.target)) {
               closeMobileProfile();
            }
         });

         document.addEventListener('DOMContentLoaded', function() {
            syncMobileProfileMode('account');
            syncMobileProfileMode('pages');

            document.querySelectorAll('[data-admin-notif-close]').forEach(function(button) {
               button.addEventListener('click', function(event) {
                  event.preventDefault();
                  event.stopPropagation();
                  hideMobileNotificationDropdown();
               });
            });

            const notificationButton = document.getElementById('mobNotificationBtn');
            if (notificationButton) {
               notificationButton.addEventListener('show.bs.dropdown', closeMobileProfile);
            }

            const profileButton = document.getElementById('mobProfileBtn');
            if (profileButton) {
               profileButton.addEventListener('keydown', function(event) {
                  if (event.key === 'Enter' || event.key === ' ') {
                     event.preventDefault();
                     toggleMobileProfile(event, 'account');
                  }
               });
            }
         });

         // Exit App Confirmation Logic for Physical Back Button
         let allowAppExit = false;
         window.addEventListener('load', function() {
            if (window.location.pathname === '/admin/dashboard' || window.location.pathname === '/dashboard') {
               window.history.pushState('fake_exit_state', null, '');
               
               window.addEventListener('popstate', function(event) {
                  if (allowAppExit) return;
                  
                  const exitModal = document.getElementById('exitAppModal');
                  if (exitModal) {
                     exitModal.style.display = 'flex';
                  }
                  
                  // Trap the user again
                  window.history.pushState('fake_exit_state', null, '');
               });
            }
         });

         function confirmExitApp(choice) {
            const exitModal = document.getElementById('exitAppModal');
            if (exitModal) exitModal.style.display = 'none';
            
            if (choice === 'yes') {
               allowAppExit = true;
               window.history.go(-2);
               setTimeout(() => { window.close(); }, 300);
            }
         }

         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js?v=10')
                  .then(r => console.log('SW:', r.scope))
                  .catch(e => console.log('SW fail:', e));
            });
         }

         // PWA Install Prompt Logic
         let deferredPrompt;
         window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
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
      </script>

      <script>
          let adminLastSeenActivityId = localStorage.getItem('admin_last_seen_activity_id') || 0;
          let adminActivityNotificationsReady = false;

          function rememberLatestAdminActivity(activities) {
              if (!Array.isArray(activities) || activities.length === 0) return;

              const latestId = Math.max(...activities.map(activity => Number(activity.id) || 0));
              if (latestId > Number(adminLastSeenActivityId || 0)) {
                  adminLastSeenActivityId = latestId;
                  localStorage.setItem('admin_last_seen_activity_id', latestId);
              }
          }

          function requestAdminPwaNotificationPermission() {
              if (!('Notification' in window)) return;

              if (Notification.permission === 'default') {
                  Notification.requestPermission().catch(() => {});
              }
          }

          function showAdminPwaNotification(activity) {
              if (!activity || !('Notification' in window) || Notification.permission !== 'granted') return;

              const payload = {
                  type: 'SHOW_ADMIN_ACTIVITY_NOTIFICATION',
                  title: activity.title || 'Admin activity',
                  body: activity.body || 'A user activity was recorded.',
                  url: activity.url || '{{ route('admin.dashboard') }}',
                  tag: `admin-activity-${activity.id}`,
              };

              if (navigator.serviceWorker?.controller) {
                  navigator.serviceWorker.controller.postMessage(payload);
                  return;
              }

              navigator.serviceWorker?.ready
                  .then(registration => registration.active?.postMessage(payload))
                  .catch(() => {});
          }

          function notifyNewAdminAuthActivities(activities) {
              if (!Array.isArray(activities) || activities.length === 0) return;

              const newActivities = activities
                  .filter(activity => Number(activity.id) > Number(adminLastSeenActivityId || 0))
                  .sort((a, b) => Number(a.id) - Number(b.id));

              if (!adminActivityNotificationsReady) {
                  adminActivityNotificationsReady = true;
                  rememberLatestAdminActivity(activities);
                  return;
              }

              newActivities.forEach(showAdminPwaNotification);
              rememberLatestAdminActivity(activities);
          }

          function fetchAdminActivities() {
              fetch(`{{ route('admin.api.latest-activities') }}`)
                  .then(res => res.json())
                  .then(data => {
                      if (document.getElementById('admin-activity-list-mobile')) {
                          document.getElementById('admin-activity-list-mobile').innerHTML = data.html;
                      }
                      
                      const countEl = document.getElementById('admin-activity-count-mobile');
                      const badgeEl = document.getElementById('admin-activity-badge-mobile');
                      
                      if (data.new_count > 0) {
                          if(countEl) { countEl.style.display = 'inline-flex'; countEl.innerText = data.new_count + ' new'; }
                          if(badgeEl) { badgeEl.style.display = 'block'; }
                      } else {
                          if(countEl) { countEl.style.display = 'none'; }
                          if(badgeEl) { badgeEl.style.display = 'none'; }
                      }

                      notifyNewAdminAuthActivities(data.auth_activities);
                  })
                  .catch(err => console.error('Error fetching activities:', err));
          }

          function resetAdminActivityBadge(type) {
              const countEl = document.getElementById('admin-activity-count-mobile');
              const badgeEl = document.getElementById('admin-activity-badge-mobile');
              if(countEl) { countEl.style.display = 'none'; }
              if(badgeEl) { badgeEl.style.display = 'none'; }
              
              fetch(`/admin/api/activities/mark-all-read`, {
                  method: 'POST',
                  headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
              }).then(() => {
                  document.querySelectorAll('.admin-activity-item').forEach(el => {
                      if (el.style.background.includes('rgba')) {
                          el.style.background = 'transparent';
                      }
                  });
              });
          }

          function markActivityRead(id, event) {
              event.preventDefault();
              event.stopPropagation();
              fetch(`/admin/api/activities/${id}/mark-read`, {
                  method: 'POST',
                  headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
              }).then(() => fetchAdminActivities());
          }

          function deleteActivity(id, event) {
              event.preventDefault();
              event.stopPropagation();
              fetch(`/admin/api/activities/${id}`, {
                  method: 'DELETE',
                  headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
              }).then(() => fetchAdminActivities());
          }

          function markAllActivitiesRead(event) {
              event.preventDefault();
              event.stopPropagation();
              fetch(`/admin/api/activities/mark-all-read`, {
                  method: 'POST',
                  headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
              }).then(() => fetchAdminActivities());
          }

          function clearAllActivities(event) {
              event.preventDefault();
              event.stopPropagation();
              if(confirm('Are you sure you want to completely clear all activity logs? This cannot be undone.')) {
                  fetch(`/admin/api/activities/clear-all`, {
                      method: 'DELETE',
                      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
                  }).then(() => fetchAdminActivities());
              }
          }

          document.addEventListener('DOMContentLoaded', function() {
              requestAdminPwaNotificationPermission();
              fetchAdminActivities();
              setInterval(fetchAdminActivities, 15000);
          });
      </script>

      @stack('late-styles')
      @stack('scripts')
      @include('mobile.partials.page-transition')
      @include('mobile.layouts.logout-transition')
   </body>
</html>
