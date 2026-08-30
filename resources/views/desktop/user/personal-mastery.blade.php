@extends('desktop.layouts.app')
@section('title', 'Philippines Personal Mastery')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/personal-mastery.css?v=6') }}" data-page-style="user-personal-mastery">
@endpush

@section('content')
@php
    $hasMasteryScores = $latest !== null && $baseline !== null;
    $growth = $hasMasteryScores ? $latest - $baseline : null;
    $masteryStatRows = [
        ['Personal best', $personalBest === null ? 'N/A' : $personalBest.'%', 'fa-trophy', '#f59e0b'],
        ['Latest assessed', $latest === null ? 'N/A' : $latest.'%', 'fa-bullseye', '#3b82f6'],
        ['Growth from baseline', $growth === null ? 'N/A' : (($growth >= 0 ? '+' : '').$growth.' pts'), $growth === null || $growth >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down', $growth === null ? '#64748b' : ($growth >= 0 ? '#10b981' : '#ef4444')],
        ['Practice streak', ($profile->current_streak ?? 0).' days', 'fa-fire', '#ef4444'],
    ];
@endphp

<div class="db-section active" id="personal-mastery-page">
    <div class="mastery-hero-card">
        <div class="mastery-copy">
            <div class="mastery-badge" aria-hidden="true">
                <i class="fa-solid fa-trophy fa-xl"></i>
            </div>
            <div>
                <h5 class="mastery-title">Philippines <span>Personal Mastery</span></h5>
                <p class="mastery-subtitle">Track private interview growth without public rankings.</p>
            </div>
        </div>
        <div class="mastery-visual" aria-hidden="true">
            <svg viewBox="0 0 260 170" role="img">
                <defs>
                    <linearGradient id="masteryPanel" x1="40" y1="18" x2="212" y2="142" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#fff7e6"/>
                        <stop offset="1" stop-color="#dcebff"/>
                    </linearGradient>
                    <linearGradient id="masteryShield" x1="104" y1="52" x2="164" y2="118" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#263c62"/>
                        <stop offset="1" stop-color="#1662b8"/>
                    </linearGradient>
                    <filter id="masteryShadow" x="0" y="0" width="260" height="170" filterUnits="userSpaceOnUse">
                        <feDropShadow dx="0" dy="10" stdDeviation="10" flood-color="#3266bc" flood-opacity=".2"/>
                    </filter>
                </defs>
                <rect x="31" y="28" width="205" height="111" rx="23" fill="url(#masteryPanel)" stroke="#b6d2fb" stroke-width="2" filter="url(#masteryShadow)" transform="rotate(3 133.5 83.5)"/>
                <path d="M111 58h54v31c0 19-14 36-27 43-14-7-27-24-27-43V58Z" fill="url(#masteryShield)"/>
                <path d="M121 70h34v18c0 11-8 21-17 26-9-5-17-15-17-26V70Z" fill="#fff"/>
                <path d="m130 91 8 8 18-24" fill="none" stroke="#fb9700" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M51 124h126" stroke="#8ab5ff" stroke-width="10" stroke-linecap="round" opacity=".55"/>
                <rect x="58" y="83" width="17" height="39" rx="9" fill="#1598ef"/>
                <rect x="84" y="65" width="17" height="57" rx="9" fill="#15bce9"/>
                <rect x="194" y="58" width="18" height="68" rx="9" fill="#36d56f"/>
                <circle cx="62" cy="46" r="16" fill="#fb9700"/>
                <circle cx="213" cy="48" r="15" fill="#2f80ed"/>
                <path d="M26 155c39-17 77-18 118-3 31 12 67 12 92-4" fill="none" stroke="#78a7ff" stroke-width="10" stroke-linecap="round" opacity=".55"/>
            </svg>
        </div>
    </div>
    <div class="mastery-action-row">
        <a class="mastery-progress-btn" href="{{ route('user.progress') }}">
            <i class="fa-solid fa-chart-line"></i>
            <span>Open Philippines Progress</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>
    </div>
    <div class="mastery-stats-grid">
        @foreach($masteryStatRows as [$label,$value,$icon,$color])
            <div>
                <div class="mastery-stat-card" style="--mastery-stat-color: {{ $color }}">
                    <div class="mastery-stat-icon">
                        <i class="fa-solid {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="fw-bold mastery-stat-value">{{ $value }}</div>
                        <div class="mastery-stat-label">{{ $label }}</div>
                    </div>
                    <i class="fa-solid {{ $icon }} mastery-stat-watermark" aria-hidden="true"></i>
                </div>
            </div>
        @endforeach
    </div>

    <section class="mastery-next-action" id="mastery-next-action">
        <span class="mastery-next-icon" aria-hidden="true">
            <i class="fa-solid {{ $nextBestAction['icon'] }}"></i>
        </span>
        <div>
            <p class="mastery-kicker">{{ $nextBestAction['eyebrow'] }}</p>
            <h6>{{ $nextBestAction['title'] }}</h6>
            <p>{{ $nextBestAction['body'] }}</p>
        </div>
        <a class="mastery-pill-link" href="{{ $nextBestAction['href'] }}">
            <span>{{ $nextBestAction['cta'] }}</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>
    </section>

    <div class="mastery-section-grid">
        <section class="mastery-panel" id="mastery-drills">
            <div class="mastery-panel-head">
                <div>
                    <p class="mastery-kicker">Weakness to drill</p>
                    <h6>Recommended practice</h6>
                </div>
                <a class="mastery-mini-link" href="{{ route('user.drills.voice') }}">
                    <i class="fa-solid fa-ear-listen"></i>
                    <span>Voice</span>
                </a>
            </div>
            <div class="mastery-list">
                @foreach($weaknessDrills as $drill)
                    <div class="mastery-list-row">
                        <span class="mastery-row-icon" aria-hidden="true">
                            <i class="fa-solid {{ $drill['icon'] }}"></i>
                        </span>
                        <div>
                            <strong class="mastery-row-title">{{ $drill['title'] }}</strong>
                            <p>{{ $drill['reason'] ?? $drill['body'] }}</p>
                        </div>
                        <a class="mastery-mini-link" href="{{ $drill['href'] }}">{{ $drill['cta'] }}</a>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mastery-panel" id="mastery-tracks">
            <div class="mastery-panel-head">
                <div>
                    <p class="mastery-kicker">Philippines tracks</p>
                    <h6>Career track mastery</h6>
                </div>
                <span class="mastery-score-chip">{{ count($careerTracks) }}</span>
            </div>
            <div class="mastery-list">
                @foreach($careerTracks as $track)
                    <a class="mastery-track-row" href="{{ $track['href'] }}" style="--track-progress: {{ min(100, max(0, $track['best'])) }}%; text-decoration:none;color:inherit;">
                        <span class="mastery-track-icon" aria-hidden="true">
                            <i class="fa-solid {{ $track['icon'] }}"></i>
                        </span>
                        <div>
                            <strong class="mastery-track-title">{{ $track['label'] }}</strong>
                            <span class="mastery-track-meta">{{ $track['status'] }} - {{ $track['attempts'] }} scored</span>
                            <span class="mastery-track-progress" aria-hidden="true"><span class="mastery-track-fill"></span></span>
                        </div>
                        <span class="mastery-score-chip">{{ $track['best'] }}%</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    <div class="mastery-section-grid">
        <section class="mastery-panel" id="mastery-story-bank">
            <div class="mastery-panel-head">
                <div>
                    <p class="mastery-kicker">STAR answer bank</p>
                    <h6>Save truthful proof stories</h6>
                </div>
                <span class="mastery-score-chip">{{ $storyCount }}</span>
            </div>

            @if($errors->has('star_story'))
                <p class="text-danger mb-2" style="font-size:0.72rem;font-weight:800;">{{ $errors->first('star_story') }}</p>
            @endif
            @if($errors->any() && ! $errors->has('star_story'))
                <div class="mastery-form-alert" role="alert">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('user.mastery.stories.store') }}" class="mb-3" id="masteryStoryForm">
                @csrf
                <div class="mastery-form-grid">
                    <label class="mastery-field">
                        <span>Track</span>
                        <select class="mastery-select" name="track">
                            @foreach($careerTracks as $track)
                                <option value="{{ $track['key'] }}" @selected(old('track') === $track['key'])>{{ $track['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="mastery-field">
                        <span>Question</span>
                        <input class="mastery-input" name="question" value="{{ old('question') }}" maxlength="220" placeholder="Example: Tell me about a challenge">
                    </label>
                    <label class="mastery-field">
                        <span>Situation</span>
                        <textarea class="mastery-textarea" name="situation" maxlength="1500" placeholder="Where and when did it happen?">{{ old('situation') }}</textarea>
                    </label>
                    <label class="mastery-field">
                        <span>Task</span>
                        <textarea class="mastery-textarea" name="story_task" maxlength="1500" placeholder="What responsibility did you have?">{{ old('story_task') }}</textarea>
                    </label>
                    <label class="mastery-field">
                        <span>Action</span>
                        <textarea class="mastery-textarea" name="action" maxlength="1500" placeholder="What did you personally do?">{{ old('action') }}</textarea>
                    </label>
                    <label class="mastery-field">
                        <span>Result</span>
                        <textarea class="mastery-textarea" name="result" maxlength="1500" placeholder="What changed, improved, or what did you learn?">{{ old('result') }}</textarea>
                    </label>
                </div>
                <button class="mastery-pill-link" type="submit">
                    <i class="fa-regular fa-bookmark"></i>
                    <span>Save STAR story</span>
                </button>
            </form>

            <div class="mastery-list">
                @forelse($storyBank as $story)
                    @php($storyMeta = $story->metadata ?? [])
                    <div class="mastery-story-row">
                        <div>
                            <strong>{{ $story->title }}</strong>
                            <span class="mastery-story-meta">{{ ucwords(str_replace('_', ' ', data_get($storyMeta, 'track', 'general'))) }}</span>
                            <p class="mt-1">
                                {{ \Illuminate\Support\Str::limit(data_get($storyMeta, 'result') ?: data_get($storyMeta, 'action') ?: $story->task, 150) }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('user.mastery.stories.destroy', $story) }}" data-sr-confirm-form data-sr-confirm-title="Remove STAR story" data-sr-confirm-message="This will delete this saved proof story from Personal Mastery." data-sr-confirm-action="Remove Story" data-sr-confirm-variant="danger">
                            @csrf
                            @method('DELETE')
                            <button class="mastery-icon-button" type="submit" aria-label="Delete story">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="mastery-story-row">
                        <div>
                            <strong>No saved stories yet</strong>
                            <p class="mt-1">Start with one real school, OJT, work, freelance, family business, or volunteer experience.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </section>

        <div class="mastery-stack">
            <section class="mastery-panel" id="mastery-weekly-review">
                <div class="mastery-panel-head">
                    <div>
                        <p class="mastery-kicker">Weekly review</p>
                        <h6>{{ $weeklyReview['label'] }}</h6>
                    </div>
                    <a class="mastery-mini-link" href="{{ $weeklyReview['focus_href'] }}">Focus</a>
                </div>
                <div class="mastery-review-grid">
                    @foreach([
                        ['Assessments', $weeklyReview['assessments']],
                        ['Voice drills', $weeklyReview['voice_drills']],
                        ['Stories saved', $weeklyReview['stories']],
                        ['Prep done', $weeklyReview['completed_prep']],
                    ] as [$label, $value])
                        <div class="mastery-review-stat">
                            <span class="mastery-review-value">{{ $value }}</span>
                            <p>{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-2">Next focus: <strong style="color:var(--mastery-shell-title,var(--tx));">{{ $weeklyReview['focus'] }}</strong></p>
            </section>

            <section class="mastery-panel" id="mastery-coach-shortcuts">
                <div class="mastery-panel-head">
                    <div>
                        <p class="mastery-kicker">Taglish coach</p>
                        <h6>Quick coach shortcuts</h6>
                    </div>
                    <a class="mastery-mini-link" href="{{ route('user.coach') }}">Open</a>
                </div>
                <div class="mastery-shortcut-grid">
                    @foreach($coachShortcuts as $shortcut)
                        <a class="mastery-shortcut" href="{{ route('user.coach', ['ask' => $shortcut['prompt']]) }}">
                            <span class="mastery-row-icon" aria-hidden="true">
                                <i class="fa-solid {{ $shortcut['icon'] }}"></i>
                            </span>
                            <span>{{ $shortcut['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <div class="mastery-section-grid">
        <section class="mastery-panel" id="mastery-checklist">
            <div class="mastery-panel-head">
                <div>
                    <p class="mastery-kicker">Interview readiness</p>
                    <h6>Philippines prep checklist</h6>
                </div>
                <span class="mastery-score-chip">{{ $checklistItems->whereNotNull('completed_at')->count() }}/{{ $checklistItems->count() }}</span>
            </div>
            <div class="mastery-list">
                @foreach($checklistItems as $item)
                    <form method="POST" action="{{ route('user.mastery.checklist.toggle', $item) }}" class="mastery-check-row {{ $item->completed_at ? 'done' : '' }}">
                        @csrf
                        <button class="mastery-check-button" type="submit" aria-label="Toggle {{ $item->title }}">
                            <i class="{{ $item->completed_at ? 'fa-solid fa-check' : 'fa-regular fa-circle' }}"></i>
                        </button>
                        <div>
                            <strong class="mastery-row-title">{{ $item->title }}</strong>
                            <p>{{ $item->task }}</p>
                        </div>
                    </form>
                @endforeach
            </div>
        </section>

        <section class="mastery-panel" id="mastery-badges">
            <div class="mastery-panel-head">
                <div>
                    <p class="mastery-kicker">Personal best badges</p>
                    <h6>Private milestones</h6>
                </div>
                <span class="mastery-score-chip">{{ collect($masteryBadges)->where('earned', true)->count() }}/{{ count($masteryBadges) }}</span>
            </div>
            <div class="mastery-badge-grid">
                @foreach($masteryBadges as $badge)
                    <div class="mastery-badge-item {{ $badge['earned'] ? 'earned' : '' }}">
                        <span class="mastery-badge-icon" aria-hidden="true">
                            <i class="fa-solid {{ $badge['icon'] }}"></i>
                        </span>
                        <div>
                            <strong class="mastery-row-title">{{ $badge['label'] }}</strong>
                            <p>{{ $badge['earned'] ? 'Unlocked' : 'Keep practicing' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="mastery-info-card">
        <div>
            <div class="mastery-info-heading">
                <span class="mastery-info-icon"><i class="fa-solid fa-info"></i></span>
                <h5>What counts here?</h5>
            </div>
            <p>Only score-eligible interview assessments count here. Coached practice stays in history but does not change your mastery baseline.</p>
            <a class="mastery-progress-btn mastery-progress-btn-mobile" href="{{ route('user.progress') }}">
                <i class="fa-solid fa-chart-line"></i>
                <span>Open Philippines Progress</span>
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
        <svg class="mastery-info-art" viewBox="0 0 130 150" aria-hidden="true">
            <path d="M26 22c15-18 48-24 77-8" fill="none" stroke="currentColor" stroke-width="8" opacity=".18" stroke-linecap="round"/>
            <rect x="30" y="30" width="78" height="104" rx="13" fill="#eef5ff" stroke="currentColor" stroke-width="5"/>
            <rect x="55" y="24" width="28" height="16" rx="6" fill="currentColor"/>
            <path d="m45 61 8 8 15-17M45 88l8 8 15-17M45 115l8 8 15-17" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M78 64h21M78 91h21M78 118h21" stroke="currentColor" stroke-width="5" stroke-linecap="round" opacity=".45"/>
        </svg>
    </div>
</div>
@endsection
