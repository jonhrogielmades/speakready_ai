@php
   $srEarlyLayoutShell = ($serverLayout ?? 'desktop') === 'mobile' ? 'mobile' : 'desktop';
@endphp
<style data-sr-early-layout-shell-guard-style>
   html.sr-layout-shell-redirecting {
      background: #f1f6fb;
   }

   html.sr-layout-shell-redirecting::before {
      content: "";
      position: fixed;
      inset: 0;
      z-index: 2147483646;
      background: #f1f6fb;
   }

   html.sr-layout-shell-redirecting::after {
      content: "";
      position: fixed;
      top: 50%;
      left: 50%;
      z-index: 2147483647;
      width: 44px;
      height: 44px;
      margin: -22px 0 0 -22px;
      border: 4px solid rgba(37, 99, 235, 0.18);
      border-top-color: #2563eb;
      border-radius: 50%;
      animation: srEarlyShellGuardSpin .7s linear infinite;
   }

   html.sr-layout-shell-redirecting body {
      visibility: hidden !important;
   }

   @keyframes srEarlyShellGuardSpin {
      to { transform: rotate(360deg); }
   }

   @media (prefers-reduced-motion: reduce) {
      html.sr-layout-shell-redirecting::after {
         animation: none;
      }
   }
</style>
<script data-sr-early-layout-shell-guard="{{ $srEarlyLayoutShell }}">
   (function (serverLayout) {
      var maxMobileWidth = 820;
      var maxAge = 60 * 60 * 24 * 365;
      var repairKey = 'sr_layout_shell_guard_v4';
      var maxRepairAttempts = 2;

      function getViewportWidth() {
         return Math.round(
            (window.visualViewport && window.visualViewport.width) ||
            window.innerWidth ||
            document.documentElement.clientWidth ||
            screen.width ||
            0
         );
      }

      function isLikelyMobileDevice() {
         var userAgent = navigator.userAgent || '';
         var platform = navigator.platform || '';
         var uaDataMobile = navigator.userAgentData && navigator.userAgentData.mobile === true;
         var iPadOSDesktopUA = platform === 'MacIntel' && navigator.maxTouchPoints > 1;
         var touchCapable = (navigator.maxTouchPoints || 0) > 0 || 'ontouchstart' in window;
         var screenSizes = [
            screen.width,
            screen.height,
            screen.availWidth,
            screen.availHeight
         ].map(function (value) {
            return Math.round(Number(value) || 0);
         }).filter(function (value) {
            return value > 0;
         });
         var shortestScreenSide = screenSizes.length ? Math.min.apply(Math, screenSizes) : 0;
         var touchPhoneScreen = touchCapable && shortestScreenSide > 0 && shortestScreenSide <= maxMobileWidth;

         return Boolean(
            uaDataMobile ||
            iPadOSDesktopUA ||
            touchPhoneScreen ||
            /Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|webOS|Windows Phone|Silk\/|Kindle|Opera Mobi|Fennec|Macintosh.*Touch|SM-|Pixel|Nexus|FBAN|FBAV|Instagram|Line|Snapchat/i.test(userAgent)
         );
      }

      function setLayoutCookies(layout, width) {
         var secure = window.location.protocol === 'https:' ? '; Secure' : '';
         var suffix = '; path=/; max-age=' + maxAge + '; SameSite=Lax' + secure;

         document.cookie = 'sr_is_mobile=' + (layout === 'mobile' ? '1' : '0') + suffix;
         if (width > 0) {
            document.cookie = 'sr_viewport_width=' + width + suffix;
         }
      }

      function readStoredAttempt(signature) {
         try {
            var stored = JSON.parse(sessionStorage.getItem(repairKey) || 'null');
            if (!stored || stored.signature !== signature || Date.now() - Number(stored.savedAt || 0) > 15000) {
               return 0;
            }

            return Math.max(0, Number(stored.attempts) || 0);
         } catch (error) {
            return 0;
         }
      }

      function storeAttempt(signature, attempts) {
         try {
            sessionStorage.setItem(repairKey, JSON.stringify({
               signature: signature,
               attempts: attempts,
               savedAt: Date.now()
            }));
         } catch (error) {}
      }

      function clearStoredAttempt() {
         try {
            sessionStorage.removeItem(repairKey);
         } catch (error) {}
      }

      var width = getViewportWidth();
      var shouldUseMobile = (width > 0 && width <= maxMobileWidth) || isLikelyMobileDevice();
      var targetLayout = shouldUseMobile ? 'mobile' : 'desktop';

      setLayoutCookies(targetLayout, width);

      window.SpeakReadyLayoutShellGuard = {
         serverLayout: serverLayout,
         targetLayout: targetLayout,
         viewportWidth: width,
         redirecting: false
      };

      if (serverLayout === targetLayout) {
         clearStoredAttempt();
         return;
      }

      try {
         var url = new URL(window.location.href);
         var signature = serverLayout + '>' + targetLayout + '|' + url.pathname;
         var parsedQueryAttempt = parseInt(url.searchParams.get('sr_layout_fix') || '0', 10) || 0;
         var queryAttempt = url.searchParams.get('sr_layout') === targetLayout && parsedQueryAttempt <= maxRepairAttempts
            ? Math.max(0, parsedQueryAttempt)
            : 0;
         var attempts = Math.max(queryAttempt, readStoredAttempt(signature));

         if (attempts >= maxRepairAttempts) {
            clearStoredAttempt();
            return;
         }

         attempts += 1;
         storeAttempt(signature, attempts);
         document.documentElement.classList.add('sr-layout-shell-redirecting');
         window.SpeakReadyLayoutShellGuard.redirecting = true;

         url.searchParams.set('sr_layout', targetLayout);
         url.searchParams.set('sr_is_mobile', targetLayout === 'mobile' ? '1' : '0');
         url.searchParams.set('sr_layout_fix', String(attempts));
         if (width > 0) {
            url.searchParams.set('sr_viewport_width', String(width));
         }
         if (targetLayout === 'mobile') {
            url.searchParams.set('sr_mobile_desktop_blocked', '1');
         } else {
            url.searchParams.delete('sr_mobile_desktop_blocked');
         }

         window.location.replace(url.toString());
      } catch (error) {
         document.documentElement.classList.remove('sr-layout-shell-redirecting');
      }
   })(@json($srEarlyLayoutShell));
</script>
