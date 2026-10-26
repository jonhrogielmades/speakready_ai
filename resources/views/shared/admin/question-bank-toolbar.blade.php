@php($hasQuestionCategories = $categories->isNotEmpty())

<button
    type="button"
    class="btn btn-outline-info py-2"
    style="font-size:.85rem"
    data-bs-toggle="modal"
    data-bs-target="#aiGenerateModal"
    {{ $hasQuestionCategories ? '' : 'disabled' }}
    title="{{ $hasQuestionCategories ? 'Generate a role-specific interview question' : 'Create an interview category first' }}"
>
    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
    Generate Interview Question
</button>

<a
    href="{{ route('admin.questions.export') }}"
    class="btn btn-outline-secondary py-2"
    style="font-size:.85rem"
    download
    title="Download every Question Bank record as a reusable CSV file"
>
    <i class="fa-solid fa-download me-1"></i>
    Export
</a>

<button
    type="button"
    class="btn btn-outline-secondary py-2"
    style="font-size:.85rem"
    data-bs-toggle="modal"
    data-bs-target="#importQuestionsModal"
    title="Import questions from a CSV file"
>
    <i class="fa-solid fa-upload me-1"></i>
    Import
</button>

<button
    type="button"
    class="btn btn-outline-primary py-2"
    style="font-size:.85rem"
    data-bs-toggle="modal"
    data-bs-target="#datasetsModal"
    title="Review and import reliable interview question packs"
>
    <i class="fa-solid fa-globe me-1"></i>
    Interview Question Sources
</button>

<button
    type="button"
    class="bgrd btn px-3 py-2"
    style="font-size:.85rem"
    data-bs-toggle="modal"
    data-bs-target="#addQuestionModal"
    onclick="prepareManualQuestion()"
    {{ $hasQuestionCategories ? '' : 'disabled' }}
    title="{{ $hasQuestionCategories ? 'Create a new interview question' : 'Create an interview category first' }}"
>
    <i class="fa-solid fa-plus me-1"></i>
    Add Interview Question
</button>
