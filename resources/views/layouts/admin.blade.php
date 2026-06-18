<!DOCTYPE html>
<html lang="en" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>SpeakReady AI Admin Portal</title>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
          .admin-brand { color: #f87171 !important; font-weight: 700; }
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
            <div class="db-logo">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: transparent; padding: 0;">
               <span class="admin-brand">SpeakReady AI Admin</span>
            </div>
            <div class="db-nav">
               <div class="db-nav-section">Core Modules</div>
               <a href="{{ route('admin.dashboard') }}" class="db-nl {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="fa-solid fa-gauge-high"></i> Admin Dashboard</a>
               <a href="{{ route('admin.users') }}" class="db-nl {{ request()->routeIs('admin.users') ? 'active' : '' }}"><i class="fa-solid fa-users"></i> User Management</a>
               <a href="{{ route('admin.categories') }}" class="db-nl {{ request()->routeIs('admin.categories') ? 'active' : '' }}"><i class="fa-solid fa-list"></i> Categories</a>
               <a href="{{ route('admin.questions') }}" class="db-nl {{ request()->routeIs('admin.questions') ? 'active' : '' }}"><i class="fa-solid fa-clipboard-question"></i> Question Bank</a>
               <a href="{{ route('admin.modules') }}" class="db-nl {{ request()->routeIs('admin.modules') ? 'active' : '' }}"><i class="fa-solid fa-book-open"></i> Learning Content</a>
               
               <div class="db-nav-section">Monitoring</div>
               <a href="#" class="db-nl"><i class="fa-solid fa-video"></i> Session Monitoring</a>
               <a href="#" class="db-nl"><i class="fa-solid fa-magnifying-glass-chart"></i> Feedback Audit</a>
               
               <div class="db-nav-section">System</div>
               <a href="#" class="db-nl"><i class="fa-solid fa-robot"></i> AI Providers</a>
               <a href="#" class="db-nl"><i class="fa-solid fa-gear"></i> System Settings</a>
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
               <div class="ms-auto d-flex align-items-center gap-3">
                  <span class="db-badge" style="background:rgba(248,113,113,.15);color:#f87171;border:1px solid rgba(248,113,113,.3)"><i class="fa-solid fa-user-shield me-2"></i> Administrator</span>
                  <button class="boc d-flex align-items-center justify-content-center" id="dbThBtn" style="width:38px;height:38px;padding:0;border-radius:12px" onclick="toggleTheme()">
                  <i class="fa-solid fa-sun" id="dbSunI" style="display:none"></i>
                  <i class="fa-solid fa-moon" id="dbMoonI"></i>
                  </button>
                  <div style="position:relative" id="profileWrap">
                     <div class="db-user-pill" id="userPill" onclick="toggleProfile(event)">
                        <div class="db-avatar" id="userAvatar" style="background:#f87171;color:#fff">{{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'A' }}</div>
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
   </body>
</html>
