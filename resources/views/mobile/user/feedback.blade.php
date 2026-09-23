@extends('mobile.layouts.app')
@section('title', 'Feedback Center')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/feedback.css?v=12') }}" data-page-style="user-feedback">
@endpush

@section('content')
@php
 $hasActiveFeedbackFilters = filled($feedbackFilters['scenario']?? '') || filled($feedbackFilters['search']?? '');
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
 <p class="feedback-subtitle">See what worked, what to improve, and what to practice next.</p>
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
 <h5 class="feedback-insight-title" id="feedback-ai-summary-title">Feedback Summary</h5>
 <p class="feedback-insight-subtitle">Score, strengths, focus area, and next practice.</p>
 </div>
 </div>
 @if($feedbackSummary)
 @php
 $summaryOverall = $feedbackSummary->overall;
 $summaryColor = $summaryOverall === null? '#64748b': ($summaryOverall >= 80? '#10b981': ($summaryOverall >= 60? '#2563eb': ($summaryOverall >= 45? '#f59e0b': '#ef4444')));
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
 <div class="feedback-section-label">Category Breakdown</div>
 <div class="feedback-metric-grid" aria-label="Latest score metrics">
 @foreach($feedbackSummary->metrics->take(4) as $metric)
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
 <b>Focus Area</b>
 <p>{{ $feedbackSummary->weaknesses }}</p>
 </div>
 <div class="feedback-summary-note">
 <b>Next Practice</b>
 <p>{{ $feedbackSummary->suggestions }}</p>
 </div>
 </div>
 <div class="feedback-summary-actions {{ $latestFeedbackSession ? 'feedback-summary-actions-pair' : 'feedback-summary-actions-single' }}">
 <a href="{{ route('interview.setup') }}" class="feedback-summary-action feedback-summary-action-primary">
 <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
 Practice again
 </a>
 @if($latestFeedbackSession)
 <a href="{{ route('user.review', $latestFeedbackSession->id) }}" class="feedback-summary-action">
 <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
 Detailed review
 </a>
 @endif
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
 <h5 class="feedback-insight-title" id="feedback-practice-recommendations-title">Suggested Next Practice</h5>
 <p class="feedback-insight-subtitle">Choose one focused step.</p>
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
 <h5 class="feedback-insight-title" id="feedback-answer-coaching-title">Answer Review</h5>
 <p class="feedback-insight-subtitle">Your answer, short feedback, and the next attempt.</p>
 </div>
 </div>
 <div class="feedback-answer-grid">
 @forelse($answerCoachingHighlights as $answerCoaching)
 <article class="feedback-answer-item">
 <div class="feedback-answer-top">
 <strong>{{ $answerCoaching->label ?? 'Answer '.$answerCoaching->number }}</strong>
 <span class="feedback-answer-score">{{ $answerCoaching->score === null? 'Pending': $answerCoaching->score.'%' }}</span>
 </div>
 <div class="feedback-answer-user">
 <b>Your answer</b>
 <p>{{ $answerCoaching->answer }}</p>
 @if($answerCoaching->has_voice_recording?? false)
 <div class="feedback-answer-voice">
 <span><i class="fa-solid fa-wave-square" aria-hidden="true"></i> Voice answer session</span>
 <audio controls preload="metadata" src="{{ $answerCoaching->voice_recording_url }}"></audio>
 @if($answerCoaching->is_voice_only_answer?? false)
 <small>Feedback is based on this voice answer.</small>
 @endif
 </div>
 @endif
 </div>
 <p class="feedback-answer-feedback"><strong>Feedback:</strong> {{ $answerCoaching->feedback }}</p>
 @if(($answerCoaching->impact ?? '') !== '')
 <p class="feedback-answer-impact"><strong>Impact:</strong> {{ $answerCoaching->impact }}</p>
 @endif
 <p class="feedback-answer-focus"><strong>Next practice:</strong> {{ $answerCoaching->improvement }}</p>
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
 <h5 class="feedback-history-title">Practice History</h5>
 <form id="feedbackFilterForm" action="{{ route('user.feedback') }}" method="GET" class="d-none"></form>
 <input form="feedbackFilterForm" type="hidden" name="sort" value="{{ $feedbackFilters['sort']?? 'desc' }}">
 <div id="feedback-filters">
 <select id="scenarioFilter" name="scenario" form="feedbackFilterForm" class="form-select db-filter-input">
 <option value="">All Scenarios</option>
 @foreach($feedbackCategories as $category)
 <option value="{{ $category }}" @selected(($feedbackFilters['scenario']?? '') === $category)>{{ $category }}</option>
 @endforeach
 </select>
 @php
 $nextFeedbackSort = ($feedbackFilters['sort']?? 'desc') === 'desc'? 'asc': 'desc';
 $feedbackSortQuery = array_filter([
 'scenario' => $feedbackFilters['scenario']?? '',
 'search' => $feedbackFilters['search']?? '',
 'sort' => $nextFeedbackSort,
 ], fn ($value) => filled($value));
 @endphp
 <a class="btn btn-outline-secondary" id="sortDateBtn" href="{{ route('user.feedback', $feedbackSortQuery) }}">
 <i class="fa-solid {{ ($feedbackFilters['sort']?? 'desc') === 'desc'? 'fa-arrow-down-short-wide': 'fa-arrow-up-wide-short' }} me-2"></i>
 {{ ($feedbackFilters['sort']?? 'desc') === 'desc'? 'Newest First': 'Oldest First' }}
 </a>
 <label for="feedbackSearch" class="visually-hidden">Search feedback history</label>
 <div class="input-group db-filter-input feedback-search-wrap">
 <span class="input-group-text border-0"><i class="fa-solid fa-search"></i></span>
 <input type="text" id="feedbackSearch" name="search" form="feedbackFilterForm" class="form-control border-0" placeholder="Search practice history..." value="{{ $feedbackFilters['search']?? '' }}" aria-describedby="feedbackFilterStatus" autocomplete="off">
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
 <span>{{ $hasFeedbackRecords? 'No feedback records match your current filters.': 'Complete a practice interview to generate feedback.' }}</span>
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
 <th class="border-0 text-end">Review</th>
 </tr>
 </thead>
 <tbody>
 @foreach($sessions as $session)
 <tr data-scenario="{{ $session->practice_scenario?? 'General Job Interview' }}" data-date="{{ $session->created_at->timestamp }}">
 <td class="border-0 py-3">{{ $session->created_at->format('M d, Y') }}</td>
 <td class="border-0 py-3 fw-bold">{{ $session->practice_scenario?? 'General Job Interview' }}</td>
 @php $sc = $session->score? $session->score->overall_readiness_score: null; @endphp
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
 @if($sc === null) <span class="badge feedback-score-badge feedback-score-badge-pending" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Not scored</span>
 @elseif($sc >= 90) <span class="badge feedback-score-badge feedback-score-badge-excellent" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">Excellent</span>
 @elseif($sc >= 70) <span class="badge feedback-score-badge feedback-score-badge-good" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span>
 @elseif($sc >= 50) <span class="badge feedback-score-badge feedback-score-badge-fair" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">Fair</span>
 @else <span class="badge feedback-score-badge feedback-score-badge-needs-work" style="background: rgba(245, 158, 11, 0.18); color: #b45309;">Needs Practice</span>
 @endif
 </div>
 <div class="d-flex justify-content-end gap-2 feedback-history-actions">
 <a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-primary btn-shine"><i class="fa-solid fa-chart-simple"></i> View Report</a>
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
 if (event.key!== 'Enter') return;
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
 if (typeof window.createSpeakReadyTour!== 'function') return;

 const stepsMobile = [
 { element: '#feedbackModulesLikeHero', popover: { title: 'Feedback Center', description: 'Use this page to turn completed interviews into strengths, focus areas, and next practice actions.', side: 'bottom', align: 'start' }},
 { element: '#feedbackAiSummary', popover: { title: 'Feedback Summary', description: 'See your latest score, rating, scenario, strengths, focus area, and suggested next practice.', side: 'bottom', align: 'start' }},
 { element: '.feedback-metric-grid', popover: { title: 'Category Breakdown', description: 'Scan the latest category scores to spot which interview skills are strongest or need attention.', side: 'top', align: 'start' }},
 { element: '.feedback-summary-note-grid', popover: { title: 'Strengths And Focus', description: 'Use these notes to understand what worked, what needs work, and what to practice next.', side: 'top', align: 'start' }},
 { element: '.feedback-summary-actions', popover: { title: 'Act On Feedback', description: 'Start another practice session or open the detailed review for the latest interview.', side: 'top', align: 'start' }},
 { element: '#feedbackAiSummary .feedback-feature-empty', popover: { title: 'Unlock Summary', description: 'Complete a mock interview to generate your AI feedback summary.', side: 'top', align: 'start' }},
 { element: '#feedbackPracticeRecommendations', popover: { title: 'Suggested Next Practice', description: 'Choose a module, drill, challenge, or interview action based on the feedback patterns.', side: 'bottom', align: 'start' }},
 { element: '.feedback-recommend-item', popover: { title: 'Recommended Action', description: 'Open a focused next step directly from the recommendation list.', side: 'top', align: 'start' }},
 { element: '#feedbackAnswerCoaching', popover: { title: 'Answer Review', description: 'Review latest answers with the answer text, score, feedback, and next practice cue.', side: 'bottom', align: 'start' }},
 { element: '.feedback-answer-item', popover: { title: 'Answer Coaching Card', description: 'Each card pairs your answer with short coaching and a shortcut to deeper review.', side: 'top', align: 'start' }},
 { element: '.feedback-answer-voice', popover: { title: 'Voice Answer Playback', description: 'When an answer used audio, listen here and review coaching against the spoken response.', side: 'top', align: 'start' }},
 { element: '#feedbackAnswerCoaching .feedback-feature-empty', popover: { title: 'Unlock Answer Coaching', description: 'Answer coaching appears after a completed interview with saved responses.', side: 'top', align: 'start' }},
 { element: '#feedback-filters', popover: { title: 'Filters And Search', description: 'Filter by scenario, sort by date, or search keywords to find a specific feedback record.', side: 'bottom', align: 'start' }},
 { element: '#scenarioFilter', popover: { title: 'Scenario Filter', description: 'Narrow the history to one interview scenario when you want targeted feedback.', side: 'bottom', align: 'start' }},
 { element: '#sortDateBtn', popover: { title: 'Sort History', description: 'Switch between newest and oldest records while reviewing past practice.', side: 'bottom', align: 'center' }},
 { element: '#feedbackSearch', popover: { title: 'Search Feedback', description: 'Search by scenario, notes, or keywords to quickly locate an interview.', side: 'bottom', align: 'start' }},
 { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review past practice interviews, scores, ratings, and report actions.', side: 'top', align: 'center' }},
 { element: '.feedback-history-actions', popover: { title: 'Open Report', description: 'Use the action button to view the full report for a previous interview.', side: 'top', align: 'center' }},
 { element: '.feedback-empty-state', popover: { title: 'No Records Yet', description: 'If the history is empty, start a practice interview or clear filters to show available feedback.', side: 'top', align: 'start' }},
 { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Move through older interview feedback records from here.', side: 'top', align: 'center' }}
 ];

 const stepsDesktop = [
 { element: '#feedbackModulesLikeHero', popover: { title: 'Feedback Center', description: 'Use this page to turn completed interviews into strengths, focus areas, and next practice actions.', side: 'bottom', align: 'start' }},
 { element: '#feedbackAiSummary', popover: { title: 'Feedback Summary', description: 'See your latest score, rating, scenario, strengths, focus area, and suggested next practice.', side: 'bottom', align: 'start' }},
 { element: '.feedback-metric-grid', popover: { title: 'Category Breakdown', description: 'Scan the latest category scores to spot which interview skills are strongest or need attention.', side: 'top', align: 'start' }},
 { element: '.feedback-summary-note-grid', popover: { title: 'Strengths And Focus', description: 'Use these notes to understand what worked, what needs work, and what to practice next.', side: 'top', align: 'start' }},
 { element: '.feedback-summary-actions', popover: { title: 'Act On Feedback', description: 'Start another practice session or open the detailed review for the latest interview.', side: 'top', align: 'start' }},
 { element: '#feedbackAiSummary .feedback-feature-empty', popover: { title: 'Unlock Summary', description: 'Complete a mock interview to generate your AI feedback summary.', side: 'top', align: 'start' }},
 { element: '#feedbackPracticeRecommendations', popover: { title: 'Suggested Next Practice', description: 'Choose a module, drill, challenge, or interview action based on the feedback patterns.', side: 'bottom', align: 'end' }},
 { element: '.feedback-recommend-item', popover: { title: 'Recommended Action', description: 'Open a focused next step directly from the recommendation list.', side: 'top', align: 'start' }},
 { element: '#feedbackAnswerCoaching', popover: { title: 'Answer Review', description: 'Review latest answers with the answer text, score, feedback, and next practice cue.', side: 'bottom', align: 'start' }},
 { element: '.feedback-answer-item', popover: { title: 'Answer Coaching Card', description: 'Each card pairs your answer with short coaching and a shortcut to deeper review.', side: 'top', align: 'start' }},
 { element: '.feedback-answer-voice', popover: { title: 'Voice Answer Playback', description: 'When an answer used audio, listen here and review coaching against the spoken response.', side: 'top', align: 'start' }},
 { element: '#feedbackAnswerCoaching .feedback-feature-empty', popover: { title: 'Unlock Answer Coaching', description: 'Answer coaching appears after a completed interview with saved responses.', side: 'top', align: 'start' }},
 { element: '#feedback-filters', popover: { title: 'Filters And Search', description: 'Filter by scenario, sort by date, or search keywords to find a specific feedback record.', side: 'bottom', align: 'end' }},
 { element: '#scenarioFilter', popover: { title: 'Scenario Filter', description: 'Narrow the history to one interview scenario when you want targeted feedback.', side: 'bottom', align: 'start' }},
 { element: '#sortDateBtn', popover: { title: 'Sort History', description: 'Switch between newest and oldest records while reviewing past practice.', side: 'bottom', align: 'center' }},
 { element: '#feedbackSearch', popover: { title: 'Search Feedback', description: 'Search by scenario, notes, or keywords to quickly locate an interview.', side: 'bottom', align: 'end' }},
 { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review past practice interviews, scores, ratings, and report actions.', side: 'top', align: 'center' }},
 { element: '.feedback-history-actions', popover: { title: 'Open Report', description: 'Use the action button to view the full report for a previous interview.', side: 'top', align: 'center' }},
 { element: '.feedback-empty-state', popover: { title: 'No Records Yet', description: 'If the history is empty, start a practice interview or clear filters to show available feedback.', side: 'top', align: 'start' }},
 { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Move through older interview feedback records from here.', side: 'top', align: 'end' }}
 ];

 const filterTourSteps = (steps) => steps.filter((step) => document.querySelector(step.element));
 const visibleMobileSteps = filterTourSteps(stepsMobile);
 const visibleDesktopSteps = filterTourSteps(stepsDesktop);

 if (!visibleMobileSteps.length &&!visibleDesktopSteps.length) return;

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
