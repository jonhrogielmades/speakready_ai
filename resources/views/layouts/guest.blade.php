<!DOCTYPE html>
<html lang="en" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
      <meta name="theme-color" content="#ffffff">
      <title>SpeakReady AI - Practice Smarter. Interview Better.</title>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="manifest" href="{{ asset('manifest.json') }}">
      <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
      <!-- Bootstrap 5.3 -->
      <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet"/>
      <!-- AOS Animate on Scroll -->
      <link href="{{ asset('css/aos.css') }}" rel="stylesheet"/>
      <!-- Swiper CSS -->
      <link href="{{ asset('css/swiper-bundle.min.css') }}" rel="stylesheet"/>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
      <!-- all min css -->
      <link rel="stylesheet" href="{{ asset('css/all.min.css') }}"/>
      <!-- magnific CSS -->
      <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}"/>
      <!-- Style CSS -->
      <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
      <script>
         if (localStorage.getItem('theme') === 'light') {
             document.documentElement.classList.add('lm');
         }
      </script>
      <style>
         .feature-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
            border-color: var(--pur);
         }
         .hnum {
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(139, 92, 246, 0.15);
            color: var(--pur); font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px; font-size: 1.2rem;
         }
         
         /* --- PWA Install Prompt --- */
         #pwa-install-prompt {
            display: none; position: fixed;
            bottom: 20px;
            left: 16px; right: 16px; z-index: 9999;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid #e5e7eb; border-radius: 16px;
            padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center; animation: mobFadeIn 0.3s ease;
         }
         html:not(.lm) #pwa-install-prompt { background: rgba(8, 8, 15, 0.95); box-shadow: 0 10px 30px rgba(0,0,0,0.5); border-color: #333; }
         #pwa-install-prompt h5 { color: #111; font-weight: 700; margin-bottom: 8px; font-size: 1.1rem; }
         html:not(.lm) #pwa-install-prompt h5 { color: #fff; }
         #pwa-install-prompt p { color: #555; font-size: 0.85rem; margin-bottom: 16px; }
         html:not(.lm) #pwa-install-prompt p { color: #aaa; }
         .pwa-btn-wrap { display: flex; gap: 12px; justify-content: center; }
         .pwa-btn-no { flex: 1; padding: 10px; border-radius: 10px; border: 1px solid #ccc; background: transparent; color: #333; font-weight: 600; cursor: pointer; }
         html:not(.lm) .pwa-btn-no { border-color: #444; color: #fff; }
         .pwa-btn-yes { flex: 1; padding: 10px; border-radius: 10px; border: none; background: var(--pur, #8b5cf6); color: #fff; font-weight: 600; cursor: pointer; }
         
         @keyframes mobFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
         }
      </style>
   </head>
   <body>
<!-- ======================== LANDING PAGE ======================== -->
      <div id="landing">
         <!-- NAVBAR -->
         <nav id="nbar">
            <div class="container">
               <div class="d-flex align-items-center justify-content-between w-100">
                  <a href="#" class="d-flex align-items-center gap-2 text-truncate" style="font-size:1.2rem;font-weight:700;color:var(--tx); max-width: calc(100vw - 120px);">
                     <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: transparent; padding: 0; flex-shrink: 0;">
                     <span class="text-truncate">SpeakReady AI</span>
                  </a>
                  <div class="d-none d-lg-flex align-items-center gap-1 mx-auto">
                     <a href="#" class="nav-link">Home</a>
                     <a href="#features" class="nav-link">Features</a>
                     <a href="#how" class="nav-link">How It Works</a>
                     <a href="#benefits" class="nav-link">Benefits</a>
                     <a href="#developers" class="nav-link">Developers</a>
                     <a href="#faq" class="nav-link">FAQ</a>
                     <a href="#contact" class="nav-link">Contact Us</a>
                  </div>
                  <div class="d-flex align-items-center gap-2 flex-shrink-0">
                     <button class="boc d-flex align-items-center justify-content-center" id="thbtn" style="width:38px;height:38px;padding:0;border-radius:12px" aria-label="Toggle theme">
                     <i class="fa-solid fa-sun" id="suni" style="display:none"></i>
                     <i class="fa-solid fa-moon" id="mooni"></i>
                     </button>
                     <button class="boc px-3 py-2 d-none d-sm-flex align-items-center gap-1" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('login')">
                     <i class="fa-regular fa-user fa-sm"></i> Login
                     </button>
                     <button class="bgrd btn px-3 py-2 d-none d-sm-flex align-items-center gap-1" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">
                     Register <i class="fa-solid fa-arrow-right fa-sm"></i>
                     </button>
                     <button class="boc d-lg-none px-2 py-2" id="mbtog" style="border-radius:10px">
                     <i class="fa-solid fa-bars" id="barIcon"></i>
                     <i class="fa-solid fa-xmark" id="xIcon" style="display:none"></i>
                     </button>
                  </div>
               </div>
            </div>
         </nav>
         <div id="mbmenu">
            <a href="#" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">Home</a>
            <a href="#features" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">Features</a>
            <a href="#how" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">How It Works</a>
            <a href="#benefits" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">Benefits</a>
            <a href="#developers" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">Developers</a>
            <a href="#faq" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">FAQ</a>
            <a href="#contact" class="nav-link d-block py-3">Contact Us</a>
            <div class="d-flex gap-2 mt-3">
               <button class="boc flex-fill py-2 btn" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('login')">Login</button>
               <button class="bgrd flex-fill py-2 btn" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">Register</button>
            </div>
         </div>

         <!-- HERO -->
         <section id="hero">
            <div class="aur aur-a" style="top:-80px;left:-120px"></div>
            <div class="aur aur-b" style="top:180px;right:-180px"></div>
            <div class="aur aur-a" style="bottom:-80px;left:45%;transform:translateX(-50%);opacity:.4"></div>
             <div class="container position-relative" style="z-index:2">
                <div class="text-center mt-3 pt-3 afu" style="animation-delay:.05s">
                    <span class="hbadge">
                 AI-Powered Learning | Real-Time Feedback | Interactive Training
                    </span>
                </div>
                <div class="row align-items-center mt-4">
                  <div class="col-lg-5 col-md-6 mb-4 mb-lg-0 text-center position-relative order-1 order-lg-2">
                     <style>
                        .mic-3d-anim {
                           animation: float3d 4s ease-in-out infinite;
                           filter: drop-shadow(0 20px 30px rgba(0,0,0,0.2));
                           transform-style: preserve-3d;
                           transition: transform 0.5s ease;
                        }
                        .mic-3d-anim:hover {
                           transform: scale(1.05) rotateY(10deg) rotateX(5deg);
                        }
                        @keyframes float3d {
                           0% { transform: translateY(0px) rotateY(0deg); }
                           50% { transform: translateY(-15px) rotateY(5deg); }
                           100% { transform: translateY(0px) rotateY(0deg); }
                        }
                     </style>
                     <img src="{{ asset('img/hero_boy.png') }}" class="img-fluid mic-3d-anim afu" alt="SpeakReady AI Interview Practice" style="max-height: 450px; animation-delay: .1s; mix-blend-mode: multiply;">
                  </div>
                  <div class="col-lg-7 col-md-6 text-center order-2 order-lg-1">
                     <h1 class="h1 afu" style="animation-delay:.12s">Practice Smarter.<br><span class="gt">Interview Better.</span></h1>
                     <p class="mx-auto afu" style="max-width:580px;font-size:clamp(.95rem,1.8vw,1.2rem);color:var(--tx2);margin-bottom:36px;animation-delay:.2s">SpeakReady AI offers simulated mock interviews, personalized feedback, and comprehensive coaching to help you land your dream opportunity.</p>
                     <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap afu" style="animation-delay:.28s">
                        <button class="bgrd btn px-4 py-3 fs-6" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">Start Practicing</button>
                        <button class="boc btn px-4 py-3 fs-6" id="heroInstallBtn"><i class="fa-solid fa-download me-2" style="color:var(--pur)"></i>Install App</button>
                        <a href="#features" class="boc btn px-4 py-3 fs-6">Learn More</a>
                     </div>
                     
                   </div>
                </div>
                
                <div class="mt-3 mb-3 afu text-center" style="animation-delay:.4s">
                   <p style="font-size:.71rem;color:var(--tx3);text-transform:uppercase;letter-spacing:.12em;margin-bottom:14px">Featured Technologies</p>
                   <style>
                       .tech-icons a { color: inherit; text-decoration: none; display: flex; align-items: center; transition: all 0.2s ease; }
                       .tech-icons a:hover { transform: translateY(-3px) scale(1.1); color: var(--pur); }
                   </style>
                   <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap tech-icons" style="color:var(--tx2); font-size:1.5rem;">
                      <a href="https://laravel.com" target="_blank" rel="noopener noreferrer" title="Laravel"><i class="fa-brands fa-laravel"></i></a>
                      <a href="https://php.net" target="_blank" rel="noopener noreferrer" title="PHP"><i class="fa-brands fa-php"></i></a>
                      <a href="https://www.mysql.com/" target="_blank" rel="noopener noreferrer" title="MySQL"><i class="fa-solid fa-database"></i></a>
                      @php
                          $primaryAi = \App\Models\AiProvider::where('is_primary', true)->first() ?? \App\Models\AiProvider::where('status', 'active')->first();
                          $slug = 'openai';
                          $title = 'OpenAI';
                          $link = 'https://openai.com';
                          if ($primaryAi) {
                              $name = strtolower($primaryAi->name);
                              if (str_contains($name, 'openai')) { $slug = 'openai'; $title = 'OpenAI'; $link = 'https://openai.com'; }
                              elseif (str_contains($name, 'gemini')) { $slug = 'googlegemini'; $title = 'Gemini'; $link = 'https://deepmind.google/technologies/gemini/'; }
                              elseif (str_contains($name, 'cohere')) { $slug = 'cohere'; $title = 'Cohere'; $link = 'https://cohere.com'; }
                              elseif (str_contains($name, 'anthropic')) { $slug = 'anthropic'; $title = 'Anthropic'; $link = 'https://anthropic.com'; }
                              elseif (str_contains($name, 'groq')) { $slug = 'groq'; $title = 'Groq'; $link = 'https://groq.com'; }
                          }
                      @endphp
                      <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" title="{{ $title }}">
                          <i class="fa-solid fa-robot"></i>
                      </a>
                      <a href="https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API" target="_blank" rel="noopener noreferrer" title="Web Speech API"><i class="fa-solid fa-microphone"></i></a>
                   </div>
                </div>
                
                <div class="row justify-content-center mt-3 mb-3">
                  <div class="col-lg-11 adi">
                     <div class="dwrap">
                        <div class="dtbar"><span class="dd" style="background:#ff5f57"></span><span class="dd" style="background:#ffbd2e"></span><span class="dd" style="background:#28c840"></span><span class="ms-auto me-auto" style="font-size:.76rem;color:var(--tx3)"></span></div>
                        <div class="dgrid">
                           <div class="dside">
                              <div style="font-size:.64rem;text-transform:uppercase;letter-spacing:.1em;color:var(--tx3);padding:0 10px 10px;font-weight:700">Interview Hub</div>
                              <button class="dsi on"><i class="fa-solid fa-chart-pie"></i> Analytics</button>
                              <button class="dsi"><i class="fa-solid fa-video"></i> Mock Sessions</button>
                              <button class="dsi"><i class="fa-solid fa-comment-medical"></i> Feedback</button>
                              <button class="dsi"><i class="fa-solid fa-graduation-cap"></i> Learning Lab</button>
                           </div>
                           <div class="p-3">
                              <div class="row g-2 mb-3">
                                 <div class="col-6 col-sm-3">
                                    <div class="stpill">
                                       <div style="font-size:1.4rem;font-weight:700" class="gt">{{ number_format(\App\Models\Score::avg('overall_readiness_score') ?? 85, 0) }}%</div>
                                       <div style="font-size:.67rem;color:var(--tx3)">Readiness Score</div>
                                       <div style="font-size:.67rem;color:#34d399;font-weight:600"><i class="fa-solid fa-caret-up me-1"></i>Avg</div>
                                    </div>
                                 </div>
                                 <div class="col-6 col-sm-3">
                                    <div class="stpill">
                                       <div style="font-size:1.4rem;font-weight:700">{{ number_format(\App\Models\InterviewSession::count() ?? 12) }}</div>
                                       <div style="font-size:.67rem;color:var(--tx3)">Interviews Done</div>
                                    </div>
                                 </div>
                                 <div class="col-6 col-sm-3">
                                    <div class="stpill">
                                       <div style="font-size:1.4rem;font-weight:700">{{ number_format(\App\Models\Score::avg('clarity_score') ?? 92, 0) }}%</div>
                                       <div style="font-size:.67rem;color:var(--tx3)">Clarity Score</div>
                                    </div>
                                 </div>
                                 <div class="col-6 col-sm-3">
                                    <div class="stpill">
                                       <div style="font-size:1.4rem;font-weight:700">{{ number_format(\App\Models\Score::avg('grammar_score') ?? 95, 0) }}%</div>
                                       <div style="font-size:.67rem;color:var(--tx3)">Grammar Score</div>
                                    </div>
                                 </div>
                              </div>
                              <div class="row g-2">
                                 <div class="col-sm-7">
                                    <div style="background:var(--bg3);border:1px solid var(--bd);border-radius:12px;padding:14px">
                                       <div style="font-size:.73rem;color:var(--tx3);margin-bottom:10px;font-weight:600"><i class="fa-solid fa-chart-bar me-1"></i>Performance Trend</div>
                                       <div style="display:flex;align-items:flex-end;gap:5px;height:76px">
                                          <div class="bbar" style="height:45%"></div>
                                          <div class="bbar" style="height:55%"></div>
                                          <div class="bbar" style="height:60%"></div>
                                          <div class="bbar" style="height:70%"></div>
                                          <div class="bbar" style="height:75%"></div>
                                          <div class="bbar" style="height:85%"></div>
                                          <div class="bbar" style="height:92%"></div>
                                       </div>
                                       <div class="d-flex justify-content-between mt-2" style="font-size:.62rem;color:var(--tx3)"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span></div>
                                    </div>
                                 </div>
                                 <div class="col-sm-5">
                                    <div style="background:var(--bg3);border:1px solid var(--bd);border-radius:12px;padding:12px;height:100%;display:flex;flex-direction:column;gap:8px">
                                       <div style="font-size:.71rem;color:var(--tx3);font-weight:600"><span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--pur);box-shadow:0 0 6px var(--pur);margin-right:6px;animation:bpls 2s infinite"></span>AI Feedback</div>
                                       <div class="cbbl cbus">Tell me about a challenge you faced.</div>
                                       <div class="cbbl cbai"><strong>Great STAR method usage!</strong> Your response was structured perfectly, but try reducing filler words like 'um'.</div>
                                    </div>
                                 </div>
                              </div>
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
               <div class="row align-items-center g-5">
                  <div class="col-lg-6 rv">
                     <span class="slbl">About the System</span>
                     <h2 class="stitle mb-4">Empowering you to <span class="gt">shine in interviews</span></h2>
                     <p style="font-size:1.05rem;color:var(--tx2);margin-bottom:20px;">SpeakReady AI is an advanced, intelligent platform designed to help you prepare for any interview scenario. By simulating realistic interviews, it provides immediate, actionable feedback on your answers, delivery, and body language to solve the problem of interview anxiety and lack of practice.</p>
                     
                     <h4 class="fs-5 mb-3 mt-4">Target Users</h4>
                     <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-user-graduate me-2"></i>Students</span>
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-graduation-cap me-2"></i>Fresh Graduates</span>
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-briefcase me-2"></i>Job Seekers</span>
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-award me-2"></i>Scholarship Applicants</span>
                        <span class="ftag px-3 py-2"><i class="fa-solid fa-university me-2"></i>College Applicants</span>
                     </div>
                  </div>
                  <div class="col-lg-6 rv" style="transition-delay:.1s">
                     <!-- STATISTICS -->
                     <div class="row g-3 text-center">
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:var(--pur);">{{ \App\Models\User::count() }}</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Registered Users</div>
                           </div>
                        </div>
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:#34d399;">{{ \App\Models\InterviewSession::count() }}</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Interview Sessions</div>
                           </div>
                        </div>
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:#f59e0b;">{{ \App\Models\Question::count() }}</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Questions Available</div>
                           </div>
                        </div>
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:#3b82f6;">{{ \App\Models\Feedback::count() }}</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">AI Feedback Generated</div>
                           </div>
                        </div>
                        <div class="col-12 mt-3">
                           <div class="gc p-4">
                              <div class="d-flex justify-content-center align-items-center gap-2">
                                <div class="pnum" style="font-size:3rem; color:var(--pur);"><span class="counter">{{ number_format(\App\Models\Score::avg('overall_readiness_score') ?? 0, 0) }}</span>%</div>
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
               <div class="row g-4">
                  <div class="col-md-3 col-sm-6 rv">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(168,85,247,.15);color:var(--pur)"><i class="fa-solid fa-gauge-high fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Dashboard Overview</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Track all your interview sessions and overall readiness in one place.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.05s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(52,211,153,.15);color:#34d399"><i class="fa-solid fa-microphone-lines fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Mock Interview</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Interactive AI avatar asking real-world questions tailored to you.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(168,85,247,.15);color:#a855f7"><i class="fa-solid fa-ear-listen fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Voice Rehearsal</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Practice your enunciation and pacing with real-time feedback.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.15s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(239,68,68,.15);color:#ef4444"><i class="fa-solid fa-book-open fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Learning Lab</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Access curated resources and tutorials to master any interview.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(245,158,11,.15);color:#f59e0b"><i class="fa-solid fa-robot fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">AI Coach</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Get personalized advice and strategies from your AI mentor.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.25s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(59,130,246,.15);color:#3b82f6"><i class="fa-solid fa-chart-line fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Progress Tracking</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Visual charts tracking your improvement and score over time.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(16,185,129,.15);color:#10b981"><i class="fa-solid fa-clipboard-check fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Feedback Center</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Actionable insights on content, tone, and delivery.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.35s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(234,179,8,.15);color:#eab308"><i class="fa-solid fa-trophy fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Leaderboard</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Compete with peers and track your ranking in the community.</p>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- HOW IT WORKS -->
         <section id="how" class="sp" style="background:var(--bg3)">
            <div class="container">
               <div class="text-center mb-5 rv">
                  <span class="slbl">How It Works</span>
                  <h2 class="stitle">Your journey to <span class="gt">interview mastery</span></h2>
               </div>
               
               <div class="row g-4 justify-content-center">
                  <div class="col-md-4 col-sm-6 rv">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">1</div>
                        <h3 class="fs-5 fw-semibold mb-2">Create an Account</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Join the community and access your personalized dashboard.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">2</div>
                        <h3 class="fs-5 fw-semibold mb-2">Configure Your Setup</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Choose your target role, difficulty, and interview type.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">3</div>
                        <h3 class="fs-5 fw-semibold mb-2">Take a Mock Interview</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Face our interactive AI avatar with real-world questions.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">4</div>
                        <h3 class="fs-5 fw-semibold mb-2">Review AI Feedback</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Get instant, actionable evaluations on your performance.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.4s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">5</div>
                        <h3 class="fs-5 fw-semibold mb-2">Train & Rehearse</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Refine your skills using Voice Rehearsal and the AI Coach.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.5s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">6</div>
                        <h3 class="fs-5 fw-semibold mb-2">Track Your Progress</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Monitor your growth, earn achievements, and climb the leaderboard.</p>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- BENEFITS & INTERVIEW CATEGORIES -->
         <section id="benefits" class="sp position-relative">
            <div class="aur aur-b" style="top:50%;right:-200px;transform:translateY(-50%)"></div>
            <div class="container position-relative" style="z-index:1">
               <div class="row g-5">
                  <div class="col-lg-5 rv">
                     <span class="slbl">Benefits</span>
                     <h2 class="stitle mb-4">Why use <span class="gt">SpeakReady AI?</span></h2>
                     
                     <ul class="list-unstyled d-flex flex-column gap-3 mt-4">
                        <li class="d-flex align-items-start gap-3">
                           <div class="ftico" style="width:40px;height:40px;font-size:1rem;"><i class="fa-solid fa-comments"></i></div>
                           <div><h4 class="fs-6 fw-bold mb-1">Improve Communication Skills</h4><p style="font-size:.85rem;color:var(--tx2)">Enhance how you articulate your thoughts and experiences.</p></div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                           <div class="ftico" style="width:40px;height:40px;font-size:1rem;"><i class="fa-solid fa-thumbs-up"></i></div>
                           <div><h4 class="fs-6 fw-bold mb-1">Build Interview Confidence</h4><p style="font-size:.85rem;color:var(--tx2)">Overcome anxiety through repeated, low-stakes practice.</p></div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                           <div class="ftico" style="width:40px;height:40px;font-size:1rem;"><i class="fa-solid fa-clock"></i></div>
                           <div><h4 class="fs-6 fw-bold mb-1">Practice Anytime, Anywhere</h4><p style="font-size:.85rem;color:var(--tx2)">24/7 access to your personal AI interview coach.</p></div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                           <div class="ftico" style="width:40px;height:40px;font-size:1rem;"><i class="fa-solid fa-bolt"></i></div>
                           <div><h4 class="fs-6 fw-bold mb-1">Receive Personalized Feedback</h4><p style="font-size:.85rem;color:var(--tx2)">Actionable insights specific to your responses.</p></div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                           <div class="ftico" style="width:40px;height:40px;font-size:1rem;"><i class="fa-solid fa-book-open-reader"></i></div>
                           <div><h4 class="fs-6 fw-bold mb-1">Learn Professional Techniques</h4><p style="font-size:.85rem;color:var(--tx2)">Master the STAR method and behavioral strategies.</p></div>
                        </li>
                        <li class="d-flex align-items-start gap-3">
                           <div class="ftico" style="width:40px;height:40px;font-size:1rem;"><i class="fa-solid fa-chart-pie"></i></div>
                           <div><h4 class="fs-6 fw-bold mb-1">Monitor Progress Over Time</h4><p style="font-size:.85rem;color:var(--tx2)">See tangible improvements in your interview readiness.</p></div>
                        </li>
                     </ul>
                  </div>
                  
                  <div class="col-lg-7 rv">
                     <span class="slbl">Interview Categories</span>
                     <h2 class="stitle mb-4">Tailored to your <span class="gt">goals</span></h2>
                     <div class="row g-3">
                        <div class="col-sm-6">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid var(--pur);">
                              <div style="font-size:2rem; margin-bottom:15px; color:var(--pur)"><i class="fa-solid fa-briefcase"></i></div>
                              <h4 class="fs-5 fw-bold">Job Interview</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Practice employment interviews across various industries.</p>
                           </div>
                        </div>
                        <div class="col-sm-6">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #34d399;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#34d399"><i class="fa-solid fa-award"></i></div>
                              <h4 class="fs-5 fw-bold">Scholarship Interview</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Prepare for rigorous scholarship and grant applications.</p>
                           </div>
                        </div>
                        <div class="col-sm-6">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #f59e0b;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#f59e0b"><i class="fa-solid fa-university"></i></div>
                              <h4 class="fs-5 fw-bold">College Admission</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Improve admission interview performance for top universities.</p>
                           </div>
                        </div>
                        <div class="col-sm-6">
                           <div class="gc p-4 h-100 text-center" style="border-top: 4px solid #3b82f6;">
                              <div style="font-size:2rem; margin-bottom:15px; color:#3b82f6"><i class="fa-solid fa-laptop-code"></i></div>
                              <h4 class="fs-5 fw-bold">IT/Programming</h4>
                              <p style="font-size:.85rem;color:var(--tx2)">Practice technical, coding, and system design interviews.</p>
                           </div>
                        </div>
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
               <div class="row g-3 justify-content-center">
                  <div class="col-md-4 rv">
                     <div class="gc p-4 h-100 text-center d-flex flex-column align-items-center justify-content-center">
                        <img src="{{ asset('img/dev1.png') }}" alt="Developer" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid var(--pur);">
                        <h6 class="fw-bold mb-1">Jonh Rogiel M. Tumanda</h6>
                        <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Lead Programmer</p>
                        <p style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Core Code, Databases, and APIs.</p>
                     </div>
                  </div>
                  <div class="col-md-4 rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center d-flex flex-column align-items-center justify-content-center">
                        <img src="{{ asset('img/dev2.png') }}" alt="Developer" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #34d399;">
                        <h6 class="fw-bold mb-1">Karyl G. Gesto</h6>
                        <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">Manuscript Editor</p>
                        <p style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Technical Writing, Documentation, and Compliance.</p>
                     </div>
                  </div>
                  <div class="col-md-4 rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center d-flex flex-column align-items-center justify-content-center">
                        <img src="{{ asset('img/dev3.png') }}" alt="Developer" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #f59e0b;">
                        <h6 class="fw-bold mb-1">Eva Mae C. Cabilic</h6>
                        <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:15px">QA Tester</p>
                        <p style="font-size:.875rem;color:var(--tx2);line-height:1.65;">Bug Hunting, Test Cases, and UX Stability.</p>
                     </div>
                  </div>
               </div>
            </div>
         </section>
         
         <!-- DEMO PREVIEW GALLERY -->
         <section id="demo-preview" class="sp position-relative">
            <div class="aur aur-a" style="top:50%;left:50%;transform:translate(-50%,-50%)"></div>
            <div class="container position-relative" style="z-index:1">
               <div class="text-center mb-5 rv">
                  <span class="slbl">Demo Preview</span>
                  <h2 class="stitle">Inside <span class="gt">SpeakReady AI</span></h2>
               </div>
               
               <div class="row justify-content-center">
                   <div class="col-lg-10">
                       <div class="gc p-2">
                           <div class="swiper demoSwiper rounded" style="overflow:hidden">
                               <div class="swiper-wrapper">
                                   <!-- Slide 1: Overview -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-gauge-high fa-4x mb-4" style="color:var(--pur)"></i>
                                           <h3 class="fs-3 fw-bold">Overview</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Track all your interview sessions and overall readiness.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 2: Mock Interview -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-microphone-lines fa-4x mb-4" style="color:#34d399"></i>
                                           <h3 class="fs-3 fw-bold">Mock Interview</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Interactive AI avatar asking real-world questions.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 3: Voice Rehearsal -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-ear-listen fa-4x mb-4" style="color:#a855f7"></i>
                                           <h3 class="fs-3 fw-bold">Voice Rehearsal</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Practice your enunciation and pacing with real-time feedback.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 4: Learning Lab -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-book-open fa-4x mb-4" style="color:#ef4444"></i>
                                           <h3 class="fs-3 fw-bold">Learning Lab</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Access curated resources and tutorials to master any interview.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 5: AI Coach -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-robot fa-4x mb-4" style="color:#f59e0b"></i>
                                           <h3 class="fs-3 fw-bold">AI Coach</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Get personalized advice and strategies from your AI mentor.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 6: Progress Tracking -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-chart-line fa-4x mb-4" style="color:#3b82f6"></i>
                                           <h3 class="fs-3 fw-bold">Progress Tracking</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Visual charts tracking your improvement over time.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 7: Feedback Center -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-clipboard-check fa-4x mb-4" style="color:#10b981"></i>
                                           <h3 class="fs-3 fw-bold">Feedback Center</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Actionable insights on content, tone, and delivery.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 8: Reports -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-file-invoice fa-4x mb-4" style="color:#6366f1"></i>
                                           <h3 class="fs-3 fw-bold">Reports</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Detailed summaries and exportable reports of your performance.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 9: Leaderboard -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-trophy fa-4x mb-4" style="color:#eab308"></i>
                                           <h3 class="fs-3 fw-bold">Leaderboard</h3>
                                           <p class="mb-0" style="color:var(--tx2); max-width:400px; margin:0 auto;">Compete with peers and track your ranking in the community.</p>
                                       </div>
                                   </div>
                               </div>
                               <div class="swiper-pagination"></div>
                               <div class="swiper-button-next" style="color:var(--pur)"></div>
                               <div class="swiper-button-prev" style="color:var(--pur)"></div>
                           </div>
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
                              <div class="accordion-body">Our AI models analyze your voice, transcript, and body language (if camera is enabled) to evaluate the structure of your answers, clarity, tone, and confidence, providing instant, personalized feedback.</div>
                           </div>
                        </div>
                        <div class="accordion-item">
                           <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f3">Is my data secure?</button></h2>
                           <div id="f3" class="accordion-collapse collapse" data-bs-parent="#faqAcc">
                              <div class="accordion-body">Yes. We prioritize your privacy. All your interview recordings and transcripts are encrypted and strictly used for your personal feedback. They are never shared publicly.</div>
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
               <div class="row g-5 justify-content-center">
                  <div class="col-lg-5 rv">
                     <span class="slbl">Contact Us</span>
                     <h2 class="stitle mb-4">Get in <span class="gt">Touch</span></h2>
                     <p style="font-size:1.05rem;color:var(--tx2);margin-bottom:30px">Have questions or need support? We're here to help you on your journey to interview success.</p>
                     
                     <div class="d-flex flex-column gap-4">
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-envelope" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Email Address</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">capstonespeakreadyai@gmail.com</p>
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
                             <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: #059669; border-radius: 12px; padding: 15px;">
                                 <i class="fa-solid fa-circle-check fs-5 me-3"></i>
                                 <div>
                                     <strong>Success!</strong> {{ session('contact_success') }}
                                 </div>
                                 <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" style="filter: brightness(0.5);"></button>
                             </div>
                         @endif
                         <form action="{{ route('contact.send') }}" method="POST">
                             @csrf
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Name</label>
                                 <input type="text" name="name" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your Full Name" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Email</label>
                                 <input type="email" name="email" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="you@example.com" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Subject</label>
                                 <input type="text" name="subject" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="How can we help?" required>
                             </div>
                             <div class="mb-4">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Message</label>
                                 <textarea name="message" class="form-control" rows="4" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your message here..." required></textarea>
                             </div>
                             <button type="submit" class="bgrd btn w-100 py-3 fw-semibold">Send Message</button>
                         </form>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- FOOTER -->
         <style>
            #foot {
                background: linear-gradient(to bottom, var(--bg2), var(--bg3));
                position: relative;
                overflow: hidden;
            }
            #foot::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0;
                height: 1px;
                background: linear-gradient(90deg, transparent, var(--pur), transparent);
                opacity: 0.3;
            }
            .footer-heading {
                font-size: 0.95rem;
                font-weight: 700;
                color: var(--tx);
                margin-bottom: 1.25rem;
                letter-spacing: 0.5px;
                text-transform: uppercase;
            }
            .footer-links {
                margin: 0;
                padding: 0;
            }
            .footer-links li {
                margin-bottom: 0.75rem;
            }
            .footer-links a {
                color: var(--tx2);
                text-decoration: none;
                font-size: 0.9rem;
                transition: all 0.2s ease;
                display: inline-block;
            }
            .footer-links a:hover {
                color: var(--pur);
                transform: translateX(4px);
            }
            .footer-social-link {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                background: var(--bg);
                border: 1px solid var(--bd);
                border-radius: 50%;
                color: var(--tx2);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                text-decoration: none;
                font-size: 1rem;
            }
            .footer-social-link:hover {
                background: var(--pur);
                color: #fff;
                border-color: var(--pur);
                transform: translateY(-4px);
                box-shadow: 0 6px 15px rgba(124, 58, 237, 0.35);
            }
            .footer-bottom {
                border-top: 1px solid var(--bd);
                padding-top: 1.5rem;
                padding-bottom: 1.5rem;
                margin-top: 2rem;
            }
            .footer-newsletter input:focus {
                border-color: var(--pur) !important;
                box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15) !important;
            }
            .footer-newsletter-btn {
                background: linear-gradient(135deg, var(--pur), #9333ea);
                color: #fff;
                border: none;
                border-radius: 10px;
                transition: all 0.2s;
            }
            .footer-newsletter-btn:hover {
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
                color: #fff;
            }
         </style>
         <footer id="foot">
            <div class="container pt-5">
               <div class="row g-5 mb-5">
                  <div class="col-lg-4 pe-lg-5">
                     <a class="d-flex align-items-center gap-2 mb-3 text-decoration-none" href="#">
                        <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="width:32px; height:32px; background:transparent; padding:0;">
                        <span style="font-size:1.3rem;font-weight:800;letter-spacing:-0.5px;color:var(--tx)">SpeakReady AI</span>
                     </a>
                     <p style="font-size:.95rem;color:var(--tx2);line-height:1.7;margin-bottom:1.75rem">Your personal AI interview coach. Practice smarter, interview better, and secure your dream opportunity with confidence.</p>
                     <div class="d-flex gap-3">
                         <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                         <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="Twitter"><i class="fa-brands fa-twitter"></i></a>
                         <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                         <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="footer-social-link" title="GitHub"><i class="fa-brands fa-github"></i></a>
                     </div>
                  </div>
                  <div class="col-6 col-md-2 col-lg-2">
                     <h5 class="footer-heading">Company</h5>
                     <ul class="list-unstyled footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how">How It Works</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="#faq">FAQ</a></li>
                     </ul>
                  </div>
                  <div class="col-6 col-md-3 col-lg-2">
                     <h5 class="footer-heading">Platform</h5>
                     <ul class="list-unstyled footer-links">
                        <li><a href="#" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('login')">Log In</a></li>
                        <li><a href="#" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">Register</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                     </ul>
                  </div>
                  <div class="col-12 col-md-7 col-lg-4">
                     <h5 class="footer-heading">Stay Updated</h5>
                     <p style="font-size:.9rem;color:var(--tx2);line-height:1.6;margin-bottom:1.25rem">Get the latest interview tips and platform updates directly in your inbox.</p>
                     <form class="footer-newsletter d-flex gap-2" onsubmit="event.preventDefault(); alert('Thanks for subscribing!');">
                         <input type="email" placeholder="Enter your email" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:12px 15px;border-radius:10px;font-size:0.95rem;box-shadow:none;" required>
                         <button type="submit" class="btn footer-newsletter-btn fw-semibold px-3"><i class="fa-solid fa-paper-plane"></i></button>
                     </form>
                  </div>
               </div>
               <div class="footer-bottom d-flex align-items-center justify-content-between flex-wrap gap-3">
                  <p style="font-size:.85rem;color:var(--tx3);margin:0">&copy; {{ date('Y') }} SpeakReady AI. All rights reserved.</p>
                  <div class="d-flex gap-3" style="font-size:.85rem;">
                      <a href="#" style="color:var(--tx3);text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='var(--pur)'" onmouseout="this.style.color='var(--tx3)'">Security</a>
                      <span style="color:var(--bd)">|</span>
                      <a href="#" style="color:var(--tx3);text-decoration:none;transition:color 0.2s;" onmouseover="this.style.color='var(--pur)'" onmouseout="this.style.color='var(--tx3)'">Cookie Preferences</a>
                  </div>
               </div>
            </div>
         </footer>
      </div>
      <!-- /landing -->
      


      <!-- ======================== LOGIN OFFCANVAS ======================== -->
      <div class="offcanvas offcanvas-end" tabindex="-1" id="lofc">
         <div class="offcanvas-header">
            <div class="d-flex align-items-center gap-2">
               <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="width:30px;height:30px;background: transparent; padding: 0;">
               <h5 class="offcanvas-title mb-0">SpeakReady AI</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" style="filter:invert(1)"></button>
         </div>
         <div class="offcanvas-body p-4">
            <div class="tab-switch"><button class="tab-sw-btn on" id="tabLogin" onclick="swTab('login')">Log In</button><button class="tab-sw-btn" id="tabSignup" onclick="swTab('signup')">Register</button></div>
            <!-- Login -->
            <div id="fLogin">
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
                  <label class="olbl"><i class="fa-regular fa-envelope me-1"></i>Email address</label>
                  <input class="oinp" type="email" name="email" id="loginEmail" placeholder="you@example.com" required value="{{ old('email') }}">
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Password</label>
                  <div class="position-relative mb-3">
                     <input class="oinp" type="password" name="password" id="loginPass" placeholder="********" required style="padding-right: 40px; margin-bottom: 0;">
                     <span class="position-absolute top-50 translate-middle-y toggle-password" onclick="togglePasswordVisibility('loginPass', this)" style="right: 15px; cursor: pointer; color: var(--tx3); z-index: 10;">
                        <i class="fa-solid fa-eye-slash"></i>
                     </span>
                  </div>
                  <div class="text-end mb-3" style="margin-top:-8px"><a href="#" style="font-size:.8rem;color:var(--pur)">Forgot password?</a></div>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="loginBtn">Log In <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or continue with</div>
               <a href="{{ route('auth.google') }}" class="oauth" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Continue with Google</a>

               <p class="text-center mt-4" style="font-size:.82rem;color:var(--tx3)">Don't have an account? <a href="#" style="color:var(--pur)" onclick="swTab('signup');return false">Register for free</a></p>
            </div>
            <!-- Sign Up -->
            <div id="fSignup" style="display:none">
               <form action="{{ route('register') }}" method="POST">
                  @csrf
                  @if($errors->any() && old('name'))
                     <div class="err-msg" style="display:block;"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first() }}</span></div>
                  @endif
                  <label class="olbl"><i class="fa-regular fa-user me-1"></i>Full name</label>
                  <input class="oinp" type="text" name="name" id="signupName" placeholder="John Doe" required value="{{ old('name') }}">
                  <label class="olbl"><i class="fa-regular fa-envelope me-1"></i>Email address</label>
                  <input class="oinp" type="email" name="email" id="signupEmail" placeholder="you@example.com" required>
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Password</label>
                  <div class="position-relative mb-3">
                     <input class="oinp" type="password" name="password" id="signupPass" placeholder="Min. 8 characters" required style="padding-right: 40px; margin-bottom: 0;">
                     <span class="position-absolute top-50 translate-middle-y toggle-password" onclick="togglePasswordVisibility('signupPass', this)" style="right: 15px; cursor: pointer; color: var(--tx3); z-index: 10;">
                        <i class="fa-solid fa-eye-slash"></i>
                     </span>
                  </div>
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Confirm Password</label>
                  <div class="position-relative mb-3">
                     <input class="oinp" type="password" name="password_confirmation" id="signupPassConfirm" placeholder="Confirm your password" required style="padding-right: 40px; margin-bottom: 0;">
                     <span class="position-absolute top-50 translate-middle-y toggle-password" onclick="togglePasswordVisibility('signupPassConfirm', this)" style="right: 15px; cursor: pointer; color: var(--tx3); z-index: 10;">
                        <i class="fa-solid fa-eye-slash"></i>
                     </span>
                  </div>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="signupBtn">Create Free Account <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or sign up with</div>
               <a href="{{ route('auth.google') }}" class="oauth" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><i class="fa-brands fa-google me-2" style="color:#EA4335;"></i>Continue with Google</a>

               <p class="text-center mt-3" style="font-size:.76rem;color:var(--tx3)">By signing up, you agree to our <a href="#" style="color:var(--pur)">Terms</a> &amp; <a href="#" style="color:var(--pur)">Privacy Policy</a></p>
            </div>
            <!-- Close Button at Bottom -->
            <div class="text-center mt-5 mb-3">
               <button type="button" class="boc d-inline-flex align-items-center justify-content-center" data-bs-dismiss="offcanvas" style="width: 48px; height: 48px; border-radius: 50%; opacity: 0.8;" aria-label="Close">
                  <i class="fa-solid fa-xmark fs-5"></i>
               </button>
            </div>
         </div>
      </div>
      
      <!-- ===== PWA INSTALL PROMPT ===== -->
      <div id="pwa-install-prompt">
         <h5>Install SpeakReady AI</h5>
         <p>Do you want to install this app for a better and faster experience?</p>
         <div class="pwa-btn-wrap">
            <button id="pwa-btn-no" class="pwa-btn-no">No</button>
            <button id="pwa-btn-yes" class="pwa-btn-yes">Yes</button>
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
      <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
      <script src="{{ asset('js/chart.umd.min.js') }}"></script>
      <!-- Magnific -->
      <script src="{{ asset('js/jquery.magnific-popup.min.js') }}"></script>
      <!-- Counter Up and Waypoints -->
      <script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/Counter-Up/1.0.0/jquery.counterup.min.js"></script>
      
      <script src="{{ asset('js/main.js') }}"></script>
      @if($errors->any())
      <script>
         document.addEventListener('DOMContentLoaded', function() {
            var myOffcanvas = document.getElementById('lofc');
            var bsOffcanvas = new bootstrap.Offcanvas(myOffcanvas);
            bsOffcanvas.show();
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
                 var swiper = new Swiper(".demoSwiper", {
                     slidesPerView: 1,
                     spaceBetween: 30,
                     loop: true,
                     autoplay: {
                         delay: 3000,
                         disableOnInteraction: false,
                     },
                     pagination: {
                         el: ".swiper-pagination",
                         clickable: true,
                     },
                     navigation: {
                         nextEl: ".swiper-button-next",
                         prevEl: ".swiper-button-prev",
                     },
                 });
             }
         });
      </script>
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

         // PWA Install Prompt Logic
         let deferredPrompt;
         window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (!localStorage.getItem('pwa_prompt_dismissed')) {
               document.getElementById('pwa-install-prompt').style.display = 'block';
            }
         });

         async function triggerInstall() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());

            if (isStandalone) {
                alert("This app has already been installed on your device.");
                return;
            }

            if (deferredPrompt) {
               deferredPrompt.prompt();
               const { outcome } = await deferredPrompt.userChoice;
               console.log(`User response to the install prompt: ${outcome}`);
               deferredPrompt = null;
               document.getElementById('pwa-install-prompt').style.display = 'none';
            } else {
               if (isIos) {
                   alert("To install on iOS, tap the 'Share' icon at the bottom of Safari and select 'Add to Home Screen'.");
               } else {
                   alert("This app has already been installed on your device.");
               }
            }
         }

         document.getElementById('pwa-btn-yes')?.addEventListener('click', triggerInstall);
         document.getElementById('heroInstallBtn')?.addEventListener('click', triggerInstall);

         document.getElementById('pwa-btn-no')?.addEventListener('click', () => {
            document.getElementById('pwa-install-prompt').style.display = 'none';
            localStorage.setItem('pwa_prompt_dismissed', 'true');
         });
      </script>

      <!-- LOGIN TRANSITION OVERLAY -->
      <style>
      #loginTransitionOverlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100vw;
          height: 100vh;
          background: var(--bg, #ffffff);
          z-index: 999999;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          opacity: 0;
          visibility: hidden;
          transition: opacity 0.3s ease, visibility 0.3s ease;
      }
      #loginTransitionOverlay.active {
          opacity: 1;
          visibility: visible;
      }
      .logo-loading-wrapper {
          position: relative;
          width: 120px;
          height: 120px;
          margin-bottom: 20px;
          display: flex;
          align-items: center;
          justify-content: center;
      }
      .logo-loading-circle {
          position: absolute;
          width: 100%;
          height: 100%;
          border-radius: 50%;
          border: 4px solid var(--bd, #e2e8f0);
          border-top: 4px solid var(--pur, #7c3aed);
          animation: spin 1s linear infinite;
      }
      .logo-loading-wrapper img {
          width: 70px;
          height: 70px;
          object-fit: contain;
          animation: pulse 1.5s ease-in-out infinite;
      }
      @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
      }
      @keyframes pulse {
          0% { transform: scale(0.9); opacity: 0.8; }
          50% { transform: scale(1.1); opacity: 1; }
          100% { transform: scale(0.9); opacity: 0.8; }
      }
      </style>

      <div id="loginTransitionOverlay">
          <div class="logo-loading-wrapper">
              <div class="logo-loading-circle"></div>
              <img src="{{ asset('img/logo.png') }}" alt="Loading...">
          </div>
          <h4 style="color:var(--tx); font-weight:600; font-size:1.2rem; letter-spacing:0.5px;">Authenticating...</h4>
          <p style="color:var(--tx3); font-size:0.9rem;">Please wait while we log you in</p>
      </div>

      <script>
          function showLoginTransition() {
              const overlay = document.getElementById('loginTransitionOverlay');
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
          });
          
          function togglePasswordVisibility(inputId, btn) {
             const input = document.getElementById(inputId);
             const icon = btn.querySelector('i');
             if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
             } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
             }
          }
      </script>
   </body>
</html>


