@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
@php
    $scoreVal = (int) round($profile->readiness_score ?? $avgScore ?? 0);
    $scoreVal = max(0, min(100, $scoreVal));
    $scoreClass = $scoreVal >= 80 ? 'score-high' : ($scoreVal >= 60 ? 'score-med' : 'score-low');
    $scoreText = $scoreVal >= 80 ? 'Interview Ready' : ($scoreVal >= 60 ? 'Building Momentum' : 'Practice Mode');
    $mobileScoreText = $scoreVal >= 80 ? 'Interview Ready' : 'Building Momentum';
    $scoreIcon = $scoreVal >= 80 ? 'fa-circle-check' : ($scoreVal >= 60 ? 'fa-chart-line' : 'fa-arrow-trend-up');
    $fullName = trim(Auth::user()->name ?? '') ?: 'User';
    $nameParts = preg_split('/\s+/', $fullName);
    $firstName = $nameParts[0] ?? 'User';
    $welcomeName = $firstName;
    $rating = round(($avgScore ?? 0) / 20, 1);
    $goalPercent = isset($upcomingGoal) ? max(0, min(100, round($upcomingGoal->percent ?? 0))) : 0;
    $categoryCount = isset($categoryPerformance) ? count($categoryPerformance) : 0;
    $moduleCount = isset($learningLabProgress) ? count($learningLabProgress) : 0;
    $sessionsMeter = max(0, min(100, (int) round((($totalSessions ?? 0) / 10) * 100)));
    $ratingMeter = max(0, min(100, (int) round(($rating / 5) * 100)));
    $xpValue = max(0, (int) ($experiencePoints ?? 0));
    $playerLevel = max(1, (int) ($profile->player_level ?? (floor($xpValue / 1000) + 1)));
    $xpMeter = max(0, min(100, (int) round((($xpValue % 1000) / 1000) * 100)));
    $streakMeter = max(0, min(100, (int) round((($currentStreak ?? 0) / 7) * 100)));
    $trendScores = collect($scoreTrend ?? [])->pluck('score')->filter(fn ($score) => is_numeric($score))->map(fn ($score) => (int) round($score))->values();
    $trendAverage = $trendScores->isNotEmpty() ? (int) round($trendScores->avg()) : $scoreVal;
    $trendFirst = $trendScores->first();
    $trendLast = $trendScores->last();
    $trendImprovement = ($trendFirst !== null && $trendFirst > 0 && $trendLast !== null)
        ? (int) round((($trendLast - $trendFirst) / $trendFirst) * 100)
        : 0;
@endphp

<style>
    :root {
        --dash-primary: #3b82f6;
        --dash-primary-2: #0ea5e9;
        --dash-success: #22c55e;
        --dash-warning: #f59e0b;
        --dash-danger: #ef4444;
        --dash-info: #06b6d4;
        --dash-section-gap: 20px;
        --dash-card-radius: 16px;
        --dash-card-pad: 20px;
        --dash-welcome-bg-a: rgba(59, 130, 246, 0.24);
        --dash-welcome-bg-b: rgba(14, 165, 233, 0.13);
        --dash-welcome-bg-c: #121a2b;
        --dash-welcome-bg-d: #18243a;
        --dash-welcome-border: rgba(96, 165, 250, 0.22);
        --dash-welcome-title: #f8fbff;
        --dash-welcome-subtitle: #bfdbfe;
        --dash-welcome-sheen: rgba(96, 165, 250, 0.14);
        --dash-welcome-shadow: rgba(2, 6, 23, 0.26);
    }

    .lm {
        --dash-welcome-bg-a: rgba(255, 255, 255, 0.72);
        --dash-welcome-bg-b: rgba(96, 165, 250, 0.16);
        --dash-welcome-bg-c: #eef7ff;
        --dash-welcome-bg-d: #d8ecff;
        --dash-welcome-border: #dbeafe;
        --dash-welcome-title: #111827;
        --dash-welcome-subtitle: #475569;
        --dash-welcome-sheen: rgba(191, 219, 254, 0.22);
        --dash-welcome-shadow: rgba(37, 99, 235, 0.08);
    }

    .sr-dashboard {
        display: flex;
        flex-direction: column;
        gap: var(--dash-section-gap);
        padding-top: 10px !important;
        padding-bottom: 28px !important;
        max-width: 1440px;
        margin-inline: auto;
    }

    #dashboard .db-content .db-section.active.sr-dashboard {
        display: flex;
        flex-direction: column;
        gap: var(--dash-section-gap);
        padding-top: 10px !important;
        padding-bottom: 28px !important;
    }

    .sr-dashboard-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: var(--dash-section-gap);
        align-items: start;
    }

    .sr-summary-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
        gap: var(--dash-section-gap);
        align-items: stretch;
    }

    .sr-summary-grid > .sr-card {
        min-width: 0;
    }

    .sr-mobile-readiness-row {
        min-width: 0;
    }

    .sr-mobile-readiness-row > .sr-score-panel {
        min-width: 0;
    }

    .sr-welcome-stack {
        min-width: 0;
    }

    .sr-main-stack,
    .sr-side-stack {
        display: flex;
        flex-direction: column;
        gap: var(--dash-section-gap);
        min-width: 0;
    }

    .sr-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: var(--dash-card-radius);
        box-shadow: var(--shadow-soft, 0 10px 28px rgba(0,0,0,.12));
        color: var(--tx);
        overflow: hidden;
        min-width: 0;
        width: 100%;
    }

    .sr-card-pad {
        padding: var(--dash-card-pad);
    }

    .sr-hero-card {
        position: relative;
        isolation: isolate;
        min-height: 150px;
        overflow: hidden;
        border-radius: 16px;
        background:
            radial-gradient(circle at 94% 8%, var(--dash-welcome-bg-a), transparent 25%),
            radial-gradient(circle at 68% 86%, var(--dash-welcome-bg-b), transparent 28%),
            linear-gradient(112deg, var(--dash-welcome-bg-c) 0%, color-mix(in srgb, var(--dash-welcome-bg-c) 58%, var(--dash-welcome-bg-d) 42%) 54%, var(--dash-welcome-bg-d) 100%);
        border-color: var(--dash-welcome-border);
        box-shadow: 0 10px 26px var(--dash-welcome-shadow);
        margin-bottom: 2px;
    }

    .lm .sr-hero-card {
        box-shadow: 0 7px 22px var(--dash-welcome-shadow);
    }

    .sr-hero-card::after {
        content: "";
        position: absolute;
        z-index: -1;
        inset: 0 0 0 auto;
        width: min(45%, 380px);
        background: linear-gradient(90deg, transparent, var(--dash-welcome-sheen));
        pointer-events: none;
    }

    .sr-hero-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        min-height: 150px;
        padding: 26px clamp(188px, 35%, 252px) 24px 22px;
    }

    .sr-welcome-art {
        position: absolute;
        z-index: 0;
        right: 14px;
        bottom: -3px;
        width: clamp(188px, 24vw, 226px);
        aspect-ratio: 3 / 2;
        pointer-events: none;
        user-select: none;
        transform-origin: 50% 78%;
        filter: drop-shadow(0 13px 18px rgba(37, 99, 235, 0.14));
        animation: srWelcomeFloat 6.4s cubic-bezier(0.42, 0, 0.2, 1) infinite;
    }

    .sr-welcome-robot-img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center bottom;
        user-select: none;
    }

    .sr-welcome-hand-wave-img {
        position: absolute;
        inset: 0;
        z-index: 4;
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center bottom;
        pointer-events: none;
        user-select: none;
        clip-path: inset(17% 68% 49% 12%);
        transform-origin: 25% 44%;
        animation: srRobotHandWave 5.8s cubic-bezier(0.42, 0, 0.2, 1) infinite;
        will-change: transform;
    }

    .sr-robot-hand-message {
        position: absolute;
        z-index: 5;
        top: -13%;
        left: -78%;
        width: clamp(190px, 108%, 260px);
        height: auto;
        overflow: visible;
        pointer-events: none;
        transform-origin: 94% 41%;
        filter: drop-shadow(0 12px 18px rgba(15, 23, 42, 0.22));
        animation: srRobotMessageFromHand 5.8s cubic-bezier(0.22, 1, 0.36, 1) infinite;
        will-change: opacity, transform;
    }

    .sr-robot-message-bubble,
    .sr-robot-message-tail {
        fill: rgba(255, 255, 255, 0.96);
        stroke: rgba(147, 197, 253, 0.86);
        stroke-width: 2;
    }

    .sr-robot-message-tail {
        stroke-linejoin: round;
    }

    .sr-robot-message-shine {
        fill: #bfdbfe;
        opacity: 0.36;
    }

    .sr-robot-message-dot {
        fill: #38bdf8;
        animation: srRobotMessageDotPulse 1.55s ease-in-out infinite;
        transform-box: fill-box;
        transform-origin: center;
    }

    .sr-robot-message-dot:nth-of-type(2) {
        animation-delay: 0.18s;
    }

    .sr-robot-message-dot:nth-of-type(3) {
        animation-delay: 0.36s;
    }

    .sr-robot-message-text {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        width: 100%;
        height: 100%;
        overflow: hidden;
        color: #0f172a;
        font-family: inherit;
        font-size: 22px;
        font-weight: 900;
        line-height: 1;
        text-align: center;
        animation: srRobotMessageContentReveal 5.8s ease-in-out infinite;
    }

    .sr-robot-message-greeting {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        max-width: 100%;
        min-width: 0;
        white-space: nowrap;
    }

    .sr-robot-message-text strong {
        min-width: 0;
        max-width: 96px;
        overflow: hidden;
        color: #2563eb;
        text-overflow: ellipsis;
    }

    .sr-robot-message-question {
        display: block;
        width: 100%;
        color: #0ea5e9;
        font-size: 0.78em;
        font-weight: 900;
        line-height: 1;
        white-space: nowrap;
    }

    @media (min-width: 992px) {
        .sr-dashboard {
            --dash-section-gap: 18px;
            --dash-card-pad: 18px;
        }

        .sr-hero-card {
            min-height: 172px;
        }

        .sr-hero-inner {
            min-height: 172px;
            padding: 28px clamp(232px, 28%, 320px) 26px 28px;
        }

        .sr-hero-card .sr-subtitle {
            max-width: 560px;
            font-size: clamp(0.94rem, 1.35vw, 1.18rem);
            line-height: 1.38;
        }

        .sr-welcome-art {
            right: clamp(18px, 2.4vw, 30px);
            bottom: 2px;
            width: clamp(168px, 15vw, 198px);
        }

        .sr-robot-hand-message {
            top: -8%;
            left: -60%;
            width: clamp(142px, 82%, 172px);
        }

        .sr-summary-grid {
            align-items: stretch;
        }

        .sr-welcome-stack {
            display: flex;
            flex-direction: column;
            gap: var(--dash-section-gap);
        }

        .sr-hero-card {
            align-self: start;
        }

        .sr-welcome-stack .sr-hero-card {
            align-self: stretch;
        }

        .sr-welcome-stack .sr-stats-desktop {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin: 0;
        }

        .sr-welcome-stack .sr-stat-card {
            min-height: 150px;
            padding: 16px;
            border-radius: 16px;
        }

        .sr-welcome-stack .sr-stat-head {
            gap: 8px;
            min-width: 0;
        }

        .sr-welcome-stack .sr-stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            font-size: 0.9rem;
        }

        .sr-welcome-stack .sr-chip {
            padding: 8px 14px;
            font-size: 0.86rem;
            white-space: nowrap;
        }

        .sr-welcome-stack .sr-stat-value {
            margin-top: 18px;
            font-size: clamp(1.7rem, 2.35vw, 2.25rem);
        }

        .sr-welcome-stack .sr-stat-label {
            font-size: clamp(0.86rem, 1.15vw, 1rem);
            line-height: 1.22;
        }

        .sr-mobile-readiness-row {
            min-width: 0;
            height: 100%;
        }

        .sr-mobile-readiness-row > .sr-score-panel {
            height: 100%;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-score-layout {
            flex: 1 1 auto;
            align-content: center;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-score-layout {
            grid-template-columns: 1fr;
            gap: 26px;
            align-items: center;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-score-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            width: 100%;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-readiness-ring {
            --ring-size: clamp(160px, 14vw, 190px);
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-readiness-ring::before {
            inset: 10px;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-score-value {
            font-size: clamp(2.85rem, 4vw, 3.45rem);
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-score-value span {
            font-size: 1.05rem;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-ring-label {
            font-size: 0.74rem;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-score-meta-item {
            min-height: 106px;
            padding: 18px;
            border-radius: 16px;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-meta-label {
            font-size: 0.86rem;
            line-height: 1.35;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-meta-value {
            font-size: 1.55rem;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-score-icon {
            width: 48px;
            height: 48px;
            flex-basis: 48px;
            border-radius: 16px;
            font-size: 1.12rem;
        }

        .sr-mobile-readiness-row > .sr-score-panel .sr-score-note {
            margin-top: 18px;
        }

        .sr-dashboard-shell {
            grid-template-columns: minmax(0, 1fr) minmax(320px, 340px);
        }
    }

    @media (min-width: 1400px) {
        .sr-dashboard {
            --dash-section-gap: 20px;
        }

        .sr-summary-grid {
            grid-template-columns: minmax(0, 1fr) minmax(320px, 350px);
        }

        .sr-hero-inner {
            padding-right: clamp(280px, 30%, 360px);
        }

        .sr-hero-card .sr-subtitle {
            max-width: 660px;
        }

        .sr-welcome-art {
            width: clamp(188px, 15vw, 218px);
        }

        .sr-dashboard-shell {
            grid-template-columns: minmax(0, 1fr) minmax(330px, 350px);
        }
    }

    .sr-user-row {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 14px;
        width: 100%;
        min-width: 0;
    }

    .sr-welcome-copy {
        min-width: 0;
    }

    .sr-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: #60a5fa;
        font-size: 0.78rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .sr-title {
        margin: 0;
        font-size: clamp(1.48rem, 2.7vw, 1.92rem);
        line-height: 1.12;
        font-weight: 900;
        color: var(--dash-welcome-title);
    }

    .sr-title-name {
        color: var(--dash-primary);
        font-weight: 900;
    }

    .sr-wave {
        display: inline-block;
        margin-left: 5px;
        transform-origin: 70% 70%;
        animation: srWaveHand 1.8s ease-in-out infinite;
    }

    @keyframes srWelcomeFloat {
        0%, 100% {
            transform: translate3d(0, 0, 0) rotate(0deg) scale(1);
        }
        42% {
            transform: translate3d(0, -5px, 0) rotate(0.9deg) scale(1.008);
        }
        72% {
            transform: translate3d(-2px, -2px, 0) rotate(-0.5deg) scale(1.003);
        }
    }

    @keyframes srWaveHand {
        0%, 60%, 100% {
            transform: rotate(0deg);
        }
        10% {
            transform: rotate(16deg);
        }
        20% {
            transform: rotate(-10deg);
        }
        30% {
            transform: rotate(14deg);
        }
        40% {
            transform: rotate(-6deg);
        }
        50% {
            transform: rotate(8deg);
        }
    }

    @keyframes srRobotHandWave {
        0%, 5%, 34%, 100% {
            transform: rotate(0deg) translate3d(0, 0, 0);
        }
        8% {
            transform: rotate(5deg) translate3d(0, -1px, 0);
        }
        12% {
            transform: rotate(-8deg) translate3d(-1px, 0, 0);
        }
        17% {
            transform: rotate(9deg) translate3d(1px, -1px, 0);
        }
        22% {
            transform: rotate(-4deg) translate3d(-1px, 0, 0);
        }
        28% {
            transform: rotate(2deg) translate3d(0, -1px, 0);
        }
    }

    @keyframes srRobotMessageFromHand {
        0%, 30% {
            opacity: 0;
            transform: translate3d(20px, 6px, 0) scale(0.06, 0.14) rotate(6deg);
        }
        31% {
            opacity: 1;
            transform: translate3d(18px, 5px, 0) scale(0.12, 0.2) rotate(5deg);
        }
        39% {
            opacity: 1;
            transform: translate3d(-6px, -5px, 0) scale(1.07, 0.96) rotate(-1.8deg);
        }
        46% {
            opacity: 1;
            transform: translate3d(1px, 1px, 0) scale(0.985, 1.025) rotate(0.7deg);
        }
        52%, 83% {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1) rotate(0deg);
        }
        86% {
            opacity: 1;
            transform: translate3d(-2px, -3px, 0) scale(1.015) rotate(0.5deg);
        }
        91% {
            opacity: 1;
            transform: translate3d(8px, 4px, 0) scale(0.42, 0.34) rotate(4deg);
        }
        96%, 100% {
            opacity: 0;
            transform: translate3d(20px, 6px, 0) scale(0.06, 0.14) rotate(6deg);
        }
    }

    @keyframes srRobotMessageContentReveal {
        0%, 43%, 88%, 100% {
            opacity: 0;
            transform: translateY(3px);
        }
        53%, 80% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes srWelcomeSubtitleIn {
        from {
            opacity: 0;
            transform: translate3d(0, 8px, 0);
        }
        to {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }
    }

    @keyframes srSubtitleAccentMove {
        0%, 100% {
            transform: translate3d(0, 0, 0);
            text-shadow: 0 2px 12px rgba(15, 23, 42, 0.18);
        }
        42% {
            transform: translate3d(0, -1px, 0);
            text-shadow: 0 5px 16px rgba(15, 23, 42, 0.24);
        }
        68% {
            transform: translate3d(0, 0, 0);
            text-shadow: 0 2px 12px rgba(15, 23, 42, 0.18);
        }
    }

    @keyframes srRobotMessageDotPulse {
        0%, 100% {
            opacity: 0.42;
            transform: scale(0.82);
        }
        50% {
            opacity: 1;
            transform: scale(1);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .sr-welcome-hand-wave-img,
        .sr-welcome-art,
        .sr-wave,
        .sr-robot-hand-message,
        .sr-robot-message-text,
        .sr-robot-message-dot,
        .sr-hero-card .sr-subtitle,
        .sr-subtitle-accent {
            animation: none;
        }

        .sr-robot-hand-message {
            opacity: 1;
            transform: none;
        }
    }

    .sr-subtitle {
        margin: 0;
        color: var(--dash-welcome-subtitle);
        font-size: clamp(0.82rem, 1.25vw, 1rem);
        font-weight: 650;
        line-height: 1.45;
        max-width: 520px;
        text-wrap: balance;
    }

    .sr-hero-card .sr-subtitle {
        animation: srWelcomeSubtitleIn 720ms cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .sr-subtitle-accent {
        display: inline-block;
        color: #fde047 !important;
        font-weight: 900;
        text-shadow: 0 2px 12px rgba(15, 23, 42, 0.18);
        animation: srSubtitleAccentMove 5.8s ease-in-out infinite;
    }

    .sr-subtitle-accent.is-sky {
        color: #7dd3fc !important;
        animation-delay: 0.14s;
    }

    .sr-subtitle-accent.is-mint {
        color: #86efac !important;
        animation-delay: 0.28s;
    }

    .sr-btn {
        min-height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 0.9rem;
        font-weight: 700;
        padding: 10px 15px;
        text-decoration: none;
        border: 1px solid var(--bd2);
        color: var(--tx);
        background: transparent;
    }

    .sr-btn-primary {
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        border-color: transparent;
        color: #fff;
    }
    .sr-hero-card {
        --dash-name-color: #2563eb;
    }
    :root:not(.lm) .sr-hero-card {
        --dash-name-color: #93c5fd;
        --dash-welcome-title: #f8fafc;
        --dash-welcome-subtitle: #e2e8f0;
        border-color: rgba(147, 197, 253, 0.28);
    }
    .sr-title-name {
        color: var(--dash-name-color) !important;
    }
    .sr-welcome-robot-img {
        filter: drop-shadow(0 10px 18px rgba(37, 99, 235, 0.18));
    }
    :root:not(.lm) .sr-welcome-robot-img {
        filter: drop-shadow(0 12px 20px rgba(0, 0, 0, 0.45));
    }

    .sr-score-panel {
        --score-panel-bg: #ffffff;
        --score-panel-soft: #f3f6ff;
        --score-panel-border: rgba(191, 219, 254, 0.9);
        --score-panel-title: #0f172a;
        --score-panel-muted: #64748b;
        --score-panel-ring-track: #eef1f6;
        --score-panel-note-bg: #fbfdff;
        background: var(--score-panel-bg);
        border: 1px solid var(--score-panel-border);
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }

    .lm .sr-score-panel {
        --score-panel-bg: #ffffff;
        --score-panel-soft: #f3f6ff;
        --score-panel-border: rgba(191, 219, 254, 0.9);
        --score-panel-title: #0f172a;
        --score-panel-muted: #64748b;
        --score-panel-ring-track: #eef1f6;
        --score-panel-note-bg: #fbfdff;
    }

    :root:not(.lm) .sr-score-panel {
        --score-panel-bg: #151c2d;
        --score-panel-soft: #1d263a;
        --score-panel-border: rgba(96, 165, 250, 0.18);
        --score-panel-title: #f8fafc;
        --score-panel-muted: #a8b4c7;
        --score-panel-ring-track: rgba(148, 163, 184, 0.16);
        --score-panel-note-bg: rgba(15, 23, 42, 0.26);
        box-shadow: 0 14px 30px rgba(2, 6, 23, 0.24);
    }

    .sr-score-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    }

    .sr-score-layout {
        display: grid;
        grid-template-columns: minmax(128px, 0.9fr) minmax(128px, 1fr);
        gap: 14px;
        align-items: center;
    }

    .sr-readiness-ring {
        --ring-size: 126px;
        --ring-value: 0%;
        width: var(--ring-size);
        height: var(--ring-size);
        border-radius: 50%;
        margin: 0 auto;
        display: grid;
        place-items: center;
        background: conic-gradient(#ef4444 var(--ring-value), var(--score-panel-ring-track) 0);
        position: relative;
    }

    .score-med ~ .sr-score-layout .sr-readiness-ring,
    .sr-score-panel.score-med-panel .sr-readiness-ring {
        background: conic-gradient(#f59e0b var(--ring-value), var(--score-panel-ring-track) 0);
    }

    .score-high ~ .sr-score-layout .sr-readiness-ring,
    .sr-score-panel.score-high-panel .sr-readiness-ring {
        background: conic-gradient(#22c55e var(--ring-value), var(--score-panel-ring-track) 0);
    }

    .sr-readiness-ring::before {
        content: "";
        position: absolute;
        inset: 8px;
        border-radius: 50%;
        background: var(--score-panel-bg);
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.05);
    }

    .sr-ring-content {
        position: relative;
        z-index: 1;
        text-align: center;
    }

    .sr-score-value {
        color: var(--score-panel-title);
        font-size: clamp(2.05rem, 4vw, 2.45rem);
        line-height: 0.95;
        font-weight: 900;
    }

    .sr-score-value span {
        font-size: 0.92rem;
        color: var(--score-panel-title);
        margin-left: 1px;
    }

    .sr-ring-label {
        color: var(--score-panel-muted);
        font-size: 0.62rem;
        font-weight: 700;
        margin-top: 4px;
    }

    .sr-status-pill,
    .sr-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 800;
        padding: 6px 10px;
        white-space: nowrap;
    }

    .sr-status-pill {
        width: fit-content;
        max-width: max-content;
    }

    .score-high { background: rgba(34, 197, 94, 0.13); color: var(--dash-success); border: 1px solid rgba(34,197,94,.24); }
    .score-med { background: rgba(245, 158, 11, 0.13); color: var(--dash-warning); border: 1px solid rgba(245,158,11,.25); }
    .score-low { background: rgba(239, 68, 68, 0.13); color: var(--dash-danger); border: 1px solid rgba(239,68,68,.25); }

    .sr-progress {
        height: 9px;
        background: var(--bg3);
        border-radius: 999px;
        overflow: hidden;
    }

    .sr-progress > span {
        display: block;
        height: 100%;
        width: var(--value, 0%);
        border-radius: inherit;
        background: linear-gradient(90deg, #22c55e, #0ea5e9);
    }

    .sr-score-meta {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
        margin-top: 0;
    }

    .sr-score-meta-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-radius: 14px;
        padding: 12px;
        min-height: 64px;
        background: var(--score-panel-soft);
        border: 1px solid rgba(226, 232, 240, 0.72);
    }

    :root:not(.lm) .sr-score-meta-item {
        border-color: rgba(96, 165, 250, 0.14);
    }

    .sr-meta-label {
        color: var(--score-panel-muted);
        font-size: 0.72rem;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .sr-meta-value {
        color: var(--score-panel-title);
        font-size: 1.18rem;
        line-height: 1;
        font-weight: 900;
    }

    .sr-score-icon {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.13);
    }

    .sr-score-note {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 14px -14px -14px;
        padding: 10px 16px;
        color: var(--score-panel-muted);
        font-size: 0.75rem;
        font-weight: 600;
        background: var(--score-panel-note-bg);
        border-top: 1px solid var(--score-panel-border);
    }

    .sr-score-note i {
        color: #fbbf24;
    }
    .sr-score-top .sr-status-pill.score-low {
        color: #dc2626 !important;
        background: rgba(239, 68, 68, 0.11) !important;
        border-color: rgba(239, 68, 68, 0.28) !important;
    }
    .sr-score-top .sr-status-pill.score-med {
        color: #d97706 !important;
        background: rgba(245, 158, 11, 0.12) !important;
        border-color: rgba(245, 158, 11, 0.3) !important;
    }
    .sr-score-top .sr-status-pill.score-high {
        color: #059669 !important;
        background: rgba(34, 197, 94, 0.12) !important;
        border-color: rgba(34, 197, 94, 0.28) !important;
    }
    .sr-score-top .sr-status-pill i {
        color: currentColor !important;
    }
    .sr-score-top .sr-chip.ph-focus-chip {
        color: #2563eb !important;
        background: rgba(37, 99, 235, 0.1) !important;
        border-color: rgba(37, 99, 235, 0.22) !important;
    }
    .sr-score-top .sr-chip.ph-focus-chip i {
        color: #2563eb !important;
    }
    :root:not(.lm) .sr-score-top .sr-status-pill.score-low {
        color: #fca5a5 !important;
        background: rgba(239, 68, 68, 0.16) !important;
        border-color: rgba(248, 113, 113, 0.3) !important;
    }
    :root:not(.lm) .sr-score-top .sr-status-pill.score-med {
        color: #fcd34d !important;
        background: rgba(245, 158, 11, 0.16) !important;
        border-color: rgba(251, 191, 36, 0.32) !important;
    }
    :root:not(.lm) .sr-score-top .sr-status-pill.score-high {
        color: #86efac !important;
        background: rgba(34, 197, 94, 0.16) !important;
        border-color: rgba(74, 222, 128, 0.3) !important;
    }
    :root:not(.lm) .sr-chip {
        color: #bfdbfe !important;
        border-color: rgba(147, 197, 253, 0.22) !important;
        background: rgba(37, 99, 235, 0.14) !important;
    }
    :root:not(.lm) .sr-score-top .sr-chip.ph-focus-chip,
    :root:not(.lm) .sr-score-top .sr-chip.ph-focus-chip i {
        color: #bfdbfe !important;
    }
    :root:not(.lm) .sr-score-note {
        color: #cbd5e1;
        border-top-color: rgba(147, 197, 253, 0.16);
    }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: var(--dash-section-gap);
        margin: 0 0 2px;
    }

    .sr-mobile-stat-grid {
        display: none;
    }

    .sr-stat-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        background: var(--stat-bg, #ffffff);
        border: 1px solid var(--stat-border, rgba(226, 232, 240, 0.9));
        border-radius: 18px;
        padding: 18px;
        min-height: 190px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
        color: var(--stat-title, #0f172a);
    }

    :root:not(.lm) .sr-stat-card {
        --stat-bg: #151c2d;
        --stat-border: rgba(96, 165, 250, 0.16);
        --stat-title: #f8fafc;
        --stat-muted: #a8b4c7;
        box-shadow: 0 14px 30px rgba(2, 6, 23, 0.22);
    }

    .sr-stat-card::after {
        content: "";
        position: absolute;
        z-index: -1;
        left: -8%;
        right: -8%;
        bottom: -22px;
        height: 44px;
        background: color-mix(in srgb, var(--accent, #60a5fa) 14%, transparent);
        border-radius: 50% 50% 0 0 / 62% 62% 0 0;
        opacity: 0.85;
    }

    .sr-stat-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .sr-stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent, #60a5fa);
        background: color-mix(in srgb, var(--accent, #60a5fa) 13%, #ffffff);
        border: 1px solid color-mix(in srgb, var(--accent, #60a5fa) 12%, transparent);
        box-shadow: 0 10px 22px color-mix(in srgb, var(--accent, #60a5fa) 18%, transparent);
        font-size: 0.92rem;
    }

    :root:not(.lm) .sr-stat-icon {
        background: color-mix(in srgb, var(--accent, #60a5fa) 18%, #111827);
        border-color: color-mix(in srgb, var(--accent, #60a5fa) 20%, transparent);
    }

    .sr-stat-value {
        font-size: clamp(2.1rem, 3vw, 2.75rem);
        line-height: 1;
        font-weight: 900;
        color: var(--stat-title, #0f172a);
        margin-top: 30px;
        letter-spacing: 0;
    }

    .sr-stat-label {
        color: var(--stat-title, #0f172a);
        font-size: clamp(1rem, 1.8vw, 1.28rem);
        font-weight: 850;
        margin-top: 10px;
        line-height: 1.18;
    }

    .sr-stat-card .sr-chip {
        background: color-mix(in srgb, var(--accent, #60a5fa) 13%, #ffffff) !important;
        color: var(--accent, #60a5fa) !important;
        border: 1px solid color-mix(in srgb, var(--accent, #60a5fa) 9%, transparent);
        padding: 8px 14px;
        font-size: 0.88rem;
        box-shadow: none;
    }

    :root:not(.lm) .sr-stat-card .sr-chip {
        background: color-mix(in srgb, var(--accent, #60a5fa) 16%, #111827) !important;
    }

        .sr-stat-body {
            position: relative;
            padding-right: 36px;
            margin-top: 0;
        }

    .sr-stat-meter {
        position: absolute;
        right: 0;
        bottom: 0;
        width: 58px;
        height: 58px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        color: var(--accent, #60a5fa);
        background: conic-gradient(var(--accent, #60a5fa) var(--meter-value, 72%), color-mix(in srgb, var(--accent, #60a5fa) 15%, transparent) 0);
    }

    .sr-stat-meter::before {
        content: "";
        position: absolute;
        inset: 6px;
        border-radius: 50%;
        background: var(--stat-bg, #fff);
    }

    .sr-stat-meter > * {
        position: relative;
        z-index: 1;
        font-weight: 900;
        font-size: 0.88rem;
    }

    .sr-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
    }

    .sr-card-title {
        display: flex;
        align-items: center;
        gap: 7px;
        margin: 0;
        color: var(--tx);
        font-size: clamp(0.88rem, 0.9vw, 0.95rem);
        line-height: 1.25;
        font-weight: 800;
        overflow-wrap: anywhere;
        min-width: 0;
    }

    .sr-card-title i {
        flex: 0 0 auto;
        font-size: 1em;
        margin: 0 !important;
    }

    .sr-card-kicker {
        color: var(--tx3);
        font-size: 0.76rem;
        line-height: 1.35;
        margin-top: 4px;
    }

    #card-progress-chart {
        --trend-card-bg: #ffffff;
        --trend-card-border: rgba(226, 232, 240, 0.92);
        --trend-title: #0f172a;
        --trend-muted: #475569;
        --trend-soft: #f6f9ff;
        background: var(--trend-card-bg);
        border-color: var(--trend-card-border);
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
    }

    :root:not(.lm) #card-progress-chart {
        --trend-card-bg: #151c2d;
        --trend-card-border: rgba(96, 165, 250, 0.16);
        --trend-title: #f8fafc;
        --trend-muted: #b7c3d6;
        --trend-soft: #1d263a;
        box-shadow: 0 16px 34px rgba(2, 6, 23, 0.24);
    }

    .sr-trend-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 16px;
        align-items: start;
        margin-bottom: 18px;
    }

    .sr-trend-title-row {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .sr-trend-icon {
        width: 44px;
        height: 44px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.16);
        font-size: 1.18rem;
        flex: 0 0 auto;
    }

    .sr-trend-title {
        margin: 0;
        color: var(--trend-title);
        font-size: clamp(1.35rem, 2.4vw, 1.8rem);
        line-height: 1.1;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sr-trend-subtitle {
        margin: 12px 0 0;
        color: var(--trend-muted);
        font-size: clamp(0.9rem, 1.4vw, 1.08rem);
        line-height: 1.45;
        font-weight: 500;
        max-width: 500px;
    }

    .sr-trend-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .sr-trend-detail-btn,
    .sr-trend-filter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border-radius: 999px;
        min-height: 42px;
        padding: 10px 18px;
        text-decoration: none;
        border: 1px solid rgba(59, 130, 246, 0.14);
        background: var(--trend-soft);
        color: #2563eb;
        font-weight: 850;
        font-size: 0.9rem;
        white-space: nowrap;
    }

    .sr-trend-filter {
        appearance: none;
        cursor: pointer;
        padding-right: 42px;
        background-image: linear-gradient(45deg, transparent 50%, currentColor 50%), linear-gradient(135deg, currentColor 50%, transparent 50%);
        background-position: calc(100% - 20px) 50%, calc(100% - 15px) 50%;
        background-size: 5px 5px, 5px 5px;
        background-repeat: no-repeat;
    }

    .sr-trend-filter {
        color: #0f2c75;
    }

    :root:not(.lm) .sr-trend-filter {
        color: #bfdbfe;
    }

    .sr-trend-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        min-width: min(440px, 100%);
        margin-top: 14px;
    }

    .sr-trend-metric {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 76px;
        padding: 14px;
        border-radius: 14px;
        border: 1px solid var(--trend-card-border);
        background: rgba(255, 255, 255, 0.48);
    }

    :root:not(.lm) .sr-trend-metric {
        background: rgba(15, 23, 42, 0.18);
    }

    .sr-trend-metric-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        color: var(--metric-color, #2563eb);
        background: color-mix(in srgb, var(--metric-color, #2563eb) 12%, transparent);
        flex: 0 0 auto;
    }

    .sr-trend-metric-label {
        color: var(--trend-muted);
        font-size: 0.76rem;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .sr-trend-metric-value {
        color: var(--trend-title);
        font-size: 1.55rem;
        line-height: 1;
        font-weight: 900;
    }

    .sr-trend-metric-value strong {
        color: var(--metric-color, #2563eb);
        font-size: 1.75rem;
    }

    .sr-trend-chart-wrap {
        margin-top: 18px;
    }

    .sr-trend-note {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 18px;
        padding: 13px 16px;
        border: 1px solid rgba(59, 130, 246, 0.12);
        border-radius: 14px;
        background: var(--trend-soft);
        color: var(--trend-muted);
        font-size: 0.92rem;
        font-weight: 500;
    }

    .sr-trend-note i {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        flex: 0 0 auto;
    }

    .sr-trend-note strong {
        color: #0f2c75;
        font-weight: 900;
    }

    :root:not(.lm) .sr-trend-note strong {
        color: #dbeafe;
    }

    #card-progress-chart {
        padding: 16px !important;
    }
    #card-progress-chart .sr-trend-header {
        margin-bottom: 10px;
    }
    #card-progress-chart .sr-trend-title-row {
        gap: 10px;
    }
    #card-progress-chart .sr-trend-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        color: #2563eb;
        background: rgba(37, 99, 235, 0.1);
    }
    #card-progress-chart .sr-trend-title {
        font-size: 1rem;
    }
    #card-progress-chart .sr-trend-subtitle {
        margin-top: 8px;
        font-size: 0.78rem;
        line-height: 1.35;
    }
    #card-progress-chart .sr-trend-actions {
        gap: 8px;
        margin-bottom: 10px !important;
    }
    #card-progress-chart .sr-trend-detail-btn,
    #card-progress-chart .sr-trend-filter {
        min-height: 38px;
        padding: 8px 13px;
        border-radius: 14px;
        font-size: 0.76rem;
        color: #2563eb;
        background: rgba(37, 99, 235, 0.08);
        border-color: rgba(37, 99, 235, 0.16);
    }
    #card-progress-chart .sr-trend-filter {
        padding-right: 34px;
        background-position: calc(100% - 17px) 50%, calc(100% - 12px) 50%;
    }
    #card-progress-chart .sr-trend-metrics {
        gap: 8px;
        margin-top: 10px;
    }
    #card-progress-chart .sr-trend-metric {
        min-height: 52px;
        gap: 8px;
        padding: 9px;
        border-radius: 10px;
        background: rgba(248, 250, 252, 0.9);
    }
    #card-progress-chart .sr-trend-metric-icon {
        width: 30px;
        height: 30px;
        font-size: 0.76rem;
    }
    #card-progress-chart .sr-trend-metric-label {
        margin-bottom: 2px;
        font-size: 0.58rem;
    }
    #card-progress-chart .sr-trend-metric-value {
        font-size: 0.72rem;
    }
    #card-progress-chart .sr-trend-metric-value strong {
        font-size: 0.95rem;
    }
    #card-progress-chart .sr-trend-chart-wrap {
        height: 180px;
        margin-top: 12px;
    }
    #card-progress-chart .sr-trend-note {
        gap: 9px;
        margin-top: 12px;
        padding: 10px 12px;
        border-radius: 11px;
        font-size: 0.72rem;
        line-height: 1.3;
        color: #334155;
        background: rgba(239, 246, 255, 0.9);
        border-color: rgba(191, 219, 254, 0.9);
    }
    #card-progress-chart .sr-trend-note i {
        width: 30px;
        height: 30px;
    }
    :root:not(.lm) #card-progress-chart .sr-trend-icon,
    :root:not(.lm) #card-progress-chart .sr-trend-detail-btn,
    :root:not(.lm) #card-progress-chart .sr-trend-filter {
        color: #bfdbfe;
        background: rgba(37, 99, 235, 0.18);
        border-color: rgba(147, 197, 253, 0.24);
    }
    :root:not(.lm) #card-progress-chart .sr-trend-metric {
        background: rgba(15, 23, 42, 0.26);
        border-color: rgba(147, 197, 253, 0.16);
    }
    :root:not(.lm) #card-progress-chart .sr-trend-note {
        color: #d6deea;
        background: rgba(37, 99, 235, 0.12);
        border-color: rgba(147, 197, 253, 0.18);
    }

    .chart-container-mobile,
    .sr-chart-box {
        position: relative;
        width: 100%;
        height: 280px;
    }

    .chart-container-mobile canvas,
    .sr-chart-box canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }

    .sr-two-col {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--dash-section-gap);
    }

    .sr-dashboard section,
    .sr-two-col > .sr-card {
        min-width: 0;
    }

    .sr-two-col > .sr-card {
        height: 100%;
    }

    .sr-progress-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .sr-progress-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
    }

    .sr-progress-name {
        color: var(--tx);
        font-size: 0.86rem;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sr-progress-score {
        color: var(--tx3);
        font-size: 0.82rem;
        font-weight: 800;
    }

    .sr-progress-row .sr-progress {
        grid-column: 1 / -1;
        height: 7px;
    }

    .sr-empty {
        min-height: 150px;
        border: 1px dashed var(--bd2);
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: var(--tx3);
        gap: 8px;
        padding: 22px;
        background: var(--sf2, var(--bg3));
    }

    .sr-empty i {
        font-size: 1.5rem;
        color: #60a5fa;
    }

    .sr-insight-box {
        border-radius: 14px;
        border: 1px solid var(--bd);
        background: var(--sf2, var(--bg3));
        padding: 14px;
    }

    .sr-insight-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        color: var(--tx);
        font-size: 0.83rem;
        font-weight: 800;
    }

    .sr-tag-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .sr-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        background: rgba(59, 130, 246, .1);
        border: 1px solid rgba(59, 130, 246, .18);
        color: #60a5fa;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .sr-rec-list,
    .sr-notification-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .sr-rec-item,
    .sr-notification-item,
    .sr-module-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 12px;
        border-radius: 14px;
        border: 1px solid var(--bd);
        background: var(--sf2, var(--bg3));
    }

    .sr-rec-icon,
    .sr-list-icon {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: var(--accent, #60a5fa);
        background: color-mix(in srgb, var(--accent, #60a5fa) 12%, transparent);
    }

    .sr-plan-copy {
        min-width: 0;
        flex: 1 1 auto;
    }

    .sr-plan-top {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 4px;
    }

    .sr-plan-step {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 4px 8px;
        background: rgba(59, 130, 246, 0.1);
        color: #60a5fa;
        font-size: 0.68rem;
        font-weight: 800;
        line-height: 1.1;
        white-space: nowrap;
    }

    .sr-plan-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-top: 8px;
    }

    .sr-plan-cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 7px;
        color: #60a5fa;
        font-size: 0.76rem;
        font-weight: 800;
    }

    #card-practice-plan {
        --plan-bg: #ffffff;
        --plan-border: rgba(226, 232, 240, 0.92);
        --plan-title: #0f172a;
        --plan-muted: #64748b;
        --plan-soft: #f8fbff;
        background: var(--plan-bg);
        border-color: var(--plan-border);
        border-radius: 16px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
    }

    :root:not(.lm) #card-practice-plan {
        --plan-bg: #151c2d;
        --plan-border: rgba(96, 165, 250, 0.16);
        --plan-title: #f8fafc;
        --plan-muted: #a8b4c7;
        --plan-soft: #1d263a;
        box-shadow: 0 14px 30px rgba(2, 6, 23, 0.22);
    }

    .sr-plan-header {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 16px;
    }

    .sr-plan-header-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: #fff;
        background: #10b981;
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.18);
        flex: 0 0 auto;
    }

    .sr-plan-header-copy {
        min-width: 0;
        flex: 1 1 auto;
    }

    .sr-plan-title {
        margin: 0;
        color: var(--plan-title);
        font-size: 1.08rem;
        line-height: 1.2;
        font-weight: 900;
        letter-spacing: 0;
    }

    .sr-plan-subtitle {
        margin: 6px 0 0;
        max-width: 500px;
        color: var(--plan-muted);
        font-size: 0.78rem;
        line-height: 1.45;
        font-weight: 600;
    }

    .sr-plan-full-link {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 50px;
        margin-bottom: 14px;
        padding: 11px 14px;
        border: 1px solid var(--plan-border);
        border-radius: 12px;
        color: var(--plan-title);
        background: var(--plan-bg);
        text-decoration: none;
        font-size: 0.88rem;
        font-weight: 900;
    }

    .sr-plan-full-link i:first-child {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        display: grid;
        place-items: center;
        color: #2563eb;
        background: rgba(37, 99, 235, 0.08);
        flex: 0 0 auto;
    }

    .sr-plan-full-link i:last-child,
    .sr-plan-card-chevron {
        margin-left: auto;
        color: var(--plan-title);
        opacity: 0.78;
        flex: 0 0 auto;
    }

    #card-practice-plan .sr-rec-list {
        gap: 14px;
    }

    #card-practice-plan .sr-rec-item {
        position: relative;
        isolation: isolate;
        gap: 12px;
        min-height: 122px;
        padding: 18px 38px 18px 18px;
        border-radius: 14px;
        border-color: var(--plan-border);
        background: var(--plan-bg);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }

    :root:not(.lm) #card-practice-plan .sr-rec-item {
        box-shadow: 0 10px 24px rgba(2, 6, 23, 0.18);
    }

    #card-practice-plan .sr-rec-item::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: var(--accent, #60a5fa);
    }

    #card-practice-plan .sr-rec-icon {
        width: 46px;
        height: 46px;
        border-radius: 13px;
        color: var(--accent, #60a5fa);
        background: color-mix(in srgb, var(--accent, #60a5fa) 16%, transparent);
        box-shadow: 0 10px 18px color-mix(in srgb, var(--accent, #60a5fa) 18%, transparent);
    }

    .sr-plan-task-title {
        color: var(--plan-title);
        font-size: 0.98rem;
        line-height: 1.25;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .sr-plan-action {
        color: var(--plan-muted);
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.55;
    }

    #card-practice-plan .sr-plan-step {
        color: var(--accent, #60a5fa);
        background: color-mix(in srgb, var(--accent, #60a5fa) 14%, transparent);
    }

    #card-practice-plan .sr-tag {
        padding: 5px 8px;
        font-size: 0.68rem;
        line-height: 1.1;
    }

    #card-practice-plan .sr-plan-cta {
        color: var(--accent, #60a5fa);
        font-size: 0.78rem;
        margin-top: 10px;
    }

    .sr-plan-card-chevron {
        position: absolute;
        top: 22px;
        right: 16px;
    }

    #card-practice-plan.sr-card-pad {
        padding: 16px !important;
        border-radius: 14px;
    }
    #card-practice-plan .sr-plan-header {
        gap: 10px;
        margin-bottom: 12px;
    }
    #card-practice-plan .sr-plan-header-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        font-size: 0.95rem;
    }
    #card-practice-plan .sr-plan-title {
        font-size: 0.96rem;
        line-height: 1.16;
    }
    #card-practice-plan .sr-plan-subtitle {
        margin-top: 5px;
        font-size: 0.68rem;
        line-height: 1.3;
    }
    #card-practice-plan .sr-plan-full-link {
        min-height: 38px;
        gap: 9px;
        margin-bottom: 10px;
        padding: 8px 10px;
        border-radius: 10px;
        font-size: 0.74rem;
    }
    #card-practice-plan .sr-plan-full-link i:first-child {
        width: 24px;
        height: 24px;
        border-radius: 7px;
        font-size: 0.72rem;
    }
    #card-practice-plan .sr-rec-list {
        gap: 10px;
    }
    #card-practice-plan .sr-rec-item {
        min-height: 92px;
        gap: 10px;
        padding: 12px 30px 12px 12px;
        border-radius: 12px;
    }
    #card-practice-plan .sr-rec-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        font-size: 0.9rem;
    }
    #card-practice-plan .sr-plan-top {
        gap: 7px;
        margin-bottom: 5px;
    }
    #card-practice-plan .sr-plan-step {
        padding: 3px 7px;
        font-size: 0.56rem;
    }
    #card-practice-plan .sr-plan-task-title {
        font-size: 0.78rem;
        line-height: 1.14;
    }
    #card-practice-plan .sr-plan-action {
        font-size: 0.66rem;
        line-height: 1.28;
    }
    #card-practice-plan .sr-plan-meta {
        gap: 6px;
        margin-top: 7px;
    }
    #card-practice-plan .sr-tag {
        padding: 4px 7px;
        font-size: 0.56rem;
    }
    #card-practice-plan .sr-plan-cta {
        margin-top: 7px;
        font-size: 0.64rem;
    }
    #card-practice-plan .sr-plan-card-chevron {
        top: 15px;
        right: 12px;
        font-size: 0.78rem;
    }

    .sr-polished-card {
        --polish-accent: #3b82f6;
        --polish-bg: #ffffff;
        --polish-border: rgba(226, 232, 240, 0.92);
        --polish-title: #0f172a;
        --polish-muted: #64748b;
        background: var(--polish-bg);
        border-color: var(--polish-border);
        border-radius: 18px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
    }

    :root:not(.lm) .sr-polished-card {
        --polish-bg: #151c2d;
        --polish-border: rgba(96, 165, 250, 0.16);
        --polish-title: #f8fafc;
        --polish-muted: #a8b4c7;
        box-shadow: 0 14px 30px rgba(2, 6, 23, 0.22);
    }

    .sr-dashboard {
        --dash-surface: #ffffff;
        --dash-surface-2: #f8fbff;
        --dash-border: rgba(226, 232, 240, 0.92);
        --dash-title: #0f172a;
        --dash-muted: #475569;
        --dash-muted-2: #64748b;
        --dash-shadow-card: 0 12px 28px rgba(15, 23, 42, 0.08);
    }

    :root:not(.lm) .sr-dashboard {
        --dash-surface: #151c2d;
        --dash-surface-2: #1d263a;
        --dash-border: rgba(96, 165, 250, 0.16);
        --dash-title: #f8fafc;
        --dash-muted: #d6deea;
        --dash-muted-2: #a8b4c7;
        --dash-shadow-card: 0 14px 30px rgba(2, 6, 23, 0.26);
    }

    .sr-dashboard .sr-card:not(.sr-hero-card):not(.sr-score-panel),
    .sr-dashboard .sr-polished-card,
    .sr-dashboard .sr-side-feature,
    .sr-dashboard .sr-goal-panel,
    .sr-dashboard .sr-notification-card,
    .sr-dashboard .sr-session-card,
    .sr-dashboard .sr-session-card-polished,
    .sr-dashboard .sr-recommendation-card,
    .sr-dashboard .sr-rec-item {
        background: var(--dash-surface) !important;
        border-color: var(--dash-border) !important;
        color: var(--dash-title);
        box-shadow: var(--dash-shadow-card);
    }

    .sr-dashboard :is(
        .sr-card-title,
        .sr-polished-title,
        .sr-side-title,
        .sr-plan-title,
        .sr-plan-task-title,
        .sr-session-title,
        .sr-session-card-polished .sr-session-title,
        .sr-notification-title,
        .sr-rec-title,
        .sr-goal-panel strong
    ) {
        color: var(--dash-title) !important;
    }

    .sr-dashboard :is(
        .sr-card-kicker,
        .sr-polished-subtitle,
        .sr-side-subtitle,
        .sr-plan-subtitle,
        .sr-plan-action,
        .sr-session-date,
        .sr-session-card-polished .sr-session-date,
        .sr-notification-text,
        .sr-notification-time,
        .sr-rec-description,
        .sr-empty,
        .sr-progress-score
    ) {
        color: var(--dash-muted-2) !important;
    }

    .sr-dashboard :is(
        .sr-feedback-panel,
        .sr-trend-metric,
        .sr-trend-note,
        .sr-plan-full-link,
        .sr-progress,
        .sr-empty
    ) {
        background: var(--dash-surface-2) !important;
        border-color: var(--dash-border) !important;
    }

    :root:not(.lm) .sr-dashboard table,
    :root:not(.lm) .sr-dashboard tbody,
    :root:not(.lm) .sr-dashboard tr,
    :root:not(.lm) .sr-dashboard td,
    :root:not(.lm) .sr-dashboard th {
        color: var(--dash-muted) !important;
        border-color: rgba(148, 163, 184, 0.16) !important;
    }

    :root:not(.lm) .sr-dashboard .sr-btn {
        color: #e2e8f0;
        border-color: rgba(147, 197, 253, 0.22);
        background: rgba(15, 23, 42, 0.18);
    }

    .sr-polished-header {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 18px;
    }

    .sr-polished-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        color: var(--polish-accent);
        background: color-mix(in srgb, var(--polish-accent) 14%, transparent);
        font-size: 1.25rem;
        flex: 0 0 auto;
    }

    .sr-polished-title {
        margin: 0;
        color: var(--polish-title);
        font-size: 1.18rem;
        line-height: 1.18;
        font-weight: 900;
    }

    .sr-polished-subtitle {
        margin: 6px 0 0;
        color: var(--polish-muted);
        font-size: 0.86rem;
        line-height: 1.4;
        font-weight: 600;
    }

    .sr-polished-empty {
        min-height: 220px;
        border: 1px dashed color-mix(in srgb, #3b82f6 42%, transparent);
        border-radius: 16px;
        display: grid;
        place-items: center;
        text-align: center;
        padding: 26px;
        color: var(--polish-muted);
        background: linear-gradient(180deg, color-mix(in srgb, #3b82f6 6%, transparent), transparent);
    }

    .sr-polished-empty-inner {
        display: grid;
        justify-items: center;
        gap: 14px;
        max-width: 360px;
    }

    .sr-empty-visual {
        width: 74px;
        height: 58px;
        display: grid;
        place-items: center;
        color: #3b82f6;
        font-size: 2.7rem;
        filter: drop-shadow(0 12px 18px rgba(37, 99, 235, 0.18));
    }

    .sr-polished-empty-text {
        margin: 0;
        color: var(--polish-muted);
        font-size: 1rem;
        line-height: 1.45;
        font-weight: 800;
    }

    .sr-learning-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .sr-learning-item {
        display: grid;
        grid-template-columns: 46px minmax(0, 1fr);
        gap: 14px;
        align-items: center;
        padding: 16px;
        border-radius: 14px;
        border: 1px solid var(--polish-border);
        background: var(--polish-bg);
    }

    .sr-learning-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        color: var(--accent, #3b82f6);
        background: color-mix(in srgb, var(--accent, #3b82f6) 12%, transparent);
        font-size: 1.18rem;
    }

    .sr-learning-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        margin-bottom: 12px;
    }

    .sr-learning-title {
        color: var(--polish-title);
        font-size: 0.94rem;
        line-height: 1.25;
        font-weight: 900;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sr-learning-score {
        color: var(--accent, #3b82f6);
        font-size: 1.06rem;
        line-height: 1;
        font-weight: 900;
    }

    .sr-learning-bar {
        height: 8px;
        border-radius: 999px;
        overflow: hidden;
        background: color-mix(in srgb, var(--accent, #3b82f6) 12%, #e5e7eb);
    }

    .sr-learning-bar span {
        display: block;
        height: 100%;
        width: var(--value, 0%);
        border-radius: inherit;
        background: var(--accent, #3b82f6);
    }

    .sr-feedback-panel {
        border: 1px solid color-mix(in srgb, var(--polish-accent) 24%, var(--polish-border));
        border-radius: 16px;
        padding: 18px;
        background: linear-gradient(180deg, color-mix(in srgb, var(--polish-accent) 5%, transparent), transparent);
    }

    .sr-feedback-chip-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .sr-feedback-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 10px 14px;
        color: #ea580c;
        background: rgba(245, 158, 11, 0.1);
        border: 1px solid rgba(245, 158, 11, 0.22);
        font-size: 0.82rem;
        line-height: 1.1;
        font-weight: 900;
    }

    .sr-feedback-chip i {
        font-size: 0.95rem;
        flex: 0 0 auto;
    }

    .sr-rec-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 5px 9px;
        color: #ea580c;
        background: rgba(245, 158, 11, 0.12);
        font-size: 0.66rem;
        line-height: 1;
        font-weight: 900;
        white-space: nowrap;
    }

    .sr-recommendation-card {
        position: relative;
        display: grid;
        grid-template-columns: 40px minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        min-height: 108px;
        padding: 16px 14px;
        border: 1px solid var(--polish-border);
        border-radius: 14px;
        background: var(--polish-bg);
        color: inherit;
        text-decoration: none;
        overflow: hidden;
    }

    .sr-recommendation-card::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: #8b5cf6;
    }

    .sr-recommendation-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: var(--accent, #8b5cf6);
        background: color-mix(in srgb, var(--accent, #8b5cf6) 14%, transparent);
        box-shadow: 0 10px 18px color-mix(in srgb, var(--accent, #8b5cf6) 14%, transparent);
    }

    .sr-recommendation-title {
        color: var(--polish-title);
        font-size: 0.86rem;
        line-height: 1.35;
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .sr-recommendation-reason {
        margin-top: 6px;
        color: var(--polish-muted);
        font-size: 0.72rem;
        line-height: 1.45;
        font-weight: 600;
    }

    .sr-recommendation-next {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        color: #8b5cf6;
        background: rgba(139, 92, 246, 0.1);
    }

    .sr-section-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 14px;
    }

    .sr-section-actions form {
        margin: 0;
    }

    .sr-section-action {
        width: min(100%, 180px);
        min-height: 42px;
        border-radius: 10px;
        justify-content: center;
        font-size: 0.76rem;
        font-weight: 900;
    }

    .sr-section-action.danger {
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.28);
        background: rgba(239, 68, 68, 0.02);
    }

    .sr-session-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .sr-session-card-polished {
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr) 78px 76px 34px;
        gap: 10px;
        align-items: center;
        padding: 12px;
        border: 1px solid var(--polish-border);
        border-radius: 12px;
        background: var(--polish-bg);
    }

    .sr-session-icon {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        display: grid;
        place-items: center;
        color: #2563eb;
        background: rgba(37, 99, 235, 0.1);
        flex: 0 0 auto;
    }

    .sr-session-card-polished .sr-session-title {
        color: var(--polish-title);
        font-size: 0.8rem;
        line-height: 1.2;
        font-weight: 900;
    }

    .sr-session-card-polished .sr-session-date {
        margin-top: 3px;
        color: var(--polish-muted);
        font-size: 0.62rem;
        font-weight: 700;
    }

    .sr-session-score-stack {
        display: grid;
        gap: 7px;
        min-width: 0;
    }

    .sr-session-score-pill {
        width: max-content;
        min-width: 42px;
        justify-self: start;
        padding: 5px 8px;
        border-radius: 8px;
        color: var(--score-color, #ef4444);
        background: color-mix(in srgb, var(--score-color, #ef4444) 12%, transparent);
        font-size: 0.72rem;
        line-height: 1;
        font-weight: 900;
        text-align: center;
    }

    .sr-session-score-bar {
        height: 5px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(37, 99, 235, 0.12);
    }

    .sr-session-score-bar span {
        display: block;
        width: var(--score-value, 0%);
        height: 100%;
        border-radius: inherit;
        background: #2563eb;
    }

    .sr-session-review-btn {
        min-height: 34px;
        padding: 7px 12px;
        border-radius: 9px;
        font-size: 0.68rem;
    }

    .sr-session-delete-btn {
        width: 34px;
        min-height: 34px;
        padding: 0;
        border-radius: 9px;
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.32);
    }

    .sr-side-feature {
        --side-accent: #3b82f6;
        --side-bg: #ffffff;
        --side-border: rgba(226, 232, 240, 0.92);
        --side-title: #0f172a;
        --side-muted: #64748b;
        background: var(--side-bg);
        border-color: var(--side-border);
        border-radius: 18px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
    }

    :root:not(.lm) .sr-side-feature {
        --side-bg: #151c2d;
        --side-border: rgba(96, 165, 250, 0.16);
        --side-title: #f8fafc;
        --side-muted: #a8b4c7;
        box-shadow: 0 14px 30px rgba(2, 6, 23, 0.22);
    }

    .sr-side-feature-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
    }

    .sr-side-title-row {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }

    .sr-side-icon {
        width: 42px;
        height: 42px;
        border-radius: 13px;
        display: grid;
        place-items: center;
        color: var(--side-accent);
        background: color-mix(in srgb, var(--side-accent) 13%, transparent);
        font-size: 1.1rem;
        flex: 0 0 auto;
    }

    .sr-side-title {
        margin: 0;
        color: var(--side-title);
        font-size: 1.08rem;
        line-height: 1.2;
        font-weight: 900;
    }

    .sr-side-subtitle {
        margin: 5px 0 0;
        color: var(--side-muted);
        font-size: 0.82rem;
        line-height: 1.35;
        font-weight: 600;
    }

    .sr-side-detail-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 38px;
        padding: 8px 13px;
        border-radius: 999px;
        color: #2563eb;
        background: rgba(37, 99, 235, 0.08);
        border: 1px solid rgba(37, 99, 235, 0.12);
        text-decoration: none;
        font-size: 0.78rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .sr-radar-box {
        height: 190px;
        padding: 0;
    }

    #card-skill-radar {
        padding: 16px !important;
    }

    #card-skill-radar .sr-side-feature-header {
        gap: 10px;
        margin-bottom: 10px;
    }

    #card-skill-radar .sr-side-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        font-size: 0.95rem;
    }

    #card-skill-radar .sr-side-title {
        font-size: 0.96rem;
    }

    #card-skill-radar .sr-side-subtitle {
        font-size: 0.68rem;
        line-height: 1.28;
    }

    #card-skill-radar .sr-side-detail-btn {
        min-height: 34px;
        padding: 7px 10px;
        font-size: 0.68rem;
    }

    .sr-challenge-feature {
        --side-accent: #2563eb;
        background:
            radial-gradient(circle at 100% 0%, rgba(14, 165, 233, 0.16), transparent 34%),
            linear-gradient(135deg, rgba(37, 99, 235, 0.06), rgba(14, 165, 233, 0.1));
        border-color: rgba(37, 99, 235, 0.16);
    }

    :root:not(.lm) .sr-challenge-feature {
        background:
            radial-gradient(circle at 100% 0%, rgba(14, 165, 233, 0.16), transparent 34%),
            linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(14, 165, 233, 0.08)),
            #151c2d;
    }

    .sr-challenge-star {
        margin-left: auto;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: #2563eb;
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(37, 99, 235, 0.12);
        flex: 0 0 auto;
    }

    .sr-challenge-title {
        margin: 12px 0 8px;
        color: var(--side-title);
        font-size: 1.18rem;
        line-height: 1.24;
        font-weight: 900;
    }

    .sr-challenge-copy {
        margin: 0;
        color: var(--side-muted);
        font-size: 0.9rem;
        line-height: 1.45;
        font-weight: 600;
    }

    .sr-reward-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 22px 0;
    }

    .sr-reward-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 10px 14px;
        font-size: 0.9rem;
        font-weight: 900;
    }

    .sr-reward-pill.xp {
        color: #ea580c;
        background: rgba(245, 158, 11, 0.12);
    }

    .sr-reward-pill.streak {
        color: #16a34a;
        background: rgba(34, 197, 94, 0.12);
    }

    .sr-challenge-cta {
        min-height: 54px;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 900;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.22);
    }

    #card-daily-challenge {
        padding: 16px !important;
    }
    #card-daily-challenge .sr-side-feature-header {
        gap: 10px;
    }
    #card-daily-challenge .sr-side-icon,
    #card-daily-challenge .sr-challenge-star {
        width: 36px;
        height: 36px;
        border-radius: 11px;
        font-size: 0.9rem;
    }
    #card-daily-challenge .sr-side-title {
        font-size: 0.96rem;
    }
    #card-daily-challenge .sr-challenge-title {
        margin: 10px 0 6px;
        font-size: 0.9rem;
        line-height: 1.18;
    }
    #card-daily-challenge .sr-challenge-copy {
        font-size: 0.72rem;
        line-height: 1.35;
    }
    #card-daily-challenge .sr-reward-row {
        gap: 8px;
        margin: 14px 0;
    }
    #card-daily-challenge .sr-reward-pill {
        gap: 6px;
        padding: 7px 10px;
        font-size: 0.68rem;
    }
    #card-daily-challenge .sr-challenge-cta {
        width: min(100%, 220px) !important;
        min-height: 38px;
        margin: 0 auto;
        border-radius: 10px;
        gap: 6px;
        font-size: 0.72rem;
        box-shadow: 0 8px 16px rgba(37, 99, 235, 0.18);
    }
    #card-daily-challenge > .sr-challenge-cta {
        display: flex;
        justify-self: center;
        align-self: center;
    }

    .sr-goal-panel {
        border: 1px solid var(--side-border);
        border-radius: 14px;
        background: var(--side-bg);
        overflow: hidden;
    }

    .sr-goal-main {
        padding: 16px;
    }

    .sr-goal-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 14px;
    }

    .sr-goal-title {
        color: var(--side-title);
        font-size: 1.05rem;
        line-height: 1.2;
        font-weight: 900;
    }

    .sr-goal-percent {
        color: #22c55e;
        font-size: 1.26rem;
        line-height: 1;
        font-weight: 900;
    }

    .sr-goal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-top: 1px solid var(--side-border);
    }

    .sr-goal-note {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #2563eb;
        font-size: 0.82rem;
        font-weight: 900;
    }

    .sr-achievement-showcase {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .sr-achievement-tile {
        display: grid;
        justify-items: center;
        align-content: center;
        gap: 12px;
        min-height: 158px;
        padding: 16px 10px;
        border-radius: 16px;
        border: 1px solid color-mix(in srgb, var(--accent, #f59e0b) 26%, transparent);
        background:
            radial-gradient(circle at 50% 24%, color-mix(in srgb, var(--accent, #f59e0b) 14%, transparent), transparent 34%),
            color-mix(in srgb, var(--accent, #f59e0b) 7%, var(--side-bg));
        text-align: center;
    }

    .sr-achievement-tile-icon {
        display: grid;
        place-items: center;
        min-height: 50px;
        color: var(--accent, #f59e0b);
        font-size: 2.6rem;
        filter: drop-shadow(0 10px 14px color-mix(in srgb, var(--accent, #f59e0b) 18%, transparent));
    }

    .sr-achievement-tile-title {
        color: var(--side-title);
        font-size: 0.94rem;
        line-height: 1.15;
        font-weight: 900;
    }

    .sr-achievement-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 11px;
        color: var(--accent, #f59e0b);
        background: color-mix(in srgb, var(--accent, #f59e0b) 13%, transparent);
        font-size: 0.7rem;
        line-height: 1;
        font-weight: 900;
    }

    @media (min-width: 992px) {
        .sr-side-stack .sr-achievement-showcase {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sr-side-stack .sr-achievement-tile {
            min-height: 132px;
            padding: 14px 10px;
            gap: 9px;
        }

        .sr-side-stack .sr-achievement-tile-icon {
            min-height: 38px;
            font-size: 2rem;
        }

        .sr-side-stack .sr-achievement-tile-title {
            font-size: 0.86rem;
            line-height: 1.18;
            overflow-wrap: anywhere;
        }

        .sr-side-stack .sr-achievement-status {
            padding: 7px 10px;
            font-size: 0.68rem;
            white-space: nowrap;
        }
    }

    .sr-notification-list-polished {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .sr-notification-card {
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr) auto;
        gap: 14px;
        align-items: start;
        min-height: 94px;
        padding: 16px;
        border-radius: 16px;
        border: 1px solid var(--side-border);
        background: var(--side-bg);
    }

    .sr-notification-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        color: #2563eb;
        background: rgba(37, 99, 235, 0.09);
        font-size: 1.18rem;
    }

    .sr-notification-title {
        color: var(--side-title);
        font-size: 0.96rem;
        line-height: 1.25;
        font-weight: 900;
    }

    .sr-notification-message {
        margin-top: 6px;
        color: var(--side-muted);
        font-size: 0.8rem;
        line-height: 1.45;
        font-weight: 600;
    }

    .sr-notification-meta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--side-muted);
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .sr-notification-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #2563eb;
        flex: 0 0 auto;
    }

    .sr-sessions-mobile {
        display: none;
    }

    .sr-session-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 12px;
        background: var(--sf2, var(--bg3));
    }

    .sr-session-meta {
        min-width: 0;
    }

    .sr-session-title {
        color: var(--tx);
        font-size: 0.9rem;
        font-weight: 800;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sr-session-date {
        color: var(--tx3);
        font-size: 0.76rem;
        margin-top: 2px;
    }

    .sr-score-mini {
        color: var(--tx);
        font-weight: 900;
        font-size: 0.95rem;
    }

    .sr-achievement-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .sr-achievement {
        border: 1px solid var(--bd);
        border-radius: 14px;
        background: var(--sf2, var(--bg3));
        padding: 12px;
        min-height: 92px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        text-align: center;
        color: var(--tx2);
    }

    .sr-achievement.locked {
        opacity: .52;
    }

    .sr-achievement i {
        color: var(--accent, #60a5fa);
        font-size: 1.25rem;
    }

    .sr-achievement span {
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--tx);
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    .sr-challenge-card {
        background:
            linear-gradient(135deg, rgba(59, 130, 246, 0.13), rgba(6, 182, 212, 0.05)),
            var(--sf);
        border-color: rgba(59, 130, 246, 0.22);
    }

    .sr-goal-box {
        border: 1px solid var(--bd);
        border-radius: 14px;
        background: var(--sf2, var(--bg3));
        padding: 14px;
    }

    .custom-table th,
    .custom-table td {
        white-space: nowrap;
    }

    @media (max-width: 1199px) {
        .sr-dashboard-shell {
            grid-template-columns: 1fr;
        }

        .sr-side-stack {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 991px) {
        .sr-summary-grid {
            grid-template-columns: 1fr;
        }

        .sr-welcome-stack {
            display: contents;
        }

        .sr-hero-card {
            order: 1;
        }

        .sr-score-panel {
            max-width: none;
            order: 2;
        }

        .sr-mobile-readiness-row {
            order: 2;
        }

        .sr-stats-desktop {
            order: 3;
        }

        .stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
    }

    @media (max-width: 767px) {
        #mob-content {
            padding-bottom: calc(var(--mob-nav-h, 64px) + var(--mob-safe-bottom, 0px) + 8px);
        }

        #mob-content > .db-content {
            padding: 10px 12px !important;
        }

        .sr-dashboard {
            --dash-section-gap: 12px;
            --dash-card-radius: 14px;
            --dash-card-pad: 14px;
            gap: 0;
            padding: 6px 0 8px !important;
        }

        #dashboard .db-content .db-section.active.sr-dashboard {
            gap: 0;
            padding: 6px 0 8px !important;
        }

        #mob-content .sr-dashboard > * + *,
        .sr-dashboard > * + * {
            margin-top: var(--dash-section-gap) !important;
        }

        .sr-dashboard-shell,
        .sr-summary-grid,
        .sr-main-stack,
        .sr-side-stack {
            gap: 0;
        }

        .sr-side-stack,
        .sr-two-col {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .sr-summary-grid > * + *,
        .sr-dashboard-shell > * + *,
        .sr-main-stack > * + *,
        .sr-side-stack > * + *,
        .sr-two-col > * + * {
            margin-top: var(--dash-section-gap) !important;
        }

        .stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
            margin: 0;
        }

        .sr-card {
            border-radius: var(--dash-card-radius);
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        .sr-hero-card {
            border-radius: var(--dash-card-radius);
            margin-bottom: 0;
            min-height: 104px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .sr-stats-desktop {
            display: none;
        }

        .sr-mobile-stat-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-auto-rows: 1fr;
            align-items: stretch;
            gap: 10px;
            padding: 0;
            margin: var(--dash-section-gap) 0;
        }

        #mob-content .sr-mobile-stat-grid {
            margin-top: var(--dash-section-gap) !important;
            margin-bottom: var(--dash-section-gap) !important;
        }

        #mob-content .sr-mobile-stat-grid + .sr-stats-desktop + .sr-dashboard-shell,
        #mob-content .sr-mobile-stat-grid + .sr-dashboard-shell,
        .sr-mobile-stat-grid + .sr-stats-desktop + .sr-dashboard-shell,
        .sr-mobile-stat-grid + .sr-dashboard-shell {
            margin-top: 0 !important;
        }

        .sr-card-pad,
        .sr-hero-inner {
            padding: var(--dash-card-pad);
        }

        .sr-hero-inner {
            min-height: 104px;
            padding: 12px 112px 12px 14px;
        }

        .sr-welcome-art {
            right: -8px;
            bottom: -3px;
            width: clamp(108px, 34vw, 138px);
            opacity: 0.96;
        }

        .sr-robot-hand-message {
            top: -18px;
            left: -94px;
            width: 142px;
        }

        .sr-robot-message-text {
            gap: 4px;
            font-size: 22px;
        }

        .sr-robot-message-text strong {
            max-width: 82px;
        }

        .sr-hero-card .sr-user-row {
            align-items: center;
            gap: 9px;
        }

        .sr-welcome-copy {
            flex: 1 1 auto;
            max-width: 100%;
        }

        .sr-hero-card .sr-title {
            font-size: 1.12rem;
            line-height: 1.22;
            margin-bottom: 4px;
            overflow-wrap: anywhere;
        }

        .sr-hero-card .sr-subtitle {
            font-size: 0.74rem;
            line-height: 1.38;
            max-width: 100%;
        }

        .sr-score-panel {
            width: 100%;
            max-width: none;
            padding: 10px;
            border-radius: var(--dash-card-radius);
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            min-height: 0 !important;
        }

        .sr-mobile-readiness-row {
            display: grid;
            grid-template-columns: minmax(0, 0.54fr) minmax(0, 0.46fr);
            gap: 8px;
            align-items: stretch;
            min-width: 0;
        }

        .sr-mobile-readiness-row > .sr-score-panel,
        .sr-mobile-readiness-row > .sr-mobile-stat-grid {
            min-width: 0;
            margin: 0 !important;
        }

        .sr-score-top {
            gap: 8px;
            margin-bottom: 10px;
        }

        .sr-score-layout {
            grid-template-columns: minmax(88px, 0.78fr) minmax(116px, 1fr);
            gap: 8px;
            align-items: center;
        }

        .sr-readiness-ring {
            --ring-size: clamp(86px, 28vw, 104px);
        }

        .sr-score-value {
            font-size: clamp(1.72rem, 7vw, 2.1rem);
        }

        .sr-score-value span {
            font-size: 0.78rem;
        }

        .sr-ring-label {
            font-size: 0.56rem;
        }

        .sr-score-meta {
            gap: 8px;
            margin-top: 0;
        }

        .sr-score-meta-item {
            min-height: 54px;
            padding: 9px 10px;
            border-radius: 12px;
        }

        .sr-score-icon {
            width: 34px;
            height: 34px;
            flex-basis: 34px;
            border-radius: 12px;
        }

        .sr-meta-label {
            font-size: 0.66rem;
            margin-bottom: 5px;
        }

        .sr-meta-value {
            font-size: 1rem;
        }

        .sr-score-note {
            margin: 10px -10px -10px;
            padding: 8px 11px;
            font-size: 0.64rem;
        }

        .sr-stat-card {
            min-height: 112px;
            padding: 10px;
            border-radius: 14px;
            gap: 6px;
            justify-content: flex-start;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .sr-stat-head {
            min-height: 28px;
        }

        .sr-stat-card > div:last-child {
            margin-top: 0;
        }

        .sr-stat-icon {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            font-size: 0.66rem;
        }

        .sr-stat-card .sr-chip {
            padding: 5px 8px;
            font-size: 0.62rem;
            line-height: 1.1;
        }

        .sr-stat-value {
            margin-top: 14px;
            font-size: 1.02rem;
            line-height: 1;
        }

        .sr-stat-value span {
            font-size: 0.62rem !important;
        }

        .sr-stat-label {
            margin-top: 4px;
            font-size: 0.56rem;
            line-height: 1.08;
        }

        .sr-mobile-stat-grid .sr-stat-card {
            min-width: 0;
            min-height: 112px;
            height: 100%;
            padding: 10px;
            border-radius: 14px;
            gap: 6px;
            justify-content: flex-start;
            box-shadow: var(--shadow-soft, 0 8px 22px rgba(0,0,0,.1));
        }

        .sr-mobile-stat-grid .sr-stat-head {
            gap: 6px;
            min-height: 28px;
            min-width: 0;
        }

        .sr-mobile-stat-grid .sr-stat-icon {
            width: 28px;
            height: 28px;
            border-radius: 9px;
            font-size: 0.66rem;
        }

        .sr-mobile-stat-grid .sr-chip {
            max-width: calc(100% - 40px);
            padding: 5px 7px;
            font-size: 0.58rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sr-mobile-stat-grid .sr-stat-card > div:last-child {
            min-width: 0;
            margin-top: 0;
        }

        .sr-mobile-stat-grid .sr-stat-value {
            font-size: 1.02rem;
            line-height: 1;
            margin-top: 14px;
        }

        .sr-mobile-stat-grid .sr-stat-label {
            font-size: 0.56rem;
            font-weight: 900;
            line-height: 1.08;
            white-space: nowrap;
            overflow-wrap: anywhere;
        }

        .sr-mobile-stat-grid .sr-stat-body {
            padding-right: 32px;
        }

        .sr-mobile-stat-grid .sr-stat-meter {
            width: 30px;
            height: 30px;
            bottom: 0;
        }

        .sr-mobile-stat-grid .sr-stat-meter::before {
            inset: 4px;
        }

        .sr-mobile-stat-grid .sr-stat-meter > * {
            font-size: 0.54rem;
        }

        .chart-container-mobile,
        .sr-chart-box {
            height: 218px;
        }

        .sr-btn,
        .sr-chip,
        .sr-status-pill {
            max-width: 100%;
        }

        .sr-btn {
            min-height: 42px;
            padding: 9px 12px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            line-height: 1.2;
            text-align: center;
            touch-action: manipulation;
        }

        .sr-btn i {
            margin: 0 !important;
            flex: 0 0 auto;
        }

        .sr-chip,
        .sr-status-pill,
        .sr-tag {
            white-space: normal;
            overflow-wrap: anywhere;
            line-height: 1.2;
        }

        .sr-card-header {
            align-items: stretch;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 12px;
        }

        .sr-card-title {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.88rem;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .sr-card-title i {
            margin: 0 !important;
            flex: 0 0 auto;
        }

        .sr-card-kicker,
        .sr-subtitle {
            line-height: 1.4;
        }

        .sr-empty {
            min-height: 128px;
            padding: 16px;
        }

        .sr-rec-item,
        .sr-notification-item,
        .sr-module-item,
        .sr-session-card,
        .sr-insight-box,
        .sr-goal-box {
            border-radius: 12px;
            padding: 11px;
        }

        .sr-rec-item,
        .sr-module-item {
            gap: 10px;
        }

        .sr-rec-icon,
        .sr-list-icon {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            font-size: 0.88rem;
            flex: 0 0 34px;
        }

        .sr-session-card {
            align-items: stretch;
            flex-direction: column;
            gap: 10px;
            background: var(--bg3);
            border: 1px solid var(--bd);
        }

        .sr-session-actions {
            display: grid !important;
            grid-template-columns: auto minmax(0, 1fr) 40px;
            justify-content: stretch;
            flex-wrap: nowrap;
            width: 100%;
            gap: 8px !important;
        }

        .sr-session-actions .sr-btn-primary {
            width: 100%;
            min-height: 38px;
        }

        .sr-session-actions form,
        .sr-session-actions form .sr-btn {
            width: 100%;
        }

        #card-practice-plan.sr-card-pad {
            padding: 12px;
        }

        .sr-plan-header {
            gap: 10px;
            margin-bottom: 12px;
        }

        .sr-plan-header-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            font-size: 0.86rem;
        }

        .sr-plan-title {
            font-size: 0.96rem;
        }

        .sr-plan-subtitle {
            margin-top: 5px;
            font-size: 0.68rem;
            line-height: 1.42;
        }

        .sr-plan-full-link {
            min-height: 38px;
            margin-bottom: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 0.72rem;
        }

        .sr-plan-full-link i:first-child {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            font-size: 0.72rem;
        }

        #card-practice-plan .sr-rec-list {
            gap: 10px;
        }

        #card-practice-plan .sr-rec-item {
            min-height: 92px;
            padding: 12px 28px 12px 12px;
            border-radius: 12px;
            gap: 10px;
        }

        #card-practice-plan .sr-rec-icon {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            font-size: 0.82rem;
        }

        .sr-plan-task-title {
            font-size: 0.76rem;
        }

        .sr-plan-action {
            font-size: 0.64rem;
            line-height: 1.28;
        }

        #card-practice-plan .sr-plan-step,
        #card-practice-plan .sr-tag {
            font-size: 0.58rem;
            padding: 4px 7px;
        }

        #card-practice-plan .sr-plan-meta {
            gap: 5px;
            margin-top: 7px;
        }

        #card-practice-plan .sr-plan-cta {
            font-size: 0.62rem;
            margin-top: 7px;
        }

        .sr-plan-card-chevron {
            top: 18px;
            right: 12px;
            font-size: 0.72rem;
        }

        .sr-polished-card.sr-card-pad {
            padding: 14px;
        }

        .sr-polished-header {
            gap: 10px;
            margin-bottom: 14px;
        }

        .sr-polished-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            font-size: 1rem;
        }

        .sr-polished-title {
            font-size: 0.98rem;
        }

        .sr-polished-subtitle {
            margin-top: 5px;
            font-size: 0.72rem;
            line-height: 1.35;
        }

        .sr-polished-empty {
            min-height: 170px;
            border-radius: 14px;
            padding: 22px 16px;
        }

        .sr-empty-visual {
            width: 54px;
            height: 44px;
            font-size: 2rem;
        }

        .sr-polished-empty-text {
            font-size: 0.86rem;
            line-height: 1.42;
        }

        .sr-learning-list {
            gap: 10px;
        }

        .sr-learning-item {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 11px;
            padding: 12px;
            border-radius: 12px;
        }

        .sr-learning-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 0.98rem;
        }

        .sr-learning-top {
            gap: 8px;
            margin-bottom: 9px;
        }

        .sr-learning-title {
            font-size: 0.76rem;
        }

        .sr-learning-score {
            font-size: 0.9rem;
        }

        .sr-learning-bar {
            height: 6px;
        }

        .sr-feedback-panel {
            padding: 14px;
            border-radius: 14px;
        }

        .sr-feedback-chip-list {
            gap: 8px;
        }

        .sr-feedback-chip {
            padding: 8px 10px;
            font-size: 0.68rem;
            gap: 6px;
        }

        .sr-rec-badge {
            padding: 4px 7px;
            font-size: 0.56rem;
        }

        .sr-recommendation-card {
            grid-template-columns: 34px minmax(0, 1fr) 30px;
            gap: 10px;
            min-height: 96px;
            padding: 13px 11px;
            border-radius: 12px;
        }

        .sr-recommendation-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            font-size: 0.82rem;
        }

        .sr-recommendation-title {
            font-size: 0.72rem;
            line-height: 1.35;
        }

        .sr-recommendation-reason {
            margin-top: 5px;
            font-size: 0.62rem;
            line-height: 1.4;
        }

        .sr-recommendation-next {
            width: 30px;
            height: 30px;
            font-size: 0.72rem;
        }

        .sr-section-actions {
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .sr-section-action {
            width: min(100%, 160px);
            min-height: 36px;
            border-radius: 9px;
            font-size: 0.62rem;
        }

        .sr-session-card-polished {
            grid-template-columns: 30px minmax(0, 1fr) 76px 68px 30px;
            gap: 8px;
            padding: 10px;
            border-radius: 11px;
        }

        .sr-session-icon {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            font-size: 0.78rem;
        }

        .sr-session-card-polished .sr-session-title {
            font-size: 0.72rem;
        }

        .sr-session-card-polished .sr-session-date {
            font-size: 0.56rem;
        }

        .sr-session-score-pill {
            min-width: 38px;
            padding: 5px 7px;
            font-size: 0.62rem;
        }

        .sr-session-review-btn {
            min-height: 32px;
            padding: 6px 9px;
            font-size: 0.62rem;
        }

        .sr-session-delete-btn {
            width: 30px;
            min-height: 30px;
            border-radius: 8px;
            font-size: 0.72rem;
        }

        .sr-side-feature.sr-card-pad {
            padding: 14px;
        }

        .sr-side-feature-header {
            gap: 10px;
            margin-bottom: 14px;
        }

        .sr-side-title-row {
            gap: 10px;
        }

        .sr-side-icon {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            font-size: 0.94rem;
        }

        .sr-side-title {
            font-size: 0.96rem;
        }

        .sr-side-subtitle {
            font-size: 0.72rem;
        }

        .sr-side-detail-btn {
            min-height: 34px;
            padding: 7px 10px;
            font-size: 0.68rem;
        }

        .sr-radar-box {
            height: 180px;
        }

        #card-skill-radar {
            padding: 12px !important;
        }

        #card-skill-radar .sr-side-feature-header {
            margin-bottom: 8px;
        }

        .sr-challenge-star {
            width: 34px;
            height: 34px;
            border-radius: 11px;
        }

        .sr-challenge-title {
            margin: 10px 0 7px;
            font-size: 1rem;
        }

        .sr-challenge-copy {
            font-size: 0.78rem;
        }

        .sr-reward-row {
            gap: 8px;
            margin: 16px 0;
        }

        .sr-reward-pill {
            padding: 8px 11px;
            font-size: 0.76rem;
        }

        .sr-challenge-cta {
            min-height: 46px;
            border-radius: 11px;
            font-size: 0.86rem;
        }

        #card-daily-challenge {
            padding: 12px !important;
        }
        #card-daily-challenge .sr-challenge-title {
            margin: 8px 0 5px;
            font-size: 0.82rem;
        }
        #card-daily-challenge .sr-challenge-copy {
            font-size: 0.68rem;
        }
        #card-daily-challenge .sr-reward-row {
            gap: 7px;
            margin: 12px 0;
        }
        #card-daily-challenge .sr-reward-pill {
            padding: 6px 9px;
            font-size: 0.62rem;
        }
        #card-daily-challenge .sr-challenge-cta {
            min-height: 34px;
            width: min(100%, 190px) !important;
            font-size: 0.66rem;
            margin-left: auto;
            margin-right: auto;
        }

        .sr-goal-main {
            padding: 13px;
        }

        .sr-goal-title {
            font-size: 0.94rem;
        }

        .sr-goal-percent {
            font-size: 1.08rem;
        }

        .sr-goal-footer {
            padding: 12px 13px;
        }

        .sr-goal-note {
            font-size: 0.7rem;
        }

        .sr-achievement-showcase {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .sr-achievement-tile {
            min-height: 116px;
            padding: 10px 5px;
            border-radius: 13px;
            gap: 8px;
        }

        .sr-achievement-tile-icon {
            min-height: 34px;
            font-size: 1.7rem;
        }

        .sr-achievement-tile-title {
            font-size: 0.66rem;
            line-height: 1.15;
        }

        .sr-achievement-status {
            padding: 5px 7px;
            font-size: 0.52rem;
            gap: 4px;
        }

        .sr-notification-list-polished {
            gap: 10px;
        }

        .sr-notification-card {
            grid-template-columns: 38px minmax(0, 1fr) auto;
            gap: 10px;
            min-height: 82px;
            padding: 12px;
            border-radius: 13px;
        }

        .sr-notification-card-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            font-size: 0.95rem;
        }

        .sr-notification-title {
            font-size: 0.78rem;
        }

        .sr-notification-message {
            margin-top: 5px;
            font-size: 0.68rem;
        }

        .sr-notification-meta {
            gap: 6px;
            font-size: 0.62rem;
        }

        .sr-notification-dot {
            width: 8px;
            height: 8px;
        }

        .sr-score-mini {
            min-width: 48px;
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(96, 165, 250, 0.1);
            font-weight: 900;
        }

        #card-recent-sessions .sr-card-header > .d-flex {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px !important;
            width: 100%;
        }

        #card-recent-sessions .sr-card-header > .sr-recent-actions .sr-btn:only-child {
            grid-column: 1 / -1;
            justify-self: center;
            width: min(180px, 100%);
         }

        #card-recent-sessions .sr-card-header form,
        #card-recent-sessions .sr-card-header .sr-btn {
            width: 100%;
        }

        #card-progress-chart.sr-card-pad {
            padding: 14px;
        }

        .sr-trend-header {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .sr-trend-title-row {
            gap: 10px;
        }

        .sr-trend-icon {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            font-size: 0.94rem;
        }

        .sr-trend-title {
            font-size: 1.08rem;
        }

        .sr-trend-subtitle {
            margin-top: 9px;
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .sr-trend-actions {
            align-items: stretch;
            justify-content: flex-start !important;
            width: 100%;
        }

        .sr-trend-detail-btn,
        .sr-trend-filter {
            min-height: 36px;
            padding: 8px 12px;
            font-size: 0.72rem;
            max-width: 100%;
        }

        .sr-trend-detail-btn {
            width: auto;
        }

        .sr-trend-filter {
            width: auto;
            padding-right: 34px;
            background-position: calc(100% - 17px) 50%, calc(100% - 12px) 50%;
        }

        .sr-trend-actions.justify-content-between {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            align-items: stretch;
        }

        .sr-trend-actions.justify-content-between .sr-trend-detail-btn,
        .sr-trend-actions.justify-content-between .sr-trend-filter {
            width: 100%;
            min-width: 0;
        }

        .sr-trend-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            min-width: 0;
            width: 100%;
            margin-top: 0;
        }

        .sr-trend-metric {
            min-height: 50px;
            padding: 7px 8px;
            border-radius: 10px;
            min-width: 0;
            align-items: center;
            flex-direction: row;
            gap: 8px;
        }

        .sr-trend-metric > div:last-child {
            min-width: 0;
        }

        .sr-trend-metric-icon {
            width: 26px;
            height: 26px;
            font-size: 0.68rem;
        }

        .sr-trend-metric-label {
            font-size: 0.58rem;
            margin-bottom: 2px;
        }

        .sr-trend-metric-value {
            font-size: 0.78rem;
            overflow-wrap: anywhere;
        }

        .sr-trend-metric-value strong {
            font-size: 1rem;
        }

        #card-progress-chart .sr-chart-box {
            height: 214px;
            margin-top: 12px;
        }

        .sr-trend-note {
            gap: 9px;
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            font-size: 0.72rem;
            line-height: 1.3;
        }

        .sr-trend-note i {
            width: 30px;
            height: 30px;
        }

        #card-progress-chart .sr-trend-header {
            margin-bottom: 8px;
        }
        #card-progress-chart .sr-trend-actions {
            gap: 8px;
            margin-bottom: 8px !important;
        }
        #card-progress-chart .sr-trend-metrics {
            margin-top: 8px;
        }
        #card-progress-chart .sr-chart-box {
            height: 168px;
            margin-top: 10px;
        }
        #card-progress-chart .sr-trend-note {
            margin-top: 10px;
        }

        .sr-sessions-table {
            display: none;
        }

        .sr-sessions-mobile {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

    }

    @media (max-width: 420px) {
        .sr-hero-inner {
            min-height: 98px;
            padding: 12px 92px 12px 12px;
        }

        .sr-welcome-art {
            right: -14px;
            bottom: -3px;
            width: clamp(100px, 32vw, 124px);
            opacity: 0.94;
        }

        .sr-robot-hand-message {
            top: -16px;
            left: -86px;
            width: 132px;
        }

        .sr-robot-message-text {
            font-size: 21px;
        }

        .sr-robot-message-text strong {
            max-width: 76px;
        }

        .stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 10px !important;
        }

        .sr-mobile-stat-grid {
            gap: 10px;
        }

        .sr-score-layout {
            grid-template-columns: minmax(84px, 0.74fr) minmax(112px, 1fr);
            gap: 8px;
        }

        .sr-readiness-ring {
            --ring-size: clamp(82px, 27vw, 98px);
        }

        .sr-score-meta-item {
            min-height: 50px;
            padding: 8px;
        }

        .sr-score-icon {
            width: 30px;
            height: 30px;
            flex-basis: 30px;
        }

        .sr-achievement-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .sr-achievement {
            min-height: 82px;
            padding: 10px 6px;
        }

        .sr-achievement span {
            font-size: 0.66rem;
        }
    }

    @media (max-width: 360px) {
        .sr-hero-inner {
            min-height: 96px;
            padding: 12px 78px 12px 12px;
        }

        .sr-welcome-art {
            right: -18px;
            bottom: 0;
            width: 92px;
            opacity: 0.9;
        }

        .sr-robot-hand-message {
            top: -13px;
            left: -76px;
            width: 118px;
        }

        .sr-robot-message-text {
            font-size: 19px;
        }

        .sr-robot-message-text strong {
            max-width: 68px;
        }

        .sr-hero-card .sr-title {
            font-size: 0.98rem;
        }

        .sr-hero-card .sr-subtitle {
            font-size: 0.66rem;
            line-height: 1.32;
        }

        .sr-score-top {
            gap: 6px;
        }

        .sr-score-top .sr-status-pill,
        .sr-score-top .sr-chip {
            padding: 5px 7px;
            font-size: 0.6rem;
        }

        .sr-score-layout {
            grid-template-columns: 1fr;
            justify-items: stretch;
        }

        .sr-readiness-ring {
            --ring-size: 104px;
        }

        .sr-score-meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
        }

        .sr-score-meta-item {
            align-items: flex-start;
            min-height: 76px;
            flex-direction: column;
            gap: 8px;
        }

        .sr-score-icon {
            order: -1;
        }

        .sr-score-note {
            align-items: flex-start;
            line-height: 1.25;
        }

        #mob-content > .db-content {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .sr-stat-card {
            min-height: 104px;
            padding: 9px;
        }

        .sr-stat-card .sr-chip {
            padding: 4px 6px;
            font-size: 0.54rem;
        }

        .sr-mobile-stat-grid {
            gap: 10px;
            padding: 0;
        }

        .sr-mobile-stat-grid .sr-stat-card {
            min-height: 104px;
            padding: 9px;
        }

        .sr-mobile-stat-grid .sr-chip {
            padding: 4px 5px;
            font-size: 0.52rem;
        }

        .sr-mobile-stat-grid .sr-stat-icon {
            width: 24px;
            height: 24px;
            border-radius: 8px;
            font-size: 0.56rem;
        }

        .sr-mobile-stat-grid .sr-stat-value {
            font-size: 0.98rem;
            margin-top: 12px;
        }

        .sr-mobile-stat-grid .sr-stat-label {
            font-size: 0.52rem;
            line-height: 1.12;
            white-space: nowrap;
        }

        .sr-mobile-stat-grid .sr-stat-body {
            padding-right: 28px;
        }

        .sr-mobile-stat-grid .sr-stat-meter {
            width: 28px;
            height: 28px;
        }

        #card-progress-chart .sr-chart-box {
            height: 154px;
        }

        .sr-trend-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sr-trend-metric {
            align-items: center;
            flex-direction: row;
            gap: 7px;
        }

        .sr-trend-metric-icon {
            width: 24px;
            height: 24px;
            font-size: 0.66rem;
        }

        .sr-trend-metric-value strong {
            font-size: 0.95rem;
        }
    }

    @media (max-width: 767px) {
        #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.07), transparent 230px),
                var(--bg);
        }

        #mob-content > .db-content {
            padding: 12px 14px !important;
        }

        .sr-dashboard {
            --dash-section-gap: 14px;
            --dash-card-radius: 8px;
            --dash-card-pad: 14px;
            --dash-mobile-panel: #ffffff;
            --dash-mobile-panel-2: #f8fafc;
            --dash-mobile-line: rgba(15, 23, 42, 0.09);
            --dash-mobile-line-strong: rgba(15, 23, 42, 0.14);
            --dash-mobile-ink: #0f172a;
            --dash-mobile-copy: #334155;
            --dash-mobile-muted: #64748b;
            --dash-mobile-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 10px 24px rgba(15, 23, 42, 0.06);
            padding: 8px 0 12px !important;
        }

        :root:not(.lm) .sr-dashboard {
            --dash-mobile-panel: #111827;
            --dash-mobile-panel-2: #172033;
            --dash-mobile-line: rgba(148, 163, 184, 0.18);
            --dash-mobile-line-strong: rgba(148, 163, 184, 0.28);
            --dash-mobile-ink: #f8fafc;
            --dash-mobile-copy: #cbd5e1;
            --dash-mobile-muted: #94a3b8;
            --dash-mobile-shadow: 0 1px 0 rgba(148, 163, 184, 0.08), 0 18px 34px rgba(0, 0, 0, 0.24);
        }

        .sr-dashboard .sr-card:not(.sr-hero-card),
        .sr-dashboard .sr-polished-card,
        .sr-dashboard .sr-side-feature,
        .sr-dashboard .sr-stat-card,
        .sr-dashboard .sr-recommendation-card,
        .sr-dashboard .sr-notification-card,
        .sr-dashboard .sr-session-card-polished,
        .sr-dashboard .sr-rec-item,
        .sr-dashboard .sr-learning-item,
        .sr-dashboard .sr-trend-metric {
            background: var(--dash-mobile-panel) !important;
            border: 1px solid var(--dash-mobile-line) !important;
            border-radius: var(--dash-card-radius) !important;
            box-shadow: var(--dash-mobile-shadow) !important;
        }

        .sr-dashboard .sr-card-title,
        .sr-dashboard .sr-polished-title,
        .sr-dashboard .sr-side-title,
        .sr-dashboard .sr-plan-title,
        .sr-dashboard .sr-plan-task-title,
        .sr-dashboard .sr-learning-title,
        .sr-dashboard .sr-recommendation-title,
        .sr-dashboard .sr-notification-title,
        .sr-dashboard .sr-session-title,
        .sr-dashboard .sr-trend-title {
            color: var(--dash-mobile-ink) !important;
            letter-spacing: 0;
        }

        .sr-dashboard .sr-card-kicker,
        .sr-dashboard .sr-polished-subtitle,
        .sr-dashboard .sr-side-subtitle,
        .sr-dashboard .sr-plan-subtitle,
        .sr-dashboard .sr-plan-action,
        .sr-dashboard .sr-recommendation-reason,
        .sr-dashboard .sr-notification-message,
        .sr-dashboard .sr-session-date,
        .sr-dashboard .sr-trend-subtitle,
        .sr-dashboard .sr-trend-note {
            color: var(--dash-mobile-muted) !important;
        }

        .sr-hero-card {
            min-height: 126px;
            background:
                linear-gradient(135deg, rgba(37, 99, 235, 0.97), rgba(8, 145, 178, 0.92)),
                #2563eb;
            border: 0 !important;
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.22) !important;
        }

        :root:not(.lm) .sr-hero-card {
            background:
                linear-gradient(135deg, rgba(30, 64, 175, 0.94), rgba(8, 47, 73, 0.92)),
                #1e3a8a;
            box-shadow: 0 18px 36px rgba(0, 0, 0, 0.28) !important;
        }

        .sr-hero-inner {
            min-height: 126px;
            padding: 18px 116px 18px 16px;
        }

        .sr-hero-card .sr-title {
            color: #ffffff !important;
            font-size: 1.28rem;
            line-height: 1.15;
            font-weight: 900;
        }

        .sr-title-name {
            color: #dbeafe !important;
        }

        .sr-hero-card .sr-subtitle {
            color: rgba(255, 255, 255, 0.86) !important;
            font-size: 0.78rem;
            line-height: 1.42;
            font-weight: 700;
            margin-top: 0;
        }

        .sr-welcome-art {
            right: -10px;
            bottom: -2px;
            width: clamp(120px, 38vw, 146px);
            opacity: 0.98;
        }

        .sr-score-panel {
            padding: 14px;
        }

        .sr-score-top {
            align-items: flex-start;
        }

        .sr-score-layout {
            grid-template-columns: 104px minmax(0, 1fr);
            gap: 12px;
        }

        .sr-readiness-ring {
            --ring-size: 104px;
        }

        .sr-score-meta-item,
        .sr-plan-full-link,
        .sr-feedback-panel,
        .sr-trend-note {
            background: var(--dash-mobile-panel-2) !important;
            border-color: var(--dash-mobile-line) !important;
            border-radius: 8px !important;
        }

        .sr-mobile-stat-grid {
            gap: 10px;
            margin: 14px 0 !important;
        }

        .sr-mobile-stat-grid .sr-stat-card {
            min-height: 118px;
            padding: 12px;
        }

        .sr-mobile-stat-grid .sr-stat-icon,
        .sr-polished-icon,
        .sr-side-icon,
        .sr-trend-icon,
        .sr-plan-header-icon,
        .sr-recommendation-icon,
        .sr-notification-card-icon,
        .sr-learning-icon,
        .sr-session-icon {
            border-radius: 8px !important;
        }

        .sr-mobile-stat-grid .sr-chip,
        .sr-status-pill,
        .sr-tag,
        .sr-rec-badge {
            border-radius: 999px !important;
            font-size: 0.58rem;
            font-weight: 850;
        }

        .sr-mobile-stat-grid .sr-stat-value {
            color: var(--dash-mobile-ink);
            font-size: 1.14rem;
            margin-top: 16px;
        }

        .sr-mobile-stat-grid .sr-stat-label {
            color: var(--dash-mobile-muted);
            font-size: 0.6rem;
            line-height: 1.2;
            white-space: normal;
        }

        .sr-card-header,
        .sr-polished-header,
        .sr-side-feature-header,
        .sr-trend-header {
            margin-bottom: 14px;
        }

        .sr-card-title,
        .sr-polished-title,
        .sr-side-title,
        .sr-trend-title {
            font-size: 1rem;
            line-height: 1.2;
        }

        .sr-btn,
        .sr-section-action,
        .sr-trend-detail-btn,
        .sr-trend-filter,
        .sr-session-review-btn,
        .sr-session-delete-btn {
            border-radius: 8px !important;
            font-weight: 850;
        }

        #card-practice-plan .sr-rec-item,
        .sr-recommendation-card,
        .sr-notification-card,
        .sr-session-card-polished {
            min-height: 0;
            padding: 12px;
        }

        .sr-session-card-polished {
            grid-template-columns: 32px minmax(0, 1fr) auto;
            grid-template-areas:
                "icon copy score"
                "icon copy actions";
        }

        .sr-session-card-polished .sr-session-icon {
            grid-area: icon;
        }

        .sr-session-card-polished > div:nth-child(2) {
            grid-area: copy;
        }

        .sr-session-score-pill {
            grid-area: score;
            justify-self: end;
        }

        .sr-session-review-btn {
            grid-area: actions;
            justify-self: end;
            width: auto;
        }

        .sr-session-delete-btn {
            grid-area: actions;
            justify-self: end;
            margin-right: 74px;
        }
    }

    @media (max-width: 420px) {
        .sr-hero-inner {
            min-height: 118px;
            padding-right: 100px;
        }

        .sr-score-layout {
            grid-template-columns: 96px minmax(0, 1fr);
        }

        .sr-readiness-ring {
            --ring-size: 96px;
        }
    }

    @media (max-width: 360px) {
        .sr-hero-inner {
            padding-right: 82px;
        }

        .sr-score-layout {
            grid-template-columns: 1fr;
        }

        .sr-readiness-ring {
            --ring-size: 104px;
        }
    }

    @media (max-width: 767px) {
        #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0, rgba(16, 185, 129, 0.035) 280px, transparent 520px),
                var(--bg) !important;
        }

        #mob-content > .db-content {
            padding: 12px 12px 16px !important;
        }

        .sr-dashboard {
            --dash-section-gap: 12px;
            --dash-card-radius: 8px;
            --dash-card-pad: 13px;
            --dash-mobile-panel: rgba(255, 255, 255, 0.98);
            --dash-mobile-panel-2: #f8fafc;
            --dash-mobile-line: rgba(15, 23, 42, 0.1);
            --dash-mobile-line-strong: rgba(15, 23, 42, 0.16);
            --dash-mobile-ink: #0f172a;
            --dash-mobile-copy: #334155;
            --dash-mobile-muted: #64748b;
            --dash-mobile-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
            max-width: 520px;
            margin-inline: auto;
            padding: 8px 0 16px !important;
        }

        :root:not(.lm) .sr-dashboard {
            --dash-mobile-panel: rgba(17, 24, 39, 0.98);
            --dash-mobile-panel-2: #162033;
            --dash-mobile-line: rgba(148, 163, 184, 0.18);
            --dash-mobile-line-strong: rgba(148, 163, 184, 0.3);
            --dash-mobile-ink: #f8fafc;
            --dash-mobile-copy: #dbe4f0;
            --dash-mobile-muted: #9aa8bd;
            --dash-mobile-shadow: 0 1px 0 rgba(148, 163, 184, 0.08), 0 18px 36px rgba(0, 0, 0, 0.26);
        }

        #mob-content .sr-dashboard > * + *,
        .sr-dashboard > * + * {
            margin-top: var(--dash-section-gap) !important;
        }

        .sr-summary-grid > * + *,
        .sr-dashboard-shell > * + *,
        .sr-main-stack > * + *,
        .sr-side-stack > * + *,
        .sr-two-col > * + * {
            margin-top: var(--dash-section-gap) !important;
        }

        .sr-dashboard .sr-card:not(.sr-hero-card),
        .sr-dashboard .sr-polished-card,
        .sr-dashboard .sr-side-feature,
        .sr-dashboard .sr-stat-card,
        .sr-dashboard .sr-recommendation-card,
        .sr-dashboard .sr-notification-card,
        .sr-dashboard .sr-session-card-polished,
        .sr-dashboard .sr-rec-item,
        .sr-dashboard .sr-learning-item,
        .sr-dashboard .sr-trend-metric {
            background: var(--dash-mobile-panel) !important;
            border: 1px solid var(--dash-mobile-line) !important;
            border-radius: var(--dash-card-radius) !important;
            box-shadow: var(--dash-mobile-shadow) !important;
        }

        .sr-card-pad,
        .sr-polished-card.sr-card-pad,
        .sr-side-feature.sr-card-pad,
        #card-progress-chart.sr-card-pad,
        #card-practice-plan.sr-card-pad,
        #card-skill-radar,
        #card-daily-challenge {
            padding: var(--dash-card-pad) !important;
        }

        .sr-dashboard :is(
            .sr-card-title,
            .sr-polished-title,
            .sr-side-title,
            .sr-plan-title,
            .sr-plan-task-title,
            .sr-learning-title,
            .sr-recommendation-title,
            .sr-notification-title,
            .sr-session-title,
            .sr-trend-title
        ) {
            color: var(--dash-mobile-ink) !important;
            letter-spacing: 0;
        }

        .sr-dashboard :is(
            .sr-card-kicker,
            .sr-polished-subtitle,
            .sr-side-subtitle,
            .sr-plan-subtitle,
            .sr-plan-action,
            .sr-recommendation-reason,
            .sr-notification-message,
            .sr-session-date,
            .sr-trend-subtitle,
            .sr-trend-note
        ) {
            color: var(--dash-mobile-muted) !important;
        }

        .sr-hero-card {
            min-height: 116px;
            overflow: hidden;
            background:
                linear-gradient(115deg, rgba(37, 99, 235, 0.98), rgba(8, 145, 178, 0.94)),
                #2563eb !important;
            border: 0 !important;
            box-shadow: 0 16px 34px rgba(37, 99, 235, 0.24) !important;
        }

        .sr-hero-card::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 26px 26px;
            opacity: 0.26;
        }

        .sr-hero-card::after {
            width: 54%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.13));
        }

        :root:not(.lm) .sr-hero-card {
            background:
                linear-gradient(115deg, rgba(30, 64, 175, 0.96), rgba(15, 118, 110, 0.9)),
                #1e3a8a !important;
            box-shadow: 0 20px 38px rgba(0, 0, 0, 0.3) !important;
        }

        .sr-hero-inner {
            min-height: 116px;
            padding: 14px 100px 14px 14px;
        }

        .sr-hero-card .sr-title {
            color: #ffffff !important;
            font-size: 1.16rem;
            line-height: 1.15;
            font-weight: 900;
        }

        .sr-hero-card .sr-title-name {
            color: #ffffff !important;
            text-shadow: 0 2px 10px rgba(15, 23, 42, 0.22);
        }

        .sr-hero-card .sr-subtitle {
            max-width: 14.5rem;
            margin-top: 0;
            color: rgba(255, 255, 255, 0.88) !important;
            font-size: 0.76rem;
            line-height: 1.4;
            font-weight: 750;
        }

        .sr-welcome-art {
            right: -10px;
            bottom: -5px;
            width: 122px;
            opacity: 0.98;
            filter: drop-shadow(0 12px 20px rgba(15, 23, 42, 0.22));
        }

        .sr-btn:active {
            transform: translateY(1px);
        }

        .sr-score-panel {
            position: relative;
            padding: 13px !important;
            overflow: hidden;
        }

        .sr-score-panel::before {
            content: "";
            position: absolute;
            top: 0;
            left: 13px;
            right: 13px;
            height: 3px;
            border-radius: 0 0 999px 999px;
            background: linear-gradient(90deg, #2563eb, #14b8a6, #22c55e);
        }

        .sr-score-top {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .sr-score-top .sr-status-pill,
        .sr-score-top .sr-chip {
            min-height: 28px;
            justify-content: center;
            padding: 6px 9px;
            font-size: 0.62rem;
            line-height: 1;
            white-space: nowrap;
        }

        .sr-score-layout {
            grid-template-columns: 108px minmax(0, 1fr);
            gap: 12px;
            align-items: stretch;
        }

        .sr-readiness-ring {
            --ring-size: 108px;
            align-self: center;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08), 0 14px 26px rgba(15, 23, 42, 0.08);
        }

        .sr-score-value {
            font-size: 2rem;
        }

        .sr-score-meta {
            gap: 8px;
        }

        .sr-score-meta-item {
            min-height: 58px;
            padding: 9px 10px;
            background: var(--dash-mobile-panel-2) !important;
            border-color: var(--dash-mobile-line) !important;
        }

        .sr-meta-label {
            font-size: 0.62rem;
            letter-spacing: 0;
        }

        .sr-meta-value {
            font-size: 1.05rem;
        }

        .sr-score-icon {
            width: 32px;
            height: 32px;
            flex-basis: 32px;
            border-radius: 8px !important;
        }

        .sr-score-note {
            margin: 12px 0 0;
            padding: 9px 10px;
            border: 1px solid var(--dash-mobile-line);
            border-radius: 8px;
            background: var(--dash-mobile-panel-2);
            font-size: 0.68rem;
            line-height: 1.35;
        }

        .sr-mobile-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin: 12px 0 !important;
        }

        .sr-mobile-stat-grid .sr-stat-card {
            min-height: 104px;
            padding: 11px;
            overflow: hidden;
        }

        .sr-mobile-stat-grid .sr-stat-card::after {
            left: 0;
            right: 0;
            bottom: 0;
            height: 3px;
            border-radius: 0;
            background: var(--accent, #2563eb);
            opacity: 0.9;
        }

        .sr-mobile-stat-grid .sr-stat-head {
            align-items: center;
            gap: 7px;
            min-height: 28px;
        }

        .sr-mobile-stat-grid .sr-stat-icon {
            width: 28px;
            height: 28px;
            flex: 0 0 28px;
            color: var(--accent, #2563eb);
            background: color-mix(in srgb, var(--accent, #2563eb) 12%, transparent);
        }

        .sr-mobile-stat-grid .sr-chip {
            max-width: calc(100% - 35px);
            padding: 5px 7px;
            font-size: 0.56rem;
            color: var(--accent, #2563eb) !important;
            background: color-mix(in srgb, var(--accent, #2563eb) 11%, transparent) !important;
            border-color: color-mix(in srgb, var(--accent, #2563eb) 18%, transparent) !important;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sr-mobile-stat-grid .sr-stat-body {
            padding-right: 30px;
        }

        .sr-mobile-stat-grid .sr-stat-value {
            margin-top: 13px;
            color: var(--dash-mobile-ink);
            font-size: 1.12rem;
        }

        .sr-mobile-stat-grid .sr-stat-label {
            color: var(--dash-mobile-muted);
            font-size: 0.58rem;
            line-height: 1.18;
            white-space: normal;
        }

        .sr-mobile-stat-grid .sr-stat-meter {
            width: 28px;
            height: 28px;
            bottom: 0;
            opacity: 0.95;
        }

        .sr-mobile-stat-grid .sr-stat-meter::before {
            inset: 4px;
        }

        .sr-mobile-stat-grid .sr-stat-meter > * {
            font-size: 0.52rem;
        }

        .sr-polished-header,
        .sr-side-feature-header,
        .sr-plan-header,
        .sr-trend-header {
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .sr-polished-icon,
        .sr-side-icon,
        .sr-trend-icon,
        .sr-plan-header-icon,
        .sr-recommendation-icon,
        .sr-notification-card-icon,
        .sr-learning-icon,
        .sr-session-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 8px !important;
            font-size: 0.92rem;
        }

        .sr-polished-title,
        .sr-side-title,
        .sr-plan-title,
        .sr-trend-title {
            font-size: 0.94rem !important;
            line-height: 1.2;
        }

        .sr-polished-subtitle,
        .sr-side-subtitle,
        .sr-plan-subtitle,
        .sr-trend-subtitle {
            margin-top: 4px;
            font-size: 0.68rem !important;
            line-height: 1.34;
        }

        .sr-btn,
        .sr-section-action,
        .sr-trend-detail-btn,
        .sr-trend-filter,
        .sr-side-detail-btn,
        .sr-session-review-btn,
        .sr-session-delete-btn {
            min-height: 38px;
            border-radius: 8px !important;
            font-size: 0.68rem;
            font-weight: 900;
        }

        .sr-trend-actions.justify-content-between {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(116px, 0.92fr);
            gap: 8px;
            align-items: stretch;
        }

        .sr-trend-actions.justify-content-between .sr-trend-detail-btn,
        .sr-trend-actions.justify-content-between .sr-trend-filter {
            width: 100%;
            min-width: 0;
        }

        .sr-trend-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-top: 8px;
        }

        .sr-trend-metric {
            min-height: 54px;
            padding: 8px;
            gap: 8px;
            background: var(--dash-mobile-panel-2) !important;
        }

        .sr-trend-metric-icon {
            width: 28px;
            height: 28px;
            font-size: 0.7rem;
        }

        .sr-trend-metric-label {
            font-size: 0.56rem;
            line-height: 1.1;
        }

        .sr-trend-metric-value,
        .sr-trend-metric-value strong {
            font-size: 0.9rem;
        }

        #card-progress-chart .sr-chart-box {
            height: 172px;
            margin-top: 10px;
        }

        .sr-trend-note,
        .sr-plan-full-link,
        .sr-feedback-panel,
        .sr-insight-box,
        .sr-empty,
        .sr-polished-empty,
        .sr-goal-panel {
            background: var(--dash-mobile-panel-2) !important;
            border-color: var(--dash-mobile-line) !important;
            border-radius: 8px !important;
        }

        .sr-rec-list,
        .sr-learning-list,
        .sr-notification-list-polished,
        .sr-session-list,
        #card-practice-plan .sr-rec-list {
            gap: 8px;
        }

        #card-practice-plan .sr-rec-item,
        .sr-recommendation-card,
        .sr-notification-card,
        .sr-learning-item {
            min-height: 0;
            padding: 11px;
        }

        #card-practice-plan .sr-rec-item::before,
        .sr-recommendation-card::before {
            width: 3px;
            background: var(--accent, #2563eb);
        }

        .sr-recommendation-card {
            grid-template-columns: 34px minmax(0, 1fr) 30px;
            gap: 10px;
        }

        .sr-recommendation-next {
            width: 30px;
            height: 30px;
        }

        .sr-learning-item {
            grid-template-columns: 36px minmax(0, 1fr);
            gap: 10px;
        }

        .sr-learning-title {
            white-space: normal;
        }

        .sr-section-actions {
            display: grid;
            grid-template-columns: minmax(0, 180px);
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .sr-section-actions form,
        .sr-section-action {
            width: 100%;
        }

        .sr-session-card-polished {
            grid-template-columns: 34px minmax(0, 1fr) auto;
            grid-template-areas:
                "icon meta score"
                "review review delete";
            gap: 10px;
            align-items: center;
            padding: 11px;
        }

        .sr-session-card-polished .sr-session-icon {
            grid-area: icon;
        }

        .sr-session-card-polished .sr-session-meta {
            grid-area: meta;
        }

        .sr-session-card-polished .sr-session-score-stack {
            grid-area: score;
            justify-self: end;
            width: 56px;
        }

        .sr-session-score-pill {
            justify-self: end;
        }

        .sr-session-score-bar {
            width: 56px;
        }

        .sr-session-card-polished .sr-session-review-btn {
            grid-area: review;
            width: 100%;
            justify-self: stretch;
        }

        .sr-session-card-polished > form {
            grid-area: delete;
            justify-self: end;
            width: 40px;
            margin: 0;
        }

        .sr-session-delete-btn {
            width: 40px;
            margin: 0 !important;
        }

        .sr-notification-card {
            grid-template-columns: 36px minmax(0, 1fr);
            grid-template-areas:
                "icon copy"
                ". meta";
            gap: 9px 10px;
        }

        .sr-notification-card-icon {
            grid-area: icon;
        }

        .sr-notification-card > .min-w-0 {
            grid-area: copy;
        }

        .sr-notification-meta {
            grid-area: meta;
            justify-self: start;
            font-size: 0.62rem;
        }

        .sr-achievement-showcase {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 5px;
        }

        .sr-achievement-tile {
            min-height: 78px;
            padding: 7px 4px;
            border-radius: 8px;
            gap: 5px;
        }

        .sr-achievement-tile-icon {
            min-height: 22px;
            font-size: clamp(1rem, 4.3vw, 1.25rem);
        }

        .sr-achievement-tile-title {
            font-size: clamp(0.44rem, 1.8vw, 0.54rem);
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .sr-achievement-status {
            max-width: 100%;
            padding: 3px 4px;
            font-size: clamp(0.38rem, 1.55vw, 0.45rem);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }

    @media (max-width: 420px) {
        .sr-hero-card {
            min-height: 108px;
        }

        .sr-hero-inner {
            min-height: 108px;
            padding: 13px 86px 13px 13px;
        }

        .sr-welcome-art {
            right: -16px;
            bottom: -4px;
            width: 112px;
        }

        .sr-hero-card .sr-title {
            font-size: 1.06rem;
        }

        .sr-hero-card .sr-subtitle {
            max-width: 12.8rem;
            font-size: 0.7rem;
        }

        .sr-score-layout {
            grid-template-columns: 98px minmax(0, 1fr);
            gap: 10px;
        }

        .sr-readiness-ring {
            --ring-size: 98px;
        }
    }

    @media (max-width: 360px) {
        .sr-hero-card {
            min-height: 104px;
        }

        .sr-hero-inner {
            min-height: 104px;
            padding: 12px 72px 12px 12px;
        }

        .sr-welcome-art {
            right: -20px;
            bottom: -3px;
            width: 96px;
        }

        .sr-hero-card .sr-title {
            font-size: 0.98rem;
        }

        .sr-hero-card .sr-subtitle {
            max-width: 11.5rem;
            font-size: 0.66rem;
        }

        .sr-score-top {
            grid-template-columns: 1fr;
        }

        .sr-score-top .sr-status-pill,
        .sr-score-top .sr-chip {
            justify-self: stretch;
        }

        .sr-score-layout {
            grid-template-columns: 1fr;
            justify-items: center;
        }

        .sr-score-meta {
            width: 100%;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .sr-score-meta-item {
            align-items: flex-start;
            min-height: 76px;
            flex-direction: column;
            gap: 7px;
        }

        .sr-score-icon {
            order: -1;
        }

        .sr-mobile-stat-grid {
            gap: 7px;
        }

        .sr-mobile-stat-grid .sr-stat-card {
            min-height: 100px;
            padding: 10px;
        }

        .sr-trend-actions.justify-content-between,
        .sr-section-actions {
            grid-template-columns: 1fr;
        }

        .sr-session-card-polished {
            grid-template-columns: 32px minmax(0, 1fr);
            grid-template-areas:
                "icon meta"
                "score score"
                "review delete";
        }

        .sr-session-card-polished .sr-session-score-stack {
            justify-self: stretch;
            width: 100%;
        }

        .sr-session-score-bar {
            width: 100%;
        }
    }

    @media (max-width: 767px) {
        #mob-content .sr-dashboard .sr-score-panel {
            padding: 10px !important;
            border-radius: 8px !important;
        }

        #mob-content .sr-dashboard .sr-mobile-readiness-row {
            display: grid !important;
            grid-template-columns: minmax(104px, 0.4fr) minmax(0, 0.6fr) !important;
            align-items: stretch !important;
            gap: clamp(6px, 2vw, 8px) !important;
            min-width: 0 !important;
            margin-top: var(--dash-section-gap) !important;
        }

        #mob-content .sr-dashboard .sr-mobile-readiness-row > .sr-score-panel,
        #mob-content .sr-dashboard .sr-mobile-readiness-row > .sr-mobile-stat-grid {
            min-width: 0 !important;
            margin: 0 !important;
        }

        #mob-content .sr-dashboard .sr-mobile-readiness-row > .sr-score-panel {
            order: 0 !important;
        }

        #mob-content .sr-dashboard .sr-mobile-readiness-row > .sr-mobile-stat-grid {
            order: 1 !important;
        }

        #mob-content .sr-dashboard .sr-score-top {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 6px !important;
            margin-bottom: 8px !important;
            align-items: center !important;
        }

        #mob-content .sr-dashboard .sr-score-top .sr-status-pill,
        #mob-content .sr-dashboard .sr-score-top .sr-chip {
            min-width: 0 !important;
            max-width: 100% !important;
            min-height: 24px !important;
            padding: 5px 8px !important;
            font-size: 0.56rem !important;
            line-height: 1 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        #mob-content .sr-dashboard .sr-score-layout {
            grid-template-columns: 1fr !important;
            gap: 7px !important;
            justify-items: center !important;
        }

        #mob-content .sr-dashboard .sr-readiness-ring {
            --ring-size: clamp(64px, 21vw, 76px) !important;
        }

        #mob-content .sr-dashboard .sr-score-value {
            font-size: clamp(1.05rem, 4.8vw, 1.34rem) !important;
            line-height: 1 !important;
        }

        #mob-content .sr-dashboard .sr-score-value span {
            font-size: clamp(0.56rem, 2.6vw, 0.72rem) !important;
        }

        #mob-content .sr-dashboard .sr-ring-label {
            font-size: clamp(0.4rem, 2vw, 0.48rem) !important;
            line-height: 1.1 !important;
        }

        #mob-content .sr-dashboard .sr-score-meta {
            display: grid !important;
            gap: 6px !important;
            grid-template-columns: 1fr !important;
            width: 100% !important;
        }

        #mob-content .sr-dashboard .sr-score-meta-item {
            min-width: 0 !important;
            min-height: 42px !important;
            padding: 6px !important;
            border-radius: 7px !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 5px !important;
        }

        #mob-content .sr-dashboard .sr-score-meta-item > div:first-child {
            min-width: 0 !important;
        }

        #mob-content .sr-dashboard .sr-meta-label {
            margin-bottom: 2px !important;
            font-size: clamp(0.46rem, 2.2vw, 0.52rem) !important;
            line-height: 1.1 !important;
            white-space: normal !important;
            overflow-wrap: anywhere !important;
        }

        #mob-content .sr-dashboard .sr-meta-value {
            font-size: clamp(0.74rem, 3.2vw, 0.92rem) !important;
            line-height: 1.05 !important;
        }

        #mob-content .sr-dashboard .sr-score-icon {
            width: 22px !important;
            height: 22px !important;
            flex-basis: 22px !important;
            order: 0 !important;
            border-radius: 7px !important;
            font-size: 0.68rem !important;
        }

        #mob-content .sr-dashboard .sr-score-note {
            display: none !important;
            min-height: 30px !important;
            margin-top: 8px !important;
            padding: 7px 9px !important;
            border-radius: 7px !important;
            font-size: 0.66rem !important;
            line-height: 1.2 !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            grid-auto-rows: minmax(82px, auto) !important;
            align-items: stretch !important;
            gap: clamp(6px, 2vw, 8px) !important;
            margin: 10px 0 !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-stat-card {
            position: relative !important;
            min-width: 0 !important;
            min-height: 82px !important;
            height: 100% !important;
            padding: clamp(7px, 2vw, 9px) !important;
            border-radius: 8px !important;
            justify-content: space-between !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-stat-head {
            min-width: 0 !important;
            min-height: 22px !important;
            gap: 5px !important;
            margin-bottom: 7px !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-stat-icon {
            width: 22px !important;
            height: 22px !important;
            flex-basis: 22px !important;
            font-size: 0.62rem !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-chip {
            max-width: calc(100% - 27px) !important;
            min-height: 16px !important;
            padding: 2px 6px !important;
            font-size: clamp(0.46rem, 2vw, 0.5rem) !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-stat-value {
            margin-top: 0 !important;
            font-size: clamp(0.84rem, 3.6vw, 0.96rem) !important;
            line-height: 1 !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-stat-value span {
            font-size: clamp(0.52rem, 2.4vw, 0.62rem) !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-stat-label {
            margin-top: 3px !important;
            font-size: clamp(0.48rem, 2.1vw, 0.52rem) !important;
            line-height: 1.12 !important;
            white-space: normal !important;
            overflow-wrap: anywhere !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-stat-body {
            position: static !important;
            margin-top: auto !important;
            min-width: 0 !important;
            padding-right: 0 !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-stat-meter {
            display: grid !important;
            width: clamp(28px, 7.5vw, 34px) !important;
            height: clamp(28px, 7.5vw, 34px) !important;
            left: 50% !important;
            top: 50% !important;
            right: auto !important;
            bottom: auto !important;
            transform: translate(-50%, -50%) !important;
            font-size: clamp(0.66rem, 2.2vw, 0.78rem) !important;
        }

        #mob-content .sr-dashboard .sr-mobile-stat-grid .sr-stat-meter > * {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            height: 100% !important;
            line-height: 1 !important;
        }

        #mob-content .sr-dashboard .sr-trend-actions.justify-content-between {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin-bottom: 8px !important;
        }

        #mob-content .sr-dashboard .sr-trend-detail-btn,
        #mob-content .sr-dashboard .sr-trend-filter {
            width: 100% !important;
            min-height: 34px !important;
            padding: 7px 9px !important;
            border-radius: 7px !important;
            font-size: 0.66rem !important;
            font-weight: 850 !important;
            line-height: 1.1 !important;
        }

        #mob-content .sr-dashboard .sr-trend-detail-btn i {
            font-size: 0.62rem !important;
        }

        #mob-content .sr-dashboard .sr-trend-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin: 8px 0 10px !important;
        }

        #mob-content .sr-dashboard .sr-trend-metric {
            min-height: 52px !important;
            padding: 8px !important;
            gap: 8px !important;
            border-radius: 8px !important;
        }

        #mob-content .sr-dashboard .sr-trend-metric-icon {
            width: 26px !important;
            height: 26px !important;
            flex-basis: 26px !important;
            border-radius: 8px !important;
            font-size: 0.7rem !important;
        }

        #mob-content .sr-dashboard .sr-trend-metric-label {
            font-size: 0.52rem !important;
            line-height: 1.1 !important;
        }

        #mob-content .sr-dashboard .sr-trend-metric-value {
            font-size: 0.74rem !important;
            line-height: 1.1 !important;
        }

        #mob-content .sr-dashboard .sr-trend-metric-value strong {
            font-size: 0.82rem !important;
            line-height: 1 !important;
        }

        #mob-content .sr-dashboard .sr-trend-metric-value span {
            font-size: 0.56rem !important;
            line-height: 1 !important;
        }
    }

    .sr-hero-card {
        min-height: 174px;
    }

    .sr-hero-inner {
        min-height: 174px;
        padding: 24px clamp(238px, 32%, 332px) 24px 24px;
    }

    .sr-hero-card .sr-user-row {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        clip-path: inset(50%);
        white-space: nowrap;
    }

    .sr-hero-card .sr-welcome-art {
        right: clamp(16px, 2.6vw, 32px);
        bottom: 0;
        width: clamp(186px, 16vw, 218px);
        opacity: 1;
    }

    .sr-hero-card .sr-robot-hand-message {
        top: -40px;
        left: -304px;
        width: 356px;
        max-width: none;
        transform-origin: 96% 58%;
    }

    .sr-hero-card .sr-robot-message-bubble,
    .sr-hero-card .sr-robot-message-tail {
        stroke-width: 2.2;
    }

    .sr-hero-card .sr-robot-message-text {
        align-items: flex-start;
        gap: 0;
        justify-content: center;
        overflow: visible;
        color: #0f172a;
        font-size: 16px;
        font-weight: 850;
        line-height: 1.17;
        text-align: left;
    }

    .sr-hero-card .sr-robot-message-copy {
        margin: 0;
        max-width: 100%;
        text-wrap: balance;
    }

    .sr-hero-card .sr-robot-message-copy .sr-subtitle-accent {
        display: inline;
        font-weight: 950;
        color: #2563eb !important;
        text-shadow: none;
    }

    .sr-hero-card .sr-robot-message-copy .sr-subtitle-accent.is-sky {
        color: #0284c7 !important;
    }

    .sr-hero-card .sr-robot-message-copy .sr-subtitle-accent.is-mint {
        color: #059669 !important;
    }

    @media (max-width: 991px) {
        .sr-hero-card {
            min-height: 158px;
        }

        .sr-hero-inner {
            min-height: 158px;
            padding: 14px 126px 14px 14px;
        }

        .sr-hero-card .sr-welcome-art {
            right: -6px;
            bottom: -2px;
            width: clamp(124px, 35vw, 150px);
        }

        .sr-hero-card .sr-robot-hand-message {
            top: -48px;
            left: -236px;
            width: 276px;
        }

        .sr-hero-card .sr-robot-message-text {
            font-size: 15.5px;
            line-height: 1.16;
        }
    }

    @media (max-width: 420px) {
        .sr-hero-card {
            min-height: 146px;
        }

        .sr-hero-inner {
            min-height: 146px;
            padding: 12px 94px 12px 12px;
        }

        .sr-hero-card .sr-welcome-art {
            right: -14px;
            bottom: -2px;
            width: 112px;
        }

        .sr-hero-card .sr-robot-hand-message {
            top: -42px;
            left: -204px;
            width: 238px;
        }

        .sr-hero-card .sr-robot-message-text {
            font-size: 16px;
            line-height: 1.15;
        }
    }

    @media (max-width: 360px) {
        .sr-hero-card {
            min-height: 138px;
        }

        .sr-hero-inner {
            min-height: 138px;
            padding-right: 84px;
        }

        .sr-hero-card .sr-welcome-art {
            right: -18px;
            bottom: -2px;
            width: 102px;
        }

        .sr-hero-card .sr-robot-hand-message {
            top: -36px;
            left: -184px;
            width: 214px;
        }

        .sr-hero-card .sr-robot-message-text {
            font-size: 16.5px;
            line-height: 1.13;
        }
    }
</style>

<div class="db-section active sr-dashboard" id="sec-overview">
    <div class="sr-summary-grid">
        <div class="sr-welcome-stack">
            <section class="sr-card sr-hero-card p-0" aria-label="Dashboard welcome">
                <div class="sr-hero-inner">
                    <div class="sr-user-row">
                        <div class="sr-welcome-copy">
                            <p class="sr-subtitle visually-hidden">Track your readiness, monitor your progress, and get coaching for job, BPO, IT, scholarship, and admission interviews.</p>
                        </div>
                    </div>
                    <div class="sr-welcome-art" aria-hidden="true">
                        <img class="sr-welcome-robot-img" src="{{ asset('img/dashboard-welcome-robot-transparent.png') }}" alt="">
                        <svg class="sr-robot-hand-message" viewBox="0 0 390 172" focusable="false">
                            <path class="sr-robot-message-tail" d="M306 82 C335 84 363 91 384 102 C358 106 331 118 306 132 C319 115 319 97 306 82 Z"></path>
                            <rect class="sr-robot-message-bubble" x="10" y="12" width="304" height="132" rx="34"></rect>
                            <ellipse class="sr-robot-message-shine" cx="238" cy="36" rx="48" ry="13" transform="rotate(8 238 36)"></ellipse>
                            <circle class="sr-robot-message-dot" cx="30" cy="31" r="5"></circle>
                            <circle class="sr-robot-message-dot" cx="20" cy="47" r="4"></circle>
                            <circle class="sr-robot-message-dot" cx="32" cy="64" r="3.5"></circle>
                            <foreignObject x="42" y="31" width="252" height="102">
                                <div xmlns="http://www.w3.org/1999/xhtml" class="sr-robot-message-text">
                                    <p class="sr-robot-message-copy">Track your <span class="sr-subtitle-accent">readiness</span>, monitor your <span class="sr-subtitle-accent is-sky">progress</span>, and get <span class="sr-subtitle-accent is-mint">coaching</span> for job, BPO, IT, scholarship, and admission interviews.</p>
                                </div>
                            </foreignObject>
                        </svg>
                        <img class="sr-welcome-hand-wave-img" src="{{ asset('img/dashboard-welcome-robot-transparent.png') }}" alt="">
                    </div>
                </div>
            </section>

            <div class="stat-grid sr-stats-desktop" role="group" aria-label="Quick statistics">
                <div class="sr-stat-card" style="--accent:#3b82f6;--meter-value:{{ $sessionsMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-microphone"></i></div>
                        <span class="sr-chip">Practice</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $totalSessions ?? 0 }}</div>
                        <div class="sr-stat-label">Completed sessions</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#22c55e;--meter-value:{{ $ratingMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-regular fa-star"></i></div>
                        <span class="sr-chip">Quality</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $rating }}<span style="font-size:.9rem;color:var(--tx3)">/5</span></div>
                        <div class="sr-stat-label">Average rating</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-award"></i></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#06b6d4;--meter-value:{{ $xpMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-bolt"></i></div>
                        <span class="sr-chip">Growth</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ number_format($experiencePoints ?? 0) }}</div>
                        <div class="sr-stat-label">Experience points</div>
                        <div class="sr-stat-meter" aria-hidden="true"><span>Lv. {{ $playerLevel }}</span></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#f59e0b;--meter-value:{{ $streakMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-fire"></i></div>
                        <span class="sr-chip">Streak</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $currentStreak ?? 0 }}</div>
                        <div class="sr-stat-label">Active practice days</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-regular fa-calendar-days"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sr-mobile-readiness-row">
            <section class="sr-card sr-score-panel {{ $scoreVal >= 80 ? 'score-high-panel' : ($scoreVal >= 60 ? 'score-med-panel' : 'score-low-panel') }}" aria-label="Readiness score">
                <div class="sr-score-top">
                    <span class="sr-status-pill {{ $scoreClass }}"><i class="fa-solid {{ $scoreIcon }}"></i> {{ $isMobile ? $mobileScoreText : $scoreText }}</span>
                    @unless($isMobile)
                        <span class="sr-chip ph-focus-chip"><i class="fa-solid fa-location-dot"></i> PH Focus</span>
                    @endunless
                </div>
                <div class="sr-score-layout">
                    <div class="sr-readiness-ring" style="--ring-value: {{ $scoreVal }}%;" aria-label="Overall readiness {{ $scoreVal }} percent">
                        <div class="sr-ring-content">
                            <div class="sr-score-value">{{ $scoreVal }}<span>%</span></div>
                            <div class="sr-ring-label">Overall Readiness</div>
                        </div>
                    </div>
                    <div class="sr-score-meta">
                        <div class="sr-score-meta-item">
                            <div>
                                <div class="sr-meta-label">Average Rating</div>
                                <div class="sr-meta-value">{{ $rating }}/5</div>
                            </div>
                            <div class="sr-score-icon"><i class="fa-regular fa-star"></i></div>
                        </div>
                        <div class="sr-score-meta-item">
                            <div>
                                <div class="sr-meta-label">Next Goal</div>
                                <div class="sr-meta-value">{{ isset($upcomingGoal) ? ($upcomingGoal->target ?? 100) : 100 }}%</div>
                            </div>
                            <div class="sr-score-icon"><i class="fa-solid fa-bullseye"></i></div>
                        </div>
                    </div>
                </div>
                <div class="sr-score-note"><i class="fa-solid fa-star"></i> Keep practicing. You're on your way!</div>
            </section>

            <div class="sr-mobile-stat-grid" role="group" aria-label="Quick statistics">
                <div class="sr-stat-card" style="--accent:#3b82f6;--meter-value:{{ $sessionsMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-microphone"></i></div>
                        <span class="sr-chip">Practice</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $totalSessions ?? 0 }}</div>
                        <div class="sr-stat-label">Completed sessions</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-arrow-trend-up"></i></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#22c55e;--meter-value:{{ $ratingMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-regular fa-star"></i></div>
                        <span class="sr-chip">Quality</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $rating }}<span style="font-size:.9rem;color:var(--tx3)">/5</span></div>
                        <div class="sr-stat-label">Average rating</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-solid fa-award"></i></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#06b6d4;--meter-value:{{ $xpMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-bolt"></i></div>
                        <span class="sr-chip">Growth</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ number_format($experiencePoints ?? 0) }}</div>
                        <div class="sr-stat-label">Experience points</div>
                        <div class="sr-stat-meter" aria-hidden="true"><span>Lv. {{ $playerLevel }}</span></div>
                    </div>
                </div>
                <div class="sr-stat-card" style="--accent:#f59e0b;--meter-value:{{ $streakMeter }}%;">
                    <div class="sr-stat-head">
                        <div class="sr-stat-icon"><i class="fa-solid fa-fire"></i></div>
                        <span class="sr-chip">Streak</span>
                    </div>
                    <div class="sr-stat-body">
                        <div class="sr-stat-value">{{ $currentStreak ?? 0 }}</div>
                        <div class="sr-stat-label">Active practice days</div>
                        <div class="sr-stat-meter" aria-hidden="true"><i class="fa-regular fa-calendar-days"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sr-dashboard-shell">
        <main class="sr-main-stack">
            <section id="card-progress-chart" class="sr-card sr-card-pad">
                <div class="sr-trend-header">
                    <div>
                        <div class="sr-trend-title-row">
                            <div class="sr-trend-icon"><i class="fa-solid fa-chart-line"></i></div>
                            <h5 class="sr-trend-title">Readiness Trend</h5>
                        </div>
                        <p class="sr-trend-subtitle">Recent completed Philippine interview sessions, scored from 0 to 100.</p>
                    </div>
                </div>
                <div class="sr-trend-actions justify-content-between mb-2">
                    <a href="{{ route('user.progress') }}" class="sr-trend-detail-btn">View Details <i class="fa-solid fa-chevron-right"></i></a>
                    <select class="sr-trend-filter" id="readinessTrendRange" aria-label="Readiness trend range">
                        <option value="5">Recent 5 Sessions</option>
                        <option value="10" selected>Recent 10 Sessions</option>
                    </select>
                </div>
                <div class="sr-trend-metrics">
                    <div class="sr-trend-metric" style="--metric-color:#2563eb">
                        <div class="sr-trend-metric-icon"><i class="fa-solid fa-gauge-high"></i></div>
                        <div>
                            <div class="sr-trend-metric-label">Average Score</div>
                            <div class="sr-trend-metric-value"><strong>{{ $trendAverage }}</strong> /100</div>
                        </div>
                    </div>
                    <div class="sr-trend-metric" style="--metric-color:#16a34a">
                        <div class="sr-trend-metric-icon"><i class="fa-solid {{ $trendImprovement >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i></div>
                        <div>
                            <div class="sr-trend-metric-label">Improvement</div>
                            <div class="sr-trend-metric-value"><strong>{{ $trendImprovement >= 0 ? '+' : '' }}{{ $trendImprovement }}%</strong> <span style="font-size:.82rem;font-weight:700;color:var(--trend-muted)">vs first</span></div>
                        </div>
                    </div>
                </div>
                <div class="sr-chart-box sr-trend-chart-wrap">
                    <canvas id="progressChart"></canvas>
                </div>
                <div class="sr-trend-note">
                    <i class="fa-regular fa-star"></i>
                    <span><strong>Keep it up!</strong> You're improving your readiness. Continue practicing consistently.</span>
                </div>
            </section>

            <section id="card-practice-plan" class="sr-card sr-card-pad">
                <div class="sr-plan-header">
                    <div class="sr-plan-header-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="sr-plan-header-copy">
                        <h5 class="sr-plan-title">Personalized Practice Plan</h5>
                        <p class="sr-plan-subtitle">A plan built just for you based on your latest scores, voice work, and learning progress.</p>
                    </div>
                </div>
                <a href="{{ route('user.progress') }}#personalized-practice-plan" class="sr-plan-full-link">
                    <i class="fa-regular fa-rectangle-list"></i>
                    <span>View Full Plan</span>
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
                @if(isset($practicePlan) && $practicePlan->count() > 0)
                    <div class="sr-rec-list">
                        @foreach($practicePlan as $item)
                            <a href="{{ $item->url }}" class="sr-rec-item" style="--accent: {{ $item->color }}; text-decoration:none;color:inherit;">
                                <div class="sr-rec-icon" style="--accent: {{ $item->color }}"><i class="fa-solid {{ $item->icon ?? 'fa-clipboard-list' }}"></i></div>
                                <div class="sr-plan-copy">
                                    <div class="sr-plan-top">
                                        <span class="sr-plan-step">{{ $item->day }}</span>
                                        <span class="sr-plan-task-title">{{ $item->title }}</span>
                                    </div>
                                    <div class="sr-plan-action">{{ $item->action }}</div>
                                    <div class="sr-plan-meta">
                                        <span class="sr-tag" style="background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.18);color:#10b981">{{ $item->minutes }} min</span>
                                        <span class="sr-tag" style="background:color-mix(in srgb, {{ $item->color }} 12%, transparent);border-color:color-mix(in srgb, {{ $item->color }} 22%, transparent);color:{{ $item->color }}">{{ $item->focus }}</span>
                                    </div>
                                    <span class="sr-plan-cta">{{ $item->cta }} <i class="fa-solid fa-arrow-right"></i></span>
                                </div>
                                <i class="fa-solid fa-chevron-right sr-plan-card-chevron"></i>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="sr-empty">
                        <i class="fa-solid fa-calendar-check"></i>
                        <div>Complete a Philippine interview or voice rehearsal to generate a practice plan.</div>
                    </div>
                @endif
            </section>

            <div class="sr-two-col">
                <section class="sr-card sr-card-pad sr-polished-card" style="--polish-accent:#10b981">
                    <div class="sr-polished-header">
                        <div class="sr-polished-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <div>
                            <h5 class="sr-polished-title">Category Performance</h5>
                            <p class="sr-polished-subtitle">Where your interview scores are strongest.</p>
                        </div>
                    </div>
                    @if($categoryCount > 0)
                        <div class="sr-progress-list">
                            @foreach($categoryPerformance as $index => $cat)
                                @php
                                    $colors = ['#22c55e', '#3b82f6', '#06b6d4', '#f59e0b', '#8b5cf6'];
                                    $color = $colors[$index % count($colors)];
                                    $catScore = max(0, min(100, (int) $cat->score));
                                @endphp
                                <div class="sr-progress-row">
                                    <div class="sr-progress-name">{{ $cat->name }}</div>
                                    <div class="sr-progress-score">{{ $catScore }}%</div>
                                    <div class="sr-progress"><span style="--value: {{ $catScore }}%; background: {{ $color }}"></span></div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sr-polished-empty">
                            <div class="sr-polished-empty-inner">
                                <div class="sr-empty-visual"><i class="fa-solid fa-folder-open"></i></div>
                                <p class="sr-polished-empty-text">Complete a Philippine interview session to unlock category performance.</p>
                            </div>
                        </div>
                    @endif
                </section>

                <section class="sr-card sr-card-pad sr-polished-card" style="--polish-accent:#8b5cf6">
                    <div class="sr-polished-header">
                        <div class="sr-polished-icon"><i class="fa-solid fa-book-open-reader"></i></div>
                        <div>
                            <h5 class="sr-polished-title">Learning Progress</h5>
                            <p class="sr-polished-subtitle">Latest modules you are working through.</p>
                        </div>
                    </div>
                    @if($moduleCount > 0)
                        <div class="sr-learning-list">
                            @foreach($learningLabProgress as $prog)
                                @php $progVal = max(0, min(100, (int) $prog->progress)); @endphp
                                <div class="sr-learning-item" style="--accent: {{ $prog->color }}">
                                    <div class="sr-learning-icon"><i class="fa-solid {{ $prog->icon }}"></i></div>
                                    <div class="min-w-0">
                                        <div class="sr-learning-top">
                                            <div class="sr-learning-title">{{ $prog->title }}</div>
                                            <div class="sr-learning-score">{{ $progVal }}%</div>
                                        </div>
                                        <div class="sr-learning-bar"><span style="--value: {{ $progVal }}%"></span></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sr-polished-empty">
                            <div class="sr-polished-empty-inner">
                                <div class="sr-empty-visual"><i class="fa-solid fa-book-open"></i></div>
                                <p class="sr-polished-empty-text">Start a module to track your learning progress.</p>
                            </div>
                        </div>
                    @endif
                </section>
            </div>

            <div class="sr-two-col">
                <section id="card-ai-feedback" class="sr-card sr-card-pad sr-polished-card" style="--polish-accent:#3b82f6">
                    <div class="sr-polished-header">
                        <div class="sr-polished-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                        <div>
                            <h5 class="sr-polished-title">AI Feedback Summary</h5>
                            <p class="sr-polished-subtitle">A quick view of strengths and coaching priorities.</p>
                        </div>
                    </div>
                    @if(!empty($aiFeedback['strengths']) || !empty($aiFeedback['improvements']))
                        <div class="d-flex flex-column gap-3">
                            @if(!empty($aiFeedback['strengths']))
                                <div class="sr-insight-box">
                                    <div class="sr-insight-title"><i class="fa-solid fa-circle-check" style="color:#22c55e"></i> Top Strengths</div>
                                    <div class="sr-tag-list">
                                        @foreach($aiFeedback['strengths'] as $strength)
                                            <span class="sr-tag" style="background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.18);color:#22c55e">{{ $strength }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if(!empty($aiFeedback['improvements']))
                                <div class="sr-feedback-panel">
                                    <div class="sr-insight-title"><i class="fa-solid fa-arrow-trend-up" style="color:#f59e0b"></i> Improve Next</div>
                                    <div class="sr-feedback-chip-list">
                                        @foreach($aiFeedback['improvements'] as $improvement)
                                            <span class="sr-feedback-chip"><i class="fa-solid fa-circle-dot"></i>{{ $improvement }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="sr-polished-empty">
                            <div class="sr-polished-empty-inner">
                                <div class="sr-empty-visual"><i class="fa-solid fa-robot"></i></div>
                                <p class="sr-polished-empty-text">Complete a Philippine interview to generate AI feedback.</p>
                            </div>
                        </div>
                    @endif
                </section>

                <section id="card-ai-recommendations" class="sr-card sr-card-pad sr-polished-card" style="--polish-accent:#f59e0b">
                    <div class="sr-polished-header">
                        <div class="sr-polished-icon"><i class="fa-solid fa-lightbulb"></i></div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <h5 class="sr-polished-title">AI Recommendations</h5>
                                <span class="sr-rec-badge">Personalized for you</span>
                            </div>
                            <p class="sr-polished-subtitle">Next actions based on your performance.</p>
                        </div>
                    </div>
                    @if(isset($aiRecommendations) && count($aiRecommendations) > 0)
                        <div class="sr-rec-list">
                            @foreach($aiRecommendations as $rec)
                                <a href="{{ $rec->url ?? route('user.modules.index') }}" class="sr-recommendation-card" style="--accent: {{ $rec->color }}">
                                    <div class="sr-recommendation-icon"><i class="fa-solid {{ $rec->icon }}"></i></div>
                                    <div class="min-w-0">
                                        <div class="sr-recommendation-title">{{ $rec->text }}</div>
                                        @if(!empty($rec->reason))
                                            <div class="sr-recommendation-reason">{{ $rec->reason }}</div>
                                        @endif
                                    </div>
                                    <div class="sr-recommendation-next"><i class="fa-solid fa-chevron-right"></i></div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="sr-polished-empty">
                            <div class="sr-polished-empty-inner">
                                <div class="sr-empty-visual"><i class="fa-solid fa-lightbulb"></i></div>
                                <p class="sr-polished-empty-text">Complete a Philippine interview to get tailored recommendations.</p>
                            </div>
                        </div>
                    @endif
                </section>
            </div>

            <section id="card-recent-sessions" class="sr-card sr-card-pad sr-polished-card" style="--polish-accent:#06b6d4">
                <div class="sr-polished-header">
                    <div class="sr-polished-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <h5 class="sr-polished-title">Recent Sessions</h5>
                            <a href="{{ route('user.reports') }}" class="sr-plan-cta" style="margin-top:0;color:#2563eb">View All <i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                        <p class="sr-polished-subtitle">Review the latest completed Philippine mock interviews.</p>
                    </div>
                </div>
                <div class="sr-section-actions">
                    <a href="{{ route('user.reports') }}" class="sr-btn sr-section-action"><i class="fa-regular fa-rectangle-list"></i> View Reports</a>
                    @if(isset($recentSessions) && $recentSessions->count() > 0)
                        <form action="{{ route('user.sessions.clear') }}" method="POST" onsubmit="return confirm('Clear all completed interview sessions? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sr-btn sr-section-action danger w-100">
                                <i class="fa-solid fa-trash-can"></i> Clear All
                            </button>
                        </form>
                    @endif
                </div>

                <div class="table-responsive sr-sessions-table">
                    <table class="table custom-table mb-0 w-100">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Score</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSessions ?? [] as $session)
                                @php
                                    $sessionScore = $session->score ? (int) $session->score->overall_readiness_score : 0;
                                    $sessionColor = $sessionScore >= 80 ? '#22c55e' : ($sessionScore >= 60 ? '#f59e0b' : '#ef4444');
                                @endphp
                                <tr>
                                    <td>{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</td>
                                    <td><span class="sr-chip" style="background:rgba(59,130,246,.1);color:#60a5fa">{{ $session->category ? $session->category->title : 'Philippines Interview' }}</span></td>
                                    <td><span style="color:{{ $sessionColor }};font-weight:900">{{ $sessionScore }}%</span></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="{{ route('user.review', $session->id) }}" class="sr-btn sr-btn-primary" style="min-height:34px;padding:6px 11px;font-size:.78rem">Review</a>
                                            <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="sr-btn" title="Delete session" style="width:34px;min-height:34px;padding:0;color:#ef4444;border-color:rgba(239,68,68,.35)">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4" style="color:var(--tx3)">No recent sessions found. Start Philippine interview practice when you are ready.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="sr-sessions-mobile sr-session-list">
                    @forelse($recentSessions ?? [] as $session)
                        @php
                            $sessionScore = $session->score ? (int) $session->score->overall_readiness_score : 0;
                            $sessionColor = $sessionScore >= 80 ? '#22c55e' : ($sessionScore >= 60 ? '#f59e0b' : '#ef4444');
                        @endphp
                        <div class="sr-session-card-polished">
                            <div class="sr-session-icon"><i class="fa-solid fa-briefcase"></i></div>
                            <div class="sr-session-meta">
                                <div class="sr-session-title">{{ $session->category ? $session->category->title : 'Philippines Interview' }}</div>
                                <div class="sr-session-date">{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</div>
                            </div>
                            <div class="sr-session-score-stack" style="--score-color: {{ $sessionColor }}">
                                <span class="sr-session-score-pill">{{ $sessionScore }}%</span>
                                <div class="sr-session-score-bar"><span style="--score-value: {{ $sessionScore }}%"></span></div>
                            </div>
                            <a href="{{ route('user.review', $session->id) }}" class="sr-btn sr-btn-primary sr-session-review-btn">Review</a>
                            <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sr-btn sr-session-delete-btn" title="Delete session">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="sr-polished-empty">
                            <div class="sr-polished-empty-inner">
                                <div class="sr-empty-visual"><i class="fa-solid fa-calendar-plus"></i></div>
                                <p class="sr-polished-empty-text">No recent sessions found. Start Philippine interview practice when you are ready.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>

        <aside class="sr-side-stack">
            <section id="card-skill-radar" class="sr-card sr-card-pad sr-side-feature" style="--side-accent:#ec4899">
                <div class="sr-side-feature-header">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-solid fa-chart-simple"></i></div>
                        <div>
                            <h5 class="sr-side-title">Skill Radar</h5>
                            <p class="sr-side-subtitle">Average capability profile.</p>
                        </div>
                    </div>
                    <a href="{{ route('user.progress') }}" class="sr-side-detail-btn"><i class="fa-solid fa-chart-line"></i> View Details</a>
                </div>
                <div class="chart-container-mobile sr-radar-box">
                    <canvas id="radarChart"></canvas>
                </div>
            </section>

            <section id="card-daily-challenge" class="sr-card sr-card-pad sr-side-feature sr-challenge-feature">
                <div class="sr-side-feature-header mb-0">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-regular fa-calendar-check"></i></div>
                        <div>
                            <h5 class="sr-side-title" style="color:#2563eb">Today&apos;s Challenge</h5>
                        </div>
                    </div>
                    <div class="sr-challenge-star"><i class="fa-regular fa-star"></i></div>
                </div>
                <h5 class="sr-challenge-title">Answer 3 Philippine HR questions</h5>
                <p class="sr-challenge-copy">Earn extra XP, sharpen structure, and practice local role-fit answers.</p>
                <div class="sr-reward-row">
                    <span class="sr-reward-pill xp"><i class="fa-regular fa-star"></i> +60 XP</span>
                    <span class="sr-reward-pill streak"><i class="fa-solid fa-fire"></i> +1 Streak</span>
                </div>
                <a href="{{ route('interview.setup') }}" class="sr-btn sr-btn-primary w-100 sr-challenge-cta"><i class="fa-solid fa-play"></i> Start PH Challenge</a>
            </section>

            <section class="sr-card sr-card-pad sr-side-feature" style="--side-accent:#ef4444">
                <div class="sr-side-feature-header">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-solid fa-bullseye"></i></div>
                        <div>
                            <h5 class="sr-side-title">Current Goal</h5>
                            <p class="sr-side-subtitle">Progress toward your next readiness target.</p>
                        </div>
                    </div>
                </div>
                @if(isset($upcomingGoal))
                    <div class="sr-goal-panel">
                        <div class="sr-goal-main">
                            <div class="sr-goal-row">
                                <div class="sr-goal-title">{{ $upcomingGoal->title }}</div>
                                <div class="sr-goal-percent">{{ $goalPercent }}%</div>
                            </div>
                            <div class="sr-progress"><span style="--value: {{ $goalPercent }}%; background:linear-gradient(90deg,#22c55e,#0ea5e9)"></span></div>
                        </div>
                        <div class="sr-goal-footer">
                            <div class="sr-goal-note"><i class="fa-solid fa-chart-line"></i> You're just getting started!</div>
                            <a href="{{ route('user.progress') }}" class="sr-side-detail-btn">View Goals <i class="fa-solid fa-chevron-right"></i></a>
                        </div>
                    </div>
                @else
                    <div class="sr-polished-empty">
                        <div class="sr-polished-empty-inner">
                            <div class="sr-empty-visual"><i class="fa-solid fa-bullseye"></i></div>
                            <p class="sr-polished-empty-text">No current goal set.</p>
                        </div>
                    </div>
                @endif
            </section>

            <section class="sr-card sr-card-pad sr-side-feature" style="--side-accent:#f59e0b">
                <div class="sr-side-feature-header">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-solid fa-trophy"></i></div>
                        <div>
                            <h5 class="sr-side-title">Achievements</h5>
                            <p class="sr-side-subtitle">Milestones earned through practice.</p>
                        </div>
                    </div>
                    <a href="{{ route('user.progress') }}" class="sr-side-detail-btn">View All <i class="fa-solid fa-chevron-right"></i></a>
                </div>
                <div class="sr-achievement-showcase">
                    @php
                        $achievements = [
                            ['name' => 'First Interview', 'label' => 'First Interview', 'icon' => 'fa-medal', 'accent' => '#f59e0b', 'fallback' => 'Earned'],
                            ['name' => '3-Day Streak', 'label' => '3-Day Streak', 'icon' => 'fa-fire', 'accent' => '#ef4444', 'fallback' => 'In Progress'],
                            ['name' => 'STAR Master', 'label' => 'STAR Master', 'icon' => 'fa-star', 'accent' => '#2563eb', 'fallback' => 'In Progress'],
                            ['name' => 'Top Comm', 'label' => 'Top Comm', 'icon' => 'fa-bullhorn', 'accent' => '#22c55e', 'fallback' => 'Locked'],
                        ];
                    @endphp
                    @foreach($achievements as $achievement)
                        @php $earned = in_array($achievement['name'], $badgesEarned ?? []); @endphp
                        <div class="sr-achievement-tile" style="--accent: {{ $achievement['accent'] }}">
                            <div class="sr-achievement-tile-icon"><i class="fa-solid {{ $achievement['icon'] }}"></i></div>
                            <div class="sr-achievement-tile-title">{{ $achievement['label'] }}</div>
                            <div class="sr-achievement-status">
                                @if(! $earned && $achievement['fallback'] === 'Locked')<i class="fa-solid fa-lock"></i>@endif
                                {{ $earned ? 'Earned' : $achievement['fallback'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="sr-card sr-card-pad sr-side-feature" style="--side-accent:#6366f1">
                <div class="sr-side-feature-header">
                    <div class="sr-side-title-row">
                        <div class="sr-side-icon"><i class="fa-solid fa-bell"></i></div>
                        <div>
                            <h5 class="sr-side-title">Notifications</h5>
                            <p class="sr-side-subtitle">Recent updates and reminders.</p>
                        </div>
                    </div>
                    <a href="{{ route('user.notifications') }}" class="sr-side-detail-btn">View All <i class="fa-solid fa-chevron-right"></i></a>
                </div>
                @if(isset($recentNotifications) && count($recentNotifications) > 0)
                    <div class="sr-notification-list-polished">
                        @foreach($recentNotifications as $notif)
                            <div class="sr-notification-card">
                                <div class="sr-notification-card-icon"><i class="fa-solid {{ $notif->data['icon'] ?? 'fa-bell' }}"></i></div>
                                <div class="min-w-0">
                                    <div class="sr-notification-title">{{ $notif->data['title'] ?? 'Notification' }}</div>
                                    <div class="sr-notification-message">{{ $notif->data['message'] ?? '' }}</div>
                                </div>
                                <div class="sr-notification-meta">
                                    <span>{{ $notif->created_at ? $notif->created_at->diffForHumans(null, true, true) : '' }}</span>
                                    <span class="sr-notification-dot"></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="sr-polished-empty">
                        <div class="sr-polished-empty-inner">
                            <div class="sr-empty-visual"><i class="fa-solid fa-bell-slash"></i></div>
                            <p class="sr-polished-empty-text">No new notifications.</p>
                        </div>
                    </div>
                @endif
            </section>
        </aside>
    </div>
</div>

<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Chart === 'undefined') return;

    const rootStyle = getComputedStyle(document.documentElement);
    const getThemeColor = (varName, fallback) => rootStyle.getPropertyValue(varName).trim() || fallback;
    const isLightMode = document.documentElement.classList.contains('lm');
    const txColor = isLightMode ? '#334155' : getThemeColor('--tx2', '#dbeafe');
    const mutedColor = isLightMode ? '#64748b' : getThemeColor('--tx3', '#a8b4c7');
    const surfaceColor = isLightMode ? '#ffffff' : getThemeColor('--sf', '#171d2d');
    const gridColor = isLightMode ? 'rgba(100,116,139,0.28)' : 'rgba(219,234,254,0.42)';
    const trendLineColor = isLightMode ? '#2563eb' : '#60a5fa';
    const trendPointFill = isLightMode ? '#ffffff' : '#0f172a';
    const radarGridColor = isLightMode ? 'rgba(100,116,139,0.32)' : 'rgba(219,234,254,0.58)';
    const radarAngleColor = isLightMode ? 'rgba(100,116,139,0.34)' : 'rgba(219,234,254,0.42)';
    const radarGridWidth = isLightMode ? 1.25 : 1.6;
    const radarLabelColor = isLightMode ? '#334155' : '#e2e8f0';

    Chart.defaults.color = txColor;
    Chart.defaults.font.family = "'Poppins', sans-serif";

    const emptyChartPlugin = {
        id: 'emptyChartMessage',
        afterDraw(chart, args, options) {
            const datasets = chart.data.datasets || [];
            const hasValues = datasets.some((dataset) => {
                return (dataset.data || []).some((value) => Number(value) > 0);
            });

            if (hasValues && !options?.force) return;
            if (!options?.text) return;

            const { ctx, chartArea } = chart;
            if (!chartArea) return;

            ctx.save();
            ctx.fillStyle = options?.color || mutedColor;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = "700 13px 'Poppins', sans-serif";
            ctx.fillText(
                options?.text || 'Complete a scored interview to see this chart.',
                (chartArea.left + chartArea.right) / 2,
                (chartArea.top + chartArea.bottom) / 2
            );
            ctx.restore();
        }
    };

    Chart.register(emptyChartPlugin);

    const progressCanvas = document.getElementById('progressChart');
    if (progressCanvas) {
        const progressCtx = progressCanvas.getContext('2d');
        const chartDataObj = {
            recent: {
                labels: {!! json_encode(collect($scoreTrend ?? [])->pluck('date')) !!},
                data: {!! json_encode(collect($scoreTrend ?? [])->pluck('score')) !!}
            }
        };
        const trendRangeSelect = document.getElementById('readinessTrendRange');
        const isCompactTrend = () => window.matchMedia('(max-width: 575px)').matches;
        const trendSlice = (count) => {
            const range = Number(count || 10);
            return {
                labels: chartDataObj.recent.labels.slice(-range),
                data: chartDataObj.recent.data.slice(-range)
            };
        };
        const initialTrendRange = isCompactTrend() ? 5 : 10;

        if (trendRangeSelect) {
            trendRangeSelect.value = String(initialTrendRange);
        }

        const initialTrend = trendSlice(initialTrendRange);

        const gradientLine = progressCtx.createLinearGradient(0, 0, 0, 320);
        gradientLine.addColorStop(0, isLightMode ? 'rgba(37, 99, 235, 0.24)' : 'rgba(96, 165, 250, 0.28)');
        gradientLine.addColorStop(0.58, isLightMode ? 'rgba(59, 130, 246, 0.10)' : 'rgba(96, 165, 250, 0.14)');
        gradientLine.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

        const progressChart = new Chart(progressCtx, {
            type: 'line',
            data: {
                labels: initialTrend.labels.length ? initialTrend.labels : ['No Data'],
                datasets: [{
                    label: 'Readiness Score',
                    data: initialTrend.data.length ? initialTrend.data : [0],
                    borderColor: trendLineColor,
                    backgroundColor: gradientLine,
                    borderWidth: isCompactTrend() ? 2 : 3,
                    tension: 0.38,
                    fill: true,
                    pointBackgroundColor: trendPointFill,
                    pointBorderColor: trendLineColor,
                    pointBorderWidth: isCompactTrend() ? 2 : 3,
                    pointRadius: isCompactTrend() ? 3 : 5,
                    pointHoverRadius: isCompactTrend() ? 5 : 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    emptyChartMessage: {
                        color: mutedColor,
                        text: ''
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: isLightMode ? '#ffffff' : 'rgba(15, 23, 42, 0.94)',
                        titleColor: isLightMode ? '#0f172a' : '#fff',
                        bodyColor: isLightMode ? '#334155' : '#dbeafe',
                        borderColor: trendLineColor,
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return ' Readiness Score: ' + context.parsed.y + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 25,
                            padding: isCompactTrend() ? 4 : 8,
                            color: txColor,
                            font: { size: isCompactTrend() ? 10 : 12, weight: 600 }
                        },
                        grid: { color: gridColor, lineWidth: isLightMode ? 1.1 : 1.35, borderDash: [6, 6], drawTicks: false },
                        border: { display: false }
                    },
                    x: {
                        ticks: {
                            padding: isCompactTrend() ? 6 : 10,
                            font: { size: isCompactTrend() ? 9 : 11, weight: 600 },
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: isCompactTrend() ? 4 : 8,
                            color: txColor
                        },
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });

        const applyTrendRange = (range) => {
            const nextTrend = trendSlice(range);
            progressChart.data.labels = nextTrend.labels.length ? nextTrend.labels : ['No Data'];
            progressChart.data.datasets[0].data = nextTrend.data.length ? nextTrend.data : [0];
            progressChart.update();
        };

        trendRangeSelect?.addEventListener('change', (event) => {
            applyTrendRange(event.target.value);
        });

        window.addEventListener('resize', () => {
            const compact = isCompactTrend();
            progressChart.data.datasets[0].borderWidth = compact ? 2 : 3;
            progressChart.data.datasets[0].pointBorderWidth = compact ? 2 : 3;
            progressChart.data.datasets[0].pointRadius = compact ? 3 : 5;
            progressChart.data.datasets[0].pointHoverRadius = compact ? 5 : 6;
            progressChart.options.scales.y.ticks.padding = compact ? 4 : 8;
            progressChart.options.scales.y.ticks.font.size = compact ? 10 : 12;
            progressChart.options.scales.x.ticks.padding = compact ? 6 : 10;
            progressChart.options.scales.x.ticks.font.size = compact ? 9 : 11;
            progressChart.options.scales.x.ticks.maxTicksLimit = compact ? 4 : 8;
            progressChart.update('none');
        });
    }

    const radarCanvas = document.getElementById('radarChart');
    if (radarCanvas) {
        const radarScores = [
            {{ (int) ($radarData['clarity'] ?? 0) }},
            {{ (int) ($radarData['relevance'] ?? 0) }},
            {{ (int) ($radarData['grammar'] ?? 0) }},
            {{ (int) ($radarData['professionalism'] ?? 0) }},
            {{ (int) ($radarData['delivery_stability'] ?? 0) }}
        ];
        const hasRadarScores = radarScores.some((value) => Number(value) > 0);
        const radarDisplayScores = hasRadarScores ? radarScores : [35, 35, 35, 35, 35];

        new Chart(radarCanvas.getContext('2d'), {
            type: 'radar',
            data: {
                labels: ['Clarity', 'Relevance', 'Grammar', 'Professionalism', 'Delivery Stability'],
                datasets: [{
                    label: 'Score Level',
                    data: radarDisplayScores,
                    backgroundColor: hasRadarScores ? (isLightMode ? 'rgba(236, 72, 153, 0.18)' : 'rgba(244, 114, 182, 0.22)') : (isLightMode ? 'rgba(236, 72, 153, 0.1)' : 'rgba(244, 114, 182, 0.16)'),
                    borderColor: hasRadarScores ? (isLightMode ? '#db2777' : '#f472b6') : (isLightMode ? 'rgba(219, 39, 119, 0.72)' : 'rgba(251, 113, 133, 0.92)'),
                    pointBackgroundColor: hasRadarScores ? (isLightMode ? '#db2777' : '#f472b6') : (isLightMode ? '#ec4899' : '#fecdd3'),
                    pointBorderColor: surfaceColor,
                    pointHoverBackgroundColor: surfaceColor,
                    pointHoverBorderColor: '#ec4899',
                    borderWidth: hasRadarScores ? 2 : 1.5,
                    borderDash: hasRadarScores ? [] : [6, 5]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    emptyChartMessage: {
                        color: mutedColor,
                        force: false,
                        text: ''
                    }
                },
                scales: {
                    r: {
                        angleLines: { color: radarAngleColor, lineWidth: radarGridWidth },
                        grid: { color: radarGridColor, lineWidth: radarGridWidth },
                        pointLabels: { color: radarLabelColor, font: { size: 10, weight: 800 } },
                        suggestedMin: 0,
                        suggestedMax: 100,
                        ticks: { display: false, stepSize: 20 }
                    }
                }
            }
        });
    }
});
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const completionKey = 'onboarding_completed';
        const serverDetectedMobile = @json($isMobile);

        const stepsMobile = [
            { element: '#mobTutorialBtn', popover: { title: 'Replay The Tour', description: 'Use this anytime you want a quick walkthrough of the current page.', side: 'bottom', align: 'end' }},
            { element: '#mob-bottom-nav', popover: { title: 'Mobile Navigation', description: 'Jump to Home, Progress, Interview, Feedback, or Profile from the bottom bar.', side: 'top', align: 'center' }},
            { element: '.sr-score-panel', popover: { title: 'Readiness Summary', description: 'Your current readiness score, status, average rating, and next target live here.', side: 'bottom', align: 'start' }},
            { element: '.sr-mobile-stat-grid', popover: { title: 'Practice Snapshot', description: 'Track sessions, rating, XP, and streak without opening a report.', side: 'top', align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Readiness Trend', description: 'See how your score changes across your latest completed sessions.', side: 'top', align: 'start' }},
            { element: '#card-ai-feedback', popover: { title: 'AI Feedback Summary', description: 'Review your strongest patterns and the next skills to tighten up.', side: 'top', align: 'start' }},
            { element: '#card-ai-recommendations', popover: { title: 'AI Recommendations', description: 'Use these next actions to decide what to practice first.', side: 'top', align: 'start' }},
            { element: '#card-recent-sessions', popover: { title: 'Recent Sessions', description: 'Open past interviews, review feedback, or clear old records.', side: 'top', align: 'start' }},
            { element: '#card-daily-challenge', popover: { title: 'Daily Challenge', description: 'Start a focused practice task for extra XP and streak progress.', side: 'top', align: 'start' }},
            { element: '#mobThBtn', popover: { title: 'Theme Toggle', description: 'Switch between light and dark mode for a comfortable view.', side: 'bottom', align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#dbSidebar', popover: { title: 'Navigation Menu', description: 'Open Philippine Mock Interview, modules, Voice Rehearsal, AI Coach, reports, and more from here.', side: 'right', align: 'start' }},
            { element: '#dbTutorialBtn', popover: { title: 'Replay The Tour', description: 'Use this button whenever you want to restart the walkthrough.', side: 'bottom', align: 'center' }},
            { element: '.sr-score-panel', popover: { title: 'Readiness Summary', description: 'Your current readiness score, status, average rating, and next target live here.', side: 'bottom', align: 'start' }},
            { element: '.sr-stats-desktop', popover: { title: 'Practice Snapshot', description: 'Track completed sessions, rating, XP, and active practice days at a glance.', side: 'top', align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Readiness Trend', description: 'See how your score changes across your latest completed sessions.', side: 'top', align: 'start' }},
            { element: '#card-ai-feedback', popover: { title: 'AI Feedback Summary', description: 'Review your strongest patterns and the next skills to tighten up.', side: 'top', align: 'start' }},
            { element: '#card-ai-recommendations', popover: { title: 'AI Recommendations', description: 'Use these next actions to decide what to practice first.', side: 'bottom', align: 'start' }},
            { element: '#card-recent-sessions', popover: { title: 'Recent Sessions', description: 'Open past interviews, review feedback, or clear old records.', side: 'top', align: 'start' }},
            { element: '#card-daily-challenge', popover: { title: 'Daily Challenge', description: 'Start a focused practice task for extra XP and streak progress.', side: 'left', align: 'start' }},
            { element: '#dbThBtn', popover: { title: 'Theme Toggle', description: 'Switch between light and dark mode for a comfortable viewing experience.', side: 'bottom', align: 'center' }},
            { element: '#notifWrap', popover: { title: 'Notifications', description: 'Stay updated with interview feedback and platform announcements.', side: 'bottom', align: 'center' }},
            { element: '#profileWrap', popover: { title: 'Your Profile', description: 'Manage account settings, notifications, and sign-out options.', side: 'bottom', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey,
            serverDetectedMobile,
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 350,
        });

    });
</script>
@endpush

@endsection
