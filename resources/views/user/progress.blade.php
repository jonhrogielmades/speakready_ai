@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Progress')

@section('content')
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .progress-hero {
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
    .progress-hero::after {
        content: "";
        position: absolute;
        z-index: -1;
        inset: 0 0 0 auto;
        width: min(34%, 320px);
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.055));
        pointer-events: none;
    }
    .lm .progress-hero {
        background:
            radial-gradient(circle at 92% 35%, rgba(147, 197, 253, 0.2), transparent 25%),
            linear-gradient(110deg, rgba(255, 255, 255, 0.99), rgba(246, 249, 255, 0.97));
        border-color: #dce8fb;
        box-shadow: 0 7px 22px rgba(59, 130, 246, 0.08);
    }
    .progress-hero-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 98px;
        padding: 14px clamp(126px, 14vw, 148px) 14px 16px;
    }
    .progress-hero-copy {
        min-width: 0;
        width: 100%;
    }
    .progress-hero-title {
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
    .progress-hero-title svg {
        width: 23px;
        height: 23px;
        flex: 0 0 auto;
        color: #3b82f6;
    }
    .progress-hero-subtitle {
        max-width: 680px;
        font-size: 0.88rem;
        color: var(--tx3);
        margin: 0;
        line-height: 1.45;
    }
    .progress-hero-art {
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
        animation: progressHeroArtFloat 4.8s ease-in-out infinite;
    }
    .progress-hero-art :is(circle, rect, path, polygon, ellipse):nth-child(odd) {
        transform-origin: center;
        animation: progressHeroArtPulse 3.4s ease-in-out infinite;
    }
    @keyframes progressHeroArtFloat {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg) scale(1); }
        35% { transform: translate3d(0, -7px, 0) rotate(1.5deg) scale(1.015); }
        70% { transform: translate3d(-3px, -2px, 0) rotate(-1deg) scale(1.005); }
    }
    @keyframes progressHeroArtPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.78; }
    }
    .progress-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .premium-panel {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .premium-panel:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08);
    }
    .practice-plan-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
    }
    .practice-plan-row {
        display: grid;
        grid-template-columns: 76px minmax(0, 1fr);
        align-items: flex-start;
        gap: 24px;
        min-width: 0;
        height: 100%;
        padding: 26px;
        border: 1px solid color-mix(in srgb, var(--plan-color, #3b82f6) 22%, rgba(191, 219, 254, 0.86));
        border-radius: 22px;
        background:
            radial-gradient(circle at 82% 30%, color-mix(in srgb, var(--plan-color, #3b82f6) 8%, transparent), transparent 34%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), color-mix(in srgb, var(--plan-color, #3b82f6) 4%, rgba(248, 250, 252, 0.94)));
        color: inherit;
        text-decoration: none;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
    }
    .practice-plan-row:hover {
        color: inherit;
        transform: translateY(-2px);
        border-color: color-mix(in srgb, var(--plan-color, #3b82f6) 34%, rgba(191, 219, 254, 0.86));
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
    }
    .practice-plan-icon {
        width: 76px;
        height: 76px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 76px;
        color: var(--plan-color, #3b82f6);
        background:
            radial-gradient(circle at 30% 16%, rgba(255,255,255,0.74), transparent 32%),
            color-mix(in srgb, var(--plan-color, #3b82f6) 15%, #ffffff);
        font-size: 2.1rem;
    }
    .practice-plan-copy {
        min-width: 0;
        flex: 1 1 auto;
    }
    .practice-plan-top {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 10px;
    }
    .practice-plan-step {
        display: inline-flex;
        border-radius: 999px;
        padding: 8px 14px;
        background: linear-gradient(135deg, rgba(219, 234, 254, 0.96), rgba(239, 246, 255, 0.96));
        color: #2563eb;
        font-size: 0.92rem;
        font-weight: 800;
        line-height: 1.1;
        white-space: nowrap;
    }
    .practice-plan-title {
        color: var(--tx);
        font-size: 1.28rem;
        font-weight: 900;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }
    .practice-plan-text {
        color: #475569;
        font-size: 1rem;
        font-weight: 600;
        line-height: 1.42;
        max-width: 680px;
    }
    :root:not(.lm) .practice-plan-text {
        color: #c2ccda;
    }
    .practice-plan-tasks {
        display: grid;
        gap: 8px;
        margin: 16px 0 0;
        padding: 0;
        list-style: none;
    }
    .practice-plan-tasks li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #475569;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.35;
    }
    :root:not(.lm) .practice-plan-tasks li {
        color: #c2ccda;
    }
    .practice-plan-tasks li i {
        width: 24px;
        height: 24px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(16, 185, 129, 0.1);
        flex: 0 0 24px;
    }
    .practice-plan-footer {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 18px;
    }
    .practice-plan-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        border-radius: 999px;
        padding: 8px 13px;
        background: rgba(22, 163, 74, 0.12);
        color: #059669;
        font-size: 0.92rem;
        font-weight: 800;
        line-height: 1.1;
    }
    .practice-plan-link {
        color: #0d6efd;
        font-size: 0.98rem;
        font-weight: 900;
        white-space: nowrap;
    }
    .practice-plan-panel {
        border-radius: 24px;
        padding: 32px !important;
    }
    #personalized-practice-plan .practice-plan-list {
        gap: 10px;
    }
    #personalized-practice-plan .practice-plan-row {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        padding: 14px !important;
        border-radius: 14px;
    }
    #personalized-practice-plan .practice-plan-icon {
        width: 42px;
        height: 42px;
        flex-basis: 42px;
        border-radius: 12px;
        font-size: 1.05rem;
    }
    #personalized-practice-plan .practice-plan-top {
        gap: 8px;
        margin-bottom: 8px;
    }
    #personalized-practice-plan .practice-plan-step {
        padding: 5px 10px;
        font-size: 0.68rem;
    }
    #personalized-practice-plan .practice-plan-title {
        font-size: 0.86rem;
        line-height: 1.16;
    }
    #personalized-practice-plan .practice-plan-text {
        font-size: 0.74rem;
        line-height: 1.32;
    }
    #personalized-practice-plan .practice-plan-tasks {
        gap: 6px;
        margin-top: 10px;
    }
    #personalized-practice-plan .practice-plan-tasks li {
        gap: 7px;
        font-size: 0.72rem;
        line-height: 1.25;
    }
    #personalized-practice-plan .practice-plan-tasks li i {
        width: 18px;
        height: 18px;
        flex-basis: 18px;
        border-radius: 7px;
        font-size: 0.58rem;
    }
    #personalized-practice-plan .practice-plan-footer {
        gap: 10px;
        margin-top: 10px;
    }
    #personalized-practice-plan .practice-plan-pill {
        gap: 5px;
        padding: 5px 9px;
        font-size: 0.68rem;
    }
    #personalized-practice-plan .practice-plan-link {
        font-size: 0.74rem;
    }
    .practice-plan-heading {
        display: grid;
        grid-template-columns: 64px minmax(0, 1fr);
        gap: 18px;
        align-items: start;
        margin-bottom: 24px;
    }
    .practice-plan-heading-icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        color: #16a34a;
        background: rgba(22, 163, 74, 0.1);
        font-size: 1.8rem;
    }
    .practice-plan-heading-title {
        color: var(--tx);
        margin: 0;
        font-size: 1.65rem;
        line-height: 1.18;
        font-weight: 950;
    }
    .practice-plan-heading-text {
        color: #475569;
        max-width: 560px;
        margin: 10px 0 0;
        font-size: 1.06rem;
        line-height: 1.45;
        font-weight: 500;
    }
    :root:not(.lm) .practice-plan-heading-text {
        color: #c2ccda;
    }
    .progress-chart-panel {
        border-radius: 24px;
        padding: 32px !important;
        background: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
    }
    :root:not(.lm) .progress-chart-panel {
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.96), rgba(15, 23, 42, 0.94));
    }
    .progress-panel-heading {
        display: grid;
        grid-template-columns: 70px minmax(0, 1fr);
        gap: 22px;
        align-items: center;
        margin-bottom: 24px;
    }
    .progress-panel-icon {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        color: var(--panel-accent, #2563eb);
        background:
            radial-gradient(circle at 30% 16%, rgba(255,255,255,0.78), transparent 34%),
            color-mix(in srgb, var(--panel-accent, #2563eb) 12%, #ffffff);
        font-size: 2rem;
    }
    .progress-panel-title {
        margin: 0;
        color: var(--tx);
        font-size: 1.6rem;
        line-height: 1.16;
        font-weight: 950;
    }
    .progress-panel-subtitle {
        margin: 8px 0 0;
        color: #475569;
        font-size: 1.02rem;
        line-height: 1.45;
        font-weight: 500;
    }
    :root:not(.lm) .progress-panel-subtitle {
        color: #c2ccda;
    }
    .progress-chart-frame {
        height: 360px;
        min-height: 0;
    }
    .progress-chart-frame.scenario {
        height: 340px;
    }
    .skill-empty-state {
        min-height: 210px;
        border: 2px dashed rgba(139, 92, 246, 0.45);
        border-radius: 20px;
        display: grid;
        place-items: center;
        padding: 32px 18px;
        text-align: center;
        background:
            radial-gradient(circle at 50% 25%, rgba(139, 92, 246, 0.08), transparent 32%),
            linear-gradient(135deg, rgba(255,255,255,0.82), rgba(248,250,252,0.7));
    }
    :root:not(.lm) .skill-empty-state {
        background: rgba(139, 92, 246, 0.06);
    }
    .skill-empty-icon {
        width: 92px;
        height: 92px;
        margin: 0 auto 16px;
        display: grid;
        place-items: center;
        color: #7c3aed;
        font-size: 3.8rem;
        opacity: 0.78;
    }
    .skill-empty-text {
        max-width: 440px;
        margin: 0 auto;
        color: var(--tx);
        font-size: 1.1rem;
        line-height: 1.35;
        font-weight: 850;
    }
    #readiness-trend .progress-chart-panel,
    #category-perf .progress-chart-panel,
    #skill-tracker .progress-chart-panel {
        border-radius: 14px;
        padding: 16px !important;
    }
    #readiness-trend .progress-panel-heading,
    #category-perf .progress-panel-heading,
    #skill-tracker .progress-panel-heading {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 12px;
    }
    #readiness-trend .progress-panel-icon,
    #category-perf .progress-panel-icon,
    #skill-tracker .progress-panel-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1.1rem;
    }
    #readiness-trend .progress-panel-title,
    #category-perf .progress-panel-title,
    #skill-tracker .progress-panel-title {
        font-size: 1rem;
        line-height: 1.16;
    }
    #readiness-trend .progress-panel-subtitle,
    #category-perf .progress-panel-subtitle,
    #skill-tracker .progress-panel-subtitle {
        margin-top: 5px;
        font-size: 0.76rem;
        line-height: 1.35;
    }
    #readiness-trend .progress-chart-frame {
        height: 170px;
    }
    #category-perf .progress-chart-frame.scenario {
        height: 190px;
    }
    #skill-tracker .skill-empty-state {
        min-height: 138px;
        border-radius: 13px;
        padding: 16px 12px;
    }
    #skill-tracker .skill-empty-icon {
        width: 44px;
        height: 44px;
        margin-bottom: 10px;
        font-size: 1.7rem;
    }
    #skill-tracker .skill-empty-text {
        max-width: 220px;
        font-size: 0.78rem;
        line-height: 1.25;
    }
    .skill-metric-row {
        margin-bottom: 18px;
    }
    .skill-metric-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    .skill-metric-label {
        color: var(--tx);
        font-weight: 800;
    }
    .skill-metric-value {
        color: var(--tx3);
        text-align: right;
        font-weight: 700;
    }
    .skill-metric-bar {
        height: 10px;
        background: rgba(148, 163, 184, 0.18);
        border-radius: 999px;
        overflow: hidden;
    }
    .skill-metric-fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #7c3aed);
    }
    .strengths-star-panel,
    .history-panel {
        border-radius: 24px;
        padding: 32px !important;
        background: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
    }
    :root:not(.lm) .strengths-star-panel,
    :root:not(.lm) .history-panel {
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.96), rgba(15, 23, 42, 0.94));
    }
    .strengths-overview,
    .star-overview,
    .history-heading {
        display: grid;
        grid-template-columns: 96px minmax(0, 1fr);
        gap: 24px;
        align-items: start;
    }
    .strengths-overview {
        padding-bottom: 30px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.28);
        margin-bottom: 30px;
    }
    .strengths-icon,
    .star-icon,
    .history-icon,
    .star-note-icon {
        display: grid;
        place-items: center;
        color: var(--panel-accent, #7c3aed);
        background:
            radial-gradient(circle at 30% 16%, rgba(255,255,255,0.78), transparent 34%),
            color-mix(in srgb, var(--panel-accent, #7c3aed) 12%, #ffffff);
    }
    .strengths-icon {
        width: 96px;
        height: 96px;
        border-radius: 22px;
        font-size: 3rem;
    }
    .star-icon {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        font-size: 2.6rem;
    }
    .history-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: transparent;
        font-size: 2rem;
    }
    .strengths-title,
    .star-title,
    .history-title {
        margin: 0;
        color: var(--tx);
        font-size: 1.85rem;
        line-height: 1.18;
        font-weight: 950;
    }
    .strengths-text,
    .star-text,
    .star-note-text {
        margin: 14px 0 0;
        color: #475569;
        font-size: 1.35rem;
        line-height: 1.55;
        font-weight: 500;
    }
    :root:not(.lm) .strengths-text,
    :root:not(.lm) .star-text,
    :root:not(.lm) .star-note-text {
        color: #c2ccda;
    }
    .strengths-lists {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .strengths-list-card {
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 18px;
        padding: 18px;
        background: rgba(248, 250, 252, 0.68);
    }
    :root:not(.lm) .strengths-list-card {
        background: rgba(255, 255, 255, 0.04);
    }
    .strengths-list-card h6 {
        margin: 0 0 12px;
        font-size: 1rem;
        font-weight: 900;
    }
    .strengths-list-card ul {
        display: grid;
        gap: 8px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .strengths-list-card li {
        color: var(--tx);
        font-size: 0.92rem;
        line-height: 1.4;
    }
    .star-overview {
        align-items: center;
        margin-bottom: 30px;
    }
    .star-note {
        display: grid;
        grid-template-columns: 70px minmax(0, 1fr);
        gap: 22px;
        align-items: center;
        border: 1px solid rgba(167, 139, 250, 0.34);
        border-radius: 20px;
        padding: 24px;
        background:
            radial-gradient(circle at 92% 28%, rgba(139, 92, 246, 0.08), transparent 30%),
            linear-gradient(135deg, rgba(250, 245, 255, 0.82), rgba(248, 250, 252, 0.78));
    }
    :root:not(.lm) .star-note {
        background: rgba(139, 92, 246, 0.06);
    }
    .star-note-icon {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        font-size: 2rem;
    }
    .star-note-title {
        margin: 0 0 8px;
        color: var(--tx);
        font-size: 1.1rem;
        font-weight: 950;
    }
    .star-note-text {
        margin: 0;
        font-size: 1rem;
    }
    .history-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        margin-bottom: 22px;
    }
    .history-heading {
        grid-template-columns: 48px minmax(0, 1fr);
        gap: 16px;
        align-items: center;
    }
    .history-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    #strengths-tracker .strengths-star-panel {
        border-radius: 14px;
        padding: 16px !important;
    }
    #strengths-tracker .strengths-overview,
    #strengths-tracker .star-overview {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
    }
    #strengths-tracker .strengths-overview {
        padding-bottom: 14px;
        margin-bottom: 14px;
    }
    #strengths-tracker .star-overview {
        margin-bottom: 14px;
    }
    #strengths-tracker .strengths-icon,
    #strengths-tracker .star-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1.2rem;
    }
    #strengths-tracker .star-icon {
        border-radius: 50%;
    }
    #strengths-tracker .strengths-title,
    #strengths-tracker .star-title {
        font-size: 0.95rem;
        line-height: 1.18;
    }
    #strengths-tracker .strengths-text,
    #strengths-tracker .star-text {
        margin-top: 7px;
        font-size: 0.78rem;
        line-height: 1.38;
    }
    #strengths-tracker .star-note {
        grid-template-columns: 38px minmax(0, 1fr);
        gap: 12px;
        border-radius: 12px;
        padding: 12px;
    }
    #strengths-tracker .star-note-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        font-size: 1rem;
    }
    #strengths-tracker .star-note-title {
        margin-bottom: 5px;
        font-size: 0.78rem;
        line-height: 1.2;
    }
    #strengths-tracker .star-note-text {
        font-size: 0.72rem;
        line-height: 1.42;
    }
    .history-clear-btn {
        min-height: 58px;
        border-radius: 14px !important;
        padding: 12px 24px !important;
        font-weight: 900 !important;
        font-size: 1rem;
    }
    .history-search {
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr);
        align-items: center;
        min-height: 66px;
        border: 1px solid rgba(148, 163, 184, 0.36);
        border-radius: 18px;
        background: rgba(248, 250, 252, 0.72);
        margin-bottom: 22px;
        overflow: hidden;
    }
    :root:not(.lm) .history-search {
        background: rgba(255, 255, 255, 0.04);
    }
    .history-search i {
        justify-self: center;
        color: #475569;
        font-size: 1.35rem;
    }
    .history-search input {
        width: 100%;
        min-width: 0;
        border: 0;
        outline: 0;
        background: transparent;
        color: var(--tx);
        font-size: 1.08rem;
        font-weight: 600;
    }
    .history-list {
        display: grid;
        gap: 14px;
    }
    .history-card {
        border: 1px solid rgba(191, 219, 254, 0.72);
        border-radius: 18px;
        padding: 24px;
        background:
            radial-gradient(circle at 92% 18%, rgba(37, 99, 235, 0.07), transparent 32%),
            linear-gradient(135deg, rgba(255,255,255,0.96), rgba(239,246,255,0.72));
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }
    :root:not(.lm) .history-card {
        background: rgba(37, 99, 235, 0.06);
    }
    .history-date {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #64748b;
        font-size: 1rem;
        font-weight: 800;
        margin-bottom: 14px;
    }
    .history-scenario {
        margin: 0 0 18px;
        color: var(--tx);
        font-size: 1.35rem;
        line-height: 1.18;
        font-weight: 950;
    }
    .history-meta {
        display: grid;
        gap: 10px;
        margin-bottom: 22px;
        color: #334155;
        font-size: 1rem;
        font-weight: 850;
    }
    :root:not(.lm) .history-meta {
        color: #d6deea;
    }
    .history-score-value {
        color: #4f46e5;
    }
    .history-rating-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 13px;
        font-size: 0.9rem;
        font-weight: 900;
    }
    .history-card-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 74px;
        gap: 18px;
        align-items: center;
    }
    .history-feedback-btn,
    .history-delete-btn {
        min-height: 60px;
        border-radius: 14px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 1rem;
        font-weight: 900 !important;
    }
    .history-delete-btn {
        width: 74px;
        padding: 0 !important;
    }
    #history-table .history-list {
        gap: 10px;
    }
    #history-table .history-card {
        border-radius: 14px;
        padding: 14px;
    }
    #history-table .history-date {
        gap: 7px;
        margin-bottom: 10px;
        font-size: 0.78rem;
    }
    #history-table .history-scenario {
        margin-bottom: 12px;
        font-size: 0.94rem;
        line-height: 1.18;
    }
    #history-table .history-meta {
        gap: 8px;
        margin-bottom: 14px;
        font-size: 0.78rem;
    }
    #history-table .history-rating-badge {
        padding: 5px 10px;
        font-size: 0.72rem;
    }
    #history-table .history-card-actions {
        grid-template-columns: minmax(0, 1fr) 42px;
        gap: 8px;
    }
    #history-table .history-feedback-btn,
    #history-table .history-delete-btn {
        min-height: 40px;
        border-radius: 11px !important;
        gap: 7px;
        font-size: 0.74rem;
    }
    #history-table .history-delete-btn {
        width: 42px;
    }
    .learning-panel,
    .recommend-panel,
    .voice-panel {
        border-radius: 24px;
        padding: 32px !important;
        background: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
    }
    :root:not(.lm) .learning-panel,
    :root:not(.lm) .recommend-panel,
    :root:not(.lm) .voice-panel {
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.96), rgba(15, 23, 42, 0.94));
    }
    .learning-heading,
    .voice-heading,
    .recommend-heading {
        display: grid;
        grid-template-columns: 70px minmax(0, 1fr);
        gap: 18px;
        align-items: center;
        margin-bottom: 30px;
    }
    .learning-heading-icon,
    .voice-heading-icon,
    .recommend-heading-icon,
    .learning-module-icon,
    .recommend-item-icon {
        display: grid;
        place-items: center;
        color: var(--panel-accent, #7c3aed);
        background:
            radial-gradient(circle at 30% 16%, rgba(255,255,255,0.78), transparent 34%),
            color-mix(in srgb, var(--panel-accent, #7c3aed) 12%, #ffffff);
    }
    .learning-heading-icon,
    .voice-heading-icon,
    .recommend-heading-icon {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        font-size: 2rem;
    }
    .learning-title,
    .voice-title,
    .recommend-title {
        margin: 0;
        color: var(--tx);
        font-size: 1.6rem;
        line-height: 1.18;
        font-weight: 950;
    }
    .learning-subtitle,
    .voice-subtitle,
    .recommend-subtitle {
        margin: 8px 0 0;
        color: #475569;
        font-size: 1.05rem;
        line-height: 1.42;
        font-weight: 500;
    }
    :root:not(.lm) .learning-subtitle,
    :root:not(.lm) .voice-subtitle,
    :root:not(.lm) .recommend-subtitle {
        color: #c2ccda;
    }
    .learning-list {
        display: grid;
        gap: 26px;
    }
    .learning-module {
        display: grid;
        grid-template-columns: 84px minmax(0, 1fr);
        gap: 24px;
        align-items: center;
        padding-bottom: 26px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.24);
    }
    .learning-module:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
    .learning-module-icon {
        width: 84px;
        height: 84px;
        border-radius: 18px;
        font-size: 2.2rem;
    }
    .learning-module-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 18px;
    }
    .learning-module-title {
        color: var(--tx);
        font-size: 1.25rem;
        line-height: 1.24;
        font-weight: 950;
    }
    .learning-percent {
        color: var(--module-color, #7c3aed);
        font-size: 1.25rem;
        font-weight: 950;
    }
    .learning-track {
        height: 12px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.2);
        overflow: hidden;
    }
    .learning-fill {
        height: 100%;
        width: var(--learning-progress, 0%);
        border-radius: inherit;
        background: linear-gradient(90deg, var(--module-color, #7c3aed), color-mix(in srgb, var(--module-color, #7c3aed) 72%, #ffffff));
    }
    .learning-summary {
        display: grid;
        grid-template-columns: 128px minmax(0, 1fr);
        gap: 26px;
        align-items: center;
        margin-top: 28px;
        padding: 28px 38px;
        border: 1px solid rgba(56, 189, 248, 0.32);
        border-radius: 18px;
        background:
            radial-gradient(circle at 12% 35%, rgba(14, 165, 233, 0.12), transparent 26%),
            linear-gradient(135deg, rgba(240, 249, 255, 0.9), rgba(248, 250, 252, 0.82));
    }
    :root:not(.lm) .learning-summary {
        background: rgba(14, 165, 233, 0.06);
    }
    .learning-summary-icon {
        width: 112px;
        height: 112px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        color: #0ea5e9;
        background: rgba(14, 165, 233, 0.12);
        font-size: 3rem;
    }
    .learning-summary-value {
        color: #0ea5e9;
        font-size: 2.7rem;
        line-height: 1;
        font-weight: 950;
    }
    .learning-summary-label {
        color: #334155;
        margin-top: 10px;
        font-size: 1.05rem;
        font-weight: 500;
    }
    :root:not(.lm) .learning-summary-label {
        color: #c2ccda;
    }
    #learning-progress .learning-panel {
        border-radius: 14px;
        padding: 16px !important;
    }
    #learning-progress .learning-heading {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }
    #learning-progress .learning-heading-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1.1rem;
    }
    #learning-progress .learning-title {
        font-size: 1rem;
        line-height: 1.16;
    }
    #learning-progress .learning-subtitle {
        margin-top: 5px;
        font-size: 0.76rem;
        line-height: 1.35;
    }
    #learning-progress .learning-list {
        gap: 14px;
    }
    #learning-progress .learning-module {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        padding-bottom: 15px;
    }
    #learning-progress .learning-module-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        font-size: 1.05rem;
    }
    #learning-progress .learning-module-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: start;
        gap: 10px;
        margin-bottom: 8px;
    }
    #learning-progress .learning-module-title {
        display: -webkit-box;
        min-width: 0;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        font-size: 0.8rem;
        line-height: 1.2;
    }
    #learning-progress .learning-percent {
        min-width: 36px;
        text-align: right;
        font-size: 0.78rem;
        line-height: 1;
    }
    #learning-progress .learning-track {
        height: 8px;
    }
    #learning-progress .learning-summary {
        grid-template-columns: 54px minmax(0, 1fr);
        gap: 12px;
        margin-top: 16px;
        padding: 14px;
        border-radius: 14px;
    }
    #learning-progress .learning-summary-icon {
        width: 48px;
        height: 48px;
        font-size: 1.4rem;
    }
    #learning-progress .learning-summary-value {
        font-size: 1.45rem;
    }
    #learning-progress .learning-summary-label {
        margin-top: 5px;
        font-size: 0.72rem;
        line-height: 1.25;
    }
    .recommend-list {
        display: grid;
        gap: 14px;
    }
    .recommend-item {
        display: grid;
        grid-template-columns: 68px minmax(0, 1fr) 34px;
        gap: 22px;
        align-items: center;
        padding: 26px;
        border: 1px solid rgba(167, 139, 250, 0.3);
        border-radius: 18px;
        background:
            radial-gradient(circle at 92% 28%, rgba(124, 58, 237, 0.08), transparent 30%),
            linear-gradient(135deg, rgba(250, 245, 255, 0.76), rgba(248, 250, 252, 0.76));
        text-decoration: none;
    }
    :root:not(.lm) .recommend-item {
        background: rgba(124, 58, 237, 0.06);
    }
    .recommend-item:hover {
        color: inherit;
    }
    .recommend-item-icon {
        width: 68px;
        height: 68px;
        border-radius: 16px;
        font-size: 1.8rem;
    }
    .recommend-item-title {
        color: var(--tx);
        font-size: 1.18rem;
        line-height: 1.25;
        font-weight: 950;
    }
    .recommend-item-text {
        color: #475569;
        margin-top: 10px;
        font-size: 1rem;
        line-height: 1.45;
        font-weight: 500;
    }
    :root:not(.lm) .recommend-item-text {
        color: #c2ccda;
    }
    .recommend-arrow {
        color: #6d5dfc;
        font-size: 2rem;
    }
    #recommended-next .recommend-panel {
        border-radius: 14px;
        padding: 16px !important;
    }
    #recommended-next .recommend-heading {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 14px;
    }
    #recommended-next .recommend-heading-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1.1rem;
    }
    #recommended-next .recommend-title {
        font-size: 1rem;
        line-height: 1.16;
    }
    #recommended-next .recommend-subtitle {
        margin-top: 5px;
        font-size: 0.76rem;
        line-height: 1.35;
    }
    #recommended-next .recommend-list {
        gap: 10px;
    }
    #recommended-next .recommend-item {
        grid-template-columns: 42px minmax(0, 1fr) 18px;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
    }
    #recommended-next .recommend-item-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        font-size: 1.05rem;
    }
    #recommended-next .recommend-item-title {
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        font-size: 0.82rem;
        line-height: 1.18;
    }
    #recommended-next .recommend-item-text {
        display: -webkit-box;
        overflow: hidden;
        margin-top: 6px;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        font-size: 0.74rem;
        line-height: 1.32;
    }
    #recommended-next .recommend-arrow {
        font-size: 1rem;
    }
    .voice-empty {
        min-height: 280px;
        border: 1px solid rgba(244, 114, 182, 0.32);
        border-radius: 18px;
        display: grid;
        place-items: center;
        padding: 34px 18px;
        text-align: center;
        background:
            radial-gradient(circle at 50% 22%, rgba(236, 72, 153, 0.12), transparent 28%),
            linear-gradient(135deg, rgba(253, 242, 248, 0.78), rgba(255, 247, 250, 0.7));
    }
    :root:not(.lm) .voice-empty {
        background: rgba(236, 72, 153, 0.06);
    }
    .voice-empty-icon {
        width: 110px;
        height: 110px;
        margin: 0 auto 22px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        color: #ec407a;
        background: rgba(236, 72, 153, 0.12);
        font-size: 4.2rem;
    }
    .voice-empty-title {
        margin: 0 0 12px;
        color: var(--tx);
        font-size: 1.18rem;
        font-weight: 950;
    }
    .voice-empty-text {
        max-width: 430px;
        margin: 0 auto;
        color: #475569;
        font-size: 1rem;
        line-height: 1.45;
        font-weight: 500;
    }
    :root:not(.lm) .voice-empty-text {
        color: #c2ccda;
    }
    #voice-progress .voice-panel {
        border-radius: 14px;
        padding: 16px !important;
    }
    #voice-progress .voice-heading {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 14px;
    }
    #voice-progress .voice-heading-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1.1rem;
    }
    #voice-progress .voice-title {
        font-size: 1rem;
        line-height: 1.16;
    }
    #voice-progress .voice-subtitle {
        margin-top: 5px;
        font-size: 0.76rem;
        line-height: 1.35;
    }
    #voice-progress .voice-empty {
        min-height: 158px;
        padding: 18px 12px;
        border-radius: 14px;
    }
    #voice-progress .voice-empty-icon {
        width: 54px;
        height: 54px;
        margin-bottom: 12px;
        font-size: 1.75rem;
    }
    #voice-progress .voice-empty-title {
        margin-bottom: 8px;
        font-size: 0.86rem;
        line-height: 1.2;
    }
    #voice-progress .voice-empty-text {
        font-size: 0.74rem;
        line-height: 1.36;
    }
    .activity-panel,
    .goals-panel,
    .badges-panel {
        border-radius: 24px;
        padding: 32px !important;
        background: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
        border: 1px solid rgba(226, 232, 240, 0.9);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
    }
    :root:not(.lm) .activity-panel,
    :root:not(.lm) .goals-panel,
    :root:not(.lm) .badges-panel {
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.96), rgba(15, 23, 42, 0.94));
    }
    .activity-heading,
    .goals-heading,
    .badges-heading {
        display: grid;
        grid-template-columns: 80px minmax(0, 1fr);
        gap: 22px;
        align-items: center;
        margin-bottom: 32px;
    }
    .activity-heading-icon,
    .goals-heading-icon,
    .badges-heading-icon {
        width: 80px;
        height: 80px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        color: var(--panel-accent, #6d5dfc);
        background:
            radial-gradient(circle at 30% 16%, rgba(255,255,255,0.78), transparent 34%),
            color-mix(in srgb, var(--panel-accent, #6d5dfc) 12%, #ffffff);
        font-size: 2.4rem;
    }
    .activity-title,
    .goals-title,
    .badges-title {
        margin: 0;
        color: var(--tx);
        font-size: 1.6rem;
        line-height: 1.18;
        font-weight: 950;
    }
    .activity-subtitle,
    .goals-subtitle,
    .badges-subtitle {
        margin: 10px 0 0;
        color: #475569;
        font-size: 1.05rem;
        line-height: 1.42;
        font-weight: 500;
    }
    :root:not(.lm) .activity-subtitle,
    :root:not(.lm) .goals-subtitle,
    :root:not(.lm) .badges-subtitle {
        color: #c2ccda;
    }
    .activity-empty {
        text-align: center;
        padding: 8px 20px 10px;
    }
    .activity-illustration {
        width: min(430px, 90%);
        margin: 0 auto 26px;
        color: #6d5dfc;
    }
    .activity-empty-title {
        max-width: 520px;
        margin: 0 auto 14px;
        color: var(--tx);
        font-size: 1.45rem;
        line-height: 1.25;
        font-weight: 950;
    }
    .activity-empty-text {
        margin: 0 auto 28px;
        color: #475569;
        font-size: 1.05rem;
        line-height: 1.45;
        font-weight: 500;
    }
    :root:not(.lm) .activity-empty-text {
        color: #c2ccda;
    }
    .activity-cta {
        min-height: 60px;
        min-width: min(100%, 340px);
        border-radius: 14px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        font-size: 1.08rem;
        font-weight: 950 !important;
    }
    #activity-calendar .activity-panel {
        border-radius: 14px;
        padding: 16px !important;
    }
    #activity-calendar .activity-heading {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 14px;
    }
    #activity-calendar .activity-heading-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1.1rem;
    }
    #activity-calendar .activity-title {
        font-size: 1rem;
        line-height: 1.16;
    }
    #activity-calendar .activity-subtitle {
        margin-top: 5px;
        font-size: 0.76rem;
        line-height: 1.35;
    }
    #activity-calendar .activity-empty {
        padding: 0 8px 0;
    }
    #activity-calendar .activity-illustration {
        width: min(220px, 82%);
        margin-bottom: 12px;
    }
    #activity-calendar .activity-empty-title {
        max-width: 240px;
        margin-bottom: 8px;
        font-size: 0.92rem;
        line-height: 1.18;
    }
    #activity-calendar .activity-empty-text {
        margin-bottom: 14px;
        font-size: 0.74rem;
        line-height: 1.34;
    }
    #activity-calendar .activity-cta {
        min-height: 40px;
        min-width: 0;
        width: 100%;
        gap: 8px;
        border-radius: 11px !important;
        font-size: 0.78rem;
    }
    .goal-row {
        margin-top: 8px;
    }
    .goal-top {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: center;
        margin-bottom: 18px;
    }
    .goal-title {
        color: var(--tx);
        font-size: 1.15rem;
        line-height: 1.25;
        font-weight: 950;
    }
    .goal-percent {
        color: #4f46e5;
        font-size: 1.15rem;
        font-weight: 950;
    }
    .goal-track {
        height: 12px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.2);
        overflow: hidden;
    }
    .goal-fill {
        height: 100%;
        width: var(--goal-progress, 0%);
        border-radius: inherit;
        background: linear-gradient(90deg, #2563eb, #22c55e);
    }
    .goal-note {
        display: grid;
        grid-template-columns: 58px minmax(0, 1fr);
        gap: 18px;
        align-items: center;
        margin-top: 34px;
        padding: 20px;
        border: 1px solid rgba(16, 185, 129, 0.26);
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(236, 253, 245, 0.82), rgba(240, 253, 250, 0.72));
    }
    :root:not(.lm) .goal-note {
        background: rgba(16, 185, 129, 0.06);
    }
    .goal-note-icon {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        color: #10b981;
        background: rgba(16, 185, 129, 0.12);
        font-size: 1.6rem;
    }
    .goal-note-title {
        color: var(--tx);
        font-size: 1.05rem;
        line-height: 1.25;
        font-weight: 950;
    }
    .goal-note-text {
        color: #334155;
        margin-top: 5px;
        font-size: 0.95rem;
        font-weight: 500;
    }
    :root:not(.lm) .goal-note-text {
        color: #c2ccda;
    }
    #goals-milestones .goals-panel {
        border-radius: 14px;
        padding: 16px !important;
    }
    #goals-milestones .goals-heading {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 14px;
    }
    #goals-milestones .goals-heading-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1.1rem;
    }
    #goals-milestones .goals-title {
        font-size: 1rem;
        line-height: 1.16;
    }
    #goals-milestones .goals-subtitle {
        margin-top: 5px;
        font-size: 0.76rem;
        line-height: 1.35;
    }
    #goals-milestones .goal-row {
        margin-top: 6px;
    }
    #goals-milestones .goal-top {
        gap: 10px;
        margin-bottom: 10px;
    }
    #goals-milestones .goal-title,
    #goals-milestones .goal-percent {
        font-size: 0.82rem;
        line-height: 1.18;
    }
    #goals-milestones .goal-percent {
        min-width: 34px;
        text-align: right;
    }
    #goals-milestones .goal-track {
        height: 8px;
    }
    #goals-milestones .goal-note {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        margin-top: 18px;
        padding: 12px;
        border-radius: 12px;
    }
    #goals-milestones .goal-note-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        font-size: 1.05rem;
    }
    #goals-milestones .goal-note-title {
        font-size: 0.82rem;
        line-height: 1.18;
    }
    #goals-milestones .goal-note-text {
        margin-top: 4px;
        font-size: 0.72rem;
        line-height: 1.3;
    }
    .badge-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        text-align: center;
    }
    .badge-item {
        min-width: 0;
    }
    .badge-medal {
        width: 72px;
        height: 72px;
        margin: 0 auto 10px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, rgba(255, 247, 237, 0.95), rgba(254, 243, 199, 0.76));
        border: 1px solid rgba(245, 158, 11, 0.34);
        color: #f59e0b;
        font-size: 1.85rem;
        box-shadow: inset 0 0 0 8px rgba(255, 255, 255, 0.45);
    }
    .badge-item.locked .badge-medal {
        color: #94a3b8;
        border-color: rgba(148, 163, 184, 0.24);
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.95), rgba(241, 245, 249, 0.82));
        opacity: 0.72;
    }
    .badge-title {
        color: var(--tx);
        font-size: 0.82rem;
        line-height: 1.15;
        font-weight: 950;
    }
    .badge-desc {
        color: #475569;
        margin-top: 4px;
        font-size: 0.72rem;
        line-height: 1.18;
        font-weight: 500;
    }
    :root:not(.lm) .badge-desc {
        color: #c2ccda;
    }
    #achievements-badges .badges-panel {
        border-radius: 14px;
        padding: 16px !important;
    }
    #achievements-badges .badges-heading {
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 14px;
    }
    #achievements-badges .badges-heading-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1.1rem;
    }
    #achievements-badges .badges-title {
        font-size: 1rem;
        line-height: 1.16;
    }
    #achievements-badges .badges-subtitle {
        margin-top: 5px;
        font-size: 0.76rem;
        line-height: 1.35;
    }
    #achievements-badges .badge-grid {
        gap: 8px;
    }
    #achievements-badges .badge-medal {
        width: 56px;
        height: 56px;
        margin-bottom: 7px;
        font-size: 1.35rem;
        box-shadow: inset 0 0 0 6px rgba(255, 255, 255, 0.45);
    }
    #achievements-badges .badge-title {
        font-size: 0.66rem;
        line-height: 1.05;
        overflow-wrap: anywhere;
    }
    #achievements-badges .badge-desc {
        margin-top: 3px;
        font-size: 0.56rem;
        line-height: 1.08;
        overflow-wrap: anywhere;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }

    @media (max-width: 767px) {
        #sec-progress-tracking {
            padding-bottom: calc(18px + env(safe-area-inset-bottom, 0px));
        }
        #sec-progress-tracking > .row,
        #sec-progress-tracking .row.g-4 {
            --bs-gutter-x: 12px;
            --bs-gutter-y: 12px;
            margin-bottom: 12px !important;
        }
        .progress-hero {
            min-height: 104px;
            margin-bottom: 12px;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .progress-hero-inner {
            justify-content: flex-start;
            min-height: 104px;
            padding: 14px 96px 14px 14px;
        }
        .progress-hero-title {
            justify-content: flex-start;
            gap: 7px;
            font-size: 1.1rem !important;
            margin-bottom: 4px;
            letter-spacing: 0;
        }
        .progress-hero-title svg {
            width: 20px;
            height: 20px;
        }
        .progress-hero-subtitle {
            max-width: 100%;
            font-size: 0.74rem;
            line-height: 1.4;
        }
        .progress-hero-art {
            right: -10px;
            bottom: -1px;
            width: 112px;
        }
        .progress-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 14px;
        }
        .progress-actions .btn {
            width: 100%;
            min-height: 44px;
            padding-left: 8px;
            padding-right: 8px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 12px !important;
            white-space: normal;
        }
        .progress-actions .btn i {
            margin-right: 0 !important;
        }
        #sec-progress-tracking .premium-panel,
        #sec-progress-tracking #learning-progress > div,
        #sec-progress-tracking #voice-progress > div,
        #sec-progress-tracking #activity-calendar .col-12 > div,
        #sec-progress-tracking #goals-milestones > div,
        #sec-progress-tracking #achievements-badges > div {
            border-radius: 14px !important;
            padding: 14px !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        #sec-progress-tracking .premium-panel:hover {
            transform: none;
        }
        #sec-progress-tracking .premium-panel h5,
        #sec-progress-tracking #learning-progress h5,
        #sec-progress-tracking #voice-progress h5,
        #sec-progress-tracking #activity-calendar h5,
        #sec-progress-tracking #goals-milestones h5,
        #sec-progress-tracking #achievements-badges h5 {
            font-size: 0.98rem;
            line-height: 1.25;
            margin-bottom: 12px !important;
        }
        #progress-stats {
            --bs-gutter-x: 10px;
            --bs-gutter-y: 10px;
        }
        #progress-stats > .col-md-3.col-sm-6 {
            width: 50% !important;
            flex: 0 0 50% !important;
        }
        #progress-stats .premium-panel {
            min-height: 0 !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        #progress-stats .premium-panel .fs-1 {
            font-size: 1.35rem !important;
            margin-bottom: 6px !important;
        }
        #progress-stats .premium-panel h3 {
            font-size: 1.08rem;
            line-height: 1.12;
        }
        #progress-stats .premium-panel p {
            font-size: 0.68rem !important;
            line-height: 1.25;
        }
        #ai-insights {
            border-radius: 14px !important;
            padding: 14px !important;
            margin-bottom: 12px !important;
        }
        #ai-insights .d-flex {
            align-items: flex-start !important;
            gap: 10px;
        }
        #ai-insights .fa-robot {
            width: 38px;
            height: 38px;
            padding: 0 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem !important;
            margin-right: 0 !important;
            flex: 0 0 auto;
        }
        #ai-insights h6 {
            font-size: 0.88rem;
        }
        #ai-insights p {
            font-size: 0.78rem;
            line-height: 1.45;
        }
        #readiness-trend .premium-panel > div,
        #category-perf .premium-panel > div {
            height: 210px !important;
        }
        .practice-plan-list {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .practice-plan-row {
            border-radius: 12px;
            padding: 12px;
            gap: 10px;
        }
        .practice-plan-icon {
            width: 34px;
            height: 34px;
            flex-basis: 34px;
            border-radius: 11px;
        }
        .practice-plan-footer {
            align-items: flex-start;
            flex-direction: column;
            gap: 7px;
        }
        #skill-tracker .d-flex.justify-content-between,
        #learning-progress .d-flex.justify-content-between,
        #goals-milestones .d-flex.justify-content-between {
            align-items: flex-start !important;
            gap: 8px;
        }
        #skill-tracker .d-flex.justify-content-between > span:first-child,
        #learning-progress .d-flex.justify-content-between > span:first-child,
        #goals-milestones .d-flex.justify-content-between > span:first-child {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        #skill-tracker .d-flex.justify-content-between > span:last-child,
        #learning-progress .d-flex.justify-content-between > span:last-child,
        #goals-milestones .d-flex.justify-content-between > span:last-child {
            flex: 0 0 auto;
            text-align: right;
            font-size: 0.78rem;
        }
        #strengths-tracker .row.mb-4 {
            --bs-gutter-y: 12px;
        }
        #strengths-tracker .col-6 {
            width: 100%;
        }
        #strengths-tracker h6 {
            font-size: 0.86rem;
        }
        #strengths-tracker .list-group {
            font-size: 0.8rem !important;
        }
        #history-table .premium-panel > .d-flex {
            align-items: stretch !important;
            gap: 10px !important;
            margin-bottom: 12px !important;
        }
        #history-table .premium-panel > .d-flex > div {
            width: 100%;
            align-items: stretch !important;
        }
        #history-table .premium-panel > .d-flex form,
        #history-table .premium-panel > .d-flex .btn,
        #history-table .input-group {
            width: 100% !important;
        }
        #history-table .input-group {
            min-height: 44px;
        }
        #history-table table,
        #history-table tbody,
        #history-table tr,
        #history-table td {
            display: block;
            width: 100%;
        }
        #history-table thead {
            display: none;
        }
        #history-table tbody tr {
            border: 1px solid var(--bd) !important;
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 10px;
            background: var(--bg3);
        }
        #history-table tbody td {
            border: 0 !important;
            padding: 5px 0 !important;
            text-align: left !important;
            font-size: 0.82rem;
        }
        #history-table tbody td:nth-child(1) {
            color: var(--tx3);
            font-size: 0.74rem;
            padding-bottom: 2px !important;
        }
        #history-table tbody td:nth-child(2) {
            font-size: 0.94rem;
            color: var(--tx);
        }
        #history-table tbody td:nth-child(3)::before {
            content: "Score: ";
            color: var(--tx3);
            font-weight: 600;
        }
        #history-table tbody td:nth-child(4)::before {
            content: "Rating: ";
            color: var(--tx3);
            font-weight: 600;
        }
        #history-table tbody td:nth-child(5) .d-flex {
            justify-content: stretch !important;
            flex-direction: row !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            margin-top: 6px;
        }
        #history-table tbody td:nth-child(5) .btn-outline-primary {
            flex: 1 1 0;
            width: 100% !important;
            min-height: 38px;
        }
        #history-table tbody td:nth-child(5) form {
            flex: 1 1 0;
            width: auto !important;
            margin: 0 !important;
        }
        #history-table tbody td:nth-child(5) .btn-outline-danger {
            width: 100% !important;
            min-height: 38px;
            padding-left: 0;
            padding-right: 0;
        }
        #voice-progress .row.text-center {
            --bs-gutter-x: 8px;
            margin-bottom: 12px !important;
        }
        #voice-progress .row.text-center h3 {
            font-size: 1rem;
            line-height: 1.15;
        }
        #voice-progress .row.text-center small {
            font-size: 0.68rem;
            line-height: 1.25;
        }
        #voice-progress .p-3 .d-flex {
            align-items: flex-start !important;
            gap: 10px;
        }
        #voice-progress .p-3 h2 {
            font-size: 1.1rem;
            flex: 0 0 auto;
        }
        #achievements-badges .row.g-3 {
            --bs-gutter-x: 8px;
            --bs-gutter-y: 12px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-left: 0;
            margin-right: 0;
        }
        #achievements-badges .col-3 {
            width: auto;
            padding-left: 0;
            padding-right: 0;
        }
        #achievements-badges .rounded-circle {
            width: 52px !important;
            height: 52px !important;
            padding: 10px !important;
        }
        #achievements-badges .rounded-circle i {
            font-size: 1.1rem !important;
        }
        #achievements-badges .col-3 > div:last-child {
            font-size: 0.64rem !important;
            overflow-wrap: anywhere;
        }
    }

    @media (max-width: 390px) {
        .progress-hero-inner {
            padding-right: 82px;
        }
        .progress-hero-title {
            font-size: 1rem !important;
        }
        .progress-hero-art {
            width: 98px;
        }
        #sec-progress-tracking .progress-hero-inner {
            padding-right: 86px;
        }
        #sec-progress-tracking .progress-hero-title {
            font-size: 0.86rem !important;
        }
        #sec-progress-tracking .progress-hero-art {
            width: 78px;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .progress-hero-art,
        .progress-hero-art :is(circle, rect, path, polygon, ellipse) {
            animation: none !important;
        }
    }

    .progress-hero {
        min-height: 210px;
        border-radius: 22px;
        border-color: rgba(191, 219, 254, 0.9);
        background:
            radial-gradient(circle at 92% 10%, rgba(191, 219, 254, 0.78), transparent 35%),
            linear-gradient(135deg, rgba(255,255,255,0.98), rgba(239,246,255,0.96));
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
    }
    :root:not(.lm) .progress-hero {
        background:
            radial-gradient(circle at 92% 10%, rgba(37, 99, 235, 0.2), transparent 35%),
            linear-gradient(135deg, #151c2d, #172238);
    }
    .progress-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        z-index: -1;
        background:
            radial-gradient(ellipse at 36% 8%, rgba(255, 255, 255, 0.94), transparent 36%),
            radial-gradient(ellipse at 82% 100%, rgba(147, 197, 253, 0.3), transparent 34%);
        pointer-events: none;
    }
    .progress-hero-inner {
        justify-content: flex-start;
        min-height: 210px;
        padding: 32px clamp(270px, 34%, 360px) 32px 32px;
    }
    .progress-hero-copy {
        display: grid;
        grid-template-columns: 64px minmax(0, 1fr);
        gap: 22px;
        align-items: start;
    }
    .progress-hero-icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        display: grid;
        place-items: center;
        color: #2563eb;
        background: rgba(37, 99, 235, 0.06);
        border: 1px solid rgba(37, 99, 235, 0.14);
        font-size: 2rem;
    }
    .progress-hero-title {
        display: block;
        font-size: clamp(2rem, 4.6vw, 3rem);
        font-weight: 950;
        margin: 0 0 16px;
    }
    .progress-hero-title svg {
        display: none;
    }
    .progress-hero-subtitle {
        max-width: 520px;
        color: #475569;
        font-size: clamp(1.08rem, 2vw, 1.45rem);
        line-height: 1.55;
        font-weight: 500;
    }
    :root:not(.lm) .progress-hero-subtitle {
        color: #b7c3d6;
    }
    .progress-hero-art {
        right: 26px;
        bottom: 14px;
        width: clamp(220px, 28vw, 310px);
    }
    .progress-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 22px;
    }
    .progress-export-btn {
        min-height: 72px;
        border: 0;
        border-radius: 16px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        color: #fff;
        font-size: 1.25rem;
        font-weight: 900 !important;
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }
    .progress-export-btn.pdf {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
    }
    .progress-export-btn.excel {
        background: linear-gradient(135deg, #16a34a, #047857);
    }
    .progress-export-btn i {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        color: currentColor;
        background: rgba(255,255,255,0.88);
        font-size: 0.95rem;
    }
    .progress-export-btn.pdf i::before {
        content: "PDF";
        font-family: "Poppins", sans-serif;
        font-size: 0.58rem;
        font-weight: 950;
    }
    .progress-export-btn.excel i::before {
        content: "XLS";
        font-family: "Poppins", sans-serif;
        font-size: 0.58rem;
        font-weight: 950;
    }
    .progress-export-btn i::before {
        line-height: 1;
    }
    .progress-export-btn.pdf i { color: #2563eb; }
    .progress-export-btn.excel i { color: #16a34a; }
    .progress-stat-card {
        min-height: 186px;
        border-radius: 20px;
        border: 1px solid rgba(226, 232, 240, 0.9);
        background: var(--sf);
        display: grid;
        place-items: center;
        padding: 22px;
        text-align: center;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
    }
    .progress-stat-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        margin-bottom: 18px;
        color: var(--stat-accent, #2563eb);
        background: color-mix(in srgb, var(--stat-accent, #2563eb) 12%, transparent);
        font-size: 1.7rem;
    }
    .progress-stat-value {
        color: var(--tx);
        font-size: 2rem;
        line-height: 1;
        font-weight: 950;
    }
    .progress-stat-label {
        margin-top: 10px;
        color: var(--tx3);
        font-size: 1rem;
        line-height: 1.25;
        font-weight: 600;
    }
    .progress-ai-insight {
        position: relative;
        overflow: hidden;
        min-height: 180px;
        border: 1px solid rgba(191, 219, 254, 0.8) !important;
        border-radius: 20px !important;
        background:
            radial-gradient(circle at 88% 38%, rgba(37, 99, 235, 0.16), transparent 30%),
            linear-gradient(135deg, rgba(239,246,255,0.98), rgba(219,234,254,0.88)) !important;
        color: var(--tx) !important;
        box-shadow: 0 16px 34px rgba(37, 99, 235, 0.1);
        padding: 28px !important;
    }
    .progress-ai-content {
        display: grid;
        grid-template-columns: 64px minmax(0, 1fr) 160px;
        gap: 22px;
        align-items: center;
    }
    .progress-ai-icon {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        color: #2563eb;
        background: #fff;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.12);
        font-size: 1.7rem;
    }
    .progress-ai-title {
        margin: 0 0 8px;
        color: #2563eb;
        font-size: 1.45rem;
        line-height: 1.2;
        font-weight: 950;
    }
    .progress-ai-text {
        margin: 0;
        color: #334155;
        font-size: 1rem;
        line-height: 1.55;
        font-weight: 500;
    }
    .progress-ai-art {
        justify-self: end;
        width: 140px;
        height: 120px;
        display: grid;
        place-items: center;
        color: #2563eb;
        opacity: 0.55;
        font-size: 5rem;
    }
    #sec-progress-tracking .progress-hero {
        --progress-hero-title-color: #2563eb;
        --progress-hero-text-color: #334155;
        --progress-hero-icon-bg: rgba(37, 99, 235, 0.08);
        --progress-hero-icon-border: rgba(37, 99, 235, 0.18);
    }
    #sec-progress-tracking .progress-hero-title {
        background: none !important;
        -webkit-text-fill-color: var(--progress-hero-title-color) !important;
        color: var(--progress-hero-title-color) !important;
    }
    #sec-progress-tracking .progress-hero-subtitle {
        color: var(--progress-hero-text-color) !important;
    }
    #sec-progress-tracking .progress-hero-icon {
        color: var(--progress-hero-title-color);
        background: var(--progress-hero-icon-bg);
        border-color: var(--progress-hero-icon-border);
    }
    #sec-progress-tracking .progress-hero-art {
        opacity: 0.92;
    }
    #sec-progress-tracking .progress-ai-insight {
        --progress-ai-title-color: #2563eb;
        --progress-ai-text-color: #334155;
        --progress-ai-icon-bg: #ffffff;
        --progress-ai-icon-color: #2563eb;
        color: var(--progress-ai-text-color) !important;
    }
    #sec-progress-tracking .progress-ai-title {
        color: var(--progress-ai-title-color) !important;
    }
    #sec-progress-tracking .progress-ai-text {
        color: var(--progress-ai-text-color) !important;
    }
    #sec-progress-tracking .progress-ai-icon {
        color: var(--progress-ai-icon-color) !important;
        background: var(--progress-ai-icon-bg) !important;
    }
    html[data-theme="dark"] #sec-progress-tracking .progress-hero,
    :root:not(.lm) #sec-progress-tracking .progress-hero {
        --progress-hero-title-color: #93c5fd;
        --progress-hero-text-color: #e2e8f0;
        --progress-hero-icon-bg: rgba(59, 130, 246, 0.2);
        --progress-hero-icon-border: rgba(147, 197, 253, 0.32);
        border-color: rgba(147, 197, 253, 0.34);
        background:
            radial-gradient(circle at 92% 10%, rgba(59, 130, 246, 0.28), transparent 35%),
            linear-gradient(135deg, #111827, #172554) !important;
    }
    html[data-theme="dark"] #sec-progress-tracking .progress-hero::before,
    :root:not(.lm) #sec-progress-tracking .progress-hero::before {
        background:
            radial-gradient(ellipse at 30% 8%, rgba(59, 130, 246, 0.2), transparent 36%),
            radial-gradient(ellipse at 82% 100%, rgba(14, 165, 233, 0.18), transparent 34%);
    }
    html[data-theme="dark"] #sec-progress-tracking .progress-hero-art,
    :root:not(.lm) #sec-progress-tracking .progress-hero-art {
        opacity: 1;
        filter: drop-shadow(0 16px 24px rgba(0, 0, 0, 0.38));
    }
    html[data-theme="dark"] #sec-progress-tracking .progress-ai-insight,
    :root:not(.lm) #sec-progress-tracking .progress-ai-insight {
        --progress-ai-title-color: #93c5fd;
        --progress-ai-text-color: #e2e8f0;
        --progress-ai-icon-bg: rgba(59, 130, 246, 0.2);
        --progress-ai-icon-color: #bfdbfe;
        border-color: rgba(147, 197, 253, 0.32) !important;
        background:
            radial-gradient(circle at 88% 38%, rgba(37, 99, 235, 0.28), transparent 30%),
            linear-gradient(135deg, #111827, #172554) !important;
        box-shadow: 0 16px 34px rgba(0, 0, 0, 0.28);
    }
    @media (max-width: 767px) {
        .progress-hero {
            min-height: 172px;
            border-radius: 18px;
        }
        .progress-hero-inner {
            min-height: 172px;
            padding: 22px 130px 22px 20px;
        }
        .progress-hero-copy {
            grid-template-columns: 52px minmax(0, 1fr);
            gap: 14px;
        }
        .progress-hero-icon {
            width: 52px;
            height: 52px;
            border-radius: 15px;
            font-size: 1.55rem;
        }
        .progress-hero-title {
            font-size: clamp(1.38rem, 8vw, 2.05rem) !important;
            margin-bottom: 10px;
        }
        .progress-hero-subtitle {
            font-size: 0.96rem;
            line-height: 1.5;
        }
        .progress-hero-art {
            right: -6px;
            bottom: 12px;
            width: 138px;
        }
        .progress-actions {
            gap: 10px;
            margin-bottom: 16px;
        }
        .progress-export-btn {
            min-height: 58px !important;
            font-size: 0.95rem !important;
            gap: 9px;
            border-radius: 14px !important;
            white-space: nowrap !important;
        }
        .progress-export-btn i {
            width: 26px;
            height: 26px;
            font-size: 0.76rem;
        }
        #progress-stats {
            --bs-gutter-x: 12px;
            --bs-gutter-y: 12px;
        }
        .progress-stat-card {
            min-height: 150px !important;
            border-radius: 16px !important;
            padding: 18px 10px !important;
        }
        .progress-stat-icon {
            width: 58px;
            height: 58px;
            margin-bottom: 15px;
            font-size: 1.35rem !important;
        }
        .progress-stat-value {
            font-size: 1.45rem !important;
        }
        .progress-stat-label {
            margin-top: 8px;
            font-size: 0.78rem !important;
        }
        .progress-ai-insight {
            min-height: 150px;
            border-radius: 18px !important;
            padding: 20px !important;
        }
        .progress-ai-content {
            grid-template-columns: 52px minmax(0, 1fr) 84px;
            gap: 14px;
        }
        .progress-ai-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            font-size: 1.35rem !important;
        }
        .progress-ai-title {
            font-size: 1.12rem;
        }
        .progress-ai-text {
            font-size: 0.84rem;
            line-height: 1.5;
        }
        .progress-ai-art {
            width: 78px;
            height: 78px;
            font-size: 3rem;
        }
        #sec-progress-tracking .progress-hero {
            min-height: 96px;
        }
        #sec-progress-tracking .progress-hero-inner {
            min-height: 96px;
            padding: 12px 104px 12px 14px;
        }
        #sec-progress-tracking .progress-hero-copy {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
        }
        #sec-progress-tracking .progress-hero-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 1rem;
        }
        #sec-progress-tracking .progress-hero-title {
            font-size: 0.98rem !important;
            line-height: 1.08;
            margin-bottom: 4px;
        }
        #sec-progress-tracking .progress-hero-subtitle {
            max-width: 13.5rem;
            font-size: 0.68rem;
            line-height: 1.28;
        }
        #sec-progress-tracking .progress-hero-art {
            right: -6px;
            bottom: 4px;
            width: 92px;
        }
        #sec-progress-tracking .progress-ai-insight {
            min-height: 88px;
            padding: 14px !important;
            border-radius: 14px !important;
        }
        #sec-progress-tracking .progress-ai-content {
            grid-template-columns: 42px minmax(0, 1fr);
            gap: 12px;
        }
        #sec-progress-tracking .progress-ai-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            font-size: 1.05rem !important;
        }
        #sec-progress-tracking .progress-ai-title {
            font-size: 0.82rem;
            margin-bottom: 4px;
        }
        #sec-progress-tracking .progress-ai-text {
            font-size: 0.68rem;
            line-height: 1.3;
        }
        #sec-progress-tracking .progress-ai-art {
            display: none;
        }
    }
    @media (max-width: 575px) {
        #sec-progress-tracking {
            --progress-gap: 12px;
        }
        .progress-hero {
            min-height: 178px;
            margin-bottom: var(--progress-gap);
            border-radius: 18px !important;
        }
        .progress-hero-inner {
            min-height: 178px;
            padding: 18px 112px 18px 16px;
        }
        .progress-hero-copy {
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }
        .progress-hero-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            font-size: 1.25rem;
        }
        .progress-hero-title {
            font-size: clamp(1.3rem, 7.4vw, 1.85rem) !important;
            line-height: 1.12;
            margin-bottom: 8px;
            overflow-wrap: normal;
        }
        .progress-hero-subtitle {
            max-width: 19rem;
            font-size: 0.88rem;
            line-height: 1.45;
        }
        .progress-hero-art {
            right: -18px;
            bottom: 8px;
            width: 132px;
        }
        #sec-progress-tracking .progress-hero {
            min-height: 92px;
        }
        #sec-progress-tracking .progress-hero-inner {
            min-height: 92px;
            padding: 11px 96px 11px 12px;
        }
        #sec-progress-tracking .progress-hero-copy {
            grid-template-columns: 36px minmax(0, 1fr);
            gap: 9px;
        }
        #sec-progress-tracking .progress-hero-icon {
            width: 36px;
            height: 36px;
            font-size: 0.92rem;
        }
        #sec-progress-tracking .progress-hero-title {
            font-size: 0.9rem !important;
            margin-bottom: 4px;
        }
        #sec-progress-tracking .progress-hero-subtitle {
            max-width: 12rem;
            font-size: 0.64rem;
        }
        #sec-progress-tracking .progress-hero-art {
            right: -4px;
            bottom: 5px;
            width: 84px;
        }
        .progress-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--progress-gap);
            margin-bottom: var(--progress-gap);
        }
        .progress-export-btn {
            min-width: 0;
            min-height: 58px !important;
            padding: 10px 8px !important;
            font-size: clamp(0.78rem, 3.9vw, 0.95rem) !important;
            gap: 8px;
            line-height: 1.15;
        }
        #progress-stats {
            --bs-gutter-x: var(--progress-gap);
            --bs-gutter-y: var(--progress-gap);
        }
        #progress-stats > .col-md-3.col-sm-6 {
            width: 50% !important;
            flex: 0 0 50% !important;
        }
        .progress-stat-card {
            aspect-ratio: auto;
            min-height: 0 !important;
            padding: 12px 8px 11px !important;
        }
        .progress-stat-icon {
            width: 42px;
            height: 42px;
            margin-bottom: 9px;
            font-size: 1.05rem !important;
        }
        .progress-stat-value {
            font-size: clamp(1.08rem, 5.4vw, 1.28rem) !important;
            overflow-wrap: anywhere;
        }
        .progress-stat-label {
            margin-top: 5px;
            font-size: clamp(0.62rem, 3vw, 0.72rem) !important;
        }
        .progress-ai-insight {
            min-height: 0;
            margin-bottom: var(--progress-gap) !important;
        }
        .progress-ai-content {
            grid-template-columns: 48px minmax(0, 1fr);
            gap: 14px;
        }
        .progress-ai-art {
            display: none;
        }
    }
    @media (max-width: 430px) {
        .progress-hero {
            min-height: 160px;
        }
        .progress-hero-inner {
            min-height: 160px;
            padding: 16px 92px 16px 14px;
        }
        .progress-hero-copy {
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 10px;
        }
        .progress-hero-icon {
            width: 40px;
            height: 40px;
            border-radius: 13px;
            font-size: 1.1rem;
        }
        .progress-hero-title {
            font-size: clamp(1.14rem, 7.1vw, 1.45rem) !important;
        }
        .progress-hero-subtitle {
            font-size: 0.78rem;
        }
        .progress-hero-art {
            width: 112px;
            right: -24px;
            bottom: 10px;
        }
        .progress-actions {
            gap: 9px;
        }
        .progress-export-btn i {
            width: 24px;
            height: 24px;
        }
        .progress-stat-card {
            min-height: 0 !important;
        }
        .progress-stat-icon {
            width: 38px;
            height: 38px;
            margin-bottom: 8px;
        }
    }
    @media (max-width: 360px) {
        .progress-hero {
            min-height: 150px;
        }
        .progress-hero-inner {
            min-height: 150px;
            padding-right: 76px;
        }
        .progress-hero-copy {
            grid-template-columns: 34px minmax(0, 1fr);
            gap: 9px;
        }
        .progress-hero-icon {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            font-size: 0.95rem;
        }
        .progress-hero-title {
            font-size: 1rem !important;
            margin-bottom: 6px;
        }
        .progress-hero-subtitle {
            font-size: 0.72rem;
            line-height: 1.4;
        }
        .progress-hero-art {
            width: 92px;
            right: -22px;
        }
        .progress-export-btn {
            min-height: 52px !important;
            font-size: 0.72rem !important;
        }
        .progress-export-btn i {
            display: none;
        }
        .progress-stat-card {
            min-height: 0 !important;
            padding: 9px 6px !important;
        }
        .progress-stat-icon {
            width: 34px;
            height: 34px;
            font-size: 0.9rem !important;
            margin-bottom: 7px;
        }
        .progress-stat-value {
            font-size: 1rem !important;
        }
        .progress-stat-label {
            font-size: 0.6rem !important;
        }
        .progress-ai-content {
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 10px;
        }
        .progress-ai-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            font-size: 1rem !important;
        }
        .progress-ai-title {
            font-size: 0.96rem;
        }
        .progress-ai-text {
            font-size: 0.76rem;
        }
    }
    @media (max-width: 767px) {
        .progress-chart-panel {
            border-radius: 18px !important;
            padding: 20px !important;
        }
        .progress-panel-heading {
            grid-template-columns: 54px minmax(0, 1fr);
            gap: 14px;
            margin-bottom: 10px;
            height: auto !important;
            min-height: 0 !important;
        }
        .progress-panel-icon {
            width: 54px;
            height: 54px;
            border-radius: 15px;
            font-size: 1.45rem;
        }
        #sec-progress-tracking .progress-panel-title {
            font-size: clamp(1.18rem, 5.8vw, 1.55rem) !important;
            margin-bottom: 0 !important;
        }
        .progress-panel-subtitle {
            font-size: 0.88rem;
            line-height: 1.38;
            margin-top: 6px;
        }
        .progress-chart-frame {
            height: 220px;
        }
        .progress-chart-frame.scenario {
            height: 240px;
        }
        .skill-empty-state {
            min-height: 190px;
            border-radius: 16px;
            padding: 26px 14px;
        }
        .skill-empty-icon {
            width: 74px;
            height: 74px;
            margin-bottom: 12px;
            font-size: 3rem;
        }
        .skill-empty-text {
            font-size: 0.96rem;
        }
        .strengths-star-panel,
        .history-panel {
            border-radius: 18px !important;
            padding: 20px !important;
        }
        .strengths-overview,
        .star-overview {
            grid-template-columns: 64px minmax(0, 1fr);
            gap: 18px;
        }
        .strengths-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            font-size: 2rem;
        }
        .star-icon {
            width: 62px;
            height: 62px;
            font-size: 1.9rem;
        }
        #sec-progress-tracking .strengths-title,
        #sec-progress-tracking .star-title,
        #sec-progress-tracking .history-title {
            font-size: clamp(1.16rem, 5.8vw, 1.55rem) !important;
            margin-bottom: 0 !important;
        }
        .strengths-text,
        .star-text {
            font-size: 0.98rem;
            line-height: 1.5;
        }
        .strengths-lists {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .star-note {
            grid-template-columns: 52px minmax(0, 1fr);
            gap: 14px;
            padding: 18px;
            border-radius: 16px;
        }
        .star-note-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            font-size: 1.5rem;
        }
        .star-note-text {
            font-size: 0.88rem;
        }
        .history-top {
            align-items: flex-start;
            flex-direction: column;
            gap: 14px;
        }
        .history-actions,
        .history-actions form,
        .history-clear-btn {
            width: 100%;
        }
        .history-clear-btn {
            min-height: 52px;
        }
        .history-search {
            min-height: 56px;
            grid-template-columns: 44px minmax(0, 1fr);
            border-radius: 15px;
        }
        .history-card {
            padding: 18px;
            border-radius: 16px;
        }
        .history-scenario {
            font-size: 1.12rem;
        }
        .history-card-actions {
            grid-template-columns: minmax(0, 1fr) 58px;
            gap: 12px;
        }
        .history-feedback-btn,
        .history-delete-btn {
            min-height: 54px;
            font-size: 0.9rem;
        }
        .history-delete-btn {
            width: 58px;
        }
        .learning-panel,
        .recommend-panel,
        .voice-panel {
            border-radius: 18px !important;
            padding: 20px !important;
        }
        .learning-heading,
        .voice-heading,
        .recommend-heading {
            grid-template-columns: 54px minmax(0, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }
        .learning-heading-icon,
        .voice-heading-icon,
        .recommend-heading-icon {
            width: 54px;
            height: 54px;
            border-radius: 15px;
            font-size: 1.45rem;
        }
        #sec-progress-tracking .learning-title,
        #sec-progress-tracking .voice-title,
        #sec-progress-tracking .recommend-title {
            font-size: clamp(1.16rem, 5.8vw, 1.55rem) !important;
            margin-bottom: 0 !important;
        }
        .learning-subtitle,
        .voice-subtitle,
        .recommend-subtitle {
            font-size: 0.88rem;
        }
        .learning-list {
            gap: 20px;
        }
        .learning-module {
            grid-template-columns: 60px minmax(0, 1fr);
            gap: 16px;
            padding-bottom: 20px;
        }
        .learning-module-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            font-size: 1.5rem;
        }
        .learning-module-title,
        .learning-percent {
            font-size: 0.98rem;
        }
        .learning-track {
            height: 10px;
        }
        .learning-summary {
            grid-template-columns: 86px minmax(0, 1fr);
            gap: 18px;
            padding: 22px;
        }
        .learning-summary-icon {
            width: 78px;
            height: 78px;
            font-size: 2.2rem;
        }
        .learning-summary-value {
            font-size: 2rem;
        }
        .learning-summary-label {
            font-size: 0.92rem;
        }
        .recommend-item {
            grid-template-columns: 56px minmax(0, 1fr) 24px;
            gap: 16px;
            padding: 18px;
            border-radius: 16px;
        }
        .recommend-item-icon {
            width: 56px;
            height: 56px;
            border-radius: 15px;
            font-size: 1.4rem;
        }
        .recommend-item-title {
            font-size: 0.98rem;
        }
        .recommend-item-text {
            font-size: 0.86rem;
        }
        .recommend-arrow {
            font-size: 1.4rem;
        }
        .voice-empty {
            min-height: 220px;
            padding: 28px 14px;
        }
        .voice-empty-icon {
            width: 82px;
            height: 82px;
            margin-bottom: 16px;
            font-size: 3rem;
        }
        .voice-empty-text {
            font-size: 0.9rem;
        }
        .activity-panel,
        .goals-panel,
        .badges-panel {
            border-radius: 18px !important;
            padding: 20px !important;
        }
        .activity-heading,
        .goals-heading,
        .badges-heading {
            grid-template-columns: 56px minmax(0, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .activity-heading-icon,
        .goals-heading-icon,
        .badges-heading-icon {
            width: 56px;
            height: 56px;
            border-radius: 15px;
            font-size: 1.55rem;
        }
        #sec-progress-tracking .activity-title,
        #sec-progress-tracking .goals-title,
        #sec-progress-tracking .badges-title {
            font-size: clamp(1.16rem, 5.8vw, 1.55rem) !important;
            margin-bottom: 0 !important;
        }
        .activity-subtitle,
        .goals-subtitle,
        .badges-subtitle {
            font-size: 0.88rem;
        }
        .activity-empty {
            padding: 0 4px 4px;
        }
        .activity-illustration {
            width: min(340px, 100%);
            margin-bottom: 20px;
        }
        .activity-empty-title {
            font-size: 1.12rem;
        }
        .activity-empty-text {
            font-size: 0.9rem;
            margin-bottom: 22px;
        }
        .activity-cta {
            min-height: 54px;
            font-size: 0.94rem;
        }
        .goal-title,
        .goal-percent {
            font-size: 0.98rem;
        }
        .goal-note {
            grid-template-columns: 52px minmax(0, 1fr);
            gap: 14px;
            padding: 16px;
            margin-top: 26px;
        }
        .goal-note-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            font-size: 1.35rem;
        }
        .badge-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .badge-medal {
            width: 56px;
            height: 56px;
            margin-bottom: 8px;
            font-size: 1.35rem;
            box-shadow: inset 0 0 0 6px rgba(255, 255, 255, 0.45);
        }
        .badge-title {
            font-size: 0.68rem;
            line-height: 1.12;
        }
        .badge-desc {
            font-size: 0.6rem;
            line-height: 1.12;
        }
        #personalized-practice-plan .practice-plan-panel {
            border-radius: 18px !important;
            padding: 20px !important;
        }
        .practice-plan-heading {
            grid-template-columns: 52px minmax(0, 1fr);
            gap: 14px;
            margin-bottom: 18px;
        }
        .practice-plan-heading-icon {
            width: 52px;
            height: 52px;
            border-radius: 15px;
            font-size: 1.45rem;
        }
        #personalized-practice-plan .practice-plan-heading-title {
            font-size: clamp(1.26rem, 6.2vw, 1.65rem) !important;
            margin-bottom: 0 !important;
        }
        .practice-plan-heading-text {
            font-size: 0.94rem;
            line-height: 1.42;
            margin-top: 8px;
        }
        .practice-plan-list {
            gap: 14px;
        }
        .practice-plan-row {
            grid-template-columns: 58px minmax(0, 1fr);
            gap: 16px;
            border-radius: 18px !important;
            padding: 18px !important;
        }
        .practice-plan-icon {
            width: 46px;
            height: 46px;
            flex-basis: 46px;
            border-radius: 14px;
            font-size: 1.15rem;
        }
        .practice-plan-top {
            gap: 9px;
            margin-bottom: 8px;
        }
        .practice-plan-step {
            padding: 7px 11px;
            font-size: 0.78rem;
        }
        .practice-plan-title {
            font-size: 1.02rem;
        }
        .practice-plan-text {
            font-size: 0.88rem;
        }
        .practice-plan-tasks {
            gap: 7px;
            margin-top: 13px;
        }
        .practice-plan-tasks li {
            gap: 8px;
            font-size: 0.84rem;
        }
        .practice-plan-tasks li i {
            width: 21px;
            height: 21px;
            flex-basis: 21px;
            border-radius: 7px;
            font-size: 0.72rem;
        }
        .practice-plan-footer {
            flex-direction: row;
            align-items: center;
            gap: 12px;
            margin-top: 14px;
        }
        .practice-plan-pill {
            padding: 7px 10px;
            font-size: 0.8rem;
        }
        .practice-plan-link {
            font-size: 0.86rem;
        }
    }
    @media (max-width: 430px) {
        .progress-chart-panel {
            padding: 16px !important;
            border-radius: 18px !important;
        }
        .progress-panel-heading {
            grid-template-columns: 46px minmax(0, 1fr);
            gap: 12px;
            margin-bottom: 8px;
            height: auto !important;
            min-height: 0 !important;
        }
        .progress-panel-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            font-size: 1.2rem;
        }
        #sec-progress-tracking .progress-panel-title {
            font-size: 1.08rem !important;
        }
        .progress-panel-subtitle {
            font-size: 0.78rem;
        }
        .progress-chart-frame {
            height: 190px;
        }
        .progress-chart-frame.scenario {
            height: 220px;
        }
        .skill-empty-state {
            min-height: 170px;
            padding: 22px 12px;
        }
        .skill-empty-icon {
            width: 64px;
            height: 64px;
            font-size: 2.5rem;
        }
        .skill-empty-text {
            font-size: 0.86rem;
        }
        #readiness-trend .progress-chart-panel,
        #category-perf .progress-chart-panel,
        #skill-tracker .progress-chart-panel {
            padding: 12px !important;
            border-radius: 13px !important;
        }
        #readiness-trend .progress-panel-heading,
        #category-perf .progress-panel-heading,
        #skill-tracker .progress-panel-heading {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }
        #readiness-trend .progress-panel-icon,
        #category-perf .progress-panel-icon,
        #skill-tracker .progress-panel-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 1rem;
        }
        #sec-progress-tracking #readiness-trend .progress-panel-title,
        #sec-progress-tracking #category-perf .progress-panel-title,
        #sec-progress-tracking #skill-tracker .progress-panel-title {
            font-size: 0.92rem !important;
        }
        #readiness-trend .progress-panel-subtitle,
        #category-perf .progress-panel-subtitle,
        #skill-tracker .progress-panel-subtitle {
            font-size: 0.72rem;
        }
        #readiness-trend .progress-chart-frame {
            height: 142px;
        }
        #category-perf .progress-chart-frame.scenario {
            height: 158px;
        }
        #skill-tracker .skill-empty-state {
            min-height: 112px;
            padding: 12px 10px;
        }
        #skill-tracker .skill-empty-icon {
            width: 36px;
            height: 36px;
            margin-bottom: 7px;
            font-size: 1.35rem;
        }
        #skill-tracker .skill-empty-text {
            max-width: 190px;
            font-size: 0.68rem;
            line-height: 1.2;
        }
        .strengths-star-panel,
        .history-panel {
            padding: 16px !important;
        }
        .strengths-overview,
        .star-overview,
        .history-heading {
            grid-template-columns: 48px minmax(0, 1fr);
            gap: 13px;
        }
        .strengths-overview {
            padding-bottom: 22px;
            margin-bottom: 22px;
        }
        .strengths-icon,
        .star-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 1.45rem;
        }
        .history-icon {
            width: 38px;
            height: 38px;
            font-size: 1.45rem;
        }
        #sec-progress-tracking .strengths-title,
        #sec-progress-tracking .star-title,
        #sec-progress-tracking .history-title {
            font-size: 1.08rem !important;
        }
        .strengths-text,
        .star-text {
            font-size: 0.86rem;
        }
        .star-note {
            grid-template-columns: 44px minmax(0, 1fr);
            padding: 15px;
        }
        .star-note-icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            font-size: 1.25rem;
        }
        .star-note-title {
            font-size: 0.95rem;
        }
        .star-note-text {
            font-size: 0.78rem;
        }
        #strengths-tracker .strengths-star-panel {
            padding: 12px !important;
        }
        #strengths-tracker .strengths-overview,
        #strengths-tracker .star-overview {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
        }
        #strengths-tracker .strengths-overview {
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        #strengths-tracker .star-overview {
            margin-bottom: 12px;
        }
        #strengths-tracker .strengths-icon,
        #strengths-tracker .star-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 1.05rem;
        }
        #strengths-tracker .star-icon {
            border-radius: 50%;
        }
        #sec-progress-tracking #strengths-tracker .strengths-title,
        #sec-progress-tracking #strengths-tracker .star-title {
            font-size: 0.9rem !important;
            line-height: 1.15;
        }
        #strengths-tracker .strengths-text,
        #strengths-tracker .star-text {
            margin-top: 6px;
            font-size: 0.75rem;
            line-height: 1.34;
        }
        #strengths-tracker .star-note {
            grid-template-columns: 34px minmax(0, 1fr);
            gap: 10px;
            padding: 10px;
        }
        #strengths-tracker .star-note-icon {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            font-size: 0.92rem;
        }
        #strengths-tracker .star-note-title {
            font-size: 0.74rem;
        }
        #strengths-tracker .star-note-text {
            font-size: 0.68rem;
            line-height: 1.36;
        }
        .history-search input {
            font-size: 0.92rem;
        }
        .history-card {
            padding: 15px;
        }
        .history-date,
        .history-meta {
            font-size: 0.86rem;
        }
        .history-scenario {
            font-size: 1rem;
        }
        .history-card-actions {
            grid-template-columns: minmax(0, 1fr) 48px;
            gap: 10px;
        }
        .history-feedback-btn,
        .history-delete-btn {
            min-height: 48px;
            font-size: 0.82rem;
        }
        .history-delete-btn {
            width: 48px;
        }
        #history-table .history-card {
            padding: 12px;
            border-radius: 13px;
        }
        #history-table .history-date,
        #history-table .history-meta {
            font-size: 0.72rem;
        }
        #history-table .history-date {
            margin-bottom: 8px;
        }
        #history-table .history-scenario {
            margin-bottom: 10px;
            font-size: 0.86rem;
        }
        #history-table .history-meta {
            gap: 7px;
            margin-bottom: 12px;
        }
        #history-table .history-rating-badge {
            padding: 4px 9px;
            font-size: 0.68rem;
        }
        #history-table .history-card-actions {
            grid-template-columns: minmax(0, 1fr) 38px;
            gap: 7px;
        }
        #history-table .history-feedback-btn,
        #history-table .history-delete-btn {
            min-height: 36px;
            border-radius: 10px !important;
            font-size: 0.68rem;
        }
        #history-table .history-delete-btn {
            width: 38px;
        }
        .learning-panel,
        .recommend-panel,
        .voice-panel {
            padding: 16px !important;
        }
        .learning-heading,
        .voice-heading,
        .recommend-heading {
            grid-template-columns: 46px minmax(0, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }
        .learning-heading-icon,
        .voice-heading-icon,
        .recommend-heading-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            font-size: 1.2rem;
        }
        #sec-progress-tracking .learning-title,
        #sec-progress-tracking .voice-title,
        #sec-progress-tracking .recommend-title {
            font-size: 1.08rem !important;
        }
        .learning-subtitle,
        .voice-subtitle,
        .recommend-subtitle {
            font-size: 0.78rem;
        }
        .learning-module {
            grid-template-columns: 48px minmax(0, 1fr);
            gap: 12px;
        }
        .learning-module-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 1.2rem;
        }
        .learning-module-top {
            gap: 10px;
            margin-bottom: 12px;
        }
        .learning-module-title,
        .learning-percent {
            font-size: 0.84rem;
        }
        .learning-summary {
            grid-template-columns: 64px minmax(0, 1fr);
            gap: 14px;
            padding: 18px;
        }
        .learning-summary-icon {
            width: 58px;
            height: 58px;
            font-size: 1.7rem;
        }
        .learning-summary-value {
            font-size: 1.55rem;
        }
        .learning-summary-label {
            font-size: 0.8rem;
        }
        #learning-progress .learning-panel {
            padding: 12px !important;
        }
        #learning-progress .learning-heading {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        #learning-progress .learning-heading-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 1rem;
        }
        #sec-progress-tracking #learning-progress .learning-title {
            font-size: 0.92rem !important;
        }
        #learning-progress .learning-subtitle {
            font-size: 0.72rem;
        }
        #learning-progress .learning-list {
            gap: 12px;
        }
        #learning-progress .learning-module {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            padding-bottom: 13px;
        }
        #learning-progress .learning-module-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 0.95rem;
        }
        #learning-progress .learning-module-top {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 8px;
            margin-bottom: 8px;
        }
        #learning-progress .learning-module-title {
            display: -webkit-box;
            min-width: 0;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            font-size: 0.74rem;
            line-height: 1.18;
        }
        #learning-progress .learning-percent {
            min-width: 34px;
            text-align: right;
            font-size: 0.72rem;
        }
        #learning-progress .learning-track {
            height: 7px;
        }
        #learning-progress .learning-summary {
            grid-template-columns: 46px minmax(0, 1fr);
            gap: 10px;
            margin-top: 12px;
            padding: 12px;
        }
        #learning-progress .learning-summary-icon {
            width: 42px;
            height: 42px;
            font-size: 1.2rem;
        }
        #learning-progress .learning-summary-value {
            font-size: 1.28rem;
        }
        #learning-progress .learning-summary-label {
            font-size: 0.68rem;
        }
        .recommend-item {
            grid-template-columns: 48px minmax(0, 1fr) 20px;
            gap: 12px;
            padding: 15px;
        }
        .recommend-item-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 1.2rem;
        }
        .recommend-item-title {
            font-size: 0.86rem;
        }
        .recommend-item-text {
            font-size: 0.78rem;
        }
        #recommended-next .recommend-panel {
            padding: 12px !important;
        }
        #recommended-next .recommend-heading {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        #recommended-next .recommend-heading-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 1rem;
        }
        #sec-progress-tracking #recommended-next .recommend-title {
            font-size: 0.92rem !important;
        }
        #recommended-next .recommend-subtitle {
            font-size: 0.72rem;
        }
        #recommended-next .recommend-item {
            grid-template-columns: 38px minmax(0, 1fr) 16px;
            gap: 10px;
            padding: 10px;
            border-radius: 12px;
        }
        #recommended-next .recommend-item-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 0.95rem;
        }
        #recommended-next .recommend-item-title {
            font-size: 0.76rem;
            line-height: 1.18;
        }
        #recommended-next .recommend-item-text {
            margin-top: 5px;
            font-size: 0.7rem;
            line-height: 1.28;
        }
        #recommended-next .recommend-arrow {
            font-size: 0.9rem;
        }
        .voice-empty {
            min-height: 190px;
            padding: 24px 12px;
        }
        .voice-empty-icon {
            width: 68px;
            height: 68px;
            font-size: 2.4rem;
        }
        .voice-empty-title {
            font-size: 0.95rem;
        }
        .voice-empty-text {
            font-size: 0.8rem;
        }
        .activity-panel,
        .goals-panel,
        .badges-panel {
            padding: 16px !important;
        }
        .activity-heading,
        .goals-heading,
        .badges-heading {
            grid-template-columns: 46px minmax(0, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }
        .activity-heading-icon,
        .goals-heading-icon,
        .badges-heading-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            font-size: 1.2rem;
        }
        #sec-progress-tracking .activity-title,
        #sec-progress-tracking .goals-title,
        #sec-progress-tracking .badges-title {
            font-size: 1.08rem !important;
        }
        .activity-subtitle,
        .goals-subtitle,
        .badges-subtitle {
            font-size: 0.78rem;
        }
        .activity-empty-title {
            font-size: 0.98rem;
        }
        .activity-empty-text {
            font-size: 0.8rem;
        }
        .activity-cta {
            width: 100%;
            min-width: 0;
            min-height: 48px;
            font-size: 0.84rem;
        }
        #voice-progress .voice-panel,
        #activity-calendar .activity-panel {
            padding: 12px !important;
        }
        #voice-progress .voice-heading,
        #activity-calendar .activity-heading {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        #voice-progress .voice-heading-icon,
        #activity-calendar .activity-heading-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 1rem;
        }
        #sec-progress-tracking #voice-progress .voice-title,
        #sec-progress-tracking #activity-calendar .activity-title {
            font-size: 0.92rem !important;
        }
        #voice-progress .voice-subtitle,
        #activity-calendar .activity-subtitle {
            font-size: 0.72rem;
        }
        #voice-progress .voice-empty {
            min-height: 138px;
            padding: 14px 10px;
            border-radius: 12px;
        }
        #voice-progress .voice-empty-icon {
            width: 46px;
            height: 46px;
            margin-bottom: 9px;
            font-size: 1.45rem;
        }
        #voice-progress .voice-empty-title {
            margin-bottom: 6px;
            font-size: 0.78rem;
        }
        #voice-progress .voice-empty-text {
            font-size: 0.68rem;
            line-height: 1.32;
        }
        #activity-calendar .activity-empty {
            padding: 0 4px;
        }
        #activity-calendar .activity-illustration {
            width: min(176px, 78%);
            margin-bottom: 8px;
        }
        #activity-calendar .activity-empty-title {
            max-width: 218px;
            margin-bottom: 6px;
            font-size: 0.8rem;
            line-height: 1.18;
        }
        #activity-calendar .activity-empty-text {
            margin-bottom: 10px;
            font-size: 0.68rem;
            line-height: 1.3;
        }
        #activity-calendar .activity-cta {
            min-height: 36px;
            border-radius: 10px !important;
            font-size: 0.72rem;
        }
        .goal-title,
        .goal-percent {
            font-size: 0.86rem;
        }
        .goal-note {
            grid-template-columns: 44px minmax(0, 1fr);
            padding: 14px;
        }
        .goal-note-icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            font-size: 1.15rem;
        }
        .goal-note-title {
            font-size: 0.92rem;
        }
        .goal-note-text {
            font-size: 0.78rem;
        }
        #goals-milestones .goals-panel {
            padding: 12px !important;
        }
        #goals-milestones .goals-heading {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        #goals-milestones .goals-heading-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 1rem;
        }
        #sec-progress-tracking #goals-milestones .goals-title {
            font-size: 0.92rem !important;
        }
        #goals-milestones .goals-subtitle {
            font-size: 0.72rem;
        }
        #goals-milestones .goal-top {
            margin-bottom: 8px;
        }
        #goals-milestones .goal-title,
        #goals-milestones .goal-percent {
            font-size: 0.76rem;
        }
        #goals-milestones .goal-track {
            height: 7px;
        }
        #goals-milestones .goal-note {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            margin-top: 16px;
            padding: 10px;
        }
        #goals-milestones .goal-note-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            font-size: 0.95rem;
        }
        #goals-milestones .goal-note-title {
            font-size: 0.76rem;
        }
        #goals-milestones .goal-note-text {
            font-size: 0.68rem;
            line-height: 1.28;
        }
        .badge-medal {
            width: 74px;
            height: 74px;
            font-size: 1.85rem;
        }
        .badge-title {
            font-size: 0.8rem;
        }
        .badge-desc {
            font-size: 0.7rem;
        }
        #achievements-badges .badges-panel {
            padding: 12px !important;
        }
        #achievements-badges .badges-heading {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        #achievements-badges .badges-heading-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            font-size: 1rem;
        }
        #sec-progress-tracking #achievements-badges .badges-title {
            font-size: 0.92rem !important;
        }
        #achievements-badges .badges-subtitle {
            font-size: 0.72rem;
        }
        #achievements-badges .badge-grid {
            gap: 7px;
        }
        #achievements-badges .badge-medal {
            width: 50px;
            height: 50px;
            margin-bottom: 6px;
            font-size: 1.18rem;
            box-shadow: inset 0 0 0 5px rgba(255, 255, 255, 0.45);
        }
        #achievements-badges .badge-title {
            font-size: 0.58rem;
            line-height: 1.02;
        }
        #achievements-badges .badge-desc {
            font-size: 0.5rem;
            line-height: 1.05;
        }
        #personalized-practice-plan .practice-plan-panel {
            padding: 16px !important;
        }
        .practice-plan-heading {
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .practice-plan-heading-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            font-size: 1.22rem;
        }
        #personalized-practice-plan .practice-plan-heading-title {
            font-size: 1.16rem !important;
        }
        .practice-plan-heading-text {
            font-size: 0.82rem;
        }
        .practice-plan-row {
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 11px;
            padding: 15px !important;
        }
        .practice-plan-icon {
            width: 40px;
            height: 40px;
            flex-basis: 40px;
            border-radius: 12px;
            font-size: 1rem;
        }
        .practice-plan-title {
            font-size: 0.92rem;
        }
        .practice-plan-text,
        .practice-plan-tasks li {
            font-size: 0.78rem;
        }
        .practice-plan-footer {
            align-items: flex-start;
            flex-direction: column;
            gap: 8px;
        }
        #personalized-practice-plan .practice-plan-row {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            padding: 12px !important;
        }
        #personalized-practice-plan .practice-plan-icon {
            width: 38px;
            height: 38px;
            flex-basis: 38px;
            border-radius: 11px;
            font-size: 0.92rem;
        }
        #personalized-practice-plan .practice-plan-top {
            gap: 7px;
            margin-bottom: 7px;
        }
        #personalized-practice-plan .practice-plan-step {
            padding: 4px 8px;
            font-size: 0.62rem;
        }
        #personalized-practice-plan .practice-plan-title {
            font-size: 0.78rem;
        }
        #personalized-practice-plan .practice-plan-text,
        #personalized-practice-plan .practice-plan-tasks li {
            font-size: 0.68rem;
        }
        #personalized-practice-plan .practice-plan-footer {
            flex-direction: row;
            align-items: center;
            gap: 8px;
            margin-top: 9px;
        }
        #personalized-practice-plan .practice-plan-pill {
            padding: 4px 8px;
            font-size: 0.62rem;
        }
        #personalized-practice-plan .practice-plan-link {
            font-size: 0.68rem;
        }
    }
    @media (max-width: 360px) {
        .progress-panel-heading {
            grid-template-columns: 1fr;
        }
        #readiness-trend .progress-panel-heading,
        #category-perf .progress-panel-heading,
        #skill-tracker .progress-panel-heading {
            grid-template-columns: 36px minmax(0, 1fr);
        }
        #readiness-trend .progress-panel-icon,
        #category-perf .progress-panel-icon,
        #skill-tracker .progress-panel-icon {
            width: 36px;
            height: 36px;
            font-size: 0.92rem;
        }
        .progress-chart-frame {
            height: 180px;
        }
        .progress-chart-frame.scenario {
            height: 210px;
        }
        .strengths-overview,
        .star-overview,
        .star-note,
        .history-heading {
            grid-template-columns: 1fr;
        }
        .history-card-actions {
            grid-template-columns: 1fr;
        }
        .history-delete-btn {
            width: 100%;
        }
        #history-table .history-card-actions {
            grid-template-columns: minmax(0, 1fr) 36px;
        }
        #history-table .history-delete-btn {
            width: 36px;
        }
        .learning-heading,
        .voice-heading,
        .recommend-heading,
        .activity-heading,
        .goals-heading,
        .badges-heading,
        .goal-note,
        .learning-module,
        .learning-summary,
        .recommend-item {
            grid-template-columns: 1fr;
        }
        #recommended-next .recommend-heading {
            grid-template-columns: 36px minmax(0, 1fr);
        }
        #recommended-next .recommend-item {
            grid-template-columns: 36px minmax(0, 1fr) 14px;
            gap: 8px;
            padding: 9px;
        }
        #recommended-next .recommend-item-icon {
            width: 36px;
            height: 36px;
            font-size: 0.88rem;
        }
        #recommended-next .recommend-item-title {
            font-size: 0.72rem;
        }
        #recommended-next .recommend-item-text {
            font-size: 0.66rem;
        }
        #voice-progress .voice-heading,
        #activity-calendar .activity-heading {
            grid-template-columns: 36px minmax(0, 1fr);
        }
        #voice-progress .voice-heading-icon,
        #activity-calendar .activity-heading-icon {
            width: 36px;
            height: 36px;
            font-size: 0.92rem;
        }
        #voice-progress .voice-empty {
            min-height: 126px;
            padding: 12px 8px;
        }
        #activity-calendar .activity-illustration {
            width: min(150px, 74%);
        }
        #goals-milestones .goals-heading,
        #goals-milestones .goal-note {
            grid-template-columns: 36px minmax(0, 1fr);
        }
        #goals-milestones .goals-heading-icon,
        #goals-milestones .goal-note-icon {
            width: 36px;
            height: 36px;
            font-size: 0.9rem;
        }
        #achievements-badges .badges-heading {
            grid-template-columns: 36px minmax(0, 1fr);
        }
        #achievements-badges .badges-heading-icon {
            width: 36px;
            height: 36px;
            font-size: 0.9rem;
        }
        .badge-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }
        .badge-medal {
            width: 48px;
            height: 48px;
            font-size: 1.12rem;
            box-shadow: inset 0 0 0 5px rgba(255, 255, 255, 0.45);
        }
        .badge-title {
            font-size: 0.62rem;
        }
        .badge-desc {
            font-size: 0.55rem;
        }
        #achievements-badges .badge-grid {
            gap: 6px;
        }
        #achievements-badges .badge-medal {
            width: 44px;
            height: 44px;
            font-size: 1rem;
        }
        #achievements-badges .badge-title {
            font-size: 0.52rem;
        }
        #achievements-badges .badge-desc {
            font-size: 0.46rem;
        }
        .practice-plan-heading {
            grid-template-columns: 1fr;
        }
        .practice-plan-row {
            grid-template-columns: 1fr;
        }
        #personalized-practice-plan .practice-plan-row {
            grid-template-columns: 36px minmax(0, 1fr);
        }
        .practice-plan-icon {
            width: 38px;
            height: 38px;
        }
        #personalized-practice-plan .practice-plan-icon {
            width: 36px;
            height: 36px;
        }
    }
    @media (max-width: 767px) {
        #readiness-trend .progress-panel-heading,
        #category-perf .progress-panel-heading,
        #skill-tracker .progress-panel-heading,
        #readiness-trend .premium-panel > .progress-panel-heading,
        #category-perf .premium-panel > .progress-panel-heading,
        #skill-tracker .premium-panel > .progress-panel-heading {
            height: auto !important;
            min-height: 0 !important;
            margin-bottom: 8px !important;
        }
        #readiness-trend .premium-panel > .progress-chart-frame {
            height: 142px !important;
        }
        #category-perf .premium-panel > .progress-chart-frame {
            height: 158px !important;
        }
        #sec-progress-tracking :is(
            .progress-chart-panel,
            .practice-plan-panel,
            .strengths-star-panel,
            .history-panel,
            .learning-panel,
            .recommend-panel,
            .voice-panel,
            .activity-panel,
            .goals-panel,
            .badges-panel
        ) {
            padding-top: 16px !important;
            padding-bottom: 16px !important;
        }
        #sec-progress-tracking :is(
            .progress-panel-heading,
            .practice-plan-heading,
            .strengths-overview,
            .star-overview,
            .history-top,
            .learning-heading,
            .recommend-heading,
            .voice-heading,
            .activity-heading,
            .goals-heading,
            .badges-heading
        ) {
            margin-bottom: 12px !important;
        }
    }
    html[data-theme="dark"] #sec-progress-tracking,
    :root:not(.lm) #sec-progress-tracking {
        --progress-surface: linear-gradient(135deg, rgba(17, 24, 39, 0.96), rgba(15, 23, 42, 0.94));
        --progress-card: rgba(255, 255, 255, 0.045);
        --progress-card-strong: rgba(255, 255, 255, 0.07);
        --progress-border: rgba(148, 163, 184, 0.2);
        --progress-muted: #c2ccda;
        --progress-muted-strong: #d6deea;
    }
    html[data-theme="light"] #sec-progress-tracking,
    .lm #sec-progress-tracking {
        --progress-surface: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(248,250,252,0.96));
        --progress-card: rgba(248, 250, 252, 0.72);
        --progress-card-strong: rgba(255, 255, 255, 0.96);
        --progress-border: rgba(226, 232, 240, 0.9);
        --progress-muted: #475569;
        --progress-muted-strong: #334155;
    }
    html[data-theme="dark"] #sec-progress-tracking :is(
        .progress-chart-panel,
        .strengths-star-panel,
        .history-panel,
        .learning-panel,
        .recommend-panel,
        .voice-panel,
        .activity-panel,
        .goals-panel,
        .badges-panel,
        .practice-plan-panel,
        .progress-stat-card
    ),
    :root:not(.lm) #sec-progress-tracking :is(
        .progress-chart-panel,
        .strengths-star-panel,
        .history-panel,
        .learning-panel,
        .recommend-panel,
        .voice-panel,
        .activity-panel,
        .goals-panel,
        .badges-panel,
        .practice-plan-panel,
        .progress-stat-card
    ) {
        background: var(--progress-surface) !important;
        border-color: var(--progress-border) !important;
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.28) !important;
    }
    html[data-theme="dark"] #sec-progress-tracking :is(
        .practice-plan-row,
        .star-note,
        .history-search,
        .history-card,
        .strengths-list-card,
        .learning-summary,
        .recommend-item,
        .voice-empty,
        .goal-note,
        .skill-empty-state
    ),
    :root:not(.lm) #sec-progress-tracking :is(
        .practice-plan-row,
        .star-note,
        .history-search,
        .history-card,
        .strengths-list-card,
        .learning-summary,
        .recommend-item,
        .voice-empty,
        .goal-note,
        .skill-empty-state
    ) {
        background: var(--progress-card) !important;
        border-color: var(--progress-border) !important;
    }
    html[data-theme="dark"] #sec-progress-tracking :is(
        .practice-plan-text,
        .practice-plan-tasks li,
        .practice-plan-heading-text,
        .progress-panel-subtitle,
        .strengths-text,
        .star-text,
        .star-note-text,
        .learning-subtitle,
        .voice-subtitle,
        .recommend-subtitle,
        .recommend-item-text,
        .voice-empty-text,
        .activity-subtitle,
        .goals-subtitle,
        .badges-subtitle,
        .activity-empty-text,
        .goal-note-text,
        .badge-desc,
        .learning-summary-label
    ),
    :root:not(.lm) #sec-progress-tracking :is(
        .practice-plan-text,
        .practice-plan-tasks li,
        .practice-plan-heading-text,
        .progress-panel-subtitle,
        .strengths-text,
        .star-text,
        .star-note-text,
        .learning-subtitle,
        .voice-subtitle,
        .recommend-subtitle,
        .recommend-item-text,
        .voice-empty-text,
        .activity-subtitle,
        .goals-subtitle,
        .badges-subtitle,
        .activity-empty-text,
        .goal-note-text,
        .badge-desc,
        .learning-summary-label
    ) {
        color: var(--progress-muted) !important;
    }
    html[data-theme="dark"] #sec-progress-tracking :is(.history-meta, .history-search i),
    :root:not(.lm) #sec-progress-tracking :is(.history-meta, .history-search i) {
        color: var(--progress-muted-strong) !important;
    }
    html[data-theme="dark"] #sec-progress-tracking :is(
        .progress-panel-icon,
        .practice-plan-icon,
        .strengths-icon,
        .star-icon,
        .star-note-icon,
        .learning-heading-icon,
        .voice-heading-icon,
        .recommend-heading-icon,
        .learning-module-icon,
        .recommend-item-icon,
        .activity-heading-icon,
        .goals-heading-icon,
        .badges-heading-icon
    ),
    :root:not(.lm) #sec-progress-tracking :is(
        .progress-panel-icon,
        .practice-plan-icon,
        .strengths-icon,
        .star-icon,
        .star-note-icon,
        .learning-heading-icon,
        .voice-heading-icon,
        .recommend-heading-icon,
        .learning-module-icon,
        .recommend-item-icon,
        .activity-heading-icon,
        .goals-heading-icon,
        .badges-heading-icon
    ) {
        background:
            radial-gradient(circle at 30% 16%, rgba(255,255,255,0.12), transparent 34%),
            color-mix(in srgb, var(--panel-accent, #3b82f6) 20%, rgba(15, 23, 42, 0.95)) !important;
    }
    html[data-theme="dark"] #sec-progress-tracking .badge-medal,
    :root:not(.lm) #sec-progress-tracking .badge-medal {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.18), rgba(120, 53, 15, 0.22)) !important;
        box-shadow: inset 0 0 0 12px rgba(255, 255, 255, 0.04) !important;
    }
    html[data-theme="dark"] #sec-progress-tracking .badge-item.locked .badge-medal,
    :root:not(.lm) #sec-progress-tracking .badge-item.locked .badge-medal {
        background: rgba(148, 163, 184, 0.08) !important;
        color: #94a3b8 !important;
    }

    #progressModulesLikeHero.progress-hero {
        --progress-hero-title-color: #1d4ed8;
        --progress-hero-text-color: #334155;
        --progress-hero-icon-bg: rgba(239, 246, 255, 0.92);
        --progress-hero-icon-border: rgba(147, 197, 253, 0.42);
        display: grid !important;
        grid-template-columns: 44px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 10px !important;
        min-height: 104px !important;
        padding: 14px 116px 14px 14px !important;
        margin-bottom: 12px !important;
        border-radius: 14px !important;
        overflow: hidden !important;
        position: relative !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(219, 234, 254, 0.78), transparent 35%),
            linear-gradient(142deg, #ffffff 0%, #f8fbff 52%, #dbeafe 100%) !important;
        border-color: rgba(147, 197, 253, 0.52) !important;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.1) !important;
    }
    html[data-theme="dark"] #progressModulesLikeHero.progress-hero,
    :root:not(.lm) #progressModulesLikeHero.progress-hero {
        --progress-hero-title-color: #93c5fd;
        --progress-hero-text-color: #e2e8f0;
        --progress-hero-icon-bg: rgba(59, 130, 246, 0.2);
        --progress-hero-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28) !important;
    }
    #progressModulesLikeHero .progress-hero-inner,
    #progressModulesLikeHero .progress-hero-copy {
        display: contents !important;
    }
    #progressModulesLikeHero .progress-hero-icon {
        box-sizing: border-box;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        border: 1px solid var(--progress-hero-icon-border) !important;
        border-radius: 10px !important;
        background: var(--progress-hero-icon-bg) !important;
        color: var(--progress-hero-title-color) !important;
        font-size: 0.9rem !important;
    }
    #progressModulesLikeHero .progress-hero-title {
        margin: 0 0 4px !important;
        font-size: 1.1rem !important;
        line-height: 1.15 !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        white-space: nowrap !important;
        color: var(--progress-hero-title-color) !important;
        -webkit-text-fill-color: var(--progress-hero-title-color) !important;
    }
    #progressModulesLikeHero .progress-hero-subtitle {
        max-width: 100% !important;
        margin: 0 !important;
        font-size: 0.74rem !important;
        line-height: 1.4 !important;
        color: var(--progress-hero-text-color) !important;
    }
    #progressModulesLikeHero .progress-hero-art {
        position: absolute !important;
        right: -10px !important;
        bottom: -1px !important;
        width: 112px !important;
        min-width: 0 !important;
        max-width: none !important;
    }
    @media (max-width: 390px) {
        #progressModulesLikeHero.progress-hero {
            grid-template-columns: 34px minmax(0, 1fr) !important;
            gap: 8px !important;
            padding: 10px 86px 10px 10px !important;
        }
        #progressModulesLikeHero .progress-hero-icon {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.82rem !important;
        }
        #progressModulesLikeHero .progress-hero-title {
            font-size: 0.86rem !important;
        }
        #progressModulesLikeHero .progress-hero-subtitle {
            font-size: 0.66rem !important;
        }
        #progressModulesLikeHero .progress-hero-art {
            width: 78px !important;
        }
    }
</style>

<div class="db-section active" id="sec-progress-tracking">
    <div class="progress-hero" id="progressModulesLikeHero">
        <div class="progress-hero-inner">
            <div class="progress-hero-copy">
                <div class="progress-hero-icon"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <h4 class="progress-hero-title text-gradient-primary">Philippines Interview Progress</h4>
                    <p class="progress-hero-subtitle">Track readiness growth across your Philippines practice scenarios.</p>
                </div>
            </div>
        </div>
        <svg class="progress-hero-art" viewBox="0 0 220 150" aria-hidden="true" role="img">
            <defs>
                <linearGradient id="progressArtPanel" x1="36" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE"/>
                    <stop offset="1" stop-color="#ECFEFF"/>
                </linearGradient>
                <linearGradient id="progressArtBlue" x1="54" y1="34" x2="168" y2="112" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#3B82F6"/>
                    <stop offset="1" stop-color="#06B6D4"/>
                </linearGradient>
            </defs>
            <rect x="31" y="21" width="158" height="108" rx="18" fill="url(#progressArtPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <path d="M54 105V52" stroke="#93C5FD" stroke-width="5" stroke-linecap="round"/>
            <path d="M54 105h113" stroke="#93C5FD" stroke-width="5" stroke-linecap="round"/>
            <path d="M65 92l25-28 27 16 38-43" fill="none" stroke="url(#progressArtBlue)" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="90" cy="64" r="9" fill="#2563EB" stroke="#EFF6FF" stroke-width="4"/>
            <circle cx="117" cy="80" r="9" fill="#0EA5E9" stroke="#EFF6FF" stroke-width="4"/>
            <circle cx="155" cy="37" r="11" fill="#22C55E" stroke="#EFF6FF" stroke-width="4"/>
            <rect x="67" y="101" width="13" height="16" rx="5" fill="#60A5FA" opacity=".65"/>
            <rect x="93" y="91" width="13" height="26" rx="5" fill="#38BDF8" opacity=".75"/>
            <rect x="119" y="97" width="13" height="20" rx="5" fill="#818CF8" opacity=".65"/>
            <rect x="145" y="75" width="13" height="42" rx="5" fill="#22C55E" opacity=".75"/>
            <path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
            <path d="M194 28l10-10m-6 30l14-2M24 59l-11-7m18 55l-14 3" stroke="#38BDF8" stroke-width="5" stroke-linecap="round" opacity=".55"/>
        </svg>
    </div>
    <div class="progress-actions">
        <!-- Feature 15: Progress Reports -->
        <button class="btn btn-primary btn-shine progress-export-btn pdf" id="exportPdfBtn"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
        <button class="btn btn-success btn-shine progress-export-btn excel" id="exportExcelBtn"><i class="fa-solid fa-file-excel"></i> Export Excel</button>
    </div>

    <!-- Feature 9, 14: Top Stats (Streaks, Comparison) -->
    <div id="progress-stats" class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="premium-panel progress-stat-card" style="--stat-accent:#f59e0b">
                <div class="progress-stat-icon"><i class="fa-solid fa-fire"></i></div>
                <div class="progress-stat-value">{{ $currentStreak }} {{ $currentStreak == 1 ? 'Day' : 'Days' }}</div>
                <div class="progress-stat-label">Current Streak</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.2s;">
            <div class="premium-panel progress-stat-card" style="--stat-accent:#ef4444">
                <div class="progress-stat-icon"><i class="fa-solid fa-fire-flame-curved"></i></div>
                <div class="progress-stat-value">{{ $longestStreak }} {{ $longestStreak == 1 ? 'Day' : 'Days' }}</div>
                <div class="progress-stat-label">Longest Streak</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.3s;">
            <div class="premium-panel progress-stat-card" style="--stat-accent:#16a34a">
                <div class="progress-stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="progress-stat-value">{{ $totalPracticeDays }}</div>
                <div class="progress-stat-label">Total Practice Days</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 animate-fade-up" style="animation-delay: 0.4s;">
            <div class="premium-panel progress-stat-card" style="--stat-accent:#2563eb">
                <div class="progress-stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <div class="progress-stat-value">{{ $readinessMovement?->label ?? 'N/A' }}</div>
                <div class="progress-stat-label">Readiness vs Last</div>
            </div>
        </div>
    </div>

    <!-- Feature 13: AI Progress Insights -->
    @if($readinessMovement)
        <div id="ai-insights" class="alert border-0 mb-4 animate-fade-up progress-ai-insight" style="animation-delay: 0.5s;">
            <div class="progress-ai-content">
                <div class="progress-ai-icon"><i class="fa-solid fa-robot"></i></div>
                <div>
                    <h6 class="progress-ai-title">AI Progress Insights</h6>
                    <p class="progress-ai-text">Your overall readiness score {!! $readinessMovement->trend_html !!} recently. <br>
                    <strong>Recommended Next Step:</strong> Review your latest Philippines interview feedback and rehearse the weakest answer again.</p>
                </div>
                <div class="progress-ai-art"><i class="fa-solid fa-brain"></i></div>
            </div>
        </div>
    @elseif($scoredSessions->count() === 1)
        <div id="ai-insights" class="alert border-0 mb-4 animate-fade-up progress-ai-insight" style="animation-delay: 0.5s;">
            <div class="progress-ai-content">
                <div class="progress-ai-icon"><i class="fa-solid fa-robot"></i></div>
                <div>
                    <h6 class="progress-ai-title">AI Progress Insights</h6>
                    <p class="progress-ai-text">Complete one more scored Philippines practice interview to compare readiness movement accurately.</p>
                </div>
                <div class="progress-ai-art"><i class="fa-solid fa-brain"></i></div>
            </div>
        </div>
    @else
        <div id="ai-insights" class="alert border-0 mb-4 animate-fade-up progress-ai-insight" style="animation-delay: 0.5s;">
            <div class="progress-ai-content">
                <div class="progress-ai-icon"><i class="fa-solid fa-robot"></i></div>
                <div>
                    <h6 class="progress-ai-title">AI Progress Insights</h6>
                    <p class="progress-ai-text">Complete at least 2 Philippines practice interviews to generate personalized progress insights.</p>
                </div>
                <div class="progress-ai-art"><i class="fa-solid fa-brain"></i></div>
            </div>
        </div>
    @endif

    <div class="row mb-4 animate-fade-up" id="personalized-practice-plan" style="animation-delay: 0.55s;">
        <div class="col-12">
            <div class="premium-panel practice-plan-panel">
                <div class="practice-plan-heading">
                    <div class="practice-plan-heading-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div>
                        <h5 class="practice-plan-heading-title">Personalized Practice Plan</h5>
                        <p class="practice-plan-heading-text">Daily actions based on your latest scores, voice rehearsal, and module progress.</p>
                        <span class="practice-plan-pill mt-2"><i class="fa-solid fa-clock"></i> {{ isset($practicePlan) ? $practicePlan->sum('minutes') : 0 }} min total</span>
                    </div>
                </div>
                @if(isset($practicePlan) && $practicePlan->count() > 0)
                    <div class="practice-plan-list">
                        @foreach($practicePlan as $item)
                            <a href="{{ $item->url }}" class="practice-plan-row" style="--plan-color: {{ $item->color }};">
                                <div class="practice-plan-icon"><i class="fa-solid {{ $item->icon }}"></i></div>
                                <div class="practice-plan-copy">
                                    <div class="practice-plan-top">
                                        <span class="practice-plan-step">{{ $item->day }}</span>
                                        <span class="practice-plan-title">{{ $item->title }}</span>
                                    </div>
                                    <div class="practice-plan-text">{{ $item->action }}</div>
                                    <div class="practice-plan-text mt-1">{{ $item->reason }}</div>
                                    <ul class="practice-plan-tasks">
                                        @foreach(array_slice((array) ($item->tasks ?? []), 0, 2) as $task)
                                            <li><i class="fa-solid fa-check" style="color:#10b981;margin-top:2px;"></i><span>{{ $task }}</span></li>
                                        @endforeach
                                    </ul>
                                    <div class="practice-plan-footer">
                                        <span class="practice-plan-pill"><i class="fa-regular fa-clock"></i> {{ $item->minutes }} min</span>
                                        <span class="practice-plan-link">{{ $item->cta }} <i class="fa-solid fa-arrow-right ms-1"></i></span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4" style="color:var(--tx3);">
                        <i class="fa-solid fa-calendar-check fs-2 mb-3" style="color:var(--bd);"></i>
                        <p>Complete a Philippines practice interview or voice rehearsal to generate your plan.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 1: Readiness Score Trend -->
        <div class="col-12 animate-fade-up" id="readiness-trend" style="animation-delay: 0.6s;">
            <div class="premium-panel progress-chart-panel" style="height:100%; --panel-accent:#2563eb;">
                <div class="progress-panel-heading">
                    <div class="progress-panel-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <div>
                        <h5 class="progress-panel-title">Overall Readiness Trend</h5>
                        <p class="progress-panel-subtitle">Track your overall interview readiness over time.</p>
                    </div>
                </div>
                <div class="progress-chart-frame">
                    <canvas id="readinessChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Feature 3: Scenario Performance Analysis -->
        <div class="col-12 animate-fade-up" id="category-perf" style="animation-delay: 0.7s;">
            <div class="premium-panel progress-chart-panel" style="height:100%; --panel-accent:#10b981;">
                <div class="progress-panel-heading">
                    <div class="progress-panel-icon"><i class="fa-solid fa-crosshairs"></i></div>
                    <div>
                        <h5 class="progress-panel-title">Scenario Performance</h5>
                        <p class="progress-panel-subtitle">Your average scores across different interview scenarios.</p>
                    </div>
                </div>
                <div class="progress-chart-frame scenario">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 4: Skill Improvement Tracker -->
        <div class="col-12 animate-fade-up" id="skill-tracker" style="animation-delay: 0.8s;">
            <div class="premium-panel progress-chart-panel" style="height:100%; --panel-accent:#8b5cf6;">
                <div class="progress-panel-heading">
                    <div class="progress-panel-icon"><i class="fa-solid fa-chart-simple"></i></div>
                    <div>
                        <h5 class="progress-panel-title">Skill Improvement Tracker</h5>
                        <p class="progress-panel-subtitle">Track your progress in key interview skills.</p>
                    </div>
                </div>
                
                @if(count($skillComparison) > 0)
                @foreach($skillComparison as $metric)
                <div class="skill-metric-row">
                    <div class="skill-metric-top">
                        <span class="skill-metric-label">{{ $metric['label'] }}</span>
                        <span class="skill-metric-value">{{ $metric['previous'] }}% <i class="fa-solid fa-arrow-right mx-1" style="font-size:0.8em"></i> {{ $metric['current'] }}%
                        @if($metric['delta'] >= 0)
                            <span class="text-success ms-1">(+{{ $metric['delta'] }}%)</span>
                        @else
                            <span class="text-danger ms-1">({{ $metric['delta'] }}%)</span>
                        @endif
                        </span>
                    </div>
                    <div class="skill-metric-bar">
                        <div class="skill-metric-fill" role="progressbar" style="width: {{ $metric['bar'] }}%;"></div>
                    </div>
                </div>
                @endforeach
                @else
                    <div class="skill-empty-state">
                        <div>
                            <div class="skill-empty-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                            <p class="skill-empty-text">Complete multiple Philippine practice interviews to track your specific skill improvements.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Feature 12: Strengths & Areas for Improvement -->
        <div class="col-12 animate-fade-up" id="strengths-tracker" style="animation-delay: 0.9s;">
            <div class="premium-panel strengths-star-panel" style="height:100%; --panel-accent:#7c3aed;">
                @php
                    $strengths = $latestSkillSummary->strengths ?: ['None identified yet'];
                    $weaknesses = $latestSkillSummary->weaknesses ?: ['None identified yet'];
                @endphp
                <div class="strengths-overview">
                    <div class="strengths-icon"><i class="fa-solid fa-star"></i></div>
                    <div>
                        <h5 class="strengths-title">Strengths & Areas for Improvement</h5>
                        @if($latestSkillSummary->has_data)
                            <div class="strengths-lists">
                                <div class="strengths-list-card">
                                    <h6 class="text-success"><i class="fa-solid fa-arrow-trend-up me-2"></i>Strengths</h6>
                                    <ul>
                                        @foreach(array_slice($strengths, 0, 3) as $str)
                                            <li><i class="fa-solid fa-check text-success me-2"></i>{{ $str }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="strengths-list-card">
                                    <h6 class="text-warning"><i class="fa-solid fa-arrow-trend-down me-2"></i>Needs Work</h6>
                                    <ul>
                                        @foreach(array_slice($weaknesses, 0, 3) as $wk)
                                            <li><i class="fa-solid fa-xmark text-warning me-2"></i>{{ $wk }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @else
                            <p class="strengths-text">Complete an interview to see strengths and areas for improvement.</p>
                        @endif
                    </div>
                </div>

                <!-- Feature 7: STAR Method Progress -->
                <div class="star-overview">
                    <div class="star-icon"><i class="fa-solid fa-bullseye"></i></div>
                    <div>
                        <h5 class="star-title">STAR Method Progress</h5>
                        <p class="star-text">Insufficient data to analyze your STAR Method usage. Keep practicing behavioral questions!</p>
                    </div>
                </div>
                <div class="star-note">
                    <div class="star-note-icon"><i class="fa-regular fa-lightbulb"></i></div>
                    <div>
                        <h6 class="star-note-title">What is STAR Method?</h6>
                        <p class="star-note-text">STAR stands for Situation, Task, Action, Result. It helps you structure strong and impactful answers.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2: Interview Performance History -->
    <div class="row mb-4 animate-fade-up" id="history-table" style="animation-delay: 1s;">
        <div class="col-12">
            <div class="premium-panel history-panel" style="--panel-accent:#4f46e5;">
                <div class="history-top">
                    <div class="history-heading">
                        <div class="history-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <h5 class="history-title">Interview Performance History</h5>
                    </div>
                    <div class="history-actions">
                        @if($sessions->count() > 0)
                            <form action="{{ route('user.sessions.clear') }}" method="POST" onsubmit="return confirm('Clear all completed interview sessions? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger history-clear-btn">
                                    <i class="fa-solid fa-trash-can me-1"></i> Clear All
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <label class="history-search" for="historySearch">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="historySearch" placeholder="Search history...">
                </label>
                <div class="history-list">
                    @foreach($sessions as $session)
                        @php $sc = $session->score ? $session->score->overall_readiness_score : null; @endphp
                        <article class="history-card" data-history-record>
                            <div class="history-date"><i class="fa-regular fa-calendar-days"></i>{{ $session->created_at->format('M d, Y') }}</div>
                            <h6 class="history-scenario">{{ $session->practice_scenario ?? 'General Job Interview' }}</h6>
                            <div class="history-meta">
                                <div>Score:
                                    @if($session->score)
                                        <span class="history-score-value">{{ $session->score->overall_readiness_score }}%</span>
                                    @else
                                        <span class="history-rating-badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Score pending</span>
                                    @endif
                                </div>
                                <div>Rating:
                                    @if($sc === null) <span class="history-rating-badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Not scored</span>
                                    @elseif($sc >= 90) <span class="history-rating-badge" style="background: rgba(16, 185, 129, 0.16); color: #059669;">Excellent</span>
                                    @elseif($sc >= 70) <span class="history-rating-badge" style="background: rgba(59, 130, 246, 0.16); color: #2563eb;">Good</span>
                                    @elseif($sc >= 50) <span class="history-rating-badge" style="background: rgba(245, 158, 11, 0.18); color: #d97706;">Average</span>
                                    @else <span class="history-rating-badge" style="background: rgba(239, 68, 68, 0.16); color: #ef334e;">Needs Work</span>
                                    @endif
                                </div>
                            </div>
                            <div class="history-card-actions">
                                <a href="{{ route('user.review', $session->id) }}" class="btn btn-outline-primary history-feedback-btn"><i class="fa-regular fa-message"></i> View Feedback</a>
                                <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger history-delete-btn" title="Delete session">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                    @if($sessions->count() == 0)
                        <div class="skill-empty-state">
                            <p class="skill-empty-text">No interview records found. Start a Philippines practice interview to track your progress.</p>
                        </div>
                    @endif
                </div>
                <div class="table-responsive d-none">
                    <table class="table custom-table align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--bd); color: var(--tx3);">
                                <th class="border-0">Date</th>
                                <th class="border-0">Practice Scenario</th>
                                <th class="border-0">Score</th>
                                <th class="border-0">Rating</th>
                                <th class="border-0 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sessions as $session)
                            <tr style="border-bottom: 1px solid var(--bd);">
                                <td class="border-0 py-3">{{ $session->created_at->format('M d, Y') }}</td>
                                <td class="border-0 py-3 fw-bold">{{ $session->practice_scenario ?? 'General Job Interview' }}</td>
                                <td class="border-0 py-3">
                                    @if($session->score)
                                        {{ $session->score->overall_readiness_score }}%
                                    @else
                                        <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Score pending</span>
                                    @endif
                                </td>
                                <td class="border-0 py-3">
                                    @php $sc = $session->score ? $session->score->overall_readiness_score : null; @endphp
                                    @if($sc === null) <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: var(--tx3);">Not scored</span>
                                    @elseif($sc >= 90) <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">Excellent</span>
                                    @elseif($sc >= 70) <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">Good</span>
                                    @elseif($sc >= 50) <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">Average</span>
                                    @else <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">Needs Work</span>
                                    @endif
                                </td>
                                <td class="border-0 py-3 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">View Feedback</a>
                                        <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete session" style="border-radius:8px;">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @if($sessions->count() == 0)
                            <tr>
                                <td colspan="5" class="text-center py-4" style="color:var(--tx3);font-style:italic;">No interview records found. Start a Philippines practice interview to track your progress.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 5: Learning Progress Tracking -->
        <div class="col-12" id="learning-progress">
            <div class="learning-panel" style="--panel-accent:#6d5dfc;">
                <div class="learning-heading">
                    <div class="learning-heading-icon"><i class="fa-solid fa-book-open"></i></div>
                    <div>
                        <h5 class="learning-title">Learning Progress</h5>
                        <p class="learning-subtitle">Track your module progress and keep learning.</p>
                    </div>
                </div>
                <div class="learning-list">
                @forelse($learningProgress as $lp)
                @php
                    $moduleColor = ($lp->progress_percentage >= 100) ? '#10b981' : '#6d5dfc';
                    $moduleIcon = ($lp->progress_percentage >= 100) ? 'fa-bullhorn' : 'fa-book-open';
                @endphp
                <div class="learning-module" style="--module-color: {{ $moduleColor }};">
                    <div class="learning-module-icon"><i class="fa-solid {{ $moduleIcon }}"></i></div>
                    <div>
                        <div class="learning-module-top">
                            <div class="learning-module-title">{{ $lp->learningModule ? $lp->learningModule->title : 'Module' }}</div>
                            <div class="learning-percent">{{ $lp->progress_percentage }}%</div>
                        </div>
                        <div class="learning-track">
                            <div class="learning-fill" style="--learning-progress: {{ max(0, min(100, (int) $lp->progress_percentage)) }}%;"></div>
                        </div>
                    </div>
                </div>
                @empty
                    <div class="skill-empty-state">
                        <div>
                            <div class="skill-empty-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                            <p class="skill-empty-text">No learning progress recorded yet.</p>
                        </div>
                    </div>
                @endforelse
                </div>
                
                @if($learningProgress->count() > 0)
                <div class="learning-summary">
                    <div class="learning-summary-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div>
                        <div class="learning-summary-value">{{ round($learningProgress->avg('progress_percentage') ?? 0) }}%</div>
                        <div class="learning-summary-label">Overall Learning Completion Rate</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if(isset($moduleRecommendations) && $moduleRecommendations->count() > 0)
        <div class="col-12" id="recommended-next">
            <div class="recommend-panel" style="--panel-accent:#f59e0b;">
                <div class="recommend-heading">
                    <div class="recommend-heading-icon"><i class="fa-solid fa-lightbulb"></i></div>
                    <div>
                        <h5 class="recommend-title">Recommended Next</h5>
                        <p class="recommend-subtitle">Based on your performance and progress.</p>
                    </div>
                </div>
                <div class="recommend-list">
                @foreach($moduleRecommendations as $recommendation)
                    <a href="{{ $recommendation->url }}" class="recommend-item" style="--panel-accent: {{ $recommendation->color }};">
                        <div class="recommend-item-icon"><i class="fa-solid {{ $recommendation->icon }}"></i></div>
                        <div>
                            <div class="recommend-item-title">{{ $recommendation->module->title }}</div>
                            <div class="recommend-item-text">{{ $recommendation->reason }}</div>
                        </div>
                        <div class="recommend-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                    </a>
                @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Feature 6: Voice Rehearsal Progress -->
        <div class="col-12" id="voice-progress">
            <div class="voice-panel" style="--panel-accent:#ec407a;">
                <div class="voice-heading">
                    <div class="voice-heading-icon"><i class="fa-solid fa-microphone"></i></div>
                    <div>
                        <h5 class="voice-title">Voice Rehearsal Progress</h5>
                        <p class="voice-subtitle">Improvements start with practice.</p>
                    </div>
                </div>
                @if($voiceSummary->latest)
                    @php $latestVoice = $voiceSummary->latest; $prevVoice = $voiceSummary->previous; @endphp
                    <div class="row text-center mb-4">
                        <div class="col-4 border-end" style="border-color:var(--bd) !important;">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ $latestVoice->speaking_pace ?? $latestVoice->wpm ?? 'N/A' }}</h3>
                            <small style="color:var(--tx3)">Pace (wpm)</small>
                        </div>
                        <div class="col-4 border-end" style="border-color:var(--bd) !important;">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ is_numeric($latestVoice->clarity_score) ? $latestVoice->clarity_score . '%' : 'N/A' }}</h3>
                            <small style="color:var(--tx3)">Clarity</small>
                        </div>
                        <div class="col-4">
                            <h3 style="color:var(--tx);font-weight:bold;">{{ is_numeric($latestVoice->confidence_score) ? $latestVoice->confidence_score . '%' : 'N/A' }}</h3>
                            <small style="color:var(--tx3)">Delivery Stability</small>
                        </div>
                    </div>
                    
                    <div class="p-3" style="background: rgba(16, 185, 129, 0.1); border-radius: 12px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-success mb-1 fw-bold">Filler Word Change</h6>
                                @if($prevVoice)
                                    <small style="color:var(--tx)">Previous: <strong>{{ $prevVoice->filler_words ?? 0 }}</strong> | Current: <strong>{{ $latestVoice->filler_words ?? 0 }}</strong></small>
                                @else
                                    <small style="color:var(--tx)">Complete another voice rehearsal to compare filler word movement.</small>
                                @endif
                            </div>
                            <h2 class="text-success mb-0 fw-bold">{{ $voiceSummary->filler_reduction === null ? 'N/A' : (($voiceSummary->filler_reduction > 0 ? '+' : '') . $voiceSummary->filler_reduction . '%') }}</h2>
                        </div>
                    </div>
                @else
                    <div class="voice-empty">
                        <div>
                            <div class="voice-empty-icon"><i class="fa-solid fa-microphone"></i></div>
                            <h6 class="voice-empty-title">No voice rehearsal data available yet.</h6>
                            <p class="voice-empty-text">Start a voice rehearsal to track your clarity, pace, and delivery over time.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 8: Practice Activity Calendar -->
    <div class="row mb-4" id="activity-calendar">
        <div class="col-12">
            <div class="activity-panel" style="--panel-accent:#6d5dfc;">
                <div class="activity-heading">
                    <div class="activity-heading-icon"><i class="fa-regular fa-calendar"></i></div>
                    <div>
                        <h5 class="activity-title">Practice Activity Calendar</h5>
                        <p class="activity-subtitle">Track your daily practice and stay consistent.</p>
                    </div>
                </div>
                <div class="activity-empty">
                    <svg class="activity-illustration" viewBox="0 0 520 260" aria-hidden="true" role="img">
                        <defs>
                            <linearGradient id="activityCalTop" x1="120" y1="50" x2="400" y2="202" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#8B5CF6"/>
                                <stop offset="1" stop-color="#C4B5FD"/>
                            </linearGradient>
                        </defs>
                        <ellipse cx="260" cy="218" rx="210" ry="18" fill="#ede9fe"/>
                        <circle cx="260" cy="130" r="118" fill="#ede9fe" opacity=".75"/>
                        <path d="M116 180c34-18 52-49 43-91 34 39 37 72 6 101" fill="#c4b5fd" opacity=".7"/>
                        <path d="M404 190c-22-42-10-75 34-103 8 48-4 82-34 103z" fill="#c4b5fd" opacity=".7"/>
                        <rect x="162" y="72" width="196" height="148" rx="18" fill="#fff" stroke="#ddd6fe" stroke-width="3"/>
                        <path d="M162 96c0-13 11-24 24-24h148c13 0 24 11 24 24v26H162V96z" fill="url(#activityCalTop)"/>
                        <path d="M198 58v34M260 58v34M322 58v34" stroke="#37306b" stroke-width="13" stroke-linecap="round"/>
                        @for($row = 0; $row < 3; $row++)
                            @for($col = 0; $col < 6; $col++)
                                <rect x="{{ 194 + ($col * 28) }}" y="{{ 144 + ($row * 31) }}" width="22" height="22" rx="5" fill="#ede9fe" opacity=".75"/>
                            @endfor
                        @endfor
                        <rect x="278" y="172" width="30" height="30" rx="7" fill="#6d5dfc"/>
                        <path d="M286 187l6 6 11-14" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="118" cy="91" r="6" fill="#c4b5fd"/>
                        <circle cx="76" cy="158" r="8" fill="#c4b5fd"/>
                        <circle cx="432" cy="132" r="7" fill="#c4b5fd"/>
                        <path d="M380 86l8 16 16 8-16 8-8 16-8-16-16-8 16-8 8-16z" fill="#a78bfa"/>
                    </svg>
                    <h6 class="activity-empty-title">Complete your first Philippines practice interview</h6>
                    <p class="activity-empty-text">to start tracking your daily practice activity.</p>
                    <a href="{{ route('interview.setup') }}" class="btn btn-outline-primary activity-cta"><i class="fa-regular fa-calendar-days"></i> Go to Calendar</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Feature 10: Goals & Milestones -->
        <div class="col-12" id="goals-milestones">
            <div class="goals-panel" style="--panel-accent:#10b981;">
                <div class="goals-heading">
                    <div class="goals-heading-icon"><i class="fa-solid fa-bullseye"></i></div>
                    <div>
                        <h5 class="goals-title">Goals & Milestones</h5>
                        <p class="goals-subtitle">Track your progress and reach your interview goals.</p>
                    </div>
                </div>
                @forelse($goals as $goal)
                <div class="goal-row">
                    <div class="goal-top">
                        <span class="goal-title">{{ $goal->title }}</span>
                        <span class="goal-percent">{{ $goal->progress }}%</span>
                    </div>
                    <div class="goal-track">
                        <div class="goal-fill" style="--goal-progress: {{ max(0, min(100, (int) $goal->progress)) }}%;"></div>
                    </div>
                </div>
                @empty
                    <div class="goal-row">
                        <div class="goal-top">
                            <span class="goal-title">Complete your first scored interview</span>
                            <span class="goal-percent">0%</span>
                        </div>
                        <div class="goal-track">
                            <div class="goal-fill" style="--goal-progress: 0%;"></div>
                        </div>
                    </div>
                @endforelse
                <div class="goal-note">
                    <div class="goal-note-icon"><i class="fa-solid fa-star"></i></div>
                    <div>
                        <div class="goal-note-title">You're just getting started!</div>
                        <div class="goal-note-text">Keep practicing to hit your first milestone.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature 11: Achievements & Badges -->
        <div class="col-12" id="achievements-badges">
            <div class="badges-panel" style="--panel-accent:#f59e0b;">
                <div class="badges-heading">
                    <div class="badges-heading-icon"><i class="fa-solid fa-trophy"></i></div>
                    <div>
                        <h5 class="badges-title">Achievements & Badges</h5>
                        <p class="badges-subtitle">Celebrate your progress and stay motivated.</p>
                    </div>
                </div>
                <div class="badge-grid">
                    @forelse($badges as $badge)
                    <div class="badge-item {{ $badge->unlocked ? '' : 'locked' }}">
                        <div class="badge-medal">
                            <i class="fa-solid {{ $badge->icon }}"></i>
                        </div>
                        <div class="badge-title">{{ $badge->title }}</div>
                        <div class="badge-desc">{{ $badge->description ?? ($badge->unlocked ? 'Unlocked' : 'Keep practicing') }}</div>
                    </div>
                    @empty
                    <div class="badge-item">
                        <div class="badge-medal"><i class="fa-solid fa-medal"></i></div>
                        <div class="badge-title">First Interview</div>
                        <div class="badge-desc">Complete 1 interview</div>
                    </div>
                    <div class="badge-item">
                        <div class="badge-medal"><i class="fa-solid fa-fire"></i></div>
                        <div class="badge-title">3-Day Streak</div>
                        <div class="badge-desc">Practice 3 days in a row</div>
                    </div>
                    <div class="badge-item">
                        <div class="badge-medal"><i class="fa-solid fa-star"></i></div>
                        <div class="badge-title">STAR Master</div>
                        <div class="badge-desc">Use STAR effectively</div>
                    </div>
                    <div class="badge-item locked">
                        <div class="badge-medal"><i class="fa-solid fa-bullhorn"></i></div>
                        <div class="badge-title">Top Comm</div>
                        <div class="badge-desc">Top communicator</div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            // If bootstrap is available
            if(typeof bootstrap !== 'undefined') {
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                });
            }

            const trendData = @json($scoreTrend);
            const scenarioPerformance = @json($categoryPerf);
            const progressCharts = [];
            const previousChartColorUpdater = window.updateChartColors;
            const progressThemeColors = () => {
                const isLight = document.documentElement.dataset.theme === 'light' || document.documentElement.classList.contains('lm');
                return {
                    tick: isLight ? '#1e2f50' : '#cbd5e1',
                    grid: isLight ? 'rgba(148, 163, 184, 0.24)' : 'rgba(148, 163, 184, 0.18)',
                    border: isLight ? 'rgba(148, 163, 184, 0.35)' : 'rgba(148, 163, 184, 0.22)'
                };
            };
            const applyProgressChartTheme = (chart) => {
                if (!chart) return;
                const colors = progressThemeColors();
                if (chart.options.scales?.y) {
                    chart.options.scales.y.ticks.color = colors.tick;
                    chart.options.scales.y.grid.color = colors.grid;
                }
                if (chart.options.scales?.x) {
                    chart.options.scales.x.ticks.color = colors.tick;
                    chart.options.scales.x.border.color = colors.border;
                }
                chart.update('none');
            };
            
            // Feature 1: Readiness Trend
            const labels = trendData.map(s => s.date);
            const scores = trendData.map(s => s.score);
            
            if(window.Chart && document.getElementById('readinessChart')) {
                const readinessCanvas = document.getElementById('readinessChart');
                const readinessGradient = readinessCanvas.getContext('2d').createLinearGradient(0, 0, 0, 340);
                readinessGradient.addColorStop(0, 'rgba(37, 99, 235, 0.18)');
                readinessGradient.addColorStop(1, 'rgba(37, 99, 235, 0.02)');

                const readinessChart = new Chart(readinessCanvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Readiness Score',
                            data: scores,
                            borderColor: '#2563eb',
                            backgroundColor: readinessGradient,
                            borderWidth: 3,
                            tension: 0.34,
                            fill: true,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        elements: { line: { capBezierPoints: true } },
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                max: 100,
                                ticks: { color: progressThemeColors().tick, stepSize: 10, padding: 12 },
                                border: { display: false },
                                grid: { color: progressThemeColors().grid, borderDash: [4, 5], drawTicks: false }
                            },
                            x: {
                                ticks: { color: progressThemeColors().tick, maxRotation: 0, autoSkipPadding: 16 },
                                border: { color: progressThemeColors().border },
                                grid: { display: false }
                            }
                        }
                    }
                });
                progressCharts.push(readinessChart);
            }

            // Feature 3: Scenario Performance
            if(window.Chart && document.getElementById('categoryChart')) {
                const scenarioLabels = Object.keys(scenarioPerformance);
                const scenarioData = Object.values(scenarioPerformance);

                const categoryChart = new Chart(document.getElementById('categoryChart'), {
                    type: 'bar',
                    data: {
                        labels: scenarioLabels,
                        datasets: [{
                            label: 'Avg Score',
                            data: scenarioData,
                            backgroundColor: [
                                '#3b82f6',
                                '#10b981',
                                '#8b5cf6',
                                '#fb923c'
                            ],
                            borderRadius: 4,
                            maxBarThickness: 96
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { 
                            y: { 
                                beginAtZero: true, 
                                max: 100,
                                ticks: { color: progressThemeColors().tick, stepSize: 10, padding: 12 },
                                border: { display: false },
                                grid: { color: progressThemeColors().grid, borderDash: [4, 5], drawTicks: false }
                            },
                            x: {
                                ticks: { color: progressThemeColors().tick, maxRotation: 0, font: { weight: 500 } },
                                border: { color: progressThemeColors().border },
                                grid: { display: false }
                            }
                        }
                    }
                });
                progressCharts.push(categoryChart);
            }
            window.updateChartColors = function() {
                if (typeof previousChartColorUpdater === 'function') {
                    previousChartColorUpdater();
                }
                progressCharts.forEach(applyProgressChartTheme);
            };

            // Feature 2: Table Search Filter
            const searchInput = document.getElementById('historySearch');
            if(searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const filter = searchInput.value.toLowerCase();
                    const rows = document.querySelectorAll('table tbody tr');
                    const cards = document.querySelectorAll('[data-history-record]');
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if(text.includes(filter)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    cards.forEach(card => {
                        const text = card.textContent.toLowerCase();
                        card.style.display = text.includes(filter) ? '' : 'none';
                    });
                });
            }

            // Export PDF
            const exportPdfBtn = document.getElementById('exportPdfBtn');
            if (exportPdfBtn) {
                exportPdfBtn.addEventListener('click', function() {
                    const element = document.querySelector('.db-section');
                    if (!element || typeof window.html2pdf !== 'function') {
                        alert('PDF export is not available right now. Please use your browser print option instead.');
                        return;
                    }
                    const opt = {
                        margin:       [0.5, 0.5, 0.5, 0.5],
                        filename:     'progress_report.pdf',
                        image:        { type: 'jpeg', quality: 0.98 },
                        html2canvas:  { scale: 2, useCORS: true },
                        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
                    };
                    
                    // Hide buttons during export
                    const buttons = element.querySelectorAll('button');
                    const originalDisplays = [];
                    buttons.forEach(btn => {
                        originalDisplays.push(btn.style.display);
                        btn.style.display = 'none';
                    });

                    // Hide inputs like search during export
                    const inputs = element.querySelectorAll('input');
                    const originalInputDisplays = [];
                    inputs.forEach(input => {
                        originalInputDisplays.push(input.style.display);
                        input.style.display = 'none';
                    });
                    
                    html2pdf().set(opt).from(element).save().catch(() => {
                        alert('PDF export failed. Please try again or use your browser print option.');
                    }).finally(() => {
                        buttons.forEach((btn, index) => {
                            btn.style.display = originalDisplays[index];
                        });
                        inputs.forEach((input, index) => {
                            input.style.display = originalInputDisplays[index];
                        });
                    });
                });
            }

            // Export Excel
            const exportExcelBtn = document.getElementById('exportExcelBtn');
            if (exportExcelBtn) {
                exportExcelBtn.addEventListener('click', function() {
                    if (!window.XLSX) {
                        alert('Excel export is not available right now.');
                        return;
                    }
                    const table = document.querySelector('#history-table table');
                    if (table) {
                        const clonedTable = table.cloneNode(true);
                        const ths = clonedTable.querySelectorAll('th');
                        if (ths.length > 0) ths[ths.length - 1].remove();
                        const trs = clonedTable.querySelectorAll('tbody tr');
                        trs.forEach(tr => {
                            const tds = tr.querySelectorAll('td');
                            if (tds.length > 0) tds[tds.length - 1].remove();
                        });

                        const wb = XLSX.utils.table_to_book(clonedTable, {sheet: "History"});
                        XLSX.writeFile(wb, 'interview_history.xlsx');
                    } else {
                        alert("No history table found to export.");
                    }
                });
            }
        });
    </script>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#progress-stats', popover: { title: 'At A Glance', description: 'Review streak, practice days, and readiness movement without opening a report.', side: 'bottom', align: 'start' }},
            { element: '#ai-insights', popover: { title: 'AI Insights', description: 'Use trend-based coaching notes to decide what to practice next.', side: 'bottom', align: 'start' }},
            { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Track how your overall readiness score changes over time.', side: 'bottom', align: 'start' }},
            { element: '#category-perf', popover: { title: 'Scenario Breakdown', description: 'Compare Philippines practice scenarios to find strengths and weak spots.', side: 'top', align: 'start' }},
            { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Watch the core interview skills that are improving across sessions.', side: 'top', align: 'start' }},
            { element: '#strengths-tracker', popover: { title: 'Strengths & STAR', description: 'Review strengths, areas to improve, and STAR method progress.', side: 'top', align: 'start' }},
            { element: '#history-table', popover: { title: 'Session History', description: 'Open previous interviews and detailed AI feedback from one place.', side: 'top', align: 'start' }},
            { element: '#learning-progress', popover: { title: 'Learning Progress', description: 'See how much of your module work is complete.', side: 'top', align: 'start' }},
            { element: '#voice-progress', popover: { title: 'Voice Rehearsal', description: 'Check speaking pace, clarity, and delivery stability from voice drills.', side: 'top', align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Activity Calendar', description: 'Use the calendar to spot consistent practice days and gaps.', side: 'top', align: 'start' }},
            { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track progress toward platform goals and target outcomes.', side: 'top', align: 'start' }},
            { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards appear here as your practice history grows.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#progress-stats', popover: { title: 'At A Glance', description: 'Review streak, practice days, and readiness movement without opening a report.', side: 'bottom', align: 'start' }},
            { element: '#ai-insights', popover: { title: 'AI Insights', description: 'Use trend-based coaching notes to decide what to practice next.', side: 'bottom', align: 'start' }},
            { element: '#readiness-trend', popover: { title: 'Readiness Trend', description: 'Track how your overall readiness score changes over time.', side: 'bottom', align: 'start' }},
            { element: '#category-perf', popover: { title: 'Scenario Breakdown', description: 'Compare Philippines practice scenarios to find strengths and weak spots.', side: 'bottom', align: 'start' }},
            { element: '#skill-tracker', popover: { title: 'Skill Improvement', description: 'Watch the core interview skills that are improving across sessions.', side: 'right', align: 'start' }},
            { element: '#strengths-tracker', popover: { title: 'Strengths & STAR', description: 'Review strengths, areas to improve, and STAR method progress.', side: 'left', align: 'start' }},
            { element: '#history-table', popover: { title: 'Session History', description: 'Open previous interviews and detailed AI feedback from one place.', side: 'top', align: 'start' }},
            { element: '#learning-progress', popover: { title: 'Learning Progress', description: 'See how much of your module work is complete.', side: 'right', align: 'start' }},
            { element: '#voice-progress', popover: { title: 'Voice Rehearsal', description: 'Check speaking pace, clarity, and delivery stability from voice drills.', side: 'left', align: 'start' }},
            { element: '#activity-calendar', popover: { title: 'Activity Calendar', description: 'Use the calendar to spot consistent practice days and gaps.', side: 'top', align: 'start' }},
            { element: '#goals-milestones', popover: { title: 'Goals & Milestones', description: 'Track progress toward platform goals and target outcomes.', side: 'right', align: 'start' }},
            { element: '#achievements-badges', popover: { title: 'Achievements', description: 'Badges and awards appear here as your practice history grows.', side: 'left', align: 'start' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_progress',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
