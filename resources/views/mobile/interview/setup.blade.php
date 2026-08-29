@extends('mobile.layouts.app')
@section('title', 'Philippines Interview Setup')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/interview/setup.css?v=4') }}" data-page-style="interview-setup">
<link rel="stylesheet" href="{{ asset('css/mobile/interview/setup-2.css?v=1') }}" data-page-style="interview-setup-2">
@endpush

@section('content')
@php
    $sourcePacks = $sourcePacks ?? [];
    $interviewCategories = ($categories ?? collect())
        ->filter(function ($category): bool {
            $title = strtolower(trim(preg_replace('/\s+/', ' ', str_replace('/', ' / ', (string) $category->title)) ?? ''));

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
    $selectedApplication = $selectedApplication ?? null;
    $selectedPack = $selectedPack ?? null;
    $packQuestionTypes = collect($selectedPack?->question_types ?? [])
        ->filter(fn ($type) => in_array($type, ['Behavioral', 'Situational', 'Technical', 'Personal'], true))
        ->values()
        ->all();

    $scenarioLabelForCategory = function (?string $categoryTitle): string {
        $title = trim((string) $categoryTitle);
        $displayTitle = trim(preg_replace('/\s*\/\s*/', ' / ', $title) ?? $title);
        $key = strtolower(trim(preg_replace('/\s+/', ' ', $displayTitle) ?? $displayTitle));
        $knownLabels = [
            'job interview' => 'Philippines Job Interviews',
            'general job interview' => 'Philippines Job Interviews',
            'college admission' => 'Philippines School Admission Interviews',
            'college admission interview' => 'Philippines School Admission Interviews',
            'school admission' => 'Philippines School Admission Interviews',
            'school admission interview' => 'Philippines School Admission Interviews',
        ];

        if (isset($knownLabels[$key])) {
            return $knownLabels[$key];
        }

        if ($displayTitle === '') {
            return 'Philippines Job Interviews';
        }

        if (! str_contains($key, 'interview')) {
            $displayTitle .= ' Interview';
        }

        return str_contains($key, 'philipp') ? $displayTitle : "Philippines {$displayTitle}";
    };

    $focusForCategory = function (?string $categoryTitle, string $label): string {
        $title = strtolower((string) $categoryTitle);

        if (str_contains($title, 'job') && ! str_contains($title, 'bpo') && ! str_contains($title, 'customer')) {
            return 'Philippines Job Interview';
        }

        return $label;
    };

    $scenarioOptions = $interviewCategories
        ->map(function ($category) use ($sourcePacks, $scenarioLabelForCategory, $focusForCategory) {
            $key = \App\Services\QuestionDatasetProvider::defaultKeyForCategory($category->title);
            $pack = $sourcePacks[$key] ?? collect($sourcePacks)->first() ?? [];
            $label = $scenarioLabelForCategory($category->title);
            $sourceSummary = collect($pack['sources'] ?? [])
                ->pluck('name')
                ->take(3)
                ->implode(', ');

            return [
                'key' => $key,
                'category_id' => $category->id,
                'label' => $label,
                'focus' => $focusForCategory($category->title, $label),
                'context_label' => $label,
                'source_summary' => $sourceSummary ?: 'Philippines career and education sources',
            ];
        })
        ->values();
    $firstScenario = $scenarioOptions->first();
    $packText = strtolower(implode(' ', array_filter([
        $selectedPack?->name,
        $selectedPack?->company,
        $selectedPack?->role_family,
        $selectedPack?->interview_focus,
        $selectedPack?->company_persona,
    ])));
    $packScenario = null;
    if ($packText !== '') {
        $packScenarioNeedles = match (true) {
            str_contains($packText, 'college') || str_contains($packText, 'school') || str_contains($packText, 'admission') => ['school admission', 'college', 'admission'],
            default => ['job interview', 'job interviews'],
        };

        $packScenario = $scenarioOptions->first(function ($scenario) use ($packScenarioNeedles) {
            $label = strtolower($scenario['label'].' '.$scenario['focus']);

            foreach ($packScenarioNeedles as $needle) {
                if (str_contains($label, $needle)) {
                    return true;
                }
            }

            return false;
        });
    }
    $selectedCategoryId = (int) old('category_id', $packScenario['category_id'] ?? ($firstScenario['category_id'] ?? 0));
    $selectedSourcePackKey = old('source_pack_key');
    $selectedScenario = $scenarioOptions->first(fn ($scenario) => (int) $scenario['category_id'] === $selectedCategoryId)
        ?? $scenarioOptions->firstWhere('key', $selectedSourcePackKey)
        ?? $scenarioOptions->first();
    $packDifficulty = in_array($selectedPack?->difficulty, ['easy', 'medium', 'hard'], true)
        ? $selectedPack->difficulty
        : 'medium';
    $targetPositionDefault = old(
        'target_position',
        $selectedApplication?->job_title
            ?? ($selectedPack?->role_family ? $selectedPack->role_family.' Role' : ($selectedPack?->name ?? ''))
    );
    $companyPersonaDefault = $selectedPack?->company_persona
        ?? ($selectedApplication?->company_name ? $selectedApplication->company_name.' hiring context' : 'Philippines hiring context');
    $setupDefaults = [
        'difficulty' => old('difficulty', $selectedPack ? $packDifficulty : 'medium'),
        'num_questions' => (string) old('num_questions', 10),
        'time_limit' => (string) old('time_limit', $selectedPack?->pressure_mode ? 2 : 0),
        'interview_focus' => old('interview_focus', $selectedPack?->interview_focus ?? ($selectedScenario['focus'] ?? 'Philippines Job Interview')),
        'ai_assistance_level' => old('ai_assistance_level', 'standard'),
        'interviewer_strictness' => old('interviewer_strictness', $selectedPack?->pressure_mode ? 'strict' : 'neutral'),
        'live_feedback_mode' => old('live_feedback_mode', $selectedPack?->pressure_mode ? 'real_interview' : 'coaching'),
        'response_mode' => old('response_mode', 'voice'),
        'company_persona' => old('company_persona', $companyPersonaDefault),
        'interview_format' => old('interview_format', 'standard'),
    ];
    $selectedQuestionTypes = old('question_types', $packQuestionTypes ?: ['Behavioral', 'Situational']);
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
                    Philippines Interview Setup
                </h4>
                <p class="setup-hero-subtitle">Practice a Philippines-focused mock interview with local HR screening, role-fit, and communication expectations.</p>
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

    <form action="{{ route('interview.start') }}" method="POST" id="setupForm">
        @csrf
        @if($selectedApplication)
            <input type="hidden" name="job_application_id" value="{{ $selectedApplication->id }}">
            <textarea name="resume_text" hidden>{{ old('resume_text', $selectedApplication->resume_text) }}</textarea>
            <textarea name="job_description" hidden>{{ old('job_description', $selectedApplication->job_description) }}</textarea>
        @endif
        @if($selectedPack)
            <input type="hidden" name="interview_pack_id" value="{{ $selectedPack->id }}">
        @endif
        <div class="row g-4">
            <!-- Left Column: Form Settings -->
            <div class="col-lg-8" id="setup-left-col">
                <div class="setup-stepper" id="setupStepper" aria-label="Interview setup steps">
                    <div class="setup-stepper-track" id="setupStepperTrack"></div>
                    <div class="setup-stepper-actions">
                        <button type="button" class="setup-step-btn" id="setupStepPrev"><i class="fa-solid fa-arrow-left"></i> Back</button>
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
                            <h5 class="setup-details-card-title">Philippines Interview Details</h5>
                        </div>
                    </div>

                    <div class="setup-card-fields">
                        <div class="setup-card-field">
                            <label class="setup-card-label" for="valScenario">
                                <span class="setup-card-label-icon" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
                                Practice Scenario
                            </label>
                            <div class="setup-select-wrap">
                                <select class="oinp setup-input" name="category_id" id="valScenario" aria-describedby="scenarioHelp{{ $hasScenarioOptions ? '' : ' scenarioEmptyState' }}" aria-invalid="{{ $hasScenarioOptions ? 'false' : 'true' }}" required>
                                @forelse($scenarioOptions as $scenario)
                                    <option value="{{ $scenario['category_id'] }}"
                                        data-source-pack-key="{{ $scenario['key'] }}"
                                        data-focus="{{ $scenario['focus'] }}"
                                        data-context-label="{{ $scenario['context_label'] }}"
                                        data-source-summary="{{ $scenario['source_summary'] }}"
                                        {{ $selectedScenario && (int) $selectedScenario['category_id'] === (int) $scenario['category_id'] ? 'selected' : '' }}>
                                        {{ $scenario['label'] }}
                                    </option>
                                @empty
                                    <option value="" selected>No active interview scenarios available</option>
                                @endforelse
                                </select>
                            </div>
                            <input type="hidden" name="source_pack_key" id="valSourcePack" value="{{ $selectedScenario['key'] ?? '' }}">
                            <input type="hidden" name="interview_focus" id="valFocus" value="{{ $setupDefaults['interview_focus'] }}" class="setup-input">
                            @unless($hasScenarioOptions)
                                <div class="setup-inline-error setup-inline-error-visible" id="scenarioEmptyState" role="alert">No active interview scenarios are available. Ask an admin to activate at least one core category before starting.</div>
                            @endunless
                        </div>

                        <div class="setup-card-field">
                            <label class="setup-card-label" for="valPosition">
                                <span class="setup-card-label-icon" aria-hidden="true"><i class="fa-solid fa-bullseye-arrow"></i></span>
                                Target Position
                            </label>
                            <div class="setup-search-wrap">
                                <input class="oinp setup-input" type="text" name="target_position" id="valPosition" placeholder="e.g. Call Center Agent, Teacher, Software Developer" value="{{ $targetPositionDefault }}" required aria-describedby="targetPositionError">
                            </div>
                            <div class="desc-text" id="scenarioHelp">Choose either job interviews or school admission interviews.</div>
                            <div class="setup-inline-error" id="targetPositionError" role="alert" hidden>Enter the target position before continuing.</div>
                        </div>

                        <div class="setup-calibrated-simple">
                            <div class="setup-calibrated-icon" aria-hidden="true">
                                <i class="fa-solid fa-database"></i>
                            </div>
                            <div>
                                <h6>Philippines-calibrated practice</h6>
                                <p><strong>Sources:</strong> <span id="sourceSummary">{{ $selectedScenario['source_summary'] ?? 'Philippines career and education sources' }}</span></p>
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
                            <input type="radio" name="difficulty" value="easy" class="setup-input" {{ $setupDefaults['difficulty'] === 'easy' ? 'checked' : '' }}>
                            <span>
                                <span class="structure-difficulty-title">Easy</span>
                                <span class="structure-difficulty-desc">Basic and introductory questions</span>
                            </span>
                            <span class="structure-difficulty-icon" aria-hidden="true"><i class="fa-solid fa-signal"></i></span>
                        </label>
                        <label class="structure-difficulty-card">
                            <input type="radio" name="difficulty" value="medium" class="setup-input" {{ $setupDefaults['difficulty'] === 'medium' ? 'checked' : '' }}>
                            <span>
                                <span class="structure-difficulty-title">Medium</span>
                                <span class="structure-difficulty-desc">Common interview questions</span>
                            </span>
                            <span class="structure-difficulty-icon" aria-hidden="true"><i class="fa-solid fa-star"></i></span>
                        </label>
                        <label class="structure-difficulty-card">
                            <input type="radio" name="difficulty" value="hard" class="setup-input" {{ $setupDefaults['difficulty'] === 'hard' ? 'checked' : '' }}>
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
                                <option value="1" {{ $setupDefaults['num_questions'] === '1' ? 'selected' : '' }}>1 Question</option>
                                <option value="3" {{ $setupDefaults['num_questions'] === '3' ? 'selected' : '' }}>3 Questions</option>
                                <option value="5" {{ $setupDefaults['num_questions'] === '5' ? 'selected' : '' }}>5 Questions</option>
                                <option value="10" {{ $setupDefaults['num_questions'] === '10' ? 'selected' : '' }}>10 Questions</option>
                                <option value="15" {{ $setupDefaults['num_questions'] === '15' ? 'selected' : '' }}>15 Questions</option>
                                <option value="20" {{ $setupDefaults['num_questions'] === '20' ? 'selected' : '' }}>20 Questions</option>
                                <option value="25" {{ $setupDefaults['num_questions'] === '25' ? 'selected' : '' }}>25 Questions</option>
                                <option value="30" {{ $setupDefaults['num_questions'] === '30' ? 'selected' : '' }}>30 Questions</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="olbl" for="valTimeLimit">Time Limit</label>
                            <div class="structure-select-wrap">
                                <select class="oinp setup-input" name="time_limit" id="valTimeLimit">
                                <option value="0" {{ $setupDefaults['time_limit'] === '0' ? 'selected' : '' }}>No Limit</option>
                                <option value="1" {{ $setupDefaults['time_limit'] === '1' ? 'selected' : '' }}>1 Minute per Question</option>
                                <option value="2" {{ $setupDefaults['time_limit'] === '2' ? 'selected' : '' }}>2 Minutes per Question</option>
                                <option value="3" {{ $setupDefaults['time_limit'] === '3' ? 'selected' : '' }}>3 Minutes per Question</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="olbl" for="valInterviewFormat">Interview Format Laboratory</label>
                            <div class="structure-select-wrap">
                                <select class="oinp setup-input" name="interview_format" id="valInterviewFormat">
                                @foreach([
                                    'standard' => 'Standard live interview',
                                    'hr_screen' => 'HR screening',
                                    'hiring_manager' => 'Hiring manager',
                                    'panel' => 'Multi-perspective panel',
                                    'phone' => 'Telephone interview',
                                    'asynchronous' => 'One-way recorded interview',
                                    'technical' => 'Technical deep dive',
                                    'case' => 'Case interview',
                                    'presentation' => 'Presentation defense',
                                ] as $formatValue => $formatLabel)
                                    <option value="{{ $formatValue }}" {{ $setupDefaults['interview_format'] === $formatValue ? 'selected' : '' }}>{{ $formatLabel }}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="structure-info-note">
                        <i class="fa-solid fa-info" aria-hidden="true"></i>
                        <span>Feedback is adjusted to the selected format; camera behavior remains optional.</span>
                    </div>
                </div>

                <div class="setup-panel setup-inclusive-card animate-fade-up delay-300" id="panel-inclusive">
                    <div class="setup-inclusive-head">
                        <div class="setup-inclusive-head-icon" aria-hidden="true">
                            <i class="fa-solid fa-universal-access"></i>
                        </div>
                        <h5 class="setup-inclusive-title">Inclusive Practice Conditions</h5>
                    </div>
                    <p class="setup-inclusive-copy">Choose conditions that give you an accurate opportunity to demonstrate job-related ability. These settings are recorded with the assessment.</p>
                    @php $inclusive = Auth::user()->profile?->inclusive_preferences ?? []; @endphp
                    <div class="inclusive-option-list">
                        @foreach([
                            'camera_coaching' => 'Optional body-language coach',
                            'separate_language_scoring' => 'Separate language mechanics',
                            'extended_time' => 'Extended response time',
                            'captions' => 'Captions / transcript controls',
                            'reduced_distraction' => 'Reduced-distraction workspace',
                            'simplified_questions' => 'Clearer question wording',
                        ] as $preferenceKey => $preferenceLabel)
                            <label class="inclusive-option"><input type="checkbox" name="{{ $preferenceKey }}" value="1" {{ old($preferenceKey, data_get($inclusive, $preferenceKey, false)) ? 'checked' : '' }}> <span>{{ $preferenceLabel }}</span></label>
                        @endforeach
                    </div>
                    <div class="inclusive-note">
                        <i class="fa-solid fa-info" aria-hidden="true"></i>
                        <span><strong>Important:</strong> body-language signals are never included in the readiness score. Camera coaching only reports visible framing, head alignment, hand/shoulder/posture cues, and movement steadiness. It does not infer confidence, honesty, personality, or employability.</span>
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
                        <div class="assistance-field">
                            <label class="olbl" for="valAssistance">AI Assistance Level</label>
                            <div class="assistance-select-wrap">
                                <select class="oinp setup-input" name="ai_assistance_level" id="valAssistance">
                                    <option value="beginner" {{ $setupDefaults['ai_assistance_level'] === 'beginner' ? 'selected' : '' }}>Beginner Mode (More hints & feedback)</option>
                                    <option value="standard" {{ $setupDefaults['ai_assistance_level'] === 'standard' ? 'selected' : '' }}>Standard Mode (Balanced experience)</option>
                                    <option value="challenge" {{ $setupDefaults['ai_assistance_level'] === 'challenge' ? 'selected' : '' }}>Challenge Mode (No hints, harder follow-ups)</option>
                                </select>
                            </div>
                        </div>

                        <div class="assistance-field">
                            <label class="olbl" id="questionTypesLabel">Question Types</label>
                            <div class="assistance-question-list" id="questionTypeGroup" role="group" aria-labelledby="questionTypesLabel" aria-describedby="questionTypeError" aria-invalid="false">
                                @foreach([
                                    'Behavioral' => 'fa-regular fa-message',
                                    'Situational' => 'fa-regular fa-user',
                                    'Technical' => 'fa-solid fa-code',
                                    'Personal' => 'fa-regular fa-user-circle',
                                ] as $questionType => $questionIcon)
                                    <label class="assistance-question-card">
                                        <input type="checkbox" name="question_types[]" value="{{ $questionType }}" {{ in_array($questionType, $selectedQuestionTypes, true) ? 'checked' : '' }}>
                                        <span class="assistance-question-icon" aria-hidden="true"><i class="{{ $questionIcon }}"></i></span>
                                        <span class="assistance-question-text">{{ $questionType }} Questions</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="setup-inline-error" id="questionTypeError" role="alert" hidden>Select at least one question type.</div>
                        </div>

                        <div class="assistance-field">
                            <label class="olbl" for="valStrictness">Interviewer Strictness</label>
                            <div class="assistance-select-wrap">
                                <select class="oinp setup-input" name="interviewer_strictness" id="valStrictness">
                                    <option value="friendly" {{ $setupDefaults['interviewer_strictness'] === 'friendly' ? 'selected' : '' }}>Friendly Interviewer</option>
                                    <option value="neutral" {{ $setupDefaults['interviewer_strictness'] === 'neutral' ? 'selected' : '' }}>Neutral HR Interviewer</option>
                                    <option value="strict" {{ $setupDefaults['interviewer_strictness'] === 'strict' ? 'selected' : '' }}>Strict Technical Lead</option>
                                    <option value="executive" {{ $setupDefaults['interviewer_strictness'] === 'executive' ? 'selected' : '' }}>Executive Panel</option>
                                </select>
                            </div>
                        </div>

                        <div class="assistance-field">
                            <label class="olbl" for="valFeedbackMode">Live Feedback Mode</label>
                            <div class="assistance-select-wrap">
                                <select class="oinp setup-input" name="live_feedback_mode" id="valFeedbackMode">
                                    <option value="coaching" {{ $setupDefaults['live_feedback_mode'] === 'coaching' ? 'selected' : '' }}>Coaching On</option>
                                    <option value="real_interview" {{ $setupDefaults['live_feedback_mode'] === 'real_interview' ? 'selected' : '' }}>Real Interview Mode</option>
                                </select>
                            </div>
                        </div>

                        <div class="assistance-context-panel">
                            <input type="hidden" name="company_persona" id="valPersona" value="{{ $setupDefaults['company_persona'] }}" class="setup-input">
                            <span class="assistance-context-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                            <div>
                                <strong class="assistance-context-title">Philippines hiring context</strong>
                                <div class="desc-text">The interviewer stays within local Philippine workplace expectations, including HR screening, communication clarity, professionalism, and role fit.</div>
                            </div>
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
                            <input type="radio" name="response_mode" value="text" class="setup-input" {{ $setupDefaults['response_mode'] === 'text' ? 'checked' : '' }}>
                            <span>
                                <span class="response-mode-title">Text Mode</span>
                                <span class="response-mode-desc">Type your answers manually</span>
                            </span>
                        </label>
                        <label class="response-mode-card">
                            <input type="radio" name="response_mode" value="voice" class="setup-input" {{ $setupDefaults['response_mode'] === 'voice' ? 'checked' : '' }}>
                            <span>
                                <span class="response-mode-title">Voice Mode</span>
                                <span class="response-mode-desc">Speak through your microphone</span>
                            </span>
                        </label>
                        <label class="response-mode-card">
                            <input type="radio" name="response_mode" value="hybrid" class="setup-input" {{ $setupDefaults['response_mode'] === 'hybrid' ? 'checked' : '' }}>
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
                    <div class="setup-panel" id="panel-summary" style="background:linear-gradient(145deg, rgba(59,130,246,0.08) 0%, rgba(59,130,246,0.02) 100%); border:1px solid rgba(59,130,246,0.25); box-shadow: 0 15px 35px rgba(59,130,246,0.1), inset 0 1px 1px rgba(255, 255, 255, 0.1); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);">
                        <h5 style="font-weight:800;margin-bottom:24px;color:var(--pur);text-align:center;letter-spacing:0.5px;"><i class="fa-solid fa-clipboard-list me-2"></i> Interview Summary</h5>
                        
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-globe"></i></span>
                            <span class="summary-label">Scenario:</span>
                            <span class="summary-val" id="sumScenario">{{ $selectedScenario['context_label'] ?? 'Philippines Job Interview' }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-briefcase"></i></span>
                            <span class="summary-label">Position:</span>
                            <span class="summary-val" id="sumPosition">{{ filled($targetPositionDefault) ? $targetPositionDefault : 'Not Specified' }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-signal"></i></span>
                            <span class="summary-label">Difficulty:</span>
                            <span class="summary-val" id="sumDifficulty">Medium</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-circle-question"></i></span>
                            <span class="summary-label">Questions:</span>
                            <span class="summary-val" id="sumQuestions">10</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-microphone"></i></span>
                            <span class="summary-label">Response Mode:</span>
                            <span class="summary-val" id="sumResponse">Voice</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-shield-alt"></i></span>
                            <span class="summary-label">Strictness:</span>
                            <span class="summary-val" id="sumStrictness">Neutral HR Interviewer</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-message"></i></span>
                            <span class="summary-label">Live Feedback:</span>
                            <span class="summary-val" id="sumFeedbackMode">Coaching On</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-building"></i></span>
                            <span class="summary-label">Hiring Context:</span>
                            <span class="summary-val" id="sumPersona">Philippines hiring context</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
                            <span class="summary-label">Est. Duration:</span>
                            <span class="summary-val text-success" id="sumDuration">15 Minutes</span>
                        </div>
                        
                        <div class="setup-start-action" style="margin-top:30px;">
                            <button type="submit" id="btn-start-interview" class="btn w-100 py-3 btn-shine">
                                Start Philippine Interview <i class="fa-solid fa-play ms-2"></i>
                            </button>
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
        <h4>Philippines Interview Ready</h4>
        <p>Please wait while we begin or resume your customized interview session.</p>
    </div>

    <div class="modal fade setup-alert-modal" id="targetPositionAlertModal" tabindex="-1" aria-labelledby="targetPositionAlertTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="targetPositionAlertTitle">Target position required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
        'valInterviewFormat',
        'valAssistance',
        'valStrictness',
        'valFeedbackMode',
    ];
    const setupPanelRequiredFields = {
        'panel-basic': ['valScenario', 'valPosition'],
        'panel-structure': ['valNumQuestions', 'valTimeLimit', 'valInterviewFormat'],
        'panel-content': ['valAssistance', 'valStrictness', 'valFeedbackMode'],
    };
    const defaultCompanyPersona = @json($companyPersonaDefault ?: 'Philippines hiring context');
    let setupValidationVisible = false;
    const setupFieldErrorIds = {
        valPosition: 'targetPositionError',
    };
    let targetPositionAlertVisible = false;

    function ensureCompanyPersonaFallback() {
        const personaInput = document.getElementById('valPersona');
        if (personaInput && !String(personaInput.value || '').trim()) {
            personaInput.value = defaultCompanyPersona;
        }
        return personaInput?.value || defaultCompanyPersona;
    }

    function setSetupFieldInvalid(field, invalid) {
        if (!field) return;
        field.classList.toggle('setup-field-invalid', invalid);
        field.setAttribute('aria-invalid', invalid ? 'true' : 'false');
    }

    function setSetupFieldError(fieldId, visible) {
        if (fieldId === 'valPosition') {
            visible = false;
        }
        const error = document.getElementById(setupFieldErrorIds[fieldId]);
        if (!error) return;
        error.hidden = !visible;
        error.classList.toggle('setup-inline-error-visible', visible);
    }

    function showTargetPositionAlert() {
        const modalElement = document.getElementById('targetPositionAlertModal');
        if (!modalElement || targetPositionAlertVisible) return;

        if (modalElement.parentElement !== document.body) {
            document.body.appendChild(modalElement);
        }

        targetPositionAlertVisible = true;
        modalElement.addEventListener('hidden.bs.modal', () => {
            targetPositionAlertVisible = false;
            document.getElementById('valPosition')?.focus();
        }, { once: true });

        if (window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return;
        }

        alert('Enter the target position before continuing.');
        targetPositionAlertVisible = false;
    }

    function hasCheckedSetupInput(name) {
        return Boolean(document.querySelector(`input[name="${name}"]:checked`));
    }

    function setQuestionTypeError(visible) {
        const group = document.getElementById('questionTypeGroup');
        const error = document.getElementById('questionTypeError');
        if (group) {
            group.classList.toggle('setup-field-invalid', visible);
            group.setAttribute('aria-invalid', visible ? 'true' : 'false');
        }
        if (error) {
            error.hidden = !visible;
            error.classList.toggle('setup-inline-error-visible', visible);
        }
    }

    function missingSetupItems(panelId = null) {
        const fieldIds = panelId ? (setupPanelRequiredFields[panelId] || []) : setupRequiredFieldIds;
        const missing = fieldIds
            .map(id => ({ type: 'field', id, panelId }))
            .filter(item => {
                const field = document.getElementById(item.id);
                return !field || String(field.value || '').trim().length === 0;
            });

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
        const missingFieldIds = new Set(missing.filter(item => item.type === 'field').map(item => item.id));
        setupRequiredFieldIds.forEach(id => setSetupFieldInvalid(document.getElementById(id), missingFieldIds.has(id)));
        Object.keys(setupFieldErrorIds).forEach(id => setSetupFieldError(id, missingFieldIds.has(id)));
        setQuestionTypeError(missing.some(item => item.name === 'question_types[]'));
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
            if (missing[0]?.type === 'field' && missing[0]?.id === 'valPosition') {
                showTargetPositionAlert();
            } else {
                focusSetupItem(missing[0]);
            }
        }
        return missing.length === 0;
    }

    function validateSetupForm(reveal = false) {
        ensureCompanyPersonaFallback();
        const missing = missingSetupItems();
        if (reveal || setupValidationVisible) {
            setupValidationVisible = true;
            markSetupValidation(missing);
            if (missing[0]?.type === 'field' && missing[0]?.id === 'valPosition') {
                showTargetPositionAlert();
            } else {
                focusSetupItem(missing[0]);
            }
        }
        return missing.length === 0;
    }

    function updateSummary() {
        ensureCompanyPersonaFallback();
        const scenarioSelect = document.getElementById('valScenario');
        if (scenarioSelect) {
            const selectedOption = scenarioSelect.options[scenarioSelect.selectedIndex];
            document.getElementById('sumScenario').innerText = selectedOption?.dataset.contextLabel || selectedOption?.text || 'Philippines Job Interview';
            document.getElementById('valSourcePack').value = selectedOption?.dataset.sourcePackKey || '';
            document.getElementById('valFocus').value = selectedOption?.dataset.focus || 'Philippines Job Interview';
            const sourceSummary = document.getElementById('sourceSummary');
            if (sourceSummary) {
                sourceSummary.innerText = selectedOption?.dataset.sourceSummary || 'Philippines career and education sources';
            }
        }

        const posVal = document.getElementById('valPosition').value;
        document.getElementById('sumPosition').innerText = posVal || 'Not Specified';
        updateStartInterviewState();

        const diff = document.querySelector('input[name="difficulty"]:checked');
        if(diff) document.getElementById('sumDifficulty').innerText = diff.value.charAt(0).toUpperCase() + diff.value.slice(1);

        const numQ = document.getElementById('valNumQuestions').value;
        document.getElementById('sumQuestions').innerText = numQ;

        const resp = document.querySelector('input[name="response_mode"]:checked');
        if(resp) document.getElementById('sumResponse').innerText = resp.value.charAt(0).toUpperCase() + resp.value.slice(1);

        const strictness = document.getElementById('valStrictness');
        if (strictness) {
            document.getElementById('sumStrictness').innerText = strictness.options[strictness.selectedIndex].text;
        }

        const feedbackMode = document.getElementById('valFeedbackMode');
        if (feedbackMode) {
            document.getElementById('sumFeedbackMode').innerText = feedbackMode.options[feedbackMode.selectedIndex].text;
        }

        const personaValue = ensureCompanyPersonaFallback();
        document.getElementById('sumPersona').innerText = personaValue || 'Philippines hiring context';

        const timeLimit = parseInt(document.getElementById('valTimeLimit').value);
        let durationStr = "Self-paced";
        if(timeLimit > 0) {
            durationStr = (numQ * timeLimit) + " Minutes";
        } else {
            durationStr = Math.round(numQ * 1.5) + " Minutes";
        }
        document.getElementById('sumDuration').innerText = durationStr;
        updateStartInterviewState();
    }

    function updateStartInterviewState() {
        const startButton = document.getElementById('btn-start-interview');
        if (!startButton) return;

        ensureCompanyPersonaFallback();

        const hasRequiredFields = setupRequiredFieldIds.every(id => {
            const field = document.getElementById(id);
            return field && String(field.value || '').trim().length > 0;
        });

        const hasDifficulty = hasCheckedSetupInput('difficulty');
        const hasResponseMode = hasCheckedSetupInput('response_mode');
        const hasQuestionType = hasCheckedSetupInput('question_types[]');
        const hasCompleteSetupFields = hasRequiredFields && hasDifficulty && hasResponseMode && hasQuestionType;
        const hasReviewedSetupSteps = getRequiredSetupReviewStepIds().every(stepId => visitedSetupStepIds.has(stepId));
        const canStart = hasCompleteSetupFields && hasReviewedSetupSteps;

        if (setupValidationVisible) {
            markSetupValidation(missingSetupItems());
        }

        startButton.disabled = !canStart;
        startButton.classList.toggle('setup-start-disabled', !canStart);
        startButton.setAttribute('aria-disabled', canStart ? 'false' : 'true');
        startButton.title = canStart
            ? 'Start interview'
            : (hasCompleteSetupFields ? 'Review all setup steps first' : 'Complete all required details first');
    }

    document.querySelectorAll('.setup-input').forEach(el => {
        el.addEventListener('change', updateSummary);
        el.addEventListener('keyup', updateSummary);
    });

    document.querySelectorAll('input[name="question_types[]"]').forEach(el => {
        el.addEventListener('change', updateSummary);
    });

    const setupStepState = {
        index: 0,
        desktopQuery: window.matchMedia('(min-width: 992px)'),
        baseSteps: [
            { id: 'panel-basic', label: 'Details' },
            { id: 'panel-structure', label: 'Structure' },
            { id: 'panel-inclusive', label: 'Access' },
            { id: 'panel-content', label: 'Scenario' },
            { id: 'panel-response', label: 'Response' },
        ],
    };
    const visitedSetupStepIds = new Set();

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
        const mode = setupStepState.desktopQuery.matches ? 'desktop' : 'mobile';
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

        if (!section || !stepper) return;

        renderSetupStepper();
        setupStepState.index = Math.max(0, Math.min(steps.length - 1, nextIndex));
        if (steps[setupStepState.index]?.id) {
            visitedSetupStepIds.add(steps[setupStepState.index].id);
        }

        section.classList.add('setup-step-mode');
        section.classList.toggle('setup-summary-step', !isDesktop && steps[setupStepState.index]?.id === 'panel-summary');
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
                stepButton.setAttribute('aria-current', index === setupStepState.index ? 'step' : 'false');
            }
        });

        if (prevButton) prevButton.disabled = setupStepState.index === 0;
        if (nextButton) {
            const isLast = setupStepState.index === steps.length - 1;
            nextButton.hidden = isLast;
        }

        updateStartInterviewState();

    }

    let setupTutorialRestoreIndex = null;

    function getSetupPanelForElement(element) {
        if (!element || typeof element.closest !== 'function') return null;

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
        showSetupStep(Number.isInteger(setupTutorialRestoreIndex) ? setupTutorialRestoreIndex : setupStepState.index);
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
        updateSummary();
        showSetupStep(0);
    }

    initializeInterviewSetupPage();
    window.addEventListener('load', initializeInterviewSetupPage, { once: true });

    const setupForm = document.getElementById('setupForm');
    const setupTransitionOverlay = document.getElementById('setupTransitionOverlay');
    const startInterviewButton = document.getElementById('btn-start-interview');

    if (setupForm && setupTransitionOverlay) {
        setupForm.addEventListener('submit', function(event) {
            updateStartInterviewState();
            if (!validateSetupForm(true) || startInterviewButton?.disabled) {
                event.preventDefault();
                return;
            }

            setupTransitionOverlay.classList.add('active');
            document.body.classList.add('finish-transition-active');

            if (startInterviewButton) {
                startInterviewButton.disabled = true;
                startInterviewButton.innerHTML = 'Begin / Resume Interview <i class="fa-solid fa-spinner fa-spin ms-2"></i>';
            }
        });

        window.addEventListener('pageshow', function() {
            setupTransitionOverlay.classList.remove('active');
            document.body.classList.remove('finish-transition-active');

            if (startInterviewButton) {
                startInterviewButton.innerHTML = 'Start Philippine Interview <i class="fa-solid fa-play ms-2"></i>';
                updateStartInterviewState();
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
                overflow-x: hidden !important;
            }

            body.interview-setup-page #mob-content {
                box-sizing: border-box !important;
                min-height: 100dvh !important;
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
                height: auto !important;
                max-height: none !important;
                overflow: visible !important;
                overflow-y: visible !important;
                overscroll-behavior: auto !important;
            }

            body.interview-setup-page #sec-interview-setup {
                overflow-x: clip !important;
            }

            body.interview-setup-page #sec-interview-setup .setup-summary-wrap,
            body.interview-setup-page #sec-interview-setup #panel-summary,
            body.interview-setup-page #sec-interview-setup .col-lg-4 > div {
                position: static !important;
                top: auto !important;
            }

            body.interview-setup-page #dashboard .db-nav,
            html body.user-desktop-shell.interview-setup-page:not(.admin-shell) #dashboard .db-nav {
                scrollbar-width: none !important;
                -ms-overflow-style: none !important;
            }

            body.interview-setup-page #dashboard .db-nav::-webkit-scrollbar,
            html body.user-desktop-shell.interview-setup-page:not(.admin-shell) #dashboard .db-nav::-webkit-scrollbar {
                width: 0 !important;
                height: 0 !important;
                display: none !important;
            }
        `;

        let style = document.getElementById('interview-setup-scroll-fix');
        if (!style) {
            style = document.createElement('style');
            style.id = 'interview-setup-scroll-fix';
        }
        if (style.parentNode !== document.head) document.head.appendChild(style);
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
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const setupTourSteps = [
            { element: '#panel-basic', popover: { title: 'Philippines Interview', description: 'Choose an optional local category and enter the target position.', side: 'top', align: 'center' }},
            { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Set difficulty, question count, and optional response timing before you start.', side: 'top', align: 'center' }},
            { element: '#panel-inclusive', popover: { title: 'Inclusive Practice', description: 'Select practice conditions that make the interview setup match your needs.', side: 'top', align: 'center' }},
            { element: '#panel-content', popover: { title: 'Practice Scenario', description: 'Pick the Philippines scenario, assistance level, and question types.', side: 'top', align: 'center' }},
            { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose typed, voice, or hybrid answers depending on how you want to practice.', side: 'top', align: 'center' }},
            { element: '#panel-summary', popover: { title: 'Live Summary', description: 'Confirm your interview setup before generating the practice session.', side: 'top', align: 'center' }},
            { element: '#btn-start-interview', popover: { title: 'Start Interview', description: 'Generate your customized Philippine interview when the setup looks right.', side: 'top', align: 'center' }}
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
