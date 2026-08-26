@extends('desktop.layouts.app')
@section('title', 'Real-Life Mission Mode')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/missions.css?v=1') }}" data-page-style="user-missions">
<link rel="stylesheet" href="{{ asset('css/desktop/user/missions-2.css?v=2') }}" data-page-style="user-missions-2">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')

<div class="db-section active mission-page" id="mission-mode-page">
    <div class="sr-page-hero mission-progress-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="mission-hero-icon"><i class="fa-solid fa-shield-check"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 4v6c0 4.4-2.8 7.8-7 8-4.2-.2-7-3.6-7-8V7l7-4Z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M9 12l2 2 4-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Real-Life Mission Mode
                    </h4>
                    <p class="sr-page-hero-subtitle">Complete practical speaking tasks for interviews, customer calls, panels, and team conversations.</p>
                </div>
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
                <div class="mission-board-title">
                    <span class="mission-board-icon"><i class="fa-solid fa-route"></i></span>
                    <div>
                        <h5 class="mission-title">Mission Board</h5>
                        <div class="mission-kicker">Generate tasks from what you want to practice, then measure how ready your answer sounds.</div>
                    </div>
                </div>
                <span class="mission-pill" style="--pill-color:#16a34a"><i class="fa-solid fa-microphone-lines"></i>{{ $practiceSessionCount }} saved sessions</span>
            </div>

            <div class="mission-generator">
                <input type="text" id="missionGoalInput" maxlength="240" placeholder="Example: BPO final interview, scholarship panel, IT debugging question...">
                <button type="button" class="mission-btn mission-btn-primary" id="generateMissionBtn" data-generate-url="{{ route('user.missions.generate') }}" style="min-height:42px;"><i class="fa-solid fa-wand-magic-sparkles"></i>Generate Task</button>
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
const missionGenerateUrl = document.getElementById('generateMissionBtn')?.dataset.generateUrl || @json(route('user.missions.generate'));
let activeMission = missionData[0] || null;
let missionTimer = null;
let remainingSeconds = activeMission ? Number(activeMission.duration) || 60 : 60;
const MissionSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const missionSpeechLocale = document.documentElement.dataset.speechLocale || navigator.language || 'en-US';
const missionTranscriptPlaceholder = 'Your spoken answer will appear here...';
const missionDuplicateSafeWordSet = new Set([
    'i', "i'm", 'the', 'a', 'an', 'and', 'to', 'of', 'for', 'in', 'on', 'it', 'is', 'was',
    'were', 'am', 'are', 'my', 'we', 'you', 'that', 'this', 'with', 'um', 'uh', 'like'
]);
let missionRecognition = null;
let missionRecognitionActive = false;
let missionShouldAutoRestart = false;
let missionRecognitionToken = 0;
let missionVoiceTranscript = '';
let missionVoiceInterim = '';
let missionLastCommittedSpeech = '';
let missionLastCommittedAt = 0;

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

function cleanMissionTranscriptText(value) {
    return String(value || '').replace(/\s+/gu, ' ').trim();
}

function normalizeMissionTranscriptForMatch(value) {
    return cleanMissionTranscriptText(value)
        .toLocaleLowerCase(missionSpeechLocale)
        .replace(/[^\p{L}\p{N}'\u2019\s]/gu, '')
        .replace(/\s+/gu, ' ')
        .trim();
}

function missionWordsForTranscript(value) {
    return cleanMissionTranscriptText(value).split(/\s+/u).filter(Boolean);
}

function appendMissionTranscriptWithoutOverlap(existing, addition) {
    const existingClean = cleanMissionTranscriptText(existing);
    const additionClean = cleanMissionTranscriptText(addition);
    if (!existingClean) return additionClean;
    if (!additionClean) return existingClean;

    const existingWords = missionWordsForTranscript(existingClean);
    const additionWords = missionWordsForTranscript(additionClean);
    const existingNormalized = existingWords.map(normalizeMissionTranscriptForMatch);
    const additionNormalized = additionWords.map(normalizeMissionTranscriptForMatch);
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
    return cleanMissionTranscriptText(existingClean + (remainder ? ' ' + remainder : ''));
}

function shouldCollapseMissionDuplicateWindow(size, normalizedPhrase) {
    if (!normalizedPhrase) return false;
    if (size >= 2) return true;
    return Array.from(normalizedPhrase).length > 2 || missionDuplicateSafeWordSet.has(normalizedPhrase);
}

function collapseRepeatedMissionSpeech(text) {
    const words = missionWordsForTranscript(text);
    if (words.length < 2) return cleanMissionTranscriptText(text);

    let index = 0;
    while (index < words.length) {
        let collapsed = false;
        const maxWindow = Math.min(12, Math.floor((words.length - index) / 2));

        for (let size = maxWindow; size >= 1; size--) {
            const first = words.slice(index, index + size).map(normalizeMissionTranscriptForMatch).join(' ');
            const second = words.slice(index + size, index + (size * 2)).map(normalizeMissionTranscriptForMatch).join(' ');

            if (first && first === second && shouldCollapseMissionDuplicateWindow(size, first)) {
                words.splice(index + size, size);
                index = Math.max(0, index - size);
                collapsed = true;
                break;
            }
        }

        if (!collapsed) index++;
    }

    return cleanMissionTranscriptText(words.join(' '));
}

function mergeMissionTranscriptParts(...parts) {
    let merged = '';
    parts.forEach(part => {
        const clean = cleanMissionTranscriptText(part);
        if (clean) merged = appendMissionTranscriptWithoutOverlap(merged, clean);
    });
    return collapseRepeatedMissionSpeech(merged);
}

function missionTranscriptEditorText() {
    const box = document.getElementById('missionVoiceTranscript');
    const text = cleanMissionTranscriptText(box ? (box.innerText || box.textContent || '') : '');
    return text === missionTranscriptPlaceholder ? '' : text;
}

function missionVoiceHasTranscript() {
    return Boolean(missionTranscriptEditorText() || missionVoiceTranscript || missionVoiceInterim);
}

function setMissionVoiceButtonStates() {
    const hasTranscript = missionVoiceHasTranscript();
    const isListening = missionRecognitionActive || missionShouldAutoRestart;

    document.getElementById('speakMissionBtn')?.toggleAttribute('disabled', !activeMission);
    document.getElementById('startMissionVoiceBtn')?.toggleAttribute('disabled', !activeMission || isListening);
    document.getElementById('stopMissionVoiceBtn')?.toggleAttribute('disabled', !isListening);
    document.getElementById('clearMissionVoiceBtn')?.toggleAttribute('disabled', !hasTranscript);
    document.getElementById('useMissionVoiceTranscriptBtn')?.toggleAttribute('disabled', !hasTranscript);
}

function bestMissionSpeechAlternative(result) {
    let best = result[0] || null;
    for (let i = 1; i < result.length; i++) {
        if ((result[i].confidence || 0) > (best?.confidence || 0)) best = result[i];
    }
    return best ? best.transcript : '';
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

function resetMissionResult(summary = 'Score an answer to see mission-specific feedback.') {
    document.getElementById('missionScoreRing').style.setProperty('--score', '0%');
    document.getElementById('missionScoreRing').style.setProperty('--score-color', '#22c55e');
    document.getElementById('missionScoreValue').textContent = '--';
    document.getElementById('missionResultStatus').textContent = 'Waiting';
    document.getElementById('missionResultStatus').style.setProperty('--pill-color', '#64748b');
    document.getElementById('missionResultSummary').textContent = summary;
    document.getElementById('missionFeedbackList').innerHTML = '<li><i class="fa-solid fa-circle-info"></i><span>Your result will check structure, evidence, tone fit, and next action.</span></li>';
}

function setMissionControlsEnabled(enabled) {
    ['missionTimerBtn', 'scoreMissionBtn', 'voiceMissionBtn', 'clearMissionBtn'].forEach(id => {
        document.getElementById(id)?.toggleAttribute('disabled', !enabled);
    });

    const answer = document.getElementById('missionAnswer');
    if (answer) {
        answer.toggleAttribute('disabled', !enabled);
        answer.placeholder = enabled
            ? 'Type or paste your spoken answer here...'
            : 'Generate or select a mission first...';
    }
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
    if (!mission) {
        activeMission = null;
        document.querySelectorAll('.mission-card').forEach(card => card.classList.remove('active'));
        document.getElementById('detailIcon').style.removeProperty('--mission-color');
        document.getElementById('detailIcon').innerHTML = '<i class="fa-solid fa-route"></i>';
        document.getElementById('detailTitle').textContent = 'No mission selected';
        document.getElementById('detailCategory').textContent = 'Scenario';
        document.getElementById('detailDuration').textContent = '60s';
        document.getElementById('detailIntent').textContent = 'Ready';
        document.getElementById('detailPrompt').textContent = 'Generate tasks or select a mission card to begin.';
        document.getElementById('detailTip').textContent = 'Mission tools unlock once a mission is selected.';
        document.getElementById('detailCriteria').innerHTML = '<li><i class="fa-solid fa-circle-info"></i><span>Choose a mission to enable the timer, scoring, and voice practice.</span></li>';
        setMissionControlsEnabled(false);
        resetMissionTimer();
        resetMissionResult('Generate or select a mission before scoring an answer.');
        setMissionVoiceButtonStates();
        return;
    }

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
    document.getElementById('detailCriteria').innerHTML = (Array.isArray(mission.success_criteria) ? mission.success_criteria : [])
        .map(item => `<li><i class="fa-solid fa-check"></i><span>${escapeMissionHtml(item)}</span></li>`)
        .join('');

    setMissionControlsEnabled(true);
    resetMissionTimer();
    scoreMission(false);
    setMissionVoiceButtonStates();
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
        setMissionVoiceButtonStates();
        return;
    }

    document.getElementById('missionVoiceModalTitle').textContent = `${activeMission.title} Voice Practice`;
    document.getElementById('missionVoicePrompt').textContent = activeMission.prompt;
    setMissionVoiceStatus('Listen to the mission, then record your spoken answer.');
    updateMissionVoiceTranscript();
    setMissionVoiceButtonStates();

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

function commitMissionSpeechSegment(segment) {
    const cleanSegment = collapseRepeatedMissionSpeech(cleanMissionTranscriptText(segment));
    if (!cleanSegment) return;

    const normalized = normalizeMissionTranscriptForMatch(cleanSegment);
    const now = Date.now();
    if (normalized && normalized === missionLastCommittedSpeech && (now - missionLastCommittedAt) < 5000) return;

    missionVoiceTranscript = collapseRepeatedMissionSpeech(
        appendMissionTranscriptWithoutOverlap(missionVoiceTranscript, cleanSegment)
    );
    missionLastCommittedSpeech = normalized;
    missionLastCommittedAt = now;
}

function updateMissionVoiceTranscript() {
    const box = document.getElementById('missionVoiceTranscript');
    const text = mergeMissionTranscriptParts(missionVoiceTranscript, missionVoiceInterim);
    box.textContent = text || missionTranscriptPlaceholder;
    setMissionVoiceButtonStates();
}

function finalizeMissionVoiceInterim() {
    if (!missionVoiceInterim) return;
    commitMissionSpeechSegment(missionVoiceInterim);
    missionVoiceInterim = '';
    updateMissionVoiceTranscript();
}

function startMissionVoiceEngine(token = missionRecognitionToken) {
    if (token !== missionRecognitionToken) return false;
    if (!missionRecognition || missionRecognitionActive || !missionShouldAutoRestart) return false;

    try {
        missionRecognition.start();
        missionRecognitionActive = true;
        return true;
    } catch (error) {
        if (!error || error.name !== 'InvalidStateError') {
            console.error('Mission voice recognition failed to start:', error);
            setMissionVoiceStatus('Voice transcription could not start. You can type directly in the transcript box.', '#ef4444');
        }
        missionShouldAutoRestart = false;
        return false;
    }
}

function startMissionVoice() {
    if (!MissionSpeechRecognition) {
        setMissionVoiceStatus('Voice transcription is not supported in this browser. You can type directly in the transcript box.', '#f59e0b');
        setMissionVoiceButtonStates();
        return;
    }

    missionRecognitionToken++;
    const token = missionRecognitionToken;

    if (missionRecognition) {
        missionShouldAutoRestart = false;
        try { missionRecognition.stop(); } catch (error) {}
    }
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();

    missionVoiceTranscript = collapseRepeatedMissionSpeech(missionTranscriptEditorText());
    missionVoiceInterim = '';
    missionLastCommittedSpeech = '';
    missionLastCommittedAt = 0;
    missionShouldAutoRestart = true;
    setMissionVoiceButtonStates();

    missionRecognition = new MissionSpeechRecognition();
    missionRecognition.lang = missionSpeechLocale;
    missionRecognition.continuous = true;
    missionRecognition.interimResults = true;
    missionRecognition.maxAlternatives = 3;

    missionRecognition.onstart = () => {
        if (token !== missionRecognitionToken) return;
        missionRecognitionActive = true;
        setMissionVoiceStatus('Listening. Speak your answer clearly...', '#16a34a');
        setMissionVoiceButtonStates();
    };
    missionRecognition.onerror = event => {
        if (token !== missionRecognitionToken) return;
        const reason = event.error === 'not-allowed'
            ? 'Microphone permission was blocked. Allow microphone access, then try again.'
            : 'Voice transcription stopped. You can try again or type directly.';
        if (['not-allowed', 'service-not-allowed', 'audio-capture'].includes(event.error)) {
            missionShouldAutoRestart = false;
        }
        setMissionVoiceStatus(reason, '#ef4444');
        setMissionVoiceButtonStates();
    };
    missionRecognition.onend = () => {
        if (token !== missionRecognitionToken) return;
        missionRecognitionActive = false;
        if (missionShouldAutoRestart) {
            setMissionVoiceStatus('Reconnecting voice transcription...', '#f59e0b');
            setMissionVoiceButtonStates();
            setTimeout(() => startMissionVoiceEngine(token), 300);
            return;
        }

        setMissionVoiceStatus('Voice capture stopped. Review or edit the transcript.');
        setMissionVoiceButtonStates();
    };
    missionRecognition.onresult = event => {
        if (token !== missionRecognitionToken) return;
        const interimParts = [];
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = bestMissionSpeechAlternative(event.results[i]);
            if (!transcript) continue;

            if (event.results[i].isFinal) {
                commitMissionSpeechSegment(transcript);
            } else {
                interimParts.push(transcript);
            }
        }

        missionVoiceInterim = cleanMissionTranscriptText(interimParts.join(' '));
        updateMissionVoiceTranscript();
    };

    startMissionVoiceEngine(token);
}

function stopMissionVoice() {
    missionShouldAutoRestart = false;
    finalizeMissionVoiceInterim();
    missionRecognitionToken++;
    missionRecognitionActive = false;
    if (missionRecognition) {
        try { missionRecognition.stop(); } catch (error) {}
    }
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    setMissionVoiceStatus('Voice capture stopped. Review or edit the transcript.');
    setMissionVoiceButtonStates();
}

function clearMissionVoiceTranscript() {
    missionVoiceTranscript = '';
    missionVoiceInterim = '';
    missionLastCommittedSpeech = '';
    missionLastCommittedAt = 0;
    document.getElementById('missionVoiceTranscript').textContent = missionTranscriptPlaceholder;
    setMissionVoiceStatus('Transcript cleared. Start voice again when ready.');
    setMissionVoiceButtonStates();
}

function useMissionVoiceTranscript() {
    finalizeMissionVoiceInterim();
    const box = document.getElementById('missionVoiceTranscript');
    const text = collapseRepeatedMissionSpeech(missionTranscriptEditorText());
    if (!text) {
        setMissionVoiceStatus('Record or type a transcript before using it.', '#f59e0b');
        return;
    }

    missionVoiceTranscript = text;
    missionVoiceInterim = '';
    box.textContent = text;
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
    if (!activeMission) {
        resetMissionResult('Generate or select a mission before scoring an answer.');
        if (showEmptyAlert) {
            setMissionGeneratorStatus('Generate or select a mission before scoring.', '#f59e0b');
        }
        return;
    }

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
        setMissionVoiceButtonStates();
    });
    document.getElementById('missionVoiceModal').addEventListener('hidden.bs.modal', stopMissionVoice);
    document.getElementById('generateMissionBtn').addEventListener('click', generateMissionTasks);
    document.getElementById('missionGoalInput').addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            generateMissionTasks();
        }
    });
    const missionTranscriptBox = document.getElementById('missionVoiceTranscript');
    missionTranscriptBox.addEventListener('focus', () => {
        if (!missionTranscriptEditorText()) missionTranscriptBox.textContent = '';
    });
    missionTranscriptBox.addEventListener('input', () => {
        missionVoiceTranscript = collapseRepeatedMissionSpeech(missionTranscriptEditorText());
        missionVoiceInterim = '';
        setMissionVoiceButtonStates();
    });
    missionTranscriptBox.addEventListener('blur', () => {
        missionVoiceTranscript = collapseRepeatedMissionSpeech(missionTranscriptEditorText());
        missionVoiceInterim = '';
        updateMissionVoiceTranscript();
    });
    selectMission(activeMission?.id);
    setMissionVoiceButtonStates();
});
</script>
@push('scripts')
<script>
    if (typeof window.createSpeakReadyTour === 'function') {
        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_user_missions',
            serverDetectedMobile: false,
            stepsDesktop: [
                { element: '#missionGrid', popover: { title: 'Mission Board', description: 'Pick or generate practical speaking missions for interviews and workplace conversations.', side: 'bottom', align: 'start' }},
                { element: '#missionTool', popover: { title: 'Mission Tools', description: 'Use the prompt, timer, answer box, scoring, and voice practice for the selected mission.', side: 'left', align: 'start' }},
                { element: '#missionResultPanel', popover: { title: 'Mission Result', description: 'Score your answer to get structure, evidence, tone, and next-action feedback.', side: 'top', align: 'start' }}
            ],
            stepsMobile: [
                { element: '#missionGrid', popover: { title: 'Mission Board', description: 'Pick or generate practical speaking missions for interviews and workplace conversations.', side: 'bottom', align: 'start' }},
                { element: '#missionTool', popover: { title: 'Mission Tools', description: 'Use the prompt, timer, answer box, scoring, and voice practice for the selected mission.', side: 'top', align: 'start' }},
                { element: '#missionResultPanel', popover: { title: 'Mission Result', description: 'Score your answer to get structure, evidence, tone, and next-action feedback.', side: 'top', align: 'start' }}
            ],
            autoStart: false,
            autoStartDelay: 500,
        });
    }
</script>
@endpush
@endsection
