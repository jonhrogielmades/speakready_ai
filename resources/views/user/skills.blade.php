@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Skill Trees')

@section('content')
<style>
    .text-gradient-primary {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }
    .premium-panel {
        background: var(--sf) !important;
        border: 1px solid var(--bd);
        border-radius: 24px !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .premium-panel:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08) !important;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }
    @media (max-width: 767px) {
        #skill-trees-page .sr-page-actions {
            display: grid !important;
            grid-template-columns: 82px minmax(0, 1fr) !important;
            gap: 8px !important;
            margin-bottom: 12px !important;
        }
        #skill-trees-page .sr-page-actions > * {
            width: 100% !important;
            min-height: 42px;
        }
        #skill-trees-page .stat-card {
            min-height: 106px;
            padding: 12px !important;
            border-radius: 14px !important;
        }
        #skill-trees-page .skill-xp-overview {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-left: 0;
            margin-right: 0;
            overflow: hidden;
        }
        #skill-trees-page .skill-xp-overview > [class*="col-"] {
            flex: none !important;
            max-width: none !important;
            width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        #skill-trees-page .stat-card [style*="width:50px"] {
            width: 34px !important;
            height: 34px !important;
            border-radius: 11px !important;
            font-size: 0.95rem !important;
            margin-bottom: 8px !important;
        }
        #skill-trees-page .stat-card h6 {
            font-size: 0.62rem !important;
            line-height: 1.15;
            letter-spacing: 0 !important;
            margin-bottom: 4px !important;
            overflow-wrap: anywhere;
        }
        #skill-trees-page .stat-card h3 {
            font-size: 1.05rem;
            line-height: 1.12;
        }
        #skill-trees-page .stat-card h3 span {
            display: inline;
            font-size: 0.62rem !important;
            line-height: 1.1;
        }
        #skill-trees-page h5 {
            font-size: 0.98rem;
            line-height: 1.25;
        }
        #skill-trees-page .perk-card {
            padding: 14px !important;
            border-radius: 14px !important;
        }
        #skill-trees-page .perk-card [style*="width:60px"] {
            width: 44px !important;
            height: 44px !important;
            border-radius: 13px !important;
            font-size: 1.15rem !important;
            margin-bottom: 12px !important;
        }
        #skill-trees-page .perk-card p {
            min-height: 0 !important;
            font-size: 0.8rem !important;
            line-height: 1.4;
        }
        #skill-trees-page .perk-card .mt-auto .d-flex {
            display: grid !important;
            grid-template-columns: 1fr;
            gap: 10px;
            align-items: stretch !important;
        }
        #skill-trees-page .perk-card .btn {
            width: 100%;
            min-height: 40px;
        }
    }
</style>
@include('partials.page-hero-styles')
<style>
    #skill-trees-page {
        --skill-blue: #2563eb;
        --skill-ink: #071936;
        --skill-muted: #52617a;
        --skill-border: #d6e4f8;
    }
    #skill-trees-page .sr-page-hero.skill-tree-hero {
        --skill-hero-title-color: #1d4ed8;
        --skill-hero-text-color: #334155;
        --skill-hero-icon-bg: rgba(239, 246, 255, 0.92);
        --skill-hero-icon-border: rgba(147, 197, 253, 0.42);
        display: grid !important;
        grid-template-columns: 44px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 10px !important;
        min-height: 78px;
        margin-bottom: 14px;
        padding: 8px 76px 8px 14px !important;
        border-radius: 16px;
        border-color: rgba(191, 219, 254, 0.86);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
    }
    :root:not(.lm) #skill-trees-page .sr-page-hero.skill-tree-hero,
    .dm #skill-trees-page .sr-page-hero.skill-tree-hero {
        --skill-hero-title-color: #93c5fd;
        --skill-hero-text-color: #e2e8f0;
        --skill-hero-icon-bg: rgba(59, 130, 246, 0.2);
        --skill-hero-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28);
    }
    #skill-trees-page .skill-tree-hero .sr-page-hero-inner,
    #skill-trees-page .skill-tree-hero .sr-page-hero-copy {
        display: contents !important;
    }
    #skill-trees-page .skill-hero-icon {
        box-sizing: border-box;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        border: 1px solid var(--skill-hero-icon-border) !important;
        border-radius: 10px !important;
        background: var(--skill-hero-icon-bg) !important;
        color: var(--skill-hero-title-color) !important;
        font-size: 0.9rem !important;
    }
    #skill-trees-page .skill-tree-hero .sr-page-hero-title {
        display: block !important;
        color: var(--skill-hero-title-color) !important;
        background: none !important;
        -webkit-text-fill-color: var(--skill-hero-title-color) !important;
        font-size: 1.02rem !important;
        line-height: 1.08 !important;
        margin: 0 0 4px !important;
        font-weight: 950 !important;
        text-transform: uppercase;
    }
    #skill-trees-page .skill-tree-hero .sr-page-hero-title svg {
        display: none;
    }
    #skill-trees-page .skill-tree-hero .sr-page-hero-subtitle {
        color: var(--skill-hero-text-color) !important;
        font-size: 0.66rem !important;
        line-height: 1.32;
        max-width: 13.5rem;
        margin: 0;
        font-weight: 500;
    }
    #skill-trees-page .skill-tree-hero .sr-page-hero-art {
        width: 62px;
        right: 8px;
        bottom: 4px;
        opacity: 0.92;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16));
    }
    #skill-trees-page .sr-page-actions.skill-tree-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
        justify-content: start;
        margin: 8px 0 14px;
    }
    #skill-trees-page .skill-level-pill,
    #skill-trees-page .skill-back-link {
        min-height: 34px;
        border-radius: 9px !important;
        font-weight: 900 !important;
        font-size: 0.72rem !important;
        padding: 6px 10px !important;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.05);
        white-space: nowrap;
    }
    #skill-trees-page .skill-level-pill {
        background: linear-gradient(135deg, #3b82f6, #075dec) !important;
    }
    #skill-trees-page .skill-back-link {
        background: rgba(255, 255, 255, 0.84) !important;
        border-color: var(--skill-border) !important;
        color: var(--skill-ink) !important;
    }
    #skill-trees-page .skill-xp-overview {
        --bs-gutter-x: 18px;
        --bs-gutter-y: 18px;
    }
    #skill-trees-page .skill-xp-overview .stat-card {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr);
        grid-template-areas:
            "icon label"
            "value value";
        align-items: center;
        align-content: start;
        gap: 10px 12px;
        min-height: 110px;
        padding: 18px !important;
        text-align: left !important;
        border-radius: 16px !important;
        background: rgba(255, 255, 255, 0.92) !important;
        border: 1px solid rgba(214, 228, 248, 0.9) !important;
    }
    #skill-trees-page .skill-xp-overview .stat-card [style*="width:50px"] {
        grid-area: icon;
        width: 34px !important;
        height: 34px !important;
        border-radius: 11px !important;
        margin: 0 !important;
        font-size: 0.95rem !important;
    }
    #skill-trees-page .skill-xp-overview h6 {
        grid-area: label;
        color: #5b6780 !important;
        font-size: 0.66rem !important;
        letter-spacing: 0 !important;
        line-height: 1.15;
        margin: 0 !important;
        overflow-wrap: anywhere;
    }
    #skill-trees-page .skill-xp-overview h3 {
        grid-area: value;
        color: var(--skill-ink) !important;
        font-size: 1.38rem;
        line-height: 1.05;
        margin-top: 6px !important;
    }
    #skill-trees-page .skill-xp-overview h3 span {
        color: var(--skill-blue) !important;
        display: block;
        font-size: 0.62rem !important;
        line-height: 1.1;
        margin: 3px 0 0;
    }
    #skill-trees-page .perks-title {
        color: var(--skill-ink);
        font-size: 1.05rem;
        font-weight: 900;
        margin: 0 0 12px;
    }
    #skill-trees-page .perks-panel {
        padding: 14px;
        border-radius: 18px;
        border: 1px solid rgba(214, 228, 248, 0.9);
        background: rgba(255, 255, 255, 0.76);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }
    #skill-trees-page .perk-list {
        display: grid;
        gap: 10px;
        margin: 0;
    }
    #skill-trees-page .perk-list > [class*="col-"] {
        width: 100%;
        max-width: 100%;
        flex: 0 0 100%;
    }
    #skill-trees-page .perk-card {
        min-height: 94px;
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr) minmax(98px, auto);
        align-items: center;
        gap: 12px;
        padding: 12px !important;
        border-radius: 14px !important;
        background: rgba(255, 255, 255, 0.92) !important;
        border-color: var(--skill-border) !important;
    }
    #skill-trees-page .perk-card > [style*="width:60px"] {
        width: 44px !important;
        height: 44px !important;
        border-radius: 13px !important;
        margin: 0 !important;
        font-size: 1.1rem !important;
    }
    #skill-trees-page .perk-icon {
        background:
            radial-gradient(circle at 28% 18%, rgba(255, 255, 255, 0.72), transparent 34%),
            color-mix(in srgb, var(--perk-color, #3b82f6) 16%, #ffffff) !important;
        border: 1px solid color-mix(in srgb, var(--perk-color, #3b82f6) 26%, #dbeafe) !important;
        color: var(--perk-color, #3b82f6) !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.78), 0 10px 20px rgba(15, 23, 42, 0.06);
    }
    #skill-trees-page .perk-card.is-unlocked .perk-icon {
        background: linear-gradient(135deg, var(--perk-color, #3b82f6), color-mix(in srgb, var(--perk-color, #3b82f6) 72%, #0f172a)) !important;
        border-color: color-mix(in srgb, var(--perk-color, #3b82f6) 78%, #ffffff) !important;
        color: #ffffff !important;
    }
    #skill-trees-page .perk-content {
        min-width: 0;
    }
    #skill-trees-page .perk-card h5 {
        margin: 0 0 4px !important;
        color: var(--skill-ink) !important;
        font-size: 0.88rem;
        font-weight: 900 !important;
    }
    #skill-trees-page .perk-card p {
        min-height: 0 !important;
        margin: 0 !important;
        color: var(--skill-muted) !important;
        font-size: 0.74rem !important;
        line-height: 1.28;
    }
    #skill-trees-page .perk-purchase {
        min-width: 0;
        padding: 0 0 0 12px !important;
        border-top: 0 !important;
        border-left: 1px solid var(--skill-border);
    }
    #skill-trees-page .perk-card .btn {
        min-width: 86px;
        min-height: 34px;
        border-radius: 11px !important;
        font-size: 0.74rem;
        font-weight: 900 !important;
    }
    #skill-trees-page .perk-purchase strong {
        font-size: 0.84rem;
    }
    #skill-trees-page .perk-card .btn[disabled] {
        opacity: 1;
        background: #eef2f7 !important;
        color: #667085 !important;
        border: 0;
    }
    #skill-trees-page .perk-card .btn:not([disabled]) {
        border: 0;
        box-shadow: 0 10px 22px rgba(37, 99, 235, 0.18);
    }
    :root:not(.lm) #skill-trees-page,
    .dm #skill-trees-page {
        --skill-ink: #e5eefc;
        --skill-muted: #b8c5d8;
        --skill-border: rgba(148, 163, 184, 0.28);
    }
    :root:not(.lm) #skill-trees-page .skill-back-link,
    .dm #skill-trees-page .skill-back-link,
    :root:not(.lm) #skill-trees-page .skill-xp-overview .stat-card,
    .dm #skill-trees-page .skill-xp-overview .stat-card,
    :root:not(.lm) #skill-trees-page .perks-panel,
    .dm #skill-trees-page .perks-panel,
    :root:not(.lm) #skill-trees-page .perk-card,
    .dm #skill-trees-page .perk-card {
        background: linear-gradient(145deg, rgba(15, 23, 42, 0.96), rgba(17, 24, 39, 0.92)) !important;
        border-color: var(--skill-border) !important;
        color: #e2e8f0 !important;
    }
    :root:not(.lm) #skill-trees-page .perks-title,
    .dm #skill-trees-page .perks-title,
    :root:not(.lm) #skill-trees-page .perk-card h5,
    .dm #skill-trees-page .perk-card h5,
    :root:not(.lm) #skill-trees-page .skill-xp-overview h3,
    .dm #skill-trees-page .skill-xp-overview h3,
    :root:not(.lm) #skill-trees-page [style*="color:var(--tx)"],
    .dm #skill-trees-page [style*="color:var(--tx)"] {
        color: #e5eefc !important;
    }
    :root:not(.lm) #skill-trees-page .perk-card p,
    .dm #skill-trees-page .perk-card p,
    :root:not(.lm) #skill-trees-page .skill-back-link,
    .dm #skill-trees-page .skill-back-link,
    :root:not(.lm) #skill-trees-page [style*="color:var(--tx2)"],
    .dm #skill-trees-page [style*="color:var(--tx2)"] {
        color: #cbd5e1 !important;
    }
    :root:not(.lm) #skill-trees-page .skill-xp-overview h6,
    .dm #skill-trees-page .skill-xp-overview h6,
    :root:not(.lm) #skill-trees-page [style*="color:var(--tx3)"],
    .dm #skill-trees-page [style*="color:var(--tx3)"] {
        color: #94a3b8 !important;
    }
    :root:not(.lm) #skill-trees-page .perk-purchase,
    .dm #skill-trees-page .perk-purchase {
        border-color: var(--skill-border) !important;
    }
    :root:not(.lm) #skill-trees-page .perk-card > [style*="var(--bg1)"],
    .dm #skill-trees-page .perk-card > [style*="var(--bg1)"],
    :root:not(.lm) #skill-trees-page .perk-card .btn[disabled],
    .dm #skill-trees-page .perk-card .btn[disabled],
    :root:not(.lm) #skill-trees-page .btn-unlock[disabled],
    .dm #skill-trees-page .btn-unlock[disabled] {
        background: rgba(30, 41, 59, 0.9) !important;
        color: #94a3b8 !important;
        border: 1px solid rgba(148, 163, 184, 0.24) !important;
    }
    :root:not(.lm) #skill-trees-page .perk-icon,
    .dm #skill-trees-page .perk-icon {
        background:
            radial-gradient(circle at 28% 18%, rgba(255, 255, 255, 0.14), transparent 34%),
            color-mix(in srgb, var(--perk-color, #60a5fa) 24%, #0f172a) !important;
        border-color: color-mix(in srgb, var(--perk-color, #60a5fa) 44%, rgba(148, 163, 184, 0.28)) !important;
        color: color-mix(in srgb, var(--perk-color, #60a5fa) 82%, #ffffff) !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 10px 20px rgba(0, 0, 0, 0.2);
    }
    :root:not(.lm) #skill-trees-page .perk-card.is-unlocked .perk-icon,
    .dm #skill-trees-page .perk-card.is-unlocked .perk-icon {
        color: #ffffff !important;
    }
    :root:not(.lm) #skill-trees-page .skill-xp-overview .stat-card [style*="rgba(59,130,246,0.1)"],
    .dm #skill-trees-page .skill-xp-overview .stat-card [style*="rgba(59,130,246,0.1)"],
    :root:not(.lm) #skill-trees-page .skill-xp-overview .stat-card [style*="rgba(16,185,129,0.1)"],
    .dm #skill-trees-page .skill-xp-overview .stat-card [style*="rgba(16,185,129,0.1)"],
    :root:not(.lm) #skill-trees-page .skill-xp-overview .stat-card [style*="rgba(139,92,246,0.1)"],
    .dm #skill-trees-page .skill-xp-overview .stat-card [style*="rgba(139,92,246,0.1)"],
    :root:not(.lm) #skill-trees-page .skill-xp-overview .stat-card [style*="rgba(245,158,11,0.1)"],
    .dm #skill-trees-page .skill-xp-overview .stat-card [style*="rgba(245,158,11,0.1)"] {
        background: rgba(30, 41, 59, 0.84) !important;
    }
    #skill-trees-page .sr-page-hero,
    #skill-trees-page .sr-page-actions,
    #skill-trees-page .skill-xp-overview,
    #skill-trees-page .perks-panel,
    #skill-trees-page .perk-list,
    #skill-trees-page .perk-card {
        max-width: 100%;
    }
    #skill-trees-page .skill-back-link,
    #skill-trees-page .skill-level-pill,
    #skill-trees-page .skill-xp-overview h6,
    #skill-trees-page .skill-xp-overview h3,
    #skill-trees-page .perk-card,
    #skill-trees-page .perk-card h5,
    #skill-trees-page .perk-card p,
    #skill-trees-page .perk-purchase,
    #skill-trees-page .perk-card .btn {
        overflow-wrap: anywhere;
    }
    #skill-trees-page .perk-card .btn {
        white-space: normal;
    }
    @media (max-width: 767px) {
        #skill-trees-page .sr-page-hero.skill-tree-hero {
            min-height: 74px !important;
            grid-template-columns: 36px minmax(0, 1fr) !important;
            gap: 9px !important;
            border-radius: 16px;
            padding: 8px 64px 8px 12px !important;
            margin-bottom: 12px;
        }
        #skill-trees-page .skill-tree-hero .sr-page-hero-inner {
            display: contents !important;
            padding-right: 0;
        }
        #skill-trees-page .skill-hero-icon {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.82rem !important;
        }
        #skill-trees-page .skill-tree-hero .sr-page-hero-title {
            font-size: 0.9rem !important;
            line-height: 1.08;
            margin-bottom: 4px !important;
        }
        #skill-trees-page .skill-tree-hero .sr-page-hero-title svg {
            display: none;
        }
        #skill-trees-page .skill-tree-hero .sr-page-hero-subtitle {
            font-size: 0.67rem !important;
            line-height: 1.32;
            max-width: 12.5rem;
        }
        #skill-trees-page .skill-tree-hero .sr-page-hero-art {
            display: block;
            width: 56px;
            right: -4px;
            bottom: 5px;
        }
        #skill-trees-page .sr-page-actions.skill-tree-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 6px !important;
            margin: 8px 0 12px !important;
        }
        #skill-trees-page .skill-level-pill,
        #skill-trees-page .skill-back-link {
            min-height: 32px;
            border-radius: 9px !important;
            font-size: 0.68rem !important;
            padding: 5px 8px !important;
        }
        #skill-trees-page .skill-xp-overview {
            gap: 10px;
            margin-bottom: 18px !important;
        }
        #skill-trees-page .skill-xp-overview .stat-card {
            grid-template-columns: 34px minmax(0, 1fr);
            gap: 8px 9px;
            min-height: 86px;
            padding: 12px !important;
            border-radius: 14px !important;
        }
        #skill-trees-page .skill-xp-overview .stat-card [style*="width:50px"] {
            width: 34px !important;
            height: 34px !important;
            border-radius: 11px !important;
            font-size: 0.95rem !important;
        }
        #skill-trees-page .skill-xp-overview h6 {
            font-size: 0.58rem !important;
            margin-bottom: 4px !important;
        }
        #skill-trees-page .skill-xp-overview h3 {
            font-size: 1.08rem !important;
            margin-top: 2px !important;
        }
        #skill-trees-page .skill-xp-overview h3 span {
            font-size: 0.62rem !important;
        }
        #skill-trees-page .perks-title {
            font-size: 0.95rem;
            margin: 0 0 10px;
        }
        #skill-trees-page .perks-panel {
            padding: 10px;
            border-radius: 15px;
        }
        #skill-trees-page .perk-list {
            gap: 8px;
        }
        #skill-trees-page .perk-card {
            min-height: 88px;
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 10px;
            padding: 10px !important;
            border-radius: 13px !important;
        }
        #skill-trees-page .perk-card > [style*="width:60px"] {
            width: 42px !important;
            height: 42px !important;
            border-radius: 12px !important;
            font-size: 1.05rem !important;
        }
        #skill-trees-page .perk-card h5 {
            font-size: 0.86rem;
            margin-bottom: 4px !important;
        }
        #skill-trees-page .perk-card p {
            font-size: 0.72rem !important;
            line-height: 1.32;
        }
        #skill-trees-page .perk-purchase strong {
            font-size: 0.82rem;
        }
        #skill-trees-page .perk-purchase {
            grid-column: 1 / -1;
            min-width: 0;
            padding: 8px 0 0 !important;
            border-left: 0;
            border-top: 1px solid var(--skill-border) !important;
        }
        #skill-trees-page .perk-purchase .d-flex {
            grid-template-columns: minmax(0, 1fr) auto !important;
            gap: 10px;
        }
        #skill-trees-page .perk-card .btn {
            min-width: 104px;
            min-height: 38px;
            font-size: 0.78rem;
        }
    }
    @media (max-width: 380px) {
        #skill-trees-page .sr-page-hero.skill-tree-hero {
            min-height: 74px !important;
            padding: 8px 58px 8px 12px !important;
        }
        #skill-trees-page .skill-tree-hero .sr-page-hero-title {
            font-size: 0.84rem !important;
        }
        #skill-trees-page .skill-tree-hero .sr-page-hero-subtitle {
            font-size: 0.6rem !important;
            max-width: 10.5rem;
        }
        #skill-trees-page .skill-tree-hero .sr-page-hero-art {
            width: 48px;
            right: -4px;
        }
        #skill-trees-page .perk-card .btn {
            min-width: 92px;
            padding-left: 10px;
            padding-right: 10px;
        }
    }

    /* Final SaaSPro mobile pass for Skill Trees. */
    @media (max-width: 767px) {
        html body #skill-trees-page {
            --skill-pro-card: rgba(255, 255, 255, 0.98);
            --skill-pro-field: rgba(255, 255, 255, 0.96);
            --skill-pro-soft: #f8fafc;
            --skill-pro-border: rgba(15, 23, 42, 0.1);
            --skill-pro-title: #0f172a;
            --skill-pro-text: #334155;
            --skill-pro-muted: #64748b;
            --skill-pro-accent: #2563eb;
            --skill-pro-accent-2: #0891b2;
            --skill-pro-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
            display: grid !important;
            gap: 10px !important;
            max-width: 520px !important;
            margin: 0 auto !important;
            padding: 0 0 16px !important;
            color: var(--skill-pro-title) !important;
        }

        html[data-theme="dark"] body #skill-trees-page,
        :root:not(.lm) body #skill-trees-page,
        body.dm #skill-trees-page,
        .dm #skill-trees-page {
            --skill-pro-card: rgba(15, 23, 42, 0.94);
            --skill-pro-field: rgba(30, 41, 59, 0.9);
            --skill-pro-soft: rgba(51, 65, 85, 0.78);
            --skill-pro-border: rgba(148, 163, 184, 0.24);
            --skill-pro-title: #f8fafc;
            --skill-pro-text: #e2e8f0;
            --skill-pro-muted: #cbd5e1;
            --skill-pro-accent: #93c5fd;
            --skill-pro-accent-2: #67e8f9;
            --skill-pro-shadow: 0 1px 0 rgba(148, 163, 184, 0.08), 0 18px 36px rgba(0, 0, 0, 0.26);
        }

        html body #skill-trees-page .sr-page-hero.skill-tree-hero {
            width: 100% !important;
            height: 69px !important;
            min-height: 69px !important;
            max-height: 69px !important;
            margin: 0 !important;
            padding: 8px 72px 8px 10px !important;
            border-radius: 8px !important;
            overflow: hidden !important;
        }

        html body #skill-trees-page .skill-hero-icon {
            width: 28px !important;
            height: 28px !important;
            min-width: 28px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: 1px solid rgba(255, 255, 255, 0.34) !important;
            border-radius: 8px !important;
            background: rgba(15, 23, 42, 0.24) !important;
            font-size: 0.76rem !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            text-shadow: 0 1px 2px rgba(15, 23, 42, 0.32);
        }

        html body #skill-trees-page .skill-hero-icon i {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            opacity: 1 !important;
        }

        html body #skill-trees-page .skill-tree-hero .sr-page-hero-title {
            margin: 0 0 3px !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            font-size: 0.72rem !important;
            font-weight: 900 !important;
            line-height: 1.08 !important;
            text-transform: none !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        html body #skill-trees-page .skill-tree-hero .sr-page-hero-subtitle {
            display: -webkit-box !important;
            max-width: 12.2rem !important;
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

        html body #skill-trees-page .skill-tree-hero .sr-page-hero-art {
            width: 72px !important;
            right: -5px !important;
            bottom: -2px !important;
            opacity: 0.98 !important;
            filter: drop-shadow(0 10px 16px rgba(15, 23, 42, 0.22));
        }

        html body #skill-trees-page .sr-page-actions.skill-tree-actions {
            display: grid !important;
            grid-template-columns: 0.72fr 1fr !important;
            gap: 7px !important;
            margin: 0 !important;
        }

        html body #skill-trees-page .skill-level-pill,
        html body #skill-trees-page .skill-back-link {
            width: 100% !important;
            min-height: 34px !important;
            padding: 7px 8px !important;
            border-radius: 8px !important;
            font-size: 0.64rem !important;
            font-weight: 900 !important;
            line-height: 1.12 !important;
            box-shadow: none !important;
            white-space: normal !important;
        }

        html body #skill-trees-page .skill-level-pill {
            border: 0 !important;
            background: linear-gradient(135deg, var(--skill-pro-accent), var(--skill-pro-accent-2)) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
        }

        html body #skill-trees-page .skill-back-link {
            border: 1px solid var(--skill-pro-border) !important;
            background: var(--skill-pro-field) !important;
            color: var(--skill-pro-accent) !important;
        }

        html body #skill-trees-page .skill-xp-overview {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin: 0 !important;
            --bs-gutter-x: 0;
            --bs-gutter-y: 0;
        }

        html body #skill-trees-page .skill-xp-overview > [class*="col-"] {
            width: 100% !important;
            max-width: none !important;
            padding: 0 !important;
        }

        html body #skill-trees-page .skill-xp-overview .stat-card {
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            grid-template-areas:
                "icon label"
                "value value" !important;
            gap: 7px 8px !important;
            min-height: 82px !important;
            padding: 10px !important;
            border: 1px solid var(--skill-pro-border) !important;
            border-radius: 8px !important;
            background: var(--skill-pro-card) !important;
            box-shadow: var(--skill-pro-shadow) !important;
            color: var(--skill-pro-title) !important;
            text-align: left !important;
        }

        html body #skill-trees-page .skill-xp-overview .stat-card [style*="width:50px"] {
            grid-area: icon;
            width: 30px !important;
            height: 30px !important;
            border-radius: 8px !important;
            margin: 0 !important;
            font-size: 0.84rem !important;
        }

        html body #skill-trees-page .skill-xp-overview h6 {
            grid-area: label;
            margin: 0 !important;
            color: var(--skill-pro-muted) !important;
            font-size: 0.55rem !important;
            font-weight: 900 !important;
            letter-spacing: 0 !important;
            line-height: 1.1 !important;
            text-transform: none !important;
        }

        html body #skill-trees-page .skill-xp-overview h3 {
            grid-area: value;
            margin: 2px 0 0 !important;
            color: var(--skill-pro-title) !important;
            font-size: 1.04rem !important;
            font-weight: 900 !important;
            line-height: 1.05 !important;
        }

        html body #skill-trees-page .skill-xp-overview h3 span {
            display: inline !important;
            color: var(--skill-pro-accent) !important;
            font-size: 0.58rem !important;
            font-weight: 900 !important;
        }

        html body #skill-trees-page .perks-panel {
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        html body #skill-trees-page .perks-title {
            margin: 0 0 8px !important;
            color: var(--skill-pro-title) !important;
            font-size: 0.8rem !important;
            font-weight: 900 !important;
            line-height: 1.18 !important;
        }

        html body #skill-trees-page .perk-list {
            display: grid !important;
            gap: 8px !important;
        }

        html body #skill-trees-page .perk-list > [class*="col-"] {
            width: 100% !important;
            max-width: none !important;
            padding: 0 !important;
        }

        html body #skill-trees-page .perk-card {
            display: grid !important;
            grid-template-columns: 38px minmax(0, 1fr) !important;
            align-items: start !important;
            gap: 8px 10px !important;
            min-height: 0 !important;
            padding: 10px !important;
            border: 1px solid var(--skill-pro-border) !important;
            border-radius: 8px !important;
            background: var(--skill-pro-card) !important;
            box-shadow: var(--skill-pro-shadow) !important;
            color: var(--skill-pro-title) !important;
        }

        html body #skill-trees-page .perk-card > .perk-icon {
            width: 38px !important;
            height: 38px !important;
            border-radius: 8px !important;
            margin: 0 !important;
            font-size: 0.96rem !important;
        }

        html body #skill-trees-page .perk-content {
            min-width: 0 !important;
        }

        html body #skill-trees-page .perk-card h5 {
            margin: 0 0 4px !important;
            color: var(--skill-pro-title) !important;
            font-size: 0.78rem !important;
            font-weight: 900 !important;
            line-height: 1.18 !important;
        }

        html body #skill-trees-page .perk-card p {
            margin: 0 !important;
            color: var(--skill-pro-muted) !important;
            font-size: 0.66rem !important;
            font-weight: 700 !important;
            line-height: 1.28 !important;
        }

        html body #skill-trees-page .perk-purchase {
            grid-column: 1 / -1 !important;
            padding: 8px 0 0 !important;
            border-top: 1px solid var(--skill-pro-border) !important;
            border-left: 0 !important;
        }

        html body #skill-trees-page .perk-purchase .d-flex {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            gap: 8px !important;
            align-items: center !important;
        }

        html body #skill-trees-page .perk-purchase span {
            color: var(--skill-pro-muted) !important;
            font-size: 0.52rem !important;
            font-weight: 900 !important;
            letter-spacing: 0 !important;
        }

        html body #skill-trees-page .perk-purchase strong {
            font-size: 0.76rem !important;
            font-weight: 900 !important;
            line-height: 1.1 !important;
        }

        html body #skill-trees-page .perk-card .btn {
            min-width: 92px !important;
            min-height: 34px !important;
            padding: 6px 10px !important;
            border-radius: 8px !important;
            font-size: 0.68rem !important;
            font-weight: 900 !important;
            line-height: 1.12 !important;
            white-space: nowrap !important;
        }
    }

    @media (max-width: 390px) {
        html body #skill-trees-page .sr-page-hero.skill-tree-hero {
            padding: 8px 66px 8px 10px !important;
        }

        html body #skill-trees-page .skill-tree-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }

        html body #skill-trees-page .skill-tree-hero .sr-page-hero-subtitle {
            max-width: 10.8rem !important;
            font-size: 0.46rem !important;
        }

        html body #skill-trees-page .skill-tree-hero .sr-page-hero-art {
            width: 66px !important;
            right: -6px !important;
        }

        html body #skill-trees-page .skill-xp-overview .stat-card {
            padding: 9px !important;
        }

        html body #skill-trees-page .perk-card .btn {
            min-width: 86px !important;
            padding-inline: 8px !important;
        }
    }
</style>

<div class="db-section active animate-fade-up" id="skill-trees-page">
    <div class="sr-page-hero skill-tree-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="skill-hero-icon"><i class="fa-solid fa-code-branch"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 4v5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M7 14h10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M7 14v4M17 14v4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="12" cy="4" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="7" cy="20" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="17" cy="20" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="11" r="2" fill="none" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        Skill Trees
                    </h4>
                    <p class="sr-page-hero-subtitle">Unlock perks by earning Skill XP in PH Challenges.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs>
                <linearGradient id="skillPanel" x1="36" y1="18" x2="176" y2="128" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#DBEAFE"/>
                    <stop offset="1" stop-color="#ECFEFF"/>
                </linearGradient>
                <linearGradient id="skillBlue" x1="64" y1="34" x2="166" y2="118" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#3B82F6"/>
                    <stop offset="1" stop-color="#06B6D4"/>
                </linearGradient>
            </defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#skillPanel)" stroke="#BFDBFE" stroke-width="3"/>
            <path d="M110 52v30M78 104h64M78 104v15M142 104v15" stroke="#60A5FA" stroke-width="7" stroke-linecap="round"/>
            <circle cx="110" cy="45" r="20" fill="url(#skillBlue)"/>
            <circle cx="110" cy="88" r="17" fill="#38BDF8"/>
            <circle cx="78" cy="119" r="16" fill="#22C55E"/>
            <circle cx="142" cy="119" r="16" fill="#F59E0B"/>
            <path d="m101 45 6 6 13-15" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M70 69h25M126 69h25" stroke="#93C5FD" stroke-width="6" stroke-linecap="round" opacity=".8"/>
            <path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="sr-page-actions skill-tree-actions">
        <span class="badge bg-primary skill-level-pill d-inline-flex align-items-center justify-content-center" style="font-size:14px;padding:10px 15px;border-radius:12px;">Level {{ $profile->player_level ?? 1 }}</span>
        <a href="{{ route('user.learning') }}" class="btn btn-sm skill-back-link d-inline-flex align-items-center justify-content-center" style="background:var(--bg3); border:1px solid var(--bd); color:var(--tx2); border-radius:10px; font-weight:600; white-space:nowrap;">
            <i class="fa-solid fa-arrow-left me-1"></i> <span>PH Challenges</span>
        </a>
    </div>

    <!-- Skill XP Overview -->
    <div class="row g-4 mb-4 skill-xp-overview">
        <div class="col-6 col-md-3 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="card stat-card premium-panel text-center" style="border:none;padding:24px;">
                <div style="width:50px;height:50px;background:rgba(59,130,246,0.1);color:#3b82f6;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-crown"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Leadership</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->leadership_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 animate-fade-up" style="animation-delay: 0.2s;">
            <div class="card stat-card premium-panel text-center" style="border:none;padding:24px;">
                <div style="width:50px;height:50px;background:rgba(16,185,129,0.1);color:#10b981;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Communication</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->communication_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 animate-fade-up" style="animation-delay: 0.3s;">
            <div class="card stat-card premium-panel text-center" style="border:none;padding:24px;">
                <div style="width:50px;height:50px;background:rgba(139,92,246,0.1);color:#8b5cf6;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-laptop-code"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Technical</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->technical_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
        <div class="col-6 col-md-3 animate-fade-up" style="animation-delay: 0.4s;">
            <div class="card stat-card premium-panel text-center" style="border:none;padding:24px;">
                <div style="width:50px;height:50px;background:rgba(245,158,11,0.1);color:#f59e0b;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:20px;">
                    <i class="fa-solid fa-lightbulb"></i>
                </div>
                <h6 style="color:var(--tx3);font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;">Problem Solving</h6>
                <h3 style="color:var(--tx);font-weight:800;margin:0;">{{ $profile->problem_solving_xp ?? 0 }} <span style="font-size:12px;color:var(--tx3)">XP</span></h3>
            </div>
        </div>
    </div>

    <section class="perks-panel">
        <h5 class="perks-title">Available Perks</h5>

        <div class="row g-0 perk-list">
            @foreach($perks as $id => $perk)
                @php
                    $isUnlocked = $profile->hasPerk($id);
                    $colName = $perk['type'] . '_xp';
                    $userXP = $profile->$colName ?? 0;
                    $canAfford = $userXP >= $perk['cost'];

                    $color = '#3b82f6';
                    if ($perk['type'] == 'communication') $color = '#10b981';
                    if ($perk['type'] == 'technical') $color = '#8b5cf6';
                    if ($perk['type'] == 'problem_solving') $color = '#f59e0b';
                @endphp

                <div class="col-12 animate-fade-up" style="animation-delay: {{ 0.4 + ($loop->index * 0.1) }}s;">
                    <div class="card perk-card h-100 premium-panel {{ $isUnlocked ? 'is-unlocked' : '' }}" style="--perk-color: {{ $color }}; border:1px solid {{ $isUnlocked ? $color : 'rgba(0,0,0,0.05)' }} !important; padding:24px; position:relative; overflow:hidden;">
                        @if($isUnlocked)
                            <div style="position:absolute;top:-10px;right:-10px;background:{{ $color }};color:#fff;font-size:12px;font-weight:bold;padding:15px 20px 5px 15px;transform:rotate(45deg);z-index:2;">
                                <i class="fa-solid fa-check" style="transform:rotate(-45deg)"></i>
                            </div>
                        @endif

                        <div class="perk-icon" style="width:60px;height:60px;background:{{ $isUnlocked ? $color : 'var(--bg1)' }};color:{{ $isUnlocked ? '#fff' : $color }};border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:15px;transition:0.3s all;">
                            <i class="fa-solid {{ $perk['icon'] }}"></i>
                        </div>

                        <div class="perk-content">
                            <h5 style="color:var(--tx);font-weight:700;margin-bottom:10px;">{{ $perk['name'] }}</h5>
                            <p style="color:var(--tx3);font-size:14px;min-height:45px;">{{ $perk['description'] }}</p>
                        </div>

                        <div class="perk-purchase mt-auto pt-3 border-top" style="border-color:var(--border-color) !important;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span style="font-size:12px;color:var(--tx3);text-transform:uppercase;letter-spacing:1px;display:block;">Cost</span>
                                    <strong style="color:{{ $color }}">{{ $perk['cost'] }} XP</strong>
                                </div>
                                <div>
                                    @if($isUnlocked)
                                        <button class="btn" style="background:var(--bg1);color:var(--tx3);border-radius:12px;font-weight:600;" disabled>Unlocked</button>
                                    @else
                                        <button class="btn btn-unlock btn-shine" data-id="{{ $id }}" style="background:{{ $canAfford ? $color : 'var(--bg1)' }};color:{{ $canAfford ? '#fff' : 'var(--tx3)' }};border-radius:12px;font-weight:600;" {{ $canAfford ? '' : 'disabled' }}>
                                            <i class="fa-solid {{ $canAfford ? 'fa-unlock' : 'fa-lock' }} me-2"></i>{{ $canAfford ? 'Unlock' : 'Locked' }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const unlockBtns = document.querySelectorAll('.btn-unlock');
    
    unlockBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const perkId = this.dataset.id;
            const originalText = this.innerHTML;
            
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            fetch("{{ route('user.skills.unlock') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    perk_id: perkId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Perk Unlocked!',
                        text: data.message,
                        background: document.documentElement.classList.contains('lm') ? '#fff' : '#1e1e1e',
                        color: document.documentElement.classList.contains('lm') ? '#000' : '#fff'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message,
                        background: document.documentElement.classList.contains('lm') ? '#fff' : '#1e1e1e',
                        color: document.documentElement.classList.contains('lm') ? '#000' : '#fff'
                    });
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    });
});
</script>
@endpush

