@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Setup')
@section('content')
@php
    $categoryByTitle = ($categories ?? collect())->keyBy('title');
    $defaultCategory = $categoryByTitle->get('Job Interview') ?? ($categories ?? collect())->first();
    $scenarioDefinitions = [
        'ph_job_interview' => [
            'label' => 'Philippines General Job Interview',
            'focus' => 'Philippines Job Interview',
            'category_titles' => ['Job Interview'],
        ],
        'ph_bpo_communication' => [
            'label' => 'Philippines BPO / Customer Support Interview',
            'focus' => 'BPO / Customer Support Interview',
            'category_titles' => ['Communication', 'Job Interview'],
        ],
        'ph_it_programming' => [
            'label' => 'Philippines IT / Programming Interview',
            'focus' => 'IT / Programming Interview',
            'category_titles' => ['IT/Programming', 'Job Interview'],
        ],
        'ph_scholarship' => [
            'label' => 'Philippines Scholarship Interview',
            'focus' => 'Philippines Scholarship Interview',
            'category_titles' => ['Scholarship Interview', 'Job Interview'],
        ],
        'ph_college_admission' => [
            'label' => 'Philippines College Admission Interview',
            'focus' => 'Philippines College Admission Interview',
            'category_titles' => ['College Admission', 'Job Interview'],
        ],
    ];
    $scenarioOptions = collect($scenarioDefinitions)
        ->filter(fn ($definition, $key) => isset(($sourcePacks ?? [])[$key]))
        ->map(function ($definition, $key) use ($categoryByTitle, $defaultCategory, $sourcePacks) {
            $category = collect($definition['category_titles'])
                ->map(fn ($title) => $categoryByTitle->get($title))
                ->first()
                ?? $defaultCategory;
            $pack = $sourcePacks[$key];

            return array_merge($definition, [
                'key' => $key,
                'category_id' => $category?->id,
                'context_label' => $definition['label'],
                'source_summary' => collect($pack['sources'] ?? [])->pluck('name')->take(3)->implode(', '),
            ]);
        })
        ->values();
    $selectedSourcePackKey = old('source_pack_key', 'ph_job_interview');
    $selectedScenario = $scenarioOptions->firstWhere('key', $selectedSourcePackKey) ?? $scenarioOptions->first();
    $setupDefaults = [
        'difficulty' => old('difficulty', 'medium'),
        'num_questions' => (string) old('num_questions', 10),
        'time_limit' => (string) old('time_limit', 0),
        'interview_focus' => old('interview_focus', $selectedScenario['focus'] ?? 'Philippines Job Interview'),
        'ai_assistance_level' => old('ai_assistance_level', 'standard'),
        'interviewer_strictness' => old('interviewer_strictness', 'neutral'),
        'live_feedback_mode' => old('live_feedback_mode', 'coaching'),
        'response_mode' => old('response_mode', 'voice'),
        'company_persona' => old('company_persona', 'Philippines hiring context'),
        'interview_format' => old('interview_format', 'standard'),
    ];
    $selectedQuestionTypes = old('question_types', ['Behavioral', 'Situational']);
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
        margin-bottom: 14px;
        border: 1px solid rgba(96, 165, 250, 0.26);
        border-radius: 16px;
        background:
            radial-gradient(circle at 92% 35%, rgba(96, 165, 250, 0.2), transparent 25%),
            linear-gradient(110deg, rgba(59, 130, 246, 0.12), rgba(6, 182, 212, 0.045)),
            var(--sf);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        position: relative;
        isolation: isolate;
    }
    .setup-hero::before {
        content: none;
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

    #panel-summary {
        max-width: 360px;
        margin-left: auto;
        margin-right: auto;
        padding: 18px !important;
        border-radius: 26px !important;
        background:
            radial-gradient(circle at 12% 8%, rgba(219, 234, 254, 0.78), transparent 28%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.92)) !important;
        border: 1px solid rgba(226, 232, 240, 0.96) !important;
        box-shadow: 0 20px 46px rgba(15, 23, 42, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.95) !important;
    }
    #panel-summary h5 {
        display: flex;
        align-items: center;
        gap: 14px;
        color: #0f172a !important;
        text-align: left !important;
        font-size: 1.3rem !important;
        line-height: 1.15;
        margin-bottom: 18px !important;
        letter-spacing: 0 !important;
    }
    #panel-summary h5 i {
        width: 44px;
        height: 44px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #123a7a;
        background: #e8f1ff;
        font-size: 1rem;
        flex: 0 0 auto;
    }
    .summary-row {
        display: grid;
        grid-template-columns: 34px minmax(0, 0.9fr) minmax(0, 1.1fr);
        gap: 10px;
        align-items: center;
        min-height: 54px;
        padding: 9px 0;
        border-bottom: 1px solid #e2e8f0;
        font-size: .85rem;
    }
    .summary-row:last-child { border-bottom:none; }
    .summary-icon {
        width: 28px;
        height: 28px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: #eaf3ff;
        font-size: 0.82rem;
    }
    .summary-label {
        color:#334155;
        font-weight:500;
        line-height: 1.2;
    }
    .summary-val {
        color:#0f172a;
        font-weight:800;
        text-align:right;
        line-height: 1.24;
        overflow-wrap: anywhere;
    }
    .summary-val.text-success {
        color: #16a34a !important;
    }
    #panel-summary .setup-start-action {
        margin-top: 16px !important;
    }
    #panel-summary #btn-start-interview {
        min-height: 50px;
        border-radius: 14px !important;
        background: linear-gradient(135deg, #1687ff, #0757ff) !important;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.26) !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 0.98rem !important;
        line-height: 1.15;
        padding: 12px 14px !important;
        white-space: normal;
    }
    #panel-summary #btn-start-interview i {
        margin-left: 0 !important;
    }
    .finish-transition-overlay {
        position: fixed;
        inset: 0;
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
        text-align: center;
        transition: opacity 0.3s ease, visibility 0.3s ease;
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
    @keyframes finishSpin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @keyframes finishPulse {
        0% { transform: scale(0.9); opacity: 0.8; }
        50% { transform: scale(1.1); opacity: 1; }
        100% { transform: scale(0.9); opacity: 0.8; }
    }
    :root:not(.lm) #panel-summary {
        background:
            radial-gradient(circle at 12% 8%, rgba(37, 99, 235, 0.2), transparent 28%),
            linear-gradient(145deg, rgba(15, 23, 42, 0.98), rgba(30, 41, 59, 0.93)) !important;
        border-color: rgba(96, 165, 250, 0.25) !important;
    }
    :root:not(.lm) #panel-summary h5,
    :root:not(.lm) .summary-val { color: #f8fafc !important; }
    :root:not(.lm) .summary-label { color: #cbd5e1; }
    :root:not(.lm) .summary-row { border-bottom-color: rgba(148, 163, 184, 0.22); }
    :root:not(.lm) .summary-icon {
        color: #60a5fa;
        background: rgba(96, 165, 250, 0.13);
    }

    .setup-chip-panel {
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 14px;
        background: var(--bg3);
    }
    #panel-content.setup-assistance-card {
        max-width: 680px;
        margin-left: auto;
        margin-right: auto;
        padding: clamp(18px, 2.5vw, 28px);
        border-radius: 24px;
        border-color: rgba(203, 213, 225, 0.72);
        background:
            radial-gradient(circle at 10% 5%, rgba(254, 226, 226, 0.7), transparent 16%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.96));
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.09);
    }
    :root:not(.lm) #panel-content.setup-assistance-card {
        background:
            radial-gradient(circle at 10% 5%, rgba(248, 113, 113, 0.14), transparent 16%),
            linear-gradient(145deg, rgba(15, 23, 42, 0.96), rgba(17, 24, 39, 0.94));
        border-color: rgba(96, 165, 250, 0.24);
    }
    .assistance-head {
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 26px;
    }
    .assistance-head-icon {
        width: 58px;
        height: 58px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ef4444;
        background: #fee2e2;
        font-size: 1.6rem;
        flex: 0 0 auto;
    }
    .assistance-title {
        color: #0f172a;
        font-size: clamp(1.45rem, 2.5vw, 2rem) !important;
        font-weight: 900;
        line-height: 1.12;
        margin: 0 !important;
    }
    :root:not(.lm) .assistance-title { color: #f8fafc; }
    .assistance-stack {
        display: grid;
        gap: 22px;
    }
    .assistance-field {
        min-width: 0;
    }
    .assistance-field .olbl {
        color: #26334f;
        font-size: 1rem;
        font-weight: 900;
        margin-bottom: 10px;
        letter-spacing: 0;
    }
    :root:not(.lm) .assistance-field .olbl { color: #e2e8f0; }
    .assistance-select-wrap {
        position: relative;
    }
    .assistance-select-wrap::after {
        content: "";
        position: absolute;
        top: 50%;
        right: 20px;
        width: 10px;
        height: 10px;
        border-right: 3px solid #111827;
        border-bottom: 3px solid #111827;
        transform: translateY(-65%) rotate(45deg);
        pointer-events: none;
    }
    .assistance-select-wrap select.oinp {
        appearance: none;
        -webkit-appearance: none;
        min-height: 58px;
        border-radius: 15px;
        border-color: rgba(148, 163, 184, 0.42);
        background: rgba(255, 255, 255, 0.88);
        background-image: none;
        color: #0f172a;
        font-size: 1rem;
        padding: 13px 48px 13px 18px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    :root:not(.lm) .assistance-select-wrap select.oinp {
        background: rgba(15, 23, 42, 0.74);
        border-color: rgba(148, 163, 184, 0.28);
        color: #f8fafc;
    }
    :root:not(.lm) .assistance-select-wrap::after {
        border-color: #e2e8f0;
    }
    .assistance-question-list {
        display: grid;
        gap: 12px;
    }
    .assistance-question-card {
        display: grid;
        grid-template-columns: 24px 46px minmax(0, 1fr);
        align-items: center;
        gap: 16px;
        min-height: 72px;
        padding: 14px 18px;
        border: 1px solid rgba(203, 213, 225, 0.76);
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.78);
        color: #0f172a;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.25;
        cursor: pointer;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, transform 0.2s ease;
    }
    .assistance-question-card:hover {
        transform: translateY(-1px);
        border-color: rgba(59, 130, 246, 0.64);
    }
    .assistance-question-card:has(input[type="checkbox"]:checked) {
        border-color: rgba(37, 99, 235, 0.85);
        background: linear-gradient(145deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.9));
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.09);
    }
    .assistance-question-card input[type="checkbox"] {
        width: 22px;
        height: 22px;
        margin: 0;
        accent-color: #2563eb;
    }
    .assistance-question-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: linear-gradient(145deg, #e0edff, #f4f8ff);
        font-size: 1.1rem;
    }
    .assistance-question-text {
        min-width: 0;
        overflow-wrap: anywhere;
    }
    :root:not(.lm) .assistance-question-card {
        background: rgba(15, 23, 42, 0.74);
        border-color: rgba(148, 163, 184, 0.28);
        color: #f8fafc;
    }
    :root:not(.lm) .assistance-question-card:has(input[type="checkbox"]:checked) {
        background: rgba(37, 99, 235, 0.12);
    }
    .assistance-context-panel {
        display: grid;
        grid-template-columns: 62px minmax(0, 1fr);
        align-items: center;
        gap: 16px;
        padding: 18px;
        border: 1px solid rgba(147, 197, 253, 0.78);
        border-radius: 18px;
        background:
            radial-gradient(circle at 7% 18%, rgba(219, 234, 254, 0.88), transparent 24%),
            linear-gradient(145deg, rgba(239, 246, 255, 0.94), rgba(255, 255, 255, 0.88));
    }
    .assistance-context-icon {
        width: 52px;
        height: 52px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: #dbeafe;
        font-size: 1.45rem;
    }
    .assistance-context-title {
        display: block;
        color: #0f172a;
        font-size: 1.08rem;
        font-weight: 900;
        line-height: 1.2;
        margin-bottom: 5px;
    }
    .assistance-context-panel .desc-text {
        color: #475569;
        font-size: 0.86rem;
        line-height: 1.45;
        margin: 0;
    }
    :root:not(.lm) .assistance-context-panel {
        background: rgba(37, 99, 235, 0.1);
        border-color: rgba(96, 165, 250, 0.36);
    }
    :root:not(.lm) .assistance-context-title { color: #f8fafc; }
    :root:not(.lm) .assistance-context-panel .desc-text { color: #cbd5e1; }
    #panel-response.setup-response-card {
        max-width: 680px;
        margin-left: auto;
        margin-right: auto;
        padding: clamp(18px, 2.5vw, 28px);
        border-radius: 24px;
        border-color: rgba(203, 213, 225, 0.72);
        background:
            radial-gradient(circle at 10% 8%, rgba(219, 234, 254, 0.7), transparent 16%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.96));
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.09);
    }
    :root:not(.lm) #panel-response.setup-response-card {
        background:
            radial-gradient(circle at 10% 8%, rgba(59, 130, 246, 0.16), transparent 16%),
            linear-gradient(145deg, rgba(15, 23, 42, 0.96), rgba(17, 24, 39, 0.94));
        border-color: rgba(96, 165, 250, 0.24);
    }
    .response-head {
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 28px;
    }
    .response-head-icon {
        width: 58px;
        height: 58px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: linear-gradient(145deg, #eff6ff, #dbeafe);
        font-size: 1.7rem;
        flex: 0 0 auto;
    }
    .response-title {
        color: #0f172a;
        font-size: clamp(1.45rem, 2.5vw, 2rem) !important;
        font-weight: 900;
        line-height: 1.12;
        margin: 0 !important;
    }
    :root:not(.lm) .response-title { color: #f8fafc; }
    .response-mode-list {
        display: grid;
        gap: 14px;
    }
    .response-mode-card {
        display: grid;
        grid-template-columns: 30px minmax(0, 1fr);
        align-items: center;
        gap: 26px;
        min-height: 104px;
        padding: 20px 26px;
        border: 1px solid rgba(203, 213, 225, 0.76);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.78);
        cursor: pointer;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease, transform 0.2s ease;
    }
    .response-mode-card:hover {
        transform: translateY(-1px);
        border-color: rgba(59, 130, 246, 0.64);
    }
    .response-mode-card:has(input[type="radio"]:checked) {
        border-color: rgba(37, 99, 235, 0.86);
        background: linear-gradient(145deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.9));
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.09);
    }
    .response-mode-card input[type="radio"] {
        width: 26px;
        height: 26px;
        margin: 0;
        accent-color: #2563eb;
    }
    .response-mode-title {
        display: block;
        color: #0f172a;
        font-size: 1.22rem;
        font-weight: 900;
        line-height: 1.15;
        margin-bottom: 7px;
    }
    .response-mode-desc {
        display: block;
        color: #64748b;
        font-size: 1rem;
        line-height: 1.35;
    }
    :root:not(.lm) .response-mode-card {
        background: rgba(15, 23, 42, 0.74);
        border-color: rgba(148, 163, 184, 0.28);
    }
    :root:not(.lm) .response-mode-card:has(input[type="radio"]:checked) {
        background: rgba(37, 99, 235, 0.12);
    }
    :root:not(.lm) .response-mode-title { color: #f8fafc; }
    :root:not(.lm) .response-mode-desc { color: #cbd5e1; }
    #panel-basic.setup-details-card {
        padding: clamp(24px, 3.2vw, 42px);
        border-radius: 24px;
        border-color: rgba(203, 213, 225, 0.7);
        background:
            radial-gradient(circle at 90% 8%, rgba(219, 234, 254, 0.42), transparent 18%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.96));
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
    }
    :root:not(.lm) #panel-basic.setup-details-card {
        background:
            radial-gradient(circle at 90% 8%, rgba(59, 130, 246, 0.16), transparent 18%),
            linear-gradient(145deg, rgba(15, 23, 42, 0.96), rgba(17, 24, 39, 0.94));
        border-color: rgba(96, 165, 250, 0.24);
    }
    .setup-details-card-head {
        display: grid;
        grid-template-columns: 88px minmax(0, 1fr);
        gap: 24px;
        align-items: start;
        padding-bottom: 30px;
        margin-bottom: 32px;
        border-bottom: 1px solid rgba(191, 219, 254, 0.86);
    }
    .setup-details-icon,
    .setup-card-label-icon,
    .setup-calibrated-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: linear-gradient(145deg, #eff6ff, #dbeafe);
        flex: 0 0 auto;
    }
    .setup-details-icon {
        width: 88px;
        height: 88px;
        border-radius: 24px;
        font-size: 2.45rem;
    }
    .setup-details-card-title {
        color: #0f172a;
        font-size: clamp(1.8rem, 3vw, 2.7rem) !important;
        font-weight: 900;
        line-height: 1.12;
        margin: 0 0 16px !important;
    }
    .setup-details-card-subtitle {
        max-width: 740px;
        color: #64748b;
        font-size: clamp(1.08rem, 1.65vw, 1.55rem);
        line-height: 1.52;
        margin: 0;
    }
    :root:not(.lm) .setup-details-card-title { color: #f8fafc; }
    :root:not(.lm) .setup-details-card-subtitle { color: #cbd5e1; }
    .setup-card-fields {
        display: grid;
        gap: 34px;
    }
    .setup-card-label {
        display: inline-flex;
        align-items: center;
        gap: 18px;
        color: #0f172a;
        font-size: clamp(1.2rem, 1.9vw, 1.8rem);
        font-weight: 900;
        line-height: 1.15;
        margin-bottom: 18px;
    }
    :root:not(.lm) .setup-card-label { color: #f8fafc; }
    .setup-card-label-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        font-size: 1.22rem;
    }
    .setup-select-wrap,
    .setup-search-wrap {
        position: relative;
    }
    .setup-select-wrap::after {
        content: "";
        position: absolute;
        top: 50%;
        right: 26px;
        width: 12px;
        height: 12px;
        border-right: 3px solid #0f172a;
        border-bottom: 3px solid #0f172a;
        transform: translateY(-65%) rotate(45deg);
        pointer-events: none;
        z-index: 2;
    }
    #panel-basic.setup-details-card .oinp {
        min-height: 76px;
        border-radius: 18px;
        border-color: rgba(148, 163, 184, 0.44);
        background: rgba(255, 255, 255, 0.92);
        color: #0f172a;
        font-size: clamp(1rem, 1.55vw, 1.45rem);
        padding: 16px 24px;
    }
    #panel-basic.setup-details-card select.oinp {
        appearance: none;
        -webkit-appearance: none;
        padding-left: 24px;
        padding-right: 62px;
        background-image: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #panel-basic.setup-details-card input.oinp {
        padding-left: 24px;
    }
    #panel-basic.setup-details-card .oinp::placeholder {
        color: #64748b;
    }
    #panel-basic.setup-details-card .desc-text {
        color: #64748b;
        font-size: clamp(0.96rem, 1.25vw, 1.28rem);
        line-height: 1.55;
        margin-top: 22px;
    }
    .setup-calibrated-simple {
        display: grid;
        grid-template-columns: 86px minmax(0, 1fr);
        gap: 28px;
        align-items: start;
        padding: clamp(24px, 3vw, 34px);
        border: 1px solid rgba(191, 219, 254, 0.86);
        border-radius: 18px;
        background:
            radial-gradient(circle at 8% 15%, rgba(219, 234, 254, 0.72), transparent 25%),
            linear-gradient(145deg, rgba(239, 246, 255, 0.92), rgba(255, 255, 255, 0.86));
    }
    .setup-calibrated-icon {
        width: 72px;
        height: 72px;
        border-radius: 22px;
        font-size: 2rem;
    }
    .setup-calibrated-simple h6 {
        color: #0f172a;
        font-size: clamp(1.28rem, 2vw, 2rem) !important;
        font-weight: 900;
        line-height: 1.2;
        margin: 0 0 16px !important;
    }
    .setup-calibrated-simple p {
        color: #64748b;
        font-size: clamp(1rem, 1.35vw, 1.36rem);
        line-height: 1.55;
        margin: 0;
    }
    .setup-calibrated-simple strong {
        color: #475569;
        font-weight: 900;
    }
    :root:not(.lm) #panel-basic.setup-details-card .oinp {
        background: rgba(15, 23, 42, 0.74);
        border-color: rgba(148, 163, 184, 0.28);
        color: #f8fafc;
    }
    :root:not(.lm) #panel-basic.setup-details-card select.oinp {
        background-image: none;
    }
    :root:not(.lm) .setup-select-wrap::after {
        border-color: #e2e8f0;
    }
    :root:not(.lm) .setup-calibrated-simple {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.86), rgba(15, 23, 42, 0.72));
        border-color: rgba(96, 165, 250, 0.3);
    }
    :root:not(.lm) .setup-calibrated-simple h6 { color: #f8fafc; }
    :root:not(.lm) .setup-calibrated-simple p,
    :root:not(.lm) .setup-calibrated-simple strong,
    :root:not(.lm) #panel-basic.setup-details-card .desc-text { color: #cbd5e1; }

    #panel-structure.setup-structure-card {
        padding: clamp(22px, 3vw, 36px);
        border-radius: 24px;
        border-color: rgba(203, 213, 225, 0.7);
        background:
            radial-gradient(circle at 90% 8%, rgba(219, 234, 254, 0.38), transparent 18%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.96));
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
    }
    :root:not(.lm) #panel-structure.setup-structure-card {
        background: linear-gradient(145deg, rgba(15, 23, 42, 0.96), rgba(17, 24, 39, 0.94));
        border-color: rgba(96, 165, 250, 0.24);
    }
    .setup-structure-head {
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 28px;
    }
    .setup-structure-head-icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: linear-gradient(145deg, #eff6ff, #dbeafe);
        font-size: 1.65rem;
        flex: 0 0 auto;
    }
    .setup-structure-title {
        color: #0f172a;
        font-size: clamp(1.55rem, 2.6vw, 2.3rem) !important;
        font-weight: 900;
        line-height: 1.1;
        margin: 0 !important;
    }
    :root:not(.lm) .setup-structure-title { color: #f8fafc; }
    .setup-structure-section-title {
        color: #0f172a;
        font-size: clamp(1.02rem, 1.45vw, 1.32rem);
        font-weight: 900;
        line-height: 1.2;
        margin: 0 0 14px;
    }
    :root:not(.lm) .setup-structure-section-title { color: #f8fafc; }
    .structure-difficulty-list {
        display: grid;
        gap: 12px;
        margin-bottom: 26px;
    }
    .structure-difficulty-card {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr) 52px;
        align-items: center;
        gap: 16px;
        min-height: 86px;
        padding: 16px 20px;
        border: 1px solid rgba(203, 213, 225, 0.72);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.78);
        cursor: pointer;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }
    .structure-difficulty-card:hover {
        transform: translateY(-1px);
        border-color: rgba(96, 165, 250, 0.55);
    }
    .structure-difficulty-card:has(input[type="radio"]:checked) {
        border-color: rgba(59, 130, 246, 0.78);
        box-shadow: 0 10px 26px rgba(37, 99, 235, 0.1);
        background: linear-gradient(145deg, rgba(239, 246, 255, 0.95), rgba(255, 255, 255, 0.86));
    }
    .structure-difficulty-card input[type="radio"] {
        width: 24px;
        height: 24px;
        margin: 0;
        accent-color: #3b82f6;
    }
    .structure-difficulty-title {
        display: block;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 900;
        line-height: 1.15;
        margin-bottom: 5px;
    }
    .structure-difficulty-desc {
        display: block;
        color: #64748b;
        font-size: 0.92rem;
        line-height: 1.3;
    }
    .structure-difficulty-icon {
        width: 50px;
        height: 50px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: linear-gradient(145deg, #e0edff, #f4f8ff);
        border: 1px solid rgba(147, 197, 253, 0.45);
        font-size: 1.18rem;
    }
    .structure-difficulty-icon i {
        color: currentColor;
        line-height: 1;
    }
    :root:not(.lm) .structure-difficulty-card {
        background: rgba(15, 23, 42, 0.74);
        border-color: rgba(148, 163, 184, 0.28);
    }
    :root:not(.lm) .structure-difficulty-title { color: #f8fafc; }
    :root:not(.lm) .structure-difficulty-desc { color: #cbd5e1; }
    .structure-select-grid {
        display: grid;
        gap: 18px;
    }
    .structure-select-wrap {
        position: relative;
    }
    .structure-select-wrap::after {
        content: "";
        position: absolute;
        top: 50%;
        right: 22px;
        width: 10px;
        height: 10px;
        border-right: 2px solid #475569;
        border-bottom: 2px solid #475569;
        transform: translateY(-65%) rotate(45deg);
        pointer-events: none;
    }
    .structure-select-wrap select.oinp {
        appearance: none;
        -webkit-appearance: none;
        min-height: 58px;
        border-radius: 16px;
        background-image: none;
        background-color: rgba(255, 255, 255, 0.88);
        border-color: rgba(148, 163, 184, 0.42);
        color: #0f172a;
        font-size: 1rem;
        padding: 13px 48px 13px 18px;
    }
    .structure-info-note {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 14px;
        align-items: center;
        margin-top: 22px;
        padding: 16px 18px;
        border: 1px solid rgba(191, 219, 254, 0.8);
        border-radius: 16px;
        background: linear-gradient(145deg, rgba(239, 246, 255, 0.9), rgba(255, 255, 255, 0.84));
        color: #475569;
        font-size: 0.94rem;
        line-height: 1.45;
    }
    .structure-info-note i {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: #dbeafe;
    }

    #panel-inclusive.setup-inclusive-card {
        padding: clamp(22px, 3vw, 36px);
        border-radius: 24px;
        border-color: rgba(203, 213, 225, 0.7);
        background:
            radial-gradient(circle at 88% 10%, rgba(219, 234, 254, 0.34), transparent 18%),
            linear-gradient(145deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.96));
        box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
    }
    :root:not(.lm) #panel-inclusive.setup-inclusive-card {
        background: linear-gradient(145deg, rgba(15, 23, 42, 0.96), rgba(17, 24, 39, 0.94));
        border-color: rgba(96, 165, 250, 0.24);
    }
    .setup-inclusive-head {
        display: flex;
        align-items: center;
        gap: 18px;
        margin-bottom: 22px;
    }
    .setup-inclusive-head-icon {
        width: 58px;
        height: 58px;
        border-radius: 999px;
        border: 1px solid rgba(147, 197, 253, 0.72);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: linear-gradient(145deg, #eff6ff, #f8fbff);
        font-size: 1.65rem;
        flex: 0 0 auto;
    }
    .setup-inclusive-title {
        color: #0f172a;
        font-size: clamp(1.42rem, 2.4vw, 2.1rem) !important;
        font-weight: 900;
        line-height: 1.12;
        margin: 0 !important;
    }
    .setup-inclusive-copy {
        color: #475569;
        font-size: clamp(0.98rem, 1.45vw, 1.28rem);
        line-height: 1.58;
        margin: 0 0 26px;
    }
    :root:not(.lm) .setup-inclusive-title { color: #f8fafc; }
    :root:not(.lm) .setup-inclusive-copy { color: #cbd5e1; }
    .inclusive-option-list {
        display: grid;
        gap: 12px;
    }
    .inclusive-option {
        display: grid;
        grid-template-columns: 28px minmax(0, 1fr);
        align-items: center;
        gap: 18px;
        min-height: 64px;
        padding: 14px 18px;
        border: 1px solid rgba(203, 213, 225, 0.72);
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.78);
        color: #0f172a;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.25;
        cursor: pointer;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }
    .inclusive-option input[type="checkbox"] {
        width: 24px;
        height: 24px;
        margin: 0;
        accent-color: #2563eb;
    }
    .inclusive-option:has(input[type="checkbox"]:checked) {
        border-color: rgba(37, 99, 235, 0.7);
        background: linear-gradient(145deg, rgba(239, 246, 255, 0.96), rgba(255, 255, 255, 0.9));
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
    }
    :root:not(.lm) .inclusive-option {
        background: rgba(15, 23, 42, 0.74);
        border-color: rgba(148, 163, 184, 0.28);
        color: #f8fafc;
    }
    .inclusive-note {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 14px;
        align-items: start;
        margin-top: 18px;
        padding: 16px 18px;
        border: 1px solid rgba(191, 219, 254, 0.82);
        border-radius: 16px;
        background: linear-gradient(145deg, rgba(239, 246, 255, 0.92), rgba(255, 255, 255, 0.86));
        color: #1e3a8a;
        font-size: 0.92rem;
        line-height: 1.48;
    }
    .inclusive-note i {
        width: 38px;
        height: 38px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background: #3b82f6;
    }
    .inclusive-note strong {
        color: #1d4ed8;
        font-weight: 900;
    }

    /* Interview setup contrast guard for both day and night themes. */
    #sec-interview-setup {
        --setup-card-bg: rgba(255, 255, 255, 0.94);
        --setup-card-bg-soft: rgba(248, 250, 252, 0.96);
        --setup-card-bg-selected: rgba(239, 246, 255, 0.98);
        --setup-input-bg: rgba(255, 255, 255, 0.96);
        --setup-text: #0f172a;
        --setup-text-muted: #475569;
        --setup-text-soft: #64748b;
        --setup-icon-fg: #1d4ed8;
        --setup-icon-bg: #dbeafe;
        --setup-border: rgba(148, 163, 184, 0.46);
        --setup-border-strong: rgba(37, 99, 235, 0.72);
        color: var(--setup-text);
    }
    :root:not(.lm) #sec-interview-setup {
        --setup-card-bg: rgba(15, 23, 42, 0.86);
        --setup-card-bg-soft: rgba(30, 41, 59, 0.78);
        --setup-card-bg-selected: rgba(37, 99, 235, 0.2);
        --setup-input-bg: rgba(15, 23, 42, 0.9);
        --setup-text: #f8fafc;
        --setup-text-muted: #dbeafe;
        --setup-text-soft: #cbd5e1;
        --setup-icon-fg: #0f172a;
        --setup-icon-bg: #dbeafe;
        --setup-border: rgba(148, 163, 184, 0.34);
        --setup-border-strong: rgba(96, 165, 250, 0.68);
        color: var(--setup-text);
    }
    #sec-interview-setup :where(
        .setup-details-card-title,
        .setup-structure-title,
        .setup-structure-section-title,
        .setup-inclusive-title,
        .assistance-title,
        .response-title,
        .setup-card-label,
        .structure-difficulty-title,
        .assistance-question-text,
        .response-mode-title,
        .assistance-context-title,
        .setup-calibrated-simple h6,
        .inclusive-option,
        .summary-val
    ) {
        color: var(--setup-text) !important;
        -webkit-text-fill-color: currentColor;
    }
    #sec-interview-setup :where(
        .setup-hero-subtitle,
        .setup-details-card-subtitle,
        .desc-text,
        .structure-difficulty-desc,
        .setup-inclusive-copy,
        .response-mode-desc,
        .assistance-context-panel .desc-text,
        .setup-calibrated-simple p,
        .setup-calibrated-simple strong,
        .summary-label
    ) {
        color: var(--setup-text-soft) !important;
        -webkit-text-fill-color: currentColor;
    }
    #sec-interview-setup :where(
        .oinp,
        .structure-select-wrap select.oinp,
        .assistance-select-wrap select.oinp,
        #panel-basic.setup-details-card .oinp
    ) {
        background-color: var(--setup-input-bg) !important;
        border-color: var(--setup-border) !important;
        color: var(--setup-text) !important;
        -webkit-text-fill-color: currentColor;
        caret-color: var(--setup-text);
    }
    #sec-interview-setup :where(.oinp, select.oinp) option {
        background: var(--setup-card-bg) !important;
        color: var(--setup-text) !important;
    }
    #sec-interview-setup .oinp::placeholder {
        color: var(--setup-text-soft) !important;
        opacity: 1;
        -webkit-text-fill-color: var(--setup-text-soft);
    }
    #sec-interview-setup :where(
        .setup-details-icon,
        .setup-card-label-icon,
        .setup-calibrated-icon,
        .setup-structure-head-icon,
        .setup-inclusive-head-icon,
        .assistance-head-icon,
        .response-head-icon,
        .structure-difficulty-icon,
        .assistance-question-icon,
        .assistance-context-icon,
        .summary-icon
    ) {
        background: var(--setup-icon-bg) !important;
        border-color: var(--setup-border) !important;
        color: var(--setup-icon-fg) !important;
    }
    #sec-interview-setup :where(
        .setup-details-icon,
        .setup-card-label-icon,
        .setup-calibrated-icon,
        .setup-structure-head-icon,
        .setup-inclusive-head-icon,
        .assistance-head-icon,
        .response-head-icon,
        .structure-difficulty-icon,
        .assistance-question-icon,
        .assistance-context-icon,
        .summary-icon
    ) i,
    #sec-interview-setup :where(
        .setup-details-icon,
        .setup-card-label-icon,
        .setup-calibrated-icon,
        .setup-structure-head-icon,
        .setup-inclusive-head-icon,
        .assistance-head-icon,
        .response-head-icon,
        .structure-difficulty-icon,
        .assistance-question-icon,
        .assistance-context-icon,
        .summary-icon
    ) svg {
        color: var(--setup-icon-fg) !important;
        fill: currentColor !important;
        -webkit-text-fill-color: currentColor !important;
    }
    #sec-interview-setup :where(
        .structure-difficulty-card,
        .inclusive-option,
        .assistance-question-card,
        .response-mode-card,
        .setup-calibrated-simple,
        .structure-info-note,
        .inclusive-note,
        .assistance-context-panel
    ) {
        background: var(--setup-card-bg) !important;
        border-color: var(--setup-border) !important;
        color: var(--setup-text) !important;
    }
    #sec-interview-setup :where(
        .structure-difficulty-card,
        .inclusive-option,
        .assistance-question-card,
        .response-mode-card
    ):has(input:checked) {
        background: var(--setup-card-bg-selected) !important;
        border-color: var(--setup-border-strong) !important;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.14) !important;
    }
    #sec-interview-setup :where(.structure-info-note, .inclusive-note) {
        color: var(--setup-text-muted) !important;
    }
    #sec-interview-setup :where(.structure-info-note, .inclusive-note) strong {
        color: var(--setup-text) !important;
    }
    #sec-interview-setup .setup-select-wrap::after,
    #sec-interview-setup .structure-select-wrap::after,
    #sec-interview-setup .assistance-select-wrap::after {
        border-color: var(--setup-text-muted) !important;
    }
    :root:not(.lm) #sec-interview-setup .text-gradient-primary {
        background: none !important;
        -webkit-background-clip: initial;
        background-clip: initial;
        -webkit-text-fill-color: #dbeafe !important;
        color: #dbeafe !important;
    }

    #sec-interview-setup .custom-radio,
    #sec-interview-setup .custom-cbx {
        margin-bottom: 0;
        min-height: 100%;
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
            margin-bottom: 12px !important;
            border-radius: 14px !important;
            min-height: 104px !important;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08) !important;
        }
        #sec-interview-setup .setup-hero::after {
            border-radius: 0;
        }
        #sec-interview-setup .setup-hero-inner {
            justify-content: flex-start !important;
            align-items: center !important;
            min-height: 104px !important;
            padding: 14px 96px 14px 14px !important;
        }
        #sec-interview-setup .setup-hero-title {
            justify-content: flex-start !important;
            align-items: center !important;
            gap: 7px !important;
            font-size: 0.92rem !important;
            margin-bottom: 4px;
            letter-spacing: 0;
            white-space: nowrap;
        }
        #sec-interview-setup .setup-hero-title svg {
            width: 20px !important;
            height: 20px !important;
            padding: 0;
            border-radius: 0;
        }
        #sec-interview-setup .setup-hero-subtitle {
            max-width: 100% !important;
            font-size: 0.74rem !important;
            line-height: 1.4 !important;
        }
        #sec-interview-setup .setup-hero-art {
            right: -10px !important;
            bottom: -1px !important;
            width: 112px !important;
        }
        #sec-interview-setup .setup-panel {
            border-radius: 14px !important;
            padding: 14px !important;
            margin-bottom: 0;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        #sec-interview-setup .setup-panel:hover,
        #sec-interview-setup .custom-radio:hover,
        #sec-interview-setup .custom-cbx:hover {
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
        #panel-basic.setup-details-card {
            padding: 12px !important;
            border-radius: 13px !important;
        }
        #panel-basic .setup-details-card-head {
            grid-template-columns: 28px minmax(0, 1fr);
            gap: 8px;
            padding-bottom: 13px;
            margin-bottom: 15px;
        }
        #panel-basic .setup-details-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            font-size: 0.82rem;
        }
        #panel-basic .setup-details-card-title {
            font-size: clamp(0.98rem, 4.7vw, 1.18rem) !important;
            line-height: 1.16;
            margin-bottom: 5px !important;
            white-space: nowrap;
        }
        #panel-basic .setup-details-card-subtitle {
            font-size: 0.66rem;
            line-height: 1.3;
        }
        #panel-basic .setup-card-fields {
            gap: 14px;
        }
        #panel-basic .setup-card-label {
            gap: 8px;
            font-size: 0.82rem;
            margin-bottom: 8px;
        }
        #panel-basic .setup-card-label-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            font-size: 0.68rem;
        }
        #panel-basic.setup-details-card .oinp {
            min-height: 34px;
            border-radius: 9px;
            font-size: 0.68rem;
            padding-top: 7px;
            padding-bottom: 7px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #panel-basic.setup-details-card select.oinp {
            padding-left: 14px;
            padding-right: 42px;
            background-image: none;
        }
        #panel-basic .setup-select-wrap::after {
            right: 17px;
            width: 8px;
            height: 8px;
            border-width: 2px;
        }
        #panel-basic.setup-details-card input.oinp {
            padding-left: 14px;
        }
        #panel-basic.setup-details-card .desc-text {
            font-size: 0.63rem;
            line-height: 1.32;
            margin-top: 8px;
        }
        #panel-basic .setup-calibrated-simple {
            grid-template-columns: 30px minmax(0, 1fr);
            gap: 8px;
            align-items: flex-start;
            padding: 11px;
            border-radius: 12px;
        }
        #panel-basic .setup-calibrated-icon {
            width: 24px;
            height: 24px;
            border-radius: 7px;
            font-size: 0.66rem;
        }
        #panel-basic .setup-calibrated-simple h6 {
            font-size: 0.82rem !important;
            margin-bottom: 4px !important;
        }
        #panel-basic .setup-calibrated-simple p {
            font-size: 0.64rem;
            line-height: 1.36;
            overflow-wrap: anywhere;
        }
        #panel-structure.setup-structure-card {
            padding: 12px !important;
            border-radius: 13px !important;
        }
        #panel-structure .setup-structure-head {
            gap: 8px;
            margin-bottom: 14px;
        }
        #panel-structure .setup-structure-head-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            font-size: 0.78rem;
        }
        #panel-structure .setup-structure-title {
            font-size: 1rem !important;
            white-space: nowrap;
        }
        #panel-structure .setup-structure-section-title {
            font-size: 0.78rem;
            margin-bottom: 8px;
        }
        #panel-structure .structure-difficulty-list {
            gap: 8px;
            margin-bottom: 16px;
        }
        #panel-structure .structure-difficulty-card {
            grid-template-columns: 22px minmax(0, 1fr) 30px;
            gap: 8px;
            min-height: 50px;
            padding: 8px 10px;
            border-radius: 11px;
        }
        #panel-structure .structure-difficulty-card input[type="radio"] {
            width: 16px;
            height: 16px;
        }
        #panel-structure .structure-difficulty-title {
            font-size: 0.78rem;
            margin-bottom: 2px;
        }
        #panel-structure .structure-difficulty-desc {
            font-size: 0.6rem;
            line-height: 1.22;
        }
        #panel-structure .structure-difficulty-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            font-size: 0.64rem;
        }
        #panel-structure .structure-select-grid {
            gap: 12px;
        }
        #panel-structure .olbl {
            font-size: 0.68rem;
            margin-bottom: 6px;
        }
        #panel-structure .structure-select-wrap select.oinp {
            min-height: 36px;
            border-radius: 9px;
            font-size: 0.68rem;
            padding: 7px 34px 7px 10px;
        }
        #panel-structure .structure-select-wrap::after {
            right: 15px;
            width: 7px;
            height: 7px;
        }
        #panel-structure .structure-info-note {
            grid-template-columns: 26px minmax(0, 1fr);
            gap: 8px;
            margin-top: 14px;
            padding: 10px;
            border-radius: 11px;
            font-size: 0.62rem;
            line-height: 1.32;
        }
        #panel-structure .structure-info-note i {
            width: 24px;
            height: 24px;
            border-radius: 8px;
        }
        #panel-inclusive.setup-inclusive-card {
            padding: 12px !important;
            border-radius: 13px !important;
        }
        #panel-inclusive .setup-inclusive-head {
            gap: 8px;
            margin-bottom: 12px;
        }
        #panel-inclusive .setup-inclusive-head-icon {
            width: 30px;
            height: 30px;
            font-size: 0.88rem;
        }
        #panel-inclusive .setup-inclusive-title {
            font-size: 1rem !important;
            white-space: nowrap;
        }
        #panel-inclusive .setup-inclusive-copy {
            font-size: 0.68rem;
            line-height: 1.42;
            margin-bottom: 14px;
        }
        #panel-inclusive .inclusive-option-list {
            gap: 8px;
        }
        #panel-inclusive .inclusive-option {
            grid-template-columns: 20px minmax(0, 1fr);
            gap: 10px;
            min-height: 40px;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 0.72rem;
        }
        #panel-inclusive .inclusive-option input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        #panel-inclusive .inclusive-note {
            grid-template-columns: 26px minmax(0, 1fr);
            gap: 8px;
            margin-top: 12px;
            padding: 10px;
            border-radius: 11px;
            font-size: 0.62rem;
            line-height: 1.35;
        }
        #panel-inclusive .inclusive-note i {
            width: 24px;
            height: 24px;
            font-size: 0.72rem;
        }
        #panel-content.setup-assistance-card {
            max-width: 100%;
            padding: 12px !important;
            border-radius: 13px !important;
        }
        #panel-content .assistance-head {
            gap: 8px;
            margin-bottom: 14px;
        }
        #panel-content .assistance-head-icon {
            width: 30px;
            height: 30px;
            font-size: 0.88rem;
        }
        #panel-content .assistance-title {
            font-size: 1rem !important;
            white-space: nowrap;
        }
        #panel-content .assistance-stack {
            gap: 14px;
        }
        #panel-content .assistance-field .olbl {
            font-size: 0.72rem;
            margin-bottom: 7px;
        }
        #panel-content .assistance-select-wrap select.oinp {
            min-height: 38px;
            border-radius: 10px;
            font-size: 0.7rem;
            padding: 8px 34px 8px 12px;
        }
        #panel-content .assistance-select-wrap::after {
            right: 15px;
            width: 7px;
            height: 7px;
            border-width: 2px;
        }
        #panel-content .assistance-question-list {
            gap: 8px;
        }
        #panel-content .assistance-question-card {
            grid-template-columns: 18px 34px minmax(0, 1fr);
            gap: 10px;
            min-height: 48px;
            padding: 8px 10px;
            border-radius: 11px;
            font-size: 0.76rem;
        }
        #panel-content .assistance-question-card input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        #panel-content .assistance-question-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            font-size: 0.78rem;
        }
        #panel-content .assistance-context-panel {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            padding: 10px;
            border-radius: 12px;
        }
        #panel-content .assistance-context-icon {
            width: 34px;
            height: 34px;
            font-size: 0.95rem;
        }
        #panel-content .assistance-context-title {
            font-size: 0.82rem;
            margin-bottom: 3px;
        }
        #panel-content .assistance-context-panel .desc-text {
            font-size: 0.64rem;
            line-height: 1.35;
        }
        #panel-response.setup-response-card {
            max-width: 100%;
            padding: 12px !important;
            border-radius: 13px !important;
        }
        #panel-response .response-head {
            gap: 8px;
            margin-bottom: 14px;
        }
        #panel-response .response-head-icon {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            font-size: 0.88rem;
        }
        #panel-response .response-title {
            font-size: 1rem !important;
            white-space: nowrap;
        }
        #panel-response .response-mode-list {
            gap: 9px;
        }
        #panel-response .response-mode-card {
            grid-template-columns: 18px minmax(0, 1fr);
            gap: 14px;
            min-height: 58px;
            padding: 10px 12px;
            border-radius: 12px;
        }
        #panel-response .response-mode-card input[type="radio"] {
            width: 18px;
            height: 18px;
        }
        #panel-response .response-mode-title {
            font-size: 0.86rem;
            margin-bottom: 3px;
        }
        #panel-response .response-mode-desc {
            font-size: 0.7rem;
            line-height: 1.28;
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
        #panel-summary {
            position: static !important;
            top: auto !important;
            max-width: min(360px, 100%);
            padding: 16px !important;
        }
        #panel-summary h5 {
            font-size: 1.14rem !important;
            margin-bottom: 12px !important;
        }
        #panel-summary h5 i {
            width: 38px;
            height: 38px;
            font-size: 0.9rem;
        }
        #sec-interview-setup .summary-row {
            grid-template-columns: 30px minmax(72px, 0.78fr) minmax(0, 1.22fr);
            min-height: 48px;
            padding: 7px 0;
            gap: 8px;
            font-size: 0.74rem;
            align-items: center;
        }
        #sec-interview-setup .summary-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            font-size: 0.72rem;
        }
        #sec-interview-setup .summary-val {
            overflow-wrap: anywhere;
        }
        #panel-summary .setup-start-action {
            margin-top: 14px !important;
        }
        #panel-summary #btn-start-interview {
            min-height: 48px;
            border-radius: 12px !important;
            font-size: 0.88rem !important;
            padding: 10px 12px !important;
            touch-action: manipulation;
        }
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

    @media (max-width: 390px) {
        #sec-interview-setup .setup-hero-inner {
            padding-right: 82px !important;
        }
        #sec-interview-setup .setup-hero-title {
            font-size: 0.78rem !important;
            white-space: nowrap;
        }
        #sec-interview-setup .setup-hero-title svg {
            width: 20px !important;
            height: 20px !important;
            padding: 0;
            border-radius: 0;
        }
        #sec-interview-setup .setup-hero-art {
            width: 98px !important;
        }
        #panel-basic .setup-details-card-head {
            grid-template-columns: 26px minmax(0, 1fr);
            gap: 7px;
        }
        #panel-basic .setup-details-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
        }
        #panel-basic .setup-details-card-title {
            font-size: 0.96rem !important;
        }
        #panel-basic.setup-details-card select.oinp {
            padding-left: 12px;
            padding-right: 42px;
            background-image: none;
        }
        #panel-basic.setup-details-card {
            padding: 10px !important;
        }
        #panel-basic .setup-details-card-subtitle,
        #panel-basic.setup-details-card .desc-text,
        #panel-basic .setup-calibrated-simple p {
            font-size: 0.6rem;
        }
        #panel-basic .setup-calibrated-simple {
            grid-template-columns: 28px minmax(0, 1fr);
            gap: 9px;
        }
        #panel-basic .setup-calibrated-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            font-size: 0.78rem;
        }
        #panel-structure .setup-structure-title {
            font-size: 0.92rem !important;
        }
        #panel-structure .structure-difficulty-card {
            grid-template-columns: 20px minmax(0, 1fr) 26px;
            gap: 7px;
            padding: 7px 9px;
        }
        #panel-structure .structure-difficulty-icon {
            width: 24px;
            height: 24px;
            font-size: 0.58rem;
        }
        #panel-inclusive .setup-inclusive-title {
            font-size: 0.92rem !important;
        }
        #panel-inclusive .setup-inclusive-head-icon {
            width: 26px;
            height: 26px;
            font-size: 0.78rem;
        }
        #panel-inclusive .inclusive-option {
            font-size: 0.66rem;
            min-height: 36px;
            padding: 7px 9px;
        }
        #panel-inclusive .inclusive-note {
            font-size: 0.58rem;
        }
        #panel-content.setup-assistance-card {
            padding: 10px !important;
        }
        #panel-content .assistance-head-icon {
            width: 26px;
            height: 26px;
            font-size: 0.78rem;
        }
        #panel-content .assistance-title {
            font-size: 0.92rem !important;
        }
        #panel-content .assistance-field .olbl {
            font-size: 0.68rem;
        }
        #panel-content .assistance-select-wrap select.oinp {
            min-height: 36px;
            font-size: 0.66rem;
            padding-left: 10px;
            padding-right: 32px;
        }
        #panel-content .assistance-question-card {
            grid-template-columns: 17px 30px minmax(0, 1fr);
            gap: 8px;
            min-height: 44px;
            padding: 7px 9px;
            font-size: 0.68rem;
        }
        #panel-content .assistance-question-icon {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            font-size: 0.7rem;
        }
        #panel-content .assistance-context-panel {
            grid-template-columns: 32px minmax(0, 1fr);
            gap: 8px;
            padding: 9px;
        }
        #panel-content .assistance-context-icon {
            width: 30px;
            height: 30px;
            font-size: 0.82rem;
        }
        #panel-content .assistance-context-title {
            font-size: 0.76rem;
        }
        #panel-content .assistance-context-panel .desc-text {
            font-size: 0.58rem;
        }
        #panel-response.setup-response-card {
            padding: 10px !important;
        }
        #panel-response .response-head-icon {
            width: 26px;
            height: 26px;
            font-size: 0.78rem;
        }
        #panel-response .response-title {
            font-size: 0.92rem !important;
        }
        #panel-response .response-mode-card {
            grid-template-columns: 17px minmax(0, 1fr);
            gap: 11px;
            min-height: 52px;
            padding: 9px 10px;
            border-radius: 11px;
        }
        #panel-response .response-mode-card input[type="radio"] {
            width: 17px;
            height: 17px;
        }
        #panel-response .response-mode-title {
            font-size: 0.78rem;
        }
        #panel-response .response-mode-desc {
            font-size: 0.62rem;
        }
    }

    /* Match the responsive banner sizing used by the Progress page. */
    #sec-interview-setup .setup-hero {
        --setup-hero-title-color: #1d4ed8;
        --setup-hero-text-color: #334155;
        --setup-hero-icon-bg: rgba(239, 246, 255, 0.92);
        --setup-hero-icon-border: rgba(147, 197, 253, 0.42);
        min-height: 104px;
        margin-bottom: 12px;
        border-radius: 14px;
        background:
            radial-gradient(circle at 86% 18%, rgba(219, 234, 254, 0.78), transparent 35%),
            linear-gradient(142deg, #ffffff 0%, #f8fbff 52%, #dbeafe 100%) !important;
        border-color: rgba(147, 197, 253, 0.52) !important;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.1);
    }
    html[data-theme="dark"] #sec-interview-setup .setup-hero,
    :root:not(.lm) #sec-interview-setup .setup-hero {
        --setup-hero-title-color: #93c5fd;
        --setup-hero-text-color: #e2e8f0;
        --setup-hero-icon-bg: rgba(59, 130, 246, 0.2);
        --setup-hero-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28) !important;
    }
    #sec-interview-setup .setup-hero-inner {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr);
        align-items: center;
        gap: 8px;
        justify-content: flex-start;
        min-height: 104px;
        padding: 14px 116px 14px 14px;
    }
    #sec-interview-setup .setup-hero-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border: 1px solid var(--setup-hero-icon-border);
        border-radius: 10px;
        background: var(--setup-hero-icon-bg);
        color: var(--setup-hero-title-color);
    }
    #sec-interview-setup .setup-hero-icon svg {
        width: 18px;
        height: 18px;
        display: block;
    }
    #sec-interview-setup .setup-hero-title {
        display: block;
        font-size: 1.1rem !important;
        line-height: 1.15;
        margin: 0 0 4px;
        letter-spacing: 0;
        white-space: nowrap;
        -webkit-text-fill-color: var(--setup-hero-title-color) !important;
        color: var(--setup-hero-title-color) !important;
    }
    #sec-interview-setup .setup-hero-subtitle {
        max-width: 100%;
        font-size: 0.74rem !important;
        line-height: 1.4;
        margin: 0;
        color: var(--setup-hero-text-color) !important;
    }
    #sec-interview-setup .setup-hero-art {
        right: -10px;
        bottom: -1px;
        width: 112px !important;
    }

    @media (max-width: 390px) {
        #sec-interview-setup .setup-hero-inner {
            grid-template-columns: 32px minmax(0, 1fr);
            padding-right: 86px;
        }
        #sec-interview-setup .setup-hero-icon {
            width: 32px;
            height: 32px;
        }
        #sec-interview-setup .setup-hero-icon svg {
            width: 16px;
            height: 16px;
        }
        #sec-interview-setup .setup-hero-title {
            font-size: 0.86rem !important;
        }
        #sec-interview-setup .setup-hero-subtitle {
            font-size: 0.66rem !important;
        }
        #sec-interview-setup .setup-hero-art {
            width: 78px !important;
        }
    }
    @media (min-width: 992px) {
        #sec-interview-setup {
            --setup-gap: 14px;
            --setup-card-pad: 14px;
            max-width: 1380px;
            margin-inline: auto;
            padding-top: 10px !important;
            padding-bottom: 30px !important;
        }

        #sec-interview-setup .setup-hero {
            margin-bottom: var(--setup-gap);
            border-radius: 16px;
        }

        #sec-interview-setup .setup-hero-inner {
            justify-content: flex-start;
            min-height: 96px;
            padding: 14px clamp(138px, 15vw, 170px) 14px 18px;
        }

        #sec-interview-setup .setup-hero-title {
            font-size: clamp(1.2rem, 1.35vw, 1.45rem);
            margin-bottom: 5px;
        }

        #sec-interview-setup .setup-hero-subtitle {
            max-width: 760px;
            font-size: 0.84rem;
        }

        #sec-interview-setup .setup-hero-art {
            right: 14px;
            width: clamp(116px, 11vw, 138px);
        }

        #sec-interview-setup #setupForm > .row {
            --bs-gutter-x: var(--setup-gap);
            --bs-gutter-y: var(--setup-gap);
            align-items: flex-start;
        }

        #setup-left-col {
            gap: var(--setup-gap);
        }

        #sec-interview-setup .setup-panel {
            margin-bottom: 0;
            padding: var(--setup-card-pad) !important;
            border-radius: 14px !important;
            width: 100%;
            max-width: 100%;
            min-height: 0 !important;
            height: auto !important;
            display: block;
            overflow: hidden;
        }

        #sec-interview-setup .setup-panel *,
        #sec-interview-setup .setup-stepper * {
            min-width: 0;
        }

        #sec-interview-setup .setup-panel:hover {
            transform: translateY(-1px);
        }

        #sec-interview-setup .setup-panel h5 {
            margin-bottom: 10px !important;
        }

        #panel-basic.setup-details-card,
        #panel-structure.setup-structure-card,
        #panel-inclusive.setup-inclusive-card,
        #panel-content.setup-assistance-card,
        #panel-response.setup-response-card {
            max-width: none;
            margin-inline: 0;
            padding: var(--setup-card-pad) !important;
            border-radius: 14px !important;
        }

        #panel-basic .setup-details-card-head,
        #panel-structure .setup-structure-head,
        #panel-inclusive .setup-inclusive-head,
        #panel-content .assistance-head,
        #panel-response .response-head {
            margin-bottom: 12px;
            gap: 12px;
            min-height: 42px;
            align-items: center;
        }

        #panel-basic .setup-details-icon,
        #panel-structure .setup-structure-head-icon,
        #panel-inclusive .setup-inclusive-head-icon,
        #panel-content .assistance-head-icon,
        #panel-response .response-head-icon {
            width: 42px;
            height: 42px;
            font-size: 1.1rem;
        }

        #panel-basic .setup-details-card-title,
        #panel-structure .setup-structure-title,
        #panel-inclusive .setup-inclusive-title,
        #panel-content .assistance-title,
        #panel-response .response-title {
            font-size: 1.12rem !important;
            line-height: 1.18;
        }

        #panel-basic .setup-details-card-subtitle,
        #panel-inclusive .setup-inclusive-copy {
            font-size: 0.82rem;
            line-height: 1.42;
            margin-bottom: 12px;
        }

        #panel-basic .setup-details-card-subtitle,
        #panel-inclusive .setup-inclusive-copy,
        #panel-basic.setup-details-card .desc-text,
        #panel-content .assistance-context-panel .desc-text {
            max-width: none;
        }

        #panel-content .assistance-field .olbl,
        #panel-structure .setup-structure-section-title,
        #panel-basic .setup-card-label {
            font-size: 0.84rem;
            margin-bottom: 8px;
        }

        #panel-basic .setup-card-label-icon {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            font-size: 0.9rem;
        }

        #panel-basic .setup-card-fields,
        #panel-inclusive .inclusive-option-list {
            gap: 12px;
            align-items: start;
        }

        #panel-content .assistance-stack {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            align-items: start;
        }

        #panel-content .assistance-field:first-child,
        #panel-content .assistance-field:nth-child(2),
        #panel-content .assistance-context-panel {
            grid-column: 1 / -1;
        }

        #panel-basic .setup-card-fields {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
        }

        #panel-basic .setup-card-field,
        #panel-content .assistance-field {
            min-width: 0;
        }

        #panel-basic .setup-calibrated-simple,
        #panel-content .assistance-context-panel {
            grid-column: 1 / -1;
        }

        #panel-basic.setup-details-card .oinp,
        #panel-basic.setup-details-card select.oinp,
        #panel-basic.setup-details-card input.oinp {
            min-height: 48px !important;
            padding: 10px 42px 10px 14px !important;
            border-radius: 12px !important;
            font-size: 0.92rem !important;
        }

        #panel-basic.setup-details-card input.oinp {
            padding-right: 14px !important;
        }

        #panel-basic.setup-details-card .desc-text {
            margin-top: 6px;
            font-size: 0.76rem !important;
            line-height: 1.35;
        }

        #panel-basic .setup-calibrated-simple {
            min-height: 0;
            padding: 14px !important;
            border-radius: 14px !important;
            gap: 14px;
            align-items: center;
            margin-top: 0 !important;
        }

        #panel-basic .setup-calibrated-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 1.15rem;
        }

        #panel-basic .setup-calibrated-simple strong {
            font-size: 1.1rem !important;
            line-height: 1.2;
        }

        #panel-basic .setup-calibrated-simple p {
            margin-top: 6px;
            font-size: 0.84rem !important;
            line-height: 1.42;
        }

        #panel-structure .structure-difficulty-list {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
            align-items: stretch;
        }

        #panel-structure .structure-difficulty-card {
            min-height: 92px !important;
            padding: 12px !important;
            gap: 10px;
            height: 100%;
        }

        #panel-structure .structure-difficulty-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            font-size: 1rem;
        }

        #panel-structure .structure-difficulty-title {
            font-size: 0.9rem !important;
            line-height: 1.2;
        }

        #panel-structure .structure-difficulty-desc {
            font-size: 0.72rem !important;
            line-height: 1.32;
        }

        #panel-structure .structure-select-grid,
        #panel-structure .structure-config-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            align-items: start;
        }

        #panel-structure .structure-select-grid .olbl {
            min-height: 2.4em;
            display: flex;
            align-items: flex-end;
        }

        #panel-structure .structure-select-wrap select.oinp {
            min-height: 48px !important;
            padding: 10px 42px 10px 14px !important;
            border-radius: 12px !important;
            font-size: 0.86rem !important;
        }

        #panel-structure .structure-info-note {
            min-height: 0;
            padding: 12px 14px !important;
            border-radius: 12px !important;
            gap: 10px;
            font-size: 0.78rem !important;
            line-height: 1.38;
            margin-top: 12px !important;
        }

        #panel-response .response-mode-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            align-items: stretch;
        }

        #panel-content .assistance-question-list {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            align-items: stretch;
        }

        #panel-response .response-mode-list {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        #sec-interview-setup .oinp,
        #sec-interview-setup select.oinp,
        #sec-interview-setup input.oinp,
        #sec-interview-setup .custom-radio,
        #sec-interview-setup .assistance-question-card,
        #sec-interview-setup .response-mode-card,
        #sec-interview-setup .structure-difficulty-card {
            min-height: 54px;
            font-size: 0.84rem;
        }

        #sec-interview-setup .custom-radio,
        #sec-interview-setup .assistance-question-card,
        #sec-interview-setup .response-mode-card,
        #sec-interview-setup .structure-difficulty-card {
            padding: 11px 12px;
            border-radius: 12px;
        }

        #panel-inclusive .inclusive-option-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            align-items: stretch;
        }

        #panel-inclusive .inclusive-option {
            min-height: 46px !important;
            padding: 11px 12px !important;
            border-radius: 12px !important;
            font-size: 0.82rem !important;
            line-height: 1.25;
        }

        #panel-inclusive .inclusive-note {
            min-height: 0;
            margin-top: 12px;
            padding: 12px 14px !important;
            border-radius: 12px !important;
            gap: 10px;
            font-size: 0.78rem !important;
            line-height: 1.38;
        }

        #panel-inclusive .inclusive-note i {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            font-size: 0.82rem;
        }

        #panel-content.setup-assistance-card {
            padding: var(--setup-card-pad) !important;
            border-radius: 14px !important;
        }

        #panel-content .assistance-select-wrap select.oinp {
            min-height: 48px !important;
            padding: 10px 42px 10px 14px !important;
            border-radius: 12px !important;
            font-size: 0.86rem !important;
        }

        #panel-content .assistance-question-card {
            min-height: 48px !important;
            padding: 10px !important;
            border-radius: 12px !important;
            gap: 8px;
        }

        #panel-content .assistance-question-icon {
            width: 28px;
            height: 28px;
            border-radius: 10px;
            font-size: 0.78rem;
        }

        #panel-content .assistance-question-text {
            font-size: 0.76rem !important;
            line-height: 1.25;
        }

        #panel-content .assistance-context-panel {
            min-height: 0;
            padding: 12px 14px !important;
            border-radius: 12px !important;
            gap: 10px;
            margin-top: 0 !important;
        }

        #panel-content .assistance-context-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            font-size: 0.84rem;
        }

        #panel-content .assistance-context-title {
            font-size: 0.86rem !important;
        }

        #panel-response.setup-response-card {
            padding: var(--setup-card-pad) !important;
            border-radius: 14px !important;
        }

        #panel-response .response-mode-card {
            min-height: 72px !important;
            padding: 12px !important;
            border-radius: 12px !important;
            height: 100%;
        }

        #panel-response .response-mode-title {
            font-size: 0.86rem !important;
            line-height: 1.2;
        }

        #panel-response .response-mode-desc {
            font-size: 0.72rem !important;
            line-height: 1.32;
        }

        #sec-interview-setup .col-lg-4 > div[style*="sticky"] {
            top: 92px !important;
        }

        #sec-interview-setup.setup-step-mode #setupForm > .row {
            display: flex;
            align-items: flex-start;
        }

        #sec-interview-setup.setup-step-mode #setupForm > .row > .col-lg-8 {
            width: 70%;
            max-width: none;
        }

        #sec-interview-setup.setup-step-mode #setupForm > .row > .col-lg-4 {
            display: block;
            width: 30%;
            max-width: none;
        }

        #sec-interview-setup.setup-step-mode #setup-left-col {
            display: block;
        }

        #sec-interview-setup.setup-step-mode #setup-left-col > .setup-panel {
            display: none;
        }

        #sec-interview-setup.setup-step-mode #setup-left-col > .setup-panel.setup-step-active {
            display: block;
        }

        #sec-interview-setup.setup-step-mode .col-lg-4 > div[style*="sticky"] {
            position: sticky !important;
            top: 92px !important;
        }

        .setup-stepper {
            width: 100%;
            max-width: none;
            margin-left: 0;
            margin-right: 0;
            margin: 0 0 var(--setup-gap);
            padding: 12px 14px;
            border: 1px solid var(--bd);
            border-radius: 14px;
            background: var(--sf);
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            box-shadow: var(--shadow-soft, 0 10px 28px rgba(0, 0, 0, 0.12));
        }

        .setup-stepper-track {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 8px;
        }

        .setup-stepper-item {
            min-width: 0;
            padding: 0;
            border: 0;
            background: transparent;
            text-align: left;
            cursor: pointer;
        }

        .setup-stepper-dot {
            width: 100%;
            height: 8px;
            border-radius: 999px;
            background: var(--bg3);
            border: 1px solid var(--bd);
            display: block;
        }

        .setup-stepper-item.is-active .setup-stepper-dot,
        .setup-stepper-item.is-complete .setup-stepper-dot {
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            border-color: transparent;
        }

        .setup-stepper-label {
            display: block;
            margin-top: 7px;
            color: var(--tx3);
            font-size: 0.68rem;
            font-weight: 800;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .setup-stepper-item.is-active .setup-stepper-label {
            color: var(--tx);
            font-weight: 900;
        }

        .setup-stepper-item.is-complete .setup-stepper-label {
            color: #2563eb;
        }

        .setup-stepper-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .setup-step-btn {
            min-height: 38px;
            border-radius: 11px;
            border: 1px solid var(--bd);
            background: var(--bg3);
            color: var(--tx);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .setup-step-btn.primary {
            border-color: transparent;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            color: #fff;
        }

        .setup-step-btn:disabled {
            opacity: 0.48;
            cursor: not-allowed;
        }

        #panel-summary {
            width: 100%;
            max-width: none;
            padding: 14px !important;
            border-radius: 16px !important;
        }

        #panel-summary h5 {
            font-size: 0.98rem !important;
            margin-bottom: 10px !important;
        }

        #panel-summary h5 i {
            width: 34px;
            height: 34px;
            font-size: 0.84rem;
        }

        #panel-summary .summary-row {
            grid-template-columns: 28px minmax(70px, 0.8fr) minmax(0, 1.2fr);
            min-height: 42px;
            gap: 8px;
            padding: 6px 0;
            font-size: 0.74rem;
        }

        #panel-summary .summary-icon {
            width: 24px;
            height: 24px;
            border-radius: 8px;
            font-size: 0.68rem;
        }

        #panel-summary .setup-start-action {
            margin-top: 14px !important;
        }

        #panel-summary #btn-start-interview {
            min-height: 44px;
            font-size: 0.82rem !important;
            border-radius: 12px !important;
        }
    }

    @media (min-width: 1200px) {
        #sec-interview-setup {
            --setup-gap: 16px;
            --setup-card-pad: 16px;
        }

        #sec-interview-setup #setupForm > .row > .col-lg-8 {
            width: 68%;
        }

        #sec-interview-setup #setupForm > .row > .col-lg-4 {
            width: 32%;
        }

        #sec-interview-setup.setup-step-mode #setupForm > .row > .col-lg-8 {
            width: 70%;
        }

        #sec-interview-setup.setup-step-mode #setupForm > .row > .col-lg-4 {
            width: 30%;
        }

        #sec-interview-setup.setup-step-mode .setup-stepper {
            width: 100%;
            max-width: none;
        }
    }

    @media (min-width: 992px) and (max-width: 1199.98px) {
        #sec-interview-setup.setup-step-mode #setupForm > .row > .col-lg-8 {
            width: 66%;
        }

        #sec-interview-setup.setup-step-mode #setupForm > .row > .col-lg-4 {
            width: 34%;
        }

        #sec-interview-setup.setup-step-mode .setup-stepper {
            width: 100%;
        }

        #panel-basic .setup-card-fields,
        #panel-structure .structure-difficulty-list,
        #panel-structure .structure-config-grid,
        #panel-response .response-mode-list,
        #panel-inclusive .inclusive-option-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        #panel-content .assistance-question-list {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        #panel-structure .structure-select-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        #sec-interview-setup:not(.setup-step-mode) .col-lg-4 > div[style*="sticky"] {
            position: static !important;
        }
    }

    @media (max-width: 991.98px) {
        .setup-stepper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin: 0 0 10px;
            padding: 8px;
            border: 1px solid var(--bd);
            border-radius: 12px;
            background: var(--sf);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .setup-stepper-track {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            column-gap: 8px;
            row-gap: 6px;
        }

        .setup-stepper-item {
            min-width: 0;
            padding: 0;
            border: 0;
            background: transparent;
            text-align: left;
        }

        .setup-stepper-dot {
            display: block;
            width: 100%;
            height: 5px;
            border-radius: 999px;
            background: var(--bg3);
            border: 1px solid var(--bd);
        }

        .setup-stepper-item.is-active .setup-stepper-dot,
        .setup-stepper-item.is-complete .setup-stepper-dot {
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            border-color: transparent;
        }

        .setup-stepper-label {
            display: block;
            margin-top: 4px;
            color: var(--tx3);
            font-size: 0.54rem;
            font-weight: 800;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .setup-stepper-item.is-active .setup-stepper-label {
            color: var(--tx);
        }

        .setup-stepper-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 7px;
        }

        .setup-step-btn {
            min-height: 34px;
            border-radius: 10px;
            border: 1px solid var(--bd);
            background: var(--bg3);
            color: var(--tx);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 9px;
            font-size: 0.68rem;
            font-weight: 800;
        }

        .setup-step-btn.primary {
            border-color: transparent;
            background: linear-gradient(135deg, #2563eb, #06b6d4);
            color: #fff;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.18);
        }

        .setup-step-btn:disabled {
            opacity: 0.48;
            box-shadow: none;
        }

        #sec-interview-setup.setup-step-mode #setup-left-col > .setup-panel,
        #sec-interview-setup.setup-step-mode #panel-summary {
            display: none;
        }

        #sec-interview-setup.setup-step-mode #setup-left-col > .setup-panel.setup-step-active,
        #sec-interview-setup.setup-step-mode #panel-summary.setup-step-active {
            display: block;
        }

        #sec-interview-setup.setup-step-mode .setup-step-transition-in {
            animation: setupStepPanelIn 0.24s ease both;
        }

        #sec-interview-setup.setup-step-mode .setup-panel.setup-step-active {
            border-color: rgba(37, 99, 235, 0.48) !important;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.14) !important;
        }

        #sec-interview-setup.setup-step-mode.setup-summary-step #setup-left-col > .setup-panel {
            display: none;
        }
    }

    @keyframes setupStepPanelIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        #sec-interview-setup.setup-step-mode .setup-step-transition-in {
            animation: none;
        }
    }

    @media (min-width: 1500px) {
        #sec-interview-setup {
            max-width: 1440px;
        }
    }

    #btn-start-interview.setup-start-disabled,
    #btn-start-interview:disabled {
        opacity: 0.52 !important;
        cursor: not-allowed !important;
        transform: none !important;
        box-shadow: none !important;
        filter: grayscale(0.25);
    }
</style>

<div class="db-section active" id="sec-interview-setup">
    <div class="setup-hero">
        <div class="setup-hero-inner">
            <span class="setup-hero-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" role="img">
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
            </span>
            <div class="setup-hero-copy">
                <h4 class="setup-hero-title text-gradient-primary">
                    Philippines Interview Setup
                </h4>
                <p class="setup-hero-subtitle">Practice a Philippines-focused mock interview with local HR screening, role-fit, and communication expectations.</p>
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
                <div class="setup-stepper" id="setupStepper" aria-label="Interview setup steps">
                    <div class="setup-stepper-track" id="setupStepperTrack"></div>
                    <div class="setup-stepper-actions">
                        <button type="button" class="setup-step-btn" id="setupStepPrev"><i class="fa-solid fa-arrow-left"></i> Back</button>
                        <button type="button" class="setup-step-btn primary" id="setupStepNext">Next <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="setup-panel setup-details-card animate-fade-up delay-100" id="panel-basic">
                    <div class="setup-details-card-head">
                        <div class="setup-details-icon" aria-hidden="true">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <div>
                            <h5 class="setup-details-card-title">Philippines Interview Details</h5>
                            <p class="setup-details-card-subtitle">Configure your practice scenario to get tailored questions and feedback.</p>
                        </div>
                    </div>

                    <div class="setup-card-fields">
                        <div class="setup-card-field">
                            <label class="setup-card-label" for="valScenario">
                                <span class="setup-card-label-icon" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
                                Practice Scenario
                            </label>
                            <div class="setup-select-wrap">
                                <select class="oinp setup-input" name="source_pack_key" id="valScenario">
                                @foreach($scenarioOptions as $scenario)
                                    <option value="{{ $scenario['key'] }}"
                                        data-category-id="{{ $scenario['category_id'] }}"
                                        data-focus="{{ $scenario['focus'] }}"
                                        data-context-label="{{ $scenario['context_label'] }}"
                                        data-source-summary="{{ $scenario['source_summary'] }}"
                                        {{ $selectedScenario && $selectedScenario['key'] === $scenario['key'] ? 'selected' : '' }}>
                                        {{ $scenario['label'] }}
                                    </option>
                                @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="category_id" id="valCategory" value="{{ $selectedScenario['category_id'] ?? '' }}">
                            <input type="hidden" name="interview_focus" id="valFocus" value="{{ $setupDefaults['interview_focus'] }}" class="setup-input">
                            <div class="desc-text">One scenario sets the source pack, interview focus, and scoring context.</div>
                        </div>

                        <div class="setup-card-field">
                            <label class="setup-card-label" for="valPosition">
                                <span class="setup-card-label-icon" aria-hidden="true"><i class="fa-solid fa-bullseye-arrow"></i></span>
                                Target Position
                            </label>
                            <div class="setup-search-wrap">
                                <input class="oinp setup-input" type="text" name="target_position" id="valPosition" placeholder="e.g. Call Center Agent, Teacher, Software Developer" value="{{ old('target_position') }}" required>
                            </div>
                        </div>

                        <div class="setup-calibrated-simple">
                            <div class="setup-calibrated-icon" aria-hidden="true">
                                <i class="fa-solid fa-database"></i>
                            </div>
                            <div>
                                <h6>Philippines-calibrated practice</h6>
                                <p><strong>Sources:</strong> <span id="sourceSummary">{{ $selectedScenario['source_summary'] ?? 'Philippines career and education sources' }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interview Structure -->
                <div class="setup-panel setup-structure-card animate-fade-up delay-300" id="panel-structure">
                    <div class="setup-structure-head">
                        <div class="setup-structure-head-icon" aria-hidden="true">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h5 class="setup-structure-title">Interview Structure</h5>
                    </div>

                    <div class="setup-structure-section-title">Difficulty Level</div>
                    <div class="structure-difficulty-list">
                        <label class="structure-difficulty-card">
                            <input type="radio" name="difficulty" value="easy" class="setup-input" {{ $setupDefaults['difficulty'] === 'easy' ? 'checked' : '' }}>
                            <span>
                                <span class="structure-difficulty-title">Easy</span>
                                <span class="structure-difficulty-desc">Basic and introductory questions</span>
                            </span>
                            <span class="structure-difficulty-icon" aria-hidden="true"><i class="fa-solid fa-signal"></i></span>
                        </label>
                        <label class="structure-difficulty-card">
                            <input type="radio" name="difficulty" value="medium" class="setup-input" {{ $setupDefaults['difficulty'] === 'medium' ? 'checked' : '' }}>
                            <span>
                                <span class="structure-difficulty-title">Medium</span>
                                <span class="structure-difficulty-desc">Common interview questions</span>
                            </span>
                            <span class="structure-difficulty-icon" aria-hidden="true"><i class="fa-solid fa-star"></i></span>
                        </label>
                        <label class="structure-difficulty-card">
                            <input type="radio" name="difficulty" value="hard" class="setup-input" {{ $setupDefaults['difficulty'] === 'hard' ? 'checked' : '' }}>
                            <span>
                                <span class="structure-difficulty-title">Hard</span>
                                <span class="structure-difficulty-desc">Advanced and situational questions</span>
                            </span>
                            <span class="structure-difficulty-icon" aria-hidden="true"><i class="fa-solid fa-shield-alt"></i></span>
                        </label>
                    </div>

                    <div class="structure-select-grid">
                        <div>
                            <label class="olbl">Number of Questions</label>
                            <div class="structure-select-wrap">
                                <select class="oinp setup-input" name="num_questions" id="valNumQuestions">
                                <option value="5" {{ $setupDefaults['num_questions'] === '5' ? 'selected' : '' }}>5 Questions</option>
                                <option value="10" {{ $setupDefaults['num_questions'] === '10' ? 'selected' : '' }}>10 Questions</option>
                                <option value="15" {{ $setupDefaults['num_questions'] === '15' ? 'selected' : '' }}>15 Questions</option>
                                <option value="20" {{ $setupDefaults['num_questions'] === '20' ? 'selected' : '' }}>20 Questions</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="olbl">Time Limit</label>
                            <div class="structure-select-wrap">
                                <select class="oinp setup-input" name="time_limit" id="valTimeLimit">
                                <option value="0" {{ $setupDefaults['time_limit'] === '0' ? 'selected' : '' }}>No Limit</option>
                                <option value="1" {{ $setupDefaults['time_limit'] === '1' ? 'selected' : '' }}>1 Minute per Question</option>
                                <option value="2" {{ $setupDefaults['time_limit'] === '2' ? 'selected' : '' }}>2 Minutes per Question</option>
                                <option value="3" {{ $setupDefaults['time_limit'] === '3' ? 'selected' : '' }}>3 Minutes per Question</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="olbl">Interview Format Laboratory</label>
                            <div class="structure-select-wrap">
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
                            </div>
                        </div>
                    </div>
                    <div class="structure-info-note">
                        <i class="fa-solid fa-info" aria-hidden="true"></i>
                        <span>Feedback is adjusted to the selected format; camera behavior remains optional.</span>
                    </div>
                </div>

                <div class="setup-panel setup-inclusive-card animate-fade-up delay-300" id="panel-inclusive">
                    <div class="setup-inclusive-head">
                        <div class="setup-inclusive-head-icon" aria-hidden="true">
                            <i class="fa-solid fa-universal-access"></i>
                        </div>
                        <h5 class="setup-inclusive-title">Inclusive Practice Conditions</h5>
                    </div>
                    <p class="setup-inclusive-copy">Choose conditions that give you an accurate opportunity to demonstrate job-related ability. These settings are recorded with the assessment.</p>
                    @php $inclusive = Auth::user()->profile?->inclusive_preferences ?? []; @endphp
                    <div class="inclusive-option-list">
                        @foreach([
                            'camera_coaching' => 'Optional body-language coach',
                            'separate_language_scoring' => 'Separate language mechanics',
                            'extended_time' => 'Extended response time',
                            'captions' => 'Captions / transcript controls',
                            'reduced_distraction' => 'Reduced-distraction workspace',
                            'simplified_questions' => 'Clearer question wording',
                        ] as $preferenceKey => $preferenceLabel)
                            <label class="inclusive-option"><input type="checkbox" name="{{ $preferenceKey }}" value="1" {{ old($preferenceKey, data_get($inclusive, $preferenceKey, false)) ? 'checked' : '' }}> <span>{{ $preferenceLabel }}</span></label>
                        @endforeach
                    </div>
                    <div class="inclusive-note">
                        <i class="fa-solid fa-info" aria-hidden="true"></i>
                        <span><strong>Important:</strong> body-language signals are never included in the readiness score. Camera coaching only reports visible framing, head alignment, hand/shoulder/posture cues, and movement steadiness. It does not infer confidence, honesty, personality, or employability.</span>
                    </div>
                </div>

                <!-- Content & Assistance -->
                <div class="setup-panel setup-assistance-card animate-fade-up delay-400" id="panel-content">
                    <div class="assistance-head">
                        <div class="assistance-head-icon" aria-hidden="true">
                            <i class="fa-solid fa-brain"></i>
                        </div>
                        <h5 class="assistance-title">Content & Assistance</h5>
                    </div>

                    <div class="assistance-stack">
                        <div class="assistance-field">
                            <label class="olbl">AI Assistance Level</label>
                            <div class="assistance-select-wrap">
                                <select class="oinp setup-input" name="ai_assistance_level" id="valAssistance">
                                    <option value="beginner" {{ $setupDefaults['ai_assistance_level'] === 'beginner' ? 'selected' : '' }}>Beginner Mode (More hints & feedback)</option>
                                    <option value="standard" {{ $setupDefaults['ai_assistance_level'] === 'standard' ? 'selected' : '' }}>Standard Mode (Balanced experience)</option>
                                    <option value="challenge" {{ $setupDefaults['ai_assistance_level'] === 'challenge' ? 'selected' : '' }}>Challenge Mode (No hints, harder follow-ups)</option>
                                </select>
                            </div>
                        </div>

                        <div class="assistance-field">
                            <label class="olbl">Question Types</label>
                            <div class="assistance-question-list">
                                @foreach([
                                    'Behavioral' => 'fa-regular fa-message',
                                    'Situational' => 'fa-regular fa-user',
                                    'Technical' => 'fa-solid fa-code',
                                    'Personal' => 'fa-regular fa-user-circle',
                                ] as $questionType => $questionIcon)
                                    <label class="assistance-question-card">
                                        <input type="checkbox" name="question_types[]" value="{{ $questionType }}" {{ in_array($questionType, $selectedQuestionTypes, true) ? 'checked' : '' }}>
                                        <span class="assistance-question-icon" aria-hidden="true"><i class="{{ $questionIcon }}"></i></span>
                                        <span class="assistance-question-text">{{ $questionType }} Questions</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="assistance-field">
                            <label class="olbl">Interviewer Strictness</label>
                            <div class="assistance-select-wrap">
                                <select class="oinp setup-input" name="interviewer_strictness" id="valStrictness">
                                    <option value="friendly" {{ $setupDefaults['interviewer_strictness'] === 'friendly' ? 'selected' : '' }}>Friendly Interviewer</option>
                                    <option value="neutral" {{ $setupDefaults['interviewer_strictness'] === 'neutral' ? 'selected' : '' }}>Neutral HR Interviewer</option>
                                    <option value="strict" {{ $setupDefaults['interviewer_strictness'] === 'strict' ? 'selected' : '' }}>Strict Technical Lead</option>
                                    <option value="executive" {{ $setupDefaults['interviewer_strictness'] === 'executive' ? 'selected' : '' }}>Executive Panel</option>
                                </select>
                            </div>
                        </div>

                        <div class="assistance-field">
                            <label class="olbl">Live Feedback Mode</label>
                            <div class="assistance-select-wrap">
                                <select class="oinp setup-input" name="live_feedback_mode" id="valFeedbackMode">
                                    <option value="coaching" {{ $setupDefaults['live_feedback_mode'] === 'coaching' ? 'selected' : '' }}>Coaching On</option>
                                    <option value="real_interview" {{ $setupDefaults['live_feedback_mode'] === 'real_interview' ? 'selected' : '' }}>Real Interview Mode</option>
                                </select>
                            </div>
                        </div>

                        <div class="assistance-context-panel">
                            <input type="hidden" name="company_persona" id="valPersona" value="{{ $setupDefaults['company_persona'] }}" class="setup-input">
                            <span class="assistance-context-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                            <div>
                                <strong class="assistance-context-title">Philippines hiring context</strong>
                                <div class="desc-text">The interviewer stays within local Philippine workplace expectations, including HR screening, communication clarity, professionalism, and role fit.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Response Mode -->
                <div class="setup-panel setup-response-card animate-fade-up delay-400" id="panel-response">
                    <div class="response-head">
                        <div class="response-head-icon" aria-hidden="true">
                            <i class="fa-solid fa-microphone"></i>
                        </div>
                        <h5 class="response-title">Response Mode</h5>
                    </div>
                    <div class="response-mode-list">
                        <label class="response-mode-card">
                            <input type="radio" name="response_mode" value="text" class="setup-input" {{ $setupDefaults['response_mode'] === 'text' ? 'checked' : '' }}>
                            <span>
                                <span class="response-mode-title">Text Mode</span>
                                <span class="response-mode-desc">Type your answers manually</span>
                            </span>
                        </label>
                        <label class="response-mode-card">
                            <input type="radio" name="response_mode" value="voice" class="setup-input" {{ $setupDefaults['response_mode'] === 'voice' ? 'checked' : '' }}>
                            <span>
                                <span class="response-mode-title">Voice Mode</span>
                                <span class="response-mode-desc">Speak through your microphone</span>
                            </span>
                        </label>
                        <label class="response-mode-card">
                            <input type="radio" name="response_mode" value="hybrid" class="setup-input" {{ $setupDefaults['response_mode'] === 'hybrid' ? 'checked' : '' }}>
                            <span>
                                <span class="response-mode-title">Hybrid Mode</span>
                                <span class="response-mode-desc">Voice-to-text with manual editing</span>
                            </span>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Right Column: Live Summary -->
            <div class="col-lg-4 animate-fade-up delay-200">
                <div style="position:sticky;top:20px;">
                    <div class="setup-panel" id="panel-summary" style="background:linear-gradient(145deg, rgba(59,130,246,0.08) 0%, rgba(59,130,246,0.02) 100%); border:1px solid rgba(59,130,246,0.25); box-shadow: 0 15px 35px rgba(59,130,246,0.1), inset 0 1px 1px rgba(255, 255, 255, 0.1); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);">
                        <h5 style="font-weight:800;margin-bottom:24px;color:var(--pur);text-align:center;letter-spacing:0.5px;"><i class="fa-solid fa-clipboard-list me-2"></i> Interview Summary</h5>
                        
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-globe"></i></span>
                            <span class="summary-label">Scenario:</span>
                            <span class="summary-val" id="sumScenario">{{ $selectedScenario['context_label'] ?? 'Philippines Job Interview' }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-briefcase"></i></span>
                            <span class="summary-label">Position:</span>
                            <span class="summary-val" id="sumPosition">Not Specified</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-signal"></i></span>
                            <span class="summary-label">Difficulty:</span>
                            <span class="summary-val" id="sumDifficulty">Medium</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-circle-question"></i></span>
                            <span class="summary-label">Questions:</span>
                            <span class="summary-val" id="sumQuestions">10</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-microphone"></i></span>
                            <span class="summary-label">Response Mode:</span>
                            <span class="summary-val" id="sumResponse">Voice</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-solid fa-shield-alt"></i></span>
                            <span class="summary-label">Strictness:</span>
                            <span class="summary-val" id="sumStrictness">Neutral HR Interviewer</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-message"></i></span>
                            <span class="summary-label">Live Feedback:</span>
                            <span class="summary-val" id="sumFeedbackMode">Coaching On</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-building"></i></span>
                            <span class="summary-label">Hiring Context:</span>
                            <span class="summary-val" id="sumPersona">Philippines hiring context</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-icon" aria-hidden="true"><i class="fa-regular fa-clock"></i></span>
                            <span class="summary-label">Est. Duration:</span>
                            <span class="summary-val text-success" id="sumDuration">15 Minutes</span>
                        </div>
                        
                        <div class="setup-start-action" style="margin-top:30px;">
                            <button type="submit" id="btn-start-interview" class="btn w-100 py-3 btn-shine" style="font-size:1.1rem;font-weight:700;border-radius:14px;background:var(--dash-primary, #60a5fa);color:white;border:none;box-shadow:0 8px 25px rgba(96,165,250,0.4);transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 30px rgba(96,165,250,0.6)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 8px 25px rgba(96,165,250,0.4)'">
                                Start Philippine Interview <i class="fa-solid fa-play ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div id="setupTransitionOverlay" class="finish-transition-overlay" role="status" aria-live="polite" aria-atomic="true">
        <div class="finish-loading-wrapper">
            <div class="finish-loading-circle"></div>
            <img src="{{ asset('img/logo.png') }}" alt="Loading interview">
        </div>
        <h4>Philippines Interview Ready</h4>
        <p>Please wait while we begin or resume your customized interview session.</p>
    </div>
</div>

<script>
    function updateSummary() {
        const scenarioSelect = document.getElementById('valScenario');
        if (scenarioSelect) {
            const selectedOption = scenarioSelect.options[scenarioSelect.selectedIndex];
            document.getElementById('sumScenario').innerText = selectedOption?.dataset.contextLabel || selectedOption?.text || 'Philippines Job Interview';
            document.getElementById('valCategory').value = selectedOption?.dataset.categoryId || '';
            document.getElementById('valFocus').value = selectedOption?.dataset.focus || 'Philippines Job Interview';
            const sourceSummary = document.getElementById('sourceSummary');
            if (sourceSummary) {
                sourceSummary.innerText = selectedOption?.dataset.sourceSummary || 'Philippines career and education sources';
            }
        }

        const posVal = document.getElementById('valPosition').value;
        document.getElementById('sumPosition').innerText = posVal || 'Not Specified';
        updateStartInterviewState();

        const diff = document.querySelector('input[name="difficulty"]:checked');
        if(diff) document.getElementById('sumDifficulty').innerText = diff.value.charAt(0).toUpperCase() + diff.value.slice(1);

        const numQ = document.getElementById('valNumQuestions').value;
        document.getElementById('sumQuestions').innerText = numQ;

        const resp = document.querySelector('input[name="response_mode"]:checked');
        if(resp) document.getElementById('sumResponse').innerText = resp.value.charAt(0).toUpperCase() + resp.value.slice(1);

        const strictness = document.getElementById('valStrictness');
        if (strictness) {
            document.getElementById('sumStrictness').innerText = strictness.options[strictness.selectedIndex].text;
        }

        const feedbackMode = document.getElementById('valFeedbackMode');
        if (feedbackMode) {
            document.getElementById('sumFeedbackMode').innerText = feedbackMode.options[feedbackMode.selectedIndex].text;
        }

        const personaInput = document.getElementById('valPersona');
        if (personaInput) {
            document.getElementById('sumPersona').innerText = personaInput.value || 'Standard';
        }

        const timeLimit = parseInt(document.getElementById('valTimeLimit').value);
        let durationStr = "Self-paced";
        if(timeLimit > 0) {
            durationStr = (numQ * timeLimit) + " Minutes";
        } else {
            durationStr = Math.round(numQ * 1.5) + " Minutes";
        }
        document.getElementById('sumDuration').innerText = durationStr;
        updateStartInterviewState();
    }

    function updateStartInterviewState() {
        const startButton = document.getElementById('btn-start-interview');
        if (!startButton) return;

        const requiredFields = [
            'valScenario',
            'valPosition',
            'valNumQuestions',
            'valTimeLimit',
            'valInterviewFormat',
            'valAssistance',
            'valStrictness',
            'valFeedbackMode',
            'valPersona',
        ];

        const hasRequiredFields = requiredFields.every(id => {
            const field = document.getElementById(id);
            return field && String(field.value || '').trim().length > 0;
        });

        const hasDifficulty = Boolean(document.querySelector('input[name="difficulty"]:checked'));
        const hasResponseMode = Boolean(document.querySelector('input[name="response_mode"]:checked'));
        const hasQuestionType = Boolean(document.querySelector('input[name="question_types[]"]:checked'));
        const canStart = hasRequiredFields && hasDifficulty && hasResponseMode && hasQuestionType;

        startButton.disabled = !canStart;
        startButton.classList.toggle('setup-start-disabled', !canStart);
        startButton.setAttribute('aria-disabled', canStart ? 'false' : 'true');
        startButton.title = canStart ? 'Start interview' : 'Complete all required details first';
    }

    document.querySelectorAll('.setup-input').forEach(el => {
        el.addEventListener('change', updateSummary);
        el.addEventListener('keyup', updateSummary);
    });

    const setupStepState = {
        index: 0,
        desktopQuery: window.matchMedia('(min-width: 992px)'),
        baseSteps: [
            { id: 'panel-basic', label: 'Details' },
            { id: 'panel-structure', label: 'Structure' },
            { id: 'panel-inclusive', label: 'Access' },
            { id: 'panel-content', label: 'Scenario' },
            { id: 'panel-response', label: 'Response' },
        ],
    };

    function getSetupSteps() {
        const steps = [...setupStepState.baseSteps];
        if (!setupStepState.desktopQuery.matches) {
            steps.push({ id: 'panel-summary', label: 'Summary' });
        }
        return steps;
    }

    function renderSetupStepper() {
        const track = document.getElementById('setupStepperTrack');
        if (!track) return;

        const steps = getSetupSteps();
        const mode = setupStepState.desktopQuery.matches ? 'desktop' : 'mobile';
        if (track.dataset.rendered === mode) return;

        track.innerHTML = steps.map((step, index) => `
            <button type="button" class="setup-stepper-item" data-setup-step="${index}" aria-label="Go to ${step.label}">
                <span class="setup-stepper-dot"></span>
                <span class="setup-stepper-label">${step.label}</span>
            </button>
        `).join('');
        track.dataset.rendered = mode;

        track.querySelectorAll('[data-setup-step]').forEach(button => {
            button.addEventListener('click', () => {
                showSetupStep(Number(button.dataset.setupStep));
            });
        });
    }

    function showSetupStep(nextIndex) {
        const section = document.getElementById('sec-interview-setup');
        const stepper = document.getElementById('setupStepper');
        const prevButton = document.getElementById('setupStepPrev');
        const nextButton = document.getElementById('setupStepNext');
        const isDesktop = setupStepState.desktopQuery.matches;
        const steps = getSetupSteps();

        if (!section || !stepper) return;

        renderSetupStepper();
        setupStepState.index = Math.max(0, Math.min(steps.length - 1, nextIndex));

        section.classList.add('setup-step-mode');
        section.classList.toggle('setup-summary-step', !isDesktop && steps[setupStepState.index]?.id === 'panel-summary');
        stepper.hidden = false;

        steps.forEach((step, index) => {
            const panel = document.getElementById(step.id);
            if (panel) {
                const isActivePanel = index === setupStepState.index;
                panel.classList.toggle('setup-step-active', isActivePanel);
                panel.classList.remove('setup-step-transition-in');
                if (isActivePanel) {
                    void panel.offsetWidth;
                    panel.classList.add('setup-step-transition-in');
                }
            }

            const stepButton = document.querySelector(`[data-setup-step="${index}"]`);
            if (stepButton) {
                stepButton.classList.toggle('is-active', index === setupStepState.index);
                stepButton.classList.toggle('is-complete', index < setupStepState.index);
                stepButton.setAttribute('aria-current', index === setupStepState.index ? 'step' : 'false');
            }
        });

        if (prevButton) prevButton.disabled = setupStepState.index === 0;
        if (nextButton) {
            const isLast = setupStepState.index === steps.length - 1;
            nextButton.hidden = isLast;
        }

    }

    document.getElementById('setupStepPrev')?.addEventListener('click', () => {
        showSetupStep(setupStepState.index - 1);
    });

    document.getElementById('setupStepNext')?.addEventListener('click', () => {
        showSetupStep(setupStepState.index + 1);
    });

    setupStepState.desktopQuery.addEventListener?.('change', () => {
        showSetupStep(setupStepState.index);
    });

    window.onload = () => {
        updateSummary();
        showSetupStep(0);
    };

    const setupForm = document.getElementById('setupForm');
    const setupTransitionOverlay = document.getElementById('setupTransitionOverlay');
    const startInterviewButton = document.getElementById('btn-start-interview');

    if (setupForm && setupTransitionOverlay) {
        setupForm.addEventListener('submit', function(event) {
            updateStartInterviewState();
            if (startInterviewButton?.disabled) {
                event.preventDefault();
                const firstMissingField = [
                    'valScenario',
                    'valPosition',
                    'valNumQuestions',
                    'valTimeLimit',
                    'valInterviewFormat',
                    'valAssistance',
                    'valStrictness',
                    'valFeedbackMode',
                    'valPersona',
                ].map(id => document.getElementById(id)).find(field => field && String(field.value || '').trim().length === 0);
                firstMissingField?.focus();
                return;
            }

            setupTransitionOverlay.classList.add('active');
            document.body.classList.add('finish-transition-active');

            if (startInterviewButton) {
                startInterviewButton.disabled = true;
                startInterviewButton.innerHTML = 'Begin / Resume Interview <i class="fa-solid fa-spinner fa-spin ms-2"></i>';
            }
        });

        window.addEventListener('pageshow', function() {
            setupTransitionOverlay.classList.remove('active');
            document.body.classList.remove('finish-transition-active');

            if (startInterviewButton) {
                startInterviewButton.disabled = false;
                startInterviewButton.innerHTML = 'Start Philippine Interview <i class="fa-solid fa-play ms-2"></i>';
            }
        });
    }
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#panel-basic', popover: { title: 'Philippines Interview', description: 'Choose an optional local category and enter the target position.', side: 'top', align: 'center' }},
            { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Set difficulty, question count, and optional response timing before you start.', side: 'top', align: 'center' }},
            { element: '#panel-content', popover: { title: 'Practice Scenario', description: 'Pick the Philippines scenario, assistance level, and question types.', side: 'top', align: 'center' }},
            { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose typed, voice, or hybrid answers depending on how you want to practice.', side: 'top', align: 'center' }},
            { element: '#panel-summary', popover: { title: 'Live Summary', description: 'Confirm your interview setup before generating the practice session.', side: 'top', align: 'center' }},
            { element: '#btn-start-interview', popover: { title: 'Start Interview', description: 'Generate your customized Philippine interview when the setup looks right.', side: 'top', align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#panel-basic', popover: { title: 'Philippines Interview', description: 'Choose an optional local category and enter the target position.', side: 'top', align: 'center' }},
            { element: '#panel-structure', popover: { title: 'Interview Structure', description: 'Set difficulty, question count, and optional response timing before you start.', side: 'top', align: 'center' }},
            { element: '#panel-content', popover: { title: 'Practice Scenario', description: 'Pick the Philippines scenario, assistance level, and question types.', side: 'top', align: 'center' }},
            { element: '#panel-response', popover: { title: 'Response Mode', description: 'Choose typed, voice, or hybrid answers depending on how you want to practice.', side: 'top', align: 'center' }},
            { element: '#panel-summary', popover: { title: 'Live Summary', description: 'Confirm your interview setup before generating the practice session.', side: 'top', align: 'center' }},
            { element: '#btn-start-interview', popover: { title: 'Start Interview', description: 'Generate your customized Philippine interview when the setup looks right.', side: 'top', align: 'center' }}
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
