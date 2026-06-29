@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('content')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

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
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <a href="{{ route('admin.modules') }}" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Modules</a>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Edit Module: {{ $module->title }}</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage content, resources, and assessments.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary px-3 py-2" style="font-size:0.9rem">Status: {{ ucfirst($module->status) }}</span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 d-flex flex-wrap gap-2" id="moduleEditTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active oinp" id="basic-tab" data-bs-toggle="pill" data-bs-target="#basic" type="button" role="tab" style="width:auto;margin:0;">Basic Info</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link oinp" id="chapters-tab" data-bs-toggle="pill" data-bs-target="#chapters" type="button" role="tab" style="width:auto;margin:0;">Chapters & Lessons</button>
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
                            <select class="oinp w-100" name="category" id="editCategorySelect" onchange="if(this.value === 'new_category') { document.getElementById('editNewCategoryInput').style.display='block'; document.getElementById('editNewCategoryInput').name='category'; document.getElementById('editNewCategoryInput').required=true; this.name=''; } else { document.getElementById('editNewCategoryInput').style.display='none'; document.getElementById('editNewCategoryInput').name=''; document.getElementById('editNewCategoryInput').required=false; this.name='category'; }">
                                <option value="" {{ !$module->category ? 'selected' : '' }}>Select a Category...</option>
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
                                <option value="new_category">+ Add New Category</option>
                            </select>
                            <input type="text" id="editNewCategoryInput" class="oinp w-100 mt-2" placeholder="Enter new category name" style="display: none;">
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
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> AI Generate Chapter
                    </button>
                </form>
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
                    <div id="editor-container" style="height: 250px; background: var(--bg); color: var(--tx); border-radius: 0 0 8px 8px; border: 1px solid var(--bd);"></div>
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

<!-- Edit Chapter Modals -->
@foreach($module->chapters as $chapter)
<div class="modal fade" id="editChapterModal{{ $chapter->id }}" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.chapters.update', $chapter->id) }}" method="POST" id="editChapterForm-{{ $chapter->id }}">
                @csrf @method('PUT')
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Edit Chapter {{ $chapter->order }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <label class="olbl">Chapter Title</label>
                    <input class="oinp mb-3 w-100" type="text" name="title" value="{{ $chapter->title }}" required>

                    <label class="olbl">Video URL (Optional YouTube Embed link)</label>
                    <input class="oinp mb-3 w-100" type="text" name="video_url" value="{{ $chapter->video_url }}" placeholder="https://www.youtube.com/embed/...">

                    <label class="olbl">Rich Text Lesson Content</label>
                    <!-- Quill Editor Container -->
                    <div id="edit-editor-container-{{ $chapter->id }}" style="height: 250px; background: var(--bg); color: var(--tx); border-radius: 0 0 8px 8px; border: 1px solid var(--bd);"></div>
                    <input type="hidden" name="content" id="editChapterContent-{{ $chapter->id }}" value="{{ $chapter->content }}">
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Update Chapter</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach



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

