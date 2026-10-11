<script>
   (function () {
      var maxMobileWidth = 820;
      var maxAge = 60 * 60 * 24 * 365;
      var sameSite = '; SameSite=Lax';
      var repairKey = 'sr_desktop_shell_mobile_repair_v3';
      var alertKey = 'sr_desktop_site_mobile_alert_v1';

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
            /Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|webOS|Windows Phone|Silk\/|Kindle|Opera Mobi|Fennec|Macintosh.*Touch|SM-|Pixel|Nexus/i.test(userAgent)
         );
      }

      function showMobileOnlyAlertOnce() {
         try {
            if (sessionStorage.getItem(alertKey) === '1') return;
            sessionStorage.setItem(alertKey, '1');
         } catch (error) {}

         if (typeof window.alert === 'function') {
            window.alert('Desktop site is not available on mobile. Loading the mobile layout.');
         }
      }

      var width = getViewportWidth();
      var likelyMobileDevice = isLikelyMobileDevice();
      var shouldUseMobileShell = (width > 0 && width <= maxMobileWidth) || likelyMobileDevice;
      if (!shouldUseMobileShell) return;

      if (likelyMobileDevice) {
         showMobileOnlyAlertOnce();
      }
      document.cookie = 'sr_is_mobile=1; path=/; max-age=' + maxAge + sameSite;
      if (width > 0) {
         document.cookie = 'sr_viewport_width=' + width + '; path=/; max-age=' + maxAge + sameSite;
      }

      try {
         var url = new URL(window.location.href);
         var alreadyRequestedMobile = url.searchParams.get('sr_layout') === 'mobile';
         var repairCount = parseInt(sessionStorage.getItem(repairKey) || '0', 10);

         if (!alreadyRequestedMobile || repairCount < 2) {
            sessionStorage.setItem(repairKey, String(repairCount + 1));
            url.searchParams.set('sr_layout', 'mobile');
            url.searchParams.set('sr_is_mobile', '1');
            url.searchParams.set('sr_mobile_desktop_blocked', '1');
            if (width > 0) {
               url.searchParams.set('sr_viewport_width', String(width));
            }
            if (alreadyRequestedMobile) {
               url.searchParams.set('sr_layout_fix', String(Date.now()));
            }
            window.location.replace(url.toString());
         }
      } catch (error) {}
   })();
</script>
