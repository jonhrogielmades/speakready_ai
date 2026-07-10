<!DOCTYPE html>
<html lang="en" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#1a0a0a">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
      <title>SpeakReady AI Admin Portal</title>
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
      <link rel="stylesheet" href="{{ asset('css/style.css?v=8') }}" />
      <style>
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

         .mob-header-brand {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.88rem; font-weight: 700;
            color: var(--tx); text-decoration: none;
         }
         .mob-header-brand img { width: 28px; height: 28px; border-radius: 7px; }
         .mob-header-brand .adm-badge {
            font-size: 0.58rem; font-weight: 700;
            background: var(--adm-bg); color: var(--adm);
            border: 1px solid var(--adm-bd);
            padding: 2px 7px; border-radius: 6px;
            text-transform: uppercase; letter-spacing: 0.05em;
            flex-shrink: 0;
         }
         .mob-header-right { display: flex; align-items: center; gap: 10px; }
         .mob-icon-btn {
            width: 36px; height: 36px; border-radius: 10px;
            border: 1px solid var(--adm-bd);
            background: transparent; color: var(--tx);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; cursor: pointer; transition: 0.2s;
            -webkit-tap-highlight-color: transparent;
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

         /* ---- Page Content ---- */
         #mob-content {
            padding-top: calc(var(--mob-top-h) + var(--mob-safe-top));
            padding-bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 16px);
            min-height: 100dvh;
            overflow-x: hidden !important;
            width: 100vw;
            max-width: 100%;
         }
         .db-content { padding: 14px !important; }

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
            display: flex; width: 100%;
            align-items: center; justify-content: space-around;
            padding: 0 4px;
         }
         .mob-nav-item {
            display: flex; flex-direction: column; align-items: center; gap: 3px;
            padding: 8px 10px; border-radius: 12px;
            text-decoration: none; color: var(--tx3);
            font-size: 0.6rem; font-weight: 600; letter-spacing: 0.02em;
            min-width: 50px;
            transition: color 0.2s;
            -webkit-tap-highlight-color: transparent;
            border: none; background: transparent; cursor: pointer;
            font-family: "Poppins", sans-serif;
         }
         .mob-nav-item i { font-size: 1.2rem; transition: transform 0.18s; }
         .mob-nav-item:active i { transform: scale(0.82); }
         .mob-nav-item.active { color: var(--adm); }
         .mob-nav-item.active i { filter: drop-shadow(0 0 5px rgba(248,113,113,0.55)); }

         /* ---- Drawer Overlay ---- */
         #mob-drawer-overlay {
            display: none; position: fixed; inset: 0; z-index: 1050;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            animation: mobFadeIn 0.22s ease;
         }
         #mob-drawer-overlay.open { display: block; }

         /* ---- Bottom Drawer ---- */
         #mob-drawer {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 1100;
            background: var(--bg2);
            border-top: 2px solid var(--adm-bd);
            border-radius: 24px 24px 0 0;
            padding: 12px 20px calc(24px + var(--mob-safe-bottom));
            max-height: 88dvh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.34, 1.26, 0.64, 1);
         }
         #mob-drawer.open { transform: translateY(0); }
         .drawer-handle {
            width: 40px; height: 4px;
            background: var(--adm-bd); border-radius: 100px;
            margin: 0 auto 18px;
         }
         .drawer-section-title {
            font-size: 0.67rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.1em;
            color: var(--adm); margin: 16px 0 10px; padding: 0 4px;
         }
         .drawer-section-title:first-of-type { margin-top: 0; }
         .drawer-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 8px; margin-bottom: 4px;
         }
         .drawer-item {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            padding: 12px 8px; border-radius: 12px;
            background: var(--sf); border: 1px solid var(--bd);
            text-decoration: none; color: var(--tx2);
            font-size: 0.68rem; font-weight: 600; text-align: center;
            transition: 0.2s; -webkit-tap-highlight-color: transparent;
         }
         .drawer-item i { font-size: 1.2rem; color: var(--adm); }
         .drawer-item:active { transform: scale(0.94); background: var(--adm-bg); }
         .drawer-item.active { border-color: var(--adm-bd); background: var(--adm-bg); color: var(--adm); }
         .drawer-divider { height: 1px; background: var(--bd); margin: 14px 0; }
         .drawer-action {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px; border-radius: 12px;
            color: var(--tx2); font-size: 0.875rem; font-weight: 500;
            cursor: pointer; border: none; background: transparent;
            width: 100%; font-family: "Poppins", sans-serif;
            text-align: left; text-decoration: none; transition: 0.18s;
         }
         .drawer-action i { width: 20px; text-align: center; font-size: 1rem; }
         .drawer-action:active { background: var(--adm-bg); }
         .drawer-action.danger { color: #f87171; }
         .drawer-ai-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
         }
         .drawer-ai-item {
            display: flex; flex-direction: column; align-items: center; gap: 5px;
            padding: 10px 6px; border-radius: 10px;
            background: var(--bg3); border: 1px solid var(--bd);
            text-decoration: none; color: var(--tx3);
            font-size: 0.63rem; font-weight: 600; text-align: center;
            transition: 0.2s; -webkit-tap-highlight-color: transparent;
         }
         .drawer-ai-item i { font-size: 1rem; color: #60a5fa; }
         .drawer-ai-item:active { background: rgba(96,165,250,0.12); }
         .drawer-ai-item.active { color: #60a5fa; border-color: rgba(96,165,250,0.3); }

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

         /* --- 8. premium-card padding reduction --- */
         @media (max-width: 575px) {
            .premium-card { padding: 14px !important; }
            .premium-card.p-3 { padding: 12px !important; }
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
            .modal-dialog:not(.modal-sm) {
               margin: 0 !important;
               max-width: 100% !important;
               height: 100dvh;
            }
            .modal-dialog:not(.modal-sm) .modal-content {
               border-radius: 0 !important;
               height: 100dvh;
               border: none !important;
            }
            .modal-xl .modal-dialog { margin: 0 !important; }
            .modal-body { overflow-y: auto; }
            /* Nav tabs in modal: scrollable */
            .modal .nav-tabs { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .modal .nav-tabs .nav-link { white-space: nowrap; font-size: 0.8rem; padding: 8px 12px; }
            /* Modal footer: stack buttons */
            .modal-footer { flex-wrap: wrap; gap: 8px; }
            .modal-footer .btn { flex: 1 1 auto; font-size: 0.82rem; }
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

         /* --- 19. db-content padding on tiny screens --- */
         @media (max-width: 380px) {
            .db-content { padding: 10px !important; }
            .row.g-3 { --bs-gutter-x: 0.5rem; --bs-gutter-y: 0.5rem; }
            .row.g-4 { --bs-gutter-x: 0.5rem; --bs-gutter-y: 0.5rem; }
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

         /* --- PWA Install Prompt --- */
         #pwa-install-prompt {
            display: none; position: fixed;
            bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 20px);
            left: 16px; right: 16px; z-index: 1050;
            background: rgba(10, 4, 4, 0.95);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--adm-bd); border-radius: 16px;
            padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            text-align: center; animation: mobFadeIn 0.3s ease;
         }
         .lm #pwa-install-prompt { background: rgba(255, 248, 248, 0.95); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
         #pwa-install-prompt h5 { color: #fff; font-weight: 700; margin-bottom: 8px; font-size: 1.1rem; }
         .lm #pwa-install-prompt h5 { color: #111; }
         #pwa-install-prompt p { color: #aaa; font-size: 0.85rem; margin-bottom: 16px; }
         .lm #pwa-install-prompt p { color: #555; }
         .pwa-btn-wrap { display: flex; gap: 12px; justify-content: center; }
         .pwa-btn-no { flex: 1; padding: 10px; border-radius: 10px; border: 1px solid #444; background: transparent; color: #fff; font-weight: 600; cursor: pointer; }
         .lm .pwa-btn-no { border-color: #ccc; color: #333; }
         .pwa-btn-yes { flex: 1; padding: 10px; border-radius: 10px; border: none; background: var(--adm, #f87171); color: #fff; font-weight: 600; cursor: pointer; }
      </style>
      <script>
         if (localStorage.getItem('theme') === 'light') {
             document.documentElement.classList.add('lm');
         }
      </script>
   </head>
   <body>

      <!-- ===== ADMIN MOBILE TOP HEADER ===== -->
      <header id="mob-header">
         <a href="{{ route('admin.dashboard') }}" class="mob-header-brand">
            <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
            <span>SpeakReady AI</span>
         </a>
         <div class="mob-header-right">
            <div class="dropdown">
                <a href="#" class="mob-icon-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="Live Activity" style="text-decoration:none;" onclick="resetAdminActivityBadge('mobile')">
                   <i class="fa-regular fa-bell"></i>
                   <span id="admin-activity-badge-mobile" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-dark rounded-circle" style="display:none; width: 8px; height: 8px; margin-left: -5px; margin-top: 5px;">
                      <span class="visually-hidden">New alerts</span>
                   </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow-lg" style="width: 300px; border-radius: 12px; border: 1px solid var(--adm-bd); background: var(--bg3, #2b2b40); padding: 0; overflow: hidden; margin-top: 10px;">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--bd, rgba(255,255,255,0.1)) !important; background: var(--sf, #1e1e2d);">
                        <div>
                            <span class="fw-bold" style="color: var(--tx, #fff); font-size: 0.95rem;">Live User Activity</span>
                            <span id="admin-activity-count-mobile" class="badge bg-danger rounded-pill ms-1" style="display:none;">0</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm p-0 m-0 text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false" onclick="event.stopPropagation();">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius:8px; border:1px solid var(--bd); background:var(--bg3);">
                                <li><a class="dropdown-item" href="#" onclick="markAllActivitiesRead(event)"><i class="fa-solid fa-check-double me-2 text-primary"></i>Mark all as read</a></li>
                                <li><hr class="dropdown-divider" style="border-color:var(--bd)"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="clearAllActivities(event)"><i class="fa-solid fa-trash-can me-2"></i>Clear all</a></li>
                            </ul>
                        </div>
                    </div>
                    <div id="admin-activity-list-mobile" style="max-height: 280px; overflow-y: auto;">
                        <div class="p-3 text-center text-muted" style="font-size:0.85rem;">Loading activities...</div>
                    </div>
                    <div class="p-2 border-top text-center" style="border-color: var(--bd, rgba(255,255,255,0.1)) !important; background: var(--sf, #1e1e2d);">
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm w-100 fw-bold" style="background: rgba(59,130,246,0.15); color: #3b82f6; border-radius: 8px;">
                            <i class="fa-solid fa-bullhorn me-2"></i>Broadcast Announcement
                        </a>
                    </div>
                </div>
            </div>
            <button class="mob-icon-btn" onclick="toggleTheme()" title="Toggle theme">
               <i class="fa-solid fa-sun" id="mobSunI" style="display:none"></i>
               <i class="fa-solid fa-moon" id="mobMoonI"></i>
            </button>
            <div class="mob-avatar-adm" onclick="openMobDrawer()" title="Menu" style="padding:0;overflow:hidden;">
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
      <div id="mob-content">
         <div class="db-content">
            @yield('content')
         </div>
      </div>

      <!-- ===== ADMIN BOTTOM NAVIGATION ===== -->
      <nav id="mob-bottom-nav" aria-label="Admin navigation">
         <div class="mob-nav-items">
            <a href="{{ route('admin.dashboard') }}"
               class="mob-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
               <i class="fa-solid fa-gauge-high"></i>
               <span>Dashboard</span>
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
               <span>Feedback</span>
            </a>
            <button class="mob-nav-item" onclick="openMobDrawer()">
               <i class="fa-solid fa-ellipsis"></i>
               <span>More</span>
            </button>
         </div>
      </nav>

      <!-- ===== DRAWER OVERLAY ===== -->
      <div id="mob-drawer-overlay" onclick="closeMobDrawer()"></div>

      <!-- ===== ADMIN BOTTOM DRAWER ===== -->
      <div id="mob-drawer" role="dialog" aria-modal="true" aria-label="Admin menu">
         <div class="drawer-handle"></div>

         <!-- Admin user info -->
         <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;background:var(--adm-bg);border:1px solid var(--adm-bd);border-radius:14px;padding:12px 14px;">
            <div style="width:40px;height:40px;border-radius:50%;background:#f87171;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.95rem;font-weight:700;flex-shrink:0;border:2px solid rgba(248,113,113,0.35);padding:0;overflow:hidden;">
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
            <div>
               <div style="font-weight:700;font-size:.875rem">{{ Auth::user()->name ?? 'Admin' }}</div>
               <div style="font-size:.68rem;color:var(--adm);font-weight:600"><i class="fa-solid fa-user-shield me-1"></i>Administrator</div>
            </div>
         </div>

         <!-- Core Modules -->
         <div class="drawer-section-title">Core Modules</div>
         <div class="drawer-grid">
            <a href="{{ route('admin.dashboard') }}"
               class="drawer-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
               <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
            </a>
            <a href="{{ route('admin.users.index') }}"
               class="drawer-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
               <i class="fa-solid fa-users"></i><span>Users</span>
            </a>
            <a href="{{ route('admin.categories') }}"
               class="drawer-item {{ request()->routeIs('admin.categories') ? 'active' : '' }}">
               <i class="fa-solid fa-list"></i><span>Categories</span>
            </a>
            <a href="{{ route('admin.questions') }}"
               class="drawer-item {{ request()->routeIs('admin.questions') ? 'active' : '' }}">
               <i class="fa-solid fa-circle-question"></i><span>Questions</span>
            </a>
            <a href="{{ route('admin.modules') }}"
               class="drawer-item {{ request()->routeIs('admin.modules') ? 'active' : '' }}">
               <i class="fa-solid fa-book-open"></i><span>Modules</span>
            </a>
            <a href="{{ route('admin.game') }}"
               class="drawer-item {{ request()->routeIs('admin.game') || request()->routeIs('admin.game.*') ? 'active' : '' }}">
               <i class="fa-solid fa-gamepad"></i><span>Learning Games</span>
            </a>
         </div>

         <!-- Monitoring -->
         <div class="drawer-section-title">Monitoring</div>
         <div class="drawer-grid">
            <a href="{{ route('admin.sessions.index') }}"
               class="drawer-item {{ request()->routeIs('admin.sessions.index') ? 'active' : '' }}">
               <i class="fa-solid fa-video"></i><span>Sessions</span>
            </a>
            <a href="{{ route('admin.sessions.archive') }}"
               class="drawer-item {{ request()->routeIs('admin.sessions.archive') ? 'active' : '' }}">
               <i class="fa-solid fa-box-archive"></i><span>Archive</span>
            </a>
            <a href="{{ route('admin.feedback.index') }}"
               class="drawer-item {{ request()->routeIs('admin.feedback.index') ? 'active' : '' }}">
               <i class="fa-solid fa-clipboard-check"></i><span>Feedback</span>
            </a>
            <a href="{{ route('admin.feedback.complaints') }}"
               class="drawer-item {{ request()->routeIs('admin.feedback.complaints') ? 'active' : '' }}">
               <i class="fa-solid fa-clipboard-list"></i><span>Complaints</span>
            </a>
            <a href="{{ route('admin.contacts.index') }}"
               class="drawer-item {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">
               <i class="fa-solid fa-envelope"></i><span>Contacts</span>
            </a>
         </div>

         <!-- System -->
         <div class="drawer-section-title">System</div>
         <div class="drawer-grid" style="grid-template-columns: 1fr;">
            <a href="{{ route('admin.ai.providers') }}"
               class="drawer-item {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}"
               style="flex-direction: row; justify-content: flex-start; gap: 10px; padding: 12px 16px; margin-bottom: 8px;">
               <i class="fa-solid fa-microchip" style="font-size: 1rem;"></i><span style="font-size: 0.82rem;">AI Providers</span>
            </a>
            <a href="{{ route('admin.settings.index') }}"
               class="drawer-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
               style="flex-direction: row; justify-content: flex-start; gap: 10px; padding: 12px 16px;">
               <i class="fa-solid fa-gear" style="font-size: 1rem;"></i><span style="font-size: 0.82rem;">System Settings</span>
            </a>
         </div>

         <div class="drawer-divider"></div>

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

         // Swipe-down to close
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
            if (html.classList.contains('lm')) {
               html.classList.remove('lm');
               localStorage.setItem('theme', 'dark');
               document.getElementById('mobSunI').style.display = 'none';
               document.getElementById('mobMoonI').style.display = '';
            } else {
               html.classList.add('lm');
               localStorage.setItem('theme', 'light');
               document.getElementById('mobMoonI').style.display = 'none';
               document.getElementById('mobSunI').style.display = '';
            }
         }
         (function() {
            if (localStorage.getItem('theme') === 'light') {
               document.getElementById('mobMoonI').style.display = 'none';
               document.getElementById('mobSunI').style.display = '';
            }
         })();

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
               navigator.serviceWorker.register('/sw.js')
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
                          if(countEl) { countEl.style.display = 'inline-block'; countEl.innerText = data.new_count; }
                          if(badgeEl) { badgeEl.style.display = 'block'; }
                      } else {
                          if(countEl) { countEl.style.display = 'none'; }
                          if(badgeEl) { badgeEl.style.display = 'none'; }
                      }
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
              fetchAdminActivities();
              setInterval(fetchAdminActivities, 15000);
          });
      </script>

      @stack('scripts')
      @include('layouts.logout-transition')
   </body>
</html>
