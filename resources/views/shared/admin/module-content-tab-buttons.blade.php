<li class="nav-item" role="presentation">
    <button class="nav-link oinp" id="resources-tab" data-bs-toggle="pill" data-bs-target="#resources" type="button" role="tab" style="width:auto;margin:0;">
        Resources <span class="badge bg-secondary ms-1">{{ $module->resources->count() }}</span>
    </button>
</li>
<li class="nav-item" role="presentation">
    <button class="nav-link oinp" id="quizzes-tab" data-bs-toggle="pill" data-bs-target="#quizzes" type="button" role="tab" style="width:auto;margin:0;">
        Quizzes <span class="badge bg-secondary ms-1">{{ $module->quizzes->count() }}</span>
    </button>
</li>
