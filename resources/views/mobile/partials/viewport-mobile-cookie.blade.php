<script>
   (function () {
      var maxMobileWidth = 820;
      var maxAge = 60 * 60 * 24 * 365;
      var sameSite = '; SameSite=Lax';

      function getViewportWidth() {
         return Math.round(
            (window.visualViewport && window.visualViewport.width) ||
            window.innerWidth ||
            document.documentElement.clientWidth ||
            screen.width ||
            0
         );
      }

      function syncViewportMetrics() {
         var height = Math.round(
            (window.visualViewport && window.visualViewport.height) ||
            window.innerHeight ||
            document.documentElement.clientHeight ||
            screen.height ||
            0
         );

         if (height > 0) {
            document.documentElement.style.setProperty('--sr-js-vh', height + 'px');
         }
      }

      function isLikelyMobileDevice() {
         var userAgent = navigator.userAgent || '';
         var platform = navigator.platform || '';
         var uaDataMobile = navigator.userAgentData && navigator.userAgentData.mobile === true;
         var iPadOSDesktopUA = platform === 'MacIntel' && navigator.maxTouchPoints > 1;

         return Boolean(
            uaDataMobile ||
            iPadOSDesktopUA ||
            /Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|webOS|Windows Phone|Silk\/|Kindle|Opera Mobi|Fennec|Macintosh.*Touch|SM-|Pixel|Nexus/i.test(userAgent)
         );
      }

      function syncLayout() {
         var width = getViewportWidth();
         var isMobileViewport = width > 0 && width <= maxMobileWidth;
         var shouldUseMobileExperience = isMobileViewport || isLikelyMobileDevice();

         document.cookie = 'sr_is_mobile=' + (shouldUseMobileExperience ? '1' : '0') + '; path=/; max-age=' + maxAge + sameSite;
         if (width > 0) {
            document.cookie = 'sr_viewport_width=' + width + '; path=/; max-age=' + maxAge + sameSite;
         }

         var body = document.body;
         var declaredShell = body ? body.getAttribute('data-layout-shell') : '';
         var bodyClasses = body ? body.classList : null;
         var hasMobileShell = Boolean(
            document.getElementById('mob-content') ||
            declaredShell === 'mobile' ||
            (bodyClasses && (bodyClasses.contains('mobile-shell') || bodyClasses.contains('guest-mobile-shell') || bodyClasses.contains('auth-mobile-shell')))
         );
         var hasDesktopShell = Boolean(
            document.querySelector('.db-sidebar') ||
            declaredShell === 'desktop' ||
            (bodyClasses && (bodyClasses.contains('desktop-shell') || bodyClasses.contains('guest-desktop-shell') || bodyClasses.contains('auth-desktop-shell')))
         );
         var shouldUseMobileShell = shouldUseMobileExperience && hasDesktopShell && !hasMobileShell;
         var shouldUseDesktopShell = !shouldUseMobileExperience && hasMobileShell;
         var reloadKey = 'sr_layout_reload_' + (shouldUseMobileExperience ? 'mobile' : 'desktop');

         if (shouldUseMobileShell || shouldUseDesktopShell) {
            var targetLayout = shouldUseMobileExperience ? 'mobile' : 'desktop';
            try {
               var url = new URL(window.location.href);
               var targetWidth = width > 0 ? String(width) : '';
               var needsUrlHint = url.searchParams.get('sr_layout') !== targetLayout ||
                  (targetWidth && url.searchParams.get('sr_viewport_width') !== targetWidth);

               if (needsUrlHint) {
                  url.searchParams.set('sr_layout', targetLayout);
                  url.searchParams.set('sr_is_mobile', shouldUseMobileExperience ? '1' : '0');
                  if (targetWidth) {
                     url.searchParams.set('sr_viewport_width', targetWidth);
                  }
                  window.location.replace(url.toString());
                  return true;
               }
            } catch (error) {}
         }

         if ((shouldUseMobileShell || shouldUseDesktopShell) && !sessionStorage.getItem(reloadKey)) {
            sessionStorage.setItem(reloadKey, '1');
            window.location.reload();
            return true;
         }

         return false;
      }

      syncViewportMetrics();
      syncLayout();

      var resizeTimer;
      function queueSync() {
         window.clearTimeout(resizeTimer);
         resizeTimer = window.setTimeout(function () {
            syncViewportMetrics();
            syncLayout();
         }, 220);
      }

      window.addEventListener('resize', queueSync, { passive: true });
      window.addEventListener('orientationchange', queueSync, { passive: true });
      if (window.visualViewport) {
         window.visualViewport.addEventListener('resize', queueSync, { passive: true });
      }
   })();
</script>
