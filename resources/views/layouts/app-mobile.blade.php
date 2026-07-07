<!DOCTYPE html>
<html lang="en" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#08080f">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
      <title>SpeakReady AI - AI-Based Interview Practice System</title>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"/>
      <link href="{{ asset('css/aos.css') }}" rel="stylesheet"/>
      <link href="{{ asset('css/swiper-bundle.min.css') }}" rel="stylesheet"/>
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}"/>
      <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}"/>
      <link rel="stylesheet" href="{{ asset('css/style.css?v=6') }}" />
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
      <style>
         /* Global Mobile Responsiveness for Premium UI Updates */
         .premium-panel, .panel, .setup-panel {
             border-radius: 16px !important;
             padding: 16px !important;
         }
         .stat-card.premium-panel, .perk-card.premium-panel, .module-card.premium-panel, .print-card {
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
         .table-responsive {
             max-width: 100% !important;
             overflow-x: auto !important;
             margin-left: 0 !important;
             margin-right: 0 !important;
             -webkit-overflow-scrolling: touch !important;
         }
         canvas {
             max-width: 100% !important;
             height: auto !important;
         }
         .table {
             min-width: 100% !important;
         }
         
         /* ===== MOBILE LAYOUT SHELL ===== */
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
            background: rgba(8, 8, 15, 0.9);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--bd);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-left: 16px;
            padding-right: 16px;
         }
         .lm #mob-header { background: rgba(250, 250, 254, 0.94); }

         .mob-header-logo {
            display: flex; align-items: center; gap: 8px;
            font-size: 1rem; font-weight: 700;
            color: var(--tx); text-decoration: none;
         }
         .mob-header-logo img { width: 30px; height: 30px; border-radius: 8px; }

         .mob-header-right { display: flex; align-items: center; gap: 10px; }

         .mob-icon-btn {
            width: 36px; height: 36px;
            border-radius: 10px;
            border: 1px solid var(--bd2);
            background: transparent; color: var(--tx);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; cursor: pointer; transition: background-color 0.2s, transform 0.2s, color 0.2s;
            -webkit-tap-highlight-color: transparent;
         }
         .mob-icon-btn:active { background: rgba(139,92,246,0.15); transform: scale(0.92); }

         .mob-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: var(--grad);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 0.8rem; font-weight: 700;
            flex-shrink: 0; cursor: pointer;
            -webkit-tap-highlight-color: transparent;
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

         /* ---- Bottom Navigation Bar ---- */
         #mob-bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            z-index: 990;
            height: calc(var(--mob-nav-h) + var(--mob-safe-bottom));
            padding-bottom: var(--mob-safe-bottom);
            background: rgba(8, 8, 15, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--bd);
            display: flex; align-items: center;
         }
         .lm #mob-bottom-nav { background: rgba(250, 250, 254, 0.97); }

         .mob-nav-items {
            display: flex; width: 100%;
            align-items: center; justify-content: space-around;
            padding: 0 4px;
         }
         .mob-nav-item {
            display: flex; flex-direction: column;
            align-items: center; gap: 3px;
            padding: 8px 10px; border-radius: 12px;
            text-decoration: none; color: var(--tx3);
            font-size: 0.62rem; font-weight: 600; letter-spacing: 0.02em;
            min-width: 52px;
            transition: color 0.2s;
            -webkit-tap-highlight-color: transparent;
            border: none; background: transparent; cursor: pointer;
            font-family: "Poppins", sans-serif;
         }
         .mob-nav-item i { font-size: 1.25rem; transition: transform 0.18s; }
         .mob-nav-item:active i { transform: scale(0.82); }
         .mob-nav-item.active { color: #60a5fa; }
         .mob-nav-item.active i { filter: drop-shadow(0 0 6px rgba(96,165,250,0.55)); }

         /* ---- Drawer Overlay ---- */
         #mob-drawer-overlay {
            display: none; position: fixed; inset: 0; z-index: 1050;
            background: rgba(0,0,0,0.55); backdrop-filter: blur(4px);
            animation: mobFadeIn 0.22s ease;
         }
         #mob-drawer-overlay.open { display: block; }

         /* ---- Bottom Drawer ---- */
         #mob-drawer {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 1100;
            background: var(--bg2);
            border-top: 1px solid var(--bd2);
            border-radius: 24px 24px 0 0;
            padding: 10px 16px calc(18px + var(--mob-safe-bottom));
            max-height: 90dvh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.34, 1.26, 0.64, 1);
         }
         #mob-drawer.open { transform: translateY(0); }

         .drawer-handle {
            width: 32px; height: 4px;
            background: var(--bd2); border-radius: 100px;
            margin: 0 auto 16px;
         }
         .drawer-title {
            font-size: 0.66rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.04em;
            color: var(--tx3); margin: 14px 0 8px; padding: 0 2px;
         }
         .drawer-grid {
            display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px; margin-bottom: 14px;
         }
         .drawer-item {
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px;
            min-height: 62px;
            padding: 10px 8px; border-radius: 12px;
            background: var(--sf); border: 1px solid var(--bd);
            text-decoration: none; color: var(--tx2);
            font-size: 0.66rem; line-height: 1.15; font-weight: 700; text-align: center;
            transition: background-color 0.2s, transform 0.2s, border-color 0.2s, color 0.2s; -webkit-tap-highlight-color: transparent;
         }
         .drawer-item i { font-size: 1.1rem; color: #60a5fa; }
         .drawer-item span {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            overflow-wrap: anywhere;
         }
         .drawer-item:active { transform: scale(0.95); background: rgba(139,92,246,0.1); }
         .drawer-item.active {
            border-color: rgba(96,165,250,0.4);
            background: rgba(96,165,250,0.08); color: #60a5fa;
         }
         .drawer-divider { height: 1px; background: var(--bd); margin: 12px 0; }
         .drawer-action {
            display: flex; align-items: center; gap: 12px;
            min-height: 42px;
            padding: 9px 10px; border-radius: 10px;
            color: var(--tx2); font-size: 0.78rem; font-weight: 600;
            cursor: pointer; border: none; background: transparent;
            width: 100%; font-family: "Poppins", sans-serif;
            text-align: left; text-decoration: none; transition: background-color 0.18s, color 0.18s;
         }
         .drawer-action i { width: 18px; text-align: center; font-size: 0.86rem; }
         .drawer-action:active { background: rgba(139,92,246,0.08); }
         .drawer-action.danger { color: #f87171; }

         .drawer-user {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            padding: 0 2px;
            min-width: 0;
         }

         .drawer-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--grad);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.88rem;
            font-weight: 800;
            flex: 0 0 auto;
            padding: 0;
            overflow: hidden;
            border: 1px solid var(--bd);
         }

         .drawer-user-meta {
            min-width: 0;
         }

         .drawer-user-name {
            font-weight: 800;
            font-size: 0.8rem;
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
         }

         .drawer-user-role {
            font-size: 0.64rem;
            color: var(--tx3);
            line-height: 1.25;
         }

         @media (max-width: 360px) {
            #mob-drawer {
               padding-left: 12px;
               padding-right: 12px;
            }

            .drawer-grid {
               gap: 7px;
            }

            .drawer-item {
               min-height: 58px;
               padding: 8px 6px;
               font-size: 0.62rem;
            }

            .drawer-item i {
               font-size: 1rem;
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
               padding: 12px !important;
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

         /* --- Panel & card padding reduction on small screens --- */
         @media (max-width: 575px) {
            .setup-panel  { padding: 14px !important; }
            .panel        { padding: 14px !important; }
            .premium-card { padding: 14px !important; }
         }

         /* --- Question types checkbox grid: 1 col on small screens --- */
         @media (max-width: 575px) {
            .cbx-grid { grid-template-columns: 1fr !important; }
            .row.g-4  { --bs-gutter-x: 0.75rem; --bs-gutter-y: 0.75rem; }
            .db-content { padding: 10px 12px 12px !important; }
         }

         @media (max-width: 380px) {
            .db-content {
               padding-left: 10px !important;
               padding-right: 10px !important;
            }

            .stat-grid {
               grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            #progress-stats .premium-panel,
            #dashboard-stats .ll-stat-card,
            .stat-card.premium-panel {
               min-height: 100px !important;
               padding: 10px !important;
            }
         }

         /* --- Table mobile scrolling --- */
         .table-responsive { -webkit-overflow-scrolling: touch; }

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
      <script>
         if (localStorage.getItem('theme') === 'light') {
             document.documentElement.classList.add('lm');
         }
      </script>
   </head>
   <body>

      <!-- ===== MOBILE TOP HEADER ===== -->
      <header id="mob-header">
         <a href="{{ route('dashboard') }}" class="mob-header-logo">
            <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
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
            <a href="{{ route('user.notifications') }}" class="mob-icon-btn" style="position:relative; text-decoration:none;">
               <i class="fa-regular fa-bell"></i>
               <span id="mobNotifBadge" style="position:absolute;top:5px;right:5px;width:9px;height:9px;border-radius:50%;background:#f87171;border:2px solid var(--bg);display:none;"></span>
            </a>
            <div class="mob-avatar" onclick="openMobDrawer()" title="Menu" style="padding:0;overflow:hidden;border:1px solid var(--bd);">
               @if(Auth::check() && Auth::user()->profile_photo_path)
                  @php
                      $photoPath = Auth::user()->profile_photo_path;
                      $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                  @endphp
                  <img src="{{ $photoUrl }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
               @else
                  {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U' }}
               @endif
            </div>
         </div>
      </header>

      <!-- ===== PAGE CONTENT ===== -->
      <div id="mob-content">
         <div class="db-content">
            @yield('content')
         </div>
      </div>

      <!-- ===== BOTTOM NAVIGATION ===== -->
      <nav id="mob-bottom-nav" aria-label="Main navigation">
         <div class="mob-nav-items">
            <a href="{{ route('dashboard') }}"
               class="mob-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
               id="mobnav-home">
               <i class="fa-solid fa-gauge-high"></i>
               <span>Home</span>
            </a>
            <a href="{{ route('interview.setup') }}"
               class="mob-nav-item {{ request()->routeIs('interview.*') ? 'active' : '' }}"
               id="mobnav-interview">
               <i class="fa-solid fa-microphone-lines"></i>
               <span>Interview</span>
            </a>
            <a href="{{ route('user.progress') }}"
               class="mob-nav-item {{ request()->routeIs('user.progress') ? 'active' : '' }}"
               id="mobnav-progress">
               <i class="fa-solid fa-chart-line"></i>
               <span>Progress</span>
            </a>
            <a href="{{ route('user.coach') }}"
               class="mob-nav-item {{ request()->routeIs('user.coach') ? 'active' : '' }}"
               id="mobnav-coach">
               <i class="fa-solid fa-robot"></i>
               <span>Coach</span>
            </a>
            <button class="mob-nav-item" id="mobnav-more" onclick="openMobDrawer()">
               <i class="fa-solid fa-ellipsis"></i>
               <span>More</span>
            </button>
         </div>
      </nav>

      <!-- ===== DRAWER OVERLAY ===== -->
      <div id="mob-drawer-overlay" onclick="closeMobDrawer()"></div>

      <!-- ===== BOTTOM DRAWER ===== -->
      <div id="mob-drawer" role="dialog" aria-modal="true" aria-label="More options">
         <div class="drawer-handle"></div>

         <!-- User info -->
         <div class="drawer-user">
            <div class="drawer-user-avatar">
               @if(Auth::check() && Auth::user()->profile_photo_path)
                  @php
                      $photoPath = Auth::user()->profile_photo_path;
                      $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                  @endphp
                  <img src="{{ $photoUrl }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
               @else
                  {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U' }}
               @endif
            </div>
            <div class="drawer-user-meta">
               <div class="drawer-user-name">{{ Auth::user()->name ?? 'User' }}</div>
               <div class="drawer-user-role">{{ Auth::check() && Auth::user()->is_admin ? 'ADMIN' : 'USER' }}</div>
            </div>
         </div>

         <div class="drawer-title">Training & Performance</div>
         <div class="drawer-grid">
            <a href="{{ route('user.modules.index') }}"
               class="drawer-item {{ request()->routeIs('user.modules.*') ? 'active' : '' }}">
               <i class="fa-solid fa-book-open-reader"></i>
               <span>Interview Modules</span>
            </a>
            <a href="{{ route('user.drills.voice') }}"
               class="drawer-item {{ request()->routeIs('user.drills.voice') ? 'active' : '' }}">
               <i class="fa-solid fa-ear-listen"></i>
               <span>Voice Rehearsal</span>
            </a>
            <a href="{{ route('user.learning') }}"
               class="drawer-item {{ request()->routeIs('user.learning*') ? 'active' : '' }}">
               <i class="fa-solid fa-gamepad"></i>
               <span>Learning Games</span>
            </a>
            <a href="{{ route('user.feedback') }}"
               class="drawer-item {{ request()->routeIs('user.feedback') ? 'active' : '' }}">
               <i class="fa-solid fa-clipboard-check"></i>
               <span>Feedback</span>
            </a>
            <a href="{{ route('user.reports') }}"
               class="drawer-item {{ request()->routeIs('user.reports') ? 'active' : '' }}">
               <i class="fa-solid fa-folder-open"></i>
               <span>Reports</span>
            </a>
         </div>

         <div class="drawer-title">Community & More</div>
         <div class="drawer-grid">
            <a href="{{ route('user.leaderboard') }}"
               class="drawer-item {{ request()->routeIs('user.leaderboard') ? 'active' : '' }}">
               <i class="fa-solid fa-trophy"></i>
               <span>Leaderboard</span>
            </a>
            <a href="{{ route('user.account') }}"
               class="drawer-item {{ request()->routeIs('user.account') ? 'active' : '' }}">
               <i class="fa-solid fa-user-gear"></i>
               <span>Account</span>
            </a>
            <a href="{{ route('user.notifications') }}"
               class="drawer-item {{ request()->routeIs('user.notifications') ? 'active' : '' }}">
               <i class="fa-solid fa-bell"></i>
               <span>Notifications</span>
            </a>
         </div>

         <div class="drawer-divider"></div>

         <a href="{{ route('user.account') }}" class="drawer-action">
            <i class="fa-solid fa-user-gear"></i> Account Management
         </a>

         <form action="{{ route('logout') }}" method="POST" style="display:block">
            @csrf
            <button type="submit" class="drawer-action danger">
               <i class="fa-solid fa-right-from-bracket"></i> Log Out
            </button>
         </form>
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

      <!-- ======================== SCRIPTS ======================== -->
      <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
      <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
      <script src="{{ asset('js/aos.js') }}"></script>
      <script src="{{ asset('js/chart.umd.min.js') }}"></script>
      <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
      <script src="{{ asset('js/main.js') }}"></script>
      <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>

      <script>
         function openMobDrawer() {
            document.getElementById('mob-drawer').classList.add('open');
            document.getElementById('mob-drawer-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
         }
         function closeMobDrawer() {
            document.getElementById('mob-drawer').classList.remove('open');
            document.getElementById('mob-drawer-overlay').classList.remove('open');
            document.body.style.overflow = '';
         }

         // Swipe down to close drawer
         (function() {
            const drawer = document.getElementById('mob-drawer');
            let startY = 0;
            drawer.addEventListener('touchstart', e => { startY = e.touches[0].clientY; }, { passive: true });
            drawer.addEventListener('touchend', e => {
               if (e.changedTouches[0].clientY - startY > 60) closeMobDrawer();
            }, { passive: true });
         })();

         function toggleTheme() {
            const html = document.getElementById('htmlRoot');
            const sunI = document.getElementById('mobSunI');
            const moonI = document.getElementById('mobMoonI');
            if (html.classList.contains('lm')) {
               html.classList.remove('lm');
               localStorage.setItem('theme', 'dark');
               sunI.style.display = 'none';
               moonI.style.display = '';
            } else {
               html.classList.add('lm');
               localStorage.setItem('theme', 'light');
               moonI.style.display = 'none';
               sunI.style.display = '';
            }
         }

         // Sync theme icon on load
         (function() {
            if (localStorage.getItem('theme') === 'light') {
               document.getElementById('mobMoonI').style.display = 'none';
               document.getElementById('mobSunI').style.display = '';
            }
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

         function fetchMobileNotifications() {
            fetch('/notifications/fetch')
               .then(res => res.json())
               .then(data => {
                  const badge = document.getElementById('mobNotifBadge');
                  if (badge) {
                     if (data.unreadCount > 0) {
                        badge.style.display = 'block';
                     } else {
                        badge.style.display = 'none';
                     }
                  }
               })
               .catch(err => console.error('Error fetching notifications:', err));
         }

         document.addEventListener('DOMContentLoaded', function() {
            fetchMobileNotifications();
            setInterval(fetchMobileNotifications, 60000);
         });
      </script>

      @stack('scripts')
      @include('layouts.logout-transition')
   </body>
</html>
