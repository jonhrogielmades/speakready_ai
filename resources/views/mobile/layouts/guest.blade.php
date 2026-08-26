<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#f7fbff">
      <title>@yield('title', 'SpeakReady AI - Practice Smarter. Interview Better.')</title>
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://accounts.google.com">
      <link rel="dns-prefetch" href="//accounts.google.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <!-- Bootstrap 5.3 -->
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"/>
      <!-- AOS Animate on Scroll -->
      <link href="{{ asset('css/aos.css') }}" rel="stylesheet"/>
      <!-- Swiper CSS -->
      <link href="{{ asset('css/swiper-bundle.min.css') }}" rel="stylesheet"/>
      <!-- all min css -->
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}"/>
      <!-- magnific CSS -->
      <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}"/>
      <!-- Style CSS -->
      <link rel="stylesheet" href="{{ asset('css/mobile/style.css?v=30') }}" />
      <link rel="stylesheet" href="{{ asset('css/mobile/guest.css?v=1') }}" />
   </head>
   <body class="guest-shell guest-mobile-shell @if(!$errors->any()) guest-splash-pending @endif" data-layout-shell="mobile" data-guest-layout="mobile">
      @include('mobile.partials.viewport-mobile-cookie')
      @if(!$errors->any())
      <div id="srLaunchScreen" class="sr-launch-screen" role="status" aria-live="polite" aria-label="Opening SpeakReady AI">
         <div class="sr-launch-content">
            <div class="sr-launch-mark">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI">
            </div>
            <p class="sr-launch-kicker">AI Interview Coach</p>
            <h1 class="sr-launch-title">SpeakReady AI</h1>
            <p class="sr-launch-copy">Practice. Improve. Speak with confidence.</p>
            <div class="sr-launch-progress" aria-hidden="true"><span></span></div>
            <div class="sr-launch-status">Preparing your practice space</div>
         </div>
      </div>
      <script>
         (function () {
            const splash = document.getElementById('srLaunchScreen');
            if (!splash) return;

            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const isMobile = window.matchMedia('(max-width: 820px)').matches;
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const seenKey = 'speakready_guest_splash_seen';
            const shouldShow = (isStandalone || isMobile) && !sessionStorage.getItem(seenKey);

            function clearSplash() {
               document.body.classList.remove('guest-splash-pending');
               splash.classList.add('is-hiding');
               window.setTimeout(function () {
                  splash.remove();
               }, reduceMotion ? 0 : 460);
            }

            if (!shouldShow) {
               clearSplash();
               return;
            }

            sessionStorage.setItem(seenKey, '1');
            const startedAt = window.performance ? performance.now() : Date.now();
            const minimumDuration = reduceMotion ? 240 : 1650;

            function finishWhenReady() {
               const now = window.performance ? performance.now() : Date.now();
               const remaining = Math.max(0, minimumDuration - (now - startedAt));
               window.setTimeout(clearSplash, remaining);
            }

            if (document.readyState === 'loading') {
               document.addEventListener('DOMContentLoaded', finishWhenReady, { once: true });
            } else {
               finishWhenReady();
            }

            window.setTimeout(clearSplash, 3600);
         })();
      </script>
      @endif
<!-- ======================== LANDING PAGE ======================== -->
      <div id="landing">
         <!-- NAVBAR -->
         <nav id="nbar">
            <div class="container">
               <div class="d-flex align-items-center justify-content-between w-100">
                  <a href="#hero" class="guest-brand d-flex align-items-center gap-2 text-truncate" style="font-size:1.2rem;font-weight:700;color:var(--tx); max-width: calc(100vw - 120px);">
                     <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: #ffffff; padding: 0; flex-shrink: 0;">
                     <span class="guest-brand-copy">
                        <span class="guest-brand-name">SpeakReady AI</span>
                        @php
                           $guestHeaderNow = now();
                        @endphp
                        <time class="guest-header-clock" id="guestHeaderClock" datetime="{{ $guestHeaderNow->toIso8601String() }}" aria-label="Current date and time">
                           <span id="guestHeaderDate">{{ $guestHeaderNow->format('D, M j') }}</span>
                           <span class="guest-header-clock-separator" aria-hidden="true">&bull;</span>
                           <span id="guestHeaderTime">{{ $guestHeaderNow->format('g:i A') }}</span>
                        </time>
                     </span>
                  </a>
                  <div class="d-none d-lg-flex align-items-center gap-1 mx-auto">
                     <a href="#hero" class="nav-link">Home</a>
                     <a href="#features" class="nav-link">Features</a>
                     <a href="#how" class="nav-link">How It Works</a>
                     <a href="#benefits" class="nav-link">Interview Categories</a>
                     <a href="#developers" class="nav-link">Developers</a>
                     <a href="#faq" class="nav-link">FAQ</a>
                     <a href="#contact" class="nav-link">Contact Us</a>
                  </div>
                  <div class="d-flex align-items-center gap-2 flex-shrink-0">
                     <button class="boc d-flex align-items-center justify-content-center" id="thbtn" style="width:38px;height:38px;padding:0;border-radius:10px" aria-label="Toggle theme">
                     <i class="fa-solid fa-sun" id="suni" style="display:none"></i>
                     <i class="fa-solid fa-moon" id="mooni"></i>
                     </button>
                     <button class="boc px-3 py-2 d-none d-sm-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('login')">
                     <i class="fa-regular fa-user fa-sm"></i> Login
                     </button>
                     <button class="bgrd btn px-3 py-2 d-none d-sm-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('signup')">
                     Register <i class="fa-solid fa-arrow-right fa-sm"></i>
                     </button>
                     <button class="boc d-lg-none d-flex align-items-center justify-content-center" id="mbtog" style="width:38px;height:38px;padding:0;border-radius:10px" type="button" data-ucp-open aria-label="Open quick navigation" aria-haspopup="dialog" aria-controls="userCommandPalette" aria-expanded="false">
                     <i class="fa-solid fa-bars" aria-hidden="true"></i>
                     </button>
                  </div>
               </div>
            </div>
         </nav>
         @include('mobile.partials.user-command-palette', ['guestQuickNavigation' => true])

         <!-- HERO -->
         <section id="hero">
            <div class="aur aur-a" style="top:-80px;left:-120px"></div>
            <div class="aur aur-b" style="top:180px;right:-180px"></div>
            <div class="aur aur-a" style="bottom:-80px;left:45%;transform:translateX(-50%);opacity:.4"></div>
             <div class="container position-relative" style="z-index:2">
                <div class="text-center mt-3 pt-3 afu" style="animation-delay:.05s">
                    <span class="hbadge">
                 AI-Powered Practice | Real-Time Feedback
                    </span>
                </div>
                @php
                   $previewReadiness = 85;
                   $previewInterviews = 6;
                   $previewClarity = 92;
                   $previewGrammar = 95;
                @endphp
                <div class="row align-items-center justify-content-center mt-4">
                  <div class="col-lg-7 col-md-10 text-center">
                     <h1 class="h1 afu" style="animation-delay:.12s">Practice Smarter.<br><span class="gt">Interview Better.</span></h1>
                     <p class="mx-auto afu" style="max-width:580px;font-size:clamp(.95rem,1.8vw,1.2rem);color:var(--tx2);margin-bottom:36px;animation-delay:.2s">SpeakReady AI offers simulated mock interviews, personalized feedback, and comprehensive coaching to help you land your dream opportunity.</p>
                     <div class="hero-cta-row d-flex align-items-center justify-content-center gap-3 flex-wrap afu" style="animation-delay:.28s">
                        <button class="bgrd btn px-4 py-3 fs-6" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('signup')">Get Started Free</button>
                        <button class="boc btn px-4 py-3 fs-6" id="heroInstallBtn"><i class="fa-solid fa-download me-2" style="color:var(--pur)"></i>Install App</button>
                        <a href="#features" class="boc btn px-4 py-3 fs-6">Learn More</a>
                     </div>
                    </div>
                 </div>

                <div class="hero-tech-card mt-3 mb-3 afu text-center" style="animation-delay:.4s">
                  <p class="hero-tech-title" style="font-size:.71rem;color:var(--hero-tech-color, #000000);text-transform:uppercase;letter-spacing:.12em;margin-bottom:14px">Featured Technologies</p>
                  <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap tech-icons" style="color:var(--hero-tech-color, #000000); font-size:1.5rem;">
                      <a href="https://laravel.com" target="_blank" rel="noopener noreferrer" title="Laravel"><i class="fa-brands fa-laravel"></i></a>
                      <a href="https://php.net" target="_blank" rel="noopener noreferrer" title="PHP"><i class="fa-brands fa-php"></i></a>
                      <a href="https://www.mysql.com/" target="_blank" rel="noopener noreferrer" title="MySQL"><i class="fa-solid fa-database"></i></a>
                      @php
                          $title = 'OpenAI';
                          $link = 'https://openai.com';
                      @endphp
                      <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" title="{{ $title }}">
                          <i class="fa-solid fa-robot"></i>
                      </a>
                      <a href="https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API" target="_blank" rel="noopener noreferrer" title="Web Speech API"><i class="fa-solid fa-microphone"></i></a>
                   </div>
                </div>

                <div id="demo-preview" class="landing-section-heading mobile-demo-preview-heading text-center mt-4 mb-3 afu" style="animation-delay:.48s">
                  <span class="slbl">Demo Preview</span>
                  <h2 class="stitle">Inside <span class="gt">SpeakReady AI</span></h2>
                </div>

                <div class="row justify-content-center mt-3 mb-3">
                  <div class="col-lg-12 adi">
                     <div class="ui-showcase">
                        <div class="ui-device ui-device-mobile ui-device-mobile-image" aria-label="Mobile UI preview">
                           <div class="ui-device-bar">
                              <span class="ui-device-dot" style="background:#ff5f57"></span>
                              <span class="ui-device-dot" style="background:#ffbd2e"></span>
                              <span class="ui-device-dot" style="background:#28c840"></span>
                              <span class="ui-device-title">SpeakReady AI Mobile Dashboard</span>
                           </div>
                           <div class="swiper mobilePreviewSwiper mobile-preview-image-swiper">
                              @php
                                 $mobilePreviewSlides = [
                                    [
                                       'image' => 'img/mobile-preview-home-shell.png',
                                       'alt' => 'SpeakReady AI mobile home preview',
                                       'kicker' => 'Dashboard Overview',
                                       'title' => 'See your readiness at a glance.',
                                       'text' => 'Track your interview progress, practice streak, rating, and next goal from one clean mobile dashboard.',
                                       'points' => [
                                          'Check your overall readiness score',
                                          'View practice sessions and ratings',
                                          'Follow your next improvement goal',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-progress-shell.png',
                                       'alt' => 'SpeakReady AI mobile progress preview',
                                       'kicker' => 'Progress Tracking',
                                       'title' => 'Know what to improve next.',
                                       'text' => 'Review your streak, exported reports, AI insights, and a simple practice plan made for your interview growth.',
                                       'points' => [
                                          'Monitor streaks and total practice days',
                                          'Export progress as PDF or Excel',
                                          'Follow a personalized practice plan',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-setup-shell.png',
                                       'alt' => 'SpeakReady AI mobile interview setup preview',
                                       'kicker' => 'Interview Setup',
                                       'title' => 'Configure a focused mock interview.',
                                       'text' => 'Set your practice scenario, target position, and interview details before starting a tailored session.',
                                       'points' => [
                                          'Choose the interview scenario',
                                          'Add your target position',
                                          'Review each setup step clearly',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-feedback-center-shell.png',
                                       'alt' => 'SpeakReady AI mobile feedback center preview',
                                       'kicker' => 'Feedback Center',
                                       'title' => 'Review coaching feedback after practice.',
                                       'text' => 'Browse feedback summaries, priority recommendations, answer coaching, and history from the mobile shell.',
                                       'points' => [
                                          'Read AI feedback summaries',
                                          'See recommended next practice',
                                          'Review answer-by-answer coaching',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-modules-shell.png',
                                       'alt' => 'SpeakReady AI mobile interview modules preview',
                                       'kicker' => 'Interview Modules',
                                       'title' => 'Explore guided preparation modules.',
                                       'text' => 'Open learning paths and recommended lessons that keep interview preparation organized by topic.',
                                       'points' => [
                                          'Filter module topics',
                                          'Follow recommended lessons',
                                          'Track learning path progress',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-voice-rehearsal-shell.png',
                                       'alt' => 'SpeakReady AI mobile voice rehearsal preview',
                                       'kicker' => 'Voice Rehearsal',
                                       'title' => 'Practice answers out loud.',
                                       'text' => 'Record responses, check pacing, and review speaking metrics from the mobile interview practice screen.',
                                       'points' => [
                                          'Record spoken interview answers',
                                          'Switch prompts and confidence level',
                                          'Track duration, WPM, stability, and fillers',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-mission-mode-shell.png',
                                       'alt' => 'SpeakReady AI mobile mission mode preview',
                                       'kicker' => 'Mission Mode',
                                       'title' => 'Generate real-life practice tasks.',
                                       'text' => 'Turn target situations into mission tasks, then score written or spoken answers against the prompt.',
                                       'points' => [
                                          'Generate personalized mission tasks',
                                          'Practice with text or voice',
                                          'Score mission-specific answers',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-challenges-shell.png',
                                       'alt' => 'SpeakReady AI mobile interview challenges preview',
                                       'kicker' => 'Interview Challenges',
                                       'title' => 'Build skill through challenge journeys.',
                                       'text' => 'Complete gamified interview challenges with goals, question sets, skill rewards, and progress stats.',
                                       'points' => [
                                          'View level, XP, energy, and accuracy',
                                          'Follow challenge goals',
                                          'Complete success checklist items',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-readiness-coach-shell.png',
                                       'alt' => 'SpeakReady AI mobile readiness coach preview',
                                       'kicker' => 'Readiness Coach',
                                       'title' => 'Ask for focused interview help.',
                                       'text' => 'Use the coach chat for interview, resume, certificate, and practice guidance while keeping claims truthful.',
                                       'points' => [
                                          'Chat with the readiness coach',
                                          'Attach context when needed',
                                          'Send focused preparation questions',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-reports-shell.png',
                                       'alt' => 'SpeakReady AI mobile interview reports preview',
                                       'kicker' => 'Interview Reports',
                                       'title' => 'Review and export interview reports.',
                                       'text' => 'See report availability, start a scored interview, and access export actions from the mobile report screen.',
                                       'points' => [
                                          'Start a scored interview',
                                          'Review generated report status',
                                          'Export reports as PDF or Excel',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-personal-mastery-shell.png',
                                       'alt' => 'SpeakReady AI mobile personal mastery preview',
                                       'kicker' => 'Personal Mastery',
                                       'title' => 'Track private growth over time.',
                                       'text' => 'Follow personal bests, baseline growth, practice streaks, and recommended drills from one progress hub.',
                                       'points' => [
                                          'Compare baseline and latest score',
                                          'Start a scored mock interview',
                                          'Drill recommended weak areas',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-notifications-shell.png',
                                       'alt' => 'SpeakReady AI mobile notifications preview',
                                       'kicker' => 'Notifications',
                                       'title' => 'Stay current on activity and alerts.',
                                       'text' => 'View notification states and recent account activity in a mobile-friendly timeline.',
                                       'points' => [
                                          'Check progress alerts',
                                          'Review activity history',
                                          'Scan recent login events',
                                       ],
                                    ],
                                    [
                                       'image' => 'img/mobile-preview-account-shell.png',
                                       'alt' => 'SpeakReady AI mobile account management preview',
                                       'kicker' => 'Account Management',
                                       'title' => 'Manage profile and security settings.',
                                       'text' => 'Update profile details, target role, profile photo, and password fields from the mobile account screen.',
                                       'points' => [
                                          'Update account details',
                                          'Upload a profile picture',
                                          'Manage password settings',
                                       ],
                                    ],
                                 ];
                              @endphp
                              <div class="swiper-wrapper">
                                 @foreach($mobilePreviewSlides as $slide)
                                    @php
                                       $shouldPreloadPreviewImage = $loop->first || $loop->iteration === 2 || $loop->last;
                                    @endphp
                                    <div class="swiper-slide mobile-preview-image-slide">
                                       <img class="mobile-preview-shell-img"
                                            src="{{ $shouldPreloadPreviewImage ? asset($slide['image']) : 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' }}"
                                            @unless($shouldPreloadPreviewImage) data-src="{{ asset($slide['image']) }}" @endunless
                                            data-preview-index="{{ $loop->index }}"
                                            alt="{{ $slide['alt'] }}"
                                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                            decoding="async">
                                       <div class="mobile-preview-copy">
                                          <div class="mobile-preview-copy-kicker"><span>{{ $loop->iteration }}</span> {{ $slide['kicker'] }}</div>
                                          <h3 class="mobile-preview-copy-title">{{ $slide['title'] }}</h3>
                                          <p class="mobile-preview-copy-text">{{ $slide['text'] }}</p>
                                          <ul class="mobile-preview-copy-list">
                                             @foreach($slide['points'] as $point)
                                                <li><i class="fa-solid fa-check"></i>{{ $point }}</li>
                                             @endforeach
                                          </ul>
                                       </div>
                                    </div>
                                 @endforeach
                              </div>
                              <div class="swiper-pagination mobile-preview-pagination"></div>
                              <button type="button" class="mobile-preview-autoplay-toggle" aria-label="Pause demo preview">
                                 <i class="fa-solid fa-pause" aria-hidden="true"></i>
                              </button>
                              <button type="button" class="swiper-button-next mobile-preview-next" aria-label="Next demo preview"></button>
                              <button type="button" class="swiper-button-prev mobile-preview-prev" aria-label="Previous demo preview"></button>
                           </div>
                            <div class="ui-dashboard ui-dashboard-preview ui-mobile-wire d-none" aria-hidden="true">
                               <div class="ui-mobile-wire-topbar">
                                  <div class="ui-mobile-wire-brand">
                                     <span class="ui-mobile-wire-logo" aria-hidden="true"></span>
                                     <span>SpeakReady AI</span>
                                  </div>
                                  <div class="ui-mobile-wire-actions" aria-hidden="true">
                                     <span class="ui-mobile-wire-action"><i class="fa-solid fa-play"></i></span>
                                     <span class="ui-mobile-wire-action"><i class="fa-solid fa-expand"></i></span>
                                     <span class="ui-mobile-wire-action"><i class="fa-solid fa-gear"></i></span>
                                     <span class="ui-mobile-wire-action"><i class="fa-regular fa-bell"></i></span>
                                     <span class="ui-mobile-wire-avatar">U</span>
                                  </div>
                               </div>

                               <div class="ui-mobile-wire-body">
                                  <section class="ui-mobile-wire-hero">
                                     <h3 class="ui-mobile-wire-hero-title">Practice Smarter.<span>Interview Better.</span></h3>
                                     <ul class="ui-mobile-wire-keywords" aria-hidden="true">
                                        <li><span class="ui-mobile-wire-line" style="width:36px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:34px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:42px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:28px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:38px"></span></li>
                                        <li><span class="ui-mobile-wire-line" style="width:44px"></span></li>
                                     </ul>
                                     <div class="ui-mobile-wire-chips" aria-hidden="true">
                                        <span class="ui-mobile-wire-chip"><span class="ui-mobile-wire-dot"></span>Job</span>
                                        <span class="ui-mobile-wire-chip"><span class="ui-mobile-wire-dot"></span>OJT</span>
                                        <span class="ui-mobile-wire-chip"><span class="ui-mobile-wire-dot"></span>Scholarship</span>
                                     </div>
                                     <div class="ui-mobile-wire-speech" aria-hidden="true">
                                        <span class="ui-mobile-wire-line" style="width:44px"></span>
                                        <span class="ui-mobile-wire-line" style="width:58px"></span>
                                        <span class="ui-mobile-wire-line" style="width:50px"></span>
                                     </div>
                                     <img class="ui-mobile-wire-robot" src="{{ asset('img/dashboard-welcome-robot-transparent.png') }}" alt="" aria-hidden="true">
                                  </section>

                                  <div class="ui-mobile-wire-progress" aria-hidden="true"><span></span></div>

                                  <div class="ui-mobile-wire-summary">
                                     <section class="ui-mobile-wire-card ui-mobile-wire-score">
                                        <div class="ui-mobile-wire-card-title"><i class="fa-solid fa-arrow-trend-up"></i> Building Momentum</div>
                                        <div class="ui-mobile-wire-ring">
                                           <div>
                                              <strong>0%</strong>
                                              <span>Overall Readiness</span>
                                           </div>
                                        </div>
                                        <div class="ui-mobile-wire-mini-list">
                                           <div class="ui-mobile-wire-mini">
                                              <div>
                                                 <span>Average Rating</span>
                                                 <strong>0/5</strong>
                                              </div>
                                              <i class="fa-regular fa-star"></i>
                                           </div>
                                           <div class="ui-mobile-wire-mini">
                                              <div>
                                                 <span>Next Goal</span>
                                                 <strong>50%</strong>
                                              </div>
                                              <i class="fa-solid fa-bullseye"></i>
                                           </div>
                                        </div>
                                     </section>

                                     <section class="ui-mobile-wire-card ui-mobile-wire-stat">
                                        <div class="ui-mobile-wire-stat-head">
                                           <span class="ui-mobile-wire-icon"><i class="fa-solid fa-microphone"></i></span>
                                           <span class="ui-mobile-wire-pill">Practice</span>
                                        </div>
                                        <div class="ui-mobile-wire-stat-mark"><i class="fa-solid fa-arrow-trend-up"></i></div>
                                        <div>
                                           <div class="ui-mobile-wire-stat-value">0</div>
                                           <div class="ui-mobile-wire-stat-label">Completed sessions</div>
                                        </div>
                                        <div class="ui-mobile-wire-underbar"></div>
                                     </section>

                                     <section class="ui-mobile-wire-card ui-mobile-wire-stat">
                                        <div class="ui-mobile-wire-stat-head">
                                           <span class="ui-mobile-wire-icon"><i class="fa-regular fa-star"></i></span>
                                           <span class="ui-mobile-wire-pill">Quality</span>
                                        </div>
                                        <div class="ui-mobile-wire-stat-mark">0</div>
                                        <div>
                                           <div class="ui-mobile-wire-stat-value">0/5</div>
                                           <div class="ui-mobile-wire-stat-label">Average rating</div>
                                        </div>
                                        <div class="ui-mobile-wire-underbar"></div>
                                     </section>

                                     <section class="ui-mobile-wire-card ui-mobile-wire-stat">
                                        <div class="ui-mobile-wire-stat-head">
                                           <span class="ui-mobile-wire-icon"><i class="fa-solid fa-bolt"></i></span>
                                           <span class="ui-mobile-wire-pill">Growth</span>
                                        </div>
                                        <div class="ui-mobile-wire-stat-mark">Lv. 1</div>
                                        <div>
                                           <div class="ui-mobile-wire-stat-value">0</div>
                                           <div class="ui-mobile-wire-stat-label">Experience points</div>
                                        </div>
                                        <div class="ui-mobile-wire-underbar"></div>
                                     </section>

                                     <section class="ui-mobile-wire-card ui-mobile-wire-stat">
                                        <div class="ui-mobile-wire-stat-head">
                                           <span class="ui-mobile-wire-icon"><i class="fa-solid fa-fire"></i></span>
                                           <span class="ui-mobile-wire-pill">Streak</span>
                                        </div>
                                        <div class="ui-mobile-wire-stat-mark"><i class="fa-regular fa-calendar-days"></i></div>
                                        <div>
                                           <div class="ui-mobile-wire-stat-value">0</div>
                                           <div class="ui-mobile-wire-stat-label">Active practice days</div>
                                        </div>
                                        <div class="ui-mobile-wire-underbar"></div>
                                     </section>
                                  </div>

                                  <section class="ui-mobile-wire-trend">
                                     <div class="ui-mobile-wire-trend-head">
                                        <span class="ui-mobile-wire-icon"><i class="fa-solid fa-chart-line"></i></span>
                                        <h3 class="ui-mobile-wire-trend-title">Readiness Trend</h3>
                                     </div>
                                     <p class="ui-mobile-wire-trend-copy">Recent completed Philippine interview sessions, scored from 0 to 100.</p>
                                     <div class="ui-mobile-wire-trend-actions">
                                        <span class="ui-mobile-wire-button">View Details <i class="fa-solid fa-chevron-right"></i></span>
                                        <span class="ui-mobile-wire-button">Recent 5 Sessions</span>
                                     </div>
                                     <div class="ui-mobile-wire-trend-metrics">
                                        <div class="ui-mobile-wire-metric">
                                           <i class="fa-solid fa-award"></i>
                                           <div>Average Score<strong>0/100</strong></div>
                                        </div>
                                        <div class="ui-mobile-wire-metric">
                                           <i class="fa-solid fa-arrow-up"></i>
                                           <div>Improvement<strong>+0%</strong></div>
                                        </div>
                                     </div>
                                     <div class="ui-mobile-wire-chart" aria-hidden="true"></div>
                                  </section>
                               </div>

                               <div class="ui-mobile-wire-nav" aria-hidden="true">
                                  <span class="ui-mobile-wire-nav-item active"><i class="fa-solid fa-house"></i>Home</span>
                                  <span class="ui-mobile-wire-nav-item"><i class="fa-solid fa-chart-line"></i>Progress</span>
                                  <span class="ui-mobile-wire-nav-item"><i class="fa-solid fa-microphone"></i>Interview</span>
                                  <span class="ui-mobile-wire-nav-item"><i class="fa-solid fa-clipboard-check"></i>Feedback</span>
                                  <span class="ui-mobile-wire-nav-item"><i class="fa-solid fa-table-cells-large"></i>More</span>
                               </div>
                            </div>
                         </div>

                        <div class="ui-device ui-device-desktop ui-desktop-shell" aria-label="Desktop UI preview">
                           <div class="ui-device-bar">
                              <span class="ui-device-dot" style="background:#ff5f57"></span>
                              <span class="ui-device-dot" style="background:#ffbd2e"></span>
                              <span class="ui-device-dot" style="background:#28c840"></span>
                              <span class="ui-device-title">SpeakReady AI Desktop Dashboard</span>
                           </div>
                           <div class="ui-dashboard ui-desktop-wire" aria-label="SpeakReady AI desktop dashboard wireframe preview">
                              <aside class="ui-desktop-wire-sidebar" aria-hidden="true">
                                 <div class="ui-desktop-wire-brand">
                                    <span class="ui-desktop-wire-mark"><i class="fa-solid fa-microphone-lines"></i></span>
                                    <div>
                                       <strong>SpeakReady AI</strong>
                                       <span>Interview Hub</span>
                                    </div>
                                 </div>

                                 <nav class="ui-desktop-wire-nav">
                                    <span class="ui-desktop-wire-nav-section">Dashboard</span>
                                    <span class="ui-desktop-wire-nav-item active"><i class="fa-solid fa-gauge-high"></i>Overview</span>
                                    <span class="ui-desktop-wire-nav-section">Interview Practice</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-microphone-lines"></i>Mock Interview</span>
                                    <span class="ui-desktop-wire-nav-section">Specialized Training</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-book-open-reader"></i>Modules</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-ear-listen"></i>Voice Rehearsal</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-route"></i>Missions</span>
                                    <span class="ui-desktop-wire-nav-section">Performance</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-chart-line"></i>Progress</span>
                                    <span class="ui-desktop-wire-nav-item"><i class="fa-solid fa-clipboard-check"></i>Feedback</span>
                                 </nav>

                                 <div class="ui-desktop-wire-sidebar-card">
                                    <span class="ui-desktop-wire-avatar"><i class="fa-regular fa-user"></i></span>
                                    <span class="ui-desktop-wire-line" style="width:72%"></span>
                                    <span class="ui-desktop-wire-line" style="width:92%"></span>
                                    <span class="ui-desktop-wire-line" style="width:56%"></span>
                                 </div>
                              </aside>

                              <main class="ui-desktop-wire-main">
                                 <div class="ui-desktop-wire-top" aria-hidden="true">
                                    <span class="ui-desktop-wire-tool"><i class="fa-solid fa-bars"></i></span>
                                    <div class="ui-desktop-wire-tools" aria-hidden="true">
                                       <span class="ui-desktop-wire-tool"><i class="fa-solid fa-expand"></i></span>
                                       <span class="ui-desktop-wire-tool"><i class="fa-solid fa-circle-play"></i></span>
                                       <span class="ui-desktop-wire-tool"><i class="fa-solid fa-sun"></i></span>
                                       <span class="ui-desktop-wire-tool"><i class="fa-regular fa-bell"></i></span>
                                       <span class="ui-desktop-wire-user-pill">
                                          <span class="ui-desktop-wire-avatar">U</span>
                                          <span>User</span>
                                          <i class="fa-solid fa-chevron-down fa-xs"></i>
                                       </span>
                                    </div>
                                 </div>

                                 <div class="ui-desktop-wire-dashboard-preview">
                                    <section class="ui-desktop-wire-summary-grid">
                                       <div class="ui-desktop-wire-welcome-stack">
                                          <section class="ui-desktop-wire-panel ui-desktop-wire-welcome">
                                             <div class="ui-desktop-wire-welcome-copy">
                                                <h3 class="ui-desktop-wire-welcome-title">Practice Smarter.<span>Interview Better.</span></h3>
                                                <ul class="ui-desktop-wire-bullets" aria-hidden="true">
                                                   <li><span class="ui-desktop-wire-line" style="width:92px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:84px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:104px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:74px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:88px"></span></li>
                                                   <li><span class="ui-desktop-wire-line" style="width:108px"></span></li>
                                                </ul>
                                                <div class="ui-desktop-wire-chips">
                                                   <span class="ui-desktop-wire-chip">Job</span>
                                                   <span class="ui-desktop-wire-chip">BPO</span>
                                                   <span class="ui-desktop-wire-chip">IT</span>
                                                   <span class="ui-desktop-wire-chip">Scholarship</span>
                                                   <span class="ui-desktop-wire-chip">Admission</span>
                                                </div>
                                             </div>
                                             <div class="ui-desktop-wire-welcome-visual" aria-hidden="true">
                                                <div class="ui-desktop-wire-speech">
                                                   <span class="ui-desktop-wire-line" style="width:62%"></span>
                                                   <span class="ui-desktop-wire-line" style="width:86%"></span>
                                                   <span class="ui-desktop-wire-line" style="width:72%"></span>
                                                </div>
                                                <img class="ui-desktop-wire-robot" src="{{ asset('img/dashboard-welcome-robot-transparent.png') }}" alt="">
                                             </div>
                                          </section>

                                          <div class="ui-desktop-wire-stats">
                                             <section class="ui-desktop-wire-panel ui-desktop-wire-stat">
                                                <div class="ui-desktop-wire-stat-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-microphone"></i></span>
                                                   <span class="ui-desktop-wire-pill">Practice</span>
                                                </div>
                                                <div class="ui-desktop-wire-stat-mark"><i class="fa-solid fa-arrow-trend-up"></i></div>
                                                <div>
                                                   <div class="ui-desktop-wire-stat-value">{{ $previewInterviews }}</div>
                                                   <div class="ui-desktop-wire-stat-label">Completed sessions</div>
                                                </div>
                                                <div class="ui-desktop-wire-underbar"></div>
                                             </section>

                                             <section class="ui-desktop-wire-panel ui-desktop-wire-stat">
                                                <div class="ui-desktop-wire-stat-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-regular fa-star"></i></span>
                                                   <span class="ui-desktop-wire-pill">Quality</span>
                                                </div>
                                                <div class="ui-desktop-wire-stat-mark"><i class="fa-solid fa-award"></i></div>
                                                <div>
                                                   <div class="ui-desktop-wire-stat-value">4<span style="font-size:.72rem">/5</span></div>
                                                   <div class="ui-desktop-wire-stat-label">Average rating</div>
                                                </div>
                                                <div class="ui-desktop-wire-underbar"></div>
                                             </section>

                                             <section class="ui-desktop-wire-panel ui-desktop-wire-stat">
                                                <div class="ui-desktop-wire-stat-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-bolt"></i></span>
                                                   <span class="ui-desktop-wire-pill">Growth</span>
                                                </div>
                                                <div class="ui-desktop-wire-stat-mark">Lv. 1</div>
                                                <div>
                                                   <div class="ui-desktop-wire-stat-value">320</div>
                                                   <div class="ui-desktop-wire-stat-label">Experience points</div>
                                                </div>
                                                <div class="ui-desktop-wire-underbar"></div>
                                             </section>

                                             <section class="ui-desktop-wire-panel ui-desktop-wire-stat">
                                                <div class="ui-desktop-wire-stat-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-fire"></i></span>
                                                   <span class="ui-desktop-wire-pill">Streak</span>
                                                </div>
                                                <div class="ui-desktop-wire-stat-mark"><i class="fa-regular fa-calendar-days"></i></div>
                                                <div>
                                                   <div class="ui-desktop-wire-stat-value">5</div>
                                                   <div class="ui-desktop-wire-stat-label">Active practice days</div>
                                                </div>
                                                <div class="ui-desktop-wire-underbar"></div>
                                             </section>
                                          </div>
                                       </div>

                                       <section class="ui-desktop-wire-panel ui-desktop-wire-score-panel">
                                          <div class="ui-desktop-wire-score-top">
                                             <span class="ui-desktop-wire-status"><i class="fa-solid fa-circle-check"></i> Interview Ready</span>
                                             <span class="ui-desktop-wire-pill"><i class="fa-solid fa-location-dot"></i> PH Focus</span>
                                          </div>
                                          <div class="ui-desktop-wire-score-layout">
                                             <div class="ui-desktop-wire-ring">
                                                <div>
                                                   <strong>{{ $previewReadiness }}%</strong>
                                                   <span>Overall Readiness</span>
                                                </div>
                                             </div>
                                             <div class="ui-desktop-wire-score-meta">
                                                <div class="ui-desktop-wire-goal"><span>Average Rating</span><strong>4/5</strong></div>
                                                <div class="ui-desktop-wire-goal"><span>Next Goal</span><strong>90%</strong></div>
                                             </div>
                                          </div>
                                          <div class="ui-desktop-wire-note"><i class="fa-regular fa-star"></i> Keep practicing. You're on your way!</div>
                                       </section>
                                    </section>

                                    <section class="ui-desktop-wire-dashboard-shell">
                                       <div class="ui-desktop-wire-main-stack">
                                          <section class="ui-desktop-wire-panel ui-desktop-wire-chart-panel">
                                             <div class="ui-desktop-wire-card-head">
                                                <div>
                                                   <div class="ui-desktop-wire-card-head">
                                                      <span class="ui-desktop-wire-icon"><i class="fa-solid fa-chart-line"></i></span>
                                                      <h3 class="ui-desktop-wire-card-title">Readiness Trend</h3>
                                                   </div>
                                                   <p class="ui-desktop-wire-card-subtitle">Recent completed Philippine interview sessions, scored from 0 to 100.</p>
                                                </div>
                                                <span class="ui-desktop-wire-pill">Recent 10 Sessions</span>
                                             </div>
                                             <div class="ui-desktop-wire-goals" style="grid-template-columns:repeat(2,minmax(0,1fr));margin-top:12px;">
                                                <div class="ui-desktop-wire-goal"><span>Average Score</span><strong>{{ $previewReadiness }}/100</strong></div>
                                                <div class="ui-desktop-wire-goal"><span>Improvement</span><strong>+18%</strong></div>
                                             </div>
                                             <div class="ui-desktop-wire-chart" aria-hidden="true">
                                                <span style="height:44%"></span>
                                                <span style="height:52%"></span>
                                                <span style="height:58%"></span>
                                                <span style="height:68%"></span>
                                                <span style="height:72%"></span>
                                                <span style="height:84%"></span>
                                                <span style="height:92%"></span>
                                             </div>
                                          </section>

                                          <section class="ui-desktop-wire-panel ui-desktop-wire-plan">
                                             <div class="ui-desktop-wire-plan-head">
                                                <span class="ui-desktop-wire-icon"><i class="fa-solid fa-calendar-check"></i></span>
                                                <div>
                                                   <h3 class="ui-desktop-wire-card-title">Personalized Practice Plan</h3>
                                                   <p class="ui-desktop-wire-card-subtitle">A plan built from latest scores, voice work, and learning progress.</p>
                                                </div>
                                             </div>
                                             <div class="ui-desktop-wire-plan-list">
                                                <div class="ui-desktop-wire-plan-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-microphone"></i></span>
                                                   <div><strong>Day 1</strong><span>Practice STAR answer structure</span></div>
                                                   <span class="ui-desktop-wire-pill">12 min</span>
                                                </div>
                                                <div class="ui-desktop-wire-plan-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-ear-listen"></i></span>
                                                   <div><strong>Day 2</strong><span>Voice rehearsal and pacing</span></div>
                                                   <span class="ui-desktop-wire-pill">8 min</span>
                                                </div>
                                             </div>
                                          </section>

                                          <div class="ui-desktop-wire-two-col">
                                             <section class="ui-desktop-wire-panel ui-desktop-wire-polished">
                                                <div class="ui-desktop-wire-polished-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-layer-group"></i></span>
                                                   <div>
                                                      <h3 class="ui-desktop-wire-card-title">Category Performance</h3>
                                                      <p class="ui-desktop-wire-card-subtitle">Where interview scores are strongest.</p>
                                                   </div>
                                                </div>
                                                <div class="ui-desktop-wire-progress-list">
                                                   <div class="ui-desktop-wire-progress-row"><span>Job Interview</span><strong>88%</strong><div class="ui-desktop-wire-progress-track"><span style="width:88%"></span></div></div>
                                                   <div class="ui-desktop-wire-progress-row"><span>BPO Interview</span><strong>82%</strong><div class="ui-desktop-wire-progress-track"><span style="width:82%"></span></div></div>
                                                </div>
                                             </section>

                                             <section class="ui-desktop-wire-panel ui-desktop-wire-polished">
                                                <div class="ui-desktop-wire-polished-head">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-book-open-reader"></i></span>
                                                   <div>
                                                      <h3 class="ui-desktop-wire-card-title">Learning Progress</h3>
                                                      <p class="ui-desktop-wire-card-subtitle">Latest modules in progress.</p>
                                                   </div>
                                                </div>
                                                <div class="ui-desktop-wire-progress-list">
                                                   <div class="ui-desktop-wire-progress-row"><span>Interview Basics</span><strong>72%</strong><div class="ui-desktop-wire-progress-track"><span style="width:72%"></span></div></div>
                                                   <div class="ui-desktop-wire-progress-row"><span>Professional Tone</span><strong>64%</strong><div class="ui-desktop-wire-progress-track"><span style="width:64%"></span></div></div>
                                                </div>
                                             </section>
                                          </div>
                                       </div>

                                       <aside class="ui-desktop-wire-side-stack">
                                          <section class="ui-desktop-wire-panel ui-desktop-wire-feedback">
                                             <div class="ui-desktop-wire-card-head">
                                                <h3 class="ui-desktop-wire-card-title">AI Feedback Summary</h3>
                                                <span class="ui-desktop-wire-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                                             </div>
                                             <div class="ui-desktop-wire-bubble ai"><strong>Top Strengths</strong><br>Clear examples, good structure, and calm delivery.</div>
                                             <div class="ui-desktop-wire-bubble"><strong>Improve Next</strong><br>Add measurable results and tighter closing lines.</div>
                                          </section>

                                          <section class="ui-desktop-wire-panel ui-desktop-wire-recommendations">
                                             <div class="ui-desktop-wire-card-head">
                                                <h3 class="ui-desktop-wire-card-title">AI Recommendations</h3>
                                                <span class="ui-desktop-wire-pill">Personalized</span>
                                             </div>
                                             <div class="ui-desktop-wire-rec-list" style="margin-top:12px;">
                                                <div class="ui-desktop-wire-rec-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-lightbulb"></i></span>
                                                   <div><strong>Practice evidence mapping</strong><span>Use job details in answers.</span></div>
                                                   <i class="fa-solid fa-chevron-right"></i>
                                                </div>
                                                <div class="ui-desktop-wire-rec-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-robot"></i></span>
                                                   <div><strong>Ask Readiness Coach</strong><span>Refine one weak answer.</span></div>
                                                   <i class="fa-solid fa-chevron-right"></i>
                                                </div>
                                             </div>
                                          </section>

                                          <section class="ui-desktop-wire-panel ui-desktop-wire-table-panel">
                                             <div class="ui-desktop-wire-card-head">
                                                <h3 class="ui-desktop-wire-card-title">Recent Sessions</h3>
                                                <span class="ui-desktop-wire-pill">View Reports</span>
                                             </div>
                                             <div class="ui-desktop-wire-table-list" style="margin-top:12px;">
                                                <div class="ui-desktop-wire-table-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-briefcase"></i></span>
                                                   <div><strong>Job Interview</strong><span>Behavioral practice</span></div>
                                                   <span class="ui-desktop-wire-score">92</span>
                                                </div>
                                                <div class="ui-desktop-wire-table-row">
                                                   <span class="ui-desktop-wire-icon"><i class="fa-solid fa-headset"></i></span>
                                                   <div><strong>BPO Interview</strong><span>Customer scenario</span></div>
                                                   <span class="ui-desktop-wire-score">84</span>
                                                </div>
                                             </div>
                                          </section>
                                       </aside>
                                    </section>
                                 </div>
                              </main>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- ABOUT THE SYSTEM & SYSTEM STATS -->
         <section id="about" class="sp position-relative" style="background:var(--bg2)">
            <div class="container position-relative" style="z-index:1">
               <div class="landing-section-heading mb-5 rv">
                  <span class="slbl">About the System</span>
                  <h2 class="stitle">Empowering you to <span class="gt">shine in interviews</span></h2>
               </div>
               <div class="row align-items-center g-5">
                  <div class="col-lg-6 rv">
                     <div class="about-system-panel">
                        <p class="about-system-copy" style="font-size:1.05rem;color:var(--tx2);margin-bottom:20px;">SpeakReady AI is an advanced, intelligent platform designed to help you prepare for Philippine interview scenarios, including job, BPO, IT, fresh graduate, scholarship, and college admission interviews. It provides immediate, evidence-linked feedback on answer quality and optional, non-scoring delivery coaching to reduce interview anxiety and make practice more focused.</p>

                        <h4 class="fs-5 mb-3 mt-4">Target Users</h4>
                        <div class="target-users-grid d-flex flex-wrap gap-2 mb-4">
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-user-graduate me-2"></i>Students</span>
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-graduation-cap me-2"></i>Fresh Graduates</span>
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-briefcase me-2"></i>Job Seekers</span>
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-award me-2"></i>Scholarship Applicants</span>
                           <span class="ftag px-3 py-2"><i class="fa-solid fa-university me-2"></i>College Applicants</span>
                        </div>
                     </div>
                  </div>
                  <div class="col-lg-6 rv" style="transition-delay:.1s">
                     <!-- STATISTICS -->
                      <div class="row g-3 text-center landing-stats-row">
                         <div class="col-3" data-landing-stat="registered-users">
                            <div class="gc p-4 h-100">
                               <div class="pnum counter" style="font-size:2.5rem; color:var(--pur);">{{ data_get($landingStats ?? [], 'registered_users.display', '0') }}</div>
                               <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Registered Users</div>
                            </div>
                         </div>
                         <div class="col-3" data-landing-stat="interview-sessions">
                            <div class="gc p-4 h-100">
                               <div class="pnum counter" style="font-size:2.5rem; color:#34d399;">{{ data_get($landingStats ?? [], 'interview_sessions.display', '0') }}</div>
                               <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Interview Sessions</div>
                            </div>
                         </div>
                         <div class="col-3" data-landing-stat="questions-available">
                            <div class="gc p-4 h-100">
                               <div class="pnum counter" style="font-size:2.5rem; color:#f59e0b;">{{ data_get($landingStats ?? [], 'questions_available.display', '0') }}</div>
                               <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Questions Available</div>
                            </div>
                         </div>
                         <div class="col-3" data-landing-stat="feedback-generated">
                            <div class="gc p-4 h-100">
                               <div class="pnum counter" style="font-size:2.5rem; color:#3b82f6;">{{ data_get($landingStats ?? [], 'feedback_generated.display', '0') }}</div>
                               <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">AI Feedback Generated</div>
                            </div>
                         </div>
                         <div class="col-12 mt-3" data-landing-stat="success-rate">
                            <div class="gc p-4">
                               <div class="d-flex justify-content-center align-items-center gap-2">
                                 <div class="pnum" style="font-size:3rem; color:var(--pur);"><span class="counter">{{ data_get($landingStats ?? [], 'success_rate.display', '0') }}</span>%</div>
                                 <div class="text-start plbl text-uppercase" style="font-size:0.9rem; letter-spacing:1px;">Success<br>Rate</div>
                               </div>
                            </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- CORE FEATURES -->
         <section id="features" class="sp">
            <div class="container">
               <div class="text-center mb-5 rv">
                  <span class="slbl">Core Features</span>
                  <h2 class="stitle">Everything you need to <span class="gt">succeed</span></h2>
               </div>
               <div class="swiper landingFeatureSwiper landing-auto-carousel features-auto-carousel" aria-label="Core features carousel">
                  <div class="row g-4 swiper-wrapper">
                  <div class="col-md-3 col-sm-6 rv swiper-slide">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#2563eb;--feature-icon-bg:rgba(37,99,235,.14);--feature-icon-border:rgba(37,99,235,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-gauge-high fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Dashboard Overview</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Monitor readiness scores, recent sessions, learning progress, and AI feedback summaries from one home base.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.05s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#10b981;--feature-icon-bg:rgba(16,185,129,.14);--feature-icon-border:rgba(16,185,129,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-microphone-lines fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Philippine AI Mock Interviews</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Practice with a realistic AI interviewer using role, category, difficulty, focus, and timed question settings.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#f59e0b;--feature-icon-bg:rgba(245,158,11,.14);--feature-icon-border:rgba(245,158,11,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-file-lines fa-lg"></i></div>
                         <h3 class="fs-6 fw-bold mb-2">Job Evidence Mapping</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Compare your resume and role details to focus practice on the skills a job asks for.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.15s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#8b5cf6;--feature-icon-bg:rgba(139,92,246,.14);--feature-icon-border:rgba(139,92,246,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-ear-listen fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Voice Rehearsal Studio</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Improve pacing, clarity, delivery stability, and filler-word control without treating speaking style as personality.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#ef4444;--feature-icon-bg:rgba(239,68,68,.14);--feature-icon-border:rgba(239,68,68,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-book-open-reader fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Interview Modules</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Study structured modules with chapters, resources, quizzes, and practice activities tied to interview skills.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.25s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#ec4899;--feature-icon-bg:rgba(236,72,153,.14);--feature-icon-border:rgba(236,72,153,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-gamepad fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Learning Games</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Complete challenge paths with levels, energy, lives, target tones, banned words, and score goals.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#06b6d4;--feature-icon-bg:rgba(6,182,212,.14);--feature-icon-border:rgba(6,182,212,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-robot fa-lg"></i></div>
                         <h3 class="fs-6 fw-bold mb-2">AI Practice Coach</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Get focused prep guidance, score explanations, and grounded advice without invented achievements.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.35s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#22c55e;--feature-icon-bg:rgba(34,197,94,.14);--feature-icon-border:rgba(34,197,94,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-clipboard-check fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Feedback Center</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">See evidence-linked rubrics, score confidence, fact-grounded revision templates, and targeted follow-ups.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.4s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#3b82f6;--feature-icon-bg:rgba(59,130,246,.14);--feature-icon-border:rgba(59,130,246,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-chart-line fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Progress Tracking</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Visualize readiness, STAR structure, skill breakdowns, learning progress, and voice rehearsal growth.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.45s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#0ea5e9;--feature-icon-bg:rgba(14,165,233,.14);--feature-icon-border:rgba(14,165,233,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-folder-open fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Reports &amp; Sharing</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Print detailed reviews and create expiring, password-protected links with reviewer permissions.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.5s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#6366f1;--feature-icon-bg:rgba(99,102,241,.14);--feature-icon-border:rgba(99,102,241,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-network-wired fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Skill Trees</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Earn leadership, communication, technical, and problem-solving XP, then unlock perks as you improve.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv swiper-slide" style="transition-delay:.55s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="--feature-icon-color:#eab308;--feature-icon-bg:rgba(234,179,8,.14);--feature-icon-border:rgba(234,179,8,.28);width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;"><i class="fa-solid fa-trophy fa-lg"></i></div>
                         <h3 class="fs-6 fw-bold mb-2">Personal Mastery</h3>
                         <p style="font-size:.85rem;color:var(--tx2)">Compare only against your own assessment baseline, personal best, and competency growth.</p>
                     </div>
                  </div>
                  </div>
                  <div class="swiper-pagination landing-carousel-pagination landing-features-pagination"></div>
                  <button type="button" class="landing-carousel-autoplay-toggle landing-features-autoplay-toggle" aria-label="Pause core features carousel" data-pause-label="Pause core features carousel" data-play-label="Play core features carousel">
                     <i class="fa-solid fa-pause" aria-hidden="true"></i>
                  </button>
                  <button type="button" class="swiper-button-next landing-carousel-next landing-features-next" aria-label="Next feature"></button>
                  <button type="button" class="swiper-button-prev landing-carousel-prev landing-features-prev" aria-label="Previous feature"></button>
               </div>
            </div>
         </section>

         <!-- HOW IT WORKS -->
         <section id="how" class="sp" style="background:var(--bg3)">
            <div class="container">
               <div class="landing-section-heading mb-5 rv">
                  <span class="slbl">How It Works</span>
                  <h2 class="stitle">Your journey to <span class="gt">Philippine interview mastery</span></h2>
               </div>

               <div class="swiper landingHowSwiper landing-auto-carousel how-auto-carousel" aria-label="How it works carousel">
                  <div class="row g-4 justify-content-center swiper-wrapper">
                  <div class="col-md-4 col-sm-6 rv swiper-slide">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">1</div>
                        <h3 class="fs-5 fw-semibold mb-2">Create an Account</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Join the community and access your personalized dashboard.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">2</div>
                        <h3 class="fs-5 fw-semibold mb-2">Configure Your Setup</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Choose your target role, difficulty, and Philippine interview scenario.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">3</div>
                        <h3 class="fs-5 fw-semibold mb-2">Take a Philippine Mock Interview</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Face our interactive AI avatar with Philippine HR, BPO, IT, and fresh graduate questions.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">4</div>
                        <h3 class="fs-5 fw-semibold mb-2">Review AI Feedback</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Get instant, actionable evaluations on your performance.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.4s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">5</div>
                        <h3 class="fs-5 fw-semibold mb-2">Train & Rehearse</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Refine your skills using Voice Rehearsal and the AI Coach.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.5s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">6</div>
                        <h3 class="fs-5 fw-semibold mb-2">Track Your Progress</h3>
                         <p style="font-size:.875rem;color:var(--tx2)">Monitor competency growth, real interview outcomes, and your personal assessment baseline.</p>
                     </div>
                  </div>
                  </div>
                  <div class="swiper-pagination landing-carousel-pagination landing-how-pagination"></div>
                  <button type="button" class="landing-carousel-autoplay-toggle landing-how-autoplay-toggle" aria-label="Pause how it works carousel" data-pause-label="Pause how it works carousel" data-play-label="Play how it works carousel">
                     <i class="fa-solid fa-pause" aria-hidden="true"></i>
                  </button>
                  <button type="button" class="swiper-button-next landing-carousel-next landing-how-next" aria-label="Next how it works step"></button>
                  <button type="button" class="swiper-button-prev landing-carousel-prev landing-how-prev" aria-label="Previous how it works step"></button>
               </div>
            </div>
         </section>

         <!-- INTERVIEW CATEGORIES -->
         <section id="benefits" class="sp position-relative">
            <div class="aur aur-b" style="top:50%;right:-200px;transform:translateY(-50%)"></div>
            <div class="container position-relative" style="z-index:1">
               <div class="row justify-content-center">
                  <div class="col-lg-10 rv">
                     <div class="landing-section-heading mb-4">
                        <span class="slbl">Interview Categories</span>
                        <h2 class="stitle">Tailored to your <span class="gt">goals</span></h2>
                     </div>
                     <div class="swiper landingCategorySwiper landing-auto-carousel category-auto-carousel" aria-label="Interview categories carousel">
                        <div class="row g-3 swiper-wrapper">
                        <div class="col-sm-6 swiper-slide">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid var(--pur);">
                              <div style="font-size:2rem; margin-bottom:15px; color:var(--pur)"><i class="fa-solid fa-briefcase"></i></div>
                              <h4 class="fs-5 fw-bold">Job Interview</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Practice employment interviews across various industries.</p>
                           </div>
                        </div>
                        <div class="col-sm-6 swiper-slide">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #34d399;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#34d399"><i class="fa-solid fa-award"></i></div>
                              <h4 class="fs-5 fw-bold">Scholarship Interview</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Prepare for rigorous scholarship and grant applications.</p>
                           </div>
                        </div>
                        <div class="col-sm-6 swiper-slide">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #f59e0b;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#f59e0b"><i class="fa-solid fa-university"></i></div>
                              <h4 class="fs-5 fw-bold">College Admission</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Improve admission interview performance for top universities.</p>
                           </div>
                        </div>
                        <div class="col-sm-6 swiper-slide">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #3b82f6;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#3b82f6"><i class="fa-solid fa-laptop-code"></i></div>
                              <h4 class="fs-5 fw-bold">IT/Programming</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Practice technical, coding, and system design interviews.</p>
                           </div>
                        </div>
                        </div>
                        <div class="swiper-pagination landing-carousel-pagination landing-category-pagination"></div>
                        <button type="button" class="landing-carousel-autoplay-toggle landing-category-autoplay-toggle" aria-label="Pause interview categories carousel" data-pause-label="Pause interview categories carousel" data-play-label="Play interview categories carousel">
                           <i class="fa-solid fa-pause" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="swiper-button-next landing-carousel-next landing-category-next" aria-label="Next interview category"></button>
                        <button type="button" class="swiper-button-prev landing-carousel-prev landing-category-prev" aria-label="Previous interview category"></button>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- DEVELOPERS -->
         <section id="developers" class="sp" style="background:var(--bg2)">
            <div class="container">
               <div class="text-center mb-5 rv">
                  <span class="slbl">Developers</span>
                  <h2 class="stitle">Meet Our <span class="gt">Team</span></h2>
               </div>
               <div class="developers-grid">
                  <div class="developer-card-wrap rv">
                     <div class="gc p-4 h-100 developer-card">
                        <img src="{{ asset('img/dev1.png') }}" alt="Developer" class="developer-photo img-fluid rounded-circle mb-3" style="border: 4px solid var(--pur);">
                        <h6 class="fw-bold mb-1">Jonh Rogiel M. Tumanda</h6>
                        <p class="developer-role" style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Lead Programmer</p>
                        <p class="developer-bio" style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Core Code, Databases, and APIs.</p>
                     </div>
                  </div>
                  <div class="developer-card-wrap rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 developer-card">
                        <img src="{{ asset('img/dev2.png') }}" alt="Developer" class="developer-photo img-fluid rounded-circle mb-3" style="border: 4px solid #34d399;">
                        <h6 class="fw-bold mb-1">Karyl G. Gesto</h6>
                        <p class="developer-role" style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Manuscript Editor</p>
                        <p class="developer-bio" style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Technical Writing, Documentation, and Compliance.</p>
                     </div>
                  </div>
                  <div class="developer-card-wrap rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 developer-card">
                        <img src="{{ asset('img/dev3.png') }}" alt="Developer" class="developer-photo img-fluid rounded-circle mb-3" style="border: 4px solid #f59e0b;">
                        <h6 class="fw-bold mb-1">Eva Mae C. Cabilic</h6>
                        <p class="developer-role" style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">QA Tester</p>
                        <p class="developer-bio" style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Bug Hunting, Test Cases, and UX Stability.</p>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- FAQ -->
         <section id="faq" class="sp" style="background:var(--bg2)">
            <div class="container">
               <div class="text-center mb-5 rv">
                  <span class="slbl">FAQ</span>
                  <h2 class="stitle">Common <span class="gt">Questions</span></h2>
               </div>
               <div class="row justify-content-center rv">
                  <div class="col-lg-8">
                     <div class="accordion acco" id="faqAcc">
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#f1">What is SpeakReady AI?</button></h2>
                           <div id="f1" class="accordion-collapse collapse show" data-bs-parent="#faqAcc">
                              <div class="accordion-body">SpeakReady AI is an intelligent mock interview platform designed to help students, job seekers, and applicants practice their interview skills using advanced AI simulations.</div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f2">How does AI feedback work?</button></h2>
                           <div id="f2" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                               <div class="accordion-body">SpeakReady evaluates answer relevance, clarity, professionalism, applicable STAR evidence, and job evidence using a versioned rubric. Delivery signals and optional body-language prompts are coaching aids, do not affect readiness scores, and do not infer confidence, honesty, or personality.</div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f3">Is my data secure?</button></h2>
                           <div id="f3" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                               <div class="accordion-body">Interview records are private by default. When you choose to share a review, you can set an expiry, optional password, reviewer permissions, and hide sensitive identity or application context.</div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f4">Can I practice multiple interview types?</button></h2>
                           <div id="f4" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                              <div class="accordion-body">Absolutely. You can choose from Job Interviews, Scholarship Interviews, College Admissions, or specific IT/Programming technical interviews.</div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f5">Is the system free to use?</button></h2>
                           <div id="f5" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                              <div class="accordion-body">SpeakReady AI offers a free basic tier so you can start practicing immediately. We also offer premium plans with unlimited sessions and advanced analytics.</div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- CONTACT US -->
         <section id="contact" class="sp position-relative">
            <div class="container position-relative" style="z-index:1">
               <div class="landing-section-heading mb-5 rv">
                  <span class="slbl">Contact Us</span>
                  <h2 class="stitle">Get in <span class="gt">Touch</span></h2>
               </div>
               <div class="row g-5 justify-content-center">
                  <div class="col-lg-5 rv">
                     <p style="font-size:1.05rem;color:var(--tx2);margin-bottom:30px">Have questions or need support? We're here to help you on your journey to interview success.</p>

                     <div class="d-flex flex-column gap-4">
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-envelope" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Email Address</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">admin@speakready.ai</p>
                             </div>
                         </div>
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-phone" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Contact Number</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">09066544727</p>
                             </div>
                         </div>
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-location-dot" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Location</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">Pinut-an, San Ricardo, Southern Leyte, Philippines</p>
                             </div>
                         </div>
                     </div>
                  </div>
                  <div class="col-lg-5 rv" style="transition-delay:.1s">
                     <div class="gc p-4 p-md-5 h-100">
                         @if(session('contact_success'))
                             <div class="alert alert-success d-flex align-items-center mb-4" role="status" style="background: rgba(52, 211, 153, 0.12); border: 1px solid rgba(52, 211, 153, 0.24); color: #10b981; border-radius: 12px; padding: 15px;">
                                 <i class="fa-solid fa-circle-check fs-5 me-3" aria-hidden="true"></i>
                                 <div>{{ session('contact_success') }}</div>
                             </div>
                         @endif
                         @if($errors->contact->any())
                             <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; border-radius: 12px; padding: 15px;">
                                 <i class="fa-solid fa-circle-exclamation fs-5 me-3" aria-hidden="true"></i>
                                 <div>
                                     <strong>Check your message:</strong> {{ $errors->contact->first() }}
                                 </div>
                                 <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" style="filter: brightness(0.5);"></button>
                             </div>
                         @endif
                         @if(session('contact_error'))
                             <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; border-radius: 12px; padding: 15px;">
                                 <i class="fa-solid fa-circle-xmark fs-5 me-3" aria-hidden="true"></i>
                                 <div>
                                     <strong>Error:</strong> {{ session('contact_error') }}
                                 </div>
                                 <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" style="filter: brightness(0.5);"></button>
                             </div>
                         @endif
                         <form action="{{ route('contact.send') }}" method="POST">
                             @csrf
                             <div class="mb-3">
                                 <label class="form-label" for="contactName" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Name</label>
                                 <input type="text" name="name" id="contactName" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your Full Name" value="{{ $errors->contact->any() ? old('name') : '' }}" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" for="contactEmail" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Email</label>
                                 <input type="email" name="email" id="contactEmail" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="you@example.com" value="{{ $errors->contact->any() ? old('email') : '' }}" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" for="contactSubject" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Subject</label>
                                 <input type="text" name="subject" id="contactSubject" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="How can we help?" value="{{ $errors->contact->any() ? old('subject') : '' }}" required>
                             </div>
                             <div class="mb-4">
                                 <label class="form-label" for="contactMessage" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Message</label>
                                 <textarea name="message" id="contactMessage" class="form-control" rows="4" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your message here..." required>{{ $errors->contact->any() ? old('message') : '' }}</textarea>
                             </div>
                             <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">Send Message</button>
                         </form>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- FOOTER -->
         <footer id="foot">
            <div class="container footer-shell">
               <div class="footer-panel">
                  <div class="footer-brand">
                     <a class="footer-brand-link" href="#hero">
                        <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i footer-logo">
                        <span>SpeakReady AI</span>
                     </a>
                     <p class="footer-copy">Your personal Philippine interview coach for smarter practice, clearer feedback, and confident interview preparation.</p>
                  </div>
                  <nav class="footer-nav-grid" aria-label="Footer navigation">
                     <div>
                        <h5 class="footer-heading">Company</h5>
                        <ul class="list-unstyled footer-links">
                           <li><a href="#features">Features</a></li>
                           <li><a href="#about">About</a></li>
                           <li><a href="#how">How It Works</a></li>
                           <li><a href="#contact">Contact</a></li>
                           <li><a href="#faq">FAQ</a></li>
                        </ul>
                     </div>
                     <div>
                        <h5 class="footer-heading">Platform</h5>
                        <ul class="list-unstyled footer-links">
                           <li><a href="#lofc" role="button" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('login')">Log In</a></li>
                           <li><a href="#lofc" role="button" data-bs-toggle="modal" data-bs-target="#lofc" onclick="swTab('signup')">Register</a></li>
                           <li><a href="#benefits">Benefits</a></li>
                           <li><a href="{{ route('legal.privacy') }}">Privacy Policy</a></li>
                           <li><a href="{{ route('legal.terms') }}">Terms of Service</a></li>
                        </ul>
                     </div>
                  </nav>
                  <div class="footer-action">
                     <div>
                        <h5 class="footer-heading">Stay Updated</h5>
                        <p>Get interview tips, feature updates, and practice reminders in one clean digest.</p>
                     </div>
                     @if(session('newsletter_success'))
                         <div class="alert alert-success mb-0 py-2 px-3" role="status" style="background:rgba(52,211,153,0.12);border:1px solid rgba(52,211,153,0.24);color:#10b981;border-radius:8px;font-size:0.78rem;">
                            {{ session('newsletter_success') }}
                         </div>
                     @endif
                     @if($errors->newsletter->any())
                         <div class="alert alert-danger mb-0 py-2 px-3" role="alert" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#ef4444;border-radius:8px;font-size:0.78rem;">
                            {{ $errors->newsletter->first() }}
                         </div>
                     @endif
                     <form class="footer-newsletter d-flex gap-2" action="{{ route('newsletter.subscribe') }}" method="POST">
                         @csrf
                         <label class="visually-hidden" for="footerNewsletterEmail">Email address</label>
                         <input id="footerNewsletterEmail" name="email" type="email" placeholder="Email address" class="form-control" value="{{ $errors->newsletter->any() ? old('email') : '' }}" required>
                         <button type="submit" class="btn footer-newsletter-btn fw-semibold px-3" aria-label="Subscribe to updates"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
                     </form>
                     <div class="footer-socials" aria-label="Contact links">
                         <a href="mailto:admin@speakready.ai" class="footer-social-link" title="Email SpeakReady AI" aria-label="Email SpeakReady AI"><i class="fa-solid fa-envelope" aria-hidden="true"></i></a>
                         <a href="tel:09066544727" class="footer-social-link" title="Call SpeakReady AI" aria-label="Call SpeakReady AI"><i class="fa-solid fa-phone" aria-hidden="true"></i></a>
                         <a href="https://www.google.com/maps/search/?api=1&query=Pinut-an%2C%20San%20Ricardo%2C%20Southern%20Leyte%2C%20Philippines" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="View location" aria-label="View location"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></a>
                     </div>
                  </div>
               </div>
               <div class="footer-bottom">
                  <p>&copy; {{ date('Y') }} SpeakReady AI. All rights reserved.</p>
                  <div class="footer-legal">
                      <a href="{{ route('legal.security') }}" class="footer-legal-link">Security</a>
                      <span class="footer-dot" aria-hidden="true"></span>
                      <a href="{{ route('legal.cookies') }}" class="footer-legal-link">Cookie Preferences</a>
                  </div>
               </div>
            </div>
         </footer>
      </div>
      <!-- /landing -->



      <!-- ======================== LOGIN MODAL ======================== -->
      <div class="modal fade" tabindex="-1" id="lofc" aria-labelledby="authModalTitle" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:var(--sf);color:var(--tx);border:1px solid var(--bd);border-radius:18px;box-shadow:0 24px 80px rgba(0,0,0,.35);overflow:hidden;">
               <div class="modal-header" style="border-bottom:1px solid var(--bd);">
                  <div class="d-flex align-items-center gap-2">
                     <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="width:30px;height:30px;background: #ffffff; padding: 0;">
                     <h5 class="modal-title mb-0" id="authModalTitle">SpeakReady AI</h5>
                  </div>
                  <button type="button" class="btn-close auth-modal-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body p-4">
            <div class="tab-switch" role="tablist" aria-label="Authentication options"><button type="button" class="tab-sw-btn on" id="tabLogin" role="tab" aria-selected="true" aria-controls="fLogin" onclick="swTab('login')">Log In</button><button type="button" class="tab-sw-btn" id="tabSignup" role="tab" aria-selected="false" aria-controls="fSignup" onclick="swTab('signup')">Register</button></div>
            <!-- Login -->
            <div id="fLogin" class="auth-panel" role="tabpanel" aria-labelledby="tabLogin">
               @if(session('success'))
                  <div style="background:rgba(52,211,153,0.1);color:#34d399;border:1px solid rgba(52,211,153,0.2);padding:10px;border-radius:12px;font-size:0.85rem;margin-bottom:15px;">
                     <i class="fa-solid fa-check-circle me-1"></i> {{ session('success') }}
                  </div>
               @endif
               @if($errors->has('account_inactive'))
                  <div class="err-msg" style="display:block; padding:12px; margin-bottom:15px; text-align:left; line-height: 1.4;">
                     <div class="mb-2"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first('account_inactive') }}</span></div>
                     <form action="{{ route('request.reactivation') }}" method="POST">
                        @csrf
                        <input type="hidden" name="email" value="{{ old('email') }}">
                        <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold" style="border-radius:8px; background: #f59e0b; border: none; color: #fff;">Request Reactivation</button>
                     </form>
                  </div>
               @endif
               <form id="loginForm" action="{{ route('login') }}" method="POST">
                  @csrf
                  @if($errors->any() && !$errors->has('account_inactive') && !old('name'))
                     <div class="err-msg" style="display:block;"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first() }}</span></div>
                  @endif
                  <label class="olbl" for="loginEmail"><i class="fa-regular fa-envelope me-1"></i>Email address</label>
                  <input class="oinp" type="email" name="email" id="loginEmail" placeholder="you@example.com" required autocomplete="email" value="{{ old('email') }}">
                  <label class="olbl" for="loginPass"><i class="fa-solid fa-lock me-1"></i>Password</label>
                  <div class="password-field mb-3">
                     <input class="oinp" type="password" name="password" id="loginPass" placeholder="********" required autocomplete="current-password">
                     <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('loginPass', this)" aria-label="Show password">
                        <i class="fa-solid fa-eye-slash"></i>
                     </button>
                  </div>
                  <div class="form-check mb-3" style="margin-top:-4px;">
                     <input type="hidden" name="remember" value="0">
                     <input class="form-check-input" type="checkbox" name="remember" value="1" id="loginRemember" checked>
                     <label class="form-check-label" for="loginRemember" style="font-size:.8rem;color:var(--tx2);">Keep me signed in on this device</label>
                  </div>
                  <div class="text-end mb-3" style="margin-top:-8px"><a href="{{ route('password.request') }}" style="font-size:.8rem;color:var(--pur)">Forgot password?</a></div>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="loginBtn">Log In <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or continue with</div>
               <a href="{{ route('auth.google.login') }}" class="oauth" data-auth-transition="google" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Log in with Google</a>
            </div>
            <!-- Sign Up -->
            <div id="fSignup" class="auth-panel" role="tabpanel" aria-labelledby="tabSignup" style="display:none">
               <form id="signupForm" action="{{ route('register') }}" method="POST">
                  @csrf
                  @if($errors->any() && old('name'))
                     <div class="err-msg" style="display:block;"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first() }}</span></div>
                  @endif
                  <label class="olbl" for="signupName"><i class="fa-regular fa-user me-1"></i>Full name</label>
                  <input class="oinp" type="text" name="name" id="signupName" placeholder="John Doe" required value="{{ old('name') }}">
                  <label class="olbl" for="signupEmail"><i class="fa-solid fa-envelope me-1"></i>Email address</label>
                  <input class="oinp" type="email" name="email" id="signupEmail" placeholder="you@example.com" required autocomplete="email" value="{{ old('email') }}">
                  <label class="olbl" for="signupPass"><i class="fa-solid fa-lock me-1"></i>Password</label>
                  <div class="password-field mb-3">
                     <input class="oinp" type="password" name="password" id="signupPass" placeholder="Min. 8 characters" required autocomplete="new-password">
                     <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('signupPass', this)" aria-label="Show password">
                        <i class="fa-solid fa-eye-slash"></i>
                     </button>
                  </div>
                  <label class="olbl" for="signupPassConfirm"><i class="fa-solid fa-lock me-1"></i>Confirm Password</label>
                  <div class="password-field mb-3">
                     <input class="oinp" type="password" name="password_confirmation" id="signupPassConfirm" placeholder="Confirm your password" required autocomplete="new-password">
                     <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('signupPassConfirm', this)" aria-label="Show password">
                        <i class="fa-solid fa-eye-slash"></i>
                     </button>
                  </div>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="signupBtn">Create Free Account <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or sign up with</div>
               <a href="{{ route('auth.google.register') }}" class="oauth" data-auth-transition="google-register" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Sign up with Google</a>
            </div>
               </div>
            </div>
         </div>
      </div>

      <!-- ===== PWA INSTALL PROMPT ===== -->
      <div id="pwa-install-prompt">
         <h5 id="pwaPromptTitle">Install SpeakReady AI</h5>
         <p id="pwaPromptCopy">Do you want to install this app for a better and faster experience?</p>
         <div class="pwa-btn-wrap">
            <button id="pwa-btn-no" class="pwa-btn-no">No</button>
            <button id="pwa-btn-yes" class="pwa-btn-yes">Yes</button>
         </div>
      </div>

      <button type="button" id="backToTopBtn" class="back-to-top-btn" aria-label="Back to top. Drag to move." title="Drag or tap to go back to top">
         <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
      </button>


<!-- ======================== SCRIPTS ======================== -->
      <!-- jQuery -->
      <script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
      <!-- Bootstrap 5 -->
      <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
      @include('mobile.partials.flash-modal', ['includeValidationErrors' => false])
      <!-- AOS -->
      <script src="{{ asset('js/aos.js') }}"></script>
      <!-- Swiper -->
      <script src="{{ asset('js/swiper-bundle.min.js') }}"></script>
      <script src="{{ asset('js/chart.umd.min.js') }}"></script>
      <!-- Magnific -->
      <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
      <!-- Counter Up and Waypoints -->
      <script src="{{ asset('js/jquery.waypoints.min.js') }}"></script>
      <script src="{{ asset('js/jquery.counterup.min.js') }}"></script>

      <script src="{{ asset('js/main.js?v=7') }}"></script>
      @if($errors->any())
      <script>
         document.addEventListener('DOMContentLoaded', function() {
            var authModal = document.getElementById('lofc');
            var bsModal = new bootstrap.Modal(authModal);
            bsModal.show();
            @if(old('name'))
               swTab('signup');
            @endif
         });
      </script>
      @endif

      <script>
         // Initialize CounterUp and Swiper when document is ready
         $(document).ready(function() {
             if($.fn.counterUp) {
                 $('.counter').counterUp({
                     delay: 10,
                     time: 1500
                 });
             }

             if(typeof Swiper !== 'undefined') {
                 const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

                 document.querySelectorAll(".mobilePreviewSwiper").forEach(function(previewEl) {
                     const previewSwiper = new Swiper(previewEl, {
                         slidesPerView: "auto",
                         centeredSlides: true,
                         spaceBetween: 0,
                         effect: "coverflow",
                         coverflowEffect: {
                             rotate: 0,
                             stretch: 96,
                             depth: 132,
                             modifier: 1,
                             scale: 0.92,
                             slideShadows: false,
                         },
                         loop: true,
                         watchSlidesProgress: true,
                         autoplay: reduceMotion ? false : {
                             delay: 3000,
                             disableOnInteraction: false,
                             pauseOnMouseEnter: true,
                         },
                         pagination: {
                             el: previewEl.querySelector(".mobile-preview-pagination"),
                             clickable: true,
                         },
                         navigation: {
                             nextEl: previewEl.querySelector(".mobile-preview-next"),
                             prevEl: previewEl.querySelector(".mobile-preview-prev"),
                         },
                     });

                     const hydrateNearbyPreviewImages = function() {
                         const previewImages = Array.from(previewEl.querySelectorAll(".mobile-preview-shell-img[data-src]"));
                         if (!previewImages.length) return;

                         const totalSlides = new Set(
                             Array.from(previewEl.querySelectorAll(".mobile-preview-shell-img[data-preview-index]"))
                                 .map(function(img) { return Number(img.dataset.previewIndex); })
                         ).size;
                         if (!totalSlides) return;

                         const activeIndex = Number.isInteger(previewSwiper.realIndex) ? previewSwiper.realIndex : 0;
                         const nearbyIndexes = new Set([
                             activeIndex,
                             (activeIndex + 1) % totalSlides,
                             (activeIndex - 1 + totalSlides) % totalSlides,
                         ]);

                         previewImages.forEach(function(img) {
                             const imageIndex = Number(img.dataset.previewIndex);
                             if (!nearbyIndexes.has(imageIndex)) return;

                             img.src = img.dataset.src;
                             img.removeAttribute("data-src");
                         });
                     };

                     hydrateNearbyPreviewImages();
                     previewSwiper.on("slideChange", hydrateNearbyPreviewImages);

                     const autoplayToggle = previewEl.querySelector(".mobile-preview-autoplay-toggle");
                     if (autoplayToggle) {
                         const icon = autoplayToggle.querySelector("i");

                         if (reduceMotion) {
                             autoplayToggle.classList.add("is-paused");
                             autoplayToggle.setAttribute("aria-label", "Play demo preview");
                             icon?.classList.remove("fa-pause");
                             icon?.classList.add("fa-play");
                         }

                         autoplayToggle.addEventListener("click", function() {
                             const paused = autoplayToggle.classList.toggle("is-paused");
                             if (paused) {
                                 previewSwiper.autoplay?.stop();
                                 autoplayToggle.setAttribute("aria-label", "Play demo preview");
                                 icon?.classList.remove("fa-pause");
                                 icon?.classList.add("fa-play");
                             } else {
                                 previewSwiper.autoplay?.start();
                                 autoplayToggle.setAttribute("aria-label", "Pause demo preview");
                                 icon?.classList.remove("fa-play");
                                 icon?.classList.add("fa-pause");
                             }
                         });
                     }
                 });

                 document.querySelectorAll(".landingFeatureSwiper, .landingHowSwiper, .landingCategorySwiper").forEach(function(carouselEl) {
                     const landingSwiper = new Swiper(carouselEl, {
                         slidesPerView: "auto",
                         centeredSlides: true,
                         spaceBetween: 16,
                         effect: "coverflow",
                         coverflowEffect: {
                             rotate: 0,
                             stretch: 62,
                             depth: 92,
                             modifier: 1,
                             scale: 0.94,
                             slideShadows: false,
                         },
                         loop: true,
                         watchSlidesProgress: true,
                         autoplay: reduceMotion ? false : {
                             delay: 2800,
                             disableOnInteraction: false,
                             pauseOnMouseEnter: true,
                         },
                         pagination: {
                             el: carouselEl.querySelector(".landing-carousel-pagination"),
                             clickable: true,
                         },
                         navigation: {
                             nextEl: carouselEl.querySelector(".landing-carousel-next"),
                             prevEl: carouselEl.querySelector(".landing-carousel-prev"),
                         },
                     });

                     const carouselToggle = carouselEl.querySelector(".landing-carousel-autoplay-toggle");
                     if (carouselToggle) {
                         const icon = carouselToggle.querySelector("i");

                         if (reduceMotion) {
                             carouselToggle.classList.add("is-paused");
                             carouselToggle.setAttribute("aria-label", carouselToggle.dataset.playLabel || "Play carousel");
                             icon?.classList.remove("fa-pause");
                             icon?.classList.add("fa-play");
                         }

                         carouselToggle.addEventListener("click", function() {
                             const paused = carouselToggle.classList.toggle("is-paused");
                             if (paused) {
                                 landingSwiper.autoplay?.stop();
                                 carouselToggle.setAttribute("aria-label", carouselToggle.dataset.playLabel || "Play carousel");
                                 icon?.classList.remove("fa-pause");
                                 icon?.classList.add("fa-play");
                             } else {
                                 landingSwiper.autoplay?.start();
                                 carouselToggle.setAttribute("aria-label", carouselToggle.dataset.pauseLabel || "Pause carousel");
                                 icon?.classList.remove("fa-play");
                                 icon?.classList.add("fa-pause");
                             }
                         });
                     }
                 });

             }
         });
      </script>
      <!-- PWA Service Worker Registration -->
      <script>
         if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
               navigator.serviceWorker.register('/sw.js?v=10').then(function(registration) {
                  console.log('ServiceWorker registration successful with scope: ', registration.scope);
               }, function(err) {
                  console.log('ServiceWorker registration failed: ', err);
               });
            });
         }

         // PWA Install Prompt Logic
         let deferredPrompt;

         function isPwaAlreadyInstalled() {
            return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               localStorage.getItem('pwa_app_installed') === 'true';
         }

         async function updateInstallButtonState() {
            const installButton = document.getElementById('heroInstallBtn');
            if (!installButton) return;

            let isInstalled = isPwaAlreadyInstalled();

            if (!isInstalled && 'getInstalledRelatedApps' in navigator) {
               try {
                  const relatedApps = await navigator.getInstalledRelatedApps();
                  isInstalled = relatedApps.length > 0;
               } catch (error) {
                  isInstalled = isPwaAlreadyInstalled();
               }
            }

            const icon = installButton.querySelector('i');
            const label = isInstalled ? 'Already Installed' : 'Install App';
            installButton.disabled = isInstalled;
            installButton.classList.toggle('is-disabled', isInstalled);

            if (icon) {
               installButton.replaceChildren(icon, document.createTextNode(label));
            } else {
               installButton.textContent = label;
            }

            installButton.setAttribute('aria-label', label);
         }

         window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            localStorage.removeItem('pwa_app_installed');
            updateInstallButtonState();
            if (!localStorage.getItem('pwa_prompt_dismissed')) {
               queuePwaInstallPrompt();
            }
         });

         window.addEventListener('appinstalled', () => {
            localStorage.setItem('pwa_app_installed', 'true');
            deferredPrompt = null;
            document.getElementById('pwa-install-prompt')?.style.setProperty('display', 'none');
            updateInstallButtonState();
         });

         const standaloneDisplayMode = window.matchMedia('(display-mode: standalone)');
         if (standaloneDisplayMode.addEventListener) {
            standaloneDisplayMode.addEventListener('change', updateInstallButtonState);
         } else if (standaloneDisplayMode.addListener) {
            standaloneDisplayMode.addListener(updateInstallButtonState);
         }

         document.addEventListener('DOMContentLoaded', updateInstallButtonState);
         updateInstallButtonState();

         function queuePwaInstallPrompt() {
            const prompt = document.getElementById('pwa-install-prompt');
            if (!prompt) return;

            window.setTimeout(() => {
               if (document.body.classList.contains('guest-splash-pending')) {
                  queuePwaInstallPrompt();
                  return;
               }

               if (!localStorage.getItem('pwa_prompt_dismissed')) {
                  showPwaInstallMessage('Install SpeakReady AI', 'Do you want to install this app for a better and faster experience?', true);
               }
            }, 4200);
         }

         async function triggerInstall() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
            const prompt = document.getElementById('pwa-install-prompt');

            if (isStandalone) {
                showPwaInstallMessage('Already installed', 'SpeakReady AI is already running as an installed app on this device.', false);
                return;
            }

            if (deferredPrompt) {
               deferredPrompt.prompt();
               const { outcome } = await deferredPrompt.userChoice;
               console.log(`User response to the install prompt: ${outcome}`);
               if (outcome === 'accepted') {
                  localStorage.setItem('pwa_app_installed', 'true');
               }
               deferredPrompt = null;
               prompt?.style.setProperty('display', 'none');
               updateInstallButtonState();
            } else {
               if (isIos) {
                   showPwaInstallMessage('Install from Safari', "Tap Safari's Share button, then choose Add to Home Screen.", false);
               } else {
                   showPwaInstallMessage('Install unavailable', 'Your browser is not offering an install prompt right now. Try Chrome or Edge, or use your browser menu if it shows Install app.', false);
               }
            }
         }

         function showPwaInstallMessage(title, message, allowInstall) {
            const prompt = document.getElementById('pwa-install-prompt');
            const promptTitle = document.getElementById('pwaPromptTitle');
            const promptCopy = document.getElementById('pwaPromptCopy');
            const yesButton = document.getElementById('pwa-btn-yes');
            const noButton = document.getElementById('pwa-btn-no');

            if (!prompt) return;

            if (promptTitle) promptTitle.textContent = title;
            if (promptCopy) promptCopy.textContent = message;
            if (yesButton) yesButton.hidden = !allowInstall;
            if (noButton) noButton.textContent = allowInstall ? 'No' : 'OK';
            prompt.dataset.mode = allowInstall ? 'install' : 'message';
            prompt.style.display = 'block';
         }

         document.getElementById('pwa-btn-yes')?.addEventListener('click', triggerInstall);
         document.getElementById('heroInstallBtn')?.addEventListener('click', triggerInstall);

         document.getElementById('pwa-btn-no')?.addEventListener('click', () => {
            const prompt = document.getElementById('pwa-install-prompt');
            prompt?.style.setProperty('display', 'none');
            if (prompt?.dataset.mode !== 'message') {
               localStorage.setItem('pwa_prompt_dismissed', 'true');
            }
         });
      </script>

      <script>
         document.addEventListener('DOMContentLoaded', function() {
            const backToTopBtn = document.getElementById('backToTopBtn');
            if (!backToTopBtn) return;

            const edgePadding = 10;
            let hasCustomPosition = false;
            let suppressNextClick = false;
            const drag = {
               active: false,
               moved: false,
               pointerId: null,
               startX: 0,
               startY: 0,
               offsetX: 0,
               offsetY: 0
            };

            const clamp = function(value, min, max) {
               return Math.min(Math.max(value, min), max);
            };

            const placeBackToTop = function(left, top) {
               const width = backToTopBtn.offsetWidth || 48;
               const height = backToTopBtn.offsetHeight || 48;
               const maxLeft = Math.max(edgePadding, window.innerWidth - width - edgePadding);
               const maxTop = Math.max(edgePadding, window.innerHeight - height - edgePadding);

               backToTopBtn.style.left = clamp(left, edgePadding, maxLeft) + 'px';
               backToTopBtn.style.top = clamp(top, edgePadding, maxTop) + 'px';
               backToTopBtn.style.right = 'auto';
               backToTopBtn.style.bottom = 'auto';
               hasCustomPosition = true;
            };

            const keepBackToTopInView = function() {
               if (!hasCustomPosition) return;

               const rect = backToTopBtn.getBoundingClientRect();
               placeBackToTop(rect.left, rect.top);
            };

            const toggleBackToTop = function() {
               const demoTargets = [
                  document.getElementById('demo-preview'),
                  document.querySelector('.mobilePreviewSwiper')
               ].filter(Boolean);
               const demoPreviewVisible = demoTargets.some(function(target) {
                  const rect = target.getBoundingClientRect();
                  return rect.bottom > 72 && rect.top < window.innerHeight - 72;
               });

               backToTopBtn.classList.toggle('is-visible', window.scrollY > 420 && !demoPreviewVisible);
            };

            toggleBackToTop();
            window.addEventListener('scroll', toggleBackToTop, { passive: true });
            window.addEventListener('resize', keepBackToTopInView, { passive: true });

            backToTopBtn.addEventListener('pointerdown', function(event) {
               if (event.button !== undefined && event.button !== 0) return;

               const rect = backToTopBtn.getBoundingClientRect();
               drag.active = true;
               drag.moved = false;
               drag.pointerId = event.pointerId;
               drag.startX = event.clientX;
               drag.startY = event.clientY;
               drag.offsetX = event.clientX - rect.left;
               drag.offsetY = event.clientY - rect.top;

               backToTopBtn.classList.add('is-dragging');
               backToTopBtn.setPointerCapture?.(event.pointerId);
            });

            backToTopBtn.addEventListener('pointermove', function(event) {
               if (!drag.active || event.pointerId !== drag.pointerId) return;

               const movedX = Math.abs(event.clientX - drag.startX);
               const movedY = Math.abs(event.clientY - drag.startY);

               if (movedX + movedY > 4) {
                  drag.moved = true;
               }

               if (!drag.moved) return;

               event.preventDefault();
               placeBackToTop(event.clientX - drag.offsetX, event.clientY - drag.offsetY);
            });

            const finishDrag = function(event) {
               if (!drag.active || event.pointerId !== drag.pointerId) return;

               if (drag.moved) {
                  suppressNextClick = true;
                  window.setTimeout(function() {
                     suppressNextClick = false;
                  }, 350);
               }

               drag.active = false;
               drag.pointerId = null;
               backToTopBtn.classList.remove('is-dragging');
               backToTopBtn.releasePointerCapture?.(event.pointerId);
            };

            backToTopBtn.addEventListener('pointerup', finishDrag);
            backToTopBtn.addEventListener('pointercancel', finishDrag);

            backToTopBtn.addEventListener('click', function(event) {
               if (suppressNextClick) {
                  event.preventDefault();
                  event.stopPropagation();
                  suppressNextClick = false;
                  return;
               }

               window.scrollTo({
                  top: 0,
                  behavior: 'smooth'
               });
            });
         });
      </script>

      <!-- LOGIN TRANSITION OVERLAY -->

      <div id="loginTransitionOverlay">
          <div class="logo-loading-wrapper">
              <div class="logo-loading-circle"></div>
              <img src="{{ asset('img/logo.png') }}" alt="Loading...">
          </div>
          <h4 id="authTransitionTitle" style="color:var(--tx); font-weight:600; font-size:1.2rem; letter-spacing:0.5px;">Authenticating...</h4>
          <p id="authTransitionCopy" style="color:var(--tx3); font-size:0.9rem;">Please wait while we log you in</p>
      </div>

      <script>
          function showLoginTransition(mode) {
              const overlay = document.getElementById('loginTransitionOverlay');
              const title = document.getElementById('authTransitionTitle');
              const copy = document.getElementById('authTransitionCopy');

              if (mode === 'register') {
                  if (title) title.textContent = 'Creating your account...';
                  if (copy) copy.textContent = 'Please wait while we set up your dashboard';
              } else if (mode === 'google-register') {
                  if (title) title.textContent = 'Signing up with Google...';
                  if (copy) copy.textContent = 'Opening secure Google registration';
              } else if (mode === 'google' || mode === 'google-login') {
                  if (title) title.textContent = 'Connecting to Google...';
                  if (copy) copy.textContent = 'Opening secure Google sign-in';
              } else {
                  if (title) title.textContent = 'Authenticating...';
                  if (copy) copy.textContent = 'Please wait while we log you in';
              }

              if (overlay) overlay.classList.add('active');
          }

          document.addEventListener('DOMContentLoaded', function() {
              const loginForm = document.getElementById('loginForm');
              if (loginForm) {
                  loginForm.addEventListener('submit', function(e) {
                      if (this.checkValidity()) {
                          showLoginTransition();
                      }
                  });
              }

              const signupForm = document.getElementById('signupForm');
              if (signupForm) {
                  signupForm.addEventListener('submit', function() {
                      if (this.checkValidity()) {
                          showLoginTransition('register');
                      }
                  });
              }

              const googleAuthLinks = document.querySelectorAll('a[data-auth-transition^="google"]');
              const resetGoogleAuthLinks = function() {
                  googleAuthLinks.forEach(function(link) {
                      link.style.pointerEvents = '';
                      link.removeAttribute('aria-disabled');
                      const icon = link.querySelector('i');
                      if (icon) {
                          icon.className = 'fa-brands fa-google me-2';
                          icon.style.color = '#EA4335';
                      }
                  });
              };

              googleAuthLinks.forEach(function(link) {
                  link.addEventListener('click', function(event) {
                      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                          return;
                      }

                      event.preventDefault();
                      showLoginTransition(link.dataset.authTransition || 'google-login');
                      link.setAttribute('aria-disabled', 'true');
                      link.style.pointerEvents = 'none';

                      const icon = link.querySelector('i');
                      if (icon) {
                          icon.className = 'fa-solid fa-spinner fa-spin me-2';
                          icon.style.color = '';
                      }

                      window.setTimeout(function() {
                          window.location.href = link.href;
                      }, 80);
                  });
              });

              window.addEventListener('pageshow', function() {
                  const overlay = document.getElementById('loginTransitionOverlay');
                  if (overlay) overlay.classList.remove('active');
                  resetGoogleAuthLinks();
              });
          });

          function togglePasswordVisibility(inputId, btn) {
             const input = document.getElementById(inputId);
             const icon = btn.querySelector('i');
             if (!input || !icon) return;
             if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                btn.setAttribute('aria-label', 'Hide password');
             } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                btn.setAttribute('aria-label', 'Show password');
             }
          }
      </script>
      @include('mobile.partials.page-transition')
   </body>
</html>
