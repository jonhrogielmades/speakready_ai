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
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero,
    html body #mob-content .practice-page-hero.practice-page-hero,
    .practice-page-hero.practice-page-hero {
        min-height: 80px !important;
        border-color: rgba(147, 197, 253, 0.48) !important;
        border-radius: 8px !important;
        background:
            radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
            radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
            linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
        box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
    }
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero::before,
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero::after,
    html body #mob-content .practice-page-hero.practice-page-hero::before,
    html body #mob-content .practice-page-hero.practice-page-hero::after,
    .practice-page-hero.practice-page-hero::before,
    .practice-page-hero.practice-page-hero::after {
        content: none !important;
        display: none !important;
    }
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero .sr-page-hero-inner,
    html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-inner,
    .practice-page-hero.practice-page-hero .sr-page-hero-inner {
        min-height: 80px !important;
        justify-content: flex-start !important;
        padding: 10px 14px !important;
    }
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero .sr-page-hero-copy,
    html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-copy,
    .practice-page-hero.practice-page-hero .sr-page-hero-copy {
        display: grid !important;
        grid-template-columns: 48px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 14px !important;
        width: 100% !important;
        min-width: 0 !important;
    }
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero .sr-page-hero-title,
    html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-title,
    .practice-page-hero.practice-page-hero .sr-page-hero-title {
        display: block !important;
        margin: 0 0 3px !important;
        background: none !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        font-size: 1.42rem !important;
        line-height: 1.08 !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
        text-transform: none !important;
        text-shadow: none !important;
        white-space: normal !important;
    }
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero .sr-page-hero-subtitle,
    html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-subtitle,
    .practice-page-hero.practice-page-hero .sr-page-hero-subtitle {
        max-width: 760px !important;
        margin: 0 !important;
        color: rgba(248, 251, 255, 0.92) !important;
        -webkit-text-fill-color: rgba(248, 251, 255, 0.92) !important;
        font-size: 0.88rem !important;
        line-height: 1.28 !important;
        font-weight: 700 !important;
    }
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero .practice-hero-icon,
    html body #mob-content .practice-page-hero.practice-page-hero .practice-hero-icon,
    .practice-page-hero.practice-page-hero .practice-hero-icon {
        box-sizing: border-box !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 48px !important;
        height: 48px !important;
        min-width: 48px !important;
        border: 1px solid rgba(255, 255, 255, 0.62) !important;
        border-radius: 8px !important;
        background: rgba(255, 255, 255, 0.94) !important;
        color: #1d4ed8 !important;
        -webkit-text-fill-color: #1d4ed8 !important;
        font-size: 1.15rem !important;
        box-shadow: none !important;
    }
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero .practice-hero-icon :is(i, svg, svg *),
    html body #mob-content .practice-page-hero.practice-page-hero .practice-hero-icon :is(i, svg, svg *),
    .practice-page-hero.practice-page-hero .practice-hero-icon :is(i, svg, svg *) {
        color: currentColor !important;
        -webkit-text-fill-color: currentColor !important;
        stroke: currentColor !important;
    }
    html body.user-desktop-shell #dashboard .db-content .practice-page-hero.practice-page-hero .sr-page-hero-art,
    html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-art,
    .practice-page-hero.practice-page-hero .sr-page-hero-art {
        display: none !important;
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
        html body #mob-content .practice-page-hero.practice-page-hero,
        .practice-page-hero.practice-page-hero {
            min-height: 76px !important;
            margin-bottom: 12px !important;
        }
        html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-inner,
        .practice-page-hero.practice-page-hero .sr-page-hero-inner {
            min-height: 76px !important;
            padding: 9px 10px !important;
        }
        html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-copy,
        .practice-page-hero.practice-page-hero .sr-page-hero-copy {
            grid-template-columns: 46px minmax(0, 1fr) !important;
            gap: 12px !important;
        }
        html body #mob-content .practice-page-hero.practice-page-hero .practice-hero-icon,
        .practice-page-hero.practice-page-hero .practice-hero-icon {
            width: 46px !important;
            height: 46px !important;
            min-width: 46px !important;
            font-size: 1.05rem !important;
        }
        html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-title,
        .practice-page-hero.practice-page-hero .sr-page-hero-title {
            font-size: 1.16rem !important;
            line-height: 1.08 !important;
        }
        html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-subtitle,
        .practice-page-hero.practice-page-hero .sr-page-hero-subtitle {
            font-size: 0.74rem !important;
            line-height: 1.25 !important;
        }
    }

    @media (max-width: 390px) {
        html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-copy,
        .practice-page-hero.practice-page-hero .sr-page-hero-copy {
            grid-template-columns: 42px minmax(0, 1fr) !important;
            gap: 10px !important;
        }
        html body #mob-content .practice-page-hero.practice-page-hero .practice-hero-icon,
        .practice-page-hero.practice-page-hero .practice-hero-icon {
            width: 42px !important;
            height: 42px !important;
            min-width: 42px !important;
        }
        html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-title,
        .practice-page-hero.practice-page-hero .sr-page-hero-title {
            font-size: 1.02rem !important;
        }
        html body #mob-content .practice-page-hero.practice-page-hero .sr-page-hero-subtitle,
        .practice-page-hero.practice-page-hero .sr-page-hero-subtitle {
            font-size: 0.67rem !important;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .sr-page-hero-art,
        .sr-page-hero-art :is(circle, rect, path, polygon, ellipse) {
            animation: none !important;
        }
    }
</style>
