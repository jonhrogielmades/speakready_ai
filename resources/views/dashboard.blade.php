@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    /* Premium Dashboard Styles - Mobile Optimized */
    :root {
        --dash-primary: #60a5fa;
        --dash-success: #34d399;
        --dash-warning: #fbbf24;
        --dash-danger: #f87171;
        --dash-info: #38bdf8;
    }
    
    .db-section {
        padding-top: 8px;
    }
    
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .lm .premium-card {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.5);
    }
    @media (max-width: 575px) {
        .premium-card {
            padding: 20px;
            border-radius: 20px;
        }
    }
    .premium-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.1);
    }
    
    .text-gradient-primary {
        background: linear-gradient(135deg, var(--dash-primary) 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    
    /* Elegant gradients for premium cards */
    .card-grad-primary {
        background: linear-gradient(135deg, var(--sf) 0%, rgba(59,130,246,0.05) 100%);
    }
    .card-grad-success {
        background: linear-gradient(135deg, var(--sf) 0%, rgba(52,211,153,0.05) 100%);
    }
    
    .glass-effect {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .score-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.8rem;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .score-high { background: rgba(52, 211, 153, 0.15); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.3); }
    .score-med { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
    .score-low { background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.3); }
    
    .progress-track {
        background: var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 100px;
        height: 6px;
        overflow: hidden;
        margin-top: 8px;
    }
    .progress-fill {
        height: 100%;
        border-radius: 100px;
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .badge-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: var(--bg3, rgba(255,255,255,0.05));
        border: 1px solid var(--bd, rgba(255,255,255,0.1));
        color: #fbbf24;
        transition: 0.3s;
    }
    .badge-icon:hover {
        transform: scale(1.05) rotate(5deg);
    }


    @media (max-width: 575px) {

    }
    
    .notif-item {
        display: flex;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid var(--bd);
        align-items: flex-start;
    }
    .notif-item:last-child { border-bottom: none; padding-bottom: 0; }
    
    .notif-dot {
        width: 10px; height: 10px; border-radius: 50%;
        margin-top: 5px; flex-shrink: 0;
    }
    
    /* Modern Stat Card Grid */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    @media (max-width: 768px) {
        .stat-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    .stat-card {
        padding: 24px 16px;
        text-align: center;
        border-radius: 20px;
        background: var(--sf);
        border: 1px solid var(--bd);
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        position: relative;
        overflow: hidden;
        z-index: 1;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: radial-gradient(circle at top right, rgba(96, 165, 250, 0.1), transparent 70%);
        opacity: 0;
        transition: opacity 0.4s ease;
        z-index: -1;
    }
    @media (max-width: 575px) {
        .stat-card { padding: 16px 12px; border-radius: 16px; }
    }
    .stat-card:hover { 
        border-color: rgba(96,165,250,0.4); 
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    }
    .stat-card:hover::before { opacity: 1; }
    .stat-card:hover .stat-icon { transform: scale(1.15) rotate(5deg); }
    .stat-val { font-size: 1.9rem; font-weight: 800; line-height: 1.1; margin-bottom: 6px; letter-spacing: -0.5px; }
    .stat-label { font-size: 0.75rem; color: var(--tx3); text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; }
    .stat-icon { font-size: 1.8rem; margin-bottom: 16px; transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); display: inline-block; }
    
    .chart-container-mobile {
        position: relative; height: 250px; width: 100%;
    }
    @media (max-width: 575px) {
        .chart-container-mobile { height: 200px; }
    }
    
    .flex-col-mobile { display: flex; align-items: center; }
    @media (max-width: 575px) {
        .flex-col-mobile { flex-direction: column; align-items: flex-start; gap: 10px; }
        .flex-col-mobile > div:last-child { width: 100%; }
        .flex-col-mobile > div:last-child .nav-pills { display: flex; width: 100%; justify-content: space-between; }
        .flex-col-mobile > div:last-child .nav-item { flex: 1; text-align: center; }
        .flex-col-mobile > div:last-child .nav-link { width: 100%; }
    }
    
    .avatar-lg {
        width: 70px; height: 70px; border-radius: 20px;
        background: linear-gradient(135deg, #2563eb, #60a5fa);
        display: flex; align-items: center; justify-content: center;
        color: white; font-size: 1.8rem; font-weight: 700;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        border: 2px solid rgba(255,255,255,0.1);
    }
    @media (max-width: 575px) {
        .avatar-lg { width: 56px; height: 56px; font-size: 1.4rem; border-radius: 16px; }
    }

    /* Animations & Dynamic Effects */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }

    @keyframes ambientFloat {
        0% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0, 0) scale(1); }
    }
    
    @keyframes pulseGlow {
        0% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(52, 211, 153, 0); }
        100% { box-shadow: 0 0 0 0 rgba(52, 211, 153, 0); }
    }

    @keyframes shineEffect {
        0% { left: -100%; }
        20% { left: 100%; }
        100% { left: 100%; }
    }
    
    .btn-shine {
        position: relative;
        overflow: hidden;
    }
    .btn-shine::after {
        content: '';
        position: absolute;
        top: 0; left: -100%;
        width: 50%; height: 100%;
        background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%);
        transform: skewX(-20deg);
        animation: shineEffect 4s infinite;
    }
    
    .ambient-bg-element {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: 0.15;
        z-index: -1;
        pointer-events: none;
        animation: ambientFloat 20s infinite ease-in-out;
    }

    .animate-fade-up {
        animation: fadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }
    
    .animate-scale-in {
        animation: scaleIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        opacity: 0;
    }

    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }
    .delay-500 { animation-delay: 0.5s; }
    .delay-600 { animation-delay: 0.6s; }
    .delay-700 { animation-delay: 0.7s; }
    .delay-800 { animation-delay: 0.8s; }
</style>

<div class="db-section active" id="sec-overview" style="position: relative; z-index: 1;">
    <!-- Ambient Background -->
    <div class="ambient-bg-element" style="top: -5%; left: -10%; width: 40vw; height: 40vw; background: var(--dash-primary);"></div>
    <div class="ambient-bg-element" style="bottom: 20%; right: -5%; width: 35vw; height: 35vw; background: #06b6d4; animation-delay: -10s;"></div>

    <!-- Feature 1: Welcome Section -->
    <div class="d-flex align-items-center justify-content-between mb-4 mt-2 animate-fade-up">
        <div class="d-flex align-items-center gap-3">
            <div class="avatar-lg" style="padding:0;overflow:hidden;border:2px solid rgba(255,255,255,0.1);">
                @if(Auth::check() && Auth::user()->profile_photo_path)
                    @if(Str::startsWith(Auth::user()->profile_photo_path, ['http://', 'https://', 'data:']))
                        <img src="{{ Auth::user()->profile_photo_path }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;">
                    @endif
                @else
                    {{ substr(Auth::user()->name ?? 'User', 0, 1) }}
                @endif
            </div>
            <div>
                <h4 class="fw-bold mb-1" style="font-size: clamp(1.4rem, 4vw, 1.8rem); letter-spacing: -0.5px;">Welcome back, <span id="greetName" class="text-gradient-primary">{{ explode(' ', Auth::user()->name)[0] ?? 'User' }}</span>!</h4>
                <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Your interview readiness score is <strong style="color:var(--dash-success)">{{ $profile->readiness_score ?? $avgScore ?? 0 }}%</strong>.</p>
            </div>
        </div>
        <div>
        </div>
    </div>



    <div class="row g-4">
        <!-- LEFT COLUMN (Main Content) -->
        <div class="col-lg-8">
            
            <!-- Feature 2: Readiness Score Card -->
            <div class="premium-card mb-4 position-relative overflow-hidden card-grad-success animate-fade-up delay-100">
                <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;background:rgba(52,211,153,0.15);border-radius:50%;filter:blur(40px);pointer-events:none;"></div>
                
                @php
                    $scoreVal = $profile->readiness_score ?? $avgScore ?? 0;
                    $scoreClass = $scoreVal >= 80 ? 'score-high' : ($scoreVal >= 60 ? 'score-med' : 'score-low');
                    $scoreText = $scoreVal >= 80 ? 'Highly Acceptable' : ($scoreVal >= 60 ? 'Needs Practice' : 'Beginner');
                    $scoreIcon = $scoreVal >= 80 ? 'fa-check-circle' : ($scoreVal >= 60 ? 'fa-exclamation-circle' : 'fa-arrow-up');
                @endphp
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0"><i class="fa-solid fa-star me-2" style="color:var(--dash-warning)"></i> Overall Readiness</h5>
                    <span class="score-badge {{ $scoreClass }}" style="animation: pulseGlow 2s infinite;"><i class="fa-solid {{ $scoreIcon }}"></i> {{ $scoreText }}</span>
                </div>
                <div class="d-flex align-items-end gap-3 mb-2">
                    <div style="font-size: clamp(3rem, 8vw, 4rem); font-weight: 800; line-height: 1; color: var(--tx); letter-spacing: -1px;">
                        {{ $scoreVal }}<span style="font-size: clamp(1.5rem, 4vw, 2rem); color: var(--tx3)">%</span>
                    </div>
                </div>
                <div class="progress-track mt-3" style="height:10px;background:var(--bd);">
                    <div class="progress-fill" style="width: {{ $scoreVal }}%; background:linear-gradient(90deg, #34d399, #10b981);"></div>
                </div>
            </div>

            <!-- Feature 3: Quick Statistics Cards -->
            <div class="stat-grid mb-4">
                <div class="stat-card animate-scale-in delay-200">
                    <div class="stat-icon" style="color:var(--dash-primary);"><i class="fa-solid fa-microphone"></i></div>
                    <div class="stat-val">{{ $totalSessions ?? 0 }}</div>
                    <div class="stat-label">Total Sessions</div>
                </div>
                <div class="stat-card animate-scale-in delay-300">
                    <div class="stat-icon" style="color:var(--dash-success);"><i class="fa-solid fa-chart-simple"></i></div>
                    <div class="stat-val">{{ round(($avgScore ?? 0) / 20, 1) }}<span style="font-size:1rem;color:var(--tx3)">/5</span></div>
                    <div class="stat-label">Avg Rating</div>
                </div>
                <div class="stat-card animate-scale-in delay-400">
                    <div class="stat-icon" style="color:var(--dash-info);"><i class="fa-solid fa-bolt"></i></div>
                    <div class="stat-val">{{ $experiencePoints ?? 0 }}</div>
                    <div class="stat-label">Total XP</div>
                </div>
                <div class="stat-card animate-scale-in delay-500">
                    <div class="stat-icon" style="color:var(--dash-warning);"><i class="fa-solid fa-fire"></i></div>
                    <div class="stat-val">{{ $currentStreak ?? 0 }}</div>
                    <div class="stat-label">Day Streak</div>
                </div>
            </div>

            <!-- Feature 4: Interview Progress Chart -->
            <div id="card-progress-chart" class="premium-card mb-4 card-grad-primary animate-fade-up delay-200">
                <div class="d-flex justify-content-between mb-4 flex-col-mobile">
                    <h5 class="fw-bold m-0"><i class="fa-solid fa-chart-line me-2" style="color:var(--dash-primary)"></i> Interview Progress</h5>
                    <div>
                        <ul class="nav nav-pills" id="chartTabs">
                            <li class="nav-item"><a class="nav-link active" href="#" data-period="recent" style="color: var(--tx) !important;">Recent</a></li>
                        </ul>
                    </div>
                </div>
                <div class="chart-container-mobile">
                    <canvas id="progressChart"></canvas>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Feature 5: Category Performance -->
                <div class="col-md-6">
                    <div class="premium-card h-100 animate-fade-up delay-300">
                        <h6 class="fw-bold mb-4">Category Performance</h6>
                        
                        @if(isset($categoryPerformance) && count($categoryPerformance) > 0)
                            @foreach($categoryPerformance as $index => $cat)
                                @php
                                    $colors = ['#34d399', '#60a5fa', '#38bdf8', '#fbbf24', '#a78bfa'];
                                    $color = $colors[$index % count($colors)];
                                @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem; font-weight: 500;">
                                        <span>{{ $cat->name }}</span>
                                        <span style="color:{{$color}}">{{ $cat->score }}%</span>
                                    </div>
                                    <div class="progress-track" style="height:6px;">
                                        <div class="progress-fill" style="width:{{ $cat->score }}%; background:{{$color}};"></div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-4" style="color:var(--tx3);font-size:0.9rem;">
                                <i class="fa-solid fa-folder-open mb-2" style="font-size:2rem;opacity:0.5;"></i>
                                <p class="m-0">Complete a session to see performance.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Feature 10: Learning Lab Progress -->
                <div class="col-md-6">
                    <div class="premium-card h-100 animate-fade-up delay-400">
                        <h6 class="fw-bold mb-4">Learning Lab Progress</h6>
                        
                        @if(isset($learningLabProgress) && count($learningLabProgress) > 0)
                            @foreach($learningLabProgress as $prog)
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div style="width:42px;height:42px;border-radius:12px;background:{{ $prog->color }}22;display:flex;align-items:center;justify-content:center;color:{{ $prog->color }};font-size:1.1rem;flex-shrink:0;">
                                    <i class="fa-solid {{ $prog->icon }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between mb-1" style="font-size:0.85rem; font-weight: 500;">
                                        <span>{{ $prog->title }}</span>
                                        <span style="color:{{ $prog->progress == 100 ? '#34d399' : 'var(--tx)' }}">{{ $prog->progress }}%</span>
                                    </div>
                                    <div class="progress-track" style="height:5px;">
                                        <div class="progress-fill" style="width:{{ $prog->progress }}%;background:{{ $prog->progress == 100 ? '#34d399' : $prog->color }};"></div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                             <div class="text-center py-4" style="color:var(--tx3);font-size:0.9rem;">
                                <i class="fa-solid fa-book-open mb-2" style="font-size:2rem;opacity:0.5;"></i>
                                <p class="m-0">Start a module to track progress.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Feature 7: AI Feedback Summary -->
                <div class="col-md-6">
                    <div class="premium-card h-100 animate-fade-up delay-400">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-wand-magic-sparkles me-2" style="color:var(--dash-primary)"></i> AI Feedback Summary</h6>
                        <div class="p-3 mb-3" style="background:rgba(52,211,153,0.05);border-radius:16px;border:1px solid rgba(52,211,153,0.2);">
                            <h6 style="color:var(--dash-success);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:12px;">Top Strengths</h6>
                            <ul style="margin:0;padding-left:20px;font-size:0.9rem;color:var(--tx);font-weight:500;line-height:1.6;">
                                @foreach($aiFeedback['strengths'] ?? [] as $strength)
                                    <li class="mb-1">{{ $strength }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="p-3" style="background:rgba(248,113,113,0.05);border-radius:16px;border:1px solid rgba(248,113,113,0.2);">
                            <h6 style="color:var(--dash-danger);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:12px;">Areas for Improvement</h6>
                            <ul style="margin:0;padding-left:20px;font-size:0.9rem;color:var(--tx);font-weight:500;line-height:1.6;">
                                @foreach($aiFeedback['improvements'] ?? [] as $improvement)
                                    <li class="mb-1">{{ $improvement }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 8: AI Recommendations -->
                <div class="col-md-6">
                    <div id="card-ai-recommendations" class="premium-card h-100 card-grad-primary animate-fade-up delay-500">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-lightbulb me-2" style="color:var(--dash-warning)"></i> AI Recommendations</h6>
                        <p style="font-size:0.85rem;color:var(--tx3);margin-bottom:15px;">Based on your recent performance, AI suggests:</p>
                        
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded" style="background:var(--bg3); border: 1px solid var(--bd); border-radius: 12px;">
                            <div style="color:var(--dash-primary); font-size: 1.2rem;"><i class="fa-solid fa-bullseye"></i></div>
                            <div style="font-size:0.9rem; font-weight: 500;">Practice Behavioral Questions</div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded" style="background:var(--bg3); border: 1px solid var(--bd); border-radius: 12px;">
                            <div style="color:var(--dash-success); font-size: 1.2rem;"><i class="fa-solid fa-star"></i></div>
                            <div style="font-size:0.9rem; font-weight: 500;">Improve STAR Responses</div>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded" style="background:var(--bg3); border: 1px solid var(--bd); border-radius: 12px;">
                            <div style="color:var(--dash-info); font-size: 1.2rem;"><i class="fa-solid fa-book"></i></div>
                            <div style="font-size:0.9rem; font-weight: 500;">Review Scholarship Module</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature 6: Recent Interview Sessions -->
            <div id="card-recent-sessions" class="premium-card mb-4 animate-fade-up delay-600" style="background-color: var(--sf); color: var(--tx);">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="color: var(--tx);">Recent Sessions</h5>
                    <a href="{{ route('user.reports') }}" class="btn btn-sm btn-link text-decoration-none" style="color:var(--dash-primary);font-weight:600;">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table custom-table mb-0 w-100" style="background-color: var(--sf); color: var(--tx); --bs-table-bg: transparent; --bs-table-color: var(--tx);">
                        <thead>
                            <tr style="background-color: var(--sf);">
                                <th style="padding-left:24px; color: var(--tx); background-color: var(--sf);">Date</th>
                                <th style="color: var(--tx); background-color: var(--sf);">Category</th>
                                <th style="color: var(--tx); background-color: var(--sf);">Score</th>
                                <th class="text-end" style="padding-right:24px; color: var(--tx); background-color: var(--sf);">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSessions ?? [] as $session)
                            <tr>
                                <td style="color:var(--tx2);font-size:0.85rem;padding-left:24px;">{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</td>
                                <td>
                                    <span class="badge" style="background:rgba(59,130,246,0.15);color:var(--dash-primary);padding:6px 10px;border-radius:8px;">
                                        {{ $session->category ? $session->category->title : 'General' }}
                                    </span>
                                </td>
                                <td>
                                    @php $ss = $session->score ? $session->score->overall_readiness_score : 0; @endphp
                                    <span style="color:{{ $ss >= 80 ? 'var(--dash-success)' : ($ss >= 60 ? 'var(--dash-warning)' : 'var(--dash-danger)') }};font-weight:700;">
                                        {{ $ss }}%
                                    </span>
                                </td>
                                <td class="text-end" style="padding-right:24px;">
                                    <a href="{{ route('user.review', $session->id) }}" class="btn btn-sm" style="font-size:0.8rem;border-radius:8px;background-color:#3b82f6 !important;color:#ffffff !important;border:none !important;font-weight:600;">Review</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4" style="color:var(--tx3);">No recent sessions found. Start practicing!</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN (Sidebar) -->
        <div class="col-lg-4">
            
            <!-- Feature 16: Interview Readiness Radar Chart -->
            <div class="premium-card mb-4 text-center animate-fade-up delay-200">
                <h6 class="fw-bold mb-3">Readiness Radar</h6>
                <div class="chart-container-mobile" style="height:260px;">
                    <canvas id="radarChart"></canvas>
                </div>
            </div>

            <!-- Feature 9: Daily Practice Challenge -->
            <div id="card-daily-challenge" class="premium-card mb-4 animate-fade-up delay-300" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(59,130,246,0.08) 100%); border: 1px solid rgba(59,130,246,0.3);">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-calendar-day" style="color:var(--dash-primary);font-size:1.2rem;"></i>
                    <h6 class="fw-bold m-0" style="color:var(--dash-primary);text-transform:uppercase;letter-spacing:1px;font-size:0.8rem;">Today's Challenge</h6>
                </div>
                <h5 class="fw-bold mb-2">Answer 3 Behavioral Questions</h5>
                <p style="font-size:0.85rem;color:var(--tx2);margin-bottom:15px;">Earn extra XP and maintain your streak!</p>
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <span class="badge" style="background:rgba(251,191,36,0.15);color:var(--dash-warning);border:1px solid rgba(251,191,36,0.3);padding:6px 10px;border-radius:8px;">+50 XP</span>
                    <span class="badge" style="background:rgba(52,211,153,0.15);color:var(--dash-success);border:1px solid rgba(52,211,153,0.3);padding:6px 10px;border-radius:8px;">+1 Streak</span>
                </div>
                <a href="{{ route('interview.setup') }}" class="btn w-100 py-2 btn-shine" style="background:var(--dash-primary);color:white;font-weight:600;border-radius:12px;border:none;box-shadow: 0 4px 15px rgba(96,165,250,0.4);transition: 0.3s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(96,165,250,0.6)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 15px rgba(96,165,250,0.4)'">Start Challenge</a>
            </div>

            <!-- Feature 13: Upcoming Goals -->
            <div class="premium-card mb-4 animate-fade-up delay-400">
                <h6 class="fw-bold mb-3">Upcoming Goals</h6>
                <div class="p-3 mb-3" style="background:var(--bg3);border-radius:16px;border:1px solid var(--bd);">
                    <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;margin-bottom:5px;letter-spacing:0.5px;font-weight:600;">Current Goal</div>
                    <div class="fw-bold" style="color:var(--dash-success);font-size:1.1rem;">Reach 90% Readiness</div>
                    <div class="progress-track mt-2" style="height:6px;"><div class="progress-fill" style="width:{{ $avgScore ?? 0 }}%;background:var(--dash-success);"></div></div>
                </div>
                <div style="font-size:0.9rem;color:var(--tx2);font-weight:500;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-regular fa-circle text-muted"></i> Complete 2 Mock Interviews
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-regular fa-circle text-muted"></i> Practice Voice Rehearsal
                    </div>
                </div>
            </div>

            <!-- Feature 14: Achievement & Badges System -->
            <div class="premium-card mb-4 animate-fade-up delay-500">
                <h6 class="fw-bold mb-3">Achievements</h6>
                <div class="d-flex flex-wrap gap-2 justify-content-between">
                    <div class="text-center" title="First Interview Completed">
                        <div class="badge-icon mx-auto mb-2" style="background:rgba(251,191,36,0.15);border-color:rgba(251,191,36,0.3);color:{{ in_array('First Interview', $badgesEarned ?? []) ? 'var(--dash-warning)' : 'var(--tx3)' }}; opacity:{{ in_array('First Interview', $badgesEarned ?? []) ? '1' : '0.4' }};"><i class="fa-solid fa-medal"></i></div>
                        <div style="font-size:0.65rem;color:var(--tx3);font-weight:600;">First<br>Interview</div>
                    </div>
                    <div class="text-center" title="Practice Streak">
                        <div class="badge-icon mx-auto mb-2" style="background:rgba(248,113,113,0.15);border-color:rgba(248,113,113,0.3);color:{{ in_array('3-Day Streak', $badgesEarned ?? []) ? 'var(--dash-danger)' : 'var(--tx3)' }}; opacity:{{ in_array('3-Day Streak', $badgesEarned ?? []) ? '1' : '0.4' }};"><i class="fa-solid fa-fire"></i></div>
                        <div style="font-size:0.65rem;color:var(--tx3);font-weight:600;">3-Day<br>Streak</div>
                    </div>
                    <div class="text-center" title="STAR Master">
                        <div class="badge-icon mx-auto mb-2" style="background:rgba(59,130,246,0.15);border-color:rgba(59,130,246,0.3);color:{{ in_array('STAR Master', $badgesEarned ?? []) ? 'var(--dash-primary)' : 'var(--tx3)' }}; opacity:{{ in_array('STAR Master', $badgesEarned ?? []) ? '1' : '0.4' }};"><i class="fa-solid fa-star"></i></div>
                        <div style="font-size:0.65rem;color:var(--tx3);font-weight:600;">STAR<br>Master</div>
                    </div>
                    <div class="text-center" title="Excellent Communicator">
                        <div class="badge-icon mx-auto mb-2" style="background:rgba(52,211,153,0.15);border-color:rgba(52,211,153,0.3);color:{{ in_array('Top Comm', $badgesEarned ?? []) ? 'var(--dash-success)' : 'var(--tx3)' }}; opacity:{{ in_array('Top Comm', $badgesEarned ?? []) ? '1' : '0.4' }};"><i class="fa-solid fa-bullhorn"></i></div>
                        <div style="font-size:0.65rem;color:var(--tx3);font-weight:600;">Top<br>Comm</div>
                    </div>
                </div>
            </div>

            <!-- Feature 15: Notifications Panel -->
            <div class="premium-card mb-4 animate-fade-up delay-600">
                <h6 class="fw-bold mb-3">Recent Notifications</h6>
                <div class="notif-item">
                    <div class="notif-dot" style="background:var(--dash-success);"></div>
                    <div>
                        <div style="font-size:0.9rem;color:var(--tx);font-weight:600;">New Feedback Available</div>
                        <div style="font-size:0.8rem;color:var(--tx3);margin-top:2px;">Your session has been reviewed.</div>
                    </div>
                </div>
                <div class="notif-item">
                    <div class="notif-dot" style="background:var(--dash-primary);"></div>
                    <div>
                        <div style="font-size:0.9rem;color:var(--tx);font-weight:600;">New Learning Module</div>
                        <div style="font-size:0.8rem;color:var(--tx3);margin-top:2px;">"Mastering the STAR Method" is now available.</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- Scripts for Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const rootStyle = getComputedStyle(document.documentElement);
    const getThemeColor = (varName, fallback) => rootStyle.getPropertyValue(varName).trim() || fallback;
    
    const txColor = getThemeColor('--tx3', '#808090');
    const sfColor = getThemeColor('--sf', '#1e1e2d');
    // Using a reliable border color depending on whether .lm class is present on html
    const isLightMode = document.documentElement.classList.contains('lm');
    const gridColor = isLightMode ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.05)';
    const radarGridColor = isLightMode ? 'rgba(0,0,0,0.1)' : 'rgba(255,255,255,0.1)';

    // Shared Chart.js options for dark mode
    Chart.defaults.color = txColor;
    Chart.defaults.font.family = "'Inter', sans-serif";

    // Feature 4: Progress Line Chart
    const progressCtx = document.getElementById('progressChart').getContext('2d');
    
    // Mock data for Daily, Weekly, Monthly (Replacing with actual Recent Score Trend)
    const chartDataObj = {
        recent: { 
            labels: {!! json_encode(collect($scoreTrend ?? [])->pluck('date')) !!}, 
            data: {!! json_encode(collect($scoreTrend ?? [])->pluck('score')) !!} 
        }
    };

    let gradientLine = progressCtx.createLinearGradient(0, 0, 0, 300);
    gradientLine.addColorStop(0, 'rgba(96, 165, 250, 0.4)');
    gradientLine.addColorStop(1, 'rgba(96, 165, 250, 0.0)');

    let progressChart = new Chart(progressCtx, {
        type: 'line',
        data: {
            labels: chartDataObj.recent.labels.length ? chartDataObj.recent.labels : ['No Data'],
            datasets: [{
                label: 'Readiness Score',
                data: chartDataObj.recent.data.length ? chartDataObj.recent.data : [0],
                borderColor: '#60a5fa',
                backgroundColor: gradientLine,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: sfColor,
                pointBorderColor: '#60a5fa',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false }, 
                tooltip: { 
                    mode: 'index', 
                    intersect: false,
                    backgroundColor: 'rgba(30, 30, 45, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#34d399',
                    borderColor: '#60a5fa',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return ' Readiness Score: ' + context.parsed.y + '%';
                        }
                    }
                } 
            },
            scales: {
                y: { beginAtZero: true, max: 100, grid: { color: gridColor, drawBorder: false } },
                x: { grid: { display: false, drawBorder: false } }
            }
        }
    });

    // Feature 16: Readiness Radar Chart
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    
    // Dynamic values from backend
    let clarity = {{ $radarData['clarity'] ?? 0 }};
    let relevance = {{ $radarData['relevance'] ?? 0 }};
    let grammar = {{ $radarData['grammar'] ?? 0 }};
    let professionalism = {{ $radarData['professionalism'] ?? 0 }};
    let confidence = {{ $radarData['confidence'] ?? 0 }};

    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: ['Clarity', 'Relevance', 'Grammar', 'Professionalism', 'Confidence'],
            datasets: [{
                label: 'Score Level',
                data: [clarity, relevance, grammar, professionalism, confidence],
                backgroundColor: 'rgba(52, 211, 153, 0.2)',
                borderColor: '#34d399',
                pointBackgroundColor: '#34d399',
                pointBorderColor: sfColor,
                pointHoverBackgroundColor: sfColor,
                pointHoverBorderColor: '#34d399',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                r: {
                    angleLines: { color: radarGridColor },
                    grid: { color: radarGridColor },
                    pointLabels: { color: txColor, font: { size: 11 } },
                    ticks: { display: false, min: 0, max: 100, stepSize: 20 }
                }
            }
        }
    });
});
</script>

<!-- Gamification Confetti -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Trigger confetti if they have a streak > 2 or just earned a badge
        const streak = {{ $currentStreak ?? 0 }};
        if(streak >= 3) {
            setTimeout(() => {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 },
                    colors: ['#60a5fa', '#34d399', '#fbbf24', '#a78bfa']
                });
            }, 1000);
        }
    });
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.driver === 'undefined') return;
        const driver = window.driver.js.driver;

        const stepsMobile = [
            { element: '#mob-bottom-nav', popover: { title: 'Mobile Navigation', description: 'Access Mock Interviews, Learning Lab, and Progress Tracking right from the bottom bar.', side: "top", align: 'center' }},
            { element: '.card-grad-success', popover: { title: 'Overall Readiness', description: 'This score represents your overall readiness based on recent interview performances.', side: "bottom", align: 'start' }},
            { element: '.stat-grid', popover: { title: 'Quick Statistics', description: 'Get a quick overview of your total sessions, average rating, and daily streak.', side: "top", align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Progress Chart', description: 'Visualize your interview progress and score trends over time.', side: "top", align: 'start' }},
            { element: '#card-ai-recommendations', popover: { title: 'AI Recommendations', description: 'Get personalized suggestions to improve your specific weak points.', side: "top", align: 'start' }},
            { element: '#card-daily-challenge', popover: { title: 'Daily Challenge', description: 'Complete daily challenges to earn extra XP and maintain your practice streak!', side: "top", align: 'start' }},
            { element: '#card-recent-sessions', popover: { title: 'Recent Sessions', description: 'Review your past mock interviews and see detailed feedback for each.', side: "top", align: 'start' }},
            { element: '#mobThBtn', popover: { title: 'Theme Toggle', description: 'Switch between light and dark mode.', side: "bottom", align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#dbSidebar', popover: { title: 'Navigation Menu', description: 'Access all features including Mock Interviews, Learning Lab, and Progress Tracking from here.', side: "right", align: 'start' }},
            { element: '.card-grad-success', popover: { title: 'Overall Readiness', description: 'This score represents your overall readiness based on recent interview performances.', side: "bottom", align: 'start' }},
            { element: '.stat-grid', popover: { title: 'Quick Statistics', description: 'Get a quick overview of your total sessions, average rating, and daily streak.', side: "top", align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Progress Chart', description: 'Visualize your interview progress and score trends over time.', side: "top", align: 'start' }},
            { element: '#card-ai-recommendations', popover: { title: 'AI Recommendations', description: 'Get personalized suggestions to improve your specific weak points.', side: "bottom", align: 'start' }},
            { element: '#card-daily-challenge', popover: { title: 'Daily Challenge', description: 'Complete daily challenges to earn extra XP and maintain your practice streak!', side: "bottom", align: 'start' }},
            { element: '#card-recent-sessions', popover: { title: 'Recent Sessions', description: 'Review your past mock interviews and see detailed feedback for each.', side: "top", align: 'start' }},
            { element: '#dbThBtn', popover: { title: 'Theme Toggle', description: 'Switch between light and dark mode for a comfortable viewing experience.', side: "bottom", align: 'center' }},
            { element: '#notifWrap', popover: { title: 'Notifications', description: 'Stay updated with feedback on your interviews and platform announcements.', side: "bottom", align: 'center' }},
            { element: '#profileWrap', popover: { title: 'Your Profile', description: 'Manage your account settings and preferences.', side: "bottom", align: 'end' }}
        ];

        const driverObj = driver({
            showProgress: true,
            animate: true,
            popoverClass: document.documentElement.classList.contains('lm') ? 'driverjs-theme-light' : 'driverjs-theme-dark',
            steps: {{ $isMobile ? 'true' : 'false' }} ? stepsMobile : stepsDesktop,
            onDestroyStarted: () => {
                if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tutorial?")) {
                    driverObj.destroy();
                    localStorage.setItem('onboarding_completed', 'true');
                }
            },
        });

        window.startOnboardingTour = function() {
            driverObj.drive();
        };

        if (!localStorage.getItem('onboarding_completed')) {
            setTimeout(() => {
                startOnboardingTour();
            }, 500);
        }
    });
</script>
@endpush

@endsection
