@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
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
    .panel { 
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        padding: 24px;
        margin-bottom: 24px; 
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .panel:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 45px rgba(0, 0, 0, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.08);
    }
    .panel-title { font-weight:800;margin-bottom:20px;display:flex;align-items:center;font-size:1.1rem;color:var(--tx); letter-spacing: -0.3px; }
    .stat-row { display:flex;justify-content:space-between;margin-bottom:12px;font-size:.9rem;color:var(--tx2); font-weight: 500; }
    .progress-bar-bg { background:var(--bg3);height:8px;border-radius:4px;overflow:hidden;margin-bottom:15px; }
    .progress-bar-fill { background:#60a5fa;height:100%;transition:width 0.3s; }
    .star-item { display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--bg3);border-radius:12px;margin-bottom:10px;font-size:.9rem; font-weight: 500; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .star-item:hover { transform: translateY(-1px); background: var(--sf); box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--bd); }
    .star-item i { font-size:1.1rem; transition: transform 0.3s, text-shadow 0.3s; }
    .star-item i.fa-circle-check { text-shadow: 0 0 10px rgba(52, 211, 153, 0.5); }
    @keyframes scanAnim { 0% { top: 0%; opacity: 0.5; } 50% { top: 100%; opacity: 1; } 100% { top: 0%; opacity: 0.5; } }
    @keyframes avatarPulse { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(3.5); opacity: 0; } }
    
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    
    /* Animations */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }

    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
    .sound-wave { position:absolute;border-radius:50%;width:100%;height:100%;display:none; }

    /* Circular Audio Spectrum */
    .circular-spectrum { position: absolute; top: 50%; left: 50%; width: 0; height: 0; display: none; z-index: 5; }
    .circular-spectrum .spectrum-bar { position: absolute; bottom: 0; left: -4px; width: 8px; background: linear-gradient(to top, #8b5cf6, #34d399); border-radius: 4px; transform-origin: bottom center; height: 6px; transition: height 0.05s ease-out; box-shadow: 0 0 12px rgba(52,211,153,0.6); }
    
    /* Responsive overrides */
    @media (max-width: 768px) {
        .avatar-wrapper { transform: scale(0.7); }
        .circular-spectrum { transform: scale(0.7); }
        .ai-avatar-panel { height: 260px !important; }
        .panel { padding: 15px; }
        .panel-title { font-size: 0.9rem; }
    }
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
                    // Try to match exact difficulty and active status first
                    $questions = \App\Models\Question::where('category_id', $sessionRecord->category_id)
                        ->where('status', 'active')
                        ->where('difficulty', $sessionRecord->difficulty)
                        ->inRandomOrder()->limit($num)->get();
                        
                    // If no questions match the difficulty, fallback to any active questions in category
                    if ($questions->isEmpty()) {
                        $questions = \App\Models\Question::where('category_id', $sessionRecord->category_id)
                            ->where('status', 'active')
                            ->inRandomOrder()->limit($num)->get();
                    }
                }
            } else {
                $questions = collect([]);
            }
        @endphp

        @if($sessionRecord && $questions->count() > 0)
        <!-- Header Info -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="text-gradient-primary" style="font-size:1.6rem;font-weight:800;margin-bottom:4px;letter-spacing:-0.5px;"><i class="fa-solid fa-chalkboard-user me-2"></i>Interview Workspace</h4>
                <div style="font-size:.85rem;color:var(--tx3);display:flex;gap:15px;">
                    <span><i class="fa-solid fa-layer-group me-1"></i> {{ $sessionRecord->category->title ?? 'General' }}</span>
                    <span><i class="fa-solid fa-gauge-high me-1"></i> {{ ucfirst($sessionRecord->difficulty) }}</span>
                    <span><i class="fa-solid fa-briefcase me-1"></i> {{ $sessionRecord->target_position ?? 'Standard' }}</span>
                </div>
            </div>
            
            <!-- Desktop Buttons (Visible only when workspace is active) -->
            <div class="d-none d-lg-flex gap-2" id="headerButtons" style="opacity: 0; pointer-events: none; transition: opacity 0.3s;">
                <button type="button" class="btn btn-outline-info" onclick="repeatQuestion()"><i class="fa-solid fa-volume-high me-2"></i>Repeat</button>
                <button type="button" class="btn btn-outline-danger" onclick="finishInterview()"><i class="fa-solid fa-flag-checkered me-2"></i>End Session</button>
                <button type="button" class="btn px-4 next-btn-class text-white btn-shine" style="background:var(--dash-primary, #60a5fa); border:none; box-shadow: 0 4px 15px rgba(96,165,250,0.4); font-weight:600;" onclick="submitAnswer()">Send Answer <i class="fa-solid fa-paper-plane ms-2"></i></button>
            </div>
        </div>

        <div id="workspaceWrapper" style="display:none;">
        <div class="row g-4" id="workspaceRow">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <!-- Progress Tracker Removed by User -->

                <!-- Simulated AI Video Avatar Panel -->
                <div class="panel p-0 ai-avatar-panel animate-fade-up delay-100" style="overflow:hidden;border:1px solid var(--bd);background:#000;position:relative;height:280px;border-radius:24px;margin-bottom:24px;box-shadow:0 15px 40px rgba(0,0,0,0.15);">
                    <div style="position:absolute; inset:0; background: radial-gradient(circle at top right, rgba(139,92,246,0.3), transparent 60%), radial-gradient(circle at bottom left, rgba(59,130,246,0.3), transparent 60%); z-index:1; pointer-events:none;"></div>
                    <!-- Mobile Picture-in-Picture Camera -->
                    <div class="d-block d-lg-none" style="position:absolute; top:15px; right:15px; width:80px; height:105px; border-radius:8px; overflow:hidden; border:2px solid rgba(255,255,255,0.3); z-index:50; box-shadow: 0 4px 15px rgba(0,0,0,0.6);">
                        <video id="userCameraMobile" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);background:#222;"></video>
                    </div>

                    <div id="aiAvatarContainer" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);">
                        <div class="avatar-wrapper" id="aiAvatarHead" style="width:110px;height:110px;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;transition:border-color 0.4s;">
                            <!-- The Image Container (with border, glow, and clipping for the image itself) -->
                            <div style="width:100%;height:100%;background:rgba(255,255,255,0.1);border-radius:50%;border:4px solid #8b5cf6;overflow:hidden;position:relative;z-index:10;box-shadow: 0 0 25px rgba(139,92,246,0.5);">
                                <img src="{{ asset('img/ai_avatar.jpg') }}" alt="AI Avatar" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        </div>
                        
                        <!-- Circular Audio Spectrum Waveform -->
                        <div class="circular-spectrum sound-wave">
                            @for ($i = 0; $i < 36; $i++)
                                @php 
                                    // Use a pseudo-random sequence so it looks dynamic but is consistent
                                    $animClass = 'sb' . (($i * 7) % 10 + 1); 
                                    $rot = $i * 10;
                                @endphp
                                <div class="spectrum-bar {{ $animClass }}" style="transform: rotate({{ $rot }}deg) translateY(-65px);"></div>
                            @endfor
                        </div>
                    </div>
                    <!-- Overlay Text -->
                    <div style="position:absolute;bottom:0;left:0;width:100%;background:linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.7) 60%, transparent 100%);padding:30px 20px 20px 20px;">
                        <div class="d-flex justify-content-between align-items-end gap-3">
                            <div>
                                <span class="badge mb-2" style="background:var(--pur);color:white;font-size:0.75rem;"><i class="fa-solid fa-bolt me-1"></i> {{ $sessionRecord->company_persona ?? 'AI Coach' }}</span>
                                <div id="aiQuestionText" style="color:white;font-size:1.1rem;font-weight:600;line-height:1.4;">Loading your first question...</div>
                            </div>
                            <span class="badge bg-white text-dark" style="font-size:0.8rem;white-space:nowrap;" id="qCounter">1/10</span>
                        </div>
                    </div>
                </div>

                <!-- Answer Response System -->
                <div class="panel mb-4 animate-fade-up delay-200">
                    <div class="panel-title">
                        <i class="fa-solid fa-pen-nib me-2"></i> Your Response
                        @if(session('game_level_id'))
                            <span class="badge ms-auto" style="background:#ef4444; color:white;"><i class="fa-solid fa-gamepad me-1"></i> GAME MODE</span>
                        @endif
                    </div>
                    
                    <form id="answerForm">
                        <div id="voiceControls" style="display:none;margin-bottom:20px;background:rgba(59,130,246,.05);padding:15px;border-radius:12px;border:1px solid rgba(59,130,246,.2)">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div style="font-weight:600;font-size:.9rem;color:#60a5fa"><i class="fa-solid fa-waveform me-2"></i>Voice Recording</div>
                                <span id="recordingTimer" style="font-family:monospace;font-size:1.1rem;color:#f87171;display:none;">00:00</span>
                            </div>
                            
                            @if(session('game_level_id'))
                            <!-- Gamified Hold-to-Talk Button -->
                            <div class="d-flex justify-content-center py-3">
                                <button type="button" id="holdToTalkBtn" class="btn btn-danger" style="width:120px; height:120px; border-radius:50%; font-weight:800; border:4px solid #b91c1c; box-shadow: 0 10px 20px rgba(239,68,68,0.4); display:flex; flex-direction:column; align-items:center; justify-content:center; user-select:none; touch-action:manipulation;">
                                    <i class="fa-solid fa-microphone fa-2x mb-2"></i>
                                    HOLD
                                </button>
                            </div>
                            @else
                            <div class="d-flex gap-2">
                                <button type="button" id="micStartBtn" class="btn btn-primary" onclick="startRecording()"><i class="fa-solid fa-microphone me-2"></i>Start</button>
                                <button type="button" id="micPauseBtn" class="btn btn-warning" onclick="pauseRecording()" style="display:none;"><i class="fa-solid fa-pause me-2"></i>Pause</button>
                                <button type="button" id="micStopBtn" class="btn btn-danger" onclick="stopRecording()" style="display:none;"><i class="fa-solid fa-stop me-2"></i>Stop</button>
                            </div>
                            @endif
                        </div>

                        <div id="chatTranscriptContainer" style="max-height: 350px; overflow-y: auto; padding: 15px; margin-bottom: 20px; background: rgba(0,0,0,0.15); border-radius: 12px; border: 1px solid var(--bd); display: flex; flex-direction: column; gap: 15px;">
                            <!-- Chat bubbles will go here -->
                        </div>
                        <textarea id="answerTextarea" class="oinp mb-2" style="min-height:80px;font-size:.95rem" placeholder="Type your answer here, or use voice to auto-transcribe..."></textarea>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div style="font-size:.8rem;color:var(--tx3)">
                                <span id="wordCount">0 words</span> • <span id="charCount">0 characters</span>
                                <span id="autoSaveIndicator" class="ms-3 text-success" style="display:none;"><i class="fa-solid fa-check me-1"></i>Auto-saved</span>
                            </div>
                        </div>

                        <div class="d-flex d-lg-none flex-column flex-sm-row justify-content-between border-top pt-4 gap-3" style="border-color:var(--bd) !important">
                            <div class="d-flex flex-wrap gap-2 w-100">
                                <button type="button" class="btn btn-outline-info flex-fill" onclick="repeatQuestion()"><i class="fa-solid fa-volume-high"></i></button>
                                <button type="button" class="btn btn-outline-danger flex-fill" onclick="finishInterview()"><i class="fa-solid fa-flag-checkered"></i> End</button>
                            </div>
                            <button type="button" class="btn px-4 w-100 next-btn-class text-white btn-shine" style="background:var(--dash-primary, #60a5fa); border:none; box-shadow: 0 4px 15px rgba(96,165,250,0.4); font-weight:600;" onclick="submitAnswer()">Send Answer <i class="fa-solid fa-paper-plane ms-2"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Side Panels -->
            <div class="col-lg-4">
                <!-- Session Navigation (Mobile fallback / Overview) -->
                <!-- Camera Presence (Hidden on mobile since it's inside the AI panel now) -->
                <div class="panel d-none d-lg-block animate-fade-up delay-100" id="cameraPanel">
                    <div class="panel-title"><i class="fa-solid fa-camera-web me-2"></i> Camera Presence</div>
                    <div style="position:relative;background:#000;height:180px;border-radius:12px;margin-bottom:15px;overflow:hidden;display:flex;align-items:center;justify-content:center">
                        <video id="userCamera" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);"></video>
                        <div class="face-scanner-box" id="faceScannerBox" style="display:none;position:absolute;width:120px;height:120px;border:2px solid #34d399;border-radius:12px;box-shadow:0 0 15px rgba(52,211,153,0.3);transition:all 0.3s ease;">
                            <div class="scan-line" style="width:100%;height:2px;background:#34d399;position:absolute;top:0;animation: scanAnim 2s infinite linear;box-shadow:0 0 8px #34d399;"></div>
                        </div>
                        <div style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.6);padding:2px 8px;border-radius:4px;font-size:.7rem;color:#34d399"><i class="fa-solid fa-circle text-success pulse-anim" style="font-size:.5rem;margin-right:4px"></i> AI Track</div>
                    </div>
                    <div class="stat-row"><span>Eye Contact</span><span id="stEyeContact" class="text-success"><i class="fa-solid fa-check me-1"></i>Good</span></div>
                    <div class="stat-row mb-0"><span>Posture</span><span id="stPosture" class="text-success"><i class="fa-solid fa-check me-1"></i>Good</span></div>
                </div>

                <!-- AI Visualizer Panel -->
                <div class="panel animate-fade-up delay-200">
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
                <div class="panel animate-fade-up delay-300">
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
                <div class="panel animate-fade-up delay-400" id="voiceAnalyticsPanel" style="display:none;">
                    <div class="panel-title"><i class="fa-solid fa-wave-square me-2"></i> Voice Analytics</div>
                    <div class="stat-row"><span>Speaking Duration</span><span id="vaDuration">0s</span></div>
                    <div class="stat-row"><span>Speed (WPM)</span><span id="vaWpm">0</span></div>
                    <div class="stat-row mb-0"><span>Filler Words (Um, Uh)</span><span id="vaFillers" class="text-danger">0</span></div>
                </div>

                <!-- Interview Notes -->
                <div class="panel animate-fade-up delay-400">
                    <div class="panel-title"><i class="fa-solid fa-clipboard me-2"></i> Session Notes</div>
                    <textarea id="sessionNotes" class="oinp" style="min-height:100px;font-size:.85rem;padding:10px" placeholder="Private notes, key reminders, etc..."></textarea>
                </div>
            </div>
        </div>
        </div>

        <div id="introContainer" class="text-center p-4 p-md-5 panel animate-fade-up" style="margin-top:40px;max-width:600px;margin-left:auto;margin-right:auto;border: 1px solid rgba(139,92,246,0.3);box-shadow: 0 20px 50px rgba(139,92,246,0.15);">
            <div style="width:70px;height:70px;border-radius:20px;background:linear-gradient(135deg, rgba(59,130,246,.15), rgba(139,92,246,.15));display:flex;align-items:center;justify-content:center;margin:0 auto 24px;border: 1px solid rgba(139,92,246,0.2);">
                <i class="fa-solid fa-robot" style="font-size:1.8rem;color:#60a5fa"></i>
            </div>
            <h4 style="color:var(--tx);font-weight:700">Interview Workspace Ready</h4>
            <p style="color:var(--tx3);margin-bottom:30px">Your session is configured with {{ $questions->count() }} questions. The AI visualizer and STAR analyzer will run in real-time as you respond.</p>
            <div style="display:flex; justify-content:center; gap: 10px; flex-wrap: wrap; margin-bottom: 30px;">
                <span class="db-badge" style="background:rgba(59,130,246,.15);color:#60a5fa"><i class="fa-solid fa-microphone me-1"></i> {{ ucfirst($sessionRecord->response_mode) }} Mode</span>
                <span class="db-badge" style="background:rgba(52,211,153,.12);color:#34d399"><i class="fa-solid fa-bullseye me-1"></i> {{ ucfirst($sessionRecord->coach_focus_mode) }} Focus</span>
            </div>
            <button class="btn px-4 py-3 w-100 btn-shine" style="font-size:1.15rem;font-weight:700;border-radius:14px;background:var(--dash-primary, #60a5fa);color:white;border:none;box-shadow:0 8px 25px rgba(96,165,250,0.4);transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(96,165,250,0.6)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 8px 25px rgba(96,165,250,0.4)'" onclick="startInterviewSession()">Begin Interview <i class="fa-solid fa-play ms-2"></i></button>
        </div>

        <form id="finishForm" action="{{ route('interview.finish') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="session_id" value="{{ session('active_interview_id') }}">
            <input type="hidden" name="duration_seconds" id="formDuration">
            <input type="hidden" name="notes" id="formNotes">
        </form>

        <script>
            const questions = {!! json_encode($questions) !!};
            const totalQuestions = {{ $num }};
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
                filler_words: 0,
                pause_count: 0,
                confidence_score: 85,
                eye_contact_score: 90,
                posture_score: 90
            }));

            // Voice and Body Language state
            let recognition = null;
            let isRecording = false;
            let recTimerSeconds = 0;
            let recTimerInterval;
            let preRecordingText = ''; // Prevents duplicate word bugs

            let lastSpeechEnd = 0;
            if ('webkitSpeechRecognition' in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = true;
                recognition.interimResults = true;
                
                recognition.onsoundstart = function() {
                    if (lastSpeechEnd > 0) {
                        const gap = (Date.now() - lastSpeechEnd) / 1000;
                        if (gap > 3) {
                            answersData[currentQIdx].pause_count++;
                        }
                    }
                };
                
                recognition.onsoundend = function() {
                    lastSpeechEnd = Date.now();
                };

                recognition.onresult = function(event) {
                    let currentTranscript = '';
                    for (let i = 0; i < event.results.length; ++i) {
                        currentTranscript += event.results[i][0].transcript;
                    }
                    if(currentTranscript) {
                        const ta = document.getElementById('answerTextarea');
                        let newText = preRecordingText + (preRecordingText ? " " : "") + currentTranscript.trim();
                        ta.value = newText.trim();
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
                            let mobileVideo = document.getElementById('userCameraMobile');
                            if (mobileVideo) {
                                mobileVideo.srcObject = stream;
                                mobileVideo.play();
                            }
                        })
                        .catch(function(err) {
                            console.error("Error accessing camera: ", err);
                        });
                } else {
                    console.error("getUserMedia not supported");
                }
            }
            
            async function trackBodyLanguage() {
                const video = document.getElementById('userCamera');
                if (!video || !video.srcObject) return;
                
                try {
                    const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                    
                    if (detection) {
                        // Face is visible
                        const landmarks = detection.landmarks;
                        const nose = landmarks.getNose()[0];
                        const leftEye = landmarks.getLeftEye()[0];
                        const rightEye = landmarks.getRightEye()[0];
                        
                        // Calculate Head Yaw/Pitch to detect looking away
                        const eyeDist = Math.abs(leftEye.x - rightEye.x);
                        const noseDistLeft = Math.abs(leftEye.x - nose.x);
                        const ratio = noseDistLeft / eyeDist;
                        
                        // If nose is too close to left or right eye, user is looking away
                        const eyeGood = (ratio > 0.3 && ratio < 0.7);
                        if (!eyeGood) answersData[currentQIdx].eye_contact_score = Math.max(40, answersData[currentQIdx].eye_contact_score - 5);
                        
                        // Posture logic: if nose Y is extremely low compared to the frame, slouching
                        const postGood = (nose.y < video.videoHeight * 0.7);
                        if (!postGood) answersData[currentQIdx].posture_score = Math.max(40, answersData[currentQIdx].posture_score - 2); 

                        document.getElementById('stEyeContact').innerHTML = eyeGood ? '<i class="fa-solid fa-check me-1"></i>Good' : '<i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i>Looking Away';
                        document.getElementById('stEyeContact').className = eyeGood ? 'text-success' : 'text-warning';
                        document.getElementById('stPosture').innerHTML = postGood ? '<i class="fa-solid fa-check me-1"></i>Good' : '<i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i>Slouching';
                        document.getElementById('stPosture').className = postGood ? 'text-success' : 'text-warning';
                    } else {
                        // No face detected
                        document.getElementById('stEyeContact').innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i>No Face Detected';
                        document.getElementById('stEyeContact').className = 'text-danger';
                        document.getElementById('stPosture').innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1 text-danger"></i>No Face Detected';
                        document.getElementById('stPosture').className = 'text-danger';
                        
                        answersData[currentQIdx].eye_contact_score = Math.max(40, answersData[currentQIdx].eye_contact_score - 5);
                        answersData[currentQIdx].posture_score = Math.max(40, answersData[currentQIdx].posture_score - 5);
                    }
                } catch(e) {
                    console.error("Tracking error", e);
                }
            }

            let visualizerInterval = null;
            let currentAmplitude = 0.2;
            let preferredVoice = null;

            // Initialize preferred voice
            function loadVoices() {
                let voices = window.speechSynthesis.getVoices();
                if (voices.length > 0) {
                    // Try to find a high-quality English voice
                    preferredVoice = voices.find(v => v.lang.startsWith('en') && (v.name.includes('Google') || v.name.includes('Premium') || v.name.includes('Natural') || v.name.includes('Siri'))) || voices.find(v => v.lang.startsWith('en')) || voices[0];
                }
            }
            if ('speechSynthesis' in window) {
                window.speechSynthesis.onvoiceschanged = loadVoices;
                loadVoices();
            }

            function speakQuestion(text) {
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                    let utterance = new SpeechSynthesisUtterance(text);
                    if (preferredVoice) utterance.voice = preferredVoice;
                    utterance.rate = 0.95;
                    utterance.pitch = 1.0;

                    // Spike the amplitude every time a new word is spoken!
                    utterance.onboundary = function(e) {
                        if(e.name === 'word') currentAmplitude = 1.0;
                    };

                    utterance.onstart = function() {
                        document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'block');
                        document.getElementById('aiAvatarHead').style.borderColor = '#34d399';
                        
                        // Start dynamic JS visualizer
                        const bars = document.querySelectorAll('.spectrum-bar');
                        visualizerInterval = setInterval(() => {
                            currentAmplitude = Math.max(0.15, currentAmplitude - 0.1); // Decay slowly between words
                            bars.forEach(bar => {
                                // Calculate random jitter scaled by current word amplitude
                                let h = 6 + (Math.random() * 80 * currentAmplitude);
                                bar.style.height = h + 'px';
                            });
                        }, 50); // 20 FPS jitter
                    };
                    
                    utterance.onend = function() {
                        document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'none');
                        document.getElementById('aiAvatarHead').style.borderColor = '#8b5cf6';
                        if(visualizerInterval) clearInterval(visualizerInterval);
                    };

                    window.speechSynthesis.speak(utterance);
                }
            }

            function startInterviewSession() {
                document.getElementById('introContainer').style.display = 'none';
                document.getElementById('workspaceWrapper').style.display = 'block';
                document.getElementById('headerButtons').style.opacity = '1';
                document.getElementById('headerButtons').style.pointerEvents = 'auto';
                
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

                // Hold-to-Talk Gamified Logic
                const holdBtn = document.getElementById('holdToTalkBtn');
                if (holdBtn) {
                    const startHold = (e) => { e.preventDefault(); holdBtn.style.transform = 'scale(0.95)'; holdBtn.style.background = '#991b1b'; startRecording(); };
                    const endHold = (e) => { e.preventDefault(); holdBtn.style.transform = 'scale(1)'; holdBtn.style.background = ''; stopRecording(); };
                    
                    holdBtn.addEventListener('mousedown', startHold);
                    holdBtn.addEventListener('mouseup', endHold);
                    holdBtn.addEventListener('mouseleave', (e) => { if(isRecording) endHold(e); });
                    
                    holdBtn.addEventListener('touchstart', startHold, {passive: false});
                    holdBtn.addEventListener('touchend', endHold, {passive: false});
                    holdBtn.addEventListener('touchcancel', (e) => { if(isRecording) endHold(e); });
                }

                loadQuestion(0);
                
                document.getElementById('answerTextarea').addEventListener('input', triggerAnalysis);
                document.getElementById('sessionNotes').addEventListener('change', autoSaveState);
            }

            function loadQuestion(idx) {
                currentQIdx = idx;
                const q = questions[idx];
                
                document.getElementById('aiQuestionText').innerText = q.question_text;
                document.getElementById('qCounter').innerText = (idx + 1);

                // Append AI question to chat log if it's the first time seeing it
                appendChatMessage('interviewer', q.question_text);

                // Restore answer state if navigated back (though disabled in chat mode)
                document.getElementById('answerTextarea').value = answersData[idx] ? answersData[idx].text : '';
                
                speakQuestion(q.question_text);
                
                triggerAnalysis();
            }

            function repeatQuestion() {
                if(questions && questions[currentQIdx]) {
                    speakQuestion(questions[currentQIdx].question_text);
                }
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

                // Fillers mock (Improved regex to catch more natural conversational fillers)
                const fillerPattern = /\b(um|uh|like|you know|basically|i mean|sort of|kind of|literally)\b/gi;
                const matches = text.match(fillerPattern);
                const fillers = matches ? matches.length : 0;
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
                preRecordingText = document.getElementById('answerTextarea').value.trim();
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
                    
                    // Deduct 3 seconds per pause for a highly accurate WPM of ACTIVE speaking time
                    let activeSeconds = recTimerSeconds - (answersData[currentQIdx].pause_count * 3);
                    if (activeSeconds < 1) activeSeconds = 1;
                    const wpm = Math.round((wordCount / activeSeconds) * 60);
                    
                    document.getElementById('vaWpm').innerText = wpm;
                    answersData[currentQIdx].wpm = wpm;

                    // Body Language Tracking Logic via face-api.js
                    if (recTimerSeconds % 2 === 0) {
                        trackBodyLanguage();
                    }
                    
                    // Confidence Score Calc
                    let conf = 100 - (answersData[currentQIdx].filler_words * 2) - (answersData[currentQIdx].pause_count * 5);
                    if(wpm < 100) conf -= 10;
                    else if(wpm > 160) conf -= 5;
                    answersData[currentQIdx].confidence_score = Math.max(0, Math.min(100, conf));

                }, 1000);

                document.getElementById('faceScannerBox').style.display = 'block';
            }

            function pauseRecording() {
                if(recognition) recognition.stop();
                isRecording = false;
                clearInterval(recTimerInterval);
                document.getElementById('micStartBtn').style.display = 'block';
                document.getElementById('micStartBtn').innerText = 'Resume';
                document.getElementById('micPauseBtn').style.display = 'none';
                document.getElementById('faceScannerBox').style.display = 'none';
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
                formData.append('pause_count', answersData[currentQIdx].pause_count);
                formData.append('confidence_score', answersData[currentQIdx].confidence_score);
                formData.append('eye_contact_score', answersData[currentQIdx].eye_contact_score);
                formData.append('posture_score', answersData[currentQIdx].posture_score);
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

            function appendChatMessage(role, text) {
                const chatContainer = document.getElementById('chatTranscriptContainer');
                const bubble = document.createElement('div');
                bubble.style.padding = '12px 16px';
                bubble.style.borderRadius = '16px';
                bubble.style.maxWidth = '85%';
                bubble.style.lineHeight = '1.5';
                bubble.style.fontSize = '0.95rem';
                
                if (role === 'interviewer') {
                    bubble.style.background = 'rgba(139,92,246,0.15)';
                    bubble.style.border = '1px solid rgba(139,92,246,0.3)';
                    bubble.style.alignSelf = 'flex-start';
                    bubble.innerHTML = '<strong><i class="fa-solid fa-robot me-1"></i> Interviewer</strong><br>' + text;
                } else {
                    bubble.style.background = 'rgba(59,130,246,0.15)';
                    bubble.style.border = '1px solid rgba(59,130,246,0.3)';
                    bubble.style.alignSelf = 'flex-end';
                    bubble.innerHTML = '<strong><i class="fa-solid fa-user me-1"></i> You</strong><br>' + text;
                }
                
                chatContainer.appendChild(bubble);
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }

            function submitAnswer() {
                if(isRecording) stopRecording();
                
                const answerText = document.getElementById('answerTextarea').value.trim();
                if(!answerText) return alert("Please provide an answer before submitting.");

                // Optimistically append user answer to chat
                appendChatMessage('user', answerText);
                
                // Show thinking indicator
                const chatContainer = document.getElementById('chatTranscriptContainer');
                const thinkingBubble = document.createElement('div');
                thinkingBubble.id = 'thinkingBubble';
                thinkingBubble.style.padding = '12px 16px';
                thinkingBubble.style.borderRadius = '16px';
                thinkingBubble.style.maxWidth = '85%';
                thinkingBubble.style.alignSelf = 'flex-start';
                thinkingBubble.style.background = 'rgba(255,255,255,0.05)';
                thinkingBubble.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-muted me-2"></i> <em>AI is thinking...</em>';
                chatContainer.appendChild(thinkingBubble);
                chatContainer.scrollTop = chatContainer.scrollHeight;

                // Disable buttons
                document.querySelectorAll('.next-btn-class').forEach(el => el.disabled = true);
                
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('question_id', questions[currentQIdx].id);
                formData.append('answer_text', answerText);
                formData.append('is_skipped', false);
                formData.append('response_mode', responseMode);
                formData.append('wpm', answersData[currentQIdx].wpm);
                formData.append('voice_duration', answersData[currentQIdx].voice_duration);
                formData.append('filler_words_count', answersData[currentQIdx].filler_words);
                formData.append('pause_count', answersData[currentQIdx].pause_count);
                formData.append('confidence_score', answersData[currentQIdx].confidence_score);
                formData.append('eye_contact_score', answersData[currentQIdx].eye_contact_score);
                formData.append('posture_score', answersData[currentQIdx].posture_score);

                if (currentQIdx >= totalQuestions - 1) {
                    saveCurrentAnswer(false).then(() => {
                        finishInterview();
                    });
                    return;
                }

                formData.append('is_final_question', (currentQIdx === totalQuestions - 2));

                fetch('{{ route("interview.chatReply") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    const tb = document.getElementById('thinkingBubble');
                    if(tb) tb.remove();
                    document.querySelectorAll('.next-btn-class').forEach(el => el.disabled = false);

                    if (data.success) {
                        const newQ = {
                            id: data.next_question_id,
                            question_text: data.next_question_text
                        };
                        questions.push(newQ);
                        
                        answersData.push({
                            text: '',
                            is_skipped: false,
                            wpm: 0,
                            voice_duration: 0,
                            filler_words: 0,
                            pause_count: 0,
                            confidence_score: 85,
                            eye_contact_score: 90,
                            posture_score: 90
                        });

                        document.getElementById('answerTextarea').value = '';
                        preRecordingText = '';
                        currentQIdx++;
                        
                        document.getElementById('aiQuestionText').innerText = newQ.question_text;
                        document.getElementById('qCounter').innerText = (currentQIdx + 1);
                        
                        appendChatMessage('interviewer', newQ.question_text);
                        speakQuestion(newQ.question_text);
                    } else {
                        alert(data.error || 'An error occurred.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Network error.");
                    document.querySelectorAll('.next-btn-class').forEach(el => el.disabled = false);
                    const tb = document.getElementById('thinkingBubble');
                    if(tb) tb.remove();
                });
            }

            function skipQuestion() {
                if(isRecording) stopRecording();
                document.getElementById('answerTextarea').value = "[User skipped the question]";
                submitAnswer();
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

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    // Load face-api models
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/'),
        faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/')
    ]).then(() => {
        console.log("Face-api models loaded");
    }).catch(err => console.error("Error loading models", err));
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.driver === 'undefined') return;
        const driver = window.driver.js.driver;

        const stepsMobile = [
            { element: '.ai-avatar-panel', popover: { title: 'AI Avatar', description: 'Your AI interviewer. It will speak the questions out loud.', side: "bottom", align: 'start' }},
            { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here. Real-time metrics will update as you speak.', side: "top", align: 'start' }},
            { element: '#cameraPanel', popover: { title: 'Body Language', description: 'Real-time eye contact and posture analysis using your camera.', side: "top", align: 'start' }},
            { element: '#overallReadiness', popover: { title: 'AI Visualizer', description: 'Instant feedback on clarity, relevance, and professionalism.', side: "top", align: 'start' }},
            { element: '.star-item', popover: { title: 'STAR Analyzer', description: 'Tracks if you are using the Situation, Task, Action, Result framework.', side: "top", align: 'start' }},
            { element: '#voiceAnalyticsPanel', popover: { title: 'Voice Analytics', description: 'Measures speaking duration, pace (WPM), and filler word usage.', side: "top", align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '.ai-avatar-panel', popover: { title: 'AI Avatar', description: 'Your AI interviewer. It will speak the questions out loud.', side: "right", align: 'start' }},
            { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here. Real-time metrics will update as you speak.', side: "right", align: 'start' }},
            { element: '#cameraPanel', popover: { title: 'Body Language', description: 'Real-time eye contact and posture analysis using your camera.', side: "left", align: 'start' }},
            { element: '#overallReadiness', popover: { title: 'AI Visualizer', description: 'Instant feedback on clarity, relevance, and professionalism.', side: "left", align: 'start' }},
            { element: '.star-item', popover: { title: 'STAR Analyzer', description: 'Tracks if you are using the Situation, Task, Action, Result framework.', side: "left", align: 'start' }},
            { element: '#voiceAnalyticsPanel', popover: { title: 'Voice Analytics', description: 'Measures speaking duration, pace (WPM), and filler word usage.', side: "left", align: 'start' }}
        ];

        const driverObj = driver({
            showProgress: true,
            animate: true,
            popoverClass: document.documentElement.classList.contains('lm') ? 'driverjs-theme-light' : 'driverjs-theme-dark',
            steps: {{ $isMobile ? 'true' : 'false' }} ? stepsMobile : stepsDesktop,
            onDestroyStarted: () => {
                if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tutorial?")) {
                    driverObj.destroy();
                    localStorage.setItem('onboarding_completed_interview_session', 'true');
                }
            },
        });

        window.startOnboardingTour = function() {
            driverObj.drive();
        };

        if (!localStorage.getItem('onboarding_completed_interview_session')) {
            // We want this to show only AFTER the intro container is hidden.
            // So we'll let the user click "Begin Interview" first.
        }
        
        // Expose startOnboardingTour to be called after interview starts
        const originalStartInterview = window.startInterviewSession;
        window.startInterviewSession = function() {
            originalStartInterview();
            if (!localStorage.getItem('onboarding_completed_interview_session')) {
                setTimeout(() => {
                    startOnboardingTour();
                }, 1000);
            }
        };
    });
</script>
@endpush
@endsection

