@extends('desktop.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/dashboard.css?v=61') }}" data-page-style="dashboard">
@endpush

@section('content')
@php
    $scoreVal = (int) round($profile->readiness_score ?? $avgScore ?? 0);
    $scoreVal = max(0, min(100, $scoreVal));
    $scoreText = $scoreVal >= 80 ? 'Interview Ready' : 'Boost';
    $fullName = trim(Auth::user()->name ?? '') ?: 'User';
    $nameParts = preg_split('/\s+/', $fullName);
    $firstName = $nameParts[0] ?? 'User';
    $welcomeName = $firstName;
    $rating = round(($avgScore ?? 0) / 20, 1);
    $goalPercent = isset($upcomingGoal) ? max(0, min(100, round($upcomingGoal->percent ?? 0))) : 0;
    $moduleCount = isset($learningLabProgress) ? count($learningLabProgress) : 0;
    $sessionsMeter = max(0, min(100, (int) round((($totalSessions ?? 0) / 10) * 100)));
    $ratingMeter = max(0, min(100, (int) round(($rating / 5) * 100)));
    $xpValue = max(0, (int) ($experiencePoints ?? 0));
    $formatCompactStat = static function ($value): string {
        $value = max(0, (float) $value);

        if ($value < 1000) {
            return number_format((int) round($value));
        }

        if ($value < 10000) {
            return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.').'K';
        }

        if ($value < 999500) {
            return number_format((int) round($value / 1000)).'K';
        }

        return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.').'M';
    };
    $compactXpValue = $formatCompactStat($xpValue);
    $playerLevel = max(1, (int) ($profile->player_level ?? (floor($xpValue / 1000) + 1)));
    $xpMeter = max(0, min(100, (int) round((($xpValue % 1000) / 1000) * 100)));
    $streakMeter = max(0, min(100, (int) round((($currentStreak ?? 0) / 7) * 100)));
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
    $challengeXp = $hasTrendScores ? 60 : 40;
    $trendDirectionText = ! $hasTrendScores
        ? 'No scores yet'
        : ($trendImprovement > 0
            ? 'Trending up'
            : ($trendImprovement < 0 ? 'Needs focus' : 'Stable'));
    $trendDirectionClass = ! $hasTrendScores
        ? 'is-empty'
        : ($trendImprovement > 0
            ? 'is-up'
            : ($trendImprovement < 0 ? 'is-down' : 'is-flat'));
    $trendDirectionIcon = ! $hasTrendScores
        ? 'fa-circle-dot'
        : ($trendImprovement < 0 ? 'fa-arrow-trend-down' : 'fa-arrow-trend-up');
    $goalTarget = isset($upcomingGoal) ? max(0, min(100, (int) round($upcomingGoal->target ?? 100))) : 100;
    $dashboardJobPositionOptions = collect(config('speakready_scope.job_positions', []))
        ->flatten()
        ->map(fn ($position) => trim((string) $position))
        ->filter()
        ->unique(fn (string $position) => strtolower($position))
        ->values();
@endphp

<div class="db-section active sr-dashboard" id="sec-overview">
    <section class="sr-saas-command" aria-labelledby="srDashboardTitle">
        <div class="sr-saas-command-copy">
            <span class="sr-saas-eyebrow"><i class="fa-solid fa-circle-dot"></i> User Dashboard</span>
            <h4 id="srDashboardTitle">Your readiness workspace</h4>
            <p>Track interview readiness, practice momentum, and next actions in one focused view.</p>
        </div>
        <div class="sr-saas-command-metrics" role="group" aria-label="Dashboard state">
            <a href="{{ route('user.progress') }}" class="sr-saas-command-metric" aria-label="Open readiness status: {{ $scoreText }}">
                <span>Status</span>
                <strong>{{ $scoreText }}</strong>
            </a>
            <a href="{{ route('user.progress') }}" class="sr-saas-command-metric" aria-label="Open readiness trend: {{ $trendDirectionText }}">
                <span>Trend</span>
                <strong>{{ $trendDirectionText }}</strong>
            </a>
            <a href="{{ route('user.progress') }}" class="sr-saas-command-metric" aria-label="Open readiness target: {{ $goalTarget }} percent">
                <span>Target</span>
                <strong>{{ $goalTarget }}%</strong>
            </a>
        </div>
    </section>


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
                        </div>
                    </div>
                    <div class="sr-image-speech" aria-hidden="true" data-sr-dashboard-bubble>
                        <strong>Hi! {{ $welcomeName }}</strong>
                        <span data-sr-dashboard-bubble-line>You're <span class="sr-image-speech-accent">ready</span> to practice and <span class="sr-image-speech-accent is-success">succeed</span> today!</span>
                        <span class="sr-image-speech-action" data-sr-dashboard-bubble-action>Click the robot for AI Coach.</span>
                    </div>
                    <div class="sr-image-head-icons" aria-hidden="true">
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-microphone"></i></span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-headset"></i></span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face">AI</span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-bullseye"></i></span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-graduation-cap"></i></span></span>
                        <span class="sr-image-head-icon"><span class="sr-image-head-icon-face"><i class="fa-solid fa-star"></i></span></span>
                    </div>
                    <a
                        href="{{ route('user.coach') }}"
                        class="sr-image-robot sr-image-coach-trigger"
                        id="dashboardCoachImageTrigger"
                        aria-label="Open AI Coach"
                        title="AI Coach"
                    >
                        <picture>
                            <source srcset="{{ asset('img/dashboard-hero-robot-reference.webp') }}?v=20261020" type="image/webp">
                            <img src="{{ asset('img/dashboard-hero-robot-reference.png') }}" alt="" aria-hidden="true" width="1536" height="1024" loading="eager" fetchpriority="high" decoding="async" draggable="false">
                        </picture>
                    </a>
                </div>
            </section>

            <div class="stat-grid sr-stats-desktop" role="group" aria-label="Quick statistics">
                <a href="{{ route('user.reports') }}" class="sr-stat-card sr-stat-link-card" style="--accent:#3b82f6;--meter-value:{{ $sessionsMeter }}%;" aria-label="Open completed sessions reports">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-microphone"></i></div>
                        <span class="sr-chip">Practice</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $totalSessions ?? 0 }}</div>
                        <div class="sr-stat-label">Completed sessions</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    </div>
                    <div class="sr-stat-foot">
                        <span>10-session milestone</span>
                        <strong>{{ $sessionsMeter }}%</strong>
                    </div>
                </a>
                <a href="{{ route('user.feedback') }}" class="sr-stat-card sr-stat-link-card" style="--accent:#22c55e;--meter-value:{{ $ratingMeter }}%;" aria-label="Open feedback and average rating details">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-regular fa-star"></i></div>
                        <span class="sr-chip">Quality</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $rating }}<span style="font-size:.9rem;color:var(--tx3)">/5</span></div>
                        <div class="sr-stat-label">Average rating</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-award"></i></div>
                    </div>
                    <div class="sr-stat-foot">
                        <span>Quality meter</span>
                        <strong>{{ $ratingMeter }}%</strong>
                    </div>
                </a>
                <a href="{{ route('user.skills') }}" class="sr-stat-card sr-stat-link-card" style="--accent:#06b6d4;--meter-value:{{ $xpMeter }}%;" aria-label="Open skills and experience points">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-bolt"></i></div>
                        <span class="sr-chip">Growth</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value" title="{{ number_format($xpValue) }} XP">{{ $compactXpValue }}</div>
                        <div class="sr-stat-label">Experience points</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-medal" aria-hidden="true"></i></div>
                    </div>
                    <div class="sr-stat-foot">
                        <span>Next level</span>
                        <strong>{{ $xpMeter }}%</strong>
                    </div>
                </a>
                <a href="{{ route('user.practice.calendar') }}" class="sr-stat-card sr-stat-link-card" style="--accent:#f59e0b;--meter-value:{{ $streakMeter }}%;" aria-label="Open activity calendar and streak details">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-fire"></i></div>
                        <span class="sr-chip">Streak</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $currentStreak ?? 0 }}</div>
                        <div class="sr-stat-label">Active practice days</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-regular fa-calendar-days"></i></div>
                    </div>
                    <div class="sr-stat-foot">
                        <span>7-day streak</span>
                        <strong>{{ $streakMeter }}%</strong>
                    </div>
                </a>
            </div>
        </div>

        <div class="sr-mobile-readiness-row">
            <a href="{{ route('user.progress') }}" class="sr-card sr-score-panel sr-score-link-panel {{ $scoreVal >= 80 ? 'score-high-panel' : ($scoreVal >= 60 ? 'score-med-panel' : 'score-low-panel') }}" aria-label="Open readiness and goal progress">
                <div class="sr-score-top sr-score-top-desktop">
                    <span class="sr-chip sr-boost-chip"><i class="fa-solid fa-bolt"></i> Boost</span>
                    <span class="sr-chip ph-focus-chip"><i class="fa-solid fa-location-dot"></i> Interview Focus</span>
                </div>
                <div class="sr-score-layout">
                    <div class="sr-score-primary">
                        <div class="sr-readiness-ring" role="img" style="--ring-value: {{ $scoreVal }}%;" aria-label="Overall readiness {{ $scoreVal }} percent">
                            <div class="sr-ring-content">
                                <div class="sr-score-value">{{ $scoreVal }}<span>%</span></div>
                                <div class="sr-ring-label">Overall Readiness</div>
                            </div>
                        </div>
                        <div class="sr-score-goal-card">
                            <div>
                                <div class="sr-meta-label">Next Goal</div>
                                <div class="sr-meta-value">{{ isset($upcomingGoal) ? ($upcomingGoal->target ?? 100) : 100 }}%</div>
                            </div>
                            <div class="sr-score-icon"><i class="fa-solid fa-bullseye"></i></div>
                        </div>
                    </div>
                </div>
                <div class="sr-score-brief" role="group" aria-label="Readiness details">
                    <div>
                        <span>Goal progress</span>
                        <strong>{{ $goalPercent }}%</strong>
                    </div>
                    <div>
                        <span>Trend sample</span>
                        <strong>{{ $trendSessionCount }} {{ \Illuminate\Support\Str::plural('session', $trendSessionCount) }}</strong>
                    </div>
                    <div>
                        <span>Next task</span>
                        <strong>+{{ $challengeXp }} XP</strong>
                    </div>
                </div>
                </a>

            <div class="sr-mobile-stat-grid" role="group" aria-label="Quick statistics">
                <a href="{{ route('user.reports') }}" class="sr-stat-card sr-stat-link-card" style="--accent:#3b82f6;--meter-value:{{ $sessionsMeter }}%;" aria-label="Open completed sessions reports">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-microphone"></i></div>
                        <span class="sr-chip">Practice</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $totalSessions ?? 0 }}</div>
                        <div class="sr-stat-label">Completed sessions</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    </div>
                    <div class="sr-stat-foot">
                        <span>10-session milestone</span>
                        <strong>{{ $sessionsMeter }}%</strong>
                    </div>
                </a>
                <a href="{{ route('user.feedback') }}" class="sr-stat-card sr-stat-link-card" style="--accent:#22c55e;--meter-value:{{ $ratingMeter }}%;" aria-label="Open feedback and average rating details">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-regular fa-star"></i></div>
                        <span class="sr-chip">Quality</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $rating }}<span style="font-size:.9rem;color:var(--tx3)">/5</span></div>
                        <div class="sr-stat-label">Average rating</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-award"></i></div>
                    </div>
                    <div class="sr-stat-foot">
                        <span>Quality meter</span>
                        <strong>{{ $ratingMeter }}%</strong>
                    </div>
                </a>
                <a href="{{ route('user.skills') }}" class="sr-stat-card sr-stat-link-card" style="--accent:#06b6d4;--meter-value:{{ $xpMeter }}%;" aria-label="Open skills and experience points">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-bolt"></i></div>
                        <span class="sr-chip">Growth</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value" title="{{ number_format($xpValue) }} XP">{{ $compactXpValue }}</div>
                        <div class="sr-stat-label">Experience points</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-medal" aria-hidden="true"></i></div>
                    </div>
                    <div class="sr-stat-foot">
                        <span>Next level</span>
                        <strong>{{ $xpMeter }}%</strong>
                    </div>
                </a>
                <a href="{{ route('user.practice.calendar') }}" class="sr-stat-card sr-stat-link-card" style="--accent:#f59e0b;--meter-value:{{ $streakMeter }}%;" aria-label="Open activity calendar and streak details">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-fire"></i></div>
                        <span class="sr-chip">Streak</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $currentStreak ?? 0 }}</div>
                        <div class="sr-stat-label">Active practice days</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-regular fa-calendar-days"></i></div>
                    </div>
                    <div class="sr-stat-foot">
                        <span>7-day streak</span>
                        <strong>{{ $streakMeter }}%</strong>
                    </div>
                </a>
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
                        <p class="sr-trend-subtitle">Recent completed interview sessions, scored from 0 to 100.</p>
                    </div>
                    <span class="sr-trend-state {{ $trendDirectionClass }}"><i class="fa-solid {{ $trendDirectionIcon }}"></i> {{ $trendDirectionText }}</span>
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

        </main>
    </div>
</div>

@php
    $dashboardMockScenarios = collect($dashboardMockScenarios ?? []);
    $dashboardMockDefaultScenario = $dashboardMockScenarios->first();
    $dashboardMockSelectedCategoryId = old('category_id', $dashboardMockDefaultScenario['category_id'] ?? '');
    $dashboardMockSelectedScenario = $dashboardMockScenarios->first(fn ($scenario) => (string) $scenario['category_id'] === (string) $dashboardMockSelectedCategoryId)
        ?? $dashboardMockDefaultScenario;
    $dashboardMockQuestionTypes = collect(old('question_types', ['Behavioral', 'Situational']))->all();
    $dashboardMockHasScenarios = $dashboardMockScenarios->isNotEmpty();
@endphp

<div class="modal fade sr-dashboard-coach-modal sr-dashboard-mock-modal" id="dashboardMockModal" tabindex="-1" aria-labelledby="dashboardMockModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="dashboardMockForm" action="{{ route('interview.start') }}" method="POST">
                @csrf
                <input type="hidden" name="interview_focus" id="dashboardMockFocus" value="{{ $dashboardMockSelectedScenario['interview_focus'] ?? 'Job Interview' }}">
                <input type="hidden" name="coach_focus_mode" value="balanced">
                <input type="hidden" name="time_limit" value="0">
                <div class="modal-header">
                    <div class="sr-dashboard-coach-heading">
                        <span class="sr-dashboard-coach-icon"><i class="fa-solid fa-play"></i></span>
                        <div>
                            <h5 class="modal-title" id="dashboardMockModalTitle">Start Mock Interview</h5>
                            <p>Set up a focused practice session.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sr-dashboard-mock-grid">
                        <div class="sr-dashboard-mock-field">
                            <label class="sr-dashboard-coach-label" for="dashboardMockCategory">Practice scenario</label>
                            <select class="sr-dashboard-mock-control" name="category_id" id="dashboardMockCategory" required {{ $dashboardMockHasScenarios ? '' : 'disabled' }}>
                                @forelse($dashboardMockScenarios as $scenario)
                                    <option value="{{ $scenario['category_id'] }}"
                                        data-focus="{{ $scenario['interview_focus'] }}"
                                        {{ (string) $dashboardMockSelectedCategoryId === (string) $scenario['category_id'] ? 'selected' : '' }}>
                                        {{ $scenario['label'] }}
                                    </option>
                                @empty
                                    <option value="" selected>No active scenarios available</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="sr-dashboard-mock-field">
                            <label class="sr-dashboard-coach-label" for="dashboardMockPosition">Target position</label>
                            <select class="sr-dashboard-mock-control" name="target_position" id="dashboardMockPosition" required data-selected-target="{{ old('target_position', Auth::user()?->target_position) }}">
                                <option value="" disabled {{ old('target_position', Auth::user()?->target_position) ? '' : 'selected' }}>Choose a target position</option>
                                @foreach($dashboardJobPositionOptions as $positionOption)
                                    <option value="{{ $positionOption }}" {{ old('target_position', Auth::user()?->target_position) === $positionOption ? 'selected' : '' }}>{{ $positionOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sr-dashboard-mock-field">
                            <label class="sr-dashboard-coach-label">Difficulty</label>
                            <div class="sr-dashboard-mock-choice-grid">
                                @foreach([
                                    'easy' => ['label' => 'Easy', 'icon' => 'fa-signal'],
                                    'medium' => ['label' => 'Medium', 'icon' => 'fa-star'],
                                    'hard' => ['label' => 'Hard', 'icon' => 'fa-shield-alt'],
                                ] as $difficultyValue => $difficultyMeta)
                                    <label class="sr-dashboard-mock-choice">
                                        <input type="radio" name="difficulty" value="{{ $difficultyValue }}" {{ old('difficulty', 'medium') === $difficultyValue ? 'checked' : '' }}>
                                        <span class="sr-dashboard-mock-choice-icon" aria-hidden="true"><i class="fa-solid {{ $difficultyMeta['icon'] }}"></i></span>
                                        <span>{{ $difficultyMeta['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="sr-dashboard-mock-field">
                            <label class="sr-dashboard-coach-label" for="dashboardMockQuestions">Questions</label>
                            <select class="sr-dashboard-mock-control" name="num_questions" id="dashboardMockQuestions">
                                @foreach([1, 3, 5, 10, 15, 20, 25, 30] as $questionCount)
                                    <option value="{{ $questionCount }}" {{ (string) old('num_questions', 5) === (string) $questionCount ? 'selected' : '' }}>{{ $questionCount }} {{ $questionCount === 1 ? 'Question' : 'Questions' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sr-dashboard-mock-field">
                            <label class="sr-dashboard-coach-label">Response mode</label>
                            <div class="sr-dashboard-mock-choice-grid">
                                @foreach([
                                    'text' => ['label' => 'Text', 'icon' => 'fa-keyboard'],
                                    'voice' => ['label' => 'Voice', 'icon' => 'fa-microphone'],
                                    'hybrid' => ['label' => 'Hybrid', 'icon' => 'fa-pen-to-square'],
                                ] as $responseValue => $responseMeta)
                                    <label class="sr-dashboard-mock-choice">
                                        <input type="radio" name="response_mode" value="{{ $responseValue }}" {{ old('response_mode', 'voice') === $responseValue ? 'checked' : '' }}>
                                        <span class="sr-dashboard-mock-choice-icon" aria-hidden="true"><i class="fa-solid {{ $responseMeta['icon'] }}"></i></span>
                                        <span>{{ $responseMeta['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="sr-dashboard-mock-field">
                            <label class="sr-dashboard-coach-label" for="dashboardMockFeedbackMode">Feedback mode</label>
                            <select class="sr-dashboard-mock-control" name="live_feedback_mode" id="dashboardMockFeedbackMode">
                                <option value="coaching" {{ old('live_feedback_mode', 'coaching') === 'coaching' ? 'selected' : '' }}>Coaching On</option>
                                <option value="real_interview" {{ old('live_feedback_mode', 'coaching') === 'real_interview' ? 'selected' : '' }}>Real Interview Mode</option>
                            </select>
                        </div>
                    </div>

                    <div class="sr-dashboard-mock-field sr-dashboard-mock-field-full">
                        <label class="sr-dashboard-coach-label" id="dashboardMockQuestionTypesLabel">Question types</label>
                        <div class="sr-dashboard-mock-checks" role="group" aria-labelledby="dashboardMockQuestionTypesLabel">
                            @foreach(['Behavioral', 'Situational', 'Technical', 'Personal'] as $questionType)
                                <label class="sr-dashboard-mock-check">
                                    <input type="checkbox" name="question_types[]" value="{{ $questionType }}" {{ in_array($questionType, $dashboardMockQuestionTypes, true) ? 'checked' : '' }}>
                                    <strong>{{ $questionType }}</strong>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="sr-dashboard-mock-status" id="dashboardMockStatus" role="alert" hidden></div>
                    @unless($dashboardMockHasScenarios)
                        <div class="sr-dashboard-mock-empty" role="alert">No active interview scenarios are available. Ask an admin to activate a job interview category.</div>
                    @endunless
                </div>
                <div class="modal-footer">
                    <button type="button" class="sr-dashboard-coach-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="sr-dashboard-coach-submit" id="dashboardMockSubmit" {{ $dashboardMockHasScenarios ? '' : 'disabled' }}><i class="fa-solid fa-play"></i> Start Interview</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('shared.user.dashboard-setup-tools-modal')

@push('scripts')
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
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
        const isLightMode = getDashboardTheme() === 'light';

        return {
            isLightMode,
            txColor: isLightMode ? '#1e293b' : readThemeColor(rootStyle, '--tx', '#f8fafc'),
            mutedColor: isLightMode ? '#475569' : readThemeColor(rootStyle, '--tx2', '#dbeafe'),
            surfaceColor: isLightMode ? '#ffffff' : readThemeColor(rootStyle, '--sf', '#111827'),
            gridColor: isLightMode ? 'rgba(71,85,105,0.34)' : 'rgba(226,232,240,0.5)',
            trendLineColor: isLightMode ? '#1d4ed8' : '#93c5fd',
            trendPointFill: isLightMode ? '#ffffff' : '#0f172a'
        };
    };
    const createTrendGradient = (ctx, palette) => {
        const gradient = ctx.createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, palette.isLightMode ? 'rgba(37, 99, 235, 0.24)' : 'rgba(96, 165, 250, 0.28)');
        gradient.addColorStop(0.58, palette.isLightMode ? 'rgba(59, 130, 246, 0.10)' : 'rgba(96, 165, 250, 0.14)');
        gradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');
        return gradient;
    };
    const initialPalette = getDashboardChartPalette();
    const {
        isLightMode,
        txColor,
        mutedColor,
        surfaceColor,
        gridColor,
        trendLineColor,
        trendPointFill
    } = initialPalette;
    const isCompactTrend = () => window.matchMedia('(max-width: 575px)').matches;
    let progressChart = null;
    let progressCtx = null;

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

    };
});
</script>
@endpush

@push('scripts')
<script>
    (function() {
        const dashboardTargetOptions = @json([
            'job' => $dashboardJobPositionOptions,
        ]);

        function initDashboardMockModal() {
            const form = document.getElementById('dashboardMockForm');
            if (!form || form.dataset.bound === 'true') return;

            form.dataset.bound = 'true';

            const modal = document.getElementById('dashboardMockModal');
            const scenarioSelect = document.getElementById('dashboardMockCategory');
            const focusInput = document.getElementById('dashboardMockFocus');
            const positionInput = document.getElementById('dashboardMockPosition');
            const submitButton = document.getElementById('dashboardMockSubmit');
            const status = document.getElementById('dashboardMockStatus');
            const defaultSubmitHtml = submitButton ? submitButton.innerHTML : '';

            function syncTargetOptions(targetKind) {
                if (!positionInput) return;

                const previousKind = positionInput.dataset.targetKind || targetKind;
                const previousValue = previousKind === targetKind
                    ? String(positionInput.value || positionInput.dataset.selectedTarget || '').trim()
                    : '';
                const options = dashboardTargetOptions[targetKind] || [];
                positionInput.innerHTML = '';

                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = 'Choose a target position';
                placeholderOption.disabled = true;
                positionInput.appendChild(placeholderOption);

                options.forEach((optionValue) => {
                    const option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = optionValue;
                    positionInput.appendChild(option);
                });

                const hasPreviousValue = Array.from(positionInput.options).some((option) => option.value === previousValue);
                positionInput.value = previousValue && hasPreviousValue ? previousValue : '';
                placeholderOption.selected = positionInput.value === '';
                positionInput.dataset.selectedTarget = positionInput.value;
                positionInput.dataset.targetKind = targetKind;
            }

            function syncScenarioFields() {
                const selectedOption = scenarioSelect?.selectedOptions?.[0];
                if (!selectedOption) return;

                if (focusInput) {
                    focusInput.value = selectedOption.dataset.focus || 'Job Interview';
                }

                syncTargetOptions('job');
            }

            function setStatus(message) {
                if (!status) return;

                status.textContent = message || '';
                status.hidden = !message;
            }

            scenarioSelect?.addEventListener('change', () => {
                syncScenarioFields();
                setStatus('');
            });

            form.querySelectorAll('input[name="question_types[]"]').forEach((input) => {
                input.addEventListener('change', () => setStatus(''));
            });

            modal?.addEventListener('shown.bs.modal', () => {
                syncScenarioFields();
                positionInput?.focus();
            });

            form.addEventListener('submit', (event) => {
                syncScenarioFields();

                if (!scenarioSelect?.value) {
                    event.preventDefault();
                    setStatus('Choose a practice scenario before starting.');
                    scenarioSelect?.focus();
                    return;
                }

                const hasQuestionType = form.querySelectorAll('input[name="question_types[]"]:checked').length > 0;
                if (!hasQuestionType) {
                    event.preventDefault();
                    setStatus('Select at least one question type.');
                    form.querySelector('input[name="question_types[]"]')?.focus();
                    return;
                }

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.setAttribute('aria-busy', 'true');
                    submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Starting...';
                }
            });

            modal?.addEventListener('hidden.bs.modal', () => {
                setStatus('');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.setAttribute('aria-busy', 'false');
                    submitButton.innerHTML = defaultSubmitHtml;
                }
            });

            syncScenarioFields();
        }

        function initDashboardModals() {
            initDashboardMockModal();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardModals);
        } else {
            initDashboardModals();
        }
    })();
</script>
@endpush

@push('scripts')
<script>
    (function() {
        const providerDashboardBubbleMessages = @json($dashboardBubbleMessages ?? []);
        const fallbackDashboardBubbleMessages = [
            {
                line: 'You\'re ready to practice and succeed today!',
                action: 'Click the robot for AI Coach.',
            },
            {
                line: 'Warm up with one focused mock interview today.',
                action: 'Ask the coach for a prep plan.',
            },
            {
                line: 'Turn practice into progress one answer at a time.',
                action: 'Review feedback after each session.',
            },
            {
                line: 'Build confidence before the real interview.',
                action: 'Click the robot when you need help.',
            },
        ];
        let dashboardBubbleMessages = (Array.isArray(providerDashboardBubbleMessages) && providerDashboardBubbleMessages.length
            ? providerDashboardBubbleMessages
            : fallbackDashboardBubbleMessages
        ).map((message) => ({
            line: String(message?.line || '').trim(),
            action: String(message?.action || '').trim(),
        })).filter((message) => message.line && message.action);

        if (dashboardBubbleMessages.length < 2) {
            dashboardBubbleMessages = fallbackDashboardBubbleMessages;
        }

        function initDashboardBubbleMessages() {
            document.querySelectorAll('[data-sr-dashboard-bubble]').forEach((bubble) => {
                const line = bubble.querySelector('[data-sr-dashboard-bubble-line]');
                const action = bubble.querySelector('[data-sr-dashboard-bubble-action]');

                if (!line || !action || dashboardBubbleMessages.length < 2) return;

                let messageIndex = 0;
                const applyMessage = () => {
                    line.textContent = dashboardBubbleMessages[messageIndex].line;
                    action.textContent = dashboardBubbleMessages[messageIndex].action;
                };

                applyMessage();

                window.setInterval(() => {
                    messageIndex = (messageIndex + 1) % dashboardBubbleMessages.length;
                    applyMessage();
                }, 30000);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardBubbleMessages);
        } else {
            initDashboardBubbleMessages();
        }
    })();
</script>
@endpush

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const completionKey = null;
        let dashboardTourCompleted = @json((bool) ($onboardingState['dashboard_tour_completed'] ?? false));
        let dashboardTourSkipped = @json((bool) ($onboardingState['dashboard_tour_skipped'] ?? false));
        const onboardingProgressUrl = @json(route('user.onboarding.progress'));
        const serverDetectedMobile = false;
        const dashboardTourQueryPresent = new URLSearchParams(window.location.search).get('onboarding') === 'tour';
        const dashboardTourRequested = @json((bool) ($dashboardTourAutoStart ?? false));

        // Keep the registered tour scope aligned with the visible dashboard URL.
        if (dashboardTourQueryPresent) {
            const cleanUrl = new URL(window.location.href);
            cleanUrl.searchParams.delete('onboarding');
            window.history.replaceState(window.history.state, '', `${cleanUrl.pathname}${cleanUrl.search}${cleanUrl.hash}`);
        }

        async function saveDashboardTourMilestone(milestone) {
            const response = await fetch(onboardingProgressUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ milestone }),
            });

            if (!response.ok) throw new Error('Dashboard tutorial progress could not be saved.');

            const payload = await response.json().catch(() => ({}));
            if (payload.onboarding) {
                window.dispatchEvent(new CustomEvent('speakready:onboarding-updated', {
                    detail: payload.onboarding,
                }));
            }
        }

        async function saveDashboardTourCompletion() {
            await saveDashboardTourMilestone('dashboard_tour_completed');
            dashboardTourCompleted = true;
            dashboardTourSkipped = false;
        }

        async function saveDashboardTourSkipped() {
            await saveDashboardTourMilestone('dashboard_tour_skipped');
            dashboardTourSkipped = true;
        }

        const stepsMobile = [];

        const stepsDesktop = [
            { element: '#dbSidebar', popover: { title: 'Explore Practice', description: 'Open Interview Practice, Activity Calendar, Challenges, Modules, Interview Coach, Progress, Feedback, and Reports and Sessions.', side: 'right', align: 'start' }},
            { element: '#dashboardCoachImageTrigger', popover: { title: 'Ask the Interview Coach', description: 'Select the robot to open the coach for interview planning, answer feedback, and help with your next practice step.', side: 'bottom', align: 'center' }},
            { element: '.sr-score-panel', popover: { title: 'Readiness and Goal', description: 'Open Progress to review your overall readiness score, current status, and next goal.', side: 'bottom', align: 'start' }},
            { element: '.sr-stats-desktop', popover: { title: 'Practice Snapshot', description: 'These cards link to completed sessions, feedback ratings, XP, and your activity calendar.', side: 'top', align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Readiness Trend', description: 'Compare scores from your latest completed interviews, then open Progress for more detail.', side: 'top', align: 'start' }},
            { element: '#dbTutorialBtn', popover: { title: 'Replay This Tutorial', description: 'Select this button whenever you want to review the current page.', side: 'bottom', align: 'center' }},
            { element: '#dbThBtn', popover: { title: 'Display Theme', description: 'Switch between light and dark mode.', side: 'bottom', align: 'center' }},
            { element: '#dbFontSizeBtn', popover: { title: 'Reading Size', description: 'Adjust text size from 50% to 150%, or reset it to 100%.', side: 'bottom', align: 'center' }},
            { element: '#profileWrap', popover: { title: 'Account Settings', description: 'Open account management, notifications, language, font size settings, and sign out here.', side: 'bottom', align: 'end' }}
        ];

        const dashboardTour = window.createSpeakReadyTour({
            completionKey,
            serverDetectedMobile,
            stepsMobile,
            stepsDesktop,
            autoStart: false,
            initiallyCompleted: dashboardTourCompleted,
            isCompleted: () => dashboardTourCompleted,
            persistLocalCompletion: false,
            onCompleted: saveDashboardTourCompletion,
            onSkipped: saveDashboardTourSkipped,
        });

        const registrationAlertModal = document.getElementById('srFlashModal');
        const setupToolsModal = document.getElementById('dashboardSetupToolsModal');
        let tourStartScheduled = false;

        const isModalOpen = (element) => Boolean(element && (
            element.classList.contains('show') ||
            element.getAttribute('aria-hidden') === 'false'
        ));

        function startDashboardTourWhenReady() {
            if (!dashboardTourRequested || !dashboardTour || tourStartScheduled) return;

            const setupState = window.__speakReadyDashboardSetupState;
            if (isModalOpen(registrationAlertModal) || isModalOpen(setupToolsModal) || setupState?.pending) return;

            tourStartScheduled = true;
            window.setTimeout(() => {
                tourStartScheduled = false;

                if (isModalOpen(registrationAlertModal) || isModalOpen(setupToolsModal) || window.__speakReadyDashboardSetupState?.pending) {
                    return;
                }

                dashboardTour.start();
            }, 350);
        }

        registrationAlertModal?.addEventListener('hidden.bs.modal', startDashboardTourWhenReady);
        setupToolsModal?.addEventListener('hidden.bs.modal', startDashboardTourWhenReady);
        window.addEventListener('speakready:dashboard-setup-ready', startDashboardTourWhenReady);
        window.setTimeout(startDashboardTourWhenReady, 450);

    });
</script>
@endpush

@endsection
