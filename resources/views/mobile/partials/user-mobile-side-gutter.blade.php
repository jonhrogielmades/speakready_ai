<style data-user-mobile-side-gutter="10px">
   @media (max-width: 991.98px) {
      body.user-mobile-shell {
         --sr-user-mobile-side-gutter: 10px;
         --sr-mobile-inline: 10px !important;
         --sr-user-mobile-gutter-left: max(var(--sr-user-mobile-side-gutter), env(safe-area-inset-left, 0px));
         --sr-user-mobile-gutter-right: max(var(--sr-user-mobile-side-gutter), env(safe-area-inset-right, 0px));
      }

      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) {
         width: 100% !important;
         max-width: 100% !important;
         padding-left: var(--sr-user-mobile-gutter-left) !important;
         padding-right: var(--sr-user-mobile-gutter-right) !important;
         box-sizing: border-box !important;
      }

      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) > .sr-user-page-root,
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) [data-user-mobile-page-root="true"],
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) > :is(.db-section, .container, .container-fluid, [id], main, section),
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) :is(.db-section, .container, .container-fluid),
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) :is(
            .feedback-shell,
            .review-shell,
            #portfolioReport,
            #sec-progress-tracking,
            #practice-calendar-page,
            #interview-modules-page,
            #module-detail-page,
            #learning-games-page,
            #ai-coach-page,
            #notifications-page,
            #voice-rehearsal-page,
            #sec-learning-game-session,
            #sec-interview-setup,
            #account-page,
            #job-tracker-page
         ) {
         width: 100% !important;
         max-width: 100% !important;
         padding-left: 0 !important;
         padding-right: 0 !important;
         margin-left: 0 !important;
         margin-right: 0 !important;
         box-sizing: border-box !important;
      }

      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) > .row,
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) > .sr-user-page-root > .row,
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) [data-user-mobile-page-root="true"] > .row,
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) :is(
            .db-section,
            .container,
            .container-fluid,
            .feedback-shell,
            .review-shell,
            #portfolioReport,
            #sec-progress-tracking,
            #practice-calendar-page,
            #interview-modules-page,
            #module-detail-page,
            #learning-games-page,
            #ai-coach-page,
            #notifications-page,
            #voice-rehearsal-page,
            #sec-learning-game-session,
            #sec-interview-setup,
            #account-page,
            #job-tracker-page
         ) > .row {
         margin-left: 0 !important;
         margin-right: 0 !important;
      }

      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) > .row > [class*="col-"],
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) > .sr-user-page-root > .row > [class*="col-"],
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) [data-user-mobile-page-root="true"] > .row > [class*="col-"],
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) :is(
            .db-section,
            .container,
            .container-fluid,
            .feedback-shell,
            .review-shell,
            #portfolioReport,
            #sec-progress-tracking,
            #practice-calendar-page,
            #interview-modules-page,
            #module-detail-page,
            #learning-games-page,
            #ai-coach-page,
            #notifications-page,
            #voice-rehearsal-page,
            #sec-learning-game-session,
            #sec-interview-setup,
            #account-page,
            #job-tracker-page
         ) > .row > [class*="col-"] {
         padding-left: 0 !important;
         padding-right: 0 !important;
      }
   }
</style>
<script data-user-mobile-layout-system="true">
   (function () {
      var existing = window.SpeakReadyUserMobileLayout;
      var pageRootSelectors = [
         '.db-section',
         '.container',
         '.container-fluid',
         'main',
         'section',
         '[id]'
      ];
      var namedPageRootSelectors = [
         '.feedback-shell',
         '.review-shell',
         '#portfolioReport',
         '#sec-progress-tracking',
         '#practice-calendar-page',
         '#interview-modules-page',
         '#module-detail-page',
         '#learning-games-page',
         '#ai-coach-page',
         '#notifications-page',
         '#voice-rehearsal-page',
         '#sec-learning-game-session',
         '#sec-interview-setup',
         '#account-page',
         '#job-tracker-page'
      ];
      var allRootSelectors = pageRootSelectors.concat(namedPageRootSelectors);

      function setImportant(element, property, value) {
         if (!element || !element.style) return;
         element.style.setProperty(property, value, 'important');
      }

      function getContentRoot(root) {
         if (root && root.nodeType === 1) {
            if (root.matches && root.matches('[data-user-ajax-content]')) return root;
            var scoped = root.querySelector && root.querySelector('[data-user-ajax-content]');
            if (scoped) return scoped;
         }

         return document.getElementById('userAppContent') || document.querySelector('[data-user-ajax-content]');
      }

      function isPageRoot(element) {
         return element && element.matches && allRootSelectors.some(function (selector) {
            return element.matches(selector);
         });
      }

      function normalizePageRoot(element) {
         if (!element || element.closest('.modal, .dropdown-menu, #mobProfileDropdown, #mobNotifDropdown, #mob-bottom-nav, #mob-header')) return;

         element.classList.add('sr-user-page-root');
         element.setAttribute('data-user-mobile-page-root', 'true');
         setImportant(element, 'width', '100%');
         setImportant(element, 'max-width', '100%');
         setImportant(element, 'padding-left', '0');
         setImportant(element, 'padding-right', '0');
         setImportant(element, 'margin-left', '0');
         setImportant(element, 'margin-right', '0');
         setImportant(element, 'box-sizing', 'border-box');

         Array.from(element.children || []).forEach(function (child) {
            if (child.classList && child.classList.contains('row')) {
               setImportant(child, 'margin-left', '0');
               setImportant(child, 'margin-right', '0');
               Array.from(child.children || []).forEach(function (column) {
                  if (column.matches && column.matches('[class*="col-"]')) {
                     setImportant(column, 'padding-left', '0');
                     setImportant(column, 'padding-right', '0');
                  }
               });
            }
         });
      }

      function refresh(root) {
         if (!document.body || !document.body.classList.contains('user-mobile-shell')) return;

         var content = getContentRoot(root);
         if (!content) return;

         content.setAttribute('data-user-mobile-layout-root', 'true');
         setImportant(content, 'width', '100%');
         setImportant(content, 'max-width', '100%');
         setImportant(content, 'padding-left', 'var(--sr-user-mobile-gutter-left)');
         setImportant(content, 'padding-right', 'var(--sr-user-mobile-gutter-right)');
         setImportant(content, 'box-sizing', 'border-box');

         Array.from(content.children || []).forEach(function (child) {
            if (isPageRoot(child)) normalizePageRoot(child);
         });

         namedPageRootSelectors.forEach(function (selector) {
            Array.from(content.querySelectorAll(selector)).forEach(normalizePageRoot);
         });
      }

      if (existing && existing.version) {
         existing.refresh();
         return;
      }

      window.SpeakReadyUserMobileLayout = {
         version: '1.0.0',
         refresh: refresh,
         normalizePageRoot: normalizePageRoot
      };

      if (document.readyState === 'loading') {
         document.addEventListener('DOMContentLoaded', function () {
            refresh();
         }, { once: true });
      } else {
         refresh();
      }

      window.addEventListener('pageshow', function () {
         refresh();
      });

      document.addEventListener('speakready:user-content-updated', function (event) {
         refresh(event.detail && event.detail.root);
      });
   })();
</script>
