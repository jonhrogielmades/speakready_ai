@once
@push('styles')
<style data-sr-dashboard-setup-styles>
    .modal.sr-dashboard-setup-modal {
        --sr-setup-bg: #ffffff;
        --sr-setup-surface: #f8fafc;
        --sr-setup-line: rgba(148, 163, 184, 0.24);
        --sr-setup-text: #0f172a;
        --sr-setup-muted: #475569;
        --sr-setup-accent: #2563eb;
        --sr-setup-ok: #047857;
        --sr-setup-danger: #dc2626;
    }

    .modal.sr-dashboard-setup-modal .modal-dialog {
        width: min(430px, calc(100vw - 24px));
        max-width: min(430px, calc(100vw - 24px));
        margin-inline: auto;
    }

    .modal.sr-dashboard-setup-modal .modal-content {
        overflow: hidden;
        border: 1px solid var(--sr-setup-line);
        border-radius: 18px;
        background: var(--sr-setup-bg);
        color: var(--sr-setup-text);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
    }

    .modal.sr-dashboard-setup-modal .modal-header,
    .modal.sr-dashboard-setup-modal .modal-body,
    .modal.sr-dashboard-setup-modal .modal-footer {
        padding-inline: clamp(16px, 4.5vw, 22px);
    }

    .modal.sr-dashboard-setup-modal .modal-header {
        align-items: flex-start;
        gap: 12px;
        padding-top: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        background:
            linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(20, 184, 166, 0.06)),
            var(--sr-setup-bg);
    }

    .modal.sr-dashboard-setup-modal .btn-close {
        flex: 0 0 auto;
        margin: 2px 0 0;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-coach-heading {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr);
        align-items: start;
        gap: 11px;
        min-width: 0;
        margin: 0;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 12px;
        background: rgba(37, 99, 235, 0.1);
        color: var(--sr-setup-accent);
        font-size: 0.92rem;
    }

    .modal.sr-dashboard-setup-modal .modal-title {
        margin: 0;
        color: var(--sr-setup-text);
        font-size: clamp(1rem, 4.5vw, 1.12rem);
        line-height: 1.15;
        font-weight: 900;
        letter-spacing: 0;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-coach-heading p {
        margin: 5px 0 0;
        color: var(--sr-setup-muted);
        font-size: 0.86rem;
        line-height: 1.35;
        font-weight: 650;
    }

    .modal.sr-dashboard-setup-modal .modal-body {
        display: grid;
        gap: 12px;
        padding-top: 14px;
        padding-bottom: 14px;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-intro {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 10px;
        min-height: 44px;
        padding: 10px 12px;
        border: 1px solid rgba(37, 99, 235, 0.16);
        border-radius: 12px;
        background: rgba(37, 99, 235, 0.06);
        color: var(--sr-setup-muted);
        font-size: 0.73rem;
        line-height: 1.25;
        font-weight: 850;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-intro span,
    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-intro strong {
        min-width: 0;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-intro i {
        color: var(--sr-setup-accent);
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-intro strong {
        justify-self: end;
        color: var(--sr-setup-ok);
        font-size: 0.68rem;
        line-height: 1.2;
        text-align: right;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-list {
        display: grid;
        gap: 10px;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool {
        position: relative;
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr) 58px;
        grid-template-areas:
            "icon copy switch"
            "icon copy status";
        align-items: center;
        column-gap: 10px;
        row-gap: 5px;
        min-height: 70px;
        margin: 0;
        padding: 11px;
        border: 1px solid var(--sr-setup-line);
        border-radius: 14px;
        background: var(--sr-setup-surface);
        cursor: pointer;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool-icon {
        grid-area: icon;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: rgba(37, 99, 235, 0.1);
        color: var(--sr-setup-accent);
        font-size: 0.95rem;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool-copy {
        grid-area: copy;
        min-width: 0;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool-copy strong {
        display: block;
        color: var(--sr-setup-text);
        font-size: 0.82rem;
        line-height: 1.18;
        font-weight: 900;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool-copy small {
        display: block;
        margin-top: 3px;
        color: var(--sr-setup-muted);
        font-size: 0.71rem;
        line-height: 1.3;
        font-weight: 650;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-switch {
        grid-area: switch;
        justify-self: end;
        position: relative;
        display: inline-flex;
        align-items: center;
        width: 50px;
        height: 28px;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-switch input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-switch-ui {
        position: relative;
        display: block;
        width: 48px;
        height: 28px;
        border: 1px solid rgba(148, 163, 184, 0.42);
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.22);
        transition: background-color 0.18s ease, border-color 0.18s ease;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-switch-ui::after {
        content: "";
        position: absolute;
        top: 3px;
        left: 3px;
        width: 20px;
        height: 20px;
        border-radius: 999px;
        background: #ffffff;
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.2);
        transition: transform 0.18s ease;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-switch input:checked + .sr-dashboard-setup-switch-ui {
        border-color: rgba(37, 99, 235, 0.48);
        background: linear-gradient(135deg, #2563eb, #06b6d4);
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-switch input:checked + .sr-dashboard-setup-switch-ui::after {
        transform: translateX(20px);
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool-status {
        grid-area: status;
        justify-self: end;
        min-width: 0;
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--sr-setup-muted);
        font-size: 0.68rem;
        line-height: 1;
        font-weight: 850;
        text-transform: none;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool[data-state="success"] .sr-dashboard-setup-tool-status {
        color: var(--sr-setup-ok);
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool[data-state="error"] .sr-dashboard-setup-tool-status {
        color: var(--sr-setup-danger);
    }

    .modal.sr-dashboard-setup-modal .modal-footer {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: 10px;
        padding-top: 13px;
        padding-bottom: 14px;
        border-top: 1px solid rgba(148, 163, 184, 0.16);
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-footer-note {
        margin: 0;
        color: var(--sr-setup-muted);
        font-size: 0.72rem;
        line-height: 1.3;
        font-weight: 750;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-later,
    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-allow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 0 14px;
        border-radius: 12px;
        font-size: 0.78rem;
        font-weight: 850;
        white-space: nowrap;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-later {
        border: 1px solid var(--sr-setup-line);
        background: #ffffff;
        color: var(--sr-setup-text);
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-allow {
        border: 1px solid rgba(37, 99, 235, 0.22);
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color: #ffffff;
    }

    html:not(.lm) .modal.sr-dashboard-setup-modal,
    html[data-theme="dark"] .modal.sr-dashboard-setup-modal,
    body.dm .modal.sr-dashboard-setup-modal {
        --sr-setup-bg: #0f172a;
        --sr-setup-surface: rgba(15, 23, 42, 0.78);
        --sr-setup-line: rgba(147, 197, 253, 0.18);
        --sr-setup-text: #f8fafc;
        --sr-setup-muted: #cbd5e1;
    }

    html:not(.lm) .modal.sr-dashboard-setup-modal .modal-content,
    html[data-theme="dark"] .modal.sr-dashboard-setup-modal .modal-content,
    body.dm .modal.sr-dashboard-setup-modal .modal-content {
        box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
    }

    html:not(.lm) .modal.sr-dashboard-setup-modal .sr-dashboard-setup-later,
    html[data-theme="dark"] .modal.sr-dashboard-setup-modal .sr-dashboard-setup-later,
    body.dm .modal.sr-dashboard-setup-modal .sr-dashboard-setup-later {
        background: rgba(15, 23, 42, 0.72);
        color: var(--sr-setup-text);
    }

    @media (max-width: 575px) {
        .modal.sr-dashboard-setup-modal .modal-dialog {
            width: min(342px, calc(100vw - 18px));
            max-width: min(342px, calc(100vw - 18px));
            margin-top: max(10px, env(safe-area-inset-top, 0px));
            margin-bottom: max(10px, env(safe-area-inset-bottom, 0px));
        }

        .modal.sr-dashboard-setup-modal .modal-body {
            max-height: calc(100dvh - 196px);
            overflow: auto;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-intro {
            grid-template-columns: 1fr;
            gap: 5px;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-intro strong {
            justify-self: start;
            text-align: left;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool {
            grid-template-columns: 34px minmax(0, 1fr) 54px;
            min-height: 68px;
            padding: 10px;
            column-gap: 9px;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool-icon {
            width: 34px;
            height: 34px;
            border-radius: 11px;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool-copy strong {
            font-size: 0.78rem;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool-copy small {
            font-size: 0.66rem;
            line-height: 1.25;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-switch {
            width: 48px;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-switch-ui {
            width: 46px;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-switch input:checked + .sr-dashboard-setup-switch-ui::after {
            transform: translateX(18px);
        }

        .modal.sr-dashboard-setup-modal .modal-footer {
            grid-template-columns: 1fr 1fr;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-footer-note {
            grid-column: 1 / -1;
            text-align: center;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-later,
        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-allow {
            width: 100%;
            min-width: 0;
        }
    }

    @media (max-width: 350px) {
        .modal.sr-dashboard-setup-modal .modal-header,
        .modal.sr-dashboard-setup-modal .modal-body,
        .modal.sr-dashboard-setup-modal .modal-footer {
            padding-inline: 14px;
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool {
            grid-template-columns: 32px minmax(0, 1fr) 50px;
            column-gap: 8px;
        }
    }
</style>
@endpush
@endonce

<div class="modal fade sr-dashboard-coach-modal sr-dashboard-setup-modal" id="dashboardSetupToolsModal" tabindex="-1" aria-labelledby="dashboardSetupToolsModalTitle" aria-hidden="true" data-sr-dashboard-setup-modal data-sr-setup-auto-show="{{ ! empty($setupToolsAutoShow) ? 'true' : 'false' }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="dashboardSetupToolsForm" data-sr-dashboard-setup-form>
                <div class="modal-header">
                    <div class="sr-dashboard-coach-heading">
                        <span class="sr-dashboard-coach-icon sr-dashboard-setup-icon"><i class="fa-solid fa-sliders"></i></span>
                        <div>
                            <h5 class="modal-title" id="dashboardSetupToolsModalTitle">Setup tools</h5>
                            <p>Prepare camera, microphone, and alerts for practice.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-sr-setup-later aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="sr-dashboard-setup-intro" role="note">
                        <span><i class="fa-solid fa-shield-halved"></i> Browser permission check</span>
                        <strong>Ready for desktop and mobile</strong>
                    </div>

                    <div class="sr-dashboard-setup-list" role="group" aria-label="Dashboard tool permissions">
                        <label class="sr-dashboard-setup-tool" data-sr-setup-tool-row="microphone">
                            <span class="sr-dashboard-setup-tool-icon"><i class="fa-solid fa-microphone"></i></span>
                            <span class="sr-dashboard-setup-tool-copy">
                                <strong>Microphone</strong>
                                <small>Voice answers and AI coach prompts.</small>
                            </span>
                            <span class="sr-dashboard-setup-switch">
                                <input type="checkbox" role="switch" aria-label="Enable microphone" aria-checked="true" data-sr-setup-tool="microphone" checked>
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
                                <input type="checkbox" role="switch" aria-label="Enable camera" aria-checked="true" data-sr-setup-tool="camera" checked>
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
                                <input type="checkbox" role="switch" aria-label="Enable notifications" aria-checked="false" data-sr-setup-tool="notifications">
                                <span class="sr-dashboard-setup-switch-ui" aria-hidden="true"></span>
                            </span>
                            <span class="sr-dashboard-setup-tool-status" data-sr-setup-tool-status="notifications">Off</span>
                        </label>
                    </div>

                    <div class="sr-dashboard-coach-status sr-dashboard-setup-status" id="dashboardSetupToolsStatus" role="status" aria-live="polite"></div>
                </div>
                <div class="modal-footer">
                    <span class="sr-dashboard-setup-footer-note">Prompts open only for tools left on.</span>
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
            const shouldAutoShow = modalElement?.dataset.srSetupAutoShow === 'true';
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

                input.setAttribute('aria-checked', enabled ? 'true' : 'false');
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
                if (window.SpeakReadyPush && typeof window.SpeakReadyPush.enable === 'function') {
                    try {
                        const result = await window.SpeakReadyPush.enable({ sendTest: true });

                        if (result.status === 'granted') {
                            if (result.test && result.test.success === false && result.test.skipped !== true) {
                                setToolStatus('notifications', 'Test failed', 'error');
                                return 'test_failed';
                            }

                            setToolStatus('notifications', 'Ready', 'success');
                            return 'granted';
                        }

                        if (result.status === 'unconfigured') {
                            setToolStatus('notifications', 'Server setup needed', 'error');
                            return 'unconfigured';
                        }

                        if (result.status === 'unsupported') {
                            setToolStatus('notifications', 'Unsupported', 'error');
                            return 'unsupported';
                        }

                        setToolStatus('notifications', 'Blocked', 'error');
                        return result.status || 'denied';
                    } catch (error) {
                        setToolStatus('notifications', permissionErrorMessage('notifications', error), 'error');
                        return error && error.name ? error.name : 'error';
                    }
                }

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

                    const blockedTools = enabledTools.filter((tool) => results[tool] !== 'granted');

                    if (blockedTools.length) {
                        setStatus('Some selected tools still need browser permission. Allow them in site settings, switch them off, or choose Do later.', 'warning');
                        return;
                    }

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

            if (shouldAutoShow && !hasCompletedSetup() && !safeSessionGet(snoozeKey) && window.bootstrap && modalElement) {
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
