@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Challenge')
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
    #sec-learning-game-session,
    #workspaceWrapper,
    #workspaceRow { min-width:0; }
    .panel { background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;margin-bottom:20px; }
    .panel-title { font-weight:700;margin-bottom:15px;display:flex;align-items:center;font-size:1rem;color:var(--tx); }
    .stat-row { display:flex;justify-content:space-between;margin-bottom:10px;font-size:.85rem;color:var(--tx2); }
    .progress-bar-bg { background:var(--bg3);height:8px;border-radius:4px;overflow:hidden;margin-bottom:15px; }
    .progress-bar-fill { background:#60a5fa;height:100%;transition:width 0.3s; }
    .star-item { display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:var(--bg3);border-radius:8px;margin-bottom:8px;font-size:.85rem; }
    .star-item i { font-size:1rem; }
    @keyframes scanAnim { 0% { top: 0%; opacity: 0.5; } 50% { top: 100%; opacity: 1; } 100% { top: 0%; opacity: 0.5; } }
    @keyframes avatarPulse { 0% { transform: scale(1); opacity: 0.8; } 100% { transform: scale(3.5); opacity: 0; } }
    .sound-wave { position:absolute;border-radius:50%;width:100%;height:100%;display:none; }

    /* Circular Audio Spectrum */
    .circular-spectrum { position: absolute; top: 50%; left: 50%; width: 0; height: 0; display: none; z-index: 5; }
    .circular-spectrum .spectrum-bar { position: absolute; bottom: 0; left: -4px; width: 8px; background: linear-gradient(to top, #8b5cf6, #34d399); border-radius: 4px; transform-origin: bottom center; height: 6px; transition: height 0.05s ease-out; box-shadow: 0 0 12px rgba(52,211,153,0.6); }
    .session-nav-row { display:flex;align-items:stretch;gap:8px;width:100%; }
    .session-nav-row .btn { min-height:38px;display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;min-width:0; }
    .session-nav-icon { flex:0 0 44px;padding-left:0 !important;padding-right:0 !important; }
    .session-nav-skip { flex:0 0 84px;padding-left:10px !important;padding-right:10px !important; }
    .session-nav-next { flex:1 1 auto;padding-left:14px !important;padding-right:14px !important; }
    .session-nav-next .next-label-short { display:none; }
    .hud-title-wrap { min-width:0; }
    .hud-title-row { min-width:0; }
    .hud-title { min-width:0;overflow-wrap:anywhere;line-height:1.25; }
    .hud-badges .badge { white-space:normal;text-align:center; }
    .ai-question-overlay { position:absolute;bottom:0;left:0;width:100%;background:linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.8) 70%, transparent 100%);padding:40px 20px 20px 20px; }
    .ai-question-wrap { display:flex;justify-content:space-between;align-items:flex-end;gap:12px; }
    .question-counter-badge {
        position:absolute;
        top:14px;
        right:14px;
        z-index:70;
        min-width:38px;
        height:38px;
        padding:0 9px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        border-radius:999px;
        background:#fff;
        color:#111827;
        font-size:0.86rem;
        font-weight:900;
        line-height:1;
        box-shadow:0 8px 22px rgba(0,0,0,0.28);
    }
    .answer-meta-row { gap:10px; }
    .challenge-finish-modal .modal-content {
        background: var(--sf);
        color: var(--tx);
        border: 1px solid var(--bd);
        border-radius: 18px;
        box-shadow: 0 24px 70px rgba(0,0,0,0.35);
    }
    .challenge-finish-modal .modal-body {
        padding: 30px;
        text-align: center;
    }
    .challenge-score-spinner {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        margin: 0 auto 18px;
        display: grid;
        place-items: center;
        background: rgba(52,211,153,0.12);
        color: #34d399;
        border: 1px solid rgba(52,211,153,0.28);
    }
    .challenge-score-spinner i { font-size: 1.8rem; }
    
    /* Responsive overrides */
    @media (max-width: 768px) {
        #sec-learning-game-session { padding-bottom: 18px !important; }
        #workspaceRow {
            --bs-gutter-y: 12px;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        #workspaceRow > [class*="col-"] {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        .hud-banner {
            padding: 14px !important;
            margin-bottom: 14px !important;
            border-radius: 14px !important;
            align-items: flex-start !important;
        }
        .hud-title-wrap { width:100%; }
        .hud-title-row {
            align-items: flex-start !important;
            flex-wrap: wrap;
        }
        .hud-title {
            font-size: 1.05rem !important;
            width: 100%;
        }
        .hud-badges {
            width:100%;
            justify-content:flex-start;
            gap:7px !important;
        }
        .hud-badges .badge {
            flex:1 1 126px;
            padding:7px 8px !important;
            font-size:0.78rem !important;
            line-height:1.25;
        }
        .avatar-wrapper { transform: scale(0.7); }
        .circular-spectrum { transform: scale(0.7); }
        .ai-avatar-panel {
            height: clamp(238px, 44vh, 310px) !important;
            border-radius: 14px !important;
            margin-bottom: 12px !important;
        }
        .ai-question-overlay { padding:52px 14px 14px !important; }
        .ai-question-wrap {
            align-items:flex-start !important;
            gap:8px !important;
        }
        #aiQuestionText {
            font-size:1rem !important;
            line-height:1.35 !important;
            max-height:112px !important;
        }
        .question-counter-badge {
            top:12px;
            right:12px;
            min-width:34px;
            height:34px;
            padding:0 8px;
            font-size:0.72rem !important;
        }
        .mobile-camera-preview {
            top:54px !important;
            right:12px !important;
            width:72px !important;
            height:94px !important;
        }
        .panel { padding: 15px; }
        .panel-title { font-size: 0.9rem; }
        #answerTextarea { min-height:160px !important; }
        #holdToTalkBtn {
            width:104px !important;
            height:104px !important;
        }
        .challenge-finish-modal .modal-dialog {
            margin: 12px;
            max-width: calc(100vw - 24px);
        }
        .challenge-finish-modal .modal-body { padding:24px 18px; }
        .challenge-score-spinner {
            width:58px;
            height:58px;
            margin-bottom:14px;
        }
    }

    @media (max-width: 420px) {
        .session-nav-row {
            display:grid;
            grid-template-columns: 38px 38px minmax(58px, 0.75fr) minmax(92px, 1.25fr);
            gap:6px;
        }
        .session-nav-row .btn { min-height:34px;font-size:0.82rem; }
        .session-nav-icon,
        .session-nav-skip,
        .session-nav-next { width:100%;flex-basis:auto; }
        .session-nav-next { padding-left:8px !important;padding-right:8px !important; }
        .session-nav-next .next-label-full { display:none; }
        .session-nav-next .next-label-short { display:inline; }
        .session-nav-next i { margin-left:0.35rem !important; }
        .answer-meta-row {
            flex-direction:column;
            align-items:flex-start !important;
            margin-bottom:14px !important;
        }
        .panel { border-radius:14px; }
    }

    @media (max-width: 340px) {
        .session-nav-row { gap:4px; }
        .session-nav-icon { font-size:0.78rem; }
        .session-nav-skip { font-size:0.76rem;padding-left:6px !important;padding-right:6px !important; }
        .session-nav-next { font-size:0.78rem; }
        .hud-badges .badge { flex-basis:100%; }
    }

    html body #sec-learning-game-session {
        --game-pro-radius: 8px;
        --game-pro-gap: 10px;
        --game-pro-card: rgba(255, 255, 255, 0.98);
        --game-pro-soft: #f8fafc;
        --game-pro-field: rgba(255, 255, 255, 0.96);
        --game-pro-border: rgba(15, 23, 42, 0.1);
        --game-pro-title: #0f172a;
        --game-pro-muted: #64748b;
        --game-pro-accent: #2563eb;
        --game-pro-danger: #dc2626;
        --game-pro-success: #15803d;
        --game-pro-warning: #b45309;
        --game-pro-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
        max-width: 520px;
        margin: 0 auto !important;
        padding-bottom: 16px !important;
        color: var(--game-pro-title);
    }

    html[data-theme="dark"] body #sec-learning-game-session,
    :root:not(.lm) body #sec-learning-game-session,
    .dm #sec-learning-game-session,
    body.dm #sec-learning-game-session {
        --game-pro-card: rgba(17, 24, 39, 0.98);
        --game-pro-soft: #162033;
        --game-pro-field: rgba(15, 23, 42, 0.92);
        --game-pro-border: rgba(148, 163, 184, 0.2);
        --game-pro-title: #f8fafc;
        --game-pro-muted: #9aa8bd;
        --game-pro-accent: #93c5fd;
        --game-pro-danger: #fca5a5;
        --game-pro-success: #86efac;
        --game-pro-warning: #fde68a;
        --game-pro-shadow: 0 1px 0 rgba(148, 163, 184, 0.08), 0 18px 36px rgba(0, 0, 0, 0.26);
    }

    html body #sec-learning-game-session #workspaceWrapper {
        width: 100% !important;
    }

    html body #sec-learning-game-session #workspaceRow {
        --bs-gutter-x: 0;
        --bs-gutter-y: var(--game-pro-gap);
        display: block !important;
        margin: 0 !important;
    }

    html body #sec-learning-game-session #workspaceRow > [class*="col-"] {
        width: 100% !important;
        max-width: none !important;
        padding: 0 !important;
    }

    html body #sec-learning-game-session :is(.hud-banner, .panel, .ai-avatar-panel, #gameSessionControls, .response-panel) {
        border: 1px solid var(--game-pro-border) !important;
        border-radius: var(--game-pro-radius) !important;
        background: var(--game-pro-card) !important;
        box-shadow: var(--game-pro-shadow) !important;
    }

    html body #sec-learning-game-session .hud-banner {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        gap: 10px !important;
        align-items: start !important;
        margin: 0 0 var(--game-pro-gap) !important;
        padding: 12px !important;
        background:
            linear-gradient(180deg, rgba(239, 246, 255, 0.78), var(--game-pro-card) 48%) !important;
        color: var(--game-pro-title) !important;
    }

    html[data-theme="dark"] body #sec-learning-game-session .hud-banner,
    :root:not(.lm) body #sec-learning-game-session .hud-banner,
    .dm #sec-learning-game-session .hud-banner {
        background:
            linear-gradient(180deg, rgba(30, 64, 175, 0.18), var(--game-pro-card) 48%) !important;
    }

    html body #sec-learning-game-session .hud-title-row {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        gap: 7px !important;
        align-items: start !important;
        margin-bottom: 6px !important;
    }

    html body #sec-learning-game-session .hud-title {
        color: var(--game-pro-title) !important;
        -webkit-text-fill-color: var(--game-pro-title) !important;
        font-size: 1rem !important;
        line-height: 1.14 !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
        text-transform: uppercase !important;
    }

    html body #sec-learning-game-session .hud-title-wrap > div:not(.hud-title-row) {
        color: var(--game-pro-muted) !important;
        font-size: 0.78rem !important;
        line-height: 1.38 !important;
    }

    html body #sec-learning-game-session .hud-badges {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 6px !important;
        width: 100% !important;
    }

    html body #sec-learning-game-session .hud-badges .badge,
    html body #sec-learning-game-session .hud-title-row > .badge,
    html body #sec-learning-game-session .session-chip {
        min-height: 30px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 5px !important;
        padding: 6px 8px !important;
        border: 1px solid var(--game-pro-border) !important;
        border-radius: 999px !important;
        background: var(--game-pro-soft) !important;
        color: var(--game-pro-title) !important;
        font-size: 0.64rem !important;
        font-weight: 900 !important;
        line-height: 1.1 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    html body #sec-learning-game-session .ai-avatar-panel {
        height: 208px !important;
        margin: 0 0 var(--game-pro-gap) !important;
        padding: 0 !important;
        overflow: hidden !important;
        border-color: rgba(147, 197, 253, 0.32) !important;
        background: #020617 !important;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.2) !important;
    }

    html body #sec-learning-game-session #aiAvatarContainer {
        background:
            radial-gradient(circle at 50% 42%, rgba(96, 165, 250, 0.34), transparent 26%),
            linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0f766e 100%) !important;
    }

    html body #sec-learning-game-session #aiAvatarHead.avatar-wrapper {
        width: 88px !important;
        height: 88px !important;
        transform: none !important;
    }

    html body #sec-learning-game-session .circular-spectrum {
        transform: scale(0.72) !important;
        opacity: 0.88;
    }

    html body #sec-learning-game-session .question-counter-badge {
        top: 12px !important;
        left: 12px !important;
        right: auto !important;
        min-width: 0 !important;
        min-height: 28px !important;
        height: auto !important;
        padding: 5px 8px !important;
        border: 1px solid rgba(255, 255, 255, 0.28) !important;
        border-radius: 999px !important;
        background: rgba(255, 255, 255, 0.94) !important;
        color: #0f172a !important;
        font-size: 0.64rem !important;
        font-weight: 900 !important;
        box-shadow: none !important;
    }

    html body #sec-learning-game-session .interviewer-panel-badge {
        position: absolute !important;
        top: 12px !important;
        left: 62px !important;
        z-index: 58 !important;
        max-width: calc(100% - 132px) !important;
        padding: 5px 8px !important;
        border: 1px solid rgba(255, 255, 255, 0.22) !important;
        border-radius: 999px !important;
        background: rgba(15, 23, 42, 0.42) !important;
        color: #ffffff !important;
        font-size: 0.56rem !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    html body #sec-learning-game-session .ai-question-overlay {
        inset: auto 10px 10px 10px !important;
        width: auto !important;
        padding: 0 !important;
        background: transparent !important;
        z-index: 58 !important;
    }

    html body #sec-learning-game-session .ai-question-overlay .game-coach-badge {
        display: none !important;
    }

    html body #sec-learning-game-session .ai-question-wrap {
        display: block !important;
    }

    html body #sec-learning-game-session #aiQuestionText {
        min-height: 32px !important;
        max-height: 86px !important;
        width: 100% !important;
        padding: 7px 9px !important;
        border: 1px solid rgba(255, 255, 255, 0.16) !important;
        border-radius: var(--game-pro-radius) !important;
        background: rgba(15, 23, 42, 0.78) !important;
        color: #f8fafc !important;
        -webkit-text-fill-color: #f8fafc !important;
        font-size: 0.68rem !important;
        line-height: 1.3 !important;
        font-weight: 750 !important;
        box-shadow: none !important;
        overflow-y: auto !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    html body #sec-learning-game-session #gameSessionControls {
        position: sticky !important;
        top: calc(var(--mob-top-h, 56px) + var(--mob-safe-top, 0px) + 8px) !important;
        z-index: 14 !important;
        display: grid !important;
        grid-template-columns: 38px 38px minmax(70px, 0.82fr) minmax(108px, 1.18fr) !important;
        gap: 8px !important;
        margin: 0 0 var(--game-pro-gap) !important;
        padding: 8px !important;
        background: color-mix(in srgb, var(--game-pro-card) 92%, transparent) !important;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
    }

    html body #sec-learning-game-session #gameSessionControls .btn {
        min-width: 0 !important;
        min-height: 38px !important;
        width: 100% !important;
        padding: 8px 9px !important;
        border-radius: 6px !important;
        font-size: 0.68rem !important;
        font-weight: 900 !important;
        line-height: 1.1 !important;
        letter-spacing: 0 !important;
        white-space: nowrap !important;
        box-shadow: none !important;
    }

    html body #sec-learning-game-session #gameSessionControls .btn i {
        font-size: 0.72rem !important;
    }

    html body #sec-learning-game-session #gameSessionControls .btn-outline-info {
        border-color: rgba(14, 165, 233, 0.28) !important;
        background: rgba(14, 165, 233, 0.1) !important;
        color: #0369a1 !important;
    }

    html[data-theme="dark"] body #sec-learning-game-session #gameSessionControls .btn-outline-info,
    :root:not(.lm) body #sec-learning-game-session #gameSessionControls .btn-outline-info,
    .dm #sec-learning-game-session #gameSessionControls .btn-outline-info {
        color: #7dd3fc !important;
    }

    html body #sec-learning-game-session #gameSessionControls .btn-outline-secondary {
        border-color: var(--game-pro-border) !important;
        background: var(--game-pro-field) !important;
        color: var(--game-pro-title) !important;
    }

    html body #sec-learning-game-session #gameSessionControls .btn-outline-warning {
        border-color: rgba(245, 158, 11, 0.26) !important;
        background: rgba(254, 243, 199, 0.8) !important;
        color: var(--game-pro-warning) !important;
    }

    html[data-theme="dark"] body #sec-learning-game-session #gameSessionControls .btn-outline-warning,
    :root:not(.lm) body #sec-learning-game-session #gameSessionControls .btn-outline-warning,
    .dm #sec-learning-game-session #gameSessionControls .btn-outline-warning {
        background: rgba(120, 53, 15, 0.24) !important;
    }

    html body #sec-learning-game-session #gameSessionControls .session-nav-next {
        background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
        color: #ffffff !important;
        border-color: transparent !important;
    }

    html body #sec-learning-game-session .response-panel {
        margin: 0 0 var(--game-pro-gap) !important;
        padding: 12px !important;
    }

    html body #sec-learning-game-session .response-panel .panel-title,
    html body #sec-learning-game-session .panel .panel-title {
        display: grid !important;
        grid-template-columns: 30px minmax(0, 1fr) auto !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 0 0 10px !important;
        color: var(--game-pro-title) !important;
        -webkit-text-fill-color: var(--game-pro-title) !important;
        font-size: 0.88rem !important;
        font-weight: 900 !important;
        line-height: 1.15 !important;
        letter-spacing: 0 !important;
    }

    html body #sec-learning-game-session .panel-title > i {
        width: 30px !important;
        height: 30px !important;
        margin: 0 !important;
        border-radius: var(--game-pro-radius) !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: #dbeafe !important;
        color: #2563eb !important;
        font-size: 0.78rem !important;
    }

    html[data-theme="dark"] body #sec-learning-game-session .panel-title > i,
    :root:not(.lm) body #sec-learning-game-session .panel-title > i,
    .dm #sec-learning-game-session .panel-title > i {
        background: rgba(59, 130, 246, 0.2) !important;
        color: #93c5fd !important;
    }

    html body #sec-learning-game-session .panel-title-text {
        min-width: 0 !important;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    html body #sec-learning-game-session .response-title-actions {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
    }

    html body #sec-learning-game-session .game-mode-badge {
        min-height: 28px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        padding: 6px 8px !important;
        border-radius: 999px !important;
        background: rgba(239, 68, 68, 0.1) !important;
        color: var(--game-pro-danger) !important;
        border: 1px solid rgba(239, 68, 68, 0.24) !important;
        font-size: 0.58rem !important;
        font-weight: 900 !important;
    }

    html body #sec-learning-game-session #voiceControls {
        width: 100% !important;
        margin: 0 0 10px !important;
        padding: 10px !important;
        border: 1px solid rgba(14, 165, 233, 0.2) !important;
        border-radius: var(--game-pro-radius) !important;
        background: rgba(14, 165, 233, 0.08) !important;
    }

    html body #sec-learning-game-session #voiceControls > .d-flex:first-child {
        margin-bottom: 8px !important;
    }

    html body #sec-learning-game-session #voiceControls #recordingTimer {
        min-width: 52px !important;
        min-height: 34px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        margin: 0 !important;
        padding: 0 8px !important;
        border: 1px solid rgba(239, 68, 68, 0.22) !important;
        border-radius: 6px !important;
        background: rgba(254, 242, 242, 0.88) !important;
        color: var(--game-pro-danger) !important;
        font-size: 0.66rem !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
    }

    html[data-theme="dark"] body #sec-learning-game-session #voiceControls #recordingTimer,
    :root:not(.lm) body #sec-learning-game-session #voiceControls #recordingTimer,
    .dm #sec-learning-game-session #voiceControls #recordingTimer {
        background: rgba(127, 29, 29, 0.22) !important;
    }

    html body #sec-learning-game-session #holdToTalkBtn {
        width: 104px !important;
        height: 104px !important;
        border-radius: 999px !important;
        font-size: 0.76rem !important;
        box-shadow: 0 10px 22px rgba(239, 68, 68, 0.24) !important;
    }

    html body #sec-learning-game-session #answerTextarea,
    html body #sec-learning-game-session #sessionNotes {
        width: 100% !important;
        padding: 10px !important;
        border: 1px solid var(--game-pro-border) !important;
        border-radius: var(--game-pro-radius) !important;
        background: var(--game-pro-field) !important;
        color: var(--game-pro-title) !important;
        -webkit-text-fill-color: currentColor !important;
        caret-color: var(--game-pro-title) !important;
        font-size: 0.78rem !important;
        line-height: 1.42 !important;
        box-shadow: none !important;
    }

    html body #sec-learning-game-session #answerTextarea {
        min-height: 132px !important;
        max-height: 190px !important;
        margin-bottom: 8px !important;
        resize: vertical !important;
    }

    html body #sec-learning-game-session #sessionNotes {
        min-height: 104px !important;
        resize: vertical !important;
    }

    html body #sec-learning-game-session #answerTextarea::placeholder,
    html body #sec-learning-game-session #sessionNotes::placeholder {
        color: var(--game-pro-muted) !important;
        -webkit-text-fill-color: var(--game-pro-muted) !important;
        opacity: 0.78 !important;
    }

    html body #sec-learning-game-session .response-count-bar {
        min-height: 30px !important;
        margin: 0 !important;
        padding: 7px 9px !important;
        border: 1px solid var(--game-pro-border) !important;
        border-radius: var(--game-pro-radius) !important;
        background: var(--game-pro-soft) !important;
        color: var(--game-pro-muted) !important;
        font-size: 0.64rem !important;
        font-weight: 800 !important;
        line-height: 1.2 !important;
    }

    html body #sec-learning-game-session #autoSaveIndicator {
        color: var(--game-pro-success) !important;
        font-weight: 900 !important;
    }

    html body #sec-learning-game-session .col-lg-4 .panel {
        margin: 0 0 var(--game-pro-gap) !important;
        padding: 12px !important;
    }

    html body #sec-learning-game-session .stat-row,
    html body #sec-learning-game-session .star-item {
        min-height: 30px !important;
        margin: 0 !important;
        padding: 7px 0 !important;
        border-bottom: 1px solid var(--game-pro-border) !important;
        background: transparent !important;
        color: var(--game-pro-muted) !important;
        font-size: 0.68rem !important;
        line-height: 1.28 !important;
    }

    html body #sec-learning-game-session .stat-row:last-child,
    html body #sec-learning-game-session .star-item:last-of-type {
        border-bottom: 0 !important;
    }

    html body #sec-learning-game-session .star-item span,
    html body #sec-learning-game-session .stat-row span:first-child {
        color: var(--game-pro-muted) !important;
    }

    html body #sec-learning-game-session .stat-row span:last-child {
        color: var(--game-pro-title) !important;
        font-weight: 850 !important;
    }

    html body #sec-learning-game-session #overallReadiness {
        color: var(--game-pro-success) !important;
        font-size: 1.5rem !important;
        line-height: 1 !important;
    }

    html body #sec-learning-game-session #coachingTip,
    html body #sec-learning-game-session .panel div[style*="251,191,36"] {
        border: 1px solid rgba(245, 158, 11, 0.24) !important;
        border-radius: var(--game-pro-radius) !important;
        background: rgba(254, 243, 199, 0.72) !important;
        color: var(--game-pro-warning) !important;
        font-size: 0.68rem !important;
        line-height: 1.32 !important;
    }

    html[data-theme="dark"] body #sec-learning-game-session #coachingTip,
    :root:not(.lm) body #sec-learning-game-session #coachingTip,
    .dm #sec-learning-game-session #coachingTip,
    html[data-theme="dark"] body #sec-learning-game-session .panel div[style*="251,191,36"],
    :root:not(.lm) body #sec-learning-game-session .panel div[style*="251,191,36"],
    .dm #sec-learning-game-session .panel div[style*="251,191,36"] {
        background: rgba(120, 53, 15, 0.24) !important;
    }

    html body #sec-learning-game-session #get-ready-overlay {
        background:
            radial-gradient(circle at 50% 22%, rgba(37, 99, 235, 0.18), transparent 30%),
            rgba(2, 6, 23, 0.92) !important;
        color: #ffffff !important;
    }

    html body #sec-learning-game-session #get-ready-overlay h2,
    html body #sec-learning-game-session #get-ready-overlay p {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    html body #sec-learning-game-session .challenge-finish-modal .modal-content {
        border: 1px solid var(--game-pro-border) !important;
        border-radius: var(--game-pro-radius) !important;
        background: var(--game-pro-card) !important;
        color: var(--game-pro-title) !important;
        box-shadow: 0 20px 44px rgba(2, 6, 23, 0.28) !important;
    }

    @media (max-width: 991px) {
        body.user-mobile-shell #mob-content #sec-learning-game-session {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 0 max(14px, var(--mob-safe-bottom, 0px)) !important;
            overflow-x: clip !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session,
        body.user-mobile-shell #mob-content #sec-learning-game-session * {
            min-width: 0;
            max-width: 100%;
            box-sizing: border-box;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session #workspaceWrapper {
            width: 100% !important;
            overflow-x: clip !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session #workspaceRow {
            display: flex !important;
            flex-direction: column !important;
            gap: var(--game-pro-gap) !important;
            width: 100% !important;
            margin: 0 !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session #workspaceRow > [class*="col-"] {
            flex: 0 0 auto !important;
            width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .hud-banner {
            width: 100% !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .hud-badges .badge,
        body.user-mobile-shell #mob-content #sec-learning-game-session .hud-title-row > .badge,
        body.user-mobile-shell #mob-content #sec-learning-game-session .game-mode-badge {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .ai-avatar-panel {
            width: 100% !important;
            height: clamp(190px, 34dvh, 238px) !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .mobile-camera-preview {
            top: 48px !important;
            right: 10px !important;
            width: clamp(58px, 18vw, 72px) !important;
            height: clamp(74px, 24vw, 94px) !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session #gameSessionControls {
            top: calc(var(--mob-top-h, 56px) + var(--mob-safe-top, 0px) + 6px) !important;
            grid-template-columns: 36px 36px minmax(54px, 0.72fr) minmax(86px, 1.28fr) !important;
            gap: 6px !important;
            width: 100% !important;
            padding: 7px !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session #gameSessionControls .btn {
            min-height: 34px !important;
            padding: 7px 6px !important;
            font-size: 0.6rem !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .session-nav-next .next-label-full {
            display: none !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .session-nav-next .next-label-short {
            display: inline !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .response-panel {
            width: 100% !important;
            padding: 11px !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .response-panel .panel-title {
            grid-template-columns: 30px minmax(0, 1fr) minmax(0, auto) !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .response-title-actions {
            max-width: 120px !important;
            justify-self: end !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session #answerTextarea {
            min-height: clamp(112px, 23dvh, 154px) !important;
            max-height: 34dvh !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session #voiceControls {
            padding: 9px !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session #holdToTalkBtn {
            width: clamp(88px, 28vw, 104px) !important;
            height: clamp(88px, 28vw, 104px) !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .col-lg-4 {
            display: flex !important;
            flex-direction: column !important;
            gap: var(--game-pro-gap) !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .col-lg-4 .panel {
            width: 100% !important;
            margin: 0 !important;
        }

        body.user-mobile-shell #mob-content #sec-learning-game-session .challenge-finish-modal .modal-dialog {
            width: calc(100vw - 24px) !important;
            max-width: calc(100vw - 24px) !important;
            margin: 12px auto !important;
        }
    }

    @media (max-width: 390px) {
        html body #sec-learning-game-session .ai-avatar-panel {
            height: 192px !important;
        }

        html body #sec-learning-game-session #aiAvatarHead.avatar-wrapper {
            width: 80px !important;
            height: 80px !important;
        }

        html body #sec-learning-game-session .interviewer-panel-badge {
            left: 58px !important;
            max-width: calc(100% - 116px) !important;
            font-size: 0.52rem !important;
        }

        html body #sec-learning-game-session #gameSessionControls {
            grid-template-columns: 34px 34px minmax(58px, 0.78fr) minmax(84px, 1.22fr) !important;
            gap: 6px !important;
        }

        html body #sec-learning-game-session #gameSessionControls .btn {
            min-height: 34px !important;
            padding: 7px 7px !important;
            font-size: 0.58rem !important;
        }

        html body #sec-learning-game-session #answerTextarea {
            min-height: 112px !important;
            font-size: 0.74rem !important;
        }
    }

    @media (max-width: 360px) {
        html body #sec-learning-game-session .hud-badges {
            grid-template-columns: 1fr !important;
        }

        html body #sec-learning-game-session #gameSessionControls .session-nav-next i {
            display: none !important;
        }
    }

    @media (min-width: 992px) {
        html body #sec-learning-game-session {
            --game-desktop-radius: 12px;
            --game-desktop-gap: 12px;
            --game-desktop-border: rgba(148, 163, 184, 0.2);
            --game-desktop-card-shadow: 0 10px 28px rgba(2, 6, 23, 0.12);
            width: 100% !important;
            max-width: 1480px !important;
            margin: 0 auto !important;
            padding: 0 0 24px !important;
        }

        html.lm body #sec-learning-game-session {
            --game-desktop-border: rgba(15, 23, 42, 0.12);
            --game-desktop-card-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        }

        html body #sec-learning-game-session .hud-banner {
            grid-template-columns: minmax(0, 1fr) auto !important;
            margin: 0 0 var(--game-desktop-gap) !important;
            padding: 14px !important;
            border-radius: var(--game-desktop-radius) !important;
        }

        html body #sec-learning-game-session .hud-title {
            font-size: clamp(1.12rem, 1.08vw, 1.45rem) !important;
            line-height: 1.12 !important;
        }

        html body #sec-learning-game-session .hud-badges {
            width: auto !important;
            grid-template-columns: repeat(3, max-content) !important;
        }

        html body #sec-learning-game-session #workspaceRow {
            --bs-gutter-x: 0 !important;
            --bs-gutter-y: 0 !important;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) clamp(330px, 34%, 470px) !important;
            gap: var(--game-desktop-gap) !important;
            align-items: start !important;
            margin: 0 !important;
        }

        html body #sec-learning-game-session #workspaceRow > [class*="col-"] {
            width: auto !important;
            max-width: none !important;
            min-width: 0 !important;
            padding: 0 !important;
        }

        html body #sec-learning-game-session #workspaceRow > .col-lg-8 {
            grid-column: 1 / 2 !important;
        }

        html body #sec-learning-game-session #workspaceRow > .col-lg-4 {
            grid-column: 2 / 3 !important;
        }

        html body #sec-learning-game-session :is(.hud-banner, .panel, .ai-avatar-panel, #gameSessionControls, .response-panel) {
            border-radius: var(--game-desktop-radius) !important;
            border-color: var(--game-desktop-border) !important;
            box-shadow: var(--game-desktop-card-shadow) !important;
        }

        html body #sec-learning-game-session .ai-avatar-panel {
            height: 260px !important;
            margin: 0 0 var(--game-desktop-gap) !important;
        }

        html body #sec-learning-game-session #aiAvatarHead.avatar-wrapper {
            width: 96px !important;
            height: 96px !important;
        }

        html body #sec-learning-game-session .circular-spectrum {
            transform: scale(0.82) !important;
        }

        html body #sec-learning-game-session .ai-question-overlay {
            inset: auto 14px 14px 14px !important;
        }

        html body #sec-learning-game-session #aiQuestionText {
            min-height: 34px !important;
            padding: 8px 10px !important;
            border-radius: 10px !important;
            font-size: 0.76rem !important;
            line-height: 1.32 !important;
        }

        html body #sec-learning-game-session #gameSessionControls {
            position: static !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin: 0 0 var(--game-desktop-gap) !important;
            padding: 8px !important;
            border-radius: var(--game-desktop-radius) !important;
        }

        html body #sec-learning-game-session #gameSessionControls .btn {
            min-height: 32px !important;
            padding: 6px 10px !important;
            border-radius: 8px !important;
            font-size: 0.68rem !important;
            font-weight: 850 !important;
            line-height: 1 !important;
        }

        html body #sec-learning-game-session #gameSessionControls .session-nav-icon {
            flex: 0 0 42px !important;
        }

        html body #sec-learning-game-session #gameSessionControls .session-nav-skip {
            flex: 0 0 94px !important;
        }

        html body #sec-learning-game-session #gameSessionControls .session-nav-next {
            flex: 1 1 auto !important;
        }

        html body #sec-learning-game-session .response-panel {
            margin: 0 !important;
            padding: 14px !important;
            border-radius: var(--game-desktop-radius) !important;
        }

        html body #sec-learning-game-session .response-panel .panel-title,
        html body #sec-learning-game-session .panel .panel-title {
            font-size: 0.9rem !important;
        }

        html body #sec-learning-game-session #answerTextarea {
            min-height: 148px !important;
            border-radius: 10px !important;
            font-size: 0.78rem !important;
            line-height: 1.42 !important;
        }

        html body #sec-learning-game-session .response-count-bar {
            border-radius: 9px !important;
            font-size: 0.66rem !important;
        }

        html body #sec-learning-game-session .col-lg-4 {
            display: flex !important;
            flex-direction: column !important;
            gap: var(--game-desktop-gap) !important;
        }

        html body #sec-learning-game-session .col-lg-4 .panel {
            margin: 0 !important;
            padding: 14px !important;
            border-radius: var(--game-desktop-radius) !important;
        }

        html body #sec-learning-game-session #cameraPanel {
            position: sticky !important;
            top: 12px !important;
        }
    }

    @media (min-width: 992px) and (max-width: 1320px) {
        html body #sec-learning-game-session #workspaceRow {
            grid-template-columns: minmax(0, 1fr) clamp(300px, 31%, 380px) !important;
        }

        html body #sec-learning-game-session .ai-avatar-panel {
            height: 238px !important;
        }

        html body #sec-learning-game-session #answerTextarea {
            min-height: 132px !important;
        }
    }
</style>

<div class="db-section active" id="sec-learning-game-session">
    @if(session('active_game_session_id'))
        @php
            $sessionRecord = \App\Models\GameSession::with('level')
                ->where('user_id', auth()->id())
                ->find(session('active_game_session_id'));
            if ($sessionRecord) {
                $cameraCoachingEnabled = (bool) data_get($sessionRecord->accommodation_profile, 'camera_coaching', false);
                $num = $sessionRecord->num_questions ?? count($sessionRecord->questions ?? []);
                $questions = collect($sessionRecord->questions ?? [])->values()->map(function ($questionText, $index) {
                    return (object) [
                        'id' => $index,
                        'question_index' => $index,
                        'question_text' => $questionText,
                    ];
                });
            } else {
                $questions = collect([]);
            }
        @endphp

        @if($sessionRecord && $questions->count() > 0)
        <style>
            #get-ready-overlay {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.85);
                z-index: 9999;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                color: #fff;
                backdrop-filter: blur(10px);
            }
            #countdown-text {
                font-size: 6rem;
                font-weight: 900;
                background: linear-gradient(135deg, var(--pur) 0%, #34d399 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                animation: pulse 1s infinite;
            }
            .hud-banner {
                background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(52,211,153,0.1) 100%);
                border: 1px solid var(--pur);
                border-radius: 18px;
                padding: 15px 25px;
                margin-bottom: 25px;
                box-shadow: 0 4px 20px rgba(59,130,246,0.15);
            }
        </style>

        <!-- Get Ready Overlay -->
        <div id="get-ready-overlay">
            <h2 style="font-weight:800;text-transform:uppercase;margin-bottom:10px;color:var(--tx)">Level {{ $gameLevel->level_number }}</h2>
            <h1 id="countdown-text">3</h1>
            <p style="font-weight:600;color:var(--tx3);margin-top:20px;">Prepare your mic...</p>
        </div>

        <!-- HUD Banner -->
        <div class="hud-banner d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="hud-title-wrap">
                <div class="hud-title-row d-flex align-items-center gap-2 mb-1">
                    <span class="badge" style="background:var(--pur);color:#fff;font-size:0.8rem;"><i class="fa-solid fa-gamepad me-1"></i> PH CHALLENGE</span>
                    <h4 class="hud-title" style="font-size:1.4rem;font-weight:800;margin:0;color:var(--tx)">Level {{ $gameLevel->level_number }}: {{ $gameLevel->title }}</h4>
                </div>
                @if($gameLevel->learning_objective)
                    <div style="font-size:0.86rem;color:var(--tx2);line-height:1.45;max-width:760px;">{{ $gameLevel->learning_objective }}</div>
                @endif

            </div>
            
            <div class="hud-badges d-flex flex-wrap gap-2 align-items-center">
                @if($gameLevel->time_limit_seconds)
                    <div class="badge" style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid #ef4444;padding:8px 12px;font-size:0.9rem;">
                        <i class="fa-solid fa-stopwatch me-1"></i> <span id="game-timer">{{ $gameLevel->time_limit_seconds }}s</span>
                    </div>
                @endif
                <div class="badge" style="background:rgba(52,211,153,0.1);color:#34d399;border:1px solid #34d399;padding:8px 12px;font-size:0.9rem;">
                    <i class="fa-solid fa-bullseye me-1"></i> Goal: {{ $gameLevel->required_score }}%+
                </div>
                <div class="badge" style="background:rgba(59,130,246,0.1);color:#60a5fa;border:1px solid #60a5fa;padding:8px 12px;font-size:0.9rem;">
                    <i class="fa-solid fa-clock me-1"></i> <span id="challengeTimer">00:00</span>
                </div>

            </div>
        </div>

        <div id="workspaceWrapper" style="display:none;">
        <div class="row g-4 m-0" id="workspaceRow">
            <!-- Main Content Area -->
            <div class="col-lg-8 px-0 pe-lg-3">
                <!-- Progress Tracker Removed by User -->

                <!-- Simulated AI Video Avatar Panel -->
                <div class="panel p-0 ai-avatar-panel" style="overflow:hidden;border:1px solid var(--bd);background:#000;position:relative;height:250px;border-radius:18px;margin-bottom:20px;">
                    <span class="question-counter-badge" id="qCounter">1/10</span>
                    <span class="badge interviewer-panel-badge"><i class="fa-solid fa-bolt me-1"></i> {{ $sessionRecord->company_persona ?? 'AI Coach' }}</span>
                    @if($cameraCoachingEnabled)
                    <!-- Optional mobile camera framing preview -->
                    <div class="mobile-camera-preview d-block d-lg-none" style="position:absolute; top:15px; right:15px; width:80px; height:105px; border-radius:8px; overflow:hidden; border:2px solid rgba(255,255,255,0.3); z-index:50; box-shadow: 0 4px 15px rgba(0,0,0,0.6);">
                        <video id="userCameraMobile" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);background:#222;"></video>
                    </div>
                    @endif

                    <div id="aiAvatarContainer" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background: linear-gradient(135deg, #1e1b4b, #312e81);">
                        <div class="avatar-wrapper" id="aiAvatarHead" style="width:100px;height:100px;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;transition:border-color 0.3s;">
                            <!-- The Image Container (with border, glow, and clipping for the image itself) -->
                            <div style="width:100%;height:100%;background:rgba(255,255,255,0.1);border-radius:50%;border:3px solid #8b5cf6;overflow:hidden;position:relative;z-index:10;box-shadow: 0 0 15px rgba(139,92,246,0.3);">
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
                    <div class="ai-question-overlay">
                        <div class="ai-question-wrap">
                            <div style="width: 100%;">
                                <span class="badge mb-2 game-coach-badge" style="background:var(--pur);color:white;font-size:0.75rem;"><i class="fa-solid fa-bolt me-1"></i> {{ $sessionRecord->company_persona ?? 'AI Coach' }}</span>
                                <div id="aiQuestionText" class="custom-scrollbar" style="color:white;font-size:1.1rem;font-weight:600;line-height:1.4; max-height: 90px; overflow-y: auto; padding-right: 10px;">Loading your first question...</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unified challenge controls -->
                <div class="session-nav-row" id="gameSessionControls">
                    <button type="button" class="btn btn-outline-info session-nav-icon" onclick="repeatQuestion()" aria-label="Repeat question" title="Repeat question"><i class="fa-solid fa-volume-high"></i></button>
                    <button type="button" class="btn btn-outline-secondary session-nav-icon prev-btn-class" onclick="prevQuestion()" disabled aria-label="Previous question" title="Previous question"><i class="fa-solid fa-arrow-left"></i></button>
                    <button type="button" class="btn btn-outline-warning session-nav-skip skip-btn-class" onclick="skipQuestion()">Skip <i class="fa-solid fa-forward-step ms-1"></i></button>
                    <button type="button" class="bgrd btn session-nav-next next-btn-class text-white" onclick="submitAnswer()"><span class="next-label-full">Next Question</span><span class="next-label-short">Next</span><i class="fa-solid fa-arrow-right ms-2"></i></button>
                </div>

                <!-- Answer Response System -->
                <div class="panel response-panel mb-4">
                    <div class="panel-title">
                        <i class="fa-solid fa-pen-nib me-2"></i>
                        <span class="panel-title-text">Your Response</span>
                        <div class="response-title-actions">
                            @if($sessionRecord->game_level_id)
                                <span class="badge game-mode-badge"><i class="fa-solid fa-gamepad me-1"></i> CHALLENGE MODE</span>
                            @endif
                        </div>
                    </div>
                    
                    <form id="answerForm">
                        <div id="voiceControls" style="display:none;margin-bottom:20px;background:rgba(59,130,246,.05);padding:15px;border-radius:12px;border:1px solid rgba(59,130,246,.2)">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div style="font-weight:600;font-size:.9rem;color:#60a5fa"><i class="fa-solid fa-waveform me-2"></i>Voice Recording</div>
                                <span id="recordingTimer" style="font-family:monospace;font-size:1.1rem;color:#f87171;display:none;">00:00</span>
                            </div>
                            
                            @if($sessionRecord->game_level_id)
                            <div class="d-flex justify-content-center py-3">
                                <button type="button" id="holdToTalkBtn" class="btn btn-danger" style="width:120px; height:120px; border-radius:50%; font-weight:800; border:4px solid #b91c1c; box-shadow: 0 10px 20px rgba(239,68,68,0.4); display:flex; flex-direction:column; align-items:center; justify-content:center; user-select:none; touch-action:manipulation;">
                                    <i class="fa-solid fa-microphone fa-2x mb-2"></i>
                                    HOLD
                                </button>
                            </div>
                            @else
                            <div class="d-flex gap-2">
                                <button type="button" id="micStartBtn" class="btn btn-primary" onclick="startRecording()"><i class="fa-solid fa-microphone me-2"></i>Start</button>
                                <button type="button" id="micPauseBtn" class="btn btn-warning" onclick="pauseRecording()" style="display:none;"><i class="fa-solid fa-pause me-2"></i>Pause</button>
                                <button type="button" id="micStopBtn" class="btn btn-danger" onclick="stopRecording()" style="display:none;"><i class="fa-solid fa-stop me-2"></i>Stop</button>
                            </div>
                            @endif
                        </div>

                        <textarea id="answerTextarea" class="oinp mb-2" style="min-height:200px;font-size:.95rem" placeholder="Type your answer here, or use voice to auto-transcribe..."></textarea>
                        
                        <div class="answer-meta-row response-count-bar d-flex justify-content-between align-items-center mb-4">
                            <div style="font-size:.8rem;color:var(--tx3)">
                                <span id="wordCount">0 words</span> • <span id="charCount">0 characters</span>
                                <span id="autoSaveIndicator" class="ms-3 text-success" style="display:none;"><i class="fa-solid fa-check me-1"></i>Auto-saved</span>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Side Panels -->
            <div class="col-lg-4 px-0 ps-lg-3">
                <!-- Session Navigation (Mobile fallback / Overview) -->
                @if($cameraCoachingEnabled)
                <!-- Optional descriptive body-language coach; never used in readiness scoring. -->
                <div class="panel d-none d-lg-block" id="cameraPanel">
                    <div class="panel-title"><i class="fa-solid fa-camera-web me-2"></i> Optional Body-Language Coach</div>
                    <div style="position:relative;background:#000;height:180px;border-radius:12px;margin-bottom:15px;overflow:hidden;display:flex;align-items:center;justify-content:center">
                        <video id="userCamera" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);"></video>
                        <div class="face-scanner-box" id="faceScannerBox" style="display:none;position:absolute;width:120px;height:120px;border:2px solid #34d399;border-radius:12px;box-shadow:0 0 15px rgba(52,211,153,0.3);transition:all 0.3s ease;">
                            <div class="scan-line" style="width:100%;height:2px;background:#34d399;position:absolute;top:0;animation: scanAnim 2s infinite linear;box-shadow:0 0 8px #34d399;"></div>
                        </div>
                        <div style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.6);padding:2px 8px;border-radius:4px;font-size:.7rem;color:#34d399"><i class="fa-solid fa-circle text-success pulse-anim" style="font-size:.5rem;margin-right:4px"></i> Private Preview</div>
                    </div>
                    <div class="stat-row"><span>Face in frame</span><span id="stEyeContact">Waiting</span></div>
                    <div class="stat-row"><span>Hands / gestures</span><span id="stGesture">Waiting</span></div>
                    <div class="stat-row"><span>Shoulders / posture</span><span id="stPose">Waiting</span></div>
                    <div class="stat-row"><span>Movement steadiness</span><span id="stMovement">Waiting</span></div>
                    <div class="stat-row"><span>Head alignment</span><span id="stPosture">Optional - not scored</span></div>
                </div>
                @endif

                @php $successChecklist = $gameLevel->guidance_checklist; @endphp
                @if($gameLevel->skill_focus || $gameLevel->learning_objective || $successChecklist || $gameLevel->retry_hint)
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-bullseye me-2"></i> Challenge Brief</div>
                    @if($gameLevel->skill_focus)
                        <div class="badge mb-3" style="background:rgba(56,189,248,0.12);color:#38bdf8;border:1px solid rgba(56,189,248,0.35);padding:8px 10px;font-size:0.82rem;">
                            <i class="fa-solid fa-graduation-cap me-1"></i> {{ $gameLevel->skill_focus }}
                        </div>
                    @endif
                    @if($gameLevel->learning_objective)
                        <div style="font-size:0.84rem;color:var(--tx2);line-height:1.5;margin-bottom:14px;">{{ $gameLevel->learning_objective }}</div>
                    @endif
                    @if($successChecklist)
                        <div style="font-size:0.78rem;color:var(--tx3);font-weight:700;margin-bottom:8px;text-transform:uppercase;">Success checklist</div>
                        <div class="d-flex flex-column gap-2 mb-3">
                            @foreach($successChecklist as $criterion)
                                <div style="display:flex;gap:8px;align-items:flex-start;font-size:0.82rem;color:var(--tx2);line-height:1.4;">
                                    <i class="fa-solid fa-check" style="color:#34d399;margin-top:2px;"></i>
                                    <span>{{ $criterion }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if($gameLevel->retry_hint)
                        <div style="font-size:0.82rem;color:#fbbf24;background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.28);border-radius:8px;padding:10px;line-height:1.45;">
                            <i class="fa-solid fa-lightbulb me-1"></i>{{ $gameLevel->retry_hint }}
                        </div>
                    @endif
                </div>
                @endif

                <!-- AI Visualizer Panel -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-chart-pie me-2"></i> AI Visualizer</div>
                    <div class="text-center mb-3">
                        <div style="font-size:2rem;font-weight:700;color:#34d399" id="overallReadiness">--%</div>
                        <div style="font-size:.75rem;color:var(--tx3)">Practice coverage · not a readiness score</div>
                    </div>
                    <div class="stat-row"><span>Clarity</span><span id="metClarity">--%</span></div>
                    <div class="stat-row"><span>Relevance</span><span id="metRelevance">--%</span></div>
                    <div class="stat-row"><span>Grammar</span><span id="metGrammar">--%</span></div>
                    <div class="stat-row mb-0"><span>Professionalism</span><span id="metProf">--%</span></div>
                </div>

                <!-- STAR Framework Analyzer -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-star me-2" style="color:#fbbf24"></i> STAR Analyzer</div>
                    <div class="star-item"><span>Situation</span><i class="fa-solid fa-circle-xmark text-danger" id="starS"></i></div>
                    <div class="star-item"><span>Task</span><i class="fa-solid fa-circle-xmark text-danger" id="starT"></i></div>
                    <div class="star-item"><span>Action</span><i class="fa-solid fa-circle-xmark text-danger" id="starA"></i></div>
                    <div class="star-item"><span>Result</span><i class="fa-solid fa-circle-xmark text-danger" id="starR"></i></div>
                    <div style="margin-top:10px;font-size:.8rem;color:#fbbf24;background:rgba(251,191,36,.1);padding:10px;border-radius:8px;border:1px solid rgba(251,191,36,.3)" id="coachingTip">
                        <i class="fa-solid fa-lightbulb me-1"></i> <strong>Coach:</strong> Start typing to get real-time analysis!
                    </div>
                </div>

                <!-- Voice Analytics Module -->
                <div class="panel" id="voiceAnalyticsPanel" style="display:none;">
                    <div class="panel-title"><i class="fa-solid fa-wave-square me-2"></i> Voice Analytics</div>
                    <div class="stat-row"><span>Speaking Duration</span><span id="vaDuration">0s</span></div>
                    <div class="stat-row"><span>Speed (WPM)</span><span id="vaWpm">0</span></div>
                    <div class="stat-row mb-0"><span>Filler Words (Um, Uh)</span><span id="vaFillers" class="text-danger">0</span></div>
                </div>

                <!-- Challenge Notes -->
                <div class="panel">
                    <div class="panel-title"><i class="fa-solid fa-clipboard me-2"></i> Challenge Notes</div>
                    <textarea id="sessionNotes" class="oinp" style="min-height:100px;font-size:.85rem;padding:10px" placeholder="Private notes, key reminders, etc..."></textarea>
                </div>
            </div>
        </div>
        </div>

        <!-- Intro container removed for automatic start via get-ready overlay -->

        <form id="finishForm" action="{{ route('user.game.finish') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="game_session_id" value="{{ $sessionRecord->id }}">
            <input type="hidden" name="duration_seconds" id="formDuration">
            <input type="hidden" name="notes" id="formNotes">
        </form>

        <div class="modal fade challenge-finish-modal" id="challengeFinishModal" tabindex="-1" aria-labelledby="challengeFinishModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="challenge-score-spinner">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                        </div>
                        <h5 id="challengeFinishModalTitle" style="font-weight:900;margin-bottom:8px;color:var(--tx);">Scoring Challenge</h5>
                        <p id="challengeFinishStatus" style="margin:0;color:var(--tx2);line-height:1.5;">Saving your final answer...</p>
                        <div style="margin-top:16px;font-size:0.8rem;color:var(--tx3);">Your result modal will open automatically after scoring.</div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const questions = {!! json_encode($questions) !!};
            const gameSessionId = {{ (int) $sessionRecord->id }};
            const responseMode = "{{ $sessionRecord->response_mode }}";
            const cameraCoachingEnabled = @json($cameraCoachingEnabled);
            let currentQIdx = 0;
            let timerSeconds = 0;
            let timerInterval;
            let isFinishingChallenge = false;
            
            // Answers state
            let answersData = Array(questions.length).fill().map(() => ({
                text: '',
                is_skipped: false,
                wpm: 0,
                voice_duration: 0,
                filler_words: 0,
                pause_count: 0,
                confidence_score: 0,
                eye_contact_score: 0,
                posture_score: 0
            }));

            // Voice state and optional, non-scoring body-language state
            let recognition = null;
            let recognitionActive = false;
            let shouldAutoRestartRecognition = false;
            let isRecording = false;
            let recTimerSeconds = 0;
            let recTimerInterval;
            window.bodyLanguageModelState = window.bodyLanguageModelState || { ready: false, failed: false, poseLandmarker: null, handLandmarker: null };
            let gameCameraMovementBaseline = null;
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
                if (!cameraCoachingEnabled) return;
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
            function setGameCameraStat(id, content, className = 'text-secondary', asHtml = false) {
                const element = document.getElementById(id);
                if (!element) return;
                if (asHtml) {
                    element.innerHTML = content;
                } else {
                    element.textContent = content;
                }
                element.className = className;
            }

            function gameVisibleLandmark(landmark, threshold = 0.35) {
                if (!landmark || !Number.isFinite(Number(landmark.x)) || !Number.isFinite(Number(landmark.y))) return false;
                return Number(landmark.visibility ?? landmark.presence ?? 1) >= threshold;
            }

            function gameCenterOf(points) {
                const usable = points.filter(point => point && Number.isFinite(Number(point.x)) && Number.isFinite(Number(point.y)));
                if (usable.length === 0) return null;
                const total = usable.reduce(
                    (point, current) => ({ x: point.x + current.x, y: point.y + current.y }),
                    { x: 0, y: 0 }
                );
                return { x: total.x / usable.length, y: total.y / usable.length };
            }

            function gamePointDistance(left, right) {
                if (!left || !right) return null;
                return Math.hypot(Number(left.x) - Number(right.x), Number(left.y) - Number(right.y));
            }

            function gameDetectVideoFrame(landmarker, video, timestamp) {
                if (!landmarker || typeof landmarker.detectForVideo !== 'function') return null;
                try {
                    return landmarker.detectForVideo(video, timestamp);
                } catch (error) {
                    return landmarker.detectForVideo(video);
                }
            }

            async function trackBodyLanguage() {
                const bodyLanguageState = window.bodyLanguageModelState || {};
                const canUseBodyModels = Boolean(bodyLanguageState.ready && bodyLanguageState.poseLandmarker && bodyLanguageState.handLandmarker);
                const canUseFaceModel = typeof faceapi !== 'undefined';
                if (!cameraCoachingEnabled || (!canUseBodyModels && !canUseFaceModel)) return;
                const video = document.getElementById('userCamera');
                if (!video || !video.srcObject) return;

                try {
                    let detection = null;
                    if (canUseFaceModel) {
                        try {
                            detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
                        } catch (faceError) {
                            console.error("Face framing tracking error", faceError);
                        }
                    }

                    let poseLandmarks = null;
                    let handLandmarks = [];
                    if (canUseBodyModels) {
                        const timestamp = performance.now();
                        const poseResult = gameDetectVideoFrame(bodyLanguageState.poseLandmarker, video, timestamp);
                        const handResult = gameDetectVideoFrame(bodyLanguageState.handLandmarker, video, timestamp);
                        poseLandmarks = Array.isArray(poseResult?.landmarks) && poseResult.landmarks.length > 0
                            ? poseResult.landmarks[0]
                            : null;
                        handLandmarks = Array.isArray(handResult?.landmarks)
                            ? handResult.landmarks.slice(0, 2)
                            : [];
                    }

                    const poseDetected = Array.isArray(poseLandmarks) && poseLandmarks.length > 0;
                    const faceVisible = Boolean(detection || (poseDetected && gameVisibleLandmark(poseLandmarks[0])));
                    let shouldersVisible = false;
                    let shouldersLevel = null;
                    let uprightPosture = null;
                    let poseCameraFacing = null;
                    const movementPoints = {};

                    if (poseDetected) {
                        const nose = poseLandmarks[0];
                        const leftShoulder = poseLandmarks[11];
                        const rightShoulder = poseLandmarks[12];
                        const leftHip = poseLandmarks[23];
                        const rightHip = poseLandmarks[24];
                        const noseVisible = gameVisibleLandmark(nose);
                        shouldersVisible = gameVisibleLandmark(leftShoulder) && gameVisibleLandmark(rightShoulder);
                        const hipsVisible = gameVisibleLandmark(leftHip) && gameVisibleLandmark(rightHip);
                        const shoulderMidpoint = shouldersVisible ? gameCenterOf([leftShoulder, rightShoulder]) : null;
                        const hipMidpoint = hipsVisible ? gameCenterOf([leftHip, rightHip]) : null;
                        const shoulderWidth = shouldersVisible ? Math.max(0.01, gamePointDistance(leftShoulder, rightShoulder) ?? 0.01) : 0.01;
                        if (noseVisible) movementPoints.nose = { x: nose.x, y: nose.y };
                        if (shoulderMidpoint) {
                            movementPoints.shoulders = shoulderMidpoint;
                            shouldersLevel = Math.abs(Number(leftShoulder.y) - Number(rightShoulder.y)) <= 0.065;
                        }
                        if (noseVisible && shoulderMidpoint) {
                            poseCameraFacing = Math.abs((Number(nose.x) - shoulderMidpoint.x) / shoulderWidth) <= 0.38;
                            uprightPosture = Math.abs((Number(nose.x) - shoulderMidpoint.x) / shoulderWidth) <= 0.45;
                        }
                        if (shoulderMidpoint && hipMidpoint) {
                            const torsoHeight = Math.max(0.01, Math.abs(hipMidpoint.y - shoulderMidpoint.y));
                            uprightPosture = Math.abs((shoulderMidpoint.x - hipMidpoint.x) / torsoHeight) <= 0.28;
                        }
                    }

                    const handCenters = handLandmarks
                        .map(hand => gameCenterOf(Array.isArray(hand) ? hand : []))
                        .filter(Boolean);
                    handCenters.forEach((center, index) => {
                        movementPoints['hand' + index] = center;
                    });

                    let movementScore = null;
                    let gestureActive = false;
                    if (gameCameraMovementBaseline && Object.keys(movementPoints).length > 0) {
                        const distances = Object.entries(movementPoints)
                            .map(([key, point]) => gamePointDistance(point, gameCameraMovementBaseline[key]))
                            .filter(distance => Number.isFinite(distance));
                        if (distances.length > 0) {
                            movementScore = Math.min(100, Math.round((distances.reduce((total, distance) => total + distance, 0) / distances.length) * 650));
                        }
                        gestureActive = handCenters.some((center, index) => {
                            const distance = gamePointDistance(center, gameCameraMovementBaseline['hand' + index]);
                            return Number.isFinite(distance) && distance >= 0.045;
                        });
                    }
                    gameCameraMovementBaseline = movementPoints;

                    setGameCameraStat('stEyeContact', faceVisible ? '<i class="fa-solid fa-check me-1"></i>Visible' : '<i class="fa-solid fa-circle-info me-1"></i>Move into frame', faceVisible ? 'text-success' : 'text-warning', true);
                    setGameCameraStat('stPosture', faceVisible ? (poseCameraFacing === false ? 'Head turned estimate' : 'Camera-facing estimate') : 'Optional - not scored', faceVisible ? (poseCameraFacing === false ? 'text-warning' : 'text-success') : 'text-secondary');
                    setGameCameraStat('stGesture', handLandmarks.length > 0 ? (gestureActive ? 'Gesture movement' : handLandmarks.length + ' hand(s) visible') : 'Hands not visible', handLandmarks.length > 0 ? 'text-success' : 'text-secondary');
                    setGameCameraStat('stPose', shouldersVisible ? (shouldersLevel && uprightPosture !== false ? 'Balanced upper body' : 'Posture cue available') : (poseDetected ? 'Partial pose estimate' : 'Pose not detected'), shouldersVisible ? (shouldersLevel && uprightPosture !== false ? 'text-success' : 'text-warning') : 'text-secondary');
                    setGameCameraStat('stMovement', movementScore === null ? 'Calibrating' : (movementScore >= 45 ? 'Higher movement' : 'Steady'), movementScore === null ? 'text-secondary' : (movementScore >= 45 ? 'text-warning' : 'text-success'));
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

            function speakQuestion(text) {
                questionSpeechToken++;
                const token = questionSpeechToken;

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

                    // Spike the amplitude every time a new word is spoken!
                    utterance.onboundary = function(e) {
                        if(e.name === 'word') currentAmplitude = 1.0;
                    };

                    utterance.onstart = function() {
                        document.querySelectorAll('.sound-wave').forEach(el => el.style.display = 'block');
                        document.getElementById('aiAvatarHead').style.borderColor = '#34d399';
                        
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
                        scheduleAutoTranscriptionStart(token);
                    };

                    window.speechSynthesis.speak(utterance);
                } else {
                    scheduleAutoTranscriptionStart(token);
                }
            }

            function startChallengeSession() {
                document.getElementById('workspaceWrapper').style.display = 'block';
                if (cameraCoachingEnabled) initCamera();
                
                if(isVoiceTranscriptionMode()) {
                    document.getElementById('voiceControls').style.display = 'block';
                    document.getElementById('voiceAnalyticsPanel').style.display = 'block';
                }

                timerInterval = setInterval(() => {
                    timerSeconds++;
                    const m = Math.floor(timerSeconds / 60).toString().padStart(2, '0');
                    const s = (timerSeconds % 60).toString().padStart(2, '0');
                    const challengeTimer = document.getElementById('challengeTimer');
                    if (challengeTimer) challengeTimer.innerText = m + ':' + s;
                    
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

                loadQuestion(0);
                
                document.getElementById('answerTextarea').addEventListener('input', triggerAnalysis);
                document.getElementById('sessionNotes').addEventListener('change', autoSaveState);
            }

            function loadQuestion(idx) {
                currentQIdx = idx;
                const q = questions[idx];
                
                document.getElementById('aiQuestionText').innerText = q.question_text;
                document.getElementById('qCounter').innerText = (idx + 1) + '/' + questions.length;

                // Restore answer state if navigated back
                document.getElementById('answerTextarea').value = answersData[idx].text;
                resetSpeechRecognitionBufferFromTextarea();
                
                speakQuestion(q.question_text);
                
                document.querySelectorAll('.prev-btn-class').forEach(el => el.disabled = (idx === 0));
                
                if (idx === questions.length - 1) {
                    document.querySelectorAll('.next-btn-class').forEach(el => {
                        el.innerHTML = '<span class="next-label-full">Finish Challenge</span><span class="next-label-short">Finish</span><i class="fa-solid fa-flag-checkered ms-2"></i>';
                        el.classList.add('btn-success');
                        el.classList.remove('bgrd', 'btn-primary');
                    });
                } else {
                    document.querySelectorAll('.next-btn-class').forEach(el => {
                        el.innerHTML = '<span class="next-label-full">Next Question</span><span class="next-label-short">Next</span><i class="fa-solid fa-arrow-right ms-2"></i>';
                        el.classList.add('bgrd');
                        el.classList.remove('btn-success');
                    });
                }
                
                triggerAnalysis();
            }

            function repeatQuestion() {
                if(questions && questions[currentQIdx]) {
                    speakQuestion(questions[currentQIdx].question_text);
                }
            }

            function prevQuestion() {
                if(isRecording) stopRecording();
                if (currentQIdx > 0) {
                    loadQuestion(currentQIdx - 1);
                }
            }

            function triggerAnalysis() {
                const text = document.getElementById('answerTextarea').value;
                const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
                const charCount = text.length;
                
                document.getElementById('wordCount').innerText = wordCount + ' words';
                document.getElementById('charCount').innerText = charCount + ' characters';

                // Local STAR estimate from answer text.
                const hasS = wordCount > 10;
                const hasT = wordCount > 20 && text.toLowerCase().includes('task');
                const hasA = wordCount > 30 && text.toLowerCase().includes('action');
                const hasR = wordCount > 40 && (text.toLowerCase().includes('result') || text.toLowerCase().includes('led to'));
                
                updateStarIcon('starS', hasS);
                updateStarIcon('starT', hasT);
                updateStarIcon('starA', hasA);
                updateStarIcon('starR', hasR);

                // Coaching Tip
                let tip = "Provide a specific example.";
                if(!hasS) tip = "Start by describing the Situation.";
                else if(!hasR) tip = "Don't forget to mention the measurable Result of your actions.";
                else tip = "Great STAR response!";
                document.getElementById('coachingTip').innerHTML = `<i class="fa-solid fa-lightbulb me-1"></i> <strong>Coach:</strong> ${tip}`;

                // Local readiness estimate shown before server-side scoring.
                let readiness = Math.min(100, Math.max(0, wordCount * 2));
                if(wordCount === 0) readiness = 0;
                document.getElementById('overallReadiness').innerText = readiness + '%';
                document.getElementById('metClarity').innerText = (readiness > 0 ? Math.min(100, readiness + 10) : 0) + '%';
                document.getElementById('metRelevance').innerText = (readiness > 0 ? Math.min(100, readiness + 5) : 0) + '%';
                document.getElementById('metGrammar').innerText = (readiness > 0 ? Math.min(100, readiness + 15) : 0) + '%';
                document.getElementById('metProf').innerText = (readiness > 0 ? Math.min(100, readiness + 8) : 0) + '%';

                // Local filler-word estimate.
                const fillerPattern = /\b(um|uh|like|you know|basically|i mean|sort of|kind of|literally)\b/gi;
                const matches = text.match(fillerPattern);
                const fillers = matches ? matches.length : 0;
                document.getElementById('vaFillers').innerText = fillers;
                answersData[currentQIdx].text = text;
                answersData[currentQIdx].filler_words = fillers;
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

                resetSpeechRecognitionBufferFromTextarea();
                lastSpeechEnd = 0;
                shouldAutoRestartRecognition = true;
                isRecording = true;
                startSpeechRecognitionEngine();
                document.getElementById('micStartBtn').style.display = 'none';
                document.getElementById('micPauseBtn').style.display = 'block';
                document.getElementById('micStopBtn').style.display = 'block';
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
                    let activeSeconds = recTimerSeconds - (answersData[currentQIdx].pause_count * 3);
                    if (activeSeconds < 1) activeSeconds = 1;
                    const wpm = Math.round((wordCount / activeSeconds) * 60);
                    document.getElementById('vaWpm').innerText = wpm;
                    answersData[currentQIdx].wpm = wpm;

                    // Optional body-language guidance is descriptive and never affects scoring.
                    if (cameraCoachingEnabled && recTimerSeconds % 2 === 0) {
                        trackBodyLanguage();
                    }

                }, 1000);

                const scannerBox = document.getElementById('faceScannerBox');
                if (scannerBox) scannerBox.style.display = 'block';
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
                clearInterval(recTimerInterval);
                document.getElementById('micStartBtn').style.display = 'block';
                document.getElementById('micStartBtn').innerText = 'Resume';
                document.getElementById('micPauseBtn').style.display = 'none';
                document.getElementById('faceScannerBox').style.display = 'none';
            }

            function stopRecording() {
                pauseRecording();
                clearTimeout(autoStartAfterQuestionTimer);
                document.getElementById('micStartBtn').innerText = 'Start';
                document.getElementById('micStopBtn').style.display = 'none';
                document.getElementById('recordingTimer').style.display = 'none';
                recTimerSeconds = 0;
                resetSpeechRecognitionBufferFromTextarea();
            }

            function saveCurrentAnswer(isSkipped = false) {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('game_session_id', gameSessionId);
                formData.append('question_index', currentQIdx);
                formData.append('answer_text', answersData[currentQIdx].text);
                formData.append('is_skipped', isSkipped);
                formData.append('response_mode', responseMode);
                formData.append('wpm', answersData[currentQIdx].wpm);
                formData.append('voice_duration', answersData[currentQIdx].voice_duration);
                formData.append('filler_words_count', answersData[currentQIdx].filler_words);
                formData.append('pause_count', answersData[currentQIdx].pause_count);
                formData.append('confidence_score', answersData[currentQIdx].confidence_score);
                formData.append('eye_contact_score', answersData[currentQIdx].eye_contact_score);
                formData.append('posture_score', answersData[currentQIdx].posture_score);
                formData.append('notes', document.getElementById('sessionNotes').value);

                return fetch('{{ route("user.game.answer") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Answer save failed with status ' + response.status);
                    }

                    return response;
                });
            }

            function autoSaveState() {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('game_session_id', gameSessionId);
                formData.append('notes', document.getElementById('sessionNotes').value);
                formData.append('duration_seconds', timerSeconds);
                formData.append('current_question_index', currentQIdx);
                
                fetch('{{ route("user.game.saveState") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(() => {
                    const ind = document.getElementById('autoSaveIndicator');
                    ind.style.display = 'inline';
                    setTimeout(() => ind.style.display = 'none', 2000);
                });
            }

            function submitAnswer() {
                if (isFinishingChallenge) return;
                if(isRecording) stopRecording();
                const isFinalQuestion = currentQIdx >= questions.length - 1;
                document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = true);
                if (isFinalQuestion) {
                    showChallengeFinishModal('Saving your final answer...');
                }
                saveCurrentAnswer(false).then(() => {
                    if (currentQIdx < questions.length - 1) {
                        document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = false);
                        loadQuestion(currentQIdx + 1);
                    } else {
                        finishChallenge();
                    }
                }).catch(error => {
                    console.error(error);
                    hideChallengeFinishModal();
                    document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = false);
                    alert('We could not save your answer. Please try again before continuing.');
                });
            }

            function skipQuestion() {
                if (isFinishingChallenge) return;
                if(isRecording) stopRecording();
                const isFinalQuestion = currentQIdx >= questions.length - 1;
                document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = true);
                if (isFinalQuestion) {
                    showChallengeFinishModal('Saving this skipped answer...');
                }
                saveCurrentAnswer(true).then(() => {
                    if (currentQIdx < questions.length - 1) {
                        document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = false);
                        loadQuestion(currentQIdx + 1);
                    } else {
                        finishChallenge();
                    }
                }).catch(error => {
                    console.error(error);
                    hideChallengeFinishModal();
                    document.querySelectorAll('.next-btn-class, .skip-btn-class').forEach(el => el.disabled = false);
                    alert('We could not save your skipped answer. Please try again before continuing.');
                });
            }

            function prevQuestion() {
                if(isRecording) stopRecording();
                if (currentQIdx > 0) {
                    loadQuestion(currentQIdx - 1);
                }
            }

            function finishChallenge() {
                if (isFinishingChallenge) return;
                isFinishingChallenge = true;
                showChallengeFinishModal('Scoring your answers and preparing your result modal...');
                document.querySelectorAll('.next-btn-class, .skip-btn-class, .prev-btn-class').forEach(el => el.disabled = true);
                let video = document.getElementById('userCamera');
                if (video && video.srcObject) {
                    video.srcObject.getTracks().forEach(track => track.stop());
                }
                clearInterval(timerInterval);
                document.getElementById('formDuration').value = timerSeconds;
                document.getElementById('formNotes').value = document.getElementById('sessionNotes').value;
                window.setTimeout(() => document.getElementById('finishForm').submit(), 120);
            }

            function showChallengeFinishModal(message) {
                const status = document.getElementById('challengeFinishStatus');
                if (status) status.textContent = message;

                const modalEl = document.getElementById('challengeFinishModal');
                if (!modalEl) return;

                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl, {
                        backdrop: 'static',
                        keyboard: false
                    }).show();
                    return;
                }

                modalEl.style.display = 'block';
                modalEl.classList.add('show');
                modalEl.removeAttribute('aria-hidden');
                modalEl.setAttribute('aria-modal', 'true');
            }

            function hideChallengeFinishModal() {
                isFinishingChallenge = false;
                const modalEl = document.getElementById('challengeFinishModal');
                if (!modalEl) return;

                if (window.bootstrap && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    return;
                }

                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.removeAttribute('aria-modal');
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

@if(isset($cameraCoachingEnabled) && $cameraCoachingEnabled)
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/'),
        faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights/')
    ]).then(() => {
        console.log("Optional face-framing models loaded");
    }).catch(err => {
        window.faceFramingModelUnavailable = true;
        console.error("Error loading optional face-framing models", err);
    });
</script>
<script type="module">
    const modelState = window.bodyLanguageModelState = window.bodyLanguageModelState || {
        ready: false,
        failed: false,
        poseLandmarker: null,
        handLandmarker: null
    };

    import('https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21/vision_bundle.mjs')
        .then(async ({ FilesetResolver, PoseLandmarker, HandLandmarker }) => {
            const vision = await FilesetResolver.forVisionTasks(
                'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@0.10.21/wasm'
            );
            const [poseLandmarker, handLandmarker] = await Promise.all([
                PoseLandmarker.createFromOptions(vision, {
                    baseOptions: {
                        modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/pose_landmarker/pose_landmarker_lite/float16/latest/pose_landmarker_lite.task'
                    },
                    runningMode: 'VIDEO',
                    numPoses: 1,
                    minPoseDetectionConfidence: 0.5,
                    minPosePresenceConfidence: 0.5,
                    minTrackingConfidence: 0.5,
                    outputSegmentationMasks: false
                }),
                HandLandmarker.createFromOptions(vision, {
                    baseOptions: {
                        modelAssetPath: 'https://storage.googleapis.com/mediapipe-models/hand_landmarker/hand_landmarker/float16/latest/hand_landmarker.task'
                    },
                    runningMode: 'VIDEO',
                    numHands: 2,
                    minHandDetectionConfidence: 0.5,
                    minHandPresenceConfidence: 0.5,
                    minTrackingConfidence: 0.5
                })
            ]);

            Object.assign(modelState, {
                ready: true,
                failed: false,
                poseLandmarker,
                handLandmarker
            });
            console.log("Optional body-language models loaded");
        })
        .catch(err => {
            modelState.ready = false;
            modelState.failed = true;
            console.error("Error loading optional body-language models", err);
        });
</script>
@endif

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let onboardingTour = null;
        if (typeof window.createSpeakReadyTour === 'function') {
            const stepsMobile = [
                { element: '.ai-avatar-panel', popover: { title: 'AI Coach', description: 'The coach presents each Philippines challenge question and guides the session flow.', side: 'bottom', align: 'start' }},
                { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here while live metrics update.', side: 'top', align: 'start' }},
                { element: '#cameraPanel', popover: { title: 'Optional Body-Language Coach', description: 'Private framing, hand, posture, and movement prompts are optional and never affect readiness or challenge scoring.', side: 'top', align: 'start' }},
                { element: '#overallReadiness', popover: { title: 'AI Visualizer', description: 'Watch instant feedback for clarity, relevance, and professionalism.', side: 'top', align: 'start' }},
                { element: '.star-item', popover: { title: 'STAR Analyzer', description: 'This tracks Situation, Task, Action, and Result coverage in your answer.', side: 'top', align: 'start' }},
                { element: '#voiceAnalyticsPanel', popover: { title: 'Voice Analytics', description: 'Review speaking duration, pace, and filler word usage.', side: 'top', align: 'start' }}
            ];

            const stepsDesktop = [
                { element: '.ai-avatar-panel', popover: { title: 'AI Coach', description: 'The coach presents each Philippines challenge question and guides the session flow.', side: 'right', align: 'start' }},
                { element: '#answerForm', popover: { title: 'Your Response', description: 'Type or speak your answer here while live metrics update.', side: 'right', align: 'start' }},
                { element: '#cameraPanel', popover: { title: 'Optional Body-Language Coach', description: 'Private framing, hand, posture, and movement prompts are optional and never affect readiness or challenge scoring.', side: 'left', align: 'start' }},
                { element: '#overallReadiness', popover: { title: 'AI Visualizer', description: 'Watch instant feedback for clarity, relevance, and professionalism.', side: 'left', align: 'start' }},
                { element: '.star-item', popover: { title: 'STAR Analyzer', description: 'This tracks Situation, Task, Action, and Result coverage in your answer.', side: 'left', align: 'start' }},
                { element: '#voiceAnalyticsPanel', popover: { title: 'Voice Analytics', description: 'Review speaking duration, pace, and filler word usage.', side: 'left', align: 'start' }}
            ];

            onboardingTour = window.createSpeakReadyTour({
                completionKey: 'onboarding_completed_learning_game_session',
                serverDetectedMobile: @json($isMobile),
                stepsMobile,
                stepsDesktop,
                autoStart: false,
            });
        }
        
        // Expose startOnboardingTour to be called after the challenge starts
        const originalStartChallenge = window.startChallengeSession;
        window.startChallengeSession = function() {
            if (typeof originalStartChallenge === 'function') {
                originalStartChallenge.apply(this, arguments);
            }

            if (onboardingTour && !onboardingTour.isCompleted()) {
                setTimeout(() => {
                    onboardingTour.start();
                }, 1000);
            }
        };

        // ARENA COUNTDOWN LOGIC
        let countdownValue = 3;
        const countdownText = document.getElementById('countdown-text');
        const overlay = document.getElementById('get-ready-overlay');
        if (!countdownText || !overlay) {
            window.startChallengeSession();
            return;
        }
        
        const countdownInterval = setInterval(() => {
            countdownValue--;
            if (countdownValue > 0) {
                countdownText.innerText = countdownValue;
            } else if (countdownValue === 0) {
                countdownText.innerText = "GO!";
                countdownText.style.color = "#34d399";
                countdownText.style.animation = "none";
                countdownText.style.transform = "scale(1.5)";
                countdownText.style.transition = "0.2s transform";
            } else {
                clearInterval(countdownInterval);
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    window.startChallengeSession();
                }, 500);
            }
        }, 1000);
    });
</script>
@endpush
@endsection
