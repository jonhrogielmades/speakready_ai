@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Notifications')

@section('content')
<style>
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }

    #notifications-page {
        --notif-panel-bg: rgba(255, 255, 255, .78);
        --notif-panel-border: rgba(191, 219, 254, .78);
        --notif-row-bg: rgba(255, 255, 255, .88);
        --notif-row-border: rgba(147, 197, 253, .48);
        --notif-row-unread-bg: linear-gradient(135deg, rgba(239, 246, 255, .98), rgba(255, 255, 255, .9));
        --notif-row-unread-border: rgba(37, 99, 235, .48);
        --notif-title: #0f172a;
        --notif-text: #334155;
        --notif-muted: #64748b;
        --notif-chip-bg: rgba(248, 250, 252, .9);
        --notif-chip-border: rgba(148, 163, 184, .32);
        --notif-primary-bg: rgba(239, 246, 255, .92);
        --notif-primary-text: #1d4ed8;
        --notif-primary-border: rgba(59, 130, 246, .34);
        --notif-danger-bg: rgba(255, 241, 242, .9);
        --notif-danger-text: #dc2626;
        --notif-danger-border: rgba(239, 68, 68, .34);
        --notif-empty-bg: rgba(239, 246, 255, .62);
        --notif-page-shadow: 0 18px 44px rgba(15, 23, 42, .07);
        max-width: 860px;
        margin-inline: auto;
    }
    :root:not(.lm) #notifications-page,
    .dm #notifications-page {
        --notif-panel-bg: color-mix(in srgb, var(--bg3) 78%, transparent);
        --notif-panel-border: rgba(148, 163, 184, .2);
        --notif-row-bg: color-mix(in srgb, var(--bg3) 84%, transparent);
        --notif-row-border: rgba(148, 163, 184, .2);
        --notif-row-unread-bg: linear-gradient(135deg, rgba(30, 41, 59, .92), rgba(15, 23, 42, .84));
        --notif-row-unread-border: rgba(96, 165, 250, .5);
        --notif-title: var(--tx);
        --notif-text: var(--tx2);
        --notif-muted: color-mix(in srgb, var(--tx2) 78%, var(--tx3));
        --notif-chip-bg: rgba(255, 255, 255, .055);
        --notif-chip-border: rgba(148, 163, 184, .2);
        --notif-primary-bg: rgba(59, 130, 246, .14);
        --notif-primary-text: #bfdbfe;
        --notif-primary-border: rgba(96, 165, 250, .34);
        --notif-danger-bg: rgba(127, 29, 29, .24);
        --notif-danger-text: #fecaca;
        --notif-danger-border: rgba(248, 113, 113, .34);
        --notif-empty-bg: rgba(96, 165, 250, .08);
        --notif-page-shadow: 0 18px 44px rgba(0, 0, 0, .2);
    }
    .notif-hero {
        --notif-hero-title: #1d4ed8;
        --notif-hero-text: #334155;
        --notif-icon-bg: rgba(239, 246, 255, 0.92);
        --notif-icon-border: rgba(147, 197, 253, 0.42);
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        align-items: center;
        min-height: 78px;
        padding: 8px 108px 8px 12px;
        margin-bottom: 10px;
        border: 1px solid rgba(191, 219, 254, .86);
        border-radius: 16px;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, .12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,.98) 0%, rgba(248,250,252,.96) 62%, rgba(239,246,255,.92) 100%);
        box-shadow: 0 10px 24px rgba(37, 99, 235, .08);
        overflow: hidden;
    }
    :root:not(.lm) .notif-hero,
    .dm .notif-hero {
        --notif-hero-title: #93c5fd;
        --notif-hero-text: #e2e8f0;
        --notif-icon-bg: rgba(59, 130, 246, 0.2);
        --notif-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, .26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%);
        border-color: rgba(147, 197, 253, .28);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .18);
    }
    .notif-hero-copy {
        display: grid;
        grid-template-columns: 36px minmax(0, 1fr);
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .notif-hero-icon {
        width: 31px;
        height: 31px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--notif-hero-title);
        background: var(--notif-icon-bg);
        border: 1px solid var(--notif-icon-border);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.78);
        font-size: .9rem;
    }
    #notifications-page .notif-hero .notif-hero-title {
        display: block !important;
        margin: 0 0 4px !important;
        color: var(--notif-hero-title) !important;
        font-weight: 950 !important;
        font-size: 1.02rem !important;
        line-height: 1.08 !important;
        letter-spacing: 0 !important;
        text-transform: uppercase !important;
    }
    .notif-hero-subtitle {
        margin: 0;
        max-width: 15rem;
        color: var(--notif-hero-text);
        font-size: .65rem;
        line-height: 1.32;
        font-weight: 500;
    }
    .notif-hero-art {
        position: absolute;
        right: 4px;
        bottom: 3px;
        width: 82px;
        max-width: 100%;
        filter: drop-shadow(0 14px 20px rgba(37, 99, 235, .16));
        animation: notifHeroArtFloat 4.8s ease-in-out infinite;
        transform-origin: 50% 78%;
    }
    .notif-hero-art :is(circle, rect, path):nth-child(odd) {
        transform-origin: center;
        animation: notifHeroArtPulse 3.4s ease-in-out infinite;
    }
    @keyframes notifHeroArtFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-4px) rotate(-1deg); }
    }
    @keyframes notifHeroArtPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .78; }
    }
    @media (prefers-reduced-motion: reduce) {
        .notif-hero-art,
        .notif-hero-art :is(circle, rect, path) {
            animation: none !important;
        }
    }
    .notif-bulk-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 10px;
    }
    .notif-bulk-btn,
    .notification-action-btn {
        min-width: 0;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-weight: 800;
        letter-spacing: 0;
        line-height: 1.1;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .notif-bulk-btn {
        min-height: 38px;
        padding: 8px 10px;
        border: 1px solid var(--notif-primary-border);
        border-radius: 12px;
        color: var(--notif-primary-text);
        background: var(--notif-primary-bg);
        box-shadow: 0 8px 18px rgba(37, 99, 235, .06);
        font-size: .72rem;
    }
    .notif-bulk-btn i {
        font-size: .72rem;
    }
    .notif-bulk-btn.danger {
        border-color: var(--notif-danger-border);
        color: var(--notif-danger-text);
        background: var(--notif-danger-bg);
    }
    .notif-bulk-btn:hover,
    .notification-action-btn:hover {
        transform: translateY(-1px);
    }
    .premium-panel {
        display: grid;
        gap: 10px;
        padding: 12px;
        border-radius: 18px;
        background: var(--notif-panel-bg);
        border: 1px solid var(--notif-panel-border);
        box-shadow: var(--notif-page-shadow);
    }
    .notification-row {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 12px;
        padding: 14px;
        border: 1px solid var(--notif-row-border);
        border-radius: 14px;
        background: var(--notif-row-bg);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        min-width: 0;
    }
    .notification-row:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(37, 99, 235, .09);
    }
    .notification-row.is-unread {
        background: var(--notif-row-unread-bg);
        border-color: var(--notif-row-unread-border);
    }
    .notification-icon-box {
        width:40px;
        height:40px;
        border-radius:13px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:.95rem;
        flex-shrink:0;
    }
    .notification-content { min-width: 0; }
    .notification-head {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px 8px;
        margin-bottom: 7px;
    }
    .notification-title {
        color: var(--notif-title);
        margin: 0;
        font-size: .88rem;
        line-height: 1.25;
        font-weight: 800;
        overflow-wrap: anywhere;
    }
    .notification-meta {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        width: fit-content;
        max-width: 100%;
        padding: 4px 8px;
        border: 1px solid var(--notif-chip-border);
        border-radius: 999px;
        color: var(--notif-muted);
        font-size:.66rem;
        line-height: 1.15;
        background: var(--notif-chip-bg);
    }
    .notification-status-badge {
        font-size:.58rem;
        letter-spacing:0;
        border-radius:999px;
        padding:4px 8px;
        color: #fff;
        background: linear-gradient(135deg, #2563eb, #0ea5e9);
        box-shadow: 0 6px 14px rgba(37, 99, 235, .22);
    }
    .notification-message {
        color: var(--notif-text);
        font-size: .76rem;
        line-height: 1.45;
        margin: 0 0 10px;
        overflow-wrap: anywhere;
    }
    .notification-actions {
        display:grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap:8px;
        max-width: 360px;
    }
    .notification-action-btn {
        border:1px solid var(--notif-primary-border);
        background: var(--notif-primary-bg);
        color: var(--notif-primary-text);
        padding:8px 10px;
        min-height:34px;
        border-radius: 11px;
        font-size:0.68rem;
    }
    .notification-action-btn.read {
        color: var(--notif-primary-text);
    }
    .notification-action-btn.delete {
        color: var(--notif-danger-text);
        border-color: var(--notif-danger-border);
        background: var(--notif-danger-bg);
    }
    .notification-actions > :only-child {
        grid-column: auto;
    }
    .notifications-empty-state {
        padding:42px 22px;
        text-align:center;
        color: var(--notif-text);
        background: var(--notif-empty-bg);
        border: 1px dashed var(--notif-panel-border);
        border-radius: 16px;
    }
    .notifications-empty-icon {
        width:64px;
        height:64px;
        margin:0 auto 14px;
        border-radius:20px;
        display:flex;
        align-items:center;
        justify-content:center;
        background: var(--notif-primary-bg);
        color: var(--notif-primary-text);
        font-size:1.55rem;
    }
    #notifications-page .notifications-pagination {
        margin-top: 14px !important;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    #notifications-page .notifications-pagination nav {
        width: auto;
        max-width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    #notifications-page .notifications-pagination .pagination {
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }
    #notifications-page .notifications-pagination .page-item {
        margin: 0 !important;
    }
    #notifications-page .notifications-pagination .page-link,
    #notifications-page .notifications-pagination span.page-link {
        width: auto !important;
        min-width: 38px !important;
        height: 38px !important;
        min-height: 38px !important;
        padding: 0 12px !important;
        border-radius: 10px !important;
        border: 1px solid var(--notif-panel-border) !important;
        background: var(--notif-row-bg) !important;
        color: var(--notif-title) !important;
        font-size: 0.84rem !important;
        line-height: 1 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: none !important;
    }
    #notifications-page .notifications-pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #2563eb, #0ea5e9) !important;
        border-color: transparent !important;
        color: #fff !important;
    }
    #notifications-page .notifications-pagination .page-item.disabled .page-link {
        opacity: 0.55;
    }
    #notifications-page .notifications-pagination svg,
    #notifications-page .notifications-pagination .page-link svg {
        display: none !important;
        width: 0 !important;
        height: 0 !important;
    }
    #notifications-page .notifications-pagination p,
    #notifications-page .notifications-pagination .text-sm {
        width: 100%;
        margin: 0 !important;
        text-align: center;
        color: var(--notif-muted) !important;
        font-size: 0.78rem !important;
        line-height: 1.35 !important;
        position: static !important;
    }
    @media (max-width: 767px) {
        #notifications-page .premium-panel {
            padding: 8px;
            gap: 8px;
            border-radius: 15px;
        }
        #notifications-page .notification-row {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 10px;
            padding: 12px;
            border-radius: 14px;
        }
        #notifications-page .notification-icon-box {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            font-size: .86rem;
        }
        #notifications-page .notification-title {
            font-size: .8rem;
        }
        #notifications-page .notification-message {
            font-size: .68rem;
            margin-bottom: 9px;
        }
        #notifications-page .notification-meta {
            font-size: 0.66rem !important;
            padding: 4px 8px;
        }
        #notifications-page .notification-actions {
            gap: 8px;
        }
        #notifications-page .notification-action-btn {
            min-height: 32px;
            padding: 7px 8px;
            border-radius: 10px;
            font-size: .62rem;
        }
        #notifications-page .notifications-empty-state {
            border-radius: 18px;
            padding: 34px 18px;
        }
        #notifications-page .pagination {
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
        }
        #notifications-page .page-link {
            min-width: 38px;
            min-height: 38px;
            border-radius: 11px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    }
    @media (max-width: 575px) {
        #notifications-page {
            max-width: 100%;
        }
        .notif-hero {
            min-height: 72px;
            padding: 7px 88px 7px 10px;
            margin-bottom: 10px;
            border-radius: 16px;
        }
        .notif-hero-copy {
            grid-template-columns: 36px minmax(0, 1fr);
            gap: 9px;
        }
        .notif-hero-icon {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            font-size: .8rem;
        }
        #notifications-page .notif-hero .notif-hero-title {
            margin-bottom: 4px !important;
            font-size: .9rem !important;
        }
        .notif-hero-subtitle {
            max-width: 12rem;
            font-size: .64rem;
            line-height: 1.28;
        }
        .notif-hero-art {
            right: -4px;
            bottom: 5px;
            width: 76px;
        }
        .notif-bulk-actions {
            gap: 7px;
            margin-bottom: 9px;
        }
        .notif-bulk-btn {
            min-height: 34px;
            padding: 7px 8px;
            border-radius: 11px;
            gap: 6px;
            font-size: .62rem;
        }
    }
    @media (max-width: 390px) {
        .notif-hero {
            min-height: 68px;
            padding: 6px 72px 6px 10px;
        }
        .notif-hero-copy {
            grid-template-columns: 36px minmax(0, 1fr);
            gap: 8px;
        }
        .notif-hero-icon {
            width: 30px;
            height: 30px;
            border-radius: 10px;
        }
        .notif-hero-art {
            width: 64px;
        }
        #notifications-page .notification-row {
            grid-template-columns: 34px minmax(0, 1fr);
            padding: 10px;
            gap: 8px;
        }
        #notifications-page .notification-icon-box {
            width: 32px;
            height: 32px;
            border-radius: 11px;
        }
        #notifications-page .notification-actions {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991px) {
        #notifications-page {
            --notif-saas-radius: 12px;
            --notif-saas-gap: 8px;
            --notif-saas-border: rgba(37, 99, 235, 0.14);
            --notif-saas-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            --notif-saas-muted: #475569;
            max-width: 100% !important;
            display: flex;
            flex-direction: column;
            gap: var(--notif-saas-gap);
            padding-inline: 0 !important;
            padding-bottom: 14px !important;
        }
        html[data-theme="dark"] #notifications-page,
        :root:not(.lm) #notifications-page,
        .dm #notifications-page {
            --notif-saas-border: rgba(147, 197, 253, 0.18);
            --notif-saas-shadow: 0 12px 26px rgba(0, 0, 0, 0.26);
            --notif-saas-muted: #cbd5e1;
        }
        #notifications-page .notif-hero {
            min-height: 82px !important;
            padding: 10px 76px 10px 12px !important;
            margin: 0 !important;
            border-radius: var(--notif-saas-radius) !important;
            border-color: var(--notif-saas-border) !important;
            box-shadow: var(--notif-saas-shadow) !important;
        }
        #notifications-page .notif-hero-copy {
            grid-template-columns: 32px minmax(0, 1fr) !important;
            gap: 9px !important;
        }
        #notifications-page .notif-hero-icon {
            width: 32px !important;
            height: 32px !important;
            border-radius: 10px !important;
            font-size: 0.82rem !important;
        }
        #notifications-page .notif-hero .notif-hero-title {
            font-size: 0.88rem !important;
            line-height: 1.12 !important;
            margin-bottom: 4px !important;
        }
        #notifications-page .notif-hero-subtitle {
            max-width: 12rem !important;
            max-height: 2.7em;
            overflow: hidden;
            font-size: 0.62rem !important;
            line-height: 1.34 !important;
        }
        #notifications-page .notif-hero-art {
            width: 70px !important;
            right: -4px !important;
            bottom: 6px !important;
        }
        #notifications-page .notif-bulk-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: var(--notif-saas-gap) !important;
            margin: 0 !important;
        }
        #notifications-page .notif-bulk-btn {
            min-height: 36px !important;
            padding: 0 10px !important;
            border-radius: 10px !important;
            gap: 6px !important;
            font-size: 0.66rem !important;
            box-shadow: none !important;
        }
        #notifications-page .premium-panel {
            gap: var(--notif-saas-gap) !important;
            padding: 8px !important;
            border-radius: var(--notif-saas-radius) !important;
            border-color: var(--notif-saas-border) !important;
            box-shadow: var(--notif-saas-shadow) !important;
            margin: 0 !important;
        }
        #notifications-page .notification-row {
            grid-template-columns: 34px minmax(0, 1fr) !important;
            gap: 8px !important;
            min-height: 0 !important;
            padding: 10px !important;
            border-radius: var(--notif-saas-radius) !important;
            border-color: var(--notif-saas-border) !important;
            box-shadow: none !important;
        }
        #notifications-page .notification-row:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        #notifications-page .notification-icon-box {
            width: 32px !important;
            height: 32px !important;
            border-radius: 10px !important;
            font-size: 0.78rem !important;
        }
        #notifications-page .notification-head {
            gap: 5px 6px !important;
            margin-bottom: 6px !important;
        }
        #notifications-page .notification-title {
            font-size: 0.78rem !important;
            line-height: 1.2 !important;
        }
        #notifications-page .notification-status-badge {
            padding: 3px 7px !important;
            font-size: 0.54rem !important;
            box-shadow: none !important;
        }
        #notifications-page .notification-meta {
            padding: 3px 7px !important;
            font-size: 0.58rem !important;
            line-height: 1.15 !important;
            color: var(--notif-saas-muted) !important;
        }
        #notifications-page .notification-message {
            margin-bottom: 8px !important;
            font-size: 0.66rem !important;
            line-height: 1.36 !important;
            color: var(--notif-saas-muted) !important;
        }
        #notifications-page .notification-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 7px !important;
            max-width: none !important;
        }
        #notifications-page .notification-action-btn {
            min-height: 32px !important;
            padding: 0 8px !important;
            border-radius: 9px !important;
            gap: 5px !important;
            font-size: 0.6rem !important;
            box-shadow: none !important;
        }
        #notifications-page .notification-actions > :only-child {
            grid-column: 1 / -1 !important;
        }
        #notifications-page .notifications-empty-state {
            padding: 30px 14px !important;
            border-radius: var(--notif-saas-radius) !important;
        }
        #notifications-page .notifications-empty-icon {
            width: 50px !important;
            height: 50px !important;
            border-radius: 15px !important;
            font-size: 1.2rem !important;
        }
        #notifications-page .notifications-pagination {
            margin-top: 0 !important;
        }
        #notifications-page .notifications-pagination .page-link,
        #notifications-page .notifications-pagination span.page-link {
            min-width: 34px !important;
            height: 34px !important;
            min-height: 34px !important;
            padding: 0 10px !important;
            border-radius: 9px !important;
            font-size: 0.76rem !important;
        }
    }
    /* Final compact hero override shared across user pages. */
    #notifications-page .notif-hero {
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin-bottom: 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
    }
    #notifications-page .notif-hero-copy {
        grid-template-columns: 30px minmax(0, 1fr) !important;
        gap: 8px !important;
    }
    #notifications-page .notif-hero-icon {
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
    }
    #notifications-page .notif-hero .notif-hero-title {
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin: 0 0 3px !important;
        white-space: nowrap !important;
    }
    #notifications-page .notif-hero-subtitle {
        max-width: 13.5rem !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
    }
    #notifications-page .notif-hero-art {
        right: -5px !important;
        bottom: -2px !important;
        width: 72px !important;
    }
    @media (max-width: 390px) {
        #notifications-page .notif-hero {
            padding: 8px 66px 8px 9px !important;
        }
        #notifications-page .notif-hero-copy {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
        }
        #notifications-page .notif-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }
        #notifications-page .notif-hero .notif-hero-title {
            font-size: 0.68rem !important;
        }
        #notifications-page .notif-hero-subtitle {
            font-size: 0.46rem !important;
        }
        #notifications-page .notif-hero-art {
            width: 66px !important;
        }
    }

    /* SaaSPro mobile polish for Notifications. */
    @media (max-width: 767px) {
        body #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0, rgba(20, 184, 166, 0.035) 260px, transparent 520px),
                var(--bg) !important;
        }

        body #mob-content > .db-content {
            padding: 12px 12px 18px !important;
        }

        html body #notifications-page {
            --notif-pro-card: rgba(255, 255, 255, 0.98);
            --notif-pro-field: rgba(255, 255, 255, 0.96);
            --notif-pro-soft: #f8fafc;
            --notif-pro-border: rgba(15, 23, 42, 0.1);
            --notif-pro-title: #0f172a;
            --notif-pro-text: #334155;
            --notif-pro-muted: #64748b;
            --notif-pro-accent: #2563eb;
            --notif-pro-accent-2: #0891b2;
            --notif-pro-success: #059669;
            --notif-pro-danger: #dc2626;
            --notif-pro-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
            display: flex !important;
            flex-direction: column !important;
            gap: 10px !important;
            max-width: 520px !important;
            margin: 0 auto !important;
            padding: 0 0 16px !important;
            color: var(--notif-pro-title) !important;
        }

        html[data-theme="dark"] body #notifications-page,
        :root:not(.lm) body #notifications-page,
        body.dm #notifications-page,
        .dm #notifications-page {
            --notif-pro-card: rgba(15, 23, 42, 0.94);
            --notif-pro-field: rgba(30, 41, 59, 0.9);
            --notif-pro-soft: rgba(51, 65, 85, 0.78);
            --notif-pro-border: rgba(148, 163, 184, 0.24);
            --notif-pro-title: #f8fafc;
            --notif-pro-text: #e2e8f0;
            --notif-pro-muted: #cbd5e1;
            --notif-pro-accent: #93c5fd;
            --notif-pro-accent-2: #67e8f9;
            --notif-pro-success: #86efac;
            --notif-pro-danger: #fca5a5;
            --notif-pro-shadow: 0 1px 0 rgba(148, 163, 184, 0.08), 0 18px 36px rgba(0, 0, 0, 0.26);
        }

        html body #notifications-page .notif-hero {
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

        html[data-theme="dark"] body #notifications-page .notif-hero,
        :root:not(.lm) body #notifications-page .notif-hero,
        body.dm #notifications-page .notif-hero,
        .dm #notifications-page .notif-hero {
            background:
                linear-gradient(115deg, rgba(30, 64, 175, 0.96), rgba(15, 118, 110, 0.9)),
                #1e3a8a !important;
            box-shadow: 0 18px 34px rgba(0, 0, 0, 0.3) !important;
        }

        html body #notifications-page .notif-hero::before {
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

        html body #notifications-page .notif-hero::after {
            display: none !important;
        }

        html body #notifications-page .notif-hero-copy {
            position: relative;
            z-index: 1;
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            min-width: 0 !important;
        }

        html body #notifications-page .notif-hero-icon {
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

        html body #notifications-page .notif-hero .notif-hero-title {
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

        html body #notifications-page .notif-hero-subtitle {
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

        html body #notifications-page .notif-hero-art {
            display: block !important;
            width: 72px !important;
            height: auto !important;
            right: -5px !important;
            bottom: -2px !important;
            opacity: 0.98 !important;
            filter: drop-shadow(0 10px 16px rgba(15, 23, 42, 0.22));
            pointer-events: none;
        }

        html body #notifications-page .notif-bulk-actions {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 7px !important;
            margin: 0 !important;
        }

        html body #notifications-page .notif-bulk-btn {
            min-width: 0 !important;
            min-height: 38px !important;
            padding: 7px 8px !important;
            border: 1px solid rgba(37, 99, 235, 0.2) !important;
            border-radius: 8px !important;
            background: var(--notif-pro-field) !important;
            color: var(--notif-pro-accent) !important;
            box-shadow: var(--notif-pro-shadow) !important;
            gap: 5px !important;
            font-size: 0.62rem !important;
            font-weight: 900 !important;
            line-height: 1.12 !important;
            white-space: normal !important;
            text-align: center !important;
        }

        html body #notifications-page .notif-bulk-btn i {
            color: inherit !important;
            font-size: 0.68rem !important;
        }

        html body #notifications-page .notif-bulk-btn.danger {
            border-color: rgba(220, 38, 38, 0.24) !important;
            background: rgba(220, 38, 38, 0.1) !important;
            color: var(--notif-pro-danger) !important;
            box-shadow: none !important;
        }

        html body #notifications-page .notif-bulk-btn:hover,
        html body #notifications-page .notification-action-btn:hover {
            transform: none !important;
        }

        html body #notifications-page .premium-panel {
            display: grid !important;
            gap: 8px !important;
            width: 100% !important;
            max-width: none !important;
            justify-self: stretch !important;
            align-self: stretch !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            margin: 0 !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        html body #notifications-page .notification-row {
            display: grid !important;
            grid-template-columns: 32px minmax(0, 1fr) !important;
            gap: 8px !important;
            min-height: 0 !important;
            padding: 10px !important;
            border: 1px solid var(--notif-pro-border) !important;
            border-radius: 8px !important;
            background: var(--notif-pro-card) !important;
            box-shadow: var(--notif-pro-shadow) !important;
            color: var(--notif-pro-title) !important;
            overflow: hidden !important;
        }

        html body #notifications-page .notification-row:hover {
            transform: none !important;
            box-shadow: var(--notif-pro-shadow) !important;
        }

        html body #notifications-page .notification-row.is-unread {
            border-color: rgba(37, 99, 235, 0.32) !important;
            background:
                linear-gradient(135deg, rgba(37, 99, 235, 0.07), rgba(20, 184, 166, 0.04)),
                var(--notif-pro-card) !important;
        }

        html[data-theme="dark"] body #notifications-page .notification-row.is-unread,
        :root:not(.lm) body #notifications-page .notification-row.is-unread,
        body.dm #notifications-page .notification-row.is-unread,
        .dm #notifications-page .notification-row.is-unread {
            border-color: rgba(147, 197, 253, 0.36) !important;
            background:
                linear-gradient(135deg, rgba(37, 99, 235, 0.18), rgba(20, 184, 166, 0.08)),
                var(--notif-pro-card) !important;
        }

        html body #notifications-page .notification-icon-box {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            border: 1px solid var(--notif-pro-border) !important;
            border-radius: 8px !important;
            font-size: 0.76rem !important;
            box-shadow: none !important;
        }

        html body #notifications-page .notification-content {
            min-width: 0 !important;
        }

        html body #notifications-page .notification-head {
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 5px 6px !important;
            margin-bottom: 6px !important;
        }

        html body #notifications-page .notification-title {
            flex: 1 1 100%;
            min-width: 0 !important;
            color: var(--notif-pro-title) !important;
            font-size: 0.76rem !important;
            font-weight: 900 !important;
            line-height: 1.18 !important;
            margin: 0 !important;
            overflow-wrap: anywhere !important;
        }

        html body #notifications-page .notification-status-badge {
            min-height: 20px !important;
            padding: 4px 6px !important;
            border-radius: 6px !important;
            background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
            color: #ffffff !important;
            box-shadow: none !important;
            font-size: 0.5rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
        }

        html body #notifications-page .notification-meta {
            min-height: 20px !important;
            max-width: 100% !important;
            padding: 4px 6px !important;
            border: 1px solid var(--notif-pro-border) !important;
            border-radius: 6px !important;
            background: var(--notif-pro-soft) !important;
            color: var(--notif-pro-muted) !important;
            font-size: 0.54rem !important;
            font-weight: 800 !important;
            line-height: 1 !important;
        }

        html body #notifications-page .notification-meta i {
            color: var(--notif-pro-accent) !important;
        }

        html body #notifications-page .notification-message {
            margin: 0 0 8px !important;
            color: var(--notif-pro-text) !important;
            font-size: 0.66rem !important;
            font-weight: 650 !important;
            line-height: 1.34 !important;
            overflow-wrap: anywhere !important;
        }

        html body #notifications-page .notification-actions {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 7px !important;
            max-width: none !important;
        }

        html body #notifications-page .notification-actions > :only-child {
            grid-column: 1 / -1 !important;
        }

        html body #notifications-page .notification-action-btn {
            min-width: 0 !important;
            min-height: 32px !important;
            padding: 7px 8px !important;
            border: 1px solid rgba(37, 99, 235, 0.2) !important;
            border-radius: 8px !important;
            background: var(--notif-pro-field) !important;
            color: var(--notif-pro-accent) !important;
            box-shadow: none !important;
            gap: 5px !important;
            font-size: 0.58rem !important;
            font-weight: 900 !important;
            line-height: 1.12 !important;
            white-space: normal !important;
            text-align: center !important;
        }

        html body #notifications-page .notification-action-btn i {
            color: inherit !important;
            font-size: 0.64rem !important;
        }

        html body #notifications-page .notification-action-btn.delete {
            border-color: rgba(220, 38, 38, 0.24) !important;
            background: rgba(220, 38, 38, 0.1) !important;
            color: var(--notif-pro-danger) !important;
        }

        html body #notifications-page .notifications-empty-state {
            width: 100% !important;
            max-width: none !important;
            box-sizing: border-box !important;
            padding: 30px 14px !important;
            border: 1px dashed var(--notif-pro-border) !important;
            border-radius: 8px !important;
            background: var(--notif-pro-card) !important;
            color: var(--notif-pro-text) !important;
            box-shadow: var(--notif-pro-shadow) !important;
        }

        html body #notifications-page .notifications-empty-icon {
            width: 46px !important;
            height: 46px !important;
            margin-bottom: 10px !important;
            border: 1px solid rgba(37, 99, 235, 0.2) !important;
            border-radius: 8px !important;
            background: rgba(37, 99, 235, 0.1) !important;
            color: var(--notif-pro-accent) !important;
            font-size: 1.05rem !important;
        }

        html body #notifications-page .notifications-empty-state p {
            color: var(--notif-pro-muted) !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            line-height: 1.32 !important;
        }

        html body #notifications-page .notifications-pagination {
            margin-top: 0 !important;
            gap: 7px !important;
        }

        html body #notifications-page .notifications-pagination .pagination {
            gap: 5px !important;
        }

        html body #notifications-page .notifications-pagination .page-link,
        html body #notifications-page .notifications-pagination span.page-link {
            min-width: 32px !important;
            height: 32px !important;
            min-height: 32px !important;
            padding: 0 9px !important;
            border: 1px solid var(--notif-pro-border) !important;
            border-radius: 8px !important;
            background: var(--notif-pro-field) !important;
            color: var(--notif-pro-title) !important;
            font-size: 0.7rem !important;
            font-weight: 900 !important;
            box-shadow: none !important;
        }

        html body #notifications-page .notifications-pagination .page-item.active .page-link {
            border-color: transparent !important;
            background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
            color: #ffffff !important;
        }

        html body #notifications-page .notifications-pagination p,
        html body #notifications-page .notifications-pagination .text-sm {
            color: var(--notif-pro-muted) !important;
            font-size: 0.66rem !important;
            font-weight: 700 !important;
            line-height: 1.3 !important;
        }
    }

    @media (max-width: 390px) {
        html body #notifications-page .notif-hero {
            padding: 8px 66px 8px 10px !important;
        }

        html body #notifications-page .notif-hero-copy {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
        }

        html body #notifications-page .notif-hero-icon {
            width: 26px !important;
            height: 26px !important;
            min-width: 26px !important;
            font-size: 0.7rem !important;
        }

        html body #notifications-page .notif-hero .notif-hero-title {
            font-size: 0.68rem !important;
        }

        html body #notifications-page .notif-hero-subtitle {
            max-width: 10.8rem !important;
            font-size: 0.46rem !important;
        }

        html body #notifications-page .notif-hero-art {
            width: 66px !important;
            right: -6px !important;
        }

        html body #notifications-page .notification-row {
            grid-template-columns: 30px minmax(0, 1fr) !important;
            padding: 9px !important;
        }

        html body #notifications-page .notification-icon-box {
            width: 30px !important;
            height: 30px !important;
            min-width: 30px !important;
        }
    }

    @media (max-width: 360px) {
        html body #notifications-page .notif-bulk-actions,
        html body #notifications-page .notification-actions {
            grid-template-columns: 1fr !important;
        }

        html body #notifications-page .notification-title {
            font-size: 0.72rem !important;
        }

        html body #notifications-page .notification-message {
            font-size: 0.62rem !important;
        }
    }

    body.user-desktop-shell #notifications-page {
        --notif-shell-gap: 10px;
        --notif-shell-radius: 12px;
        --notif-shell-border: rgba(15, 23, 42, 0.12);
        --notif-shell-card: rgba(255, 255, 255, 0.98);
        --notif-shell-field: rgba(248, 250, 252, 0.92);
        --notif-shell-title: #0f172a;
        --notif-shell-text: #334155;
        --notif-shell-muted: #64748b;
        --notif-shell-accent: #2563eb;
        --notif-shell-accent-2: #0891b2;
        --notif-shell-danger: #dc2626;
        --notif-shell-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 0 24px !important;
        color: var(--notif-shell-title) !important;
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
        overflow-x: hidden !important;
    }

    html[data-theme="dark"] body.user-desktop-shell #notifications-page,
    :root:not(.lm) body.user-desktop-shell #notifications-page,
    body.user-desktop-shell.dm #notifications-page,
    body.user-desktop-shell .dm #notifications-page {
        --notif-shell-border: rgba(148, 163, 184, 0.24);
        --notif-shell-card: rgba(15, 23, 42, 0.96);
        --notif-shell-field: rgba(30, 41, 59, 0.92);
        --notif-shell-title: #f8fafc;
        --notif-shell-text: #e2e8f0;
        --notif-shell-muted: #cbd5e1;
        --notif-shell-accent: #93c5fd;
        --notif-shell-accent-2: #67e8f9;
        --notif-shell-danger: #fca5a5;
        --notif-shell-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
    }

    body.user-desktop-shell #notifications-page > :is(.notif-hero, .notif-bulk-actions, .notifications-list-panel, .notifications-pagination) {
        margin-top: 0 !important;
        margin-bottom: var(--notif-shell-gap) !important;
    }

    body.user-desktop-shell #notifications-page > :last-child {
        margin-bottom: 0 !important;
    }

    body.user-desktop-shell #notifications-page .notif-hero {
        position: relative !important;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) 180px !important;
        align-items: center !important;
        min-height: 116px !important;
        height: auto !important;
        gap: 14px !important;
        padding: 18px 178px 18px 20px !important;
        border: 1px solid rgba(191, 219, 254, 0.86) !important;
        border-radius: var(--notif-shell-radius) !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
        box-shadow: 0 10px 26px rgba(37, 99, 235, 0.12) !important;
        overflow: hidden !important;
    }

    html[data-theme="dark"] body.user-desktop-shell #notifications-page .notif-hero,
    :root:not(.lm) body.user-desktop-shell #notifications-page .notif-hero,
    body.user-desktop-shell.dm #notifications-page .notif-hero,
    body.user-desktop-shell .dm #notifications-page .notif-hero {
        border-color: rgba(147, 197, 253, 0.28) !important;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.28), transparent 35%),
            linear-gradient(142deg, rgba(15, 23, 42, 0.98) 0%, rgba(17, 24, 39, 0.98) 58%, rgba(30, 41, 59, 0.96) 100%) !important;
        box-shadow: 0 14px 30px rgba(0, 0, 0, 0.24) !important;
    }

    body.user-desktop-shell #notifications-page .notif-hero::before,
    body.user-desktop-shell #notifications-page .notif-hero::after {
        content: none !important;
        display: none !important;
    }

    body.user-desktop-shell #notifications-page .notif-hero-copy {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        max-width: 780px !important;
    }

    body.user-desktop-shell #notifications-page .notif-hero-icon {
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        border: 1px solid rgba(147, 197, 253, 0.42) !important;
        border-radius: 12px !important;
        background: rgba(239, 246, 255, 0.92) !important;
        color: #1d4ed8 !important;
        font-size: 1.05rem !important;
        box-shadow: none !important;
    }

    html[data-theme="dark"] body.user-desktop-shell #notifications-page .notif-hero-icon,
    :root:not(.lm) body.user-desktop-shell #notifications-page .notif-hero-icon,
    body.user-desktop-shell.dm #notifications-page .notif-hero-icon,
    body.user-desktop-shell .dm #notifications-page .notif-hero-icon {
        border-color: rgba(147, 197, 253, 0.32) !important;
        background: rgba(59, 130, 246, 0.2) !important;
        color: var(--notif-shell-accent) !important;
    }

    body.user-desktop-shell #notifications-page .notif-hero .notif-hero-title {
        margin: 0 0 5px !important;
        color: var(--notif-shell-title) !important;
        -webkit-text-fill-color: var(--notif-shell-title) !important;
        background: none !important;
        font-size: clamp(1.12rem, 1.08vw, 1.45rem) !important;
        line-height: 1.12 !important;
        font-weight: 900 !important;
        text-transform: none !important;
        letter-spacing: 0 !important;
        opacity: 1 !important;
        text-shadow: none !important;
    }

    body.user-desktop-shell #notifications-page .notif-hero-subtitle {
        max-width: 640px !important;
        margin: 0 !important;
        color: var(--notif-shell-text) !important;
        font-size: 0.84rem !important;
        line-height: 1.42 !important;
        font-weight: 600 !important;
    }

    body.user-desktop-shell #notifications-page .notif-hero-art {
        display: block !important;
        position: absolute !important;
        right: 12px !important;
        bottom: -10px !important;
        width: clamp(140px, 12vw, 174px) !important;
        max-width: none !important;
        opacity: 0.96 !important;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16)) !important;
        animation: none !important;
    }

    body.user-desktop-shell #notifications-page .notif-bulk-actions {
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 8px 0 var(--notif-shell-gap) !important;
    }

    body.user-desktop-shell #notifications-page .notif-bulk-btn,
    body.user-desktop-shell #notifications-page .notification-action-btn {
        min-height: 34px !important;
        padding: 7px 11px !important;
        border: 1px solid rgba(37, 99, 235, 0.22) !important;
        border-radius: 9px !important;
        background: rgba(239, 246, 255, 0.94) !important;
        color: var(--notif-shell-accent) !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 7px !important;
        font-size: 0.68rem !important;
        line-height: 1.12 !important;
        font-weight: 900 !important;
        box-shadow: none !important;
        transform: none !important;
    }

    body.user-desktop-shell #notifications-page :is(.notif-bulk-btn, .notification-action-btn) i {
        color: inherit !important;
    }

    body.user-desktop-shell #notifications-page :is(.notif-bulk-btn.danger, .notification-action-btn.delete) {
        border-color: rgba(220, 38, 38, 0.22) !important;
        background: rgba(254, 242, 242, 0.96) !important;
        color: var(--notif-shell-danger) !important;
    }

    html[data-theme="dark"] body.user-desktop-shell #notifications-page .notif-bulk-btn,
    html[data-theme="dark"] body.user-desktop-shell #notifications-page .notification-action-btn,
    :root:not(.lm) body.user-desktop-shell #notifications-page .notif-bulk-btn,
    :root:not(.lm) body.user-desktop-shell #notifications-page .notification-action-btn,
    body.user-desktop-shell.dm #notifications-page .notif-bulk-btn,
    body.user-desktop-shell.dm #notifications-page .notification-action-btn,
    body.user-desktop-shell .dm #notifications-page .notif-bulk-btn,
    body.user-desktop-shell .dm #notifications-page .notification-action-btn {
        border-color: rgba(147, 197, 253, 0.24) !important;
        background: rgba(30, 41, 59, 0.88) !important;
        color: var(--notif-shell-accent) !important;
    }

    html[data-theme="dark"] body.user-desktop-shell #notifications-page :is(.notif-bulk-btn.danger, .notification-action-btn.delete),
    :root:not(.lm) body.user-desktop-shell #notifications-page :is(.notif-bulk-btn.danger, .notification-action-btn.delete),
    body.user-desktop-shell.dm #notifications-page :is(.notif-bulk-btn.danger, .notification-action-btn.delete),
    body.user-desktop-shell .dm #notifications-page :is(.notif-bulk-btn.danger, .notification-action-btn.delete) {
        border-color: rgba(248, 113, 113, 0.28) !important;
        background: rgba(127, 29, 29, 0.24) !important;
        color: var(--notif-shell-danger) !important;
    }

    body.user-desktop-shell #notifications-page .premium-panel,
    body.user-desktop-shell #notifications-page .notifications-list-panel {
        display: grid !important;
        gap: 8px !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        margin: 0 0 var(--notif-shell-gap) !important;
        padding: 14px !important;
        border: 1px solid var(--notif-shell-border) !important;
        border-radius: var(--notif-shell-radius) !important;
        background: var(--notif-shell-card) !important;
        box-shadow: var(--notif-shell-shadow) !important;
        transform: none !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    body.user-desktop-shell #notifications-page .notification-row {
        display: grid !important;
        grid-template-columns: 34px minmax(0, 1fr) !important;
        gap: 10px !important;
        align-items: start !important;
        padding: 10px !important;
        border: 1px solid var(--notif-shell-border) !important;
        border-radius: 10px !important;
        background: var(--notif-shell-field) !important;
        box-shadow: none !important;
        transform: none !important;
    }

    body.user-desktop-shell #notifications-page .notification-row.is-unread {
        border-color: rgba(37, 99, 235, 0.28) !important;
        background:
            linear-gradient(135deg, rgba(239, 246, 255, 0.98), rgba(255, 255, 255, 0.96)) !important;
    }

    html[data-theme="dark"] body.user-desktop-shell #notifications-page .notification-row.is-unread,
    :root:not(.lm) body.user-desktop-shell #notifications-page .notification-row.is-unread,
    body.user-desktop-shell.dm #notifications-page .notification-row.is-unread,
    body.user-desktop-shell .dm #notifications-page .notification-row.is-unread {
        border-color: rgba(96, 165, 250, 0.38) !important;
        background:
            radial-gradient(circle at 10% 0%, rgba(59, 130, 246, 0.16), transparent 36%),
            var(--notif-shell-field) !important;
    }

    body.user-desktop-shell #notifications-page .notification-row:hover {
        box-shadow: none !important;
        transform: none !important;
    }

    body.user-desktop-shell #notifications-page .notification-icon-box {
        width: 34px !important;
        height: 34px !important;
        min-width: 34px !important;
        border-radius: 10px !important;
        border: 1px solid var(--notif-shell-border) !important;
        box-shadow: none !important;
    }

    body.user-desktop-shell #notifications-page .notification-content {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        grid-template-areas:
            "head actions"
            "message message" !important;
        column-gap: 12px !important;
        row-gap: 6px !important;
        align-items: start !important;
        min-width: 0 !important;
    }

    body.user-desktop-shell #notifications-page .notification-head {
        grid-area: head !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        flex-wrap: wrap !important;
        min-width: 0 !important;
        margin-bottom: 0 !important;
    }

    body.user-desktop-shell #notifications-page .notification-title {
        margin: 0 !important;
        color: var(--notif-shell-title) !important;
        -webkit-text-fill-color: var(--notif-shell-title) !important;
        font-size: 0.78rem !important;
        line-height: 1.18 !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
        opacity: 1 !important;
    }

    body.user-desktop-shell #notifications-page .notification-status-badge,
    body.user-desktop-shell #notifications-page .notification-meta {
        min-height: 22px !important;
        padding: 4px 7px !important;
        border-radius: 999px !important;
        font-size: 0.56rem !important;
        line-height: 1 !important;
        font-weight: 900 !important;
        box-shadow: none !important;
    }

    body.user-desktop-shell #notifications-page .notification-status-badge {
        border: 1px solid rgba(37, 99, 235, 0.2) !important;
        background: linear-gradient(135deg, #2563eb, #0891b2) !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.user-desktop-shell #notifications-page .notification-meta {
        border: 1px solid var(--notif-shell-border) !important;
        background: var(--notif-shell-card) !important;
        color: var(--notif-shell-muted) !important;
    }

    body.user-desktop-shell #notifications-page .notification-meta i {
        color: var(--notif-shell-accent) !important;
    }

    body.user-desktop-shell #notifications-page .notification-message {
        grid-area: message !important;
        margin: 0 0 8px !important;
        color: var(--notif-shell-text) !important;
        -webkit-text-fill-color: var(--notif-shell-text) !important;
        font-size: 0.68rem !important;
        line-height: 1.32 !important;
        font-weight: 600 !important;
        opacity: 1 !important;
    }

    body.user-desktop-shell #notifications-page .notification-actions {
        grid-area: actions !important;
        display: flex !important;
        justify-content: flex-end !important;
        align-self: start !important;
        gap: 8px !important;
        flex-wrap: nowrap !important;
        min-width: max-content !important;
        margin: 0 !important;
    }

    body.user-desktop-shell #notifications-page .notifications-empty-state {
        width: 100% !important;
        min-height: 120px !important;
        margin: 0 !important;
        padding: 18px !important;
        border: 1px dashed var(--notif-shell-border) !important;
        border-radius: var(--notif-shell-radius) !important;
        background: var(--notif-shell-field) !important;
        color: var(--notif-shell-muted) !important;
        box-shadow: none !important;
    }

    body.user-desktop-shell #notifications-page .notifications-empty-icon {
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        margin-bottom: 10px !important;
        border: 1px solid rgba(37, 99, 235, 0.18) !important;
        border-radius: 12px !important;
        background: rgba(239, 246, 255, 0.94) !important;
        color: var(--notif-shell-accent) !important;
        box-shadow: none !important;
    }

    html[data-theme="dark"] body.user-desktop-shell #notifications-page .notifications-empty-icon,
    :root:not(.lm) body.user-desktop-shell #notifications-page .notifications-empty-icon,
    body.user-desktop-shell.dm #notifications-page .notifications-empty-icon,
    body.user-desktop-shell .dm #notifications-page .notifications-empty-icon {
        border-color: rgba(147, 197, 253, 0.24) !important;
        background: rgba(59, 130, 246, 0.16) !important;
    }

    body.user-desktop-shell #notifications-page .notifications-empty-state p {
        color: var(--notif-shell-text) !important;
        font-size: 0.72rem !important;
        line-height: 1.32 !important;
        font-weight: 800 !important;
    }

    body.user-desktop-shell #notifications-page .notifications-pagination {
        justify-content: flex-end !important;
        margin-top: 0 !important;
        gap: 8px !important;
    }

    body.user-desktop-shell #notifications-page .notifications-pagination .page-link,
    body.user-desktop-shell #notifications-page .notifications-pagination span.page-link {
        min-width: 32px !important;
        height: 32px !important;
        min-height: 32px !important;
        padding: 0 9px !important;
        border: 1px solid var(--notif-shell-border) !important;
        border-radius: 8px !important;
        background: var(--notif-shell-field) !important;
        color: var(--notif-shell-title) !important;
        font-size: 0.7rem !important;
        font-weight: 900 !important;
        box-shadow: none !important;
    }

    body.user-desktop-shell #notifications-page .notifications-pagination .page-item.active .page-link {
        border-color: transparent !important;
        background: linear-gradient(135deg, #2563eb, #0891b2) !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }

    body.user-desktop-shell #notifications-page .notifications-pagination :is(p, .text-sm) {
        color: var(--notif-shell-muted) !important;
    }
</style>

<div class="db-section active animate-fade-up" id="notifications-page">
    <div class="notif-hero">
        <div class="notif-hero-copy">
            <div class="notif-hero-icon"><i class="fa-regular fa-bell"></i></div>
            <div>
                <h4 class="notif-hero-title">Notifications</h4>
                <p class="notif-hero-subtitle">Track progress updates, alerts, and activity reminders.</p>
            </div>
        </div>
        <svg class="notif-hero-art" viewBox="0 0 180 160" aria-hidden="true">
            <defs>
                <linearGradient id="notifCard" x1="24" y1="18" x2="150" y2="138"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#F8FAFC"/></linearGradient>
                <linearGradient id="notifBell" x1="64" y1="42" x2="130" y2="118"><stop stop-color="#60A5FA"/><stop offset="1" stop-color="#1455F5"/></linearGradient>
            </defs>
            <path d="M42 24h82c22 0 36 15 32 37l-10 55c-4 20-19 31-40 29l-70-6c-20-2-31-17-27-37l10-48c4-19 15-30 23-30Z" fill="url(#notifCard)" stroke="#BFDBFE" stroke-width="4"/>
            <path d="M94 44c-17 0-31 14-31 31 0 25-13 25-13 35h87c0-10-13-10-13-35 0-17-13-31-30-31Z" fill="url(#notifBell)"/>
            <path d="M76 109c8 14 28 17 40 3" fill="none" stroke="#1D4ED8" stroke-width="8" stroke-linecap="round"/>
            <circle cx="136" cy="55" r="22" fill="#EF4444"/>
            <path d="M136 44v15" stroke="#fff" stroke-width="7" stroke-linecap="round"/>
            <circle cx="136" cy="68" r="3.4" fill="#fff"/>
            <path d="M43 38l-8-12M31 54l-13-4M44 67l-11 7" fill="none" stroke="#60A5FA" stroke-width="5" stroke-linecap="round" opacity=".7"/>
        </svg>
    </div>
    @if(count($notifications) > 0)
    <div class="notif-bulk-actions">
        <button class="notif-bulk-btn" type="button" onclick="markAllReadPage()"><i class="fa-solid fa-check"></i><span>Mark all as read</span></button>
        <button class="notif-bulk-btn danger" type="button" onclick="clearAllNotificationsPage()"><i class="fa-solid fa-trash-can"></i><span>Clear all</span></button>
    </div>
    @endif

    <div class="premium-panel notifications-list-panel animate-fade-up" style="animation-delay: 0.2s; width: 100% !important; min-width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; box-sizing: border-box !important;" id="notificationsPageList">
        @forelse($notifications as $notification)
        @php
            $isRead = !is_null($notification->read_at);
            $icon = $notification->data['icon'] ?? 'fa-bell';
            $typeClass = $notification->data['type'] ?? 'info';
            // Map types to colors
            $colors = [
                'info' => ['bg' => 'rgba(59,130,246,.15)', 'text' => '#60a5fa'],
                'success' => ['bg' => 'rgba(52,211,153,.15)', 'text' => '#34d399'],
                'warning' => ['bg' => 'rgba(245,158,11,.15)', 'text' => '#fbbf24'],
                'error' => ['bg' => 'rgba(248,113,113,.15)', 'text' => '#f87171'],
            ];
            $colorSettings = $colors[$typeClass] ?? $colors['info'];
        @endphp
        <div class="notification-row {{ !$isRead ? 'is-unread' : '' }}" id="notif-{{ $notification->id }}">
            <div class="notification-icon-box" style="background:{{ $colorSettings['bg'] }};color:{{ $colorSettings['text'] }};">
                <i class="fa-solid {{ $icon }}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-head">
                    <h6 class="notification-title" style="font-weight:{{ $isRead ? '700' : '800' }};">
                        {{ $notification->data['title'] ?? 'Notification' }}
                    </h6>
                    @if(!$isRead)
                    <span class="badge notification-status-badge">NEW</span>
                    @endif
                    <span class="notification-meta"><i class="fa-regular fa-clock"></i>{{ $notification->created_at->diffForHumans() }}</span>
                </div>
                <p class="notification-message">{{ $notification->data['message'] ?? '' }}</p>
                <div class="notification-actions">
                    @if(!$isRead)
                    <button class="notification-action-btn read" onclick="markRead('{{ $notification->id }}')"><i class="fa-solid fa-check"></i>Mark as read</button>
                    @endif
                    <button class="notification-action-btn delete" onclick="deleteNotification('{{ $notification->id }}')"><i class="fa-solid fa-trash"></i>Delete</button>
                </div>
            </div>
        </div>
        @empty
        <div class="notifications-empty-state notifications-empty-state-wide" style="width: 100% !important; min-width: 100% !important; max-width: none !important; margin: 0 !important; box-sizing: border-box !important;">
            <div class="notifications-empty-icon"><i class="fa-regular fa-bell-slash"></i></div>
            <p class="mb-0">You have no notifications at the moment.</p>
        </div>
        @endforelse
    </div>
    
    <div class="notifications-pagination mt-4 d-flex justify-content-center">
        {{ $notifications->links('pagination::bootstrap-5') }}
    </div>
</div>

@push('scripts')
<script>
function reloadNotificationsPage() {
    window.location.reload();
}

function markAllReadPage() {
    fetch('/notifications/read-all', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            window.location.reload();
        }
    });
}

function clearAllNotificationsPage() {
    if(confirm('Are you sure you want to clear all notifications?')) {
        fetch('/notifications/clear-all', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                window.location.reload();
            }
        });
    }
}

function markRead(id) {
    fetch('/notifications/' + id + '/read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            window.location.reload(); // simple reload for consistency
        }
    });
}

function deleteNotification(id) {
    if(confirm('Are you sure you want to delete this notification?')) {
        fetch('/notifications/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                window.location.reload();
            }
        });
    }
}
</script>
@endpush
@endsection


