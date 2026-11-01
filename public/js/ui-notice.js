(function (window, document) {
    'use strict';

    var modalId = 'srUiNoticeModal';
    var modalInstance = null;
    var pendingResolve = null;

    var tones = {
        success: { title: 'Success', icon: 'fa-circle-check' },
        danger: { title: 'Action needed', icon: 'fa-circle-exclamation' },
        error: { title: 'Action needed', icon: 'fa-circle-exclamation' },
        warning: { title: 'Please check', icon: 'fa-triangle-exclamation' },
        info: { title: 'Notice', icon: 'fa-circle-info' }
    };

    function normalizeTone(options) {
        var requested = String(options && (options.tone || options.icon) || 'info').toLowerCase();
        return tones[requested] ? requested : 'info';
    }

    function settle(value) {
        if (typeof pendingResolve !== 'function') return;
        var resolve = pendingResolve;
        pendingResolve = null;
        resolve(Boolean(value));
    }

    function ensureModal() {
        var modal = document.getElementById(modalId);
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = modalId;
        modal.className = 'modal fade sr-feedback-modal';
        modal.tabIndex = -1;
        modal.setAttribute('aria-labelledby', 'srUiNoticeTitle');
        modal.setAttribute('aria-describedby', 'srUiNoticeMessage');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = [
            '<div class="modal-dialog modal-dialog-centered">',
            '<div class="modal-content">',
            '<div class="modal-header">',
            '<div class="sr-feedback-heading">',
            '<span class="sr-feedback-icon" id="srUiNoticeIcon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span>',
            '<h5 class="modal-title" id="srUiNoticeTitle">Notice</h5>',
            '</div>',
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>',
            '</div>',
            '<div class="modal-body"><div class="sr-feedback-message-list"><div class="sr-feedback-message" id="srUiNoticeMessage"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span></span></div></div></div>',
            '<div class="modal-footer"><button type="button" class="btn sr-feedback-action" data-bs-dismiss="modal">OK</button></div>',
            '</div>',
            '</div>'
        ].join('');

        document.body.appendChild(modal);
        modal.addEventListener('hidden.bs.modal', function () { settle(false); });
        modal.querySelector('.sr-feedback-action')?.addEventListener('click', function () { settle(true); });
        return modal;
    }

    function setContent(modal, message, options) {
        var tone = normalizeTone(options);
        var config = tones[tone];
        modal.setAttribute('data-feedback-tone', tone === 'error' ? 'danger' : tone);
        var title = modal.querySelector('#srUiNoticeTitle');
        var icon = modal.querySelector('#srUiNoticeIcon');
        var bodyIcon = modal.querySelector('#srUiNoticeMessage > i');
        var body = modal.querySelector('#srUiNoticeMessage > span');
        var action = modal.querySelector('.sr-feedback-action');
        if (title) title.textContent = String(options && options.title || config.title);
        if (body) body.textContent = String(message || options?.text || options?.message || 'Please try again.');
        if (icon) icon.innerHTML = '<i class="fa-solid ' + config.icon + '"></i>';
        if (bodyIcon) bodyIcon.className = 'fa-solid ' + config.icon;
        if (action) action.textContent = String(options && (options.action || options.actionText) || 'OK');
    }

    function showNotice(message, options) {
        options = options || {};
        var modal = ensureModal();
        setContent(modal, message || options.text || options.message, options);

        return new Promise(function (resolve) {
            settle(false);
            pendingResolve = resolve;

            if (window.bootstrap?.Modal) {
                modalInstance = window.bootstrap.Modal.getOrCreateInstance(modal, { backdrop: true, keyboard: true, focus: true });
                modalInstance.show();
                return;
            }

            modal.style.display = 'block';
            modal.classList.add('show', 'active');
            modal.setAttribute('aria-hidden', 'false');
        });
    }

    function hideNotice() {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        if (modalInstance) {
            modalInstance.hide();
        } else {
            modal.classList.remove('show', 'active');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            settle(false);
        }
    }

    window.SpeakReadyUiNotice = showNotice;
    window.SpeakReadyUiNotice.hide = hideNotice;
    window.SpeakReadyAlert = showNotice;
    window.SpeakReadyAlert.hide = hideNotice;
}(window, document));
