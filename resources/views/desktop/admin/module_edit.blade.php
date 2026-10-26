@extends('desktop.layouts.admin')
@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/desktop/admin/module_edit.css?v=1') }}" data-page-style="admin-module_edit">
<link rel="stylesheet" href="{{ asset('css/desktop/admin/module_edit-2.css?v=2') }}" data-page-style="admin-module_edit-2">
@endpush

@section('content')
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
    @if($errors->any())
        <div class="alert alert-danger mb-4" role="alert">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <a href="{{ route('admin.modules') }}" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Interview Modules</a>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Edit Interview Module: {{ $module->title }}</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage interview action steps and lessons.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary px-3 py-2" style="font-size:0.9rem">Status: {{ ucfirst($module->status) }}</span>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 d-flex flex-wrap gap-2" id="moduleEditTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active oinp" id="basic-tab" data-bs-toggle="pill" data-bs-target="#basic" type="button" role="tab" style="width:auto;margin:0;">Interview Module Info</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link oinp" id="chapters-tab" data-bs-toggle="pill" data-bs-target="#chapters" type="button" role="tab" style="width:auto;margin:0;">Interview Lessons</button>
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
                            <label class="olbl">Interview Module Title</label>
                            <input class="oinp w-100" type="text" name="title" value="{{ $module->title }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Interview Category</label>
                            <select class="oinp w-100" name="category" id="editCategorySelect" onchange="if(this.value === 'new_category') { document.getElementById('editNewCategoryInput').style.display='block'; document.getElementById('editNewCategoryInput').name='category'; document.getElementById('editNewCategoryInput').required=true; this.name=''; } else { document.getElementById('editNewCategoryInput').style.display='none'; document.getElementById('editNewCategoryInput').name=''; document.getElementById('editNewCategoryInput').required=false; this.name='category'; }">
                                <option value="" {{ !$module->category ? 'selected' : '' }}>Select a Interview Category...</option>
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
                                <option value="new_category">+ Add New Interview Category</option>
                            </select>
                            <input type="text" id="editNewCategoryInput" class="oinp w-100 mt-2" placeholder="Enter new interview category name" style="display: none;">
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
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Generate Interview Chapter
                    </button>
                </form>
                <button type="button" class="bgrd btn btn-sm" data-bs-toggle="modal" data-bs-target="#addChapterModal"><i class="fa-solid fa-plus me-1"></i> Add Interview Chapter</button>
            </div>
            
            @if($module->chapters->isEmpty())
                <div class="text-center py-5" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;">
                    <p style="color:var(--tx3)">No interview chapters added yet.</p>
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
                                    <div class="admin-module-chapter-content p-3 mt-2">
                                        @if(filled($chapter->content))
                                            {!! \App\Support\ModuleChapterHtml::sanitize($chapter->content) !!}
                                        @else
                                            <em>No text content provided.</em>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-3 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editChapterModal{{ $chapter->id }}">Edit Chapter</button>
                                    <form action="{{ route('admin.modules.chapters.destroy', [$module, $chapter]) }}" method="POST" onsubmit="return window.SpeakReadyAdminConfirm(event, this, 'Delete this chapter?');">
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

@include('shared.admin.module-chapter-modals')
@endsection
