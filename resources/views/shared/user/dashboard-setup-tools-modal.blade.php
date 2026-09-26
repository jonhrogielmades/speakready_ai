<div class="modal fade sr-dashboard-coach-modal sr-dashboard-setup-modal" id="dashboardSetupToolsModal" tabindex="-1" aria-labelledby="dashboardSetupToolsModalTitle" aria-hidden="true" data-sr-dashboard-setup-modal>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="dashboardSetupToolsForm" data-sr-dashboard-setup-form>
                <div class="modal-header">
                    <div class="sr-dashboard-coach-heading">
                        <span class="sr-dashboard-coach-icon sr-dashboard-setup-icon"><i class="fa-solid fa-sliders"></i></span>
                        <div>
                            <h5 class="modal-title" id="dashboardSetupToolsModalTitle">Setup tools</h5>
                            <p>Choose the tools to prepare for voice practice and interview sessions.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-sr-setup-later aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sr-dashboard-setup-list" role="group" aria-label="Dashboard tool permissions">
                        <label class="sr-dashboard-setup-tool" data-sr-setup-tool-row="microphone">
                            <span class="sr-dashboard-setup-tool-icon"><i class="fa-solid fa-microphone"></i></span>
                            <span class="sr-dashboard-setup-tool-copy">
                                <strong>Microphone</strong>
                                <small>Voice answers and AI coach prompts.</small>
                            </span>
                            <span class="sr-dashboard-setup-switch">
                                <input type="checkbox" data-sr-setup-tool="microphone" checked>
                                <span class="sr-dashboard-setup-switch-ui" aria-hidden="true"></span>
                            </span>
                            <span class="sr-dashboard-setup-tool-status" data-sr-setup-tool-status="microphone">On</span>
                        </label>

                        <label class="sr-dashboard-setup-tool" data-sr-setup-tool-row="camera">
                            <span class="sr-dashboard-setup-tool-icon"><i class="fa-solid fa-camera"></i></span>
                            <span class="sr-dashboard-setup-tool-copy">
                                <strong>Camera</strong>
                                <small>Camera checks for monitored practice.</small>
                            </span>
                            <span class="sr-dashboard-setup-switch">
                                <input type="checkbox" data-sr-setup-tool="camera" checked>
                                <span class="sr-dashboard-setup-switch-ui" aria-hidden="true"></span>
                            </span>
                            <span class="sr-dashboard-setup-tool-status" data-sr-setup-tool-status="camera">On</span>
                        </label>

                        <label class="sr-dashboard-setup-tool" data-sr-setup-tool-row="notifications">
                            <span class="sr-dashboard-setup-tool-icon"><i class="fa-regular fa-bell"></i></span>
                            <span class="sr-dashboard-setup-tool-copy">
                                <strong>Notifications</strong>
                                <small>Practice reminders and progress alerts.</small>
                            </span>
                            <span class="sr-dashboard-setup-switch">
                                <input type="checkbox" data-sr-setup-tool="notifications">
                                <span class="sr-dashboard-setup-switch-ui" aria-hidden="true"></span>
                            </span>
                            <span class="sr-dashboard-setup-tool-status" data-sr-setup-tool-status="notifications">Off</span>
                        </label>
                    </div>

                    <div class="sr-dashboard-coach-status sr-dashboard-setup-status" id="dashboardSetupToolsStatus" role="status" aria-live="polite"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sr-dashboard-coach-secondary sr-dashboard-setup-later" data-bs-dismiss="modal" data-sr-setup-later>Do later</button>
                    <button type="submit" class="sr-dashboard-coach-submit sr-dashboard-setup-allow" id="dashboardSetupToolsAllow"><i class="fa-solid fa-check"></i> Allow</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        function initDashboardSetupToolsModal() {
            const form = document.getElementById('dashboardSetupToolsForm');
            if (!form || form.dataset.bound === 'true') return;

            form.dataset.bound = 'true';

            const modalElement = document.getElementById('dashboardSetupToolsModal');
            const allowButton = document.getElementById('dashboardSetupToolsAllow');
            const status = document.getElementById('dashboardSetupToolsStatus');
            const toggles = Array.from(form.querySelectorAll('[data-sr-setup-tool]'));
            const storageKey = 'speakready.dashboardSetupTools.v1';
            const snoozeKey = 'speakready.dashboardSetupTools.snoozed';
            let completed = false;
            let submitActive = false;

            function safeLocalGet(key) {
                try {
                    return window.localStorage.getItem(key);
                } catch (error) {
                    return null;
                }
            }

            function safeLocalSet(key, value) {
                try {
                    window.localStorage.setItem(key, value);
                } catch (error) {
                    return false;
                }

                return true;
            }

            function safeSessionGet(key) {
                try {
                    return window.sessionStorage.getItem(key);
                } catch (error) {
                    return null;
                }
            }

            function safeSessionSet(key, value) {
                try {
                    window.sessionStorage.setItem(key, value);
                } catch (error) {
                    return false;
                }

                return true;
            }

            function safeSessionRemove(key) {
                try {
                    window.sessionStorage.removeItem(key);
                } catch (error) {
                    return false;
                }

                return true;
            }

            function hasCompletedSetup() {
                const saved = safeLocalGet(storageKey);
                if (!saved) return false;

                try {
                    const parsed = JSON.parse(saved);
                    return Boolean(parsed && parsed.completedAt);
                } catch (error) {
                    return false;
                }
            }

            function setStatus(message, type = 'info') {
                if (!status) return;

                status.textContent = message || '';
                status.dataset.type = type;
                status.classList.toggle('show', Boolean(message));
            }

            function rowFor(tool) {
                return form.querySelector('[data-sr-setup-tool-row="' + tool + '"]');
            }

            function statusFor(tool) {
                return form.querySelector('[data-sr-setup-tool-status="' + tool + '"]');
            }

            function setToolStatus(tool, message, state = 'ready') {
                const row = rowFor(tool);
                const statusElement = statusFor(tool);

                if (row) row.dataset.state = state;
                if (statusElement) statusElement.textContent = message;
            }

            function syncToggle(input) {
                const tool = input.dataset.srSetupTool;
                const enabled = input.checked;
                const row = rowFor(tool);

                if (row) row.classList.toggle('is-off', !enabled);
                setToolStatus(tool, enabled ? 'On' : 'Off', enabled ? 'ready' : 'off');
            }

            function toolLabel(tool) {
                return {
                    microphone: 'Microphone',
                    camera: 'Camera',
                    notifications: 'Notifications',
                }[tool] || tool;
            }

            function permissionErrorMessage(tool, error) {
                const errorName = error && error.name ? error.name : '';

                if (errorName === 'NotAllowedError' || errorName === 'SecurityError') {
                    return toolLabel(tool) + ' blocked';
                }

                if (errorName === 'NotFoundError' || errorName === 'OverconstrainedError') {
                    return toolLabel(tool) + ' not found';
                }

                if (errorName === 'NotReadableError') {
                    return toolLabel(tool) + ' busy';
                }

                return toolLabel(tool) + ' unavailable';
            }

            async function requestMediaTool(tool) {
                if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                    setToolStatus(tool, 'Unsupported', 'error');
                    return 'unsupported';
                }

                const constraints = tool === 'camera' ? { video: true } : { audio: true };

                try {
                    const stream = await navigator.mediaDevices.getUserMedia(constraints);
                    stream.getTracks().forEach((track) => track.stop());
                    setToolStatus(tool, 'Allowed', 'success');
                    return 'granted';
                } catch (error) {
                    setToolStatus(tool, permissionErrorMessage(tool, error), 'error');
                    return error && error.name ? error.name : 'error';
                }
            }

            async function requestNotifications() {
                if (!('Notification' in window) || typeof Notification.requestPermission !== 'function') {
                    setToolStatus('notifications', 'Unsupported', 'error');
                    return 'unsupported';
                }

                if (Notification.permission === 'granted') {
                    setToolStatus('notifications', 'Allowed', 'success');
                    return 'granted';
                }

                if (Notification.permission === 'denied') {
                    setToolStatus('notifications', 'Blocked', 'error');
                    return 'denied';
                }

                const permission = await Notification.requestPermission();
                setToolStatus('notifications', permission === 'granted' ? 'Allowed' : 'Blocked', permission === 'granted' ? 'success' : 'error');
                return permission;
            }

            async function requestTool(tool) {
                setToolStatus(tool, 'Requesting', 'pending');

                if (tool === 'notifications') {
                    return requestNotifications();
                }

                return requestMediaTool(tool);
            }

            function saveSetup(results) {
                completed = true;
                safeLocalSet(storageKey, JSON.stringify({
                    completedAt: new Date().toISOString(),
                    tools: results,
                }));
                safeSessionRemove(snoozeKey);
            }

            function dismissModal() {
                if (window.bootstrap && modalElement) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                }
            }

            toggles.forEach((input) => {
                syncToggle(input);
                input.addEventListener('change', () => {
                    syncToggle(input);
                    setStatus('');
                });
            });

            form.querySelectorAll('[data-sr-setup-later]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!completed) safeSessionSet(snoozeKey, '1');
                });
            });

            modalElement?.addEventListener('hidden.bs.modal', () => {
                if (!completed && !submitActive) safeSessionSet(snoozeKey, '1');
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (submitActive) return;

                const enabledTools = toggles
                    .filter((input) => input.checked)
                    .map((input) => input.dataset.srSetupTool);
                const results = {};

                submitActive = true;
                if (allowButton) {
                    allowButton.disabled = true;
                    allowButton.setAttribute('aria-busy', 'true');
                    allowButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
                }

                setStatus(enabledTools.length ? 'Requesting selected tools...' : 'Saved with all tools off.', enabledTools.length ? 'info' : 'success');

                try {
                    const requestOrder = enabledTools.slice().sort((left, right) => {
                        if (left === 'notifications') return -1;
                        if (right === 'notifications') return 1;
                        return 0;
                    });

                    for (const tool of requestOrder) {
                        results[tool] = await requestTool(tool);
                    }

                    toggles
                        .filter((input) => !input.checked)
                        .forEach((input) => {
                            results[input.dataset.srSetupTool] = 'off';
                        });

                    saveSetup(results);
                    setStatus('Setup saved. You can change browser permissions anytime from site settings.', 'success');
                    window.setTimeout(dismissModal, 650);
                } catch (error) {
                    setStatus('Setup could not finish. Check browser permissions and try again.', 'error');
                } finally {
                    submitActive = false;
                    if (allowButton) {
                        allowButton.disabled = false;
                        allowButton.setAttribute('aria-busy', 'false');
                        allowButton.innerHTML = '<i class="fa-solid fa-check"></i> Allow';
                    }
                }
            });

            if (!hasCompletedSetup() && !safeSessionGet(snoozeKey) && window.bootstrap && modalElement) {
                window.setTimeout(() => {
                    if (hasCompletedSetup() || safeSessionGet(snoozeKey)) return;

                    window.bootstrap.Modal.getOrCreateInstance(modalElement, {
                        backdrop: 'static',
                        keyboard: true,
                    }).show();
                }, 220);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardSetupToolsModal);
        } else {
            initDashboardSetupToolsModal();
        }
    })();
</script>
@endpush
