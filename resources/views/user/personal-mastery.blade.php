@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Personal Mastery')
@section('content')
<style>
    #personal-mastery-page {
        --mastery-blue: #1677f2;
        --mastery-navy: #16233d;
        --mastery-muted: #53617d;
        --mastery-line: rgba(102, 147, 219, 0.22);
        --mastery-hero-title: #1d4ed8;
        --mastery-hero-text: #334155;
        --mastery-icon-bg: rgba(239, 246, 255, 0.92);
        --mastery-icon-border: rgba(147, 197, 253, 0.42);
        max-width: none;
        margin: 0;
    }
    :root:not(.lm) #personal-mastery-page,
    .dm #personal-mastery-page {
        --mastery-muted: #cbd5e1;
        --mastery-line: rgba(147, 197, 253, 0.28);
        --mastery-hero-title: #93c5fd;
        --mastery-hero-text: #e2e8f0;
        --mastery-icon-bg: rgba(59, 130, 246, 0.2);
        --mastery-icon-border: rgba(147, 197, 253, 0.32);
    }
    #personal-mastery-page .mastery-hero-card {
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        align-items: center;
        overflow: hidden;
        min-height: 78px;
        padding: 8px 76px 8px 12px;
        border: 1px solid rgba(191, 219, 254, 0.86);
        border-radius: 16px;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%);
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
    }
    :root:not(.lm) #personal-mastery-page .mastery-hero-card,
    .dm #personal-mastery-page .mastery-hero-card {
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%);
        border-color: rgba(147, 197, 253, 0.28);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
    }
    #personal-mastery-page .mastery-hero-card::after {
        content: "";
        position: absolute;
        inset: auto -12% -52px 52%;
        height: 112px;
        border-top: 8px solid rgba(37, 99, 235, 0.22);
        border-radius: 50%;
        transform: rotate(-5deg);
    }
    #personal-mastery-page .mastery-copy {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: 36px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
    }
    #personal-mastery-page .mastery-badge,
    #personal-mastery-page .mastery-stat-icon,
    #personal-mastery-page .mastery-info-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }
    #personal-mastery-page .mastery-badge {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        color: var(--mastery-hero-title);
        background: var(--mastery-icon-bg);
        border: 1px solid var(--mastery-icon-border);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.86);
    }
    #personal-mastery-page .mastery-title {
        margin: 0 0 4px;
        color: var(--mastery-hero-title);
        font-size: 1.02rem;
        line-height: 1.08;
        font-weight: 950;
        letter-spacing: 0;
        text-transform: uppercase;
    }
    #personal-mastery-page .mastery-title span {
        display: inline;
        color: inherit;
    }
    #personal-mastery-page .mastery-subtitle {
        max-width: 14rem;
        margin: 0;
        color: var(--mastery-hero-text);
        font-size: 0.66rem;
        line-height: 1.32;
        font-weight: 500;
    }
    #personal-mastery-page .mastery-visual {
        position: absolute;
        z-index: 1;
        right: 8px;
        bottom: 4px;
        width: 94px;
    }
    #personal-mastery-page .mastery-visual svg {
        display: block;
        width: 100%;
        height: auto;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16));
        animation: masteryHeroFloat 4.8s ease-in-out infinite;
        transform-origin: 50% 78%;
    }
    #personal-mastery-page .mastery-visual svg :is(circle, rect, path):nth-child(odd) {
        transform-origin: center;
        animation: masteryHeroPulse 3.4s ease-in-out infinite;
    }
    @keyframes masteryHeroFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-4px) rotate(-1deg); }
    }
    @keyframes masteryHeroPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .78; }
    }
    @media (prefers-reduced-motion: reduce) {
        #personal-mastery-page .mastery-visual svg,
        #personal-mastery-page .mastery-visual svg :is(circle, rect, path) {
            animation: none !important;
        }
    }
    #personal-mastery-page .mastery-stat-card {
        position: relative;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 9px;
        overflow: hidden;
        background: var(--sf);
        border: 1px solid var(--mastery-line);
        border-radius: 14px;
        padding: 12px;
        height: 100%;
        color: var(--mastery-stat-color, var(--mastery-blue));
        box-shadow: 0 12px 28px rgba(38, 83, 156, 0.08);
    }
    :root:not(.lm) #personal-mastery-page .mastery-stat-card,
    :root:not(.lm) #personal-mastery-page .mastery-info-card,
    .dm #personal-mastery-page .mastery-stat-card,
    .dm #personal-mastery-page .mastery-info-card {
        background: color-mix(in srgb, var(--bg3) 78%, transparent);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
    }
    #personal-mastery-page .mastery-stat-watermark {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--mastery-stat-color, currentColor);
        font-size: 2.5rem;
        opacity: 0.1;
        pointer-events: none;
    }
    #personal-mastery-page .mastery-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 12px;
    }
    #personal-mastery-page .mastery-stat-icon {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        color: var(--mastery-stat-color, var(--mastery-blue));
        background: color-mix(in srgb, var(--mastery-stat-color, var(--mastery-blue)) 15%, transparent);
        font-size: 0.9rem;
    }
    #personal-mastery-page .mastery-stat-value {
        color: var(--tx);
        font-size: 1.34rem;
        line-height: 1;
        margin-bottom: 5px;
    }
    #personal-mastery-page .mastery-stat-label {
        color: var(--tx2);
        font-size: 0.72rem;
        font-weight: 600;
        line-height: 1.2;
    }
    #personal-mastery-page .mastery-info-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        margin-top: 12px;
        padding: 14px;
        background: var(--sf);
        border: 1px solid var(--mastery-line);
        border-radius: 14px;
        box-shadow: 0 12px 28px rgba(38, 83, 156, 0.08);
    }
    #personal-mastery-page .mastery-info-heading {
        display: flex;
        gap: 9px;
        align-items: center;
        margin-bottom: 9px;
    }
    #personal-mastery-page .mastery-info-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        color: var(--mastery-blue);
        background: rgba(59, 130, 246, 0.13);
        font-size: 0.88rem;
    }
    #personal-mastery-page .mastery-info-card h5 {
        margin: 0;
        color: var(--tx);
        font-size: 0.95rem;
        font-weight: 900;
    }
    #personal-mastery-page .mastery-info-card p {
        margin: 0 0 12px;
        color: var(--tx2);
        max-width: 760px;
        font-size: 0.78rem;
        line-height: 1.42;
        font-weight: 500;
    }
    #personal-mastery-page .mastery-action-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-top: 12px;
    }
    #personal-mastery-page .mastery-action-row .mastery-progress-btn {
        width: auto;
        max-width: 100%;
    }
    #personal-mastery-page .mastery-progress-btn-mobile {
        display: none !important;
    }
    #personal-mastery-page .mastery-progress-btn {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        width: 100%;
        min-height: 40px;
        box-sizing: border-box;
        padding: 0 13px;
        border: 0;
        border-radius: 10px;
        color: #fff;
        background: linear-gradient(100deg, #2457ff 0%, #00a4f4 100%);
        font-size: 0.8rem;
        font-weight: 800;
        box-shadow: 0 12px 20px rgba(0, 118, 231, 0.2);
        overflow: hidden;
        text-decoration: none;
        white-space: nowrap;
    }
    #personal-mastery-page .mastery-progress-btn span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #personal-mastery-page .mastery-progress-btn i {
        flex: 0 0 auto;
    }
    #personal-mastery-page .mastery-progress-btn .fa-chevron-right {
        margin-left: auto;
    }
    #personal-mastery-page .mastery-info-art {
        width: 124px;
        color: #8ab5ff;
        opacity: 0.55;
    }
    @media (max-width: 767.98px) {
        #personal-mastery-page .mastery-hero-card {
            grid-template-columns: minmax(0, 1fr);
            min-height: 74px;
            padding: 8px 64px 8px 12px;
            border-radius: 16px;
        }
        #personal-mastery-page .mastery-copy {
            grid-template-columns: 32px minmax(0, 1fr);
            gap: 9px;
        }
        #personal-mastery-page .mastery-badge {
            width: 32px;
            height: 32px;
            border-radius: 11px;
            font-size: 0.8rem;
        }
        #personal-mastery-page .mastery-title {
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        #personal-mastery-page .mastery-subtitle {
            max-width: 12rem;
            font-size: 0.64rem;
            line-height: 1.28;
        }
        #personal-mastery-page .mastery-visual {
            right: -4px;
            bottom: 5px;
            width: 84px;
        }
        #personal-mastery-page .mastery-info-card {
            grid-template-columns: minmax(0, 1fr);
        }
        #personal-mastery-page .mastery-action-row {
            display: none !important;
        }
        #personal-mastery-page .mastery-progress-btn-mobile {
            display: inline-flex !important;
            width: 100% !important;
        }
        #personal-mastery-page .mastery-info-art {
            display: none;
        }
    }
    @media (max-width: 575.98px) {
        #personal-mastery-page .mastery-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        #personal-mastery-page .mastery-stat-card {
            grid-template-columns: 30px minmax(0, 1fr);
            align-content: start;
            gap: 8px;
            min-height: 78px;
            padding: 10px;
            border-radius: 13px;
        }
        #personal-mastery-page .mastery-stat-watermark {
            right: 8px;
            font-size: 2rem;
        }
        #personal-mastery-page .mastery-stat-icon {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            font-size: 0.82rem;
        }
        #personal-mastery-page .mastery-stat-value {
            font-size: 1.12rem;
            margin-bottom: 3px;
        }
        #personal-mastery-page .mastery-stat-label {
            font-size: 0.62rem;
            line-height: 1.25;
        }
        #personal-mastery-page .mastery-info-card p {
            font-size: 0.7rem;
        }
    }
    @media (max-width: 360px) {
        #personal-mastery-page .mastery-stats-grid {
            grid-template-columns: minmax(0, 1fr);
        }
        #personal-mastery-page .mastery-stat-card {
            grid-template-columns: auto minmax(0, 1fr);
            min-height: 0;
        }
    }

    @media (max-width: 991px) {
        #personal-mastery-page {
            --mastery-saas-radius: 12px;
            --mastery-saas-gap: 8px;
            --mastery-saas-border: rgba(37, 99, 235, 0.14);
            --mastery-saas-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            --mastery-saas-card: rgba(248, 250, 252, 0.78);
            --mastery-saas-muted: #475569;
            max-width: 100% !important;
            display: flex;
            flex-direction: column;
            gap: var(--mastery-saas-gap);
            padding-inline: 0 !important;
            padding-bottom: 14px !important;
        }
        html[data-theme="dark"] #personal-mastery-page,
        :root:not(.lm) #personal-mastery-page,
        .dm #personal-mastery-page {
            --mastery-saas-border: rgba(147, 197, 253, 0.18);
            --mastery-saas-shadow: 0 12px 26px rgba(0, 0, 0, 0.26);
            --mastery-saas-card: rgba(255, 255, 255, 0.045);
            --mastery-saas-muted: #cbd5e1;
        }
        #personal-mastery-page .mastery-hero-card {
            min-height: 86px !important;
            padding: 10px 74px 11px 12px !important;
            border-radius: var(--mastery-saas-radius) !important;
            border-color: var(--mastery-saas-border) !important;
            box-shadow: var(--mastery-saas-shadow) !important;
            margin: 0 !important;
        }
        #personal-mastery-page .mastery-hero-card::after {
            height: 96px;
            border-top-width: 6px;
            opacity: 0.82;
        }
        #personal-mastery-page .mastery-copy {
            grid-template-columns: 32px minmax(0, 1fr) !important;
            gap: 9px !important;
        }
        #personal-mastery-page .mastery-badge {
            width: 32px !important;
            height: 32px !important;
            border-radius: 10px !important;
            font-size: 0.78rem !important;
        }
        #personal-mastery-page .mastery-title {
            font-size: 0.86rem !important;
            line-height: 1.12 !important;
            margin-bottom: 4px !important;
            white-space: normal !important;
        }
        #personal-mastery-page .mastery-subtitle {
            max-width: 12rem !important;
            max-height: 2.7em;
            overflow: hidden;
            font-size: 0.62rem !important;
            line-height: 1.34 !important;
            color: var(--mastery-hero-text) !important;
        }
        #personal-mastery-page .mastery-visual {
            width: 72px !important;
            right: -6px !important;
            bottom: 7px !important;
        }
        #personal-mastery-page .mastery-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: var(--mastery-saas-gap) !important;
            margin-top: 0 !important;
        }
        #personal-mastery-page .mastery-stat-card {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            min-height: 72px !important;
            padding: 9px !important;
            border-radius: var(--mastery-saas-radius) !important;
            border-color: var(--mastery-saas-border) !important;
            background: var(--sf) !important;
            box-shadow: var(--mastery-saas-shadow) !important;
        }
        #personal-mastery-page .mastery-stat-icon {
            width: 28px !important;
            height: 28px !important;
            border-radius: 9px !important;
            font-size: 0.74rem !important;
        }
        #personal-mastery-page .mastery-stat-value {
            font-size: 1rem !important;
            line-height: 1.05 !important;
            margin-bottom: 3px !important;
        }
        #personal-mastery-page .mastery-stat-label {
            font-size: 0.58rem !important;
            line-height: 1.2 !important;
            color: var(--mastery-saas-muted) !important;
        }
        #personal-mastery-page .mastery-stat-watermark {
            right: 7px !important;
            font-size: 1.8rem !important;
            opacity: 0.05 !important;
        }
        #personal-mastery-page .mastery-info-card {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 8px !important;
            margin-top: 0 !important;
            padding: 10px !important;
            border-radius: var(--mastery-saas-radius) !important;
            border-color: var(--mastery-saas-border) !important;
            background: var(--sf) !important;
            box-shadow: var(--mastery-saas-shadow) !important;
        }
        #personal-mastery-page .mastery-info-heading {
            gap: 7px !important;
            margin-bottom: 7px !important;
        }
        #personal-mastery-page .mastery-info-icon {
            width: 30px !important;
            height: 30px !important;
            border-radius: 10px !important;
            font-size: 0.76rem !important;
        }
        #personal-mastery-page .mastery-info-card h5 {
            font-size: 0.86rem !important;
            line-height: 1.16 !important;
        }
        #personal-mastery-page .mastery-info-card p {
            margin-bottom: 9px !important;
            font-size: 0.68rem !important;
            line-height: 1.34 !important;
            color: var(--mastery-saas-muted) !important;
        }
        #personal-mastery-page .mastery-progress-btn {
            min-height: 38px !important;
            padding: 0 11px !important;
            border-radius: 10px !important;
            font-size: 0.72rem !important;
            box-shadow: none !important;
        }
        #personal-mastery-page .mastery-info-art {
            display: none !important;
        }
    }
    /* Final compact hero override shared across user pages. */
    #personal-mastery-page .mastery-hero-card {
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin-bottom: 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
    }
    #personal-mastery-page .mastery-copy {
        grid-template-columns: 30px minmax(0, 1fr) !important;
        gap: 8px !important;
    }
    #personal-mastery-page .mastery-badge {
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
    }
    #personal-mastery-page .mastery-title {
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin: 0 0 3px !important;
        white-space: nowrap !important;
    }
    #personal-mastery-page .mastery-subtitle {
        max-width: 13.5rem !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
    }
    #personal-mastery-page .mastery-visual {
        right: -5px !important;
        bottom: -2px !important;
        width: 72px !important;
    }
    @media (max-width: 390px) {
        #personal-mastery-page .mastery-hero-card {
            padding: 8px 66px 8px 9px !important;
        }
        #personal-mastery-page .mastery-copy {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
        }
        #personal-mastery-page .mastery-badge {
            width: 27px !important;
            height: 27px !important;
        }
        #personal-mastery-page .mastery-title {
            font-size: 0.68rem !important;
        }
        #personal-mastery-page .mastery-subtitle {
            font-size: 0.46rem !important;
        }
        #personal-mastery-page .mastery-visual {
            width: 66px !important;
        }
    }

    /* SaaSPro mobile polish for Mastery. */
    @media (max-width: 767px) {
        body #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0, rgba(20, 184, 166, 0.035) 260px, transparent 520px),
                var(--bg) !important;
        }

        body #mob-content > .db-content {
            padding: 12px 12px 18px !important;
        }

        html body #personal-mastery-page {
            --mastery-pro-card: rgba(255, 255, 255, 0.98);
            --mastery-pro-field: rgba(255, 255, 255, 0.96);
            --mastery-pro-soft: #f8fafc;
            --mastery-pro-border: rgba(15, 23, 42, 0.1);
            --mastery-pro-title: #0f172a;
            --mastery-pro-text: #334155;
            --mastery-pro-muted: #64748b;
            --mastery-pro-accent: #2563eb;
            --mastery-pro-accent-2: #0891b2;
            --mastery-pro-success: #059669;
            --mastery-pro-danger: #dc2626;
            --mastery-pro-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
            display: flex !important;
            flex-direction: column !important;
            gap: 10px !important;
            max-width: 520px !important;
            margin: 0 auto !important;
            padding: 0 0 16px !important;
            color: var(--mastery-pro-title) !important;
        }

        html[data-theme="dark"] body #personal-mastery-page,
        :root:not(.lm) body #personal-mastery-page,
        body.dm #personal-mastery-page,
        .dm #personal-mastery-page {
            --mastery-pro-card: rgba(15, 23, 42, 0.94);
            --mastery-pro-field: rgba(30, 41, 59, 0.9);
            --mastery-pro-soft: rgba(51, 65, 85, 0.78);
            --mastery-pro-border: rgba(148, 163, 184, 0.24);
            --mastery-pro-title: #f8fafc;
            --mastery-pro-text: #e2e8f0;
            --mastery-pro-muted: #cbd5e1;
            --mastery-pro-accent: #93c5fd;
            --mastery-pro-accent-2: #67e8f9;
            --mastery-pro-success: #86efac;
            --mastery-pro-danger: #fca5a5;
            --mastery-pro-shadow: 0 1px 0 rgba(148, 163, 184, 0.08), 0 18px 36px rgba(0, 0, 0, 0.26);
        }

        html body #personal-mastery-page .mastery-hero-card {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            align-items: center !important;
            width: 100% !important;
            height: 69px !important;
            min-height: 69px !important;
            max-height: 69px !important;
            margin: 0 !important;
            padding: 8px 72px 8px 10px !important;
            border: 0 !important;
            border-radius: 8px !important;
            background:
                linear-gradient(115deg, rgba(37, 99, 235, 0.98), rgba(8, 145, 178, 0.94)),
                #2563eb !important;
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.24) !important;
            overflow: hidden !important;
            position: relative;
            isolation: isolate;
        }

        html[data-theme="dark"] body #personal-mastery-page .mastery-hero-card,
        :root:not(.lm) body #personal-mastery-page .mastery-hero-card,
        body.dm #personal-mastery-page .mastery-hero-card,
        .dm #personal-mastery-page .mastery-hero-card {
            background:
                linear-gradient(115deg, rgba(30, 64, 175, 0.96), rgba(15, 118, 110, 0.9)),
                #1e3a8a !important;
            box-shadow: 0 18px 34px rgba(0, 0, 0, 0.3) !important;
        }

        html body #personal-mastery-page .mastery-hero-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 24px 24px;
            opacity: 0.22;
            pointer-events: none;
        }

        html body #personal-mastery-page .mastery-hero-card::after {
            display: none !important;
        }

        html body #personal-mastery-page .mastery-copy {
            position: relative;
            z-index: 1;
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            min-width: 0 !important;
        }

        html body #personal-mastery-page .mastery-badge {
            width: 28px !important;
            height: 28px !important;
            min-width: 28px !important;
            border: 1px solid rgba(255, 255, 255, 0.28) !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            font-size: 0.76rem !important;
            box-shadow: none !important;
        }

        html body #personal-mastery-page .mastery-badge .fa-xl {
            font-size: 0.82rem !important;
            line-height: 1 !important;
        }

        html body #personal-mastery-page .mastery-title {
            display: block !important;
            max-width: 100% !important;
            margin: 0 0 3px !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            background: none !important;
            font-size: 0.72rem !important;
            font-weight: 900 !important;
            line-height: 1.08 !important;
            text-transform: none !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        html body #personal-mastery-page .mastery-title span {
            color: inherit !important;
        }

        html body #personal-mastery-page .mastery-subtitle {
            display: -webkit-box !important;
            max-width: 12rem !important;
            margin: 0 !important;
            color: rgba(255, 255, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(255, 255, 255, 0.9) !important;
            font-size: 0.49rem !important;
            font-weight: 700 !important;
            line-height: 1.25 !important;
            overflow: hidden !important;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        html body #personal-mastery-page .mastery-visual {
            display: block !important;
            width: 72px !important;
            height: auto !important;
            right: -5px !important;
            bottom: -2px !important;
            opacity: 0.98 !important;
            pointer-events: none;
        }

        html body #personal-mastery-page .mastery-visual svg {
            filter: drop-shadow(0 10px 16px rgba(15, 23, 42, 0.22)) !important;
        }

        html body #personal-mastery-page .mastery-stats-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin: 0 !important;
        }

        html body #personal-mastery-page .mastery-stats-grid > div {
            min-width: 0 !important;
        }

        html body #personal-mastery-page .mastery-stat-card {
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            min-height: 78px !important;
            height: 100% !important;
            padding: 9px !important;
            border: 1px solid var(--mastery-pro-border) !important;
            border-radius: 8px !important;
            background: var(--mastery-pro-card) !important;
            box-shadow: var(--mastery-pro-shadow) !important;
            color: var(--mastery-stat-color, var(--mastery-pro-accent)) !important;
            overflow: hidden !important;
        }

        html body #personal-mastery-page .mastery-stat-icon {
            width: 30px !important;
            height: 30px !important;
            border-radius: 8px !important;
            color: var(--mastery-stat-color, var(--mastery-pro-accent)) !important;
            background: color-mix(in srgb, var(--mastery-stat-color, var(--mastery-pro-accent)) 16%, transparent) !important;
            font-size: 0.78rem !important;
        }

        html body #personal-mastery-page .mastery-stat-value {
            max-width: 100% !important;
            margin: 0 0 3px !important;
            color: var(--mastery-pro-title) !important;
            font-size: 0.96rem !important;
            font-weight: 900 !important;
            line-height: 1.05 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        html body #personal-mastery-page .mastery-stat-label {
            color: var(--mastery-pro-muted) !important;
            font-size: 0.58rem !important;
            font-weight: 800 !important;
            line-height: 1.18 !important;
            overflow-wrap: anywhere;
        }

        html body #personal-mastery-page .mastery-stat-watermark {
            right: 7px !important;
            font-size: 1.7rem !important;
            color: var(--mastery-stat-color, var(--mastery-pro-accent)) !important;
            opacity: 0.1 !important;
        }

        html body #personal-mastery-page .mastery-info-card {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 8px !important;
            margin: 0 !important;
            padding: 10px !important;
            border: 1px solid var(--mastery-pro-border) !important;
            border-radius: 8px !important;
            background: var(--mastery-pro-card) !important;
            box-shadow: var(--mastery-pro-shadow) !important;
            color: var(--mastery-pro-title) !important;
        }

        html body #personal-mastery-page .mastery-info-heading {
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            margin-bottom: 8px !important;
        }

        html body #personal-mastery-page .mastery-info-icon {
            width: 30px !important;
            height: 30px !important;
            border: 1px solid rgba(37, 99, 235, 0.18) !important;
            border-radius: 8px !important;
            background: rgba(37, 99, 235, 0.1) !important;
            color: var(--mastery-pro-accent) !important;
            font-size: 0.74rem !important;
        }

        html body #personal-mastery-page .mastery-info-card h5 {
            color: var(--mastery-pro-title) !important;
            font-size: 0.84rem !important;
            font-weight: 900 !important;
            line-height: 1.16 !important;
            margin: 0 !important;
        }

        html body #personal-mastery-page .mastery-info-card p {
            margin: 0 0 9px !important;
            color: var(--mastery-pro-muted) !important;
            font-size: 0.68rem !important;
            font-weight: 650 !important;
            line-height: 1.34 !important;
        }

        html body #personal-mastery-page .mastery-progress-btn {
            min-height: 38px !important;
            width: auto !important;
            max-width: 100% !important;
            padding: 0 10px !important;
            border-radius: 8px !important;
            background: linear-gradient(135deg, var(--mastery-pro-accent), var(--mastery-pro-accent-2)) !important;
            color: #ffffff !important;
            font-size: 0.72rem !important;
            font-weight: 900 !important;
            line-height: 1.12 !important;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24) !important;
        }

        html body #personal-mastery-page .mastery-progress-btn span {
            min-width: 0 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        html body #personal-mastery-page .mastery-progress-btn i {
            color: #ffffff !important;
            flex: 0 0 auto;
        }

        html body #personal-mastery-page .mastery-info-art {
            display: none !important;
        }
    }

    @media (max-width: 390px) {
        html body #personal-mastery-page .mastery-hero-card {
            padding: 8px 66px 8px 10px !important;
        }

        html body #personal-mastery-page .mastery-copy {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
        }

        html body #personal-mastery-page .mastery-badge {
            width: 26px !important;
            height: 26px !important;
            min-width: 26px !important;
            font-size: 0.7rem !important;
        }

        html body #personal-mastery-page .mastery-title {
            font-size: 0.68rem !important;
        }

        html body #personal-mastery-page .mastery-subtitle {
            max-width: 10.8rem !important;
            font-size: 0.46rem !important;
        }

        html body #personal-mastery-page .mastery-visual {
            width: 66px !important;
            right: -6px !important;
        }
    }

    @media (max-width: 360px) {
        html body #personal-mastery-page .mastery-stats-grid {
            grid-template-columns: 1fr !important;
        }

        html body #personal-mastery-page .mastery-stat-card {
            min-height: 68px !important;
        }
    }

    body.user-desktop-shell #personal-mastery-page {
        --mastery-shell-gap: 10px;
        --mastery-shell-radius: 12px;
        --mastery-shell-border: rgba(15, 23, 42, 0.12);
        --mastery-shell-card: rgba(255, 255, 255, 0.98);
        --mastery-shell-field: rgba(248, 250, 252, 0.92);
        --mastery-shell-title: #0f172a;
        --mastery-shell-text: #334155;
        --mastery-shell-muted: #64748b;
        --mastery-shell-accent: #2563eb;
        --mastery-shell-accent-2: #0891b2;
        --mastery-shell-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 0 24px !important;
        display: block !important;
        color: var(--mastery-shell-title) !important;
        overflow-x: hidden !important;
    }

    html[data-theme="dark"] body.user-desktop-shell #personal-mastery-page,
    :root:not(.lm) body.user-desktop-shell #personal-mastery-page,
    body.user-desktop-shell.dm #personal-mastery-page,
    body.user-desktop-shell .dm #personal-mastery-page {
        --mastery-shell-border: rgba(148, 163, 184, 0.24);
        --mastery-shell-card: rgba(15, 23, 42, 0.96);
        --mastery-shell-field: rgba(30, 41, 59, 0.92);
        --mastery-shell-title: #f8fafc;
        --mastery-shell-text: #e2e8f0;
        --mastery-shell-muted: #cbd5e1;
        --mastery-shell-accent: #93c5fd;
        --mastery-shell-accent-2: #67e8f9;
        --mastery-shell-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
    }

    body.user-desktop-shell #personal-mastery-page > :is(.mastery-hero-card, .mastery-action-row, .mastery-stats-grid, .mastery-info-card) {
        margin-top: 0 !important;
        margin-bottom: var(--mastery-shell-gap) !important;
    }

    body.user-desktop-shell #personal-mastery-page > :last-child {
        margin-bottom: 0 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-hero-card {
        position: relative !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) 180px !important;
        align-items: center !important;
        min-height: 116px !important;
        height: auto !important;
        gap: 14px !important;
        padding: 18px 178px 18px 20px !important;
        border: 1px solid rgba(191, 219, 254, 0.86) !important;
        border-radius: var(--mastery-shell-radius) !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
        box-shadow: 0 10px 26px rgba(37, 99, 235, 0.12) !important;
        overflow: hidden !important;
    }

    html[data-theme="dark"] body.user-desktop-shell #personal-mastery-page .mastery-hero-card,
    :root:not(.lm) body.user-desktop-shell #personal-mastery-page .mastery-hero-card,
    body.user-desktop-shell.dm #personal-mastery-page .mastery-hero-card,
    body.user-desktop-shell .dm #personal-mastery-page .mastery-hero-card {
        border-color: rgba(147, 197, 253, 0.28) !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.28), transparent 35%),
            linear-gradient(142deg, rgba(15, 23, 42, 0.98) 0%, rgba(17, 24, 39, 0.98) 58%, rgba(30, 41, 59, 0.96) 100%) !important;
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.24) !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-hero-card::after {
        content: none !important;
        display: none !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-copy {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        min-width: 0 !important;
        position: relative !important;
        z-index: 1 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-badge {
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        border-radius: 12px !important;
        border: 1px solid rgba(147, 197, 253, 0.42) !important;
        background: rgba(239, 246, 255, 0.92) !important;
        color: #1d4ed8 !important;
        font-size: 1.05rem !important;
        box-shadow: none !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-badge .fa-xl {
        font-size: 1.05rem !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-title {
        margin: 0 0 5px !important;
        color: var(--mastery-shell-title) !important;
        -webkit-text-fill-color: var(--mastery-shell-title) !important;
        background: none !important;
        font-size: clamp(1.12rem, 1.08vw, 1.45rem) !important;
        line-height: 1.12 !important;
        font-weight: 900 !important;
        text-transform: none !important;
        letter-spacing: 0 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-title span {
        color: inherit !important;
        -webkit-text-fill-color: inherit !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-subtitle {
        max-width: 640px !important;
        margin: 0 !important;
        color: var(--mastery-shell-text) !important;
        font-size: 0.84rem !important;
        line-height: 1.42 !important;
        font-weight: 600 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-visual {
        display: block !important;
        position: absolute !important;
        right: 12px !important;
        bottom: -10px !important;
        width: clamp(140px, 12vw, 174px) !important;
        max-width: none !important;
        opacity: 0.96 !important;
        pointer-events: none !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-visual svg {
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16)) !important;
        animation: none !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-stats-grid {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: var(--mastery-shell-gap) !important;
        width: 100% !important;
        margin-top: 0 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-stat-card {
        min-height: 86px !important;
        height: 100% !important;
        padding: 10px !important;
        border: 1px solid var(--mastery-shell-border) !important;
        border-radius: var(--mastery-shell-radius) !important;
        background: var(--mastery-shell-card) !important;
        color: var(--mastery-stat-color, var(--mastery-shell-accent)) !important;
        box-shadow: var(--mastery-shell-shadow) !important;
        transform: none !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-stat-icon {
        width: 32px !important;
        height: 32px !important;
        border-radius: 9px !important;
        color: var(--mastery-stat-color, var(--mastery-shell-accent)) !important;
        background: color-mix(in srgb, var(--mastery-stat-color, var(--mastery-shell-accent)) 16%, transparent) !important;
        font-size: 0.86rem !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-stat-value {
        margin: 0 0 3px !important;
        color: var(--mastery-shell-title) !important;
        font-size: 1.02rem !important;
        line-height: 1.05 !important;
        font-weight: 900 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-stat-label {
        color: var(--mastery-shell-muted) !important;
        font-size: 0.56rem !important;
        line-height: 1.12 !important;
        font-weight: 850 !important;
        text-transform: uppercase !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-stat-watermark {
        right: 8px !important;
        font-size: 1.9rem !important;
        color: var(--mastery-stat-color, var(--mastery-shell-accent)) !important;
        opacity: 0.1 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-action-row {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        margin-top: 0 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-info-card {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        gap: 12px !important;
        align-items: center !important;
        padding: 14px !important;
        border: 1px solid var(--mastery-shell-border) !important;
        border-radius: var(--mastery-shell-radius) !important;
        background: var(--mastery-shell-card) !important;
        color: var(--mastery-shell-title) !important;
        box-shadow: var(--mastery-shell-shadow) !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-info-heading {
        display: grid !important;
        grid-template-columns: 34px minmax(0, 1fr) !important;
        gap: 10px !important;
        align-items: center !important;
        margin-bottom: 8px !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-info-icon {
        width: 34px !important;
        height: 34px !important;
        border-radius: 10px !important;
        background: rgba(37, 99, 235, 0.1) !important;
        color: var(--mastery-shell-accent) !important;
        font-size: 0.84rem !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-info-card h5 {
        margin: 0 !important;
        color: var(--mastery-shell-title) !important;
        font-size: 0.94rem !important;
        line-height: 1.18 !important;
        font-weight: 900 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-info-card p {
        margin: 0 0 8px !important;
        color: var(--mastery-shell-muted) !important;
        font-size: 0.7rem !important;
        line-height: 1.32 !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-progress-btn {
        min-height: 34px !important;
        width: auto !important;
        padding: 7px 12px !important;
        border-radius: 9px !important;
        background: linear-gradient(135deg, var(--mastery-shell-accent), var(--mastery-shell-accent-2)) !important;
        color: #ffffff !important;
        font-size: 0.68rem !important;
        font-weight: 900 !important;
        box-shadow: none !important;
    }

    body.user-desktop-shell #personal-mastery-page .mastery-info-art {
        display: none !important;
    }

    @media (max-width: 767.98px) {
        html body #personal-mastery-page .mastery-action-row {
            display: none !important;
        }

        html body #personal-mastery-page .mastery-progress-btn.mastery-progress-btn-mobile {
            display: inline-flex !important;
            width: 100% !important;
            max-width: none !important;
        }
    }
</style>

<div class="db-section active" id="personal-mastery-page">
    <div class="mastery-hero-card">
        <div class="mastery-copy">
            <div class="mastery-badge" aria-hidden="true">
                <i class="fa-solid fa-trophy fa-xl"></i>
            </div>
            <div>
                <h5 class="mastery-title">Philippines <span>Personal Mastery</span></h5>
                <p class="mastery-subtitle">Track private interview growth without public rankings.</p>
            </div>
        </div>
        <div class="mastery-visual" aria-hidden="true">
            <svg viewBox="0 0 260 170" role="img">
                <defs>
                    <linearGradient id="masteryPanel" x1="40" y1="18" x2="212" y2="142" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#fff7e6"/>
                        <stop offset="1" stop-color="#dcebff"/>
                    </linearGradient>
                    <linearGradient id="masteryShield" x1="104" y1="52" x2="164" y2="118" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#263c62"/>
                        <stop offset="1" stop-color="#1662b8"/>
                    </linearGradient>
                    <filter id="masteryShadow" x="0" y="0" width="260" height="170" filterUnits="userSpaceOnUse">
                        <feDropShadow dx="0" dy="10" stdDeviation="10" flood-color="#3266bc" flood-opacity=".2"/>
                    </filter>
                </defs>
                <rect x="31" y="28" width="205" height="111" rx="23" fill="url(#masteryPanel)" stroke="#b6d2fb" stroke-width="2" filter="url(#masteryShadow)" transform="rotate(3 133.5 83.5)"/>
                <path d="M111 58h54v31c0 19-14 36-27 43-14-7-27-24-27-43V58Z" fill="url(#masteryShield)"/>
                <path d="M121 70h34v18c0 11-8 21-17 26-9-5-17-15-17-26V70Z" fill="#fff"/>
                <path d="m130 91 8 8 18-24" fill="none" stroke="#fb9700" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M51 124h126" stroke="#8ab5ff" stroke-width="10" stroke-linecap="round" opacity=".55"/>
                <rect x="58" y="83" width="17" height="39" rx="9" fill="#1598ef"/>
                <rect x="84" y="65" width="17" height="57" rx="9" fill="#15bce9"/>
                <rect x="194" y="58" width="18" height="68" rx="9" fill="#36d56f"/>
                <circle cx="62" cy="46" r="16" fill="#fb9700"/>
                <circle cx="213" cy="48" r="15" fill="#2f80ed"/>
                <path d="M26 155c39-17 77-18 118-3 31 12 67 12 92-4" fill="none" stroke="#78a7ff" stroke-width="10" stroke-linecap="round" opacity=".55"/>
            </svg>
        </div>
    </div>
    <div class="mastery-action-row">
        <a class="mastery-progress-btn" href="{{ route('user.progress') }}">
            <i class="fa-solid fa-chart-line"></i>
            <span>Open Philippines Progress</span>
            <i class="fa-solid fa-chevron-right"></i>
        </a>
    </div>
    <div class="mastery-stats-grid">
        @foreach([
            ['Personal best', $personalBest.'%', 'fa-trophy', '#f59e0b'],
            ['Latest assessed', $latest.'%', 'fa-bullseye', '#3b82f6'],
            ['Growth from baseline', (($latest-$baseline) >= 0 ? '+' : '').($latest-$baseline).' pts', 'fa-arrow-trend-up', '#10b981'],
            ['Practice streak', ($profile->current_streak ?? 0).' days', 'fa-fire', '#ef4444'],
        ] as [$label,$value,$icon,$color])
            <div>
                <div class="mastery-stat-card" style="--mastery-stat-color: {{ $color }}">
                    <div class="mastery-stat-icon">
                        <i class="fa-solid {{ $icon }}"></i>
                    </div>
                    <div>
                        <div class="fw-bold mastery-stat-value">{{ $value }}</div>
                        <div class="mastery-stat-label">{{ $label }}</div>
                    </div>
                    <i class="fa-solid {{ $icon }} mastery-stat-watermark" aria-hidden="true"></i>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mastery-info-card">
        <div>
            <div class="mastery-info-heading">
                <span class="mastery-info-icon"><i class="fa-solid fa-info"></i></span>
                <h5>What counts here?</h5>
            </div>
            <p>Only score-eligible interview assessments count here. Coached practice stays in history but does not change your mastery baseline.</p>
            <a class="mastery-progress-btn mastery-progress-btn-mobile" href="{{ route('user.progress') }}">
                <i class="fa-solid fa-chart-line"></i>
                <span>Open Philippines Progress</span>
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
        <svg class="mastery-info-art" viewBox="0 0 130 150" aria-hidden="true">
            <path d="M26 22c15-18 48-24 77-8" fill="none" stroke="currentColor" stroke-width="8" opacity=".18" stroke-linecap="round"/>
            <rect x="30" y="30" width="78" height="104" rx="13" fill="#eef5ff" stroke="currentColor" stroke-width="5"/>
            <rect x="55" y="24" width="28" height="16" rx="6" fill="currentColor"/>
            <path d="m45 61 8 8 15-17M45 88l8 8 15-17M45 115l8 8 15-17" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M78 64h21M78 91h21M78 118h21" stroke="currentColor" stroke-width="5" stroke-linecap="round" opacity=".45"/>
        </svg>
    </div>
</div>
@endsection
