<style data-sr-initial-render-guard-style>
   html.sr-user-ui-booting body[data-app-surface="user"] [data-user-ajax-content] {
      visibility: hidden !important;
   }

   html.sr-user-ui-booting body[data-app-surface="user"] #dashboard {
      pointer-events: none !important;
      user-select: none;
      cursor: progress;
   }

   .sr-initial-render-guard {
      display: none;
   }

   html.sr-user-ui-booting body[data-app-surface="user"] .sr-initial-render-guard {
      position: fixed;
      z-index: 880;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      gap: 12px;
      overflow: hidden;
      background:
         radial-gradient(circle at 50% 42%, rgba(37, 99, 235, .12), transparent 230px),
         var(--bg, #102b46);
      color: var(--tx2, #c6d8e8);
      text-align: center;
   }

   html.sr-user-ui-booting body[data-layout-shell="desktop"] .sr-initial-render-guard {
      inset: var(--nav, 68px) 0 0 var(--dsb, 240px);
   }

   html.sr-user-ui-booting body[data-layout-shell="desktop"].collapsed-sidebar .sr-initial-render-guard {
      left: 76px;
   }

   html.sr-user-ui-booting body[data-layout-shell="mobile"] .sr-initial-render-guard {
      top: calc(var(--mob-top-h, 64px) + env(safe-area-inset-top, 0px));
      right: 0;
      bottom: calc(var(--mob-nav-h, 72px) + env(safe-area-inset-bottom, 0px));
      left: 0;
   }

   .sr-initial-render-spinner {
      width: 42px;
      height: 42px;
      border: 4px solid rgba(96, 165, 250, .2);
      border-top-color: var(--pur, #2563eb);
      border-right-color: var(--blu, #06b6d4);
      border-radius: 50%;
      animation: srInitialRenderSpin .72s linear infinite;
   }

   .sr-initial-render-guard span {
      max-width: min(80vw, 320px);
      font: 600 .82rem/1.45 "Poppins", Arial, sans-serif;
      letter-spacing: .01em;
   }

   @media (max-width: 991.98px) {
      html.sr-user-ui-booting body[data-layout-shell="desktop"] .sr-initial-render-guard {
         inset: var(--nav, 68px) 0 0;
      }
   }

   @media (prefers-reduced-motion: reduce) {
      .sr-initial-render-spinner {
         animation: none;
      }
   }

   @keyframes srInitialRenderSpin {
      to { transform: rotate(360deg); }
   }
</style>
<script data-sr-initial-render-guard-script>
   (function () {
      var root = document.documentElement;
      var revealed = false;
      var failSafeTimer = 0;

      root.classList.add('sr-user-ui-booting');

      function finishReveal() {
         document.body?.removeAttribute('aria-busy');
         if (revealed) return;
         revealed = true;
         window.clearTimeout(failSafeTimer);
         root.classList.remove('sr-user-ui-booting');

         var guard = document.getElementById('srInitialRenderGuard');
         if (guard) {
            guard.hidden = true;
            guard.setAttribute('aria-hidden', 'true');
         }
      }

      function revealAfterPaint() {
         var scheduleFrame = window.requestAnimationFrame || function (callback) {
            return window.setTimeout(callback, 16);
         };

         scheduleFrame(function () {
            scheduleFrame(finishReveal);
         });
      }

      function markBodyBusy() {
         if (document.body) document.body.setAttribute('aria-busy', 'true');
      }

      if (document.readyState === 'loading') {
         document.addEventListener('DOMContentLoaded', function () {
            markBodyBusy();
            revealAfterPaint();
         }, { once: true });
      } else {
         markBodyBusy();
         revealAfterPaint();
      }

      failSafeTimer = window.setTimeout(finishReveal, 20000);

      window.SpeakReadyInitialRenderGuard = {
         reveal: revealAfterPaint
      };
   })();
</script>
