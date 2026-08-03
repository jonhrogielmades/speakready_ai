<style>
   @media (min-width: 992px) {
      body.desktop-shell {
         --sr-page-title-accent: var(--tx);
         --sr-page-title-accent-shadow: none;
         --desktop-shell-title: var(--tx);
         --desktop-shell-copy: var(--tx2);
         --desktop-shell-muted: color-mix(in srgb, var(--tx2) 72%, var(--tx) 28%);
      }

      body.desktop-shell #dashboard :where(.db-content, .db-dropdown, .modal-content) {
         color: var(--desktop-shell-title) !important;
      }

      body.desktop-shell #dashboard :where(.db-content, .db-dropdown, .modal-content) :where(
         h1, h2, h3, h4, h5, h6,
         .text-gradient-primary,
         .gradient-text,
         [class*="text-gradient"],
         [class*="page-title"],
         [class*="hero-title"],
         [class$="-title"],
         .modal-title,
         .fw-bold,
         strong
      ):not(.btn):not(.badge):not(.dropdown-item) {
         color: var(--desktop-shell-title) !important;
         -webkit-text-fill-color: var(--desktop-shell-title) !important;
         text-shadow: none !important;
      }

      body.desktop-shell #dashboard :where(.db-content, .db-dropdown, .modal-content) :where(
         p, li, label, .form-label, .card-text, .small, small,
         .text-muted,
         [class*="subtitle"],
         [class*="description"],
         [class*="caption"],
         [class*="meta"]
      ):not(.btn):not(.badge):not(.dropdown-item) {
         color: var(--desktop-shell-copy) !important;
         -webkit-text-fill-color: var(--desktop-shell-copy) !important;
      }

      body.desktop-shell #dashboard :where(.text-muted, small, .small) {
         color: var(--desktop-shell-muted) !important;
         -webkit-text-fill-color: var(--desktop-shell-muted) !important;
      }

      body.desktop-shell #dashboard :where(
         .premium-panel,
         .premium-card,
         .setup-panel,
         .panel,
         .card,
         .module-card,
         .print-card,
         .perk-card,
         .ll-stat-card,
         .db-stat-card,
         .stat-card,
         .sr-card,
         .sr-stat-card,
         .tracker-panel,
         .category-card,
         .table-responsive
      ) {
         color: var(--desktop-shell-title) !important;
      }

      body.desktop-shell #dashboard :where(input, select, textarea, .form-control, .form-select, .oinp) {
         color: var(--tx) !important;
         -webkit-text-fill-color: currentColor !important;
         background-color: var(--bg3) !important;
         border-color: var(--bd) !important;
      }

      body.desktop-shell #dashboard :where(input, textarea, .form-control, .oinp)::placeholder {
         color: var(--desktop-shell-muted) !important;
         -webkit-text-fill-color: var(--desktop-shell-muted) !important;
         font-weight: 400 !important;
         opacity: 1;
      }

      body.desktop-shell #dashboard .db-content :where(table, .table, .custom-table, .db-table) {
         --bs-table-color: var(--desktop-shell-copy) !important;
         --bs-table-hover-color: var(--desktop-shell-title) !important;
         color: var(--desktop-shell-copy) !important;
      }

      body.desktop-shell #dashboard .db-content :where(table, .table, .custom-table, .db-table) :where(th) {
         color: var(--desktop-shell-title) !important;
         -webkit-text-fill-color: var(--desktop-shell-title) !important;
      }

      body.desktop-shell #dashboard .db-content :where(table, .table, .custom-table, .db-table) :where(td) {
         color: var(--desktop-shell-copy) !important;
         -webkit-text-fill-color: var(--desktop-shell-copy) !important;
      }

      body.desktop-shell #dashboard .db-content :where(table, .table, .custom-table, .db-table) :where(td) :where(strong, .fw-bold, h6, a:not(.btn)) {
         color: var(--desktop-shell-title) !important;
         -webkit-text-fill-color: var(--desktop-shell-title) !important;
      }

      body.desktop-shell #dashboard :where(
         .btn,
         .btn *,
         button,
         button *,
         .badge,
         .badge *,
         .alert,
         .alert *,
         .dropdown-item,
         .dropdown-item *,
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
         [class*="bg-info"]
      ) {
         -webkit-text-fill-color: currentColor !important;
      }

      body.desktop-shell #dashboard :where(.btn *, button *, .badge *, .alert *, .dropdown-item *) {
         color: inherit !important;
      }

      body.desktop-shell #dashboard :where(.text-dark):not(.btn):not(.badge) {
         color: var(--desktop-shell-title) !important;
         -webkit-text-fill-color: var(--desktop-shell-title) !important;
      }

      body.user-desktop-shell #dashboard .sr-hero-image-panel :where(.sr-image-brand, .sr-image-title, .sr-image-title span, .sr-image-copy) {
         color: #ffffff !important;
         -webkit-text-fill-color: #ffffff !important;
      }

      body.user-desktop-shell #dashboard .sr-hero-image-panel :where(.sr-image-brand span, .sr-image-title strong) {
         color: #16f4df !important;
         -webkit-text-fill-color: #16f4df !important;
      }
   }
</style>
