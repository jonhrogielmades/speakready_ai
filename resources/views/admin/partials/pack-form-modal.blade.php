@php
    $useOldInput = old('_pack_modal_id') === $modalId;
    $fieldValue = fn (string $name, mixed $default = '') => $useOldInput ? old($name, $default) : $default;
    $questionTypesText = $pack ? implode("\n", $pack->question_types ?? []) : '';
    $sampleQuestionsText = $pack ? implode("\n", $pack->sample_questions ?? []) : '';
    $pressureModeChecked = $useOldInput ? old('pressure_mode') : ($pack->pressure_mode ?? false);
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" action="{{ $action }}" method="POST">
            @csrf
            <input type="hidden" name="_pack_modal_id" value="{{ $modalId }}">
            @if($method !== 'POST')
                @method($method)
            @endif
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($useOldInput && $errors->any())
                    <div class="alert alert-danger">
                        <strong>Please fix the highlighted fields.</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Pack Name</label>
                        <input class="form-control @if($useOldInput && $errors->has('name')) is-invalid @endif" name="name" value="{{ $fieldValue('name', $pack->name ?? '') }}" required>
                        @if($useOldInput && $errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="active" @selected($fieldValue('status', $pack->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected($fieldValue('status', $pack->status ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input class="form-control @if($useOldInput && $errors->has('slug')) is-invalid @endif" name="slug" value="{{ $fieldValue('slug', $pack->slug ?? '') }}" placeholder="Auto-generated if empty">
                        @if($useOldInput && $errors->has('slug'))
                            <div class="invalid-feedback">{{ $errors->first('slug') }}</div>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Company</label>
                        <input class="form-control" name="company" value="{{ $fieldValue('company', $pack->company ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role Family</label>
                        <input class="form-control" name="role_family" value="{{ $fieldValue('role_family', $pack->role_family ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Difficulty</label>
                        <select class="form-select" name="difficulty" required>
                            @foreach(['easy', 'medium', 'hard'] as $difficulty)
                                <option value="{{ $difficulty }}" @selected($fieldValue('difficulty', $pack->difficulty ?? 'medium') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Interview Focus</label>
                        <input class="form-control @if($useOldInput && $errors->has('interview_focus')) is-invalid @endif" name="interview_focus" value="{{ $fieldValue('interview_focus', $pack->interview_focus ?? 'General Practice') }}" required>
                        @if($useOldInput && $errors->has('interview_focus'))
                            <div class="invalid-feedback">{{ $errors->first('interview_focus') }}</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Company Persona</label>
                        <input class="form-control" name="company_persona" value="{{ $fieldValue('company_persona', $pack->company_persona ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Question Types</label>
                        <textarea class="form-control" name="question_types_text" rows="4" placeholder="Behavioral&#10;Situational">{{ $fieldValue('question_types_text', $questionTypesText) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sample Questions</label>
                        <textarea class="form-control" name="sample_questions_text" rows="4" placeholder="Tell me about a time...">{{ $fieldValue('sample_questions_text', $sampleQuestionsText) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ $fieldValue('description', $pack->description ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="pressure_mode" value="1" @checked($pressureModeChecked)>
                            <label class="form-check-label">Enable pressure mode defaults for users</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Pack</button>
            </div>
        </form>
    </div>
</div>
