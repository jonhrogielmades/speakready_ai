<style>
   body:is(.user-desktop-shell, .user-mobile-shell) {
      --sr-user-readable-title: var(--tx);
      --sr-user-readable-copy: var(--tx2);
      --sr-user-readable-muted: color-mix(in srgb, var(--tx2) 72%, var(--tx) 28%);
      --sr-user-readable-surface: var(--bg2);
      --sr-user-readable-field: var(--bg3);
      --sr-user-readable-border: var(--bd);
   }

   body.user-desktop-shell #dashboard #userAppContent,
   body.user-mobile-shell #mob-content #userAppContent {
      color: var(--sr-user-readable-title) !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(
      h1, h2, h3, h4, h5, h6,
      .fw-bold,
      strong,
      .text-dark,
      .text-gradient-primary,
      .gradient-text,
      [class*="text-gradient"],
      [class*="page-title"],
      [class*="hero-title"],
      [class*="heading"],
      [class*="-title"],
      [class$="-name"]
   ),
   body.user-mobile-shell #mob-content #userAppContent :is(
      h1, h2, h3, h4, h5, h6,
      .fw-bold,
      strong,
      .text-dark,
      .text-gradient-primary,
      .gradient-text,
      [class*="text-gradient"],
      [class*="page-title"],
      [class*="hero-title"],
      [class*="heading"],
      [class*="-title"],
      [class$="-name"]
   ) {
      color: var(--sr-user-readable-title) !important;
      -webkit-text-fill-color: var(--sr-user-readable-title) !important;
      text-shadow: none !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(
      p, li, dt, dd, td, th,
      label,
      legend,
      .form-label,
      .card-text,
      .small,
      small,
      .text-muted,
      .text-secondary,
      .text-body-secondary,
      [class*="subtitle"],
      [class*="description"],
      [class*="caption"],
      [class*="meta"],
      [class*="copy"],
      [class*="hint"],
      [class*="label"],
      [class*="message"],
      [class*="empty"]
   ),
   body.user-mobile-shell #mob-content #userAppContent :is(
      p, li, dt, dd, td, th,
      label,
      legend,
      .form-label,
      .card-text,
      .small,
      small,
      .text-muted,
      .text-secondary,
      .text-body-secondary,
      [class*="subtitle"],
      [class*="description"],
      [class*="caption"],
      [class*="meta"],
      [class*="copy"],
      [class*="hint"],
      [class*="label"],
      [class*="message"],
      [class*="empty"]
   ) {
      color: var(--sr-user-readable-copy) !important;
      -webkit-text-fill-color: var(--sr-user-readable-copy) !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(.text-muted, .text-secondary, .text-body-secondary, small, .small),
   body.user-mobile-shell #mob-content #userAppContent :is(.text-muted, .text-secondary, .text-body-secondary, small, .small) {
      color: var(--sr-user-readable-muted) !important;
      -webkit-text-fill-color: var(--sr-user-readable-muted) !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(
      .premium-panel,
      .premium-card,
      .setup-panel,
      .panel,
      .card,
      .module-card,
      .print-card,
      .perk-card,
      .ll-stat-card,
      .ll-module-card,
      .level-card,
      .db-stat-card,
      .stat-card,
      .sr-card,
      .sr-stat-card,
      .tracker-panel,
      .tracker-card,
      .category-card,
      .account-card,
      .leaderboard-card,
      .report-card,
      .notification-card,
      .notification-item,
      .accordion,
      .accordion-item,
      .list-group-item,
      .modal-content,
      .table-responsive
   ),
   body.user-mobile-shell #mob-content #userAppContent :is(
      .premium-panel,
      .premium-card,
      .setup-panel,
      .panel,
      .card,
      .module-card,
      .print-card,
      .perk-card,
      .ll-stat-card,
      .ll-module-card,
      .level-card,
      .db-stat-card,
      .stat-card,
      .sr-card,
      .sr-stat-card,
      .tracker-panel,
      .tracker-card,
      .category-card,
      .account-card,
      .leaderboard-card,
      .report-card,
      .notification-card,
      .notification-item,
      .accordion,
      .accordion-item,
      .list-group-item,
      .modal-content,
      .table-responsive
   ) {
      color: var(--sr-user-readable-title) !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(input, select, textarea, .form-control, .form-select, .oinp, .tracker-field),
   body.user-mobile-shell #mob-content #userAppContent :is(input, select, textarea, .form-control, .form-select, .oinp, .tracker-field) {
      color: var(--sr-user-readable-title) !important;
      -webkit-text-fill-color: currentColor !important;
      background-color: var(--sr-user-readable-field) !important;
      border-color: var(--sr-user-readable-border) !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(input, textarea, .form-control, .oinp, .tracker-field)::placeholder,
   body.user-mobile-shell #mob-content #userAppContent :is(input, textarea, .form-control, .oinp, .tracker-field)::placeholder {
      color: var(--sr-user-readable-muted) !important;
      -webkit-text-fill-color: var(--sr-user-readable-muted) !important;
      font-weight: 400 !important;
      opacity: 1 !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(table, .table, .custom-table, .db-table),
   body.user-mobile-shell #mob-content #userAppContent :is(table, .table, .custom-table, .db-table) {
      --bs-table-color: var(--sr-user-readable-copy) !important;
      --bs-table-hover-color: var(--sr-user-readable-title) !important;
      color: var(--sr-user-readable-copy) !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(table, .table, .custom-table, .db-table) :is(th, th *),
   body.user-mobile-shell #mob-content #userAppContent :is(table, .table, .custom-table, .db-table) :is(th, th *) {
      color: var(--sr-user-readable-title) !important;
      -webkit-text-fill-color: var(--sr-user-readable-title) !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(table, .table, .custom-table, .db-table) :is(td, td *),
   body.user-mobile-shell #mob-content #userAppContent :is(table, .table, .custom-table, .db-table) :is(td, td *) {
      color: var(--sr-user-readable-copy) !important;
      -webkit-text-fill-color: var(--sr-user-readable-copy) !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(
      .btn,
      .btn *,
      button,
      button *,
      [role="button"],
      [role="button"] *,
      .badge,
      .badge *,
      [class*="badge"],
      [class*="badge"] *,
      .pill,
      .pill *,
      [class*="pill"],
      [class*="pill"] *,
      .tag,
      .tag *,
      [class*="tag"],
      [class*="tag"] *,
      .alert,
      .alert *,
      .dropdown-item,
      .dropdown-item *,
      .dropdown-menu,
      .dropdown-menu *,
      .nav-link,
      .nav-link *,
      .pagination,
      .pagination *,
      .text-primary,
      .text-info,
      .text-success,
      .text-danger,
      .text-warning,
      .text-white,
      [class*="bg-primary"],
      [class*="bg-success"],
      [class*="bg-danger"],
      [class*="bg-warning"],
      [class*="bg-info"],
      [class*="bg-dark"]
   ),
   body.user-mobile-shell #mob-content #userAppContent :is(
      .btn,
      .btn *,
      button,
      button *,
      [role="button"],
      [role="button"] *,
      .badge,
      .badge *,
      [class*="badge"],
      [class*="badge"] *,
      .pill,
      .pill *,
      [class*="pill"],
      [class*="pill"] *,
      .tag,
      .tag *,
      [class*="tag"],
      [class*="tag"] *,
      .alert,
      .alert *,
      .dropdown-item,
      .dropdown-item *,
      .dropdown-menu,
      .dropdown-menu *,
      .nav-link,
      .nav-link *,
      .pagination,
      .pagination *,
      .text-primary,
      .text-info,
      .text-success,
      .text-danger,
      .text-warning,
      .text-white,
      [class*="bg-primary"],
      [class*="bg-success"],
      [class*="bg-danger"],
      [class*="bg-warning"],
      [class*="bg-info"],
      [class*="bg-dark"]
   ) {
      -webkit-text-fill-color: currentColor !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(.btn *, button *, [role="button"] *, .badge *, [class*="badge"] *, .pill *, [class*="pill"] *, .tag *, [class*="tag"] *, .alert *, .dropdown-item *, .nav-link *, .pagination *),
   body.user-mobile-shell #mob-content #userAppContent :is(.btn *, button *, [role="button"] *, .badge *, [class*="badge"] *, .pill *, [class*="pill"] *, .tag *, [class*="tag"] *, .alert *, .dropdown-item *, .nav-link *, .pagination *) {
      color: inherit !important;
   }

   body:is(.user-desktop-shell, .user-mobile-shell) :is(#dashboard #userAppContent, #mob-content #userAppContent) .progress-export-btn :is(i, i::before) {
      -webkit-text-fill-color: currentColor !important;
   }

   body:is(.user-desktop-shell, .user-mobile-shell) :is(#dashboard #userAppContent, #mob-content #userAppContent) .progress-export-btn i {
      background: #ffffff !important;
      border: 1px solid rgba(255, 255, 255, 0.9) !important;
      box-shadow: inset 0 1px 0 rgba(15, 23, 42, 0.08), 0 4px 10px rgba(15, 23, 42, 0.12) !important;
      color: currentColor !important;
   }

   body:is(.user-desktop-shell, .user-mobile-shell) :is(#dashboard #userAppContent, #mob-content #userAppContent) .progress-export-btn.pdf i {
      color: #1d4ed8 !important;
   }

   body:is(.user-desktop-shell, .user-mobile-shell) :is(#dashboard #userAppContent, #mob-content #userAppContent) .progress-export-btn.excel i {
      color: #047857 !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(.chat-bubble, .bubble-ai),
   body.user-mobile-shell #mob-content #userAppContent :is(.chat-bubble, .bubble-ai) {
      color: var(--sr-user-readable-title) !important;
      -webkit-text-fill-color: currentColor !important;
   }

   body.user-desktop-shell #dashboard #userAppContent :is(.bubble-user, .bubble-user *),
   body.user-mobile-shell #mob-content #userAppContent :is(.bubble-user, .bubble-user *) {
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
   }

   body.user-mobile-shell #mob-content #userAppContent :is(
      .sr-hero-card,
      .sr-page-hero,
      .progress-hero,
      .feedback-hero,
      .setup-hero,
      .modules-hero,
      .vr-hero,
      .mission-progress-hero,
      .sr-learning-hero,
      .coach-progress-hero,
      .mastery-hero-card,
      .notif-hero,
      .skill-tree-hero,
      .mod-hero
   ) :is(
      h1,
      h2,
      h3,
      h4,
      .sr-title,
      .sr-title-name,
      .sr-page-hero-title,
      .progress-hero-title,
      .feedback-title,
      .setup-hero-title,
      .modules-hero-title,
      .mastery-title,
      .notif-hero-title
   ) {
      color: var(--sr-unified-hero-text, #f8fbff) !important;
      -webkit-text-fill-color: var(--sr-unified-hero-text, #f8fbff) !important;
   }

   body.user-mobile-shell #mob-content #userAppContent :is(
      .sr-hero-card,
      .sr-page-hero,
      .progress-hero,
      .feedback-hero,
      .setup-hero,
      .modules-hero,
      .vr-hero,
      .mission-progress-hero,
      .sr-learning-hero,
      .coach-progress-hero,
      .mastery-hero-card,
      .notif-hero,
      .skill-tree-hero,
      .mod-hero
   ) :is(
      p,
      .sr-subtitle,
      .sr-page-hero-subtitle,
      .progress-hero-subtitle,
      .feedback-subtitle,
      .setup-hero-subtitle,
      .modules-hero-subtitle,
      .mastery-subtitle,
      .notif-hero-subtitle
   ) {
      color: var(--sr-unified-hero-muted, rgba(248, 251, 255, 0.9)) !important;
      -webkit-text-fill-color: var(--sr-unified-hero-muted, rgba(248, 251, 255, 0.9)) !important;
   }

   body.user-mobile-shell #mob-content #userAppContent .sr-hero-card .sr-subtitle .sr-subtitle-accent {
      color: #fde047 !important;
      -webkit-text-fill-color: #fde047 !important;
   }

   body.user-mobile-shell #mob-content #userAppContent .sr-hero-card .sr-subtitle .sr-subtitle-accent.is-sky {
      color: #7dd3fc !important;
      -webkit-text-fill-color: #7dd3fc !important;
   }

   body.user-mobile-shell #mob-content #userAppContent .sr-hero-card .sr-subtitle .sr-subtitle-accent.is-mint {
      color: #86efac !important;
      -webkit-text-fill-color: #86efac !important;
   }

   body.user-mobile-shell #mob-content #userAppContent .sr-hero-image-panel :is(.sr-image-brand, .sr-image-title, .sr-image-title span, .sr-image-copy) {
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
   }

   body.user-mobile-shell #mob-content #userAppContent .sr-hero-image-panel :is(.sr-image-brand span, .sr-image-title strong) {
      color: #16f4df !important;
      -webkit-text-fill-color: #16f4df !important;
   }

   body:is(.user-desktop-shell, .user-mobile-shell) :is(#dashboard #userAppContent, #mob-content #userAppContent) .sr-hero-image-panel .sr-image-copy-highlight {
      color: inherit !important;
      -webkit-text-fill-color: currentColor !important;
   }

   body:is(.user-desktop-shell, .user-mobile-shell) :is(#dashboard #userAppContent, #mob-content #userAppContent) .sr-hero-image-panel .sr-image-copy li {
      color: var(--sr-image-detail-color, rgba(255, 255, 255, 0.9)) !important;
      -webkit-text-fill-color: var(--sr-image-detail-color, rgba(255, 255, 255, 0.9)) !important;
   }

   body:is(.user-desktop-shell, .user-mobile-shell) :is(#dashboard #userAppContent, #mob-content #userAppContent) .sr-hero-image-panel .sr-image-copy li::before {
      background: currentColor !important;
      color: var(--sr-image-detail-color, rgba(255, 255, 255, 0.9)) !important;
      -webkit-text-fill-color: var(--sr-image-detail-color, rgba(255, 255, 255, 0.9)) !important;
   }

   body.user-desktop-shell #dashboard #userAppContent .sr-hero-image-panel :is(.sr-image-brand, .sr-image-title, .sr-image-title span, .sr-image-copy) {
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
   }

   body.user-desktop-shell #dashboard #userAppContent .sr-hero-image-panel :is(.sr-image-brand span, .sr-image-title strong) {
      color: #16f4df !important;
      -webkit-text-fill-color: #16f4df !important;
   }
</style>
