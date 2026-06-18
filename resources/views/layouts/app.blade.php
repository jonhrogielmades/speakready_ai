<!DOCTYPE html>
<html lang="en" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>SpeakReady AI - Automate Everything with AI Agents</title>
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
               <div class="ms-auto d-flex align-items-center gap-3">
                  <button class="boc d-flex align-items-center justify-content-center" id="dbThBtn" style="width:38px;height:38px;padding:0;border-radius:12px" onclick="toggleTheme()">
                  <i class="fa-solid fa-sun" id="dbSunI" style="display:none"></i>
                  <i class="fa-solid fa-moon" id="dbMoonI"></i>
                  </button>
                  <div style="position:relative" id="profileWrap">
                     <div class="db-user-pill" id="userPill" onclick="toggleProfile(event)">
                        <div class="db-avatar" id="userAvatar">{{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U' }}</div>
                        <div class="d-none d-md-block">
                           <div style="font-size:.85rem;font-weight:600;line-height:1.2" id="userName">{{ Auth::user()->name ?? 'User' }}</div>
                           <div style="font-size:.72rem;color:var(--tx3)" id="userPlan">Pro Plan</div>
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
   </body>
</html>
