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
        if (status) status.setAttribute('role', 'status');
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

    function showFormError(form, message) {
        const status = form.querySelector('[data-account-delete-status]');
        if (!status) return;

        status.setAttribute('role', 'alert');
        status.textContent = message;
    }

    async function startGoogleDeletion(form) {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 8000);

        try {
            const response = await window.fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                redirect: 'error',
                signal: controller.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const contentType = response.headers.get('content-type') || '';
            const payload = contentType.includes('application/json')
                ? await response.json().catch(() => ({}))
                : {};

            if (!response.ok) {
                const fallback = response.status === 419
                    ? 'Your session expired. Refresh the page and try again.'
                    : 'Google confirmation could not start. Please try again.';
                throw new Error(payload.message || fallback);
            }

            if (typeof payload.redirect_url !== 'string' || payload.redirect_url.trim() === '') {
                throw new Error('Google confirmation could not start. Refresh the page and try again.');
            }

            const redirectUrl = new URL(payload.redirect_url);
            if (redirectUrl.protocol !== 'https:'
                || redirectUrl.hostname !== 'accounts.google.com'
                || redirectUrl.username !== ''
                || redirectUrl.password !== '') {
                throw new Error('Google returned an invalid confirmation address. Please try again.');
            }

            window.location.assign(redirectUrl.toString());
        } catch (error) {
            setBusy(form, false);
            showFormError(
                form,
                error?.name === 'AbortError'
                    ? 'Google confirmation did not open in time. Check your connection and try again.'
                    : (error instanceof TypeError
                        ? 'Google confirmation could not start. Check your connection and try again.'
                        : (error?.message || 'Google confirmation could not start. Please try again.'))
            );
        } finally {
            window.clearTimeout(timeout);
        }
    }

    forms.forEach(form => {
        setBusy(form, false);
        form.addEventListener('submit', event => {
            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            if (!form.checkValidity()) return;

            if (form.dataset.accountDeleteKind === 'google'
                && typeof window.fetch === 'function'
                && typeof window.AbortController === 'function') {
                event.preventDefault();
                setBusy(form, true);
                void startGoogleDeletion(form);
                return;
            }

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
