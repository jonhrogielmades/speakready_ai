@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Interview Setup')
@section('content')
@php
    $setupDefaults = [
        'difficulty' => old('difficulty', $selectedPack?->difficulty ?? 'medium'),
        'num_questions' => (string) old('num_questions', 10),
        'time_limit' => (string) old('time_limit', $selectedPack?->pressure_mode ? 2 : 0),
        'interview_focus' => old('interview_focus', $selectedPack?->interview_focus ?? 'General Practice'),
        'ai_assistance_level' => old('ai_assistance_level', $selectedPack?->pressure_mode ? 'challenge' : 'standard'),
        'interviewer_strictness' => old('interviewer_strictness', $selectedPack?->pressure_mode ? 'strict' : 'neutral'),
        'live_feedback_mode' => old('live_feedback_mode', $selectedPack?->pressure_mode ? 'real_interview' : 'coaching'),
        'response_mode' => old('response_mode', 'voice'),
        'company_persona' => old('company_persona', $selectedPack?->company_persona ?? ''),
        'interview_format' => old('interview_format', 'standard'),
    ];
    $selectedQuestionTypes = old('question_types', $selectedPack?->question_types ?? ['Behavioral', 'Situational']);
@endphp
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .setup-hero {
        min-height: 98px;
        margin-bottom: 20px;
        border: 1px solid var(--bd);
        border-radius: 16px;
        background:
            radial-gradient(circle at 92% 35%, rgba(96, 165, 250, 0.2), transparent 25%),
            linear-gradient(110deg, rgba(59, 130, 246, 0.12), rgba(6, 182, 212, 0.045)),
            var(--sf);
        border-color: rgba(96, 165, 250, 0.26);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        position: relative;
        isolation: isolate;
    }
    .setup-hero::after {
        content: "";
        position: absolute;
        z-index: -1;
        inset: 0 0 0 auto;
        width: min(34%, 320px);
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.055));
        pointer-events: none;
    }
    .lm .setup-hero {
        background:
            radial-gradient(circle at 92% 35%, rgba(147, 197, 253, 0.2), transparent 25%),
            linear-gradient(110deg, rgba(255, 255, 255, 0.99), rgba(246, 249, 255, 0.97));
        border-color: #dce8fb;
        box-shadow: 0 7px 22px rgba(59, 130, 246, 0.08);
    }
    .setup-hero-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 98px;
        padding: 14px clamp(126px, 14vw, 148px) 14px 16px;
    }
    .setup-hero-copy {
        min-width: 0;
        width: 100%;
    }
    .setup-hero-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.45rem;
        font-weight: 800;
        margin-bottom: 5px;
        letter-spacing: 0;
        text-transform: uppercase;
        line-height: 1.15;
    }
    .setup-hero-title svg {
        width: 23px;
        height: 23px;
        flex: 0 0 auto;
        color: #3b82f6;
    }
    .setup-hero-subtitle {
        max-width: 680px;
        font-size: 0.88rem;
        color: var(--tx3);
        margin: 0;
        line-height: 1.45;
    }
    .setup-hero-art {
        position: absolute;
        z-index: 0;
        right: 8px;
        bottom: -2px;
        width: clamp(122px, 13vw, 142px);
        height: auto;
        filter: drop-shadow(0 16px 24px rgba(37, 99, 235, 0.18));
        pointer-events: none;
        user-select: none;
        transform-origin: 50% 60%;
        animation: setupHeroFloat 5.5s ease-in-out infinite;
    }
    .setup-hero-art .setup-art-panel {
        transform-origin: 50% 50%;
        animation: setupPanelBreathe 5.5s ease-in-out infinite;
    }
    .setup-hero-art .setup-art-line {
        transform-origin: 50% 50%;
        animation: setupLineSlide 3.8s ease-in-out infinite;
    }
    .setup-hero-art .setup-art-line:nth-of-type(3) { animation-delay: 0.18s; }
    .setup-hero-art .setup-art-line:nth-of-type(4) { animation-delay: 0.36s; }
    .setup-hero-art .setup-art-line:nth-of-type(5) { animation-delay: 0.54s; }
    .setup-hero-art .setup-art-check {
        transform-origin: 164px 50px;
        animation: setupCheckPulse 2.6s ease-in-out infinite;
    }
    .setup-hero-art .setup-art-spark {
        transform-origin: center;
        animation: setupSparkDrift 3.2s ease-in-out infinite;
    }
    .setup-hero-art .setup-art-spark:nth-last-child(1) { animation-delay: 0.32s; }

    .setup-panel { 
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        scroll-margin-top: 120px;
        box-shadow: var(--shadow-soft, 0 10px 28px rgba(0, 0, 0, 0.14));
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .setup-panel:hover {
        transform: translateY(-1px);
        border-color: rgba(96, 165, 250, 0.34);
        box-shadow: var(--shadow-card, 0 18px 45px rgba(0, 0, 0, 0.18));
    }
    #sec-interview-setup { --setup-gap: 20px; }
    #sec-interview-setup #setupForm > .row,
    #sec-interview-setup .row.g-4 {
        --bs-gutter-x: var(--setup-gap);
        --bs-gutter-y: var(--setup-gap);
    }
    #setup-left-col {
        display: flex;
        flex-direction: column;
        gap: var(--setup-gap);
    }
    #setup-left-col > .setup-panel,
    #panel-summary {
        margin-bottom: 0;
    }
    #sec-interview-setup .setup-panel h5 {
        font-size: 1rem;
        line-height: 1.3;
        margin-bottom: 16px !important;
        letter-spacing: 0 !important;
    }
    #panel-summary h5 {
        color: var(--tx) !important;
        text-align: left !important;
    }
    #btn-start-interview { scroll-margin-top: 120px; }
    .olbl { font-weight:600;color:var(--tx);font-size:.9rem;margin-bottom:8px;display:block; letter-spacing: 0.3px; }
    .oinp { width:100%;padding:12px 16px;border:1px solid var(--bd);border-radius:12px;background:var(--bg3);color:var(--tx);font-size:.9rem;transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .oinp:focus { outline:none;border-color:var(--pur);box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15); background: var(--sf); }
    .desc-text { font-size:.75rem;color:var(--tx3);margin-top:4px; }
    
    .custom-radio { position:relative;display:flex;align-items:flex-start;padding:16px;border:1px solid var(--bd);border-radius:12px;background:var(--bg3);cursor:pointer;margin-bottom:10px;transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .custom-radio:hover { border-color:#60a5fa; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(96, 165, 250, 0.1); background: var(--sf); }
    .custom-radio input[type="radio"]:checked + div { color: #60a5fa; }
    .custom-radio:has(input[type="radio"]:checked) { border-color: #60a5fa; background: rgba(96, 165, 250, 0.05); box-shadow: 0 4px 20px rgba(96, 165, 250, 0.15); }
    .custom-radio input[type="radio"] { margin-top:4px;margin-right:12px;accent-color:var(--pur); }
    .custom-radio .r-title { font-weight:700;font-size:.95rem;color:var(--tx);display:block; }
    .custom-radio .r-desc { font-size:.8rem;color:var(--tx3);display:block; margin-top:2px; }
    
    .cbx-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
    .custom-cbx { display:flex;align-items:center;padding:12px 16px;border:1px solid var(--bd);border-radius:10px;background:var(--bg3);cursor:pointer;font-size:.9rem;font-weight:500;color:var(--tx);transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .custom-cbx:hover { border-color:#60a5fa; transform: translateY(-1px); background: var(--sf); }
    .custom-cbx:has(input[type="checkbox"]:checked) { border-color: #60a5fa; background: rgba(96, 165, 250, 0.05); }
    .custom-cbx input[type="checkbox"] { margin-right:10px;accent-color:#60a5fa; }

    .summary-row { display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--bd);font-size:.85rem; }
    .summary-row:last-child { border-bottom:none; }
    .summary-label { color:var(--tx3);font-weight:600; }
    .summary-val { color:var(--tx);font-weight:700;text-align:right; }

    /* Drag and Drop Zone */
    @keyframes dashBorder { to { background-position: 100% 0, 0 100%, 0 0, 100% 100%; } }
    .drop-zone { 
        border: 2px dashed var(--bd); 
        border-radius: 16px; 
        padding: 40px 20px; 
        text-align: center; 
        cursor: pointer; 
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
        background: var(--bg3); 
        position: relative;
    }
    .drop-zone:hover { border-color: rgba(96,165,250,0.5); background: var(--sf); transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.05); }
    .drop-zone.dragover { border-color: #60a5fa; background: rgba(96,165,250,0.1); transform: scale(1.02); }
    .drop-zone-icon { font-size: 2.5rem; color: #60a5fa; margin-bottom: 12px; transition: transform 0.4s; }
    .drop-zone:hover .drop-zone-icon { transform: scale(1.1) translateY(-5px); }
    .drop-zone-text { font-size: 0.95rem; color: var(--tx); font-weight: 600; }
    
    /* Persona Cards */
    .persona-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 12px; }
    .persona-card { 
        border: 1px solid var(--bd); 
        border-radius: 16px; 
        padding: 16px; 
        text-align: center; 
        cursor: pointer; 
        background: var(--bg3); 
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
        position: relative; 
        overflow: hidden; 
    }
    .persona-card:hover { border-color: rgba(167,139,250,0.6); transform: translateY(-4px); background: var(--sf); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
    .persona-card.selected { border-color: #8b5cf6; background: rgba(139,92,246,0.08); box-shadow: 0 8px 25px rgba(139,92,246,0.25); }
    .persona-card.selected::after { content:''; position:absolute; inset:0; border-radius:16px; box-shadow: inset 0 0 0 1px rgba(139,92,246,0.5); pointer-events:none; }
    .persona-icon { font-size: 2rem; margin-bottom: 10px; color: var(--tx); transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); display: inline-block; }
    .persona-card:hover .persona-icon { transform: scale(1.15) rotate(5deg); }
    .persona-card.selected .persona-icon { color: #8b5cf6; transform: scale(1.1); }
    .persona-title { font-weight: 700; font-size: 0.9rem; color: var(--tx); letter-spacing: 0.3px; }
    .persona-desc { font-size: 0.75rem; color: var(--tx3); margin-top: 6px; }
    .persona-check { position: absolute; top: 12px; right: 12px; color: #8b5cf6; font-size: 1rem; opacity: 0; transition: opacity 0.3s, transform 0.3s; transform: scale(0.5); }
    .persona-card.selected .persona-check { opacity: 1; transform: scale(1); }
    .setup-chip-panel {
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 14px;
        background: var(--bg3);
    }

    #sec-interview-setup .custom-radio,
    #sec-interview-setup .custom-cbx {
        margin-bottom: 0;
        min-height: 100%;
    }
    #sec-interview-setup .summary-row {
        gap: 14px;
        align-items: flex-start;
    }
    #sec-interview-setup .summary-label,
    #sec-interview-setup .summary-val {
        min-width: 0;
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
    @keyframes setupHeroFloat {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg); }
        50% { transform: translate3d(0, -7px, 0) rotate(1.2deg); }
    }
    @keyframes setupPanelBreathe {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.015); }
    }
    @keyframes setupLineSlide {
        0%, 100% { transform: translateX(0); opacity: 0.78; }
        50% { transform: translateX(7px); opacity: 1; }
    }
    @keyframes setupCheckPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.08); opacity: 0.9; }
    }
    @keyframes setupSparkDrift {
        0%, 100% { transform: translate(0, 0); opacity: 0.55; }
        50% { transform: translate(4px, -5px); opacity: 0.9; }
    }

    @media (prefers-reduced-motion: reduce) {
        #sec-interview-setup .setup-hero-art,
        #sec-interview-setup .setup-hero-art * {
            animation: none !important;
        }
    }

    /* Driver.js Dark Theme Customization */
    .driverjs-theme-dark.driver-popover { background-color: var(--bg3); color: var(--tx); border: 1px solid var(--bd); }
    .driverjs-theme-dark .driver-popover-title { color: var(--tx); }
    .driverjs-theme-dark .driver-popover-description { color: var(--tx2); }
    .driverjs-theme-dark .driver-popover-footer button { background-color: var(--bg); color: var(--tx); border: 1px solid var(--bd); text-shadow: none; }
    .driverjs-theme-dark .driver-popover-progress-text { color: var(--tx3); }
    .driverjs-theme-dark .driver-popover-arrow::before { border-color: var(--bg3) !important; }

    @media (max-width: 767px) {
        #sec-interview-setup {
            --setup-gap: 14px;
            padding-bottom: calc(18px + env(safe-area-inset-bottom, 0px));
        }
        #sec-interview-setup #setupForm > .row {
            --bs-gutter-y: 14px;
        }
        #sec-interview-setup .setup-hero {
            margin-bottom: 12px;
            border-radius: 14px;
            min-height: 104px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        #sec-interview-setup .setup-hero-inner {
            justify-content: flex-start;
            min-height: 104px;
            padding: 14px 96px 14px 14px;
        }
        #sec-interview-setup .setup-hero-title {
            justify-content: flex-start;
            gap: 7px;
            font-size: 1.1rem !important;
            margin-bottom: 4px;
            letter-spacing: 0;
        }
        #sec-interview-setup .setup-hero-title svg {
            width: 20px;
            height: 20px;
        }
        #sec-interview-setup .setup-hero-subtitle {
            max-width: 100%;
            font-size: 0.74rem;
            line-height: 1.4;
        }
        #sec-interview-setup .setup-hero-art {
            right: -10px;
            bottom: -1px;
            width: 112px;
        }
        #sec-interview-setup .setup-panel {
            border-radius: 14px !important;
            padding: 14px !important;
            margin-bottom: 0;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        #sec-interview-setup .setup-panel:hover,
        #sec-interview-setup .custom-radio:hover,
        #sec-interview-setup .custom-cbx:hover,
        #sec-interview-setup .persona-card:hover,
        #sec-interview-setup .drop-zone:hover {
            transform: none;
        }
        #sec-interview-setup .setup-panel h5 {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.98rem;
            margin-bottom: 12px !important;
        }
        #sec-interview-setup .setup-panel h5 i {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(96, 165, 250, 0.12);
            margin-right: 0 !important;
            flex: 0 0 auto;
        }
        #sec-interview-setup .row.g-3,
        #sec-interview-setup .row.g-4 {
            --bs-gutter-y: 12px;
            --bs-gutter-x: 12px;
        }
        #sec-interview-setup .row.mt-1 {
            margin-top: 0 !important;
        }
        #sec-interview-setup .olbl {
            font-size: 0.78rem;
            margin-bottom: 6px;
        }
        #sec-interview-setup .oinp {
            min-height: 46px;
            padding: 11px 13px;
            border-radius: 11px;
            font-size: 0.86rem;
        }
        #sec-interview-setup textarea.oinp {
            min-height: 96px;
            line-height: 1.45;
        }
        #sec-interview-setup .desc-text {
            font-size: 0.71rem;
            line-height: 1.35;
        }
        #sec-interview-setup .custom-radio,
        #sec-interview-setup .custom-cbx {
            align-items: center;
            min-height: 52px;
            padding: 12px;
            border-radius: 12px;
            gap: 10px;
        }
        #sec-interview-setup .custom-radio input[type="radio"],
        #sec-interview-setup .custom-cbx input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
            flex: 0 0 auto;
        }
        #sec-interview-setup .custom-radio .r-title {
            font-size: 0.88rem;
        }
        #sec-interview-setup .custom-radio .r-desc {
            font-size: 0.72rem;
            line-height: 1.35;
        }
        #sec-interview-setup .cbx-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        #sec-interview-setup .setup-chip-panel {
            padding: 12px;
            border-radius: 12px;
        }
        #sec-interview-setup .drop-zone {
            padding: 22px 14px;
            border-radius: 14px;
        }
        #sec-interview-setup .drop-zone-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        #sec-interview-setup .drop-zone-text {
            font-size: 0.84rem;
            line-height: 1.35;
        }
        #sec-interview-setup .persona-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        #sec-interview-setup .persona-card {
            min-height: 112px;
            padding: 14px 10px 12px;
            border-radius: 12px;
        }
        #sec-interview-setup .persona-card.selected::after {
            border-radius: 12px;
        }
        #sec-interview-setup .persona-icon {
            font-size: 1.45rem;
            margin-bottom: 8px;
        }
        #sec-interview-setup .persona-title {
            font-size: 0.8rem;
        }
        #sec-interview-setup .persona-desc {
            font-size: 0.68rem;
            line-height: 1.25;
        }
        #sec-interview-setup .persona-check {
            top: 9px;
            right: 9px;
            font-size: 0.88rem;
        }
        #sec-interview-setup .summary-row {
            display: grid;
            grid-template-columns: minmax(92px, 0.8fr) minmax(0, 1.2fr);
            padding: 8px 0;
            gap: 10px;
            font-size: 0.78rem;
            align-items: start;
        }
        #sec-interview-setup .summary-val {
            overflow-wrap: anywhere;
        }
        #sec-interview-setup .setup-start-action {
            margin-top: 18px !important;
        }
        #sec-interview-setup #btn-start-interview {
            min-height: 54px;
            border-radius: 13px !important;
            font-size: 0.98rem !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 10px 22px rgba(96, 165, 250, 0.32) !important;
            touch-action: manipulation;
        }
        #sec-interview-setup #btn-start-interview i {
            margin-left: 0 !important;
        }
        #panel-summary {
            position: static !important;
            top: auto !important;
            background: linear-gradient(145deg, rgba(59, 130, 246, 0.11), rgba(6, 182, 212, 0.04)) !important;
            border-color: rgba(96, 165, 250, 0.28) !important;
        }
        #panel-summary h5 {
            margin-bottom: 10px !important;
        }
    }

    @media (max-width: 390px) {
        #sec-interview-setup .setup-hero-inner {
            padding-right: 82px;
        }
        #sec-interview-setup .setup-hero-title {
            font-size: 1rem !important;
        }
        #sec-interview-setup .setup-hero-art {
            width: 98px;
        }
        #sec-interview-setup .persona-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<script>
    if (window.pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
    }
</script>

<div class="db-section active" id="sec-interview-setup">
    <div class="setup-hero">
        <div class="setup-hero-inner">
            <div class="setup-hero-copy">
                <h4 class="setup-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true" role="img">
                        <path d="M4 7h9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M17 7h3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="15" cy="7" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 12h3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M11 12h9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="9" cy="12" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 17h7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M15 17h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="13" cy="17" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Interview Setup
                </h4>
                <p class="setup-hero-subtitle">Configure your mock interview session to match your goals.</p>
            </div>
        </div>
        <svg class="setup-hero-art" viewBox="0 0 220 150" aria-hidden="true" role="img">
            <defs>
                <linearGradient id="setupArtPanel" x1="34" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE"/>
                    <stop offset="1" stop-color="#ECFEFF"/>
                </linearGradient>
                <linearGradient id="setupArtBlue" x1="64" y1="20" x2="154" y2="120" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#3B82F6"/>
                    <stop offset="1" stop-color="#06B6D4"/>
                </linearGradient>
            </defs>
            <rect class="setup-art-panel" x="32" y="20" width="156" height="108" rx="18" fill="url(#setupArtPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <rect class="setup-art-line" x="51" y="42" width="70" height="8" rx="4" fill="#93C5FD"/>
            <rect class="setup-art-line" x="51" y="59" width="118" height="7" rx="3.5" fill="#C7D2FE"/>
            <rect class="setup-art-line" x="51" y="75" width="96" height="7" rx="3.5" fill="#BAE6FD"/>
            <path class="setup-art-line" d="M58 103h46" stroke="#2563EB" stroke-width="8" stroke-linecap="round"/>
            <path class="setup-art-line" d="M126 103h31" stroke="#06B6D4" stroke-width="8" stroke-linecap="round"/>
            <circle class="setup-art-check" cx="164" cy="50" r="22" fill="url(#setupArtBlue)"/>
            <path d="M155 50l6 6 13-15" fill="none" stroke="#FFFFFF" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="61" cy="31" r="5" fill="#60A5FA"/>
            <circle cx="77" cy="31" r="5" fill="#67E8F9"/>
            <path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
            <path class="setup-art-spark" d="M190 30l9-9m-1 28l13-2" stroke="#38BDF8" stroke-width="5" stroke-linecap="round" opacity=".55"/>
            <path class="setup-art-spark" d="M24 58l-11-7m19 55l-14 3" stroke="#38BDF8" stroke-width="5" stroke-linecap="round" opacity=".55"/>
        </svg>
    </div>

    @if($errors->any())
       <div class="alert alert-danger" style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#f87171;padding:10px;border-radius:10px;margin-bottom:15px;font-size:.85rem">
          <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $errors->first() }}
       </div>
    @endif

    <form action="{{ route('interview.start') }}" method="POST" id="setupForm">
        @csrf
        <div class="row g-4">
            <!-- Left Column: Form Settings -->
            <div class="col-lg-8" id="setup-left-col">
                
                <!-- Basic Info -->
                <div class="setup-panel animate-fade-up delay-100" id="panel-basic">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-briefcase me-2" style="color:#60a5fa"></i> Basic Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="olbl">Interview Category</label>
                            <select class="oinp setup-input" name="category_id" id="valCategory" required>
                                @if(isset($categories) && $categories->count() > 0)
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->title }}</option>
                                    @endforeach
                                @else
                                    <option value="" disabled selected>No Categories Available (Contact Admin)</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Target Position</label>
                            <input class="oinp setup-input" type="text" name="target_position" id="valPosition" placeholder="Enter your target role (e.g. Software Developer)..." value="{{ old('target_position', $selectedApplication?->job_title ?? ($selectedPack?->role_family ?? '')) }}" required>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="olbl">Tracked Application</label>
                            <select class="oinp setup-input" name="job_application_id" id="valApplication">
                                <option value="">No tracked job</option>
                                @foreach(($applications ?? collect()) as $application)
                                    <option value="{{ $application->id }}" {{ (int) old('job_application_id', $selectedApplication?->id) === (int) $application->id ? 'selected' : '' }}>
                                        {{ $application->company_name }} - {{ $application->job_title }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="desc-text">Use your saved resume, job description, competency twin, and adaptive plan.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Interview Pack</label>
                            <select class="oinp setup-input" name="interview_pack_id" id="valPack">
                                <option value="">Custom setup</option>
                                @foreach(($packs ?? collect()) as $pack)
                                    <option value="{{ $pack->id }}" {{ (int) old('interview_pack_id', $selectedPack?->id) === (int) $pack->id ? 'selected' : '' }}>
                                        {{ $pack->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="desc-text"><a href="{{ route('user.packs.index') }}" style="color:#60a5fa;text-decoration:none;">Browse packs</a> for company and role-specific drills.</div>
                        </div>
                        <div class="col-md-12">
                            <div class="setup-chip-panel">
                                <strong style="color:var(--tx)"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Automatic calibrated AI routing</strong>
                                <div class="desc-text">SpeakReady selects a healthy provider automatically and applies the same versioned scoring rubric. Provider choice no longer changes the user setup.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Personalization -->
                <div class="setup-panel animate-fade-up delay-200" id="panel-advanced">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-file-lines me-2" style="color:#a78bfa"></i> Advanced Personalization <span class="badge bg-primary" style="font-size:0.7rem;vertical-align:middle;margin-left:5px">New</span></h5>
                    <p style="font-size:.85rem;color:var(--tx3);margin-bottom:15px">Provide your resume and the target job description to get highly tailored, role-specific questions.</p>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="olbl">Upload Resume (PDF)</label>
                            <div class="drop-zone" id="resumeDropZone" onclick="document.getElementById('resumeFileInput').click()">
                                <i class="fa-solid fa-cloud-arrow-up drop-zone-icon"></i>
                                <div class="drop-zone-text" id="dropZoneText">Drag & Drop your PDF resume here<br><span style="font-size:0.75rem;opacity:0.7">or click to browse</span></div>
                                <input type="file" id="resumeFileInput" accept=".pdf" style="display:none;" onchange="handleResumeUpload(event)">
                            </div>
                            <textarea class="oinp setup-input mt-2" name="resume_text" id="valResume" rows="3" placeholder="Or paste your resume text manually here..." style="font-size:0.8rem;">{{ old('resume_text', $selectedApplication?->resume_text ?? '') }}</textarea>
                            <div id="pdfParsingIndicator" style="display:none; color:#60a5fa; font-size:0.8rem; margin-top:5px;"><i class="fa-solid fa-circle-notch fa-spin me-1"></i> Extracting text from PDF...</div>
                        </div>
                        <div class="col-md-12">
                            <label class="olbl">Paste Job Description (Optional)</label>
                            <textarea class="oinp setup-input" name="job_description" id="valJobDescription" rows="3" placeholder="Paste the exact job description you are applying for to tailor the questions to those specific requirements...">{{ old('job_description', $selectedApplication?->job_description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Interview Structure -->
                <div class="setup-panel animate-fade-up delay-300" id="panel-structure">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-layer-group me-2" style="color:#60a5fa"></i> Interview Structure</h5>
                    
                    <label class="olbl mb-3">Difficulty Level</label>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="difficulty" value="easy" class="setup-input" {{ $setupDefaults['difficulty'] === 'easy' ? 'checked' : '' }}>
                                <div>
                                    <span class="r-title">Easy</span>
                                    <span class="r-desc">Basic and introductory questions</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="difficulty" value="medium" class="setup-input" {{ $setupDefaults['difficulty'] === 'medium' ? 'checked' : '' }}>
                                <div>
                                    <span class="r-title">Medium</span>
                                    <span class="r-desc">Common interview questions</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="difficulty" value="hard" class="setup-input" {{ $setupDefaults['difficulty'] === 'hard' ? 'checked' : '' }}>
                                <div>
                                    <span class="r-title">Hard</span>
                                    <span class="r-desc">Advanced and situational questions</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="olbl">Number of Questions</label>
                            <select class="oinp setup-input" name="num_questions" id="valNumQuestions">
                                <option value="5" {{ $setupDefaults['num_questions'] === '5' ? 'selected' : '' }}>5 Questions</option>
                                <option value="10" {{ $setupDefaults['num_questions'] === '10' ? 'selected' : '' }}>10 Questions</option>
                                <option value="15" {{ $setupDefaults['num_questions'] === '15' ? 'selected' : '' }}>15 Questions</option>
                                <option value="20" {{ $setupDefaults['num_questions'] === '20' ? 'selected' : '' }}>20 Questions</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Time Limit</label>
                            <select class="oinp setup-input" name="time_limit" id="valTimeLimit">
                                <option value="0" {{ $setupDefaults['time_limit'] === '0' ? 'selected' : '' }}>No Limit</option>
                                <option value="1" {{ $setupDefaults['time_limit'] === '1' ? 'selected' : '' }}>1 Minute per Question</option>
                                <option value="2" {{ $setupDefaults['time_limit'] === '2' ? 'selected' : '' }}>2 Minutes per Question</option>
                                <option value="3" {{ $setupDefaults['time_limit'] === '3' ? 'selected' : '' }}>3 Minutes per Question</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="olbl">Interview Format Laboratory</label>
                            <select class="oinp setup-input" name="interview_format" id="valInterviewFormat">
                                @foreach([
                                    'standard' => 'Standard live interview',
                                    'hr_screen' => 'HR screening',
                                    'hiring_manager' => 'Hiring manager',
                                    'panel' => 'Multi-perspective panel',
                                    'phone' => 'Telephone interview',
                                    'asynchronous' => 'One-way recorded interview',
                                    'technical' => 'Technical deep dive',
                                    'case' => 'Case interview',
                                    'presentation' => 'Presentation defense',
                                ] as $formatValue => $formatLabel)
                                    <option value="{{ $formatValue }}" {{ $setupDefaults['interview_format'] === $formatValue ? 'selected' : '' }}>{{ $formatLabel }}</option>
                                @endforeach
                            </select>
                            <div class="desc-text">Feedback is adjusted to the selected format; camera behavior remains optional.</div>
                        </div>
                    </div>
                </div>

                <div class="setup-panel animate-fade-up delay-300" id="panel-inclusive">
                    <h5 style="font-weight:700;margin-bottom:10px;color:var(--tx)"><i class="fa-solid fa-universal-access me-2 text-info"></i> Inclusive Practice Conditions</h5>
                    <p class="desc-text mb-3">Choose conditions that give you an accurate opportunity to demonstrate job-related ability. These settings are recorded with the assessment.</p>
                    @php $inclusive = Auth::user()->profile?->inclusive_preferences ?? []; @endphp
                    <div class="cbx-grid">
                        @foreach([
                            'camera_coaching' => 'Optional camera framing coach',
                            'separate_language_scoring' => 'Separate language mechanics',
                            'extended_time' => 'Extended response time',
                            'captions' => 'Captions / transcript controls',
                            'reduced_distraction' => 'Reduced-distraction workspace',
                            'simplified_questions' => 'Clearer question wording',
                        ] as $preferenceKey => $preferenceLabel)
                            <label class="custom-cbx"><input type="checkbox" name="{{ $preferenceKey }}" value="1" {{ old($preferenceKey, data_get($inclusive, $preferenceKey, false)) ? 'checked' : '' }}> {{ $preferenceLabel }}</label>
                        @endforeach
                    </div>
                    <div class="desc-text mt-3"><strong>Important:</strong> eye contact and posture are never included in the readiness score. Camera coaching only reports framing and detection availability.</div>
                </div>

                <!-- Content & Assistance -->
                <div class="setup-panel animate-fade-up delay-400" id="panel-content">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-brain me-2" style="color:#f87171"></i> Content & Assistance</h5>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="olbl">Interview Focus</label>
                            <select class="oinp setup-input" name="interview_focus" id="valFocus">
                                @foreach(['General Practice', 'Communication Skills', 'Technical Knowledge', 'Problem Solving', 'Leadership', 'Teamwork', 'Personal', 'Salary Negotiation'] as $focusOption)
                                    <option value="{{ $focusOption }}" {{ $setupDefaults['interview_focus'] === $focusOption ? 'selected' : '' }}>{{ $focusOption }}{{ $focusOption === 'Salary Negotiation' ? ' (New)' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">AI Assistance Level</label>
                            <select class="oinp setup-input" name="ai_assistance_level" id="valAssistance">
                                <option value="beginner" {{ $setupDefaults['ai_assistance_level'] === 'beginner' ? 'selected' : '' }}>Beginner Mode (More hints & feedback)</option>
                                <option value="standard" {{ $setupDefaults['ai_assistance_level'] === 'standard' ? 'selected' : '' }}>Standard Mode (Balanced experience)</option>
                                <option value="challenge" {{ $setupDefaults['ai_assistance_level'] === 'challenge' ? 'selected' : '' }}>Challenge Mode (No hints, harder follow-ups)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="olbl">Interviewer Strictness</label>
                            <select class="oinp setup-input" name="interviewer_strictness" id="valStrictness">
                                <option value="friendly" {{ $setupDefaults['interviewer_strictness'] === 'friendly' ? 'selected' : '' }}>Friendly Interviewer</option>
                                <option value="neutral" {{ $setupDefaults['interviewer_strictness'] === 'neutral' ? 'selected' : '' }}>Neutral HR Interviewer</option>
                                <option value="strict" {{ $setupDefaults['interviewer_strictness'] === 'strict' ? 'selected' : '' }}>Strict Technical Lead</option>
                                <option value="executive" {{ $setupDefaults['interviewer_strictness'] === 'executive' ? 'selected' : '' }}>Executive Panel</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Live Feedback Mode</label>
                            <select class="oinp setup-input" name="live_feedback_mode" id="valFeedbackMode">
                                <option value="coaching" {{ $setupDefaults['live_feedback_mode'] === 'coaching' ? 'selected' : '' }}>Coaching On</option>
                                <option value="real_interview" {{ $setupDefaults['live_feedback_mode'] === 'real_interview' ? 'selected' : '' }}>Real Interview Mode</option>
                            </select>
                        </div>
                    </div>

                    <div class="setup-chip-panel mb-4">
                        <label class="custom-cbx" style="margin:0;">
                            <input type="checkbox" name="pressure_mode" id="valPressureMode" value="1" class="setup-input" {{ old('pressure_mode', $selectedPack?->pressure_mode ? 1 : 0) ? 'checked' : '' }}>
                            Pressure Mode
                        </label>
                        <div class="desc-text">Uses strict interviewer behavior, real interview mode, tougher follow-ups, and timed answers by default.</div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-12">
                            <label class="olbl">Company Persona Simulator</label>
                            <p style="font-size:.75rem;color:var(--tx3);margin-top:-4px;margin-bottom:8px;">Practice a general style inspired by public interview guidance; this is not an exact representation of any employer.</p>
                            <input type="hidden" name="company_persona" id="valPersona" value="{{ $setupDefaults['company_persona'] }}" class="setup-input">
                            <div class="persona-grid">
                                <div class="persona-card {{ $setupDefaults['company_persona'] === '' ? 'selected' : '' }}" onclick="selectPersona(this, '')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-solid fa-building persona-icon" style="color:#60a5fa"></i>
                                    <div class="persona-title">Standard</div>
                                    <div class="persona-desc">General Industry</div>
                                </div>
                                <div class="persona-card {{ $setupDefaults['company_persona'] === 'Amazon' ? 'selected' : '' }}" onclick="selectPersona(this, 'Amazon')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-brands fa-amazon persona-icon" style="color:#f97316"></i>
                                    <div class="persona-title">Amazon</div>
                                    <div class="persona-desc">Leadership Principles</div>
                                </div>
                                <div class="persona-card {{ $setupDefaults['company_persona'] === 'Google' ? 'selected' : '' }}" onclick="selectPersona(this, 'Google')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-brands fa-google persona-icon" style="color:#ef4444"></i>
                                    <div class="persona-title">Google</div>
                                    <div class="persona-desc">Googlyness & Scaling</div>
                                </div>
                                <div class="persona-card {{ $setupDefaults['company_persona'] === 'McKinsey' ? 'selected' : '' }}" onclick="selectPersona(this, 'McKinsey')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-solid fa-chart-pie persona-icon" style="color:#3b82f6"></i>
                                    <div class="persona-title">McKinsey</div>
                                    <div class="persona-desc">Consulting & Case</div>
                                </div>
                                <div class="persona-card {{ $setupDefaults['company_persona'] === 'Goldman Sachs' ? 'selected' : '' }}" onclick="selectPersona(this, 'Goldman Sachs')">
                                    <i class="fa-solid fa-circle-check persona-check"></i>
                                    <i class="fa-solid fa-vault persona-icon" style="color:#eab308"></i>
                                    <div class="persona-title">Goldman Sachs</div>
                                    <div class="persona-desc">Finance & Pressure</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="olbl mb-2">Question Types</label>
                    <div class="cbx-grid">
                        @foreach(['Behavioral', 'Situational', 'Technical', 'Personal'] as $questionType)
                            <label class="custom-cbx"><input type="checkbox" name="question_types[]" value="{{ $questionType }}" {{ in_array($questionType, $selectedQuestionTypes, true) ? 'checked' : '' }}> {{ $questionType }} Questions</label>
                        @endforeach
                    </div>
                </div>

                <!-- Response Mode -->
                <div class="setup-panel animate-fade-up delay-400" id="panel-response">
                    <h5 style="font-weight:700;margin-bottom:20px;color:var(--tx)"><i class="fa-solid fa-microphone me-2" style="color:#34d399"></i> Response Mode</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="response_mode" value="text" class="setup-input" {{ $setupDefaults['response_mode'] === 'text' ? 'checked' : '' }}>
                                <div>
                                    <span class="r-title">Text Mode</span>
                                    <span class="r-desc">Type your answers manually</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="response_mode" value="voice" class="setup-input" {{ $setupDefaults['response_mode'] === 'voice' ? 'checked' : '' }}>
                                <div>
                                    <span class="r-title">Voice Mode</span>
                                    <span class="r-desc">Speak through your microphone</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="custom-radio">
                                <input type="radio" name="response_mode" value="hybrid" class="setup-input" {{ $setupDefaults['response_mode'] === 'hybrid' ? 'checked' : '' }}>
                                <div>
                                    <span class="r-title">Hybrid Mode</span>
                                    <span class="r-desc">Voice-to-text with manual editing</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Live Summary -->
            <div class="col-lg-4 animate-fade-up delay-200">
                <div style="position:sticky;top:20px;">
                    <div class="setup-panel" id="panel-summary" style="background:linear-gradient(145deg, rgba(59,130,246,0.08) 0%, rgba(59,130,246,0.02) 100%); border:1px solid rgba(59,130,246,0.25); box-shadow: 0 15px 35px rgba(59,130,246,0.1), inset 0 1px 1px rgba(255, 255, 255, 0.1); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);">
                        <h5 style="font-weight:800;margin-bottom:24px;color:var(--pur);text-align:center;letter-spacing:0.5px;"><i class="fa-solid fa-clipboard-list me-2"></i> Interview Summary</h5>
                        
                        <div class="summary-row">
                            <span class="summary-label">Category:</span>
                            <span class="summary-val" id="sumCategory">Job Interview</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Position:</span>
                            <span class="summary-val" id="sumPosition">Software Developer</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Application:</span>
                            <span class="summary-val" id="sumApplication">None</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Pack:</span>
                            <span class="summary-val" id="sumPack">Custom</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Difficulty:</span>
                            <span class="summary-val" id="sumDifficulty">Medium</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Questions:</span>
                            <span class="summary-val" id="sumQuestions">10</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Response Mode:</span>
                            <span class="summary-val" id="sumResponse">Voice</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Focus:</span>
                            <span class="summary-val" id="sumFocus">General Practice</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Strictness:</span>
                            <span class="summary-val" id="sumStrictness">Neutral HR Interviewer</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Live Feedback:</span>
                            <span class="summary-val" id="sumFeedbackMode">Coaching On</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Pressure Mode:</span>
                            <span class="summary-val" id="sumPressureMode">Off</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Company Persona:</span>
                            <span class="summary-val" id="sumPersona">Standard</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Est. Duration:</span>
                            <span class="summary-val text-success" id="sumDuration">15 Minutes</span>
                        </div>
                        
                        <div class="setup-start-action" style="margin-top:30px;">
                            <button type="submit" id="btn-start-interview" class="btn w-100 py-3 btn-shine" style="font-size:1.1rem;font-weight:700;border-radius:14px;background:var(--dash-primary, #60a5fa);color:white;border:none;box-shadow:0 8px 25px rgba(96,165,250,0.4);transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(96,165,250,0.6)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 8px 25px rgba(96,165,250,0.4)'">
                                Start Mock Interview <i class="fa-solid fa-play ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@php
    $applicationData = ($applications ?? collect())->mapWithKeys(fn ($application) => [
        $application->id => [
            'company_name' => $application->company_name,
            'job_title' => $application->job_title,
            'resume_text' => $application->resume_text,
            'job_description' => $application->job_description,
            'match_score' => $application->match_score,
        ],
    ])->all();
    $packData = ($packs ?? collect())->mapWithKeys(fn ($pack) => [
        $pack->id => [
            'name' => $pack->name,
            'role_family' => $pack->role_family,
            'difficulty' => $pack->difficulty,
            'interview_focus' => $pack->interview_focus,
            'company_persona' => $pack->company_persona,
            'question_types' => $pack->question_types ?? [],
            'pressure_mode' => (bool) $pack->pressure_mode,
        ],
    ])->all();
@endphp
<script>
    const applicationData = @json($applicationData);
    const packData = @json($packData);

    function updateSummary() {
        // Category text
        const catSelect = document.getElementById('valCategory');
        if(catSelect.options.length > 0) {
            document.getElementById('sumCategory').innerText = catSelect.options[catSelect.selectedIndex].text;
        }

        // Position
        const posVal = document.getElementById('valPosition').value;
        document.getElementById('sumPosition').innerText = posVal || 'Not Specified';

        const appSelect = document.getElementById('valApplication');
        if (appSelect) {
            document.getElementById('sumApplication').innerText = appSelect.value ? appSelect.options[appSelect.selectedIndex].text : 'None';
        }

        const packSelect = document.getElementById('valPack');
        if (packSelect) {
            document.getElementById('sumPack').innerText = packSelect.value ? packSelect.options[packSelect.selectedIndex].text : 'Custom';
        }

        // Difficulty
        const diff = document.querySelector('input[name="difficulty"]:checked');
        if(diff) document.getElementById('sumDifficulty').innerText = diff.value.charAt(0).toUpperCase() + diff.value.slice(1);

        // Questions
        const numQ = document.getElementById('valNumQuestions').value;
        document.getElementById('sumQuestions').innerText = numQ;

        // Response Mode
        const resp = document.querySelector('input[name="response_mode"]:checked');
        if(resp) document.getElementById('sumResponse').innerText = resp.value.charAt(0).toUpperCase() + resp.value.slice(1);

        // Focus
        const focus = document.getElementById('valFocus').value;
        document.getElementById('sumFocus').innerText = focus;

        const strictness = document.getElementById('valStrictness');
        if (strictness) {
            document.getElementById('sumStrictness').innerText = strictness.options[strictness.selectedIndex].text;
        }

        const feedbackMode = document.getElementById('valFeedbackMode');
        if (feedbackMode) {
            document.getElementById('sumFeedbackMode').innerText = feedbackMode.options[feedbackMode.selectedIndex].text;
        }

        const pressureMode = document.getElementById('valPressureMode');
        if (pressureMode) {
            document.getElementById('sumPressureMode').innerText = pressureMode.checked ? 'On' : 'Off';
        }

        // Persona
        const personaInput = document.getElementById('valPersona');
        if (personaInput) {
            document.getElementById('sumPersona').innerText = personaInput.value || 'Standard';
        }

        // Estimated Duration
        const timeLimit = parseInt(document.getElementById('valTimeLimit').value);
        let durationStr = "Self-paced";
        if(timeLimit > 0) {
            durationStr = (numQ * timeLimit) + " Minutes";
        } else {
            // Rough estimate based on questions (e.g. 1.5 mins per question)
            durationStr = Math.round(numQ * 1.5) + " Minutes";
        }
        document.getElementById('sumDuration').innerText = durationStr;
    }

    // Attach listeners
    document.querySelectorAll('.setup-input').forEach(el => {
        el.addEventListener('change', updateSummary);
        el.addEventListener('keyup', updateSummary);
    });

    document.getElementById('valApplication')?.addEventListener('change', applyApplicationSelection);
    document.getElementById('valPack')?.addEventListener('change', applyPackSelection);
    document.getElementById('valPressureMode')?.addEventListener('change', applyPressureMode);

    function selectPersona(cardEl, value) {
        document.querySelectorAll('.persona-card').forEach(el => el.classList.remove('selected'));
        cardEl.classList.add('selected');
        document.getElementById('valPersona').value = value;
        updateSummary();
    }

    function setPersonaValue(value) {
        document.getElementById('valPersona').value = value || '';
        let selected = false;
        document.querySelectorAll('.persona-card').forEach(el => {
            const clickAttr = el.getAttribute('onclick') || '';
            const matches = value ? clickAttr.includes(`'${value}'`) : clickAttr.includes("''");
            el.classList.toggle('selected', matches && !selected);
            if (matches) selected = true;
        });
        if (!selected) {
            document.querySelectorAll('.persona-card').forEach(el => el.classList.remove('selected'));
        }
    }

    function setSelectValue(id, value) {
        const el = document.getElementById(id);
        if (!el || value === null || value === undefined || value === '') return;
        const option = Array.from(el.options).find(opt => opt.value === String(value));
        if (option) el.value = String(value);
    }

    function setDifficulty(value) {
        const radio = document.querySelector(`input[name="difficulty"][value="${value}"]`);
        if (radio) radio.checked = true;
    }

    function setQuestionTypes(types) {
        if (!Array.isArray(types) || types.length === 0) return;
        document.querySelectorAll('input[name="question_types[]"]').forEach(input => {
            input.checked = types.includes(input.value);
        });
    }

    function applyPressureMode() {
        const enabled = document.getElementById('valPressureMode')?.checked;
        if (enabled) {
            setSelectValue('valStrictness', 'strict');
            setSelectValue('valAssistance', 'challenge');
            setSelectValue('valFeedbackMode', 'real_interview');
            if (document.getElementById('valTimeLimit')?.value === '0') {
                setSelectValue('valTimeLimit', '2');
            }
        }
        updateSummary();
    }

    function applyApplicationSelection() {
        const selectedId = document.getElementById('valApplication')?.value;
        const app = selectedId ? applicationData[selectedId] : null;
        if (!app) {
            updateSummary();
            return;
        }

        document.getElementById('valPosition').value = app.job_title || '';
        document.getElementById('valResume').value = app.resume_text || '';
        document.getElementById('valJobDescription').value = app.job_description || '';
        updateSummary();
    }

    function applyPackSelection() {
        const selectedId = document.getElementById('valPack')?.value;
        const pack = selectedId ? packData[selectedId] : null;
        if (!pack) {
            updateSummary();
            return;
        }

        if (!document.getElementById('valPosition').value && pack.role_family) {
            document.getElementById('valPosition').value = pack.role_family;
        }
        setDifficulty(pack.difficulty);
        setSelectValue('valFocus', pack.interview_focus);
        setPersonaValue(pack.company_persona);
        setQuestionTypes(pack.question_types || []);

        const pressure = document.getElementById('valPressureMode');
        if (pressure && pack.pressure_mode) {
            pressure.checked = true;
            applyPressureMode();
        }

        updateSummary();
    }

    // PDF Drag and Drop Handling
    const dropZone = document.getElementById('resumeDropZone');
    const valResume = document.getElementById('valResume');
    const pdfIndicator = document.getElementById('pdfParsingIndicator');

    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', (e) => { e.preventDefault(); dropZone.classList.remove('dragover'); });
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            processPdfFile(e.dataTransfer.files[0]);
        }
    });

    function handleResumeUpload(e) {
        if (e.target.files && e.target.files[0]) {
            processPdfFile(e.target.files[0]);
        }
    }

    async function processPdfFile(file) {
        if (file.type !== 'application/pdf') {
            alert('Please upload a valid PDF file.');
            return;
        }
        if (!window.pdfjsLib) {
            alert('PDF parsing is not available right now. Please paste your resume text manually.');
            return;
        }
        document.getElementById('dropZoneText').innerHTML = `<i class="fa-solid fa-file-pdf me-2"></i> ${file.name}`;
        pdfIndicator.style.display = 'block';
        valResume.value = '';

        try {
            const fileReader = new FileReader();
            fileReader.onload = async function() {
                const typedarray = new Uint8Array(this.result);
                const pdf = await pdfjsLib.getDocument(typedarray).promise;
                let fullText = '';
                
                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const textContent = await page.getTextContent();
                    const pageText = textContent.items.map(item => item.str).join(' ');
                    fullText += pageText + '\n\n';
                }
                
                valResume.value = fullText.trim();
                pdfIndicator.style.display = 'none';
                updateSummary();
            };
            fileReader.readAsArrayBuffer(file);
        } catch (err) {
            console.error(err);
            pdfIndicator.innerHTML = '<i class="fa-solid fa-circle-exclamation text-danger me-1"></i> Error extracting text.';
        }
    }

    // Initial update
    window.onload = () => {
        applyApplicationSelection();
        applyPackSelection();
        applyPressureMode();
        updateSummary();
    };
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#panel-basic', popover: { title: 'Basic Information', description: 'Choose the interview category, target position, tracked job, and interview pack.', side: 'top', align: 'center' }},
            { element: '#valApplication', popover: { title: 'Tracked Application', description: 'Attach a job from Job Tracker to reuse its resume, job description, and role context.', side: 'bottom', align: 'start' }},
            { element: '#valPack', popover: { title: 'Interview Pack', description: 'Apply a ready-made company or role simulation with preset focus, difficulty, and questions.', side: 'bottom', align: 'start' }},
            { element: '#panel-advanced', popover: { title: 'Personalization', description: 'Add your resume, job description, or company context for role-specific questions.', side: 'top', align: 'center' }},
            { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Set difficulty, question count, and optional response timing before you start.', side: 'top', align: 'center' }},
            { element: '#panel-content', popover: { title: 'Content Focus', description: 'Pick the interview focus, assistance level, question types, and company style.', side: 'top', align: 'center' }},
            { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose typed, voice, or hybrid answers depending on how you want to practice.', side: 'top', align: 'center' }},
            { element: '#panel-summary', popover: { title: 'Live Summary', description: 'Confirm your interview setup before generating the practice session.', side: 'top', align: 'center' }},
            { element: '#btn-start-interview', popover: { title: 'Start Interview', description: 'Generate your customized mock interview when the setup looks right.', side: 'top', align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#panel-basic', popover: { title: 'Basic Information', description: 'Choose the interview category, target position, tracked job, and interview pack.', side: 'top', align: 'center' }},
            { element: '#valApplication', popover: { title: 'Tracked Application', description: 'Attach a job from Job Tracker to reuse its resume, job description, and role context.', side: 'bottom', align: 'start' }},
            { element: '#valPack', popover: { title: 'Interview Pack', description: 'Apply a ready-made company or role simulation with preset focus, difficulty, and questions.', side: 'bottom', align: 'start' }},
            { element: '#panel-advanced', popover: { title: 'Personalization', description: 'Add your resume, job description, or company context for role-specific questions.', side: 'top', align: 'center' }},
            { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Set difficulty, question count, and optional response timing before you start.', side: 'top', align: 'center' }},
            { element: '#panel-content', popover: { title: 'Content Focus', description: 'Pick the interview focus, assistance level, question types, and company style.', side: 'top', align: 'center' }},
            { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose typed, voice, or hybrid answers depending on how you want to practice.', side: 'top', align: 'center' }},
            { element: '#panel-summary', popover: { title: 'Live Summary', description: 'Confirm your interview setup before generating the practice session.', side: 'top', align: 'center' }},
            { element: '#btn-start-interview', popover: { title: 'Start Interview', description: 'Generate your customized mock interview when the setup looks right.', side: 'top', align: 'center' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_interview_setup',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
            beforeStart: () => {
                document.documentElement.style.setProperty('scroll-behavior', 'auto', 'important');
            },
            onBeforeDestroy: () => {
                document.documentElement.style.removeProperty('scroll-behavior');
            },
            onDestroyed: () => {
                document.documentElement.style.removeProperty('scroll-behavior');
            },
        });
    });
</script>
@endpush
@endsection
