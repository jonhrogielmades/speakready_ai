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

   body.user-mobile-shell {
      --sr-mobile-readable-panel: rgba(14, 22, 36, 0.97);
      --sr-mobile-readable-card: rgba(30, 41, 59, 0.94);
      --sr-mobile-readable-card-soft: rgba(15, 23, 42, 0.86);
      --sr-mobile-readable-title: #f8fafc;
      --sr-mobile-readable-copy: #e2e8f0;
      --sr-mobile-readable-muted: #cbd5e1;
      --sr-mobile-readable-subtle: #94a3b8;
      --sr-mobile-readable-border: rgba(147, 197, 253, 0.26);
      --sr-mobile-readable-accent: #60a5fa;
      --sr-mobile-readable-accent-strong: #93c5fd;
      --sr-mobile-readable-danger: #fca5a5;
      --sr-mobile-readable-danger-bg: rgba(127, 29, 29, 0.26);
      --sr-mobile-readable-shadow: 0 22px 48px rgba(0, 0, 0, 0.34);
   }

   html.lm body.user-mobile-shell,
   .lm body.user-mobile-shell {
      --sr-mobile-readable-panel: rgba(248, 252, 255, 0.98);
      --sr-mobile-readable-card: rgba(255, 255, 255, 0.96);
      --sr-mobile-readable-card-soft: rgba(241, 247, 255, 0.94);
      --sr-mobile-readable-title: #0f172a;
      --sr-mobile-readable-copy: #334155;
      --sr-mobile-readable-muted: #475569;
      --sr-mobile-readable-subtle: #64748b;
      --sr-mobile-readable-border: rgba(59, 130, 246, 0.22);
      --sr-mobile-readable-accent: #2563eb;
      --sr-mobile-readable-accent-strong: #1d4ed8;
      --sr-mobile-readable-danger: #dc2626;
      --sr-mobile-readable-danger-bg: rgba(254, 226, 226, 0.9);
      --sr-mobile-readable-shadow: 0 18px 42px rgba(15, 23, 42, 0.13);
   }

   body.user-mobile-shell #mob-header .mob-header-brand-pill {
      color: var(--sr-mobile-readable-title) !important;
      background: var(--sr-mobile-readable-card) !important;
      border-color: var(--sr-mobile-readable-border) !important;
      box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12) !important;
      min-width: 118px !important;
      max-width: 168px !important;
      overflow: hidden !important;
   }

   body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-header-brand-text {
      display: block !important;
      min-width: 0 !important;
      max-width: 112px !important;
      margin: 0 !important;
      overflow: hidden !important;
      text-overflow: ellipsis !important;
      white-space: nowrap !important;
      color: var(--sr-mobile-readable-title) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-title) !important;
      font-size: 0.76rem !important;
      font-weight: 900 !important;
      line-height: 1.05 !important;
      letter-spacing: 0 !important;
      text-shadow: none !important;
   }

   body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-logo-ring {
      background: #ffffff !important;
      border-color: rgba(255, 255, 255, 0.95) !important;
      box-shadow: 0 6px 14px rgba(37, 99, 235, 0.13) !important;
   }

   @media (max-width: 360px) {
      body.user-mobile-shell #mob-header .mob-header-brand-pill {
         min-width: 110px !important;
         max-width: 138px !important;
      }

      body.user-mobile-shell #mob-header .mob-header-brand-pill .mob-header-brand-text {
         max-width: 84px !important;
         font-size: 0.68rem !important;
      }
   }

   body.user-mobile-shell #mob-bottom-nav {
      background: var(--sr-mobile-readable-panel) !important;
      border-top-color: var(--sr-mobile-readable-border) !important;
      box-shadow: 0 -14px 34px rgba(15, 23, 42, 0.2) !important;
   }

   body.user-mobile-shell #mob-bottom-nav .mob-nav-item {
      color: var(--sr-mobile-readable-muted) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-muted) !important;
      font-weight: 800 !important;
   }

   body.user-mobile-shell #mob-bottom-nav .mob-nav-item :is(i, span) {
      color: inherit !important;
      -webkit-text-fill-color: currentColor !important;
   }

   body.user-mobile-shell #mob-bottom-nav .mob-nav-item.active,
   body.user-mobile-shell #mob-bottom-nav .mob-nav-primary {
      color: var(--sr-mobile-readable-accent-strong) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-accent-strong) !important;
   }

   body.user-mobile-shell #mob-bottom-nav .mob-nav-primary-icon,
   body.user-mobile-shell #mob-bottom-nav .mob-nav-primary-icon i {
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
   }

   html:not(.lm) body.user-mobile-shell #mob-bottom-nav .mob-nav-primary-icon {
      border-color: rgba(14, 22, 36, 0.98) !important;
   }

   body.user-mobile-shell :is(.mob-profile-dropdown, .mob-notif-dropdown) {
      background: var(--sr-mobile-readable-panel) !important;
      border-color: var(--sr-mobile-readable-border) !important;
      box-shadow: var(--sr-mobile-readable-shadow) !important;
      color: var(--sr-mobile-readable-title) !important;
   }

   body.user-mobile-shell :is(.mob-profile-head, .mob-profile-pages-close, .mob-notif-header, .mob-notif-footer) {
      border-color: var(--sr-mobile-readable-border) !important;
   }

   body.user-mobile-shell :is(.mob-profile-name, .mob-profile-pages-close, .mob-notif-title, .mob-notif-copy strong) {
      color: var(--sr-mobile-readable-title) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-title) !important;
   }

   body.user-mobile-shell :is(.mob-profile-role, .mob-profile-section-title, .mob-notif-copy span, .mob-notif-copy small, .mob-notif-status) {
      color: var(--sr-mobile-readable-muted) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-muted) !important;
   }

   body.user-mobile-shell :is(.mob-profile-link, .mob-profile-action, .mob-profile-language, .mob-profile-close, .mob-notif-item, .mob-notif-action, .mob-notif-view-all) {
      background: var(--sr-mobile-readable-card) !important;
      border-color: var(--sr-mobile-readable-border) !important;
      color: var(--sr-mobile-readable-title) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-title) !important;
   }

   body.user-mobile-shell :is(.mob-profile-link span, .mob-profile-action span, .mob-profile-language-text, .mob-profile-close i, .mob-notif-action span, .mob-notif-action i, .mob-notif-view-all, .mob-notif-view-all i) {
      color: inherit !important;
      -webkit-text-fill-color: currentColor !important;
   }

   body.user-mobile-shell .mob-profile-link.active,
   body.user-mobile-shell .mob-notif-item.unread {
      background: color-mix(in srgb, var(--sr-mobile-readable-accent) 14%, var(--sr-mobile-readable-card)) !important;
      border-color: color-mix(in srgb, var(--sr-mobile-readable-accent) 42%, var(--sr-mobile-readable-border)) !important;
   }

   body.user-mobile-shell .mob-profile-action.danger,
   body.user-mobile-shell .mob-notif-action.danger,
   body.user-mobile-shell .mob-notif-link-btn.danger {
      color: var(--sr-mobile-readable-danger) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-danger) !important;
      background: var(--sr-mobile-readable-danger-bg) !important;
      border-color: color-mix(in srgb, var(--sr-mobile-readable-danger) 40%, transparent) !important;
   }

   body.user-mobile-shell .mob-notif-count {
      color: var(--sr-mobile-readable-danger) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-danger) !important;
      background: var(--sr-mobile-readable-danger-bg) !important;
   }

   body.user-mobile-shell .mob-notif-link-btn,
   body.user-mobile-shell .mob-notif-retry {
      color: var(--sr-mobile-readable-accent-strong) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-accent-strong) !important;
      background: color-mix(in srgb, var(--sr-mobile-readable-accent) 12%, var(--sr-mobile-readable-card)) !important;
      border-color: color-mix(in srgb, var(--sr-mobile-readable-accent) 28%, var(--sr-mobile-readable-border)) !important;
   }

   body.user-mobile-shell #mob-content #notifications-page {
      --notif-panel-bg: var(--sr-mobile-readable-panel);
      --notif-panel-border: var(--sr-mobile-readable-border);
      --notif-row-bg: var(--sr-mobile-readable-card);
      --notif-row-border: var(--sr-mobile-readable-border);
      --notif-row-unread-bg: color-mix(in srgb, var(--sr-mobile-readable-accent) 13%, var(--sr-mobile-readable-card));
      --notif-row-unread-border: color-mix(in srgb, var(--sr-mobile-readable-accent) 44%, var(--sr-mobile-readable-border));
      --notif-title: var(--sr-mobile-readable-title);
      --notif-text: var(--sr-mobile-readable-copy);
      --notif-muted: var(--sr-mobile-readable-muted);
      --notif-chip-bg: var(--sr-mobile-readable-card-soft);
      --notif-chip-border: var(--sr-mobile-readable-border);
      --notif-primary-bg: color-mix(in srgb, var(--sr-mobile-readable-accent) 12%, var(--sr-mobile-readable-card));
      --notif-primary-text: var(--sr-mobile-readable-accent-strong);
      --notif-primary-border: color-mix(in srgb, var(--sr-mobile-readable-accent) 34%, var(--sr-mobile-readable-border));
      --notif-danger-bg: var(--sr-mobile-readable-danger-bg);
      --notif-danger-text: var(--sr-mobile-readable-danger);
      --notif-danger-border: color-mix(in srgb, var(--sr-mobile-readable-danger) 38%, transparent);
   }

   body.user-mobile-shell #mob-content #notifications-page :is(.premium-panel, .notification-row, .notifications-empty-state) {
      background: var(--sr-mobile-readable-card) !important;
      border-color: var(--sr-mobile-readable-border) !important;
      color: var(--sr-mobile-readable-title) !important;
   }

   body.user-mobile-shell #mob-content #notifications-page .notification-row.is-unread {
      background: var(--notif-row-unread-bg) !important;
      border-color: var(--notif-row-unread-border) !important;
   }

   body.user-mobile-shell #mob-content #notifications-page .notification-title {
      color: var(--sr-mobile-readable-title) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-title) !important;
   }

   body.user-mobile-shell #mob-content #notifications-page :is(.notification-message, .notification-meta, .notifications-empty-state p) {
      color: var(--sr-mobile-readable-muted) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-muted) !important;
   }

   body.user-mobile-shell #mob-content #notifications-page .notification-action-btn.read {
      color: var(--sr-mobile-readable-accent-strong) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-accent-strong) !important;
      background: color-mix(in srgb, var(--sr-mobile-readable-accent) 12%, var(--sr-mobile-readable-card)) !important;
      border-color: color-mix(in srgb, var(--sr-mobile-readable-accent) 34%, var(--sr-mobile-readable-border)) !important;
   }

   body.user-mobile-shell #mob-content #notifications-page .notification-action-btn.delete {
      color: var(--sr-mobile-readable-danger) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-danger) !important;
      background: var(--sr-mobile-readable-danger-bg) !important;
      border-color: color-mix(in srgb, var(--sr-mobile-readable-danger) 38%, transparent) !important;
   }

   body.user-mobile-shell #mob-content #portfolioReport {
      --reports-pro-card: var(--sr-mobile-readable-panel);
      --reports-pro-field: var(--sr-mobile-readable-card);
      --reports-pro-soft: var(--sr-mobile-readable-card-soft);
      --reports-pro-border: var(--sr-mobile-readable-border);
      --reports-pro-title: var(--sr-mobile-readable-title);
      --reports-pro-text: var(--sr-mobile-readable-copy);
      --reports-pro-muted: var(--sr-mobile-readable-muted);
      color: var(--sr-mobile-readable-title) !important;
   }

   body.user-mobile-shell #mob-content #portfolioReport #report-readiness {
      background: var(--sr-mobile-readable-panel) !important;
      border-color: var(--sr-mobile-readable-border) !important;
      box-shadow: var(--sr-mobile-readable-shadow) !important;
      color: var(--sr-mobile-readable-title) !important;
   }

   body.user-mobile-shell #mob-content #portfolioReport #report-readiness > .row > [class*="col-"],
   body.user-mobile-shell #mob-content #portfolioReport #report-readiness .report-summary-item {
      background: var(--sr-mobile-readable-card) !important;
      border-color: var(--sr-mobile-readable-border) !important;
      color: var(--sr-mobile-readable-title) !important;
   }

   body.user-mobile-shell #mob-content #portfolioReport #report-readiness :is(h5, .report-previous-score, .report-summary-value) {
      color: var(--sr-mobile-readable-title) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-title) !important;
   }

   body.user-mobile-shell #mob-content #portfolioReport #report-readiness :is(.report-section-kicker, h6, .report-summary-label, .report-readiness-message) {
      color: var(--sr-mobile-readable-muted) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-muted) !important;
   }

   body.user-mobile-shell #mob-content #portfolioReport #report-readiness .report-chip {
      color: var(--sr-mobile-readable-accent-strong) !important;
      -webkit-text-fill-color: var(--sr-mobile-readable-accent-strong) !important;
      background: color-mix(in srgb, var(--sr-mobile-readable-accent) 12%, var(--sr-mobile-readable-card)) !important;
      border-color: color-mix(in srgb, var(--sr-mobile-readable-accent) 34%, var(--sr-mobile-readable-border)) !important;
   }
</style>
