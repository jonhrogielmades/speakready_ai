@php
    $defaultQuestionErrors = $errors->getBag('default');
    $addQuestionHasErrors = old('_question_form') === 'add' && $defaultQuestionErrors->any();
    $addQuestionErrors = $addQuestionHasErrors
        ? $defaultQuestionErrors
        : new \Illuminate\Support\MessageBag();
    $importQuestionErrors = $errors->getBag('importQuestions');
    $datasetImportErrors = $errors->getBag('datasetImport');
    $defaultQuestionCategoryId = (string) ($categories->first()?->id ?? '');
    $addQuestionCategoryId = (string) ($addQuestionHasErrors ? old('category_id', $defaultQuestionCategoryId) : $defaultQuestionCategoryId);
    $addQuestionType = $addQuestionHasErrors ? old('type', 'Behavioral') : 'Behavioral';
    $addQuestionDifficulty = $addQuestionHasErrors ? old('difficulty', 'Medium') : 'Medium';
    $addQuestionStatus = $addQuestionHasErrors ? old('status', 'active') : 'active';
@endphp

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1" aria-labelledby="addQuestionModalLabel" aria-hidden="true" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form id="addQuestionForm" action="{{ route('admin.questions.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_question_form" value="add">
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <div>
                        <h5 class="modal-title" id="addQuestionModalLabel" style="color:var(--tx)">Add Interview Question</h5>
                        <div class="text-muted mt-1" style="font-size:.8rem">Review all fields, then save the question to the active bank.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    @if($addQuestionHasErrors)
                        <div class="alert alert-danger" role="alert" data-server-error>
                            The question was not saved. Correct the highlighted fields and try again.
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="olbl" for="addCatId">Interview Category</label>
                            <select class="oinp {{ $addQuestionErrors->has('category_id') ? 'is-invalid' : '' }}" name="category_id" id="addCatId" required>
                                @forelse($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string) $category->id === $addQuestionCategoryId ? 'selected' : '' }}>{{ $category->title }}</option>
                                @empty
                                    <option value="" selected disabled>No interview categories available</option>
                                @endforelse
                            </select>
                            @if($addQuestionErrors->has('category_id'))
                                <div class="invalid-feedback d-block" data-server-error>{{ $addQuestionErrors->first('category_id') }}</div>
                            @endif

                            <label class="olbl mt-3" for="addType">Type</label>
                            <select class="oinp {{ $addQuestionErrors->has('type') ? 'is-invalid' : '' }}" name="type" id="addType" required>
                                @foreach(['Behavioral', 'Situational', 'Technical', 'Personal'] as $type)
                                    <option value="{{ $type }}" {{ $addQuestionType === $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            @if($addQuestionErrors->has('type'))
                                <div class="invalid-feedback d-block" data-server-error>{{ $addQuestionErrors->first('type') }}</div>
                            @endif

                            <label class="olbl mt-3" for="addDiff">Difficulty</label>
                            <select class="oinp {{ $addQuestionErrors->has('difficulty') ? 'is-invalid' : '' }}" name="difficulty" id="addDiff" required>
                                @foreach(['Easy', 'Medium', 'Hard'] as $difficulty)
                                    <option value="{{ $difficulty }}" {{ $addQuestionDifficulty === $difficulty ? 'selected' : '' }}>{{ $difficulty }}</option>
                                @endforeach
                            </select>
                            @if($addQuestionErrors->has('difficulty'))
                                <div class="invalid-feedback d-block" data-server-error>{{ $addQuestionErrors->first('difficulty') }}</div>
                            @endif

                            <label class="olbl mt-3" for="addStatus">Status</label>
                            <select class="oinp {{ $addQuestionErrors->has('status') ? 'is-invalid' : '' }}" name="status" id="addStatus" required>
                                <option value="active" {{ $addQuestionStatus === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $addQuestionStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @if($addQuestionErrors->has('status'))
                                <div class="invalid-feedback d-block" data-server-error>{{ $addQuestionErrors->first('status') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="olbl" for="addQText">Interview Question Text</label>
                            <textarea class="oinp {{ $addQuestionErrors->has('question_text') ? 'is-invalid' : '' }}" name="question_text" id="addQText" rows="3" maxlength="5000" required>{{ $addQuestionHasErrors ? old('question_text') : '' }}</textarea>
                            @if($addQuestionErrors->has('question_text'))
                                <div class="invalid-feedback d-block" data-server-error>{{ $addQuestionErrors->first('question_text') }}</div>
                            @endif

                            <label class="olbl mt-3" for="addExpectedGuide">Expected Answer Guide</label>
                            <textarea class="oinp {{ $addQuestionErrors->has('expected_guide') ? 'is-invalid' : '' }}" name="expected_guide" id="addExpectedGuide" rows="3" maxlength="10000" placeholder="Key evidence or answer structure to look for">{{ $addQuestionHasErrors ? old('expected_guide') : '' }}</textarea>
                            @if($addQuestionErrors->has('expected_guide'))
                                <div class="invalid-feedback d-block" data-server-error>{{ $addQuestionErrors->first('expected_guide') }}</div>
                            @endif

                            <label class="olbl mt-3" for="addMappedSkills">Mapped Skills</label>
                            <input class="oinp {{ $addQuestionErrors->has('mapped_skills') ? 'is-invalid' : '' }}" type="text" name="mapped_skills" id="addMappedSkills" maxlength="1000" value="{{ $addQuestionHasErrors ? old('mapped_skills') : '' }}" placeholder="Communication, Professionalism, Role Fit">
                            <div class="form-text text-muted">Separate multiple skills with commas.</div>
                            @if($addQuestionErrors->has('mapped_skills'))
                                <div class="invalid-feedback d-block" data-server-error>{{ $addQuestionErrors->first('mapped_skills') }}</div>
                            @endif

                            <input type="hidden" name="source_name" id="addSourceName" value="{{ $addQuestionHasErrors ? old('source_name') : '' }}">
                            <input type="hidden" name="source_url" id="addSourceUrl" value="{{ $addQuestionHasErrors ? old('source_url') : '' }}">
                            <input type="hidden" name="source_type" id="addSourceType" value="{{ $addQuestionHasErrors ? old('source_type') : '' }}">
                            <input type="hidden" name="ai_provider" id="addAiProvider" value="{{ $addQuestionHasErrors ? old('ai_provider') : '' }}">

                            <div id="addSourceBadge" class="mt-3" style="{{ $addQuestionHasErrors && old('source_name') ? '' : 'display:none;' }}background:var(--bg);border:1px solid var(--bd);border-radius:10px;padding:10px;color:var(--tx3);font-size:.8rem;">
                                @if($addQuestionHasErrors && old('source_name'))
                                    <i class="fa-solid fa-link me-1"></i> Source: {{ old('source_name') }}
                                @endif
                            </div>

                            @foreach(['source_name', 'source_url', 'source_type', 'ai_provider'] as $sourceField)
                                @if($addQuestionErrors->has($sourceField))
                                    <div class="invalid-feedback d-block" data-server-error>{{ $addQuestionErrors->first($sourceField) }}</div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4" data-loading-text="Saving...">
                        <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                        <span data-button-label>Save Question</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importQuestionsModal" tabindex="-1" aria-labelledby="importQuestionsModalLabel" aria-hidden="true" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form id="importQuestionsForm" action="{{ route('admin.questions.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" id="importQuestionsModalLabel" style="color:var(--tx)">Import Interview Questions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    @if($importQuestionErrors->any())
                        <div class="alert alert-danger" role="alert">{{ $importQuestionErrors->first('file') }}</div>
                    @endif

                    <p style="color:var(--tx3);font-size:.85rem;">
                        Upload a CSV exported from this Question Bank, or use the template. Existing questions in the same category are skipped safely; invalid files are rejected before any rows are saved.
                    </p>
                    <a href="{{ route('admin.questions.export', ['template' => 1]) }}" class="btn btn-sm btn-outline-info mb-3" download>
                        <i class="fa-solid fa-file-arrow-down me-1"></i> Download CSV Template
                    </a>

                    <label class="olbl" for="questionImportFile">CSV File</label>
                    <input class="form-control {{ $importQuestionErrors->has('file') ? 'is-invalid' : '' }}" id="questionImportFile" style="background:var(--bg);color:var(--tx);border:1px solid var(--bd)" type="file" name="file" accept=".csv,.txt,text/csv,text/plain" required>
                    <div class="form-text text-muted">Maximum file size: 5 MB. Maximum records: 2,000.</div>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4" data-loading-text="Importing...">
                        <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                        <span data-button-label>Import Questions</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Analytics Modal -->
<div class="modal fade" id="analyticsModal" tabindex="-1" aria-labelledby="analyticsModalLabel" aria-hidden="true" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                <h5 class="modal-title" id="analyticsModalLabel" style="color:var(--tx)"><i class="fa-solid fa-chart-line me-2"></i> Question Analytics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body text-center p-4" id="analyticsBody">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
            </div>
        </div>
    </div>
</div>

<!-- Question Generation Modal -->
<div class="modal fade" id="aiGenerateModal" tabindex="-1" aria-labelledby="aiGenerateModalLabel" aria-hidden="true" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form id="questionGeneratorForm">
                @csrf
                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <h5 class="modal-title" id="aiGenerateModalLabel" style="color:var(--tx)"><i class="fa-solid fa-wand-magic-sparkles me-2"></i> Generate Interview Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1)"></button>
                </div>
                <div class="modal-body">
                    <div id="questionGeneratorError" class="alert alert-danger d-none" role="alert"></div>

                    <label class="olbl" for="aiCatId">Interview Category</label>
                    <select class="oinp mb-3" id="aiCatId" required>
                        @forelse($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->title }}</option>
                        @empty
                            <option value="" selected disabled>No interview categories available</option>
                        @endforelse
                    </select>

                    <label class="olbl" for="aiProvider">Generation Provider</label>
                    <select class="oinp mb-3" id="aiProvider" required>
                        @foreach($aiProviderOptions ?? [] as $provider)
                            <option value="{{ $provider['key'] }}" {{ $provider['is_default'] ? 'selected' : '' }} {{ $provider['enabled'] ? '' : 'disabled' }}>
                                {{ $provider['label'] }}{{ $provider['enabled'] ? '' : ' (not configured)' }}
                            </option>
                        @endforeach
                    </select>

                    <label class="olbl" for="aiDataset">Reliable Question Source</label>
                    <select class="oinp mb-3" id="aiDataset" required>
                        <option value="auto">Auto-select from interview category</option>
                        @foreach($questionDatasets ?? [] as $key => $dataset)
                            <option value="{{ $key }}">{{ $dataset['name'] }}</option>
                        @endforeach
                    </select>

                    <label class="olbl" for="aiPosition">Target Position or Role</label>
                    <input type="text" class="oinp mb-3" id="aiPosition" maxlength="255" placeholder="e.g. Software Engineer" autocomplete="organization-title" required>

                    <label class="olbl" for="aiDiff">Difficulty</label>
                    <select class="oinp" id="aiDiff" required>
                        <option value="Easy">Easy</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="Hard">Hard</option>
                    </select>
                </div>
                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-outline-info" id="generateQuestionButton">
                        <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                        <span data-button-label>Generate Question</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Question Sources Modal -->
<div class="modal fade" id="datasetsModal" tabindex="-1" aria-labelledby="datasetsModalLabel" aria-hidden="true" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                <div>
                    <h5 class="modal-title" id="datasetsModalLabel" style="color:var(--tx)"><i class="fa-solid fa-globe me-2"></i> Interview Question Sources</h5>
                    <div class="text-muted mt-1" style="font-size:.8rem">Review reliable public sources and import their prepared question packs.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1)"></button>
            </div>
            <div class="modal-body">
                @if($datasetImportErrors->any())
                    <div class="alert alert-danger" role="alert">{{ $datasetImportErrors->first('dataset') }}</div>
                @endif

                <div class="row">
                    @forelse($questionDatasets ?? [] as $key => $dataset)
                        <div class="col-md-6 mb-3">
                            <div style="background:var(--bg);border:1px solid var(--bd);border-radius:12px;padding:16px;height:100%;display:flex;flex-direction:column;">
                                <div class="d-flex justify-content-between gap-2 mb-2">
                                    <h6 style="color:var(--tx);font-weight:700;margin:0;">{{ $dataset['name'] }}</h6>
                                    <span class="badge bg-info text-dark">{{ count($dataset['questions'] ?? []) }} questions</span>
                                </div>
                                <p style="color:var(--tx3);font-size:.8rem;flex-grow:1;margin-bottom:12px;">{{ $dataset['description'] }}</p>

                                <div class="mb-3" style="font-size:.75rem;">
                                    @foreach(array_slice($dataset['sources'] ?? [], 0, 3) as $source)
                                        <div class="mb-1">
                                            <i class="fa-solid fa-link me-1 text-info"></i>
                                            @if(!empty($source['url']))
                                                <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="text-info">{{ $source['name'] ?? 'Public source' }}</a>
                                            @else
                                                <span style="color:var(--tx3)">{{ $source['name'] ?? 'Reliable source' }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <form action="{{ route('admin.questions.import-dataset') }}" method="POST" class="question-dataset-import-form">
                                    @csrf
                                    <input type="hidden" name="dataset" value="{{ $key }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100" data-loading-text="Importing...">
                                        <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                                        <span data-button-label>Import Pack</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-secondary mb-0">No question source packs are currently available.</div>
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--bd)">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<form id="bulkDeleteForm" action="{{ route('admin.questions.bulk-delete') }}" method="POST" style="display:none;">
    @csrf
</form>

<script>
function filterCategory(categoryId) {
    const selectedCategory = String(categoryId);
    const categorySelect = document.getElementById('categoryFilterSelect');

    if (categorySelect && categorySelect.value !== selectedCategory) {
        categorySelect.value = selectedCategory;
    }

    document.querySelectorAll('.question-row').forEach(row => {
        row.style.display = selectedCategory === 'all' || row.dataset.categoryId === selectedCategory ? '' : 'none';
    });
}

function toggleAllQuestions(source) {
    document.querySelectorAll('.question-row:not([style*="display: none"]) .question-checkbox').forEach(checkbox => {
        checkbox.checked = source.checked;
    });
    toggleBulkDeleteBtn();
}

function toggleBulkDeleteBtn() {
    const checked = document.querySelectorAll('.question-checkbox:checked').length;
    const button = document.getElementById('btnBulkDelete');
    const selectAll = document.getElementById('selectAllQuestions');
    const mobileSelect = document.getElementById('selectAllMobile');

    if (button) button.disabled = checked === 0;
    if (checked === 0 && selectAll) selectAll.checked = false;
    if (checked === 0 && mobileSelect) mobileSelect.checked = false;
}

function submitBulkDelete() {
    const selected = Array.from(document.querySelectorAll('.question-checkbox:checked'));
    if (selected.length === 0) return;

    const confirmation = window.SpeakReadyAdminConfirm
        ? window.SpeakReadyAdminConfirm(`Delete ${selected.length} selected question(s)?`)
        : Promise.resolve(window.confirm(`Delete ${selected.length} selected question(s)?`));

    confirmation.then(confirmed => {
        if (!confirmed) return;

        const form = document.getElementById('bulkDeleteForm');
        form.querySelectorAll('input[name="question_ids[]"]').forEach(input => input.remove());
        selected.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'question_ids[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });
        form.submit();
    });
}

function clearGeneratedQuestionSource() {
    ['addSourceName', 'addSourceUrl', 'addSourceType', 'addAiProvider'].forEach(id => {
        const field = document.getElementById(id);
        if (field) field.value = '';
    });

    const sourceBadge = document.getElementById('addSourceBadge');
    if (sourceBadge) {
        sourceBadge.style.display = 'none';
        sourceBadge.innerHTML = '';
    }
}

function prepareManualQuestion() {
    const form = document.getElementById('addQuestionForm');
    if (!form) return;

    form.reset();
    form.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));
    form.querySelectorAll('[data-server-error]').forEach(error => error.remove());
    clearGeneratedQuestionSource();
}

function questionBankNotice(message) {
    if (window.SpeakReadyUiNotice) {
        window.SpeakReadyUiNotice(message);
        return;
    }

    window.alert(message);
}

function openAnalytics(questionId) {
    const modalElement = document.getElementById('analyticsModal');
    const body = document.getElementById('analyticsBody');
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
    body.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';

    fetch(`/admin/questions/${encodeURIComponent(questionId)}/analytics`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'Unable to load question analytics.');
            return data;
        })
        .then(data => {
            const averageScore = data.has_score_data ? `${Number(data.average_score) || 0}%` : 'N/A';
            const analyticsNote = data.has_score_data
                ? 'Data reflects submitted answers stored in the system.'
                : 'No scored answers have been recorded for this question yet.';

            body.innerHTML = `
                <div class="row">
                    <div class="col-6 mb-3"><div style="background:var(--bg);border-radius:12px;padding:16px;"><h6 style="color:var(--tx3);font-size:.8rem;margin-bottom:8px">Usage Count</h6><h2 style="color:var(--tx);margin:0;font-weight:700">${Number(data.used_count) || 0}</h2></div></div>
                    <div class="col-6 mb-3"><div style="background:var(--bg);border-radius:12px;padding:16px;"><h6 style="color:var(--tx3);font-size:.8rem;margin-bottom:8px">Average Score</h6><h2 style="color:var(--tx);margin:0;font-weight:700">${averageScore}</h2></div></div>
                </div>
                <p style="color:var(--tx3);font-size:.85rem;margin:0">${analyticsNote}</p>`;
        })
        .catch(error => {
            body.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtml(error.message || 'Unable to load question analytics.')}</div>`;
        });
}

async function generateAiQuestion(event) {
    event?.preventDefault();

    const form = document.getElementById('questionGeneratorForm');
    const button = document.getElementById('generateQuestionButton');
    const errorBox = document.getElementById('questionGeneratorError');
    if (!form || !button || !form.reportValidity()) return;

    errorBox.classList.add('d-none');
    errorBox.textContent = '';
    button.disabled = true;
    button.querySelector('.spinner-border')?.classList.remove('d-none');
    button.querySelector('[data-button-label]').textContent = 'Generating...';

    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), 60000);

    try {
        const response = await fetch("{{ route('admin.questions.ai-generate') }}", {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value
            },
            signal: controller.signal,
            body: JSON.stringify({
                category_id: document.getElementById('aiCatId').value,
                position: document.getElementById('aiPosition').value.trim(),
                difficulty: document.getElementById('aiDiff').value,
                ai_provider: document.getElementById('aiProvider').value,
                dataset: document.getElementById('aiDataset').value
            })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.question_text) {
            const validationMessage = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : null;
            throw new Error(validationMessage || data.message || data.error || 'Unable to generate a question right now.');
        }

        prepareManualQuestion();
        document.getElementById('addCatId').value = document.getElementById('aiCatId').value;
        document.getElementById('addType').value = data.type || 'Behavioral';
        document.getElementById('addDiff').value = data.difficulty || document.getElementById('aiDiff').value;
        document.getElementById('addQText').value = data.question_text;
        document.getElementById('addExpectedGuide').value = data.expected_guide || '';
        document.getElementById('addMappedSkills').value = Array.isArray(data.mapped_skills) ? data.mapped_skills.join(', ') : (data.mapped_skills || '');
        document.getElementById('addSourceName').value = data.source_name || '';
        document.getElementById('addSourceUrl').value = data.source_url || '';
        document.getElementById('addSourceType').value = data.source_type || '';
        document.getElementById('addAiProvider').value = data.ai_provider || 'local';

        const sourceBadge = document.getElementById('addSourceBadge');
        const sourceParts = [];
        if (data.source_name) sourceParts.push(`Source: ${escapeHtml(data.source_name)}`);
        if (data.dataset_name) sourceParts.push(`Pack: ${escapeHtml(data.dataset_name)}`);
        if (data.provider_label) sourceParts.push(`Provider: ${escapeHtml(data.provider_label)}`);
        if (sourceParts.length > 0) {
            sourceBadge.style.display = 'block';
            sourceBadge.innerHTML = `<i class="fa-solid fa-link me-1"></i>${sourceParts.join(' &middot; ')}`;
        }

        const generatorModalElement = document.getElementById('aiGenerateModal');
        const generatorModal = bootstrap.Modal.getOrCreateInstance(generatorModalElement);
        const showAddModal = () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addQuestionModal')).show();
            window.setTimeout(() => document.getElementById('addQText')?.focus(), 200);
        };

        if (generatorModalElement.classList.contains('show')) {
            generatorModalElement.addEventListener('hidden.bs.modal', showAddModal, { once: true });
            generatorModal.hide();
        } else {
            showAddModal();
        }

        if (data.notice) questionBankNotice(data.notice);
    } catch (error) {
        const message = error.name === 'AbortError'
            ? 'Question generation timed out. Please try again or use the reliable source provider.'
            : (error.message || 'Unable to generate a question right now.');
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    } finally {
        window.clearTimeout(timeout);
        button.disabled = false;
        button.querySelector('.spinner-border')?.classList.add('d-none');
        button.querySelector('[data-button-label]').textContent = 'Generate Question';
    }
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));
}

function bindQuestionFormLoading(form) {
    if (!form || form.dataset.loadingBound === 'true') return;
    form.dataset.loadingBound = 'true';

    form.addEventListener('submit', function () {
        const button = form.querySelector('button[type="submit"]');
        if (!button || button.disabled) return;

        button.disabled = true;
        button.querySelector('.spinner-border')?.classList.remove('d-none');
        const label = button.querySelector('[data-button-label]');
        if (label) label.textContent = button.dataset.loadingText || 'Working...';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('questionGeneratorForm')?.addEventListener('submit', generateAiQuestion);
    bindQuestionFormLoading(document.getElementById('addQuestionForm'));
    bindQuestionFormLoading(document.getElementById('importQuestionsForm'));
    document.querySelectorAll('.question-dataset-import-form').forEach(bindQuestionFormLoading);

    const initialModalId = @json(
        $addQuestionErrors->any()
            ? 'addQuestionModal'
            : ($importQuestionErrors->any()
                ? 'importQuestionsModal'
                : ($datasetImportErrors->any() ? 'datasetsModal' : null))
    );

    if (initialModalId && window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById(initialModalId)).show();
    }
});
</script>
