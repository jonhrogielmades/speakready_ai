@extends('layouts.app')
@section('content')
<style>
    .pulse-anim { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
    @keyframes ai-wave { 0% { height: 4px; } 100% { height: 24px; } }
    .ai-wave-bar { width:4px; height:4px; background:var(--pur); border-radius:2px; margin:0 2px; }
    .ai-speaking .ai-wave-bar { animation: ai-wave 400ms alternate infinite ease-in-out; }
    .ai-speaking .ai-wave-bar:nth-child(1) { animation-delay: 0ms; }
    .ai-speaking .ai-wave-bar:nth-child(2) { animation-delay: 100ms; }
    .ai-speaking .ai-wave-bar:nth-child(3) { animation-delay: 200ms; }
    .ai-speaking .ai-wave-bar:nth-child(4) { animation-delay: 300ms; }
    .ai-speaking .ai-wave-bar:nth-child(5) { animation-delay: 400ms; }
    .panel { background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;margin-bottom:20px; }
    .panel-title { font-weight:700;margin-bottom:15px;display:flex;align-items:center;font-size:1rem;color:var(--tx); }
    .stat-row { display:flex;justify-content:space-between;margin-bottom:10px;font-size:.85rem;color:var(--tx2); }
    .progress-bar-bg { background:var(--bg3);height:8px;border-radius:4px;overflow:hidden;margin-bottom:15px; }
    .progress-bar-fill { background:#60a5fa;height:100%;transition:width 0.3s; }
    .star-item { display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:var(--bg3);border-radius:8px;margin-bottom:8px;font-size:.85rem; }
    .star-item i { font-size:1rem; }
</style>

<div class="db-section active" id="sec-interview-session">
    @if(session('active_interview_id'))
        @php
            $sessionRecord = \App\Models\InterviewSession::with('category')->find(session('active_interview_id'));
            if ($sessionRecord) {
                $num = $sessionRecord->num_questions ?? 5;
                // Try to find questions specifically generated for this session first
                $questions = \App\Models\Question::where('interview_session_id', $sessionRecord->id)->get();
                
                // Fallback to local category questions if none were specifically generated
                if ($questions->isEmpty()) {
                    $questions = \App\Models\Question::where('category_id', $sessionRecord->category_id)->inRandomOrder()->limit($num)->get();
                }
            } else {
                $questions = collect([]);
            }
        @endphp

        @if($sessionRecord && $questions->count() > 0)
        <!-- Header Info -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Interview Workspace</h4>
                <div style="font-size:.85rem;color:var(--tx3);display:flex;gap:15px;">
                    <span><i class="fa-solid fa-layer-group me-1"></i> {{ $sessionRecord->category->title ?? 'General' }}</span>
                    <span><i class="fa-solid fa-gauge-high me-1"></i> {{ ucfirst($sessionRecord->difficulty) }}</span>
                    <span><i class="fa-solid fa-briefcase me-1"></i> {{ $sessionRecord->target_position ?? 'Standard' }}</span>
                </div>
            </div>
            <div class="text-end">
                <div style="font-size:.8rem;color:var(--tx3);margin-bottom:4px">Time Elapsed</div>
                <span class="db-badge" style="background:#f87171;font-size:1.1rem;padding:6px 14px" id="interviewTimer">00:00</span>
            </div>
        </div>

        <div class="row g-4" id="workspaceRow" style="display:none;">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <!-- Progress Tracker -->
                <div class="panel py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size:.85rem;font-weight:600;color:var(--tx2)">Question <span id="currentQNum">1</span> of {{ $questions->count() }}</span>
                        <span style="font-size:.85rem;font-weight:600;color:var(--tx2)" id="progressPct">0% Completed</span>
                    </div>
                    <div class="progress-bar-bg mb-0">
                        <div class="progress-bar-fill" id="progressBar" style="width: 0%;"></div>
                    </div>
                </div>

                <!-- Question Panel -->
                <div class="panel">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="db-badge" style="background:var(--pur)" id="qTypeBadge">Behavioral</span>
                        <span style="font-size:.8rem;color:var(--tx3)" id="qDiffBadge">Medium</span>
                    </div>
                    
                    <div id="aiVoiceVisualizer" style="display: none; align-items: center; margin-bottom: 15px;">
                        <div style="background:rgba(139,92,246,.1); padding:8px 12px; border-radius:12px; display:flex; align-items:center;">
                            <i class="fa-solid fa-robot" style="color:var(--pur); font-size:1.2rem; margin-right:10px;"></i>
                            <div class="d-flex align-items-center" style="height:24px;">
                                <div class="ai-wave-bar"></div>
                                <div class="ai-wave-bar"></div>
                                <div class="ai-wave-bar"></div>
                                <div class="ai-wave-bar"></div>
                                <div class="ai-wave-bar"></div>
                            </div>
                            <span style="font-size: 0.8rem; color:var(--pur); margin-left: 10px; font-weight: 600;">AI Interviewer speaking...</span>
                        </div>
                    </div>

                    <h4 style="line-height:1.5;color:var(--tx);font-weight:600;margin-bottom:0" id="qTextDisplay">Loading question...</h4>
                </div>

                <!-- Answer Response System -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-pen-nib me-2"></i> Your Response</div>
                    
                    <form id="answerForm">
                        <div id="voiceControls" style="display:none;margin-bottom:20px;background:rgba(59,130,246,.05);padding:15px;border-radius:12px;border:1px solid rgba(59,130,246,.2)">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div style="font-weight:600;font-size:.9rem;color:#60a5fa"><i class="fa-solid fa-waveform me-2"></i>Voice Recording</div>
                                <span id="recordingTimer" style="font-family:monospace;font-size:1.1rem;color:#f87171;display:none;">00:00</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" id="micStartBtn" class="btn btn-primary" onclick="startRecording()"><i class="fa-solid fa-microphone me-2"></i>Start</button>
                                <button type="button" id="micPauseBtn" class="btn btn-warning" onclick="pauseRecording()" style="display:none;"><i class="fa-solid fa-pause me-2"></i>Pause</button>
                                <button type="button" id="micStopBtn" class="btn btn-danger" onclick="stopRecording()" style="display:none;"><i class="fa-solid fa-stop me-2"></i>Stop</button>
                            </div>
                        </div>

                        <textarea id="answerTextarea" class="oinp mb-2" style="min-height:200px;font-size:.95rem" placeholder="Type your answer here, or use voice to auto-transcribe..."></textarea>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div style="font-size:.8rem;color:var(--tx3)">
                                <span id="wordCount">0 words</span> • <span id="charCount">0 characters</span>
                                <span id="autoSaveIndicator" class="ms-3 text-success" style="display:none;"><i class="fa-solid fa-check me-1"></i>Auto-saved</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between border-top pt-4" style="border-color:var(--bd) !important">
                            <div>
                                <button type="button" class="btn btn-outline-secondary me-2" onclick="prevQuestion()" id="prevBtn" disabled><i class="fa-solid fa-arrow-left me-2"></i>Previous</button>
                                <button type="button" class="btn btn-outline-warning" onclick="skipQuestion()"><i class="fa-solid fa-forward-step me-2"></i>Skip</button>
                            </div>
                            <button type="button" class="bgrd btn px-4" onclick="submitAnswer()" id="nextBtn">Next Question <i class="fa-solid fa-arrow-right ms-2"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Side Panels -->
            <div class="col-lg-4">
                <!-- Session Navigation (Mobile fallback / Overview) -->
                <!-- Camera Presence -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-camera-web me-2"></i> Camera Presence</div>
                    <div style="position:relative;background:#000;height:180px;border-radius:12px;margin-bottom:15px;overflow:hidden;display:flex;align-items:center;justify-content:center">
                        <video id="userCamera" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);"></video>
                        <div style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.6);padding:2px 8px;border-radius:4px;font-size:.7rem;color:#34d399"><i class="fa-solid fa-circle text-success pulse-anim" style="font-size:.5rem;margin-right:4px"></i> Live</div>
                    </div>
                    <div class="stat-row"><span>Eye Contact</span><span class="text-success"><i class="fa-solid fa-check me-1"></i>Good</span></div>
                    <div class="stat-row mb-0"><span>Posture</span><span class="text-success"><i class="fa-solid fa-check me-1"></i>Good</span></div>
                </div>

                <!-- AI Visualizer Panel -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-chart-pie me-2"></i> AI Visualizer</div>
                    <div class="text-center mb-3">
                        <div style="font-size:2rem;font-weight:700;color:#34d399" id="overallReadiness">--%</div>
                        <div style="font-size:.75rem;color:var(--tx3)">Real-time Readiness</div>
                    </div>
                    <div class="stat-row"><span>Clarity</span><span id="metClarity">--%</span></div>
                    <div class="stat-row"><span>Relevance</span><span id="metRelevance">--%</span></div>
                    <div class="stat-row"><span>Grammar</span><span id="metGrammar">--%</span></div>
                    <div class="stat-row mb-0"><span>Professionalism</span><span id="metProf">--%</span></div>
                </div>

                <!-- STAR Framework Analyzer -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-star me-2" style="color:#fbbf24"></i> STAR Analyzer</div>
                    <div class="star-item"><span>Situation</span><i class="fa-solid fa-circle-xmark text-danger" id="starS"></i></div>
                    <div class="star-item"><span>Task</span><i class="fa-solid fa-circle-xmark text-danger" id="starT"></i></div>
                    <div class="star-item"><span>Action</span><i class="fa-solid fa-circle-xmark text-danger" id="starA"></i></div>
                    <div class="star-item"><span>Result</span><i class="fa-solid fa-circle-xmark text-danger" id="starR"></i></div>
                    <div style="margin-top:10px;font-size:.8rem;color:#fbbf24;background:rgba(251,191,36,.1);padding:10px;border-radius:8px;border:1px solid rgba(251,191,36,.3)" id="coachingTip">
                        <i class="fa-solid fa-lightbulb me-1"></i> <strong>Coach:</strong> Start typing to get real-time analysis!
                    </div>
                </div>

                <!-- Voice Analytics Module -->
                <div class="panel" id="voiceAnalyticsPanel" style="display:none;">
                    <div class="panel-title"><i class="fa-solid fa-wave-square me-2"></i> Voice Analytics</div>
                    <div class="stat-row"><span>Speaking Duration</span><span id="vaDuration">0s</span></div>
                    <div class="stat-row"><span>Speed (WPM)</span><span id="vaWpm">0</span></div>
                    <div class="stat-row mb-0"><span>Filler Words (Um, Uh)</span><span id="vaFillers" class="text-danger">0</span></div>
                </div>

                <!-- Interview Notes -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-clipboard me-2"></i> Session Notes</div>
                    <textarea id="sessionNotes" class="oinp" style="min-height:100px;font-size:.85rem;padding:10px" placeholder="Private notes, key reminders, etc..."></textarea>
                </div>
            </div>
        </div>

        <div id="introContainer" class="text-center p-5 panel" style="margin-top:40px;max-width:600px;margin-left:auto;margin-right:auto;">
            <div style="width:60px;height:60px;border-radius:15px;background:rgba(59,130,246,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="fa-solid fa-robot" style="font-size:1.8rem;color:#60a5fa"></i>
            </div>
            <h4 style="color:var(--tx);font-weight:700">Interview Workspace Ready</h4>
            <p style="color:var(--tx3);margin-bottom:30px">Your session is configured with {{ $questions->count() }} questions. The AI visualizer and STAR analyzer will run in real-time as you respond.</p>
            <div style="display:flex; justify-content:center; gap: 10px; flex-wrap: wrap; margin-bottom: 30px;">
                <span class="db-badge" style="background:rgba(139,92,246,.15);color:#a78bfa"><i class="fa-solid fa-microphone me-1"></i> {{ ucfirst($sessionRecord->response_mode) }} Mode</span>
                <span class="db-badge" style="background:rgba(52,211,153,.12);color:#34d399"><i class="fa-solid fa-bullseye me-1"></i> {{ ucfirst($sessionRecord->coach_focus_mode) }} Focus</span>
            </div>
            <button class="bgrd btn px-4 py-3 w-100" style="font-size:1.1rem;font-weight:600" onclick="startInterviewSession()">Begin Interview <i class="fa-solid fa-play ms-2"></i></button>
        </div>

        <form id="finishForm" action="{{ route('interview.finish') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="session_id" value="{{ session('active_interview_id') }}">
            <input type="hidden" name="duration_seconds" id="formDuration">
            <input type="hidden" name="notes" id="formNotes">
        </form>

        <script>
            const questions = {!! json_encode($questions) !!};
            const responseMode = "{{ $sessionRecord->response_mode }}";
            let currentQIdx = 0;
            let timerSeconds = 0;
            let timerInterval;
            
            // Answers state
            let answersData = Array(questions.length).fill().map(() => ({
                text: '',
                is_skipped: false,
                wpm: 0,
                voice_duration: 0,
                filler_words: 0
            }));

            // Voice state
            let recognition = null;
            let isRecording = false;
            let recTimerSeconds = 0;
            let recTimerInterval;

            if ('webkitSpeechRecognition' in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = true;
                recognition.interimResults = true;
                recognition.onresult = function(event) {
                    let finalTranscript = '';
                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        if (event.results[i].isFinal) finalTranscript += event.results[i][0].transcript;
                    }
                    if(finalTranscript) {
                        const ta = document.getElementById('answerTextarea');
                        ta.value = ta.value + " " + finalTranscript.trim();
                        triggerAnalysis();
                    }
                };
            }

            function initCamera() {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({ video: true })
                        .then(function(stream) {
                            let video = document.getElementById('userCamera');
                            if (video) {
                                video.srcObject = stream;
                                video.play();
                            }
                        })
                        .catch(function(err) {
                            console.error("Error accessing camera: ", err);
                        });
                } else {
                    console.error("getUserMedia not supported");
                }
            }

            function speakQuestion(text) {
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                    let utterance = new SpeechSynthesisUtterance(text);
                    utterance.rate = 1.0;
                    utterance.pitch = 1.0;

                    const vis = document.getElementById('aiVoiceVisualizer');
                    
                    utterance.onstart = function() {
                        if(vis) {
                            vis.style.display = 'flex';
                            vis.classList.add('ai-speaking');
                        }
                    };
                    
                    utterance.onend = function() {
                        if(vis) {
                            vis.classList.remove('ai-speaking');
                            vis.style.display = 'none';
                        }
                    };

                    window.speechSynthesis.speak(utterance);
                }
            }

            function startInterviewSession() {
                document.getElementById('introContainer').style.display = 'none';
                document.getElementById('workspaceRow').style.display = 'flex';
                
                initCamera();
                
                if(responseMode === 'voice' || responseMode === 'hybrid') {
                    document.getElementById('voiceControls').style.display = 'block';
                    document.getElementById('voiceAnalyticsPanel').style.display = 'block';
                }

                timerInterval = setInterval(() => {
                    timerSeconds++;
                    const m = Math.floor(timerSeconds / 60).toString().padStart(2, '0');
                    const s = (timerSeconds % 60).toString().padStart(2, '0');
                    document.getElementById('interviewTimer').innerText = m + ':' + s;
                    
                    if(timerSeconds % 30 === 0) autoSaveState(); // auto save every 30s
                }, 1000);

                loadQuestion(0);
                
                document.getElementById('answerTextarea').addEventListener('input', triggerAnalysis);
                document.getElementById('sessionNotes').addEventListener('change', autoSaveState);
            }

            function loadQuestion(idx) {
                currentQIdx = idx;
                const q = questions[idx];
                
                document.getElementById('currentQNum').innerText = idx + 1;
                document.getElementById('qTextDisplay').innerText = q.question_text;
                document.getElementById('qTypeBadge').innerText = q.type || 'General';
                document.getElementById('qDiffBadge').innerText = ucfirst(q.difficulty);
                
                let pct = Math.round((idx / questions.length) * 100);
                document.getElementById('progressBar').style.width = pct + '%';
                document.getElementById('progressPct').innerText = pct + '% Completed';

                // Restore answer state if navigated back
                document.getElementById('answerTextarea').value = answersData[idx].text;
                
                speakQuestion(q.question_text);
                
                document.getElementById('prevBtn').disabled = (idx === 0);
                
                if (idx === questions.length - 1) {
                    document.getElementById('nextBtn').innerHTML = 'Finish Interview <i class="fa-solid fa-flag-checkered ms-2"></i>';
                    document.getElementById('nextBtn').classList.replace('btn-primary', 'btn-success');
                } else {
                    document.getElementById('nextBtn').innerHTML = 'Next Question <i class="fa-solid fa-arrow-right ms-2"></i>';
                    document.getElementById('nextBtn').classList.replace('btn-success', 'btn-primary');
                }
                
                triggerAnalysis();
            }

            function triggerAnalysis() {
                const text = document.getElementById('answerTextarea').value;
                const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
                const charCount = text.length;
                
                document.getElementById('wordCount').innerText = wordCount + ' words';
                document.getElementById('charCount').innerText = charCount + ' characters';

                // Mock STAR Analysis
                const hasS = wordCount > 10;
                const hasT = wordCount > 20 && text.toLowerCase().includes('task');
                const hasA = wordCount > 30 && text.toLowerCase().includes('action');
                const hasR = wordCount > 40 && (text.toLowerCase().includes('result') || text.toLowerCase().includes('led to'));
                
                updateStarIcon('starS', hasS);
                updateStarIcon('starT', hasT);
                updateStarIcon('starA', hasA);
                updateStarIcon('starR', hasR);

                // Coaching Tip
                let tip = "Provide a specific example.";
                if(!hasS) tip = "Start by describing the Situation.";
                else if(!hasR) tip = "Don't forget to mention the measurable Result of your actions.";
                else tip = "Great STAR response!";
                document.getElementById('coachingTip').innerHTML = `<i class="fa-solid fa-lightbulb me-1"></i> <strong>Coach:</strong> ${tip}`;

                // Mock Visualizer
                let readiness = Math.min(100, Math.max(0, wordCount * 2));
                if(wordCount === 0) readiness = 0;
                document.getElementById('overallReadiness').innerText = readiness + '%';
                document.getElementById('metClarity').innerText = (readiness > 0 ? Math.min(100, readiness + 10) : 0) + '%';
                document.getElementById('metRelevance').innerText = (readiness > 0 ? Math.min(100, readiness + 5) : 0) + '%';
                document.getElementById('metGrammar').innerText = (readiness > 0 ? Math.min(100, readiness + 15) : 0) + '%';
                document.getElementById('metProf').innerText = (readiness > 0 ? Math.min(100, readiness + 8) : 0) + '%';

                // Fillers mock
                const fillers = (text.toLowerCase().match(/\b(um|uh|like|basically|you know)\b/g) || []).length;
                document.getElementById('vaFillers').innerText = fillers;
                answersData[currentQIdx].text = text;
                answersData[currentQIdx].filler_words = fillers;
            }

            function updateStarIcon(id, status) {
                const el = document.getElementById(id);
                if(status) {
                    el.className = 'fa-solid fa-circle-check text-success';
                } else {
                    el.className = 'fa-solid fa-circle-xmark text-danger';
                }
            }

            function startRecording() {
                if(!recognition) return alert("Speech recognition not supported in this browser.");
                recognition.start();
                isRecording = true;
                document.getElementById('micStartBtn').style.display = 'none';
                document.getElementById('micPauseBtn').style.display = 'block';
                document.getElementById('micStopBtn').style.display = 'block';
                document.getElementById('recordingTimer').style.display = 'block';
                
                recTimerInterval = setInterval(() => {
                    recTimerSeconds++;
                    const m = Math.floor(recTimerSeconds / 60).toString().padStart(2, '0');
                    const s = (recTimerSeconds % 60).toString().padStart(2, '0');
                    document.getElementById('recordingTimer').innerText = m + ':' + s;
                    document.getElementById('vaDuration').innerText = recTimerSeconds + 's';
                    answersData[currentQIdx].voice_duration = recTimerSeconds;
                    
                    const wordCount = document.getElementById('answerTextarea').value.trim().split(/\s+/).filter(w=>w.length>0).length;
                    const wpm = recTimerSeconds > 0 ? Math.round((wordCount / recTimerSeconds) * 60) : 0;
                    document.getElementById('vaWpm').innerText = wpm;
                    answersData[currentQIdx].wpm = wpm;
                }, 1000);
            }

            function pauseRecording() {
                if(recognition) recognition.stop();
                isRecording = false;
                clearInterval(recTimerInterval);
                document.getElementById('micStartBtn').style.display = 'block';
                document.getElementById('micStartBtn').innerText = 'Resume';
                document.getElementById('micPauseBtn').style.display = 'none';
            }

            function stopRecording() {
                pauseRecording();
                document.getElementById('micStartBtn').innerText = 'Start';
                document.getElementById('micStopBtn').style.display = 'none';
                document.getElementById('recordingTimer').style.display = 'none';
                recTimerSeconds = 0;
            }

            function saveCurrentAnswer(isSkipped = false) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('question_id', questions[currentQIdx].id);
                formData.append('answer_text', answersData[currentQIdx].text);
                formData.append('is_skipped', isSkipped);
                formData.append('response_mode', responseMode);
                formData.append('wpm', answersData[currentQIdx].wpm);
                formData.append('voice_duration', answersData[currentQIdx].voice_duration);
                formData.append('filler_words_count', answersData[currentQIdx].filler_words);
                formData.append('notes', document.getElementById('sessionNotes').value);

                return fetch('{{ route("interview.answer") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }

            function autoSaveState() {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('notes', document.getElementById('sessionNotes').value);
                formData.append('duration_seconds', timerSeconds);
                
                fetch('{{ url("interview/save-state") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(() => {
                    const ind = document.getElementById('autoSaveIndicator');
                    ind.style.display = 'inline';
                    setTimeout(() => ind.style.display = 'none', 2000);
                });
            }

            function submitAnswer() {
                if(isRecording) stopRecording();
                saveCurrentAnswer(false).then(() => {
                    if (currentQIdx < questions.length - 1) {
                        loadQuestion(currentQIdx + 1);
                    } else {
                        finishInterview();
                    }
                });
            }

            function skipQuestion() {
                if(isRecording) stopRecording();
                saveCurrentAnswer(true).then(() => {
                    if (currentQIdx < questions.length - 1) {
                        loadQuestion(currentQIdx + 1);
                    } else {
                        finishInterview();
                    }
                });
            }

            function prevQuestion() {
                if(isRecording) stopRecording();
                if (currentQIdx > 0) {
                    loadQuestion(currentQIdx - 1);
                }
            }

            function finishInterview() {
                let video = document.getElementById('userCamera');
                if (video && video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                }
                clearInterval(timerInterval);
                document.getElementById('formDuration').value = timerSeconds;
                document.getElementById('formNotes').value = document.getElementById('sessionNotes').value;
                document.getElementById('finishForm').submit();
            }

            function ucfirst(str) {
                if(!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        </script>
        @else
        <div class="panel">
            <p style="color:var(--tx3)">No questions found for this setup. Please ask an admin to add some.</p>
        </div>
        @endif
    @endif
</div>
@endsection