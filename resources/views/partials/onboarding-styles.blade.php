<style>
    .driver-popover.sr-driver-popover {
        max-width: min(360px, calc(100vw - 28px));
        max-height: min(78vh, calc(100vh - 112px));
        overflow: auto;
        border-radius: 14px;
        padding: 16px;
        box-shadow: 0 18px 48px rgba(0, 0, 0, 0.28);
        font-family: "Poppins", sans-serif;
        box-sizing: border-box;
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
    }

    .driver-popover.sr-driver-popover .driver-popover-description {
        color: var(--tx2, #cbd5e1);
        font-size: 0.84rem;
        line-height: 1.5;
        margin-top: 7px;
    }

    .driver-popover.sr-driver-popover.driverjs-theme-light .driver-popover-description {
        color: #475569;
    }

    .driver-popover.sr-driver-popover .driver-popover-footer {
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
            padding: 14px;
        }

        .driver-popover.sr-driver-popover .driver-popover-footer {
            flex-wrap: wrap;
        }

        .driver-popover.sr-driver-popover .driver-popover-navigation-btns {
            width: 100%;
            justify-content: flex-end;
        }
    }
</style>
