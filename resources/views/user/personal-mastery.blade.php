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
        max-width: 980px;
        margin: 0 auto;
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
        color: currentColor;
        font-size: 2.5rem;
        opacity: 0.06;
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
        background: color-mix(in srgb, currentColor 14%, transparent);
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
    <div class="mastery-stats-grid">
        @foreach([
            ['Personal best', $personalBest.'%', 'fa-trophy', '#f59e0b'],
            ['Latest assessed', $latest.'%', 'fa-bullseye', '#3b82f6'],
            ['Growth from baseline', (($latest-$baseline) >= 0 ? '+' : '').($latest-$baseline).' pts', 'fa-arrow-trend-up', '#10b981'],
            ['Practice streak', ($profile->current_streak ?? 0).' days', 'fa-fire', '#ef4444'],
        ] as [$label,$value,$icon,$color])
            <div>
                <div class="mastery-stat-card" style="color:{{ $color }}">
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
            <a class="mastery-progress-btn" href="{{ route('user.progress') }}">
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
