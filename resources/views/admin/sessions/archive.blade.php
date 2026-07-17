@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@section('content')
<style>
    .premium-card {
        background: var(--sf, #1e1e2d);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.1));
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .archive-confirm-modal .modal-content {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        color: var(--tx);
        box-shadow: 0 24px 70px rgba(0, 0, 0, 0.35);
    }
    .archive-confirm-modal .modal-header,
    .archive-confirm-modal .modal-footer {
        border-color: var(--bd);
    }
    .archive-confirm-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }
    .archive-confirm-icon.restore {
        background: rgba(52, 211, 153, 0.14);
        color: #34d399;
    }
    .archive-confirm-icon.delete {
        background: rgba(248, 113, 113, 0.14);
        color: #f87171;
    }
    .archive-page-title {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        line-height: 1.15;
    }

    /* Mobile Card-based Table Layout for Main Archive Table */
    @media (max-width: 767px) {
        .archive-page-title {
            font-size: clamp(1rem, 5vw, 1.18rem) !important;
            gap: 6px;
        }
        .archive-page-title i {
            margin-right: 0 !important;
            font-size: 0.95em;
        }
        #mainArchiveTableWrapper {
            overflow-x: visible !important;
            -webkit-overflow-scrolling: auto !important;
        }
        #mainArchiveTable thead {
            display: none;
        }
        #mainArchiveTable tbody tr {
            display: flex;
            flex-direction: column;
            background: var(--bg3, rgba(255,255,255,0.02));
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid var(--bd, rgba(255,255,255,0.1));
            padding: 12px;
        }
        #mainArchiveTable tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-top: none !important;
            text-align: right;
        }
        #mainArchiveTable tbody td:last-child {
            border-bottom: none !important;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 12px !important;
        }
        #mainArchiveTable tbody td::before {
            font-size: 0.8rem;
            color: var(--tx3, #888);
            font-weight: 600;
            margin-right: 15px;
            flex-shrink: 0;
            text-align: left;
        }
        #mainArchiveTable tbody td:nth-child(1)::before { content: "ID"; }
        #mainArchiveTable tbody td:nth-child(3)::before { content: "Category"; }
        #mainArchiveTable tbody td:nth-child(4)::before { content: "Archived Date"; }
        
        #mainArchiveTable tbody td:nth-child(2) {
            order: -1;
            justify-content: flex-start;
            border-bottom: 1px solid var(--bd, rgba(255,255,255,0.1)) !important;
            padding-bottom: 12px !important;
            margin-bottom: 4px;
            text-align: left;
            flex-direction: column;
            align-items: flex-start;
        }
        #mainArchiveTable tbody td:nth-child(2)::before { content: none; }
    }
</style>

<div class="db-section active" id="sec-admin-archive">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="archive-page-title fw-bold mb-1 mt-2"><i class="fa-solid fa-box-archive text-warning me-2"></i>Archived Philippines Interview Sessions</h4>
            <p style="font-size:0.95rem;color:var(--tx2);margin:0;">Historical records of Philippine interview practice sessions.</p>
        </div>
    </div>

    @if(session('message'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.3);color:#34d399">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="filter:invert(1)"></button>
    </div>
    @endif

    <div class="premium-card mb-4">
        <form method="GET" action="{{ route('admin.sessions.archive') }}" class="row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search user or PH session ID..." value="{{ request('search') }}" style="background:var(--bg3);border:1px solid var(--bd);color:var(--tx);">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-warning w-100" style="border-radius:8px;">Search PH Archive</button>
            </div>
        </form>

        <div class="table-responsive" id="mainArchiveTableWrapper">
            <table class="table custom-table mb-0 w-100" id="mainArchiveTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Category</th>
                        <th>Archived Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    <tr>
                        <td>#{{ $session->id }}</td>
                        <td>{{ $session->user ? $session->user->name : 'Deleted User' }}</td>
                        <td>{{ $session->category ? $session->category->title : 'N/A' }}</td>
                        <td style="color:var(--tx2);">{{ $session->updated_at->format('M d, Y') }}</td>
                        <td class="text-end">
                            <form action="{{ route('admin.sessions.restore', $session->id) }}" method="POST" class="d-inline" id="restoreArchiveForm{{ $session->id }}">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-success" style="border-radius:8px;" title="Restore" data-archive-restore-trigger data-archive-restore-form="restoreArchiveForm{{ $session->id }}" data-archive-restore-title="Restore archived Philippines interview session #{{ $session->id }}?" data-archive-restore-message="This Philippines interview session will return to PH Session Monitoring.">
                                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Restore
                                </button>
                            </form>
                            <form action="{{ route('admin.sessions.destroy', $session->id) }}" method="POST" class="d-inline" id="deleteArchiveForm{{ $session->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger" style="border-radius:8px;" title="Delete" data-archive-delete-trigger data-archive-delete-form="deleteArchiveForm{{ $session->id }}" data-archive-delete-title="Delete archived session #{{ $session->id }}?" data-archive-delete-message="This archived session and its related records will be permanently deleted. This cannot be undone.">
                                    <i class="fa-solid fa-trash-can me-1"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No archived sessions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $sessions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<div class="modal fade archive-confirm-modal" id="archiveRestoreConfirmModal" tabindex="-1" aria-labelledby="archiveRestoreConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="archive-confirm-icon restore"><i class="fa-solid fa-clock-rotate-left"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="archiveRestoreConfirmTitle">Restore session?</h5>
                        <div style="font-size:.8rem;color:var(--tx3);">Please confirm this restore action.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body">
                <p id="archiveRestoreConfirmMessage" style="margin:0;color:var(--tx2);line-height:1.5;">This Philippines interview session will return to PH Session Monitoring.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="archiveRestoreConfirmButton"><i class="fa-solid fa-clock-rotate-left me-1"></i>Restore</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade archive-confirm-modal" id="archiveDeleteConfirmModal" tabindex="-1" aria-labelledby="archiveDeleteConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <span class="archive-confirm-icon delete"><i class="fa-solid fa-trash-can"></i></span>
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="archiveDeleteConfirmTitle">Delete archived session?</h5>
                        <div style="font-size:.8rem;color:var(--tx3);">Please confirm this destructive action.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1);"></button>
            </div>
            <div class="modal-body">
                <p id="archiveDeleteConfirmMessage" style="margin:0;color:var(--tx2);line-height:1.5;">This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="archiveDeleteConfirmButton"><i class="fa-solid fa-trash-can me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function wireArchiveModal(config) {
        const modalEl = document.getElementById(config.modalId);
        const titleEl = document.getElementById(config.titleId);
        const messageEl = document.getElementById(config.messageId);
        const confirmButton = document.getElementById(config.buttonId);
        let pendingForm = null;

        if (!modalEl || !confirmButton || typeof bootstrap === 'undefined') return;

        const modal = new bootstrap.Modal(modalEl);

        document.querySelectorAll(config.triggerSelector).forEach((trigger) => {
            trigger.addEventListener('click', () => {
                pendingForm = document.getElementById(trigger.dataset[config.formDataset] || '');
                if (!pendingForm) return;
                if (titleEl) titleEl.textContent = trigger.dataset[config.titleDataset] || config.defaultTitle;
                if (messageEl) messageEl.textContent = trigger.dataset[config.messageDataset] || config.defaultMessage;
                modal.show();
            });
        });

        confirmButton.addEventListener('click', () => {
            if (!pendingForm) return;
            confirmButton.disabled = true;
            pendingForm.submit();
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            pendingForm = null;
            confirmButton.disabled = false;
        });
    }

    wireArchiveModal({
        modalId: 'archiveRestoreConfirmModal',
        titleId: 'archiveRestoreConfirmTitle',
        messageId: 'archiveRestoreConfirmMessage',
        buttonId: 'archiveRestoreConfirmButton',
        triggerSelector: '[data-archive-restore-trigger]',
        formDataset: 'archiveRestoreForm',
        titleDataset: 'archiveRestoreTitle',
        messageDataset: 'archiveRestoreMessage',
        defaultTitle: 'Restore session?',
        defaultMessage: 'This Philippines interview session will return to PH Session Monitoring.'
    });

    wireArchiveModal({
        modalId: 'archiveDeleteConfirmModal',
        titleId: 'archiveDeleteConfirmTitle',
        messageId: 'archiveDeleteConfirmMessage',
        buttonId: 'archiveDeleteConfirmButton',
        triggerSelector: '[data-archive-delete-trigger]',
        formDataset: 'archiveDeleteForm',
        titleDataset: 'archiveDeleteTitle',
        messageDataset: 'archiveDeleteMessage',
        defaultTitle: 'Delete archived session?',
        defaultMessage: 'This cannot be undone.'
    });
});
</script>
@endsection

