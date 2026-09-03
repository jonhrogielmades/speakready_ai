<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#ffffff">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <title>@yield('title', 'SpeakReady AI - AI-Based Interview Practice System')</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <!-- Bootstrap 5.3 -->
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"/>
      <!-- AOS Animate on Scroll -->
      <link href="{{ asset('css/aos.css') }}" rel="stylesheet"/>
      <!-- Swiper -->
      <link href="{{ asset('css/swiper-bundle.min.css') }}" rel="stylesheet"/>
      <!-- all min css -->
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}"/>
      <!-- magnific CSS -->
      <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}"/>
      <!-- Shared app CSS -->
      <link rel="stylesheet" href="{{ asset('css/desktop/style.css?v=35') }}" />
      <style>
          .db-nl { text-decoration: none; display: flex; align-items: center; }
          
          /* Global Mobile Responsiveness for Premium UI Updates */
          @media (max-width: 768px) {
              .premium-panel, .panel, .setup-panel {
                  border-radius: 16px !important;
                  padding: 16px !important;
              }
              .stat-card.premium-panel, .perk-card.premium-panel, .module-card.premium-panel, .print-card {
                  padding: 16px !important;
              }
              h4.text-gradient-primary, .text-gradient-primary {
                  font-size: 1.25rem !important;
              }
              .db-section {
                  padding: 15px !important;
              }
              .accordion-item.premium-panel {
                  padding: 0 !important;
              }
              .accordion-button {
                  padding: 16px !important;
              }
          }

          @media (min-width: 992px) {
              body.user-desktop-shell .db-section :is(
                  h1, h2, h3, h4, h5, h6,
                  .text-gradient-primary,
                  .sr-page-hero-title,
                  .modules-hero-title,
                  .module-smart-title,
                  .module-card-title,
                  .module-rec-copy strong,
                  .module-path-copy strong,
                  .mission-title,
                  .mission-card-name,
                  .vr-assessment-title,
                  .vr-progress-title,
                  .instant-feedback-title,
                  .intention-coach-title
              ) {
                  color: var(--tx) !important;
                  -webkit-text-fill-color: var(--tx) !important;
              }
          }
      </style>
      @include('desktop.partials.onboarding-styles')
      @stack('styles')
   </head>
   <body class="user-desktop-shell desktop-shell @yield('body-class')" data-layout-shell="desktop" data-app-surface="user">
      <div id="dashboard">
         <!-- Sidebar -->
         <div class="db-sidebar" id="dbSidebar">
            <div class="db-logo d-flex justify-content-between align-items-center">
               <div class="db-brand d-flex align-items-center gap-2">
                  <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: #ffffff; padding: 0;">
                  <div class="db-brand-copy">
                     <span class="db-brand-text">SpeakReady AI</span>
                     <span class="db-brand-subtitle">AI-Based Interview Practice</span>
                  </div>
               </div>
               <button class="db-sidebar-close d-lg-none" type="button" aria-label="Close navigation" onclick="closeDashboardSidebar()">
                  <i class="fa-solid fa-xmark"></i>
               </button>
            </div>
            <div class="db-nav">
               <div class="db-nav-section">Dashboard</div>
               <a href="{{ route('dashboard') }}" class="db-nl db-nav-blue {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Overview"><i class="fa-solid fa-house"></i><span class="db-nav-label">Overview</span></a>


                <div class="db-nav-section">Interview Practice</div>
                <a href="{{ route('interview.setup') }}" class="db-nl db-nav-purple {{ request()->routeIs('interview.setup') ? 'active' : '' }}" title="Mock Interview"><i class="fa-solid fa-microphone-lines"></i><span class="db-nav-label">Mock Interview</span></a>

               <div class="db-nav-section">Specialized Training</div>
               <a href="{{ route('user.modules.index') }}" class="db-nl db-nav-purple {{ request()->routeIs('user.modules.*') ? 'active' : '' }}" title="Modules"><i class="fa-solid fa-book-open"></i><span class="db-nav-label">Modules</span></a>
               <a href="{{ route('user.learning') }}" class="db-nl db-nav-amber {{ request()->routeIs('user.learning') ? 'active' : '' }}" title="Challenges"><i class="fa-solid fa-trophy"></i><span class="db-nav-label">Challenges</span></a>
               <a href="{{ route('user.coach') }}" class="db-nl db-nav-rose {{ request()->routeIs('user.coach') ? 'active' : '' }}" title="AI Chatbot Coach"><i class="fa-solid fa-robot"></i><span class="db-nav-label">AI Chatbot Coach</span></a>

               <div class="db-nav-section">Performance</div>
               <a href="{{ route('user.progress') }}" class="db-nl db-nav-emerald {{ request()->routeIs('user.progress') ? 'active' : '' }}" title="Progress"><i class="fa-solid fa-chart-line"></i><span class="db-nav-label">Progress</span></a>
               <a href="{{ route('user.feedback') }}" class="db-nl db-nav-cyan {{ request()->routeIs('user.feedback') ? 'active' : '' }}" title="Feedback"><i class="fa-solid fa-bookmark"></i><span class="db-nav-label">Feedback</span></a>
               <a href="{{ route('user.reports') }}" class="db-nl db-nav-purple {{ request()->routeIs('user.reports') ? 'active' : '' }}" title="Reports"><i class="fa-solid fa-file-lines"></i><span class="db-nav-label">Reports</span></a>
            </div>
            <div class="db-bottom">
               <a href="{{ route('user.skills') }}" class="db-upgrade-card" title="View skill perks">
                  <span class="db-upgrade-icon"><i class="fa-solid fa-gem"></i></span>
                  <span class="db-upgrade-copy">
                     <strong>Unlock Pro Features</strong>
                     <small>Get advanced AI feedback and personalized insights.</small>
                  </span>
                  <span class="db-upgrade-action">Upgrade Now <i class="fa-solid fa-arrow-right"></i></span>
               </a>
               <form action="{{ route('logout') }}" method="POST" class="db-logout-form">
                  @csrf
                  <button type="submit" class="db-nl db-nav-danger" title="Log Out"><i class="fa-solid fa-right-from-bracket"></i><span class="db-nav-label">Log Out</span></button>
               </form>
            </div>
         </div>
         <button class="db-sidebar-backdrop" type="button" aria-label="Close navigation" onclick="closeDashboardSidebar()"></button>
         <!-- Main Content Area -->
         <div class="db-main">
            <!-- Top bar -->
            <div class="db-top">
               <button class="boc db-sidebar-toggle" type="button" aria-label="Toggle navigation" title="Toggle navigation" aria-expanded="true" onclick="toggleDashboardSidebar()">
               <i class="fa-solid fa-bars"></i>
               </button>
               <form class="db-top-search db-top-command-search" role="search" data-page-search-form>
                  <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                  <input id="dbPageSearch" type="search" placeholder="Search this page..." autocomplete="off" aria-label="Search and highlight text on this page" data-page-search-input>
                  <span class="db-search-count" aria-live="polite" data-page-search-count></span>
                  <button class="db-search-clear" type="button" aria-label="Clear search" title="Clear search" data-page-search-clear hidden>
                     <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                  </button>
               </form>
               <div class="db-top-actions ms-auto d-flex align-items-center gap-3 flex-shrink-0">
                  <span class="db-top-upgrade-card is-locked" title="Locked pro features" aria-disabled="true">
                     <span class="db-top-upgrade-icon"><i class="fa-solid fa-lock"></i></span>
                     <span class="db-top-upgrade-copy">Pro Locked</span>
                     <span class="db-top-upgrade-action">Upgrade <i class="fa-solid fa-lock"></i></span>
                  </span>
                  <button class="boc d-flex align-items-center justify-content-center" id="dbFullscreenBtn" type="button" aria-label="Enter fullscreen" title="Enter fullscreen" data-user-fullscreen-toggle>
                     <i class="fa-solid fa-expand" id="dbFullscreenIcon" aria-hidden="true"></i>
                  </button>
                  <button class="boc d-flex align-items-center justify-content-center" id="dbTutorialBtn" type="button" aria-label="Start tutorial" onclick="triggerMobTutorial()" title="Start tutorial" style="color:#60a5fa;border-color:rgba(96,165,250,0.3)">
                     <i class="fa-solid fa-circle-play"></i>
                  </button>
                  <button class="boc d-flex align-items-center justify-content-center" id="dbThBtn" type="button" aria-label="Toggle color theme" title="Toggle color theme" onclick="toggleTheme()">
                  <i class="fa-solid fa-sun" id="dbSunI" style="display:none"></i>
                  <i class="fa-solid fa-moon" id="dbMoonI"></i>
                  </button>
                  
                  <!-- Notifications -->
                  <div style="position:relative" id="notifWrap">
                     <button class="boc d-flex align-items-center justify-content-center" id="bellBtn" type="button" aria-label="Open notifications" title="Notifications" aria-haspopup="true" aria-expanded="false" aria-controls="notifDropdown" onclick="toggleNotif(event)">
                        <i class="fa-regular fa-bell"></i>
                     </button>
                     <span id="notifBadge" style="position:absolute;top:5px;right:5px;width:9px;height:9px;border-radius:50%;background:#f87171;border:2px solid var(--bg);display:none;"></span>
                     
                     <!-- Notification Dropdown -->
                     <div class="db-dropdown user-notif-dropdown mob-notif-dropdown" id="notifDropdown" role="dialog" aria-modal="false" aria-hidden="true" aria-labelledby="notifDropdownTitle" style="right:0;">
                        <div class="dd-header user-notif-header mob-notif-header">
                           <div class="dd-header-title user-notif-title mob-notif-title" id="notifDropdownTitle">
                              <i class="fa-regular fa-bell me-2" style="color:var(--pur)"></i>Notifications 
                              <span class="mob-notif-count" id="unreadCountBadge" style="display:none;">0 new</span>
                           </div>
                           <div class="user-notif-actions mob-notif-actions">
                              <button type="button" class="user-notif-action mob-notif-action" onclick="markAllNotificationsRead()" title="Mark all read"><i class="fa-solid fa-check"></i><span>Read</span></button>
                              <button type="button" class="user-notif-action mob-notif-action danger" onclick="clearAllNotificationsDD()" title="Clear all"><i class="fa-solid fa-trash"></i><span>Clear</span></button>
                              <button type="button" class="user-notif-action user-notif-action-icon mob-notif-action" onclick="toggleNotif(event)" aria-label="Close notifications"><i class="fa-solid fa-xmark"></i></button>
                           </div>
                        </div>
                        <div class="dd-body user-notif-list mob-notif-list" id="notifListContainer">
                           <div class="text-center py-4" style="color:var(--tx3);font-size:0.85rem;" id="noNotifMsg">Loading notifications...</div>
                        </div>
                        <div class="user-notif-footer mob-notif-footer">
                           <a href="{{ route('user.notifications') }}" class="user-notif-view-all mob-notif-view-all"><i class="fa-solid fa-list me-1"></i>View All Notifications</a>
                        </div>
                     </div>
                  </div>

                  <div style="position:relative" id="profileWrap">
                     <button class="db-user-pill" id="userPill" type="button" aria-label="Open profile menu" aria-haspopup="true" aria-expanded="false" aria-controls="profileDropdown" onclick="toggleProfile(event)">
                        <span class="user-avatar-presence">
                    @if(Auth::check() && Auth::user()->profile_photo_path)
                           @php
                               $photoPath = Auth::user()->profile_photo_path;
                               $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                           @endphp
                           <span class="db-avatar user-avatar" style="padding:0;overflow:hidden;border:1px solid var(--bd);">
                              <img src="{{ $photoUrl }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                           </span>
                     @else
                           <span class="db-avatar user-avatar">{{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U' }}</span>
                     @endif
                        </span>
                        <span class="d-none d-md-block">
                           <span style="display:block;font-size:.85rem;font-weight:600;line-height:1.2" id="userName">{{ Auth::user()->name ?? 'User' }}</span>
                           <span style="display:block;font-size:.72rem;color:var(--tx3)" id="userPlan">{{ Auth::check() && Auth::user()->is_admin ? 'ADMIN' : 'USER' }}</span>
                        </span>
                        <i class="fa-solid fa-chevron-down fa-xs" id="profileChevron" style="color:var(--tx3);margin-left:2px;transition:.3s"></i>
                     </button>
                     <!-- Profile Dropdown -->
                     <div class="db-dropdown profile-dd" id="profileDropdown" role="dialog" aria-modal="false" aria-hidden="true" aria-labelledby="userPill" style="right:0">
                        <div style="padding:8px 0">
                           <a href="{{ route('user.account') }}" class="profile-menu-item" style="display:block;text-decoration:none;color:var(--tx2);"><i class="fa-solid fa-user-gear me-2"></i>Account Management</a>
                           <a href="{{ route('user.notifications') }}" class="profile-menu-item" style="display:block;text-decoration:none;color:var(--tx2);"><i class="fa-solid fa-bell me-2"></i>Notifications</a>
                           <form action="{{ route('user.language.update') }}" method="POST" style="padding:10px 16px 8px;">
                              @csrf
                              <label for="profileLanguageSelect" style="display:flex;align-items:center;gap:8px;color:var(--tx2);font-size:.86rem;font-weight:600;margin-bottom:8px;">
                                 <i class="fa-solid fa-language" style="width:18px;text-align:center;color:#60a5fa;"></i>
                                 Language
                              </label>
                              <select id="profileLanguageSelect" name="preferred_language" class="form-select form-select-sm" onchange="this.form.submit()" style="background:var(--bg3);color:var(--tx);border-color:var(--bd);border-radius:10px;font-size:.82rem;">
                                 @foreach($supportedLanguages as $languageCode => $language)
                                    <option value="{{ $languageCode }}" {{ ($currentLanguageCode ?? 'en') === $languageCode ? 'selected' : '' }}>{{ $language['native_label'] ?? $language['label'] }}</option>
                                 @endforeach
                              </select>
                              <small style="display:block;color:var(--tx3);font-size:.68rem;margin-top:6px;line-height:1.35;">AI translates the app and interview experience.</small>
                           </form>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="db-content" id="userAppContent" data-layout-shell="desktop" data-user-ajax-content data-page-title="{{ trim($__env->yieldContent('page-title')) ?: (trim($__env->yieldContent('title')) ?: 'Overview') }}">
                @yield('content')
            </div>
         </div>
      </div>
      @include('desktop.partials.user-command-palette')
      @include('desktop.partials.viewport-mobile-cookie')
      <div class="modal fade sr-confirm-modal" id="srConfirmModal" tabindex="-1" aria-labelledby="srConfirmModalTitle" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
               <div class="modal-header">
                  <div class="sr-confirm-heading">
                     <span class="sr-confirm-icon" id="srConfirmModalIcon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                     <h5 class="modal-title" id="srConfirmModalTitle">Confirm action</h5>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body">
                  <p id="srConfirmModalMessage">Are you sure you want to continue?</p>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn sr-confirm-cancel" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn sr-confirm-action danger" id="srConfirmModalAction">Continue</button>
               </div>
            </div>
         </div>
      </div>
      <!-- ======================== SCRIPTS ======================== -->
      <!-- jQuery -->
      <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
      <!-- Bootstrap 5 -->
      <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
      @include('desktop.partials.flash-modal')
      <!-- AOS -->
      <script src="{{ asset('js/aos.js') }}"></script>
      <!-- Swiper -->
      <script src="{{ asset('js/chart.umd.min.js') }}"></script>
      <!-- CounterUp -->
      <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
      <!-- Main js -->
      <script src="{{ asset('js/main.js?v=8') }}"></script>
      @include('desktop.partials.language-translation')
      <!-- PWA Service Worker Registration -->
      <script>
         (function initializeSpeakReadyConfirm() {
            if (window.SpeakReadyConfirm?.initialized) return;

            const modalEl = document.getElementById('srConfirmModal');
            const titleEl = document.getElementById('srConfirmModalTitle');
            const messageEl = document.getElementById('srConfirmModalMessage');
            const actionBtn = document.getElementById('srConfirmModalAction');
            const iconEl = document.getElementById('srConfirmModalIcon');
            let resolver = null;
            let confirmed = false;

            const resolvePending = (value) => {
               if (typeof resolver === 'function') {
                  resolver(Boolean(value));
               }
               resolver = null;
            };

            actionBtn?.addEventListener('click', function() {
               confirmed = true;
               const instance = window.bootstrap?.Modal?.getOrCreateInstance(modalEl);
               if (instance) {
                  instance.hide();
               } else {
                  resolvePending(true);
               }
            });

            modalEl?.addEventListener('hidden.bs.modal', function() {
               resolvePending(confirmed);
               confirmed = false;
            });

            window.SpeakReadyConfirm = {
               initialized: true,
               show(options = {}) {
                  const title = options.title || 'Confirm action';
                  const message = options.message || 'Are you sure you want to continue?';
                  const action = options.action || 'Continue';
                  const variant = options.variant === 'danger' ? 'danger' : 'primary';

                  if (!modalEl || !window.bootstrap?.Modal) {
                     console.warn('Confirmation modal is unavailable.');
                     return Promise.resolve(false);
                  }

                  if (titleEl) titleEl.textContent = title;
                  if (messageEl) messageEl.textContent = message;
                  if (actionBtn) {
                     actionBtn.textContent = action;
                     actionBtn.className = 'btn sr-confirm-action ' + variant;
                  }
                  if (iconEl) {
                     iconEl.className = 'sr-confirm-icon ' + variant;
                  }

                  confirmed = false;
                  return new Promise((resolve) => {
                     resolvePending(false);
                     resolver = resolve;
                     window.bootstrap.Modal.getOrCreateInstance(modalEl, {
                        backdrop: 'static',
                        keyboard: true
                     }).show();
                  });
               }
            };

            document.addEventListener('submit', function(event) {
               const form = event.target?.closest?.('form[data-sr-confirm-form]');
               if (!form || form.dataset.srConfirmResolved === 'true') return;

               event.preventDefault();
               window.SpeakReadyConfirm.show({
                  title: form.dataset.srConfirmTitle,
                  message: form.dataset.srConfirmMessage,
                  action: form.dataset.srConfirmAction,
                  variant: form.dataset.srConfirmVariant
               }).then((isConfirmed) => {
                  if (!isConfirmed) return;
                  form.dataset.srConfirmResolved = 'true';
                  form.submit();
               });
            });
         })();

         function setUserDropdownState(dropdownId, triggerId, isOpen) {
            const dropdown = document.getElementById(dropdownId);
            const trigger = document.getElementById(triggerId);
            if (!dropdown) return;

            dropdown.classList.toggle('open', Boolean(isOpen));
            dropdown.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            trigger?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
         }

         function closeUserHeaderDropdowns(exceptId = null) {
            if (exceptId !== 'notifDropdown') {
               setUserDropdownState('notifDropdown', 'bellBtn', false);
            }
            if (exceptId !== 'profileDropdown') {
               setUserDropdownState('profileDropdown', 'userPill', false);
               const ch = document.getElementById('profileChevron');
               if (ch) ch.style.transform = 'rotate(0deg)';
            }
         }

         function closeDashboardSidebar() {
            document.getElementById('dbSidebar')?.classList.remove('mob-open');
            document.body.classList.remove('sidebar-open');
         }

         function getUserSidebarPreference() {
            try {
               return localStorage.getItem('user_sidebar_collapsed');
            } catch (error) {
               return null;
            }
         }

         function setUserSidebarPreference(isCollapsed) {
            try {
               localStorage.setItem('user_sidebar_collapsed', isCollapsed ? '1' : '0');
            } catch (error) {
               // Sidebar still toggles for the current page when storage is unavailable.
            }
         }

         function syncSidebarToggleState() {
            const toggle = document.querySelector('.db-sidebar-toggle');
            const isDesktopCollapsed = window.innerWidth >= 992 && document.body.classList.contains('collapsed-sidebar');
            if (toggle) {
               toggle.setAttribute('aria-expanded', isDesktopCollapsed ? 'false' : 'true');
               toggle.setAttribute('aria-label', isDesktopCollapsed ? 'Expand navigation' : 'Collapse navigation');
               toggle.title = isDesktopCollapsed ? 'Expand navigation' : 'Collapse navigation';
            }
         }

         function toggleDashboardSidebar() {
            if (window.innerWidth < 992) {
               document.body.classList.remove('collapsed-sidebar');
               const sidebar = document.getElementById('dbSidebar');
               const isOpen = sidebar?.classList.toggle('mob-open');
               document.body.classList.toggle('sidebar-open', Boolean(isOpen));
               syncSidebarToggleState();
               return;
            }
            const isCollapsed = document.body.classList.toggle('collapsed-sidebar');
            setUserSidebarPreference(isCollapsed);
            closeDashboardSidebar();
            syncSidebarToggleState();
         }

         function initializeDashboardSidebar() {
            if (window.innerWidth >= 992 && getUserSidebarPreference() === '1') {
               document.body.classList.add('collapsed-sidebar');
            } else if (window.innerWidth < 992) {
               document.body.classList.remove('collapsed-sidebar');
            }
            closeDashboardSidebar();
            syncSidebarToggleState();
         }

         initializeDashboardSidebar();

         window.addEventListener('resize', function() {
            if (window.innerWidth < 992) {
               document.body.classList.remove('collapsed-sidebar');
            } else if (getUserSidebarPreference() === '1') {
               document.body.classList.add('collapsed-sidebar');
            }
            closeDashboardSidebar();
            syncSidebarToggleState();
         });

         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js?v=11').then(function(registration) {
                  console.log('ServiceWorker registration successful with scope: ', registration.scope);
               }, function(err) {
                  console.log('ServiceWorker registration failed: ', err);
               });
            });
         }
         
         function triggerMobTutorial(attempt = 0) {
            if (typeof window.startOnboardingTour === 'function') {
               const started = window.startOnboardingTour();
               if (started !== false) return;
            }

            if (typeof window.startOnboardingTour !== 'function' && typeof window.initSpeakReadyFallbackTour === 'function') {
               window.initSpeakReadyFallbackTour(window.SpeakReadyTourContext || {});
            }

            if (typeof window.startOnboardingTour === 'function') {
               const started = window.startOnboardingTour();
               if (started !== false) return;
            }

            if (attempt < 20) {
               window.setTimeout(function() {
                  triggerMobTutorial(attempt + 1);
               }, 100);
               return;
            }

            console.warn('Tutorial could not initialize on this page.');
         }
         
         const userNotificationsUrl = @json(route('user.notifications'));
         const notificationJsonHeaders = {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
         };

         function toggleNotif(e) {
            e?.stopPropagation?.();
            const dd = document.getElementById('notifDropdown');
            if (!dd) return;

            const willOpen = !dd.classList.contains('open');
            closeUserHeaderDropdowns(willOpen ? 'notifDropdown' : null);
            setUserDropdownState('notifDropdown', 'bellBtn', willOpen);
            if (willOpen && dd.getAttribute('data-loaded') !== 'true') {
               fetchNotifications();
            }
         }

         function escapeNotifHtml(value) {
            const div = document.createElement('div');
            div.textContent = value == null ? '' : String(value);
            return div.innerHTML;
         }

         function safeNotificationIcon(value) {
            const icon = String(value || 'fa-bell').trim();

            return /^fa-[a-z0-9-]+$/i.test(icon) ? icon : 'fa-bell';
         }

         function renderNotificationStatus(message, isError = false) {
            const listContainer = document.getElementById('notifListContainer');
            if (!listContainer) return;

            const retry = isError
               ? '<button type="button" class="user-notif-retry" onclick="fetchNotifications()">Try again</button>'
               : '';
            listContainer.innerHTML = `<div class="user-notif-status ${isError ? 'error' : ''}">${escapeNotifHtml(message)}${retry}</div>`;
         }

         function requestNotificationJson(url, options = {}) {
            return fetch(url, {
               ...options,
               headers: {
                  ...notificationJsonHeaders,
                  ...(options.headers || {})
               }
            }).then(async (res) => {
               const contentType = res.headers.get('content-type') || '';
               if (!res.ok) {
                  throw new Error('Notification request failed with status ' + res.status);
               }
               if (!contentType.includes('application/json')) {
                  throw new Error('Notification request returned a non-JSON response.');
               }

               return res.json();
            });
         }

         function fetchNotifications(options = {}) {
            const quiet = Boolean(options.quiet);
            const dropdown = document.getElementById('notifDropdown');
            const isDropdownOpen = dropdown?.classList.contains('open');
            if (!quiet && dropdown?.getAttribute('data-loaded') !== 'true') {
               renderNotificationStatus('Loading notifications...');
            }

            requestNotificationJson('/notifications/fetch')
               .then(data => {
                  updateNotifUI(data);
                  dropdown?.setAttribute('data-loaded', 'true');
               })
               .catch(err => {
                  if (!quiet) {
                     console.error('Error fetching notifications:', err);
                  }
                  if (!quiet && isDropdownOpen) {
                     renderNotificationStatus('Notifications could not load right now.', true);
                  }
               });
         }

         function updateNotifUI(data) {
            const badge = document.getElementById('notifBadge');
            const unreadBadge = document.getElementById('unreadCountBadge');
            const listContainer = document.getElementById('notifListContainer');
            if (!badge || !unreadBadge || !listContainer) return;

            const unreadCount = Number(data?.unreadCount || 0);
            const notifications = Array.isArray(data?.notifications) ? data.notifications : [];

            if (unreadCount > 0) {
               badge.style.display = 'block';
               unreadBadge.style.display = 'inline-block';
               unreadBadge.textContent = unreadCount + ' new';
            } else {
               badge.style.display = 'none';
               unreadBadge.style.display = 'none';
            }

            if (notifications.length === 0) {
               renderNotificationStatus('No notifications to show.');
               return;
            }

            let html = '';
            const notificationUrl = escapeNotifHtml(userNotificationsUrl);
            notifications.forEach(n => {
               const unreadClass = n.read_at ? '' : 'notif-unread';
               const title = escapeNotifHtml(n.data?.title || 'Notification');
               const message = escapeNotifHtml(n.data?.message || '');
               const icon = escapeNotifHtml(safeNotificationIcon(n.data?.icon));
               const id = escapeNotifHtml(n.id);
               const createdAt = new Date(n.created_at);
               const date = escapeNotifHtml(Number.isNaN(createdAt.getTime()) ? 'Recently' : createdAt.toLocaleString());

               html += `
                  <div class="notif-item user-notif-item ${unreadClass}">
                     <a class="notif-ico user-notif-ico" href="${notificationUrl}" aria-label="Open notifications page"><i class="fa-solid ${icon}"></i></a>
                     <div class="user-notif-copy">
                        <a class="user-notif-open" href="${notificationUrl}">
                           <strong>${title}</strong>
                           <span>${message}</span>
                           <small><i class="fa-regular fa-clock me-1"></i>${date}</small>
                        </a>
                        <div class="user-notif-row-actions">
                           ${n.read_at ? '' : `<button class="user-notif-link-btn" type="button" data-notif-action="read" data-notif-id="${id}">Mark as read</button>`}
                           <button class="user-notif-link-btn danger" type="button" data-notif-action="delete" data-notif-id="${id}">Delete</button>
                        </div>
                     </div>
                  </div>
               `;
            });

            listContainer.innerHTML = html;
         }

         function markAllNotificationsRead() {
            requestNotificationJson('/notifications/read-all', { method: 'POST' })
               .then(data => {
                  if(data.success) {
                     fetchNotifications();
                     if(typeof reloadNotificationsPage === 'function') reloadNotificationsPage();
                  }
               })
               .catch(err => {
                  console.error('Error marking notifications read:', err);
                  renderNotificationStatus('Could not mark notifications as read.', true);
               });
         }

         function clearAllNotificationsDD() {
            window.SpeakReadyConfirm.show({
               title: 'Clear all notifications?',
               message: 'This will permanently remove all notifications from your account.',
               action: 'Clear All',
               variant: 'danger'
            }).then((isConfirmed) => {
               if (!isConfirmed) return;

               requestNotificationJson('/notifications/clear-all', { method: 'DELETE' })
                  .then(data => {
                     if(data.success) {
                        fetchNotifications();
                        if(typeof reloadNotificationsPage === 'function') reloadNotificationsPage();
                     }
                  })
                  .catch(err => {
                     console.error('Error clearing notifications:', err);
                     renderNotificationStatus('Could not clear notifications.', true);
                  });
            });
         }

         function markReadDD(id, e) {
            e?.stopPropagation?.();
            if (!id) return;

            requestNotificationJson('/notifications/' + encodeURIComponent(id) + '/read', { method: 'POST' })
               .then(data => {
                  if(data.success) {
                     fetchNotifications();
                  }
               })
               .catch(err => {
                  console.error('Error marking notification read:', err);
                  renderNotificationStatus('Could not update that notification.', true);
               });
         }

         function deleteNotificationDD(id, e) {
            e?.stopPropagation?.();
            if (!id) return;

            window.SpeakReadyConfirm.show({
               title: 'Delete notification?',
               message: 'This notification will be permanently removed.',
               action: 'Delete',
               variant: 'danger'
            }).then((isConfirmed) => {
               if (!isConfirmed) return;

               requestNotificationJson('/notifications/' + encodeURIComponent(id), { method: 'DELETE' })
                  .then(data => {
                     if(data.success) {
                        fetchNotifications();
                     }
                  })
                  .catch(err => {
                     console.error('Error deleting notification:', err);
                     renderNotificationStatus('Could not delete that notification.', true);
                  });
            });
         }

         // Fetch initially to set badge
         document.addEventListener('DOMContentLoaded', function() {
            fetchNotifications({ quiet: true });
            // Poll every minute
            setInterval(() => fetchNotifications({ quiet: true }), 60000);
         });

         document.addEventListener('click', function(event) {
            const actionButton = event.target.closest('#notifDropdown [data-notif-action]');
            if (!actionButton) return;

            event.preventDefault();
            const notificationId = actionButton.dataset.notifId;
            if (actionButton.dataset.notifAction === 'read') {
               markReadDD(notificationId, event);
            } else if (actionButton.dataset.notifAction === 'delete') {
               deleteNotificationDD(notificationId, event);
            }
         });

         document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
               closeUserHeaderDropdowns();
            }
         });
      </script>
      <style>
         @media (min-width: 992px) {
            body.user-desktop-shell .db-section :is(
               h1, h2, h3, h4, h5, h6,
               .text-gradient-primary,
               .sr-page-hero-title,
               .modules-hero-title,
               .module-smart-title,
               .module-card-title,
               .module-rec-copy strong,
               .module-path-copy strong,
               .mission-title,
               .mission-card-name,
               .vr-assessment-title,
               .vr-progress-title,
               .instant-feedback-title,
               .intention-coach-title,
               .setup-hero-title,
               .progress-hero-title,
               .feedback-title,
               .notif-hero-title,
               .mastery-title
            ) {
               color: var(--tx) !important;
               -webkit-text-fill-color: var(--tx) !important;
            }

            body.user-desktop-shell #dashboard .db-content #sec-interview-setup .setup-hero :is(.setup-hero-title, .setup-hero-title.text-gradient-primary) {
               background: none !important;
               color: #ffffff !important;
               -webkit-text-fill-color: #ffffff !important;
            }

            body.user-desktop-shell #dashboard .db-content #sec-interview-setup .setup-hero :is(.setup-hero-subtitle, .setup-hero-icon, .setup-hero-icon svg) {
               color: rgba(248, 251, 255, 0.92) !important;
               -webkit-text-fill-color: rgba(248, 251, 255, 0.92) !important;
            }

            body.user-desktop-shell #dashboard .db-content #sec-interview-setup .setup-hero .setup-hero-icon {
               background: rgba(255, 255, 255, 0.94) !important;
               border-color: rgba(255, 255, 255, 0.62) !important;
               color: #1d4ed8 !important;
               -webkit-text-fill-color: #1d4ed8 !important;
            }

            body.user-desktop-shell #dashboard .db-content #sec-interview-setup .setup-hero .setup-hero-icon svg {
               color: #1d4ed8 !important;
               -webkit-text-fill-color: #1d4ed8 !important;
            }

            body.user-desktop-shell #dashboard .db-content #interview-modules-page .modules-page-hero :is(.sr-page-hero-title, .sr-page-hero-title.text-gradient-primary) {
               background: none !important;
               color: #ffffff !important;
               -webkit-text-fill-color: #ffffff !important;
            }

            body.user-desktop-shell #dashboard .db-content #interview-modules-page .modules-page-hero .sr-page-hero-subtitle {
               color: rgba(248, 251, 255, 0.92) !important;
               -webkit-text-fill-color: rgba(248, 251, 255, 0.92) !important;
            }

            body.user-desktop-shell #dashboard .db-content #interview-modules-page .modules-page-hero .modules-page-hero-icon {
               background: rgba(255, 255, 255, 0.94) !important;
               border-color: rgba(255, 255, 255, 0.62) !important;
               color: #1d4ed8 !important;
               -webkit-text-fill-color: #1d4ed8 !important;
            }

            body.user-desktop-shell #dashboard .db-content #interview-modules-page .modules-page-hero .modules-page-hero-icon svg {
               color: #1d4ed8 !important;
               -webkit-text-fill-color: #1d4ed8 !important;
            }

         }
      </style>
      @if($isMobile ?? false)
      <style>
         #progressModulesLikeHero.progress-hero,
         #feedbackModulesLikeHero.feedback-hero,
         #sec-interview-setup .setup-hero,
         #interview-modules-page .modules-hero,
         #learning-games-page .sr-learning-hero,
         #ai-coach-page .sr-page-hero.coach-progress-hero,
         #portfolioReport .sr-page-hero,
         #notifications-page .notif-hero,
         #account-page .sr-page-hero,
         #skill-trees-page .sr-page-hero.skill-tree-hero,
         .sr-page-hero {
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         :root:not(.lm) #progressModulesLikeHero.progress-hero,
         :root:not(.lm) #feedbackModulesLikeHero.feedback-hero,
         :root:not(.lm) #sec-interview-setup .setup-hero,
         :root:not(.lm) #interview-modules-page .modules-hero,
         :root:not(.lm) #learning-games-page .sr-learning-hero,
         :root:not(.lm) #ai-coach-page .sr-page-hero.coach-progress-hero,
         :root:not(.lm) #portfolioReport .sr-page-hero,
         :root:not(.lm) #notifications-page .notif-hero,
         :root:not(.lm) #account-page .sr-page-hero,
         :root:not(.lm) #skill-trees-page .sr-page-hero.skill-tree-hero,
         :root:not(.lm) .sr-page-hero,
         .dm #progressModulesLikeHero.progress-hero,
         .dm #feedbackModulesLikeHero.feedback-hero,
         .dm #sec-interview-setup .setup-hero,
         .dm #interview-modules-page .modules-hero,
         .dm #learning-games-page .sr-learning-hero,
         .dm #ai-coach-page .sr-page-hero.coach-progress-hero,
         .dm #portfolioReport .sr-page-hero,
         .dm #notifications-page .notif-hero,
         .dm #account-page .sr-page-hero,
         .dm #skill-trees-page .sr-page-hero.skill-tree-hero,
         .dm .sr-page-hero {
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         #progressModulesLikeHero :is(.progress-hero-title, .progress-hero-subtitle, .progress-hero-icon),
         #feedbackModulesLikeHero :is(.feedback-title, .feedback-subtitle, .feedback-chat-mark),
         #sec-interview-setup .setup-hero :is(.setup-hero-title, .setup-hero-subtitle, .setup-hero-icon),
         #interview-modules-page .modules-hero :is(.modules-hero-title, .modules-hero-subtitle, .modules-hero-icon),
         #learning-games-page .sr-learning-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .learning-hero-icon),
         #ai-coach-page .coach-progress-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .coach-hero-icon),
         #portfolioReport .sr-page-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .reports-hero-icon),
         #notifications-page .notif-hero :is(.notif-hero-title, .notif-hero-subtitle, .notif-hero-icon),
         #account-page .sr-page-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .account-hero-icon),
         #skill-trees-page .skill-tree-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .skill-tree-hero-icon),
         .sr-page-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle) {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
         }

         #feedbackModulesLikeHero .feedback-title {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         #sec-interview-setup .setup-hero .setup-hero-title {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            text-transform: uppercase !important;
         }

         #sec-interview-setup .setup-hero .setup-hero-subtitle {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         #progressModulesLikeHero .progress-hero-subtitle,
         #feedbackModulesLikeHero .feedback-subtitle,
         #sec-interview-setup .setup-hero-subtitle,
         #interview-modules-page .modules-hero-subtitle,
         #learning-games-page .sr-learning-hero .sr-page-hero-subtitle,
         #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle,
         #portfolioReport .sr-page-hero-subtitle,
         #notifications-page .notif-hero-subtitle,
         #account-page .sr-page-hero-subtitle,
         #skill-trees-page .skill-tree-hero .sr-page-hero-subtitle,
         .sr-page-hero .sr-page-hero-subtitle {
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
         }

         #progressModulesLikeHero .progress-hero-icon,
         #sec-interview-setup .setup-hero-icon,
         #interview-modules-page .modules-hero-icon,
         #learning-games-page .learning-hero-icon,
         #ai-coach-page .coach-hero-icon,
         #portfolioReport .reports-hero-icon,
         #notifications-page .notif-hero-icon,
         #account-page .account-hero-icon,
         #skill-trees-page .skill-tree-hero-icon,
         #feedbackModulesLikeHero .feedback-chat-mark {
            background: rgba(15, 23, 42, 0.16) !important;
            border-color: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         #progressModulesLikeHero.progress-hero::before,
         #progressModulesLikeHero.progress-hero::after,
         #interview-modules-page .modules-hero::before,
         #interview-modules-page .modules-hero::after,
         #learning-games-page .sr-learning-hero::before,
         #learning-games-page .sr-learning-hero::after {
            content: none !important;
            display: none !important;
         }

         html body #progressModulesLikeHero.progress-hero {
            grid-template-columns: 30px minmax(0, 1fr) !important;
            gap: 8px !important;
            min-height: 69px !important;
            padding: 8px 72px 8px 10px !important;
            margin-bottom: 10px !important;
            border-radius: 8px !important;
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         html body #progressModulesLikeHero.progress-hero :is(.progress-hero-title, .progress-hero-subtitle, .progress-hero-icon) {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-subtitle {
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-icon {
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
            font-size: 0.8rem !important;
            background: rgba(15, 23, 42, 0.16) !important;
            border-color: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-title {
            font-size: 0.72rem !important;
            line-height: 1.15 !important;
            margin: 0 0 3px !important;
            white-space: nowrap !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-subtitle {
            font-size: 0.49rem !important;
            line-height: 1.32 !important;
         }

         html body #progressModulesLikeHero.progress-hero .progress-hero-art {
            right: -5px !important;
            bottom: -2px !important;
            width: 72px !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero {
            grid-template-columns: 30px minmax(0, 1fr) !important;
            gap: 8px !important;
            min-height: 69px !important;
            padding: 8px 72px 8px 10px !important;
            margin-bottom: 10px !important;
            border-radius: 8px !important;
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero :is(.modules-hero-title, .modules-hero-subtitle, .modules-hero-icon) {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-icon {
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
            font-size: 0.8rem !important;
            background: rgba(15, 23, 42, 0.16) !important;
            border-color: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-icon svg {
            width: 15px !important;
            height: 15px !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-title {
            font-size: 0.72rem !important;
            line-height: 1.15 !important;
            margin: 0 0 3px !important;
            white-space: nowrap !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
            font-size: 0.49rem !important;
            line-height: 1.32 !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-art {
            right: -5px !important;
            bottom: -2px !important;
            width: 72px !important;
         }

         @media (max-width: 390px) {
            html body #interview-modules-page .modules-hero.modules-hero {
               grid-template-columns: 28px minmax(0, 1fr) !important;
               gap: 7px !important;
               padding: 8px 66px 8px 9px !important;
            }

            html body #interview-modules-page .modules-hero.modules-hero .modules-hero-icon {
               width: 27px !important;
               height: 27px !important;
            }

            html body #interview-modules-page .modules-hero.modules-hero .modules-hero-title {
               font-size: 0.68rem !important;
            }

            html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
               font-size: 0.46rem !important;
            }

            html body #interview-modules-page .modules-hero.modules-hero .modules-hero-art {
               width: 66px !important;
            }
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero {
            grid-template-columns: 30px minmax(0, 1fr) !important;
            gap: 8px !important;
            min-height: 69px !important;
            padding: 8px 72px 8px 10px !important;
            margin-bottom: 10px !important;
            border-radius: 8px !important;
            background:
               radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
               radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
               linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .learning-hero-icon) {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-title {
            font-size: 0.72rem !important;
            line-height: 1.15 !important;
            margin: 0 0 3px !important;
            white-space: nowrap !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-subtitle {
            max-width: 13.5rem !important;
            font-size: 0.49rem !important;
            line-height: 1.32 !important;
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero .learning-hero-icon {
            width: 28px !important;
            height: 28px !important;
            border-radius: 8px !important;
            font-size: 0.8rem !important;
            background: rgba(15, 23, 42, 0.16) !important;
            border-color: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
         }

         html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-art {
            right: -5px !important;
            bottom: -2px !important;
            width: 72px !important;
         }

         @media (max-width: 390px) {
            html body #learning-games-page .sr-learning-hero.sr-learning-hero {
               grid-template-columns: 28px minmax(0, 1fr) !important;
               gap: 7px !important;
               padding: 8px 66px 8px 9px !important;
            }

            html body #learning-games-page .sr-learning-hero.sr-learning-hero .learning-hero-icon {
               width: 27px !important;
               height: 27px !important;
            }

            html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-title {
               font-size: 0.68rem !important;
            }

            html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-subtitle {
               font-size: 0.46rem !important;
            }

            html body #learning-games-page .sr-learning-hero.sr-learning-hero .sr-page-hero-art {
               width: 66px !important;
            }
         }
      </style>
      @endif
      @include('desktop.partials.desktop-theme-contrast')
      @include('desktop.partials.user-theme-contrast')
      @include('desktop.partials.onboarding-script')
      <!-- USER_PAGE_SCRIPTS_START -->
      @stack('scripts')
      @include('desktop.partials.onboarding-fallback-init')
      <!-- USER_PAGE_SCRIPTS_END -->
      @include('desktop.partials.page-transition')
      @include('desktop.layouts.logout-transition')
   </body>
</html>
