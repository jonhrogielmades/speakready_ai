@extends('desktop.layouts.app')
@section('title', 'Module Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/modules/show.css?v=4') }}" data-page-style="user-modules-show">
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
                $chapterGateEnabled = \App\Support\ModuleChapterGate::enabled($module);
                $chapterUnlockDelayMs = 5 * 60 * 1000;
                $initialUnlockedChapters = \App\Support\ModuleChapterGate::unlockedCount($module, $moduleProgress);
                $completionProgressRequired = $module->quizzes->isNotEmpty() ? 75 : 25;
                $canComplete = \App\Support\ModuleLessonContent::hasLesson($module) && ($module->quizzes->isEmpty() || ($quizResults['passed'] ?? false));
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
                            <input type="hidden" name="milestone" value="started">
                            <button type="submit" class="btn btn-sm module-progress-button module-progress-button-start">
                                <i class="fa-solid fa-play me-1"></i> Mark Started
                            </button>
                        </form>
                    @endif
                    @if($currentProgress >= $completionProgressRequired && $currentProgress < 100 && $canComplete)
                        <form action="{{ route('user.modules.progress', $module->id) }}" method="POST" data-module-progress-form>
                            @csrf
                            <input type="hidden" name="milestone" value="completed">
                            <button type="submit" class="btn btn-sm module-progress-button module-progress-button-complete">
                                <i class="fa-solid fa-circle-check me-1"></i> Mark Completed
                            </button>
                        </form>
                    @endif
                </div>
                @if($currentProgress >= 75 && $module->quizzes->isNotEmpty() && !($quizResults['passed'] ?? false))
                    <p class="mb-0 mt-2" role="status">Pass every quiz before marking this module completed. See the Quizzes tab for your results.</p>
                @endif
                @unless(\App\Support\ModuleLessonContent::hasLesson($module))
                    <p class="mb-0 mt-2" role="status">This module is waiting for an administrator to add a lesson.</p>
                @endunless
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
                <div @if($chapterGateEnabled) class="module-chapter-gate" data-chapter-gate data-reading-url="{{ route('user.modules.reading', $module->id) }}" data-csrf-token="{{ csrf_token() }}" data-elapsed-seconds="{{ (int) ($moduleProgress->chapter_gate_seconds ?? 0) }}" data-unlock-delay-ms="{{ $chapterUnlockDelayMs }}" data-initial-unlocked="{{ $initialUnlockedChapters }}" @endif>
                @if($chapterGateEnabled)
                    <div class="chapter-gate-status">
                        <div class="chapter-gate-main">
                            <span class="chapter-gate-icon"><i class="fa-solid fa-lock-open"></i></span>
                            <div class="chapter-gate-copy">
                                <strong data-chapter-gate-progress>{{ $initialUnlockedChapters }} of {{ $module->chapters->count() }} chapters unlocked</strong>
                                <span data-chapter-gate-message>{{ $initialUnlockedChapters >= $module->chapters->count() ? 'All chapters unlocked.' : 'Next chapter unlocks after 5:00 while this page is visible.' }}</span>
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
                            {!! \App\Support\ModuleChapterHtml::sanitize($chapter->content) !!}
                            @include('shared.user.module-video', ['chapter' => $chapter])
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
                        <a href="{{ route('user.modules.resources.download', [$module, $resource]) }}" style="text-decoration:none;">
                            <div class="resource-item">
                                <div class="resource-icon">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </div>
                                <div style="flex:1;">
                                    <h6 style="color:var(--tx); font-weight:700; margin-bottom:4px;">{{ $resource->title }}</h6>
                                    <div style="color:var(--tx3); font-size:0.8rem;">
                                        Click to download file
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
            <form action="{{ route('user.modules.progress', $module->id) }}" method="POST" data-module-progress-form>
                @csrf
                <input type="hidden" name="milestone" value="quiz_reviewed">
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
                                        <div class="quiz-options" role="radiogroup" aria-label="Question {{ $question->id }} options">
                                            @foreach($question->options as $option)
                                                <label class="quiz-option">
                                                    <input type="radio" name="quiz_answers[{{ $question->id }}]" value="{{ (string) $option }}" required>
                                                    <span>{{ $option }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <input type="text" name="quiz_answers[{{ $question->id }}]" class="form-control mt-2" maxlength="1000" required aria-label="Answer for question {{ $question->id }}" placeholder="Type your answer">
                                    @endif
                                </div>
                            @empty
                                <div class="quiz-question-box" style="color:var(--tx3);">No quiz questions have been added yet.</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
            @if($currentProgress >= 25 && $currentProgress < 100)
                <button type="submit" class="btn w-100 mt-3" style="background:rgba(59,130,246,0.1); color:var(--pur); border:1px solid rgba(59,130,246,0.2); font-weight:600; border-radius:8px;">Submit Quiz Review</button>
                <p class="mt-2 mb-0" style="color:var(--tx3);">Your answers are scored on the server and cannot be changed by the browser.</p>
            @elseif($currentProgress >= 100)
                <p class="mt-3 mb-0" style="color:var(--tx3);">This module is completed.</p>
            @else
                <p class="mt-3 mb-0" style="color:var(--tx3);">Mark the module as started before saving quiz review progress.</p>
            @endif
            </form>
            @include('shared.user.module-quiz-results')
        </div>
        @endif

        <!-- Practice Tab -->
        @if($module->activities->count() > 0)
        <div class="tab-pane fade" id="activities" role="tabpanel" aria-labelledby="activities-tab">
            <div class="row">
                @foreach($module->activities as $activity)
                    @include('shared.user.module-practice', ['activity' => $activity])
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
<script src="{{ asset('js/module-chapter-gate.js?v=1') }}"></script>
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

        // The shared gate script synchronizes reading time with the server.

        if (typeof window.createSpeakReadyTour === 'function') {
            const steps = [
                { element: '#module-detail-page .mod-hero', popover: { title: 'Module Overview', description: 'Review the topic, difficulty, and preparation goal before working through this module.', side: 'bottom', align: 'start' }},
                { element: '.module-progress-panel', popover: { title: 'Module Progress', description: 'Mark the module started here. After you finish the required lesson and any quizzes, you can mark it completed.', side: 'bottom', align: 'start' }},
                { element: '#moduleTabs', popover: { title: 'Learning Sections', description: 'Switch between chapters and any available resources, quizzes, or practice activities.', side: 'bottom', align: 'start' }},
                { element: '.chapter-gate-status', popover: { title: 'Chapter Unlocks', description: 'When a chapter timer is shown, keep this page visible to unlock the next chapter after the countdown.', side: 'bottom', align: 'start' }},
                { element: '#chapters .chapter-card', popover: { title: 'Read The Lesson', description: 'Work through each unlocked chapter and its supporting media before moving to the next one.', side: 'top', align: 'start' }},
                { element: '#quizzes-tab', popover: { title: 'Check Your Learning', description: 'Open the Quizzes tab and pass every available quiz before marking this module completed.', side: 'bottom', align: 'center' }},
                { element: '#activities-tab', popover: { title: 'Practice Activities', description: 'Open Practice for exercises that apply the module to your own interview preparation.', side: 'bottom', align: 'center' }},
                { element: '.module-next-grid', popover: { title: 'Continue Preparing', description: 'Use these recommendations to choose your next module after this one.', side: 'top', align: 'start' }}
            ];

            window.createSpeakReadyTour({
                completionKey: 'onboarding_completed_interview_module_detail',
                serverDetectedMobile: false,
                stepsMobile: steps,
                stepsDesktop: steps,
                autoStart: false,
            });
        }
    });
</script>
@endpush
@endsection
