<style data-user-mobile-side-gutter="10px">
   @media (max-width: 991.98px) {
      body.user-mobile-shell {
         --sr-mobile-inline: 10px !important;
         --sr-user-mobile-gutter-left: max(10px, env(safe-area-inset-left, 0px));
         --sr-user-mobile-gutter-right: max(10px, env(safe-area-inset-right, 0px));
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
          body.user-mobile-shell #mob-content > .db-content) > :is(.db-section, .container, .container-fluid, [id], main, section),
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) :is(.db-section, .container, .container-fluid),
      :is(body.user-mobile-shell > .db-content,
          body.user-mobile-shell #mob-content > .db-content) :is(
            .feedback-shell,
            .review-shell,
            #portfolioReport,
            #sec-progress-tracking,
            #practice-plan-page,
            #practice-calendar-page,
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
          body.user-mobile-shell #mob-content > .db-content) :is(
            .db-section,
            .container,
            .container-fluid,
            .feedback-shell,
            .review-shell,
            #portfolioReport,
            #sec-progress-tracking,
            #practice-plan-page,
            #practice-calendar-page,
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
          body.user-mobile-shell #mob-content > .db-content) :is(
            .db-section,
            .container,
            .container-fluid,
            .feedback-shell,
            .review-shell,
            #portfolioReport,
            #sec-progress-tracking,
            #practice-plan-page,
            #practice-calendar-page,
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
