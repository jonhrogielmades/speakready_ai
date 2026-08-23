<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta name="theme-color" content="#ffffff">
      <title>SpeakReady AI Interview Admin Portal</title>
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
      <link rel="stylesheet" href="{{ asset('css/desktop/style.css?v=28') }}" />
      <style>
          .db-nl { text-decoration: none; display: flex; align-items: center; }
          .admin-brand { color: var(--tx) !important; font-weight: 700; }
          .db-nl[aria-expanded="true"] .toggle-icon { transform: rotate(180deg); }
          .admin-shell .modal {
             --bs-modal-bg: var(--sf);
             --bs-modal-color: var(--tx);
          }
          .admin-shell .modal-dialog {
             margin: 1.25rem auto;
          }
          .admin-shell .modal:not(.admin-fullscreen-modal) .modal-dialog {
             min-height: calc(100% - 2.5rem);
             display: flex;
             align-items: center;
          }
          .admin-shell .modal-content {
             width: 100%;
             max-height: calc(100vh - 2.5rem);
             overflow: hidden;
             background: var(--sf) !important;
             color: var(--tx) !important;
             border: 1px solid var(--bd) !important;
             border-radius: 16px !important;
             box-shadow: 0 24px 70px rgba(0, 0, 0, .26);
          }
          .admin-shell .modal-header,
          .admin-shell .modal-footer {
             border-color: var(--bd) !important;
             background: var(--sf) !important;
             color: var(--tx) !important;
          }
          .admin-shell .modal-title,
          .admin-shell .modal label,
          .admin-shell .modal .form-label,
          .admin-shell .modal p,
          .admin-shell .modal small,
          .admin-shell .modal span:not(.badge):not([class*="text-"]) {
             color: var(--tx) !important;
          }
          .admin-shell .modal .text-muted {
             color: var(--tx2) !important;
          }
          .admin-shell .modal-body {
             max-height: min(72vh, 760px);
             overflow-y: auto;
             background: var(--sf) !important;
             color: var(--tx) !important;
          }
          .admin-shell .modal .form-control,
          .admin-shell .modal .form-select,
          .admin-shell .modal textarea,
          .admin-shell .modal input {
             background: var(--bg3) !important;
             color: var(--tx) !important;
             border: 1px solid var(--bd) !important;
          }
          .admin-shell .modal .form-control::placeholder,
          .admin-shell .modal textarea::placeholder,
          .admin-shell .modal input::placeholder {
             color: var(--tx3) !important;
             font-weight: 400 !important;
          }
          .admin-shell .modal .btn-close {
             opacity: .85;
             filter: var(--admin-close-filter, invert(1));
          }
          .lm .admin-shell .modal .btn-close {
             --admin-close-filter: none;
          }
          .admin-shell .modal-footer {
             display: grid !important;
             grid-template-columns: repeat(2, minmax(0, 1fr));
             gap: .75rem;
          }
          .admin-shell .modal-footer .btn,
          .admin-shell .modal-footer button {
             width: 100%;
             min-height: 42px;
             border-radius: 10px !important;
             font-weight: 700;
          }
          .admin-shell .modal-footer .btn-outline-secondary,
          .admin-shell .modal-footer [data-bs-dismiss="modal"] {
             border: 1px solid var(--bd) !important;
             color: var(--tx) !important;
             background: var(--bg3) !important;
          }
          .admin-shell .modal-footer .btn:only-child {
             grid-column: 1 / -1;
          }
          .admin-shell .modal :is(a, span, strong, div, p, td, th):not(.badge) {
             overflow-wrap: anywhere;
             word-break: break-word;
          }
          .admin-shell .modal :is(.table-responsive, .table-responsive-sm, .table-responsive-md, .table-responsive-lg, .table-responsive-xl) {
             max-width: 100%;
          }
          .admin-shell .modal table {
             width: 100%;
          }
          .admin-shell .modal table :is(td, th) {
             min-width: 0;
             max-width: 100%;
             white-space: normal;
             overflow-wrap: anywhere;
          }
          .admin-shell .modal .premium-card,
          .admin-shell .modal .card,
          .admin-shell .modal .list-group-item,
          .admin-shell .modal .custom-switch-container {
             max-width: 100%;
          }
      </style>
      @stack('styles')
   </head>
   <body class="admin-shell desktop-shell" data-layout-shell="desktop" data-app-surface="admin">
      <div id="dashboard">
         <!-- Sidebar -->
         <div class="db-sidebar" id="dbSidebar">
             <div class="db-logo d-flex justify-content-between align-items-center">
                <div class="db-brand d-flex align-items-center gap-2">
                   <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: #ffffff; padding: 0;">
                   <span class="admin-brand db-brand-text">SpeakReady AI Admin</span>
                </div>
                <button class="db-sidebar-close d-lg-none" type="button" aria-label="Close navigation" onclick="closeDashboardSidebar()">
                   <i class="fa-solid fa-xmark"></i>
                </button>
             </div>
            <div class="db-nav">
               <div class="db-nav-section">Interview Modules</div>
               <a href="{{ route('admin.dashboard') }}" class="db-nl db-nav-blue {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" title="Interview Dashboard"><i class="fa-solid fa-gauge-high"></i><span class="db-nav-label">Interview Dashboard</span></a>
               <a href="{{ route('admin.users.index') }}" class="db-nl db-nav-purple {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" title="User Management"><i class="fa-solid fa-users"></i><span class="db-nav-label">User Management</span></a>
               <a href="{{ route('admin.categories') }}" class="db-nl db-nav-cyan {{ request()->routeIs('admin.categories') ? 'active' : '' }}" title="Interview Categories"><i class="fa-solid fa-list"></i><span class="db-nav-label">Interview Categories</span></a>
               <a href="{{ route('admin.questions') }}" class="db-nl db-nav-amber {{ request()->routeIs('admin.questions') ? 'active' : '' }}" title="Question Bank"><i class="fa-solid fa-circle-question"></i><span class="db-nav-label">Question Bank</span></a>
               <a href="{{ route('admin.modules') }}" class="db-nl db-nav-emerald {{ request()->routeIs('admin.modules') || request()->routeIs('admin.modules.*') ? 'active' : '' }}" title="Interview Lessons"><i class="fa-solid fa-book-open"></i><span class="db-nav-label">Interview Lessons</span></a>
               <a href="{{ route('admin.game') }}" class="db-nl db-nav-rose {{ request()->routeIs('admin.game') || request()->routeIs('admin.game.*') ? 'active' : '' }}" title="Interview Games"><i class="fa-solid fa-gamepad"></i><span class="db-nav-label">Interview Games</span></a>
               
               <div class="db-nav-section">Interview Monitoring</div>
               <a href="{{ route('admin.sessions.index') }}" class="db-nl db-nav-indigo {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}" title="Session Monitoring"><i class="fa-solid fa-video"></i><span class="db-nav-label">Session Monitoring</span></a>
               <a href="{{ route('admin.feedback.index') }}" class="db-nl db-nav-emerald {{ request()->routeIs('admin.feedback.*') && !request()->routeIs('admin.feedback.complaints') ? 'active' : '' }}" title="Feedback Audit"><i class="fa-solid fa-clipboard-check"></i><span class="db-nav-label">Feedback Audit</span></a>
               <a href="{{ route('admin.feedback.complaints') }}" class="db-nl db-nav-rose {{ request()->routeIs('admin.feedback.complaints') ? 'active' : '' }}" title="User Complaints"><i class="fa-solid fa-clipboard-list"></i><span class="db-nav-label">User Complaints</span></a>
               <a href="{{ route('admin.contacts.index') }}" class="db-nl db-nav-cyan {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}" title="Contact Messages"><i class="fa-solid fa-envelope"></i><span class="db-nav-label">Contact Messages</span></a>
               
               <div class="db-nav-section">System</div>
               
               <a href="{{ route('admin.ai.providers') }}" class="db-nl db-nav-purple {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}" title="AI Providers"><i class="fa-solid fa-microchip"></i><span class="db-nav-label">AI Providers</span></a>
               
               <a href="{{ route('admin.settings.index') }}" class="db-nl db-nav-blue {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" title="System Settings"><i class="fa-solid fa-gear"></i><span class="db-nav-label">System Settings</span></a>
            </div>
            <div class="db-bottom">
               <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                  @csrf
                  <button type="submit" class="db-nl db-nav-danger" title="Log Out" style="color:#f87171; width:100%; text-align:left; border:none; background:none;"><i class="fa-solid fa-right-from-bracket"></i><span class="db-nav-label">Log Out</span></button>
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
               <div class="db-page-context d-none d-xl-flex">
                  <span class="db-page-eyebrow">PH interview admin</span>
                  <strong>{{ trim($__env->yieldContent('page-title')) ?: 'Dashboard' }}</strong>
               </div>
               <div class="db-top-search">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" aria-label="Search PH interview admin portal" placeholder="Search PH interview admin">
               </div>
               <div class="db-top-actions ms-auto d-flex align-items-center gap-3 flex-shrink-0">
                  <div class="dropdown">
                      <button class="boc d-flex align-items-center justify-content-center position-relative db-activity-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open live activity" title="Live Activity" style="width:38px;height:38px;padding:0;border-radius:12px;text-decoration:none;color:var(--tx);" onclick="resetAdminActivityBadge('desktop')">
                          <i class="fa-regular fa-bell"></i>
                          <span id="admin-activity-badge-desktop" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="display:none; width: 10px; height: 10px;">
                              <span class="visually-hidden">New alerts</span>
                          </span>
                      </button>
                      <div class="dropdown-menu dropdown-menu-end shadow-lg" style="width: 320px; border-radius: 12px; border: 1px solid var(--bd); background: var(--bg3); padding: 0; overflow: hidden; margin-top: 10px;">
                          <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--bd) !important; background: var(--sf);">
                              <div>
                                  <span class="fw-bold" style="color: var(--tx); font-size: 0.95rem;">Live User Activity</span>
                                  <span id="admin-activity-count-desktop" class="badge bg-danger rounded-pill ms-1" style="display:none;">0</span>
                              </div>
                              <div class="dropdown">
                                  <button class="btn btn-sm p-0 m-0 text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false" onclick="event.stopPropagation();">
                                      <i class="fa-solid fa-ellipsis-vertical"></i>
                                  </button>
                                  <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius:8px; border:1px solid var(--bd); background:var(--bg3);">
                                      <li><a class="dropdown-item" href="#" onclick="markAllActivitiesRead(event)"><i class="fa-solid fa-check-double me-2 text-primary"></i>Mark all as read</a></li>
                                      <li><hr class="dropdown-divider" style="border-color:var(--bd)"></li>
                                      <li><a class="dropdown-item text-danger" href="#" onclick="clearAllActivities(event)"><i class="fa-solid fa-trash-can me-2"></i>Clear all</a></li>
                                  </ul>
                              </div>
                          </div>
                          <div id="admin-activity-list-desktop" style="max-height: 300px; overflow-y: auto; background: var(--bg2);">
                              <div class="p-3 text-center text-muted" style="font-size:0.85rem;">Loading activities...</div>
                          </div>
                          <div class="p-2 border-top text-center" style="border-color: var(--bd) !important; background: var(--sf);">
                              <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm w-100 fw-bold" style="background: rgba(59,130,246,0.15); color: #3b82f6; border-radius: 8px;">
                                  <i class="fa-solid fa-list-check me-2"></i>View All Activities
                              </a>
                          </div>
                      </div>
                  </div>
                  <button class="boc d-flex align-items-center justify-content-center" id="dbThBtn" type="button" aria-label="Toggle color theme" title="Toggle color theme" style="width:38px;height:38px;padding:0;border-radius:12px" onclick="toggleTheme()">
                  <i class="fa-solid fa-sun" id="dbSunI" style="display:none"></i>
                  <i class="fa-solid fa-moon" id="dbMoonI"></i>
                  </button>
                  <div style="position:relative" id="profileWrap">
                     <div class="db-user-pill" id="userPill" onclick="toggleProfile(event)">
                        @if(Auth::check() && Auth::user()->profile_photo_path)
                                @php
                                    $photoPath = Auth::user()->profile_photo_path;
                                    $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                                @endphp
                            <div class="db-avatar" id="userAvatar" style="padding:0;overflow:hidden;border:1px solid var(--bd);">
                                <img src="{{ $photoUrl }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        @else
                            <div class="db-avatar" id="userAvatar" style="background:#f87171;color:#fff">{{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'A' }}</div>
                        @endif
                        <div class="d-none d-md-block">
                           <div style="font-size:.85rem;font-weight:600;line-height:1.2" id="userName">{{ Auth::user()->name ?? 'Admin' }}</div>
                        </div>
                        <i class="fa-solid fa-chevron-down fa-xs" id="profileChevron" style="color:var(--tx3);margin-left:2px;transition:.3s"></i>
                     </div>
                     <!-- Profile Dropdown -->
                     <div class="db-dropdown profile-dd" id="profileDropdown" style="right:0">
                        <div style="padding:8px 0">
                           <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                              @csrf
                              <button type="submit" class="profile-menu-item danger" style="width:100%;text-align:left;"><i class="fa-solid fa-right-from-bracket" style="color:#f87171"></i>Log Out</button>
                           </form>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="db-content">
                @yield('content')
                @include('desktop.partials.admin-motion-title-svg')
            </div>
         </div>
      </div>
      @stack('modals')
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
      <script src="{{ asset('js/main.js?v=7') }}"></script>
      <!-- PWA Service Worker Registration -->
      <script>
         function closeDashboardSidebar() {
            document.getElementById('dbSidebar')?.classList.remove('mob-open');
            document.body.classList.remove('sidebar-open');
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
            localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? '1' : '0');
            closeDashboardSidebar();
            syncSidebarToggleState();
         }

         function initializeDashboardSidebar() {
            if (window.innerWidth >= 992 && localStorage.getItem('admin_sidebar_collapsed') === '1') {
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
            } else if (localStorage.getItem('admin_sidebar_collapsed') === '1') {
               document.body.classList.add('collapsed-sidebar');
            }
            closeDashboardSidebar();
            syncSidebarToggleState();
         });

         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js?v=10').then(function(registration) {
                  console.log('ServiceWorker registration successful with scope: ', registration.scope);
               }, function(err) {
                  console.log('ServiceWorker registration failed: ', err);
               });
            });
         }
      </script>
      <script>
          let adminLastSeenActivityId = localStorage.getItem('admin_last_seen_activity_id') || 0;
          let adminActivityNotificationsReady = false;

          function rememberLatestAdminActivity(activities) {
              if (!Array.isArray(activities) || activities.length === 0) return;

              const latestId = Math.max(...activities.map(activity => Number(activity.id) || 0));
              if (latestId > Number(adminLastSeenActivityId || 0)) {
                  adminLastSeenActivityId = latestId;
                  localStorage.setItem('admin_last_seen_activity_id', latestId);
              }
          }

          function requestAdminPwaNotificationPermission() {
              if (!('Notification' in window)) return;

              if (Notification.permission === 'default') {
                  Notification.requestPermission().catch(() => {});
              }
          }

          function showAdminPwaNotification(activity) {
              if (!activity || !('Notification' in window) || Notification.permission !== 'granted') return;

              const payload = {
                  type: 'SHOW_ADMIN_ACTIVITY_NOTIFICATION',
                  title: activity.title || 'Admin activity',
                  body: activity.body || 'A user activity was recorded.',
                  url: activity.url || '{{ route('admin.dashboard') }}',
                  tag: `admin-activity-${activity.id}`,
              };

              if (navigator.serviceWorker?.controller) {
                  navigator.serviceWorker.controller.postMessage(payload);
                  return;
              }

              navigator.serviceWorker?.ready
                  .then(registration => registration.active?.postMessage(payload))
                  .catch(() => {});
          }

          function notifyNewAdminAuthActivities(activities) {
              if (!Array.isArray(activities) || activities.length === 0) return;

              const newActivities = activities
                  .filter(activity => Number(activity.id) > Number(adminLastSeenActivityId || 0))
                  .sort((a, b) => Number(a.id) - Number(b.id));

              if (!adminActivityNotificationsReady) {
                  adminActivityNotificationsReady = true;
                  rememberLatestAdminActivity(activities);
                  return;
              }

              newActivities.forEach(showAdminPwaNotification);
              rememberLatestAdminActivity(activities);
          }

          function fetchAdminActivities() {
              fetch(`{{ route('admin.api.latest-activities') }}`)
                  .then(res => res.json())
                  .then(data => {
                      if (document.getElementById('admin-activity-list-desktop')) {
                          document.getElementById('admin-activity-list-desktop').innerHTML = data.html;
                      }
                      if (document.getElementById('admin-activity-list-mobile')) {
                          document.getElementById('admin-activity-list-mobile').innerHTML = data.html;
                      }
                      
                      const countEls = [document.getElementById('admin-activity-count-desktop'), document.getElementById('admin-activity-count-mobile')];
                      const badgeEls = [document.getElementById('admin-activity-badge-desktop'), document.getElementById('admin-activity-badge-mobile')];
                      
                      if (data.new_count > 0) {
                          countEls.forEach(el => { if(el) { el.style.display = 'inline-block'; el.innerText = data.new_count; } });
                          badgeEls.forEach(el => { if(el) { el.style.display = 'block'; } });
                      } else {
                          countEls.forEach(el => { if(el) { el.style.display = 'none'; } });
                          badgeEls.forEach(el => { if(el) { el.style.display = 'none'; } });
                      }

                      notifyNewAdminAuthActivities(data.auth_activities);
                  })
                  .catch(err => console.error('Error fetching activities:', err));
          }

          function resetAdminActivityBadge(type) {
              // Now we just mark all as read automatically when dropdown opens if desired,
              // or just let them stay blue until user manually marks as read. 
              // Usually clicking the bell just hides the badge.
              const countEls = [document.getElementById('admin-activity-count-desktop'), document.getElementById('admin-activity-count-mobile')];
              const badgeEls = [document.getElementById('admin-activity-badge-desktop'), document.getElementById('admin-activity-badge-mobile')];
              countEls.forEach(el => { if(el) { el.style.display = 'none'; } });
              badgeEls.forEach(el => { if(el) { el.style.display = 'none'; } });
              
              // Tell backend to mark all as read so it persists
              fetch(`/admin/api/activities/mark-all-read`, {
                  method: 'POST',
                  headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
              }).then(() => {
                  document.querySelectorAll('.admin-activity-item').forEach(el => {
                      if (el.style.background.includes('rgba')) {
                          el.style.background = 'transparent';
                      }
                  });
              });
          }

          function markActivityRead(id, event) {
              event.preventDefault();
              event.stopPropagation();
              fetch(`/admin/api/activities/${id}/mark-read`, {
                  method: 'POST',
                  headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
              }).then(() => fetchAdminActivities());
          }

          function deleteActivity(id, event) {
              event.preventDefault();
              event.stopPropagation();
              fetch(`/admin/api/activities/${id}`, {
                  method: 'DELETE',
                  headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
              }).then(() => fetchAdminActivities());
          }

          function markAllActivitiesRead(event) {
              event.preventDefault();
              event.stopPropagation();
              fetch(`/admin/api/activities/mark-all-read`, {
                  method: 'POST',
                  headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
              }).then(() => fetchAdminActivities());
          }

          function clearAllActivities(event) {
              event.preventDefault();
              event.stopPropagation();
              if(confirm('Are you sure you want to completely clear all activity logs? This cannot be undone.')) {
                  fetch(`/admin/api/activities/clear-all`, {
                      method: 'DELETE',
                      headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
                  }).then(() => fetchAdminActivities());
              }
          }

          document.addEventListener('DOMContentLoaded', function() {
              requestAdminPwaNotificationPermission();
              fetchAdminActivities();
              setInterval(fetchAdminActivities, 15000);
          });
      </script>

      @stack('late-styles')
      @include('desktop.partials.desktop-theme-contrast')
      @stack('scripts')
      @include('desktop.partials.page-transition')
      @include('desktop.layouts.logout-transition')
   </body>
</html>
