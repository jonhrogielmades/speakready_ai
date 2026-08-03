@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Challenges')

@section('content')
<style>
    /* Premium aesthetics for Learning Lab */
    .ll-header {
        background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(52,211,153,0.1) 100%);
        border: 1px solid var(--bd);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    .ll-stat-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s;
    }
    .ll-stat-card:hover {
        transform: translateY(-5px);
        border-color: rgba(139,92,246,0.5);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08);
    }
    .ll-stat-val {
        font-size: 2rem;
        font-weight: 700;
        color: var(--tx);
        margin: 10px 0;
    }
    .ll-nav-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        border-radius: 30px;
        background: var(--bg3);
        color: var(--tx);
        border: 1px solid var(--bd);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }
    @media (max-width: 576px) {
        .ll-nav-pill {
            padding: 8px 16px;
            font-size: 0.85rem;
            gap: 6px;
        }
    }
    .ll-nav-pill:hover {
        background: var(--sf);
        border-color: var(--pur);
        color: var(--pur);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(139,92,246,0.15);
    }
    .ll-nav-pill.active {
        background: var(--pur);
        color: #fff;
        border-color: var(--pur);
        box-shadow: 0 8px 25px rgba(139,92,246,0.3);
    }
    .learning-category-select-wrap {
        display: none;
    }
    .ll-category-list {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 16px;
        padding: 20px;
    }
    .ll-category-item {
        display: flex;
        align-items: center;
        padding: 10px;
        border-radius: 10px;
        color: var(--tx2);
        text-decoration: none;
        transition: 0.2s;
        margin-bottom: 5px;
    }
    .ll-category-item:hover, .ll-category-item.active {
        background: rgba(59,130,246,0.1);
        color: var(--pur);
    }
    .ll-module-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 18px;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: 0.3s;
    }
    .ll-module-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        border-color: rgba(59,130,246,0.4);
    }
    .ll-progress-bar {
        width: 100%;
        height: 8px;
        background: var(--bd);
        border-radius: 4px;
        overflow: hidden;
    }
    .ll-progress-fill {
        height: 100%;
        border-radius: 4px;
        background: linear-gradient(90deg, var(--pur) 0%, #34d399 100%);
    }
    /* Gamified Path Styles */
    .level-path-container {
        position: relative;
        padding: 20px 0;
        margin-top: 20px;
    }
    .level-path-line {
        position: absolute;
        left: 40px;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--bd);
        border-radius: 4px;
        z-index: 1;
    }
    .level-path-line-progress {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: linear-gradient(180deg, #34d399 0%, var(--pur) 100%);
        border-radius: 4px;
        z-index: 2;
    }
    .level-node {
        position: relative;
        display: flex;
        align-items: flex-start;
        margin-bottom: 40px;
        z-index: 3;
    }
    .level-icon-wrapper {
        width: 80px;
        flex-shrink: 0;
        display: flex;
        justify-content: center;
        position: relative;
    }
    .level-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: 0.3s;
        border: 4px solid var(--sf);
    }
    .level-node.completed .level-icon {
        background: #34d399;
        color: #fff;
    }
    .level-node.active .level-icon {
        background: var(--pur);
        color: #fff;
        box-shadow: 0 0 0 6px rgba(59,130,246,0.2), 0 10px 20px rgba(59,130,246,0.4);
        animation: pulse-ring 2s infinite;
    }
    .level-node.locked .level-icon {
        background: var(--bg3);
        color: var(--tx3);
        border-color: var(--bd);
    }
    .level-card {
        flex-grow: 1;
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        padding: 20px;
        margin-left: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s;
        position: relative;
        overflow: hidden;
    }
    .level-node.active .level-card {
        border-color: rgba(139,92,246,0.5);
        box-shadow: 0 20px 50px rgba(0,0,0,0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08);
    }
    
    .db-top-search { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .db-top-search:focus-within { border-color: var(--pur) !important; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15); background: var(--sf) !important; }

    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    
    /* Animations */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
    .level-node.locked .level-card {
        opacity: 0.7;
        pointer-events: none;
        user-select: none;
    }
    .level-node.locked .level-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: repeating-linear-gradient(45deg, rgba(0,0,0,0.02), rgba(0,0,0,0.02) 10px, transparent 10px, transparent 20px);
        z-index: 10;
    }
    .score-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        background: rgba(52,211,153,0.1);
        color: #34d399;
    }
    .requirement-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        background: rgba(245,158,11,0.1);
        color: #f59e0b;
    }
    .learning-notice {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        width: 100%;
        border-radius: 14px;
        padding: 14px 16px;
        margin-bottom: 18px;
        font-weight: 700;
        line-height: 1.42;
        overflow-wrap: anywhere;
    }
    .learning-notice-danger {
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #7f1d1d;
    }
    .learning-notice-success {
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.3);
        color: #047857;
    }
    .learning-notice-icon {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 30px;
        margin-top: 1px;
        background: rgba(255, 255, 255, 0.6);
    }
    .learning-notice-message {
        min-width: 0;
        flex: 1 1 auto;
    }
    .game-result-modal .modal-dialog {
        max-width: min(900px, calc(100vw - 24px));
    }
    .game-result-modal .modal-content {
        background: var(--sf);
        color: var(--tx);
        border: 1px solid var(--bd);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    }
    .game-result-hero {
        padding: 24px;
        background: linear-gradient(135deg, rgba(59,130,246,0.13), rgba(52,211,153,0.12));
        border-bottom: 1px solid var(--bd);
    }
    .game-result-score {
        width: 128px;
        height: 128px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        flex: 0 0 128px;
        box-shadow: inset 0 0 0 10px rgba(255,255,255,0.45);
    }
    .game-result-score-inner {
        width: 94px;
        height: 94px;
        border-radius: 50%;
        background: var(--sf);
        border: 1px solid var(--bd);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .game-result-stat {
        background: var(--bg3);
        border: 1px solid var(--bd);
        border-radius: 12px;
        padding: 12px;
        min-height: 74px;
    }
    .game-result-stat-label {
        font-size: 0.72rem;
        font-weight: 800;
        color: var(--tx3);
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .game-result-stat-value {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--tx);
    }
    .game-result-checklist {
        margin: 0;
        padding: 0;
        list-style: none;
        display: grid;
        gap: 7px;
    }
    .game-result-checklist li {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 0.86rem;
        color: var(--tx2);
    }
    .game-result-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }
    .game-result-actions form {
        margin: 0;
    }
    .game-result-breakdown-card {
        border: 1px solid var(--bd);
        border-radius: 10px;
        padding: 10px;
        background: var(--sf);
        min-height: 62px;
        overflow-wrap: anywhere;
    }
    .ai-scorecard-panel {
        border: 1px solid var(--bd);
        border-radius: 12px;
        padding: 14px;
        background: var(--bg3);
    }
    .ai-scorecard-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 12px;
    }
    .ai-scorecard-kicker {
        font-size: 0.72rem;
        color: var(--tx3);
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .ai-scorecard-title {
        font-size: 1.05rem;
        color: var(--tx);
        font-weight: 950;
        line-height: 1.2;
    }
    .ai-scorecard-reliability {
        min-width: 112px;
        border: 1px solid var(--bd);
        border-radius: 10px;
        padding: 9px 10px;
        background: var(--sf);
        text-align: right;
    }
    .ai-scorecard-reliability span,
    .ai-scorecard-level {
        display: block;
        color: var(--tx3);
        font-size: 0.68rem;
        font-weight: 850;
        text-transform: uppercase;
    }
    .ai-scorecard-reliability strong {
        display: block;
        color: var(--tx);
        font-size: 1.05rem;
        line-height: 1.05;
    }
    .ai-scorecard-summary {
        color: var(--tx2);
        font-size: 0.9rem;
        line-height: 1.48;
        margin-bottom: 12px;
    }
    .ai-scorecard-metric {
        border: 1px solid var(--bd);
        border-radius: 10px;
        padding: 11px;
        background: var(--sf);
        min-height: 142px;
        display: flex;
        flex-direction: column;
        gap: 7px;
    }
    .ai-scorecard-metric-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .ai-scorecard-metric-name {
        min-width: 0;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--tx);
        font-size: 0.8rem;
        font-weight: 900;
        line-height: 1.2;
    }
    .ai-scorecard-metric-score {
        flex: 0 0 auto;
        color: var(--tx);
        font-size: 1rem;
        font-weight: 950;
    }
    .ai-scorecard-meter {
        width: 100%;
        height: 7px;
        overflow: hidden;
        border-radius: 999px;
        background: var(--bd);
    }
    .ai-scorecard-meter-fill {
        height: 100%;
        border-radius: inherit;
    }
    .ai-scorecard-note {
        color: var(--tx2);
        font-size: 0.76rem;
        line-height: 1.36;
    }
    .ai-scorecard-actions-list {
        display: grid;
        gap: 7px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .ai-scorecard-actions-list li,
    .ai-scorecard-question {
        border: 1px solid var(--bd);
        border-radius: 9px;
        padding: 9px 10px;
        background: var(--sf);
        color: var(--tx2);
        font-size: 0.8rem;
        line-height: 1.38;
    }
    .ai-scorecard-question {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: start;
        gap: 9px;
    }
    .ai-scorecard-question strong,
    .ai-scorecard-question-score {
        color: var(--tx);
        font-weight: 900;
        white-space: nowrap;
    }
    .ai-scorecard-transparency {
        margin-top: 12px;
        color: var(--tx3);
        font-size: 0.76rem;
        line-height: 1.42;
    }
    @media (max-width: 576px) {
        .game-result-modal .modal-dialog {
            margin: 12px;
            max-width: calc(100vw - 24px);
        }
        .game-result-modal .modal-content {
            max-height: calc(100dvh - 24px);
            border-radius: 14px;
        }
        .game-result-hero {
            padding: 18px;
        }
        .game-result-score {
            width: 112px;
            height: 112px;
            flex-basis: 112px;
        }
        .game-result-score-inner {
            width: 82px;
            height: 82px;
        }
        #gameResultModalTitle {
            font-size: 1.08rem;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }
        .game-result-modal .modal-body {
            padding: 16px !important;
        }
        .game-result-modal .modal-footer {
            padding: 0 16px 16px !important;
        }
        .game-result-stat {
            min-height: 68px;
            padding: 10px;
        }
        .game-result-stat-value {
            font-size: 0.95rem;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }
        .game-result-breakdown-card {
            min-height: 58px;
            padding: 9px;
        }
        .ai-scorecard-heading {
            display: grid;
            gap: 9px;
        }
        .ai-scorecard-reliability {
            width: 100%;
            min-width: 0;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            text-align: left;
        }
        .ai-scorecard-reliability small {
            grid-column: 1 / -1;
        }
        .ai-scorecard-metric {
            min-height: 122px;
        }
        .ai-scorecard-question {
            grid-template-columns: auto minmax(0, 1fr);
        }
        .ai-scorecard-question-score {
            grid-column: 2;
        }
        .game-result-actions,
        .game-result-actions .btn,
        .game-result-actions form {
            width: 100%;
        }
        .game-result-actions {
            justify-content: stretch;
        }
        .game-result-actions .btn {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .game-result-modal .modal-footer [style*="text-align:right"] {
            text-align: left !important;
        }
    }
    @media (max-width: 380px) {
        .game-result-breakdown-grid > [class*="col-"] {
            width: 100%;
            flex: 0 0 100%;
        }
        .game-result-score {
            width: 102px;
            height: 102px;
            flex-basis: 102px;
        }
        .game-result-score-inner {
            width: 76px;
            height: 76px;
        }
    }
    @keyframes pulse-ring {
        0% { box-shadow: 0 0 0 0 rgba(59,130,246, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(59,130,246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59,130,246, 0); }
    }

    /* AI Assistant FAB */
    .ll-ai-fab {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--pur) 0%, #34d399 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 10px 25px rgba(59,130,246,0.4);
        cursor: pointer;
        transition: 0.3s;
        z-index: 100;
        text-decoration: none;
    }
    .ll-ai-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 15px 35px rgba(59,130,246,0.5);
    }
    
    /* Mobile Responsiveness */
    @media (max-width: 767px) {
        #learning-games-page .sr-page-actions {
            display: grid !important;
            grid-template-columns: 1fr auto !important;
            gap: 8px !important;
            margin-bottom: 12px !important;
        }
        #learning-games-page #tour-search {
            max-width: none !important;
            min-height: 44px;
            padding: 10px 12px !important;
            display: flex;
            align-items: center;
            gap: 9px;
        }
        #learning-games-page #btn-skill-tree {
            min-height: 44px;
            padding: 8px 11px;
            border-radius: 12px !important;
        }
        #learning-games-page #nav-pills-container {
            display: none !important;
        }
        #learning-games-page .learning-category-select-wrap {
            display: block;
            margin-bottom: 12px;
        }
        #learning-games-page .learning-category-select {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--bd);
            border-radius: 12px;
            background: var(--bg3);
            color: var(--tx);
            padding: 10px 12px;
            font-weight: 700;
            font-size: 0.86rem;
            outline: none;
        }
        .level-path-line {
            left: 20px;
        }
        .level-icon-wrapper {
            width: 42px;
        }
        .level-icon {
            width: 36px;
            height: 36px;
            font-size: 1rem;
            border-width: 3px;
        }
        .level-card {
            margin-left: 10px;
            padding: 14px;
            border-radius: 14px;
        }
        .level-node {
            margin-bottom: 22px;
        }
        #learning-games-page .ll-nav-pill {
            flex: 0 0 auto;
            min-height: 40px;
            padding: 8px 12px;
            font-size: 0.78rem;
            white-space: nowrap;
        }
        .db-top-search {
            width: 100% !important;
            max-width: 100% !important;
        }
        .d-flex.align-items-center.gap-3.flex-wrap {
            width: 100%;
        }

        .ll-stat-val {
            font-size: 1.08rem !important;
            line-height: 1.12;
        }
        .ll-stat-card {
            min-height: 96px !important;
            padding: 12px !important;
            border-radius: 14px !important;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        #learning-games-page #dashboard-stats {
            --bs-gutter-x: 10px;
            --bs-gutter-y: 10px;
            margin-bottom: 12px !important;
        }
        #learning-games-page #dashboard-stats > [class*="col-"] {
            width: 50% !important;
            flex: 0 0 50% !important;
        }
        #learning-games-page #dashboard-stats .ll-stat-card [style*="width:55px"] {
            width: 36px !important;
            height: 36px !important;
            border-radius: 11px !important;
            font-size: 1rem !important;
            flex: 0 0 36px !important;
        }
        #learning-games-page #dashboard-stats .ll-stat-card [style*="text-transform:uppercase"] {
            font-size: 0.64rem !important;
            line-height: 1.2;
        }
        #learning-games-page .level-card h5 {
            font-size: 0.94rem;
            line-height: 1.25;
        }
        #learning-games-page .level-card p,
        #learning-games-page .level-card div {
            overflow-wrap: anywhere;
        }
        #learning-games-page .level-card .btn {
            width: 100%;
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        #learning-games-page .learning-notice {
            gap: 10px;
            padding: 12px;
            border-radius: 12px;
            margin: 0 0 14px;
            font-size: 0.84rem;
            line-height: 1.35;
        }
        #learning-games-page .learning-notice-icon {
            width: 26px;
            height: 26px;
            flex-basis: 26px;
            border-radius: 8px;
            font-size: 0.82rem;
        }
        .ll-ai-fab {
            bottom: 80px;
            right: 20px;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
        }
    }
</style>
@include('partials.page-hero-styles')
<style>
    #learning-games-page {
        --challenge-blue: #2563eb;
        --challenge-ink: #071936;
        --challenge-muted: #52617a;
        --challenge-line: #dbe7fb;
    }
    #learning-games-page .sr-learning-hero {
        --learning-hero-title-color: #1d4ed8;
        --learning-hero-text-color: #334155;
        --learning-hero-icon-bg: rgba(239, 246, 255, 0.92);
        --learning-hero-icon-border: rgba(147, 197, 253, 0.42);
        display: grid !important;
        grid-template-columns: 44px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 10px !important;
        min-height: 104px;
        margin-bottom: 14px;
        padding: 14px 126px 14px 14px !important;
        border-radius: 16px;
        border-color: rgba(191, 219, 254, 0.86);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
    }
    .lm #learning-games-page .sr-learning-hero {
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
    }
    :root:not(.lm) #learning-games-page .sr-learning-hero,
    .dm #learning-games-page .sr-learning-hero {
        --learning-hero-title-color: #93c5fd;
        --learning-hero-text-color: #e2e8f0;
        --learning-hero-icon-bg: rgba(59, 130, 246, 0.2);
        --learning-hero-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28);
    }
    #learning-games-page .sr-learning-hero .sr-page-hero-inner,
    #learning-games-page .sr-learning-hero .sr-page-hero-copy {
        display: contents !important;
    }
    #learning-games-page .learning-hero-icon {
        box-sizing: border-box;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        border: 1px solid var(--learning-hero-icon-border) !important;
        border-radius: 10px !important;
        background: var(--learning-hero-icon-bg) !important;
        color: var(--learning-hero-title-color) !important;
        font-size: 0.9rem !important;
    }
    #learning-games-page .sr-learning-hero .sr-page-hero-title {
        display: block !important;
        color: var(--learning-hero-title-color) !important;
        background: none !important;
        -webkit-text-fill-color: var(--learning-hero-title-color) !important;
        font-size: 1.02rem !important;
        line-height: 1.08 !important;
        max-width: none;
        margin: 0 0 4px !important;
        font-weight: 950 !important;
        text-transform: uppercase;
        overflow-wrap: normal;
    }
    #learning-games-page .sr-learning-hero .sr-page-hero-title svg {
        display: none;
    }
    #learning-games-page .sr-learning-hero .sr-page-hero-subtitle {
        color: var(--learning-hero-text-color) !important;
        max-width: 15rem;
        margin: 0;
        font-size: 0.66rem !important;
        line-height: 1.32 !important;
        font-weight: 500;
    }
    #learning-games-page .sr-learning-hero .sr-page-hero-art {
        width: 94px;
        right: 8px;
        bottom: 4px;
        opacity: 0.92;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16));
    }
    #learning-games-page .sr-page-actions.learning-actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 14px;
        align-items: center;
        margin-bottom: 26px;
    }
    #learning-games-page #tour-search {
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: none !important;
        border-radius: 18px !important;
        padding: 16px 18px !important;
        background: linear-gradient(135deg, rgba(240, 253, 250, 0.88), rgba(255, 255, 255, 0.94)) !important;
        border-color: rgba(16, 185, 129, 0.25) !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }
    #learning-games-page #btn-skill-tree {
        min-height: 42px;
        padding: 0 16px;
        border-radius: 13px !important;
        background: linear-gradient(135deg, rgba(236, 253, 245, 0.95), rgba(255, 255, 255, 0.92)) !important;
        border-color: rgba(16, 185, 129, 0.28) !important;
        color: #103d35 !important;
        font-size: 0.82rem;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }
    #learning-games-page .ll-stat-card {
        border-radius: 18px;
        border-color: rgba(203, 213, 225, 0.82);
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
    }
    #learning-games-page #dashboard-stats {
        --bs-gutter-x: 14px;
        --bs-gutter-y: 14px;
    }
    #learning-games-page #dashboard-stats .ll-stat-card {
        min-height: 112px;
        justify-content: center;
    }
    #learning-games-page #dashboard-stats .ll-stat-card.d-flex {
        display: grid !important;
        grid-template-columns: 54px minmax(0, 1fr);
        align-items: center !important;
        gap: 14px !important;
        text-align: left;
    }
    #learning-games-page #dashboard-stats .ll-stat-card [style*="width:55px"] {
        width: 46px !important;
        height: 46px !important;
        border-radius: 13px !important;
        font-size: 1.18rem !important;
        flex: 0 0 46px !important;
    }
    #learning-games-page #dashboard-stats .ll-stat-val {
        font-size: 1.34rem !important;
        line-height: 1.05;
        margin: 0 0 3px !important;
    }
    #learning-games-page #dashboard-stats .ll-stat-val span {
        font-size: 0.8rem !important;
    }
    #learning-games-page #dashboard-stats .ll-stat-card [style*="text-transform:uppercase"] {
        font-size: 0.68rem !important;
        line-height: 1.15;
        letter-spacing: 0;
    }
    #learning-games-page #dashboard-stats .ll-progress-bar {
        margin: 8px 0 6px !important;
    }
    #learning-games-page .journey-header {
        margin-top: 8px;
    }
    #learning-games-page .journey-title {
        color: var(--challenge-ink);
        font-size: 1.85rem;
        font-weight: 900;
        letter-spacing: 0;
    }
    #learning-games-page .journey-lives {
        background: linear-gradient(135deg, #fff7ed, #ffedd5) !important;
        color: #071936 !important;
        border: 1px solid #fed7aa;
        border-radius: 999px !important;
        padding: 11px 18px !important;
        font-size: 0.98rem !important;
    }
    #learning-games-page .level-path-container {
        padding: 18px 0 6px;
        margin-top: 4px;
    }
    #learning-games-page .level-path-line {
        left: 42px;
        width: 5px;
        background: #dce7f8;
    }
    #learning-games-page .level-icon {
        width: 58px;
        height: 58px;
        border-width: 6px;
        font-weight: 900;
    }
    #learning-games-page .level-node.active .level-icon {
        background: linear-gradient(135deg, #60a5fa, #1d4ed8);
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.26);
    }
    #learning-games-page .level-node.locked .level-icon {
        background: #ffffff;
        color: #0f1f3f;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.14);
    }
    #learning-games-page .level-node.locked .level-card {
        opacity: 1;
        min-height: 210px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 180px;
        align-items: start;
        column-gap: 28px;
        background:
            radial-gradient(circle at 98% 86%, rgba(124, 58, 237, 0.1), rgba(124, 58, 237, 0) 30%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 251, 255, 0.94));
    }
    #learning-games-page .level-node.locked .level-card::after {
        display: none;
    }
    #learning-games-page .level-node.locked .locked-card-main {
        min-width: 0;
    }
    #learning-games-page .locked-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: fit-content;
        margin: 14px 0 16px;
        padding: 9px 18px;
        border-radius: 999px;
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.14), rgba(99, 102, 241, 0.12));
        color: #6d5ac7;
        font-size: 0.98rem;
        font-weight: 900;
    }
    #learning-games-page .locked-card-art {
        width: 154px;
        aspect-ratio: 1;
        justify-self: end;
        align-self: start;
        display: grid;
        place-items: center;
        border-radius: 42px;
        background:
            radial-gradient(circle at 28% 24%, rgba(255, 255, 255, 0.82), rgba(255, 255, 255, 0) 34%),
            linear-gradient(135deg, rgba(124, 58, 237, 0.09), rgba(99, 102, 241, 0.16));
        color: #8177d9;
        font-size: 4.4rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82);
        opacity: 0.78;
    }
    #learning-games-page .level-card {
        border-radius: 20px;
        padding: 28px 30px;
        background:
            radial-gradient(circle at 92% 28%, rgba(99, 102, 241, 0.09), rgba(99, 102, 241, 0) 26%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 251, 255, 0.94));
        border-color: #cfe0f7;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.07);
    }
    #learning-games-page .level-card > .d-flex:first-child {
        margin-bottom: 12px !important;
    }
    #learning-games-page .level-card h5 {
        color: #071936 !important;
        font-weight: 900 !important;
        line-height: 1.25;
    }
    #learning-games-page .level-card p {
        color: #52617a !important;
        font-size: 1rem !important;
    }
    #learning-games-page .level-card [style*="background:rgba(59,130,246,0.07)"] {
        margin-top: 14px;
        margin-bottom: 16px !important;
        border-radius: 14px !important;
        padding: 16px !important;
    }
    #learning-games-page .level-card .d-flex.flex-wrap.gap-2 {
        gap: 9px !important;
        margin-bottom: 18px !important;
    }
    #learning-games-page .score-badge,
    #learning-games-page .requirement-badge,
    #learning-games-page .level-card .badge {
        border-radius: 999px !important;
        padding: 9px 14px;
        font-size: 0.85rem;
        border-color: #d6e4f8 !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.78);
    }
    #learning-games-page .active-challenge-panel {
        background: linear-gradient(135deg, #eff6ff, #f8fbff) !important;
        border-color: #cfe0f7 !important;
        border-radius: 18px !important;
        padding: 20px !important;
        margin-bottom: 18px !important;
    }
    #learning-games-page .active-challenge-panel [style*="Contains"] {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px !important;
        font-size: 0.94rem !important;
        color: #173b67 !important;
    }
    #learning-games-page .active-challenge-panel [style*="Success checklist"] {
        margin-bottom: 10px !important;
        font-size: 0.8rem !important;
        color: #44536a !important;
    }
    #learning-games-page .active-challenge-panel [style*="fa-check"] {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 8px !important;
        line-height: 1.45 !important;
        color: #334155 !important;
    }
    #learning-games-page .active-challenge-panel [style*="Best attempt"] {
        margin-top: 14px !important;
        margin-bottom: 10px;
    }
    #learning-games-page .start-challenge-btn {
        width: 100%;
        min-height: 58px;
        border-radius: 16px !important;
        font-size: 1.08rem;
        font-weight: 900 !important;
        background: linear-gradient(135deg, #3b82f6, #075dec) !important;
        box-shadow: 0 16px 28px rgba(37, 99, 235, 0.28) !important;
    }
    :root:not(.lm) #learning-games-page,
    .dm #learning-games-page {
        --challenge-ink: #e5eefc;
        --challenge-muted: #b8c5d8;
        --challenge-line: rgba(148, 163, 184, 0.26);
    }
    :root:not(.lm) #learning-games-page #tour-search,
    .dm #learning-games-page #tour-search,
    :root:not(.lm) #learning-games-page #btn-skill-tree,
    .dm #learning-games-page #btn-skill-tree,
    :root:not(.lm) #learning-games-page .learning-category-select,
    .dm #learning-games-page .learning-category-select {
        background: rgba(15, 23, 42, 0.78) !important;
        border-color: rgba(148, 163, 184, 0.28) !important;
        color: #e2e8f0 !important;
    }
    :root:not(.lm) #learning-games-page #tour-search input,
    .dm #learning-games-page #tour-search input {
        color: #e2e8f0 !important;
    }
    :root:not(.lm) #learning-games-page #tour-search input::placeholder,
    .dm #learning-games-page #tour-search input::placeholder {
        color: #94a3b8 !important;
        font-weight: 400 !important;
    }
    :root:not(.lm) #learning-games-page .ll-nav-pill,
    .dm #learning-games-page .ll-nav-pill,
    :root:not(.lm) #learning-games-page .ll-stat-card,
    .dm #learning-games-page .ll-stat-card,
    :root:not(.lm) #learning-games-page .level-card,
    .dm #learning-games-page .level-card {
        background: linear-gradient(145deg, rgba(15, 23, 42, 0.96), rgba(17, 24, 39, 0.92)) !important;
        border-color: rgba(148, 163, 184, 0.28) !important;
        color: #e2e8f0 !important;
    }
    :root:not(.lm) #learning-games-page .ll-nav-pill:not(.active),
    .dm #learning-games-page .ll-nav-pill:not(.active) {
        color: #cbd5e1 !important;
    }
    :root:not(.lm) #learning-games-page .journey-lives,
    .dm #learning-games-page .journey-lives {
        background: rgba(127, 29, 29, 0.28) !important;
        border-color: rgba(248, 113, 113, 0.28) !important;
        color: #fecaca !important;
    }
    :root:not(.lm) #learning-games-page .level-path-line,
    .dm #learning-games-page .level-path-line {
        background: rgba(148, 163, 184, 0.24) !important;
    }
    :root:not(.lm) #learning-games-page .level-card h5,
    .dm #learning-games-page .level-card h5,
    :root:not(.lm) #learning-games-page .ll-stat-val,
    .dm #learning-games-page .ll-stat-val,
    :root:not(.lm) #learning-games-page [style*="color:var(--tx)"],
    .dm #learning-games-page [style*="color:var(--tx)"] {
        color: #e5eefc !important;
    }
    :root:not(.lm) #learning-games-page .level-card p,
    .dm #learning-games-page .level-card p,
    :root:not(.lm) #learning-games-page [style*="color:var(--tx2)"],
    .dm #learning-games-page [style*="color:var(--tx2)"] {
        color: #cbd5e1 !important;
    }
    :root:not(.lm) #learning-games-page [style*="color:var(--tx3)"],
    .dm #learning-games-page [style*="color:var(--tx3)"] {
        color: #94a3b8 !important;
    }
    :root:not(.lm) #learning-games-page .level-node.locked .level-card,
    .dm #learning-games-page .level-node.locked .level-card {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(30, 41, 59, 0.88)) !important;
        border-color: rgba(148, 163, 184, 0.24) !important;
    }
    :root:not(.lm) #learning-games-page .level-node.locked .level-icon,
    .dm #learning-games-page .level-node.locked .level-icon {
        background: rgba(30, 41, 59, 0.94) !important;
        color: #94a3b8 !important;
        border-color: rgba(148, 163, 184, 0.24) !important;
    }
    :root:not(.lm) #learning-games-page .locked-status-pill,
    .dm #learning-games-page .locked-status-pill {
        background: rgba(124, 58, 237, 0.18) !important;
        border-color: rgba(167, 139, 250, 0.24) !important;
        color: #c4b5fd !important;
    }
    :root:not(.lm) #learning-games-page .locked-card-art,
    .dm #learning-games-page .locked-card-art,
    :root:not(.lm) #learning-games-page .active-challenge-panel,
    .dm #learning-games-page .active-challenge-panel,
    :root:not(.lm) #learning-games-page .level-card [style*="background:rgba(59,130,246,0.07)"],
    .dm #learning-games-page .level-card [style*="background:rgba(59,130,246,0.07)"],
    :root:not(.lm) #learning-games-page .score-badge,
    .dm #learning-games-page .score-badge,
    :root:not(.lm) #learning-games-page .requirement-badge,
    .dm #learning-games-page .requirement-badge,
    :root:not(.lm) #learning-games-page .level-card .badge,
    .dm #learning-games-page .level-card .badge {
        background: rgba(30, 41, 59, 0.82) !important;
        border-color: rgba(148, 163, 184, 0.28) !important;
        color: #e2e8f0 !important;
    }
    #learning-games-page .sr-learning-hero,
    #learning-games-page .learning-mobile-control-row,
    #learning-games-page #dashboard-stats,
    #learning-games-page .level-card,
    #learning-games-page .active-challenge-panel,
    #learning-games-page .game-result-modal .modal-content {
        max-width: 100%;
    }
    #learning-games-page .level-card,
    #learning-games-page .ll-stat-card,
    #learning-games-page .ll-nav-pill,
    #learning-games-page .badge,
    #learning-games-page .requirement-badge,
    #learning-games-page .score-badge {
        overflow-wrap: anywhere;
    }
    @media (max-width: 767px) {
        #learning-games-page {
            padding-left: 2px;
            padding-right: 2px;
        }
        #learning-games-page .sr-learning-hero {
            min-height: 92px !important;
            grid-template-columns: 36px minmax(0, 1fr) !important;
            gap: 9px !important;
            border-radius: 16px;
            padding: 11px 96px 11px 12px !important;
            margin-bottom: 14px;
        }
        #learning-games-page .sr-learning-hero .sr-page-hero-inner {
            display: contents !important;
            padding-right: 0;
        }
        #learning-games-page .learning-hero-icon {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.82rem !important;
        }
        #learning-games-page .sr-learning-hero .sr-page-hero-title {
            font-size: 0.9rem !important;
            line-height: 1.08;
            margin-bottom: 4px !important;
        }
        #learning-games-page .sr-learning-hero .sr-page-hero-title svg {
            display: none;
        }
        #learning-games-page .sr-learning-hero .sr-page-hero-subtitle {
            font-size: 0.64rem !important;
            line-height: 1.28;
            max-width: 12rem;
            margin-top: 0;
        }
        #learning-games-page .sr-learning-hero .sr-page-hero-art {
            display: block;
            width: 84px;
            right: -4px;
            bottom: 5px;
        }
        #learning-games-page .sr-page-actions.learning-actions {
            grid-template-columns: 1fr;
            gap: 9px;
            margin-bottom: 12px;
        }
        #learning-games-page #tour-search,
        #learning-games-page #btn-skill-tree {
            min-height: 44px;
            border-radius: 13px !important;
            padding: 10px 12px !important;
            font-size: 0.84rem;
        }
        #learning-games-page #btn-skill-tree {
            width: auto;
        }
        #learning-games-page .learning-mobile-control-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            align-items: stretch;
            margin-bottom: 12px;
        }
        #learning-games-page .learning-mobile-control-row .learning-category-select-wrap {
            margin-bottom: 0;
            min-width: 0;
        }
        #learning-games-page .learning-mobile-control-row #btn-skill-tree {
            min-width: 0;
            height: 44px;
            padding: 8px 12px !important;
            justify-self: end;
        }
        #learning-games-page .learning-mobile-control-row .learning-category-select {
            height: 44px;
            min-height: 44px;
            font-size: 0.78rem;
            padding: 8px 28px 8px 10px;
            text-overflow: ellipsis;
        }
        #learning-games-page .ll-stat-card {
            min-height: 82px !important;
            border-radius: 14px !important;
            padding: 11px !important;
        }
        #learning-games-page #dashboard-stats .ll-stat-card {
            min-height: 84px !important;
        }
        #learning-games-page #dashboard-stats .ll-stat-card.d-flex {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 9px !important;
        }
        #learning-games-page #dashboard-stats .ll-stat-card [style*="width:55px"] {
            width: 34px !important;
            height: 34px !important;
            border-radius: 10px !important;
            font-size: 0.9rem !important;
            flex-basis: 34px !important;
        }
        #learning-games-page #dashboard-stats .ll-stat-val {
            font-size: 1rem !important;
            line-height: 1.05;
            margin-bottom: 2px !important;
        }
        #learning-games-page #dashboard-stats .ll-stat-val span {
            font-size: 0.68rem !important;
        }
        #learning-games-page #dashboard-stats .ll-stat-card [style*="text-transform:uppercase"] {
            font-size: 0.55rem !important;
            line-height: 1.1;
        }
        #learning-games-page #dashboard-stats .ll-stat-card .d-flex.justify-content-between {
            margin-bottom: 6px !important;
            gap: 6px;
        }
        #learning-games-page #dashboard-stats .ll-stat-card .d-flex.justify-content-between span:first-child {
            font-size: 0.74rem !important;
            line-height: 1.15;
            white-space: nowrap;
        }
        #learning-games-page #dashboard-stats .ll-stat-card .d-flex.justify-content-between span:last-child {
            font-size: 0.56rem !important;
            padding: 3px 6px !important;
        }
        #learning-games-page #dashboard-stats .ll-progress-bar {
            height: 9px !important;
            margin: 6px 0 5px !important;
        }
        #learning-games-page #dashboard-stats .ll-progress-bar + div {
            font-size: 0.58rem !important;
            line-height: 1.1;
        }
        #learning-games-page .journey-header {
            align-items: flex-start !important;
            gap: 10px;
            margin-bottom: 12px !important;
        }
        #learning-games-page .journey-title {
            font-size: 1.28rem;
            line-height: 1.15;
        }
        #learning-games-page .journey-lives {
            padding: 8px 12px !important;
            font-size: 0.78rem !important;
        }
        #learning-games-page .level-path-line {
            left: 18px;
            width: 4px;
        }
        #learning-games-page .level-icon-wrapper {
            width: 38px;
        }
        #learning-games-page .level-icon {
            width: 34px;
            height: 34px;
            border-width: 4px;
            font-size: 0.9rem;
        }
        #learning-games-page .level-card {
            margin-left: 10px;
            padding: 16px;
            border-radius: 16px;
        }
        #learning-games-page .level-node.locked .level-card {
            min-height: 132px;
            display: block;
            position: relative;
            padding-right: 16px;
        }
        #learning-games-page .level-node.locked .level-card > .locked-card-main > .d-flex:first-child {
            padding-right: 76px;
        }
        #learning-games-page .locked-status-pill {
            margin: 8px 0 10px;
            padding: 6px 11px;
            gap: 6px;
            font-size: 0.76rem;
        }
        #learning-games-page .locked-card-art {
            width: 62px;
            position: absolute;
            top: 14px;
            right: 14px;
            border-radius: 20px;
            font-size: 1.8rem;
        }
        #learning-games-page .level-card > .d-flex:first-child {
            align-items: flex-start !important;
            gap: 8px !important;
            margin-bottom: 10px !important;
        }
        #learning-games-page .level-card h5 {
            font-size: 1rem !important;
        }
        #learning-games-page .level-card p {
            font-size: 0.82rem !important;
            line-height: 1.45 !important;
            margin-bottom: 10px !important;
        }
        #learning-games-page .level-card [style*="background:rgba(59,130,246,0.07)"] {
            margin-top: 10px;
            margin-bottom: 12px !important;
            border-radius: 12px !important;
            padding: 12px !important;
        }
        #learning-games-page .level-card .d-flex.flex-wrap.gap-2 {
            gap: 7px !important;
            margin-bottom: 12px !important;
        }
        #learning-games-page .score-badge,
        #learning-games-page .requirement-badge,
        #learning-games-page .level-card .badge {
            padding: 7px 10px;
            font-size: 0.72rem;
            max-width: 100%;
            white-space: normal;
            text-align: left;
        }
        #learning-games-page .active-challenge-panel {
            padding: 14px !important;
            border-radius: 14px !important;
            margin-bottom: 16px !important;
        }
        #learning-games-page .active-challenge-panel [style*="Contains"] {
            margin-bottom: 12px !important;
            font-size: 0.84rem !important;
        }
        #learning-games-page .active-challenge-panel [style*="Success checklist"] {
            margin-bottom: 8px !important;
        }
        #learning-games-page .active-challenge-panel [style*="fa-check"] {
            gap: 7px;
            margin-bottom: 7px !important;
            font-size: 0.75rem !important;
        }
        #learning-games-page .active-challenge-panel [style*="Best attempt"],
        #learning-games-page .active-challenge-panel [style*="Cost"] {
            font-size: 0.74rem !important;
        }
        #learning-games-page .start-challenge-btn {
            min-height: 48px;
            font-size: 0.94rem;
        }
    }
    @media (max-width: 380px) {
        #learning-games-page .sr-learning-hero {
            min-height: 74px !important;
            padding: 8px 76px 8px 12px !important;
        }
        #learning-games-page .sr-learning-hero .sr-page-hero-inner {
            padding-right: 0;
        }
        #learning-games-page .sr-learning-hero .sr-page-hero-title {
            font-size: 0.84rem !important;
        }
        #learning-games-page .sr-learning-hero .sr-page-hero-subtitle {
            font-size: 0.6rem !important;
            max-width: 10.5rem;
        }
        #learning-games-page .sr-learning-hero .sr-page-hero-art {
            width: 68px;
            right: -4px;
        }
        #learning-games-page #dashboard-stats > [class*="col-"] {
            width: 100% !important;
            flex: 0 0 100% !important;
        }
        #learning-games-page .level-card {
            padding: 13px;
        }
        #learning-games-page .level-node.locked .level-card {
            padding-right: 13px;
        }
        #learning-games-page .level-node.locked .level-card > .locked-card-main > .d-flex:first-child {
            padding-right: 60px;
        }
        #learning-games-page .locked-card-art {
            width: 50px;
            top: 12px;
            right: 12px;
            border-radius: 16px;
            font-size: 1.45rem;
        }
        #learning-games-page .learning-mobile-control-row {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 7px;
        }
        #learning-games-page .learning-mobile-control-row #btn-skill-tree,
        #learning-games-page .learning-mobile-control-row .learning-category-select {
            font-size: 0.72rem;
        }
        #learning-games-page .learning-mobile-control-row #btn-skill-tree {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }
    }

    /* SaaSPro mobile polish for Challenges. */
    @media (max-width: 767px) {
        body #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0, rgba(20, 184, 166, 0.035) 260px, transparent 520px),
                var(--bg) !important;
        }

        body #mob-content > .db-content {
            padding: 12px 12px 18px !important;
        }

        html body #learning-games-page {
            --challenge-pro-card: rgba(255, 255, 255, 0.98);
            --challenge-pro-field: rgba(255, 255, 255, 0.96);
            --challenge-pro-soft: #f8fafc;
            --challenge-pro-border: rgba(15, 23, 42, 0.1);
            --challenge-pro-title: #0f172a;
            --challenge-pro-muted: #64748b;
            --challenge-pro-accent: #2563eb;
            --challenge-pro-accent-2: #0891b2;
            --challenge-pro-success: #059669;
            --challenge-pro-warn: #d97706;
            --challenge-pro-danger: #dc2626;
            --challenge-pro-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
            max-width: 520px;
            margin: 0 auto !important;
            padding: 0 0 16px !important;
            color: var(--challenge-pro-title) !important;
        }

        html[data-theme="dark"] body #learning-games-page,
        :root:not(.lm) body #learning-games-page,
        body.dm #learning-games-page,
        .dm #learning-games-page {
            --challenge-pro-card: rgba(15, 23, 42, 0.94);
            --challenge-pro-field: rgba(30, 41, 59, 0.9);
            --challenge-pro-soft: rgba(51, 65, 85, 0.78);
            --challenge-pro-border: rgba(148, 163, 184, 0.24);
            --challenge-pro-title: #f8fafc;
            --challenge-pro-muted: #cbd5e1;
            --challenge-pro-accent: #93c5fd;
            --challenge-pro-accent-2: #67e8f9;
            --challenge-pro-success: #86efac;
            --challenge-pro-warn: #fbbf24;
            --challenge-pro-danger: #fca5a5;
            --challenge-pro-shadow: 0 1px 0 rgba(148, 163, 184, 0.08), 0 18px 36px rgba(0, 0, 0, 0.26);
        }

        html body #learning-games-page .sr-learning-hero.sr-page-hero {
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            width: 100% !important;
            height: 69px !important;
            min-height: 69px !important;
            max-height: 69px !important;
            margin: 0 0 10px !important;
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

        html[data-theme="dark"] body #learning-games-page .sr-learning-hero.sr-page-hero,
        :root:not(.lm) body #learning-games-page .sr-learning-hero.sr-page-hero,
        body.dm #learning-games-page .sr-learning-hero.sr-page-hero,
        .dm #learning-games-page .sr-learning-hero.sr-page-hero {
            background:
                linear-gradient(115deg, rgba(30, 64, 175, 0.96), rgba(15, 118, 110, 0.9)),
                #1e3a8a !important;
            box-shadow: 0 18px 34px rgba(0, 0, 0, 0.3) !important;
        }

        html body #learning-games-page .sr-learning-hero.sr-page-hero::before {
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

        html body #learning-games-page .sr-learning-hero.sr-page-hero::after {
            display: none !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-inner,
        html body #learning-games-page .sr-learning-hero .sr-page-hero-copy {
            display: contents !important;
            min-height: 0 !important;
            padding: 0 !important;
        }

        html body #learning-games-page .learning-hero-icon {
            width: 28px !important;
            height: 28px !important;
            min-width: 28px !important;
            border: 1px solid rgba(255, 255, 255, 0.28) !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
            font-size: 0.76rem !important;
            box-shadow: none !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-copy > div:last-child {
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-title {
            display: block !important;
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

        html body #learning-games-page .sr-learning-hero .sr-page-hero-title svg {
            display: none !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-subtitle {
            display: -webkit-box !important;
            max-width: 11.8rem !important;
            margin: 0 !important;
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 0.49rem !important;
            font-weight: 700 !important;
            line-height: 1.25 !important;
            overflow: hidden !important;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-art {
            display: block !important;
            width: 72px !important;
            height: auto !important;
            right: -5px !important;
            bottom: -2px !important;
            opacity: 0.98 !important;
            filter: drop-shadow(0 10px 16px rgba(15, 23, 42, 0.22));
            pointer-events: none;
        }

        html body #learning-games-page .sr-page-actions.learning-actions {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 8px !important;
            margin: 0 0 10px !important;
        }

        html body #learning-games-page #tour-search {
            width: 100% !important;
            max-width: none !important;
            min-height: 38px !important;
            display: none !important;
            grid-template-columns: 16px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            padding: 8px 10px !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 8px !important;
            background: var(--challenge-pro-field) !important;
            box-shadow: var(--challenge-pro-shadow) !important;
        }

        html body #learning-games-page #tour-search i {
            color: var(--challenge-pro-accent) !important;
            font-size: 0.76rem !important;
        }

        html body #learning-games-page #tour-search input {
            min-width: 0;
            color: var(--challenge-pro-title) !important;
            font-size: 0.74rem !important;
            font-weight: 700 !important;
        }

        html body #learning-games-page #tour-search input::placeholder {
            color: var(--challenge-pro-muted) !important;
            font-weight: 400 !important;
            opacity: 1;
        }

        html body #learning-games-page .learning-mobile-control-row {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: stretch !important;
            gap: 7px !important;
            margin: 0 !important;
        }

        html body #learning-games-page .learning-category-select-wrap {
            min-width: 0 !important;
            margin: 0 !important;
        }

        html body #learning-games-page .learning-category-select,
        html body #learning-games-page #btn-skill-tree {
            min-height: 40px !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 8px !important;
            background: var(--challenge-pro-field) !important;
            color: var(--challenge-pro-title) !important;
            box-shadow: var(--challenge-pro-shadow) !important;
            font-size: 0.72rem !important;
            font-weight: 800 !important;
        }

        html body #learning-games-page .learning-category-select {
            width: 100% !important;
            height: 40px !important;
            padding: 8px 28px 8px 10px !important;
            text-overflow: ellipsis;
        }

        html body #learning-games-page #btn-skill-tree {
            width: auto !important;
            min-width: 92px !important;
            padding: 8px 10px !important;
            white-space: nowrap !important;
        }

        html body #learning-games-page #btn-skill-tree i {
            color: var(--challenge-pro-success) !important;
        }

        html body #learning-games-page #nav-pills-container {
            display: none !important;
        }

        html body #learning-games-page #dashboard-stats {
            --bs-gutter-x: 0;
            --bs-gutter-y: 0;
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin: 0 0 10px !important;
        }

        html body #learning-games-page #dashboard-stats > [class*="col-"] {
            width: auto !important;
            flex: 0 0 auto !important;
            max-width: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        html body #learning-games-page .ll-stat-card {
            min-height: 78px !important;
            padding: 9px !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 8px !important;
            background: var(--challenge-pro-card) !important;
            box-shadow: var(--challenge-pro-shadow) !important;
            color: var(--challenge-pro-title) !important;
            overflow: hidden !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card.d-flex {
            display: grid !important;
            grid-template-columns: 32px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card [style*="width:55px"] {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            border-radius: 8px !important;
            font-size: 0.86rem !important;
            flex-basis: 32px !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-val {
            color: var(--challenge-pro-title) !important;
            font-size: 0.92rem !important;
            line-height: 1.05 !important;
            margin: 0 0 2px !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-val span,
        html body #learning-games-page #dashboard-stats .ll-stat-card [style*="color:var(--tx3)"] {
            color: var(--challenge-pro-muted) !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card [style*="text-transform:uppercase"] {
            font-size: 0.52rem !important;
            line-height: 1.12 !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card .d-flex.justify-content-between {
            gap: 5px !important;
            margin-bottom: 6px !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card .d-flex.justify-content-between span:first-child {
            min-width: 0;
            color: var(--challenge-pro-title) !important;
            font-size: 0.7rem !important;
            line-height: 1.12 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card .d-flex.justify-content-between span:last-child {
            flex: 0 0 auto;
            color: var(--challenge-pro-accent) !important;
            background: var(--challenge-pro-soft) !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 6px !important;
            padding: 3px 6px !important;
            font-size: 0.52rem !important;
        }

        html body #learning-games-page #dashboard-stats .ll-progress-bar {
            height: 8px !important;
            margin: 5px 0 4px !important;
            background: var(--challenge-pro-soft) !important;
        }

        html body #learning-games-page #dashboard-stats .ll-progress-bar + div {
            color: var(--challenge-pro-muted) !important;
            font-size: 0.54rem !important;
            line-height: 1.1 !important;
        }

        html body #learning-games-page .journey-header {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: center !important;
            gap: 8px !important;
            margin: 0 0 9px !important;
        }

        html body #learning-games-page .journey-title {
            color: var(--challenge-pro-title) !important;
            font-size: 0.9rem !important;
            font-weight: 900 !important;
            line-height: 1.1 !important;
            margin: 0 !important;
        }

        html body #learning-games-page .journey-lives {
            min-height: 28px;
            padding: 5px 8px !important;
            border: 1px solid rgba(239, 68, 68, 0.22) !important;
            border-radius: 8px !important;
            background: rgba(239, 68, 68, 0.08) !important;
            color: var(--challenge-pro-danger) !important;
            font-size: 0.6rem !important;
            font-weight: 900 !important;
            white-space: nowrap;
        }

        html body #learning-games-page .level-path-container {
            padding: 6px 0 0 !important;
            margin: 0 !important;
        }

        html body #learning-games-page .level-path-line {
            left: 16px !important;
            width: 3px !important;
            border-radius: 999px !important;
            background: var(--challenge-pro-border) !important;
        }

        html body #learning-games-page .level-path-line-progress {
            background: linear-gradient(180deg, var(--challenge-pro-accent), var(--challenge-pro-accent-2)) !important;
        }

        html body #learning-games-page .level-node {
            display: flex !important;
            align-items: flex-start !important;
            gap: 0 !important;
            margin-bottom: 10px !important;
        }

        html body #learning-games-page .level-icon-wrapper {
            width: 34px !important;
            flex: 0 0 34px !important;
        }

        html body #learning-games-page .level-icon {
            width: 30px !important;
            height: 30px !important;
            border-width: 3px !important;
            border-radius: 8px !important;
            font-size: 0.78rem !important;
            box-shadow: none !important;
        }

        html body #learning-games-page .level-node.completed .level-icon {
            background: rgba(16, 185, 129, 0.14) !important;
            border-color: rgba(16, 185, 129, 0.42) !important;
            color: var(--challenge-pro-success) !important;
        }

        html body #learning-games-page .level-node.active .level-icon {
            background: rgba(37, 99, 235, 0.14) !important;
            border-color: rgba(37, 99, 235, 0.42) !important;
            color: var(--challenge-pro-accent) !important;
        }

        html body #learning-games-page .level-node.locked .level-icon {
            background: var(--challenge-pro-soft) !important;
            border-color: var(--challenge-pro-border) !important;
            color: var(--challenge-pro-muted) !important;
        }

        html body #learning-games-page .level-card {
            min-width: 0 !important;
            width: calc(100% - 42px) !important;
            margin-left: 8px !important;
            padding: 10px !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 8px !important;
            background: var(--challenge-pro-card) !important;
            box-shadow: var(--challenge-pro-shadow) !important;
            color: var(--challenge-pro-title) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        html body #learning-games-page .level-node.locked .level-card {
            min-height: 0 !important;
            display: block !important;
            position: relative !important;
            padding-right: 10px !important;
            opacity: 1 !important;
            background: var(--challenge-pro-field) !important;
        }

        html body #learning-games-page .level-node.locked .level-card > .locked-card-main > .d-flex:first-child {
            padding-right: 54px !important;
        }

        html body #learning-games-page .locked-card-art {
            width: 48px !important;
            height: 48px !important;
            top: 10px !important;
            right: 10px !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 8px !important;
            background: var(--challenge-pro-soft) !important;
            color: var(--challenge-pro-muted) !important;
            font-size: 1.35rem !important;
        }

        html body #learning-games-page .locked-status-pill {
            width: fit-content !important;
            margin: 6px 0 8px !important;
            padding: 4px 7px !important;
            gap: 5px !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 6px !important;
            background: var(--challenge-pro-soft) !important;
            color: var(--challenge-pro-muted) !important;
            font-size: 0.58rem !important;
            font-weight: 900 !important;
        }

        html body #learning-games-page .level-card > .d-flex:first-child {
            align-items: flex-start !important;
            gap: 7px !important;
            margin-bottom: 8px !important;
        }

        html body #learning-games-page .level-card h5 {
            color: var(--challenge-pro-title) !important;
            font-size: 0.82rem !important;
            font-weight: 900 !important;
            line-height: 1.16 !important;
            margin: 0 !important;
        }

        html body #learning-games-page .level-card p {
            color: var(--challenge-pro-muted) !important;
            font-size: 0.68rem !important;
            line-height: 1.36 !important;
            margin-bottom: 8px !important;
        }

        html body #learning-games-page .level-card [style*="font-size:0.75rem"] {
            color: var(--challenge-pro-accent) !important;
            font-size: 0.58rem !important;
            line-height: 1.12 !important;
        }

        html body #learning-games-page .level-node.completed .level-card [style*="font-size:0.75rem"] {
            color: var(--challenge-pro-success) !important;
        }

        html body #learning-games-page .level-node.locked .level-card [style*="font-size:0.75rem"] {
            color: var(--challenge-pro-muted) !important;
        }

        html body #learning-games-page .level-card [style*="background:rgba(59,130,246,0.07)"] {
            margin: 8px 0 9px !important;
            padding: 9px !important;
            border: 1px solid rgba(37, 99, 235, 0.18) !important;
            border-radius: 8px !important;
            background: rgba(37, 99, 235, 0.07) !important;
        }

        html body #learning-games-page .level-card [style*="font-size:0.78rem"],
        html body #learning-games-page .level-card [style*="font-size:0.84rem"] {
            font-size: 0.66rem !important;
            line-height: 1.3 !important;
        }

        html body #learning-games-page .level-card [style*="color:var(--tx2)"] {
            color: var(--challenge-pro-title) !important;
        }

        html body #learning-games-page .level-card [style*="color:var(--tx3)"] {
            color: var(--challenge-pro-muted) !important;
        }

        html body #learning-games-page .level-card .d-flex.flex-wrap.gap-2 {
            gap: 6px !important;
            margin-bottom: 9px !important;
        }

        html body #learning-games-page .score-badge,
        html body #learning-games-page .requirement-badge,
        html body #learning-games-page .level-card .badge {
            min-height: 24px !important;
            max-width: 100% !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
            padding: 5px 7px !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 6px !important;
            background: var(--challenge-pro-soft) !important;
            color: var(--challenge-pro-title) !important;
            font-size: 0.58rem !important;
            font-weight: 900 !important;
            line-height: 1.12 !important;
            white-space: normal !important;
            text-align: left !important;
        }

        html body #learning-games-page .score-badge {
            border-color: rgba(16, 185, 129, 0.28) !important;
            color: var(--challenge-pro-success) !important;
            background: rgba(16, 185, 129, 0.1) !important;
        }

        html body #learning-games-page .requirement-badge {
            border-color: rgba(245, 158, 11, 0.28) !important;
            color: var(--challenge-pro-warn) !important;
            background: rgba(245, 158, 11, 0.1) !important;
        }

        html body #learning-games-page .active-challenge-panel {
            padding: 9px !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 8px !important;
            background: var(--challenge-pro-field) !important;
            box-shadow: none !important;
            margin-bottom: 10px !important;
        }

        html body #learning-games-page .active-challenge-panel [style*="Contains"] {
            color: var(--challenge-pro-title) !important;
            margin-bottom: 8px !important;
            font-size: 0.68rem !important;
            line-height: 1.25 !important;
        }

        html body #learning-games-page .active-challenge-panel [style*="Success checklist"] {
            color: var(--challenge-pro-muted) !important;
            margin-bottom: 6px !important;
            font-size: 0.62rem !important;
        }

        html body #learning-games-page .active-challenge-panel [style*="fa-check"] {
            gap: 5px !important;
            margin-bottom: 5px !important;
            color: var(--challenge-pro-title) !important;
            font-size: 0.62rem !important;
            line-height: 1.28 !important;
        }

        html body #learning-games-page .active-challenge-panel [style*="Best attempt"],
        html body #learning-games-page .active-challenge-panel [style*="Cost"] {
            color: var(--challenge-pro-muted) !important;
            font-size: 0.62rem !important;
            line-height: 1.25 !important;
        }

        html body #learning-games-page .start-challenge-btn,
        html body #learning-games-page .level-card .btn {
            min-height: 36px !important;
            max-width: 100% !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-size: 0.72rem !important;
            font-weight: 900 !important;
            line-height: 1.15 !important;
            white-space: normal !important;
        }

        html body #learning-games-page .start-challenge-btn {
            width: 100% !important;
            background: linear-gradient(135deg, var(--challenge-pro-accent), var(--challenge-pro-accent-2)) !important;
            color: #ffffff !important;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.24) !important;
        }

        html body #learning-games-page .learning-notice {
            display: grid !important;
            grid-template-columns: 26px minmax(0, 1fr) !important;
            gap: 8px !important;
            padding: 9px !important;
            border-radius: 8px !important;
            margin-bottom: 10px !important;
            box-shadow: var(--challenge-pro-shadow) !important;
        }

        html body #learning-games-page .learning-notice-icon {
            width: 26px !important;
            height: 26px !important;
            border-radius: 8px !important;
        }

        html body #learning-games-page .learning-notice-message {
            font-size: 0.68rem !important;
            line-height: 1.3 !important;
        }

        html body #learning-games-page .text-info {
            color: #0284c7 !important;
        }

        html body #learning-games-page .text-success {
            color: #059669 !important;
        }

        html body #learning-games-page .text-danger {
            color: #dc2626 !important;
        }

        html body #learning-games-page .text-warning {
            color: #d97706 !important;
        }

        html[data-theme="dark"] body #learning-games-page .text-info,
        :root:not(.lm) body #learning-games-page .text-info,
        body.dm #learning-games-page .text-info,
        .dm #learning-games-page .text-info {
            color: #67e8f9 !important;
        }

        html[data-theme="dark"] body #learning-games-page .text-success,
        :root:not(.lm) body #learning-games-page .text-success,
        body.dm #learning-games-page .text-success,
        .dm #learning-games-page .text-success {
            color: #86efac !important;
        }

        html[data-theme="dark"] body #learning-games-page .text-danger,
        :root:not(.lm) body #learning-games-page .text-danger,
        body.dm #learning-games-page .text-danger,
        .dm #learning-games-page .text-danger {
            color: #fca5a5 !important;
        }

        html[data-theme="dark"] body #learning-games-page .text-warning,
        :root:not(.lm) body #learning-games-page .text-warning,
        body.dm #learning-games-page .text-warning,
        .dm #learning-games-page .text-warning {
            color: #fbbf24 !important;
        }

        html body #learning-games-page .text-center.py-5 {
            padding: 22px 12px !important;
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 8px !important;
            background: var(--challenge-pro-card) !important;
            box-shadow: var(--challenge-pro-shadow) !important;
        }

        html body #learning-games-page .text-center.py-5 h5 {
            color: var(--challenge-pro-muted) !important;
            font-size: 0.82rem !important;
        }

        html body .game-result-modal {
            --challenge-pro-card: rgba(255, 255, 255, 0.98);
            --challenge-pro-field: rgba(255, 255, 255, 0.96);
            --challenge-pro-border: rgba(15, 23, 42, 0.1);
            --challenge-pro-title: #0f172a;
            --challenge-pro-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
        }

        html[data-theme="dark"] body .game-result-modal,
        :root:not(.lm) body .game-result-modal,
        body.dm .game-result-modal,
        .dm .game-result-modal {
            --challenge-pro-card: rgba(15, 23, 42, 0.94);
            --challenge-pro-field: rgba(30, 41, 59, 0.9);
            --challenge-pro-border: rgba(148, 163, 184, 0.24);
            --challenge-pro-title: #f8fafc;
            --challenge-pro-shadow: 0 1px 0 rgba(148, 163, 184, 0.08), 0 18px 36px rgba(0, 0, 0, 0.26);
        }

        html body .game-result-modal .modal-dialog {
            margin: 10px !important;
        }

        html body .game-result-modal .modal-content {
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 8px !important;
            background: var(--challenge-pro-card) !important;
            color: var(--challenge-pro-title) !important;
            box-shadow: var(--challenge-pro-shadow) !important;
        }

        html body .game-result-modal .game-result-hero {
            padding: 12px !important;
        }

        html body .game-result-modal .game-result-score {
            width: 72px !important;
            height: 72px !important;
        }

        html body .game-result-modal .game-result-score-inner {
            width: 52px !important;
            height: 52px !important;
        }

        html body .game-result-modal .game-result-stat,
        html body .game-result-modal .game-result-breakdown-card {
            border: 1px solid var(--challenge-pro-border) !important;
            border-radius: 8px !important;
            background: var(--challenge-pro-field) !important;
        }
    }

    @media (max-width: 390px) {
        html body #learning-games-page .sr-learning-hero.sr-page-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            padding: 8px 66px 8px 10px !important;
        }

        html body #learning-games-page .learning-hero-icon {
            width: 26px !important;
            height: 26px !important;
            min-width: 26px !important;
            font-size: 0.7rem !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-subtitle {
            max-width: 10.7rem !important;
            font-size: 0.46rem !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-art {
            width: 66px !important;
            right: -6px !important;
        }

        html body #learning-games-page .level-node.locked .level-card > .locked-card-main > .d-flex:first-child {
            padding-right: 48px !important;
        }

        html body #learning-games-page .locked-card-art {
            width: 42px !important;
            height: 42px !important;
            font-size: 1.12rem !important;
        }
    }

    @media (max-width: 360px) {
        html body #learning-games-page .learning-mobile-control-row {
            grid-template-columns: 1fr !important;
        }

        html body #learning-games-page #btn-skill-tree {
            width: 100% !important;
        }

        html body #learning-games-page #dashboard-stats {
            gap: 7px !important;
        }

        html body #learning-games-page .ll-stat-card {
            min-height: 74px !important;
            padding: 8px !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card .d-flex.justify-content-between span:first-child {
            font-size: 0.64rem !important;
        }
    }

    @media (min-width: 992px) {
        html body #learning-games-page {
            --challenge-desktop-radius: 12px;
            --challenge-desktop-gap: 12px;
            --challenge-desktop-border: rgba(148, 163, 184, 0.2);
            --challenge-desktop-card-shadow: 0 10px 28px rgba(2, 6, 23, 0.12);
            width: 100% !important;
            max-width: 1480px !important;
            margin: 0 auto !important;
            padding: 0 0 24px !important;
        }

        html.lm body #learning-games-page {
            --challenge-desktop-border: rgba(15, 23, 42, 0.12);
            --challenge-desktop-card-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        }

        html body #learning-games-page .sr-learning-hero.sr-page-hero {
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
            border-radius: var(--challenge-desktop-radius) !important;
            overflow: hidden !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.12) !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-inner,
        html body #learning-games-page .sr-learning-hero .sr-page-hero-copy {
            display: flex !important;
            align-items: center !important;
            min-height: 0 !important;
            padding: 0 !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-copy {
            gap: 12px !important;
            max-width: 780px !important;
        }

        html body #learning-games-page .learning-hero-icon {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            flex: 0 0 44px !important;
            border-radius: 12px !important;
            font-size: 1.05rem !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-title {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin: 0 0 5px !important;
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a !important;
            font-size: clamp(1.12rem, 1.08vw, 1.45rem) !important;
            line-height: 1.12 !important;
            font-weight: 900 !important;
            text-transform: none !important;
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-title svg {
            display: none !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-subtitle {
            display: block !important;
            max-width: 640px !important;
            margin: 0 !important;
            color: var(--learning-hero-text-color) !important;
            -webkit-text-fill-color: var(--learning-hero-text-color) !important;
            font-size: 0.84rem !important;
            line-height: 1.42 !important;
            font-weight: 600 !important;
            overflow: visible !important;
        }

        html body #learning-games-page .sr-learning-hero .sr-page-hero-art {
            display: block !important;
            position: absolute !important;
            right: 12px !important;
            bottom: -10px !important;
            width: clamp(140px, 12vw, 174px) !important;
            max-width: none !important;
            opacity: 0.96 !important;
        }

        html body #learning-games-page .sr-page-actions.learning-actions {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: stretch !important;
            gap: var(--challenge-desktop-gap) !important;
            margin: 0 0 var(--challenge-desktop-gap) !important;
        }

        html body #learning-games-page #tour-search {
            display: grid !important;
            grid-template-columns: 18px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            width: 100% !important;
            max-width: none !important;
            min-height: 38px !important;
            padding: 8px 10px !important;
            border-radius: 10px !important;
            border-color: var(--challenge-desktop-border) !important;
            box-shadow: var(--challenge-desktop-card-shadow) !important;
        }

        html body #learning-games-page #tour-search input {
            min-width: 0 !important;
            font-size: 0.78rem !important;
        }

        html body #learning-games-page .learning-mobile-control-row {
            display: grid !important;
            grid-template-columns: 260px auto !important;
            align-items: stretch !important;
            gap: 8px !important;
        }

        html body #learning-games-page .learning-category-select-wrap {
            min-width: 0 !important;
            margin: 0 !important;
        }

        html body #learning-games-page .learning-category-select,
        html body #learning-games-page #btn-skill-tree {
            min-height: 38px !important;
            border-radius: 10px !important;
            border-color: var(--challenge-desktop-border) !important;
            font-size: 0.74rem !important;
            font-weight: 850 !important;
            box-shadow: var(--challenge-desktop-card-shadow) !important;
        }

        html body #learning-games-page .learning-category-select {
            width: 100% !important;
            height: 38px !important;
            padding: 7px 30px 7px 10px !important;
        }

        html body #learning-games-page #btn-skill-tree {
            min-width: 112px !important;
            padding: 7px 10px !important;
            white-space: nowrap !important;
        }

        html body #learning-games-page #nav-pills-container {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: flex-start !important;
            gap: 6px !important;
            margin: 0 0 var(--challenge-desktop-gap) !important;
            padding: 0 !important;
        }

        html body #learning-games-page .ll-nav-pill {
            min-height: 32px !important;
            padding: 7px 12px !important;
            border-radius: 9px !important;
            font-size: 0.7rem !important;
            line-height: 1 !important;
            white-space: nowrap !important;
        }

        html body #learning-games-page #dashboard-stats {
            --bs-gutter-x: var(--challenge-desktop-gap) !important;
            --bs-gutter-y: var(--challenge-desktop-gap) !important;
            margin: 0 0 var(--challenge-desktop-gap) !important;
        }

        html body #learning-games-page #dashboard-stats > [class*="col-"] {
            padding-left: calc(var(--challenge-desktop-gap) * 0.5) !important;
            padding-right: calc(var(--challenge-desktop-gap) * 0.5) !important;
        }

        html body #learning-games-page .ll-stat-card {
            min-height: 86px !important;
            padding: 10px !important;
            border-radius: var(--challenge-desktop-radius) !important;
            border-color: var(--challenge-desktop-border) !important;
            box-shadow: var(--challenge-desktop-card-shadow) !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card.d-flex {
            display: grid !important;
            grid-template-columns: 38px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card [style*="width:55px"] {
            width: 36px !important;
            height: 36px !important;
            min-width: 36px !important;
            flex-basis: 36px !important;
            border-radius: 10px !important;
            font-size: 0.95rem !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-val {
            font-size: 1.02rem !important;
            line-height: 1.05 !important;
            margin: 0 0 2px !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-val span {
            font-size: 0.68rem !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card [style*="text-transform:uppercase"] {
            font-size: 0.56rem !important;
            line-height: 1.12 !important;
        }

        html body #learning-games-page #dashboard-stats .ll-progress-bar {
            height: 8px !important;
            margin: 5px 0 4px !important;
        }

        html body #learning-games-page .journey-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 10px !important;
            margin: 0 0 8px !important;
        }

        html body #learning-games-page .journey-title {
            margin: 0 !important;
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a !important;
            font-size: 0.94rem !important;
            line-height: 1.18 !important;
            font-weight: 900 !important;
        }

        html body #learning-games-page .journey-header-actions {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: flex-end !important;
            gap: 8px !important;
            min-width: 0 !important;
        }

        html body #learning-games-page .journey-header-actions #btn-skill-tree {
            flex: 0 0 auto !important;
            width: auto !important;
            min-width: 112px !important;
            max-width: 140px !important;
            min-height: 28px !important;
            height: 28px !important;
            padding: 5px 9px !important;
            margin: 0 !important;
            border: 1px solid rgba(16, 185, 129, 0.22) !important;
            border-radius: 8px !important;
            background: rgba(236, 253, 245, 0.88) !important;
            color: #047857 !important;
            -webkit-text-fill-color: #047857 !important;
            box-shadow: none !important;
            font-size: 0.64rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            white-space: nowrap !important;
        }

        html body #learning-games-page .journey-header-actions #btn-skill-tree i {
            color: #059669 !important;
            -webkit-text-fill-color: #059669 !important;
        }

        html:not(.lm) body #learning-games-page .journey-header-actions #btn-skill-tree,
        body.dm #learning-games-page .journey-header-actions #btn-skill-tree,
        .dm #learning-games-page .journey-header-actions #btn-skill-tree {
            border-color: rgba(52, 211, 153, 0.24) !important;
            background: rgba(6, 78, 59, 0.24) !important;
            color: #86efac !important;
            -webkit-text-fill-color: #86efac !important;
        }

        html:not(.lm) body #learning-games-page .journey-header-actions #btn-skill-tree i,
        body.dm #learning-games-page .journey-header-actions #btn-skill-tree i,
        .dm #learning-games-page .journey-header-actions #btn-skill-tree i {
            color: #86efac !important;
            -webkit-text-fill-color: #86efac !important;
        }

        html body #learning-games-page .journey-lives {
            min-height: 28px !important;
            padding: 6px 9px !important;
            border-radius: 8px !important;
            font-size: 0.66rem !important;
        }

        html body #learning-games-page .level-path-container {
            padding: 14px !important;
            border-radius: var(--challenge-desktop-radius) !important;
            border: 1px solid var(--challenge-desktop-border) !important;
            box-shadow: var(--challenge-desktop-card-shadow) !important;
        }

        html body #learning-games-page .level-node {
            margin-bottom: var(--challenge-desktop-gap) !important;
        }

        html body #learning-games-page .level-icon-wrapper {
            width: 54px !important;
        }

        html body #learning-games-page .level-icon {
            width: 42px !important;
            height: 42px !important;
            border-radius: 12px !important;
            font-size: 0.88rem !important;
        }

        html body #learning-games-page .level-card {
            min-height: 0 !important;
            padding: 12px !important;
            border-radius: var(--challenge-desktop-radius) !important;
            border-color: var(--challenge-desktop-border) !important;
            box-shadow: var(--challenge-desktop-card-shadow) !important;
        }

        html body #learning-games-page .level-card h5 {
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a !important;
            font-size: 0.9rem !important;
            line-height: 1.18 !important;
        }

        html body #learning-games-page .level-card p {
            font-size: 0.72rem !important;
            line-height: 1.34 !important;
            margin-bottom: 8px !important;
        }

        html body #learning-games-page .level-card .badge,
        html body #learning-games-page :is(.score-badge, .requirement-badge, .locked-status-pill) {
            min-height: 24px !important;
            padding: 5px 8px !important;
            border-radius: 8px !important;
            font-size: 0.6rem !important;
            line-height: 1 !important;
        }

        html body #learning-games-page .active-challenge-panel {
            margin-bottom: 10px !important;
            padding: 10px !important;
            border-radius: 10px !important;
        }

        html body #learning-games-page .active-challenge-panel :is(div, span) {
            font-size: 0.68rem !important;
            line-height: 1.3 !important;
        }

        html body #learning-games-page .start-challenge-btn,
        html body #learning-games-page .btn-success,
        html body #learning-games-page .btn-outline-secondary {
            min-height: 32px !important;
            padding: 7px 11px !important;
            border-radius: 9px !important;
            font-size: 0.68rem !important;
            line-height: 1 !important;
        }

        html body #learning-games-page .locked-card-art {
            width: 58px !important;
            height: 58px !important;
            border-radius: 14px !important;
            font-size: 1.35rem !important;
        }
    }

    @media (min-width: 992px) and (max-width: 1320px) {
        html body #learning-games-page .sr-page-actions.learning-actions {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        html body #learning-games-page .learning-mobile-control-row {
            grid-template-columns: minmax(0, 1fr) auto !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card.d-flex {
            grid-template-columns: 34px minmax(0, 1fr) !important;
        }

        html body #learning-games-page #dashboard-stats .ll-stat-card [style*="width:55px"] {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            flex-basis: 32px !important;
        }
    }

    @media (min-width: 992px) {
        html body #learning-games-page #tour-search {
            display: none !important;
        }

        html body #learning-games-page .sr-page-actions.learning-actions {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-bottom: var(--challenge-desktop-gap) !important;
        }

        html body #learning-games-page .learning-mobile-control-row {
            width: min(100%, 590px) !important;
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: stretch !important;
            justify-content: center !important;
            gap: 8px !important;
            margin: 0 !important;
        }

        html body #learning-games-page .learning-category-select-wrap {
            flex: 1 1 0 !important;
            min-width: 0 !important;
            width: auto !important;
            max-width: none !important;
            margin: 0 !important;
        }

        html body #learning-games-page .learning-category-select {
            width: 100% !important;
            min-width: 0 !important;
        }

        html body #learning-games-page #btn-skill-tree {
            flex: 0 0 150px !important;
            width: 150px !important;
            max-width: 150px !important;
            min-width: 150px !important;
            height: 38px !important;
            padding: 7px 10px !important;
            margin: 0 !important;
            justify-self: auto !important;
        }
    }

    @media (min-width: 992px) {
        html body #learning-games-page #modules-list.level-path-container {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            align-items: start !important;
            gap: var(--challenge-desktop-gap) !important;
            padding: 12px !important;
            margin: 0 !important;
            overflow: visible !important;
        }

        html body #learning-games-page #modules-list > .level-path-line {
            display: none !important;
        }

        html body #learning-games-page #modules-list > .learning-notice,
        html body #learning-games-page #modules-list > .text-center.py-5 {
            grid-column: 1 / -1 !important;
        }

        html body #learning-games-page #modules-list > .level-node {
            display: block !important;
            position: relative !important;
            min-width: 0 !important;
            height: auto !important;
            margin: 0 !important;
        }

        html body #learning-games-page #modules-list .level-icon-wrapper {
            position: absolute !important;
            top: 12px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            z-index: 2 !important;
            width: 40px !important;
            min-width: 40px !important;
            flex: 0 0 40px !important;
            justify-content: center !important;
            padding-top: 0 !important;
        }

        html body #learning-games-page #modules-list .level-icon {
            width: 36px !important;
            height: 36px !important;
            border-width: 3px !important;
            border-radius: 10px !important;
            font-size: 0.78rem !important;
            box-shadow: none !important;
        }

        html body #learning-games-page #modules-list .level-card {
            width: 100% !important;
            min-width: 0 !important;
            height: auto !important;
            margin: 0 !important;
            padding: 54px 10px 10px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0 !important;
            text-align: center !important;
        }

        html body #learning-games-page #modules-list .level-node.locked .level-card {
            min-height: 0 !important;
            display: flex !important;
            grid-template-columns: none !important;
            padding-right: 11px !important;
            position: relative !important;
        }

        html body #learning-games-page #modules-list .level-card > div:first-child {
            width: 100% !important;
        }

        html body #learning-games-page #modules-list .level-card > .locked-card-main {
            min-width: 0 !important;
        }

        html body #learning-games-page #modules-list .level-node.locked .level-card > .locked-card-main > .d-flex:first-child {
            padding-right: 0 !important;
        }

        html body #learning-games-page #modules-list .level-card > div:first-child > .d-flex:first-child {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 6px !important;
            margin-bottom: 8px !important;
            text-align: center !important;
        }

        html body #learning-games-page #modules-list .level-card > .d-flex:first-child {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-start !important;
            gap: 6px !important;
            margin-bottom: 8px !important;
            text-align: center !important;
        }

        html body #learning-games-page #modules-list .level-card > .d-flex:first-child > div:first-child {
            min-width: 0 !important;
        }

        html body #learning-games-page #modules-list .level-card > div:first-child > .d-flex:first-child > div:first-child {
            min-width: 0 !important;
        }

        html body #learning-games-page #modules-list .level-card > div:first-child > .d-flex:first-child [style*="font-size:0.75rem"] {
            margin-bottom: 2px !important;
        }

        html body #learning-games-page #modules-list :is(.requirement-badge, .score-badge) {
            align-self: center !important;
            margin: 0 !important;
        }

        html body #learning-games-page #modules-list .locked-card-art {
            position: absolute !important;
            top: 12px !important;
            right: 11px !important;
            width: 34px !important;
            height: 34px !important;
            border-radius: 10px !important;
            font-size: 0.86rem !important;
        }

        html body #learning-games-page #modules-list .level-card h5 {
            font-size: 0.82rem !important;
            line-height: 1.18 !important;
            max-width: 100% !important;
            margin: 0 !important;
        }

        html body #learning-games-page #modules-list .level-card p {
            font-size: 0.66rem !important;
            line-height: 1.32 !important;
            text-align: center !important;
            max-width: 38ch !important;
            margin: 0 auto 8px !important;
        }

        html body #learning-games-page #modules-list .level-card [style*="background:rgba(59,130,246,0.07)"] {
            margin: 0 0 7px !important;
            padding: 7px 8px !important;
            border-radius: 8px !important;
            text-align: center !important;
        }

        html body #learning-games-page #modules-list .level-card [style*="font-size:0.78rem"] {
            font-size: 0.6rem !important;
            line-height: 1.12 !important;
            margin-bottom: 3px !important;
        }

        html body #learning-games-page #modules-list .level-card [style*="font-size:0.84rem"] {
            font-size: 0.66rem !important;
            line-height: 1.26 !important;
            max-width: 36ch !important;
            margin: 0 auto !important;
        }

        html body #learning-games-page #modules-list .level-card .d-flex.flex-wrap.gap-2 {
            justify-content: center !important;
            gap: 6px !important;
            margin-bottom: 9px !important;
        }

        html body #learning-games-page #modules-list .active-challenge-panel {
            padding: 8px !important;
            margin-bottom: 8px !important;
            text-align: left !important;
        }

        html body #learning-games-page #modules-list .active-challenge-panel [style*="Contains"] {
            margin-bottom: 7px !important;
        }

        html body #learning-games-page #modules-list .level-card form,
        html body #learning-games-page #modules-list .level-card [style*="margin-top:15px"] {
            margin-top: auto !important;
        }
    }

    @media (max-width: 767px) {
        html body #learning-games-page .sr-page-actions.learning-actions {
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            margin: 0 0 10px !important;
            padding: 0 !important;
        }

        html body #learning-games-page .learning-mobile-control-row,
        html body #learning-games-page .learning-mobile-control-row .learning-category-select-wrap,
        html body #learning-games-page .learning-mobile-control-row .learning-category-select {
            display: block !important;
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        html body #learning-games-page .learning-mobile-control-row {
            margin: 0 0 10px !important;
        }

        html body #learning-games-page .learning-mobile-control-row .learning-category-select-wrap {
            margin: 0 !important;
        }

        html body #learning-games-page .journey-header-actions {
            display: grid !important;
            grid-template-columns: repeat(2, 104px) !important;
            align-items: center !important;
            justify-content: end !important;
            gap: 6px !important;
            min-width: 0 !important;
        }

        html body #learning-games-page .journey-header-actions #btn-skill-tree,
        html body #learning-games-page .journey-header-actions .journey-lives {
            width: 104px !important;
            min-width: 104px !important;
            max-width: 104px !important;
            height: 28px !important;
            min-height: 28px !important;
            padding: 5px 7px !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 4px !important;
            font-size: 0.58rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            white-space: nowrap !important;
            box-shadow: none !important;
        }

        html body #learning-games-page .journey-header-actions #btn-skill-tree i,
        html body #learning-games-page .journey-header-actions .journey-lives i {
            margin-right: 3px !important;
            font-size: 0.58rem !important;
        }
    }
</style>
@php
    $gameResult = session('game_result');
@endphp

<div class="db-section active" id="learning-games-page">
    <!-- Header & Navigation -->
    <div class="sr-page-hero sr-learning-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="learning-hero-icon"><i class="fa-solid fa-gamepad"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 15h10l2 3a2 2 0 0 0 3-2l-1-5a6 6 0 0 0-6-5H9a6 6 0 0 0-6 5l-1 5a2 2 0 0 0 3 2l2-3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 11h4M10 9v4M16 10h.01M18 13h.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Philippines Interview Challenges
                    </h4>
                    <p class="sr-page-hero-subtitle">Complete interview challenges, earn XP, and build practical answer skills.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="gamesPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="gamesBlue" x1="58" y1="40" x2="166" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#gamesPanel)" stroke="#BFDBFE" stroke-width="3"/><path d="M67 84c5-26 18-36 43-36s38 10 43 36l4 22c2 12-11 18-18 8l-10-14H91l-10 14c-7 10-20 4-18-8l4-22Z" fill="url(#gamesBlue)"/><path d="M82 80h23M94 69v23M132 74h.01M146 88h.01" stroke="#EFF6FF" stroke-width="7" stroke-linecap="round"/><circle cx="164" cy="43" r="17" fill="#F59E0B"/><path d="m164 33 3 7 8 1-6 5 2 8-7-4-7 4 2-8-6-5 8-1 3-7Z" fill="#fff"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="sr-page-actions learning-actions">
        <div id="tour-search" class="db-top-search" style="width:100%; max-width:300px; background:var(--bg3);border:1px solid var(--bd); margin:0; border-radius:12px; padding:10px 16px;">
            <i class="fa-solid fa-magnifying-glass" style="color:var(--tx3)"></i>
            <input type="text" placeholder="Search challenges, skills, scenarios..." style="width:100%; background:transparent; border:none; color:var(--tx); outline:none;">
        </div>
        <div class="learning-mobile-control-row">
            <div class="learning-category-select-wrap">
                <select id="learningCategorySelect" class="learning-category-select" aria-label="Select challenge path">
                    @foreach($categories as $category)
                        <option value="{{ route('user.learning', ['category_id' => $category->id]) }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div id="nav-pills-container" class="mb-4 pb-2 d-flex flex-wrap gap-2">
        @foreach($categories as $category)
            <a href="{{ route('user.learning', ['category_id' => $category->id]) }}" class="ll-nav-pill {{ request('category_id') == $category->id ? 'active' : '' }}" style="margin:0;"><i class="fa-solid fa-folder"></i> {{ $category->title }}</a>
        @endforeach
    </div>

    <!-- Gamified HUD Stats -->
    <div id="dashboard-stats" class="row g-4 mb-4">
        <!-- Player Level & XP -->
        <div class="col-12 col-sm-6 col-lg-3 animate-fade-up" style="animation-delay: 0.1s">
            <div class="ll-stat-card" style="display:flex; flex-direction:column; justify-content:center; height:100%;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-weight:800; color:var(--tx); font-size:1.1rem;"><i class="fa-solid fa-crown text-warning me-2"></i> LEVEL {{ $profile?->player_level ?? 1 }}</span>
                    <span style="font-size:0.75rem; color:var(--tx3); font-weight:700; background:var(--bg3); padding:3px 8px; border-radius:6px;">{{ ($profile?->player_level ?? 1) >= 5 ? 'GOLD' : (($profile?->player_level ?? 1) >= 3 ? 'SILVER' : 'BRONZE') }}</span>
                </div>
                <div class="ll-progress-bar" style="height:12px; background:var(--bd); border-radius:6px; margin:5px 0;">
                    @php 
                        $xp = $profile?->experience_points ?? 0;
                        $nextLevelXp = ($profile?->player_level ?? 1) * 1000;
                        $percent = min(100, ($xp / $nextLevelXp) * 100);
                    @endphp
                    <div class="ll-progress-fill" style="width:{{ $percent }}%; background:linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);"></div>
                </div>
                <div style="font-size:0.75rem; color:var(--tx3); font-weight:700; text-align:right;">{{ number_format($xp) }} / {{ number_format($nextLevelXp) }} XP</div>
            </div>
        </div>
        
        <!-- Energy/Lives -->
        @php
            $maxEnergy = \App\Models\Profile::MAX_ENERGY;
            $currentEnergy = $profile?->energy ?? $maxEnergy;
        @endphp
        <div class="col-12 col-sm-6 col-lg-3 animate-fade-up" style="animation-delay: 0.2s">
            <div class="ll-stat-card d-flex align-items-center gap-3" style="height:100%;">
                <div style="width:55px; height:55px; border-radius:15px; background:rgba(239,68,68,0.1); color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                    <i class="fa-solid fa-heart"></i>
                </div>
                <div style="text-align:left;">
                    <div class="ll-stat-val" style="font-size:1.5rem; margin:0; font-weight:800;">{{ $currentEnergy }} <span style="font-size:1rem; color:var(--tx3);">/ {{ $maxEnergy }}</span></div>
                    <div style="font-size:0.8rem; color:var(--tx3); font-weight:700; text-transform:uppercase">Energy</div>
                </div>
            </div>
        </div>

        <!-- Streak -->
        <div class="col-12 col-sm-6 col-lg-3 animate-fade-up" style="animation-delay: 0.3s">
            <div class="ll-stat-card d-flex align-items-center gap-3" style="height:100%;">
                <div style="width:55px; height:55px; border-radius:15px; background:rgba(245,158,11,0.1); color:#f59e0b; display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                    <i class="fa-solid fa-fire"></i>
                </div>
                <div style="text-align:left;">
                    <div class="ll-stat-val" style="font-size:1.5rem; margin:0; font-weight:800;">{{ $profile?->current_streak ?? 0 }} <span style="font-size:1rem; color:var(--tx3);">Days</span></div>
                    <div style="font-size:0.8rem; color:var(--tx3); font-weight:700; text-transform:uppercase">Combo Streak</div>
                </div>
            </div>
        </div>

        <!-- Score/Accuracy -->
        <div class="col-12 col-sm-6 col-lg-3 animate-fade-up" style="animation-delay: 0.4s">
            <div class="ll-stat-card d-flex align-items-center gap-3" style="height:100%;">
                <div style="width:55px; height:55px; border-radius:15px; background:rgba(52,211,153,0.1); color:#34d399; display:flex; align-items:center; justify-content:center; font-size:1.8rem;">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <div style="text-align:left;">
                    @php $avgScore = $gameProgress && $gameProgress->count() > 0 ? round($gameProgress->avg('best_score')) : 0; @endphp
                    <div class="ll-stat-val" style="font-size:1.5rem; margin:0; font-weight:800;">{{ $avgScore }}%</div>
                    <div style="font-size:0.8rem; color:var(--tx3); font-weight:700; text-transform:uppercase">Accuracy</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            
            <div class="journey-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="journey-title" style="margin:0">Challenge Journey</h5>
                <div class="journey-header-actions">
                    <a id="btn-skill-tree" href="{{ route('user.skills') }}" class="btn btn-sm journey-skill-tree-btn d-inline-flex align-items-center justify-content-center"><i class="fa-solid fa-tree me-1"></i> <span>Skill Tree</span></a>
                    <span class="badge journey-lives"><i class="fa-solid fa-heart me-1" style="color:#ef4444"></i> {{ $currentEnergy }} / {{ $maxEnergy }} Lives</span>
                </div>
            </div>

            <div class="level-path-container" id="modules-list">
                @if(session('error') && ! $gameResult)
                    <div class="learning-notice learning-notice-danger" role="alert">
                        <span class="learning-notice-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <span class="learning-notice-message">{{ session('error') }}</span>
                    </div>
                @endif
                @if(session('success') && ! $gameResult)
                    <div class="learning-notice learning-notice-success" role="status">
                        <span class="learning-notice-icon"><i class="fa-solid fa-circle-check"></i></span>
                        <span class="learning-notice-message">{{ session('success') }}</span>
                    </div>
                @endif
                
                <!-- Path Line -->
                <div class="level-path-line">
                    @php 
                        $completedCount = $gameProgress ? $gameProgress->where('status', 'completed')->count() : 0;
                        $totalLevels = $gameLevels ? $gameLevels->count() : 1;
                        $pathPercent = min(100, ($completedCount / max(1, $totalLevels)) * 100);
                    @endphp
                    <div class="level-path-line-progress" style="height: {{ $pathPercent }}%;"></div>
                </div>

                @if($gameLevels && $gameLevels->count() > 0)
                    @php $catPassed = []; @endphp
                    @foreach($gameLevels as $level)
                        @php
                            if (!isset($catPassed[$level->category_id])) {
                                $catPassed[$level->category_id] = true; // First level in any category is unlocked
                            }
                            
                            $prog = $gameProgress ? $gameProgress->get($level->id) : null;
                            $isCompleted = $prog && $prog->best_score >= $level->required_score;
                            
                            if ($isCompleted) {
                                $status = 'completed';
                                $catPassed[$level->category_id] = true; // Next level in this category will be unlocked
                            } else {
                                if ($catPassed[$level->category_id]) {
                                    $status = 'active';
                                    $catPassed[$level->category_id] = false; // Next ones in this category will be locked
                                } else {
                                    $status = 'locked';
                                }
                            }

                            // Explicit prerequisite overrides (if set)
                            if ($level->prerequisite_level_id && $status === 'active') {
                                $prereqProg = $gameProgress->get($level->prerequisite_level_id);
                                $prereqLevel = $gameLevels->where('id', $level->prerequisite_level_id)->first();
                                if (!$prereqProg || $prereqProg->best_score < ($prereqLevel ? $prereqLevel->required_score : 80)) {
                                    $status = 'locked';
                                    $catPassed[$level->category_id] = false;
                                }
                            }

                            if ($level->is_hidden && $status === 'locked') {
                                continue;
                            }
                            
                            $score = $prog ? $prog->best_score : 0;
                            $successChecklist = $level->guidance_checklist;
                            $lockedArtIcons = ['fa-lightbulb', 'fa-comment-dots', 'fa-chalkboard-user', 'fa-trophy'];
                            $lockedArtIcon = $lockedArtIcons[$loop->index % count($lockedArtIcons)];
                            
                            $nodeClass = '';
                            $iconHtml = '';
                            if($status === 'completed') {
                                $nodeClass = 'completed';
                                $iconHtml = '<i class="fa-solid fa-check"></i>';
                            } elseif ($status === 'active') {
                                $nodeClass = 'active';
                                $iconHtml = $level->level_number;
                            } else {
                                $nodeClass = 'locked';
                                $iconHtml = '<i class="fa-solid fa-lock"></i>';
                            }
                        @endphp

                        <div class="level-node {{ $nodeClass }} animate-fade-up" style="animation-delay: {{ $loop->index * 0.1 }}s">
                            <div class="level-icon-wrapper">
                                <div class="level-icon">{!! $iconHtml !!}</div>
                            </div>
                            <div class="level-card">
                                <div class="{{ $status === 'locked' ? 'locked-card-main' : '' }}">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <div style="font-size:0.75rem;color:{{ $status === 'completed' ? '#34d399' : ($status === 'active' ? 'var(--pur)' : 'var(--tx3)') }};font-weight:700;margin-bottom:5px;text-transform:uppercase">Level {{ $level->level_number }}</div>
                                        <h5 style="color:var(--tx);font-weight:700;margin:0">{{ $level->title }}</h5>
                                        @if($status === 'locked')
                                            <div class="locked-status-pill"><i class="fa-solid fa-lock"></i> Locked</div>
                                        @endif
                                    </div>
                                    @if($status === 'completed')
                                        <div class="score-badge"><i class="fa-solid fa-star"></i> Score: {{ $score }}%</div>
                                    @elseif($status === 'active')
                                        <div class="requirement-badge"><i class="fa-solid fa-bullseye"></i> Goal: {{ $level->required_score }}%+</div>
                                    @elseif($status !== 'locked')
                                        <div class="requirement-badge" style="background:var(--bg3);color:var(--tx3)"><i class="fa-solid fa-lock"></i> Locked</div>
                                    @endif
                                </div>
                                
                                <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:10px;line-height:1.5">{{ $level->description }}</p>

                                @if($level->skill_focus || $level->learning_objective)
                                    <div style="background:rgba(59,130,246,0.07);border:1px solid rgba(59,130,246,0.18);border-radius:10px;padding:12px;margin-bottom:12px;">
                                        @if($level->skill_focus)
                                            <div style="font-size:0.78rem;color:#38bdf8;font-weight:800;text-transform:uppercase;letter-spacing:0;margin-bottom:4px;"><i class="fa-solid fa-bullseye me-1"></i>{{ $level->skill_focus }}</div>
                                        @endif
                                        @if($level->learning_objective)
                                            <div style="font-size:0.84rem;color:var(--tx2);line-height:1.45;">{{ $level->learning_objective }}</div>
                                        @endif
                                    </div>
                                @endif
                                
                                @if($status === 'active' || $status === 'completed')
                                    <div class="d-flex flex-wrap gap-2 mb-{{ $status==='active' ? '20' : '0' }}px">
                                        @if($level->skill_focus)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-graduation-cap text-info me-1"></i> {{ $level->skill_focus }}</span>
                                        @endif
                                        @if($level->time_limit_seconds)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-clock text-danger me-1"></i> {{ $level->time_limit_seconds }}s</span>
                                        @endif
                                        @if($level->banned_words)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);" title="{{ $level->banned_words }}"><i class="fa-solid fa-ban text-danger me-1"></i> Banned Words</span>
                                        @endif
                                        @if($level->target_tone)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-face-smile text-success me-1"></i> {{ $level->target_tone }}</span>
                                        @endif
                                        @if($level->custom_badge_name)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-medal text-primary me-1"></i> {{ $level->custom_badge_name }}</span>
                                        @endif
                                        @if($level->skill_xp_amount > 0)
                                            <span class="badge border" style="background:var(--bg3); color:var(--tx);"><i class="fa-solid fa-bolt text-warning me-1"></i> +{{ $level->skill_xp_amount }} {{ $level->skill_xp_type }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($status === 'active')
                                    <div class="active-challenge-panel" style="background:var(--bg3);border-radius:10px;padding:15px;margin-bottom:20px;border:1px solid var(--bd)">
                                        <div style="font-size:0.85rem;color:var(--tx2);font-weight:600;margin-bottom:5px"><i class="fa-solid fa-list-check me-1 text-info"></i> Contains {{ count($level->parsed_questions) }} Questions</div>
                                        @if($successChecklist)
                                            <div style="margin-top:12px;">
                                                <div style="font-size:0.78rem;color:var(--tx3);font-weight:700;margin-bottom:6px;">Success checklist</div>
                                                @foreach($successChecklist as $criterion)
                                                    <div style="font-size:0.78rem;color:var(--tx2);line-height:1.4;margin-bottom:4px;"><i class="fa-solid fa-check text-success me-1"></i>{{ $criterion }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($score > 0 && ! $isCompleted)
                                            <div style="margin-top:12px;font-size:0.78rem;color:#f59e0b;font-weight:700;"><i class="fa-solid fa-arrow-trend-up me-1"></i> Best attempt: {{ $score }}%</div>
                                        @endif
                                        <div style="margin-top:10px; font-size:0.75rem; color:var(--tx3);"><i class="fa-solid fa-heart text-danger"></i> Cost: {{ $level->energy_cost }} Energy</div>
                                    </div>
                                    <form action="{{ route('user.game.start', $level->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-shine start-challenge-btn" style="background:var(--dash-primary, #60a5fa);color:#fff;border:none;box-shadow:0 4px 15px rgba(96,165,250,0.4);border-radius:12px;font-weight:600;padding:10px 25px"><i class="fa-solid fa-play me-2"></i> Start Challenge</button>
                                    </form>
                                @elseif($status === 'completed')
                                    <div style="margin-top:15px;">
                                        <button class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-weight:600"><i class="fa-solid fa-check text-success me-1"></i> Completed</button>
                                    </div>
                                @elseif($status === 'locked')
                                    @if($level->prerequisite_level_id)
                                        @php $prereq = $gameLevels->where('id', $level->prerequisite_level_id)->first(); @endphp
                                        @if($prereq)
                                            <div style="margin-top:15px;font-size:0.8rem;color:var(--tx2);font-weight:600;display:flex;align-items:center;gap:5px;">
                                                <i class="fa-solid fa-circle-info text-info"></i> Reach {{ $prereq->required_score }}% in Level {{ $prereq->level_number }} to unlock.
                                            </div>
                                        @endif
                                    @endif
                                @endif
                                </div>
                                @if($status === 'locked')
                                    <div class="locked-card-art" aria-hidden="true">
                                        <i class="fa-solid {{ $lockedArtIcon }}"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @php
                        $certificateLevelCount = $gameLevels->count();
                        $certificateUnlocked = $selectedCategory
                            && $certificateLevelCount > 0
                            && $gameLevels->every(function ($level) use ($gameProgress) {
                                $progress = $gameProgress ? $gameProgress->get($level->id) : null;

                                return $progress && (int) $progress->best_score >= (int) $level->required_score;
                            });
                    @endphp
                    <div class="level-node {{ $certificateUnlocked ? 'completed' : 'locked' }} animate-fade-up" style="animation-delay: {{ $gameLevels->count() * 0.1 }}s">
                        <div class="level-icon-wrapper">
                            <div class="level-icon">
                                @if($certificateUnlocked)
                                    <i class="fa-solid fa-medal"></i>
                                @else
                                    <i class="fa-solid fa-lock"></i>
                                @endif
                            </div>
                        </div>
                        <div class="level-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div style="font-size:0.75rem;color:{{ $certificateUnlocked ? '#34d399' : 'var(--tx3)' }};font-weight:700;margin-bottom:5px;text-transform:uppercase">Final Reward</div>
                                    <h5 style="color:var(--tx);font-weight:700;margin:0">Completion Certificate</h5>
                                </div>
                                @if($certificateUnlocked)
                                    <div class="score-badge"><i class="fa-solid fa-circle-check"></i> Unlocked</div>
                                @else
                                    <div class="requirement-badge" style="background:var(--bg3);color:var(--tx3)"><i class="fa-solid fa-lock"></i> Locked</div>
                                @endif
                            </div>
                            <p style="color:var(--tx3);font-size:0.9rem;margin-bottom:14px;line-height:1.5">
                                Complete every level in this challenge path to unlock your downloadable PDF certificate.
                            </p>
                            @if($certificateUnlocked)
                                <a href="{{ route('user.game.certificate.download', $selectedCategory->id) }}" class="btn btn-success" style="border-radius:12px;font-weight:700;padding:10px 18px;">
                                    <i class="fa-solid fa-file-pdf me-2"></i> Download Certificate
                                </a>
                            @else
                                <div style="margin-top:8px;font-size:0.8rem;color:var(--tx2);font-weight:600;display:flex;align-items:center;gap:6px;">
                                    <i class="fa-solid fa-flag-checkered text-info"></i> Unlocks after the final level.
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fa-solid fa-folder-open fa-3x mb-3" style="color:var(--bd)"></i>
                        <h5 style="color:var(--tx3)">No challenge levels loaded yet.</h5>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($gameResult)
    @php
        $resultPassed = ($gameResult['status'] ?? '') === 'passed';
        $scoreValue = max(0, min(100, (int) ($gameResult['score'] ?? 0)));
        $scoreColor = $resultPassed ? '#34d399' : '#f59e0b';
        $nextLevel = $gameResult['next_level'] ?? null;
        $certificate = $gameResult['certificate'] ?? null;
        $scorecard = $gameResult['ai_scorecard'] ?? data_get($gameResult, 'goal_breakdown.ai_feedback_scorecard', []);
        $scorecardMetrics = $scorecard['metrics'] ?? [];
        if (empty($scorecardMetrics) && ! empty($gameResult['goal_breakdown']['averages'])) {
            foreach ($gameResult['goal_breakdown']['averages'] as $label => $value) {
                $metricScore = max(0, min(100, (int) $value));
                $scorecardMetrics[$label] = [
                    'label' => \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)),
                    'score' => $metricScore,
                    'level' => $metricScore >= 85 ? 'Strong' : ($metricScore >= 70 ? 'Competent' : ($metricScore >= 50 ? 'Needs Work' : 'Limited')),
                    'feedback' => '',
                ];
            }
        }
        $metricOrder = ['clarity', 'relevance', 'confidence', 'grammar', 'professionalism', 'goal_coverage', 'star_method'];
        $orderedScorecardMetrics = [];
        foreach ($metricOrder as $metricKey) {
            if (array_key_exists($metricKey, $scorecardMetrics)) {
                $orderedScorecardMetrics[$metricKey] = $scorecardMetrics[$metricKey];
            }
        }
        foreach ($scorecardMetrics as $metricKey => $metricData) {
            if (! array_key_exists($metricKey, $orderedScorecardMetrics)) {
                $orderedScorecardMetrics[$metricKey] = $metricData;
            }
        }
        $metricIcons = [
            'clarity' => 'fa-lines-leaning',
            'relevance' => 'fa-bullseye',
            'confidence' => 'fa-microphone-lines',
            'grammar' => 'fa-spell-check',
            'professionalism' => 'fa-handshake-angle',
            'goal_coverage' => 'fa-list-check',
            'star_method' => 'fa-star',
        ];
        $scorecardReliability = isset($scorecard['reliability_score']) ? max(0, min(100, (int) $scorecard['reliability_score'])) : null;
        $questionFeedback = array_slice($scorecard['question_feedback'] ?? [], 0, 4);
    @endphp
    <div class="modal fade game-result-modal" id="gameResultModal" tabindex="-1" aria-labelledby="gameResultModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="game-result-hero">
                    <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                        <div class="game-result-score" style="background: conic-gradient({{ $scoreColor }} {{ $scoreValue }}%, var(--bg3) 0);">
                            <div class="game-result-score-inner">
                                <div style="font-size:1.7rem;font-weight:900;color:var(--tx);line-height:1;">{{ $scoreValue }}%</div>
                                <div style="font-size:0.72rem;font-weight:800;color:var(--tx3);text-transform:uppercase;">Score</div>
                            </div>
                        </div>
                        <div class="flex-grow-1 text-center text-md-start">
                            <span class="badge mb-2" style="background:{{ $resultPassed ? 'rgba(52,211,153,0.16);color:#10b981;border:1px solid rgba(16,185,129,0.35)' : 'rgba(245,158,11,0.14);color:#f59e0b;border:1px solid rgba(245,158,11,0.35)' }};padding:7px 11px;border-radius:999px;">
                                <i class="fa-solid {{ $resultPassed ? 'fa-circle-check' : 'fa-rotate-right' }} me-1"></i>{{ $resultPassed ? 'Passed' : 'Needs Retry' }}
                            </span>
                            <h4 id="gameResultModalTitle" style="font-weight:900;margin:0 0 6px;color:var(--tx);">
                                Level {{ $gameResult['level_number'] ?? '' }}: {{ $gameResult['level_title'] ?? 'Interview Challenge' }}
                            </h4>
                            <div style="color:var(--tx2);font-size:0.96rem;line-height:1.5;">
                                {{ $gameResult['message'] ?? ($resultPassed ? 'Level cleared.' : 'Try again to clear this level.') }}
                            </div>
                            @if(! $resultPassed && ! empty($gameResult['retry_hint']))
                                <div class="mt-2" style="color:#f59e0b;font-size:0.86rem;font-weight:700;">
                                    <i class="fa-solid fa-lightbulb me-1"></i>{{ $gameResult['retry_hint'] }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="game-result-stat">
                                <div class="game-result-stat-label">Goal</div>
                                <div class="game-result-stat-value">{{ $gameResult['required_score'] ?? 0 }}%+</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="game-result-stat">
                                <div class="game-result-stat-label">Best Score</div>
                                <div class="game-result-stat-value">{{ $gameResult['best_score'] ?? $scoreValue }}% @if(!empty($gameResult['is_new_best']))<span class="badge text-bg-success ms-1">New</span>@endif</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="game-result-stat">
                                <div class="game-result-stat-label">Energy</div>
                                <div class="game-result-stat-value">-{{ $gameResult['energy_spent'] ?? 0 }} <span style="font-size:0.82rem;color:var(--tx3);">left {{ $gameResult['energy_remaining'] ?? 0 }}</span></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="game-result-stat">
                                <div class="game-result-stat-label">Reward</div>
                                <div class="game-result-stat-value">+{{ $gameResult['xp_earned'] ?? 0 }} XP</div>
                            </div>
                        </div>
                    </div>

                    @if(! $resultPassed)
                        <div class="mb-4" style="border:1px solid rgba(245,158,11,0.28);background:rgba(245,158,11,0.08);border-radius:12px;padding:14px;color:var(--tx2);">
                            <strong style="color:#f59e0b;">{{ $gameResult['points_to_goal'] ?? 0 }} more point{{ (int)($gameResult['points_to_goal'] ?? 0) === 1 ? '' : 's' }} needed.</strong>
                            Retry starts a fresh attempt and costs {{ $gameResult['retry_energy_cost'] ?? 0 }} energy.
                        </div>
                    @elseif($nextLevel)
                        <div class="mb-4" style="border:1px solid rgba(52,211,153,0.28);background:rgba(52,211,153,0.08);border-radius:12px;padding:14px;color:var(--tx2);">
                            <strong style="color:#10b981;">Next level unlocked:</strong>
                            Level {{ $nextLevel['level_number'] ?? '' }} - {{ $nextLevel['title'] ?? 'Next Challenge' }}.
                            Starting it costs {{ $nextLevel['energy_cost'] ?? 0 }} energy.
                        </div>
                    @elseif($resultPassed)
                        <div class="mb-4" style="border:1px solid rgba(52,211,153,0.28);background:rgba(52,211,153,0.08);border-radius:12px;padding:14px;color:var(--tx2);">
                            <strong style="color:#10b981;">Path complete.</strong> You cleared the last available level in this scenario path.
                            @if($certificate)
                                Your PDF certificate is unlocked.
                            @endif
                        </div>
                    @endif

                    @if(!empty($gameResult['skill_focus']) || !empty($gameResult['learning_objective']))
                        <div class="mb-4" style="border:1px solid var(--bd);border-radius:12px;padding:14px;background:var(--bg3);">
                            @if(!empty($gameResult['skill_focus']))
                                <div style="font-size:0.78rem;color:#38bdf8;font-weight:900;text-transform:uppercase;margin-bottom:5px;">
                                    <i class="fa-solid fa-bullseye me-1"></i>{{ $gameResult['skill_focus'] }}
                                </div>
                            @endif
                            @if(!empty($gameResult['learning_objective']))
                                <div style="font-size:0.9rem;color:var(--tx2);line-height:1.5;">{{ $gameResult['learning_objective'] }}</div>
                            @endif
                        </div>
                    @endif

                    @if(!empty($orderedScorecardMetrics))
                        <div class="mb-4 ai-scorecard-panel">
                            <div class="ai-scorecard-heading">
                                <div>
                                    <div class="ai-scorecard-kicker">Goal Score Breakdown</div>
                                    <div class="ai-scorecard-title"><i class="fa-solid fa-clipboard-check me-1 text-info"></i> AI Feedback Scorecard</div>
                                </div>
                                @if($scorecardReliability !== null)
                                    <div class="ai-scorecard-reliability">
                                        <span>Reliability</span>
                                        <strong>{{ $scorecardReliability }}%</strong>
                                        <small style="color:var(--tx3);font-weight:800;">{{ $scorecard['reliability_band'] ?? 'Measured' }}</small>
                                    </div>
                                @endif
                            </div>

                            @if(!empty($scorecard['summary']))
                                <div class="ai-scorecard-summary">{{ $scorecard['summary'] }}</div>
                            @endif

                            <div class="row g-2 game-result-breakdown-grid">
                                @foreach($orderedScorecardMetrics as $metricKey => $metric)
                                    @php
                                        $metricScore = max(0, min(100, (int) ($metric['score'] ?? 0)));
                                        $metricColor = $metricScore >= 85 ? '#10b981' : ($metricScore >= 70 ? '#3b82f6' : ($metricScore >= 50 ? '#f59e0b' : '#ef4444'));
                                        $metricLabel = $metric['label'] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $metricKey));
                                        $metricIcon = $metricIcons[$metricKey] ?? 'fa-chart-simple';
                                    @endphp
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="ai-scorecard-metric">
                                            <div class="ai-scorecard-metric-top">
                                                <div class="ai-scorecard-metric-name"><i class="fa-solid {{ $metricIcon }}" style="color:{{ $metricColor }}"></i><span>{{ $metricLabel }}</span></div>
                                                <div class="ai-scorecard-metric-score">{{ $metricScore }}%</div>
                                            </div>
                                            <div class="ai-scorecard-meter" aria-hidden="true">
                                                <div class="ai-scorecard-meter-fill" style="width:{{ $metricScore }}%;background:{{ $metricColor }};"></div>
                                            </div>
                                            <div class="ai-scorecard-level">{{ $metric['level'] ?? 'Measured' }}</div>
                                            @if(!empty($metric['feedback']))
                                                <div class="ai-scorecard-note">{{ $metric['feedback'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(!empty($scorecard['priority_actions']))
                                <div class="mt-3">
                                    <div style="font-weight:900;color:var(--tx);margin-bottom:8px;">Next Best Actions</div>
                                    <ul class="ai-scorecard-actions-list">
                                        @foreach($scorecard['priority_actions'] as $action)
                                            <li><i class="fa-solid fa-arrow-right text-info me-1"></i>{{ $action }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!empty($questionFeedback))
                                <div class="mt-3">
                                    <div style="font-weight:900;color:var(--tx);margin-bottom:8px;">Question Feedback</div>
                                    <div class="d-grid gap-2">
                                        @foreach($questionFeedback as $item)
                                            <div class="ai-scorecard-question">
                                                <strong>Q{{ ((int) ($item['question_index'] ?? 0)) + 1 }}</strong>
                                                <span>{{ $item['feedback'] ?? 'Review this answer before retrying.' }}</span>
                                                <span class="ai-scorecard-question-score">{{ (int) ($item['score'] ?? 0) }}%</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="ai-scorecard-transparency">
                                <i class="fa-solid fa-shield-halved me-1"></i>{{ $scorecard['evidence_policy'] ?? 'Scores are based on saved challenge answers and level goals.' }}
                                @if(!empty($scorecard['guidance_note']))
                                    <span>{{ $scorecard['guidance_note'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if(!empty($gameResult['success_criteria']))
                        <div>
                            <div style="font-weight:900;color:var(--tx);margin-bottom:10px;">Level Goals</div>
                            <ul class="game-result-checklist">
                                @foreach($gameResult['success_criteria'] as $criterion)
                                    <li><i class="fa-solid fa-check" style="color:#10b981;margin-top:3px;"></i><span>{{ $criterion }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <div class="game-result-actions">
                        @if($resultPassed && $nextLevel)
                            <form action="{{ route('user.game.start', $nextLevel['id']) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success" {{ empty($nextLevel['can_start']) ? 'disabled' : '' }}>
                                    <i class="fa-solid fa-forward me-1"></i> Start Next Level
                                </button>
                            </form>
                        @elseif(! $resultPassed)
                            <form action="{{ route('user.game.start', $gameResult['level_id']) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-warning text-dark" {{ empty($gameResult['can_retry']) ? 'disabled' : '' }}>
                                    <i class="fa-solid fa-rotate-right me-1"></i> Retry Level
                                </button>
                            </form>
                        @endif
                        @if($certificate)
                            <a href="{{ $certificate['download_url'] }}" class="btn btn-success">
                                <i class="fa-solid fa-file-pdf me-1"></i> Download Certificate
                            </a>
                        @endif

                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            Back to Journey
                        </button>
                    </div>
                    @if(($resultPassed && $nextLevel && empty($nextLevel['can_start'])) || (! $resultPassed && empty($gameResult['can_retry'])))
                        <div class="w-100 mt-2" style="font-size:0.82rem;color:#ef4444;text-align:right;">
                            <i class="fa-solid fa-heart-crack me-1"></i>Not enough energy. Energy refills daily.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif



@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const resultModal = document.getElementById('gameResultModal');
        if (resultModal && window.bootstrap && bootstrap.Modal) {
            new bootstrap.Modal(resultModal, {
                backdrop: 'static',
                keyboard: true
            }).show();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const categorySelect = document.getElementById('learningCategorySelect');
        if (!categorySelect) return;

        categorySelect.addEventListener('change', function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;
        if (document.getElementById('gameResultModal')) return;

        const stepsMobile = [
            { element: '#nav-pills-container', popover: { title: 'Challenge Paths', description: 'Switch paths to find different Philippines interview challenges and topics.', side: 'bottom', align: 'start' }},
            { element: '#dashboard-stats', popover: { title: 'Player Stats', description: 'Track level, energy, combo streak, and accuracy while you play.', side: 'top', align: 'start' }},
            { element: '#modules-list', popover: { title: 'Challenge Path', description: 'Choose a level, review its goals and energy cost, then complete levels to unlock more.', side: 'top', align: 'start' }},
            { element: '#btn-skill-tree', popover: { title: 'Skill Tree', description: 'Open the skill tree to spend XP on perks that improve your training loop.', side: 'bottom', align: 'end' }}
        ];

        const stepsDesktop = [
            { element: '#nav-pills-container', popover: { title: 'Challenge Paths', description: 'Switch paths to find different Philippines interview challenges and topics.', side: 'bottom', align: 'start' }},
            { element: '#dashboard-stats', popover: { title: 'Player Stats', description: 'Track level, energy, combo streak, and accuracy while you play.', side: 'bottom', align: 'start' }},
            { element: '#modules-list', popover: { title: 'Challenge Path', description: 'Choose a level, review its goals and energy cost, then complete levels to unlock more.', side: 'top', align: 'start' }},
            { element: '#btn-skill-tree', popover: { title: 'Skill Tree', description: 'Open the skill tree to spend XP on perks that improve your training loop.', side: 'bottom', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_learning',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
