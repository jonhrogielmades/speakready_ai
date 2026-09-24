<div class="db-section active animate-fade-up" id="practice-calendar-page">
    @include('shared.partials.practice-page-hero-styles')

    <div class="setup-hero practice-page-hero" id="practiceCalendarHero" aria-labelledby="practice-calendar-hero-title">
        <div class="setup-hero-inner">
            <span class="setup-hero-icon practice-hero-icon" aria-hidden="true">
                <i class="fa-regular fa-calendar-days"></i>
            </span>
            <div class="setup-hero-copy">
                <h4 id="practice-calendar-hero-title" class="setup-hero-title text-gradient-primary">Practice Activity Calendar</h4>
                <p class="setup-hero-subtitle">Track practice days, streaks, and recent interview activity.</p>
            </div>
        </div>
        <svg class="setup-hero-art practice-hero-art practice-calendar-art" viewBox="0 0 220 150" aria-hidden="true" role="img">
            <defs>
                <linearGradient id="practiceCalendarPanel" x1="34" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE"/>
                    <stop offset="1" stop-color="#ECFEFF"/>
                </linearGradient>
                <linearGradient id="practiceCalendarTop" x1="44" y1="34" x2="176" y2="80" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#3B82F6"/>
                    <stop offset="1" stop-color="#06B6D4"/>
                </linearGradient>
            </defs>
            <rect class="practice-art-panel" x="34" y="22" width="152" height="108" rx="18" fill="url(#practiceCalendarPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <path class="practice-art-line" d="M34 52c0-16 13-30 30-30h92c17 0 30 14 30 30v20H34V52z" fill="url(#practiceCalendarTop)"/>
            <path class="practice-art-line" d="M70 16v29M150 16v29" stroke="#1E3A8A" stroke-width="9" stroke-linecap="round"/>
            <rect class="practice-art-cell" x="56" y="86" width="18" height="12" rx="4" fill="#3B82F6"/>
            <rect class="practice-art-cell" x="82" y="86" width="18" height="12" rx="4" fill="#BFDBFE"/>
            <rect class="practice-art-cell" x="108" y="86" width="18" height="12" rx="4" fill="#22C55E"/>
            <rect class="practice-art-cell" x="134" y="86" width="18" height="12" rx="4" fill="#BFDBFE"/>
            <rect class="practice-art-cell" x="56" y="106" width="18" height="12" rx="4" fill="#BFDBFE"/>
            <rect class="practice-art-cell" x="82" y="106" width="18" height="12" rx="4" fill="#3B82F6"/>
            <rect class="practice-art-cell" x="108" y="106" width="18" height="12" rx="4" fill="#BFDBFE"/>
            <rect class="practice-art-cell" x="134" y="106" width="18" height="12" rx="4" fill="#22C55E"/>
            <path class="practice-art-check" d="m146 104 8 8 18-24" fill="none" stroke="#22C55E" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
            <circle class="practice-art-spark" cx="184" cy="84" r="5" fill="#BAE6FD"/>
        </svg>
    </div>

    @include('shared.partials.practice-activity-calendar')
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#practiceCalendarHero', popover: { title: 'Activity Calendar', description: 'Use this page to see how consistently you have practiced over the last 28 days.', side: 'bottom', align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Calendar Panel', description: 'This panel summarizes recent practice activity, streaks, and daily interview completions.', side: 'top', align: 'start' }},
            { element: '.activity-summary-grid', popover: { title: 'Activity Summary', description: 'Check active days, this week, current streak, and the latest practice timing at a glance.', side: 'top', align: 'start' }},
            { element: '.activity-grid', popover: { title: '28-Day Grid', description: 'Each day tile shows whether practice was recorded and how active that day was.', side: 'top', align: 'start' }},
            { element: '.activity-legend', popover: { title: 'Practice Again', description: 'Use the legend and shortcut to keep the streak moving with another practice session.', side: 'top', align: 'start' }},
            { element: '.activity-empty', popover: { title: 'Start Tracking', description: 'Complete your first practice interview to fill the calendar with real activity.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#practiceCalendarHero', popover: { title: 'Activity Calendar', description: 'Use this page to see how consistently you have practiced over the last 28 days.', side: 'bottom', align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Calendar Panel', description: 'This panel summarizes recent practice activity, streaks, and daily interview completions.', side: 'top', align: 'start' }},
            { element: '.activity-summary-grid', popover: { title: 'Activity Summary', description: 'Check active days, this week, current streak, and the latest practice timing at a glance.', side: 'top', align: 'start' }},
            { element: '.activity-grid', popover: { title: '28-Day Grid', description: 'Each day tile shows whether practice was recorded and how active that day was.', side: 'top', align: 'start' }},
            { element: '.activity-legend', popover: { title: 'Practice Again', description: 'Use the legend and shortcut to keep the streak moving with another practice session.', side: 'top', align: 'start' }},
            { element: '.activity-empty', popover: { title: 'Start Tracking', description: 'Complete your first practice interview to fill the calendar with real activity.', side: 'top', align: 'start' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_practice_calendar',
            serverDetectedMobile: {{ ($serverDetectedMobile ?? false) ? 'true' : 'false' }},
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
