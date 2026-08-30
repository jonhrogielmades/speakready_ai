@extends('mobile.layouts.app')
@section('title', 'Philippines Interview Challenge')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/game-session.css?v=1') }}" data-page-style="user-game-session">
<link rel="stylesheet" href="{{ asset('css/mobile/user/game-session-2.css?v=1') }}" data-page-style="user-game-session-2">
@endpush

@section('content')

<div class="db-section active" id="sec-learning-game-session">
    @if(session('active_game_session_id'))
        @php
            $sessionRecord = $gameSession ?? null;
            if ($sessionRecord) {
                $cameraCoachingEnabled = (bool) data_get($sessionRecord->accommodation_profile, 'camera_coaching', false);
                $num = $sessionRecord->num_questions ?? count($sessionRecord->questions ?? []);
                $questions = collect($sessionRecord->questions ?? [])->values()->map(function ($questionText, $index) {
                    return (object) [
                        'id' => $index,
                        'question_index' => $index,
                        'question_text' => $questionText,
                    ];
                });
            } else {
                $cameraCoachingEnabled = false;
                $num = 0;
                $questions = collect([]);
            }
        @endphp

        @if($sessionRecord && $questions->count() > 0)

        <!-- Get Ready Overlay -->
        <div id="get-ready-overlay">
            <h2 style="font-weight:800;text-transform:uppercase;margin-bottom:10px;color:var(--tx)">Level {{ $gameLevel->level_number }}</h2>
            <h1 id="countdown-text">3</h1>
            <p style="font-weight:600;color:var(--tx3);margin-top:20px;">Prepare your mic...</p>
        </div>

        <!-- HUD Banner -->
        <div class="hud-banner d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="hud-title-wrap">
                <div class="hud-title-row d-flex align-items-center gap-2 mb-1">
                    <span class="badge" style="background:var(--pur);color:#fff;font-size:0.8rem;"><i class="fa-solid fa-gamepad me-1"></i> PH CHALLENGE</span>
                    <h4 class="hud-title" style="font-size:1.4rem;font-weight:800;margin:0;color:var(--tx)">Level {{ $gameLevel->level_number }}: {{ $gameLevel->title }}</h4>
                </div>
                @if($gameLevel->learning_objective)
                    <div style="font-size:0.86rem;color:var(--tx2);line-height:1.45;max-width:760px;">{{ $gameLevel->learning_objective }}</div>
                @endif

            </div>
            
            <div class="hud-badges d-flex flex-wrap gap-2 align-items-center">
                @if($gameLevel->time_limit_seconds)
                    <div class="badge" style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid #ef4444;padding:8px 12px;font-size:0.9rem;">
                        <i class="fa-solid fa-stopwatch me-1"></i> <span id="game-timer">{{ $gameLevel->time_limit_seconds }}s</span>
                    </div>
                @endif
                <div class="badge" style="background:rgba(52,211,153,0.1);color:#34d399;border:1px solid #34d399;padding:8px 12px;font-size:0.9rem;">
                    <i class="fa-solid fa-bullseye me-1"></i> Goal: {{ $gameLevel->required_score }}%+
                </div>
                <div class="badge" style="background:rgba(59,130,246,0.1);color:#60a5fa;border:1px solid #60a5fa;padding:8px 12px;font-size:0.9rem;">
                    <i class="fa-solid fa-clock me-1"></i> <span id="challengeTimer">00:00</span>
                </div>

            </div>
        </div>

        <div id="workspaceWrapper" style="display:none;">
        <div class="row g-4 m-0" id="workspaceRow">
            <!-- Main Content Area -->
            <div class="col-lg-8 px-0 pe-lg-3">
                <!-- Progress Tracker Removed by User -->

                <!-- Simulated AI Video Avatar Panel -->
                <div class="panel p-0 ai-avatar-panel" style="overflow:hidden;border:1px solid var(--bd);background:#000;position:relative;height:250px;border-radius:18px;margin-bottom:20px;">
                    <span class="question-counter-badge" id="qCounter">1/10</span>
                    <span class="badge interviewer-panel-badge"><i class="fa-solid fa-bolt me-1"></i> {{ $sessionRecord->company_persona ?? 'AI Coach' }}</span>
                    @if($cameraCoachingEnabled)
                    <!-- Optional mobile camera framing preview -->
                    <div class="mobile-camera-preview d-block d-lg-none" style="position:absolute; top:15px; right:15px; width:80px; height:105px; border-radius:8px; overflow:hidden; border:2px solid rgba(255,255,255,0.3); z-index:50; box-shadow: 0 4px 15px rgba(0,0,0,0.6);">
                        <video id="userCameraMobile" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);background:#222;"></video>
                    </div>
                    @endif

                    <div id="aiAvatarContainer" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background: linear-gradient(135deg, #1e1b4b, #312e81);">
                        <div class="avatar-wrapper" id="aiAvatarHead" style="width:100px;height:100px;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;transition:border-color 0.3s;">
                            <!-- The Image Container (with border, glow, and clipping for the image itself) -->
                            <div style="width:100%;height:100%;background:rgba(255,255,255,0.1);border-radius:50%;border:3px solid #8b5cf6;overflow:hidden;position:relative;z-index:10;box-shadow: 0 0 15px rgba(139,92,246,0.3);">
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
                    <div class="ai-question-overlay">
                        <div class="ai-question-wrap">
                            <div style="width: 100%;">
                                <span class="badge mb-2 game-coach-badge" style="background:var(--pur);color:white;font-size:0.75rem;"><i class="fa-solid fa-bolt me-1"></i> {{ $sessionRecord->company_persona ?? 'AI Coach' }}</span>
                                <div id="aiQuestionText" class="custom-scrollbar" style="color:white;font-size:1.1rem;font-weight:600;line-height:1.4; max-height: 90px; overflow-y: auto; padding-right: 10px;">Loading your first question...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unified challenge controls -->
                <div class="session-nav-row" id="gameSessionControls">
                    <button type="button" class="btn btn-outline-info session-nav-icon" onclick="repeatQuestion()" aria-label="Repeat question" title="Repeat question"><i class="fa-solid fa-volume-high"></i></button>
                    <button type="button" class="btn btn-outline-secondary session-nav-icon prev-btn-class" onclick="prevQuestion()" disabled aria-label="Previous question" title="Previous question"><i class="fa-solid fa-arrow-left"></i></button>
                    <button type="button" class="btn btn-outline-warning session-nav-skip skip-btn-class" onclick="skipQuestion()">Skip <i class="fa-solid fa-forward-step ms-1"></i></button>
                    <button type="button" class="bgrd btn session-nav-next next-btn-class text-white" onclick="submitAnswer()"><span class="next-label-full">Next Question</span><span class="next-label-short">Next</span><i class="fa-solid fa-arrow-right ms-2"></i></button>
                </div>

                <!-- Answer Response System -->
                <div class="panel response-panel mb-4">
                    <div class="panel-title">
                        <i class="fa-solid fa-pen-nib me-2"></i>
                        <span class="panel-title-text">Your Response</span>
                        <div class="response-title-actions">
                            @if($sessionRecord->game_level_id)
                                <span class="badge game-mode-badge"><i class="fa-solid fa-gamepad me-1"></i> CHALLENGE MODE</span>
                            @endif
                        </div>
                    </div>
                    
                    <form id="answerForm">
                        <div id="voiceControls" style="display:none;margin-bottom:20px;background:rgba(59,130,246,.05);padding:15px;border-radius:12px;border:1px solid rgba(59,130,246,.2)">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div style="font-weight:600;font-size:.9rem;color:#60a5fa"><i class="fa-solid fa-waveform me-2"></i>Voice Recording</div>
                                <span id="recordingTimer" style="font-family:monospace;font-size:1.1rem;color:#f87171;display:none;">00:00</span>
                            </div>
                            
                            @if($sessionRecord->game_level_id)
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

                        <textarea id="answerTextarea" class="oinp mb-2" style="min-height:200px;font-size:.95rem" placeholder="Type your answer here, or use voice to auto-transcribe..."></textarea>
                        
                        <div class="answer-meta-row response-count-bar d-flex justify-content-between align-items-center mb-4">
                            <div style="font-size:.8rem;color:var(--tx3)">
                                <span id="wordCount">0 words</span> • <span id="charCount">0 characters</span>
                                <span id="autoSaveIndicator" class="ms-3 text-success" style="display:none;"><i class="fa-solid fa-check me-1"></i>Auto-saved</span>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Side Panels -->
            <div class="col-lg-4 px-0 ps-lg-3">
                <!-- Session Navigation (Mobile fallback / Overview) -->
                @if($cameraCoachingEnabled)
                <!-- Optional descriptive body-language coach; never used in readiness scoring. -->
                <div class="panel d-none d-lg-block" id="cameraPanel">
                    <div class="panel-title"><i class="fa-solid fa-camera-web me-2"></i> Optional Body-Language Coach</div>
                    <div style="position:relative;background:#000;height:180px;border-radius:12px;margin-bottom:15px;overflow:hidden;display:flex;align-items:center;justify-content:center">
                        <video id="userCamera" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);"></video>
                        <div class="face-scanner-box" id="faceScannerBox" style="display:none;position:absolute;width:120px;height:120px;border:2px solid #34d399;border-radius:12px;box-shadow:0 0 15px rgba(52,211,153,0.3);transition:all 0.3s ease;">
                            <div class="scan-line" style="width:100%;height:2px;background:#34d399;position:absolute;top:0;animation: scanAnim 2s infinite linear;box-shadow:0 0 8px #34d399;"></div>
                        </div>
                        <div style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.6);padding:2px 8px;border-radius:4px;font-size:.7rem;color:#34d399"><i class="fa-solid fa-circle text-success pulse-anim" style="font-size:.5rem;margin-right:4px"></i> Private Preview</div>
                    </div>
                    <div class="stat-row"><span>Face in frame</span><span id="stEyeContact">Waiting</span></div>
                    <div class="stat-row"><span>Hands / gestures</span><span id="stGesture">Waiting</span></div>
                    <div class="stat-row"><span>Shoulders / posture</span><span id="stPose">Waiting</span></div>
                    <div class="stat-row"><span>Movement steadiness</span><span id="stMovement">Waiting</span></div>
                    <div class="stat-row"><span>Head alignment</span><span id="stPosture">Optional - not scored</span></div>
                </div>
                @endif

                @php $successChecklist = $gameLevel->guidance_checklist; @endphp
                @if($gameLevel->skill_focus || $gameLevel->learning_objective || $successChecklist || $gameLevel->retry_hint)
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-bullseye me-2"></i> Challenge Brief</div>
                    @if($gameLevel->skill_focus)
                        <div class="badge mb-3" style="background:rgba(56,189,248,0.12);color:#38bdf8;border:1px solid rgba(56,189,248,0.35);padding:8px 10px;font-size:0.82rem;">
                            <i class="fa-solid fa-graduation-cap me-1"></i> {{ $gameLevel->skill_focus }}
                        </div>
                    @endif
                    @if($gameLevel->learning_objective)
                        <div style="font-size:0.84rem;color:var(--tx2);line-height:1.5;margin-bottom:14px;">{{ $gameLevel->learning_objective }}</div>
                    @endif
                    @if($successChecklist)
                        <div style="font-size:0.78rem;color:var(--tx3);font-weight:700;margin-bottom:8px;text-transform:uppercase;">Success checklist</div>
                        <div class="d-flex flex-column gap-2 mb-3">
                            @foreach($successChecklist as $criterion)
                                <div style="display:flex;gap:8px;align-items:flex-start;font-size:0.82rem;color:var(--tx2);line-height:1.4;">
                                    <i class="fa-solid fa-check" style="color:#34d399;margin-top:2px;"></i>
                                    <span>{{ $criterion }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if($gameLevel->retry_hint)
                        <div style="font-size:0.82rem;color:#fbbf24;background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.28);border-radius:8px;padding:10px;line-height:1.45;">
                            <i class="fa-solid fa-lightbulb me-1"></i>{{ $gameLevel->retry_hint }}
                        </div>
                    @endif
                </div>
                @endif

                <!-- AI Visualizer Panel -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-chart-pie me-2"></i> AI Visualizer</div>
                    <div class="text-center mb-3">
                        <div style="font-size:2rem;font-weight:700;color:#34d399" id="overallReadiness">--%</div>
                        <div style="font-size:.75rem;color:var(--tx3)">Practice coverage · not a readiness score</div>
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

                <!-- Challenge Notes -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-clipboard me-2"></i> Challenge Notes</div>
                    <textarea id="sessionNotes" class="oinp" style="min-height:100px;font-size:.85rem;padding:10px" placeholder="Private notes, key reminders, etc..."></textarea>
                </div>
            </div>
        </div>
        </div>

        <!-- Intro container removed for automatic start via get-ready overlay -->

        <form id="finishForm" action="{{ route('user.game.finish') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="game_session_id" value="{{ $sessionRecord->id }}">
            <input type="hidden" name="duration_seconds" id="formDuration">
            <input type="hidden" name="notes" id="formNotes">
        </form>

        <div class="modal fade challenge-finish-modal" id="challengeFinishModal" tabindex="-1" aria-labelledby="challengeFinishModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="challenge-score-spinner">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                        </div>
                        <h5 id="challengeFinishModalTitle" style="font-weight:900;margin-bottom:8px;color:var(--tx);">Scoring Challenge</h5>
                        <p id="challengeFinishStatus" style="margin:0;color:var(--tx2);line-height:1.5;">Saving your final answer...</p>
                        <div style="margin-top:16px;font-size:0.8rem;color:var(--tx3);">Your result modal will open automatically after scoring.</div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $initialQuestionIndex = min(max(0, (int) ($sessionRecord->current_question_index ?? 0)), max(0, $questions->count() - 1));
            $answersByIndex = $sessionRecord->relationLoaded('answers')
                ? $sessionRecord->answers->keyBy('question_index')
                : collect();
            $initialAnswersData = $questions->map(function ($question) use ($answersByIndex) {
                $answer = $answersByIndex->get($question->question_index);

                return [
                    'text' => $answer && ! $answer->is_skipped ? (string) ($answer->answer_text ?? '') : '',
                    'is_skipped' => (bool) ($answer?->is_skipped ?? false),
                    'wpm' => (int) ($answer?->wpm ?? 0),
                    'voice_duration' => (int) ($answer?->voice_duration ?? 0),
                    'filler_words' => (int) ($answer?->filler_words_count ?? 0),
                    'pause_count' => (int) ($answer?->pause_count ?? 0),
                    'confidence_score' => (int) ($answer?->confidence_score ?? 0),
                    'eye_contact_score' => (int) ($answer?->eye_contact_score ?? 0),
                    'posture_score' => (int) ($answer?->posture_score ?? 0),
                ];
            })->values();
        @endphp

        <script>
            const questions = {!! json_encode($questions) !!};
            const gameSessionId = {{ (int) $sessionRecord->id }};
            const responseMode = "{{ $sessionRecord->response_mode }}";
            const cameraCoachingEnabled = @json($cameraCoachingEnabled);
            let currentQIdx = {{ $initialQuestionIndex }};
            let timerSeconds = 0;
            let timerInterval;
            let isFinishingChallenge = false;
            
            // Answers state
            let answersData = @json($initialAnswersData);

            // Voice state and optional, non-scoring body-language state
            let recognition = null;
            let recognitionActive = false;
            let shouldAutoRestartRecognition = false;
            let isRecording = false;
            let recTimerSeconds = 0;
            let recTimerInterval;
            window.bodyLanguageModelState = window.bodyLanguageModelState || { ready: false, failed: false, poseLandmarker: null, handLandmarker: null };
            let gameCameraMovementBaseline = null;
            let preRecordingText = '';
            let committedSpeechTranscript = '';
            let liveSpeechInterim = '';
            let lastCommittedSpeech = '';
            let lastCommittedAt = 0;

            const BrowserSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const speechLocale = document.documentElement.dataset.speechLocale || navigator.language || 'en-US';
            const speechLanguage = speechLocale.split('-')[0];
            const duplicateSafeWordSet = new Set([
                'i', "i'm", 'the', 'a', 'an', 'and', 'to', 'of', 'for', 'in', 'on', 'it', 'is', 'was',
                'were', 'am', 'are', 'my', 'we', 'you', 'that', 'this', 'with', 'um', 'uh', 'like'
            ]);

            function cleanTranscriptText(value) {
                return String(value || '').replace(/\s+/g, ' ').trim();
            }

            function normalizeTranscriptForMatch(value) {
                return cleanTranscriptText(value)
                    .toLocaleLowerCase(speechLocale)
                    .replace(/[^\p{L}\p{N}'\u2019\s]/gu, '')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function wordsForTranscript(value) {
                return cleanTranscriptText(value).split(/\s+/).filter(Boolean);
            }

            function appendWithoutOverlap(existing, addition) {
                const existingClean = cleanTranscriptText(existing);
                const additionClean = cleanTranscriptText(addition);
                if (!existingClean) return additionClean;
                if (!additionClean) return existingClean;

                const existingWords = wordsForTranscript(existingClean);
                const additionWords = wordsForTranscript(additionClean);
                const existingNormalized = existingWords.map(normalizeTranscriptForMatch);
                const additionNormalized = additionWords.map(normalizeTranscriptForMatch);
                const maxOverlap = Math.min(existingNormalized.length, additionNormalized.length, 24);
                let overlap = 0;

                for (let size = maxOverlap; size > 0; size--) {
                    const existingTail = existingNormalized.slice(existingNormalized.length - size).join(' ');
                    const additionHead = additionNormalized.slice(0, size).join(' ');
                    if (existingTail && existingTail === additionHead) {
                        overlap = size;
                        break;
                    }
                }

                const remainder = additionWords.slice(overlap).join(' ');
                return cleanTranscriptText(existingClean + (remainder ? ' ' + remainder : ''));
            }

            function shouldCollapseDuplicateWindow(size, normalizedPhrase) {
                if (!normalizedPhrase) return false;
                if (size >= 2) return true;
                return normalizedPhrase.length > 2 || duplicateSafeWordSet.has(normalizedPhrase);
            }

            function collapseRepeatedSpeech(text) {
                const words = wordsForTranscript(text);
                if (words.length < 2) return cleanTranscriptText(text);

                let index = 0;
                while (index < words.length) {
                    let collapsed = false;
                    const maxWindow = Math.min(12, Math.floor((words.length - index) / 2));

                    for (let size = maxWindow; size >= 1; size--) {
                        const first = words.slice(index, index + size).map(normalizeTranscriptForMatch).join(' ');
                        const second = words.slice(index + size, index + (size * 2)).map(normalizeTranscriptForMatch).join(' ');

                        if (first && first === second && shouldCollapseDuplicateWindow(size, first)) {
                            words.splice(index + size, size);
                            index = Math.max(0, index - size);
                            collapsed = true;
                            break;
                        }
                    }

                    if (!collapsed) index++;
                }

                return cleanTranscriptText(words.join(' '));
            }

            function mergeTranscriptParts(...parts) {
                let merged = '';
                parts.forEach(part => {
                    const clean = cleanTranscriptText(part);
                    if (clean) merged = appendWithoutOverlap(merged, clean);
                });
                return collapseRepeatedSpeech(merged);
            }

            function bestSpeechAlternative(result) {
                let best = result[0] || null;
                for (let i = 1; i < result.length; i++) {
                    if ((result[i].confidence || 0) > (best.confidence || 0)) {
                        best = result[i];
                    }
                }
                return best ? best.transcript : '';
            }

            function resetSpeechRecognitionBufferFromTextarea() {
                const ta = document.getElementById('answerTextarea');
                preRecordingText = ta ? cleanTranscriptText(ta.value) : '';
                committedSpeechTranscript = '';
                liveSpeechInterim = '';
                lastCommittedSpeech = '';
                lastCommittedAt = 0;
            }

            function commitSpeechSegment(segment) {
                const cleanSegment = collapseRepeatedSpeech(cleanTranscriptText(segment));
                if (!cleanSegment) return;

                const normalized = normalizeTranscriptForMatch(cleanSegment);
                const now = Date.now();
                if (normalized && normalized === lastCommittedSpeech && (now - lastCommittedAt) < 5000) {
                    return;
                }

                committedSpeechTranscript = collapseRepeatedSpeech(appendWithoutOverlap(committedSpeechTranscript, cleanSegment));
                lastCommittedSpeech = normalized;
                lastCommittedAt = now;
            }

            function renderSpeechTranscript() {
                const ta = document.getElementById('answerTextarea');
                if (!ta) return;

                const recognizedTranscript = mergeTranscriptParts(committedSpeechTranscript, liveSpeechInterim);
                ta.value = mergeTranscriptParts(preRecordingText, recognizedTranscript);
                triggerAnalysis();
            }

            function startSpeechRecognitionEngine() {
                if (!recognition || recognitionActive || !isRecording || !shouldAutoRestartRecognition) return;

                try {
                    recognition.start();
                    recognitionActive = true;
                } catch (error) {
                    if (!error || error.name !== 'InvalidStateError') {
                        console.error('Speech recognition failed to start:', error);
                    }
                }
            }

            function finalizeInterimTranscript() {
                if (!liveSpeechInterim) return;
                commitSpeechSegment(liveSpeechInterim);
                liveSpeechInterim = '';
                renderSpeechTranscript();
            }

            let lastSpeechEnd = 0;
            if (BrowserSpeechRecognition) {
                recognition = new BrowserSpeechRecognition();
                recognition.continuous = true;
                recognition.interimResults = true;
                recognition.lang = speechLocale;
                recognition.maxAlternatives = 3;

                recognition.onstart = function() {
                    recognitionActive = true;
                };

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
                    const interimParts = [];

                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        const transcript = bestSpeechAlternative(event.results[i]);
                        if (!transcript) continue;

                        if (event.results[i].isFinal) {
                            commitSpeechSegment(transcript);
                        } else {
                            interimParts.push(transcript);
                        }
                    }

                    liveSpeechInterim = cleanTranscriptText(interimParts.join(' '));
                    renderSpeechTranscript();
                };

                recognition.onerror = function(event) {
                    console.warn('Speech recognition error:', event.error || event);
                    if (['not-allowed', 'service-not-allowed', 'audio-capture'].includes(event.error)) {
                        shouldAutoRestartRecognition = false;
                    }
                };

                recognition.onend = function() {
                    recognitionActive = false;
                    if (shouldAutoRestartRecognition && isRecording) {
                        setTimeout(startSpeechRecognitionEngine, 250);
                    }
                };
            }

            function initCamera() {
                if (!cameraCoachingEnabled) return;
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
            function setGameCameraStat(id, content, className = 'text-secondary', asHtml = false) {
                const element = document.getElementById(id);
                if (!element) return;
                if (asHtml) {
                    element.innerHTML = content;
                } else {
                    element.textContent = content;
                }
                element.className = className;
            }

            function gameVisibleLandmark(landmark, threshold = 0.35) {
                if (!landmark || !Number.isFinite(Number(landmark.x)) || !Number.isFinite(Number(landmark.y))) return false;
                return Number(landmark.visibility ?? landmark.presence ?? 1) >= threshold;
            }

            function gameCenterOf(points) {
                const usable = points.filter(point => point && Number.isFinite(Number(point.x)) && Number.isFinite(Number(point.y)));
                if (usable.length === 0) return null;
                const total = usable.reduce(
                    (point, current) => ({ x: point.x + current.x, y: point.y + current.y }),
                    { x: 0, y: 0 }
                );
                return { x: total.x / usable.length, y: total.y / usable.length };
            }

            function gamePointDistance(left, right) {
                if (!left || !right) return null;
                return Math.hypot(Number(left.x) - Number(right.x), Number(left.y) - Number(right.y));
            }

            function gameDetectVideoFrame(landmarker, video, timestamp) {
                if (!landmarker || typeof landmarker.detectForVideo !== 'function') return null;
                try {
                    return landmarker.detectForVideo(video, timestamp);
                } catch (error) {
                    return landmarker.detectForVideo(video);
                }
            }

            async function trackBodyLanguage() {
                const bodyLanguageState = window.bodyLanguageModelState || {};
                const canUseBodyModels = Boolean(bodyLanguageState.ready && bodyLanguageState.poseLandmarker && bodyLanguageState.handLandmarker);
                const canUseFaceModel = typeof faceapi !== 'undefined';
                if (!cameraCoachingEnabled || (!canUseBodyModels && !canUseFaceModel)) return;
                const video = document.getElementById('userCamera');
                if (!video || !video.srcObject) return;

                try {
                    let detection = null;
                    if (canUseFaceModel) {
                        try {
                            detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                        } catch (faceError) {
                            console.error("Face framing tracking error", faceError);
                        }
                    }

                    let poseLandmarks = null;
                    let handLandmarks = [];
                    if (canUseBodyModels) {
                        const timestamp = performance.now();
                        const poseResult = gameDetectVideoFrame(bodyLanguageState.poseLandmarker, video, timestamp);
                        const handResult = gameDetectVideoFrame(bodyLanguageState.handLandmarker, video, timestamp);
                        poseLandmarks = Array.isArray(poseResult?.landmarks) && poseResult.landmarks.length > 0
                            ? poseResult.landmarks[0]
                            : null;
                        handLandmarks = Array.isArray(handResult?.landmarks)
                            ? handResult.landmarks.slice(0, 2)
                            : [];
                    }

                    const poseDetected = Array.isArray(poseLandmarks) && poseLandmarks.length > 0;
                    const faceVisible = Boolean(detection || (poseDetected && gameVisibleLandmark(poseLandmarks[0])));
                    let shouldersVisible = false;
                    let shouldersLevel = null;
                    let uprightPosture = null;
                    let poseCameraFacing = null;
                    const movementPoints = {};

                    if (poseDetected) {
                        const nose = poseLandmarks[0];
                        const leftShoulder = poseLandmarks[11];
                        const rightShoulder = poseLandmarks[12];
                        const leftHip = poseLandmarks[23];
                        const rightHip = poseLandmarks[24];
                        const noseVisible = gameVisibleLandmark(nose);
                        shouldersVisible = gameVisibleLandmark(leftShoulder) && gameVisibleLandmark(rightShoulder);
                        const hipsVisible = gameVisibleLandmark(leftHip) && gameVisibleLandmark(rightHip);
                        const shoulderMidpoint = shouldersVisible ? gameCenterOf([leftShoulder, rightShoulder]) : null;
                        const hipMidpoint = hipsVisible ? gameCenterOf([leftHip, rightHip]) : null;
                        const shoulderWidth = shouldersVisible ? Math.max(0.01, gamePointDistance(leftShoulder, rightShoulder) ?? 0.01) : 0.01;
                        if (noseVisible) movementPoints.nose = { x: nose.x, y: nose.y };
                        if (shoulderMidpoint) {
                            movementPoints.shoulders = shoulderMidpoint;
                            shouldersLevel = Math.abs(Number(leftShoulder.y) - Number(rightShoulder.y)) <= 0.065;
                        }
                        if (noseVisible && shoulderMidpoint) {
                            poseCameraFacing = Math.abs((Number(nose.x) - shoulderMidpoint.x) / shoulderWidth) <= 0.38;
                            uprightPosture = Math.abs((Number(nose.x) - shoulderMidpoint.x) / shoulderWidth) <= 0.45;
                        }
                        if (shoulderMidpoint && hipMidpoint) {
                            const torsoHeight = Math.max(0.01, Math.abs(hipMidpoint.y - shoulderMidpoint.y));
                            uprightPosture = Math.abs((shoulderMidpoint.x - hipMidpoint.x) / torsoHeight) <= 0.28;
                        }
                    }

                    const handCenters = handLandmarks
                        .map(hand => gameCenterOf(Array.isArray(hand) ? hand : []))
                        .filter(Boolean);
                    handCenters.forEach((center, index) => {
                        movementPoints['hand' + index] = center;
                    });

                    let movementScore = null;
                    let gestureActive = false;
                    if (gameCameraMovementBaseline && Object.keys(movementPoints).length > 0) {
                        const distances = Object.entries(movementPoints)
                            .map(([key, point]) => gamePointDistance(point, gameCameraMovementBaseline[key]))
                            .filter(distance => Number.isFinite(distance));
                        if (distances.length > 0) {
                            movementScore = Math.min(100, Math.round((distances.reduce((total, distance) => total + distance, 0) / distances.length) * 650));
                        }
                        gestureActive = handCenters.some((center, index) => {
                            const distance = gamePointDistance(center, gameCameraMovementBaseline['hand' + index]);
                            return Number.isFinite(distance) && distance >= 0.045;
                        });
                    }
                    gameCameraMovementBaseline = movementPoints;

                    setGameCameraStat('stEyeContact', faceVisible ? '<i class="fa-solid fa-check me-1"></i>Visible' : '<i class="fa-solid fa-circle-info me-1"></i>Move into frame', faceVisible ? 'text-success' : 'text-warning', true);
                    setGameCameraStat('stPosture', faceVisible ? (poseCameraFacing === false ? 'Head turned estimate' : 'Camera-facing estimate') : 'Optional - not scored', faceVisible ? (poseCameraFacing === false ? 'text-warning' : 'text-success') : 'text-secondary');
                    setGameCameraStat('stGesture', handLandmarks.length > 0 ? (gestureActive ? 'Gesture movement' : handLandmarks.length + ' hand(s) visible') : 'Hands not visible', handLandmarks.length > 0 ? 'text-success' : 'text-secondary');
                    setGameCameraStat('stPose', shouldersVisible ? (shouldersLevel && uprightPosture !== false ? 'Balanced upper body' : 'Posture cue available') : (poseDetected ? 'Partial pose estimate' : 'Pose not detected'), shouldersVisible ? (shouldersLevel && uprightPosture !== false ? 'text-success' : 'text-warning') : 'text-secondary');
                    setGameCameraStat('stMovement', movementScore === null ? 'Calibrating' : (movementScore >= 45 ? 'Higher movement' : 'Steady'), movementScore === null ? 'text-secondary' : (movementScore >= 45 ? 'text-warning' : 'text-success'));
                } catch(e) {
                    console.error("Tracking error", e);
                }
            }

            let visualizerInterval = null;
            let currentAmplitude = 0.2;
            let preferredVoice = null;
            let autoStartAfterQuestionTimer = null;
            let questionSpeechToken = 0;

            function isVoiceTranscriptionMode() {
                return responseMode === 'voice' || responseMode === 'hybrid' || responseMode === 'voice_and_text';
            }

            function scheduleAutoTranscriptionStart(token) {
                if (token !== questionSpeechToken) return;
                clearTimeout(autoStartAfterQuestionTimer);
                if (!isVoiceTranscriptionMode()) return;

                autoStartAfterQuestionTimer = setTimeout(() => {
                    if (token !== questionSpeechToken || isRecording) return;
                    startRecording({ silent: true });
                }, 450);
            }

            // Initialize preferred voice
            function loadVoices() {
                let voices = window.speechSynthesis.getVoices();
                if (voices.length > 0) {
                    preferredVoice = voices.find(v => v.lang === speechLocale && (v.name.includes('Google') || v.name.includes('Premium') || v.name.includes('Natural') || v.name.includes('Siri'))) || voices.find(v => v.lang === speechLocale) || voices.find(v => v.lang.startsWith(speechLanguage)) || voices.find(v => v.lang.startsWith('en')) || voices[0];
                }
            }
            if ('speechSynthesis' in window) {
                window.speechSynthesis.onvoiceschanged = loadVoices;
                loadVoices();
            }

            function speakQuestion(text) {
                questionSpeechToken++;
                const token = questionSpeechToken;

                if (isRecording) {
                    pauseRecording();
                }

                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                    let utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = speechLocale;
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
                        scheduleAutoTranscriptionStart(token);
                    };

                    window.speechSynthesis.speak(utterance);
                } else {
                    scheduleAutoTranscriptionStart(token);
                }
            }

            function startChallengeSession() {
                document.getElementById('workspaceWrapper').style.display = 'block';
                if (cameraCoachingEnabled) initCamera();
                
                if(isVoiceTranscriptionMode()) {
                    document.getElementById('voiceControls').style.display = 'block';
                    document.getElementById('voiceAnalyticsPanel').style.display = 'block';
                }

                timerInterval = setInterval(() => {
                    timerSeconds++;
                    const m = Math.floor(timerSeconds / 60).toString().padStart(2, '0');
                    const s = (timerSeconds % 60).toString().padStart(2, '0');
                    const challengeTimer = document.getElementById('challengeTimer');
                    if (challengeTimer) challengeTimer.innerText = m + ':' + s;
                    
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

                loadQuestion(currentQIdx);
                
                document.getElementById('answerTextarea').addEventListener('input', triggerAnalysis);
                document.getElementById('sessionNotes').addEventListener('change', autoSaveState);
            }

            function loadQuestion(idx) {
                currentQIdx = idx;
                const q = questions[idx];
                
                document.getElementById('aiQuestionText').innerText = q.question_text;
                document.getElementById('qCounter').innerText = (idx + 1) + '/' + questions.length;

                // Restore answer state if navigated back
                document.getElementById('answerTextarea').value = answersData[idx].text;
                resetSpeechRecognitionBufferFromTextarea();
                
                speakQuestion(q.question_text);
                
                document.querySelectorAll('.prev-btn-class').forEach(el => el.disabled = (idx === 0));
                
                if (idx === questions.length - 1) {
                    document.querySelectorAll('.next-btn-class').forEach(el => {
                        el.innerHTML = '<span class="next-label-full">Finish Challenge</span><span class="next-label-short">Finish</span><i class="fa-solid fa-flag-checkered ms-2"></i>';
                        el.classList.add('btn-success');
                        el.classList.remove('bgrd', 'btn-primary');
                    });
                } else {
                    document.querySelectorAll('.next-btn-class').forEach(el => {
                        el.innerHTML = '<span class="next-label-full">Next Question</span><span class="next-label-short">Next</span><i class="fa-solid fa-arrow-right ms-2"></i>';
                        el.classList.add('bgrd');
                        el.classList.remove('btn-success');
                    });
                }
                
                triggerAnalysis();
            }

            function repeatQuestion() {
                if(questions && questions[currentQIdx]) {
                    speakQuestion(questions[currentQIdx].question_text);
                }
            }

            function prevQuestion() {
                if(isRecording) stopRecording();
                if (currentQIdx > 0) {
                    loadQuestion(currentQIdx - 1);
                }
            }

            function triggerAnalysis() {
                const text = document.getElementById('answerTextarea').value;
                const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
                const charCount = text.length;
                
                document.getElementById('wordCount').innerText = wordCount + ' words';
                document.getElementById('charCount').innerText = charCount + ' characters';

                // Local STAR estimate from answer text.
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

                // Local readiness estimate shown before server-side scoring.
                let readiness = Math.min(100, Math.max(0, wordCount * 2));
                if(wordCount === 0) readiness = 0;
                document.getElementById('overallReadiness').innerText = readiness + '%';
                document.getElementById('metClarity').innerText = (readiness > 0 ? Math.min(100, readiness + 10) : 0) + '%';
                document.getElementById('metRelevance').innerText = (readiness > 0 ? Math.min(100, readiness + 5) : 0) + '%';
                document.getElementById('metGrammar').innerText = (readiness > 0 ? Math.min(100, readiness + 15) : 0) + '%';
                document.getElementById('metProf').innerText = (readiness > 0 ? Math.min(100, readiness + 8) : 0) + '%';

                // Local filler-word estimate.
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

            function startRecording(options = {}) {
                const silent = options && options.silent === true;
                if(!recognition) {
                    if(!silent) alert("Speech recognition not supported in this browser.");
                    return;
                }
                if (isRecording) return;

                resetSpeechRecognitionBufferFromTextarea();
                lastSpeechEnd = 0;
                shouldAutoRestartRecognition = true;
                isRecording = true;
                startSpeechRecognitionEngine();
                document.getElementById('micStartBtn').style.display = 'none';
                document.getElementById('micPauseBtn').style.display = 'block';
                document.getElementById('micStopBtn').style.display = 'block';
                document.getElementById('recordingTimer').style.display = 'block';
                clearInterval(recTimerInterval);
                
                recTimerInterval = setInterval(() => {
                    recTimerSeconds++;
                    const m = Math.floor(recTimerSeconds / 60).toString().padStart(2, '0');
                    const s = (recTimerSeconds % 60).toString().padStart(2, '0');
                    document.getElementById('recordingTimer').innerText = m + ':' + s;
                    document.getElementById('vaDuration').innerText = recTimerSeconds + 's';
                    answersData[currentQIdx].voice_duration = recTimerSeconds;
                    
                    const wordCount = document.getElementById('answerTextarea').value.trim().split(/\s+/).filter(w=>w.length>0).length;
                    let activeSeconds = recTimerSeconds - (answersData[currentQIdx].pause_count * 3);
                    if (activeSeconds < 1) activeSeconds = 1;
                    const wpm = Math.round((wordCount / activeSeconds) * 60);
                    document.getElementById('vaWpm').innerText = wpm;
                    answersData[currentQIdx].wpm = wpm;

                    // Optional body-language guidance is descriptive and never affects scoring.
                    if (cameraCoachingEnabled && recTimerSeconds % 2 === 0) {
                        trackBodyLanguage();
                    }

                }, 1000);

                const scannerBox = document.getElementById('faceScannerBox');
                if (scannerBox) scannerBox.style.display = 'block';
            }

            function pauseRecording() {
                finalizeInterimTranscript();
                shouldAutoRestartRecognition = false;
                if(recognition) {
                    try {
                        recognition.stop();
                    } catch (error) {
                        console.error('Speech recognition failed to stop:', error);
                    }
                }
                isRecording = false;
                clearInterval(recTimerInterval);
                document.getElementById('micStartBtn').style.display = 'block';
                document.getElementById('micStartBtn').innerText = 'Resume';
                document.getElementById('micPauseBtn').style.display = 'none';
                document.getElementById('faceScannerBox').style.display = 'none';
            }

            function stopRecording() {
                pauseRecording();
                clearTimeout(autoStartAfterQuestionTimer);
                document.getElementById('micStartBtn').innerText = 'Start';
                document.getElementById('micStopBtn').style.display = 'none';
                document.getElementById('recordingTimer').style.display = 'none';
                recTimerSeconds = 0;
                resetSpeechRecognitionBufferFromTextarea();
            }

            function saveCurrentAnswer(isSkipped = false) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('game_session_id', gameSessionId);
                formData.append('question_index', currentQIdx);
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

                return fetch('{{ route("user.game.answer") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Answer save failed with status ' + response.status);
                    }

                    return response;
                });
            }

            function autoSaveState() {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('game_session_id', gameSessionId);
                formData.append('notes', document.getElementById('sessionNotes').value);
                formData.append('duration_seconds', timerSeconds);
                formData.append('current_question_index', currentQIdx);
                
                fetch('{{ route("user.game.saveState") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Auto-save failed with status ' + response.status);
                    }

                    const ind = document.getElementById('autoSaveIndicator');
                    ind.style.display = 'inline';
                    setTimeout(() => ind.style.display = 'none', 2000);
                }).catch(error => {
                    console.error(error);
                });
            }

            function submitAnswer() {
                if (isFinishingChallenge) return;
                if(isRecording) stopRecording();
                const isFinalQuestion = currentQIdx >= questions.length - 1;
                document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = true);
                if (isFinalQuestion) {
                    showChallengeFinishModal('Saving your final answer...');
                }
                saveCurrentAnswer(false).then(() => {
                    if (currentQIdx < questions.length - 1) {
                        document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = false);
                        loadQuestion(currentQIdx + 1);
                    } else {
                        finishChallenge();
                    }
                }).catch(error => {
                    console.error(error);
                    hideChallengeFinishModal();
                    document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = false);
                    alert('We could not save your answer. Please try again before continuing.');
                });
            }

            function skipQuestion() {
                if (isFinishingChallenge) return;
                if(isRecording) stopRecording();
                const isFinalQuestion = currentQIdx >= questions.length - 1;
                document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = true);
                if (isFinalQuestion) {
                    showChallengeFinishModal('Saving this skipped answer...');
                }
                saveCurrentAnswer(true).then(() => {
                    if (currentQIdx < questions.length - 1) {
                        document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = false);
                        loadQuestion(currentQIdx + 1);
                    } else {
                        finishChallenge();
                    }
                }).catch(error => {
                    console.error(error);
                    hideChallengeFinishModal();
                    document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = false);
                    alert('We could not save your skipped answer. Please try again before continuing.');
                });
            }

            function prevQuestion() {
                if(isRecording) stopRecording();
                if (currentQIdx > 0) {
                    loadQuestion(currentQIdx - 1);
                }
            }

            function finishChallenge() {
                if (isFinishingChallenge) return;
                isFinishingChallenge = true;
                showChallengeFinishModal('Scoring your answers and preparing your result modal...');
                document.querySelectorAll('.next-btn-class, .skip-btn-class, .prev-btn-class').forEach(el => el.disabled = true);
                let video = document.getElementById('userCamera');
                if (video && video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                }
                clearInterval(timerInterval);
                document.getElementById('formDuration').value = timerSeconds;
                document.getElementById('formNotes').value = document.getElementById('sessionNotes').value;
                window.setTimeout(() => document.getElementById('finishForm').submit(), 120);
            }

            function showChallengeFinishModal(message) {
                const status = document.getElementById('challengeFinishStatus');
                if (status) status.textContent = message;

                const modalEl = document.getElementById('challengeFinishModal');
                if (!modalEl) return;

                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl, {
                        backdrop: 'static',
                        keyboard: false
                    }).show();
                    return;
                }

                modalEl.style.display = 'block';
                modalEl.classList.add('show');
                modalEl.removeAttribute('aria-hidden');
                modalEl.setAttribute('aria-modal', 'true');
            }

            function hideChallengeFinishModal() {
                isFinishingChallenge = false;
                const modalEl = document.getElementById('challengeFinishModal');
                if (!modalEl) return;

                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    return;
                }

                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.removeAttribute('aria-modal');
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

@if(isset($cameraCoachingEnabled) && $cameraCoachingEnabled)
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/'),
        faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/')
    ]).then(() => {
        console.log("Optional face-framing models loaded");
    }).catch(err => {
        window.faceFramingModelUnavailable = true;
        console.error("Error loading optional face-framing models", err);
    });
</script>
<script type="module">
    const modelState = window.bodyLanguageModelState = window.bodyLanguageModelState || {
        ready: false,
        failed: false,
        poseLandmarker: null,
        handLandmarker: null
    };

    import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21/vision_bundle.mjs')
        .then(async ({ FilesetResolver, PoseLandmarker, HandLandmarker }) => {
            const vision = await FilesetResolver.forVisionTasks(
                'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21/wasm'
            );
            const [poseLandmarker, handLandmarker] = await Promise.all([
                PoseLandmarker.createFromOptions(vision, {
                    baseOptions: {
                        modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/latest/pose_landmarker_lite.task'
                    },
                    runningMode: 'VIDEO',
                    numPoses: 1,
                    minPoseDetectionConfidence: 0.5,
                    minPosePresenceConfidence: 0.5,
                    minTrackingConfidence: 0.5,
                    outputSegmentationMasks: false
                }),
                HandLandmarker.createFromOptions(vision, {
                    baseOptions: {
                        modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/latest/hand_landmarker.task'
                    },
                    runningMode: 'VIDEO',
                    numHands: 2,
                    minHandDetectionConfidence: 0.5,
                    minHandPresenceConfidence: 0.5,
                    minTrackingConfidence: 0.5
                })
            ]);

            Object.assign(modelState, {
                ready: true,
                failed: false,
                poseLandmarker,
                handLandmarker
            });
            console.log("Optional body-language models loaded");
        })
        .catch(err => {
            modelState.ready = false;
            modelState.failed = true;
            console.error("Error loading optional body-language models", err);
        });
</script>
@endif

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let onboardingTour = null;
        if (typeof window.createSpeakReadyTour === 'function') {
            const stepsMobile = [
                { element: '.ai-avatar-panel', popover: { title: 'AI Coach', description: 'The coach presents each Philippines challenge question and guides the session flow.', side: 'bottom', align: 'start' }},
                { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here while live metrics update.', side: 'top', align: 'start' }},
                { element: '#cameraPanel', popover: { title: 'Optional Body-Language Coach', description: 'Private framing, hand, posture, and movement prompts are optional and never affect readiness or challenge scoring.', side: 'top', align: 'start' }},
                { element: '#overallReadiness', popover: { title: 'AI Visualizer', description: 'Watch instant feedback for clarity, relevance, and professionalism.', side: 'top', align: 'start' }},
                { element: '.star-item', popover: { title: 'STAR Analyzer', description: 'This tracks Situation, Task, Action, and Result coverage in your answer.', side: 'top', align: 'start' }},
                { element: '#voiceAnalyticsPanel', popover: { title: 'Voice Analytics', description: 'Review speaking duration, pace, and filler word usage.', side: 'top', align: 'start' }}
            ];

            const stepsDesktop = [
                { element: '.ai-avatar-panel', popover: { title: 'AI Coach', description: 'The coach presents each Philippines challenge question and guides the session flow.', side: 'right', align: 'start' }},
                { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here while live metrics update.', side: 'right', align: 'start' }},
                { element: '#cameraPanel', popover: { title: 'Optional Body-Language Coach', description: 'Private framing, hand, posture, and movement prompts are optional and never affect readiness or challenge scoring.', side: 'left', align: 'start' }},
                { element: '#overallReadiness', popover: { title: 'AI Visualizer', description: 'Watch instant feedback for clarity, relevance, and professionalism.', side: 'left', align: 'start' }},
                { element: '.star-item', popover: { title: 'STAR Analyzer', description: 'This tracks Situation, Task, Action, and Result coverage in your answer.', side: 'left', align: 'start' }},
                { element: '#voiceAnalyticsPanel', popover: { title: 'Voice Analytics', description: 'Review speaking duration, pace, and filler word usage.', side: 'left', align: 'start' }}
            ];

            onboardingTour = window.createSpeakReadyTour({
                completionKey: 'onboarding_completed_learning_game_session',
                serverDetectedMobile: true,
                stepsMobile,
                stepsDesktop,
                autoStart: false,
            });
        }
        
        // Expose startOnboardingTour to be called after the challenge starts
        const originalStartChallenge = window.startChallengeSession;
        window.startChallengeSession = function() {
            if (typeof originalStartChallenge === 'function') {
                originalStartChallenge.apply(this, arguments);
            }

            if (onboardingTour && !onboardingTour.isCompleted()) {
                setTimeout(() => {
                    onboardingTour.start();
                }, 1000);
            }
        };

        // ARENA COUNTDOWN LOGIC
        let countdownValue = 3;
        const countdownText = document.getElementById('countdown-text');
        const overlay = document.getElementById('get-ready-overlay');
        if (!countdownText || !overlay) {
            window.startChallengeSession();
            return;
        }
        
        const countdownInterval = setInterval(() => {
            countdownValue--;
            if (countdownValue > 0) {
                countdownText.innerText = countdownValue;
            } else if (countdownValue === 0) {
                countdownText.innerText = "GO!";
                countdownText.style.color = "#34d399";
                countdownText.style.animation = "none";
                countdownText.style.transform = "scale(1.5)";
                countdownText.style.transition = "0.2s transform";
            } else {
                clearInterval(countdownInterval);
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    window.startChallengeSession();
                }, 500);
            }
        }, 1000);
    });
</script>
@endpush
@endsection
