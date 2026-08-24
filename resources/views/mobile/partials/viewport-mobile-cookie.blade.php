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

      var body = document.body;
      var declaredShell = body ? body.getAttribute('data-layout-shell') : '';
      var bodyClasses = body ? body.classList : null;
      var hasMobileShell = Boolean(
         document.getElementById('mob-content') ||
         declaredShell === 'mobile' ||
         (bodyClasses && (bodyClasses.contains('mobile-shell') || bodyClasses.contains('guest-mobile-shell')))
      );
      var hasDesktopShell = Boolean(
         document.querySelector('.db-sidebar') ||
         declaredShell === 'desktop' ||
         (bodyClasses && (bodyClasses.contains('desktop-shell') || bodyClasses.contains('guest-desktop-shell')))
      );
      var shouldUseMobileShell = isMobileViewport && hasDesktopShell && !hasMobileShell;
      var shouldUseDesktopShell = !isMobileViewport && hasMobileShell;
      var reloadKey = 'sr_layout_reload_' + (isMobileViewport ? 'mobile' : 'desktop');

      if (shouldUseMobileShell || shouldUseDesktopShell) {
         var targetLayout = isMobileViewport ? 'mobile' : 'desktop';
         try {
            var url = new URL(window.location.href);
            var targetWidth = width > 0 ? String(width) : '';
            var needsUrlHint = url.searchParams.get('sr_layout') !== targetLayout ||
               (targetWidth && url.searchParams.get('sr_viewport_width') !== targetWidth);

            if (needsUrlHint) {
               url.searchParams.set('sr_layout', targetLayout);
               url.searchParams.set('sr_is_mobile', isMobileViewport ? '1' : '0');
               if (targetWidth) {
                  url.searchParams.set('sr_viewport_width', targetWidth);
               }
               window.location.replace(url.toString());
               return;
            }
         } catch (error) {}
      }

      if ((shouldUseMobileShell || shouldUseDesktopShell) && !sessionStorage.getItem(reloadKey)) {
         sessionStorage.setItem(reloadKey, '1');
         window.location.reload();
      }
   })();
</script>
