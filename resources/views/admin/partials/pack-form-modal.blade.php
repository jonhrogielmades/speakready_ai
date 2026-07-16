@php
    $questionTypesText = $pack ? implode("\n", $pack->question_types ?? []) : '';
    $sampleQuestionsText = $pack ? implode("\n", $pack->sample_questions ?? []) : '';
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" action="{{ $action }}" method="POST">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Pack Name</label>
                        <input class="form-control" name="name" value="{{ old('name', $pack->name ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="active" @selected(old('status', $pack->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $pack->status ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input class="form-control" name="slug" value="{{ old('slug', $pack->slug ?? '') }}" placeholder="Auto-generated if empty">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Company</label>
                        <input class="form-control" name="company" value="{{ old('company', $pack->company ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role Family</label>
                        <input class="form-control" name="role_family" value="{{ old('role_family', $pack->role_family ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Difficulty</label>
                        <select class="form-select" name="difficulty" required>
                            @foreach(['easy', 'medium', 'hard'] as $difficulty)
                                <option value="{{ $difficulty }}" @selected(old('difficulty', $pack->difficulty ?? 'medium') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Interview Focus</label>
                        <input class="form-control" name="interview_focus" value="{{ old('interview_focus', $pack->interview_focus ?? 'General Practice') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Company Persona</label>
                        <input class="form-control" name="company_persona" value="{{ old('company_persona', $pack->company_persona ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Question Types</label>
                        <textarea class="form-control" name="question_types_text" rows="4" placeholder="Behavioral&#10;Situational">{{ old('question_types_text', $questionTypesText) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sample Questions</label>
                        <textarea class="form-control" name="sample_questions_text" rows="4" placeholder="Tell me about a time...">{{ old('sample_questions_text', $sampleQuestionsText) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ old('description', $pack->description ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="pressure_mode" value="1" @checked(old('pressure_mode', $pack->pressure_mode ?? false))>
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
