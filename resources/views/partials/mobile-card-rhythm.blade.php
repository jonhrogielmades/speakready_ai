         /* ---- Consistent Mobile Card Rhythm ---- */
         #mob-content {
            width: 100% !important;
            --mob-page-x: 12px;
            --mob-card-gap: 12px;
            --mob-card-pad: 16px;
            --mob-card-radius: 14px;
         }
         #mob-content .db-content {
            padding: 12px var(--mob-page-x) 12px !important;
         }
         #mob-content .db-section,
         #mob-content .container,
         #mob-content .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: auto !important;
            margin-right: auto !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            box-sizing: border-box !important;
         }
         #mob-content .row:is(.g-1, .g-2, .g-3, .g-4, .g-5, .gx-1, .gx-2, .gx-3, .gx-4, .gx-5, .gy-1, .gy-2, .gy-3, .gy-4, .gy-5) {
            --bs-gutter-x: var(--mob-card-gap) !important;
            --bs-gutter-y: var(--mob-card-gap) !important;
         }
         #mob-content .row.g-0 {
            --bs-gutter-x: 0 !important;
            --bs-gutter-y: 0 !important;
         }
         #mob-content .row > * {
            min-width: 0 !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
         }
         #mob-content :is(
            .premium-card,
            .premium-panel,
            .setup-panel,
            .panel,
            .db-stat-card,
            .ll-stat-card,
            .ll-module-card,
            .module-card,
            .perk-card,
            .level-card,
            .print-card,
            .sr-card,
            .sr-stat-card,
            .tracker-panel,
            .chapter-card,
            .mod-hero,
            .ll-category-list,
            .retry-panel,
            .stat-box,
            .card
         ) {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            border-radius: var(--mob-card-radius) !important;
            box-sizing: border-box !important;
         }
         #mob-content :is(
            .premium-card,
            .premium-panel,
            .setup-panel,
            .panel,
            .db-stat-card,
            .ll-stat-card,
            .ll-module-card,
            .module-card,
            .perk-card,
            .level-card,
            .print-card,
            .sr-card,
            .sr-stat-card,
            .tracker-panel,
            .chapter-card,
            .mod-hero,
            .ll-category-list,
            .retry-panel,
            .stat-box
         ):not(.p-0):not(.accordion-item),
         #mob-content .card[style*="padding"]:not(.p-0):not(.accordion-item) {
            padding: var(--mob-card-pad) !important;
         }
         #mob-content :is(
            .premium-card,
            .premium-panel,
            .setup-panel,
            .panel,
            .db-stat-card,
            .ll-stat-card,
            .ll-module-card,
            .module-card,
            .perk-card,
            .level-card,
            .print-card,
            .sr-card,
            .sr-stat-card,
            .tracker-panel,
            .chapter-card,
            .mod-hero,
            .ll-category-list,
            .retry-panel,
            .stat-box,
            .card
         ):is(.mb-4, .my-4) {
            margin-bottom: var(--mob-card-gap) !important;
         }
         #mob-content :is(
            .premium-card,
            .premium-panel,
            .setup-panel,
            .panel,
            .db-stat-card,
            .ll-stat-card,
            .ll-module-card,
            .module-card,
            .perk-card,
            .level-card,
            .print-card,
            .sr-card,
            .sr-stat-card,
            .tracker-panel,
            .chapter-card,
            .mod-hero,
            .ll-category-list,
            .retry-panel,
            .stat-box,
            .card
         ):is(.mt-4, .my-4) {
            margin-top: var(--mob-card-gap) !important;
         }
         #mob-content .card-body:not(.p-0),
         #mob-content .accordion-body:not(.p-0) {
            padding: var(--mob-card-pad) !important;
         }
         #mob-content .accordion-item.premium-panel,
         #mob-content .accordion-item.card {
            padding: 0 !important;
            overflow: hidden !important;
         }
         #mob-content .accordion-button {
            padding: var(--mob-card-pad) !important;
         }

         @media (max-width: 575px) {
            #mob-content {
               --mob-page-x: 12px;
               --mob-card-gap: 12px;
               --mob-card-pad: 14px;
            }
            #mob-content :is(
               .premium-card,
               .premium-panel,
               .setup-panel,
               .panel,
               .db-stat-card,
               .ll-stat-card,
               .ll-module-card,
               .module-card,
               .perk-card,
               .level-card,
               .print-card,
               .sr-card,
               .sr-stat-card,
               .tracker-panel,
               .chapter-card,
               .mod-hero,
               .ll-category-list,
               .retry-panel,
               .stat-box
            ):not(.p-0):not(.accordion-item),
            #mob-content .card[style*="padding"]:not(.p-0):not(.accordion-item) {
               padding: var(--mob-card-pad) !important;
            }
         }

         @media (max-width: 380px) {
            #mob-content {
               --mob-page-x: 10px;
               --mob-card-gap: 10px;
               --mob-card-pad: 12px;
            }
            #mob-content .db-content {
               padding: 10px var(--mob-page-x) 10px !important;
            }
         }
