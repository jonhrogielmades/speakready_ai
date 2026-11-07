(function () {
    'use strict';

    const SCROLL_LOCK_CLASS = 'interviewer-avatar-modal-open';
    const FALLBACK_BACKDROP_CLASS = 'interviewer-avatar-fallback-backdrop';

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
        const voiceStatus = document.getElementById('interviewerAvatarVoiceStatus');
        const voiceButtons = Array.from(dialog.querySelectorAll('.interviewer-avatar-voice-preview'));
        const setupForm = document.getElementById('setupForm');
        const radios = Array.from(group.querySelectorAll('input[type="radio"][name="interviewer_avatar"]'));
        if (!radios.length || !confirmButton) return;

        let isOpen = false;
        let nativeModal = false;
        let initialValue = null;
        let fallbackPlaceholder = null;
        let activeVoiceButton = null;
        let activeVoiceAudio = null;
        let activeVoiceAbort = null;
        let activeVoiceUrl = null;
        let voiceRequestId = 0;
        const initiallyHidden = dialog.hasAttribute('hidden');

        function setVoiceButtonState(button, state) {
            if (!button) return;
            const name = button.dataset.avatarLabel || 'interviewer';
            const labels = { idle: 'Hear', loading: 'Wait', ready: 'Play', playing: 'Stop' };
            const accessibleLabels = {
                idle: `Preview ${name} voice`,
                loading: `Cancel loading ${name} voice`,
                ready: `Play ${name} voice preview`,
                playing: `Stop ${name} voice preview`,
            };
            button.dataset.state = state;
            button.setAttribute('aria-pressed', state === 'playing' ? 'true' : 'false');
            button.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
            button.setAttribute('aria-label', accessibleLabels[state]);
            button.title = accessibleLabels[state];
            const label = button.querySelector('.interviewer-avatar-voice-preview-label');
            if (label) label.textContent = labels[state];
        }

        function clearVoiceStatus() {
            if (!voiceStatus) return;
            voiceStatus.textContent = '';
            voiceStatus.hidden = true;
        }

        function showVoiceError(message) {
            if (!voiceStatus) return;
            voiceStatus.textContent = message;
            voiceStatus.dataset.kind = 'error';
            voiceStatus.hidden = false;
        }

        function showVoiceReady() {
            if (!voiceStatus) return;
            voiceStatus.textContent = 'Voice is ready. Tap Play to hear it.';
            voiceStatus.dataset.kind = 'ready';
            voiceStatus.hidden = false;
        }

        function stopVoicePreview() {
            voiceRequestId += 1;
            activeVoiceAbort?.abort();
            activeVoiceAbort = null;
            if (activeVoiceAudio) {
                activeVoiceAudio.pause();
                activeVoiceAudio = null;
            }
            if (activeVoiceUrl) {
                URL.revokeObjectURL(activeVoiceUrl);
                activeVoiceUrl = null;
            }
            setVoiceButtonState(activeVoiceButton, 'idle');
            activeVoiceButton = null;
            clearVoiceStatus();
        }

        async function playPreparedVoice(button, audio, requestId) {
            try {
                // This call happens synchronously on a second tap if the first
                // playback was blocked after a slow voice-generation request.
                await Promise.resolve(audio.play());
                if (requestId !== voiceRequestId) {
                    audio.pause();
                    return;
                }
                clearVoiceStatus();
                setVoiceButtonState(button, 'playing');
            } catch (error) {
                if (requestId !== voiceRequestId) return;
                if (error?.name === 'NotAllowedError') {
                    setVoiceButtonState(button, 'ready');
                    showVoiceReady();
                    return;
                }
                stopVoicePreview();
                showVoiceError('This voice could not play. Please try again.');
            }
        }

        async function previewVoice(button) {
            if (!isOpen) return;
            const wasActive = activeVoiceButton === button;
            if (wasActive && button.dataset.state === 'ready' && activeVoiceAudio) {
                return playPreparedVoice(button, activeVoiceAudio, voiceRequestId);
            }
            stopVoicePreview();
            if (wasActive) return;

            const previewUrl = dialog.dataset.voicePreviewUrl;
            const avatar = button.dataset.avatar;
            if (!previewUrl || !avatar) {
                showVoiceError('Voice preview is unavailable right now.');
                return;
            }

            activeVoiceButton = button;
            const requestId = voiceRequestId;
            const controller = new AbortController();
            activeVoiceAbort = controller;
            setVoiceButtonState(button, 'loading');
            let timedOut = false;
            const timeoutId = window.setTimeout(() => {
                timedOut = true;
                controller.abort();
            }, 55000);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch(previewUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'audio/*',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ interviewer_avatar: avatar }),
                    signal: controller.signal,
                });
                if (requestId !== voiceRequestId) return;
                if (!response.ok) {
                    let message = response.status === 419
                        ? 'Your session expired. Refresh the page to preview a voice.'
                        : 'Voice preview is unavailable right now.';
                    try {
                        const error = await response.json();
                        if (typeof error?.error === 'string' && error.error.trim()) message = error.error;
                    } catch (_) { /* Keep the useful fallback message. */ }
                    throw new Error(message);
                }
                if (!response.headers.get('content-type')?.toLowerCase().startsWith('audio/')) {
                    throw new Error('Voice preview is unavailable right now.');
                }

                const blob = await response.blob();
                if (requestId !== voiceRequestId) return;
                if (!blob.size) throw new Error('Voice preview is unavailable right now.');
                activeVoiceAbort = null;
                activeVoiceUrl = URL.createObjectURL(blob);
                const audio = new Audio(activeVoiceUrl);
                activeVoiceAudio = audio;
                audio.addEventListener('ended', () => {
                    if (activeVoiceAudio === audio) stopVoicePreview();
                });
                audio.addEventListener('error', () => {
                    if (activeVoiceAudio !== audio) return;
                    stopVoicePreview();
                    showVoiceError('This voice could not play. Please try again.');
                });
                await playPreparedVoice(button, audio, requestId);
            } catch (error) {
                if (requestId !== voiceRequestId || (error?.name === 'AbortError' && !timedOut)) return;
                stopVoicePreview();
                showVoiceError(timedOut
                    ? 'Voice preview took too long. Please try again.'
                    : (typeof error?.message === 'string' ? error.message : 'Voice preview is unavailable right now.'));
            } finally {
                window.clearTimeout(timeoutId);
            }
        }

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

            stopVoicePreview();

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
            document.body.classList.remove(FALLBACK_BACKDROP_CLASS);
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
                document.body.classList.add(FALLBACK_BACKDROP_CLASS);
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
        voiceButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                previewVoice(button);
            });
        });
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
            stopVoicePreview();
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
        window.addEventListener('pagehide', stopVoicePreview);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInterviewerAvatarModal, { once: true });
    } else {
        window.setTimeout(initInterviewerAvatarModal, 0);
    }
})();
