@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('content')
<div class="db-section active" id="sec-admin-questions">
    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                <h6 style="color:var(--tx3);font-size:.85rem;margin-bottom:8px">Total Questions</h6>
                <h3 style="color:var(--tx);margin:0;font-weight:700">{{ $totalQuestions ?? 0 }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                <h6 style="color:var(--tx3);font-size:.85rem;margin-bottom:8px">Active Questions</h6>
                <h3 style="color:var(--tx);margin:0;font-weight:700">{{ $activeQuestions ?? 0 }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
                <h6 style="color:var(--tx3);font-size:.85rem;margin-bottom:8px">Categories</h6>
                <h3 style="color:var(--tx);margin:0;font-weight:700">{{ $totalCategories ?? 0 }}</h3>
            </div>
        </div>
    </div>

    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h4 style="font-size:1.4rem;font-weight:700;margin-bottom:4px">Question Bank</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Manage interview questions.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-danger py-2" id="btnBulkDelete" style="font-size:.85rem; display:none;" onclick="submitBulkDelete()"><i class="fa-solid fa-trash me-1"></i> Delete Selected</button>
            <button class="btn btn-outline-info py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#aiGenerateModal"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Generate via AI</button>
            <a href="{{ route('admin.questions.export') }}" class="btn btn-outline-secondary py-2" style="font-size:.85rem"><i class="fa-solid fa-download me-1"></i> Export</a>
            <button class="btn btn-outline-secondary py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#importQuestionsModal"><i class="fa-solid fa-upload me-1"></i> Import</button>
            <button class="btn btn-outline-primary py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#datasetsModal"><i class="fa-solid fa-globe me-1"></i> Datasets</button>
            <button class="bgrd btn px-3 py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#addQuestionModal" onclick="clearGeneratedQuestionSource()"><i class="fa-solid fa-plus me-1"></i> Add Question</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <style>
        .category-card:hover {
            border-color: var(--tx3) !important;
        }
        .category-card.active {
            background: rgba(255,255,255,0.05) !important;
            border-color: #0dcaf0 !important;
        }
        .category-card {
            cursor: pointer; 
            min-width: 160px; 
            background: var(--sf); 
            border: 1px solid var(--bd); 
            border-radius: 14px; 
            padding: 16px; 
            text-align: center; 
            transition: 0.2s;
        }
        #sec-admin-questions .category-filter-cards {
            overflow-x: auto;
            padding-bottom: 8px;
        }
        #sec-admin-questions .category-filter-mobile {
            display: none;
            background: var(--sf);
            border: 1px solid var(--bd);
            border-radius: 14px;
            padding: 14px;
        }
        #sec-admin-questions .category-filter-mobile select {
            width: 100%;
            min-height: 46px;
            background: var(--bg);
            color: var(--tx);
            border: 1px solid var(--bd);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.95rem;
            font-weight: 600;
            outline: none;
        }
        #sec-admin-questions .category-filter-mobile select:focus {
            border-color: #0dcaf0;
            box-shadow: 0 0 0 0.2rem rgba(13, 202, 240, 0.12);
        }

        /* Mobile Card-based Table Layout for Main Questions Table */
        @media (max-width: 767px) {
            #sec-admin-questions .category-filter-cards {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
            }
            #sec-admin-questions .category-filter-mobile {
                display: block !important;
                visibility: visible !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            #sec-admin-questions .category-filter-mobile select {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }
            #mainTableWrapper {
                overflow-x: visible !important;
                -webkit-overflow-scrolling: auto !important;
                padding: 12px !important;
            }
            #mainTable thead {
                display: none;
            }
            #mainTable tbody tr {
                display: flex;
                flex-direction: column;
                background: var(--bg3, rgba(255,255,255,0.02));
                border-radius: 12px;
                margin-bottom: 15px;
                border: 1px solid var(--bd, rgba(255,255,255,0.1));
                padding: 12px;
            }
            #mainTable tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 8px 0 !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
                border-top: none !important;
                text-align: right;
            }
            #mainTable tbody td:last-child {
                border-bottom: none !important;
                justify-content: flex-end;
                gap: 10px;
                padding-top: 12px !important;
            }
            #mainTable tbody td::before {
                font-size: 0.8rem;
                color: var(--tx3, #888);
                font-weight: 600;
                margin-right: 15px;
                flex-shrink: 0;
                text-align: left;
            }
            #mainTable tbody td:nth-child(1) { order: -2; justify-content: flex-start; padding-bottom: 4px !important; border-bottom: none !important; }
            #mainTable tbody td:nth-child(1)::before { content: "Select:"; margin-right: 10px; }
            #mainTable tbody td:nth-child(2)::before { content: "ID"; }
            #mainTable tbody td:nth-child(4)::before { content: "Category"; }
            #mainTable tbody td:nth-child(5)::before { content: "Type/Diff"; }
            #mainTable tbody td:nth-child(6)::before { content: "Status"; }
            
            #mainTable tbody td:nth-child(3) {
                order: -1;
                justify-content: flex-start;
                border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
                padding-bottom: 12px !important;
                margin-bottom: 4px;
                text-align: left;
                flex-direction: column;
                align-items: flex-start;
            }
            #mainTable tbody td:nth-child(3)::before { content: none; }
            #mainTable tbody td:nth-child(3) .fw-bold { max-width: 100% !important; white-space: normal; }
        }
    </style>

    <!-- Category Filter -->
    <div class="category-filter-mobile mb-4">
        <label class="olbl" for="categoryFilterSelect" style="margin-bottom:8px;">Category</label>
        <select id="categoryFilterSelect" aria-label="Filter questions by category" onchange="filterCategory(this.value)">
            <option value="all">All Categories ({{ $totalQuestions }} Questions)</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}">{{ $c->title }} ({{ $c->questions_count }} Questions)</option>
            @endforeach
        </select>
    </div>

    @unless(isset($isMobile) && $isMobile)
    <div class="category-filter-cards d-flex gap-3 mb-4">
        <div class="category-card active" data-category-filter="all" onclick="filterCategory('all')">
            <h6 style="color:var(--tx); margin:0; font-weight:600;">All Categories</h6>
            <span class="badge bg-secondary mt-2">{{ $totalQuestions }} Questions</span>
        </div>
        @foreach($categories as $c)
        <div class="category-card" data-category-filter="{{ $c->id }}" onclick="filterCategory('{{ $c->id }}')">
            <h6 style="color:var(--tx); margin:0; font-weight:600;">{{ $c->title }}</h6>
            <span class="badge bg-secondary mt-2">{{ $c->questions_count }} Questions</span>
        </div>
        @endforeach
    </div>
    @endunless

    <div id="mainTableWrapper" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;overflow-x:auto;">
        <div class="d-md-none mb-3 pb-2" style="border-bottom: 1px solid var(--bd);">
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
                    <th style="border-bottom:1px solid var(--bd);color:var(--tx3);font-size:.8rem;font-weight:600">ID</th>
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
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $q->id }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px;max-width:250px;">
                        <div class="fw-bold">{{ Str::limit($q->question_text, 60) }}</div>
                        @if($q->mapped_skills)
                            <div class="mt-1">
                                @foreach($q->mapped_skills as $skill)
                                    <span class="badge bg-dark border border-secondary" style="font-size:.65rem">{{ $skill }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($q->source_name)
                            <div class="mt-1">
                                <span class="badge bg-info text-dark" style="font-size:.65rem">
                                    <i class="fa-solid fa-link me-1"></i>{{ Str::limit($q->source_name, 32) }}
                                </span>
                            </div>
                        @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">{{ $q->category->title ?? 'N/A' }}</td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        <span class="badge bg-secondary mb-1 d-block">{{ $q->type }}</span>
                        @if($q->difficulty == 'Easy') <span class="badge bg-success d-block">Easy</span>
                        @elseif($q->difficulty == 'Medium') <span class="badge bg-warning text-dark d-block">Medium</span>
                        @else <span class="badge d-block" style="background: var(--danger-bg); color: var(--danger-tx);">Hard</span>
                        @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        @if($q->status == 'active')
                            <span class="badge bg-success">🟢 Active</span>
                        @else
                            <span class="badge bg-secondary">🔴 Inactive</span>
                        @endif
                    </td>
                    <td style="border-bottom:1px solid var(--bd);padding:12px 8px">
                        <div class="d-flex gap-1 flex-wrap">
                            <form action="{{ route('admin.questions.status', $q->id) }}" method="POST" style="display:inline-block">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-warning" style="font-size:.7rem" title="Toggle Status"><i class="fa-solid fa-power-off"></i></button>
                            </form>
                            <button class="btn btn-sm btn-outline-info" style="font-size:.7rem" data-bs-toggle="modal" data-bs-target="#previewQuestionModal{{ $q->id }}" title="Preview"><i class="fa-solid fa-eye"></i></button>
                            <button class="btn btn-sm btn-outline-success" style="font-size:.7rem" onclick="openAnalytics({{ $q->id }})" title="Analytics"><i class="fa-solid fa-chart-line"></i></button>
                            <button class="btn btn-sm btn-outline-primary" style="font-size:.7rem" data-bs-toggle="modal" data-bs-target="#editQuestionModal{{ $q->id }}">Edit</button>
                            <button class="btn btn-sm btn-outline-danger" style="font-size:.7rem" data-bs-toggle="modal" data-bs-target="#deleteQuestionModal{{ $q->id }}">Delete</button>
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
                    <h5 class="modal-title" style="color:var(--tx)">Edit Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="olbl">Category</label>
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
                            <label class="olbl">Question Text</label>
                            <textarea class="oinp mb-3" name="question_text" rows="2" required>{{ $q->question_text }}</textarea>

                            <label class="olbl">Expected Answer Guide (Helps AI)</label>
                            <textarea class="oinp mb-3" name="expected_guide" rows="3" placeholder="e.g. Education, Skills, Experience">{{ $q->expected_guide }}</textarea>

                            <label class="olbl">Mapped Skills (Comma separated)</label>
                            <input class="oinp mb-3" type="text" name="mapped_skills" value="{{ is_array($q->mapped_skills) ? implode(', ', $q->mapped_skills) : '' }}" placeholder="Leadership, Communication">

                            <label class="olbl">Source Name</label>
                            <input class="oinp mb-3" type="text" name="source_name" value="{{ $q->source_name }}" placeholder="e.g. JobStreet Philippines">

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

<!-- Delete Question Modal -->
<div class="modal fade" id="deleteQuestionModal{{ $q->id }}" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.questions.destroy', $q->id) }}" method="POST">
                @csrf @method('DELETE')
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Delete Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--tx)">Are you sure you want to delete this question?</p>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Delete</button>
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

<!-- Add Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.questions.store') }}" method="POST">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Add Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="olbl">Category</label>
                            <select class="oinp mb-3" name="category_id" id="addCatId" required>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->title }}</option>
                                @endforeach
                            </select>
                            
                            <label class="olbl">Type</label>
                            <select class="oinp mb-3" name="type" required>
                                <option value="Behavioral">Behavioral</option>
                                <option value="Situational">Situational</option>
                                <option value="Technical">Technical</option>
                                <option value="Personal">Personal</option>
                            </select>

                            <label class="olbl">Difficulty</label>
                            <select class="oinp mb-3" name="difficulty" id="addDiff" required>
                                <option value="Easy">Easy</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="Hard">Hard</option>
                            </select>

                            <label class="olbl">Status</label>
                            <select class="oinp mb-3" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="olbl">Question Text</label>
                            <textarea class="oinp mb-3" name="question_text" id="addQText" rows="2" required></textarea>

                            <label class="olbl">Expected Answer Guide (Helps AI)</label>
                            <textarea class="oinp mb-3" name="expected_guide" id="addExpectedGuide" rows="3" placeholder="e.g. Education, Skills, Experience"></textarea>

                            <label class="olbl">Mapped Skills (Comma separated)</label>
                            <input class="oinp mb-3" type="text" name="mapped_skills" id="addMappedSkills" placeholder="Leadership, Communication">

                            <input type="hidden" name="source_name" id="addSourceName">
                            <input type="hidden" name="source_url" id="addSourceUrl">
                            <input type="hidden" name="source_type" id="addSourceType">
                            <div id="addSourceBadge" style="display:none;background:var(--bg);border:1px solid var(--bd);border-radius:10px;padding:10px;color:var(--tx3);font-size:.8rem;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importQuestionsModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.questions.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" style="color:var(--tx)">Import Questions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <p style="color:var(--tx3); font-size:.85rem;">Upload a CSV file. The format should be: <code>Question Text, Type, Difficulty, Category ID, Source Name, Source URL, Source Type</code></p>
                    <label class="olbl">CSV File</label>
                    <input class="form-control mb-3" style="background:var(--bg);color:var(--tx);border:1px solid var(--bd)" type="file" name="file" accept=".csv" required>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Analytics Modal -->
<div class="modal fade" id="analyticsModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                <h5 class="modal-title" style="color:var(--tx)"><i class="fa-solid fa-chart-line me-2"></i> Question Analytics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body text-center p-4" id="analyticsBody">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        </div>
    </div>
</div>

<!-- AI Generation Modal -->
<div class="modal fade" id="aiGenerateModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                <h5 class="modal-title" style="color:var(--tx)"><i class="fa-solid fa-wand-magic-sparkles me-2"></i> AI Generate Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body">
                <label class="olbl">Category</label>
                <select class="oinp mb-3" id="aiCatId">
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->title }}</option>
                    @endforeach
                </select>
                <label class="olbl">AI Provider</label>
                <select class="oinp mb-3" id="aiProvider">
                    @foreach($aiProviderOptions ?? [] as $provider)
                        <option value="{{ $provider['key'] }}" {{ $provider['is_default'] ? 'selected' : '' }}>
                            {{ $provider['label'] }}{{ $provider['enabled'] ? '' : ' (not configured; fallback may be used)' }}
                        </option>
                    @endforeach
                </select>
                <label class="olbl">Reliable Philippines Source Pack</label>
                <select class="oinp mb-3" id="aiDataset">
                    <option value="auto">Auto-select from category</option>
                    @foreach($datasetPacks ?? [] as $key => $pack)
                        <option value="{{ $key }}">{{ $pack['name'] }}</option>
                    @endforeach
                </select>
                <label class="olbl">Target Position/Role</label>
                <input type="text" class="oinp mb-3" id="aiPosition" placeholder="e.g. Web Developer" value="Software Engineer">
                <label class="olbl">Difficulty</label>
                <select class="oinp mb-3" id="aiDiff">
                    <option value="Easy">Easy</option>
                    <option value="Medium">Medium</option>
                    <option value="Hard">Hard</option>
                </select>
                
                <div class="text-end">
                    <button type="button" class="btn btn-outline-info" onclick="generateAiQuestion()"><i class="fa-solid fa-robot me-1"></i> Generate</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Datasets Modal -->
<div class="modal fade" id="datasetsModal" tabindex="-1" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                <h5 class="modal-title" style="color:var(--tx)"><i class="fa-solid fa-globe me-2"></i> Community Datasets</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body">
                <p style="color:var(--tx3); font-size:.85rem; margin-bottom: 20px;">Import predefined question sets grounded in public Philippines career, education, scholarship, and TESDA sources.</p>
                <div class="row">
                    @foreach($datasetPacks ?? [] as $key => $pack)
                    <div class="col-md-6 mb-3">
                        <div style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;padding:16px;height:100%;display:flex;flex-direction:column;">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <h6 style="color:var(--tx);font-weight:700;margin:0;">{{ $pack['name'] }}</h6>
                                <span class="badge bg-info text-dark">{{ count($pack['questions'] ?? []) }}</span>
                            </div>
                            <p style="color:var(--tx3);font-size:.8rem;flex-grow:1;margin-bottom:12px;">{{ $pack['description'] }}</p>
                            <div style="font-size:.75rem;color:var(--tx3);margin-bottom:12px;">
                                <i class="fa-solid fa-link me-1"></i>{{ $pack['sources'][0]['name'] ?? 'Reliable Philippines source' }}
                            </div>
                            <form action="{{ route('admin.questions.import-dataset') }}" method="POST">
                                @csrf
                                <input type="hidden" name="dataset" value="{{ $key }}">
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fa-solid fa-download me-1"></i> Import Pack</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Form -->
<form id="bulkDeleteForm" action="{{ route('admin.questions.bulk-delete') }}" method="POST" style="display:none;">
    @csrf
</form>

<script>
function filterCategory(categoryId) {
    const selectedCategory = String(categoryId);

    document.querySelectorAll('.category-card').forEach(card => {
        card.classList.toggle('active', card.dataset.categoryFilter === selectedCategory);
    });

    const mobileSelect = document.getElementById('categoryFilterSelect');
    if (mobileSelect && mobileSelect.value !== selectedCategory) {
        mobileSelect.value = selectedCategory;
    }

    let rows = document.querySelectorAll('.question-row');
    rows.forEach(row => {
        if (selectedCategory === 'all' || row.getAttribute('data-category-id') === selectedCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function toggleAllQuestions(source) {
    let checkboxes = document.querySelectorAll('.question-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    toggleBulkDeleteBtn();
}

function toggleBulkDeleteBtn() {
    let checked = document.querySelectorAll('.question-checkbox:checked').length;
    let btn = document.getElementById('btnBulkDelete');
    if(checked > 0) {
        btn.style.display = 'inline-block';
    } else {
        btn.style.display = 'none';
        document.getElementById('selectAllQuestions').checked = false;
        let mobileSelect = document.getElementById('selectAllMobile');
        if (mobileSelect) mobileSelect.checked = false;
    }
}

function submitBulkDelete() {
    if(!confirm('Are you sure you want to delete all selected questions?')) return;
    
    let form = document.getElementById('bulkDeleteForm');
    
    // remove existing hidden inputs
    form.querySelectorAll('input[name="question_ids[]"]').forEach(el => el.remove());

    let checkboxes = document.querySelectorAll('.question-checkbox:checked');
    checkboxes.forEach(cb => {
        let input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'question_ids[]';
        input.value = cb.value;
        form.appendChild(input);
    });
    
    form.submit();
}

function clearGeneratedQuestionSource() {
    ['addSourceName', 'addSourceUrl', 'addSourceType', 'addExpectedGuide', 'addMappedSkills'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    const sourceBadge = document.getElementById('addSourceBadge');
    if (sourceBadge) {
        sourceBadge.style.display = 'none';
        sourceBadge.innerHTML = '';
    }
}

function openAnalytics(questionId) {
    var myModal = new bootstrap.Modal(document.getElementById('analyticsModal'));
    myModal.show();
    
    document.getElementById('analyticsBody').innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
    
    fetch(`/admin/questions/${questionId}/analytics`)
        .then(response => response.json())
        .then(data => {
            const averageScore = data.has_score_data ? `${data.average_score}%` : 'N/A';
            const analyticsNote = data.has_score_data
                ? 'Data reflects submitted answers stored in the system.'
                : 'No scored answers have been recorded for this question yet.';

            document.getElementById('analyticsBody').innerHTML = `
                <div class="row">
                    <div class="col-6 mb-3">
                        <div style="background:var(--bg);border-radius:12px;padding:16px;">
                            <h6 style="color:var(--tx3);font-size:.8rem;margin-bottom:8px">Usage Count</h6>
                            <h2 style="color:var(--tx);margin:0;font-weight:700">${data.used_count}</h2>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div style="background:var(--bg);border-radius:12px;padding:16px;">
                            <h6 style="color:var(--tx3);font-size:.8rem;margin-bottom:8px">Average Score</h6>
                            <h2 style="color:var(--tx);margin:0;font-weight:700">${averageScore}</h2>
                        </div>
                    </div>
                </div>
                <p style="color:var(--tx3);font-size:.85rem;margin:0">${analyticsNote}</p>
            `;
        });
}

function generateAiQuestion() {
    let catId = document.getElementById('aiCatId').value;
    let pos = document.getElementById('aiPosition').value;
    let diff = document.getElementById('aiDiff').value;
    let provider = document.getElementById('aiProvider') ? document.getElementById('aiProvider').value : 'gemini';
    let dataset = document.getElementById('aiDataset') ? document.getElementById('aiDataset').value : 'auto';
    
    let btn = event.target;
    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
    btn.disabled = true;

    fetch("{{ route('admin.questions.ai-generate') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            category_id: catId,
            position: pos,
            difficulty: diff,
            ai_provider: provider,
            dataset: dataset
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        
        // Close AI modal
        var aiModalEl = document.getElementById('aiGenerateModal');
        var aiModal = bootstrap.Modal.getInstance(aiModalEl);
        aiModal.hide();
        
        // Open Add Question modal and pre-fill
        var addModal = new bootstrap.Modal(document.getElementById('addQuestionModal'));
        document.getElementById('addCatId').value = catId;
        document.getElementById('addDiff').value = diff;
        document.getElementById('addQText').value = data.question_text;
        document.getElementById('addExpectedGuide').value = data.expected_guide || '';
        document.getElementById('addMappedSkills').value = Array.isArray(data.mapped_skills) ? data.mapped_skills.join(', ') : '';
        document.getElementById('addSourceName').value = data.source_name || '';
        document.getElementById('addSourceUrl').value = data.source_url || '';
        document.getElementById('addSourceType').value = data.source_type || '';

        let sourceBadge = document.getElementById('addSourceBadge');
        if (sourceBadge && data.source_name) {
            sourceBadge.style.display = 'block';
            sourceBadge.innerHTML = `<i class="fa-solid fa-link me-1"></i> Source: ${escapeHtml(data.source_name)}${data.dataset_name ? ` via ${escapeHtml(data.dataset_name)}` : ''}`;
        } else if (sourceBadge) {
            sourceBadge.style.display = 'none';
            sourceBadge.innerHTML = '';
        }
        addModal.show();
    })
    .catch(err => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        alert("Error generating question.");
    });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
@endsection
