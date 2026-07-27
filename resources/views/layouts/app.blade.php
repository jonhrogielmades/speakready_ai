<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta name="theme-color" content="#ffffff">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <title>@yield('title', 'SpeakReady AI - AI-Based Interview Practice System')</title>
      <script src="{{ asset('js/theme-boot.js?v=1') }}"></script>
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
      <!-- Style CSS -->
      <link rel="stylesheet" href="{{ asset('css/style.css?v=23') }}" />
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
      </style>
      <!-- Driver.js -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
      @include('partials.onboarding-styles')
   </head>
   <body>
      <div id="dashboard">
         <!-- Sidebar -->
         <div class="db-sidebar" id="dbSidebar">
            <div class="db-logo d-flex justify-content-between align-items-center">
               <div class="d-flex align-items-center gap-2">
                  <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: transparent; padding: 0;">
                  <span>SpeakReady AI</span>
               </div>
               <button class="db-sidebar-close d-lg-none" type="button" aria-label="Close navigation" onclick="closeDashboardSidebar()">
                  <i class="fa-solid fa-xmark"></i>
               </button>
            </div>
            <div class="db-nav">
               <div class="db-nav-section">Dashboard</div>
               <a href="{{ route('dashboard') }}" class="db-nl db-nav-blue {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fa-solid fa-gauge-high"></i> Overview</a>


                <div class="db-nav-section">Interview Practice</div>
                <a href="{{ route('interview.setup') }}" class="db-nl db-nav-purple {{ request()->routeIs('interview.setup') ? 'active' : '' }}"><i class="fa-solid fa-microphone-lines"></i> Mock Interview</a>

                <div class="db-nav-section">Specialized Training</div>
               <a href="{{ route('user.modules.index') }}" class="db-nl db-nav-emerald {{ request()->routeIs('user.modules.*') ? 'active' : '' }}"><i class="fa-solid fa-book-open-reader"></i> Modules</a>
               <a href="{{ route('user.drills.voice') }}" class="db-nl db-nav-rose {{ request()->routeIs('user.drills.voice') ? 'active' : '' }}"><i class="fa-solid fa-ear-listen"></i> Voice Rehearsal</a>
               <a href="{{ route('user.missions') }}" class="db-nl db-nav-cyan {{ request()->routeIs('user.missions') ? 'active' : '' }}"><i class="fa-solid fa-route"></i> Missions</a>
               <a href="{{ route('user.learning') }}" class="db-nl db-nav-amber {{ request()->routeIs('user.learning') ? 'active' : '' }}"><i class="fa-solid fa-gamepad"></i> Challenges</a>
               <a href="{{ route('user.coach') }}" class="db-nl db-nav-purple {{ request()->routeIs('user.coach') ? 'active' : '' }}"><i class="fa-solid fa-robot"></i> Readiness Coach</a>

               <div class="db-nav-section">Performance</div>
               <a href="{{ route('user.progress') }}" class="db-nl db-nav-emerald {{ request()->routeIs('user.progress') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Progress</a>
               <a href="{{ route('user.feedback') }}" class="db-nl db-nav-blue {{ request()->routeIs('user.feedback') ? 'active' : '' }}"><i class="fa-solid fa-clipboard-check"></i> Feedback</a>
               <a href="{{ route('user.reports') }}" class="db-nl db-nav-cyan {{ request()->routeIs('user.reports') ? 'active' : '' }}"><i class="fa-solid fa-folder-open"></i> Reports</a>

               <div class="db-nav-section">Personal Goals</div>
               <a href="{{ route('user.leaderboard') }}" class="db-nl db-nav-amber {{ request()->routeIs('user.leaderboard') ? 'active' : '' }}"><i class="fa-solid fa-medal"></i> Mastery</a>
            </div>
            <div class="db-bottom">
               <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                  @csrf
                  <button type="submit" class="db-nl db-nav-danger" style="color:#f87171; width:100%; text-align:left; border:none; background:none;"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
               </form>
            </div>
         </div>
         <button class="db-sidebar-backdrop" type="button" aria-label="Close navigation" onclick="closeDashboardSidebar()"></button>
         <!-- Main Content Area -->
         <div class="db-main">
            <!-- Top bar -->
            <div class="db-top">
               <button class="boc db-sidebar-toggle" type="button" aria-label="Toggle navigation" title="Toggle navigation" onclick="toggleDashboardSidebar()">
               <i class="fa-solid fa-bars"></i>
               </button>
               <div class="db-page-context d-none d-xl-flex">
                  <span class="db-page-eyebrow">Workspace</span>
                  <strong>{{ trim($__env->yieldContent('page-title')) ?: (trim($__env->yieldContent('title')) ?: 'Overview') }}</strong>
               </div>
               <div class="db-top-search">
                  <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                  <button type="button" class="db-quick-launcher" data-ucp-open aria-haspopup="dialog" aria-controls="userCommandPalette" aria-expanded="false" aria-keyshortcuts="Control+K Meta+K" title="Open quick navigation (Ctrl/Command + K)">Quick navigation</button>
                  <kbd aria-hidden="true">Ctrl K</kbd>
               </div>
               <div class="ms-auto d-flex align-items-center gap-3 flex-shrink-0">
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
                     <button class="boc d-flex align-items-center justify-content-center" id="bellBtn" type="button" aria-label="Open notifications" title="Notifications" onclick="toggleNotif(event)">
                        <i class="fa-regular fa-bell"></i>
                     </button>
                     <span id="notifBadge" style="position:absolute;top:5px;right:5px;width:9px;height:9px;border-radius:50%;background:#f87171;border:2px solid var(--bg);display:none;"></span>
                     
                     <!-- Notification Dropdown -->
                     <div class="db-dropdown" id="notifDropdown" style="right:0;width:360px;max-width:calc(100vw - 30px);">
                        <div class="dd-header d-flex flex-wrap align-items-center justify-content-between gap-2" style="border-bottom:1px solid var(--bd);padding-bottom:12px;margin-bottom:12px;">
                           <div class="dd-header-title d-flex align-items-center mb-0">
                              <i class="fa-regular fa-bell me-2" style="color:var(--pur)"></i>Notifications 
                              <span id="unreadCountBadge" style="background:rgba(248,113,113,.15);color:#f87171;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:100px;margin-left:6px;display:none;">0 new</span>
                           </div>
                           <div class="d-flex align-items-center gap-1 flex-wrap">
                              <button class="dd-close" onclick="markAllNotificationsRead()" title="Mark all read" style="width:auto;padding:4px 8px;font-size:.7rem;color:var(--pur);border-color:rgba(59,130,246,.3)"><i class="fa-solid fa-check me-1"></i><span class="d-none d-sm-inline">Read All</span></button>
                              <button class="dd-close text-danger" onclick="clearAllNotificationsDD()" title="Clear all" style="width:auto;padding:4px 8px;font-size:.7rem;border-color:rgba(248,113,113,.3)"><i class="fa-solid fa-trash me-1"></i><span class="d-none d-sm-inline">Clear All</span></button>
                              <button class="dd-close" onclick="toggleNotif(event)" style="padding:4px 8px;width:auto;"><i class="fa-solid fa-xmark"></i></button>
                           </div>
                        </div>
                        <div class="dd-body" id="notifListContainer" style="max-height: 350px; overflow-y: auto;">
                           <div class="text-center py-4" style="color:var(--tx3);font-size:0.85rem;" id="noNotifMsg">Loading notifications...</div>
                        </div>
                        <div style="padding:12px 14px;border-top:1px solid var(--bd);text-align:center">
                           <a href="{{ route('user.notifications') }}" class="boc btn w-100 py-2" style="font-size:.82rem;border-radius:10px;text-decoration:none;"><i class="fa-solid fa-list me-1"></i>View All Notifications</a>
                        </div>
                     </div>
                  </div>

                  <div style="position:relative" id="profileWrap">
                     <div class="db-user-pill" id="userPill" onclick="toggleProfile(event)">
                        <div class="user-avatar-presence">
                    @if(Auth::check() && Auth::user()->profile_photo_path)
                           @php
                               $photoPath = Auth::user()->profile_photo_path;
                               $photoUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
                           @endphp
                           <div class="db-avatar" id="userAvatar" style="padding:0;overflow:hidden;border:1px solid var(--bd);">
                              <img src="{{ $photoUrl }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                           </div>
                    @else
                           <div class="db-avatar" id="userAvatar">{{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U' }}</div>
                    @endif
                        </div>
                        <div class="d-none d-md-block">
                           <div style="font-size:.85rem;font-weight:600;line-height:1.2" id="userName">{{ Auth::user()->name ?? 'User' }}</div>
                           <div style="font-size:.72rem;color:var(--tx3)" id="userPlan">{{ Auth::check() && Auth::user()->is_admin ? 'ADMIN' : 'USER' }}</div>
                        </div>
                        <i class="fa-solid fa-chevron-down fa-xs" id="profileChevron" style="color:var(--tx3);margin-left:2px;transition:.3s"></i>
                     </div>
                     <!-- Profile Dropdown -->
                     <div class="db-dropdown profile-dd" id="profileDropdown" style="right:0">
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
                           <div style="border-top:1px solid var(--bd);margin:8px 0"></div>
                           <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                              @csrf
                              <button type="submit" class="profile-menu-item danger" style="width:100%;text-align:left;"><i class="fa-solid fa-right-from-bracket me-2" style="color:#f87171"></i>Log Out</button>
                           </form>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            
            <!-- Dashboard Content -->
            <div class="db-content" id="userAppContent" data-user-ajax-content data-page-title="{{ trim($__env->yieldContent('page-title')) ?: (trim($__env->yieldContent('title')) ?: 'Overview') }}">
                @yield('content')
            </div>
         </div>
      </div>
      @include('partials.user-command-palette')
      @include('partials.viewport-mobile-cookie')
      <!-- ======================== SCRIPTS ======================== -->
      <!-- jQuery -->
      <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
      <!-- Bootstrap 5 -->
      <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
      <!-- AOS -->
      <script src="{{ asset('js/aos.js') }}"></script>
      <!-- Swiper -->
      <script src="{{ asset('js/chart.umd.min.js') }}"></script>
      <!-- CounterUp -->
      <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
      <!-- Main js -->
      <script src="{{ asset('js/main.js?v=6') }}"></script>
      @include('partials.language-translation')
      <!-- PWA Service Worker Registration -->
      <script>
         function closeDashboardSidebar() {
            document.getElementById('dbSidebar')?.classList.remove('mob-open');
            document.body.classList.remove('sidebar-open');
         }

         function toggleDashboardSidebar() {
            if (window.innerWidth < 992) {
               const sidebar = document.getElementById('dbSidebar');
               const isOpen = sidebar?.classList.toggle('mob-open');
               document.body.classList.toggle('sidebar-open', Boolean(isOpen));
               return;
            }
            document.body.classList.toggle('collapsed-sidebar');
         }

         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js?v=10').then(function(registration) {
                  console.log('ServiceWorker registration successful with scope: ', registration.scope);
               }, function(err) {
                  console.log('ServiceWorker registration failed: ', err);
               });
            });
         }
         
         function triggerMobTutorial() {
             if (typeof window.startOnboardingTour === 'function') {
                 window.startOnboardingTour();
             } else {
                 alert('A tutorial is not available for this specific page.');
             }
         }
         
         function toggleNotif(e) {
            e.stopPropagation();
            const dd = document.getElementById('notifDropdown');
            if (dd.classList.contains('open')) {
               dd.classList.remove('open');
            } else {
               // Close profile dropdown if open
               const pd = document.getElementById('profileDropdown');
               if(pd && pd.classList.contains('open')) pd.classList.remove('open');
               
               const ch = document.getElementById('profileChevron');
               if (ch) ch.style.transform = 'rotate(0deg)';
               
               dd.classList.add('open');
               if (dd.getAttribute('data-loaded') !== 'true') {
                  fetchNotifications();
               }
            }
         }

         function fetchNotifications() {
            fetch('/notifications/fetch')
               .then(res => res.json())
               .then(data => {
                  updateNotifUI(data);
                  document.getElementById('notifDropdown').setAttribute('data-loaded', 'true');
               })
               .catch(err => console.error('Error fetching notifications:', err));
         }

         function updateNotifUI(data) {
            const badge = document.getElementById('notifBadge');
            const unreadBadge = document.getElementById('unreadCountBadge');
            const listContainer = document.getElementById('notifListContainer');

            if (data.unreadCount > 0) {
               badge.style.display = 'block';
               unreadBadge.style.display = 'inline-block';
               unreadBadge.textContent = data.unreadCount + ' new';
            } else {
               badge.style.display = 'none';
               unreadBadge.style.display = 'none';
            }

            if (data.notifications.length === 0) {
               listContainer.innerHTML = '<div class="text-center py-4" style="color:var(--tx3);font-size:0.85rem;">No notifications to show.</div>';
               return;
            }

            let html = '';
            data.notifications.forEach(n => {
               const isRead = n.read_at ? 'read' : '';
               const unreadClass = n.read_at ? '' : 'notif-unread';
               const icon = n.data.icon || 'fa-bell';
               const typeClass = n.data.type || 'info'; // Use for colors later if needed
               
               // Formatting date
               const date = new Date(n.created_at).toLocaleString();

               html += `
                  <div class="notif-item ${unreadClass} d-flex gap-3 mb-2" style="position:relative">
                     <div class="notif-ico flex-shrink-0" style="background:rgba(59,130,246,.12);cursor:pointer" onclick="window.location.href='/notifications'"><i class="fa-solid ${icon}" style="color:#60a5fa;font-size:.9rem"></i></div>
                     <div style="flex:1;min-width:0;">
                        <div style="cursor:pointer" onclick="window.location.href='/notifications'">
                           <div style="font-size:.85rem;font-weight:600;margin-bottom:3px;word-wrap:break-word;white-space:normal;">${n.data.title || 'Notification'}</div>
                           <div style="font-size:.78rem;color:var(--tx2);word-wrap:break-word;white-space:normal;">${n.data.message || ''}</div>
                           <div style="font-size:.7rem;color:var(--tx3);margin-top:5px"><i class="fa-regular fa-clock me-1"></i>${date}</div>
                        </div>
                        <div class="d-flex gap-3 mt-2">
                           ${n.read_at ? '' : `<button class="btn btn-sm btn-link text-decoration-none p-0" onclick="markReadDD('${n.id}', event)" style="font-size:.75rem;color:var(--pur)">Mark as read</button>`}
                           <button class="btn btn-sm btn-link text-decoration-none text-danger p-0" onclick="deleteNotificationDD('${n.id}', event)" style="font-size:.75rem;">Delete</button>
                        </div>
                     </div>
                  </div>
               `;
            });

            listContainer.innerHTML = html;
         }

         function markAllNotificationsRead() {
            fetch('/notifications/read-all', {
               method: 'POST',
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Content-Type': 'application/json'
               }
            })
            .then(res => res.json())
            .then(data => {
               if(data.success) {
                  fetchNotifications();
                  if(typeof reloadNotificationsPage === 'function') reloadNotificationsPage();
               }
            });
         }

         function clearAllNotificationsDD() {
            if(confirm('Are you sure you want to clear all notifications?')) {
               fetch('/notifications/clear-all', {
                  method: 'DELETE',
                  headers: {
                     'X-CSRF-TOKEN': '{{ csrf_token() }}',
                     'Content-Type': 'application/json'
                  }
               })
               .then(res => res.json())
               .then(data => {
                  if(data.success) {
                     fetchNotifications();
                     if(typeof reloadNotificationsPage === 'function') reloadNotificationsPage();
                  }
               });
            }
         }

         function markReadDD(id, e) {
            e.stopPropagation();
            fetch('/notifications/' + id + '/read', {
               method: 'POST',
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Content-Type': 'application/json'
               }
            })
            .then(res => res.json())
            .then(data => {
               if(data.success) {
                  fetchNotifications();
               }
            });
         }

         function deleteNotificationDD(id, e) {
            e.stopPropagation();
            fetch('/notifications/' + id, {
               method: 'DELETE',
               headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Content-Type': 'application/json'
               }
            })
            .then(res => res.json())
            .then(data => {
               if(data.success) {
                  fetchNotifications();
               }
            });
         }

         // Fetch initially to set badge
         document.addEventListener('DOMContentLoaded', function() {
            fetchNotifications();
            // Poll every minute
            setInterval(fetchNotifications, 60000);
         });
      </script>
      <!-- Driver.js -->
      <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
      <style>
         #progressModulesLikeHero.progress-hero,
         #feedbackModulesLikeHero.feedback-hero,
         #sec-interview-setup .setup-hero,
         #interview-modules-page .modules-hero,
         #voice-rehearsal-page .sr-page-hero.vr-hero,
         #mission-mode-page .mission-progress-hero.sr-page-hero,
         #learning-games-page .sr-learning-hero,
         #ai-coach-page .sr-page-hero.coach-progress-hero,
         #portfolioReport .sr-page-hero,
         #personal-mastery-page .mastery-hero-card,
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
         :root:not(.lm) #voice-rehearsal-page .sr-page-hero.vr-hero,
         :root:not(.lm) #mission-mode-page .mission-progress-hero.sr-page-hero,
         :root:not(.lm) #learning-games-page .sr-learning-hero,
         :root:not(.lm) #ai-coach-page .sr-page-hero.coach-progress-hero,
         :root:not(.lm) #portfolioReport .sr-page-hero,
         :root:not(.lm) #personal-mastery-page .mastery-hero-card,
         :root:not(.lm) #notifications-page .notif-hero,
         :root:not(.lm) #account-page .sr-page-hero,
         :root:not(.lm) #skill-trees-page .sr-page-hero.skill-tree-hero,
         :root:not(.lm) .sr-page-hero,
         .dm #progressModulesLikeHero.progress-hero,
         .dm #feedbackModulesLikeHero.feedback-hero,
         .dm #sec-interview-setup .setup-hero,
         .dm #interview-modules-page .modules-hero,
         .dm #voice-rehearsal-page .sr-page-hero.vr-hero,
         .dm #mission-mode-page .mission-progress-hero.sr-page-hero,
         .dm #learning-games-page .sr-learning-hero,
         .dm #ai-coach-page .sr-page-hero.coach-progress-hero,
         .dm #portfolioReport .sr-page-hero,
         .dm #personal-mastery-page .mastery-hero-card,
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
         #voice-rehearsal-page .vr-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .vr-hero-icon),
         #mission-mode-page .mission-progress-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .mission-hero-icon),
         #learning-games-page .sr-learning-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .learning-hero-icon),
         #ai-coach-page .coach-progress-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .coach-hero-icon),
         #portfolioReport .sr-page-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .reports-hero-icon),
         #personal-mastery-page .mastery-hero-card :is(.mastery-title, .mastery-subtitle, .mastery-badge),
         #notifications-page .notif-hero :is(.notif-hero-title, .notif-hero-subtitle, .notif-hero-icon),
         #account-page .sr-page-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .account-hero-icon),
         #skill-trees-page .skill-tree-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle, .skill-tree-hero-icon),
         .sr-page-hero :is(.sr-page-hero-title, .sr-page-hero-subtitle) {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
         }

         #feedbackModulesLikeHero .feedback-title {
            color: #fde047 !important;
            -webkit-text-fill-color: #fde047 !important;
         }

         #sec-interview-setup .setup-hero .setup-hero-title {
            color: #fde047 !important;
            -webkit-text-fill-color: #fde047 !important;
            text-transform: uppercase !important;
         }

         #progressModulesLikeHero .progress-hero-subtitle,
         #feedbackModulesLikeHero .feedback-subtitle,
         #sec-interview-setup .setup-hero-subtitle,
         #interview-modules-page .modules-hero-subtitle,
         #voice-rehearsal-page .vr-hero .sr-page-hero-subtitle,
         #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle,
         #learning-games-page .sr-learning-hero .sr-page-hero-subtitle,
         #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle,
         #portfolioReport .sr-page-hero-subtitle,
         #personal-mastery-page .mastery-subtitle,
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
         #voice-rehearsal-page .vr-hero-icon,
         #mission-mode-page .mission-hero-icon,
         #learning-games-page .learning-hero-icon,
         #ai-coach-page .coach-hero-icon,
         #portfolioReport .reports-hero-icon,
         #personal-mastery-page .mastery-badge,
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
         }

         html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
            font-size: 0.49rem !important;
            line-height: 1.32 !important;
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
      @include('partials.onboarding-script')
      <!-- USER_PAGE_SCRIPTS_START -->
      @stack('scripts')
      <!-- USER_PAGE_SCRIPTS_END -->
      @include('layouts.logout-transition')
   </body>
</html>
