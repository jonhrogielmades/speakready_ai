@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')
@section('page-title', 'Interview Packs')
@section('content')
<div class="db-section active" id="sec-admin-packs">
    <style>
        #sec-admin-packs .pack-panel {
            background: var(--sf);
            border: 1px solid var(--bd);
            border-radius: 8px;
            overflow: hidden;
        }
        #sec-admin-packs .pack-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--bd);
        }
        #sec-admin-packs .pack-filters {
            display: grid;
            grid-template-columns: minmax(180px, 280px) minmax(130px, 170px) auto;
            gap: 10px;
            align-items: center;
        }
        #sec-admin-packs .pack-table {
            table-layout: fixed;
            width: 100%;
            min-width: 880px;
            font-size: .82rem;
        }
        #sec-admin-packs .pack-table th,
        #sec-admin-packs .pack-table td {
            vertical-align: middle;
            border-color: var(--bd) !important;
        }
        #sec-admin-packs .pack-title {
            font-weight: 800;
            color: var(--tx);
            line-height: 1.2;
            overflow-wrap: anywhere;
        }
        #sec-admin-packs .pack-meta {
            color: var(--tx3);
            font-size: .74rem;
            margin-top: 3px;
        }
        #sec-admin-packs .pack-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        #sec-admin-packs .pack-mobile-list {
            display: none;
        }
        #sec-admin-packs .pack-header-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        #sec-admin-packs .stat-card {
            background: var(--sf);
            border: 1px solid var(--bd);
            border-radius: 8px;
            padding: 16px;
            min-height: 96px;
        }
        #sec-admin-packs .stat-card span {
            color: var(--tx3);
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 800;
        }
        #sec-admin-packs .stat-card strong {
            display: block;
            color: var(--tx);
            font-size: 1.65rem;
            margin-top: 6px;
        }
        body.admin-shell .modal.pack-form-modal .modal-dialog.modal-lg.modal-dialog-scrollable,
        body.admin-mobile-shell .modal.pack-form-modal .modal-dialog.modal-lg.modal-dialog-scrollable {
            width: min(980px, calc(100vw - 32px));
            max-width: min(980px, calc(100vw - 32px));
            margin: 16px auto !important;
            height: calc(100dvh - 32px) !important;
            min-height: 0 !important;
            display: flex !important;
            align-items: stretch !important;
        }
        body.admin-shell .modal.pack-form-modal .modal-content,
        body.admin-mobile-shell .modal.pack-form-modal .modal-content {
            height: 100% !important;
            max-height: 100% !important;
            display: flex;
            flex-direction: column;
        }
        body.admin-shell .modal.pack-form-modal .modal-header,
        body.admin-shell .modal.pack-form-modal .modal-footer,
        body.admin-mobile-shell .modal.pack-form-modal .modal-header,
        body.admin-mobile-shell .modal.pack-form-modal .modal-footer {
            flex: 0 0 auto;
        }
        body.admin-shell .modal.pack-form-modal .modal-body,
        body.admin-mobile-shell .modal.pack-form-modal .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            max-height: none !important;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 18px 20px;
        }
        body.admin-shell .modal.pack-form-modal .modal-footer,
        body.admin-mobile-shell .modal.pack-form-modal .modal-footer {
            position: static;
            bottom: auto;
            z-index: 2;
        }
        body.admin-shell .modal.pack-form-modal .form-label,
        body.admin-mobile-shell .modal.pack-form-modal .form-label {
            margin-bottom: 5px;
            font-size: .86rem;
            font-weight: 800;
        }
        body.admin-shell .modal.pack-form-modal .form-control,
        body.admin-shell .modal.pack-form-modal .form-select,
        body.admin-mobile-shell .modal.pack-form-modal .form-control,
        body.admin-mobile-shell .modal.pack-form-modal .form-select {
            min-height: 44px;
        }
        body.admin-shell .modal.pack-form-modal textarea.form-control,
        body.admin-mobile-shell .modal.pack-form-modal textarea.form-control {
            min-height: auto;
        }
        @media (max-width: 991.98px) {
            #sec-admin-packs .pack-panel-header {
                align-items: flex-start;
                flex-direction: column;
            }
            #sec-admin-packs .pack-filters {
                grid-template-columns: 1fr;
                width: 100%;
            }
            #sec-admin-packs .pack-filters .btn {
                width: 100%;
            }
            #sec-admin-packs .pack-header-actions {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            #sec-admin-packs .pack-header-actions .btn {
                width: 100%;
                min-height: 42px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            body.admin-shell .modal.pack-form-modal .modal-dialog.modal-lg.modal-dialog-scrollable,
            body.admin-mobile-shell .modal.pack-form-modal .modal-dialog.modal-lg.modal-dialog-scrollable {
                width: calc(100vw - 18px);
                max-width: calc(100vw - 18px);
                margin: 9px auto !important;
                height: calc(100dvh - 18px) !important;
                min-height: 0 !important;
            }
            body.admin-shell .modal.pack-form-modal .modal-content,
            body.admin-mobile-shell .modal.pack-form-modal .modal-content {
                max-height: 100% !important;
            }
        }
        @media (max-width: 767.98px) {
            #sec-admin-packs .pack-panel {
                border-radius: 12px;
                overflow: visible;
                background: transparent;
                border: 0;
            }
            #sec-admin-packs .pack-panel-header {
                padding: 14px;
                border: 1px solid var(--bd);
                border-radius: 12px;
                background: var(--sf);
                margin-bottom: 12px;
            }
            #sec-admin-packs .pack-table-wrap {
                display: none !important;
            }
            #sec-admin-packs .pack-mobile-list {
                display: grid;
                gap: 12px;
            }
            #sec-admin-packs .pack-mobile-card {
                background: var(--sf);
                border: 1px solid var(--bd);
                border-radius: 12px;
                padding: 14px;
                box-shadow: 0 10px 26px rgba(15, 23, 42, .08);
            }
            #sec-admin-packs .pack-mobile-head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 12px;
            }
            #sec-admin-packs .pack-mobile-title {
                min-width: 0;
                color: var(--tx);
                font-weight: 800;
                font-size: .94rem;
                line-height: 1.2;
                overflow-wrap: anywhere;
            }
            #sec-admin-packs .pack-mobile-slug {
                color: var(--tx3);
                font-size: .72rem;
                line-height: 1.25;
                text-align: right;
                overflow-wrap: anywhere;
                max-width: 42%;
            }
            #sec-admin-packs .pack-mobile-meta {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
                margin-bottom: 12px;
            }
            #sec-admin-packs .pack-mobile-field {
                min-width: 0;
                padding: 10px;
                border-radius: 10px;
                background: var(--bg3);
                border: 1px solid color-mix(in srgb, var(--bd) 80%, transparent);
            }
            #sec-admin-packs .pack-mobile-field span {
                display: block;
                color: var(--tx3);
                font-size: .66rem;
                font-weight: 800;
                letter-spacing: .04em;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            #sec-admin-packs .pack-mobile-field strong {
                display: block;
                color: var(--tx);
                font-size: .8rem;
                line-height: 1.25;
                overflow-wrap: anywhere;
            }
            #sec-admin-packs .pack-mobile-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 12px;
            }
            #sec-admin-packs .pack-mobile-actions {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }
            #sec-admin-packs .pack-mobile-actions form {
                margin: 0;
            }
            #sec-admin-packs .pack-mobile-actions .btn {
                width: 100%;
                min-height: 42px;
                border-radius: 10px !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            #sec-admin-packs .pack-mobile-empty {
                border: 1px solid var(--bd);
                border-radius: 12px;
                padding: 28px 14px;
                text-align: center;
                color: var(--tx3);
                background: var(--sf);
            }
            #sec-admin-packs .pack-pagination {
                border: 1px solid var(--bd) !important;
                border-radius: 12px;
                background: var(--sf);
                margin-top: 12px;
            }
        }
        @media (max-width: 380px) {
            #sec-admin-packs .pack-mobile-head {
                flex-direction: column;
                gap: 4px;
            }
            #sec-admin-packs .pack-mobile-slug {
                max-width: 100%;
                text-align: left;
            }
            #sec-admin-packs .pack-mobile-meta {
                grid-template-columns: 1fr;
            }
            #sec-admin-packs .pack-header-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 style="font-size:1.4rem;font-weight:800;margin-bottom:4px">Interview Packs</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Publish company, role, and pressure-mode packs directly into the user practice setup.</p>
        </div>
        <div class="pack-header-actions">
            <button class="btn btn-outline-primary px-3 py-2" data-bs-toggle="modal" data-bs-target="#generatePackModal">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> AI Generate
            </button>
            <button class="bgrd btn px-3 py-2" data-bs-toggle="modal" data-bs-target="#addPackModal">
                <i class="fa-solid fa-plus me-1"></i> Add Pack
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="stat-card"><span>Total Packs</span><strong>{{ $stats['total'] }}</strong></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><span>Active For Users</span><strong>{{ $stats['active'] }}</strong></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><span>Pressure Mode</span><strong>{{ $stats['pressure'] }}</strong></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><span>Used In Sessions</span><strong>{{ $stats['used_sessions'] }}</strong></div></div>
    </div>

    <div class="pack-panel">
        <div class="pack-panel-header">
            <div>
                <h5 style="color:var(--tx);font-weight:800;margin:0">Pack Library</h5>
                <p style="color:var(--tx3);margin:4px 0 0;font-size:.82rem">Only active packs appear on the user side.</p>
            </div>
            <form method="GET" action="{{ route('admin.packs.index') }}" class="pack-filters">
                <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search packs">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
                <button class="btn btn-outline-primary" type="submit"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            </form>
        </div>
        <div class="table-responsive pack-table-wrap">
            <table class="table pack-table mb-0">
                <thead>
                    <tr>
                        <th style="width:28%">Pack</th>
                        <th style="width:15%">Audience</th>
                        <th style="width:14%">Mode</th>
                        <th style="width:20%">Questions</th>
                        <th style="width:8%">Sessions</th>
                        <th style="width:15%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packs as $pack)
                        <tr>
                            <td>
                                <div class="pack-title">{{ $pack->name }}</div>
                                <div class="pack-meta">{{ $pack->slug }}</div>
                            </td>
                            <td>
                                <div style="color:var(--tx);font-weight:700">{{ $pack->company ?: 'General' }}</div>
                                <div class="pack-meta">{{ $pack->role_family ?: 'Any role' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $pack->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($pack->status) }}</span>
                                <span class="badge bg-{{ $pack->pressure_mode ? 'danger' : 'info' }}">{{ $pack->pressure_mode ? 'Pressure' : 'Standard' }}</span>
                                <div class="pack-meta">{{ ucfirst($pack->difficulty) }} · {{ $pack->interview_focus }}</div>
                            </td>
                            <td>
                                <div style="color:var(--tx2)">{{ implode(', ', $pack->question_types ?? []) ?: 'General' }}</div>
                                <div class="pack-meta">{{ count($pack->sample_questions ?? []) }} sample questions</div>
                            </td>
                            <td>{{ $pack->sessions_count }}</td>
                            <td>
                                <div class="pack-actions">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPackModal{{ $pack->id }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form action="{{ route('admin.packs.destroy', $pack) }}" method="POST" onsubmit="return confirm('Remove this interview pack from the admin library?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5" style="color:var(--tx3)">No interview packs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pack-mobile-list">
            @forelse($packs as $pack)
                <article class="pack-mobile-card">
                    <div class="pack-mobile-head">
                        <div class="pack-mobile-title">{{ $pack->name }}</div>
                        <div class="pack-mobile-slug">{{ $pack->slug }}</div>
                    </div>
                    <div class="pack-mobile-badges">
                        <span class="badge bg-{{ $pack->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($pack->status) }}</span>
                        <span class="badge bg-{{ $pack->pressure_mode ? 'danger' : 'info' }}">{{ $pack->pressure_mode ? 'Pressure' : 'Standard' }}</span>
                    </div>
                    <div class="pack-mobile-meta">
                        <div class="pack-mobile-field">
                            <span>Audience</span>
                            <strong>{{ $pack->company ?: 'General' }}</strong>
                        </div>
                        <div class="pack-mobile-field">
                            <span>Role</span>
                            <strong>{{ $pack->role_family ?: 'Any role' }}</strong>
                        </div>
                        <div class="pack-mobile-field">
                            <span>Mode</span>
                            <strong>{{ ucfirst($pack->difficulty) }} / {{ $pack->interview_focus }}</strong>
                        </div>
                        <div class="pack-mobile-field">
                            <span>Questions</span>
                            <strong>{{ count($pack->sample_questions ?? []) }} samples</strong>
                        </div>
                        <div class="pack-mobile-field">
                            <span>Types</span>
                            <strong>{{ implode(', ', $pack->question_types ?? []) ?: 'General' }}</strong>
                        </div>
                        <div class="pack-mobile-field">
                            <span>Sessions</span>
                            <strong>{{ $pack->sessions_count }}</strong>
                        </div>
                    </div>
                    <div class="pack-mobile-actions">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPackModal{{ $pack->id }}" aria-label="Edit {{ $pack->name }}">
                            <i class="fa-solid fa-pen me-1"></i> Edit
                        </button>
                        <form action="{{ route('admin.packs.destroy', $pack) }}" method="POST" onsubmit="return confirm('Remove this interview pack from the admin library?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete {{ $pack->name }}">
                                <i class="fa-solid fa-trash me-1"></i> Delete
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="pack-mobile-empty">No interview packs found.</div>
            @endforelse
        </div>
        <div class="p-3 border-top pack-pagination" style="border-color:var(--bd)!important">
            {{ $packs->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('modals')
    @php($generateModalHasErrors = old('_pack_modal_id') === 'generatePackModal')
    <div class="modal fade pack-form-modal" id="generatePackModal" tabindex="-1" aria-labelledby="generatePackModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" action="{{ route('admin.packs.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="_pack_modal_id" value="generatePackModal">
                <div class="modal-header">
                    <h5 class="modal-title" id="generatePackModalTitle">AI Generate Interview Packs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($generateModalHasErrors && $errors->any())
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
                        <div class="col-md-6">
                            <label class="form-label" for="generatePackTargetRole">Target Role</label>
                            <input id="generatePackTargetRole" class="form-control @if($generateModalHasErrors && $errors->has('target_role')) is-invalid @endif" name="target_role" value="{{ old('target_role') }}" placeholder="Customer Service Representative" required>
                            @if($generateModalHasErrors && $errors->has('target_role'))
                                <div class="invalid-feedback">{{ $errors->first('target_role') }}</div>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="generatePackCompany">Company</label>
                            <input id="generatePackCompany" class="form-control" name="company" value="{{ old('company') }}" placeholder="Optional">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="generatePackRoleFamily">Role Family</label>
                            <input id="generatePackRoleFamily" class="form-control" name="role_family" value="{{ old('role_family') }}" placeholder="Support">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="generatePackDifficulty">Difficulty</label>
                            <select id="generatePackDifficulty" class="form-select" name="difficulty" required>
                                @foreach(['easy', 'medium', 'hard'] as $difficulty)
                                    <option value="{{ $difficulty }}" @selected(old('difficulty', 'medium') === $difficulty)>{{ ucfirst($difficulty) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="generatePackFocus">Interview Focus</label>
                            <input id="generatePackFocus" class="form-control @if($generateModalHasErrors && $errors->has('interview_focus')) is-invalid @endif" name="interview_focus" value="{{ old('interview_focus', 'Communication Skills') }}" required>
                            @if($generateModalHasErrors && $errors->has('interview_focus'))
                                <div class="invalid-feedback">{{ $errors->first('interview_focus') }}</div>
                            @endif
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="generatePackCount">Packs</label>
                            <input id="generatePackCount" class="form-control" type="number" min="1" max="5" name="pack_count" value="{{ old('pack_count', 1) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="generateQuestionCount">Questions</label>
                            <input id="generateQuestionCount" class="form-control" type="number" min="3" max="10" name="question_count" value="{{ old('question_count', 5) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="generatePackStatus">Status</label>
                            <select id="generatePackStatus" class="form-select" name="status" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="generatePackProvider">AI Provider</label>
                            <select id="generatePackProvider" class="form-select" name="ai_provider">
                                <option value="" @selected(old('ai_provider') === null)>Default</option>
                                @foreach(['gemini' => 'Gemini', 'openai' => 'OpenAI', 'claude' => 'Claude', 'groq' => 'Groq', 'openrouter' => 'OpenRouter', 'wisdomgate' => 'WisdomGate', 'cohere' => 'Cohere'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('ai_provider') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="generatePackContext">Context</label>
                            <input id="generatePackContext" class="form-control" name="context" value="{{ old('context') }}" placeholder="Hiring priorities, tools, industry, or competencies">
                        </div>
                        <div class="col-12">
                            <div class="form-check pack-pressure-check">
                                <input id="generatePackPressureMode" class="form-check-input pack-pressure-input" type="checkbox" name="pressure_mode" value="1" @checked(old('pressure_mode'))>
                                <label class="form-check-label pack-pressure-label" for="generatePackPressureMode">Enable pressure mode defaults for generated packs</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Generate Packs
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('admin.partials.pack-form-modal', ['modalId' => 'addPackModal', 'title' => 'Add Interview Pack', 'action' => route('admin.packs.store'), 'method' => 'POST', 'pack' => null])

    @foreach($packs as $pack)
        @include('admin.partials.pack-form-modal', ['modalId' => 'editPackModal'.$pack->id, 'title' => 'Edit Interview Pack', 'action' => route('admin.packs.update', $pack), 'method' => 'PUT', 'pack' => $pack])
    @endforeach
@endpush

@push('late-styles')
    <style>
        @media (max-width: 991.98px) {
            body.admin-mobile-shell .modal.pack-form-modal.show {
                align-items: stretch !important;
                justify-content: center !important;
                padding: calc(var(--mob-safe-top, 0px) + 8px) 8px calc(var(--mob-nav-h, 64px) + var(--mob-safe-bottom, 0px) + 8px) !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-dialog.modal-lg.modal-dialog-scrollable {
                width: min(100%, 520px) !important;
                max-width: min(100%, 520px) !important;
                height: calc(100dvh - var(--mob-nav-h, 64px) - var(--mob-safe-top, 0px) - var(--mob-safe-bottom, 0px) - 16px) !important;
                min-height: 0 !important;
                max-height: none !important;
                margin: 0 auto !important;
                display: flex !important;
                align-items: stretch !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-content {
                height: 100% !important;
                max-height: 100% !important;
                border-radius: 14px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-header {
                min-height: 56px;
                padding: 12px 14px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-title {
                font-size: .98rem;
                line-height: 1.25;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-body {
                flex: 1 1 auto !important;
                min-height: 0 !important;
                max-height: none !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                padding: 18px 14px 14px !important;
                scroll-padding-top: 16px;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-body .row {
                margin-left: -5px !important;
                margin-right: -5px !important;
                --bs-gutter-y: .82rem;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-body .row > [class*="col-"] {
                min-width: 0;
                padding-left: 5px !important;
                padding-right: 5px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .form-label {
                display: block !important;
                margin: 0 0 5px !important;
                color: var(--tx) !important;
                -webkit-text-fill-color: var(--tx) !important;
                font-size: .76rem;
                line-height: 1.2;
                font-weight: 800;
                opacity: 1 !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal :is(.form-control, .form-select) {
                min-height: 42px !important;
                font-size: .92rem;
            }
            body.admin-mobile-shell .modal.pack-form-modal textarea.form-control {
                min-height: 74px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .pack-pressure-check {
                display: flex !important;
                align-items: center;
                gap: 10px;
                min-height: 46px;
                margin: 2px 0 0 !important;
                padding: 9px 10px !important;
                border: 1px solid var(--bd);
                border-radius: 12px;
                background: var(--bg3);
            }
            body.admin-mobile-shell .modal.pack-form-modal .pack-pressure-input {
                accent-color: #0ea5e9;
                flex: 0 0 auto !important;
                width: 18px !important;
                min-width: 18px !important;
                max-width: 18px !important;
                height: 18px !important;
                min-height: 18px !important;
                margin: 0 !important;
                border-radius: 5px !important;
                padding: 0 !important;
                border: 2px solid #93c5fd !important;
                background-color: #eaf2ff !important;
                background-size: 12px 12px !important;
                cursor: pointer;
            }
            body.admin-mobile-shell .modal.pack-form-modal .pack-pressure-input:checked {
                background-color: #0ea5e9 !important;
                border-color: #0284c7 !important;
                box-shadow: 0 0 0 3px rgba(14, 165, 233, .18) !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .pack-pressure-input:focus {
                border-color: #0284c7 !important;
                box-shadow: 0 0 0 3px rgba(14, 165, 233, .22) !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .pack-pressure-label {
                margin: 0 !important;
                font-size: .8rem;
                line-height: 1.25;
                text-align: left;
                cursor: pointer;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-footer {
                display: grid !important;
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 8px !important;
                padding: 10px 14px 12px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-footer .btn,
            body.admin-mobile-shell .modal.pack-form-modal .modal-footer button {
                min-height: 44px !important;
                font-size: .84rem !important;
                white-space: nowrap;
            }
        }

        @media (max-width: 380px) {
            body.admin-mobile-shell .modal.pack-form-modal .modal-title {
                font-size: .92rem;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-body {
                padding: 12px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-footer {
                padding-inline: 12px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-footer .btn,
            body.admin-mobile-shell .modal.pack-form-modal .modal-footer button {
                font-size: .78rem !important;
            }
        }
    </style>
@endpush

@if($errors->any() && old('_pack_modal_id'))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById(@json(old('_pack_modal_id')));
                if (modal && window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endpush
@endif
