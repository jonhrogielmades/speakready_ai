<script>
   (function () {
      var maxMobileWidth = 820;
      var width = Math.round(window.innerWidth || document.documentElement.clientWidth || screen.width || 0);
      var isMobileViewport = width > 0 && width <= maxMobileWidth;
      var maxAge = 60 * 60 * 24 * 365;
      var sameSite = '; SameSite=Lax';

      document.cookie = 'sr_is_mobile=' + (isMobileViewport ? '1' : '0') + '; path=/; max-age=' + maxAge + sameSite;
      if (width > 0) {
         document.cookie = 'sr_viewport_width=' + width + '; path=/; max-age=' + maxAge + sameSite;
      }

      var hasMobileShell = Boolean(document.getElementById('mob-content'));
      var hasDesktopShell = Boolean(document.querySelector('.db-sidebar'));
      var shouldUseMobileShell = isMobileViewport && hasDesktopShell && !hasMobileShell;
      var shouldUseDesktopShell = !isMobileViewport && hasMobileShell;
      var reloadKey = 'sr_layout_reload_' + (isMobileViewport ? 'mobile' : 'desktop');

      if ((shouldUseMobileShell || shouldUseDesktopShell) && !sessionStorage.getItem(reloadKey)) {
         sessionStorage.setItem(reloadKey, '1');
         window.location.reload();
      }
   })();
</script>
