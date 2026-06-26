         <!-- HERO -->
         <section id="hero">
            <div class="aur aur-a" style="top:-80px;left:-120px"></div>
            <div class="aur aur-b" style="top:180px;right:-180px"></div>
            <div class="aur aur-a" style="bottom:-80px;left:45%;transform:translateX(-50%);opacity:.4"></div>
            <div class="container position-relative" style="z-index:2">
               <div class="text-center mt-3 pt-3">
                  <div class="afu" style="animation-delay:.05s">
                      <span class="hbadge">
                   AI-Powered Learning | Real-Time Feedback | Interactive Training
                      </span>
                  </div>
                  <h1 class="h1 afu" style="animation-delay:.12s">Practice Smarter.<br><span class="gt">Interview Better.</span></h1>
                  <p class="mx-auto afu" style="max-width:580px;font-size:clamp(.95rem,1.8vw,1.2rem);color:var(--tx2);margin-bottom:36px;animation-delay:.2s">SpeakReady AI offers simulated mock interviews, personalized feedback, and comprehensive coaching to help you land your dream opportunity.</p>
                  <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap afu" style="animation-delay:.28s">
                     <button class="bgrd btn px-4 py-3 fs-6" data-bs-toggle="offcanvas" data-bs-target="#lofc" onclick="swTab('signup')">Start Practicing</button>
                     <button class="boc btn px-4 py-3 fs-6" id="heroInstallBtn"><i class="fa-solid fa-download me-2" style="color:var(--pur)"></i>Install App</button>
                     <a href="#features" class="boc btn px-4 py-3 fs-6">Learn More</a>
                  </div>
                  
                  <div class="mt-5 afu" style="animation-delay:.4s">
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
                            <img src="https://cdn.simpleicons.org/{{ $slug }}/a1a1aa" alt="{{ $title }}" style="height:26px;">
                        </a>
                        <a href="https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API" target="_blank" rel="noopener noreferrer" title="Web Speech API"><i class="fa-solid fa-microphone"></i></a>
                     </div>
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
