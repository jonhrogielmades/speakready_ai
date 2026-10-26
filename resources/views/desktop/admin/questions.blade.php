@extends('desktop.layouts.admin')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/admin/questions.css?v=6') }}" data-page-style="admin-questions">
@endpush

@section('content')
<div class="db-section active" id="sec-admin-questions">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Interview Question Bank</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage practice questions for local job interviews.</p>
        </div>
        <div class="d-flex gap-2">
            @include('shared.admin.question-bank-toolbar')
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif


    <!-- Category Filter -->
    <div class="question-table-controls">
        <div class="category-filter-select">
        <select id="categoryFilterSelect" aria-label="Filter questions by category" onchange="filterCategory(this.value)">
            <option value="all">All Interview Categories ({{ $totalQuestions }} Questions)</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}">{{ $c->title }} ({{ $c->questions_count }} Questions)</option>
            @endforeach
        </select>
        </div>
        <div class="question-bulk-action-bar">
            <button type="button" class="btn btn-outline-danger py-2" id="btnBulkDelete" disabled onclick="submitBulkDelete()"><i class="fa-solid fa-trash me-1"></i> Delete Selected</button>
        </div>
    </div>

    <div id="mainTableWrapper" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;overflow-x:auto;">
        <div class="d-md-none mb-3 pb-2 question-select-all-wrap" style="border-bottom: 1px solid var(--bd);">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAllMobile" onclick="document.getElementById('selectAllQuestions').click();">
                <label class="form-check-label" for="selectAllMobile" style="color:var(--tx3); font-size:0.85rem; font-weight:600;">
                    Select All
                </label>
            </div>
        </div>
        <table id="mainTable" class="table table-dark table-hover mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600;width:40px;">
                        <input class="form-check-input" type="checkbox" id="selectAllQuestions" onclick="toggleAllQuestions(this)">
                    </th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Question</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Category</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Type / Diff</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">Status</th>
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600;min-width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($questions as $q)
                <tr class="question-row" data-category-id="{{ $q->category_id }}">
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        <input class="form-check-input question-checkbox" type="checkbox" value="{{ $q->id }}" onchange="toggleBulkDeleteBtn()">
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px;">
                        <div class="fw-bold question-title" title="{{ $q->question_text }}">{{ $q->question_text }}</div>
                        @if($q->mapped_skills)
                            <div class="mt-1">
                                @foreach($q->mapped_skills as $skill)
                                    <span class="badge bg-dark border border-secondary" style="font-size:.65rem">{{ $skill }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px"><span class="question-category" title="{{ $q->category->title ?? 'N/A' }}">{{ $q->category->title ?? 'N/A' }}</span></td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        <span class="badge bg-secondary mb-1 d-block">{{ $q->type }}</span>
                        @if($q->difficulty == 'Easy') <span class="badge bg-success d-block">Easy</span>
                        @elseif($q->difficulty == 'Medium') <span class="badge bg-warning text-dark d-block">Medium</span>
                        @else <span class="badge d-block" style="background: var(--danger-bg); color: var(--danger-tx);">Hard</span>
                        @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($q->status == 'active')
                            <span class="badge bg-success"><i class="fa-solid fa-circle me-1"></i>Active</span>
                        @else
                            <span class="badge bg-secondary"><i class="fa-solid fa-circle me-1"></i>Inactive</span>
                        @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        <div class="question-actions">
                            <form action="{{ route('admin.questions.status', $q->id) }}" method="POST" style="display:inline-block">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-warning" style="font-size:.7rem" title="Toggle Status"><i class="fa-solid fa-power-off"></i></button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-info" style="font-size:.7rem" data-bs-toggle="modal" data-bs-target="#previewQuestionModal{{ $q->id }}" title="Preview"><i class="fa-solid fa-eye"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-success" style="font-size:.7rem" onclick="openAnalytics({{ $q->id }})" title="Analytics"><i class="fa-solid fa-chart-line"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:.7rem" data-bs-toggle="modal" data-bs-target="#editQuestionModal{{ $q->id }}" title="Edit"><i class="fa-solid fa-pen me-1"></i>Edit</button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@foreach($questions as $q)
<!-- Edit Question Modal -->
<div class="modal fade" id="editQuestionModal{{ $q->id }}" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.questions.update', $q->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Edit Interview Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="olbl">Interview Category</label>
                            <select class="oinp mb-3" name="category_id" required>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" {{ $c->id == $q->category_id ? 'selected' : '' }}>{{ $c->title }}</option>
                                @endforeach
                            </select>

                            <label class="olbl">Type</label>
                            <select class="oinp mb-3" name="type" required>
                                <option value="Behavioral" {{ $q->type == 'Behavioral' ? 'selected' : '' }}>Behavioral</option>
                                <option value="Situational" {{ $q->type == 'Situational' ? 'selected' : '' }}>Situational</option>
                                <option value="Technical" {{ $q->type == 'Technical' ? 'selected' : '' }}>Technical</option>
                                <option value="Personal" {{ $q->type == 'Personal' ? 'selected' : '' }}>Personal</option>
                            </select>

                            <label class="olbl">Difficulty</label>
                            <select class="oinp mb-3" name="difficulty" required>
                                <option value="Easy" {{ $q->difficulty == 'Easy' ? 'selected' : '' }}>Easy</option>
                                <option value="Medium" {{ $q->difficulty == 'Medium' ? 'selected' : '' }}>Medium</option>
                                <option value="Hard" {{ $q->difficulty == 'Hard' ? 'selected' : '' }}>Hard</option>
                            </select>

                            <label class="olbl">Status</label>
                            <select class="oinp mb-3" name="status" required>
                                <option value="active" {{ $q->status == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $q->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Interview Question Text</label>
                            <textarea class="oinp mb-3" name="question_text" rows="2" required>{{ $q->question_text }}</textarea>

                            <label class="olbl">Expected Answer Guide (Keeps interview context)</label>
                            <textarea class="oinp mb-3" name="expected_guide" rows="3" placeholder="e.g. job role fit evidence">{{ $q->expected_guide }}</textarea>

                            <label class="olbl">Mapped Skills (Comma separated)</label>
                            <input class="oinp mb-3" type="text" name="mapped_skills" value="{{ is_array($q->mapped_skills) ? implode(', ', $q->mapped_skills) : '' }}" placeholder="Communication, Professionalism">

                            <label class="olbl">Source Name</label>
                            <input class="oinp mb-3" type="text" name="source_name" value="{{ $q->source_name }}" placeholder="e.g. Career source">

                            <label class="olbl">Source URL</label>
                            <input class="oinp mb-3" type="url" name="source_url" value="{{ $q->source_url }}" placeholder="https://...">

                            <input type="hidden" name="source_type" value="{{ $q->source_type }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Question Modal -->
<div class="modal fade" id="previewQuestionModal{{ $q->id }}" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd);background:var(--bg)">
            <div class="modal-header" style="border-bottom:none">
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body text-center p-4">
                <span class="badge bg-primary mb-3">{{ $q->category->title ?? 'General' }}</span>
                <h3 style="color:var(--tx);font-weight:700;line-height:1.5;margin-bottom:24px;">{{ $q->question_text }}</h3>

                <div class="d-flex justify-content-center gap-3 text-muted" style="font-size:.85rem">
                    <div><i class="fa-solid fa-layer-group me-1"></i> {{ $q->type }}</div>
                    <div><i class="fa-solid fa-gauge-high me-1"></i> {{ $q->difficulty }}</div>
                </div>

                @if($q->expected_guide)
                <div class="mt-4 p-3 text-start" style="background:var(--sf);border-radius:12px;border:1px solid var(--bd);">
                    <h6 style="color:var(--tx3);font-size:.8rem;text-transform:uppercase;">Expected Guide (Hidden from User)</h6>
                    <div style="color:var(--tx);font-size:.9rem;white-space:pre-wrap;">{{ $q->expected_guide }}</div>
                </div>
                @endif

                @if($q->source_name)
                <div class="mt-3 text-start" style="font-size:.8rem;color:var(--tx3);">
                    <i class="fa-solid fa-link me-1"></i>
                    Source:
                    @if($q->source_url)
                        <a href="{{ $q->source_url }}" target="_blank" rel="noopener" class="text-info">{{ $q->source_name }}</a>
                    @else
                        {{ $q->source_name }}
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endforeach

@include('shared.admin.question-bank-actions')
@endsection
