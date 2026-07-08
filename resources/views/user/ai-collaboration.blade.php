@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
@php
    $conversation = $currentSession && is_array($currentSession->conversation_log) ? $currentSession->conversation_log : [];
    $feedback = $currentSession && is_array($currentSession->ai_feedback) ? $currentSession->ai_feedback : [];
    $isCompleted = $currentSession && $currentSession->status === 'completed';
    $score = $currentSession ? (int) ($currentSession->overall_score ?? 0) : 0;
    $scoreTone = $score >= 80 ? '#22c55e' : ($score >= 60 ? '#f59e0b' : '#ef4444');
@endphp

<style>
    .ac-page {
        display: flex;
        flex-direction: column;
        gap: 20px;
        width: 100%;
        max-width: 100%;
        padding-bottom: 28px !important;
        overflow-x: hidden;
    }

    .ac-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
        background:
            linear-gradient(135deg, rgba(59, 130, 246, 0.14), rgba(34, 197, 94, 0.06)),
            var(--sf);
        border: 1px solid rgba(59, 130, 246, 0.22);
        border-radius: 18px;
        padding: 22px;
        overflow: hidden;
        min-width: 0;
    }

    .ac-title {
        margin: 0;
        color: var(--tx);
        font-size: clamp(1.15rem, 2vw, 1.6rem);
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .ac-subtitle {
        margin: 7px 0 0;
        color: var(--tx2);
        max-width: 760px;
        font-size: 0.92rem;
        overflow-wrap: anywhere;
    }

    .ac-stat-row {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        width: min(520px, 100%);
        min-width: 0;
    }

    .ac-stat {
        background: var(--sf2, var(--bg3));
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 12px;
        min-width: 0;
    }

    .ac-stat strong {
        display: block;
        color: var(--tx);
        font-size: 1.25rem;
        line-height: 1;
    }

    .ac-stat span {
        color: var(--tx3);
        font-size: 0.7rem;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .ac-grid {
        display: grid;
        grid-template-columns: 330px minmax(0, 1fr);
        gap: 20px;
        align-items: start;
        min-width: 0;
    }

    .ac-panel {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 18px;
        box-shadow: var(--shadow-soft, 0 10px 28px rgba(0,0,0,.12));
        overflow: hidden;
        min-width: 0;
    }

    .ac-panel-pad {
        padding: 18px;
        min-width: 0;
    }

    .ac-panel-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 14px;
        color: var(--tx);
        font-size: 0.94rem;
        font-weight: 900;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .ac-form-grid {
        display: grid;
        gap: 12px;
        min-width: 0;
    }

    .ac-label {
        color: var(--tx3);
        font-size: 0.72rem;
        font-weight: 800;
        margin-bottom: 6px;
        text-transform: uppercase;
    }

    .ac-input,
    .ac-select,
    .ac-textarea {
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        border: 1px solid var(--bd);
        border-radius: 12px;
        background: var(--bg3);
        color: var(--tx);
        padding: 10px 12px;
        font-size: 0.88rem;
        outline: none;
    }

    .ac-textarea {
        min-height: 120px;
        resize: vertical;
        line-height: 1.5;
    }

    .ac-input:focus,
    .ac-select:focus,
    .ac-textarea:focus {
        border-color: rgba(59, 130, 246, 0.7);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.14);
    }

    .ac-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 40px;
        border: 1px solid var(--bd2);
        border-radius: 12px;
        background: transparent;
        color: var(--tx);
        padding: 9px 14px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        line-height: 1.2;
        text-align: center;
        overflow-wrap: anywhere;
    }

    .ac-btn-primary {
        border-color: transparent;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        color: #fff;
    }

    .ac-btn-success {
        border-color: transparent;
        background: linear-gradient(135deg, #16a34a, #22c55e);
        color: #fff;
    }

    .ac-history {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-height: 420px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .ac-history-item {
        display: flex;
        gap: 10px;
        align-items: center;
        padding: 10px;
        border-radius: 12px;
        border: 1px solid var(--bd);
        background: var(--sf2, var(--bg3));
        color: var(--tx2);
        text-decoration: none;
        min-width: 0;
    }

    .ac-history-item.active {
        border-color: rgba(59, 130, 246, 0.5);
        background: rgba(59, 130, 246, 0.1);
        color: var(--tx);
    }

    .ac-history-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(59, 130, 246, 0.13);
        color: #60a5fa;
        flex: 0 0 auto;
    }

    .ac-history-name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .ac-history-meta {
        color: var(--tx3);
        font-size: 0.7rem;
        overflow-wrap: anywhere;
    }

    .ac-workspace {
        display: grid;
        gap: 18px;
        min-width: 0;
    }

    .ac-scenario-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 14px;
        min-width: 0;
    }

    .ac-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 0.72rem;
        font-weight: 900;
        white-space: normal;
        line-height: 1.2;
        max-width: 100%;
        min-width: 0;
        overflow-wrap: anywhere;
        background: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.22);
    }

    .ac-score-ring {
        width: 78px;
        height: 78px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            radial-gradient(circle at center, var(--sf) 58%, transparent 60%),
            conic-gradient(var(--score-color) var(--score), var(--bg3) 0);
        color: var(--tx);
        font-weight: 900;
        flex: 0 0 auto;
    }

    .ac-copy {
        color: var(--tx2);
        font-size: 0.9rem;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .ac-source {
        background: var(--bg3);
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 14px;
        color: var(--tx2);
        font-size: 0.86rem;
        line-height: 1.65;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .ac-chat {
        height: 390px;
        overflow-y: auto;
        padding: 16px;
        border: 1px solid var(--bd);
        background: var(--bg3);
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-width: 0;
    }

    .ac-message {
        display: flex;
        gap: 10px;
        max-width: 86%;
        min-width: 0;
    }

    .ac-message.user {
        margin-left: auto;
        flex-direction: row-reverse;
    }

    .ac-avatar {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: rgba(59, 130, 246, 0.14);
        color: #60a5fa;
    }

    .ac-message.user .ac-avatar {
        background: rgba(34, 197, 94, 0.14);
        color: #22c55e;
    }

    .ac-bubble {
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 11px 13px;
        color: var(--tx);
        background: var(--sf);
        font-size: 0.87rem;
        line-height: 1.55;
        white-space: pre-wrap;
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .ac-message.user .ac-bubble {
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        color: #fff;
        border-color: transparent;
    }

    .ac-chat-form {
        display: flex;
        gap: 10px;
        margin-top: 12px;
        min-width: 0;
    }

    .ac-chat-form .ac-textarea {
        flex: 1 1 auto;
        min-height: 48px;
        max-height: 120px;
    }

    .ac-score-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
        gap: 10px;
        margin-top: 12px;
    }

    .ac-score-card {
        background: var(--bg3);
        border: 1px solid var(--bd);
        border-radius: 13px;
        padding: 12px;
        min-width: 0;
    }

    .ac-score-card strong {
        display: block;
        color: var(--tx);
        font-size: 1.05rem;
    }

    .ac-score-card span {
        color: var(--tx3);
        font-size: 0.68rem;
        font-weight: 800;
    }

    .ac-feedback-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }

    .ac-feedback-box {
        border: 1px solid var(--bd);
        border-radius: 14px;
        background: var(--bg3);
        padding: 14px;
        color: var(--tx2);
        line-height: 1.55;
        font-size: 0.86rem;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .ac-feedback-box strong {
        display: block;
        color: var(--tx);
        margin-bottom: 6px;
    }

    .ac-empty {
        min-height: 360px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--tx3);
    }

    .ac-empty i {
        display: block;
        color: #60a5fa;
        font-size: 2rem;
        margin-bottom: 10px;
    }

    @media (max-width: 1100px) {
        .ac-hero,
        .ac-grid {
            grid-template-columns: 1fr;
        }

        .ac-stat-row {
            width: 100%;
        }
    }

    @media (max-width: 767px) {
        .ac-page {
            gap: 14px;
            padding-bottom: 12px !important;
        }

        .ac-hero,
        .ac-panel-pad {
            padding: 14px;
            border-radius: 16px;
        }

        .ac-stat-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .ac-stat {
            padding: 10px;
        }

        .ac-chat {
            height: min(330px, 42dvh);
            min-height: 240px;
            padding: 12px;
        }

        .ac-message {
            max-width: 100%;
        }

        .ac-avatar {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            font-size: 0.78rem;
        }

        .ac-bubble {
            padding: 10px 11px;
            font-size: 0.82rem;
        }

        .ac-chat-form {
            flex-direction: column;
        }

        .ac-chat-form .ac-btn {
            width: 100%;
        }

        .ac-grid-has-session > .ac-workspace {
            order: 1;
        }

        .ac-grid-has-session > aside.ac-panel {
            order: 2;
        }

        .ac-history {
            max-height: 260px;
        }
    }

    @media (max-width: 420px) {
        .ac-scenario-head {
            flex-direction: column;
        }

        .ac-title {
            font-size: 1.08rem;
        }

        .ac-subtitle,
        .ac-copy {
            font-size: 0.84rem;
        }

        .ac-source,
        .ac-feedback-box {
            font-size: 0.8rem;
            padding: 12px;
        }

        .ac-score-grid,
        .ac-feedback-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="db-section active ac-page">
    <section class="ac-hero">
        <div>
            <h1 class="ac-title"><i class="fa-solid fa-wand-magic-sparkles me-2" style="color:#60a5fa"></i>AI Collaboration</h1>
            <p class="ac-subtitle">Practice solving work problems with AI while proving judgment, structure, verification, and communication.</p>
        </div>
        <div class="ac-stat-row">
            <div class="ac-stat">
                <strong>{{ $collaborationStats->completed }}</strong>
                <span>Completed</span>
            </div>
            <div class="ac-stat">
                <strong>{{ $collaborationStats->average }}%</strong>
                <span>Average</span>
            </div>
            <div class="ac-stat">
                <strong>{{ $collaborationStats->best }}%</strong>
                <span>Best</span>
            </div>
            <div class="ac-stat">
                <strong>{{ $collaborationStats->active }}</strong>
                <span>Active</span>
            </div>
        </div>
    </section>

    <div class="ac-grid {{ $currentSession ? 'ac-grid-has-session' : 'ac-grid-empty' }}">
        <aside class="ac-panel">
            <div class="ac-panel-pad">
                <h2 class="ac-panel-title"><i class="fa-solid fa-plus" style="color:#22c55e"></i>New Simulation</h2>
                <form action="{{ route('user.ai-collaboration.start') }}" method="POST" class="ac-form-grid">
                    @csrf
                    <div>
                        <div class="ac-label">Target role</div>
                        <input class="ac-input" name="role" value="{{ old('role', Auth::user()->target_position ?? 'Business Analyst') }}" required maxlength="120">
                        @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <div class="ac-label">Industry</div>
                        <input class="ac-input" name="industry" value="{{ old('industry', 'General business') }}" maxlength="120">
                    </div>
                    <div>
                        <div class="ac-label">Mission</div>
                        <select class="ac-select" name="scenario_type" required>
                            <option value="decision_brief">Decision Brief</option>
                            <option value="customer_insight">Customer Insight</option>
                            <option value="process_improvement">Process Improvement</option>
                            <option value="product_strategy">Product Strategy</option>
                            <option value="hiring_screen">Hiring Judgment</option>
                        </select>
                    </div>
                    <div>
                        <div class="ac-label">Difficulty</div>
                        <select class="ac-select" name="difficulty" required>
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                    <button type="submit" class="ac-btn ac-btn-primary w-100">
                        <i class="fa-solid fa-play"></i> Start Simulation
                    </button>
                </form>
            </div>

            <div class="ac-panel-pad" style="border-top:1px solid var(--bd)">
                <h2 class="ac-panel-title"><i class="fa-solid fa-clock-rotate-left" style="color:#f59e0b"></i>History</h2>
                <div class="ac-history">
                    @forelse($sessions as $session)
                        <a href="{{ route('user.ai-collaboration', ['session' => $session->id]) }}"
                           class="ac-history-item {{ $currentSession && $currentSession->id === $session->id ? 'active' : '' }}">
                            <div class="ac-history-icon">
                                <i class="fa-solid {{ $session->status === 'completed' ? 'fa-circle-check' : 'fa-hourglass-half' }}"></i>
                            </div>
                            <div style="min-width:0;flex:1">
                                <div class="ac-history-name">{{ $session->title }}</div>
                                <div class="ac-history-meta">
                                    {{ ucfirst($session->difficulty) }} &middot;
                                    {{ $session->status === 'completed' ? $session->overall_score . '%' : 'In progress' }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="ac-source">No simulations yet.</div>
                    @endforelse
                </div>
            </div>
        </aside>

        <main class="ac-workspace">
            @if(!$currentSession)
                <section class="ac-panel ac-empty">
                    <div>
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <div style="font-weight:900;color:var(--tx)">Start your first AI collaboration simulation.</div>
                        <div style="font-size:0.86rem;margin-top:4px">Your missions and reports will appear here.</div>
                    </div>
                </section>
            @else
                <section class="ac-panel">
                    <div class="ac-panel-pad">
                        <div class="ac-scenario-head">
                            <div>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="ac-badge"><i class="fa-solid fa-briefcase"></i>{{ $currentSession->role }}</span>
                                    <span class="ac-badge"><i class="fa-solid fa-layer-group"></i>{{ ucfirst($currentSession->difficulty) }}</span>
                                    <span class="ac-badge"><i class="fa-solid {{ $isCompleted ? 'fa-circle-check' : 'fa-hourglass-half' }}"></i>{{ $isCompleted ? 'Completed' : 'In Progress' }}</span>
                                </div>
                                <h2 class="ac-panel-title" style="font-size:1.15rem;margin-bottom:6px">{{ $currentSession->title }}</h2>
                                <div class="ac-copy">{{ $currentSession->industry }}</div>
                            </div>
                            @if($isCompleted)
                                <div class="ac-score-ring" style="--score: {{ $score }}%; --score-color: {{ $scoreTone }};">
                                    {{ $score }}%
                                </div>
                            @endif
                        </div>

                        <div class="ac-copy">{!! nl2br(e($currentSession->scenario_brief)) !!}</div>

                        <div class="row g-3 mt-1">
                            <div class="col-lg-7">
                                <div class="ac-label">Source material</div>
                                <div class="ac-source">{!! nl2br(e($currentSession->source_material)) !!}</div>
                            </div>
                            <div class="col-lg-5">
                                <div class="ac-label">Expected output</div>
                                <div class="ac-source">{!! nl2br(e($currentSession->expected_output)) !!}</div>
                            </div>
                        </div>
                    </div>
                </section>

                @if($isCompleted)
                    <section class="ac-panel">
                        <div class="ac-panel-pad">
                            <h2 class="ac-panel-title"><i class="fa-solid fa-chart-simple" style="color:#22c55e"></i>Collaboration Report</h2>
                            <div class="ac-score-grid">
                                <div class="ac-score-card"><strong>{{ $currentSession->prompt_quality_score }}%</strong><span>Prompt Quality</span></div>
                                <div class="ac-score-card"><strong>{{ $currentSession->critical_thinking_score }}%</strong><span>Judgment</span></div>
                                <div class="ac-score-card"><strong>{{ $currentSession->verification_score }}%</strong><span>Verification</span></div>
                                <div class="ac-score-card"><strong>{{ $currentSession->structure_score }}%</strong><span>Structure</span></div>
                                <div class="ac-score-card"><strong>{{ $currentSession->communication_score }}%</strong><span>Communication</span></div>
                            </div>

                            <div class="ac-feedback-grid">
                                <div class="ac-feedback-box"><strong>Strengths</strong>{{ $feedback['strengths'] ?? 'No strengths available.' }}</div>
                                <div class="ac-feedback-box"><strong>Improvements</strong>{{ $feedback['improvements'] ?? 'No improvements available.' }}</div>
                                <div class="ac-feedback-box"><strong>Next Drill</strong>{{ $feedback['next_drill'] ?? 'Run another simulation with more verification prompts.' }}</div>
                                <div class="ac-feedback-box"><strong>Evidence Notes</strong>{{ $feedback['evidence_notes'] ?? 'No evidence notes available.' }}</div>
                            </div>

                            <div class="ac-feedback-box mt-3">
                                <strong>Ethics Note</strong>{{ $feedback['ethics_note'] ?? 'Use AI to prepare and sharpen judgment before interviews, not to secretly generate live answers.' }}
                            </div>
                        </div>
                    </section>

                    <section class="ac-panel">
                        <div class="ac-panel-pad">
                            <h2 class="ac-panel-title"><i class="fa-solid fa-file-lines" style="color:#60a5fa"></i>Final Recommendation</h2>
                            <div class="ac-source">{!! nl2br(e($currentSession->final_recommendation)) !!}</div>
                            @if($currentSession->candidate_reflection)
                                <div class="ac-label mt-3">Reflection</div>
                                <div class="ac-source">{!! nl2br(e($currentSession->candidate_reflection)) !!}</div>
                            @endif
                        </div>
                    </section>
                @else
                    <section class="ac-panel">
                        <div class="ac-panel-pad">
                            <h2 class="ac-panel-title"><i class="fa-solid fa-robot" style="color:#60a5fa"></i>AI Work Assistant</h2>
                            <div class="ac-chat" id="acChat">
                                @forelse($conversation as $message)
                                    @if(in_array($message['role'] ?? '', ['user', 'ai'], true))
                                        <div class="ac-message {{ ($message['role'] ?? '') === 'user' ? 'user' : 'ai' }}">
                                            <div class="ac-avatar">
                                                <i class="fa-solid {{ ($message['role'] ?? '') === 'user' ? 'fa-user' : 'fa-robot' }}"></i>
                                            </div>
                                            <div class="ac-bubble">{{ $message['content'] ?? '' }}</div>
                                        </div>
                                    @endif
                                @empty
                                    <div class="ac-message ai">
                                        <div class="ac-avatar"><i class="fa-solid fa-robot"></i></div>
                                        <div class="ac-bubble">Ready. Share what you want to inspect first: options, risks, assumptions, missing data, or metrics.</div>
                                    </div>
                                @endforelse
                            </div>
                            <div class="ac-chat-form">
                                <textarea class="ac-textarea" id="acMessage" placeholder="Ask for options, risks, assumptions, metrics, or a challenge to your thinking."></textarea>
                                <button class="ac-btn ac-btn-primary" id="acSendBtn" type="button" onclick="sendCollaborationMessage()">
                                    <i class="fa-solid fa-paper-plane"></i> Send
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="ac-panel">
                        <div class="ac-panel-pad">
                            <h2 class="ac-panel-title"><i class="fa-solid fa-flag-checkered" style="color:#22c55e"></i>Submit Recommendation</h2>
                            <form id="acSubmitForm" class="ac-form-grid">
                                <div>
                                    <div class="ac-label">Final recommendation</div>
                                    <textarea class="ac-textarea" id="finalRecommendation" required minlength="60" maxlength="20000" placeholder="State your decision, evidence, tradeoffs, risks, and verification step."></textarea>
                                </div>
                                <div>
                                    <div class="ac-label">Reflection</div>
                                    <textarea class="ac-textarea" id="candidateReflection" maxlength="10000" placeholder="What AI suggestion did you accept, reject, or verify?"></textarea>
                                </div>
                                <button class="ac-btn ac-btn-success" id="acSubmitBtn" type="submit">
                                    <i class="fa-solid fa-check"></i> Complete Simulation
                                </button>
                            </form>
                        </div>
                    </section>
                @endif
            @endif
        </main>
    </div>
</div>

@if($currentSession && !$isCompleted)
<script>
    const acAskUrl = @json(route('user.ai-collaboration.ask', $currentSession->id));
    const acSubmitUrl = @json(route('user.ai-collaboration.submit', $currentSession->id));
    const acReloadUrl = @json(route('user.ai-collaboration', ['session' => $currentSession->id]));
    const acToken = @json(csrf_token());

    function acEscape(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function acScrollBottom() {
        const chat = document.getElementById('acChat');
        if (chat) chat.scrollTop = chat.scrollHeight;
    }

    function acAppendMessage(role, content, id = '') {
        const chat = document.getElementById('acChat');
        if (!chat) return null;

        const row = document.createElement('div');
        row.className = 'ac-message ' + (role === 'user' ? 'user' : 'ai');
        if (id) row.id = id;
        row.innerHTML = `
            <div class="ac-avatar"><i class="fa-solid ${role === 'user' ? 'fa-user' : 'fa-robot'}"></i></div>
            <div class="ac-bubble">${acEscape(content)}</div>
        `;
        chat.appendChild(row);
        acScrollBottom();
        return row;
    }

    async function sendCollaborationMessage() {
        const input = document.getElementById('acMessage');
        const button = document.getElementById('acSendBtn');
        const text = input.value.trim();
        if (!text) return;

        input.value = '';
        button.disabled = true;
        acAppendMessage('user', text);
        const typing = acAppendMessage('ai', 'Thinking through the case...', 'acTyping');

        try {
            const response = await fetch(acAskUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': acToken
                },
                body: JSON.stringify({ message: text })
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.error || 'Unable to send message.');
            }

            typing.querySelector('.ac-bubble').textContent = data.response;
        } catch (error) {
            typing.querySelector('.ac-bubble').textContent = error.message || 'Unable to connect right now.';
        } finally {
            button.disabled = false;
            input.focus();
            acScrollBottom();
        }
    }

    document.getElementById('acMessage')?.addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendCollaborationMessage();
        }
    });

    document.getElementById('acSubmitForm')?.addEventListener('submit', async function(event) {
        event.preventDefault();
        const button = document.getElementById('acSubmitBtn');
        const finalRecommendation = document.getElementById('finalRecommendation').value.trim();
        const candidateReflection = document.getElementById('candidateReflection').value.trim();

        if (finalRecommendation.length < 60) {
            alert('Please provide a fuller recommendation before submitting.');
            return;
        }

        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Scoring';

        try {
            const response = await fetch(acSubmitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': acToken
                },
                body: JSON.stringify({
                    final_recommendation: finalRecommendation,
                    candidate_reflection: candidateReflection
                })
            });

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'Unable to submit recommendation.');
            }

            window.location.href = acReloadUrl;
        } catch (error) {
            alert(error.message || 'Unable to submit recommendation.');
            button.disabled = false;
            button.innerHTML = '<i class="fa-solid fa-check"></i> Complete Simulation';
        }
    });

    acScrollBottom();
</script>
@endif
@endsection
