@once
<style data-sr-first-steps-modal-styles>
    .sr-first-steps-modal {
        --sr-first-bg: var(--sf, #ffffff);
        --sr-first-surface: var(--bg2, #f8fafc);
        --sr-first-line: var(--bd, #e2e8f0);
        --sr-first-text: var(--tx, #0f172a);
        --sr-first-muted: var(--tx2, #64748b);
        --sr-first-accent: var(--pur, #4f46e5);
        --sr-first-success: #16a34a;
    }

    body.sr-first-steps-active .modal-backdrop.show {
        opacity: 1;
        background-color: rgba(15, 23, 42, 0.68);
        -webkit-backdrop-filter: blur(12px) saturate(0.82);
        backdrop-filter: blur(12px) saturate(0.82);
    }

    .sr-first-steps-modal .modal-dialog {
        width: min(720px, calc(100vw - 24px));
        max-width: min(720px, calc(100vw - 24px));
        margin-inline: auto;
    }

    .sr-first-steps-modal .modal-content {
        max-height: calc(100dvh - 28px);
        overflow: hidden;
        border: 1px solid color-mix(in srgb, var(--sr-first-accent) 24%, var(--sr-first-line));
        border-radius: 22px;
        background: var(--sr-first-bg);
        color: var(--sr-first-text);
        box-shadow: 0 28px 72px rgba(15, 23, 42, 0.25);
    }

    .sr-first-steps-modal .modal-header {
        align-items: flex-start;
        gap: 14px;
        padding: 20px 22px 16px;
        border-bottom: 1px solid var(--sr-first-line);
        background: linear-gradient(135deg, color-mix(in srgb, var(--sr-first-accent) 11%, var(--sr-first-bg)), color-mix(in srgb, #06b6d4 7%, var(--sr-first-bg)));
    }

    .sr-first-steps-heading {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        min-width: 0;
    }

    .sr-first-steps-heading-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: color-mix(in srgb, var(--sr-first-accent) 14%, transparent);
        color: var(--sr-first-accent);
        font-size: 1rem;
    }

    .sr-first-steps-kicker {
        margin: 0 0 3px;
        color: var(--sr-first-accent);
        font-size: 0.68rem;
        font-weight: 900;
        letter-spacing: 0.09em;
        text-transform: uppercase;
    }

    .sr-first-steps-modal .modal-title {
        margin: 0;
        color: var(--sr-first-text);
        font-size: 1.2rem;
        line-height: 1.2;
        font-weight: 900;
    }

    .sr-first-steps-subtitle {
        margin: 5px 0 0;
        color: var(--sr-first-muted);
        font-size: 0.78rem;
        line-height: 1.45;
        font-weight: 650;
    }

    .sr-first-steps-modal .modal-body {
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 18px 22px;
    }

    .sr-first-steps-progress {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }

    .sr-first-steps-progress-track {
        height: 9px;
        overflow: hidden;
        border-radius: 999px;
        background: color-mix(in srgb, var(--sr-first-muted) 18%, transparent);
    }

    .sr-first-steps-progress-track span {
        display: block;
        width: var(--sr-first-progress, 0%);
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--sr-first-accent), #06b6d4, #22c55e);
        transition: width 0.25s ease;
    }

    .sr-first-steps-progress strong {
        color: var(--sr-first-text);
        font-size: 0.76rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .sr-first-steps-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-areas:
            "recovery recovery"
            "personalization personalization"
            "tools verification";
        gap: 11px;
    }

    .sr-first-steps-step[data-sr-onboarding-step="recovery_code_saved"] { grid-area: recovery; }
    .sr-first-steps-step[data-sr-onboarding-step="setup_tools_completed"] { grid-area: tools; }
    .sr-first-steps-step[data-sr-onboarding-step="email_verified"] { grid-area: verification; }

    .sr-first-steps-step {
        display: grid;
        grid-template-columns: 36px minmax(0, 1fr);
        align-content: start;
        gap: 10px;
        min-width: 0;
        padding: 13px;
        border: 1px solid var(--sr-first-line);
        border-radius: 15px;
        background: var(--sr-first-surface);
    }

    .sr-first-steps-step--personalization {
        grid-area: personalization;
        grid-column: 1 / -1;
    }

    .sr-first-steps-step.is-done {
        border-color: rgba(34, 197, 94, 0.3);
        background: color-mix(in srgb, #22c55e 7%, var(--sr-first-bg));
    }

    .sr-first-steps-step-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 12px;
        background: color-mix(in srgb, var(--sr-first-accent) 13%, transparent);
        color: var(--sr-first-accent);
    }

    .sr-first-steps-step.is-done .sr-first-steps-step-icon {
        background: rgba(34, 197, 94, 0.14);
        color: var(--sr-first-success);
    }

    .sr-first-steps-step-copy {
        min-width: 0;
    }

    .sr-first-steps-step-copy strong {
        display: block;
        color: var(--sr-first-text);
        font-size: 0.8rem;
        font-weight: 900;
    }

    .sr-first-steps-step-copy small {
        display: block;
        margin-top: 3px;
        color: var(--sr-first-muted);
        font-size: 0.69rem;
        line-height: 1.4;
        font-weight: 650;
    }

    .sr-first-steps-step-actions {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 1px;
    }

    .sr-first-steps-step-actions:empty,
    .sr-first-steps-step-actions[hidden] {
        display: none;
    }

    .sr-first-steps-step-actions form {
        margin: 0;
    }

    .sr-first-steps-status {
        color: var(--sr-first-success);
        font-size: 0.7rem;
        font-weight: 900;
    }

    .sr-first-steps-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 38px;
        padding: 7px 11px;
        border: 1px solid color-mix(in srgb, var(--sr-first-accent) 30%, transparent);
        border-radius: 10px;
        background: color-mix(in srgb, var(--sr-first-accent) 9%, var(--sr-first-bg));
        color: var(--sr-first-accent);
        font-size: 0.7rem;
        font-weight: 900;
        text-decoration: none;
    }

    .sr-first-steps-action:hover {
        background: color-mix(in srgb, var(--sr-first-accent) 16%, var(--sr-first-bg));
        color: var(--sr-first-accent);
    }

    .sr-first-steps-personalization {
        grid-column: 1 / -1;
        margin-top: 2px;
    }

    .sr-first-steps-personalization summary {
        cursor: pointer;
        color: var(--sr-first-accent);
        font-size: 0.72rem;
        font-weight: 900;
        text-align: right;
        list-style-position: inside;
    }

    .sr-first-steps-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
        margin-top: 11px;
    }

    .sr-first-steps-field-wide { grid-column: 1 / -1; }

    .sr-first-steps-field {
        min-width: 0;
    }

    .sr-first-steps-field label {
        display: block;
        margin-bottom: 4px;
        color: var(--sr-first-muted);
        font-size: 0.66rem;
        font-weight: 850;
    }

    .sr-first-steps-field :is(input, select) {
        width: 100%;
        min-width: 0;
        min-height: 42px;
        padding: 7px 9px;
        border: 1px solid var(--sr-first-line);
        border-radius: 9px;
        background: var(--sr-first-bg);
        color: var(--sr-first-text);
        font-size: 0.82rem;
    }

    .sr-first-steps-field select {
        text-overflow: ellipsis;
    }

    .sr-first-steps-step-actions[data-sr-personalization-actions] {
        flex-wrap: wrap;
        margin-top: 11px;
    }

    .sr-first-steps-action:disabled {
        cursor: wait;
        opacity: 0.66;
    }

    .sr-first-steps-form-status {
        display: none;
        margin: 9px 0 0;
        color: var(--sr-first-muted);
        font-size: 0.7rem;
        font-weight: 750;
    }

    .sr-first-steps-form-status.show { display: block; }
    .sr-first-steps-form-status[data-type="error"] { color: #dc2626; }
    .sr-first-steps-form-status[data-type="success"] { color: var(--sr-first-success); }

    .sr-first-steps-modal .modal-footer {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 10px;
        padding: 14px 22px;
        border-top: 1px solid var(--sr-first-line);
    }

    .sr-first-steps-footer-copy {
        margin: 0;
        color: var(--sr-first-muted);
        font-size: 0.72rem;
        line-height: 1.4;
        font-weight: 750;
    }

    .sr-first-steps-proceed {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 40px;
        padding: 8px 15px;
        border: 1px solid color-mix(in srgb, var(--sr-first-accent) 24%, transparent);
        border-radius: 11px;
        background: linear-gradient(135deg, var(--sr-first-accent), #06b6d4);
        color: #ffffff;
        font-size: 0.76rem;
        font-weight: 900;
    }

    .sr-first-steps-proceed:disabled {
        cursor: not-allowed;
        filter: grayscale(0.35);
        opacity: 0.48;
    }

    html:not(.lm) .sr-first-steps-modal,
    html[data-theme="dark"] .sr-first-steps-modal,
    body.dm .sr-first-steps-modal {
        --sr-first-bg: #0f172a;
        --sr-first-surface: rgba(30, 41, 59, 0.76);
        --sr-first-line: rgba(148, 163, 184, 0.22);
        --sr-first-text: #f8fafc;
        --sr-first-muted: #cbd5e1;
    }

    @media (max-width: 575.98px) {
        .sr-first-steps-modal .modal-dialog {
            width: min(360px, calc(100vw - 18px));
            max-width: min(360px, calc(100vw - 18px));
            margin-block: 9px;
        }

        .sr-first-steps-modal .modal-content {
            max-height: calc(100dvh - 18px);
            border-radius: 18px;
        }

        .sr-first-steps-modal .modal-header,
        .sr-first-steps-modal .modal-body,
        .sr-first-steps-modal .modal-footer {
            padding-inline: 15px;
        }

        .sr-first-steps-list,
        .sr-first-steps-form-grid {
            grid-template-columns: 1fr;
        }

        .sr-first-steps-list {
            grid-template-areas:
                "recovery"
                "personalization"
                "tools"
                "verification";
        }

        .sr-first-steps-field-wide { grid-column: auto; }

        .sr-first-steps-field :is(input, select),
        .sr-first-steps-step-actions[data-sr-personalization-actions] .sr-first-steps-action {
            min-height: 44px;
        }

        .sr-first-steps-field label {
            font-size: 0.75rem;
        }

        .sr-first-steps-field :is(input, select) {
            padding-inline: 11px;
            font-size: 1rem;
        }

        .sr-first-steps-step-actions[data-sr-personalization-actions] {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }

        .sr-first-steps-step-actions[data-sr-personalization-actions] .sr-first-steps-action {
            width: 100%;
            min-width: 0;
        }

        body.user-mobile-shell .sr-first-steps-modal .modal-footer,
        .sr-first-steps-modal .modal-footer {
            display: grid !important;
            grid-template-columns: 1fr;
            flex-wrap: nowrap !important;
            gap: 8px !important;
        }

        .sr-first-steps-footer-copy {
            text-align: center;
        }

        .sr-first-steps-proceed {
            width: 100%;
            min-width: 0;
            padding-inline: 8px;
            white-space: nowrap;
        }

        body.sr-first-steps-active .modal-backdrop.show {
            background-color: rgba(15, 23, 42, 0.64);
            -webkit-backdrop-filter: blur(8px) saturate(0.86);
            backdrop-filter: blur(8px) saturate(0.86);
        }
    }

    @media (max-width: 359.98px) {
        .sr-first-steps-step-actions[data-sr-personalization-actions] {
            grid-template-columns: 1fr;
        }
    }
</style>
@endonce
