@php
    $moduleCategories = collect($categories ?? [])
        ->map(fn ($category) => trim((string) $category))
        ->filter()
        ->unique(fn (string $category) => mb_strtolower($category))
        ->values();
    $defaultModuleErrors = $errors->getBag('default');
    $addModuleHasErrors = old('_module_form') === 'add' && $defaultModuleErrors->any();
    $addModuleErrors = $addModuleHasErrors
        ? $defaultModuleErrors
        : new \Illuminate\Support\MessageBag();
    $generateModuleErrors = $errors->getBag('generateModule');
    $generateModuleHasErrors = $generateModuleErrors->any();

    $addCategoryChoice = $addModuleHasErrors
        ? (string) old('category_choice', '')
        : ($moduleCategories->isEmpty() ? '__new__' : '');
    $addNewCategory = $addModuleHasErrors ? (string) old('new_category', '') : '';

    if ($addModuleHasErrors && $addCategoryChoice === '') {
        $submittedCategory = trim((string) old('category', ''));
        $knownCategory = $moduleCategories->first(
            fn (string $category) => strcasecmp($category, $submittedCategory) === 0
        );

        if ($knownCategory !== null) {
            $addCategoryChoice = $knownCategory;
        } elseif ($submittedCategory !== '') {
            $addCategoryChoice = '__new__';
            $addNewCategory = $submittedCategory;
        }
    }

    $showNewModuleCategory = $addCategoryChoice === '__new__';
@endphp

<!-- Add Interview Module Modal -->
<div class="modal fade" id="addModuleModal" tabindex="-1" aria-labelledby="addModuleModalLabel" aria-hidden="true" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.store') }}" method="POST" id="addModuleForm">
                @csrf
                <input type="hidden" name="_module_form" value="add">
                <input type="hidden" name="status" value="draft">

                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <div>
                        <h5 class="modal-title" id="addModuleModalLabel" style="color:var(--tx)">Add Interview Learning Module</h5>
                        <div class="text-muted mt-1" style="font-size:.8rem">Create a draft, then add lessons, resources, and quizzes in the module builder.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1)"></button>
                </div>

                <div class="modal-body">
                    @if($addModuleHasErrors)
                        <div class="alert alert-danger" role="alert" data-module-server-error>
                            The module was not created. Correct the highlighted fields and try again.
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="olbl" for="addModuleTitle">Module Title</label>
                            <input
                                class="oinp {{ $addModuleErrors->has('title') ? 'is-invalid' : '' }}"
                                type="text"
                                name="title"
                                id="addModuleTitle"
                                maxlength="255"
                                value="{{ $addModuleHasErrors ? old('title') : '' }}"
                                placeholder="e.g. Answering Tell Me About Yourself"
                                required
                            >
                            @if($addModuleErrors->has('title'))
                                <div class="invalid-feedback d-block" data-module-server-error>{{ $addModuleErrors->first('title') }}</div>
                            @endif

                            <label class="olbl mt-3" for="addModuleCategoryChoice">Category</label>
                            <select
                                class="oinp {{ $addModuleErrors->has('category') || $addModuleErrors->has('category_choice') ? 'is-invalid' : '' }}"
                                name="category_choice"
                                id="addModuleCategoryChoice"
                                required
                            >
                                <option value="" disabled {{ $addCategoryChoice === '' ? 'selected' : '' }}>Select a category...</option>
                                @foreach($moduleCategories as $moduleCategory)
                                    <option value="{{ $moduleCategory }}" {{ strcasecmp($addCategoryChoice, $moduleCategory) === 0 ? 'selected' : '' }}>{{ $moduleCategory }}</option>
                                @endforeach
                                <option value="__new__" {{ $showNewModuleCategory ? 'selected' : '' }}>+ Create New Category</option>
                            </select>

                            <div id="newModuleCategoryWrap" class="mt-2" style="{{ $showNewModuleCategory ? '' : 'display:none;' }}">
                                <label class="visually-hidden" for="addModuleNewCategory">New Category Name</label>
                                <input
                                    type="text"
                                    id="addModuleNewCategory"
                                    name="new_category"
                                    class="oinp {{ $addModuleErrors->has('new_category') || $addModuleErrors->has('category') ? 'is-invalid' : '' }}"
                                    maxlength="255"
                                    value="{{ $addNewCategory }}"
                                    placeholder="Enter the new category name"
                                    {{ $showNewModuleCategory ? 'required' : 'disabled' }}
                                >
                            </div>
                            @if($addModuleErrors->has('category') || $addModuleErrors->has('category_choice') || $addModuleErrors->has('new_category'))
                                <div class="invalid-feedback d-block" data-module-server-error>
                                    {{ $addModuleErrors->first('category') ?: ($addModuleErrors->first('category_choice') ?: $addModuleErrors->first('new_category')) }}
                                </div>
                            @endif

                            <label class="olbl mt-3" for="addModuleDifficulty">Difficulty Level</label>
                            <select class="oinp {{ $addModuleErrors->has('difficulty') ? 'is-invalid' : '' }}" name="difficulty" id="addModuleDifficulty" required>
                                @foreach(['Beginner', 'Intermediate', 'Advanced'] as $difficulty)
                                    <option value="{{ $difficulty }}" {{ ($addModuleHasErrors ? old('difficulty', 'Beginner') : 'Beginner') === $difficulty ? 'selected' : '' }}>{{ $difficulty }}</option>
                                @endforeach
                            </select>
                            @if($addModuleErrors->has('difficulty'))
                                <div class="invalid-feedback d-block" data-module-server-error>{{ $addModuleErrors->first('difficulty') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="olbl" for="addModuleDescription">Description</label>
                            <textarea
                                class="oinp {{ $addModuleErrors->has('description') ? 'is-invalid' : '' }}"
                                name="description"
                                id="addModuleDescription"
                                rows="7"
                                maxlength="10000"
                                placeholder="What learners will prepare, practice, revise, and check"
                            >{{ $addModuleHasErrors ? old('description') : '' }}</textarea>
                            @if($addModuleErrors->has('description'))
                                <div class="invalid-feedback d-block" data-module-server-error>{{ $addModuleErrors->first('description') }}</div>
                            @endif

                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="addModuleFeatured" {{ $addModuleHasErrors && old('is_featured') ? 'checked' : '' }}>
                                <label class="form-check-label olbl" for="addModuleFeatured">Mark as Featured</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4" data-loading-text="Creating...">
                        <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                        <span data-button-label>Create Interview Module</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate Interview Module Modal -->
<div class="modal fade" id="aiGenerateModuleModal" tabindex="-1" aria-labelledby="aiGenerateModuleModalLabel" aria-hidden="true" style="--bs-modal-bg:var(--sf)">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid var(--bd)">
            <form action="{{ route('admin.modules.generate') }}" method="POST" id="aiGenerateForm">
                @csrf
                <input type="hidden" name="_module_form" value="generate">

                <div class="modal-header" style="border-bottom:1px solid var(--bd)">
                    <div>
                        <h5 class="modal-title" id="aiGenerateModuleModalLabel" style="color:var(--tx)">
                            <i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>Generate Interview Module
                        </h5>
                        <div class="text-muted mt-1" style="font-size:.8rem">Creates a complete draft with at least two sanitized lessons.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1)"></button>
                </div>

                <div class="modal-body">
                    @if($generateModuleHasErrors)
                        <div class="alert alert-danger" role="alert" data-module-server-error>
                            The module could not be generated. Correct the highlighted fields and try again.
                        </div>
                    @endif

                    <p style="color:var(--tx3);font-size:.9rem;margin-bottom:20px;">
                        Describe the interview topic. If an AI provider is unavailable or returns incomplete content, a reliable action-focused draft is created automatically.
                    </p>

                    <label class="olbl" for="generateModulePrompt">Interview Topic or Goal</label>
                    <textarea
                        class="oinp {{ $generateModuleErrors->has('prompt') ? 'is-invalid' : '' }}"
                        name="prompt"
                        id="generateModulePrompt"
                        rows="4"
                        maxlength="1000"
                        placeholder="e.g. Preparing STAR answers for a customer support interview"
                        required
                    >{{ $generateModuleHasErrors ? old('prompt') : '' }}</textarea>
                    @if($generateModuleErrors->has('prompt'))
                        <div class="invalid-feedback d-block" data-module-server-error>{{ $generateModuleErrors->first('prompt') }}</div>
                    @endif

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="olbl" for="generateModuleCategory">Category</label>
                            <select class="oinp {{ $generateModuleErrors->has('category') ? 'is-invalid' : '' }}" name="category" id="generateModuleCategory">
                                <option value="">Auto-select</option>
                                @foreach($moduleCategories as $moduleCategory)
                                    <option value="{{ $moduleCategory }}" {{ $generateModuleHasErrors && old('category') === $moduleCategory ? 'selected' : '' }}>{{ $moduleCategory }}</option>
                                @endforeach
                            </select>
                            @if($generateModuleErrors->has('category'))
                                <div class="invalid-feedback d-block" data-module-server-error>{{ $generateModuleErrors->first('category') }}</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="olbl" for="generateModuleDifficulty">Difficulty</label>
                            <select class="oinp {{ $generateModuleErrors->has('difficulty') ? 'is-invalid' : '' }}" name="difficulty" id="generateModuleDifficulty">
                                <option value="">Auto-select</option>
                                @foreach(['Beginner', 'Intermediate', 'Advanced'] as $difficulty)
                                    <option value="{{ $difficulty }}" {{ $generateModuleHasErrors && old('difficulty') === $difficulty ? 'selected' : '' }}>{{ $difficulty }}</option>
                                @endforeach
                            </select>
                            @if($generateModuleErrors->has('difficulty'))
                                <div class="invalid-feedback d-block" data-module-server-error>{{ $generateModuleErrors->first('difficulty') }}</div>
                            @endif
                        </div>
                    </div>

                    <div id="aiLoadingIndicator" class="mt-3 d-none" role="status" aria-live="polite" style="text-align:center;padding:15px;border-radius:10px;background:rgba(59,130,246,0.1);">
                        <i class="fa-solid fa-circle-notch fa-spin text-primary" style="font-size:1.5rem;margin-bottom:10px;"></i>
                        <h6 style="color:var(--tx);margin:0;">Generating Interview Actions...</h6>
                        <p style="color:var(--tx3);font-size:.8rem;margin:0;">Keep this page open while the draft and lessons are created.</p>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid var(--bd)">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="bgrd btn px-4" id="aiGenerateBtn" data-loading-text="Generating...">
                        <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                        <span data-button-label>Generate Module</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function syncCustomModuleCategory() {
    const categoryChoice = document.getElementById('addModuleCategoryChoice');
    const categoryWrap = document.getElementById('newModuleCategoryWrap');
    const newCategory = document.getElementById('addModuleNewCategory');
    if (!categoryChoice || !categoryWrap || !newCategory) return;

    const useNewCategory = categoryChoice.value === '__new__';
    categoryWrap.style.display = useNewCategory ? '' : 'none';
    newCategory.disabled = !useNewCategory;
    newCategory.required = useNewCategory;
    if (!useNewCategory) newCategory.value = '';
}

function clearModuleFormErrors(form) {
    if (!form) return;
    form.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));
    form.querySelectorAll('[data-module-server-error]').forEach(error => error.remove());
}

function prepareManualModule() {
    const form = document.getElementById('addModuleForm');
    if (!form) return;

    form.reset();
    clearModuleFormErrors(form);
    syncCustomModuleCategory();
}

function prepareGeneratedModule() {
    const form = document.getElementById('aiGenerateForm');
    if (!form) return;

    form.reset();
    clearModuleFormErrors(form);
    document.getElementById('aiLoadingIndicator')?.classList.add('d-none');
}

function bindModuleFormLoading(form, loadingIndicator = null) {
    if (!form || form.dataset.loadingBound === 'true') return;
    form.dataset.loadingBound = 'true';

    form.addEventListener('submit', function () {
        const button = form.querySelector('button[type="submit"]');
        if (!button || button.disabled) return;

        button.disabled = true;
        button.querySelector('.spinner-border')?.classList.remove('d-none');
        const label = button.querySelector('[data-button-label]');
        if (label) label.textContent = button.dataset.loadingText || 'Working...';
        loadingIndicator?.classList.remove('d-none');
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const categoryChoice = document.getElementById('addModuleCategoryChoice');
    categoryChoice?.addEventListener('change', syncCustomModuleCategory);
    syncCustomModuleCategory();

    bindModuleFormLoading(document.getElementById('addModuleForm'));
    bindModuleFormLoading(
        document.getElementById('aiGenerateForm'),
        document.getElementById('aiLoadingIndicator')
    );

    const initialModuleModalId = @json(
        $addModuleHasErrors
            ? 'addModuleModal'
            : ($generateModuleHasErrors ? 'aiGenerateModuleModal' : null)
    );

    if (initialModuleModalId && window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById(initialModuleModalId)).show();
    }
});
</script>
