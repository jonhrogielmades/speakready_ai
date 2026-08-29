@extends('desktop.layouts.app')
@section('title', 'Philippines Voice Rehearsal')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/drills/voice.css?v=1') }}" data-page-style="user-drills-voice">
<link rel="stylesheet" href="{{ asset('css/desktop/user/drills/voice-2.css?v=6') }}" data-page-style="user-drills-voice-2">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')

<div class="db-section active" id="voice-rehearsal-page">
    <div class="vr-shell">
    <div class="sr-page-hero vr-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="vr-hero-icon"><i class="fa-solid fa-microphone-lines"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a3 3 0 0 0-3 3v5a3 3 0 0 0 6 0V7a3 3 0 0 0-3-3Z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3M8 21h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Philippines Voice Rehearsal
                    </h4>
                    <p class="sr-page-hero-subtitle">Practice delivery, pacing, and answer clarity for Philippines interview scenarios.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="voicePanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="voiceBlue" x1="70" y1="36" x2="154" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#voicePanel)" stroke="#BFDBFE" stroke-width="3"/><rect x="91" y="40" width="38" height="58" rx="19" fill="url(#voiceBlue)"/><path d="M75 78a35 35 0 0 0 70 0M110 113v14M92 127h36" fill="none" stroke="#2563EB" stroke-width="7" stroke-linecap="round"/><path d="M55 72v20M68 60v44M152 60v44M165 72v20" stroke="#38BDF8" stroke-width="7" stroke-linecap="round" opacity=".75"/><circle cx="160" cy="44" r="17" fill="#22C55E"/><path d="M153 44l5 5 10-12" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="sr-page-actions vr-tabs-wrap">
        <ul class="nav nav-pills vr-tabs" id="moduleTabs" style="margin-bottom:0;">
            <li class="nav-item"><a class="nav-link active" href="#" data-target="tab-practice"><i class="fa-solid fa-crosshairs"></i> Practice</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-target="tab-analytics"><i class="fa-solid fa-chart-simple"></i> History & Analytics</a></li>
        </ul>
    </div>

    <!-- TAB: PRACTICE -->
    <div id="tab-practice" class="tab-pane active">
        <div class="vr-practice-flow animate-fade-up delay-100">
            <div class="vr-practice-main-col">
                <section class="vr-option-panel" aria-label="Voice rehearsal setup">
                    <div class="vr-option-list">
                        <button id="voiceCategoryButton" type="button" class="vr-option-row" onclick="cycleVoiceCategory()">
                            <span class="vr-option-icon"><i class="fa-solid fa-briefcase"></i></span>
                            <span id="categoryDisplay">Job Interviews</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                        <button id="voiceIntentionButton" type="button" class="vr-option-row" onclick="cycleVoiceIntention()">
                            <span class="vr-option-icon"><i id="intentionDisplayIcon" class="fa-solid fa-check"></i></span>
                            <span id="intentionDisplay">Confident</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                        <button id="btnRandomizePrompt" type="button" class="vr-option-row" onclick="randomizePrompt()">
                            <span class="vr-option-icon"><i class="fa-solid fa-shuffle"></i></span>
                            <span>Randomize</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 vr-hidden-selects" aria-hidden="true" hidden>
                        <div class="d-flex flex-wrap gap-2">
                            <select id="categorySelect" class="form-select w-auto" style="background:var(--bg3);color:var(--tx);border-color:var(--bd);border-radius:10px;" tabindex="-1">
                                <option value="Tell Me About Yourself">Job Interviews</option>
                                <option value="School Admission">School Admission Interviews</option>
                            </select>
                            <select id="intentionSelect" class="form-select w-auto" style="background:var(--bg3);color:var(--tx);border-color:var(--bd);border-radius:10px;" tabindex="-1">
                                <option value="Confident">Confident</option>
                                <option value="Friendly">Friendly</option>
                                <option value="Calm">Calm</option>
                                <option value="Persuasive">Persuasive</option>
                                <option value="Accountable">Accountable</option>
                            </select>
                        </div>
                    </div>
                </section>

                    <div class="vr-prompt-card">
                        <div class="vr-prompt-kicker">PHILIPPINES PROMPT</div>
                        <h3 id="promptText" class="vr-prompt-text">"Walk me through your background and connect it to the role you are preparing for."</h3>
                        <div id="voiceStatusNotice" class="vr-notice" role="status" aria-live="polite" hidden></div>

                    <!-- Mic Visualization -->
                    <div class="vr-mic-stage">
                        <!-- Idle State -->
                        <div id="micIdle" class="text-center">
                            <div id="statusText" style="color:var(--tx3);font-size:0.9rem;">Ready to record</div>
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
                    <p class="vr-start-note">Tap Start and choose Allow microphone when prompted.</p>
                    <div id="micPermissionHelp" class="text-center mb-4" style="display:none;color:var(--tx3);font-size:0.82rem;line-height:1.5;"></div>
                    </div>

                    <!-- Live Stats -->
                    <div class="row g-2 mb-4 voice-live-stats">
                        <div class="col-3">
                            <div class="stat-box">
                                <span class="stat-ico"><i class="fa-solid fa-clock"></i></span>
                                <div class="stat-val" id="timeDisp">0:00</div>
                                <div class="stat-lbl">Duration</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="stat-box">
                                <span class="stat-ico"><i class="fa-solid fa-gauge-high"></i></span>
                                <div class="stat-val" id="wpmDisp">0</div>
                                <div class="stat-lbl">WPM</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="stat-box">
                                <span class="stat-ico warn"><i class="fa-solid fa-chart-line"></i></span>
                                <div class="stat-val" id="stabilityDisp" style="color:#f59e0b;">0%</div>
                                <div class="stat-lbl">Stability</div>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="stat-box">
                                <span class="stat-ico danger"><i class="fa-solid fa-comment-slash"></i></span>
                                <div class="stat-val" id="fillerDisp" style="color:#f87171;">0</div>
                                <div class="stat-lbl">Fillers</div>
                            </div>
                        </div>
                    </div>

                    <!-- Transcript Box -->
                    <div class="vr-transcript-wrap">
                        <div class="d-flex justify-content-between mb-2">
                            <label class="vr-transcript-label"><i class="fa-solid fa-comment-dots"></i> Live Answer Transcript</label>
                            <span id="transStatus" style="font-size:0.8rem;color:#34d399;display:none;"><i class="fa-solid fa-circle-dot fa-fade me-1"></i> Transcribing</span>
                        </div>
                        <div id="transcriptView" style="background:var(--bg3);border:1px solid var(--bd);border-radius:12px;padding:16px;min-height:120px;color:var(--tx);font-size:1.05rem;line-height:1.6;white-space:pre-wrap;" contenteditable="false">Your answer will appear here...</div>
                        <p class="mt-2 mb-0" style="font-size:0.8rem;color:var(--tx3);display:none;" id="editHint"><i class="fa-solid fa-pencil me-1"></i> You can edit the transcript above manually before saving.</p>
                    </div>

                    <!-- Post-Analysis Dashboard -->
                    <div id="analysisPanel" style="opacity:0.5;pointer-events:none;transition:opacity 0.4s;">
                        <div class="premium-card vr-assessment-card mb-4" style="background: linear-gradient(180deg, var(--sf) 0%, rgba(59,130,246,0.05) 100%);">
                            <h6 class="vr-assessment-title"><i class="fa-solid fa-chart-pie me-2" style="color:#60a5fa;"></i> Practice Assessment</h6>

                            <!-- Clarity & Delivery Stability -->
                            <div class="d-flex justify-content-between align-items-center mb-4 vr-assessment-scores">
                                <div class="text-center w-50 vr-score-row">
                                    <div class="vr-score-ring" id="resClarity">--%</div>
                                    <div class="vr-score-label">Clarity Score</div>
                                </div>
                                <div class="vr-assessment-divider" style="width:1px;height:40px;background:var(--bd);"></div>
                                <div class="text-center w-50 vr-score-row">
                                    <div class="vr-score-ring blue" id="resConfidence">--</div>
                                    <div class="vr-score-label">Delivery Stability</div>
                                </div>
                            </div>

                            <!-- Pace Rating -->
                            <div class="mb-3 p-3 vr-pace-card" style="background:var(--bg3);border-radius:10px;border:1px solid var(--bd);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span style="font-size:0.78rem;color:var(--tx2);"><i class="fa-solid fa-gauge-high me-1"></i> Speaking Pace</span>
                                    <span style="font-weight:700;" id="resPaceRating">--</span>
                                </div>
                                <div class="progress-track" style="height:6px;">
                                    <div id="paceBar" class="progress-fill" style="width:0%;background:#3b82f6;"></div>
                                </div>
                                <div style="font-size:0.75rem;color:var(--tx3);margin-top:6px;text-align:right;" id="resPaceDesc">Optimal: 100-150 WPM</div>
                            </div>

                            <!-- Pronunciation & Keywords -->
                            <div class="mb-3 vr-keywords-card">
                                <h6 class="vr-assessment-kicker"><i class="fa-solid fa-tags"></i> Detected Keywords</h6>
                                <div id="resKeywords" class="d-flex flex-wrap gap-2">
                                    <span style="color:var(--tx3);font-size:0.85rem;">Waiting for analysis...</span>
                                </div>
                            </div>

                            <div class="mb-3 vr-ai-wrap">
                                <h6 class="vr-assessment-kicker"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Feedback</h6>
                                <div class="p-2 mb-2 vr-ai-card good" style="background:rgba(52,211,153,0.05);border:1px solid rgba(52,211,153,0.2);border-radius:8px;">
                                    <strong style="color:#34d399;font-size:0.8rem;"><i class="fa-solid fa-check"></i> Strengths</strong>
                                    <div id="resStrengths" style="font-size:0.85rem;color:var(--tx2);margin-top:4px;">--</div>
                                </div>
                                <div class="p-2 vr-ai-card warn" style="background:rgba(248,113,113,0.05);border:1px solid rgba(248,113,113,0.2);border-radius:8px;">
                                    <strong style="color:#f87171;font-size:0.8rem;"><i class="fa-solid fa-arrow-trend-up"></i> Needs Work</strong>
                                    <div id="resWeak" style="font-size:0.85rem;color:var(--tx2);margin-top:4px;">--</div>
                                </div>
                            </div>

                            <button id="btnSave" class="btn w-100 btn-shine" style="background:#34d399;color:#fff;font-weight:700;border-radius:10px;border:none;min-height:42px;box-shadow:0 4px 15px rgba(52,211,153,0.32);" onclick="saveSession()" disabled aria-disabled="true" title="Record or analyze an answer first"><i class="fa-solid fa-cloud-arrow-up me-2"></i> Save Session</button>
                        </div>
                    </div>
            </div>

            <aside class="vr-practice-side-col">
                <div class="vr-feedback-row">
                    <section class="instant-feedback-panel mb-4" aria-label="Instant speaking feedback">
                        <div class="instant-feedback-head">
                            <div>
                                <h6 class="instant-feedback-title"><i class="fa-solid fa-bolt me-2" style="color:#0ea5e9;"></i>Instant Speaking Feedback</h6>
                                <div class="instant-feedback-summary" id="instantFeedbackSummary">Ready for your first speaking signal.</div>
                            </div>
                            <span class="instant-feedback-status" id="instantFeedbackStatus">Ready</span>
                        </div>
                        <div class="instant-feedback-grid">
                            <div class="instant-feedback-signal" id="instantPaceSignal">
                                <span class="instant-signal-ico"><i class="fa-solid fa-gauge-high"></i></span>
                                <div class="instant-feedback-kicker">Pace</div>
                                <div class="instant-feedback-value" id="instantPaceValue">0 WPM</div>
                                <span class="instant-feedback-badge" id="instantPaceBadge">Waiting</span>
                                <div class="instant-feedback-note" id="instantPaceNote">Keep a natural rhythm once recording starts.</div>
                            </div>
                            <div class="instant-feedback-signal" id="instantFillerSignal">
                                <span class="instant-signal-ico filler"><i class="fa-solid fa-comment-slash"></i></span>
                                <div class="instant-feedback-kicker">Fillers</div>
                                <div class="instant-feedback-value" id="instantFillerValue">0 found</div>
                                <span class="instant-feedback-badge" id="instantFillerBadge">Waiting</span>
                                <div class="instant-feedback-note" id="instantFillerNote">Silent pauses are counted better than filler words.</div>
                            </div>
                            <div class="instant-feedback-signal" id="instantDepthSignal">
                                <span class="instant-signal-ico depth"><i class="fa-solid fa-file-lines"></i></span>
                                <div class="instant-feedback-kicker">Answer Depth</div>
                                <div class="instant-feedback-value" id="instantDepthValue">0 words</div>
                                <span class="instant-feedback-badge" id="instantDepthBadge">Waiting</span>
                                <div class="instant-feedback-note" id="instantDepthNote">Build toward a complete example with a result.</div>
                            </div>
                        </div>
                        <ul class="instant-feedback-actions" id="instantFeedbackActions">
                            <li><i class="fa-solid fa-circle-info"></i><span>Waiting for transcript evidence.</span></li>
                        </ul>
                    </section>

                    <section class="intention-coach-panel mb-4" aria-label="Emotion and intention coach">
                        <div class="intention-coach-head">
                            <div>
                                <h6 class="intention-coach-title"><i class="fa-solid fa-face-smile-beam me-2" style="color:#14b8a6;"></i>Emotion & Intention Coach</h6>
                                <div class="intention-coach-summary" id="intentionCoachSummary">Choose a target intention, then speak or edit your answer.</div>
                            </div>
                            <span class="intention-coach-status" id="intentionCoachStatus">Ready</span>
                        </div>
                        <div class="intention-coach-grid">
                            <div class="intention-coach-metric">
                                <span class="intention-metric-ico"><i class="fa-solid fa-bullseye"></i></span>
                                <div class="intention-coach-kicker">Target</div>
                                <div class="intention-coach-value" id="intentionTargetValue">Confident</div>
                            </div>
                            <div class="intention-coach-metric">
                                <span class="intention-metric-ico detected"><i class="fa-solid fa-ear-listen"></i></span>
                                <div class="intention-coach-kicker">Detected</div>
                                <div class="intention-coach-value" id="intentionDetectedValue">Waiting</div>
                            </div>
                            <div class="intention-coach-metric">
                                <span class="intention-metric-ico match"><i class="fa-solid fa-chart-simple"></i></span>
                                <div class="intention-coach-kicker">Match</div>
                                <div class="intention-coach-value" id="intentionMatchValue">--%</div>
                                <div class="intention-meter"><span id="intentionMatchMeter" style="--intent-score:0%;"></span></div>
                            </div>
                        </div>
                        <ul class="intention-tip-list" id="intentionCoachTips">
                            <li><i class="fa-solid fa-circle-info"></i><span>Waiting for enough words to compare your wording with the target intention.</span></li>
                        </ul>
                    </section>
                </div>
            </aside>
        
        <!-- Answer draft comparison -->
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
    </div>

    <!-- TAB: HISTORY & ANALYTICS -->
    <div id="tab-analytics" class="tab-pane">
        <div class="vr-progress-layout animate-fade-up delay-100">
            <div>
                <!-- Progress Charts -->
                <section class="vr-progress-panel mb-4">
                    <h5 class="vr-progress-title"><i class="fa-solid fa-chart-line"></i> Progress Analytics</h5>
                    <div class="vr-chart-frame">
                        <canvas id="voiceProgressChart"></canvas>
                    </div>
                    <div id="voiceProgressEmpty" class="text-center mt-2" style="display:none;color:var(--tx3);font-size:0.78rem;">No saved rehearsal data yet. Start a practice to see your progress here.</div>
                </section>

                <!-- Rehearsal History -->
                <section class="vr-progress-panel">
                    <div class="voice-history-head">
                        <h5 class="vr-progress-title"><i class="fa-solid fa-clock-rotate-left"></i> Rehearsal History</h5>
                        <div class="voice-history-actions">
                            <button class="btn btn-sm btn-outline-primary" type="button" style="border-radius:8px;" onclick="downloadReport()"><i class="fa-solid fa-print me-1"></i> Print / Save PDF</button>
                            @if($history->count() > 0)
                                <form action="{{ route('user.drills.voice.clear') }}" method="POST" data-sr-confirm-form data-sr-confirm-title="Clear all voice rehearsal sessions?" data-sr-confirm-message="This will permanently remove your saved voice rehearsal history. This cannot be undone." data-sr-confirm-action="Clear All" data-sr-confirm-variant="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:8px;">
                                        <i class="fa-solid fa-trash-can me-1"></i> Clear All
                                    </button>
                                </form>
                            @endif
                        </div>
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
                </section>
            </div>

            <aside>
                <!-- AI Practice Suggestions -->
                <section class="vr-progress-panel">
                    <h6 class="vr-progress-title mb-4"><i class="fa-solid fa-lightbulb"></i> Recommended Voice Practice</h6>
                    
                    <div class="vr-suggestion-card">
                        <div class="vr-suggestion-content">
                            <span class="vr-suggestion-icon"><i class="fa-solid fa-gauge-high"></i></span>
                            <div>
                                <div class="vr-suggestion-title">Pace Yourself</div>
                                <div class="vr-suggestion-text">Your average pace is 165 WPM. Try taking slight pauses between sentences.</div>
                            </div>
                        </div>
                    </div>
                    <div class="vr-suggestion-card">
                        <div class="vr-suggestion-content">
                            <span class="vr-suggestion-icon"><i class="fa-solid fa-comment-slash"></i></span>
                            <div>
                                <div class="vr-suggestion-title">Reduce Fillers</div>
                                <div class="vr-suggestion-text">You used "Um" 12 times last session. Try silent pauses instead of filler words.</div>
                            </div>
                        </div>
                    </div>
                    <div class="vr-suggestion-card">
                        <div class="vr-suggestion-content">
                            <span class="vr-suggestion-icon"><i class="fa-solid fa-briefcase"></i></span>
                            <div>
                                <div class="vr-suggestion-title">Practice School Admission</div>
                                <div class="vr-suggestion-text">Practice a school admission answer that connects your goals, program fit, and readiness.</div>
                            </div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
</div>

<script>
// Tab Switching
document.querySelectorAll('#moduleTabs .nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        const activeLink = e.target.closest('.nav-link');
        if (!activeLink || !activeLink.dataset.target) return;
        document.querySelectorAll('#moduleTabs .nav-link').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('#voice-rehearsal-page > .vr-shell > .tab-pane').forEach(p => p.classList.remove('active'));
        activeLink.classList.add('active');
        document.getElementById(activeLink.getAttribute('data-target')).classList.add('active');
    });
});

// AI-backed practice prompt bank with a local fallback for provider outages.
const fallbackPrompts = {
    "Tell Me About Yourself": ["Walk me through your background and connect it to the Philippines role you are preparing for.", "What should a Philippine job interviewer remember about you after your first two minutes?", "How would you summarize your strengths, experience, and next career goal in the Philippine context?"],
    "School Admission": ["Why does this school or program fit your academic and career plan?", "Tell me about a challenge that shaped your readiness for school and how you responded.", "Describe how you will contribute to your school, community, or the Philippines if admitted."]
};

const voiceMissionPreset = {
    mission: @json(request('mission')),
    category: @json(request('category')),
    prompt: @json(request('prompt')),
    intent: @json(request('intent'))
};
let promptRequestSequence = 0;
let voiceNoticeTimer = null;

function setVoiceNotice(message, variant = 'info', persist = false) {
    const notice = document.getElementById('voiceStatusNotice');
    if (!notice) return;

    window.clearTimeout(voiceNoticeTimer);
    const cleanMessage = cleanTranscriptText(message);
    notice.hidden = !cleanMessage;
    notice.textContent = cleanMessage;
    notice.className = `vr-notice vr-notice-${variant}`;

    if (cleanMessage && !persist) {
        voiceNoticeTimer = window.setTimeout(() => setVoiceNotice(''), 4500);
    }
}

function setVoiceSaveReady(isReady) {
    const btn = document.getElementById('btnSave');
    if (!btn) return;

    btn.disabled = !isReady;
    btn.setAttribute('aria-disabled', isReady ? 'false' : 'true');
    btn.title = isReady ? 'Save analyzed rehearsal' : 'Record or analyze an answer first';
}

function setAnalysisPanelActive(isActive) {
    const panel = document.getElementById('analysisPanel');
    if (!panel) return;

    panel.style.opacity = isActive ? '1' : '0.5';
    panel.style.pointerEvents = isActive ? 'auto' : 'none';
    if (!isActive) setVoiceSaveReady(false);
}

function voiceScenarioLabel(category) {
    const labels = {
        "Tell Me About Yourself": "Job Interviews",
        "School Admission": "School Admission Interviews"
    };

    return labels[category] || 'Job Interviews';
}

function categoryValueForPreset(category) {
    const labels = {
        "Job Interviews": "Tell Me About Yourself",
        "General Job Interview": "Tell Me About Yourself",
        "School Admission Interviews": "School Admission",
        "Scholarship / Admission": "School Admission"
    };

    return labels[category] || category || "Tell Me About Yourself";
}

function syncVoiceOptionLabels() {
    const categorySelect = document.getElementById('categorySelect');
    const intentionSelect = document.getElementById('intentionSelect');
    const categoryDisplay = document.getElementById('categoryDisplay');
    const intentionDisplay = document.getElementById('intentionDisplay');
    const intentionDisplayIcon = document.getElementById('intentionDisplayIcon');
    const intentionIcons = {
        Confident: 'fa-check',
        Friendly: 'fa-face-smile',
        Calm: 'fa-leaf',
        Persuasive: 'fa-bullhorn',
        Accountable: 'fa-clipboard-check'
    };

    if (categorySelect && categoryDisplay) {
        categoryDisplay.innerText = voiceScenarioLabel(categorySelect.value);
    }
    if (intentionSelect && intentionDisplay) {
        intentionDisplay.innerText = intentionSelect.value || 'Confident';
        if (intentionDisplayIcon) {
            intentionDisplayIcon.className = `fa-solid ${intentionIcons[intentionSelect.value] || 'fa-check'}`;
        }
    }
}

function cycleSelectOption(selectId) {
    const select = document.getElementById(selectId);
    if (!select || select.options.length === 0) return;
    select.selectedIndex = (select.selectedIndex + 1) % select.options.length;
    select.dispatchEvent(new Event('change', { bubbles: true }));
}

function cycleVoiceCategory() {
    cycleSelectOption('categorySelect');
}

function cycleVoiceIntention() {
    cycleSelectOption('intentionSelect');
}

function localFallbackPrompt(category) {
    const list = fallbackPrompts[category] || fallbackPrompts["Tell Me About Yourself"];
    return list[Math.floor(Math.random() * list.length)];
}

async function randomizePrompt(options = {}) {
    const cat = document.getElementById('categorySelect').value;
    const promptEl = document.getElementById('promptText');
    const btn = document.getElementById('btnRandomizePrompt');
    const originalBtn = btn ? btn.innerHTML : '';
    const requestId = ++promptRequestSequence;
    const fallbackPrompt = localFallbackPrompt(cat);
    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), 5000);

    promptEl.innerText = `"${fallbackPrompt}"`;

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="vr-option-icon"><i class="fa-solid fa-spinner fa-spin"></i></span><span>Refreshing</span><i class="fa-solid fa-chevron-right"></i>';
    }

    try {
        const response = await fetch("{{ route('user.drills.voice.prompt') }}", {
            method: 'POST',
            signal: controller.signal,
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

        if (requestId === promptRequestSequence) {
            promptEl.innerText = `"${prompt}"`;
            if (!options.silent) setVoiceNotice('Prompt refreshed.', 'success');
        }
    } catch (error) {
        console.error('AI prompt generation failed:', error);
        if (requestId === promptRequestSequence && !options.silent) {
            setVoiceNotice('Using a local prompt because the AI prompt refresh is unavailable.', 'warning');
        }
    } finally {
        window.clearTimeout(timeoutId);
        if (btn && requestId === promptRequestSequence) {
            btn.disabled = false;
            btn.innerHTML = originalBtn || '<span class="vr-option-icon"><i class="fa-solid fa-shuffle"></i></span><span>Randomize</span><i class="fa-solid fa-chevron-right"></i>';
        }
    }
}
document.getElementById('categorySelect').addEventListener('change', () => {
    syncVoiceOptionLabels();
    randomizePrompt({ silent: true });
});
document.getElementById('intentionSelect').addEventListener('change', () => {
    syncVoiceOptionLabels();
    updateIntentionCoach();
});

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
const BrowserSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const speechLocale = document.documentElement.dataset.speechLocale || navigator.language || 'en-US';
const serverDetectedMobile = false;
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

function setMicrophoneHelp(message, color = 'var(--tx3)') {
    const help = document.getElementById('micPermissionHelp');
    if (!help) return;
    help.textContent = message || '';
    help.style.color = color;
    help.style.display = message ? 'block' : 'none';
}

function setStartButtonEnabled(enabled) {
    const btn = document.getElementById('btnStart');
    if (!btn) return;
    btn.disabled = !enabled;
    btn.style.opacity = enabled ? '1' : '0.65';
    btn.style.cursor = enabled ? 'pointer' : 'not-allowed';
}

function microphoneRequiresSecureOrigin() {
    return !(window.isSecureContext
        || ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname));
}

function stopProbeStream(stream) {
    if (stream) stream.getTracks().forEach(track => track.stop());
}

function mobilePermissionGuidance() {
    return mobileSpeechSurface
        ? 'On mobile, allow Microphone for this site in the browser permission prompt or site settings, then tap Start again.'
        : 'Allow Microphone for this site in your browser permission prompt or site settings, then start again.';
}

function microphoneErrorMessage(error) {
    const name = error?.name || error || 'unknown';
    if (name === 'NotAllowedError' || name === 'SecurityError' || name === 'not-allowed' || name === 'service-not-allowed') {
        return `Microphone permission is blocked. ${mobilePermissionGuidance()}`;
    }
    if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
        return 'No microphone was detected on this device.';
    }
    if (name === 'NotReadableError' || name === 'TrackStartError' || name === 'audio-capture') {
        return 'The microphone is unavailable or already being used by another app.';
    }
    return `Microphone could not start. ${mobilePermissionGuidance()}`;
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

async function queryMicrophonePermissionState() {
    if (!navigator.permissions?.query) return null;

    try {
        const status = await navigator.permissions.query({ name: 'microphone' });
        status.onchange = () => refreshMicrophoneAvailabilityUi();
        return status.state;
    } catch (error) {
        return null;
    }
}

async function requestMicrophoneProbe() {
    if (!navigator.mediaDevices?.getUserMedia) return true;

    let stream = null;
    try {
        stream = await navigator.mediaDevices.getUserMedia(audioCaptureConstraints());
        return true;
    } catch (error) {
        if (error?.name === 'OverconstrainedError' || error?.name === 'ConstraintNotSatisfiedError') {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            return true;
        }
        throw error;
    } finally {
        stopProbeStream(stream);
    }
}

async function ensureMicrophonePermission() {
    if (!recognition) {
        const message = 'Live microphone transcription is not supported in this browser.';
        setMicrophoneHelp(message, '#fbbf24');
        setTranscriptionStatus(message, '#fbbf24');
        return false;
    }

    if (microphoneRequiresSecureOrigin()) {
        const message = 'Microphone access requires HTTPS on mobile. Open the secure site URL, then tap Start.';
        setMicrophoneHelp(message, '#f87171');
        setTranscriptionStatus('Secure connection required', '#f87171');
        return false;
    }

    const permissionState = await queryMicrophonePermissionState();
    if (permissionState === 'denied') {
        const message = `Microphone permission is blocked. ${mobilePermissionGuidance()}`;
        setMicrophoneHelp(message, '#f87171');
        setTranscriptionStatus('Microphone permission denied', '#f87171');
        return false;
    }

    setMicrophoneHelp(mobileSpeechSurface ? 'When prompted, choose Allow microphone.' : '');
    setTranscriptionStatus('Requesting microphone permission', '#fbbf24');

    try {
        await requestMicrophoneProbe();
        setMicrophoneHelp('');
        return true;
    } catch (error) {
        const message = microphoneErrorMessage(error);
        console.error('Microphone permission check failed:', error);
        setMicrophoneHelp(message, '#f87171');
        setTranscriptionStatus(message, '#f87171');
        return false;
    }
}

async function refreshMicrophoneAvailabilityUi() {
    if (!recognition) {
        setStartButtonEnabled(false);
        setMicrophoneHelp('Live microphone transcription is not supported in this browser.', '#fbbf24');
        return;
    }

    if (microphoneRequiresSecureOrigin()) {
        setStartButtonEnabled(false);
        setMicrophoneHelp('Microphone access requires HTTPS on mobile. Open the secure site URL to use Voice Rehearsal.', '#f87171');
        return;
    }

    const permissionState = await queryMicrophonePermissionState();
    if (permissionState === 'denied') {
        setStartButtonEnabled(true);
        setMicrophoneHelp(`Microphone permission is blocked. ${mobilePermissionGuidance()}`, '#f87171');
        return;
    }

    setStartButtonEnabled(true);
    setMicrophoneHelp(mobileSpeechSurface ? 'Tap Start and choose Allow microphone when prompted.' : '');
}

function restoreStartAvailabilityAfterFailedPermission() {
    setStartButtonEnabled(Boolean(recognition) && !microphoneRequiresSecureOrigin());
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
    if (!recognition) {
        setTranscriptionStatus('Live microphone transcription is not supported in this browser.', '#fbbf24');
        return false;
    }
    if (recognitionActive || !isRec || isPaused || !shouldAutoRestartRecognition) return false;

    try {
        recognition.start();
        recognitionActive = true;
        setTranscriptionStatus('Listening - speak now');
        return true;
    } catch (error) {
        if (!error || error.name !== 'InvalidStateError') {
            console.error('Speech recognition failed to start:', error);
            const message = microphoneErrorMessage(error);
            setMicrophoneHelp(message, '#f87171');
            setTranscriptionStatus(message, '#f87171');
        }
        return false;
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
            const message = microphoneErrorMessage(error);
            setTimeout(async () => {
                await stopRec(false);
                setTranscriptionStatus(message, '#f87171');
                setMicrophoneHelp(message, '#f87171');
                restoreStartAvailabilityAfterFailedPermission();
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
    updateLiveStability();
    updateInstantFeedback();
}

function updateLiveStability() {
    const stability = document.getElementById('stabilityDisp');
    if (!stability) return;

    let score = 85;
    const wpm = parseInt(document.getElementById('wpmDisp').innerText, 10) || 0;
    if (wordCount < 5 || seconds < 5) score -= 30;
    else if (wordCount < 20) score -= 10;
    score -= Math.min(30, fillerCount * 3);
    if (wpm > 0 && (wpm < 90 || wpm > 190)) score -= 12;

    score = Math.max(0, Math.min(100, score));
    stability.innerText = score + '%';
    stability.style.color = score >= 80 ? '#34d399' : (score >= 60 ? '#f59e0b' : '#f87171');
}

function setInstantText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function applyInstantState(el, state) {
    if (!el) return;
    el.classList.remove('good', 'warn', 'bad', 'neutral');
    el.classList.add(state || 'neutral');
}

function instantPaceSignal(wpm) {
    if (seconds < 5 || wordCount < 3) {
        return {
            state: 'neutral',
            label: 'Collecting',
            value: `${wpm} WPM`,
            note: 'Speak one complete thought to unlock a reliable pace signal.'
        };
    }

    if (wpm >= 100 && wpm <= 150) {
        return {
            state: 'good',
            label: 'Good Pace',
            value: `${wpm} WPM`,
            note: 'Your pace is within the interview-friendly range.'
        };
    }

    if (wpm >= 90 && wpm < 100) {
        return {
            state: 'warn',
            label: 'Slightly Slow',
            value: `${wpm} WPM`,
            note: 'Add a little momentum while keeping your words clear.'
        };
    }

    if (wpm > 150 && wpm <= 180) {
        return {
            state: 'warn',
            label: 'Quick',
            value: `${wpm} WPM`,
            note: 'Add short pauses between points so the answer is easier to follow.'
        };
    }

    return {
        state: 'bad',
        label: wpm < 90 ? 'Too Slow' : 'Too Fast',
        value: `${wpm} WPM`,
        note: wpm < 90
            ? 'Move into the next point sooner so the answer keeps energy.'
            : 'Slow down and pause after important details.'
    };
}

function instantFillerSignal() {
    const rate = wordCount > 0 ? (fillerCount / wordCount) * 100 : 0;

    if (wordCount < 8) {
        return {
            state: 'neutral',
            label: 'Collecting',
            value: `${fillerCount} found`,
            note: 'Filler feedback becomes useful after a few more words.'
        };
    }

    if (fillerCount === 0) {
        return {
            state: 'good',
            label: 'Clean',
            value: '0 found',
            note: 'No tracked filler words are showing in the transcript.'
        };
    }

    if (rate <= 4) {
        return {
            state: 'good',
            label: 'Manageable',
            value: `${fillerCount} found`,
            note: 'The filler count is low for the current answer length.'
        };
    }

    if (rate <= 8) {
        return {
            state: 'warn',
            label: 'Watch',
            value: `${fillerCount} found`,
            note: 'Use a silent pause before your next detail.'
        };
    }

    return {
        state: 'bad',
        label: 'High',
        value: `${fillerCount} found`,
        note: 'Replace fillers with short pauses to sound steadier.'
    };
}

function instantDepthSignal() {
    if (wordCount === 0) {
        return {
            state: 'neutral',
            label: 'Waiting',
            value: '0 words',
            note: 'A full answer usually needs a direct point and supporting evidence.'
        };
    }

    if (wordCount < 20) {
        return {
            state: 'warn',
            label: 'Brief',
            value: `${wordCount} words`,
            note: 'Add one action you took and one result or lesson.'
        };
    }

    if (wordCount > 170) {
        return {
            state: 'warn',
            label: 'Long',
            value: `${wordCount} words`,
            note: 'Start closing so the answer stays focused.'
        };
    }

    return {
        state: 'good',
        label: 'Useful Detail',
        value: `${wordCount} words`,
        note: 'Your answer has enough length for meaningful review.'
    };
}

function instantActionsFor(signals, wpm) {
    const actions = [];

    if (signals.pace.state === 'bad') {
        actions.push(wpm < 90
            ? 'Move to your next point sooner to raise delivery energy.'
            : 'Pause after each main idea to bring the pace down.');
    } else if (signals.pace.state === 'warn') {
        actions.push(wpm > 150
            ? 'Add a short pause before the next example.'
            : 'Keep your next sentence concise and forward-moving.');
    }

    if (signals.filler.state === 'bad' || signals.filler.state === 'warn') {
        actions.push('Use a silent pause instead of the next filler word.');
    }

    if (signals.depth.state === 'warn') {
        actions.push(wordCount < 20
            ? 'Add one concrete action and one result.'
            : 'Close with the result or lesson learned.');
    }

    return actions.slice(0, 3);
}

function buildInstantFeedback(metrics = currentVoiceMetrics()) {
    const signals = {
        pace: instantPaceSignal(metrics.wpm),
        filler: instantFillerSignal(),
        depth: instantDepthSignal()
    };

    let overall = 'good';
    if (wordCount === 0 && !isRec) {
        overall = 'neutral';
    } else if (Object.values(signals).some(signal => signal.state === 'bad')) {
        overall = 'bad';
    } else if (Object.values(signals).some(signal => signal.state === 'warn')) {
        overall = 'warn';
    } else if (Object.values(signals).some(signal => signal.state === 'neutral')) {
        overall = 'neutral';
    }

    return {
        metrics,
        signals,
        overall,
        actions: instantActionsFor(signals, metrics.wpm)
    };
}

function setInstantSignal(prefix, signal) {
    applyInstantState(document.getElementById(`instant${prefix}Signal`), signal.state);
    setInstantText(`instant${prefix}Value`, signal.value);
    setInstantText(`instant${prefix}Badge`, signal.label);
    setInstantText(`instant${prefix}Note`, signal.note);
}

function updateInstantFeedback() {
    const status = document.getElementById('instantFeedbackStatus');
    if (!status) return null;

    const feedback = buildInstantFeedback();
    setInstantSignal('Pace', feedback.signals.pace);
    setInstantSignal('Filler', feedback.signals.filler);
    setInstantSignal('Depth', feedback.signals.depth);

    const statusLabel = isRec
        ? (isPaused ? 'Paused' : 'Live')
        : (wordCount > 0 ? 'Review' : 'Ready');
    status.textContent = statusLabel;
    applyInstantState(status, feedback.overall);

    const summary = wordCount === 0
        ? 'Ready for your first speaking signal.'
        : feedback.overall === 'good'
            ? 'Pace, filler control, and answer depth look steady.'
            : feedback.overall === 'bad'
                ? 'One delivery signal needs attention before you finish.'
                : 'You have one small adjustment to make while speaking.';
    setInstantText('instantFeedbackSummary', summary);

    const list = document.getElementById('instantFeedbackActions');
    if (list) {
        list.innerHTML = feedback.actions
            .map(action => `<li><i class="fa-solid fa-arrow-right"></i><span>${escapeTranscriptHtml(action)}</span></li>`)
            .join('');
    }

    updateIntentionCoach(feedback.metrics);

    return feedback;
}

const intentionProfiles = {
    Confident: {
        keywords: ['i can', 'i have', 'i handled', 'i led', 'i built', 'i improved', 'my experience', 'contribute', 'ready'],
        tip: 'Use a clear ownership phrase such as "I handled", "I improved", or "I can contribute".'
    },
    Friendly: {
        keywords: ['thank', 'appreciate', 'happy', 'glad', 'support', 'help', 'together', 'welcome'],
        tip: 'Add a warm acknowledgement before the main point, then keep the answer concise.'
    },
    Calm: {
        keywords: ['understand', 'clarify', 'resolve', 'confirm', 'next step', 'check', 'help', 'issue'],
        tip: 'Use a steady sequence: acknowledge the concern, explain the fact, then name the next step.'
    },
    Persuasive: {
        keywords: ['because', 'benefit', 'evidence', 'result', 'improve', 'support', 'recommend', 'value'],
        tip: 'Add one reason and one proof point so the listener can verify your claim.'
    },
    Accountable: {
        keywords: ['learned', 'changed', 'feedback', 'mistake', 'weakness', 'improved', 'now', 'responsibility'],
        tip: 'Own the issue briefly, then spend more time on what changed and what improved.'
    }
};

function escapeRegExp(value) {
    return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function countIntentionHits(normalizedText, keywords) {
    return keywords.reduce((count, keyword) => {
        return count + (new RegExp(`\\b${escapeRegExp(keyword)}\\b`, 'i').test(normalizedText) ? 1 : 0);
    }, 0);
}

function selectedIntention() {
    const select = document.getElementById('intentionSelect');
    return select?.value || 'Confident';
}

function detectIntention(normalizedText) {
    if (!normalizedText || wordCount < 8) {
        return { label: 'Waiting', score: 0, hits: 0 };
    }

    let best = { label: 'Neutral', score: 0, hits: 0 };
    Object.entries(intentionProfiles).forEach(([label, profile]) => {
        const hits = countIntentionHits(normalizedText, profile.keywords);
        const score = hits * 18;
        if (score > best.score) {
            best = { label, score, hits };
        }
    });

    return best;
}

function buildIntentionCoach(metrics = currentVoiceMetrics()) {
    const target = selectedIntention();
    const profile = intentionProfiles[target] || intentionProfiles.Confident;
    const normalized = normalizeTranscriptForMatch(transcript);
    const detected = detectIntention(normalized);

    if (!normalized || wordCount < 8) {
        return {
            target,
            detected: detected.label,
            match: 0,
            state: 'neutral',
            summary: 'Waiting for enough words to compare your wording with the target intention.',
            tips: []
        };
    }

    const targetHits = countIntentionHits(normalized, profile.keywords);
    let match = targetHits * 18;
    if (detected.label === target) match += 24;
    if (wordCount >= 35 && wordCount <= 150) match += 14;
    if (fillerCount <= 2) match += 10;
    if (metrics.wpm >= 90 && metrics.wpm <= 170) match += 10;
    if (metrics.confidence >= 75) match += 8;
    match = Math.max(0, Math.min(100, match));

    const tips = [];
    if (detected.label !== target || match < 70) {
        tips.push(profile.tip);
    }
    if (metrics.wpm > 170) {
        tips.push('Slow the next sentence slightly so the intention sounds controlled.');
    } else if (metrics.wpm > 0 && metrics.wpm < 90) {
        tips.push('Add a little more energy so the answer does not sound hesitant.');
    }
    if (fillerCount > 2) {
        tips.push('Replace the next filler with a short silent pause.');
    }
    if (wordCount < 35) {
        tips.push('Add one concrete example so the intention has evidence behind it.');
    }
    if (tips.length === 0) {
        tips.push(`Your ${target.toLowerCase()} intention is coming through. Finish with one clear result or next action.`);
    }

    return {
        target,
        detected: detected.label,
        match,
        state: match >= 80 ? 'good' : (match >= 55 ? 'warn' : 'bad'),
        summary: detected.label === target
            ? `Your wording is mostly aligned with a ${target.toLowerCase()} delivery.`
            : `Your wording currently reads closer to ${detected.label.toLowerCase()} than ${target.toLowerCase()}.`,
        tips: tips.slice(0, 3)
    };
}

function updateIntentionCoach(metrics = currentVoiceMetrics()) {
    const status = document.getElementById('intentionCoachStatus');
    if (!status) return null;

    const coach = buildIntentionCoach(metrics);
    setInstantText('intentionTargetValue', coach.target);
    setInstantText('intentionDetectedValue', coach.detected);
    setInstantText('intentionMatchValue', coach.match > 0 ? `${coach.match}%` : '--%');
    setInstantText('intentionCoachSummary', coach.summary);

    status.textContent = coach.match > 0 ? (coach.state === 'good' ? 'Aligned' : (coach.state === 'warn' ? 'Adjust' : 'Mismatch')) : 'Ready';
    applyInstantState(status, coach.state);

    const meter = document.getElementById('intentionMatchMeter');
    if (meter) meter.style.setProperty('--intent-score', `${coach.match}%`);

    const list = document.getElementById('intentionCoachTips');
    if (list) {
        list.innerHTML = coach.tips
            .map(tip => `<li><i class="fa-solid fa-arrow-right"></i><span>${escapeTranscriptHtml(tip)}</span></li>`)
            .join('');
    }

    return coach;
}

async function startRec() {
    if (!recognition) {
        const message = 'Live microphone transcription is not supported in this browser.';
        setMicrophoneHelp(message, '#fbbf24');
        setTranscriptionStatus(message, '#fbbf24');
        setVoiceNotice(message, 'warning', true);
        return;
    }
    if (isRec || stopInProgress) return;

    setStartButtonEnabled(false);
    const microphoneReady = await ensureMicrophonePermission();
    if (!microphoneReady) {
        restoreStartAvailabilityAfterFailedPermission();
        return;
    }
    setStartButtonEnabled(true);

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
    window.currentAnalysis = null;
    window.analysisTranscript = null;
    setAnalysisPanelActive(false);
    setVoiceNotice('');
    
    document.getElementById('transcriptView').innerHTML = "";
    document.getElementById('transcriptView').setAttribute('contenteditable', 'false');
    document.getElementById('editHint').style.display = 'none';
    document.getElementById('timeDisp').innerText = "0:00";
    document.getElementById('wpmDisp').innerText = "0";
    document.getElementById('fillerDisp').innerText = "0";
    document.getElementById('stabilityDisp').innerText = "0%";
    updateInstantFeedback();

    updateUIState();

    const started = startSpeechRecognitionEngine();
    if (!started && !recognitionActive) {
        isRec = false;
        isPaused = false;
        shouldAutoRestartRecognition = false;
        updateUIState();
        restoreStartAvailabilityAfterFailedPermission();
        return;
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
    clearInterval(timer);
    updateUIState();
    updateInstantFeedback();
}

function resumeRec() {
    if (!isRec || !isPaused) return;
    isPaused = false;
    shouldAutoRestartRecognition = true;
    const started = startSpeechRecognitionEngine();
    if (!started && !recognitionActive) {
        isPaused = true;
        shouldAutoRestartRecognition = false;
        updateUIState();
        return;
    }
    clearInterval(timer);
    timer = setInterval(updateTimer, 1000);
    updateUIState();
    updateInstantFeedback();
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

        clearInterval(timer);

        await recognitionSettled;
        finalizeInterimTranscript();
        processTranscript(mergeTranscriptParts(committedSpeechTranscript, liveSpeechInterim));
        updateUIState();
        updateInstantFeedback();

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
    document.getElementById('stabilityDisp').innerText = "0%";
    seconds = 0;
    wordCount = 0;
    fillerCount = 0;
    document.getElementById('transcriptView').innerHTML = "Your speech will appear here...";
    setAnalysisPanelActive(false);
    document.getElementById('comparisonPanel').style.display = 'none';
    updateInstantFeedback();
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
    setVoiceSaveReady(false);
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
    const instant = buildInstantFeedback({ wpm, clarity, confidence });
    const actionText = instant.actions.join(' ');
    const steadySignals = Object.values(instant.signals)
        .filter(signal => signal.state === 'good')
        .map(signal => signal.label.toLowerCase());

    return {
        ai_feedback_strengths: tooShort
            ? 'The transcript was saved, but it is too brief for reliable AI strengths feedback.'
            : steadySignals.length > 0
                ? `Saved with steady local delivery signals: ${steadySignals.join(', ')}.`
                : 'Saved with local delivery metrics from the transcript, pace, and filler count.',
        ai_feedback_weaknesses: tooShort
            ? 'Add a fuller answer with a clear situation, action, and result before relying on coaching feedback.'
            : actionText || 'Use the WPM, filler count, and transcript to identify the next practice focus.',
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
        setAnalysisPanelActive(false);
        setVoiceNotice('Record or enter a transcript before analysis.', 'warning', true);
        return false;
    }

    setAnalysisPanelActive(true);
    setVoiceSaveReady(false);
    
    // Calculate metrics
    const metrics = currentVoiceMetrics();
    const wpm = metrics.wpm;
    const instant = updateInstantFeedback() || buildInstantFeedback(metrics);
    
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
    
    // Show immediate local feedback while the deeper AI review runs.
    document.getElementById('resStrengths').innerText = instant.overall === 'good'
        ? 'Your live delivery signals are steady: pace, filler control, and answer depth.'
        : 'Your voice metrics were captured instantly from the transcript.';
    document.getElementById('resWeak').innerText = instant.actions.join(' ');
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
        setVoiceSaveReady(true);
        setVoiceNotice('Analysis ready. You can save this rehearsal now.', 'success');
        return true;

    } catch (error) {
        console.error("Analysis Error:", error);
        const fallback = localAnalysisPayload(clarity, confScore, wpm);
        document.getElementById('resStrengths').innerText = fallback.ai_feedback_strengths;
        document.getElementById('resWeak').innerText = fallback.ai_feedback_weaknesses;
        document.getElementById('compAI').innerText = fallback.ai_improved_answer;
        window.currentAnalysis = fallback;
        window.analysisTranscript = transcript;
        setVoiceSaveReady(true);
        setVoiceNotice('Local analysis is ready. AI feedback was unavailable, so fallback coaching was used.', 'warning');
        return true;
    }
}

async function saveSession() {
    transcript = collapseRepeatedSpeech(transcriptEditorText());
    processTranscript(transcript);

    if (!transcript) {
        setVoiceNotice('Record or enter a transcript before saving.', 'warning', true);
        setVoiceSaveReady(false);
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
    btn.setAttribute('aria-disabled', 'true');

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
            setVoiceNotice('Session saved to your history.', 'success');

            const clarityScore = Number(data.session.score) || parseInt(String(data.session.clarity || '').replace(/[^\d]/g, ''), 10) || 0;
            voiceHistoryData.unshift({
                d: data.session.date,
                ts: Number(data.session.timestamp) || Math.floor(Date.now() / 1000),
                c: data.session.category,
                cl: data.session.clarity,
                w: Number(data.session.wpm) || 0,
                f: Number(data.session.fillers) || 0,
                score: clarityScore
            });
            loadHistory();
            renderVoiceProgressChart();
        } else {
            setVoiceNotice('Failed to save the session.', 'danger', true);
        }
    } catch (error) {
        console.error("Save Error:", error);
        setVoiceNotice(error.message || "An error occurred while saving.", 'danger', true);
    } finally {
        btn.innerHTML = originalText;
        setVoiceSaveReady(Boolean(window.currentAnalysis && window.analysisTranscript === transcript));
    }
}

function downloadReport() {
    setVoiceNotice('Use the print dialog destination to save this report as a PDF.', 'info');
    window.print();
}

// Chart.js & History Init
let voiceProgressChart = null;
let voiceHistoryData = {!! json_encode($history->map(function($session) {
    return [
        'd' => $session->created_at->format('M d'),
        'ts' => $session->created_at->timestamp,
        'c' => $session->practice_scenario ?? 'Job Interviews',
        'cl' => ($session->clarity_score ?? 0) . '%',
        'w' => $session->wpm ?? 0,
        'f' => $session->filler_words ?? 0,
        'score' => $session->clarity_score ?? 0
    ];
})) !!};

function loadHistory() {
    let html = '';
    if (voiceHistoryData.length === 0) {
        html = '<tr><td colspan="5"><div class="vr-history-empty"><div><i class="fa-solid fa-file-lines"></i><strong>No history found.</strong><span>Practice a Philippines voice session to see it here.</span></div></div></td></tr>';
    } else {
        voiceHistoryData.forEach(h => {
            const date = escapeTranscriptHtml(h.d);
            const category = escapeTranscriptHtml(h.c);
            const clarity = escapeTranscriptHtml(h.cl);
            const wpm = escapeTranscriptHtml(h.w);
            const fillers = escapeTranscriptHtml(h.f);
            html += `<tr>
                <td data-label="Date" style="color:var(--tx2);font-size:0.9rem;">${date}</td>
                <td data-label="Prompt Scenario"><span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;">${category}</span></td>
                <td data-label="Clarity" style="color:#34d399;font-weight:600;">${clarity}</td>
                <td data-label="WPM">${wpm}</td>
                <td data-label="Fillers" style="color:#f87171;">${fillers}</td>
            </tr>`;
        });
    }
    document.getElementById('historyTable').innerHTML = html;
    
    return voiceHistoryData;
}

function chronologicalVoiceHistory() {
    return [...voiceHistoryData].sort((a, b) => (a.ts || 0) - (b.ts || 0));
}

function renderVoiceProgressChart() {
    if(typeof Chart === 'undefined') return;

    Chart.defaults.color = '#808090';
    Chart.defaults.font.family = "'Inter', sans-serif";

    const voiceProgressCanvas = document.getElementById('voiceProgressChart');
    if (!voiceProgressCanvas) return;

    const chartRows = chronologicalVoiceHistory();
    const emptyState = document.getElementById('voiceProgressEmpty');
    if (emptyState) emptyState.style.display = chartRows.length === 0 ? 'block' : 'none';

    const chartData = {
        labels: chartRows.map(h => h.d),
        datasets: [{
            label: 'Clarity Score',
            data: chartRows.map(h => Number(h.score) || 0),
            borderColor: '#34d399',
            backgroundColor: 'rgba(52,211,153,0.1)',
            borderWidth: 3, tension: 0.4, fill: true
        }, {
            label: 'Fillers Used',
            data: chartRows.map(h => Number(h.f) || 0),
            borderColor: '#f87171',
            backgroundColor: 'transparent',
            borderWidth: 2, tension: 0.4, fill: false
        }]
    };

    if (voiceProgressChart) {
        voiceProgressChart.data = chartData;
        voiceProgressChart.update();
        return;
    }

    voiceProgressChart = new Chart(voiceProgressCanvas.getContext('2d'), {
        type: 'line',
        data: chartData,
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

document.addEventListener("DOMContentLoaded", function() {
    loadHistory();
    const presetCategory = categoryValueForPreset(voiceMissionPreset.category);
    const categorySelect = document.getElementById('categorySelect');
    if (presetCategory && categorySelect && Array.from(categorySelect.options).some(option => option.value === presetCategory)) {
        categorySelect.value = presetCategory;
    }

    const intentionSelect = document.getElementById('intentionSelect');
    if (voiceMissionPreset.intent && intentionSelect && Array.from(intentionSelect.options).some(option => option.value === voiceMissionPreset.intent)) {
        intentionSelect.value = voiceMissionPreset.intent;
    }

    if (voiceMissionPreset.prompt) {
        document.getElementById('promptText').innerText = `"${cleanTranscriptText(voiceMissionPreset.prompt)}"`;
    } else {
        randomizePrompt({ silent: true });
    }
    syncVoiceOptionLabels();
    setVoiceSaveReady(false);
    refreshMicrophoneAvailabilityUi();
    updateInstantFeedback();
    updateIntentionCoach();
    renderVoiceProgressChart();
});
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#voiceCategoryButton', popover: { title: 'Prompt Scenario', description: 'Choose the Philippines interview scenario for this voice rehearsal.', side: 'bottom', align: 'start' }},
            { element: '#btnStart', popover: { title: 'Start Recording', description: 'Record your answer and track pacing, filler words, and transcript quality.', side: 'bottom', align: 'center' }},
            { element: '#transcriptView', popover: { title: 'Live Transcript', description: 'Your speech appears here while filler words and keywords are highlighted.', side: 'top', align: 'start' }},
            { element: '#analysisPanel', popover: { title: 'Practice Assessment', description: 'After recording, review pacing, clarity, and next practice actions.', side: 'top', align: 'start' }},
            { element: '#moduleTabs', popover: { title: 'History & Analytics', description: 'Use the other tabs to review past sessions, charts, and long-term progress.', side: 'bottom', align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#voiceCategoryButton', popover: { title: 'Prompt Scenario', description: 'Choose the Philippines interview scenario for this voice rehearsal.', side: 'bottom', align: 'start' }},
            { element: '#btnStart', popover: { title: 'Start Recording', description: 'Record your answer and track pacing, filler words, and transcript quality.', side: 'bottom', align: 'center' }},
            { element: '#transcriptView', popover: { title: 'Live Transcript', description: 'Your speech appears here while filler words and keywords are highlighted.', side: 'top', align: 'start' }},
            { element: '#analysisPanel', popover: { title: 'Practice Assessment', description: 'After recording, review pacing, clarity, and next practice actions.', side: 'bottom', align: 'start' }},
            { element: '#moduleTabs', popover: { title: 'History & Analytics', description: 'Use the other tabs to review past sessions, charts, and long-term progress.', side: 'bottom', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_drills_voice',
            serverDetectedMobile: false,
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
