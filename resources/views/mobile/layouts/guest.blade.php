<!DOCTYPE html>
<html lang="{{ $systemHtmlLocale ?? 'en' }}" id="htmlRoot" data-speech-locale="{{ $systemSpeechLocale ?? 'en-US' }}">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
      <meta name="theme-color" content="#f1f6fb">
      <title>@yield('title', ($systemName ?? 'SpeakReady AI').' - Practice Smarter. Interview Better.')</title>
      @include('shared.early-layout-shell-guard', ['serverLayout' => 'mobile'])
      <script src="{{ asset('js/theme-boot.js?v=2') }}"></script>
      <link rel="icon" href="{{ asset($systemFavicon ?? 'favicon.ico') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json?v=7') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/apple-touch-icon.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
      <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
      <link rel="preload" href="{{ asset('videos/speakready-ai-15s-product-ad.mp4') }}?v=20261102" as="video" type="video/mp4" fetchpriority="high">
      <!-- Bootstrap 5.3 -->
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"/>
      <!-- Swiper CSS -->
      <link href="{{ asset('css/swiper-bundle.min.css') }}" rel="stylesheet"/>
      <!-- all min css -->
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
      <!-- Style CSS -->
      <link rel="stylesheet" href="{{ asset('css/mobile/style.css?v=39') }}" />
      <link rel="stylesheet" href="{{ asset('css/mobile/guest.css?v=39') }}" />
      <link rel="stylesheet" href="{{ asset('css/about-system.css?v=4') }}" />
      <link rel="stylesheet" href="{{ asset('css/landing-hero.css?v=6') }}" />
      <style data-mobile-side-gutter="10px">
         :root,
         .lm {
            --pur: {{ $systemPrimaryColor ?? '#2563eb' }};
            --blu: {{ $systemSecondaryColor ?? '#06b6d4' }};
            --grad: linear-gradient(135deg, {{ $systemPrimaryColor ?? '#2563eb' }}, {{ $systemSecondaryColor ?? '#06b6d4' }});
         }

         @media (max-width: 767.98px) {
            body.guest-mobile-shell {
               --sr-mobile-inline: 10px !important;
            }

            body.guest-mobile-shell :is(.container, .container-fluid) {
               padding-left: max(10px, env(safe-area-inset-left, 0px)) !important;
               padding-right: max(10px, env(safe-area-inset-right, 0px)) !important;
            }
         }
      </style>
   </head>
   <body class="guest-shell guest-mobile-shell" data-layout-shell="mobile" data-guest-layout="mobile">
      @include('mobile.partials.viewport-mobile-cookie')
<!-- ======================== LANDING PAGE ======================== -->
      <div id="landing">
         <!-- NAVBAR -->
         <nav id="nbar">
            <div class="container">
               <div class="d-flex align-items-center justify-content-between w-100">
                  <a href="#hero" class="guest-brand d-flex align-items-center gap-2" style="font-size:1.2rem;font-weight:700;color:var(--tx);">
                     <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="{{ $systemName ?? 'SpeakReady AI' }}" class="logo-i" style="background: #ffffff; padding: 0; flex-shrink: 0;">
                     <span class="guest-brand-copy">
                        <span class="guest-brand-name">{{ $systemName ?? 'SpeakReady AI' }}</span>
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
                  <div class="d-none d-xl-flex align-items-center gap-1 mx-auto">
                     <a href="#hero" class="nav-link">Home</a>
                     <a href="#developers" class="nav-link">Developers</a>
                  </div>
                  <div class="d-flex align-items-center gap-2 flex-shrink-0">
                     <a href="{{ route('login', sr_layout_query()) }}" class="boc mobile-topbar-login d-flex align-items-center justify-content-center gap-1" aria-label="Log in">
                     <i class="fa-regular fa-user fa-sm" aria-hidden="true"></i><span>Login</span>
                     </a>
                     <button type="button" class="boc d-flex align-items-center justify-content-center" id="thbtn" style="width:38px;height:38px;padding:0;border-radius:10px" aria-label="Toggle theme">
                     <i class="fa-solid fa-sun" id="suni" style="display:none"></i>
                     <i class="fa-solid fa-moon" id="mooni"></i>
                     </button>
                     <a href="{{ route('register', sr_layout_query()) }}" class="bgrd btn px-3 py-2 d-none d-sm-flex align-items-center gap-1 guest-nav-cta">
                     Get Started <i class="fa-solid fa-arrow-right fa-sm"></i>
                     </a>
                     <button class="boc d-xl-none d-flex align-items-center justify-content-center" id="mbtog" style="width:38px;height:38px;padding:0;border-radius:10px" type="button" data-ucp-open aria-label="Open quick navigation" aria-haspopup="dialog" aria-controls="userCommandPalette" aria-expanded="false">
                     <i class="fa-solid fa-bars" aria-hidden="true"></i>
                     </button>
                  </div>
               </div>
            </div>
         </nav>
         @include('mobile.partials.user-command-palette', ['guestQuickNavigation' => true])

         <!-- HERO -->
         <section id="hero" aria-labelledby="hero-title">
            <div class="aur aur-a" style="top:-80px;left:-120px"></div>
            <div class="aur aur-b" style="top:180px;right:-180px"></div>
            <div class="aur aur-a" style="bottom:-80px;left:45%;transform:translateX(-50%);opacity:.4"></div>
             <div class="container position-relative" style="z-index:2">
                <div class="text-center mt-3 pt-3 afu" style="animation-delay:.05s">
                    <span class="hbadge">
                       <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                       <span>AI-Based Interview Practice System</span>
                    </span>
                </div>
                <div class="row align-items-center justify-content-center mt-4">
                  <div class="col-lg-7 col-md-10 text-center hero-home-copy">
                     <h1 id="hero-title" class="h1 afu" style="animation-delay:.12s"><span class="hero-title-line">Practice Smarter.</span><br><span class="gt">Interview Better.</span></h1>
                     <p class="hero-home-copy-text mx-auto afu" style="animation-delay:.2s">Practice realistic job interviews with AI-powered coaching, camera detection, and evidence-based feedback.</p>
                     <div class="hero-cta-row d-flex align-items-center justify-content-center gap-3 flex-wrap afu" style="animation-delay:.28s">
                        <button type="button" class="boc btn px-4 py-3 fs-6 hero-install-btn hero-secondary-cta" id="heroInstallBtn"><i class="fa-solid fa-download me-2" aria-hidden="true"></i>Install App</button>
                        <a href="#about" class="boc btn px-4 py-3 fs-6 hero-learn-btn hero-secondary-cta hero-how-it-works-cta">How It Works?</a>
                     </div>
                     <div class="hero-trust-row" role="list" aria-label="What you can do with SpeakReady AI">
                        <span role="listitem"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Guided setup</span>
                        <span role="listitem"><i class="fa-solid fa-microphone" aria-hidden="true"></i>Text or voice practice</span>
                        <span role="listitem"><i class="fa-solid fa-chart-line" aria-hidden="true"></i>Actionable feedback</span>
                     </div>
                    </div>
                 </div>

                @php
                   $heroVideo = asset('videos/speakready-ai-15s-product-ad.mp4').'?v=20261102';
                @endphp
                <div id="demo-preview" class="mobile-hero-preview-row mt-4 mb-3 afu" style="animation-delay:.48s">
                  <figure class="mobile-hero-image-frame mb-0" aria-label="SpeakReady AI mobile product overview video">
                     <div class="hero-video-showcase">
                        <div class="hero-video-kicker">
                           <span class="hero-video-kicker-title"><i class="fa-solid fa-play" aria-hidden="true"></i> See it in action</span>
                           <span class="hero-video-kicker-meta">15-sec tour</span>
                        </div>
                        <div class="hero-video-shell">
                           <div class="hero-video-chrome" aria-hidden="true">
                              <span class="hero-video-chrome-dots"><i></i><i></i><i></i></span>
                              <span class="hero-video-chrome-label">speakreadyai.online</span>
                           </div>
                           <video
                              class="mobile-hero-image mobile-hero-video"
                              width="1920"
                              height="1080"
                              autoplay
                              muted
                              loop
                              playsinline
                              preload="metadata"
                              aria-label="SpeakReady AI mobile product overview"
                           >
                              <source src="{{ $heroVideo }}" type="video/mp4">
                           </video>
                        </div>
                        <p class="hero-video-caption">Practice interviews. Get real-time coaching. Improve with every session.</p>
                        <div class="hero-benefit-row" role="list" aria-label="SpeakReady AI benefits">
                           <span class="hero-benefit-item" role="listitem"><i class="fa-solid fa-briefcase" aria-hidden="true"></i><span>Role-focused practice</span></span>
                           <span class="hero-benefit-item" role="listitem"><i class="fa-solid fa-comments" aria-hidden="true"></i><span>Real-time feedback</span></span>
                           <span class="hero-benefit-item" role="listitem"><i class="fa-solid fa-chart-line" aria-hidden="true"></i><span>Evidence-based coaching</span></span>
                        </div>
                     </div>
                  </figure>
                </div>
             </div>
         </section>
         <!-- ABOUT THE SYSTEM -->
         <section id="about" class="sp position-relative" style="background:var(--bg2)">
            <div class="container position-relative" style="z-index:1">
               <div class="landing-section-heading mb-5 rv">
                  <span class="slbl">About the System</span>
                  <h2 class="stitle">Built for focused <span class="gt">job interview practice</span></h2>
               </div>
               <div class="row justify-content-center g-5">
                  <div class="col-12 mx-auto rv about-system-content">
                     <div class="about-system-panel">
                        <div class="about-system-panel-head">
                           <div class="about-system-intro">
                              <div class="about-system-kicker"><span class="about-system-kicker-icon" aria-hidden="true"><i class="fa-solid fa-sparkles"></i></span><span>Structured practice. Clear next steps.</span></div>
                              <p class="about-system-copy">SpeakReady AI guides users through a complete job-interview practice flow: choose the Job Interviews scenario, select a target position, set the interview structure, choose support controls, answer by text, voice, or hybrid mode, and review evidence-based feedback. Coaching On and optional Camera Detection support practice, while Real Interview Mode keeps the session quieter and readiness scoring focused on answer quality.</p>
                              <div class="about-system-proof-row" role="list" aria-label="Practice options">
                                 <span role="listitem"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Role-specific prompts</span>
                                 <span role="listitem"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Text, voice, or hybrid</span>
                                 <span role="listitem"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Review after every session</span>
                              </div>
                           </div>
                        </div>

                        <div class="about-system-capabilities" role="list" aria-label="Core system capabilities">
                           <article class="about-feature-card" role="listitem">
                              <span class="about-feature-icon" aria-hidden="true"><i class="fa-solid fa-briefcase"></i></span>
                              <div>
                                 <h4>Role-focused setup</h4>
                                 <p>Choose a target position and interview structure before practice begins.</p>
                              </div>
                           </article>
                           <article class="about-feature-card" role="listitem">
                              <span class="about-feature-icon" aria-hidden="true"><i class="fa-solid fa-sliders"></i></span>
                              <div>
                                 <h4>Flexible response modes</h4>
                                 <p>Practice by text, voice, or hybrid mode with support controls that fit your goal.</p>
                              </div>
                           </article>
                           <article class="about-feature-card" role="listitem">
                              <span class="about-feature-icon" aria-hidden="true"><i class="fa-solid fa-chart-line"></i></span>
                              <div>
                                 <h4>Evidence-based review</h4>
                                 <p>Use readiness signals and feedback to choose a clear focus for your next session.</p>
                              </div>
                           </article>
                        </div>

                        <div class="about-system-audience">
                           <div class="about-system-audience-heading">
                              <h4><i class="fa-solid fa-users" aria-hidden="true"></i> Target users</h4>
                              <p>Whether you are starting out or changing direction, each session gives you a practical place to prepare.</p>
                           </div>
                           <div class="target-users-grid" role="list" aria-label="People who can use SpeakReady AI">
                              <span class="ftag" role="listitem"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i>Students</span>
                              <span class="ftag" role="listitem"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>Fresh Graduates</span>
                              <span class="ftag" role="listitem"><i class="fa-solid fa-briefcase" aria-hidden="true"></i>Job Seekers</span>
                              <span class="ftag" role="listitem"><i class="fa-solid fa-rotate" aria-hidden="true"></i>Career Shifters</span>
                              <span class="ftag" role="listitem"><i class="fa-solid fa-user-check" aria-hidden="true"></i>First-time Applicants</span>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- HOW IT WORKS -->
         <section id="how" class="sp" style="background:var(--bg3)">
            <div class="container">
               <div class="landing-section-heading mb-5 rv">
                  <span class="slbl">How It Works</span>
                  <h2 class="stitle">From setup to <span class="gt">stronger answers</span></h2>
               </div>

               <div class="swiper landingHowSwiper landing-auto-carousel how-auto-carousel" aria-label="How it works carousel">
                  <div class="row g-4 justify-content-center swiper-wrapper">
                  <div class="col-md-4 col-sm-6 rv swiper-slide">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">1</div>
                        <h3 class="fs-5 fw-semibold mb-2">Open Interview Setup</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Start from your workspace and open the guided setup for a job-interview practice session.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">2</div>
                        <h3 class="fs-5 fw-semibold mb-2">Pick Scenario And Role</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Select Job Interviews, then choose a target position so prompts match your hiring goal.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">3</div>
                        <h3 class="fs-5 fw-semibold mb-2">Set Interview Structure</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Choose difficulty, question count, timer, question types, and response mode before starting.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">4</div>
                        <h3 class="fs-5 fw-semibold mb-2">Select Support Controls</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Set Camera Detection, then choose Coaching On for help or Real Interview Mode for quiet practice.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.4s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">5</div>
                        <h3 class="fs-5 fw-semibold mb-2">Practice The Session</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Answer each prompt by text, voice, or hybrid input while the system records your responses.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv swiper-slide" style="transition-delay:.5s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">6</div>
                        <h3 class="fs-5 fw-semibold mb-2">Review Next Steps</h3>
                         <p style="font-size:.875rem;color:var(--tx2)">Use evidence feedback, reports, modules, challenges, and trends to plan your next practice.</p>
                     </div>
                  </div>
                  </div>
                  <div class="landing-carousel-control-bar">
                     <div class="swiper-pagination landing-carousel-pagination landing-how-pagination"></div>
                     <button type="button" class="landing-carousel-autoplay-toggle landing-how-autoplay-toggle" aria-label="Pause how it works carousel" data-pause-label="Pause how it works carousel" data-play-label="Play how it works carousel">
                        <i class="fa-solid fa-pause" aria-hidden="true"></i>
                     </button>
                  </div>
                  <button type="button" class="swiper-button-next landing-carousel-next landing-how-next" aria-label="Next how it works step"></button>
                  <button type="button" class="swiper-button-prev landing-carousel-prev landing-how-prev" aria-label="Previous how it works step"></button>
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
                        <img src="{{ asset('img/dev1.png') }}" alt="Jonh Rogiel M. Tumanda" class="developer-photo img-fluid rounded-circle mb-3" width="1024" height="682" loading="lazy" decoding="async" style="border: 4px solid var(--pur);">
                        <h6 class="fw-bold mb-1">Jonh Rogiel M. Tumanda</h6>
                        <p class="developer-role" style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Lead Programmer</p>
                        <p class="developer-bio" style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Core Code, Databases, and APIs.</p>
                     </div>
                  </div>
                  <div class="developer-card-wrap rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 developer-card">
                        <img src="{{ asset('img/dev2.png') }}" alt="Karyl G. Gesto" class="developer-photo img-fluid rounded-circle mb-3" width="1024" height="682" loading="lazy" decoding="async" style="border: 4px solid #34d399;">
                        <h6 class="fw-bold mb-1">Karyl G. Gesto</h6>
                        <p class="developer-role" style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Manuscript Editor</p>
                        <p class="developer-bio" style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Technical Writing, Documentation, and Compliance.</p>
                     </div>
                  </div>
                  <div class="developer-card-wrap rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 developer-card">
                        <img src="{{ asset('img/dev3.png') }}" alt="Eva Mae C. Cabilic" class="developer-photo img-fluid rounded-circle mb-3" width="1024" height="682" loading="lazy" decoding="async" style="border: 4px solid #f59e0b;">
                        <h6 class="fw-bold mb-1">Eva Mae C. Cabilic</h6>
                        <p class="developer-role" style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">QA Tester</p>
                        <p class="developer-bio" style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Bug Hunting, Test Cases, and UX Stability.</p>
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
               <div class="row g-5 justify-content-center contact-layout-row">
                  <div class="col-lg-5 rv contact-info-column">
                     <p style="font-size:1.05rem;color:var(--tx2);margin-bottom:30px">Have questions or need support? We're here to help you on your journey to interview success.</p>

                     <div class="d-flex flex-column gap-4">
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-envelope" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Email Address</h5>
                                 <a href="mailto:{{ config('mail.contact_to') }}" class="mb-0 d-inline-block" style="color:var(--tx2);font-size:0.9rem;">{{ config('mail.contact_to') }}</a>
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
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">San Ramon, Bontoc, Southern Leyte - SLSU Bontoc Campus</p>
                             </div>
                         </div>
                     </div>
                  </div>
                  <div class="col-lg-5 rv contact-form-column" style="transition-delay:.1s">
                     <div class="gc p-4 p-md-5 h-100 contact-form-panel">
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
                        <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="{{ $systemName ?? 'SpeakReady AI' }}" class="logo-i footer-logo">
                        <span>{{ $systemName ?? 'SpeakReady AI' }}</span>
                     </a>
                     <p class="footer-copy">{{ $systemDescription ?? 'Your interview practice system for AI mock interviews, learning modules, and progress reports.' }}</p>
                  </div>
                  <nav class="footer-nav-grid" aria-label="Footer navigation">
                     <div>
                        <h5 class="footer-heading">Company</h5>
                        <ul class="list-unstyled footer-links">
                           <li><a href="#hero">Home</a></li>
                           <li><a href="#about">About</a></li>
                           <li><a href="#developers">Developers</a></li>
                        </ul>
                     </div>
                     <div>
                        <h5 class="footer-heading">Platform</h5>
                        <ul class="list-unstyled footer-links">
                           <li><a href="{{ route('login', sr_layout_query()) }}">Log In</a></li>
                           <li><a href="{{ route('register', sr_layout_query()) }}">Register</a></li>
                           <li><a href="#contact">Contact</a></li>
                           <li><a href="{{ route('legal.privacy') }}">Privacy Policy</a></li>
                           <li><a href="{{ route('legal.terms') }}">Terms of Service</a></li>
                        </ul>
                     </div>
                  </nav>
                  <div class="footer-action">
                     <div>
                        <h5 class="footer-heading">Stay Updated</h5>
                        <p>Get practice tips, system updates, module releases, and readiness reminders in one clean digest.</p>
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
                         <a href="mailto:{{ config('mail.contact_to') }}" class="footer-social-link" title="Email {{ $systemName ?? 'SpeakReady AI' }}" aria-label="Email {{ $systemName ?? 'SpeakReady AI' }}"><i class="fa-solid fa-envelope" aria-hidden="true"></i></a>
                         <a href="tel:{{ preg_replace('/[^0-9+]/', '', $systemContactNumber ?? '') ?: '09066544727' }}" class="footer-social-link" title="Call {{ $systemName ?? 'SpeakReady AI' }}" aria-label="Call {{ $systemName ?? 'SpeakReady AI' }}"><i class="fa-solid fa-phone" aria-hidden="true"></i></a>
                         <a href="https://www.google.com/maps/search/?api=1&query=San%20Ramon%2C%20Bontoc%2C%20Southern%20Leyte%20SLSU%20Bontoc%20Campus" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="View location" aria-label="View location"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></a>
                     </div>
                  </div>
               </div>
               <div class="footer-bottom">
                  <p>{{ html_entity_decode($systemFooter ?? '&copy; '.date('Y').' SpeakReady AI. All rights reserved.') }}</p>
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



      <!-- ===== PWA INSTALL PROMPT ===== -->
      <div id="pwa-install-prompt" role="dialog" aria-labelledby="pwaPromptTitle" aria-describedby="pwaPromptCopy">
         <div class="pwa-install-dialog">
            <h5 id="pwaPromptTitle">Install SpeakReady AI</h5>
            <p id="pwaPromptCopy">Do you want to install this app for a better and faster experience?</p>
            <div class="pwa-btn-wrap">
               <button type="button" id="pwa-btn-no" class="pwa-btn-no">No</button>
               <button type="button" id="pwa-btn-yes" class="pwa-btn-yes">Yes</button>
            </div>
         </div>
      </div>

      <button type="button" id="backToTopBtn" class="back-to-top-btn" aria-label="Back to top. Drag to move." title="Drag or tap to go back to top">
         <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
      </button>


<!-- ======================== SCRIPTS ======================== -->
      <!-- Bootstrap 5 -->
      <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
      <script src="{{ asset('js/ui-notice.js?v=1') }}"></script>
      @include('mobile.partials.flash-modal', ['includeValidationErrors' => false])
      <!-- Swiper -->
      <script src="{{ asset('js/swiper-bundle.min.js') }}"></script>

      <script src="{{ asset('js/guest.js?v=3') }}"></script>
      <script>
         document.addEventListener('DOMContentLoaded', function() {
             if(typeof Swiper !== 'undefined') {
                 const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

                 document.querySelectorAll(".landingHowSwiper").forEach(function(carouselEl) {
                     const landingSwiper = new Swiper(carouselEl, {
                         slidesPerView: "auto",
                         centeredSlides: true,
                         spaceBetween: 16,
                         speed: 180,
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
                             delay: 2200,
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
               navigator.serviceWorker.register('/sw.js?v=46').then(function(registration) {
                  if (!registration) return;
                  console.log('ServiceWorker registration successful with scope: ', registration.scope || '/');
               }, function(err) {
                  console.log('ServiceWorker registration failed: ', err);
               });
            });
         }

         // PWA Install Prompt Logic
         let deferredPrompt;
         const PWA_APP_INSTALLED_KEY = 'pwa_app_installed';
         const PWA_PROMPT_DISMISSED_KEY = 'pwa_prompt_dismissed';
         const PWA_PROMPT_AUTO_SHOWN_KEY = 'pwa_prompt_auto_shown';
         const PWA_PROMPT_LAST_SHOWN_KEY = 'pwa_prompt_last_shown_at';

         function isPwaAlreadyInstalled() {
            return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               localStorage.getItem(PWA_APP_INSTALLED_KEY) === 'true';
         }

         async function detectPwaAlreadyInstalled() {
            let isInstalled = isPwaAlreadyInstalled();

            if (!isInstalled && 'getInstalledRelatedApps' in navigator) {
               try {
                  const relatedApps = await navigator.getInstalledRelatedApps();
                  isInstalled = relatedApps.length > 0;
               } catch (error) {
                  isInstalled = isPwaAlreadyInstalled();
               }
            }

            if (isInstalled) {
               localStorage.setItem(PWA_APP_INSTALLED_KEY, 'true');
               document.getElementById('pwa-install-prompt')?.style.setProperty('display', 'none');
            }

            return isInstalled;
         }

         function shouldAutoShowPwaInstallPrompt() {
            if (
               isPwaAlreadyInstalled() ||
               localStorage.getItem(PWA_PROMPT_DISMISSED_KEY) ||
               localStorage.getItem(PWA_PROMPT_AUTO_SHOWN_KEY) ||
               localStorage.getItem(PWA_PROMPT_LAST_SHOWN_KEY)
            ) {
               return false;
            }

            return true;
         }

         function rememberPwaInstallPromptShown() {
            localStorage.setItem(PWA_PROMPT_AUTO_SHOWN_KEY, 'true');
            localStorage.setItem(PWA_PROMPT_LAST_SHOWN_KEY, String(Date.now()));
         }

         async function updateInstallButtonState() {
            const installButton = document.getElementById('heroInstallBtn');
            if (!installButton) return;

            const isInstalled = await detectPwaAlreadyInstalled();

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

         window.addEventListener('beforeinstallprompt', async (e) => {
            e.preventDefault();
            if (await detectPwaAlreadyInstalled()) {
               deferredPrompt = null;
               updateInstallButtonState();
               return;
            }

            deferredPrompt = e;
            localStorage.removeItem(PWA_APP_INSTALLED_KEY);
            updateInstallButtonState();
            if (shouldAutoShowPwaInstallPrompt()) {
               queuePwaInstallPrompt();
            }
         });

         window.addEventListener('appinstalled', () => {
            localStorage.setItem(PWA_APP_INSTALLED_KEY, 'true');
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
            if (!prompt || !shouldAutoShowPwaInstallPrompt()) return;
            rememberPwaInstallPromptShown();

            if (!isPwaAlreadyInstalled()) {
               showPwaInstallMessage('Install SpeakReady AI', 'Do you want to install this app for a better and faster experience?', true);
            }
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
                  localStorage.setItem(PWA_APP_INSTALLED_KEY, 'true');
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
            if (allowInstall && isPwaAlreadyInstalled()) return;

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
               localStorage.setItem(PWA_PROMPT_DISMISSED_KEY, 'true');
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
                  document.getElementById('demo-preview')
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
                  behavior: 'auto'
               });
            });
         });
      </script>

      @include('mobile.partials.page-transition')
   </body>
</html>
