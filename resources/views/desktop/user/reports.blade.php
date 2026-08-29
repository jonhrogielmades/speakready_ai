@extends('desktop.layouts.app')
@section('title', 'Philippines Interview Reports')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/reports.css?v=1') }}" data-page-style="user-reports">
<link rel="stylesheet" href="{{ asset('css/desktop/user/reports-2.css?v=3') }}" data-page-style="user-reports-2">
@endpush

@section('content')
@php
    $reportDateSource = isset($latestSession) ? $latestSession?->created_at : null;
    $reportGeneratedDate = $reportDateSource ? $reportDateSource->format('F j, Y') : 'Not available yet';
    $reportGeneratedShortDate = $reportDateSource ? $reportDateSource->format('M d, Y') : 'Pending';
@endphp
<!-- Add print styles specifically for this Philippines interview report -->
@include('desktop.partials.page-hero-styles')

<div class="db-section active animate-fade-up" id="portfolioReport">
    <!-- Feature 10: Interview Portfolio Report Header & Actions -->
    <div class="sr-page-hero btn-no-print">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="reports-hero-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h10l4 4v14H5V3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M15 3v5h5M8 13h8M8 17h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Interview Reports
                    </h4>
                    <p class="sr-page-hero-subtitle">Review readiness, feedback, and progress.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="reportPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="reportBlue" x1="62" y1="42" x2="164" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="42" y="18" width="128" height="116" rx="16" fill="url(#reportPanel)" stroke="#BFDBFE" stroke-width="3"/><path d="M138 18v30h32" fill="#BAE6FD"/><path d="M68 63h74M68 81h62M68 99h34" stroke="#93C5FD" stroke-width="7" stroke-linecap="round"/><path d="M76 118l18-18 15 10 25-30" fill="none" stroke="url(#reportBlue)" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="164" cy="48" r="17" fill="#22C55E"/><path d="M157 48l5 5 10-12" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    @if($sessions->count() > 0)
        <div class="sr-page-actions report-export-actions btn-no-print">
            <form action="{{ route('user.sessions.clear') }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Clear interview sessions" data-sr-confirm-message="This will permanently delete all completed interview sessions and report data. This cannot be undone." data-sr-confirm-action="Clear Sessions" data-sr-confirm-variant="danger">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-shine" style="border-radius:12px;font-weight:600;width:100%;">
                    <i class="fa-solid fa-trash-can me-2"></i>Clear Sessions
                </button>
            </form>
        </div>
    @endif

    <!-- Print Header visible only when printing or mimicking paper -->
    <div class="report-print-identity d-flex align-items-center mb-4 gap-3">
        <div style="width:60px;height:60px;background:var(--pur);border-radius:50%;display:flex;justify-content:center;align-items:center;">
            <i class="fa-solid fa-user-graduate text-white fs-3"></i>
        </div>
        <div>
            <h3 class="text-gradient-primary" style="margin:0;font-weight:800;letter-spacing:-0.5px;">{{ $user->name ?? 'Candidate' }}</h3>
            <p style="color:var(--tx3);margin:0;">SpeakReady AI Philippines Interview Report &bull; Generated from {{ $reportGeneratedDate }}</p>
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
            <div class="col-md-3 border-end" style="border-color:rgba(59, 130, 246, 0.2) !important;">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:0;margin-bottom:8px;">Final Score</h6>
                <div style="font-size:3.5rem;font-weight:900;line-height:1;color:{{ $readinessSummary->color }};">{{ $readinessSummary->current }}<span style="font-size:1.5rem">%</span></div>
                <div class="badge mt-2 fs-6" style="background-color:{{ $readinessSummary->color }};color:#fff;">{{ $readinessSummary->rating }}</div>
            </div>
            <div class="col-md-3 border-end mt-4 mt-md-0" style="border-color:rgba(59, 130, 246, 0.2) !important;">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:0;margin-bottom:8px;">Previous Score</h6>
                <div style="font-size:2rem;font-weight:700;line-height:1;color:var(--tx);">{{ $readinessSummary->previous === null ? 'N/A' : $readinessSummary->previous . '%' }}</div>
            </div>
            <div class="col-md-6 mt-4 mt-md-0 ps-md-4">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:0;margin-bottom:8px;">Readiness Change</h6>
                <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start">
                    <i class="fa-solid {{ $readinessSummary->delta === null ? 'fa-minus' : ($readinessSummary->delta >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down') }} fs-1" style="color:{{ $readinessSummary->delta_color }};"></i>
                    <div style="font-size:2.5rem;font-weight:800;color:{{ $readinessSummary->delta_color }};">{{ $readinessSummary->delta_label }}</div>
                </div>
                <p style="color:var(--tx);margin-top:8px;font-size:0.95rem;">{{ $readinessSummary->message }}</p>
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

    <div class="row g-4 mb-4">
        <!-- Feature 2: Detailed Score Breakdown -->
        <div class="col-lg-7">
            <div class="print-card" style="padding:32px;height:100%;">
                <div class="report-section-kicker">Score details</div>
                <h5 style="color:var(--tx);font-weight:bold;margin:4px 0 20px;"><i class="fa-solid fa-chart-simple text-primary me-2"></i>Detailed Score Breakdown</h5>

                <div class="row mb-4 bg-light bg-opacity-10 rounded p-3" style="background:var(--bg);">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Scenario</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestScenarioLabel }}</div>
                    </div>
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Date</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestSession->created_at->format('M d, Y') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Difficulty</small>
                        <div style="color:var(--tx);font-weight:bold;text-transform:capitalize;">{{ $reportSummary->difficulty }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Questions</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $reportSummary->questions }}</div>
                    </div>
                </div>

                <div class="report-score-list">
                    @foreach($latestPerformanceMetrics as $metric)
                    <div class="report-score-row">
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <span style="color:var(--tx);font-weight:800;">{{ $metric['name'] }}</span>
                            <span style="color:var(--tx3);font-weight:800;">{{ $metric['score'] }}%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" aria-label="{{ $metric['name'] }} score" aria-valuenow="{{ $metric['bar'] }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $metric['bar'] }}%;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Feature 8: Performance Comparison Report -->
        <div class="col-lg-5">
            <div id="report-comparison" class="print-card" style="padding:32px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-code-compare text-warning me-2"></i>Performance Comparison</h5>
                <p style="color:var(--tx3);font-size:0.9rem;">Comparing First Interview vs. Latest Interview</p>

                @if(count($comparisonRows) > 0)
                <div class="table-responsive">
                    <table class="table table-borderless table-sm align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent; --bs-table-color: var(--tx);">
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
                            <td class="text-end {{ $row['delta'] >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="fa-solid {{ $row['delta'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} me-1"></i>{{ abs($row['delta']) }}%
                            </td>
                        </tr>
                        @endforeach
                      </tbody>
                  </table>
                </div>
                @else
                <div class="text-center py-4" style="color:var(--tx3);">
                    <p>Complete at least 2 Philippines practice interviews to view performance comparison.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 2: Feedback Summary Report -->
    <div class="row mb-4">
        <div class="col-12">
            <div id="report-feedback" class="print-card" style="padding:32px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-comment-dots text-info me-2"></i>Feedback Summary Report</h5>
                @if($feedbackSummary->has_data)
                @php
                    $strengths = $feedbackSummary->strengths ?: ['None identified yet'];
                    $weaknesses = $feedbackSummary->weaknesses ?: ['None identified yet'];
                    $primaryRecommendation = ($feedbackSummary->weaknesses[0] ?? null)
                        ? 'Focus on your ' . strtolower($feedbackSummary->weaknesses[0])
                        : 'Maintain your strongest interview skills';
                @endphp
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(16,185,129,0.05);border-radius:12px;border:1px solid rgba(16,185,129,0.2);height:100%;">
                            <h6 style="color:#10b981;font-weight:bold;"><i class="fa-solid fa-check-circle me-2"></i>Strengths</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                @foreach($strengths as $s)
                                <li>{{ $s }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(239,68,68,0.05);border-radius:12px;border:1px solid rgba(239,68,68,0.2);height:100%;">
                            <h6 style="color:#ef4444;font-weight:bold;"><i class="fa-solid fa-circle-xmark me-2"></i>Areas for Improvement</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                @foreach($weaknesses as $w)
                                <li>{{ $w }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(59,130,246,0.05);border-radius:12px;border:1px solid rgba(59,130,246,0.2);height:100%;">
                            <h6 style="color:#3b82f6;font-weight:bold;"><i class="fa-solid fa-lightbulb me-2"></i>Recommended Practice</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                <li>{{ $primaryRecommendation }}</li>
                                <li>Review your latest Philippines interview feedback</li>
                                <li>Complete one focused voice rehearsal</li>
                            </ul>
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center py-4" style="color:var(--tx3);">
                    <p>Complete an interview to see your AI feedback summary.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 3: Question-by-Question Analysis -->
    <div class="row mb-4">
        <div class="col-12">
            <div id="report-question-review" class="print-card" style="padding:32px;">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div>
                        <div class="report-section-kicker">Answer review</div>
                        <h5 style="color:var(--tx);font-weight:bold;margin:4px 0 0;"><i class="fa-solid fa-list-check text-primary me-2"></i>Question-by-Question Analysis</h5>
                    </div>
                    @if($latestSession)
                        <a href="{{ route('user.review', $latestSession->id) }}" class="btn btn-outline-primary btn-sm btn-no-print" style="border-radius:10px;font-weight:800;align-self:start;">
                            <i class="fa-solid fa-up-right-from-square me-1"></i>Open Full Report
                        </a>
                    @endif
                </div>

                @if($questionReviews->isNotEmpty())
                    <div class="d-flex flex-column gap-3">
                        @foreach($questionReviews as $review)
                            <div class="report-question-card">
                                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                                    <div style="min-width:0;">
                                        <div class="report-section-kicker">Question {{ $review->number }}</div>
                                        <h6 style="color:var(--tx);font-weight:800;margin:5px 0 0;line-height:1.4;overflow-wrap:anywhere;">{{ $review->question }}</h6>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 align-items-start">
                                        <span class="report-chip" style="color:{{ $review->score_color }};background:rgba(100,116,139,.08);border:1px solid rgba(100,116,139,.18);">
                                            <i class="fa-solid fa-gauge-high"></i>{{ $review->score_label }}
                                        </span>
                                        <span class="report-chip" style="color:#3b82f6;background:rgba(59,130,246,.10);border:1px solid rgba(59,130,246,.22);">
                                            <i class="fa-solid fa-clipboard-check"></i>{{ $review->status_label }}
                                        </span>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-lg-5">
                                        <div class="report-section-kicker mb-2">User Answer</div>
                                        <div class="report-question-answer">{{ $review->answer }}</div>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div style="height:100%;padding:12px;border-radius:10px;background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.18);">
                                                    <div style="color:#10b981;font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:7px;"><i class="fa-solid fa-check-circle me-1"></i>Strength</div>
                                                    <p style="color:var(--tx);font-size:.9rem;line-height:1.55;margin:0;">{{ $review->strength }}</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div style="height:100%;padding:12px;border-radius:10px;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.2);">
                                                    <div style="color:#f59e0b;font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:7px;"><i class="fa-solid fa-screwdriver-wrench me-1"></i>Improve</div>
                                                    <p style="color:var(--tx);font-size:.9rem;line-height:1.55;margin:0;">{{ $review->improvement }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3" style="padding:12px;border-radius:10px;background:rgba(59,130,246,.055);border:1px solid rgba(59,130,246,.16);">
                                            <div style="color:#3b82f6;font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:7px;"><i class="fa-solid fa-comment-dots me-1"></i>AI Feedback</div>
                                            <p style="color:var(--tx);font-size:.9rem;line-height:1.55;margin:0;">{{ $review->feedback }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4" style="color:var(--tx3);">
                        <p>No question-level answers were recorded for this scored interview yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 4: Mistakes & Improvement Areas -->
    <div class="row mb-4">
        <div class="col-12">
            <div id="report-improvements" class="print-card" style="padding:32px;">
                <div class="report-section-kicker">Priority fixes</div>
                <h5 style="color:var(--tx);font-weight:bold;margin:4px 0 20px;"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i>Mistakes &amp; Improvement Areas</h5>

                @if($improvementAreas->isNotEmpty())
                    <div class="row g-3">
                        @foreach($improvementAreas as $area)
                            <div class="col-md-6">
                                <div class="report-improvement-card">
                                    <div class="d-flex gap-3">
                                        <div style="width:36px;height:36px;flex:0 0 36px;border-radius:10px;background:rgba(100,116,139,.08);border:1px solid rgba(100,116,139,.18);display:flex;align-items:center;justify-content:center;color:{{ $area->color }};">
                                            <i class="fa-solid fa-arrow-trend-up"></i>
                                        </div>
                                        <div style="min-width:0;">
                                            <h6 style="color:var(--tx);font-weight:800;margin:0 0 7px;overflow-wrap:anywhere;">{{ $area->issue }}</h6>
                                            <p style="color:var(--tx3);font-size:.86rem;line-height:1.55;margin:0 0 8px;"><strong style="color:var(--tx);">Evidence:</strong> {{ $area->evidence }}</p>
                                            <p style="color:var(--tx);font-size:.9rem;line-height:1.55;margin:0;"><strong style="color:{{ $area->color }};">Next fix:</strong> {{ $area->fix }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4" style="color:var(--tx3);">
                        <p>No repeated mistakes were detected in the latest scored interview report.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 5: Download / Export Report -->
    <div class="row mb-4 btn-no-print">
        <div class="col-12">
            <div id="report-export" class="print-card" style="padding:32px;">
                <div class="report-section-kicker">Export options</div>
                <h5 style="color:var(--tx);font-weight:bold;margin:4px 0 20px;"><i class="fa-solid fa-download text-success me-2"></i>Download / Export Report</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="report-export-choice">
                            <div>
                                <h6 style="color:var(--tx);font-weight:800;margin:0 0 5px;">PDF Report</h6>
                                <p style="color:var(--tx3);font-size:.86rem;line-height:1.45;margin:0;">Save the full interview report view.</p>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm js-export-pdf" id="exportPdfBtn"><i class="fa-solid fa-file-pdf me-1"></i>PDF</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="report-export-choice">
                            <div>
                                <h6 style="color:var(--tx);font-weight:800;margin:0 0 5px;">Score Sheet</h6>
                                <p style="color:var(--tx3);font-size:.86rem;line-height:1.45;margin:0;">Export comparison rows to Excel.</p>
                            </div>
                            <button type="button" class="btn btn-success btn-sm js-export-excel" id="exportExcelBtn"><i class="fa-solid fa-file-excel me-1"></i>Excel</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="report-export-choice">
                            <div>
                                <h6 style="color:var(--tx);font-weight:800;margin:0 0 5px;">Question CSV</h6>
                                <p style="color:var(--tx3);font-size:.86rem;line-height:1.45;margin:0;">Download answers and feedback rows.</p>
                            </div>
                            @if($latestSession)
                                <a href="{{ route('user.sessions.export', $latestSession) }}" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-table me-1"></i>CSV</a>
                            @else
                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled><i class="fa-solid fa-table me-1"></i>CSV</button>
                            @endif
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-3 js-print-report btn-no-print" style="border-radius:10px;font-weight:800;">
                    <i class="fa-solid fa-print me-1"></i>Print Report
                </button>
                <p class="report-export-status" id="reportExportStatus" role="status" aria-live="polite" hidden></p>
            </div>
        </div>
    </div>

    <!-- Feature 3: Progress Report Charts -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-chart-line text-success me-2"></i>Readiness Score Trend</h5>
                <div class="report-chart-frame" style="height:250px;">
                    <canvas id="trendChart"></canvas>
                    <div class="report-chart-fallback d-none" id="trendChartFallback">Score trend chart is unavailable right now.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-chart-bar text-primary me-2"></i>Scenario Performance</h5>
                <div class="report-chart-frame" style="height:250px;">
                    <canvas id="catChart"></canvas>
                    <div class="report-chart-fallback d-none" id="catChartFallback">Scenario performance chart is unavailable right now.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 7: Skill Analysis Report -->
        <div class="col-md-6">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-crosshairs text-danger me-2"></i>Skill Analysis Report</h5>
                @php
                    $skillRows = array_values(array_filter($comparisonRows, fn($row) => $row['label'] !== 'Overall Score'));
                @endphp
                @if(count($skillRows) > 0)
                @foreach($skillRows as $sk)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.9rem;">
                        <span style="color:var(--tx);font-weight:600;">{{ $sk['label'] }}</span>
                        <span style="color:var(--tx3)">{{ $sk['current'] }}% <span class="{{ $sk['delta'] >= 0 ? 'text-success' : 'text-danger' }} ms-2">({{ $sk['delta'] >= 0 ? '+' : '' }}{{ $sk['delta'] }}%)</span></span>
                    </div>
                    <div class="progress" style="height:8px;background:var(--bd);border-radius:4px;">
                        <div class="progress-bar bg-primary" role="progressbar" aria-label="{{ $sk['label'] }} score" aria-valuenow="{{ $sk['bar'] }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $sk['bar'] }}%;border-radius:4px;"></div>
                    </div>
                </div>
                @endforeach
                @else
                <div class="text-center py-4" style="color:var(--tx3);">
                    <p>Complete at least 2 Philippines practice interviews to track your specific skill improvements.</p>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-6 d-flex flex-column gap-4">
            <!-- Feature 4: Voice Rehearsal Report -->
            <div class="print-card flex-grow-1" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-microphone-lines text-warning me-2"></i>Voice Rehearsal Report</h5>
                <div class="row text-center align-items-center h-100 gy-3">
                    <div class="col-4 border-end px-1 px-sm-3" style="border-color:var(--bd)!important;">
                        <div style="font-size:clamp(1.2rem, 5vw, 1.8rem);font-weight:bold;color:var(--tx);">{{ $voiceData->wpm ?? 'N/A' }}</div>
                        <div style="font-size:clamp(0.55rem, 2.2vw, 0.75rem);color:var(--tx3);text-transform:uppercase;font-weight:600;">Pace (WPM)</div>
                    </div>
                    <div class="col-4 border-end px-1 px-sm-3" style="border-color:var(--bd)!important;">
                        <div style="font-size:clamp(1.2rem, 5vw, 1.8rem);font-weight:bold;color:var(--tx);">{{ $voiceData->confidence === null ? 'N/A' : $voiceData->confidence . '%' }}</div>
                        <div style="font-size:clamp(0.55rem, 2.2vw, 0.75rem);color:var(--tx3);text-transform:uppercase;font-weight:600;">Speaking Steadiness</div>
                    </div>
                    <div class="col-4 px-1 px-sm-3">
                        <div style="font-size:clamp(1.2rem, 5vw, 1.8rem);font-weight:bold;color:#ef4444;">{{ $voiceData->filler_words ?? 'N/A' }}</div>
                        <div style="font-size:clamp(0.55rem, 2.2vw, 0.75rem);color:var(--tx3);text-transform:uppercase;font-weight:600;">Filler Words</div>
                    </div>
                </div>
            </div>

            <!-- Feature 5: Learning Progress Report -->
            <div id="report-learning" class="print-card flex-grow-1" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-graduation-cap text-info me-2"></i>Learning Progress Report</h5>
                <div class="row align-items-center h-100 gy-3">
                    <div class="col-md-6 text-center text-md-start">
                        <div style="font-size:clamp(2rem, 8vw, 2.5rem);font-weight:bold;color:#0dcaf0;line-height:1;">{{ $learningData->completion_rate }}%</div>
                        <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:12px;">Overall Completion</div>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0" style="color:var(--tx);font-size:0.9rem;">
                            <li class="mb-2 d-flex justify-content-between align-items-center"><span>Lessons:</span> <strong>{{ $learningData->lessons_completed }}/{{ $learningData->lessons_total }}</strong></li>
                            <li class="mb-2 d-flex justify-content-between align-items-center"><span>Videos:</span> <strong>{{ $learningData->videos_watched }}</strong></li>
                            <li class="d-flex justify-content-between align-items-center"><span>Quiz Avg:</span> <strong>{{ $learningData->quiz_average === null ? 'N/A' : $learningData->quiz_average . '%' }}</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 9: Achievement Report -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-award text-warning me-2"></i>Achievement Report</h5>
                <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-md-start">
                    @forelse($achievements as $ach)
                    <div class="text-center" style="width:110px;">
                        <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.05);border:2px solid {{ $ach->color }};display:flex;justify-content:center;align-items:center;margin:0 auto 12px;box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                            <i class="fa-solid {{ $ach->icon }} fs-2" style="color:{{ $ach->color }};"></i>
                        </div>
                        <div style="font-size:0.8rem;color:var(--tx);font-weight:600;line-height:1.2;">{{ $ach->title }}</div>
                    </div>
                    @empty
                    <p style="color:var(--tx3);margin:0;">No achievements earned yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
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
                You have completed interview records, but none of them have score data yet. Once a scored Philippines interview is available, this page will show your report summary, score breakdown, question analysis, improvement areas, and export options.
            @else
                Your report is generated automatically from scored Philippines interview performance. Complete your first practice interview to unlock your report summary, score breakdown, question analysis, improvement areas, and export options.
            @endif
        </p>
        <a href="{{ route('interview.setup') }}" class="btn btn-primary btn-shine report-start-btn" style="font-weight:700;"><i class="fa-solid fa-play"></i>Start Philippine Interview</a>
    </div>
    @endif
</div>

<!-- Scripts for Charts -->
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const showChartFallback = function(canvas, message) {
            if (canvas) {
                canvas.classList.add('d-none');
            }

            const fallback = canvas ? document.getElementById(canvas.id + 'Fallback') : null;
            if (!fallback) return;

            fallback.textContent = message;
            fallback.classList.remove('d-none');
        };

        @if($hasScoreData)
        const trendData = @json($scoreTrend);
        const labels = trendData.map(d => d.date);
        const scores = trendData.map(d => d.score);

        const trendChart = document.getElementById('trendChart');
        if (window.Chart && trendChart && labels.length > 0) {
            new Chart(trendChart, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Score Trend',
                        data: scores,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        } else {
            showChartFallback(trendChart, 'Score trend chart is unavailable right now.');
        }

        const scenarioPerf = @json($categoryPerf);
        const scenarioLabels = Object.keys(scenarioPerf);
        const scenarioScores = Object.values(scenarioPerf);

        const categoryChart = document.getElementById('catChart');
        if (window.Chart && categoryChart && scenarioLabels.length > 0) {
            new Chart(categoryChart, {
                type: 'bar',
                data: {
                    labels: scenarioLabels,
                    datasets: [{
                        data: scenarioScores,
                        backgroundColor: ['#3b82f6', '#f59e0b', '#8b5cf6', '#10b981', '#ef4444'],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        } else {
            showChartFallback(categoryChart, 'Scenario performance chart is unavailable right now.');
        }
        @endif

        const latestScoreRows = @json($latestPerformanceMetrics ?? []);
        const reportFinalScore = @json($reportSummary->final_score_label ?? 'N/A');
        const exportStatus = document.getElementById('reportExportStatus');
        const setExportStatus = function(message, state = 'info') {
            if (!exportStatus) return;
            exportStatus.textContent = message;
            exportStatus.dataset.state = state;
            exportStatus.hidden = false;
        };
        const withReportActionsHidden = function(callback) {
            const element = document.getElementById('portfolioReport');
            const hiddenActions = element ? Array.from(element.querySelectorAll('.btn-no-print')) : [];
            const originalDisplays = hiddenActions.map((action) => action.style.display);
            const restore = function() {
                hiddenActions.forEach((action, index) => {
                    action.style.display = originalDisplays[index];
                });
            };

            hiddenActions.forEach((action) => {
                action.style.display = 'none';
            });

            callback(restore);
        };
        const csvEscape = function(value) {
            const text = String(value ?? '').replace(/\s+/g, ' ').trim();
            return `"${text.replace(/"/g, '""')}"`;
        };
        const downloadCsvFromTable = function(table, filename) {
            const rows = Array.from(table.querySelectorAll('tr'))
                .map((row) => Array.from(row.querySelectorAll('th,td')).map((cell) => csvEscape(cell.textContent)).join(','))
                .join('\n');
            const blob = new Blob([rows], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        };
        const buildLatestScoreTable = function() {
            if (!Array.isArray(latestScoreRows) || latestScoreRows.length === 0) {
                return null;
            }

            const table = document.createElement('table');
            const thead = document.createElement('thead');
            const header = document.createElement('tr');
            ['Metric', 'Latest Score'].forEach((label) => {
                const th = document.createElement('th');
                th.textContent = label;
                header.appendChild(th);
            });
            thead.appendChild(header);

            const tbody = document.createElement('tbody');
            [['Final Score', reportFinalScore], ...latestScoreRows.map((row) => [row.name, `${row.score}%`])].forEach((row) => {
                const tr = document.createElement('tr');
                row.forEach((value) => {
                    const td = document.createElement('td');
                    td.textContent = value;
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });

            table.appendChild(thead);
            table.appendChild(tbody);
            return table;
        };

        // Export PDF
        const exportPdf = function() {
            const element = document.getElementById('portfolioReport');
            if (!element) {
                setExportStatus('No report content is available to export.', 'warning');
                return;
            }

            if (typeof window.html2pdf !== 'function') {
                setExportStatus('PDF export is unavailable, so the print dialog is opening instead.', 'warning');
                withReportActionsHidden((restore) => {
                    const afterPrint = function() {
                        restore();
                        window.removeEventListener('afterprint', afterPrint);
                    };
                    window.addEventListener('afterprint', afterPrint);
                    window.print();
                    setTimeout(afterPrint, 1200);
                });
                return;
            }

            const opt = {
                margin:       [0.5, 0.5, 0.5, 0.5],
                filename:     'interview_report.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };

            withReportActionsHidden((restore) => {
                setExportStatus('Preparing your PDF report...', 'info');
                html2pdf().set(opt).from(element).save().then(() => {
                    setExportStatus('PDF export started.', 'success');
                }).catch(() => {
                    setExportStatus('PDF export failed, so the print dialog is opening instead.', 'warning');
                    window.print();
                }).finally(() => {
                    restore();
                });
            });
        };
        document.querySelectorAll('.js-export-pdf').forEach((button) => {
            button.addEventListener('click', exportPdf);
        });

        // Export Excel
        const exportExcel = function() {
            const table = document.querySelector('#report-comparison table') || buildLatestScoreTable();
            if (table) {
                if (!window.XLSX) {
                    downloadCsvFromTable(table, 'interview_report_scores.csv');
                    setExportStatus('Excel library is unavailable, so a CSV score sheet was downloaded.', 'warning');
                    return;
                }
                const wb = XLSX.utils.table_to_book(table, {sheet: "Comparison"});
                XLSX.writeFile(wb, 'interview_report_scores.xlsx');
                setExportStatus('Excel export started.', 'success');
            } else {
                setExportStatus('No score data is available to export.', 'warning');
            }
        };
        document.querySelectorAll('.js-export-excel').forEach((button) => {
            button.addEventListener('click', exportExcel);
        });

        document.querySelectorAll('.js-print-report').forEach((button) => {
            button.addEventListener('click', function() {
                window.print();
            });
        });
    });
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#report-readiness', popover: { title: 'Report Summary', description: 'See the final score, result level, interview type, date, duration, and question count.', side: 'bottom', align: 'start' }},
            { element: '#report-question-review', popover: { title: 'Question Analysis', description: 'Review each question with the answer, score, strength, feedback, and next improvement.', side: 'bottom', align: 'start' }},
            { element: '#report-improvements', popover: { title: 'Improvement Areas', description: 'Focus on repeated mistakes and the next fix for each one.', side: 'top', align: 'start' }},
            { element: '#report-export', popover: { title: 'Export Report', description: 'Download the report as PDF, Excel, CSV, or print it.', side: 'top', align: 'start' }},
            { element: '#report-empty-state', popover: { title: 'No Report Yet', description: 'Complete a scored interview to unlock reports and exports.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#report-readiness', popover: { title: 'Report Summary', description: 'See the final score, result level, interview type, date, duration, and question count.', side: 'bottom', align: 'start' }},
            { element: '#report-question-review', popover: { title: 'Question Analysis', description: 'Review each question with the answer, score, strength, feedback, and next improvement.', side: 'bottom', align: 'start' }},
            { element: '#report-improvements', popover: { title: 'Improvement Areas', description: 'Focus on repeated mistakes and the next fix for each one.', side: 'top', align: 'start' }},
            { element: '#report-export', popover: { title: 'Export Report', description: 'Download the report as PDF, Excel, CSV, or print it.', side: 'top', align: 'end' }},
            { element: '#report-empty-state', popover: { title: 'No Report Yet', description: 'Complete a scored interview to unlock reports and exports.', side: 'top', align: 'center' }}
        ];

        const filterTourSteps = (steps) => steps.filter((step) => document.querySelector(step.element));
        const visibleMobileSteps = filterTourSteps(stepsMobile);
        const visibleDesktopSteps = filterTourSteps(stepsDesktop);

        if (!visibleMobileSteps.length && !visibleDesktopSteps.length) return;

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
