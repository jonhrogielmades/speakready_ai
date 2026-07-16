@php
    $useOldInput = old('_pack_modal_id') === $modalId;
    $fieldValue = fn (string $name, mixed $default = '') => $useOldInput ? old($name, $default) : $default;
    $fieldId = fn (string $name) => $modalId.'_'.$name;
    $questionTypesText = $pack ? implode("\n", $pack->question_types ?? []) : '';
    $sampleQuestionsText = $pack ? implode("\n", $pack->sample_questions ?? []) : '';
    $pressureModeChecked = $useOldInput ? old('pressure_mode') : ($pack->pressure_mode ?? false);
@endphp
<div class="modal fade pack-form-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
                        <label class="form-label" for="{{ $fieldId('name') }}">Pack Name</label>
                        <input id="{{ $fieldId('name') }}" class="form-control @if($useOldInput && $errors->has('name')) is-invalid @endif" name="name" value="{{ $fieldValue('name', $pack->name ?? '') }}" required>
                        @if($useOldInput && $errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="{{ $fieldId('status') }}">Status</label>
                        <select id="{{ $fieldId('status') }}" class="form-select" name="status" required>
                            <option value="active" @selected($fieldValue('status', $pack->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected($fieldValue('status', $pack->status ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="{{ $fieldId('slug') }}">Slug</label>
                        <input id="{{ $fieldId('slug') }}" class="form-control @if($useOldInput && $errors->has('slug')) is-invalid @endif" name="slug" value="{{ $fieldValue('slug', $pack->slug ?? '') }}" placeholder="Auto-generated if empty">
                        @if($useOldInput && $errors->has('slug'))
                            <div class="invalid-feedback">{{ $errors->first('slug') }}</div>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="{{ $fieldId('company') }}">Company</label>
                        <input id="{{ $fieldId('company') }}" class="form-control" name="company" value="{{ $fieldValue('company', $pack->company ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="{{ $fieldId('role_family') }}">Role Family</label>
                        <input id="{{ $fieldId('role_family') }}" class="form-control" name="role_family" value="{{ $fieldValue('role_family', $pack->role_family ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="{{ $fieldId('difficulty') }}">Difficulty</label>
                        <select id="{{ $fieldId('difficulty') }}" class="form-select" name="difficulty" required>
                            @foreach(['easy', 'medium', 'hard'] as $difficulty)
                                <option value="{{ $difficulty }}" @selected($fieldValue('difficulty', $pack->difficulty ?? 'medium') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="{{ $fieldId('interview_focus') }}">Interview Focus</label>
                        <input id="{{ $fieldId('interview_focus') }}" class="form-control @if($useOldInput && $errors->has('interview_focus')) is-invalid @endif" name="interview_focus" value="{{ $fieldValue('interview_focus', $pack->interview_focus ?? 'General Practice') }}" required>
                        @if($useOldInput && $errors->has('interview_focus'))
                            <div class="invalid-feedback">{{ $errors->first('interview_focus') }}</div>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="{{ $fieldId('company_persona') }}">Company Persona</label>
                        <input id="{{ $fieldId('company_persona') }}" class="form-control" name="company_persona" value="{{ $fieldValue('company_persona', $pack->company_persona ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="{{ $fieldId('question_types_text') }}">Question Types</label>
                        <textarea id="{{ $fieldId('question_types_text') }}" class="form-control" name="question_types_text" rows="3" placeholder="Behavioral&#10;Situational">{{ $fieldValue('question_types_text', $questionTypesText) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="{{ $fieldId('sample_questions_text') }}">Sample Questions</label>
                        <textarea id="{{ $fieldId('sample_questions_text') }}" class="form-control" name="sample_questions_text" rows="3" placeholder="Tell me about a time...">{{ $fieldValue('sample_questions_text', $sampleQuestionsText) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="{{ $fieldId('description') }}">Description</label>
                        <textarea id="{{ $fieldId('description') }}" class="form-control" name="description" rows="2">{{ $fieldValue('description', $pack->description ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check pack-pressure-check">
                            <input id="{{ $fieldId('pressure_mode') }}" class="form-check-input pack-pressure-input" type="checkbox" name="pressure_mode" value="1" @checked($pressureModeChecked)>
                            <label class="form-check-label pack-pressure-label" for="{{ $fieldId('pressure_mode') }}">Enable pressure mode defaults for users</label>
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
