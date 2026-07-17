@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Voice Rehearsal')

@section('content')
<style>
    /* Premium Dashboard Styles inherited/adapted */
    .premium-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .premium-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 45px rgba(0, 0, 0, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.08);
    }
    /* Wave animation */
    @keyframes wave { 0%, 100% { height: 20px; } 50% { height: 80px; } }
    .wave-bar {
        width: 6px; background: #3b82f6; border-radius: 4px;
        animation: wave 1s infinite ease-in-out;
    }
    /* Filler word highlight */
    .filler-word {
        background: rgba(248, 113, 113, 0.2);
        color: #f87171;
        padding: 0 4px;
        border-radius: 4px;
        font-weight: 600;
    }
    .keyword-highlight {
        background: rgba(52, 211, 153, 0.2);
        color: #34d399;
        padding: 0 4px;
        border-radius: 4px;
        font-weight: 600;
    }
    .stat-box {
        background: var(--bg3);
        border: 1px solid var(--bd);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .stat-val { font-size: 1.5rem; font-weight: 700; color: var(--tx); }
    .stat-lbl { font-size: 0.8rem; color: var(--tx3); text-transform: uppercase; letter-spacing: 1px; }

    .tab-pane { display: none; }
    .tab-pane.active { display: block; animation: fadeIn 0.4s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

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
    @media (max-width: 767px) {
        #voice-rehearsal-page .sr-page-actions {
            display: block !important;
            margin-bottom: 12px !important;
        }
        #voice-rehearsal-page #moduleTabs {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            width: 100%;
        }
        #voice-rehearsal-page #moduleTabs .nav-link {
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 0.78rem;
            text-align: center;
        }
        #voice-rehearsal-page .premium-card {
            padding: 14px !important;
            border-radius: 14px !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        #voice-rehearsal-page .premium-card:hover {
            transform: none;
        }
        #voice-rehearsal-page .premium-card > .d-flex.justify-content-between {
            display: grid !important;
            grid-template-columns: 1fr;
            gap: 8px;
            align-items: stretch !important;
            margin-bottom: 14px !important;
        }
        #voice-rehearsal-page #categorySelect,
        #voice-rehearsal-page .premium-card .btn {
            width: 100% !important;
            min-height: 42px;
        }
        #voice-rehearsal-page #promptText {
            font-size: 1.05rem !important;
            line-height: 1.35 !important;
        }
        #voice-rehearsal-page .text-center.mb-5 {
            margin-bottom: 18px !important;
        }
        #voice-rehearsal-page [style*="height:160px"] {
            height: 118px !important;
            margin-bottom: 18px !important;
            border-radius: 16px !important;
        }
        #voice-rehearsal-page .voice-recorder-controls {
            display: flex !important;
            flex-wrap: wrap;
            justify-content: center !important;
            align-items: center;
            gap: 8px !important;
        }
        #voice-rehearsal-page .voice-recorder-controls .btn {
            width: auto !important;
            min-width: 140px;
            padding: 9px 10px !important;
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        #voice-rehearsal-page .stat-box {
            padding: 11px 7px;
            border-radius: 12px;
        }
        #voice-rehearsal-page .row.g-2.mb-4 {
            display: flex;
            flex-wrap: nowrap;
            margin-left: -4px;
            margin-right: -4px;
        }
        #voice-rehearsal-page .row.g-2.mb-4 > .col-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            width: 33.333333%;
            padding-left: 4px;
            padding-right: 4px;
        }
        #voice-rehearsal-page .stat-val {
            font-size: 1.05rem;
            line-height: 1.1;
        }
        #voice-rehearsal-page .stat-lbl {
            font-size: 0.62rem;
            letter-spacing: 0;
        }
        #voice-rehearsal-page #transcriptView {
            min-height: 108px !important;
            padding: 12px !important;
            font-size: 0.9rem !important;
            line-height: 1.45 !important;
        }
        #voice-rehearsal-page #analysisPanel .d-flex.justify-content-between.align-items-center {
            gap: 10px;
        }
        #voice-rehearsal-page #tab-analytics .premium-card .d-flex.justify-content-between {
            display: grid !important;
            grid-template-columns: 1fr;
            gap: 8px;
            align-items: stretch !important;
        }
        #voice-rehearsal-page .voice-history-table {
            table-layout: fixed !important;
            min-width: 0 !important;
            width: 100% !important;
        }
        #voice-rehearsal-page .voice-history-table th,
        #voice-rehearsal-page .voice-history-table td {
            padding: 10px 5px !important;
            vertical-align: middle !important;
            white-space: normal !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
            text-align: center;
            font-size: 0.72rem !important;
        }
        #voice-rehearsal-page .voice-history-table th {
            white-space: nowrap !important;
            font-size: 0.68rem !important;
        }
        #voice-rehearsal-page .voice-history-table th:first-child,
        #voice-rehearsal-page .voice-history-table td:first-child {
            width: 18%;
            text-align: left;
        }
        #voice-rehearsal-page .voice-history-table th:nth-child(2),
        #voice-rehearsal-page .voice-history-table td:nth-child(2) {
            width: 34%;
        }
        #voice-rehearsal-page .voice-history-table th:nth-child(3),
        #voice-rehearsal-page .voice-history-table td:nth-child(3),
        #voice-rehearsal-page .voice-history-table th:nth-child(4),
        #voice-rehearsal-page .voice-history-table td:nth-child(4),
        #voice-rehearsal-page .voice-history-table th:nth-child(5),
        #voice-rehearsal-page .voice-history-table td:nth-child(5) {
            width: 16%;
        }
        #voice-rehearsal-page .voice-history-table .badge {
            max-width: 100%;
            white-space: normal;
            line-height: 1.08;
            padding: 4px 5px;
            border-radius: 8px;
            font-size: 0.56rem;
        }
        #voice-rehearsal-page .voice-suggestion-head {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 8px !important;
            margin-bottom: 4px;
        }
        #voice-rehearsal-page .voice-suggestion-head i {
            flex: 0 0 18px;
            width: 18px;
            margin-top: 0 !important;
            text-align: center;
        }
        #voice-rehearsal-page .voice-suggestion-title {
            min-width: 0;
            margin: 0;
            line-height: 1.25;
        }
    }
</style>
@include('partials.page-hero-styles')

<div class="db-section active" id="voice-rehearsal-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a3 3 0 0 0-3 3v5a3 3 0 0 0 6 0V7a3 3 0 0 0-3-3Z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3M8 21h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Philippines Voice Rehearsal
                </h4>
                <p class="sr-page-hero-subtitle">Practice delivery, pacing, and answer clarity for Philippines interview scenarios.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="voicePanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="voiceBlue" x1="70" y1="36" x2="154" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#voicePanel)" stroke="#BFDBFE" stroke-width="3"/><rect x="91" y="40" width="38" height="58" rx="19" fill="url(#voiceBlue)"/><path d="M75 78a35 35 0 0 0 70 0M110 113v14M92 127h36" fill="none" stroke="#2563EB" stroke-width="7" stroke-linecap="round"/><path d="M55 72v20M68 60v44M152 60v44M165 72v20" stroke="#38BDF8" stroke-width="7" stroke-linecap="round" opacity=".75"/><circle cx="160" cy="44" r="17" fill="#22C55E"/><path d="M153 44l5 5 10-12" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="sr-page-actions">
        <ul class="nav nav-pills" id="moduleTabs" style="margin-bottom:0;">
            <li class="nav-item"><a class="nav-link active" href="#" data-target="tab-practice">Practice</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-target="tab-analytics">History & Analytics</a></li>
        </ul>
    </div>

    <!-- TAB: PRACTICE -->
    <div id="tab-practice" class="tab-pane active">
        <div class="row g-4">
            <!-- Left: Controls & Recording -->
            <div class="col-lg-8 animate-fade-up delay-100">
                <div class="premium-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <select id="categorySelect" class="form-select w-auto" style="background:var(--bg3);color:var(--tx);border-color:var(--bd);border-radius:10px;">
                            <option value="Tell Me About Yourself">General Job Interview</option>
                            <option value="Strengths and Weaknesses">Strengths & Weaknesses</option>
                            <option value="Leadership">Leadership / Teamwork</option>
                            <option value="Problem Solving">Problem Solving</option>
                            <option value="Technical">IT / Technical Interview</option>
                            <option value="Scholarship">Scholarship / Admission</option>
                        </select>
                        <button id="btnRandomizePrompt" class="btn btn-sm btn-outline-secondary" onclick="randomizePrompt()" style="border-radius:8px;"><i class="fa-solid fa-shuffle"></i> Randomize</button>
                    </div>

                    <div class="text-center mb-5">
                        <h5 style="color:#60a5fa;font-size:0.85rem;font-weight:700;letter-spacing:1px;margin-bottom:12px;">PHILIPPINES PROMPT</h5>
                        <h3 id="promptText" style="color:var(--tx);font-weight:600;line-height:1.4;">"Tell me about a time you showed leadership."</h3>
                    </div>

                    <!-- Mic Visualization -->
                    <div style="margin-bottom:40px;position:relative;height:160px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(139,92,246,0.05) 100%);border-radius:24px;border:1px solid rgba(139,92,246,0.2);box-shadow:inset 0 4px 20px rgba(0,0,0,0.1), 0 10px 30px rgba(59,130,246,0.1);">
                        <!-- Idle State -->
                        <div id="micIdle" class="text-center">
                            <div style="width:64px;height:64px;border-radius:50%;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto;color:#60a5fa;font-size:1.8rem;transition:all 0.3s;">
                                <i class="fa-solid fa-microphone"></i>
                            </div>
                            <div id="statusText" style="color:var(--tx3);margin-top:12px;font-size:0.9rem;">Ready to record</div>
                        </div>

                        <!-- Active State -->
                        <div id="micActive" style="display:none;align-items:center;gap:6px;height:100%;">
                            <div class="wave-bar" style="height:30px;animation-delay:0.1s"></div>
                            <div class="wave-bar" style="height:60px;animation-delay:0.2s"></div>
                            <div class="wave-bar" style="height:80px;animation-delay:0.3s"></div>
                            <div class="wave-bar" style="height:50px;animation-delay:0.4s"></div>
                            <div class="wave-bar" style="height:70px;animation-delay:0.5s"></div>
                            <div class="wave-bar" style="height:40px;animation-delay:0.6s"></div>
                            <div class="wave-bar" style="height:90px;animation-delay:0.7s"></div>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="voice-recorder-controls d-flex justify-content-center gap-3 mb-4">
                        <button id="btnStart" class="btn btn-shine" style="background:var(--dash-primary, #60a5fa);color:#fff;border-radius:12px;padding:12px 24px;font-weight:600;border:none;box-shadow:0 4px 15px rgba(96,165,250,0.4);" onclick="startRec()"><i class="fa-solid fa-play me-2"></i> Start</button>
                        <button id="btnPause" class="btn btn-warning" style="display:none;color:#fff;border-radius:12px;padding:12px 24px;font-weight:600;" onclick="pauseRec()"><i class="fa-solid fa-pause me-2"></i> Pause</button>
                        <button id="btnResume" class="btn btn-info" style="display:none;color:#fff;border-radius:12px;padding:12px 24px;font-weight:600;" onclick="resumeRec()"><i class="fa-solid fa-play me-2"></i> Resume</button>
                        <button id="btnStop" class="btn btn-danger" style="display:none;color:#fff;border-radius:12px;padding:12px 24px;font-weight:600;" onclick="stopRec()"><i class="fa-solid fa-stop me-2"></i> Stop & Analyze</button>
                        <button id="btnRerecord" class="btn btn-outline-secondary" style="display:none;border-radius:12px;padding:12px 24px;font-weight:600;" onclick="resetRec()"><i class="fa-solid fa-rotate-left me-2"></i> Retry</button>
                    </div>

                    <!-- Live Stats -->
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-val" id="timeDisp">0:00</div>
                                <div class="stat-lbl">Duration</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-val" id="wpmDisp">0</div>
                                <div class="stat-lbl">WPM</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-val" id="fillerDisp" style="color:#f87171;">0</div>
                                <div class="stat-lbl">Fillers</div>
                            </div>
                        </div>
                    </div>

                    <!-- Transcript Box -->
                    <div>
                        <div class="d-flex justify-content-between mb-2">
                            <label style="font-size:0.85rem;color:var(--tx3);font-weight:600;text-transform:uppercase;">Live Answer Transcript</label>
                            <span id="transStatus" style="font-size:0.8rem;color:#34d399;display:none;"><i class="fa-solid fa-circle-dot fa-fade me-1"></i> Transcribing</span>
                        </div>
                        <div id="transcriptView" style="background:var(--bg3);border:1px solid var(--bd);border-radius:12px;padding:16px;min-height:120px;color:var(--tx);font-size:1.05rem;line-height:1.6;white-space:pre-wrap;" contenteditable="false">Your answer will appear here...</div>
                        <p class="mt-2" style="font-size:0.8rem;color:var(--tx3);display:none;" id="editHint"><i class="fa-solid fa-pencil me-1"></i> You can edit the transcript above manually before saving.</p>
                    </div>
                </div>
            </div>

            <!-- Right: Post-Analysis Dashboard -->
            <div class="col-lg-4 animate-fade-up delay-200">
                <div id="analysisPanel" style="opacity:0.5;pointer-events:none;transition:opacity 0.4s;">
                    <div class="premium-card mb-4" style="background: linear-gradient(180deg, var(--sf) 0%, rgba(59,130,246,0.05) 100%);">
                        <h6 class="fw-bold mb-4"><i class="fa-solid fa-chart-pie me-2" style="color:#60a5fa;"></i> Practice Assessment</h6>
                        
                        <!-- Clarity & Delivery Stability -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="text-center w-50">
                                <div style="font-size:2rem;font-weight:800;color:#34d399;" id="resClarity">--%</div>
                                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Clarity Score</div>
                            </div>
                            <div style="width:1px;height:40px;background:var(--bd);"></div>
                            <div class="text-center w-50">
                                <div style="font-size:1.2rem;font-weight:700;color:#60a5fa;" id="resConfidence">--</div>
                                <div style="font-size:0.75rem;color:var(--tx3);text-transform:uppercase;">Delivery Stability</div>
                            </div>
                        </div>

                        <!-- Pace Rating -->
                        <div class="mb-4 p-3" style="background:var(--bg3);border-radius:10px;border:1px solid var(--bd);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="font-size:0.85rem;color:var(--tx2);">Speaking Pace</span>
                                <span style="font-weight:700;" id="resPaceRating">--</span>
                            </div>
                            <div class="progress-track" style="height:6px;">
                                <div id="paceBar" class="progress-fill" style="width:0%;background:#3b82f6;"></div>
                            </div>
                            <div style="font-size:0.75rem;color:var(--tx3);margin-top:6px;text-align:right;" id="resPaceDesc">Optimal: 100-150 WPM</div>
                        </div>

                        <!-- Pronunciation & Keywords -->
                        <div class="mb-4">
                            <h6 style="font-size:0.85rem;color:var(--tx3);text-transform:uppercase;margin-bottom:8px;">Detected Keywords</h6>
                            <div id="resKeywords" class="d-flex flex-wrap gap-2">
                                <span style="color:var(--tx3);font-size:0.85rem;">Waiting for analysis...</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 style="font-size:0.85rem;color:var(--tx3);text-transform:uppercase;margin-bottom:8px;">AI Feedback</h6>
                            <div class="p-2 mb-2" style="background:rgba(52,211,153,0.05);border:1px solid rgba(52,211,153,0.2);border-radius:8px;">
                                <strong style="color:#34d399;font-size:0.8rem;"><i class="fa-solid fa-check"></i> Strengths</strong>
                                <div id="resStrengths" style="font-size:0.85rem;color:var(--tx2);margin-top:4px;">--</div>
                            </div>
                            <div class="p-2" style="background:rgba(248,113,113,0.05);border:1px solid rgba(248,113,113,0.2);border-radius:8px;">
                                <strong style="color:#f87171;font-size:0.8rem;"><i class="fa-solid fa-arrow-trend-up"></i> Needs Work</strong>
                                <div id="resWeak" style="font-size:0.85rem;color:var(--tx2);margin-top:4px;">--</div>
                            </div>
                        </div>

                        <!-- Audio Playback -->
                        <div class="mb-4">
                            <h6 style="font-size:0.85rem;color:var(--tx3);text-transform:uppercase;margin-bottom:8px;">Playback</h6>
                            <audio id="audioPlayback" controls style="width:100%;height:40px;outline:none;" class="mb-2"></audio>
                            <div id="playbackStatus" style="font-size:0.76rem;color:var(--tx3);">Playback appears after you stop recording.</div>
                        </div>

                        <button id="btnSave" class="btn w-100 btn-shine" style="background:#34d399;color:#fff;font-weight:600;border-radius:12px;border:none;box-shadow:0 4px 15px rgba(52,211,153,0.4);" onclick="saveSession()"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Save Session</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fact-grounded revision comparison -->
        <div id="comparisonPanel" class="premium-card mt-4" style="display:none;">
            <h5 class="fw-bold mb-4"><i class="fa-solid fa-code-compare me-2" style="color:#60a5fa;"></i> Fact-Grounded Revision</h5>
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 style="color:var(--tx2);font-size:0.9rem;">Your Answer</h6>
                    <div id="compUser" class="p-3" style="background:var(--bg3);border:1px solid var(--bd);border-radius:12px;font-size:0.95rem;color:var(--tx);min-height:100px;"></div>
                </div>
                <div class="col-md-6">
                    <h6 style="color:#60a5fa;font-size:0.9rem;">Revision Template</h6>
                    <div id="compAI" class="p-3" style="background:rgba(96,165,250,0.05);border:1px solid rgba(96,165,250,0.2);border-radius:12px;font-size:0.95rem;color:var(--tx);min-height:100px;"></div>
                    <div class="mt-2" style="color:var(--tx3);font-size:.78rem;">Uses your transcript as its source. Fill placeholders only with facts you can verify.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB: HISTORY & ANALYTICS -->
    <div id="tab-analytics" class="tab-pane">
        <div class="row g-4">
            <div class="col-lg-8 animate-fade-up delay-100">
                <!-- Progress Charts -->
                <div class="premium-card mb-4">
                    <h5 class="fw-bold mb-4">Progress Analytics</h5>
                    <div style="height:250px;">
                        <canvas id="voiceProgressChart"></canvas>
                    </div>
                </div>

                <!-- Rehearsal History -->
                <div class="premium-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0">Rehearsal History</h5>
                        <button class="btn btn-sm btn-outline-primary" style="border-radius:8px;" onclick="downloadReport()"><i class="fa-solid fa-download me-1"></i> Download Report (PDF)</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table custom-table voice-history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Prompt Scenario</th>
                                    <th>Clarity</th>
                                    <th>WPM</th>
                                    <th>Fillers</th>
                                </tr>
                            </thead>
                            <tbody id="historyTable">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 animate-fade-up delay-200">
                <!-- AI Practice Suggestions -->
                <div class="premium-card" style="background: linear-gradient(135deg, var(--sf) 0%, rgba(251,191,36,0.05) 100%);">
                    <h6 class="fw-bold mb-4"><i class="fa-solid fa-lightbulb me-2" style="color:#fbbf24;"></i> Recommended Voice Practice</h6>
                    
                    <div class="p-3 mb-3" style="background:var(--bg3);border-radius:12px;border:1px solid var(--bd);">
                        <div>
                            <div class="voice-suggestion-head">
                                <i class="fa-solid fa-gauge-high" style="color:#60a5fa;"></i>
                                <div class="voice-suggestion-title" style="font-weight:600;font-size:0.9rem;">Pace Yourself</div>
                            </div>
                            <div style="font-size:0.8rem;color:var(--tx2);">Your average pace is 165 WPM. Try taking slight pauses between sentences.</div>
                        </div>
                    </div>
                    <div class="p-3 mb-3" style="background:var(--bg3);border-radius:12px;border:1px solid var(--bd);">
                        <div>
                            <div class="voice-suggestion-head">
                                <i class="fa-solid fa-comment-slash" style="color:#f87171;"></i>
                                <div class="voice-suggestion-title" style="font-weight:600;font-size:0.9rem;">Reduce Fillers</div>
                            </div>
                            <div style="font-size:0.8rem;color:var(--tx2);">You used "Um" 12 times last session. Try silent pauses instead of filler words.</div>
                        </div>
                    </div>
                    <div class="p-3" style="background:var(--bg3);border-radius:12px;border:1px solid var(--bd);">
                        <div>
                            <div class="voice-suggestion-head">
                                <i class="fa-solid fa-briefcase" style="color:#34d399;"></i>
                                <div class="voice-suggestion-title" style="font-weight:600;font-size:0.9rem;">Practice IT / Technical</div>
                            </div>
                            <div style="font-size:0.8rem;color:var(--tx2);">Your clarity can drop on technical explanations. Practice the IT / Technical scenario next.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Tab Switching
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        e.target.classList.add('active');
        document.getElementById(e.target.getAttribute('data-target')).classList.add('active');
    });
});

// AI-backed practice prompt bank with a local fallback for provider outages.
const fallbackPrompts = {
    "Tell Me About Yourself": ["Walk me through your background and connect it to the Philippines role or program you are preparing for.", "What should a Philippine interviewer remember about you after your first two minutes?", "How would you summarize your strengths, experience, and next career goal in the Philippine context?"],
    "Strengths and Weaknesses": ["What is one strength you can prove with a specific school, internship, freelance, or work example?", "Tell me about a weakness you are actively improving and what changed because of that work.", "Describe feedback you received from a teacher, supervisor, client, or team lead and how you used it to improve."],
    "Leadership": ["Tell me about a time you led a team through uncertainty in school, work, internship, or community work.", "Describe a situation where you had to resolve conflict while keeping the work moving.", "Give an example of how you motivated others toward a shared goal in a Philippine team setting."],
    "Problem Solving": ["Tell me about a complex problem you solved with limited information in school, work, or training.", "Describe a time you had competing deadlines and how you chose what to do first.", "How would you handle a Philippine interviewer asking about salary expectations, schedule, or work setup?"],
    "Technical": ["Explain a technical concept from your experience to a non-technical Philippine interviewer.", "Walk me through your debugging process when the cause is unclear.", "Describe a technical tradeoff you made for a class, client, employer, or startup project and how you evaluated it."],
    "Scholarship": ["Why does this Philippine scholarship or admission program fit your academic and career plan?", "Tell me about a challenge that shaped your goals and how you responded.", "Describe how you will contribute to your school, community, or the Philippines if selected."]
};

function voiceScenarioLabel(category) {
    const labels = {
        "Tell Me About Yourself": "General Job Interview",
        "Strengths and Weaknesses": "Strengths & Weaknesses",
        "Leadership": "Leadership / Teamwork",
        "Problem Solving": "Problem Solving",
        "Technical": "IT / Technical Interview",
        "Scholarship": "Scholarship / Admission"
    };

    return labels[category] || category || 'General Job Interview';
}

function localFallbackPrompt(category) {
    const list = fallbackPrompts[category] || fallbackPrompts["Tell Me About Yourself"];
    return list[Math.floor(Math.random() * list.length)];
}

async function randomizePrompt() {
    const cat = document.getElementById('categorySelect').value;
    const promptEl = document.getElementById('promptText');
    const btn = document.getElementById('btnRandomizePrompt');
    const originalBtn = btn ? btn.innerHTML : '';

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating';
    }
    promptEl.innerText = '"Generating an AI practice question..."';

    try {
        const response = await fetch("{{ route('user.drills.voice.prompt') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ category: cat })
        });
        if (!response.ok) throw new Error(`Prompt generation failed with status ${response.status}`);

        const data = await response.json();
        const prompt = cleanTranscriptText(data.prompt || '');
        if (!prompt) throw new Error('AI returned an empty prompt');

        promptEl.innerText = `"${prompt}"`;
    } catch (error) {
        console.error('AI prompt generation failed:', error);
        promptEl.innerText = `"${localFallbackPrompt(cat)}"`;
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalBtn || '<i class="fa-solid fa-shuffle"></i> Randomize';
        }
    }
}
document.getElementById('categorySelect').addEventListener('change', randomizePrompt);

// Recording Logic
let recognition = null;
let recognitionActive = false;
let shouldAutoRestartRecognition = false;
let isRec = false;
let isPaused = false;
let transcript = "";
let committedSpeechTranscript = "";
let liveSpeechInterim = "";
let lastCommittedSpeech = "";
let lastCommittedAt = 0;
let recognitionRestartDelay = 300;
let timer = null;
let seconds = 0;
let mediaRecorder = null;
let mediaStream = null;
let audioObjectUrl = null;
let audioChunks = [];
const BrowserSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const speechLocale = document.documentElement.dataset.speechLocale || navigator.language || 'en-US';
const serverDetectedMobile = @json($isMobile);
const mobileSpeechSurface = serverDetectedMobile
    || window.matchMedia('(max-width: 767px)').matches
    || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
let recognitionStopResolver = null;
let recognitionStopTimer = null;
let stopInProgress = false;

const fillerWordsList = ['you know', 'i mean', 'um', 'uh', 'erm', 'hmm', 'like', 'actually', 'basically', 'literally'];
const duplicateSafeWordSet = new Set([
    'i', "i'm", 'the', 'a', 'an', 'and', 'to', 'of', 'for', 'in', 'on', 'it', 'is', 'was',
    'were', 'am', 'are', 'my', 'we', 'you', 'that', 'this', 'with', 'um', 'uh', 'like'
]);
let fillerCount = 0;
let wordCount = 0;

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
    return cleanTranscriptText(value).split(/\s+/u).filter(Boolean);
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
    return Array.from(normalizedPhrase).length > 2 || duplicateSafeWordSet.has(normalizedPhrase);
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
        if ((result[i].confidence || 0) > (best?.confidence || 0)) best = result[i];
    }
    return best ? best.transcript : '';
}

function commitSpeechSegment(segment) {
    const cleanSegment = collapseRepeatedSpeech(cleanTranscriptText(segment));
    if (!cleanSegment) return;

    const normalized = normalizeTranscriptForMatch(cleanSegment);
    const now = Date.now();
    if (normalized && normalized === lastCommittedSpeech && (now - lastCommittedAt) < 5000) return;

    committedSpeechTranscript = collapseRepeatedSpeech(appendWithoutOverlap(committedSpeechTranscript, cleanSegment));
    lastCommittedSpeech = normalized;
    lastCommittedAt = now;
}

function renderSpeechTranscript() {
    transcript = mergeTranscriptParts(committedSpeechTranscript, liveSpeechInterim);
    processTranscript(transcript);
}

function finalizeInterimTranscript() {
    if (!liveSpeechInterim) return;
    commitSpeechSegment(liveSpeechInterim);
    liveSpeechInterim = '';
    renderSpeechTranscript();
}

function setTranscriptionStatus(message, color = '#34d399') {
    const status = document.getElementById('transStatus');
    status.textContent = message;
    status.style.color = color;
    status.style.display = message ? 'inline-block' : 'none';
}

function resolveRecognitionStopWaiter() {
    if (recognitionStopTimer) {
        clearTimeout(recognitionStopTimer);
        recognitionStopTimer = null;
    }

    if (recognitionStopResolver) {
        const resolve = recognitionStopResolver;
        recognitionStopResolver = null;
        resolve();
    }
}

function waitForRecognitionStop(timeoutMs = mobileSpeechSurface ? 1600 : 800) {
    return new Promise(resolve => {
        resolveRecognitionStopWaiter();
        recognitionStopResolver = resolve;
        recognitionStopTimer = setTimeout(resolveRecognitionStopWaiter, timeoutMs);

        if (!recognitionActive) {
            setTimeout(resolveRecognitionStopWaiter, mobileSpeechSurface ? 450 : 200);
        }
    });
}

function startSpeechRecognitionEngine() {
    if (!recognition || recognitionActive || !isRec || isPaused || !shouldAutoRestartRecognition) return;

    try {
        recognition.start();
        recognitionActive = true;
    } catch (error) {
        if (!error || error.name !== 'InvalidStateError') {
            console.error('Speech recognition failed to start:', error);
            setTranscriptionStatus('Unable to start transcription', '#f87171');
        }
    }
}

if (BrowserSpeechRecognition) {
    recognition = new BrowserSpeechRecognition();
    recognition.continuous = !mobileSpeechSurface;
    recognition.interimResults = true;
    recognition.lang = speechLocale;
    recognition.maxAlternatives = 3;

    recognition.onstart = () => {
        recognitionActive = true;
        recognitionRestartDelay = 300;
        setTranscriptionStatus('Transcribing');
    };

    recognition.onresult = (event) => {
        const interimParts = [];
        for (let i = event.resultIndex; i < event.results.length; ++i) {
            const recognizedText = bestSpeechAlternative(event.results[i]);
            if (!recognizedText) continue;

            if (event.results[i].isFinal) {
                commitSpeechSegment(recognizedText);
            } else {
                interimParts.push(recognizedText);
            }
        }

        liveSpeechInterim = cleanTranscriptText(interimParts.join(' '));
        renderSpeechTranscript();
    };

    recognition.onerror = (event) => {
        recognitionActive = false;
        const error = event.error || 'unknown';
        console.warn('Speech recognition error:', error);

        if (['not-allowed', 'service-not-allowed', 'audio-capture'].includes(error)) {
            shouldAutoRestartRecognition = false;
            const message = error === 'audio-capture' ? 'Microphone unavailable' : 'Microphone permission denied';
            setTimeout(async () => {
                await stopRec(false);
                setTranscriptionStatus(message, '#f87171');
            }, 0);
        } else if (error === 'no-speech') {
            recognitionRestartDelay = 500;
            if (isRec && !isPaused) {
                setTranscriptionStatus('Still listening - speak close to the mic', '#fbbf24');
            }
        } else if (error === 'network') {
            recognitionRestartDelay = 1500;
            setTranscriptionStatus('Reconnecting transcription', '#fbbf24');
        }
    };

    recognition.onend = () => {
        recognitionActive = false;
        if (shouldAutoRestartRecognition && isRec && !isPaused) {
            setTimeout(startSpeechRecognitionEngine, recognitionRestartDelay);
        }
        resolveRecognitionStopWaiter();
    };
}

function escapeTranscriptHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function countFillersInTranscript(text) {
    return fillerWordsList.reduce((count, filler) => {
        const regex = new RegExp(`\\b${filler}\\b`, 'gi');
        return count + (text.match(regex)?.length || 0);
    }, 0);
}

function processTranscript(text) {
    const box = document.getElementById('transcriptView');
    text = collapseRepeatedSpeech(cleanTranscriptText(text));
    transcript = text;
    
    // Count words
    const words = wordsForTranscript(text);
    wordCount = words.length;
    
    // Detect Fillers and Highlights
    fillerCount = countFillersInTranscript(text);
    let formattedHtml = escapeTranscriptHtml(text);
    
    fillerWordsList.forEach(filler => {
        const regex = new RegExp(`\\b${filler}\\b`, 'gi');
        formattedHtml = formattedHtml.replace(regex, `<span class="filler-word">$&</span>`);
    });
    
    // Highlight only terms that are actually present in the transcript.
    ['leadership', 'team', 'success', 'problem', 'solved', 'agile', 'communication', 'manager'].forEach(kw => {
        const regex = new RegExp(`\\b${kw}\\b`, 'gi');
        formattedHtml = formattedHtml.replace(regex, `<span class="keyword-highlight">$&</span>`);
    });
    
    box.innerHTML = formattedHtml || (isRec ? "Listening..." : "");
    document.getElementById('fillerDisp').innerText = fillerCount;
    updateWPM();
}

function updateTimer() {
    seconds++;
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    document.getElementById('timeDisp').innerText = `${m}:${s.toString().padStart(2, '0')}`;
    updateWPM();
}

function updateWPM() {
    if (seconds > 0) {
        const mins = seconds / 60;
        const wpm = Math.round(wordCount / mins);
        document.getElementById('wpmDisp').innerText = wpm;
    }
}

function setPlaybackStatus(message, color = 'var(--tx3)') {
    const status = document.getElementById('playbackStatus');
    if (!status) return;
    status.textContent = message;
    status.style.color = color;
}

function clearAudioPlayback() {
    if (audioObjectUrl) {
        URL.revokeObjectURL(audioObjectUrl);
        audioObjectUrl = null;
    }

    const playback = document.getElementById('audioPlayback');
    if (playback) {
        playback.removeAttribute('src');
        playback.load();
    }

    setPlaybackStatus('Playback appears after you stop recording.');
}

function releaseMediaStream(stream = mediaStream) {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
    if (mediaStream === stream) {
        mediaStream = null;
    }
}

async function ensureMicrophoneAccess() {
    if (!navigator.mediaDevices?.getUserMedia) return true;

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            }
        });
        releaseMediaStream(stream);
        return true;
    } catch (error) {
        console.error('Microphone access failed:', error);
        const denied = error?.name === 'NotAllowedError' || error?.name === 'SecurityError';
        setTranscriptionStatus(denied ? 'Microphone permission denied' : 'Microphone unavailable', '#f87171');
        return false;
    }
}

function shouldCaptureAudioPlayback() {
    return !mobileSpeechSurface && Boolean(navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
}

function preferredAudioMimeType() {
    if (!window.MediaRecorder || typeof MediaRecorder.isTypeSupported !== 'function') return '';

    return [
        'audio/webm;codecs=opus',
        'audio/webm',
        'audio/mp4',
        'audio/mpeg'
    ].find(type => MediaRecorder.isTypeSupported(type)) || '';
}

async function initAudioRec() {
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) return false;

    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            }
        });
        mediaStream = stream;
        const recordingChunks = audioChunks;
        const preferredType = preferredAudioMimeType();
        const recorder = preferredType
            ? new MediaRecorder(stream, { mimeType: preferredType })
            : new MediaRecorder(stream);
        mediaRecorder = recorder;
        recorder.ondataavailable = event => {
            if (event.data?.size > 0) recordingChunks.push(event.data);
        };
        recorder.onstop = () => {
            if (audioObjectUrl) URL.revokeObjectURL(audioObjectUrl);
            audioObjectUrl = null;

            if (recordingChunks.length > 0) {
                const audioBlob = new Blob(recordingChunks, { type: recorder.mimeType || preferredType || 'audio/webm' });
                audioObjectUrl = URL.createObjectURL(audioBlob);
                const playback = document.getElementById('audioPlayback');
                playback.src = audioObjectUrl;
                playback.load();
                setPlaybackStatus('Playback ready.', '#34d399');
            } else {
                setPlaybackStatus('No playback audio was captured for this recording.', '#fbbf24');
            }

            releaseMediaStream(stream);
        };
        recorder.start(1000);
        setPlaybackStatus('Recording playback audio...');
        return true;
    } catch (error) {
        console.error('Audio recording failed:', error);
        releaseMediaStream();
        setPlaybackStatus('Playback recording is unavailable in this browser.', '#fbbf24');
        return false;
    }
}

async function startRec() {
    if (!recognition) return alert("Speech recognition is not supported in this browser.");
    if (isRec || stopInProgress) return;

    isRec = true;
    isPaused = false;
    shouldAutoRestartRecognition = true;
    recognitionActive = false;
    transcript = "";
    committedSpeechTranscript = "";
    liveSpeechInterim = "";
    lastCommittedSpeech = "";
    lastCommittedAt = 0;
    seconds = 0;
    wordCount = 0;
    fillerCount = 0;
    audioChunks = [];
    window.currentAnalysis = null;
    window.analysisTranscript = null;
    
    document.getElementById('transcriptView').innerHTML = "";
    document.getElementById('transcriptView').setAttribute('contenteditable', 'false');
    document.getElementById('editHint').style.display = 'none';
    clearAudioPlayback();

    updateUIState();
    setTranscriptionStatus('Preparing microphone', '#fbbf24');

    if (mobileSpeechSurface) {
        startSpeechRecognitionEngine();
        if (recognitionActive) {
            setTranscriptionStatus('Listening - speak now');
        }
        setPlaybackStatus('Playback recording is disabled on mobile so speech detection can use the microphone.', '#fbbf24');
    } else {
        if (shouldCaptureAudioPlayback()) {
            await initAudioRec();
        } else {
            mediaRecorder = null;
            releaseMediaStream();
            setPlaybackStatus('Playback recording is unavailable in this browser.', '#fbbf24');
        }

        if (!isRec) {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
            return;
        }
        startSpeechRecognitionEngine();
        if (recognitionActive) {
            setTranscriptionStatus('Listening - speak now');
        }
    }

    clearInterval(timer);
    timer = setInterval(updateTimer, 1000);
}

function pauseRec() {
    if (!isRec || isPaused) return;
    finalizeInterimTranscript();
    isPaused = true;
    shouldAutoRestartRecognition = false;
    if (recognition && recognitionActive) {
        try {
            recognition.stop();
        } catch (error) {
            console.error('Speech recognition failed to pause:', error);
        }
    }
    if(mediaRecorder && mediaRecorder.state === "recording") mediaRecorder.pause();
    clearInterval(timer);
    updateUIState();
}

function resumeRec() {
    if (!isRec || !isPaused) return;
    isPaused = false;
    shouldAutoRestartRecognition = true;
    startSpeechRecognitionEngine();
    if(mediaRecorder && mediaRecorder.state === "paused") mediaRecorder.resume();
    clearInterval(timer);
    timer = setInterval(updateTimer, 1000);
    updateUIState();
}

async function stopRec(shouldAnalyze = true) {
    if (stopInProgress) return false;
    stopInProgress = true;

    try {
        finalizeInterimTranscript();
        shouldAutoRestartRecognition = false;
        isRec = false;
        isPaused = false;
        const recognitionSettled = recognition ? waitForRecognitionStop() : Promise.resolve();

        if (recognition && recognitionActive) {
            try {
                recognition.stop();
            } catch (error) {
                console.error('Speech recognition failed to stop:', error);
                resolveRecognitionStopWaiter();
            }
        } else {
            resolveRecognitionStopWaiter();
        }

        if (mediaRecorder && mediaRecorder.state !== "inactive") {
            mediaRecorder.stop();
        } else {
            releaseMediaStream();
        }
        clearInterval(timer);

        await recognitionSettled;
        finalizeInterimTranscript();
        processTranscript(mergeTranscriptParts(committedSpeechTranscript, liveSpeechInterim));
        updateUIState();

        document.getElementById('transcriptView').setAttribute('contenteditable', 'true');
        document.getElementById('editHint').style.display = 'block';

        if (shouldAnalyze) return generateAnalysis();
        return true;
    } finally {
        stopInProgress = false;
    }
}

async function resetRec() {
    await stopRec(false);
    document.getElementById('timeDisp').innerText = "0:00";
    document.getElementById('wpmDisp').innerText = "0";
    document.getElementById('fillerDisp').innerText = "0";
    document.getElementById('transcriptView').innerHTML = "Your speech will appear here...";
    document.getElementById('analysisPanel').style.opacity = '0.5';
    document.getElementById('analysisPanel').style.pointerEvents = 'none';
    document.getElementById('comparisonPanel').style.display = 'none';
    startRec();
}

function updateUIState() {
    const stBtn = document.getElementById('btnStart');
    const paBtn = document.getElementById('btnPause');
    const reBtn = document.getElementById('btnResume');
    const spBtn = document.getElementById('btnStop');
    const rrBtn = document.getElementById('btnRerecord');
    
    const idle = document.getElementById('micIdle');
    const active = document.getElementById('micActive');
    const tStat = document.getElementById('transStatus');

    if (!isRec) {
        stBtn.style.display = 'block'; paBtn.style.display = 'none'; reBtn.style.display = 'none';
        spBtn.style.display = 'none'; rrBtn.style.display = 'none';
        idle.style.display = 'block'; active.style.display = 'none';
        tStat.style.display = 'none';
    } else if (isPaused) {
        stBtn.style.display = 'none'; paBtn.style.display = 'none'; reBtn.style.display = 'block';
        spBtn.style.display = 'block'; rrBtn.style.display = 'block';
        idle.style.display = 'block'; active.style.display = 'none';
        tStat.style.display = 'none';
        document.getElementById('statusText').innerText = "Paused";
    } else {
        stBtn.style.display = 'none'; paBtn.style.display = 'block'; reBtn.style.display = 'none';
        spBtn.style.display = 'block'; rrBtn.style.display = 'block';
        idle.style.display = 'none'; active.style.display = 'flex';
        tStat.style.display = 'inline-block';
    }
}

const transcriptView = document.getElementById('transcriptView');
function transcriptEditorText() {
    const text = cleanTranscriptText(transcriptView.innerText);
    return ['Listening...', 'Your speech will appear here...', 'Your answer will appear here...'].includes(text) ? '' : text;
}

transcriptView.addEventListener('input', () => {
    if (isRec) return;

    transcript = collapseRepeatedSpeech(transcriptEditorText());
    wordCount = wordsForTranscript(transcript).length;
    fillerCount = countFillersInTranscript(transcript);
    document.getElementById('fillerDisp').innerText = fillerCount;
    updateWPM();
    window.currentAnalysis = null;
    window.analysisTranscript = null;
});
transcriptView.addEventListener('blur', () => {
    if (!isRec) processTranscript(transcript);
});

function currentVoiceMetrics() {
    const wpm = parseInt(document.getElementById('wpmDisp').innerText, 10) || 0;
    let clarity = 92;
    let confidence = 85;

    if (wordCount < 5) clarity -= 35;
    else if (wordCount < 20) clarity -= 10;
    clarity -= Math.min(35, fillerCount * 4);
    if (wpm > 0 && (wpm < 90 || wpm > 180)) clarity -= 10;
    if (wpm > 0 && (wpm < 60 || wpm > 220)) clarity -= 10;

    if (wordCount < 5 || seconds < 5) confidence -= 30;
    else if (wordCount < 20) confidence -= 10;
    confidence -= Math.min(30, fillerCount * 3);
    if (wpm > 0 && (wpm < 90 || wpm > 190)) confidence -= 12;

    return {
        wpm,
        clarity: Math.max(0, Math.min(100, clarity)),
        confidence: Math.max(0, Math.min(100, confidence))
    };
}

function localAnalysisPayload(clarity, confidence, wpm) {
    const tooShort = wordCount < 20;

    return {
        ai_feedback_strengths: tooShort
            ? 'The transcript was saved, but it is too brief for reliable AI strengths feedback.'
            : 'Saved with local delivery metrics. Review the transcript for structure, examples, and measurable results.',
        ai_feedback_weaknesses: tooShort
            ? 'Add a fuller answer with a clear situation, action, and result before relying on coaching feedback.'
            : 'AI feedback was unavailable. Use the WPM, filler count, and transcript to identify the next practice focus.',
        ai_improved_answer: 'AI revision was unavailable. Build a stronger answer from your transcript by naming the situation, your task, the specific actions you took, and the result without inventing new facts.',
        clarity_score: clarity,
        confidence_score: confidence,
        speaking_pace: wpm,
        filler_words: fillerCount,
        wpm: wpm,
        duration_seconds: seconds
    };
}

async function generateAnalysis() {
    if (!isRec) {
        transcript = collapseRepeatedSpeech(transcriptEditorText());
        processTranscript(transcript);
    }
    if (!transcript) {
        alert('No speech was detected. Record or enter a transcript before analysis.');
        return false;
    }

    // Unlock analysis panel
    const panel = document.getElementById('analysisPanel');
    panel.style.opacity = '1';
    panel.style.pointerEvents = 'auto';
    
    // Calculate metrics
    const metrics = currentVoiceMetrics();
    const wpm = metrics.wpm;
    
    let paceRating = "Too Slow";
    let paceCol = "#f87171";
    let pacePct = 30;
    if (wpm >= 100 && wpm <= 150) { paceRating = "Good Pace"; paceCol = "#34d399"; pacePct = 100; }
    else if (wpm > 150) { paceRating = "Too Fast"; paceCol = "#fbbf24"; pacePct = 80; }
    
    document.getElementById('resPaceRating').innerText = paceRating;
    document.getElementById('resPaceRating').style.color = paceCol;
    document.getElementById('paceBar').style.width = pacePct + '%';
    document.getElementById('paceBar').style.background = paceCol;
    
    // Match the measurable server-side scoring rules shown after saving.
    const clarity = metrics.clarity;
    document.getElementById('resClarity').innerText = clarity;
    
    const confScore = metrics.confidence;
    const conf = confScore >= 80 ? 'High' : (confScore >= 60 ? 'Medium' : 'Low');
    document.getElementById('resConfidence').innerText = conf;
    
    // Keywords
    const keywordCandidates = ['leadership', 'communication', 'agile', 'teamwork', 'team', 'manager', 'problem', 'success'];
    const normalizedTranscript = normalizeTranscriptForMatch(transcript);
    const keywords = keywordCandidates.filter(keyword => new RegExp(`\\b${keyword}\\b`, 'i').test(normalizedTranscript));
    document.getElementById('resKeywords').innerHTML = keywords.length
        ? keywords.map(keyword => `<span class="badge" style="background:rgba(52,211,153,0.15);color:#34d399;font-weight:600;">${keyword}</span>`).join('')
        : '<span style="color:var(--tx3);font-size:0.85rem;">No tracked keywords detected.</span>';
    
    // Set loading state for AI Feedback
    document.getElementById('resStrengths').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Analyzing...';
    document.getElementById('resWeak').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Analyzing...';
    document.getElementById('comparisonPanel').style.display = 'block';
    document.getElementById('compUser').textContent = transcript;
    document.getElementById('compAI').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Building a grounded revision template...';

    // Fetch AI Analysis
    try {
        const promptText = document.getElementById('promptText').innerText.replace(/"/g, '');
        const transText = transcript;
        
        const response = await fetch("{{ route('user.drills.voice.analyze') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ prompt: promptText, transcript: transText })
        });
        if (!response.ok) throw new Error(`Analysis failed with status ${response.status}`);

        const data = await response.json();
        
        // Populate AI Feedback
        document.getElementById('resStrengths').innerText = data.strengths || "AI strengths analysis was unavailable for this recording.";
        document.getElementById('resWeak').innerText = data.weaknesses || "AI improvement analysis was unavailable. Review the transcript, pace, and filler-word count before relying on this session.";
        document.getElementById('compAI').textContent = data.improved_answer || "A grounded revision template was unavailable for this recording.";
        
        // Store for saving
        window.currentAnalysis = {
            ai_feedback_strengths: data.strengths,
            ai_feedback_weaknesses: data.weaknesses,
            ai_improved_answer: data.improved_answer,
            clarity_score: clarity,
            confidence_score: confScore,
            speaking_pace: wpm,
            filler_words: fillerCount,
            wpm: wpm,
            duration_seconds: seconds
        };
        window.analysisTranscript = transcript;
        return true;

    } catch (error) {
        console.error("Analysis Error:", error);
        const fallback = localAnalysisPayload(clarity, confScore, wpm);
        document.getElementById('resStrengths').innerText = fallback.ai_feedback_strengths;
        document.getElementById('resWeak').innerText = fallback.ai_feedback_weaknesses;
        document.getElementById('compAI').innerText = fallback.ai_improved_answer;
        window.currentAnalysis = fallback;
        window.analysisTranscript = transcript;
        return true;
    }
}

async function saveSession() {
    transcript = collapseRepeatedSpeech(transcriptEditorText());
    processTranscript(transcript);

    if (!transcript) {
        alert('No transcript is available to save.');
        return;
    }

    if (!window.currentAnalysis || window.analysisTranscript !== transcript) {
        const analyzed = await generateAnalysis();
        if (!analyzed) return;
    }

    const btn = document.getElementById('btnSave');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Saving...';
    btn.disabled = true;

    try {
        const payload = {
            ...window.currentAnalysis,
            category: document.getElementById('categorySelect').value,
            transcript: transcript,
            prompt: document.getElementById('promptText').innerText.replace(/"/g, '')
        };

        const response = await fetch("{{ route('user.drills.voice.save') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.message || `Save failed with status ${response.status}`);
        
        if (data.success) {
            alert("Session saved successfully to your History!");
            
            // Append to history table
            const emptyHistory = document.querySelector('#historyTable td[colspan="5"]');
            if (emptyHistory) emptyHistory.closest('tr')?.remove();

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="color:var(--tx2);font-size:0.9rem;">${data.session.date}</td>
                <td><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;">${data.session.category}</span></td>
                <td style="color:#34d399;font-weight:600;">${data.session.clarity}</td>
                <td>${data.session.wpm}</td>
                <td style="color:#f87171;">${data.session.fillers}</td>
            `;
            document.getElementById('historyTable').prepend(tr);
            
            // Optionally, update charts dynamically here if desired
        } else {
            alert("Failed to save session.");
        }
    } catch (error) {
        console.error("Save Error:", error);
        alert(error.message || "An error occurred while saving.");
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

function downloadReport() {
    window.print();
}

// Chart.js & History Init
function loadHistory() {
    const histData = {!! json_encode($history->map(function($session) {
        return [
            'd' => $session->created_at->format('M d'),
            'c' => $session->practice_scenario ?? 'General Job Interview',
            'cl' => ($session->clarity_score ?? 0) . '%',
            'w' => $session->wpm ?? 0,
            'f' => $session->filler_words ?? 0,
            'score' => $session->clarity_score ?? 0
        ];
    })) !!};

    let html = '';
    if (histData.length === 0) {
        html = '<tr><td colspan="5" class="text-center text-muted">No history found. Practice a Philippines voice session to see it here.</td></tr>';
    } else {
        histData.forEach(h => {
            html += `<tr>
                <td style="color:var(--tx2);font-size:0.9rem;">${h.d}</td>
                <td><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;">${h.c}</span></td>
                <td style="color:#34d399;font-weight:600;">${h.cl}</td>
                <td>${h.w}</td>
                <td style="color:#f87171;">${h.f}</td>
            </tr>`;
        });
    }
    document.getElementById('historyTable').innerHTML = html;
    
    return histData;
}

document.addEventListener("DOMContentLoaded", function() {
    loadHistory();
    randomizePrompt();
    
    if(typeof Chart !== 'undefined') {
        Chart.defaults.color = '#808090';
        Chart.defaults.font.family = "'Inter', sans-serif";
        
        const voiceProgressCanvas = document.getElementById('voiceProgressChart');
        if (!voiceProgressCanvas) return;

        const ctx = voiceProgressCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Session 1', 'Session 2', 'Session 3', 'Session 4', 'Session 5'],
                datasets: [{
                    label: 'Clarity Score',
                    data: [70, 75, 82, 85, 92],
                    borderColor: '#34d399',
                    backgroundColor: 'rgba(52,211,153,0.1)',
                    borderWidth: 3, tension: 0.4, fill: true
                }, {
                    label: 'Fillers Used',
                    data: [15, 12, 8, 5, 2],
                    borderColor: '#f87171',
                    backgroundColor: 'transparent',
                    borderWidth: 2, tension: 0.4, fill: false
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#categorySelect', popover: { title: 'Prompt Scenario', description: 'Choose the Philippines interview scenario for this voice rehearsal.', side: 'bottom', align: 'start' }},
            { element: '#btnStart', popover: { title: 'Start Recording', description: 'Record your answer and track pacing, filler words, and transcript quality.', side: 'bottom', align: 'center' }},
            { element: '#transcriptView', popover: { title: 'Live Transcript', description: 'Your speech appears here while filler words and keywords are highlighted.', side: 'top', align: 'start' }},
            { element: '#analysisPanel', popover: { title: 'Practice Assessment', description: 'After recording, review pacing, clarity, and next practice actions.', side: 'top', align: 'start' }},
            { element: '#moduleTabs', popover: { title: 'History & Analytics', description: 'Use the other tabs to review past sessions, charts, and long-term progress.', side: 'bottom', align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#categorySelect', popover: { title: 'Prompt Scenario', description: 'Choose the Philippines interview scenario for this voice rehearsal.', side: 'bottom', align: 'start' }},
            { element: '#btnStart', popover: { title: 'Start Recording', description: 'Record your answer and track pacing, filler words, and transcript quality.', side: 'bottom', align: 'center' }},
            { element: '#transcriptView', popover: { title: 'Live Transcript', description: 'Your speech appears here while filler words and keywords are highlighted.', side: 'top', align: 'start' }},
            { element: '#analysisPanel', popover: { title: 'Practice Assessment', description: 'After recording, review pacing, clarity, and next practice actions.', side: 'bottom', align: 'start' }},
            { element: '#moduleTabs', popover: { title: 'History & Analytics', description: 'Use the other tabs to review past sessions, charts, and long-term progress.', side: 'bottom', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_drills_voice',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
            startDelay: 300,
            beforeStart: () => {
                const practiceTab = document.querySelector('a[data-target="tab-practice"]');
                if (practiceTab) practiceTab.click();
            },
        });
    });
</script>
@endpush
@endsection


