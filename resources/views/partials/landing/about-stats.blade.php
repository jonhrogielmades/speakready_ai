         <!-- ABOUT THE SYSTEM & SYSTEM STATS -->
         <section id="about" class="sp position-relative" style="background:var(--bg2)">
            <div class="container position-relative" style="z-index:1">
               <div class="landing-section-heading mb-5 rv">
                  <span class="slbl">About the System</span>
                  <h2 class="stitle">Empowering you to <span class="gt">shine in Philippine interviews</span></h2>
               </div>
               <div class="row align-items-center g-5">
                  <div class="col-lg-6 rv">
                     <p style="font-size:1.05rem;color:var(--tx2);margin-bottom:20px;">SpeakReady AI is an advanced, intelligent platform designed to help you prepare for Philippine interview scenarios, including job, BPO, IT, fresh graduate, scholarship, and college admission interviews. It provides immediate, evidence-linked feedback on answer quality and optional, non-scoring delivery coaching to reduce interview anxiety and make practice more focused.</p>
                     
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

