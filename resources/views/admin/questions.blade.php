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
            <button class="bgrd btn px-3 py-2" style="font-size:.85rem" data-bs-toggle="modal" data-bs-target="#addQuestionModal"><i class="fa-solid fa-plus me-1"></i> Add Question</button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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
    </style>

    <!-- Category Filter Cards -->
    <div class="d-flex gap-3 mb-4" style="overflow-x:auto; padding-bottom:8px;">
        <div class="category-card active" onclick="filterCategory('all', this)">
            <h6 style="color:var(--tx); margin:0; font-weight:600;">All Categories</h6>
            <span class="badge bg-secondary mt-2">{{ $totalQuestions }} Questions</span>
        </div>
        @foreach($categories as $c)
        <div class="category-card" onclick="filterCategory('{{ $c->id }}', this)">
            <h6 style="color:var(--tx); margin:0; font-weight:600;">{{ $c->title }}</h6>
            <span class="badge bg-secondary mt-2">{{ $c->questions_count }} Questions</span>
        </div>
        @endforeach
    </div>

    <div style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;overflow-x:auto;">
        <table class="table table-dark table-hover mb-0" style="background:transparent;--bs-table-bg:transparent;--bs-table-color:var(--tx)">
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

                <!-- Modals per question (Edit, Delete, Preview) -->
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
                            </div>
                        </div>
                    </div>
                </div>

                @endforeach
            </tbody>
        </table>
    </div>
</div>

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
                            <textarea class="oinp mb-3" name="expected_guide" rows="3" placeholder="e.g. Education, Skills, Experience"></textarea>

                            <label class="olbl">Mapped Skills (Comma separated)</label>
                            <input class="oinp mb-3" type="text" name="mapped_skills" placeholder="Leadership, Communication">
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
                    <p style="color:var(--tx3); font-size:.85rem;">Upload a CSV file. The format should be: <code>Question Text, Type, Difficulty, Category ID</code></p>
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
                <p style="color:var(--tx3); font-size:.85rem; margin-bottom: 20px;">Browse and import predefined question sets from the community to quickly build your Question Bank.</p>
                <div class="row">
                    <!-- Web Dev Dataset -->
                    <div class="col-md-4 mb-3">
                        <div style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;padding:16px;height:100%;display:flex;flex-direction:column;">
                            <h6 style="color:var(--tx);font-weight:700;">Web Developer Pack</h6>
                            <p style="color:var(--tx3);font-size:.8rem;flex-grow:1;">Technical and behavioral questions tailored for frontend and backend web developers.</p>
                            <form action="{{ route('admin.questions.import-dataset') }}" method="POST">
                                @csrf
                                <input type="hidden" name="dataset" value="web_dev">
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fa-solid fa-download me-1"></i> Import (3)</button>
                            </form>
                        </div>
                    </div>
                    <!-- Sales Dataset -->
                    <div class="col-md-4 mb-3">
                        <div style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;padding:16px;height:100%;display:flex;flex-direction:column;">
                            <h6 style="color:var(--tx);font-weight:700;">Sales Professional</h6>
                            <p style="color:var(--tx3);font-size:.8rem;flex-grow:1;">Situational and behavioral questions for evaluating sales and client-facing roles.</p>
                            <form action="{{ route('admin.questions.import-dataset') }}" method="POST">
                                @csrf
                                <input type="hidden" name="dataset" value="sales">
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fa-solid fa-download me-1"></i> Import (3)</button>
                            </form>
                        </div>
                    </div>
                    <!-- Leadership Dataset -->
                    <div class="col-md-4 mb-3">
                        <div style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;padding:16px;height:100%;display:flex;flex-direction:column;">
                            <h6 style="color:var(--tx);font-weight:700;">Leadership & Mgt.</h6>
                            <p style="color:var(--tx3);font-size:.8rem;flex-grow:1;">Questions focusing on team management, conflict resolution, and leadership style.</p>
                            <form action="{{ route('admin.questions.import-dataset') }}" method="POST">
                                @csrf
                                <input type="hidden" name="dataset" value="leadership">
                                <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fa-solid fa-download me-1"></i> Import (3)</button>
                            </form>
                        </div>
                    </div>
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
function filterCategory(categoryId, cardElement) {
    document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
    cardElement.classList.add('active');

    let rows = document.querySelectorAll('.question-row');
    rows.forEach(row => {
        if (categoryId === 'all' || row.getAttribute('data-category-id') == categoryId) {
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

function openAnalytics(questionId) {
    var myModal = new bootstrap.Modal(document.getElementById('analyticsModal'));
    myModal.show();
    
    document.getElementById('analyticsBody').innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
    
    fetch(`/admin/questions/${questionId}/analytics`)
        .then(response => response.json())
        .then(data => {
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
                            <h2 style="color:var(--tx);margin:0;font-weight:700">${data.average_score}%</h2>
                        </div>
                    </div>
                </div>
                <p style="color:var(--tx3);font-size:.85rem;margin:0">* Data reflects simulated metrics for demo purposes.</p>
            `;
        });
}

function generateAiQuestion() {
    let catId = document.getElementById('aiCatId').value;
    let pos = document.getElementById('aiPosition').value;
    let diff = document.getElementById('aiDiff').value;
    
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
            difficulty: diff
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
        addModal.show();
    })
    .catch(err => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
        alert("Error generating question.");
    });
}
</script>
@endsection