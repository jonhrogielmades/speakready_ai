@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    /* Premium aesthetics for Learning Lab */
    .ll-header {
        background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(52,211,153,0.1) 100%);
        border: 1px solid var(--bd);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    .ll-stat-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        transition: 0.3s;
    }
    .ll-stat-card:hover {
        transform: translateY(-5px);
        border-color: rgba(59,130,246,0.3);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .ll-stat-val {
        font-size: 2rem;
        font-weight: 700;
        color: var(--tx);
        margin: 10px 0;
    }
    .ll-nav-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 30px;
        background: var(--sf);
        color: var(--tx2);
        border: 1px solid var(--bd);
        text-decoration: none;
        font-weight: 500;
        transition: 0.3s;
        margin-right: 10px;
        margin-bottom: 10px;
    }
    .ll-nav-pill:hover, .ll-nav-pill.active {
        background: var(--pur);
        color: #fff;
        border-color: var(--pur);
        box-shadow: 0 4px 15px rgba(59,130,246,0.3);
    }
    .ll-category-list {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 20px;
    }
    .ll-category-item {
        display: flex;
        align-items: center;
        padding: 10px;
        border-radius: 10px;
        color: var(--tx2);
        text-decoration: none;
        transition: 0.2s;
        margin-bottom: 5px;
    }
    .ll-category-item:hover, .ll-category-item.active {
        background: rgba(59,130,246,0.1);
        color: var(--pur);
    }
    .ll-module-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 18px;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: 0.3s;
    }
    .ll-module-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        border-color: rgba(59,130,246,0.4);
    }
    .ll-progress-bar {
        width: 100%;
        height: 8px;
        background: var(--bd);
        border-radius: 4px;
        overflow: hidden;
    }
    .ll-progress-fill {
        height: 100%;
        border-radius: 4px;
        background: linear-gradient(90deg, var(--pur) 0%, #34d399 100%);
    }
    /* Gamified Path Styles */
    .level-path-container {
        position: relative;
        padding: 20px 0;
        margin-top: 20px;
    }
    .level-path-line {
        position: absolute;
        left: 40px;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--bd);
        border-radius: 4px;
        z-index: 1;
    }
    .level-path-line-progress {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: linear-gradient(180deg, #34d399 0%, var(--pur) 100%);
        border-radius: 4px;
        z-index: 2;
    }
    .level-node {
        position: relative;
        display: flex;
        align-items: flex-start;
        margin-bottom: 40px;
        z-index: 3;
    }
    .level-icon-wrapper {
        width: 80px;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        position: relative;
    }
    .level-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: 0.3s;
        border: 4px solid var(--sf);
    }
    .level-node.completed .level-icon {
        background: #34d399;
        color: #fff;
    }
    .level-node.active .level-icon {
        background: var(--pur);
        color: #fff;
        box-shadow: 0 0 0 6px rgba(59,130,246,0.2), 0 10px 20px rgba(59,130,246,0.4);
        animation: pulse-ring 2s infinite;
    }
    .level-node.locked .level-icon {
        background: var(--bg3);
        color: var(--tx3);
        border-color: var(--bd);
    }
    .level-card {
        flex-grow: 1;
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 20px;
        margin-left: 20px;
        transition: 0.3s;
        position: relative;
        overflow: hidden;
    }
    .level-node.active .level-card {
        border-color: rgba(59,130,246,0.4);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }
    .level-node.locked .level-card {
        opacity: 0.7;
        pointer-events: none;
        user-select: none;
    }
    .level-node.locked .level-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: repeating-linear-gradient(45deg, rgba(0,0,0,0.02), rgba(0,0,0,0.02) 10px, transparent 10px, transparent 20px);
        z-index: 10;
    }
    .score-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        background: rgba(52,211,153,0.1);
        color: #34d399;
    }
    .requirement-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        background: rgba(245,158,11,0.1);
        color: #f59e0b;
    }
    @keyframes pulse-ring {
        0% { box-shadow: 0 0 0 0 rgba(59,130,246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(59,130,246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59,130,246, 0); }
    }

    /* AI Assistant FAB */
    .ll-ai-fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--pur) 0%, #34d399 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 10px 25px rgba(59,130,246,0.4);
        cursor: pointer;
        transition: 0.3s;
        z-index: 100;
        text-decoration: none;
    }
    .ll-ai-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 15px 35px rgba(59,130,246,0.5);
    }
    
    /* Mobile Responsiveness */
    @media (max-width: 767px) {
        .level-path-line {
            left: 25px;
        }
        .level-icon-wrapper {
            width: 50px;
        }
        .level-icon {
            width: 40px;
            height: 40px;
            font-size: 1.2rem;
            border-width: 3px;
        }
        .level-card {
            margin-left: 15px;
            padding: 15px;
        }
        .db-top-search {
            width: 100% !important;
            max-width: 100% !important;
        }
        .d-flex.align-items-center.gap-3.flex-wrap {
            width: 100%;
        }

        .ll-stat-val {
            font-size: 1.5rem;
        }
        .ll-stat-card {
            padding: 15px;
        }
        .ll-ai-fab {
            bottom: 80px;
            right: 20px;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
        }
        #nav-pills-container {
            padding-bottom: 10px;
        }
        #nav-pills-container::-webkit-scrollbar {
            display: none;
        }
    }
</style>

<div class="db-section active">
    <!-- Header & Navigation -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h3 style="font-weight:800;color:var(--tx);margin:0; font-style:italic; text-transform:uppercase;">Interview Arena</h3>
                <span class="badge" style="background:linear-gradient(135deg, var(--pur) 0%, #34d399 100%); color:#fff; border-radius:8px; font-weight:800;">SEASON 1</span>
            </div>
            <p style="color:var(--tx3);margin-top:5px; font-weight:500;">Complete challenges, earn XP, and level up your career skills.</p>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap" style="flex: 1; min-width: 250px; justify-content: flex-end;">
            <div class="db-top-search" style="width:100%; max-width:300px; background:var(--sf);border:1px solid var(--bd); margin:0;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search quests, skills, topics..." style="width:100%;">
            </div>
            <button class="btn btn-sm d-inline-flex align-items-center" style="background:var(--bg3); border:1px solid var(--bd); color:var(--tx2); border-radius:10px; font-weight:600;" onclick="startOnboardingTour()"><i class="fa-solid fa-gamepad me-sm-1" style="color:#60a5fa"></i> <span class="d-none d-sm-inline">How to Play</span></button>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div id="nav-pills-container" class="mb-4 pb-2" style="overflow-x:auto;white-space:nowrap;">
        <a href="{{ route('user.learning') }}" class="ll-nav-pill active"><i class="fa-solid fa-map-location-dot"></i> Journey Map</a>
        <a href="#" class="ll-nav-pill"><i class="fa-solid fa-microphone-lines"></i> AI Mock Simulator</a>
        <a href="#" class="ll-nav-pill"><i class="fa-solid fa-video"></i> Video Analysis</a>
        <a href="#" class="ll-nav-pill"><i class="fa-solid fa-briefcase"></i> Career Paths</a>
        <a href="#" class="ll-nav-pill"><i class="fa-solid fa-file-invoice"></i> Resume-to-Question</a>
        <a href="#" class="ll-nav-pill"><i class="fa-solid fa-ranking-star"></i> Leaderboard</a>
    </div>

    <!-- Gamified HUD Stats -->
    <div id="dashboard-stats" class="row g-4 mb-4">
        <!-- Player Level & XP -->
        <div class="col-12 col-sm-6 col-lg-3">
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
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="ll-stat-card d-flex align-items-center gap-3" style="height:100%;">
                <div style="width:55px; height:55px; border-radius:15px; background:rgba(239,68,68,0.1); color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                    <i class="fa-solid fa-heart"></i>
                </div>
                <div style="text-align:left;">
                    <div class="ll-stat-val" style="font-size:1.5rem; margin:0; font-weight:800;">{{ $profile?->energy ?? 3 }} <span style="font-size:1rem; color:var(--tx3);">/ 3</span></div>
                    <div style="font-size:0.8rem; color:var(--tx3); font-weight:700; text-transform:uppercase">Energy</div>
                </div>
            </div>
        </div>

        <!-- Streak -->
        <div class="col-12 col-sm-6 col-lg-3">
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
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="ll-stat-card d-flex align-items-center gap-3" style="height:100%;">
                <div style="width:55px; height:55px; border-radius:15px; background:rgba(52,211,153,0.1); color:#34d399; display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <div style="text-align:left;">
                    @php $avgScore = $arenaProgress && $arenaProgress->count() > 0 ? round($arenaProgress->avg('best_score')) : 0; @endphp
                    <div class="ll-stat-val" style="font-size:1.5rem; margin:0; font-weight:800;">{{ $avgScore }}%</div>
                    <div style="font-size:0.8rem; color:var(--tx3); font-weight:700; text-transform:uppercase">Accuracy</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 style="font-weight:700;color:var(--tx);margin:0">Your Interview Journey</h5>
                <span class="badge" style="background:rgba(245,158,11,0.1);color:#f59e0b;font-size:0.85rem;padding:8px 15px;border-radius:10px;"><i class="fa-solid fa-heart me-1"></i> {{ $profile?->energy ?? 3 }} / 3 Lives</span>
            </div>

            <div class="level-path-container" id="modules-list">
                <!-- Path Line -->
                <div class="level-path-line">
                    @php 
                        $completedCount = $arenaProgress ? $arenaProgress->where('status', 'completed')->count() : 0;
                        $totalLevels = $arenaLevels ? $arenaLevels->count() : 1;
                        $pathPercent = min(100, ($completedCount / max(1, $totalLevels)) * 100);
                    @endphp
                    <div class="level-path-line-progress" style="height: {{ $pathPercent }}%;"></div>
                </div>

                @if($arenaLevels && $arenaLevels->count() > 0)
                    @foreach($arenaLevels as $level)
                        @php
                            $prog = $arenaProgress ? $arenaProgress->get($level->id) : null;
                            $status = $prog ? $prog->status : 'locked';
                            if (!$prog) {
                                if (!$level->prerequisite_level_id) {
                                    $status = 'active';
                                } else {
                                    $prereqProg = $arenaProgress->get($level->prerequisite_level_id);
                                    $prereqLevel = $arenaLevels->where('id', $level->prerequisite_level_id)->first();
                                    if ($prereqProg && $prereqProg->best_score >= ($prereqLevel ? $prereqLevel->required_score : 80)) {
                                        $status = 'active';
                                    }
                                }
                            }

                            if ($level->is_hidden && $status === 'locked') {
                                continue;
                            }
                            
                            $score = $prog ? $prog->best_score : 0;
                            
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

                        <div class="level-node {{ $nodeClass }}">
                            <div class="level-icon-wrapper">
                                <div class="level-icon">{!! $iconHtml !!}</div>
                            </div>
                            <div class="level-card">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <div style="font-size:0.75rem;color:{{ $status === 'completed' ? '#34d399' : ($status === 'active' ? 'var(--pur)' : 'var(--tx3)') }};font-weight:700;margin-bottom:5px;text-transform:uppercase">Level {{ $level->level_number }}</div>
                                        <h5 style="color:var(--tx);font-weight:700;margin:0">{{ $level->title }}</h5>
                                    </div>
                                    @if($status === 'completed')
                                        <div class="score-badge"><i class="fa-solid fa-star"></i> Score: {{ $score }}%</div>
                                    @elseif($status === 'active')
                                        <div class="requirement-badge"><i class="fa-solid fa-bullseye"></i> Goal: {{ $level->required_score }}%+</div>
                                    @else
                                        <div class="requirement-badge" style="background:var(--bg3);color:var(--tx3)"><i class="fa-solid fa-lock"></i> Locked</div>
                                    @endif
                                </div>
                                
                                <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:10px;line-height:1.5">{{ $level->description }}</p>
                                
                                @if($status === 'active' || $status === 'completed')
                                    <div class="d-flex flex-wrap gap-2 mb-{{ $status==='active' ? '20' : '0' }}px">
                                        @if($level->time_limit_seconds)
                                            <span class="badge bg-dark border" style="color:var(--tx2)"><i class="fa-solid fa-clock text-danger me-1"></i> {{ $level->time_limit_seconds }}s</span>
                                        @endif
                                        @if($level->banned_words)
                                            <span class="badge bg-dark border" style="color:var(--tx2)" title="{{ $level->banned_words }}"><i class="fa-solid fa-ban text-danger me-1"></i> Banned Words</span>
                                        @endif
                                        @if($level->target_tone)
                                            <span class="badge bg-dark border" style="color:var(--tx2)"><i class="fa-solid fa-face-smile text-success me-1"></i> {{ $level->target_tone }}</span>
                                        @endif
                                        @if($level->custom_badge_name)
                                            <span class="badge bg-dark border" style="color:var(--pur)"><i class="fa-solid fa-medal me-1"></i> {{ $level->custom_badge_name }}</span>
                                        @endif
                                        @if($level->skill_xp_amount > 0)
                                            <span class="badge bg-dark border" style="color:#34d399"><i class="fa-solid fa-bolt me-1"></i> +{{ $level->skill_xp_amount }} {{ $level->skill_xp_type }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($status === 'active')
                                    <div style="background:var(--bg3);border-radius:10px;padding:15px;margin-bottom:20px;border:1px solid var(--bd)">
                                        <div style="font-size:0.8rem;color:var(--tx2);font-weight:600;margin-bottom:5px"><i class="fa-solid fa-flask me-1 text-info"></i> Your Mission:</div>
                                        <div style="color:var(--tx3);font-size:0.85rem">{{ $level->mission_text }}</div>
                                        <div style="margin-top:10px; font-size:0.75rem; color:var(--tx3);"><i class="fa-solid fa-heart text-danger"></i> Cost: {{ $level->energy_cost }} Energy</div>
                                    </div>
                                    <form action="{{ route('user.arena.start', $level->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn bgrd" style="border-radius:10px;font-weight:600;padding:10px 25px"><i class="fa-solid fa-play me-2"></i> Start Challenge</button>
                                    </form>
                                @elseif($status === 'completed')
                                    <div style="margin-top:15px;">
                                        <button class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-weight:600"><i class="fa-solid fa-check text-success me-1"></i> Completed</button>
                                    </div>
                                @elseif($status === 'locked')
                                    @if($level->prerequisite_level_id)
                                        @php $prereq = $arenaLevels->where('id', $level->prerequisite_level_id)->first(); @endphp
                                        @if($prereq)
                                            <div style="margin-top:15px;font-size:0.8rem;color:var(--tx2);font-weight:600;display:flex;align-items:center;gap:5px;">
                                                <i class="fa-solid fa-circle-info text-info"></i> Reach {{ $prereq->required_score }}% in Level {{ $prereq->level_number }} to unlock.
                                            </div>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="fa-solid fa-ghost fa-3x mb-3" style="color:var(--bd)"></i>
                        <h5 style="color:var(--tx3)">No Arena Levels loaded yet.</h5>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>



@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.driver === 'undefined') return;
        const driver = window.driver.js.driver;

        const stepsMobile = [
            { element: '#nav-pills-container', popover: { title: 'Arena Navigation', description: 'Navigate between your Journey Map, AI Mock Simulator, Video Analysis, and more.', side: "bottom", align: 'start' }},
            { element: '#dashboard-stats', popover: { title: 'Your Player Stats', description: 'Track your Level, Energy, Combo Streak, and overall AI Accuracy.', side: "top", align: 'start' }},

            { element: '#modules-list', popover: { title: 'Interview Journey', description: 'Complete levels to earn XP and unlock tougher challenges. Master the levels to rank up!', side: "top", align: 'start' }},
            { element: '#ai-fab', popover: { title: 'AI Assistant', description: 'Stuck on a challenge? Click here anytime to ask the AI Coach for a hint.', side: "top", align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#nav-pills-container', popover: { title: 'Arena Navigation', description: 'Navigate between your Journey Map, AI Mock Simulator, Video Analysis, and more.', side: "bottom", align: 'start' }},
            { element: '#dashboard-stats', popover: { title: 'Your Player Stats', description: 'Track your Level, Energy, Combo Streak, and overall AI Accuracy.', side: "bottom", align: 'start' }},

            { element: '#modules-list', popover: { title: 'Interview Journey', description: 'Complete levels to earn XP and unlock tougher challenges. Master the levels to rank up!', side: "top", align: 'start' }},
            { element: '#ai-fab', popover: { title: 'AI Assistant', description: 'Stuck on a challenge? Click here anytime to ask the AI Coach for a hint.', side: "left", align: 'end' }}
        ];

        const driverObj = driver({
            showProgress: true,
            animate: true,
            popoverClass: document.documentElement.classList.contains('lm') ? 'driverjs-theme-light' : 'driverjs-theme-dark',
            steps: {{ $isMobile ? 'true' : 'false' }} ? stepsMobile : stepsDesktop,
            onDestroyStarted: () => {
                if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tutorial?")) {
                    driverObj.destroy();
                    localStorage.setItem('onboarding_completed_learning', 'true');
                }
            },
        });

        window.startOnboardingTour = function() {
            driverObj.drive();
        };

        if (!localStorage.getItem('onboarding_completed_learning')) {
            setTimeout(() => {
                startOnboardingTour();
            }, 500);
        }
    });
</script>
@endpush
@endsection

