@extends('desktop.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/dashboard.css?v=15') }}" data-page-style="dashboard">
@endpush

@section('content')
@php
    $scoreVal = (int) round($profile->readiness_score ?? $avgScore ?? 0);
    $scoreVal = max(0, min(100, $scoreVal));
    $scoreClass = $scoreVal >= 80 ? 'score-high' : ($scoreVal >= 60 ? 'score-med' : 'score-low');
    $scoreText = $scoreVal >= 80 ? 'Interview Ready' : ($scoreVal >= 60 ? 'Building Momentum' : 'Practice Mode');
    $mobileScoreText = $scoreVal >= 80 ? 'Interview Ready' : 'Building Momentum';
    $scoreIcon = $scoreVal >= 80 ? 'fa-circle-check' : ($scoreVal >= 60 ? 'fa-chart-line' : 'fa-arrow-trend-up');
    $fullName = trim(Auth::user()->name ?? '') ?: 'User';
    $nameParts = preg_split('/\s+/', $fullName);
    $firstName = $nameParts[0] ?? 'User';
    $welcomeName = $firstName;
    $rating = round(($avgScore ?? 0) / 20, 1);
    $goalPercent = isset($upcomingGoal) ? max(0, min(100, round($upcomingGoal->percent ?? 0))) : 0;
    $categoryCount = isset($categoryPerformance) ? count($categoryPerformance) : 0;
    $moduleCount = isset($learningLabProgress) ? count($learningLabProgress) : 0;
    $sessionsMeter = max(0, min(100, (int) round((($totalSessions ?? 0) / 10) * 100)));
    $ratingMeter = max(0, min(100, (int) round(($rating / 5) * 100)));
    $xpValue = max(0, (int) ($experiencePoints ?? 0));
    $playerLevel = max(1, (int) ($profile->player_level ?? (floor($xpValue / 1000) + 1)));
    $xpMeter = max(0, min(100, (int) round((($xpValue % 1000) / 1000) * 100)));
    $streakMeter = max(0, min(100, (int) round((($currentStreak ?? 0) / 7) * 100)));
    $hasRadarScores = collect($radarData ?? [])->contains(fn ($score) => is_numeric($score) && (int) $score > 0);
    $trendScores = collect($scoreTrend ?? [])->pluck('score')->filter(fn ($score) => is_numeric($score))->map(fn ($score) => (int) round($score))->values();
    $trendAverage = $trendScores->isNotEmpty() ? (int) round($trendScores->avg()) : $scoreVal;
    $trendFirst = $trendScores->first();
    $trendLast = $trendScores->last();
    $trendImprovement = ($trendFirst !== null && $trendFirst > 0 && $trendLast !== null)
        ? (int) round((($trendLast - $trendFirst) / $trendFirst) * 100)
        : 0;
    $hasTrendScores = $trendScores->isNotEmpty();
    $trendSessionCount = $trendScores->count();
    if (! $hasTrendScores) {
        $trendNoteTitle = 'Start your trend';
        $trendNoteBody = 'Complete a scored interview to unlock your readiness trend.';
        $trendNoteIcon = 'fa-regular fa-compass';
    } elseif ($trendSessionCount < 2) {
        $trendNoteTitle = 'One score logged';
        $trendNoteBody = 'Complete one more scored session to compare your progress.';
        $trendNoteIcon = 'fa-regular fa-star';
    } elseif ($trendImprovement > 0) {
        $trendNoteTitle = 'Keep it up';
        $trendNoteBody = 'Your readiness is up '.$trendImprovement.'% across your latest scored sessions.';
        $trendNoteIcon = 'fa-solid fa-arrow-trend-up';
    } elseif ($trendImprovement < 0) {
        $trendNoteTitle = 'Practice focus';
        $trendNoteBody = 'Your latest scores dipped. Review feedback and try one focused session today.';
        $trendNoteIcon = 'fa-solid fa-bullseye';
    } else {
        $trendNoteTitle = 'Steady trend';
        $trendNoteBody = 'Your readiness is holding steady. Consistent practice will help move it higher.';
        $trendNoteIcon = 'fa-regular fa-star';
    }
    $goalNote = $goalPercent >= 100
        ? 'Target reached. Set your next readiness goal.'
        : ($goalPercent >= 75
            ? 'Almost there. A focused session can close the gap.'
            : ($goalPercent >= 40
                ? 'You are building momentum toward this goal.'
                : 'Start with one scored session to build momentum.'));
    $challengeTitle = $scoreVal >= 75 ? 'Sharpen 3 advanced PH answers' : 'Answer 3 Philippine HR questions';
    $challengeCopy = $scoreVal >= 75
        ? 'Polish role-fit stories, metrics, and confident closing answers.'
        : 'Practice structure, confidence, and local role-fit responses.';
    $challengeXp = $hasTrendScores ? 60 : 40;
    $dashboardAccentFallbacks = ['#3b82f6', '#22c55e', '#06b6d4', '#f59e0b', '#8b5cf6', '#ec4899', '#ef4444'];
    $safeAccent = static function ($value, string $fallback = '#3b82f6') use ($dashboardAccentFallbacks): string {
        $color = trim((string) $value);
        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return $color;
        }

        return in_array($fallback, $dashboardAccentFallbacks, true) ? $fallback : '#3b82f6';
    };
    $safeFaIcon = static function ($value, string $fallback = 'fa-clipboard-list'): string {
        $icon = trim((string) $value);

        return preg_match('/^fa-[a-z0-9-]+$/', $icon) ? $icon : $fallback;
    };
    $achievementCatalog = [
        [
            'name' => 'First Interview',
            'label' => 'First Interview',
            'icon' => 'fa-medal',
            'accent' => '#f59e0b',
            'earned' => (($totalSessions ?? 0) > 0) || in_array('First Interview', $badgesEarned ?? [], true),
            'status' => (($totalSessions ?? 0) > 0) ? 'Earned' : 'Start one',
        ],
        [
            'name' => '3-Day Streak',
            'label' => '3-Day Streak',
            'icon' => 'fa-fire',
            'accent' => '#ef4444',
            'earned' => (($currentStreak ?? 0) >= 3) || in_array('3-Day Streak', $badgesEarned ?? [], true),
            'status' => (($currentStreak ?? 0) >= 3) ? 'Earned' : max(0, (int) ($currentStreak ?? 0)).'/3 days',
        ],
        [
            'name' => 'STAR Master',
            'label' => 'STAR Master',
            'icon' => 'fa-star',
            'accent' => '#2563eb',
            'earned' => in_array('STAR Master', $badgesEarned ?? [], true),
            'status' => in_array('STAR Master', $badgesEarned ?? [], true) ? 'Earned' : 'In Progress',
        ],
        [
            'name' => 'Top Comm',
            'label' => 'Top Comm',
            'icon' => 'fa-bullhorn',
            'accent' => '#22c55e',
            'earned' => ($scoreVal >= 80) || in_array('Top Comm', $badgesEarned ?? [], true),
            'status' => ($scoreVal >= 80) ? 'Earned' : 'Locked',
        ],
    ];
@endphp

<div class="db-section active sr-dashboard" id="sec-overview">
    <div class="sr-summary-grid">
        <div class="sr-welcome-stack">
            <section class="sr-card sr-hero-card sr-hero-image-panel p-0" aria-label="SpeakReady AI welcome hero">
                <div class="sr-image-hero-inner">
                    <div class="sr-image-hero-content">
                        <div class="sr-image-title-row">
                            <h6 class="sr-image-title">
                                <span>Practice Smarter.</span>
                                <span><strong>Interview Better.</strong></span>
                            </h6>
                        </div>
                        <ul class="sr-image-copy" aria-label="Practice support details">
                            <li>Confidence</li>
                            <li>Clarity</li>
                            <li>Structure</li>
                            <li>Tone</li>
                            <li>Timing</li>
                            <li>Focus</li>
                            <li>Fluency</li>
                            <li><span class="sr-image-copy-highlight">AI</span> feedback</li>
                            <li>Practice</li>
                            <li>Progress</li>
                        </ul>
                        <div class="sr-image-chip-row" aria-label="Practice focus areas">
                            <span class="sr-image-chip"><i class="fa-solid fa-briefcase"></i> Job Interviews</span>
                            <span class="sr-image-chip"><i class="fa-solid fa-building-columns"></i> School Admission Interviews</span>
                        </div>
                    </div>
                    <div class="sr-image-speech" aria-hidden="true">
                        <strong>Hi! {{ $welcomeName }}</strong>
                        <span>You're <span class="sr-image-speech-accent">ready</span> to practice confidently and <span class="sr-image-speech-accent is-success">succeed</span> today!</span>
                    </div>
                    <div class="sr-image-head-icons" aria-hidden="true">
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-microphone"></i></span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-headset"></i></span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face">AI</span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-bullseye"></i></span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-graduation-cap"></i></span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-star"></i></span></span>
                    </div>
                    <img class="sr-image-robot" src="{{ asset('img/dashboard-welcome-robot-transparent.png') }}" alt="">
                    <img class="sr-image-robot-hand" src="{{ asset('img/dashboard-welcome-robot-transparent.png') }}" alt="" aria-hidden="true">
                </div>
            </section>

            <div class="stat-grid sr-stats-desktop" role="group" aria-label="Quick statistics">
                <div class="sr-stat-card" style="--accent:#3b82f6;--meter-value:{{ $sessionsMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-microphone"></i></div>
                        <span class="sr-chip">Practice</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $totalSessions ?? 0 }}</div>
                        <div class="sr-stat-label">Completed sessions</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#22c55e;--meter-value:{{ $ratingMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-regular fa-star"></i></div>
                        <span class="sr-chip">Quality</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $rating }}<span style="font-size:.9rem;color:var(--tx3)">/5</span></div>
                        <div class="sr-stat-label">Average rating</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-award"></i></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#06b6d4;--meter-value:{{ $xpMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-bolt"></i></div>
                        <span class="sr-chip">Growth</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ number_format($experiencePoints ?? 0) }}</div>
                        <div class="sr-stat-label">Experience points</div>
                        <div class="sr-stat-meter" aria-hidden="true"><span>Lv. {{ $playerLevel }}</span></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#f59e0b;--meter-value:{{ $streakMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-fire"></i></div>
                        <span class="sr-chip">Streak</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $currentStreak ?? 0 }}</div>
                        <div class="sr-stat-label">Active practice days</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-regular fa-calendar-days"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sr-mobile-readiness-row">
            <section class="sr-card sr-score-panel {{ $scoreVal >= 80 ? 'score-high-panel' : ($scoreVal >= 60 ? 'score-med-panel' : 'score-low-panel') }}" aria-label="Readiness score">
                <div class="sr-score-top">
                    <span class="sr-status-pill {{ $scoreClass }}"><i class="fa-solid {{ $scoreIcon }}"></i> {{ $scoreText }}</span>
                    <span class="sr-chip ph-focus-chip"><i class="fa-solid fa-location-dot"></i> PH Focus</span>
                </div>
                <div class="sr-score-layout">
                    <div class="sr-readiness-ring" style="--ring-value: {{ $scoreVal }}%;" aria-label="Overall readiness {{ $scoreVal }} percent">
                        <div class="sr-ring-content">
                            <div class="sr-score-value">{{ $scoreVal }}<span>%</span></div>
                            <div class="sr-ring-label">Overall Readiness</div>
                        </div>
                    </div>
                    <div class="sr-score-meta">
                        <div class="sr-score-meta-item">
                            <div>
                                <div class="sr-meta-label">Average Rating</div>
                                <div class="sr-meta-value">{{ $rating }}/5</div>
                            </div>
                            <div class="sr-score-icon"><i class="fa-regular fa-star"></i></div>
                        </div>
                        <div class="sr-score-meta-item">
                            <div>
                                <div class="sr-meta-label">Next Goal</div>
                                <div class="sr-meta-value">{{ isset($upcomingGoal) ? ($upcomingGoal->target ?? 100) : 100 }}%</div>
                            </div>
                            <div class="sr-score-icon"><i class="fa-solid fa-bullseye"></i></div>
                        </div>
                    </div>
                </div>
                </section>

            <div class="sr-mobile-stat-grid" role="group" aria-label="Quick statistics">
                <div class="sr-stat-card" style="--accent:#3b82f6;--meter-value:{{ $sessionsMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-microphone"></i></div>
                        <span class="sr-chip">Practice</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $totalSessions ?? 0 }}</div>
                        <div class="sr-stat-label">Completed sessions</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#22c55e;--meter-value:{{ $ratingMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-regular fa-star"></i></div>
                        <span class="sr-chip">Quality</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $rating }}<span style="font-size:.9rem;color:var(--tx3)">/5</span></div>
                        <div class="sr-stat-label">Average rating</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-award"></i></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#06b6d4;--meter-value:{{ $xpMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-bolt"></i></div>
                        <span class="sr-chip">Growth</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ number_format($experiencePoints ?? 0) }}</div>
                        <div class="sr-stat-label">Experience points</div>
                        <div class="sr-stat-meter" aria-hidden="true"><span>Lv. {{ $playerLevel }}</span></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#f59e0b;--meter-value:{{ $streakMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-fire"></i></div>
                        <span class="sr-chip">Streak</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $currentStreak ?? 0 }}</div>
                        <div class="sr-stat-label">Active practice days</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-regular fa-calendar-days"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sr-dashboard-shell">
        <main class="sr-main-stack">
            <section id="card-progress-chart" class="sr-card sr-card-pad">
                <div class="sr-trend-header">
                    <div>
                        <div class="sr-trend-title-row">
                            <div class="sr-trend-icon"><i class="fa-solid fa-chart-line"></i></div>
                            <h5 class="sr-trend-title">Readiness Trend</h5>
                        </div>
                        <p class="sr-trend-subtitle">Recent completed Philippine interview sessions, scored from 0 to 100.</p>
                    </div>
                </div>
                <div class="sr-trend-actions justify-content-between mb-2">
                    <a href="{{ route('user.progress') }}" class="sr-trend-detail-btn">View Details <i class="fa-solid fa-chevron-right"></i></a>
                    <select class="sr-trend-filter" id="readinessTrendRange" aria-label="Readiness trend range">
                        <option value="5">Recent 5 Sessions</option>
                        <option value="10" selected>Recent 10 Sessions</option>
                    </select>
                </div>
                <div class="sr-trend-metrics">
                    <div class="sr-trend-metric" style="--metric-color:#2563eb">
                        <div class="sr-trend-metric-icon"><i class="fa-solid fa-gauge-high"></i></div>
                        <div>
                            <div class="sr-trend-metric-label">Average Score</div>
                            <div class="sr-trend-metric-value"><strong>{{ $trendAverage }}</strong> /100</div>
                        </div>
                    </div>
                    <div class="sr-trend-metric" style="--metric-color:#16a34a">
                        <div class="sr-trend-metric-icon"><i class="fa-solid {{ $trendImprovement >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i></div>
                        <div>
                            <div class="sr-trend-metric-label">Improvement</div>
                            <div class="sr-trend-metric-value"><strong>{{ $trendImprovement >= 0 ? '+' : '' }}{{ $trendImprovement }}%</strong> <span style="font-size:.82rem;font-weight:700;color:var(--trend-muted)">vs first</span></div>
                        </div>
                    </div>
                </div>
                <div class="sr-chart-box sr-trend-chart-wrap">
                    <canvas id="progressChart"></canvas>
                </div>
                <div class="sr-trend-note {{ $hasTrendScores ? '' : 'is-empty' }}">
                    <i class="{{ $trendNoteIcon }}"></i>
                    <span><strong>{{ $trendNoteTitle }}.</strong> {{ $trendNoteBody }}</span>
                </div>
            </section>

            <div class="sr-two-col sr-inline-panels">
                <section class="sr-card sr-card-pad sr-polished-card" style="--polish-accent:#10b981">
                    <div class="sr-polished-header">
                        <div class="sr-polished-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <h5 class="sr-polished-title">Category Performance</h5>
                            <p class="sr-polished-subtitle">Where your interview scores are strongest.</p>
                        </div>
                    </div>
                    @if($categoryCount > 0)
                        <div class="sr-progress-list">
                            @foreach($categoryPerformance as $index => $cat)
                                @php
                                    $colors = ['#22c55e', '#3b82f6', '#06b6d4', '#f59e0b', '#8b5cf6'];
                                    $color = $colors[$index % count($colors)];
                                    $catScore = max(0, min(100, (int) $cat->score));
                                @endphp
                                <div class="sr-progress-row">
                                    <div class="sr-progress-name">{{ $cat->name }}</div>
                                    <div class="sr-progress-score">{{ $catScore }}%</div>
                                    <div class="sr-progress"><span style="--value: {{ $catScore }}%; background: {{ $color }}"></span></div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sr-polished-empty">
                            <div class="sr-polished-empty-inner">
                                <div class="sr-empty-visual"><i class="fa-solid fa-folder-open"></i></div>
                                <p class="sr-polished-empty-text">Complete a Philippine interview session to unlock category performance.</p>
                            </div>
                        </div>
                    @endif
                </section>

                <section id="card-ai-recommendations" class="sr-card sr-card-pad sr-polished-card" style="--polish-accent:#f59e0b">
                    <div class="sr-polished-header">
                        <div class="sr-polished-icon"><i class="fa-solid fa-lightbulb"></i></div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <h5 class="sr-polished-title">AI Recommendations</h5>
                                <span class="sr-rec-badge">Personalized for you</span>
                            </div>
                            <p class="sr-polished-subtitle">Next actions based on your performance.</p>
                        </div>
                    </div>
                    @if(isset($aiRecommendations) && count($aiRecommendations) > 0)
                        <div class="sr-rec-list">
                            @foreach($aiRecommendations as $rec)
                                @php
                                    $recAccent = $safeAccent($rec->color ?? null, '#f59e0b');
                                    $recIcon = $safeFaIcon($rec->icon ?? null, 'fa-lightbulb');
                                @endphp
                                <a href="{{ $rec->url ?? route('user.modules.index') }}" class="sr-recommendation-card" style="--accent: {{ $recAccent }}">
                                    <div class="sr-recommendation-icon"><i class="fa-solid {{ $recIcon }}"></i></div>
                                    <div class="min-w-0">
                                        <div class="sr-recommendation-title">{{ $rec->text }}</div>
                                        @if(!empty($rec->reason))
                                            <div class="sr-recommendation-reason">{{ $rec->reason }}</div>
                                        @endif
                                    </div>
                                    <div class="sr-recommendation-next"><i class="fa-solid fa-chevron-right"></i></div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="sr-polished-empty">
                            <div class="sr-polished-empty-inner">
                                <div class="sr-empty-visual"><i class="fa-solid fa-lightbulb"></i></div>
                                <p class="sr-polished-empty-text">Complete a Philippine interview to get tailored recommendations.</p>
                            </div>
                        </div>
                    @endif
                </section>
            </div>

            <section id="card-recent-sessions" class="sr-card sr-card-pad sr-polished-card" style="--polish-accent:#06b6d4">
                <div class="sr-polished-header">
                    <div class="sr-polished-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <h5 class="sr-polished-title">Recent Sessions</h5>
                            <a href="{{ route('user.reports') }}" class="sr-plan-cta" style="margin-top:0;color:#2563eb">View All <i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                        <p class="sr-polished-subtitle">Review the latest completed Philippine mock interviews.</p>
                    </div>
                </div>
                <div class="sr-section-actions">
                    <a href="{{ route('user.reports') }}" class="sr-btn sr-section-action"><i class="fa-regular fa-rectangle-list"></i> View Reports</a>
                    @if(isset($recentSessions) && $recentSessions->count() > 0)
                        <form action="{{ route('user.sessions.clear') }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Clear all sessions?" data-sr-confirm-message="This will permanently clear all completed interview sessions. This cannot be undone." data-sr-confirm-action="Clear All" data-sr-confirm-variant="danger">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sr-btn sr-section-action danger w-100">
                                <i class="fa-solid fa-trash-can"></i> Clear All
                            </button>
                        </form>
                    @endif
                </div>

                <div class="table-responsive sr-sessions-table">
                    <table class="table custom-table mb-0 w-100">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Score</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSessions ?? [] as $session)
                                @php
                                    $sessionScore = $session->score ? (int) $session->score->overall_readiness_score : 0;
                                    $sessionColor = $sessionScore >= 80 ? '#22c55e' : ($sessionScore >= 60 ? '#f59e0b' : '#ef4444');
                                @endphp
                                <tr>
                                    <td>{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</td>
                                    <td><span class="sr-chip" style="background:rgba(59,130,246,.1);color:#60a5fa">{{ $session->category ? $session->category->title : 'Philippines Interview' }}</span></td>
                                    <td><span style="color:{{ $sessionColor }};font-weight:900">{{ $sessionScore }}%</span></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('user.review', $session->id) }}" class="sr-btn sr-btn-primary" style="min-height:34px;padding:6px 11px;font-size:.78rem">Review</a>
                                            <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Delete this session?" data-sr-confirm-message="This interview session and its saved feedback will be permanently deleted." data-sr-confirm-action="Delete Session" data-sr-confirm-variant="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sr-btn" title="Delete session" aria-label="Delete session from {{ $session->created_at ? $session->created_at->format('M d, Y') : 'recent sessions' }}" style="width:34px;min-height:34px;padding:0;color:#ef4444;border-color:rgba(239,68,68,.35)">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4" style="color:var(--tx3)">No recent sessions found. Start Philippine interview practice when you are ready.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="sr-sessions-mobile sr-session-list">
                    @forelse($recentSessions ?? [] as $session)
                        @php
                            $sessionScore = $session->score ? (int) $session->score->overall_readiness_score : 0;
                            $sessionColor = $sessionScore >= 80 ? '#22c55e' : ($sessionScore >= 60 ? '#f59e0b' : '#ef4444');
                        @endphp
                        <div class="sr-session-card-polished">
                            <div class="sr-session-icon"><i class="fa-solid fa-briefcase"></i></div>
                            <div class="sr-session-meta">
                                <div class="sr-session-title">{{ $session->category ? $session->category->title : 'Philippines Interview' }}</div>
                                <div class="sr-session-date">{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</div>
                            </div>
                            <div class="sr-session-score-stack" style="--score-color: {{ $sessionColor }}">
                                <span class="sr-session-score-pill">{{ $sessionScore }}%</span>
                                <div class="sr-session-score-bar"><span style="--score-value: {{ $sessionScore }}%"></span></div>
                            </div>
                            <a href="{{ route('user.review', $session->id) }}" class="sr-btn sr-btn-primary sr-session-review-btn">Review</a>
                            <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Delete this session?" data-sr-confirm-message="This interview session and its saved feedback will be permanently deleted." data-sr-confirm-action="Delete Session" data-sr-confirm-variant="danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sr-btn sr-session-delete-btn" title="Delete session" aria-label="Delete session from {{ $session->created_at ? $session->created_at->format('M d, Y') : 'recent sessions' }}">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="sr-polished-empty">
                            <div class="sr-polished-empty-inner">
                                <div class="sr-empty-visual"><i class="fa-solid fa-calendar-plus"></i></div>
                                <p class="sr-polished-empty-text">No recent sessions found. Start Philippine interview practice when you are ready.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>

        <aside class="sr-side-stack">
            <section id="card-skill-radar" class="sr-card sr-card-pad sr-side-feature" style="--side-accent:#ec4899">
                <div class="sr-side-feature-header">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-solid fa-chart-simple"></i></div>
                        <div>
                            <h5 class="sr-side-title">Skill Radar</h5>
                            <p class="sr-side-subtitle">Average capability profile.</p>
                        </div>
                    </div>
                    <a href="{{ route('user.progress') }}" class="sr-side-detail-btn"><i class="fa-solid fa-chart-line"></i> View Details</a>
                </div>
                @if($hasRadarScores)
                    <div class="chart-container-mobile sr-radar-box">
                        <canvas id="radarChart"></canvas>
                    </div>
                @else
                    <div class="sr-radar-locked" role="status">
                        <div class="sr-radar-locked-icon"><i class="fa-solid fa-lock"></i></div>
                        <p>Complete a scored interview to unlock your skill radar.</p>
                    </div>
                @endif
            </section>

            <section id="card-daily-challenge" class="sr-card sr-card-pad sr-side-feature sr-challenge-feature">
                <div class="sr-side-feature-header mb-0">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-regular fa-calendar-check"></i></div>
                        <div>
                            <h5 class="sr-side-title" style="color:#2563eb">Today&apos;s Challenge</h5>
                        </div>
                    </div>
                    <div class="sr-challenge-star"><i class="fa-regular fa-star"></i></div>
                </div>
                <h5 class="sr-challenge-title">{{ $challengeTitle }}</h5>
                <p class="sr-challenge-copy">{{ $challengeCopy }}</p>
                <div class="sr-reward-row">
                    <span class="sr-reward-pill xp"><i class="fa-regular fa-star"></i> +{{ $challengeXp }} XP</span>
                    <span class="sr-reward-pill streak"><i class="fa-solid fa-fire"></i> Streak eligible</span>
                </div>
                <a href="{{ route('interview.setup') }}" class="sr-btn sr-btn-primary w-100 sr-challenge-cta"><i class="fa-solid fa-play"></i> Start PH Challenge</a>
            </section>

            <section class="sr-card sr-card-pad sr-side-feature" style="--side-accent:#ef4444">
                <div class="sr-side-feature-header">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-solid fa-bullseye"></i></div>
                        <div>
                            <h5 class="sr-side-title">Current Goal</h5>
                            <p class="sr-side-subtitle">Progress toward your next readiness target.</p>
                        </div>
                    </div>
                </div>
                @if(isset($upcomingGoal))
                    <div class="sr-goal-panel">
                        <div class="sr-goal-main">
                            <div class="sr-goal-row">
                                <div class="sr-goal-title">{{ $upcomingGoal->title }}</div>
                                <div class="sr-goal-percent">{{ $goalPercent }}%</div>
                            </div>
                            <div class="sr-progress"><span style="--value: {{ $goalPercent }}%; background:linear-gradient(90deg,#22c55e,#0ea5e9)"></span></div>
                        </div>
                        <div class="sr-goal-footer">
                            <div class="sr-goal-note"><i class="fa-solid fa-chart-line"></i> {{ $goalNote }}</div>
                            <a href="{{ route('user.progress') }}" class="sr-side-detail-btn">View Goals <i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>
                @else
                    <div class="sr-polished-empty">
                        <div class="sr-polished-empty-inner">
                            <div class="sr-empty-visual"><i class="fa-solid fa-bullseye"></i></div>
                            <p class="sr-polished-empty-text">No current goal set.</p>
                        </div>
                    </div>
                @endif
            </section>

            <section id="card-achievements" class="sr-card sr-card-pad sr-side-feature" style="--side-accent:#f59e0b">
                <div class="sr-side-feature-header">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-solid fa-trophy"></i></div>
                        <div>
                            <h5 class="sr-side-title">Achievements</h5>
                            <p class="sr-side-subtitle">Milestones earned through practice.</p>
                        </div>
                    </div>
                    <a href="{{ route('user.progress') }}" class="sr-side-detail-btn">View All <i class="fa-solid fa-chevron-right"></i></a>
                </div>
                <div class="sr-achievement-showcase">
                    @foreach($achievementCatalog as $achievement)
                        @php $earned = (bool) $achievement['earned']; @endphp
                        <div class="sr-achievement-tile" style="--accent: {{ $achievement['accent'] }}">
                            <div class="sr-achievement-tile-icon"><i class="fa-solid {{ $achievement['icon'] }}"></i></div>
                            <div class="sr-achievement-tile-title">{{ $achievement['label'] }}</div>
                            <div class="sr-achievement-status">
                                @if(! $earned && $achievement['status'] === 'Locked')<i class="fa-solid fa-lock"></i>@endif
                                {{ $earned ? 'Earned' : $achievement['status'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

        </aside>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Chart === 'undefined') return;

    const rootElement = document.documentElement;
    const readThemeColor = (style, varName, fallback) => style.getPropertyValue(varName).trim() || fallback;
    const getDashboardTheme = () => {
        const theme = (window.SpeakReadyTheme?.get?.() || rootElement.dataset.theme || '').toLowerCase();
        if (theme === 'light' || theme === 'dark') return theme;

        return rootElement.classList.contains('lm') || document.body?.classList.contains('lm') ? 'light' : 'dark';
    };
    const getDashboardChartPalette = () => {
        const rootStyle = getComputedStyle(rootElement);
        const radarCard = document.getElementById('card-skill-radar');
        const radarStyle = radarCard ? getComputedStyle(radarCard) : rootStyle;
        const isLightMode = getDashboardTheme() === 'light';

        return {
            isLightMode,
            txColor: isLightMode ? '#1e293b' : readThemeColor(rootStyle, '--tx', '#f8fafc'),
            mutedColor: isLightMode ? '#475569' : readThemeColor(rootStyle, '--tx2', '#dbeafe'),
            surfaceColor: isLightMode ? '#ffffff' : readThemeColor(rootStyle, '--sf', '#111827'),
            gridColor: isLightMode ? 'rgba(71,85,105,0.34)' : 'rgba(226,232,240,0.5)',
            trendLineColor: isLightMode ? '#1d4ed8' : '#93c5fd',
            trendPointFill: isLightMode ? '#ffffff' : '#0f172a',
            radarGridColor: readThemeColor(radarStyle, '--sr-radar-grid-color', isLightMode ? 'rgba(71,85,105,0.48)' : 'rgba(226,232,240,0.66)'),
            radarAngleColor: readThemeColor(radarStyle, '--sr-radar-angle-color', isLightMode ? 'rgba(71,85,105,0.42)' : 'rgba(226,232,240,0.54)'),
            radarGridWidth: isLightMode ? 1.35 : 1.75,
            radarLabelColor: readThemeColor(radarStyle, '--sr-radar-label-color', isLightMode ? '#1e293b' : '#f8fafc')
        };
    };
    const createTrendGradient = (ctx, palette) => {
        const gradient = ctx.createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, palette.isLightMode ? 'rgba(37, 99, 235, 0.24)' : 'rgba(96, 165, 250, 0.28)');
        gradient.addColorStop(0.58, palette.isLightMode ? 'rgba(59, 130, 246, 0.10)' : 'rgba(96, 165, 250, 0.14)');
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');
        return gradient;
    };
    const getRadarDatasetColors = (hasScores, palette) => ({
        backgroundColor: hasScores
            ? (palette.isLightMode ? 'rgba(219, 39, 119, 0.2)' : 'rgba(244, 114, 182, 0.28)')
            : (palette.isLightMode ? 'rgba(219, 39, 119, 0.12)' : 'rgba(244, 114, 182, 0.2)'),
        borderColor: hasScores
            ? (palette.isLightMode ? '#be185d' : '#f9a8d4')
            : (palette.isLightMode ? 'rgba(190, 24, 93, 0.78)' : 'rgba(251, 207, 232, 0.94)'),
        pointBackgroundColor: hasScores
            ? (palette.isLightMode ? '#be185d' : '#f9a8d4')
            : (palette.isLightMode ? '#db2777' : '#fbcfe8'),
        pointBorderColor: palette.surfaceColor,
        pointHoverBackgroundColor: palette.surfaceColor,
        pointHoverBorderColor: palette.isLightMode ? '#be185d' : '#f9a8d4'
    });
    const initialPalette = getDashboardChartPalette();
    const {
        isLightMode,
        txColor,
        mutedColor,
        surfaceColor,
        gridColor,
        trendLineColor,
        trendPointFill,
        radarGridColor,
        radarAngleColor,
        radarGridWidth,
        radarLabelColor
    } = initialPalette;
    const isCompactTrend = () => window.matchMedia('(max-width: 575px)').matches;
    const radarPointLabelSize = () => window.matchMedia('(max-width: 380px)').matches ? 9 : 10;
    let progressChart = null;
    let progressCtx = null;
    let radarChart = null;
    let dashboardHasRadarScores = false;

    Chart.defaults.color = txColor;
    Chart.defaults.font.family = "'Poppins', sans-serif";

    const emptyChartPlugin = {
        id: 'emptyChartMessage',
        afterDraw(chart, args, options) {
            const datasets = chart.data.datasets || [];
            const hasValues = datasets.some((dataset) => {
                return (dataset.data || []).some((value) => Number(value) > 0);
            });

            if (hasValues && !options?.force) return;
            if (!options?.text) return;

            const { ctx, chartArea } = chart;
            if (!chartArea) return;

            ctx.save();
            ctx.fillStyle = options?.color || mutedColor;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = "700 13px 'Poppins', sans-serif";
            const message = options?.text || 'Complete a scored interview to see this chart.';
            const maxWidth = Math.max(120, chartArea.right - chartArea.left - 24);
            const words = message.split(' ');
            const lines = [];
            let currentLine = '';
            words.forEach((word) => {
                const testLine = currentLine ? `${currentLine} ${word}` : word;
                if (ctx.measureText(testLine).width > maxWidth && currentLine) {
                    lines.push(currentLine);
                    currentLine = word;
                } else {
                    currentLine = testLine;
                }
            });
            if (currentLine) lines.push(currentLine);
            const lineHeight = 18;
            const startY = ((chartArea.top + chartArea.bottom) / 2) - ((lines.length - 1) * lineHeight / 2);
            lines.forEach((line, index) => {
                ctx.fillText(line, (chartArea.left + chartArea.right) / 2, startY + (index * lineHeight));
            });
            ctx.restore();
        }
    };

    if (!window.SpeakReadyEmptyChartPluginRegistered) {
        Chart.register(emptyChartPlugin);
        window.SpeakReadyEmptyChartPluginRegistered = true;
    }

    const progressCanvas = document.getElementById('progressChart');
    if (progressCanvas) {
        progressCtx = progressCanvas.getContext('2d');
        const normalizeTrendValue = (value) => {
            const numericValue = Number(value);

            return Number.isFinite(numericValue) ? Math.max(0, Math.min(100, Math.round(numericValue))) : null;
        };
        const chartDataObj = {
            recent: {
                labels: @json(collect($scoreTrend ?? [])->pluck('date')->values()),
                data: @json(collect($scoreTrend ?? [])->pluck('score')->values()).map(normalizeTrendValue)
            }
        };
        const trendRangeSelect = document.getElementById('readinessTrendRange');
        const trendSlice = (count) => {
            const range = Number(count || 10);
            return {
                labels: chartDataObj.recent.labels.slice(-range),
                data: chartDataObj.recent.data.slice(-range)
            };
        };
        const displayTrend = (trend) => {
            const hasData = trend.data.some((value) => value !== null);

            return {
                labels: hasData ? trend.labels : ['No scored sessions'],
                data: hasData ? trend.data : [null]
            };
        };
        const initialTrendRange = isCompactTrend() ? 5 : 10;

        if (trendRangeSelect) {
            trendRangeSelect.value = String(initialTrendRange);
        }

        const initialTrend = displayTrend(trendSlice(initialTrendRange));

        const gradientLine = createTrendGradient(progressCtx, initialPalette);

        progressChart = new Chart(progressCtx, {
            type: 'line',
            data: {
                labels: initialTrend.labels,
                datasets: [{
                    label: 'Readiness Score',
                    data: initialTrend.data,
                    borderColor: trendLineColor,
                    backgroundColor: gradientLine,
                    borderWidth: isCompactTrend() ? 2 : 3,
                    tension: 0.38,
                    fill: true,
                    pointBackgroundColor: trendPointFill,
                    pointBorderColor: trendLineColor,
                    pointBorderWidth: isCompactTrend() ? 2 : 3,
                    pointRadius: isCompactTrend() ? 3 : 5,
                    pointHoverRadius: isCompactTrend() ? 5 : 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    emptyChartMessage: {
                        color: mutedColor,
                        text: 'Complete a scored interview to see your readiness trend.'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: isLightMode ? '#ffffff' : 'rgba(15, 23, 42, 0.94)',
                        titleColor: isLightMode ? '#0f172a' : '#fff',
                        bodyColor: isLightMode ? '#334155' : '#dbeafe',
                        borderColor: trendLineColor,
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                if (context.parsed.y === null || typeof context.parsed.y === 'undefined') {
                                    return ' No readiness score yet';
                                }

                                return ' Readiness Score: ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 25,
                            padding: isCompactTrend() ? 4 : 8,
                            color: txColor,
                            font: { size: isCompactTrend() ? 10 : 12, weight: 600 }
                        },
                        grid: { color: gridColor, lineWidth: isLightMode ? 1.1 : 1.35, borderDash: [6, 6], drawTicks: false },
                        border: { display: false }
                    },
                    x: {
                        ticks: {
                            padding: isCompactTrend() ? 6 : 10,
                            font: { size: isCompactTrend() ? 9 : 11, weight: 600 },
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: isCompactTrend() ? 4 : 8,
                            color: txColor
                        },
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });

        const applyTrendRange = (range) => {
            const nextTrend = displayTrend(trendSlice(range));
            progressChart.data.labels = nextTrend.labels;
            progressChart.data.datasets[0].data = nextTrend.data;
            progressChart.update();
        };

        trendRangeSelect?.addEventListener('change', (event) => {
            applyTrendRange(event.target.value);
        });

        let trendResizeTimer = null;
        const updateTrendChartSizing = () => {
            const compact = isCompactTrend();
            progressChart.data.datasets[0].borderWidth = compact ? 2 : 3;
            progressChart.data.datasets[0].pointBorderWidth = compact ? 2 : 3;
            progressChart.data.datasets[0].pointRadius = compact ? 3 : 5;
            progressChart.data.datasets[0].pointHoverRadius = compact ? 5 : 6;
            progressChart.options.scales.y.ticks.padding = compact ? 4 : 8;
            progressChart.options.scales.y.ticks.font.size = compact ? 10 : 12;
            progressChart.options.scales.x.ticks.padding = compact ? 6 : 10;
            progressChart.options.scales.x.ticks.font.size = compact ? 9 : 11;
            progressChart.options.scales.x.ticks.maxTicksLimit = compact ? 4 : 8;
            progressChart.update('none');
        };
        window.addEventListener('resize', () => {
            window.clearTimeout(trendResizeTimer);
            trendResizeTimer = window.setTimeout(updateTrendChartSizing, 120);
        });
    }

    const radarCanvas = document.getElementById('radarChart');
    if (radarCanvas) {
        const radarScores = [
            {{ (int) ($radarData['clarity'] ?? 0) }},
            {{ (int) ($radarData['relevance'] ?? 0) }},
            {{ (int) ($radarData['grammar'] ?? 0) }},
            {{ (int) ($radarData['professionalism'] ?? 0) }},
            {{ (int) ($radarData['delivery_stability'] ?? 0) }}
        ];
        const hasRadarScores = radarScores.some((value) => Number(value) > 0);
        dashboardHasRadarScores = hasRadarScores;
        const radarDisplayScores = hasRadarScores ? radarScores : [0, 0, 0, 0, 0];
        const radarColors = getRadarDatasetColors(hasRadarScores, initialPalette);

        radarChart = new Chart(radarCanvas.getContext('2d'), {
            type: 'radar',
            data: {
                labels: ['Clarity', 'Relevance', 'Grammar', 'Professionalism', 'Delivery Stability'],
                datasets: [{
                    label: 'Score Level',
                    data: radarDisplayScores,
                    backgroundColor: radarColors.backgroundColor,
                    borderColor: radarColors.borderColor,
                    pointBackgroundColor: radarColors.pointBackgroundColor,
                    pointBorderColor: radarColors.pointBorderColor,
                    pointHoverBackgroundColor: radarColors.pointHoverBackgroundColor,
                    pointHoverBorderColor: radarColors.pointHoverBorderColor,
                    borderWidth: hasRadarScores ? 2.25 : 1.75,
                    borderDash: hasRadarScores ? [] : [6, 5]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 8, right: 12, bottom: 8, left: 12 } },
                elements: {
                    line: { borderJoinStyle: 'round' },
                    point: { radius: 3.5, hoverRadius: 5, borderWidth: 2 }
                },
                plugins: {
                    legend: { display: false },
                    emptyChartMessage: {
                        color: mutedColor,
                        force: !hasRadarScores,
                        text: 'Complete a scored interview to unlock your skill radar.'
                    }
                },
                scales: {
                    r: {
                        angleLines: { color: radarAngleColor, lineWidth: radarGridWidth },
                        grid: { color: radarGridColor, lineWidth: radarGridWidth },
                        pointLabels: { color: radarLabelColor, font: { size: radarPointLabelSize(), weight: 900 }, padding: 10 },
                        suggestedMin: 0,
                        suggestedMax: 100,
                        ticks: { display: false, stepSize: 20, backdropColor: 'transparent' }
                    }
                }
            }
        });
    }

    const previousChartColorUpdater = window.updateChartColors;
    window.updateChartColors = function() {
        if (typeof previousChartColorUpdater === 'function' && previousChartColorUpdater !== window.updateChartColors) {
            previousChartColorUpdater();
        }

        const palette = getDashboardChartPalette();
        Chart.defaults.color = palette.txColor;

        if (progressChart && progressCtx) {
            const progressDataset = progressChart.data.datasets[0];
            progressDataset.borderColor = palette.trendLineColor;
            progressDataset.backgroundColor = createTrendGradient(progressCtx, palette);
            progressDataset.pointBackgroundColor = palette.trendPointFill;
            progressDataset.pointBorderColor = palette.trendLineColor;
            progressChart.options.plugins.emptyChartMessage.color = palette.mutedColor;
            progressChart.options.plugins.tooltip.backgroundColor = palette.isLightMode ? '#ffffff' : 'rgba(15, 23, 42, 0.94)';
            progressChart.options.plugins.tooltip.titleColor = palette.isLightMode ? '#0f172a' : '#fff';
            progressChart.options.plugins.tooltip.bodyColor = palette.isLightMode ? '#334155' : '#dbeafe';
            progressChart.options.plugins.tooltip.borderColor = palette.trendLineColor;
            progressChart.options.scales.y.ticks.color = palette.txColor;
            progressChart.options.scales.y.grid.color = palette.gridColor;
            progressChart.options.scales.y.grid.lineWidth = palette.isLightMode ? 1.1 : 1.35;
            progressChart.options.scales.x.ticks.color = palette.txColor;
            progressChart.update('none');
        }

        if (radarChart) {
            const radarColors = getRadarDatasetColors(dashboardHasRadarScores, palette);
            const radarDataset = radarChart.data.datasets[0];
            Object.assign(radarDataset, radarColors);
            radarChart.options.plugins.emptyChartMessage.color = palette.mutedColor;
            radarChart.options.scales.r.angleLines.color = palette.radarAngleColor;
            radarChart.options.scales.r.angleLines.lineWidth = palette.radarGridWidth;
            radarChart.options.scales.r.grid.color = palette.radarGridColor;
            radarChart.options.scales.r.grid.lineWidth = palette.radarGridWidth;
            radarChart.options.scales.r.pointLabels.color = palette.radarLabelColor;
            radarChart.options.scales.r.pointLabels.font.size = radarPointLabelSize();
            radarChart.update('none');
        }
    };
});
</script>
@endpush

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const completionKey = 'onboarding_completed';
        const serverDetectedMobile = false;

        const stepsMobile = [];

        const stepsDesktop = [
            { element: '#dbSidebar', popover: { title: 'Navigation Menu', description: 'Open Philippine Mock Interview, modules, Voice Rehearsal, AI Coach, reports, and more from here.', side: 'right', align: 'start' }},
            { element: '#dbTutorialBtn', popover: { title: 'Replay The Tour', description: 'Use this button whenever you want to restart the walkthrough.', side: 'bottom', align: 'center' }},
            { element: '.sr-score-panel', popover: { title: 'Readiness Summary', description: 'Your current readiness score, status, average rating, and next target live here.', side: 'bottom', align: 'start' }},
            { element: '.sr-stats-desktop', popover: { title: 'Practice Snapshot', description: 'Track completed sessions, rating, XP, and active practice days at a glance.', side: 'top', align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Readiness Trend', description: 'See how your score changes across your latest completed sessions.', side: 'top', align: 'start' }},
            { element: '#card-ai-recommendations', popover: { title: 'AI Recommendations', description: 'Use these next actions to decide what to practice first.', side: 'bottom', align: 'start' }},
            { element: '#card-recent-sessions', popover: { title: 'Recent Sessions', description: 'Open past interviews, review feedback, or clear old records.', side: 'top', align: 'start' }},
            { element: '#card-daily-challenge', popover: { title: 'Daily Challenge', description: 'Start a focused practice task for extra XP and streak progress.', side: 'left', align: 'start' }},
            { element: '#dbThBtn', popover: { title: 'Theme Toggle', description: 'Switch between light and dark mode for a comfortable viewing experience.', side: 'bottom', align: 'center' }},
            { element: '#profileWrap', popover: { title: 'Your Profile', description: 'Manage account settings, notifications, and sign-out options.', side: 'bottom', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey,
            serverDetectedMobile,
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 350,
        });

    });
</script>
@endpush

@endsection
