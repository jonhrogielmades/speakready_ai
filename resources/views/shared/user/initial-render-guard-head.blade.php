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
      z-index: 14000;
   }

   html.sr-user-ui-booting body[data-app-surface="user"] .sr-initial-render-guard {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
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
