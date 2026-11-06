<style data-sr-mobile-portrait-only>
   #sr-portrait-overlay {
      display: none !important;
   }

   @media screen and (orientation: landscape) {
      body[data-layout-shell="mobile"] {
         height: 100vh !important;
         height: 100dvh !important;
         min-height: 0 !important;
         overflow: hidden !important;
      }

      body[data-layout-shell="mobile"] > :not(#sr-portrait-overlay) {
         display: none !important;
      }

      body[data-layout-shell="mobile"] > #sr-portrait-overlay {
         position: fixed !important;
         inset: 0 !important;
         z-index: 2147483647 !important;
         display: grid !important;
         place-items: center !important;
         width: 100vw !important;
         height: 100vh !important;
         height: 100dvh !important;
         padding: max(16px, env(safe-area-inset-top, 0px)) max(16px, env(safe-area-inset-right, 0px)) max(16px, env(safe-area-inset-bottom, 0px)) max(16px, env(safe-area-inset-left, 0px)) !important;
         overflow: auto !important;
         background: #071a33 !important;
         color: #f8fafc !important;
         font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif !important;
         text-align: center !important;
         box-sizing: border-box !important;
      }

      #sr-portrait-overlay .sr-portrait-card {
         width: min(100%, 430px);
         padding: 22px 26px;
         border: 1px solid rgba(125, 211, 252, .28);
         border-radius: 24px;
         background: #102744;
         box-shadow: 0 20px 55px rgba(0, 0, 0, .25);
      }

      #sr-portrait-overlay svg {
         display: block;
         width: 48px;
         height: 48px;
         margin: 0 auto 12px;
         fill: none;
         stroke: #67e8f9;
         stroke-width: 2;
         stroke-linecap: round;
         stroke-linejoin: round;
      }

      #sr-portrait-overlay h1 {
         margin: 0;
         color: #fff;
         font-size: clamp(1.25rem, 4vw, 1.65rem);
         font-weight: 700;
         line-height: 1.2;
      }

      #sr-portrait-overlay p {
         margin: 9px auto 0;
         color: #dbeafe;
         font-size: .95rem;
         line-height: 1.45;
      }
   }
</style>
<script>
   (function () {
      function lockPortrait() {
         var installed = navigator.standalone === true || (window.matchMedia && (
            window.matchMedia('(display-mode: standalone)').matches ||
            window.matchMedia('(display-mode: fullscreen)').matches ||
            window.matchMedia('(display-mode: minimal-ui)').matches
         ));
         if (!installed || !screen.orientation || typeof screen.orientation.lock !== 'function') return;

         try {
            var lock = screen.orientation.lock('portrait');
            if (lock && typeof lock.catch === 'function') lock.catch(function () {});
         } catch (error) {
            // The landscape blocker still works when the browser disallows locking.
         }
      }

      if (document.readyState === 'loading') {
         document.addEventListener('DOMContentLoaded', lockPortrait, { once: true });
      } else {
         lockPortrait();
      }
   })();
</script>
