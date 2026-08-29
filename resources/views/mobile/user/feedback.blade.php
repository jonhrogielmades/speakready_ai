@extends('mobile.layouts.app')
@section('title', 'Philippines Interview Feedback')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/feedback.css?v=3') }}" data-page-style="user-feedback">
@endpush

@section('content')
@php
    $hasActiveFeedbackFilters = filled($feedbackFilters['scenario'] ?? '') || filled($feedbackFilters['search'] ?? '');
@endphp

<div class="db-section active animate-fade-up feedback-shell">
    <div class="feedback-hero" id="feedbackModulesLikeHero">
        <div class="feedback-hero-copy">
            <svg class="feedback-chat-mark" viewBox="0 0 64 64" aria-hidden="true">
                <path d="M13 46.5 8 56l12.6-3.8c3.4 1.6 7.3 2.4 11.4 2.4 14.4 0 26-9.8 26-22S46.4 10.5 32 10.5 6 20.3 6 32.4c0 5.5 2.4 10.5 7 14.1Z" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M20 25h24M20 35h16" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round"/>
                <circle cx="45" cy="42" r="4.5" fill="currentColor" opacity=".72"/>
            </svg>
            <div>
                <h4 class="feedback-title">Feedback Center</h4>
                <p class="feedback-subtitle">Review scores and AI feedback from Philippines interview practice.</p>
            </div>
        </div>
        <svg class="feedback-hero-art" viewBox="0 0 270 190" aria-hidden="true">
            <defs>
                <linearGradient id="feedbackBubble" x1="20" y1="16" x2="225" y2="160"><stop stop-color="#EFF6FF"/><stop offset="1" stop-color="#DBEAFE"/></linearGradient>
                <linearGradient id="feedbackCheck" x1="181" y1="22" x2="234" y2="78"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#1D4ED8"/></linearGradient>
            </defs>
            <path d="M30 34h186c15 0 27 12 27 27v58c0 15-12 27-27 27h-95l-50 30 13-30H30c-15 0-27-12-27-27V61c0-15 12-27 27-27Z" fill="url(#feedbackBubble)" stroke="#BFDBFE" stroke-width="2"/>
            <path d="M45 71h105M45 95h132M45 119h112" stroke="#93C5FD" stroke-width="8" stroke-linecap="round"/>
            <path d="M45 142h59M124 142h76" stroke="#60A5FA" stroke-width="8" stroke-linecap="round" opacity=".88"/>
            <circle cx="211" cy="61" r="31" fill="url(#feedbackCheck)"/>
            <path d="m197 60 10 10 20-24" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M-4 11h12M2 5v12M-27 88h8M-23 84v8" stroke="#60A5FA" stroke-width="5" stroke-linecap="round"/>
            <circle cx="-5" cy="126" r="6" fill="#93C5FD" opacity=".85"/>
        </svg>
    </div>

    <div class="feedback-insight-grid" aria-label="Feedback Center priority insights">
        <section class="feedback-insight-panel" id="feedbackAiSummary" aria-labelledby="feedback-ai-summary-title">
            <div class="feedback-insight-head">
                <span class="feedback-insight-icon" aria-hidden="true"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                <div>
                    <h5 class="feedback-insight-title" id="feedback-ai-summary-title">AI Feedback Summary</h5>
                    <p class="feedback-insight-subtitle">Latest score, strengths, and next focus.</p>
                </div>
            </div>
            @if($feedbackSummary)
                @php
                    $summaryOverall = $feedbackSummary->overall;
                    $summaryColor = $summaryOverall === null
                        ? '#64748b'
                        : ($summaryOverall >= 80 ? '#10b981' : ($summaryOverall >= 60 ? '#2563eb' : ($summaryOverall >= 45 ? '#f59e0b' : '#ef4444')));
                @endphp
                <div class="feedback-summary-layout">
                    <div class="feedback-summary-score" style="--summary-color: {{ $summaryColor }};">
                        @if($summaryOverall === null)
                            <strong style="font-size:1.25rem;">Pending</strong>
                        @else
                            <strong>{{ $summaryOverall }}%</strong>
                        @endif
                        <span>{{ $feedbackSummary->rating }}</span>
                        <small>{{ $feedbackSummary->scenario }} - {{ $feedbackSummary->date }}</small>
                    </div>
                    <div class="feedback-summary-copy">
                        <p class="feedback-summary-headline">{{ $feedbackSummary->headline }}</p>
                        @if($feedbackSummary->metrics->count() > 0)
                            <div class="feedback-metric-grid" aria-label="Latest score metrics">
                                @foreach($feedbackSummary->metrics->take(6) as $metric)
                                    <div class="feedback-metric-chip" style="--metric-color: {{ $metric->color }};">
                                        <i class="fa-solid {{ $metric->icon }}" aria-hidden="true"></i>
                                        <span>{{ $metric->label }}</span>
                                        <strong>{{ $metric->value }}%</strong>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="feedback-summary-note-grid">
                            <div class="feedback-summary-note">
                                <b>Strengths</b>
                                <p>{{ $feedbackSummary->strengths }}</p>
                            </div>
                            <div class="feedback-summary-note">
                                <b>Improve</b>
                                <p>{{ $feedbackSummary->weaknesses }}</p>
                            </div>
                            <div class="feedback-summary-note">
                                <b>Next Focus</b>
                                <p>{{ $feedbackSummary->suggestions }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="feedback-feature-empty">
                    Complete a mock interview to unlock your summary.
                </div>
            @endif
        </section>

        <section class="feedback-insight-panel" id="feedbackPracticeRecommendations" aria-labelledby="feedback-practice-recommendations-title">
            <div class="feedback-insight-head">
                <span class="feedback-insight-icon" aria-hidden="true"><i class="fa-solid fa-route"></i></span>
                <div>
                    <h5 class="feedback-insight-title" id="feedback-practice-recommendations-title">Priority Practice Recommendations</h5>
                    <p class="feedback-insight-subtitle">Your best next steps.</p>
                </div>
            </div>
            <div class="feedback-recommend-list">
                @forelse($practiceRecommendations as $recommendation)
                    <a href="{{ $recommendation->url }}" class="feedback-recommend-item" style="--recommend-color: {{ $recommendation->color }};">
                        <span class="feedback-recommend-icon" aria-hidden="true"><i class="fa-solid {{ $recommendation->icon }}"></i></span>
                        <span class="feedback-recommend-copy">
                            <strong>{{ $recommendation->title }}</strong>
                            <span>{{ $recommendation->description }}</span>
                        </span>
                        <span class="feedback-recommend-cta">{{ $recommendation->cta }}</span>
                    </a>
                @empty
                    <div class="feedback-feature-empty">No recommendations yet.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="feedback-insight-panel feedback-answer-panel" id="feedbackAnswerCoaching" aria-labelledby="feedback-answer-coaching-title">
        <div class="feedback-insight-head">
            <span class="feedback-insight-icon" aria-hidden="true"><i class="fa-solid fa-comments"></i></span>
            <div>
                <h5 class="feedback-insight-title" id="feedback-answer-coaching-title">Answer-by-Answer Coaching</h5>
                <p class="feedback-insight-subtitle">Quick feedback for each latest answer.</p>
            </div>
        </div>
        <div class="feedback-answer-grid">
            @forelse($answerCoachingHighlights as $answerCoaching)
                <article class="feedback-answer-item">
                    <div class="feedback-answer-top">
                        <strong>Q{{ $answerCoaching->number }}: {{ $answerCoaching->question }}</strong>
                        <span class="feedback-answer-score">{{ $answerCoaching->score === null ? 'Pending' : $answerCoaching->score.'%' }}</span>
                    </div>
                    <div class="feedback-answer-user">
                        <b>Your answer</b>
                        <p>{{ $answerCoaching->answer }}</p>
                    </div>
                    <p>{{ $answerCoaching->feedback }}</p>
                    <p class="feedback-answer-focus"><strong>Next:</strong> {{ $answerCoaching->improvement }}</p>
                    <a href="{{ $answerCoaching->review_url }}" class="feedback-answer-action">
                        View details <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </article>
            @empty
                <div class="feedback-feature-empty">
                    Answer coaching appears after a completed interview.
                </div>
            @endforelse
        </div>
    </section>

    <div class="premium-panel">
        <div class="feedback-history-head">
            <h5 class="feedback-history-title">Feedback History</h5>
            <form id="feedbackFilterForm" action="{{ route('user.feedback') }}" method="GET" class="d-none"></form>
            <input form="feedbackFilterForm" type="hidden" name="sort" value="{{ $feedbackFilters['sort'] ?? 'desc' }}">
            <div id="feedback-filters">
                <select id="scenarioFilter" name="scenario" form="feedbackFilterForm" class="form-select db-filter-input">
                    <option value="">All Scenarios</option>
                    @foreach($feedbackCategories as $category)
                        <option value="{{ $category }}" @selected(($feedbackFilters['scenario'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                @php
                    $nextFeedbackSort = ($feedbackFilters['sort'] ?? 'desc') === 'desc' ? 'asc' : 'desc';
                    $feedbackSortQuery = array_filter([
                        'scenario' => $feedbackFilters['scenario'] ?? '',
                        'search' => $feedbackFilters['search'] ?? '',
                        'sort' => $nextFeedbackSort,
                    ], fn ($value) => filled($value));
                @endphp
                <a class="btn btn-outline-secondary" id="sortDateBtn" href="{{ route('user.feedback', $feedbackSortQuery) }}">
                    <i class="fa-solid {{ ($feedbackFilters['sort'] ?? 'desc') === 'desc' ? 'fa-arrow-down-short-wide' : 'fa-arrow-up-wide-short' }} me-2"></i>
                    {{ ($feedbackFilters['sort'] ?? 'desc') === 'desc' ? 'Newest First' : 'Oldest First' }}
                </a>
                @if($sessions->total() > 0)
                    <form class="feedback-clear-form" action="{{ route('user.sessions.clear') }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Clear feedback history" data-sr-confirm-message="This will permanently delete all completed interview sessions and their feedback. This cannot be undone." data-sr-confirm-action="Clear All" data-sr-confirm-variant="danger">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fa-solid fa-trash-can me-2"></i> Clear All
                        </button>
                    </form>
                @endif
                <label for="feedbackSearch" class="visually-hidden">Search feedback history</label>
                <div class="input-group db-filter-input feedback-search-wrap">
                    <span class="input-group-text border-0"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="feedbackSearch" name="search" form="feedbackFilterForm" class="form-control border-0" placeholder="Search feedback..." value="{{ $feedbackFilters['search'] ?? '' }}" aria-describedby="feedbackFilterStatus" autocomplete="off">
                </div>
                <div class="feedback-filter-status" id="feedbackFilterStatus" role="status" aria-live="polite" hidden>
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    <span>Updating feedback...</span>
                </div>
            </div>
        </div>

        @if($sessions->count() == 0)
            <div class="feedback-empty-state">
                <i class="fa-solid fa-message" aria-hidden="true"></i>
                <div class="feedback-empty-copy">
                    <span>{{ $hasFeedbackRecords ? 'No feedback records match your current filters.' : 'Complete a practice interview to generate feedback.' }}</span>
                    @if($hasActiveFeedbackFilters)
                        <a href="{{ route('user.feedback') }}" class="feedback-empty-reset">Clear filters</a>
                    @endif
                </div>
            </div>
        @else
        <div class="table-responsive feedback-table-wrap">
            <table class="table custom-table align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent;" id="feedbackTable">
                <thead>
                    <tr style="border-bottom: 2px solid var(--bd); color: var(--tx3);">
                        <th class="border-0">Date</th>
                        <th class="border-0">Practice Scenario</th>
                        <th class="border-0">Score</th>
                        <th class="border-0">Rating</th>
                        <th class="border-0 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sessions as $session)
                    <tr data-scenario="{{ $session->practice_scenario ?? 'General Job Interview' }}" data-date="{{ $session->created_at->timestamp }}">
                        <td class="border-0 py-3">{{ $session->created_at->format('M d, Y') }}</td>
                        <td class="border-0 py-3 fw-bold">{{ $session->practice_scenario ?? 'General Job Interview' }}</td>
                        @php $sc = $session->score ? $session->score->overall_readiness_score : null; @endphp
                        <td class="border-0 py-3 feedback-mobile-history-cell" colspan="3">
                            <div class="feedback-mobile-history-row">
                                <div class="feedback-mobile-history-stat">
                                    <span>Score</span>
                                    <strong>
                                        @if($session->score)
                                            {{ $session->score->overall_readiness_score }}%
                                        @else
                                            Pending
                                        @endif
                                    </strong>
                                </div>
                                <div class="feedback-mobile-history-stat">
                                    <span>Rating</span>
                                    @if($sc === null) <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Not scored</span>
                                    @elseif($sc >= 90) <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">Excellent</span>
                                    @elseif($sc >= 70) <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span>
                                    @elseif($sc >= 50) <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">Fair</span>
                                    @else <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">Needs Work</span>
                                    @endif
                                </div>
                                <div class="d-flex justify-content-end gap-2 feedback-history-actions">
                                    <a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-primary btn-shine"><i class="fa-solid fa-chart-simple"></i> View Details</a>
                                    <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Delete interview session" data-sr-confirm-message="This will permanently delete this interview session and its feedback. This cannot be undone." data-sr-confirm-action="Delete Session" data-sr-confirm-variant="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete session">
                                            <i class="fa-solid fa-trash-can"></i> <span class="feedback-history-delete-label">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        
        @if($sessions->hasPages())
            <!-- Pagination UI -->
            <div class="mt-4 d-flex justify-content-end" id="feedbackPagination">
                {{ $sessions->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('feedbackSearch');
        const scenarioFilter = document.getElementById('scenarioFilter');
        const filterForm = document.getElementById('feedbackFilterForm');
        const filterStatus = document.getElementById('feedbackFilterStatus');
        const feedbackShell = document.querySelector('.feedback-shell');
        let searchTimer = null;
        let isSubmitting = false;

        function submitFilters() {
            if (!filterForm || isSubmitting) return;
            isSubmitting = true;
            feedbackShell?.classList.add('feedback-is-filtering');
            filterForm.setAttribute('aria-busy', 'true');
            if (filterStatus) {
                filterStatus.hidden = false;
            }
            filterForm.submit();
        }

        if (scenarioFilter) {
            scenarioFilter.addEventListener('change', submitFilters);
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(submitFilters, 450);
            });
            searchInput.addEventListener('keydown', function(event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                clearTimeout(searchTimer);
                submitFilters();
            });
        }
    });
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#feedbackAiSummary', popover: { title: 'AI Summary', description: 'See your latest score and focus.', side: 'bottom', align: 'start' }},
            { element: '#feedbackAnswerCoaching', popover: { title: 'Answer Coaching', description: 'Review each latest answer.', side: 'bottom', align: 'start' }},
            { element: '#feedbackPracticeRecommendations', popover: { title: 'Next Practice', description: 'Choose what to practice next.', side: 'bottom', align: 'start' }},
            { element: '#feedback-filters', popover: { title: 'Filters & Search', description: 'Filter by scenario or search keywords to find a specific feedback record.', side: 'bottom', align: 'start' }},
            { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review past Philippines practice interviews, scores, ratings, and available actions.', side: 'top', align: 'center' }},
            { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Move through older interview feedback records from here.', side: 'top', align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#feedbackAiSummary', popover: { title: 'AI Summary', description: 'See your latest score and focus.', side: 'bottom', align: 'start' }},
            { element: '#feedbackAnswerCoaching', popover: { title: 'Answer Coaching', description: 'Review each latest answer.', side: 'bottom', align: 'start' }},
            { element: '#feedbackPracticeRecommendations', popover: { title: 'Next Practice', description: 'Choose what to practice next.', side: 'bottom', align: 'end' }},
            { element: '#feedback-filters', popover: { title: 'Filters & Search', description: 'Filter by scenario or search keywords to find a specific feedback record.', side: 'bottom', align: 'end' }},
            { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review past Philippines practice interviews, scores, ratings, and available actions.', side: 'top', align: 'center' }},
            { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Move through older interview feedback records from here.', side: 'top', align: 'end' }}
        ];

        const filterTourSteps = (steps) => steps.filter((step) => document.querySelector(step.element));
        const visibleMobileSteps = filterTourSteps(stepsMobile);
        const visibleDesktopSteps = filterTourSteps(stepsDesktop);

        if (!visibleMobileSteps.length && !visibleDesktopSteps.length) return;

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_feedback',
            serverDetectedMobile: true,
            stepsMobile: visibleMobileSteps,
            stepsDesktop: visibleDesktopSteps,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
