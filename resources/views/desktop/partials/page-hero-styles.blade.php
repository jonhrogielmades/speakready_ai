<style>
    .sr-page-hero {
        min-height: 98px;
        margin-bottom: 14px;
        border: 1px solid rgba(96, 165, 250, 0.26);
        border-radius: 16px;
        background:
            radial-gradient(circle at 92% 35%, rgba(96, 165, 250, 0.2), transparent 25%),
            linear-gradient(110deg, rgba(59, 130, 246, 0.12), rgba(6, 182, 212, 0.045)),
            var(--sf);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        position: relative;
        isolation: isolate;
    }
    .sr-page-hero::after {
        content: "";
        position: absolute;
        z-index: -1;
        inset: 0 0 0 auto;
        width: min(34%, 320px);
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.055));
        pointer-events: none;
    }
    .lm .sr-page-hero {
        background:
            radial-gradient(circle at 92% 35%, rgba(147, 197, 253, 0.2), transparent 25%),
            linear-gradient(110deg, rgba(255, 255, 255, 0.99), rgba(246, 249, 255, 0.97));
        border-color: #dce8fb;
        box-shadow: 0 7px 22px rgba(59, 130, 246, 0.08);
    }
    .sr-page-hero-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 98px;
        padding: 14px clamp(126px, 14vw, 148px) 14px 16px;
    }
    .sr-page-hero-copy {
        min-width: 0;
        width: 100%;
    }
    .sr-page-hero-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.45rem;
        font-weight: 800;
        margin-bottom: 5px;
        letter-spacing: 0;
        text-transform: uppercase;
        line-height: 1.15;
    }
    .sr-page-hero-title svg {
        width: 23px;
        height: 23px;
        flex: 0 0 auto;
        color: #3b82f6;
    }
    .sr-page-hero-subtitle {
        max-width: 680px;
        font-size: 0.88rem;
        color: var(--tx3);
        margin: 0;
        line-height: 1.45;
    }
    .sr-page-hero-art {
        position: absolute;
        z-index: 0;
        right: 8px;
        bottom: -2px;
        width: clamp(122px, 13vw, 142px);
        height: auto;
        filter: drop-shadow(0 16px 24px rgba(37, 99, 235, 0.18));
        pointer-events: none;
        user-select: none;
        transform-origin: 50% 78%;
        animation: srHeroArtFloat 4.8s ease-in-out infinite;
    }
    .sr-page-hero-art :is(circle, rect, path, polygon, ellipse):nth-child(odd) {
        transform-origin: center;
        animation: srHeroArtPulse 3.4s ease-in-out infinite;
    }
    @keyframes srHeroArtFloat {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg) scale(1); }
        35% { transform: translate3d(0, -7px, 0) rotate(1.5deg) scale(1.015); }
        70% { transform: translate3d(-3px, -2px, 0) rotate(-1deg) scale(1.005); }
    }
    @keyframes srHeroArtPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.78; }
    }
    .sr-page-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    @media (max-width: 767px) {
        .sr-page-hero {
            min-height: 112px;
            margin-bottom: 12px;
        }
        .sr-page-hero-inner {
            justify-content: flex-start;
            min-height: 112px;
            padding: 14px 112px 14px 14px;
        }
        .sr-page-hero-title {
            justify-content: flex-start;
            gap: 7px;
            font-size: 1.1rem !important;
            margin-bottom: 4px;
            letter-spacing: 0;
        }
        .sr-page-hero-title svg {
            width: 20px;
            height: 20px;
        }
        .sr-page-hero-subtitle {
            max-width: 100%;
            font-size: 0.7rem;
            line-height: 1.4;
        }
        .sr-page-hero-art {
            right: -2px;
            bottom: -1px;
            width: 122px;
        }
        .sr-page-actions {
            justify-content: stretch;
            margin-bottom: 14px;
        }
        .sr-page-actions > * {
            width: 100%;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .sr-page-hero-art,
        .sr-page-hero-art :is(circle, rect, path, polygon, ellipse) {
            animation: none !important;
        }
    }

    @media (min-width: 768px) {
        body.user-desktop-shell:not(.admin-shell) .sr-page-hero {
            min-height: 92px;
        }

        body.user-desktop-shell:not(.admin-shell) .sr-page-hero-inner {
            min-height: 92px;
            padding-right: clamp(156px, 14vw, 178px);
        }

        body.user-desktop-shell:not(.admin-shell) .sr-page-hero-art {
            top: 50%;
            right: 14px;
            bottom: auto;
            width: clamp(116px, 11vw, 132px);
            max-height: calc(100% - 14px);
            transform: translate3d(0, -50%, 0);
            transform-origin: 50% 50%;
            animation: srDesktopHeroArtFloat 4.8s ease-in-out infinite;
        }

        @keyframes srDesktopHeroArtFloat {
            0%, 100% { transform: translate3d(0, -50%, 0) rotate(0deg) scale(1); }
            35% { transform: translate3d(0, calc(-50% - 5px), 0) rotate(1deg) scale(1.01); }
            70% { transform: translate3d(-2px, calc(-50% - 1px), 0) rotate(-0.75deg) scale(1.005); }
        }
    }

    @media (min-width: 992px) {
        body.user-desktop-shell:not(.admin-shell) #dashboard .db-content .sr-page-hero {
            background: linear-gradient(105deg, rgba(29, 78, 216, 0.95) 0%, rgba(15, 95, 232, 0.94) 50%, rgba(31, 182, 213, 0.92) 100%) !important;
        }

        html:not(.lm) body.user-desktop-shell:not(.admin-shell) #dashboard .db-content .sr-page-hero,
        html[data-theme="dark"] body.user-desktop-shell:not(.admin-shell) #dashboard .db-content .sr-page-hero,
        body.dm.user-desktop-shell:not(.admin-shell) #dashboard .db-content .sr-page-hero {
            background: linear-gradient(105deg, rgba(30, 64, 175, 0.96) 0%, rgba(15, 82, 217, 0.95) 52%, rgba(18, 158, 192, 0.93) 100%) !important;
        }

        body.user-desktop-shell:not(.admin-shell) #dashboard .db-content .sr-page-hero :is(h1, h2, h3, h4, .sr-page-hero-title, .text-gradient-primary) {
            background: none !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
        }

        body.user-desktop-shell:not(.admin-shell) #dashboard .db-content .sr-page-hero :is(p, .sr-page-hero-subtitle) {
            color: rgba(248, 251, 255, 0.92) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.92) !important;
        }

        body.user-desktop-shell:not(.admin-shell) #dashboard .db-content .sr-page-hero :is(
            .reports-hero-icon,
            .account-hero-icon,
            .modules-page-hero-icon,
            .modules-hero-icon,
            .vr-hero-icon,
            .mission-hero-icon,
            .learning-hero-icon,
            .coach-hero-icon,
            .skill-tree-hero-icon
        ) {
            background: rgba(255, 255, 255, 0.94) !important;
            border-color: rgba(255, 255, 255, 0.62) !important;
            color: #1d4ed8 !important;
            -webkit-text-fill-color: #1d4ed8 !important;
        }

        body.user-desktop-shell:not(.admin-shell) #dashboard .db-content .sr-page-hero :is(
            .reports-hero-icon,
            .account-hero-icon,
            .modules-page-hero-icon,
            .modules-hero-icon,
            .vr-hero-icon,
            .mission-hero-icon,
            .learning-hero-icon,
            .coach-hero-icon,
            .skill-tree-hero-icon
        ) :is(i, svg, svg *),
        body.user-desktop-shell:not(.admin-shell) #dashboard .db-content .sr-page-hero .sr-page-hero-title :is(svg, svg *) {
            color: currentColor !important;
            -webkit-text-fill-color: currentColor !important;
            stroke: currentColor !important;
        }
    }
</style>
