@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
@php
    $scoreVal = (int) round($profile->readiness_score ?? $avgScore ?? 0);
    $scoreVal = max(0, min(100, $scoreVal));
    $scoreClass = $scoreVal >= 80 ? 'score-high' : ($scoreVal >= 60 ? 'score-med' : 'score-low');
    $scoreText = $scoreVal >= 80 ? 'Interview Ready' : ($scoreVal >= 60 ? 'Building Momentum' : 'Practice Mode');
    $scoreIcon = $scoreVal >= 80 ? 'fa-circle-check' : ($scoreVal >= 60 ? 'fa-chart-line' : 'fa-arrow-trend-up');
    $fullName = trim(Auth::user()->name ?? '') ?: 'User';
    $nameParts = preg_split('/\s+/', $fullName);
    $firstName = $nameParts[0] ?? 'User';
    $lastName = count($nameParts) > 1 ? $nameParts[count($nameParts) - 1] : '';
    $welcomeName = trim($firstName . ' ' . $lastName);
    $rating = round(($avgScore ?? 0) / 20, 1);
    $goalPercent = isset($upcomingGoal) ? max(0, min(100, round($upcomingGoal->percent ?? 0))) : 0;
    $categoryCount = isset($categoryPerformance) ? count($categoryPerformance) : 0;
    $moduleCount = isset($learningLabProgress) ? count($learningLabProgress) : 0;
    $avatarUrl = null;
    if (Auth::check() && Auth::user()->profile_photo_path) {
        $photoPath = Auth::user()->profile_photo_path;
        $avatarUrl = (str_starts_with($photoPath, 'http') || str_starts_with($photoPath, 'data:')) ? $photoPath : asset('storage/' . $photoPath);
    }
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
    }

    .sr-dashboard {
        display: flex;
        flex-direction: column;
        gap: var(--dash-section-gap);
        padding-top: 10px !important;
        padding-bottom: 28px !important;
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
        overflow: hidden;
        background:
            linear-gradient(135deg, rgba(59, 130, 246, 0.14), rgba(6, 182, 212, 0.06)),
            var(--sf);
        border-color: rgba(59, 130, 246, 0.2);
        margin-bottom: 2px;
    }

    .sr-hero-card::after {
        content: "";
        position: absolute;
        inset: auto -90px -120px auto;
        width: 260px;
        height: 260px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.22), transparent 68%);
        pointer-events: none;
    }

    .sr-hero-inner {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
        padding: var(--dash-card-pad);
    }

    .sr-user-row {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .sr-avatar-xl {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.45rem;
        font-weight: 800;
        overflow: hidden;
        flex: 0 0 auto;
        border: 1px solid rgba(255,255,255,.16);
    }

    .sr-avatar-xl img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
        font-size: clamp(1.05rem, 1.7vw, 1.35rem);
        line-height: 1.25;
        font-weight: 700;
        color: var(--tx);
    }

    .sr-title-name {
        color: var(--dash-primary);
        font-weight: 800;
    }

    .sr-subtitle {
        margin: 8px 0 0;
        color: var(--tx2);
        font-size: 0.95rem;
        max-width: 680px;
    }

    .sr-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 18px;
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

    .sr-mobile-short {
        display: none;
    }

    .sr-mobile-full {
        display: inline;
    }

    .sr-score-panel {
        background:
            linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(34, 197, 94, 0.04)),
            var(--sf);
        border: 1px solid rgba(59, 130, 246, 0.16);
        border-radius: var(--dash-card-radius);
        padding: var(--dash-card-pad);
    }

    .lm .sr-score-panel {
        background:
            linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(34, 197, 94, 0.045)),
            var(--sf);
        border-color: rgba(15, 23, 42, 0.08);
    }

    .sr-score-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    .sr-score-value {
        font-size: clamp(3.1rem, 6vw, 4.3rem);
        line-height: 1;
        font-weight: 900;
        color: var(--tx);
    }

    .sr-score-value span {
        font-size: 1.45rem;
        color: var(--tx3);
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
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 15px;
    }

    .sr-score-meta-item {
        border-radius: 12px;
        padding: 10px;
        background: rgba(255,255,255,.045);
        border: 1px solid rgba(255,255,255,.07);
    }

    .lm .sr-score-meta-item {
        background: rgba(15, 23, 42, 0.035);
        border-color: rgba(15, 23, 42, 0.06);
    }

    .sr-meta-label {
        color: var(--tx3);
        font-size: 0.72rem;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .sr-meta-value {
        color: var(--tx);
        font-size: 1rem;
        font-weight: 800;
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
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 16px;
        min-height: 132px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: var(--shadow-soft, 0 10px 28px rgba(0,0,0,.12));
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
        background: color-mix(in srgb, var(--accent, #60a5fa) 13%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent, #60a5fa) 22%, transparent);
    }

    .sr-stat-value {
        font-size: 1.65rem;
        line-height: 1;
        font-weight: 900;
        color: var(--tx);
        margin-top: 18px;
    }

    .sr-stat-label {
        color: var(--tx3);
        font-size: 0.78rem;
        font-weight: 700;
        margin-top: 6px;
    }

    .sr-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
    }

    .sr-card-title {
        margin: 0;
        color: var(--tx);
        font-size: 1rem;
        font-weight: 800;
    }

    .sr-card-kicker {
        color: var(--tx3);
        font-size: 0.8rem;
        margin-top: 4px;
    }

    .chart-container-mobile,
    .sr-chart-box {
        position: relative;
        width: 100%;
        height: 280px;
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
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .sr-achievement {
        border: 1px solid var(--bd);
        border-radius: 14px;
        background: var(--sf2, var(--bg3));
        padding: 12px;
        min-height: 104px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
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

        .sr-score-panel {
            max-width: none;
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
            --dash-section-gap: 16px;
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
            gap: var(--dash-section-gap) !important;
            margin: 0;
        }

        .sr-card {
            border-radius: var(--dash-card-radius);
        }

        .sr-hero-card {
            border-radius: var(--dash-card-radius);
            margin-bottom: 0;
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
            gap: var(--dash-section-gap);
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
            gap: 12px;
            padding-bottom: 12px;
        }

        .sr-user-row {
            align-items: flex-start;
        }

        .sr-avatar-xl {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 1.15rem;
        }

        .sr-hero-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-top: 14px;
        }

        .sr-hero-actions .sr-btn {
            min-width: 0;
            width: 100%;
            min-height: 38px;
            border-radius: 10px;
            gap: 5px;
            padding: 8px 6px;
            font-size: 0.72rem;
            line-height: 1.15;
            white-space: normal;
            text-align: center;
        }

        .sr-hero-actions .sr-btn i {
            font-size: 0.78rem;
            flex: 0 0 auto;
        }

        .sr-mobile-short {
            display: inline;
        }

        .sr-mobile-full {
            display: none;
        }

        .sr-score-meta {
            grid-template-columns: 1fr;
        }

        .sr-score-panel {
            width: 100%;
            max-width: none;
            padding: var(--dash-card-pad);
            border-radius: var(--dash-card-radius);
        }

        .sr-score-top {
            gap: 8px;
            flex-wrap: wrap;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .sr-score-value {
            font-size: clamp(2.75rem, 13vw, 3.35rem);
        }

        .sr-score-meta {
            gap: 8px;
            margin-top: 12px;
        }

        .sr-score-meta-item {
            padding: 9px 10px;
        }

        .sr-stat-card {
            min-height: 118px;
            padding: 12px;
            border-radius: 14px;
            gap: 10px;
            justify-content: flex-start;
        }

        .sr-stat-head {
            min-height: 32px;
        }

        .sr-stat-card > div:last-child {
            margin-top: auto;
        }

        .sr-stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            font-size: 0.84rem;
        }

        .sr-stat-card .sr-chip {
            padding: 5px 8px;
            font-size: 0.66rem;
        }

        .sr-stat-value {
            margin-top: 0;
            font-size: 1.22rem;
        }

        .sr-stat-value span {
            font-size: 0.78rem !important;
        }

        .sr-stat-label {
            margin-top: 4px;
            font-size: 0.68rem;
            line-height: 1.25;
        }

        .sr-mobile-stat-grid .sr-stat-card {
            min-width: 0;
            min-height: 104px;
            height: 100%;
            padding: 12px;
            border-radius: 14px;
            gap: 8px;
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
            font-size: 0.76rem;
        }

        .sr-mobile-stat-grid .sr-chip {
            max-width: calc(100% - 34px);
            padding: 4px 6px;
            font-size: 0.58rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sr-mobile-stat-grid .sr-stat-card > div:last-child {
            min-width: 0;
            margin-top: auto;
        }

        .sr-mobile-stat-grid .sr-stat-value {
            font-size: 1.12rem;
        }

        .sr-mobile-stat-grid .sr-stat-label {
            overflow-wrap: anywhere;
        }

        .chart-container-mobile,
        .sr-chart-box {
            height: 230px;
        }

        .sr-btn,
        .sr-chip,
        .sr-status-pill {
            max-width: 100%;
        }

        .sr-card-header {
            align-items: stretch;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 12px;
        }

        .sr-card-title {
            font-size: 0.96rem;
            line-height: 1.25;
            overflow-wrap: anywhere;
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

        .sr-session-card {
            align-items: stretch;
            flex-direction: column;
        }

        .sr-session-actions {
            justify-content: space-between;
            flex-wrap: wrap;
            width: 100%;
        }

        .sr-session-actions .sr-btn-primary {
            flex: 1 1 auto;
        }

        #card-recent-sessions .sr-card-header > .d-flex {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(132px, 1fr));
            gap: 8px !important;
            width: 100%;
        }

        #card-recent-sessions .sr-card-header form,
        #card-recent-sessions .sr-card-header .sr-btn {
            width: 100%;
        }

        #card-progress-chart.sr-card-pad {
            padding: var(--dash-card-pad);
        }

        #card-progress-chart .sr-card-header {
            gap: 8px;
            margin-bottom: 10px;
        }

        #card-progress-chart .sr-card-title {
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            line-height: 1.2;
        }

        #card-progress-chart .sr-card-kicker {
            font-size: 0.72rem;
            line-height: 1.3;
        }

        #card-progress-chart .sr-card-header > .sr-chip {
            align-self: flex-start;
            padding: 5px 8px;
            font-size: 0.66rem;
        }

        #card-progress-chart .sr-chart-box {
            height: 214px;
            margin-top: 2px;
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
        .stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 12px !important;
        }

        .sr-mobile-stat-grid {
            gap: 12px;
        }

        .sr-achievement-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 360px) {
        #mob-content > .db-content {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .sr-hero-actions {
            gap: 6px;
        }

        .sr-hero-actions .sr-btn {
            min-height: 34px;
            padding: 6px 4px;
            font-size: 0.66rem;
        }

        .sr-hero-actions .sr-btn i {
            font-size: 0.72rem;
        }

        .sr-stat-card {
            min-height: 112px;
            padding: 10px;
        }

        .sr-stat-card .sr-chip {
            padding: 4px 7px;
            font-size: 0.62rem;
        }

        .sr-mobile-stat-grid {
            gap: 12px;
            padding: 0;
        }

        .sr-mobile-stat-grid .sr-stat-card {
            min-height: 104px;
            padding: 12px;
        }

        .sr-mobile-stat-grid .sr-chip {
            padding: 4px 5px;
            font-size: 0.56rem;
        }

        #card-progress-chart .sr-chart-box {
            height: 202px;
        }
    }
</style>

<div class="db-section active sr-dashboard" id="sec-overview">
    <div class="sr-summary-grid">
        <section class="sr-card sr-hero-card card-grad-success">
            <div class="sr-hero-inner">
                <div class="sr-user-row">
                    <div class="sr-avatar-xl">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="Avatar">
                        @else
                            {{ strtoupper(substr(Auth::user()->name ?? 'User', 0, 1)) }}
                        @endif
                    </div>
                    <div>
                        <h6 class="sr-title">Welcome back, <span class="sr-title-name">{{ $welcomeName }}</span>.</h6>
                        <p class="sr-subtitle">Track readiness, progress, and coaching.</p>
                    </div>
                </div>
                <div class="sr-hero-actions">
                    <a href="{{ route('interview.setup') }}" class="sr-btn sr-btn-primary"><i class="fa-solid fa-microphone-lines"></i> <span class="sr-mobile-full">Start Mock Interview</span><span class="sr-mobile-short">Mock</span></a>
                    <a href="{{ route('user.progress') }}" class="sr-btn"><i class="fa-solid fa-chart-line"></i> <span class="sr-mobile-full">View Progress</span><span class="sr-mobile-short">Progress</span></a>
                    <a href="{{ route('user.coach') }}" class="sr-btn"><i class="fa-solid fa-robot"></i> <span class="sr-mobile-full">Ask AI Coach</span><span class="sr-mobile-short">Coach</span></a>
                    <a href="{{ route('user.ai-collaboration') }}" class="sr-btn"><i class="fa-solid fa-wand-magic-sparkles"></i> <span class="sr-mobile-full">AI Collaboration</span><span class="sr-mobile-short">AI Lab</span></a>
                </div>
            </div>
        </section>

        <section class="sr-card sr-score-panel" aria-label="Readiness score">
            <div class="sr-score-top">
                <span class="sr-status-pill {{ $scoreClass }}"><i class="fa-solid {{ $scoreIcon }}"></i> {{ $scoreText }}</span>
                <span class="sr-chip" style="background:rgba(59,130,246,.11);color:#60a5fa;border:1px solid rgba(59,130,246,.2)">Live score</span>
            </div>
            <div class="sr-score-value">{{ $scoreVal }}<span>%</span></div>
            <div class="sr-progress mt-3" aria-label="Readiness score"><span style="--value: {{ $scoreVal }}%"></span></div>
            <div class="sr-score-meta">
                <div class="sr-score-meta-item">
                    <div class="sr-meta-label">Avg rating</div>
                    <div class="sr-meta-value">{{ $rating }}/5</div>
                </div>
                <div class="sr-score-meta-item">
                    <div class="sr-meta-label">Next goal</div>
                    <div class="sr-meta-value">{{ isset($upcomingGoal) ? ($upcomingGoal->target ?? 100) : 100 }}%</div>
                </div>
            </div>
        </section>
    </div>

    <div class="sr-mobile-stat-grid" role="group" aria-label="Quick statistics">
        <div class="sr-stat-card">
            <div class="sr-stat-head">
                <div class="sr-stat-icon" style="--accent:#3b82f6"><i class="fa-solid fa-microphone"></i></div>
                <span class="sr-chip" style="background:rgba(59,130,246,.1);color:#60a5fa">Practice</span>
            </div>
            <div>
                <div class="sr-stat-value">{{ $totalSessions ?? 0 }}</div>
                <div class="sr-stat-label">Completed sessions</div>
            </div>
        </div>
        <div class="sr-stat-card">
            <div class="sr-stat-head">
                <div class="sr-stat-icon" style="--accent:#22c55e"><i class="fa-solid fa-star-half-stroke"></i></div>
                <span class="sr-chip" style="background:rgba(34,197,94,.1);color:#22c55e">Quality</span>
            </div>
            <div>
                <div class="sr-stat-value">{{ $rating }}<span style="font-size:.9rem;color:var(--tx3)">/5</span></div>
                <div class="sr-stat-label">Average rating</div>
            </div>
        </div>
        <div class="sr-stat-card">
            <div class="sr-stat-head">
                <div class="sr-stat-icon" style="--accent:#06b6d4"><i class="fa-solid fa-bolt"></i></div>
                <span class="sr-chip" style="background:rgba(6,182,212,.1);color:#06b6d4">Growth</span>
            </div>
            <div>
                <div class="sr-stat-value">{{ number_format($experiencePoints ?? 0) }}</div>
                <div class="sr-stat-label">Experience points</div>
            </div>
        </div>
        <div class="sr-stat-card">
            <div class="sr-stat-head">
                <div class="sr-stat-icon" style="--accent:#f59e0b"><i class="fa-solid fa-fire"></i></div>
                <span class="sr-chip" style="background:rgba(245,158,11,.1);color:#f59e0b">Streak</span>
            </div>
            <div>
                <div class="sr-stat-value">{{ $currentStreak ?? 0 }}</div>
                <div class="sr-stat-label">Active practice days</div>
            </div>
        </div>
    </div>

    <div class="stat-grid sr-stats-desktop" role="group" aria-label="Quick statistics">
        <div class="sr-stat-card">
            <div class="sr-stat-head">
                <div class="sr-stat-icon" style="--accent:#3b82f6"><i class="fa-solid fa-microphone"></i></div>
                <span class="sr-chip" style="background:rgba(59,130,246,.1);color:#60a5fa">Practice</span>
            </div>
            <div>
                <div class="sr-stat-value">{{ $totalSessions ?? 0 }}</div>
                <div class="sr-stat-label">Completed sessions</div>
            </div>
        </div>
        <div class="sr-stat-card">
            <div class="sr-stat-head">
                <div class="sr-stat-icon" style="--accent:#22c55e"><i class="fa-solid fa-star-half-stroke"></i></div>
                <span class="sr-chip" style="background:rgba(34,197,94,.1);color:#22c55e">Quality</span>
            </div>
            <div>
                <div class="sr-stat-value">{{ $rating }}<span style="font-size:.9rem;color:var(--tx3)">/5</span></div>
                <div class="sr-stat-label">Average rating</div>
            </div>
        </div>
        <div class="sr-stat-card">
            <div class="sr-stat-head">
                <div class="sr-stat-icon" style="--accent:#06b6d4"><i class="fa-solid fa-bolt"></i></div>
                <span class="sr-chip" style="background:rgba(6,182,212,.1);color:#06b6d4">Growth</span>
            </div>
            <div>
                <div class="sr-stat-value">{{ number_format($experiencePoints ?? 0) }}</div>
                <div class="sr-stat-label">Experience points</div>
            </div>
        </div>
        <div class="sr-stat-card">
            <div class="sr-stat-head">
                <div class="sr-stat-icon" style="--accent:#f59e0b"><i class="fa-solid fa-fire"></i></div>
                <span class="sr-chip" style="background:rgba(245,158,11,.1);color:#f59e0b">Streak</span>
            </div>
            <div>
                <div class="sr-stat-value">{{ $currentStreak ?? 0 }}</div>
                <div class="sr-stat-label">Active practice days</div>
            </div>
        </div>
    </div>

    <div class="sr-dashboard-shell">
        <main class="sr-main-stack">
            <section id="card-progress-chart" class="sr-card sr-card-pad">
                <div class="sr-card-header">
                    <div>
                        <h2 class="sr-card-title"><i class="fa-solid fa-chart-line me-2" style="color:#60a5fa"></i> Readiness Trend</h2>
                        <div class="sr-card-kicker">Recent completed sessions, scored from 0 to 100.</div>
                    </div>
                    <span class="sr-chip" style="background:rgba(59,130,246,.1);color:#60a5fa;border:1px solid rgba(59,130,246,.18)">Recent 10</span>
                </div>
                <div class="sr-chart-box">
                    <canvas id="progressChart"></canvas>
                </div>
            </section>

            <div class="sr-two-col">
                <section class="sr-card sr-card-pad">
                    <div class="sr-card-header">
                        <div>
                            <h2 class="sr-card-title">Category Performance</h2>
                            <div class="sr-card-kicker">Where your interview scores are strongest.</div>
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
                        <div class="sr-empty">
                            <i class="fa-solid fa-folder-open"></i>
                            <div>Complete a session to unlock category performance.</div>
                        </div>
                    @endif
                </section>

                <section class="sr-card sr-card-pad">
                    <div class="sr-card-header">
                        <div>
                            <h2 class="sr-card-title">Learning Progress</h2>
                            <div class="sr-card-kicker">Latest modules you are working through.</div>
                        </div>
                    </div>
                    @if($moduleCount > 0)
                        <div class="sr-progress-list">
                            @foreach($learningLabProgress as $prog)
                                @php $progVal = max(0, min(100, (int) $prog->progress)); @endphp
                                <div class="sr-module-item">
                                    <div class="sr-list-icon" style="--accent: {{ $prog->color }}"><i class="fa-solid {{ $prog->icon }}"></i></div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="sr-progress-row">
                                            <div class="sr-progress-name">{{ $prog->title }}</div>
                                            <div class="sr-progress-score">{{ $progVal }}%</div>
                                            <div class="sr-progress"><span style="--value: {{ $progVal }}%; background: {{ $prog->color }}"></span></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sr-empty">
                            <i class="fa-solid fa-book-open"></i>
                            <div>Start a module to track your learning progress.</div>
                        </div>
                    @endif
                </section>
            </div>

            <div class="sr-two-col">
                <section class="sr-card sr-card-pad">
                    <div class="sr-card-header">
                        <div>
                            <h2 class="sr-card-title"><i class="fa-solid fa-wand-magic-sparkles me-2" style="color:#60a5fa"></i> AI Feedback Summary</h2>
                            <div class="sr-card-kicker">A quick view of strengths and coaching priorities.</div>
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
                                <div class="sr-insight-box">
                                    <div class="sr-insight-title"><i class="fa-solid fa-arrow-trend-up" style="color:#f59e0b"></i> Improve Next</div>
                                    <div class="sr-tag-list">
                                        @foreach($aiFeedback['improvements'] as $improvement)
                                            <span class="sr-tag" style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.18);color:#f59e0b">{{ $improvement }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="sr-empty">
                            <i class="fa-solid fa-robot"></i>
                            <div>Complete an interview to generate AI feedback.</div>
                        </div>
                    @endif
                </section>

                <section id="card-ai-recommendations" class="sr-card sr-card-pad">
                    <div class="sr-card-header">
                        <div>
                            <h2 class="sr-card-title"><i class="fa-solid fa-lightbulb me-2" style="color:#f59e0b"></i> AI Recommendations</h2>
                            <div class="sr-card-kicker">Next actions based on recent performance.</div>
                        </div>
                    </div>
                    @if(isset($aiRecommendations) && count($aiRecommendations) > 0)
                        <div class="sr-rec-list">
                            @foreach($aiRecommendations as $rec)
                                <div class="sr-rec-item">
                                    <div class="sr-rec-icon" style="--accent: {{ $rec->color }}"><i class="fa-solid {{ $rec->icon }}"></i></div>
                                    <div style="font-size:.88rem;font-weight:700;color:var(--tx2);line-height:1.45">{{ $rec->text }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sr-empty">
                            <i class="fa-solid fa-lightbulb"></i>
                            <div>Complete a session to get tailored recommendations.</div>
                        </div>
                    @endif
                </section>
            </div>

            <section id="card-recent-sessions" class="sr-card sr-card-pad">
                <div class="sr-card-header">
                    <div>
                        <h2 class="sr-card-title">Recent Sessions</h2>
                        <div class="sr-card-kicker">Review the latest completed mock interviews.</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('user.reports') }}" class="sr-btn" style="min-height:36px;padding:7px 12px">View Reports</a>
                        @if(isset($recentSessions) && $recentSessions->count() > 0)
                            <form action="{{ route('user.sessions.clear') }}" method="POST" onsubmit="return confirm('Clear all completed interview sessions? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sr-btn" style="min-height:36px;padding:7px 12px;color:#ef4444;border-color:rgba(239,68,68,.35)">
                                    <i class="fa-solid fa-trash-can"></i> Clear All
                                </button>
                            </form>
                        @endif
                    </div>
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
                                    <td><span class="sr-chip" style="background:rgba(59,130,246,.1);color:#60a5fa">{{ $session->category ? $session->category->title : 'General' }}</span></td>
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
                                    <td colspan="4" class="text-center py-4" style="color:var(--tx3)">No recent sessions found. Start practicing when you are ready.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="sr-sessions-mobile">
                    @forelse($recentSessions ?? [] as $session)
                        @php
                            $sessionScore = $session->score ? (int) $session->score->overall_readiness_score : 0;
                            $sessionColor = $sessionScore >= 80 ? '#22c55e' : ($sessionScore >= 60 ? '#f59e0b' : '#ef4444');
                        @endphp
                        <div class="sr-session-card">
                            <div class="sr-session-meta">
                                <div class="sr-session-title">{{ $session->category ? $session->category->title : 'General Interview' }}</div>
                                <div class="sr-session-date">{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }}</div>
                            </div>
                            <div class="sr-session-actions d-flex align-items-center gap-2">
                                <span class="sr-score-mini" style="color:{{ $sessionColor }}">{{ $sessionScore }}%</span>
                                <a href="{{ route('user.review', $session->id) }}" class="sr-btn sr-btn-primary" style="min-height:34px;padding:6px 10px;font-size:.78rem">Review</a>
                                <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sr-btn" title="Delete session" style="width:34px;min-height:34px;padding:0;color:#ef4444;border-color:rgba(239,68,68,.35)">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="sr-empty">
                            <i class="fa-solid fa-calendar-plus"></i>
                            <div>No recent sessions found. Start practicing when you are ready.</div>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>

        <aside class="sr-side-stack">
            <section class="sr-card sr-card-pad">
                <div class="sr-card-header">
                    <div>
                        <h2 class="sr-card-title">Skill Radar</h2>
                        <div class="sr-card-kicker">Average capability profile.</div>
                    </div>
                </div>
                <div class="chart-container-mobile" style="height:260px">
                    <canvas id="radarChart"></canvas>
                </div>
            </section>

            <section id="card-daily-challenge" class="sr-card sr-card-pad sr-challenge-card">
                <div class="sr-eyebrow"><i class="fa-solid fa-calendar-day"></i> Today&apos;s Challenge</div>
                <h2 class="sr-card-title" style="font-size:1.15rem">Answer 3 behavioral questions</h2>
                <p class="sr-card-kicker mb-3">Earn extra XP, sharpen structure, and keep your practice streak alive.</p>
                <div class="sr-tag-list mb-3">
                    <span class="sr-tag" style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.18);color:#f59e0b">+50 XP</span>
                    <span class="sr-tag" style="background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.18);color:#22c55e">+1 Streak</span>
                </div>
                <a href="{{ route('interview.setup') }}" class="sr-btn sr-btn-primary w-100"><i class="fa-solid fa-play"></i> Start Challenge</a>
            </section>

            <section class="sr-card sr-card-pad">
                <div class="sr-card-header">
                    <div>
                        <h2 class="sr-card-title">Current Goal</h2>
                        <div class="sr-card-kicker">Progress toward your next readiness target.</div>
                    </div>
                </div>
                @if(isset($upcomingGoal))
                    <div class="sr-goal-box">
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <div style="font-weight:800;color:var(--tx)">{{ $upcomingGoal->title }}</div>
                            <div style="font-weight:900;color:#22c55e">{{ $goalPercent }}%</div>
                        </div>
                        <div class="sr-progress"><span style="--value: {{ $goalPercent }}%; background:linear-gradient(90deg,#22c55e,#0ea5e9)"></span></div>
                    </div>
                @else
                    <div class="sr-empty">
                        <i class="fa-solid fa-bullseye"></i>
                        <div>No current goal set.</div>
                    </div>
                @endif
            </section>

            <section class="sr-card sr-card-pad">
                <div class="sr-card-header">
                    <div>
                        <h2 class="sr-card-title">Achievements</h2>
                        <div class="sr-card-kicker">Milestones earned through practice.</div>
                    </div>
                </div>
                <div class="sr-achievement-grid">
                    @php
                        $achievements = [
                            ['name' => 'First Interview', 'label' => 'First Interview', 'icon' => 'fa-medal', 'accent' => '#f59e0b'],
                            ['name' => '3-Day Streak', 'label' => '3-Day Streak', 'icon' => 'fa-fire', 'accent' => '#ef4444'],
                            ['name' => 'STAR Master', 'label' => 'STAR Master', 'icon' => 'fa-star', 'accent' => '#3b82f6'],
                            ['name' => 'Top Comm', 'label' => 'Top Comm', 'icon' => 'fa-bullhorn', 'accent' => '#22c55e'],
                        ];
                    @endphp
                    @foreach($achievements as $achievement)
                        @php $earned = in_array($achievement['name'], $badgesEarned ?? []); @endphp
                        <div class="sr-achievement {{ $earned ? '' : 'locked' }}" style="--accent: {{ $achievement['accent'] }}">
                            <i class="fa-solid {{ $achievement['icon'] }}"></i>
                            <span>{{ $achievement['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="sr-card sr-card-pad">
                <div class="sr-card-header">
                    <div>
                        <h2 class="sr-card-title">Notifications</h2>
                        <div class="sr-card-kicker">Recent updates and reminders.</div>
                    </div>
                </div>
                @if(isset($recentNotifications) && count($recentNotifications) > 0)
                    <div class="sr-notification-list">
                        @foreach($recentNotifications as $notif)
                            <div class="sr-notification-item">
                                <div class="sr-list-icon" style="--accent:#3b82f6"><i class="fa-solid {{ $notif->data['icon'] ?? 'fa-bell' }}"></i></div>
                                <div>
                                    <div style="font-size:.88rem;font-weight:800;color:var(--tx)">{{ $notif->data['title'] ?? 'Notification' }}</div>
                                    <div style="font-size:.8rem;color:var(--tx3);line-height:1.45;margin-top:2px">{{ $notif->data['message'] ?? '' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="sr-empty">
                        <i class="fa-solid fa-bell-slash"></i>
                        <div>No new notifications.</div>
                    </div>
                @endif
            </section>
        </aside>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof Chart === 'undefined') return;

    const rootStyle = getComputedStyle(document.documentElement);
    const getThemeColor = (varName, fallback) => rootStyle.getPropertyValue(varName).trim() || fallback;
    const txColor = getThemeColor('--tx3', '#8792a6');
    const sfColor = getThemeColor('--sf', '#171d2d');
    const isLightMode = document.documentElement.classList.contains('lm');
    const gridColor = isLightMode ? 'rgba(15,23,42,0.08)' : 'rgba(255,255,255,0.07)';
    const radarGridColor = isLightMode ? 'rgba(15,23,42,0.12)' : 'rgba(255,255,255,0.12)';

    Chart.defaults.color = txColor;
    Chart.defaults.font.family = "'Poppins', sans-serif";

    const progressCanvas = document.getElementById('progressChart');
    if (progressCanvas) {
        const progressCtx = progressCanvas.getContext('2d');
        const chartDataObj = {
            recent: {
                labels: {!! json_encode(collect($scoreTrend ?? [])->pluck('date')) !!},
                data: {!! json_encode(collect($scoreTrend ?? [])->pluck('score')) !!}
            }
        };

        const gradientLine = progressCtx.createLinearGradient(0, 0, 0, 300);
        gradientLine.addColorStop(0, 'rgba(59, 130, 246, 0.34)');
        gradientLine.addColorStop(1, 'rgba(14, 165, 233, 0.00)');

        new Chart(progressCtx, {
            type: 'line',
            data: {
                labels: chartDataObj.recent.labels.length ? chartDataObj.recent.labels : ['No Data'],
                datasets: [{
                    label: 'Readiness Score',
                    data: chartDataObj.recent.data.length ? chartDataObj.recent.data : [0],
                    borderColor: '#3b82f6',
                    backgroundColor: gradientLine,
                    borderWidth: 3,
                    tension: 0.38,
                    fill: true,
                    pointBackgroundColor: sfColor,
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: isLightMode ? '#ffffff' : 'rgba(15, 23, 42, 0.94)',
                        titleColor: isLightMode ? '#0f172a' : '#fff',
                        bodyColor: isLightMode ? '#334155' : '#dbeafe',
                        borderColor: '#3b82f6',
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
                    y: { beginAtZero: true, max: 100, grid: { color: gridColor }, border: { display: false } },
                    x: { grid: { display: false }, border: { display: false } }
                }
            }
        });
    }

    const radarCanvas = document.getElementById('radarChart');
    if (radarCanvas) {
        new Chart(radarCanvas.getContext('2d'), {
            type: 'radar',
            data: {
                labels: ['Clarity', 'Relevance', 'Grammar', 'Professionalism', 'Confidence'],
                datasets: [{
                    label: 'Score Level',
                    data: [
                        {{ $radarData['clarity'] ?? 0 }},
                        {{ $radarData['relevance'] ?? 0 }},
                        {{ $radarData['grammar'] ?? 0 }},
                        {{ $radarData['professionalism'] ?? 0 }},
                        {{ $radarData['confidence'] ?? 0 }}
                    ],
                    backgroundColor: 'rgba(34, 197, 94, 0.16)',
                    borderColor: '#22c55e',
                    pointBackgroundColor: '#22c55e',
                    pointBorderColor: sfColor,
                    pointHoverBackgroundColor: sfColor,
                    pointHoverBorderColor: '#22c55e',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    r: {
                        angleLines: { color: radarGridColor },
                        grid: { color: radarGridColor },
                        pointLabels: { color: txColor, font: { size: 11, weight: 600 } },
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
        if (typeof window.driver === 'undefined') return;
        const driver = window.driver.js.driver;

        const stepsMobile = [
            { element: '#mob-bottom-nav', popover: { title: 'Mobile Navigation', description: 'Access Mock Interviews, Learning Lab, and Progress Tracking right from the bottom bar.', side: "top", align: 'center' }},
            { element: '.sr-score-panel', popover: { title: 'Readiness Summary', description: 'This area summarizes your current interview readiness and next goal.', side: "bottom", align: 'start' }},
            { element: '.sr-mobile-stat-grid', popover: { title: 'Quick Statistics', description: 'Get a quick overview of practice activity, rating, XP, and streak.', side: "top", align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Progress Chart', description: 'Visualize your interview score trend over time.', side: "top", align: 'start' }},
            { element: '#card-ai-recommendations', popover: { title: 'AI Recommendations', description: 'Get personalized suggestions to improve your specific weak points.', side: "top", align: 'start' }},
            { element: '#card-recent-sessions', popover: { title: 'Recent Sessions', description: 'Review past mock interviews and detailed feedback.', side: "top", align: 'start' }},
            { element: '#mobThBtn', popover: { title: 'Theme Toggle', description: 'Switch between light and dark mode.', side: "bottom", align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#dbSidebar', popover: { title: 'Navigation Menu', description: 'Access all features including Mock Interviews, Learning Lab, and Progress Tracking from here.', side: "right", align: 'start' }},
            { element: '.sr-score-panel', popover: { title: 'Readiness Summary', description: 'This area summarizes your current interview readiness and next goal.', side: "bottom", align: 'start' }},
            { element: '.sr-stats-desktop', popover: { title: 'Quick Statistics', description: 'Get a quick overview of practice activity, rating, XP, and streak.', side: "top", align: 'start' }},
            { element: '#card-progress-chart', popover: { title: 'Progress Chart', description: 'Visualize your interview score trend over time.', side: "top", align: 'start' }},
            { element: '#card-ai-recommendations', popover: { title: 'AI Recommendations', description: 'Get personalized suggestions to improve your specific weak points.', side: "bottom", align: 'start' }},
            { element: '#card-recent-sessions', popover: { title: 'Recent Sessions', description: 'Review past mock interviews and detailed feedback.', side: "top", align: 'start' }},
            { element: '#dbThBtn', popover: { title: 'Theme Toggle', description: 'Switch between light and dark mode for a comfortable viewing experience.', side: "bottom", align: 'center' }},
            { element: '#notifWrap', popover: { title: 'Notifications', description: 'Stay updated with feedback on your interviews and platform announcements.', side: "bottom", align: 'center' }},
            { element: '#profileWrap', popover: { title: 'Your Profile', description: 'Manage your account settings and preferences.', side: "bottom", align: 'end' }}
        ];

        const driverObj = driver({
            showProgress: true,
            animate: true,
            popoverClass: document.documentElement.classList.contains('lm') ? 'driverjs-theme-light' : 'driverjs-theme-dark',
            steps: ({{ $isMobile ? 'true' : 'false' }} ? stepsMobile : stepsDesktop).filter(step => step.element ? document.querySelector(step.element) : true),
            onDestroyStarted: () => {
                if (!driverObj.hasNextStep() || confirm("Are you sure you want to exit the tutorial?")) {
                    driverObj.destroy();
                    localStorage.setItem('onboarding_completed', 'true');
                }
            },
        });

        window.startOnboardingTour = function() {
            driverObj.drive();
        };

        if (!localStorage.getItem('onboarding_completed')) {
            startOnboardingTour();
        }

    });
</script>
@endpush

@endsection
