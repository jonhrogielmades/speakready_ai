@extends('desktop.layouts.app')
@section('title', 'Philippines Interview Progress')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/progress.css?v=1') }}" data-page-style="user-progress">
@endpush

@section('content')

<div class="db-section active" id="sec-progress-tracking">
    <div class="progress-hero" id="progressModulesLikeHero">
        <div class="progress-hero-inner">
            <div class="progress-hero-copy">
                <div class="progress-hero-icon"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <h4 class="progress-hero-title text-gradient-primary">Philippines Interview Progress</h4>
                    <p class="progress-hero-subtitle">Track readiness growth across your Philippines practice scenarios.</p>
                </div>
            </div>
        </div>
        <svg class="progress-hero-art" viewBox="0 0 220 150" aria-hidden="true" role="img">
            <defs>
                <linearGradient id="progressArtPanel" x1="36" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE"/>
                    <stop offset="1" stop-color="#ECFEFF"/>
                </linearGradient>
                <linearGradient id="progressArtBlue" x1="54" y1="34" x2="168" y2="112" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#3B82F6"/>
                    <stop offset="1" stop-color="#06B6D4"/>
                </linearGradient>
            </defs>
            <rect x="31" y="21" width="158" height="108" rx="18" fill="url(#progressArtPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <path d="M54 105V52" stroke="#93C5FD" stroke-width="5" stroke-linecap="round"/>
            <path d="M54 105h113" stroke="#93C5FD" stroke-width="5" stroke-linecap="round"/>
            <path d="M65 92l25-28 27 16 38-43" fill="none" stroke="url(#progressArtBlue)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="90" cy="64" r="9" fill="#2563EB" stroke="#EFF6FF" stroke-width="4"/>
            <circle cx="117" cy="80" r="9" fill="#0EA5E9" stroke="#EFF6FF" stroke-width="4"/>
            <circle cx="155" cy="37" r="11" fill="#22C55E" stroke="#EFF6FF" stroke-width="4"/>
            <rect x="67" y="101" width="13" height="16" rx="5" fill="#60A5FA" opacity=".65"/>
            <rect x="93" y="91" width="13" height="26" rx="5" fill="#38BDF8" opacity=".75"/>
            <rect x="119" y="97" width="13" height="20" rx="5" fill="#818CF8" opacity=".65"/>
            <rect x="145" y="75" width="13" height="42" rx="5" fill="#22C55E" opacity=".75"/>
            <path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
            <path d="M194 28l10-10m-6 30l14-2M24 59l-11-7m18 55l-14 3" stroke="#38BDF8" stroke-width="5" stroke-linecap="round" opacity=".55"/>
        </svg>
    </div>
    <div class="progress-actions">
        <!-- Feature 15: Progress Reports -->
        <button class="btn btn-primary btn-shine progress-export-btn pdf" id="exportPdfBtn"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
        <button class="btn btn-success btn-shine progress-export-btn excel" id="exportExcelBtn"><i class="fa-solid fa-file-excel"></i> Export Excel</button>
    </div>

    <!-- Feature 9, 14: Top Stats (Streaks, Comparison) -->
    <div id="progress-stats" class="row g-4">
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="premium-panel progress-stat-card" style="--stat-accent:#f59e0b">
                <div class="progress-stat-icon"><i class="fa-solid fa-fire"></i></div>
                <div class="progress-stat-value">{{ $currentStreak }} {{ $currentStreak == 1 ? 'Day' : 'Days' }}</div>
                <div class="progress-stat-label">Current Streak</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.2s;">
            <div class="premium-panel progress-stat-card" style="--stat-accent:#ef4444">
                <div class="progress-stat-icon"><i class="fa-solid fa-fire-flame-curved"></i></div>
                <div class="progress-stat-value">{{ $longestStreak }} {{ $longestStreak == 1 ? 'Day' : 'Days' }}</div>
                <div class="progress-stat-label">Longest Streak</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.3s;">
            <div class="premium-panel progress-stat-card" style="--stat-accent:#16a34a">
                <div class="progress-stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="progress-stat-value">{{ $totalPracticeDays }} {{ $totalPracticeDays == 1 ? 'Day' : 'Days' }}</div>
                <div class="progress-stat-label">Total Practice</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.4s;">
            <div class="premium-panel progress-stat-card" style="--stat-accent:#2563eb">
                <div class="progress-stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <div class="progress-stat-value">{{ $readinessMovement?->label ?? 'N/A' }}</div>
                <div class="progress-stat-label">VS Last</div>
            </div>
        </div>
    </div>

    <!-- Feature 13: AI Progress Insights -->
    @if($readinessMovement)
        <div id="ai-insights" class="alert border-0 animate-fade-up progress-ai-insight" style="animation-delay: 0.5s;">
            <div class="progress-ai-content">
                <div class="progress-ai-icon"><i class="fa-solid fa-robot"></i></div>
                <div>
                    <h6 class="progress-ai-title">AI Progress Insights</h6>
                    <p class="progress-ai-text">Your overall readiness score {!! $readinessMovement->trend_html !!} recently. <br>
                    <strong>Recommended Next Step:</strong> Review your latest Philippines interview feedback and rehearse the weakest answer again.</p>
                </div>
                <div class="progress-ai-art"><i class="fa-solid fa-brain"></i></div>
            </div>
        </div>
    @elseif($scoredSessions->count() === 1)
        <div id="ai-insights" class="alert border-0 animate-fade-up progress-ai-insight" style="animation-delay: 0.5s;">
            <div class="progress-ai-content">
                <div class="progress-ai-icon"><i class="fa-solid fa-robot"></i></div>
                <div>
                    <h6 class="progress-ai-title">AI Progress Insights</h6>
                    <p class="progress-ai-text">Complete one more scored Philippines practice interview to compare readiness movement accurately.</p>
                </div>
                <div class="progress-ai-art"><i class="fa-solid fa-brain"></i></div>
            </div>
        </div>
    @else
        <div id="ai-insights" class="alert border-0 animate-fade-up progress-ai-insight" style="animation-delay: 0.5s;">
            <div class="progress-ai-content">
                <div class="progress-ai-icon"><i class="fa-solid fa-robot"></i></div>
                <div>
                    <h6 class="progress-ai-title">AI Progress Insights</h6>
                    <p class="progress-ai-text">Complete at least 2 Philippines practice interviews to generate personalized progress insights.</p>
                </div>
                <div class="progress-ai-art"><i class="fa-solid fa-brain"></i></div>
            </div>
        </div>
    @endif

    <div class="row animate-fade-up" id="personalized-practice-plan" style="animation-delay: 0.55s;">
        <div class="col-12">
            <div class="premium-panel practice-plan-panel">
                <div class="practice-plan-heading">
                    <div class="practice-plan-heading-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div>
                        <h5 class="practice-plan-heading-title">Personalized Practice Plan</h5>
                        <p class="practice-plan-heading-text">Daily actions based on your latest scores, voice rehearsal, and module progress.</p>
                        <span class="practice-plan-pill mt-2"><i class="fa-solid fa-clock"></i> {{ isset($practicePlan) ? $practicePlan->sum('minutes') : 0 }} min total</span>
                    </div>
                </div>
                @if(isset($practicePlan) && $practicePlan->count() > 0)
                    <div class="practice-plan-list">
                        @foreach($practicePlan as $item)
                            <a href="{{ $item->url }}" class="practice-plan-row" style="--plan-color: {{ $item->color }};">
                                <div class="practice-plan-icon"><i class="fa-solid {{ $item->icon }}"></i></div>
                                <div class="practice-plan-copy">
                                    <div class="practice-plan-top">
                                        <span class="practice-plan-step">{{ $item->day }}</span>
                                        <span class="practice-plan-title">{{ $item->title }}</span>
                                    </div>
                                    <div class="practice-plan-text">{{ $item->action }}</div>
                                    <div class="practice-plan-text mt-1">{{ $item->reason }}</div>
                                    <ul class="practice-plan-tasks">
                                        @foreach(array_slice((array) ($item->tasks ?? []), 0, 2) as $task)
                                            <li><i class="fa-solid fa-check" style="color:#10b981;margin-top:2px;"></i><span>{{ $task }}</span></li>
                                        @endforeach
                                    </ul>
                                    <div class="practice-plan-footer">
                                        <span class="practice-plan-pill"><i class="fa-regular fa-clock"></i> {{ $item->minutes }} min</span>
                                        <span class="practice-plan-link">{{ $item->cta }} <i class="fa-solid fa-arrow-right ms-1"></i></span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4" style="color:var(--tx3);">
                        <i class="fa-solid fa-calendar-check fs-2 mb-3" style="color:var(--bd);"></i>
                        <p>Complete a Philippines practice interview or voice rehearsal to generate your plan.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 1: Readiness Score Trend -->
        <div class="col-12 animate-fade-up" id="readiness-trend" style="animation-delay: 0.6s;">
            <div class="premium-panel progress-chart-panel" style="height:100%; --panel-accent:#2563eb;">
                <div class="progress-panel-heading">
                    <div class="progress-panel-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <div>
                        <h5 class="progress-panel-title">Overall Readiness Trend</h5>
                        <p class="progress-panel-subtitle">Track your overall interview readiness over time.</p>
                    </div>
                </div>
                <div class="progress-chart-frame">
                    @if($scoreTrend->isNotEmpty())
                        <canvas id="readinessChart"></canvas>
                    @else
                        <div class="progress-chart-empty">
                            <i class="fa-solid fa-chart-line"></i>
                            <h6>No readiness trend yet</h6>
                            <p>Complete a scored Philippines practice interview to start charting your growth.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <!-- Feature 3: Scenario Performance Analysis -->
        <div class="col-12 animate-fade-up" id="category-perf" style="animation-delay: 0.7s;">
            <div class="premium-panel progress-chart-panel" style="height:100%; --panel-accent:#10b981;">
                <div class="progress-panel-heading">
                    <div class="progress-panel-icon"><i class="fa-solid fa-crosshairs"></i></div>
                    <div>
                        <h5 class="progress-panel-title">Scenario Performance</h5>
                        <p class="progress-panel-subtitle">Your average scores across different interview scenarios.</p>
                    </div>
                </div>
                <div class="progress-chart-frame scenario">
                    @if(count($categoryPerf) > 0)
                        <canvas id="categoryChart"></canvas>
                    @else
                        <div class="progress-chart-empty">
                            <i class="fa-solid fa-crosshairs"></i>
                            <h6>No scenario performance yet</h6>
                            <p>Your scored scenario averages appear here after completed practice sessions.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 4: Skill Improvement Tracker -->
        <div class="col-12 animate-fade-up" id="skill-tracker" style="animation-delay: 0.8s;">
            <div class="premium-panel progress-chart-panel" style="height:100%; --panel-accent:#8b5cf6;">
                <div class="progress-panel-heading">
                    <div class="progress-panel-icon"><i class="fa-solid fa-chart-simple"></i></div>
                    <div>
                        <h5 class="progress-panel-title">Skill Improvement Tracker</h5>
                        <p class="progress-panel-subtitle">Track your progress in key interview skills.</p>
                    </div>
                </div>
                
                @if(count($skillComparison) > 0)
                @foreach($skillComparison as $metric)
                <div class="skill-metric-row">
                    <div class="skill-metric-top">
                        <span class="skill-metric-label">{{ $metric['label'] }}</span>
                        <span class="skill-metric-value">{{ $metric['previous'] }}% <i class="fa-solid fa-arrow-right mx-1" style="font-size:0.8em"></i> {{ $metric['current'] }}%
                        @if($metric['delta'] >= 0)
                            <span class="text-success ms-1">(+{{ $metric['delta'] }}%)</span>
                        @else
                            <span class="text-danger ms-1">({{ $metric['delta'] }}%)</span>
                        @endif
                        </span>
                    </div>
                    <div class="skill-metric-bar">
                        <div class="skill-metric-fill" role="progressbar" style="width: {{ $metric['bar'] }}%;"></div>
                    </div>
                </div>
                @endforeach
                @else
                    <div class="skill-empty-state">
                        <div>
                            <div class="skill-empty-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                            <p class="skill-empty-text">Complete multiple Philippine practice interviews to track your specific skill improvements.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Feature 12: Strengths & Areas for Improvement -->
        <div class="col-12 animate-fade-up" id="strengths-tracker" style="animation-delay: 0.9s;">
            <div class="premium-panel strengths-star-panel" style="height:100%; --panel-accent:#7c3aed;">
                @php
                    $strengths = $latestSkillSummary->strengths ?: ['None identified yet'];
                    $weaknesses = $latestSkillSummary->weaknesses ?: ['None identified yet'];
                @endphp
                <div class="strengths-overview">
                    <div class="strengths-icon"><i class="fa-solid fa-star"></i></div>
                    <div>
                        <h5 class="strengths-title">Strengths & Areas for Improvement</h5>
                        @if($latestSkillSummary->has_data)
                            <div class="strengths-lists">
                                <div class="strengths-list-card">
                                    <h6 class="text-success"><i class="fa-solid fa-arrow-trend-up me-2"></i>Strengths</h6>
                                    <ul>
                                        @foreach(array_slice($strengths, 0, 3) as $str)
                                            <li><i class="fa-solid fa-check text-success me-2"></i>{{ $str }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="strengths-list-card">
                                    <h6 class="text-warning"><i class="fa-solid fa-arrow-trend-down me-2"></i>Needs Work</h6>
                                    <ul>
                                        @foreach(array_slice($weaknesses, 0, 3) as $wk)
                                            <li><i class="fa-solid fa-xmark text-warning me-2"></i>{{ $wk }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @else
                            <p class="strengths-text">Complete an interview to see strengths and areas for improvement.</p>
                        @endif
                    </div>
                </div>

                <!-- Feature 7: STAR Method Progress -->
                <div class="star-overview">
                    <div class="star-icon"><i class="fa-solid fa-bullseye"></i></div>
                    <div>
                        <h5 class="star-title">STAR Method Progress</h5>
                        @if($starProgress->has_data)
                            <p class="star-text">{{ $starProgress->message }}</p>
                            <div class="star-progress-summary">
                                <div class="star-progress-score">
                                    <span>{{ $starProgress->overall_percent }}%</span>
                                    <small>STAR coverage</small>
                                </div>
                                <div class="star-part-list">
                                    @foreach($starProgress->parts as $part)
                                        <div class="star-part-row">
                                            <div class="star-part-top">
                                                <span>{{ $part->label }}</span>
                                                <span>{{ $part->percent === null ? 'N/A' : $part->percent . '%' }}</span>
                                            </div>
                                            <div class="star-part-track">
                                                <div class="star-part-fill" style="--star-progress: {{ $part->percent ?? 0 }}%;"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="star-text">Insufficient data to analyze your STAR Method usage. Keep practicing behavioral questions!</p>
                        @endif
                    </div>
                </div>
                <div class="star-note">
                    <div class="star-note-icon"><i class="fa-regular fa-lightbulb"></i></div>
                    <div>
                        <h6 class="star-note-title">{{ $starProgress->has_data ? 'Next STAR Step' : 'What is STAR Method?' }}</h6>
                        <p class="star-note-text">{{ $starProgress->has_data ? $starProgress->suggestion : 'STAR stands for Situation, Task, Action, Result. It helps you structure strong and impactful answers.' }}</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Feature 2: Interview Performance History -->
        <div class="col-12 animate-fade-up" id="history-table" style="animation-delay: 1s;">
            <div class="premium-panel history-panel" style="--panel-accent:#4f46e5;">
                <div class="history-top">
                    <div class="history-heading">
                        <div class="history-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <h5 class="history-title">Interview Performance History</h5>
                    </div>
                    @if($sessions->count() > 0)
                    <div class="history-actions">
                        <form action="{{ route('user.sessions.clear') }}" method="POST" onsubmit="return confirm('Clear all completed interview sessions? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger history-clear-btn">
                                <i class="fa-solid fa-trash-can me-1"></i> Clear All
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                <label class="history-search" for="historySearch">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="historySearch" placeholder="Search history...">
                </label>
                <div class="history-list">
                    @foreach($sessions as $session)
                        @php $sc = $session->score ? $session->score->overall_readiness_score : null; @endphp
                        <article class="history-card" data-history-record>
                            <div class="history-date"><i class="fa-regular fa-calendar-days"></i>{{ $session->created_at->format('M d, Y') }}</div>
                            <h6 class="history-scenario">{{ $session->practice_scenario ?? 'General Job Interview' }}</h6>
                            <div class="history-meta">
                                <div>Score:
                                    @if($session->score)
                                        <span class="history-score-value">{{ $session->score->overall_readiness_score }}%</span>
                                    @else
                                        <span class="history-rating-badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Score pending</span>
                                    @endif
                                </div>
                                <div>Rating:
                                    @if($sc === null) <span class="history-rating-badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Not scored</span>
                                    @elseif($sc >= 90) <span class="history-rating-badge" style="background: rgba(16, 185, 129, 0.16); color: #059669;">Excellent</span>
                                    @elseif($sc >= 70) <span class="history-rating-badge" style="background: rgba(59, 130, 246, 0.16); color: #2563eb;">Good</span>
                                    @elseif($sc >= 50) <span class="history-rating-badge" style="background: rgba(245, 158, 11, 0.18); color: #d97706;">Average</span>
                                    @else <span class="history-rating-badge" style="background: rgba(239, 68, 68, 0.16); color: #ef334e;">Needs Work</span>
                                    @endif
                                </div>
                            </div>
                            <div class="history-card-actions">
                                <a href="{{ route('user.review', $session->id) }}" class="btn btn-outline-primary history-feedback-btn"><i class="fa-regular fa-message"></i> View Feedback</a>
                                <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger history-delete-btn" title="Delete session">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                    @if($sessions->count() == 0)
                        <div class="skill-empty-state">
                            <p class="skill-empty-text">No interview records found. Start a Philippines practice interview to track your progress.</p>
                        </div>
                    @endif
                </div>
                <div class="skill-empty-state history-no-results" id="historyNoResults" hidden>
                    <div>
                        <div class="skill-empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <p class="skill-empty-text">No history records match your search.</p>
                    </div>
                </div>
                <div class="table-responsive d-none">
                    <table class="table custom-table align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent;">
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
                            <tr style="border-bottom: 1px solid var(--bd);">
                                <td class="border-0 py-3">{{ $session->created_at->format('M d, Y') }}</td>
                                <td class="border-0 py-3 fw-bold">{{ $session->practice_scenario ?? 'General Job Interview' }}</td>
                                <td class="border-0 py-3">
                                    @if($session->score)
                                        {{ $session->score->overall_readiness_score }}%
                                    @else
                                        <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Score pending</span>
                                    @endif
                                </td>
                                <td class="border-0 py-3">
                                    @php $sc = $session->score ? $session->score->overall_readiness_score : null; @endphp
                                    @if($sc === null) <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Not scored</span>
                                    @elseif($sc >= 90) <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">Excellent</span>
                                    @elseif($sc >= 70) <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span>
                                    @elseif($sc >= 50) <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">Average</span>
                                    @else <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">Needs Work</span>
                                    @endif
                                </td>
                                <td class="border-0 py-3 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">View Feedback</a>
                                        <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete session" style="border-radius:8px;">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @if($sessions->count() == 0)
                            <tr>
                                <td colspan="5" class="text-center py-4" style="color:var(--tx3);font-style:italic;">No interview records found. Start a Philippines practice interview to track your progress.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 5: Learning Progress Tracking -->
        <div class="col-12" id="learning-progress">
            <div class="learning-panel" style="--panel-accent:#6d5dfc;">
                <div class="learning-heading">
                    <div class="learning-heading-icon"><i class="fa-solid fa-book-open"></i></div>
                    <div>
                        <h5 class="learning-title">Learning Progress</h5>
                        <p class="learning-subtitle">Track your module progress and keep learning.</p>
                    </div>
                </div>
                <div class="learning-list">
                @forelse($learningProgress as $lp)
                @php
                    $moduleColor = ($lp->progress_percentage >= 100) ? '#10b981' : '#6d5dfc';
                    $moduleIcon = ($lp->progress_percentage >= 100) ? 'fa-bullhorn' : 'fa-book-open';
                @endphp
                <div class="learning-module" style="--module-color: {{ $moduleColor }};">
                    <div class="learning-module-icon"><i class="fa-solid {{ $moduleIcon }}"></i></div>
                    <div>
                        <div class="learning-module-top">
                            <div class="learning-module-title">{{ $lp->learningModule ? $lp->learningModule->title : 'Module' }}</div>
                            <div class="learning-percent">{{ $lp->progress_percentage }}%</div>
                        </div>
                        <div class="learning-track">
                            <div class="learning-fill" style="--learning-progress: {{ max(0, min(100, (int) $lp->progress_percentage)) }}%;"></div>
                        </div>
                    </div>
                </div>
                @empty
                    <div class="skill-empty-state">
                        <div>
                            <div class="skill-empty-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                            <p class="skill-empty-text">No learning progress recorded yet.</p>
                        </div>
                    </div>
                @endforelse
                </div>
                
                @if($learningProgress->count() > 0)
                <div class="learning-summary">
                    <div class="learning-summary-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div>
                        <div class="learning-summary-value">{{ round($learningProgress->avg('progress_percentage') ?? 0) }}%</div>
                        <div class="learning-summary-label">Overall Learning Completion Rate</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if(isset($moduleRecommendations) && $moduleRecommendations->count() > 0)
        <div class="col-12" id="recommended-next">
            <div class="recommend-panel" style="--panel-accent:#f59e0b;">
                <div class="recommend-heading">
                    <div class="recommend-heading-icon"><i class="fa-solid fa-lightbulb"></i></div>
                    <div>
                        <h5 class="recommend-title">Recommended Next</h5>
                        <p class="recommend-subtitle">Based on your performance and progress.</p>
                    </div>
                </div>
                <div class="recommend-list">
                @foreach($moduleRecommendations as $recommendation)
                    <a href="{{ $recommendation->url }}" class="recommend-item" style="--panel-accent: {{ $recommendation->color }};">
                        <div class="recommend-item-icon"><i class="fa-solid {{ $recommendation->icon }}"></i></div>
                        <div>
                            <div class="recommend-item-title">{{ $recommendation->module->title }}</div>
                            <div class="recommend-item-text">{{ $recommendation->reason }}</div>
                        </div>
                        <div class="recommend-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                    </a>
                @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Feature 6: Voice Rehearsal Progress -->
        <div class="col-12" id="voice-progress">
            <div class="voice-panel" style="--panel-accent:#ec407a;">
                <div class="voice-heading">
                    <div class="voice-heading-icon"><i class="fa-solid fa-microphone"></i></div>
                    <div>
                        <h5 class="voice-title">Voice Rehearsal Progress</h5>
                        <p class="voice-subtitle">Improvements start with practice.</p>
                    </div>
                </div>
                @if($voiceSummary->latest)
                    @php $latestVoice = $voiceSummary->latest; $prevVoice = $voiceSummary->previous; @endphp
                    <div class="row text-center mb-4">
                        <div class="col-4 border-end" style="border-color:var(--bd) !important;">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ $latestVoice->speaking_pace ?? $latestVoice->wpm ?? 'N/A' }}</h3>
                            <small style="color:var(--tx3)">Pace (wpm)</small>
                        </div>
                        <div class="col-4 border-end" style="border-color:var(--bd) !important;">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ is_numeric($latestVoice->clarity_score) ? $latestVoice->clarity_score . '%' : 'N/A' }}</h3>
                            <small style="color:var(--tx3)">Clarity</small>
                        </div>
                        <div class="col-4">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ is_numeric($latestVoice->confidence_score) ? $latestVoice->confidence_score . '%' : 'N/A' }}</h3>
                            <small style="color:var(--tx3)">Delivery Stability</small>
                        </div>
                    </div>
                    
                    <div class="p-3" style="background: rgba(16, 185, 129, 0.1); border-radius: 12px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-success mb-1 fw-bold">Filler Word Change</h6>
                                @if($prevVoice)
                                    <small style="color:var(--tx)">Previous: <strong>{{ $prevVoice->filler_words ?? 0 }}</strong> | Current: <strong>{{ $latestVoice->filler_words ?? 0 }}</strong></small>
                                @else
                                    <small style="color:var(--tx)">Complete another voice rehearsal to compare filler word movement.</small>
                                @endif
                            </div>
                            <h2 class="text-success mb-0 fw-bold">{{ $voiceSummary->filler_reduction === null ? 'N/A' : (($voiceSummary->filler_reduction > 0 ? '+' : '') . $voiceSummary->filler_reduction . '%') }}</h2>
                        </div>
                    </div>
                @else
                    <div class="voice-empty">
                        <div>
                            <div class="voice-empty-icon"><i class="fa-solid fa-microphone"></i></div>
                            <h6 class="voice-empty-title">No voice rehearsal data available yet.</h6>
                            <p class="voice-empty-text">Start a voice rehearsal to track your clarity, pace, and delivery over time.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Feature 8: Practice Activity Calendar -->
        <div class="col-12" id="activity-calendar">
            <div class="activity-panel" style="--panel-accent:#6d5dfc;">
                <div class="activity-heading">
                    <div class="activity-heading-icon"><i class="fa-regular fa-calendar"></i></div>
                    <div>
                        <h5 class="activity-title">Practice Activity Calendar</h5>
                        <p class="activity-subtitle">Last 28 days across interviews and voice drills.</p>
                    </div>
                </div>
                @if($activityCalendar->range_active_days > 0)
                    <div class="activity-summary-grid">
                        <div class="activity-summary-item">
                            <strong>{{ $activityCalendar->range_active_days }}</strong>
                            <span>Active days</span>
                        </div>
                        <div class="activity-summary-item">
                            <strong>{{ $activityCalendar->recent_active_days }}</strong>
                            <span>This week</span>
                        </div>
                        <div class="activity-summary-item">
                            <strong>{{ $activityCalendar->current_streak }}</strong>
                            <span>Activity streak</span>
                        </div>
                        <div class="activity-summary-item">
                            <strong>{{ $activityCalendar->last_activity_label }}</strong>
                            <span>Latest practice</span>
                        </div>
                    </div>
                    <div class="activity-grid" role="list" aria-label="Last 28 days practice activity">
                        @foreach($activityCalendar->days as $day)
                            <div class="activity-day {{ $day->total > 0 ? 'active' : '' }} {{ $day->is_today ? 'today' : '' }}"
                                role="listitem"
                                title="{{ $day->tooltip }}"
                                aria-label="{{ $day->tooltip }}"
                                style="--activity-intensity: {{ $day->intensity }}%;">
                                <span class="activity-day-week">{{ $day->weekday }}</span>
                                <span class="activity-day-number">{{ $day->day_number }}</span>
                                @if($day->total > 0)
                                    <span class="activity-day-dot">{{ $day->total }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="activity-legend">
                        <span><i></i>Practice recorded</span>
                        <a href="{{ route('interview.setup') }}" class="btn btn-outline-primary activity-cta compact"><i class="fa-solid fa-play"></i> Practice Again</a>
                    </div>
                @else
                <div class="activity-empty">
                    <svg class="activity-illustration" viewBox="0 0 520 260" aria-hidden="true" role="img">
                        <defs>
                            <linearGradient id="activityCalTop" x1="120" y1="50" x2="400" y2="202" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#8B5CF6"/>
                                <stop offset="1" stop-color="#C4B5FD"/>
                            </linearGradient>
                        </defs>
                        <ellipse cx="260" cy="218" rx="210" ry="18" fill="#ede9fe"/>
                        <circle cx="260" cy="130" r="118" fill="#ede9fe" opacity=".75"/>
                        <path d="M116 180c34-18 52-49 43-91 34 39 37 72 6 101" fill="#c4b5fd" opacity=".7"/>
                        <path d="M404 190c-22-42-10-75 34-103 8 48-4 82-34 103z" fill="#c4b5fd" opacity=".7"/>
                        <rect x="162" y="72" width="196" height="148" rx="18" fill="#fff" stroke="#ddd6fe" stroke-width="3"/>
                        <path d="M162 96c0-13 11-24 24-24h148c13 0 24 11 24 24v26H162V96z" fill="url(#activityCalTop)"/>
                        <path d="M198 58v34M260 58v34M322 58v34" stroke="#37306b" stroke-width="13" stroke-linecap="round"/>
                        @for($row = 0; $row < 3; $row++)
                            @for($col = 0; $col < 6; $col++)
                                <rect x="{{ 194 + ($col * 28) }}" y="{{ 144 + ($row * 31) }}" width="22" height="22" rx="5" fill="#ede9fe" opacity=".75"/>
                            @endfor
                        @endfor
                        <rect x="278" y="172" width="30" height="30" rx="7" fill="#6d5dfc"/>
                        <path d="M286 187l6 6 11-14" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="118" cy="91" r="6" fill="#c4b5fd"/>
                        <circle cx="76" cy="158" r="8" fill="#c4b5fd"/>
                        <circle cx="432" cy="132" r="7" fill="#c4b5fd"/>
                        <path d="M380 86l8 16 16 8-16 8-8 16-8-16-16-8 16-8 8-16z" fill="#a78bfa"/>
                    </svg>
                    <h6 class="activity-empty-title">Complete your first Philippines practice interview</h6>
                    <p class="activity-empty-text">to start tracking your daily practice activity.</p>
                    <a href="{{ route('interview.setup') }}" class="btn btn-outline-primary activity-cta"><i class="fa-solid fa-play"></i> Start Practice</a>
                </div>
                @endif
            </div>
        </div>

        <!-- Feature 10: Goals & Milestones -->
        <div class="col-12" id="goals-milestones">
            <div class="goals-panel" style="--panel-accent:#10b981;">
                <div class="goals-heading">
                    <div class="goals-heading-icon"><i class="fa-solid fa-bullseye"></i></div>
                    <div>
                        <h5 class="goals-title">Goals & Milestones</h5>
                        <p class="goals-subtitle">Track your progress and reach your interview goals.</p>
                    </div>
                </div>
                @forelse($goals as $goal)
                <div class="goal-row">
                    <div class="goal-top">
                        <span class="goal-title">{{ $goal->title }}</span>
                        <span class="goal-percent">{{ $goal->progress }}%</span>
                    </div>
                    <div class="goal-track">
                        <div class="goal-fill" style="--goal-progress: {{ max(0, min(100, (int) $goal->progress)) }}%;"></div>
                    </div>
                </div>
                @empty
                    <div class="goal-row">
                        <div class="goal-top">
                            <span class="goal-title">Complete your first scored interview</span>
                            <span class="goal-percent">0%</span>
                        </div>
                        <div class="goal-track">
                            <div class="goal-fill" style="--goal-progress: 0%;"></div>
                        </div>
                    </div>
                @endforelse
                <div class="goal-note">
                    <div class="goal-note-icon"><i class="fa-solid {{ $goalNote->icon }}"></i></div>
                    <div>
                        <div class="goal-note-title">{{ $goalNote->title }}</div>
                        <div class="goal-note-text">{{ $goalNote->text }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature 11: Achievements & Badges -->
        <div class="col-12" id="achievements-badges">
            <div class="badges-panel" style="--panel-accent:#f59e0b;">
                <div class="badges-heading">
                    <div class="badges-heading-icon"><i class="fa-solid fa-trophy"></i></div>
                    <div>
                        <h5 class="badges-title">Achievements & Badges</h5>
                        <p class="badges-subtitle">Celebrate your progress and stay motivated.</p>
                    </div>
                </div>
                <div class="badge-grid">
                    @forelse($badges as $badge)
                    <div class="badge-item {{ $badge->unlocked ? '' : 'locked' }}">
                        <div class="badge-medal">
                            <i class="fa-solid {{ $badge->icon }}"></i>
                        </div>
                        <div class="badge-title">{{ $badge->title }}</div>
                        <div class="badge-desc">{{ $badge->description ?? ($badge->unlocked ? 'Unlocked' : 'Keep practicing') }}</div>
                    </div>
                    @empty
                    <div class="badge-item">
                        <div class="badge-medal"><i class="fa-solid fa-medal"></i></div>
                        <div class="badge-title">First Interview</div>
                        <div class="badge-desc">Complete 1 interview</div>
                    </div>
                    <div class="badge-item">
                        <div class="badge-medal"><i class="fa-solid fa-fire"></i></div>
                        <div class="badge-title">3-Day Streak</div>
                        <div class="badge-desc">Practice 3 days in a row</div>
                    </div>
                    <div class="badge-item">
                        <div class="badge-medal"><i class="fa-solid fa-star"></i></div>
                        <div class="badge-title">STAR Master</div>
                        <div class="badge-desc">Use STAR effectively</div>
                    </div>
                    <div class="badge-item locked">
                        <div class="badge-medal"><i class="fa-solid fa-bullhorn"></i></div>
                        <div class="badge-title">Top Comm</div>
                        <div class="badge-desc">Top communicator</div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/chart.umd.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            // If bootstrap is available
            if(typeof bootstrap !== 'undefined') {
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                });
            }

            const trendData = @json($scoreTrend);
            const scenarioPerformance = @json($categoryPerf);
            const progressCharts = [];
            const previousChartColorUpdater = window.updateChartColors;
            const progressThemeColors = () => {
                const isLight = document.documentElement.dataset.theme === 'light' || document.documentElement.classList.contains('lm');
                return {
                    tick: isLight ? '#1e2f50' : '#cbd5e1',
                    grid: isLight ? 'rgba(148, 163, 184, 0.24)' : 'rgba(148, 163, 184, 0.18)',
                    border: isLight ? 'rgba(148, 163, 184, 0.35)' : 'rgba(148, 163, 184, 0.22)'
                };
            };
            const applyProgressChartTheme = (chart) => {
                if (!chart) return;
                const colors = progressThemeColors();
                if (chart.options.scales?.y) {
                    chart.options.scales.y.ticks.color = colors.tick;
                    chart.options.scales.y.grid.color = colors.grid;
                }
                if (chart.options.scales?.x) {
                    chart.options.scales.x.ticks.color = colors.tick;
                    chart.options.scales.x.border.color = colors.border;
                }
                chart.update('none');
            };
            
            // Feature 1: Readiness Trend
            const labels = trendData.map(s => s.date);
            const scores = trendData.map(s => s.score);
            
            if(window.Chart && document.getElementById('readinessChart')) {
                const readinessCanvas = document.getElementById('readinessChart');
                const readinessGradient = readinessCanvas.getContext('2d').createLinearGradient(0, 0, 0, 340);
                readinessGradient.addColorStop(0, 'rgba(37, 99, 235, 0.18)');
                readinessGradient.addColorStop(1, 'rgba(37, 99, 235, 0.02)');

                const readinessChart = new Chart(readinessCanvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Readiness Score',
                            data: scores,
                            borderColor: '#2563eb',
                            backgroundColor: readinessGradient,
                            borderWidth: 3,
                            tension: 0.34,
                            fill: true,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        elements: { line: { capBezierPoints: true } },
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                max: 100,
                                ticks: { color: progressThemeColors().tick, stepSize: 10, padding: 12 },
                                border: { display: false },
                                grid: { color: progressThemeColors().grid, borderDash: [4, 5], drawTicks: false }
                            },
                            x: {
                                ticks: { color: progressThemeColors().tick, maxRotation: 0, autoSkipPadding: 16 },
                                border: { color: progressThemeColors().border },
                                grid: { display: false }
                            }
                        }
                    }
                });
                progressCharts.push(readinessChart);
            }

            // Feature 3: Scenario Performance
            if(window.Chart && document.getElementById('categoryChart')) {
                const scenarioLabels = Object.keys(scenarioPerformance);
                const scenarioData = Object.values(scenarioPerformance);

                const categoryChart = new Chart(document.getElementById('categoryChart'), {
                    type: 'bar',
                    data: {
                        labels: scenarioLabels,
                        datasets: [{
                            label: 'Avg Score',
                            data: scenarioData,
                            backgroundColor: [
                                '#3b82f6',
                                '#10b981',
                                '#8b5cf6',
                                '#fb923c'
                            ],
                            borderRadius: 4,
                            maxBarThickness: 96
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                max: 100,
                                ticks: { color: progressThemeColors().tick, stepSize: 10, padding: 12 },
                                border: { display: false },
                                grid: { color: progressThemeColors().grid, borderDash: [4, 5], drawTicks: false }
                            },
                            x: {
                                ticks: { color: progressThemeColors().tick, maxRotation: 0, font: { weight: 500 } },
                                border: { color: progressThemeColors().border },
                                grid: { display: false }
                            }
                        }
                    }
                });
                progressCharts.push(categoryChart);
            }
            window.updateChartColors = function() {
                if (typeof previousChartColorUpdater === 'function') {
                    previousChartColorUpdater();
                }
                progressCharts.forEach(applyProgressChartTheme);
            };

            // Feature 2: Table Search Filter
            const searchInput = document.getElementById('historySearch');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = searchInput.value.toLowerCase();
                    const rows = document.querySelectorAll('#history-table table tbody tr');
                    const cards = document.querySelectorAll('#history-table [data-history-record]');
                    const noResults = document.getElementById('historyNoResults');
                    let visibleCards = 0;
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if(text.includes(filter)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    cards.forEach(card => {
                        const text = card.textContent.toLowerCase();
                        const isVisible = text.includes(filter);
                        card.style.display = isVisible ? '' : 'none';
                        if (isVisible) {
                            visibleCards++;
                        }
                    });
                    if (noResults) {
                        noResults.hidden = filter.length === 0 || cards.length === 0 || visibleCards > 0;
                    }
                });
            }

            const progressPage = document.querySelector('.db-section');
            const temporaryExportHiding = (callback) => {
                if (!progressPage) {
                    callback();
                    return;
                }

                const controls = progressPage.querySelectorAll('button, input');
                const originalDisplays = [];
                controls.forEach(control => {
                    originalDisplays.push(control.style.display);
                    control.style.display = 'none';
                });

                const restore = () => {
                    controls.forEach((control, index) => {
                        control.style.display = originalDisplays[index];
                    });
                };

                try {
                    callback(restore);
                } catch (error) {
                    restore();
                    throw error;
                }
            };

            const cloneHistoryTableWithoutActions = () => {
                const table = document.querySelector('#history-table table');
                if (!table) return null;

                const clonedTable = table.cloneNode(true);
                const headers = clonedTable.querySelectorAll('th');
                if (headers.length > 0) headers[headers.length - 1].remove();

                clonedTable.querySelectorAll('tbody tr').forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length > 0) cells[cells.length - 1].remove();
                });

                return clonedTable;
            };

            const csvEscape = (value) => {
                const text = String(value ?? '').replace(/\s+/g, ' ').trim();
                return `"${text.replace(/"/g, '""')}"`;
            };

            const downloadCsvFromTable = (table) => {
                const rows = Array.from(table.querySelectorAll('tr'))
                    .map(row => Array.from(row.querySelectorAll('th,td')).map(cell => csvEscape(cell.textContent)).join(','))
                    .join('\n');
                const blob = new Blob([rows], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'interview_history.csv';
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
            };

            // Export PDF
            const exportPdfBtn = document.getElementById('exportPdfBtn');
            if (exportPdfBtn) {
                exportPdfBtn.addEventListener('click', function() {
                    const element = progressPage;
                    if (!element) {
                        return;
                    }

                    if (typeof window.html2pdf !== 'function') {
                        temporaryExportHiding((restore) => {
                            const afterPrint = () => {
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
                        filename:     'progress_report.pdf',
                        image:        { type: 'jpeg', quality: 0.98 },
                        html2canvas:  { scale: 2, useCORS: true },
                        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
                    };

                    temporaryExportHiding((restore) => {
                        html2pdf().set(opt).from(element).save().catch(() => {
                            window.print();
                        }).finally(() => {
                            restore();
                        });
                    });
                });
            }

            // Export Excel
            const exportExcelBtn = document.getElementById('exportExcelBtn');
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', function() {
                    const table = cloneHistoryTableWithoutActions();
                    if (table) {
                        if (!window.XLSX) {
                            downloadCsvFromTable(table);
                            return;
                        }

                        const wb = XLSX.utils.table_to_book(table, {sheet: "History"});
                        XLSX.writeFile(wb, 'interview_history.xlsx');
                    }
                });
            }
        });
    </script>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#progress-stats', popover: { title: 'At A Glance', description: 'Review streak, practice days, and readiness movement without opening a report.', side: 'bottom', align: 'start' }},
            { element: '#ai-insights', popover: { title: 'AI Insights', description: 'Use trend-based coaching notes to decide what to practice next.', side: 'bottom', align: 'start' }},
            { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Track how your overall readiness score changes over time.', side: 'bottom', align: 'start' }},
            { element: '#category-perf', popover: { title: 'Scenario Breakdown', description: 'Compare Philippines practice scenarios to find strengths and weak spots.', side: 'top', align: 'start' }},
            { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Watch the core interview skills that are improving across sessions.', side: 'top', align: 'start' }},
            { element: '#strengths-tracker', popover: { title: 'Strengths & STAR', description: 'Review strengths, areas to improve, and STAR method progress.', side: 'top', align: 'start' }},
            { element: '#history-table', popover: { title: 'Session History', description: 'Open previous interviews and detailed AI feedback from one place.', side: 'top', align: 'start' }},
            { element: '#learning-progress', popover: { title: 'Learning Progress', description: 'See how much of your module work is complete.', side: 'top', align: 'start' }},
            { element: '#voice-progress', popover: { title: 'Voice Rehearsal', description: 'Check speaking pace, clarity, and delivery stability from voice drills.', side: 'top', align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Activity Calendar', description: 'Use the calendar to spot consistent practice days and gaps.', side: 'top', align: 'start' }},
            { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track progress toward platform goals and target outcomes.', side: 'top', align: 'start' }},
            { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards appear here as your practice history grows.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#progress-stats', popover: { title: 'At A Glance', description: 'Review streak, practice days, and readiness movement without opening a report.', side: 'bottom', align: 'start' }},
            { element: '#ai-insights', popover: { title: 'AI Insights', description: 'Use trend-based coaching notes to decide what to practice next.', side: 'bottom', align: 'start' }},
            { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Track how your overall readiness score changes over time.', side: 'bottom', align: 'start' }},
            { element: '#category-perf', popover: { title: 'Scenario Breakdown', description: 'Compare Philippines practice scenarios to find strengths and weak spots.', side: 'bottom', align: 'start' }},
            { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Watch the core interview skills that are improving across sessions.', side: 'right', align: 'start' }},
            { element: '#strengths-tracker', popover: { title: 'Strengths & STAR', description: 'Review strengths, areas to improve, and STAR method progress.', side: 'left', align: 'start' }},
            { element: '#history-table', popover: { title: 'Session History', description: 'Open previous interviews and detailed AI feedback from one place.', side: 'top', align: 'start' }},
            { element: '#learning-progress', popover: { title: 'Learning Progress', description: 'See how much of your module work is complete.', side: 'right', align: 'start' }},
            { element: '#voice-progress', popover: { title: 'Voice Rehearsal', description: 'Check speaking pace, clarity, and delivery stability from voice drills.', side: 'left', align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Activity Calendar', description: 'Use the calendar to spot consistent practice days and gaps.', side: 'top', align: 'start' }},
            { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track progress toward platform goals and target outcomes.', side: 'right', align: 'start' }},
            { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards appear here as your practice history grows.', side: 'left', align: 'start' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_progress',
            serverDetectedMobile: false,
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
