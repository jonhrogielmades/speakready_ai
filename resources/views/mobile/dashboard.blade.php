@extends('mobile.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/dashboard.css?v=45') }}" data-page-style="dashboard-v45">
@endpush

@section('content')
@php
    $scoreVal = (int) round($profile->readiness_score ?? $avgScore ?? 0);
    $scoreVal = max(0, min(100, $scoreVal));
    $scoreClass = $scoreVal >= 80 ? 'score-high' : ($scoreVal >= 60 ? 'score-med' : 'score-low');
    $scoreText = $scoreVal >= 80 ? 'Interview Ready' : ($scoreVal >= 60 ? 'Boost' : 'Practice Mode');
    $mobileScoreText = $scoreVal >= 80 ? 'Interview Ready' : 'Boost';
    $scoreIcon = $scoreVal >= 80 ? 'fa-circle-check' : ($scoreVal >= 60 ? 'fa-chart-line' : 'fa-arrow-trend-up');
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
    $challengeTitle = $scoreVal >= 75 ? 'Sharpen 3 advanced advanced answers' : 'Answer 3 HR questions';
    $challengeCopy = $scoreVal >= 75
        ? 'Polish role-fit stories, metrics, and confident closing answers.'
        : 'Practice structure, confidence, and local role-fit responses.';
    $challengeXp = $hasTrendScores ? 60 : 40;
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
                        </div>
                    </div>
                    <div class="sr-image-speech" aria-hidden="true" data-sr-dashboard-bubble>
                        <strong>Hi! {{ $welcomeName }}</strong>
                        <span data-sr-dashboard-bubble-line>You're <span class="sr-image-speech-accent">ready</span> to practice and <span class="sr-image-speech-accent is-success">succeed</span> today!</span>
                        <span class="sr-image-speech-action" data-sr-dashboard-bubble-action>Tap the robot for AI Coach.</span>
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
                        <img src="{{ asset('img/dashboard-hero-robot-reference.png') }}" alt="" aria-hidden="true" width="1536" height="1024" loading="eager" fetchpriority="high" decoding="async" draggable="false">
                    </a>
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
                        <div class="sr-stat-value" title="{{ number_format($xpValue) }} XP">{{ $compactXpValue }}</div>
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
            <div class="sr-mobile-stat-grid sr-readiness-card-grid" role="group" aria-label="Readiness summary">
                <div class="sr-stat-card sr-readiness-stat-card" style="--accent:{{ $scoreVal >= 80 ? '#22c55e' : ($scoreVal >= 60 ? '#f59e0b' : '#ef4444') }};--meter-value:{{ $scoreVal }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid {{ $scoreIcon }}"></i></div>
                        <span class="sr-chip">{{ $mobileScoreText }}</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $scoreVal }}<span>%</span></div>
                        <div class="sr-stat-label">Overall readiness</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    </div>
                </div>
                <div class="sr-stat-card sr-readiness-stat-card" style="--accent:#3b82f6;--meter-value:{{ isset($upcomingGoal) ? ($upcomingGoal->target ?? 100) : 100 }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-bullseye"></i></div>
                        <span class="sr-chip">Goal</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ isset($upcomingGoal) ? ($upcomingGoal->target ?? 100) : 100 }}<span>%</span></div>
                        <div class="sr-stat-label">Next goal</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-bullseye"></i></div>
                    </div>
                </div>
            </div>

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
                        <div class="sr-stat-value" title="{{ number_format($xpValue) }} XP">{{ $compactXpValue }}</div>
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
                        <p class="sr-trend-subtitle">Recent completed interview sessions, scored from 0 to 100.</p>
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

        </main>

        <aside class="sr-side-stack">
            <section id="card-skill-radar" class="sr-card sr-card-pad sr-side-feature" style="--side-accent:#ec4899">
                <div class="sr-side-feature-header">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-solid fa-chart-simple"></i></div>
                        <div>
                            <h5 class="sr-side-title">Skill Chart</h5>
                            <p class="sr-side-subtitle">Average capability by skill.</p>
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
                        <p>Complete a scored interview to unlock your skill chart.</p>
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
                <a href="{{ route('interview.setup') }}" class="sr-btn sr-btn-primary w-100 sr-challenge-cta"><i class="fa-solid fa-play"></i> START INTERVIEW</a>
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

            <section id="card-achievements" class="sr-card sr-card-pad sr-side-feature sr-achievements-main" style="--side-accent:#f59e0b">
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
            ? (palette.isLightMode ? 'rgba(219, 39, 119, 0.72)' : 'rgba(244, 114, 182, 0.76)')
            : (palette.isLightMode ? 'rgba(219, 39, 119, 0.18)' : 'rgba(244, 114, 182, 0.24)'),
        borderColor: hasScores
            ? (palette.isLightMode ? '#be185d' : '#f9a8d4')
            : (palette.isLightMode ? 'rgba(190, 24, 93, 0.78)' : 'rgba(251, 207, 232, 0.94)'),
        hoverBackgroundColor: hasScores
            ? (palette.isLightMode ? '#be185d' : '#f9a8d4')
            : (palette.isLightMode ? 'rgba(219, 39, 119, 0.28)' : 'rgba(244, 114, 182, 0.34)'),
        hoverBorderColor: palette.isLightMode ? '#9d174d' : '#fbcfe8'
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
    const skillChartLabelSize = () => window.matchMedia('(max-width: 380px)').matches ? 9 : 10;
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
        window.addEventListener('resize', () => {
            window.clearTimeout(trendResizeTimer);
            trendResizeTimer = window.setTimeout(() => {
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
            }, 120);
        });
    }

    const radarCanvas = document.getElementById('radarChart');
    if (radarCanvas) {
        const radarScores = [
            {{ (int) ($radarData['clarity'] ?? 0) }},
            {{ (int) ($radarData['relevance'] ?? 0) }},
            {{ (int) ($radarData['grammar'] ?? 0) }},
            {{ (int) ($radarData['professionalism'] ?? 0) }}
        ];
        const hasRadarScores = radarScores.some((value) => Number(value) > 0);
        dashboardHasRadarScores = hasRadarScores;
        const radarDisplayScores = hasRadarScores ? radarScores : [0, 0, 0, 0];
        const radarColors = getRadarDatasetColors(hasRadarScores, initialPalette);

        radarChart = new Chart(radarCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Clarity', 'Relevance', 'Grammar', 'Professionalism'],
                datasets: [{
                    label: 'Score Level',
                    data: radarDisplayScores,
                    backgroundColor: radarColors.backgroundColor,
                    borderColor: radarColors.borderColor,
                    hoverBackgroundColor: radarColors.hoverBackgroundColor,
                    hoverBorderColor: radarColors.hoverBorderColor,
                    borderWidth: hasRadarScores ? 1.5 : 1,
                    borderRadius: 0,
                    borderSkipped: false,
                    maxBarThickness: 22,
                    categoryPercentage: 0.74,
                    barPercentage: 0.84
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 6, right: 10, bottom: 4, left: 0 } },
                plugins: {
                    legend: { display: false },
                    emptyChartMessage: {
                        color: mutedColor,
                        force: !hasRadarScores,
                        text: 'Complete scored interviews to build your skill chart.'
                    },
                    tooltip: {
                        backgroundColor: isLightMode ? '#ffffff' : 'rgba(15, 23, 42, 0.94)',
                        titleColor: isLightMode ? '#0f172a' : '#fff',
                        bodyColor: isLightMode ? '#334155' : '#dbeafe',
                        borderColor: radarColors.borderColor,
                        borderWidth: 1,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return ' Score Level: ' + context.parsed.x + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 25,
                            color: txColor,
                            font: { size: 10, weight: 700 },
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: { color: radarGridColor, lineWidth: radarGridWidth, drawTicks: false },
                        border: { display: false }
                    },
                    y: {
                        ticks: {
                            color: radarLabelColor,
                            font: { size: skillChartLabelSize(), weight: 900 }
                        },
                        grid: { display: false },
                        border: { display: false }
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
            radarChart.options.plugins.tooltip.backgroundColor = palette.isLightMode ? '#ffffff' : 'rgba(15, 23, 42, 0.94)';
            radarChart.options.plugins.tooltip.titleColor = palette.isLightMode ? '#0f172a' : '#fff';
            radarChart.options.plugins.tooltip.bodyColor = palette.isLightMode ? '#334155' : '#dbeafe';
            radarChart.options.plugins.tooltip.borderColor = radarColors.borderColor;
            radarChart.options.scales.x.ticks.color = palette.txColor;
            radarChart.options.scales.x.grid.color = palette.radarGridColor;
            radarChart.options.scales.x.grid.lineWidth = palette.radarGridWidth;
            radarChart.options.scales.y.ticks.color = palette.radarLabelColor;
            radarChart.options.scales.y.ticks.font.size = skillChartLabelSize();
            radarChart.update('none');
        }
    };
});
</script>
@endpush

@push('scripts')
@php
    $mobileDashboardBubbleMessages = collect($dashboardBubbleMessages ?? [])
        ->map(function ($message) {
            if (is_array($message) && isset($message['action']) && is_string($message['action']) && str_contains($message['action'], 'Click the robot')) {
                $message['action'] = str_replace('Click the robot', 'Tap the robot', $message['action']);
            }

            return $message;
        })
        ->values()
        ->all();
@endphp
<script>
    (function() {
        const providerDashboardBubbleMessages = @json($mobileDashboardBubbleMessages);
        const fallbackDashboardBubbleMessages = [
            {
                line: 'You\'re ready to practice and succeed today!',
                action: 'Tap the robot for AI Coach.',
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
                action: 'Tap the robot for AI Coach.',
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

        const completionKey = 'onboarding_completed';
        const serverDetectedMobile = true;

        const stepsMobile = [
            { element: '#mobTutorialBtn', popover: { title: 'Replay Tutorial', description: 'Use this anytime you want a quick walkthrough of the current page.', side: 'bottom', align: 'end' }},
            { element: '#mob-bottom-nav', popover: { title: 'Mobile Navigation', description: 'Jump to Home, Progress, Interview, Feedback, or More from the bottom bar.', side: 'top', align: 'center' }},
            { element: '.sr-readiness-card-grid', popover: { title: 'Readiness Summary', description: 'Your readiness score, status, and next target are practice indicators for your current preparation.', side: 'bottom', align: 'start' }},
            { element: '.sr-mobile-stat-grid:not(.sr-readiness-card-grid)', popover: { title: 'Practice Snapshot', description: 'Track interviews, ratings, XP, and streaks without opening a report.', side: 'top', align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Readiness Trend', description: 'See how your score changes across your latest completed sessions.', side: 'top', align: 'start' }},
            { element: '#card-daily-challenge', popover: { title: "Today's Challenge", description: 'Start a focused interview task for XP, streak progress, and sharper answer structure.', side: 'top', align: 'start' }},
            { element: '#mobThBtn', popover: { title: 'Theme Toggle', description: 'Switch between light and dark mode for a comfortable view.', side: 'bottom', align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#dbSidebar', popover: { title: 'Practice Navigation', description: 'Open Mock Interview, Modules, Challenges, AI Coach, Progress, Feedback, and Reports.', side: 'right', align: 'start' }},
            { element: '#dbTutorialBtn', popover: { title: 'Replay Tutorial', description: 'Restart this walkthrough whenever the page changes or you want a quick orientation.', side: 'bottom', align: 'center' }},
            { element: '.sr-score-panel', popover: { title: 'Readiness Summary', description: 'Your readiness score, status, average rating, and next target are practice indicators for your current preparation.', side: 'bottom', align: 'start' }},
            { element: '.sr-stats-desktop', popover: { title: 'Practice Snapshot', description: 'Track completed interviews, ratings, XP, streaks, and active practice days at a glance.', side: 'top', align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Readiness Trend', description: 'See how your score changes across your latest completed sessions.', side: 'top', align: 'start' }},
            { element: '#card-daily-challenge', popover: { title: "Today's Challenge", description: 'Start a focused interview task for XP, streak progress, and sharper answer structure.', side: 'left', align: 'start' }},
            { element: '#dbThBtn', popover: { title: 'Theme Toggle', description: 'Switch between light and dark mode for a comfortable viewing experience.', side: 'bottom', align: 'center' }},
            { element: '#profileWrap', popover: { title: 'Account And Language', description: 'Manage profile settings, language translation, notifications, and sign-out options.', side: 'bottom', align: 'end' }}
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
