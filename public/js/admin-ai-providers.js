(function () {
    'use strict';

    function initializeAddProviderForm() {
        const modalElement = document.getElementById('addProviderModal');
        const form = document.querySelector('[data-provider-add-form]');

        if (!modalElement || !form || form.dataset.initialized === 'true') {
            return;
        }

        form.dataset.initialized = 'true';

        const providerSelect = form.querySelector('[data-provider-select]');
        const endpointInput = document.getElementById('addProviderEndpoint');
        const saveButton = form.querySelector('[data-provider-save-button]');
        const saveLabel = form.querySelector('[data-provider-save-label]');

        const fillOfficialEndpoint = function (force) {
            if (!providerSelect || !endpointInput) {
                return;
            }

            const selectedOption = providerSelect.options[providerSelect.selectedIndex];
            const endpoint = selectedOption ? selectedOption.dataset.endpoint || '' : '';

            if (force || endpointInput.value.trim() === '') {
                endpointInput.value = endpoint;
            }
        };

        if (providerSelect) {
            providerSelect.addEventListener('change', function () {
                fillOfficialEndpoint(true);
            });
            fillOfficialEndpoint(false);
        }

        form.querySelectorAll('[data-provider-key-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.dataset.target || '');
                if (!input) {
                    return;
                }

                const reveal = input.type === 'password';
                input.type = reveal ? 'text' : 'password';
                button.setAttribute('aria-label', reveal ? 'Hide API key' : 'Show API key');

                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye', !reveal);
                    icon.classList.toggle('fa-eye-slash', reveal);
                }
            });
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            form.querySelectorAll('[data-provider-key-toggle]').forEach(function (button) {
                const input = document.getElementById(button.dataset.target || '');
                if (input) {
                    input.type = 'password';
                    input.value = '';
                }

                button.setAttribute('aria-label', 'Show API key');
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-eye');
                    icon.classList.remove('fa-eye-slash');
                }
            });
        });

        form.addEventListener('submit', function () {
            if (!form.checkValidity() || !saveButton) {
                return;
            }

            saveButton.disabled = true;
            saveButton.setAttribute('aria-busy', 'true');
            if (saveLabel) {
                saveLabel.textContent = 'Saving...';
            }
        });

        window.addEventListener('pageshow', function () {
            if (!saveButton) {
                return;
            }

            saveButton.disabled = providerSelect ? providerSelect.disabled : false;
            saveButton.removeAttribute('aria-busy');
            if (saveLabel) {
                saveLabel.textContent = 'Save Provider';
            }
        });

        if (modalElement.dataset.reopenOnError === 'true' && window.bootstrap?.Modal) {
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
            modalElement.addEventListener('shown.bs.modal', function focusInvalidProviderField() {
                const invalidField = form.querySelector('.is-invalid');
                (invalidField || providerSelect)?.focus();
            }, { once: true });
            modal.show();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAddProviderForm, { once: true });
    } else {
        initializeAddProviderForm();
    }
})();
