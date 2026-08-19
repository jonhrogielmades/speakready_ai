@extends('mobile.layouts.app')
@section('title', 'Module Details')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/modules/show.css?v=1') }}" data-page-style="user-modules-show">
@endpush

@section('content')

<div class="db-section active" id="module-detail-page">
    <div class="mb-3">
        <a href="{{ route('user.modules.index') }}" class="btn btn-sm" style="color:var(--tx2); background:transparent; border:1px solid var(--bd); border-radius:8px;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Philippines Modules
        </a>
    </div>

    <div class="mod-hero mb-4">
        <i class="fa-solid fa-book-open-reader mod-hero-bg"></i>
        <div style="position:relative; z-index:2;">
            <div class="d-flex gap-2 mb-3">
                <span class="badge" style="background:rgba(59,130,246,0.15); color:var(--pur); font-weight:700;">{{ ucfirst($module->type) }}</span>
                @if($module->difficulty)
                    <span class="badge" style="background:rgba(16,185,129,0.15); color:#10b981; font-weight:700;">{{ ucfirst($module->difficulty) }}</span>
                @endif
                <span class="badge" style="background:rgba(245,158,11,0.15); color:#f59e0b; font-weight:700;"><i class="fa-solid fa-eye me-1"></i>{{ $module->views }} views</span>
            </div>
            <h2 style="font-weight:800; color:var(--tx); margin-bottom:15px; font-family:'Poppins', sans-serif;">{{ $module->title }}</h2>
            <p style="color:var(--tx2); font-size:1.05rem; max-width:800px; line-height:1.6;">{{ $module->description }}</p>
            @php
                $currentProgress = max(0, min(100, (int) ($moduleProgress->progress_percentage ?? 0)));
                $progressLabel = $currentProgress >= 100 ? 'Completed' : ($currentProgress > 0 ? 'In progress' : 'Not started');
            @endphp
            <div class="module-progress-panel">
                <div>
                    <div class="d-flex justify-content-between gap-3" style="font-size:0.9rem;font-weight:800;color:var(--tx);">
                        <span>{{ $progressLabel }}</span>
                        <span>{{ $currentProgress }}%</span>
                    </div>
                    <div class="module-progress-track" aria-label="{{ $currentProgress }}% complete"><span style="--module-progress: {{ $currentProgress }}%"></span></div>
                </div>
                <div class="module-action-row">
                    @if($currentProgress < 25)
                        <form action="{{ route('user.modules.progress', $module->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="progress_percentage" value="25">
                            <button type="submit" class="btn btn-sm" style="background:rgba(59,130,246,0.12);color:var(--pur);border:1px solid rgba(59,130,246,0.22);font-weight:800;border-radius:10px;">
                                <i class="fa-solid fa-play me-1"></i> Mark Started
                            </button>
                        </form>
                    @endif
                    @if($currentProgress < 100)
                        <form action="{{ route('user.modules.progress', $module->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="progress_percentage" value="100">
                            <button type="submit" class="btn btn-sm" style="background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.25);font-weight:800;border-radius:10px;">
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
                @foreach($module->chapters as $index => $chapter)
                    <div class="chapter-card">
                        <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                            <h4 class="chapter-title">Chapter {{ $index + 1 }}: {{ $chapter->title }}</h4>
                            <span class="badge" style="background:var(--bg2); color:var(--tx3); border:1px solid var(--bd);">{{ $chapter->reading_time ?? 5 }} min read</span>
                        </div>
                        <div class="chapter-content">
                            {!! $chapter->content !!}
                        </div>
                    </div>
                @endforeach
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
                        <a href="{{ asset('storage/' . $resource->file_path) }}" target="_blank" style="text-decoration:none;">
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
                            <form action="{{ route('user.modules.progress', $module->id) }}" method="POST" class="mt-3">
                                @csrf
                                <input type="hidden" name="progress_percentage" value="{{ max($currentProgress, 75) }}">
                                <button class="btn w-100" style="background:rgba(59,130,246,0.1); color:var(--pur); border:1px solid rgba(59,130,246,0.2); font-weight:600; border-radius:8px;">Save Quiz Review Progress</button>
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
                            <a href="{{ route('interview.setup') }}" class="btn w-100 mt-3" style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.2); font-weight:600; border-radius:8px;">Start Philippines Practice</a>
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
            <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:0;">Continue with modules connected to your latest Philippines interview feedback.</p>
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
@endsection
