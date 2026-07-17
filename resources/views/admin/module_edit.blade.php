@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('content')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
    #sec-admin-module-edit .form-switch {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-left: 0 !important;
    }
    #sec-admin-module-edit .form-switch .form-check-input {
        width: 20px !important;
        min-width: 20px !important;
        max-width: 20px !important;
        height: 20px !important;
        min-height: 20px !important;
        margin: 0 !important;
        float: none !important;
        flex: 0 0 20px !important;
        border-radius: 50% !important;
        background-image: none !important;
        cursor: pointer;
    }
    #sec-admin-module-edit .form-switch .form-check-input:checked {
        background-color: #2563eb !important;
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.18) !important;
    }
    #sec-admin-module-edit .form-switch .form-check-input:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.16) !important;
    }
    #sec-admin-module-edit .form-switch .form-check-label {
        margin: 0 !important;
        line-height: 1.3;
        min-width: 0;
        word-break: normal;
        overflow-wrap: normal;
    }
</style>

<div class="db-section active" id="sec-admin-module-edit">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(16, 185, 129, 0.1); color:#10b981; border:1px solid rgba(16, 185, 129, 0.3); border-radius:12px;">
            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="background:rgba(244, 63, 94, 0.1); color:#f43f5e; border:1px solid rgba(244, 63, 94, 0.3); border-radius:12px;">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert" style="background:rgba(245, 158, 11, 0.1); color:#f59e0b; border:1px solid rgba(245, 158, 11, 0.3); border-radius:12px;">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
        </div>
    @endif
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <a href="{{ route('admin.modules') }}" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to PH Modules</a>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Edit PH Interview Module: {{ $module->title }}</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage Philippines interview lessons, resources, and assessments.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary px-3 py-2" style="font-size:0.9rem">Status: {{ ucfirst($module->status) }}</span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 d-flex flex-wrap gap-2" id="moduleEditTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active oinp" id="basic-tab" data-bs-toggle="pill" data-bs-target="#basic" type="button" role="tab" style="width:auto;margin:0;">PH Module Info</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link oinp" id="chapters-tab" data-bs-toggle="pill" data-bs-target="#chapters" type="button" role="tab" style="width:auto;margin:0;">PH Lessons</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link oinp" id="resources-tab" data-bs-toggle="pill" data-bs-target="#resources" type="button" role="tab" style="width:auto;margin:0;">Resources</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link oinp" id="quizzes-tab" data-bs-toggle="pill" data-bs-target="#quizzes" type="button" role="tab" style="width:auto;margin:0;">Quizzes</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link oinp" id="games-tab" data-bs-toggle="pill" data-bs-target="#games" type="button" role="tab" style="width:auto;margin:0;">Linked PH Games</button>
        </li>



    </ul>

    <div class="tab-content" id="moduleEditTabsContent">
        
        <!-- Basic Info Tab -->
        <div class="tab-pane fade show active" id="basic" role="tabpanel">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <form action="{{ route('admin.modules.update', $module->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="olbl">PH Interview Module Title</label>
                            <input class="oinp w-100" type="text" name="title" value="{{ $module->title }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">PH Interview Category</label>
                            <select class="oinp w-100" name="category" id="editCategorySelect" onchange="if(this.value === 'new_category') { document.getElementById('editNewCategoryInput').style.display='block'; document.getElementById('editNewCategoryInput').name='category'; document.getElementById('editNewCategoryInput').required=true; this.name=''; } else { document.getElementById('editNewCategoryInput').style.display='none'; document.getElementById('editNewCategoryInput').name=''; document.getElementById('editNewCategoryInput').required=false; this.name='category'; }">
                                <option value="" {{ !$module->category ? 'selected' : '' }}>Select a PH Category...</option>
                                @if(isset($categories) && count($categories) > 0)
                                    @foreach($categories as $cat)
                                        @if(!empty($cat))
                                            <option value="{{ $cat }}" {{ $module->category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endif
                                    @endforeach
                                @endif
                                @if(!empty($module->category) && !($categories ?? collect())->contains($module->category))
                                    <option value="{{ $module->category }}" selected>{{ $module->category }} (Custom)</option>
                                @endif
                                <option value="new_category">+ Add New PH Category</option>
                            </select>
                            <input type="text" id="editNewCategoryInput" class="oinp w-100 mt-2" placeholder="Enter new PH category name" style="display: none;">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="olbl">Difficulty Level</label>
                            <select class="oinp w-100" name="difficulty">
                                <option value="Beginner" {{ $module->difficulty == 'Beginner' ? 'selected' : '' }}>Beginner</option>
                                <option value="Intermediate" {{ $module->difficulty == 'Intermediate' ? 'selected' : '' }}>Intermediate</option>
                                <option value="Advanced" {{ $module->difficulty == 'Advanced' ? 'selected' : '' }}>Advanced</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Status</label>
                            <select class="oinp w-100" name="status">
                                <option value="draft" {{ $module->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ $module->status == 'published' ? 'selected' : '' }}>Published</option>
                                <option value="archived" {{ $module->status == 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="olbl">Description</label>
                        <textarea class="oinp w-100" name="description" rows="4">{{ $module->description }}</textarea>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="editFeatureSwitch" {{ $module->is_featured ? 'checked' : '' }}>
                        <label class="form-check-label olbl" for="editFeatureSwitch">Mark as Featured</label>
                    </div>
                    <button type="submit" class="bgrd btn px-4">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Chapters Tab -->
        <div class="tab-pane fade" id="chapters" role="tabpanel">
            <div class="mb-3 d-flex justify-content-end gap-2 flex-wrap">
                <form action="{{ route('admin.modules.chapters.generate', $module->id) }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-sm" style="background:rgba(59,130,246,0.1); color:var(--pur); border:1px solid rgba(59,130,246,0.3);" onclick="this.innerHTML='<i class=\'fa-solid fa-circle-notch fa-spin me-1\'></i> Generating...'; this.style.pointerEvents='none';">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Generate PH Chapter
                    </button>
                </form>
                <button class="bgrd btn btn-sm" data-bs-toggle="modal" data-bs-target="#addChapterModal"><i class="fa-solid fa-plus me-1"></i> Add PH Chapter</button>
            </div>
            
            @if($module->chapters->isEmpty())
                <div class="text-center py-5" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;">
                    <p style="color:var(--tx3)">No Philippines interview chapters added yet.</p>
                </div>
            @else
                <div class="accordion" id="chaptersAccordion">
                    @foreach($module->chapters as $index => $chapter)
                    <div class="accordion-item mb-2" style="background:var(--sf);border:1px solid var(--bd);border-radius:8px;overflow:hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChapter{{ $chapter->id }}" style="background:var(--sf);color:var(--tx);box-shadow:none;">
                                <strong>Chapter {{ $chapter->order }}:</strong>&nbsp; {{ $chapter->title }}
                            </button>
                        </h2>
                        <div id="collapseChapter{{ $chapter->id }}" class="accordion-collapse collapse" data-bs-parent="#chaptersAccordion">
                            <div class="accordion-body" style="color:var(--tx3);border-top:1px solid var(--bd);">
                                @if($chapter->video_url)
                                    <div class="mb-3">
                                        <span class="badge bg-info text-dark mb-2">Video Lesson</span><br>
                                        <a href="{{ $chapter->video_url }}" target="_blank" style="color:var(--pr)">{{ $chapter->video_url }}</a>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <span class="badge bg-secondary mb-2">Text Content</span>
                                    <div class="p-3 mt-2" style="background:#1a1a1a;border-radius:8px;max-height:200px;overflow-y:auto;">
                                        {!! $chapter->content ?? '<em>No text content provided.</em>' !!}
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-3 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editChapterModal{{ $chapter->id }}">Edit Chapter</button>
                                    <form action="{{ route('admin.modules.chapters.destroy', $chapter->id) }}" method="POST" onsubmit="return confirm('Delete this chapter?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete Chapter</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Resources Tab -->
        <div class="tab-pane fade" id="resources" role="tabpanel">
            <div class="mb-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                <form action="{{ route('admin.modules.resources.store', $module->id) }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="olbl">Resource Title</label>
                        <input class="oinp w-100" type="text" name="title" required>
                    </div>
                    <div class="col-md-5">
                        <label class="olbl">File</label>
                        <input class="oinp w-100" type="file" name="file" accept=".pdf,.docx,.pptx" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="bgrd btn w-100">Upload</button>
                    </div>
                </form>
            </div>

            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;overflow-x:auto;">
                @if($module->resources->isEmpty())
                    <p class="mb-0 text-center py-4" style="color:var(--tx3)">No resources uploaded yet.</p>
                @else
                    <table class="table table-dark table-hover mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>File</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($module->resources as $resource)
                                <tr>
                                    <td>{{ $resource->title }}</td>
                                    <td>{{ strtoupper($resource->file_type ?? 'file') }}</td>
                                    <td><a href="{{ asset('storage/' . $resource->file_path) }}" target="_blank" style="color:var(--pr)">Open</a></td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.modules.resources.destroy', $resource->id) }}" method="POST" onsubmit="return confirm('Delete this resource?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <!-- Quizzes Tab -->
        <div class="tab-pane fade" id="quizzes" role="tabpanel">
            <div class="mb-3 d-flex justify-content-end gap-2 flex-wrap">
                <form action="{{ route('admin.modules.quizzes.generate', $module->id) }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-sm" style="background:rgba(59,130,246,0.1); color:var(--pur); border:1px solid rgba(59,130,246,0.3);">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> AI Generate Quiz (PH)
                    </button>
                </form>
                <button class="bgrd btn btn-sm" data-bs-toggle="modal" data-bs-target="#addQuizModal"><i class="fa-solid fa-plus me-1"></i> Add PH Quiz</button>
            </div>

            @if($module->quizzes->isEmpty())
                <div class="text-center py-5" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;">
                    <p style="color:var(--tx3)">No Philippines interview quizzes added yet.</p>
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach($module->quizzes as $quiz)
                        <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                <div>
                                    <h5 style="color:var(--tx);margin:0;">{{ $quiz->title }}</h5>
                                    <span style="color:var(--tx3);font-size:.85rem;">Passing score: {{ $quiz->passing_score }}%</span>
                                </div>
                                <form action="{{ route('admin.modules.quizzes.destroy', $quiz->id) }}" method="POST" onsubmit="return confirm('Delete this quiz?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete Quiz</button>
                                </form>
                            </div>

                            @if($quiz->questions->isEmpty())
                                <p style="color:var(--tx3)">No questions yet.</p>
                            @else
                                <div class="table-responsive mb-3">
                                    <table class="table table-dark table-sm mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
                                        <thead>
                                            <tr>
                                                <th>Question</th>
                                                <th>Answer</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($quiz->questions as $question)
                                                <tr>
                                                    <td>{{ $question->question_text }}</td>
                                                    <td>{{ $question->correct_answer }}</td>
                                                    <td class="text-end">
                                                        <form action="{{ route('admin.modules.quizzes.questions.destroy', $question->id) }}" method="POST" onsubmit="return confirm('Delete this question?');">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <form action="{{ route('admin.modules.quizzes.questions.store', $quiz->id) }}" method="POST" class="row g-2 align-items-end">
                                @csrf
                                <input type="hidden" name="type" value="multiple_choice">
                                <div class="col-lg-4">
                                    <label class="olbl">Question</label>
                                    <input class="oinp w-100" type="text" name="question_text" required>
                                </div>
                                <div class="col-lg-4">
                                    <label class="olbl">Options</label>
                                    <input class="oinp w-100" type="text" name="options" placeholder="Option A, Option B, Option C">
                                </div>
                                <div class="col-lg-3">
                                    <label class="olbl">Correct Answer</label>
                                    <input class="oinp w-100" type="text" name="correct_answer" required>
                                </div>
                                <div class="col-lg-1">
                                    <button type="submit" class="btn btn-outline-primary w-100">Add</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Linked Games Tab -->
        <div class="tab-pane fade" id="games" role="tabpanel">
            <div class="mb-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                <form action="{{ route('admin.modules.arena-levels.store', $module->id) }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-9">
                        <label class="olbl">PH Interview Learning Game</label>
                        <select class="oinp w-100" name="game_level_id" required>
                            <option value="" disabled selected>Select a PH interview game...</option>
                            @foreach($allGameLevels as $level)
                                <option value="{{ $level->id }}" {{ $module->gameLevels->contains('id', $level->id) ? 'disabled' : '' }}>
                                    Level {{ $level->level_number }} - {{ $level->title }}{{ $module->gameLevels->contains('id', $level->id) ? ' (attached)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="bgrd btn w-100">Attach</button>
                    </div>
                </form>
            </div>

            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;overflow-x:auto;">
                @if($module->gameLevels->isEmpty())
                    <p class="mb-0 text-center py-4" style="color:var(--tx3)">No PH interview games linked yet.</p>
                @else
                    <table class="table table-dark table-hover mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>Title</th>
                                <th>Difficulty</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($module->gameLevels as $level)
                                <tr>
                                    <td>{{ $level->level_number }}</td>
                                    <td>{{ $level->title }}</td>
                                    <td>{{ ucfirst($level->difficulty) }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.modules.arena-levels.destroy', [$module->id, $level->id]) }}" method="POST" onsubmit="return confirm('Detach this game?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Detach</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add Chapter Modal -->
<div class="modal fade" id="addChapterModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.chapters.store', $module->id) }}" method="POST" id="chapterForm">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Add Philippines Interview Chapter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">PH Chapter Title</label>
                    <input class="oinp mb-3 w-100" type="text" name="title" required>

                    <label class="olbl">Video URL (Optional YouTube Embed link)</label>
                    <input class="oinp mb-3 w-100" type="text" name="video_url" placeholder="https://www.youtube.com/embed/...">

                    <label class="olbl">Philippines Interview Lesson Content</label>
                    <!-- Quill Editor Container -->
                    <div id="editor-container" style="height: 250px; background: var(--bg); color: var(--tx); border-radius: 0 0 8px 8px; border: 1px solid var(--bd);"></div>
                    <input type="hidden" name="content" id="chapterContent">
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Save PH Chapter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Chapter Modals -->
@foreach($module->chapters as $chapter)
<div class="modal fade" id="editChapterModal{{ $chapter->id }}" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.chapters.update', $chapter->id) }}" method="POST" id="editChapterForm-{{ $chapter->id }}">
                @csrf @method('PUT')
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Edit PH Chapter {{ $chapter->order }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">PH Chapter Title</label>
                    <input class="oinp mb-3 w-100" type="text" name="title" value="{{ $chapter->title }}" required>

                    <label class="olbl">Video URL (Optional YouTube Embed link)</label>
                    <input class="oinp mb-3 w-100" type="text" name="video_url" value="{{ $chapter->video_url }}" placeholder="https://www.youtube.com/embed/...">

                    <label class="olbl">Philippines Interview Lesson Content</label>
                    <!-- Quill Editor Container -->
                    <div id="edit-editor-container-{{ $chapter->id }}" style="height: 250px; background: var(--bg); color: var(--tx); border-radius: 0 0 8px 8px; border: 1px solid var(--bd);"></div>
                    <input type="hidden" name="content" id="editChapterContent-{{ $chapter->id }}" value="{{ $chapter->content }}">
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Update PH Chapter</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- Add Quiz Modal -->
<div class="modal fade" id="addQuizModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.quizzes.store', $module->id) }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Add Philippines Interview Quiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">PH Quiz Title</label>
                    <input class="oinp mb-3 w-100" type="text" name="title" required>

                    <label class="olbl">Passing Score</label>
                    <input class="oinp mb-3 w-100" type="number" name="passing_score" min="0" max="100" value="80" required>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Create PH Quiz</button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<style>
    /* Responsive & Theme Fixes for Quill Editor */
    .ql-toolbar { background: var(--bg3, #f1f1f1); border-color: var(--bd) !important; border-radius: 8px 8px 0 0; }
    .ql-container { border-color: var(--bd) !important; }
    body.dark-mode .ql-snow .ql-stroke { stroke: #e0e0e0; }
    body.dark-mode .ql-snow .ql-fill { fill: #e0e0e0; }
    body.dark-mode .ql-snow .ql-picker { color: #e0e0e0; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toolbarOptions = [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline'],
        ['link', 'image'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['clean']
    ];

    var quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        }
    });

    var form = document.getElementById('chapterForm');
    if(form) {
        form.onsubmit = function() {
            var content = document.getElementById('chapterContent');
            content.value = quill.root.innerHTML;
        };
    }

    // Initialize Quill for Edit Modals
    var chapters = @json($module->chapters);
    chapters.forEach(function(chapter) {
        var containerId = '#edit-editor-container-' + chapter.id;
        var editQuill = new Quill(containerId, {
            theme: 'snow',
            modules: {
                toolbar: toolbarOptions
            }
        });
        
        // Load initial content
        var initialContent = document.getElementById('editChapterContent-' + chapter.id).value;
        editQuill.root.innerHTML = initialContent;

        var editForm = document.getElementById('editChapterForm-' + chapter.id);
        if(editForm) {
            editForm.onsubmit = function() {
                var contentInput = document.getElementById('editChapterContent-' + chapter.id);
                contentInput.value = editQuill.root.innerHTML;
            };
        }
    });

    // Tab persistence logic
    var activeTab = localStorage.getItem('activeModuleTab_' + {{ $module->id }});
    if (activeTab) {
        var triggerEl = document.querySelector('button[data-bs-target="' + activeTab + '"]');
        if (triggerEl) {
            // Remove active class from all tabs
            document.querySelectorAll('#moduleEditTabs .nav-link').forEach(function(btn) {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });
            
            // Set saved tab as active
            triggerEl.classList.add('active');
            var paneId = activeTab.replace('#', '');
            var pane = document.getElementById(paneId);
            if(pane) {
               pane.classList.add('show', 'active');
            }
        }
    }

    var tabEls = document.querySelectorAll('button[data-bs-toggle="pill"]');
    tabEls.forEach(function(el) {
        el.addEventListener('shown.bs.tab', function (event) {
            var target = event.target.getAttribute('data-bs-target');
            localStorage.setItem('activeModuleTab_' + {{ $module->id }}, target);
        });
    });
});
</script>
@endsection

