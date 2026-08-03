@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Real-Life Mission Mode')

@section('content')
<style>
    .mission-page {
        display: flex;
        flex-direction: column;
        gap: 14px;
        max-width: none;
        margin: 0;
        padding: 0 0 24px;
    }
    .text-gradient-primary {
        background: linear-gradient(135deg, #2563eb 0%, #0891b2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    #mission-mode-page .mission-progress-hero.sr-page-hero {
        --mission-hero-title-color: #1d4ed8;
        --mission-hero-text-color: #334155;
        --mission-hero-icon-bg: rgba(239, 246, 255, 0.92);
        --mission-hero-icon-border: rgba(147, 197, 253, 0.42);
        display: grid !important;
        grid-template-columns: 44px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 10px !important;
        min-height: 104px !important;
        margin-bottom: 14px !important;
        padding: 14px 126px 14px 14px !important;
        border-radius: 16px !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
        border-color: rgba(191, 219, 254, 0.86) !important;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08) !important;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-inner,
    #mission-mode-page .mission-progress-hero .sr-page-hero-copy {
        display: contents !important;
    }
    #mission-mode-page .mission-hero-icon {
        box-sizing: border-box;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        border: 1px solid var(--mission-hero-icon-border) !important;
        border-radius: 10px !important;
        background: var(--mission-hero-icon-bg) !important;
        color: var(--mission-hero-title-color) !important;
        font-size: 0.9rem !important;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-title {
        display: block !important;
        margin: 0 0 4px !important;
        color: var(--mission-hero-title-color) !important;
        background: none !important;
        -webkit-text-fill-color: var(--mission-hero-title-color) !important;
        font-size: 1.02rem !important;
        line-height: 1.08 !important;
        font-weight: 950 !important;
        text-transform: uppercase;
        overflow-wrap: normal;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-title svg {
        display: none;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
        max-width: 27rem !important;
        margin: 0 !important;
        color: var(--mission-hero-text-color) !important;
        font-size: 0.78rem !important;
        line-height: 1.32 !important;
        font-weight: 500;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-art {
        right: 8px !important;
        bottom: 4px !important;
        width: 94px !important;
        opacity: 0.92;
    }
    :root:not(.lm) #mission-mode-page .mission-progress-hero.sr-page-hero {
        --mission-hero-title-color: #93c5fd;
        --mission-hero-text-color: #e2e8f0;
        --mission-hero-icon-bg: rgba(59, 130, 246, 0.2);
        --mission-hero-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28) !important;
    }
    .mission-shell {
        display: grid;
        grid-template-columns: minmax(0, 0.98fr) minmax(300px, 390px);
        gap: 14px;
        align-items: start;
    }
    .mission-panel {
        background:
            linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(246, 250, 255, 0.9)),
            var(--sf);
        border: 1px solid rgba(191, 219, 254, 0.72);
        border-radius: 18px;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
        padding: 16px;
        min-width: 0;
    }
    .mission-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }
    .mission-title {
        color: var(--tx);
        font-size: 1.08rem;
        font-weight: 900;
        margin: 0;
        letter-spacing: 0;
    }
    .mission-kicker {
        color: var(--tx3);
        font-size: 0.8rem;
        line-height: 1.45;
        margin-top: 4px;
    }
    .mission-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 11px;
        color: var(--pill-color, #60a5fa);
        background: linear-gradient(135deg, color-mix(in srgb, var(--pill-color, #60a5fa) 14%, white), rgba(255, 255, 255, 0.72));
        border: 1px solid color-mix(in srgb, var(--pill-color, #60a5fa) 24%, transparent);
        font-size: 0.74rem;
        font-weight: 850;
        white-space: nowrap;
    }
    .mission-board-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .mission-board-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        background: linear-gradient(145deg, #edf4ff, #f8fbff);
        border: 1px solid #dbeafe;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.72), 0 8px 18px rgba(37, 99, 235, 0.08);
        flex: 0 0 auto;
    }
    .mission-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }
    .mission-generator {
        display: grid;
        grid-template-columns: 1fr;
        gap: 9px;
        margin-bottom: 12px;
    }
    .mission-generator .mission-btn {
        justify-self: center;
        width: min(190px, 72%);
        min-height: 36px !important;
        padding: 7px 14px;
        font-size: 0.74rem;
    }
    .mission-generator input {
        width: 100%;
        min-height: 40px;
        color: var(--tx);
        background: linear-gradient(135deg, rgba(248, 251, 255, 0.98), rgba(239, 246, 255, 0.75));
        border: 1px solid rgba(191, 219, 254, 0.92);
        border-radius: 12px;
        padding: 9px 12px;
        outline: none;
    }
    .mission-generator input:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.14);
    }
    .mission-generator-status {
        color: var(--tx3);
        font-size: 0.76rem;
        line-height: 1.35;
        margin: -4px 0 12px;
    }
    .mission-empty-state {
        color: var(--tx3);
        background: var(--bg3);
        border: 1px dashed var(--bd2);
        border-radius: 14px;
        padding: 16px;
        font-size: 0.84rem;
        line-height: 1.45;
        text-align: center;
    }
    .mission-card {
        width: 100%;
        min-height: 146px;
        text-align: left;
        border: 1px solid var(--bd);
        border-radius: 14px;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--mission-color, #2563eb) 10%, transparent), transparent 58%),
            var(--bg3);
        color: var(--tx);
        padding: 13px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        cursor: pointer;
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .mission-card:hover,
    .mission-card.active {
        border-color: color-mix(in srgb, var(--mission-color, #2563eb) 48%, var(--bd));
        box-shadow: 0 14px 34px color-mix(in srgb, var(--mission-color, #2563eb) 18%, transparent);
        transform: translateY(-2px);
    }
    #mission-mode-page .mission-card-name {
        color: var(--tx);
        font-size: 0.82rem;
        font-weight: 900;
        line-height: 1;
        margin: 0 !important;
        min-width: 0;
        width: 100% !important;
        max-width: none !important;
        display: flex !important;
        align-items: center;
        gap: 6px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap !important;
    }
    #mission-mode-page .mission-title-icon {
        width: 18px;
        height: 18px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 18px;
        color: var(--mission-color, #2563eb);
        background: color-mix(in srgb, var(--mission-color, #2563eb) 14%, transparent);
        font-size: 0.55rem;
    }
    #mission-mode-page .mission-title-text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .mission-card-copy {
        color: var(--tx3);
        font-size: 0.74rem;
        line-height: 1.45;
        margin: 0;
    }
    .mission-card-meta,
    .mission-meta-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: auto;
    }
    .mission-meta {
        border-radius: 999px;
        padding: 5px 9px;
        color: #334155;
        background: linear-gradient(135deg, rgba(248, 250, 252, 0.96), rgba(241, 245, 249, 0.92));
        border: 1px solid rgba(226, 232, 240, 0.96);
        font-size: 0.68rem;
        font-weight: 900;
        line-height: 1;
    }
    .mission-detail-head {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr);
        align-items: start;
        gap: 11px;
        margin-bottom: 12px;
    }
    .mission-detail-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--mission-color, #2563eb);
        background: linear-gradient(145deg, color-mix(in srgb, var(--mission-color, #2563eb) 14%, white), rgba(255, 255, 255, 0.78));
        border: 1px solid color-mix(in srgb, var(--mission-color, #2563eb) 24%, transparent);
        flex: 0 0 auto;
        font-size: 0.95rem;
    }
    .mission-prompt {
        color: var(--tx);
        font-size: 0.88rem;
        line-height: 1.45;
        font-weight: 750;
        min-height: 58px;
        padding: 12px 13px;
        border: 1px solid rgba(191, 219, 254, 0.72);
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.66), rgba(255, 255, 255, 0.94));
        margin: 10px 0;
    }
    .mission-prompt:empty {
        display: none;
    }
    .mission-criteria {
        display: grid;
        gap: 8px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .mission-criteria li {
        display: flex;
        gap: 8px;
        color: var(--tx2);
        font-size: 0.76rem;
        line-height: 1.45;
    }
    .mission-criteria i {
        color: #22c55e;
        margin-top: 3px;
        flex: 0 0 auto;
    }
    .mission-answer {
        width: 100%;
        min-height: 124px;
        resize: vertical;
        color: var(--tx);
        background: linear-gradient(135deg, rgba(248, 251, 255, 0.98), rgba(239, 246, 255, 0.68));
        border: 1px solid rgba(191, 219, 254, 0.86);
        border-radius: 14px;
        padding: 12px;
        line-height: 1.55;
        outline: none;
        font-size: 0.86rem;
    }
    .mission-answer:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.14);
    }
    .mission-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-top: 12px;
    }
    .mission-btn {
        min-height: 38px;
        border-radius: 12px;
        border: 1px solid rgba(203, 213, 225, 0.86);
        background: rgba(255, 255, 255, 0.72);
        color: var(--tx);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 8px 10px;
        font-size: 0.76rem;
        font-weight: 850;
        text-decoration: none;
        cursor: pointer;
        text-align: center;
    }
    .mission-btn i {
        font-size: 0.74rem;
    }
    .mission-btn-primary {
        color: #fff;
        border-color: transparent;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
    }
    #missionTool {
        padding: 14px;
    }
    #missionTool .mission-title {
        font-size: 1rem;
        line-height: 1.1;
        margin-bottom: 7px;
    }
    #missionTool .mission-actions .mission-btn {
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
    }
    #missionTool .mission-actions .mission-btn-primary {
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
    }
    .mission-result-grid {
        display: grid;
        grid-template-columns: 132px minmax(0, 1fr);
        gap: 14px;
        align-items: center;
    }
    #missionResultPanel {
        margin-top: 6px;
    }
    .mission-score-ring {
        width: 116px;
        height: 116px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        margin: 0 auto;
        color: var(--tx);
        background:
            conic-gradient(var(--score-color, #22c55e) var(--score, 0%), rgba(148, 163, 184, 0.2) 0),
            var(--bg3);
    }
    .mission-score-inner {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--sf);
        border: 1px solid var(--bd);
        font-size: 1.55rem;
        font-weight: 950;
    }
    .mission-feedback-list {
        display: grid;
        gap: 9px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .mission-feedback-list li {
        display: flex;
        gap: 9px;
        color: var(--tx2);
        font-size: 0.78rem;
        line-height: 1.45;
    }
    .mission-feedback-list i {
        color: #60a5fa;
        flex: 0 0 auto;
        margin-top: 3px;
    }
    .mission-voice-modal .modal-content {
        background: var(--sf);
        color: var(--tx);
        border: 1px solid var(--bd);
        border-radius: 18px;
        box-shadow: 0 24px 80px rgba(15, 23, 42, 0.24);
    }
    .modal-backdrop.mission-voice-backdrop {
        background: rgba(15, 23, 42, 0.42);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        opacity: 1 !important;
    }
    .mission-voice-modal .modal-header,
    .mission-voice-modal .modal-footer {
        border-color: var(--bd);
    }
    .mission-voice-modal .modal-footer {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .mission-voice-modal .modal-footer .btn {
        width: 100%;
        min-height: 42px;
    }
    .mission-voice-prompt,
    .mission-voice-transcript {
        color: var(--tx);
        background: var(--bg3);
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 14px;
        line-height: 1.5;
    }
    .mission-voice-transcript {
        min-height: 140px;
        white-space: pre-wrap;
    }
    .mission-voice-status {
        color: var(--tx3);
        font-size: 0.8rem;
        line-height: 1.4;
    }
    .mission-recent-list {
        display: grid;
        gap: 9px;
    }
    .mission-recent-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        padding: 11px;
        border: 1px solid var(--bd);
        border-radius: 14px;
        background: var(--bg3);
    }
    .mission-recent-title {
        color: var(--tx);
        font-size: 0.84rem;
        font-weight: 850;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .mission-recent-meta {
        color: var(--tx3);
        font-size: 0.72rem;
        margin-top: 2px;
    }

    /* Theme readability guard: keep Mission Mode surfaces legible in day and night mode. */
    #mission-mode-page input::placeholder,
    #mission-mode-page textarea::placeholder {
        color: var(--tx3);
        font-weight: 400 !important;
        opacity: 1;
    }
    #mission-mode-page :is(.mission-panel, .mission-card, .mission-generator input, .mission-answer, .mission-prompt, .mission-btn, .mission-meta, .mission-pill, .mission-detail-icon, .mission-board-icon, .mission-recent-item, .mission-empty-state) {
        color-scheme: light dark;
    }
    #mission-mode-page :is(.mission-title, .mission-card-name, .mission-prompt, .mission-answer, .mission-generator input, .mission-recent-title) {
        color: var(--tx) !important;
    }
    #mission-mode-page :is(.mission-kicker, .mission-card-copy, .mission-generator-status, .mission-recent-meta, .mission-voice-status) {
        color: var(--tx3) !important;
    }
    #mission-mode-page :is(.mission-criteria li, .mission-feedback-list li) {
        color: var(--tx2) !important;
    }
    .lm #mission-mode-page :is(.mission-panel, .mission-card, .mission-generator input, .mission-answer, .mission-prompt, .mission-btn, .mission-recent-item, .mission-empty-state) {
        border-color: rgba(191, 219, 254, 0.72);
    }
    :root:not(.lm) #mission-mode-page .mission-panel,
    .dm #mission-mode-page .mission-panel {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.92)), var(--sf) !important;
        border-color: rgba(148, 163, 184, 0.24) !important;
        box-shadow: 0 14px 36px rgba(0, 0, 0, 0.25);
    }
    :root:not(.lm) #mission-mode-page :is(.mission-card, .mission-recent-item, .mission-empty-state, .mission-voice-prompt, .mission-voice-transcript),
    .dm #mission-mode-page :is(.mission-card, .mission-recent-item, .mission-empty-state, .mission-voice-prompt, .mission-voice-transcript) {
        background: rgba(15, 23, 42, 0.62) !important;
        border-color: rgba(148, 163, 184, 0.22) !important;
    }
    :root:not(.lm) #mission-mode-page :is(.mission-generator input, .mission-answer, .mission-prompt),
    .dm #mission-mode-page :is(.mission-generator input, .mission-answer, .mission-prompt) {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.78), rgba(30, 41, 59, 0.66)) !important;
        border-color: rgba(148, 163, 184, 0.28) !important;
    }
    :root:not(.lm) #mission-mode-page .mission-btn,
    .dm #mission-mode-page .mission-btn {
        background: rgba(30, 41, 59, 0.7) !important;
        border-color: rgba(148, 163, 184, 0.28) !important;
        color: var(--tx) !important;
    }
    #mission-mode-page .mission-btn-primary,
    :root:not(.lm) #mission-mode-page .mission-btn-primary,
    .dm #mission-mode-page .mission-btn-primary {
        background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
        border-color: transparent !important;
        color: #ffffff !important;
    }
    :root:not(.lm) #mission-mode-page :is(.mission-meta, .mission-pill),
    .dm #mission-mode-page :is(.mission-meta, .mission-pill) {
        background: rgba(15, 23, 42, 0.68) !important;
        border-color: rgba(148, 163, 184, 0.24) !important;
        color: var(--tx2) !important;
    }
    :root:not(.lm) #mission-mode-page :is(.mission-board-icon, .mission-detail-icon, .mission-title-icon),
    .dm #mission-mode-page :is(.mission-board-icon, .mission-detail-icon, .mission-title-icon) {
        background: rgba(59, 130, 246, 0.16) !important;
        border-color: rgba(147, 197, 253, 0.24) !important;
        color: #93c5fd !important;
    }
    @media (max-width: 991px) {
        .mission-shell,
        .mission-result-grid {
            grid-template-columns: 1fr;
        }
        .mission-grid {
            grid-template-columns: 1fr;
        }
        .mission-generator {
            grid-template-columns: 1fr;
        }
        .mission-score-ring {
            width: 108px;
            height: 108px;
        }
        .mission-score-inner {
            width: 78px;
            height: 78px;
            font-size: 1.45rem;
        }
    }
    @media (max-width: 576px) {
        .mission-page {
            gap: 12px;
            padding-left: 4px;
            padding-right: 4px;
        }
        #mission-mode-page .mission-progress-hero.sr-page-hero {
            min-height: 92px !important;
            grid-template-columns: 36px minmax(0, 1fr) !important;
            gap: 9px !important;
            padding: 11px 96px 11px 12px !important;
            border-radius: 16px !important;
        }
        #mission-mode-page .mission-hero-icon {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.82rem !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-title {
            font-size: 0.9rem !important;
            margin-bottom: 4px !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            max-width: 12rem !important;
            font-size: 0.64rem !important;
            line-height: 1.28 !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            right: -4px !important;
            bottom: 5px !important;
            width: 84px !important;
        }
        .mission-panel {
            padding: 12px;
            border-radius: 15px;
        }
        #missionResultPanel {
            margin-top: 14px;
        }
        .mission-panel-head {
            display: grid;
            grid-template-columns: 1fr;
        }
        .mission-board-title {
            align-items: flex-start;
        }
        .mission-board-icon {
            width: 34px;
            height: 34px;
            border-radius: 12px;
        }
        .mission-title {
            font-size: 0.98rem;
        }
        .mission-kicker {
            font-size: 0.72rem;
        }
        .mission-generator {
            gap: 8px;
        }
        .mission-generator input {
            min-height: 38px;
            font-size: 0.78rem;
        }
        .mission-generator .mission-btn {
            width: min(170px, 76%);
            min-height: 34px !important;
            font-size: 0.68rem;
        }
        .mission-detail-head {
            display: grid !important;
            grid-template-columns: 32px minmax(0, 1fr) !important;
            align-items: start !important;
            gap: 8px;
        }
        .mission-detail-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            flex: 0 0 32px;
            font-size: 0.82rem;
        }
        .mission-detail-head > div {
            min-width: 0;
        }
        .mission-detail-head .mission-title {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .mission-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .mission-btn {
            min-height: 34px;
            padding: 7px 6px;
            font-size: 0.66rem;
            line-height: 1.15;
            white-space: normal;
            gap: 5px;
        }
        .mission-btn i {
            flex: 0 0 auto;
            margin: 0;
        }
        .mission-card {
            min-height: 0;
            gap: 8px;
            padding: 11px;
        }
        #mission-mode-page .mission-card-name {
            font-size: 0.76rem;
            white-space: nowrap !important;
            overflow: hidden;
            text-overflow: ellipsis;
            gap: 5px;
        }
        #mission-mode-page .mission-title-icon {
            width: 17px;
            height: 17px;
            border-radius: 5px;
            flex-basis: 17px;
            font-size: 0.5rem;
        }
        .mission-prompt {
            font-size: 0.8rem;
            padding: 10px;
            min-height: 52px;
        }
        .mission-answer {
            min-height: 112px;
            font-size: 0.78rem;
        }
        #missionTool {
            padding: 10px;
        }
        #missionTool .mission-title {
            font-size: 0.92rem;
        }
        .mission-meta {
            padding: 5px 8px;
            font-size: 0.62rem;
        }
        .mission-score-ring {
            width: 96px;
            height: 96px;
        }
        .mission-score-inner {
            width: 70px;
            height: 70px;
            font-size: 1.3rem;
        }
        .mission-feedback-list li {
            font-size: 0.74rem;
        }
    }

    @media (max-width: 767px) {
        #mission-mode-page {
            --mission-saas-radius: 12px;
            --mission-saas-gap: 6px;
            --mission-saas-border: rgba(37, 99, 235, 0.14);
            --mission-saas-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            --mission-saas-card: rgba(248, 250, 252, 0.76);
            --mission-saas-muted: #475569;
            gap: var(--mission-saas-gap);
            padding-inline: 0 !important;
            padding-bottom: 14px !important;
        }
        html[data-theme="dark"] #mission-mode-page,
        :root:not(.lm) #mission-mode-page,
        .dm #mission-mode-page {
            --mission-saas-border: rgba(147, 197, 253, 0.18);
            --mission-saas-shadow: 0 12px 26px rgba(0, 0, 0, 0.26);
            --mission-saas-card: rgba(255, 255, 255, 0.045);
            --mission-saas-muted: #cbd5e1;
        }
        #mission-mode-page .mission-progress-hero.sr-page-hero {
            min-height: 94px !important;
            grid-template-columns: 34px minmax(0, 1fr) !important;
            gap: 10px !important;
            padding: 12px 84px 14px 12px !important;
            margin-bottom: 4px !important;
            border-radius: var(--mission-saas-radius) !important;
            border-color: var(--mission-saas-border) !important;
            box-shadow: var(--mission-saas-shadow) !important;
            overflow: hidden !important;
        }
        #mission-mode-page .mission-hero-icon {
            width: 34px !important;
            height: 34px !important;
            border-radius: 10px !important;
            font-size: 0.82rem !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-title {
            font-size: 0.86rem !important;
            line-height: 1.12 !important;
            margin-bottom: 4px !important;
            white-space: normal !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            max-width: 12rem !important;
            font-size: 0.62rem !important;
            line-height: 1.34 !important;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            right: -6px !important;
            bottom: 8px !important;
            width: 72px !important;
        }
        #mission-mode-page .mission-shell {
            grid-template-columns: 1fr !important;
            gap: var(--mission-saas-gap) !important;
        }
        #mission-mode-page .mission-panel {
            padding: 9px !important;
            border-radius: var(--mission-saas-radius) !important;
            border-color: var(--mission-saas-border) !important;
            background: var(--sf) !important;
            box-shadow: var(--mission-saas-shadow) !important;
            margin: 0 !important;
        }
        #mission-mode-page .mission-panel-head {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr);
            gap: 5px !important;
            margin-bottom: 6px !important;
        }
        #mission-mode-page .mission-board-title,
        #mission-mode-page .mission-detail-head {
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            gap: 8px !important;
            align-items: start !important;
            margin-bottom: 0 !important;
        }
        #mission-mode-page .mission-board-icon,
        #mission-mode-page .mission-detail-icon {
            width: 30px !important;
            height: 30px !important;
            border-radius: 10px !important;
            font-size: 0.82rem !important;
            box-shadow: none !important;
        }
        #mission-mode-page .mission-title {
            font-size: 0.9rem !important;
            line-height: 1.14 !important;
            margin-bottom: 2px !important;
        }
        #mission-mode-page .mission-kicker,
        #mission-mode-page .mission-generator-status,
        #mission-mode-page .mission-card-copy {
            color: var(--mission-saas-muted) !important;
            font-size: 0.64rem !important;
            line-height: 1.24 !important;
        }
        #mission-mode-page .mission-pill {
            min-height: 26px !important;
            justify-self: start;
            padding: 6px 9px !important;
            border-radius: 999px !important;
            border-color: var(--mission-saas-border) !important;
            background: var(--mission-saas-card) !important;
            font-size: 0.6rem !important;
        }
        #mission-mode-page .mission-generator {
            gap: 5px !important;
            margin-bottom: 4px !important;
        }
        #mission-mode-page .mission-generator input,
        #mission-mode-page .mission-answer {
            border-radius: 10px !important;
            border-color: var(--mission-saas-border) !important;
            background: var(--mission-saas-card) !important;
            color: var(--tx) !important;
            box-shadow: none !important;
        }
        #mission-mode-page .mission-generator input {
            min-height: 40px !important;
            padding: 9px 10px !important;
            font-size: 0.72rem !important;
        }
        #mission-mode-page .mission-generator .mission-btn {
            width: 100% !important;
            min-height: 38px !important;
        }
        #mission-mode-page .mission-grid {
            grid-template-columns: 1fr !important;
            gap: 5px !important;
            margin-top: 4px !important;
        }
        #mission-mode-page .mission-grid:empty {
            display: none !important;
            margin: 0 !important;
        }
        #mission-mode-page .mission-card {
            min-height: 0 !important;
            gap: 6px !important;
            padding: 9px !important;
            border-radius: 10px !important;
            border-color: var(--mission-saas-border) !important;
            background: var(--mission-saas-card) !important;
            box-shadow: none !important;
        }
        #mission-mode-page .mission-card:hover,
        #mission-mode-page .mission-card.active {
            transform: none !important;
            border-color: color-mix(in srgb, var(--mission-color, #2563eb) 52%, var(--mission-saas-border)) !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--mission-color, #2563eb) 12%, transparent) !important;
        }
        #mission-mode-page .mission-card-name {
            font-size: 0.76rem !important;
            line-height: 1.15 !important;
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
            align-items: flex-start;
        }
        #mission-mode-page .mission-title-text {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        #mission-mode-page .mission-title-icon {
            width: 18px !important;
            height: 18px !important;
            flex-basis: 18px !important;
        }
        #mission-mode-page .mission-meta {
            padding: 3px 7px !important;
            border-radius: 999px !important;
            border-color: var(--mission-saas-border) !important;
            background: var(--mission-saas-card) !important;
            color: var(--mission-saas-muted) !important;
            font-size: 0.58rem !important;
        }
        #mission-mode-page #missionTool {
            position: static !important;
            z-index: auto;
        }
        #mission-mode-page .mission-detail-head .mission-title {
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
        }
        #mission-mode-page .mission-prompt {
            min-height: 0 !important;
            padding: 8px !important;
            border-radius: 10px !important;
            border-color: var(--mission-saas-border) !important;
            background: var(--mission-saas-card) !important;
            font-size: 0.78rem !important;
            line-height: 1.34 !important;
        }
        #mission-mode-page .mission-criteria {
            gap: 4px !important;
            margin-bottom: 6px !important;
        }
        #mission-mode-page .mission-criteria li,
        #mission-mode-page .mission-feedback-list li {
            gap: 7px !important;
            color: var(--mission-saas-muted) !important;
            font-size: 0.68rem !important;
            line-height: 1.34 !important;
        }
        #mission-mode-page .mission-answer {
            min-height: 96px !important;
            padding: 8px !important;
            font-size: 0.74rem !important;
            line-height: 1.38 !important;
        }
        #mission-mode-page .mission-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 5px !important;
            margin-top: 6px !important;
        }
        #mission-mode-page .mission-btn {
            min-height: 34px !important;
            border-radius: 9px !important;
            padding: 7px 8px !important;
            font-size: 0.64rem !important;
            font-weight: 900 !important;
            box-shadow: none !important;
            white-space: normal !important;
        }
        #mission-mode-page .mission-result-grid {
            grid-template-columns: 74px minmax(0, 1fr) !important;
            gap: 7px !important;
            align-items: center !important;
        }
        #mission-mode-page .mission-score-ring {
            width: 68px !important;
            height: 68px !important;
        }
        #mission-mode-page .mission-score-inner {
            width: 50px !important;
            height: 50px !important;
            font-size: 0.95rem !important;
        }
        #mission-mode-page #missionResultPanel {
            margin-top: 0 !important;
            padding-top: 12px !important;
        }
        #mission-mode-page #missionResultPanel .mission-panel-head {
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: center !important;
            gap: 5px !important;
            margin-bottom: 6px !important;
        }
        #mission-mode-page #missionResultPanel .mission-panel-head.mb-2 {
            margin-bottom: 6px !important;
        }
        #mission-mode-page #missionResultPanel .mission-title {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0 0 3px !important;
            line-height: 1.2 !important;
        }
        #mission-mode-page #missionResultPanel .mission-title i {
            margin-right: 0 !important;
            flex: 0 0 auto;
        }
        #mission-mode-page .mission-feedback-list {
            gap: 5px !important;
            margin: 0 !important;
        }
        #mission-mode-page .mission-recent-list {
            gap: 6px !important;
        }
        #mission-mode-page .mission-recent-item {
            grid-template-columns: minmax(0, 1fr) auto !important;
            gap: 6px !important;
            padding: 9px !important;
            border-radius: 10px !important;
            border-color: var(--mission-saas-border) !important;
            background: var(--mission-saas-card) !important;
        }
        #mission-mode-page .mission-recent-title,
        #mission-mode-page .mission-recent-meta {
            overflow-wrap: anywhere;
        }
        #mission-mode-page .mission-empty-state {
            padding: 12px !important;
            border-radius: 10px !important;
            border-color: var(--mission-saas-border) !important;
            background: var(--mission-saas-card) !important;
            font-size: 0.7rem !important;
        }
        .mission-voice-modal {
            --mission-saas-border: rgba(37, 99, 235, 0.14);
            --mission-saas-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            --mission-saas-card: rgba(248, 250, 252, 0.76);
        }
        html[data-theme="dark"] .mission-voice-modal,
        :root:not(.lm) .mission-voice-modal,
        .dm .mission-voice-modal {
            --mission-saas-border: rgba(147, 197, 253, 0.18);
            --mission-saas-shadow: 0 12px 26px rgba(0, 0, 0, 0.26);
            --mission-saas-card: rgba(255, 255, 255, 0.045);
        }
        .mission-voice-modal .modal-dialog {
            margin: 10px !important;
        }
        .mission-voice-modal .modal-content {
            border-radius: 14px !important;
            border-color: var(--mission-saas-border) !important;
            box-shadow: var(--mission-saas-shadow) !important;
        }
        .mission-voice-modal .modal-header,
        .mission-voice-modal .modal-footer {
            padding: 10px 12px !important;
        }
        .mission-voice-modal .modal-body {
            padding: 12px !important;
        }
        .mission-voice-modal .modal-body > .d-flex {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px !important;
        }
        .mission-voice-modal .modal-body > .d-flex .mission-btn {
            width: 100% !important;
            min-height: 36px !important;
            padding: 7px 8px !important;
            border-radius: 9px !important;
            font-size: 0.64rem !important;
            font-weight: 900 !important;
            white-space: normal !important;
        }
        .mission-voice-prompt,
        .mission-voice-transcript {
            padding: 10px !important;
            border-radius: 10px !important;
            border-color: var(--mission-saas-border) !important;
            background: var(--mission-saas-card) !important;
            font-size: 0.72rem !important;
            line-height: 1.36 !important;
        }
        .mission-voice-transcript {
            min-height: 130px !important;
        }
        .mission-voice-modal .modal-footer {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px !important;
        }
        .mission-voice-modal .modal-footer .btn {
            width: 100% !important;
            min-height: 36px !important;
            border-radius: 9px !important;
            font-size: 0.64rem !important;
            font-weight: 900 !important;
        }
    }
    /* Final compact hero override shared across user pages. */
    #mission-mode-page .mission-progress-hero.sr-page-hero {
        grid-template-columns: 30px minmax(0, 1fr) !important;
        gap: 8px !important;
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin-bottom: 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
    }
    #mission-mode-page .mission-hero-icon {
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-title {
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin: 0 0 3px !important;
        white-space: nowrap !important;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
        max-width: 13.5rem !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-art {
        right: -5px !important;
        bottom: -2px !important;
        width: 72px !important;
    }
    @media (max-width: 390px) {
        #mission-mode-page .mission-progress-hero.sr-page-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }
        #mission-mode-page .mission-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            font-size: 0.46rem !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            width: 66px !important;
        }
    }

    /* SaaSPro mobile polish for Mission Mode. */
    @media (max-width: 767px) {
        body #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0, rgba(20, 184, 166, 0.035) 260px, transparent 520px),
                var(--bg) !important;
        }

        body #mob-content > .db-content {
            padding: 12px 12px 18px !important;
        }

        html body #mission-mode-page {
            --mission-pro-card: rgba(255, 255, 255, 0.98);
            --mission-pro-field: rgba(255, 255, 255, 0.96);
            --mission-pro-soft: #f8fafc;
            --mission-pro-border: rgba(15, 23, 42, 0.1);
            --mission-pro-title: #0f172a;
            --mission-pro-muted: #64748b;
            --mission-pro-accent: #2563eb;
            --mission-pro-accent-2: #0891b2;
            --mission-pro-success: #059669;
            --mission-pro-warn: #d97706;
            --mission-pro-danger: #dc2626;
            --mission-pro-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
            max-width: 520px;
            margin: 0 auto !important;
            padding: 0 0 16px !important;
            gap: 10px !important;
            color: var(--mission-pro-title) !important;
        }

        html[data-theme="dark"] body #mission-mode-page,
        :root:not(.lm) body #mission-mode-page,
        body.dm #mission-mode-page,
        .dm #mission-mode-page {
            --mission-pro-card: rgba(15, 23, 42, 0.96);
            --mission-pro-field: rgba(15, 23, 42, 0.94);
            --mission-pro-soft: rgba(30, 41, 59, 0.96);
            --mission-pro-border: rgba(148, 163, 184, 0.24);
            --mission-pro-title: #f8fafc;
            --mission-pro-muted: #cbd5e1;
            --mission-pro-accent: #93c5fd;
            --mission-pro-accent-2: #67e8f9;
            --mission-pro-success: #86efac;
            --mission-pro-warn: #fcd34d;
            --mission-pro-danger: #fca5a5;
            --mission-pro-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
        }

        html body #mission-mode-page .mission-progress-hero.sr-page-hero {
            position: relative !important;
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            height: 69px !important;
            min-height: 69px !important;
            max-height: 69px !important;
            overflow: hidden !important;
            padding: 8px 72px 8px 10px !important;
            margin: 0 0 10px !important;
            border-radius: 8px !important;
            background:
                radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
                radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
                linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
            isolation: isolate !important;
        }

        html body #mission-mode-page .mission-progress-hero.sr-page-hero::before,
        html body #mission-mode-page .mission-progress-hero.sr-page-hero::after {
            content: none !important;
            display: none !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-inner,
        html body #mission-mode-page .mission-progress-hero .sr-page-hero-copy {
            display: contents !important;
            min-height: 0 !important;
            padding: 0 !important;
        }

        html body #mission-mode-page .mission-hero-icon {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 28px !important;
            height: 28px !important;
            border: 1px solid rgba(255, 255, 255, 0.28) !important;
            border-radius: 8px !important;
            background: rgba(15, 23, 42, 0.16) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            font-size: 0.8rem !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-title {
            display: block !important;
            margin: 0 0 3px !important;
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
            font-size: 0.72rem !important;
            font-weight: 900 !important;
            line-height: 1.15 !important;
            text-transform: none !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-title svg {
            display: none !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            display: -webkit-box !important;
            max-width: none !important;
            margin: 0 !important;
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
            font-size: 0.49rem !important;
            font-weight: 750 !important;
            line-height: 1.32 !important;
            overflow: hidden !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            display: block !important;
            position: absolute !important;
            right: -5px !important;
            bottom: -2px !important;
            width: 72px !important;
            max-width: none !important;
            pointer-events: none !important;
            filter: drop-shadow(0 10px 18px rgba(15, 23, 42, 0.16)) !important;
        }

        #mission-mode-page .mission-shell {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
            align-items: stretch !important;
        }

        #mission-mode-page .mission-panel {
            margin: 0 !important;
            padding: 10px !important;
            border: 1px solid var(--mission-pro-border) !important;
            border-radius: 8px !important;
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.04), rgba(20, 184, 166, 0.02)),
                var(--mission-pro-card) !important;
            color: var(--mission-pro-title) !important;
            box-shadow: var(--mission-pro-shadow) !important;
        }

        #mission-mode-page .mission-panel-head {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: start !important;
            gap: 8px !important;
            margin: 0 0 9px !important;
        }

        #mission-mode-page .mission-board-title,
        #mission-mode-page .mission-detail-head {
            display: grid !important;
            grid-template-columns: 34px minmax(0, 1fr) !important;
            align-items: start !important;
            gap: 8px !important;
            margin: 0 !important;
        }

        #mission-mode-page .mission-board-icon,
        #mission-mode-page .mission-detail-icon,
        #mission-mode-page .mission-title-icon {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: 1px solid var(--mission-pro-border) !important;
            border-radius: 8px !important;
            background: var(--mission-pro-soft) !important;
            color: var(--mission-color, var(--mission-pro-accent)) !important;
            -webkit-text-fill-color: var(--mission-color, var(--mission-pro-accent)) !important;
            box-shadow: none !important;
        }

        #mission-mode-page .mission-board-icon,
        #mission-mode-page .mission-detail-icon {
            width: 34px !important;
            height: 34px !important;
            flex: 0 0 34px !important;
            font-size: 0.84rem !important;
        }

        #mission-mode-page .mission-title-icon {
            width: 22px !important;
            height: 22px !important;
            flex: 0 0 22px !important;
            font-size: 0.62rem !important;
        }

        #mission-mode-page .mission-title {
            min-width: 0 !important;
            margin: 0 !important;
            color: var(--mission-pro-title) !important;
            -webkit-text-fill-color: var(--mission-pro-title) !important;
            font-size: 0.86rem !important;
            font-weight: 900 !important;
            line-height: 1.18 !important;
            letter-spacing: 0 !important;
            overflow-wrap: anywhere !important;
        }

        #mission-mode-page .mission-title i {
            color: currentColor !important;
            -webkit-text-fill-color: currentColor !important;
            margin-right: 0 !important;
        }

        #mission-mode-page :is(.mission-kicker, .mission-generator-status, .mission-card-copy, .mission-recent-meta, .mission-voice-status) {
            color: var(--mission-pro-muted) !important;
            -webkit-text-fill-color: var(--mission-pro-muted) !important;
            font-size: 0.66rem !important;
            font-weight: 650 !important;
            line-height: 1.32 !important;
        }

        #mission-mode-page .mission-board-title .mission-kicker,
        #mission-mode-page #missionResultSummary {
            display: -webkit-box !important;
            overflow: hidden !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
        }

        #mission-mode-page .mission-pill,
        #mission-mode-page .mission-meta {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 24px !important;
            padding: 4px 7px !important;
            border: 1px solid var(--mission-pro-border) !important;
            border-radius: 999px !important;
            background: color-mix(in srgb, var(--mission-pro-card) 86%, var(--pill-color, var(--mission-pro-accent)) 14%) !important;
            color: var(--pill-color, var(--mission-pro-accent)) !important;
            -webkit-text-fill-color: var(--pill-color, var(--mission-pro-accent)) !important;
            font-size: 0.58rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            white-space: nowrap !important;
        }

        #mission-mode-page .mission-meta {
            color: var(--mission-pro-muted) !important;
            -webkit-text-fill-color: var(--mission-pro-muted) !important;
        }

        #mission-mode-page .mission-generator {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            gap: 7px !important;
            align-items: stretch !important;
            margin: 0 0 7px !important;
        }

        #mission-mode-page .mission-generator input,
        #mission-mode-page .mission-answer,
        .mission-voice-modal :is(.mission-voice-prompt, .mission-voice-transcript) {
            width: 100% !important;
            border: 1px solid var(--mission-pro-border) !important;
            border-radius: 8px !important;
            background: var(--mission-pro-field) !important;
            color: var(--mission-pro-title) !important;
            -webkit-text-fill-color: var(--mission-pro-title) !important;
            box-shadow: none !important;
        }

        #mission-mode-page .mission-generator input {
            min-height: 38px !important;
            padding: 8px 10px !important;
            font-size: 0.72rem !important;
            font-weight: 750 !important;
        }

        #mission-mode-page .mission-generator .mission-btn {
            width: auto !important;
            min-width: 118px !important;
            min-height: 38px !important;
            padding: 0 10px !important;
        }

        #mission-mode-page .mission-generator-status {
            margin: 0 0 9px !important;
        }

        #mission-mode-page .mission-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 8px !important;
            margin: 0 !important;
        }

        #mission-mode-page .mission-card {
            display: grid !important;
            gap: 7px !important;
            width: 100% !important;
            min-height: 0 !important;
            padding: 9px !important;
            border: 1px solid var(--mission-pro-border) !important;
            border-radius: 8px !important;
            background:
                linear-gradient(135deg, color-mix(in srgb, var(--mission-color, #2563eb) 10%, transparent), transparent 62%),
                var(--mission-pro-field) !important;
            color: var(--mission-pro-title) !important;
            box-shadow: none !important;
        }

        #mission-mode-page .mission-card:hover,
        #mission-mode-page .mission-card.active {
            transform: none !important;
            border-color: color-mix(in srgb, var(--mission-color, #2563eb) 54%, var(--mission-pro-border)) !important;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--mission-color, #2563eb) 13%, transparent) !important;
        }

        #mission-mode-page .mission-card-name {
            display: grid !important;
            grid-template-columns: 22px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 7px !important;
            margin: 0 !important;
            color: var(--mission-pro-title) !important;
            -webkit-text-fill-color: var(--mission-pro-title) !important;
            font-size: 0.78rem !important;
            font-weight: 900 !important;
            line-height: 1.18 !important;
            white-space: normal !important;
            overflow: visible !important;
        }

        #mission-mode-page .mission-title-text {
            display: -webkit-box !important;
            min-width: 0 !important;
            overflow: hidden !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
        }

        #mission-mode-page .mission-card-copy {
            display: -webkit-box !important;
            margin: 0 !important;
            overflow: hidden !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
        }

        #mission-mode-page .mission-card-meta,
        #mission-mode-page .mission-meta-row {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 5px !important;
            margin: 0 !important;
        }

        #mission-mode-page #missionTool {
            position: static !important;
            z-index: auto !important;
            padding-bottom: 10px !important;
        }

        #mission-mode-page .mission-detail-head > div {
            min-width: 0 !important;
        }

        #mission-mode-page .mission-prompt {
            min-height: 0 !important;
            margin: 9px 0 !important;
            padding: 9px !important;
            border: 1px solid var(--mission-pro-border) !important;
            border-radius: 8px !important;
            background: var(--mission-pro-field) !important;
            color: var(--mission-pro-title) !important;
            -webkit-text-fill-color: var(--mission-pro-title) !important;
            font-size: 0.76rem !important;
            font-weight: 750 !important;
            line-height: 1.36 !important;
        }

        #mission-mode-page .mission-criteria {
            display: grid !important;
            gap: 6px !important;
            margin: 0 0 8px !important;
        }

        #mission-mode-page .mission-criteria li,
        #mission-mode-page .mission-feedback-list li {
            display: grid !important;
            grid-template-columns: 18px minmax(0, 1fr) !important;
            gap: 6px !important;
            color: var(--mission-pro-muted) !important;
            -webkit-text-fill-color: var(--mission-pro-muted) !important;
            font-size: 0.66rem !important;
            font-weight: 650 !important;
            line-height: 1.32 !important;
        }

        #mission-mode-page .mission-criteria i,
        #mission-mode-page .mission-feedback-list i {
            color: var(--mission-pro-success) !important;
            -webkit-text-fill-color: var(--mission-pro-success) !important;
            margin-top: 2px !important;
        }

        #mission-mode-page .mission-answer {
            min-height: 100px !important;
            padding: 9px !important;
            font-size: 0.74rem !important;
            font-weight: 650 !important;
            line-height: 1.4 !important;
            resize: vertical !important;
        }

        #mission-mode-page .mission-answer:focus,
        #mission-mode-page .mission-generator input:focus {
            border-color: rgba(37, 99, 235, 0.48) !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
        }

        #mission-mode-page .mission-actions {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 7px !important;
            margin-top: 8px !important;
        }

        #mission-mode-page .mission-btn,
        .mission-voice-modal .mission-btn,
        .mission-voice-modal .modal-footer .btn {
            min-width: 0 !important;
            min-height: 34px !important;
            padding: 0 9px !important;
            border: 1px solid var(--mission-pro-border) !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 5px !important;
            background: var(--mission-pro-field) !important;
            color: var(--mission-pro-title) !important;
            -webkit-text-fill-color: var(--mission-pro-title) !important;
            font-size: 0.66rem !important;
            font-weight: 900 !important;
            line-height: 1.12 !important;
            text-decoration: none !important;
            white-space: nowrap !important;
            box-shadow: none !important;
        }

        #mission-mode-page .mission-btn i,
        .mission-voice-modal .mission-btn i,
        .mission-voice-modal .modal-footer .btn i {
            color: currentColor !important;
            -webkit-text-fill-color: currentColor !important;
            font-size: 0.72rem !important;
            margin: 0 !important;
        }

        #mission-mode-page .mission-btn-primary,
        .mission-voice-modal .mission-btn-primary,
        .mission-voice-modal .modal-footer .btn-primary {
            border-color: transparent !important;
            background: linear-gradient(135deg, #2563eb, #0891b2) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.16) !important;
        }

        #mission-mode-page #voiceMissionBtn {
            color: var(--mission-pro-accent) !important;
            -webkit-text-fill-color: var(--mission-pro-accent) !important;
        }

        #mission-mode-page #clearMissionBtn,
        .mission-voice-modal #clearMissionVoiceBtn {
            color: var(--mission-pro-danger) !important;
            -webkit-text-fill-color: var(--mission-pro-danger) !important;
        }

        #mission-mode-page #missionResultPanel {
            margin: 0 !important;
            padding-top: 10px !important;
        }

        #mission-mode-page .mission-result-grid {
            display: grid !important;
            grid-template-columns: 66px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
        }

        #mission-mode-page .mission-score-ring {
            width: 62px !important;
            height: 62px !important;
            margin: 0 !important;
            background:
                conic-gradient(var(--score-color, #22c55e) var(--score, 0%), rgba(148, 163, 184, 0.22) 0),
                var(--mission-pro-soft) !important;
        }

        #mission-mode-page .mission-score-inner {
            width: 46px !important;
            height: 46px !important;
            border: 1px solid var(--mission-pro-border) !important;
            background: var(--mission-pro-card) !important;
            color: var(--mission-pro-title) !important;
            -webkit-text-fill-color: var(--mission-pro-title) !important;
            font-size: 0.9rem !important;
            font-weight: 950 !important;
        }

        #mission-mode-page #missionResultPanel .mission-panel-head {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: start !important;
            gap: 6px !important;
            margin: 0 0 6px !important;
        }

        #mission-mode-page .mission-feedback-list {
            gap: 6px !important;
            margin: 0 !important;
        }

        #mission-mode-page .mission-recent-list {
            display: grid !important;
            gap: 8px !important;
        }

        #mission-mode-page .mission-recent-item {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: center !important;
            gap: 8px !important;
            padding: 9px !important;
            border: 1px solid var(--mission-pro-border) !important;
            border-radius: 8px !important;
            background: var(--mission-pro-field) !important;
        }

        #mission-mode-page .mission-recent-title {
            color: var(--mission-pro-title) !important;
            -webkit-text-fill-color: var(--mission-pro-title) !important;
            font-size: 0.74rem !important;
            font-weight: 900 !important;
            line-height: 1.18 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        #mission-mode-page .mission-empty-state {
            padding: 14px !important;
            border: 1px dashed var(--mission-pro-border) !important;
            border-radius: 8px !important;
            background: var(--mission-pro-field) !important;
            color: var(--mission-pro-muted) !important;
            -webkit-text-fill-color: var(--mission-pro-muted) !important;
            font-size: 0.68rem !important;
            font-weight: 700 !important;
        }

        .mission-voice-modal {
            --mission-pro-card: rgba(255, 255, 255, 0.98);
            --mission-pro-field: rgba(255, 255, 255, 0.96);
            --mission-pro-soft: #f8fafc;
            --mission-pro-border: rgba(15, 23, 42, 0.1);
            --mission-pro-title: #0f172a;
            --mission-pro-muted: #64748b;
            --mission-pro-accent: #2563eb;
            --mission-pro-danger: #dc2626;
            --mission-pro-shadow: 0 18px 38px rgba(15, 23, 42, 0.18);
        }

        html[data-theme="dark"] .mission-voice-modal,
        :root:not(.lm) .mission-voice-modal,
        body.dm .mission-voice-modal,
        .dm .mission-voice-modal {
            --mission-pro-card: rgba(15, 23, 42, 0.98);
            --mission-pro-field: rgba(15, 23, 42, 0.94);
            --mission-pro-soft: rgba(30, 41, 59, 0.96);
            --mission-pro-border: rgba(148, 163, 184, 0.24);
            --mission-pro-title: #f8fafc;
            --mission-pro-muted: #cbd5e1;
            --mission-pro-accent: #93c5fd;
            --mission-pro-danger: #fca5a5;
            --mission-pro-shadow: 0 22px 46px rgba(0, 0, 0, 0.35);
        }

        .mission-voice-modal .modal-dialog {
            margin: 10px !important;
        }

        .mission-voice-modal .modal-content {
            border: 1px solid var(--mission-pro-border) !important;
            border-radius: 8px !important;
            background: var(--mission-pro-card) !important;
            color: var(--mission-pro-title) !important;
            box-shadow: var(--mission-pro-shadow) !important;
            overflow: hidden !important;
        }

        .mission-voice-modal .modal-header,
        .mission-voice-modal .modal-footer {
            padding: 10px !important;
            border-color: var(--mission-pro-border) !important;
        }

        .mission-voice-modal .modal-title {
            color: var(--mission-pro-title) !important;
            -webkit-text-fill-color: var(--mission-pro-title) !important;
            font-size: 0.9rem !important;
            font-weight: 900 !important;
            line-height: 1.2 !important;
        }

        .mission-voice-modal .modal-body {
            padding: 10px !important;
        }

        .mission-voice-modal .modal-body > .d-flex {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 7px !important;
            margin-bottom: 10px !important;
        }

        .mission-voice-modal :is(.mission-kicker, .mission-voice-status) {
            color: var(--mission-pro-muted) !important;
            -webkit-text-fill-color: var(--mission-pro-muted) !important;
            font-size: 0.66rem !important;
            font-weight: 750 !important;
            line-height: 1.28 !important;
        }

        .mission-voice-modal :is(.mission-voice-prompt, .mission-voice-transcript) {
            padding: 9px !important;
            font-size: 0.72rem !important;
            font-weight: 650 !important;
            line-height: 1.38 !important;
        }

        .mission-voice-modal .mission-voice-transcript {
            min-height: 130px !important;
        }

        .mission-voice-modal .modal-footer {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 7px !important;
        }
    }

    @media (max-width: 390px) {
        html body #mission-mode-page .mission-progress-hero.sr-page-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }

        html body #mission-mode-page .mission-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            font-size: 0.46rem !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            width: 66px !important;
        }

        #mission-mode-page .mission-generator {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        #mission-mode-page .mission-generator .mission-btn {
            width: 100% !important;
        }

        #mission-mode-page .mission-panel-head,
        #mission-mode-page #missionResultPanel .mission-panel-head {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        #mission-mode-page .mission-pill {
            justify-self: start !important;
        }

        #mission-mode-page .mission-result-grid {
            grid-template-columns: minmax(0, 1fr) !important;
            justify-items: stretch !important;
        }

        #mission-mode-page .mission-score-ring {
            justify-self: center !important;
        }
    }

    @media (max-width: 360px) {
        html body #mission-mode-page .mission-progress-hero.sr-page-hero {
            padding-right: 62px !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-title {
            font-size: 0.64rem !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            font-size: 0.44rem !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            right: -8px !important;
            width: 62px !important;
        }

        #mission-mode-page .mission-actions,
        .mission-voice-modal .modal-body > .d-flex,
        .mission-voice-modal .modal-footer {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }
</style>
@include('partials.page-hero-styles')
<style>
    @media (max-width: 991px) {
        #mission-mode-page.mission-page {
            gap: 8px !important;
            padding-inline: 0 !important;
        }
        #mission-mode-page .mission-progress-hero.sr-page-hero {
            min-height: 98px !important;
            padding: 12px 84px 14px 12px !important;
            margin: 0 0 8px !important;
            overflow: hidden !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            line-height: 1.34 !important;
            max-height: 2.7em !important;
            overflow: hidden !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            width: 72px !important;
            right: -6px !important;
            bottom: 8px !important;
        }
        #mission-mode-page .mission-shell {
            gap: 8px !important;
        }
        #mission-mode-page .mission-panel {
            margin: 0 !important;
            padding: 10px !important;
        }
        #mission-mode-page .mission-panel-head {
            margin-bottom: 8px !important;
        }
        #mission-mode-page #missionResultPanel {
            margin-top: 0 !important;
            padding-top: 10px !important;
        }
        #mission-mode-page #missionResultPanel .mission-result-grid {
            grid-template-columns: 66px minmax(0, 1fr) !important;
            gap: 8px !important;
            align-items: center !important;
            padding-top: 0 !important;
        }
        #mission-mode-page #missionResultPanel .mission-panel-head {
            align-items: center !important;
            margin-bottom: 4px !important;
        }
        #mission-mode-page .mission-actions {
            gap: 5px !important;
            margin-top: 6px !important;
        }
        #mission-mode-page #missionTool {
            padding-bottom: 6px !important;
        }
        #mission-mode-page .mission-score-ring {
            width: 62px !important;
            height: 62px !important;
        }
        #mission-mode-page .mission-score-inner {
            width: 46px !important;
            height: 46px !important;
            font-size: 0.9rem !important;
        }
        #mission-mode-page > .mission-shell + #missionResultPanel {
            margin-top: 8px !important;
        }
        #mission-mode-page > .mission-shell > #missionTool {
            margin-bottom: 0 !important;
        }
        #mission-mode-page > .mission-shell > #missionTool .mission-actions {
            margin-bottom: 0 !important;
        }
    }
    /* Final compact hero override after mission mobile rules. */
    #mission-mode-page .mission-progress-hero.sr-page-hero {
        grid-template-columns: 30px minmax(0, 1fr) !important;
        gap: 8px !important;
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin-bottom: 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
    }
    #mission-mode-page .mission-hero-icon {
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-title {
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin: 0 0 3px !important;
        white-space: nowrap !important;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
        max-width: 13.5rem !important;
        max-height: none !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
    }
    #mission-mode-page .mission-progress-hero .sr-page-hero-art {
        right: -5px !important;
        bottom: -2px !important;
        width: 72px !important;
    }
    @media (max-width: 390px) {
        #mission-mode-page .mission-progress-hero.sr-page-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }
        #mission-mode-page .mission-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            font-size: 0.46rem !important;
        }
        #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            width: 66px !important;
        }
    }

    /* Keep the SaaSPro mobile layer last after shared hero rules. */
    @media (max-width: 767px) {
        body #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0, rgba(20, 184, 166, 0.035) 260px, transparent 520px),
                var(--bg) !important;
        }

        body #mob-content > .db-content {
            padding: 12px 12px 18px !important;
        }

        html body #mission-mode-page.mission-page {
            max-width: 520px !important;
            margin: 0 auto !important;
            padding: 0 0 16px !important;
            gap: 10px !important;
        }

        html body #mission-mode-page .mission-progress-hero.sr-page-hero {
            height: 69px !important;
            min-height: 69px !important;
            max-height: 69px !important;
            margin: 0 0 10px !important;
            padding: 8px 72px 8px 10px !important;
            border-radius: 8px !important;
            background:
                radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
                radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
                linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-title {
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
            text-transform: none !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            max-width: none !important;
            max-height: none !important;
            color: rgba(248, 251, 255, 0.9) !important;
            -webkit-text-fill-color: rgba(248, 251, 255, 0.9) !important;
        }

        #mission-mode-page .mission-shell,
        #mission-mode-page .mission-grid {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
        }

        #mission-mode-page .mission-panel {
            border-radius: 8px !important;
        }

        #mission-mode-page #missionResultPanel {
            margin-top: 0 !important;
        }

        #mission-mode-page #missionResultPanel .mission-result-grid {
            grid-template-columns: 66px minmax(0, 1fr) !important;
        }
    }

    @media (max-width: 390px) {
        html body #mission-mode-page .mission-progress-hero.sr-page-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            width: 66px !important;
        }
    }

    @media (min-width: 992px) {
        html body #mission-mode-page.mission-page {
            --mission-desktop-radius: 12px;
            --mission-desktop-gap: 12px;
            --mission-desktop-border: rgba(148, 163, 184, 0.2);
            --mission-desktop-card-shadow: 0 10px 28px rgba(2, 6, 23, 0.12);
            width: 100% !important;
            max-width: 1480px !important;
            margin: 0 auto !important;
            padding: 0 0 24px !important;
            gap: var(--mission-desktop-gap) !important;
        }

        html.lm body #mission-mode-page.mission-page {
            --mission-desktop-border: rgba(15, 23, 42, 0.12);
            --mission-desktop-card-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        }

        html body #mission-mode-page .mission-progress-hero.sr-page-hero {
            position: relative !important;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 180px !important;
            align-items: center !important;
            min-height: 116px !important;
            max-height: none !important;
            height: auto !important;
            gap: 14px !important;
            margin: 0 0 12px !important;
            padding: 18px 178px 18px 20px !important;
            border-radius: var(--mission-desktop-radius) !important;
            overflow: hidden !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.12) !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-inner,
        html body #mission-mode-page .mission-progress-hero .sr-page-hero-copy {
            display: flex !important;
            align-items: center !important;
            min-height: 0 !important;
            padding: 0 !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-copy {
            gap: 12px !important;
            max-width: 780px !important;
        }

        html body #mission-mode-page .mission-hero-icon {
            width: 44px !important;
            height: 44px !important;
            flex: 0 0 44px !important;
            border-radius: 12px !important;
            font-size: 1.05rem !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-title {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin: 0 0 5px !important;
            color: var(--mission-hero-title-color) !important;
            -webkit-text-fill-color: var(--mission-hero-title-color) !important;
            font-size: clamp(1.12rem, 1.08vw, 1.45rem) !important;
            line-height: 1.12 !important;
            font-weight: 900 !important;
            text-transform: none !important;
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-subtitle {
            display: block !important;
            max-width: 640px !important;
            color: var(--mission-hero-text-color) !important;
            -webkit-text-fill-color: var(--mission-hero-text-color) !important;
            font-size: 0.84rem !important;
            line-height: 1.42 !important;
            font-weight: 600 !important;
            overflow: visible !important;
        }

        html body #mission-mode-page .mission-progress-hero .sr-page-hero-art {
            display: block !important;
            position: absolute !important;
            right: 12px !important;
            bottom: -10px !important;
            width: clamp(140px, 12vw, 174px) !important;
            max-width: none !important;
            opacity: 0.96 !important;
        }

        #mission-mode-page .mission-shell {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) clamp(330px, 34%, 470px) !important;
            gap: var(--mission-desktop-gap) !important;
            align-items: stretch !important;
        }

        #mission-mode-page .mission-panel {
            margin: 0 !important;
            padding: 14px !important;
            border-radius: var(--mission-desktop-radius) !important;
            border-color: var(--mission-desktop-border) !important;
            box-shadow: var(--mission-desktop-card-shadow) !important;
            min-width: 0 !important;
        }

        #mission-mode-page .mission-shell > .mission-panel {
            height: 100% !important;
        }

        #mission-mode-page .mission-panel-head {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            gap: 10px !important;
            margin: 0 0 10px !important;
        }

        #mission-mode-page .mission-board-title,
        #mission-mode-page .mission-detail-head {
            display: grid !important;
            grid-template-columns: 34px minmax(0, 1fr) !important;
            gap: 8px !important;
            align-items: start !important;
        }

        #mission-mode-page .mission-board-icon,
        #mission-mode-page .mission-detail-icon {
            width: 34px !important;
            height: 34px !important;
            flex: 0 0 34px !important;
            border-radius: 10px !important;
            font-size: 0.84rem !important;
        }

        #mission-mode-page .mission-title {
            margin: 0 !important;
            font-size: 0.94rem !important;
            line-height: 1.18 !important;
            font-weight: 900 !important;
        }

        #mission-mode-page :is(.mission-kicker, .mission-generator-status, .mission-card-copy, .mission-recent-meta) {
            font-size: 0.7rem !important;
            line-height: 1.34 !important;
        }

        #mission-mode-page .mission-pill,
        #mission-mode-page .mission-meta {
            min-height: 24px !important;
            padding: 5px 8px !important;
            font-size: 0.6rem !important;
            line-height: 1 !important;
            white-space: nowrap !important;
        }

        #mission-mode-page .mission-generator {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 150px !important;
            gap: 8px !important;
            align-items: stretch !important;
            margin: 0 0 8px !important;
        }

        #mission-mode-page .mission-generator input {
            min-height: 38px !important;
            padding: 8px 10px !important;
            border-radius: 10px !important;
            font-size: 0.78rem !important;
        }

        #mission-mode-page .mission-generator .mission-btn {
            width: 100% !important;
            min-width: 0 !important;
            min-height: 38px !important;
            padding: 0 10px !important;
        }

        #mission-mode-page .mission-generator-status {
            margin: 0 0 10px !important;
        }

        #mission-mode-page .mission-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin: 0 !important;
        }

        #mission-mode-page .mission-empty-state {
            grid-column: 1 / -1 !important;
            min-height: 132px !important;
            display: grid !important;
            place-items: center !important;
            padding: 14px !important;
            border-radius: 10px !important;
            font-size: 0.78rem !important;
        }

        #mission-mode-page .mission-card {
            min-height: 118px !important;
            padding: 10px !important;
            gap: 7px !important;
            border-radius: 10px !important;
            box-shadow: none !important;
        }

        #mission-mode-page .mission-card:hover,
        #mission-mode-page .mission-card.active {
            transform: translateY(-1px) !important;
            box-shadow: 0 10px 22px color-mix(in srgb, var(--mission-color, #2563eb) 13%, transparent) !important;
        }

        #mission-mode-page .mission-card-name {
            display: grid !important;
            grid-template-columns: 22px minmax(0, 1fr) !important;
            gap: 7px !important;
            font-size: 0.78rem !important;
            line-height: 1.18 !important;
            white-space: normal !important;
        }

        #mission-mode-page .mission-title-icon {
            width: 22px !important;
            height: 22px !important;
            flex: 0 0 22px !important;
            border-radius: 7px !important;
            font-size: 0.62rem !important;
        }

        #mission-mode-page .mission-title-text,
        #mission-mode-page .mission-card-copy {
            display: -webkit-box !important;
            overflow: hidden !important;
            -webkit-box-orient: vertical !important;
        }

        #mission-mode-page .mission-title-text {
            -webkit-line-clamp: 2 !important;
        }

        #mission-mode-page .mission-card-copy {
            margin: 0 !important;
            -webkit-line-clamp: 3 !important;
        }

        #mission-mode-page #missionTool {
            align-self: stretch !important;
            height: 100% !important;
            padding: 14px !important;
        }

        #mission-mode-page #missionTool .mission-detail-head {
            margin: 0 0 10px !important;
        }

        #mission-mode-page .mission-prompt {
            min-height: 72px !important;
            margin: 0 0 10px !important;
            padding: 10px !important;
            border-radius: 10px !important;
            font-size: 0.82rem !important;
            line-height: 1.36 !important;
        }

        #mission-mode-page .mission-criteria {
            gap: 6px !important;
            margin: 0 0 9px !important;
        }

        #mission-mode-page .mission-criteria li,
        #mission-mode-page .mission-feedback-list li {
            gap: 7px !important;
            font-size: 0.68rem !important;
            line-height: 1.32 !important;
        }

        #mission-mode-page .mission-answer {
            min-height: 126px !important;
            padding: 10px !important;
            border-radius: 10px !important;
            font-size: 0.78rem !important;
            line-height: 1.4 !important;
        }

        #mission-mode-page .mission-actions {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 6px !important;
            margin-top: 8px !important;
        }

        #mission-mode-page .mission-btn {
            min-height: 34px !important;
            padding: 7px 9px !important;
            border-radius: 9px !important;
            font-size: 0.68rem !important;
            line-height: 1.12 !important;
            white-space: nowrap !important;
            box-shadow: none !important;
        }

        #mission-mode-page #missionResultPanel {
            margin-top: var(--mission-desktop-gap) !important;
        }

        #mission-mode-page .mission-result-grid {
            display: grid !important;
            grid-template-columns: 96px minmax(0, 1fr) !important;
            gap: 12px !important;
            align-items: center !important;
        }

        #mission-mode-page .mission-score-ring {
            width: 86px !important;
            height: 86px !important;
            margin: 0 !important;
        }

        #mission-mode-page .mission-score-inner {
            width: 62px !important;
            height: 62px !important;
            font-size: 1.18rem !important;
        }

        #mission-mode-page .mission-feedback-list {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 7px 10px !important;
            margin: 0 !important;
        }

        #mission-mode-page .mission-recent-list {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
        }

        #mission-mode-page .mission-recent-item {
            padding: 9px 10px !important;
            border-radius: 10px !important;
        }
    }

    @media (min-width: 992px) and (max-width: 1320px) {
        #mission-mode-page .mission-shell {
            grid-template-columns: minmax(0, 1fr) clamp(300px, 31%, 380px) !important;
        }

        #mission-mode-page .mission-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        #mission-mode-page .mission-generator {
            grid-template-columns: minmax(0, 1fr) 132px !important;
        }

        #mission-mode-page .mission-answer {
            min-height: 112px !important;
        }

        #mission-mode-page .mission-feedback-list,
        #mission-mode-page .mission-recent-list {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }
</style>

<div class="db-section active mission-page" id="mission-mode-page">
    <div class="sr-page-hero mission-progress-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="mission-hero-icon"><i class="fa-solid fa-shield-check"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 4v6c0 4.4-2.8 7.8-7 8-4.2-.2-7-3.6-7-8V7l7-4Z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M9 12l2 2 4-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Real-Life Mission Mode
                    </h4>
                    <p class="sr-page-hero-subtitle">Complete practical speaking tasks for interviews, customer calls, panels, and team conversations.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="missionPanel" x1="36" y1="18" x2="182" y2="126"><stop stop-color="#DCFCE7"/><stop offset="1" stop-color="#DBEAFE"/></linearGradient><linearGradient id="missionAccent" x1="72" y1="38" x2="152" y2="118"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#14B8A6"/></linearGradient></defs>
            <rect x="32" y="24" width="156" height="104" rx="19" fill="url(#missionPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <path d="M73 91l23 22 53-66" fill="none" stroke="url(#missionAccent)" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="65" cy="50" r="14" fill="#F59E0B"/><path d="M58 50h14M65 43v14" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
            <rect x="132" y="88" width="34" height="22" rx="8" fill="#2563EB" opacity=".82"/>
            <path d="M34 136c28-10 61-11 94 0s61 7 82-4" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".52"/>
        </svg>
    </div>

    <div class="mission-shell">
        <section class="mission-panel">
            <div class="mission-panel-head">
                <div class="mission-board-title">
                    <span class="mission-board-icon"><i class="fa-solid fa-route"></i></span>
                    <div>
                        <h5 class="mission-title">Mission Board</h5>
                        <div class="mission-kicker">Generate tasks from what you want to practice, then measure how ready your answer sounds.</div>
                    </div>
                </div>
                <span class="mission-pill" style="--pill-color:#16a34a"><i class="fa-solid fa-microphone-lines"></i>{{ $practiceSessionCount }} saved sessions</span>
            </div>

            <div class="mission-generator">
                <input type="text" id="missionGoalInput" maxlength="240" placeholder="Example: BPO final interview, scholarship panel, IT debugging question...">
                <button type="button" class="mission-btn mission-btn-primary" id="generateMissionBtn" style="min-height:42px;"><i class="fa-solid fa-wand-magic-sparkles"></i>Generate Task</button>
            </div>
            <div class="mission-generator-status" id="missionGeneratorStatus">Tasks can be personalized to your target role, school interview, panel, or workplace situation.</div>
            <div class="mission-grid" id="missionGrid">
            </div>
        </section>

        <aside class="mission-panel" id="missionTool">
            <div class="mission-detail-head">
                <span class="mission-detail-icon" id="detailIcon"><i class="fa-solid fa-route"></i></span>
                <div>
                    <h5 class="mission-title" id="detailTitle">Mission</h5>
                    <div class="mission-meta-row">
                        <span class="mission-meta" id="detailCategory">Scenario</span>
                        <span class="mission-meta" id="detailDuration">60s</span>
                        <span class="mission-meta" id="detailIntent">Confident</span>
                    </div>
                </div>
            </div>

            <div class="mission-prompt" id="detailPrompt"></div>

            <ul class="mission-criteria mb-3" id="detailCriteria"></ul>

            <div class="mission-kicker mb-2" id="detailTip"></div>
            <textarea class="mission-answer" id="missionAnswer" placeholder="Type or paste your spoken answer here..."></textarea>

            <div class="mission-actions">
                <button type="button" class="mission-btn" id="missionTimerBtn"><i class="fa-regular fa-clock"></i><span id="missionTimerText">Start 0:00</span></button>
                <button type="button" class="mission-btn mission-btn-primary" id="scoreMissionBtn"><i class="fa-solid fa-chart-simple"></i>Score Answer</button>
                <button type="button" class="mission-btn" id="voiceMissionBtn"><i class="fa-solid fa-microphone-lines"></i>Practice With Voice</button>
                <button type="button" class="mission-btn" id="clearMissionBtn"><i class="fa-solid fa-eraser"></i>Clear</button>
            </div>
        </aside>
    </div>

    <section class="mission-panel" id="missionResultPanel" aria-live="polite">
        <div class="mission-result-grid">
            <div class="mission-score-ring" id="missionScoreRing" style="--score:0%;--score-color:#22c55e;">
                <div class="mission-score-inner"><span id="missionScoreValue">--</span></div>
            </div>
            <div>
                <div class="mission-panel-head mb-2">
                    <div>
                        <h5 class="mission-title"><i class="fa-solid fa-clipboard-check me-2" style="color:#22c55e;"></i>Mission Result</h5>
                        <div class="mission-kicker" id="missionResultSummary">Score an answer to see mission-specific feedback.</div>
                    </div>
                    <span class="mission-pill" id="missionResultStatus" style="--pill-color:#64748b">Waiting</span>
                </div>
                <ul class="mission-feedback-list" id="missionFeedbackList">
                    <li><i class="fa-solid fa-circle-info"></i><span>Your result will check structure, evidence, tone fit, and next action.</span></li>
                </ul>
            </div>
        </div>
    </section>

    @if($recentVoiceSessions->isNotEmpty())
        <section class="mission-panel">
            <div class="mission-panel-head">
                <div>
                    <h5 class="mission-title"><i class="fa-solid fa-clock-rotate-left me-2" style="color:#f59e0b;"></i>Recent Voice Practice</h5>
                    <div class="mission-kicker">Latest saved rehearsals that can support mission progress.</div>
                </div>
                <a href="{{ route('user.drills.voice') }}" class="mission-btn" style="min-height:36px;padding:7px 11px;"><i class="fa-solid fa-arrow-right"></i>Open Voice</a>
            </div>
            <div class="mission-recent-list">
                @foreach($recentVoiceSessions as $session)
                    <div class="mission-recent-item">
                        <div>
                            <div class="mission-recent-title">{{ $session->practice_scenario }}</div>
                            <div class="mission-recent-meta">{{ $session->created_at ? $session->created_at->format('M d, Y') : '' }} · {{ $session->wpm ?? 0 }} WPM · {{ $session->filler_words ?? 0 }} fillers</div>
                        </div>
                        <span class="mission-pill" style="--pill-color:#16a34a">{{ $session->clarity_score ?? 0 }}%</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

<div class="modal fade mission-voice-modal" id="missionVoiceModal" tabindex="-1" aria-labelledby="missionVoiceModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="missionVoiceModalTitle">Mission Voice Practice</h5>
                    <div class="mission-voice-status" id="missionVoiceStatus">Listen to the mission, then record your spoken answer.</div>
                </div>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="mission-kicker mb-2">Mission Question</div>
                    <div class="mission-voice-prompt" id="missionVoicePrompt">Generate or select a mission first.</div>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="mission-btn mission-btn-primary" id="speakMissionBtn" style="min-height:38px;padding:8px 12px;"><i class="fa-solid fa-volume-high"></i>AI Speak Mission</button>
                    <button type="button" class="mission-btn" id="startMissionVoiceBtn" style="min-height:38px;padding:8px 12px;"><i class="fa-solid fa-microphone"></i>Start Voice</button>
                    <button type="button" class="mission-btn" id="stopMissionVoiceBtn" style="min-height:38px;padding:8px 12px;"><i class="fa-solid fa-stop"></i>Stop</button>
                    <button type="button" class="mission-btn" id="clearMissionVoiceBtn" style="min-height:38px;padding:8px 12px;"><i class="fa-solid fa-eraser"></i>Clear Transcript</button>
                </div>
                <div>
                    <div class="mission-kicker mb-2">Voice Transcript</div>
                    <div class="mission-voice-transcript" id="missionVoiceTranscript" contenteditable="true">Your spoken answer will appear here...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="useMissionVoiceTranscriptBtn"><i class="fa-solid fa-check me-1"></i>Use Transcript</button>
            </div>
        </div>
    </div>
</div>

<script>
let missionData = @json($missions->values());
const missionGenerateUrl = @json(route('user.missions.generate'));
let activeMission = missionData[0] || null;
let missionTimer = null;
let remainingSeconds = activeMission ? Number(activeMission.duration) || 60 : 60;
const MissionSpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const missionSpeechLocale = document.documentElement.dataset.speechLocale || navigator.language || 'en-US';
const missionTranscriptPlaceholder = 'Your spoken answer will appear here...';
const missionDuplicateSafeWordSet = new Set([
    'i', "i'm", 'the', 'a', 'an', 'and', 'to', 'of', 'for', 'in', 'on', 'it', 'is', 'was',
    'were', 'am', 'are', 'my', 'we', 'you', 'that', 'this', 'with', 'um', 'uh', 'like'
]);
let missionRecognition = null;
let missionRecognitionActive = false;
let missionShouldAutoRestart = false;
let missionRecognitionToken = 0;
let missionVoiceTranscript = '';
let missionVoiceInterim = '';
let missionLastCommittedSpeech = '';
let missionLastCommittedAt = 0;

function escapeMissionHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function normalizeMissionText(value) {
    return String(value || '').toLowerCase().replace(/[^\p{L}\p{N}\s']/gu, ' ').replace(/\s+/g, ' ').trim();
}

function missionWordCount(value) {
    const clean = normalizeMissionText(value);
    return clean ? clean.split(/\s+/).length : 0;
}

function cleanMissionTranscriptText(value) {
    return String(value || '').replace(/\s+/gu, ' ').trim();
}

function normalizeMissionTranscriptForMatch(value) {
    return cleanMissionTranscriptText(value)
        .toLocaleLowerCase(missionSpeechLocale)
        .replace(/[^\p{L}\p{N}'\u2019\s]/gu, '')
        .replace(/\s+/gu, ' ')
        .trim();
}

function missionWordsForTranscript(value) {
    return cleanMissionTranscriptText(value).split(/\s+/u).filter(Boolean);
}

function appendMissionTranscriptWithoutOverlap(existing, addition) {
    const existingClean = cleanMissionTranscriptText(existing);
    const additionClean = cleanMissionTranscriptText(addition);
    if (!existingClean) return additionClean;
    if (!additionClean) return existingClean;

    const existingWords = missionWordsForTranscript(existingClean);
    const additionWords = missionWordsForTranscript(additionClean);
    const existingNormalized = existingWords.map(normalizeMissionTranscriptForMatch);
    const additionNormalized = additionWords.map(normalizeMissionTranscriptForMatch);
    const maxOverlap = Math.min(existingNormalized.length, additionNormalized.length, 24);
    let overlap = 0;

    for (let size = maxOverlap; size > 0; size--) {
        const existingTail = existingNormalized.slice(existingNormalized.length - size).join(' ');
        const additionHead = additionNormalized.slice(0, size).join(' ');
        if (existingTail && existingTail === additionHead) {
            overlap = size;
            break;
        }
    }

    const remainder = additionWords.slice(overlap).join(' ');
    return cleanMissionTranscriptText(existingClean + (remainder ? ' ' + remainder : ''));
}

function shouldCollapseMissionDuplicateWindow(size, normalizedPhrase) {
    if (!normalizedPhrase) return false;
    if (size >= 2) return true;
    return Array.from(normalizedPhrase).length > 2 || missionDuplicateSafeWordSet.has(normalizedPhrase);
}

function collapseRepeatedMissionSpeech(text) {
    const words = missionWordsForTranscript(text);
    if (words.length < 2) return cleanMissionTranscriptText(text);

    let index = 0;
    while (index < words.length) {
        let collapsed = false;
        const maxWindow = Math.min(12, Math.floor((words.length - index) / 2));

        for (let size = maxWindow; size >= 1; size--) {
            const first = words.slice(index, index + size).map(normalizeMissionTranscriptForMatch).join(' ');
            const second = words.slice(index + size, index + (size * 2)).map(normalizeMissionTranscriptForMatch).join(' ');

            if (first && first === second && shouldCollapseMissionDuplicateWindow(size, first)) {
                words.splice(index + size, size);
                index = Math.max(0, index - size);
                collapsed = true;
                break;
            }
        }

        if (!collapsed) index++;
    }

    return cleanMissionTranscriptText(words.join(' '));
}

function mergeMissionTranscriptParts(...parts) {
    let merged = '';
    parts.forEach(part => {
        const clean = cleanMissionTranscriptText(part);
        if (clean) merged = appendMissionTranscriptWithoutOverlap(merged, clean);
    });
    return collapseRepeatedMissionSpeech(merged);
}

function missionTranscriptEditorText() {
    const box = document.getElementById('missionVoiceTranscript');
    const text = cleanMissionTranscriptText(box ? (box.innerText || box.textContent || '') : '');
    return text === missionTranscriptPlaceholder ? '' : text;
}

function bestMissionSpeechAlternative(result) {
    let best = result[0] || null;
    for (let i = 1; i < result.length; i++) {
        if ((result[i].confidence || 0) > (best?.confidence || 0)) best = result[i];
    }
    return best ? best.transcript : '';
}

function hasAny(text, terms) {
    return terms.some(term => new RegExp(`\\b${term}\\b`, 'i').test(text));
}

function formatMissionTime(totalSeconds) {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

function renderMissionBoard() {
    const grid = document.getElementById('missionGrid');
    if (!grid) return;

    if (missionData.length === 0) {
        grid.innerHTML = '<div class="mission-empty-state">Tell AI what you want to practice to generate your mission tasks.</div>';
        return;
    }

    grid.innerHTML = missionData.map(mission => `
        <button type="button" class="mission-card" data-mission-id="${escapeMissionHtml(mission.id)}" style="--mission-color: ${escapeMissionHtml(mission.color)};">
            <h6 class="mission-card-name">
                <span class="mission-title-icon"><i class="fa-solid ${escapeMissionHtml(mission.icon)}"></i></span>
                <span class="mission-title-text">${escapeMissionHtml(mission.title)}</span>
            </h6>
            <div>
                <p class="mission-card-copy">${escapeMissionHtml(mission.prompt)}</p>
            </div>
            <div class="mission-card-meta">
                <span class="mission-meta">${escapeMissionHtml(mission.difficulty)}</span>
                <span class="mission-meta"><i class="fa-regular fa-clock me-1"></i>${Number(mission.duration) || 60}s</span>
                <span class="mission-meta"><i class="fa-solid fa-face-smile me-1"></i>${escapeMissionHtml(mission.intent)}</span>
            </div>
        </button>
    `).join('');

    document.querySelectorAll('.mission-card').forEach(card => {
        card.addEventListener('click', () => selectMission(card.dataset.missionId));
    });
}

function setMissionGeneratorStatus(message, color = 'var(--tx3)') {
    const status = document.getElementById('missionGeneratorStatus');
    if (!status) return;
    status.textContent = message;
    status.style.color = color;
}

async function generateMissionTasks() {
    const input = document.getElementById('missionGoalInput');
    const button = document.getElementById('generateMissionBtn');
    const goal = input ? input.value.trim() : '';

    if (goal.length < 3) {
        setMissionGeneratorStatus('Type what you want to practice first.', '#f59e0b');
        return;
    }

    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Generating';
    setMissionGeneratorStatus('Generating missions for your goal...');

    try {
        const response = await fetch(missionGenerateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ goal })
        });

        const data = await response.json();
        if (!response.ok || !data.success || !Array.isArray(data.missions) || data.missions.length === 0) {
            throw new Error(data.message || 'Mission generation failed.');
        }

        missionData = data.missions;
        activeMission = missionData[0] || null;
        renderMissionBoard();
        selectMission(activeMission?.id);
        setMissionGeneratorStatus('Generated fresh missions based on your request.', '#16a34a');
    } catch (error) {
        console.error('Mission generation failed:', error);
        setMissionGeneratorStatus('Could not generate with AI right now. Try a simpler goal or use the current missions.', '#ef4444');
    } finally {
        button.disabled = false;
        button.innerHTML = originalHtml;
    }
}

function stopMissionTimer() {
    if (missionTimer) {
        clearInterval(missionTimer);
        missionTimer = null;
    }
}

function resetMissionTimer() {
    stopMissionTimer();
    remainingSeconds = activeMission ? Number(activeMission.duration) || 60 : 60;
    document.getElementById('missionTimerText').textContent = `Start ${formatMissionTime(remainingSeconds)}`;
}

function tickMissionTimer() {
    remainingSeconds = Math.max(0, remainingSeconds - 1);
    document.getElementById('missionTimerText').textContent = remainingSeconds > 0
        ? `Stop ${formatMissionTime(remainingSeconds)}`
        : 'Time Up';

    if (remainingSeconds === 0) {
        stopMissionTimer();
    }
}

function toggleMissionTimer() {
    if (missionTimer) {
        resetMissionTimer();
        return;
    }

    missionTimer = setInterval(tickMissionTimer, 1000);
    tickMissionTimer();
}

function selectMission(id) {
    const mission = missionData.find(item => item.id === id) || missionData[0];
    if (!mission) return;

    activeMission = mission;
    document.querySelectorAll('.mission-card').forEach(card => {
        card.classList.toggle('active', card.dataset.missionId === mission.id);
    });

    document.getElementById('detailIcon').style.setProperty('--mission-color', mission.color);
    document.getElementById('detailIcon').innerHTML = `<i class="fa-solid ${escapeMissionHtml(mission.icon)}"></i>`;
    document.getElementById('detailTitle').textContent = mission.title;
    document.getElementById('detailCategory').textContent = mission.category;
    document.getElementById('detailDuration').textContent = `${mission.duration}s`;
    document.getElementById('detailIntent').textContent = mission.intent;
    document.getElementById('detailPrompt').textContent = mission.prompt;
    document.getElementById('detailTip').textContent = mission.coach_tip;
    document.getElementById('detailCriteria').innerHTML = mission.success_criteria
        .map(item => `<li><i class="fa-solid fa-check"></i><span>${escapeMissionHtml(item)}</span></li>`)
        .join('');

    resetMissionTimer();
    scoreMission(false);
}

function setMissionVoiceStatus(message, color = 'var(--tx3)') {
    const status = document.getElementById('missionVoiceStatus');
    if (!status) return;
    status.textContent = message;
    status.style.color = color;
}

function missionVoiceText() {
    if (!activeMission) return '';
    return `${activeMission.title}. ${activeMission.prompt}`;
}

function openMissionVoiceModal() {
    if (!activeMission) {
        setMissionGeneratorStatus('Generate or select a mission before using voice practice.', '#f59e0b');
        return;
    }

    document.getElementById('missionVoiceModalTitle').textContent = `${activeMission.title} Voice Practice`;
    document.getElementById('missionVoicePrompt').textContent = activeMission.prompt;
    setMissionVoiceStatus('Listen to the mission, then record your spoken answer.');

    const modalElement = document.getElementById('missionVoiceModal');
    if (window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    } else {
        modalElement.classList.add('show');
        modalElement.style.display = 'block';
        modalElement.removeAttribute('aria-hidden');
    }
}

function speakMissionPrompt() {
    if (!('speechSynthesis' in window)) {
        setMissionVoiceStatus('Text-to-speech is not supported in this browser.', '#f59e0b');
        return;
    }

    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(missionVoiceText());
    utterance.lang = 'en-PH';
    utterance.rate = 0.92;
    utterance.pitch = 1;
    utterance.onstart = () => setMissionVoiceStatus('AI is speaking the mission...');
    utterance.onend = () => setMissionVoiceStatus('Now answer the mission using Start Voice.');
    utterance.onerror = () => setMissionVoiceStatus('Could not speak the mission in this browser.', '#f59e0b');
    window.speechSynthesis.speak(utterance);
}

function commitMissionSpeechSegment(segment) {
    const cleanSegment = collapseRepeatedMissionSpeech(cleanMissionTranscriptText(segment));
    if (!cleanSegment) return;

    const normalized = normalizeMissionTranscriptForMatch(cleanSegment);
    const now = Date.now();
    if (normalized && normalized === missionLastCommittedSpeech && (now - missionLastCommittedAt) < 5000) return;

    missionVoiceTranscript = collapseRepeatedMissionSpeech(
        appendMissionTranscriptWithoutOverlap(missionVoiceTranscript, cleanSegment)
    );
    missionLastCommittedSpeech = normalized;
    missionLastCommittedAt = now;
}

function updateMissionVoiceTranscript() {
    const box = document.getElementById('missionVoiceTranscript');
    const text = mergeMissionTranscriptParts(missionVoiceTranscript, missionVoiceInterim);
    box.textContent = text || missionTranscriptPlaceholder;
}

function finalizeMissionVoiceInterim() {
    if (!missionVoiceInterim) return;
    commitMissionSpeechSegment(missionVoiceInterim);
    missionVoiceInterim = '';
    updateMissionVoiceTranscript();
}

function startMissionVoiceEngine(token = missionRecognitionToken) {
    if (token !== missionRecognitionToken) return false;
    if (!missionRecognition || missionRecognitionActive || !missionShouldAutoRestart) return false;

    try {
        missionRecognition.start();
        missionRecognitionActive = true;
        return true;
    } catch (error) {
        if (!error || error.name !== 'InvalidStateError') {
            console.error('Mission voice recognition failed to start:', error);
            setMissionVoiceStatus('Voice transcription could not start. You can type directly in the transcript box.', '#ef4444');
        }
        missionShouldAutoRestart = false;
        return false;
    }
}

function startMissionVoice() {
    if (!MissionSpeechRecognition) {
        setMissionVoiceStatus('Voice transcription is not supported in this browser. You can type directly in the transcript box.', '#f59e0b');
        return;
    }

    missionRecognitionToken++;
    const token = missionRecognitionToken;

    if (missionRecognition) {
        missionShouldAutoRestart = false;
        try { missionRecognition.stop(); } catch (error) {}
    }
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();

    missionVoiceTranscript = collapseRepeatedMissionSpeech(missionTranscriptEditorText());
    missionVoiceInterim = '';
    missionLastCommittedSpeech = '';
    missionLastCommittedAt = 0;
    missionShouldAutoRestart = true;

    missionRecognition = new MissionSpeechRecognition();
    missionRecognition.lang = missionSpeechLocale;
    missionRecognition.continuous = true;
    missionRecognition.interimResults = true;
    missionRecognition.maxAlternatives = 3;

    missionRecognition.onstart = () => {
        if (token !== missionRecognitionToken) return;
        missionRecognitionActive = true;
        setMissionVoiceStatus('Listening. Speak your answer clearly...', '#16a34a');
    };
    missionRecognition.onerror = event => {
        if (token !== missionRecognitionToken) return;
        const reason = event.error === 'not-allowed'
            ? 'Microphone permission was blocked. Allow microphone access, then try again.'
            : 'Voice transcription stopped. You can try again or type directly.';
        if (['not-allowed', 'service-not-allowed', 'audio-capture'].includes(event.error)) {
            missionShouldAutoRestart = false;
        }
        setMissionVoiceStatus(reason, '#ef4444');
    };
    missionRecognition.onend = () => {
        if (token !== missionRecognitionToken) return;
        missionRecognitionActive = false;
        if (missionShouldAutoRestart) {
            setMissionVoiceStatus('Reconnecting voice transcription...', '#f59e0b');
            setTimeout(() => startMissionVoiceEngine(token), 300);
            return;
        }

        setMissionVoiceStatus('Voice capture stopped. Review or edit the transcript.');
    };
    missionRecognition.onresult = event => {
        if (token !== missionRecognitionToken) return;
        const interimParts = [];
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = bestMissionSpeechAlternative(event.results[i]);
            if (!transcript) continue;

            if (event.results[i].isFinal) {
                commitMissionSpeechSegment(transcript);
            } else {
                interimParts.push(transcript);
            }
        }

        missionVoiceInterim = cleanMissionTranscriptText(interimParts.join(' '));
        updateMissionVoiceTranscript();
    };

    startMissionVoiceEngine(token);
}

function stopMissionVoice() {
    missionShouldAutoRestart = false;
    finalizeMissionVoiceInterim();
    missionRecognitionToken++;
    missionRecognitionActive = false;
    if (missionRecognition) {
        try { missionRecognition.stop(); } catch (error) {}
    }
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    setMissionVoiceStatus('Voice capture stopped. Review or edit the transcript.');
}

function clearMissionVoiceTranscript() {
    missionVoiceTranscript = '';
    missionVoiceInterim = '';
    missionLastCommittedSpeech = '';
    missionLastCommittedAt = 0;
    document.getElementById('missionVoiceTranscript').textContent = missionTranscriptPlaceholder;
    setMissionVoiceStatus('Transcript cleared. Start voice again when ready.');
}

function useMissionVoiceTranscript() {
    finalizeMissionVoiceInterim();
    const box = document.getElementById('missionVoiceTranscript');
    const text = collapseRepeatedMissionSpeech(missionTranscriptEditorText());
    if (!text) {
        setMissionVoiceStatus('Record or type a transcript before using it.', '#f59e0b');
        return;
    }

    missionVoiceTranscript = text;
    missionVoiceInterim = '';
    box.textContent = text;
    document.getElementById('missionAnswer').value = text;
    scoreMission(false);
    setMissionVoiceStatus('Transcript added to your mission answer.', '#16a34a');

    const modalElement = document.getElementById('missionVoiceModal');
    if (window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalElement).hide();
    }
}

function missionToneSignal(mission, normalizedText) {
    const intent = String(mission.intent || '').toLowerCase();
    if (intent === 'confident') {
        return hasAny(normalizedText, ['i can', 'i have', 'i handled', 'i led', 'i built', 'i improved', 'my experience']);
    }
    if (intent === 'calm') {
        return hasAny(normalizedText, ['understand', 'appreciate', 'clarify', 'resolve', 'next step', 'will check', 'help']);
    }
    if (intent === 'persuasive') {
        return hasAny(normalizedText, ['because', 'benefit', 'result', 'evidence', 'support', 'improve', 'reduce', 'increase']);
    }
    if (intent === 'accountable') {
        return hasAny(normalizedText, ['learned', 'changed', 'improved', 'now', 'feedback', 'mistake', 'weakness']);
    }
    return normalizedText.length > 0;
}

function scoreMission(showEmptyAlert = true) {
    if (!activeMission) return;

    const answer = document.getElementById('missionAnswer').value.trim();
    const normalized = normalizeMissionText(answer);
    const words = missionWordCount(answer);

    if (!answer && showEmptyAlert) {
        alert('Add an answer before scoring this mission.');
        return;
    }

    let score = 0;
    const feedback = [];

    if (words >= 45 && words <= 150) {
        score += 24;
        feedback.push('Interview use: your answer length is practical for a spoken interview response.');
    } else if (words > 0) {
        score += 12;
        feedback.push(words < 45 ? 'Interview use: add one concrete detail so the answer has enough proof.' : 'Interview use: tighten the answer so it stays clear under time pressure.');
    }

    if (hasAny(normalized, ['because', 'for example', 'example', 'when', 'during', 'project', 'internship', 'school', 'work', 'client'])) {
        score += 22;
        feedback.push('Interview use: evidence is present, which makes the answer easier for an interviewer to trust.');
    } else if (answer) {
        feedback.push('Interview use: add one specific example, project, class, client, or work situation.');
    }

    if (hasAny(normalized, ['result', 'improved', 'reduced', 'increased', 'learned', 'completed', 'solved', 'helped', 'successful'])) {
        score += 20;
        feedback.push('Interview use: the answer includes an outcome or lesson, so it shows growth or impact.');
    } else if (answer) {
        feedback.push('Interview use: close with a result, lesson, or next action.');
    }

    if (missionToneSignal(activeMission, normalized)) {
        score += 20;
        feedback.push(`Interview use: ${activeMission.intent.toLowerCase()} intention is showing in the wording.`);
    } else if (answer) {
        feedback.push(`Interview use: adjust wording so it sounds more ${String(activeMission.intent).toLowerCase()} without inventing facts.`);
    }

    if (hasAny(normalized, ['i will', 'next', 'contribute', 'support', 'help', 'apply', 'continue', 'moving forward'])) {
        score += 14;
        feedback.push('Interview use: the ending gives a forward direction the interviewer can remember.');
    } else if (answer) {
        feedback.push('Interview use: end with what you will do, contribute, or improve next.');
    }

    score = Math.max(0, Math.min(100, score));
    const scoreColor = score >= 80 ? '#22c55e' : (score >= 60 ? '#f59e0b' : '#ef4444');
    const status = score >= 80 ? 'Mission Ready' : (score >= 60 ? 'Almost There' : 'Needs Practice');

    document.getElementById('missionScoreRing').style.setProperty('--score', `${score}%`);
    document.getElementById('missionScoreRing').style.setProperty('--score-color', scoreColor);
    document.getElementById('missionScoreValue').textContent = answer ? score : '--';
    document.getElementById('missionResultStatus').textContent = answer ? status : 'Waiting';
    document.getElementById('missionResultStatus').style.setProperty('--pill-color', answer ? scoreColor : '#64748b');
    document.getElementById('missionResultSummary').textContent = answer
        ? `${activeMission.title} checked ${words} words against interview-ready structure, evidence, result, tone, and next action.`
        : 'Score an answer to see mission-specific feedback.';
    document.getElementById('missionFeedbackList').innerHTML = (answer ? feedback : ['Your result will check interview structure, evidence, role fit, tone, and next action.'])
        .map(item => `<li><i class="fa-solid fa-arrow-right"></i><span>${escapeMissionHtml(item)}</span></li>`)
        .join('');
}

document.addEventListener('DOMContentLoaded', () => {
    renderMissionBoard();
    document.getElementById('scoreMissionBtn').addEventListener('click', () => scoreMission(true));
    document.getElementById('clearMissionBtn').addEventListener('click', () => {
        document.getElementById('missionAnswer').value = '';
        scoreMission(false);
    });
    document.getElementById('missionTimerBtn').addEventListener('click', toggleMissionTimer);
    document.getElementById('voiceMissionBtn').addEventListener('click', openMissionVoiceModal);
    document.getElementById('speakMissionBtn').addEventListener('click', speakMissionPrompt);
    document.getElementById('startMissionVoiceBtn').addEventListener('click', startMissionVoice);
    document.getElementById('stopMissionVoiceBtn').addEventListener('click', stopMissionVoice);
    document.getElementById('clearMissionVoiceBtn').addEventListener('click', clearMissionVoiceTranscript);
    document.getElementById('useMissionVoiceTranscriptBtn').addEventListener('click', useMissionVoiceTranscript);
    document.getElementById('missionVoiceModal').addEventListener('shown.bs.modal', () => {
        document.querySelector('.modal-backdrop:last-of-type')?.classList.add('mission-voice-backdrop');
    });
    document.getElementById('missionVoiceModal').addEventListener('hidden.bs.modal', stopMissionVoice);
    document.getElementById('generateMissionBtn').addEventListener('click', generateMissionTasks);
    document.getElementById('missionGoalInput').addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            generateMissionTasks();
        }
    });
    const missionTranscriptBox = document.getElementById('missionVoiceTranscript');
    missionTranscriptBox.addEventListener('focus', () => {
        if (!missionTranscriptEditorText()) missionTranscriptBox.textContent = '';
    });
    missionTranscriptBox.addEventListener('input', () => {
        missionVoiceTranscript = collapseRepeatedMissionSpeech(missionTranscriptEditorText());
        missionVoiceInterim = '';
    });
    missionTranscriptBox.addEventListener('blur', () => {
        missionVoiceTranscript = collapseRepeatedMissionSpeech(missionTranscriptEditorText());
        missionVoiceInterim = '';
        updateMissionVoiceTranscript();
    });
    selectMission(activeMission?.id);
});
</script>
@endsection
