<!DOCTYPE html>
<html lang="en" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
      <meta name="theme-color" content="#ffffff">
      <title>SpeakReady AI - AI-Based Interview Practice System</title>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">
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
      <link rel="stylesheet" href="{{ asset('css/style.css?v=2') }}" />
      <style>
          .db-nl { text-decoration: none; display: flex; align-items: center; }
      </style>
      <script>
         if (localStorage.getItem('theme') === 'light') {
             document.documentElement.classList.add('lm');
         }
      </script>
      <!-- Driver.js -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
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
               <button class="d-lg-none" style="background:none;border:none;color:var(--tx2);font-size:1.5rem;padding:0;line-height:1;" onclick="document.getElementById('dbSidebar').classList.remove('mob-open')">
                  <i class="fa-solid fa-xmark"></i>
               </button>
            </div>
            <div class="db-nav">
               <div class="db-nav-section">Dashboard</div>
               <a href="{{ route('dashboard') }}" class="db-nl {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fa-solid fa-gauge-high"></i> Overview</a>

               
               <div class="db-nav-section">Interview Practice</div>
               <a href="{{ route('interview.setup') }}" class="db-nl {{ request()->routeIs('interview.setup') ? 'active' : '' }}"><i class="fa-solid fa-microphone-lines"></i> Mock Interview</a>
               
               <div class="db-nav-section">Specialized Training</div>
               <a href="{{ route('user.drills.voice') }}" class="db-nl {{ request()->routeIs('user.drills.voice') ? 'active' : '' }}"><i class="fa-solid fa-ear-listen"></i> Voice Rehearsal</a>
               <a href="{{ route('user.learning') }}" class="db-nl {{ request()->routeIs('user.learning') ? 'active' : '' }}"><i class="fa-solid fa-book-open"></i> Learning Lab</a>
               <a href="{{ route('user.coach') }}" class="db-nl {{ request()->routeIs('user.coach') ? 'active' : '' }}"><i class="fa-solid fa-robot"></i> AI Coach</a>
               
               <div class="db-nav-section">Performance</div>
               <a href="{{ route('user.progress') }}" class="db-nl {{ request()->routeIs('user.progress') ? 'active' : '' }}"><i class="fa-solid fa-chart-line"></i> Progress Tracking</a>
               <a href="{{ route('user.feedback') }}" class="db-nl {{ request()->routeIs('user.feedback') ? 'active' : '' }}"><i class="fa-solid fa-clipboard-check"></i> Feedback Center</a>
               <a href="{{ route('user.reports') }}" class="db-nl {{ request()->routeIs('user.reports') ? 'active' : '' }}"><i class="fa-solid fa-file-invoice"></i> Reports</a>

               <div class="db-nav-section">Community</div>
               <a href="{{ route('user.leaderboard') }}" class="db-nl {{ request()->routeIs('user.leaderboard') ? 'active' : '' }}"><i class="fa-solid fa-trophy"></i> Leaderboard</a>
            </div>
            <div class="db-bottom">
               <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                  @csrf
                  <button type="submit" class="db-nl" style="color:#f87171; width:100%; text-align:left; border:none; background:none;"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
               </form>
            </div>
         </div>
         <!-- Main Content Area -->
         <div class="db-main">
            <!-- Top bar -->
            <div class="db-top">
               <button class="boc me-2 px-2 py-2" style="border-radius:10px;width:38px;height:38px; display:flex; align-items:center; justify-content:center;" onclick="window.innerWidth < 992 ? document.getElementById('dbSidebar').classList.toggle('mob-open') : document.body.classList.toggle('collapsed-sidebar')">
               <i class="fa-solid fa-bars"></i>
               </button>
               <div class="db-top-search">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" placeholder="Search...">
               </div>
               <div class="ms-auto d-flex align-items-center gap-3 flex-shrink-0">
                  <button class="boc d-flex align-items-center justify-content-center" id="dbThBtn" style="width:38px;height:38px;padding:0;border-radius:12px" onclick="toggleTheme()">
                  <i class="fa-solid fa-sun" id="dbSunI" style="display:none"></i>
                  <i class="fa-solid fa-moon" id="dbMoonI"></i>
                  </button>
                  
                  <!-- Notifications -->
                  <div style="position:relative" id="notifWrap">
                     <button class="boc d-flex align-items-center justify-content-center" id="bellBtn" style="width:38px;height:38px;padding:0;border-radius:12px" onclick="toggleNotif(event)">
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
            <div class="db-content">
                @yield('content')
            </div>
         </div>
      </div>
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
      <script src="{{ asset('js/main.js') }}"></script>
      <!-- PWA Service Worker Registration -->
      <script>
         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js').then(function(registration) {
                  console.log('ServiceWorker registration successful with scope: ', registration.scope);
               }, function(err) {
                  console.log('ServiceWorker registration failed: ', err);
               });
            });
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
      @stack('scripts')
      @include('layouts.logout-transition')
   </body>
</html>



