@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Feedback')

@section('content')
<style>
    .feedback-shell {
        max-width: 1120px;
        margin: 0 auto;
    }
    .feedback-shell .feedback-hero {
        min-height: 236px;
        margin-bottom: 24px;
        border: 1px solid rgba(191, 219, 254, 0.72);
        border-radius: 22px;
        background:
            radial-gradient(circle at 84% 72%, rgba(147, 197, 253, 0.32), transparent 18%),
            linear-gradient(108deg, rgba(255, 255, 255, 0.96) 0%, rgba(239, 246, 255, 0.98) 48%, rgba(219, 234, 254, 0.94) 100%);
        box-shadow: 0 18px 42px rgba(37, 99, 235, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.92);
        overflow: hidden;
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(220px, 34%);
        gap: 24px;
        align-items: center;
        padding: clamp(24px, 4vw, 42px);
    }
    .feedback-hero::before {
        content: "";
        position: absolute;
        inset: 18px;
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.78);
        pointer-events: none;
    }
    .feedback-hero-copy {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: 68px minmax(0, 1fr);
        gap: 24px;
        align-items: start;
    }
    .feedback-chat-mark {
        width: 68px;
        height: 68px;
        color: #2563eb;
        filter: drop-shadow(0 12px 16px rgba(37, 99, 235, 0.22));
    }
    .feedback-title {
        margin: 0 0 14px;
        color: #2563eb;
        font-size: clamp(2rem, 4vw, 2.8rem);
        font-weight: 900;
        line-height: 1.05;
        letter-spacing: 0;
        text-transform: uppercase;
    }
    .feedback-subtitle {
        max-width: 600px;
        margin: 0;
        color: #334155;
        font-size: clamp(1.05rem, 2vw, 1.34rem);
        line-height: 1.55;
        letter-spacing: 0;
    }
    .feedback-hero-art {
        position: relative;
        z-index: 1;
        width: 100%;
        min-width: 210px;
        filter: drop-shadow(0 24px 30px rgba(37, 99, 235, 0.24));
        transform-origin: 50% 78%;
        animation: feedbackHeroArtFloat 4.8s ease-in-out infinite;
    }
    .feedback-hero-art :is(circle, rect, path):nth-child(odd) {
        transform-origin: center;
        animation: feedbackHeroArtPulse 3.4s ease-in-out infinite;
    }
    @keyframes feedbackHeroArtFloat {
        0%, 100% { transform: translate3d(0, 0, 0) rotate(0deg) scale(1); }
        35% { transform: translate3d(0, -7px, 0) rotate(1.5deg) scale(1.015); }
        70% { transform: translate3d(-3px, -2px, 0) rotate(-1deg) scale(1.005); }
    }
    @keyframes feedbackHeroArtPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.78; }
    }
    @media (prefers-reduced-motion: reduce) {
        .feedback-hero-art,
        .feedback-hero-art :is(circle, rect, path) {
            animation: none !important;
        }
    }
    .premium-panel {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(203, 213, 225, 0.74);
        border-radius: 22px;
        padding: clamp(20px, 3.4vw, 38px);
        box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .feedback-history-title {
        color: #0f172a;
        margin: 0 0 22px;
        font-size: clamp(1.75rem, 3.2vw, 2.4rem);
        font-weight: 900;
        letter-spacing: 0;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }

    #feedback-filters {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 22px !important;
        width: 100%;
        margin-bottom: 24px;
    }
    .db-filter-input,
    #feedback-filters .btn,
    #feedback-filters .form-select {
        min-height: 70px;
        border: 1px solid rgba(148, 163, 184, 0.46) !important;
        border-radius: 12px !important;
        background-color: rgba(248, 250, 252, 0.72) !important;
        color: #475569 !important;
        font-size: 1.05rem;
        font-weight: 600;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.035);
        transition: all 0.24s ease;
    }
    .db-filter-input:focus, .db-filter-input:focus-within,
    #feedback-filters .form-select:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14), 0 10px 24px rgba(37, 99, 235, 0.09) !important;
        background: #fff !important;
    }
    #feedback-filters .feedback-clear-form,
    #feedback-filters .feedback-search-wrap {
        grid-column: 1 / -1;
    }
    #feedback-filters .feedback-clear-form .btn {
        color: #ef1d2b !important;
        border-color: #ef1d2b !important;
        background: rgba(255, 255, 255, 0.78) !important;
        font-weight: 800;
        justify-content: center;
    }
    #feedback-filters .feedback-search-wrap {
        overflow: hidden;
        display: flex;
        align-items: center;
    }
    #feedback-filters .feedback-search-wrap .input-group-text,
    #feedback-filters .feedback-search-wrap .form-control {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        color: #475569;
        font-size: 1.05rem;
        font-weight: 600;
    }
    #feedback-filters .feedback-search-wrap .input-group-text {
        padding-left: 22px;
        padding-right: 12px;
        color: #475569 !important;
        font-size: 1.25rem;
    }
    .feedback-empty-state {
        display: block;
        width: 100%;
        border: 1px solid rgba(191, 219, 254, 0.72);
        border-radius: 14px;
        background: rgba(96, 165, 250, 0.08);
        color: #475569;
        font-size: 0.95rem;
        line-height: 1.45;
        padding: 18px;
        text-align: left;
    }
    .feedback-table-wrap { overflow: visible; }
    #feedbackTable {
        border-collapse: separate;
        border-spacing: 0 18px;
        color: #0f172a !important;
    }
    #feedbackTable thead { display: none; }
    #feedbackTable tbody tr {
        border: 1px solid rgba(203, 213, 225, 0.8) !important;
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(239, 246, 255, 0.72)) !important;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
    }
    #feedbackTable tbody td {
        padding: 24px 26px !important;
        border: 0 !important;
        vertical-align: middle;
    }
    #feedbackTable tbody td:first-child {
        border-radius: 18px 0 0 18px;
        color: #334155;
        font-weight: 600;
        white-space: nowrap;
    }
    #feedbackTable tbody td:first-child::before {
        content: "\f133";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        margin-right: 10px;
        color: #475569;
    }
    #feedbackTable tbody td:nth-child(2) {
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 900 !important;
    }
    #feedbackTable tbody td:nth-child(3) {
        color: #2563eb;
        font-weight: 900 !important;
    }
    #feedbackTable tbody td:nth-child(3)::before,
    #feedbackTable tbody td:nth-child(4)::before {
        color: #475569;
    }
    #feedbackTable .badge {
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 0.86rem;
    }
    #feedbackTable tbody td:last-child {
        border-radius: 0 18px 18px 0;
    }
    .feedback-history-actions {
        min-width: 300px;
    }
    .feedback-history-actions .btn {
        min-height: 52px;
        border-radius: 10px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 0.98rem;
        font-weight: 800 !important;
        padding-inline: 18px;
    }
    .feedback-history-actions .btn-primary {
        border-color: #2563eb;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.22);
    }
    .feedback-history-actions .btn-outline-danger {
        color: #ef1d2b;
        border-color: #ef1d2b;
        background: rgba(255, 255, 255, 0.74);
    }
    .feedback-history-delete-label { display: inline; }

    :root:not(.lm) .feedback-hero {
        border-color: rgba(147, 197, 253, 0.28);
        background:
            radial-gradient(circle at 84% 72%, rgba(37, 99, 235, 0.24), transparent 18%),
            linear-gradient(108deg, #111827 0%, #172554 100%);
        box-shadow: 0 18px 42px rgba(2, 6, 23, 0.28);
    }
    :root:not(.lm) .feedback-title {
        color: #93c5fd;
        text-shadow: 0 1px 0 rgba(0, 0, 0, 0.16);
    }
    :root:not(.lm) .feedback-subtitle {
        color: #e2e8f0;
    }
    :root:not(.lm) .feedback-chat-mark {
        color: #60a5fa;
        filter: drop-shadow(0 8px 12px rgba(0, 0, 0, 0.32));
    }
    :root:not(.lm) .feedback-hero-art {
        opacity: 1;
        filter: drop-shadow(0 14px 18px rgba(0, 0, 0, 0.38));
    }
    :root:not(.lm) .premium-panel {
        background: rgba(17, 24, 39, 0.94);
        border-color: rgba(147, 197, 253, 0.18);
    }
    :root:not(.lm) .feedback-history-title,
    :root:not(.lm) #feedbackTable tbody td:nth-child(2) {
        color: #f8fafc;
    }
    :root:not(.lm) #feedbackTable tbody tr {
        background: linear-gradient(135deg, rgba(21, 28, 45, 0.98), rgba(29, 38, 58, 0.92)) !important;
        border-color: rgba(147, 197, 253, 0.18) !important;
    }
    :root:not(.lm) .db-filter-input,
    :root:not(.lm) #feedback-filters .btn,
    :root:not(.lm) #feedback-filters .form-select,
    :root:not(.lm) #feedback-filters .feedback-search-wrap .input-group-text,
    :root:not(.lm) #feedback-filters .feedback-search-wrap .form-control {
        background-color: rgba(15, 23, 42, 0.24) !important;
        border-color: rgba(147, 197, 253, 0.18) !important;
        color: #d6deea !important;
    }

    @media (max-width: 1199px) {
        #feedback-filters {
            grid-template-columns: 1fr;
        }

        #feedback-filters #scenarioFilter {
            grid-column: 1 / -1;
            display: block;
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
        }

        #feedback-filters #sortDateBtn {
            grid-column: 1 / -1;
            width: 100% !important;
            min-width: 0;
            white-space: nowrap;
        }

        #feedback-filters .feedback-search-wrap {
            grid-column: 1 / -1;
            width: 100% !important;
            max-width: none !important;
            min-width: 0;
        }

        #feedback-filters form {
            grid-column: 1 / -1;
            width: 100%;
        }

        #feedback-filters form .btn {
            width: 100%;
        }
    }

    @media (max-width: 767px) {
        .feedback-shell {
            padding-inline: 0;
        }
        .feedback-hero {
            min-height: 96px;
            grid-template-columns: 1fr;
            gap: 8px;
            padding: 0;
            border-radius: 16px;
            margin-bottom: 14px;
            border-color: rgba(96, 165, 250, 0.26);
            background:
                radial-gradient(circle at 92% 35%, rgba(96, 165, 250, 0.2), transparent 25%),
                linear-gradient(110deg, rgba(255, 255, 255, 0.99), rgba(239, 246, 255, 0.97)) !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }
        .feedback-hero::before {
            display: none;
        }
        .feedback-hero-copy {
            min-height: 96px;
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            padding: 12px 104px 12px 14px;
        }
        .feedback-chat-mark {
            box-sizing: border-box;
            width: 38px;
            height: 38px;
            padding: 9px;
            color: #2563eb;
            border: 1px solid rgba(37, 99, 235, 0.16);
            border-radius: 11px;
            background: rgba(37, 99, 235, 0.07);
        }
        .feedback-title {
            font-size: 0.98rem !important;
            line-height: 1.08;
            margin-bottom: 4px;
            color: #2563eb;
        }
        .feedback-subtitle {
            max-width: 13.5rem;
            font-size: 0.68rem;
            line-height: 1.28;
            color: #334155;
        }
        .feedback-hero-art {
            position: absolute;
            right: -6px;
            bottom: 4px;
            width: 92px;
            min-width: 0;
            opacity: 0.9;
            filter: drop-shadow(0 14px 18px rgba(0, 0, 0, 0.34));
        }
        :root:not(.lm) .feedback-hero {
            border-color: rgba(96, 165, 250, 0.38);
            background:
                radial-gradient(circle at 92% 35%, rgba(96, 165, 250, 0.22), transparent 25%),
                linear-gradient(110deg, #111827, #172554) !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18);
        }
        :root:not(.lm) .feedback-title {
            color: #93c5fd;
        }
        :root:not(.lm) .feedback-subtitle {
            color: #f8fafc;
        }
        :root:not(.lm) .feedback-chat-mark {
            color: #60a5fa;
            border-color: rgba(147, 197, 253, 0.28);
            background: rgba(59, 130, 246, 0.16);
        }
        .premium-panel {
            padding: 14px;
            border-radius: 14px;
        }
        .feedback-history-title {
            font-size: 1.25rem;
            margin-bottom: 12px;
        }
        #feedback-filters {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px !important;
            margin-bottom: 12px;
        }
        .db-filter-input,
        #feedback-filters .btn,
        #feedback-filters .form-select {
            min-height: 44px;
            border-radius: 10px !important;
            font-size: 0.78rem;
        }
        #feedback-filters .feedback-search-wrap .input-group-text,
        #feedback-filters .feedback-search-wrap .form-control {
            font-size: 0.78rem;
        }
        #feedback-filters .feedback-search-wrap .input-group-text {
            padding-left: 12px;
            padding-right: 8px;
            font-size: 0.9rem;
        }
        #feedbackTable {
            border-spacing: 0 10px;
        }
        #feedbackTable tbody tr {
            display: block !important;
            padding: 10px !important;
            border-radius: 12px !important;
        }
        #feedbackTable tbody td {
            display: block !important;
            width: 100% !important;
            padding: 0 0 6px !important;
            font-size: 0.7rem !important;
            text-align: left !important;
        }
        #feedbackTable tbody td:first-child,
        #feedbackTable tbody td:last-child {
            border-radius: 0;
        }
        #feedbackTable tbody td:nth-child(1) {
            font-size: 0.6rem !important;
            color: #334155 !important;
            padding-bottom: 6px !important;
        }
        #feedbackTable tbody td:nth-child(2) {
            font-size: 0.78rem !important;
            line-height: 1.18;
            padding-bottom: 7px !important;
        }
        #feedbackTable tbody td:nth-child(3)::before {
            content: "Score: ";
            color: #475569;
            font-weight: 800;
        }
        #feedbackTable tbody td:nth-child(4)::before {
            content: "Rating: ";
            color: #475569;
            font-weight: 800;
        }
        #feedback-filters #sortDateBtn,
        #feedback-filters .feedback-clear-form {
            grid-column: auto !important;
            min-width: 0;
        }
        #feedback-filters #sortDateBtn {
            width: 100% !important;
        }
        #feedback-filters .feedback-clear-form .btn {
            min-height: 44px;
            padding-inline: 10px;
            font-size: 0.74rem;
            width: 100%;
        }
        #mob-content #feedbackTable tbody td:nth-child(5) .feedback-history-actions {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: stretch !important;
            flex-wrap: nowrap !important;
            gap: 8px !important;
            margin-top: 4px !important;
            min-width: 0;
        }
        #mob-content #feedbackTable tbody td:nth-child(5) .feedback-history-actions > a,
        #mob-content #feedbackTable tbody td:nth-child(5) .feedback-history-actions > form {
            flex: 1 1 0 !important;
            min-width: 0 !important;
            width: auto !important;
            margin: 0 !important;
        }
        #mob-content #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn {
            width: 100% !important;
            min-height: 34px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px;
            border-radius: 10px !important;
            font-size: 0.62rem;
            white-space: nowrap;
        }
        #feedbackTable .badge {
            padding: 4px 7px;
            font-size: 0.56rem;
        }
    }
    @media (max-width: 575px) {
        .feedback-hero {
            min-height: 92px;
        }
        .feedback-hero-copy {
            min-height: 92px;
            grid-template-columns: 36px minmax(0, 1fr);
            gap: 9px;
            padding: 11px 96px 11px 12px;
        }
        .feedback-chat-mark {
            width: 36px;
            height: 36px;
            padding: 8px;
        }
        .feedback-title {
            font-size: 0.9rem !important;
            margin-bottom: 4px;
        }
        .feedback-subtitle {
            max-width: 12rem;
            font-size: 0.64rem;
        }
        .feedback-hero-art {
            right: -4px;
            bottom: 5px;
            width: 84px;
        }
    }
    @media (max-width: 390px) {
        .feedback-hero-copy {
            padding-right: 86px;
        }
        .feedback-title {
            font-size: 0.86rem !important;
        }
        .feedback-hero-art {
            width: 78px;
        }
    }
    @media (max-width: 380px) {
        .premium-panel { padding: 12px; }
        .feedback-hero {
            min-height: 92px;
            padding: 0;
        }
        .feedback-hero-copy {
            min-height: 92px;
            grid-template-columns: 36px minmax(0, 1fr);
            gap: 9px;
            padding: 11px 86px 11px 12px;
        }
        .feedback-chat-mark {
            width: 36px;
            height: 36px;
            padding: 8px;
        }
        .feedback-title { font-size: 0.86rem !important; }
        .feedback-subtitle {
            max-width: 12rem;
            font-size: 0.64rem;
        }
        .feedback-hero-art {
            width: 78px;
            right: -4px;
            bottom: 5px;
        }
        #mob-content #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn {
            font-size: 0.56rem;
            min-height: 32px !important;
            padding-inline: 6px;
        }
        #feedbackTable tbody tr {
            padding: 9px !important;
        }
        #feedbackTable tbody td:nth-child(2) {
            font-size: 0.74rem !important;
        }
        #feedbackTable .badge {
            font-size: 0.5rem;
        }
    }
    @media (max-width: 767px) {
        #feedback-filters {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 7px !important;
        }
        #feedback-filters #scenarioFilter,
        #feedback-filters .feedback-search-wrap {
            grid-column: 1 / -1 !important;
        }
        #feedback-filters #sortDateBtn,
        #feedback-filters .feedback-clear-form {
            grid-column: auto !important;
            width: 100% !important;
            min-width: 0 !important;
        }
        #feedback-filters .db-filter-input,
        #feedback-filters .btn,
        #feedback-filters .form-select {
            min-height: 38px !important;
            border-radius: 9px !important;
            font-size: 0.68rem !important;
        }
        #feedback-filters .feedback-clear-form .btn,
        #feedback-filters #sortDateBtn {
            padding-inline: 8px !important;
            justify-content: center;
        }
        #feedback-filters .feedback-search-wrap .input-group-text,
        #feedback-filters .feedback-search-wrap .form-control {
            font-size: 0.68rem !important;
        }
        #feedback-filters .feedback-search-wrap .input-group-text {
            padding-left: 10px;
            padding-right: 7px;
        }
    }

    /* Match the compact Modules banner layout and theme behavior. */
    .feedback-shell .feedback-hero {
        --feedback-hero-title-color: #1d4ed8;
        --feedback-hero-text-color: #334155;
        --feedback-hero-icon-bg: rgba(239, 246, 255, 0.92);
        --feedback-hero-icon-border: rgba(147, 197, 253, 0.42);
        display: grid !important;
        grid-template-columns: 44px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 10px !important;
        min-height: 104px !important;
        padding: 14px 116px 14px 14px !important;
        margin-bottom: 12px !important;
        border-radius: 14px !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(219, 234, 254, 0.78), transparent 35%),
            linear-gradient(142deg, #ffffff 0%, #f8fbff 52%, #dbeafe 100%) !important;
        border-color: rgba(147, 197, 253, 0.52) !important;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.1) !important;
    }
    html[data-theme="dark"] .feedback-shell .feedback-hero,
    :root:not(.lm) .feedback-shell .feedback-hero {
        --feedback-hero-title-color: #93c5fd;
        --feedback-hero-text-color: #e2e8f0;
        --feedback-hero-icon-bg: rgba(59, 130, 246, 0.2);
        --feedback-hero-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28) !important;
    }
    .feedback-shell .feedback-hero::before {
        content: none !important;
    }
    .feedback-shell .feedback-hero-copy {
        display: contents !important;
    }
    .feedback-shell .feedback-chat-mark {
        box-sizing: border-box;
        width: 34px !important;
        height: 34px !important;
        padding: 8px;
        border: 1px solid var(--feedback-hero-icon-border);
        border-radius: 10px;
        background: var(--feedback-hero-icon-bg);
        color: var(--feedback-hero-title-color) !important;
        filter: none !important;
    }
    .feedback-shell .feedback-title {
        margin: 0 0 4px !important;
        font-size: 1.1rem !important;
        line-height: 1.15 !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        color: var(--feedback-hero-title-color) !important;
        -webkit-text-fill-color: var(--feedback-hero-title-color) !important;
        white-space: nowrap;
    }
    .feedback-shell .feedback-subtitle {
        max-width: 100% !important;
        margin: 0 !important;
        font-size: 0.74rem !important;
        line-height: 1.4 !important;
        color: var(--feedback-hero-text-color) !important;
    }
    .feedback-shell .feedback-hero-art {
        position: absolute !important;
        right: -10px;
        bottom: -1px;
        width: 112px !important;
        min-width: 0 !important;
        max-width: none !important;
    }

    @media (max-width: 767px) {
        .feedback-shell .feedback-hero {
            grid-template-columns: 44px minmax(0, 1fr) !important;
            gap: 10px !important;
            min-height: 104px !important;
            padding: 14px 116px 14px 14px !important;
        }
        .feedback-shell .feedback-chat-mark {
            width: 34px !important;
            height: 34px !important;
            padding: 8px;
        }
        .feedback-shell .feedback-title {
            font-size: 1.1rem !important;
        }
        .feedback-shell .feedback-subtitle {
            font-size: 0.74rem !important;
            line-height: 1.4 !important;
        }
        .feedback-shell .feedback-hero-art {
            width: 112px !important;
        }
    }

    @media (max-width: 390px) {
        .feedback-shell .feedback-hero {
            grid-template-columns: 34px minmax(0, 1fr) !important;
            gap: 8px !important;
            padding: 10px 86px 10px 10px !important;
        }
        .feedback-shell .feedback-chat-mark {
            width: 32px !important;
            height: 32px !important;
            padding: 7px;
        }
        .feedback-shell .feedback-title {
            font-size: 0.86rem !important;
        }
        .feedback-shell .feedback-subtitle {
            font-size: 0.66rem !important;
        }
        .feedback-shell .feedback-hero-art {
            width: 78px !important;
        }
    }

    #feedbackModulesLikeHero.feedback-hero {
        --feedback-hero-title-color: #1d4ed8;
        --feedback-hero-text-color: #334155;
        --feedback-hero-icon-bg: rgba(239, 246, 255, 0.92);
        --feedback-hero-icon-border: rgba(147, 197, 253, 0.42);
        display: grid !important;
        grid-template-columns: 44px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 10px !important;
        min-height: 78px !important;
        padding: 8px 94px 8px 14px !important;
        margin-bottom: 14px !important;
        border-radius: 16px !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
        border-color: rgba(191, 219, 254, 0.86) !important;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08) !important;
        overflow: hidden !important;
        position: relative !important;
    }
    :root:not(.lm) #feedbackModulesLikeHero.feedback-hero,
    .dm #feedbackModulesLikeHero.feedback-hero {
        --feedback-hero-title-color: #93c5fd;
        --feedback-hero-text-color: #e2e8f0;
        --feedback-hero-icon-bg: rgba(59, 130, 246, 0.2);
        --feedback-hero-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28) !important;
    }
    #feedbackModulesLikeHero.feedback-hero::before {
        display: none !important;
    }
    #feedbackModulesLikeHero .feedback-hero-copy {
        display: contents !important;
    }
    #feedbackModulesLikeHero .feedback-chat-mark {
        width: 34px !important;
        height: 34px !important;
        padding: 8px !important;
        border: 1px solid var(--feedback-hero-icon-border) !important;
        border-radius: 10px !important;
        background: var(--feedback-hero-icon-bg) !important;
        color: var(--feedback-hero-title-color) !important;
        filter: none !important;
    }
    #feedbackModulesLikeHero .feedback-title {
        color: var(--feedback-hero-title-color) !important;
        font-size: 1.02rem !important;
        line-height: 1.08 !important;
        margin: 0 0 4px !important;
        font-weight: 950 !important;
        white-space: nowrap !important;
    }
    #feedbackModulesLikeHero .feedback-subtitle {
        color: var(--feedback-hero-text-color) !important;
        font-size: 0.64rem !important;
        line-height: 1.32 !important;
        margin: 0 !important;
        max-width: 13.5rem !important;
        font-weight: 500;
    }
    #feedbackModulesLikeHero .feedback-hero-art {
        position: absolute !important;
        right: 8px !important;
        bottom: 2px !important;
        width: 78px !important;
        min-width: 0 !important;
        opacity: 0.92;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16));
    }
    @media (max-width: 390px) {
        #feedbackModulesLikeHero.feedback-hero {
            min-height: 74px !important;
            grid-template-columns: 36px minmax(0, 1fr) !important;
            gap: 9px !important;
            padding: 8px 76px 8px 12px !important;
            border-radius: 16px !important;
        }
        #feedbackModulesLikeHero .feedback-chat-mark {
            width: 32px !important;
            height: 32px !important;
            padding: 7px !important;
        }
        #feedbackModulesLikeHero .feedback-title {
            font-size: 0.9rem !important;
            margin-bottom: 4px !important;
        }
        #feedbackModulesLikeHero .feedback-subtitle {
            max-width: 12rem !important;
            font-size: 0.64rem !important;
            line-height: 1.28 !important;
        }
        #feedbackModulesLikeHero .feedback-hero-art {
            right: -4px !important;
            bottom: 5px !important;
            width: 68px !important;
        }
    }
</style>

<div class="db-section active animate-fade-up feedback-shell">
    <div class="feedback-hero" id="feedbackModulesLikeHero">
        <div class="feedback-hero-copy">
            <svg class="feedback-chat-mark" viewBox="0 0 64 64" aria-hidden="true">
                <path d="M13 46.5 8 56l12.6-3.8c3.4 1.6 7.3 2.4 11.4 2.4 14.4 0 26-9.8 26-22S46.4 10.5 32 10.5 6 20.3 6 32.4c0 5.5 2.4 10.5 7 14.1Z" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M20 25h24M20 35h16" fill="none" stroke="currentColor" stroke-width="4.5" stroke-linecap="round"/>
                <circle cx="45" cy="42" r="4.5" fill="currentColor" opacity=".72"/>
            </svg>
            <div>
                <h4 class="feedback-title">Feedback Center</h4>
                <p class="feedback-subtitle">Review scores and AI feedback from Philippines interview practice.</p>
            </div>
        </div>
        <svg class="feedback-hero-art" viewBox="0 0 270 190" aria-hidden="true">
            <defs>
                <linearGradient id="feedbackBubble" x1="20" y1="16" x2="225" y2="160"><stop stop-color="#EFF6FF"/><stop offset="1" stop-color="#DBEAFE"/></linearGradient>
                <linearGradient id="feedbackCheck" x1="181" y1="22" x2="234" y2="78"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#1D4ED8"/></linearGradient>
            </defs>
            <path d="M30 34h186c15 0 27 12 27 27v58c0 15-12 27-27 27h-95l-50 30 13-30H30c-15 0-27-12-27-27V61c0-15 12-27 27-27Z" fill="url(#feedbackBubble)" stroke="#BFDBFE" stroke-width="2"/>
            <path d="M45 71h105M45 95h132M45 119h112" stroke="#93C5FD" stroke-width="8" stroke-linecap="round"/>
            <path d="M45 142h59M124 142h76" stroke="#60A5FA" stroke-width="8" stroke-linecap="round" opacity=".88"/>
            <circle cx="211" cy="61" r="31" fill="url(#feedbackCheck)"/>
            <path d="m197 60 10 10 20-24" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M-4 11h12M2 5v12M-27 88h8M-23 84v8" stroke="#60A5FA" stroke-width="5" stroke-linecap="round"/>
            <circle cx="-5" cy="126" r="6" fill="#93C5FD" opacity=".85"/>
        </svg>
    </div>

    <div class="premium-panel">
        <div class="feedback-history-head">
            <h5 class="feedback-history-title">Feedback History</h5>
            <div id="feedback-filters">
                <select id="scenarioFilter" class="form-select db-filter-input">
                    <option value="">All Scenarios</option>
                    @foreach($feedbackCategories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-secondary" id="sortDateBtn"><i class="fa-regular fa-calendar me-2"></i> All Time</button>
                @if($sessions->total() > 0)
                    <form class="feedback-clear-form" action="{{ route('user.sessions.clear') }}" method="POST" onsubmit="return confirm('Clear all completed interview sessions? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fa-solid fa-trash-can me-2"></i> Clear All
                        </button>
                    </form>
                @endif
                <div class="input-group db-filter-input feedback-search-wrap">
                    <span class="input-group-text border-0"><i class="fa-solid fa-search"></i></span>
                    <input type="text" id="feedbackSearch" class="form-control border-0" placeholder="Search feedback...">
                </div>
            </div>
        </div>

        <div class="table-responsive feedback-table-wrap">
            <table class="table custom-table align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent;" id="feedbackTable">
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
                    <tr style="border-bottom: 1px solid var(--bd);" data-scenario="{{ $session->practice_scenario ?? 'General Job Interview' }}" data-date="{{ $session->created_at->timestamp }}">
                        <td class="border-0 py-3">{{ $session->created_at->format('M d, Y') }}</td>
                        <td class="border-0 py-3 fw-bold">{{ $session->practice_scenario ?? 'General Job Interview' }}</td>
                        <td class="border-0 py-3 fw-bold">
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
                            @elseif($sc >= 50) <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b;">Fair</span>
                            @else <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">Needs Improvement</span>
                            @endif
                        </td>
                        <td class="border-0 py-3 text-end">
                            <div class="d-flex justify-content-end gap-2 feedback-history-actions">
                                <a href="{{ route('user.review', $session->id) }}" class="btn btn-sm btn-primary btn-shine"><i class="fa-solid fa-chart-simple"></i> View Details</a>
                                <form action="{{ route('user.sessions.destroy', $session->id) }}" method="POST" onsubmit="return confirm('Delete this interview session? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete session">
                                        <i class="fa-solid fa-trash-can"></i> <span class="feedback-history-delete-label">Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($sessions->count() == 0)
                    <tr>
                        <td colspan="5" class="border-0 py-3">
                            <span class="feedback-empty-state">No feedback available yet. Complete a Philippines practice interview to generate detailed feedback.</span>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <!-- Pagination UI -->
        <div class="mt-4 d-flex justify-content-end" id="feedbackPagination">
            {{ $sessions->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('feedbackSearch');
        const scenarioFilter = document.getElementById('scenarioFilter');
        const sortBtn = document.getElementById('sortDateBtn');
        const tbody = document.querySelector('#feedbackTable tbody');
        let sortDesc = true;

        function filterTable() {
            const search = searchInput.value.toLowerCase();
            const scenario = scenarioFilter.value.toLowerCase();
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const rowScenario = (row.getAttribute('data-scenario') || '').toLowerCase();
                
                const matchesSearch = text.includes(search);
                const matchesScenario = scenario === "" || rowScenario.includes(scenario);

                if (matchesSearch && matchesScenario) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if(searchInput) searchInput.addEventListener('keyup', filterTable);
        if(scenarioFilter) scenarioFilter.addEventListener('change', filterTable);

        if(sortBtn) {
            sortBtn.addEventListener('click', function() {
                sortDesc = !sortDesc;
                sortBtn.innerHTML = sortDesc ? '<i class="fa-solid fa-arrow-down-short-wide me-1"></i> Sort by Date' : '<i class="fa-solid fa-arrow-up-wide-short me-1"></i> Sort by Date';
                
                const rows = Array.from(tbody.querySelectorAll('tr'));
                rows.sort((a, b) => {
                    const d1 = parseInt(a.getAttribute('data-date') || 0);
                    const d2 = parseInt(b.getAttribute('data-date') || 0);
                    return sortDesc ? d2 - d1 : d1 - d2;
                });
                
                rows.forEach(row => tbody.appendChild(row));
            });
        }
    });
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#feedback-filters', popover: { title: 'Filters & Search', description: 'Filter by scenario or search keywords to find a specific feedback record.', side: 'bottom', align: 'start' }},
            { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review past Philippines practice interviews, scores, ratings, and available actions.', side: 'top', align: 'center' }},
            { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Move through older interview feedback records from here.', side: 'top', align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#feedback-filters', popover: { title: 'Filters & Search', description: 'Filter by scenario or search keywords to find a specific feedback record.', side: 'bottom', align: 'end' }},
            { element: '#feedbackTable', popover: { title: 'Interview History', description: 'Review past Philippines practice interviews, scores, ratings, and available actions.', side: 'top', align: 'center' }},
            { element: '#feedbackPagination', popover: { title: 'Pagination', description: 'Move through older interview feedback records from here.', side: 'top', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_feedback',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
