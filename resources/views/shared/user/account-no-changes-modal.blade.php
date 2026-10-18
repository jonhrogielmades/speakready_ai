<div class="modal fade account-no-changes-modal" id="accountNoChangesModal" tabindex="-1" aria-labelledby="accountNoChangesModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <div class="account-no-changes-heading">
                    <span class="account-no-changes-icon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span>
                    <h5 class="modal-title" id="accountNoChangesModalTitle">No changes to save.</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ session('info', 'Your profile details are already up to date.') }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn account-no-changes-close-btn" data-bs-dismiss="modal">Okay</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    @if(session('info'))
        <script>
            (() => {
                const openAccountNoChangesModal = () => {
                    const modalElement = document.getElementById('accountNoChangesModal');
                    if (!modalElement) return;

                    if (window.bootstrap?.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                        return;
                    }

                    window.setTimeout(openAccountNoChangesModal, 50);
                };

                openAccountNoChangesModal();
            })();
        </script>
    @endif
@endpush
