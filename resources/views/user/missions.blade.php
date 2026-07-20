@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Real-Life Mission Mode')

@section('content')
<style>
    .mission-page {
        display: flex;
        flex-direction: column;
        gap: 18px;
        padding-bottom: 28px;
    }
    .text-gradient-primary {
        background: linear-gradient(135deg, #2563eb 0%, #0891b2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .mission-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(320px, 420px);
        gap: 18px;
        align-items: start;
    }
    .mission-panel {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 18px;
        box-shadow: 0 10px 34px rgba(15, 23, 42, 0.08);
        padding: 18px;
        min-width: 0;
    }
    .mission-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }
    .mission-title {
        color: var(--tx);
        font-size: 1rem;
        font-weight: 850;
        margin: 0;
    }
    .mission-kicker {
        color: var(--tx3);
        font-size: 0.78rem;
        line-height: 1.45;
        margin-top: 4px;
    }
    .mission-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        color: var(--pill-color, #60a5fa);
        background: color-mix(in srgb, var(--pill-color, #60a5fa) 12%, transparent);
        border: 1px solid color-mix(in srgb, var(--pill-color, #60a5fa) 24%, transparent);
        font-size: 0.72rem;
        font-weight: 850;
        white-space: nowrap;
    }
    .mission-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .mission-generator {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 9px;
        margin-bottom: 14px;
    }
    .mission-generator input {
        width: 100%;
        min-height: 42px;
        color: var(--tx);
        background: var(--bg3);
        border: 1px solid var(--bd);
        border-radius: 12px;
        padding: 9px 12px;
        outline: none;
    }
    .mission-generator input:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.14);
    }
    .mission-generator-status {
        color: var(--tx3);
        font-size: 0.76rem;
        line-height: 1.35;
        margin: -6px 0 12px;
    }
    .mission-empty-state {
        color: var(--tx3);
        background: var(--bg3);
        border: 1px dashed var(--bd2);
        border-radius: 14px;
        padding: 16px;
        font-size: 0.84rem;
        line-height: 1.45;
        text-align: center;
    }
    .mission-card {
        width: 100%;
        min-height: 176px;
        text-align: left;
        border: 1px solid var(--bd);
        border-radius: 16px;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--mission-color, #2563eb) 10%, transparent), transparent 58%),
            var(--bg3);
        color: var(--tx);
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        cursor: pointer;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .mission-card:hover,
    .mission-card.active {
        border-color: color-mix(in srgb, var(--mission-color, #2563eb) 48%, var(--bd));
        box-shadow: 0 14px 34px color-mix(in srgb, var(--mission-color, #2563eb) 18%, transparent);
        transform: translateY(-2px);
    }
    #mission-mode-page .mission-card-name {
        color: var(--tx);
        font-size: 0.82rem;
        font-weight: 900;
        line-height: 1;
        margin: 0 !important;
        min-width: 0;
        width: 100% !important;
        max-width: none !important;
        display: flex !important;
        align-items: center;
        gap: 6px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap !important;
    }
    #mission-mode-page .mission-title-icon {
        width: 18px;
        height: 18px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 18px;
        color: var(--mission-color, #2563eb);
        background: color-mix(in srgb, var(--mission-color, #2563eb) 14%, transparent);
        font-size: 0.55rem;
    }
    #mission-mode-page .mission-title-text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .mission-card-copy {
        color: var(--tx3);
        font-size: 0.76rem;
        line-height: 1.45;
        margin: 0;
    }
    .mission-card-meta,
    .mission-meta-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: auto;
    }
    .mission-meta {
        border-radius: 999px;
        padding: 5px 8px;
        color: var(--tx2);
        background: rgba(148, 163, 184, 0.1);
        border: 1px solid rgba(148, 163, 184, 0.18);
        font-size: 0.68rem;
        font-weight: 800;
    }
    .mission-detail-head {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }
    .mission-detail-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--mission-color, #2563eb);
        background: color-mix(in srgb, var(--mission-color, #2563eb) 14%, transparent);
        border: 1px solid color-mix(in srgb, var(--mission-color, #2563eb) 24%, transparent);
        flex: 0 0 auto;
    }
    .mission-prompt {
        color: var(--tx);
        font-size: 1rem;
        line-height: 1.45;
        font-weight: 750;
        padding: 13px;
        border: 1px solid var(--bd);
        border-radius: 14px;
        background: var(--bg3);
        margin: 12px 0;
    }
    .mission-criteria {
        display: grid;
        gap: 8px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .mission-criteria li {
        display: flex;
        gap: 8px;
        color: var(--tx2);
        font-size: 0.8rem;
        line-height: 1.45;
    }
    .mission-criteria i {
        color: #22c55e;
        margin-top: 3px;
        flex: 0 0 auto;
    }
    .mission-answer {
        width: 100%;
        min-height: 190px;
        resize: vertical;
        color: var(--tx);
        background: var(--bg3);
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 14px;
        line-height: 1.55;
        outline: none;
    }
    .mission-answer:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.14);
    }
    .mission-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
        margin-top: 12px;
    }
    .mission-btn {
        min-height: 42px;
        border-radius: 12px;
        border: 1px solid var(--bd2);
        background: transparent;
        color: var(--tx);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 9px 12px;
        font-size: 0.84rem;
        font-weight: 850;
        text-decoration: none;
        cursor: pointer;
        text-align: center;
    }
    .mission-btn-primary {
        color: #fff;
        border-color: transparent;
        background: linear-gradient(135deg, #2563eb, #0891b2);
        box-shadow: 0 10px 22px rgba(37, 99, 235, 0.2);
    }
    .mission-result-grid {
        display: grid;
        grid-template-columns: 160px minmax(0, 1fr);
        gap: 16px;
        align-items: center;
    }
    #missionResultPanel {
        margin-top: 6px;
    }
    .mission-score-ring {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        margin: 0 auto;
        color: var(--tx);
        background:
            conic-gradient(var(--score-color, #22c55e) var(--score, 0%), rgba(148, 163, 184, 0.2) 0),
            var(--bg3);
    }
    .mission-score-inner {
        width: 104px;
        height: 104px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--sf);
        border: 1px solid var(--bd);
        font-size: 1.9rem;
        font-weight: 950;
    }
    .mission-feedback-list {
        display: grid;
        gap: 9px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .mission-feedback-list li {
        display: flex;
        gap: 9px;
        color: var(--tx2);
        font-size: 0.82rem;
        line-height: 1.45;
    }
    .mission-feedback-list i {
        color: #60a5fa;
        flex: 0 0 auto;
        margin-top: 3px;
    }
    .mission-voice-modal .modal-content {
        background: var(--sf);
        color: var(--tx);
        border: 1px solid var(--bd);
        border-radius: 18px;
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.24);
    }
    .modal-backdrop.mission-voice-backdrop {
        background: rgba(15, 23, 42, 0.42);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        opacity: 1 !important;
    }
    .mission-voice-modal .modal-header,
    .mission-voice-modal .modal-footer {
        border-color: var(--bd);
    }
    .mission-voice-modal .modal-footer {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .mission-voice-modal .modal-footer .btn {
        width: 100%;
        min-height: 42px;
    }
    .mission-voice-prompt,
    .mission-voice-transcript {
        color: var(--tx);
        background: var(--bg3);
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 14px;
        line-height: 1.5;
    }
    .mission-voice-transcript {
        min-height: 140px;
        white-space: pre-wrap;
    }
    .mission-voice-status {
        color: var(--tx3);
        font-size: 0.8rem;
        line-height: 1.4;
    }
    .mission-recent-list {
        display: grid;
        gap: 9px;
    }
    .mission-recent-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 11px;
        border: 1px solid var(--bd);
        border-radius: 14px;
        background: var(--bg3);
    }
    .mission-recent-title {
        color: var(--tx);
        font-size: 0.84rem;
        font-weight: 850;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .mission-recent-meta {
        color: var(--tx3);
        font-size: 0.72rem;
        margin-top: 2px;
    }
    @media (max-width: 991px) {
        .mission-shell,
        .mission-result-grid {
            grid-template-columns: 1fr;
        }
        .mission-grid {
            grid-template-columns: 1fr;
        }
        .mission-generator {
            grid-template-columns: 1fr;
        }
        .mission-score-ring {
            width: 124px;
            height: 124px;
        }
        .mission-score-inner {
            width: 92px;
            height: 92px;
            font-size: 1.6rem;
        }
    }
    @media (max-width: 576px) {
        .mission-panel {
            padding: 14px;
            border-radius: 15px;
        }
        #missionResultPanel {
            margin-top: 14px;
        }
        .mission-panel-head {
            display: grid;
            grid-template-columns: 1fr;
        }
        .mission-detail-head {
            display: flex !important;
            grid-template-columns: none !important;
            align-items: center !important;
            flex-direction: row !important;
            gap: 8px;
        }
        .mission-detail-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            flex: 0 0 32px;
            font-size: 0.82rem;
        }
        .mission-detail-head > div {
            min-width: 0;
        }
        .mission-detail-head .mission-title {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .mission-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .mission-btn {
            min-height: 42px;
            padding: 8px 6px;
            font-size: 0.72rem;
            line-height: 1.15;
            white-space: normal;
            gap: 5px;
        }
        .mission-btn i {
            flex: 0 0 auto;
            margin: 0;
        }
        .mission-card {
            min-height: 0;
            gap: 10px;
            padding: 13px;
        }
        #mission-mode-page .mission-card-name {
            font-size: 0.76rem;
            white-space: nowrap !important;
            overflow: hidden;
            text-overflow: ellipsis;
            gap: 5px;
        }
        #mission-mode-page .mission-title-icon {
            width: 17px;
            height: 17px;
            border-radius: 5px;
            flex-basis: 17px;
            font-size: 0.5rem;
        }
    }
</style>
@include('partials.page-hero-styles')

<div class="db-section active mission-page" id="mission-mode-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 4v6c0 4.4-2.8 7.8-7 8-4.2-.2-7-3.6-7-8V7l7-4Z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M9 12l2 2 4-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Real-Life Mission Mode
                </h4>
                <p class="sr-page-hero-subtitle">Complete practical speaking tasks for interviews, customer calls, panels, and team conversations.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="missionPanel" x1="36" y1="18" x2="182" y2="126"><stop stop-color="#DCFCE7"/><stop offset="1" stop-color="#DBEAFE"/></linearGradient><linearGradient id="missionAccent" x1="72" y1="38" x2="152" y2="118"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#14B8A6"/></linearGradient></defs>
            <rect x="32" y="24" width="156" height="104" rx="19" fill="url(#missionPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <path d="M73 91l23 22 53-66" fill="none" stroke="url(#missionAccent)" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="65" cy="50" r="14" fill="#F59E0B"/><path d="M58 50h14M65 43v14" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
            <rect x="132" y="88" width="34" height="22" rx="8" fill="#2563EB" opacity=".82"/>
            <path d="M34 136c28-10 61-11 94 0s61 7 82-4" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".52"/>
        </svg>
    </div>

    <div class="mission-shell">
        <section class="mission-panel">
            <div class="mission-panel-head">
                <div>
                    <h5 class="mission-title"><i class="fa-solid fa-route me-2" style="color:#0ea5e9;"></i>Mission Board</h5>
                    <div class="mission-kicker">Generate tasks from what you want to practice, then measure how ready your answer sounds.</div>
                </div>
                <span class="mission-pill" style="--pill-color:#16a34a"><i class="fa-solid fa-microphone-lines"></i>{{ $practiceSessionCount }} saved sessions</span>
            </div>

            <div class="mission-generator">
                <input type="text" id="missionGoalInput" maxlength="240" placeholder="Example: BPO final interview, scholarship panel, IT debugging question...">
                <button type="button" class="mission-btn mission-btn-primary" id="generateMissionBtn" style="min-height:42px;"><i class="fa-solid fa-wand-magic-sparkles"></i>Generate Task</button>
            </div>
            <div class="mission-generator-status" id="missionGeneratorStatus">Tasks can be personalized to your target role, school interview, panel, or workplace situation.</div>

            <div class="mission-grid" id="missionGrid">
            </div>
        </section>

        <aside class="mission-panel" id="missionTool">
            <div class="mission-detail-head">
                <span class="mission-detail-icon" id="detailIcon"><i class="fa-solid fa-route"></i></span>
                <div>
                    <h5 class="mission-title" id="detailTitle">Mission</h5>
                    <div class="mission-meta-row">
                        <span class="mission-meta" id="detailCategory">Scenario</span>
                        <span class="mission-meta" id="detailDuration">60s</span>
                        <span class="mission-meta" id="detailIntent">Confident</span>
                    </div>
                </div>
            </div>

            <div class="mission-prompt" id="detailPrompt"></div>

            <ul class="mission-criteria mb-3" id="detailCriteria"></ul>

            <div class="mission-kicker mb-2" id="detailTip"></div>
            <textarea class="mission-answer" id="missionAnswer" placeholder="Type or paste your spoken answer here..."></textarea>

            <div class="mission-actions">
                <button type="button" class="mission-btn" id="missionTimerBtn"><i class="fa-regular fa-clock"></i><span id="missionTimerText">Start 0:00</span></button>
                <button type="button" class="mission-btn mission-btn-primary" id="scoreMissionBtn"><i class="fa-solid fa-chart-simple"></i>Score Answer</button>
                <button type="button" class="mission-btn" id="voiceMissionBtn"><i class="fa-solid fa-microphone-lines"></i>Practice With Voice</button>
                <button type="button" class="mission-btn" id="clearMissionBtn"><i class="fa-solid fa-eraser"></i>Clear</button>
            </div>
        </aside>
    </div>

    <section class="mission-panel" id="missionResultPanel" aria-live="polite">
        <div class="mission-result-grid">
            <div class="mission-score-ring" id="missionScoreRing" style="--score:0%;--score-color:#22c55e;">
                <div class="mission-score-inner"><span id="missionScoreValue">--</span></div>
            </div>
            <div>
                <div class="mission-panel-head mb-2">
                    <div>
                        <h5 class="mission-title"><i class="fa-solid fa-clipboard-check me-2" style="color:#22c55e;"></i>Mission Result</h5>
                        <div class="mission-kicker" id="missionResultSummary">Score an answer to see mission-specific feedback.</div>
                    </div>
                    <span class="mission-pill" id="missionResultStatus" style="--pill-color:#64748b">Waiting</span>
                </div>
                <ul class="mission-feedback-list" id="missionFeedbackList">
                    <li><i class="fa-solid fa-circle-info"></i><span>Your result will check structure, evidence, tone fit, and next action.</span></li>
                </ul>
            </div>
        </div>
    </section>

    @if($recentVoiceSessions->isNotEmpty())
        <section class="mission-panel">
            <div class="mission-panel-head">
                <div>
                    <h5 class="mission-title"><i class="fa-solid fa-clock-rotate-left me-2" style="color:#f59e0b;"></i>Recent Voice Practice</h5>
                    <div class="mission-kicker">Latest saved rehearsals that can support mission progress.</div>
                </div>
                <a href="{{ route('user.drills.voice') }}" class="mission-btn" style="min-height:36px;padding:7px 11px;"><i class="fa-solid fa-arrow-right"></i>Open Voice</a>
            </div>
            <div class="mission-recent-list">
                @foreach($recentVoiceSessions as $session)
                    <div class="mission-recent-item">
                        <div>
                            <div class="mission-recent-title">{{ $session->practice_scenario }}</div>
                            <div class="mission-recent-meta">{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }} · {{ $session->wpm ?? 0 }} WPM · {{ $session->filler_words ?? 0 }} fillers</div>
                        </div>
                        <span class="mission-pill" style="--pill-color:#16a34a">{{ $session->clarity_score ?? 0 }}%</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

<div class="modal fade mission-voice-modal" id="missionVoiceModal" tabindex="-1" aria-labelledby="missionVoiceModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="missionVoiceModalTitle">Mission Voice Practice</h5>
                    <div class="mission-voice-status" id="missionVoiceStatus">Listen to the mission, then record your spoken answer.</div>
                </div>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="mission-kicker mb-2">Mission Question</div>
                    <div class="mission-voice-prompt" id="missionVoicePrompt">Generate or select a mission first.</div>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="mission-btn mission-btn-primary" id="speakMissionBtn" style="min-height:38px;padding:8px 12px;"><i class="fa-solid fa-volume-high"></i>AI Speak Mission</button>
                    <button type="button" class="mission-btn" id="startMissionVoiceBtn" style="min-height:38px;padding:8px 12px;"><i class="fa-solid fa-microphone"></i>Start Voice</button>
                    <button type="button" class="mission-btn" id="stopMissionVoiceBtn" style="min-height:38px;padding:8px 12px;"><i class="fa-solid fa-stop"></i>Stop</button>
                    <button type="button" class="mission-btn" id="clearMissionVoiceBtn" style="min-height:38px;padding:8px 12px;"><i class="fa-solid fa-eraser"></i>Clear Transcript</button>
                </div>
                <div>
                    <div class="mission-kicker mb-2">Voice Transcript</div>
                    <div class="mission-voice-transcript" id="missionVoiceTranscript" contenteditable="true">Your spoken answer will appear here...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="useMissionVoiceTranscriptBtn"><i class="fa-solid fa-check me-1"></i>Use Transcript</button>
            </div>
        </div>
    </div>
</div>

<script>
let missionData = @json($missions->values());
const missionGenerateUrl = @json(route('user.missions.generate'));
let activeMission = missionData[0] || null;
let missionTimer = null;
let remainingSeconds = activeMission ? Number(activeMission.duration) || 60 : 60;
const MissionSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
let missionRecognition = null;
let missionVoiceTranscript = '';

function escapeMissionHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function normalizeMissionText(value) {
    return String(value || '').toLowerCase().replace(/[^\p{L}\p{N}\s']/gu, ' ').replace(/\s+/g, ' ').trim();
}

function missionWordCount(value) {
    const clean = normalizeMissionText(value);
    return clean ? clean.split(/\s+/).length : 0;
}

function hasAny(text, terms) {
    return terms.some(term => new RegExp(`\\b${term}\\b`, 'i').test(text));
}

function formatMissionTime(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

function renderMissionBoard() {
    const grid = document.getElementById('missionGrid');
    if (!grid) return;

    if (missionData.length === 0) {
        grid.innerHTML = '<div class="mission-empty-state">Tell AI what you want to practice to generate your mission tasks.</div>';
        return;
    }

    grid.innerHTML = missionData.map(mission => `
        <button type="button" class="mission-card" data-mission-id="${escapeMissionHtml(mission.id)}" style="--mission-color: ${escapeMissionHtml(mission.color)};">
            <h6 class="mission-card-name">
                <span class="mission-title-icon"><i class="fa-solid ${escapeMissionHtml(mission.icon)}"></i></span>
                <span class="mission-title-text">${escapeMissionHtml(mission.title)}</span>
            </h6>
            <div>
                <p class="mission-card-copy">${escapeMissionHtml(mission.prompt)}</p>
            </div>
            <div class="mission-card-meta">
                <span class="mission-meta">${escapeMissionHtml(mission.difficulty)}</span>
                <span class="mission-meta"><i class="fa-regular fa-clock me-1"></i>${Number(mission.duration) || 60}s</span>
                <span class="mission-meta"><i class="fa-solid fa-face-smile me-1"></i>${escapeMissionHtml(mission.intent)}</span>
            </div>
        </button>
    `).join('');

    document.querySelectorAll('.mission-card').forEach(card => {
        card.addEventListener('click', () => selectMission(card.dataset.missionId));
    });
}

function setMissionGeneratorStatus(message, color = 'var(--tx3)') {
    const status = document.getElementById('missionGeneratorStatus');
    if (!status) return;
    status.textContent = message;
    status.style.color = color;
}

async function generateMissionTasks() {
    const input = document.getElementById('missionGoalInput');
    const button = document.getElementById('generateMissionBtn');
    const goal = input ? input.value.trim() : '';

    if (goal.length < 3) {
        setMissionGeneratorStatus('Type what you want to practice first.', '#f59e0b');
        return;
    }

    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Generating';
    setMissionGeneratorStatus('Generating missions for your goal...');

    try {
        const response = await fetch(missionGenerateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ goal })
        });

        const data = await response.json();
        if (!response.ok || !data.success || !Array.isArray(data.missions) || data.missions.length === 0) {
            throw new Error(data.message || 'Mission generation failed.');
        }

        missionData = data.missions;
        activeMission = missionData[0] || null;
        renderMissionBoard();
        selectMission(activeMission?.id);
        setMissionGeneratorStatus('Generated fresh missions based on your request.', '#16a34a');
    } catch (error) {
        console.error('Mission generation failed:', error);
        setMissionGeneratorStatus('Could not generate with AI right now. Try a simpler goal or use the current missions.', '#ef4444');
    } finally {
        button.disabled = false;
        button.innerHTML = originalHtml;
    }
}

function stopMissionTimer() {
    if (missionTimer) {
        clearInterval(missionTimer);
        missionTimer = null;
    }
}

function resetMissionTimer() {
    stopMissionTimer();
    remainingSeconds = activeMission ? Number(activeMission.duration) || 60 : 60;
    document.getElementById('missionTimerText').textContent = `Start ${formatMissionTime(remainingSeconds)}`;
}

function tickMissionTimer() {
    remainingSeconds = Math.max(0, remainingSeconds - 1);
    document.getElementById('missionTimerText').textContent = remainingSeconds > 0
        ? `Stop ${formatMissionTime(remainingSeconds)}`
        : 'Time Up';

    if (remainingSeconds === 0) {
        stopMissionTimer();
    }
}

function toggleMissionTimer() {
    if (missionTimer) {
        resetMissionTimer();
        return;
    }

    missionTimer = setInterval(tickMissionTimer, 1000);
    tickMissionTimer();
}

function selectMission(id) {
    const mission = missionData.find(item => item.id === id) || missionData[0];
    if (!mission) return;

    activeMission = mission;
    document.querySelectorAll('.mission-card').forEach(card => {
        card.classList.toggle('active', card.dataset.missionId === mission.id);
    });

    document.getElementById('detailIcon').style.setProperty('--mission-color', mission.color);
    document.getElementById('detailIcon').innerHTML = `<i class="fa-solid ${escapeMissionHtml(mission.icon)}"></i>`;
    document.getElementById('detailTitle').textContent = mission.title;
    document.getElementById('detailCategory').textContent = mission.category;
    document.getElementById('detailDuration').textContent = `${mission.duration}s`;
    document.getElementById('detailIntent').textContent = mission.intent;
    document.getElementById('detailPrompt').textContent = mission.prompt;
    document.getElementById('detailTip').textContent = mission.coach_tip;
    document.getElementById('detailCriteria').innerHTML = mission.success_criteria
        .map(item => `<li><i class="fa-solid fa-check"></i><span>${escapeMissionHtml(item)}</span></li>`)
        .join('');

    resetMissionTimer();
    scoreMission(false);
}

function setMissionVoiceStatus(message, color = 'var(--tx3)') {
    const status = document.getElementById('missionVoiceStatus');
    if (!status) return;
    status.textContent = message;
    status.style.color = color;
}

function missionVoiceText() {
    if (!activeMission) return '';
    return `${activeMission.title}. ${activeMission.prompt}`;
}

function openMissionVoiceModal() {
    if (!activeMission) {
        setMissionGeneratorStatus('Generate or select a mission before using voice practice.', '#f59e0b');
        return;
    }

    document.getElementById('missionVoiceModalTitle').textContent = `${activeMission.title} Voice Practice`;
    document.getElementById('missionVoicePrompt').textContent = activeMission.prompt;
    setMissionVoiceStatus('Listen to the mission, then record your spoken answer.');

    const modalElement = document.getElementById('missionVoiceModal');
    if (window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    } else {
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.removeAttribute('aria-hidden');
    }
}

function speakMissionPrompt() {
    if (!('speechSynthesis' in window)) {
        setMissionVoiceStatus('Text-to-speech is not supported in this browser.', '#f59e0b');
        return;
    }

    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(missionVoiceText());
    utterance.lang = 'en-PH';
    utterance.rate = 0.92;
    utterance.pitch = 1;
    utterance.onstart = () => setMissionVoiceStatus('AI is speaking the mission...');
    utterance.onend = () => setMissionVoiceStatus('Now answer the mission using Start Voice.');
    utterance.onerror = () => setMissionVoiceStatus('Could not speak the mission in this browser.', '#f59e0b');
    window.speechSynthesis.speak(utterance);
}

function updateMissionVoiceTranscript(interim = '') {
    const box = document.getElementById('missionVoiceTranscript');
    const text = [missionVoiceTranscript, interim].filter(Boolean).join(' ').trim();
    box.textContent = text || 'Your spoken answer will appear here...';
}

function startMissionVoice() {
    if (!MissionSpeechRecognition) {
        setMissionVoiceStatus('Voice transcription is not supported in this browser. You can type directly in the transcript box.', '#f59e0b');
        return;
    }

    if (missionRecognition) {
        try { missionRecognition.stop(); } catch (error) {}
    }

    missionRecognition = new MissionSpeechRecognition();
    missionRecognition.lang = 'en-PH';
    missionRecognition.continuous = true;
    missionRecognition.interimResults = true;

    missionRecognition.onstart = () => setMissionVoiceStatus('Listening. Speak your answer clearly...', '#16a34a');
    missionRecognition.onerror = event => {
        const reason = event.error === 'not-allowed'
            ? 'Microphone permission was blocked. Allow microphone access, then try again.'
            : 'Voice transcription stopped. You can try again or type directly.';
        setMissionVoiceStatus(reason, '#ef4444');
    };
    missionRecognition.onend = () => setMissionVoiceStatus('Voice capture stopped. Review or edit the transcript.');
    missionRecognition.onresult = event => {
        let interim = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = event.results[i][0]?.transcript || '';
            if (event.results[i].isFinal) {
                missionVoiceTranscript = `${missionVoiceTranscript} ${transcript}`.trim();
            } else {
                interim += transcript;
            }
        }
        updateMissionVoiceTranscript(interim.trim());
    };

    missionRecognition.start();
}

function stopMissionVoice() {
    if (missionRecognition) {
        try { missionRecognition.stop(); } catch (error) {}
    }
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
}

function clearMissionVoiceTranscript() {
    missionVoiceTranscript = '';
    document.getElementById('missionVoiceTranscript').textContent = 'Your spoken answer will appear here...';
    setMissionVoiceStatus('Transcript cleared. Start voice again when ready.');
}

function useMissionVoiceTranscript() {
    const box = document.getElementById('missionVoiceTranscript');
    const text = box.textContent.trim();
    if (!text || text === 'Your spoken answer will appear here...') {
        setMissionVoiceStatus('Record or type a transcript before using it.', '#f59e0b');
        return;
    }

    document.getElementById('missionAnswer').value = text;
    scoreMission(false);
    setMissionVoiceStatus('Transcript added to your mission answer.', '#16a34a');

    const modalElement = document.getElementById('missionVoiceModal');
    if (window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
    }
}

function missionToneSignal(mission, normalizedText) {
    const intent = String(mission.intent || '').toLowerCase();
    if (intent === 'confident') {
        return hasAny(normalizedText, ['i can', 'i have', 'i handled', 'i led', 'i built', 'i improved', 'my experience']);
    }
    if (intent === 'calm') {
        return hasAny(normalizedText, ['understand', 'appreciate', 'clarify', 'resolve', 'next step', 'will check', 'help']);
    }
    if (intent === 'persuasive') {
        return hasAny(normalizedText, ['because', 'benefit', 'result', 'evidence', 'support', 'improve', 'reduce', 'increase']);
    }
    if (intent === 'accountable') {
        return hasAny(normalizedText, ['learned', 'changed', 'improved', 'now', 'feedback', 'mistake', 'weakness']);
    }
    return normalizedText.length > 0;
}

function scoreMission(showEmptyAlert = true) {
    if (!activeMission) return;

    const answer = document.getElementById('missionAnswer').value.trim();
    const normalized = normalizeMissionText(answer);
    const words = missionWordCount(answer);

    if (!answer && showEmptyAlert) {
        alert('Add an answer before scoring this mission.');
        return;
    }

    let score = 0;
    const feedback = [];

    if (words >= 45 && words <= 150) {
        score += 24;
        feedback.push('Interview use: your answer length is practical for a spoken interview response.');
    } else if (words > 0) {
        score += 12;
        feedback.push(words < 45 ? 'Interview use: add one concrete detail so the answer has enough proof.' : 'Interview use: tighten the answer so it stays clear under time pressure.');
    }

    if (hasAny(normalized, ['because', 'for example', 'example', 'when', 'during', 'project', 'internship', 'school', 'work', 'client'])) {
        score += 22;
        feedback.push('Interview use: evidence is present, which makes the answer easier for an interviewer to trust.');
    } else if (answer) {
        feedback.push('Interview use: add one specific example, project, class, client, or work situation.');
    }

    if (hasAny(normalized, ['result', 'improved', 'reduced', 'increased', 'learned', 'completed', 'solved', 'helped', 'successful'])) {
        score += 20;
        feedback.push('Interview use: the answer includes an outcome or lesson, so it shows growth or impact.');
    } else if (answer) {
        feedback.push('Interview use: close with a result, lesson, or next action.');
    }

    if (missionToneSignal(activeMission, normalized)) {
        score += 20;
        feedback.push(`Interview use: ${activeMission.intent.toLowerCase()} intention is showing in the wording.`);
    } else if (answer) {
        feedback.push(`Interview use: adjust wording so it sounds more ${String(activeMission.intent).toLowerCase()} without inventing facts.`);
    }

    if (hasAny(normalized, ['i will', 'next', 'contribute', 'support', 'help', 'apply', 'continue', 'moving forward'])) {
        score += 14;
        feedback.push('Interview use: the ending gives a forward direction the interviewer can remember.');
    } else if (answer) {
        feedback.push('Interview use: end with what you will do, contribute, or improve next.');
    }

    score = Math.max(0, Math.min(100, score));
    const scoreColor = score >= 80 ? '#22c55e' : (score >= 60 ? '#f59e0b' : '#ef4444');
    const status = score >= 80 ? 'Mission Ready' : (score >= 60 ? 'Almost There' : 'Needs Practice');

    document.getElementById('missionScoreRing').style.setProperty('--score', `${score}%`);
    document.getElementById('missionScoreRing').style.setProperty('--score-color', scoreColor);
    document.getElementById('missionScoreValue').textContent = answer ? score : '--';
    document.getElementById('missionResultStatus').textContent = answer ? status : 'Waiting';
    document.getElementById('missionResultStatus').style.setProperty('--pill-color', answer ? scoreColor : '#64748b');
    document.getElementById('missionResultSummary').textContent = answer
        ? `${activeMission.title} checked ${words} words against interview-ready structure, evidence, result, tone, and next action.`
        : 'Score an answer to see mission-specific feedback.';
    document.getElementById('missionFeedbackList').innerHTML = (answer ? feedback : ['Your result will check interview structure, evidence, role fit, tone, and next action.'])
        .map(item => `<li><i class="fa-solid fa-arrow-right"></i><span>${escapeMissionHtml(item)}</span></li>`)
        .join('');
}

document.addEventListener('DOMContentLoaded', () => {
    renderMissionBoard();
    document.getElementById('scoreMissionBtn').addEventListener('click', () => scoreMission(true));
    document.getElementById('clearMissionBtn').addEventListener('click', () => {
        document.getElementById('missionAnswer').value = '';
        scoreMission(false);
    });
    document.getElementById('missionTimerBtn').addEventListener('click', toggleMissionTimer);
    document.getElementById('voiceMissionBtn').addEventListener('click', openMissionVoiceModal);
    document.getElementById('speakMissionBtn').addEventListener('click', speakMissionPrompt);
    document.getElementById('startMissionVoiceBtn').addEventListener('click', startMissionVoice);
    document.getElementById('stopMissionVoiceBtn').addEventListener('click', stopMissionVoice);
    document.getElementById('clearMissionVoiceBtn').addEventListener('click', clearMissionVoiceTranscript);
    document.getElementById('useMissionVoiceTranscriptBtn').addEventListener('click', useMissionVoiceTranscript);
    document.getElementById('missionVoiceModal').addEventListener('shown.bs.modal', () => {
        document.querySelector('.modal-backdrop:last-of-type')?.classList.add('mission-voice-backdrop');
    });
    document.getElementById('missionVoiceModal').addEventListener('hidden.bs.modal', stopMissionVoice);
    document.getElementById('generateMissionBtn').addEventListener('click', generateMissionTasks);
    document.getElementById('missionGoalInput').addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            generateMissionTasks();
        }
    });
    selectMission(activeMission?.id);
});
</script>
@endsection
