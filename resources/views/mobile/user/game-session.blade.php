@extends('mobile.layouts.app')
@section('title', 'Interview Challenge')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/game-session.css?v=1') }}" data-page-style="user-game-session">
<link rel="stylesheet" href="{{ asset('css/mobile/user/game-session-2.css?v=1') }}" data-page-style="user-game-session-2">
@endpush

@section('content')
@include('mobile.partials.page-hero-styles')

<div class="db-section active" id="sec-learning-game-session">
 @if(session('active_game_session_id'))
 @php
 $sessionRecord = $gameSession?? null;
 if ($sessionRecord) {
 $cameraCoachingEnabled = (bool) data_get($sessionRecord->accommodation_profile, 'camera_coaching', false);
 $num = $sessionRecord->num_questions?? count($sessionRecord->questions?? []);
 $questions = collect($sessionRecord->questions?? [])->values()->map(function ($questionText, $index) {
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
 $isVoiceOnlyMode = $sessionRecord && $sessionRecord->response_mode === 'voice';
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
 <span class="badge hud-mode-badge" style="background:var(--pur);color:#fff;font-size:0.8rem;"><i class="fa-solid fa-gamepad me-1"></i> INTERVIEW CHALLENGE</span>
 <h4 class="hud-title" style="font-size:1.4rem;font-weight:800;margin:0;color:var(--tx)">Level {{ $gameLevel->level_number }}: {{ $gameLevel->title }}</h4>
 </div>
 @if($gameLevel->learning_objective)
 <div class="hud-objective" style="font-size:0.86rem;color:var(--tx2);line-height:1.45;max-width:760px;">{{ $gameLevel->learning_objective }}</div>
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

 <div id="workspaceWrapper" class="learning-game-interview-layout" style="display:none;">
 <div class="row g-4 {{ $cameraCoachingEnabled? 'has-session-side-panel': 'session-main-only' }}" id="workspaceRow">
 <!-- Main Content Area -->
 <div class="{{ $cameraCoachingEnabled? 'col-lg-8': 'col-lg-12' }}">
 <!-- Progress Tracker Removed by User -->
 <div class="desktop-session-two-column">
 <div class="desktop-interview-panel">

 <!-- Simulated AI Video Avatar Panel -->
 <div class="panel p-0 ai-avatar-panel animate-fade-up delay-100" style="overflow:hidden;border:1px solid var(--bd);background:#000;position:relative;height:250px;border-radius:18px;margin-bottom:20px;">
 <span class="question-counter-badge" id="qCounter">1/10</span>
 <span class="badge interviewer-panel-badge"><i class="fa-solid fa-bolt me-1"></i> {{ $sessionRecord->company_persona?? 'AI Coach' }}</span>
 @if($cameraCoachingEnabled)
 <!-- Optional mobile camera framing preview -->
 <div class="mobile-camera-preview d-block d-lg-none" style="position:absolute; top:15px; right:15px; width:80px; height:105px; border-radius:8px; overflow:hidden; border:2px solid rgba(255,255,255,0.3); z-index:50; box-shadow: 0 4px 15px rgba(0,0,0,0.6);">
 <video id="userCameraMobile" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);background:#222;"></video>
 </div>
 @endif

 <div id="aiAvatarContainer" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background: linear-gradient(135deg, #1e1b4b, #312e81);">
 <div class="avatar-wrapper" id="aiAvatarHead" style="width:100px;height:100px;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;transition:border-color 0.3s;">
 <!-- The Image Container (with border, glow, and clipping for the image itself) -->
 <div class="avatar-frame" style="width:100%;height:100%;background:rgba(255,255,255,0.1);border-radius:50%;border:3px solid #8b5cf6;overflow:hidden;position:relative;z-index:10;box-shadow: 0 0 15px rgba(139,92,246,0.3);">
 <img src="{{ asset('img/ai_avatar.jpg') }}" alt="AI Avatar" style="width:100%;height:100%;object-fit:cover;">
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
 <div class="question-caption-overlay ai-question-overlay" aria-live="polite" aria-atomic="true">
 <div id="questionCaptionText" class="question-caption-line custom-scrollbar">Loading your first question...</div>
 </div>
 </div>

 <div class="ai-question-card animate-fade-up delay-150">
 <div class="d-flex justify-content-center align-items-end gap-3 text-center">
 <div class="w-100">
 <div id="aiQuestionText">Loading your first question...</div>
 </div>
 </div>
 </div>

 <!-- Unified challenge controls -->
 <div class="session-nav-row animate-fade-up delay-150" id="gameSessionControls">
 <button type="button" class="btn btn-outline-info session-nav-icon" onclick="repeatQuestion()" aria-label="Repeat question" title="Repeat question"><i class="fa-solid fa-volume-high"></i></button>
 <button type="button" class="btn btn-outline-secondary session-nav-icon prev-btn-class" onclick="prevQuestion()" disabled aria-label="Previous question" title="Previous question"><i class="fa-solid fa-arrow-left"></i></button>
 <button type="button" class="btn btn-outline-warning session-nav-skip skip-btn-class" onclick="skipQuestion()">Skip <i class="fa-solid fa-forward-step ms-1"></i></button>
 <button type="button" class="bgrd btn session-nav-next next-btn-class text-white" onclick="submitAnswer()"><span class="next-label-full">Next Question</span><span class="next-label-short">Next</span><i class="fa-solid fa-arrow-right ms-2"></i></button>
 </div>
 </div>

 <div class="desktop-response-column">

 <!-- Answer Response System -->
 <div class="panel response-panel mb-4 animate-fade-up delay-200">
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

 <textarea id="answerTextarea" class="oinp mb-2" style="min-height:200px;font-size:.95rem" placeholder="{{ $isVoiceOnlyMode? 'Record your answer with voice. The transcript will appear here when available...': 'Type your answer here, or use voice to auto-transcribe...' }}" @if($isVoiceOnlyMode) readonly @endif></textarea>
 
 <div class="answer-meta-row response-count-bar d-flex justify-content-between align-items-center mb-4">
 <div style="font-size:.8rem;color:var(--tx3)">
 <span id="wordCount">0 words</span> • <span id="charCount">0 characters</span>
 <span id="autoSaveIndicator" class="ms-3 text-success" style="display:none;"><i class="fa-solid fa-check me-1"></i>Auto-saved</span>
 </div>
 </div>

 </form>
 </div>
 </div>
 </div>
 </div>

 @if($cameraCoachingEnabled)
 <!-- Side Panels -->
 <div class="col-lg-4">
 <!-- Session Navigation (Mobile fallback / Overview) -->
 <!-- Optional body-language detection; never used in readiness scoring. -->
 <div class="panel d-none d-lg-block" id="cameraPanel">
 <div class="panel-title"><i class="fa-solid fa-camera-web me-2"></i> Body-Language Detection</div>
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
 </div>
 @endif
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
 $initialQuestionIndex = min(max(0, (int) ($sessionRecord->current_question_index?? 0)), max(0, $questions->count() - 1));
 $answersByIndex = $sessionRecord->relationLoaded('answers')? $sessionRecord->answers->keyBy('question_index'): collect();
 $initialAnswersData = $questions->map(function ($question) use ($answersByIndex) {
 $answer = $answersByIndex->get($question->question_index);

 return [
 'text' => $answer &&! $answer->is_skipped? (string) ($answer->answer_text?? ''): '',
 'is_skipped' => (bool) ($answer?->is_skipped?? false),
 'wpm' => (int) ($answer?->wpm?? 0),
 'voice_duration' => (int) ($answer?->voice_duration?? 0),
 'filler_words' => (int) ($answer?->filler_words_count?? 0),
 'pause_count' => (int) ($answer?->pause_count?? 0),
 'confidence_score' => (int) ($answer?->confidence_score?? 0),
 'eye_contact_score' => (int) ($answer?->eye_contact_score?? 0),
 'posture_score' => (int) ($answer?->posture_score?? 0),
 'has_voice_recording' => trim((string) ($answer?->voice_recording_path?? ''))!== '',
 ];
 })->values();
 @endphp

 <script>
 const questions = {!! json_encode($questions)!!};
 const gameSessionId = {{ (int) $sessionRecord->id }};
 const responseMode = "{{ $sessionRecord->response_mode }}";
 const isVoiceOnlySession = responseMode === "voice";
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
 let isStartingRecording = false;
 let stopRequestedWhileStarting = false;
 let recTimerSeconds = 0;
 let recTimerInterval;
 let mediaRecorder = null;
 let mediaRecorderStream = null;
 let mediaRecorderChunks = [];
 let mediaRecorderStartedAt = 0;
 let voiceRecordingStopPromise = null;
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
 let gameMatchFullscreenRequested = false;
 let gameMatchFullscreenRetryQueued = false;
 const gameMatchFullscreenRetryEvents = ['pointerdown', 'keydown', 'touchstart', 'click'];

 function refreshGameMatchFullscreenLayout() {
 window.SpeakReadyViewport?.refreshNow?.();
 window.SpeakReadyViewport?.refresh?.();
 window.dispatchEvent(new Event('resize'));
 window.requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
 window.setTimeout(() => window.dispatchEvent(new Event('resize')), 220);
 }

 function setGameMatchFullscreenShell(active) {
 document.body.classList.toggle('game-match-auto-fullscreen', active);
 document.body.classList.toggle('mobile-interview-fullscreen', active && document.body.classList.contains('user-mobile-shell'));
 refreshGameMatchFullscreenLayout();
 }

 function removeGameMatchFullscreenRetry() {
 if (!gameMatchFullscreenRetryQueued) return;

 gameMatchFullscreenRetryQueued = false;
 gameMatchFullscreenRetryEvents.forEach(eventName => {
 document.removeEventListener(eventName, retryGameMatchFullscreenFromGesture, true);
 });
 }

 function retryGameMatchFullscreenFromGesture() {
 removeGameMatchFullscreenRetry();
 enterGameMatchFullscreen({ retry: true });
 }

 function armGameMatchFullscreenRetry() {
 if (gameMatchFullscreenRetryQueued || document.fullscreenElement) return;

 gameMatchFullscreenRetryQueued = true;
 gameMatchFullscreenRetryEvents.forEach(eventName => {
 document.addEventListener(eventName, retryGameMatchFullscreenFromGesture, {
 capture: true,
 once: true
 });
 });
 }

 function enterGameMatchFullscreen(options = {}) {
 gameMatchFullscreenRequested = true;
 setGameMatchFullscreenShell(true);

 const root = document.documentElement;
 if (document.fullscreenElement || typeof root.requestFullscreen!== 'function') {
 return Promise.resolve(false);
 }

 return root.requestFullscreen({ navigationUI: 'hide' }).then(() => {
 removeGameMatchFullscreenRetry();
 gameMatchFullscreenRequested = true;
 setGameMatchFullscreenShell(true);
 return true;
 }).catch(error => {
 console.warn('Game match fullscreen request was blocked:', error);
 armGameMatchFullscreenRetry();
 setGameMatchFullscreenShell(true);
 return false;
 });
 }

 function exitGameMatchFullscreen() {
 removeGameMatchFullscreenRetry();
 gameMatchFullscreenRequested = false;
 setGameMatchFullscreenShell(false);

 if (document.fullscreenElement && document.exitFullscreen) {
 return document.exitFullscreen().catch(() => {});
 }

 return Promise.resolve();
 }

 function handleGameMatchFullscreenChange() {
 if (document.fullscreenElement) {
 removeGameMatchFullscreenRetry();
 gameMatchFullscreenRequested = true;
 setGameMatchFullscreenShell(true);
 return;
 }

 if (gameMatchFullscreenRequested) {
 gameMatchFullscreenRequested = false;
 setGameMatchFullscreenShell(false);
 }
 }

 document.addEventListener('fullscreenchange', handleGameMatchFullscreenChange);
 window.addEventListener('pagehide', removeGameMatchFullscreenRetry);
 window.enterGameMatchFullscreen = enterGameMatchFullscreen;
 window.exitGameMatchFullscreen = exitGameMatchFullscreen;

 function cleanTranscriptText(value) {
 return String(value || '').replace(/\s+/g, ' ').trim();
 }

 function normalizeTranscriptForMatch(value) {
 return cleanTranscriptText(value).toLocaleLowerCase(speechLocale).replace(/[^\p{L}\p{N}'\u2019\s]/gu, '').replace(/\s+/g, ' ').trim();
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
 return cleanTranscriptText(existingClean + (remainder? ' ' + remainder: ''));
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
 if (!recognition || recognitionActive ||!isRecording ||!shouldAutoRestartRecognition) return;

 try {
 recognition.start();
 recognitionActive = true;
 } catch (error) {
 if (!error || error.name!== 'InvalidStateError') {
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
 }
 }).catch(function(err) {
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
 if (!landmark ||!Number.isFinite(Number(landmark.x)) ||!Number.isFinite(Number(landmark.y))) return false;
 return Number(landmark.visibility?? landmark.presence?? 1) >= threshold;
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
 if (!left ||!right) return null;
 return Math.hypot(Number(left.x) - Number(right.x), Number(left.y) - Number(right.y));
 }

 function gameDetectVideoFrame(landmarker, video, timestamp) {
 if (!landmarker || typeof landmarker.detectForVideo!== 'function') return null;
 try {
 return landmarker.detectForVideo(video, timestamp);
 } catch (error) {
 return landmarker.detectForVideo(video);
 }
 }

 async function trackBodyLanguage() {
 const bodyLanguageState = window.bodyLanguageModelState || {};
 const canUseBodyModels = Boolean(bodyLanguageState.ready && bodyLanguageState.poseLandmarker && bodyLanguageState.handLandmarker);
 const canUseFaceModel = typeof faceapi!== 'undefined';
 if (!cameraCoachingEnabled || (!canUseBodyModels &&!canUseFaceModel)) return;
 const video = document.getElementById('userCamera');
 if (!video ||!video.srcObject) return;

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
 poseLandmarks = Array.isArray(poseResult?.landmarks) && poseResult.landmarks.length > 0? poseResult.landmarks[0]: null;
 handLandmarks = Array.isArray(handResult?.landmarks)? handResult.landmarks.slice(0, 2): [];
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
 const shoulderMidpoint = shouldersVisible? gameCenterOf([leftShoulder, rightShoulder]): null;
 const hipMidpoint = hipsVisible? gameCenterOf([leftHip, rightHip]): null;
 const shoulderWidth = shouldersVisible? Math.max(0.01, gamePointDistance(leftShoulder, rightShoulder)?? 0.01): 0.01;
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

 const handCenters = handLandmarks.map(hand => gameCenterOf(Array.isArray(hand)? hand: [])).filter(Boolean);
 handCenters.forEach((center, index) => {
 movementPoints['hand' + index] = center;
 });

 let movementScore = null;
 let gestureActive = false;
 if (gameCameraMovementBaseline && Object.keys(movementPoints).length > 0) {
 const distances = Object.entries(movementPoints).map(([key, point]) => gamePointDistance(point, gameCameraMovementBaseline[key])).filter(distance => Number.isFinite(distance));
 if (distances.length > 0) {
 movementScore = Math.min(100, Math.round((distances.reduce((total, distance) => total + distance, 0) / distances.length) * 650));
 }
 gestureActive = handCenters.some((center, index) => {
 const distance = gamePointDistance(center, gameCameraMovementBaseline['hand' + index]);
 return Number.isFinite(distance) && distance >= 0.045;
 });
 }
 gameCameraMovementBaseline = movementPoints;

 setGameCameraStat('stEyeContact', faceVisible? '<i class="fa-solid fa-check me-1"></i>Visible': '<i class="fa-solid fa-circle-info me-1"></i>Move into frame', faceVisible? 'text-success': 'text-warning', true);
 setGameCameraStat('stPosture', faceVisible? (poseCameraFacing === false? 'Head turned estimate': 'Camera-facing estimate'): 'Optional - not scored', faceVisible? (poseCameraFacing === false? 'text-warning': 'text-success'): 'text-secondary');
 setGameCameraStat('stGesture', handLandmarks.length > 0? (gestureActive? 'Gesture movement': handLandmarks.length + ' hand(s) visible'): 'Hands not visible', handLandmarks.length > 0? 'text-success': 'text-secondary');
 setGameCameraStat('stPose', shouldersVisible? (shouldersLevel && uprightPosture!== false? 'Balanced upper body': 'Posture cue available'): (poseDetected? 'Partial pose estimate': 'Pose not detected'), shouldersVisible? (shouldersLevel && uprightPosture!== false? 'text-success': 'text-warning'): 'text-secondary');
 setGameCameraStat('stMovement', movementScore === null? 'Calibrating': (movementScore >= 45? 'Higher movement': 'Steady'), movementScore === null? 'text-secondary': (movementScore >= 45? 'text-warning': 'text-success'));
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
 if (token!== questionSpeechToken) return;
 clearTimeout(autoStartAfterQuestionTimer);
 if (!isVoiceTranscriptionMode()) return;
 if (isVoiceOnlySession && document.getElementById('holdToTalkBtn')) return;

 autoStartAfterQuestionTimer = setTimeout(() => {
 if (token!== questionSpeechToken || isRecording) return;
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
 stopRecording();
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
 const avatarHead = document.getElementById('aiAvatarHead');
 if (avatarHead) avatarHead.style.setProperty('--avatar-ring-color', '#34d399');
 
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
 const avatarHead = document.getElementById('aiAvatarHead');
 if (avatarHead) avatarHead.style.setProperty('--avatar-ring-color', '#8b5cf6');
 if(visualizerInterval) clearInterval(visualizerInterval);
 scheduleAutoTranscriptionStart(token);
 };

 window.speechSynthesis.speak(utterance);
 } else {
 scheduleAutoTranscriptionStart(token);
 }
 }

 function startChallengeSession() {
 const workspaceWrapper = document.getElementById('workspaceWrapper');
 if (workspaceWrapper) workspaceWrapper.style.display = 'block';
 enterGameMatchFullscreen({ auto: true });
 if (cameraCoachingEnabled) initCamera();
 
 if(isVoiceTranscriptionMode()) {
 const voiceControls = document.getElementById('voiceControls');
 const voiceAnalyticsPanel = document.getElementById('voiceAnalyticsPanel');
 if (voiceControls) voiceControls.style.display = 'block';
 if (voiceAnalyticsPanel) voiceAnalyticsPanel.style.display = 'block';
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
 
 const answerTextarea = document.getElementById('answerTextarea');
 const sessionNotes = document.getElementById('sessionNotes');
 if (answerTextarea) answerTextarea.addEventListener('input', triggerAnalysis);
 if (sessionNotes) sessionNotes.addEventListener('change', autoSaveState);
 }

 function loadQuestion(idx) {
 currentQIdx = idx;
 const q = questions[idx];
 
 const questionText = q.question_text || '';
 document.getElementById('aiQuestionText').innerText = questionText;
 const questionCaptionText = document.getElementById('questionCaptionText');
 if (questionCaptionText) questionCaptionText.innerText = questionText;
 document.getElementById('qCounter').innerText = (idx + 1) + '/' + questions.length;

 // Restore answer state if navigated back
 document.getElementById('answerTextarea').value = answersData[idx].text;
 resetSpeechRecognitionBufferFromTextarea();
 
 speakQuestion(questionText);
 
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
 if(isRecording || isStartingRecording) stopRecording();
 if (currentQIdx > 0) {
 loadQuestion(currentQIdx - 1);
 }
 }

 function setElementText(id, value) {
 const el = document.getElementById(id);
 if (el) el.innerText = value;
 }

 function setElementHtml(id, value) {
 const el = document.getElementById(id);
 if (el) el.innerHTML = value;
 }

 function sessionNotesValue() {
 const notes = document.getElementById('sessionNotes');
 return notes? notes.value: '';
 }

 function triggerAnalysis() {
 const text = document.getElementById('answerTextarea').value;
 const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
 const charCount = text.length;
 
 setElementText('wordCount', wordCount + ' words');
 setElementText('charCount', charCount + ' characters');

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
 setElementHtml('coachingTip', `<i class="fa-solid fa-lightbulb me-1"></i> <strong>Coach:</strong> ${tip}`);

 // Local readiness estimate shown before server-side scoring.
 let readiness = Math.min(100, Math.max(0, wordCount * 2));
 if(wordCount === 0) readiness = 0;
 setElementText('overallReadiness', readiness + '%');
 setElementText('metClarity', (readiness > 0? Math.min(100, readiness + 10): 0) + '%');
 setElementText('metRelevance', (readiness > 0? Math.min(100, readiness + 5): 0) + '%');
 setElementText('metGrammar', (readiness > 0? Math.min(100, readiness + 15): 0) + '%');
 setElementText('metProf', (readiness > 0? Math.min(100, readiness + 8): 0) + '%');

 // Local filler-word estimate.
 const fillerPattern = /\b(um|uh|like|you know|basically|i mean|sort of|kind of|literally)\b/gi;
 const matches = text.match(fillerPattern);
 const fillers = matches? matches.length: 0;
 setElementText('vaFillers', fillers);
 answersData[currentQIdx].text = text;
 answersData[currentQIdx].filler_words = fillers;
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

 function preferredRecordingMimeType() {
 if (typeof MediaRecorder === 'undefined' || typeof MediaRecorder.isTypeSupported!== 'function') {
 return '';
 }

 return [
 'audio/webm;codecs=opus',
 'audio/webm',
 'audio/mp4',
 'video/webm;codecs=opus',
 'video/webm'
 ].find(type => MediaRecorder.isTypeSupported(type)) || '';
 }

 function voiceRecordingSupported() {
 return Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && typeof MediaRecorder!== 'undefined');
 }

 function normalizeRecordingMimeType(value) {
 const type = String(value || 'audio/webm').split(';')[0].toLowerCase().trim();
 return type || 'audio/webm';
 }

 function voiceFileExtension(mimeType) {
 switch (normalizeRecordingMimeType(mimeType)) {
 case 'audio/mp4':
 case 'audio/m4a':
 case 'audio/x-m4a':
 case 'video/mp4':
 return 'm4a';
 case 'audio/mpeg':
 case 'audio/mpga':
 return 'mp3';
 case 'audio/wav':
 case 'audio/x-wav':
 return 'wav';
 case 'audio/ogg':
 return 'ogg';
 default:
 return 'webm';
 }
 }

 function voiceFileName(questionIndex, mimeType) {
 return 'challenge-question-' + (questionIndex + 1) + '.' + voiceFileExtension(mimeType);
 }

 function stopVoiceRecordingTracks() {
 if (mediaRecorderStream) {
 mediaRecorderStream.getTracks().forEach(track => track.stop());
 }
 mediaRecorderStream = null;
 }

 async function startVoiceAudioRecording(silent = false) {
 if (!isVoiceOnlySession) return true;

 if (!voiceRecordingSupported()) {
 if (!silent) alert('Voice recording is not supported in this browser.');
 return false;
 }

 try {
 const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
 const recorderMimeType = preferredRecordingMimeType();
 const recorderOptions = recorderMimeType? { mimeType: recorderMimeType }: undefined;
 const recordingQuestionIndex = currentQIdx;

 mediaRecorderChunks = [];
 mediaRecorderStream = stream;
 mediaRecorderStartedAt = Date.now();
 mediaRecorder = new MediaRecorder(stream, recorderOptions);
 voiceRecordingStopPromise = null;

 mediaRecorder.ondataavailable = function(event) {
 if (event.data && event.data.size > 0) {
 mediaRecorderChunks.push(event.data);
 }
 };

 mediaRecorder.onstop = function() {
 const mimeType = normalizeRecordingMimeType(mediaRecorder && mediaRecorder.mimeType? mediaRecorder.mimeType: recorderMimeType);
 const duration = Math.max(
 answersData[recordingQuestionIndex].voice_duration || 0,
 Math.round((Date.now() - mediaRecorderStartedAt) / 1000)
 );

 if (mediaRecorderChunks.length > 0) {
 const blob = new Blob(mediaRecorderChunks, { type: mimeType });
 if (blob.size >= 128) {
 answersData[recordingQuestionIndex].voice_recording = {
 blob,
 mime_type: mimeType,
 filename: voiceFileName(recordingQuestionIndex, mimeType),
 duration
 };
 answersData[recordingQuestionIndex].has_voice_recording = true;
 }
 }

 stopVoiceRecordingTracks();
 mediaRecorder = null;
 mediaRecorderChunks = [];
 };

 mediaRecorder.start();
 return true;
 } catch (error) {
 console.error('Voice recording failed to start:', error);
 stopVoiceRecordingTracks();
 mediaRecorder = null;
 mediaRecorderChunks = [];
 if (!silent) alert('Microphone access is required for voice-only challenge answers.');
 return false;
 }
 }

 function stopVoiceAudioRecording() {
 if (!mediaRecorder) {
 stopVoiceRecordingTracks();
 return voiceRecordingStopPromise || Promise.resolve();
 }

 if (mediaRecorder.state === 'inactive') {
 stopVoiceRecordingTracks();
 return voiceRecordingStopPromise || Promise.resolve();
 }

 const recorder = mediaRecorder;
 let fallbackTimer = null;
 let resolveStopPromise = function() {};
 voiceRecordingStopPromise = new Promise(resolve => {
 resolveStopPromise = resolve;
 const previousOnStop = recorder.onstop;
 fallbackTimer = setTimeout(() => {
 stopVoiceRecordingTracks();
 mediaRecorder = null;
 mediaRecorderChunks = [];
 resolve();
 }, 5000);

 recorder.onstop = function(event) {
 clearTimeout(fallbackTimer);
 if (typeof previousOnStop === 'function') {
 previousOnStop.call(recorder, event);
 }
 resolve();
 };
 });

 try {
 if (typeof recorder.requestData === 'function') {
 recorder.requestData();
 }
 recorder.stop();
 } catch (error) {
 console.error('Voice recording failed to stop:', error);
 stopVoiceRecordingTracks();
 mediaRecorder = null;
 mediaRecorderChunks = [];
 if (fallbackTimer) clearTimeout(fallbackTimer);
 resolveStopPromise();
 }

 return voiceRecordingStopPromise;
 }

 function answerHasVoiceRecording(questionIndex) {
 const data = answersData[questionIndex] || {};
 return Boolean(data.has_voice_recording || (data.voice_recording && data.voice_recording.blob));
 }

 async function startRecording(options = {}) {
 const silent = options && options.silent === true;
 if(!recognition &&!isVoiceOnlySession) {
 if(!silent) alert("Speech recognition not supported in this browser.");
 return;
 }
 if (isRecording || isStartingRecording) return;

 resetSpeechRecognitionBufferFromTextarea();
 lastSpeechEnd = 0;
 isStartingRecording = true;
 stopRequestedWhileStarting = false;
 const voiceRecorderStarted = await startVoiceAudioRecording(silent);
 isStartingRecording = false;
 if (!voiceRecorderStarted) return;
 if (stopRequestedWhileStarting) {
 stopRequestedWhileStarting = false;
 stopRecording();
 return;
 }

 shouldAutoRestartRecognition = Boolean(recognition);
 isRecording = true;
 startSpeechRecognitionEngine();
 const micStartBtn = document.getElementById('micStartBtn');
 const micPauseBtn = document.getElementById('micPauseBtn');
 const micStopBtn = document.getElementById('micStopBtn');
 const recordingTimer = document.getElementById('recordingTimer');
 if (micStartBtn) micStartBtn.style.display = 'none';
 if (micPauseBtn) micPauseBtn.style.display = 'block';
 if (micStopBtn) micStopBtn.style.display = 'block';
 if (recordingTimer) recordingTimer.style.display = 'block';
 clearInterval(recTimerInterval);
 
 recTimerInterval = setInterval(() => {
 recTimerSeconds++;
 const m = Math.floor(recTimerSeconds / 60).toString().padStart(2, '0');
 const s = (recTimerSeconds % 60).toString().padStart(2, '0');
 setElementText('recordingTimer', m + ':' + s);
 setElementText('vaDuration', recTimerSeconds + 's');
 answersData[currentQIdx].voice_duration = recTimerSeconds;
 
 const wordCount = document.getElementById('answerTextarea').value.trim().split(/\s+/).filter(w=>w.length>0).length;
 let activeSeconds = recTimerSeconds - (answersData[currentQIdx].pause_count * 3);
 if (activeSeconds < 1) activeSeconds = 1;
 const wpm = Math.round((wordCount / activeSeconds) * 60);
 setElementText('vaWpm', wpm);
 answersData[currentQIdx].wpm = wpm;

 // Optional body-language detection is descriptive and never affects scoring.
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
 const micStartBtn = document.getElementById('micStartBtn');
 const micPauseBtn = document.getElementById('micPauseBtn');
 const scannerBox = document.getElementById('faceScannerBox');
 if (micStartBtn) {
 micStartBtn.style.display = 'block';
 micStartBtn.innerText = 'Resume';
 }
 if (micPauseBtn) micPauseBtn.style.display = 'none';
 if (scannerBox) scannerBox.style.display = 'none';
 }

 function stopRecording() {
 if (isStartingRecording &&!isRecording) {
 stopRequestedWhileStarting = true;
 return voiceRecordingStopPromise || Promise.resolve();
 }

 pauseRecording();
 clearTimeout(autoStartAfterQuestionTimer);
 const stopPromise = stopVoiceAudioRecording();
 const micStartBtn = document.getElementById('micStartBtn');
 const micStopBtn = document.getElementById('micStopBtn');
 const recordingTimer = document.getElementById('recordingTimer');
 if (micStartBtn) micStartBtn.innerText = 'Start';
 if (micStopBtn) micStopBtn.style.display = 'none';
 if (recordingTimer) recordingTimer.style.display = 'none';
 recTimerSeconds = 0;
 resetSpeechRecognitionBufferFromTextarea();
 return stopPromise;
 }

 async function saveCurrentAnswer(isSkipped = false) {
 if (voiceRecordingStopPromise) {
 await voiceRecordingStopPromise;
 }

 if (!isSkipped && isVoiceOnlySession &&!answerHasVoiceRecording(currentQIdx)) {
 throw new Error('Please record your voice answer before continuing.');
 }

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
 formData.append('notes', sessionNotesValue());

 const voiceRecording = answersData[currentQIdx].voice_recording;
 if (!isSkipped && isVoiceOnlySession && voiceRecording && voiceRecording.blob) {
 const duration = voiceRecording.duration || answersData[currentQIdx].voice_duration || 0;
 formData.append('voice_audio', voiceRecording.blob, voiceRecording.filename || voiceFileName(currentQIdx, voiceRecording.mime_type));
 formData.append('voice_recording_duration_seconds', duration);
 formData.append('voice_recording_transcription_status', answersData[currentQIdx].text? 'transcribed': 'recorded');
 }

 return fetch('{{ route("user.game.answer") }}', {
 method: 'POST',
 body: formData,
 headers: { 'X-Requested-With': 'XMLHttpRequest' }
 }).then(async response => {
 if (!response.ok) {
 let message = 'Answer save failed with status ' + response.status;
 try {
 const payload = await response.json();
 message = payload.message || message;
 } catch (error) {
 // Keep the status-based message when the server did not return JSON.
 }
 throw new Error(message);
 }

 if (!isSkipped && isVoiceOnlySession) {
 answersData[currentQIdx].has_voice_recording = true;
 }

 return response;
 });
 }

 function autoSaveState() {
 const formData = new FormData();
 formData.append('_token', '{{ csrf_token() }}');
 formData.append('game_session_id', gameSessionId);
 formData.append('notes', sessionNotesValue());
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
 const stopPromise = (isRecording || isStartingRecording)? stopRecording(): Promise.resolve();
 const isFinalQuestion = currentQIdx >= questions.length - 1;
 document.querySelectorAll('.next-btn-class,.skip-btn-class').forEach(el => el.disabled = true);
 if (isFinalQuestion) {
 showChallengeFinishModal('Saving your final answer...');
 }
 Promise.resolve(stopPromise).then(() => saveCurrentAnswer(false)).then(() => {
 if (currentQIdx < questions.length - 1) {
 document.querySelectorAll('.next-btn-class,.skip-btn-class').forEach(el => el.disabled = false);
 loadQuestion(currentQIdx + 1);
 } else {
 finishChallenge();
 }
 }).catch(error => {
 console.error(error);
 hideChallengeFinishModal();
 document.querySelectorAll('.next-btn-class,.skip-btn-class').forEach(el => el.disabled = false);
 alert(error.message || 'We could not save your answer. Please try again before continuing.');
 });
 }

 function skipQuestion() {
 if (isFinishingChallenge) return;
 const stopPromise = (isRecording || isStartingRecording)? stopRecording(): Promise.resolve();
 const isFinalQuestion = currentQIdx >= questions.length - 1;
 document.querySelectorAll('.next-btn-class,.skip-btn-class').forEach(el => el.disabled = true);
 if (isFinalQuestion) {
 showChallengeFinishModal('Saving this skipped answer...');
 }
 Promise.resolve(stopPromise).then(() => saveCurrentAnswer(true)).then(() => {
 if (currentQIdx < questions.length - 1) {
 document.querySelectorAll('.next-btn-class,.skip-btn-class').forEach(el => el.disabled = false);
 loadQuestion(currentQIdx + 1);
 } else {
 finishChallenge();
 }
 }).catch(error => {
 console.error(error);
 hideChallengeFinishModal();
 document.querySelectorAll('.next-btn-class,.skip-btn-class').forEach(el => el.disabled = false);
 alert('We could not save your skipped answer. Please try again before continuing.');
 });
 }

 function prevQuestion() {
 if(isRecording || isStartingRecording) stopRecording();
 if (currentQIdx > 0) {
 loadQuestion(currentQIdx - 1);
 }
 }

 function finishChallenge() {
 if (isFinishingChallenge) return;
 isFinishingChallenge = true;
 showChallengeFinishModal('Scoring your answers and preparing your result modal...');
 document.querySelectorAll('.next-btn-class,.skip-btn-class,.prev-btn-class').forEach(el => el.disabled = true);
 let video = document.getElementById('userCamera');
 if (video && video.srcObject) {
 video.srcObject.getTracks().forEach(track => track.stop());
 }
 clearInterval(timerInterval);
 document.getElementById('formDuration').value = timerSeconds;
 document.getElementById('formNotes').value = sessionNotesValue();
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

 import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21/vision_bundle.mjs').then(async ({ FilesetResolver, PoseLandmarker, HandLandmarker }) => {
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
 }).catch(err => {
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
 { element: '.ai-avatar-panel', popover: { title: 'Challenge Coach', description: 'The coach presents each challenge question and guides the Learning Game flow.', side: 'bottom', align: 'start' }},
 { element: '#answerForm', popover: { title: 'Your Response', description: 'Use the response box and voice controls to answer each challenge question.', side: 'top', align: 'start' }},
 { element: '#cameraPanel', popover: { title: 'Body-Language Detection', description: 'Camera detection checks visible framing, head, posture, hands, and movement. Camera observations never affect readiness or challenge scoring.', side: 'top', align: 'start' }}
 ];

 const stepsDesktop = [
 { element: '.ai-avatar-panel', popover: { title: 'Challenge Coach', description: 'The coach presents each challenge question and guides the Learning Game flow.', side: 'right', align: 'start' }},
 { element: '#answerForm', popover: { title: 'Your Response', description: 'Use the response box and voice controls to answer each challenge question.', side: 'right', align: 'start' }},
 { element: '#cameraPanel', popover: { title: 'Body-Language Detection', description: 'Camera detection checks visible framing, head, posture, hands, and movement. Camera observations never affect readiness or challenge scoring.', side: 'left', align: 'start' }}
 ];
 const visibleTourSteps = steps => steps.filter(step => document.querySelector(step.element));

 onboardingTour = window.createSpeakReadyTour({
 completionKey: 'onboarding_completed_learning_game_session',
 serverDetectedMobile: true,
 stepsMobile: visibleTourSteps(stepsMobile),
 stepsDesktop: visibleTourSteps(stepsDesktop),
 autoStart: false,
 });
 }

 if (typeof window.enterGameMatchFullscreen === 'function') {
 window.enterGameMatchFullscreen({ auto: true });
 }
 
 // Expose startOnboardingTour to be called after the challenge starts
 const originalStartChallenge = window.startChallengeSession;
 window.startChallengeSession = function() {
 if (typeof originalStartChallenge === 'function') {
 originalStartChallenge.apply(this, arguments);
 }

 if (onboardingTour &&!onboardingTour.isCompleted()) {
 setTimeout(() => {
 onboardingTour.start();
 }, 1000);
 }
 };

 // Learning Game countdown logic
 let countdownValue = 3;
 const countdownText = document.getElementById('countdown-text');
 const overlay = document.getElementById('get-ready-overlay');
 if (!countdownText ||!overlay) {
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
