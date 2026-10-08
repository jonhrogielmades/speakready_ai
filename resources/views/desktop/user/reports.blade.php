@extends('desktop.layouts.app')
@section('title', 'Reports and Sessions')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/reports.css?v=2') }}" data-page-style="user-reports">
<link rel="stylesheet" href="{{ asset('css/desktop/user/reports-2.css?v=22') }}" data-page-style="user-reports-2">
@endpush

@section('content')
@php
 $reportDateSource = isset($latestSession)? $latestSession?->created_at: null;
 $reportGeneratedDate = $reportDateSource? $reportDateSource->format('F j, Y'): 'Not available yet';
 $reportGeneratedShortDate = $reportDateSource? $reportDateSource->format('M d, Y'): 'Pending';
 $hasComparisonRows = count($comparisonRows?? []) > 0;
 $reportHeaderText = $systemSettings['rep_header'] ?? 'SpeakReady AI Official Report';
 $reportShowLogo = ($systemSettings['rep_logo'] ?? 'yes') === 'yes';
@endphp
<!-- Add print styles for this interview report -->
@include('desktop.partials.page-hero-styles')

<div class="db-section active animate-fade-up" id="portfolioReport">
 <!-- Feature 10: Interview Portfolio Report Header -->
 <div class="sr-page-hero btn-no-print">
 <div class="sr-page-hero-inner">
 <div class="sr-page-hero-copy">
 <div class="reports-hero-icon"><i class="fa-solid fa-file-lines"></i></div>
 <div>
 <h4 class="sr-page-hero-title text-gradient-primary">
 <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h10l4 4v14H5V3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M15 3v5h5M8 13h8M8 17h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
 Reports and Sessions
 </h4>
 <p class="sr-page-hero-subtitle">Review readiness, scores, and comparisons.</p>
 </div>
 </div>
 </div>
 <svg class="sr-page-hero-art reports-hero-art" viewBox="0 0 220 150" aria-hidden="true">
 <defs>
 <linearGradient id="reportsHeroPaperDesktop" x1="50" y1="22" x2="170" y2="128"><stop stop-color="#FFFFFF"/><stop offset="1" stop-color="#EAF4FF"/></linearGradient>
 <linearGradient id="reportsHeroBlueDesktop" x1="74" y1="110" x2="168" y2="110"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#1D9BF0"/></linearGradient>
 <linearGradient id="reportsHeroAquaDesktop" x1="128" y1="52" x2="192" y2="116"><stop stop-color="#22D3EE"/><stop offset="1" stop-color="#10B981"/></linearGradient>
 </defs>
 <g class="reports-art-sheet">
 <rect x="48" y="22" width="112" height="106" rx="18" fill="url(#reportsHeroPaperDesktop)" stroke="#BFDBFE" stroke-width="3"/>
 <path d="M126 22v28h34" fill="#DBEAFE"/>
 <path d="M126 22v28h34" fill="none" stroke="#93C5FD" stroke-width="3" stroke-linejoin="round"/>
 <rect class="reports-art-line" x="66" y="58" width="58" height="8" rx="4" fill="#BFDBFE"/>
 <rect class="reports-art-line" x="66" y="76" width="74" height="8" rx="4" fill="#BFDBFE"/>
 <rect class="reports-art-line" x="66" y="94" width="46" height="8" rx="4" fill="#BFDBFE"/>
 </g>
 <g class="reports-art-chart">
 <rect x="82" y="113" width="22" height="20" rx="6" fill="#60A5FA"/>
 <rect x="112" y="98" width="22" height="35" rx="6" fill="#2563EB"/>
 <rect x="142" y="82" width="22" height="51" rx="6" fill="url(#reportsHeroBlueDesktop)"/>
 <path d="M74 106c17-10 31-8 46-19 18-13 32-15 55-7" fill="none" stroke="#14B8A6" stroke-width="6" stroke-linecap="round"/>
 </g>
 <g class="reports-art-badge">
 <circle cx="165" cy="58" r="32" fill="url(#reportsHeroAquaDesktop)"/>
 <path d="M151 58l9 9 20-25" fill="none" stroke="#FFFFFF" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
 </g>
 <path class="reports-art-spark" d="M35 52h13M41.5 45.5v13M176 122h10M181 117v10M26 108h8" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round"/>
 </svg>
 </div>

 <!-- Print Header visible only when printing or mimicking paper -->
 <div class="report-print-identity d-flex align-items-center mb-4 gap-3">
 @if($reportShowLogo)
 <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="{{ $systemName ?? 'SpeakReady AI' }}" style="width:60px;height:60px;border-radius:50%;object-fit:contain;background:#fff;padding:6px;border:1px solid var(--bd);">
 @else
 <div style="width:60px;height:60px;background:var(--pur);border-radius:50%;display:flex;justify-content:center;align-items:center;">
 <i class="fa-solid fa-user-graduate text-white fs-3"></i>
 </div>
 @endif
 <div>
 <h3 class="text-gradient-primary" style="margin:0;font-weight:800;letter-spacing:-0.5px;">{{ $user->name?? 'Candidate' }}</h3>
 <p style="color:var(--tx);margin:0;font-weight:800;">{{ $reportHeaderText }}</p>
 <p style="color:var(--tx3);margin:0;">{{ $systemName ?? 'SpeakReady AI' }} Interview Report &bull; Generated from {{ $reportGeneratedDate }}</p>
 </div>
 </div>

 @if($hasScoreData)
 <!-- Feature 1: Report Summary -->
 <div id="report-readiness" class="print-card mb-4" style="border-radius:24px; padding:32px;">
 <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-4">
 <div>
 <div class="report-section-kicker">Latest report</div>
 <h5 style="color:var(--tx);font-weight:bold;margin:4px 0 0;"><i class="fa-solid fa-file-invoice text-primary me-2"></i>Report Summary</h5>
 </div>
 <span class="report-chip align-self-start" style="color:#3b82f6;background:rgba(59,130,246,.10);border:1px solid rgba(59,130,246,.22);">
 <i class="fa-regular fa-calendar"></i> Report date {{ $reportGeneratedShortDate }}
 </span>
 </div>
 <div class="row align-items-center text-center text-md-start">
 <div class="col-md-3 border-end" style="border-color:rgba(59, 130, 246, 0.2)!important;">
 <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:0;margin-bottom:8px;">Final Score</h6>
 <div class="report-final-score" style="font-size:3.5rem;font-weight:900;line-height:1;color:{{ $readinessSummary->color }};">{{ $readinessSummary->current }}<span class="report-score-percent" style="font-size:1.5rem">%</span></div>
 <div class="badge mt-2 fs-6" style="background-color:{{ $readinessSummary->color }};color:#fff;">{{ $readinessSummary->rating }}</div>
 </div>
 <div class="col-md-3 border-end mt-4 mt-md-0" style="border-color:rgba(59, 130, 246, 0.2)!important;">
 <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:0;margin-bottom:8px;">Previous Score</h6>
 <div class="report-previous-score" style="font-size:2rem;font-weight:700;line-height:1;color:var(--tx);">{{ $readinessSummary->previous === null? 'N/A': $readinessSummary->previous. '%' }}</div>
 </div>
 <div class="col-md-6 mt-4 mt-md-0 ps-md-4">
 <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:0;margin-bottom:8px;">Readiness Change</h6>
 <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start">
 <i class="fa-solid {{ $readinessSummary->delta === null? 'fa-minus': ($readinessSummary->delta >= 0? 'fa-arrow-trend-up': 'fa-arrow-trend-down') }} fs-1" style="color:{{ $readinessSummary->delta_color }};"></i>
 <div class="report-readiness-delta" style="font-size:2.5rem;font-weight:800;color:{{ $readinessSummary->delta_color }};">{{ $readinessSummary->delta_label }}</div>
 </div>
 <p class="report-readiness-message" style="color:var(--tx);margin-top:8px;font-size:0.95rem;">{{ $readinessSummary->message }}</p>
 </div>
 </div>
 <div class="report-summary-grid mt-4">
 <div class="report-summary-item">
 <span class="report-summary-label">Interview Type</span>
 <div class="report-summary-value">{{ $reportSummary->interview_type }}</div>
 </div>
 <div class="report-summary-item">
 <span class="report-summary-label">Date</span>
 <div class="report-summary-value">{{ $reportSummary->date }}</div>
 </div>
 <div class="report-summary-item">
 <span class="report-summary-label">Duration</span>
 <div class="report-summary-value">{{ $reportSummary->duration }}</div>
 </div>
 <div class="report-summary-item">
 <span class="report-summary-label">Result Level</span>
 <div class="report-summary-value">{{ $reportSummary->result_level }}</div>
 </div>
 <div class="report-summary-item">
 <span class="report-summary-label">Target Role</span>
 <div class="report-summary-value">{{ $reportSummary->target_role }}</div>
 </div>
 <div class="report-summary-item">
 <span class="report-summary-label">Questions</span>
 <div class="report-summary-value">{{ $reportSummary->questions }}</div>
 </div>
 </div>
 </div>

 <div class="row g-4 mb-4 report-score-comparison-row">
 <!-- Feature 2: Detailed Score Breakdown -->
 <div class="{{ $hasComparisonRows? 'col-lg-7': 'col-12' }} report-card-equal-col">
 <div id="report-score-breakdown" class="print-card" style="padding:32px;height:100%;">
 <div class="report-section-kicker">Score details</div>
 <h5 style="color:var(--tx);font-weight:bold;margin:4px 0 20px;"><i class="fa-solid fa-chart-simple text-primary me-2"></i>Detailed Score Breakdown</h5>

 <div class="row mb-4 bg-light bg-opacity-10 rounded p-3 report-score-meta-grid" style="background:var(--bg);">
 <div class="col-6 col-md-3 mb-3 mb-md-0">
 <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Scenario</small>
 <div class="report-score-meta-value" style="color:var(--tx);font-weight:bold;">{{ $latestScenarioLabel }}</div>
 </div>
 <div class="col-6 col-md-3 mb-3 mb-md-0">
 <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Date</small>
 <div class="report-score-meta-value" style="color:var(--tx);font-weight:bold;">{{ $latestSession->created_at->format('M d, Y') }}</div>
 </div>
 <div class="col-6 col-md-3">
 <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Difficulty</small>
 <div class="report-score-meta-value" style="color:var(--tx);font-weight:bold;text-transform:capitalize;">{{ $reportSummary->difficulty }}</div>
 </div>
 <div class="col-6 col-md-3">
 <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Questions</small>
 <div class="report-score-meta-value" style="color:var(--tx);font-weight:bold;">{{ $reportSummary->questions }}</div>
 </div>
 </div>

 <div class="report-score-list">
 @forelse($latestPerformanceMetrics as $metric)
 <div class="report-score-row">
 <div class="d-flex justify-content-between gap-3 mb-2">
 <span class="report-score-name" style="color:var(--tx);font-weight:800;">{{ $metric['name'] }}</span>
 <span class="report-score-value" style="color:var(--tx3);font-weight:800;">{{ $metric['score'] }}%</span>
 </div>
 <div class="progress">
 <div class="progress-bar bg-primary" role="progressbar" aria-label="{{ $metric['name'] }} score" aria-valuenow="{{ $metric['bar'] }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $metric['bar'] }}%;"></div>
 </div>
 </div>
 @empty
 <div class="report-empty-inline">
 <i class="fa-solid fa-circle-info"></i>
 <span>The final score is available, but detailed metric rows were not saved for this interview.</span>
 </div>
 @endforelse
 </div>
 </div>
 </div>

 @if($hasComparisonRows)
 <!-- Feature 8: Performance Comparison Report -->
 <div class="col-lg-5 report-card-equal-col">
 <div id="report-comparison" class="print-card report-comparison-card" style="padding:32px;height:100%;">
 <h5 class="report-comparison-title" style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-code-compare text-warning me-2"></i>Performance Comparison</h5>
 <p class="report-comparison-copy" style="color:var(--tx3);font-size:0.9rem;">Comparing First Interview vs. Latest Interview</p>

 @if(count($comparisonRows) > 0)
 <div class="table-responsive report-comparison-table-wrap">
 <table class="table table-borderless table-sm align-middle report-comparison-table" style="color:var(--tx); background: transparent; --bs-table-bg: transparent; --bs-table-color: var(--tx);">
 <thead style="border-bottom:1px solid var(--bd);">
 <tr>
 <th class="text-uppercase" style="font-size:0.8rem;color:var(--tx3);">Metric</th>
 <th class="text-uppercase text-center" style="font-size:0.8rem;color:var(--tx3);">First Score</th>
 <th class="text-uppercase text-center" style="font-size:0.8rem;color:var(--tx3);">Latest Score</th>
 <th class="text-uppercase text-end" style="font-size:0.8rem;color:var(--tx3);">Trend</th>
 </tr>
 </thead>
 <tbody>
 @foreach($comparisonRows as $row)
 <tr>
 <td class="fw-bold">{{ $row['label'] }}</td>
 <td class="text-center">{{ $row['previous'] }}%</td>
 <td class="text-center text-primary fw-bold">{{ $row['current'] }}%</td>
 <td class="text-end {{ $row['delta'] >= 0? 'text-success': 'text-danger' }}">
 <i class="fa-solid {{ $row['delta'] >= 0? 'fa-arrow-up': 'fa-arrow-down' }} me-1"></i>{{ abs($row['delta']) }}%
 </td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 @endif
 </div>
 </div>
 @endif
 </div>

 @else
 <!-- Empty State -->
 <div id="report-empty-state" class="print-card report-empty-card text-center mb-4">
 <svg class="report-empty-art" viewBox="0 0 220 170" aria-hidden="true">
 <defs>
 <linearGradient id="emptyFolderBack" x1="58" y1="36" x2="159" y2="142"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#60A5FA"/></linearGradient>
 <linearGradient id="emptyFolderFront" x1="78" y1="72" x2="170" y2="144"><stop stop-color="#60A5FA"/><stop offset="1" stop-color="#2563EB"/></linearGradient>
 </defs>
 <circle cx="110" cy="84" r="70" fill="#DBEAFE" opacity=".82"/>
 <path d="M54 60c0-9 7-16 16-16h39l15 15h42c8 0 15 7 15 15v53H54V60Z" fill="url(#emptyFolderBack)"/>
 <path d="M69 82c2-10 10-17 20-17h83c10 0 17 9 15 19l-10 48c-2 9-10 15-19 15H67c-10 0-18-9-16-19l18-46Z" fill="url(#emptyFolderFront)"/>
 <path d="M67 78c3-14 13-23 27-23h83" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round" opacity=".88"/>
 <path d="M31 94h7M35 90v8M183 44h8M187 40v8M42 132h4M172 127h5" stroke="#60A5FA" stroke-width="5" stroke-linecap="round"/>
 </svg>
 <h4 class="report-empty-title" style="color:var(--tx);font-weight:800;">No Scored Interview Report Available</h4>
 <p class="report-empty-copy" style="color:var(--tx3); margin-bottom: 24px; max-width: 560px; margin-left: auto; margin-right: auto;">
 @if($sessions->count() > 0)
 You have completed interview records, but none of them have score data yet. Once a scored interview is available, this page will show your report summary, score breakdown, and performance comparison.
 @else
 Your report is generated automatically from scored interview performance. Complete your first practice interview to unlock your report summary, score breakdown, and performance comparison.
 @endif
 </p>
 <a href="{{ route('interview.setup') }}" class="btn btn-primary btn-shine report-start-btn" style="font-weight:700;"><i class="fa-solid fa-play"></i>Start Interview</a>
 </div>
 @endif

 @include('shared.user.recent-sessions-card', [
 'recentSessionsClientPager' => true,
 'recentSessionsPageSizeDesktop' => 3,
 'recentSessionsPageSizeMobile' => 3,
 ])
</div>

@push('scripts')
<script>
 document.addEventListener("DOMContentLoaded", function() {
 if (typeof window.createSpeakReadyTour!== 'function') return;

 const stepsMobile = [
 { element: '#portfolioReport .sr-page-hero', popover: { title: 'Reports and Sessions', description: 'Use reports to review readiness, scores, comparisons, and recent completed sessions.', side: 'bottom', align: 'start' }},
 { element: '#report-readiness', popover: { title: 'Report Summary', description: 'See the final score, previous score, readiness change, result level, target role, and question count.', side: 'bottom', align: 'start' }},
 { element: '#report-score-breakdown', popover: { title: 'Score Breakdown', description: 'Review metric-level scores for the latest interview, including scenario, date, difficulty, and question count.', side: 'bottom', align: 'start' }},
 { element: '.report-score-list', popover: { title: 'Metric Rows', description: 'Each row shows one scored skill so you can see where the final score came from.', side: 'top', align: 'start' }},
 { element: '#report-comparison', popover: { title: 'Performance Comparison', description: 'Compare first and latest scores to see which skills are moving up or down.', side: 'top', align: 'start' }},
 { element: '#card-recent-sessions', popover: { title: 'Recent Sessions', description: 'Open past interviews, review feedback, or clear old records from this report area.', side: 'top', align: 'start' }},
 { element: '#report-empty-state', popover: { title: 'No Report Yet', description: 'Complete a scored interview to unlock report summaries and score breakdowns.', side: 'top', align: 'start' }}
 ];

 const stepsDesktop = [
 { element: '#portfolioReport .sr-page-hero', popover: { title: 'Reports and Sessions', description: 'Use reports to review readiness, scores, comparisons, and recent completed sessions.', side: 'bottom', align: 'start' }},
 { element: '#report-readiness', popover: { title: 'Report Summary', description: 'See the final score, previous score, readiness change, result level, target role, and question count.', side: 'bottom', align: 'start' }},
 { element: '#report-score-breakdown', popover: { title: 'Score Breakdown', description: 'Review metric-level scores for the latest interview, including scenario, date, difficulty, and question count.', side: 'bottom', align: 'start' }},
 { element: '.report-score-list', popover: { title: 'Metric Rows', description: 'Each row shows one scored skill so you can see where the final score came from.', side: 'top', align: 'start' }},
 { element: '#report-comparison', popover: { title: 'Performance Comparison', description: 'Compare first and latest scores to see which skills are moving up or down.', side: 'top', align: 'start' }},
 { element: '#card-recent-sessions', popover: { title: 'Recent Sessions', description: 'Open past interviews, review feedback, or clear old records from this report area.', side: 'top', align: 'start' }},
 { element: '#report-empty-state', popover: { title: 'No Report Yet', description: 'Complete a scored interview to unlock report summaries and score breakdowns.', side: 'top', align: 'center' }}
 ];

 const filterTourSteps = (steps) => steps.filter((step) => document.querySelector(step.element));
 const visibleMobileSteps = filterTourSteps(stepsMobile);
 const visibleDesktopSteps = filterTourSteps(stepsDesktop);

 if (!visibleMobileSteps.length &&!visibleDesktopSteps.length) return;

 window.createSpeakReadyTour({
 completionKey: 'onboarding_completed_reports',
 serverDetectedMobile: false,
 stepsMobile: visibleMobileSteps,
 stepsDesktop: visibleDesktopSteps,
 autoStartDelay: 500,
 });
 });
</script>
@endpush
@endsection
