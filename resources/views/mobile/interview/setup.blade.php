@extends('mobile.layouts.app')
@section('title', 'Interview Setup')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/interview/setup.css?v=25') }}" data-page-style="interview-setup">
<link rel="stylesheet" href="{{ asset('css/mobile/interview/setup-2.css?v=24') }}" data-page-style="interview-setup-2">
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
 $targetPositionExamples = \App\Services\QuestionDatasetProvider::targetPositionExamples(3, $jobPositionOptionGroups->all());
 $interviewCategories = ($categories?? collect())
 ->filter(fn ($category): bool => \App\Support\InterviewScope::isSupportedCoreCategory($category))
 ->values();

 $scenarioLabelForCategory = function (?string $categoryTitle): string {
 return \App\Support\InterviewScope::categoryLabel($categoryTitle);
 };

 $focusForCategory = function (?string $categoryTitle, string $label): string {
 return \App\Support\InterviewScope::focus($categoryTitle);
 };

 $scenarioOptions = $interviewCategories
 ->map(function ($category) use ($sourceDatasets, $scenarioLabelForCategory, $focusForCategory) {
 $key = \App\Services\QuestionDatasetProvider::defaultKeyForCategory($category->title);
 $sourceDataset = $sourceDatasets[$key]?? $sourceDatasets['ph_job_interview']?? \App\Services\QuestionDatasetProvider::find('ph_job_interview')?? [];
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
 'source_summary' => $sourceSummary?: \App\Support\InterviewScope::sourceSummaryFallback(),
 ];
 })
 ->values();
 $selectedCategoryId = old('category_id');
 $selectedCategoryId = $selectedCategoryId === null || $selectedCategoryId === ''? null: (int) $selectedCategoryId;
 $selectedScenario = $selectedCategoryId? $scenarioOptions->first(fn ($scenario) => (int) $scenario['category_id'] === $selectedCategoryId): null;
 $targetFieldMode = 'job';
 $targetFieldCopies = [
 'job' => [
 'label' => 'Target Position',
 'summary_label' => 'Position:',
 'placeholder' => 'Choose a target position',
 'required_message' => 'Enter the target position before continuing.',
 'calibration_title' => 'Role-focused interview practice',
 ],
 ];
 $targetFieldCopy = $targetFieldCopies[$targetFieldMode];
 $targetPositionDefault = old(
 'target_position',
 Auth::user()?->target_position ?? ''
 );
 if (strcasecmp(trim((string) $targetPositionDefault), 'Other') === 0) {
 $targetPositionDefault = trim((string) old('custom_position', ''));
 }
 $questionOptions = collect([1, 3, 5, 10, 15, 20, 25, 30]);
 $maxQuestionSetting = (int) \App\Support\SystemSettings::value('int_max_questions', 20);
 $maxQuestionSetting = $questionOptions->contains($maxQuestionSetting) ? $maxQuestionSetting : 20;
 $questionOptions = $questionOptions->filter(fn (int $count): bool => $count <= $maxQuestionSetting)->values();
 $defaultQuestionSetting = (int) \App\Support\SystemSettings::value('int_default_questions', 10);
 if (!$questionOptions->contains($defaultQuestionSetting)) {
     $defaultQuestionSetting = (int) ($questionOptions->last() ?: 10);
 }
 $defaultTimeLimitSetting = (int) \App\Support\SystemSettings::value('int_time_limit', 0);
 $requestedLiveFeedbackMode = request()->query('live_feedback_mode');
 $requestedLiveFeedbackMode = in_array($requestedLiveFeedbackMode, ['coaching', 'real_interview'], true) ? $requestedLiveFeedbackMode : '';
 $oldInterviewerAvatar = old('interviewer_avatar');
 $setupDefaults = [
 'difficulty' => old('difficulty', ''),
 'num_questions' => (string) old('num_questions', ''),
 'time_limit' => (string) old('time_limit', ''),
 'interview_focus' => old('interview_focus', $selectedScenario['focus']?? ''),
 'live_feedback_mode' => old('live_feedback_mode', $requestedLiveFeedbackMode),
 'response_mode' => old('response_mode', ''),
 'interviewer_avatar' => \App\Support\InterviewerAvatar::normalize(is_string($oldInterviewerAvatar) ? $oldInterviewerAvatar : null),
 ];
 $interviewerAvatarDescriptions = [
 'friendly_recruiter' => 'Warm introductions and confidence building.',
 'structured_hr' => 'Clear behavioral questions and focused follow-ups.',
 'hiring_manager' => 'Practical questions about ownership and results.',
 'role_specialist' => 'Deeper questions tailored to your target position.',
 'senior_leader' => 'Concise questions about judgment and impact.',
 ];
 $selectedQuestionTypes = old('question_types', []);
 $selectedQuestionTypes = is_array($selectedQuestionTypes)? $selectedQuestionTypes: [];
 $hasScenarioOptions = $scenarioOptions->isNotEmpty();
 $interviewSetupDraftKey = 'speakready.interview.setupDraft.'.auth()->id();
@endphp

<div class="db-section active setup-step-mode setup-page-booting" id="sec-interview-setup" aria-busy="true" data-setup-ready="false">
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

 <form action="{{ route('interview.start') }}" method="POST" id="setupForm" data-sr-no-transition="true" data-setup-draft-key="{{ $interviewSetupDraftKey }}">
 @csrf
 <div class="row g-4">
 <!-- Left Column: Form Settings -->
 <div class="col-lg-8" id="setup-left-col">
 <div class="setup-stepper" id="setupStepper" aria-label="Interview setup steps">
 <div class="setup-stepper-track" id="setupStepperTrack"></div>
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
 <select class="oinp setup-input" name="category_id" id="valScenario" aria-describedby="{{ $hasScenarioOptions? '': 'scenarioEmptyState' }}" aria-invalid="{{ $hasScenarioOptions? 'false': 'true' }}" required>
 @if($hasScenarioOptions)
 <option value="" disabled {{ $selectedScenario? '': 'selected' }}>Choose a practice scenario</option>
 @endif
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
 @unless($hasScenarioOptions)
 <div class="setup-inline-error setup-inline-error-visible" id="scenarioEmptyState" role="alert">No active interview scenarios are available. Ask an admin to activate at least one core category before starting.</div>
 @endunless
 </div>

 <div class="setup-card-field">
 <label class="setup-card-label" for="jobCategoryFilter">
 <span class="setup-card-label-icon" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span>
 Job Category
 </label>
 <div class="setup-select-wrap">
 <select class="oinp" id="jobCategoryFilter" name="job_category">
 <option value="">All job categories</option>
 @foreach($jobPositionOptionGroups as $jobCategory => $targetChoices)
 <option value="{{ $jobCategory }}">{{ $jobCategory }}</option>
 @endforeach
 </select>
 </div>
 </div>

 <div class="setup-card-field setup-target-position-field">
 <label class="setup-card-label" for="targetPositionDropdownButton">
 <span class="setup-card-label-icon" aria-hidden="true"><i class="fa-solid fa-bullseye-arrow"></i></span>
 <span id="targetPositionLabelText">{{ $targetFieldCopy['label'] }}</span>
 </label>
 <input type="hidden" class="setup-input setup-target-hidden-input" name="target_position" id="valPosition" value="{{ $targetPositionDefault }}" required data-selected-target="{{ $targetPositionDefault }}" data-target-kind="{{ $targetFieldMode }}">
 <div class="setup-target-dropdown" data-target-dropdown>
 <button type="button" class="oinp setup-target-trigger" id="targetPositionDropdownButton" aria-haspopup="dialog" aria-expanded="false" aria-controls="targetPositionDropdownMenu" aria-describedby="targetPositionError">
 <span class="setup-target-trigger-text {{ $targetPositionDefault === ''? 'setup-target-placeholder': '' }}" id="targetPositionDropdownLabel">{{ $targetPositionDefault !== ''? $targetPositionDefault: $targetFieldCopy['placeholder'] }}</span>
 <i class="fa-solid fa-chevron-down setup-target-trigger-icon" aria-hidden="true"></i>
 </button>
 <dialog class="setup-target-menu" id="targetPositionDropdownMenu" data-target-dropdown-menu role="dialog" aria-modal="true" aria-label="Choose target position" tabindex="-1" autofocus hidden>
 <div class="setup-target-menu-search">
 <label class="visually-hidden" for="targetPositionSearch">Search target positions</label>
 <input type="search" class="oinp" id="targetPositionSearch" placeholder="Search positions" autocomplete="off">
 </div>
 @foreach(['job' => $jobPositionOptionGroups] as $targetKind => $targetGroups)
 @foreach($targetGroups as $jobCategory => $targetChoices)
 <div class="setup-target-choice-group" data-target-dropdown-group-kind="{{ $targetKind }}" data-job-category="{{ $jobCategory }}" {{ $targetFieldMode === $targetKind? '': 'hidden' }}>
 <div class="setup-target-choice-group-title">{{ $jobCategory }}</div>
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
 <p class="setup-target-empty" id="targetPositionEmpty" hidden>Don't worry if you can't find your role. Enter it below to add your role.</p>
 <div class="setup-target-custom-entry">
 <label class="visually-hidden" for="customTargetPositionDraft">Enter your role</label>
 <input type="text" class="oinp" id="customTargetPositionDraft" maxlength="255" autocomplete="organization-title" placeholder="Enter your role" aria-describedby="customTargetPositionError">
 <button type="button" class="setup-target-custom-save" id="saveCustomTargetPositionButton">Save</button>
 </div>
 <p class="setup-target-custom-error" id="customTargetPositionError" role="alert" hidden>Enter a role before saving.</p>
 </dialog>
 </div>
 <input type="hidden" id="customTargetPositionInput" name="custom_position" value="{{ old('custom_position', '') }}">
 <div class="setup-inline-error" id="targetPositionError" role="alert" hidden>{{ $targetFieldCopy['required_message'] }}</div>
 </div>

 <div class="setup-role-discovery-launch">
 <button type="button" class="setup-role-discovery-open" id="roleFinderOpenButton" data-bs-toggle="modal" data-bs-target="#roleFinderModal" aria-haspopup="dialog" aria-controls="roleFinderModal"><i class="fa-solid fa-compass" aria-hidden="true"></i> Help me find a role</button>
 </div>

 <div class="setup-calibrated-simple">
 <div class="setup-calibrated-icon" aria-hidden="true">
 <i class="fa-solid fa-database"></i>
 </div>
 <div>
 <h6 id="targetCalibrationTitle">{{ $targetFieldCopy['calibration_title'] }}</h6>
 <p><strong>Sources:</strong> <span id="sourceSummary">{{ $selectedScenario['source_summary']?? 'career sources' }}</span></p>
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
 <div class="setup-inline-error" id="difficultyError" role="alert" hidden>Select a difficulty level.</div>

 <div class="structure-select-grid">
 <div>
 <label class="olbl" for="valNumQuestions">Number of Questions</label>
 <div class="structure-select-wrap">
 <select class="oinp setup-input" name="num_questions" id="valNumQuestions" required>
 <option value="" disabled {{ $setupDefaults['num_questions'] === '' ? 'selected': '' }}>Not yet selected</option>
 @foreach($questionOptions as $questionCount)
 <option value="{{ $questionCount }}" {{ $setupDefaults['num_questions'] === (string) $questionCount ? 'selected': '' }}>{{ $questionCount }} {{ $questionCount === 1 ? 'Question' : 'Questions' }}</option>
 @endforeach
 </select>
 </div>
 </div>
 <div>
 <label class="olbl" for="valTimeLimit">Time Limit</label>
 <div class="structure-select-wrap">
 <select class="oinp setup-input" name="time_limit" id="valTimeLimit" required>
 <option value="" disabled {{ $setupDefaults['time_limit'] === ''? 'selected': '' }}>Not yet selected</option>
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
 $cameraDetectionValue = old('camera_detection', old('camera_coaching', null));
 $cameraDetectionSelected = $cameraDetectionValue !== null && $cameraDetectionValue !== '';
 $cameraDetectionOn = $cameraDetectionSelected && filter_var($cameraDetectionValue, FILTER_VALIDATE_BOOLEAN);
 @endphp
 <input type="hidden" name="separate_language_scoring" value="0">
 <input type="hidden" name="extended_time" value="0">
 <input type="hidden" name="captions" value="0">
 <input type="hidden" name="reduced_distraction" value="0">
 <input type="hidden" name="simplified_questions" value="0">
 <div class="inclusive-option-list camera-mode-list" role="radiogroup" aria-label="Camera body-language detection">
 <label class="inclusive-option camera-mode-option">
 <input type="radio" name="camera_detection" value="1" class="setup-input" {{ $cameraDetectionSelected && $cameraDetectionOn? 'checked': '' }}>
 <span class="camera-mode-copy">
 <strong>Camera On</strong>
 <small>Detects body language</small>
 </span>
 </label>
 <label class="inclusive-option camera-mode-option">
 <input type="radio" name="camera_detection" value="0" class="setup-input" {{ $cameraDetectionSelected &&! $cameraDetectionOn? 'checked': '' }}>
 <span class="camera-mode-copy">
 <strong>Camera Off</strong>
 <small>No body-language detection</small>
 </span>
 </label>
 </div>
 <div class="setup-inline-error" id="cameraDetectionError" role="alert" hidden>Choose Camera On or Camera Off.</div>
 <div class="inclusive-note">
 <i class="fa-solid fa-info" aria-hidden="true"></i>
 <span><strong>Important:</strong> Camera On enables visible framing, head alignment, shoulder/posture cues, and movement steadiness detection. Camera Off does not start body-language detection.</span>
 </div>
 </div>

 <!-- Scenario -->
 <div class="setup-panel setup-assistance-card animate-fade-up delay-400" id="panel-content">
 <div class="assistance-head">
 <div class="assistance-head-icon" aria-hidden="true">
 <i class="fa-solid fa-brain"></i>
 </div>
 <h5 class="assistance-title">Scenario</h5>
 </div>

 <div class="assistance-stack">
 <div class="assistance-field assistance-feedback-field">
 <label class="olbl" for="valFeedbackMode">Live Feedback Mode</label>
 <div class="assistance-select-wrap">
 <select class="oinp setup-input" name="live_feedback_mode" id="valFeedbackMode" required>
 <option value="" disabled {{ $setupDefaults['live_feedback_mode'] === ''? 'selected': '' }}>Not yet selected</option>
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
 <span class="assistance-question-text">{{ $questionType }}</span>
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
 <div class="setup-inline-error" id="responseModeError" role="alert" hidden>Choose a response mode.</div>
 @php($initialAvatarImage = \App\Support\InterviewerAvatar::image($setupDefaults['interviewer_avatar']))
 <div class="interviewer-avatar-picker" aria-describedby="interviewerAvatarPickerHelp interviewerAvatarError">
 <h6 class="interviewer-avatar-title">AI Interviewer</h6>
 <p class="interviewer-avatar-help" id="interviewerAvatarPickerHelp">Choose the interviewer style for this session.</p>
 <button type="button" class="interviewer-avatar-trigger" id="interviewerAvatarTrigger" aria-haspopup="dialog" aria-controls="interviewerAvatarDialog">
 <span class="interviewer-avatar-trigger-portrait"><img id="interviewerAvatarSelectedImage" src="{{ asset($initialAvatarImage) }}" alt="" width="52" height="52"></span>
 <span class="interviewer-avatar-trigger-copy">
 <span class="interviewer-avatar-trigger-name" id="interviewerAvatarSelectedName">{{ \App\Support\InterviewerAvatar::label($setupDefaults['interviewer_avatar']) }}</span>
 <span class="interviewer-avatar-trigger-description" id="interviewerAvatarSelectedDescription">{{ $interviewerAvatarDescriptions[$setupDefaults['interviewer_avatar']] }}</span>
 </span>
 <span class="interviewer-avatar-trigger-action">Change <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
 </button>
 <div class="setup-inline-error" id="interviewerAvatarError" role="alert" hidden>Choose an AI interviewer.</div>
 </div>
 <dialog class="interviewer-avatar-dialog" id="interviewerAvatarDialog" aria-labelledby="interviewerAvatarDialogTitle" aria-describedby="interviewerAvatarDialogHelp">
 <div class="interviewer-avatar-dialog-panel">
 <div class="interviewer-avatar-dialog-header">
 <div>
 <h2 class="interviewer-avatar-title" id="interviewerAvatarDialogTitle">Choose AI Interviewer</h2>
 <p class="interviewer-avatar-help" id="interviewerAvatarDialogHelp">Select a portrait and interview style.</p>
 </div>
 <button type="button" class="interviewer-avatar-dialog-close" id="interviewerAvatarClose" aria-label="Close interviewer choices"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
 </div>
 <div class="interviewer-avatar-dialog-body">
 <div class="interviewer-avatar-grid" id="interviewerAvatarGroup" role="radiogroup" aria-label="AI interviewer">
 @foreach(\App\Support\InterviewerAvatar::keys() as $avatarKey)
 @php($avatarImage = \App\Support\InterviewerAvatar::image($avatarKey))
 <label class="interviewer-avatar-choice">
 <input type="radio" name="interviewer_avatar" form="setupForm" value="{{ $avatarKey }}" class="setup-input" data-summary-label="{{ \App\Support\InterviewerAvatar::label($avatarKey) }}" required {{ $setupDefaults['interviewer_avatar'] === $avatarKey? 'checked': '' }}>
 <span class="interviewer-avatar-card">
 <span class="interviewer-avatar-portrait">
 <picture>
 <source srcset="{{ asset($avatarImage) }}" type="image/webp">
 <img src="{{ asset(str_replace('.webp', '.png', $avatarImage)) }}" alt="" loading="lazy" width="88" height="88">
 </picture>
 </span>
 <span class="interviewer-avatar-card-copy">
 <span class="interviewer-avatar-name">{{ \App\Support\InterviewerAvatar::label($avatarKey) }}</span>
 <span class="interviewer-avatar-description">{{ $interviewerAvatarDescriptions[$avatarKey] }}</span>
 </span>
 </span>
 </label>
 @endforeach
 </div>
 </div>
 <div class="interviewer-avatar-dialog-footer">
 <button type="button" id="interviewerAvatarCancel">Cancel</button>
 <button type="button" id="interviewerAvatarConfirm">Use this interviewer</button>
 </div>
 </div>
 </dialog>
 </div>

 <div class="setup-stepper-actions" aria-label="Interview setup navigation">
 <button type="button" class="setup-step-btn" id="setupStepPrev"><i class="fa-solid fa-arrow-left"></i> Back</button>
 <button type="submit" id="btn-start-interview" class="setup-step-btn setup-start-button btn-shine" aria-label="Start Interview" title="Start Interview" data-default-label='Start <i class="fa-solid fa-play ms-2"></i>' data-loading-label='Starting <i class="fa-solid fa-spinner fa-spin ms-2"></i>' hidden>
 Start <i class="fa-solid fa-play ms-2"></i>
 </button>
 <button type="button" class="setup-step-btn primary" id="setupStepNext">Next <i class="fa-solid fa-arrow-right"></i></button>
 </div>

 </div>

 <!-- Right Column: Live Summary -->
 <div class="col-lg-4 animate-fade-up delay-200">
 <div class="setup-summary-wrap">
 <div class="setup-panel" id="panel-summary" style="background:linear-gradient(145deg, rgba(59,130,246,0.08) 0%, rgba(59,130,246,0.02) 100%); border:1px solid rgba(59,130,246,0.25); box-shadow: 0 15px 35px rgba(59,130,246,0.1), inset 0 1px 1px rgba(255, 255, 255, 0.1); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);">
 <h5 class="setup-summary-title" style="font-weight:800;margin-bottom:24px;color:var(--pur);text-align:center;letter-spacing:0.5px;"><i class="fa-solid fa-clipboard-list me-2"></i> Interview Summary</h5>

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
 <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></span>
 <span class="summary-label">AI Interviewer:</span>
 <span class="summary-val summary-val-pending" id="sumInterviewerAvatar">Not set yet</span>
 </div>
 <div class="summary-row">
 <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-video"></i></span>
 <span class="summary-label">Camera Detection:</span>
 <span class="summary-val summary-val-pending" id="sumCameraDetection">Not set yet</span>
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
 <div class="modal fade setup-role-discovery-modal" id="roleFinderModal" tabindex="-1" aria-labelledby="roleDiscoveryTitle" aria-describedby="roleDiscoveryIntro" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
 <div class="modal-content">
 <div class="modal-header">
 <h5 class="modal-title" id="roleDiscoveryTitle">Help me find a role</h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close role finder"></button>
 </div>
 <div class="modal-body">
 <p id="roleDiscoveryIntro">Share your skills, interests, or qualifications to explore roles for interview practice. These are role ideas, not live job openings.</p>
 <div class="setup-role-discovery-grid">
 <div>
 <label for="roleSkills">Skills <span class="setup-optional-label">Optional</span></label>
 <input type="text" class="oinp" id="roleSkills" name="role_skills" form="setupForm" maxlength="500" placeholder="e.g. customer service, spreadsheets" aria-describedby="roleSkillsHelp">
 <small id="roleSkillsHelp">Separate skills with commas.</small>
 </div>
 <div>
 <label for="roleInterests">Interests <span class="setup-optional-label">Optional</span></label>
 <input type="text" class="oinp" id="roleInterests" name="role_interests" form="setupForm" maxlength="500" placeholder="e.g. helping people, technology" aria-describedby="roleInterestsHelp">
 <small id="roleInterestsHelp">Separate interests with commas.</small>
 </div>
 <div>
 <label for="roleQualifications">Qualifications or certifications <span class="setup-optional-label">Optional</span></label>
 <input type="text" class="oinp" id="roleQualifications" name="role_qualifications" form="setupForm" maxlength="500" placeholder="e.g. TESDA NC II, nursing degree">
 </div>
 <div>
 <label for="roleExperienceLevel">Experience level <span class="setup-optional-label">Optional</span></label>
 <select class="oinp" id="roleExperienceLevel" name="role_experience_level" form="setupForm">
 <option value="">Choose an experience level</option>
 <option value="entry">Student, first job, or entry level</option>
 <option value="mid">Some professional experience</option>
 <option value="senior">Senior or specialist experience</option>
 </select>
 </div>
 </div>
 <p class="setup-role-status" id="roleSuggestionStatus" role="status" aria-live="polite"></p>
 <div class="setup-role-results" id="roleSuggestionResults" aria-label="Suggested positions"></div>
 </div>
 <div class="modal-footer">
 <button type="button" class="setup-role-close-button" data-bs-dismiss="modal">Close</button>
 <button type="button" class="setup-suggest-roles-button" id="suggestRolesButton"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Suggest positions</button>
 </div>
 </div>
 </div>
 </div>
 </form>

<div id="setupTransitionOverlay" class="finish-transition-overlay interview-prep-overlay" role="dialog" aria-modal="true" aria-live="polite" aria-atomic="true" aria-labelledby="setupLoadingTitle" aria-describedby="setupLoadingDescription">
 <div class="interview-prep-shell">
 <div class="interview-prep-art" aria-hidden="true">
 <span class="prep-art-ring"></span>
 <span class="prep-art-doc">
 <span class="prep-art-clip"></span>
 <span class="prep-art-avatar"><i class="fa-solid fa-user"></i></span>
 <span class="prep-art-line prep-art-line-1"></span>
 <span class="prep-art-line prep-art-line-2"></span>
 <span class="prep-art-line prep-art-line-3"></span>
 <span class="prep-art-bars"><span></span><span></span><span></span></span>
 </span>
 <span class="prep-art-badge prep-art-badge-left"><i class="fa-solid fa-brain"></i></span>
 <span class="prep-art-badge prep-art-badge-right"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
 <span class="prep-art-lens"><i class="fa-solid fa-check"></i></span>
 <span class="prep-art-handle"></span>
 </div>

 <div class="interview-prep-copy">
<h4 id="setupLoadingTitle" class="interview-prep-title">Preparing Your <span>Interview...</span></h4>
<p id="setupLoadingDescription" class="interview-prep-subtitle">This will just take a few moments.</p>
 </div>

 <div class="interview-prep-progress" aria-label="Preparing interview progress">
 <div class="interview-prep-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="19">
 <span id="setupLoadingProgressBar" class="interview-prep-progress-bar"></span>
 </div>
 <strong id="setupLoadingPercent" class="interview-prep-progress-percent">19%</strong>
 </div>

 <div class="interview-prep-checklist" id="setupLoadingChecklist" aria-label="Configured interview setup">
 <div class="interview-prep-check-row" data-loading-item="details">
 <span class="interview-prep-row-icon"><i class="fa-regular fa-file-lines"></i></span>
 <span class="interview-prep-row-copy">
 <strong data-loading-title>Details configured</strong>
 <small data-loading-detail>Scenario and target role ready</small>
 </span>
 <span class="interview-prep-row-check" aria-label="Pending"><i class="fa-solid fa-check"></i></span>
 </div>
 <div class="interview-prep-check-row" data-loading-item="structure">
 <span class="interview-prep-row-icon"><i class="fa-solid fa-list-check"></i></span>
 <span class="interview-prep-row-copy">
 <strong data-loading-title>Structure configured</strong>
 <small data-loading-detail>Question count and timing ready</small>
 </span>
 <span class="interview-prep-row-check" aria-label="Pending"><i class="fa-solid fa-check"></i></span>
 </div>
 <div class="interview-prep-check-row" data-loading-item="camera">
 <span class="interview-prep-row-icon"><i class="fa-solid fa-video"></i></span>
 <span class="interview-prep-row-copy">
 <strong data-loading-title>Camera configured</strong>
 <small data-loading-detail>Camera preference confirmed</small>
 </span>
 <span class="interview-prep-row-check" aria-label="Pending"><i class="fa-solid fa-check"></i></span>
 </div>
 <div class="interview-prep-check-row" data-loading-item="coaching">
 <span class="interview-prep-row-icon"><i class="fa-solid fa-comments"></i></span>
 <span class="interview-prep-row-copy">
 <strong data-loading-title>Coaching configured</strong>
 <small data-loading-detail>Feedback mode and question mix ready</small>
 </span>
 <span class="interview-prep-row-check" aria-label="Pending"><i class="fa-solid fa-check"></i></span>
 </div>
 <div class="interview-prep-check-row" data-loading-item="response">
 <span class="interview-prep-row-icon"><i class="fa-solid fa-microphone-lines"></i></span>
 <span class="interview-prep-row-copy">
 <strong data-loading-title>Response configured</strong>
 <small data-loading-detail>Answer mode ready</small>
 </span>
 <span class="interview-prep-row-check" aria-label="Pending"><i class="fa-solid fa-check"></i></span>
 </div>
 </div>

 <div class="interview-prep-tip">
 <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
 <span>Great practice leads to <strong>great performance!</strong></span>
 </div>

<div class="interview-prep-actions" aria-label="Interview loading actions">
<button type="button" id="setupPrepCancelButton" class="interview-prep-button cancel" onclick="cancelSetupPreparedInterview()" disabled aria-disabled="true">
<i class="fa-solid fa-xmark"></i> Cancel
</button>
<button type="button" id="setupPrepBeginButton" class="interview-prep-button begin" onclick="beginPreparedInterview()" disabled aria-disabled="true">
Begin Interview <i class="fa-solid fa-play"></i>
</button>
</div>
 </div>
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
 window.SpeakReadyInterviewSetupDraftConfig = { storageKey: @json($interviewSetupDraftKey) };
</script>
<script src="{{ asset('js/interview-setup-draft.js?v=1') }}"></script>
<script src="{{ asset('js/interview-setup-avatar-modal.js?v=2') }}"></script>
<script>
 const setupRequiredFieldIds = [
 'valScenario',
 'valPosition',
 'valNumQuestions',
 'valTimeLimit',
 'valFeedbackMode',
 ];
 const setupPanelRequiredFields = {
 'panel-basic': ['valScenario', 'valPosition'],
 'panel-structure': ['valNumQuestions', 'valTimeLimit'],
 'panel-content': ['valFeedbackMode'],
 };
 let setupValidationVisible = false;
 const setupFieldErrorIds = {
 valPosition: 'targetPositionError',
 };
 const setupGroupErrorIds = {
 difficulty: { container: '.structure-difficulty-list', error: 'difficultyError' },
 camera_detection: { container: '.camera-mode-list', error: 'cameraDetectionError' },
 response_mode: { container: '.response-mode-list', error: 'responseModeError' },
 interviewer_avatar: { container: '.interviewer-avatar-picker', error: 'interviewerAvatarError' },
 };
 let targetPositionAlertVisible = false;
 const setupScenarioMismatchMessages = {
 job: {
 title: 'Use a job target',
 message: @json('Enter a specific job title, such as '.$targetPositionExamples.'.'),
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
 },
 ];
 const setupTargetFieldCopies = {
 job: {
 label: 'Target Position',
 summaryLabel: 'Position:',
 placeholder: 'Choose a target position',
 requiredTitle: 'Target position required',
 requiredMessage: 'Enter the target position before continuing.',
 calibrationTitle: 'Role-focused interview practice',
 },
 };
 const setupTargetChoiceGroups = @json([
 'job' => $jobPositionOptionGroups,
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

 function setSetupGroupError(name, visible) {
 const config = setupGroupErrorIds[name];
 if (!config) return;

 const group = document.querySelector(config.container);
 const error = document.getElementById(config.error);

 if (group) {
 group.classList.toggle('setup-field-invalid', visible);
 group.setAttribute('aria-invalid', visible? 'true': 'false');
 }

 if (error) {
 error.hidden =!visible;
 error.classList.toggle('setup-inline-error-visible', visible);
 }
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

 window.SpeakReadyUiNotice?.(message);
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
 const target = normalizeSetupScenarioText(setupTargetFieldValue(document.getElementById('valPosition')));

 if (!target) return null;

 return setupVagueTargetRecommendations.find((recommendation) => recommendation.terms.includes(target)) || null;
 }

 function selectedSetupScenarioKind() {
 return 'job';
 }

 function targetSetupScenarioKind() {
 const target = normalizeSetupScenarioText(setupTargetFieldValue(document.getElementById('valPosition')));
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

 return setupTextContainsPhrase(target, jobIndicators)? 'job': null;
 }

 function setupScenarioTargetMismatch() {
 const vagueRecommendation = vagueSetupTargetRecommendation();

 if (vagueRecommendation && vagueRecommendation.kind === 'job') {
 return vagueRecommendation.jobScenario;
 }

 return null;
 }

 function currentSetupTargetFieldCopy() {
 return setupTargetFieldCopies.job;
 }

 function flattenSetupTargetChoices(choiceGroups) {
 return Object.values(choiceGroups || {}).flatMap((choices) => Array.isArray(choices)? choices: []);
 }

 function setupTargetFieldValue(positionField) {
 if (!positionField) return '';
 const value = String(positionField.value || positionField.dataset.selectedTarget || positionField.getAttribute('value') || '').trim();
 if (value && positionField.value !== value) {
 positionField.value = value;
 positionField.setAttribute('value', value);
 }
 return value;
 }

 function setSetupTargetInputValue(positionField, value, targetKind = null) {
 if (!positionField) return;
 const nextValue = String(value || '').trim();
 positionField.value = nextValue;
 positionField.dataset.selectedTarget = nextValue;
 if (targetKind) {
 positionField.dataset.targetKind = targetKind;
 }
 if (nextValue) {
 positionField.setAttribute('value', nextValue);
 } else {
 positionField.removeAttribute('value');
 }
 }

 function isSetupCompactMobile() {
 return window.matchMedia('(max-width: 991.98px)').matches;
 }

 function syncSetupTargetMenuViewport() {
 const menu = document.getElementById('targetPositionDropdownMenu');
 if (!menu || !isSetupCompactMobile()) return;

 const viewport = window.visualViewport;
 const viewportHeight = viewport?.height || window.innerHeight || document.documentElement.clientHeight || 640;
 const viewportTop = viewport?.offsetTop || 0;
 menu.style.setProperty('--setup-target-viewport-height', `${Math.round(viewportHeight)}px`);
 menu.style.setProperty('--setup-target-viewport-center', `${Math.round(viewportTop + viewportHeight / 2)}px`);
 }

 function setSetupTargetDropdownOpen(open) {
 const dropdown = document.querySelector('[data-target-dropdown]');
 const trigger = document.getElementById('targetPositionDropdownButton');
 const menu = document.getElementById('targetPositionDropdownMenu');

 if (!dropdown || !trigger || !menu) return;

 dropdown.classList.toggle('setup-target-dropdown-open', open);
 document.documentElement.classList.toggle('setup-target-position-modal-open', open);
 document.body.classList.toggle('setup-target-position-modal-open', open);
 trigger.setAttribute('aria-expanded', open? 'true': 'false');
 if (open) {
 const draft = document.getElementById('customTargetPositionDraft');
 const savedCustom = document.getElementById('customTargetPositionInput');
 const draftError = document.getElementById('customTargetPositionError');
 if (draft) {
 draft.value = savedCustom?.value || '';
 draft.removeAttribute('aria-invalid');
 }
 if (draftError) draftError.hidden = true;
 menu.hidden = false;
 if (!menu.open) {
 if (isSetupCompactMobile()) menu.showModal();
 else menu.show();
 }
 } else {
 if (menu.open) menu.close();
 menu.hidden = true;
 }

 if (open) {
 filterSetupTargetChoices();
 menu.scrollTop = 0;
 syncSetupTargetMenuViewport();
 const selectedChoice = menu.querySelector('.setup-target-choice-selected');
 if (selectedChoice && !selectedChoice.hidden && !selectedChoice.closest('[hidden]')) {
 const menuRect = menu.getBoundingClientRect();
 const choiceRect = selectedChoice.getBoundingClientRect();
 menu.scrollTop = Math.max(0, choiceRect.top - menuRect.top - (menu.clientHeight - choiceRect.height) / 2);
 }
 menu.focus({ preventScroll: true });
 }
 }

 function visibleSetupTargetChoices() {
 return Array.from(document.querySelectorAll('[data-target-dropdown-choice-value]')).filter((choice) => !choice.hidden && !choice.closest('[hidden]'));
 }

 function filterSetupTargetChoices() {
 const category = document.getElementById('jobCategoryFilter')?.value || '';
 const search = (document.getElementById('targetPositionSearch')?.value || '').trim().toLocaleLowerCase();
 const menu = document.getElementById('targetPositionDropdownMenu');
 if (menu) menu.dataset.categorySelected = category? 'true': 'false';
 let visibleCount = 0;
 document.querySelectorAll('[data-target-dropdown-group-kind]').forEach((group) => {
 const categoryMatches = !category || group.dataset.jobCategory === category;
 let groupVisible = false;
 group.querySelectorAll('[data-target-dropdown-choice-value]').forEach((choice) => {
 const matches = categoryMatches && (!search || (choice.dataset.targetDropdownChoiceValue || '').toLocaleLowerCase().includes(search));
 choice.hidden = !matches;
 if (matches) {
 groupVisible = true;
 visibleCount += 1;
 }
 });
 group.hidden = !groupVisible;
 });
 const empty = document.getElementById('targetPositionEmpty');
 if (empty) empty.hidden = visibleCount > 0;
 }

 function syncSetupTargetDropdown(positionField, targetKind, targetCopy) {
 if (!positionField) return;
 const choiceGroups = setupTargetChoiceGroups[targetKind] || {};
 const previousKind = positionField.dataset.targetKind || targetKind;
 const previousValue = previousKind === targetKind? setupTargetFieldValue(positionField): '';
 const availableValues = flattenSetupTargetChoices(choiceGroups);

 setSetupTargetInputValue(positionField, previousValue, targetKind);

 const selectedValue = setupTargetFieldValue(positionField);
 const catalogValue = availableValues.find((value) => value.toLocaleLowerCase() === selectedValue.toLocaleLowerCase());
 const customInput = document.getElementById('customTargetPositionInput');
 const isCustom = Boolean(selectedValue && !catalogValue) || positionField.dataset.customTarget === 'true';
 if (customInput) customInput.value = isCustom? selectedValue: '';
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
 filterSetupTargetChoices();
 }

 function syncSetupTargetFieldCopy() {
 const targetKind = 'job';
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
 const value = item.id === 'valPosition'? setupTargetFieldValue(field): String(field?.value || '').trim();
 return!field || value.length === 0;
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

 if (!panelId || panelId === 'panel-inclusive') {
 if (!hasCheckedSetupInput('camera_detection')) {
 missing.push({ type: 'group', name: 'camera_detection', panelId: 'panel-inclusive' });
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
 if (!hasCheckedSetupInput('interviewer_avatar')) {
 missing.push({ type: 'group', name: 'interviewer_avatar', panelId: 'panel-response' });
 }
 }

 return missing;
 }

 function markSetupValidation(missing) {
 const missingFieldIds = new Set(missing.filter(item => item.type === 'field' || item.type === 'scenario_mismatch').map(item => item.id));
 const missingGroups = new Set(missing.filter(item => item.type === 'group').map(item => item.name));
 const missingPanelIds = new Set(missing.map(item => item.panelId).filter(Boolean));
 setupRequiredFieldIds.forEach(id => setSetupFieldInvalid(document.getElementById(id), missingFieldIds.has(id)));
 Object.keys(setupFieldErrorIds).forEach(id => setSetupFieldError(id, missingFieldIds.has(id)));
 Object.keys(setupGroupErrorIds).forEach(name => setSetupGroupError(name, missingGroups.has(name)));
 setQuestionTypeError(missing.some(item => item.name === 'question_types[]'));
 document.querySelectorAll('#panel-basic, #panel-structure, #panel-inclusive, #panel-content, #panel-response, #panel-summary').forEach(panel => {
 const invalid = missingPanelIds.has(panel.id);
 panel.classList.toggle('setup-panel-invalid', invalid);
 panel.setAttribute('aria-invalid', invalid? 'true': 'false');
 });
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

 if (item.name === 'interviewer_avatar') {
 document.getElementById('interviewerAvatarTrigger')?.focus();
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
 const hasScenario = String(scenarioSelect.value || '').trim().length > 0;
 setSummaryValue('sumScenario', selectedOption?.dataset.contextLabel || selectedOption?.text || '', detailsReady && hasScenario);
 document.getElementById('valFocus').value = hasScenario? (selectedOption?.dataset.focus || 'Job Interview'): '';
 const sourceSummary = document.getElementById('sourceSummary');
 if (sourceSummary) {
 sourceSummary.innerText = selectedOption?.dataset.sourceSummary || 'career sources';
 }
 }

 syncSetupTargetFieldCopy();
 const posVal = setupTargetFieldValue(document.getElementById('valPosition'));
 setSummaryValue('sumPosition', posVal, detailsReady);

 const diff = document.querySelector('input[name="difficulty"]:checked');
 setSummaryValue('sumDifficulty', diff? titleizeSetupValue(diff.value): '', structureReady);

 const numQ = document.getElementById('valNumQuestions').value;
 setSummaryValue('sumQuestions', numQ, structureReady);

 const resp = document.querySelector('input[name="response_mode"]:checked');
 setSummaryValue('sumResponse', resp? titleizeSetupValue(resp.value): '', responseReady);
 const interviewerAvatar = document.querySelector('input[name="interviewer_avatar"]:checked');
 setSummaryValue('sumInterviewerAvatar', interviewerAvatar?.dataset.summaryLabel || '', responseReady);

 const cameraDetection = document.querySelector('input[name="camera_detection"]:checked');
 setSummaryValue('sumCameraDetection', cameraDetection?.value === '1'? 'Camera On': 'Camera Off', cameraReady && Boolean(cameraDetection));

 const selectedQuestionTypes = Array.from(document.querySelectorAll('input[name="question_types[]"]:checked')).map(input => input.value);
 setSummaryValue('sumQuestionTypes', selectedQuestionTypes.join(', '), contentReady && selectedQuestionTypes.length > 0);

 const feedbackMode = document.getElementById('valFeedbackMode');
 if (feedbackMode) {
 setSummaryValue('sumFeedbackMode', feedbackMode.options[feedbackMode.selectedIndex]?.text || '', contentReady && Boolean(feedbackMode.value));
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
 const value = id === 'valPosition'? setupTargetFieldValue(field): String(field?.value || '').trim();
 return field && value.length > 0;
 });

 const hasDifficulty = hasCheckedSetupInput('difficulty');
 const hasCameraDetection = hasCheckedSetupInput('camera_detection');
 const hasResponseMode = hasCheckedSetupInput('response_mode');
 const hasInterviewerAvatar = hasCheckedSetupInput('interviewer_avatar');
 const hasQuestionType = hasCheckedSetupInput('question_types[]');
 const hasScenarioTargetMatch = !setupScenarioTargetMismatch();
 const hasCompleteSetupFields = hasRequiredFields && hasDifficulty && hasCameraDetection && hasResponseMode && hasInterviewerAvatar && hasQuestionType && hasScenarioTargetMatch;
 const hasReviewedSetupSteps = getRequiredSetupReviewStepIds().every(stepId => visitedSetupStepIds.has(stepId));
 const canStart = hasCompleteSetupFields && hasReviewedSetupSteps;
 const activeStepId = getSetupSteps()[setupStepState.index]?.id;
 const shouldShowStart = activeStepId === 'panel-summary' && hasResponseMode;

 if (setupValidationVisible) {
 markSetupValidation(missingSetupItems());
 }

 startButton.hidden =!shouldShowStart;
 startButton.classList.toggle('setup-start-visible', shouldShowStart);
 startButton.disabled =!canStart;
 startButton.classList.toggle('setup-start-disabled',!canStart);
 startButton.setAttribute('aria-disabled', canStart? 'false': 'true');
 startButton.title = canStart? 'Start interview': (hasRequiredFields && !hasScenarioTargetMatch? 'Match the target to the selected scenario first': (hasCompleteSetupFields? 'Review all setup steps first': 'Complete all required details first'));
 syncSetupStepperState();
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
 const targetPositionDropdownMenu = document.getElementById('targetPositionDropdownMenu');
 const targetPositionSearch = document.getElementById('targetPositionSearch');
 const jobCategoryFilter = document.getElementById('jobCategoryFilter');
 const customTargetPositionInput = document.getElementById('customTargetPositionInput');
 const customTargetPositionDraft = document.getElementById('customTargetPositionDraft');
 const customTargetPositionError = document.getElementById('customTargetPositionError');
 const saveCustomTargetPositionButton = document.getElementById('saveCustomTargetPositionButton');

 function setSetupTargetPosition(value, category = '') {
 const positionField = document.getElementById('valPosition');
 if (!positionField) return;
 const catalogChoice = Array.from(document.querySelectorAll('[data-target-dropdown-choice-value]'))
 .find((choice) => (choice.dataset.targetDropdownChoiceValue || '').toLocaleLowerCase() === String(value || '').toLocaleLowerCase());
 const isCustom = Boolean(String(value || '').trim()) && !catalogChoice;
 positionField.dataset.customTarget = isCustom? 'true': 'false';
 setSetupTargetInputValue(positionField, value, 'job');
 if (customTargetPositionInput) customTargetPositionInput.value = isCustom? String(value || ''): '';
 const selectedCategory = catalogChoice?.closest('[data-job-category]')?.dataset.jobCategory || category;
 if (selectedCategory && Array.from(jobCategoryFilter?.options || []).some((option) => option.value === selectedCategory)) {
 jobCategoryFilter.value = selectedCategory;
 }
 syncSetupTargetDropdown(positionField, 'job', currentSetupTargetFieldCopy());
 positionField.dispatchEvent(new Event('change', { bubbles: true }));
 }

 function chooseSetupTargetValue(choice) {
 const targetKind = 'job';
 if (!choice || choice.dataset.targetDropdownChoiceKind !== targetKind) return;

 setSetupTargetPosition(choice.dataset.targetDropdownChoiceValue || '');
 setSetupTargetDropdownOpen(false);
 targetPositionDropdownButton?.focus();
 }

 jobCategoryFilter?.addEventListener('change', () => {
 const selected = setupTargetFieldValue(document.getElementById('valPosition'));
 const selectedChoice = Array.from(document.querySelectorAll('[data-target-dropdown-choice-value]'))
 .find((choice) => (choice.dataset.targetDropdownChoiceValue || '').toLocaleLowerCase() === selected.toLocaleLowerCase());
 const selectedCategory = selectedChoice?.closest('[data-job-category]')?.dataset.jobCategory || '';
 if (selectedChoice && jobCategoryFilter.value && selectedCategory !== jobCategoryFilter.value) {
 setSetupTargetPosition('');
 }
 if (targetPositionSearch) targetPositionSearch.value = '';
 filterSetupTargetChoices();
 if (jobCategoryFilter.value) {
 setSetupTargetDropdownOpen(true);
 }
 });

 targetPositionSearch?.addEventListener('input', filterSetupTargetChoices);
 targetPositionSearch?.addEventListener('keydown', (event) => {
 if (event.key === 'Escape') {
 event.preventDefault();
 setSetupTargetDropdownOpen(false);
 targetPositionDropdownButton?.focus();
 } else if (event.key === 'ArrowDown') {
 event.preventDefault();
 visibleSetupTargetChoices()[0]?.focus();
 } else if (event.key === 'Enter') {
 event.preventDefault();
 const first = visibleSetupTargetChoices()[0];
 if (first) chooseSetupTargetValue(first);
 else {
 if (customTargetPositionDraft && targetPositionSearch.value.trim()) customTargetPositionDraft.value = targetPositionSearch.value.trim();
 customTargetPositionDraft?.focus();
 }
 }
 });

 function saveCustomTargetPosition() {
 const value = String(customTargetPositionDraft?.value || '').trim();
 if (!value) {
 if (customTargetPositionError) customTargetPositionError.hidden = false;
 customTargetPositionDraft?.setAttribute('aria-invalid', 'true');
 customTargetPositionDraft?.focus();
 return;
 }
 setSetupTargetPosition(value);
 setSetupTargetDropdownOpen(false);
 targetPositionDropdownButton?.focus();
 }

 saveCustomTargetPositionButton?.addEventListener('click', saveCustomTargetPosition);
 customTargetPositionDraft?.addEventListener('input', () => {
 if (customTargetPositionError) customTargetPositionError.hidden = true;
 customTargetPositionDraft.removeAttribute('aria-invalid');
 });
 customTargetPositionDraft?.addEventListener('keydown', (event) => {
 if (event.key !== 'Enter') return;
 event.preventDefault();
 saveCustomTargetPosition();
 });

 const suggestRolesButton = document.getElementById('suggestRolesButton');
 const roleSuggestionStatus = document.getElementById('roleSuggestionStatus');
 const roleSuggestionResults = document.getElementById('roleSuggestionResults');
 const roleFinderModal = document.getElementById('roleFinderModal');
 const roleFinderOpenButton = document.getElementById('roleFinderOpenButton');
 let roleFinderSelected = false;

 roleFinderModal?.addEventListener('show.bs.modal', () => {
 setSetupTargetDropdownOpen(false);
 if (roleFinderModal.parentElement !== document.body) document.body.appendChild(roleFinderModal);
 });

 for (const eventName of ['input', 'change']) {
 roleFinderModal?.addEventListener(eventName, () => {
 document.getElementById('setupForm')?.dispatchEvent(new Event(eventName, { bubbles: true }));
 });
 }

 roleFinderModal?.addEventListener('shown.bs.modal', () => {
 document.getElementById('roleSkills')?.focus();
 });

 roleFinderModal?.addEventListener('hidden.bs.modal', () => {
 (roleFinderSelected? targetPositionDropdownButton: roleFinderOpenButton)?.focus();
 roleFinderSelected = false;
 });

 roleFinderModal?.addEventListener('keydown', (event) => {
 if (event.key !== 'Enter' || !event.target.matches('input[type="text"]')) return;
 event.preventDefault();
 suggestRolesButton?.click();
 });

 function splitRoleDiscoveryTerms(value) {
 return String(value || '').split(',').map((term) => term.trim()).filter(Boolean).slice(0, 12);
 }

 function setRoleSuggestionStatus(message, isError = false) {
 if (!roleSuggestionStatus) return;
 roleSuggestionStatus.textContent = message;
 roleSuggestionStatus.classList.toggle('is-error', isError);
 }

 function renderRoleSuggestions(roles) {
 if (!roleSuggestionResults) return;
 roleSuggestionResults.replaceChildren();
 roles.forEach((role) => {
 const title = String(role?.title || '').trim();
 if (!title) return;
 const button = document.createElement('button');
 button.type = 'button';
 button.className = 'setup-role-result';
 const heading = document.createElement('strong');
 heading.textContent = title;
 const category = document.createElement('small');
 category.textContent = String(role?.category || 'Suggested role');
 const reason = document.createElement('span');
 reason.textContent = String(role?.reason || 'Explore this role in your interview practice.');
 button.append(heading, category, reason);
 button.addEventListener('click', () => {
 setSetupTargetPosition(title, String(role?.category || ''));
 setRoleSuggestionStatus(`${title} selected as your target position.`);
 roleFinderSelected = true;
 window.bootstrap?.Modal?.getOrCreateInstance(roleFinderModal)?.hide();
 });
 roleSuggestionResults.appendChild(button);
 });
 }

 suggestRolesButton?.addEventListener('click', async () => {
 const payload = {
 skills: splitRoleDiscoveryTerms(document.getElementById('roleSkills')?.value),
 interests: splitRoleDiscoveryTerms(document.getElementById('roleInterests')?.value),
 qualifications: String(document.getElementById('roleQualifications')?.value || '').trim(),
 experience_level: String(document.getElementById('roleExperienceLevel')?.value || '').trim(),
 };
 if (!payload.skills.length && !payload.interests.length && !payload.qualifications) {
 setRoleSuggestionStatus('Add at least one skill, interest, or qualification first.', true);
 document.getElementById('roleSkills')?.focus();
 return;
 }
 suggestRolesButton.disabled = true;
 suggestRolesButton.setAttribute('aria-busy', 'true');
 setRoleSuggestionStatus('Finding positions that may fit your background…');
 roleSuggestionResults?.replaceChildren();
 try {
 const response = await fetch(@json(route('interview.suggestRoles')), {
 method: 'POST',
 headers: {
 'Accept': 'application/json',
 'Content-Type': 'application/json',
 'X-CSRF-TOKEN': document.querySelector('#setupForm input[name="_token"]')?.value || '',
 },
 credentials: 'same-origin',
 body: JSON.stringify(payload),
 });
 const data = await response.json().catch(() => ({}));
 if (!response.ok) {
 const firstError = Object.values(data?.errors || {})[0];
 throw new Error(Array.isArray(firstError)? firstError[0]: (data?.message || 'Could not load suggestions.'));
 }
 const roles = Array.isArray(data?.roles)? data.roles: [];
 renderRoleSuggestions(roles);
 setRoleSuggestionStatus(roles.length? 'Choose a position to use for interview practice.': 'No suggestions yet. Try adding more specific details.', !roles.length);
 } catch (error) {
 setRoleSuggestionStatus(error?.message || 'Could not load suggestions. Please try again.', true);
 } finally {
 suggestRolesButton.disabled = false;
 suggestRolesButton.removeAttribute('aria-busy');
 }
 });

 targetPositionDropdown?.addEventListener('click', (event) => {
 event.stopPropagation();
 });

 targetPositionDropdown?.addEventListener('pointerdown', (event) => {
 event.stopPropagation();
 });

 targetPositionDropdown?.addEventListener('touchstart', (event) => {
 event.stopPropagation();
 }, { passive: true });

 targetPositionDropdownMenu?.addEventListener('touchmove', (event) => {
 event.stopPropagation();
 }, { passive: true });

 targetPositionDropdownMenu?.addEventListener('wheel', (event) => {
 event.stopPropagation();
 }, { passive: true });

 targetPositionDropdownButton?.addEventListener('click', () => {
 const willOpen = targetPositionDropdownButton.getAttribute('aria-expanded') !== 'true';
 syncSetupTargetFieldCopy();
 setSetupTargetDropdownOpen(willOpen);
 });
 targetPositionDropdownMenu?.addEventListener('click', (event) => {
 if (event.target !== targetPositionDropdownMenu) return;
 const rect = targetPositionDropdownMenu.getBoundingClientRect();
 if (event.clientX >= rect.left && event.clientX <= rect.right && event.clientY >= rect.top && event.clientY <= rect.bottom) return;
 setSetupTargetDropdownOpen(false);
 targetPositionDropdownButton?.focus();
 });

 targetPositionDropdownMenu?.addEventListener('cancel', (event) => {
 event.preventDefault();
 setSetupTargetDropdownOpen(false);
 targetPositionDropdownButton?.focus();
 });

 targetPositionDropdownButton?.addEventListener('keydown', (event) => {
 if (!['ArrowDown', 'Enter', ' '].includes(event.key)) return;
 event.preventDefault();
 syncSetupTargetFieldCopy();
 setSetupTargetDropdownOpen(true);
 window.setTimeout(() => targetPositionSearch?.focus(), 0);
 });

 document.querySelectorAll('[data-target-dropdown-choice-value]').forEach((choice) => {
 choice.addEventListener('click', (event) => {
 event.preventDefault();
 event.stopPropagation();
 chooseSetupTargetValue(choice);
 });
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

 window.addEventListener('resize', () => {
 if (targetPositionDropdownButton?.getAttribute('aria-expanded') === 'true') {
 syncSetupTargetMenuViewport();
 }
 });

 window.addEventListener('scroll', () => {
 if (targetPositionDropdownButton?.getAttribute('aria-expanded') === 'true') {
 syncSetupTargetMenuViewport();
 }
 }, { passive: true });

 window.visualViewport?.addEventListener('resize', () => {
 if (targetPositionDropdownButton?.getAttribute('aria-expanded') === 'true') {
 syncSetupTargetMenuViewport();
 }
 });

 window.visualViewport?.addEventListener('scroll', () => {
 if (targetPositionDropdownButton?.getAttribute('aria-expanded') === 'true') {
 syncSetupTargetMenuViewport();
 }
 }, { passive: true });

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
 const setupDraftApi = window.SpeakReadyInterviewSetupDraft;
 const setupDraftFormElement = document.getElementById('setupForm');
 const restoredSetupDraft = @json(!$errors->any())? setupDraftApi?.restore(setupDraftFormElement): null;
 if (!document.querySelector('input[name="interviewer_avatar"]:checked')) {
 const defaultInterviewerAvatar = document.querySelector('input[name="interviewer_avatar"][value="friendly_recruiter"]');
 if (defaultInterviewerAvatar) defaultInterviewerAvatar.checked = true;
 }
 if (Array.isArray(restoredSetupDraft?.visited_step_ids)) {
 restoredSetupDraft.visited_step_ids.forEach(stepId => visitedSetupStepIds.add(stepId));
 }
 let initialSetupStepIndex = Number.isFinite(Number(restoredSetupDraft?.active_step_index))? Number(restoredSetupDraft.active_step_index): 0;

 document.querySelectorAll('input[name="camera_detection"]').forEach(el => {
 el.addEventListener('change', () => {
 visitedSetupStepIds.add('panel-inclusive');
 updateSummary();
 });
 });

 function getSetupSteps() {
 return [...setupStepState.baseSteps, { id: 'panel-summary', label: 'Summary' }];
 }

 function getRequiredSetupReviewStepIds() {
 return setupStepState.baseSteps.map(step => step.id);
 }

 function isSetupStepComplete(stepId) {
 if (stepId === 'panel-summary') {
 return getRequiredSetupReviewStepIds().every(requiredStepId => isSetupStepComplete(requiredStepId));
 }

 return missingSetupItems(stepId).length === 0;
 }

 function getMaxAccessibleSetupStepIndex(steps = getSetupSteps()) {
 let maxAccessibleIndex = 0;

 for (let index = 0; index < steps.length - 1; index++) {
 if (!isSetupStepComplete(steps[index]?.id)) break;
 maxAccessibleIndex = index + 1;
 }

 return maxAccessibleIndex;
 }

 function syncSetupStepperState(steps = getSetupSteps()) {
 const maxAccessibleIndex = getMaxAccessibleSetupStepIndex(steps);

 steps.forEach((step, index) => {
 const stepButton = document.querySelector(`[data-setup-step="${index}"]`);
 if (!stepButton) return;

 const isActive = index === setupStepState.index;
 const isLocked = index > maxAccessibleIndex;
 const isReady = step.id !== 'panel-summary' && isSetupStepComplete(step.id);
 const isComplete = step.id !== 'panel-summary' && isSetupStepComplete(step.id) && index < setupStepState.index;

 stepButton.classList.toggle('is-active', isActive);
 stepButton.classList.toggle('is-ready', isReady);
 stepButton.classList.toggle('is-complete', isComplete);
 stepButton.classList.toggle('is-locked', isLocked);
 stepButton.setAttribute('aria-current', isActive? 'step': 'false');
 stepButton.setAttribute('aria-disabled', isLocked? 'true': 'false');
 stepButton.setAttribute('aria-label', isLocked? `${step.label} locked until previous selections are complete`: `Go to ${step.label}`);
 stepButton.title = isLocked? 'Complete previous selections first': `Go to ${step.label}`;
 stepButton.tabIndex = isLocked? -1: 0;
 });
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
 <span class="setup-stepper-label"><span class="setup-stepper-label-text">${step.label}</span><i class="fa-solid fa-lock setup-stepper-lock" aria-hidden="true"></i></span>
 </button>
 `).join('');
 track.dataset.rendered = mode;

 track.querySelectorAll('[data-setup-step]').forEach(button => {
 button.addEventListener('click', () => {
 const targetIndex = Number(button.dataset.setupStep);
 const steps = getSetupSteps();
 const maxAccessibleIndex = getMaxAccessibleSetupStepIndex(steps);
 if (targetIndex > maxAccessibleIndex) {
 showSetupStep(maxAccessibleIndex);
 validateSetupStep(steps[maxAccessibleIndex]?.id, true);
 return;
 }
 if (targetIndex > setupStepState.index) {
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
 const requestedIndex = Math.max(0, Math.min(steps.length - 1, nextIndex));
 setupStepState.index = Math.min(requestedIndex, getMaxAccessibleSetupStepIndex(steps));
 if (steps[setupStepState.index]?.id) {
 visitedSetupStepIds.add(steps[setupStepState.index].id);
 }

 section.classList.add('setup-step-mode');
 section.classList.toggle('setup-summary-step',!isDesktop && steps[setupStepState.index]?.id === 'panel-summary');
 stepper.hidden = false;

 const setupActions = document.querySelector('.setup-stepper-actions');
 const activePanel = document.getElementById(steps[setupStepState.index]?.id);
 if (setupActions) {
 if (!isDesktop && activePanel) {
 activePanel.appendChild(setupActions);
 } else if (stepper) {
 stepper.appendChild(setupActions);
 }
 }

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

 });

 syncSetupStepperState(steps);

 if (prevButton) prevButton.disabled = setupStepState.index === 0;
 if (nextButton) {
 const isLast = setupStepState.index === steps.length - 1;
 nextButton.hidden = isLast;
 }

 updateSummary();
 setupDraftApi?.save(setupDraftFormElement, {
 activeStepIndex: setupStepState.index,
 visitedStepIds: Array.from(visitedSetupStepIds),
 });

 }

 let setupTutorialRestoreIndex = null;

 function getSetupPanelForElement(element) {
 if (!element || typeof element.closest!== 'function') return null;

 if (element.id === 'btn-start-interview') {
 return document.getElementById('panel-summary');
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
 showSetupStep(initialSetupStepIndex);
 }

 function revealInterviewSetupPage() {
 const section = document.getElementById('sec-interview-setup');
 if (!section) return;

 const reveal = () => {
 section.classList.remove('setup-page-booting');
 section.setAttribute('aria-busy', 'false');
 section.dataset.setupReady = 'true';
 };

 if (typeof window.requestAnimationFrame === 'function') {
 window.requestAnimationFrame(() => window.requestAnimationFrame(reveal));
 } else {
 window.setTimeout(reveal, 0);
 }
 }

 initializeInterviewSetupPage();
 revealInterviewSetupPage();
 window.addEventListener('load', initializeInterviewSetupPage, { once: true });

 const setupForm = document.getElementById('setupForm');
 const setupTransitionOverlay = document.getElementById('setupTransitionOverlay');
 const startInterviewButton = document.getElementById('btn-start-interview');
 const setupAutoFullscreenPreferenceKey = 'speakready.interview.autoFullscreen';
 let setupFormSubmitting = false;
 setupDraftApi?.watch(setupForm, {
 activeStepIndex: () => setupStepState.index,
 visitedStepIds: () => Array.from(visitedSetupStepIds),
 beforeSave: () => syncSetupTargetFieldCopy(),
 });

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
 return root.requestFullscreen({ navigationUI: 'hide' }).catch(() => {});
 }
 return Promise.resolve();
 }

 function ensureSetupTransitionFullscreenOverlay() {
 if (setupTransitionOverlay && setupTransitionOverlay.parentElement!== document.body) {
 document.body.appendChild(setupTransitionOverlay);
 }
 }

 let setupLoadingProgressTimer = null;
 let setupLoadingChecklistTimers = [];
let setupLoadingReady = false;
let setupLoadingSubmitStarted = false;

 function setupLoadingSelectedOptionText(selectId) {
 const select = document.getElementById(selectId);
 if (!select || !select.value) return '';
 const option = select.options[select.selectedIndex];
 return String(option?.dataset.contextLabel || option?.text || select.value || '').trim();
 }

 function setupLoadingTargetValue() {
 const field = document.getElementById('valPosition');
 if (!field) return '';
 if (typeof setupTargetFieldValue === 'function') {
 return setupTargetFieldValue(field);
 }
 return String(field.value || '').trim();
 }

 function setupLoadingCheckedLabel(name) {
 const input = document.querySelector(`input[name="${name}"]:checked`);
 if (!input) return '';
 return input.dataset.summaryLabel
 || input.closest('label')?.querySelector('strong')?.textContent
 || input.closest('label')?.querySelector('.structure-difficulty-title, .response-mode-title')?.textContent
 || titleizeSetupValue(input.value);
 }

 function setupLoadingFeedbackModeLabel() {
 const radioLabel = setupLoadingCheckedLabel('live_feedback_mode');
 if (radioLabel) return radioLabel;
 return setupLoadingSelectedOptionText('valFeedbackMode');
 }

 function setupLoadingTimeLimitLabel() {
 const select = document.getElementById('valTimeLimit');
 if (!select || select.value === '') return '';
 const optionText = select.options[select.selectedIndex]?.text || '';
 return optionText.replace(/\s+per\s+Question/i, '/question');
 }

 function setupLoadingQuestionCountLabel() {
 const count = String(document.getElementById('valNumQuestions')?.value || '').trim();
 if (!count) return '';
 return `${count} ${Number(count) === 1? 'question': 'questions'}`;
 }

 function setSetupLoadingItem(key, title, detail) {
 const item = setupTransitionOverlay?.querySelector(`[data-loading-item="${key}"]`);
 if (!item) return;
 const titleEl = item.querySelector('[data-loading-title]');
 const detailEl = item.querySelector('[data-loading-detail]');
 if (titleEl) titleEl.textContent = title;
 if (detailEl) detailEl.textContent = detail;
 }

 function updateSetupTransitionDetails() {
 const scenario = setupLoadingSelectedOptionText('valScenario') || 'Interview scenario';
 const target = setupLoadingTargetValue() || 'Target role';
 const difficulty = setupLoadingCheckedLabel('difficulty') || 'Difficulty';
 const questions = setupLoadingQuestionCountLabel() || 'Questions';
 const timeLimit = setupLoadingTimeLimitLabel() || 'Timing';
 const camera = setupLoadingCheckedLabel('camera_detection') || (document.querySelector('input[name="camera_detection"]:checked')?.value === '1'? 'Camera On': 'Camera Off');
 const feedback = setupLoadingFeedbackModeLabel() || 'Feedback mode';
 const questionTypes = Array.from(document.querySelectorAll('input[name="question_types[]"]:checked')).map(input => input.value).join(', ') || 'Question mix';
 const response = setupLoadingCheckedLabel('response_mode') || 'Response mode';

 setSetupLoadingItem('details', 'Details configured', `${scenario} - ${target}`);
 setSetupLoadingItem('structure', 'Structure configured', `${difficulty} - ${questions} - ${timeLimit}`);
 setSetupLoadingItem('camera', 'Camera configured', camera);
 setSetupLoadingItem('coaching', 'Coaching configured', `${feedback} - ${questionTypes}`);
 setSetupLoadingItem('response', 'Response configured', `${response} answers ready`);
 }

 function setSetupLoadingProgress(value) {
const progress = Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
 const bar = document.getElementById('setupLoadingProgressBar');
 const percent = document.getElementById('setupLoadingPercent');
 const track = setupTransitionOverlay?.querySelector('.interview-prep-progress-track');
 if (bar) bar.style.width = `${progress}%`;
 if (percent) percent.textContent = `${progress}%`;
 if (track) track.setAttribute('aria-valuenow', String(progress));
 }

function setSetupLoadingActionsReady(ready) {
setupLoadingReady = Boolean(ready);
['setupPrepCancelButton', 'setupPrepBeginButton'].forEach(id => {
const button = document.getElementById(id);
if (!button) return;
const disabled = !setupLoadingReady || setupLoadingSubmitStarted;
button.disabled = disabled;
button.setAttribute('aria-disabled', disabled? 'true': 'false');
button.classList.toggle('is-ready', setupLoadingReady && !setupLoadingSubmitStarted);
});
}

function setSetupLoadingSubmitting(submitting) {
setupLoadingSubmitStarted = Boolean(submitting);
const beginButton = document.getElementById('setupPrepBeginButton');
const cancelButton = document.getElementById('setupPrepCancelButton');
if (beginButton) {
beginButton.disabled = setupLoadingSubmitStarted || !setupLoadingReady;
beginButton.setAttribute('aria-disabled', beginButton.disabled? 'true': 'false');
beginButton.classList.toggle('is-ready', setupLoadingReady && !setupLoadingSubmitStarted);
beginButton.innerHTML = setupLoadingSubmitStarted? 'Starting <i class="fa-solid fa-spinner fa-spin"></i>': 'Begin Interview <i class="fa-solid fa-play"></i>';
}
if (cancelButton) {
cancelButton.disabled = setupLoadingSubmitStarted || !setupLoadingReady;
cancelButton.setAttribute('aria-disabled', cancelButton.disabled? 'true': 'false');
cancelButton.classList.toggle('is-ready', setupLoadingReady && !setupLoadingSubmitStarted);
}
}

 function clearSetupLoadingChecklistTimers() {
 setupLoadingChecklistTimers.forEach(timerId => window.clearTimeout(timerId));
 setupLoadingChecklistTimers = [];
 }

 function resetSetupLoadingChecklist() {
 clearSetupLoadingChecklistTimers();
 setupTransitionOverlay?.querySelectorAll('.interview-prep-check-row').forEach(row => {
 row.classList.remove('is-checking', 'is-complete');
 row.querySelector('.interview-prep-row-check')?.setAttribute('aria-label', 'Pending');
 });
 }

function completeSetupLoadingChecklist() {
clearSetupLoadingChecklistTimers();
setupTransitionOverlay?.querySelectorAll('.interview-prep-check-row').forEach(row => {
row.classList.remove('is-checking');
row.classList.add('is-complete');
row.querySelector('.interview-prep-row-check')?.setAttribute('aria-label', 'Complete');
});
}

 function startSetupLoadingChecklist() {
 resetSetupLoadingChecklist();
 const rows = Array.from(setupTransitionOverlay?.querySelectorAll('.interview-prep-check-row') || []);
 rows.forEach((row, index) => {
 const checkingTimer = window.setTimeout(() => {
 row.classList.add('is-checking');
 row.querySelector('.interview-prep-row-check')?.setAttribute('aria-label', 'Checking');

 const completeTimer = window.setTimeout(() => {
 row.classList.remove('is-checking');
 row.classList.add('is-complete');
 row.querySelector('.interview-prep-row-check')?.setAttribute('aria-label', 'Complete');
 }, 260);

 setupLoadingChecklistTimers.push(completeTimer);
 }, 180 + (index * 430));

 setupLoadingChecklistTimers.push(checkingTimer);
 });
 }

 function startSetupLoadingProgress() {
 window.clearInterval(setupLoadingProgressTimer);
setSetupLoadingActionsReady(false);
 let progress = 19;
 setSetupLoadingProgress(progress);
 setupLoadingProgressTimer = window.setInterval(() => {
const step = progress < 48? 7: (progress < 78? 4: (progress < 94? 2: 1));
progress = Math.min(100, progress + step);
 setSetupLoadingProgress(progress);
if (progress >= 100) {
 window.clearInterval(setupLoadingProgressTimer);
 setupLoadingProgressTimer = null;
completeSetupLoadingChecklist();
setSetupLoadingActionsReady(true);
document.getElementById('setupPrepBeginButton')?.focus();
 }
 }, 260);
 }

 function prepareSetupTransitionOverlay() {
 updateSetupTransitionDetails();
 startSetupLoadingProgress();
 startSetupLoadingChecklist();
 }

 function resetSetupTransitionOverlay() {
 window.clearInterval(setupLoadingProgressTimer);
 setupLoadingProgressTimer = null;
setupLoadingSubmitStarted = false;
 setSetupLoadingProgress(19);
 resetSetupLoadingChecklist();
setSetupLoadingActionsReady(false);
setSetupLoadingSubmitting(false);
 }

function submitSetupFormAfterLoadingFrame(delay = 120) {
 window.setTimeout(() => {
 if (typeof HTMLFormElement !== 'undefined' && HTMLFormElement.prototype.submit) {
 HTMLFormElement.prototype.submit.call(setupForm);
 return;
 }
 setupForm.submit();
}, delay);
}

function exitSetupBrowserFullscreen() {
if (document.fullscreenElement && document.exitFullscreen) {
return document.exitFullscreen().catch(() => {});
}
return Promise.resolve();
}

function cancelSetupPreparedInterview() {
if (!setupLoadingReady || setupLoadingSubmitStarted) return;
setupFormSubmitting = false;
setupTransitionOverlay.classList.remove('active');
document.documentElement.classList.remove('finish-transition-active');
document.body.classList.remove('finish-transition-active');
resetSetupTransitionOverlay();
if (startInterviewButton) {
startInterviewButton.innerHTML = startInterviewButton.dataset.defaultLabel || 'Start <i class="fa-solid fa-play ms-2"></i>';
updateSummary();
}
exitSetupBrowserFullscreen();
}

function beginPreparedInterview() {
if (!setupLoadingReady || setupLoadingSubmitStarted) return;
setSetupLoadingSubmitting(true);
requestSetupBrowserFullscreen().finally(() => {
submitSetupFormAfterLoadingFrame();
});
 }

 if (setupForm && setupTransitionOverlay) {
 ensureSetupTransitionFullscreenOverlay();
 setupForm.addEventListener('submit', function(event) {
 if (setupFormSubmitting) return;
 updateStartInterviewState();
 setupDraftApi?.save(setupForm, {
 activeStepIndex: setupStepState.index,
 visitedStepIds: Array.from(visitedSetupStepIds),
 });
 if (!validateSetupForm(true) || startInterviewButton?.disabled) {
 event.preventDefault();
 return;
 }

 event.preventDefault();
 setupFormSubmitting = true;
 ensureSetupTransitionFullscreenOverlay();
 prepareSetupTransitionOverlay();
 setupTransitionOverlay.classList.add('active');
 document.documentElement.classList.add('finish-transition-active');
 document.body.classList.add('finish-transition-active');
 requestSetupBrowserFullscreen();

 if (startInterviewButton) {
 startInterviewButton.disabled = true;
startInterviewButton.innerHTML = startInterviewButton.dataset.loadingLabel || 'Preparing Interview <i class="fa-solid fa-spinner fa-spin ms-2"></i>';
 }
 });

 window.addEventListener('pageshow', function() {
 setupFormSubmitting = false;
 setupTransitionOverlay.classList.remove('active');
 document.documentElement.classList.remove('finish-transition-active');
 document.body.classList.remove('finish-transition-active');
 resetSetupTransitionOverlay();

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
 body.interview-setup-page #sec-interview-setup .col-lg-4,
 body.interview-setup-page #sec-interview-setup .setup-summary-wrap,
 body.interview-setup-page #sec-interview-setup .setup-panel,
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

 body.interview-setup-page #sec-interview-setup .setup-summary-wrap,
 body.interview-setup-page #sec-interview-setup #panel-summary,
 body.interview-setup-page #sec-interview-setup .col-lg-4 > div {
 position: static!important;
 top: auto!important;
 }

 body.interview-setup-page #dashboard .db-nav,
 html body.user-desktop-shell.interview-setup-page:not(.admin-shell) #dashboard .db-nav {
 scrollbar-width: none!important;
 -ms-overflow-style: none!important;
 }

 body.interview-setup-page #dashboard .db-nav::-webkit-scrollbar,
 html body.user-desktop-shell.interview-setup-page:not(.admin-shell) #dashboard .db-nav::-webkit-scrollbar {
 width: 0!important;
 height: 0!important;
 display: none!important;
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
 { element: '#setupStepper', popover: { title: 'Interview Setup Steps', description: 'Work through Details, Structure, Camera, Scenario, Response, and Summary before starting.', side: 'bottom', align: 'center' }},
 { element: '#valScenario', popover: { title: 'Practice Scenario', description: 'Choose the interview scenario that matches the kind of practice you want.', side: 'bottom', align: 'start' }},
 { element: '#jobCategoryFilter', popover: { title: 'Job Category', description: 'Filter the target role list by category, or keep All job categories to search everything.', side: 'bottom', align: 'start' }},
 { element: '#targetPositionDropdownButton', popover: { title: 'Target Role', description: 'Search and select a role, or enter your own position if it is not listed. Your questions will use this target.', side: 'bottom', align: 'start' }},
 { element: '#roleFinderOpenButton', popover: { title: 'Find a Role', description: 'Share skills, interests, qualifications, and experience to get role ideas for interview practice.', side: 'bottom', align: 'start' }},
 { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Choose difficulty, question count, and a per-question time limit. You can also select No Limit.', side: 'top', align: 'center' }},
 { element: '#panel-inclusive', popover: { title: 'Camera Detection', description: 'Camera On enables body-language observations such as framing and posture. Camera Off leaves detection off.', side: 'top', align: 'center' }},
 { element: '#valFeedbackMode', popover: { title: 'Live Feedback', description: 'Choose Coaching On for help during the session, or Real Interview Mode for a quieter practice run.', side: 'bottom', align: 'start' }},
 { element: '#questionTypeGroup', popover: { title: 'Question Types', description: 'Select one or more Behavioral, Situational, Technical, or Personal question types.', side: 'top', align: 'center' }},
 { element: '#panel-response', popover: { title: 'Response and AI Interviewer', description: 'Choose Text, Voice, or Hybrid for your answers, then select the interviewer portrait and style you want to practice with.', side: 'top', align: 'center' }},
 { element: '#panel-summary', popover: { title: 'Interview Summary', description: 'Check the scenario, role, timing, camera, feedback, question mix, response mode, and AI interviewer before starting.', side: 'top', align: 'center' }},
 { element: '.setup-stepper-actions', popover: { title: 'Continue and Start', description: 'Use Next to complete the required steps. Start appears after you review the Summary.', side: 'top', align: 'center' }}
 ];

 window.createSpeakReadyTour({
 completionKey: 'onboarding_completed_interview_setup',
 serverDetectedMobile: true,
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
