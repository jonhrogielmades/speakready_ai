@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Feedback')

@section('content')
<style>
    .feedback-shell {
        max-width: none;
        margin: 0;
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
        display: flex;
        align-items: center;
        gap: 10px;
        width: min(100%, 430px);
        margin: 8px auto 0;
        border: 1px solid rgba(147, 197, 253, 0.72);
        border-radius: 12px;
        background: rgba(239, 246, 255, 0.72);
        color: #334155;
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1.35;
        padding: 12px;
        text-align: left;
    }
    .feedback-empty-state i {
        width: 28px;
        height: 28px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
        font-size: 0.8rem;
    }
    :root:not(.lm) .feedback-empty-state {
        border-color: rgba(96, 165, 250, 0.34);
        background: rgba(30, 41, 59, 0.62);
        color: #e2e8f0;
    }
    :root:not(.lm) .feedback-empty-state i {
        background: rgba(96, 165, 250, 0.18);
        color: #93c5fd;
    }
    #feedbackTable tbody tr.feedback-empty-row {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }
    #feedbackTable tbody tr.feedback-empty-row td {
        padding: 8px 0 !important;
        border-radius: 0 !important;
    }
    #feedbackTable tbody tr.feedback-empty-row td::before {
        content: none !important;
        display: none !important;
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
        #feedbackTable tbody tr.feedback-empty-row {
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }
        #feedbackTable tbody tr.feedback-empty-row td {
            padding: 0 !important;
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
        #feedbackTable tbody tr.feedback-empty-row {
            padding: 0 !important;
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
        --feedback-hero-title-color: #ffffff;
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
        --feedback-hero-title-color: #ffffff;
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
        --feedback-hero-title-color: #ffffff;
        --feedback-hero-text-color: #334155;
        --feedback-hero-icon-bg: rgba(239, 246, 255, 0.92);
        --feedback-hero-icon-border: rgba(147, 197, 253, 0.42);
        display: grid !important;
        grid-template-columns: 30px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 8px !important;
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin-bottom: 10px !important;
        border-radius: 8px !important;
        background:
            radial-gradient(circle at 86% 20%, rgba(219, 234, 254, 0.72), transparent 34%),
            linear-gradient(142deg, #ffffff 0%, #f8fbff 54%, #dbeafe 100%) !important;
        border-color: rgba(147, 197, 253, 0.52) !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
        overflow: hidden !important;
        position: relative !important;
    }
    :root:not(.lm) #feedbackModulesLikeHero.feedback-hero,
    .dm #feedbackModulesLikeHero.feedback-hero {
        --feedback-hero-title-color: #ffffff;
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
        width: 28px !important;
        height: 28px !important;
        padding: 6px !important;
        border: 1px solid var(--feedback-hero-icon-border) !important;
        border-radius: 8px !important;
        background: var(--feedback-hero-icon-bg) !important;
        color: var(--feedback-hero-title-color) !important;
        filter: none !important;
    }
    #feedbackModulesLikeHero .feedback-title {
        color: var(--feedback-hero-title-color) !important;
        -webkit-text-fill-color: var(--feedback-hero-title-color) !important;
        font-size: 0.72rem !important;
        line-height: 1.08 !important;
        margin: 0 0 3px !important;
        font-weight: 950 !important;
        white-space: nowrap !important;
    }
    #feedbackModulesLikeHero .feedback-subtitle {
        color: var(--feedback-hero-text-color) !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
        margin: 0 !important;
        max-width: 13.5rem !important;
        font-weight: 500;
    }
    #feedbackModulesLikeHero .feedback-hero-art {
        position: absolute !important;
        right: -5px !important;
        bottom: -2px !important;
        width: 72px !important;
        min-width: 0 !important;
        opacity: 0.92;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16));
    }
    @media (max-width: 390px) {
        #feedbackModulesLikeHero.feedback-hero {
            min-height: 69px !important;
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
            border-radius: 8px !important;
        }
        #feedbackModulesLikeHero .feedback-chat-mark {
            width: 27px !important;
            height: 27px !important;
            padding: 6px !important;
        }
        #feedbackModulesLikeHero .feedback-title {
            font-size: 0.68rem !important;
            margin-bottom: 3px !important;
        }
        #feedbackModulesLikeHero .feedback-subtitle {
            max-width: 12rem !important;
            font-size: 0.46rem !important;
            line-height: 1.28 !important;
        }
        #feedbackModulesLikeHero .feedback-hero-art {
            right: -5px !important;
            bottom: -2px !important;
            width: 66px !important;
        }
    }

    @media (max-width: 767px) {
        .feedback-shell {
            --feedback-saas-radius: 12px;
            --feedback-saas-gap: 10px;
            --feedback-saas-border: rgba(37, 99, 235, 0.14);
            --feedback-saas-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            --feedback-saas-card: rgba(248, 250, 252, 0.76);
            --feedback-saas-muted: #475569;
        }
        html[data-theme="dark"] .feedback-shell,
        :root:not(.lm) .feedback-shell,
        .dm .feedback-shell {
            --feedback-saas-border: rgba(147, 197, 253, 0.18);
            --feedback-saas-shadow: 0 12px 26px rgba(0, 0, 0, 0.26);
            --feedback-saas-card: rgba(255, 255, 255, 0.045);
            --feedback-saas-muted: #cbd5e1;
        }
        .feedback-shell .premium-panel {
            padding: 12px !important;
            border-radius: var(--feedback-saas-radius) !important;
            border-color: var(--feedback-saas-border) !important;
            box-shadow: var(--feedback-saas-shadow) !important;
        }
        .feedback-shell .feedback-history-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 10px !important;
            color: var(--tx) !important;
            font-size: 0.98rem !important;
            line-height: 1.14 !important;
            letter-spacing: 0 !important;
        }
        .feedback-shell .feedback-history-title::before {
            content: "\f0e6";
            width: 32px;
            height: 32px;
            border: 1px solid var(--feedback-saas-border);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.08);
            font-family: "Font Awesome 6 Free";
            font-size: 0.82rem;
            font-weight: 900;
        }
        :root:not(.lm) .feedback-shell .feedback-history-title::before,
        .dm .feedback-shell .feedback-history-title::before {
            color: #93c5fd;
            background: rgba(59, 130, 246, 0.18);
        }
        .feedback-shell #feedback-filters {
            gap: 8px !important;
            margin-bottom: 10px !important;
            padding: 8px;
            border: 1px solid var(--feedback-saas-border);
            border-radius: 12px;
            background: var(--feedback-saas-card);
        }
        .feedback-shell #feedback-filters .db-filter-input,
        .feedback-shell #feedback-filters .btn,
        .feedback-shell #feedback-filters .form-select {
            min-height: 40px !important;
            border-radius: 9px !important;
            border-color: var(--feedback-saas-border) !important;
            box-shadow: none !important;
            font-size: 0.7rem !important;
            font-weight: 800;
        }
        .feedback-shell #feedback-filters .feedback-search-wrap {
            min-height: 40px !important;
            background: color-mix(in srgb, var(--sf) 74%, transparent) !important;
        }
        .feedback-shell #feedback-filters .feedback-search-wrap .input-group-text,
        .feedback-shell #feedback-filters .feedback-search-wrap .form-control {
            color: var(--feedback-saas-muted) !important;
            font-size: 0.7rem !important;
        }
        .feedback-shell #feedback-filters #sortDateBtn,
        .feedback-shell #feedback-filters .feedback-clear-form .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
        }
        .feedback-shell .feedback-table-wrap {
            margin-top: 0;
        }
        .feedback-shell #feedbackTable {
            border-spacing: 0 8px !important;
        }
        .feedback-shell #feedbackTable tbody tr {
            position: relative;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 7px 10px;
            padding: 11px !important;
            border-radius: 12px !important;
            border-color: var(--feedback-saas-border) !important;
            background: var(--feedback-saas-card) !important;
            box-shadow: none !important;
        }
        .feedback-shell #feedbackTable tbody tr.feedback-empty-row {
            display: block !important;
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
        }
        .feedback-shell #feedbackTable tbody td {
            width: auto !important;
            padding: 0 !important;
            border-radius: 0 !important;
            line-height: 1.25;
        }
        .feedback-shell #feedbackTable tbody td:nth-child(1) {
            grid-column: 1 / -1;
            display: flex !important;
            align-items: center;
            gap: 6px;
            color: var(--feedback-saas-muted) !important;
            font-size: 0.62rem !important;
            font-weight: 800;
            text-transform: uppercase;
        }
        .feedback-shell #feedbackTable tbody td:nth-child(1)::before {
            margin-right: 0 !important;
            color: #64748b !important;
            font-size: 0.66rem;
        }
        .feedback-shell #feedbackTable tbody td:nth-child(2) {
            grid-column: 1 / -1;
            color: var(--tx) !important;
            font-size: 0.86rem !important;
            font-weight: 900 !important;
        }
        .feedback-shell #feedbackTable tbody td:nth-child(3),
        .feedback-shell #feedbackTable tbody td:nth-child(4) {
            align-self: center;
            color: var(--feedback-saas-muted) !important;
            font-size: 0.68rem !important;
            font-weight: 850 !important;
        }
        .feedback-shell #feedbackTable tbody td:nth-child(3)::before,
        .feedback-shell #feedbackTable tbody td:nth-child(4)::before {
            color: var(--feedback-saas-muted) !important;
        }
        .feedback-shell #feedbackTable tbody td:nth-child(5) {
            grid-column: 1 / -1;
            padding-top: 3px !important;
        }
        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 38px;
            gap: 8px !important;
            margin-top: 0 !important;
        }
        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions > a,
        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions > form {
            width: 100% !important;
        }
        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn {
            min-height: 38px !important;
            border-radius: 9px !important;
            font-size: 0.68rem !important;
            font-weight: 900 !important;
            box-shadow: none !important;
        }
        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn-outline-danger {
            padding-inline: 0 !important;
        }
        .feedback-shell .feedback-history-delete-label {
            display: none !important;
        }
        .feedback-shell #feedbackTable .badge {
            padding: 4px 8px !important;
            border-radius: 999px !important;
            font-size: 0.58rem !important;
            font-weight: 900 !important;
        }
        .feedback-shell .feedback-empty-state {
            width: 100%;
            margin-top: 0;
            border-radius: 10px;
            border-color: var(--feedback-saas-border);
            background: var(--feedback-saas-card);
            font-size: 0.72rem;
        }
        .feedback-shell #feedbackPagination {
            justify-content: center !important;
            margin-top: 12px !important;
        }
        .feedback-shell #feedbackPagination .pagination {
            gap: 5px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .feedback-shell #feedbackPagination .page-link {
            min-width: 34px;
            min-height: 34px;
            border-radius: 9px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-color: var(--feedback-saas-border);
            font-size: 0.72rem;
            font-weight: 800;
        }
    }

    /* Final mobile SaaSPro layer for the Feedback screen. */
    @media (max-width: 767px) {
        #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0, rgba(16, 185, 129, 0.035) 260px, transparent 520px),
                var(--bg) !important;
        }

        #mob-content > .db-content {
            padding: 12px 12px 16px !important;
        }

        #mob-content .feedback-shell {
            --feedback-mobile-radius: 8px;
            --feedback-mobile-card: rgba(255, 255, 255, 0.98);
            --feedback-mobile-panel: #f8fafc;
            --feedback-mobile-field: rgba(255, 255, 255, 0.96);
            --feedback-mobile-border: rgba(15, 23, 42, 0.1);
            --feedback-mobile-title: #0f172a;
            --feedback-mobile-muted: #64748b;
            --feedback-mobile-shadow: 0 14px 30px rgba(15, 23, 42, 0.1);
            max-width: 520px;
            margin: 0 auto !important;
            gap: 12px !important;
        }

        html[data-theme="dark"] #mob-content .feedback-shell,
        :root:not(.lm) #mob-content .feedback-shell,
        .dm #mob-content .feedback-shell {
            --feedback-mobile-card: rgba(15, 23, 42, 0.96);
            --feedback-mobile-panel: rgba(30, 41, 59, 0.82);
            --feedback-mobile-field: rgba(15, 23, 42, 0.9);
            --feedback-mobile-border: rgba(148, 163, 184, 0.18);
            --feedback-mobile-title: #f8fafc;
            --feedback-mobile-muted: #94a3b8;
            --feedback-mobile-shadow: 0 16px 32px rgba(2, 6, 23, 0.34);
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero.feedback-hero {
            height: 69px !important;
            min-height: 69px !important;
            max-height: 69px !important;
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            padding: 8px 72px 8px 10px !important;
            overflow: hidden !important;
            border: 0 !important;
            border-radius: var(--feedback-mobile-radius) !important;
            background:
                radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
                radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
                linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
        }

        html[data-theme="dark"] #mob-content .feedback-shell #feedbackModulesLikeHero.feedback-hero,
        :root:not(.lm) #mob-content .feedback-shell #feedbackModulesLikeHero.feedback-hero,
        .dm #mob-content .feedback-shell #feedbackModulesLikeHero.feedback-hero {
            background:
                radial-gradient(circle at 94% 8%, rgba(255, 255, 255, 0.3), transparent 25%),
                radial-gradient(circle at 68% 86%, rgba(56, 189, 248, 0.22), transparent 28%),
                linear-gradient(112deg, #2563eb 0%, #1d7fe4 48%, #38a9dc 100%) !important;
            border-color: rgba(147, 197, 253, 0.48) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.18) !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero.feedback-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            display: block !important;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 26px 26px;
            opacity: 0.25;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-hero-copy {
            display: contents !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-chat-mark {
            box-sizing: border-box;
            position: relative;
            z-index: 2;
            width: 28px !important;
            height: 28px !important;
            padding: 6px !important;
            border-radius: var(--feedback-mobile-radius) !important;
            border: 1px solid rgba(255, 255, 255, 0.26) !important;
            background: rgba(15, 23, 42, 0.16) !important;
            color: #ffffff !important;
            box-shadow: none !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-hero-copy > div {
            position: relative;
            z-index: 2;
            min-width: 0;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-title {
            margin: 0 0 3px !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            font-size: 0.72rem !important;
            line-height: 1.15 !important;
            letter-spacing: 0 !important;
            text-transform: uppercase !important;
            white-space: nowrap !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-subtitle {
            margin: 0 !important;
            max-width: 13.5rem !important;
            color: rgba(255, 255, 255, 0.86) !important;
            font-size: 0.49rem !important;
            line-height: 1.32 !important;
            font-weight: 650 !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-hero-art {
            width: 72px !important;
            height: auto !important;
            right: -5px !important;
            bottom: -2px !important;
            top: auto !important;
            opacity: 0.96 !important;
            filter: drop-shadow(0 10px 16px rgba(15, 23, 42, 0.18));
            transform-origin: 50% 60%;
            animation: srFeedbackHeroFloat 5s ease-in-out infinite;
        }

        #mob-content .feedback-shell .premium-panel {
            padding: 12px !important;
            border-radius: var(--feedback-mobile-radius) !important;
            border: 1px solid var(--feedback-mobile-border) !important;
            background: var(--feedback-mobile-card) !important;
            box-shadow: var(--feedback-mobile-shadow) !important;
        }

        #mob-content .feedback-shell .feedback-history-head {
            display: block !important;
        }

        #mob-content .feedback-shell .feedback-history-title {
            display: flex !important;
            align-items: center !important;
            gap: 9px !important;
            margin: 0 0 10px !important;
            color: var(--feedback-mobile-title) !important;
            font-size: 0.93rem !important;
            line-height: 1.1 !important;
            font-weight: 900 !important;
            letter-spacing: 0 !important;
        }

        #mob-content .feedback-shell .feedback-history-title::before {
            content: "";
            flex: 0 0 30px;
            width: 30px;
            height: 30px;
            border-radius: var(--feedback-mobile-radius);
            background:
                linear-gradient(#ffffff, #ffffff) center 9px / 15px 2px no-repeat,
                linear-gradient(#ffffff, #ffffff) center 15px / 18px 2px no-repeat,
                linear-gradient(135deg, #2563eb, #14b8a6);
        }

        #mob-content .feedback-shell .feedback-history-title::after {
            content: "";
            min-width: 28px;
            height: 1px;
            flex: 1;
            background: linear-gradient(90deg, var(--feedback-mobile-border), transparent);
        }

        #mob-content .feedback-shell #feedback-filters {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            width: 100% !important;
            margin: 0 0 12px !important;
            padding: 8px !important;
            border: 1px solid var(--feedback-mobile-border) !important;
            border-radius: var(--feedback-mobile-radius) !important;
            background: var(--feedback-mobile-panel) !important;
        }

        #mob-content .feedback-shell #feedback-filters #scenarioFilter,
        #mob-content .feedback-shell #feedback-filters .feedback-search-wrap {
            grid-column: 1 / -1 !important;
        }

        #mob-content .feedback-shell #feedback-filters:not(:has(.feedback-clear-form)) #sortDateBtn {
            grid-column: 1 / -1 !important;
        }

        #mob-content .feedback-shell #feedback-filters .feedback-clear-form,
        #mob-content .feedback-shell #feedback-filters .feedback-clear-form .btn,
        #mob-content .feedback-shell #feedback-filters #sortDateBtn {
            width: 100% !important;
            min-width: 0 !important;
        }

        #mob-content .feedback-shell #feedback-filters .db-filter-input,
        #mob-content .feedback-shell #feedback-filters .btn,
        #mob-content .feedback-shell #feedback-filters .form-select {
            min-height: 38px !important;
            border-radius: 6px !important;
            border-color: var(--feedback-mobile-border) !important;
            background-color: var(--feedback-mobile-field) !important;
            color: var(--feedback-mobile-title) !important;
            font-size: 0.68rem !important;
            font-weight: 800 !important;
            letter-spacing: 0 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            box-shadow: none !important;
        }

        #mob-content .feedback-shell #feedback-filters .feedback-search-wrap {
            overflow: hidden !important;
            border-radius: 6px !important;
            background: var(--feedback-mobile-field) !important;
        }

        #mob-content .feedback-shell #feedback-filters .feedback-search-wrap .input-group-text {
            width: 36px !important;
            justify-content: center !important;
            color: var(--feedback-mobile-muted) !important;
        }

        #mob-content .feedback-shell #feedback-filters .feedback-search-wrap .form-control::placeholder {
            color: var(--feedback-mobile-muted) !important;
            opacity: 0.8;
        }

        #mob-content .feedback-shell .feedback-table-wrap {
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: transparent !important;
        }

        #mob-content .feedback-shell #feedbackTable {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 8px !important;
            margin: 0 !important;
        }

        #mob-content .feedback-shell #feedbackTable thead {
            display: none !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody,
        #mob-content .feedback-shell #feedbackTable tbody tr,
        #mob-content .feedback-shell #feedbackTable tbody td {
            width: 100% !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody tr {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(108px, auto) !important;
            gap: 7px 10px !important;
            padding: 11px !important;
            border: 1px solid var(--feedback-mobile-border) !important;
            border-radius: var(--feedback-mobile-radius) !important;
            background: var(--feedback-mobile-panel) !important;
            box-shadow: none !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody tr.feedback-empty-row {
            display: block !important;
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td {
            min-width: 0;
            display: flex !important;
            align-items: center !important;
            padding: 0 !important;
            border: 0 !important;
            color: var(--feedback-mobile-title) !important;
            text-align: left !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody tr.feedback-empty-row td {
            display: block !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(1) {
            grid-column: 1 / -1 !important;
            color: var(--feedback-mobile-muted) !important;
            font-size: 0.62rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            text-transform: uppercase !important;
            letter-spacing: 0 !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(1)::before {
            display: none !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(2) {
            grid-column: 1 / -1 !important;
            color: var(--feedback-mobile-title) !important;
            font-size: 0.82rem !important;
            font-weight: 900 !important;
            line-height: 1.25 !important;
            overflow-wrap: anywhere;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(3),
        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(4) {
            min-height: 42px;
            flex-direction: column;
            align-items: flex-start !important;
            justify-content: center !important;
            padding: 7px 8px !important;
            border-radius: 6px !important;
            background: var(--feedback-mobile-field) !important;
            font-size: 0.76rem !important;
            font-weight: 900 !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(3)::before,
        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(4)::before {
            margin-bottom: 2px;
            color: var(--feedback-mobile-muted) !important;
            font-size: 0.58rem !important;
            font-weight: 900 !important;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: 0 !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(3)::before {
            content: "Score";
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(4)::before {
            content: "Rating";
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) {
            grid-column: 1 / -1 !important;
            display: block !important;
        }

        #mob-content .feedback-shell #feedbackTable .badge {
            max-width: 100%;
            min-height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px !important;
            padding: 0 9px !important;
            font-size: 0.61rem !important;
            font-weight: 900 !important;
            line-height: 1.1 !important;
            white-space: normal !important;
            text-align: left;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 38px !important;
            gap: 8px !important;
            width: 100% !important;
            justify-content: stretch !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions > a,
        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions > form {
            width: 100% !important;
            min-width: 0 !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn {
            width: 100% !important;
            min-height: 38px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 7px !important;
            border-radius: 6px !important;
            font-size: 0.7rem !important;
            font-weight: 900 !important;
            letter-spacing: 0 !important;
            white-space: nowrap !important;
            box-shadow: none !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn i {
            margin: 0 !important;
            flex: 0 0 auto;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn-primary {
            border-color: transparent !important;
            background: linear-gradient(135deg, #2563eb, #0891b2) !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn-outline-danger {
            border-color: rgba(239, 68, 68, 0.2) !important;
            background: rgba(254, 242, 242, 0.9) !important;
            color: #dc2626 !important;
        }

        html[data-theme="dark"] #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn-outline-danger,
        :root:not(.lm) #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn-outline-danger,
        .dm #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn-outline-danger {
            background: rgba(127, 29, 29, 0.26) !important;
            color: #fecaca !important;
        }

        #mob-content .feedback-shell .feedback-history-delete-label {
            display: none !important;
        }

        #mob-content .feedback-shell .feedback-empty-state {
            min-height: 112px !important;
            padding: 18px 14px !important;
            border: 1px dashed var(--feedback-mobile-border) !important;
            border-radius: var(--feedback-mobile-radius) !important;
            background: var(--feedback-mobile-panel) !important;
            color: var(--feedback-mobile-muted) !important;
            font-size: 0.78rem !important;
            font-weight: 800 !important;
            line-height: 1.35 !important;
            text-align: center !important;
        }

        #mob-content .feedback-shell .feedback-empty-state i {
            color: #2563eb !important;
        }

        #mob-content .feedback-shell #feedbackPagination {
            justify-content: center !important;
            margin-top: 12px !important;
        }

        #mob-content .feedback-shell #feedbackPagination .pagination {
            gap: 6px;
            flex-wrap: wrap;
            justify-content: center;
        }

        #mob-content .feedback-shell #feedbackPagination .page-link {
            min-width: 34px !important;
            min-height: 34px !important;
            border-radius: var(--feedback-mobile-radius) !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-color: var(--feedback-mobile-border) !important;
            background: var(--feedback-mobile-field) !important;
            color: var(--feedback-mobile-title) !important;
            font-size: 0.68rem !important;
            font-weight: 900 !important;
            box-shadow: none !important;
        }
    }

    @media (max-width: 390px) {
        #mob-content .feedback-shell #feedbackModulesLikeHero.feedback-hero {
            height: 69px !important;
            min-height: 69px !important;
            max-height: 69px !important;
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-chat-mark {
            width: 28px !important;
            height: 28px !important;
            padding: 6px !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-title {
            font-size: 0.68rem !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-subtitle {
            max-width: 12rem !important;
            font-size: 0.46rem !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-hero-art {
            width: 66px !important;
            right: -5px !important;
            bottom: -2px !important;
        }

        #mob-content .feedback-shell .premium-panel {
            padding: 10px !important;
        }
    }

    @media (max-width: 360px) {
        #mob-content .feedback-shell #feedbackModulesLikeHero.feedback-hero {
            height: 69px !important;
            min-height: 69px !important;
            max-height: 69px !important;
            grid-template-columns: 27px minmax(0, 1fr) !important;
            padding-right: 62px !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-chat-mark {
            width: 27px !important;
            height: 27px !important;
            padding: 6px !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-title {
            font-size: 0.64rem !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-subtitle {
            max-width: 10.25rem !important;
            font-size: 0.43rem !important;
        }

        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-hero-art {
            width: 62px !important;
            right: -6px !important;
        }

        #mob-content .feedback-shell #feedback-filters {
            grid-template-columns: 1fr !important;
        }

        #mob-content .feedback-shell #feedback-filters #sortDateBtn,
        #mob-content .feedback-shell #feedback-filters .feedback-clear-form {
            grid-column: 1 / -1 !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody tr {
            grid-template-columns: 1fr !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(3),
        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(4) {
            grid-column: 1 / -1 !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions {
            grid-template-columns: minmax(0, 1fr) 36px !important;
        }

        #mob-content .feedback-shell #feedbackTable tbody td:nth-child(5) .feedback-history-actions .btn {
            min-height: 36px !important;
            font-size: 0.65rem !important;
        }
    }

    @media (max-width: 767px) and (prefers-reduced-motion: reduce) {
        #mob-content .feedback-shell #feedbackModulesLikeHero .feedback-hero-art {
            animation: none !important;
        }
    }

    @keyframes srFeedbackHeroFloat {
        0%, 100% {
            transform: translateY(0) rotate(-1deg);
        }

        50% {
            transform: translateY(-5px) rotate(1.5deg);
        }
    }

    body.user-desktop-shell .feedback-shell {
        --feedback-shell-gap: 10px;
        --feedback-shell-radius: 12px;
        --feedback-shell-border: rgba(15, 23, 42, 0.12);
        --feedback-shell-card: rgba(255, 255, 255, 0.98);
        --feedback-shell-field: rgba(248, 250, 252, 0.92);
        --feedback-shell-title: #0f172a;
        --feedback-shell-text: #334155;
        --feedback-shell-muted: #64748b;
        --feedback-shell-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 0 24px !important;
        color: var(--feedback-shell-title) !important;
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
        overflow-x: hidden !important;
    }

    html[data-theme="dark"] body.user-desktop-shell .feedback-shell,
    :root:not(.lm) body.user-desktop-shell .feedback-shell,
    body.user-desktop-shell.dm .feedback-shell,
    body.user-desktop-shell .dm .feedback-shell {
        --feedback-shell-border: rgba(148, 163, 184, 0.24);
        --feedback-shell-card: rgba(15, 23, 42, 0.96);
        --feedback-shell-field: rgba(30, 41, 59, 0.92);
        --feedback-shell-title: #f8fafc;
        --feedback-shell-text: #e2e8f0;
        --feedback-shell-muted: #cbd5e1;
        --feedback-shell-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
    }

    body.user-desktop-shell .feedback-shell > :is(.feedback-hero, .premium-panel) {
        margin: 0 0 var(--feedback-shell-gap) !important;
    }

    body.user-desktop-shell .feedback-shell > :last-child {
        margin-bottom: 0 !important;
    }

    body.user-desktop-shell #feedbackModulesLikeHero.feedback-hero {
        position: relative !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) 180px !important;
        align-items: center !important;
        min-height: 116px !important;
        height: auto !important;
        gap: 14px !important;
        padding: 18px 178px 18px 20px !important;
        border: 1px solid rgba(191, 219, 254, 0.86) !important;
        border-radius: var(--feedback-shell-radius) !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
        box-shadow: 0 10px 26px rgba(37, 99, 235, 0.12) !important;
        overflow: hidden !important;
    }

    body.user-desktop-shell #feedbackModulesLikeHero.feedback-hero::before {
        content: none !important;
        display: none !important;
    }

    body.user-desktop-shell #feedbackModulesLikeHero .feedback-hero-copy {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        min-width: 0 !important;
        padding: 0 !important;
    }

    body.user-desktop-shell #feedbackModulesLikeHero .feedback-chat-mark {
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        padding: 9px !important;
        border: 1px solid rgba(147, 197, 253, 0.42) !important;
        border-radius: 12px !important;
        background: rgba(239, 246, 255, 0.92) !important;
        color: #1d4ed8 !important;
        box-shadow: none !important;
        filter: none !important;
    }

    body.user-desktop-shell #feedbackModulesLikeHero .feedback-title {
        margin: 0 0 5px !important;
        color: var(--feedback-shell-title) !important;
        -webkit-text-fill-color: var(--feedback-shell-title) !important;
        background: none !important;
        font-size: clamp(1.12rem, 1.08vw, 1.45rem) !important;
        line-height: 1.12 !important;
        font-weight: 900 !important;
        text-transform: none !important;
        letter-spacing: 0 !important;
    }

    body.user-desktop-shell #feedbackModulesLikeHero .feedback-subtitle {
        max-width: 640px !important;
        margin: 0 !important;
        color: var(--feedback-shell-text) !important;
        font-size: 0.84rem !important;
        line-height: 1.42 !important;
        font-weight: 600 !important;
    }

    body.user-desktop-shell #feedbackModulesLikeHero .feedback-hero-art {
        display: block !important;
        position: absolute !important;
        right: 12px !important;
        bottom: -10px !important;
        width: clamp(140px, 12vw, 174px) !important;
        min-width: 0 !important;
        max-width: none !important;
        opacity: 0.96 !important;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16)) !important;
        animation: none !important;
    }

    body.user-desktop-shell .feedback-shell .premium-panel {
        padding: 14px !important;
        border: 1px solid var(--feedback-shell-border) !important;
        border-radius: var(--feedback-shell-radius) !important;
        background: var(--feedback-shell-card) !important;
        color: var(--feedback-shell-title) !important;
        box-shadow: var(--feedback-shell-shadow) !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    body.user-desktop-shell .feedback-shell .feedback-history-head {
        display: grid !important;
        grid-template-columns: minmax(0, 180px) minmax(0, 1fr) !important;
        gap: 10px !important;
        align-items: start !important;
        margin-bottom: 10px !important;
    }

    body.user-desktop-shell .feedback-shell .feedback-history-title {
        margin: 0 !important;
        color: var(--feedback-shell-title) !important;
        font-size: 0.94rem !important;
        line-height: 1.18 !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
    }

    body.user-desktop-shell .feedback-shell #feedback-filters {
        display: grid !important;
        grid-template-columns: minmax(160px, 1fr) 116px minmax(220px, 1.5fr) auto !important;
        gap: 8px !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    body.user-desktop-shell .feedback-shell #feedback-filters .feedback-clear-form,
    body.user-desktop-shell .feedback-shell #feedback-filters .feedback-search-wrap {
        grid-column: auto !important;
    }

    body.user-desktop-shell .feedback-shell #feedback-filters .feedback-search-wrap {
        order: 3 !important;
    }

    body.user-desktop-shell .feedback-shell #feedback-filters .feedback-clear-form {
        order: 4 !important;
    }

    body.user-desktop-shell .feedback-shell #feedback-filters :is(.db-filter-input, .btn, .form-select),
    body.user-desktop-shell .feedback-shell #feedback-filters .feedback-search-wrap {
        min-height: 34px !important;
        height: 34px !important;
        padding: 6px 9px !important;
        border: 1px solid var(--feedback-shell-border) !important;
        border-radius: 9px !important;
        background: var(--feedback-shell-field) !important;
        color: var(--feedback-shell-text) !important;
        box-shadow: none !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
        line-height: 1.1 !important;
    }

    body.user-desktop-shell .feedback-shell #feedback-filters .feedback-search-wrap {
        display: flex !important;
        align-items: center !important;
        overflow: hidden !important;
    }

    body.user-desktop-shell .feedback-shell #feedback-filters .feedback-search-wrap .input-group-text {
        padding: 0 7px 0 2px !important;
        color: var(--feedback-shell-muted) !important;
        font-size: 0.72rem !important;
    }

    body.user-desktop-shell .feedback-shell #feedback-filters .feedback-search-wrap .form-control {
        min-height: 0 !important;
        height: auto !important;
        padding: 0 !important;
        color: var(--feedback-shell-text) !important;
        font-size: 0.68rem !important;
        font-weight: 800 !important;
    }

    body.user-desktop-shell .feedback-shell .feedback-empty-state {
        width: 100% !important;
        min-height: 96px !important;
        margin: 0 !important;
        justify-content: center !important;
        border: 1px dashed var(--feedback-shell-border) !important;
        border-radius: 10px !important;
        background: var(--feedback-shell-field) !important;
        color: var(--feedback-shell-muted) !important;
        font-size: 0.72rem !important;
    }

    body.user-desktop-shell .feedback-shell .feedback-table-wrap {
        margin: 0 !important;
        overflow-x: auto !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackTable {
        margin: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 6px !important;
        color: var(--feedback-shell-text) !important;
        font-size: 0.72rem !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackTable thead {
        display: table-header-group !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackTable thead th {
        padding: 7px 9px !important;
        border: 0 !important;
        color: var(--feedback-shell-muted) !important;
        font-size: 0.58rem !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        letter-spacing: 0 !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackTable tbody tr {
        display: table-row !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackTable tbody td {
        display: table-cell !important;
        padding: 9px !important;
        border-top: 1px solid var(--feedback-shell-border) !important;
        border-bottom: 1px solid var(--feedback-shell-border) !important;
        background: var(--feedback-shell-field) !important;
        color: var(--feedback-shell-text) !important;
        vertical-align: middle !important;
        font-size: 0.72rem !important;
        line-height: 1.2 !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackTable tbody td:first-child {
        border-left: 1px solid var(--feedback-shell-border) !important;
        border-radius: 10px 0 0 10px !important;
        white-space: nowrap !important;
        color: var(--feedback-shell-muted) !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackTable tbody td:last-child {
        border-right: 1px solid var(--feedback-shell-border) !important;
        border-radius: 0 10px 10px 0 !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackTable tbody td::before {
        content: none !important;
        display: none !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackTable .badge {
        padding: 5px 8px !important;
        border-radius: 999px !important;
        font-size: 0.58rem !important;
        line-height: 1 !important;
        font-weight: 900 !important;
        white-space: nowrap !important;
    }

    body.user-desktop-shell .feedback-shell .feedback-history-actions {
        display: flex !important;
        justify-content: flex-end !important;
        gap: 8px !important;
        flex-wrap: nowrap !important;
    }

    body.user-desktop-shell .feedback-shell .feedback-history-actions .btn {
        min-height: 30px !important;
        padding: 6px 9px !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        font-size: 0.62rem !important;
        font-weight: 900 !important;
        line-height: 1.1 !important;
        white-space: nowrap !important;
    }

    body.user-desktop-shell .feedback-shell #feedbackPagination {
        margin-top: 10px !important;
        justify-content: flex-end !important;
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
            <form id="feedbackFilterForm" action="{{ route('user.feedback') }}" method="GET" class="d-none"></form>
            <input form="feedbackFilterForm" type="hidden" name="sort" value="{{ $feedbackFilters['sort'] ?? 'desc' }}">
            <div id="feedback-filters">
                <select id="scenarioFilter" name="scenario" form="feedbackFilterForm" class="form-select db-filter-input">
                    <option value="">All Scenarios</option>
                    @foreach($feedbackCategories as $category)
                        <option value="{{ $category }}" @selected(($feedbackFilters['scenario'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                @php
                    $nextFeedbackSort = ($feedbackFilters['sort'] ?? 'desc') === 'desc' ? 'asc' : 'desc';
                    $feedbackSortQuery = array_filter([
                        'scenario' => $feedbackFilters['scenario'] ?? '',
                        'search' => $feedbackFilters['search'] ?? '',
                        'sort' => $nextFeedbackSort,
                    ], fn ($value) => filled($value));
                @endphp
                <a class="btn btn-outline-secondary" id="sortDateBtn" href="{{ route('user.feedback', $feedbackSortQuery) }}">
                    <i class="fa-solid {{ ($feedbackFilters['sort'] ?? 'desc') === 'desc' ? 'fa-arrow-down-short-wide' : 'fa-arrow-up-wide-short' }} me-2"></i>
                    {{ ($feedbackFilters['sort'] ?? 'desc') === 'desc' ? 'Newest First' : 'Oldest First' }}
                </a>
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
                    <input type="text" id="feedbackSearch" name="search" form="feedbackFilterForm" class="form-control border-0" placeholder="Search feedback..." value="{{ $feedbackFilters['search'] ?? '' }}">
                </div>
            </div>
        </div>

        @if($sessions->count() == 0)
            <div class="feedback-empty-state">
                <i class="fa-solid fa-message" aria-hidden="true"></i>
                {{ $hasFeedbackRecords ? 'No feedback records match your current filters.' : 'Complete a practice interview to generate feedback.' }}
            </div>
        @else
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
                </tbody>
            </table>
        </div>
        @endif
        
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
        const filterForm = document.getElementById('feedbackFilterForm');
        let searchTimer = null;

        function submitFilters() {
            if (!filterForm) return;
            filterForm.submit();
        }

        if (scenarioFilter) {
            scenarioFilter.addEventListener('change', submitFilters);
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(submitFilters, 450);
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
