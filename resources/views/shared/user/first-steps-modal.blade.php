@php
    $firstStepsState = is_array($onboardingState ?? null) ? $onboardingState : [];
    $firstStepsRecoverySaved = (bool) ($firstStepsState['recovery_code_saved'] ?? false);
    $firstStepsPersonalized = (bool) ($firstStepsState['profile_personalized'] ?? false);
    $firstStepsPersonalizationSkipped = (bool) ($firstStepsState['personalization_skipped'] ?? false);
    $firstStepsProfileHandled = $firstStepsPersonalized || $firstStepsPersonalizationSkipped;
    $firstStepsSetupComplete = (bool) ($firstStepsState['setup_tools_completed'] ?? false);
    $firstStepsTourComplete = (bool) ($firstStepsState['dashboard_tour_completed'] ?? false);
    $firstStepsCompletedCount = collect([
        $firstStepsRecoverySaved,
        $firstStepsProfileHandled,
        $firstStepsSetupComplete,
        $firstStepsTourComplete,
    ])->filter()->count();
    $firstStepsComplete = $firstStepsCompletedCount === 4;
    $firstStepsPercent = (int) round(($firstStepsCompletedCount / 4) * 100);
    $firstStepsUser = Auth::user();
    $firstStepsPositions = collect(\App\Services\QuestionDatasetProvider::targetPositionOptionGroups())
        ->flatten()
        ->map(fn ($position) => trim((string) $position))
        ->filter()
        ->unique(fn (string $position) => strtolower($position))
        ->values();
    $firstStepsLanguages = $supportedLanguages ?? \App\Models\Setting::supportedLanguages();
    $firstStepsSelectedPosition = old('target_position', $firstStepsUser?->target_position);
    $firstStepsSelectedLanguage = old('preferred_language', $firstStepsUser?->preferred_language ?? 'en');
    $firstStepsSelectedExperience = old('experience_level', $firstStepsState['experience_level'] ?? '');
    $firstStepsSelectedGoal = old('interview_goal', $firstStepsState['interview_goal'] ?? '');
    $firstStepsSelectedDate = old('interview_date', $firstStepsState['interview_date'] ?? '');
    $firstStepsLayoutQuery = sr_layout_query(request());
    $firstStepsAccountUrl = route('user.account', $firstStepsLayoutQuery).'#password-recovery-code';
    $firstStepsToolsUrl = route('dashboard', array_merge($firstStepsLayoutQuery, ['onboarding' => 'tools']));
    $firstStepsTourUrl = route('dashboard', array_merge($firstStepsLayoutQuery, ['onboarding' => 'tour']));
    $firstStepsAutoShow = request()->routeIs('dashboard') && ! $firstStepsComplete && ! in_array(request()->query('onboarding'), ['tools', 'tour'], true);
@endphp

<div class="modal fade sr-first-steps-modal" id="firstStepsOnboardingModal" tabindex="-1" aria-labelledby="firstStepsOnboardingTitle" aria-hidden="true"
     data-sr-onboarding-modal
     data-sr-onboarding-user="{{ (int) Auth::id() }}"
     data-sr-first-steps-auto-show="{{ $firstStepsAutoShow ? 'true' : 'false' }}"
     data-sr-first-steps-complete="{{ $firstStepsComplete ? 'true' : 'false' }}"
     data-sr-dashboard-tools-url="{{ $firstStepsToolsUrl }}"
     data-sr-dashboard-tour-url="{{ $firstStepsTourUrl }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="sr-first-steps-heading">
                    <span class="sr-first-steps-heading-icon" aria-hidden="true"><i class="fa-solid fa-route"></i></span>
                    <div>
                        <p class="sr-first-steps-kicker">First steps</p>
                        <h2 class="modal-title" id="firstStepsOnboardingTitle">Get interview-ready</h2>
                        <p class="sr-first-steps-subtitle">Complete these four setup steps at your pace. Progress follows your account on every device.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-sr-first-steps-later aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="sr-first-steps-progress" data-sr-onboarding-progress role="progressbar" aria-label="{{ $firstStepsCompletedCount }} of 4 onboarding steps complete" aria-valuemin="0" aria-valuemax="4" aria-valuenow="{{ $firstStepsCompletedCount }}">
                    <div class="sr-first-steps-progress-track" aria-hidden="true">
                        <span data-sr-onboarding-progress-bar style="--sr-first-progress:{{ $firstStepsPercent }}%"></span>
                    </div>
                    <strong data-sr-onboarding-progress-label aria-live="polite" aria-atomic="true">{{ $firstStepsCompletedCount }} of 4 complete</strong>
                </div>

                <div class="sr-first-steps-list">
                    <section class="sr-first-steps-step {{ $firstStepsRecoverySaved ? 'is-done' : '' }}" data-sr-onboarding-step="recovery_code_saved">
                        <span class="sr-first-steps-step-icon"><i class="fa-solid {{ $firstStepsRecoverySaved ? 'fa-check' : 'fa-key' }}" aria-hidden="true"></i></span>
                        <span class="sr-first-steps-step-copy">
                            <strong>1. Save your recovery code</strong>
                            <small>{{ $firstStepsRecoverySaved ? 'Your saved-code acknowledgement is attached to this account.' : 'Keep a backup code somewhere private and secure.' }}</small>
                        </span>
                        <span class="sr-first-steps-step-actions">
                            @if($firstStepsRecoverySaved)
                                <span class="sr-first-steps-status">Complete</span>
                            @else
                                <a class="sr-first-steps-action" href="{{ $firstStepsAccountUrl }}" data-full-reload="true"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open Account</a>
                            @endif
                        </span>
                    </section>

                    <section class="sr-first-steps-step sr-first-steps-step--personalization {{ $firstStepsProfileHandled ? 'is-done' : '' }}" data-sr-onboarding-step="profile_personalized">
                        <span class="sr-first-steps-step-icon"><i class="fa-solid {{ $firstStepsProfileHandled ? 'fa-check' : 'fa-bullseye' }}" aria-hidden="true"></i></span>
                        <span class="sr-first-steps-step-copy">
                            <strong>2. Personalize your practice</strong>
                            <small data-sr-personalization-summary>{{ $firstStepsPersonalized ? 'Your role and practice goal are ready.' : ($firstStepsPersonalizationSkipped ? 'Skipped for now; you can update it anytime.' : 'Choose your role, goal, experience, and language.') }}</small>
                        </span>
                        <span class="sr-first-steps-step-actions" data-sr-step-status-actions @if(! $firstStepsProfileHandled) hidden @endif>
                            @if($firstStepsProfileHandled)<span class="sr-first-steps-status" data-sr-personalization-status>{{ $firstStepsPersonalized ? 'Complete' : 'Skipped' }}</span>@endif
                        </span>
                        <details class="sr-first-steps-personalization" data-sr-personalization-details {{ $firstStepsProfileHandled ? '' : 'open' }}>
                            <summary data-sr-personalization-toggle>{{ $firstStepsPersonalized ? 'Edit personalization' : 'Personalize now' }}</summary>
                            <form action="{{ route('user.onboarding.personalization') }}" method="POST" data-sr-first-steps-personalization-form data-sr-personalization-endpoint="{{ route('user.onboarding.personalization') }}">
                                @csrf
                                <div class="sr-first-steps-form-grid">
                                    <div class="sr-first-steps-field">
                                        <label for="firstStepsTargetPosition">Target position</label>
                                        <select id="firstStepsTargetPosition" name="target_position" required>
                                            <option value="">Choose a position</option>
                                            @foreach($firstStepsPositions as $position)
                                                <option value="{{ $position }}" @selected((string) $firstStepsSelectedPosition === (string) $position)>{{ $position }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sr-first-steps-field">
                                        <label for="firstStepsExperienceLevel">Experience level</label>
                                        <select id="firstStepsExperienceLevel" name="experience_level" required>
                                            @foreach(['student' => 'Student / New graduate', 'entry' => 'Entry level', 'mid' => 'Mid level', 'senior' => 'Senior level', 'career_change' => 'Career changer'] as $value => $label)
                                                <option value="{{ $value }}" @selected((string) $firstStepsSelectedExperience === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sr-first-steps-field sr-first-steps-field-wide">
                                        <label for="firstStepsInterviewGoal">Main interview goal</label>
                                        <input id="firstStepsInterviewGoal" name="interview_goal" type="text" maxlength="255" required value="{{ $firstStepsSelectedGoal }}" placeholder="Example: Give clearer STAR answers">
                                    </div>
                                    <div class="sr-first-steps-field">
                                        <label for="firstStepsPreferredLanguage">Preferred language</label>
                                        <select id="firstStepsPreferredLanguage" name="preferred_language" required>
                                            @foreach($firstStepsLanguages as $code => $language)
                                                <option value="{{ $code }}" @selected((string) $firstStepsSelectedLanguage === (string) $code)>{{ $language['native_label'] ?? $language['label'] ?? strtoupper($code) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sr-first-steps-field">
                                        <label for="firstStepsInterviewDate">Interview date (optional)</label>
                                        <input id="firstStepsInterviewDate" name="interview_date" type="date" value="{{ $firstStepsSelectedDate }}" min="{{ now()->toDateString() }}">
                                    </div>
                                </div>
                                <p class="sr-first-steps-form-status" data-sr-first-steps-form-status role="status" aria-live="polite"></p>
                                <div class="sr-first-steps-step-actions" data-sr-personalization-actions>
                                    <button class="sr-first-steps-action" type="submit" name="action" value="skip" formnovalidate>Skip for now</button>
                                    <button class="sr-first-steps-action" type="submit" name="action" value="save"><i class="fa-solid fa-check"></i> Save profile</button>
                                </div>
                            </form>
                        </details>
                    </section>

                    <section class="sr-first-steps-step {{ $firstStepsSetupComplete ? 'is-done' : '' }}" data-sr-onboarding-step="setup_tools_completed">
                        <span class="sr-first-steps-step-icon"><i class="fa-solid {{ $firstStepsSetupComplete ? 'fa-check' : 'fa-microphone-lines' }}" aria-hidden="true"></i></span>
                        <span class="sr-first-steps-step-copy">
                            <strong>3. Check your practice tools</strong>
                            <small>Confirm camera, microphone, and notification access for this account.</small>
                        </span>
                        <span class="sr-first-steps-step-actions">
                            @if($firstStepsSetupComplete)<span class="sr-first-steps-status">Complete</span>@endif
                            <button class="sr-first-steps-action" type="button" data-sr-open-setup-tools>{{ $firstStepsSetupComplete ? 'Review' : 'Set up' }}</button>
                        </span>
                    </section>

                    <section class="sr-first-steps-step {{ $firstStepsTourComplete ? 'is-done' : '' }}" data-sr-onboarding-step="dashboard_tour_completed">
                        <span class="sr-first-steps-step-icon"><i class="fa-solid {{ $firstStepsTourComplete ? 'fa-check' : 'fa-compass' }}" aria-hidden="true"></i></span>
                        <span class="sr-first-steps-step-copy">
                            <strong>4. Learn your dashboard</strong>
                            <small>Take the guided dashboard tour. Leaving early keeps this step open.</small>
                        </span>
                        <span class="sr-first-steps-step-actions">
                            @if($firstStepsTourComplete)<span class="sr-first-steps-status">Complete</span>@endif
                            <button class="sr-first-steps-action" type="button" data-sr-start-dashboard-tour>{{ $firstStepsTourComplete ? 'Replay' : 'Start tour' }}</button>
                        </span>
                    </section>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="sr-first-steps-later" data-bs-dismiss="modal" data-sr-first-steps-later>Continue later</button>
                <button type="button" class="sr-first-steps-close" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
