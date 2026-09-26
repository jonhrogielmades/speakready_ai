@extends('mobile.layouts.app')
@section('title', 'Interview Workspace')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/interview/session.css?v=47') }}" data-page-style="interview-session">
@endpush

@section('content')
@include('mobile.partials.page-hero-styles')

<div class="db-section active" id="sec-interview-session">
 @if(session('active_interview_id'))
 @php
 $sessionRecord = \App\Models\InterviewSession::with('category')
 ->where('user_id', auth()->id())
 ->find(session('active_interview_id'));
 if ($sessionRecord) {
 $num = $sessionRecord->num_questions?? 5;
 $selectedQuestionTypes = json_decode($sessionRecord->question_types?? '[]', true);
 $selectedQuestionTypes = is_array($selectedQuestionTypes)? array_values(array_filter($selectedQuestionTypes)): [];
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
 $scenarioLabel = 'Job Interviews';
 } else {
 $questions = collect([]);
 }
 @endphp

 @if($sessionRecord && $questions->count() > 0)
 @php
 $cameraDetectionEnabled = (bool) data_get($sessionRecord->accommodation_profile, 'camera_detection', data_get($sessionRecord->accommodation_profile, 'camera_coaching', false));
 $showCameraPanel = false;
 $savedStateForUi = json_decode($sessionRecord->session_state?? '', true);
 $hasSavedInterviewState = is_array($savedStateForUi) &&!empty($savedStateForUi['has_started']);
 $initialQuestionCounter = $hasSavedInterviewState? 'Resume': 'Ready';
 $assistanceLevelKey = strtolower((string) ($sessionRecord->ai_assistance_level?? 'standard'));
 $assistanceLevelLabel = [
  'beginner' => 'Beginner Assistance',
  'standard' => 'Standard Assistance',
  'challenge' => 'Challenge Assistance',
  ][$assistanceLevelKey]?? 'Standard Assistance';
  $responseModeKey = strtolower((string) ($sessionRecord->response_mode?? 'text'));
  $isVoiceOnlyResponseMode = $responseModeKey === 'voice';
  @endphp
 <div id="workspaceWrapper" style="display:none;">
 <div class="row g-4" id="workspaceRow">
 <!-- Main Content Area -->
 <div class="{{ $showCameraPanel? 'col-lg-8': 'col-lg-12' }}">
 <!-- Progress Tracker Removed by User -->

 <!-- Interviewer Avatar Panel -->
 <div class="panel p-0 ai-avatar-panel animate-fade-up delay-100" style="overflow:hidden;border:1px solid var(--bd);background:#000;position:relative;height:280px;border-radius:24px;margin-bottom:24px;box-shadow:0 15px 40px rgba(0,0,0,0.15);">
 <div style="position:absolute; inset:0; background: radial-gradient(circle at top right, rgba(139,92,246,0.3), transparent 60%), radial-gradient(circle at bottom left, rgba(59,130,246,0.3), transparent 60%); z-index:1; pointer-events:none;"></div>
 @if($cameraDetectionEnabled)
 <!-- Mobile self-view for camera detection. -->
 <div class="mobile-camera-pip d-lg-none" aria-label="Camera preview">
 <video id="userCameraMobile" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);background:#222;"></video>
 <div class="mobile-camera-placeholder" aria-hidden="true"><i class="fa-solid fa-video"></i></div>
 </div>
 @endif
 <!-- Question Counter (Top Left) -->
 <div style="position:absolute; top:15px; left:15px; z-index:50;">
 <span class="badge bg-white text-dark shadow-sm" style="font-size:0.8rem;white-space:nowrap;padding: 6px 10px;" id="qCounter">{{ $initialQuestionCounter }}</span>
 </div>
 <span class="badge interviewer-panel-badge"><i class="fa-solid fa-bolt me-1"></i> interviewer</span>
 <div class="question-timer-anchor">
 <span class="session-chip" id="questionTimerChip"><i class="fa-regular fa-clock"></i><span id="perQuestionTimer">Self-paced</span></span>
 </div>
 @if(($sessionRecord->live_feedback_mode?? 'coaching') !== 'real_interview')
 <button type="button" id="aiCoachHeadButton" class="ai-coach-head-button coaching-only" onclick="toggleAiCoachPanel()" aria-label="Open AI Coach possible answer" aria-controls="aiCoachPanel" aria-expanded="false" title="AI Coach possible answer">
 <i class="fa-solid fa-head-side-brain" aria-hidden="true"></i>
 <span class="ai-coach-head-label">AI Coach</span>
 </button>
 @endif

 <div id="aiAvatarContainer" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);">
 <div class="avatar-wrapper" id="aiAvatarHead" style="width:110px;height:110px;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;--avatar-ring-color:#8b5cf6;">
 <!-- The Image Container (with border, glow, and clipping for the image itself) -->
 <div class="avatar-frame">
 <img src="{{ asset('img/ai_interviewer_avatar.png') }}" alt="AI Interviewer" style="width:100%;height:100%;object-fit:cover;">
 </div>
 </div>
 
 <!-- Circular Audio Spectrum Waveform -->
 <div class="circular-spectrum sound-wave">
 @for ($i = 0; $i < 36; $i++)
 @php 
 // Use a pseudo-random sequence so it looks dynamic but is consistent
 $animClass = 'sb'. (($i * 7) % 10 + 1); 
 $rot = $i * 10;
 @endphp
 <div class="spectrum-bar {{ $animClass }}" style="--bar-rotation: {{ $rot }}deg;"></div>
 @endfor
 </div>
 </div>
 <div class="question-caption-overlay" aria-live="polite" aria-atomic="true">
 <div id="questionCaptionText" class="question-caption-line" style="color:#ffffff;-webkit-text-fill-color:#ffffff;text-shadow:0 2px 6px rgba(0,0,0,0.92),0 0 10px rgba(0,0,0,0.65);"></div>
 </div>

 <!-- Interview Panel Quick Actions -->
 <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mb-4 animate-fade-up delay-150 interview-panel-actions" id="interviewControls" style="opacity: 0; pointer-events: none; transition: opacity 0.3s;">
 <!-- Left: Navigation / Secondary -->
 <div class="d-flex gap-2 w-100 flex-fill">
 <button type="button" class="btn btn-outline-info flex-fill session-action-btn session-repeat-btn" onclick="repeatQuestion()" style="border-radius:12px;" aria-label="Repeat question" title="Repeat question">Repeat</button>
 <button type="button" class="btn btn-outline-danger flex-fill session-action-btn session-end-btn" onclick="requestAbortInterviewSession()" style="border-radius:12px;" aria-label="End session" title="End session">End Session</button>
 </div>
 
 </div>

 <div id="answerTranscriptControls" class="answer-transcript-controls interview-panel-voice-actions" aria-label="Voice recording controls" hidden>
 <span id="recordingTimer" style="font-family:monospace;font-size:1.1rem;color:#f87171;display:block;margin-right:10px;font-weight:bold;">00:00</span>
 <div id="voiceControls" style="display:none; margin:0; padding:0; border:none; background:transparent;">
 <div class="d-flex gap-2">
 <button type="button" id="micPauseBtn" class="btn btn-warning voice-action-btn voice-start-pause-btn" onclick="toggleRecordingPause()" style="display:inline-flex; border-radius:12px;" aria-label="Pause recording" title="Pause recording"><i class="fa-solid fa-pause"></i><span class="voice-action-label">Pause</span></button>
 <button type="button" id="micStopBtn" class="btn btn-secondary voice-action-btn voice-stop-btn" onclick="stopRecording()" style="display:inline-flex; border-radius:12px;" aria-label="Stop recording" title="Stop recording"><i class="fa-solid fa-stop"></i><span class="voice-action-label">Stop</span></button>
 </div>
 </div>
 <span id="transcriptionStatus" class="transcription-status" aria-live="polite" aria-atomic="true"></span>
 </div>
 </div>

 <div id="aiQuestionText" class="visually-hidden" aria-hidden="true">Loading your first question...</div>
 <div id="sessionNotice" class="session-inline-alert" role="alert" aria-live="assertive" tabindex="-1" hidden></div>

 <!-- Answer Response System -->
 <div class="panel response-panel mb-4 animate-fade-up delay-200">
 <div class="panel-title">
 <i class="fa-solid fa-pen-nib me-2"></i>
 <span class="panel-title-text">Your Response</span>
 <div class="response-title-actions">
 @if($sessionRecord->game_level_id)
 <span class="badge" style="background:#ef4444; color:white;"><i class="fa-solid fa-gamepad me-1"></i> GAME MODE</span>
 @endif
 <button type="button" id="responseFullscreenToggle" class="response-fullscreen-toggle d-md-none" onclick="toggleMobileFullscreen()" title="Enter fullscreen" aria-label="Enter fullscreen">
 <i class="fa-solid fa-expand"></i>
 </button>
 <button type="button" class="next-btn-class send-answer-btn response-send-answer-btn btn-shine" onclick="submitAnswer()" disabled aria-disabled="true" title="Start the interview to answer">
 Send Answer <i class="fa-solid fa-paper-plane"></i>
 </button>
 </div>
 </div>
 
 <form id="answerForm">
 <!-- Voice controls are mounted inside the interview panel. -->

 <div id="chatTranscriptContainer" style="max-height: none; overflow: visible; padding: 0; margin-bottom: 12px; background: transparent; border: 0; display: none; flex-direction: column; gap: 10px;"></div>
 @unless($isVoiceOnlyResponseMode)
 <label for="answerTextarea" class="visually-hidden">Your interview answer</label>
 <div id="responseModeLockNotice" class="response-mode-lock-notice" hidden>
 <i class="fa-solid fa-lock" aria-hidden="true"></i>
 <span>Voice Mode is voice-only. Text transcription and typing are disabled; use Hybrid Mode for voice-to-text.</span>
 </div>
 <div class="answer-transcript-stage">
 <textarea id="answerTextarea" class="oinp mb-2" style="min-height:76px;font-size:.82rem" placeholder="Type your answer using your own local school, work, internship, or project evidence..." aria-describedby="sessionNotice responseModeLockNotice answerTranscriptionOverlay responseCountBar"></textarea>
 <div id="answerTranscriptionOverlay" class="answer-transcription-overlay" hidden role="status" aria-live="polite">
 <div class="answer-transcription-wave" aria-hidden="true">
 <span style="--wave-index:0"></span>
 <span style="--wave-index:1"></span>
 <span style="--wave-index:2"></span>
 <span style="--wave-index:3"></span>
 <span style="--wave-index:4"></span>
 <span style="--wave-index:5"></span>
 <span style="--wave-index:6"></span>
 </div>
 <div class="answer-transcription-message">Analyzing your transcription...</div>
 </div>
 <div class="response-count-bar" id="responseCountBar" aria-live="polite">
 <span id="wordCount">0 words</span> <span aria-hidden="true">-</span> <span id="charCount">0 characters</span>
 </div>
 </div>
 @endunless
 <div class="response-autosave-row">
 <span id="autoSaveIndicator" class="text-success" style="display:none;"><i class="fa-solid fa-check me-1"></i>Auto-saved</span>
 </div>

 @if($isVoiceOnlyResponseMode)
 <div id="voiceSessionPanel" class="voice-session-panel" hidden data-state="idle">
 <div class="voice-session-summary">
 <div class="voice-session-title">
 <i class="fa-solid fa-wave-square"></i>
 <span>Voice Session</span>
 </div>
 <div class="voice-session-actions">
 <span id="voiceSessionBadge" class="voice-session-badge">Ready</span>
 <div class="voice-session-menu">
 <button type="button" id="voiceSessionMenuButton" class="voice-session-menu-button" onclick="toggleVoiceSessionMenu(event)" aria-label="Voice session actions" aria-haspopup="true" aria-expanded="false" aria-controls="voiceSessionMenu" disabled>
 <i class="fa-solid fa-ellipsis-vertical"></i>
 </button>
 <div id="voiceSessionMenu" class="voice-session-menu-panel" role="menu" hidden>
 <button type="button" id="voiceSessionDownloadButton" class="voice-session-menu-item" onclick="downloadVoiceSessionRecording(event)" role="menuitem" disabled>
 <i class="fa-solid fa-download"></i>
 <span>Download</span>
 </button>
 <button type="button" id="voiceSessionClearButton" class="voice-session-menu-item danger" onclick="clearCurrentVoiceSession(event)" role="menuitem" disabled>
 <i class="fa-solid fa-trash-can"></i>
 <span>Clear voice session</span>
 </button>
 </div>
 </div>
 </div>
 </div>
 <div class="voice-session-controls">
 <audio id="voiceSessionPlayback" class="voice-session-playback audio-disabled" controls preload="metadata"></audio>
 </div>
 <div class="voice-session-footer">
 <span id="voiceSessionStatus">Ready</span>
 <span id="voiceSessionMeta"></span>
 </div>
 </div>
 @endif

 <div id="aiCoachPanel" class="ai-coach-answer-panel coaching-only" hidden data-state="idle">
 <div class="ai-coach-answer-header">
 <div class="ai-coach-answer-title">
 <i class="fa-solid fa-head-side-brain" aria-hidden="true"></i>
 <span>AI Coach</span>
 </div>
 <button type="button" class="ai-coach-close-button" onclick="closeAiCoachPanel()" aria-label="Close AI Coach" title="Close AI Coach">
 <i class="fa-solid fa-xmark" aria-hidden="true"></i>
 </button>
 </div>
 <div id="aiCoachStatus" class="ai-coach-answer-meta">Possible answer</div>
 <div id="aiCoachAnswerText" class="ai-coach-answer-text" aria-live="polite" aria-label="AI Coach possible answer" draggable="false" oncopy="return false" oncut="return false" onpaste="return false" oncontextmenu="return false" ondragstart="return false" onselectstart="return false"></div>
 </div>

 </form>
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
 <div class="stat-row"><span>Shoulders / posture pose</span><span id="stPose" class="text-secondary">Waiting</span></div>
 <div class="stat-row mb-0"><span>Movement steadiness</span><span id="stMovement" class="text-secondary">Waiting</span></div>
 <div class="small mt-2" style="color:var(--tx3)">This estimates visible framing, head alignment, shoulders, posture pose, and movement steadiness only. Video is analyzed in your browser; no images, video, or raw landmarks are stored. It does not infer confidence, honesty, personality, or employability, and it is excluded from readiness.</div>
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
 <h4 id="interviewStartTitle">Interview Ready</h4>
 <p id="interviewStartDescription">{{ $hasSavedInterviewState? 'Your saved interview is ready to resume.': 'Your customized interview session is ready to begin.' }}</p>
 <div class="interview-start-meta interview-meta-line">
 <span class="session-chip"><i class="fa-solid fa-flag"></i>{{ $scenarioLabel }}</span>
 <span class="session-chip"><i class="fa-solid fa-microphone"></i>{{ ['text' => 'Text', 'voice' => 'Voice', 'hybrid' => 'Hybrid', 'voice_and_text' => 'Hybrid'][strtolower((string) $sessionRecord->response_mode)]?? 'Text' }} Mode</span>
 <span class="session-chip"><i class="fa-solid fa-brain"></i>{{ ($sessionRecord->live_feedback_mode?? 'coaching') === 'real_interview'? 'Real Interview': 'Coaching On' }}</span>
 <span class="session-chip"><i class="fa-solid fa-sliders"></i>{{ $assistanceLevelLabel }}</span>
 <span class="session-chip"><i class="fa-solid fa-list-check"></i>{{ $num }} Questions</span>
 <span class="session-chip"><i class="fa-solid fa-video"></i>Camera {{ $cameraDetectionEnabled? 'ON': 'OFF' }}</span>
 </div>
 <div class="interview-start-actions">
 <button type="button" class="interview-start-button cancel" onclick="cancelInterviewStart()">
 <i class="fa-solid fa-xmark"></i> Cancel
 </button>
 <button type="button" id="confirmInterviewStartButton" class="interview-start-button begin" onclick="confirmInterviewStart()">
 {{ $hasSavedInterviewState? 'Resume Interview': 'Begin Interview' }} <i class="fa-solid fa-play"></i>
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

<div id="sessionAlertModal" class="interview-start-modal interview-alert-modal" role="alertdialog" aria-modal="true" aria-labelledby="sessionAlertTitle" aria-describedby="sessionAlertMessage">
<div class="interview-start-dialog">
<div id="sessionAlertIcon" class="interview-start-icon success" aria-hidden="true">
<i class="fa-solid fa-circle-check"></i>
</div>
<h4 id="sessionAlertTitle">Voice Session Cleared</h4>
<p id="sessionAlertMessage" class="interview-alert-message">Voice session cleared.</p>
<div class="interview-start-actions">
<button type="button" id="sessionAlertOkButton" class="interview-start-button success" onclick="closeSessionAlertModal()">
OK <i class="fa-solid fa-check"></i>
</button>
</div>
</div>
</div>

@php
$clientQuestionsForUi = $questions->values()->map(fn ($question) => [
 'id' => (int) $question->id,
 'question_text' => (string) $question->question_text,
 'source_type' => $question->source_type,
 ])->all();
 $clientSavedStateForUi = is_array($savedStateForUi?? null)? $savedStateForUi: [];
 if (isset($clientSavedStateForUi['questions']) && is_array($clientSavedStateForUi['questions'])) {
 $clientSavedStateForUi['questions'] = collect($clientSavedStateForUi['questions'])
 ->map(fn ($question) => [
 'id' => (int) ($question['id']?? 0),
 'question_text' => (string) ($question['question_text']?? ''),
 'source_type' => $question['source_type']?? null,
 ])
 ->filter(fn ($question) => $question['id'] > 0 && $question['question_text']!== '')
 ->values()
 ->all();
 }
 @endphp
 <script>
 const savedSessionState = @json($clientSavedStateForUi);
 const initialQuestions = @json($clientQuestionsForUi);
 const savedQuestionSequence = Array.isArray(savedSessionState.questions)? savedSessionState.questions.filter(question => question && question.id && question.question_text): [];
 let questions = savedQuestionSequence.length > 0? savedQuestionSequence: initialQuestions;
 const interviewSessionId = {{ (int) $sessionRecord->id }};
 const targetQuestionCount = Math.max({{ $num }}, 1);
 const sessionTargetPosition = @json($sessionRecord->target_position?? 'this role');
 const sessionScenarioLabel = @json($scenarioLabel?? 'Interview');
 const sessionDifficultyLabel = @json(ucfirst((string) ($sessionRecord->difficulty?? 'medium')));
 const responseMode = @json($sessionRecord->response_mode?? 'voice');
 const canonicalResponseMode = (() => {
 const mode = String(responseMode || 'text').toLowerCase().trim();
 if (mode === 'voice_and_text') return 'hybrid';
 return ['text', 'voice', 'hybrid'].includes(mode)? mode: 'text';
 })();
 const perQuestionLimitSeconds = {{ (int) (($sessionRecord->time_limit?? 0) * 60) }};
 const assistanceLevel = @json($sessionRecord->ai_assistance_level?? 'standard');
 const liveFeedbackMode = @json($sessionRecord->live_feedback_mode?? 'coaching');
 const cameraDetectionEnabled = @json($cameraDetectionEnabled);
 const cameraPreviewEnabled = cameraDetectionEnabled;
 let cameraUnavailableReason = null;
 @php
 $serverAiVoiceEnabledForUi = \App\Services\AIService::speechSynthesisAvailable();
 @endphp
 const serverAiVoiceEnabled = @json($serverAiVoiceEnabledForUi);
 let currentQIdx = Number(savedSessionState.currentQIdx?? {{ (int) ($sessionRecord->current_question_index?? 0) }}) || 0;
 currentQIdx = Math.max(0, Math.min(currentQIdx, Math.max(0, questions.length - 1)));
 let timerSeconds = Number(savedSessionState.timerSeconds?? {{ (int) ($sessionRecord->duration_seconds?? 0) }}) || 0;
 let timerInterval;
 let questionTimerInterval = null;
 let questionStartedAt = null;
 let questionElapsedSeconds = 0;
 let lastTimelineCaptureAt = 0;
 let interviewChatHistory = Array.isArray(savedSessionState.chatHistory)? savedSessionState.chatHistory: [];
 let stateSaveDebounce = null;
 let interviewEnding = false;
 let interviewTerminated = false;
 let interviewStarted = false;
 let answerListenersBound = false;
 let isSubmittingAnswer = false;
 let finalAnswerSubmitted = false;
 let answerInputEnabled = false;
 let feedbackSubmissionInFlight = false;
 const pendingFetchControllers = new Set();
 const displayedQuestionIds = new Set();
 let currentRepeatPrompt = '';
 let currentRepeatOptions = {};
 let sessionNoticeTimer = null;
 let aiCoachRequestInFlight = false;
 let aiCoachCurrentAnswer = '';
 let aiCoachCurrentQuestionId = null;
 
 // Answers state
 function defaultVoiceRecordingState() {
 return {
 available: false,
 local_only: true,
 mime_type: '',
 byte_size: 0,
 duration_seconds: 0,
 filename: '',
 transcribed: false,
 transcription_status: '',
 transcription_source: '',
 transcribed_at: ''
 };
 }

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
 camera_unavailable_reason: cameraDetectionEnabled? cameraUnavailableReason: null
 },
 pronunciation_analysis: null,
 voice_recording: defaultVoiceRecordingState()
 };
 }

 function normalizeVoiceRecordingAnswerState(answerState) {
 const state = answerState && typeof answerState === 'object'? answerState: defaultAnswerState();
 const meta = state.voice_recording && typeof state.voice_recording === 'object'? state.voice_recording: {};
 state.voice_recording = {...defaultVoiceRecordingState(),...meta,
 available: Boolean(meta.available),
 local_only: true,
 byte_size: Math.max(0, Math.round(Number(meta.byte_size || 0))),
 duration_seconds: Math.max(0, Math.round(Number(meta.duration_seconds || 0))),
 mime_type: String(meta.mime_type || ''),
 filename: String(meta.filename || ''),
 transcribed: Boolean(meta.transcribed),
 transcription_status: String(meta.transcription_status || ''),
 transcription_source: String(meta.transcription_source || ''),
 transcribed_at: String(meta.transcribed_at || '')
 };

 return state;
 }

 function normalizeCameraDetectionObservationState(answerState) {
 const state = answerState && typeof answerState === 'object'? answerState: defaultAnswerState();
 const observations = state.observation_data && typeof state.observation_data === 'object'? state.observation_data: {};

 state.observation_data = {...observations,
 filler_events: Array.isArray(observations.filler_events)? observations.filler_events: [],
 camera_samples: cameraDetectionEnabled && Array.isArray(observations.camera_samples)? observations.camera_samples: [],
 camera_detection_enabled: cameraDetectionEnabled,
 camera_unavailable_reason: cameraDetectionEnabled? (observations.camera_unavailable_reason || cameraUnavailableReason): null
 };

 return state;
 }

 function observationDataForSubmit(answerState, fillerLimit = 500, cameraLimit = 180) {
 const observations = answerState && answerState.observation_data && typeof answerState.observation_data === 'object'? answerState.observation_data: {};

 return {...observations,
 filler_events: Array.isArray(observations.filler_events)? observations.filler_events.slice(-fillerLimit): [],
 camera_samples: cameraDetectionEnabled && Array.isArray(observations.camera_samples)? observations.camera_samples.slice(-cameraLimit): [],
 camera_detection_enabled: cameraDetectionEnabled,
 camera_unavailable_reason: cameraDetectionEnabled? (observations.camera_unavailable_reason || cameraUnavailableReason): null
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
 answersData = answersData.map(answerState => normalizeVoiceRecordingAnswerState(normalizeCameraDetectionObservationState(answerState)));

 // Voice state and optional, non-scoring body-language state
 let recognition = null;
 let recognitionActive = false;
 let shouldAutoRestartRecognition = false;
 let isRecording = false;
 let isRecordingPaused = false;
 let recTimerSeconds = 0;
 let recTimerInterval;
 let recTimerStartedAt = 0;
 let recTimerBaseSeconds = 0;
 let recordingStartPromise = null;
 let recordingStopPromise = null;
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
 let answerWaveAudioContext = null;
 let answerWaveAnalyser = null;
 let answerWaveSource = null;
 let answerWaveFrame = null;
 let answerWaveTimeData = null;
 let answerWaveFrequencyData = null;
 let cameraTrackingInFlight = false;
 window.bodyLanguageModelState = window.bodyLanguageModelState || { ready: false, failed: false, poseLandmarker: null };
 const cameraMovementBaselines = {};

 const BrowserSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
 const serverTranscriptionEnabled = @json(\App\Services\AIService::speechTranscriptionAvailable());
 const mobileSpeechSurface = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
 const localMicrophoneHosts = new Set(['localhost', '127.0.0.1', '::1', '[::1]']);
 const speechLocale = document.documentElement.dataset.speechLocale || navigator.language || 'en-US';
 const speechLanguage = speechLocale.split('-')[0];
 const serverTranscriptionMimeType = (() => {
 if (!window.MediaRecorder ||!MediaRecorder.isTypeSupported) return '';
 return [
 'audio/webm;codecs=opus',
 'audio/webm',
 'audio/mp4;codecs=mp4a.40.2',
 'audio/mp4',
 'audio/ogg;codecs=opus',
 'audio/ogg'
 ].find(type => MediaRecorder.isTypeSupported(type)) || '';
 })();
 const voiceSessionMimeType = (() => {
 if (!window.MediaRecorder ||!MediaRecorder.isTypeSupported) return '';
 return [
 'audio/webm;codecs=opus',
 'audio/webm',
 'audio/mp4;codecs=mp4a.40.2',
 'audio/mp4',
 'audio/ogg;codecs=opus',
 'audio/ogg'
 ].find(type => MediaRecorder.isTypeSupported(type)) || '';
 })();
 const voiceSessionTimesliceMs = 1000;
 const voiceSessionStopTimeoutMs = 8000;
 const serverTranscriptionTimesliceMs = mobileSpeechSurface? {{ max(2200, min(5000, (int) config('services.ai_transcription.mobile_chunk_ms', 3500))) }}: {{ max(1800, min(4000, (int) config('services.ai_transcription.chunk_ms', 2500))) }};
 const serverTranscriptionDrainTimeoutMs = {{ max(8000, min(60000, (int) config('services.ai_transcription.drain_timeout_ms', 20000))) }};
 const serverTranscriptionRequestTimeoutMs = {{ max(10000, min(60000, (int) config('services.ai_transcription.request_timeout_ms', 30000))) }};
 const voiceSessionTranscriptionMaxBytes = 25600 * 1024;
 const voiceSessionFullTranscriptionTimeoutMs = Math.max(serverTranscriptionRequestTimeoutMs, 60000);
 const serverTranscriptionMaxInFlight = {{ max(1, min(3, (int) config('services.ai_transcription.max_in_flight', 2))) }};
 const serverTranscriptionFailureLimit = 3;
 const serverTranscriptionSupported = serverTranscriptionEnabled
 && Boolean(window.MediaRecorder)
 && Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
 const displayRealtimeTranscriptInTextarea = true;
 let activeTranscriptionEngine = isHybridTranscriptionMode() && displayRealtimeTranscriptInTextarea? (BrowserSpeechRecognition? 'browser': (serverTranscriptionSupported? 'server': null)): null;
 const duplicateSafeWordSet = new Set([
 'i', "i'm", 'the', 'a', 'an', 'and', 'to', 'of', 'for', 'in', 'on', 'it', 'is', 'was',
 'were', 'am', 'are', 'my', 'we', 'you', 'that', 'this', 'with', 'um', 'uh', 'like'
 ]);
 const transcriptDuplicatePhraseMaxWords = 96;
 const transcriptOverlapMaxWords = 240;
 const transcriptRecentDuplicateScanWords = 360;
 const voiceSessionRecordings = new Map();
 let voiceSessionRecorder = null;
 let voiceSessionStream = null;
 let voiceSessionChunks = [];
 let voiceSessionQuestionKey = null;
 let voiceSessionQuestionIndex = null;
 let voiceSessionStopPromise = null;
 let voiceSessionStartPromise = null;
 let voiceSessionTranscriptPromise = null;
 let voiceSessionTranscriptQuestionKey = null;
 let voiceSessionRecordingStartedAt = 0;
 let voiceSessionTrackListeners = [];
 let voiceSessionUiState = 'idle';
 let voiceSessionUiMessage = '';
 let voiceSessionUiQuestionKey = null;

 const transcriptWordCorrections = Object.freeze({
 i: 'I',
 im: "I'm",
 ive: "I've",
 dont: "don't",
 doesnt: "doesn't",
 didnt: "didn't",
 cant: "can't",
 couldnt: "couldn't",
 shouldnt: "shouldn't",
 wouldnt: "wouldn't",
 wont: "won't",
 isnt: "isn't",
 arent: "aren't",
 wasnt: "wasn't",
 werent: "weren't",
 hasnt: "hasn't",
 havent: "haven't",
 hadnt: "hadn't",
 alot: 'a lot',
 teh: 'the',
 recieve: 'receive',
 recieved: 'received',
 recieving: 'receiving',
 seperate: 'separate',
 definately: 'definitely',
 occured: 'occurred',
 acheive: 'achieve',
 acheived: 'achieved',
 acheiving: 'achieving',
 accomodate: 'accommodate',
 accomodated: 'accommodated',
 adress: 'address',
 responsable: 'responsible',
 responsibilty: 'responsibility',
 experiance: 'experience',
 enviroment: 'environment',
 improvment: 'improvement',
 improovement: 'improvement',
 communcation: 'communication',
 communicaton: 'communication',
 managment: 'management',
 opurtunity: 'opportunity',
 oppurtunity: 'opportunity',
 recomend: 'recommend',
 recomended: 'recommended',
 coustomer: 'customer',
 custumer: 'customer',
 costomer: 'customer',
 requirment: 'requirement',
 requriement: 'requirement',
 succesful: 'successful',
 sucessful: 'successful',
 sucessfully: 'successfully',
 benifit: 'benefit',
 benifits: 'benefits',
 proffesional: 'professional',
 proffesionalism: 'professionalism',
 api: 'API',
 ai: 'AI',
 bpo: 'BPO',
 crm: 'CRM',
 css: 'CSS',
 html: 'HTML',
 js: 'JS',
 kpi: 'KPI',
 ojt: 'OJT',
 qa: 'QA',
 sla: 'SLA',
 sql: 'SQL',
 ui: 'UI',
 ux: 'UX',
 github: 'GitHub',
 javascript: 'JavaScript',
 laravel: 'Laravel',
 vue: 'Vue'
 });
 const transcriptNaturalCasePattern = /[A-Z]{2,}|[a-z][A-Z]|^I(?:['\u2019]|$)/u;

 function transcriptCorrectionKey(word) {
 return String(word || '').replace(/\u2019/g, "'").toLocaleLowerCase(speechLocale).replace(/^[-']+|[-']+$/g, '');
 }

 function transcriptLettersOnly(value) {
 return String(value || '').replace(/[^\p{L}]+/gu, '');
 }

 function isAllUpperTranscriptWord(value) {
 const letters = transcriptLettersOnly(value);
 if (!letters) return false;
 return letters === letters.toLocaleUpperCase(speechLocale)
 && letters!== letters.toLocaleLowerCase(speechLocale);
 }

 function isTitleCaseTranscriptWord(value) {
 const letters = transcriptLettersOnly(value);
 const chars = Array.from(letters);
 if (!chars.length) return false;
 const first = chars[0];
 const rest = chars.slice(1).join('');
 return first === first.toLocaleUpperCase(speechLocale)
 && rest === rest.toLocaleLowerCase(speechLocale)
 && letters!== letters.toLocaleLowerCase(speechLocale);
 }

 function upperFirstTranscript(value) {
 const chars = Array.from(String(value || ''));
 if (!chars.length) return '';
 return chars[0].toLocaleUpperCase(speechLocale) + chars.slice(1).join('');
 }

 function applyTranscriptCorrectionCase(original, correction) {
 if (transcriptNaturalCasePattern.test(correction)) return correction;
 if (isAllUpperTranscriptWord(original)) return correction.toLocaleUpperCase(speechLocale);
 if (isTitleCaseTranscriptWord(original)) return upperFirstTranscript(correction);
 return correction;
 }

 function autoCorrectTranscriptText(value) {
 const text = String(value || '');
 if (!text.trim()) return '';

 return text.replace(/[\p{L}\p{N}][\p{L}\p{N}'\u2019-]*/gu, word => {
 const correction = transcriptWordCorrections[transcriptCorrectionKey(word)];
 return correction? applyTranscriptCorrectionCase(word, correction): word;
 }).replace(/\s+/g, ' ').trim();
 }

 function cleanTranscriptText(value) {
 return autoCorrectTranscriptText(String(value || '').replace(/\s+/g, ' ').trim());
 }

 function normalizeTranscriptForMatch(value) {
 return cleanTranscriptText(value).toLocaleLowerCase(speechLocale).replace(/[^\p{L}\p{N}'\u2019\s]/gu, '').replace(/\s+/g, ' ').trim();
 }

 function wordsForTranscript(value) {
 return cleanTranscriptText(value).split(/\s+/).filter(Boolean);
 }

 function normalizedTranscriptWords(value) {
 return wordsForTranscript(value).map(normalizeTranscriptForMatch);
 }

 function normalizedWordsEqualAt(words, start, comparison) {
 for (let offset = 0; offset < comparison.length; offset++) {
 if (words[start + offset]!== comparison[offset]) {
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
 return cleanTranscriptText(existingClean + (remainder? ' ' + remainder: ''));
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
 return best? best.transcript: '';
 }

 function resetSpeechRecognitionBufferFromTextarea() {
 const ta = document.getElementById('answerTextarea');
 preRecordingText = ta? cleanTranscriptText(ta.value): '';
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
 if (next!== cleanSegment) {
 cleanSegment = next;
 changed = true;
 }
 });
 }

 return cleanSegment;
 }

 function syncSpeechRecognitionBufferFromManualEdit() {
 const ta = document.getElementById('answerTextarea');
 const rawText = ta? String(ta.value || ''): '';
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

 if (isHybridTranscriptionMode() && displayRealtimeTranscriptInTextarea) {
 answerState.speech_transcript = currentText;
 }
 answersData[currentQIdx] = answerState;
 }

 function handleAnswerInput() {
 syncSpeechRecognitionBufferFromManualEdit();
 triggerAnalysis();
 updateAnswerTranscriptionOverlay();
 updateSendAnswerButtonState();
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
 const duplicateWindowMs = fillerOnly? 750: 5000;
 if (normalized && normalized === lastCommittedSpeech && (now - lastCommittedAt) < duplicateWindowMs) {
 return false;
 }

 const appendSpeech = existing => fillerOnly? cleanTranscriptText(`${existing || ''} ${cleanSegment}`): appendWithoutOverlap(existing || '', cleanSegment);
 const answerState = answersData[currentQIdx] || defaultAnswerState();
 const nextCommittedTranscript = collapseRepeatedSpeech(appendSpeech(committedSpeechTranscript));
 const shouldWriteLiveTranscript = displayRealtimeTranscriptInTextarea;
 const currentAnswerTranscript = cleanTranscriptText(answerState.speech_transcript);
 const nextAnswerTranscript = shouldWriteLiveTranscript? collapseRepeatedSpeech(appendSpeech(currentAnswerTranscript)): currentAnswerTranscript;

 if (!fillerOnly
 && normalizeTranscriptForMatch(nextCommittedTranscript) === normalizeTranscriptForMatch(committedSpeechTranscript)
 && (!shouldWriteLiveTranscript || normalizeTranscriptForMatch(nextAnswerTranscript) === normalizeTranscriptForMatch(currentAnswerTranscript))
 ) {
 return false;
 }

 committedSpeechTranscript = nextCommittedTranscript;
 if (shouldWriteLiveTranscript) {
 answerState.speech_transcript = nextAnswerTranscript;
 answersData[currentQIdx] = answerState;
 }
 lastCommittedSpeech = normalized;
 lastCommittedAt = now;
 return true;
 }

 function syncHybridAnswerStateFromTextarea() {
 const answerState = answersData[currentQIdx];
 if (!answerState ||!isHybridTranscriptionMode()) return;

 const textareaText = currentAnswerTextareaText();
 answerState.text = textareaText;
 answerState.speech_transcript = cleanTranscriptText(textareaText);
 answersData[currentQIdx] = answerState;
 }

 function writeHybridTranscriptToTextarea(value) {
 if (!isHybridTranscriptionMode() ||!displayRealtimeTranscriptInTextarea) return false;

 const textarea = answerTextareaElement();
 const renderedTranscript = String(value || '');
 const wasFocusedAtEnd = textarea
 && document.activeElement === textarea
 && textarea.selectionStart === textarea.value.length
 && textarea.selectionEnd === textarea.value.length;

 if (textarea && textarea.value!== renderedTranscript) {
 textarea.value = renderedTranscript;
 if (wasFocusedAtEnd && typeof textarea.setSelectionRange === 'function') {
 const end = textarea.value.length;
 textarea.setSelectionRange(end, end);
 }
 textarea.scrollTop = textarea.scrollHeight;
 }

 const answerState = answersData[currentQIdx] || defaultAnswerState();
 answerState.text = textarea? String(textarea.value || ''): renderedTranscript;
 answerState.speech_transcript = cleanTranscriptText(answerState.text);
 answersData[currentQIdx] = answerState;
 updateAnswerTranscriptionOverlay();
 return true;
 }

 function renderSpeechTranscript() {
 if (!isHybridTranscriptionMode() ||!displayRealtimeTranscriptInTextarea) return;

 const recognizedTranscript = mergeTranscriptParts(committedSpeechTranscript, liveSpeechInterim);
 const renderedTranscript = mergeTranscriptParts(preRecordingText, recognizedTranscript);
 if (!writeHybridTranscriptToTextarea(renderedTranscript)) return;
 triggerAnalysis();
 }

 function setTranscriptionStatus(message, color = 'var(--tx3)') {
 const status = document.getElementById('transcriptionStatus');
 if (!status) return;
 const normalizedMessage = String(message || '').trim();
 status.dataset.message = normalizedMessage;
 status.textContent = normalizedMessage;
 status.style.color = color;
 status.style.display = normalizedMessage? 'block': 'none';
 }

 function currentTranscriptionStatusMessage() {
 const status = document.getElementById('transcriptionStatus');
 return status?.dataset?.message || status?.textContent || '';
 }

 function voiceSessionRecorderSupported() {
 return Boolean(window.MediaRecorder && navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
 }

 function voiceSessionKeyFor(index = currentQIdx) {
 const question = questions[index];
 return question?.id? `question:${question.id}`: `index:${index}`;
 }

 function voiceSessionExtension(mimeType = '') {
 const type = String(mimeType || '').toLowerCase();
 if (type.includes('mp4')) return 'm4a';
 if (type.includes('ogg')) return 'ogg';
 if (type.includes('mpeg')) return 'mp3';
 if (type.includes('wav')) return 'wav';
 return 'webm';
 }

 function voiceSessionFilename(index = currentQIdx, mimeType = '') {
 const questionNumber = Math.max(1, Number(questionDisplayNumber(index) || index + 1));
 const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
 return `speakready-session-${interviewSessionId}-q${questionNumber}-${timestamp}.${voiceSessionExtension(mimeType)}`;
 }

 function formatFileSize(bytes) {
 const size = Math.max(0, Number(bytes || 0));
 if (size >= 1048576) {
 return `${(size / 1048576).toFixed(size >= 10485760? 0: 1)} MB`;
 }
 if (size > 0) {
 return `${Math.max(1, Math.round(size / 1024))} KB`;
 }
 return '';
 }

 function createVoiceSessionRecorder(stream) {
 const attempts = voiceSessionMimeType? [{ mimeType: voiceSessionMimeType }, null]: [null];
 let lastError = null;

 for (const options of attempts) {
 try {
 return options? new MediaRecorder(stream, options): new MediaRecorder(stream);
 } catch (error) {
 lastError = error;
 }
 }

 throw lastError || new Error('MediaRecorder could not start.');
 }

 function clearVoiceSessionTrackListeners() {
 voiceSessionTrackListeners.forEach(({ track, eventName, handler }) => {
 try {
 track.removeEventListener(eventName, handler);
 } catch (error) {
 console.warn('Voice session track listener cleanup failed:', error);
 }
 });
 voiceSessionTrackListeners = [];
 }

 function attachVoiceSessionTrackGuards(stream, key) {
 clearVoiceSessionTrackListeners();
 if (!stream || typeof stream.getAudioTracks!== 'function') return;

 stream.getAudioTracks().forEach(track => {
 const handleEnded = () => {
 if (voiceSessionQuestionKey!== key) return;
 setVoiceSessionUiState('error', 'Microphone stopped unexpectedly', key);
 Promise.resolve(stopVoiceSessionRecorder()).catch(error => {
 console.warn('Voice session stop after track ended failed:', error);
 });
 };

 track.addEventListener('ended', handleEnded, { once: true });
 voiceSessionTrackListeners.push({ track, eventName: 'ended', handler: handleEnded });
 });
 }

 function releaseVoiceSessionStream() {
 clearVoiceSessionTrackListeners();
 stopAnswerTranscriptionVisualizer();
 stopMediaStream(voiceSessionStream);
 voiceSessionStream = null;
 }

 function revokeVoiceSessionRecording(recording) {
 if (recording?.url) URL.revokeObjectURL(recording.url);
 }

 function clearVoiceSessionRecordingFor(keyOrIndex = currentQIdx) {
 const key = typeof keyOrIndex === 'string'? keyOrIndex: voiceSessionKeyFor(keyOrIndex);
 const recording = voiceSessionRecordings.get(key);
 if (recording) revokeVoiceSessionRecording(recording);
 voiceSessionRecordings.delete(key);
 if (key === voiceSessionKeyFor(currentQIdx)) {
 updateAnswerVoiceRecordingMetadata(currentQIdx, null);
 }
 }

 function updateAnswerVoiceRecordingMetadata(index = currentQIdx, recording = null) {
 if (!answersData[index]) return;
 answersData[index].voice_recording = recording? {
 available: true,
 local_only: true,
 mime_type: recording.mimeType || '',
 byte_size: Math.max(0, Number(recording.size || 0)),
 duration_seconds: Math.max(0, Number(recording.durationSeconds || 0)),
 filename: recording.filename || '',
 transcribed: Boolean(recording.transcript),
 transcription_status: String(recording.transcriptionStatus || ''),
 transcription_source: String(recording.transcriptionSource || ''),
 transcribed_at: String(recording.transcribedAt || '')
 }: defaultVoiceRecordingState();
 }

 function submittableVoiceSessionRecording(index = currentQIdx, recording = null) {
 if (!isVoiceTranscriptionMode()) return null;
 const currentRecording = recording || voiceSessionRecordings.get(voiceSessionKeyFor(index));
 if (!currentRecording?.blob) return null;
 const size = Math.max(0, Number(currentRecording.blob.size || currentRecording.size || 0));
 if (size < 128 || size > voiceSessionTranscriptionMaxBytes) return null;
 return currentRecording;
 }

 function appendVoiceSessionRecordingUpload(formData, index = currentQIdx, recording = null) {
 const currentRecording = submittableVoiceSessionRecording(index, recording);
 if (!currentRecording) return false;

 const blob = currentRecording.blob;
 const mimeType = currentRecording.mimeType || blob.type || '';
 const durationSeconds = Math.max(0, Math.round(Number(currentRecording.durationSeconds || 0)));
 const transcriptionStatus = String(currentRecording.transcriptionStatus || (currentRecording.transcript? 'transcribed': 'unavailable'));

 formData.append('voice_audio', blob, currentRecording.filename || voiceSessionFilename(index, mimeType));
 formData.append('voice_recording_duration_seconds', durationSeconds);
 formData.append('voice_recording_transcription_status', transcriptionStatus);
 return true;
 }

 function setVoiceSessionUiState(state, message = '', key = voiceSessionQuestionKey || voiceSessionKeyFor()) {
 voiceSessionUiState = state || 'idle';
 voiceSessionUiMessage = message || '';
 voiceSessionUiQuestionKey = key;
 renderVoiceSessionPanel();
 }

 function renderVoiceSessionPanel(index = currentQIdx) {
 const panel = document.getElementById('voiceSessionPanel');
 if (!panel) return;

 const shouldShow = isVoiceOnlyMode();
 panel.hidden =!shouldShow;
 if (!shouldShow) {
 updateVoiceSessionActionState(null);
 return;
 }

 const key = voiceSessionKeyFor(index);
 const recording = voiceSessionRecordings.get(key);
 const activeForQuestion = voiceSessionRecorder && voiceSessionQuestionKey === key;
 const transcribingForQuestion = voiceSessionTranscriptPromise && voiceSessionTranscriptQuestionKey === key;
 let state = recording?.transcript? 'transcribed': (recording? 'ready': 'idle');
 let message = recording?.transcript? 'Transcript added to answer': (recording? 'Playback ready': 'Ready');

 if (activeForQuestion && voiceSessionRecorder.state === 'recording') {
 state = 'recording';
 message = 'Recording';
 } else if (activeForQuestion && voiceSessionRecorder.state === 'paused') {
 state = 'paused';
 message = 'Paused';
 } else if (transcribingForQuestion) {
 state = 'transcribing';
 message = 'Transcribing full voice recording';
 } else if (voiceSessionUiQuestionKey === key && voiceSessionUiMessage) {
 state = voiceSessionUiState;
 message = voiceSessionUiMessage;
 }

 if (!recording &&!activeForQuestion && (!voiceSessionRecorderSupported() || microphoneRequiresSecureOrigin())) {
 state = 'unavailable';
 message = microphoneRequiresSecureOrigin()? 'Microphone needs HTTPS or localhost': 'Audio recorder unavailable';
 }

 panel.dataset.state = state;

 const badge = document.getElementById('voiceSessionBadge');
 const status = document.getElementById('voiceSessionStatus');
 const meta = document.getElementById('voiceSessionMeta');
 const player = document.getElementById('voiceSessionPlayback');
 const labels = {
 idle: 'Ready',
 recording: 'Recording',
 paused: 'Paused',
 ready: 'Ready',
 saving: 'Saving',
 transcribing: 'Transcribing',
 transcribed: 'Transcribed',
 unavailable: 'Unavailable',
 error: 'Check mic'
 };

 if (badge) badge.textContent = labels[state] || labels.idle;
 if (status) status.textContent = message;
 if (meta) {
 if (recording) {
 meta.textContent = [formatSeconds(recording.durationSeconds), formatFileSize(recording.size)].filter(Boolean).join(' / ');
 } else if (activeForQuestion && recTimerSeconds > 0) {
 meta.textContent = formatSeconds(recTimerSeconds);
 } else {
 meta.textContent = '';
 }
 }

 const hasPlayback = Boolean(recording?.url);
 if (player) {
 if (hasPlayback) {
 if (player.dataset.voiceUrl!== recording.url) {
 player.src = recording.url;
 player.dataset.voiceUrl = recording.url;
 player.load();
 }
 } else if (player.dataset.voiceUrl) {
 player.pause();
 player.removeAttribute('src');
 delete player.dataset.voiceUrl;
 player.load();
 }
 player.classList.toggle('audio-disabled',!hasPlayback);
 player.setAttribute('aria-disabled', String(!hasPlayback));
 }

 updateVoiceSessionActionState(recording, { hasPlayback, transcribingForQuestion });
 }

 function updateVoiceSessionActionState(recording = null, details = {}) {
 const hasRecording = Boolean(recording?.blob || recording?.url);
 const isBusy = Boolean(details.transcribingForQuestion);
 const menuButton = document.getElementById('voiceSessionMenuButton');
 const downloadButton = document.getElementById('voiceSessionDownloadButton');
 const clearButton = document.getElementById('voiceSessionClearButton');
 const menuDisabled =!hasRecording || isBusy;

 if (menuButton) {
 menuButton.disabled = menuDisabled;
 menuButton.setAttribute('aria-disabled', String(menuDisabled));
 menuButton.setAttribute('title', isBusy? 'Voice session is processing': (hasRecording? 'Voice session actions': 'Record a voice session first'));
 }
 if (downloadButton) {
 downloadButton.disabled =!hasRecording || isBusy;
 downloadButton.setAttribute('aria-disabled', String(downloadButton.disabled));
 }
 if (clearButton) {
 clearButton.disabled =!hasRecording || isBusy;
 clearButton.setAttribute('aria-disabled', String(clearButton.disabled));
 }

 if (menuDisabled) {
 closeVoiceSessionMenu();
 }
 }

 function setVoiceSessionMenuOpen(open) {
 const button = document.getElementById('voiceSessionMenuButton');
 const menu = document.getElementById('voiceSessionMenu');
 if (!button ||!menu) return;

 const shouldOpen = Boolean(open) &&!button.disabled;
 menu.hidden =!shouldOpen;
 button.setAttribute('aria-expanded', String(shouldOpen));
 }

 function closeVoiceSessionMenu() {
 setVoiceSessionMenuOpen(false);
 }

 function toggleVoiceSessionMenu(event) {
 event?.preventDefault();
 event?.stopPropagation();
 const menu = document.getElementById('voiceSessionMenu');
 const button = document.getElementById('voiceSessionMenuButton');
 if (!menu ||!button || button.disabled) return;
 setVoiceSessionMenuOpen(menu.hidden);
 }

 function downloadVoiceSessionRecording(event) {
 event?.preventDefault();
 event?.stopPropagation();
 closeVoiceSessionMenu();

 const recording = voiceSessionRecordings.get(voiceSessionKeyFor());
 if (!recording?.blob &&!recording?.url) {
 showSessionNotice('Record a voice session before downloading.', 'warning');
 return;
 }

 const mimeType = recording.mimeType || recording.blob?.type || '';
 const url = recording.url || URL.createObjectURL(recording.blob);
 const link = document.createElement('a');
 link.href = url;
 link.download = recording.filename || voiceSessionFilename(currentQIdx, mimeType);
 document.body.appendChild(link);
 link.click();
 link.remove();

 if (!recording.url) {
 setTimeout(() => URL.revokeObjectURL(url), 1000);
 }
 }

 function clearCurrentVoiceSession(event) {
 event?.preventDefault();
 event?.stopPropagation();
 closeVoiceSessionMenu();

 const key = voiceSessionKeyFor();
 const recording = voiceSessionRecordings.get(key);
 if (!recording) {
 showSessionNotice('There is no voice session to clear.', 'warning');
 return;
 }
 if (voiceSessionTranscriptPromise && voiceSessionTranscriptQuestionKey === key) {
 showSessionNotice('Wait for transcription to finish before clearing this voice session.', 'warning');
 return;
 }

 const answerState = answersData[currentQIdx] || defaultAnswerState();
 const generatedTranscript = cleanTranscriptText(answerState.speech_transcript || recording.transcript || '');
 const textarea = document.getElementById('answerTextarea');
 const currentText = textarea? String(textarea.value || ''): String(answerState.text || '');
 const canClearGeneratedText = isHybridTranscriptionMode()
 && generatedTranscript
 && normalizeTranscriptForMatch(currentText) === normalizeTranscriptForMatch(generatedTranscript);

 clearVoiceSessionRecordingFor(key);
 answerState.speech_transcript = '';
 answerState.voice_duration = 0;
 answerState.wpm = 0;
 answerState.pronunciation_analysis = null;

 if (canClearGeneratedText) {
 if (textarea) textarea.value = '';
 answerState.text = '';
 } else {
 answerState.text = currentText;
 }

 answersData[currentQIdx] = answerState;
 resetSpeechRecognitionBufferFromTextarea();
 setTranscriptionStatus('');
 setVoiceSessionUiState('idle', 'Voice session cleared', key);

 if (canClearGeneratedText) {
 triggerAnalysis();
 } else {
 updateSendAnswerButtonState();
 scheduleStateSave();
 }

 showSessionAlertModal(canClearGeneratedText? 'Voice session and generated transcript cleared.': 'Voice session cleared.', 'Voice Session Cleared', 'success');
 }

 function voiceSessionTranscriptionErrorMessage(error) {
 const errorCode = String(error?.errorCode || '');
 if (error?.name === 'AbortError') {
 return 'Full voice transcription timed out. Try again with a shorter answer.';
 }
 if (errorCode === 'speech_transcription_rate_limited' || error?.status === 429) {
 const waitSeconds = Number(error?.retryAfterSeconds || 0);
 return waitSeconds > 0? `AI transcription is rate limited. Try again in ${Math.ceil(waitSeconds)}s.`: 'AI transcription is rate limited. Try again in a moment.';
 }
 if (errorCode === 'speech_transcription_unavailable' || error?.status === 503) {
 return 'Full voice transcription is unavailable right now.';
 }
 return error?.message || 'Full voice transcription failed. Please try again.';
 }

 function mergeFullVoiceTranscriptWithAnswer(existingText, previousSpeechTranscript, fullTranscript) {
 const existing = cleanTranscriptText(existingText);
 const previousSpeech = cleanTranscriptText(previousSpeechTranscript);
 const transcript = cleanTranscriptText(fullTranscript);
 if (!transcript) return existing;
 if (!existing) return transcript;

 if (previousSpeech && existing.includes(previousSpeech)) {
 return collapseRepeatedSpeech(cleanTranscriptText(existing.replace(previousSpeech, transcript)));
 }

 const existingNorm = normalizeTranscriptForMatch(existing);
 const previousNorm = normalizeTranscriptForMatch(previousSpeech);
 const transcriptNorm = normalizeTranscriptForMatch(transcript);

 if (!transcriptNorm) return existing;
 if (existingNorm === previousNorm || existingNorm === transcriptNorm || transcriptNorm.includes(existingNorm)) {
 return transcript;
 }
 if (existingNorm.includes(transcriptNorm)) {
 return existing;
 }

 return mergeTranscriptParts(existing, transcript);
 }

 function applyVoiceSessionTranscript(index, transcript, data = {}, recording = null, options = {}) {
 const cleanTranscript = cleanTranscriptText(transcript);
 if (!cleanTranscript ||!answersData[index]) return '';

 const answerState = answersData[index] || defaultAnswerState();
 const textarea = index === currentQIdx? document.getElementById('answerTextarea'): null;
 const existingText = textarea? String(textarea.value || ''): String(answerState.text || '');
 const mergedAnswerText = options.replaceAnswerText === true
 ? cleanTranscript
 : mergeFullVoiceTranscriptWithAnswer(existingText, answerState.speech_transcript || '', cleanTranscript);
 const voiceDuration = Math.max(
 Number(answerState.voice_duration || 0),
 Number(recording?.durationSeconds || 0),
 Number(answerState.voice_recording?.duration_seconds || 0)
 );
 const wordCount = wordsForTranscript(cleanTranscript).length;

 answerState.speech_transcript = cleanTranscript;
 answerState.text = mergedAnswerText;
 answerState.voice_duration = Math.max(0, Math.round(voiceDuration));
 answerState.wpm = answerState.voice_duration > 0? Math.round((wordCount / Math.max(1, answerState.voice_duration)) * 60): answerState.wpm;
 answersData[index] = answerState;

 recordLocalSpeechAnalysis(index, data.pronunciation_analysis || null);

 if (recording) {
 recording.transcript = cleanTranscript;
 recording.transcriptionStatus = data.transcription_status || 'transcribed';
 recording.transcriptionSource = data.transcription_source || 'ai';
 recording.transcribedAt = new Date().toISOString();
 updateAnswerVoiceRecordingMetadata(index, recording);
 }

 if (index === currentQIdx) {
 if (textarea) textarea.value = mergedAnswerText;
 committedSpeechTranscript = cleanTranscript;
 liveSpeechInterim = '';
 resetSpeechRecognitionBufferFromTextarea();

 const durationTarget = document.getElementById('vaDuration');
 if (durationTarget) durationTarget.innerText = answerState.voice_duration + 's';
 const wpmTarget = document.getElementById('vaWpm');
 if (wpmTarget) wpmTarget.innerText = answerState.wpm;
 captureTranscriptTimeline('full_voice_transcript', true, {
 source: data.transcription_source || 'voice_session',
 status: data.transcription_status || 'transcribed'
 });
 triggerAnalysis();
 }

 scheduleStateSave();
 return cleanTranscript;
 }

 async function requestFullVoiceSessionTranscript(recording, question, previousTranscript = '') {
 const attempts = 2;
 let lastError = null;

 for (let attempt = 1; attempt <= attempts; attempt++) {
 try {
 const formData = new FormData();
 formData.append('_token', '{{ csrf_token() }}');
 formData.append('session_id', interviewSessionId);
 formData.append('question_id', question.id);
 formData.append('previous_transcript', cleanTranscriptText(previousTranscript).slice(-3000));
 formData.append('audio', recording.blob, recording.filename || serverTranscriptionFilename(recording.blob));

 const response = await managedFetch('{{ route("interview.transcribe") }}', {
 method: 'POST',
 body: formData,
 headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
 timeoutMs: voiceSessionFullTranscriptionTimeoutMs
 });
 const payload = await parseResponsePayload(response);

 if (!response.ok) {
 const error = new Error(responseErrorMessage(response, payload, 'Full voice transcription failed.'));
 error.status = response.status;
 error.errorCode = payload.data?.error_code || '';
 error.retryAfterSeconds = Number(payload.data?.retry_after_seconds || 0) || null;
 throw error;
 }

 return payload.data || {};
 } catch (error) {
 lastError = error;
 if (attempt >= attempts ||!isRetryableRequestError(error)) {
 throw error;
 }
 setVoiceSessionUiState('transcribing', 'Retrying full voice transcription', voiceSessionTranscriptQuestionKey || voiceSessionKeyFor());
 setTranscriptionStatus('Retrying full voice transcription', '#fbbf24');
 await waitForRequestRetry(900 * attempt);
 }
 }

 throw lastError || new Error('Full voice transcription failed.');
 }

 async function transcribeVoiceSessionRecording(index = currentQIdx, options = {}) {
 if (voiceSessionTranscriptPromise) return voiceSessionTranscriptPromise;
 if (!isHybridTranscriptionMode()) {
 setTranscriptionStatus('Voice Mode is voice-only. Use Hybrid Mode for voice-to-text.', '#fbbf24');
 return '';
 }

 const silent = options.silent === true;
 const skipStopRecording = options.skipStopRecording === true;
 const key = voiceSessionKeyFor(index);
 voiceSessionTranscriptQuestionKey = key;

 voiceSessionTranscriptPromise = (async () => {
 if (!skipStopRecording && index === currentQIdx && (recordingStartPromise || recordingStopPromise || isRecording || isRecordingPaused || voiceSessionRecorder || voiceSessionStopPromise)) {
 await stopRecording();
 }

 if (voiceSessionStopPromise) {
 await voiceSessionStopPromise.catch(error => {
 console.warn('Voice session stop wait before transcript failed:', error);
 });
 }

 const recording = voiceSessionRecordings.get(key);
 const question = questions[index];
 if (!question?.id) {
 throw new Error('Interview question is not ready for transcription.');
 }
 if (!recording?.blob || recording.blob.size < 128) {
 throw new Error('Record your answer first, then generate the transcript.');
 }
 if (recording.blob.size > voiceSessionTranscriptionMaxBytes) {
 throw new Error('Recording is too large to transcribe. Record a shorter answer, then try again.');
 }

 setVoiceSessionUiState('transcribing', 'Transcribing full voice recording', key);
 setTranscriptionStatus('Transcribing full voice recording', '#fbbf24');

 const previousTranscript = Object.prototype.hasOwnProperty.call(options, 'previousTranscript')? options.previousTranscript: (answersData[index]?.speech_transcript || '');
 const data = await requestFullVoiceSessionTranscript(recording, question, previousTranscript);
 const transcript = cleanTranscriptText(data.transcript || '');
 if (!transcript) {
 throw new Error('No speech was detected in the voice recording.');
 }

 const appliedTranscript = applyVoiceSessionTranscript(index, transcript, data, recording, {
 replaceAnswerText: options.replaceAnswerText === true
 });
 setVoiceSessionUiState('transcribed', 'Transcript added to answer', key);
 setTranscriptionStatus('Full voice transcript added', '#16a34a');
 if (!silent) {
 showSessionNotice('Transcript added to your answer. You can edit it before sending for feedback.', 'success');
 }
 return appliedTranscript;
 })().catch(error => {
 const message = voiceSessionTranscriptionErrorMessage(error);
 console.warn('Full voice transcription failed:', error);
 const recording = voiceSessionRecordings.get(key);
 if (recording) {
 recording.transcriptionStatus = 'failed';
 updateAnswerVoiceRecordingMetadata(index, recording);
 }
 setVoiceSessionUiState('error', message, key);
 setTranscriptionStatus(message, '#f87171');
 if (!silent) showSessionNotice(message, 'warning');
 return '';
 }).finally(() => {
 voiceSessionTranscriptPromise = null;
 voiceSessionTranscriptQuestionKey = null;
 renderVoiceSessionPanel(index);
 });

 return voiceSessionTranscriptPromise;
 }

 async function fillEmptyHybridTranscriptFromRecording(index = currentQIdx) {
 if (!isHybridTranscriptionMode() || currentAnswerTextareaText().trim() !== '') return false;
 if (!serverTranscriptionEnabled || serverTranscriptionUnavailable || microphoneRequiresSecureOrigin()) return false;
 const recording = voiceSessionRecordings.get(voiceSessionKeyFor(index));
 if (!recording?.blob || recording.blob.size < 128) return false;

 const transcript = await transcribeVoiceSessionRecording(index, {
 silent: true,
 skipStopRecording: true,
 replaceAnswerText: true,
 previousTranscript: ''
 });

 return cleanTranscriptText(transcript).trim() !== '' && currentAnswerTextareaText().trim() !== '';
 }

 async function startVoiceSessionRecorder() {
 if (voiceSessionStartPromise) return voiceSessionStartPromise;
 voiceSessionStartPromise = startVoiceSessionRecorderInternal().finally(() => {
 voiceSessionStartPromise = null;
 });
 return voiceSessionStartPromise;
 }

 async function startVoiceSessionRecorderInternal() {
 const key = voiceSessionKeyFor();
 if (!isVoiceTranscriptionMode()) return false;

 if (!voiceSessionRecorderSupported() || microphoneRequiresSecureOrigin()) {
 setVoiceSessionUiState('unavailable', microphoneRequiresSecureOrigin()? 'Microphone needs HTTPS or localhost': 'Audio recorder unavailable', key);
 return false;
 }

 if (voiceSessionRecorder && voiceSessionQuestionKey === key && voiceSessionRecorder.state === 'paused') {
 try {
 voiceSessionRecorder.resume();
 voiceSessionRecordingStartedAt = recordingTimerNow();
 setVoiceSessionUiState('recording', 'Recording', key);
 startAnswerTranscriptionVisualizer(voiceSessionStream);
 updateAnswerTranscriptionOverlay();
 return true;
 } catch (error) {
 console.warn('Voice session resume failed:', error);
 }
 }

 if (voiceSessionRecorder && voiceSessionQuestionKey === key && voiceSessionRecorder.state === 'recording') {
 if (!voiceSessionRecordingStartedAt) voiceSessionRecordingStartedAt = recordingTimerNow();
 startAnswerTranscriptionVisualizer(voiceSessionStream);
 updateAnswerTranscriptionOverlay();
 return true;
 }

 if (voiceSessionRecorder && voiceSessionRecorder.state!== 'inactive') {
 await stopVoiceSessionRecorder({ discard: true, skipStartWait: true });
 }

 if (!isRecordingPaused) {
 clearVoiceSessionRecordingFor(key);
 }

 try {
 releaseVoiceSessionStream();
 const sourceStream = mediaStreamHasLiveAudio(microphoneStream)? microphoneStream.clone(): await requestMicrophoneStream();
 ensureLiveMicrophoneStream(sourceStream);
 const recorder = createVoiceSessionRecorder(sourceStream);

 voiceSessionStream = sourceStream;
 voiceSessionRecorder = recorder;
 voiceSessionQuestionKey = key;
 voiceSessionQuestionIndex = currentQIdx;
 voiceSessionChunks = [];
 voiceSessionStopPromise = null;
 voiceSessionRecordingStartedAt = 0;
 attachVoiceSessionTrackGuards(sourceStream, key);
 updateAnswerVoiceRecordingMetadata(currentQIdx, null);

 recorder.ondataavailable = event => {
 if (event.data && event.data.size > 0) {
 voiceSessionChunks.push(event.data);
 }
 };
 recorder.onerror = event => {
 console.warn('Voice session recorder error:', event.error || event);
 setVoiceSessionUiState('error', 'Audio recording interrupted', key);
 };
 recorder.onpause = () => setVoiceSessionUiState('paused', 'Paused', key);
 recorder.onresume = () => setVoiceSessionUiState('recording', 'Recording', key);
 recorder.start(voiceSessionTimesliceMs);
 voiceSessionRecordingStartedAt = recordingTimerNow();
 setVoiceSessionUiState('recording', 'Recording', key);
 startAnswerTranscriptionVisualizer(sourceStream);
 updateAnswerTranscriptionOverlay();
 return true;
 } catch (error) {
 console.warn('Voice session recorder could not start:', error);
 clearVoiceSessionTrackListeners();
 releaseVoiceSessionStream();
 voiceSessionRecorder = null;
 voiceSessionChunks = [];
 voiceSessionRecordingStartedAt = 0;
 setVoiceSessionUiState('error', microphoneErrorMessage(error), key);
 return false;
 }
 }

 function pauseVoiceSessionRecorder() {
 if (!voiceSessionRecorder || voiceSessionRecorder.state!== 'recording') return;
 try {
 try {
 voiceSessionRecorder.requestData();
 } catch (error) {
 console.warn('Voice session flush before pause failed:', error);
 }
 voiceSessionRecorder.pause();
 setVoiceSessionUiState('paused', 'Paused', voiceSessionQuestionKey);
 } catch (error) {
 console.warn('Voice session pause failed:', error);
 }
 }

 function discardVoiceSessionRecorder(options = {}) {
 const revokeSaved = options.revokeSaved === true;
 if (voiceSessionRecorder && voiceSessionRecorder.state!== 'inactive') {
 try {
 voiceSessionRecorder.ondataavailable = null;
 voiceSessionRecorder.onstop = null;
 voiceSessionRecorder.stop();
 } catch (error) {
 console.warn('Voice session cleanup failed:', error);
 }
 }
 voiceSessionRecorder = null;
 voiceSessionChunks = [];
 voiceSessionQuestionKey = null;
 voiceSessionQuestionIndex = null;
 voiceSessionStopPromise = null;
 voiceSessionRecordingStartedAt = 0;
 releaseVoiceSessionStream();

 if (revokeSaved) {
 voiceSessionRecordings.forEach(revokeVoiceSessionRecording);
 voiceSessionRecordings.clear();
 }

 setVoiceSessionUiState('idle', '', voiceSessionKeyFor());
 }

 async function stopVoiceSessionRecorder(options = {}) {
 const discard = options.discard === true;
 if (!options.skipStartWait && voiceSessionStartPromise) {
 await voiceSessionStartPromise.catch(error => {
 console.warn('Voice session start wait before stop failed:', error);
 });
 }

 const recorder = voiceSessionRecorder;
 if (!recorder) return Promise.resolve(null);
 if (voiceSessionStopPromise) return voiceSessionStopPromise;

 const key = voiceSessionQuestionKey;
 const questionIndex = voiceSessionQuestionIndex?? currentQIdx;
 const chunks = voiceSessionChunks;

 setVoiceSessionUiState(discard? 'idle': 'saving', discard? '': 'Preparing playback', key);

 voiceSessionStopPromise = new Promise(resolve => {
 let settled = false;
 const finish = () => {
 if (settled) return;
 settled = true;

 releaseVoiceSessionStream();
 voiceSessionRecorder = null;
 voiceSessionChunks = [];
 voiceSessionQuestionKey = null;
 voiceSessionQuestionIndex = null;
 voiceSessionStopPromise = null;
 voiceSessionRecordingStartedAt = 0;

 let recording = null;
 if (!discard && chunks.length > 0) {
 const mimeType = recorder.mimeType || voiceSessionMimeType || chunks.find(chunk => chunk?.type)?.type || 'audio/webm';
 const blob = new Blob(chunks, { type: mimeType });
 if (blob.size > 0) {
 recording = {
 blob,
 url: URL.createObjectURL(blob),
 mimeType,
 size: blob.size,
 durationSeconds: Math.max(answersData[questionIndex]?.voice_duration || 0, recTimerSeconds || 0),
 filename: voiceSessionFilename(questionIndex, mimeType),
 createdAt: new Date().toISOString()
 };
 const previous = voiceSessionRecordings.get(key);
 if (previous) revokeVoiceSessionRecording(previous);
 voiceSessionRecordings.set(key, recording);
 updateAnswerVoiceRecordingMetadata(questionIndex, recording);
 scheduleStateSave();
 }
 }

 if (recording) {
 setVoiceSessionUiState('ready', 'Playback ready', key);
 } else if (!discard) {
 updateAnswerVoiceRecordingMetadata(questionIndex, null);
 setVoiceSessionUiState('idle', 'No audio captured', key);
 } else {
 setVoiceSessionUiState('idle', '', key);
 }

 resolve(recording);
 };

 recorder.onstop = finish;
 try {
 if (recorder.state!== 'inactive') {
 try {
 recorder.requestData();
 } catch (error) {
 console.warn('Voice session final data flush failed:', error);
 }
 try {
 recorder.stop();
 } catch (error) {
 console.warn('Voice session stop failed:', error);
 finish();
 }
 } else {
 finish();
 }
 } catch (error) {
 console.warn('Voice session stop failed:', error);
 finish();
 }

 setTimeout(finish, voiceSessionStopTimeoutMs);
 });

 return voiceSessionStopPromise;
 }

 function clearSubmittedAnswerInput() {
 const textarea = document.getElementById('answerTextarea');
 if (textarea) textarea.value = '';

 const wordCount = document.getElementById('wordCount');
 const charCount = document.getElementById('charCount');
 if (wordCount) wordCount.innerText = '0 words';
 if (charCount) charCount.innerText = '0 characters';
 if (answersData[currentQIdx]) {
 answersData[currentQIdx].confidence_score = 0;
 answersData[currentQIdx].self_reported_confidence = 0;
 }
 updateSendAnswerButtonState();

 const chatContainer = document.getElementById('chatTranscriptContainer');
 if (chatContainer) {
 chatContainer.innerHTML = '';
 chatContainer.style.display = 'none';
 }

 resetSpeechRecognitionBufferFromTextarea();
 setTranscriptionStatus('');
 renderVoiceSessionPanel();
 resetAiCoachPanel();
 }

 function restoreSubmittedAnswerInput(answerText) {
 const textarea = document.getElementById('answerTextarea');
 if (textarea) textarea.value = answerText || '';
 if (answersData[currentQIdx]) {
 answersData[currentQIdx].text = textarea? String(textarea.value || ''): String(answerText || '');
 }
 resetSpeechRecognitionBufferFromTextarea();
 triggerAnalysis();
 updateSendAnswerButtonState();
 }

 function showSessionNotice(message, type = 'error', focus = false) {
 const notice = document.getElementById('sessionNotice');
 if (!notice ||!message) return;
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

 function showSessionAlertModal(message, title = 'Notice', type = 'success') {
 const modal = document.getElementById('sessionAlertModal');
 const titleEl = document.getElementById('sessionAlertTitle');
 const messageEl = document.getElementById('sessionAlertMessage');
 const icon = document.getElementById('sessionAlertIcon');
 if (!modal ||!messageEl) {
 showSessionNotice(message, type === 'success'? 'success': 'warning');
 return;
 }

 if (modal.parentElement!== document.body) {
 document.body.appendChild(modal);
 }

 if (titleEl) titleEl.textContent = title || 'Notice';
 messageEl.textContent = message || '';
 if (icon) {
 icon.classList.toggle('success', type === 'success');
 icon.classList.toggle('danger', type === 'error');
 icon.classList.toggle('warning', type === 'warning');
 icon.innerHTML = type === 'error'
 ? '<i class="fa-solid fa-circle-exclamation"></i>'
 : (type === 'warning'? '<i class="fa-solid fa-triangle-exclamation"></i>': '<i class="fa-solid fa-circle-check"></i>');
 }

 clearSessionNotice();
 modal.classList.add('active');
 syncInterviewModalBodyState();
 focusFirstModalAction(modal, '#sessionAlertOkButton');
 }

 function closeSessionAlertModal() {
 const modal = document.getElementById('sessionAlertModal');
 modal?.classList.remove('active');
 syncInterviewModalBodyState();
 }

 function microphoneRequiresSecureOrigin() {
 return!(window.isSecureContext || localMicrophoneHosts.has(window.location.hostname));
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

 function mediaStreamHasLiveAudio(stream) {
 return Boolean(
 stream
 && stream.active
 && typeof stream.getAudioTracks === 'function'
 && stream.getAudioTracks().some(track => track.readyState === 'live')
 );
 }

 function ensureLiveMicrophoneStream(stream) {
 if (mediaStreamHasLiveAudio(stream)) return stream;
 stopMediaStream(stream);
 const error = new Error('No live microphone audio track was available.');
 error.name = 'NotFoundError';
 throw error;
 }

 function answerTranscriptionWaveBars() {
 return Array.from(document.querySelectorAll('#answerTranscriptionOverlay .answer-transcription-wave span'));
 }

 function resetAnswerTranscriptionWave() {
 const restingLevels = [0.38, 0.58, 0.44, 0.72, 0.48, 0.62, 0.4];
 answerTranscriptionWaveBars().forEach((bar, index) => {
 bar.style.setProperty('--wave-level', String(restingLevels[index % restingLevels.length]));
 bar.style.setProperty('--wave-opacity', '0.55');
 });
 }

 function stopAnswerTranscriptionVisualizer(options = {}) {
 if (answerWaveFrame) {
 cancelAnimationFrame(answerWaveFrame);
 answerWaveFrame = null;
 }

 try {
 answerWaveSource?.disconnect?.();
 } catch (error) {
 console.warn('Answer transcription wave source cleanup failed:', error);
 }

 if (answerWaveAudioContext) {
 try {
 answerWaveAudioContext.close();
 } catch (error) {
 console.warn('Answer transcription wave audio context cleanup failed:', error);
 }
 }

 answerWaveAudioContext = null;
 answerWaveAnalyser = null;
 answerWaveSource = null;
 answerWaveTimeData = null;
 answerWaveFrequencyData = null;

 if (options.reset!== false) {
 resetAnswerTranscriptionWave();
 }
 }

 function startAnswerTranscriptionVisualizer(stream = voiceSessionStream) {
 if (!mediaStreamHasLiveAudio(stream)) return false;
 if (answerWaveAnalyser && answerWaveAudioContext && answerWaveAudioContext.state!== 'closed') return true;

 const AudioContextClass = window.AudioContext || window.webkitAudioContext;
 if (!AudioContextClass) {
 resetAnswerTranscriptionWave();
 return false;
 }

 try {
 stopAnswerTranscriptionVisualizer({ reset: false });
 answerWaveAudioContext = new AudioContextClass();
 answerWaveAnalyser = answerWaveAudioContext.createAnalyser();
 answerWaveAnalyser.fftSize = 512;
 answerWaveAnalyser.smoothingTimeConstant = 0.58;
 answerWaveSource = answerWaveAudioContext.createMediaStreamSource(stream);
 answerWaveSource.connect(answerWaveAnalyser);
 answerWaveTimeData = new Uint8Array(answerWaveAnalyser.fftSize);
 answerWaveFrequencyData = new Uint8Array(answerWaveAnalyser.frequencyBinCount);

 if (answerWaveAudioContext.state === 'suspended') {
 answerWaveAudioContext.resume().catch(error => {
 console.warn('Answer transcription wave audio context resume failed:', error);
 });
 }

 const renderFrame = () => {
 if (!answerWaveAnalyser ||!answerWaveTimeData ||!answerWaveFrequencyData) return;
 const bars = answerTranscriptionWaveBars();
 if (!bars.length) {
 answerWaveFrame = requestAnimationFrame(renderFrame);
 return;
 }

 answerWaveAnalyser.getByteTimeDomainData(answerWaveTimeData);
 answerWaveAnalyser.getByteFrequencyData(answerWaveFrequencyData);

 let sum = 0;
 for (let index = 0; index < answerWaveTimeData.length; index++) {
 const centered = (answerWaveTimeData[index] - 128) / 128;
 sum += centered * centered;
 }
 const rms = Math.sqrt(sum / Math.max(1, answerWaveTimeData.length));
 const voiceLevel = Math.max(0, Math.min(1, (rms - 0.012) * 9.5));
 const usableBins = Math.max(1, Math.floor(answerWaveFrequencyData.length * 0.42));
 const mid = (bars.length - 1) / 2;

 bars.forEach((bar, index) => {
 const start = Math.floor((index / bars.length) * usableBins);
 const end = Math.max(start + 1, Math.floor(((index + 1) / bars.length) * usableBins));
 let bandTotal = 0;
 for (let bin = start; bin < end; bin++) {
 bandTotal += answerWaveFrequencyData[bin] || 0;
 }
 const bandLevel = Math.max(0, Math.min(1, ((bandTotal / Math.max(1, end - start)) / 255 - 0.025) * 3.8));
 const centerWeight = 1 - Math.abs(index - mid) / Math.max(1, mid) * 0.32;
 const level = Math.max(0.18, Math.min(1.55, 0.2 + Math.max(voiceLevel, bandLevel) * centerWeight * 1.35));
 const opacity = Math.max(0.48, Math.min(1, 0.5 + Math.max(voiceLevel, bandLevel) * 0.62));
 bar.style.setProperty('--wave-level', level.toFixed(2));
 bar.style.setProperty('--wave-opacity', opacity.toFixed(2));
 });

 answerWaveFrame = requestAnimationFrame(renderFrame);
 };

 renderFrame();
 return true;
 } catch (error) {
 console.warn('Answer transcription wave visualizer unavailable:', error);
 stopAnswerTranscriptionVisualizer();
 return false;
 }
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
 if (!AudioContextClass ||!stream?.getAudioTracks?.().length) return stream;

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
 gain.gain.value = mobileSpeechSurface? 2.6: 2.2;

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
 const stream = await navigator.mediaDevices.getUserMedia(audioCaptureConstraints());
 return ensureLiveMicrophoneStream(stream);
 } catch (error) {
 if (error?.name === 'OverconstrainedError' || error?.name === 'ConstraintNotSatisfiedError') {
 const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
 return ensureLiveMicrophoneStream(stream);
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
 if (!BrowserSpeechRecognition &&!serverTranscriptionEnabled) {
 return 'Live transcription needs Chrome/Edge, or an OpenAI key for server transcription.';
 }
 if (!BrowserSpeechRecognition &&!window.MediaRecorder) {
 return 'Live transcription is not supported in this browser.';
 }
 if (!navigator.mediaDevices ||!navigator.mediaDevices.getUserMedia) {
 return 'Microphone access is not available in this browser.';
 }
 return 'Live transcription is not available on this device.';
 }

 function voiceRecordingUnavailableMessage() {
 if (microphoneRequiresSecureOrigin()) {
 return 'Microphone access requires HTTPS online, or http://localhost for local testing.';
 }
 if (!navigator.mediaDevices ||!navigator.mediaDevices.getUserMedia) {
 return 'Microphone access is not available in this browser.';
 }
 if (!window.MediaRecorder) {
 return 'Playable voice recording is not supported in this browser.';
 }
 return '';
 }

 function fullVoiceTranscriptionUnavailableMessage() {
 if (!serverTranscriptionEnabled) {
 return 'Full transcription needs an OpenAI/Gemini transcription key or local speech transcription.';
 }
 if (!window.MediaRecorder) {
 return 'Full transcription needs browser audio recording support.';
 }
 if (!navigator.mediaDevices ||!navigator.mediaDevices.getUserMedia) {
 return 'Microphone access is not available in this browser.';
 }
 if (microphoneRequiresSecureOrigin()) {
 return 'Microphone access requires HTTPS online, or http://localhost for local testing.';
 }
 return '';
 }

 function canUseServerTranscription() {
 return isHybridTranscriptionMode() && serverTranscriptionSupported &&!serverTranscriptionUnavailable &&!microphoneRequiresSecureOrigin();
 }

 function preferredTranscriptionEngine() {
 if (!isHybridTranscriptionMode()) return null;
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
 if (recognitionActive ||!isRecording ||!shouldAutoRestartRecognition || activeTranscriptionEngine!== 'browser') {
 return false;
 }

 try {
 recognition.start();
 recognitionActive = true;
 setTranscriptionStatus('Listening - speak now');
 return true;
 } catch (error) {
 if (!error || error.name!== 'InvalidStateError') {
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

 if (!navigator.mediaDevices ||!navigator.mediaDevices.getUserMedia) {
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
 ||!serverTranscriptionDrainResolver
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
 if (serverTranscriptionUnavailable ||!blob || blob.size < 128 ||!questions[currentQIdx]) return;

 serverTranscriptionQueue.push({
 blob,
 questionIndex: currentQIdx,
 questionId: questions[currentQIdx].id,
 previousTranscript: cleanTranscriptText(displayRealtimeTranscriptInTextarea? (answersData[currentQIdx]?.speech_transcript || ''): committedSpeechTranscript).slice(-3000),
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

 if (serverTranscriptionRecorder && serverTranscriptionRecorder.state!== 'inactive') {
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
 const status = Number(error?.status || 0);
 const rateLimited = errorCode === 'speech_transcription_rate_limited' || error?.status === 429;
 const hardUnavailable = errorCode === 'speech_transcription_unavailable'
 || [400, 401, 403, 404, 413, 419, 422, 500, 501, 503].includes(status);

 if (rateLimited) {
 const waitSeconds = Number(error?.retryAfterSeconds || 0);
 const message = waitSeconds > 0? `AI transcription is rate limited. Try again in ${Math.ceil(waitSeconds)}s.`: 'AI transcription is rate limited. Continue with browser captions or type your answer.';
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
 if (error.name!== 'AbortError') {
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
 if (!job ||!result || job.token!== serverTranscriptionSessionToken) continue;

 recordLocalSpeechAnalysis(job.questionIndex, result.pronunciationAnalysis || null);

 const transcript = cleanTranscriptText(result.transcript || '');
 if (!transcript || job.questionIndex!== currentQIdx) continue;

 if (commitSpeechSegment(transcript)) {
 recordFillerEvents(transcript);
 captureTranscriptTimeline('server_transcript');
 }
 liveSpeechInterim = '';
 renderSpeechTranscript();
 }

 if (isRecording && activeTranscriptionEngine === 'server') {
 setTranscriptionStatus('Listening - live transcript is updating');
 }
 }

 async function transcribeServerChunk(job) {
 if (!job || job.token!== serverTranscriptionSessionToken ||!job.questionId) return;

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
 const direct = Array.isArray(analysis?.reliability?.measured_components)? analysis.reliability.measured_components: [];
 const inferred = ['asr', 'pronunciation', 'forced_alignment', 'phoneme_alignment', 'gop'].filter(key => String(analysis?.[key]?.status || '') === 'measured');
 return [...new Set([...direct,...inferred].map(item => String(item)).filter(Boolean))];
 }

 function localSpeechChunkSummary(analysis) {
 const score = localSpeechScoreFrom(analysis);
 const reliabilityScore = Number(analysis?.reliability?.score);
 return {
 status: String(analysis?.status || 'partial'),
 score: Number.isFinite(score)? Math.round(score): null,
 reliability_score: Number.isFinite(reliabilityScore)? Math.round(Math.max(0, Math.min(100, reliabilityScore))): null,
 reliability_band: analysis?.reliability?.band || null,
 asr_provider: analysis?.asr?.provider || null,
 asr_model: analysis?.asr?.model || null,
 pronunciation_provider: analysis?.pronunciation?.provider || null,
 pronunciation_model: analysis?.pronunciation?.model || null,
 alignment_provider: analysis?.forced_alignment?.provider || null,
 gop_provider: analysis?.gop?.provider || null,
 word_alignment_count: Array.isArray(analysis?.forced_alignment?.word_alignments)? analysis.forced_alignment.word_alignments.length: Number(analysis?.forced_alignment?.word_alignment_count || 0),
 phoneme_alignment_count: Array.isArray(analysis?.phoneme_alignment?.phoneme_alignments)? analysis.phoneme_alignment.phoneme_alignments.length: Number(analysis?.phoneme_alignment?.phoneme_alignment_count || 0),
 measured_components: localSpeechMeasuredComponents(analysis),
 limitations: Array.isArray(analysis?.limitations)? analysis.limitations.slice(0, 4): []
 };
 }

 function aggregateLocalSpeechAnalysis(chunks, latestAnalysis) {
 const scores = chunks.map(chunk => Number(chunk.score)).filter(score => Number.isFinite(score));
 const averageScore = scores.length? Math.round(scores.reduce((sum, score) => sum + score, 0) / scores.length): null;
 const reliabilityScores = chunks.map(chunk => Number(chunk.reliability_score)).filter(score => Number.isFinite(score));
 const reliabilityScore = reliabilityScores.length? Math.round(reliabilityScores.reduce((sum, score) => sum + score, 0) / reliabilityScores.length): Number(latestAnalysis?.reliability?.score || 0);
 const measuredComponents = [...new Set(chunks.flatMap(chunk => Array.isArray(chunk.measured_components)? chunk.measured_components: []))];
 const limitations = [...new Set([...chunks.flatMap(chunk => Array.isArray(chunk.limitations)? chunk.limitations: []),
 'Aggregated from server transcription chunks; the browser does not retain full-answer raw audio for later reanalysis.'
 ].filter(Boolean))].slice(0, 8);

 return {
 version: 1,
 status: averageScore!== null? 'partial': String(latestAnalysis?.status || 'not_measured'),
 source: 'server_transcription_chunk_aggregation',
 pronunciation: {
 status: averageScore!== null? 'partial': String(latestAnalysis?.pronunciation?.status || 'not_measured'),
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
 recommendations: Array.isArray(latestAnalysis?.recommendations)? latestAnalysis.recommendations.slice(0, 5): []
 };
 }

 function recordLocalSpeechAnalysis(questionIndex, analysis) {
 if (!analysis || typeof analysis!== 'object') return;
 const useful = ['measured', 'partial'].includes(String(analysis.status || ''))
 || Number.isFinite(localSpeechScoreFrom(analysis));
 if (!useful ||!answersData[questionIndex]) return;

 const existing = Array.isArray(answersData[questionIndex].pronunciation_analysis?.chunks)? answersData[questionIndex].pronunciation_analysis.chunks.slice(-5): [];
 existing.push(localSpeechChunkSummary(analysis));
 answersData[questionIndex].pronunciation_analysis = aggregateLocalSpeechAnalysis(existing.slice(-6), analysis);
 }

 function startServerTranscriptionEngine() {
 if (!canUseServerTranscription() ||!serverTranscriptionStream ||!serverTranscriptionStream.active) {
 setTranscriptionStatus(transcriptionUnavailableMessage(), '#f87171');
 return false;
 }

 try {
 const options = serverTranscriptionMimeType? { mimeType: serverTranscriptionMimeType }: undefined;
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
 setTranscriptionStatus('Listening - live transcript is updating');
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
 if (recorder.state!== 'inactive') {
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
 if (!isRecording ||!canUseServerTranscription()) return false;

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
 let browserNoSpeechErrorCount = 0;
 if (BrowserSpeechRecognition) {
 recognition = new BrowserSpeechRecognition();
 recognition.continuous =!mobileSpeechSurface;
 recognition.interimResults = true;
 recognition.lang = speechLocale;
 recognition.maxAlternatives = 3;

 recognition.onstart = function() {
 recognitionActive = true;
 setTranscriptionStatus('Listening - live transcript is updating');
 };
 
 recognition.onsoundstart = function() {
 browserNoSpeechErrorCount = 0;
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
 let heardSpeech = false;

 for (let i = event.resultIndex; i < event.results.length; ++i) {
 const transcript = bestSpeechAlternative(event.results[i]);
 if (!transcript) continue;
 heardSpeech = true;

 if (event.results[i].isFinal) {
 if (commitSpeechSegment(transcript)) {
 recordFillerEvents(transcript);
 }
 } else {
 interimParts.push(transcript);
 }
 }

 if (heardSpeech) {
 browserNoSpeechErrorCount = 0;
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
 browserNoSpeechErrorCount++;
 if (browserNoSpeechErrorCount >= 2 && canUseServerTranscription()) {
 setTimeout(() => activateServerTranscriptionFallback('Browser captions did not hear speech - using server transcription'), 0);
 return;
 }
 shouldAutoRestartRecognition = true;
 setTranscriptionStatus('Still listening - distant speech is quiet', '#fbbf24');
 }
 };

 recognition.onend = function() {
 recognitionActive = false;
 if (shouldAutoRestartRecognition && isRecording && activeTranscriptionEngine === 'browser') {
 setTimeout(startSpeechRecognitionEngine, mobileSpeechSurface? 650: 250);
 }
 };
 }

 function markCameraUnavailable(reason) {
 const allowedReasons = ['permission_denied', 'device_unavailable', 'browser_unsupported', 'model_unavailable', 'camera_error'];
 cameraUnavailableReason = allowedReasons.includes(reason)? reason: 'camera_error';
 if (cameraDetectionEnabled) {
 answersData.forEach(answerState => {
 answerState.observation_data = answerState.observation_data || { filler_events: [], camera_samples: [] };
 answerState.observation_data.camera_unavailable_reason = cameraUnavailableReason;
 });
 }
 const faceStatus = document.getElementById('stEyeContact');
 const alignmentStatus = document.getElementById('stPosture');
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
 navigator.mediaDevices.getUserMedia({ video: true }).then(function(stream) {
 let video = document.getElementById('userCamera');
 if (video) {
 video.srcObject = stream;
 video.play();
 }
 let mobileVideo = document.getElementById('userCameraMobile');
 if (mobileVideo) {
 mobileVideo.srcObject = stream;
 mobileVideo.play();
 mobileVideo.closest('.mobile-camera-pip')?.classList.add('camera-ready');
 }
 }).catch(function(err) {
 console.error("Error accessing camera: ", err);
 const reason = err && err.name === 'NotAllowedError'? 'permission_denied': (err && err.name === 'NotFoundError'? 'device_unavailable': 'camera_error');
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
 if (!landmark ||!Number.isFinite(Number(landmark.x)) ||!Number.isFinite(Number(landmark.y))) {
 return false;
 }
 const visibility = Number(landmark.visibility?? landmark.presence?? 1);
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
 if (!left ||!right) return null;
 return Math.hypot(Number(left.x) - Number(right.x), Number(left.y) - Number(right.y));
 }

 function detectVideoFrame(landmarker, video, timestamp) {
 if (!landmarker || typeof landmarker.detectForVideo!== 'function') return null;
 try {
 return landmarker.detectForVideo(video, timestamp);
 } catch (error) {
 return landmarker.detectForVideo(video);
 }
 }

 async function trackBodyLanguageDetection() {
 const bodyLanguageState = window.bodyLanguageModelState || {};
 const canUseBodyModels = Boolean(bodyLanguageState.ready && bodyLanguageState.poseLandmarker);
 const canUseFaceModel = typeof faceapi!== 'undefined';
 if (!cameraDetectionEnabled || cameraTrackingInFlight || (!canUseBodyModels &&!canUseFaceModel)) return;
 const video = document.getElementById('userCamera') || document.getElementById('userCameraMobile');
 if (!video ||!video.srcObject) return;
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
 if (canUseBodyModels) {
 try {
 const timestamp = performance.now();
 const poseResult = detectVideoFrame(bodyLanguageState.poseLandmarker, video, timestamp);
 poseLandmarks = Array.isArray(poseResult?.landmarks) && poseResult.landmarks.length > 0? poseResult.landmarks[0]: null;
 } catch (bodyError) {
 console.error("Body-language tracking error", bodyError);
 }
 }

 const state = answersData[trackedQuestionIndex] || defaultAnswerState();
 state.observation_data = state.observation_data || { filler_events: [], camera_samples: [] };
 state.observation_data.camera_samples = Array.isArray(state.observation_data.camera_samples)? state.observation_data.camera_samples: [];
 let cameraFacing = false;
 let centered = false;
 let poseCameraFacing = null;
 let poseDetected = Array.isArray(poseLandmarks) && poseLandmarks.length > 0;
 let shouldersVisible = false;
 let shouldersLevel = null;
 let uprightPosture = null;
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
 setCameraStat('stPosture', cameraFacing? 'Camera-facing estimate': 'Head turned estimate', cameraFacing? 'text-success': 'text-warning');
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
 const shoulderMidpoint = shouldersVisible? centerOfNormalized([leftShoulder, rightShoulder]): null;
 const hipMidpoint = hipsVisible? centerOfNormalized([leftHip, rightHip]): null;
 const shoulderWidth = shouldersVisible? Math.max(0.01, pointDistance(leftShoulder, rightShoulder)?? 0.01): 0.01;

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

 const previousPoints = cameraMovementBaselines[trackedQuestionIndex] || null;
 if (previousPoints && Object.keys(movementPoints).length > 0) {
 const distances = Object.entries(movementPoints).map(([key, point]) => pointDistance(point, previousPoints[key])).filter(distance => Number.isFinite(distance));
 if (distances.length > 0) {
 const averageDistance = distances.reduce((total, distance) => total + distance, 0) / distances.length;
 movementScore = Math.min(100, Math.round(averageDistance * 650));
 highMovement = movementScore >= 45;
 }
 }
 cameraMovementBaselines[trackedQuestionIndex] = movementPoints;

 const faceDetected = Boolean(detection || (poseDetected && visibleLandmark(poseLandmarks[0])));
 cameraFacing = Boolean(detection? cameraFacing: poseCameraFacing);
 if (!detection && poseDetected && shouldersVisible) {
 const shoulderCenter = movementPoints.shoulders;
 centered = Boolean(shoulderCenter && Math.abs(shoulderCenter.x - 0.5) <= 0.24 && Math.abs(shoulderCenter.y - 0.5) <= 0.32);
 }

 if (!detection) {
 setCameraStat(
 'stEyeContact',
 faceDetected? '<i class="fa-solid fa-check me-1"></i>Visible': 'Outside frame / unavailable',
 faceDetected? 'text-success': 'text-warning',
 faceDetected
 );
 setCameraStat(
 'stPosture',
 faceDetected? (cameraFacing? 'Camera-facing estimate': 'Head turned estimate'): 'Excluded from scoring',
 faceDetected? (cameraFacing? 'text-success': 'text-warning'): 'text-secondary'
 );
 }

 setCameraStat(
 'stPose',
 shouldersVisible? (shouldersLevel && uprightPosture!== false? 'Balanced upper body': 'Posture cue available'): (poseDetected? 'Partial pose estimate': 'Pose not detected'),
 shouldersVisible? (shouldersLevel && uprightPosture!== false? 'text-success': 'text-warning'): 'text-secondary'
 );
 setCameraStat(
 'stMovement',
 movementScore === null? 'Calibrating': (highMovement? 'Higher movement': 'Steady'),
 movementScore === null? 'text-secondary': (highMovement? 'text-warning': 'text-success')
 );

 const detectionStatus = document.getElementById('cameraDetectionStatus');
 if (detectionStatus) {
 detectionStatus.innerHTML = canUseBodyModels? '<i class="fa-solid fa-person-rays me-1"></i>Pose estimate': '<i class="fa-solid fa-laptop me-1"></i>Framing estimate';
 detectionStatus.style.color = canUseBodyModels? '#34d399': '#cbd5e1';
 }

 state.observation_data.camera_samples.push({
 at_seconds: Math.max(0, Number(state.voice_duration || recTimerSeconds || 0)),
 face_detected: faceDetected,
 camera_facing: Boolean(faceDetected && cameraFacing),
 centered: Boolean(faceDetected && centered),
 pose_detected: poseDetected,
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
 let preferredVoice = null;
 let autoStartAfterQuestionTimer = null;
 let questionSpeechToken = 0;
 let activeQuestionAudio = null;
 let captionInterval = null;
 let activeSpeechCompletion = null;
 let serverVoiceUnavailable =!serverAiVoiceEnabled;
 const serverSpeechUrlCache = new Map();

 function isVoiceTranscriptionMode() {
 return canonicalResponseMode === 'voice' || canonicalResponseMode === 'hybrid';
 }

 function isVoiceOnlyMode() {
 return canonicalResponseMode === 'voice';
 }

 function isHybridTranscriptionMode() {
 return canonicalResponseMode === 'hybrid';
 }

 function responseModePlaceholder() {
 if (canonicalResponseMode === 'text') {
 return 'Type your answer using your own local school, work, internship, or project evidence...';
 }

 if (canonicalResponseMode === 'voice') {
 return '';
 }

 return '';
 }

 function answerTextareaElement() {
 return document.getElementById('answerTextarea');
 }

 function updateAnswerTranscriptionOverlay() {
 const textarea = answerTextareaElement();
 const stage = document.querySelector('.answer-transcript-stage');
 const overlay = document.getElementById('answerTranscriptionOverlay');
 const hasText = textarea? String(textarea.value || '').trim() !== '': String(answersData[currentQIdx]?.text || '').trim() !== '';
 const shouldShow = isHybridTranscriptionMode()
 && !hasText
 && !isRecordingPaused
 && Boolean(isRecording || recordingStartPromise);

 if (stage) stage.classList.toggle('is-recording-empty-transcript', shouldShow);
 if (overlay) overlay.hidden =!shouldShow;
 if (textarea) {
 textarea.classList.toggle('has-transcription-overlay', shouldShow);
 textarea.placeholder = shouldShow? '': responseModePlaceholder();
 }
 if (shouldShow) {
 startAnswerTranscriptionVisualizer(voiceSessionStream);
 } else {
 stopAnswerTranscriptionVisualizer();
 }
 }

 function currentAnswerTextareaText() {
 const textarea = answerTextareaElement();
 return textarea? String(textarea.value || ''): String(answersData[currentQIdx]?.text || '');
 }

 function setCurrentAnswerTextareaText(value) {
 const text = String(value || '');
 const textarea = answerTextareaElement();
 if (textarea) textarea.value = text;
 if (answersData[currentQIdx]) {
 answersData[currentQIdx].text = text;
 if (isHybridTranscriptionMode()) {
 answersData[currentQIdx].speech_transcript = cleanTranscriptText(text);
 }
 }
 updateAnswerTranscriptionOverlay();
 return text;
 }

 function focusAnswerTextarea() {
 answerTextareaElement()?.focus();
 }

 function hybridModeHasTranscriptText() {
 return currentAnswerTextareaText().trim() !== '';
 }

 function updateSendAnswerButtonState() {
 const hybridNeedsTranscript = isHybridTranscriptionMode() &&!hybridModeHasTranscriptText();
 const disabled =!answerInputEnabled || hybridNeedsTranscript || isSubmittingAnswer || interviewEnding || interviewTerminated || finalAnswerSubmitted;
 const title = hybridNeedsTranscript && answerInputEnabled
 ? 'Add transcript text before sending in Hybrid Mode'
 : (answerInputEnabled? 'Send answer': 'Start the interview to answer');

 document.querySelectorAll('.send-answer-btn').forEach(button => {
 button.disabled = disabled;
 button.setAttribute('aria-disabled', String(disabled));
 button.classList.toggle('is-awaiting-transcript', hybridNeedsTranscript && answerInputEnabled);
 button.setAttribute('title', title);
 });
 }

 function applyVoiceOnlyAnswerLock() {
 const textarea = answerTextareaElement();
 const lockNotice = document.getElementById('responseModeLockNotice');
 const locked = isVoiceOnlyMode();

 if (textarea) {
 textarea.readOnly = locked;
 textarea.classList.toggle('voice-only-answer-lock', locked);
 textarea.setAttribute('aria-readonly', String(locked));
 }

 if (lockNotice) {
 lockNotice.hidden =!locked;
 }

 updateSendAnswerButtonState();
 }

 function applyResponseModeUi() {
 const voiceControls = document.getElementById('voiceControls');
 const recordingTimer = document.getElementById('recordingTimer');
 const transcriptControls = document.getElementById('answerTranscriptControls');
 const textarea = answerTextareaElement();

 if (textarea) {
 textarea.placeholder = responseModePlaceholder();
 }
 applyVoiceOnlyAnswerLock();
 updateAnswerTranscriptionOverlay();

 if (!isVoiceTranscriptionMode()) {
 if (transcriptControls) transcriptControls.hidden = true;
 if (voiceControls) voiceControls.style.display = 'none';
 if (recordingTimer) {
 recordingTimer.style.display = 'none';
 recordingTimer.innerText = '00:00';
 }
 activeTranscriptionEngine = null;
 setVoiceControlsEnabled(false, 'Voice recording is disabled in Text Mode');
 setTranscriptionStatus('');
 renderVoiceSessionPanel();
 updateAnswerTranscriptionOverlay();
 return;
 }

 if (transcriptControls) transcriptControls.hidden =!interviewStarted;
 if (voiceControls && interviewStarted) voiceControls.style.display = 'flex';
 if (recordingTimer) recordingTimer.style.display = 'block';
 setRecordingControlButtons(isRecording? 'recording': (isRecordingPaused? 'paused': 'idle'));
 if (isVoiceOnlyMode()) {
 activeTranscriptionEngine = null;
 setTranscriptionStatus('Voice-only mode. Text transcription is off.');
 }
 renderVoiceSessionPanel();
 updateAnswerTranscriptionOverlay();
 }

 function managedFetch(url, options = {}) {
 const controller = new AbortController();
 const timeoutMs = Number(options.timeoutMs || 0);
 const fetchOptions = {...options };
 delete fetchOptions.timeoutMs;
 const timeout = timeoutMs > 0? setTimeout(() => controller.abort(), Math.max(1000, timeoutMs)): null;

 pendingFetchControllers.add(controller);

 return fetch(url, {
 credentials: 'same-origin',...fetchOptions,
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
 data = parsed && typeof parsed === 'object'? parsed: {};
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
 if (!errors || typeof errors!== 'object') return '';
 const first = Object.values(errors).flat().find(Boolean);
 return first? String(first): '';
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
 return plainText? plainText.slice(0, 220): fallbackMessage;
 }

 function isRetryableRequestError(error) {
 if (!error) return false;
 if (error.name === 'AbortError') return false;
 if (!navigator.onLine) return true;
 return!error.status || [408, 425, 429, 500, 502, 503, 504].includes(Number(error.status));
 }

 async function postFormJson(url, formData, fallbackMessage = 'The request could not be completed.', timeoutMs = 60000) {
 const response = await managedFetch(url, {
 method: 'POST',
 body: formData,
 headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
 timeoutMs
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
 const timeoutMs = Math.max(10000, Number(options.timeoutMs || 60000));
 let lastError = null;

 for (let attempt = 1; attempt <= attempts; attempt++) {
 try {
 return await postFormJson(url, formData, fallbackMessage, timeoutMs);
 } catch (error) {
 lastError = error;
 if (attempt >= attempts ||!isRetryableRequestError(error)) {
 throw error;
 }

 setTranscriptionStatus('Connection hiccup - retrying request', '#fbbf24');
 await waitForRequestRetry(650 * attempt);
 }
 }

 throw lastError || new Error(fallbackMessage);
 }

 function setAiCoachButtonExpanded(expanded) {
 const button = document.getElementById('aiCoachHeadButton');
 if (!button) return;
 button.setAttribute('aria-expanded', expanded? 'true': 'false');
 button.classList.toggle('is-active', expanded);
 }

 function protectAiCoachPossibleAnswer() {
 const text = document.getElementById('aiCoachAnswerText');
 if (!text || text.dataset.copyGuardBound === '1') return;
 text.dataset.copyGuardBound = '1';
 const block = event => {
 event.preventDefault();
 event.stopPropagation();
 if (event.clipboardData) {
 event.clipboardData.setData('text/plain', '');
 }
 };
 const nodeInsideAnswer = node => {
 if (!node ||!text) return false;
 const element = node.nodeType === Node.ELEMENT_NODE? node: node.parentElement;
 return element === text || text.contains(element);
 };
 ['copy', 'cut', 'contextmenu', 'dragstart', 'selectstart'].forEach(eventName => {
 text.addEventListener(eventName, block);
 });
 document.addEventListener('copy', event => {
 const selection = window.getSelection();
 if (selection && (nodeInsideAnswer(selection.anchorNode) || nodeInsideAnswer(selection.focusNode))) {
 block(event);
 }
 });
 document.addEventListener('cut', event => {
 const selection = window.getSelection();
 if (selection && (nodeInsideAnswer(selection.anchorNode) || nodeInsideAnswer(selection.focusNode))) {
 block(event);
 }
 });
 }

 function setAiCoachPanelState(state, message = '') {
 const panel = document.getElementById('aiCoachPanel');
 const status = document.getElementById('aiCoachStatus');
 const text = document.getElementById('aiCoachAnswerText');
 const button = document.getElementById('aiCoachHeadButton');
 if (panel) panel.dataset.state = state;
 if (status) {
 status.textContent = state === 'loading'? 'Generating possible answer': (state === 'error'? 'Coach unavailable': 'Possible answer');
 }
 if (text && message) text.textContent = message;
 if (button) {
 button.disabled = state === 'loading';
 button.classList.toggle('is-loading', state === 'loading');
 }
 }

 function resetAiCoachPanel() {
 aiCoachCurrentAnswer = '';
 aiCoachCurrentQuestionId = null;
 const panel = document.getElementById('aiCoachPanel');
 const text = document.getElementById('aiCoachAnswerText');
 if (text) text.textContent = '';
 if (panel) {
 panel.hidden = true;
 panel.dataset.state = 'idle';
 panel.dataset.questionId = '';
 }
 setAiCoachButtonExpanded(false);
 }

 function closeAiCoachPanel() {
 const panel = document.getElementById('aiCoachPanel');
 if (panel) panel.hidden = true;
 setAiCoachButtonExpanded(false);
 }

 function toggleAiCoachPanel() {
 if (liveFeedbackMode === 'real_interview') {
 showSessionNotice('AI Coach is available only when Coaching On is selected.', 'warning');
 return;
 }

 const panel = document.getElementById('aiCoachPanel');
 if (!panel) return;
 if (!panel.hidden) {
 closeAiCoachPanel();
 return;
 }

 openAiCoachPanel();
 }

 function openAiCoachPanel() {
 const panel = document.getElementById('aiCoachPanel');
 if (!panel) return;
 const question = questions[currentQIdx] || null;
 const questionId = question && question.id? String(question.id): '';
 if (!questionId) {
 showSessionNotice('AI Coach needs an active question first.', 'warning');
 return;
 }

 panel.hidden = false;
 setAiCoachButtonExpanded(true);

 if (aiCoachCurrentAnswer && String(aiCoachCurrentQuestionId) === questionId) {
 setAiCoachPanelState('ready');
 return;
 }

 generateAiCoachAnswer(question);
 }

 async function generateAiCoachAnswer(question = null) {
 if (aiCoachRequestInFlight) return;
 const activeQuestion = question || questions[currentQIdx] || null;
 const questionId = activeQuestion && activeQuestion.id? String(activeQuestion.id): '';
 if (!questionId) return;

 aiCoachRequestInFlight = true;
 aiCoachCurrentAnswer = '';
 aiCoachCurrentQuestionId = questionId;
 setAiCoachPanelState('loading', 'Generating a possible answer...');

 const formData = new FormData();
 formData.append('_token', '{{ csrf_token() }}');
 formData.append('session_id', interviewSessionId);
 formData.append('question_id', questionId);

 try {
 const data = await postFormJson('{{ route("interview.coachAnswer") }}', formData, 'AI Coach could not generate a possible answer right now.', 45000);
 const answer = String(data.possible_answer || '').trim();
 if (!answer) {
 throw new Error('AI Coach could not generate a possible answer right now.');
 }

 const panel = document.getElementById('aiCoachPanel');
 const text = document.getElementById('aiCoachAnswerText');
 aiCoachCurrentAnswer = answer;
 aiCoachCurrentQuestionId = String(data.question_id || questionId);
 if (panel) panel.dataset.questionId = aiCoachCurrentQuestionId;
 if (text) text.textContent = answer;
 setAiCoachPanelState('ready');
 } catch (error) {
 aiCoachCurrentAnswer = '';
 setAiCoachPanelState('error', error.message || 'AI Coach could not generate a possible answer right now.');
 showSessionNotice(error.message || 'AI Coach could not generate a possible answer right now.', 'warning');
 } finally {
 aiCoachRequestInFlight = false;
 const button = document.getElementById('aiCoachHeadButton');
 if (button) {
 button.disabled = false;
 button.classList.remove('is-loading');
 }
 }
 }

 function abortManagedFetches() {
 pendingFetchControllers.forEach(controller => controller.abort());
 pendingFetchControllers.clear();
 }

 function scheduleAutoTranscriptionStart(token) {
 if (token!== questionSpeechToken) return;
 clearTimeout(autoStartAfterQuestionTimer);
 if (!isVoiceTranscriptionMode() || interviewEnding || interviewTerminated) return;

 autoStartAfterQuestionTimer = setTimeout(() => {
 if (token!== questionSpeechToken || isRecording || interviewEnding || interviewTerminated) return;
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

 function voiceLooksFemale(voice) {
 const name = String(voice.name || '').toLowerCase();
 return /\b(female|woman|girl|zira|aria|jenny|natasha|sonia|libby|hazel|susan|samantha|karen|moira|tessa|victoria|fiona|ava|allison|shelley|shelly|joanna|salli|kimberly|kendra|ivy|emma|amy|nicole|olivia|serena|veena|linda|melina|carmit|paulina|monica|marisol|lucia|maria|lupe|paloma|google us english|google uk english female)\b/i.test(name);
 }

 function voiceLooksMale(voice) {
 const name = String(voice.name || '').toLowerCase();
 return /\b(male|man|boy|david|mark|george|daniel|alex|fred|tom|ralph|bruce|arthur|albert|jorge|diego|carlos|miguel|juan|paul|ryan|liam|brian|guy|aaron|eric|nathan|christopher|jacob|justin|matthew|joey|onyx|echo)\b/i.test(name);
 }

 // Initialize preferred voice
 function loadVoices() {
 let voices = window.speechSynthesis.getVoices();
 if (voices.length > 0) {
 const languagePriority = speechLocalePriority();
 preferredVoice = languagePriority.map(language =>
 voices.find(v => voiceMatchesLanguage(v, language) && voiceLooksFemale(v) && voiceLooksNatural(v))
 || voices.find(v => voiceMatchesLanguage(v, language) && voiceLooksFemale(v))
 || voices.find(v => voiceMatchesLanguage(v, language) && voiceLooksNatural(v) &&!voiceLooksMale(v))
 || voices.find(v => voiceMatchesLanguage(v, language) &&!voiceLooksMale(v))
 ).find(Boolean)
 || voices.find(v => voiceLooksFemale(v) && voiceLooksNatural(v))
 || voices.find(v => voiceLooksFemale(v))
 || voices.find(v =>!voiceLooksMale(v))
 || voices[0];
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

 while ((match = pattern.exec(String(text || '')))!== null) {
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
 span.className = 'question-caption-word' + (isActiveWord? ' active': '');
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

 function renderStaticQuestionCaption(text) {
 const caption = document.getElementById('questionCaptionText');
 if (!caption) return;

 const cleanText = String(text || '').trim();
 if (!cleanText) {
 clearQuestionCaption();
 return;
 }

 caption.innerHTML = '';
 caption.textContent = cleanText;
 caption.classList.remove('is-speaking');
 caption.classList.add('has-caption', 'is-static');
 caption.style.setProperty('color', '#ffffff', 'important');
 caption.style.setProperty('-webkit-text-fill-color', '#ffffff', 'important');
 caption.style.setProperty('opacity', '1', 'important');
 caption.style.setProperty('visibility', 'visible', 'important');
 caption.style.setProperty('text-shadow', '0 2px 6px rgba(0, 0, 0, 0.98), 0 0 10px rgba(0, 0, 0, 0.72)', 'important');
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
 if (!activeSpeechCompletion || activeSpeechCompletion.token!== token) return;

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

 function startSpeakingUi(text, boundaryAware = false) {
 clearCaptionInterval();
 document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'block');
 document.getElementById('aiAvatarHead')?.style.setProperty('--avatar-ring-color', '#34d399');
 document.getElementById('aiQuestionText').innerText = text;

 const words = captionWordsFor(text);
 let currentWordIdx = words.length? 0: -1;
 let boundaryFired = false;
 renderQuestionCaption(words, currentWordIdx);

 captionInterval = setInterval(() => {
 if (boundaryAware && boundaryFired) return;

 if (currentWordIdx < words.length - 1) {
 currentWordIdx++;
 renderQuestionCaption(words, currentWordIdx);
 currentAmplitude = 1.0;
 } else {
 clearCaptionInterval();
 }
 }, 350);

 if (visualizerInterval) clearInterval(visualizerInterval);
 const bars = document.querySelectorAll('.spectrum-bar');
 visualizerInterval = setInterval(() => {
 currentAmplitude = Math.max(0.15, currentAmplitude - 0.1);
 bars.forEach(bar => {
 let h = 8 + (Math.random() * 24 * currentAmplitude);
 bar.style.height = h + 'px';
 });
 }, 50);

 return {
 markBoundary: (charIndex = null) => {
 boundaryFired = true;
 clearCaptionInterval();
 currentWordIdx = charIndex === null? Math.min(currentWordIdx + 1, words.length - 1): wordIndexFromChar(words, charIndex);
 renderQuestionCaption(words, currentWordIdx);
 },
 };
 }

 function finishSpeakingUi(text, token, startTimerAfterSpeech) {
 if (token!== questionSpeechToken) return;

 document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'none');
 document.getElementById('aiAvatarHead')?.style.setProperty('--avatar-ring-color', '#8b5cf6');
 if (visualizerInterval) clearInterval(visualizerInterval);
 visualizerInterval = null;
 clearCaptionInterval();
 renderStaticQuestionCaption(text);
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
 if (visualizerInterval) clearInterval(visualizerInterval);
 visualizerInterval = null;
 document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'none');
 const avatarHead = document.getElementById('aiAvatarHead');
 if (avatarHead) avatarHead.style.borderColor = '#8b5cf6';
 }

 async function serverSpeechUrl(questionId, speechText = '') {
 const cleanSpeechText = cleanTranscriptText(speechText);
 if ((!questionId &&!cleanSpeechText) || serverVoiceUnavailable) return null;

 const cacheKey = questionId? `q:${questionId}`: `text:${cleanSpeechText}`;
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
 if (!window.Audio || (!questionId &&!speechText) || serverVoiceUnavailable || interviewTerminated) {
 return false;
 }

 try {
 const url = await serverSpeechUrl(questionId, speechText);
 if (!url || token!== questionSpeechToken || interviewTerminated) return false;

 const audio = new Audio(url);
 activeQuestionAudio = audio;

 audio.addEventListener('play', () => {
 if (token!== questionSpeechToken) return;
 startSpeakingUi(text);
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
 renderStaticQuestionCaption(text);
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

 const usedServerVoice = serverAiVoiceEnabled? await speakWithServerVoice(text, token, startTimerAfterSpeech, options.questionId, options.speechText || ''): false;
 if (usedServerVoice || token!== questionSpeechToken || interviewTerminated) return completion;

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
 if (!chip ||!timer) return;

 const elapsed = getQuestionElapsedSeconds();
 questionElapsedSeconds = elapsed;

 if (perQuestionLimitSeconds <= 0) {
 timer.innerText = 'Self-paced';
 chip.className = 'session-chip';
 return;
 }

 const remaining = perQuestionLimitSeconds - elapsed;
 timer.innerText = formatSeconds(remaining);
 chip.className = remaining <= 15? 'session-chip danger': (remaining <= 30? 'session-chip warning': 'session-chip');

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
 const text = currentAnswerTextareaText();
 answersData[currentQIdx].transcript_timeline = answersData[currentQIdx].transcript_timeline || [];
 answersData[currentQIdx].transcript_timeline.push({
 at: elapsed,
 event: eventName,
 words: text.trim().split(/\s+/).filter(Boolean).length,
 chars: text.length,...extra
 });
 }

 function handleAnswerPaste(event) {
 if (!answersData[currentQIdx]) return;

 const clipboard = event.clipboardData || window.clipboardData;
 const pastedText = clipboard? (clipboard.getData('text') || clipboard.getData('Text') || ''): '';
 const pastedChars = pastedText.length;

 answersData[currentQIdx].paste_event_count = (answersData[currentQIdx].paste_event_count || 0) + 1;
 answersData[currentQIdx].pasted_character_count = (answersData[currentQIdx].pasted_character_count || 0) + pastedChars;

 setTimeout(() => {
 captureTranscriptTimeline(pastedChars >= 80? 'large_paste': 'paste', true, {
 pasted_chars: pastedChars
 });
 triggerAnalysis();
 }, 0);
 }

 function handleQuestionTimeout() {
 clearInterval(questionTimerInterval);
 if (!answerInputEnabled || isSubmittingAnswer || interviewEnding || interviewTerminated || finalAnswerSubmitted) return;
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
 return remaining > 0? pauseFor(remaining): Promise.resolve();
 }

 function isOpeningQuestion(question) {
 return question && question.source_type === 'real_interview_opening';
 }

 function hasOpeningQuestion() {
 return questions.length > 0 && isOpeningQuestion(questions[0]);
 }

 function questionDisplayNumber(idx) {
 return hasOpeningQuestion()? idx: idx + 1;
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
 if (candidate.length > 1 &&!blockedNames.has(candidate.toLowerCase())) {
 return candidate.charAt(0).toUpperCase() + candidate.slice(1).toLowerCase();
 }
 }
 }

 return '';
 }

 function closingConversationText() {
 return `Thank you for walking me through your answers today. This ${sessionTargetPosition} interview is now complete, and your responses are being analyzed for feedback.`;
 }

 function setAnswerInputEnabled(enabled) {
 answerInputEnabled = Boolean(enabled);
 const textarea = answerTextareaElement();
 if (textarea) {
 textarea.disabled =!enabled;
 applyVoiceOnlyAnswerLock();
 }
 updateSendAnswerButtonState();
 updateAnswerTranscriptionOverlay();
 }

 function showInterviewerConversation(text, counterText = null) {
 const qText = document.getElementById('aiQuestionText');
 if (qText) qText.innerText = text;
 renderStaticQuestionCaption(text);
 setRepeatPrompt(text, {
 phase: counterText === 'Done'? 'closing': 'conversation',
 speechText: text
 });

 const qCounter = document.getElementById('qCounter');
 if (qCounter && counterText) qCounter.innerText = counterText;

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

 function enterMobileFullscreen(options = {}) {
 const isMobileShell = document.body.classList.contains('user-mobile-shell')
 || window.matchMedia('(max-width: 768px)').matches;
 if (!isMobileShell) return;

 document.body.classList.add('mobile-interview-fullscreen');
 window.SpeakReadyViewport?.refresh?.();
 updateMobileFullscreenToggle();

 if (options.requestBrowser === false) return;

 const root = document.documentElement;
 if (!document.fullscreenElement && root.requestFullscreen) {
 root.requestFullscreen({ navigationUI: 'hide' }).catch(() => {
 updateMobileFullscreenToggle();
 });
 }
 }

 function exitMobileFullscreen() {
 document.body.classList.remove('mobile-interview-fullscreen');
 window.SpeakReadyViewport?.refresh?.();
 updateMobileFullscreenToggle();

 if (document.fullscreenElement && document.exitFullscreen) {
 document.exitFullscreen().catch(() => {
 updateMobileFullscreenToggle();
 });
 }
 }

 function toggleMobileFullscreen() {
 if (document.body.classList.contains('mobile-interview-fullscreen')) {
 exitMobileFullscreen();
 } else {
 enterMobileFullscreen();
 }
 }

 function updateMobileFullscreenToggle() {
 const toggle = document.getElementById('responseFullscreenToggle');
 if (!toggle) return;

 const fullscreenOn = document.body.classList.contains('mobile-interview-fullscreen');
 toggle.title = fullscreenOn? 'Exit fullscreen': 'Enter fullscreen';
 toggle.setAttribute('aria-label', toggle.title);
 toggle.innerHTML = fullscreenOn? '<i class="fa-solid fa-compress"></i>': '<i class="fa-solid fa-expand"></i>';
 }

 function handleBrowserFullscreenChange() {
 if (!document.fullscreenElement && interviewStarted) {
 document.body.classList.remove('mobile-interview-fullscreen');
 }

 window.SpeakReadyViewport?.refresh?.();
 updateMobileFullscreenToggle();
 }

 window.exitMobileFullscreen = exitMobileFullscreen;
 window.toggleMobileFullscreen = toggleMobileFullscreen;
 const setupAutoFullscreenPreferenceKey = 'speakready.interview.autoFullscreen';

 function consumeSetupAutoFullscreenPreference() {
 try {
 const requested = window.sessionStorage.getItem(setupAutoFullscreenPreferenceKey) === '1';
 window.sessionStorage.removeItem(setupAutoFullscreenPreferenceKey);
 return requested;
 } catch (error) {
 console.warn('Unable to read interview fullscreen preference:', error);
 return false;
 }
 }

 function startInterviewSession() {
 if (interviewStarted || interviewTerminated) return;
 
 interviewStarted = true;
 document.getElementById('workspaceWrapper').style.display = 'block';
 document.getElementById('workspaceWrapper').classList.toggle('real-interview-mode', liveFeedbackMode === 'real_interview');
 document.getElementById('interviewControls').style.opacity = '1';
 document.getElementById('interviewControls').style.pointerEvents = 'auto';
 enterMobileFullscreen();
 
 if (cameraDetectionEnabled) initCamera();
 
 if(isVoiceTranscriptionMode()) {
 applyResponseModeUi();
 const recorderUnavailableMessage = voiceRecordingUnavailableMessage();
 const transcriptionEngine = isVoiceOnlyMode() || recorderUnavailableMessage? null: preferredTranscriptionEngine();
 if (recorderUnavailableMessage || (!isVoiceOnlyMode() &&!transcriptionEngine)) {
 const message = recorderUnavailableMessage || transcriptionUnavailableMessage();
 setTranscriptionStatus(message, '#f87171');
 setVoiceControlsEnabled(false, message);
 showSessionNotice(isVoiceOnlyMode()? `${message} Voice Mode needs microphone recording.`: `${message} You can type your answer instead.`, 'warning');
 } else if (transcriptionEngine === 'server') {
 setVoiceControlsEnabled(true);
 setTranscriptionStatus('Recording ready - live transcript will appear in the answer box');
 } else if (isVoiceOnlyMode()) {
 setVoiceControlsEnabled(true);
 setTranscriptionStatus('Voice-only mode. Text transcription is off.');
 } else {
 setVoiceControlsEnabled(true);
 if (isHybridTranscriptionMode()) {
 setTranscriptionStatus('Recording ready - live transcript will appear in the answer box');
 }
 }
 } else {
 applyResponseModeUi();
 }

 window.dispatchEvent(new CustomEvent('speakready:interview-session-started'));

 timerInterval = setInterval(() => {
 timerSeconds++;
 const m = Math.floor(timerSeconds / 60).toString().padStart(2, '0');
 const s = (timerSeconds % 60).toString().padStart(2, '0');
 const interviewTimer = document.getElementById('interviewTimer');
 if (interviewTimer) interviewTimer.innerText = m + ':' + s;
 
 if(timerSeconds % 30 === 0) autoSaveState(); // auto save every 30s
 }, 1000);

 const restoredChat = restoreChatHistory();
 (async () => {
 await loadQuestion(currentQIdx, { append:!restoredChat });
 })();
 
 if (!answerListenersBound) {
 answerListenersBound = true;
 const answerTextarea = document.getElementById('answerTextarea');
 if (answerTextarea) {
 answerTextarea.addEventListener('input', handleAnswerInput);
 answerTextarea.addEventListener('paste', handleAnswerPaste);
 }
 document.addEventListener('click', closeVoiceSessionMenu);
 document.addEventListener('keydown', event => {
 if (event.key === 'Escape') closeVoiceSessionMenu();
 });
 document.addEventListener('visibilitychange', () => {
 if (document.visibilityState === 'hidden') autoSaveState();
 });
 window.addEventListener('beforeunload', event => {
 if (recordingStartPromise || recordingStopPromise || isRecording || isRecordingPaused || voiceSessionRecorder || voiceSessionStopPromise || voiceSessionTranscriptPromise) {
 event.preventDefault();
 event.returnValue = '';
 }
 });
 window.addEventListener('pagehide', () => {
 if (voiceSessionTranscriptPromise) {
 abortManagedFetches();
 }
 if (voiceSessionRecorder || voiceSessionStream) {
 discardVoiceSessionRecorder();
 }
 releaseMicrophoneStream();
 });
 }
 }

 async function loadQuestion(idx, options = {}) {
 if (interviewTerminated || interviewEnding) return;
 currentQIdx = idx;
 const q = questions[idx];
 if (!q) return;
 resetAiCoachPanel();
 setAnswerInputEnabled(false);
 
 document.getElementById('aiQuestionText').innerText = '...';
 document.getElementById('qCounter').innerText = isOpeningQuestion(q)? 'Intro': Math.min(questionDisplayNumber(idx), targetQuestionCount) + '/' + targetQuestionCount;

 // Append AI question to chat log if it's the first time seeing it
 const questionDisplayKey = String(q.id || idx);
 if (options.append!== false &&!displayedQuestionIds.has(questionDisplayKey)) {
 appendChatMessage('interviewer', q.question_text);
 displayedQuestionIds.add(questionDisplayKey);
 }

 const answerState = answersData[idx] || defaultAnswerState();
 const restoredAnswerText = String(answerState.text || answerState.speech_transcript || '');
 answerState.text = restoredAnswerText;
 answersData[idx] = answerState;
 setCurrentAnswerTextareaText(restoredAnswerText);
 applyResponseModeUi();
 resetSpeechRecognitionBufferFromTextarea();
 renderVoiceSessionPanel(idx);
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

 function setRepeatPrompt(text, options = {}) {
 currentRepeatPrompt = String(text || '').trim();
 currentRepeatOptions = {
 questionId: options.questionId || null,
 phase: options.phase || 'repeat',
 speechText: options.speechText || currentRepeatPrompt
 };
 }

 function repeatQuestion() {
 const fallbackQuestion = questions && questions[currentQIdx]? questions[currentQIdx]: null;
 const repeatText = currentRepeatPrompt || fallbackQuestion?.question_text || '';
 if(repeatText) {
 speakQuestion(repeatText, {...currentRepeatOptions,
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
 if (!state ||!isVoiceTranscriptionMode()) return;
 state.observation_data = state.observation_data || { filler_events: [], camera_samples: [] };
 state.observation_data.filler_events = Array.isArray(state.observation_data.filler_events)? state.observation_data.filler_events: [];
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
 return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
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
 return String(text || '').toLowerCase().replace(/[^a-z0-9\s'-]/g, ' ').split(/\s+/).map(word => word.replace(/^'+|'+$/g, '')).filter(word => word.length > 2 &&!analysisStopWords.has(word));
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

 const ratio = questionWords.length > 0? matched / questionWords.length: 0.45;
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
 const longSentencePenalty = sentences.some(sentence => sentence.split(/\s+/).length > 40)? 8: 0;
 const casualMatches = answerText.match(unprofessionalPattern);
 const casualCount = casualMatches? casualMatches.length: 0;

 let clarity = 28 + Math.min(28, wordCount * 1.1) + (starSignals.componentCount * 8) + Math.min(8, sentenceCount * 2);
 clarity -= longSentencePenalty;
 if (wordCount > 220) clarity -= 10;
 if (wordCount < 15) clarity = Math.min(clarity, 45);

 const relevance = calculateRelevanceScore(answerText, questionText, wordCount, starSignals);

 let grammar = 55 + Math.min(20, wordCount * 0.5) + (hasEndPunctuation? 8: 0);
 grammar -= hasRepeatedWord? 8: 0;
 grammar -= longSentencePenalty;
 if (wordCount < 15) grammar = Math.min(grammar, 50);

 let professionalism = 58 + (hasFirstPersonOwnership? 10: 0) + (starSignals.hasA? 8: 0) + (starSignals.hasR? 8: 0);
 professionalism -= casualCount * 10;
 if (wordCount < 15) professionalism = Math.min(professionalism, 50);

 const starBonus = isBehavioralQuestion(questionText)? starSignals.componentCount * 3: starSignals.hasR? 5: 0;
 const readiness = (clarity * 0.25) + (relevance * 0.3) + (grammar * 0.2) + (professionalism * 0.25) + starBonus;

 return {
 clarity: clampScore(clarity),
 relevance: clampScore(relevance),
 grammar: clampScore(grammar),
 professionalism: clampScore(professionalism),
 readiness: clampScore(wordCount === 0? 0: readiness)
 };
 }

 function triggerAnalysis() {
 const text = currentAnswerTextareaText();
 const currentQuestion = questions[currentQIdx]? questions[currentQIdx].question_text: '';
 const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
 const charCount = text.length;
  
 const wordCountTarget = document.getElementById('wordCount');
 const charCountTarget = document.getElementById('charCount');
 if (wordCountTarget) wordCountTarget.innerText = wordCount + ' words';
 if (charCountTarget) charCountTarget.innerText = charCount + ' characters';

 const starSignals = detectStarSignals(text);
 
 updateStarIcon('starS', starSignals.hasS);
 updateStarIcon('starT', starSignals.hasT);
 updateStarIcon('starA', starSignals.hasA);
 updateStarIcon('starR', starSignals.hasR);

 const deliveryText = isVoiceTranscriptionMode()? String(answersData[currentQIdx]?.speech_transcript || ''): '';
 const matches = deliveryText.match(fillerPattern);
 const fillers = matches? matches.length: 0;
 const scores = calculateLiveScores(text, currentQuestion, wordCount, fillers, starSignals);
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
 answersData[currentQIdx].confidence_score = 0;
 answersData[currentQIdx].self_reported_confidence = 0;
 answersData[currentQIdx].elapsed_seconds = getQuestionElapsedSeconds();
 if (getQuestionElapsedSeconds() - lastTimelineCaptureAt >= 5) {
 captureTranscriptTimeline('input');
 }
 scheduleStateSave();
 updateSendAnswerButtonState();
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
 const isIdle = state === 'idle';
 const isPaused = state === 'paused';

 if (pauseBtn) {
 pauseBtn.style.display = 'inline-flex';
 const pauseIcon = isIdle? 'fa-microphone': (isPaused? 'fa-play': 'fa-pause');
 const pauseText = isIdle? 'Start': (isPaused? 'Resume': 'Pause');
 pauseBtn.innerHTML = `<i class="fa-solid ${pauseIcon}"></i><span class="voice-action-label">${pauseText}</span>`;
 const pauseLabel = isIdle? 'Start recording': (isPaused? 'Resume recording': 'Pause recording');
 pauseBtn.setAttribute('aria-label', pauseLabel);
 pauseBtn.setAttribute('title', pauseLabel);
 }

 if (stopBtn) {
 stopBtn.style.display = 'inline-flex';
 stopBtn.disabled = isIdle;
 stopBtn.classList.toggle('voice-control-disabled', isIdle);
 stopBtn.setAttribute('aria-label', 'Stop recording');
 stopBtn.setAttribute('title', 'Stop recording');
 stopBtn.innerHTML = '<i class="fa-solid fa-stop"></i><span class="voice-action-label">Stop</span>';
 }
 if (timer) timer.style.display = 'block';
 }

 function setVoiceControlsEnabled(enabled, reason = '') {
 ['micPauseBtn', 'micStopBtn'].forEach(id => {
 const button = document.getElementById(id);
 if (!button) return;
 button.disabled =!enabled;
 button.classList.toggle('voice-control-disabled',!enabled);
 if (!button.dataset.defaultTitle) {
 button.dataset.defaultTitle = button.getAttribute('title') || button.textContent.trim() || 'Voice recording';
 }
 const title = enabled? button.dataset.defaultTitle: (reason || 'Voice recording is unavailable');
 button.setAttribute('title', title);
 button.setAttribute('aria-label', title);
 });
 if (enabled && isVoiceTranscriptionMode()) {
 setRecordingControlButtons(isRecording? 'recording': (isRecordingPaused? 'paused': 'idle'));
 }
 }

 function recordingTimerNow() {
 return window.performance && typeof window.performance.now === 'function'? window.performance.now(): Date.now();
 }

 function formatRecordingTimer(seconds) {
 const safeSeconds = Math.max(0, Math.floor(Number(seconds) || 0));
 const m = Math.floor(safeSeconds / 60).toString().padStart(2, '0');
 const s = (safeSeconds % 60).toString().padStart(2, '0');
 return m + ':' + s;
 }

 function currentRecordingTimerSeconds() {
 if (!recTimerStartedAt) return recTimerSeconds;
 const elapsedSeconds = Math.floor(Math.max(0, recordingTimerNow() - recTimerStartedAt) / 1000);
 return Math.max(recTimerSeconds, recTimerBaseSeconds + elapsedSeconds);
 }

 function syncRecordingTimerDisplay(force = false) {
 const previousSeconds = recTimerSeconds;
 recTimerSeconds = currentRecordingTimerSeconds();
 const secondsChanged = recTimerSeconds!== previousSeconds;
 if (!force &&!secondsChanged) return recTimerSeconds;

 const timer = document.getElementById('recordingTimer');
 if (timer) timer.innerText = formatRecordingTimer(recTimerSeconds);

 const durationTarget = document.getElementById('vaDuration');
 if (durationTarget) durationTarget.innerText = recTimerSeconds + 's';

 const answerState = answersData[currentQIdx];
 if (answerState) {
 answerState.voice_duration = recTimerSeconds;
 const wordCount = String(answerState.speech_transcript || '').trim().split(/\s+/).filter(w=>w.length>0).length;

 // Match the server report: speech-transcript words divided
 // by the browser-timed recording duration.
 const timedSeconds = Math.max(1, recTimerSeconds);
 const wpm = Math.round((wordCount / timedSeconds) * 60);

 const wpmTarget = document.getElementById('vaWpm');
 if (wpmTarget) wpmTarget.innerText = wpm;
 answerState.wpm = wpm;
 }

 renderVoiceSessionPanel();

 if (secondsChanged) scheduleRecordingTimerSideEffects(previousSeconds);

 return recTimerSeconds;
 }

 function scheduleRecordingTimerSideEffects(previousSeconds) {
 const crossedAnalysisBoundary = recTimerSeconds >= 2 && Math.floor(recTimerSeconds / 2) > Math.floor(Math.max(0, previousSeconds) / 2);
 if (!crossedAnalysisBoundary) return;

 const analysisSecond = recTimerSeconds;
 setTimeout(() => {
 if (!isRecording || recTimerSeconds < analysisSecond) return;
 triggerAnalysis();

 // Optional body-language detection is descriptive and never affects readiness scoring.
 if (cameraDetectionEnabled) {
 trackBodyLanguageDetection();
 }
 }, 50);
 }

 function startRecordingTimer(startedAt = null) {
 clearInterval(recTimerInterval);
 recTimerBaseSeconds = recTimerSeconds;
 const startedAtMs = Number(startedAt) || recordingTimerNow();
 recTimerStartedAt = startedAtMs;
 syncRecordingTimerDisplay(true);
 recTimerInterval = setInterval(() => syncRecordingTimerDisplay(), 250);
 }

 function pauseRecordingTimer() {
 syncRecordingTimerDisplay(true);
 clearInterval(recTimerInterval);
 recTimerInterval = null;
 recTimerBaseSeconds = recTimerSeconds;
 recTimerStartedAt = 0;
 }

 function resetRecordingTimer() {
 clearInterval(recTimerInterval);
 recTimerInterval = null;
 recTimerStartedAt = 0;
 recTimerBaseSeconds = 0;
 voiceSessionRecordingStartedAt = 0;
 recTimerSeconds = 0;
 const timer = document.getElementById('recordingTimer');
 if (timer) timer.innerText = '00:00';
 }

 async function startRecording(options = {}) {
 if (recordingStartPromise) return recordingStartPromise;
 if (recordingStopPromise) {
 await recordingStopPromise.catch(error => {
 console.warn('Recording stop wait before start failed:', error);
 });
 }

 recordingStartPromise = startRecordingInternal(options).finally(() => {
 recordingStartPromise = null;
 updateAnswerTranscriptionOverlay();
 });
 updateAnswerTranscriptionOverlay();
 return recordingStartPromise;
 }

 async function startRecordingInternal(options = {}) {
 const silent = options && options.silent === true;
 if (isRecording) return true;
 if (!isVoiceTranscriptionMode()) {
 const message = 'Voice recording is disabled in Text Mode.';
 setTranscriptionStatus('');
 setVoiceControlsEnabled(false, message);
 updateAnswerTranscriptionOverlay();
 if(!silent) showSessionNotice(message);
 return false;
 }

 const recorderUnavailableMessage = voiceRecordingUnavailableMessage();
 const voiceOnly = isVoiceOnlyMode();
 let engine = recorderUnavailableMessage || voiceOnly? null: preferredTranscriptionEngine();
 if (recorderUnavailableMessage || (!voiceOnly &&!engine)) {
 const message = recorderUnavailableMessage || transcriptionUnavailableMessage();
 setTranscriptionStatus(message, '#f87171');
 setVoiceControlsEnabled(false, message);
 updateAnswerTranscriptionOverlay();
 if(!silent) showSessionNotice(voiceOnly? `${message} Voice Mode needs microphone recording.`: `${message} You can type your answer instead.`);
 return false;
 }

 if (!isRecordingPaused) {
 resetSpeechRecognitionBufferFromTextarea();
 }
 updateAnswerTranscriptionOverlay();

 if (!voiceOnly &&!await ensureMicrophoneReady(engine)) {
 updateAnswerTranscriptionOverlay();
 if(!silent) {
 const message = currentTranscriptionStatusMessage() || transcriptionUnavailableMessage();
 showSessionNotice(`${message} You can type your answer instead.`);
 }
 return false;
 }

 const voiceRecordingStarted = await startVoiceSessionRecorder();
 if (!voiceRecordingStarted) {
 const message = document.getElementById('voiceSessionStatus')?.textContent || 'Audio recording could not start.';
 const unavailable =!voiceSessionRecorderSupported() || microphoneRequiresSecureOrigin();
 setTranscriptionStatus(message, '#f87171');
 setVoiceControlsEnabled(!unavailable, message);
 if (!unavailable) setRecordingControlButtons('idle');
 updateAnswerTranscriptionOverlay();
 if(!silent) showSessionNotice(`${message} Check microphone permission, then try again. You can type your answer for feedback.`, 'warning');
 return false;
 }

 lastSpeechEnd = 0;
 shouldAutoRestartRecognition =!voiceOnly;
 isRecording = true;
 isRecordingPaused = false;
 activeTranscriptionEngine = voiceOnly? null: engine;
 updateAnswerTranscriptionOverlay();

 let started = voiceOnly? true: (engine === 'server'? startServerTranscriptionEngine(): startSpeechRecognitionEngine());

 if (!started &&!voiceOnly && engine === 'browser' && canUseServerTranscription()) {
 activeTranscriptionEngine = 'server';
 engine = 'server';
 started = await ensureMicrophoneReady('server') && startServerTranscriptionEngine();
 }

 if (!started) {
 shouldAutoRestartRecognition = false;
 isRecording = false;
 isRecordingPaused = false;
 await stopVoiceSessionRecorder({ discard: true, skipStartWait: true });
 const message = currentTranscriptionStatusMessage() || transcriptionUnavailableMessage();
 setVoiceControlsEnabled(false, message);
 updateAnswerTranscriptionOverlay();
 if(!silent) showSessionNotice(`${message} You can type your answer instead.`);
 return false;
 }

 if (voiceOnly) {
 setTranscriptionStatus('Voice-only recording. Text transcription is off.');
 }

 clearSessionNotice();
 setVoiceControlsEnabled(true);
 setRecordingControlButtons('recording');
 startRecordingTimer(voiceSessionRecordingStartedAt);
 updateAnswerTranscriptionOverlay();

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
 if (recordingStartPromise) {
 await recordingStartPromise.catch(error => {
 console.warn('Recording start wait before pause failed:', error);
 });
 }

 if (!isRecording &&!voiceSessionRecorder) {
 if (!isRecordingPaused) setRecordingControlButtons('idle');
 updateAnswerTranscriptionOverlay();
 return false;
 }

 finalizeInterimTranscript();
 shouldAutoRestartRecognition = false;

 const usedServerTranscription = activeTranscriptionEngine === 'server';
 const usedBrowserTranscription = activeTranscriptionEngine === 'browser';
 if(recognition && usedBrowserTranscription) {
 try {
 recognition.stop();
 } catch (error) {
 console.error('Speech recognition failed to stop:', error);
 }
 }
 isRecording = false;
 isRecordingPaused = true;
 pauseRecordingTimer();
 pauseVoiceSessionRecorder();
 setRecordingControlButtons('paused');
 updateAnswerTranscriptionOverlay();
 const scannerBox = document.getElementById('faceScannerBox');
 if (scannerBox) scannerBox.style.display = 'none';

 if (usedServerTranscription) {
 await stopServerTranscriptionEngine();
 }

 if (!interviewEnding &&!interviewTerminated) {
 setTranscriptionStatus('Paused');
 }
 }

 async function stopRecording() {
 if (recordingStartPromise) {
 await recordingStartPromise.catch(error => {
 console.warn('Recording start wait before stop failed:', error);
 });
 }

 if (recordingStopPromise) return recordingStopPromise;
 recordingStopPromise = stopRecordingInternal().finally(() => {
 recordingStopPromise = null;
 });
 return recordingStopPromise;
 }

 async function stopRecordingInternal() {
 await pauseRecording();
 await stopVoiceSessionRecorder();
 let generatedFinalTranscript = false;
 if (isHybridTranscriptionMode()) {
 generatedFinalTranscript = await fillEmptyHybridTranscriptFromRecording().catch(error => {
 console.warn('Final hybrid recording transcription failed:', error);
 return false;
 });
 }
 if (isHybridTranscriptionMode() && answersData[currentQIdx]) {
 const textareaText = currentAnswerTextareaText();
 answersData[currentQIdx].text = textareaText;
 answersData[currentQIdx].speech_transcript = cleanTranscriptText(textareaText);
 }
 clearTimeout(autoStartAfterQuestionTimer);
 isRecordingPaused = false;
 resetRecordingTimer();
 setRecordingControlButtons('idle');
 resetSpeechRecognitionBufferFromTextarea();
 updateAnswerTranscriptionOverlay();
 if (isHybridTranscriptionMode()) {
 const hasTranscriptText = currentAnswerTextareaText().trim() !== '';
 setTranscriptionStatus(hasTranscriptText? (generatedFinalTranscript? 'Recording stopped - transcript generated and ready to edit': 'Recording stopped - transcript is ready to edit'): 'Recording stopped - no speech detected yet', hasTranscriptText? '#16a34a': '#fbbf24');
 } else if (isVoiceOnlyMode()) {
 setTranscriptionStatus('Recording stopped');
 } else {
 setTranscriptionStatus('');
 }
 resetSpeechRecognitionBufferFromTextarea();
 renderVoiceSessionPanel();
 return true;
 }

 async function finalizeCurrentTranscriptionForSubmit() {
 finalizeInterimTranscript();

 if (recordingStartPromise || recordingStopPromise || isRecording || isRecordingPaused || voiceSessionRecorder || voiceSessionStopPromise) {
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
 await waitForServerTranscriptionDrain(serverTranscriptionDrainTimeoutMs);
 commitReadyServerTranscriptionResults();
 }
 }

 function saveCurrentAnswer(isSkipped = false, timedOut = false) {
 if (interviewTerminated) return Promise.reject(new Error('Interview session has been terminated.'));
 syncHybridAnswerStateFromTextarea();
 stopQuestionTimer();
 captureTranscriptTimeline(timedOut? 'timed_out_submit': 'submitted', true);
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
 appendVoiceSessionRecordingUpload(formData, currentQIdx);
 formData.append('filler_words_count', answersData[currentQIdx].filler_words);
 formData.append('pause_count', answersData[currentQIdx].pause_count);
 formData.append('confidence_score', 0);
 formData.append('self_reported_confidence', 0);
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
 syncHybridAnswerStateFromTextarea();
 if (!isHybridTranscriptionMode()) {
 answersData[currentQIdx].text = currentAnswerTextareaText();
 }
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
 snapshot.observation_data = index === currentQIdx? observationDataForSubmit(answer, 100, 120): { filler_events: [], camera_samples: [] };
 return snapshot;
 });
 formData.append('session_state', JSON.stringify({
 has_started: true,
 currentQIdx,
 timerSeconds,
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
 if (error.name!== 'AbortError') {
 console.warn('Interview state auto-save failed:', error);
 }
 });
 }

 function appendChatMessage(role, text, record = true) {
 const chatContainer = document.getElementById('chatTranscriptContainer');
 if (!chatContainer) return;
 if (role === 'interviewer') {
 chatContainer.innerHTML = '';
 chatContainer.style.display = 'none';
 if (record) {
 interviewChatHistory.push({ role, text });
 if (interviewChatHistory.length > 80) interviewChatHistory = interviewChatHistory.slice(interviewChatHistory.length - 80);
 scheduleStateSave();
 }
 return;
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
 
 bubble.style.background = 'rgba(59,130,246,0.15)';
 bubble.style.border = '1px solid rgba(59,130,246,0.3)';
 bubble.style.alignSelf = 'flex-end';
 bubble.innerHTML = '<strong><i class="fa-solid fa-user me-1"></i> You</strong><br>' + escapeHtml(text);
 
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
 updateSendAnswerButtonState();
 try {
 await finalizeCurrentTranscriptionForSubmit();
 } catch (error) {
 console.warn('Final transcription flush before submit failed:', error);
 }
 syncHybridAnswerStateFromTextarea();
 
 const timedOut = options.timedOut === true;
 let answerText = currentAnswerTextareaText().trim();
 const localVoiceRecording = isVoiceTranscriptionMode()? voiceSessionRecordings.get(voiceSessionKeyFor()): null;
 const hasLocalVoiceRecording = isVoiceTranscriptionMode()
 && Boolean(localVoiceRecording || answersData[currentQIdx]?.voice_recording?.available);

 if (isHybridTranscriptionMode() &&!answerText && hasLocalVoiceRecording &&!timedOut && options.skipped!== true) {
 await transcribeVoiceSessionRecording(currentQIdx, { silent: true });
 answerText = currentAnswerTextareaText().trim();
 }

 const hasSubmittableVoiceRecording = Boolean(submittableVoiceSessionRecording(currentQIdx, localVoiceRecording));
 if (hasSubmittableVoiceRecording && localVoiceRecording?.durationSeconds) {
 answersData[currentQIdx].voice_duration = Math.max(
 Number(answersData[currentQIdx].voice_duration || 0),
 Math.round(Number(localVoiceRecording.durationSeconds || 0))
 );
 }

 const wasSkipped = options.skipped === true || (timedOut &&!answerText &&!hasSubmittableVoiceRecording);
 if(!answerText &&!timedOut &&!wasSkipped &&!hasSubmittableVoiceRecording) {
 isSubmittingAnswer = false;
 updateSendAnswerButtonState();
 showSessionNotice(hasLocalVoiceRecording? (isVoiceOnlyMode()? 'This recording cannot be submitted. Record your voice answer again before sending.': 'This recording cannot be submitted. Record again, generate the transcript, or type the answer before submitting.'): (isVoiceOnlyMode()? 'Record a voice answer before submitting.': 'Please provide an answer before submitting.'));
 focusAnswerTextarea();
 return;
 }
 if(!answerText && hasSubmittableVoiceRecording &&!wasSkipped) {
 showSessionNotice(isVoiceOnlyMode()? 'Voice-only answer saved for feedback. Playback will be available in your AI feedback review.': 'Voice answer saved for feedback. Transcript is unavailable, but playback will be available in your AI feedback review.', 'warning');
 }
 if(!answerText && timedOut &&!hasSubmittableVoiceRecording) {
 answerText = "[Time expired with no answer]";
 setCurrentAnswerTextareaText(answerText);
 }

 setAnswerInputEnabled(false);

 answersData[currentQIdx].text = answerText;
 answersData[currentQIdx].is_skipped = wasSkipped;
 answersData[currentQIdx].timed_out = timedOut;

 const submittedDisplayText = answerText || (hasSubmittableVoiceRecording? (isVoiceOnlyMode()? 'Voice-only answer recorded.': 'Voice answer recorded (transcript unavailable).'): answerText);
 appendChatMessage('user', submittedDisplayText);
 clearSubmittedAnswerInput();

 const answeredOpeningQuestion = isOpeningQuestion(questions[currentQIdx]);
 const isLastQuestion =!answeredOpeningQuestion && isLastScoredQuestion(currentQIdx);

 if (isLastQuestion) {
 finalAnswerSubmitted = true;
 try {
 await saveCurrentAnswer(wasSkipped, timedOut);
 isSubmittingAnswer = false;
 updateSendAnswerButtonState();
 await concludeAndFinishInterview();
 } catch(error) {
 console.error(error);
 finalAnswerSubmitted = false;
 isSubmittingAnswer = false;
 updateSendAnswerButtonState();
 if (!interviewTerminated) {
 restoreSubmittedAnswerInput(answerText);
 setAnswerInputEnabled(true);
 showSessionNotice('We could not save your final answer. Please try again before finishing.');
 }
 }
 return;
 }

 stopQuestionTimer();
 captureTranscriptTimeline(timedOut? 'timed_out_submit': 'submitted', true);
 
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
 appendVoiceSessionRecordingUpload(formData, currentQIdx);
 formData.append('filler_words_count', answersData[currentQIdx].filler_words);
 formData.append('pause_count', answersData[currentQIdx].pause_count);
 formData.append('confidence_score', 0);
 formData.append('self_reported_confidence', 0);
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
 updateSendAnswerButtonState();
 await concludeAndFinishInterview();
 return;
 }

 const newQ = {
 id: data.next_question_id,
 question_text: data.next_question_text,
 source_type: data.source_type || ''
 };
 const nextQuestionIndex = placeNextQuestion(newQ);
 currentQIdx = nextQuestionIndex;
 isSubmittingAnswer = false;
 updateSendAnswerButtonState();
 await loadQuestion(currentQIdx);
 } catch(err) {
 const tb = document.getElementById('thinkingBubble');
 if(tb) tb.remove();
 isSubmittingAnswer = false;
 updateSendAnswerButtonState();
 console.error(err);
 if (!interviewTerminated && err.name!== 'AbortError') {
 restoreSubmittedAnswerInput(answerText);
 setAnswerInputEnabled(true);
 showSessionNotice(err.message || 'Network error.');
 }
 }
 }

 async function skipQuestion() {
 await finalizeCurrentTranscriptionForSubmit();
 setCurrentAnswerTextareaText("[User skipped the question]");
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
 recognition.abort? recognition.abort(): recognition.stop();
 } catch (error) {
 console.warn('Speech recognition cleanup failed:', error);
 }
 }
 recognitionActive = false;
 if (serverTranscriptionRecorder && serverTranscriptionRecorder.state!== 'inactive') {
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
 discardVoiceSessionRecorder({ revokeSaved: true });
 setTranscriptionStatus('');
 updateAnswerTranscriptionOverlay();
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
 if (!preview ||!previewText) return;

 const answerText = currentAnswerTextareaText().trim();
 if (!answerText) {
 preview.hidden = true;
 previewText.textContent = '';
 return;
 }

 previewText.textContent = answerText.length > 280? `${answerText.slice(0, 280)}...`: answerText;
 preview.hidden = false;
 }

 function appendCurrentAnswerForEndedSession(formData) {
 const question = questions[currentQIdx];
 const answerState = answersData[currentQIdx];
 if (!question ||!answerState) return;

 const answerText = String(currentAnswerTextareaText() || answerState.text || '').trim();
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
 formData.append('confidence_score', 0);
 formData.append('self_reported_confidence', 0);
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
 const data = response.ok? await response.json(): {};
 window.location.href = data.redirect_url || '{{ route("interview.setup") }}';
 } catch (error) {
 console.error('Interview abort failed:', error);
 window.location.href = '{{ route("interview.setup") }}';
 }
 }

 function activeInterviewModal() {
 return document.querySelector('#interviewStartModal.active, #endSessionModal.active, #sessionAlertModal.active');
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
 const focusable = Array.from(modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')).filter(element =>!element.disabled && element.offsetParent!== null);
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
 if (modal.parentElement!== document.body) {
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
 if (visible && overlay.parentElement!== document.body) {
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
 },
 timeoutMs: 45000
 });
 const payload = await parseResponsePayload(response);
 const data = payload.data || {};

 if (response.status === 409) {
 await waitForFeedbackRetry(data.retry_after_ms);
 continue;
 }

 if (!response.ok ||!data.redirect_url) {
 const error = new Error(finishResponseErrorMessage(response, payload));
 error.status = response.status;
 throw error;
 }

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
 return plainText? plainText.slice(0, 220): 'The feedback service returned an incomplete response.';
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
 focusAnswerTextarea();
 }

 function setInterviewStartModalVisible(visible) {
 const modal = document.getElementById('interviewStartModal');
 if (!modal) return;
 if (visible && modal.parentElement!== document.body) {
 document.body.appendChild(modal);
 }
 modal.classList.toggle('active', visible);
 syncInterviewModalBodyState();

 if (visible) {
 focusFirstModalAction(modal, '#confirmInterviewStartButton');
 }
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
 if (event.key!== 'Escape') return false;

 const modal = activeInterviewModal();
 const sessionFullscreenActive = document.body.classList.contains('mobile-interview-fullscreen')
 || Boolean(document.fullscreenElement);

 if (modal?.id === 'endSessionModal') {
 event.preventDefault();
 cancelAbortInterviewSession();
 return true;
 }

 if (modal?.id === 'sessionAlertModal') {
 event.preventDefault();
 closeSessionAlertModal();
 return true;
 }

 if (sessionFullscreenActive) {
 event.preventDefault();
 exitMobileFullscreen();
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
 consumeSetupAutoFullscreenPreference();
 enterMobileFullscreen({ requestBrowser: false });
 updateMobileFullscreenToggle();
 document.addEventListener('fullscreenchange', handleBrowserFullscreenChange);
 protectAiCoachPossibleAnswer();
 document.addEventListener('keydown', event => {
 if (handleInterviewEscapeKey(event)) return;

 const modal = activeInterviewModal();
 if (!modal) return;
 if (event.key === 'Tab') {
 trapModalFocus(event, modal);
 }
 });
 setInterviewStartModalVisible(!interviewStarted &&!interviewTerminated);
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
 if (typeof cameraDetectionEnabled!== 'undefined' && cameraDetectionEnabled) {
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
 if (typeof cameraDetectionEnabled!== 'undefined' && cameraDetectionEnabled) {
 const modelState = window.bodyLanguageModelState = window.bodyLanguageModelState || {
 ready: false,
 failed: false,
 poseLandmarker: null
 };

 import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21/vision_bundle.mjs').then(async ({ FilesetResolver, PoseLandmarker }) => {
 const vision = await FilesetResolver.forVisionTasks(
 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21/wasm'
 );
 const poseLandmarker = await PoseLandmarker.createFromOptions(vision, {
 baseOptions: {
 modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/latest/pose_landmarker_lite.task'
 },
 runningMode: 'VIDEO',
 numPoses: 1,
 minPoseDetectionConfidence: 0.5,
 minPosePresenceConfidence: 0.5,
 minTrackingConfidence: 0.5,
 outputSegmentationMasks: false
 });

 Object.assign(modelState, {
 ready: true,
 failed: false,
 poseLandmarker
 });
 const detectionStatus = document.getElementById('cameraDetectionStatus');
 if (detectionStatus) {
 detectionStatus.innerHTML = '<i class="fa-solid fa-person-rays me-1"></i>Pose model ready';
 detectionStatus.style.color = '#34d399';
 }
 console.log("Optional body-language models loaded");
 }).catch(err => {
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
 if (typeof window.createSpeakReadyTour!== 'function') return;

 const stepsMobile = [
 { element: '#interviewStartModal.active .interview-start-dialog', popover: { title: 'Session Preview', description: 'Review the scenario, response mode, coaching level, question count, and camera setting before entering the room.', side: 'bottom', align: 'center' }},
 { element: '#interviewStartModal.active #confirmInterviewStartButton', popover: { title: 'Begin When Ready', description: 'Start or resume the interview after the setup summary looks right.', side: 'top', align: 'center' }},
 { element: '.ai-avatar-panel', popover: { title: 'AI Interviewer', description: 'Questions appear here with the interviewer avatar, caption area, timer, and quick controls.', side: 'bottom', align: 'start' }},
 { element: '#interviewControls', popover: { title: 'Question Controls', description: 'Repeat the current question or end the session from this compact control strip.', side: 'top', align: 'center' }},
 { element: '#answerTranscriptControls:not([hidden])', popover: { title: 'Voice Controls', description: 'In Voice or Hybrid mode, use these buttons to pause or stop recording while the timer tracks your answer.', side: 'top', align: 'center' }},
 { element: '.response-panel', popover: { title: 'Your Response', description: 'Type, speak, or edit your answer here. Word and character counts update as you work.', side: 'top', align: 'start' }},
 { element: '.response-title-actions', popover: { title: 'Submit Tools', description: 'Send your answer, open fullscreen, or access coaching tools when they are available.', side: 'top', align: 'center' }},
 { element: '#aiCoachHeadButton', popover: { title: 'AI Coach', description: 'In coaching mode, this opens a possible-answer panel for guidance without submitting anything for you.', side: 'top', align: 'center' }},
 { element: '.desktop-camera-pip, .mobile-camera-pip, #cameraPanel', popover: { title: 'Camera Detection', description: 'If enabled, camera detection checks local framing and posture cues for coaching only. It is excluded from readiness scoring.', side: 'top', align: 'center' }}
 ];

 const stepsDesktop = [
 { element: '#interviewStartModal.active .interview-start-dialog', popover: { title: 'Session Preview', description: 'Review the scenario, response mode, coaching level, question count, and camera setting before entering the room.', side: 'bottom', align: 'center' }},
 { element: '#interviewStartModal.active #confirmInterviewStartButton', popover: { title: 'Begin When Ready', description: 'Start or resume the interview after the setup summary looks right.', side: 'top', align: 'center' }},
 { element: '.ai-avatar-panel', popover: { title: 'AI Interviewer', description: 'Questions appear here with the interviewer avatar, caption area, timer, and quick controls.', side: 'right', align: 'start' }},
 { element: '#interviewControls', popover: { title: 'Question Controls', description: 'Repeat the current question or end the session from this compact control strip.', side: 'top', align: 'center' }},
 { element: '#answerTranscriptControls:not([hidden])', popover: { title: 'Voice Controls', description: 'In Voice or Hybrid mode, use these buttons to pause or stop recording while the timer tracks your answer.', side: 'top', align: 'center' }},
 { element: '.response-panel', popover: { title: 'Your Response', description: 'Type, speak, or edit your answer here. Word and character counts update as you work.', side: 'left', align: 'start' }},
 { element: '.response-title-actions', popover: { title: 'Submit Tools', description: 'Send your answer, open fullscreen, or access coaching tools when they are available.', side: 'bottom', align: 'end' }},
 { element: '#aiCoachHeadButton', popover: { title: 'AI Coach', description: 'In coaching mode, this opens a possible-answer panel for guidance without submitting anything for you.', side: 'bottom', align: 'center' }},
 { element: '.desktop-camera-pip, .mobile-camera-pip, #cameraPanel', popover: { title: 'Camera Detection', description: 'If enabled, camera detection checks local framing and posture cues for coaching only. It is excluded from readiness scoring.', side: 'bottom', align: 'center' }}
 ];
 const sessionTourCompletionKey = 'onboarding_completed_interview_session';

 const onboardingTour = window.createSpeakReadyTour({
 completionKey: sessionTourCompletionKey,
 serverDetectedMobile: true,
 stepsMobile: stepsMobile,
 stepsDesktop: stepsDesktop,
 autoStart: false,
 startDelay: 80,
 onBeforeDestroy: () => {
 if (isInterviewWorkspaceVisible()) return;

 try {
 localStorage.removeItem(sessionTourCompletionKey);
 } catch (error) {
 console.warn('Unable to keep session tutorial incomplete before start:', error);
 }
 },
 });

 let sessionTourAutoStartTimer = null;

 function isInterviewWorkspaceVisible() {
 const workspace = document.getElementById('workspaceWrapper');
 if (!workspace) return false;
 const style = window.getComputedStyle(workspace);
 return style.display !== 'none' && workspace.getClientRects().length > 0;
 }

 function scheduleInterviewSessionTour(delay = 900) {
 if (!onboardingTour || onboardingTour.isCompleted() || sessionTourAutoStartTimer) return;

 sessionTourAutoStartTimer = window.setTimeout(() => {
 sessionTourAutoStartTimer = null;
 if (!isInterviewWorkspaceVisible() || onboardingTour.isCompleted()) return;
 onboardingTour.start();
 }, delay);
 }

 window.addEventListener('speakready:interview-session-started', () => scheduleInterviewSessionTour());

 if (isInterviewWorkspaceVisible()) {
 scheduleInterviewSessionTour(500);
 }
 });
</script>
@endpush
@endsection
