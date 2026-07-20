@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Workspace')
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
        background: transparent !important;
        border: 0 !important;
        max-height: none !important;
        overflow: visible !important;
        font-size: 0.78rem;
        line-height: 1.35;
    }
    #chatTranscriptContainer:empty {
        display: none !important;
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
    .response-title-actions {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
    }
    .response-fullscreen-toggle {
        width: 34px;
        height: 34px;
        border: 1px solid var(--bd);
        border-radius: 10px;
        background: var(--bg3);
        color: var(--tx2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
        transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
    }
    .response-fullscreen-toggle:hover,
    .response-fullscreen-toggle:focus {
        background: rgba(96, 165, 250, 0.12);
        border-color: rgba(96, 165, 250, 0.38);
        color: #60a5fa;
    }
    .response-fullscreen-toggle:active {
        transform: scale(0.94);
    }
    .response-send-answer-btn {
        min-height: 34px;
        padding: 0.45rem 0.72rem;
        border: none;
        border-radius: 10px;
        background: var(--dash-primary, #60a5fa);
        color: #fff;
        box-shadow: 0 4px 15px rgba(96,165,250,0.28);
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1.1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        white-space: nowrap;
    }
    .response-send-answer-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        box-shadow: none;
    }
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
    .question-source-line { display:inline-flex;align-items:center;gap:5px;color:rgba(255,255,255,.72);font-size:.68rem;font-weight:700;margin-top:7px;max-width:100%; }
    .question-source-line a { color:rgba(191,219,254,.95);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
    .question-source-line a:hover { color:#fff;text-decoration:underline; }
    .source-card-line {
        display: flex;
        align-items: flex-start;
        gap: 7px;
        color: var(--tx2);
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1.4;
        max-width: 100%;
    }
    .source-card-line a,
    .source-card-line span:last-child {
        color: var(--tx);
        overflow-wrap: anywhere;
    }
    .source-card-line a {
        text-decoration: none;
    }
    .source-card-line a:hover {
        color: #60a5fa;
        text-decoration: underline;
    }
    .question-caption-overlay {
        position: absolute;
        left: 18px;
        right: 18px;
        bottom: 18px;
        z-index: 60;
        display: flex;
        justify-content: center;
        pointer-events: none;
    }
    .question-caption-line {
        min-height: 2.45em;
        max-width: min(88%, 640px);
        padding: 6px 12px;
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.72);
        color: #fff;
        font-size: clamp(0.72rem, 1.2vw, 0.88rem);
        font-weight: 800;
        line-height: 1.35;
        text-align: center;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.75);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.38);
        opacity: 0;
        transform: translateY(8px);
        transition: opacity 160ms ease, transform 160ms ease;
    }
    .question-caption-line.has-caption {
        opacity: 1;
        transform: translateY(0);
    }
    .question-caption-word {
        display: inline-block;
        margin: 0 0.08em;
        opacity: 0.72;
    }
    .question-caption-word.active {
        color: #fde68a;
        opacity: 1;
    }
    .ai-question-card {
        margin-top: calc(var(--session-gap) * -0.45);
        margin-bottom: var(--session-gap);
        padding: 14px 16px;
        border: 1px solid var(--bd);
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.94));
        box-shadow: var(--shadow-soft, 0 10px 28px rgba(0, 0, 0, 0.14));
    }
    .ai-question-card .interviewer-badge {
        background: var(--pur);
        color: #fff;
        font-size: 0.72rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .interviewer-panel-badge {
        position: absolute;
        top: calc(50% - 88px);
        left: 50%;
        transform: translateX(-50%);
        z-index: 55;
        background: var(--pur);
        color: #fff;
        font-size: 0.72rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        max-width: calc(100% - 150px);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #aiQuestionText {
        display: none;
        color: #fff;
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1.45;
    }
    .session-source-line { color:var(--tx3);font-size:.78rem;line-height:1.35;margin:-12px auto 22px;max-width:480px; }
    .real-interview-mode .coaching-only { display:none !important; }
    .recovery-pill { display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;background:rgba(52,211,153,.12);color:#34d399;font-weight:700;font-size:.76rem;margin-bottom:14px; }
    .question-timer-anchor { position:absolute;top:15px;right:15px;z-index:55; }
    .finish-transition-overlay {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        z-index: 999999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100vw;
        min-width: 100vw;
        height: 100vh;
        height: 100dvh;
        min-height: 100vh;
        min-height: 100dvh;
        margin: 0;
        padding: max(24px, env(safe-area-inset-top, 0px)) 20px max(24px, env(safe-area-inset-bottom, 0px));
        background: var(--bg, #ffffff);
        box-sizing: border-box;
        overflow: hidden;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        text-align: center;
    }
    .finish-transition-overlay.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    body.finish-transition-active {
        overflow: hidden !important;
        touch-action: none;
    }
    .finish-loading-wrapper {
        position: relative;
        width: 120px;
        height: 120px;
        flex: 0 0 120px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .finish-loading-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 4px solid var(--bd, #e2e8f0);
        border-top: 4px solid var(--pur, #7c3aed);
        animation: finishSpin 1s linear infinite;
    }
    .finish-loading-wrapper img {
        width: 70px;
        height: 70px;
        object-fit: contain;
        animation: finishPulse 1.5s ease-in-out infinite;
    }
    .finish-transition-overlay h4 {
        margin: 0;
        color: var(--tx);
        font-weight: 600;
        font-size: 1.2rem;
        line-height: 1.25;
        letter-spacing: 0;
        max-width: min(100%, 420px);
        overflow-wrap: anywhere;
    }
    .finish-transition-overlay p {
        margin: 8px 0 0;
        color: var(--tx3);
        font-size: 0.9rem;
        line-height: 1.45;
        max-width: min(100%, 420px);
        overflow-wrap: anywhere;
    }
    .finish-retry-button {
        margin-top: 16px;
        border: 0;
        border-radius: 12px;
        padding: 10px 18px;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        color: #fff;
        font-weight: 800;
    }
    @keyframes finishSpin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes finishPulse {
        0% { transform: scale(0.9); opacity: 0.8; }
        50% { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(0.9); opacity: 0.8; }
    }
    .mobile-camera-pip { position:absolute; top:15px; right:15px; width:80px; height:80px; border-radius:12px; overflow:hidden; border:2px solid rgba(255,255,255,0.3); z-index:50; box-shadow: 0 4px 15px rgba(0,0,0,0.6); background:#111827; }
    .mobile-camera-placeholder { position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.72);background:linear-gradient(135deg,#111827,#312e81);font-size:1rem;z-index:3; }
    .mobile-camera-pip video { position:relative;z-index:2; }
    .mobile-camera-pip.camera-ready .mobile-camera-placeholder { display:none; }
    .transcription-status {
        display: none;
        max-width: 260px;
        font-size: 0.76rem;
        line-height: 1.25;
        color: var(--tx3);
    }

    /* Circular Audio Spectrum */
    .circular-spectrum { position: absolute; top: 50%; left: 50%; width: 0; height: 0; display: none; z-index: 5; }
    .circular-spectrum .spectrum-bar { position: absolute; bottom: 0; left: -4px; width: 8px; background: linear-gradient(to top, #8b5cf6, #34d399); border-radius: 4px; transform-origin: bottom center; height: 6px; transition: height 0.05s ease-out; box-shadow: 0 0 12px rgba(52,211,153,0.6); }
    
    /* Responsive overrides */
    @media (max-width: 768px) {
        body.mobile-interview-fullscreen {
            min-height: 100dvh;
            overflow: hidden;
            overscroll-behavior: none;
        }
        body.mobile-interview-fullscreen #sec-interview-session {
            --session-gap: 12px;
            min-height: 100dvh;
            height: 100dvh !important;
            padding: 8px 8px max(8px, env(safe-area-inset-bottom, 0px)) !important;
            overflow-y: auto !important;
            overscroll-behavior: contain;
        }
        body.mobile-interview-fullscreen #workspaceWrapper {
            min-height: 100%;
        }
        #sec-interview-session { --session-gap: 12px; }
        .question-caption-overlay {
            left: 12px;
            right: 12px;
            bottom: 12px;
        }
        .question-caption-line {
            min-height: 2.3em;
            max-width: 94%;
            padding: 5px 10px;
            font-size: clamp(0.66rem, 2.8vw, 0.78rem);
            line-height: 1.32;
        }
        .interviewer-panel-badge {
            top: calc(50% - 86px);
            max-width: calc(100% - 28px);
            font-size: 0.66rem;
        }
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
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 6px !important;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            font-size: 0.68rem !important;
        }
        .interview-session-header .sr-page-actions.interview-meta-line span,
        #sec-interview-session .interview-meta-line span {
            min-width: 0;
            width: 100%;
            min-height: 34px;
            padding: 6px 7px;
            border: 1px solid var(--bd);
            border-radius: 999px;
            background: var(--bg3);
            overflow: hidden;
            white-space: normal;
            line-height: 1.2;
            justify-content: center;
            gap: 2px;
        }
        .interview-session-header .sr-page-actions.interview-meta-line span i,
        #sec-interview-session .interview-meta-line span i {
            margin-right: 2px !important;
            font-size: 0.64rem;
            flex: 0 0 auto;
        }
        .avatar-wrapper { transform: scale(0.62); }
        .circular-spectrum { transform: scale(0.62); }
        .ai-avatar-panel { height: 220px !important; }
        .ai-question-card .interviewer-badge {
            border-radius: 999px !important;
            padding: 5px 9px !important;
            font-size: 0.62rem !important;
            font-weight: 800;
            letter-spacing: 0;
        }
        .ai-question-card {
            display: none !important;
        }
        .question-source-line {
            font-size: 0.6rem !important;
            margin-top: 5px;
        }
        .ai-question-card > .d-flex {
            gap: 0 !important;
        }
        .mobile-camera-pip {
            top: 12px !important;
            right: 12px !important;
            width: 66px !important;
            height: 66px !important;
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
            margin-bottom: var(--session-gap) !important;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        body.mobile-interview-fullscreen #workspaceRow {
            row-gap: var(--session-gap) !important;
        }
        body.mobile-interview-fullscreen #workspaceRow > [class*="col-"] {
            display: flex;
            flex-direction: column;
            gap: var(--session-gap);
        }
        body.mobile-interview-fullscreen #workspaceRow > [class*="col-"] > .panel,
        body.mobile-interview-fullscreen #workspaceRow > [class*="col-"] > #interviewControls {
            margin-bottom: 0 !important;
        }
        body.mobile-interview-fullscreen #workspaceRow > .col-lg-4 {
            margin-top: 0 !important;
        }
        .panel:hover { transform: none; }
        .panel-title {
            font-size: 0.86rem;
            margin-bottom: 10px;
            line-height: 1.15;
        }
        .response-title-actions {
            gap: 6px;
        }
        .response-fullscreen-toggle {
            width: 32px;
            height: 32px;
            border-radius: 9px;
            font-size: 0.78rem;
        }
        .response-send-answer-btn {
            min-height: 32px;
            padding: 0.42rem 0.58rem;
            font-size: 0.68rem;
            border-radius: 9px;
        }
        .response-send-answer-btn i {
            font-size: 0.64rem;
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
            display: grid !important;
            grid-template-columns: minmax(58px, 1fr) minmax(66px, 1fr) auto auto auto minmax(0, auto);
            align-items: center !important;
            border-radius: 14px;
            padding: 6px;
            gap: 4px !important;
            margin-bottom: 0 !important;
            overflow: hidden;
        }
        #interviewControls > div { gap: 4px !important; width: auto !important; min-width: 0; }
        #interviewControls > div:first-child {
            display: contents !important;
        }
        #interviewControls > div:first-child .btn {
            min-width: 0;
            width: 100%;
            min-height: 32px;
            padding: 0.32rem 0.24rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            font-size: 0.52rem;
            line-height: 1.15;
            border-radius: 8px !important;
            white-space: nowrap;
        }
        #interviewControls > div:first-child .btn i {
            margin: 0 !important;
            font-size: 0.54rem;
        }
        #interviewControls > div:nth-child(2) {
            display: contents !important;
        }
        #interviewControls > div:nth-child(2) > * {
            min-width: 0;
        }
        #interviewControls > div:nth-child(2) {
            align-items: center !important;
            justify-content: center !important;
            width: auto !important;
        }
        #recordingTimer {
            margin-right: 0 !important;
            font-size: 0.5rem !important;
            min-width: 26px;
            text-align: center;
        }
        #voiceControls {
            width: auto;
            flex: 0 0 auto;
        }
        #voiceControls .d-flex.gap-2 {
            display: flex !important;
            align-items: center;
            justify-content: center;
            gap: 4px !important;
            flex-wrap: nowrap !important;
        }
        #voiceControls .btn {
            width: 30px;
            min-width: 0;
            min-height: 28px !important;
            height: 28px !important;
            padding: 0.24rem !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px !important;
        }
        #voiceControls .btn i {
            margin: 0 !important;
            font-size: 0.62rem;
        }
        .transcription-status {
            max-width: 72px;
            display: none;
            font-size: 0.56rem;
            line-height: 1.25;
            text-align: left;
            color: var(--tx3);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #interviewControls .btn { min-height: 32px; padding: 0.36rem 0.42rem; }
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
        #chatTranscriptContainer {
            padding: 0 !important;
            margin-bottom: 8px !important;
            max-height: none !important;
            overflow: visible !important;
            gap: 8px !important;
        }
        #answerTextarea {
            min-height: 76px !important;
            border-radius: 12px !important;
            padding: 9px 10px !important;
            font-size: 0.78rem !important;
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
        body.mobile-interview-fullscreen #introContainer {
            margin-top: 10px !important;
        }
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
        .question-timer-anchor {
            top: 12px !important;
            right: 12px !important;
            z-index: 60;
        }
        .question-timer-anchor { display: none; }
        #aiQuestionText { max-height: 4.5em; overflow-y: auto; }
        .finish-transition-overlay {
            padding: max(18px, env(safe-area-inset-top, 0px)) 16px max(18px, env(safe-area-inset-bottom, 0px));
        }
        .finish-loading-wrapper {
            width: 96px;
            height: 96px;
            flex-basis: 96px;
            margin-bottom: 16px;
        }
        .finish-loading-circle {
            border-width: 3px;
        }
        .finish-loading-wrapper img {
            width: 56px;
            height: 56px;
        }
        .finish-transition-overlay h4 {
            max-width: 300px;
            font-size: 1.02rem;
        }
        .finish-transition-overlay p {
            max-width: 300px;
            font-size: 0.82rem;
        }
    }

    @media (max-width: 380px) {
        #sec-interview-session { --session-gap: 10px; }
        .finish-loading-wrapper {
            width: 84px;
            height: 84px;
            flex-basis: 84px;
            margin-bottom: 14px;
        }
        .finish-loading-wrapper img {
            width: 50px;
            height: 50px;
        }
        .finish-transition-overlay h4 {
            font-size: 0.96rem;
        }
        .finish-transition-overlay p {
            font-size: 0.78rem;
        }
        .interview-session-title { font-size: 1.06rem !important; }
        .interview-session-header .sr-page-actions.interview-meta-line,
        #sec-interview-session .interview-meta-line {
            gap: 5px !important;
            font-size: 0.62rem !important;
        }
        .interview-session-header .sr-page-actions.interview-meta-line span,
        #sec-interview-session .interview-meta-line span {
            padding: 5px 4px;
        }
        .interview-session-header .sr-page-actions.interview-meta-line span i,
        #sec-interview-session .interview-meta-line span i {
            font-size: 0.58rem;
        }
        .ai-avatar-panel { height: 204px !important; }
        .mobile-camera-pip { width: 62px !important; height: 62px !important; }
        .ai-question-card { padding: 10px; }
        .question-timer-anchor { top: 10px !important; right: 10px !important; }
        .session-chip { padding: 6px 8px; font-size: 0.7rem; }
        #interviewControls { padding: 6px; gap: 4px !important; }
        #interviewControls .btn { font-size: 0.52rem; }
        #interviewControls > div:first-child .btn {
            min-height: 28px;
            padding: 0.3rem 0.18rem;
            font-size: 0.48rem;
        }
        #interviewControls > div:first-child .btn i {
            font-size: 0.5rem;
        }
        #recordingTimer {
            font-size: 0.48rem !important;
            min-width: 24px;
        }
        #voiceControls .d-flex.gap-2 {
            gap: 6px !important;
        }
        #voiceControls .btn {
            width: 28px;
            min-height: 26px !important;
            height: 26px !important;
            padding: 0.22rem !important;
        }
        #voiceControls .btn i {
            font-size: 0.58rem;
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
                $questions = \App\Models\Question::where('interview_session_id', $sessionRecord->id)
                    ->orderBy('id')
                    ->get();
                
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
                $focusText = strtolower((string) $sessionRecord->interview_focus);
                $sourcePackKey = match (true) {
                    str_contains($focusText, 'bpo'), str_contains($focusText, 'customer support'), str_contains($focusText, 'contact center') => 'ph_bpo_communication',
                    str_contains($focusText, 'it / programming'), str_contains($focusText, 'programming'), str_contains($focusText, 'software'), str_contains($focusText, 'technical') => 'ph_it_programming',
                    str_contains($focusText, 'scholarship') => 'ph_scholarship',
                    str_contains($focusText, 'college'), str_contains($focusText, 'admission') => 'ph_college_admission',
                    default => \App\Services\QuestionDatasetProvider::defaultKeyForCategory($sessionRecord->category->title ?? null),
                };
                $sourcePack = \App\Services\QuestionDatasetProvider::find($sourcePackKey)
                    ?? ($sessionRecord->category ? \App\Services\QuestionDatasetProvider::forCategory($sessionRecord->category) : null);
                $scenarioLabels = [
                    'ph_job_interview' => 'General Job Interview',
                    'ph_bpo_communication' => 'BPO / Customer Support Interview',
                    'ph_it_programming' => 'IT / Programming Interview',
                    'ph_scholarship' => 'Scholarship Interview',
                    'ph_college_admission' => 'College Admission Interview',
                ];
                $scenarioLabel = $scenarioLabels[$sourcePack['key'] ?? $sourcePackKey] ?? 'Philippines Interview';
                $sourceNames = collect($sourcePack['sources'] ?? [])->pluck('name')->filter()->take(3)->implode(', ');
                $primarySource = $questions->first(fn ($question) => filled($question->source_name))
                    ?? (object) [
                        'source_name' => data_get($sourcePack, 'sources.0.name'),
                        'source_url' => data_get($sourcePack, 'sources.0.url'),
                        'source_type' => $sourcePack['source_type'] ?? 'dataset',
                    ];
            } else {
                $questions = collect([]);
            }
        @endphp

        @if($sessionRecord && $questions->count() > 0)
        <div id="workspaceWrapper" style="display:none;">
        <div class="row g-4" id="workspaceRow">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <!-- Progress Tracker Removed by User -->

                <!-- Simulated AI Video Avatar Panel -->
                <div class="panel p-0 ai-avatar-panel animate-fade-up delay-100" style="overflow:hidden;border:1px solid var(--bd);background:#000;position:relative;height:280px;border-radius:24px;margin-bottom:24px;box-shadow:0 15px 40px rgba(0,0,0,0.15);">
                    <div style="position:absolute; inset:0; background: radial-gradient(circle at top right, rgba(139,92,246,0.3), transparent 60%), radial-gradient(circle at bottom left, rgba(59,130,246,0.3), transparent 60%); z-index:1; pointer-events:none;"></div>
                    <!-- Video-call style self-view -->
                    <div class="d-block mobile-camera-pip">
                        <video id="userCameraMobile" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);background:#222;"></video>
                        <div class="mobile-camera-placeholder" aria-hidden="true"><i class="fa-solid fa-video"></i></div>
                    </div>
                    <!-- Question Counter (Top Left) -->
                    <div style="position:absolute; top:15px; left:15px; z-index:50;">
                        <span class="badge bg-white text-dark shadow-sm" style="font-size:0.8rem;white-space:nowrap;padding: 6px 10px;" id="qCounter">1/10</span>
                    </div>
                    <span class="badge interviewer-panel-badge"><i class="fa-solid fa-bolt me-1"></i> Philippines interviewer</span>
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
                    <div class="question-caption-overlay" aria-live="polite" aria-atomic="true">
                        <div id="questionCaptionText" class="question-caption-line"></div>
                    </div>
                </div>

                <div class="ai-question-card animate-fade-up delay-150">
                    <div class="d-flex justify-content-center align-items-end gap-3 text-center">
                        <div class="w-100">
                            <div id="aiQuestionText">Loading your first question...</div>
                        </div>
                    </div>
                </div>

                <!-- Unified Responsive Interview Controls (Desktop & Mobile) -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 mb-4 animate-fade-up delay-150" id="interviewControls" style="opacity: 0; pointer-events: none; transition: opacity 0.3s;">
                    <!-- Left: Navigation / Secondary -->
                    <div class="d-flex gap-2 w-100 flex-fill">
                        <button type="button" class="btn btn-outline-info flex-fill" onclick="repeatQuestion()" style="border-radius:12px;"><i class="fa-solid fa-volume-high me-2"></i>Repeat</button>
                        <button type="button" class="btn btn-outline-danger flex-fill" onclick="abortInterviewSession()" style="border-radius:12px;"><i class="fa-solid fa-flag-checkered me-2"></i>End Session</button>
                    </div>
                    
                    <!-- Right: Primary Actions (Mic + Send) -->
                    <div class="d-flex gap-2 w-100 flex-fill justify-content-md-end align-items-center">
                        <span id="recordingTimer" style="font-family:monospace;font-size:1.1rem;color:#f87171;display:block;margin-right:10px;font-weight:bold;">00:00</span>
                        
                        <!-- Voice Recording Controls -->
                        <div id="voiceControls" style="display:none; margin:0; padding:0; border:none; background:transparent;">
                            @if($sessionRecord->game_level_id)
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
                        <span id="transcriptionStatus" class="transcription-status" aria-live="polite" aria-atomic="true"></span>

                    </div>
                </div>

                <!-- Answer Response System -->
                <div class="panel mb-4 animate-fade-up delay-200">
                    <div class="panel-title">
                        <i class="fa-solid fa-pen-nib me-2"></i> Your Response
                        <div class="response-title-actions">
                            @if($sessionRecord->game_level_id)
                                <span class="badge" style="background:#ef4444; color:white;"><i class="fa-solid fa-gamepad me-1"></i> GAME MODE</span>
                            @endif
                            <button type="button" class="next-btn-class send-answer-btn response-send-answer-btn btn-shine" onclick="submitAnswer()">
                                Send Answer <i class="fa-solid fa-paper-plane"></i>
                            </button>
                            <button type="button" id="responseFullscreenToggle" class="response-fullscreen-toggle" onclick="toggleMobileFullscreen()" aria-label="Enter fullscreen" title="Enter fullscreen">
                                <i class="fa-solid fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    
                    <form id="answerForm">
                        <!-- Voice controls moved to interviewControls panel -->

                        <div id="chatTranscriptContainer" style="max-height: none; overflow: visible; padding: 0; margin-bottom: 12px; background: transparent; border: 0; display: none; flex-direction: column; gap: 10px;"></div>
                        <textarea id="answerTextarea" class="oinp mb-2" style="min-height:76px;font-size:.82rem" placeholder="Type your answer using your own Philippine school, work, internship, or project evidence..."></textarea>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div style="font-size:.8rem;color:var(--tx3)">
                                <span id="wordCount">0 words</span> - <span id="charCount">0 characters</span>
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
                        <div id="cameraCoachStatus" style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.65);padding:2px 8px;border-radius:4px;font-size:.7rem;color:#cbd5e1"><i class="fa-solid fa-laptop me-1"></i>Local estimate</div>
                    </div>
                    <div class="stat-row"><span>Face in frame</span><span id="stEyeContact" class="text-secondary">Waiting</span></div>
                    <div class="stat-row mb-0"><span>Head alignment estimate</span><span id="stPosture" class="text-secondary">Not scored</span></div>
                    <div class="small mt-2" style="color:var(--tx3)">This estimates face visibility and head alignment only. Video is analyzed in your browser; no images or video are stored. Only derived timestamps and framing/head-alignment samples are saved for this report, and they are excluded from readiness.</div>
                </div>
                @endif

                <!-- Question Source -->
                <div class="panel animate-fade-up delay-150" id="questionSourcePanel">
                    <div class="panel-title"><i class="fa-solid fa-link me-2"></i> Question Source</div>
                    <div id="aiQuestionSource" class="source-card-line" data-default-name="{{ $primarySource->source_name ?? '' }}" data-default-url="{{ $primarySource->source_url ?? '' }}" data-default-type="{{ $primarySource->source_type ?? 'dataset' }}">
                        <span style="color:var(--tx3);font-weight:600">Source will appear when the question starts.</span>
                    </div>
                </div>

                <!-- AI Visualizer Panel -->
                <div class="panel animate-fade-up delay-200 coaching-only" id="liveFeedbackPanel">
                    <div class="panel-title"><i class="fa-solid fa-chart-pie me-2"></i> Live Practice Signals</div>
                    <div class="text-center mb-3">
                        <div style="font-size:2rem;font-weight:700;color:#34d399" id="overallReadiness">--%</div>
                        <div style="font-size:.75rem;color:var(--tx3)">Coaching estimate - not an assessment</div>
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
                    <div class="stat-row mb-0"><span>Possible transcript filler matches</span><span id="vaFillers" class="text-danger">0</span></div>
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
            <h4 style="color:var(--tx);font-weight:700">{{ $hasSavedInterviewState ? 'Philippines Interview Saved' : 'Philippines Interview Ready' }}</h4>
            <p style="color:var(--tx3);margin-bottom:30px">Your session is configured with {{ $num }} Philippines-focused questions. The AI greeting is not counted as a question.</p>
            @if($sourceNames)
                <div class="session-source-line"><i class="fa-solid fa-link me-1"></i> Scenario: {{ $scenarioLabel }}. Sources: {{ $sourceNames }}.</div>
            @endif
            <div class="intro-badge-grid" style="display:flex; justify-content:center; gap: 10px; flex-wrap: wrap; margin-bottom: 30px;">
                <span class="db-badge" style="background:rgba(14,165,233,.13);color:#38bdf8"><i class="fa-solid fa-flag me-1"></i> {{ $scenarioLabel }}</span>
                <span class="db-badge" style="background:rgba(59,130,246,.15);color:#60a5fa"><i class="fa-solid fa-microphone me-1"></i> {{ ucfirst($sessionRecord->response_mode) }} Mode</span>
                <span class="db-badge" style="background:rgba(245,158,11,.12);color:#f59e0b"><i class="fa-solid fa-stopwatch me-1"></i> {{ $sessionRecord->time_limit ? $sessionRecord->time_limit . 'm / Q' : 'Self-paced' }}</span>
                <span class="db-badge" style="background:rgba(52,211,153,.12);color:#34d399"><i class="fa-solid fa-list-check me-1"></i> {{ $num }} Questions</span>
            </div>
            <button class="btn px-4 py-3 w-100 btn-shine intro-start-btn" style="font-size:1.15rem;font-weight:700;border-radius:14px;background:var(--dash-primary, #60a5fa);color:white;border:none;box-shadow:0 8px 25px rgba(96,165,250,0.4);transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(96,165,250,0.6)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 8px 25px rgba(96,165,250,0.4)'" onclick="startInterviewSession()">{{ $hasSavedInterviewState ? 'Resume PH Interview' : 'Begin PH Interview' }} <i class="fa-solid fa-play ms-2"></i></button>
        </div>

        <form id="finishForm" action="{{ route('interview.finish') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="session_id" value="{{ $sessionRecord->id }}">
            <input type="hidden" name="duration_seconds" id="formDuration">
            <input type="hidden" name="notes" id="formNotes">
        </form>

        <div id="finishTransitionOverlay" class="finish-transition-overlay" role="status" aria-live="polite" aria-atomic="true">
            <div class="finish-loading-wrapper">
                <div class="finish-loading-circle"></div>
                <img src="{{ asset('img/logo.png') }}" alt="Loading feedback">
            </div>
            <h4>Analyzing your response...</h4>
            <p id="finishTransitionMessage">Please wait while we finalize your interview report.</p>
            <button type="button" id="finishRetryButton" class="finish-retry-button" style="display:none;" onclick="retryFinishInterview()"><i class="fa-solid fa-rotate-right me-1"></i>Retry report</button>
        </div>

        <script>
            const savedSessionState = @json($savedStateForUi ?? []);
            const initialQuestions = {!! json_encode($questions) !!};
            const savedQuestionSequence = Array.isArray(savedSessionState.questions)
                ? savedSessionState.questions.filter(question => question && question.id && question.question_text)
                : [];
            let questions = savedQuestionSequence.length > 0 ? savedQuestionSequence : initialQuestions;
            const interviewSessionId = {{ (int) $sessionRecord->id }};
            const targetQuestionCount = Math.max({{ $num }}, 1);
            const sessionTargetPosition = @json($sessionRecord->target_position ?? 'this role');
            const sessionScenarioLabel = @json($scenarioLabel ?? 'Philippines Interview');
            const sessionDifficultyLabel = @json(ucfirst((string) ($sessionRecord->difficulty ?? 'medium')));
            const responseMode = "{{ $sessionRecord->response_mode }}";
            const perQuestionLimitSeconds = {{ (int) (($sessionRecord->time_limit ?? 0) * 60) }};
            const assistanceLevel = @json($sessionRecord->ai_assistance_level ?? 'standard');
            const liveFeedbackMode = @json($sessionRecord->live_feedback_mode ?? 'coaching');
            const cameraCoachingEnabled = @json((bool) data_get($sessionRecord->accommodation_profile, 'camera_coaching', false));
            const cameraPreviewEnabled = true;
            let cameraUnavailableReason = null;
            const serverAiVoiceEnabled = @json(filter_var(config('services.openai.tts_enabled', false), FILTER_VALIDATE_BOOLEAN));
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
            let interviewEnding = false;
            let interviewTerminated = false;
            let interviewStarted = false;
            let answerListenersBound = false;
            let isSubmittingAnswer = false;
            let finalAnswerSubmitted = false;
            let feedbackSubmissionInFlight = false;
            let openingHasPlayed = Boolean(savedSessionState.openingHasPlayed || (Array.isArray(chatHistory) && chatHistory.some(item => {
                const text = String(item?.text || '');
                return item && item.role === 'interviewer' && (
                    text.includes('Let us start with the first question.')
                    || text.includes('To begin, I would like to get to know you first.')
                );
            })));
            const pendingFetchControllers = new Set();
            const displayedQuestionIds = new Set();
            const firstQuestionIntroText = 'Heres your first questions';
            const firstQuestionIntroDisplayKey = '__first_scored_question_intro__';
            
            // Answers state
            function defaultAnswerState() {
                return {
                    text: '',
                    speech_transcript: '',
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
                    paste_event_count: 0,
                    pasted_character_count: 0,
                    transcript_timeline: [],
                    observation_data: {
                        filler_events: [],
                        camera_samples: [],
                        camera_unavailable_reason: cameraUnavailableReason
                    }
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
            let microphoneStream = null;
            let microphoneReadyPromise = null;
            let serverTranscriptionRecorder = null;
            let serverTranscriptionStream = null;
            let serverTranscriptionQueue = [];
            let serverTranscriptionProcessing = false;
            let serverTranscriptionDrainResolver = null;
            let serverTranscriptionDrainTimer = null;
            let serverTranscriptionUnavailable = false;
            let serverTranscriptionSessionToken = 0;
            let cameraTrackingInFlight = false;

            const BrowserSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const serverTranscriptionEnabled = @json(\App\Services\AIService::speechTranscriptionAvailable());
            const mobileSpeechSurface = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
            const localMicrophoneHosts = new Set(['localhost', '127.0.0.1', '::1', '[::1]']);
            const speechLocale = document.documentElement.dataset.speechLocale || navigator.language || 'en-US';
            const speechLanguage = speechLocale.split('-')[0];
            const serverTranscriptionMimeType = (() => {
                if (!window.MediaRecorder || !MediaRecorder.isTypeSupported) return '';
                return [
                    'audio/webm;codecs=opus',
                    'audio/webm',
                    'audio/mp4;codecs=mp4a.40.2',
                    'audio/mp4',
                    'audio/ogg;codecs=opus',
                    'audio/ogg'
                ].find(type => MediaRecorder.isTypeSupported(type)) || '';
            })();
            const serverTranscriptionSupported = serverTranscriptionEnabled
                && Boolean(window.MediaRecorder)
                && Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
            let activeTranscriptionEngine = BrowserSpeechRecognition ? 'browser' : (serverTranscriptionSupported ? 'server' : null);
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
                if (isFillerOnlySpeech(normalizedPhrase)) {
                    return false;
                }
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

            function isFillerOnlySpeech(segment) {
                const normalized = normalizeTranscriptForMatch(segment);
                return /^(?:(?:you know|i mean|sort of|kind of|um+|uh+|erm+|hmm+|like|actually|basically|literally)(?:\s+|$))+$/i.test(normalized);
            }

            function commitSpeechSegment(segment) {
                const cleanSegment = collapseRepeatedSpeech(cleanTranscriptText(segment));
                if (!cleanSegment) return false;

                const normalized = normalizeTranscriptForMatch(cleanSegment);
                const now = Date.now();
                const fillerOnly = isFillerOnlySpeech(cleanSegment);
                const duplicateWindowMs = fillerOnly ? 750 : 5000;
                if (normalized && normalized === lastCommittedSpeech && (now - lastCommittedAt) < duplicateWindowMs) {
                    return false;
                }

                const appendSpeech = existing => fillerOnly
                    ? cleanTranscriptText(`${existing || ''} ${cleanSegment}`)
                    : appendWithoutOverlap(existing || '', cleanSegment);
                committedSpeechTranscript = collapseRepeatedSpeech(appendSpeech(committedSpeechTranscript));
                const answerState = answersData[currentQIdx] || defaultAnswerState();
                answerState.speech_transcript = collapseRepeatedSpeech(appendSpeech(answerState.speech_transcript));
                answersData[currentQIdx] = answerState;
                lastCommittedSpeech = normalized;
                lastCommittedAt = now;
                return true;
            }

            function renderSpeechTranscript() {
                const ta = document.getElementById('answerTextarea');
                if (!ta) return;

                const recognizedTranscript = mergeTranscriptParts(committedSpeechTranscript, liveSpeechInterim);
                ta.value = mergeTranscriptParts(preRecordingText, recognizedTranscript);
                triggerAnalysis();
            }

            function setTranscriptionStatus(message, color = 'var(--tx3)') {
                const status = document.getElementById('transcriptionStatus');
                if (!status) return;
                status.textContent = message || '';
                status.style.color = color;
                status.style.display = message ? 'inline-block' : 'none';
            }

            function microphoneRequiresSecureOrigin() {
                return !(window.isSecureContext || localMicrophoneHosts.has(window.location.hostname));
            }

            function audioCaptureConstraints() {
                return {
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true
                    }
                };
            }

            function stopMediaStream(stream) {
                if (!stream) return;
                stream.getTracks().forEach(track => track.stop());
            }

            async function requestMicrophoneStream() {
                try {
                    return await navigator.mediaDevices.getUserMedia(audioCaptureConstraints());
                } catch (error) {
                    if (error?.name === 'OverconstrainedError' || error?.name === 'ConstraintNotSatisfiedError') {
                        return navigator.mediaDevices.getUserMedia({ audio: true });
                    }
                    throw error;
                }
            }

            function microphoneErrorMessage(error) {
                const name = error?.name || error || 'unknown';
                if (name === 'NotAllowedError' || name === 'SecurityError' || name === 'not-allowed') {
                    return 'Microphone permission is blocked. Allow Microphone for this site, then try again.';
                }
                if (name === 'service-not-allowed') {
                    return 'Browser speech recognition is blocked, switching to server transcription if available.';
                }
                if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                    return 'No microphone was detected on this device.';
                }
                if (name === 'NotReadableError' || name === 'TrackStartError' || name === 'audio-capture') {
                    return 'The microphone is unavailable or already being used by another app.';
                }
                if (name === 'network') {
                    return 'Browser speech recognition lost connection, switching to server transcription if available.';
                }
                return 'Microphone could not start. Allow Microphone for this site, then try again.';
            }

            function transcriptionUnavailableMessage() {
                if (microphoneRequiresSecureOrigin()) {
                    return 'Microphone access requires HTTPS online, or http://localhost for local testing.';
                }
                if (!BrowserSpeechRecognition && !serverTranscriptionEnabled) {
                    return 'Live transcription needs Chrome/Edge, or an OpenAI key for server transcription.';
                }
                if (!BrowserSpeechRecognition && !window.MediaRecorder) {
                    return 'Live transcription is not supported in this browser.';
                }
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    return 'Microphone access is not available in this browser.';
                }
                return 'Live transcription is not available on this device.';
            }

            function canUseServerTranscription() {
                return serverTranscriptionSupported && !serverTranscriptionUnavailable && !microphoneRequiresSecureOrigin();
            }

            function preferredTranscriptionEngine() {
                if (microphoneRequiresSecureOrigin()) return null;
                if (activeTranscriptionEngine === 'server' && canUseServerTranscription()) return 'server';
                if (recognition) return 'browser';
                if (canUseServerTranscription()) return 'server';
                return null;
            }

            function startSpeechRecognitionEngine() {
                if (!recognition) {
                    setTranscriptionStatus('Browser speech recognition is not supported.', '#fbbf24');
                    return false;
                }
                if (recognitionActive || !isRecording || !shouldAutoRestartRecognition || activeTranscriptionEngine !== 'browser') {
                    return false;
                }

                try {
                    recognition.start();
                    recognitionActive = true;
                    setTranscriptionStatus('Listening - speak now');
                    return true;
                } catch (error) {
                    if (!error || error.name !== 'InvalidStateError') {
                        console.error('Speech recognition failed to start:', error);
                        setTranscriptionStatus(microphoneErrorMessage(error), '#f87171');
                    }
                    return false;
                }
            }

            async function ensureMicrophoneReady(engine = 'browser') {
                if (microphoneRequiresSecureOrigin()) {
                    setTranscriptionStatus(transcriptionUnavailableMessage(), '#f87171');
                    return false;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    setTranscriptionStatus(transcriptionUnavailableMessage(), '#f87171');
                    return false;
                }

                if (engine === 'server' && serverTranscriptionStream && serverTranscriptionStream.active) {
                    return true;
                }

                if (!microphoneReadyPromise) {
                    setTranscriptionStatus('Requesting microphone permission', '#fbbf24');
                    microphoneReadyPromise = requestMicrophoneStream().then(stream => {
                        if (engine === 'server') {
                            serverTranscriptionStream = stream;
                        } else {
                            stopMediaStream(stream);
                        }
                        return true;
                    }).catch(error => {
                        setTranscriptionStatus(microphoneErrorMessage(error), '#f87171');
                        return false;
                    }).finally(() => {
                        microphoneReadyPromise = null;
                    });
                }

                return microphoneReadyPromise;
            }

            function releaseMicrophoneStream() {
                stopMediaStream(microphoneStream);
                microphoneStream = null;
                releaseServerTranscriptionStream();
            }

            function finalizeInterimTranscript() {
                if (!liveSpeechInterim) return;
                if (commitSpeechSegment(liveSpeechInterim)) {
                    recordFillerEvents(liveSpeechInterim);
                }
                liveSpeechInterim = '';
                renderSpeechTranscript();
            }

            function releaseServerTranscriptionStream() {
                stopMediaStream(serverTranscriptionStream);
                serverTranscriptionStream = null;
            }

            function serverTranscriptionFilename(blob) {
                const type = String(blob?.type || serverTranscriptionMimeType || '').toLowerCase();
                if (type.includes('mp4')) return 'speech.mp4';
                if (type.includes('ogg')) return 'speech.ogg';
                if (type.includes('mpeg')) return 'speech.mp3';
                if (type.includes('wav')) return 'speech.wav';
                return 'speech.webm';
            }

            function resolveServerTranscriptionDrain() {
                if (serverTranscriptionQueue.length > 0 || serverTranscriptionProcessing || !serverTranscriptionDrainResolver) {
                    return;
                }

                if (serverTranscriptionDrainTimer) {
                    clearTimeout(serverTranscriptionDrainTimer);
                    serverTranscriptionDrainTimer = null;
                }

                const resolve = serverTranscriptionDrainResolver;
                serverTranscriptionDrainResolver = null;
                resolve();
            }

            function waitForServerTranscriptionDrain(timeoutMs = 10000) {
                if (serverTranscriptionQueue.length === 0 && !serverTranscriptionProcessing) {
                    return Promise.resolve();
                }

                return new Promise(resolve => {
                    serverTranscriptionDrainResolver = resolve;
                    serverTranscriptionDrainTimer = setTimeout(() => {
                        serverTranscriptionDrainResolver = null;
                        serverTranscriptionDrainTimer = null;
                        resolve();
                    }, timeoutMs);
                    resolveServerTranscriptionDrain();
                });
            }

            function queueServerTranscriptionChunk(blob) {
                if (!blob || blob.size < 700 || !questions[currentQIdx]) return;

                serverTranscriptionQueue.push({
                    blob,
                    questionIndex: currentQIdx,
                    questionId: questions[currentQIdx].id,
                    token: serverTranscriptionSessionToken
                });
                processServerTranscriptionQueue();
            }

            async function processServerTranscriptionQueue() {
                if (serverTranscriptionProcessing) return;
                serverTranscriptionProcessing = true;

                while (serverTranscriptionQueue.length > 0) {
                    const job = serverTranscriptionQueue.shift();
                    try {
                        await transcribeServerChunk(job);
                    } catch (error) {
                        if (error.name !== 'AbortError') {
                            console.warn('Server transcription failed:', error);
                            setTranscriptionStatus('Server transcription is temporarily unavailable.', '#f87171');
                        }
                    }
                }

                serverTranscriptionProcessing = false;
                resolveServerTranscriptionDrain();
            }

            async function transcribeServerChunk(job) {
                if (!job || job.token !== serverTranscriptionSessionToken || !job.questionId) return;

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);
                formData.append('question_id', job.questionId);
                formData.append('audio', job.blob, serverTranscriptionFilename(job.blob));

                const response = await managedFetch('{{ route("interview.transcribe") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 503) {
                        serverTranscriptionUnavailable = true;
                    }
                    throw new Error(data.error || 'Transcription request failed.');
                }

                const transcript = cleanTranscriptText(data.transcript || '');
                if (!transcript || job.token !== serverTranscriptionSessionToken || job.questionIndex !== currentQIdx) return;

                if (commitSpeechSegment(transcript)) {
                    recordFillerEvents(transcript);
                    captureTranscriptTimeline('server_transcript');
                }
                liveSpeechInterim = '';
                renderSpeechTranscript();
                if (isRecording && activeTranscriptionEngine === 'server') {
                    setTranscriptionStatus('Listening - server transcription');
                }
            }

            function startServerTranscriptionEngine() {
                if (!canUseServerTranscription() || !serverTranscriptionStream || !serverTranscriptionStream.active) {
                    setTranscriptionStatus(transcriptionUnavailableMessage(), '#f87171');
                    return false;
                }

                try {
                    const options = serverTranscriptionMimeType ? { mimeType: serverTranscriptionMimeType } : undefined;
                    serverTranscriptionRecorder = new MediaRecorder(serverTranscriptionStream, options);
                    serverTranscriptionSessionToken++;
                    serverTranscriptionRecorder.ondataavailable = event => queueServerTranscriptionChunk(event.data);
                    serverTranscriptionRecorder.onerror = event => {
                        console.warn('MediaRecorder transcription error:', event.error || event);
                        setTranscriptionStatus('Microphone recording failed. Try again.', '#f87171');
                    };
                    serverTranscriptionRecorder.start(4200);
                    setTranscriptionStatus('Listening - server transcription');
                    return true;
                } catch (error) {
                    console.error('Server transcription recorder failed:', error);
                    setTranscriptionStatus(microphoneErrorMessage(error), '#f87171');
                    releaseServerTranscriptionStream();
                    return false;
                }
            }

            async function stopServerTranscriptionEngine() {
                const recorder = serverTranscriptionRecorder;
                if (!recorder) {
                    releaseServerTranscriptionStream();
                    await waitForServerTranscriptionDrain();
                    return;
                }

                await new Promise(resolve => {
                    let settled = false;
                    const finish = () => {
                        if (settled) return;
                        settled = true;
                        serverTranscriptionRecorder = null;
                        releaseServerTranscriptionStream();
                        resolve();
                    };

                    recorder.onstop = finish;
                    try {
                        if (recorder.state !== 'inactive') {
                            recorder.requestData();
                            recorder.stop();
                        } else {
                            finish();
                        }
                    } catch (error) {
                        console.warn('Server transcription stop failed:', error);
                        finish();
                    }

                    setTimeout(finish, 8000);
                });

                await waitForServerTranscriptionDrain();
            }

            async function activateServerTranscriptionFallback(message = 'Using server transcription fallback') {
                if (!isRecording || !canUseServerTranscription()) return false;

                shouldAutoRestartRecognition = false;
                activeTranscriptionEngine = 'server';
                setTranscriptionStatus(message, '#fbbf24');

                if (recognition && recognitionActive) {
                    try {
                        recognition.stop();
                    } catch (error) {
                        console.warn('Browser recognition stop failed before fallback:', error);
                    }
                    await new Promise(resolve => setTimeout(resolve, 350));
                }

                if (!await ensureMicrophoneReady('server')) return false;
                return startServerTranscriptionEngine();
            }

            let lastSpeechEnd = 0;
            if (BrowserSpeechRecognition) {
                recognition = new BrowserSpeechRecognition();
                recognition.continuous = !mobileSpeechSurface;
                recognition.interimResults = true;
                recognition.lang = speechLocale;
                recognition.maxAlternatives = 3;

                recognition.onstart = function() {
                    recognitionActive = true;
                    setTranscriptionStatus('Transcribing');
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
                            if (commitSpeechSegment(transcript)) {
                                recordFillerEvents(transcript);
                            }
                        } else {
                            interimParts.push(transcript);
                        }
                    }

                    liveSpeechInterim = cleanTranscriptText(interimParts.join(' '));
                    renderSpeechTranscript();
                };

                recognition.onerror = function(event) {
                    recognitionActive = false;
                    const error = event.error || 'unknown';
                    console.warn('Speech recognition error:', error);

                    if (['network', 'service-not-allowed'].includes(error) && canUseServerTranscription()) {
                        setTimeout(() => activateServerTranscriptionFallback(microphoneErrorMessage(error)), 0);
                        return;
                    }

                    if (['not-allowed', 'service-not-allowed', 'audio-capture'].includes(error)) {
                        shouldAutoRestartRecognition = false;
                        setTranscriptionStatus(microphoneErrorMessage(error), '#f87171');
                    } else if (error === 'no-speech' && isRecording) {
                        shouldAutoRestartRecognition = true;
                        setTranscriptionStatus('Still listening - speak close to the mic', '#fbbf24');
                    }
                };

                recognition.onend = function() {
                    recognitionActive = false;
                    if (shouldAutoRestartRecognition && isRecording && activeTranscriptionEngine === 'browser') {
                        setTimeout(startSpeechRecognitionEngine, mobileSpeechSurface ? 650 : 250);
                    }
                };
            }

            function markCameraUnavailable(reason) {
                const allowedReasons = ['permission_denied', 'device_unavailable', 'browser_unsupported', 'model_unavailable', 'camera_error'];
                cameraUnavailableReason = allowedReasons.includes(reason) ? reason : 'camera_error';
                if (cameraCoachingEnabled) {
                    answersData.forEach(answerState => {
                        answerState.observation_data = answerState.observation_data || { filler_events: [], camera_samples: [] };
                        answerState.observation_data.camera_unavailable_reason = cameraUnavailableReason;
                    });
                }
                const faceStatus = document.getElementById('stEyeContact');
                const alignmentStatus = document.getElementById('stPosture');
                const coachStatus = document.getElementById('cameraCoachStatus');
                if (faceStatus) {
                    faceStatus.textContent = 'Camera unavailable';
                    faceStatus.className = 'text-warning';
                }
                if (alignmentStatus) {
                    alignmentStatus.textContent = 'Not measured';
                    alignmentStatus.className = 'text-secondary';
                }
                if (coachStatus) {
                    coachStatus.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i>Not measured';
                    coachStatus.style.color = '#fbbf24';
                }
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
                                mobileVideo.closest('.mobile-camera-pip')?.classList.add('camera-ready');
                            }
                        })
                        .catch(function(err) {
                            console.error("Error accessing camera: ", err);
                            const reason = err && err.name === 'NotAllowedError'
                                ? 'permission_denied'
                                : (err && err.name === 'NotFoundError' ? 'device_unavailable' : 'camera_error');
                            markCameraUnavailable(reason);
                        });
                } else {
                    console.error("getUserMedia not supported");
                    markCameraUnavailable('browser_unsupported');
                }
            }
            
            async function trackBodyLanguage() {
                if (!cameraCoachingEnabled || cameraTrackingInFlight || typeof faceapi === 'undefined') return;
                const video = document.getElementById('userCamera');
                if (!video || !video.srcObject) return;
                const trackedQuestionIndex = currentQIdx;

                cameraTrackingInFlight = true;
                try {
                    const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                    const state = answersData[trackedQuestionIndex] || defaultAnswerState();
                    state.observation_data = state.observation_data || { filler_events: [], camera_samples: [] };
                    state.observation_data.camera_samples = Array.isArray(state.observation_data.camera_samples)
                        ? state.observation_data.camera_samples
                        : [];
                    let cameraFacing = false;
                    let centered = false;

                    if (detection) {
                        const leftEye = detection.landmarks.getLeftEye();
                        const rightEye = detection.landmarks.getRightEye();
                        const nose = detection.landmarks.getNose();
                        const centerOf = points => {
                            const total = points.reduce(
                                (point, current) => ({ x: point.x + current.x, y: point.y + current.y }),
                                { x: 0, y: 0 }
                            );
                            return { x: total.x / Math.max(1, points.length), y: total.y / Math.max(1, points.length) };
                        };
                        const leftCenter = centerOf(leftEye);
                        const rightCenter = centerOf(rightEye);
                        const eyeMidpoint = { x: (leftCenter.x + rightCenter.x) / 2, y: (leftCenter.y + rightCenter.y) / 2 };
                        const noseTip = nose[Math.min(3, Math.max(0, nose.length - 1))] || eyeMidpoint;
                        const eyeDistance = Math.max(1, Math.hypot(rightCenter.x - leftCenter.x, rightCenter.y - leftCenter.y));
                        cameraFacing = Math.abs((noseTip.x - eyeMidpoint.x) / eyeDistance) <= 0.32;

                        const box = detection.detection.box;
                        const videoWidth = Math.max(1, video.videoWidth || video.clientWidth || 1);
                        const videoHeight = Math.max(1, video.videoHeight || video.clientHeight || 1);
                        centered = Math.abs((box.x + (box.width / 2)) - (videoWidth / 2)) <= videoWidth * 0.24
                            && Math.abs((box.y + (box.height / 2)) - (videoHeight / 2)) <= videoHeight * 0.28;

                        document.getElementById('stEyeContact').innerHTML = '<i class="fa-solid fa-check me-1"></i>Visible';
                        document.getElementById('stEyeContact').className = 'text-success';
                        document.getElementById('stPosture').textContent = cameraFacing ? 'Camera-facing estimate' : 'Head turned estimate';
                        document.getElementById('stPosture').className = cameraFacing ? 'text-success' : 'text-warning';
                    } else {
                        document.getElementById('stEyeContact').textContent = 'Outside frame / unavailable';
                        document.getElementById('stEyeContact').className = 'text-warning';
                        document.getElementById('stPosture').textContent = 'Excluded from scoring';
                        document.getElementById('stPosture').className = 'text-secondary';
                    }

                    state.observation_data.camera_samples.push({
                        at_seconds: Math.max(0, Number(state.voice_duration || recTimerSeconds || 0)),
                        face_detected: Boolean(detection),
                        camera_facing: Boolean(detection && cameraFacing),
                        centered: Boolean(detection && centered)
                    });
                    state.observation_data.camera_samples = state.observation_data.camera_samples.slice(-300);
                    answersData[trackedQuestionIndex] = state;
                } catch(e) {
                    console.error("Tracking error", e);
                } finally {
                    cameraTrackingInFlight = false;
                }
            }

            let visualizerInterval = null;
            let currentAmplitude = 0.2;
            let preferredVoice = null;
            let autoStartAfterQuestionTimer = null;
            let questionSpeechToken = 0;
            let activeQuestionAudio = null;
            let captionInterval = null;
            let activeSpeechCompletion = null;
            let serverVoiceUnavailable = !serverAiVoiceEnabled;
            const serverSpeechUrlCache = new Map();

            function isVoiceTranscriptionMode() {
                return responseMode === 'voice' || responseMode === 'hybrid' || responseMode === 'voice_and_text';
            }

            function managedFetch(url, options = {}) {
                const controller = new AbortController();
                pendingFetchControllers.add(controller);

                return fetch(url, {
                    ...options,
                    signal: controller.signal
                }).finally(() => {
                    pendingFetchControllers.delete(controller);
                });
            }

            function abortManagedFetches() {
                pendingFetchControllers.forEach(controller => controller.abort());
                pendingFetchControllers.clear();
            }

            function scheduleAutoTranscriptionStart(token) {
                if (token !== questionSpeechToken) return;
                clearTimeout(autoStartAfterQuestionTimer);
                if (!isVoiceTranscriptionMode() || interviewEnding || interviewTerminated) return;

                autoStartAfterQuestionTimer = setTimeout(() => {
                    if (token !== questionSpeechToken || isRecording || interviewEnding || interviewTerminated) return;
                    startRecording({ silent: true });
                }, 450);
            }

            function speechLocalePriority() {
                if (speechLanguage === 'fil' || speechLanguage === 'tl') {
                    return ['fil-PH', 'tl-PH', 'fil', 'tl', 'en-PH', 'en-US', 'en'];
                }

                if (speechLanguage === 'ceb') {
                    return ['ceb-PH', 'ceb', 'fil-PH', 'tl-PH', 'fil', 'tl', 'en-PH', 'en-US', 'en'];
                }

                return [speechLocale, speechLanguage, 'en-PH', 'en-US', 'en'];
            }

            function voiceMatchesLanguage(voice, language) {
                const voiceLang = String(voice.lang || '').toLowerCase();
                const target = String(language || '').toLowerCase();
                return voiceLang === target || voiceLang.startsWith(target + '-') || target.startsWith(voiceLang + '-');
            }

            function voiceLooksNatural(voice) {
                return /google|premium|natural|siri|microsoft|enhanced/i.test(voice.name || '');
            }

            // Initialize preferred voice
            function loadVoices() {
                let voices = window.speechSynthesis.getVoices();
                if (voices.length > 0) {
                    const languagePriority = speechLocalePriority();
                    preferredVoice = languagePriority
                        .map(language => voices.find(v => voiceMatchesLanguage(v, language) && voiceLooksNatural(v)) || voices.find(v => voiceMatchesLanguage(v, language)))
                        .find(Boolean) || voices[0];
                }
            }
            if ('speechSynthesis' in window) {
                window.speechSynthesis.onvoiceschanged = loadVoices;
                loadVoices();
            }

            function clearCaptionInterval() {
                if (captionInterval) {
                    clearInterval(captionInterval);
                    captionInterval = null;
                }
            }

            function captionWordsFor(text) {
                const words = [];
                const pattern = /\S+/g;
                let match;

                while ((match = pattern.exec(String(text || ''))) !== null) {
                    words.push({
                        text: match[0],
                        start: match.index,
                        end: match.index + match[0].length,
                    });
                }

                return words;
            }

            function renderQuestionCaption(words, activeIndex) {
                const caption = document.getElementById('questionCaptionText');
                if (!caption) return;

                if (!words.length || activeIndex < 0) {
                    caption.classList.remove('has-caption');
                    caption.innerHTML = '';
                    return;
                }

                const safeIndex = Math.min(activeIndex, words.length - 1);
                const windowSize = 7;
                const start = Math.max(0, Math.min(safeIndex - Math.floor(windowSize / 2), Math.max(0, words.length - windowSize)));
                const visibleWords = words.slice(start, start + windowSize);

                caption.innerHTML = '';
                visibleWords.forEach((word, offset) => {
                    const span = document.createElement('span');
                    span.className = 'question-caption-word' + (start + offset === safeIndex ? ' active' : '');
                    span.textContent = word.text;
                    caption.appendChild(span);
                });
                caption.classList.add('has-caption');
            }

            function wordIndexFromChar(words, charIndex) {
                const safeChar = Number(charIndex) || 0;
                const found = words.findIndex(word => safeChar >= word.start && safeChar < word.end);
                if (found >= 0) return found;

                for (let idx = words.length - 1; idx >= 0; idx--) {
                    if (words[idx].start <= safeChar) return idx;
                }

                return 0;
            }

            function estimatedSpeechTimeoutMs(text) {
                const words = String(text || '').trim().split(/\s+/).filter(Boolean).length;
                return Math.max(4500, Math.min(60000, 2200 + (words * 620)));
            }

            function resolveSpeechCompletion(token, status = 'finished') {
                if (!activeSpeechCompletion || activeSpeechCompletion.token !== token) return;

                clearTimeout(activeSpeechCompletion.timeoutId);
                const resolve = activeSpeechCompletion.resolve;
                activeSpeechCompletion = null;
                resolve(status);
            }

            function resolveAnySpeechCompletion(status = 'cancelled') {
                if (!activeSpeechCompletion) return;

                clearTimeout(activeSpeechCompletion.timeoutId);
                const resolve = activeSpeechCompletion.resolve;
                activeSpeechCompletion = null;
                resolve(status);
            }

            function startSpeakingUi(text, boundaryAware = false) {
                clearCaptionInterval();
                document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'block');
                document.getElementById('aiAvatarHead').style.borderColor = '#34d399';
                document.getElementById('aiQuestionText').innerText = text;

                const words = captionWordsFor(text);
                let currentWordIdx = -1;
                let boundaryFired = false;
                renderQuestionCaption(words, currentWordIdx);

                captionInterval = setInterval(() => {
                    if (boundaryAware && boundaryFired) return;

                    if (currentWordIdx < words.length - 1) {
                        currentWordIdx++;
                        renderQuestionCaption(words, currentWordIdx);
                        currentAmplitude = 1.0;
                    } else {
                        clearCaptionInterval();
                    }
                }, 350);

                if (visualizerInterval) clearInterval(visualizerInterval);
                const bars = document.querySelectorAll('.spectrum-bar');
                visualizerInterval = setInterval(() => {
                    currentAmplitude = Math.max(0.15, currentAmplitude - 0.1);
                    bars.forEach(bar => {
                        let h = 6 + (Math.random() * 80 * currentAmplitude);
                        bar.style.height = h + 'px';
                    });
                }, 50);

                return {
                    markBoundary: (charIndex = null) => {
                        boundaryFired = true;
                        clearCaptionInterval();
                        currentWordIdx = charIndex === null
                            ? Math.min(currentWordIdx + 1, words.length - 1)
                            : wordIndexFromChar(words, charIndex);
                        renderQuestionCaption(words, currentWordIdx);
                    },
                };
            }

            function finishSpeakingUi(text, token, startTimerAfterSpeech) {
                if (token !== questionSpeechToken) return;

                document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'none');
                document.getElementById('aiAvatarHead').style.borderColor = '#8b5cf6';
                if (visualizerInterval) clearInterval(visualizerInterval);
                visualizerInterval = null;
                clearCaptionInterval();
                renderQuestionCaption([], -1);
                document.getElementById('aiQuestionText').innerText = text;
                if (startTimerAfterSpeech) {
                    startQuestionTimer();
                    scheduleAutoTranscriptionStart(token);
                }
                resolveSpeechCompletion(token);
            }

            function cancelQuestionAudio() {
                if (activeQuestionAudio) {
                    activeQuestionAudio.pause();
                    activeQuestionAudio.removeAttribute('src');
                    activeQuestionAudio.load();
                    activeQuestionAudio = null;
                }
            }

            function cancelQuestionSpeechOutput() {
                questionSpeechToken++;
                resolveAnySpeechCompletion();
                cancelQuestionAudio();
                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                }
                clearCaptionInterval();
                renderQuestionCaption([], -1);
                if (visualizerInterval) clearInterval(visualizerInterval);
                visualizerInterval = null;
                document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'none');
                const avatarHead = document.getElementById('aiAvatarHead');
                if (avatarHead) avatarHead.style.borderColor = '#8b5cf6';
            }

            async function serverSpeechUrl(questionId, speechText = '') {
                const cleanSpeechText = cleanTranscriptText(speechText);
                if ((!questionId && !cleanSpeechText) || serverVoiceUnavailable) return null;

                const cacheKey = questionId ? `q:${questionId}` : `text:${cleanSpeechText}`;
                if (serverSpeechUrlCache.has(cacheKey)) {
                    return serverSpeechUrlCache.get(cacheKey);
                }

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);
                if (questionId) {
                    formData.append('question_id', questionId);
                }
                if (cleanSpeechText) {
                    formData.append('speech_text', cleanSpeechText);
                }

                const response = await managedFetch('{{ route("interview.speech") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'audio/mpeg',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    if (response.status === 400 || response.status === 403 || response.status === 503) {
                        serverVoiceUnavailable = true;
                    }
                    return null;
                }

                const blob = await response.blob();
                if (!blob || blob.size === 0) return null;

                const url = URL.createObjectURL(blob);
                serverSpeechUrlCache.set(cacheKey, url);

                return url;
            }

            async function speakWithServerVoice(text, token, startTimerAfterSpeech, questionId, speechText = '') {
                if (!window.Audio || (!questionId && !speechText) || serverVoiceUnavailable || interviewTerminated) {
                    return false;
                }

                try {
                    const url = await serverSpeechUrl(questionId, speechText);
                    if (!url || token !== questionSpeechToken || interviewTerminated) return false;

                    const audio = new Audio(url);
                    activeQuestionAudio = audio;

                    audio.addEventListener('play', () => {
                        if (token !== questionSpeechToken) return;
                        startSpeakingUi(text);
                    }, { once: true });

                    audio.addEventListener('ended', () => {
                        activeQuestionAudio = null;
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    }, { once: true });

                    audio.addEventListener('error', () => {
                        activeQuestionAudio = null;
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    }, { once: true });

                    await audio.play();

                    return true;
                } catch (error) {
                    console.warn('Server AI voice unavailable, using device voice.', error);
                    cancelQuestionAudio();

                    return false;
                }
            }

            function speakWithDeviceVoice(text, token, startTimerAfterSpeech) {
                if (interviewTerminated) {
                    resolveSpeechCompletion(token, 'cancelled');
                    return;
                }

                if ('speechSynthesis' in window) {
                    window.speechSynthesis.cancel();
                    let utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = speechLocale;
                    if (preferredVoice) utterance.voice = preferredVoice;
                    utterance.rate = 0.95;
                    utterance.pitch = 1.0;

                    let speechUi = null;

                    utterance.onboundary = function(e) {
                        if(e.name === 'word') {
                            if (speechUi) speechUi.markBoundary(e.charIndex);

                            currentAmplitude = 1.0;
                        }
                    };

                    utterance.onstart = function() {
                        speechUi = startSpeakingUi(text, true);
                    };

                    utterance.onend = function() {
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    };

                    utterance.onerror = function() {
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    };

                    try {
                        window.speechSynthesis.speak(utterance);
                    } catch (error) {
                        console.warn('Device speech failed:', error);
                        finishSpeakingUi(text, token, startTimerAfterSpeech);
                    }
                } else {
                    document.getElementById('aiQuestionText').innerText = text;
                    if (startTimerAfterSpeech) startQuestionTimer();
                    if (startTimerAfterSpeech) scheduleAutoTranscriptionStart(token);
                    resolveSpeechCompletion(token);
                }
            }

            async function speakQuestion(text, options = {}) {
                if (interviewTerminated) return 'cancelled';

                cancelQuestionSpeechOutput();
                const token = ++questionSpeechToken;
                const startTimerAfterSpeech = options.startTimerAfterSpeech === true;
                const completion = new Promise(resolve => {
                    const timeoutId = setTimeout(() => {
                        if (token === questionSpeechToken) {
                            finishSpeakingUi(text, token, startTimerAfterSpeech);
                        }
                    }, estimatedSpeechTimeoutMs(text));
                    activeSpeechCompletion = { token, resolve, timeoutId };
                });

                if (isRecording) {
                    await pauseRecording();
                }

                const usedServerVoice = serverAiVoiceEnabled
                    ? await speakWithServerVoice(text, token, startTimerAfterSpeech, options.questionId, options.speechText || '')
                    : false;
                if (usedServerVoice || token !== questionSpeechToken || interviewTerminated) return completion;

                speakWithDeviceVoice(text, token, startTimerAfterSpeech);

                return completion;
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

            function captureTranscriptTimeline(eventName = 'progress', force = false, extra = {}) {
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
                    chars: text.length,
                    ...extra
                });
            }

            function handleAnswerPaste(event) {
                if (!answersData[currentQIdx]) return;

                const clipboard = event.clipboardData || window.clipboardData;
                const pastedText = clipboard ? (clipboard.getData('text') || clipboard.getData('Text') || '') : '';
                const pastedChars = pastedText.length;

                answersData[currentQIdx].paste_event_count = (answersData[currentQIdx].paste_event_count || 0) + 1;
                answersData[currentQIdx].pasted_character_count = (answersData[currentQIdx].pasted_character_count || 0) + pastedChars;

                setTimeout(() => {
                    captureTranscriptTimeline(pastedChars >= 80 ? 'large_paste' : 'paste', true, {
                        pasted_chars: pastedChars
                    });
                    triggerAnalysis();
                }, 0);
            }

            function handleQuestionTimeout() {
                clearInterval(questionTimerInterval);
                if (document.querySelector('.next-btn-class:disabled')) return;
                submitAnswer({ timedOut: true });
            }

            function scheduleStateSave() {
                if (interviewEnding || interviewTerminated) return;
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

            function questionSnapshot() {
                return questions.map(question => ({
                    id: question.id,
                    question_text: question.question_text,
                    source_name: question.source_name || '',
                    source_url: question.source_url || '',
                    source_type: question.source_type || ''
                }));
            }

            function naturalDelayFor(text, minimum = 2200, maximum = 5200) {
                const words = String(text || '').trim().split(/\s+/).filter(Boolean).length;
                return Math.max(minimum, Math.min(maximum, 900 + (words * 115)));
            }

            function pauseFor(ms) {
                return new Promise(resolve => setTimeout(resolve, Math.max(0, ms || 0)));
            }

            function waitForMinimumElapsed(startedAt, minimumMs) {
                const remaining = Math.max(0, minimumMs - (Date.now() - startedAt));
                return remaining > 0 ? pauseFor(remaining) : Promise.resolve();
            }

            function pluralizeQuestionCount() {
                return targetQuestionCount === 1 ? '1 question' : `${targetQuestionCount} questions`;
            }

            function isOpeningQuestion(question) {
                return question && question.source_type === 'real_interview_opening';
            }

            function hasOpeningQuestion() {
                return questions.length > 0 && isOpeningQuestion(questions[0]);
            }

            function questionDisplayNumber(idx) {
                return hasOpeningQuestion() ? idx : idx + 1;
            }

            function shouldIntroduceFirstScoredQuestion(idx, question, options = {}) {
                return options.append !== false
                    && !isOpeningQuestion(question)
                    && questionDisplayNumber(idx) === 1
                    && !displayedQuestionIds.has(firstQuestionIntroDisplayKey);
            }

            function isLastScoredQuestion(idx) {
                return questionDisplayNumber(idx) >= targetQuestionCount;
            }

            function isPenultimateScoredQuestion(idx) {
                return questionDisplayNumber(idx) >= targetQuestionCount - 1;
            }

            function candidateFirstName(answerText) {
                const clean = String(answerText || '').replace(/\s+/g, ' ').trim();
                const patterns = [
                    /\bmy name is\s+([A-Z][a-zA-Z'-]{1,30})\b/i,
                    /\bi am\s+([A-Z][a-zA-Z'-]{1,30})\b/i,
                    /\bi'm\s+([A-Z][a-zA-Z'-]{1,30})\b/i,
                    /^\s*([A-Z][a-zA-Z'-]{1,30})\b/
                ];
                const blockedNames = new Set(['i', 'im', "i'm", 'am', 'my', 'name', 'hello', 'hi', 'yes', 'no']);

                for (const pattern of patterns) {
                    const match = clean.match(pattern);
                    if (match && match[1]) {
                        const candidate = match[1].replace(/[^a-zA-Z'-]/g, '');
                        if (candidate.length > 1 && !blockedNames.has(candidate.toLowerCase())) {
                            return candidate.charAt(0).toUpperCase() + candidate.slice(1).toLowerCase();
                        }
                    }
                }

                return '';
            }

            function openingConversationText() {
                const modeLine = liveFeedbackMode === 'real_interview'
                    ? 'I will save feedback until the end.'
                    : 'I may ask follow-ups based on your answers.';

                return `Hi, I'm Mia, good to meet you. I will be your interviewer for the ${sessionTargetPosition} role. We have ${pluralizeQuestionCount()} today. ${modeLine} To begin, I would like to get to know you first.`;
            }

            function closingConversationText() {
                return `Thank you for walking me through your answers today. This ${sessionTargetPosition} interview is now complete, and your responses are being analyzed for feedback.`;
            }

            function setAnswerInputEnabled(enabled) {
                const textarea = document.getElementById('answerTextarea');
                if (textarea) textarea.disabled = !enabled;
                document.querySelectorAll('.next-btn-class').forEach(el => el.disabled = !enabled);
            }

            function showInterviewerConversation(text, counterText = null) {
                const qText = document.getElementById('aiQuestionText');
                if (qText) qText.innerText = text;

                const qCounter = document.getElementById('qCounter');
                if (qCounter && counterText) qCounter.innerText = counterText;

                const sourceLine = document.getElementById('aiQuestionSource');
                if (sourceLine) {
                    sourceLine.innerHTML = '';
                    sourceLine.style.display = 'none';
                }
            }

            async function beginOpeningConversation() {
                if (openingHasPlayed || interviewTerminated) {
                    await loadQuestion(currentQIdx, { append: true });
                    return;
                }

                openingHasPlayed = true;
                const introText = openingConversationText();
                setAnswerInputEnabled(false);
                appendChatMessage('interviewer', introText);
                showInterviewerConversation(introText, 'Intro');
                scheduleStateSave();
                await speakQuestion(introText, { startTimerAfterSpeech: false, phase: 'intro' });

                if (interviewTerminated) return;

                setAnswerInputEnabled(true);
                await loadQuestion(currentQIdx, { append: true });
            }

            async function playClosingConversationAndSubmit() {
                const closingText = closingConversationText();
                const closingStartedAt = Date.now();
                setAnswerInputEnabled(false);
                appendChatMessage('interviewer', closingText);
                showInterviewerConversation(closingText, 'Done');
                await speakQuestion(closingText, {
                    startTimerAfterSpeech: false,
                    phase: 'closing',
                    speechText: closingText
                });
                await waitForMinimumElapsed(closingStartedAt, naturalDelayFor(closingText, 3600, 7600));

                if (!interviewTerminated) {
                    finishInterview();
                }
            }

            async function concludeAndFinishInterview() {
                if (interviewEnding) return;

                if (isRecording) await stopRecording();
                interviewEnding = true;
                setAnswerInputEnabled(false);
                await playClosingConversationAndSubmit();
            }

            function enterMobileFullscreen() {
                if (!window.matchMedia('(max-width: 768px)').matches) return;
                document.body.classList.add('mobile-interview-fullscreen');
                updateMobileFullscreenToggle();

                const root = document.documentElement;
                if (!document.fullscreenElement && root.requestFullscreen) {
                    root.requestFullscreen({ navigationUI: 'hide' }).catch(() => {
                        updateMobileFullscreenToggle();
                    });
                }
            }

            function exitMobileFullscreen() {
                document.body.classList.remove('mobile-interview-fullscreen');
                updateMobileFullscreenToggle();

                if (document.fullscreenElement && document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {
                        updateMobileFullscreenToggle();
                    });
                }
            }

            function toggleMobileFullscreen() {
                if (document.body.classList.contains('mobile-interview-fullscreen')) {
                    exitMobileFullscreen();
                } else {
                    enterMobileFullscreen();
                }
            }

            function updateMobileFullscreenToggle() {
                const toggle = document.getElementById('responseFullscreenToggle');
                if (!toggle) return;

                const fullscreenOn = document.body.classList.contains('mobile-interview-fullscreen');
                toggle.title = fullscreenOn ? 'Exit fullscreen' : 'Enter fullscreen';
                toggle.setAttribute('aria-label', toggle.title);
                toggle.innerHTML = fullscreenOn
                    ? '<i class="fa-solid fa-compress"></i>'
                    : '<i class="fa-solid fa-expand"></i>';
            }

            function handleBrowserFullscreenChange() {
                if (!document.fullscreenElement && interviewStarted) {
                    document.body.classList.remove('mobile-interview-fullscreen');
                }

                updateMobileFullscreenToggle();
            }

            window.exitMobileFullscreen = exitMobileFullscreen;
            window.toggleMobileFullscreen = toggleMobileFullscreen;

            function startInterviewSession() {
                if (interviewStarted || interviewTerminated) return;
                
                interviewStarted = true;
                document.getElementById('introContainer').style.display = 'none';
                document.getElementById('workspaceWrapper').style.display = 'block';
                document.getElementById('workspaceWrapper').classList.toggle('real-interview-mode', liveFeedbackMode === 'real_interview');
                document.getElementById('interviewControls').style.opacity = '1';
                document.getElementById('interviewControls').style.pointerEvents = 'auto';
                
                if (cameraPreviewEnabled || cameraCoachingEnabled) initCamera();
                
                if(isVoiceTranscriptionMode()) {
                    document.getElementById('voiceControls').style.display = 'flex';
                    const transcriptionEngine = preferredTranscriptionEngine();
                    if (!transcriptionEngine) {
                        setTranscriptionStatus(transcriptionUnavailableMessage(), '#f87171');
                    } else if (transcriptionEngine === 'server') {
                        setTranscriptionStatus('Server transcription ready');
                    }
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
                (async () => {
                    if (!restoredChat && currentQIdx === 0 && !openingHasPlayed) {
                        await beginOpeningConversation();
                    } else {
                        await loadQuestion(currentQIdx, { append: !restoredChat });
                    }
                })();
                
                if (!answerListenersBound) {
                    answerListenersBound = true;
                    const answerTextarea = document.getElementById('answerTextarea');
                    answerTextarea.addEventListener('input', triggerAnalysis);
                    answerTextarea.addEventListener('paste', handleAnswerPaste);
                    document.getElementById('sessionNotes').addEventListener('input', scheduleStateSave);
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'hidden') autoSaveState();
                    });
                }
            }

            async function loadQuestion(idx, options = {}) {
                if (interviewTerminated || interviewEnding) return;
                currentQIdx = idx;
                const q = questions[idx];
                if (!q) return;
                setAnswerInputEnabled(false);
                
                document.getElementById('aiQuestionText').innerText = '...';
                document.getElementById('qCounter').innerText = isOpeningQuestion(q)
                    ? 'Intro'
                    : Math.min(questionDisplayNumber(idx), targetQuestionCount) + '/' + targetQuestionCount;
                updateQuestionSource(q);

                if (shouldIntroduceFirstScoredQuestion(idx, q, options)) {
                    appendChatMessage('interviewer', firstQuestionIntroText);
                    displayedQuestionIds.add(firstQuestionIntroDisplayKey);
                    showInterviewerConversation(firstQuestionIntroText, 'Q1');
                    await speakQuestion(firstQuestionIntroText, {
                        startTimerAfterSpeech: false,
                        phase: 'first_question_intro',
                        speechText: firstQuestionIntroText
                    });
                    if (interviewTerminated || interviewEnding) return;
                }

                // Append AI question to chat log if it's the first time seeing it
                const questionDisplayKey = String(q.id || idx);
                if (options.append !== false && !displayedQuestionIds.has(questionDisplayKey)) {
                    appendChatMessage('interviewer', q.question_text);
                    displayedQuestionIds.add(questionDisplayKey);
                }

                // Restore answer state if navigated back (though disabled in chat mode)
                document.getElementById('answerTextarea').value = answersData[idx] ? answersData[idx].text : '';
                const selfConfidenceRange = document.getElementById('selfConfidenceRange');
                const selfConfidenceValue = document.getElementById('selfConfidenceValue');
                if (selfConfidenceRange) {
                    selfConfidenceRange.value = answersData[idx]?.self_reported_confidence ?? 50;
                }
                if (selfConfidenceValue) {
                    selfConfidenceValue.textContent = (answersData[idx]?.self_reported_confidence ?? 50) + '%';
                }
                resetSpeechRecognitionBufferFromTextarea();
                lastTimelineCaptureAt = 0;
                
                await speakQuestion(q.question_text, { startTimerAfterSpeech: true, questionId: q.id });
                if (interviewTerminated || interviewEnding) return;
                setAnswerInputEnabled(true);
                
                triggerAnalysis();
                scheduleStateSave();
            }

            function updateQuestionSource(question) {
                const sourceLine = document.getElementById('aiQuestionSource');
                if (!sourceLine) return;

                const sourceType = question?.source_type || sourceLine.dataset.defaultType || '';
                const isAiAdapted = /ai|adapted|generated/i.test(sourceType);
                const name = trustedSourceDisplayName(question?.source_name || sourceLine.dataset.defaultName || '', isAiAdapted);
                const url = question?.source_url || sourceLine.dataset.defaultUrl || '';
                sourceLine.innerHTML = '';

                if (!name) {
                    sourceLine.style.display = 'none';
                    return;
                }

                sourceLine.style.display = 'flex';
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-link';
                const label = document.createElement('span');
                label.textContent = isAiAdapted ? 'AI-adapted from:' : 'Source:';
                const value = url ? document.createElement('a') : document.createElement('span');
                value.textContent = name;
                value.title = isAiAdapted
                    ? 'The question is AI-adapted from the same topic/content area, not copied word for word.'
                    : 'Trusted source used for this question set.';

                if (url) {
                    value.href = url;
                    value.target = '_blank';
                    value.rel = 'noopener';
                }

                sourceLine.append(icon, label, value);
            }

            function trustedSourceDisplayName(sourceName, isAiAdapted) {
                let cleanName = String(sourceName || '').trim();
                cleanName = cleanName.replace(/^User AI Generated\s*\([^)]+\)\s*via\s*/i, '');
                cleanName = cleanName.replace(/^AI Generated\s*\([^)]+\)\s*via\s*/i, '');
                cleanName = cleanName.replace(/^User AI Generated\s*\([^)]+\)\s*$/i, '');
                cleanName = cleanName.replace(/^AI Generated\s*\([^)]+\)\s*$/i, '');

                if (!cleanName && isAiAdapted) {
                    return 'trusted Philippines interview source';
                }

                return cleanName;
            }

            function repeatQuestion() {
                if(questions && questions[currentQIdx]) {
                    speakQuestion(questions[currentQIdx].question_text, { questionId: questions[currentQIdx].id });
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
            const fillerPattern = /\b(you know|i mean|sort of|kind of|um+|uh+|erm+|hmm+|like|basically|literally|actually)\b/gi;
            const unprofessionalPattern = /\b(whatever|stuff|things|idk|lol|yeah|nah|kinda|sorta)\b/gi;

            function canonicalFillerWord(word) {
                const normalized = String(word || '').toLowerCase().replace(/\s+/g, ' ').trim();
                if (/^um+$/.test(normalized)) return 'um';
                if (/^uh+$/.test(normalized)) return 'uh';
                if (/^erm+$/.test(normalized)) return 'erm';
                if (/^hmm+$/.test(normalized)) return 'hmm';
                return normalized;
            }

            function recordFillerEvents(segment) {
                const state = answersData[currentQIdx];
                if (!state || !isVoiceTranscriptionMode()) return;
                state.observation_data = state.observation_data || { filler_events: [], camera_samples: [] };
                state.observation_data.filler_events = Array.isArray(state.observation_data.filler_events)
                    ? state.observation_data.filler_events
                    : [];
                const matches = String(segment || '').matchAll(new RegExp(fillerPattern.source, 'gi'));
                for (const match of matches) {
                    state.observation_data.filler_events.push({
                        word: canonicalFillerWord(match[0]),
                        at_seconds: Math.max(0, Number(state.voice_duration || recTimerSeconds || 0))
                    });
                }
                state.observation_data.filler_events = state.observation_data.filler_events.slice(-500);
            }

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
                clarity -= longSentencePenalty;
                if (wordCount > 220) clarity -= 10;
                if (wordCount < 15) clarity = Math.min(clarity, 45);

                const relevance = calculateRelevanceScore(answerText, questionText, wordCount, starSignals);

                let grammar = 55 + Math.min(20, wordCount * 0.5) + (hasEndPunctuation ? 8 : 0);
                grammar -= hasRepeatedWord ? 8 : 0;
                grammar -= longSentencePenalty;
                if (wordCount < 15) grammar = Math.min(grammar, 50);

                let professionalism = 58 + (hasFirstPersonOwnership ? 10 : 0) + (starSignals.hasA ? 8 : 0) + (starSignals.hasR ? 8 : 0);
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
                    return 'The transcript detected several possible filler phrases. Try a brief silent pause when gathering your next thought.';
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

                const deliveryText = isVoiceTranscriptionMode()
                    ? String(answersData[currentQIdx]?.speech_transcript || '')
                    : '';
                const matches = deliveryText.match(fillerPattern);
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

            function setRecordingControlButtons(state) {
                const pauseBtn = document.getElementById('micPauseBtn');
                const stopBtn = document.getElementById('micStopBtn');
                const timer = document.getElementById('recordingTimer');

                if (pauseBtn) {
                    pauseBtn.style.display = 'inline-flex';
                    pauseBtn.innerHTML = state === 'paused'
                        ? '<i class="fa-solid fa-play"></i>'
                        : '<i class="fa-solid fa-pause"></i>';
                    pauseBtn.setAttribute('aria-label', state === 'paused' ? 'Resume recording' : 'Pause recording');
                    pauseBtn.setAttribute('title', state === 'paused' ? 'Resume recording' : 'Pause recording');
                }

                if (stopBtn) stopBtn.style.display = 'inline-flex';
                if (timer) timer.style.display = 'block';
            }

            async function startRecording(options = {}) {
                const silent = options && options.silent === true;
                if (isRecording) return true;

                let engine = preferredTranscriptionEngine();
                if (!engine) {
                    const message = transcriptionUnavailableMessage();
                    setTranscriptionStatus(message, '#f87171');
                    if(!silent) alert(message);
                    return false;
                }

                if (!isRecordingPaused) {
                    resetSpeechRecognitionBufferFromTextarea();
                }

                if (!await ensureMicrophoneReady(engine)) {
                    if(!silent) {
                        const message = document.getElementById('transcriptionStatus')?.textContent || transcriptionUnavailableMessage();
                        alert(message);
                    }
                    return false;
                }

                lastSpeechEnd = 0;
                shouldAutoRestartRecognition = true;
                isRecording = true;
                isRecordingPaused = false;
                activeTranscriptionEngine = engine;

                let started = engine === 'server'
                    ? startServerTranscriptionEngine()
                    : startSpeechRecognitionEngine();

                if (!started && engine === 'browser' && canUseServerTranscription()) {
                    activeTranscriptionEngine = 'server';
                    engine = 'server';
                    started = await ensureMicrophoneReady('server') && startServerTranscriptionEngine();
                }

                if (!started) {
                    shouldAutoRestartRecognition = false;
                    isRecording = false;
                    isRecordingPaused = false;
                    if(!silent) alert(document.getElementById('transcriptionStatus')?.textContent || transcriptionUnavailableMessage());
                    return false;
                }

                setRecordingControlButtons('recording');
                clearInterval(recTimerInterval);
                
                recTimerInterval = setInterval(() => {
                    recTimerSeconds++;
                    const m = Math.floor(recTimerSeconds / 60).toString().padStart(2, '0');
                    const s = (recTimerSeconds % 60).toString().padStart(2, '0');
                    document.getElementById('recordingTimer').innerText = m + ':' + s;
                    document.getElementById('vaDuration').innerText = recTimerSeconds + 's';
                    answersData[currentQIdx].voice_duration = recTimerSeconds;
                    
                    const wordCount = String(answersData[currentQIdx]?.speech_transcript || '').trim().split(/\s+/).filter(w=>w.length>0).length;
                    
                    // Match the server report: speech-transcript words divided
                    // by the browser-timed recording duration.
                    const timedSeconds = Math.max(1, recTimerSeconds);
                    const wpm = Math.round((wordCount / timedSeconds) * 60);
                    
                    document.getElementById('vaWpm').innerText = wpm;
                    answersData[currentQIdx].wpm = wpm;

                    // Optional camera-framing guidance is descriptive and never affects readiness scoring.
                    if (cameraCoachingEnabled && recTimerSeconds % 2 === 0) {
                        trackBodyLanguage();
                    }

                }, 1000);

                const scannerBox = document.getElementById('faceScannerBox');
                if (scannerBox) scannerBox.style.display = 'block';
                return true;
            }

            function toggleRecordingPause() {
                if (isRecording) {
                    pauseRecording();
                    return;
                }

                startRecording({ silent: false });
            }

            async function pauseRecording() {
                finalizeInterimTranscript();
                shouldAutoRestartRecognition = false;

                const usedServerTranscription = activeTranscriptionEngine === 'server';
                if(recognition && !usedServerTranscription) {
                    try {
                        recognition.stop();
                    } catch (error) {
                        console.error('Speech recognition failed to stop:', error);
                    }
                }
                isRecording = false;
                isRecordingPaused = true;
                clearInterval(recTimerInterval);
                setRecordingControlButtons('paused');
                const scannerBox = document.getElementById('faceScannerBox');
                if (scannerBox) scannerBox.style.display = 'none';

                if (usedServerTranscription) {
                    setTranscriptionStatus('Finalizing transcription', '#fbbf24');
                    await stopServerTranscriptionEngine();
                }

                if (!interviewEnding && !interviewTerminated) {
                    setTranscriptionStatus('Paused');
                }
            }

            async function stopRecording() {
                await pauseRecording();
                clearTimeout(autoStartAfterQuestionTimer);
                isRecordingPaused = false;
                recTimerSeconds = 0;
                const timer = document.getElementById('recordingTimer');
                if (timer) timer.innerText = '00:00';
                setRecordingControlButtons('recording');
                resetSpeechRecognitionBufferFromTextarea();
                setTranscriptionStatus('');
                return true;
            }

            function saveCurrentAnswer(isSkipped = false, timedOut = false) {
                if (interviewTerminated) return Promise.reject(new Error('Interview session has been terminated.'));
                stopQuestionTimer();
                captureTranscriptTimeline(timedOut ? 'timed_out_submit' : 'submitted', true);
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);
                formData.append('question_id', questions[currentQIdx].id);
                formData.append('answer_text', answersData[currentQIdx].text);
                formData.append('speech_transcript', answersData[currentQIdx].speech_transcript || '');
                formData.append('transcript_timeline', JSON.stringify(answersData[currentQIdx].transcript_timeline || []));
                formData.append('observation_data', JSON.stringify(answersData[currentQIdx].observation_data || {}));
                formData.append('paste_event_count', answersData[currentQIdx].paste_event_count || 0);
                formData.append('pasted_character_count', answersData[currentQIdx].pasted_character_count || 0);
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

                return managedFetch('{{ route("interview.answer") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Answer save failed with status ' + response.status);
                    }

                    return response.json();
                });
            }

            function autoSaveState() {
                if (interviewEnding || interviewTerminated) return Promise.resolve();
                if (answersData[currentQIdx]) {
                    answersData[currentQIdx].text = document.getElementById('answerTextarea').value;
                    answersData[currentQIdx].elapsed_seconds = getQuestionElapsedSeconds();
                }

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('notes', document.getElementById('sessionNotes').value);
                formData.append('duration_seconds', timerSeconds);
                formData.append('current_question_index', currentQIdx);
                const answersForAutosave = answersData.map((answer, index) => {
                    const snapshot = Object.assign({}, answer);
                    const observations = answer && answer.observation_data && typeof answer.observation_data === 'object'
                        ? answer.observation_data
                        : {};
                    snapshot.observation_data = index === currentQIdx
                        ? {
                            ...observations,
                            filler_events: Array.isArray(observations.filler_events) ? observations.filler_events.slice(-100) : [],
                            camera_samples: Array.isArray(observations.camera_samples) ? observations.camera_samples.slice(-120) : []
                        }
                        : { filler_events: [], camera_samples: [] };
                    return snapshot;
                });
                formData.append('session_state', JSON.stringify({
                    has_started: true,
                    currentQIdx,
                    timerSeconds,
                    openingHasPlayed,
                    questions: questionSnapshot(),
                    answersData: answersForAutosave,
                    chatHistory,
                    updated_at: new Date().toISOString()
                }));
                
                return managedFetch('{{ route("interview.saveState") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(() => {
                    if (interviewEnding || interviewTerminated) return;
                    const ind = document.getElementById('autoSaveIndicator');
                    ind.style.display = 'inline';
                    setTimeout(() => ind.style.display = 'none', 2000);
                }).catch(error => {
                    if (error.name !== 'AbortError') {
                        console.warn('Interview state auto-save failed:', error);
                    }
                });
            }

            function appendChatMessage(role, text, record = true) {
                const chatContainer = document.getElementById('chatTranscriptContainer');
                if (role === 'interviewer') {
                    chatContainer.innerHTML = '';
                }
                chatContainer.style.display = 'flex';

                const bubble = document.createElement('div');
                bubble.style.padding = '8px 10px';
                bubble.style.borderRadius = '12px';
                bubble.style.maxWidth = '96%';
                bubble.style.lineHeight = '1.35';
                bubble.style.fontSize = '0.76rem';
                
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

                if (record) {
                    chatHistory.push({ role, text });
                    if (chatHistory.length > 80) chatHistory = chatHistory.slice(chatHistory.length - 80);
                    scheduleStateSave();
                }
            }

            function placeNextQuestion(question) {
                const nextQuestionIndex = currentQIdx + 1;
                if (nextQuestionIndex < questions.length) {
                    questions[nextQuestionIndex] = question;
                } else {
                    questions.push(question);
                }

                while (answersData.length < questions.length) {
                    answersData.push(defaultAnswerState());
                }

                return nextQuestionIndex;
            }

            async function submitAnswer(options = {}) {
                if (isSubmittingAnswer || interviewEnding || interviewTerminated || finalAnswerSubmitted) return;
                if(isRecording) await stopRecording();
                
                const timedOut = options.timedOut === true;
                let answerText = document.getElementById('answerTextarea').value.trim();
                const wasSkipped = options.skipped === true || (timedOut && !answerText);

                if(!answerText && !timedOut && !wasSkipped) return alert("Please provide an answer before submitting.");
                if(!answerText && timedOut) {
                    answerText = "[Time expired with no answer]";
                    document.getElementById('answerTextarea').value = answerText;
                }

                isSubmittingAnswer = true;
                setAnswerInputEnabled(false);

                answersData[currentQIdx].text = answerText;
                answersData[currentQIdx].is_skipped = wasSkipped;
                answersData[currentQIdx].timed_out = timedOut;

                appendChatMessage('user', answerText);

                const answeredOpeningQuestion = isOpeningQuestion(questions[currentQIdx]);
                const isLastQuestion = !answeredOpeningQuestion && isLastScoredQuestion(currentQIdx);

                if (isLastQuestion) {
                    finalAnswerSubmitted = true;
                    try {
                        await saveCurrentAnswer(wasSkipped, timedOut);
                        isSubmittingAnswer = false;
                        await concludeAndFinishInterview();
                    } catch(error) {
                        console.error(error);
                        finalAnswerSubmitted = false;
                        isSubmittingAnswer = false;
                        if (!interviewTerminated) {
                            setAnswerInputEnabled(true);
                            alert('We could not save your final answer. Please try again before finishing.');
                        }
                    }
                    return;
                }

                stopQuestionTimer();
                captureTranscriptTimeline(timedOut ? 'timed_out_submit' : 'submitted', true);
                
                const chatContainer = document.getElementById('chatTranscriptContainer');
                chatContainer.style.display = 'flex';
                const thinkingBubble = document.createElement('div');
                thinkingBubble.id = 'thinkingBubble';
                thinkingBubble.style.padding = '8px 10px';
                thinkingBubble.style.borderRadius = '12px';
                thinkingBubble.style.maxWidth = '96%';
                thinkingBubble.style.fontSize = '0.76rem';
                thinkingBubble.style.lineHeight = '1.35';
                thinkingBubble.style.alignSelf = 'flex-start';
                thinkingBubble.style.background = 'rgba(255,255,255,0.05)';
                thinkingBubble.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-muted me-2"></i> <em>Interviewer is preparing the next question...</em>';
                chatContainer.appendChild(thinkingBubble);
                
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);
                formData.append('question_id', questions[currentQIdx].id);
                formData.append('answer_text', answerText);
                formData.append('speech_transcript', answersData[currentQIdx].speech_transcript || '');
                formData.append('conversation_context', JSON.stringify(chatHistory.slice(-16)));
                formData.append('transcript_timeline', JSON.stringify(answersData[currentQIdx].transcript_timeline || []));
                formData.append('observation_data', JSON.stringify(answersData[currentQIdx].observation_data || {}));
                formData.append('paste_event_count', answersData[currentQIdx].paste_event_count || 0);
                formData.append('pasted_character_count', answersData[currentQIdx].pasted_character_count || 0);
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
                formData.append('is_final_question', (!answeredOpeningQuestion && isPenultimateScoredQuestion(currentQIdx)));

                try {
                    const response = await managedFetch('{{ route("interview.chatReply") }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();
                    const tb = document.getElementById('thinkingBubble');
                    if(tb) tb.remove();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'An error occurred.');
                    }

                    if (data.interview_completed) {
                        finalAnswerSubmitted = true;
                        isSubmittingAnswer = false;
                        await concludeAndFinishInterview();
                        return;
                    }

                    const newQ = {
                        id: data.next_question_id,
                        question_text: data.next_question_text,
                        source_name: data.source_name || '',
                        source_url: data.source_url || '',
                        source_type: data.source_type || ''
                    };
                    const nextQuestionIndex = placeNextQuestion(newQ);
                    currentQIdx = nextQuestionIndex;
                    isSubmittingAnswer = false;
                    await loadQuestion(currentQIdx);
                } catch(err) {
                    const tb = document.getElementById('thinkingBubble');
                    if(tb) tb.remove();
                    isSubmittingAnswer = false;
                    console.error(err);
                    if (!interviewTerminated && err.name !== 'AbortError') {
                        setAnswerInputEnabled(true);
                        alert(err.message || "Network error.");
                    }
                }
            }

            async function skipQuestion() {
                if(isRecording) await stopRecording();
                document.getElementById('answerTextarea').value = "[User skipped the question]";
                submitAnswer({ skipped: true });
            }

            async function prevQuestion() {
                if(isRecording) await stopRecording();
                if (currentQIdx > 0) {
                    loadQuestion(currentQIdx - 1);
                }
            }

            function cleanupInterviewProcesses(options = {}) {
                const abortFetches = options.abortFetches === true;
                clearTimeout(autoStartAfterQuestionTimer);
                clearTimeout(stateSaveDebounce);
                clearInterval(timerInterval);
                clearInterval(questionTimerInterval);
                clearInterval(recTimerInterval);
                questionTimerInterval = null;
                timerInterval = null;
                recTimerInterval = null;
                questionStartedAt = null;
                shouldAutoRestartRecognition = false;

                if (recognition) {
                    try {
                        recognition.abort ? recognition.abort() : recognition.stop();
                    } catch (error) {
                        console.warn('Speech recognition cleanup failed:', error);
                    }
                }
                recognitionActive = false;
                if (serverTranscriptionRecorder && serverTranscriptionRecorder.state !== 'inactive') {
                    try {
                        serverTranscriptionRecorder.requestData();
                        serverTranscriptionRecorder.stop();
                    } catch (error) {
                        console.warn('Server transcription cleanup failed:', error);
                    }
                }
                serverTranscriptionRecorder = null;
                serverTranscriptionSessionToken++;
                serverTranscriptionQueue = [];
                serverTranscriptionProcessing = false;
                resolveServerTranscriptionDrain();
                isRecording = false;
                isRecordingPaused = false;
                releaseMicrophoneStream();
                setTranscriptionStatus('');
                cancelQuestionSpeechOutput();
                serverSpeechUrlCache.forEach(url => URL.revokeObjectURL(url));
                serverSpeechUrlCache.clear();
                ['userCamera', 'userCameraMobile'].forEach(id => {
                    let video = document.getElementById(id);
                    if (video && video.srcObject) {
                        video.srcObject.getTracks().forEach(track => track.stop());
                        video.srcObject = null;
                    }
                });

                if (abortFetches) {
                    abortManagedFetches();
                }
            }

            async function abortInterviewSession() {
                if (interviewTerminated) return;
                interviewTerminated = true;
                interviewEnding = true;
                finalAnswerSubmitted = true;
                setAnswerInputEnabled(false);
                cleanupInterviewProcesses({ abortFetches: true });

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('session_id', interviewSessionId);

                try {
                    const response = await fetch('{{ route("interview.abort") }}', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = response.ok ? await response.json() : {};
                    window.location.href = data.redirect_url || '{{ route("interview.setup") }}';
                } catch (error) {
                    console.error('Interview abort failed:', error);
                    window.location.href = '{{ route("interview.setup") }}';
                }
            }

            function waitForFeedbackRetry(delayMs) {
                return new Promise(resolve => setTimeout(resolve, Math.max(250, Math.min(2500, delayMs || 1000))));
            }

            function setFinishTransitionVisible(visible) {
                const overlay = document.getElementById('finishTransitionOverlay');
                if (!overlay) return;
                if (visible && overlay.parentElement !== document.body) {
                    document.body.appendChild(overlay);
                }
                overlay.classList.toggle('active', visible);
                document.body.classList.toggle('finish-transition-active', visible);
            }

            async function finishInterview() {
                if (feedbackSubmissionInFlight || interviewTerminated) return;
                feedbackSubmissionInFlight = true;
                cleanupInterviewProcesses();
                stopQuestionTimer();
                document.getElementById('formDuration').value = timerSeconds;
                document.getElementById('formNotes').value = document.getElementById('sessionNotes').value;
                const transitionMessage = document.getElementById('finishTransitionMessage');
                const retryButton = document.getElementById('finishRetryButton');
                if (transitionMessage) transitionMessage.textContent = 'Please wait while we finalize your interview report.';
                if (retryButton) retryButton.style.display = 'none';
                setFinishTransitionVisible(true);
                const form = document.getElementById('finishForm');
                const formData = new FormData(form);

                try {
                    for (let attempt = 0; attempt < 4; attempt++) {
                        const response = await managedFetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json().catch(() => ({}));

                        if (response.status === 409) {
                            await waitForFeedbackRetry(data.retry_after_ms);
                            continue;
                        }

                        if (!response.ok || !data.redirect_url) {
                            throw new Error(data.message || 'The feedback service returned an incomplete response.');
                        }

                        window.location.replace(data.redirect_url);
                        return;
                    }

                    throw new Error('The report is still processing. Please retry in a moment.');
                } catch (error) {
                    console.error('Interview feedback analysis failed:', error);
                    feedbackSubmissionInFlight = false;
                    const message = document.getElementById('finishTransitionMessage');
                    const retryButton = document.getElementById('finishRetryButton');
                    if (message) message.textContent = error.message || 'We could not finish the report. Your final answer is saved.';
                    if (retryButton) retryButton.style.display = 'inline-flex';
                    setFinishTransitionVisible(true);
                }
            }

            function retryFinishInterview() {
                if (!feedbackSubmissionInFlight) finishInterview();
            }

            function ucfirst(str) {
                if(!str) return '';
                return str.charAt(0).toUpperCase() + str.slice(1);
            }

            document.addEventListener('DOMContentLoaded', () => {
                updateMobileFullscreenToggle();
                document.addEventListener('fullscreenchange', handleBrowserFullscreenChange);

                setTimeout(() => {
                    if (!interviewStarted && !interviewTerminated) {
                        startInterviewSession();
                    }
                }, 350);
            });
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
    if (typeof cameraCoachingEnabled !== 'undefined' && cameraCoachingEnabled) {
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/'),
            faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/')
        ]).then(() => {
            console.log("Optional camera-framing models loaded");
        }).catch(err => {
            console.error("Error loading optional camera models", err);
            if (typeof markCameraUnavailable === 'function') markCameraUnavailable('model_unavailable');
        });
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
