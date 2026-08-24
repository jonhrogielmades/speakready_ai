         /* ---- Mobile shell viewport and fullscreen hardening ---- */
         :root {
            --sr-visual-vh: var(--sr-js-vh, 100vh);
            --sr-visual-vh: var(--sr-js-vh, 100dvh);
            --sr-layout-vw: 100vw;
            --sr-mobile-inline: clamp(12px, 4vw, 18px);
            --sr-touch-target: 44px;
         }

         @supports (height: 100svh) {
            :root {
               --sr-visual-vh: var(--sr-js-vh, 100svh);
            }
         }

         @supports (-webkit-touch-callout: none) {
            html {
               min-height: -webkit-fill-available;
            }
         }

         html,
         body {
            width: 100%;
            max-width: 100%;
            min-height: var(--sr-visual-vh);
            overflow-x: hidden !important;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
         }

         @supports (overflow: clip) {
            html,
            body,
            body.mobile-shell #dashboard,
            body.mobile-shell #mob-content {
               overflow-x: clip !important;
            }
         }

         body.mobile-shell {
            min-height: var(--sr-visual-vh);
            overscroll-behavior-x: none;
            touch-action: pan-y;
         }

         body.mobile-shell #dashboard,
         body.mobile-shell .db-main {
            width: 100%;
            min-width: 0;
            max-width: 100%;
            min-height: var(--sr-visual-vh);
         }

         body.mobile-shell #mob-header,
         body.mobile-shell #mob-bottom-nav {
            left: 0;
            right: 0;
            width: 100%;
            max-width: 100%;
            transform: translateZ(0);
            contain: layout paint;
         }

         body.mobile-shell #mob-content {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            min-height: var(--sr-visual-vh) !important;
            overflow-x: hidden !important;
            scroll-padding-top: calc(var(--mob-top-h) + var(--mob-safe-top) + 10px);
            scroll-padding-bottom: calc(var(--mob-nav-h) + var(--mob-safe-bottom) + 14px);
         }

         body.mobile-shell #mob-content > .db-content {
            min-width: 0;
            max-width: 100%;
            padding-left: max(var(--sr-mobile-inline), env(safe-area-inset-left, 0px)) !important;
            padding-right: max(var(--sr-mobile-inline), env(safe-area-inset-right, 0px)) !important;
         }

         body.mobile-shell #mob-content :where(
            .container,
            .container-fluid,
            .row,
            .row > *,
            .col,
            [class*="col-"],
            .d-flex,
            .d-grid,
            [class*="grid"],
            [class*="layout"],
            [class*="shell"],
            form,
            fieldset,
            .tab-content,
            .tab-pane,
            .card,
            .premium-card,
            .premium-panel,
            .setup-panel,
            .panel,
            .module-card,
            .ll-module-card,
            .level-card,
            .sr-card,
            .print-card,
            .table-responsive
         ) {
            min-width: 0 !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
         }

         body.mobile-shell #mob-content :where(img, picture, video, canvas, iframe, svg) {
            max-width: 100% !important;
         }

         body.mobile-shell #mob-content :where(p, small, span, strong, label, a:not(.btn), td, th, li, h1, h2, h3, h4, h5, h6) {
            overflow-wrap: anywhere;
         }

         body.mobile-shell #mob-content :where(.table-responsive, .table-responsive-sm, .table-responsive-md, .table-responsive-lg, .table-responsive-xl) {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: auto !important;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-inline: contain;
         }

         body.mobile-shell #mob-content :where(.table, .custom-table, .db-table) {
            width: 100%;
         }

         body.mobile-shell :where(a, button, .btn, [role="button"], input, select, textarea, summary, label) {
            -webkit-tap-highlight-color: transparent;
         }

         body.mobile-shell #mob-content :where(button, .btn, [role="button"], input[type="button"], input[type="submit"], input[type="reset"], select) {
            min-height: var(--sr-touch-target);
            touch-action: manipulation;
         }

         @media (hover: none), (pointer: coarse) {
            body.mobile-shell #mob-content :where(input, select, textarea, .form-control, .form-select, .oinp, .tracker-field) {
               font-size: max(16px, 1rem) !important;
            }
         }

         @media (max-width: 575.98px) {
            body.mobile-shell #mob-content :where(.row) {
               --bs-gutter-x: 0.75rem;
               --bs-gutter-y: 0.75rem;
            }

            body.mobile-shell #mob-content :where(.card-header, .card-footer, .action-buttons, .btn-toolbar, .sr-page-actions, .progress-actions) {
               flex-wrap: wrap !important;
               gap: 8px !important;
            }

            body.mobile-shell #mob-content :where(.btn, button) {
               max-width: 100%;
               white-space: normal;
            }
         }

         body.mobile-shell.user-app-fullscreen {
            height: var(--sr-visual-vh);
            min-height: var(--sr-visual-vh);
            overflow: hidden !important;
            overscroll-behavior: none;
         }

         body.mobile-shell.user-app-fullscreen #dashboard,
         body.mobile-shell.user-app-fullscreen .db-main,
         body.mobile-shell.user-app-fullscreen #mob-content {
            height: var(--sr-visual-vh) !important;
            min-height: var(--sr-visual-vh) !important;
         }

         body.mobile-shell.user-app-fullscreen #mob-content {
            overflow-y: auto !important;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
         }

         body.mobile-shell.user-app-fullscreen #mob-content > .db-content {
            min-height: calc(var(--sr-visual-vh) - var(--mob-top-h) - var(--mob-safe-top) - var(--mob-nav-h) - var(--mob-safe-bottom));
         }

         body.mobile-shell.user-app-fullscreen :is(#sec-progress-tracking, #voice-rehearsal-page) {
            min-width: 0 !important;
            max-width: 100% !important;
            min-height: auto !important;
            overflow: visible !important;
            padding-bottom: calc(var(--mob-safe-bottom) + 18px) !important;
         }

         body.mobile-shell.user-app-fullscreen :is(#sec-progress-tracking, #voice-rehearsal-page) :is(.progress-chart-panel, .vr-progress-panel) {
            min-width: 0 !important;
            max-width: 100% !important;
            overflow: hidden !important;
         }

         body.mobile-shell.user-app-fullscreen :is(#sec-progress-tracking, #voice-rehearsal-page) :is(.progress-chart-frame, .vr-chart-frame) {
            position: relative !important;
            width: 100% !important;
            min-width: 0 !important;
            height: clamp(168px, 31svh, 240px) !important;
            max-height: calc(var(--sr-visual-vh) - var(--mob-top-h) - var(--mob-safe-top) - var(--mob-nav-h) - var(--mob-safe-bottom) - 130px) !important;
            overflow: hidden !important;
         }

         body.mobile-shell.user-app-fullscreen #voice-rehearsal-page .vr-chart-frame {
            height: clamp(170px, 32svh, 250px) !important;
         }

         body.mobile-shell.user-app-fullscreen :is(#sec-progress-tracking, #voice-rehearsal-page) :is(.progress-chart-frame, .vr-chart-frame) > canvas {
            display: block !important;
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
         }

         body.mobile-shell.user-app-fullscreen :is(#sec-progress-tracking, #voice-rehearsal-page) .table-responsive {
            width: 100% !important;
            max-width: 100% !important;
            overflow-x: auto !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch;
         }

         body.mobile-shell.user-app-fullscreen #voice-rehearsal-page .voice-history-table {
            max-width: 100% !important;
         }

         body.mobile-shell.user-app-fullscreen #voice-rehearsal-page :is(#moduleTabs, .vr-tabs, .module-tabs) {
            position: static !important;
            inset: auto !important;
            transform: none !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
            z-index: auto !important;
         }

         body.mobile-shell.user-app-fullscreen #voice-rehearsal-page :is(.vr-practice-flow, .vr-options, .vr-setup-grid, .vr-option-grid) {
            position: relative !important;
            z-index: 0 !important;
            clear: both !important;
         }

         body.mobile-shell.user-app-fullscreen #voice-rehearsal-page :is(.vr-setup-grid, .vr-option-grid) {
            width: 100% !important;
         }

         body.mobile-shell.user-app-fullscreen #voice-rehearsal-page :is(.vr-setup-card, .vr-option-card, .vr-select-card) {
            min-width: 0 !important;
            max-width: 100% !important;
            overflow: hidden !important;
         }

         body.mobile-shell.user-app-fullscreen #sec-progress-tracking .progress-actions {
            position: static !important;
            inset: auto !important;
            transform: none !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            min-height: 0 !important;
            overflow: visible !important;
            z-index: auto !important;
         }

         body.mobile-shell.user-app-fullscreen #sec-progress-tracking .progress-actions :is(.btn, button, a) {
            min-width: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            white-space: nowrap !important;
         }

         body.mobile-shell.user-app-fullscreen #sec-progress-tracking #progress-stats {
            position: relative !important;
            z-index: 0 !important;
            clear: both !important;
         }

         body.mobile-shell .mob-profile-dropdown,
         body.mobile-shell .mob-notif-dropdown,
         body.mobile-shell .mob-notification-dropdown {
            max-height: calc(var(--sr-visual-vh) - var(--mob-top-h) - var(--mob-safe-top) - 20px);
         }

         body.mobile-shell .mob-profile-dropdown[data-origin="bottom"] {
            max-height: calc(var(--sr-visual-vh) - var(--mob-nav-h) - var(--mob-safe-bottom) - 20px);
         }

         body.mobile-shell.user-app-fullscreen .modal.show {
            height: var(--sr-visual-vh);
            overflow-y: auto;
         }
