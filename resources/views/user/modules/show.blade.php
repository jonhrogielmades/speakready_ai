@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    .mod-hero {
        background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(52,211,153,0.1) 100%);
        border: 1px solid var(--bd);
        border-radius: 20px;
        padding: 40px;
        position: relative;
        overflow: hidden;
    }
    .mod-hero-bg {
        position: absolute;
        top: -50px;
        right: -50px;
        font-size: 15rem;
        color: rgba(59,130,246,0.05);
        transform: rotate(-15deg);
        pointer-events: none;
    }
    .nav-tabs .nav-link {
        color: var(--tx2);
        font-weight: 600;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 20px;
        background: transparent;
    }
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        color: var(--pur);
    }
    .nav-tabs .nav-link.active {
        color: var(--pur);
        background: transparent;
        border-bottom: 3px solid var(--pur);
    }
    .chapter-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        transition: 0.3s;
    }
    .chapter-card:hover {
        border-color: rgba(59,130,246,0.4);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .chapter-title {
        color: var(--tx);
        font-weight: 700;
        margin-bottom: 10px;
        font-size: 1.25rem;
    }
    .chapter-content {
        color: var(--tx3);
        font-size: 0.95rem;
        line-height: 1.6;
    }
    .resource-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border: 1px solid var(--bd);
        border-radius: 12px;
        background: var(--sf);
        margin-bottom: 12px;
        transition: 0.2s;
    }
    .resource-item:hover {
        background: var(--bg2);
        border-color: var(--pur);
    }
    .resource-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        background: rgba(59,130,246,0.1);
        color: var(--pur);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
</style>

<div class="db-section active">
    <div class="mb-3">
        <a href="{{ route('user.modules.index') }}" class="btn btn-sm" style="color:var(--tx2); background:transparent; border:1px solid var(--bd); border-radius:8px;">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Modules
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
                            {!! nl2br(e($chapter->content)) !!}
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
                            <p style="color:var(--tx3); font-size:0.9rem; flex:1;">Test your knowledge on the topics covered in this module.</p>
                            <button class="btn w-100 mt-3" style="background:rgba(59,130,246,0.1); color:var(--pur); border:1px solid rgba(59,130,246,0.2); font-weight:600; border-radius:8px;">Start Quiz</button>
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
                            <a href="{{ route('interview.setup') }}" class="btn w-100 mt-3" style="background:rgba(16,185,129,0.1); color:#10b981; border:1px solid rgba(16,185,129,0.2); font-weight:600; border-radius:8px;">Go to Practice</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
