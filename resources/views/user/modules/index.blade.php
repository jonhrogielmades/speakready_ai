@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Modules')

@section('content')
@include('partials.page-hero-styles')
<style>
    .module-card {
        background: var(--sf);
        border: 1px solid rgba(147, 197, 253, 0.28);
        border-radius: 18px;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s;
    }
    .module-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 50px rgba(37, 99, 235, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.08);
        border-color: rgba(59, 130, 246, 0.42);
    }
    .module-card-media {
        position: relative;
        height: 152px;
        overflow: hidden;
        background:
            radial-gradient(circle at 82% 24%, rgba(244, 114, 182, 0.22), transparent 34%),
            radial-gradient(circle at 15% 5%, rgba(59, 130, 246, 0.18), transparent 32%),
            linear-gradient(135deg, rgba(219, 234, 254, 0.95), rgba(250, 232, 255, 0.72));
    }
    .module-card-media::before {
        content: "";
        position: absolute;
        inset: auto -24px -62px -24px;
        height: 110px;
        border-radius: 50% 50% 0 0;
        background: rgba(255, 255, 255, 0.16);
        transform: rotate(-7deg);
    }
    .module-card-media::after {
        content: "";
        position: absolute;
        top: 14px;
        right: 22px;
        width: 120px;
        height: 120px;
        border: 2px solid rgba(255, 255, 255, 0.5);
        border-radius: 999px;
        box-shadow:
            16px 0 0 rgba(255,255,255,0.18),
            32px 0 0 rgba(255,255,255,0.12);
        opacity: 0.72;
    }
    .module-card-badges {
        position: absolute;
        top: 14px;
        left: 14px;
        right: 14px;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .module-card-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 32px;
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.88);
        color: #0f172a;
        font-size: 0.82rem;
        font-weight: 800;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    }
    .module-card-badge i {
        color: #2563eb;
    }
    .module-card-badge.difficulty-beginner {
        color: #ef4444;
    }
    .module-card-badge.difficulty-intermediate {
        color: #f59e0b;
    }
    .module-card-badge.difficulty-advanced {
        color: #ef4444;
    }
    .module-card-icon {
        position: absolute;
        bottom: 22px;
        left: 28px;
        z-index: 2;
        width: 58px;
        height: 58px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(37, 99, 235, 0.52);
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.9);
        color: #2563eb;
        font-size: 1.35rem;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.16);
    }
    .module-card-body {
        padding: 22px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .module-card-title {
        color: var(--tx);
        font-weight: 900;
        margin: 0 0 10px;
        font-size: 1.22rem;
        line-height: 1.22;
    }
    .module-card-desc {
        color: var(--tx3);
        font-size: 0.92rem;
        line-height: 1.55;
        margin: 0 0 18px;
    }
    .module-card-footer {
        margin-top: auto;
        padding-top: 16px;
        border-top: 1px solid rgba(148, 163, 184, 0.2);
        display: grid;
        gap: 12px;
    }
    .module-card-views {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--tx2);
        font-size: 0.88rem;
        font-weight: 700;
    }
    .module-card-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        min-height: 44px;
        width: 100%;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #38bdf8, #2563eb);
        color: #fff;
        font-weight: 900;
        text-decoration: none;
        box-shadow: 0 12px 22px rgba(37, 99, 235, 0.2);
    }
    .module-card-link:hover {
        color: #fff;
        transform: translateY(-1px);
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
    .module-topic-select-wrap {
        display: none;
    }

    .module-smart-panel {
        background:
            radial-gradient(circle at 92% 0%, rgba(219, 234, 254, 0.55), transparent 34%),
            var(--sf);
        border: 1px solid rgba(147, 197, 253, 0.34);
        border-radius: 18px;
        padding: clamp(15px, 2.4vw, 22px);
        margin-bottom: 22px;
        box-shadow: 0 14px 34px rgba(37, 99, 235, 0.08);
    }
    .module-smart-title {
        color: var(--tx);
        font-weight: 800;
        margin: 0;
        font-size: clamp(1.1rem, 2.4vw, 1.55rem);
        line-height: 1.2;
    }
    .module-smart-subtitle {
        color: var(--tx3);
        margin: 7px 0 0;
        max-width: 620px;
        font-size: clamp(0.88rem, 1.8vw, 1.06rem);
        line-height: 1.55;
    }
    .module-smart-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        flex-wrap: wrap;
    }
    .module-section-head {
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr);
        align-items: center;
        gap: 16px;
        margin-bottom: 16px;
    }
    .module-section-icon {
        width: 56px;
        height: 56px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(147, 197, 253, 0.42);
        border-radius: 16px;
        color: #0ea5e9;
        background: rgba(248, 250, 252, 0.72);
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
    }
    .module-section-icon i {
        font-size: 1.35rem;
    }
    .module-progress-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 9px 18px;
        border: 1px solid rgba(147, 197, 253, 0.38);
        color: #2563eb;
        background: rgba(255, 255, 255, 0.72);
        border-radius: 12px;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
    }
    .module-progress-link:hover {
        color: #1d4ed8;
        border-color: rgba(37, 99, 235, 0.44);
        background: #fff;
    }
    .module-rec-grid,
    .module-path-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 14px;
    }
    .module-rec-item,
    .module-path-item {
        display: flex;
        gap: 12px;
        min-width: 0;
        border: 1px solid rgba(147, 197, 253, 0.34);
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.68);
        padding: 14px;
        color: inherit;
        text-decoration: none;
        box-shadow: 0 8px 22px rgba(37, 99, 235, 0.06);
    }
    .module-rec-item:hover,
    .module-path-item:hover {
        border-color: rgba(37, 99, 235, 0.45);
        background: rgba(255, 255, 255, 0.9);
        color: inherit;
    }
    .module-rec-icon {
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--rec-color, #3b82f6);
        background: rgba(219, 234, 254, 0.9);
    }
    .module-rec-copy,
    .module-path-copy {
        min-width: 0;
    }
    .module-rec-copy strong,
    .module-path-copy strong {
        display: block;
        color: var(--tx);
        font-size: 0.96rem;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }
    .module-rec-copy span,
    .module-path-copy span {
        display: block;
        color: var(--tx3);
        font-size: 0.84rem;
        line-height: 1.45;
        margin-top: 4px;
    }
    .module-path-progress {
        height: 7px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.24);
        overflow: hidden;
        margin-top: 8px;
    }
    .module-path-progress span {
        display: block;
        height: 100%;
        width: var(--path-progress, 0%);
        background: linear-gradient(90deg, #38bdf8, #2563eb);
        border-radius: inherit;
    }

    .module-path-panel {
        padding: clamp(16px, 2.6vw, 24px);
    }
    .module-path-panel .module-path-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }
    .module-path-panel .module-path-item {
        align-items: center;
        gap: 18px;
        min-height: 118px;
        padding: clamp(16px, 2.4vw, 22px);
        border-radius: 18px;
    }
    .module-path-panel .module-rec-icon {
        width: 70px;
        height: 70px;
        flex-basis: 70px;
        border-radius: 18px;
        color: #0ea5e9;
    }
    .module-path-panel .module-rec-icon i {
        font-size: 1.55rem;
    }
    .module-path-panel .module-path-copy strong {
        font-size: clamp(1.1rem, 2.2vw, 1.45rem);
        line-height: 1.25;
    }
    .module-path-panel .module-path-copy span {
        font-size: clamp(0.9rem, 1.7vw, 1.1rem);
        margin-top: 8px;
    }
    .module-path-panel .module-path-progress {
        width: min(100%, 420px);
        height: 8px;
        margin-top: 12px;
    }

    .module-topic-select-wrap {
        margin-bottom: 14px;
    }
    .module-topic-select-shell {
        position: relative;
        max-width: 420px;
    }
    .module-topic-select-shell::after {
        content: "\f078";
        position: absolute;
        top: 50%;
        right: 16px;
        transform: translateY(-50%);
        color: #0f172a;
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        font-size: 0.86rem;
        line-height: 1;
        pointer-events: none;
        z-index: 2;
    }

    .lm .module-smart-panel,
    .lm .module-rec-item,
    .lm .module-path-item {
        background: var(--sf);
        border-color: var(--bd);
    }

    .lm .module-progress-link {
        background: var(--bg3);
        color: var(--tx);
        border-color: var(--bd);
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

    .modules-hero {
        position: relative;
        display: grid;
        grid-template-columns: clamp(58px, 10vw, 96px) minmax(0, 1fr) clamp(220px, 30vw, 360px);
        align-items: center;
        gap: clamp(18px, 3vw, 42px);
        width: 100%;
        min-height: clamp(230px, 29vw, 330px);
        margin: 0 0 18px;
        padding: clamp(24px, 4vw, 50px);
        overflow: hidden;
        border: 1px solid rgba(147, 197, 253, 0.72);
        border-radius: 28px;
        background:
            radial-gradient(circle at 80% 10%, rgba(219, 234, 254, 0.92), transparent 38%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(239, 246, 255, 0.9) 62%, rgba(219, 234, 254, 0.72));
        box-shadow: 0 18px 44px rgba(37, 99, 235, 0.12);
    }

    .lm .modules-hero {
        background:
            radial-gradient(circle at 80% 10%, rgba(30, 64, 175, 0.22), transparent 40%),
            linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(17, 24, 39, 0.88) 58%, rgba(30, 58, 138, 0.38));
        border-color: rgba(96, 165, 250, 0.34);
    }

    body:not(.lm) .modules-hero {
        background:
            radial-gradient(circle at 86% 18%, rgba(219, 234, 254, 0.72), transparent 35%),
            linear-gradient(142deg, rgba(255, 255, 255, 0.99) 0%, rgba(248, 251, 255, 0.98) 52%, rgba(219, 234, 254, 0.94) 100%);
    }

    .modules-hero::before {
        content: "";
        position: absolute;
        right: -7%;
        bottom: -28%;
        width: 72%;
        height: 72%;
        border-radius: 55% 0 0 0;
        background: rgba(191, 219, 254, 0.28);
        transform: rotate(-7deg);
        pointer-events: none;
    }

    .modules-hero::after {
        content: "";
        position: absolute;
        top: 70px;
        right: 44px;
        width: 122px;
        height: 88px;
        opacity: 0.34;
        background-image: radial-gradient(circle, rgba(96, 165, 250, 0.45) 2px, transparent 2.5px);
        background-size: 18px 18px;
        pointer-events: none;
    }

    .modules-hero-copy {
        position: relative;
        z-index: 1;
        display: block;
        min-width: 0;
    }

    .modules-hero-icon {
        position: relative;
        z-index: 1;
        width: clamp(58px, 10vw, 96px);
        height: clamp(58px, 10vw, 96px);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(147, 197, 253, 0.42);
        border-radius: 20px;
        color: #2563eb;
        background: rgba(239, 246, 255, 0.84);
        box-shadow: 0 18px 34px rgba(37, 99, 235, 0.1);
    }

    .modules-hero-icon svg {
        width: 56%;
        height: 56%;
    }

    .modules-hero-title {
        margin: 0;
        max-width: 720px;
        font-size: clamp(2.2rem, 5.2vw, 4.8rem);
        line-height: 1.08;
        font-weight: 900;
        text-transform: uppercase;
        color: #2563eb;
        overflow-wrap: anywhere;
    }

    .modules-hero-subtitle {
        max-width: 640px;
        margin: 22px 0 0;
        color: #1f2f4a;
        font-size: clamp(1.05rem, 2.15vw, 1.72rem);
        line-height: 1.55;
    }

    body:not(.lm) .modules-hero-title {
        color: #1d4ed8;
        text-shadow: none;
    }

    body:not(.lm) .modules-hero-subtitle {
        color: #253858;
    }

    body:not(.lm) .modules-hero-icon {
        color: #2563eb;
        background: rgba(239, 246, 255, 0.96);
    }

    body:not(.lm) .modules-hero-art {
        opacity: 1;
        filter: drop-shadow(0 16px 24px rgba(37, 99, 235, 0.16));
    }

    .lm .modules-hero-title {
        color: #dbeafe;
        text-shadow: 0 2px 12px rgba(0, 0, 0, 0.22);
    }

    .lm .modules-hero-subtitle {
        color: #cbd5e1;
    }

    .modules-hero-art {
        position: relative;
        z-index: 1;
        width: min(100%, 350px);
        justify-self: end;
        filter: drop-shadow(0 22px 30px rgba(37, 99, 235, 0.18));
        transform-origin: center;
        animation: modulesArtFloat 4.8s ease-in-out infinite;
    }

    .modules-hero-art .modules-art-card {
        transform-origin: center;
        animation: modulesCardTilt 5.6s ease-in-out infinite;
    }

    .modules-hero-art .modules-art-check {
        transform-origin: 222px 118px;
        animation: modulesCheckPulse 2.8s ease-in-out infinite;
    }

    .modules-hero-art .modules-art-line {
        animation: modulesLineGlow 3.2s ease-in-out infinite;
    }

    .modules-hero-art .modules-art-line:nth-of-type(2) {
        animation-delay: 0.25s;
    }

    .modules-hero-art .modules-art-line:nth-of-type(3) {
        animation-delay: 0.5s;
    }

    #interview-modules-page {
        color: #0f172a;
    }

    #interview-modules-page .modules-hero {
        background:
            radial-gradient(circle at 86% 18%, rgba(219, 234, 254, 0.72), transparent 35%),
            linear-gradient(142deg, rgba(255, 255, 255, 0.99) 0%, rgba(248, 251, 255, 0.98) 52%, rgba(219, 234, 254, 0.94) 100%);
        border-color: rgba(147, 197, 253, 0.72);
        box-shadow: 0 18px 44px rgba(37, 99, 235, 0.12);
    }

    #interview-modules-page .modules-hero-title {
        color: #1d4ed8;
        text-shadow: none;
    }

    #interview-modules-page .modules-hero-subtitle {
        color: #253858;
    }

    #interview-modules-page .modules-hero-icon {
        color: #2563eb;
        background: rgba(239, 246, 255, 0.9);
    }

    #interview-modules-page .module-smart-panel,
    #interview-modules-page .module-rec-item,
    #interview-modules-page .module-path-item,
    #interview-modules-page .module-card {
        background:
            radial-gradient(circle at 92% 0%, rgba(219, 234, 254, 0.4), transparent 34%),
            rgba(255, 255, 255, 0.96);
        border-color: rgba(147, 197, 253, 0.34);
        color: #0f172a;
    }

    #interview-modules-page .module-smart-title,
    #interview-modules-page .module-card-title,
    #interview-modules-page .module-rec-copy strong,
    #interview-modules-page .module-path-copy strong {
        color: #0f172a;
    }

    #interview-modules-page .module-smart-subtitle,
    #interview-modules-page .module-card-desc,
    #interview-modules-page .module-rec-copy span,
    #interview-modules-page .module-path-copy span,
    #interview-modules-page .module-card-views {
        color: #5b6b86;
    }

    #interview-modules-page .db-top-search,
    #interview-modules-page .module-topic-select,
    #interview-modules-page .module-progress-link {
        background: rgba(255, 255, 255, 0.86) !important;
        border-color: rgba(147, 197, 253, 0.52) !important;
        color: #0f172a !important;
    }

    #interview-modules-page .module-topic-select {
        appearance: none;
        -webkit-appearance: none;
        padding-right: 42px !important;
    }

    #interview-modules-page .db-top-search input {
        color: #0f172a !important;
    }

    #interview-modules-page .db-top-search input::placeholder {
        color: #64748b;
        font-weight: 400 !important;
    }

    /* Animations */
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }

    @keyframes modulesArtFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-7px); }
    }

    @keyframes modulesCardTilt {
        0%, 100% { transform: rotate(0deg) translateX(0); }
        50% { transform: rotate(-1.2deg) translateX(-2px); }
    }

    @keyframes modulesCheckPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.06); opacity: 0.92; }
    }

    @keyframes modulesLineGlow {
        0%, 100% { opacity: 0.78; transform: translateX(0); }
        50% { opacity: 1; transform: translateX(5px); }
    }

    @media (prefers-reduced-motion: reduce) {
        #interview-modules-page .modules-hero-art,
        #interview-modules-page .modules-hero-art * {
            animation: none !important;
        }
    }

    @keyframes shineEffect { 0% { left: -100%; } 20% { left: 100%; } 100% { left: 100%; } }
    .btn-shine { position: relative; overflow: hidden; }
    .btn-shine::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%); transform: skewX(-20deg); animation: shineEffect 4s infinite; }

    @media (max-width: 767px) {
        #interview-modules-page .sr-page-actions {
            display: block !important;
            margin-bottom: 12px !important;
        }
        #interview-modules-page .modules-hero {
            grid-template-columns: 42px minmax(0, 1fr) 86px;
            gap: 12px;
            min-height: 0;
            padding: 15px;
            border-radius: 18px;
        }
        #interview-modules-page .modules-hero::after {
            display: none;
        }
        #interview-modules-page .modules-hero-copy {
            display: block;
        }
        #interview-modules-page .modules-hero-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
        }
        #interview-modules-page .modules-hero-icon svg {
            width: 25px;
            height: 25px;
        }
        #interview-modules-page .modules-hero-title {
            font-size: clamp(1.25rem, 8vw, 1.72rem);
            line-height: 1.16;
        }
        #interview-modules-page .modules-hero-subtitle {
            margin: 0;
            font-size: 0.86rem;
            line-height: 1.55;
        }
        #interview-modules-page .modules-hero-art {
            width: 86px;
            justify-self: end;
            margin-top: 0;
        }
        #interview-modules-page .db-top-search {
            max-width: none !important;
            min-height: 46px;
            padding: 10px 12px !important;
            display: flex;
            align-items: center;
            gap: 9px;
        }
        #interview-modules-page #nav-pills-container {
            display: none !important;
        }
        #interview-modules-page .module-rec-grid,
        #interview-modules-page .module-path-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        #interview-modules-page .module-smart-panel {
            padding: 14px;
            border-radius: 14px;
            margin-bottom: 16px;
        }
        #interview-modules-page .module-smart-head {
            display: block;
        }
        #interview-modules-page .module-section-head {
            grid-template-columns: 44px minmax(0, 1fr);
            gap: 11px;
            margin-bottom: 14px;
        }
        #interview-modules-page .module-section-icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
        }
        #interview-modules-page .module-section-icon i {
            font-size: 1.08rem;
        }
        #interview-modules-page .module-smart-title {
            font-size: 1.2rem;
        }
        #interview-modules-page .module-smart-subtitle {
            font-size: 0.9rem;
            line-height: 1.5;
        }
        #interview-modules-page .module-progress-link {
            width: 100%;
            min-height: 42px;
            margin-top: 13px;
        }
        #interview-modules-page .module-topic-select-wrap {
            display: block;
            margin-bottom: 12px;
        }
        #interview-modules-page .module-topic-select-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--tx3);
            font-size: 0.72rem;
            font-weight: 800;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 0;
        }
        #interview-modules-page .module-topic-select {
            width: 100%;
            min-height: 44px;
            border: 1px solid rgba(147, 197, 253, 0.74);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.72);
            color: var(--tx);
            padding: 10px 42px 10px 14px;
            font-weight: 800;
            font-size: 0.95rem;
            outline: none;
            appearance: none;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
        }
        #interview-modules-page .module-rec-item,
        #interview-modules-page .module-path-item {
            border-radius: 14px;
            padding: 12px;
            gap: 11px;
        }
        #interview-modules-page .module-rec-icon {
            width: 42px;
            height: 42px;
            flex-basis: 42px;
            border-radius: 12px;
        }
        #interview-modules-page .module-rec-copy strong,
        #interview-modules-page .module-path-copy strong {
            font-size: 0.94rem;
        }
        #interview-modules-page .module-rec-copy span,
        #interview-modules-page .module-path-copy span {
            font-size: 0.82rem;
        }
        #interview-modules-page .module-path-panel .module-path-item {
            min-height: 0;
            padding: 13px;
            gap: 12px;
        }
        #interview-modules-page .module-path-panel .module-rec-icon {
            width: 48px;
            height: 48px;
            flex-basis: 48px;
            border-radius: 13px;
        }
        #interview-modules-page .module-path-panel .module-rec-icon i {
            font-size: 1.1rem;
        }
        #interview-modules-page .module-path-panel .module-path-copy strong {
            font-size: 0.98rem;
        }
        #interview-modules-page .module-path-panel .module-path-copy span {
            margin-top: 5px;
            font-size: 0.83rem;
        }
        #interview-modules-page .module-path-panel .module-path-progress {
            height: 7px;
            margin-top: 8px;
        }
        #interview-modules-page .module-card {
            border-radius: 14px !important;
            min-height: auto;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        #interview-modules-page .module-card:hover {
            transform: none;
        }
        #interview-modules-page .module-card-media {
            height: 118px;
        }
        #interview-modules-page .module-card-media::after {
            width: 82px;
            height: 82px;
            right: 16px;
        }
        #interview-modules-page .module-card-badges {
            top: 10px;
            left: 10px;
            right: 10px;
            gap: 6px;
        }
        #interview-modules-page .module-card-badge {
            min-height: 28px;
            padding: 6px 10px;
            font-size: 0.76rem;
        }
        #interview-modules-page .module-card-icon {
            width: 44px;
            height: 44px;
            bottom: 14px;
            left: 16px;
            border-radius: 13px;
            font-size: 1.08rem;
        }
        #interview-modules-page .module-card-body {
            padding: 15px;
        }
        #interview-modules-page .module-card-title {
            font-size: 1rem;
            line-height: 1.25;
            margin-bottom: 8px;
        }
        #interview-modules-page .module-card-desc {
            font-size: 0.82rem;
            line-height: 1.45;
            margin-bottom: 14px;
        }
        #interview-modules-page .module-card-footer {
            padding-top: 12px;
            gap: 10px;
        }
        #interview-modules-page .module-card-views {
            font-size: 0.8rem;
        }
        #interview-modules-page .module-card-link {
            min-height: 40px;
            border-radius: 11px;
            font-size: 0.88rem;
        }
    }

    @media (max-width: 380px) {
        #interview-modules-page .modules-hero {
            grid-template-columns: 36px minmax(0, 1fr) 72px;
            padding: 13px;
        }
        #interview-modules-page .modules-hero-copy {
            display: block;
        }
        #interview-modules-page .modules-hero-icon {
            width: 36px;
            height: 36px;
        }
        #interview-modules-page .modules-hero-icon svg {
            width: 21px;
            height: 21px;
        }
        #interview-modules-page .modules-hero-title {
            font-size: 1.12rem;
        }
        #interview-modules-page .modules-hero-subtitle {
            font-size: 0.8rem;
        }
        #interview-modules-page .modules-hero-art {
            width: 72px;
        }
        #interview-modules-page .module-smart-title {
            font-size: 1.08rem;
        }
        #interview-modules-page .module-smart-subtitle {
            font-size: 0.82rem;
        }
        #interview-modules-page .module-section-head {
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 9px;
        }
        #interview-modules-page .module-section-icon {
            width: 38px;
            height: 38px;
        }
        #interview-modules-page .module-topic-select {
            min-height: 40px;
            font-size: 0.86rem;
        }
        #interview-modules-page .module-rec-icon {
            width: 38px;
            height: 38px;
            flex-basis: 38px;
        }
        #interview-modules-page .module-rec-copy strong,
        #interview-modules-page .module-path-copy strong {
            font-size: 0.86rem;
        }
        #interview-modules-page .module-rec-copy span,
        #interview-modules-page .module-path-copy span {
            font-size: 0.78rem;
        }
        #interview-modules-page .module-path-panel .module-path-item {
            padding: 11px;
            gap: 10px;
        }
        #interview-modules-page .module-path-panel .module-rec-icon {
            width: 42px;
            height: 42px;
            flex-basis: 42px;
        }
        #interview-modules-page .module-path-panel .module-path-copy strong {
            font-size: 0.88rem;
        }
        #interview-modules-page .module-card-media {
            height: 104px;
        }
        #interview-modules-page .module-card-badge {
            font-size: 0.7rem;
            padding: 5px 8px;
        }
        #interview-modules-page .module-card-icon {
            width: 38px;
            height: 38px;
            left: 13px;
            bottom: 12px;
        }
        #interview-modules-page .module-card-body {
            padding: 13px;
        }
        #interview-modules-page .module-card-title {
            font-size: 0.92rem;
        }
    }

    /* Compact Modules layout: same setup-inspired design, smaller surfaces. */
    #interview-modules-page .modules-hero {
        grid-template-columns: clamp(46px, 7vw, 68px) minmax(0, 1fr) clamp(150px, 22vw, 240px);
        gap: clamp(12px, 2.2vw, 26px);
        min-height: clamp(160px, 20vw, 220px);
        padding: clamp(16px, 2.7vw, 30px);
        margin-bottom: 12px;
        border-radius: 20px;
    }
    #interview-modules-page .modules-hero::after {
        top: 36px;
        right: 30px;
        width: 82px;
        height: 58px;
        background-size: 15px 15px;
    }
    #interview-modules-page .modules-hero-icon {
        width: clamp(46px, 7vw, 68px);
        height: clamp(46px, 7vw, 68px);
        border-radius: 15px;
    }
    #interview-modules-page .modules-hero-title {
        max-width: 100%;
        white-space: nowrap;
        font-size: 1rem;
        line-height: 1.08;
    }
    #interview-modules-page .modules-hero-subtitle {
        max-width: 500px;
        margin-top: 12px;
        font-size: clamp(0.88rem, 1.35vw, 1.08rem);
        line-height: 1.5;
    }
    #interview-modules-page .modules-hero-art {
        width: min(100%, 230px);
    }
    #interview-modules-page .module-smart-panel {
        padding: clamp(12px, 1.8vw, 16px);
        margin-bottom: 14px;
        border-radius: 15px;
    }
    #interview-modules-page .module-smart-title {
        font-size: clamp(0.98rem, 1.55vw, 1.2rem);
    }
    #interview-modules-page .module-smart-subtitle {
        margin-top: 4px;
        font-size: clamp(0.78rem, 1.15vw, 0.9rem);
        line-height: 1.45;
    }
    #interview-modules-page .module-progress-link {
        min-height: 34px;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 0.82rem;
    }
    #interview-modules-page .module-section-head {
        grid-template-columns: 40px minmax(0, 1fr);
        gap: 11px;
        margin-bottom: 12px;
    }
    #interview-modules-page .module-section-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
    }
    #interview-modules-page .module-section-icon i {
        font-size: 0.98rem;
    }
    #interview-modules-page .module-rec-grid,
    #interview-modules-page .module-path-grid {
        gap: 10px;
        margin-top: 11px;
    }
    #interview-modules-page .module-rec-item,
    #interview-modules-page .module-path-item {
        padding: 10px;
        border-radius: 13px;
        gap: 10px;
    }
    #interview-modules-page .module-rec-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        border-radius: 10px;
        font-size: 0.9rem;
    }
    #interview-modules-page .module-rec-copy strong,
    #interview-modules-page .module-path-copy strong {
        font-size: 0.82rem;
    }
    #interview-modules-page .module-rec-copy span,
    #interview-modules-page .module-path-copy span {
        font-size: 0.74rem;
        line-height: 1.38;
    }
    #interview-modules-page .module-path-panel .module-path-item {
        min-height: 82px;
        padding: 11px;
        gap: 12px;
    }
    #interview-modules-page .module-path-panel .module-rec-icon {
        width: 46px;
        height: 46px;
        flex-basis: 46px;
        border-radius: 12px;
    }
    #interview-modules-page .module-path-panel .module-rec-icon i {
        font-size: 1rem;
    }
    #interview-modules-page .module-path-panel .module-path-copy strong {
        font-size: clamp(0.88rem, 1.35vw, 1rem);
    }
    #interview-modules-page .module-path-panel .module-path-copy span {
        margin-top: 4px;
        font-size: clamp(0.74rem, 1vw, 0.84rem);
    }
    #interview-modules-page .module-path-panel .module-path-progress {
        width: min(100%, 300px);
        height: 6px;
        margin-top: 7px;
    }
    #interview-modules-page .module-topic-select-shell {
        max-width: 320px;
    }
    #interview-modules-page .module-topic-select {
        min-height: 38px;
        border-radius: 11px;
        padding: 8px 38px 8px 12px;
        font-size: 0.82rem;
    }
    #interview-modules-page .db-top-search {
        max-width: 260px !important;
        min-height: 38px;
        padding: 8px 12px !important;
        border-radius: 11px !important;
        font-size: 0.82rem;
    }
    #interview-modules-page .module-card {
        border-radius: 14px;
    }
    #interview-modules-page .module-card-media {
        height: 108px;
    }
    #interview-modules-page .module-card-media::after {
        width: 82px;
        height: 82px;
    }
    #interview-modules-page .module-card-badges {
        top: 10px;
        left: 10px;
        right: 10px;
        gap: 6px;
    }
    #interview-modules-page .module-card-badge {
        min-height: 25px;
        padding: 4px 8px;
        font-size: 0.68rem;
    }
    #interview-modules-page .module-card-icon {
        width: 40px;
        height: 40px;
        left: 16px;
        bottom: 14px;
        border-radius: 12px;
        font-size: 0.95rem;
    }
    #interview-modules-page .module-card-body {
        padding: 13px;
    }
    #interview-modules-page .module-card-title {
        font-size: 0.92rem;
        margin-bottom: 7px;
    }
    #interview-modules-page .module-card-desc {
        font-size: 0.76rem;
        line-height: 1.42;
        margin-bottom: 12px;
    }
    #interview-modules-page .module-card-footer {
        padding-top: 10px;
        gap: 8px;
    }
    #interview-modules-page .module-card-views {
        font-size: 0.74rem;
    }
    #interview-modules-page .module-card-link {
        min-height: 36px;
        border-radius: 10px;
        font-size: 0.82rem;
    }

    @media (min-width: 992px) {
        #interview-modules-page {
            max-width: 1440px;
            margin-inline: auto;
        }
        #interview-modules-page .modules-hero {
            grid-template-columns: 78px minmax(0, 1fr) minmax(230px, 300px);
            gap: 28px;
            min-height: 230px;
            padding: 30px 34px;
            margin-bottom: 20px;
            border-radius: 22px;
        }
        #interview-modules-page .modules-hero-icon {
            width: 78px;
            height: 78px;
            border-radius: 18px;
        }
        #interview-modules-page .modules-hero-title {
            max-width: 760px;
            white-space: normal;
            font-size: clamp(2.25rem, 3vw, 3.1rem);
            line-height: 1.05;
        }
        #interview-modules-page .modules-hero-subtitle {
            max-width: 690px;
            margin-top: 14px;
            font-size: 1rem;
            line-height: 1.55;
        }
        #interview-modules-page .modules-hero-art {
            width: min(100%, 285px);
            justify-self: end;
        }
        #interview-modules-page .sr-page-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 16px;
        }
        #interview-modules-page .db-top-search {
            max-width: 360px !important;
            min-height: 46px;
            padding: 10px 16px !important;
            border-radius: 12px !important;
            font-size: 0.92rem;
        }
        #interview-modules-page #nav-pills-container {
            gap: 10px !important;
            margin-bottom: 22px !important;
            padding-bottom: 0 !important;
        }
        #interview-modules-page .ll-nav-pill {
            min-height: 42px;
            padding: 9px 18px;
            border-radius: 14px;
            font-size: 0.9rem;
        }
        #interview-modules-page .module-smart-panel {
            padding: 22px;
            margin-bottom: 22px;
            border-radius: 18px;
        }
        #interview-modules-page .module-smart-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 26px;
            align-items: stretch;
            margin-bottom: 26px;
        }
        #interview-modules-page .module-smart-row .module-smart-panel {
            margin-bottom: 0;
            min-height: 270px;
        }
        #interview-modules-page .module-smart-title {
            font-size: 1.28rem;
        }
        #interview-modules-page .module-smart-subtitle {
            font-size: 0.94rem;
        }
        #interview-modules-page .module-rec-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 14px;
            margin-top: 16px;
        }
        #interview-modules-page .module-rec-item {
            min-height: 94px;
            align-items: flex-start;
            padding: 15px;
            border-radius: 15px;
        }
        #interview-modules-page .module-path-panel .module-path-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 14px;
        }
        #interview-modules-page .module-path-panel .module-path-item {
            min-height: 108px;
            padding: 16px;
        }
        #interview-modules-page .module-path-panel .module-rec-icon {
            width: 54px;
            height: 54px;
            flex-basis: 54px;
        }
        #interview-modules-page .modules-card-grid {
            --bs-gutter-x: 1.5rem;
            --bs-gutter-y: 1.5rem;
        }
        #interview-modules-page .module-card {
            min-height: 342px;
            border-radius: 16px;
        }
        #interview-modules-page .module-card-media {
            height: 128px;
        }
        #interview-modules-page .module-card-body {
            padding: 18px;
        }
        #interview-modules-page .module-card-title {
            min-height: 2.35em;
            font-size: 1.05rem;
            line-height: 1.18;
            margin-bottom: 9px;
        }
        #interview-modules-page .module-card-desc {
            min-height: 4.35em;
            font-size: 0.86rem;
            line-height: 1.45;
            margin-bottom: 14px;
        }
        #interview-modules-page .module-card-footer {
            grid-template-columns: minmax(0, auto) minmax(150px, 1fr);
            align-items: center;
            gap: 12px;
        }
        #interview-modules-page .module-card-views {
            font-size: 0.8rem;
            white-space: nowrap;
        }
        #interview-modules-page .module-card-link {
            min-height: 40px;
            font-size: 0.86rem;
        }
    }

    @media (max-width: 767px) {
        #interview-modules-page .modules-hero {
            grid-template-columns: minmax(0, 1fr);
            gap: 9px;
            padding: 11px;
            border-radius: 14px;
        }
        #interview-modules-page .modules-hero-icon {
            display: none;
        }
        #interview-modules-page .modules-hero-icon svg {
            width: 20px;
            height: 20px;
        }
        #interview-modules-page .modules-hero-title {
            font-size: 1rem;
        }
        #interview-modules-page .modules-hero-subtitle {
            margin-top: 3px;
            font-size: 0.72rem;
            line-height: 1.35;
        }
        #interview-modules-page .modules-hero-art {
            display: none;
        }
        #interview-modules-page .module-smart-panel {
            padding: 11px;
            border-radius: 13px;
        }
        #interview-modules-page .module-smart-title {
            font-size: 1rem;
        }
        #interview-modules-page .module-smart-subtitle {
            font-size: 0.76rem;
        }
        #interview-modules-page .module-progress-link {
            min-height: 34px;
            margin-top: 9px;
        }
        #interview-modules-page .module-card-media {
            height: 96px;
        }
    }

    @media (max-width: 767px) {
        #interview-modules-page .module-smart-panel {
            padding: 9px;
            margin-bottom: 10px;
            border-radius: 10px;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.06);
        }
        #interview-modules-page .module-smart-head {
            display: block;
        }
        #interview-modules-page .module-smart-title {
            font-size: 0.92rem;
            line-height: 1.15;
        }
        #interview-modules-page .module-smart-title i {
            margin-right: 5px !important;
            font-size: 0.84rem;
        }
        #interview-modules-page .module-smart-subtitle {
            margin-top: 4px;
            font-size: 0.72rem;
            line-height: 1.35;
        }
        #interview-modules-page .module-progress-link {
            min-height: 32px;
            margin-top: 8px;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.76rem;
        }
        #interview-modules-page .module-rec-grid,
        #interview-modules-page .module-path-grid,
        #interview-modules-page .module-path-panel .module-path-grid {
            gap: 8px;
            margin-top: 9px;
        }
        #interview-modules-page .module-rec-item,
        #interview-modules-page .module-path-item,
        #interview-modules-page .module-path-panel .module-path-item {
            min-height: 0;
            padding: 9px;
            gap: 9px;
            border-radius: 10px;
            align-items: center;
        }
        #interview-modules-page .module-rec-icon,
        #interview-modules-page .module-path-panel .module-rec-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 9px;
            font-size: 0.78rem;
        }
        #interview-modules-page .module-path-panel .module-rec-icon i {
            font-size: 0.82rem;
        }
        #interview-modules-page .module-rec-copy strong,
        #interview-modules-page .module-path-copy strong,
        #interview-modules-page .module-path-panel .module-path-copy strong {
            font-size: 0.78rem;
            line-height: 1.18;
        }
        #interview-modules-page .module-rec-copy span,
        #interview-modules-page .module-path-copy span,
        #interview-modules-page .module-path-panel .module-path-copy span {
            margin-top: 3px;
            font-size: 0.68rem;
            line-height: 1.28;
        }
        #interview-modules-page .module-section-head {
            grid-template-columns: 32px minmax(0, 1fr);
            gap: 9px;
            margin-bottom: 8px;
        }
        #interview-modules-page .module-section-icon {
            width: 32px;
            height: 32px;
            border-radius: 9px;
        }
        #interview-modules-page .module-section-icon i {
            font-size: 0.76rem;
        }
        #interview-modules-page .module-path-panel .module-path-progress {
            width: min(100%, 124px);
            height: 4px;
            margin-top: 5px;
        }
        #interview-modules-page .module-path-progress {
            height: 4px;
        }
    }

    @media (max-width: 380px) {
        #interview-modules-page .modules-hero {
            grid-template-columns: minmax(0, 1fr);
            padding: 10px;
        }
        #interview-modules-page .modules-hero-icon {
            display: none;
        }
        #interview-modules-page .modules-hero-title {
            font-size: 1rem;
        }
        #interview-modules-page .modules-hero-subtitle {
            font-size: 0.66rem;
        }
        #interview-modules-page .modules-hero-art {
            display: none;
        }
        #interview-modules-page .module-card-media {
            height: 88px;
        }
        #interview-modules-page .module-smart-panel {
            padding: 8px;
        }
        #interview-modules-page .module-smart-title {
            font-size: 0.86rem;
        }
        #interview-modules-page .module-smart-subtitle {
            font-size: 0.66rem;
        }
        #interview-modules-page .module-rec-item,
        #interview-modules-page .module-path-item,
        #interview-modules-page .module-path-panel .module-path-item {
            padding: 8px;
        }
    }

    /* Match the responsive banner rhythm used by the Progress page. */
    #interview-modules-page {
        --modules-hero-title-color: #1d4ed8;
        --modules-hero-text-color: #334155;
        --modules-hero-control-bg: #ffffff;
        --modules-hero-control-text: #0f172a;
        --modules-hero-control-border: rgba(147, 197, 253, 0.52);
        --modules-hero-icon-bg: rgba(239, 246, 255, 0.9);
        --modules-hero-icon-border: rgba(147, 197, 253, 0.42);
    }
    #interview-modules-page .modules-hero {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr);
        align-items: center;
        gap: 10px;
        min-height: 104px;
        padding: 14px 116px 14px 14px;
        margin-bottom: 12px;
        border-radius: 14px;
    }
    html[data-theme="dark"] #interview-modules-page,
    :root:not(.lm) #interview-modules-page {
        --modules-hero-title-color: #93c5fd;
        --modules-hero-text-color: #e2e8f0;
        --modules-hero-control-bg: #111827;
        --modules-hero-control-text: #f8fafc;
        --modules-hero-control-border: rgba(147, 197, 253, 0.34);
        --modules-hero-icon-bg: rgba(59, 130, 246, 0.2);
        --modules-hero-icon-border: rgba(147, 197, 253, 0.32);
    }
    #interview-modules-page .modules-hero-icon {
        display: inline-flex;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        color: var(--modules-hero-title-color);
        background: var(--modules-hero-icon-bg);
        border-color: var(--modules-hero-icon-border);
    }
    #interview-modules-page .modules-hero-copy {
        min-width: 0;
    }
    #interview-modules-page .modules-hero-title {
        display: block;
        font-size: 1.1rem !important;
        line-height: 1.15;
        margin: 0 0 4px;
        max-width: 100%;
        overflow: visible;
        white-space: nowrap;
        -webkit-text-fill-color: var(--modules-hero-title-color) !important;
        color: var(--modules-hero-title-color) !important;
    }
    #interview-modules-page .modules-hero-subtitle {
        max-width: 100%;
        margin-top: 0;
        font-size: 0.74rem;
        line-height: 1.4;
        color: var(--modules-hero-text-color) !important;
    }
    #interview-modules-page .modules-hero-art {
        display: block;
        position: absolute;
        right: -10px;
        bottom: -1px;
        width: 112px;
        max-width: none;
    }
    #interview-modules-page .module-topic-select-wrap {
        display: block;
        margin-bottom: 14px;
    }
    #interview-modules-page .module-topic-select-shell {
        position: relative;
        width: min(100%, 260px);
        max-width: 100%;
    }
    #interview-modules-page .module-topic-select-shell::after {
        content: "";
        position: absolute;
        top: 50%;
        right: 14px;
        width: 8px;
        height: 8px;
        border-right: 2px solid var(--modules-hero-control-text, #0f172a);
        border-bottom: 2px solid var(--modules-hero-control-text, #0f172a);
        transform: translateY(-65%) rotate(45deg);
        pointer-events: none;
    }
    #interview-modules-page .module-topic-select {
        width: 100%;
        min-height: 36px;
        padding: 8px 34px 8px 12px !important;
        border: 1px solid var(--modules-hero-control-border) !important;
        border-radius: 8px;
        appearance: none;
        -webkit-appearance: none;
        background-color: var(--modules-hero-control-bg) !important;
        background-image: none !important;
        color: var(--modules-hero-control-text) !important;
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1.2;
        box-shadow: 0 6px 16px rgba(37, 99, 235, 0.07);
    }

    @media (max-width: 767px) {
        #interview-modules-page .module-topic-select-wrap {
            width: 100%;
        }
        #interview-modules-page .module-topic-select-shell {
            width: 100%;
        }
        #interview-modules-page .module-topic-select {
            min-height: 42px;
            border-radius: 10px;
            background-color: var(--modules-hero-control-bg) !important;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.1);
        }
    }

    @media (max-width: 390px) {
        #interview-modules-page .modules-hero {
            grid-template-columns: 34px minmax(0, 1fr);
            gap: 8px;
            padding-right: 86px;
        }
        #interview-modules-page .modules-hero-icon {
            display: inline-flex;
            width: 34px;
            height: 34px;
            border-radius: 10px;
        }
        #interview-modules-page .modules-hero-title {
            font-size: 0.86rem !important;
        }
        #interview-modules-page .modules-hero-subtitle {
            font-size: 0.66rem;
        }
        #interview-modules-page .modules-hero-art {
            display: block;
            width: 78px;
        }
        #interview-modules-page .module-topic-select-shell {
            width: 100%;
        }
    }

    /* Keep the modules page readable in both day and night themes. */
    #interview-modules-page {
        --modules-page-card-bg: #ffffff;
        --modules-page-card-text: #0f172a;
        --modules-page-card-muted: #475569;
        --modules-page-card-border: rgba(147, 197, 253, 0.52);
        --modules-page-soft-bg: #eff6ff;
        --modules-page-hero-bg:
            radial-gradient(circle at 86% 18%, rgba(219, 234, 254, 0.78), transparent 35%),
            linear-gradient(142deg, #ffffff 0%, #f8fbff 52%, #dbeafe 100%);
    }
    html[data-theme="dark"] #interview-modules-page,
    :root:not(.lm) #interview-modules-page {
        --modules-page-card-bg: #111827;
        --modules-page-card-text: #f8fafc;
        --modules-page-card-muted: #cbd5e1;
        --modules-page-card-border: rgba(147, 197, 253, 0.28);
        --modules-page-soft-bg: #1e293b;
        --modules-page-hero-bg:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%);
    }
    #interview-modules-page .modules-hero,
    body:not(.lm) #interview-modules-page .modules-hero,
    .lm #interview-modules-page .modules-hero {
        background: var(--modules-page-hero-bg) !important;
        border-color: var(--modules-page-card-border) !important;
    }
    #interview-modules-page .module-smart-panel,
    #interview-modules-page .module-rec-item,
    #interview-modules-page .module-path-item,
    #interview-modules-page .module-card,
    body:not(.lm) #interview-modules-page .module-card,
    .lm #interview-modules-page .module-card {
        background: var(--modules-page-card-bg) !important;
        border-color: var(--modules-page-card-border) !important;
        color: var(--modules-page-card-text) !important;
    }
    #interview-modules-page :is(
        .module-smart-title,
        .module-card-title,
        .module-rec-copy strong,
        .module-path-copy strong
    ) {
        color: var(--modules-page-card-text) !important;
    }
    #interview-modules-page :is(
        .module-smart-subtitle,
        .module-card-desc,
        .module-rec-copy span,
        .module-path-copy span,
        .module-card-views
    ) {
        color: var(--modules-page-card-muted) !important;
    }
    #interview-modules-page .module-card-media {
        background:
            radial-gradient(circle at 82% 24%, rgba(244, 114, 182, 0.18), transparent 34%),
            radial-gradient(circle at 15% 5%, rgba(59, 130, 246, 0.18), transparent 32%),
            var(--modules-page-soft-bg) !important;
    }
    #interview-modules-page .module-card-icon,
    #interview-modules-page .module-rec-icon {
        background: var(--modules-page-soft-bg) !important;
    }
    #interview-modules-page .module-section-icon,
    #interview-modules-page .module-rec-icon,
    #interview-modules-page .module-path-panel .module-rec-icon {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: var(--modules-hero-title-color) !important;
        background: var(--modules-page-soft-bg) !important;
        border: 1px solid var(--modules-page-card-border) !important;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.08);
    }
    #interview-modules-page .module-section-icon i,
    #interview-modules-page .module-rec-icon i,
    #interview-modules-page .module-path-panel .module-rec-icon i {
        color: inherit !important;
        line-height: 1 !important;
    }

    @media (max-width: 767px) {
        #interview-modules-page .module-section-icon,
        #interview-modules-page .module-rec-icon,
        #interview-modules-page .module-path-panel .module-rec-icon {
            width: 38px !important;
            height: 38px !important;
            flex: 0 0 38px !important;
            border-radius: 10px !important;
            font-size: 0.9rem !important;
        }
    }

    /* SaaSPro mobile polish for the Modules screen. */
    @media (max-width: 767px) {
        body #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0, rgba(20, 184, 166, 0.035) 260px, transparent 520px),
                var(--bg) !important;
        }

        body #mob-content > .db-content {
            padding: 12px 12px 18px !important;
        }

        html body #interview-modules-page {
            --modules-pro-card: rgba(255, 255, 255, 0.98);
            --modules-pro-field: rgba(255, 255, 255, 0.96);
            --modules-pro-soft: #f8fafc;
            --modules-pro-border: rgba(15, 23, 42, 0.1);
            --modules-pro-title: #0f172a;
            --modules-pro-muted: #64748b;
            --modules-pro-accent: #2563eb;
            --modules-pro-badge-bg: rgba(255, 255, 255, 0.92);
            --modules-pro-badge-text: #1e293b;
            --modules-pro-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
            max-width: 520px;
            margin: 0 auto !important;
            padding: 0 0 16px !important;
            color: var(--modules-pro-title) !important;
        }

        html[data-theme="dark"] body #interview-modules-page,
        :root:not(.lm) body #interview-modules-page,
        body.dm #interview-modules-page,
        .dm #interview-modules-page {
            --modules-pro-card: rgba(15, 23, 42, 0.96);
            --modules-pro-field: rgba(15, 23, 42, 0.95);
            --modules-pro-soft: rgba(30, 41, 59, 0.96);
            --modules-pro-border: rgba(148, 163, 184, 0.24);
            --modules-pro-title: #f8fafc;
            --modules-pro-muted: #cbd5e1;
            --modules-pro-accent: #93c5fd;
            --modules-pro-badge-bg: rgba(15, 23, 42, 0.84);
            --modules-pro-badge-text: #f8fafc;
            --modules-pro-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
        }

        html body #interview-modules-page .modules-hero.modules-hero {
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
        }

        html body #interview-modules-page .modules-hero.modules-hero::before,
        html body #interview-modules-page .modules-hero.modules-hero::after {
            content: none !important;
            display: none !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-icon {
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
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-icon svg {
            width: 15px !important;
            height: 15px !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-copy {
            display: block !important;
            min-width: 0 !important;
            overflow: hidden !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-title {
            display: block !important;
            margin: 0 0 3px !important;
            color: #f8fbff !important;
            -webkit-text-fill-color: #f8fbff !important;
            font-size: 0.72rem !important;
            font-weight: 900 !important;
            line-height: 1.15 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
            display: -webkit-box !important;
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

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-art {
            display: block !important;
            position: absolute !important;
            right: -5px !important;
            bottom: -2px !important;
            width: 72px !important;
            max-width: none !important;
            pointer-events: none !important;
            filter: drop-shadow(0 10px 18px rgba(15, 23, 42, 0.16)) !important;
        }

        #interview-modules-page .module-topic-select-wrap {
            display: block !important;
            width: 100% !important;
            margin: 0 0 10px !important;
        }

        #interview-modules-page .module-topic-select-shell {
            position: relative !important;
            width: 100% !important;
            max-width: none !important;
        }

        #interview-modules-page .module-topic-select-shell::after {
            content: "" !important;
            position: absolute !important;
            top: 50% !important;
            right: 14px !important;
            width: 7px !important;
            height: 7px !important;
            border-right: 2px solid var(--modules-pro-muted) !important;
            border-bottom: 2px solid var(--modules-pro-muted) !important;
            transform: translateY(-65%) rotate(45deg) !important;
            pointer-events: none !important;
        }

        #interview-modules-page .module-topic-select {
            width: 100% !important;
            min-height: 40px !important;
            padding: 9px 36px 9px 12px !important;
            border: 1px solid var(--modules-pro-border) !important;
            border-radius: 8px !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            background: var(--modules-pro-field) !important;
            color: var(--modules-pro-title) !important;
            font-size: 0.78rem !important;
            font-weight: 800 !important;
            line-height: 1.2 !important;
            box-shadow: var(--modules-pro-shadow) !important;
        }

        #interview-modules-page .module-smart-row {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
            margin: 0 0 12px !important;
        }

        #interview-modules-page .module-smart-panel {
            padding: 10px !important;
            margin: 0 0 10px !important;
            border: 1px solid var(--modules-pro-border) !important;
            border-radius: 8px !important;
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.045), rgba(20, 184, 166, 0.025)),
                var(--modules-pro-card) !important;
            color: var(--modules-pro-title) !important;
            box-shadow: var(--modules-pro-shadow) !important;
        }

        #interview-modules-page .module-smart-row .module-smart-panel {
            margin-bottom: 0 !important;
        }

        #interview-modules-page .module-smart-head {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: start !important;
            gap: 8px !important;
        }

        #interview-modules-page .module-section-head {
            display: grid !important;
            grid-template-columns: 34px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
            margin: 0 0 8px !important;
        }

        #interview-modules-page .module-smart-title {
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            margin: 0 !important;
            color: var(--modules-pro-title) !important;
            -webkit-text-fill-color: var(--modules-pro-title) !important;
            font-size: 0.86rem !important;
            font-weight: 900 !important;
            line-height: 1.16 !important;
            letter-spacing: 0 !important;
            overflow-wrap: anywhere !important;
        }

        #interview-modules-page .module-smart-title i {
            margin-right: 0 !important;
            -webkit-text-fill-color: currentColor !important;
        }

        #interview-modules-page .module-smart-subtitle {
            display: -webkit-box !important;
            margin: 4px 0 0 !important;
            color: var(--modules-pro-muted) !important;
            -webkit-text-fill-color: var(--modules-pro-muted) !important;
            font-size: 0.68rem !important;
            font-weight: 650 !important;
            line-height: 1.34 !important;
            overflow: hidden !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
        }

        #interview-modules-page .module-progress-link {
            width: auto !important;
            min-height: 30px !important;
            margin: 0 !important;
            padding: 0 9px !important;
            border: 1px solid var(--modules-pro-border) !important;
            border-radius: 8px !important;
            background: var(--modules-pro-field) !important;
            color: var(--modules-pro-accent) !important;
            -webkit-text-fill-color: var(--modules-pro-accent) !important;
            font-size: 0.68rem !important;
            font-weight: 900 !important;
            white-space: nowrap !important;
            box-shadow: none !important;
        }

        #interview-modules-page .module-rec-grid,
        #interview-modules-page .module-path-grid,
        #interview-modules-page .module-path-panel .module-path-grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 8px !important;
            margin-top: 8px !important;
        }

        #interview-modules-page .module-rec-item,
        #interview-modules-page .module-path-item,
        #interview-modules-page .module-path-panel .module-path-item {
            display: grid !important;
            grid-template-columns: 34px minmax(0, 1fr) !important;
            align-items: center !important;
            min-height: 0 !important;
            gap: 8px !important;
            padding: 8px !important;
            border: 1px solid var(--modules-pro-border) !important;
            border-radius: 8px !important;
            background: var(--modules-pro-field) !important;
            color: var(--modules-pro-title) !important;
            box-shadow: none !important;
        }

        #interview-modules-page .module-section-icon,
        #interview-modules-page .module-rec-icon,
        #interview-modules-page .module-path-panel .module-rec-icon {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 34px !important;
            height: 34px !important;
            flex: 0 0 34px !important;
            border: 1px solid var(--modules-pro-border) !important;
            border-radius: 8px !important;
            background: var(--modules-pro-soft) !important;
            color: var(--rec-color, var(--modules-pro-accent)) !important;
            -webkit-text-fill-color: var(--rec-color, var(--modules-pro-accent)) !important;
            font-size: 0.8rem !important;
            box-shadow: none !important;
        }

        #interview-modules-page .module-section-icon i,
        #interview-modules-page .module-rec-icon i,
        #interview-modules-page .module-path-panel .module-rec-icon i {
            color: inherit !important;
            -webkit-text-fill-color: currentColor !important;
            font-size: inherit !important;
            line-height: 1 !important;
        }

        #interview-modules-page .module-rec-copy,
        #interview-modules-page .module-path-copy {
            min-width: 0 !important;
        }

        #interview-modules-page .module-rec-copy strong,
        #interview-modules-page .module-path-copy strong,
        #interview-modules-page .module-path-panel .module-path-copy strong {
            display: block !important;
            color: var(--modules-pro-title) !important;
            -webkit-text-fill-color: var(--modules-pro-title) !important;
            font-size: 0.78rem !important;
            font-weight: 900 !important;
            line-height: 1.2 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }

        #interview-modules-page .module-rec-copy span,
        #interview-modules-page .module-path-copy span,
        #interview-modules-page .module-path-panel .module-path-copy span {
            display: -webkit-box !important;
            margin-top: 3px !important;
            color: var(--modules-pro-muted) !important;
            -webkit-text-fill-color: var(--modules-pro-muted) !important;
            font-size: 0.66rem !important;
            font-weight: 650 !important;
            line-height: 1.28 !important;
            overflow: hidden !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
        }

        #interview-modules-page .module-path-progress,
        #interview-modules-page .module-path-panel .module-path-progress {
            width: 100% !important;
            height: 5px !important;
            margin-top: 6px !important;
            border-radius: 999px !important;
            background: rgba(148, 163, 184, 0.24) !important;
            overflow: hidden !important;
        }

        #interview-modules-page .module-path-progress span {
            display: block !important;
            height: 100% !important;
            width: var(--path-progress, 0%) !important;
            border-radius: inherit !important;
            background: linear-gradient(90deg, #22c55e, #38bdf8, #2563eb) !important;
        }

        #interview-modules-page .modules-card-grid {
            --bs-gutter-x: 0 !important;
            --bs-gutter-y: 0 !important;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 10px !important;
            margin: 0 !important;
        }

        #interview-modules-page .modules-card-grid > [class*="col-"] {
            width: 100% !important;
            max-width: none !important;
            padding: 0 !important;
        }

        #interview-modules-page .module-card {
            min-height: 0 !important;
            border: 1px solid var(--modules-pro-border) !important;
            border-radius: 8px !important;
            background: var(--modules-pro-card) !important;
            color: var(--modules-pro-title) !important;
            box-shadow: var(--modules-pro-shadow) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            overflow: hidden !important;
        }

        #interview-modules-page .module-card:hover {
            transform: none !important;
            border-color: rgba(37, 99, 235, 0.24) !important;
        }

        #interview-modules-page .module-card-media {
            position: relative !important;
            height: 84px !important;
            overflow: hidden !important;
            background:
                linear-gradient(135deg, rgba(37, 99, 235, 0.16), rgba(20, 184, 166, 0.14)),
                repeating-linear-gradient(90deg, rgba(37, 99, 235, 0.12) 0 1px, transparent 1px 18px),
                var(--modules-pro-soft) !important;
        }

        #interview-modules-page .module-card-media::before,
        #interview-modules-page .module-card-media::after {
            content: none !important;
            display: none !important;
        }

        #interview-modules-page .module-card-badges {
            top: 8px !important;
            left: 8px !important;
            right: 8px !important;
            gap: 5px !important;
            align-items: flex-start !important;
        }

        #interview-modules-page .module-card-badge {
            min-width: 0 !important;
            min-height: 22px !important;
            max-width: 100% !important;
            padding: 4px 7px !important;
            border: 1px solid rgba(148, 163, 184, 0.22) !important;
            border-radius: 999px !important;
            background: var(--modules-pro-badge-bg) !important;
            color: var(--modules-pro-badge-text) !important;
            -webkit-text-fill-color: var(--modules-pro-badge-text) !important;
            font-size: 0.62rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            box-shadow: none !important;
        }

        #interview-modules-page .module-card-badge i {
            color: var(--modules-pro-accent) !important;
            -webkit-text-fill-color: var(--modules-pro-accent) !important;
        }

        #interview-modules-page .module-card-badge.difficulty-beginner {
            background: #ecfdf5 !important;
            color: #047857 !important;
            -webkit-text-fill-color: #047857 !important;
        }

        #interview-modules-page .module-card-badge.difficulty-intermediate {
            background: #fffbeb !important;
            color: #92400e !important;
            -webkit-text-fill-color: #92400e !important;
        }

        #interview-modules-page .module-card-badge.difficulty-advanced {
            background: #fef2f2 !important;
            color: #b91c1c !important;
            -webkit-text-fill-color: #b91c1c !important;
        }

        html[data-theme="dark"] body #interview-modules-page .module-card-badge.difficulty-beginner,
        :root:not(.lm) body #interview-modules-page .module-card-badge.difficulty-beginner,
        body.dm #interview-modules-page .module-card-badge.difficulty-beginner,
        .dm #interview-modules-page .module-card-badge.difficulty-beginner {
            background: rgba(6, 78, 59, 0.72) !important;
            color: #bbf7d0 !important;
            -webkit-text-fill-color: #bbf7d0 !important;
        }

        html[data-theme="dark"] body #interview-modules-page .module-card-badge.difficulty-intermediate,
        :root:not(.lm) body #interview-modules-page .module-card-badge.difficulty-intermediate,
        body.dm #interview-modules-page .module-card-badge.difficulty-intermediate,
        .dm #interview-modules-page .module-card-badge.difficulty-intermediate {
            background: rgba(120, 53, 15, 0.74) !important;
            color: #fde68a !important;
            -webkit-text-fill-color: #fde68a !important;
        }

        html[data-theme="dark"] body #interview-modules-page .module-card-badge.difficulty-advanced,
        :root:not(.lm) body #interview-modules-page .module-card-badge.difficulty-advanced,
        body.dm #interview-modules-page .module-card-badge.difficulty-advanced,
        .dm #interview-modules-page .module-card-badge.difficulty-advanced {
            background: rgba(127, 29, 29, 0.76) !important;
            color: #fecaca !important;
            -webkit-text-fill-color: #fecaca !important;
        }

        #interview-modules-page .module-card-icon {
            left: 10px !important;
            bottom: 8px !important;
            width: 36px !important;
            height: 36px !important;
            border: 1px solid var(--modules-pro-border) !important;
            border-radius: 8px !important;
            background: var(--modules-pro-card) !important;
            color: var(--modules-pro-accent) !important;
            -webkit-text-fill-color: var(--modules-pro-accent) !important;
            font-size: 0.92rem !important;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.1) !important;
        }

        #interview-modules-page .module-card-body {
            padding: 10px !important;
        }

        #interview-modules-page .module-card-title {
            display: -webkit-box !important;
            margin: 0 0 6px !important;
            color: var(--modules-pro-title) !important;
            -webkit-text-fill-color: var(--modules-pro-title) !important;
            font-size: 0.86rem !important;
            font-weight: 900 !important;
            line-height: 1.22 !important;
            overflow: hidden !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
        }

        #interview-modules-page .module-card-desc {
            display: -webkit-box !important;
            margin: 0 0 10px !important;
            color: var(--modules-pro-muted) !important;
            -webkit-text-fill-color: var(--modules-pro-muted) !important;
            font-size: 0.7rem !important;
            font-weight: 650 !important;
            line-height: 1.38 !important;
            overflow: hidden !important;
            -webkit-box-orient: vertical !important;
            -webkit-line-clamp: 2 !important;
        }

        #interview-modules-page .module-card-footer {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: center !important;
            gap: 8px !important;
            margin-top: auto !important;
            padding-top: 9px !important;
            border-top: 1px solid var(--modules-pro-border) !important;
        }

        #interview-modules-page .module-card-views {
            min-width: 0 !important;
            color: var(--modules-pro-muted) !important;
            -webkit-text-fill-color: var(--modules-pro-muted) !important;
            font-size: 0.68rem !important;
            font-weight: 800 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        #interview-modules-page .module-card-views i {
            color: var(--modules-pro-accent) !important;
            -webkit-text-fill-color: var(--modules-pro-accent) !important;
        }

        #interview-modules-page .module-card-link {
            width: auto !important;
            min-width: 108px !important;
            min-height: 34px !important;
            padding: 0 10px !important;
            border-radius: 8px !important;
            background: linear-gradient(135deg, #2563eb, #0891b2) !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
            font-size: 0.74rem !important;
            font-weight: 900 !important;
            white-space: nowrap !important;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.16) !important;
        }

        #interview-modules-page .module-card-link i {
            color: inherit !important;
            -webkit-text-fill-color: currentColor !important;
        }

        #interview-modules-page .modules-card-grid .text-center {
            padding: 24px 12px !important;
            border: 1px solid var(--modules-pro-border) !important;
            border-radius: 8px !important;
            background: var(--modules-pro-card) !important;
            box-shadow: var(--modules-pro-shadow) !important;
        }

        #interview-modules-page .modules-card-grid .text-center i,
        #interview-modules-page .modules-card-grid .text-center h5 {
            color: var(--modules-pro-muted) !important;
            -webkit-text-fill-color: var(--modules-pro-muted) !important;
        }

        #interview-modules-page .pagination {
            display: flex !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
            gap: 6px !important;
            margin: 12px 0 0 !important;
        }

        #interview-modules-page .page-link {
            min-width: 34px !important;
            min-height: 34px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border: 1px solid var(--modules-pro-border) !important;
            border-radius: 8px !important;
            background: var(--modules-pro-field) !important;
            color: var(--modules-pro-title) !important;
            -webkit-text-fill-color: var(--modules-pro-title) !important;
            font-size: 0.74rem !important;
            font-weight: 800 !important;
            box-shadow: none !important;
        }

        #interview-modules-page .page-item.active .page-link {
            border-color: #2563eb !important;
            background: #2563eb !important;
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
        }

        #interview-modules-page .page-item.disabled .page-link {
            opacity: 0.55 !important;
        }
    }

    @media (max-width: 390px) {
        html body #interview-modules-page .modules-hero.modules-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-title {
            font-size: 0.68rem !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
            font-size: 0.46rem !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-art {
            width: 66px !important;
        }
    }

    @media (max-width: 360px) {
        html body #interview-modules-page .modules-hero.modules-hero {
            padding-right: 62px !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-title {
            font-size: 0.64rem !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
            font-size: 0.44rem !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-art {
            right: -8px !important;
            width: 62px !important;
        }

        #interview-modules-page .module-card-footer {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        #interview-modules-page .module-card-link {
            width: 100% !important;
        }
    }

    @media (min-width: 992px) {
        html body #interview-modules-page {
            --modules-desktop-radius: 12px;
            --modules-desktop-gap: 12px;
            --modules-desktop-border: rgba(148, 163, 184, 0.2);
            --modules-desktop-card-shadow: 0 10px 28px rgba(2, 6, 23, 0.12);
            width: 100% !important;
            max-width: 1480px !important;
            margin: 0 auto !important;
            padding: 0 0 24px !important;
        }

        html.lm body #interview-modules-page {
            --modules-desktop-border: rgba(15, 23, 42, 0.12);
            --modules-desktop-card-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
        }

        html body #interview-modules-page .modules-hero.modules-hero {
            position: relative !important;
            display: grid !important;
            grid-template-columns: 44px minmax(0, 1fr) !important;
            align-items: center !important;
            min-height: 98px !important;
            max-height: none !important;
            height: auto !important;
            gap: 10px !important;
            margin: 0 0 14px !important;
            padding: 14px clamp(126px, 14vw, 148px) 14px 16px !important;
            border: 1px solid rgba(96, 165, 250, 0.26) !important;
            border-radius: 16px !important;
            background:
                radial-gradient(circle at 92% 35%, rgba(96, 165, 250, 0.2), transparent 25%),
                linear-gradient(110deg, rgba(59, 130, 246, 0.12), rgba(6, 182, 212, 0.045)),
                var(--sf) !important;
            overflow: hidden !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08) !important;
        }

        html body #interview-modules-page .modules-hero-copy {
            min-width: 0 !important;
            max-width: 680px !important;
        }

        html body #interview-modules-page .modules-hero-icon {
            width: 44px !important;
            height: 44px !important;
            border-radius: 12px !important;
            font-size: 1.05rem !important;
        }

        html body #interview-modules-page .modules-hero-icon svg {
            width: 22px !important;
            height: 22px !important;
        }

        html body #interview-modules-page .modules-hero-title {
            margin: 0 0 5px !important;
            max-width: 100% !important;
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a !important;
            font-size: 1.45rem !important;
            line-height: 1.15 !important;
            font-weight: 800 !important;
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
            text-transform: uppercase !important;
        }

        html body #interview-modules-page .modules-hero-subtitle {
            display: block !important;
            max-width: 680px !important;
            margin: 0 !important;
            font-size: 0.88rem !important;
            line-height: 1.45 !important;
            font-weight: 600 !important;
            overflow: visible !important;
        }

        html body #interview-modules-page .modules-hero-art {
            display: block !important;
            position: absolute !important;
            right: 8px !important;
            bottom: -2px !important;
            width: clamp(122px, 13vw, 142px) !important;
            max-width: none !important;
            opacity: 0.96 !important;
        }

        #interview-modules-page .module-topic-select-wrap {
            display: flex !important;
            justify-content: flex-end !important;
            align-items: center !important;
            margin: 0 0 12px !important;
        }

        #interview-modules-page .module-topic-select-shell {
            width: clamp(150px, 14vw, 210px) !important;
        }

        #interview-modules-page .module-topic-select {
            min-height: 32px !important;
            padding: 6px 30px 6px 10px !important;
            border-radius: 9px !important;
            font-size: 0.72rem !important;
            font-weight: 850 !important;
        }

        #interview-modules-page .module-smart-row {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) clamp(330px, 34%, 470px) !important;
            gap: var(--modules-desktop-gap) !important;
            align-items: stretch !important;
            margin: 0 0 var(--modules-desktop-gap) !important;
        }

        #interview-modules-page .module-smart-row .module-smart-panel {
            min-height: 0 !important;
            margin: 0 !important;
        }

        #interview-modules-page .module-smart-panel {
            padding: 14px !important;
            border-radius: var(--modules-desktop-radius) !important;
            border-color: var(--modules-desktop-border) !important;
            box-shadow: var(--modules-desktop-card-shadow) !important;
        }

        #interview-modules-page .module-smart-head,
        #interview-modules-page .module-section-head {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
            gap: 10px !important;
            margin: 0 0 10px !important;
        }

        #interview-modules-page .module-smart-title {
            margin: 0 0 4px !important;
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a !important;
            font-size: 0.94rem !important;
            line-height: 1.18 !important;
            font-weight: 900 !important;
        }

        #interview-modules-page .module-smart-subtitle {
            margin: 0 !important;
            font-size: 0.7rem !important;
            line-height: 1.34 !important;
        }

        #interview-modules-page .module-progress-link {
            min-height: 28px !important;
            padding: 6px 9px !important;
            border-radius: 8px !important;
            font-size: 0.68rem !important;
            white-space: nowrap !important;
        }

        #interview-modules-page .module-rec-grid,
        #interview-modules-page .module-path-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            margin: 0 !important;
        }

        #interview-modules-page .module-path-panel .module-path-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        #interview-modules-page .module-rec-item,
        #interview-modules-page .module-path-item {
            min-height: 72px !important;
            padding: 10px !important;
            border-radius: 10px !important;
            gap: 8px !important;
            box-shadow: none !important;
        }

        #interview-modules-page .module-rec-icon,
        #interview-modules-page .module-section-icon,
        #interview-modules-page .module-path-panel .module-rec-icon {
            width: 32px !important;
            height: 32px !important;
            flex: 0 0 32px !important;
            border-radius: 9px !important;
            font-size: 0.78rem !important;
        }

        #interview-modules-page .module-rec-copy strong,
        #interview-modules-page .module-path-copy strong {
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a !important;
            font-size: 0.74rem !important;
            line-height: 1.22 !important;
        }

        #interview-modules-page .module-rec-copy span,
        #interview-modules-page .module-path-copy span {
            font-size: 0.66rem !important;
            line-height: 1.3 !important;
        }

        #interview-modules-page .modules-card-grid {
            --bs-gutter-x: var(--modules-desktop-gap) !important;
            --bs-gutter-y: var(--modules-desktop-gap) !important;
            margin-bottom: var(--modules-desktop-gap) !important;
        }

        #interview-modules-page .modules-card-grid > [class*="col-"] {
            flex: 0 0 auto !important;
            width: 25% !important;
        }

        #interview-modules-page .module-card {
            min-height: 286px !important;
            border-radius: var(--modules-desktop-radius) !important;
            border-color: var(--modules-desktop-border) !important;
            box-shadow: var(--modules-desktop-card-shadow) !important;
        }

        #interview-modules-page .module-card:hover {
            transform: translateY(-1px) !important;
        }

        #interview-modules-page .module-card-media {
            height: 104px !important;
        }

        #interview-modules-page .module-card-badges {
            top: 9px !important;
            left: 9px !important;
            right: 9px !important;
            gap: 5px !important;
        }

        #interview-modules-page .module-card-badge {
            min-height: 24px !important;
            padding: 5px 8px !important;
            font-size: 0.6rem !important;
        }

        #interview-modules-page .module-card-icon {
            left: 16px !important;
            bottom: 14px !important;
            width: 38px !important;
            height: 38px !important;
            border-radius: 11px !important;
            font-size: 0.94rem !important;
        }

        #interview-modules-page .module-card-body {
            padding: 12px !important;
        }

        #interview-modules-page .module-card-title {
            min-height: 2.25em !important;
            margin: 0 0 7px !important;
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a !important;
            font-size: 0.86rem !important;
            line-height: 1.18 !important;
        }

        #interview-modules-page .module-card-desc {
            min-height: 4em !important;
            margin: 0 0 10px !important;
            font-size: 0.72rem !important;
            line-height: 1.34 !important;
        }

        #interview-modules-page .module-card-footer {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) auto !important;
            align-items: center !important;
            padding-top: 9px !important;
            gap: 7px !important;
        }

        #interview-modules-page .module-card-views {
            font-size: 0.68rem !important;
        }

        #interview-modules-page .module-card-link {
            justify-self: end !important;
            width: auto !important;
            min-width: 104px !important;
            min-height: 26px !important;
            padding: 4px 9px !important;
            border-radius: 8px !important;
            font-size: 0.62rem !important;
            line-height: 1 !important;
        }

        #interview-modules-page .pagination {
            margin-top: 12px !important;
        }
    }

    @media (min-width: 992px) and (max-width: 1320px) {
        #interview-modules-page .module-smart-row {
            grid-template-columns: minmax(0, 1fr) clamp(300px, 31%, 380px) !important;
        }

        #interview-modules-page .modules-card-grid > [class*="col-"] {
            width: 33.333333% !important;
        }

        #interview-modules-page .module-rec-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }

    @media (min-width: 992px) {
        body.user-desktop-shell #interview-modules-page .sr-page-hero.modules-page-hero {
            --modules-page-hero-title: #0f172a;
            --modules-page-hero-text: #334155;
            --modules-page-hero-icon: #2563eb;
            --modules-page-hero-icon-bg: rgba(239, 246, 255, 0.92);
            --modules-page-hero-icon-border: rgba(147, 197, 253, 0.42);
            display: grid !important;
            grid-template-columns: 44px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 10px !important;
            min-height: 104px !important;
            margin: 0 0 14px !important;
            padding: 14px 126px 14px 14px !important;
            border: 1px solid rgba(191, 219, 254, 0.86) !important;
            border-radius: 16px !important;
            background:
                radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
                linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08) !important;
            overflow: hidden !important;
        }

        html[data-theme="dark"] body.user-desktop-shell #interview-modules-page .sr-page-hero.modules-page-hero,
        :root:not(.lm) body.user-desktop-shell #interview-modules-page .sr-page-hero.modules-page-hero,
        body.dm.user-desktop-shell #interview-modules-page .sr-page-hero.modules-page-hero,
        .dm body.user-desktop-shell #interview-modules-page .sr-page-hero.modules-page-hero {
            --modules-page-hero-title: #f8fafc;
            --modules-page-hero-text: #cbd5e1;
            --modules-page-hero-icon: #93c5fd;
            --modules-page-hero-icon-bg: rgba(59, 130, 246, 0.2);
            --modules-page-hero-icon-border: rgba(147, 197, 253, 0.32);
            border-color: rgba(147, 197, 253, 0.28) !important;
            background:
                radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
                linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22) !important;
        }

        body.user-desktop-shell #interview-modules-page .sr-page-hero.modules-page-hero::after {
            width: min(34%, 320px) !important;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.055)) !important;
        }

        body.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-inner,
        body.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-copy {
            display: contents !important;
            min-height: 0 !important;
            padding: 0 !important;
        }

        body.user-desktop-shell #interview-modules-page .modules-page-hero-icon {
            box-sizing: border-box;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 34px !important;
            height: 34px !important;
            padding: 0 !important;
            border: 1px solid var(--modules-page-hero-icon-border) !important;
            border-radius: 10px !important;
            background: var(--modules-page-hero-icon-bg) !important;
            color: var(--modules-page-hero-icon) !important;
        }

        body.user-desktop-shell #interview-modules-page .modules-page-hero-icon svg {
            width: 18px !important;
            height: 18px !important;
        }

        body.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-title {
            display: block !important;
            margin: 0 0 4px !important;
            color: var(--modules-page-hero-title) !important;
            -webkit-text-fill-color: var(--modules-page-hero-title) !important;
            background: none !important;
            -webkit-background-clip: initial !important;
            background-clip: initial !important;
            font-size: 1.02rem !important;
            line-height: 1.08 !important;
            font-weight: 950 !important;
            text-transform: none !important;
            text-shadow: none !important;
            overflow-wrap: normal !important;
        }

        body.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-title svg {
            display: none !important;
        }

        body.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-subtitle {
            max-width: 48rem !important;
            margin: 0 !important;
            color: var(--modules-page-hero-text) !important;
            -webkit-text-fill-color: var(--modules-page-hero-text) !important;
            font-size: 0.78rem !important;
            line-height: 1.32 !important;
            font-weight: 600 !important;
        }

        body.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-art {
            display: block !important;
            right: 8px !important;
            bottom: 4px !important;
            width: 94px !important;
            max-width: none !important;
            opacity: 0.92 !important;
            filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16)) !important;
        }

        html[data-theme="dark"] body.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-art,
        :root:not(.lm) body.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-art,
        body.dm.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-art,
        .dm body.user-desktop-shell #interview-modules-page .modules-page-hero .sr-page-hero-art {
            opacity: 0.88 !important;
            filter: drop-shadow(0 14px 22px rgba(0, 0, 0, 0.28)) !important;
        }
    }

    @media (max-width: 991px) {
        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-title {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
        }

        html body #interview-modules-page .modules-hero.modules-hero .modules-hero-subtitle {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
        }
    }
</style>

<div class="db-section active" id="interview-modules-page">
    @if($isMobile ?? false)
    <div class="modules-hero" aria-labelledby="modules-hero-title">
        <span class="modules-hero-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M6 4.5h9.5A2.5 2.5 0 0 1 18 7v12.5H7.5A2.5 2.5 0 0 1 5 17V6.5A2 2 0 0 1 7 4.5Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 8h7M8 11.5h7M8 15h4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7.5 19.5A2.5 2.5 0 0 1 10 17h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </span>
        <div class="modules-hero-copy">
            <h1 id="modules-hero-title" class="modules-hero-title">Philippines Interview Modules</h1>
            <p class="modules-hero-subtitle">Open action modules that tell you what to prepare, write, rehearse, revise, and check before your Philippines interview.</p>
        </div>
        <svg class="modules-hero-art" viewBox="0 0 300 240" aria-hidden="true">
            <defs><linearGradient id="modulePanelMobile" x1="58" y1="34" x2="244" y2="196"><stop stop-color="#FFFFFF"/><stop offset="1" stop-color="#EAF4FF"/></linearGradient><linearGradient id="moduleBlueMobile" x1="78" y1="128" x2="238" y2="128"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#1D9BF0"/></linearGradient><linearGradient id="moduleGreenMobile" x1="218" y1="150" x2="270" y2="190"><stop stop-color="#18D7B5"/><stop offset="1" stop-color="#10B981"/></linearGradient></defs>
            <g class="modules-art-card">
                <rect x="42" y="36" width="226" height="168" rx="30" fill="url(#modulePanelMobile)" stroke="#DBEAFE" stroke-width="4"/>
                <circle cx="82" cy="70" r="9" fill="#2563EB"/><circle cx="116" cy="70" r="9" fill="#14B8A6"/><circle cx="150" cy="70" r="9" fill="#8B5CF6"/>
                <rect class="modules-art-line" x="72" y="104" width="126" height="16" rx="8" fill="#CFE0F8"/><rect class="modules-art-line" x="72" y="140" width="144" height="16" rx="8" fill="#CFE0F8"/><rect class="modules-art-line" x="72" y="176" width="100" height="16" rx="8" fill="#CFE0F8"/>
                <rect x="72" y="204" width="86" height="16" rx="8" fill="url(#moduleBlueMobile)"/><rect x="172" y="204" width="70" height="16" rx="8" fill="#CFE0F8"/><rect x="254" y="204" width="0" height="16" rx="8" fill="url(#moduleGreenMobile)"/>
            </g>
            <g class="modules-art-check">
                <circle cx="222" cy="118" r="50" fill="url(#moduleBlueMobile)"/><path d="M198 118l17 17 34-40" fill="none" stroke="#fff" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
            <path d="M14 154l24 24M20 184l30 10" fill="none" stroke="#60A5FA" stroke-width="8" stroke-linecap="round" opacity=".8"/>
        </svg>
    </div>
    @else
    <div class="sr-page-hero modules-page-hero" aria-labelledby="modules-hero-title">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="modules-page-hero-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><circle cx="8" cy="6" r="2" fill="#eff6ff" stroke="currentColor" stroke-width="2"/><circle cx="15" cy="12" r="2" fill="#eff6ff" stroke="currentColor" stroke-width="2"/><circle cx="11" cy="18" r="2" fill="#eff6ff" stroke="currentColor" stroke-width="2"/></svg>
                </div>
                <div>
                    <h4 id="modules-hero-title" class="sr-page-hero-title text-gradient-primary">
                        Philippines Interview Modules
                    </h4>
                    <p class="sr-page-hero-subtitle">Open action modules that tell you what to prepare, write, rehearse, revise, and check before your Philippines interview.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 300 240" aria-hidden="true">
            <defs><linearGradient id="modulePanel" x1="58" y1="34" x2="244" y2="196"><stop stop-color="#FFFFFF"/><stop offset="1" stop-color="#EAF4FF"/></linearGradient><linearGradient id="moduleBlue" x1="78" y1="128" x2="238" y2="128"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#1D9BF0"/></linearGradient><linearGradient id="moduleGreen" x1="218" y1="150" x2="270" y2="190"><stop stop-color="#18D7B5"/><stop offset="1" stop-color="#10B981"/></linearGradient></defs>
            <g class="modules-art-card">
                <rect x="42" y="36" width="226" height="168" rx="30" fill="url(#modulePanel)" stroke="#DBEAFE" stroke-width="4"/>
                <circle cx="82" cy="70" r="9" fill="#2563EB"/><circle cx="116" cy="70" r="9" fill="#14B8A6"/><circle cx="150" cy="70" r="9" fill="#8B5CF6"/>
                <rect class="modules-art-line" x="72" y="104" width="126" height="16" rx="8" fill="#CFE0F8"/><rect class="modules-art-line" x="72" y="140" width="144" height="16" rx="8" fill="#CFE0F8"/><rect class="modules-art-line" x="72" y="176" width="100" height="16" rx="8" fill="#CFE0F8"/>
                <rect x="72" y="204" width="86" height="16" rx="8" fill="url(#moduleBlue)"/><rect x="172" y="204" width="70" height="16" rx="8" fill="#CFE0F8"/><rect x="254" y="204" width="0" height="16" rx="8" fill="url(#moduleGreen)"/>
            </g>
            <g class="modules-art-check">
                <circle cx="222" cy="118" r="50" fill="url(#moduleBlue)"/><path d="M198 118l17 17 34-40" fill="none" stroke="#fff" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
            </g>
            <path d="M14 154l24 24M20 184l30 10" fill="none" stroke="#60A5FA" stroke-width="8" stroke-linecap="round" opacity=".8"/>
        </svg>
    </div>
    @endif
    <!-- Sub-Navigation -->
    <div class="module-topic-select-wrap">
        <div class="module-topic-select-shell">
            <select id="moduleTopicSelect" class="module-topic-select" aria-label="Select module topic">
                <option value="{{ route('user.modules.index', array_filter(['search' => request('search')])) }}" {{ !request('category') ? 'selected' : '' }}>All Topics</option>
                @foreach($categories as $category)
                    <option value="{{ route('user.modules.index', array_filter(['category' => $category, 'search' => request('search')])) }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if((isset($moduleRecommendations) && $moduleRecommendations->count() > 0) || (isset($learningPaths) && $learningPaths->count() > 0))
        <div class="module-smart-row">
            @if(isset($moduleRecommendations) && $moduleRecommendations->count() > 0)
                <section class="module-smart-panel" aria-labelledby="module-recommendations-title">
                    <div class="module-smart-head">
                        <div>
                            <h5 id="module-recommendations-title" class="module-smart-title"><i class="fa-solid fa-wand-magic-sparkles me-2" style="color:#f59e0b"></i>Recommended For You</h5>
                            <p class="module-smart-subtitle">Suggested from your latest Philippines interview scores, feedback, and module progress.</p>
                        </div>
                        <a href="{{ route('user.progress') }}" class="module-progress-link">View Progress</a>
                    </div>
                    <div class="module-rec-grid">
                        @foreach($moduleRecommendations as $recommendation)
                            <a href="{{ $recommendation->url }}" class="module-rec-item">
                                <div class="module-rec-icon" style="--rec-color: {{ $recommendation->color }}"><i class="fa-solid {{ $recommendation->icon }}"></i></div>
                                <div class="module-rec-copy">
                                    <strong>{{ $recommendation->module->title }}</strong>
                                    <span>{{ $recommendation->reason }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if(isset($learningPaths) && $learningPaths->count() > 0)
                <section class="module-smart-panel module-path-panel" aria-labelledby="module-paths-title">
                    <div class="module-section-head">
                        <span class="module-section-icon" aria-hidden="true"><i class="fa-solid fa-route"></i></span>
                        <div>
                            <h5 id="module-paths-title" class="module-smart-title">Learning Paths</h5>
                            <p class="module-smart-subtitle">Track completion by topic so your Philippines interview preparation stays ordered.</p>
                        </div>
                    </div>
                    <div class="module-path-grid">
                        @foreach($learningPaths->take(6) as $path)
                            <a href="{{ $path->url }}" class="module-path-item">
                                <div class="module-rec-icon" style="--rec-color:#06b6d4"><i class="fa-solid fa-layer-group"></i></div>
                                <div class="module-path-copy">
                                    <strong>{{ $path->title }}</strong>
                                    <span>{{ $path->completed }}/{{ $path->total }} modules completed</span>
                                    <div class="module-path-progress" aria-label="{{ $path->progress }}% complete"><span style="--path-progress: {{ $path->progress }}%"></span></div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif

    <div class="row g-4 mb-4 modules-card-grid">
        @forelse($modules as $index => $module)
            <div class="col-12 col-md-6 col-lg-4 animate-fade-up" style="animation-delay: {{ $index * 0.1 }}s">
                <div class="module-card">
                    <div class="module-card-media">
                        <div class="module-card-badges">
                            <span class="module-card-badge"><i class="fa-solid fa-tag"></i> {{ ucfirst($module->type) }}</span>
                            @if($module->difficulty)
                                <span class="module-card-badge difficulty-{{ $module->difficulty }}">
                                    {{ ucfirst($module->difficulty) }}
                                </span>
                            @endif
                        </div>
                        <div class="module-card-icon" aria-hidden="true">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                    </div>
                    <div class="module-card-body">
                        <h5 class="module-card-title">{{ $module->title }}</h5>
                        <p class="module-card-desc">
                            {{ \Illuminate\Support\Str::limit($module->description, 100) }}
                        </p>
                        
                        <div class="module-card-footer">
                            <div class="module-card-views">
                                <i class="fa-solid fa-eye me-1"></i> {{ number_format($module->views) }} views
                            </div>
                            <a href="{{ route('user.modules.show', $module->id) }}" class="module-card-link btn-shine">
                                Open Action Module <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5" style="background:var(--bg2); border-radius:16px; border:1px solid var(--bd);">
                    <i class="fa-solid fa-folder-open fa-3x mb-3" style="color:var(--bd)"></i>
                    <h5 style="color:var(--tx3)">No modules found for this topic.</h5>
                </div>
            </div>
        @endforelse
    </div>

    @if($modules->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $modules->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const topicSelect = document.getElementById('moduleTopicSelect');
        if (topicSelect) {
            topicSelect.addEventListener('change', function () {
                if (this.value) {
                    window.location.href = this.value;
                }
            });
        }
    });
</script>
@endpush
@endsection
