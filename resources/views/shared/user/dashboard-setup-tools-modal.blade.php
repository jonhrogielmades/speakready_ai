@php
    $dashboardSetupState = is_array($onboardingState ?? null) ? $onboardingState : [];
    $dashboardSavedTools = is_array($dashboardSetupState['setup_tools'] ?? null) ? $dashboardSetupState['setup_tools'] : [];
    $dashboardSetupCompleted = (bool) ($dashboardSetupState['setup_tools_completed'] ?? false);
    $dashboardToolIsEnabled = static function (string $tool, bool $default) use ($dashboardSavedTools): bool {
        if (! array_key_exists($tool, $dashboardSavedTools)) return $default;

        return $dashboardSavedTools[$tool] !== 'off';
    };
@endphp

@once
@push('styles')
<style data-page-style="dashboard-setup-tools-modal" data-sr-dashboard-setup-styles>
    .modal.sr-dashboard-setup-modal {
        --sr-setup-bg: #ffffff;
        --sr-setup-surface: #f8fafc;
        --sr-setup-line: rgba(148, 163, 184, 0.24);
        --sr-setup-text: #0f172a;
        --sr-setup-muted: #475569;
        --sr-setup-accent: #2563eb;
        --sr-setup-ok: #047857;
        --sr-setup-danger: #dc2626;
        --sr-setup-safe-top: env(safe-area-inset-top, 0px);
        --sr-setup-safe-bottom: env(safe-area-inset-bottom, 0px);
        --sr-setup-viewport-gap: 16px;
        --sr-setup-viewport-gap-total: 32px;
    }

    .modal.sr-dashboard-setup-modal .modal-dialog {
        width: min(430px, calc(100vw - 24px));
        max-width: min(430px, calc(100vw - 24px));
        margin-inline: auto;
    }

    .modal.sr-dashboard-setup-modal .modal-dialog.modal-dialog-centered,
    body.user-mobile-shell .modal.sr-dashboard-setup-modal .modal-dialog.modal-dialog-centered,
    body.user-desktop-shell .modal.sr-dashboard-setup-modal .modal-dialog.modal-dialog-centered {
        display: flex !important;
        align-items: center !important;
        min-height: calc(100vh - var(--sr-setup-viewport-gap-total) - var(--sr-setup-safe-top) - var(--sr-setup-safe-bottom)) !important;
        min-height: calc(100dvh - var(--sr-setup-viewport-gap-total) - var(--sr-setup-safe-top) - var(--sr-setup-safe-bottom)) !important;
        margin-top: calc(var(--sr-setup-viewport-gap) + var(--sr-setup-safe-top)) !important;
        margin-bottom: calc(var(--sr-setup-viewport-gap) + var(--sr-setup-safe-bottom)) !important;
    }

    .modal.sr-dashboard-setup-modal .modal-content {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - var(--sr-setup-viewport-gap-total) - var(--sr-setup-safe-top) - var(--sr-setup-safe-bottom));
        max-height: calc(100dvh - var(--sr-setup-viewport-gap-total) - var(--sr-setup-safe-top) - var(--sr-setup-safe-bottom));
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
        min-height: 0;
        overflow: auto;
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

    .modal.sr-dashboard-setup-modal .sr-dashboard-setup-tool[data-state="error"] .sr-dashboard-setup-switch input:checked + .sr-dashboard-setup-switch-ui {
        border-color: rgba(220, 38, 38, 0.55);
        background: linear-gradient(135deg, #dc2626, #f97316);
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr) auto;
        align-items: center;
        gap: 10px;
        padding: 12px;
        border: 1px solid rgba(220, 38, 38, 0.24);
        border-radius: 13px;
        background: rgba(254, 242, 242, 0.92);
        color: #7f1d1d;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help[hidden] {
        display: none;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 11px;
        background: rgba(220, 38, 38, 0.11);
        color: #dc2626;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help-copy {
        min-width: 0;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help-copy strong {
        display: block;
        font-size: 0.76rem;
        line-height: 1.25;
        font-weight: 900;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help-copy small {
        display: block;
        margin-top: 3px;
        color: #991b1b;
        font-size: 0.68rem;
        line-height: 1.4;
        font-weight: 650;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-camera-retry {
        min-height: 34px;
        padding: 0 10px;
        border: 1px solid rgba(220, 38, 38, 0.28);
        border-radius: 9px;
        background: #ffffff;
        color: #b91c1c;
        font-size: 0.7rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .modal.sr-dashboard-setup-modal .sr-dashboard-media-control {
        display: block;
        min-width: 0;
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

    html:not(.lm) .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help,
    html[data-theme="dark"] .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help,
    body.dm .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help {
        border-color: rgba(248, 113, 113, 0.28);
        background: rgba(127, 29, 29, 0.22);
        color: #fecaca;
    }

    html:not(.lm) .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help-copy small,
    html[data-theme="dark"] .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help-copy small,
    body.dm .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help-copy small {
        color: #fca5a5;
    }

    html:not(.lm) .modal.sr-dashboard-setup-modal .sr-dashboard-camera-retry,
    html[data-theme="dark"] .modal.sr-dashboard-setup-modal .sr-dashboard-camera-retry,
    body.dm .modal.sr-dashboard-setup-modal .sr-dashboard-camera-retry {
        background: rgba(15, 23, 42, 0.72);
        color: #fecaca;
    }

    @media (max-width: 575px) {
        .modal.sr-dashboard-setup-modal {
            --sr-setup-viewport-gap: 10px;
            --sr-setup-viewport-gap-total: 20px;
        }

        .modal.sr-dashboard-setup-modal .modal-dialog {
            width: min(342px, calc(100vw - 18px));
            max-width: min(342px, calc(100vw - 18px));
        }

        .modal.sr-dashboard-setup-modal .modal-body {
            max-height: none;
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

        .modal.sr-dashboard-setup-modal .sr-dashboard-camera-help {
            grid-template-columns: 32px minmax(0, 1fr);
        }

        .modal.sr-dashboard-setup-modal .sr-dashboard-media-control {
            grid-column: 2;
            justify-self: start;
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

<div class="modal fade sr-dashboard-coach-modal sr-dashboard-setup-modal" id="dashboardSetupToolsModal" tabindex="-1" aria-labelledby="dashboardSetupToolsModalTitle" aria-hidden="true" data-sr-dashboard-setup-modal data-sr-setup-auto-show="{{ ! empty($setupToolsAutoShow) ? 'true' : 'false' }}" data-sr-setup-completed="{{ $dashboardSetupCompleted ? 'true' : 'false' }}" data-sr-setup-snoozed="{{ ! empty($dashboardSetupState['setup_tools_snoozed']) ? 'true' : 'false' }}" data-sr-onboarding-user="{{ (int) Auth::id() }}" data-sr-onboarding-progress-url="{{ route('user.onboarding.progress') }}">
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
                        <strong data-sr-live-permission-summary>Check this device</strong>
                    </div>

                    <div class="sr-dashboard-setup-list" role="group" aria-label="Dashboard tool permissions">
                        <label class="sr-dashboard-setup-tool" data-sr-setup-tool-row="microphone">
                            <span class="sr-dashboard-setup-tool-icon"><i class="fa-solid fa-microphone"></i></span>
                            <span class="sr-dashboard-setup-tool-copy">
                                <strong>Microphone</strong>
                                <small>Voice answers and AI coach prompts.</small>
                            </span>
                            <span class="sr-dashboard-setup-switch">
                                <input type="checkbox" role="switch" aria-label="Enable microphone" aria-checked="{{ $dashboardToolIsEnabled('microphone', true) ? 'true' : 'false' }}" data-sr-setup-tool="microphone" @checked($dashboardToolIsEnabled('microphone', true))>
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
                                <input type="checkbox" role="switch" aria-label="Enable camera" aria-checked="{{ $dashboardToolIsEnabled('camera', true) ? 'true' : 'false' }}" data-sr-setup-tool="camera" @checked($dashboardToolIsEnabled('camera', true))>
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
                                <input type="checkbox" role="switch" aria-label="Enable notifications" aria-checked="{{ $dashboardToolIsEnabled('notifications', false) ? 'true' : 'false' }}" data-sr-setup-tool="notifications" @checked($dashboardToolIsEnabled('notifications', false))>
                                <span class="sr-dashboard-setup-switch-ui" aria-hidden="true"></span>
                            </span>
                            <span class="sr-dashboard-setup-tool-status" data-sr-setup-tool-status="notifications">Off</span>
                        </label>
                    </div>

                    <div class="sr-dashboard-camera-help" data-sr-media-permission-help role="alert" hidden>
                        <span class="sr-dashboard-camera-help-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved" data-sr-media-permission-icon></i></span>
                        <span class="sr-dashboard-camera-help-copy">
                            <strong data-sr-media-permission-title>Media permission needs attention</strong>
                            <small data-sr-media-permission-instructions>Allow camera or microphone in this site's browser permissions, then retry.</small>
                        </span>
                        <usermedia class="sr-dashboard-media-control" data-sr-user-media>
                            <button type="button" class="sr-dashboard-camera-retry" data-sr-retry-media>
                                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Retry media
                            </button>
                        </usermedia>
                    </div>

                    <div class="sr-dashboard-camera-help" data-sr-notification-permission-help role="alert" hidden>
                        <span class="sr-dashboard-camera-help-icon" aria-hidden="true"><i class="fa-solid fa-bell"></i></span>
                        <span class="sr-dashboard-camera-help-copy">
                            <strong>Notification permission needs attention</strong>
                            <small data-sr-notification-permission-instructions>Allow notifications in this site's or installed app's settings.</small>
                        </span>
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

@once
@push('scripts')
<script src="{{ asset('js/media-permission-support.js?v=1') }}"></script>
@endpush
@endonce

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
            const livePermissionSummary = form.querySelector('[data-sr-live-permission-summary]');
            const mediaPermissionHelp = form.querySelector('[data-sr-media-permission-help]');
            const mediaPermissionIcon = form.querySelector('[data-sr-media-permission-icon]');
            const mediaPermissionTitle = form.querySelector('[data-sr-media-permission-title]');
            const mediaPermissionInstructions = form.querySelector('[data-sr-media-permission-instructions]');
            const retryMediaElement = form.querySelector('[data-sr-user-media]');
            const retryMediaButton = form.querySelector('[data-sr-retry-media]');
            const notificationPermissionHelp = form.querySelector('[data-sr-notification-permission-help]');
            const notificationPermissionInstructions = form.querySelector('[data-sr-notification-permission-instructions]');
            const mediaPermissionSupport = window.SpeakReadyMediaPermissions || null;
            const onboardingUser = modalElement?.dataset.srOnboardingUser || 'guest';
            const snoozeKey = 'speakready.dashboardSetupTools.snoozed.' + onboardingUser;
            const shouldAutoShow = modalElement?.dataset.srSetupAutoShow === 'true';
            const progressUrl = modalElement?.dataset.srOnboardingProgressUrl || '';
            const setupState = window.__speakReadyDashboardSetupState = window.__speakReadyDashboardSetupState || {
                pending: false,
                ready: false,
            };
            let completed = modalElement?.dataset.srSetupCompleted === 'true';
            let serverSnoozed = modalElement?.dataset.srSetupSnoozed === 'true';
            let submitActive = false;
            let permissionRefreshTimer = null;
            const activeToolRequests = new Map();
            const permissionResults = Object.create(null);
            const permissionWatchers = new Map();
            const mediaPermissionIssues = new Map();

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
                return completed;
            }

            function isModalOpen(element) {
                return Boolean(element && (
                    element.classList.contains('show') ||
                    element.getAttribute('aria-hidden') === 'false'
                ));
            }

            function markSetupReady() {
                setupState.pending = false;
                setupState.ready = true;
                window.dispatchEvent(new CustomEvent('speakready:dashboard-setup-ready'));
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

            function isInstalledWebApp() {
                return Boolean(
                    (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) ||
                    window.navigator.standalone === true
                );
            }

            function mediaToolList(tools) {
                const labels = tools.map((tool) => toolLabel(tool));
                if (labels.length < 2) return labels[0] || 'Media';

                return labels.slice(0, -1).join(', ') + ' and ' + labels[labels.length - 1];
            }

            function mediaFeatureAllowed(tool) {
                if (mediaPermissionSupport && typeof mediaPermissionSupport.featureAllowed === 'function') {
                    return mediaPermissionSupport.featureAllowed(document, tool);
                }

                const policy = document.permissionsPolicy || document.featurePolicy;
                if (!policy || typeof policy.allowsFeature !== 'function') return true;

                try {
                    return policy.allowsFeature(tool);
                } catch (error) {
                    return true;
                }
            }

            function mediaIssueReason(error) {
                if (mediaPermissionSupport && typeof mediaPermissionSupport.issueReason === 'function') {
                    return mediaPermissionSupport.issueReason(error);
                }

                const errorName = error && error.name ? error.name : '';
                if (errorName === 'NotFoundError' || errorName === 'OverconstrainedError') return 'not_found';
                if (errorName === 'NotReadableError' || errorName === 'AbortError') return 'busy';
                if (errorName === 'NotAllowedError' || errorName === 'SecurityError') return 'blocked';

                return 'unavailable';
            }

            function mediaRecoveryInstructions(tools, reason = 'blocked') {
                const mediaNames = mediaToolList(tools);

                if (reason === 'policy_blocked') {
                    return mediaNames + ' is disabled by this page\'s Permissions-Policy. Refresh after the server policy is updated.';
                }

                if (reason === 'insecure_context') {
                    return mediaNames + ' requires a secure HTTPS page. Reopen SpeakReady AI using HTTPS, then retry.';
                }

                if (reason === 'not_found') {
                    return 'No usable ' + mediaNames.toLowerCase() + ' was found. Connect or enable the device, then retry.';
                }

                if (reason === 'busy') {
                    return 'Another app may be using ' + mediaNames.toLowerCase() + '. Close that app, return here, then retry.';
                }

                if (reason === 'unsupported') {
                    return 'This browser cannot provide ' + mediaNames.toLowerCase() + ' access. Update it or open SpeakReady AI in a current Chrome, Edge, Firefox, or Safari browser.';
                }

                const userAgent = navigator.userAgent || '';
                const permissionNames = tools.map((tool) => toolLabel(tool)).join(' and ');

                if (isInstalledWebApp() && /Android/i.test(userAgent)) {
                    return 'Installed web apps keep ' + permissionNames + ' in the browser\'s Site settings, not the SpeakReady App permissions page. Open SpeakReady AI in Chrome, use Site controls > Permissions, allow ' + permissionNames + ', and check Android Privacy plus Chrome app permissions.';
                }

                if (isInstalledWebApp() && /iPad|iPhone|iPod/i.test(userAgent)) {
                    return 'Open SpeakReady AI in Safari, choose Page Settings > Website Settings, allow ' + permissionNames + ', then return here and retry.';
                }

                if (/iPad|iPhone|iPod/i.test(userAgent)) {
                    return 'In Safari, open Page Settings (aA) > Website Settings, set ' + permissionNames + ' to Allow, then retry.';
                }

                if (/Android/i.test(userAgent)) {
                    return 'Open Site controls beside the address bar > Permissions, allow ' + permissionNames + ', and verify Android Privacy plus the browser app\'s ' + permissionNames + ' permissions.';
                }

                return 'Open the lock or site-controls icon beside the address bar, allow ' + permissionNames + ' for this site, then return and retry.';
            }

            function activeMediaIssueTools() {
                return Array.from(mediaPermissionIssues.keys()).filter((tool) => {
                    const input = form.querySelector('[data-sr-setup-tool="' + tool + '"]');
                    return input && input.checked;
                });
            }

            function updateMediaPermissionHelp() {
                if (!mediaPermissionHelp) return;

                const tools = activeMediaIssueTools();
                mediaPermissionHelp.hidden = tools.length === 0;
                if (!tools.length) return;

                const reasonPriority = ['policy_blocked', 'insecure_context', 'blocked', 'busy', 'not_found', 'unsupported', 'unavailable'];
                const reason = reasonPriority.find((candidate) => tools.some((tool) => mediaPermissionIssues.get(tool) === candidate)) || 'blocked';
                const mediaNames = mediaToolList(tools);

                if (mediaPermissionIcon) {
                    mediaPermissionIcon.className = tools.length === 1 && tools[0] === 'camera'
                        ? 'fa-solid fa-camera-rotate'
                        : (tools.length === 1 ? 'fa-solid fa-microphone-lines' : 'fa-solid fa-shield-halved');
                }
                if (mediaPermissionTitle) {
                    mediaPermissionTitle.textContent = {
                        policy_blocked: mediaNames + ' is blocked by the page policy',
                        insecure_context: mediaNames + ' requires HTTPS',
                        not_found: 'No ' + mediaNames.toLowerCase() + ' was detected',
                        busy: mediaNames + ' is being used elsewhere',
                        unsupported: mediaNames + ' is unsupported in this browser',
                        blocked: mediaNames + ' is selected, but browser access is blocked',
                    }[reason] || mediaNames + ' permission needs attention';
                }
                if (mediaPermissionInstructions) {
                    mediaPermissionInstructions.textContent = mediaRecoveryInstructions(tools, reason);
                }
                if (retryMediaButton) {
                    retryMediaButton.innerHTML = '<i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Retry ' + mediaNames.toLowerCase();
                }

                if (retryMediaElement && typeof retryMediaElement.setConstraints === 'function') {
                    const constraints = mediaPermissionSupport && typeof mediaPermissionSupport.constraintsFor === 'function'
                        ? mediaPermissionSupport.constraintsFor(tools)
                        : {
                            ...(tools.includes('camera') ? { video: { facingMode: { ideal: 'user' } } } : {}),
                            ...(tools.includes('microphone') ? { audio: { echoCancellation: true, noiseSuppression: true } } : {}),
                        };
                    try { retryMediaElement.setConstraints(constraints); } catch (error) {}
                }
            }

            function setMediaPermissionIssue(tool, reason = null) {
                if (reason) {
                    mediaPermissionIssues.set(tool, reason);
                } else {
                    mediaPermissionIssues.delete(tool);
                }
                updateMediaPermissionHelp();
                updateLivePermissionSummary();
            }

            function notificationRecoveryInstructions(reason = 'blocked') {
                const userAgent = navigator.userAgent || '';

                if (reason === 'unsupported') {
                    return 'Notifications are not available in this browser. On iPhone and iPad, install SpeakReady AI to the Home Screen first.';
                }

                if (isInstalledWebApp() && /Android/i.test(userAgent)) {
                    return 'Open Android Settings > Apps > SpeakReady > Notifications and turn notifications on. Also allow notifications for this site in the browser if shown.';
                }

                if (isInstalledWebApp() && /iPad|iPhone|iPod/i.test(userAgent)) {
                    return 'Open iPhone or iPad Settings > Notifications > SpeakReady and turn Allow Notifications on.';
                }

                if (/Android/i.test(userAgent)) {
                    return 'Open Site controls > Permissions > Notifications and choose Allow. Also verify the browser app can show notifications in Android Settings.';
                }

                return 'Open the lock or site-controls icon beside the address bar, set Notifications to Allow, then retry.';
            }

            function setNotificationPermissionHelp(visible, reason = 'blocked') {
                if (!notificationPermissionHelp) return;

                notificationPermissionHelp.hidden = !visible;
                if (visible && notificationPermissionInstructions) {
                    notificationPermissionInstructions.textContent = notificationRecoveryInstructions(reason);
                }
                updateLivePermissionSummary();
            }

            function updateLivePermissionSummary() {
                if (!livePermissionSummary) return;

                const selectedTools = toggles
                    .filter((input) => input.checked)
                    .map((input) => input.dataset.srSetupTool);

                if (!selectedTools.length) {
                    livePermissionSummary.textContent = 'No tools selected';
                    return;
                }

                const values = selectedTools.map((tool) => permissionResults[tool]);
                const hasIssue = activeMediaIssueTools().length > 0 ||
                    Boolean(notificationPermissionHelp && !notificationPermissionHelp.hidden) || values.some((value) =>
                    value && !['granted', 'off', 'prompt', 'unknown'].includes(value)
                );

                livePermissionSummary.textContent = hasIssue
                    ? 'Needs attention'
                    : (values.every((value) => value === 'granted') ? 'Ready on this device' : 'Check this device');
            }

            function syncToggle(input) {
                const tool = input.dataset.srSetupTool;
                const enabled = input.checked;
                const row = rowFor(tool);

                input.setAttribute('aria-checked', enabled ? 'true' : 'false');
                if (row) row.classList.toggle('is-off', !enabled);
                setToolStatus(tool, enabled ? 'On' : 'Off', enabled ? 'ready' : 'off');

                if (!enabled) {
                    permissionResults[tool] = 'off';
                    if (tool === 'camera' || tool === 'microphone') setMediaPermissionIssue(tool);
                    if (tool === 'notifications') setNotificationPermissionHelp(false);
                }
                updateLivePermissionSummary();
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

                if (errorName === 'NotReadableError' || errorName === 'AbortError') {
                    return toolLabel(tool) + ' busy';
                }

                return toolLabel(tool) + ' unavailable';
            }

            async function requestMediaTool(tool) {
                if (mediaPermissionSupport && typeof mediaPermissionSupport.probe === 'function') {
                    const result = await mediaPermissionSupport.probe(tool, {
                        navigatorRef: navigator,
                        documentRef: document,
                        isSecureContext: window.isSecureContext,
                    });

                    if (result.status === 'granted') {
                        setToolStatus(tool, 'Allowed', 'success');
                        setMediaPermissionIssue(tool);
                    } else {
                        const statusLabel = {
                            policy_blocked: 'Page policy blocked',
                            insecure_context: 'HTTPS required',
                            unsupported: 'Unsupported',
                        }[result.status] || permissionErrorMessage(tool, result.error);
                        setToolStatus(tool, statusLabel, 'error');
                        setMediaPermissionIssue(tool, result.reason);
                    }

                    return result.status;
                }

                if (!mediaFeatureAllowed(tool)) {
                    setToolStatus(tool, 'Page policy blocked', 'error');
                    setMediaPermissionIssue(tool, 'policy_blocked');
                    return 'policy_blocked';
                }

                if (window.isSecureContext === false) {
                    setToolStatus(tool, 'HTTPS required', 'error');
                    setMediaPermissionIssue(tool, 'insecure_context');
                    return 'insecure_context';
                }

                if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                    setToolStatus(tool, 'Unsupported', 'error');
                    setMediaPermissionIssue(tool, 'unsupported');
                    return 'unsupported';
                }

                const constraints = tool === 'camera'
                    ? { video: { facingMode: { ideal: 'user' } } }
                    : { audio: true };

                try {
                    const stream = await navigator.mediaDevices.getUserMedia(constraints);
                    stream.getTracks().forEach((track) => track.stop());
                    setToolStatus(tool, 'Allowed', 'success');
                    setMediaPermissionIssue(tool);
                    return 'granted';
                } catch (error) {
                    setToolStatus(tool, permissionErrorMessage(tool, error), 'error');
                    setMediaPermissionIssue(tool, mediaIssueReason(error));

                    return error && error.name ? error.name : 'error';
                }
            }

            async function requestMediaTools(tools) {
                const requestedTools = [...new Set(tools)].filter((tool) => tool === 'camera' || tool === 'microphone');
                const results = {};

                if (!requestedTools.length) return results;
                if (requestedTools.length === 1) {
                    results[requestedTools[0]] = await requestTool(requestedTools[0]);
                    return results;
                }

                requestedTools.forEach((tool) => setToolStatus(tool, 'Requesting', 'pending'));

                let combinedResult;
                if (mediaPermissionSupport && typeof mediaPermissionSupport.probeTools === 'function') {
                    combinedResult = await mediaPermissionSupport.probeTools(requestedTools, {
                        navigatorRef: navigator,
                        documentRef: document,
                        isSecureContext: window.isSecureContext,
                    });
                } else if (requestedTools.some((tool) => !mediaFeatureAllowed(tool))) {
                    combinedResult = {
                        status: 'policy_blocked',
                        reason: 'policy_blocked',
                        policyBlockedTools: requestedTools.filter((tool) => !mediaFeatureAllowed(tool)),
                    };
                } else if (window.isSecureContext === false) {
                    combinedResult = { status: 'insecure_context', reason: 'insecure_context' };
                } else if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                    combinedResult = { status: 'unsupported', reason: 'unsupported' };
                } else {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { ideal: 'user' } },
                            audio: { echoCancellation: true, noiseSuppression: true },
                        });
                        stream.getTracks().forEach((track) => track.stop());
                        combinedResult = { status: 'granted', reason: null };
                    } catch (error) {
                        combinedResult = {
                            status: error && error.name ? error.name : 'error',
                            reason: mediaIssueReason(error),
                            error,
                        };
                    }
                }

                if (combinedResult.status === 'granted') {
                    requestedTools.forEach((tool) => {
                        results[tool] = 'granted';
                        permissionResults[tool] = 'granted';
                        setToolStatus(tool, 'Allowed', 'success');
                        setMediaPermissionIssue(tool);
                    });
                    return results;
                }

                const policyBlockedTools = Array.isArray(combinedResult.policyBlockedTools)
                    ? combinedResult.policyBlockedTools
                    : [];
                if (combinedResult.reason === 'policy_blocked' && policyBlockedTools.length) {
                    for (const tool of requestedTools) {
                        if (policyBlockedTools.includes(tool)) {
                            results[tool] = 'policy_blocked';
                            permissionResults[tool] = 'policy_blocked';
                            setToolStatus(tool, 'Page policy blocked', 'error');
                            setMediaPermissionIssue(tool, 'policy_blocked');
                        } else {
                            results[tool] = await requestTool(tool);
                        }
                    }
                    return results;
                }

                if (combinedResult.reason === 'blocked') {
                    // Chromium exposes media permission state per device. Use
                    // that state after a combined denial so an allowed
                    // microphone is not mislabeled when only camera is denied.
                    for (const tool of requestedTools) {
                        const permissionState = await queryMediaPermission(tool);

                        if (permissionState === 'granted') {
                            results[tool] = await requestTool(tool);
                            continue;
                        }

                        results[tool] = permissionState === 'denied' ? 'denied' : combinedResult.status;
                        permissionResults[tool] = results[tool];
                        setToolStatus(tool, toolLabel(tool) + ' blocked', 'error');
                        setMediaPermissionIssue(tool, 'blocked');
                    }
                    return results;
                }

                if (combinedResult.reason === 'not_found' || combinedResult.reason === 'busy') {
                    // Permission was already handled by the combined request.
                    // Probe each device only to identify which one needs attention.
                    for (const tool of requestedTools) {
                        results[tool] = await requestTool(tool);
                    }
                    return results;
                }

                for (const tool of requestedTools) {
                    const statusLabel = {
                        policy_blocked: 'Page policy blocked',
                        insecure_context: 'HTTPS required',
                        unsupported: 'Unsupported',
                    }[combinedResult.status] || permissionErrorMessage(tool, combinedResult.error);

                    results[tool] = combinedResult.status;
                    permissionResults[tool] = combinedResult.status;
                    setToolStatus(tool, statusLabel, 'error');
                    setMediaPermissionIssue(tool, combinedResult.reason || 'unavailable');
                }

                return results;
            }

            async function requestNotifications() {
                if (window.SpeakReadyPush && typeof window.SpeakReadyPush.enable === 'function') {
                    try {
                        const result = await window.SpeakReadyPush.enable({ sendTest: true });

                        if (result.status === 'granted') {
                            setNotificationPermissionHelp(false);
                            if (result.test && result.test.success === false && result.test.skipped !== true) {
                                setToolStatus('notifications', 'Test failed', 'error');
                                return 'test_failed';
                            }

                            setToolStatus('notifications', 'Ready', 'success');
                            return 'granted';
                        }

                        if (result.status === 'unconfigured') {
                            setNotificationPermissionHelp(false);
                            setToolStatus('notifications', 'Server setup needed', 'error');
                            return 'unconfigured';
                        }

                        if (result.status === 'unsupported') {
                            setToolStatus('notifications', 'Unsupported', 'error');
                            setNotificationPermissionHelp(true, 'unsupported');
                            return 'unsupported';
                        }

                        setToolStatus('notifications', 'Blocked', 'error');
                        setNotificationPermissionHelp(true, 'blocked');
                        return result.status || 'denied';
                    } catch (error) {
                        setToolStatus('notifications', permissionErrorMessage('notifications', error), 'error');
                        setNotificationPermissionHelp(true, 'blocked');
                        return error && error.name ? error.name : 'error';
                    }
                }

                if (!('Notification' in window) || typeof Notification.requestPermission !== 'function') {
                    setToolStatus('notifications', 'Unsupported', 'error');
                    setNotificationPermissionHelp(true, 'unsupported');
                    return 'unsupported';
                }

                if (Notification.permission === 'granted') {
                    setToolStatus('notifications', 'Allowed', 'success');
                    setNotificationPermissionHelp(false);
                    return 'granted';
                }

                if (Notification.permission === 'denied') {
                    setToolStatus('notifications', 'Blocked', 'error');
                    setNotificationPermissionHelp(true, 'blocked');
                    return 'denied';
                }

                const permission = await Notification.requestPermission();
                setToolStatus('notifications', permission === 'granted' ? 'Allowed' : 'Blocked', permission === 'granted' ? 'success' : 'error');
                setNotificationPermissionHelp(permission !== 'granted', 'blocked');
                return permission;
            }

            async function requestTool(tool) {
                if (activeToolRequests.has(tool)) {
                    return activeToolRequests.get(tool);
                }

                const request = (async () => {
                    setToolStatus(tool, 'Requesting', 'pending');

                    const result = tool === 'notifications'
                        ? await requestNotifications()
                        : await requestMediaTool(tool);

                    permissionResults[tool] = result;
                    updateLivePermissionSummary();
                    return result;
                })();

                activeToolRequests.set(tool, request);

                try {
                    return await request;
                } finally {
                    activeToolRequests.delete(tool);
                }
            }

            async function queryMediaPermission(tool) {
                if (!navigator.permissions || typeof navigator.permissions.query !== 'function') {
                    return null;
                }

                try {
                    const permissionStatus = await navigator.permissions.query({ name: tool });

                    if (!permissionWatchers.has(tool)) {
                        const onPermissionChange = () => schedulePermissionRefresh(true);
                        if (typeof permissionStatus.addEventListener === 'function') {
                            permissionStatus.addEventListener('change', onPermissionChange);
                        } else {
                            permissionStatus.onchange = onPermissionChange;
                        }
                        permissionWatchers.set(tool, permissionStatus);
                    }

                    return permissionStatus.state || null;
                } catch (error) {
                    return null;
                }
            }

            async function refreshSelectedMediaPermissions(verifyGranted = false) {
                const selectedMediaToggles = toggles.filter((input) =>
                    input.checked && (input.dataset.srSetupTool === 'camera' || input.dataset.srSetupTool === 'microphone')
                );

                for (const input of selectedMediaToggles) {
                    const tool = input.dataset.srSetupTool;
                    const permissionState = await queryMediaPermission(tool);

                    if (permissionState === 'granted') {
                        if (verifyGranted) {
                            await requestTool(tool);
                        } else {
                            permissionResults[tool] = 'granted';
                            setToolStatus(tool, 'Allowed', 'success');
                            setMediaPermissionIssue(tool);
                        }
                    } else if (permissionState === 'denied') {
                        permissionResults[tool] = 'denied';
                        setToolStatus(tool, toolLabel(tool) + ' blocked', 'error');
                        setMediaPermissionIssue(tool, 'blocked');
                    } else if (permissionState === 'prompt') {
                        permissionResults[tool] = 'prompt';
                        setToolStatus(tool, 'Tap Allow', 'ready');
                        setMediaPermissionIssue(tool);
                    } else if (permissionState === null && verifyGranted) {
                        // Safari does not consistently expose camera/microphone
                        // through the Permissions API. A real stream check is
                        // the authoritative fallback after returning to the app.
                        await requestTool(tool);
                    } else if (permissionState === null) {
                        permissionResults[tool] = 'unknown';
                        setToolStatus(tool, 'Check needed', 'ready');
                    }
                }

                updateLivePermissionSummary();
            }

            function refreshNotificationPermission() {
                const input = form.querySelector('[data-sr-setup-tool="notifications"]');
                if (!input || !input.checked) {
                    if (input) permissionResults.notifications = 'off';
                    setNotificationPermissionHelp(false);
                    return;
                }

                if (!('Notification' in window)) {
                    permissionResults.notifications = 'unsupported';
                    setToolStatus('notifications', 'Unsupported', 'error');
                    setNotificationPermissionHelp(true, 'unsupported');
                    return;
                }

                permissionResults.notifications = Notification.permission;
                if (Notification.permission === 'granted') {
                    setToolStatus('notifications', 'Allowed', 'success');
                    setNotificationPermissionHelp(false);
                } else if (Notification.permission === 'denied') {
                    setToolStatus('notifications', 'Blocked', 'error');
                    setNotificationPermissionHelp(true, 'blocked');
                } else {
                    setToolStatus('notifications', 'Tap Allow', 'ready');
                    setNotificationPermissionHelp(false);
                }
            }

            function schedulePermissionRefresh(verifyGranted = false) {
                window.clearTimeout(permissionRefreshTimer);
                permissionRefreshTimer = window.setTimeout(() => {
                    if (document.visibilityState === 'hidden') return;
                    refreshNotificationPermission();
                    refreshSelectedMediaPermissions(verifyGranted).catch(() => {});
                }, 120);
            }

            async function requestEnabledToolOnToggle(input) {
                syncToggle(input);
                setStatus('');

                const tool = input.dataset.srSetupTool;
                if (!input.checked) return 'off';

                input.disabled = true;
                input.setAttribute('aria-busy', 'true');

                try {
                    const result = await requestTool(tool);

                    if (result === 'granted') {
                        setStatus(toolLabel(tool) + ' access is ready.', 'success');
                    } else if (tool === 'camera' || tool === 'microphone') {
                        setStatus(toolLabel(tool) + ' access is not ready. Follow the device-specific steps below, then retry.', 'warning');
                    } else {
                        setStatus(toolLabel(tool) + ' permission was not granted. Retry or switch it off to continue.', 'warning');
                    }

                    return result;
                } finally {
                    input.disabled = false;
                    input.setAttribute('aria-busy', 'false');
                }
            }

            async function persistMilestone(milestone, results) {
                if (!progressUrl) {
                    throw new Error('Onboarding progress endpoint is unavailable.');
                }

                const response = await fetch(progressUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        milestone,
                        ...(results ? { tools: results } : {}),
                    }),
                });

                if (!response.ok) {
                    throw new Error('Setup progress could not be saved.');
                }

                const payload = await response.json().catch(() => ({}));
                if (payload.onboarding) {
                    window.dispatchEvent(new CustomEvent('speakready:onboarding-updated', {
                        detail: payload.onboarding,
                    }));
                }

                return payload.onboarding || null;
            }

            async function saveSetup(results) {
                await persistMilestone('setup_tools_completed', results);

                completed = true;
                serverSnoozed = false;
                modalElement.dataset.srSetupCompleted = 'true';
                modalElement.dataset.srSetupSnoozed = 'false';
                safeSessionRemove(snoozeKey);
            }

            function snoozeSetup() {
                if (completed || serverSnoozed) return;

                serverSnoozed = true;
                modalElement.dataset.srSetupSnoozed = 'true';
                safeSessionSet(snoozeKey, '1');
                persistMilestone('setup_tools_snoozed').catch((error) => {
                    serverSnoozed = false;
                    modalElement.dataset.srSetupSnoozed = 'false';
                    console.warn('Setup reminder could not be snoozed:', error);
                });
            }

            function dismissModal() {
                if (window.bootstrap && modalElement) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                }
            }

            toggles.forEach((input) => {
                syncToggle(input);
                input.addEventListener('change', async () => {
                    await requestEnabledToolOnToggle(input);
                });
            });

            const supportsUserMediaElement = Boolean(
                'HTMLUserMediaElement' in window &&
                retryMediaElement &&
                typeof retryMediaElement.setConstraints === 'function'
            );

            async function retryMediaPermissionsFallback() {
                const tools = activeMediaIssueTools();
                if (!tools.length || tools.some((tool) => activeToolRequests.has(tool))) return;

                retryMediaButton.disabled = true;
                retryMediaButton.setAttribute('aria-busy', 'true');

                try {
                    tools.forEach((tool) => {
                        const input = form.querySelector('[data-sr-setup-tool="' + tool + '"]');
                        if (input) input.checked = true;
                    });
                    const results = await requestMediaTools(tools);
                    const ready = tools.every((tool) => results[tool] === 'granted');
                    setStatus(
                        ready
                            ? mediaToolList(tools) + ' access is ready on this device.'
                            : 'One or more media devices still need attention.',
                        ready ? 'success' : 'warning'
                    );
                } finally {
                    retryMediaButton.disabled = false;
                    retryMediaButton.setAttribute('aria-busy', 'false');
                }
            }

            if (supportsUserMediaElement) {
                retryMediaElement.addEventListener('stream', () => {
                    const stream = retryMediaElement.stream;
                    const tools = activeMediaIssueTools();
                    const hasAudio = Boolean(stream && stream.getAudioTracks().length);
                    const hasVideo = Boolean(stream && stream.getVideoTracks().length);

                    tools.forEach((tool) => {
                        const granted = tool === 'camera' ? hasVideo : hasAudio;
                        permissionResults[tool] = granted ? 'granted' : 'not_found';
                        setToolStatus(tool, granted ? 'Allowed' : toolLabel(tool) + ' not found', granted ? 'success' : 'error');
                        setMediaPermissionIssue(tool, granted ? null : 'not_found');
                    });

                    stream?.getTracks().forEach((track) => track.stop());
                    setStatus(tools.every((tool) => permissionResults[tool] === 'granted')
                        ? mediaToolList(tools) + ' access is ready on this device.'
                        : 'One or more media devices still need attention.', tools.every((tool) => permissionResults[tool] === 'granted') ? 'success' : 'warning');
                });

                retryMediaElement.addEventListener('error', () => {
                    const error = retryMediaElement.error;
                    const reason = mediaIssueReason(error);
                    activeMediaIssueTools().forEach((tool) => {
                        permissionResults[tool] = error?.name || reason;
                        setToolStatus(tool, permissionErrorMessage(tool, error), 'error');
                        setMediaPermissionIssue(tool, reason);
                    });
                    setStatus('Media access is still blocked. Follow the device-specific steps and retry.', 'warning');
                });

                retryMediaElement.addEventListener('cancel', () => {
                    setStatus('The permission prompt was closed. Retry when you are ready.', 'warning');
                });
            } else {
                retryMediaButton?.addEventListener('click', retryMediaPermissionsFallback);
            }

            form.querySelectorAll('[data-sr-setup-later]').forEach((button) => {
                button.addEventListener('click', () => {
                    snoozeSetup();
                });
            });

            modalElement?.addEventListener('hidden.bs.modal', () => {
                if (!completed && !submitActive) snoozeSetup();
                markSetupReady();
            });

            modalElement?.addEventListener('shown.bs.modal', () => {
                schedulePermissionRefresh(false);
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && isModalOpen(modalElement)) {
                    schedulePermissionRefresh(true);
                }
            });

            window.addEventListener('focus', () => {
                if (isModalOpen(modalElement)) schedulePermissionRefresh(true);
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (submitActive) return;

                const enabledTools = toggles
                    .filter((input) => input.checked)
                    .map((input) => input.dataset.srSetupTool);
                const results = {};

                submitActive = true;
                toggles.forEach((input) => {
                    input.disabled = true;
                    input.setAttribute('aria-busy', 'true');
                });
                if (allowButton) {
                    allowButton.disabled = true;
                    allowButton.setAttribute('aria-busy', 'true');
                    allowButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
                }

                setStatus(enabledTools.length ? 'Requesting selected tools...' : 'Saved with all tools off.', enabledTools.length ? 'info' : 'success');

                try {
                    // Ask for all selected media in one browser prompt while
                    // this submit click still supplies a direct user gesture.
                    // Notification registration runs afterward.
                    const mediaTools = enabledTools.filter((tool) => tool === 'camera' || tool === 'microphone');
                    Object.assign(results, await requestMediaTools(mediaTools));

                    if (enabledTools.includes('notifications')) {
                        results.notifications = await requestTool('notifications');
                    }

                    toggles
                        .filter((input) => !input.checked)
                        .forEach((input) => {
                            results[input.dataset.srSetupTool] = 'off';
                        });

                    const finalEnabledTools = toggles
                        .filter((input) => input.checked)
                        .map((input) => input.dataset.srSetupTool);
                    const blockedTools = finalEnabledTools.filter((tool) => results[tool] !== 'granted');

                    if (blockedTools.length) {
                        setStatus('Some selected tools still need browser permission. Allow them in site settings, switch them off, or choose Do later.', 'warning');
                        return;
                    }

                    await saveSetup(results);
                    setStatus('Setup saved. You can change browser permissions anytime from site settings.', 'success');
                    window.setTimeout(dismissModal, 650);
                } catch (error) {
                    setStatus('Setup could not finish. Check browser permissions and try again.', 'error');
                } finally {
                    submitActive = false;
                    toggles.forEach((input) => {
                        input.disabled = false;
                        input.setAttribute('aria-busy', 'false');
                    });
                    if (allowButton) {
                        allowButton.disabled = false;
                        allowButton.setAttribute('aria-busy', 'false');
                        allowButton.innerHTML = '<i class="fa-solid fa-check"></i> Allow';
                    }
                }
            });

            const shouldPromptForSetup = shouldAutoShow && !hasCompletedSetup() && !serverSnoozed && !safeSessionGet(snoozeKey) && window.bootstrap && modalElement;
            const registrationAlertModal = document.getElementById('srFlashModal');

            if (!shouldPromptForSetup) {
                markSetupReady();
                return;
            }

            setupState.pending = true;
            setupState.ready = false;

            function showSetupToolsModal() {
                if (hasCompletedSetup() || serverSnoozed || safeSessionGet(snoozeKey)) {
                    markSetupReady();
                    return;
                }

                if (isModalOpen(registrationAlertModal)) {
                    registrationAlertModal.addEventListener('hidden.bs.modal', () => {
                        window.setTimeout(showSetupToolsModal, 120);
                    }, { once: true });
                    return;
                }

                window.bootstrap.Modal.getOrCreateInstance(modalElement, {
                    backdrop: 'static',
                    keyboard: true,
                }).show();
            }

            window.setTimeout(showSetupToolsModal, 220);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardSetupToolsModal);
        } else {
            initDashboardSetupToolsModal();
        }
    })();
</script>
@endpush
