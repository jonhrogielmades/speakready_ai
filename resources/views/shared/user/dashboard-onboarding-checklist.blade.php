@php
    $onboardingState = is_array($onboardingState ?? null) ? $onboardingState : [];
    $onboardingActivated = (bool) ($onboardingState['activated'] ?? false);
    $onboardingRecoverySaved = (bool) ($onboardingState['recovery_code_saved'] ?? false);
    $onboardingPersonalized = (bool) ($onboardingState['profile_personalized'] ?? false);
    $onboardingPersonalizationSkipped = (bool) ($onboardingState['personalization_skipped'] ?? false);
    $onboardingProfileHandled = $onboardingPersonalized || $onboardingPersonalizationSkipped;
    $onboardingSetupComplete = (bool) ($onboardingState['setup_tools_completed'] ?? false);
    $onboardingTourComplete = (bool) ($onboardingState['dashboard_tour_completed'] ?? false);
    $onboardingFirstInterviewComplete = (bool) ($onboardingState['first_interview_completed'] ?? false);
    $onboardingCompletedCount = collect([
        $onboardingRecoverySaved,
        $onboardingProfileHandled,
        $onboardingSetupComplete,
        $onboardingTourComplete,
        $onboardingFirstInterviewComplete,
    ])->filter()->count();
    $onboardingPercent = (int) round(($onboardingCompletedCount / 5) * 100);
    $onboardingUser = Auth::user();
    $onboardingPositions = collect(config('speakready_scope.job_positions', []))
        ->flatten()
        ->map(fn ($position) => trim((string) $position))
        ->filter()
        ->unique(fn (string $position) => strtolower($position))
        ->values();
    $onboardingLanguages = $supportedLanguages ?? \App\Models\Setting::supportedLanguages();
    $selectedPosition = old('target_position', $onboardingState['target_position'] ?? $onboardingUser?->target_position);
    $selectedLanguage = old('preferred_language', $onboardingState['preferred_language'] ?? $onboardingUser?->preferred_language ?? 'en');
    $selectedExperience = old('experience_level', $onboardingState['experience_level'] ?? '');
    $selectedGoal = old('interview_goal', $onboardingState['interview_goal'] ?? '');
    $selectedDate = old('interview_date', $onboardingState['interview_date'] ?? '');
@endphp

@once
@push('styles')
<style>
    .sr-onboarding-card{margin:0 0 18px;padding:18px;border:1px solid color-mix(in srgb,var(--pur,#6366f1) 30%,var(--bd,#e2e8f0));border-radius:18px;background:linear-gradient(135deg,color-mix(in srgb,var(--pur,#6366f1) 9%,var(--sf,#fff)),var(--sf,#fff));box-shadow:0 16px 34px rgba(15,23,42,.08)}
    .sr-onboarding-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:14px}
    .sr-onboarding-kicker{display:inline-flex;align-items:center;gap:7px;margin:0 0 5px;color:var(--pur,#6366f1);font-size:.72rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase}
    .sr-onboarding-title{margin:0;color:var(--tx,#0f172a);font-size:1.05rem;font-weight:900}
    .sr-onboarding-copy{margin:5px 0 0;color:var(--tx2,#475569);font-size:.82rem;line-height:1.5}
    .sr-onboarding-progress{min-width:112px;text-align:right}
    .sr-onboarding-progress strong{display:block;color:var(--tx,#0f172a);font-size:.88rem}
    .sr-onboarding-progress-track{height:7px;margin-top:7px;border-radius:99px;background:color-mix(in srgb,var(--tx3,#94a3b8) 20%,transparent);overflow:hidden}
    .sr-onboarding-progress-track span{display:block;width:var(--onboarding-progress);height:100%;border-radius:inherit;background:linear-gradient(90deg,#6366f1,#22c55e)}
    .sr-onboarding-list{display:grid;gap:9px}
    .sr-onboarding-step{display:grid;grid-template-columns:34px minmax(0,1fr) auto;align-items:center;gap:11px;padding:11px;border:1px solid var(--bd,rgba(148,163,184,.25));border-radius:13px;background:color-mix(in srgb,var(--sf,#fff) 90%,transparent)}
    .sr-onboarding-step.is-done{border-color:rgba(34,197,94,.3);background:rgba(34,197,94,.06)}
    .sr-onboarding-step-icon{display:inline-flex;width:34px;height:34px;align-items:center;justify-content:center;border-radius:11px;background:rgba(99,102,241,.12);color:#6366f1}
    .sr-onboarding-step.is-done .sr-onboarding-step-icon{background:rgba(34,197,94,.14);color:#16a34a}
    .sr-onboarding-step-copy strong{display:block;color:var(--tx,#0f172a);font-size:.84rem}
    .sr-onboarding-step-copy small{display:block;margin-top:2px;color:var(--tx2,#64748b);font-size:.73rem;line-height:1.4}
    .sr-onboarding-action{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:34px;padding:7px 10px;border:1px solid rgba(99,102,241,.28);border-radius:9px;background:rgba(99,102,241,.09);color:var(--pur,#4f46e5);font-size:.73rem;font-weight:850;text-decoration:none;white-space:nowrap}
    .sr-onboarding-action:hover{background:rgba(99,102,241,.16);color:var(--pur,#4338ca)}
    .sr-onboarding-status{font-size:.72rem;font-weight:850;color:#16a34a}
    .sr-onboarding-personalization{margin:10px 0 2px;padding:13px;border:1px solid var(--bd,rgba(148,163,184,.3));border-radius:13px;background:var(--sf,#fff)}
    .sr-onboarding-personalization summary{cursor:pointer;color:var(--tx,#0f172a);font-size:.82rem;font-weight:850}
    .sr-onboarding-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:12px}
    .sr-onboarding-field-wide{grid-column:1/-1}
    .sr-onboarding-field label{display:block;margin-bottom:5px;color:var(--tx2,#475569);font-size:.72rem;font-weight:800}
    .sr-onboarding-field :where(input,select){width:100%;min-height:40px;padding:8px 10px;border:1px solid var(--bd,#cbd5e1);border-radius:10px;background:var(--bg2,var(--sf,#fff));color:var(--tx,#0f172a);font-size:.8rem}
    .sr-onboarding-form-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:11px}
    .sr-onboarding-activation{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;color:var(--tx2,#475569);font-size:.72rem}
    .sr-onboarding-activation span{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:99px;background:rgba(15,23,42,.05)}
    .sr-onboarding-activation .is-done{color:#15803d;background:rgba(34,197,94,.1)}
    @media(max-width:575.98px){.sr-onboarding-card{padding:14px;margin-bottom:14px}.sr-onboarding-head{display:block}.sr-onboarding-progress{margin-top:10px;text-align:left}.sr-onboarding-step{grid-template-columns:32px minmax(0,1fr)}.sr-onboarding-step>.sr-onboarding-action,.sr-onboarding-step>.sr-onboarding-status{grid-column:2;justify-self:start}.sr-onboarding-form-grid{grid-template-columns:1fr}.sr-onboarding-field-wide{grid-column:auto}.sr-onboarding-form-actions{justify-content:stretch}.sr-onboarding-form-actions .sr-onboarding-action{flex:1}}
</style>
@endpush
@endonce

@unless($onboardingActivated)
<section class="sr-onboarding-card" id="dashboardOnboardingChecklist" aria-labelledby="dashboardOnboardingTitle" data-sr-onboarding-checklist data-sr-onboarding-completed-count="{{ $onboardingCompletedCount }}">
    <div class="sr-onboarding-head">
        <div>
            <p class="sr-onboarding-kicker"><i class="fa-solid fa-route" aria-hidden="true"></i> First steps</p>
            <h2 class="sr-onboarding-title" id="dashboardOnboardingTitle">Get interview-ready</h2>
            <p class="sr-onboarding-copy">Finish the essentials at your pace. Your progress follows your account on every device.</p>
        </div>
        <div class="sr-onboarding-progress" data-sr-onboarding-progress aria-label="{{ $onboardingCompletedCount }} of 5 onboarding steps complete">
            <strong data-sr-onboarding-progress-label>{{ $onboardingCompletedCount }} of 5 complete</strong>
            <div class="sr-onboarding-progress-track" aria-hidden="true"><span data-sr-onboarding-progress-bar style="--onboarding-progress:{{ $onboardingPercent }}%"></span></div>
        </div>
    </div>

    <div class="sr-onboarding-list">
        <div class="sr-onboarding-step {{ $onboardingRecoverySaved ? 'is-done' : '' }}">
            <span class="sr-onboarding-step-icon"><i class="fa-solid {{ $onboardingRecoverySaved ? 'fa-check' : 'fa-key' }}" aria-hidden="true"></i></span>
            <span class="sr-onboarding-step-copy"><strong>Save your recovery code</strong><small>{{ $onboardingRecoverySaved ? 'Your acknowledgement is saved to this account.' : 'Generate or save a backup code from Account settings.' }}</small></span>
            @if($onboardingRecoverySaved)<span class="sr-onboarding-status">Complete</span>@else<a class="sr-onboarding-action" href="{{ route('user.account') }}"><i class="fa-solid fa-arrow-up-right-from-square"></i> Account</a>@endif
        </div>

        <div class="sr-onboarding-step {{ $onboardingProfileHandled ? 'is-done' : '' }}">
            <span class="sr-onboarding-step-icon"><i class="fa-solid {{ $onboardingProfileHandled ? 'fa-check' : 'fa-bullseye' }}" aria-hidden="true"></i></span>
            <span class="sr-onboarding-step-copy"><strong>Personalize your practice</strong><small>{{ $onboardingPersonalized ? 'Your target role and practice goal are ready.' : ($onboardingPersonalizationSkipped ? 'Skipped for now. You can add this anytime.' : 'Set your role, goal, experience, and language.') }}</small></span>
            @if($onboardingProfileHandled)<span class="sr-onboarding-status">{{ $onboardingPersonalized ? 'Complete' : 'Skipped' }}</span>@endif
        </div>

        <details class="sr-onboarding-personalization" {{ $onboardingProfileHandled ? '' : 'open' }}>
            <summary>{{ $onboardingPersonalized ? 'Edit personalization' : 'Personalize now' }}</summary>
            <form action="{{ route('user.onboarding.personalization') }}" method="POST">
                @csrf
                <div class="sr-onboarding-form-grid">
                    <div class="sr-onboarding-field">
                        <label for="onboardingTargetPosition">Target position</label>
                        <select id="onboardingTargetPosition" name="target_position" required>
                            <option value="">Choose a position</option>
                            @foreach($onboardingPositions as $position)
                                <option value="{{ $position }}" @selected((string) $selectedPosition === (string) $position)>{{ $position }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-onboarding-field">
                        <label for="onboardingExperienceLevel">Experience level</label>
                        <select id="onboardingExperienceLevel" name="experience_level" required>
                            @foreach(['student' => 'Student / New graduate', 'entry' => 'Entry level', 'mid' => 'Mid level', 'senior' => 'Senior level', 'career_change' => 'Career changer'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) $selectedExperience === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-onboarding-field sr-onboarding-field-wide">
                        <label for="onboardingInterviewGoal">Main interview goal</label>
                        <input id="onboardingInterviewGoal" name="interview_goal" type="text" maxlength="255" required value="{{ $selectedGoal }}" placeholder="Example: Give clearer STAR answers">
                    </div>
                    <div class="sr-onboarding-field">
                        <label for="onboardingPreferredLanguage">Preferred language</label>
                        <select id="onboardingPreferredLanguage" name="preferred_language" required>
                            @foreach($onboardingLanguages as $code => $language)
                                <option value="{{ $code }}" @selected((string) $selectedLanguage === (string) $code)>{{ $language['native_label'] ?? $language['label'] ?? strtoupper($code) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-onboarding-field">
                        <label for="onboardingInterviewDate">Interview date (optional)</label>
                        <input id="onboardingInterviewDate" name="interview_date" type="date" value="{{ $selectedDate }}" min="{{ now()->toDateString() }}">
                    </div>
                </div>
                <div class="sr-onboarding-form-actions">
                    <button class="sr-onboarding-action" type="submit" name="action" value="skip" formnovalidate>Skip for now</button>
                    <button class="sr-onboarding-action" type="submit" name="action" value="save"><i class="fa-solid fa-check"></i> Save profile</button>
                </div>
            </form>
        </details>

        <div class="sr-onboarding-step {{ $onboardingSetupComplete ? 'is-done' : '' }}" data-sr-onboarding-step="setup_tools_completed">
            <span class="sr-onboarding-step-icon"><i class="fa-solid {{ $onboardingSetupComplete ? 'fa-check' : 'fa-microphone-lines' }}" aria-hidden="true"></i></span>
            <span class="sr-onboarding-step-copy"><strong>Check your practice tools</strong><small>Confirm the microphone, camera, and notification choices for this account.</small></span>
            <button class="sr-onboarding-action" type="button" data-sr-open-setup-tools>{{ $onboardingSetupComplete ? 'Review' : 'Set up' }}</button>
        </div>

        <div class="sr-onboarding-step {{ $onboardingTourComplete ? 'is-done' : '' }}" data-sr-onboarding-step="dashboard_tour_completed">
            <span class="sr-onboarding-step-icon"><i class="fa-solid {{ $onboardingTourComplete ? 'fa-check' : 'fa-compass' }}" aria-hidden="true"></i></span>
            <span class="sr-onboarding-step-copy"><strong>Learn your dashboard</strong><small>Take the short guided tour. Leaving early keeps this step open.</small></span>
            <button class="sr-onboarding-action" type="button" data-sr-start-dashboard-tour>{{ $onboardingTourComplete ? 'Replay' : 'Start tour' }}</button>
        </div>

        <div class="sr-onboarding-step {{ $onboardingFirstInterviewComplete ? 'is-done' : '' }}">
            <span class="sr-onboarding-step-icon"><i class="fa-solid {{ $onboardingFirstInterviewComplete ? 'fa-check' : 'fa-play' }}" aria-hidden="true"></i></span>
            <span class="sr-onboarding-step-copy"><strong>Complete your first scored interview</strong><small>This is the activation milestone that unlocks your real readiness baseline.</small></span>
            @if($onboardingFirstInterviewComplete)<span class="sr-onboarding-status">Complete</span>@else<a class="sr-onboarding-action" href="{{ route('interview.setup') }}">Start</a>@endif
        </div>
    </div>

    <div class="sr-onboarding-activation" aria-label="Account activation requirements">
        <span class="is-done"><i class="fa-solid fa-check"></i> Terms accepted</span>
        <span class="{{ $onboardingFirstInterviewComplete ? 'is-done' : '' }}"><i class="fa-solid {{ $onboardingFirstInterviewComplete ? 'fa-check' : 'fa-circle' }}"></i> First scored interview</span>
    </div>
</section>
@endunless

@once
@push('scripts')
<script>
    (function () {
        const checklist = document.querySelector('[data-sr-onboarding-checklist]');

        function markChecklistStepComplete(stepName, actionLabel) {
            const step = checklist?.querySelector(`[data-sr-onboarding-step="${stepName}"]`);
            if (!step || step.classList.contains('is-done')) return;

            step.classList.add('is-done');
            const icon = step.querySelector('.sr-onboarding-step-icon i');
            if (icon) icon.className = 'fa-solid fa-check';

            const action = step.querySelector('.sr-onboarding-action');
            if (action && actionLabel) action.textContent = actionLabel;

            const nextCount = Math.min(5, Number(checklist.dataset.srOnboardingCompletedCount || 0) + 1);
            checklist.dataset.srOnboardingCompletedCount = String(nextCount);

            const label = checklist.querySelector('[data-sr-onboarding-progress-label]');
            if (label) label.textContent = `${nextCount} of 5 complete`;

            const progress = checklist.querySelector('[data-sr-onboarding-progress]');
            if (progress) progress.setAttribute('aria-label', `${nextCount} of 5 onboarding steps complete`);

            const progressBar = checklist.querySelector('[data-sr-onboarding-progress-bar]');
            if (progressBar) progressBar.style.setProperty('--onboarding-progress', `${Math.round((nextCount / 5) * 100)}%`);
        }

        window.addEventListener('speakready:onboarding-updated', function (event) {
            const state = event.detail || {};
            if (state.setup_tools_completed) markChecklistStepComplete('setup_tools_completed', 'Review');
            if (state.dashboard_tour_completed) markChecklistStepComplete('dashboard_tour_completed', 'Replay');
        });

        document.addEventListener('click', function (event) {
            const setupButton = event.target.closest('[data-sr-open-setup-tools]');
            if (setupButton) {
                const modal = document.getElementById('dashboardSetupToolsModal');
                if (modal && window.bootstrap?.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modal).show();
                }
                return;
            }

            const tourButton = event.target.closest('[data-sr-start-dashboard-tour]');
            if (tourButton && typeof window.startOnboardingTour === 'function') {
                window.startOnboardingTour();
            }
        });
    })();
</script>
@endpush
@endonce
