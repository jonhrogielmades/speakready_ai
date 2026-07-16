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
    </style>

    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 style="font-size:1.4rem;font-weight:800;margin-bottom:4px">Interview Packs</h4>
            <p style="font-size:.875rem;color:var(--tx3);margin:0">Publish company, role, and pressure-mode packs directly into the user practice setup.</p>
        </div>
        <button class="bgrd btn px-3 py-2" data-bs-toggle="modal" data-bs-target="#addPackModal">
            <i class="fa-solid fa-plus me-1"></i> Add Pack
        </button>
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
        <div class="table-responsive">
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
        <div class="p-3 border-top" style="border-color:var(--bd)!important">
            {{ $packs->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('modals')
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
                padding: 14px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-body .row {
                margin-left: -5px !important;
                margin-right: -5px !important;
                --bs-gutter-y: .75rem;
            }
            body.admin-mobile-shell .modal.pack-form-modal .modal-body .row > [class*="col-"] {
                min-width: 0;
                padding-left: 5px !important;
                padding-right: 5px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .form-label {
                margin-bottom: 4px;
                font-size: .78rem;
                line-height: 1.2;
            }
            body.admin-mobile-shell .modal.pack-form-modal :is(.form-control, .form-select) {
                min-height: 42px !important;
                font-size: .92rem;
            }
            body.admin-mobile-shell .modal.pack-form-modal textarea.form-control {
                min-height: 74px !important;
            }
            body.admin-mobile-shell .modal.pack-form-modal .form-check {
                align-items: flex-start;
                gap: 8px;
            }
            body.admin-mobile-shell .modal.pack-form-modal .form-check-label {
                font-size: .86rem;
                line-height: 1.35;
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
