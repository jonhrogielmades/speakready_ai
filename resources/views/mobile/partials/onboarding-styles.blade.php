<style>
    .driver-popover.sr-driver-popover {
        max-width: min(360px, calc(100vw - 28px));
        max-height: min(78vh, calc(100vh - 112px));
        overflow: auto;
        overscroll-behavior: contain;
        border-radius: 14px;
        padding: 16px;
        box-shadow: 0 18px 48px rgba(0, 0, 0, 0.28);
        font-family: "Poppins", sans-serif;
        box-sizing: border-box;
        overflow-wrap: anywhere;
    }

    .sr-native-tour-overlay,
    .sr-native-tour-stage,
    .sr-native-tour-popover {
        position: fixed;
        box-sizing: border-box;
    }

    .sr-native-tour-overlay {
        inset: 0;
        z-index: 12040;
        background: transparent;
        pointer-events: none;
    }

    .sr-native-tour-stage {
        z-index: 12050;
        background: transparent;
        border: 2px solid rgba(96, 165, 250, 0.92);
        box-shadow:
            0 0 0 9999px rgba(2, 6, 23, 0.62),
            0 18px 48px rgba(15, 23, 42, 0.28),
            0 0 0 6px rgba(96, 165, 250, 0.18);
        pointer-events: none;
        transition: top 0.16s ease, left 0.16s ease, width 0.16s ease, height 0.16s ease;
    }

    .sr-tour-highlighted {
        filter: none !important;
    }

    .sr-native-tour-popover {
        z-index: 12070;
    }

    .sr-native-tour-popover .sr-native-tour-close {
        position: absolute;
        top: 10px;
        right: 10px;
    }

    .sr-native-tour-popover .driver-popover-description:empty {
        display: none;
    }

    .sr-native-tour-popover .driver-popover-prev-btn:disabled {
        opacity: 0.48;
        cursor: not-allowed;
    }

    body.sr-native-tour-active {
        overflow-x: hidden;
    }

    .driver-popover.sr-driver-popover.driverjs-theme-dark {
        background: var(--bg3, #111827);
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.12));
        color: var(--tx, #f8fafc);
    }

    .driver-popover.sr-driver-popover.driverjs-theme-light {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.12);
        color: #0f172a;
        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.16);
    }

    .driver-popover.sr-driver-popover .driver-popover-title {
        color: inherit;
        font-size: 0.98rem;
        line-height: 1.25;
        font-weight: 800;
        letter-spacing: 0;
        margin: 0;
        padding-right: 34px;
    }

    .driver-popover.sr-driver-popover .driver-popover-description {
        color: var(--tx2, #cbd5e1);
        font-size: 0.84rem;
        line-height: 1.5;
        margin-top: 7px;
        margin-bottom: 0;
    }

    .driver-popover.sr-driver-popover.driverjs-theme-light .driver-popover-description {
        color: #475569;
    }

    .driver-popover.sr-driver-popover .driver-popover-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-top: 14px;
    }

    .driver-popover.sr-driver-popover .driver-popover-progress-text {
        color: var(--tx3, #94a3b8);
        font-size: 0.74rem;
        font-weight: 700;
    }

    .driver-popover.sr-driver-popover.driverjs-theme-light .driver-popover-progress-text {
        color: #64748b;
    }

    .driver-popover.sr-driver-popover .driver-popover-navigation-btns {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }

    .driver-popover.sr-driver-popover .driver-popover-footer button,
    .driver-popover.sr-driver-popover .driver-popover-close-btn {
        min-height: 34px;
        border-radius: 10px;
        border: 1px solid var(--bd, rgba(255, 255, 255, 0.14));
        background: var(--sf, rgba(255, 255, 255, 0.06));
        color: var(--tx, #f8fafc);
        font-family: "Poppins", sans-serif;
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1;
        padding: 8px 12px;
        text-shadow: none;
    }

    .driver-popover.sr-driver-popover.driverjs-theme-light .driver-popover-footer button,
    .driver-popover.sr-driver-popover.driverjs-theme-light .driver-popover-close-btn {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #0f172a;
    }

    .driver-popover.sr-driver-popover .driver-popover-next-btn {
        background: linear-gradient(135deg, #2563eb, #0ea5e9) !important;
        border-color: transparent !important;
        color: #ffffff !important;
    }

    .driver-popover.sr-driver-popover .driver-popover-close-btn {
        width: 30px;
        height: 30px;
        min-height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .driver-popover.sr-driver-popover.driverjs-theme-dark .driver-popover-arrow {
        border-color: var(--bg3, #111827);
    }

    .driver-popover.sr-driver-popover.driverjs-theme-light .driver-popover-arrow {
        border-color: #ffffff;
    }

    .driver-popover.sr-driver-popover .driver-popover-arrow-side-left.driver-popover-arrow {
        border-top-color: transparent !important;
        border-right-color: transparent !important;
        border-bottom-color: transparent !important;
    }

    .driver-popover.sr-driver-popover .driver-popover-arrow-side-right.driver-popover-arrow {
        border-top-color: transparent !important;
        border-bottom-color: transparent !important;
        border-left-color: transparent !important;
    }

    .driver-popover.sr-driver-popover .driver-popover-arrow-side-top.driver-popover-arrow {
        border-right-color: transparent !important;
        border-bottom-color: transparent !important;
        border-left-color: transparent !important;
    }

    .driver-popover.sr-driver-popover .driver-popover-arrow-side-bottom.driver-popover-arrow {
        border-top-color: transparent !important;
        border-right-color: transparent !important;
        border-left-color: transparent !important;
    }

    @media (max-width: 575px) {
        .driver-popover.sr-driver-popover {
            width: calc(100vw - 28px);
            max-width: calc(100vw - 28px);
            padding: 14px;
            border-radius: 12px;
        }

        .driver-popover.sr-driver-popover .driver-popover-footer {
            flex-wrap: wrap;
        }

        .driver-popover.sr-driver-popover .driver-popover-navigation-btns {
            width: 100%;
            justify-content: flex-end;
        }

        .driver-popover.sr-driver-popover .driver-popover-footer button {
            flex: 1 1 0;
        }
    }
</style>
