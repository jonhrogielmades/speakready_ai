<script>
(() => {
    const modal = document.getElementById('accountDeleteModal');
    if (!modal || modal.dataset.deleteAccountReady === 'true') return;

    modal.dataset.deleteAccountReady = 'true';

    const forms = Array.from(modal.querySelectorAll('[data-account-delete-form]'));
    const closeButton = modal.querySelector('[data-account-delete-close]');

    function setBusy(form, busy) {
        const submitButton = form.querySelector('[data-account-delete-submit]');
        const cancelButton = form.querySelector('[data-account-delete-cancel]');
        const label = form.querySelector('[data-account-delete-submit-label]');
        const status = form.querySelector('[data-account-delete-status]');

        form.dataset.submitting = busy ? 'true' : 'false';
        form.setAttribute('aria-busy', busy ? 'true' : 'false');

        if (label && !label.dataset.idleLabel) {
            label.dataset.idleLabel = label.textContent.trim();
        }
        if (label) {
            label.textContent = busy
                ? (submitButton?.dataset.busyLabel || 'Please wait…')
                : label.dataset.idleLabel;
        }
        if (submitButton) submitButton.disabled = busy;
        if (cancelButton) cancelButton.disabled = busy;
        if (closeButton) closeButton.disabled = busy;
        if (status) status.textContent = busy ? (form.dataset.accountDeleteBusyStatus || 'Please wait…') : '';
    }

    function resetPasswordField() {
        const password = modal.querySelector('[data-account-delete-password]');
        const toggle = modal.querySelector('[data-account-delete-password-toggle]');
        if (!password) return;

        password.value = '';
        password.type = 'password';

        const icon = toggle?.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
        toggle?.setAttribute('aria-label', 'Show password');
    }

    forms.forEach(form => {
        setBusy(form, false);
        form.addEventListener('submit', event => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (!form.checkValidity()) return;
            setBusy(form, true);
        });
    });

    modal.addEventListener('hide.bs.modal', event => {
        if (forms.some(form => form.dataset.submitting === 'true')) {
            event.preventDefault();
        }
    });

    modal.addEventListener('hidden.bs.modal', resetPasswordField);
    modal.addEventListener('shown.bs.modal', () => {
        const target = modal.querySelector('.is-invalid')
            || modal.querySelector('.account-delete-modal-error')
            || modal.querySelector('[data-account-delete-password]')
            || modal.querySelector('[data-account-delete-submit]');

        target?.focus({ preventScroll: true });
    });

    window.addEventListener('pageshow', () => {
        forms.forEach(form => setBusy(form, false));
    });
})();
</script>
