@extends('desktop.layouts.app')
@section('title', 'Module Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/modules/show.css?v=3') }}" data-page-style="user-modules-show">
@endpush

@section('content')

<div class="db-section active" id="module-detail-page">
    <div class="mb-3">
        <a href="{{ route('user.modules.index') }}" class="btn btn-sm" style="color:var(--tx2); background:transparent; border:1px solid var(--bd); border-radius:8px;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to local Modules
        </a>
    </div>

    <div class="mod-hero mb-4">
        <i class="fa-solid fa-book-open-reader mod-hero-bg"></i>
        <div style="position:relative; z-index:2;">
            <div class="module-meta-row">
                <span class="badge module-badge module-badge-type">{{ ucfirst($module->type) }}</span>
                @if($module->difficulty)
                    <span class="badge module-badge module-badge-difficulty">{{ ucfirst($module->difficulty) }}</span>
                @endif
                <span class="badge module-badge module-badge-views"><i class="fa-solid fa-eye"></i>{{ $module->views }} views</span>
            </div>
            <h2 class="module-detail-title">{{ $module->title }}</h2>
            <p class="module-detail-description">{{ $module->description }}</p>
            @php
                $currentProgress = max(0, min(100, (int) ($moduleProgress->progress_percentage ?? 0)));
                $progressLabel = $currentProgress >= 100 ? 'Completed' : ($currentProgress > 0 ? 'In progress' : 'Not started');
                $chapterGateEnabled = collect($module->mapped_skills ?? [])
                    ->contains(fn ($skill): bool => is_string($skill) && \Illuminate\Support\Str::startsWith($skill, 'ai_module_spec:'));
                $chapterUnlockDelayMs = 5 * 60 * 1000;
                $initialUnlockedChapters = $currentProgress >= 100 ? $module->chapters->count() : 1;
            @endphp
            <div class="module-progress-panel">
                <div>
                    <div class="module-progress-summary">
                        <span>{{ $progressLabel }}</span>
                        <span>{{ $currentProgress }}%</span>
                    </div>
                    <div class="module-progress-track" aria-label="{{ $currentProgress }}% complete"><span style="--module-progress: {{ $currentProgress }}%"></span></div>
                </div>
                <div class="module-action-row">
                    @if($currentProgress < 25)
                        <form action="{{ route('user.modules.progress', $module->id) }}" method="POST" data-module-progress-form>
                            @csrf
                            <input type="hidden" name="progress_percentage" value="25">
                            <button type="submit" class="btn btn-sm module-progress-button module-progress-button-start">
                                <i class="fa-solid fa-play me-1"></i> Mark Started
                            </button>
                        </form>
                    @endif
                    @if($currentProgress < 100)
                        <form action="{{ route('user.modules.progress', $module->id) }}" method="POST" data-module-progress-form>
                            @csrf
                            <input type="hidden" name="progress_percentage" value="100">
                            <button type="submit" class="btn btn-sm module-progress-button module-progress-button-complete">
                                <i class="fa-solid fa-circle-check me-1"></i> Mark Completed
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="moduleTabs" role="tablist" style="border-bottom:1px solid var(--bd);">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="chapters-tab" data-bs-toggle="tab" data-bs-target="#chapters" type="button" role="tab" aria-controls="chapters" aria-selected="true"><i class="fa-solid fa-book-open me-2"></i> Chapters</button>
        </li>
        @if($module->resources->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button" role="tab" aria-controls="resources" aria-selected="false"><i class="fa-solid fa-download me-2"></i> Resources</button>
        </li>
        @endif
        @if($module->quizzes->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="quizzes-tab" data-bs-toggle="tab" data-bs-target="#quizzes" type="button" role="tab" aria-controls="quizzes" aria-selected="false"><i class="fa-solid fa-list-check me-2"></i> Quizzes</button>
        </li>
        @endif
        @if($module->activities->count() > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activities" type="button" role="tab" aria-controls="activities" aria-selected="false"><i class="fa-solid fa-dumbbell me-2"></i> Practice</button>
        </li>
        @endif
    </ul>

    <div class="tab-content" id="moduleTabsContent">
        <!-- Chapters Tab -->
        <div class="tab-pane fade show active" id="chapters" role="tabpanel" aria-labelledby="chapters-tab">
            @if($module->chapters->count() > 0)
                <div @if($chapterGateEnabled) class="module-chapter-gate" data-chapter-gate data-module-id="{{ $module->id }}" data-user-id="{{ Auth::id() }}" data-unlock-delay-ms="{{ $chapterUnlockDelayMs }}" data-initial-unlocked="{{ $initialUnlockedChapters }}" @endif>
                @if($chapterGateEnabled)
                    <div class="chapter-gate-status">
                        <div class="chapter-gate-main">
                            <span class="chapter-gate-icon"><i class="fa-solid fa-lock-open"></i></span>
                            <div class="chapter-gate-copy">
                                <strong data-chapter-gate-progress>{{ $initialUnlockedChapters }} of {{ $module->chapters->count() }} chapters unlocked</strong>
                                <span>Next chapter unlocks after 5:00 of active reading.</span>
                            </div>
                        </div>
                        <div class="chapter-gate-clock">
                            <i class="fa-solid fa-clock"></i>
                            <span data-chapter-gate-next>{{ $initialUnlockedChapters >= $module->chapters->count() ? 'All unlocked' : 'Next in 05:00' }}</span>
                        </div>
                        <div class="chapter-gate-track" aria-hidden="true">
                            <span data-chapter-gate-track style="--chapter-gate-progress: {{ $module->chapters->count() > 0 ? (($initialUnlockedChapters / $module->chapters->count()) * 100) : 100 }}%"></span>
                        </div>
                    </div>
                @endif
                @foreach($module->chapters as $index => $chapter)
                    @php
                        $chapterNumber = $index + 1;
                        $chapterTitle = trim((string) ($chapter->title ?? ''));
                        $chapterLabel = preg_match('/^chapter\s+\d+\b/i', $chapterTitle)
                            ? $chapterTitle
                            : 'Chapter ' . $chapterNumber . ($chapterTitle !== '' ? ': ' . $chapterTitle : '');
                        $isChapterInitiallyLocked = $chapterGateEnabled && $chapterNumber > $initialUnlockedChapters;
                    @endphp
                    <div class="chapter-card {{ $isChapterInitiallyLocked ? 'is-chapter-locked' : 'is-chapter-unlocked' }}" data-module-chapter-card data-chapter-number="{{ $chapterNumber }}" aria-disabled="{{ $isChapterInitiallyLocked ? 'true' : 'false' }}">
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                            <h4 class="chapter-title">{{ $chapterLabel }}</h4>
                            <div class="chapter-badge-row">
                                <span class="badge" style="background:var(--bg2); color:var(--tx3); border:1px solid var(--bd);">{{ $chapter->reading_time ?? 5 }} min read</span>
                                @if($chapterGateEnabled)
                                    <span class="badge chapter-lock-badge {{ $isChapterInitiallyLocked ? '' : 'is-unlocked' }}" data-chapter-lock-badge>
                                        <i class="fa-solid {{ $isChapterInitiallyLocked ? 'fa-lock' : 'fa-lock-open' }}"></i>
                                        <span data-chapter-lock-label>{{ $isChapterInitiallyLocked ? 'Locked' : 'Unlocked' }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                        @if($chapterGateEnabled)
                            <div class="chapter-lock-panel" data-chapter-lock-panel @unless($isChapterInitiallyLocked) hidden @endunless>
                                <span class="chapter-lock-icon"><i class="fa-solid fa-lock"></i></span>
                                <div class="chapter-lock-copy">
                                    <strong>Chapter {{ $chapterNumber }} is locked</strong>
                                    <span data-chapter-lock-countdown>{{ $chapterNumber === ($initialUnlockedChapters + 1) ? 'Unlocks in 05:00' : 'Unlocks after earlier chapters' }}</span>
                                </div>
                            </div>
                        @endif
                        <div class="chapter-content" data-chapter-content @if($isChapterInitiallyLocked) hidden @endif>
                            {!! $chapter->content !!}
                        </div>
                    </div>
                @endforeach
                </div>
            @else
                <div class="text-center py-5" style="background:var(--bg2); border-radius:16px; border:1px solid var(--bd);">
                    <i class="fa-solid fa-file-circle-xmark fa-3x mb-3" style="color:var(--bd)"></i>
                    <h5 style="color:var(--tx3)">No chapters have been added to this module yet.</h5>
                </div>
            @endif
        </div>

        <!-- Resources Tab -->
        @if($module->resources->count() > 0)
        <div class="tab-pane fade" id="resources" role="tabpanel" aria-labelledby="resources-tab">
            <div class="row">
                @foreach($module->resources as $resource)
                    <div class="col-12 col-md-6 mb-3">
                        <a href="{{ asset('storage/' . $resource->file_path) }}" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
                            <div class="resource-item">
                                <div class="resource-icon">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </div>
                                <div style="flex:1;">
                                    <h6 style="color:var(--tx); font-weight:700; margin-bottom:4px;">{{ $resource->title }}</h6>
                                    <div style="color:var(--tx3); font-size:0.8rem;">
                                        Click to download or view file
                                    </div>
                                </div>
                                <div style="color:var(--tx3);">
                                    <i class="fa-solid fa-download"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Quizzes Tab -->
        @if($module->quizzes->count() > 0)
        <div class="tab-pane fade" id="quizzes" role="tabpanel" aria-labelledby="quizzes-tab">
            <div class="row">
                @foreach($module->quizzes as $quiz)
                    <div class="col-12 col-md-6 mb-3">
                        <div class="chapter-card h-100 d-flex flex-column">
                            <h5 style="color:var(--tx); font-weight:700; margin-bottom:10px;"><i class="fa-solid fa-clipboard-question text-primary me-2"></i>{{ $quiz->title }}</h5>
                            <p style="color:var(--tx3); font-size:0.9rem;">Test your knowledge on the topics covered in this module.</p>
                            @forelse($quiz->questions as $question)
                                <div class="quiz-question-box">
                                    <div style="color:var(--tx);font-weight:800;font-size:0.92rem;">{{ $loop->iteration }}. {{ $question->question_text }}</div>
                                    @if(is_array($question->options) && count($question->options) > 0)
                                        <div class="quiz-options">
                                            @foreach($question->options as $option)
                                                <div class="quiz-option">{{ $option }}</div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="quiz-question-box" style="color:var(--tx3);">No quiz questions have been added yet.</div>
                            @endforelse
                            <form action="{{ route('user.modules.progress', $module->id) }}" method="POST" class="mt-3" data-module-progress-form>
                                @csrf
                                <input type="hidden" name="progress_percentage" value="{{ max($currentProgress, 75) }}">
                                <button type="submit" class="btn w-100" style="background:rgba(59,130,246,0.1); color:var(--pur); border:1px solid rgba(59,130,246,0.2); font-weight:600; border-radius:8px;">Save Quiz Review Progress</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Practice Tab -->
        @if($module->activities->count() > 0)
        <div class="tab-pane fade" id="activities" role="tabpanel" aria-labelledby="activities-tab">
            <div class="row">
                @foreach($module->activities as $activity)
                    <div class="col-12 col-md-6 mb-3">
                        <div class="chapter-card h-100 d-flex flex-column">
                            <h5 style="color:var(--tx); font-weight:700; margin-bottom:10px;"><i class="fa-solid fa-dumbbell text-success me-2"></i>{{ $activity->title }}</h5>
                            <p style="color:var(--tx3); font-size:0.9rem; flex:1;">{{ $activity->description }}</p>
                            <a href="{{ route('interview.setup') }}" class="btn w-100 mt-3" style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.2); font-weight:600; border-radius:8px;">Start local Practice</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    @if(isset($moduleRecommendations) && $moduleRecommendations->count() > 0)
        <section class="mt-4">
            <h5 style="color:var(--tx);font-weight:800;margin-bottom:4px;"><i class="fa-solid fa-lightbulb me-2" style="color:#f59e0b"></i>Next Helpful Modules</h5>
            <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:0;">Continue with modules connected to your latest interview feedback.</p>
            <div class="module-next-grid">
                @foreach($moduleRecommendations as $recommendation)
                    <a class="module-next-item" href="{{ $recommendation->url }}">
                        <div class="module-next-icon" style="--next-color: {{ $recommendation->color }}"><i class="fa-solid {{ $recommendation->icon }}"></i></div>
                        <div style="min-width:0;">
                            <div style="color:var(--tx);font-weight:800;font-size:0.9rem;line-height:1.3;">{{ $recommendation->module->title }}</div>
                            <div style="color:var(--tx3);font-size:0.78rem;line-height:1.35;margin-top:4px;">{{ $recommendation->reason }}</div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-module-progress-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                const button = form.querySelector('button[type="submit"], button:not([type])');
                if (! button || button.disabled) {
                    return;
                }

                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
                button.dataset.originalLabel = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';
            });
        });

        initModuleChapterGate();

        function initModuleChapterGate() {
            const gate = document.querySelector('[data-chapter-gate]');
            if (! gate) {
                return;
            }

            const cards = Array.from(gate.querySelectorAll('[data-module-chapter-card]'));
            if (cards.length <= 1) {
                return;
            }

            const configuredDelayMs = Number(gate.dataset.unlockDelayMs || 300000);
            const configuredInitialUnlocked = Number(gate.dataset.initialUnlocked || 1);
            const delayMs = Number.isFinite(configuredDelayMs) ? Math.max(1000, configuredDelayMs) : 300000;
            const initialUnlocked = Number.isFinite(configuredInitialUnlocked)
                ? Math.max(1, Math.min(cards.length, configuredInitialUnlocked))
                : 1;
            const storageKey = ['speakready', 'moduleChapterGate', gate.dataset.userId || 'guest', gate.dataset.moduleId || 'module'].join(':');
            const maxElapsedMs = Math.max(0, (cards.length - 1) * delayMs);
            let elapsedAtStart = Math.min(maxElapsedMs, Math.max(0, (initialUnlocked - 1) * delayMs, savedElapsedMs(storageKey)));
            let activeStartedAt = Date.now();
            let isActive = document.visibilityState !== 'hidden';
            let timerId = null;

            render();
            timerId = window.setInterval(function () {
                persist();
                render();
            }, 1000);

            document.addEventListener('visibilitychange', function () {
                elapsedAtStart = currentElapsedMs();
                activeStartedAt = Date.now();
                isActive = document.visibilityState !== 'hidden';
                persist();
                render();
            });

            window.addEventListener('beforeunload', persist);

            function currentElapsedMs() {
                const activeElapsed = isActive ? Date.now() - activeStartedAt : 0;
                return Math.min(maxElapsedMs, Math.max(0, elapsedAtStart + activeElapsed));
            }

            function unlockedCount() {
                return Math.min(cards.length, Math.max(initialUnlocked, 1 + Math.floor(currentElapsedMs() / delayMs)));
            }

            function render() {
                const elapsed = currentElapsedMs();
                const unlocked = unlockedCount();

                cards.forEach(function (card, index) {
                    const isLocked = index >= unlocked;
                    const content = card.querySelector('[data-chapter-content]');
                    const lockPanel = card.querySelector('[data-chapter-lock-panel]');
                    const badge = card.querySelector('[data-chapter-lock-badge]');
                    const badgeIcon = badge ? badge.querySelector('i') : null;
                    const badgeLabel = card.querySelector('[data-chapter-lock-label]');
                    const countdown = card.querySelector('[data-chapter-lock-countdown]');

                    card.classList.toggle('is-chapter-locked', isLocked);
                    card.classList.toggle('is-chapter-unlocked', ! isLocked);
                    card.setAttribute('aria-disabled', isLocked ? 'true' : 'false');
                    setHidden(content, isLocked);
                    setHidden(lockPanel, ! isLocked);

                    if (badge) {
                        badge.classList.toggle('is-unlocked', ! isLocked);
                    }
                    if (badgeIcon) {
                        badgeIcon.className = 'fa-solid ' + (isLocked ? 'fa-lock' : 'fa-lock-open');
                    }
                    if (badgeLabel) {
                        badgeLabel.textContent = isLocked ? 'Locked' : 'Unlocked';
                    }
                    if (countdown && isLocked) {
                        countdown.textContent = index === unlocked
                            ? 'Unlocks in ' + formatTime(msUntilNextUnlock(elapsed))
                            : 'Unlocks after Chapter ' + index + ' opens';
                    }
                });

                const progress = gate.querySelector('[data-chapter-gate-progress]');
                const next = gate.querySelector('[data-chapter-gate-next]');
                const track = gate.querySelector('[data-chapter-gate-track]');
                const allUnlocked = unlocked >= cards.length;

                if (progress) {
                    progress.textContent = unlocked + ' of ' + cards.length + ' chapters unlocked';
                }
                if (next) {
                    next.textContent = allUnlocked ? 'All unlocked' : 'Next in ' + formatTime(msUntilNextUnlock(elapsed));
                }
                if (track) {
                    track.style.setProperty('--chapter-gate-progress', ((unlocked / cards.length) * 100) + '%');
                }

                document.querySelectorAll('.module-progress-button-complete').forEach(function (button) {
                    if (allUnlocked) {
                        button.disabled = false;
                        button.removeAttribute('title');
                    } else {
                        button.disabled = true;
                        button.setAttribute('title', 'Unlock all chapters before marking completed');
                    }
                });

                if (allUnlocked && timerId) {
                    window.clearInterval(timerId);
                    timerId = null;
                    persist();
                }
            }

            function persist() {
                try {
                    window.localStorage.setItem(storageKey, JSON.stringify({
                        elapsedMs: currentElapsedMs(),
                        updatedAt: Date.now()
                    }));
                } catch (error) {
                    // Storage can be unavailable in private browsing; the timer still works for the current page.
                }
            }

            function savedElapsedMs(key) {
                try {
                    const saved = JSON.parse(window.localStorage.getItem(key) || '{}');
                    const elapsed = Number(saved.elapsedMs || 0);
                    return Number.isFinite(elapsed) ? elapsed : 0;
                } catch (error) {
                    return 0;
                }
            }

            function setHidden(element, shouldHide) {
                if (! element) {
                    return;
                }

                if (shouldHide) {
                    element.setAttribute('hidden', 'hidden');
                } else {
                    element.removeAttribute('hidden');
                }
            }

            function msUntilNextUnlock(elapsed) {
                const remainder = elapsed % delayMs;
                return remainder === 0 ? delayMs : delayMs - remainder;
            }

            function formatTime(ms) {
                const totalSeconds = Math.max(0, Math.ceil(ms / 1000));
                const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
                const seconds = String(totalSeconds % 60).padStart(2, '0');
                return minutes + ':' + seconds;
            }
        }
    });
</script>
@endpush
@endsection
