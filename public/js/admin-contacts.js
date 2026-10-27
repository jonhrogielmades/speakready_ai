(function () {
    'use strict';

    function initializeContactReplyForms() {
        document.querySelectorAll('[data-contact-reply-form]').forEach(function (form) {
            if (form.dataset.replyGuardBound === 'true') {
                return;
            }

            form.dataset.replyGuardBound = 'true';
            form.addEventListener('submit', function () {
                var button = form.querySelector('[data-contact-reply-submit]');
                var label = form.querySelector('[data-contact-reply-submit-label]');

                if (!button || button.disabled) {
                    return;
                }

                button.disabled = true;
                button.setAttribute('aria-busy', 'true');

                if (label) {
                    label.textContent = 'Sending...';
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeContactReplyForms, { once: true });
    } else {
        initializeContactReplyForms();
    }
})();
