@extends('desktop.layouts.app')
@section('title', 'Interview Setup')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/interview/setup.css?v=29') }}" data-page-style="interview-setup">
<link rel="stylesheet" href="{{ asset('css/desktop/interview/setup-2.css?v=2') }}" data-page-style="interview-setup-2">
@endpush

@section('content')
@php
 $sourceDatasets = $sourceDatasets?? [];
 $targetScopes = $targetScopes?? config('speakready_scope', []);
 $jobPositionOptionGroups = collect($targetScopes['job_positions']?? [])
 ->map(fn ($positions) => collect(is_array($positions)? $positions: [$positions])
 ->map(fn ($position) => trim((string) $position))
 ->filter()
 ->values()
 ->all())
 ->filter(fn (array $positions) => $positions!== []);
 $jobPositionOptions = collect($targetScopes['job_positions']?? [])
 ->flatMap(fn ($positions) => is_array($positions)? $positions: [$positions])
 ->map(fn ($position) => trim((string) $position))
 ->filter()
 ->unique(fn (string $position) => strtolower($position))
 ->values();
 $schoolProgramOptionGroups = collect($targetScopes['school_programs']?? [])
 ->map(fn ($programs) => collect(is_array($programs)? $programs: [$programs])
 ->map(fn ($program) => trim((string) $program))
 ->filter()
 ->values()
 ->all())
 ->filter(fn (array $programs) => $programs!== []);
 $schoolProgramOptions = collect($targetScopes['school_programs']?? [])
 ->flatMap(fn ($programs) => is_array($programs)? $programs: [$programs])
 ->map(fn ($program) => trim((string) $program))
 ->filter()
 ->unique(fn (string $program) => strtolower($program))
 ->values();
 $interviewCategories = ($categories?? collect())
 ->filter(function ($category): bool {
 $title = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('/', ' / ', (string) $category->title))?? ''));

 if (
 str_contains($title, 'bpo')
 || str_contains($title, 'customer')
 || str_contains($title, 'programming')
 || str_contains($title, 'technical')
 || str_contains($title, 'scholar')
 || preg_match('/\bit\b/', $title)
 ) {
 return false;
 }

 return str_contains($title, 'job interview')
 || str_contains($title, 'general job')
 || str_contains($title, 'school admission')
 || str_contains($title, 'college admission')
 || str_contains($title, 'admission interview');
 })
 ->values();

 $scenarioLabelForCategory = function (?string $categoryTitle): string {
 $title = trim((string) $categoryTitle);
 $displayTitle = trim(preg_replace('/\s*\/\s*/', ' / ', $title)?? $title);
 $key = strtolower(trim(preg_replace('/\s+/', ' ', $displayTitle)?? $displayTitle));
 $knownLabels = [
 'job interview' => 'Job Interviews',
 'general job interview' => 'Job Interviews',
 'college admission' => 'School Admission Interviews',
 'college admission interview' => 'School Admission Interviews',
 'school admission' => 'School Admission Interviews',
 'school admission interview' => 'School Admission Interviews',
 ];

 if (isset($knownLabels[$key])) {
 return $knownLabels[$key];
 }

 if ($displayTitle === '') {
 return 'Job Interviews';
 }

 if (! str_contains($key, 'interview')) {
 $displayTitle.= ' Interview';
 }

 return $displayTitle;
 };

 $focusForCategory = function (?string $categoryTitle, string $label): string {
 $title = strtolower((string) $categoryTitle);

 if (str_contains($title, 'job') &&! str_contains($title, 'bpo') &&! str_contains($title, 'customer')) {
 return 'Job Interview';
 }

 return $label;
 };

 $scenarioOptions = $interviewCategories
 ->map(function ($category) use ($sourceDatasets, $scenarioLabelForCategory, $focusForCategory) {
 $key = \App\Services\QuestionDatasetProvider::defaultKeyForCategory($category->title);
 $sourceDataset = $sourceDatasets[$key]?? collect($sourceDatasets)->first()?? [];
 $label = $scenarioLabelForCategory($category->title);
 $sourceSummary = collect($sourceDataset['sources']?? [])
 ->pluck('name')
 ->take(3)
 ->implode(', ');

 return [
 'category_id' => $category->id,
 'label' => $label,
 'focus' => $focusForCategory($category->title, $label),
 'context_label' => $label,
 'source_summary' => $sourceSummary?: 'career and education sources',
 ];
 })
 ->values();
 $firstScenario = $scenarioOptions->first();
 $selectedCategoryId = (int) old('category_id', $firstScenario['category_id']?? 0);
 $selectedScenario = $scenarioOptions->first(fn ($scenario) => (int) $scenario['category_id'] === $selectedCategoryId)?? $scenarioOptions->first();
 $selectedScenarioText = strtolower(trim((string) (($selectedScenario['focus']?? '').' '.($selectedScenario['context_label']?? '').' '.($selectedScenario['label']?? ''))));
 $targetFieldMode = str_contains($selectedScenarioText, 'school admission')
 || str_contains($selectedScenarioText, 'college admission')
 || str_contains($selectedScenarioText, 'admission interview')
 ? 'school'
 : 'job';
 $targetFieldCopies = [
 'job' => [
 'label' => 'Target Position',
 'summary_label' => 'Position:',
 'placeholder' => 'Choose a target position',
 'required_message' => 'Enter the target position before continuing.',
 'calibration_title' => 'Southern Leyte role-calibrated practice',
 ],
 'school' => [
 'label' => 'Target Program',
 'summary_label' => 'Program:',
 'placeholder' => 'Choose a target program',
 'required_message' => 'Enter the target program before continuing.',
 'calibration_title' => 'program-calibrated practice',
 ],
 ];
 $targetFieldCopy = $targetFieldCopies[$targetFieldMode];
 $targetPositionDefault = old(
 'target_position',
 ''
 );
 $setupDefaults = [
 'difficulty' => old('difficulty', 'medium'),
 'num_questions' => (string) old('num_questions', 10),
 'time_limit' => (string) old('time_limit', 0),
 'interview_focus' => old('interview_focus', $selectedScenario['focus']?? 'Job Interview'),
 'ai_assistance_level' => old('ai_assistance_level', 'standard'),
 'live_feedback_mode' => old('live_feedback_mode', 'coaching'),
 'response_mode' => old('response_mode', 'voice'),
 ];
 $selectedQuestionTypes = old('question_types', ['Behavioral', 'Situational']);
 $hasScenarioOptions = $scenarioOptions->isNotEmpty();
@endphp

<div class="db-section active setup-step-mode" id="sec-interview-setup">
 <div class="setup-hero">
 <div class="setup-hero-inner">
 <span class="setup-hero-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" role="img">
 <rect x="5" y="3.5" width="14" height="17" rx="2.5" fill="none" stroke="currentColor" stroke-width="2"/>
 <path d="M9 8l1.4 1.4L13.5 6.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
 <path d="M9 13l1.4 1.4 3.1-2.9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
 <path d="M15 8h1.5M15 13h1.5M8 18h8.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
 </svg>
 </span>
 <div class="setup-hero-copy">
 <h4 class="setup-hero-title text-gradient-primary">
 Interview Setup
 </h4>
 <p class="setup-hero-subtitle">Practice a role-focused mock interview with local HR screening, role-fit, and communication expectations.</p>
 </div>
 </div>
 <svg class="setup-hero-art" viewBox="0 0 220 150" aria-hidden="true" role="img">
 <defs>
 <linearGradient id="setupArtPanel" x1="34" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
 <stop stop-color="#DBEAFE"/>
 <stop offset="1" stop-color="#ECFEFF"/>
 </linearGradient>
 <linearGradient id="setupArtBlue" x1="64" y1="20" x2="154" y2="120" gradientUnits="userSpaceOnUse">
 <stop stop-color="#3B82F6"/>
 <stop offset="1" stop-color="#06B6D4"/>
 </linearGradient>
 </defs>
 <rect class="setup-art-panel" x="32" y="20" width="156" height="108" rx="18" fill="url(#setupArtPanel)" stroke="#BFDBFE" stroke-width="3"/>
 <rect x="51" y="36" width="52" height="8" rx="4" fill="#60A5FA"/>
 <rect x="51" y="54" width="118" height="12" rx="6" fill="#DBEAFE" stroke="#BFDBFE" stroke-width="2"/>
 <rect x="51" y="75" width="92" height="12" rx="6" fill="#E0F2FE" stroke="#BAE6FD" stroke-width="2"/>
 <rect x="51" y="96" width="68" height="12" rx="6" fill="#EEF2FF" stroke="#C7D2FE" stroke-width="2"/>
 <circle class="setup-art-check" cx="164" cy="46" r="22" fill="url(#setupArtBlue)"/>
 <path d="M155 46l6 6 13-15" fill="none" stroke="#FFFFFF" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
 <path d="M130 102h33" stroke="#06B6D4" stroke-width="8" stroke-linecap="round"/>
 <path d="M122 102l5 5 11-14" fill="none" stroke="#2563EB" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
 <path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
 <path class="setup-art-spark" d="M190 30l9-9m-1 28l13-2" stroke="#38BDF8" stroke-width="5" stroke-linecap="round" opacity=".55"/>
 <path class="setup-art-spark" d="M24 58l-11-7m19 55l-14 3" stroke="#38BDF8" stroke-width="5" stroke-linecap="round" opacity=".55"/>
 </svg>
 </div>

 @if($errors->any())
 <div class="alert alert-danger" style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#f87171;padding:10px;border-radius:10px;margin-bottom:15px;font-size:.85rem">
 <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $errors->first() }}
 </div>
 @endif

 <form action="{{ route('interview.start') }}" method="POST" id="setupForm" data-sr-no-transition="true">
 @csrf
 <div class="row g-4">
 <!-- Left Column: Form Settings -->
 <div class="col-lg-8" id="setup-left-col">
 <div class="setup-stepper" id="setupStepper" aria-label="Interview setup steps">
 <div class="setup-stepper-track" id="setupStepperTrack"></div>
 <div class="setup-stepper-actions">
 <button type="button" class="setup-step-btn" id="setupStepPrev"><i class="fa-solid fa-arrow-left"></i> Back</button>
 <button type="submit" id="btn-start-interview" class="setup-step-btn setup-start-button btn-shine" aria-label="Start Interview" title="Start Interview" data-default-label='Start <i class="fa-solid fa-play ms-2"></i>' data-loading-label='Starting <i class="fa-solid fa-spinner fa-spin ms-2"></i>' hidden>
 Start <i class="fa-solid fa-play ms-2"></i>
 </button>
 <button type="button" class="setup-step-btn primary" id="setupStepNext">Next <i class="fa-solid fa-arrow-right"></i></button>
 </div>
 </div>

 <!-- Basic Info -->
 <div class="setup-panel setup-details-card setup-step-active animate-fade-up delay-100" id="panel-basic">
 <div class="setup-details-card-head">
 <div class="setup-details-icon" aria-hidden="true">
 <i class="fa-solid fa-briefcase"></i>
 </div>
 <div>
 <h5 class="setup-details-card-title">Interview Details</h5>
 </div>
 </div>

 <div class="setup-card-fields">
 <div class="setup-card-field">
 <label class="setup-card-label" for="valScenario">
 <span class="setup-card-label-icon" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
 Practice Scenario
 </label>
 <div class="setup-select-wrap">
 <select class="oinp setup-input" name="category_id" id="valScenario" aria-describedby="scenarioHelp{{ $hasScenarioOptions? '': ' scenarioEmptyState' }}" aria-invalid="{{ $hasScenarioOptions? 'false': 'true' }}" required>
 @forelse($scenarioOptions as $scenario)
 <option value="{{ $scenario['category_id'] }}"
 data-focus="{{ $scenario['focus'] }}"
 data-context-label="{{ $scenario['context_label'] }}"
 data-source-summary="{{ $scenario['source_summary'] }}"
 {{ $selectedScenario && (int) $selectedScenario['category_id'] === (int) $scenario['category_id']? 'selected': '' }}>
 {{ $scenario['label'] }}
 </option>
 @empty
 <option value="" selected>No active interview scenarios available</option>
 @endforelse
 </select>
 </div>
 <input type="hidden" name="interview_focus" id="valFocus" value="{{ $setupDefaults['interview_focus'] }}" class="setup-input">
 <div class="desc-text" id="scenarioHelp">Choose either job interviews or school admission interviews.</div>
 @unless($hasScenarioOptions)
 <div class="setup-inline-error setup-inline-error-visible" id="scenarioEmptyState" role="alert">No active interview scenarios are available. Ask an admin to activate at least one core category before starting.</div>
 @endunless
 </div>

 <div class="setup-card-field">
 <label class="setup-card-label" for="targetPositionDropdownButton">
 <span class="setup-card-label-icon" aria-hidden="true"><i class="fa-solid fa-bullseye-arrow"></i></span>
 <span id="targetPositionLabelText">{{ $targetFieldCopy['label'] }}</span>
 </label>
 <input type="hidden" class="setup-input setup-target-hidden-input" name="target_position" id="valPosition" value="{{ $targetPositionDefault }}" required data-selected-target="{{ $targetPositionDefault }}" data-target-kind="{{ $targetFieldMode }}">
 <div class="setup-target-dropdown" data-target-dropdown>
 <button type="button" class="oinp setup-target-trigger" id="targetPositionDropdownButton" aria-haspopup="true" aria-expanded="false" aria-controls="targetPositionDropdownMenu" aria-describedby="targetPositionError">
 <span class="setup-target-trigger-text {{ $targetPositionDefault === ''? 'setup-target-placeholder': '' }}" id="targetPositionDropdownLabel">{{ $targetPositionDefault !== ''? $targetPositionDefault: $targetFieldCopy['placeholder'] }}</span>
 <i class="fa-solid fa-chevron-down setup-target-trigger-icon" aria-hidden="true"></i>
 </button>
 <div class="setup-target-menu" id="targetPositionDropdownMenu" data-target-dropdown-menu role="list" aria-labelledby="targetPositionLabelText" hidden>
 @foreach(['job' => $jobPositionOptionGroups, 'school' => $schoolProgramOptionGroups] as $targetKind => $targetGroups)
 @foreach($targetGroups as $groupLabel => $targetChoices)
 <div class="setup-target-choice-group" data-target-dropdown-group-kind="{{ $targetKind }}" {{ $targetFieldMode === $targetKind? '': 'hidden' }}>
 <div class="setup-target-choice-group-title">{{ $groupLabel }}</div>
 <div class="setup-target-choice-list">
 @foreach($targetChoices as $targetChoice)
 <button type="button" class="setup-target-choice" data-target-dropdown-choice-kind="{{ $targetKind }}" data-target-dropdown-choice-value="{{ $targetChoice }}" aria-pressed="{{ $targetFieldMode === $targetKind && strcasecmp($targetPositionDefault, $targetChoice) === 0? 'true': 'false' }}">
 <span>{{ $targetChoice }}</span>
 <i class="fa-solid fa-check" aria-hidden="true"></i>
 </button>
 @endforeach
 </div>
 </div>
 @endforeach
 @endforeach
 </div>
 </div>
 <div class="setup-inline-error" id="targetPositionError" role="alert" hidden>{{ $targetFieldCopy['required_message'] }}</div>
 </div>

 <div class="setup-calibrated-simple">
 <div class="setup-calibrated-icon" aria-hidden="true">
 <i class="fa-solid fa-database"></i>
 </div>
 <div>
 <h6 id="targetCalibrationTitle">{{ $targetFieldCopy['calibration_title'] }}</h6>
 <p><strong>Sources:</strong> <span id="sourceSummary">{{ $selectedScenario['source_summary']?? 'career and education sources' }}</span></p>
 </div>
 </div>
 </div>
 </div>

 <!-- Interview Structure -->
 <div class="setup-panel setup-structure-card animate-fade-up delay-300" id="panel-structure">
 <div class="setup-structure-head">
 <div class="setup-structure-head-icon" aria-hidden="true">
 <i class="fa-solid fa-layer-group"></i>
 </div>
 <h5 class="setup-structure-title">Interview Structure</h5>
 </div>

 <div class="setup-structure-section-title">Difficulty Level</div>
 <div class="structure-difficulty-list">
 <label class="structure-difficulty-card">
 <input type="radio" name="difficulty" value="easy" class="setup-input" {{ $setupDefaults['difficulty'] === 'easy'? 'checked': '' }}>
 <span>
 <span class="structure-difficulty-title">Easy</span>
 <span class="structure-difficulty-desc">Basic and introductory questions</span>
 </span>
 <span class="structure-difficulty-icon" aria-hidden="true"><i class="fa-solid fa-signal"></i></span>
 </label>
 <label class="structure-difficulty-card">
 <input type="radio" name="difficulty" value="medium" class="setup-input" {{ $setupDefaults['difficulty'] === 'medium'? 'checked': '' }}>
 <span>
 <span class="structure-difficulty-title">Medium</span>
 <span class="structure-difficulty-desc">Common interview questions</span>
 </span>
 <span class="structure-difficulty-icon" aria-hidden="true"><i class="fa-solid fa-star"></i></span>
 </label>
 <label class="structure-difficulty-card">
 <input type="radio" name="difficulty" value="hard" class="setup-input" {{ $setupDefaults['difficulty'] === 'hard'? 'checked': '' }}>
 <span>
 <span class="structure-difficulty-title">Hard</span>
 <span class="structure-difficulty-desc">Advanced and situational questions</span>
 </span>
 <span class="structure-difficulty-icon" aria-hidden="true"><i class="fa-solid fa-shield-alt"></i></span>
 </label>
 </div>

 <div class="structure-select-grid">
 <div>
 <label class="olbl" for="valNumQuestions">Number of Questions</label>
 <div class="structure-select-wrap">
 <select class="oinp setup-input" name="num_questions" id="valNumQuestions">
 <option value="1" {{ $setupDefaults['num_questions'] === '1'? 'selected': '' }}>1 Question</option>
 <option value="3" {{ $setupDefaults['num_questions'] === '3'? 'selected': '' }}>3 Questions</option>
 <option value="5" {{ $setupDefaults['num_questions'] === '5'? 'selected': '' }}>5 Questions</option>
 <option value="10" {{ $setupDefaults['num_questions'] === '10'? 'selected': '' }}>10 Questions</option>
 <option value="15" {{ $setupDefaults['num_questions'] === '15'? 'selected': '' }}>15 Questions</option>
 <option value="20" {{ $setupDefaults['num_questions'] === '20'? 'selected': '' }}>20 Questions</option>
 <option value="25" {{ $setupDefaults['num_questions'] === '25'? 'selected': '' }}>25 Questions</option>
 <option value="30" {{ $setupDefaults['num_questions'] === '30'? 'selected': '' }}>30 Questions</option>
 </select>
 </div>
 </div>
 <div>
 <label class="olbl" for="valTimeLimit">Time Limit</label>
 <div class="structure-select-wrap">
 <select class="oinp setup-input" name="time_limit" id="valTimeLimit">
 <option value="0" {{ $setupDefaults['time_limit'] === '0'? 'selected': '' }}>No Limit</option>
 <option value="1" {{ $setupDefaults['time_limit'] === '1'? 'selected': '' }}>1 Minute per Question</option>
 <option value="2" {{ $setupDefaults['time_limit'] === '2'? 'selected': '' }}>2 Minutes per Question</option>
 <option value="3" {{ $setupDefaults['time_limit'] === '3'? 'selected': '' }}>3 Minutes per Question</option>
 </select>
 </div>
 </div>
 </div>
 </div>

 <div class="setup-panel setup-inclusive-card animate-fade-up delay-300" id="panel-inclusive">
 <div class="setup-inclusive-head">
 <div class="setup-inclusive-head-icon" aria-hidden="true">
 <i class="fa-solid fa-video"></i>
 </div>
 <h5 class="setup-inclusive-title">Camera Detection</h5>
 </div>
 <p class="setup-inclusive-copy">Turn camera-based body-language detection on or off for this interview.</p>
 @php
 $inclusive = Auth::user()->profile?->inclusive_preferences?? [];
 $cameraDetectionOn = filter_var(
 old('camera_detection', old('camera_coaching', data_get($inclusive, 'camera_detection', data_get($inclusive, 'camera_coaching', false)))),
 FILTER_VALIDATE_BOOLEAN
 );
 @endphp
 <input type="hidden" name="separate_language_scoring" value="0">
 <input type="hidden" name="extended_time" value="0">
 <input type="hidden" name="captions" value="0">
 <input type="hidden" name="reduced_distraction" value="0">
 <input type="hidden" name="simplified_questions" value="0">
 <div class="inclusive-option-list camera-mode-list" role="radiogroup" aria-label="Camera body-language detection">
 <label class="inclusive-option camera-mode-option">
 <input type="radio" name="camera_detection" value="1" class="setup-input" {{ $cameraDetectionOn? 'checked': '' }}>
 <span class="camera-mode-copy">
 <strong>Camera On</strong>
 <small>Detects body language</small>
 </span>
 </label>
 <label class="inclusive-option camera-mode-option">
 <input type="radio" name="camera_detection" value="0" class="setup-input" {{! $cameraDetectionOn? 'checked': '' }}>
 <span class="camera-mode-copy">
 <strong>Camera Off</strong>
 <small>No body-language detection</small>
 </span>
 </label>
 </div>
 <div class="inclusive-note">
 <i class="fa-solid fa-info" aria-hidden="true"></i>
 <span><strong>Important:</strong> Camera On enables visible framing, head alignment, shoulder/posture cues, and movement steadiness detection. Camera Off does not start body-language detection.</span>
 </div>
 </div>

 <!-- Content & Assistance -->
 <div class="setup-panel setup-assistance-card animate-fade-up delay-400" id="panel-content">
 <div class="assistance-head">
 <div class="assistance-head-icon" aria-hidden="true">
 <i class="fa-solid fa-brain"></i>
 </div>
 <h5 class="assistance-title">Content & Assistance</h5>
 </div>

 <div class="assistance-stack">
 <div class="assistance-field assistance-level-field">
 <label class="olbl" for="valAssistance">AI Assistance Level</label>
 <div class="assistance-select-wrap">
 <select class="oinp setup-input" name="ai_assistance_level" id="valAssistance">
 <option value="beginner" {{ $setupDefaults['ai_assistance_level'] === 'beginner'? 'selected': '' }}>Beginner Mode (More hints & feedback)</option>
 <option value="standard" {{ $setupDefaults['ai_assistance_level'] === 'standard'? 'selected': '' }}>Standard Mode (Balanced experience)</option>
 <option value="challenge" {{ $setupDefaults['ai_assistance_level'] === 'challenge'? 'selected': '' }}>Challenge Mode (No hints, harder follow-ups)</option>
 </select>
 </div>
 </div>

 <div class="assistance-field assistance-feedback-field">
 <label class="olbl" for="valFeedbackMode">Live Feedback Mode</label>
 <div class="assistance-select-wrap">
 <select class="oinp setup-input" name="live_feedback_mode" id="valFeedbackMode">
 <option value="coaching" {{ $setupDefaults['live_feedback_mode'] === 'coaching'? 'selected': '' }}>Coaching On</option>
 <option value="real_interview" {{ $setupDefaults['live_feedback_mode'] === 'real_interview'? 'selected': '' }}>Real Interview Mode</option>
 </select>
 </div>
 </div>

 <div class="assistance-field assistance-question-field">
 <label class="olbl" id="questionTypesLabel">Question Types</label>
 <div class="assistance-question-list" id="questionTypeGroup" role="group" aria-labelledby="questionTypesLabel" aria-describedby="questionTypeError" aria-invalid="false">
 @foreach([
 'Behavioral' => 'fa-regular fa-message',
 'Situational' => 'fa-regular fa-user',
 'Technical' => 'fa-solid fa-code',
 'Personal' => 'fa-regular fa-user-circle',
 ] as $questionType => $questionIcon)
 <label class="assistance-question-card">
 <input type="checkbox" name="question_types[]" value="{{ $questionType }}" {{ in_array($questionType, $selectedQuestionTypes, true)? 'checked': '' }}>
 <span class="assistance-question-icon" aria-hidden="true"><i class="{{ $questionIcon }}"></i></span>
 <span class="assistance-question-text">{{ $questionType }} Questions</span>
 </label>
 @endforeach
 </div>
 <div class="setup-inline-error" id="questionTypeError" role="alert" hidden>Select at least one question type.</div>
 </div>
 </div>
 </div>

 <!-- Response Mode -->
 <div class="setup-panel setup-response-card animate-fade-up delay-400" id="panel-response">
 <div class="response-head">
 <div class="response-head-icon" aria-hidden="true">
 <i class="fa-solid fa-microphone"></i>
 </div>
 <h5 class="response-title">Response Mode</h5>
 </div>
 <div class="response-mode-list">
 <label class="response-mode-card">
 <input type="radio" name="response_mode" value="text" class="setup-input" {{ $setupDefaults['response_mode'] === 'text'? 'checked': '' }}>
 <span>
 <span class="response-mode-title">Text Mode</span>
 <span class="response-mode-desc">Type your answers manually</span>
 </span>
 </label>
 <label class="response-mode-card">
 <input type="radio" name="response_mode" value="voice" class="setup-input" {{ $setupDefaults['response_mode'] === 'voice'? 'checked': '' }}>
 <span>
 <span class="response-mode-title">Voice Mode</span>
 <span class="response-mode-desc">Voice-only microphone answers, no text transcript</span>
 </span>
 </label>
 <label class="response-mode-card">
 <input type="radio" name="response_mode" value="hybrid" class="setup-input" {{ $setupDefaults['response_mode'] === 'hybrid'? 'checked': '' }}>
 <span>
 <span class="response-mode-title">Hybrid Mode</span>
 <span class="response-mode-desc">Voice-to-text with manual editing</span>
 </span>
 </label>
 </div>
 </div>

 </div>

 <!-- Right Column: Live Summary -->
 <div class="col-lg-4 animate-fade-up delay-200">
 <div class="setup-summary-wrap">
 <div class="setup-panel setup-summary-panel" id="panel-summary">
 <h5 class="setup-summary-title"><i class="fa-solid fa-clipboard-list me-2"></i> Interview Summary</h5>

 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-globe"></i></span>
 <span class="summary-label">Scenario:</span>
 <span class="summary-val summary-val-pending" id="sumScenario">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-briefcase"></i></span>
 <span class="summary-label" id="summaryPositionLabel">{{ $targetFieldCopy['summary_label'] }}</span>
 <span class="summary-val summary-val-pending" id="sumPosition">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-signal"></i></span>
 <span class="summary-label">Difficulty:</span>
 <span class="summary-val summary-val-pending" id="sumDifficulty">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-circle-question"></i></span>
 <span class="summary-label">Questions:</span>
 <span class="summary-val summary-val-pending" id="sumQuestions">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-microphone"></i></span>
 <span class="summary-label">Response Mode:</span>
 <span class="summary-val summary-val-pending" id="sumResponse">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-video"></i></span>
 <span class="summary-label">Camera Detection:</span>
 <span class="summary-val summary-val-pending" id="sumCameraDetection">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-brain"></i></span>
 <span class="summary-label">Assistance:</span>
 <span class="summary-val summary-val-pending" id="sumAssistance">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-circle-question"></i></span>
 <span class="summary-label">Question Types:</span>
 <span class="summary-val summary-val-pending" id="sumQuestionTypes">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-message"></i></span>
 <span class="summary-label">Live Feedback:</span>
 <span class="summary-val summary-val-pending" id="sumFeedbackMode">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
 <span class="summary-label">Est. Duration:</span>
 <span class="summary-val text-success summary-val-pending" id="sumDuration">Not set yet</span>
 </div>
 </div>
 </div>
 </div>
 </div>
 </form>

 <div id="setupTransitionOverlay" class="finish-transition-overlay" role="status" aria-live="polite" aria-atomic="true">
 <div class="finish-loading-wrapper">
 <div class="finish-loading-circle"></div>
 <img src="{{ asset('img/logo.png') }}" alt="Loading interview">
 </div>
 <h4>Interview Ready</h4>
 <p>Please wait while we begin or resume your customized interview session.</p>
 </div>

 <div class="modal fade setup-alert-modal" id="targetPositionAlertModal" tabindex="-1" aria-labelledby="targetPositionAlertTitle" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered">
 <div class="modal-content">
 <div class="modal-header">
 <h5 class="modal-title" id="targetPositionAlertTitle">Target position required</h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 <div class="modal-body" id="targetPositionAlertMessage">
 Enter the target position before continuing.
 </div>
 <div class="modal-footer">
 <button type="button" class="btn setup-alert-btn" data-bs-dismiss="modal">OK</button>
 </div>
 </div>
 </div>
 </div>
</div>

<script>
 const setupRequiredFieldIds = [
 'valScenario',
 'valPosition',
 'valNumQuestions',
 'valTimeLimit',
 'valAssistance',
 'valFeedbackMode',
 ];
 const setupPanelRequiredFields = {
 'panel-basic': ['valScenario', 'valPosition'],
 'panel-structure': ['valNumQuestions', 'valTimeLimit'],
 'panel-content': ['valAssistance', 'valFeedbackMode'],
 };
 let setupValidationVisible = false;
 const setupFieldErrorIds = {
 valPosition: 'targetPositionError',
 };
 let targetPositionAlertVisible = false;
 const setupScenarioMismatchMessages = {
 job: {
 title: 'Use a job target',
 message: 'Job Interview accepts job-related target positions only. Enter a Southern Leyte job role like Administrative Assistant / LGU Staff, Teacher / Instructor, or Customer Service Representative, or choose School Admission Interviews for school programs like BS Information Technology.',
 },
 school: {
 title: 'Use a school target',
 message: 'School Admission accepts school-related target programs only. Enter a Version 1 program like BS Information Technology, BS Nursing, or BS Agriculture, or choose Job Interviews for Southern Leyte roles like Administrative Assistant / LGU Staff or Software Developer.',
 },
 recommendJob: {
 title: 'Proceed with Job Interview',
 message: 'This looks like a job-related target position. Recommendation: proceed with Job Interview. School Admission accepts school-related target programs only.',
 },
 recommendSchool: {
 title: 'Proceed with School Admission',
 message: 'This looks like a school-related target program. Recommendation: proceed with School Admission Interviews. Job Interview accepts job-related target positions only.',
 },
 };
 const setupVagueTargetRecommendations = [
 {
 kind: 'job',
 terms: ['clean', 'cleaning'],
 jobScenario: {
 title: 'Use a specific job target',
 message: 'Clean is too broad for a target position. Recommendation: use a specific job target such as Cleaner, Janitor, Housekeeping Attendant, or Janitorial Services, then proceed with Job Interview.',
 },
 schoolScenario: {
 title: 'Proceed with Job Interview',
 message: 'Clean looks related to cleaning work. Recommendation: proceed with Job Interview using a specific target position such as Cleaner, Janitor, Housekeeping Attendant, or Janitorial Services.',
 },
 },
 ];
 const setupTargetFieldCopies = {
 job: {
 label: 'Target Position',
 summaryLabel: 'Position:',
 placeholder: 'Choose a target position',
 requiredTitle: 'Target position required',
 requiredMessage: 'Enter the target position before continuing.',
 calibrationTitle: 'Southern Leyte role-calibrated practice',
 },
 school: {
 label: 'Target Program',
 summaryLabel: 'Program:',
 placeholder: 'Choose a target program',
 requiredTitle: 'Target program required',
 requiredMessage: 'Enter the target program before continuing.',
 calibrationTitle: 'program-calibrated practice',
 },
 };
 const setupTargetChoiceGroups = @json([
 'job' => $jobPositionOptionGroups,
 'school' => $schoolProgramOptionGroups,
 ]);

 function setSetupFieldInvalid(field, invalid) {
 if (!field) return;
 field.classList.toggle('setup-field-invalid', invalid);
 field.setAttribute('aria-invalid', invalid? 'true': 'false');

 if (field.id === 'valPosition') {
 const trigger = document.getElementById('targetPositionDropdownButton');
 if (trigger) {
 trigger.classList.toggle('setup-field-invalid', invalid);
 trigger.setAttribute('aria-invalid', invalid? 'true': 'false');
 }
 }
 }

 function setSetupFieldError(fieldId, visible) {
 if (fieldId === 'valPosition') {
 visible = false;
 }
 const error = document.getElementById(setupFieldErrorIds[fieldId]);
 if (!error) return;
 error.hidden =!visible;
 error.classList.toggle('setup-inline-error-visible', visible);
 }

 function showTargetPositionAlert(message = null, title = null) {
 const modalElement = document.getElementById('targetPositionAlertModal');
 if (!modalElement || targetPositionAlertVisible) return;
 const targetCopy = currentSetupTargetFieldCopy();
 message = message || targetCopy.requiredMessage;
 title = title || targetCopy.requiredTitle;
 const titleElement = document.getElementById('targetPositionAlertTitle');
 const messageElement = document.getElementById('targetPositionAlertMessage');

 if (titleElement) titleElement.textContent = title;
 if (messageElement) messageElement.textContent = message;

 if (modalElement.parentElement!== document.body) {
 document.body.appendChild(modalElement);
 }

 targetPositionAlertVisible = true;
 modalElement.addEventListener('hidden.bs.modal', () => {
 targetPositionAlertVisible = false;
 document.getElementById('targetPositionDropdownButton')?.focus();
 }, { once: true });

 if (window.bootstrap?.Modal) {
 window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
 return;
 }

 alert(message);
 targetPositionAlertVisible = false;
 }

 function normalizeSetupScenarioText(value) {
 return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').replace(/\s+/g, ' ').trim();
 }

 function setupTextContainsPhrase(normalizedText, phrases) {
 const haystack = ` ${normalizedText} `;
 return phrases.some((phrase) => {
 const needle = normalizeSetupScenarioText(phrase);
 return needle.length > 0 && haystack.includes(` ${needle} `);
 });
 }

 function vagueSetupTargetRecommendation() {
 const target = normalizeSetupScenarioText(document.getElementById('valPosition')?.value);

 if (!target) return null;

 return setupVagueTargetRecommendations.find((recommendation) => recommendation.terms.includes(target)) || null;
 }

 function selectedSetupScenarioKind() {
 const scenarioSelect = document.getElementById('valScenario');
 const selectedOption = scenarioSelect?.options?.[scenarioSelect.selectedIndex];
 const scenarioText = normalizeSetupScenarioText([
 selectedOption?.dataset.focus,
 selectedOption?.dataset.contextLabel,
 selectedOption?.text,
 ].filter(Boolean).join(' '));

 if (setupTextContainsPhrase(scenarioText, ['school admission', 'college admission', 'admission interview'])) {
 return 'school';
 }

 if (setupTextContainsPhrase(scenarioText, ['job interview', 'general job'])) {
 return 'job';
 }

 return null;
 }

 function targetSetupScenarioKind() {
 const target = normalizeSetupScenarioText(document.getElementById('valPosition')?.value);
 if (!target) return null;

 const jobIndicators = [
 'accountant', 'admin', 'administrator', 'agent', 'aide', 'analyst', 'architect',
 'agricultural', 'agriculture', 'associate', 'assistant', 'attendant', 'auditor', 'banker', 'barista', 'bookkeeper',
 'caregiver', 'carpenter', 'call center', 'cashier', 'chef', 'civil engineer', 'clerk', 'consultant',
 'cleaner', 'cleaning', 'coordinator', 'cook', 'crew', 'custodian', 'dentist',
 'data encoder', 'data entry', 'designer',
 'developer', 'director', 'doctor', 'driver', 'editor', 'electrician', 'employee',
 'engineer', 'executive', 'facilities', 'finance', 'fisheries', 'fishery', 'front desk', 'guard', 'healthcare worker', 'hospitality', 'housekeeper',
 'housekeeping', 'hr', 'instructor', 'intern', 'local government', 'lgu',
 'janitor', 'janitorial', 'janitorial services', 'job', 'lawyer', 'lead', 'manager',
 'marketer', 'marketing', 'maintenance', 'mechanic',
 'medical technologist', 'midwife', 'nurse', 'officer', 'operator', 'paralegal',
 'pharmacist', 'physician', 'pilot', 'plumber', 'principal', 'professor', 'programmer',
 'qa', 'quality assurance', 'receptionist', 'recruiter', 'representative', 'researcher', 'sales',
 'scientist', 'secretary', 'seo', 'server', 'specialist', 'staff', 'supervisor',
 'support', 'teacher', 'technician', 'therapist', 'tourism', 'trainee', 'tutor', 'veterinarian',
 'waiter', 'worker', 'writer',
 ];
 const schoolIndicators = [
 'abm', 'accountancy', 'admission', 'agriculture', 'architecture', 'bachelor', 'bs agriculture', 'bs accountancy accounting information system', 'bs computer science',
 'bs computer engineering', 'bs cybersecurity', 'bs data science', 'bs electronics engineering',
 'bs entrepreneurship', 'bs financial management', 'bs industrial engineering',
 'bs fisheries', 'bs information systems', 'bs information technology', 'bs marketing management',
 'bs office administration', 'bs public administration', 'bs social work',
 'bs software engineering', 'bscpe', 'bscs', 'bsis', 'bsit',
 'business administration', 'college', 'computer engineering', 'computer science',
 'course', 'criminology', 'cybersecurity', 'data science', 'degree',
 'education', 'electrical engineering', 'electronics engineering', 'engineering', 'entrepreneurship', 'fisheries', 'freshman',
 'gas', 'graduate program', 'hospitality management', 'humss', 'ict', 'industrial engineering', 'accounting information system',
 'information systems', 'information technology', 'it', 'law school', 'master',
 'marketing management', 'mechanical engineering', 'medicine', 'nursing', 'program',
 'psychology', 'public administration', 'school',
 'senior high', 'software engineering', 'stem', 'strand', 'student',
 'tourism', 'university',
 ];
 const schoolProgramOverrideIndicators = [
 'bachelor of elementary education', 'bachelor of secondary education',
 'bs accountancy', 'bs accountancy accounting information system', 'bs agriculture', 'bs architecture', 'bs biology', 'bs business administration',
 'bs civil engineering', 'bs computer engineering', 'bs computer science',
 'bs criminology', 'bs cybersecurity', 'bs data science', 'bs electrical engineering',
 'bs electronics engineering', 'bs entrepreneurship', 'bs fisheries', 'bs financial management',
 'bs hospitality management', 'bs industrial engineering', 'bs information systems',
 'bs information technology', 'bs marketing management', 'bs mechanical engineering',
 'bs medical technology', 'bs nursing', 'bs office administration', 'bs pharmacy',
 'bs psychology', 'bs public administration', 'bs social work', 'bs software engineering',
 'bs tourism management', 'master in information technology', 'master of business administration',
 'senior high abm strand', 'senior high gas strand', 'senior high humss strand',
 'senior high ict strand', 'senior high stem strand',
 ];
 const explicitSchoolProgramIndicators = [
 'admission', 'bachelor', 'bs accountancy', 'bs agriculture', 'bs computer science', 'bs fisheries', 'bs information systems',
 'bs information technology', 'bscs', 'bsis', 'bsit', 'course', 'degree', 'freshman',
 'graduate program', 'law school', 'master in', 'master of', 'masters in', 'masters of',
 'senior high', 'strand',
 ];
 const isJobRelated = setupTextContainsPhrase(target, jobIndicators);
 const isSchoolRelated = setupTextContainsPhrase(target, schoolIndicators);
 const isKnownSchoolProgram = setupTextContainsPhrase(target, schoolProgramOverrideIndicators);
 const isExplicitSchoolProgram = setupTextContainsPhrase(target, explicitSchoolProgramIndicators);

 if (isKnownSchoolProgram) return 'school';
 if (isExplicitSchoolProgram && !isJobRelated) return 'school';
 if (isJobRelated) return 'job';
 if (isSchoolRelated && !isJobRelated) return 'school';

 return null;
 }

 function setupScenarioTargetMismatch() {
 const scenarioKind = selectedSetupScenarioKind();
 const vagueRecommendation = vagueSetupTargetRecommendation();

 if (vagueRecommendation) {
 if (scenarioKind === 'job' && vagueRecommendation.kind === 'job') {
 return vagueRecommendation.jobScenario;
 }

 if (scenarioKind === 'school' && vagueRecommendation.kind === 'job') {
 return vagueRecommendation.schoolScenario;
 }
 }

 const targetKind = targetSetupScenarioKind();

 if (scenarioKind === 'job' && targetKind !== 'job') {
 return targetKind === 'school'? setupScenarioMismatchMessages.recommendSchool: setupScenarioMismatchMessages.job;
 }

 if (scenarioKind === 'school' && targetKind !== 'school') {
 return targetKind === 'job'? setupScenarioMismatchMessages.recommendJob: setupScenarioMismatchMessages.school;
 }

 return null;
 }

 function currentSetupTargetFieldCopy() {
 return setupTargetFieldCopies[selectedSetupScenarioKind() === 'school'? 'school': 'job'];
 }

 function flattenSetupTargetChoices(choiceGroups) {
 return Object.values(choiceGroups || {}).flatMap((choices) => Array.isArray(choices)? choices: []);
 }

 function setSetupTargetDropdownOpen(open) {
 const dropdown = document.querySelector('[data-target-dropdown]');
 const trigger = document.getElementById('targetPositionDropdownButton');
 const menu = document.getElementById('targetPositionDropdownMenu');

 if (!dropdown || !trigger || !menu) return;

 dropdown.classList.toggle('setup-target-dropdown-open', open);
 trigger.setAttribute('aria-expanded', open? 'true': 'false');
 menu.hidden = !open;

 if (open) {
 window.setTimeout(() => menu.scrollIntoView({ block: 'nearest', inline: 'nearest' }), 0);
 }
 }

 function visibleSetupTargetChoices() {
 return Array.from(document.querySelectorAll('[data-target-dropdown-choice-value]')).filter((choice) => !choice.hidden && !choice.closest('[hidden]'));
 }

 function syncSetupTargetDropdown(positionField, targetKind, targetCopy) {
 if (!positionField) return;
 const choiceGroups = setupTargetChoiceGroups[targetKind] || {};
 const previousKind = positionField.dataset.targetKind || targetKind;
 const previousValue = previousKind === targetKind? String(positionField.value || positionField.dataset.selectedTarget || '').trim(): '';
 const availableValues = flattenSetupTargetChoices(choiceGroups);

 positionField.value = previousValue && availableValues.includes(previousValue)? previousValue: '';
 positionField.dataset.selectedTarget = positionField.value;
 positionField.dataset.targetKind = targetKind;

 const selectedValue = String(positionField.value || '').trim();
 const trigger = document.getElementById('targetPositionDropdownButton');
 const label = document.getElementById('targetPositionDropdownLabel');

 if (trigger) {
 trigger.title = selectedValue || targetCopy.placeholder;
 trigger.setAttribute('aria-label', `${targetCopy.label}: ${selectedValue || targetCopy.placeholder}`);
 }

 if (label) {
 label.textContent = selectedValue || targetCopy.placeholder;
 label.classList.toggle('setup-target-placeholder', !selectedValue);
 }

 document.querySelectorAll('[data-target-dropdown-group-kind]').forEach((group) => {
 group.hidden = group.dataset.targetDropdownGroupKind !== targetKind;
 });

 document.querySelectorAll('[data-target-dropdown-choice-value]').forEach((choice) => {
 const isCurrentKind = choice.dataset.targetDropdownChoiceKind === targetKind;
 const isSelected = isCurrentKind && choice.dataset.targetDropdownChoiceValue === selectedValue;
 choice.hidden = !isCurrentKind;
 choice.classList.toggle('setup-target-choice-selected', isSelected);
 choice.setAttribute('aria-pressed', isSelected? 'true': 'false');
 });
 }

 function syncSetupTargetFieldCopy() {
 const targetKind = selectedSetupScenarioKind() === 'school'? 'school': 'job';
 const targetCopy = currentSetupTargetFieldCopy();
 const labelText = document.getElementById('targetPositionLabelText');
 const summaryLabel = document.getElementById('summaryPositionLabel');
 const positionField = document.getElementById('valPosition');
 const positionError = document.getElementById('targetPositionError');
 const calibrationTitle = document.getElementById('targetCalibrationTitle');

 if (labelText) labelText.textContent = targetCopy.label;
 if (summaryLabel) summaryLabel.textContent = targetCopy.summaryLabel;
 syncSetupTargetDropdown(positionField, targetKind, targetCopy);
 if (positionError) positionError.textContent = targetCopy.requiredMessage;
 if (calibrationTitle) calibrationTitle.textContent = targetCopy.calibrationTitle;
 }

 function hasCheckedSetupInput(name) {
 return Boolean(document.querySelector(`input[name="${name}"]:checked`));
 }

 function setQuestionTypeError(visible) {
 const group = document.getElementById('questionTypeGroup');
 const error = document.getElementById('questionTypeError');
 if (group) {
 group.classList.toggle('setup-field-invalid', visible);
 group.setAttribute('aria-invalid', visible? 'true': 'false');
 }
 if (error) {
 error.hidden =!visible;
 error.classList.toggle('setup-inline-error-visible', visible);
 }
 }

 function missingSetupItems(panelId = null) {
 const fieldIds = panelId? (setupPanelRequiredFields[panelId] || []): setupRequiredFieldIds;
 const missing = fieldIds.map(id => ({ type: 'field', id, panelId })).filter(item => {
 const field = document.getElementById(item.id);
 return!field || String(field.value || '').trim().length === 0;
 });

 if ((!panelId || panelId === 'panel-basic') && !missing.some(item => item.id === 'valPosition')) {
 const mismatch = setupScenarioTargetMismatch();
 if (mismatch) {
 missing.push({ type: 'scenario_mismatch', id: 'valPosition', panelId: 'panel-basic', title: mismatch.title, message: mismatch.message });
 }
 }

 if (!panelId || panelId === 'panel-structure') {
 if (!hasCheckedSetupInput('difficulty')) {
 missing.push({ type: 'group', name: 'difficulty', panelId: 'panel-structure' });
 }
 }

 if (!panelId || panelId === 'panel-content') {
 if (!hasCheckedSetupInput('question_types[]')) {
 missing.push({ type: 'group', name: 'question_types[]', panelId: 'panel-content' });
 }
 }

 if (!panelId || panelId === 'panel-response') {
 if (!hasCheckedSetupInput('response_mode')) {
 missing.push({ type: 'group', name: 'response_mode', panelId: 'panel-response' });
 }
 }

 return missing;
 }

 function markSetupValidation(missing) {
 const missingFieldIds = new Set(missing.filter(item => item.type === 'field' || item.type === 'scenario_mismatch').map(item => item.id));
 setupRequiredFieldIds.forEach(id => setSetupFieldInvalid(document.getElementById(id), missingFieldIds.has(id)));
 Object.keys(setupFieldErrorIds).forEach(id => setSetupFieldError(id, missingFieldIds.has(id)));
 setQuestionTypeError(missing.some(item => item.name === 'question_types[]'));
 }

 function showSetupValidationIssue(item) {
 if (item?.type === 'field' && item.id === 'valPosition') {
 showTargetPositionAlert();
 return;
 }

 if (item?.type === 'scenario_mismatch') {
 showTargetPositionAlert(item.message, item.title);
 return;
 }

 focusSetupItem(item);
 }

 function focusSetupItem(item) {
 if (!item) return;
 const targetPanelId = item.panelId || Object.entries(setupPanelRequiredFields).find(([, ids]) => ids.includes(item.id))?.[0];
 if (targetPanelId) {
 const steps = getSetupSteps();
 const stepIndex = steps.findIndex(step => step.id === targetPanelId);
 if (stepIndex >= 0) showSetupStep(stepIndex);
 }

 window.setTimeout(() => {
 if (item.type === 'field') {
 if (item.id === 'valPosition') {
 document.getElementById('targetPositionDropdownButton')?.focus();
 return;
 }
 document.getElementById(item.id)?.focus();
 return;
 }

 if (item.name === 'question_types[]') {
 document.querySelector('input[name="question_types[]"]')?.focus();
 return;
 }

 document.querySelector(`input[name="${item.name}"]`)?.focus();
 }, 40);
 }

 function validateSetupStep(panelId, reveal = false) {
 const missing = missingSetupItems(panelId);
 if (reveal) {
 setupValidationVisible = true;
 markSetupValidation(missing);
 showSetupValidationIssue(missing[0]);
 }
 return missing.length === 0;
 }

 function validateSetupForm(reveal = false) {
 const missing = missingSetupItems();
 if (reveal || setupValidationVisible) {
 setupValidationVisible = true;
 markSetupValidation(missing);
 showSetupValidationIssue(missing[0]);
 }
 return missing.length === 0;
 }

 const setupSummaryPendingText = 'Not set yet';

 function setSummaryValue(id, value, isReady = true) {
 const element = document.getElementById(id);
 if (!element) return;

 const text = String(value || '').trim();
 const hasValue = isReady && text.length > 0;
 element.innerText = hasValue? text: setupSummaryPendingText;
 element.classList.toggle('summary-val-pending',!hasValue);

 if (id === 'sumDuration') {
 element.classList.toggle('text-success', hasValue);
 }
 }

 function titleizeSetupValue(value) {
 return String(value || '').replace(/[_-]+/g, ' ').replace(/\b\w/g, character => character.toUpperCase());
 }

 function updateSummary() {
 const detailsReady = visitedSetupStepIds.has('panel-basic');
 const structureReady = visitedSetupStepIds.has('panel-structure');
 const cameraReady = visitedSetupStepIds.has('panel-inclusive');
 const contentReady = visitedSetupStepIds.has('panel-content');
 const responseReady = visitedSetupStepIds.has('panel-response');

 const scenarioSelect = document.getElementById('valScenario');
 if (scenarioSelect) {
 const selectedOption = scenarioSelect.options[scenarioSelect.selectedIndex];
 setSummaryValue('sumScenario', selectedOption?.dataset.contextLabel || selectedOption?.text || '', detailsReady);
 document.getElementById('valFocus').value = selectedOption?.dataset.focus || 'Job Interview';
 const sourceSummary = document.getElementById('sourceSummary');
 if (sourceSummary) {
 sourceSummary.innerText = selectedOption?.dataset.sourceSummary || 'career and education sources';
 }
 }

 syncSetupTargetFieldCopy();
 const posVal = document.getElementById('valPosition').value;
 setSummaryValue('sumPosition', posVal, detailsReady);

 const diff = document.querySelector('input[name="difficulty"]:checked');
 setSummaryValue('sumDifficulty', diff? titleizeSetupValue(diff.value): '', structureReady);

 const numQ = document.getElementById('valNumQuestions').value;
 setSummaryValue('sumQuestions', numQ, structureReady);

 const resp = document.querySelector('input[name="response_mode"]:checked');
 setSummaryValue('sumResponse', resp? titleizeSetupValue(resp.value): '', responseReady);

 const cameraDetection = document.querySelector('input[name="camera_detection"]:checked');
 setSummaryValue('sumCameraDetection', cameraDetection?.value === '1'? 'Camera On': 'Camera Off', cameraReady && Boolean(cameraDetection));

 const assistance = document.getElementById('valAssistance');
 if (assistance) {
 const assistanceLabel = (assistance.options[assistance.selectedIndex]?.text || '').replace(/\s*\([^)]*\)\s*$/, '');
 setSummaryValue('sumAssistance', assistanceLabel, contentReady);
 }

 const selectedQuestionTypes = Array.from(document.querySelectorAll('input[name="question_types[]"]:checked')).map(input => input.value);
 setSummaryValue('sumQuestionTypes', selectedQuestionTypes.join(', '), contentReady && selectedQuestionTypes.length > 0);

 const feedbackMode = document.getElementById('valFeedbackMode');
 if (feedbackMode) {
 setSummaryValue('sumFeedbackMode', feedbackMode.options[feedbackMode.selectedIndex]?.text || '', contentReady);
 }

 const timeLimit = parseInt(document.getElementById('valTimeLimit').value);
 let durationStr = "No limit";
 if(timeLimit > 0) {
 durationStr = (numQ * timeLimit) + " Minutes";
 }
 setSummaryValue('sumDuration', durationStr, structureReady && Boolean(numQ));
 updateStartInterviewState();
 }

 function updateStartInterviewState() {
 const startButton = document.getElementById('btn-start-interview');
 if (!startButton) return;

 const hasRequiredFields = setupRequiredFieldIds.every(id => {
 const field = document.getElementById(id);
 return field && String(field.value || '').trim().length > 0;
 });

 const hasDifficulty = hasCheckedSetupInput('difficulty');
 const hasResponseMode = hasCheckedSetupInput('response_mode');
 const hasQuestionType = hasCheckedSetupInput('question_types[]');
 const hasScenarioTargetMatch = !setupScenarioTargetMismatch();
 const hasCompleteSetupFields = hasRequiredFields && hasDifficulty && hasResponseMode && hasQuestionType && hasScenarioTargetMatch;
 const hasReviewedSetupSteps = getRequiredSetupReviewStepIds().every(stepId => visitedSetupStepIds.has(stepId));
 const canStart = hasCompleteSetupFields && hasReviewedSetupSteps;
 const activeStepId = getSetupSteps()[setupStepState.index]?.id;
 const shouldShowStart = activeStepId === 'panel-response' && hasResponseMode;

 if (setupValidationVisible) {
 markSetupValidation(missingSetupItems());
 }

 startButton.hidden =!shouldShowStart;
 startButton.classList.toggle('setup-start-visible', shouldShowStart);
 startButton.disabled =!canStart;
 startButton.classList.toggle('setup-start-disabled',!canStart);
 startButton.setAttribute('aria-disabled', canStart? 'false': 'true');
 startButton.title = canStart? 'Start interview': (hasRequiredFields && !hasScenarioTargetMatch? 'Match the target to the selected scenario first': (hasCompleteSetupFields? 'Review all setup steps first': 'Complete all required details first'));
 }

 document.querySelectorAll('.setup-input').forEach(el => {
 el.addEventListener('change', updateSummary);
 el.addEventListener('input', updateSummary);
 el.addEventListener('keyup', updateSummary);
 });

 document.querySelectorAll('input[name="question_types[]"]').forEach(el => {
 el.addEventListener('change', updateSummary);
 });

 const targetPositionDropdown = document.querySelector('[data-target-dropdown]');
 const targetPositionDropdownButton = document.getElementById('targetPositionDropdownButton');

 function chooseSetupTargetValue(choice) {
 const targetKind = selectedSetupScenarioKind() === 'school'? 'school': 'job';
 if (!choice || choice.dataset.targetDropdownChoiceKind !== targetKind) return;

 const positionField = document.getElementById('valPosition');
 if (!positionField) return;

 positionField.value = choice.dataset.targetDropdownChoiceValue || '';
 positionField.dataset.selectedTarget = positionField.value;
 positionField.dataset.targetKind = targetKind;
 syncSetupTargetDropdown(positionField, targetKind, currentSetupTargetFieldCopy());
 positionField.dispatchEvent(new Event('change', { bubbles: true }));
 setSetupTargetDropdownOpen(false);
 targetPositionDropdownButton?.focus();
 }

 targetPositionDropdownButton?.addEventListener('click', () => {
 const willOpen = targetPositionDropdownButton.getAttribute('aria-expanded') !== 'true';
 syncSetupTargetFieldCopy();
 setSetupTargetDropdownOpen(willOpen);
 });

 targetPositionDropdownButton?.addEventListener('keydown', (event) => {
 if (!['ArrowDown', 'Enter', ' '].includes(event.key)) return;
 event.preventDefault();
 syncSetupTargetFieldCopy();
 setSetupTargetDropdownOpen(true);
 window.setTimeout(() => visibleSetupTargetChoices()[0]?.focus(), 0);
 });

 document.querySelectorAll('[data-target-dropdown-choice-value]').forEach((choice) => {
 choice.addEventListener('click', () => chooseSetupTargetValue(choice));
 choice.addEventListener('keydown', (event) => {
 const choices = visibleSetupTargetChoices();
 const currentIndex = choices.indexOf(choice);

 if (event.key === 'Escape') {
 event.preventDefault();
 setSetupTargetDropdownOpen(false);
 targetPositionDropdownButton?.focus();
 return;
 }

 if (event.key === 'Enter' || event.key === ' ') {
 event.preventDefault();
 chooseSetupTargetValue(choice);
 return;
 }

 if (event.key === 'ArrowDown') {
 event.preventDefault();
 choices[(currentIndex + 1) % choices.length]?.focus();
 return;
 }

 if (event.key === 'ArrowUp') {
 event.preventDefault();
 choices[(currentIndex - 1 + choices.length) % choices.length]?.focus();
 }
 });
 });

 document.addEventListener('click', (event) => {
 if (!targetPositionDropdown || targetPositionDropdown.contains(event.target)) return;
 setSetupTargetDropdownOpen(false);
 });

 document.addEventListener('keydown', (event) => {
 if (event.key !== 'Escape') return;
 setSetupTargetDropdownOpen(false);
 });

 const setupStepState = {
 index: 0,
 desktopQuery: window.matchMedia('(min-width: 992px)'),
 baseSteps: [
 { id: 'panel-basic', label: 'Details' },
 { id: 'panel-structure', label: 'Structure' },
 { id: 'panel-inclusive', label: 'Camera' },
 { id: 'panel-content', label: 'Scenario' },
 { id: 'panel-response', label: 'Response' },
 ],
 };
 const visitedSetupStepIds = new Set();

 document.querySelectorAll('input[name="camera_detection"]').forEach(el => {
 el.addEventListener('change', () => {
 visitedSetupStepIds.add('panel-inclusive');
 updateSummary();
 });
 });

 function getSetupSteps() {
 const steps = [...setupStepState.baseSteps];
 if (!setupStepState.desktopQuery.matches) {
 steps.push({ id: 'panel-summary', label: 'Summary' });
 }
 return steps;
 }

 function getRequiredSetupReviewStepIds() {
 return setupStepState.baseSteps.map(step => step.id);
 }

 function renderSetupStepper() {
 const track = document.getElementById('setupStepperTrack');
 if (!track) return;

 const steps = getSetupSteps();
 const mode = setupStepState.desktopQuery.matches? 'desktop': 'mobile';
 if (track.dataset.rendered === mode) return;

 track.innerHTML = steps.map((step, index) => `
 <button type="button" class="setup-stepper-item" data-setup-step="${index}" aria-label="Go to ${step.label}">
 <span class="setup-stepper-dot"></span>
 <span class="setup-stepper-label">${step.label}</span>
 </button>
 `).join('');
 track.dataset.rendered = mode;

 track.querySelectorAll('[data-setup-step]').forEach(button => {
 button.addEventListener('click', () => {
 const targetIndex = Number(button.dataset.setupStep);
 if (targetIndex > setupStepState.index) {
 const steps = getSetupSteps();
 for (let index = setupStepState.index; index < targetIndex; index++) {
 if (!validateSetupStep(steps[index]?.id, true)) return;
 }
 }
 showSetupStep(targetIndex);
 });
 });
 }

 function showSetupStep(nextIndex) {
 const section = document.getElementById('sec-interview-setup');
 const stepper = document.getElementById('setupStepper');
 const prevButton = document.getElementById('setupStepPrev');
 const nextButton = document.getElementById('setupStepNext');
 const isDesktop = setupStepState.desktopQuery.matches;
 const steps = getSetupSteps();

 if (!section ||!stepper) return;

 renderSetupStepper();
 setupStepState.index = Math.max(0, Math.min(steps.length - 1, nextIndex));
 if (steps[setupStepState.index]?.id) {
 visitedSetupStepIds.add(steps[setupStepState.index].id);
 }

 section.classList.add('setup-step-mode');
 section.classList.toggle('setup-summary-step',!isDesktop && steps[setupStepState.index]?.id === 'panel-summary');
 stepper.hidden = false;

 steps.forEach((step, index) => {
 const panel = document.getElementById(step.id);
 if (panel) {
 const isActivePanel = index === setupStepState.index;
 panel.classList.toggle('setup-step-active', isActivePanel);
 panel.classList.remove('setup-step-transition-in');
 if (isActivePanel) {
 void panel.offsetWidth;
 panel.classList.add('setup-step-transition-in');
 }
 }

 const stepButton = document.querySelector(`[data-setup-step="${index}"]`);
 if (stepButton) {
 stepButton.classList.toggle('is-active', index === setupStepState.index);
 stepButton.classList.toggle('is-complete', index < setupStepState.index);
 stepButton.setAttribute('aria-current', index === setupStepState.index? 'step': 'false');
 }
 });

 if (prevButton) prevButton.disabled = setupStepState.index === 0;
 if (nextButton) {
 const isLast = setupStepState.index === steps.length - 1;
 nextButton.hidden = isLast;
 }

 updateSummary();

 }

 let setupTutorialRestoreIndex = null;

 function getSetupPanelForElement(element) {
 if (!element || typeof element.closest!== 'function') return null;

 if (element.id === 'btn-start-interview') {
 return document.getElementById('panel-response');
 }

 return element.closest('#panel-basic, #panel-structure, #panel-inclusive, #panel-content, #panel-response, #panel-summary');
 }

 function activateInterviewSetupTourPanel(element) {
 const panel = getSetupPanelForElement(element);
 if (!panel) return;

 const stepIndex = getSetupSteps().findIndex(step => step.id === panel.id);
 if (stepIndex >= 0) {
 showSetupStep(stepIndex);
 }
 }

 function setInterviewSetupTutorialMode(enabled) {
 const section = document.getElementById('sec-interview-setup');
 if (!section) return;

 if (enabled) {
 setupTutorialRestoreIndex = setupStepState.index;
 section.classList.add('setup-tutorial-mode');
 return;
 }

 section.classList.remove('setup-tutorial-mode');
 showSetupStep(Number.isInteger(setupTutorialRestoreIndex)? setupTutorialRestoreIndex: setupStepState.index);
 setupTutorialRestoreIndex = null;
 }

 window.showInterviewSetupStep = showSetupStep;
 window.getInterviewSetupStepIndex = () => setupStepState.index;
 window.activateInterviewSetupTourPanel = activateInterviewSetupTourPanel;
 window.setInterviewSetupTutorialMode = setInterviewSetupTutorialMode;

 document.getElementById('setupStepPrev')?.addEventListener('click', () => {
 showSetupStep(setupStepState.index - 1);
 });

 document.getElementById('setupStepNext')?.addEventListener('click', () => {
 const currentPanelId = getSetupSteps()[setupStepState.index]?.id;
 if (!validateSetupStep(currentPanelId, true)) return;
 showSetupStep(setupStepState.index + 1);
 });

 setupStepState.desktopQuery.addEventListener?.('change', () => {
 showSetupStep(setupStepState.index);
 });

 function initializeInterviewSetupPage() {
 showSetupStep(0);
 }

 initializeInterviewSetupPage();
 window.addEventListener('load', initializeInterviewSetupPage, { once: true });

 const setupForm = document.getElementById('setupForm');
 const setupTransitionOverlay = document.getElementById('setupTransitionOverlay');
 const startInterviewButton = document.getElementById('btn-start-interview');
 const setupAutoFullscreenPreferenceKey = 'speakready.interview.autoFullscreen';

 function rememberSetupAutoFullscreenPreference() {
 try {
 window.sessionStorage.setItem(setupAutoFullscreenPreferenceKey, '1');
 } catch (error) {
 console.warn('Unable to save interview fullscreen preference:', error);
 }
 }

 function requestSetupBrowserFullscreen() {
 rememberSetupAutoFullscreenPreference();

 const root = document.documentElement;
 if (!document.fullscreenElement && root && typeof root.requestFullscreen === 'function') {
 root.requestFullscreen({ navigationUI: 'hide' }).catch(() => {});
 }
 }

 function ensureSetupTransitionFullscreenOverlay() {
 if (setupTransitionOverlay && setupTransitionOverlay.parentElement!== document.body) {
 document.body.appendChild(setupTransitionOverlay);
 }
 }

 if (setupForm && setupTransitionOverlay) {
 ensureSetupTransitionFullscreenOverlay();
 setupForm.addEventListener('submit', function(event) {
 updateStartInterviewState();
 if (!validateSetupForm(true) || startInterviewButton?.disabled) {
 event.preventDefault();
 return;
 }

 ensureSetupTransitionFullscreenOverlay();
 requestSetupBrowserFullscreen();
 setupTransitionOverlay.classList.add('active');
 document.body.classList.add('finish-transition-active');

 if (startInterviewButton) {
 startInterviewButton.disabled = true;
 startInterviewButton.innerHTML = startInterviewButton.dataset.loadingLabel || 'Starting Interview <i class="fa-solid fa-spinner fa-spin ms-2"></i>';
 }
 });

 window.addEventListener('pageshow', function() {
 setupTransitionOverlay.classList.remove('active');
 document.body.classList.remove('finish-transition-active');

 if (startInterviewButton) {
 startInterviewButton.innerHTML = startInterviewButton.dataset.defaultLabel || 'Start <i class="fa-solid fa-play ms-2"></i>';
 updateSummary();
 }
 });
 }
</script>

@push('scripts')
<script>
 (function() {
 function installInterviewSetupScrollFix() {
 const css = `
 html.interview-setup-page-root,
 body.interview-setup-page {
 overflow-x: hidden!important;
 }

 body.interview-setup-page #mob-content {
 box-sizing: border-box!important;
 min-height: 100dvh!important;
 }

 body.interview-setup-page #userAppContent,
 body.interview-setup-page #mob-content,
 body.interview-setup-page #mob-content > #userAppContent,
 body.interview-setup-page #sec-interview-setup,
 body.interview-setup-page #sec-interview-setup #setupForm,
 body.interview-setup-page #sec-interview-setup #setup-left-col,
 body.interview-setup-page #sec-interview-setup.col-lg-4,
 body.interview-setup-page #sec-interview-setup.setup-summary-wrap,
 body.interview-setup-page #sec-interview-setup.setup-panel,
 body.interview-setup-page #sec-interview-setup #panel-summary {
 height: auto!important;
 max-height: none!important;
 overflow: visible!important;
 overflow-y: visible!important;
 overscroll-behavior: auto!important;
 }

 body.interview-setup-page #sec-interview-setup {
 overflow-x: clip!important;
 }

 body.interview-setup-page #sec-interview-setup.setup-summary-wrap,
 body.interview-setup-page #sec-interview-setup #panel-summary,
 body.interview-setup-page #sec-interview-setup.col-lg-4 > div {
 position: static!important;
 top: auto!important;
 }

 body.interview-setup-page #dashboard.db-nav,
 html body.user-desktop-shell.interview-setup-page:not(.admin-shell) #dashboard.db-nav {
 overflow-y: auto!important;
 overflow-x: hidden!important;
 scrollbar-width: thin!important;
 scrollbar-color: rgba(125, 211, 252, 0.32) transparent!important;
 -ms-overflow-style: auto!important;
 }

 body.interview-setup-page #dashboard.db-nav::-webkit-scrollbar,
 html body.user-desktop-shell.interview-setup-page:not(.admin-shell) #dashboard.db-nav::-webkit-scrollbar {
 width: 5px!important;
 height: 5px!important;
 }

 body.interview-setup-page #dashboard.db-nav::-webkit-scrollbar-thumb,
 html body.user-desktop-shell.interview-setup-page:not(.admin-shell) #dashboard.db-nav::-webkit-scrollbar-thumb {
 border-radius: 999px!important;
 background: rgba(125, 211, 252, 0.26)!important;
 }
 `;

 let style = document.getElementById('interview-setup-scroll-fix');
 if (!style) {
 style = document.createElement('style');
 style.id = 'interview-setup-scroll-fix';
 }
 if (style.parentNode!== document.head) document.head.appendChild(style);
 style.textContent = css;

 const syncPageClass = () => {
 const active = Boolean(document.getElementById('sec-interview-setup'));
 document.documentElement.classList.toggle('interview-setup-page-root', active);
 document.body.classList.toggle('interview-setup-page', active);
 if (!active) {
 style.remove();
 window.__interviewSetupScrollObserver?.disconnect?.();
 window.__interviewSetupScrollObserver = null;
 }
 };

 syncPageClass();
 window.__interviewSetupScrollObserver?.disconnect?.();
 const target = document.querySelector('[data-user-ajax-content]') || document.body;
 window.__interviewSetupScrollObserver = new MutationObserver(syncPageClass);
 window.__interviewSetupScrollObserver.observe(target, { childList: true, subtree: true });
 }

 installInterviewSetupScrollFix();

 function initInterviewSetupTour() {
 if (typeof window.createSpeakReadyTour!== 'function') return;

 const setupTourSteps = [
 { element: '#panel-basic', popover: { title: 'Interview Focus', description: 'Choose job or school practice, select an optional category, and enter the target role or program.', side: 'top', align: 'center' }},
 { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Set difficulty, question count, and timing before you start.', side: 'top', align: 'center' }},
 { element: '#panel-inclusive', popover: { title: 'Optional Camera Coaching', description: 'Camera On can detect visible body-language signals for coaching. Camera Off skips it, and readiness scoring stays based on answer quality.', side: 'top', align: 'center' }},
 { element: '#panel-content', popover: { title: 'Practice Scenario', description: 'Pick your assistance level, live feedback mode, and question types.', side: 'top', align: 'center' }},
 { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose typed, voice, or hybrid answers depending on how you want to practice.', side: 'top', align: 'center' }},
 { element: '#panel-summary', popover: { title: 'Live Summary', description: 'Confirm the selected focus, structure, assistance, and response mode before generating the session.', side: 'top', align: 'center' }},
 { element: '#btn-start-interview', popover: { title: 'Start Interview', description: 'Generate the customized practice session when the setup looks right.', side: 'top', align: 'center' }}
 ];

 window.createSpeakReadyTour({
 completionKey: 'onboarding_completed_interview_setup',
 serverDetectedMobile: false,
 stepsMobile: setupTourSteps,
 stepsDesktop: setupTourSteps,
 autoStartDelay: 700,
 startDelay: 60,
 beforeStart: () => {
 document.documentElement.style.setProperty('scroll-behavior', 'auto', 'important');
 window.setInterviewSetupTutorialMode?.(true);
 },
 onHighlightStarted: (element) => {
 window.activateInterviewSetupTourPanel?.(element);
 },
 onBeforeDestroy: () => {
 document.documentElement.style.removeProperty('scroll-behavior');
 },
 onDestroyed: () => {
 document.documentElement.style.removeProperty('scroll-behavior');
 window.setInterviewSetupTutorialMode?.(false);
 },
 });
 }

 if (document.readyState === 'loading') {
 document.addEventListener('DOMContentLoaded', initInterviewSetupTour, { once: true });
 return;
 }

 initInterviewSetupTour();
 })();
</script>
@endpush
@endsection
