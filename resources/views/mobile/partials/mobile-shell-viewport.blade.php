         /* ---- Mobile shell viewport and fullscreen hardening ---- */
         :root {
            --sr-visual-vh: 100vh;
            --sr-visual-vh: 100dvh;
            --sr-layout-vw: 100vw;
         }

         @supports (height: 100svh) {
            :root {
               --sr-visual-vh: 100svh;
            }
         }

         html,
         body {
            width: 100%;
            max-width: 100%;
            min-height: var(--sr-visual-vh);
            overflow-x: hidden !important;
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
