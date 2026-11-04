@php
    $editingQuestion = $question ?? null;
    $questionOptions = $editingQuestion?->options ? implode("\n", $editingQuestion->options) : '';
@endphp
<form action="{{ $action }}" method="POST" class="row g-2 align-items-end">
    @csrf
    @if($editingQuestion) @method('PUT') @endif
    <div class="col-12 col-md-4">
        <label class="olbl" for="{{ $prefix }}Type">Question type</label>
        <select class="oinp w-100" id="{{ $prefix }}Type" name="question_type" required>
            <option value="multiple_choice" @selected(($editingQuestion?->type ?? 'multiple_choice') === 'multiple_choice')>Multiple choice</option>
            <option value="true_false" @selected($editingQuestion?->type === 'true_false')>True or false</option>
            <option value="short_answer" @selected($editingQuestion?->type === 'short_answer')>Short answer</option>
        </select>
    </div>
    <div class="col-12 col-md-8">
        <label class="olbl" for="{{ $prefix }}Text">Question</label>
        <input class="oinp w-100" id="{{ $prefix }}Text" name="question_text" value="{{ $editingQuestion?->question_text }}" maxlength="2000" required>
    </div>
    <div class="col-12 col-md-7">
        <label class="olbl" for="{{ $prefix }}Options">Options, one per line (multiple choice only)</label>
        <textarea class="oinp w-100" id="{{ $prefix }}Options" name="options" rows="3" maxlength="5000" placeholder="First option&#10;Second option">{{ $questionOptions }}</textarea>
    </div>
    <div class="col-12 col-md-5">
        <label class="olbl" for="{{ $prefix }}Answer">Correct answer</label>
        <input class="oinp w-100" id="{{ $prefix }}Answer" name="correct_answer" value="{{ $editingQuestion?->correct_answer }}" maxlength="255" required placeholder="Exact option, True/False, or short answer">
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-primary btn-sm">{{ $editingQuestion ? 'Update Question' : 'Add Question' }}</button>
    </div>
</form>
