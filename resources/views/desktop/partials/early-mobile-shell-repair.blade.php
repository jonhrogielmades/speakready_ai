<script>
   (function () {
      var maxMobileWidth = 820;
      var maxAge = 60 * 60 * 24 * 365;
      var sameSite = '; SameSite=Lax';
      var repairKey = 'sr_desktop_shell_mobile_repair_v2';

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

         return Boolean(
            uaDataMobile ||
            iPadOSDesktopUA ||
            /Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|webOS|Windows Phone|Silk\/|Kindle|Opera Mobi|Fennec|Macintosh.*Touch|SM-|Pixel|Nexus/i.test(userAgent)
         );
      }

      var width = getViewportWidth();
      var shouldUseMobileShell = (width > 0 && width <= maxMobileWidth) || isLikelyMobileDevice();
      if (!shouldUseMobileShell) return;

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
