<!DOCTYPE html>
<html lang="en" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta name="theme-color" content="#ffffff">
      <title>SpeakReady AI Admin Portal</title>
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
      <link rel="stylesheet" href="{{ asset('css/style.css?v=4') }}" />
      <style>
          .db-nl { text-decoration: none; display: flex; align-items: center; }
          .admin-brand { color: var(--tx) !important; font-weight: 700; }
          .db-nl[aria-expanded="true"] .toggle-icon { transform: rotate(180deg); }
      </style>
      <script>
         if (localStorage.getItem('theme') === 'light') {
             document.documentElement.classList.add('lm');
         }
      </script>
   </head>
   <body>
      <div id="dashboard">
         <!-- Sidebar -->
         <div class="db-sidebar" id="dbSidebar" style="border-right: 2px solid rgba(248,113,113,0.1);">
             <div class="db-logo d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                   <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: transparent; padding: 0;">
                   <span class="admin-brand">SpeakReady AI</span>
                </div>
                <button class="d-lg-none" style="background:none;border:none;color:var(--tx2);font-size:1.5rem;padding:0;line-height:1;" onclick="document.getElementById('dbSidebar').classList.remove('mob-open')">
                   <i class="fa-solid fa-xmark"></i>
                </button>
             </div>
            <div class="db-nav">
               <div class="db-nav-section">Core Modules</div>
               <a href="{{ route('admin.dashboard') }}" class="db-nl {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="fa-solid fa-gauge-high"></i> Admin Dashboard</a>
               <a href="{{ route('admin.users.index') }}" class="db-nl {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="fa-solid fa-users"></i> User Management</a>
               <a href="{{ route('admin.categories') }}" class="db-nl {{ request()->routeIs('admin.categories') ? 'active' : '' }}"><i class="fa-solid fa-list"></i> Categories</a>
               <a href="{{ route('admin.questions') }}" class="db-nl {{ request()->routeIs('admin.questions') ? 'active' : '' }}"><i class="fa-solid fa-circle-question"></i> Question Bank</a>
               <a href="{{ route('admin.modules') }}" class="db-nl {{ request()->routeIs('admin.modules') || request()->routeIs('admin.modules.*') ? 'active' : '' }}"><i class="fa-solid fa-book-open"></i> Learning Content</a>
               <a href="{{ route('admin.game') }}" class="db-nl {{ request()->routeIs('admin.game') || request()->routeIs('admin.game.*') ? 'active' : '' }}"><i class="fa-solid fa-gamepad"></i> Learning Games</a>
               
               <div class="db-nav-section">Monitoring</div>
               <a href="{{ route('admin.sessions.index') }}" class="db-nl {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}"><i class="fa-solid fa-video"></i> Session Monitoring</a>
               <a href="{{ route('admin.feedback.index') }}" class="db-nl {{ request()->routeIs('admin.feedback.*') && !request()->routeIs('admin.feedback.complaints') ? 'active' : '' }}"><i class="fa-solid fa-clipboard-check"></i> Feedback Audit</a>
               <a href="{{ route('admin.feedback.complaints') }}" class="db-nl {{ request()->routeIs('admin.feedback.complaints') ? 'active' : '' }}"><i class="fa-solid fa-clipboard-list"></i> User Complaints</a>
               <a href="{{ route('admin.contacts.index') }}" class="db-nl {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}"><i class="fa-solid fa-envelope"></i> Contact Messages</a>
               
               <div class="db-nav-section">System</div>
               
               <a href="{{ route('admin.ai.providers') }}" class="db-nl {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}"><i class="fa-solid fa-microchip"></i> AI Providers</a>
               
               <a href="{{ route('admin.settings.index') }}" class="db-nl {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"><i class="fa-solid fa-gear"></i> System Settings</a>
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
                  <input type="text" placeholder="Search Admin Portal...">
               </div>
               <div class="ms-auto d-flex align-items-center gap-3 flex-shrink-0">
                  <div class="dropdown">
                      <a href="#" class="boc d-flex align-items-center justify-content-center position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="Live Activity" style="width:38px;height:38px;padding:0;border-radius:12px;text-decoration:none;color:var(--tx);" onclick="resetAdminActivityBadge('desktop')">
                          <i class="fa-regular fa-bell"></i>
                          <span id="admin-activity-badge-desktop" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="display:none; width: 10px; height: 10px;">
                              <span class="visually-hidden">New alerts</span>
                          </span>
                      </a>
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
                                  <i class="fa-solid fa-bullhorn me-2"></i>Broadcast Announcement
                              </a>
                          </div>
                      </div>
                  </div>
                  <button class="boc d-flex align-items-center justify-content-center" id="dbThBtn" style="width:38px;height:38px;padding:0;border-radius:12px" onclick="toggleTheme()">
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
      </script>
      <script>
          let adminLastSeenActivityId = localStorage.getItem('admin_last_seen_activity_id') || 0;

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
              fetchAdminActivities();
              setInterval(fetchAdminActivities, 15000);
          });
      </script>

      @stack('scripts')
      @include('layouts.logout-transition')
   </body>
</html>



