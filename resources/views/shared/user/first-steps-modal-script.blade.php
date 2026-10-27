<script data-sr-first-steps-modal-script>
    (function () {
        if (window.__speakReadyFirstStepsBound) return;
        window.__speakReadyFirstStepsBound = true;

        const modalElement = document.getElementById('firstStepsOnboardingModal');
        if (!modalElement) return;

        const userId = modalElement.dataset.srOnboardingUser || 'guest';
        const dismissalKey = `speakready.firstSteps.dismissed.${userId}`;
        const returnAfterSetupKey = `speakready.firstSteps.returnAfterSetup.${userId}`;
        const returnAfterTourKey = `speakready.firstSteps.returnAfterTour.${userId}`;
        const personalizationForm = modalElement.querySelector('[data-sr-first-steps-personalization-form]');
        const personalizationFormStatus = modalElement.querySelector('[data-sr-first-steps-form-status]');
        const personalizationDetails = modalElement.querySelector('[data-sr-personalization-details]');
        const personalizationToggle = modalElement.querySelector('[data-sr-personalization-toggle]');
        // Named controls called "action" shadow HTMLFormElement.action and
        // return a RadioNodeList. Read the attribute to keep the real URL.
        const personalizationEndpoint = personalizationForm?.getAttribute('action')?.trim()
            || personalizationForm?.dataset.srPersonalizationEndpoint?.trim()
            || '';

        function sessionGet(key) {
            try { return window.sessionStorage.getItem(key); } catch (error) { return null; }
        }

        function sessionSet(key, value) {
            try { window.sessionStorage.setItem(key, value); } catch (error) {}
        }

        function sessionRemove(key) {
            try { window.sessionStorage.removeItem(key); } catch (error) {}
        }

        function modalController() {
            if (!window.bootstrap?.Modal) return null;
            return window.bootstrap.Modal.getOrCreateInstance(modalElement, {
                backdrop: true,
                keyboard: true,
            });
        }

        function closeShellMenus() {
            if (typeof window.closeMobileProfile === 'function') window.closeMobileProfile();
            if (typeof window.closeUserHeaderDropdowns === 'function') window.closeUserHeaderDropdowns();
        }

        function openFirstSteps(options = {}) {
            if (options.forgetDismissal !== false) sessionRemove(dismissalKey);
            closeShellMenus();
            modalController()?.show();
        }

        window.openSpeakReadyFirstSteps = openFirstSteps;

        function stepElement(stepName) {
            return Array.from(modalElement.querySelectorAll('[data-sr-onboarding-step]'))
                .find((step) => step.dataset.srOnboardingStep === stepName) || null;
        }

        function ensureStepStatus(step, label) {
            if (!step) return;
            const actions = step.querySelector('[data-sr-step-status-actions]');
            if (!actions) return;
            actions.hidden = false;

            let status = actions.querySelector('.sr-first-steps-status');
            if (!status) {
                status = document.createElement('span');
                status.className = 'sr-first-steps-status';
                actions.prepend(status);
            }
            status.textContent = label;
        }

        function updateProgress() {
            const steps = Array.from(modalElement.querySelectorAll('[data-sr-onboarding-step]'));
            const completeCount = steps.filter((step) => step.classList.contains('is-done')).length;
            const progress = modalElement.querySelector('[data-sr-onboarding-progress]');
            const label = modalElement.querySelector('[data-sr-onboarding-progress-label]');
            const bar = modalElement.querySelector('[data-sr-onboarding-progress-bar]');

            if (progress) {
                progress.setAttribute('aria-label', `${completeCount} of 4 onboarding steps complete`);
                progress.setAttribute('aria-valuenow', String(completeCount));
            }
            if (label) label.textContent = `${completeCount} of 4 complete`;
            if (bar) bar.style.setProperty('--sr-first-progress', `${Math.round((completeCount / 4) * 100)}%`);
            modalElement.dataset.srFirstStepsComplete = completeCount === 4 ? 'true' : 'false';
        }

        function markStepComplete(stepName, label = 'Complete') {
            const step = stepElement(stepName);
            if (!step) return;

            step.classList.add('is-done');
            const icon = step.querySelector('.sr-first-steps-step-icon i');
            if (icon) icon.className = 'fa-solid fa-check';
            ensureStepStatus(step, label);
            updateProgress();
        }

        function applyOnboardingState(state, options = {}) {
            if (!state || typeof state !== 'object') return;
            if (state.recovery_code_saved) markStepComplete('recovery_code_saved');

            if (state.profile_personalized || state.personalization_skipped) {
                markStepComplete('profile_personalized', state.profile_personalized ? 'Complete' : 'Skipped');
                const summary = modalElement.querySelector('[data-sr-personalization-summary]');
                if (summary) {
                    summary.textContent = state.profile_personalized
                        ? 'Your role and practice goal are ready.'
                        : 'Skipped for now; you can update it anytime.';
                }
                if (personalizationToggle) {
                    personalizationToggle.textContent = state.profile_personalized
                        ? 'Edit personalization'
                        : 'Personalize now';
                }
                if (options.collapsePersonalization && personalizationDetails) {
                    personalizationDetails.open = false;
                }
            }

            if (state.setup_tools_completed) markStepComplete('setup_tools_completed');
            if (state.dashboard_tour_completed) markStepComplete('dashboard_tour_completed');
        }

        window.addEventListener('speakready:onboarding-updated', (event) => {
            applyOnboardingState(event.detail || {});
        });

        document.addEventListener('click', (event) => {
            const launcher = event.target.closest('[data-sr-open-onboarding-modal]');
            if (launcher) {
                event.preventDefault();
                openFirstSteps();
                return;
            }

            const setupButton = event.target.closest('[data-sr-open-setup-tools]');
            if (setupButton && modalElement.contains(setupButton)) {
                event.preventDefault();
                const setupModalElement = document.getElementById('dashboardSetupToolsModal');
                if (!setupModalElement || !window.bootstrap?.Modal) {
                    sessionSet(returnAfterSetupKey, '1');
                    window.location.assign(modalElement.dataset.srDashboardToolsUrl);
                    return;
                }

                modalElement.addEventListener('hidden.bs.modal', () => {
                    const setupModal = window.bootstrap.Modal.getOrCreateInstance(setupModalElement);
                    setupModal.show();

                    setupModalElement.addEventListener('hidden.bs.modal', () => {
                        window.setTimeout(() => modalController()?.show(), 140);
                    }, { once: true });
                }, { once: true });
                modalController()?.hide();
                return;
            }

            const tourButton = event.target.closest('[data-sr-start-dashboard-tour]');
            if (tourButton && modalElement.contains(tourButton)) {
                event.preventDefault();
                const dashboardTourUrl = new URL(modalElement.dataset.srDashboardTourUrl, window.location.origin);
                const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
                const dashboardPath = dashboardTourUrl.pathname.replace(/\/$/, '') || '/';

                sessionSet(returnAfterTourKey, '1');
                if (currentPath !== dashboardPath) {
                    window.location.assign(modalElement.dataset.srDashboardTourUrl);
                    return;
                }

                modalElement.addEventListener('hidden.bs.modal', () => {
                    let attempts = 0;
                    const startTour = () => {
                        if (typeof window.startOnboardingTour === 'function') {
                            window.startOnboardingTour();
                            return;
                        }
                        attempts += 1;
                        if (attempts < 20) {
                            window.setTimeout(startTour, 100);
                            return;
                        }

                        window.location.assign(modalElement.dataset.srDashboardTourUrl);
                    };
                    window.setTimeout(startTour, 120);
                }, { once: true });
                modalController()?.hide();
            }
        });

        window.addEventListener('speakready:dashboard-tour-closed', () => {
            if (sessionGet(returnAfterTourKey) !== '1') return;
            sessionRemove(returnAfterTourKey);
            window.setTimeout(() => openFirstSteps({ forgetDismissal: false }), 160);
        });

        modalElement.querySelectorAll('[data-sr-first-steps-later]').forEach((button) => {
            button.addEventListener('click', () => sessionSet(dismissalKey, '1'));
        });

        function setFormStatus(message, type = 'info') {
            if (!personalizationFormStatus) return;
            personalizationFormStatus.textContent = message || '';
            personalizationFormStatus.dataset.type = type;
            personalizationFormStatus.classList.toggle('show', Boolean(message));
        }

        personalizationForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submitter = event.submitter || personalizationForm.querySelector('button[type="submit"]:focus');
            const action = submitter?.value === 'skip' ? 'skip' : 'save';

            if (action === 'save' && !personalizationForm.checkValidity()) {
                personalizationForm.reportValidity();
                return;
            }

            if (!personalizationEndpoint) {
                setFormStatus('Personalization cannot be saved because its endpoint is unavailable. Refresh and try again.', 'error');
                return;
            }

            const buttons = Array.from(personalizationForm.querySelectorAll('button[type="submit"]'));
            buttons.forEach((button) => {
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
            });
            setFormStatus(action === 'skip' ? 'Saving your choice...' : 'Saving your personalization...');

            const formData = new FormData(personalizationForm);
            formData.set('action', action);

            try {
                const response = await fetch(personalizationEndpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: formData,
                });
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const firstError = Object.values(payload.errors || {}).flat()[0];
                    throw new Error(firstError || payload.message || 'Personalization could not be saved.');
                }

                applyOnboardingState(payload.onboarding || {}, { collapsePersonalization: true });
                window.dispatchEvent(new CustomEvent('speakready:onboarding-updated', {
                    detail: payload.onboarding || {},
                }));
                setFormStatus(payload.message || 'Your personalization has been saved.', 'success');
                personalizationToggle?.focus({ preventScroll: true });
            } catch (error) {
                setFormStatus(error.message || 'Personalization could not be saved. Please try again.', 'error');
            } finally {
                buttons.forEach((button) => {
                    button.disabled = false;
                    button.removeAttribute('aria-busy');
                });
            }
        });

        function removeOnboardingQuery() {
            const url = new URL(window.location.href);
            if (!url.searchParams.has('onboarding')) return;
            url.searchParams.delete('onboarding');
            window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
        }

        function openRequestedSetupTools() {
            const mode = new URLSearchParams(window.location.search).get('onboarding');
            if (mode !== 'tools') return false;

            const setupModalElement = document.getElementById('dashboardSetupToolsModal');
            if (!setupModalElement || !window.bootstrap?.Modal) return false;
            window.setTimeout(() => {
                setupModalElement.addEventListener('hidden.bs.modal', () => {
                    if (sessionGet(returnAfterSetupKey) !== '1') return;
                    sessionRemove(returnAfterSetupKey);
                    window.setTimeout(() => openFirstSteps({ forgetDismissal: false }), 140);
                }, { once: true });
                window.bootstrap.Modal.getOrCreateInstance(setupModalElement).show();
                removeOnboardingQuery();
            }, 260);
            return true;
        }

        updateProgress();

        if (openRequestedSetupTools()) return;

        if (modalElement.dataset.srFirstStepsAutoShow === 'true' && !sessionGet(dismissalKey)) {
            const showWhenClear = () => {
                const openModal = document.querySelector('.modal.show');
                if (openModal && openModal !== modalElement) {
                    openModal.addEventListener('hidden.bs.modal', () => window.setTimeout(showWhenClear, 120), { once: true });
                    return;
                }
                openFirstSteps({ forgetDismissal: false });
            };
            window.setTimeout(showWhenClear, 420);
        }
    })();
</script>
