(function (window, document) {
    'use strict';

    var modalId = 'srAdminConfirmModal';
    var pendingResolve = null;
    var modalInstance = null;

    function ensureModal() {
        var modal = document.getElementById(modalId);
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'modal fade';
        modal.tabIndex = -1;
        modal.setAttribute('aria-labelledby', 'srAdminConfirmTitle');
        modal.setAttribute('aria-describedby', 'srAdminConfirmMessage');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = [
            '<div class="modal-dialog modal-dialog-centered">',
            '<div class="modal-content" style="background:var(--sf,#fff);color:var(--tx,#0f172a);border:1px solid var(--bd,#dbe4f0);border-radius:16px;box-shadow:0 24px 70px rgba(15,23,42,.28);">',
            '<div class="modal-header" style="border-bottom:1px solid var(--bd,#dbe4f0);">',
            '<h5 class="modal-title" id="srAdminConfirmTitle">Confirm action</h5>',
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>',
            '</div>',
            '<div class="modal-body"><p class="mb-0" id="srAdminConfirmMessage"></p></div>',
            '<div class="modal-footer" style="border-top:1px solid var(--bd,#dbe4f0);">',
            '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>',
            '<button type="button" class="btn btn-danger" id="srAdminConfirmContinue">Continue</button>',
            '</div>',
            '</div>',
            '</div>'
        ].join('');

        document.body.appendChild(modal);
        modal.addEventListener('hidden.bs.modal', function () {
            if (pendingResolve) {
                var resolve = pendingResolve;
                pendingResolve = null;
                resolve(false);
            }
        });
        modal.querySelector('#srAdminConfirmContinue').addEventListener('click', function () {
            settle(true);
        });

        return modal;
    }

    function settle(value) {
        var resolve = pendingResolve;
        pendingResolve = null;
        var modal = document.getElementById(modalId);
        if (modalInstance) {
            modalInstance.hide();
        } else if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
        if (resolve) resolve(Boolean(value));
    }

    function showConfirm(message, title) {
        var modal = ensureModal();
        var titleElement = modal.querySelector('#srAdminConfirmTitle');
        var messageElement = modal.querySelector('#srAdminConfirmMessage');
        if (titleElement) titleElement.textContent = title || 'Confirm action';
        if (messageElement) messageElement.textContent = String(message || 'Are you sure you want to continue?');

        return new Promise(function (resolve) {
            pendingResolve = resolve;
            if (window.bootstrap && window.bootstrap.Modal) {
                modalInstance = window.bootstrap.Modal.getOrCreateInstance(modal, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                modalInstance.show();
                return;
            }

            modal.style.display = 'block';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
        });
    }

    function confirmForm(event, form, message) {
        if (!event || !form) return true;
        event.preventDefault();
        showConfirm(message).then(function (confirmed) {
            if (confirmed) form.submit();
        });
        return false;
    }

    window.SpeakReadyAdminConfirm = function (eventOrMessage, form, message) {
        if (eventOrMessage && typeof eventOrMessage.preventDefault === 'function') {
            return confirmForm(eventOrMessage, form, message);
        }

        return showConfirm(eventOrMessage, form);
    };
}(window, document));
