@extends('desktop.layouts.app')
@section('title', 'Philippines Interview Workspace')
@section('body-class', 'interview-session-shell')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/interview/session.css?v=22') }}" data-page-style="interview-session">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')

<div class="db-section active" id="sec-interview-session">
    @if(session('active_interview_id'))
        @php
            $sessionRecord = \App\Models\InterviewSession::with('category')
                ->where('user_id', auth()->id())
                ->find(session('active_interview_id'));
            if ($sessionRecord) {
                $num = $sessionRecord->num_questions ?? 5;
                $selectedQuestionTypes = json_decode($sessionRecord->question_types ?? '[]', true);
                $selectedQuestionTypes = is_array($selectedQuestionTypes) ? array_values(array_filter($selectedQuestionTypes)) : [];
                // Try to find questions specifically generated for this session first
                $questions = \App\Models\Question::where('interview_session_id', $sessionRecord->id)
                    ->orderBy('id')
                    ->get();
                
                // Fallback to local category questions if none were specifically generated
                if ($questions->isEmpty()) {
                    // Try to match exact difficulty and active status first
                    $questions = \App\Models\Question::where('category_id', $sessionRecord->category_id)
                        ->where('status', 'active')
                        ->where('difficulty', $sessionRecord->difficulty)
                        ->when(!empty($selectedQuestionTypes), fn($query) => $query->whereIn('type', $selectedQuestionTypes))
                        ->inRandomOrder()->limit($num)->get();
                        
                    // If no questions match the difficulty, fallback to any active questions in category
                    if ($questions->isEmpty()) {
                        $questions = \App\Models\Question::where('category_id', $sessionRecord->category_id)
                            ->where('status', 'active')
                            ->when(!empty($selectedQuestionTypes), fn($query) => $query->whereIn('type', $selectedQuestionTypes))
                            ->inRandomOrder()->limit($num)->get();
                    }
                }
                $focusText = strtolower((string) $sessionRecord->interview_focus);
                $sourcePackKey = match (true) {
                    str_contains($focusText, 'bpo'), str_contains($focusText, 'customer support'), str_contains($focusText, 'contact center') => 'ph_bpo_communication',
                    str_contains($focusText, 'it / programming'), str_contains($focusText, 'programming'), str_contains($focusText, 'software'), str_contains($focusText, 'technical') => 'ph_it_programming',
                    str_contains($focusText, 'scholarship') => 'ph_scholarship',
                    str_contains($focusText, 'college'), str_contains($focusText, 'admission') => 'ph_college_admission',
                    default => \App\Services\QuestionDatasetProvider::defaultKeyForCategory($sessionRecord->category->title ?? null),
                };
                $sourcePack = \App\Services\QuestionDatasetProvider::find($sourcePackKey)
                    ?? ($sessionRecord->category ? \App\Services\QuestionDatasetProvider::forCategory($sessionRecord->category) : null);
                $scenarioLabels = [
                    'ph_job_interview' => 'Job Interviews',
                    'ph_bpo_communication' => 'Job Interviews',
                    'ph_it_programming' => 'Job Interviews',
                    'ph_scholarship' => 'School Admission Interviews',
                    'ph_college_admission' => 'School Admission Interviews',
                ];
                $scenarioLabel = $scenarioLabels[$sourcePack['key'] ?? $sourcePackKey] ?? 'Philippines Interview';
                $sourceNames = collect($sourcePack['sources'] ?? [])->pluck('name')->filter()->take(3)->implode(', ');
                $primarySource = $questions->first(fn ($question) => filled($question->source_name))
                    ?? (object) [
                        'source_name' => data_get($sourcePack, 'sources.0.name'),
                        'source_url' => data_get($sourcePack, 'sources.0.url'),
                        'source_type' => $sourcePack['source_type'] ?? 'dataset',
                    ];
            } else {
                $questions = collect([]);
            }
        @endphp

        @if($sessionRecord && $questions->count() > 0)
        @php
            $cameraDetectionEnabled = (bool) data_get($sessionRecord->accommodation_profile, 'camera_detection', data_get($sessionRecord->accommodation_profile, 'camera_coaching', false));
            $showCameraPanel = false;
            $savedStateForUi = json_decode($sessionRecord->session_state ?? '', true);
            $hasSavedInterviewState = is_array($savedStateForUi) && !empty($savedStateForUi['has_started']);
            $initialQuestionCounter = $hasSavedInterviewState ? 'Resume' : 'Ready';
        @endphp
        <div id="workspaceWrapper" style="display:none;">
        <div class="row g-4" id="workspaceRow">
            <!-- Main Content Area -->
            <div class="{{ $showCameraPanel ? 'col-lg-8' : 'col-lg-12' }}">
                <!-- Progress Tracker Removed by User -->
                <div class="desktop-session-two-column">
                    <div class="desktop-interview-panel">

                <!-- Interviewer Avatar Panel -->
                <div class="panel p-0 ai-avatar-panel{{ $cameraDetectionEnabled ? ' has-desktop-camera-pip' : '' }} animate-fade-up delay-100" style="overflow:hidden;border:1px solid var(--bd);background:#000;position:relative;height:280px;border-radius:24px;margin-bottom:24px;box-shadow:0 15px 40px rgba(0,0,0,0.15);">
                    <div style="position:absolute; inset:0; background: radial-gradient(circle at top right, rgba(139,92,246,0.3), transparent 60%), radial-gradient(circle at bottom left, rgba(59,130,246,0.3), transparent 60%); z-index:1; pointer-events:none;"></div>
                    @if($cameraDetectionEnabled)
                    <div class="desktop-camera-pip d-none d-lg-flex" aria-label="Camera preview">
                        <video id="userCamera" class="desktop-camera-video" autoplay muted playsinline></video>
                        <div class="desktop-camera-placeholder" aria-hidden="true"><i class="fa-solid fa-video"></i></div>
                    </div>
                    @endif
                    <!-- Question Counter (Top Left) -->
                    <div style="position:absolute; top:15px; left:15px; z-index:50;">
                        <span class="badge bg-white text-dark shadow-sm" style="font-size:0.8rem;white-space:nowrap;padding: 6px 10px;" id="qCounter">{{ $initialQuestionCounter }}</span>
                    </div>
                    <span class="badge interviewer-panel-badge"><i class="fa-solid fa-bolt me-1"></i> Philippines interviewer</span>

                    <div id="aiAvatarContainer" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);">
                        <div class="avatar-wrapper" id="aiAvatarHead" style="width:110px;height:110px;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;--avatar-ring-color:#8b5cf6;">
                            <!-- The Image Container (with border, glow, and clipping for the image itself) -->
                            <div class="avatar-frame">
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
                                <div class="spectrum-bar {{ $animClass }}" style="--bar-rotation: {{ $rot }}deg;"></div>
                            @endfor
                        </div>
                    </div>
                    <div class="question-caption-overlay" aria-live="polite" aria-atomic="true">
                        <div id="questionCaptionText" class="question-caption-line"></div>
                    </div>
                </div>

                <div class="ai-question-card animate-fade-up delay-150">
                    <div class="d-flex justify-content-center align-items-end gap-3 text-center">
                        <div class="w-100">
                            <div id="aiQuestionText">Loading your first question...</div>
                        </div>
                    </div>
                </div>

                <!-- Unified Responsive Interview Controls (Desktop & Mobile) -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mb-4 animate-fade-up delay-150" id="interviewControls" style="opacity: 0; pointer-events: none; transition: opacity 0.3s;">
                    <!-- Left: Navigation / Secondary -->
                    <div class="d-flex gap-2 w-100 flex-fill">
                        <button type="button" class="btn btn-outline-info flex-fill" onclick="repeatQuestion()" style="border-radius:12px;"><i class="fa-solid fa-volume-high me-2"></i>Repeat</button>
                        <button type="button" class="btn btn-outline-danger flex-fill" onclick="requestAbortInterviewSession()" style="border-radius:12px;"><i class="fa-solid fa-flag-checkered me-2"></i>End Session</button>
                    </div>
                    
                    <!-- Right: Primary Actions (Mic + Send) -->
                    <div class="d-flex gap-2 w-100 flex-fill justify-content-md-end align-items-center">
                        <span id="recordingTimer" style="font-family:monospace;font-size:1.1rem;color:#f87171;display:block;margin-right:10px;font-weight:bold;">00:00</span>
                        
                        <!-- Voice Recording Controls -->
                        <div id="voiceControls" style="display:none; margin:0; padding:0; border:none; background:transparent;">
                            @if($sessionRecord->game_level_id)
                                <button type="button" id="holdToTalkBtn" class="btn btn-danger" style="border-radius:12px; font-weight:700; box-shadow: 0 4px 15px rgba(239,68,68,0.4); padding: 0.5rem 1rem; user-select:none; touch-action:manipulation;">
                                    <i class="fa-solid fa-microphone me-2"></i>HOLD
                                </button>
                            @else
                                <div class="d-flex gap-2">
                                    <button type="button" id="micPauseBtn" class="btn btn-warning" onclick="toggleRecordingPause()" style="display:inline-flex; border-radius:12px;" aria-label="Pause recording" title="Pause recording"><i class="fa-solid fa-pause"></i></button>
                                    <button type="button" id="micStopBtn" class="btn btn-danger" onclick="stopRecording()" style="display:inline-flex; border-radius:12px;" aria-label="Stop recording" title="Stop recording"><i class="fa-solid fa-stop"></i></button>
                                </div>
                            @endif
                        </div>
                        <span id="transcriptionStatus" class="transcription-status" aria-live="polite" aria-atomic="true"></span>

                    </div>
                </div>
                    </div>

                <!-- Answer Response System -->
                    <div class="desktop-response-column">
                <div id="sessionNotice" class="session-inline-alert" role="alert" aria-live="assertive" tabindex="-1" hidden></div>
                <div class="panel response-panel mb-4 animate-fade-up delay-200">
                    <div class="panel-title">
                        <i class="fa-solid fa-pen-nib me-2"></i>
                        <span class="panel-title-text">Your Response</span>
                        <div class="response-title-actions">
                            @if($sessionRecord->game_level_id)
                                <span class="badge" style="background:#ef4444; color:white;"><i class="fa-solid fa-gamepad me-1"></i> GAME MODE</span>
                            @endif
                            <button type="button" id="responseFullscreenToggle" class="response-fullscreen-toggle" onclick="toggleMobileFullscreen()" aria-label="Exit fullscreen" title="Exit fullscreen">
                                <i class="fa-solid fa-compress"></i>
                            </button>
                            <button type="button" class="next-btn-class send-answer-btn response-send-answer-btn btn-shine" onclick="submitAnswer()">
                                Send Answer <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    
                    <form id="answerForm">
                        <!-- Voice controls moved to interviewControls panel -->

                        <div id="chatTranscriptContainer" style="max-height: none; overflow: visible; padding: 0; margin-bottom: 12px; background: transparent; border: 0; display: none; flex-direction: column; gap: 10px;"></div>
                        <label for="answerTextarea" class="visually-hidden">Your interview answer</label>
                        <textarea id="answerTextarea" class="oinp mb-2" style="min-height:76px;font-size:.82rem" placeholder="Type your answer using your own Philippine school, work, internship, or project evidence..." aria-describedby="sessionNotice"></textarea>
                        
                        <div class="response-count-bar">
                            <div>
                                <span id="wordCount">0 words</span> - <span id="charCount">0 characters</span>
                                <span id="autoSaveIndicator" class="ms-3 text-success" style="display:none;"><i class="fa-solid fa-check me-1"></i>Auto-saved</span>
                            </div>
                        </div>

                        <div class="interview-confidence-control" id="realtimeConfidenceControl" data-confidence-band="empty">
                            <div class="confidence-meter-label" id="realtimeConfidenceLabel">
                                <span><i class="fa-solid fa-chart-simple"></i> Realtime confidence</span>
                                <strong id="selfConfidenceValue">0%</strong>
                            </div>
                            <div class="confidence-meter-track" id="confidenceMeter" role="meter" aria-labelledby="realtimeConfidenceLabel" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <div class="confidence-meter-fill" id="confidenceMeterFill"></div>
                            </div>
                            <div class="confidence-signal-text" id="confidenceSignalText">Waiting for response</div>
                            <input type="hidden" id="selfConfidenceRange" value="0">
                        </div>

                        <div class="question-source-panel response-source-panel" id="questionSourcePanel">
                            <div id="aiQuestionSource" class="source-card-line" data-default-name="{{ $primarySource->source_name ?? '' }}" data-default-url="{{ $primarySource->source_url ?? '' }}" data-default-type="{{ $primarySource->source_type ?? 'dataset' }}">
                                <span style="color:var(--tx3);font-weight:600">Source will appear when the question starts.</span>
                            </div>
                        </div>

                        <!-- Bottom mobile buttons moved to unified control panel above -->
                    </form>
                </div>
                    </div>
                </div>
            </div>

            <!-- Side Panels -->
            @if($showCameraPanel)
            <div class="col-lg-4">
                <!-- Session Navigation (Mobile fallback / Overview) -->
                <!-- Optional body-language detection; never used in readiness scoring. -->
                <div class="panel d-none d-lg-block animate-fade-up delay-100" id="cameraPanel">
                    <div class="panel-title"><i class="fa-solid fa-camera-web me-2"></i> Body-Language Detection</div>
                    <div style="position:relative;background:#000;height:180px;border-radius:12px;margin-bottom:15px;overflow:hidden;display:flex;align-items:center;justify-content:center">
                        <video id="userCamera" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);"></video>
                        <div class="face-scanner-box" id="faceScannerBox" style="display:none;position:absolute;width:120px;height:120px;border:2px solid #34d399;border-radius:12px;box-shadow:0 0 15px rgba(52,211,153,0.3);transition:all 0.3s ease;">
                            <div class="scan-line" style="width:100%;height:2px;background:#34d399;position:absolute;top:0;animation: scanAnim 2s infinite linear;box-shadow:0 0 8px #34d399;"></div>
                        </div>
                        <div id="cameraDetectionStatus" style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.65);padding:2px 8px;border-radius:4px;font-size:.7rem;color:#cbd5e1"><i class="fa-solid fa-laptop me-1"></i>Local landmark estimate</div>
                    </div>
                    <div class="stat-row"><span>Face in frame</span><span id="stEyeContact" class="text-secondary">Waiting</span></div>
                    <div class="stat-row"><span>Head alignment estimate</span><span id="stPosture" class="text-secondary">Not scored</span></div>
                    <div class="stat-row"><span>Hands / gesture movement</span><span id="stGesture" class="text-secondary">Waiting</span></div>
                    <div class="stat-row"><span>Shoulders / posture pose</span><span id="stPose" class="text-secondary">Waiting</span></div>
                    <div class="stat-row mb-0"><span>Movement steadiness</span><span id="stMovement" class="text-secondary">Waiting</span></div>
                    <div class="small mt-2" style="color:var(--tx3)">This estimates visible framing, head alignment, hands, shoulders, posture pose, and movement steadiness only. Video is analyzed in your browser; no images, video, or raw landmarks are stored. It does not infer confidence, honesty, personality, or employability, and it is excluded from readiness.</div>
                </div>

            </div>
            @endif
        </div>
        </div>

        <form id="finishForm" action="{{ route('interview.finish') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="session_id" value="{{ $sessionRecord->id }}">
            <input type="hidden" name="duration_seconds" id="formDuration">
            <input type="hidden" name="notes" id="formNotes">
        </form>

        <div id="finishTransitionOverlay" class="finish-transition-overlay" role="status" aria-live="polite" aria-atomic="true">
            <div class="finish-loading-wrapper">
                <div class="finish-loading-circle"></div>
                <img src="{{ asset('img/logo.png') }}" alt="Loading feedback">
            </div>
            <h4 id="finishTransitionTitle">Analyzing your response...</h4>
            <p id="finishTransitionMessage">Please wait while we finalize your interview report.</p>
            <div id="finishFailureAlert" class="finish-failure-alert" role="alert" aria-live="assertive" hidden></div>
            <div class="finish-recovery-actions">
                <button type="button" id="finishRetryButton" class="finish-retry-button" style="display:none;" onclick="retryFinishInterview()"><i class="fa-solid fa-rotate-right me-1"></i>Retry report</button>
                <button type="button" id="finishBackButton" class="finish-secondary-button" style="display:none;" onclick="returnToInterviewAfterFinishError()"><i class="fa-solid fa-arrow-left me-1"></i>Back to answer</button>
            </div>
        </div>

        <div id="interviewStartModal" class="interview-start-modal" role="dialog" aria-modal="true" aria-labelledby="interviewStartTitle" aria-describedby="interviewStartDescription">
            <div class="interview-start-dialog">
                <div class="interview-start-icon" aria-hidden="true">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <h4 id="interviewStartTitle">Philippines Interview Ready</h4>
                <p id="interviewStartDescription">{{ $hasSavedInterviewState ? 'Your saved interview is ready to resume.' : 'Your customized interview session is ready to begin.' }}</p>
                <div class="interview-start-meta interview-meta-line">
                    <span class="session-chip"><i class="fa-solid fa-flag"></i>{{ $scenarioLabel }}</span>
                <span class="session-chip"><i class="fa-solid fa-microphone"></i>{{ ['text' => 'Text', 'voice' => 'Voice', 'hybrid' => 'Hybrid', 'voice_and_text' => 'Hybrid'][strtolower((string) $sessionRecord->response_mode)] ?? 'Text' }} Mode</span>
                    <span class="session-chip"><i class="fa-solid fa-list-check"></i>{{ $num }} Questions</span>
                    <span class="session-chip"><i class="fa-solid fa-video"></i>Camera {{ $cameraDetectionEnabled ? 'ON' : 'OFF' }}</span>
                </div>
                <div class="interview-start-actions">
                    <button type="button" class="interview-start-button cancel" onclick="cancelInterviewStart()">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </button>
                    <button type="button" id="confirmInterviewStartButton" class="interview-start-button begin" onclick="confirmInterviewStart()">
                        {{ $hasSavedInterviewState ? 'Resume Interview' : 'Begin Interview' }} <i class="fa-solid fa-play"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="endSessionModal" class="interview-start-modal interview-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="endSessionTitle" aria-describedby="endSessionDescription">
            <div class="interview-start-dialog">
                <div class="interview-start-icon danger" aria-hidden="true">
                    <i class="fa-solid fa-flag-checkered"></i>
                </div>
                <h4 id="endSessionTitle">End without feedback?</h4>
                <p id="endSessionDescription">Your saved responses will stay available for review, but this session will not receive a score, AI feedback, or improved answer suggestions.</p>
                <div class="end-session-feedback-alert" role="alert">
                    <strong>No feedback will be generated.</strong>
                    <span>You can review your responses to the questions, but the report will say feedback is unavailable because you ended the session early.</span>
                </div>
                <div id="endSessionDraftPreview" class="end-session-draft-preview" hidden>
                    <span>Current draft to save</span>
                    <p></p>
                </div>
                <div class="interview-start-actions">
                    <button type="button" class="interview-start-button cancel" onclick="cancelAbortInterviewSession()">
                        <i class="fa-solid fa-arrow-left"></i> Keep Practicing
                    </button>
                    <button type="button" id="confirmEndSessionButton" class="interview-start-button danger" onclick="confirmAbortInterviewSession()">
                        End without feedback <i class="fa-solid fa-flag-checkered"></i>
                    </button>
                </div>
            </div>
        </div>

        <script>
            const savedSessionState = @json($savedStateForUi ?? []);
            const initialQuestions = @json($questions->values());
            const savedQuestionSequence = Array.isArray(savedSessionState.questions)
                ? savedSessionState.questions.filter(question => question && question.id && question.question_text)
                : [];
            let questions = savedQuestionSequence.length > 0 ? savedQuestionSequence : initialQuestions;
            const interviewSessionId = {{ (int) $sessionRecord->id }};
            const targetQuestionCount = Math.max({{ $num }}, 1);
            const sessionTargetPosition = @json($sessionRecord->target_position ?? 'this role');
            const sessionScenarioLabel = @json($scenarioLabel ?? 'Philippines Interview');
            const sessionDifficultyLabel = @json(ucfirst((string) ($sessionRecord->difficulty ?? 'medium')));
            const responseMode = @json($sessionRecord->response_mode ?? 'voice');
            const canonicalResponseMode = (() => {
                const mode = String(responseMode || 'text').toLowerCase().trim();
                if (mode === 'voice_and_text') return 'hybrid';
                return ['text', 'voice', 'hybrid'].includes(mode) ? mode : 'text';
            })();
            const perQuestionLimitSeconds = {{ (int) (($sessionRecord->time_limit ?? 0) * 60) }};
            const assistanceLevel = @json($sessionRecord->ai_assistance_level ?? 'standard');
            const liveFeedbackMode = @json($sessionRecord->live_feedback_mode ?? 'coaching');
            const cameraDetectionEnabled = @json($cameraDetectionEnabled);
            const cameraPreviewEnabled = cameraDetectionEnabled;
            let cameraUnavailableReason = null;
            @php
                $serverAiVoiceEnabledForUi = \App\Services\AIService::speechSynthesisAvailable();
            @endphp
            const serverAiVoiceEnabled = @json($serverAiVoiceEnabledForUi);
            let currentQIdx = Number(savedSessionState.currentQIdx ?? {{ (int) ($sessionRecord->current_question_index ?? 0) }}) || 0;
            currentQIdx = Math.max(0, Math.min(currentQIdx, Math.max(0, questions.length - 1)));
            let timerSeconds = Number(savedSessionState.timerSeconds ?? {{ (int) ($sessionRecord->duration_seconds ?? 0) }}) || 0;
            let timerInterval;
            let questionTimerInterval = null;
            let questionStartedAt = null;
            let questionElapsedSeconds = 0;
            let lastTimelineCaptureAt = 0;
            let interviewChatHistory = Array.isArray(savedSessionState.chatHistory) ? savedSessionState.chatHistory : [];
            let stateSaveDebounce = null;
            let interviewEnding = false;
            let interviewTerminated = false;
            let interviewStarted = false;
            let answerListenersBound = false;
            let isSubmittingAnswer = false;
            let finalAnswerSubmitted = false;
            let feedbackSubmissionInFlight = false;
            let openingHasPlayed = Boolean(savedSessionState.openingHasPlayed || (Array.isArray(interviewChatHistory) && interviewChatHistory.some(item => {
                const text = String(item?.text || '');
                return item && item.role === 'interviewer' && (
                    text.includes('Let us start with the first question.')
                    || text.includes('To begin, I would like to get to know you first.')
                );
            })));
            const pendingFetchControllers = new Set();
            const displayedQuestionIds = new Set();
            let currentRepeatPrompt = '';
            let currentRepeatOptions = {};
            let sessionNoticeTimer = null;
            
            // Answers state
            function defaultAnswerState() {
                return {
                    text: '',
                    speech_transcript: '',
                    is_skipped: false,
                    timed_out: false,
                    elapsed_seconds: 0,
                    wpm: 0,
                    voice_duration: 0,
                    filler_words: 0,
                    pause_count: 0,
                    confidence_score: 0,
                    self_reported_confidence: 0,
                    eye_contact_score: 0,
                    posture_score: 0,
                    paste_event_count: 0,
                    pasted_character_count: 0,
                    transcript_timeline: [],
                    observation_data: {
                        filler_events: [],
                        camera_samples: [],
                        camera_detection_enabled: cameraDetectionEnabled,
                        camera_unavailable_reason: cameraDetectionEnabled ? cameraUnavailableReason : null
                    },
                    pronunciation_analysis: null
                };
            }

            function normalizeCameraDetectionObservationState(answerState) {
                const state = answerState && typeof answerState === 'object' ? answerState : defaultAnswerState();
                const observations = state.observation_data && typeof state.observation_data === 'object'
                    ? state.observation_data
                    : {};

                state.observation_data = {
                    ...observations,
                    filler_events: Array.isArray(observations.filler_events) ? observations.filler_events : [],
                    camera_samples: cameraDetectionEnabled && Array.isArray(observations.camera_samples) ? observations.camera_samples : [],
                    camera_detection_enabled: cameraDetectionEnabled,
                    camera_unavailable_reason: cameraDetectionEnabled ? (observations.camera_unavailable_reason || cameraUnavailableReason) : null
                };

                return state;
            }

            function observationDataForSubmit(answerState, fillerLimit = 500, cameraLimit = 180) {
                const observations = answerState && answerState.observation_data && typeof answerState.observation_data === 'object'
                    ? answerState.observation_data
                    : {};

                return {
                    ...observations,
                    filler_events: Array.isArray(observations.filler_events) ? observations.filler_events.slice(-fillerLimit) : [],
                    camera_samples: cameraDetectionEnabled && Array.isArray(observations.camera_samples)
                        ? observations.camera_samples.slice(-cameraLimit)
                        : [],
                    camera_detection_enabled: cameraDetectionEnabled,
                    camera_unavailable_reason: cameraDetectionEnabled ? (observations.camera_unavailable_reason || cameraUnavailableReason) : null
                };
            }

            let answersData = Array(questions.length).fill().map(() => defaultAnswerState());
            if (Array.isArray(savedSessionState.answersData)) {
                savedSessionState.answersData.forEach((savedAnswer, idx) => {
                    if (idx < answersData.length && savedAnswer && typeof savedAnswer === 'object') {
                        answersData[idx] = Object.assign(defaultAnswerState(), savedAnswer);
                    }
                });
            }
            answersData = answersData.map(answerState => normalizeCameraDetectionObservationState(answerState));

            function normalizeSelfConfidence(value) {
                const numeric = Number(value);
                if (!Number.isFinite(numeric)) return 0;
                return Math.max(0, Math.min(100, Math.round(numeric)));
            }

            function syncSelfConfidenceControl(value = null) {
                const normalized = normalizeSelfConfidence(value ?? document.getElementById('selfConfidenceRange')?.value ?? 0);
                const selfConfidenceRange = document.getElementById('selfConfidenceRange');
                const selfConfidenceValue = document.getElementById('selfConfidenceValue');
                const confidenceMeter = document.getElementById('confidenceMeter');
                const confidenceMeterFill = document.getElementById('confidenceMeterFill');
                const confidenceControl = document.getElementById('realtimeConfidenceControl');
                const confidenceSignalText = document.getElementById('confidenceSignalText');
                const band = normalized >= 78 ? 'high' : (normalized >= 52 ? 'medium' : (normalized > 0 ? 'low' : 'empty'));
                const signalText = {
                    empty: 'Waiting for response',
                    low: 'Building signal',
                    medium: 'Steady delivery',
                    high: 'Strong delivery'
                }[band];

                if (selfConfidenceRange) {
                    selfConfidenceRange.value = normalized;
                }
                if (selfConfidenceValue) {
                    selfConfidenceValue.textContent = normalized + '%';
                }
                if (confidenceMeter) {
                    confidenceMeter.setAttribute('aria-valuenow', String(normalized));
                    confidenceMeter.setAttribute('aria-valuetext', `${normalized}% ${signalText}`);
                }
                if (confidenceMeterFill) {
                    confidenceMeterFill.style.width = normalized + '%';
                }
                if (confidenceControl) {
                    confidenceControl.dataset.confidenceBand = band;
                }
                if (confidenceSignalText) {
                    confidenceSignalText.textContent = signalText;
                }
                if (answersData[currentQIdx]) {
                    answersData[currentQIdx].self_reported_confidence = normalized;
                    answersData[currentQIdx].confidence_score = normalized;
                }

                return normalized;
            }

            // Voice state and optional, non-scoring body-language state
            let recognition = null;
            let recognitionActive = false;
            let shouldAutoRestartRecognition = false;
            let isRecording = false;
            let isRecordingPaused = false;
            let recTimerSeconds = 0;
            let recTimerInterval;
            let preRecordingText = '';
            let committedSpeechTranscript = '';
            let liveSpeechInterim = '';
            let lastCommittedSpeech = '';
            let lastCommittedAt = 0;
            let microphoneStream = null;
            let microphoneReadyPromise = null;
            let serverTranscriptionRecorder = null;
            let serverTranscriptionStream = null;
            let serverTranscriptionQueue = [];
            let serverTranscriptionProcessing = false;
            let serverTranscriptionActiveRequests = 0;
            let serverTranscriptionResults = new Map();
            let serverTranscriptionNextSequence = 0;
            let serverTranscriptionNextCommitSequence = 0;
            let serverTranscriptionDrainResolver = null;
            let serverTranscriptionDrainTimer = null;
            let serverTranscriptionUnavailable = false;
            let serverTranscriptionConsecutiveFailures = 0;
            let serverTranscriptionSessionToken = 0;
            let farFieldAudioContext = null;
            let farFieldAudioNodes = [];
            let cameraTrackingInFlight = false;
            window.bodyLanguageModelState = window.bodyLanguageModelState || { ready: false, failed: false, poseLandmarker: null, handLandmarker: null };
            const cameraMovementBaselines = {};

            const BrowserSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const serverTranscriptionEnabled = @json(\App\Services\AIService::speechTranscriptionAvailable());
            const mobileSpeechSurface = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
            const localMicrophoneHosts = new Set(['localhost', '127.0.0.1', '::1', '[::1]']);
            const speechLocale = document.documentElement.dataset.speechLocale || navigator.language || 'en-US';
            const speechLanguage = speechLocale.split('-')[0];
            const serverTranscriptionMimeType = (() => {
                if (!window.MediaRecorder || !MediaRecorder.isTypeSupported) return '';
                return [
                    'audio/webm;codecs=opus',
                    'audio/webm',
                    'audio/mp4;codecs=mp4a.40.2',
                    'audio/mp4',
                    'audio/ogg;codecs=opus',
                    'audio/ogg'
                ].find(type => MediaRecorder.isTypeSupported(type)) || '';
            })();
            const serverTranscriptionTimesliceMs = mobileSpeechSurface
                ? {{ max(2500, min(8000, (int) config('services.ai_transcription.mobile_chunk_ms', 5000))) }}
                : {{ max(2500, min(8000, (int) config('services.ai_transcription.chunk_ms', 4000))) }};
            const serverTranscriptionDrainTimeoutMs = {{ max(8000, min(60000, (int) config('services.ai_transcription.drain_timeout_ms', 20000))) }};
            const serverTranscriptionRequestTimeoutMs = {{ max(10000, min(60000, (int) config('services.ai_transcription.request_timeout_ms', 30000))) }};
            const serverTranscriptionMaxInFlight = {{ max(1, min(3, (int) config('services.ai_transcription.max_in_flight', 2))) }};
            const serverTranscriptionFailureLimit = 3;
            const serverTranscriptionSupported = serverTranscriptionEnabled
                && Boolean(window.MediaRecorder)
                && Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
            let activeTranscriptionEngine = isVoiceTranscriptionMode()
                ? (BrowserSpeechRecognition ? 'browser' : (serverTranscriptionSupported ? 'server' : null))
                : null;
            const duplicateSafeWordSet = new Set([
                'i', "i'm", 'the', 'a', 'an', 'and', 'to', 'of', 'for', 'in', 'on', 'it', 'is', 'was',
                'were', 'am', 'are', 'my', 'we', 'you', 'that', 'this', 'with', 'um', 'uh', 'like'
            ]);
            const transcriptDuplicatePhraseMaxWords = 32;
            const transcriptOverlapMaxWords = 64;
            const transcriptRecentDuplicateScanWords = 180;

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

            function normalizedTranscriptWords(value) {
                return wordsForTranscript(value).map(normalizeTranscriptForMatch);
            }

            function normalizedWordsEqualAt(words, start, comparison) {
                for (let offset = 0; offset < comparison.length; offset++) {
                    if (words[start + offset] !== comparison[offset]) {
                        return false;
                    }
                }
                return true;
            }

            function isRecentDuplicateTranscript(existingNormalized, additionNormalized) {
                if (additionNormalized.length === 0 || additionNormalized.length > existingNormalized.length) {
                    return false;
                }

                const normalizedAddition = additionNormalized.join(' ');
                if (isFillerOnlySpeech(normalizedAddition)) {
                    return false;
                }

                const additionChars = normalizedAddition.replace(/\s+/g, '').length;
                if (additionNormalized.length < 3 && additionChars < 12) {
                    return false;
                }

                const scanSize = Math.min(
                    existingNormalized.length,
                    Math.max(transcriptRecentDuplicateScanWords, additionNormalized.length + transcriptOverlapMaxWords)
                );
                const scanStart = Math.max(0, existingNormalized.length - scanSize);

                for (let start = scanStart; start <= existingNormalized.length - additionNormalized.length; start++) {
                    if (normalizedWordsEqualAt(existingNormalized, start, additionNormalized)) {
                        return true;
                    }
                }

                return false;
            }

            function appendWithoutOverlap(existing, addition) {
                const existingClean = cleanTranscriptText(existing);
                const additionClean = cleanTranscriptText(addition);
                if (!existingClean) return additionClean;
                if (!additionClean) return existingClean;

                const existingWords = wordsForTranscript(existingClean);
                const additionWords = wordsForTranscript(additionClean);
                const existingNormalized = normalizedTranscriptWords(existingClean);
                const additionNormalized = normalizedTranscriptWords(additionClean);
                if (isRecentDuplicateTranscript(existingNormalized, additionNormalized)) {
                    return existingClean;
                }

                const maxOverlap = Math.min(existingNormalized.length, additionNormalized.length, transcriptOverlapMaxWords);
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
                if (isFillerOnlySpeech(normalizedPhrase)) {
                    return false;
                }
                if (size >= 2) return true;
                return normalizedPhrase.length > 2 || duplicateSafeWordSet.has(normalizedPhrase);
            }

            function collapseRepeatedSpeech(text) {
                const words = wordsForTranscript(text);
                if (words.length < 2) return cleanTranscriptText(text);

                let index = 0;
                while (index < words.length) {
                    let collapsed = false;
                    const maxWindow = Math.min(transcriptDuplicatePhraseMaxWords, Math.floor((words.length - index) / 2));

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

            function stripInterviewerPromptEcho(segment) {
                let cleanSegment = cleanTranscriptText(segment);
                const promptPatterns = [
                    /^(?:here(?:'s| is|s)?\s+(?:your\s+)?first\s+questions?|here\s+you\s+first\s+questions?)[,:.\s-]*/i,
                    /^(?:let(?:'s| us)\s+start\s+with\s+the\s+first\s+question)[,:.\s-]*/i,
                    /^(?:to\s+begin,?\s+i\s+would\s+like\s+to\s+get\s+to\s+know\s+you\s+first)[,:.\s-]*/i,
                ];

                let changed = true;
                while (changed) {
                    changed = false;
                    promptPatterns.forEach(pattern => {
                        const next = cleanTranscriptText(cleanSegment.replace(pattern, ''));
                        if (next !== cleanSegment) {
                            cleanSegment = next;
                            changed = true;
                        }
                    });
                }

                return cleanSegment;
            }

            function syncSpeechRecognitionBufferFromManualEdit() {
                const ta = document.getElementById('answerTextarea');
                const rawText = ta ? String(ta.value || '') : '';
                const answerState = answersData[currentQIdx] || defaultAnswerState();
                answerState.text = rawText;
                answersData[currentQIdx] = answerState;

                if (!isRecording) return;

                const currentText = cleanTranscriptText(rawText);
                preRecordingText = currentText;
                committedSpeechTranscript = '';
                liveSpeechInterim = '';
                lastCommittedSpeech = '';
                lastCommittedAt = 0;

                answerState.speech_transcript = currentText;
                answersData[currentQIdx] = answerState;
            }

            function handleAnswerInput() {
                syncSpeechRecognitionBufferFromManualEdit();
                triggerAnalysis();
            }

            function isFillerOnlySpeech(segment) {
                const normalized = normalizeTranscriptForMatch(segment);
                return /^(?:(?:you know|i mean|sort of|kind of|um+|uh+|erm+|hmm+|like|actually|basically|literally)(?:\s+|$))+$/i.test(normalized);
            }

            function commitSpeechSegment(segment) {
                const cleanSegment = stripInterviewerPromptEcho(collapseRepeatedSpeech(cleanTranscriptText(segment)));
                if (!cleanSegment) return false;

                const normalized = normalizeTranscriptForMatch(cleanSegment);
                const now = Date.now();
                const fillerOnly = isFillerOnlySpeech(cleanSegment);
                const duplicateWindowMs = fillerOnly ? 750 : 5000;
                if (normalized && normalized === lastCommittedSpeech && (now - lastCommittedAt) < duplicateWindowMs) {
                    return false;
                }

                const appendSpeech = existing => fillerOnly
                    ? cleanTranscriptText(`${existing || ''} ${cleanSegment}`)
                    : appendWithoutOverlap(existing || '', cleanSegment);
                const answerState = answersData[currentQIdx] || defaultAnswerState();
                const nextCommittedTranscript = collapseRepeatedSpeech(appendSpeech(committedSpeechTranscript));
                const currentAnswerTranscript = cleanTranscriptText(answerState.speech_transcript);
                const nextAnswerTranscript = collapseRepeatedSpeech(appendSpeech(currentAnswerTranscript));

                if (
                    !fillerOnly
                    && normalizeTranscriptForMatch(nextCommittedTranscript) === normalizeTranscriptForMatch(committedSpeechTranscript)
                    && normalizeTranscriptForMatch(nextAnswerTranscript) === normalizeTranscriptForMatch(currentAnswerTranscript)
                ) {
                    return false;
                }

                committedSpeechTranscript = nextCommittedTranscript;
                answerState.speech_transcript = nextAnswerTranscript;
                answersData[currentQIdx] = answerState;
                lastCommittedSpeech = normalized;
                lastCommittedAt = now;
                return true;
            }

            function renderSpeechTranscript() {
                const ta = document.getElementById('answerTextarea');
                if (!ta) return;

                const recognizedTranscript = mergeTranscriptParts(committedSpeechTranscript, liveSpeechInterim);
                const renderedTranscript = mergeTranscriptParts(preRecordingText, recognizedTranscript);
                ta.value = renderedTranscript;

                const answerState = answersData[currentQIdx] || defaultAnswerState();
                answerState.text = renderedTranscript;
                answersData[currentQIdx] = answerState;

                triggerAnalysis();
            }

            function setTranscriptionStatus(message, color = 'var(--tx3)') {
                const status = document.getElementById('transcriptionStatus');
                if (!status) return;
                status.textContent = message || '';
                status.style.color = color;
                status.style.display = message ? 'inline-block' : 'none';
            }

            function clearSubmittedAnswerInput() {
                const textarea = document.getElementById('answerTextarea');
                if (textarea) textarea.value = '';

                const wordCount = document.getElementById('wordCount');
                const charCount = document.getElementById('charCount');
                if (wordCount) wordCount.innerText = '0 words';
                if (charCount) charCount.innerText = '0 characters';
                syncSelfConfidenceControl(0);

                const chatContainer = document.getElementById('chatTranscriptContainer');
                if (chatContainer) {
                    chatContainer.innerHTML = '';
                    chatContainer.style.display = 'none';
                }

                resetSpeechRecognitionBufferFromTextarea();
                setTranscriptionStatus('');
            }

            function restoreSubmittedAnswerInput(answerText) {
                const textarea = document.getElementById('answerTextarea');
                if (textarea) textarea.value = answerText || '';
                if (answersData[currentQIdx]) {
                    answersData[currentQIdx].text = textarea ? String(textarea.value || '') : String(answerText || '');
                }
                resetSpeechRecognitionBufferFromTextarea();
                triggerAnalysis();
            }

            function showSessionNotice(message, type = 'error', focus = false) {
                const notice = document.getElementById('sessionNotice');
                if (!notice || !message) return;
                clearTimeout(sessionNoticeTimer);
                notice.textContent = message;
                notice.hidden = false;
                notice.classList.toggle('warning', type === 'warning');
                notice.classList.toggle('success', type === 'success');
                if (focus) notice.focus();
                sessionNoticeTimer = setTimeout(() => {
                    notice.hidden = true;
                }, 9000);
            }

            function clearSessionNotice() {
                const notice = document.getElementById('sessionNotice');
                clearTimeout(sessionNoticeTimer);
                if (notice) notice.hidden = true;
            }

            function microphoneRequiresSecureOrigin() {
                return !(window.isSecureContext || localMicrophoneHosts.has(window.location.hostname));
            }

            function audioCaptureConstraints() {
                return {
                    audio: {
                        echoCancellation: { ideal: true },
                        noiseSuppression: { ideal: true },
                        autoGainControl: { ideal: true },
                        channelCount: { ideal: 1 },
                        sampleRate: { ideal: 48000 },
                        sampleSize: { ideal: 16 }
                    }
                };
            }

            function stopMediaStream(stream) {
                if (!stream) return;
                stream.getTracks().forEach(track => track.stop());
            }

            function releaseFarFieldAudio() {
                farFieldAudioNodes.forEach(node => {
                    try {
                        if (node && typeof node.disconnect === 'function') node.disconnect();
                    } catch (error) {
                        console.warn('Far-field audio node cleanup failed:', error);
                    }
                });
                farFieldAudioNodes = [];

                if (farFieldAudioContext) {
                    try {
                        farFieldAudioContext.close();
                    } catch (error) {
                        console.warn('Far-field audio context cleanup failed:', error);
                    }
                    farFieldAudioContext = null;
                }
            }

            function enhanceFarFieldAudioStream(stream) {
                releaseFarFieldAudio();
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass || !stream?.getAudioTracks?.().length) return stream;

                try {
                    farFieldAudioContext = new AudioContextClass({ sampleRate: 48000 });
                    const source = farFieldAudioContext.createMediaStreamSource(stream);
                    const highpass = farFieldAudioContext.createBiquadFilter();
                    highpass.type = 'highpass';
                    highpass.frequency.value = 90;
                    highpass.Q.value = 0.7;

                    const lowpass = farFieldAudioContext.createBiquadFilter();
                    lowpass.type = 'lowpass';
                    lowpass.frequency.value = 8200;
                    lowpass.Q.value = 0.7;

                    const compressor = farFieldAudioContext.createDynamicsCompressor();
                    compressor.threshold.value = -48;
                    compressor.knee.value = 30;
                    compressor.ratio.value = 12;
                    compressor.attack.value = 0.004;
                    compressor.release.value = 0.28;

                    const gain = farFieldAudioContext.createGain();
                    gain.gain.value = mobileSpeechSurface ? 2.6 : 2.2;

                    const destination = farFieldAudioContext.createMediaStreamDestination();
                    source.connect(highpass);
                    highpass.connect(lowpass);
                    lowpass.connect(compressor);
                    compressor.connect(gain);
                    gain.connect(destination);
                    farFieldAudioNodes = [source, highpass, lowpass, compressor, gain, destination];

                    if (farFieldAudioContext.state === 'suspended') {
                        farFieldAudioContext.resume().catch(error => {
                            console.warn('Far-field audio context resume failed:', error);
                        });
                    }

                    return destination.stream;
                } catch (error) {
                    console.warn('Far-field audio enhancement unavailable:', error);
                    releaseFarFieldAudio();
                    return stream;
                }
            }

            async function requestMicrophoneStream() {
                try {
                    return await navigator.mediaDevices.getUserMedia(audioCaptureConstraints());
                } catch (error) {
                    if (error?.name === 'OverconstrainedError' || error?.name === 'ConstraintNotSatisfiedError') {
                        return navigator.mediaDevices.getUserMedia({ audio: true });
                    }
                    throw error;
                }
            }

            function microphoneErrorMessage(error) {
                const name = error?.name || error || 'unknown';
                if (name === 'NotAllowedError' || name === 'SecurityError' || name === 'not-allowed') {
                    return 'Microphone permission is blocked. Allow Microphone for this site, then try again.';
                }
                if (name === 'service-not-allowed') {
                    return 'Browser speech recognition is blocked, switching to server transcription if available.';
                }
                if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                    return 'No microphone was detected on this device.';
                }
                if (name === 'NotReadableError' || name === 'TrackStartError' || name === 'audio-capture') {
                    return 'The microphone is unavailable or already being used by another app.';
                }
                if (name === 'network') {
                    return 'Browser speech recognition lost connection, switching to server transcription if available.';
                }
                return 'Microphone could not start. Allow Microphone for this site, then try again.';
            }

            function transcriptionUnavailableMessage() {
                if (microphoneRequiresSecureOrigin()) {
                    return 'Microphone access requires HTTPS online, or http://localhost for local testing.';
                }
                if (!BrowserSpeechRecognition && !serverTranscriptionEnabled) {
                    return 'Live transcription needs Chrome/Edge, or an OpenAI key for server transcription.';
                }
                if (!BrowserSpeechRecognition && !window.MediaRecorder) {
                    return 'Live transcription is not supported in this browser.';
                }
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    return 'Microphone access is not available in this browser.';
                }
                return 'Live transcription is not available on this device.';
            }

            function canUseServerTranscription() {
                return isVoiceTranscriptionMode() && serverTranscriptionSupported && !serverTranscriptionUnavailable && !microphoneRequiresSecureOrigin();
            }

            function preferredTranscriptionEngine() {
                if (!isVoiceTranscriptionMode()) return null;
                if (microphoneRequiresSecureOrigin()) return null;
                if (recognition) return 'browser';
                if (canUseServerTranscription()) return 'server';
                return null;
            }

            function startSpeechRecognitionEngine() {
                if (!recognition) {
                    setTranscriptionStatus('Browser speech recognition is not supported.', '#fbbf24');
                    return false;
                }
                if (recognitionActive || !isRecording || !shouldAutoRestartRecognition || activeTranscriptionEngine !== 'browser') {
                    return false;
                }

                try {
                    recognition.start();
                    recognitionActive = true;
                    setTranscriptionStatus('Listening - speak now');
                    return true;
                } catch (error) {
                    if (!error || error.name !== 'InvalidStateError') {
                        console.error('Speech recognition failed to start:', error);
                        setTranscriptionStatus(microphoneErrorMessage(error), '#f87171');
                    }
                    return false;
                }
            }

            async function ensureMicrophoneReady(engine = 'browser') {
                if (microphoneRequiresSecureOrigin()) {
                    setTranscriptionStatus(transcriptionUnavailableMessage(), '#f87171');
                    return false;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    setTranscriptionStatus(transcriptionUnavailableMessage(), '#f87171');
                    return false;
                }

                if (engine === 'server' && serverTranscriptionStream && serverTranscriptionStream.active) {
                    return true;
                }

                if (!microphoneReadyPromise) {
                    setTranscriptionStatus('Requesting microphone permission', '#fbbf24');
                    microphoneReadyPromise = requestMicrophoneStream().then(stream => {
                        if (engine === 'server') {
                            microphoneStream = stream;
                            serverTranscriptionStream = enhanceFarFieldAudioStream(stream);
                        } else {
                            stopMediaStream(stream);
                        }
                        return true;
                    }).catch(error => {
                        setTranscriptionStatus(microphoneErrorMessage(error), '#f87171');
                        return false;
                    }).finally(() => {
                        microphoneReadyPromise = null;
                    });
                }

                return microphoneReadyPromise;
            }

            function releaseMicrophoneStream() {
                releaseServerTranscriptionStream();
            }

            function finalizeInterimTranscript() {
                if (!liveSpeechInterim) return;
                if (commitSpeechSegment(liveSpeechInterim)) {
                    recordFillerEvents(liveSpeechInterim);
                }
                liveSpeechInterim = '';
                renderSpeechTranscript();
            }

            function releaseServerTranscriptionStream() {
                stopMediaStream(serverTranscriptionStream);
                serverTranscriptionStream = null;
                releaseFarFieldAudio();
                stopMediaStream(microphoneStream);
                microphoneStream = null;
            }

            function serverTranscriptionFilename(blob) {
                const type = String(blob?.type || serverTranscriptionMimeType || '').toLowerCase();
                if (type.includes('mp4')) return 'speech.mp4';
                if (type.includes('ogg')) return 'speech.ogg';
                if (type.includes('mpeg')) return 'speech.mp3';
                if (type.includes('wav')) return 'speech.wav';
                return 'speech.webm';
            }

            function resolveServerTranscriptionDrain() {
                if (
                    serverTranscriptionQueue.length > 0
                    || serverTranscriptionActiveRequests > 0
                    || serverTranscriptionResults.size > 0
                    || !serverTranscriptionDrainResolver
                ) {
                    return;
                }

                if (serverTranscriptionDrainTimer) {
                    clearTimeout(serverTranscriptionDrainTimer);
                    serverTranscriptionDrainTimer = null;
                }

                const resolve = serverTranscriptionDrainResolver;
                serverTranscriptionDrainResolver = null;
                resolve();
            }

            function waitForServerTranscriptionDrain(timeoutMs = 10000) {
                if (
                    serverTranscriptionQueue.length === 0
                    && serverTranscriptionActiveRequests === 0
                    && serverTranscriptionResults.size === 0
                ) {
                    return Promise.resolve();
                }

                return new Promise(resolve => {
                    serverTranscriptionDrainResolver = resolve;
                    serverTranscriptionDrainTimer = setTimeout(() => {
                        serverTranscriptionDrainResolver = null;
                        serverTranscriptionDrainTimer = null;
                        resolve();
                    }, timeoutMs);
                    resolveServerTranscriptionDrain();
                });
            }

            function queueServerTranscriptionChunk(blob) {
                if (serverTranscriptionUnavailable || !blob || blob.size < 128 || !questions[currentQIdx]) return;

                serverTranscriptionQueue.push({
                    blob,
                    questionIndex: currentQIdx,
                    questionId: questions[currentQIdx].id,
                    previousTranscript: cleanTranscriptText(answersData[currentQIdx]?.speech_transcript || '').slice(-3000),
                    sequence: serverTranscriptionNextSequence++,
                    token: serverTranscriptionSessionToken
                });
                processServerTranscriptionQueue();
            }

            function disableServerTranscription(message) {
                serverTranscriptionUnavailable = true;
                serverTranscriptionSessionToken++;
                serverTranscriptionQueue = [];
                serverTranscriptionResults = new Map();
                serverTranscriptionNextSequence = 0;
                serverTranscriptionNextCommitSequence = 0;
                resolveServerTranscriptionDrain();

                if (serverTranscriptionRecorder && serverTranscriptionRecorder.state !== 'inactive') {
                    try {
                        serverTranscriptionRecorder.ondataavailable = null;
                        serverTranscriptionRecorder.stop();
                    } catch (error) {
                        console.warn('Server transcription recorder stop failed:', error);
                    }
                }
                serverTranscriptionRecorder = null;
                releaseServerTranscriptionStream();

                if (isRecording && recognition) {
                    activeTranscriptionEngine = 'browser';
                    shouldAutoRestartRecognition = true;
                    setTranscriptionStatus('Server transcription paused - using browser captions', '#fbbf24');
                    ensureMicrophoneReady('browser').then(ready => {
                        if (ready && isRecording && activeTranscriptionEngine === 'browser') {
                            startSpeechRecognitionEngine();
                        }
                    });
                    return;
                }

                setTranscriptionStatus(message || 'Server transcription is temporarily unavailable.', '#f87171');
            }

            function handleServerTranscriptionFailure(error) {
                serverTranscriptionConsecutiveFailures++;
                const errorCode = String(error?.errorCode || '');
                const rateLimited = errorCode === 'speech_transcription_rate_limited' || error?.status === 429;
                const hardUnavailable = errorCode === 'speech_transcription_unavailable'
                    || error?.status === 401
                    || error?.status === 403
                    || error?.status === 419;

                if (rateLimited) {
                    const waitSeconds = Number(error?.retryAfterSeconds || 0);
                    const message = waitSeconds > 0
                        ? `AI transcription is rate limited. Try again in ${Math.ceil(waitSeconds)}s.`
                        : 'AI transcription is rate limited. Continue with browser captions or type your answer.';
                    disableServerTranscription(message);
                    return;
                }

                if (hardUnavailable || serverTranscriptionConsecutiveFailures >= serverTranscriptionFailureLimit) {
                    disableServerTranscription(error?.message || 'Server transcription is temporarily unavailable.');
                    return;
                }

                if (isRecording && activeTranscriptionEngine === 'server') {
                    setTranscriptionStatus('Still listening - retrying transcription', '#fbbf24');
                }
            }

            function processServerTranscriptionQueue() {
                while (
                    serverTranscriptionQueue.length > 0
                    && serverTranscriptionActiveRequests < serverTranscriptionMaxInFlight
                ) {
                    const job = serverTranscriptionQueue.shift();
                    serverTranscriptionActiveRequests++;
                    serverTranscriptionProcessing = true;

                    transcribeServerChunk(job).then(result => {
                        if (job.token === serverTranscriptionSessionToken) {
                            serverTranscriptionConsecutiveFailures = 0;
                            serverTranscriptionResults.set(job.sequence, { job, result });
                        }
                    }).catch(error => {
                        if (error.name !== 'AbortError') {
                            console.warn('Server transcription failed:', error);
                            handleServerTranscriptionFailure(error);
                        }
                        if (job.token === serverTranscriptionSessionToken) {
                            serverTranscriptionResults.set(job.sequence, { job, result: null });
                        }
                    }).finally(() => {
                        serverTranscriptionActiveRequests = Math.max(0, serverTranscriptionActiveRequests - 1);
                        commitReadyServerTranscriptionResults();
                        serverTranscriptionProcessing = serverTranscriptionQueue.length > 0 || serverTranscriptionActiveRequests > 0;
                        processServerTranscriptionQueue();
                        resolveServerTranscriptionDrain();
                    });
                }

                resolveServerTranscriptionDrain();
            }

            function commitReadyServerTranscriptionResults() {
                while (serverTranscriptionResults.has(serverTranscriptionNextCommitSequence)) {
                    const entry = serverTranscriptionResults.get(serverTranscriptionNextCommitSequence);
                    serverTranscriptionResults.delete(serverTranscriptionNextCommitSequence);
                    serverTranscriptionNextCommitSequence++;

                    const job = entry?.job;
                    const result = entry?.result;
                    if (!job || !result || job.token !== serverTranscriptionSessionToken) continue;

                    recordLocalSpeechAnalysis(job.questionIndex, result.pronunciationAnalysis || null);

                    const transcript = cleanTranscriptText(result.transcript || '');
                    if (!transcript || job.questionIndex !== currentQIdx) continue;

                    if (commitSpeechSegment(transcript)) {
                        recordFillerEvents(transcript);
                        captureTranscriptTimeline('server_transcript');
                    }
                    liveSpeechInterim = '';
                    renderSpeechTranscript();
                }

                if (isRecording && activeTranscriptionEngine === 'server') {
                    setTranscriptionStatus('Listening - AI transcription (updates every few seconds)');
                }
            }

            async function transcribeServerChunk(job) {
                if (!job || job.token !== serverTranscriptionSessionToken || !job.questionId) return;

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);
                formData.append('question_id', job.questionId);
                formData.append('previous_transcript', job.previousTranscript || '');
                formData.append('audio', job.blob, serverTranscriptionFilename(job.blob));

                const response = await managedFetch('{{ route("interview.transcribe") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    timeoutMs: serverTranscriptionRequestTimeoutMs
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const error = new Error(data.error || 'Transcription request failed.');
                    error.status = response.status;
                    error.errorCode = data.error_code || '';
                    error.retryAfterSeconds = Number(data.retry_after_seconds || 0) || null;
                    throw error;
                }

                return {
                    transcript: cleanTranscriptText(data.transcript || ''),
                    status: data.transcription_status || 'transcribed',
                    pronunciationAnalysis: data.pronunciation_analysis || null
                };
            }

            function localSpeechScoreFrom(analysis) {
                const candidates = [
                    Number(analysis?.gop?.score),
                    Number(analysis?.pronunciation?.score)
                ];
                return candidates.find(score => Number.isFinite(score) && score >= 0 && score <= 100);
            }

            function localSpeechReliabilityBand(score) {
                if (!Number.isFinite(score) || score <= 0) return 'Unavailable';
                if (score >= 85) return 'High';
                if (score >= 65) return 'Moderate';
                return 'Limited';
            }

            function localSpeechMeasuredComponents(analysis) {
                const direct = Array.isArray(analysis?.reliability?.measured_components)
                    ? analysis.reliability.measured_components
                    : [];
                const inferred = ['asr', 'pronunciation', 'forced_alignment', 'phoneme_alignment', 'gop']
                    .filter(key => String(analysis?.[key]?.status || '') === 'measured');
                return [...new Set([...direct, ...inferred].map(item => String(item)).filter(Boolean))];
            }

            function localSpeechChunkSummary(analysis) {
                const score = localSpeechScoreFrom(analysis);
                const reliabilityScore = Number(analysis?.reliability?.score);
                return {
                    status: String(analysis?.status || 'partial'),
                    score: Number.isFinite(score) ? Math.round(score) : null,
                    reliability_score: Number.isFinite(reliabilityScore) ? Math.round(Math.max(0, Math.min(100, reliabilityScore))) : null,
                    reliability_band: analysis?.reliability?.band || null,
                    asr_provider: analysis?.asr?.provider || null,
                    asr_model: analysis?.asr?.model || null,
                    pronunciation_provider: analysis?.pronunciation?.provider || null,
                    pronunciation_model: analysis?.pronunciation?.model || null,
                    alignment_provider: analysis?.forced_alignment?.provider || null,
                    gop_provider: analysis?.gop?.provider || null,
                    word_alignment_count: Array.isArray(analysis?.forced_alignment?.word_alignments) ? analysis.forced_alignment.word_alignments.length : Number(analysis?.forced_alignment?.word_alignment_count || 0),
                    phoneme_alignment_count: Array.isArray(analysis?.phoneme_alignment?.phoneme_alignments) ? analysis.phoneme_alignment.phoneme_alignments.length : Number(analysis?.phoneme_alignment?.phoneme_alignment_count || 0),
                    measured_components: localSpeechMeasuredComponents(analysis),
                    limitations: Array.isArray(analysis?.limitations) ? analysis.limitations.slice(0, 4) : []
                };
            }

            function aggregateLocalSpeechAnalysis(chunks, latestAnalysis) {
                const scores = chunks.map(chunk => Number(chunk.score)).filter(score => Number.isFinite(score));
                const averageScore = scores.length
                    ? Math.round(scores.reduce((sum, score) => sum + score, 0) / scores.length)
                    : null;
                const reliabilityScores = chunks.map(chunk => Number(chunk.reliability_score)).filter(score => Number.isFinite(score));
                const reliabilityScore = reliabilityScores.length
                    ? Math.round(reliabilityScores.reduce((sum, score) => sum + score, 0) / reliabilityScores.length)
                    : Number(latestAnalysis?.reliability?.score || 0);
                const measuredComponents = [...new Set(chunks.flatMap(chunk => Array.isArray(chunk.measured_components) ? chunk.measured_components : []))];
                const limitations = [...new Set([
                    ...chunks.flatMap(chunk => Array.isArray(chunk.limitations) ? chunk.limitations : []),
                    'Aggregated from server transcription chunks; the browser does not retain full-answer raw audio for later reanalysis.'
                ].filter(Boolean))].slice(0, 8);

                return {
                    version: 1,
                    status: averageScore !== null ? 'partial' : String(latestAnalysis?.status || 'not_measured'),
                    source: 'server_transcription_chunk_aggregation',
                    pronunciation: {
                        status: averageScore !== null ? 'partial' : String(latestAnalysis?.pronunciation?.status || 'not_measured'),
                        score: averageScore,
                        provider: latestAnalysis?.pronunciation?.provider || null,
                        model: latestAnalysis?.pronunciation?.model || null,
                        method: latestAnalysis?.pronunciation?.method || 'chunk_average'
                    },
                    asr: latestAnalysis?.asr || {},
                    forced_alignment: latestAnalysis?.forced_alignment || {},
                    phoneme_alignment: latestAnalysis?.phoneme_alignment || {},
                    gop: latestAnalysis?.gop || {},
                    reliability: {
                        score: reliabilityScore,
                        band: latestAnalysis?.reliability?.band || chunks.find(chunk => chunk.reliability_band)?.reliability_band || localSpeechReliabilityBand(reliabilityScore),
                        measured_components: measuredComponents
                    },
                    chunks,
                    limitations,
                    recommendations: Array.isArray(latestAnalysis?.recommendations) ? latestAnalysis.recommendations.slice(0, 5) : []
                };
            }

            function recordLocalSpeechAnalysis(questionIndex, analysis) {
                if (!analysis || typeof analysis !== 'object') return;
                const useful = ['measured', 'partial'].includes(String(analysis.status || ''))
                    || Number.isFinite(localSpeechScoreFrom(analysis));
                if (!useful || !answersData[questionIndex]) return;

                const existing = Array.isArray(answersData[questionIndex].pronunciation_analysis?.chunks)
                    ? answersData[questionIndex].pronunciation_analysis.chunks.slice(-5)
                    : [];
                existing.push(localSpeechChunkSummary(analysis));
                answersData[questionIndex].pronunciation_analysis = aggregateLocalSpeechAnalysis(existing.slice(-6), analysis);
            }

            function startServerTranscriptionEngine() {
                if (!canUseServerTranscription() || !serverTranscriptionStream || !serverTranscriptionStream.active) {
                    setTranscriptionStatus(transcriptionUnavailableMessage(), '#f87171');
                    return false;
                }

                try {
                    const options = serverTranscriptionMimeType ? { mimeType: serverTranscriptionMimeType } : undefined;
                    serverTranscriptionRecorder = new MediaRecorder(serverTranscriptionStream, options);
                    serverTranscriptionSessionToken++;
                    serverTranscriptionQueue = [];
                    serverTranscriptionResults = new Map();
                    serverTranscriptionActiveRequests = 0;
                    serverTranscriptionNextSequence = 0;
                    serverTranscriptionNextCommitSequence = 0;
                    serverTranscriptionProcessing = false;
                    serverTranscriptionConsecutiveFailures = 0;
                    serverTranscriptionRecorder.ondataavailable = event => queueServerTranscriptionChunk(event.data);
                    serverTranscriptionRecorder.onerror = event => {
                        console.warn('MediaRecorder transcription error:', event.error || event);
                        setTranscriptionStatus('Microphone recording failed. Try again.', '#f87171');
                    };
                    serverTranscriptionRecorder.start(serverTranscriptionTimesliceMs);
                    setTranscriptionStatus('Listening - AI transcription (updates every few seconds)');
                    return true;
                } catch (error) {
                    console.error('Server transcription recorder failed:', error);
                    setTranscriptionStatus(microphoneErrorMessage(error), '#f87171');
                    releaseServerTranscriptionStream();
                    return false;
                }
            }

            async function stopServerTranscriptionEngine() {
                const recorder = serverTranscriptionRecorder;
                if (!recorder) {
                    releaseServerTranscriptionStream();
                    await waitForServerTranscriptionDrain();
                    return;
                }

                await new Promise(resolve => {
                    let settled = false;
                    const finish = () => {
                        if (settled) return;
                        settled = true;
                        serverTranscriptionRecorder = null;
                        releaseServerTranscriptionStream();
                        resolve();
                    };

                    recorder.onstop = finish;
                    try {
                        if (recorder.state !== 'inactive') {
                            recorder.requestData();
                            recorder.stop();
                        } else {
                            finish();
                        }
                    } catch (error) {
                        console.warn('Server transcription stop failed:', error);
                        finish();
                    }

                    setTimeout(finish, 8000);
                });

                await waitForServerTranscriptionDrain(serverTranscriptionDrainTimeoutMs);
            }

            async function activateServerTranscriptionFallback(message = 'Using server transcription fallback') {
                if (!isRecording || !canUseServerTranscription()) return false;

                shouldAutoRestartRecognition = false;
                activeTranscriptionEngine = 'server';
                setTranscriptionStatus(message, '#fbbf24');

                if (recognition && recognitionActive) {
                    try {
                        recognition.stop();
                    } catch (error) {
                        console.warn('Browser recognition stop failed before fallback:', error);
                    }
                    await new Promise(resolve => setTimeout(resolve, 350));
                }

                if (!await ensureMicrophoneReady('server')) return false;
                return startServerTranscriptionEngine();
            }

            let lastSpeechEnd = 0;
            if (BrowserSpeechRecognition) {
                recognition = new BrowserSpeechRecognition();
                recognition.continuous = !mobileSpeechSurface;
                recognition.interimResults = true;
                recognition.lang = speechLocale;
                recognition.maxAlternatives = 3;

                recognition.onstart = function() {
                    recognitionActive = true;
                    setTranscriptionStatus('Listening - live captions');
                };
                
                recognition.onsoundstart = function() {
                    if (lastSpeechEnd > 0) {
                        const gap = (Date.now() - lastSpeechEnd) / 1000;
                        if (gap > 3) {
                            answersData[currentQIdx].pause_count++;
                            triggerAnalysis();
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
                            if (commitSpeechSegment(transcript)) {
                                recordFillerEvents(transcript);
                            }
                        } else {
                            interimParts.push(transcript);
                        }
                    }

                    liveSpeechInterim = cleanTranscriptText(interimParts.join(' '));
                    renderSpeechTranscript();
                };

                recognition.onerror = function(event) {
                    recognitionActive = false;
                    const error = event.error || 'unknown';
                    console.warn('Speech recognition error:', error);

                    if (['network', 'service-not-allowed'].includes(error) && canUseServerTranscription()) {
                        setTimeout(() => activateServerTranscriptionFallback(microphoneErrorMessage(error)), 0);
                        return;
                    }

                    if (['not-allowed', 'service-not-allowed', 'audio-capture'].includes(error)) {
                        shouldAutoRestartRecognition = false;
                        setTranscriptionStatus(microphoneErrorMessage(error), '#f87171');
                    } else if (error === 'no-speech' && isRecording) {
                        shouldAutoRestartRecognition = true;
                        setTranscriptionStatus('Still listening - distant speech is quiet', '#fbbf24');
                    }
                };

                recognition.onend = function() {
                    recognitionActive = false;
                    if (shouldAutoRestartRecognition && isRecording && activeTranscriptionEngine === 'browser') {
                        setTimeout(startSpeechRecognitionEngine, mobileSpeechSurface ? 650 : 250);
                    }
                };
            }

            function markCameraUnavailable(reason) {
                const allowedReasons = ['permission_denied', 'device_unavailable', 'browser_unsupported', 'model_unavailable', 'camera_error'];
                cameraUnavailableReason = allowedReasons.includes(reason) ? reason : 'camera_error';
                if (cameraDetectionEnabled) {
                    answersData.forEach(answerState => {
                        answerState.observation_data = answerState.observation_data || { filler_events: [], camera_samples: [] };
                        answerState.observation_data.camera_unavailable_reason = cameraUnavailableReason;
                    });
                }
                const faceStatus = document.getElementById('stEyeContact');
                const alignmentStatus = document.getElementById('stPosture');
                const gestureStatus = document.getElementById('stGesture');
                const poseStatus = document.getElementById('stPose');
                const movementStatus = document.getElementById('stMovement');
                const detectionStatus = document.getElementById('cameraDetectionStatus');
                if (faceStatus) {
                    faceStatus.textContent = 'Camera unavailable';
                    faceStatus.className = 'text-warning';
                }
                if (alignmentStatus) {
                    alignmentStatus.textContent = 'Not measured';
                    alignmentStatus.className = 'text-secondary';
                }
                if (gestureStatus) {
                    gestureStatus.textContent = 'Not measured';
                    gestureStatus.className = 'text-secondary';
                }
                if (poseStatus) {
                    poseStatus.textContent = 'Not measured';
                    poseStatus.className = 'text-secondary';
                }
                if (movementStatus) {
                    movementStatus.textContent = 'Not measured';
                    movementStatus.className = 'text-secondary';
                }
                if (detectionStatus) {
                    detectionStatus.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Not measured';
                    detectionStatus.style.color = '#fbbf24';
                }
            }

            function initCamera() {
                if (!cameraDetectionEnabled) return;
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({ video: true })
                        .then(function(stream) {
                            let video = document.getElementById('userCamera');
                            if (video) {
                                video.srcObject = stream;
                                video.play();
                                video.closest('.desktop-camera-pip, .mobile-camera-pip, .avatar-camera-frame')?.classList.add('camera-ready');
                            }
                            let mobileVideo = document.getElementById('userCameraMobile');
                            if (mobileVideo) {
                                mobileVideo.srcObject = stream;
                                mobileVideo.play();
                                mobileVideo.closest('.mobile-camera-pip, .avatar-camera-frame')?.classList.add('camera-ready');
                            }
                        })
                        .catch(function(err) {
                            console.error("Error accessing camera: ", err);
                            const reason = err && err.name === 'NotAllowedError'
                                ? 'permission_denied'
                                : (err && err.name === 'NotFoundError' ? 'device_unavailable' : 'camera_error');
                            markCameraUnavailable(reason);
                        });
                } else {
                    console.error("getUserMedia not supported");
                    markCameraUnavailable('browser_unsupported');
                }
            }
            
            function setCameraStat(id, content, className = 'text-secondary', asHtml = false) {
                const element = document.getElementById(id);
                if (!element) return;
                if (asHtml) {
                    element.innerHTML = content;
                } else {
                    element.textContent = content;
                }
                element.className = className;
            }

            function visibleLandmark(landmark, threshold = 0.35) {
                if (!landmark || !Number.isFinite(Number(landmark.x)) || !Number.isFinite(Number(landmark.y))) {
                    return false;
                }
                const visibility = Number(landmark.visibility ?? landmark.presence ?? 1);
                return visibility >= threshold;
            }

            function centerOfNormalized(points) {
                const usable = points.filter(point => point && Number.isFinite(Number(point.x)) && Number.isFinite(Number(point.y)));
                if (usable.length === 0) return null;
                const total = usable.reduce(
                    (point, current) => ({ x: point.x + current.x, y: point.y + current.y }),
                    { x: 0, y: 0 }
                );
                return { x: total.x / usable.length, y: total.y / usable.length };
            }

            function pointDistance(left, right) {
                if (!left || !right) return null;
                return Math.hypot(Number(left.x) - Number(right.x), Number(left.y) - Number(right.y));
            }

            function detectVideoFrame(landmarker, video, timestamp) {
                if (!landmarker || typeof landmarker.detectForVideo !== 'function') return null;
                try {
                    return landmarker.detectForVideo(video, timestamp);
                } catch (error) {
                    return landmarker.detectForVideo(video);
                }
            }

            async function trackBodyLanguageDetection() {
                const bodyLanguageState = window.bodyLanguageModelState || {};
                const canUseBodyModels = Boolean(bodyLanguageState.ready && bodyLanguageState.poseLandmarker && bodyLanguageState.handLandmarker);
                const canUseFaceModel = typeof faceapi !== 'undefined';
                if (!cameraDetectionEnabled || cameraTrackingInFlight || (!canUseBodyModels && !canUseFaceModel)) return;
                const video = document.getElementById('userCamera') || document.getElementById('userCameraMobile');
                if (!video || !video.srcObject) return;
                const trackedQuestionIndex = currentQIdx;

                cameraTrackingInFlight = true;
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
                        try {
                            const timestamp = performance.now();
                            const poseResult = detectVideoFrame(bodyLanguageState.poseLandmarker, video, timestamp);
                            const handResult = detectVideoFrame(bodyLanguageState.handLandmarker, video, timestamp);
                            poseLandmarks = Array.isArray(poseResult?.landmarks) && poseResult.landmarks.length > 0
                                ? poseResult.landmarks[0]
                                : null;
                            handLandmarks = Array.isArray(handResult?.landmarks)
                                ? handResult.landmarks.slice(0, 2)
                                : [];
                        } catch (bodyError) {
                            console.error("Body-language tracking error", bodyError);
                        }
                    }

                    const state = answersData[trackedQuestionIndex] || defaultAnswerState();
                    state.observation_data = state.observation_data || { filler_events: [], camera_samples: [] };
                    state.observation_data.camera_samples = Array.isArray(state.observation_data.camera_samples)
                        ? state.observation_data.camera_samples
                        : [];
                    let cameraFacing = false;
                    let centered = false;
                    let poseCameraFacing = null;
                    let poseDetected = Array.isArray(poseLandmarks) && poseLandmarks.length > 0;
                    let shouldersVisible = false;
                    let shouldersLevel = null;
                    let uprightPosture = null;
                    let handCount = Math.min(2, handLandmarks.length);
                    let gestureActive = false;
                    let movementScore = null;
                    let highMovement = null;
                    const movementPoints = {};

                    if (detection) {
                        const leftEye = detection.landmarks.getLeftEye();
                        const rightEye = detection.landmarks.getRightEye();
                        const nose = detection.landmarks.getNose();
                        const centerOf = points => {
                            const total = points.reduce(
                                (point, current) => ({ x: point.x + current.x, y: point.y + current.y }),
                                { x: 0, y: 0 }
                            );
                            return { x: total.x / Math.max(1, points.length), y: total.y / Math.max(1, points.length) };
                        };
                        const leftCenter = centerOf(leftEye);
                        const rightCenter = centerOf(rightEye);
                        const eyeMidpoint = { x: (leftCenter.x + rightCenter.x) / 2, y: (leftCenter.y + rightCenter.y) / 2 };
                        const noseTip = nose[Math.min(3, Math.max(0, nose.length - 1))] || eyeMidpoint;
                        const eyeDistance = Math.max(1, Math.hypot(rightCenter.x - leftCenter.x, rightCenter.y - leftCenter.y));
                        cameraFacing = Math.abs((noseTip.x - eyeMidpoint.x) / eyeDistance) <= 0.32;

                        const box = detection.detection.box;
                        const videoWidth = Math.max(1, video.videoWidth || video.clientWidth || 1);
                        const videoHeight = Math.max(1, video.videoHeight || video.clientHeight || 1);
                        centered = Math.abs((box.x + (box.width / 2)) - (videoWidth / 2)) <= videoWidth * 0.24
                            && Math.abs((box.y + (box.height / 2)) - (videoHeight / 2)) <= videoHeight * 0.28;

                        setCameraStat('stEyeContact', '<i class="fa-solid fa-check me-1"></i>Visible', 'text-success', true);
                        setCameraStat('stPosture', cameraFacing ? 'Camera-facing estimate' : 'Head turned estimate', cameraFacing ? 'text-success' : 'text-warning');
                    }

                    if (poseDetected) {
                        const nose = poseLandmarks[0];
                        const leftShoulder = poseLandmarks[11];
                        const rightShoulder = poseLandmarks[12];
                        const leftHip = poseLandmarks[23];
                        const rightHip = poseLandmarks[24];
                        const noseVisible = visibleLandmark(nose);
                        shouldersVisible = visibleLandmark(leftShoulder) && visibleLandmark(rightShoulder);
                        const hipsVisible = visibleLandmark(leftHip) && visibleLandmark(rightHip);
                        const shoulderMidpoint = shouldersVisible ? centerOfNormalized([leftShoulder, rightShoulder]) : null;
                        const hipMidpoint = hipsVisible ? centerOfNormalized([leftHip, rightHip]) : null;
                        const shoulderWidth = shouldersVisible ? Math.max(0.01, pointDistance(leftShoulder, rightShoulder) ?? 0.01) : 0.01;

                        if (noseVisible) {
                            movementPoints.nose = { x: nose.x, y: nose.y };
                        }
                        if (shoulderMidpoint) {
                            movementPoints.shoulders = shoulderMidpoint;
                            shouldersLevel = Math.abs(Number(leftShoulder.y) - Number(rightShoulder.y)) <= 0.065;
                        }
                        if (noseVisible && shoulderMidpoint) {
                            poseCameraFacing = Math.abs((Number(nose.x) - shoulderMidpoint.x) / shoulderWidth) <= 0.38;
                        }
                        if (shoulderMidpoint && hipMidpoint) {
                            const torsoHeight = Math.max(0.01, Math.abs(hipMidpoint.y - shoulderMidpoint.y));
                            uprightPosture = Math.abs((shoulderMidpoint.x - hipMidpoint.x) / torsoHeight) <= 0.28;
                        } else if (noseVisible && shoulderMidpoint) {
                            uprightPosture = Math.abs((Number(nose.x) - shoulderMidpoint.x) / shoulderWidth) <= 0.45;
                        }
                    }

                    const handCenters = handLandmarks
                        .map(hand => centerOfNormalized(Array.isArray(hand) ? hand : []))
                        .filter(Boolean);
                    handCenters.forEach((center, index) => {
                        movementPoints['hand' + index] = center;
                    });

                    const previousPoints = cameraMovementBaselines[trackedQuestionIndex] || null;
                    if (previousPoints && Object.keys(movementPoints).length > 0) {
                        const distances = Object.entries(movementPoints)
                            .map(([key, point]) => pointDistance(point, previousPoints[key]))
                            .filter(distance => Number.isFinite(distance));
                        if (distances.length > 0) {
                            const averageDistance = distances.reduce((total, distance) => total + distance, 0) / distances.length;
                            movementScore = Math.min(100, Math.round(averageDistance * 650));
                            highMovement = movementScore >= 45;
                        }
                        const handDistances = handCenters
                            .map((center, index) => pointDistance(center, previousPoints['hand' + index]))
                            .filter(distance => Number.isFinite(distance));
                        gestureActive = handCount > 0 && handDistances.some(distance => distance >= 0.045);
                    }
                    cameraMovementBaselines[trackedQuestionIndex] = movementPoints;

                    const faceDetected = Boolean(detection || (poseDetected && visibleLandmark(poseLandmarks[0])));
                    cameraFacing = Boolean(detection ? cameraFacing : poseCameraFacing);
                    if (!detection && poseDetected && shouldersVisible) {
                        const shoulderCenter = movementPoints.shoulders;
                        centered = Boolean(shoulderCenter && Math.abs(shoulderCenter.x - 0.5) <= 0.24 && Math.abs(shoulderCenter.y - 0.5) <= 0.32);
                    }

                    if (!detection) {
                        setCameraStat(
                            'stEyeContact',
                            faceDetected ? '<i class="fa-solid fa-check me-1"></i>Visible' : 'Outside frame / unavailable',
                            faceDetected ? 'text-success' : 'text-warning',
                            faceDetected
                        );
                        setCameraStat(
                            'stPosture',
                            faceDetected ? (cameraFacing ? 'Camera-facing estimate' : 'Head turned estimate') : 'Excluded from scoring',
                            faceDetected ? (cameraFacing ? 'text-success' : 'text-warning') : 'text-secondary'
                        );
                    }

                    setCameraStat(
                        'stGesture',
                        handCount > 0
                            ? (gestureActive ? handCount + ' hand(s), gesture movement' : handCount + ' hand(s) visible')
                            : 'Hands not visible',
                        handCount > 0 ? 'text-success' : 'text-secondary'
                    );
                    setCameraStat(
                        'stPose',
                        shouldersVisible
                            ? (shouldersLevel && uprightPosture !== false ? 'Balanced upper body' : 'Posture cue available')
                            : (poseDetected ? 'Partial pose estimate' : 'Pose not detected'),
                        shouldersVisible
                            ? (shouldersLevel && uprightPosture !== false ? 'text-success' : 'text-warning')
                            : 'text-secondary'
                    );
                    setCameraStat(
                        'stMovement',
                        movementScore === null
                            ? 'Calibrating'
                            : (highMovement ? 'Higher movement' : 'Steady'),
                        movementScore === null
                            ? 'text-secondary'
                            : (highMovement ? 'text-warning' : 'text-success')
                    );

                    const detectionStatus = document.getElementById('cameraDetectionStatus');
                    if (detectionStatus) {
                        detectionStatus.innerHTML = canUseBodyModels
                            ? '<i class="fa-solid fa-person-rays me-1"></i>Pose + hand estimate'
                            : '<i class="fa-solid fa-laptop me-1"></i>Framing estimate';
                        detectionStatus.style.color = canUseBodyModels ? '#34d399' : '#cbd5e1';
                    }

                    state.observation_data.camera_samples.push({
                        at_seconds: Math.max(0, Number(state.voice_duration || recTimerSeconds || 0)),
                        face_detected: faceDetected,
                        camera_facing: Boolean(faceDetected && cameraFacing),
                        centered: Boolean(faceDetected && centered),
                        pose_detected: poseDetected,
                        hand_count: handCount,
                        hands_visible: handCount > 0,
                        gesture_active: Boolean(gestureActive),
                        shoulders_visible: shouldersVisible,
                        shoulders_level: shouldersLevel,
                        upright_posture: uprightPosture,
                        movement_score: movementScore,
                        high_movement: highMovement
                    });
                    state.observation_data.camera_samples = state.observation_data.camera_samples.slice(-180);
                    answersData[trackedQuestionIndex] = state;
                } catch(e) {
                    console.error("Tracking error", e);
                } finally {
                    cameraTrackingInFlight = false;
                }
            }

            let visualizerInterval = null;
            let currentAmplitude = 0.2;
            let speechAudioContext = null;
            let activeSpectrumAnalyser = null;
            let activeSpectrumData = null;
            let activeSpectrumSource = null;
            let preferredVoice = null;
            let autoStartAfterQuestionTimer = null;
            let questionSpeechToken = 0;
            let activeQuestionAudio = null;
            let captionInterval = null;
            let activeSpeechCompletion = null;
            let serverVoiceUnavailable = !serverAiVoiceEnabled;
            const serverSpeechUrlCache = new Map();

            function isVoiceTranscriptionMode() {
                return canonicalResponseMode === 'voice' || canonicalResponseMode === 'hybrid';
            }

            function responseModePlaceholder() {
                if (canonicalResponseMode === 'text') {
                    return 'Type your answer using your own Philippine school, work, internship, or project evidence...';
                }

                if (canonicalResponseMode === 'voice') {
                    return 'Speak your answer. Your voice-to-text transcript will appear here...';
                }

                return 'Speak your answer, then edit the transcript here if needed...';
            }

            function applyResponseModeUi() {
                const voiceControls = document.getElementById('voiceControls');
                const recordingTimer = document.getElementById('recordingTimer');
                const textarea = document.getElementById('answerTextarea');

                if (textarea) {
                    textarea.placeholder = responseModePlaceholder();
                }

                if (!isVoiceTranscriptionMode()) {
                    if (voiceControls) voiceControls.style.display = 'none';
                    if (recordingTimer) {
                        recordingTimer.style.display = 'none';
                        recordingTimer.innerText = '00:00';
                    }
                    activeTranscriptionEngine = null;
                    setVoiceControlsEnabled(false, 'Voice recording is disabled in Text Mode');
                    setTranscriptionStatus('');
                    return;
                }

                if (voiceControls && interviewStarted) voiceControls.style.display = 'flex';
                if (recordingTimer) recordingTimer.style.display = 'block';
            }

            function managedFetch(url, options = {}) {
                const controller = new AbortController();
                const timeoutMs = Number(options.timeoutMs || 0);
                const fetchOptions = { ...options };
                delete fetchOptions.timeoutMs;
                const timeout = timeoutMs > 0
                    ? setTimeout(() => controller.abort(), Math.max(1000, timeoutMs))
                    : null;

                pendingFetchControllers.add(controller);

                return fetch(url, {
                    credentials: 'same-origin',
                    ...fetchOptions,
                    signal: controller.signal
                }).finally(() => {
                    if (timeout) clearTimeout(timeout);
                    pendingFetchControllers.delete(controller);
                });
            }

            function waitForRequestRetry(delayMs) {
                return new Promise(resolve => setTimeout(resolve, Math.max(250, Math.min(2500, delayMs || 750))));
            }

            async function parseResponsePayload(response) {
                const clone = response.clone();
                let data = {};
                let text = '';

                try {
                    const parsed = await response.json();
                    data = parsed && typeof parsed === 'object' ? parsed : {};
                } catch (error) {
                    try {
                        text = await clone.text();
                    } catch (textError) {
                        text = '';
                    }
                }

                return { data, text };
            }

            function validationErrorMessage(errors) {
                if (!errors || typeof errors !== 'object') return '';
                const first = Object.values(errors).flat().find(Boolean);
                return first ? String(first) : '';
            }

            function responseErrorMessage(response, payload, fallbackMessage = 'The request could not be completed.') {
                const data = payload?.data || {};
                const explicitMessage = data.error || data.message || validationErrorMessage(data.errors);
                if (explicitMessage) return String(explicitMessage);

                if (response.status === 419) {
                    return 'Your secure session expired. Please refresh the page, then submit the answer again.';
                }
                if (response.status === 422) {
                    return 'Some answer details were rejected. Please check your answer and try again.';
                }
                if (response.status === 403) {
                    return 'This interview session is no longer active for your account.';
                }
                if (response.status === 409) {
                    return 'The interview question changed while submitting. Please refresh the session and continue.';
                }
                if (response.status === 429) {
                    return 'The service is busy right now. Your answer is still on screen; please try again in a moment.';
                }
                if (response.status >= 500) {
                    return 'The server had a temporary problem while sending your answer. Please try again.';
                }

                const plainText = String(payload?.text || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                return plainText ? plainText.slice(0, 220) : fallbackMessage;
            }

            function isRetryableRequestError(error) {
                if (!error) return false;
                if (error.name === 'AbortError') return false;
                if (!navigator.onLine) return true;
                return !error.status || [408, 425, 429, 500, 502, 503, 504].includes(Number(error.status));
            }

            async function postFormJson(url, formData, fallbackMessage = 'The request could not be completed.') {
                const response = await managedFetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const payload = await parseResponsePayload(response);

                if (!response.ok) {
                    const error = new Error(responseErrorMessage(response, payload, fallbackMessage));
                    error.status = response.status;
                    error.payload = payload.data;
                    throw error;
                }

                return payload.data || {};
            }

            async function postFormJsonWithRetry(url, formData, options = {}) {
                const attempts = Math.max(1, Number(options.attempts || 1));
                const fallbackMessage = options.fallbackMessage || 'The request could not be completed.';
                let lastError = null;

                for (let attempt = 1; attempt <= attempts; attempt++) {
                    try {
                        return await postFormJson(url, formData, fallbackMessage);
                    } catch (error) {
                        lastError = error;
                        if (attempt >= attempts || !isRetryableRequestError(error)) {
                            throw error;
                        }

                        setTranscriptionStatus('Connection hiccup - retrying answer submit', '#fbbf24');
                        await waitForRequestRetry(650 * attempt);
                    }
                }

                throw lastError || new Error(fallbackMessage);
            }

            function abortManagedFetches() {
                pendingFetchControllers.forEach(controller => controller.abort());
                pendingFetchControllers.clear();
            }

            function scheduleAutoTranscriptionStart(token) {
                if (token !== questionSpeechToken) return;
                clearTimeout(autoStartAfterQuestionTimer);
                if (!isVoiceTranscriptionMode() || interviewEnding || interviewTerminated) return;

                autoStartAfterQuestionTimer = setTimeout(() => {
                    if (token !== questionSpeechToken || isRecording || interviewEnding || interviewTerminated) return;
                    startRecording({ silent: true });
                }, 450);
            }

            function speechLocalePriority() {
                if (speechLanguage === 'fil' || speechLanguage === 'tl') {
                    return ['fil-PH', 'tl-PH', 'fil', 'tl', 'en-PH', 'en-US', 'en'];
                }

                if (speechLanguage === 'ceb') {
                    return ['ceb-PH', 'ceb', 'fil-PH', 'tl-PH', 'fil', 'tl', 'en-PH', 'en-US', 'en'];
                }

                return [speechLocale, speechLanguage, 'en-PH', 'en-US', 'en'];
            }

            function voiceMatchesLanguage(voice, language) {
                const voiceLang = String(voice.lang || '').toLowerCase();
                const target = String(language || '').toLowerCase();
                return voiceLang === target || voiceLang.startsWith(target + '-') || target.startsWith(voiceLang + '-');
            }

            function voiceLooksNatural(voice) {
                return /google|premium|natural|siri|microsoft|enhanced/i.test(voice.name || '');
            }

            // Initialize preferred voice
            function loadVoices() {
                let voices = window.speechSynthesis.getVoices();
                if (voices.length > 0) {
                    const languagePriority = speechLocalePriority();
                    preferredVoice = languagePriority
                        .map(language => voices.find(v => voiceMatchesLanguage(v, language) && voiceLooksNatural(v)) || voices.find(v => voiceMatchesLanguage(v, language)))
                        .find(Boolean) || voices[0];
                }
            }
            if ('speechSynthesis' in window) {
                window.speechSynthesis.onvoiceschanged = loadVoices;
                loadVoices();
            }

            function clearCaptionInterval() {
                if (captionInterval) {
                    clearInterval(captionInterval);
                    captionInterval = null;
                }
            }

            function captionWordsFor(text) {
                const words = [];
                const pattern = /\S+/g;
                let match;

                while ((match = pattern.exec(String(text || ''))) !== null) {
                    words.push({
                        text: match[0],
                        start: match.index,
                        end: match.index + match[0].length,
                    });
                }

                return words;
            }

            function renderQuestionCaption(words, activeIndex) {
                const caption = document.getElementById('questionCaptionText');
                if (!caption) return;

                if (!words.length || activeIndex < 0) {
                    caption.classList.remove('has-caption', 'is-speaking', 'is-static');
                    caption.innerHTML = '';
                    return;
                }

                const safeIndex = Math.min(activeIndex, words.length - 1);
                const windowSize = 7;
                const start = Math.max(0, Math.min(
                    safeIndex - Math.floor(windowSize / 2),
                    Math.max(0, words.length - windowSize)
                ));
                const visibleWords = words.slice(start, start + windowSize);

                caption.innerHTML = '';
                visibleWords.forEach((word, offset) => {
                    const span = document.createElement('span');
                    const index = start + offset;
                    const isActiveWord = index === safeIndex;
                    span.className = 'question-caption-word' + (isActiveWord ? ' active' : '');
                    span.style.setProperty('opacity', '1', 'important');
                    if (isActiveWord) {
                        span.setAttribute('aria-current', 'true');
                        span.style.setProperty('color', '#fde047', 'important');
                        span.style.setProperty('-webkit-text-fill-color', '#fde047', 'important');
                    } else {
                        span.style.setProperty('color', '#ffffff', 'important');
                        span.style.setProperty('-webkit-text-fill-color', '#ffffff', 'important');
                        span.style.setProperty('text-shadow', '0 2px 6px rgba(0, 0, 0, 0.98), 0 0 4px rgba(0, 0, 0, 0.95)', 'important');
                    }
                    span.textContent = word.text;
                    caption.appendChild(span);
                });
                caption.classList.remove('is-static');
                caption.classList.add('has-caption', 'is-speaking');
            }

            function clearQuestionCaption() {
                const caption = document.getElementById('questionCaptionText');
                if (!caption) return;

                caption.classList.remove('has-caption', 'is-speaking', 'is-static');
                caption.innerHTML = '';
            }

            function wordIndexFromChar(words, charIndex) {
                const safeChar = Number(charIndex) || 0;
                const found = words.findIndex(word => safeChar >= word.start && safeChar < word.end);
                if (found >= 0) return found;

                for (let idx = words.length - 1; idx >= 0; idx--) {
                    if (words[idx].start <= safeChar) return idx;
                }

                return 0;
            }

            function estimatedSpeechTimeoutMs(text) {
                const words = String(text || '').trim().split(/\s+/).filter(Boolean).length;
                return Math.max(4500, Math.min(60000, 2200 + (words * 620)));
            }

            function resolveSpeechCompletion(token, status = 'finished') {
                if (!activeSpeechCompletion || activeSpeechCompletion.token !== token) return;

                clearTimeout(activeSpeechCompletion.timeoutId);
                const resolve = activeSpeechCompletion.resolve;
                activeSpeechCompletion = null;
                resolve(status);
            }

            function resolveAnySpeechCompletion(status = 'cancelled') {
                if (!activeSpeechCompletion) return;

                clearTimeout(activeSpeechCompletion.timeoutId);
                const resolve = activeSpeechCompletion.resolve;
                activeSpeechCompletion = null;
                resolve(status);
            }

            function spectrumBars() {
                return Array.from(document.querySelectorAll('.spectrum-bar'));
            }

            function speechSpectrumMaxHeight() {
                return window.matchMedia('(min-width: 1200px)').matches ? 78 : 52;
            }

            function renderSpeechSpectrum(levels) {
                const bars = spectrumBars();
                if (!bars.length || !Array.isArray(levels) || !levels.length) return;

                const minHeight = window.matchMedia('(min-width: 1200px)').matches ? 18 : 10;
                const maxHeight = speechSpectrumMaxHeight();
                const averageLevel = levels.reduce((total, level) => total + (Number(level) || 0), 0) / levels.length;
                const movement = Math.max(0.48, Math.min(1, (averageLevel || currentAmplitude) * 1.35));

                bars.forEach((bar, idx) => {
                    const ripple = 0.66 + (((idx % 7) / 7) * 0.34);
                    const wave = 0.42 + (Math.random() * 0.58);
                    const openCloseWave = 0.35 + (Math.random() * 0.65);
                    const pop = Math.round(2 + (movement * openCloseWave * (window.matchMedia('(min-width: 1200px)').matches ? 18 : 12)));
                    const height = minHeight + ((maxHeight - minHeight) * movement * ripple * wave);
                    bar.style.height = `${height.toFixed(1)}px`;
                    bar.style.opacity = String(0.68 + (Math.random() * 0.28 * movement));
                    bar.style.setProperty('--bar-pop', `${pop}px`);
                });
            }

            function resetSpeechSpectrumBars() {
                spectrumBars().forEach(bar => {
                    bar.style.height = '';
                    bar.style.opacity = '';
                    bar.style.removeProperty('--bar-pop');
                });
            }

            function stopSpeechSpectrumVisualizer() {
                if (visualizerInterval) clearInterval(visualizerInterval);
                visualizerInterval = null;
                try {
                    activeSpectrumSource?.disconnect?.();
                    activeSpectrumAnalyser?.disconnect?.();
                } catch (error) {
                    console.debug('AI speech spectrum cleanup skipped:', error);
                }
                activeSpectrumAnalyser = null;
                activeSpectrumData = null;
                activeSpectrumSource = null;
                currentAmplitude = 0.2;
                resetSpeechSpectrumBars();
            }

            function averageSpectrumRange(data, start, end) {
                if (!data || !data.length) return 0;

                const safeStart = Math.max(0, Math.min(data.length - 1, start));
                const safeEnd = Math.max(safeStart + 1, Math.min(data.length, end));
                let total = 0;

                for (let idx = safeStart; idx < safeEnd; idx++) {
                    total += data[idx];
                }

                return total / ((safeEnd - safeStart) * 255);
            }

            function startAudioSpeechSpectrum(audioElement) {
                const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
                if (!audioElement || !AudioContextCtor) return false;

                try {
                    speechAudioContext = speechAudioContext || new AudioContextCtor();
                    const analyser = speechAudioContext.createAnalyser();
                    analyser.fftSize = 256;
                    analyser.smoothingTimeConstant = 0.68;

                    const source = speechAudioContext.createMediaElementSource(audioElement);
                    source.connect(analyser);
                    analyser.connect(speechAudioContext.destination);

                    activeSpectrumSource = source;
                    activeSpectrumAnalyser = analyser;
                    activeSpectrumData = new Uint8Array(analyser.frequencyBinCount);
                    speechAudioContext.resume?.();

                    visualizerInterval = setInterval(() => {
                        if (!activeSpectrumAnalyser || !activeSpectrumData) return;

                        activeSpectrumAnalyser.getByteFrequencyData(activeSpectrumData);
                        const bars = spectrumBars();
                        const lowEnergy = averageSpectrumRange(activeSpectrumData, 2, 12);
                        const voiceEnergy = averageSpectrumRange(activeSpectrumData, 8, 54);
                        const speechFloor = Math.max(0.08, Math.min(0.5, voiceEnergy * 0.42));

                        const levels = bars.map((bar, idx) => {
                            const position = idx / Math.max(1, bars.length - 1);
                            const bin = Math.min(
                                activeSpectrumData.length - 1,
                                Math.max(2, Math.round(4 + (position * 58)))
                            );
                            const localEnergy = activeSpectrumData[bin] / 255;
                            const neighborEnergy = activeSpectrumData[Math.min(activeSpectrumData.length - 1, bin + 2)] / 255;
                            const reactiveEnergy = (localEnergy * 0.58) + (neighborEnergy * 0.22) + (voiceEnergy * 0.14) + (lowEnergy * 0.06);

                            return Math.max(speechFloor, Math.pow(Math.min(1, reactiveEnergy * 1.55), 0.78));
                        });

                        renderSpeechSpectrum(levels);
                    }, 36);

                    return true;
                } catch (error) {
                    console.warn('AI speech spectrum unavailable:', error);
                    stopSpeechSpectrumVisualizer();
                    return false;
                }
            }

            function startCadenceSpeechSpectrum(words) {
                const wordCount = Math.max(1, Array.isArray(words) ? words.length : 1);
                let tick = 0;

                visualizerInterval = setInterval(() => {
                    tick += 1;
                    currentAmplitude = Math.max(0.62, currentAmplitude * 0.92);

                    const bars = spectrumBars();
                    const phrasePulse = (Math.sin(tick * 0.42) + 1) / 2;
                    const syllablePulse = (Math.sin(tick * 1.18) + 1) / 2;
                    const sentenceDensity = Math.min(1, 0.46 + (wordCount / 42));
                    const energy = Math.min(1, (currentAmplitude * 0.78) + (phrasePulse * 0.14) + (syllablePulse * 0.12));

                    renderSpeechSpectrum(bars.map((bar, idx) => {
                        const ringOffset = (Math.sin((idx * 0.72) + (tick * 0.3)) + 1) / 2;
                        return Math.max(0.12, Math.min(1, energy * sentenceDensity * (0.56 + (ringOffset * 0.44))));
                    }));
                }, 42);
            }

            function startSpeechSpectrumVisualizer(audioElement, words) {
                stopSpeechSpectrumVisualizer();

                if (!startAudioSpeechSpectrum(audioElement)) {
                    startCadenceSpeechSpectrum(words);
                }
            }

            function pulseSpeechSpectrum(amount = 1) {
                currentAmplitude = Math.max(currentAmplitude, Math.max(0.2, Math.min(1, amount)));
            }

            function startSpeakingUi(text, options = {}) {
                const speechOptions = typeof options === 'boolean'
                    ? { boundaryAware: options }
                    : (options || {});
                const boundaryAware = speechOptions.boundaryAware === true;
                clearCaptionInterval();
                document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'block');
                document.getElementById('aiAvatarHead')?.style.setProperty('--avatar-ring-color', '#34d399');
                document.getElementById('aiQuestionText').innerText = text;

                const words = captionWordsFor(text);
                let currentWordIdx = words.length ? 0 : -1;
                let boundaryFired = false;
                renderQuestionCaption(words, currentWordIdx);
                startSpeechSpectrumVisualizer(speechOptions.audioElement || null, words);

                captionInterval = setInterval(() => {
                    if (boundaryAware && boundaryFired) return;

                    if (currentWordIdx < words.length - 1) {
                        currentWordIdx++;
                        renderQuestionCaption(words, currentWordIdx);
                        pulseSpeechSpectrum(1);
                    } else {
                        clearCaptionInterval();
                    }
                }, 350);

                return {
                    markBoundary: (charIndex = null) => {
                        boundaryFired = true;
                        clearCaptionInterval();
                        currentWordIdx = charIndex === null
                            ? Math.min(currentWordIdx + 1, words.length - 1)
                            : wordIndexFromChar(words, charIndex);
                        renderQuestionCaption(words, currentWordIdx);
                        pulseSpeechSpectrum(1);
                    },
                };
            }

            function finishSpeakingUi(text, token, startTimerAfterSpeech) {
                if (token !== questionSpeechToken) return;

                document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'none');
                document.getElementById('aiAvatarHead')?.style.setProperty('--avatar-ring-color', '#8b5cf6');
                stopSpeechSpectrumVisualizer();
                clearCaptionInterval();
                clearQuestionCaption();
                document.getElementById('aiQuestionText').innerText = text;
                if (startTimerAfterSpeech) {
                    startQuestionTimer();
                    scheduleAutoTranscriptionStart(token);
                }
                resolveSpeechCompletion(token);
            }

            function cancelQuestionAudio() {
                if (activeQuestionAudio) {
                    activeQuestionAudio.pause();
                    activeQuestionAudio.removeAttribute('src');
                    activeQuestionAudio.load();
                    activeQuestionAudio = null;
                }
            }

            function cancelQuestionSpeechOutput() {
                questionSpeechToken++;
                resolveAnySpeechCompletion();
                cancelQuestionAudio();
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }
                clearCaptionInterval();
                renderQuestionCaption([], -1);
                stopSpeechSpectrumVisualizer();
                document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'none');
                const avatarHead = document.getElementById('aiAvatarHead');
                if (avatarHead) avatarHead.style.setProperty('--avatar-ring-color', '#8b5cf6');
            }

            async function serverSpeechUrl(questionId, speechText = '') {
                const cleanSpeechText = cleanTranscriptText(speechText);
                if ((!questionId && !cleanSpeechText) || serverVoiceUnavailable) return null;

                const cacheKey = questionId ? `q:${questionId}` : `text:${cleanSpeechText}`;
                if (serverSpeechUrlCache.has(cacheKey)) {
                    return serverSpeechUrlCache.get(cacheKey);
                }

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);
                if (questionId) {
                    formData.append('question_id', questionId);
                }
                if (cleanSpeechText) {
                    formData.append('speech_text', cleanSpeechText);
                }

                const response = await managedFetch('{{ route("interview.speech") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'audio/mpeg',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    if (response.status === 400 || response.status === 403 || response.status === 503) {
                        serverVoiceUnavailable = true;
                    }
                    return null;
                }

                const blob = await response.blob();
                if (!blob || blob.size === 0) return null;

                const url = URL.createObjectURL(blob);
                serverSpeechUrlCache.set(cacheKey, url);

                return url;
            }

            async function speakWithServerVoice(text, token, startTimerAfterSpeech, questionId, speechText = '') {
                if (!window.Audio || (!questionId && !speechText) || serverVoiceUnavailable || interviewTerminated) {
                    return false;
                }

                try {
                    const url = await serverSpeechUrl(questionId, speechText);
                    if (!url || token !== questionSpeechToken || interviewTerminated) return false;

                    const audio = new Audio(url);
                    activeQuestionAudio = audio;

                    audio.addEventListener('play', () => {
                        if (token !== questionSpeechToken) return;
                        startSpeakingUi(text, { audioElement: audio });
                    }, { once: true });

                    audio.addEventListener('ended', () => {
                        activeQuestionAudio = null;
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    }, { once: true });

                    audio.addEventListener('error', () => {
                        activeQuestionAudio = null;
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    }, { once: true });

                    await audio.play();

                    return true;
                } catch (error) {
                    console.warn('Server AI voice unavailable, using device voice.', error);
                    cancelQuestionAudio();

                    return false;
                }
            }

            function speakWithDeviceVoice(text, token, startTimerAfterSpeech) {
                if (interviewTerminated) {
                    resolveSpeechCompletion(token, 'cancelled');
                    return;
                }

                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                    let utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = speechLocale;
                    if (preferredVoice) utterance.voice = preferredVoice;
                    utterance.rate = 0.95;
                    utterance.pitch = 1.0;

                    let speechUi = null;

                    utterance.onboundary = function(e) {
                        if(e.name === 'word' || (typeof e.charIndex === 'number' && e.charIndex >= 0)) {
                            if (speechUi) speechUi.markBoundary(e.charIndex);

                            currentAmplitude = 1.0;
                        }
                    };

                    utterance.onstart = function() {
                        speechUi = startSpeakingUi(text, true);
                    };

                    utterance.onend = function() {
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    };

                    utterance.onerror = function() {
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    };

                    try {
                        window.speechSynthesis.speak(utterance);
                    } catch (error) {
                        console.warn('Device speech failed:', error);
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    }
                } else {
                    document.getElementById('aiQuestionText').innerText = text;
                    clearQuestionCaption();
                    if (startTimerAfterSpeech) startQuestionTimer();
                    if (startTimerAfterSpeech) scheduleAutoTranscriptionStart(token);
                    resolveSpeechCompletion(token);
                }
            }

            async function speakQuestion(text, options = {}) {
                if (interviewTerminated) return 'cancelled';

                cancelQuestionSpeechOutput();
                const token = ++questionSpeechToken;
                const startTimerAfterSpeech = options.startTimerAfterSpeech === true;
                const completion = new Promise(resolve => {
                    const timeoutId = setTimeout(() => {
                        if (token === questionSpeechToken) {
                            finishSpeakingUi(text, token, startTimerAfterSpeech);
                        }
                    }, estimatedSpeechTimeoutMs(text));
                    activeSpeechCompletion = { token, resolve, timeoutId };
                });

                if (isRecording) {
                    await pauseRecording();
                }

                const usedServerVoice = serverAiVoiceEnabled
                    ? await speakWithServerVoice(text, token, startTimerAfterSpeech, options.questionId, options.speechText || '')
                    : false;
                if (usedServerVoice || token !== questionSpeechToken || interviewTerminated) return completion;

                speakWithDeviceVoice(text, token, startTimerAfterSpeech);

                return completion;
            }

            function formatSeconds(total) {
                const safeTotal = Math.max(0, Math.round(total || 0));
                const m = Math.floor(safeTotal / 60).toString().padStart(2, '0');
                const s = (safeTotal % 60).toString().padStart(2, '0');
                return m + ':' + s;
            }

            function getQuestionElapsedSeconds() {
                if (!questionStartedAt) return questionElapsedSeconds || 0;
                return Math.max(0, Math.round((Date.now() - questionStartedAt) / 1000));
            }

            function updateQuestionTimerDisplay() {
                const chip = document.getElementById('questionTimerChip');
                const timer = document.getElementById('perQuestionTimer');
                if (!chip || !timer) return;

                const elapsed = getQuestionElapsedSeconds();
                questionElapsedSeconds = elapsed;

                if (perQuestionLimitSeconds <= 0) {
                    timer.innerText = 'Self-paced';
                    chip.className = 'session-chip';
                    return;
                }

                const remaining = perQuestionLimitSeconds - elapsed;
                timer.innerText = formatSeconds(remaining);
                chip.className = remaining <= 15 ? 'session-chip danger' : (remaining <= 30 ? 'session-chip warning' : 'session-chip');

                if (remaining <= 0) {
                    handleQuestionTimeout();
                }
            }

            function startQuestionTimer() {
                clearInterval(questionTimerInterval);
                questionElapsedSeconds = answersData[currentQIdx]?.elapsed_seconds || 0;
                questionStartedAt = Date.now() - (questionElapsedSeconds * 1000);
                captureTranscriptTimeline('question_loaded', true);
                updateQuestionTimerDisplay();
                questionTimerInterval = setInterval(() => {
                    updateQuestionTimerDisplay();
                    if (getQuestionElapsedSeconds() - lastTimelineCaptureAt >= 5) {
                        captureTranscriptTimeline('progress');
                    }
                }, 1000);
            }

            function stopQuestionTimer() {
                clearInterval(questionTimerInterval);
                questionTimerInterval = null;
                if (answersData[currentQIdx]) {
                    answersData[currentQIdx].elapsed_seconds = getQuestionElapsedSeconds();
                }
            }

            function captureTranscriptTimeline(eventName = 'progress', force = false, extra = {}) {
                if (!answersData[currentQIdx]) return;
                const elapsed = getQuestionElapsedSeconds();
                if (!force && elapsed === lastTimelineCaptureAt) return;
                lastTimelineCaptureAt = elapsed;
                const text = document.getElementById('answerTextarea')?.value || '';
                answersData[currentQIdx].transcript_timeline = answersData[currentQIdx].transcript_timeline || [];
                answersData[currentQIdx].transcript_timeline.push({
                    at: elapsed,
                    event: eventName,
                    words: text.trim().split(/\s+/).filter(Boolean).length,
                    chars: text.length,
                    ...extra
                });
            }

            function handleAnswerPaste(event) {
                if (!answersData[currentQIdx]) return;

                const clipboard = event.clipboardData || window.clipboardData;
                const pastedText = clipboard ? (clipboard.getData('text') || clipboard.getData('Text') || '') : '';
                const pastedChars = pastedText.length;

                answersData[currentQIdx].paste_event_count = (answersData[currentQIdx].paste_event_count || 0) + 1;
                answersData[currentQIdx].pasted_character_count = (answersData[currentQIdx].pasted_character_count || 0) + pastedChars;

                setTimeout(() => {
                    captureTranscriptTimeline(pastedChars >= 80 ? 'large_paste' : 'paste', true, {
                        pasted_chars: pastedChars
                    });
                    triggerAnalysis();
                }, 0);
            }

            function handleQuestionTimeout() {
                clearInterval(questionTimerInterval);
                if (document.querySelector('.next-btn-class:disabled')) return;
                submitAnswer({ timedOut: true });
            }

            function scheduleStateSave() {
                if (interviewEnding || interviewTerminated) return;
                clearTimeout(stateSaveDebounce);
                stateSaveDebounce = setTimeout(autoSaveState, 1200);
            }

            function restoreChatHistory() {
                const chatContainer = document.getElementById('chatTranscriptContainer');
                chatContainer.innerHTML = '';
                if (!Array.isArray(interviewChatHistory) || interviewChatHistory.length === 0) return false;
                interviewChatHistory.forEach(item => appendChatMessage(item.role, item.text, false));
                return true;
            }

            function questionSnapshot() {
                return questions.map(question => ({
                    id: question.id,
                    question_text: question.question_text,
                    source_name: question.source_name || '',
                    source_url: question.source_url || '',
                    source_type: question.source_type || ''
                }));
            }

            function naturalDelayFor(text, minimum = 2200, maximum = 5200) {
                const words = String(text || '').trim().split(/\s+/).filter(Boolean).length;
                return Math.max(minimum, Math.min(maximum, 900 + (words * 115)));
            }

            function pauseFor(ms) {
                return new Promise(resolve => setTimeout(resolve, Math.max(0, ms || 0)));
            }

            function waitForMinimumElapsed(startedAt, minimumMs) {
                const remaining = Math.max(0, minimumMs - (Date.now() - startedAt));
                return remaining > 0 ? pauseFor(remaining) : Promise.resolve();
            }

            function pluralizeQuestionCount() {
                return targetQuestionCount === 1 ? '1 question' : `${targetQuestionCount} questions`;
            }

            function isOpeningQuestion(question) {
                return question && question.source_type === 'real_interview_opening';
            }

            function hasOpeningQuestion() {
                return questions.length > 0 && isOpeningQuestion(questions[0]);
            }

            function questionDisplayNumber(idx) {
                return hasOpeningQuestion() ? idx : idx + 1;
            }

            function isLastScoredQuestion(idx) {
                return questionDisplayNumber(idx) >= targetQuestionCount;
            }

            function isPenultimateScoredQuestion(idx) {
                return questionDisplayNumber(idx) >= targetQuestionCount - 1;
            }

            function candidateFirstName(answerText) {
                const clean = String(answerText || '').replace(/\s+/g, ' ').trim();
                const patterns = [
                    /\bmy name is\s+([A-Z][a-zA-Z'-]{1,30})\b/i,
                    /\bi am\s+([A-Z][a-zA-Z'-]{1,30})\b/i,
                    /\bi'm\s+([A-Z][a-zA-Z'-]{1,30})\b/i,
                    /^\s*([A-Z][a-zA-Z'-]{1,30})\b/
                ];
                const blockedNames = new Set(['i', 'im', "i'm", 'am', 'my', 'name', 'hello', 'hi', 'yes', 'no']);

                for (const pattern of patterns) {
                    const match = clean.match(pattern);
                    if (match && match[1]) {
                        const candidate = match[1].replace(/[^a-zA-Z'-]/g, '');
                        if (candidate.length > 1 && !blockedNames.has(candidate.toLowerCase())) {
                            return candidate.charAt(0).toUpperCase() + candidate.slice(1).toLowerCase();
                        }
                    }
                }

                return '';
            }

            function openingConversationText() {
                const modeLine = liveFeedbackMode === 'real_interview'
                    ? 'I will save feedback until the end.'
                    : 'I may ask follow-ups based on your answers.';

                return `Hi, I'm Mia, good to meet you. I will be your interviewer for the ${sessionTargetPosition} role. We have ${pluralizeQuestionCount()} today. ${modeLine} To begin, I would like to get to know you first.`;
            }

            function closingConversationText() {
                return `Thank you for walking me through your answers today. This ${sessionTargetPosition} interview is now complete, and your responses are being analyzed for feedback.`;
            }

            function setAnswerInputEnabled(enabled) {
                const textarea = document.getElementById('answerTextarea');
                if (textarea) textarea.disabled = !enabled;
                document.querySelectorAll('.next-btn-class').forEach(el => el.disabled = !enabled);
            }

            function showInterviewerConversation(text, counterText = null) {
                const qText = document.getElementById('aiQuestionText');
                if (qText) qText.innerText = text;
                setRepeatPrompt(text, {
                    phase: counterText === 'Done' ? 'closing' : 'conversation',
                    speechText: text
                });

                const qCounter = document.getElementById('qCounter');
                if (qCounter && counterText) qCounter.innerText = counterText;

                const sourceLine = document.getElementById('aiQuestionSource');
                if (sourceLine) {
                    sourceLine.innerHTML = '';
                    sourceLine.style.display = 'none';
                }
            }

            async function beginOpeningConversation() {
                if (openingHasPlayed || interviewTerminated) {
                    await loadQuestion(currentQIdx, { append: true });
                    return;
                }

                openingHasPlayed = true;
                const introText = openingConversationText();
                setAnswerInputEnabled(false);
                appendChatMessage('interviewer', introText);
                showInterviewerConversation(introText, 'Intro');
                scheduleStateSave();
                await speakQuestion(introText, {
                    startTimerAfterSpeech: false,
                    phase: 'intro',
                    speechText: introText
                });

                if (interviewTerminated) return;

                setAnswerInputEnabled(true);
                await loadQuestion(currentQIdx, { append: true });
            }

            async function playClosingConversationAndSubmit() {
                const closingText = closingConversationText();
                const closingStartedAt = Date.now();
                setAnswerInputEnabled(false);
                appendChatMessage('interviewer', closingText);
                showInterviewerConversation(closingText, 'Done');
                await speakQuestion(closingText, {
                    startTimerAfterSpeech: false,
                    phase: 'closing',
                    speechText: closingText
                });
                await waitForMinimumElapsed(closingStartedAt, naturalDelayFor(closingText, 3600, 7600));

                if (!interviewTerminated) {
                    await finishInterview();
                }
            }

            async function concludeAndFinishInterview() {
                if (interviewEnding) return;

                await finalizeCurrentTranscriptionForSubmit();
                interviewEnding = true;
                setAnswerInputEnabled(false);
                await playClosingConversationAndSubmit();
            }

            let interviewAutoFullscreenRequested = false;
            let interviewSessionBrowserFullscreenRequested = false;

            function refreshInterviewFullscreenLayout() {
                if (window.SpeakReadyViewport?.refreshNow) {
                    window.SpeakReadyViewport.refreshNow();
                }
                window.dispatchEvent(new Event('resize'));
                window.requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
            }

            function isMobileInterviewViewport() {
                return window.matchMedia('(max-width: 768px)').matches;
            }

            function enterInterviewFullscreen(options = {}) {
                const shouldUseMobileLayout = isMobileInterviewViewport();
                if (shouldUseMobileLayout) {
                    document.body.classList.add('mobile-interview-fullscreen');
                }
                document.body.classList.add('interview-session-browser-fullscreen');
                updateMobileFullscreenToggle();
                refreshInterviewFullscreenLayout();

                const root = document.documentElement;
                if (!document.fullscreenElement && root.requestFullscreen) {
                    interviewSessionBrowserFullscreenRequested = true;
                    root.requestFullscreen({ navigationUI: 'hide' })
                        .then(() => {
                            interviewAutoFullscreenRequested = options.auto === true;
                            interviewSessionBrowserFullscreenRequested = true;
                            updateMobileFullscreenToggle();
                            refreshInterviewFullscreenLayout();
                        })
                        .catch(() => {
                            interviewAutoFullscreenRequested = false;
                            interviewSessionBrowserFullscreenRequested = false;
                            document.body.classList.remove('interview-session-browser-fullscreen');
                            updateMobileFullscreenToggle();
                            refreshInterviewFullscreenLayout();
                        });
                }
            }

            function exitInterviewFullscreen(options = {}) {
                const shouldExitBrowser = options.exitBrowser !== false;
                const exitPromise = shouldExitBrowser && document.fullscreenElement && document.exitFullscreen
                    ? document.exitFullscreen().catch(() => {})
                    : Promise.resolve();

                return exitPromise.finally(() => {
                    interviewAutoFullscreenRequested = false;
                    interviewSessionBrowserFullscreenRequested = false;
                    document.body.classList.remove('mobile-interview-fullscreen', 'interview-session-browser-fullscreen');
                    updateMobileFullscreenToggle();
                    refreshInterviewFullscreenLayout();
                });
            }

            function exitAutoInterviewFullscreen() {
                if (!interviewAutoFullscreenRequested && !interviewSessionBrowserFullscreenRequested) {
                    document.body.classList.remove('mobile-interview-fullscreen', 'interview-session-browser-fullscreen');
                    updateMobileFullscreenToggle();
                    refreshInterviewFullscreenLayout();
                    return Promise.resolve();
                }

                return exitInterviewFullscreen();
            }

            function enterMobileFullscreen() {
                if (!isMobileInterviewViewport()) return;
                enterInterviewFullscreen();
            }

            function exitMobileFullscreen() {
                return exitInterviewFullscreen();
            }

            function toggleMobileFullscreen() {
                if (document.body.classList.contains('mobile-interview-fullscreen') || document.body.classList.contains('interview-session-browser-fullscreen') || document.fullscreenElement) {
                    exitInterviewFullscreen();
                } else {
                    enterInterviewFullscreen();
                }
            }

            function updateMobileFullscreenToggle() {
                const toggle = document.getElementById('responseFullscreenToggle');
                if (!toggle) return;

                const fullscreenOn = document.body.classList.contains('mobile-interview-fullscreen')
                    || document.body.classList.contains('interview-session-browser-fullscreen')
                    || Boolean(document.fullscreenElement);
                toggle.title = fullscreenOn ? 'Exit fullscreen' : 'Enter fullscreen';
                toggle.setAttribute('aria-label', toggle.title);
                toggle.innerHTML = fullscreenOn
                    ? '<i class="fa-solid fa-compress"></i>'
                    : '<i class="fa-solid fa-expand"></i>';
            }

            function handleBrowserFullscreenChange() {
                if (!document.fullscreenElement && interviewStarted) {
                    interviewAutoFullscreenRequested = false;
                    interviewSessionBrowserFullscreenRequested = false;
                    document.body.classList.remove('mobile-interview-fullscreen', 'interview-session-browser-fullscreen');
                }

                updateMobileFullscreenToggle();
                refreshInterviewFullscreenLayout();
            }

            window.exitMobileFullscreen = exitMobileFullscreen;
            window.toggleMobileFullscreen = toggleMobileFullscreen;
            window.enterInterviewFullscreen = enterInterviewFullscreen;

            function startInterviewSession() {
                if (interviewStarted || interviewTerminated) return;
                
                interviewStarted = true;
                enterInterviewFullscreen({ auto: true });
                document.getElementById('workspaceWrapper').style.display = 'block';
                document.getElementById('workspaceWrapper').classList.toggle('real-interview-mode', liveFeedbackMode === 'real_interview');
                document.getElementById('interviewControls').style.opacity = '1';
                document.getElementById('interviewControls').style.pointerEvents = 'auto';
                
                if (cameraDetectionEnabled) initCamera();
                
                if(isVoiceTranscriptionMode()) {
                    applyResponseModeUi();
                    const transcriptionEngine = preferredTranscriptionEngine();
                    if (!transcriptionEngine) {
                        const message = transcriptionUnavailableMessage();
                        setTranscriptionStatus(message, '#f87171');
                        setVoiceControlsEnabled(false, message);
                        showSessionNotice(`${message} You can type your answer instead.`, 'warning');
                    } else if (transcriptionEngine === 'server') {
                        setVoiceControlsEnabled(true);
                        setTranscriptionStatus('AI transcription ready (updates every few seconds)');
                    } else {
                        setVoiceControlsEnabled(true);
                    }
                } else {
                    applyResponseModeUi();
                }

                timerInterval = setInterval(() => {
                    timerSeconds++;
                    const m = Math.floor(timerSeconds / 60).toString().padStart(2, '0');
                    const s = (timerSeconds % 60).toString().padStart(2, '0');
                    const interviewTimer = document.getElementById('interviewTimer');
                    if (interviewTimer) interviewTimer.innerText = m + ':' + s;
                    
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

                const restoredChat = restoreChatHistory();
                (async () => {
                    if (!restoredChat && currentQIdx === 0 && !openingHasPlayed) {
                        await beginOpeningConversation();
                    } else {
                        await loadQuestion(currentQIdx, { append: !restoredChat });
                    }
                })();
                
                if (!answerListenersBound) {
                    answerListenersBound = true;
                    const answerTextarea = document.getElementById('answerTextarea');
                    if (answerTextarea) {
                        answerTextarea.addEventListener('input', handleAnswerInput);
                        answerTextarea.addEventListener('paste', handleAnswerPaste);
                    }
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'hidden') autoSaveState();
                    });
                }
            }

            async function loadQuestion(idx, options = {}) {
                if (interviewTerminated || interviewEnding) return;
                currentQIdx = idx;
                const q = questions[idx];
                if (!q) return;
                setAnswerInputEnabled(false);
                
                document.getElementById('aiQuestionText').innerText = '...';
                document.getElementById('qCounter').innerText = isOpeningQuestion(q)
                    ? 'Intro'
                    : Math.min(questionDisplayNumber(idx), targetQuestionCount) + '/' + targetQuestionCount;
                updateQuestionSource(q);

                // Append AI question to chat log if it's the first time seeing it
                const questionDisplayKey = String(q.id || idx);
                if (options.append !== false && !displayedQuestionIds.has(questionDisplayKey)) {
                    appendChatMessage('interviewer', q.question_text);
                    displayedQuestionIds.add(questionDisplayKey);
                }

                const answerState = answersData[idx] || defaultAnswerState();
                const restoredAnswerText = String(answerState.text || answerState.speech_transcript || '');
                answerState.text = restoredAnswerText;
                answersData[idx] = answerState;
                document.getElementById('answerTextarea').value = restoredAnswerText;
                applyResponseModeUi();
                syncSelfConfidenceControl(answerState.confidence_score ?? answerState.self_reported_confidence ?? 0);
                resetSpeechRecognitionBufferFromTextarea();
                lastTimelineCaptureAt = 0;
                
                setRepeatPrompt(q.question_text, {
                    questionId: q.id,
                    phase: 'question',
                    speechText: q.question_text
                });
                await speakQuestion(q.question_text, { startTimerAfterSpeech: true, questionId: q.id });
                if (interviewTerminated || interviewEnding) return;
                setAnswerInputEnabled(true);
                
                triggerAnalysis();
                scheduleStateSave();
            }

            function updateQuestionSource(question) {
                const sourceLine = document.getElementById('aiQuestionSource');
                if (!sourceLine) return;

                const sourceType = question?.source_type || sourceLine.dataset.defaultType || '';
                const isAiAdapted = /ai|adapted|generated/i.test(sourceType);
                const name = trustedSourceDisplayName(question?.source_name || sourceLine.dataset.defaultName || '', isAiAdapted);
                const url = question?.source_url || sourceLine.dataset.defaultUrl || '';
                sourceLine.innerHTML = '';

                if (!name) {
                    sourceLine.style.display = 'none';
                    return;
                }

                sourceLine.style.display = 'flex';
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-link';
                const label = document.createElement('span');
                label.className = 'source-label';
                label.textContent = isAiAdapted ? 'AI-adapted from:' : 'Source:';
                const value = url ? document.createElement('a') : document.createElement('span');
                value.textContent = name;
                value.title = isAiAdapted
                    ? 'The question is AI-adapted from the same topic/content area, not copied word for word.'
                    : 'Trusted source used for this question set.';

                if (url) {
                    value.href = url;
                    value.target = '_blank';
                    value.rel = 'noopener';
                }

                sourceLine.append(icon, label, value);
            }

            function trustedSourceDisplayName(sourceName, isAiAdapted) {
                let cleanName = String(sourceName || '').trim();
                cleanName = cleanName.replace(/^User AI Generated\s*\([^)]+\)\s*via\s*/i, '');
                cleanName = cleanName.replace(/^AI Generated\s*\([^)]+\)\s*via\s*/i, '');
                cleanName = cleanName.replace(/^User AI Generated\s*\([^)]+\)\s*$/i, '');
                cleanName = cleanName.replace(/^AI Generated\s*\([^)]+\)\s*$/i, '');

                if (!cleanName && isAiAdapted) {
                    return 'trusted Philippines interview source';
                }

                return cleanName;
            }

            function setRepeatPrompt(text, options = {}) {
                currentRepeatPrompt = String(text || '').trim();
                currentRepeatOptions = {
                    questionId: options.questionId || null,
                    phase: options.phase || 'repeat',
                    speechText: options.speechText || currentRepeatPrompt
                };
            }

            function repeatQuestion() {
                const fallbackQuestion = questions && questions[currentQIdx] ? questions[currentQIdx] : null;
                const repeatText = currentRepeatPrompt || fallbackQuestion?.question_text || '';
                if(repeatText) {
                    speakQuestion(repeatText, {
                        ...currentRepeatOptions,
                        questionId: currentRepeatOptions.questionId || fallbackQuestion?.id,
                        speechText: currentRepeatOptions.speechText || repeatText,
                        startTimerAfterSpeech: false
                    });
                }
            }



            const analysisStopWords = new Set([
                'about', 'after', 'again', 'also', 'and', 'are', 'because', 'been', 'before', 'being', 'but', 'can',
                'could', 'did', 'does', 'for', 'from', 'had', 'has', 'have', 'how', 'into', 'interview', 'job',
                'more', 'most', 'that', 'the', 'their', 'then', 'there', 'this', 'those', 'through', 'tell', 'than',
                'was', 'were', 'what', 'when', 'where', 'which', 'while', 'with', 'would', 'you', 'your'
            ]);

            const situationMarkers = [
                'when', 'while', 'during', 'in my previous role', 'at my last job', 'on a project', 'our team',
                'a client', 'a customer', 'deadline', 'challenge', 'problem', 'situation', 'scenario'
            ];
            const taskMarkers = [
                'my role', 'responsible for', 'i was responsible', 'i needed to', 'i had to', 'my goal',
                'the goal', 'objective', 'task', 'asked to', 'expected to', 'requirement'
            ];
            const actionMarkers = [
                'i led', 'i built', 'i created', 'i implemented', 'i designed', 'i analyzed', 'i coordinated',
                'i resolved', 'i improved', 'i developed', 'i organized', 'i prioritized', 'i communicated',
                'i worked with', 'i decided', 'i proposed', 'i tested', 'i delivered', 'we built', 'we implemented'
            ];
            const resultMarkers = [
                'result', 'outcome', 'impact', 'increased', 'decreased', 'reduced', 'improved', 'saved',
                'delivered', 'launched', 'resolved', 'completed', 'achieved', 'learned', 'led to', 'as a result'
            ];
            const fillerPattern = /\b(you know|i mean|sort of|kind of|um+|uh+|erm+|hmm+|like|basically|literally|actually)\b/gi;
            const unprofessionalPattern = /\b(whatever|stuff|things|idk|lol|yeah|nah|kinda|sorta)\b/gi;

            function canonicalFillerWord(word) {
                const normalized = String(word || '').toLowerCase().replace(/\s+/g, ' ').trim();
                if (/^um+$/.test(normalized)) return 'um';
                if (/^uh+$/.test(normalized)) return 'uh';
                if (/^erm+$/.test(normalized)) return 'erm';
                if (/^hmm+$/.test(normalized)) return 'hmm';
                return normalized;
            }

            function recordFillerEvents(segment) {
                const state = answersData[currentQIdx];
                if (!state || !isVoiceTranscriptionMode()) return;
                state.observation_data = state.observation_data || { filler_events: [], camera_samples: [] };
                state.observation_data.filler_events = Array.isArray(state.observation_data.filler_events)
                    ? state.observation_data.filler_events
                    : [];
                const matches = String(segment || '').matchAll(new RegExp(fillerPattern.source, 'gi'));
                for (const match of matches) {
                    state.observation_data.filler_events.push({
                        word: canonicalFillerWord(match[0]),
                        at_seconds: Math.max(0, Number(state.voice_duration || recTimerSeconds || 0))
                    });
                }
                state.observation_data.filler_events = state.observation_data.filler_events.slice(-500);
            }

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function clampScore(value) {
                return Math.max(0, Math.min(100, Math.round(value)));
            }

            function normalizeText(text) {
                return ` ${String(text || '').toLowerCase().replace(/\s+/g, ' ').trim()} `;
            }

            function escapeRegExp(value) {
                return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            function includesAny(text, terms) {
                return terms.some(term => new RegExp(`\\b${escapeRegExp(term)}\\b`, 'i').test(text));
            }

            function meaningfulWords(text) {
                return String(text || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9\s'-]/g, ' ')
                    .split(/\s+/)
                    .map(word => word.replace(/^'+|'+$/g, ''))
                    .filter(word => word.length > 2 && !analysisStopWords.has(word));
            }

            function isBehavioralQuestion(questionText) {
                return /\b(tell me about a time|describe a time|give me an example|how did you handle|conflict|challenge|failure|mistake|leadership|teamwork|difficult|situation)\b/i.test(questionText || '');
            }

            function detectStarSignals(text) {
                const normalized = normalizeText(text);
                const wordCount = meaningfulWords(text).length;
                const metricPattern = /(\b\d+(\.\d+)?\s?%|\$\s?\d+|\b\d+\s?(users|customers|clients|people|hours|days|weeks|months|tickets|cases|calls|projects|minutes|seconds|revenue|sales)\b|\b(by|from|to)\s+\d+)/i;

                const hasS = wordCount >= 10 && includesAny(normalized, situationMarkers);
                const hasT = includesAny(normalized, taskMarkers);
                const hasA = includesAny(normalized, actionMarkers) || /\b(i|we)\s+(led|built|created|implemented|designed|analyzed|coordinated|resolved|improved|developed|organized|prioritized|communicated|tested|delivered)\b/i.test(normalized);
                const hasR = includesAny(normalized, resultMarkers) || metricPattern.test(text);

                return {
                    hasS,
                    hasT,
                    hasA,
                    hasR,
                    componentCount: [hasS, hasT, hasA, hasR].filter(Boolean).length,
                    hasMetric: metricPattern.test(text)
                };
            }

            function calculateRelevanceScore(answerText, questionText, wordCount, starSignals) {
                if (wordCount === 0) return 0;
                if (wordCount < 8) return clampScore(wordCount * 5);

                const answerWords = new Set(meaningfulWords(answerText));
                const questionWords = [...new Set(meaningfulWords(questionText))].slice(0, 10);
                let matched = 0;

                questionWords.forEach(qWord => {
                    const hasMatch = [...answerWords].some(aWord => aWord === qWord || aWord.startsWith(qWord) || qWord.startsWith(aWord));
                    if (hasMatch) matched++;
                });

                const ratio = questionWords.length > 0 ? matched / questionWords.length : 0.45;
                let score = 35 + (ratio * 50);

                const behavioral = isBehavioralQuestion(questionText);
                if (behavioral && starSignals.componentCount >= 3) score = Math.max(score, 72);
                else if (behavioral && starSignals.componentCount >= 2) score = Math.max(score, 62);
                if (wordCount < 25) score -= 12;
                if (starSignals.hasA && starSignals.hasR) score += 5;

                return clampScore(score);
            }

            function calculateLiveScores(answerText, questionText, wordCount, fillerCount, starSignals) {
                const sentences = answerText.split(/[.!?]+/).map(s => s.trim()).filter(Boolean);
                const sentenceCount = Math.max(1, sentences.length);
                const hasFirstPersonOwnership = /\b(i|my|me)\b/i.test(answerText);
                const hasEndPunctuation = /[.!?]$/.test(answerText.trim());
                const hasRepeatedWord = /\b([a-z]+)\s+\1\b/i.test(answerText);
                const longSentencePenalty = sentences.some(sentence => sentence.split(/\s+/).length > 40) ? 8 : 0;
                const casualMatches = answerText.match(unprofessionalPattern);
                const casualCount = casualMatches ? casualMatches.length : 0;

                let clarity = 28 + Math.min(28, wordCount * 1.1) + (starSignals.componentCount * 8) + Math.min(8, sentenceCount * 2);
                clarity -= longSentencePenalty;
                if (wordCount > 220) clarity -= 10;
                if (wordCount < 15) clarity = Math.min(clarity, 45);

                const relevance = calculateRelevanceScore(answerText, questionText, wordCount, starSignals);

                let grammar = 55 + Math.min(20, wordCount * 0.5) + (hasEndPunctuation ? 8 : 0);
                grammar -= hasRepeatedWord ? 8 : 0;
                grammar -= longSentencePenalty;
                if (wordCount < 15) grammar = Math.min(grammar, 50);

                let professionalism = 58 + (hasFirstPersonOwnership ? 10 : 0) + (starSignals.hasA ? 8 : 0) + (starSignals.hasR ? 8 : 0);
                professionalism -= casualCount * 10;
                if (wordCount < 15) professionalism = Math.min(professionalism, 50);

                const starBonus = isBehavioralQuestion(questionText) ? starSignals.componentCount * 3 : starSignals.hasR ? 5 : 0;
                const readiness = (clarity * 0.25) + (relevance * 0.3) + (grammar * 0.2) + (professionalism * 0.25) + starBonus;

                return {
                    clarity: clampScore(clarity),
                    relevance: clampScore(relevance),
                    grammar: clampScore(grammar),
                    professionalism: clampScore(professionalism),
                    readiness: clampScore(wordCount === 0 ? 0 : readiness)
                };
            }

            function calculateRealtimeConfidenceScore(answerText, wordCount, fillerCount, starSignals, scores) {
                if (wordCount === 0) return 0;

                const state = answersData[currentQIdx] || {};
                const wpm = Number(state.wpm || 0);
                const pauseCount = Number(state.pause_count || 0);
                const voiceDuration = Number(state.voice_duration || 0);
                const hasFirstPersonOwnership = /\b(i|my|me)\b/i.test(answerText);

                let confidence = 24 + Math.min(30, wordCount * 1.2);
                confidence += hasFirstPersonOwnership ? 6 : 0;
                confidence += starSignals.hasA ? 8 : 0;
                confidence += starSignals.hasR ? 7 : 0;
                confidence += starSignals.componentCount >= 3 ? 7 : 0;
                confidence += scores.clarity >= 70 ? 6 : (scores.clarity >= 55 ? 3 : 0);
                confidence += scores.relevance >= 70 ? 5 : (scores.relevance >= 55 ? 2 : 0);
                confidence -= wordCount < 18 ? 14 : 0;
                confidence -= Math.min(18, Math.round((fillerCount / Math.max(1, wordCount)) * 180));

                if (isVoiceTranscriptionMode() && (voiceDuration > 0 || wpm > 0 || pauseCount > 0)) {
                    if (wpm >= 100 && wpm <= 170) {
                        confidence += 8;
                    } else if (wpm >= 80 && wpm <= 210) {
                        confidence += 3;
                    } else if (wpm > 0) {
                        confidence -= 8;
                    }

                    confidence -= Math.min(16, Math.round((pauseCount / Math.max(1, wordCount)) * 120));
                }

                return clampScore(confidence);
            }

            function questionFocus(questionText) {
                const keywords = meaningfulWords(questionText).slice(0, 5);
                return keywords.length > 0 ? keywords.join(' / ') : 'the question asked';
            }

            function biggestSuggestion(answerText, questionText, wordCount, fillerCount, scores, starSignals) {
                if (wordCount === 0) {
                    return 'Give one specific example, your role, the action you took, and the result.';
                }
                if (wordCount < 25) {
                    return 'Expand this into a complete interview answer: context, your responsibility, specific action, and result.';
                }
                if (scores.relevance < 55) {
                    return `Tie the answer more directly to ${questionFocus(questionText)} with a relevant example.`;
                }
                if (isBehavioralQuestion(questionText) && !starSignals.hasS) {
                    return 'Open with the situation so the interviewer understands the context before your action.';
                }
                if (!starSignals.hasT) {
                    return 'State your exact responsibility or goal so your ownership is clear.';
                }
                if (!starSignals.hasA) {
                    return 'Describe the specific actions you personally took, not only what the team did.';
                }
                if (!starSignals.hasR) {
                    return 'Close with the result or impact, ideally with a number, outcome, or lesson learned.';
                }
                if (!starSignals.hasMetric && wordCount >= 40) {
                    return 'Add one measurable detail, such as time saved, quality improved, revenue, volume, or customer impact.';
                }
                if (fillerCount >= 3) {
                    return 'The transcript detected several possible filler phrases. Try a brief silent pause when gathering your next thought.';
                }
                if (wordCount > 220) {
                    return 'Tighten the answer to the strongest 60-90 seconds: situation, decision, action, result.';
                }

                return 'Strong direction. Make it sharper by naming the key decision, tradeoff, and measurable impact.';
            }

            function triggerAnalysis() {
                const text = document.getElementById('answerTextarea').value;
                const currentQuestion = questions[currentQIdx] ? questions[currentQIdx].question_text : '';
                const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
                const charCount = text.length;
                
                document.getElementById('wordCount').innerText = wordCount + ' words';
                document.getElementById('charCount').innerText = charCount + ' characters';

                const starSignals = detectStarSignals(text);
                
                updateStarIcon('starS', starSignals.hasS);
                updateStarIcon('starT', starSignals.hasT);
                updateStarIcon('starA', starSignals.hasA);
                updateStarIcon('starR', starSignals.hasR);

                const deliveryText = isVoiceTranscriptionMode()
                    ? String(answersData[currentQIdx]?.speech_transcript || '')
                    : '';
                const matches = deliveryText.match(fillerPattern);
                const fillers = matches ? matches.length : 0;
                const scores = calculateLiveScores(text, currentQuestion, wordCount, fillers, starSignals);
                const realtimeConfidence = calculateRealtimeConfidenceScore(text, wordCount, fillers, starSignals, scores);
                const tip = biggestSuggestion(text, currentQuestion, wordCount, fillers, scores, starSignals);

                const coachingTip = document.getElementById('coachingTip');
                if (coachingTip) {
                    coachingTip.innerHTML = `<i class="fa-solid fa-lightbulb me-1"></i> <strong>Biggest Suggestion:</strong> ${escapeHtml(tip)}`;
                }
                const metricTargets = {
                    overallReadiness: scores.readiness + '%',
                    metClarity: scores.clarity + '%',
                    metRelevance: scores.relevance + '%',
                    metGrammar: scores.grammar + '%',
                    metProf: scores.professionalism + '%',
                    vaFillers: fillers,
                };
                Object.entries(metricTargets).forEach(([id, value]) => {
                    const target = document.getElementById(id);
                    if (target) target.innerText = value;
                });
                answersData[currentQIdx].text = text;
                answersData[currentQIdx].filler_words = fillers;
                answersData[currentQIdx].confidence_score = realtimeConfidence;
                answersData[currentQIdx].self_reported_confidence = realtimeConfidence;
                syncSelfConfidenceControl(realtimeConfidence);
                answersData[currentQIdx].elapsed_seconds = getQuestionElapsedSeconds();
                if (getQuestionElapsedSeconds() - lastTimelineCaptureAt >= 5) {
                    captureTranscriptTimeline('input');
                }
                scheduleStateSave();
            }

            function updateStarIcon(id, status) {
                const el = document.getElementById(id);
                if (!el) return;
                if(status) {
                    el.className = 'fa-solid fa-circle-check text-success';
                } else {
                    el.className = 'fa-solid fa-circle-xmark text-danger';
                }
            }

            function setRecordingControlButtons(state) {
                const pauseBtn = document.getElementById('micPauseBtn');
                const stopBtn = document.getElementById('micStopBtn');
                const timer = document.getElementById('recordingTimer');

                if (pauseBtn) {
                    pauseBtn.style.display = 'inline-flex';
                    pauseBtn.innerHTML = state === 'paused'
                        ? '<i class="fa-solid fa-play"></i>'
                        : '<i class="fa-solid fa-pause"></i>';
                    pauseBtn.setAttribute('aria-label', state === 'paused' ? 'Resume recording' : 'Pause recording');
                    pauseBtn.setAttribute('title', state === 'paused' ? 'Resume recording' : 'Pause recording');
                }

                if (stopBtn) {
                    stopBtn.style.display = 'inline-flex';
                    stopBtn.setAttribute('aria-label', 'Stop recording');
                    stopBtn.setAttribute('title', 'Stop recording');
                }
                if (timer) timer.style.display = 'block';
            }

            function setVoiceControlsEnabled(enabled, reason = '') {
                ['holdToTalkBtn', 'micPauseBtn', 'micStopBtn'].forEach(id => {
                    const button = document.getElementById(id);
                    if (!button) return;
                    button.disabled = !enabled;
                    button.classList.toggle('voice-control-disabled', !enabled);
                    if (!button.dataset.defaultTitle) {
                        button.dataset.defaultTitle = button.getAttribute('title') || button.textContent.trim() || 'Voice recording';
                    }
                    const title = enabled ? button.dataset.defaultTitle : (reason || 'Voice recording is unavailable');
                    button.setAttribute('title', title);
                    button.setAttribute('aria-label', title);
                });
            }

            async function startRecording(options = {}) {
                const silent = options && options.silent === true;
                if (isRecording) return true;
                if (!isVoiceTranscriptionMode()) {
                    const message = 'Voice recording is disabled in Text Mode.';
                    setTranscriptionStatus('');
                    setVoiceControlsEnabled(false, message);
                    if(!silent) showSessionNotice(message);
                    return false;
                }

                let engine = preferredTranscriptionEngine();
                if (!engine) {
                    const message = transcriptionUnavailableMessage();
                    setTranscriptionStatus(message, '#f87171');
                    setVoiceControlsEnabled(false, message);
                    if(!silent) showSessionNotice(`${message} You can type your answer instead.`);
                    return false;
                }

                if (!isRecordingPaused) {
                    resetSpeechRecognitionBufferFromTextarea();
                }

                if (!await ensureMicrophoneReady(engine)) {
                    if(!silent) {
                        const message = document.getElementById('transcriptionStatus')?.textContent || transcriptionUnavailableMessage();
                        showSessionNotice(`${message} You can type your answer instead.`);
                    }
                    return false;
                }

                lastSpeechEnd = 0;
                shouldAutoRestartRecognition = true;
                isRecording = true;
                isRecordingPaused = false;
                activeTranscriptionEngine = engine;

                let started = engine === 'server'
                    ? startServerTranscriptionEngine()
                    : startSpeechRecognitionEngine();

                if (!started && engine === 'browser' && canUseServerTranscription()) {
                    activeTranscriptionEngine = 'server';
                    engine = 'server';
                    started = await ensureMicrophoneReady('server') && startServerTranscriptionEngine();
                }

                if (!started) {
                    shouldAutoRestartRecognition = false;
                    isRecording = false;
                    isRecordingPaused = false;
                    const message = document.getElementById('transcriptionStatus')?.textContent || transcriptionUnavailableMessage();
                    setVoiceControlsEnabled(false, message);
                    if(!silent) showSessionNotice(`${message} You can type your answer instead.`);
                    return false;
                }

                clearSessionNotice();
                setVoiceControlsEnabled(true);
                setRecordingControlButtons('recording');
                clearInterval(recTimerInterval);
                
                recTimerInterval = setInterval(() => {
                    recTimerSeconds++;
                    const m = Math.floor(recTimerSeconds / 60).toString().padStart(2, '0');
                    const s = (recTimerSeconds % 60).toString().padStart(2, '0');
                    document.getElementById('recordingTimer').innerText = m + ':' + s;
                    const durationTarget = document.getElementById('vaDuration');
                    if (durationTarget) durationTarget.innerText = recTimerSeconds + 's';
                    answersData[currentQIdx].voice_duration = recTimerSeconds;
                    
                    const wordCount = String(answersData[currentQIdx]?.speech_transcript || '').trim().split(/\s+/).filter(w=>w.length>0).length;
                    
                    // Match the server report: speech-transcript words divided
                    // by the browser-timed recording duration.
                    const timedSeconds = Math.max(1, recTimerSeconds);
                    const wpm = Math.round((wordCount / timedSeconds) * 60);
                    
                    const wpmTarget = document.getElementById('vaWpm');
                    if (wpmTarget) wpmTarget.innerText = wpm;
                    answersData[currentQIdx].wpm = wpm;
                    if (recTimerSeconds % 2 === 0) {
                        triggerAnalysis();
                    }

                    // Optional body-language detection is descriptive and never affects readiness scoring.
                    if (cameraDetectionEnabled && recTimerSeconds % 2 === 0) {
                        trackBodyLanguageDetection();
                    }

                }, 1000);

                const scannerBox = document.getElementById('faceScannerBox');
                if (scannerBox) scannerBox.style.display = 'block';
                return true;
            }

            function toggleRecordingPause() {
                if (!isVoiceTranscriptionMode()) {
                    showSessionNotice('Voice recording is disabled in Text Mode.');
                    return;
                }

                if (isRecording) {
                    pauseRecording();
                    return;
                }

                startRecording({ silent: false });
            }

            async function pauseRecording() {
                finalizeInterimTranscript();
                shouldAutoRestartRecognition = false;

                const usedServerTranscription = activeTranscriptionEngine === 'server';
                if(recognition && !usedServerTranscription) {
                    try {
                        recognition.stop();
                    } catch (error) {
                        console.error('Speech recognition failed to stop:', error);
                    }
                }
                isRecording = false;
                isRecordingPaused = true;
                clearInterval(recTimerInterval);
                setRecordingControlButtons('paused');
                const scannerBox = document.getElementById('faceScannerBox');
                if (scannerBox) scannerBox.style.display = 'none';

                if (usedServerTranscription) {
                    setTranscriptionStatus('Finalizing transcription', '#fbbf24');
                    await stopServerTranscriptionEngine();
                }

                if (!interviewEnding && !interviewTerminated) {
                    setTranscriptionStatus('Paused');
                }
            }

            async function stopRecording() {
                await pauseRecording();
                clearTimeout(autoStartAfterQuestionTimer);
                isRecordingPaused = false;
                recTimerSeconds = 0;
                const timer = document.getElementById('recordingTimer');
                if (timer) timer.innerText = '00:00';
                setRecordingControlButtons('recording');
                resetSpeechRecognitionBufferFromTextarea();
                setTranscriptionStatus('');
                return true;
            }

            async function finalizeCurrentTranscriptionForSubmit() {
                finalizeInterimTranscript();

                if (isRecording) {
                    await stopRecording();
                    return;
                }

                if (
                    activeTranscriptionEngine === 'server'
                    && (
                        serverTranscriptionQueue.length > 0
                        || serverTranscriptionActiveRequests > 0
                        || serverTranscriptionResults.size > 0
                    )
                ) {
                    setTranscriptionStatus('Finalizing transcription', '#fbbf24');
                    await waitForServerTranscriptionDrain(serverTranscriptionDrainTimeoutMs);
                    commitReadyServerTranscriptionResults();
                }
            }

            function saveCurrentAnswer(isSkipped = false, timedOut = false) {
                if (interviewTerminated) return Promise.reject(new Error('Interview session has been terminated.'));
                stopQuestionTimer();
                captureTranscriptTimeline(timedOut ? 'timed_out_submit' : 'submitted', true);
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);
                formData.append('question_id', questions[currentQIdx].id);
                formData.append('answer_text', answersData[currentQIdx].text);
                formData.append('speech_transcript', answersData[currentQIdx].speech_transcript || '');
                formData.append('transcript_timeline', JSON.stringify(answersData[currentQIdx].transcript_timeline || []));
                formData.append('observation_data', JSON.stringify(observationDataForSubmit(answersData[currentQIdx])));
                formData.append('pronunciation_analysis', JSON.stringify(answersData[currentQIdx].pronunciation_analysis || {}));
                formData.append('paste_event_count', answersData[currentQIdx].paste_event_count || 0);
                formData.append('pasted_character_count', answersData[currentQIdx].pasted_character_count || 0);
                formData.append('is_skipped', isSkipped);
                formData.append('timed_out', timedOut);
                formData.append('elapsed_seconds', answersData[currentQIdx].elapsed_seconds || getQuestionElapsedSeconds());
                formData.append('response_mode', canonicalResponseMode);
                formData.append('wpm', answersData[currentQIdx].wpm);
                formData.append('voice_duration', answersData[currentQIdx].voice_duration);
                formData.append('filler_words_count', answersData[currentQIdx].filler_words);
                formData.append('pause_count', answersData[currentQIdx].pause_count);
                const realtimeConfidence = syncSelfConfidenceControl(answersData[currentQIdx].confidence_score ?? 0);
                formData.append('confidence_score', realtimeConfidence);
                formData.append('self_reported_confidence', realtimeConfidence);
                formData.append('eye_contact_score', answersData[currentQIdx].eye_contact_score);
                formData.append('posture_score', answersData[currentQIdx].posture_score);
                formData.append('notes', '');

                return postFormJsonWithRetry('{{ route("interview.answer") }}', formData, {
                    attempts: 3,
                    fallbackMessage: 'We could not save your answer. Please try again.'
                });
            }

            function autoSaveState() {
                if (interviewEnding || interviewTerminated) return Promise.resolve();
                if (answersData[currentQIdx]) {
                    if (!isSubmittingAnswer) {
                        answersData[currentQIdx].text = document.getElementById('answerTextarea').value;
                    }
                    answersData[currentQIdx].elapsed_seconds = getQuestionElapsedSeconds();
                }

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('notes', '');
                formData.append('duration_seconds', timerSeconds);
                formData.append('current_question_index', currentQIdx);
                const answersForAutosave = answersData.map((answer, index) => {
                    const snapshot = Object.assign({}, answer);
                    snapshot.observation_data = index === currentQIdx
                        ? observationDataForSubmit(answer, 100, 120)
                        : { filler_events: [], camera_samples: [] };
                    return snapshot;
                });
                formData.append('session_state', JSON.stringify({
                    has_started: true,
                    currentQIdx,
                    timerSeconds,
                    openingHasPlayed,
                    questions: questionSnapshot(),
                    answersData: answersForAutosave,
                    chatHistory: interviewChatHistory,
                    updated_at: new Date().toISOString()
                }));
                
                return managedFetch('{{ route("interview.saveState") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(() => {
                    if (interviewEnding || interviewTerminated) return;
                    const ind = document.getElementById('autoSaveIndicator');
                    ind.style.display = 'inline';
                    setTimeout(() => ind.style.display = 'none', 2000);
                }).catch(error => {
                    if (error.name !== 'AbortError') {
                        console.warn('Interview state auto-save failed:', error);
                    }
                });
            }

            function appendChatMessage(role, text, record = true) {
                const chatContainer = document.getElementById('chatTranscriptContainer');
                if (role === 'interviewer') {
                    chatContainer.innerHTML = '';
                }
                chatContainer.style.display = 'flex';

                const bubble = document.createElement('div');
                bubble.style.padding = '8px 10px';
                bubble.style.borderRadius = '12px';
                bubble.style.width = '100%';
                bubble.style.maxWidth = '100%';
                bubble.style.boxSizing = 'border-box';
                bubble.style.lineHeight = '1.35';
                bubble.style.fontSize = '0.76rem';
                
                if (role === 'interviewer') {
                    bubble.style.background = 'rgba(139,92,246,0.15)';
                    bubble.style.border = '1px solid rgba(139,92,246,0.3)';
                    bubble.style.alignSelf = 'flex-start';
                    bubble.innerHTML = '<strong><i class="fa-solid fa-robot me-1"></i> Interviewer</strong><br>' + escapeHtml(text);
                } else {
                    bubble.style.background = 'rgba(59,130,246,0.15)';
                    bubble.style.border = '1px solid rgba(59,130,246,0.3)';
                    bubble.style.alignSelf = 'flex-end';
                    bubble.innerHTML = '<strong><i class="fa-solid fa-user me-1"></i> You</strong><br>' + escapeHtml(text);
                }
                
                chatContainer.appendChild(bubble);

                if (record) {
                    interviewChatHistory.push({ role, text });
                    if (interviewChatHistory.length > 80) interviewChatHistory = interviewChatHistory.slice(interviewChatHistory.length - 80);
                    scheduleStateSave();
                }
            }

            function placeNextQuestion(question) {
                const nextQuestionIndex = currentQIdx + 1;
                if (nextQuestionIndex < questions.length) {
                    questions[nextQuestionIndex] = question;
                } else {
                    questions.push(question);
                }

                while (answersData.length < questions.length) {
                    answersData.push(defaultAnswerState());
                }

                return nextQuestionIndex;
            }

            async function submitAnswer(options = {}) {
                if (isSubmittingAnswer || interviewEnding || interviewTerminated || finalAnswerSubmitted) return;
                isSubmittingAnswer = true;
                try {
                    await finalizeCurrentTranscriptionForSubmit();
                } catch (error) {
                    console.warn('Final transcription flush before submit failed:', error);
                }
                
                const timedOut = options.timedOut === true;
                let answerText = document.getElementById('answerTextarea').value.trim();
                const wasSkipped = options.skipped === true || (timedOut && !answerText);

                if(!answerText && !timedOut && !wasSkipped) {
                    isSubmittingAnswer = false;
                    showSessionNotice('Please provide an answer before submitting.');
                    document.getElementById('answerTextarea')?.focus();
                    return;
                }
                if(!answerText && timedOut) {
                    answerText = "[Time expired with no answer]";
                    document.getElementById('answerTextarea').value = answerText;
                }

                setAnswerInputEnabled(false);

                answersData[currentQIdx].text = answerText;
                answersData[currentQIdx].is_skipped = wasSkipped;
                answersData[currentQIdx].timed_out = timedOut;

                appendChatMessage('user', answerText);
                clearSubmittedAnswerInput();

                const answeredOpeningQuestion = isOpeningQuestion(questions[currentQIdx]);
                const isLastQuestion = !answeredOpeningQuestion && isLastScoredQuestion(currentQIdx);

                if (isLastQuestion) {
                    finalAnswerSubmitted = true;
                    try {
                        await saveCurrentAnswer(wasSkipped, timedOut);
                        isSubmittingAnswer = false;
                        await concludeAndFinishInterview();
                    } catch(error) {
                        console.error(error);
                        finalAnswerSubmitted = false;
                        isSubmittingAnswer = false;
                        if (!interviewTerminated) {
                            restoreSubmittedAnswerInput(answerText);
                            setAnswerInputEnabled(true);
                            showSessionNotice('We could not save your final answer. Please try again before finishing.');
                        }
                    }
                    return;
                }

                stopQuestionTimer();
                captureTranscriptTimeline(timedOut ? 'timed_out_submit' : 'submitted', true);
                
                const chatContainer = document.getElementById('chatTranscriptContainer');
                chatContainer.style.display = 'flex';
                const thinkingBubble = document.createElement('div');
                thinkingBubble.id = 'thinkingBubble';
                thinkingBubble.style.padding = '8px 10px';
                thinkingBubble.style.borderRadius = '12px';
                thinkingBubble.style.maxWidth = '96%';
                thinkingBubble.style.fontSize = '0.76rem';
                thinkingBubble.style.lineHeight = '1.35';
                thinkingBubble.style.alignSelf = 'flex-start';
                thinkingBubble.style.background = 'rgba(255,255,255,0.05)';
                thinkingBubble.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-muted me-2"></i> <em>Interviewer is preparing the next question...</em>';
                chatContainer.appendChild(thinkingBubble);
                
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);
                formData.append('question_id', questions[currentQIdx].id);
                formData.append('answer_text', answerText);
                formData.append('speech_transcript', answersData[currentQIdx].speech_transcript || '');
                formData.append('conversation_context', JSON.stringify(interviewChatHistory.slice(-16)));
                formData.append('transcript_timeline', JSON.stringify(answersData[currentQIdx].transcript_timeline || []));
                formData.append('observation_data', JSON.stringify(observationDataForSubmit(answersData[currentQIdx])));
                formData.append('pronunciation_analysis', JSON.stringify(answersData[currentQIdx].pronunciation_analysis || {}));
                formData.append('paste_event_count', answersData[currentQIdx].paste_event_count || 0);
                formData.append('pasted_character_count', answersData[currentQIdx].pasted_character_count || 0);
                formData.append('is_skipped', wasSkipped);
                formData.append('timed_out', timedOut);
                formData.append('elapsed_seconds', answersData[currentQIdx].elapsed_seconds || getQuestionElapsedSeconds());
                formData.append('response_mode', canonicalResponseMode);
                formData.append('wpm', answersData[currentQIdx].wpm);
                formData.append('voice_duration', answersData[currentQIdx].voice_duration);
                formData.append('filler_words_count', answersData[currentQIdx].filler_words);
                formData.append('pause_count', answersData[currentQIdx].pause_count);
                const realtimeConfidence = syncSelfConfidenceControl(answersData[currentQIdx].confidence_score ?? 0);
                formData.append('confidence_score', realtimeConfidence);
                formData.append('self_reported_confidence', realtimeConfidence);
                formData.append('eye_contact_score', answersData[currentQIdx].eye_contact_score);
                formData.append('posture_score', answersData[currentQIdx].posture_score);
                formData.append('is_final_question', (!answeredOpeningQuestion && isPenultimateScoredQuestion(currentQIdx)));

                try {
                    const data = await postFormJsonWithRetry('{{ route("interview.chatReply") }}', formData, {
                        attempts: 2,
                        fallbackMessage: 'We could not send your answer. Please try again.'
                    });
                    const tb = document.getElementById('thinkingBubble');
                    if(tb) tb.remove();

                    if (!data.success) {
                        throw new Error(data.error || 'An error occurred.');
                    }

                    if (data.interview_completed) {
                        finalAnswerSubmitted = true;
                        isSubmittingAnswer = false;
                        await concludeAndFinishInterview();
                        return;
                    }

                    const newQ = {
                        id: data.next_question_id,
                        question_text: data.next_question_text,
                        source_name: data.source_name || '',
                        source_url: data.source_url || '',
                        source_type: data.source_type || ''
                    };
                    const nextQuestionIndex = placeNextQuestion(newQ);
                    currentQIdx = nextQuestionIndex;
                    isSubmittingAnswer = false;
                    await loadQuestion(currentQIdx);
                } catch(err) {
                    const tb = document.getElementById('thinkingBubble');
                    if(tb) tb.remove();
                    isSubmittingAnswer = false;
                    console.error(err);
                    if (!interviewTerminated && err.name !== 'AbortError') {
                        restoreSubmittedAnswerInput(answerText);
                        setAnswerInputEnabled(true);
                        showSessionNotice(err.message || 'Network error.');
                    }
                }
            }

            async function skipQuestion() {
                await finalizeCurrentTranscriptionForSubmit();
                document.getElementById('answerTextarea').value = "[User skipped the question]";
                submitAnswer({ skipped: true });
            }

            async function prevQuestion() {
                await finalizeCurrentTranscriptionForSubmit();
                if (currentQIdx > 0) {
                    loadQuestion(currentQIdx - 1);
                }
            }

            function cleanupInterviewProcesses(options = {}) {
                const abortFetches = options.abortFetches === true;
                clearTimeout(autoStartAfterQuestionTimer);
                clearTimeout(stateSaveDebounce);
                clearInterval(timerInterval);
                clearInterval(questionTimerInterval);
                clearInterval(recTimerInterval);
                questionTimerInterval = null;
                timerInterval = null;
                recTimerInterval = null;
                questionStartedAt = null;
                shouldAutoRestartRecognition = false;

                if (recognition) {
                    try {
                        recognition.abort ? recognition.abort() : recognition.stop();
                    } catch (error) {
                        console.warn('Speech recognition cleanup failed:', error);
                    }
                }
                recognitionActive = false;
                if (serverTranscriptionRecorder && serverTranscriptionRecorder.state !== 'inactive') {
                    try {
                        serverTranscriptionRecorder.requestData();
                        serverTranscriptionRecorder.stop();
                    } catch (error) {
                        console.warn('Server transcription cleanup failed:', error);
                    }
                }
                serverTranscriptionRecorder = null;
                serverTranscriptionSessionToken++;
                serverTranscriptionQueue = [];
                serverTranscriptionActiveRequests = 0;
                serverTranscriptionResults = new Map();
                serverTranscriptionNextSequence = 0;
                serverTranscriptionNextCommitSequence = 0;
                serverTranscriptionProcessing = false;
                resolveServerTranscriptionDrain();
                isRecording = false;
                isRecordingPaused = false;
                releaseMicrophoneStream();
                setTranscriptionStatus('');
                cancelQuestionSpeechOutput();
                serverSpeechUrlCache.forEach(url => URL.revokeObjectURL(url));
                serverSpeechUrlCache.clear();
                ['userCamera', 'userCameraMobile'].forEach(id => {
                    let video = document.getElementById(id);
                    if (video && video.srcObject) {
                        video.srcObject.getTracks().forEach(track => track.stop());
                        video.srcObject = null;
                    }
                });

                if (abortFetches) {
                    abortManagedFetches();
                }
            }

            function updateEndSessionDraftPreview() {
                const preview = document.getElementById('endSessionDraftPreview');
                const previewText = preview?.querySelector('p');
                if (!preview || !previewText) return;

                const answerText = String(document.getElementById('answerTextarea')?.value || '').trim();
                if (!answerText) {
                    preview.hidden = true;
                    previewText.textContent = '';
                    return;
                }

                previewText.textContent = answerText.length > 280 ? `${answerText.slice(0, 280)}...` : answerText;
                preview.hidden = false;
            }

            function appendCurrentAnswerForEndedSession(formData) {
                const question = questions[currentQIdx];
                const answerState = answersData[currentQIdx];
                if (!question || !answerState) return;

                const answerText = String(document.getElementById('answerTextarea')?.value || answerState.text || '').trim();
                if (!answerText) return;

                answerState.text = answerText;
                answerState.elapsed_seconds = getQuestionElapsedSeconds();
                captureTranscriptTimeline('ended_session', true);

                formData.append('question_id', question.id);
                formData.append('answer_text', answerText);
                formData.append('speech_transcript', answerState.speech_transcript || '');
                formData.append('transcript_timeline', JSON.stringify(answerState.transcript_timeline || []));
                formData.append('observation_data', JSON.stringify(observationDataForSubmit(answerState)));
                formData.append('pronunciation_analysis', JSON.stringify(answerState.pronunciation_analysis || {}));
                formData.append('paste_event_count', answerState.paste_event_count || 0);
                formData.append('pasted_character_count', answerState.pasted_character_count || 0);
                formData.append('is_skipped', false);
                formData.append('timed_out', false);
                formData.append('elapsed_seconds', answerState.elapsed_seconds || getQuestionElapsedSeconds());
                formData.append('response_mode', canonicalResponseMode);
                formData.append('wpm', answerState.wpm || 0);
                formData.append('voice_duration', answerState.voice_duration || 0);
                formData.append('filler_words_count', answerState.filler_words || 0);
                formData.append('pause_count', answerState.pause_count || 0);
                const realtimeConfidence = syncSelfConfidenceControl(answerState.confidence_score ?? 0);
                formData.append('confidence_score', realtimeConfidence);
                formData.append('self_reported_confidence', realtimeConfidence);
                formData.append('eye_contact_score', answerState.eye_contact_score || 0);
                formData.append('posture_score', answerState.posture_score || 0);
            }

            async function abortInterviewSession() {
                if (interviewTerminated) return;
                interviewEnding = true;
                finalAnswerSubmitted = true;
                setAnswerInputEnabled(false);

                try {
                    await finalizeCurrentTranscriptionForSubmit();
                } catch (error) {
                    console.warn('Final transcription flush before ending failed:', error);
                }

                interviewTerminated = true;
                cleanupInterviewProcesses({ abortFetches: true });

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);
                formData.append('duration_seconds', timerSeconds);
                formData.append('current_question_index', currentQIdx);
                appendCurrentAnswerForEndedSession(formData);

                try {
                    const response = await fetch('{{ route("interview.abort") }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = response.ok ? await response.json() : {};
                    await exitAutoInterviewFullscreen();
                    window.location.href = data.redirect_url || '{{ route("interview.setup") }}';
                } catch (error) {
                    console.error('Interview abort failed:', error);
                    await exitAutoInterviewFullscreen();
                    window.location.href = '{{ route("interview.setup") }}';
                }
            }

            function activeInterviewModal() {
                return document.querySelector('#interviewStartModal.active, #endSessionModal.active');
            }

            function syncInterviewModalBodyState() {
                document.body.classList.toggle('interview-start-modal-active', Boolean(activeInterviewModal()));
            }

            function focusFirstModalAction(modal, selector) {
                if (!modal) return;
                setTimeout(() => {
                    const target = modal.querySelector(selector) || modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                    target?.focus();
                }, 50);
            }

            function trapModalFocus(event, modal) {
                const focusable = Array.from(modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'))
                    .filter(element => !element.disabled && element.offsetParent !== null);
                if (!focusable.length) return;

                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }

            function requestAbortInterviewSession() {
                if (interviewTerminated || interviewEnding) return;
                const modal = document.getElementById('endSessionModal');
                if (!modal) return;
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
                updateEndSessionDraftPreview();
                modal.classList.add('active');
                syncInterviewModalBodyState();
                focusFirstModalAction(modal, '.interview-start-button.cancel');
            }

            function cancelAbortInterviewSession() {
                const modal = document.getElementById('endSessionModal');
                modal?.classList.remove('active');
                syncInterviewModalBodyState();
            }

            function confirmAbortInterviewSession() {
                cancelAbortInterviewSession();
                abortInterviewSession();
            }

            function waitForFeedbackRetry(delayMs) {
                return new Promise(resolve => setTimeout(resolve, Math.max(250, Math.min(2500, delayMs || 1000))));
            }

            function setFinishTransitionVisible(visible) {
                const overlay = document.getElementById('finishTransitionOverlay');
                if (!overlay) return;
                if (visible && overlay.parentElement !== document.body) {
                    document.body.appendChild(overlay);
                }
                if (!visible) {
                    overlay.classList.remove('finish-transition-error');
                }
                overlay.classList.toggle('active', visible);
                document.body.classList.toggle('finish-transition-active', visible);
            }

            async function finishInterview() {
                if (feedbackSubmissionInFlight || interviewTerminated) return false;
                feedbackSubmissionInFlight = true;
                cleanupInterviewProcesses();
                stopQuestionTimer();
                document.getElementById('formDuration').value = timerSeconds;
                document.getElementById('formNotes').value = '';
                const transitionMessage = document.getElementById('finishTransitionMessage');
                const transitionTitle = document.getElementById('finishTransitionTitle');
                const failureAlert = document.getElementById('finishFailureAlert');
                const retryButton = document.getElementById('finishRetryButton');
                const backButton = document.getElementById('finishBackButton');
                const overlay = document.getElementById('finishTransitionOverlay');
                overlay?.classList.remove('finish-transition-error');
                if (transitionTitle) transitionTitle.textContent = 'Analyzing your response...';
                if (transitionMessage) transitionMessage.textContent = 'Please wait while we finalize your interview report.';
                if (failureAlert) {
                    failureAlert.textContent = '';
                    failureAlert.hidden = true;
                }
                overlay?.setAttribute('role', 'status');
                overlay?.setAttribute('aria-live', 'polite');
                if (retryButton) retryButton.style.display = 'none';
                if (backButton) backButton.style.display = 'none';
                setFinishTransitionVisible(true);
                const form = document.getElementById('finishForm');
                const formData = new FormData(form);

                try {
                    for (let attempt = 0; attempt < 4; attempt++) {
                        const response = await managedFetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const payload = await parseResponsePayload(response);
                        const data = payload.data || {};

                        if (response.status === 409) {
                            await waitForFeedbackRetry(data.retry_after_ms);
                            continue;
                        }

                        if (!response.ok || !data.redirect_url) {
                            const error = new Error(finishResponseErrorMessage(response, payload));
                            error.status = response.status;
                            throw error;
                        }

                        await exitAutoInterviewFullscreen();
                        window.location.replace(data.redirect_url);
                        return true;
                    }

                    throw new Error('The report is still processing. Please retry in a moment.');
                } catch (error) {
                    console.error('Interview feedback analysis failed:', error);
                    feedbackSubmissionInFlight = false;
                    const alertMessage = error.message || 'We could not finish the report. Your final answer is saved.';
                    const title = document.getElementById('finishTransitionTitle');
                    const message = document.getElementById('finishTransitionMessage');
                    const failureAlert = document.getElementById('finishFailureAlert');
                    const retryButton = document.getElementById('finishRetryButton');
                    const backButton = document.getElementById('finishBackButton');
                    const overlay = document.getElementById('finishTransitionOverlay');
                    overlay?.classList.add('finish-transition-error');
                    if (title) title.textContent = 'Report not finished';
                    if (message) message.textContent = 'Your answers are saved. Retry the report or return to your answer.';
                    if (failureAlert) {
                        failureAlert.textContent = alertMessage;
                        failureAlert.hidden = false;
                    }
                    overlay?.setAttribute('role', 'alert');
                    overlay?.setAttribute('aria-live', 'assertive');
                    if (retryButton) retryButton.style.display = 'inline-flex';
                    if (backButton) backButton.style.display = 'inline-flex';
                    setFinishTransitionVisible(true);
                    showSessionNotice(alertMessage, 'error', true);
                    return false;
                }
            }

            function finishResponseErrorMessage(response, payload) {
                const data = payload?.data || {};
                if (data.error_code === 'ai_feedback_providers_failed') {
                    return 'AI feedback providers are unavailable right now. Your answers are saved.';
                }
                const explicitMessage = data.error || data.message || validationErrorMessage(data.errors);
                if (explicitMessage) return String(explicitMessage);

                if (response.status === 419) {
                    return 'Your secure session expired. Please refresh the page, then retry report generation.';
                }
                if (response.status === 403) {
                    return 'This interview session is no longer active for your account.';
                }
                if (response.status === 422) {
                    return 'Some report details were rejected. Please retry the feedback report.';
                }
                if (response.status >= 500) {
                    return 'The server had a temporary problem while finalizing your feedback report. Please retry in a moment.';
                }

                const plainText = String(payload?.text || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                return plainText ? plainText.slice(0, 220) : 'The feedback service returned an incomplete response.';
            }

            function retryFinishInterview() {
                const backButton = document.getElementById('finishBackButton');
                if (backButton) backButton.style.display = 'none';
                if (!feedbackSubmissionInFlight) finishInterview();
            }

            function returnToInterviewAfterFinishError() {
                if (feedbackSubmissionInFlight) return;
                interviewEnding = false;
                finalAnswerSubmitted = false;
                setFinishTransitionVisible(false);
                setAnswerInputEnabled(true);
                showSessionNotice('Report generation paused. Your final answer is still on screen.', 'warning');
                document.getElementById('answerTextarea')?.focus();
            }

            function setInterviewStartModalVisible(visible) {
                const modal = document.getElementById('interviewStartModal');
                if (!modal) return;
                if (visible && modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
                document.body.classList.toggle('interview-ready-fullscreen', visible);
                modal.classList.toggle('active', visible);
                syncInterviewModalBodyState();
                refreshInterviewFullscreenLayout();

                if (visible) {
                    focusFirstModalAction(modal, '#confirmInterviewStartButton');
                }
            }

            function exitReadyFullscreenShell() {
                document.body.classList.remove('interview-ready-fullscreen');
                if (document.fullscreenElement && document.exitFullscreen) {
                    return document.exitFullscreen().catch(() => {}).finally(() => {
                        updateMobileFullscreenToggle();
                        refreshInterviewFullscreenLayout();
                    });
                }

                updateMobileFullscreenToggle();
                refreshInterviewFullscreenLayout();
                return Promise.resolve();
            }

            function confirmInterviewStart() {
                setInterviewStartModalVisible(false);
                startInterviewSession();
            }

            function cancelInterviewStart() {
                window.location.href = '{{ route("interview.setup") }}';
            }

            function ucfirst(str) {
                if(!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            }

            function handleInterviewEscapeKey(event) {
                if (event.key !== 'Escape') return false;

                const modal = activeInterviewModal();
                const readyFullscreenActive = document.body.classList.contains('interview-ready-fullscreen');
                const sessionFullscreenActive = document.body.classList.contains('mobile-interview-fullscreen')
                    || document.body.classList.contains('interview-session-browser-fullscreen')
                    || Boolean(document.fullscreenElement);

                if (modal?.id === 'endSessionModal') {
                    event.preventDefault();
                    cancelAbortInterviewSession();
                    return true;
                }

                if (modal?.id === 'interviewStartModal' && readyFullscreenActive) {
                    event.preventDefault();
                    exitReadyFullscreenShell();
                    return true;
                }

                if (!modal && sessionFullscreenActive) {
                    event.preventDefault();
                    exitInterviewFullscreen();
                    return true;
                }

                if (modal?.id === 'interviewStartModal') {
                    event.preventDefault();
                    cancelInterviewStart();
                    return true;
                }

                return false;
            }

            document.addEventListener('DOMContentLoaded', () => {
                updateMobileFullscreenToggle();
                document.addEventListener('fullscreenchange', handleBrowserFullscreenChange);
                document.addEventListener('keydown', event => {
                    if (handleInterviewEscapeKey(event)) return;

                    const modal = activeInterviewModal();
                    if (!modal) return;
                    if (event.key === 'Tab') {
                        trapModalFocus(event, modal);
                    }
                });
                setInterviewStartModalVisible(!interviewStarted && !interviewTerminated);
            });
        </script>
        @else
        <div class="panel interview-empty-panel">
            <h5><i class="fa-solid fa-circle-exclamation me-2"></i>No questions found</h5>
            <p style="color:var(--tx3)">No questions are available for this setup yet.</p>
            <a href="{{ route('interview.setup') }}" class="interview-start-button begin">
                Back to setup <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        @endif
    @endif
</div>

@if(isset($sessionRecord) && (bool) data_get($sessionRecord->accommodation_profile, 'camera_detection', data_get($sessionRecord->accommodation_profile, 'camera_coaching', false)))
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    if (typeof cameraDetectionEnabled !== 'undefined' && cameraDetectionEnabled) {
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/'),
            faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/')
        ]).then(() => {
            console.log("Optional face-framing models loaded");
        }).catch(err => {
            window.faceFramingModelUnavailable = true;
            console.error("Error loading optional face-framing models", err);
        });
    }
</script>
<script type="module">
    if (typeof cameraDetectionEnabled !== 'undefined' && cameraDetectionEnabled) {
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
                const detectionStatus = document.getElementById('cameraDetectionStatus');
                if (detectionStatus) {
                    detectionStatus.innerHTML = '<i class="fa-solid fa-person-rays me-1"></i>Pose + hand model ready';
                    detectionStatus.style.color = '#34d399';
                }
                console.log("Optional body-language models loaded");
            })
            .catch(err => {
                modelState.ready = false;
                modelState.failed = true;
                console.error("Error loading optional body-language models", err);
                const detectionStatus = document.getElementById('cameraDetectionStatus');
                if (detectionStatus) {
                    detectionStatus.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Framing only';
                    detectionStatus.style.color = '#fbbf24';
                }
                if (window.faceFramingModelUnavailable && typeof markCameraUnavailable === 'function') {
                    markCameraUnavailable('model_unavailable');
                }
            });
    }
</script>
@endif

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '.ai-avatar-panel', popover: { title: 'AI Interviewer', description: 'The interviewer presents each question and guides the session flow.', side: 'bottom', align: 'start' }},
            { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here while live metrics update.', side: 'top', align: 'start' }},
            { element: '#cameraPanel', popover: { title: 'Body-Language Detection', description: 'Camera detection checks visible framing, head, posture, hands, and movement. Camera observations never affect readiness scoring.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '.ai-avatar-panel', popover: { title: 'AI Interviewer', description: 'The interviewer presents each question and guides the session flow.', side: 'right', align: 'start' }},
            { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here while live metrics update.', side: 'right', align: 'start' }},
            { element: '#cameraPanel', popover: { title: 'Body-Language Detection', description: 'Camera detection checks visible framing, head, posture, hands, and movement. Camera observations never affect readiness scoring.', side: 'left', align: 'start' }}
        ];

        const onboardingTour = window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_interview_session',
            serverDetectedMobile: false,
            stepsMobile: stepsMobile.filter(step => document.querySelector(step.element)),
            stepsDesktop: stepsDesktop.filter(step => document.querySelector(step.element)),
            autoStart: false,
        });
        
        // Expose startOnboardingTour to be called after interview starts
        const originalStartInterview = window.startInterviewSession;
        window.startInterviewSession = function() {
            if (typeof originalStartInterview === 'function') {
                originalStartInterview.apply(this, arguments);
            }

            const shouldAutoStartTour = !window.matchMedia('(max-width: 767px)').matches;
            if (shouldAutoStartTour && onboardingTour && !onboardingTour.isCompleted()) {
                setTimeout(() => {
                    onboardingTour.start();
                }, 1000);
            }
        };
    });
</script>
@endpush
@endsection
