(function () {
    'use strict';

    const SCROLL_LOCK_CLASS = 'interviewer-avatar-modal-open';

    function initInterviewerAvatarModal() {
        const trigger = document.getElementById('interviewerAvatarTrigger');
        const dialog = document.getElementById('interviewerAvatarDialog');
        const group = document.getElementById('interviewerAvatarGroup');
        if (!trigger || !dialog || !group) return;

        const previewImage = document.getElementById('interviewerAvatarSelectedImage');
        const previewName = document.getElementById('interviewerAvatarSelectedName');
        const previewDescription = document.getElementById('interviewerAvatarSelectedDescription');
        const confirmButton = document.getElementById('interviewerAvatarConfirm');
        const cancelButton = document.getElementById('interviewerAvatarCancel');
        const closeButton = document.getElementById('interviewerAvatarClose');
        const setupForm = document.getElementById('setupForm');
        const radios = Array.from(group.querySelectorAll('input[type="radio"][name="interviewer_avatar"]'));
        if (!radios.length || !confirmButton) return;

        let isOpen = false;
        let nativeModal = false;
        let initialValue = null;
        let fallbackPlaceholder = null;
        const initiallyHidden = dialog.hasAttribute('hidden');

        function selectedRadio() {
            return radios.find((radio) => radio.checked) || null;
        }

        function updatePreview(radio = selectedRadio()) {
            if (!radio) return;
            const card = radio.closest('.interviewer-avatar-choice');
            if (!card) return;

            const name = card.querySelector('.interviewer-avatar-name')?.textContent?.trim() || radio.dataset.summaryLabel || '';
            const description = card.querySelector('.interviewer-avatar-description')?.textContent?.trim() || '';
            const portrait = card.querySelector('.interviewer-avatar-portrait img');
            const webp = card.querySelector('.interviewer-avatar-portrait source[type="image/webp"]')?.getAttribute('srcset');
            const imageUrl = webp?.split(',')[0]?.trim().split(/\s+/)[0] || portrait?.getAttribute('src') || '';

            if (previewImage && imageUrl) previewImage.src = imageUrl;
            if (previewImage) previewImage.alt = name ? `${name} portrait` : '';
            if (previewName) previewName.textContent = name;
            if (previewDescription) previewDescription.textContent = description;
            if (name) trigger.setAttribute('aria-label', `Change AI interviewer, ${name} selected`);
            trigger.dataset.selectedAvatar = radio.value;
        }

        function notifyChange(radio) {
            radio?.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function restoreSelection() {
            const previouslySelected = selectedRadio();
            const original = radios.find((radio) => radio.value === initialValue) || null;
            radios.forEach((radio) => { radio.checked = radio === original; });
            notifyChange(original || previouslySelected || radios[0]);
        }

        function closeModal(commit) {
            if (!isOpen) return;
            const chosen = selectedRadio();
            if (commit && !chosen) {
                radios[0].focus();
                return;
            }

            if (commit) {
                updatePreview(chosen);
                notifyChange(chosen);
            } else {
                restoreSelection();
            }

            isOpen = false;
            trigger.setAttribute('aria-expanded', 'false');
            document.documentElement.classList.remove(SCROLL_LOCK_CLASS);
            document.body.classList.remove(SCROLL_LOCK_CLASS);
            dialog.classList.remove('is-open');
            dialog.classList.remove('is-fallback');

            if (nativeModal && dialog.open && typeof dialog.close === 'function') {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
                dialog.removeAttribute('aria-modal');
            }
            if (fallbackPlaceholder?.parentNode) {
                fallbackPlaceholder.parentNode.insertBefore(dialog, fallbackPlaceholder);
                fallbackPlaceholder.remove();
            }
            fallbackPlaceholder = null;
            nativeModal = false;
            if (initiallyHidden) dialog.hidden = true;
            trigger.focus({ preventScroll: true });
        }

        function openModal() {
            if (isOpen) return;
            initialValue = selectedRadio()?.value || null;
            dialog.hidden = false;
            try {
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                    nativeModal = true;
                } else {
                    throw new Error('Native dialog unavailable');
                }
            } catch (error) {
                if (dialog.parentNode && dialog.parentNode !== document.body) {
                    fallbackPlaceholder = document.createComment('interviewer dialog position');
                    dialog.parentNode.insertBefore(fallbackPlaceholder, dialog);
                    document.body.appendChild(dialog);
                }
                dialog.setAttribute('open', '');
                dialog.setAttribute('aria-modal', 'true');
                dialog.classList.add('is-fallback');
                nativeModal = false;
            }
            isOpen = true;
            dialog.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            document.documentElement.classList.add(SCROLL_LOCK_CLASS);
            document.body.classList.add(SCROLL_LOCK_CLASS);
            (selectedRadio() || radios[0]).focus({ preventScroll: true });
        }

        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-controls', dialog.id);
        trigger.setAttribute('aria-expanded', 'false');
        updatePreview();

        trigger.addEventListener('click', openModal);
        confirmButton.addEventListener('click', (event) => {
            event.preventDefault();
            closeModal(true);
        });
        [cancelButton, closeButton].forEach((button) => {
            button?.addEventListener('click', (event) => {
                event.preventDefault();
                closeModal(false);
            });
        });
        group.addEventListener('change', () => {
            if (!isOpen) updatePreview();
            if (setupForm && !setupForm.contains(group)) {
                setupForm.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        group.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' || !radios.includes(event.target)) return;
            event.preventDefault();
            event.stopPropagation();
            if (!event.target.checked) {
                event.target.checked = true;
                notifyChange(event.target);
            }
            closeModal(true);
        });
        dialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            closeModal(false);
        });
        dialog.addEventListener('close', () => {
            if (isOpen) closeModal(false);
        });
        dialog.addEventListener('click', (event) => {
            if (event.target !== dialog) return;
            const bounds = dialog.getBoundingClientRect();
            const outside = event.clientX < bounds.left || event.clientX > bounds.right ||
                event.clientY < bounds.top || event.clientY > bounds.bottom;
            if (outside) closeModal(false);
        });
        document.addEventListener('click', (event) => {
            if (!isOpen || nativeModal || event.target === trigger || dialog.contains(event.target)) return;
            event.preventDefault();
            event.stopPropagation();
            closeModal(false);
        }, true);
        document.addEventListener('keydown', (event) => {
            if (!isOpen) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal(false);
            } else if (event.key === 'Tab' && !nativeModal) {
                const focusable = Array.from(dialog.querySelectorAll('button, input, [href], [tabindex]:not([tabindex="-1"])'))
                    .filter((element) => !element.disabled && !element.hidden);
                if (!focusable.length) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
        document.addEventListener('speakready:interview-setup-draft-restored', () => {
            if (!isOpen) updatePreview();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInterviewerAvatarModal, { once: true });
    } else {
        window.setTimeout(initInterviewerAvatarModal, 0);
    }
})();
