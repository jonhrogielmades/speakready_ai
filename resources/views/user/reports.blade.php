@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Reports')

@section('content')
<!-- Add print styles specifically for this Philippines interview report -->
<style>
    @media print {
        body { background: #fff !important; }
        .sidebar, .navbar, .btn-no-print { display: none !important; }
        .db-section { padding: 0 !important; margin: 0 !important; }
        .print-card {
            background: #fff !important;
            border: 1px solid #ccc !important;
            break-inside: avoid;
            box-shadow: none !important;
            margin-bottom: 20px !important;
        }
        .text-white { color: #000 !important; }
        canvas { max-width: 100% !important; height: auto !important; }
        h4, h5, h6 { color: #000 !important; }
    }

    @media screen {
        .report-print-identity {
            display: none !important;
        }
        .print-card {
            background: var(--sf) !important;
            border: 1px solid var(--bd) !important;
            border-radius: 24px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .print-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08) !important;
        }
        #report-readiness {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(16, 185, 129, 0.1)) !important;
            border: 1px solid rgba(59, 130, 246, 0.2) !important;
        }
    }

    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }

    @media (max-width: 767px) {
        #portfolioReport .report-export-actions {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 7px !important;
            width: 100% !important;
            align-items: stretch !important;
        }
        #portfolioReport .report-export-actions > button {
            width: 100% !important;
            height: 38px !important;
            min-height: 38px !important;
            padding: 6px 5px !important;
            font-size: 0.62rem !important;
            line-height: 1.15 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 5px !important;
            white-space: normal !important;
            text-align: center !important;
        }
        #portfolioReport .report-export-actions > button i {
            margin: 0 !important;
            font-size: 0.78rem !important;
        }
        #portfolioReport .report-export-actions > form {
            grid-column: auto !important;
            width: 100% !important;
        }
    }
</style>
@include('partials.page-hero-styles')
<style>
    #portfolioReport {
        max-width: 1040px;
        margin-inline: auto;
    }
    #portfolioReport .sr-page-hero {
        --reports-hero-title: #1d4ed8;
        --reports-hero-text: #334155;
        --reports-icon-bg: rgba(239, 246, 255, 0.92);
        --reports-icon-border: rgba(147, 197, 253, 0.42);
        display: grid !important;
        grid-template-columns: 40px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 10px !important;
        min-height: 78px !important;
        height: auto !important;
        border-radius: 16px;
        margin-bottom: 12px;
        padding: 8px 108px 8px 12px !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
        border-color: rgba(191, 219, 254, 0.86);
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
    }
    :root:not(.lm) #portfolioReport .sr-page-hero,
    .dm #portfolioReport .sr-page-hero {
        --reports-hero-title: #93c5fd;
        --reports-hero-text: #e2e8f0;
        --reports-icon-bg: rgba(59, 130, 246, 0.2);
        --reports-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28);
    }
    #portfolioReport .sr-page-hero::after {
        width: min(34%, 240px);
        background: linear-gradient(90deg, transparent, rgba(96, 165, 250, 0.12));
    }
    #portfolioReport .sr-page-hero-inner,
    #portfolioReport .sr-page-hero-copy {
        display: contents !important;
        min-height: 0 !important;
        padding: 0 !important;
    }
    #portfolioReport .reports-hero-icon {
        box-sizing: border-box;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 30px !important;
        height: 30px !important;
        border: 1px solid var(--reports-icon-border) !important;
        border-radius: 10px !important;
        background: var(--reports-icon-bg) !important;
        color: var(--reports-hero-title) !important;
        font-size: 0.82rem !important;
    }
    #portfolioReport .sr-page-hero-title {
        display: block !important;
        color: var(--reports-hero-title) !important;
        background: none !important;
        -webkit-text-fill-color: var(--reports-hero-title) !important;
        font-size: 0.95rem !important;
        line-height: 1.08 !important;
        margin: 0 0 2px !important;
        max-width: 14rem;
        font-weight: 950 !important;
        text-transform: uppercase;
    }
    #portfolioReport .sr-page-hero-title svg {
        display: none;
    }
    #portfolioReport .sr-page-hero-subtitle {
        max-width: 13rem;
        font-size: 0.65rem !important;
        line-height: 1.22;
        color: var(--reports-hero-text) !important;
        font-weight: 500;
    }
    #portfolioReport .sr-page-hero-art {
        right: 8px !important;
        bottom: 0 !important;
        width: 82px !important;
        height: auto !important;
        opacity: 0.96;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16));
    }
    #portfolioReport .report-export-actions {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 16px;
        width: 100%;
    }
    #portfolioReport .report-export-actions .btn {
        width: 100%;
        min-height: 40px;
        min-width: 0;
        border: 0;
        border-radius: 12px !important;
        padding: 7px 9px;
        font-size: 0.78rem;
        line-height: 1.1;
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.12);
        white-space: nowrap;
    }
    #portfolioReport .report-export-actions .btn i {
        font-size: 0.82rem;
        margin-right: 5px !important;
    }
    #portfolioReport .report-export-actions > form {
        width: 100%;
    }
    #portfolioReport .report-empty-card {
        max-width: 100%;
        border-radius: 22px !important;
        padding: clamp(28px, 5vw, 44px) clamp(20px, 6vw, 56px) !important;
        box-shadow: 0 18px 46px rgba(15, 23, 42, 0.08) !important;
    }
    #portfolioReport .report-empty-art {
        width: min(150px, 38vw);
        height: auto;
        margin-bottom: 18px;
        filter: drop-shadow(0 16px 24px rgba(37, 99, 235, 0.18));
    }
    #portfolioReport .report-empty-title {
        font-size: clamp(1.45rem, 4.8vw, 2.2rem);
        line-height: 1.12;
        max-width: 520px;
        margin-inline: auto;
    }
    #portfolioReport .report-empty-copy {
        font-size: clamp(0.95rem, 3.6vw, 1.12rem);
        line-height: 1.55;
    }
    #portfolioReport .report-start-btn {
        width: min(100%, 560px);
        min-height: 62px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        border-radius: 18px !important;
        font-size: clamp(0.98rem, 3.6vw, 1.15rem);
        box-shadow: 0 18px 34px rgba(37, 99, 235, 0.22);
    }

    @media (max-width: 767px) {
        #portfolioReport {
            max-width: 100%;
        }
        #portfolioReport .sr-page-hero {
            min-height: 72px !important;
            height: auto !important;
            grid-template-columns: 36px minmax(0, 1fr) !important;
            gap: 8px !important;
            border-radius: 16px;
            margin-bottom: 10px;
            padding: 7px 88px 7px 10px !important;
        }
        #portfolioReport .sr-page-hero-title {
            font-size: 0.9rem !important;
            line-height: 1.08;
            margin-bottom: 2px !important;
            max-width: 11.5rem;
        }
        #portfolioReport .reports-hero-icon {
            width: 30px !important;
            height: 30px !important;
            font-size: 0.8rem !important;
        }
        #portfolioReport .sr-page-hero-subtitle {
            font-size: 0.64rem !important;
            line-height: 1.2;
            max-width: 11.5rem;
        }
        #portfolioReport .sr-page-hero-art {
            right: 6px !important;
            bottom: 0 !important;
            width: 76px !important;
            height: auto !important;
        }
        #portfolioReport .report-export-actions {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 6px !important;
            margin-bottom: 14px;
        }
        #portfolioReport .report-export-actions .btn {
            min-width: 0;
            min-height: 38px !important;
            height: 38px !important;
            border-radius: 12px !important;
            padding: 6px 5px !important;
            font-size: 0.62rem !important;
            line-height: 1.15 !important;
        }
        #portfolioReport .report-export-actions .btn i {
            font-size: 0.72rem !important;
        }
        #portfolioReport .report-empty-card {
            padding: 26px 16px !important;
            border-radius: 18px !important;
        }
        #portfolioReport .report-empty-art {
            width: min(118px, 36vw);
            margin-bottom: 14px;
        }
        #portfolioReport .report-start-btn {
            min-height: 54px;
            border-radius: 16px !important;
        }
    }

    @media (max-width: 374px) {
        #portfolioReport .sr-page-hero {
            min-height: 68px !important;
            height: auto !important;
            padding: 6px 72px 6px 10px !important;
        }
        #portfolioReport .sr-page-hero-title {
            font-size: 0.8rem !important;
            max-width: 10.2rem;
        }
        #portfolioReport .sr-page-hero-subtitle {
            font-size: 0.58rem !important;
            max-width: 10.2rem;
        }
        #portfolioReport .sr-page-hero-art {
            width: 64px !important;
            right: 4px !important;
        }
        #portfolioReport .report-export-actions .btn {
            gap: 6px !important;
            padding-inline: 7px !important;
        }
    }

    @media (max-width: 991px) {
        #portfolioReport {
            --reports-saas-radius: 12px;
            --reports-saas-gap: 8px;
            --reports-saas-border: rgba(37, 99, 235, 0.14);
            --reports-saas-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            --reports-saas-card: rgba(248, 250, 252, 0.78);
            --reports-saas-muted: #475569;
            max-width: 100% !important;
            padding-inline: 0 !important;
            padding-bottom: 14px !important;
        }
        html[data-theme="dark"] #portfolioReport,
        :root:not(.lm) #portfolioReport,
        .dm #portfolioReport {
            --reports-saas-border: rgba(147, 197, 253, 0.18);
            --reports-saas-shadow: 0 12px 26px rgba(0, 0, 0, 0.26);
            --reports-saas-card: rgba(255, 255, 255, 0.045);
            --reports-saas-muted: #cbd5e1;
        }
        #portfolioReport .sr-page-hero {
            min-height: 84px !important;
            grid-template-columns: 34px minmax(0, 1fr) !important;
            gap: 9px !important;
            padding: 10px 76px 11px 12px !important;
            margin: 0 0 var(--reports-saas-gap) !important;
            border-radius: var(--reports-saas-radius) !important;
            border-color: var(--reports-saas-border) !important;
            box-shadow: var(--reports-saas-shadow) !important;
            overflow: hidden !important;
        }
        #portfolioReport .reports-hero-icon {
            width: 32px !important;
            height: 32px !important;
            border-radius: 10px !important;
            font-size: 0.82rem !important;
        }
        #portfolioReport .sr-page-hero-title {
            font-size: 0.86rem !important;
            line-height: 1.12 !important;
            max-width: 12rem !important;
            margin-bottom: 4px !important;
            white-space: normal !important;
        }
        #portfolioReport .sr-page-hero-subtitle {
            max-width: 12rem !important;
            max-height: 2.7em !important;
            overflow: hidden !important;
            font-size: 0.62rem !important;
            line-height: 1.34 !important;
        }
        #portfolioReport .sr-page-hero-art {
            width: 70px !important;
            right: -5px !important;
            bottom: 6px !important;
        }
        #portfolioReport .report-export-actions {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 6px !important;
            margin-bottom: var(--reports-saas-gap) !important;
        }
        #portfolioReport .report-export-actions .btn {
            min-height: 36px !important;
            height: 36px !important;
            padding: 6px 5px !important;
            border-radius: 10px !important;
            font-size: 0.58rem !important;
            line-height: 1.1 !important;
            box-shadow: none !important;
            white-space: normal !important;
        }
        #portfolioReport .report-export-actions .btn i {
            font-size: 0.68rem !important;
            margin-right: 4px !important;
        }
        #portfolioReport > .row,
        #portfolioReport .row.g-4,
        #portfolioReport .row.mb-4 {
            --bs-gutter-x: 0;
            --bs-gutter-y: var(--reports-saas-gap);
            margin-bottom: var(--reports-saas-gap) !important;
        }
        #portfolioReport .col-lg-7,
        #portfolioReport .col-lg-5,
        #portfolioReport .col-md-8,
        #portfolioReport .col-md-4,
        #portfolioReport .col-md-6,
        #portfolioReport .col-12 {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        #portfolioReport .print-card {
            padding: 10px !important;
            border-radius: var(--reports-saas-radius) !important;
            border-color: var(--reports-saas-border) !important;
            background: var(--sf) !important;
            box-shadow: var(--reports-saas-shadow) !important;
            transform: none !important;
            margin-bottom: 0 !important;
        }
        #portfolioReport .print-card:hover {
            transform: none !important;
        }
        #portfolioReport #report-readiness {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(16, 185, 129, 0.07)) !important;
        }
        #portfolioReport #report-readiness .row {
            --bs-gutter-y: 8px;
            text-align: left !important;
        }
        #portfolioReport #report-readiness [class*="col-"] {
            border-right: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin-top: 0 !important;
        }
        #portfolioReport h4,
        #portfolioReport h5,
        #portfolioReport h6 {
            line-height: 1.18 !important;
            letter-spacing: 0 !important;
        }
        #portfolioReport h5 {
            margin-bottom: 10px !important;
            font-size: 0.9rem !important;
        }
        #portfolioReport h6 {
            margin-bottom: 6px !important;
            font-size: 0.66rem !important;
        }
        #portfolioReport p,
        #portfolioReport li,
        #portfolioReport td,
        #portfolioReport small {
            font-size: 0.68rem !important;
            line-height: 1.34 !important;
        }
        #portfolioReport .bg-light.bg-opacity-10 {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px !important;
            padding: 9px !important;
            margin-bottom: 10px !important;
            border-radius: 10px !important;
            background: var(--reports-saas-card) !important;
            border: 1px solid var(--reports-saas-border);
        }
        #portfolioReport .bg-light.bg-opacity-10 > [class*="col-"] {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        #portfolioReport .row.g-3.text-center {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px !important;
        }
        #portfolioReport .row.g-3.text-center > .col {
            width: 100% !important;
            padding: 0 !important;
        }
        #portfolioReport .row.g-3.text-center [style*="width:60px"] {
            width: 48px !important;
            height: 48px !important;
            margin-bottom: 6px !important;
            font-size: 0.9rem !important;
        }
        #portfolioReport .table-responsive {
            border: 1px solid var(--reports-saas-border);
            border-radius: 10px;
            overflow-x: auto;
        }
        #portfolioReport table {
            min-width: 520px;
            margin-bottom: 0 !important;
        }
        #portfolioReport :is(#trendChart, #catChart) {
            max-height: 180px !important;
        }
        #portfolioReport [style*="height:250px"] {
            height: 180px !important;
        }
        #portfolioReport #report-feedback .row.g-4 {
            --bs-gutter-y: 8px;
        }
        #portfolioReport #report-feedback .p-3,
        #portfolioReport .print-card .p-3 {
            padding: 9px !important;
            border-radius: 10px !important;
        }
        #portfolioReport .col-md-6.d-flex.flex-column.gap-4 {
            gap: var(--reports-saas-gap) !important;
        }
        #portfolioReport .row.text-center.align-items-center.h-100 {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0 !important;
        }
        #portfolioReport .row.text-center.align-items-center.h-100 > .col-4 {
            width: 100% !important;
            padding: 0 4px !important;
        }
        #portfolioReport #report-learning .row {
            --bs-gutter-y: 8px;
        }
        #portfolioReport #report-learning ul {
            font-size: 0.72rem !important;
        }
        #portfolioReport .d-flex.flex-wrap.gap-4 {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px !important;
            justify-content: stretch !important;
        }
        #portfolioReport .d-flex.flex-wrap.gap-4 > .text-center {
            width: 100% !important;
        }
        #portfolioReport .d-flex.flex-wrap.gap-4 [style*="width:80px"] {
            width: 52px !important;
            height: 52px !important;
            margin-bottom: 6px !important;
        }
        #portfolioReport .report-empty-card {
            padding: 20px 12px !important;
            border-radius: var(--reports-saas-radius) !important;
        }
        #portfolioReport .report-empty-art {
            width: min(104px, 34vw) !important;
            margin-bottom: 12px !important;
        }
        #portfolioReport .report-empty-title {
            font-size: 1.08rem !important;
            line-height: 1.16 !important;
        }
        #portfolioReport .report-empty-copy {
            margin-bottom: 14px !important;
            font-size: 0.74rem !important;
            line-height: 1.42 !important;
        }
        #portfolioReport .report-start-btn {
            min-height: 42px !important;
            border-radius: 10px !important;
            font-size: 0.78rem !important;
            box-shadow: none !important;
        }
    }
    /* Final compact hero override shared across user pages. */
    #portfolioReport .sr-page-hero {
        grid-template-columns: 30px minmax(0, 1fr) !important;
        gap: 8px !important;
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin-bottom: 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
    }
    #portfolioReport .reports-hero-icon {
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
    }
    #portfolioReport .sr-page-hero-title {
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin: 0 0 3px !important;
        white-space: nowrap !important;
    }
    #portfolioReport .sr-page-hero-subtitle {
        max-width: 13.5rem !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
    }
    #portfolioReport .sr-page-hero-art {
        right: -5px !important;
        bottom: -2px !important;
        width: 72px !important;
    }
    @media (max-width: 390px) {
        #portfolioReport .sr-page-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }
        #portfolioReport .reports-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }
        #portfolioReport .sr-page-hero-title {
            font-size: 0.68rem !important;
        }
        #portfolioReport .sr-page-hero-subtitle {
            font-size: 0.46rem !important;
        }
        #portfolioReport .sr-page-hero-art {
            width: 66px !important;
        }
    }
</style>

<div class="db-section active animate-fade-up" id="portfolioReport">
    <!-- Feature 10: Interview Portfolio Report Header & Actions -->
    <div class="sr-page-hero btn-no-print">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="reports-hero-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h10l4 4v14H5V3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M15 3v5h5M8 13h8M8 17h5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Interview Reports
                    </h4>
                    <p class="sr-page-hero-subtitle">Review readiness, feedback, and progress.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="reportPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="reportBlue" x1="62" y1="42" x2="164" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="42" y="18" width="128" height="116" rx="16" fill="url(#reportPanel)" stroke="#BFDBFE" stroke-width="3"/><path d="M138 18v30h32" fill="#BAE6FD"/><path d="M68 63h74M68 81h62M68 99h34" stroke="#93C5FD" stroke-width="7" stroke-linecap="round"/><path d="M76 118l18-18 15 10 25-30" fill="none" stroke="url(#reportBlue)" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="164" cy="48" r="17" fill="#22C55E"/><path d="M157 48l5 5 10-12" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="sr-page-actions report-export-actions btn-no-print">
        <button class="btn btn-primary btn-shine" id="exportPdfBtn" style="border-radius:12px;font-weight:600;"><i class="fa-solid fa-file-pdf me-2"></i>Export as PDF</button>
        <button class="btn btn-success btn-shine" id="exportExcelBtn" style="border-radius:12px;font-weight:600;"><i class="fa-solid fa-file-excel me-2"></i>Export as Excel</button>
        @if($sessions->count() > 0)
            <form action="{{ route('user.sessions.clear') }}" method="POST" onsubmit="return confirm('Clear all completed interview sessions? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-shine" style="border-radius:12px;font-weight:600;width:100%;">
                    <i class="fa-solid fa-trash-can me-2"></i>Clear Sessions
                </button>
            </form>
        @endif
    </div>

    <!-- Print Header visible only when printing or mimicking paper -->
    <div class="report-print-identity d-flex align-items-center mb-4 gap-3">
        <div style="width:60px;height:60px;background:var(--pur);border-radius:50%;display:flex;justify-content:center;align-items:center;">
            <i class="fa-solid fa-user-graduate text-white fs-3"></i>
        </div>
        <div>
            <h3 class="text-gradient-primary" style="margin:0;font-weight:800;letter-spacing:-0.5px;">{{ $user->name ?? 'Candidate' }}</h3>
            <p style="color:var(--tx3);margin:0;">SpeakReady AI Philippines Interview Report &bull; Generated on {{ now()->format('F j, Y') }}</p>
        </div>
    </div>

    @if($hasScoreData)
    <!-- Feature 6: Readiness Assessment Report -->
    <div id="report-readiness" class="print-card mb-4" style="border-radius:24px; padding:32px;">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-md-3 border-end" style="border-color:rgba(59, 130, 246, 0.2) !important;">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:8px;">Readiness Score</h6>
                <div style="font-size:3.5rem;font-weight:900;line-height:1;color:{{ $readinessSummary->color }};">{{ $readinessSummary->current }}<span style="font-size:1.5rem">%</span></div>
                <div class="badge mt-2 fs-6" style="background-color:{{ $readinessSummary->color }};color:#fff;">{{ $readinessSummary->rating }}</div>
            </div>
            <div class="col-md-3 border-end mt-4 mt-md-0" style="border-color:rgba(59, 130, 246, 0.2) !important;">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:8px;">Previous Score</h6>
                <div style="font-size:2rem;font-weight:700;line-height:1;color:var(--tx);">{{ $readinessSummary->previous === null ? 'N/A' : $readinessSummary->previous . '%' }}</div>
            </div>
            <div class="col-md-6 mt-4 mt-md-0 ps-md-4">
                <h6 style="color:var(--tx3);text-transform:uppercase;font-weight:700;letter-spacing:1px;margin-bottom:8px;">Readiness Change</h6>
                <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start">
                    <i class="fa-solid {{ $readinessSummary->delta === null ? 'fa-minus' : ($readinessSummary->delta >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down') }} fs-1" style="color:{{ $readinessSummary->delta_color }};"></i>
                    <div style="font-size:2.5rem;font-weight:800;color:{{ $readinessSummary->delta_color }};">{{ $readinessSummary->delta_label }}</div>
                </div>
                <p style="color:var(--tx);margin-top:8px;font-size:0.95rem;">{{ $readinessSummary->message }}</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 1: Interview Performance Report -->
        <div class="col-lg-7">
            <div class="print-card" style="padding:32px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-clipboard-check text-primary me-2"></i>Latest Interview Performance</h5>

                <div class="row mb-4 bg-light bg-opacity-10 rounded p-3" style="background:var(--bg);">
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Scenario</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestScenarioLabel }}</div>
                    </div>
                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Date</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestSession->created_at->format('M d, Y') }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Difficulty</small>
                        <div style="color:var(--tx);font-weight:bold;text-transform:capitalize;">{{ $latestSession->difficulty ?? 'Not recorded' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <small style="color:var(--tx3);font-weight:600;text-transform:uppercase;">Questions</small>
                        <div style="color:var(--tx);font-weight:bold;">{{ $latestSession->num_questions ?? 'N/A' }}</div>
                    </div>
                </div>

                <h6 style="color:var(--tx);font-weight:bold;margin-bottom:16px;">Performance Breakdown</h6>
                <div class="row g-3 text-center">
                    @foreach($latestPerformanceMetrics as $metric)
                    <div class="col">
                        <div style="width:60px;height:60px;border-radius:50%;background:rgba(59,130,246,0.1);border:2px solid #3b82f6;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;color:var(--tx);font-weight:bold;font-size:1.1rem;">
                            {{ $metric['score'] }}
                        </div>
                        <div style="font-size:0.8rem;color:var(--tx3);font-weight:600;">{{ $metric['name'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Feature 8: Performance Comparison Report -->
        <div class="col-lg-5">
            <div id="report-comparison" class="print-card" style="padding:32px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-code-compare text-warning me-2"></i>Performance Comparison</h5>
                <p style="color:var(--tx3);font-size:0.9rem;">Comparing First Interview vs. Latest Interview</p>

                @if(count($comparisonRows) > 0)
                <div class="table-responsive">
                    <table class="table table-borderless table-sm align-middle" style="color:var(--tx); background: transparent; --bs-table-bg: transparent; --bs-table-color: var(--tx);">
                      <thead style="border-bottom:1px solid var(--bd);">
                          <tr>
                              <th class="text-uppercase" style="font-size:0.8rem;color:var(--tx3);">Metric</th>
                              <th class="text-uppercase text-center" style="font-size:0.8rem;color:var(--tx3);">First Score</th>
                              <th class="text-uppercase text-center" style="font-size:0.8rem;color:var(--tx3);">Latest Score</th>
                              <th class="text-uppercase text-end" style="font-size:0.8rem;color:var(--tx3);">Trend</th>
                          </tr>
                      </thead>
                    <tbody>
                        @foreach($comparisonRows as $row)
                        <tr>
                            <td class="fw-bold">{{ $row['label'] }}</td>
                            <td class="text-center">{{ $row['previous'] }}%</td>
                            <td class="text-center text-primary fw-bold">{{ $row['current'] }}%</td>
                            <td class="text-end {{ $row['delta'] >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="fa-solid {{ $row['delta'] >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} me-1"></i>{{ abs($row['delta']) }}%
                            </td>
                        </tr>
                        @endforeach
                      </tbody>
                  </table>
                </div>
                @else
                <div class="text-center py-4" style="color:var(--tx3);">
                    <p>Complete at least 2 Philippines practice interviews to view performance comparison.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 2: Feedback Summary Report -->
    <div class="row mb-4">
        <div class="col-12">
            <div id="report-feedback" class="print-card" style="padding:32px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-comment-dots text-info me-2"></i>Feedback Summary Report</h5>
                @if($feedbackSummary->has_data)
                @php
                    $strengths = $feedbackSummary->strengths ?: ['None identified yet'];
                    $weaknesses = $feedbackSummary->weaknesses ?: ['None identified yet'];
                    $primaryRecommendation = ($feedbackSummary->weaknesses[0] ?? null)
                        ? 'Focus on your ' . strtolower($feedbackSummary->weaknesses[0])
                        : 'Maintain your strongest interview skills';
                @endphp
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(16,185,129,0.05);border-radius:12px;border:1px solid rgba(16,185,129,0.2);height:100%;">
                            <h6 style="color:#10b981;font-weight:bold;"><i class="fa-solid fa-check-circle me-2"></i>Strengths</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                @foreach($strengths as $s)
                                <li>{{ $s }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(239,68,68,0.05);border-radius:12px;border:1px solid rgba(239,68,68,0.2);height:100%;">
                            <h6 style="color:#ef4444;font-weight:bold;"><i class="fa-solid fa-circle-xmark me-2"></i>Areas for Improvement</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                @foreach($weaknesses as $w)
                                <li>{{ $w }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3" style="background:rgba(59,130,246,0.05);border-radius:12px;border:1px solid rgba(59,130,246,0.2);height:100%;">
                            <h6 style="color:#3b82f6;font-weight:bold;"><i class="fa-solid fa-lightbulb me-2"></i>Recommended Practice</h6>
                            <ul style="color:var(--tx);font-size:0.9rem;padding-left:20px;line-height:1.8;">
                                <li>{{ $primaryRecommendation }}</li>
                                <li>Review your latest Philippines interview feedback</li>
                                <li>Complete one focused voice rehearsal</li>
                            </ul>
                        </div>
                    </div>
                </div>
                @else
                <div class="text-center py-4" style="color:var(--tx3);">
                    <p>Complete an interview to see your AI feedback summary.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Feature 3: Progress Report Charts -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-chart-line text-success me-2"></i>Readiness Score Trend</h5>
                <div style="height:250px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-chart-bar text-primary me-2"></i>Scenario Performance</h5>
                <div style="height:250px;">
                    <canvas id="catChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Feature 7: Skill Analysis Report -->
        <div class="col-md-6">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;height:100%;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-crosshairs text-danger me-2"></i>Skill Analysis Report</h5>
                @php
                    $skillRows = array_values(array_filter($comparisonRows, fn($row) => $row['label'] !== 'Overall Score'));
                @endphp
                @if(count($skillRows) > 0)
                @foreach($skillRows as $sk)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:0.9rem;">
                        <span style="color:var(--tx);font-weight:600;">{{ $sk['label'] }}</span>
                        <span style="color:var(--tx3)">{{ $sk['current'] }}% <span class="{{ $sk['delta'] >= 0 ? 'text-success' : 'text-danger' }} ms-2">({{ $sk['delta'] >= 0 ? '+' : '' }}{{ $sk['delta'] }}%)</span></span>
                    </div>
                    <div class="progress" style="height:8px;background:var(--bd);border-radius:4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $sk['bar'] }}%;border-radius:4px;"></div>
                    </div>
                </div>
                @endforeach
                @else
                <div class="text-center py-4" style="color:var(--tx3);">
                    <p>Complete at least 2 Philippines practice interviews to track your specific skill improvements.</p>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-6 d-flex flex-column gap-4">
            <!-- Feature 4: Voice Rehearsal Report -->
            <div class="print-card flex-grow-1" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-microphone-lines text-warning me-2"></i>Voice Rehearsal Report</h5>
                <div class="row text-center align-items-center h-100 gy-3">
                    <div class="col-4 border-end px-1 px-sm-3" style="border-color:var(--bd)!important;">
                        <div style="font-size:clamp(1.2rem, 5vw, 1.8rem);font-weight:bold;color:var(--tx);">{{ $voiceData->wpm ?? 'N/A' }}</div>
                        <div style="font-size:clamp(0.55rem, 2.2vw, 0.75rem);color:var(--tx3);text-transform:uppercase;font-weight:600;">Pace (WPM)</div>
                    </div>
                    <div class="col-4 border-end px-1 px-sm-3" style="border-color:var(--bd)!important;">
                        <div style="font-size:clamp(1.2rem, 5vw, 1.8rem);font-weight:bold;color:var(--tx);">{{ $voiceData->confidence === null ? 'N/A' : $voiceData->confidence . '%' }}</div>
                        <div style="font-size:clamp(0.55rem, 2.2vw, 0.75rem);color:var(--tx3);text-transform:uppercase;font-weight:600;">Delivery Stability</div>
                    </div>
                    <div class="col-4 px-1 px-sm-3">
                        <div style="font-size:clamp(1.2rem, 5vw, 1.8rem);font-weight:bold;color:#ef4444;">{{ $voiceData->filler_words ?? 'N/A' }}</div>
                        <div style="font-size:clamp(0.55rem, 2.2vw, 0.75rem);color:var(--tx3);text-transform:uppercase;font-weight:600;">Filler Words</div>
                    </div>
                </div>
            </div>

            <!-- Feature 5: Learning Progress Report -->
            <div id="report-learning" class="print-card flex-grow-1" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:16px;"><i class="fa-solid fa-graduation-cap text-info me-2"></i>Learning Progress Report</h5>
                <div class="row align-items-center h-100 gy-3">
                    <div class="col-md-6 text-center text-md-start">
                        <div style="font-size:clamp(2rem, 8vw, 2.5rem);font-weight:bold;color:#0dcaf0;line-height:1;">{{ $learningData->completion_rate }}%</div>
                        <div style="font-size:0.8rem;color:var(--tx3);text-transform:uppercase;font-weight:600;margin-bottom:12px;">Overall Completion</div>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0" style="color:var(--tx);font-size:0.9rem;">
                            <li class="mb-2 d-flex justify-content-between align-items-center"><span>Lessons:</span> <strong>{{ $learningData->lessons_completed }}/{{ $learningData->lessons_total }}</strong></li>
                            <li class="mb-2 d-flex justify-content-between align-items-center"><span>Videos:</span> <strong>{{ $learningData->videos_watched }}</strong></li>
                            <li class="d-flex justify-content-between align-items-center"><span>Quiz Avg:</span> <strong>{{ $learningData->quiz_average === null ? 'N/A' : $learningData->quiz_average . '%' }}</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 9: Achievement Report -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="print-card" style="background:var(--sf);border:1px solid var(--bd);border-radius:18px;padding:24px;">
                <h5 style="color:var(--tx);font-weight:bold;margin-bottom:20px;"><i class="fa-solid fa-award text-warning me-2"></i>Achievement Report</h5>
                <div class="d-flex flex-wrap gap-4 justify-content-center justify-content-md-start">
                    @forelse($achievements as $ach)
                    <div class="text-center" style="width:110px;">
                        <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.05);border:2px solid {{ $ach->color }};display:flex;justify-content:center;align-items:center;margin:0 auto 12px;box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                            <i class="fa-solid {{ $ach->icon }} fs-2" style="color:{{ $ach->color }};"></i>
                        </div>
                        <div style="font-size:0.8rem;color:var(--tx);font-weight:600;line-height:1.2;">{{ $ach->title }}</div>
                    </div>
                    @empty
                    <p style="color:var(--tx3);margin:0;">No achievements earned yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Empty State -->
    <div class="print-card report-empty-card text-center mb-4">
        <svg class="report-empty-art" viewBox="0 0 220 170" aria-hidden="true">
            <defs>
                <linearGradient id="emptyFolderBack" x1="58" y1="36" x2="159" y2="142"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#60A5FA"/></linearGradient>
                <linearGradient id="emptyFolderFront" x1="78" y1="72" x2="170" y2="144"><stop stop-color="#60A5FA"/><stop offset="1" stop-color="#2563EB"/></linearGradient>
            </defs>
            <circle cx="110" cy="84" r="70" fill="#DBEAFE" opacity=".82"/>
            <path d="M54 60c0-9 7-16 16-16h39l15 15h42c8 0 15 7 15 15v53H54V60Z" fill="url(#emptyFolderBack)"/>
            <path d="M69 82c2-10 10-17 20-17h83c10 0 17 9 15 19l-10 48c-2 9-10 15-19 15H67c-10 0-18-9-16-19l18-46Z" fill="url(#emptyFolderFront)"/>
            <path d="M67 78c3-14 13-23 27-23h83" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round" opacity=".88"/>
            <path d="M31 94h7M35 90v8M183 44h8M187 40v8M42 132h4M172 127h5" stroke="#60A5FA" stroke-width="5" stroke-linecap="round"/>
        </svg>
        <h4 class="report-empty-title" style="color:var(--tx);font-weight:800;">No Scored Portfolio Data Available</h4>
        <p class="report-empty-copy" style="color:var(--tx3); margin-bottom: 24px; max-width: 560px; margin-left: auto; margin-right: auto;">
            @if($sessions->count() > 0)
                You have completed interview records, but none of them have score data yet. Once a scored Philippines interview is available, this report will show analytics, comparisons, and feedback summaries.
            @else
                Your report is generated automatically from scored Philippines interview performance. Complete your first practice interview to unlock analytics, comparisons, and personalized feedback.
            @endif
        </p>
        <a href="{{ route('interview.setup') }}" class="btn btn-primary btn-shine report-start-btn" style="font-weight:700;"><i class="fa-solid fa-play"></i>Start Philippine Interview</a>
    </div>
    @endif
</div>

<!-- Scripts for Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($hasScoreData)
        const trendData = @json($scoreTrend);
        const labels = trendData.map(d => d.date);
        const scores = trendData.map(d => d.score);

        const trendChart = document.getElementById('trendChart');
        if (window.Chart && trendChart) {
            new Chart(trendChart, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Score Trend',
                        data: scores,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        const scenarioPerf = @json($categoryPerf);

        const categoryChart = document.getElementById('catChart');
        if (window.Chart && categoryChart) {
            new Chart(categoryChart, {
                type: 'bar',
                data: {
                    labels: Object.keys(scenarioPerf),
                    datasets: [{
                        data: Object.values(scenarioPerf),
                        backgroundColor: ['#3b82f6', '#f59e0b', '#8b5cf6', '#10b981', '#ef4444'],
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100, grid: { color: 'rgba(156, 163, 175, 0.1)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
        @endif

        // Export PDF
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', function() {
                const element = document.getElementById('portfolioReport');
                if (!element || typeof window.html2pdf !== 'function') {
                    alert('PDF export is not available right now. Please try again later.');
                    return;
                }
                const opt = {
                    margin:       [0.5, 0.5, 0.5, 0.5],
                    filename:     'portfolio_report.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2, useCORS: true },
                    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
                };

                // Hide header actions during export
                const headerActions = element.querySelector('.btn-no-print');
                let originalDisplay = '';
                if (headerActions) {
                    originalDisplay = headerActions.style.display;
                    headerActions.style.display = 'none';
                }

                html2pdf().set(opt).from(element).save().catch(() => {
                    alert('PDF export failed. Please try again.');
                }).finally(() => {
                    if (headerActions) {
                        headerActions.style.display = originalDisplay;
                    }
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
                const table = document.querySelector('#report-comparison table');
                if (table) {
                    const wb = XLSX.utils.table_to_book(table, {sheet: "Comparison"});
                    XLSX.writeFile(wb, 'performance_comparison.xlsx');
                } else {
                    alert("No data available to export.");
                }
            });
        }
    });
</script>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#report-readiness', popover: { title: 'Overall Readiness', description: 'See your latest readiness score and improvement since your first interview.', side: 'bottom', align: 'start' }},
            { element: '#report-comparison', popover: { title: 'Performance Comparison', description: 'Compare key metrics between your first and latest Philippines interviews.', side: 'bottom', align: 'start' }},
            { element: '#report-feedback', popover: { title: 'Feedback Summary', description: 'Review strengths, improvement areas, and recommended practice.', side: 'top', align: 'start' }},
            { element: '#report-learning', popover: { title: 'Learning Progress', description: 'Track completion across learning modules and voice rehearsal work.', side: 'top', align: 'start' }}
        ];

        const stepsDesktop = [
            { element: '#report-readiness', popover: { title: 'Overall Readiness', description: 'See your latest readiness score and improvement since your first interview.', side: 'bottom', align: 'start' }},
            { element: '#report-comparison', popover: { title: 'Performance Comparison', description: 'Compare key metrics between your first and latest Philippines interviews.', side: 'bottom', align: 'start' }},
            { element: '#report-feedback', popover: { title: 'Feedback Summary', description: 'Review strengths, improvement areas, and recommended practice.', side: 'top', align: 'start' }},
            { element: '#report-learning', popover: { title: 'Learning Progress', description: 'Track completion across learning modules and voice rehearsal work.', side: 'top', align: 'end' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_reports',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
