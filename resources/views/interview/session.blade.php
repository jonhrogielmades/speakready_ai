@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Interview Workspace')
@section('content')
<style>
    .pulse-anim { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.6; } 100% { opacity: 1; } }
    @keyframes ai-wave { 0% { height: 4px; } 100% { height: 24px; } }
    .ai-wave-bar { width:4px; height:4px; background:var(--pur); border-radius:2px; margin:0 2px; }
    .ai-speaking .ai-wave-bar { animation: ai-wave 400ms alternate infinite ease-in-out; }
    .ai-speaking .ai-wave-bar:nth-child(1) { animation-delay: 0ms; }
    .ai-speaking .ai-wave-bar:nth-child(2) { animation-delay: 100ms; }
    .ai-speaking .ai-wave-bar:nth-child(3) { animation-delay: 200ms; }
    .ai-speaking .ai-wave-bar:nth-child(4) { animation-delay: 300ms; }
    .ai-speaking .ai-wave-bar:nth-child(5) { animation-delay: 400ms; }
    .panel { 
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-soft, 0 10px 28px rgba(0, 0, 0, 0.14));
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .panel:hover {
        transform: translateY(-1px);
        border-color: rgba(96, 165, 250, 0.34);
        box-shadow: var(--shadow-card, 0 18px 45px rgba(0, 0, 0, 0.18));
    }
    #sec-interview-session { --session-gap: 20px; }
    #workspaceRow {
        --bs-gutter-x: var(--session-gap);
        --bs-gutter-y: var(--session-gap);
    }
    #sec-interview-session .ai-avatar-panel {
        border-radius: 16px !important;
        margin-bottom: var(--session-gap) !important;
        box-shadow: var(--shadow-soft, 0 10px 28px rgba(0, 0, 0, 0.14)) !important;
    }
    #interviewControls {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 14px;
        margin-bottom: var(--session-gap) !important;
        box-shadow: var(--shadow-soft, 0 10px 28px rgba(0, 0, 0, 0.14));
    }
    #chatTranscriptContainer {
        border-radius: 14px !important;
        background: var(--bg3) !important;
    }
    #introContainer {
        border-radius: 16px !important;
        margin-top: var(--session-gap) !important;
    }
    .intro-ai-icon {
        width: 68px;
        height: 68px;
        border-radius: 18px;
        background:
            radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.72), transparent 28%),
            linear-gradient(145deg, rgba(59, 130, 246, 0.18), rgba(139, 92, 246, 0.14));
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 22px;
        border: 1px solid rgba(96, 165, 250, 0.28);
        box-shadow: 0 12px 28px rgba(59, 130, 246, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.45);
        color: #60a5fa;
    }
    .intro-ai-icon i {
        font-size: 1.8rem;
        filter: drop-shadow(0 4px 8px rgba(96, 165, 250, 0.28));
    }
    #introContainer .intro-badge-grid {
        display: grid !important;
        grid-template-columns: repeat(3, max-content);
        justify-content: center !important;
        justify-items: center;
        gap: 7px 8px !important;
        margin-bottom: 24px !important;
    }
    #introContainer .intro-badge-grid .db-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: fit-content;
        max-width: 100%;
        white-space: nowrap;
    }
    #introContainer .intro-badge-grid .db-badge:nth-child(4) {
        grid-column: 1 / -1;
        justify-self: center;
    }
    .interview-meta-line { flex-wrap: wrap; }
    .panel-title { font-weight:800;margin-bottom:16px;display:flex;align-items:center;font-size:1rem;color:var(--tx); letter-spacing: 0; }
    .panel-title > i {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(96, 165, 250, 0.12);
        color: #60a5fa;
        margin-right: 8px !important;
        font-size: 0.82rem;
    }
    .stat-row { display:flex;justify-content:space-between;margin-bottom:12px;font-size:.9rem;color:var(--tx2); font-weight: 500; }
    .progress-bar-bg { background:var(--bg3);height:8px;border-radius:4px;overflow:hidden;margin-bottom:15px; }
    .progress-bar-fill { background:#60a5fa;height:100%;transition:width 0.3s; }
    .star-item { display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--bg3);border-radius:12px;margin-bottom:10px;font-size:.9rem; font-weight: 500; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .star-item:hover { transform: translateY(-1px); background: var(--sf); box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--bd); }
    .star-item i { font-size:1.1rem; transition: transform 0.3s, text-shadow 0.3s; }
    .star-item i.fa-circle-check { text-shadow: 0 0 10px rgba(52, 211, 153, 0.5); }
    @keyframes scanAnim { 0% { top: 0%; opacity: 0.5; } 50% { top: 100%; opacity: 1; } 100% { top: 0%; opacity: 0.5; } }
    @keyframes avatarPulse { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(3.5); opacity: 0; } }
    
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    
    /* Animations */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }

    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
    .sound-wave { position:absolute;border-radius:50%;width:100%;height:100%;display:none; }
    .session-chip { display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:7px 11px;font-size:.78rem;font-weight:700;white-space:nowrap;border:1px solid var(--bd);background:var(--bg3);color:var(--tx); }
    .session-chip.warning { border-color:rgba(245,158,11,.45);background:rgba(245,158,11,.12);color:#f59e0b; }
    .session-chip.danger { border-color:rgba(239,68,68,.45);background:rgba(239,68,68,.12);color:#ef4444; }
    .real-interview-mode .coaching-only { display:none !important; }
    .recovery-pill { display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;background:rgba(52,211,153,.12);color:#34d399;font-weight:700;font-size:.76rem;margin-bottom:14px; }
    .question-timer-anchor { position:absolute;top:15px;right:15px;z-index:55; }
    .mobile-camera-pip { position:absolute; top:15px; right:15px; width:80px; height:105px; border-radius:8px; overflow:hidden; border:2px solid rgba(255,255,255,0.3); z-index:50; box-shadow: 0 4px 15px rgba(0,0,0,0.6); }

    /* Circular Audio Spectrum */
    .circular-spectrum { position: absolute; top: 50%; left: 50%; width: 0; height: 0; display: none; z-index: 5; }
    .circular-spectrum .spectrum-bar { position: absolute; bottom: 0; left: -4px; width: 8px; background: linear-gradient(to top, #8b5cf6, #34d399); border-radius: 4px; transform-origin: bottom center; height: 6px; transition: height 0.05s ease-out; box-shadow: 0 0 12px rgba(52,211,153,0.6); }
    
    /* Responsive overrides */
    @media (max-width: 768px) {
        #sec-interview-session { --session-gap: 12px; }
        #sec-interview-session > .interview-session-header {
            margin-bottom: 12px !important;
            text-align: center;
        }
        .interview-session-title {
            font-size: 1.18rem !important;
            line-height: 1.18 !important;
            margin-bottom: 8px !important;
            letter-spacing: 0 !important;
        }
        .interview-session-header .sr-page-actions.interview-meta-line,
        #sec-interview-session .interview-meta-line {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 4px !important;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            font-size: 0.56rem !important;
        }
        .interview-session-header .sr-page-actions.interview-meta-line span,
        #sec-interview-session .interview-meta-line span {
            min-width: 0;
            width: 100%;
            padding: 5px 3px;
            border: 1px solid var(--bd);
            border-radius: 999px;
            background: var(--bg3);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            justify-content: center;
            gap: 2px;
        }
        .interview-session-header .sr-page-actions.interview-meta-line span i,
        #sec-interview-session .interview-meta-line span i {
            margin-right: 2px !important;
            font-size: 0.54rem;
            flex: 0 0 auto;
        }
        .avatar-wrapper { transform: scale(0.62); }
        .circular-spectrum { transform: scale(0.62); }
        .ai-avatar-panel { height: 220px !important; }
        .ai-avatar-panel .interviewer-badge {
            border-radius: 999px !important;
            padding: 5px 9px !important;
            font-size: 0.62rem !important;
            font-weight: 800;
            letter-spacing: 0;
        }
        .ai-question-overlay {
            padding: 42px 12px 12px !important;
        }
        .ai-question-overlay > .d-flex {
            gap: 0 !important;
        }
        .mobile-camera-pip {
            top: 12px !important;
            right: 12px !important;
            width: 70px !important;
            height: 92px !important;
            border-radius: 10px !important;
            border-width: 1px !important;
        }
        #qCounter {
            font-size: 0.66rem !important;
            padding: 5px 8px !important;
            border-radius: 999px;
        }
        .panel {
            padding: 12px !important;
            margin-bottom: var(--session-gap);
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .panel:hover { transform: none; }
        .panel-title {
            font-size: 0.86rem;
            margin-bottom: 10px;
            line-height: 1.15;
        }
        .panel-title > i {
            width: 24px;
            height: 24px;
            border-radius: 7px;
            font-size: 0.72rem;
        }
        #liveFeedbackPanel .panel-title > i {
            background: rgba(52, 211, 153, 0.13);
            color: #34d399;
        }
        #starPanel .panel-title > i {
            background: rgba(251, 191, 36, 0.14);
            color: #fbbf24 !important;
        }
        #voiceAnalyticsPanel .panel-title > i {
            background: rgba(6, 182, 212, 0.14);
            color: #06b6d4;
        }
        #sec-interview-session .col-lg-4 > .panel:last-child .panel-title > i {
            background: rgba(168, 85, 247, 0.13);
            color: #a855f7;
        }
        #overallReadiness {
            font-size: 1.7rem !important;
            line-height: 1.05;
        }
        .stat-row {
            margin-bottom: 8px;
            padding: 8px 9px;
            border-radius: 10px;
            background: var(--bg3);
            font-size: 0.78rem;
        }
        .star-item {
            padding: 9px 10px;
            margin-bottom: 8px;
            border-radius: 10px;
            font-size: 0.78rem;
        }
        .star-item i {
            font-size: 0.92rem;
        }
        #workspaceRow { --bs-gutter-x: 0; --bs-gutter-y: var(--session-gap); }
        #interviewControls {
            align-items: stretch !important;
            border-radius: 14px;
            padding: 10px;
            gap: 8px !important;
            margin-bottom: var(--session-gap) !important;
        }
        #interviewControls > div { gap: 8px !important; width: 100%; }
        #interviewControls > div:first-child {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        #interviewControls > div:first-child .btn {
            width: 100%;
            min-height: 42px;
            padding: 0.5rem 0.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 0.78rem;
            line-height: 1.15;
        }
        #interviewControls > div:first-child .btn i {
            margin: 0 !important;
            font-size: 0.76rem;
        }
        #interviewControls > div:nth-child(2) {
            display: flex !important;
            flex-wrap: wrap;
            align-items: center !important;
            justify-content: center !important;
        }
        #recordingTimer {
            margin-right: 0 !important;
            font-size: 0.74rem !important;
            min-width: 40px;
            text-align: center;
        }
        #voiceControls {
            width: auto;
        }
        #voiceControls .d-flex.gap-2 {
            display: flex !important;
            align-items: center;
            justify-content: center;
            gap: 7px !important;
            flex-wrap: nowrap !important;
        }
        #voiceControls .btn {
            width: 42px;
            min-width: 0;
            min-height: 34px !important;
            height: 34px !important;
            padding: 0.36rem 0.42rem !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px !important;
        }
        #voiceControls .btn i {
            margin: 0 !important;
            font-size: 0.68rem;
        }
        #interviewControls .btn { min-height: 38px; padding: 0.43rem 0.58rem; }
        #interviewControls .send-answer-btn {
            grid-column: 1 / -1;
            justify-self: center;
            width: min(100%, 132px) !important;
            min-width: 0 !important;
            min-height: 38px !important;
            padding: 0.46rem 0.62rem !important;
            border-radius: 10px !important;
            font-size: 0.68rem !important;
            line-height: 1.1;
            margin-top: 2px;
        }
        #interviewControls .send-answer-btn i {
            font-size: 0.68rem;
            margin-left: 0.35rem !important;
        }
        #chatTranscriptContainer { padding: 10px !important; margin-bottom: 12px !important; max-height: 240px !important; }
        #chatTranscriptContainer:empty::before {
            content: 'Transcript will appear here';
            color: var(--tx3);
            font-size: 0.74rem;
            display: block;
            text-align: center;
            padding: 18px 8px;
        }
        #answerTextarea {
            min-height: 92px !important;
            border-radius: 12px !important;
            padding: 11px 12px !important;
            font-size: 0.86rem !important;
            line-height: 1.4;
        }
        #answerForm > .d-flex.justify-content-between {
            margin-bottom: 0 !important;
            align-items: flex-start !important;
            gap: 8px;
        }
        #answerForm > .d-flex.justify-content-between > div {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--bd);
            border-radius: 10px;
            background: var(--bg3);
            font-size: 0.7rem !important;
            line-height: 1.35;
        }
        #autoSaveIndicator {
            display: inline-flex;
            margin-left: 6px !important;
        }
        #introContainer { padding: 16px !important; max-width: 100% !important; }
        #introContainer .intro-ai-icon {
            width: 56px;
            height: 56px;
            border-radius: 15px;
            margin-bottom: 16px;
        }
        #introContainer .intro-ai-icon i {
            font-size: 1.45rem;
        }
        #introContainer h4 { font-size: 1.18rem !important; line-height: 1.22 !important; }
        #introContainer p { font-size: 0.9rem !important; margin-bottom: 18px !important; }
        #introContainer .intro-badge-grid .db-badge {
            font-size: 0.7rem !important;
        }
        #introContainer .intro-start-btn {
            width: 100% !important;
            min-height: 44px;
            padding: 0.62rem 1rem !important;
            border-radius: 11px !important;
            font-size: 0.9rem !important;
            box-shadow: 0 6px 16px rgba(96,165,250,0.32) !important;
        }
        #introContainer .intro-start-btn i {
            font-size: 0.78rem;
            margin-left: 0.45rem !important;
        }
        .session-chip { width:auto;max-width:100%;justify-content:center; }
        .question-timer-anchor { top:112px;right:12px; }
        #aiQuestionText { max-height: 4.5em; overflow-y: auto; }
    }

    @media (max-width: 380px) {
        #sec-interview-session { --session-gap: 10px; }
        .interview-session-title { font-size: 1.06rem !important; }
        .interview-session-header .sr-page-actions.interview-meta-line,
        #sec-interview-session .interview-meta-line {
            gap: 3px !important;
            font-size: 0.5rem !important;
        }
        .interview-session-header .sr-page-actions.interview-meta-line span,
        #sec-interview-session .interview-meta-line span {
            padding: 5px 2px;
        }
        .interview-session-header .sr-page-actions.interview-meta-line span i,
        #sec-interview-session .interview-meta-line span i {
            font-size: 0.5rem;
        }
        .ai-avatar-panel { height: 204px !important; }
        .mobile-camera-pip { width: 62px !important; height: 82px !important; }
        .ai-question-overlay { padding: 38px 10px 10px !important; }
        .question-timer-anchor { top: 100px; right: 10px; }
        .session-chip { padding: 6px 8px; font-size: 0.7rem; }
        #interviewControls { padding: 9px; }
        #interviewControls .btn { font-size: 0.82rem; }
        #interviewControls > div:first-child {
            gap: 6px !important;
        }
        #interviewControls > div:first-child .btn {
            min-height: 38px;
            padding: 0.42rem 0.45rem;
            font-size: 0.68rem;
        }
        #interviewControls > div:first-child .btn i {
            font-size: 0.68rem;
        }
        #recordingTimer {
            font-size: 0.72rem !important;
            min-width: 38px;
        }
        #voiceControls .d-flex.gap-2 {
            gap: 6px !important;
        }
        #voiceControls .btn {
            width: 40px;
            min-height: 32px !important;
            height: 32px !important;
            padding: 0.35rem 0.42rem !important;
        }
        #voiceControls .btn i {
            font-size: 0.66rem;
        }
    }
</style>
@include('partials.page-hero-styles')

<div class="db-section active" id="sec-interview-session">
    @if(session('active_interview_id'))
        @php
            $sessionRecord = \App\Models\InterviewSession::with('category')
                ->where('user_id', auth()->id())
                ->find(session('active_interview_id'));
            if ($sessionRecord) {
                $num = $sessionRecord->num_questions ?? 5;
                $selectedQuestionTypes = json_decode($sessionRecord->question_types ?? '[]', true);
                $selectedQuestionTypes = is_array($selectedQuestionTypes) ? array_values(array_filter($selectedQuestionTypes)) : [];
                // Try to find questions specifically generated for this session first
                $questions = \App\Models\Question::where('interview_session_id', $sessionRecord->id)->get();
                
                // Fallback to local category questions if none were specifically generated
                if ($questions->isEmpty()) {
                    // Try to match exact difficulty and active status first
                    $questions = \App\Models\Question::where('category_id', $sessionRecord->category_id)
                        ->where('status', 'active')
                        ->where('difficulty', $sessionRecord->difficulty)
                        ->when(!empty($selectedQuestionTypes), fn($query) => $query->whereIn('type', $selectedQuestionTypes))
                        ->inRandomOrder()->limit($num)->get();
                        
                    // If no questions match the difficulty, fallback to any active questions in category
                    if ($questions->isEmpty()) {
                        $questions = \App\Models\Question::where('category_id', $sessionRecord->category_id)
                            ->where('status', 'active')
                            ->when(!empty($selectedQuestionTypes), fn($query) => $query->whereIn('type', $selectedQuestionTypes))
                            ->inRandomOrder()->limit($num)->get();
                    }
                }
            } else {
                $questions = collect([]);
            }
        @endphp

        @if($sessionRecord && $questions->count() > 0)
        <!-- Header Info -->
        <div class="interview-session-header">
            <div class="sr-page-hero">
                <div class="sr-page-hero-inner">
                    <div class="sr-page-hero-copy">
                        <h4 class="sr-page-hero-title text-gradient-primary">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 6h16v10H8l-4 4V6Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M9 10h6M9 13h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            Interview Workspace
                        </h4>
                        <p class="sr-page-hero-subtitle">Practice role-specific questions and build answers supported by evidence.</p>
                    </div>
                </div>
                <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
                    <defs>
                        <linearGradient id="workspacePanel" x1="36" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#DBEAFE"/>
                            <stop offset="1" stop-color="#ECFEFF"/>
                        </linearGradient>
                        <linearGradient id="workspaceBlue" x1="64" y1="38" x2="164" y2="118" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#3B82F6"/>
                            <stop offset="1" stop-color="#06B6D4"/>
                        </linearGradient>
                    </defs>
                    <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#workspacePanel)" stroke="#BFDBFE" stroke-width="3"/>
                    <rect x="58" y="48" width="88" height="58" rx="16" fill="url(#workspaceBlue)"/>
                    <circle cx="88" cy="77" r="7" fill="#EFF6FF"/>
                    <circle cx="118" cy="77" r="7" fill="#EFF6FF"/>
                    <path d="M91 92c8 6 21 6 29 0" stroke="#EFF6FF" stroke-width="5" stroke-linecap="round"/>
                    <path d="M78 48v-7a28 28 0 0 1 56 0v7" stroke="#60A5FA" stroke-width="7" stroke-linecap="round"/>
                    <path d="M146 67h28v27h-16l-12 12V67Z" fill="#BAE6FD" stroke="#93C5FD" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M154 77h12M154 86h8" stroke="#2563EB" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="161" cy="47" r="18" fill="#22C55E"/>
                    <path d="M154 47l5 5 10-12" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
                </svg>
            </div>
            <div class="sr-page-actions interview-meta-line" style="font-size:.85rem;color:var(--tx3);">
                <span><i class="fa-solid fa-layer-group me-1"></i> {{ $sessionRecord->category->title ?? 'General' }}</span>
                <span><i class="fa-solid fa-gauge-high me-1"></i> {{ ucfirst($sessionRecord->difficulty) }}</span>
                <span><i class="fa-solid fa-briefcase me-1"></i> {{ $sessionRecord->target_position ?? 'Standard' }}</span>
                <span><i class="fa-solid fa-user-tie me-1"></i> {{ ucfirst(str_replace('_', ' ', $sessionRecord->interviewer_strictness ?? 'neutral')) }}</span>
            </div>
        </div>

        <div id="workspaceWrapper" style="display:none;">
        <div class="row g-4" id="workspaceRow">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <!-- Progress Tracker Removed by User -->

                <!-- Simulated AI Video Avatar Panel -->
                <div class="panel p-0 ai-avatar-panel animate-fade-up delay-100" style="overflow:hidden;border:1px solid var(--bd);background:#000;position:relative;height:280px;border-radius:24px;margin-bottom:24px;box-shadow:0 15px 40px rgba(0,0,0,0.15);">
                    <div style="position:absolute; inset:0; background: radial-gradient(circle at top right, rgba(139,92,246,0.3), transparent 60%), radial-gradient(circle at bottom left, rgba(59,130,246,0.3), transparent 60%); z-index:1; pointer-events:none;"></div>
                    <!-- Mobile Picture-in-Picture Camera -->
                    @if(data_get($sessionRecord->accommodation_profile, 'camera_coaching', false))
                    <div class="d-block d-lg-none mobile-camera-pip">
                        <video id="userCameraMobile" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);background:#222;"></video>
                    </div>
                    @endif
                    <!-- Question Counter (Top Left) -->
                    <div style="position:absolute; top:15px; left:15px; z-index:50;">
                        <span class="badge bg-white text-dark shadow-sm" style="font-size:0.8rem;white-space:nowrap;padding: 6px 10px;" id="qCounter">1/10</span>
                    </div>
                    <div class="question-timer-anchor">
                        <span class="session-chip" id="questionTimerChip"><i class="fa-regular fa-clock"></i><span id="perQuestionTimer">Self-paced</span></span>
                    </div>

                    <div id="aiAvatarContainer" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);">
                        <div class="avatar-wrapper" id="aiAvatarHead" style="width:110px;height:110px;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;transition:border-color 0.4s;">
                            <!-- The Image Container (with border, glow, and clipping for the image itself) -->
                            <div style="width:100%;height:100%;background:rgba(255,255,255,0.1);border-radius:50%;border:4px solid #8b5cf6;overflow:hidden;position:relative;z-index:10;box-shadow: 0 0 25px rgba(139,92,246,0.5);">
                                <img src="{{ asset('img/ai_avatar.jpg') }}" alt="AI Avatar" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        </div>
                        
                        <!-- Circular Audio Spectrum Waveform -->
                        <div class="circular-spectrum sound-wave">
                            @for ($i = 0; $i < 36; $i++)
                                @php 
                                    // Use a pseudo-random sequence so it looks dynamic but is consistent
                                    $animClass = 'sb' . (($i * 7) % 10 + 1); 
                                    $rot = $i * 10;
                                @endphp
                                <div class="spectrum-bar {{ $animClass }}" style="transform: rotate({{ $rot }}deg) translateY(-65px);"></div>
                            @endfor
                        </div>
                    </div>
                    <!-- Overlay Text -->
                    <div class="ai-question-overlay" style="display:block;position:absolute;bottom:0;left:0;width:100%;background:linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.7) 60%, transparent 100%);padding:30px 20px 20px 20px; z-index:20;">
                        <div class="d-flex justify-content-start align-items-end gap-3">
                            <div>
                                <span class="badge mb-2 interviewer-badge" style="background:var(--pur);color:white;font-size:0.75rem;"><i class="fa-solid fa-bolt me-1"></i> {{ $sessionRecord->company_persona ?: 'Interviewer' }}</span>
                                <div id="aiQuestionText" style="color:white;font-size:0.85rem;font-weight:600;line-height:1.4;">Loading your first question...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unified Responsive Interview Controls (Desktop & Mobile) -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mb-4 animate-fade-up delay-150" id="interviewControls" style="opacity: 0; pointer-events: none; transition: opacity 0.3s;">
                    <!-- Left: Navigation / Secondary -->
                    <div class="d-flex gap-2 w-100 flex-fill">
                        <button type="button" class="btn btn-outline-info flex-fill" onclick="repeatQuestion()" style="border-radius:12px;"><i class="fa-solid fa-volume-high me-2"></i>Repeat</button>
                        <button type="button" class="btn btn-outline-danger flex-fill" onclick="finishInterview()" style="border-radius:12px;"><i class="fa-solid fa-flag-checkered me-2"></i>End Session</button>
                    </div>
                    
                    <!-- Right: Primary Actions (Mic + Send) -->
                    <div class="d-flex gap-2 w-100 flex-fill justify-content-md-end align-items-center">
                        <span id="recordingTimer" style="font-family:monospace;font-size:1.1rem;color:#f87171;display:block;margin-right:10px;font-weight:bold;">00:00</span>
                        
                        <!-- Voice Recording Controls -->
                        <div id="voiceControls" style="display:none; margin:0; padding:0; border:none; background:transparent;">
                            @if(session('game_level_id'))
                                <button type="button" id="holdToTalkBtn" class="btn btn-danger" style="border-radius:12px; font-weight:700; box-shadow: 0 4px 15px rgba(239,68,68,0.4); padding: 0.5rem 1rem; user-select:none; touch-action:manipulation;">
                                    <i class="fa-solid fa-microphone me-2"></i>HOLD
                                </button>
                            @else
                                <div class="d-flex gap-2">
                                    <button type="button" id="micPauseBtn" class="btn btn-warning" onclick="toggleRecordingPause()" style="display:inline-flex; border-radius:12px;"><i class="fa-solid fa-pause"></i></button>
                                    <button type="button" id="micStopBtn" class="btn btn-danger" onclick="stopRecording()" style="display:inline-flex; border-radius:12px;"><i class="fa-solid fa-stop"></i></button>
                                </div>
                            @endif
                        </div>

                        <button type="button" class="btn px-4 flex-fill next-btn-class send-answer-btn text-white btn-shine" style="background:var(--dash-primary, #60a5fa); border:none; box-shadow: 0 4px 15px rgba(96,165,250,0.4); font-weight:600; min-width: 160px; border-radius:12px;" onclick="submitAnswer()">Send Answer <i class="fa-solid fa-paper-plane ms-2"></i></button>
                    </div>
                </div>

                <!-- Answer Response System -->
                <div class="panel mb-4 animate-fade-up delay-200">
                    <div class="panel-title">
                        <i class="fa-solid fa-pen-nib me-2"></i> Your Response
                        @if(session('game_level_id'))
                            <span class="badge ms-auto" style="background:#ef4444; color:white;"><i class="fa-solid fa-gamepad me-1"></i> GAME MODE</span>
                        @endif
                    </div>
                    
                    <form id="answerForm">
                        <!-- Voice controls moved to interviewControls panel -->

                        <div id="chatTranscriptContainer" style="max-height: 350px; overflow-y: auto; padding: 15px; margin-bottom: 20px; background: rgba(0,0,0,0.15); border-radius: 12px; border: 1px solid var(--bd); display: flex; flex-direction: column; gap: 15px;">
                            <!-- Chat bubbles will go here -->
                        </div>
                        <textarea id="answerTextarea" class="oinp mb-2" style="min-height:80px;font-size:.95rem" placeholder="Type your answer here, or use voice to auto-transcribe..."></textarea>
                        <div class="p-3 mb-3" style="border:1px solid var(--bd);border-radius:12px;background:var(--bg3)">
                            <div class="d-flex justify-content-between gap-2 mb-2"><label for="selfConfidenceRange" style="font-size:.8rem;font-weight:700;color:var(--tx)">How prepared did you feel for this question?</label><span id="selfConfidenceValue" class="badge text-bg-secondary">50%</span></div>
                            <input id="selfConfidenceRange" type="range" min="0" max="100" value="50" class="form-range" oninput="document.getElementById('selfConfidenceValue').textContent=this.value+'%'">
                            <div style="font-size:.72rem;color:var(--tx3)">This is your self-report. It is shown separately from delivery stability and is not inferred from your face or voice.</div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div style="font-size:.8rem;color:var(--tx3)">
                                <span id="wordCount">0 words</span> • <span id="charCount">0 characters</span>
                                <span id="autoSaveIndicator" class="ms-3 text-success" style="display:none;"><i class="fa-solid fa-check me-1"></i>Auto-saved</span>
                            </div>
                        </div>

                        <!-- Bottom mobile buttons moved to unified control panel above -->
                    </form>
                </div>
            </div>

            <!-- Side Panels -->
            <div class="col-lg-4">
                <!-- Session Navigation (Mobile fallback / Overview) -->
                <!-- Optional descriptive camera coach; never used in readiness scoring. -->
                @if(data_get($sessionRecord->accommodation_profile, 'camera_coaching', false))
                <div class="panel d-none d-lg-block animate-fade-up delay-100" id="cameraPanel">
                    <div class="panel-title"><i class="fa-solid fa-camera-web me-2"></i> Optional Camera Coach</div>
                    <div style="position:relative;background:#000;height:180px;border-radius:12px;margin-bottom:15px;overflow:hidden;display:flex;align-items:center;justify-content:center">
                        <video id="userCamera" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);"></video>
                        <div class="face-scanner-box" id="faceScannerBox" style="display:none;position:absolute;width:120px;height:120px;border:2px solid #34d399;border-radius:12px;box-shadow:0 0 15px rgba(52,211,153,0.3);transition:all 0.3s ease;">
                            <div class="scan-line" style="width:100%;height:2px;background:#34d399;position:absolute;top:0;animation: scanAnim 2s infinite linear;box-shadow:0 0 8px #34d399;"></div>
                        </div>
                        <div style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.6);padding:2px 8px;border-radius:4px;font-size:.7rem;color:#34d399"><i class="fa-solid fa-circle text-success pulse-anim" style="font-size:.5rem;margin-right:4px"></i> AI Track</div>
                    </div>
                    <div class="stat-row"><span>Face in frame</span><span id="stEyeContact" class="text-secondary">Waiting</span></div>
                    <div class="stat-row mb-0"><span>Detection quality</span><span id="stPosture" class="text-secondary">Not scored</span></div>
                    <div class="small mt-2" style="color:var(--tx3)">Framing feedback is descriptive and excluded from readiness.</div>
                </div>
                @endif

                <!-- AI Visualizer Panel -->
                <div class="panel animate-fade-up delay-200 coaching-only" id="liveFeedbackPanel">
                    <div class="panel-title"><i class="fa-solid fa-chart-pie me-2"></i> Live Practice Signals</div>
                    <div class="text-center mb-3">
                        <div style="font-size:2rem;font-weight:700;color:#34d399" id="overallReadiness">--%</div>
                        <div style="font-size:.75rem;color:var(--tx3)">Coaching estimate—not an assessment</div>
                    </div>
                    <div class="stat-row"><span>Clarity</span><span id="metClarity">--%</span></div>
                    <div class="stat-row"><span>Relevance</span><span id="metRelevance">--%</span></div>
                    <div class="stat-row"><span>Grammar</span><span id="metGrammar">--%</span></div>
                    <div class="stat-row mb-0"><span>Professionalism</span><span id="metProf">--%</span></div>
                </div>

                <!-- STAR Framework Analyzer -->
                <div class="panel animate-fade-up delay-300 coaching-only" id="starPanel">
                    <div class="panel-title"><i class="fa-solid fa-star me-2" style="color:#fbbf24"></i> STAR Analyzer</div>
                    <div class="star-item"><span>Situation</span><i class="fa-solid fa-circle-xmark text-danger" id="starS"></i></div>
                    <div class="star-item"><span>Task</span><i class="fa-solid fa-circle-xmark text-danger" id="starT"></i></div>
                    <div class="star-item"><span>Action</span><i class="fa-solid fa-circle-xmark text-danger" id="starA"></i></div>
                    <div class="star-item"><span>Result</span><i class="fa-solid fa-circle-xmark text-danger" id="starR"></i></div>
                    <div style="margin-top:10px;font-size:.8rem;color:#fbbf24;background:rgba(251,191,36,.1);padding:10px;border-radius:8px;border:1px solid rgba(251,191,36,.3)" id="coachingTip">
                        <i class="fa-solid fa-lightbulb me-1"></i> <strong>Biggest Suggestion:</strong> Give one specific example, your role, the action you took, and the result.
                    </div>
                </div>

                <!-- Voice Analytics Module -->
                <div class="panel animate-fade-up delay-400 coaching-only" id="voiceAnalyticsPanel" style="display:none;">
                    <div class="panel-title"><i class="fa-solid fa-wave-square me-2"></i> Voice Analytics</div>
                    <div class="stat-row"><span>Speaking Duration</span><span id="vaDuration">0s</span></div>
                    <div class="stat-row"><span>Speed (WPM)</span><span id="vaWpm">0</span></div>
                    <div class="stat-row mb-0"><span>Filler Words (Um, Uh)</span><span id="vaFillers" class="text-danger">0</span></div>
                </div>

                <!-- Interview Notes -->
                <div class="panel animate-fade-up delay-400">
                    <div class="panel-title"><i class="fa-solid fa-clipboard me-2"></i> Session Notes</div>
                    <textarea id="sessionNotes" class="oinp" style="min-height:100px;font-size:.85rem;padding:10px" placeholder="Private notes, key reminders, etc...">{{ $sessionRecord->notes }}</textarea>
                </div>
            </div>
        </div>
        </div>

        @php
            $savedStateForUi = json_decode($sessionRecord->session_state ?? '', true);
            $hasSavedInterviewState = is_array($savedStateForUi) && !empty($savedStateForUi['has_started']);
        @endphp
        <div id="introContainer" class="text-center p-4 p-md-5 panel animate-fade-up" style="margin-top:40px;max-width:600px;margin-left:auto;margin-right:auto;border: 1px solid rgba(139,92,246,0.3);box-shadow: 0 20px 50px rgba(139,92,246,0.15);">
            @if($hasSavedInterviewState)
                <div class="recovery-pill"><i class="fa-solid fa-rotate-left"></i> Progress saved</div>
            @endif
            <div class="intro-ai-icon" aria-hidden="true">
                <i class="fa-solid fa-robot"></i>
            </div>
            <h4 style="color:var(--tx);font-weight:700">{{ $hasSavedInterviewState ? 'Interview Workspace Saved' : 'Interview Workspace Ready' }}</h4>
            <p style="color:var(--tx3);margin-bottom:30px">Your session is configured with {{ $questions->count() }} questions. Live readiness and STAR analysis will update as you respond.</p>
            <div class="intro-badge-grid" style="display:flex; justify-content:center; gap: 10px; flex-wrap: wrap; margin-bottom: 30px;">
                <span class="db-badge" style="background:rgba(59,130,246,.15);color:#60a5fa"><i class="fa-solid fa-microphone me-1"></i> {{ ucfirst($sessionRecord->response_mode) }} Mode</span>
                <span class="db-badge" style="background:rgba(52,211,153,.12);color:#34d399"><i class="fa-solid fa-bullseye me-1"></i> {{ ucfirst($sessionRecord->coach_focus_mode) }} Focus</span>
                <span class="db-badge" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="fa-solid fa-stopwatch me-1"></i> {{ $sessionRecord->time_limit ? $sessionRecord->time_limit . 'm / Q' : 'Self-paced' }}</span>
                <span class="db-badge" style="background:rgba(139,92,246,.12);color:#a78bfa"><i class="fa-solid fa-user-tie me-1"></i> {{ ucfirst(str_replace('_', ' ', $sessionRecord->interviewer_strictness ?? 'neutral')) }}</span>
            </div>
            <button class="btn px-4 py-3 w-100 btn-shine intro-start-btn" style="font-size:1.15rem;font-weight:700;border-radius:14px;background:var(--dash-primary, #60a5fa);color:white;border:none;box-shadow:0 8px 25px rgba(96,165,250,0.4);transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(96,165,250,0.6)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 8px 25px rgba(96,165,250,0.4)'" onclick="startInterviewSession()">{{ $hasSavedInterviewState ? 'Resume Interview' : 'Begin Interview' }} <i class="fa-solid fa-play ms-2"></i></button>
        </div>

        <form id="finishForm" action="{{ route('interview.finish') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="session_id" value="{{ session('active_interview_id') }}">
            <input type="hidden" name="duration_seconds" id="formDuration">
            <input type="hidden" name="notes" id="formNotes">
        </form>

        <script>
            const questions = {!! json_encode($questions) !!};
            const totalQuestions = {{ $num }};
            const responseMode = "{{ $sessionRecord->response_mode }}";
            const perQuestionLimitSeconds = {{ (int) (($sessionRecord->time_limit ?? 0) * 60) }};
            const assistanceLevel = @json($sessionRecord->ai_assistance_level ?? 'standard');
            const liveFeedbackMode = @json($sessionRecord->live_feedback_mode ?? 'coaching');
            const cameraCoachingEnabled = @json((bool) data_get($sessionRecord->accommodation_profile, 'camera_coaching', false));
            const savedSessionState = @json($savedStateForUi ?? []);
            let currentQIdx = Number(savedSessionState.currentQIdx ?? {{ (int) ($sessionRecord->current_question_index ?? 0) }}) || 0;
            currentQIdx = Math.max(0, Math.min(currentQIdx, Math.max(0, questions.length - 1)));
            let timerSeconds = Number(savedSessionState.timerSeconds ?? {{ (int) ($sessionRecord->duration_seconds ?? 0) }}) || 0;
            let timerInterval;
            let questionTimerInterval = null;
            let questionStartedAt = null;
            let questionElapsedSeconds = 0;
            let lastTimelineCaptureAt = 0;
            let chatHistory = Array.isArray(savedSessionState.chatHistory) ? savedSessionState.chatHistory : [];
            let stateSaveDebounce = null;
            
            // Answers state
            function defaultAnswerState() {
                return {
                text: '',
                is_skipped: false,
                timed_out: false,
                elapsed_seconds: 0,
                wpm: 0,
                voice_duration: 0,
                filler_words: 0,
                pause_count: 0,
                confidence_score: 0,
                self_reported_confidence: 50,
                eye_contact_score: 0,
                    posture_score: 0,
                    transcript_timeline: []
                };
            }

            let answersData = Array(questions.length).fill().map(() => defaultAnswerState());
            if (Array.isArray(savedSessionState.answersData)) {
                savedSessionState.answersData.forEach((savedAnswer, idx) => {
                    if (idx < answersData.length && savedAnswer && typeof savedAnswer === 'object') {
                        answersData[idx] = Object.assign(defaultAnswerState(), savedAnswer);
                    }
                });
            }

            // Voice state and optional, non-scoring camera-framing state
            let recognition = null;
            let recognitionActive = false;
            let shouldAutoRestartRecognition = false;
            let isRecording = false;
            let isRecordingPaused = false;
            let recTimerSeconds = 0;
            let recTimerInterval;
            let preRecordingText = '';
            let committedSpeechTranscript = '';
            let liveSpeechInterim = '';
            let lastCommittedSpeech = '';
            let lastCommittedAt = 0;

            const BrowserSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const speechLocale = document.documentElement.dataset.speechLocale || navigator.language || 'en-US';
            const speechLanguage = speechLocale.split('-')[0];
            const duplicateSafeWordSet = new Set([
                'i', "i'm", 'the', 'a', 'an', 'and', 'to', 'of', 'for', 'in', 'on', 'it', 'is', 'was',
                'were', 'am', 'are', 'my', 'we', 'you', 'that', 'this', 'with', 'um', 'uh', 'like'
            ]);

            function cleanTranscriptText(value) {
                return String(value || '').replace(/\s+/g, ' ').trim();
            }

            function normalizeTranscriptForMatch(value) {
                return cleanTranscriptText(value)
                    .toLocaleLowerCase(speechLocale)
                    .replace(/[^\p{L}\p{N}'\u2019\s]/gu, '')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            function wordsForTranscript(value) {
                return cleanTranscriptText(value).split(/\s+/).filter(Boolean);
            }

            function appendWithoutOverlap(existing, addition) {
                const existingClean = cleanTranscriptText(existing);
                const additionClean = cleanTranscriptText(addition);
                if (!existingClean) return additionClean;
                if (!additionClean) return existingClean;

                const existingWords = wordsForTranscript(existingClean);
                const additionWords = wordsForTranscript(additionClean);
                const existingNormalized = existingWords.map(normalizeTranscriptForMatch);
                const additionNormalized = additionWords.map(normalizeTranscriptForMatch);
                const maxOverlap = Math.min(existingNormalized.length, additionNormalized.length, 24);
                let overlap = 0;

                for (let size = maxOverlap; size > 0; size--) {
                    const existingTail = existingNormalized.slice(existingNormalized.length - size).join(' ');
                    const additionHead = additionNormalized.slice(0, size).join(' ');
                    if (existingTail && existingTail === additionHead) {
                        overlap = size;
                        break;
                    }
                }

                const remainder = additionWords.slice(overlap).join(' ');
                return cleanTranscriptText(existingClean + (remainder ? ' ' + remainder : ''));
            }

            function shouldCollapseDuplicateWindow(size, normalizedPhrase) {
                if (!normalizedPhrase) return false;
                if (size >= 2) return true;
                return normalizedPhrase.length > 2 || duplicateSafeWordSet.has(normalizedPhrase);
            }

            function collapseRepeatedSpeech(text) {
                const words = wordsForTranscript(text);
                if (words.length < 2) return cleanTranscriptText(text);

                let index = 0;
                while (index < words.length) {
                    let collapsed = false;
                    const maxWindow = Math.min(12, Math.floor((words.length - index) / 2));

                    for (let size = maxWindow; size >= 1; size--) {
                        const first = words.slice(index, index + size).map(normalizeTranscriptForMatch).join(' ');
                        const second = words.slice(index + size, index + (size * 2)).map(normalizeTranscriptForMatch).join(' ');

                        if (first && first === second && shouldCollapseDuplicateWindow(size, first)) {
                            words.splice(index + size, size);
                            index = Math.max(0, index - size);
                            collapsed = true;
                            break;
                        }
                    }

                    if (!collapsed) index++;
                }

                return cleanTranscriptText(words.join(' '));
            }

            function mergeTranscriptParts(...parts) {
                let merged = '';
                parts.forEach(part => {
                    const clean = cleanTranscriptText(part);
                    if (clean) merged = appendWithoutOverlap(merged, clean);
                });
                return collapseRepeatedSpeech(merged);
            }

            function bestSpeechAlternative(result) {
                let best = result[0] || null;
                for (let i = 1; i < result.length; i++) {
                    if ((result[i].confidence || 0) > (best.confidence || 0)) {
                        best = result[i];
                    }
                }
                return best ? best.transcript : '';
            }

            function resetSpeechRecognitionBufferFromTextarea() {
                const ta = document.getElementById('answerTextarea');
                preRecordingText = ta ? cleanTranscriptText(ta.value) : '';
                committedSpeechTranscript = '';
                liveSpeechInterim = '';
                lastCommittedSpeech = '';
                lastCommittedAt = 0;
            }

            function commitSpeechSegment(segment) {
                const cleanSegment = collapseRepeatedSpeech(cleanTranscriptText(segment));
                if (!cleanSegment) return;

                const normalized = normalizeTranscriptForMatch(cleanSegment);
                const now = Date.now();
                if (normalized && normalized === lastCommittedSpeech && (now - lastCommittedAt) < 5000) {
                    return;
                }

                committedSpeechTranscript = collapseRepeatedSpeech(appendWithoutOverlap(committedSpeechTranscript, cleanSegment));
                lastCommittedSpeech = normalized;
                lastCommittedAt = now;
            }

            function renderSpeechTranscript() {
                const ta = document.getElementById('answerTextarea');
                if (!ta) return;

                const recognizedTranscript = mergeTranscriptParts(committedSpeechTranscript, liveSpeechInterim);
                ta.value = mergeTranscriptParts(preRecordingText, recognizedTranscript);
                triggerAnalysis();
            }

            function startSpeechRecognitionEngine() {
                if (!recognition || recognitionActive || !isRecording || !shouldAutoRestartRecognition) return;

                try {
                    recognition.start();
                    recognitionActive = true;
                } catch (error) {
                    if (!error || error.name !== 'InvalidStateError') {
                        console.error('Speech recognition failed to start:', error);
                    }
                }
            }

            function finalizeInterimTranscript() {
                if (!liveSpeechInterim) return;
                commitSpeechSegment(liveSpeechInterim);
                liveSpeechInterim = '';
                renderSpeechTranscript();
            }

            let lastSpeechEnd = 0;
            if (BrowserSpeechRecognition) {
                recognition = new BrowserSpeechRecognition();
                recognition.continuous = true;
                recognition.interimResults = true;
                recognition.lang = speechLocale;
                recognition.maxAlternatives = 3;

                recognition.onstart = function() {
                    recognitionActive = true;
                };
                
                recognition.onsoundstart = function() {
                    if (lastSpeechEnd > 0) {
                        const gap = (Date.now() - lastSpeechEnd) / 1000;
                        if (gap > 3) {
                            answersData[currentQIdx].pause_count++;
                        }
                    }
                };
                
                recognition.onsoundend = function() {
                    lastSpeechEnd = Date.now();
                };

                recognition.onresult = function(event) {
                    const interimParts = [];

                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        const transcript = bestSpeechAlternative(event.results[i]);
                        if (!transcript) continue;

                        if (event.results[i].isFinal) {
                            commitSpeechSegment(transcript);
                        } else {
                            interimParts.push(transcript);
                        }
                    }

                    liveSpeechInterim = cleanTranscriptText(interimParts.join(' '));
                    renderSpeechTranscript();
                };

                recognition.onerror = function(event) {
                    console.warn('Speech recognition error:', event.error || event);
                    if (['not-allowed', 'service-not-allowed', 'audio-capture'].includes(event.error)) {
                        shouldAutoRestartRecognition = false;
                    }
                };

                recognition.onend = function() {
                    recognitionActive = false;
                    if (shouldAutoRestartRecognition && isRecording) {
                        setTimeout(startSpeechRecognitionEngine, 250);
                    }
                };
            }

            function initCamera() {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    navigator.mediaDevices.getUserMedia({ video: true })
                        .then(function(stream) {
                            let video = document.getElementById('userCamera');
                            if (video) {
                                video.srcObject = stream;
                                video.play();
                            }
                            let mobileVideo = document.getElementById('userCameraMobile');
                            if (mobileVideo) {
                                mobileVideo.srcObject = stream;
                                mobileVideo.play();
                            }
                        })
                        .catch(function(err) {
                            console.error("Error accessing camera: ", err);
                        });
                } else {
                    console.error("getUserMedia not supported");
                }
            }
            
            async function trackBodyLanguage() {
                if (!cameraCoachingEnabled) return;
                const video = document.getElementById('userCamera');
                if (!video || !video.srcObject) return;
                
                try {
                    const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                    
                    if (detection) {
                        document.getElementById('stEyeContact').innerHTML = '<i class="fa-solid fa-check me-1"></i>Visible';
                        document.getElementById('stEyeContact').className = 'text-success';
                        document.getElementById('stPosture').textContent = 'Available · not scored';
                        document.getElementById('stPosture').className = 'text-secondary';
                    } else {
                        document.getElementById('stEyeContact').textContent = 'Outside frame / unavailable';
                        document.getElementById('stEyeContact').className = 'text-warning';
                        document.getElementById('stPosture').textContent = 'Excluded from scoring';
                        document.getElementById('stPosture').className = 'text-secondary';
                    }
                } catch(e) {
                    console.error("Tracking error", e);
                }
            }

            let visualizerInterval = null;
            let currentAmplitude = 0.2;
            let preferredVoice = null;
            let autoStartAfterQuestionTimer = null;
            let questionSpeechToken = 0;

            function isVoiceTranscriptionMode() {
                return responseMode === 'voice' || responseMode === 'hybrid' || responseMode === 'voice_and_text';
            }

            function scheduleAutoTranscriptionStart(token) {
                if (token !== questionSpeechToken) return;
                clearTimeout(autoStartAfterQuestionTimer);
                if (!isVoiceTranscriptionMode()) return;

                autoStartAfterQuestionTimer = setTimeout(() => {
                    if (token !== questionSpeechToken || isRecording) return;
                    startRecording({ silent: true });
                }, 450);
            }

            // Initialize preferred voice
            function loadVoices() {
                let voices = window.speechSynthesis.getVoices();
                if (voices.length > 0) {
                    preferredVoice = voices.find(v => v.lang === speechLocale && (v.name.includes('Google') || v.name.includes('Premium') || v.name.includes('Natural') || v.name.includes('Siri'))) || voices.find(v => v.lang === speechLocale) || voices.find(v => v.lang.startsWith(speechLanguage)) || voices.find(v => v.lang.startsWith('en')) || voices[0];
                }
            }
            if ('speechSynthesis' in window) {
                window.speechSynthesis.onvoiceschanged = loadVoices;
                loadVoices();
            }

            function speakQuestion(text, options = {}) {
                questionSpeechToken++;
                const token = questionSpeechToken;
                const startTimerAfterSpeech = options.startTimerAfterSpeech === true;

                if (isRecording) {
                    pauseRecording();
                }

                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                    let utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = speechLocale;
                    if (preferredVoice) utterance.voice = preferredVoice;
                    utterance.rate = 0.95;
                    utterance.pitch = 1.0;

                    let words = text.split(' ');
                    let currentWordIdx = 0;
                    let captionInterval = null;
                    let boundaryFired = false;

                    utterance.onboundary = function(e) {
                        if(e.name === 'word') {
                            boundaryFired = true;
                            if (captionInterval) clearInterval(captionInterval);
                            
                            currentAmplitude = 1.0;
                            let end = text.indexOf(' ', e.charIndex);
                            if (end === -1) end = text.length;
                            document.getElementById('aiQuestionText').innerText = text.substring(0, end);
                        }
                    };

                    utterance.onstart = function() {
                        document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'block');
                        document.getElementById('aiAvatarHead').style.borderColor = '#34d399';
                        document.getElementById('aiQuestionText').innerText = '';
                        
                        // Hybrid fallback: uses setInterval ONLY if the perfect 'onboundary' event fails to fire
                        captionInterval = setInterval(() => {
                            if (!boundaryFired) {
                                if (currentWordIdx < words.length) {
                                    currentWordIdx++;
                                    document.getElementById('aiQuestionText').innerText = words.slice(0, currentWordIdx).join(' ');
                                    currentAmplitude = 1.0;
                                } else {
                                    clearInterval(captionInterval);
                                }
                            }
                        }, 350); // Fallback estimate
                        
                        // Start dynamic JS visualizer
                        const bars = document.querySelectorAll('.spectrum-bar');
                        visualizerInterval = setInterval(() => {
                            currentAmplitude = Math.max(0.15, currentAmplitude - 0.1); // Decay slowly between words
                            bars.forEach(bar => {
                                // Calculate random jitter scaled by current word amplitude
                                let h = 6 + (Math.random() * 80 * currentAmplitude);
                                bar.style.height = h + 'px';
                            });
                        }, 50); // 20 FPS jitter
                    };
                    
                    utterance.onend = function() {
                        document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'none');
                        document.getElementById('aiAvatarHead').style.borderColor = '#8b5cf6';
                        if(visualizerInterval) clearInterval(visualizerInterval);
                        if(captionInterval) clearInterval(captionInterval);
                        document.getElementById('aiQuestionText').innerText = text;
                        if (startTimerAfterSpeech) startQuestionTimer();
                        scheduleAutoTranscriptionStart(token);
                    };

                    window.speechSynthesis.speak(utterance);
                } else {
                    document.getElementById('aiQuestionText').innerText = text;
                    if (startTimerAfterSpeech) startQuestionTimer();
                    scheduleAutoTranscriptionStart(token);
                }
            }

            function formatSeconds(total) {
                const safeTotal = Math.max(0, Math.round(total || 0));
                const m = Math.floor(safeTotal / 60).toString().padStart(2, '0');
                const s = (safeTotal % 60).toString().padStart(2, '0');
                return m + ':' + s;
            }

            function getQuestionElapsedSeconds() {
                if (!questionStartedAt) return questionElapsedSeconds || 0;
                return Math.max(0, Math.round((Date.now() - questionStartedAt) / 1000));
            }

            function updateQuestionTimerDisplay() {
                const chip = document.getElementById('questionTimerChip');
                const timer = document.getElementById('perQuestionTimer');
                if (!chip || !timer) return;

                const elapsed = getQuestionElapsedSeconds();
                questionElapsedSeconds = elapsed;

                if (perQuestionLimitSeconds <= 0) {
                    timer.innerText = 'Self-paced';
                    chip.className = 'session-chip';
                    return;
                }

                const remaining = perQuestionLimitSeconds - elapsed;
                timer.innerText = formatSeconds(remaining);
                chip.className = remaining <= 15 ? 'session-chip danger' : (remaining <= 30 ? 'session-chip warning' : 'session-chip');

                if (remaining <= 0) {
                    handleQuestionTimeout();
                }
            }

            function startQuestionTimer() {
                clearInterval(questionTimerInterval);
                questionElapsedSeconds = answersData[currentQIdx]?.elapsed_seconds || 0;
                questionStartedAt = Date.now() - (questionElapsedSeconds * 1000);
                captureTranscriptTimeline('question_loaded', true);
                updateQuestionTimerDisplay();
                questionTimerInterval = setInterval(() => {
                    updateQuestionTimerDisplay();
                    if (getQuestionElapsedSeconds() - lastTimelineCaptureAt >= 5) {
                        captureTranscriptTimeline('progress');
                    }
                }, 1000);
            }

            function stopQuestionTimer() {
                clearInterval(questionTimerInterval);
                questionTimerInterval = null;
                if (answersData[currentQIdx]) {
                    answersData[currentQIdx].elapsed_seconds = getQuestionElapsedSeconds();
                }
            }

            function captureTranscriptTimeline(eventName = 'progress', force = false) {
                if (!answersData[currentQIdx]) return;
                const elapsed = getQuestionElapsedSeconds();
                if (!force && elapsed === lastTimelineCaptureAt) return;
                lastTimelineCaptureAt = elapsed;
                const text = document.getElementById('answerTextarea')?.value || '';
                answersData[currentQIdx].transcript_timeline = answersData[currentQIdx].transcript_timeline || [];
                answersData[currentQIdx].transcript_timeline.push({
                    at: elapsed,
                    event: eventName,
                    words: text.trim().split(/\s+/).filter(Boolean).length,
                    chars: text.length
                });
            }

            function handleQuestionTimeout() {
                clearInterval(questionTimerInterval);
                if (document.querySelector('.next-btn-class:disabled')) return;
                submitAnswer({ timedOut: true });
            }

            function scheduleStateSave() {
                clearTimeout(stateSaveDebounce);
                stateSaveDebounce = setTimeout(autoSaveState, 1200);
            }

            function restoreChatHistory() {
                const chatContainer = document.getElementById('chatTranscriptContainer');
                chatContainer.innerHTML = '';
                if (!Array.isArray(chatHistory) || chatHistory.length === 0) return false;
                chatHistory.forEach(item => appendChatMessage(item.role, item.text, false));
                return true;
            }

            function startInterviewSession() {
                document.getElementById('introContainer').style.display = 'none';
                document.getElementById('workspaceWrapper').style.display = 'block';
                document.getElementById('workspaceWrapper').classList.toggle('real-interview-mode', liveFeedbackMode === 'real_interview');
                document.getElementById('interviewControls').style.opacity = '1';
                document.getElementById('interviewControls').style.pointerEvents = 'auto';
                
                if (cameraCoachingEnabled) initCamera();
                
                if(isVoiceTranscriptionMode()) {
                    document.getElementById('voiceControls').style.display = 'flex';
                    if (liveFeedbackMode !== 'real_interview') {
                        document.getElementById('voiceAnalyticsPanel').style.display = 'block';
                    }
                }

                timerInterval = setInterval(() => {
                    timerSeconds++;
                    const m = Math.floor(timerSeconds / 60).toString().padStart(2, '0');
                    const s = (timerSeconds % 60).toString().padStart(2, '0');
                    const interviewTimer = document.getElementById('interviewTimer');
                    if (interviewTimer) interviewTimer.innerText = m + ':' + s;
                    
                    if(timerSeconds % 30 === 0) autoSaveState(); // auto save every 30s
                }, 1000);

                // Hold-to-Talk Gamified Logic
                const holdBtn = document.getElementById('holdToTalkBtn');
                if (holdBtn) {
                    const startHold = (e) => { e.preventDefault(); holdBtn.style.transform = 'scale(0.95)'; holdBtn.style.background = '#991b1b'; startRecording(); };
                    const endHold = (e) => { e.preventDefault(); holdBtn.style.transform = 'scale(1)'; holdBtn.style.background = ''; stopRecording(); };
                    
                    holdBtn.addEventListener('mousedown', startHold);
                    holdBtn.addEventListener('mouseup', endHold);
                    holdBtn.addEventListener('mouseleave', (e) => { if(isRecording) endHold(e); });
                    
                    holdBtn.addEventListener('touchstart', startHold, {passive: false});
                    holdBtn.addEventListener('touchend', endHold, {passive: false});
                    holdBtn.addEventListener('touchcancel', (e) => { if(isRecording) endHold(e); });
                }

                const restoredChat = restoreChatHistory();
                loadQuestion(currentQIdx, { append: !restoredChat });
                
                document.getElementById('answerTextarea').addEventListener('input', triggerAnalysis);
                document.getElementById('sessionNotes').addEventListener('input', scheduleStateSave);
                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'hidden') autoSaveState();
                });
            }

            function loadQuestion(idx, options = {}) {
                currentQIdx = idx;
                const q = questions[idx];
                if (!q) return;
                
                document.getElementById('aiQuestionText').innerText = '...';
                document.getElementById('qCounter').innerText = (idx + 1) + '/' + totalQuestions;

                // Append AI question to chat log if it's the first time seeing it
                if (options.append !== false) {
                    appendChatMessage('interviewer', q.question_text);
                }

                // Restore answer state if navigated back (though disabled in chat mode)
                document.getElementById('answerTextarea').value = answersData[idx] ? answersData[idx].text : '';
                document.getElementById('selfConfidenceRange').value = answersData[idx]?.self_reported_confidence ?? 50;
                document.getElementById('selfConfidenceValue').textContent = (answersData[idx]?.self_reported_confidence ?? 50) + '%';
                resetSpeechRecognitionBufferFromTextarea();
                lastTimelineCaptureAt = 0;
                
                speakQuestion(q.question_text, { startTimerAfterSpeech: true });
                
                triggerAnalysis();
                scheduleStateSave();
            }

            function repeatQuestion() {
                if(questions && questions[currentQIdx]) {
                    speakQuestion(questions[currentQIdx].question_text);
                }
            }



            const analysisStopWords = new Set([
                'about', 'after', 'again', 'also', 'and', 'are', 'because', 'been', 'before', 'being', 'but', 'can',
                'could', 'did', 'does', 'for', 'from', 'had', 'has', 'have', 'how', 'into', 'interview', 'job',
                'more', 'most', 'that', 'the', 'their', 'then', 'there', 'this', 'those', 'through', 'tell', 'than',
                'was', 'were', 'what', 'when', 'where', 'which', 'while', 'with', 'would', 'you', 'your'
            ]);

            const situationMarkers = [
                'when', 'while', 'during', 'in my previous role', 'at my last job', 'on a project', 'our team',
                'a client', 'a customer', 'deadline', 'challenge', 'problem', 'situation', 'scenario'
            ];
            const taskMarkers = [
                'my role', 'responsible for', 'i was responsible', 'i needed to', 'i had to', 'my goal',
                'the goal', 'objective', 'task', 'asked to', 'expected to', 'requirement'
            ];
            const actionMarkers = [
                'i led', 'i built', 'i created', 'i implemented', 'i designed', 'i analyzed', 'i coordinated',
                'i resolved', 'i improved', 'i developed', 'i organized', 'i prioritized', 'i communicated',
                'i worked with', 'i decided', 'i proposed', 'i tested', 'i delivered', 'we built', 'we implemented'
            ];
            const resultMarkers = [
                'result', 'outcome', 'impact', 'increased', 'decreased', 'reduced', 'improved', 'saved',
                'delivered', 'launched', 'resolved', 'completed', 'achieved', 'learned', 'led to', 'as a result'
            ];
            const fillerPattern = /\b(um|uh|like|you know|basically|i mean|sort of|kind of|literally|actually)\b/gi;
            const unprofessionalPattern = /\b(whatever|stuff|things|idk|lol|yeah|nah|kinda|sorta)\b/gi;

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function clampScore(value) {
                return Math.max(0, Math.min(100, Math.round(value)));
            }

            function normalizeText(text) {
                return ` ${String(text || '').toLowerCase().replace(/\s+/g, ' ').trim()} `;
            }

            function escapeRegExp(value) {
                return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            function includesAny(text, terms) {
                return terms.some(term => new RegExp(`\\b${escapeRegExp(term)}\\b`, 'i').test(text));
            }

            function meaningfulWords(text) {
                return String(text || '')
                    .toLowerCase()
                    .replace(/[^a-z0-9\s'-]/g, ' ')
                    .split(/\s+/)
                    .map(word => word.replace(/^'+|'+$/g, ''))
                    .filter(word => word.length > 2 && !analysisStopWords.has(word));
            }

            function isBehavioralQuestion(questionText) {
                return /\b(tell me about a time|describe a time|give me an example|how did you handle|conflict|challenge|failure|mistake|leadership|teamwork|difficult|situation)\b/i.test(questionText || '');
            }

            function detectStarSignals(text) {
                const normalized = normalizeText(text);
                const wordCount = meaningfulWords(text).length;
                const metricPattern = /(\b\d+(\.\d+)?\s?%|\$\s?\d+|\b\d+\s?(users|customers|clients|people|hours|days|weeks|months|tickets|cases|calls|projects|minutes|seconds|revenue|sales)\b|\b(by|from|to)\s+\d+)/i;

                const hasS = wordCount >= 10 && includesAny(normalized, situationMarkers);
                const hasT = includesAny(normalized, taskMarkers);
                const hasA = includesAny(normalized, actionMarkers) || /\b(i|we)\s+(led|built|created|implemented|designed|analyzed|coordinated|resolved|improved|developed|organized|prioritized|communicated|tested|delivered)\b/i.test(normalized);
                const hasR = includesAny(normalized, resultMarkers) || metricPattern.test(text);

                return {
                    hasS,
                    hasT,
                    hasA,
                    hasR,
                    componentCount: [hasS, hasT, hasA, hasR].filter(Boolean).length,
                    hasMetric: metricPattern.test(text)
                };
            }

            function calculateRelevanceScore(answerText, questionText, wordCount, starSignals) {
                if (wordCount === 0) return 0;
                if (wordCount < 8) return clampScore(wordCount * 5);

                const answerWords = new Set(meaningfulWords(answerText));
                const questionWords = [...new Set(meaningfulWords(questionText))].slice(0, 10);
                let matched = 0;

                questionWords.forEach(qWord => {
                    const hasMatch = [...answerWords].some(aWord => aWord === qWord || aWord.startsWith(qWord) || qWord.startsWith(aWord));
                    if (hasMatch) matched++;
                });

                const ratio = questionWords.length > 0 ? matched / questionWords.length : 0.45;
                let score = 35 + (ratio * 50);

                const behavioral = isBehavioralQuestion(questionText);
                if (behavioral && starSignals.componentCount >= 3) score = Math.max(score, 72);
                else if (behavioral && starSignals.componentCount >= 2) score = Math.max(score, 62);
                if (wordCount < 25) score -= 12;
                if (starSignals.hasA && starSignals.hasR) score += 5;

                return clampScore(score);
            }

            function calculateLiveScores(answerText, questionText, wordCount, fillerCount, starSignals) {
                const sentences = answerText.split(/[.!?]+/).map(s => s.trim()).filter(Boolean);
                const sentenceCount = Math.max(1, sentences.length);
                const hasFirstPersonOwnership = /\b(i|my|me)\b/i.test(answerText);
                const hasEndPunctuation = /[.!?]$/.test(answerText.trim());
                const hasRepeatedWord = /\b([a-z]+)\s+\1\b/i.test(answerText);
                const longSentencePenalty = sentences.some(sentence => sentence.split(/\s+/).length > 40) ? 8 : 0;
                const casualMatches = answerText.match(unprofessionalPattern);
                const casualCount = casualMatches ? casualMatches.length : 0;

                let clarity = 28 + Math.min(28, wordCount * 1.1) + (starSignals.componentCount * 8) + Math.min(8, sentenceCount * 2);
                clarity -= fillerCount * 3;
                clarity -= longSentencePenalty;
                if (wordCount > 220) clarity -= 10;
                if (wordCount < 15) clarity = Math.min(clarity, 45);

                const relevance = calculateRelevanceScore(answerText, questionText, wordCount, starSignals);

                let grammar = 55 + Math.min(20, wordCount * 0.5) + (hasEndPunctuation ? 8 : 0);
                grammar -= fillerCount * 3;
                grammar -= hasRepeatedWord ? 8 : 0;
                grammar -= longSentencePenalty;
                if (wordCount < 15) grammar = Math.min(grammar, 50);

                let professionalism = 58 + (hasFirstPersonOwnership ? 10 : 0) + (starSignals.hasA ? 8 : 0) + (starSignals.hasR ? 8 : 0);
                professionalism -= fillerCount * 3;
                professionalism -= casualCount * 10;
                if (wordCount < 15) professionalism = Math.min(professionalism, 50);

                const starBonus = isBehavioralQuestion(questionText) ? starSignals.componentCount * 3 : starSignals.hasR ? 5 : 0;
                const readiness = (clarity * 0.25) + (relevance * 0.3) + (grammar * 0.2) + (professionalism * 0.25) + starBonus;

                return {
                    clarity: clampScore(clarity),
                    relevance: clampScore(relevance),
                    grammar: clampScore(grammar),
                    professionalism: clampScore(professionalism),
                    readiness: clampScore(wordCount === 0 ? 0 : readiness)
                };
            }

            function questionFocus(questionText) {
                const keywords = meaningfulWords(questionText).slice(0, 5);
                return keywords.length > 0 ? keywords.join(' / ') : 'the question asked';
            }

            function biggestSuggestion(answerText, questionText, wordCount, fillerCount, scores, starSignals) {
                if (wordCount === 0) {
                    return 'Give one specific example, your role, the action you took, and the result.';
                }
                if (wordCount < 25) {
                    return 'Expand this into a complete interview answer: context, your responsibility, specific action, and result.';
                }
                if (scores.relevance < 55) {
                    return `Tie the answer more directly to ${questionFocus(questionText)} with a relevant example.`;
                }
                if (isBehavioralQuestion(questionText) && !starSignals.hasS) {
                    return 'Open with the situation so the interviewer understands the context before your action.';
                }
                if (!starSignals.hasT) {
                    return 'State your exact responsibility or goal so your ownership is clear.';
                }
                if (!starSignals.hasA) {
                    return 'Describe the specific actions you personally took, not only what the team did.';
                }
                if (!starSignals.hasR) {
                    return 'Close with the result or impact, ideally with a number, outcome, or lesson learned.';
                }
                if (!starSignals.hasMetric && wordCount >= 40) {
                    return 'Add one measurable detail, such as time saved, quality improved, revenue, volume, or customer impact.';
                }
                if (fillerCount >= 3) {
                    return 'Reduce filler words and make the delivery more direct and confident.';
                }
                if (wordCount > 220) {
                    return 'Tighten the answer to the strongest 60-90 seconds: situation, decision, action, result.';
                }

                return 'Strong direction. Make it sharper by naming the key decision, tradeoff, and measurable impact.';
            }

            function triggerAnalysis() {
                const text = document.getElementById('answerTextarea').value;
                const currentQuestion = questions[currentQIdx] ? questions[currentQIdx].question_text : '';
                const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
                const charCount = text.length;
                
                document.getElementById('wordCount').innerText = wordCount + ' words';
                document.getElementById('charCount').innerText = charCount + ' characters';

                const starSignals = detectStarSignals(text);
                
                updateStarIcon('starS', starSignals.hasS);
                updateStarIcon('starT', starSignals.hasT);
                updateStarIcon('starA', starSignals.hasA);
                updateStarIcon('starR', starSignals.hasR);

                const matches = text.match(fillerPattern);
                const fillers = matches ? matches.length : 0;
                const scores = calculateLiveScores(text, currentQuestion, wordCount, fillers, starSignals);
                const tip = biggestSuggestion(text, currentQuestion, wordCount, fillers, scores, starSignals);

                document.getElementById('coachingTip').innerHTML = `<i class="fa-solid fa-lightbulb me-1"></i> <strong>Biggest Suggestion:</strong> ${escapeHtml(tip)}`;
                document.getElementById('overallReadiness').innerText = scores.readiness + '%';
                document.getElementById('metClarity').innerText = scores.clarity + '%';
                document.getElementById('metRelevance').innerText = scores.relevance + '%';
                document.getElementById('metGrammar').innerText = scores.grammar + '%';
                document.getElementById('metProf').innerText = scores.professionalism + '%';
                document.getElementById('vaFillers').innerText = fillers;
                answersData[currentQIdx].text = text;
                answersData[currentQIdx].filler_words = fillers;
                answersData[currentQIdx].elapsed_seconds = getQuestionElapsedSeconds();
                if (getQuestionElapsedSeconds() - lastTimelineCaptureAt >= 5) {
                    captureTranscriptTimeline('input');
                }
                scheduleStateSave();
            }

            function updateStarIcon(id, status) {
                const el = document.getElementById(id);
                if(status) {
                    el.className = 'fa-solid fa-circle-check text-success';
                } else {
                    el.className = 'fa-solid fa-circle-xmark text-danger';
                }
            }

            function startRecording(options = {}) {
                const silent = options && options.silent === true;
                if(!recognition) {
                    if(!silent) alert("Speech recognition not supported in this browser.");
                    return;
                }
                if (isRecording) return;

                if (!isRecordingPaused) {
                    resetSpeechRecognitionBufferFromTextarea();
                }
                lastSpeechEnd = 0;
                shouldAutoRestartRecognition = true;
                isRecording = true;
                isRecordingPaused = false;
                startSpeechRecognitionEngine();
                document.getElementById('micPauseBtn').style.display = 'inline-flex';
                document.getElementById('micPauseBtn').innerHTML = '<i class="fa-solid fa-pause"></i>';
                document.getElementById('micPauseBtn').setAttribute('aria-label', 'Pause recording');
                document.getElementById('micPauseBtn').setAttribute('title', 'Pause recording');
                document.getElementById('micStopBtn').style.display = 'inline-flex';
                document.getElementById('recordingTimer').style.display = 'block';
                clearInterval(recTimerInterval);
                
                recTimerInterval = setInterval(() => {
                    recTimerSeconds++;
                    const m = Math.floor(recTimerSeconds / 60).toString().padStart(2, '0');
                    const s = (recTimerSeconds % 60).toString().padStart(2, '0');
                    document.getElementById('recordingTimer').innerText = m + ':' + s;
                    document.getElementById('vaDuration').innerText = recTimerSeconds + 's';
                    answersData[currentQIdx].voice_duration = recTimerSeconds;
                    
                    const wordCount = document.getElementById('answerTextarea').value.trim().split(/\s+/).filter(w=>w.length>0).length;
                    
                    // Deduct 3 seconds per pause for a highly accurate WPM of ACTIVE speaking time
                    let activeSeconds = recTimerSeconds - (answersData[currentQIdx].pause_count * 3);
                    if (activeSeconds < 1) activeSeconds = 1;
                    const wpm = Math.round((wordCount / activeSeconds) * 60);
                    
                    document.getElementById('vaWpm').innerText = wpm;
                    answersData[currentQIdx].wpm = wpm;

                    // Optional camera-framing guidance is descriptive and never affects readiness scoring.
                    if (cameraCoachingEnabled && recTimerSeconds % 2 === 0) {
                        trackBodyLanguage();
                    }

                }, 1000);

                const scannerBox = document.getElementById('faceScannerBox');
                if (scannerBox) scannerBox.style.display = 'block';
            }

            function toggleRecordingPause() {
                if (isRecording) {
                    pauseRecording();
                    return;
                }

                startRecording({ silent: false });
            }

            function pauseRecording() {
                finalizeInterimTranscript();
                shouldAutoRestartRecognition = false;
                if(recognition) {
                    try {
                        recognition.stop();
                    } catch (error) {
                        console.error('Speech recognition failed to stop:', error);
                    }
                }
                isRecording = false;
                isRecordingPaused = true;
                clearInterval(recTimerInterval);
                document.getElementById('micPauseBtn').style.display = 'inline-flex';
                document.getElementById('micPauseBtn').innerHTML = '<i class="fa-solid fa-play"></i>';
                document.getElementById('micPauseBtn').setAttribute('aria-label', 'Resume recording');
                document.getElementById('micPauseBtn').setAttribute('title', 'Resume recording');
                document.getElementById('micStopBtn').style.display = 'inline-flex';
                document.getElementById('recordingTimer').style.display = 'block';
                document.getElementById('faceScannerBox').style.display = 'none';
            }

            function stopRecording() {
                pauseRecording();
                clearTimeout(autoStartAfterQuestionTimer);
                isRecordingPaused = false;
                recTimerSeconds = 0;
                document.getElementById('recordingTimer').innerText = '00:00';
                document.getElementById('micPauseBtn').innerHTML = '<i class="fa-solid fa-pause"></i>';
                document.getElementById('micPauseBtn').setAttribute('aria-label', 'Pause recording');
                document.getElementById('micPauseBtn').setAttribute('title', 'Pause recording');
                resetSpeechRecognitionBufferFromTextarea();
            }

            function saveCurrentAnswer(isSkipped = false, timedOut = false) {
                stopQuestionTimer();
                captureTranscriptTimeline(timedOut ? 'timed_out_submit' : 'submitted', true);
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('question_id', questions[currentQIdx].id);
                formData.append('answer_text', answersData[currentQIdx].text);
                formData.append('transcript_timeline', JSON.stringify(answersData[currentQIdx].transcript_timeline || []));
                formData.append('is_skipped', isSkipped);
                formData.append('timed_out', timedOut);
                formData.append('elapsed_seconds', answersData[currentQIdx].elapsed_seconds || getQuestionElapsedSeconds());
                formData.append('response_mode', responseMode);
                formData.append('wpm', answersData[currentQIdx].wpm);
                formData.append('voice_duration', answersData[currentQIdx].voice_duration);
                formData.append('filler_words_count', answersData[currentQIdx].filler_words);
                formData.append('pause_count', answersData[currentQIdx].pause_count);
                formData.append('confidence_score', answersData[currentQIdx].confidence_score);
                answersData[currentQIdx].self_reported_confidence = Number(document.getElementById('selfConfidenceRange')?.value ?? 50);
                formData.append('self_reported_confidence', answersData[currentQIdx].self_reported_confidence);
                formData.append('eye_contact_score', answersData[currentQIdx].eye_contact_score);
                formData.append('posture_score', answersData[currentQIdx].posture_score);
                formData.append('notes', document.getElementById('sessionNotes').value);

                return fetch('{{ route("interview.answer") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            }

            function autoSaveState() {
                if (answersData[currentQIdx]) {
                    answersData[currentQIdx].text = document.getElementById('answerTextarea').value;
                    answersData[currentQIdx].elapsed_seconds = getQuestionElapsedSeconds();
                }

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('notes', document.getElementById('sessionNotes').value);
                formData.append('duration_seconds', timerSeconds);
                formData.append('current_question_index', currentQIdx);
                formData.append('session_state', JSON.stringify({
                    has_started: true,
                    currentQIdx,
                    timerSeconds,
                    answersData,
                    chatHistory,
                    updated_at: new Date().toISOString()
                }));
                
                fetch('{{ url("interview/save-state") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(() => {
                    const ind = document.getElementById('autoSaveIndicator');
                    ind.style.display = 'inline';
                    setTimeout(() => ind.style.display = 'none', 2000);
                });
            }

            function appendChatMessage(role, text, record = true) {
                const chatContainer = document.getElementById('chatTranscriptContainer');
                const bubble = document.createElement('div');
                bubble.style.padding = '10px 14px';
                bubble.style.borderRadius = '16px';
                bubble.style.maxWidth = '85%';
                bubble.style.lineHeight = '1.4';
                bubble.style.fontSize = '0.85rem';
                
                if (role === 'interviewer') {
                    bubble.style.background = 'rgba(139,92,246,0.15)';
                    bubble.style.border = '1px solid rgba(139,92,246,0.3)';
                    bubble.style.alignSelf = 'flex-start';
                    bubble.innerHTML = '<strong><i class="fa-solid fa-robot me-1"></i> Interviewer</strong><br>' + escapeHtml(text);
                } else {
                    bubble.style.background = 'rgba(59,130,246,0.15)';
                    bubble.style.border = '1px solid rgba(59,130,246,0.3)';
                    bubble.style.alignSelf = 'flex-end';
                    bubble.innerHTML = '<strong><i class="fa-solid fa-user me-1"></i> You</strong><br>' + escapeHtml(text);
                }
                
                chatContainer.appendChild(bubble);
                chatContainer.scrollTop = chatContainer.scrollHeight;

                if (record) {
                    chatHistory.push({ role, text });
                    if (chatHistory.length > 80) chatHistory = chatHistory.slice(chatHistory.length - 80);
                    scheduleStateSave();
                }
            }

            function submitAnswer(options = {}) {
                if(isRecording) stopRecording();
                
                const timedOut = options.timedOut === true;
                let answerText = document.getElementById('answerTextarea').value.trim();
                const wasSkipped = options.skipped === true || (timedOut && !answerText);

                if(!answerText && !timedOut && !wasSkipped) return alert("Please provide an answer before submitting.");
                if(!answerText && timedOut) {
                    answerText = "[Time expired with no answer]";
                    document.getElementById('answerTextarea').value = answerText;
                }

                answersData[currentQIdx].text = answerText;
                answersData[currentQIdx].is_skipped = wasSkipped;
                answersData[currentQIdx].timed_out = timedOut;

                // Optimistically append user answer to chat
                appendChatMessage('user', answerText);

                if (currentQIdx >= totalQuestions - 1) {
                    document.querySelectorAll('.next-btn-class').forEach(el => el.disabled = true);
                    saveCurrentAnswer(wasSkipped, timedOut).then(() => {
                        finishInterview();
                    });
                    return;
                }

                stopQuestionTimer();
                captureTranscriptTimeline(timedOut ? 'timed_out_submit' : 'submitted', true);
                
                // Show thinking indicator
                const chatContainer = document.getElementById('chatTranscriptContainer');
                const thinkingBubble = document.createElement('div');
                thinkingBubble.id = 'thinkingBubble';
                thinkingBubble.style.padding = '12px 16px';
                thinkingBubble.style.borderRadius = '16px';
                thinkingBubble.style.maxWidth = '85%';
                thinkingBubble.style.alignSelf = 'flex-start';
                thinkingBubble.style.background = 'rgba(255,255,255,0.05)';
                thinkingBubble.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-muted me-2"></i> <em>Interviewer is preparing the next question...</em>';
                chatContainer.appendChild(thinkingBubble);
                chatContainer.scrollTop = chatContainer.scrollHeight;

                // Disable buttons
                document.querySelectorAll('.next-btn-class').forEach(el => el.disabled = true);
                
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('question_id', questions[currentQIdx].id);
                formData.append('answer_text', answerText);
                formData.append('transcript_timeline', JSON.stringify(answersData[currentQIdx].transcript_timeline || []));
                formData.append('is_skipped', wasSkipped);
                formData.append('timed_out', timedOut);
                formData.append('elapsed_seconds', answersData[currentQIdx].elapsed_seconds || getQuestionElapsedSeconds());
                formData.append('response_mode', responseMode);
                formData.append('wpm', answersData[currentQIdx].wpm);
                formData.append('voice_duration', answersData[currentQIdx].voice_duration);
                formData.append('filler_words_count', answersData[currentQIdx].filler_words);
                formData.append('pause_count', answersData[currentQIdx].pause_count);
                formData.append('confidence_score', answersData[currentQIdx].confidence_score);
                answersData[currentQIdx].self_reported_confidence = Number(document.getElementById('selfConfidenceRange')?.value ?? 50);
                formData.append('self_reported_confidence', answersData[currentQIdx].self_reported_confidence);
                formData.append('eye_contact_score', answersData[currentQIdx].eye_contact_score);
                formData.append('posture_score', answersData[currentQIdx].posture_score);

                formData.append('is_final_question', (currentQIdx === totalQuestions - 2));

                fetch('{{ route("interview.chatReply") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    const tb = document.getElementById('thinkingBubble');
                    if(tb) tb.remove();
                    document.querySelectorAll('.next-btn-class').forEach(el => el.disabled = false);

                    if (data.success) {
                        const newQ = {
                            id: data.next_question_id,
                            question_text: data.next_question_text
                        };
                        questions.push(newQ);
                        
                        answersData.push(defaultAnswerState());
                        currentQIdx++;
                        loadQuestion(currentQIdx);
                    } else {
                        alert(data.error || 'An error occurred.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Network error.");
                    document.querySelectorAll('.next-btn-class').forEach(el => el.disabled = false);
                    const tb = document.getElementById('thinkingBubble');
                    if(tb) tb.remove();
                });
            }

            function skipQuestion() {
                if(isRecording) stopRecording();
                document.getElementById('answerTextarea').value = "[User skipped the question]";
                submitAnswer({ skipped: true });
            }

            function prevQuestion() {
                if(isRecording) stopRecording();
                if (currentQIdx > 0) {
                    loadQuestion(currentQIdx - 1);
                }
            }

            function finishInterview() {
                stopQuestionTimer();
                if ('speechSynthesis' in window) window.speechSynthesis.cancel();
                ['userCamera', 'userCameraMobile'].forEach(id => {
                    let video = document.getElementById(id);
                    if (video && video.srcObject) {
                        video.srcObject.getTracks().forEach(track => track.stop());
                    }
                });
                clearInterval(timerInterval);
                document.getElementById('formDuration').value = timerSeconds;
                document.getElementById('formNotes').value = document.getElementById('sessionNotes').value;
                document.getElementById('finishForm').submit();
            }

            function ucfirst(str) {
                if(!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            }
        </script>
        @else
        <div class="panel">
            <p style="color:var(--tx3)">No questions found for this setup. Please ask an admin to add some.</p>
        </div>
        @endif
    @endif
</div>

@if(isset($sessionRecord) && (bool) data_get($sessionRecord->accommodation_profile, 'camera_coaching', false))
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    if (cameraCoachingEnabled) {
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/'),
            faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/')
        ]).then(() => {
            console.log("Optional camera-framing models loaded");
        }).catch(err => console.error("Error loading optional camera models", err));
    }
</script>
@endif

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '.ai-avatar-panel', popover: { title: 'AI Interviewer', description: 'The interviewer presents each question and guides the session flow.', side: 'bottom', align: 'start' }},
            { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here while live metrics update.', side: 'top', align: 'start' }},
            { element: '#cameraPanel', popover: { title: 'Optional Camera Coach', description: 'Use private framing prompts if helpful. Camera observations never affect readiness scoring.', side: 'top', align: 'start' }},
            { element: '#overallReadiness', popover: { title: 'Practice Signals', description: 'Use these live prompts for practice; the final evidence-linked assessment is produced after the session.', side: 'top', align: 'start' }},
            { element: '.star-item', popover: { title: 'STAR Analyzer', description: 'This tracks Situation, Task, Action, and Result coverage in your answer.', side: 'top', align: 'start' }},
            { element: '#voiceAnalyticsPanel', popover: { title: 'Voice Analytics', description: 'Review speaking duration, pace, and filler word usage.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '.ai-avatar-panel', popover: { title: 'AI Interviewer', description: 'The interviewer presents each question and guides the session flow.', side: 'right', align: 'start' }},
            { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here while live metrics update.', side: 'right', align: 'start' }},
            { element: '#cameraPanel', popover: { title: 'Optional Camera Coach', description: 'Use private framing prompts if helpful. Camera observations never affect readiness scoring.', side: 'left', align: 'start' }},
            { element: '#overallReadiness', popover: { title: 'Practice Signals', description: 'Use these live prompts for practice; the final evidence-linked assessment is produced after the session.', side: 'left', align: 'start' }},
            { element: '.star-item', popover: { title: 'STAR Analyzer', description: 'This tracks Situation, Task, Action, and Result coverage in your answer.', side: 'left', align: 'start' }},
            { element: '#voiceAnalyticsPanel', popover: { title: 'Voice Analytics', description: 'Review speaking duration, pace, and filler word usage.', side: 'left', align: 'start' }}
        ];

        const onboardingTour = window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_interview_session',
            serverDetectedMobile: @json($isMobile),
            stepsMobile: stepsMobile.filter(step => document.querySelector(step.element)),
            stepsDesktop: stepsDesktop.filter(step => document.querySelector(step.element)),
            autoStart: false,
        });
        
        // Expose startOnboardingTour to be called after interview starts
        const originalStartInterview = window.startInterviewSession;
        window.startInterviewSession = function() {
            if (typeof originalStartInterview === 'function') {
                originalStartInterview.apply(this, arguments);
            }

            const shouldAutoStartTour = !window.matchMedia('(max-width: 767px)').matches;
            if (shouldAutoStartTour && onboardingTour && !onboardingTour.isCompleted()) {
                setTimeout(() => {
                    onboardingTour.start();
                }, 1000);
            }
        };
    });
</script>
@endpush
@endsection
