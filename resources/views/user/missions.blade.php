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
    .mission-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: var(--mission-color, #2563eb);
        background: color-mix(in srgb, var(--mission-color, #2563eb) 14%, transparent);
    }
    .mission-card-name {
        color: var(--tx);
        font-size: 0.95rem;
        font-weight: 900;
        line-height: 1.25;
        margin: 0;
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
        .mission-panel-head,
        .mission-detail-head {
            display: grid;
            grid-template-columns: 1fr;
        }
        .mission-actions {
            grid-template-columns: 1fr;
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
                    <div class="mission-kicker">Choose one task, answer it, and measure how ready it sounds.</div>
                </div>
                <span class="mission-pill" style="--pill-color:#16a34a"><i class="fa-solid fa-microphone-lines"></i>{{ $practiceSessionCount }} saved sessions</span>
            </div>

            <div class="mission-grid" id="missionGrid">
                @foreach($missions as $mission)
                    <button type="button" class="mission-card" data-mission-id="{{ $mission->id }}" style="--mission-color: {{ $mission->color }};">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <span class="mission-card-icon"><i class="fa-solid {{ $mission->icon }}"></i></span>
                            <span class="mission-meta">{{ $mission->difficulty }}</span>
                        </div>
                        <div>
                            <h6 class="mission-card-name">{{ $mission->title }}</h6>
                            <p class="mission-card-copy">{{ $mission->prompt }}</p>
                        </div>
                        <div class="mission-card-meta">
                            <span class="mission-meta"><i class="fa-regular fa-clock me-1"></i>{{ $mission->duration }}s</span>
                            <span class="mission-meta"><i class="fa-solid fa-face-smile me-1"></i>{{ $mission->intent }}</span>
                        </div>
                    </button>
                @endforeach
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
                <a href="{{ route('user.drills.voice') }}" class="mission-btn" id="voiceMissionLink"><i class="fa-solid fa-microphone-lines"></i>Practice With Voice</a>
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

<script>
const missionData = @json($missions->values());
const voiceBaseUrl = @json(route('user.drills.voice'));
let activeMission = missionData[0] || null;
let missionTimer = null;
let remainingSeconds = activeMission ? Number(activeMission.duration) || 60 : 60;

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

function missionVoiceUrl(mission) {
    const params = new URLSearchParams({
        mission: mission.id,
        category: mission.category,
        intent: mission.intent,
        prompt: mission.prompt
    });

    return `${voiceBaseUrl}?${params.toString()}`;
}

function formatMissionTime(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
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
    document.getElementById('voiceMissionLink').href = missionVoiceUrl(mission);

    resetMissionTimer();
    scoreMission(false);
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
        feedback.push('Length is practical for a spoken mission answer.');
    } else if (words > 0) {
        score += 12;
        feedback.push(words < 45 ? 'Add more detail so the answer has enough proof.' : 'Tighten the answer so it stays interview-friendly.');
    }

    if (hasAny(normalized, ['because', 'for example', 'example', 'when', 'during', 'project', 'internship', 'school', 'work', 'client'])) {
        score += 22;
        feedback.push('Evidence is present, which makes the answer easier to trust.');
    } else if (answer) {
        feedback.push('Add one specific example, project, class, client, or work situation.');
    }

    if (hasAny(normalized, ['result', 'improved', 'reduced', 'increased', 'learned', 'completed', 'solved', 'helped', 'successful'])) {
        score += 20;
        feedback.push('The answer includes an outcome or lesson.');
    } else if (answer) {
        feedback.push('Close with a result, lesson, or next action.');
    }

    if (missionToneSignal(activeMission, normalized)) {
        score += 20;
        feedback.push(`${activeMission.intent} intention is showing in the wording.`);
    } else if (answer) {
        feedback.push(`Adjust wording so it sounds more ${String(activeMission.intent).toLowerCase()}.`);
    }

    if (hasAny(normalized, ['i will', 'next', 'contribute', 'support', 'help', 'apply', 'continue', 'moving forward'])) {
        score += 14;
        feedback.push('The ending gives a forward direction.');
    } else if (answer) {
        feedback.push('End with what you will do, contribute, or improve next.');
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
        ? `${activeMission.title} checked ${words} words against structure, evidence, result, and intention.`
        : 'Score an answer to see mission-specific feedback.';
    document.getElementById('missionFeedbackList').innerHTML = (answer ? feedback : ['Your result will check structure, evidence, tone fit, and next action.'])
        .map(item => `<li><i class="fa-solid fa-arrow-right"></i><span>${escapeMissionHtml(item)}</span></li>`)
        .join('');
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.mission-card').forEach(card => {
        card.addEventListener('click', () => selectMission(card.dataset.missionId));
    });
    document.getElementById('scoreMissionBtn').addEventListener('click', () => scoreMission(true));
    document.getElementById('clearMissionBtn').addEventListener('click', () => {
        document.getElementById('missionAnswer').value = '';
        scoreMission(false);
    });
    document.getElementById('missionTimerBtn').addEventListener('click', toggleMissionTimer);
    selectMission(activeMission?.id);
});
</script>
@endsection
