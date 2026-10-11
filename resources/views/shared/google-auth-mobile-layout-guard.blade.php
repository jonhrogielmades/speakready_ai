<script>
   (function () {
      var maxMobileWidth = 820;

      function viewportWidth() {
         return Math.round(
            (window.visualViewport && window.visualViewport.width) ||
            window.innerWidth ||
            document.documentElement.clientWidth ||
            screen.width ||
            0
         );
      }

      function isStandalonePwa() {
         return Boolean(
            (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
            window.navigator.standalone === true
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
            /Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|webOS|Windows Phone|Silk\/|Kindle|Opera Mobi|Fennec|Macintosh.*Touch|SM-|Pixel|Nexus/i.test(userAgent)
         );
      }

      function shouldUseMobileGoogleLayout() {
         var width = viewportWidth();

         return isStandalonePwa() || (width > 0 && width <= maxMobileWidth) || isLikelyMobileDevice();
      }

      function mobileGoogleAuthHref(href) {
         if (!shouldUseMobileGoogleLayout()) {
            return href;
         }

         try {
            var width = viewportWidth();
            var url = new URL(href, window.location.origin);
            url.searchParams.set('sr_layout', 'mobile');
            url.searchParams.set('sr_is_mobile', '1');
            if (width > 0) {
               url.searchParams.set('sr_viewport_width', String(width));
            }

            return url.toString();
         } catch (error) {
            return href;
         }
      }

      function applyMobileGoogleAuthHref(link) {
         if (!link) {
            return '';
         }

         var href = mobileGoogleAuthHref(link.href);
         link.href = href;

         return href;
      }

      window.SpeakReadyGoogleMobileLayout = {
         shouldUseMobileLayout: shouldUseMobileGoogleLayout,
         withMobileLayoutHint: mobileGoogleAuthHref,
         apply: applyMobileGoogleAuthHref
      };

      document.addEventListener('DOMContentLoaded', function () {
         if (!shouldUseMobileGoogleLayout()) {
            return;
         }

         document.querySelectorAll('a[data-auth-transition^="google"]').forEach(function (link) {
            applyMobileGoogleAuthHref(link);
         });
      });
   })();
</script>
