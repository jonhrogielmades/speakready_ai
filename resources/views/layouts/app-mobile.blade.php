<!DOCTYPE html>
<html lang="en" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
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
      <link rel="stylesheet" href="{{ asset('css/style.css?v=2') }}" />
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
      <style>
         /* ===== MOBILE LAYOUT SHELL ===== */
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
            padding-bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 16px);
            min-height: 100dvh;
         }
         .db-content { padding: 16px !important; }

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
            font-family: "Space Grotesk", sans-serif;
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
            padding: 12px 20px calc(24px + var(--mob-safe-bottom));
            max-height: 90dvh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.34, 1.26, 0.64, 1);
         }
         #mob-drawer.open { transform: translateY(0); }

         .drawer-handle {
            width: 40px; height: 4px;
            background: var(--bd2); border-radius: 100px;
            margin: 0 auto 20px;
         }
         .drawer-title {
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.1em;
            color: var(--tx3); margin-bottom: 12px; padding: 0 4px;
         }
         .drawer-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 10px; margin-bottom: 20px;
         }
         .drawer-item {
            display: flex; flex-direction: column; align-items: center; gap: 7px;
            padding: 14px 10px; border-radius: 14px;
            background: var(--sf); border: 1px solid var(--bd);
            text-decoration: none; color: var(--tx2);
            font-size: 0.72rem; font-weight: 600; text-align: center;
            transition: background-color 0.2s, transform 0.2s, border-color 0.2s, color 0.2s; -webkit-tap-highlight-color: transparent;
         }
         .drawer-item i { font-size: 1.3rem; color: #60a5fa; }
         .drawer-item:active { transform: scale(0.95); background: rgba(139,92,246,0.1); }
         .drawer-item.active {
            border-color: rgba(96,165,250,0.4);
            background: rgba(96,165,250,0.08); color: #60a5fa;
         }
         .drawer-divider { height: 1px; background: var(--bd); margin: 14px 0; }
         .drawer-action {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px; border-radius: 12px;
            color: var(--tx2); font-size: 0.875rem; font-weight: 500;
            cursor: pointer; border: none; background: transparent;
            width: 100%; font-family: "Space Grotesk", sans-serif;
            text-align: left; text-decoration: none; transition: background-color 0.18s, color 0.18s;
         }
         .drawer-action i { width: 20px; text-align: center; font-size: 1rem; }
         .drawer-action:active { background: rgba(139,92,246,0.08); }
         .drawer-action.danger { color: #f87171; }

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
            
            /* Show col-lg-4 but only for the camera */
            #workspaceRow > .col-lg-4 { display: block !important; order: -1; margin-bottom: 0; }
            
            /* Hide non-camera panels in col-lg-4 */
            #workspaceRow > .col-lg-4 > .panel:not(#cameraPanel) { display: none !important; }
            
            /* Make camera a small floating square at the top right (Picture-in-Picture) */
            #cameraPanel {
               position: fixed; 
               top: calc(var(--mob-top-h) + var(--mob-safe-top) + 16px); 
               right: 16px;
               width: 110px; height: 140px;
               z-index: 100; margin: 0 !important; padding: 0 !important; 
               border: 2px solid rgba(255,255,255,0.2); border-radius: 14px;
               background: #000; box-shadow: 0 10px 25px rgba(0,0,0,0.5);
               overflow: hidden;
            }
            #cameraPanel .panel-title, #cameraPanel .stat-row { display: none !important; }
            #cameraPanel > div:nth-child(2) {
               height: 100% !important; width: 100% !important; margin: 0 !important; border-radius: 0 !important;
            }
            #cameraPanel video { object-fit: cover !important; width: 100%; height: 100%; }
            
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
            .col-md-3.col-sm-6 { width: 50% !important; }
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
            .db-content { padding: 10px !important; }
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
         <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding:0 4px">
            <div style="width:42px;height:42px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem;font-weight:700;flex-shrink:0;padding:0;overflow:hidden;border:1px solid var(--bd);">
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
            <div>
               <div style="font-weight:700;font-size:.9rem">{{ Auth::user()->name ?? 'User' }}</div>
               <div style="font-size:.72rem;color:var(--tx3)">{{ Auth::check() && Auth::user()->is_admin ? 'ADMIN' : 'USER' }}</div>
            </div>
         </div>

         <div class="drawer-title">Training & Performance</div>
         <div class="drawer-grid">
            <a href="{{ route('user.drills.voice') }}"
               class="drawer-item {{ request()->routeIs('user.drills.voice') ? 'active' : '' }}">
               <i class="fa-solid fa-ear-listen"></i>
               <span>Voice Rehearsal</span>
            </a>
            <a href="{{ route('user.learning') }}"
               class="drawer-item {{ request()->routeIs('user.learning*') ? 'active' : '' }}">
               <i class="fa-solid fa-gamepad"></i>
               <span>Interview Arena</span>
            </a>
            <a href="{{ route('user.feedback') }}"
               class="drawer-item {{ request()->routeIs('user.feedback') ? 'active' : '' }}">
               <i class="fa-solid fa-clipboard-check"></i>
               <span>Feedback</span>
            </a>
            <a href="{{ route('user.reports') }}"
               class="drawer-item {{ request()->routeIs('user.reports') ? 'active' : '' }}">
               <i class="fa-solid fa-file-invoice"></i>
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

         // Exit App Confirmation Logic for Physical Back Button
         let allowAppExit = false;
         window.addEventListener('load', function() {
            if (window.location.pathname === '/dashboard') {
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

         // PWA Service Worker
         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js')
                  .then(reg => console.log('SW registered:', reg.scope))
                  .catch(err => console.log('SW failed:', err));
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



