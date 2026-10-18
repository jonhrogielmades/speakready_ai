<div class="tab-pane fade" id="resources" role="tabpanel" aria-labelledby="resources-tab">
    <div class="card mb-4" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;">
        <div class="card-body p-4">
            <h5 style="color:var(--tx);font-weight:700;">Add Downloadable Resource</h5>
            <p class="small mb-3" style="color:var(--tx3);">Upload a PDF, Word, PowerPoint, or text file. Published modules show it in the user Resources tab.</p>
            <form action="{{ route('admin.modules.resources.store', $module) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-5">
                        <label class="olbl" for="newResourceTitle">Resource title</label>
                        <input class="oinp w-100" id="newResourceTitle" name="title" type="text" maxlength="255" required>
                    </div>
                    <div class="col-12 col-lg-5">
                        <label class="olbl" for="newResourceFile">File (maximum 10 MB)</label>
                        <input class="form-control" id="newResourceFile" name="file" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" required>
                    </div>
                    <div class="col-12 col-lg-2 d-grid">
                        <button class="bgrd btn" type="submit"><i class="fa-solid fa-upload me-1"></i> Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @forelse($module->resources as $resource)
        <div class="card mb-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:14px;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div>
                        <strong style="color:var(--tx);">{{ $resource->title }}</strong>
                        <span class="badge bg-secondary ms-2">{{ strtoupper($resource->file_type ?: 'file') }}</span>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ asset('storage/'.$resource->file_path) }}" target="_blank" rel="noopener noreferrer">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open current file
                    </a>
                </div>
                <form action="{{ route('admin.modules.resources.update', [$module, $resource]) }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-5">
                            <label class="olbl" for="resourceTitle{{ $resource->id }}">Title</label>
                            <input class="oinp w-100" id="resourceTitle{{ $resource->id }}" name="title" type="text" maxlength="255" value="{{ $resource->title }}" required>
                        </div>
                        <div class="col-12 col-lg-5">
                            <label class="olbl" for="resourceFile{{ $resource->id }}">Replace file (optional)</label>
                            <input class="form-control" id="resourceFile{{ $resource->id }}" name="file" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt">
                        </div>
                        <div class="col-12 col-lg-2 d-grid">
                            <button class="btn btn-outline-primary" type="submit">Save</button>
                        </div>
                    </div>
                </form>
                <form action="{{ route('admin.modules.resources.destroy', [$module, $resource]) }}" method="POST" class="mt-3 text-end" onsubmit="return confirm('Delete this resource and its uploaded file?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete resource</button>
                </form>
            </div>
        </div>
    @empty
        <div class="text-center py-5" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;color:var(--tx3);">
            No downloadable resources have been added.
        </div>
    @endforelse
</div>

<div class="tab-pane fade" id="quizzes" role="tabpanel" aria-labelledby="quizzes-tab">
    <div class="card mb-4" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;">
        <div class="card-body p-4">
            <h5 style="color:var(--tx);font-weight:700;">Add Module Quiz</h5>
            <form action="{{ route('admin.modules.quizzes.store', $module) }}" method="POST">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-7">
                        <label class="olbl" for="newQuizTitle">Quiz title</label>
                        <input class="oinp w-100" id="newQuizTitle" name="title" type="text" maxlength="255" required>
                    </div>
                    <div class="col-12 col-lg-3">
                        <label class="olbl" for="newQuizPassingScore">Passing score (%)</label>
                        <input class="oinp w-100" id="newQuizPassingScore" name="passing_score" type="number" min="1" max="100" value="70" required>
                    </div>
                    <div class="col-12 col-lg-2 d-grid">
                        <button class="bgrd btn" type="submit"><i class="fa-solid fa-plus me-1"></i> Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @forelse($module->quizzes as $quiz)
        <section class="card mb-4" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <h5 class="mb-0" style="color:var(--tx);font-weight:700;">{{ $quiz->title }}</h5>
                    <span class="badge bg-primary">{{ $quiz->questions->count() }} questions</span>
                </div>

                <form action="{{ route('admin.modules.quizzes.update', [$module, $quiz]) }}" method="POST" class="mt-3">
                    @csrf @method('PUT')
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-7">
                            <label class="olbl" for="quizTitle{{ $quiz->id }}">Quiz title</label>
                            <input class="oinp w-100" id="quizTitle{{ $quiz->id }}" name="title" type="text" maxlength="255" value="{{ $quiz->title }}" required>
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="olbl" for="quizPassingScore{{ $quiz->id }}">Passing score (%)</label>
                            <input class="oinp w-100" id="quizPassingScore{{ $quiz->id }}" name="passing_score" type="number" min="1" max="100" value="{{ $quiz->passing_score }}" required>
                        </div>
                        <div class="col-12 col-lg-2 d-grid">
                            <button class="btn btn-outline-primary" type="submit">Save quiz</button>
                        </div>
                    </div>
                </form>
                <form action="{{ route('admin.modules.quizzes.destroy', [$module, $quiz]) }}" method="POST" class="mt-2 text-end" onsubmit="return confirm('Delete this quiz and all of its questions?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete quiz</button>
                </form>

                <hr style="border-color:var(--bd);">
                <h6 style="color:var(--tx);font-weight:700;">Questions</h6>
                @forelse($quiz->questions as $question)
                    <div class="p-3 mb-3" style="border:1px solid var(--bd);border-radius:12px;background:var(--bg);">
                        <form action="{{ route('admin.modules.quizzes.questions.update', [$module, $quiz, $question]) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="row g-3">
                                <div class="col-12 col-lg-3">
                                    <label class="olbl" for="questionType{{ $question->id }}">Type</label>
                                    <select class="oinp w-100" id="questionType{{ $question->id }}" name="type" required>
                                        <option value="multiple_choice" @selected($question->type === 'multiple_choice')>Multiple choice</option>
                                        <option value="true_false" @selected($question->type === 'true_false')>True / False</option>
                                        <option value="short_answer" @selected($question->type === 'short_answer')>Short answer</option>
                                    </select>
                                </div>
                                <div class="col-12 col-lg-9">
                                    <label class="olbl" for="questionText{{ $question->id }}">Question</label>
                                    <input class="oinp w-100" id="questionText{{ $question->id }}" name="question_text" type="text" maxlength="2000" value="{{ $question->question_text }}" required>
                                </div>
                                <div class="col-12 col-lg-7">
                                    <label class="olbl" for="questionOptions{{ $question->id }}">Options (one per line; multiple choice only)</label>
                                    <textarea class="oinp w-100" id="questionOptions{{ $question->id }}" name="options" rows="3">{{ implode("\n", $question->options ?? []) }}</textarea>
                                </div>
                                <div class="col-12 col-lg-5">
                                    <label class="olbl" for="questionAnswer{{ $question->id }}">Correct answer</label>
                                    <input class="oinp w-100" id="questionAnswer{{ $question->id }}" name="correct_answer" type="text" maxlength="500" value="{{ $question->correct_answer }}" required>
                                </div>
                            </div>
                            <div class="mt-3 text-end">
                                <button class="btn btn-sm btn-outline-primary" type="submit">Save question</button>
                            </div>
                        </form>
                        <form action="{{ route('admin.modules.quizzes.questions.destroy', [$module, $quiz, $question]) }}" method="POST" class="mt-2 text-end" onsubmit="return confirm('Delete this quiz question?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete question</button>
                        </form>
                    </div>
                @empty
                    <p class="small" style="color:var(--tx3);">No questions have been added to this quiz.</p>
                @endforelse

                <div class="p-3 mt-3" style="border:1px dashed var(--bd);border-radius:12px;">
                    <h6 style="color:var(--tx);font-weight:700;">Add Question</h6>
                    <form action="{{ route('admin.modules.quizzes.questions.store', [$module, $quiz]) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-lg-3">
                                <label class="olbl" for="newQuestionType{{ $quiz->id }}">Type</label>
                                <select class="oinp w-100" id="newQuestionType{{ $quiz->id }}" name="type" required>
                                    <option value="multiple_choice">Multiple choice</option>
                                    <option value="true_false">True / False</option>
                                    <option value="short_answer">Short answer</option>
                                </select>
                            </div>
                            <div class="col-12 col-lg-9">
                                <label class="olbl" for="newQuestionText{{ $quiz->id }}">Question</label>
                                <input class="oinp w-100" id="newQuestionText{{ $quiz->id }}" name="question_text" type="text" maxlength="2000" required>
                            </div>
                            <div class="col-12 col-lg-7">
                                <label class="olbl" for="newQuestionOptions{{ $quiz->id }}">Options (one per line; multiple choice only)</label>
                                <textarea class="oinp w-100" id="newQuestionOptions{{ $quiz->id }}" name="options" rows="3" placeholder="Option A&#10;Option B"></textarea>
                            </div>
                            <div class="col-12 col-lg-5">
                                <label class="olbl" for="newQuestionAnswer{{ $quiz->id }}">Correct answer</label>
                                <input class="oinp w-100" id="newQuestionAnswer{{ $quiz->id }}" name="correct_answer" type="text" maxlength="500" required>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <button class="bgrd btn btn-sm" type="submit"><i class="fa-solid fa-plus me-1"></i> Add question</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    @empty
        <div class="text-center py-5" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;color:var(--tx3);">
            No module quizzes have been added.
        </div>
    @endforelse
</div>
