@extends($isMobile ? 'layouts.admin-mobile' : 'layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/' . (($isMobile ?? false) ? 'mobile' : 'desktop') . '/admin/sessions/archive.css?v=1') }}" data-page-style="admin-sessions-archive">
@endpush

@section('content')

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

