<div class="tab-pane fade" id="resources" role="tabpanel" aria-labelledby="resources-tab">
    <div class="admin-module-basic-card mb-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
        <h5>Add resource</h5>
        <form action="{{ route('admin.modules.resources.store', $module) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-12 col-md-5"><label class="olbl" for="newResourceTitle">Title</label><input class="oinp w-100" id="newResourceTitle" name="title" maxlength="255" required></div>
            <div class="col-12 col-md-5"><label class="olbl" for="newResourceFile">File (up to 10 MB)</label><input class="oinp w-100" id="newResourceFile" name="file" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.png,.jpg,.jpeg" required></div>
            <div class="col-12 col-md-2"><button class="btn btn-primary w-100" type="submit">Upload</button></div>
        </form>
    </div>
    @forelse($module->resources as $resource)
        <div class="admin-module-basic-card mb-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:14px;padding:16px;">
            <form action="{{ route('admin.modules.resources.update', [$module, $resource]) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf @method('PUT')
                <div class="col-12 col-md-5"><label class="olbl" for="resourceTitle{{ $resource->id }}">Title</label><input class="oinp w-100" id="resourceTitle{{ $resource->id }}" name="title" value="{{ $resource->title }}" maxlength="255" required></div>
                <div class="col-12 col-md-5"><label class="olbl" for="resourceFile{{ $resource->id }}">Replace file (optional)</label><input class="oinp w-100" id="resourceFile{{ $resource->id }}" name="file" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.png,.jpg,.jpeg"></div>
                <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Save</button></div>
            </form>
            <div class="d-flex align-items-center gap-2 flex-wrap mt-2">
                <span class="text-muted small">{{ $resource->file_type ?: 'File' }}</span>
                <form action="{{ route('admin.modules.resources.destroy', [$module, $resource]) }}" method="POST" onsubmit="return window.SpeakReadyAdminConfirm(event, this, 'Delete this resource?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm" type="submit">Delete resource</button>
                </form>
            </div>
        </div>
    @empty
        <p class="text-muted">No resources uploaded yet.</p>
    @endforelse
</div>

<div class="tab-pane fade" id="practice" role="tabpanel" aria-labelledby="practice-tab">
    <div class="admin-module-basic-card mb-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
        <h5>Add practice activity</h5>
        <form action="{{ route('admin.modules.practice.store', $module) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-12 col-md-7"><label class="olbl" for="newPracticeTitle">Activity title</label><input class="oinp w-100" id="newPracticeTitle" name="title" maxlength="255" required></div>
            <div class="col-12 col-md-5"><label class="olbl" for="newPracticeType">Type</label><select class="oinp w-100" id="newPracticeType" name="type"><option value="written-practice">Written answer</option><option value="reflection">Reflection</option><option value="spoken-rehearsal">Spoken rehearsal reflection</option></select></div>
            <div class="col-12"><label class="olbl" for="newPracticeDescription">Instructions</label><textarea class="oinp w-100" id="newPracticeDescription" name="description" rows="3" minlength="20" maxlength="2000" required></textarea></div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Add Activity</button></div>
        </form>
    </div>
    @forelse($module->activities as $activity)
        <div class="admin-module-basic-card mb-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
            <form action="{{ route('admin.modules.practice.update', [$module, $activity]) }}" method="POST" class="row g-2 align-items-end">
                @csrf @method('PUT')
                <div class="col-12 col-md-7"><label class="olbl" for="practiceTitle{{ $activity->id }}">Activity title</label><input class="oinp w-100" id="practiceTitle{{ $activity->id }}" name="title" value="{{ $activity->title }}" maxlength="255" required></div>
                <div class="col-12 col-md-5"><label class="olbl" for="practiceType{{ $activity->id }}">Type</label><select class="oinp w-100" id="practiceType{{ $activity->id }}" name="type"><option value="written-practice" @selected($activity->type === 'written-practice')>Written answer</option><option value="reflection" @selected($activity->type === 'reflection')>Reflection</option><option value="spoken-rehearsal" @selected($activity->type === 'spoken-rehearsal')>Spoken rehearsal reflection</option>@unless(in_array($activity->type, ['written-practice', 'reflection', 'spoken-rehearsal'], true))<option value="{{ $activity->type }}" selected>{{ ucwords(str_replace(['-', '_'], ' ', $activity->type)) }}</option>@endunless</select></div>
                <div class="col-12"><label class="olbl" for="practiceDescription{{ $activity->id }}">Instructions</label><textarea class="oinp w-100" id="practiceDescription{{ $activity->id }}" name="description" rows="3" minlength="20" maxlength="2000" required>{{ $activity->description }}</textarea></div>
                <div class="col-12"><button class="btn btn-outline-primary btn-sm" type="submit">Save Activity</button></div>
            </form>
            <form action="{{ route('admin.modules.practice.destroy', [$module, $activity]) }}" method="POST" class="mt-2" onsubmit="return window.SpeakReadyAdminConfirm(event, this, 'Delete this activity and saved learner responses?');">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm" type="submit">Delete activity</button>
            </form>
        </div>
    @empty
        <p class="text-muted">No practice activities added yet.</p>
    @endforelse
</div>

<div class="tab-pane fade" id="quizzes" role="tabpanel" aria-labelledby="quizzes-tab">
    <div class="admin-module-basic-card mb-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
        <h5>Add quiz</h5>
        <p class="text-muted small">A quiz starts with one question so learners never see an empty quiz.</p>
        <form action="{{ route('admin.modules.quizzes.store', $module) }}" method="POST">
            @csrf
            <div class="row g-2 mb-3">
                <div class="col-12 col-md-8"><label class="olbl" for="newQuizTitle">Quiz title</label><input class="oinp w-100" id="newQuizTitle" name="title" maxlength="255" required></div>
                <div class="col-12 col-md-4"><label class="olbl" for="newQuizPassing">Passing score %</label><input class="oinp w-100" id="newQuizPassing" name="passing_score" type="number" min="1" max="100" value="70" required></div>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4"><label class="olbl" for="newQuizType">First question type</label><select class="oinp w-100" id="newQuizType" name="question_type" required><option value="multiple_choice">Multiple choice</option><option value="true_false">True or false</option><option value="short_answer">Short answer</option></select></div>
                <div class="col-12 col-md-8"><label class="olbl" for="newQuizQuestion">First question</label><input class="oinp w-100" id="newQuizQuestion" name="question_text" maxlength="2000" required></div>
                <div class="col-12 col-md-7"><label class="olbl" for="newQuizOptions">Options, one per line (multiple choice only)</label><textarea class="oinp w-100" id="newQuizOptions" name="options" rows="3" maxlength="5000" placeholder="First option&#10;Second option"></textarea></div>
                <div class="col-12 col-md-5"><label class="olbl" for="newQuizAnswer">Correct answer</label><input class="oinp w-100" id="newQuizAnswer" name="correct_answer" maxlength="255" required placeholder="Exact option, True/False, or short answer"></div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Add Quiz</button></div>
            </div>
        </form>
    </div>
    @forelse($module->quizzes as $quiz)
        <div class="admin-module-basic-card mb-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:20px;">
            <form action="{{ route('admin.modules.quizzes.update', [$module, $quiz]) }}" method="POST" class="row g-2 align-items-end">
                @csrf @method('PUT')
                <div class="col-12 col-md-7"><label class="olbl" for="quizTitle{{ $quiz->id }}">Quiz title</label><input class="oinp w-100" id="quizTitle{{ $quiz->id }}" name="title" value="{{ $quiz->title }}" maxlength="255" required></div>
                <div class="col-12 col-md-3"><label class="olbl" for="quizPassing{{ $quiz->id }}">Passing score %</label><input class="oinp w-100" id="quizPassing{{ $quiz->id }}" name="passing_score" type="number" min="1" max="100" value="{{ $quiz->passing_score }}" required></div>
                <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Save Quiz</button></div>
            </form>
            <form action="{{ route('admin.modules.quizzes.destroy', [$module, $quiz]) }}" method="POST" class="mt-2" onsubmit="return window.SpeakReadyAdminConfirm(event, this, 'Delete this quiz and its questions?');">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm" type="submit">Delete quiz</button>
            </form>
            <h6 class="mt-4">Questions</h6>
            @foreach($quiz->questions as $question)
                <details class="border rounded p-2 mb-2">
                    <summary>{{ $question->question_text }}</summary>
                    <div class="mt-3">
                        @include('shared.admin.module-question-form', ['action' => route('admin.modules.quizzes.questions.update', [$module, $quiz, $question]), 'prefix' => 'question'.$question->id, 'question' => $question])
                        <form action="{{ route('admin.modules.quizzes.questions.destroy', [$module, $quiz, $question]) }}" method="POST" class="mt-2" onsubmit="return window.SpeakReadyAdminConfirm(event, this, 'Delete this question?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm" type="submit">Delete question</button>
                        </form>
                    </div>
                </details>
            @endforeach
            <details class="border rounded p-2 mt-3">
                <summary>Add question</summary>
                <div class="mt-3">@include('shared.admin.module-question-form', ['action' => route('admin.modules.quizzes.questions.store', [$module, $quiz]), 'prefix' => 'addQuestion'.$quiz->id, 'question' => null])</div>
            </details>
        </div>
    @empty
        <p class="text-muted">No quizzes added yet.</p>
    @endforelse
</div>
