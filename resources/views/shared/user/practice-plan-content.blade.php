<div class="db-section active animate-fade-up" id="practice-plan-page">
    @include('shared.partials.practice-page-hero-styles')

    <div class="setup-hero practice-page-hero" id="practicePlanHero" aria-labelledby="practice-plan-hero-title">
        <div class="setup-hero-inner">
            <span class="setup-hero-icon practice-hero-icon" aria-hidden="true">
                <i class="fa-solid fa-route"></i>
            </span>
            <div class="setup-hero-copy">
                <h4 id="practice-plan-hero-title" class="setup-hero-title text-gradient-primary">Personalized Practice Plan</h4>
                <p class="setup-hero-subtitle">Follow focused next steps from your latest interview activity.</p>
            </div>
        </div>
        <svg class="setup-hero-art practice-hero-art practice-plan-art" viewBox="0 0 220 150" aria-hidden="true" role="img">
            <defs>
                <linearGradient id="practicePlanPanel" x1="34" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE"/>
                    <stop offset="1" stop-color="#ECFEFF"/>
                </linearGradient>
                <linearGradient id="practicePlanRoute" x1="58" y1="104" x2="160" y2="46" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#22C55E"/>
                    <stop offset=".56" stop-color="#06B6D4"/>
                    <stop offset="1" stop-color="#3B82F6"/>
                </linearGradient>
            </defs>
            <rect class="practice-art-panel" x="32" y="20" width="156" height="108" rx="18" fill="url(#practicePlanPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <path class="practice-art-line" d="M58 104c18-31 44-49 78-54" fill="none" stroke="url(#practicePlanRoute)" stroke-width="9" stroke-linecap="round"/>
            <circle class="practice-art-dot" cx="58" cy="104" r="12" fill="#22C55E" stroke="#F0FDF4" stroke-width="5"/>
            <circle class="practice-art-dot" cx="100" cy="72" r="12" fill="#0EA5E9" stroke="#F0F9FF" stroke-width="5"/>
            <circle class="practice-art-dot" cx="142" cy="50" r="13" fill="#3B82F6" stroke="#EFF6FF" stroke-width="5"/>
            <path class="practice-art-line" d="M68 42h60M70 122h88" stroke="#93C5FD" stroke-width="7" stroke-linecap="round" opacity=".68"/>
            <path class="practice-art-check" d="m151 47 9 9 18-24" fill="none" stroke="#22C55E" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
            <circle class="practice-art-spark" cx="182" cy="84" r="5" fill="#BAE6FD"/>
            <circle class="practice-art-spark" cx="42" cy="54" r="4" fill="#C4B5FD"/>
        </svg>
    </div>

    <div class="sr-page-actions" id="practicePlanActions">
        <a href="{{ route('user.progress') }}" class="btn btn-outline-primary history-feedback-btn"><i class="fa-solid fa-chart-line"></i> Interview Progress</a>
        <a href="{{ route('interview.setup') }}" class="btn btn-outline-primary history-feedback-btn"><i class="fa-solid fa-play"></i> Start Practice</a>
    </div>

    @include('shared.partials.ai-recommendations')
    @include('shared.partials.personalized-practice-plan')
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#practicePlanHero', popover: { title: 'Practice Plan', description: 'Use this page to turn recent interview results and module activity into focused next steps.', side: 'bottom', align: 'start' }},
            { element: '#practicePlanActions', popover: { title: 'Quick Actions', description: 'Jump back to Interview Progress or start another practice session when you are ready to apply the plan.', side: 'bottom', align: 'start' }},
            { element: '#practice-ai-recommendations', popover: { title: 'AI Recommendations', description: 'Review the highest-priority actions the system recommends from your current performance patterns.', side: 'top', align: 'start' }},
            { element: '.practice-ai-list', popover: { title: 'Recommended Actions', description: 'Open a recommended lesson, module, or activity to work on the area with the clearest payoff.', side: 'top', align: 'start' }},
            { element: '.practice-ai-empty', popover: { title: 'Unlock Recommendations', description: 'Complete a scored interview to generate recommendations matched to your latest performance.', side: 'top', align: 'start' }},
            { element: '#personalized-practice-plan', popover: { title: 'Personalized Plan', description: 'Follow this short plan to move from insight to practice without deciding from scratch each time.', side: 'top', align: 'start' }},
            { element: '.practice-plan-list', popover: { title: 'Practice Steps', description: 'Each row gives a focused task, estimated time, and shortcut to the right practice area.', side: 'top', align: 'start' }},
            { element: '.practice-plan-tasks', popover: { title: 'Task Checklist', description: 'Use these smaller tasks to keep the practice step concrete and easy to finish.', side: 'top', align: 'start' }},
            { element: '#personalized-practice-plan .skill-empty-state', popover: { title: 'Generate A Plan', description: 'After a scored interview, your next steps will appear here automatically.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#practicePlanHero', popover: { title: 'Practice Plan', description: 'Use this page to turn recent interview results and module activity into focused next steps.', side: 'bottom', align: 'start' }},
            { element: '#practicePlanActions', popover: { title: 'Quick Actions', description: 'Jump back to Interview Progress or start another practice session when you are ready to apply the plan.', side: 'bottom', align: 'start' }},
            { element: '#practice-ai-recommendations', popover: { title: 'AI Recommendations', description: 'Review the highest-priority actions the system recommends from your current performance patterns.', side: 'top', align: 'start' }},
            { element: '.practice-ai-list', popover: { title: 'Recommended Actions', description: 'Open a recommended lesson, module, or activity to work on the area with the clearest payoff.', side: 'top', align: 'start' }},
            { element: '.practice-ai-empty', popover: { title: 'Unlock Recommendations', description: 'Complete a scored interview to generate recommendations matched to your latest performance.', side: 'top', align: 'start' }},
            { element: '#personalized-practice-plan', popover: { title: 'Personalized Plan', description: 'Follow this short plan to move from insight to practice without deciding from scratch each time.', side: 'top', align: 'start' }},
            { element: '.practice-plan-list', popover: { title: 'Practice Steps', description: 'Each row gives a focused task, estimated time, and shortcut to the right practice area.', side: 'top', align: 'start' }},
            { element: '.practice-plan-tasks', popover: { title: 'Task Checklist', description: 'Use these smaller tasks to keep the practice step concrete and easy to finish.', side: 'top', align: 'start' }},
            { element: '#personalized-practice-plan .skill-empty-state', popover: { title: 'Generate A Plan', description: 'After a scored interview, your next steps will appear here automatically.', side: 'top', align: 'start' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_practice_plan',
            serverDetectedMobile: {{ ($serverDetectedMobile ?? false) ? 'true' : 'false' }},
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
