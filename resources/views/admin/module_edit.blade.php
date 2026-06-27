@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('content')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<div class="db-section active" id="sec-admin-module-edit">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('admin.modules') }}" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Modules</a>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Edit Module: {{ $module->title }}</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage content, resources, and assessments.</p>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-2" style="font-size:0.9rem">Status: {{ ucfirst($module->status) }}</span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4" id="moduleEditTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active oinp" id="basic-tab" data-bs-toggle="pill" data-bs-target="#basic" type="button" role="tab" style="width:auto;margin-right:10px;">Basic Info</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link oinp" id="chapters-tab" data-bs-toggle="pill" data-bs-target="#chapters" type="button" role="tab" style="width:auto;margin-right:10px;">Chapters & Lessons</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link oinp" id="resources-tab" data-bs-toggle="pill" data-bs-target="#resources" type="button" role="tab" style="width:auto;margin-right:10px;">Resources</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link oinp" id="quizzes-tab" data-bs-toggle="pill" data-bs-target="#quizzes" type="button" role="tab" style="width:auto;">Quizzes</button>
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
                            <label class="olbl">Module Title</label>
                            <input class="oinp w-100" type="text" name="title" value="{{ $module->title }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Category</label>
                            <input class="oinp w-100" type="text" name="category" value="{{ $module->category }}">
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
            <div class="mb-3 text-end">
                <button class="bgrd btn btn-sm" data-bs-toggle="modal" data-bs-target="#addChapterModal"><i class="fa-solid fa-plus me-1"></i> Add Chapter</button>
            </div>
            
            @if($module->chapters->isEmpty())
                <div class="text-center py-5" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;">
                    <p style="color:var(--tx3)">No chapters added yet.</p>
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
                                <form action="{{ route('admin.modules.chapters.destroy', $chapter->id) }}" method="POST" class="mt-3 text-end">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onsubmit="return confirm('Delete this chapter?');">Delete Chapter</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Resources Tab -->
        <div class="tab-pane fade" id="resources" role="tabpanel">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <form action="{{ route('admin.modules.resources.store', $module->id) }}" method="POST" enctype="multipart/form-data" class="mb-4 pb-4" style="border-bottom:1px solid var(--bd)">
                    @csrf
                    <h6 class="mb-3" style="color:var(--tx)">Upload New Resource</h6>
                    <div class="row align-items-end">
                        <div class="col-md-5">
                            <label class="olbl">Resource Title</label>
                            <input class="oinp w-100" type="text" name="title" required placeholder="e.g. Interview Cheat Sheet">
                        </div>
                        <div class="col-md-5">
                            <label class="olbl">File (PDF, DOCX, PPTX)</label>
                            <input class="oinp w-100" type="file" name="file" accept=".pdf,.docx,.pptx" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="bgrd btn w-100">Upload</button>
                        </div>
                    </div>
                </form>

                <h6 class="mb-3" style="color:var(--tx)">Attached Resources</h6>
                @if($module->resources->isEmpty())
                    <p style="color:var(--tx3)">No resources attached.</p>
                @else
                    <ul class="list-group list-group-flush" style="border-radius:8px;">
                        @foreach($module->resources as $res)
                        <li class="list-group-item d-flex justify-content-between align-items-center" style="background:transparent;border-color:var(--bd);color:var(--tx3)">
                            <div>
                                <i class="fa-solid fa-file-pdf me-2 text-danger"></i> {{ $res->title }} <span class="badge bg-secondary ms-2">{{ strtoupper($res->file_type) }}</span>
                            </div>
                            <form action="{{ route('admin.modules.resources.destroy', $res->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <!-- Quizzes Tab -->
        <div class="tab-pane fade" id="quizzes" role="tabpanel">
            <div class="mb-3 text-end">
                <button class="bgrd btn btn-sm" data-bs-toggle="modal" data-bs-target="#addQuizModal"><i class="fa-solid fa-plus me-1"></i> Add Quiz</button>
            </div>
            
            @if($module->quizzes->isEmpty())
                <div class="text-center py-5" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;">
                    <p style="color:var(--tx3)">No quizzes created yet.</p>
                </div>
            @else
                @foreach($module->quizzes as $quiz)
                <div class="mb-4 p-4" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 style="color:var(--tx);margin:0">{{ $quiz->title }} <span class="badge bg-primary ms-2" style="font-size:0.7rem">Passing Score: {{ $quiz->passing_score }}%</span></h5>
                        <form action="{{ route('admin.modules.quizzes.destroy', $quiz->id) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete Quiz</button>
                        </form>
                    </div>
                    
                    <!-- Questions List -->
                    <h6 style="color:var(--tx3);font-size:0.9rem">Questions ({{ $quiz->questions->count() }})</h6>
                    <ul class="list-group mb-3">
                        @foreach($quiz->questions as $q)
                        <li class="list-group-item d-flex justify-content-between align-items-start" style="background:transparent;border-color:var(--bd);color:var(--tx3)">
                            <div>
                                <strong>{{ $q->question_text }}</strong><br>
                                <small>Type: {{ $q->type }} | Correct: <span class="text-success">{{ $q->correct_answer }}</span></small>
                            </div>
                            <form action="{{ route('admin.modules.quizzes.questions.destroy', $q->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-times"></i></button>
                            </form>
                        </li>
                        @endforeach
                    </ul>

                    <!-- Add Question Form -->
                    <div class="p-3 mt-3" style="background:#1a1a1a;border-radius:8px;">
                        <h6 style="color:var(--tx);font-size:0.9rem;margin-bottom:12px;">Add Question</h6>
                        <form action="{{ route('admin.modules.quizzes.questions.store', $quiz->id) }}" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <select class="oinp w-100" name="type" style="padding:6px;font-size:0.8rem" required>
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="true_false">True/False</option>
                                        <option value="short_answer">Short Answer</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="oinp w-100" name="question_text" placeholder="Question text" style="padding:6px;font-size:0.8rem" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="oinp w-100" name="options" placeholder="Options (comma separated)" style="padding:6px;font-size:0.8rem">
                                </div>
                                <div class="col-md-2">
                                    <input type="text" class="oinp w-100" name="correct_answer" placeholder="Correct Answer" style="padding:6px;font-size:0.8rem" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="bgrd btn w-100 p-1" style="font-size:0.8rem">+</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            @endif
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
                    <h5 class="modal-title" style="color:var(--tx)">Add Chapter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">Chapter Title</label>
                    <input class="oinp mb-3 w-100" type="text" name="title" required>

                    <label class="olbl">Video URL (Optional YouTube Embed link)</label>
                    <input class="oinp mb-3 w-100" type="text" name="video_url" placeholder="https://www.youtube.com/embed/...">

                    <label class="olbl">Rich Text Lesson Content</label>
                    <!-- Quill Editor Container -->
                    <div id="editor-container" style="height: 250px; background: white; color: black; border-radius: 0 0 8px 8px;"></div>
                    <input type="hidden" name="content" id="chapterContent">
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Save Chapter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Quiz Modal -->
<div class="modal fade" id="addQuizModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.quizzes.store', $module->id) }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Add Quiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">Quiz Title</label>
                    <input class="oinp mb-3 w-100" type="text" name="title" required placeholder="e.g. Basics Assessment">

                    <label class="olbl">Passing Score (%)</label>
                    <input class="oinp mb-3 w-100" type="number" name="passing_score" value="70" min="0" max="100" required>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Create Quiz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['image', 'code-block'],
                ['clean']
            ]
        }
    });

    var form = document.getElementById('chapterForm');
    form.onsubmit = function() {
        var content = document.querySelector('input[name=content]');
        content.value = quill.root.innerHTML;
    };
});
</script>
@endsection

