@extends('desktop.layouts.app')
@section('title', 'Philippines Interview Challenges')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/learning.css?v=1') }}" data-page-style="user-learning">
<link rel="stylesheet" href="{{ asset('css/desktop/user/learning-2.css?v=2') }}" data-page-style="user-learning-2">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')
@php
    $gameResult = session('game_result');
@endphp

<div class="db-section active" id="learning-games-page">
    <!-- Header & Navigation -->
    <div class="sr-page-hero sr-learning-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="learning-hero-icon"><i class="fa-solid fa-gamepad"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 15h10l2 3a2 2 0 0 0 3-2l-1-5a6 6 0 0 0-6-5H9a6 6 0 0 0-6 5l-1 5a2 2 0 0 0 3 2l2-3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 11h4M10 9v4M16 10h.01M18 13h.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Philippines Interview Challenges
                    </h4>
                    <p class="sr-page-hero-subtitle">Complete interview challenges, earn XP, and build practical answer skills.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="gamesPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="gamesBlue" x1="58" y1="40" x2="166" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#gamesPanel)" stroke="#BFDBFE" stroke-width="3"/><path d="M67 84c5-26 18-36 43-36s38 10 43 36l4 22c2 12-11 18-18 8l-10-14H91l-10 14c-7 10-20 4-18-8l4-22Z" fill="url(#gamesBlue)"/><path d="M82 80h23M94 69v23M132 74h.01M146 88h.01" stroke="#EFF6FF" stroke-width="7" stroke-linecap="round"/><circle cx="164" cy="43" r="17" fill="#F59E0B"/><path d="m164 33 3 7 8 1-6 5 2 8-7-4-7 4 2-8-6-5 8-1 3-7Z" fill="#fff"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="sr-page-actions learning-actions">
        <div id="tour-search" class="db-top-search" style="width:100%; max-width:300px; background:var(--bg3);border:1px solid var(--bd); margin:0; border-radius:12px; padding:10px 16px;">
            <i class="fa-solid fa-magnifying-glass" style="color:var(--tx3)"></i>
            <input type="text" id="learningSearchInput" placeholder="Search challenges, skills, scenarios..." style="width:100%; background:transparent; border:none; color:var(--tx); outline:none;">
        </div>
        <div class="learning-mobile-control-row">
            <div class="learning-category-select-wrap">
                <select id="learningCategorySelect" class="learning-category-select" aria-label="Select challenge path">
                    @foreach($categories as $category)
                        <option value="{{ route('user.learning', ['category_id' => $category->id]) }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div id="nav-pills-container" class="mb-4 pb-2 d-flex flex-wrap gap-2">
        @foreach($categories as $category)
            <a href="{{ route('user.learning', ['category_id' => $category->id]) }}" class="ll-nav-pill {{ request('category_id') == $category->id ? 'active' : '' }}" style="margin:0;"><i class="fa-solid fa-folder"></i> {{ $category->title }}</a>
        @endforeach
    </div>

    <!-- Gamified HUD Stats -->
    <div id="dashboard-stats" class="row g-4 mb-4">
        <!-- Player Level & XP -->
        <div class="col-12 col-sm-6 col-lg-3 animate-fade-up" style="animation-delay: 0.1s">
            <div class="ll-stat-card" style="display:flex; flex-direction:column; justify-content:center; height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-weight:800; color:var(--tx); font-size:1.1rem;"><i class="fa-solid fa-crown text-warning me-2"></i> LEVEL {{ $profile?->player_level ?? 1 }}</span>
                    <span style="font-size:0.75rem; color:var(--tx3); font-weight:700; background:var(--bg3); padding:3px 8px; border-radius:6px;">{{ ($profile?->player_level ?? 1) >= 5 ? 'GOLD' : (($profile?->player_level ?? 1) >= 3 ? 'SILVER' : 'BRONZE') }}</span>
                </div>
                <div class="ll-progress-bar" style="height:12px; background:var(--bd); border-radius:6px; margin:5px 0;">
                    @php 
                        $xp = $profile?->experience_points ?? 0;
                        $nextLevelXp = ($profile?->player_level ?? 1) * 1000;
                        $percent = min(100, ($xp / $nextLevelXp) * 100);
                    @endphp
                    <div class="ll-progress-fill" style="width:{{ $percent }}%; background:linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);"></div>
                </div>
                <div style="font-size:0.75rem; color:var(--tx3); font-weight:700; text-align:right;">{{ number_format($xp) }} / {{ number_format($nextLevelXp) }} XP</div>
            </div>
        </div>
        
        <!-- Energy/Lives -->
        @php
            $maxEnergy = \App\Models\Profile::MAX_ENERGY;
            $currentEnergy = $profile?->energy ?? $maxEnergy;
        @endphp
        <div class="col-12 col-sm-6 col-lg-3 animate-fade-up" style="animation-delay: 0.2s">
            <div class="ll-stat-card d-flex align-items-center gap-3" style="height:100%;">
                <div style="width:55px; height:55px; border-radius:15px; background:rgba(239,68,68,0.1); color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                    <i class="fa-solid fa-heart"></i>
                </div>
                <div style="text-align:left;">
                    <div class="ll-stat-val" style="font-size:1.5rem; margin:0; font-weight:800;">{{ $currentEnergy }} <span style="font-size:1rem; color:var(--tx3);">/ {{ $maxEnergy }}</span></div>
                    <div style="font-size:0.8rem; color:var(--tx3); font-weight:700; text-transform:uppercase">Energy</div>
                </div>
            </div>
        </div>

        <!-- Streak -->
        <div class="col-12 col-sm-6 col-lg-3 animate-fade-up" style="animation-delay: 0.3s">
            <div class="ll-stat-card d-flex align-items-center gap-3" style="height:100%;">
                <div style="width:55px; height:55px; border-radius:15px; background:rgba(245,158,11,0.1); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                    <i class="fa-solid fa-fire"></i>
                </div>
                <div style="text-align:left;">
                    <div class="ll-stat-val" style="font-size:1.5rem; margin:0; font-weight:800;">{{ $profile?->current_streak ?? 0 }} <span style="font-size:1rem; color:var(--tx3);">Days</span></div>
                    <div style="font-size:0.8rem; color:var(--tx3); font-weight:700; text-transform:uppercase">Combo Streak</div>
                </div>
            </div>
        </div>

        <!-- Score/Accuracy -->
        <div class="col-12 col-sm-6 col-lg-3 animate-fade-up" style="animation-delay: 0.4s">
            <div class="ll-stat-card d-flex align-items-center gap-3" style="height:100%;">
                <div style="width:55px; height:55px; border-radius:15px; background:rgba(52,211,153,0.1); color:#34d399; display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <div style="text-align:left;">
                    @php $avgScore = $gameProgress && $gameProgress->count() > 0 ? round($gameProgress->avg('best_score')) : 0; @endphp
                    <div class="ll-stat-val" style="font-size:1.5rem; margin:0; font-weight:800;">{{ $avgScore }}%</div>
                    <div style="font-size:0.8rem; color:var(--tx3); font-weight:700; text-transform:uppercase">Accuracy</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            
            <div class="journey-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="journey-title" style="margin:0">Challenge Journey</h5>
                <div class="journey-header-actions">
                    <a id="btn-skill-tree" href="{{ route('user.skills') }}" class="btn btn-sm journey-skill-tree-btn d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-tree me-1"></i> <span>Skill Tree</span></a>
                    <span class="badge journey-lives"><i class="fa-solid fa-heart me-1" style="color:#ef4444"></i> {{ $currentEnergy }} / {{ $maxEnergy }} Lives</span>
                </div>
            </div>

            <div class="level-path-container" id="modules-list">
                @if(session('error') && ! $gameResult)
                    <div class="learning-notice learning-notice-danger" role="alert">
                        <span class="learning-notice-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <span class="learning-notice-message">{{ session('error') }}</span>
                    </div>
                @endif
                @if(session('success') && ! $gameResult)
                    <div class="learning-notice learning-notice-success" role="status">
                        <span class="learning-notice-icon"><i class="fa-solid fa-circle-check"></i></span>
                        <span class="learning-notice-message">{{ session('success') }}</span>
                    </div>
                @endif
                
                <!-- Path Line -->
                <div class="level-path-line">
                    @php 
                        $completedCount = $gameProgress ? $gameProgress->where('status', 'completed')->count() : 0;
                        $totalLevels = $gameLevels ? $gameLevels->count() : 1;
                        $pathPercent = min(100, ($completedCount / max(1, $totalLevels)) * 100);
                    @endphp
                    <div class="level-path-line-progress" style="height: {{ $pathPercent }}%;"></div>
                </div>

                @if($gameLevels && $gameLevels->count() > 0)
                    @php $catPassed = []; @endphp
                    @foreach($gameLevels as $level)
                        @php
                            if (!isset($catPassed[$level->category_id])) {
                                $catPassed[$level->category_id] = true; // First level in any category is unlocked
                            }
                            
                            $prog = $gameProgress ? $gameProgress->get($level->id) : null;
                            $isCompleted = $prog && $prog->best_score >= $level->required_score;
                            
                            if ($isCompleted) {
                                $status = 'completed';
                                $catPassed[$level->category_id] = true; // Next level in this category will be unlocked
                            } else {
                                if ($catPassed[$level->category_id]) {
                                    $status = 'active';
                                    $catPassed[$level->category_id] = false; // Next ones in this category will be locked
                                } else {
                                    $status = 'locked';
                                }
                            }

                            // Explicit prerequisite overrides (if set)
                            if ($level->prerequisite_level_id && $status === 'active') {
                                $prereqProg = $gameProgress->get($level->prerequisite_level_id);
                                $prereqLevel = $gameLevels->where('id', $level->prerequisite_level_id)->first();
                                if (!$prereqProg || $prereqProg->best_score < ($prereqLevel ? $prereqLevel->required_score : 80)) {
                                    $status = 'locked';
                                    $catPassed[$level->category_id] = false;
                                }
                            }

                            if ($level->is_hidden && $status === 'locked') {
                                continue;
                            }
                            
                            $score = $prog ? $prog->best_score : 0;
                            $successChecklist = $level->guidance_checklist;
                            $lockedArtIcons = ['fa-lightbulb', 'fa-comment-dots', 'fa-chalkboard-user', 'fa-trophy'];
                            $lockedArtIcon = $lockedArtIcons[$loop->index % count($lockedArtIcons)];
                            $levelSearchText = strtolower(implode(' ', array_filter([
                                'level ' . $level->level_number,
                                $level->title,
                                $level->description,
                                $level->skill_focus,
                                $level->learning_objective,
                                $level->target_tone,
                                $level->custom_badge_name,
                                $selectedCategory?->title,
                                $status,
                            ])));

                            $nodeClass = '';
                            $iconHtml = '';
                            if($status === 'completed') {
                                $nodeClass = 'completed';
                                $iconHtml = '<i class="fa-solid fa-check"></i>';
                            } elseif ($status === 'active') {
                                $nodeClass = 'active';
                                $iconHtml = $level->level_number;
                            } else {
                                $nodeClass = 'locked';
                                $iconHtml = '<i class="fa-solid fa-lock"></i>';
                            }
                        @endphp

                        <div class="level-node {{ $nodeClass }} animate-fade-up" data-search-text="{{ $levelSearchText }}" style="animation-delay: {{ $loop->index * 0.1 }}s">
                            <div class="level-icon-wrapper">
                                <div class="level-icon">{!! $iconHtml !!}</div>
                            </div>
                            <div class="level-card">
                                <div class="{{ $status === 'locked' ? 'locked-card-main' : '' }}">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <div style="font-size:0.75rem;color:{{ $status === 'completed' ? '#34d399' : ($status === 'active' ? 'var(--pur)' : 'var(--tx3)') }};font-weight:700;margin-bottom:5px;text-transform:uppercase">Level {{ $level->level_number }}</div>
                                        <h5 style="color:var(--tx);font-weight:700;margin:0">{{ $level->title }}</h5>
                                        @if($status === 'locked')
                                            <div class="locked-status-pill"><i class="fa-solid fa-lock"></i> Locked</div>
                                        @endif
                                    </div>
                                    @if($status === 'completed')
                                        <div class="score-badge"><i class="fa-solid fa-star"></i> Score: {{ $score }}%</div>
                                    @elseif($status === 'active')
                                        <div class="requirement-badge"><i class="fa-solid fa-bullseye"></i> Goal: {{ $level->required_score }}%+</div>
                                    @elseif($status !== 'locked')
                                        <div class="requirement-badge" style="background:var(--bg3);color:var(--tx3)"><i class="fa-solid fa-lock"></i> Locked</div>
                                    @endif
                                </div>
                                
                                <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:10px;line-height:1.5">{{ $level->description }}</p>

                                @if($level->skill_focus || $level->learning_objective)
                                    <div style="background:rgba(59,130,246,0.07);border:1px solid rgba(59,130,246,0.18);border-radius:10px;padding:12px;margin-bottom:12px;">
                                        @if($level->skill_focus)
                                            <div style="font-size:0.78rem;color:#38bdf8;font-weight:800;text-transform:uppercase;letter-spacing:0;margin-bottom:4px;"><i class="fa-solid fa-bullseye me-1"></i>{{ $level->skill_focus }}</div>
                                        @endif
                                        @if($level->learning_objective)
                                            <div style="font-size:0.84rem;color:var(--tx2);line-height:1.45;">{{ $level->learning_objective }}</div>
                                        @endif
                                    </div>
                                @endif
                                
                                @if($status === 'active' || $status === 'completed')
                                    <div class="d-flex flex-wrap gap-2 learning-badge-row {{ $status === 'active' ? 'learning-badge-row-active' : '' }}">
                                        @if($level->skill_focus)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-graduation-cap text-info me-1"></i> {{ $level->skill_focus }}</span>
                                        @endif
                                        @if($level->time_limit_seconds)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-clock text-danger me-1"></i> {{ $level->time_limit_seconds }}s</span>
                                        @endif
                                        @if($level->banned_words)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);" title="{{ $level->banned_words }}"><i class="fa-solid fa-ban text-danger me-1"></i> Banned Words</span>
                                        @endif
                                        @if($level->target_tone)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-face-smile text-success me-1"></i> {{ $level->target_tone }}</span>
                                        @endif
                                        @if($level->custom_badge_name)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-medal text-primary me-1"></i> {{ $level->custom_badge_name }}</span>
                                        @endif
                                        @if($level->skill_xp_amount > 0)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-bolt text-warning me-1"></i> +{{ $level->skill_xp_amount }} {{ $level->skill_xp_type }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($status === 'active')
                                    <div class="active-challenge-panel" style="background:var(--bg3);border-radius:10px;padding:15px;margin-bottom:20px;border:1px solid var(--bd)">
                                        <div style="font-size:0.85rem;color:var(--tx2);font-weight:600;margin-bottom:5px"><i class="fa-solid fa-list-check me-1 text-info"></i> Contains {{ count($level->parsed_questions) }} Questions</div>
                                        @if($successChecklist)
                                            <div style="margin-top:12px;">
                                                <div style="font-size:0.78rem;color:var(--tx3);font-weight:700;margin-bottom:6px;">Success checklist</div>
                                                @foreach($successChecklist as $criterion)
                                                    <div style="font-size:0.78rem;color:var(--tx2);line-height:1.4;margin-bottom:4px;"><i class="fa-solid fa-check text-success me-1"></i>{{ $criterion }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($score > 0 && ! $isCompleted)
                                            <div style="margin-top:12px;font-size:0.78rem;color:#f59e0b;font-weight:700;"><i class="fa-solid fa-arrow-trend-up me-1"></i> Best attempt: {{ $score }}%</div>
                                        @endif
                                        <div style="margin-top:10px; font-size:0.75rem; color:var(--tx3);"><i class="fa-solid fa-heart text-danger"></i> Cost: {{ $level->energy_cost }} Energy</div>
                                    </div>
                                    <form action="{{ route('user.game.start', $level->id) }}" method="POST" class="start-challenge-form">
                                        @csrf
                                        <button type="submit" class="btn btn-shine start-challenge-btn" style="background:var(--dash-primary, #60a5fa);color:#fff;border:none;box-shadow:0 4px 15px rgba(96,165,250,0.4);border-radius:12px;font-weight:600;padding:10px 25px"><i class="fa-solid fa-play me-2"></i> Start Challenge</button>
                                    </form>
                                @elseif($status === 'completed')
                                    <div style="margin-top:15px;">
                                        <button class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-weight:600"><i class="fa-solid fa-check text-success me-1"></i> Completed</button>
                                    </div>
                                @elseif($status === 'locked')
                                    @if($level->prerequisite_level_id)
                                        @php $prereq = $gameLevels->where('id', $level->prerequisite_level_id)->first(); @endphp
                                        @if($prereq)
                                            <div style="margin-top:15px;font-size:0.8rem;color:var(--tx2);font-weight:600;display:flex;align-items:center;gap:5px;">
                                                <i class="fa-solid fa-circle-info text-info"></i> Reach {{ $prereq->required_score }}% in Level {{ $prereq->level_number }} to unlock.
                                            </div>
                                        @endif
                                    @endif
                                @endif
                                </div>
                                @if($status === 'locked')
                                    <div class="locked-card-art" aria-hidden="true">
                                        <i class="fa-solid {{ $lockedArtIcon }}"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @php
                        $certificateLevelCount = $gameLevels->count();
                        $certificateUnlocked = $selectedCategory
                            && $certificateLevelCount > 0
                            && $gameLevels->every(function ($level) use ($gameProgress) {
                                $progress = $gameProgress ? $gameProgress->get($level->id) : null;

                                return $progress && (int) $progress->best_score >= (int) $level->required_score;
                            });
                    @endphp
                    <div class="level-node {{ $certificateUnlocked ? 'completed' : 'locked' }} animate-fade-up" data-search-text="final reward completion certificate pdf download unlock completed locked {{ strtolower($selectedCategory?->title ?? '') }}" style="animation-delay: {{ $gameLevels->count() * 0.1 }}s">
                        <div class="level-icon-wrapper">
                            <div class="level-icon">
                                @if($certificateUnlocked)
                                    <i class="fa-solid fa-medal"></i>
                                @else
                                    <i class="fa-solid fa-lock"></i>
                                @endif
                            </div>
                        </div>
                        <div class="level-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div style="font-size:0.75rem;color:{{ $certificateUnlocked ? '#34d399' : 'var(--tx3)' }};font-weight:700;margin-bottom:5px;text-transform:uppercase">Final Reward</div>
                                    <h5 style="color:var(--tx);font-weight:700;margin:0">Completion Certificate</h5>
                                </div>
                                @if($certificateUnlocked)
                                    <div class="score-badge"><i class="fa-solid fa-circle-check"></i> Unlocked</div>
                                @else
                                    <div class="requirement-badge" style="background:var(--bg3);color:var(--tx3)"><i class="fa-solid fa-lock"></i> Locked</div>
                                @endif
                            </div>
                            <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:14px;line-height:1.5">
                                Complete every level in this challenge path to unlock your downloadable PDF certificate.
                            </p>
                            @if($certificateUnlocked)
                                <a href="{{ route('user.game.certificate.download', $selectedCategory->id) }}" class="btn btn-success" style="border-radius:12px;font-weight:700;padding:10px 18px;">
                                    <i class="fa-solid fa-file-pdf me-2"></i> Download Certificate
                                </a>
                            @else
                                <div style="margin-top:8px;font-size:0.8rem;color:var(--tx2);font-weight:600;display:flex;align-items:center;gap:6px;">
                                    <i class="fa-solid fa-flag-checkered text-info"></i> Unlocks after the final level.
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="learning-search-empty" id="learningSearchEmpty" hidden>
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>No challenges match that search.</span>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fa-solid fa-folder-open fa-3x mb-3" style="color:var(--bd)"></i>
                        <h5 style="color:var(--tx3)">No challenge levels loaded yet.</h5>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($gameResult)
    @php
        $resultPassed = ($gameResult['status'] ?? '') === 'passed';
        $scoreValue = max(0, min(100, (int) ($gameResult['score'] ?? 0)));
        $scoreColor = $resultPassed ? '#34d399' : '#f59e0b';
        $nextLevel = $gameResult['next_level'] ?? null;
        $certificate = $gameResult['certificate'] ?? null;
        $scorecard = $gameResult['ai_scorecard'] ?? data_get($gameResult, 'goal_breakdown.ai_feedback_scorecard', []);
        $scorecardMetrics = $scorecard['metrics'] ?? [];
        if (empty($scorecardMetrics) && ! empty($gameResult['goal_breakdown']['averages'])) {
            foreach ($gameResult['goal_breakdown']['averages'] as $label => $value) {
                $metricScore = max(0, min(100, (int) $value));
                $scorecardMetrics[$label] = [
                    'label' => \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)),
                    'score' => $metricScore,
                    'level' => $metricScore >= 85 ? 'Strong' : ($metricScore >= 70 ? 'Competent' : ($metricScore >= 50 ? 'Needs Work' : 'Limited')),
                    'feedback' => '',
                ];
            }
        }
        $metricOrder = ['clarity', 'relevance', 'confidence', 'grammar', 'professionalism', 'goal_coverage', 'star_method'];
        $orderedScorecardMetrics = [];
        foreach ($metricOrder as $metricKey) {
            if (array_key_exists($metricKey, $scorecardMetrics)) {
                $orderedScorecardMetrics[$metricKey] = $scorecardMetrics[$metricKey];
            }
        }
        foreach ($scorecardMetrics as $metricKey => $metricData) {
            if (! array_key_exists($metricKey, $orderedScorecardMetrics)) {
                $orderedScorecardMetrics[$metricKey] = $metricData;
            }
        }
        $metricIcons = [
            'clarity' => 'fa-lines-leaning',
            'relevance' => 'fa-bullseye',
            'confidence' => 'fa-microphone-lines',
            'grammar' => 'fa-spell-check',
            'professionalism' => 'fa-handshake-angle',
            'goal_coverage' => 'fa-list-check',
            'star_method' => 'fa-star',
        ];
        $scorecardReliability = isset($scorecard['reliability_score']) ? max(0, min(100, (int) $scorecard['reliability_score'])) : null;
        $questionFeedback = array_slice($scorecard['question_feedback'] ?? [], 0, 4);
    @endphp
    <div class="modal fade game-result-modal" id="gameResultModal" tabindex="-1" aria-labelledby="gameResultModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="game-result-hero">
                    <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                        <div class="game-result-score" style="background: conic-gradient({{ $scoreColor }} {{ $scoreValue }}%, var(--bg3) 0);">
                            <div class="game-result-score-inner">
                                <div style="font-size:1.7rem;font-weight:900;color:var(--tx);line-height:1;">{{ $scoreValue }}%</div>
                                <div style="font-size:0.72rem;font-weight:800;color:var(--tx3);text-transform:uppercase;">Score</div>
                            </div>
                        </div>
                        <div class="flex-grow-1 text-center text-md-start">
                            <span class="badge mb-2" style="background:{{ $resultPassed ? 'rgba(52,211,153,0.16);color:#10b981;border:1px solid rgba(16,185,129,0.35)' : 'rgba(245,158,11,0.14);color:#f59e0b;border:1px solid rgba(245,158,11,0.35)' }};padding:7px 11px;border-radius:999px;">
                                <i class="fa-solid {{ $resultPassed ? 'fa-circle-check' : 'fa-rotate-right' }} me-1"></i>{{ $resultPassed ? 'Passed' : 'Needs Retry' }}
                            </span>
                            <h4 id="gameResultModalTitle" style="font-weight:900;margin:0 0 6px;color:var(--tx);">
                                Level {{ $gameResult['level_number'] ?? '' }}: {{ $gameResult['level_title'] ?? 'Interview Challenge' }}
                            </h4>
                            <div style="color:var(--tx2);font-size:0.96rem;line-height:1.5;">
                                {{ $gameResult['message'] ?? ($resultPassed ? 'Level cleared.' : 'Try again to clear this level.') }}
                            </div>
                            @if(! $resultPassed && ! empty($gameResult['retry_hint']))
                                <div class="mt-2" style="color:#f59e0b;font-size:0.86rem;font-weight:700;">
                                    <i class="fa-solid fa-lightbulb me-1"></i>{{ $gameResult['retry_hint'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="game-result-stat">
                                <div class="game-result-stat-label">Goal</div>
                                <div class="game-result-stat-value">{{ $gameResult['required_score'] ?? 0 }}%+</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="game-result-stat">
                                <div class="game-result-stat-label">Best Score</div>
                                <div class="game-result-stat-value">{{ $gameResult['best_score'] ?? $scoreValue }}% @if(!empty($gameResult['is_new_best']))<span class="badge text-bg-success ms-1">New</span>@endif</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="game-result-stat">
                                <div class="game-result-stat-label">Energy</div>
                                <div class="game-result-stat-value">-{{ $gameResult['energy_spent'] ?? 0 }} <span style="font-size:0.82rem;color:var(--tx3);">left {{ $gameResult['energy_remaining'] ?? 0 }}</span></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="game-result-stat">
                                <div class="game-result-stat-label">Reward</div>
                                <div class="game-result-stat-value">+{{ $gameResult['xp_earned'] ?? 0 }} XP</div>
                            </div>
                        </div>
                    </div>

                    @if(! $resultPassed)
                        <div class="mb-4" style="border:1px solid rgba(245,158,11,0.28);background:rgba(245,158,11,0.08);border-radius:12px;padding:14px;color:var(--tx2);">
                            <strong style="color:#f59e0b;">{{ $gameResult['points_to_goal'] ?? 0 }} more point{{ (int)($gameResult['points_to_goal'] ?? 0) === 1 ? '' : 's' }} needed.</strong>
                            Retry starts a fresh attempt and costs {{ $gameResult['retry_energy_cost'] ?? 0 }} energy.
                        </div>
                    @elseif($nextLevel)
                        <div class="mb-4" style="border:1px solid rgba(52,211,153,0.28);background:rgba(52,211,153,0.08);border-radius:12px;padding:14px;color:var(--tx2);">
                            <strong style="color:#10b981;">Next level unlocked:</strong>
                            Level {{ $nextLevel['level_number'] ?? '' }} - {{ $nextLevel['title'] ?? 'Next Challenge' }}.
                            Starting it costs {{ $nextLevel['energy_cost'] ?? 0 }} energy.
                        </div>
                    @elseif($resultPassed)
                        <div class="mb-4" style="border:1px solid rgba(52,211,153,0.28);background:rgba(52,211,153,0.08);border-radius:12px;padding:14px;color:var(--tx2);">
                            <strong style="color:#10b981;">Path complete.</strong> You cleared the last available level in this scenario path.
                            @if($certificate)
                                Your PDF certificate is unlocked.
                            @endif
                        </div>
                    @endif

                    @if(!empty($gameResult['skill_focus']) || !empty($gameResult['learning_objective']))
                        <div class="mb-4" style="border:1px solid var(--bd);border-radius:12px;padding:14px;background:var(--bg3);">
                            @if(!empty($gameResult['skill_focus']))
                                <div style="font-size:0.78rem;color:#38bdf8;font-weight:900;text-transform:uppercase;margin-bottom:5px;">
                                    <i class="fa-solid fa-bullseye me-1"></i>{{ $gameResult['skill_focus'] }}
                                </div>
                            @endif
                            @if(!empty($gameResult['learning_objective']))
                                <div style="font-size:0.9rem;color:var(--tx2);line-height:1.5;">{{ $gameResult['learning_objective'] }}</div>
                            @endif
                        </div>
                    @endif

                    @if(!empty($orderedScorecardMetrics))
                        <div class="mb-4 ai-scorecard-panel">
                            <div class="ai-scorecard-heading">
                                <div>
                                    <div class="ai-scorecard-kicker">Goal Score Breakdown</div>
                                    <div class="ai-scorecard-title"><i class="fa-solid fa-clipboard-check me-1 text-info"></i> AI Feedback Scorecard</div>
                                </div>
                                @if($scorecardReliability !== null)
                                    <div class="ai-scorecard-reliability">
                                        <span>Reliability</span>
                                        <strong>{{ $scorecardReliability }}%</strong>
                                        <small style="color:var(--tx3);font-weight:800;">{{ $scorecard['reliability_band'] ?? 'Measured' }}</small>
                                    </div>
                                @endif
                            </div>

                            @if(!empty($scorecard['summary']))
                                <div class="ai-scorecard-summary">{{ $scorecard['summary'] }}</div>
                            @endif

                            <div class="row g-2 game-result-breakdown-grid">
                                @foreach($orderedScorecardMetrics as $metricKey => $metric)
                                    @php
                                        $metricScore = max(0, min(100, (int) ($metric['score'] ?? 0)));
                                        $metricColor = $metricScore >= 85 ? '#10b981' : ($metricScore >= 70 ? '#3b82f6' : ($metricScore >= 50 ? '#f59e0b' : '#ef4444'));
                                        $metricLabel = $metric['label'] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $metricKey));
                                        $metricIcon = $metricIcons[$metricKey] ?? 'fa-chart-simple';
                                    @endphp
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="ai-scorecard-metric">
                                            <div class="ai-scorecard-metric-top">
                                                <div class="ai-scorecard-metric-name"><i class="fa-solid {{ $metricIcon }}" style="color:{{ $metricColor }}"></i><span>{{ $metricLabel }}</span></div>
                                                <div class="ai-scorecard-metric-score">{{ $metricScore }}%</div>
                                            </div>
                                            <div class="ai-scorecard-meter" aria-hidden="true">
                                                <div class="ai-scorecard-meter-fill" style="width:{{ $metricScore }}%;background:{{ $metricColor }};"></div>
                                            </div>
                                            <div class="ai-scorecard-level">{{ $metric['level'] ?? 'Measured' }}</div>
                                            @if(!empty($metric['feedback']))
                                                <div class="ai-scorecard-note">{{ $metric['feedback'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(!empty($scorecard['priority_actions']))
                                <div class="mt-3">
                                    <div style="font-weight:900;color:var(--tx);margin-bottom:8px;">Next Best Actions</div>
                                    <ul class="ai-scorecard-actions-list">
                                        @foreach($scorecard['priority_actions'] as $action)
                                            <li><i class="fa-solid fa-arrow-right text-info me-1"></i>{{ $action }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!empty($questionFeedback))
                                <div class="mt-3">
                                    <div style="font-weight:900;color:var(--tx);margin-bottom:8px;">Question Feedback</div>
                                    <div class="d-grid gap-2">
                                        @foreach($questionFeedback as $item)
                                            <div class="ai-scorecard-question">
                                                <strong>Q{{ ((int) ($item['question_index'] ?? 0)) + 1 }}</strong>
                                                <span>{{ $item['feedback'] ?? 'Review this answer before retrying.' }}</span>
                                                <span class="ai-scorecard-question-score">{{ (int) ($item['score'] ?? 0) }}%</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="ai-scorecard-transparency">
                                <i class="fa-solid fa-shield-halved me-1"></i>{{ $scorecard['evidence_policy'] ?? 'Scores are based on saved challenge answers and level goals.' }}
                                @if(!empty($scorecard['guidance_note']))
                                    <span>{{ $scorecard['guidance_note'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if(!empty($gameResult['success_criteria']))
                        <div>
                            <div style="font-weight:900;color:var(--tx);margin-bottom:10px;">Level Goals</div>
                            <ul class="game-result-checklist">
                                @foreach($gameResult['success_criteria'] as $criterion)
                                    <li><i class="fa-solid fa-check" style="color:#10b981;margin-top:3px;"></i><span>{{ $criterion }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <div class="game-result-actions">
                        @if($resultPassed && $nextLevel)
                            <form action="{{ route('user.game.start', $nextLevel['id']) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success" {{ empty($nextLevel['can_start']) ? 'disabled' : '' }}>
                                    <i class="fa-solid fa-forward me-1"></i> Start Next Level
                                </button>
                            </form>
                        @elseif(! $resultPassed)
                            <form action="{{ route('user.game.start', $gameResult['level_id']) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning text-dark" {{ empty($gameResult['can_retry']) ? 'disabled' : '' }}>
                                    <i class="fa-solid fa-rotate-right me-1"></i> Retry Level
                                </button>
                            </form>
                        @endif
                        @if($certificate)
                            <a href="{{ $certificate['download_url'] }}" class="btn btn-success">
                                <i class="fa-solid fa-file-pdf me-1"></i> Download Certificate
                            </a>
                        @endif

                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            Back to Journey
                        </button>
                    </div>
                    @if(($resultPassed && $nextLevel && empty($nextLevel['can_start'])) || (! $resultPassed && empty($gameResult['can_retry'])))
                        <div class="w-100 mt-2" style="font-size:0.82rem;color:#ef4444;text-align:right;">
                            <i class="fa-solid fa-heart-crack me-1"></i>Not enough energy. Energy refills daily.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif



@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const resultModal = document.getElementById('gameResultModal');
        if (resultModal && window.bootstrap && bootstrap.Modal) {
            new bootstrap.Modal(resultModal, {
                backdrop: 'static',
                keyboard: true
            }).show();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const categorySelect = document.getElementById('learningCategorySelect');
        if (categorySelect) {
            categorySelect.addEventListener('change', function () {
                if (this.value) {
                    window.location.href = this.value;
                }
            });
        }

        const searchInput = document.getElementById('learningSearchInput');
        const searchEmpty = document.getElementById('learningSearchEmpty');
        const challengeNodes = Array.from(document.querySelectorAll('#modules-list > .level-node'));
        const pathLines = Array.from(document.querySelectorAll('#modules-list > .level-path-line'));

        if (searchInput && challengeNodes.length > 0) {
            const applySearch = () => {
                const query = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                challengeNodes.forEach(node => {
                    const isVisible = !query || (node.dataset.searchText || '').includes(query);
                    node.hidden = !isVisible;
                    if (isVisible) visibleCount++;
                });

                pathLines.forEach(line => {
                    line.hidden = Boolean(query);
                });

                if (searchEmpty) {
                    searchEmpty.hidden = !query || visibleCount > 0;
                }
            };

            searchInput.addEventListener('input', applySearch);
            applySearch();
        }

        document.querySelectorAll('.start-challenge-form').forEach(form => {
            form.addEventListener('submit', function () {
                const button = form.querySelector('.start-challenge-btn');
                if (!button || button.disabled) return;

                button.disabled = true;
                button.dataset.originalHtml = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Starting...';
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;
        if (document.getElementById('gameResultModal')) return;

        const stepsMobile = [
            { element: '#nav-pills-container', popover: { title: 'Challenge Paths', description: 'Switch paths to find different Philippines interview challenges and topics.', side: 'bottom', align: 'start' }},
            { element: '#dashboard-stats', popover: { title: 'Player Stats', description: 'Track level, energy, combo streak, and accuracy while you play.', side: 'top', align: 'start' }},
            { element: '#modules-list', popover: { title: 'Challenge Path', description: 'Choose a level, review its goals and energy cost, then complete levels to unlock more.', side: 'top', align: 'start' }},
            { element: '#btn-skill-tree', popover: { title: 'Skill Tree', description: 'Open the skill tree to spend XP on perks that improve your training loop.', side: 'bottom', align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#nav-pills-container', popover: { title: 'Challenge Paths', description: 'Switch paths to find different Philippines interview challenges and topics.', side: 'bottom', align: 'start' }},
            { element: '#dashboard-stats', popover: { title: 'Player Stats', description: 'Track level, energy, combo streak, and accuracy while you play.', side: 'bottom', align: 'start' }},
            { element: '#modules-list', popover: { title: 'Challenge Path', description: 'Choose a level, review its goals and energy cost, then complete levels to unlock more.', side: 'top', align: 'start' }},
            { element: '#btn-skill-tree', popover: { title: 'Skill Tree', description: 'Open the skill tree to spend XP on perks that improve your training loop.', side: 'bottom', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_learning',
            serverDetectedMobile: false,
            stepsMobile,
            stepsDesktop,
            autoStart: false,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
