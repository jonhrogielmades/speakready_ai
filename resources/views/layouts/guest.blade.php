<!DOCTYPE html>
<html lang="en" id="htmlRoot">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>SpeakReady AI - Practice Smarter. Interview Better.</title>
      <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
      </style>
   </head>
   <body>
<!-- ======================== LANDING PAGE ======================== -->
      <div id="landing">
         <!-- NAVBAR -->
         <nav id="nbar">
            <div class="container">
               <div class="d-flex align-items-center justify-content-between w-100">
                  <a href="#" class="d-flex align-items-center gap-2" style="font-size:1.2rem;font-weight:700;color:var(--tx)">
                     <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: transparent; padding: 0;">
                     <span>SpeakReady AI</span>
                  </a>
                  <div class="d-none d-lg-flex align-items-center gap-1 mx-auto">
                     <a href="#" class="nav-link">Home</a>
                     <a href="#features" class="nav-link">Features</a>
                     <a href="#how" class="nav-link">How It Works</a>
                     <a href="#benefits" class="nav-link">Benefits</a>
                     <a href="#testimonials" class="nav-link">Testimonials</a>
                     <a href="#faq" class="nav-link">FAQ</a>
                     <a href="#contact" class="nav-link">Contact Us</a>
                  </div>
                  <div class="d-flex align-items-center gap-2">
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
            <a href="#testimonials" class="nav-link d-block py-3 border-bottom" style="border-color:var(--bd)!important">Testimonials</a>
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
               <div class="text-center">
                  <div class="afu" style="animation-delay:.05s">
                      <span class="hbadge">
                          <span class="bdot"></span>AI-Powered Learning | Real-Time Feedback | Interactive Training
                      </span>
                  </div>
                  <h1 class="h1 afu" style="animation-delay:.12s">Practice Smarter.<br><span class="gt">Interview Better.</span></h1>
                  <p class="mx-auto afu" style="max-width:580px;font-size:clamp(.95rem,1.8vw,1.2rem);color:var(--tx2);margin-bottom:36px;animation-delay:.2s">SpeakReady AI offers simulated mock interviews, personalized feedback, and comprehensive coaching to help you land your dream opportunity.</p>
                  <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap afu" style="animation-delay:.28s">
                     <button class="bgrd btn px-4 py-3 fs-6" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">Start Practicing</button>
                     <button class="boc btn px-4 py-3 fs-6" data-bs-toggle="modal" data-bs-target="#demoModal"><i class="fa-solid fa-play me-2" style="color:var(--pur)"></i>Live Demo</button>
                     <a href="#features" class="boc btn px-4 py-3 fs-6">Learn More</a>
                  </div>
                  
                  <div class="mt-5 afu" style="animation-delay:.4s">
                     <p style="font-size:.71rem;color:var(--tx3);text-transform:uppercase;letter-spacing:.12em;margin-bottom:14px">Featured Technologies</p>
                     <div class="d-flex align-items-center justify-content-center gap-4 flex-wrap" style="color:var(--tx2); font-size:1.5rem;">
                        <i class="fa-brands fa-laravel" title="Laravel"></i>
                        <i class="fa-brands fa-php" title="PHP"></i>
                        <i class="fa-solid fa-database" title="MySQL"></i>
                        <span style="font-weight:700; font-size:1.2rem;">OpenAI/Gemini AI</span>
                        <i class="fa-solid fa-microphone" title="Web Speech API"></i>
                     </div>
                  </div>
               </div>
               
               <div class="row justify-content-center mt-5">
                  <div class="col-lg-11 adi">
                     <div class="dwrap">
                        <div class="dtbar"><span class="dd" style="background:#ff5f57"></span><span class="dd" style="background:#ffbd2e"></span><span class="dd" style="background:#28c840"></span><span class="ms-auto me-auto" style="font-size:.76rem;color:var(--tx3)">SpeakReady AI Dashboard</span></div>
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
                                       <div style="font-size:1.4rem;font-weight:700" class="gt">85%</div>
                                       <div style="font-size:.67rem;color:var(--tx3)">Readiness Score</div>
                                       <div style="font-size:.67rem;color:#34d399;font-weight:600"><i class="fa-solid fa-caret-up me-1"></i>12%</div>
                                    </div>
                                 </div>
                                 <div class="col-6 col-sm-3">
                                    <div class="stpill">
                                       <div style="font-size:1.4rem;font-weight:700">12</div>
                                       <div style="font-size:.67rem;color:var(--tx3)">Interviews Done</div>
                                    </div>
                                 </div>
                                 <div class="col-6 col-sm-3">
                                    <div class="stpill">
                                       <div style="font-size:1.4rem;font-weight:700">92%</div>
                                       <div style="font-size:.67rem;color:var(--tx3)">Eye Contact</div>
                                    </div>
                                 </div>
                                 <div class="col-6 col-sm-3">
                                    <div class="stpill">
                                       <div style="font-size:1.4rem;font-weight:700">Good</div>
                                       <div style="font-size:.67rem;color:var(--tx3)">Posture Check</div>
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
                              <div class="pnum counter" style="font-size:2.5rem; color:var(--pur);">15000</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Registered Users</div>
                           </div>
                        </div>
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:#34d399;">45000</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Total Interview Sessions</div>
                           </div>
                        </div>
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:#f59e0b;">5000</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">Questions Available</div>
                           </div>
                        </div>
                        <div class="col-6 col-sm-6">
                           <div class="gc p-4 h-100">
                              <div class="pnum counter" style="font-size:2.5rem; color:#3b82f6;">125000</div>
                              <div class="plbl text-uppercase" style="font-size:0.8rem; letter-spacing:1px; margin-top:10px;">AI Feedback Generated</div>
                           </div>
                        </div>
                        <div class="col-12 mt-3">
                           <div class="gc p-4">
                              <div class="d-flex justify-content-center align-items-center gap-2">
                                <div class="pnum" style="font-size:3rem; color:var(--pur);"><span class="counter">98</span>%</div>
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
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(139,92,246,.15);color:var(--pur)"><i class="fa-solid fa-chalkboard-user fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">AI Mock Interview</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Simulated interview sessions tailored to your chosen field.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.05s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(52,211,153,.15);color:#34d399"><i class="fa-solid fa-comment-medical fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">AI Feedback</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Instant evaluation and suggestions after every response.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(245,158,11,.15);color:#f59e0b"><i class="fa-solid fa-user-tie fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">AI Interview Coach</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Personalized interview guidance and strategic advice.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.15s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(59,130,246,.15);color:#3b82f6"><i class="fa-solid fa-star fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">STAR Response Drill</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Behavioral training using the Situation, Task, Action, Result method.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(239,68,68,.15);color:#ef4444"><i class="fa-solid fa-microphone-lines fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Voice Rehearsal</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Speaking practice with speech clarity and tone analysis.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.25s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(168,85,247,.15);color:#a855f7"><i class="fa-solid fa-video fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Camera Presence</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Posture, lighting, and eye contact guidance via webcam analysis.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(14,165,233,.15);color:#0ea5e9"><i class="fa-solid fa-chart-line fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Progress Tracking</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Readiness score and performance monitoring over time.</p>
                     </div>
                  </div>
                  <div class="col-md-3 col-sm-6 rv" style="transition-delay:.35s">
                     <div class="gc p-4 h-100 text-center feature-card">
                        <div class="ftico mx-auto mb-3" style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:rgba(244,63,94,.15);color:#f43f5e"><i class="fa-solid fa-laptop-file fa-lg"></i></div>
                        <h3 class="fs-6 fw-bold mb-2">Learning Lab</h3>
                        <p style="font-size:.85rem;color:var(--tx2)">Extensive tutorials and learning resources for interview prep.</p>
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
                        <p style="font-size:.875rem;color:var(--tx2)">Sign up to access personalized tracking and interview resources.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">2</div>
                        <h3 class="fs-5 fw-semibold mb-2">Choose Category</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Select your focus: Job, Scholarship, College Admission, or IT.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">3</div>
                        <h3 class="fs-5 fw-semibold mb-2">Answer Questions</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Respond to AI-generated questions tailored to your field.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.3s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">4</div>
                        <h3 class="fs-5 fw-semibold mb-2">Receive AI Feedback</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Get instant evaluations on your response structure and tone.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.4s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">5</div>
                        <h3 class="fs-5 fw-semibold mb-2">Improve Through Practice</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Use insights to refine your answers and body language.</p>
                     </div>
                  </div>
                  <div class="col-md-4 col-sm-6 rv" style="transition-delay:.5s">
                     <div class="gc p-4 h-100 text-center position-relative">
                        <div class="hnum">6</div>
                        <h3 class="fs-5 fw-semibold mb-2">Track Progress</h3>
                        <p style="font-size:.875rem;color:var(--tx2)">Monitor your readiness and performance score over time.</p>
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
                           <div class="ftico" style="width:40px;height:40px;font-size:1rem;"><i class="fa-solid fa-shield-halved"></i></div>
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

         <!-- TESTIMONIALS -->
         <section id="testimonials" class="sp" style="background:var(--bg2)">
            <div class="container">
               <div class="text-center mb-5 rv">
                  <span class="slbl">Testimonials</span>
                  <h2 class="stitle">Success <span class="gt">Stories</span></h2>
               </div>
               <div class="row g-3">
                  <div class="col-md-4 rv">
                     <div class="gc p-4 h-100">
                        <div class="d-flex gap-1 mb-3"><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i></div>
                        <p style="font-size:.875rem;color:var(--tx2);line-height:1.65;font-style:italic;margin-bottom:18px">"SpeakReady AI helped me conquer my nervousness. The STAR response drill completely changed how I answer behavioral questions. I just landed my first software engineering job!"</p>
                        <div class="d-flex align-items-center gap-2 mt-auto">
                           <div style="width:40px;height:40px;border-radius:50%;background:var(--pur);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;">AJ</div>
                           <div>
                              <div style="font-size:.88rem;font-weight:600">Alex J.</div>
                              <div style="font-size:.76rem;color:var(--tx3)">Fresh Graduate, IT</div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-4 rv" style="transition-delay:.1s">
                     <div class="gc p-4 h-100">
                        <div class="d-flex gap-1 mb-3"><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i></div>
                        <p style="font-size:.875rem;color:var(--tx2);line-height:1.65;font-style:italic;margin-bottom:18px">"The camera presence check pointed out that I rarely made eye contact. Fixing that alone gave me so much more confidence during my college admission interview."</p>
                        <div class="d-flex align-items-center gap-2 mt-auto">
                           <div style="width:40px;height:40px;border-radius:50%;background:#34d399;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;">MR</div>
                           <div>
                              <div style="font-size:.88rem;font-weight:600">Maria R.</div>
                              <div style="font-size:.76rem;color:var(--tx3)">College Applicant</div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-md-4 rv" style="transition-delay:.2s">
                     <div class="gc p-4 h-100">
                        <div class="d-flex gap-1 mb-3"><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i></div>
                        <p style="font-size:.875rem;color:var(--tx2);line-height:1.65;font-style:italic;margin-bottom:18px">"Getting instant AI feedback on my responses allowed me to iterate quickly. I practiced for my scholarship interview every night and ended up winning it!"</p>
                        <div class="d-flex align-items-center gap-2 mt-auto">
                           <div style="width:40px;height:40px;border-radius:50%;background:#f59e0b;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;">DL</div>
                           <div>
                              <div style="font-size:.88rem;font-weight:600">David L.</div>
                              <div style="font-size:.76rem;color:var(--tx3)">Scholarship Applicant</div>
                           </div>
                        </div>
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
                                   <!-- Slide 1 -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-gauge-high fa-4x mb-4" style="color:var(--pur)"></i>
                                           <h3 class="fs-3 fw-bold">Dashboard Screenshot</h3>
                                           <p class="text-muted" style="max-width:400px">Track all your interview sessions and overall readiness.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 2 -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-video fa-4x mb-4" style="color:#34d399"></i>
                                           <h3 class="fs-3 fw-bold">Interview Session Screenshot</h3>
                                           <p class="text-muted" style="max-width:400px">Interactive AI avatar asking real-world questions.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 3 -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-comment-medical fa-4x mb-4" style="color:#f59e0b"></i>
                                           <h3 class="fs-3 fw-bold">AI Feedback Screenshot</h3>
                                           <p class="text-muted" style="max-width:400px">Actionable insights on content, tone, and delivery.</p>
                                       </div>
                                   </div>
                                   <!-- Slide 4 -->
                                   <div class="swiper-slide text-center">
                                       <div class="p-4" style="background:var(--bg); border-radius:8px; min-height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                                           <i class="fa-solid fa-chart-line fa-4x mb-4" style="color:#3b82f6"></i>
                                           <h3 class="fs-3 fw-bold">Progress Analytics Screenshot</h3>
                                           <p class="text-muted" style="max-width:400px">Visual charts tracking your improvement over time.</p>
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
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">support@speakready.ai</p>
                             </div>
                         </div>
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-phone" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Contact Number</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">+1 (555) 123-4567</p>
                             </div>
                         </div>
                         <div class="d-flex align-items-center gap-3">
                             <div class="ftico" style="width:50px;height:50px;font-size:1.2rem;display:flex;align-items:center;justify-content:center;border-radius:12px;background:var(--bg3);border:1px solid var(--bd)"><i class="fa-solid fa-location-dot" style="color:var(--pur)"></i></div>
                             <div>
                                 <h5 class="mb-1 fs-6 fw-bold">Location</h5>
                                 <p class="mb-0" style="color:var(--tx2);font-size:0.9rem;">123 AI Boulevard, Tech City, 10010</p>
                             </div>
                         </div>
                     </div>
                  </div>
                  <div class="col-lg-5 rv" style="transition-delay:.1s">
                     <div class="gc p-4 p-md-5 h-100">
                         <form action="#" method="POST">
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Name</label>
                                 <input type="text" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your Full Name" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Email</label>
                                 <input type="email" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="you@example.com" required>
                             </div>
                             <div class="mb-3">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Subject</label>
                                 <input type="text" class="form-control" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="How can we help?" required>
                             </div>
                             <div class="mb-4">
                                 <label class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--tx)">Message</label>
                                 <textarea class="form-control" rows="4" style="background:var(--bg);border:1px solid var(--bd);color:var(--tx);padding:10px 15px;" placeholder="Your message here..." required></textarea>
                             </div>
                             <button type="button" class="bgrd btn w-100 py-3 fw-semibold">Send Message</button>
                         </form>
                     </div>
                  </div>
               </div>
            </div>
         </section>

         <!-- FOOTER -->
         <footer id="foot" style="background:var(--bg2)">
            <div class="container pt-5">
               <div class="row g-5 mb-5">
                  <div class="col-lg-4">
                     <a class="d-flex align-items-center gap-2 mb-3" href="#" style="font-size:1.15rem;font-weight:700;color:var(--tx)">
                        <img src="{{ asset('img/logo.png') }}" alt="SpeakReady AI" class="logo-i" style="background: transparent; padding: 0;">
                        SpeakReady AI
                     </a>
                     <p style="font-size:.875rem;color:var(--tx3);line-height:1.65;max-width:280px">Your personal AI interview coach. Practice smarter, interview better, and secure your dream opportunity.</p>
                  </div>
                  <div class="col-6 col-md-3 fcol">
                     <h5>Quick Links</h5>
                     <a href="#">Home</a><a href="#features">Features</a><a href="#about">About</a><a href="#contact">Contact</a>
                  </div>
                  <div class="col-6 col-md-2 fcol">
                     <h5>User Links</h5>
                     <a href="#" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('login')">Login</a>
                     <a href="#" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">Register</a>
                  </div>
                  <div class="col-6 col-md-3 fcol">
                     <h5>Legal Links</h5>
                     <a href="#">Privacy Policy</a><a href="#">Terms and Conditions</a>
                  </div>
               </div>
               <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-4 pb-4" style="border-top:1px solid var(--bd)">
                  <p style="font-size:.8rem;color:var(--tx3);margin:0"> &copy; 2026 SpeakReady AI. All rights reserved.</p>
                  <div class="d-flex gap-2">
                      <a href="#" class="sico" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                      <a href="#" class="sico" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                      <a href="#" class="sico" title="GitHub"><i class="fa-brands fa-github"></i></a>
                  </div>
               </div>
            </div>
         </footer>
      </div>
      <!-- /landing -->
      
      <!-- ======================== LIVE DEMO MODAL (BONUS) ======================== -->
      <div class="modal fade" id="demoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content" style="background:var(--bg);border:1px solid var(--bd);border-radius:16px;">
            <div class="modal-header border-0">
              <h5 class="modal-title fw-bold">Live AI Readiness Checker</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body text-center p-4">
               <i class="fa-solid fa-robot fa-3x mb-3" style="color:var(--pur)"></i>
               <h4 class="mb-3">Test Your Readiness!</h4>
               <p style="color:var(--tx2);font-size:0.9rem;">Answer 3 quick sample questions without registering to see your estimated readiness score.</p>
               
               <div class="gc p-3 mt-4 text-start">
                   <p class="mb-2 fw-bold" style="font-size:0.9rem;"><i class="fa-solid fa-q me-2 text-primary"></i> Question 1:</p>
                   <p style="font-size:0.95rem;">"Tell me about a time you had to overcome a significant challenge."</p>
               </div>
               
               <button class="btn bgrd w-100 py-3 mt-4" data-bs-dismiss="modal" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">Register to Start Demo</button>
            </div>
          </div>
        </div>
      </div>

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
               <form action="{{ route('login') }}" method="POST">
                  @csrf
                  @if($errors->any())
                     <div class="err-msg" style="display:block;"><i class="fa-solid fa-circle-exclamation me-1"></i><span>{{ $errors->first() }}</span></div>
                  @endif
                  <label class="olbl"><i class="fa-regular fa-envelope me-1"></i>Email address</label>
                  <input class="oinp" type="email" name="email" id="loginEmail" placeholder="you@example.com" required value="{{ old('email') }}">
                  <label class="olbl"><i class="fa-solid fa-lock me-1"></i>Password</label>
                  <input class="oinp" type="password" name="password" id="loginPass" placeholder="********" required>
                  <div class="text-end mb-3" style="margin-top:-8px"><a href="#" style="font-size:.8rem;color:var(--pur)">Forgot password?</a></div>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="loginBtn">Log In <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or continue with</div>
               <button type="button" class="oauth" onclick="window.location.href='{{ route('auth.google') }}'"><i class="fa-brands fa-google me-2"></i>Continue with Google</button>

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
                  <input class="oinp" type="password" name="password" id="signupPass" placeholder="Min. 8 characters" required>
                  <button type="submit" class="bgrd btn w-100 py-3 fw-semibold fs-6" id="signupBtn">Create Free Account <i class="fa-solid fa-arrow-right ms-1 fa-sm"></i></button>
               </form>
               <div class="odiv">or sign up with</div>
               <button type="button" class="oauth" onclick="window.location.href='{{ route('auth.google') }}'"><i class="fa-brands fa-google me-2"></i>Continue with Google</button>

               <p class="text-center mt-3" style="font-size:.76rem;color:var(--tx3)">By signing up, you agree to our <a href="#" style="color:var(--pur)">Terms</a> &amp; <a href="#" style="color:var(--pur)">Privacy Policy</a></p>
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
   </body>
</html>
