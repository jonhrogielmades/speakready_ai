@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Account Management')

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
        border: 1px solid var(--bd) !important;
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
    #account-page,
    .profile-crop-modal {
        --acct-blue: #1d4fff;
        --acct-sky: #08a8f5;
        --acct-ink: #061544;
        --acct-muted: #52617f;
        --acct-line: rgba(120, 154, 220, 0.26);
        --acct-card-bg: rgba(255, 255, 255, 0.84);
        --acct-card-border: rgba(207, 221, 247, 0.9);
        --acct-field-bg: rgba(255, 255, 255, 0.78);
        --acct-field-border: rgba(138, 159, 195, 0.38);
        --acct-icon-bg: linear-gradient(145deg, #eef5ff, #e6efff);
        --acct-primary-text: #1d4ed8;
        --acct-danger: #dc2626;
        --acct-danger-soft: rgba(255, 241, 242, 0.9);
        --acct-danger-border: rgba(239, 68, 68, 0.34);
        --acct-danger-bg: linear-gradient(145deg, rgba(255,255,255,0.9), rgba(255,244,244,0.88));
        --acct-success-bg: rgba(240, 253, 244, 0.94);
        --acct-success-text: #166534;
        --acct-success-border: rgba(34, 197, 94, 0.34);
        --acct-error-bg: rgba(254, 242, 242, 0.94);
        --acct-error-text: #991b1b;
        --acct-error-border: rgba(239, 68, 68, 0.34);
        --acct-modal-backdrop: rgba(2, 6, 23, 0.62);
        --acct-modal-bg: rgba(255, 255, 255, 0.96);
        --acct-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
    }
    #account-page {
        max-width: 1120px;
        margin: 0 auto;
    }
    :root:not(.lm) #account-page,
    :root:not(.lm) .profile-crop-modal,
    .dm #account-page,
    .dm .profile-crop-modal {
        --acct-blue: #93c5fd;
        --acct-sky: #22d3ee;
        --acct-ink: var(--tx);
        --acct-muted: var(--tx2);
        --acct-line: rgba(147, 197, 253, 0.22);
        --acct-card-bg: color-mix(in srgb, var(--bg3) 82%, transparent);
        --acct-card-border: var(--bd);
        --acct-field-bg: color-mix(in srgb, var(--bg2) 86%, transparent);
        --acct-field-border: rgba(148, 163, 184, 0.34);
        --acct-icon-bg: linear-gradient(145deg, rgba(59, 130, 246, 0.2), rgba(14, 165, 233, 0.12));
        --acct-primary-text: #bfdbfe;
        --acct-danger: #fca5a5;
        --acct-danger-soft: rgba(127, 29, 29, 0.24);
        --acct-danger-border: rgba(248, 113, 113, 0.38);
        --acct-danger-bg: linear-gradient(145deg, rgba(127, 29, 29, 0.28), rgba(15, 23, 42, 0.66));
        --acct-success-bg: rgba(20, 83, 45, 0.24);
        --acct-success-text: #bbf7d0;
        --acct-success-border: rgba(74, 222, 128, 0.32);
        --acct-error-bg: rgba(127, 29, 29, 0.3);
        --acct-error-text: #fecaca;
        --acct-error-border: rgba(248, 113, 113, 0.38);
        --acct-modal-backdrop: rgba(2, 6, 23, 0.76);
        --acct-modal-bg: color-mix(in srgb, var(--bg3) 88%, transparent);
        --acct-shadow: 0 18px 50px rgba(0, 0, 0, 0.22);
    }
    #account-page .sr-page-hero {
        --account-hero-title: #1d4ed8;
        --account-hero-text: #334155;
        --account-icon-bg: rgba(239, 246, 255, 0.92);
        --account-icon-border: rgba(147, 197, 253, 0.42);
        display: grid !important;
        grid-template-columns: 44px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 10px !important;
        min-height: 64px !important;
        border-radius: 16px;
        padding: 5px 82px 5px 10px !important;
        margin-bottom: 10px;
        overflow: hidden;
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
            linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
        border: 1px solid rgba(191, 219, 254, 0.86);
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
    }
    :root:not(.lm) #account-page .sr-page-hero,
    .dm #account-page .sr-page-hero {
        --account-hero-title: #93c5fd;
        --account-hero-text: #e2e8f0;
        --account-icon-bg: rgba(59, 130, 246, 0.2);
        --account-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28);
    }
    #account-page .sr-page-hero-inner,
    #account-page .sr-page-hero-copy {
        display: contents !important;
        min-height: 0 !important;
        padding: 0 !important;
    }
    #account-page .account-hero-icon {
        box-sizing: border-box;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 30px !important;
        height: 30px !important;
        border: 1px solid var(--account-icon-border) !important;
        border-radius: 10px !important;
        background: var(--account-icon-bg) !important;
        color: var(--account-hero-title) !important;
        font-size: 0.78rem !important;
    }
    #account-page .sr-page-hero-title {
        display: block !important;
        margin: 0 0 4px !important;
        color: var(--account-hero-title) !important;
        background: none !important;
        -webkit-text-fill-color: var(--account-hero-title) !important;
        font-size: 0.88rem !important;
        font-weight: 950 !important;
        line-height: 1.08 !important;
        letter-spacing: 0 !important;
        text-transform: uppercase !important;
    }
    #account-page .sr-page-hero-title svg {
        display: none;
    }
    #account-page .sr-page-hero-subtitle {
        margin: 0;
        max-width: 12rem;
        color: var(--account-hero-text) !important;
        font-size: 0.58rem !important;
        line-height: 1.24;
        font-weight: 500;
    }
    #account-page .sr-page-hero-art {
        width: 52px;
        right: 8px;
        bottom: 4px;
        opacity: 0.92;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16));
        animation: accountHeroFloat 4.8s ease-in-out infinite;
        transform-origin: 50% 78%;
    }
    #account-page .sr-page-hero-art :is(circle, rect, path):nth-child(odd) {
        transform-origin: center;
        animation: accountHeroPulse 3.4s ease-in-out infinite;
    }
    @keyframes accountHeroFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-4px) rotate(-1deg); }
    }
    @keyframes accountHeroPulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .78; }
    }
    @media (prefers-reduced-motion: reduce) {
        #account-page .sr-page-hero-art,
        #account-page .sr-page-hero-art :is(circle, rect, path) {
            animation: none !important;
        }
    }
    #account-page .account-grid {
        align-items: start;
    }
    #account-page .account-card {
        padding: 16px !important;
        border-radius: 16px !important;
        background: var(--acct-card-bg) !important;
        border-color: var(--acct-card-border) !important;
        box-shadow: var(--acct-shadow) !important;
    }
    #account-page .account-card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0 0 14px;
        color: var(--acct-ink);
        font-size: 1rem;
        font-weight: 900;
        line-height: 1.2;
        letter-spacing: 0;
    }
    #account-page .account-card-title::after {
        content: "";
        height: 1px;
        flex: 1 1 auto;
        background: var(--acct-line);
    }
    #account-page .account-title-icon,
    #account-page .account-label-icon,
    #account-page .password-prefix-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: var(--acct-blue);
        background: var(--acct-icon-bg);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.85);
    }
    #account-page .account-title-icon {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        font-size: 0.9rem;
    }
    #account-page .account-title-icon i,
    #account-page .danger-icon i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: currentColor !important;
        font-size: 0.95rem !important;
        line-height: 1;
    }
    #account-page .account-photo-row {
        flex-direction: column;
        justify-content: center;
        gap: 10px;
        margin-bottom: 16px !important;
        text-align: center;
    }
    #account-page .account-photo-avatar {
        position: relative;
        width: 78px !important;
        height: 78px !important;
        margin: 0 !important;
        border-radius: 50% !important;
        border: 5px solid color-mix(in srgb, var(--acct-field-bg) 76%, #e9f1ff 24%) !important;
        outline: 2px solid rgba(29, 79, 255, 0.24);
        box-shadow: 0 14px 30px rgba(29, 79, 255, 0.16);
    }
    #account-page .account-photo-avatar::after {
        content: none !important;
        display: none !important;
    }
    #account-page .upload-picture-btn {
        min-height: 34px;
        padding: 7px 14px;
        border-radius: 10px !important;
        border: 1px solid var(--acct-field-border);
        color: var(--acct-primary-text);
        background: var(--acct-field-bg);
        font-weight: 900;
        font-size: 0.72rem;
    }
    #account-page .upload-hint {
        margin-top: 8px;
        color: var(--acct-muted);
        font-size: 0.68rem;
        font-weight: 500;
    }
    #account-page .account-field {
        margin-bottom: 12px;
    }
    #account-page .account-field-label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        color: var(--acct-ink);
        font-size: 0.76rem;
        font-weight: 900;
    }
    #account-page .account-label-icon {
        width: 28px;
        height: 28px;
        border-radius: 9px;
        font-size: 0.72rem;
    }
    #account-page .oinp {
        width: 100%;
        min-height: 40px;
        border-radius: 10px;
        border: 1px solid var(--acct-field-border);
        background: var(--acct-field-bg);
        color: var(--acct-ink);
        padding: 8px 12px;
        font-size: 0.78rem;
        font-weight: 500;
    }
    #account-page .oinp::placeholder {
        color: var(--acct-muted);
        opacity: 1;
    }
    #account-page .password-field {
        position: relative;
        display: flex;
        align-items: stretch;
    }
    #account-page .password-field .oinp {
        padding-left: 42px;
        padding-right: 34px;
    }
    #account-page .password-prefix-icon {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 2;
        width: 32px;
        border-radius: 9px;
        font-size: 0.72rem;
    }
    #account-page .password-toggle {
        position: absolute;
        top: 50%;
        right: 6px;
        transform: translateY(-50%);
        z-index: 3;
        width: 26px;
        height: 26px;
        border: 0;
        background: transparent;
        color: var(--acct-muted);
        font-size: 0.82rem;
        display: grid;
        place-items: center;
    }
    #account-page .account-submit-btn {
        width: 100%;
        min-height: 42px;
        border: 0 !important;
        border-radius: 12px !important;
        color: #fff !important;
        background: linear-gradient(135deg, var(--acct-blue), var(--acct-sky)) !important;
        box-shadow: 0 14px 28px rgba(14, 116, 245, 0.28);
        font-size: 0.82rem;
        font-weight: 900;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    #account-page .danger-card {
        margin-top: 12px;
        background: var(--acct-danger-bg) !important;
        border-color: var(--acct-danger-border) !important;
    }
    #account-page .danger-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 8px;
        color: var(--acct-danger);
        font-size: 0.9rem;
        font-weight: 900;
    }
    #account-page .danger-icon {
        width: 34px;
        height: 34px;
        border-radius: 11px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--acct-danger) 14%, var(--acct-field-bg));
        color: var(--acct-danger);
        font-size: 0.9rem;
    }
    #account-page .danger-copy {
        color: var(--acct-muted);
        font-size: 0.74rem;
        line-height: 1.42;
        margin-bottom: 12px;
    }
    #account-page .delete-account-btn {
        width: auto;
        min-width: 0;
        width: min(100%, 180px);
        margin: 0 auto;
        padding: 0 18px;
        min-height: 38px;
        border-radius: 10px !important;
        border: 1px solid var(--acct-danger-border) !important;
        color: var(--acct-danger) !important;
        background: var(--acct-danger-soft) !important;
        font-size: 0.74rem;
        font-weight: 900;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    #account-page .danger-card form {
        text-align: center;
    }
    #account-page .alert {
        border-radius: 12px !important;
        border-width: 1px !important;
        font-size: 0.82rem;
        font-weight: 600;
    }
    #account-page .alert-success {
        background: var(--acct-success-bg) !important;
        border-color: var(--acct-success-border) !important;
        color: var(--acct-success-text) !important;
    }
    #account-page .alert-danger {
        background: var(--acct-error-bg) !important;
        border-color: var(--acct-error-border) !important;
        color: var(--acct-error-text) !important;
    }
    #account-page .alert :is(ul, li) {
        color: inherit !important;
    }
    #account-page .btn-close {
        filter: none;
        opacity: 0.75;
    }
    :root:not(.lm) #account-page .btn-close,
    .dm #account-page .btn-close {
        filter: invert(1) grayscale(100%);
    }
    .profile-crop-modal {
        position: fixed;
        inset: 0;
        z-index: 1200;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        background: var(--acct-modal-backdrop);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
    }
    .profile-crop-modal.open {
        display: flex;
    }
    .profile-crop-dialog {
        width: min(420px, 100%);
        border: 1px solid var(--acct-card-border);
        border-radius: 18px;
        background: var(--acct-modal-bg);
        box-shadow: 0 24px 70px rgba(0,0,0,0.35);
        padding: 18px;
    }
    .profile-crop-dialog h6,
    .profile-crop-dialog .olbl {
        color: var(--acct-ink) !important;
    }
    .profile-crop-frame {
        width: 100%;
        aspect-ratio: 1;
        border: 1px solid var(--acct-field-border);
        border-radius: 16px;
        background: var(--acct-field-bg);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 14px 0;
    }
    .profile-crop-frame canvas {
        width: 100%;
        height: 100%;
        display: block;
        cursor: move;
        touch-action: none;
    }
    .profile-crop-controls {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .profile-crop-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 14px;
    }
    .profile-crop-actions .btn-outline-secondary {
        border-color: var(--acct-field-border) !important;
        background: var(--acct-field-bg) !important;
        color: var(--acct-ink) !important;
    }
    .profile-crop-controls .form-range {
        accent-color: var(--acct-blue);
    }
    @media (max-width: 767px) {
        #account-page .premium-panel {
            padding: 14px !important;
            border-radius: 14px !important;
            margin-bottom: 12px !important;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08) !important;
        }
        #account-page .premium-panel:hover {
            transform: none;
        }
        #account-page .premium-panel h5,
        #account-page .premium-panel h6 {
            font-size: 0.98rem;
            line-height: 1.25;
            margin-bottom: 14px !important;
        }
        #account-page .account-photo-row {
            align-items: center !important;
            flex-direction: column;
            justify-content: center;
            gap: 12px;
            text-align: center;
            margin-bottom: 16px !important;
        }
        #account-page .account-photo-avatar {
            width: 58px !important;
            height: 58px !important;
            border-radius: 16px !important;
            margin-right: 0 !important;
            font-size: 1.35rem !important;
            flex: 0 0 58px;
        }
        #account-page .account-photo-actions {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        #account-page .account-photo-actions .btn {
            width: auto;
            min-width: 160px;
        }
        #account-page .olbl {
            font-size: 0.76rem;
            margin-bottom: 6px;
        }
        #account-page .oinp {
            min-height: 44px;
            padding: 10px 12px;
            border-radius: 11px;
            font-size: 0.86rem;
        }
        #account-page .btn {
            min-height: 42px;
            border-radius: 12px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        #account-page .text-end {
            text-align: left !important;
        }
        #account-page .text-end .btn,
        #account-page .premium-panel form > .btn,
        #account-page .premium-panel form button[type="submit"] {
            width: 100%;
        }
        #account-page .danger-card form .delete-account-btn {
            width: min(100%, 160px) !important;
            margin-inline: auto !important;
        }
        #account-page .alert {
            border-radius: 12px !important;
            font-size: 0.82rem;
        }
    }
    @media (max-width: 767px) {
        #account-page {
            max-width: 480px;
        }
        #account-page .sr-page-hero {
            min-height: 62px !important;
            grid-template-columns: 36px minmax(0, 1fr) !important;
            gap: 9px !important;
            border-radius: 16px;
            padding: 5px 62px 5px 10px !important;
            margin-bottom: 9px;
        }
        #account-page .sr-page-hero-copy {
            max-width: none;
        }
        #account-page .sr-page-hero-title {
            font-size: 0.78rem !important;
            line-height: 1.08;
        }
        #account-page .sr-page-hero-title svg {
            display: none;
        }
        #account-page .sr-page-hero-subtitle {
            max-width: 10.6rem;
            margin-top: 0;
            font-size: 0.56rem !important;
            line-height: 1.22;
        }
        #account-page .sr-page-hero-art {
            width: 46px;
            right: -4px;
            bottom: 5px;
        }
        #account-page .account-card {
            padding: 12px !important;
            border-radius: 14px !important;
        }
        #account-page .account-card-title {
            gap: 8px;
            font-size: 0.88rem !important;
            margin-bottom: 12px !important;
        }
        #account-page .account-card-title::after {
            min-width: 24px;
        }
        #account-page .account-title-icon,
        #account-page .danger-icon {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
        #account-page .account-title-icon i,
        #account-page .danger-icon i {
            font-size: 0.82rem !important;
        }
        #account-page .account-photo-avatar {
            width: 58px !important;
            height: 58px !important;
            flex: 0 0 58px;
            border-width: 4px !important;
        }
        #account-page .upload-picture-btn {
            min-height: 32px;
            padding: 6px 12px;
            font-size: 0.68rem;
        }
        #account-page .upload-hint {
            font-size: 0.62rem;
        }
        #account-page .account-field {
            margin-bottom: 10px;
        }
        #account-page .account-field-label {
            gap: 7px;
            font-size: 0.7rem;
            margin-bottom: 5px;
        }
        #account-page .account-label-icon {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            font-size: 0.68rem;
        }
        #account-page .oinp {
            min-height: 36px;
            padding: 7px 10px;
            border-radius: 9px;
            font-size: 0.72rem;
        }
        #account-page .password-field .oinp {
            padding-left: 38px;
            padding-right: 32px;
        }
        #account-page .password-prefix-icon {
            width: 30px;
            border-radius: 8px;
            font-size: 0.68rem;
        }
        #account-page .password-toggle {
            right: 5px;
            width: 24px;
            height: 24px;
            font-size: 0.76rem;
        }
        #account-page .account-submit-btn {
            min-height: 38px;
            border-radius: 10px !important;
            font-size: 0.74rem;
            gap: 7px;
        }
        #account-page .danger-title {
            gap: 8px;
            font-size: 0.82rem !important;
        }
        #account-page .danger-copy {
            font-size: 0.68rem;
            margin-bottom: 10px;
        }
        #account-page .delete-account-btn {
            min-height: 34px;
            font-size: 0.68rem;
            width: min(100%, 160px) !important;
            padding-inline: 14px;
        }
    }
    @media (max-width: 380px) {
        #account-page .sr-page-hero {
            min-height: 60px !important;
            grid-template-columns: 36px minmax(0, 1fr) !important;
            gap: 9px !important;
            padding: 5px 56px 5px 10px !important;
        }
        #account-page .sr-page-hero-copy {
            max-width: none;
        }
        #account-page .sr-page-hero-title {
            font-size: 0.74rem !important;
        }
        #account-page .sr-page-hero-subtitle {
            max-width: 9.8rem;
            font-size: 0.52rem !important;
            line-height: 1.18;
        }
        #account-page .sr-page-hero-art {
            right: -4px;
            bottom: 5px;
            width: 40px;
        }
        #account-page .account-card {
            padding: 10px !important;
        }
        #account-page .account-card-title {
            font-size: 0.82rem !important;
        }
        #account-page .oinp {
            font-size: 0.68rem;
        }
    }
</style>
@include('partials.page-hero-styles')
<style>
    #account-page .sr-page-hero .sr-page-hero-art {
        width: 94px !important;
        right: 8px !important;
        bottom: 4px !important;
    }
    @media (max-width: 767px) {
        #account-page .sr-page-hero .sr-page-hero-art {
            width: 84px !important;
            right: -4px !important;
            bottom: 5px !important;
        }
    }
    @media (max-width: 380px) {
        #account-page .sr-page-hero .sr-page-hero-art {
            width: 68px !important;
        }
    }
</style>

<div class="db-section active animate-fade-up" id="account-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="account-hero-icon"><i class="fa-regular fa-user"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 8v4M17 10h4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Account Management
                    </h4>
                    <p class="sr-page-hero-subtitle">Update profile and security settings.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="accountPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="accountBlue" x1="66" y1="36" x2="166" y2="118"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#accountPanel)" stroke="#BFDBFE" stroke-width="3"/><circle cx="103" cy="63" r="23" fill="url(#accountBlue)"/><path d="M64 114a40 40 0 0 1 78 0" fill="#BAE6FD"/><circle cx="158" cy="53" r="20" fill="#22C55E"/><path d="M158 43v20M148 53h20" stroke="#fff" stroke-width="5" stroke-linecap="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:12px">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:12px">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif


    <div class="row g-4 account-grid">
        <div class="col-lg-7 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="premium-panel account-card" style="padding:32px;margin-bottom:24px">
                <h5 class="account-card-title"><span class="account-title-icon"><i class="fa-regular fa-user"></i></span>Profile Details</h5>
                
                <form action="{{ route('user.account.profile') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="d-flex align-items-center mb-4 account-photo-row">
                        @if(Auth::user()->profile_photo_path)
                            <div class="account-photo-avatar" style="width:80px;height:80px;border-radius:24px;overflow:hidden;margin-right:24px;border:1px solid var(--bd)">
                                @if(Str::startsWith(Auth::user()->profile_photo_path, ['http://', 'https://', 'data:']))
                                    <img id="profilePhotoPreview" src="{{ Auth::user()->profile_photo_path }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;">
                                @else
                                    <img id="profilePhotoPreview" src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;">
                                @endif
                            </div>
                        @else
                            <div class="account-photo-avatar" style="width:80px;height:80px;background:var(--pur);border-radius:24px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:700;margin-right:24px;overflow:hidden;">
                                <img id="profilePhotoPreview" src="" alt="Profile Photo preview" style="width:100%;height:100%;object-fit:cover;display:none;">
                                <span id="profilePhotoInitial">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="account-photo-actions">
                            <input type="file" name="profile_photo" id="profile_photo" class="d-none" accept="image/png, image/jpeg, image/gif">
                            <button type="button" class="btn upload-picture-btn" onclick="document.getElementById('profile_photo').click()"><i class="fa-solid fa-cloud-arrow-up"></i> Upload New Picture</button>
                            <div class="upload-hint" id="photo-filename">JPG, GIF or PNG. Max size of 2MB.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="account-field-label"><span class="account-label-icon"><i class="fa-regular fa-user"></i></span>Full Name</label>
                            <input type="text" class="oinp" name="name" value="{{ old('name', Auth::user()->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="account-field-label"><span class="account-label-icon"><i class="fa-regular fa-envelope"></i></span>Email Address</label>
                            <input type="email" class="oinp" name="email" value="{{ old('email', Auth::user()->email) }}" required>
                        </div>
                    </div>
                    <div class="account-field">
                        <label class="account-field-label"><span class="account-label-icon"><i class="fa-solid fa-briefcase"></i></span>Target Job Position</label>
                        <input type="text" class="oinp" name="target_position" value="{{ old('target_position', Auth::user()->target_position) }}" placeholder="e.g., Data Analyst">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn account-submit-btn btn-shine"><i class="fa-regular fa-floppy-disk"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5 animate-fade-up" style="animation-delay: 0.2s;">
            <div class="premium-panel account-card" style="padding:32px">
                <h5 class="account-card-title"><span class="account-title-icon"><i class="fa-solid fa-lock"></i></span>Security & Password</h5>
                <form action="{{ route('user.account.password') }}" method="POST">
                    @csrf
                    <div class="account-field">
                        <label class="account-field-label">Current Password</label>
                        <div class="password-field">
                           <span class="password-prefix-icon"><i class="fa-solid fa-lock"></i></span>
                           <input type="password" class="oinp" name="current_password" id="currentPassword" placeholder="••••••••" required>
                           <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('currentPassword', this)" aria-label="Show password">
                              <i class="fa-solid fa-eye-slash"></i>
                           </button>
                        </div>
                    </div>
                    <div class="account-field">
                        <label class="account-field-label">New Password</label>
                        <div class="password-field">
                           <span class="password-prefix-icon"><i class="fa-solid fa-lock"></i></span>
                           <input type="password" class="oinp" name="new_password" id="newPassword" placeholder="••••••••" required minlength="8">
                           <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('newPassword', this)" aria-label="Show password">
                              <i class="fa-solid fa-eye-slash"></i>
                           </button>
                        </div>
                    </div>
                    <div class="account-field">
                        <label class="account-field-label">Confirm New Password</label>
                        <div class="password-field">
                           <span class="password-prefix-icon"><i class="fa-solid fa-lock"></i></span>
                           <input type="password" class="oinp" name="confirm_password" id="confirmPassword" placeholder="••••••••" required minlength="8">
                           <button type="button" class="password-toggle toggle-password" onclick="togglePasswordVisibility('confirmPassword', this)" aria-label="Show password">
                              <i class="fa-solid fa-eye-slash"></i>
                           </button>
                        </div>
                    </div>
                    <button type="submit" class="btn account-submit-btn btn-shine"><i class="fa-solid fa-shield-halved"></i>Update Password</button>
                </form>
            </div>
            
            <div class="premium-panel account-card danger-card animate-fade-up" style="animation-delay: 0.3s;padding:24px;margin-top:24px">
                <h6 class="danger-title"><span class="danger-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>Danger Zone</h6>
                <p class="danger-copy">Once you delete your account, there is no going back. Please be certain.</p>
                <form action="{{ route('user.account.delete') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn delete-account-btn"><i class="fa-regular fa-trash-can"></i>Delete Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="profile-crop-modal" id="profileCropModal" aria-hidden="true">
    <div class="profile-crop-dialog" role="dialog" aria-modal="true" aria-labelledby="profileCropTitle">
        <h6 id="profileCropTitle" style="color:var(--tx);font-weight:800;margin:0;">Crop Profile Picture</h6>
        <div class="profile-crop-frame">
            <canvas id="profileCropCanvas" width="320" height="320"></canvas>
        </div>
        <div class="profile-crop-controls">
            <label class="olbl" for="profileCropZoom" style="margin:0;">Zoom</label>
            <input type="range" id="profileCropZoom" min="1" max="3" step="0.01" value="1" class="form-range">
        </div>
        <div class="profile-crop-actions">
            <button type="button" class="btn btn-outline-secondary" onclick="cancelProfileCrop()">Cancel</button>
            <button type="button" class="btn bgrd" onclick="applyProfileCrop()">Use Crop</button>
        </div>
    </div>
</div>

<script>
    const profileInput = document.getElementById('profile_photo');
    const profilePreview = document.getElementById('profilePhotoPreview');
    const cropModal = document.getElementById('profileCropModal');
    const cropCanvas = document.getElementById('profileCropCanvas');
    const cropZoom = document.getElementById('profileCropZoom');
    const cropCtx = cropCanvas ? cropCanvas.getContext('2d') : null;
    let cropImage = null;
    let cropSourceName = 'profile-photo.jpg';
    let cropScale = 1;
    let cropOffsetX = 0;
    let cropOffsetY = 0;
    let cropDragging = false;
    let cropLastX = 0;
    let cropLastY = 0;

    function drawProfileCrop() {
        if (!cropCtx || !cropImage) return;
        const size = cropCanvas.width;
        cropCtx.clearRect(0, 0, size, size);
        cropCtx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--bg3') || '#111827';
        cropCtx.fillRect(0, 0, size, size);

        const baseScale = Math.max(size / cropImage.width, size / cropImage.height);
        const scale = baseScale * cropScale;
        const width = cropImage.width * scale;
        const height = cropImage.height * scale;
        const minX = size - width;
        const minY = size - height;
        cropOffsetX = Math.min(0, Math.max(minX, cropOffsetX));
        cropOffsetY = Math.min(0, Math.max(minY, cropOffsetY));
        cropCtx.drawImage(cropImage, cropOffsetX, cropOffsetY, width, height);
    }

    function openProfileCrop(file) {
        if (!file) return;
        cropSourceName = file.name || 'profile-photo.jpg';
        document.getElementById('photo-filename').textContent = cropSourceName;
        const reader = new FileReader();
        reader.onload = event => {
            cropImage = new Image();
            cropImage.onload = () => {
                cropScale = 1;
                cropZoom.value = '1';
                const size = cropCanvas.width;
                const baseScale = Math.max(size / cropImage.width, size / cropImage.height);
                cropOffsetX = (size - cropImage.width * baseScale) / 2;
                cropOffsetY = (size - cropImage.height * baseScale) / 2;
                cropModal.classList.add('open');
                cropModal.setAttribute('aria-hidden', 'false');
                drawProfileCrop();
            };
            cropImage.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }

    function cancelProfileCrop() {
        cropModal.classList.remove('open');
        cropModal.setAttribute('aria-hidden', 'true');
        if (profileInput) profileInput.value = '';
        document.getElementById('photo-filename').textContent = 'JPG, GIF or PNG. Max size of 2MB.';
    }

    function applyProfileCrop() {
        if (!cropCanvas || !profileInput) return;
        cropCanvas.toBlob(blob => {
            if (!blob) return;
            const file = new File([blob], cropSourceName.replace(/\.[^.]+$/, '') + '-cropped.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            transfer.items.add(file);
            profileInput.files = transfer.files;

            const previewUrl = URL.createObjectURL(file);
            if (profilePreview) {
                profilePreview.src = previewUrl;
                profilePreview.style.display = 'block';
                const initial = document.getElementById('profilePhotoInitial');
                if (initial) initial.style.display = 'none';
            }
            document.getElementById('photo-filename').textContent = file.name;
            cropModal.classList.remove('open');
            cropModal.setAttribute('aria-hidden', 'true');
        }, 'image/jpeg', 0.9);
    }

    if (profileInput) {
        profileInput.addEventListener('change', function () {
            openProfileCrop(this.files && this.files[0]);
        });
    }

    if (cropZoom) {
        cropZoom.addEventListener('input', function () {
            cropScale = parseFloat(this.value) || 1;
            drawProfileCrop();
        });
    }

    if (cropCanvas) {
        const pointerPosition = event => {
            const rect = cropCanvas.getBoundingClientRect();
            return {
                x: (event.clientX - rect.left) * (cropCanvas.width / rect.width),
                y: (event.clientY - rect.top) * (cropCanvas.height / rect.height)
            };
        };
        cropCanvas.addEventListener('pointerdown', event => {
            cropDragging = true;
            const pos = pointerPosition(event);
            cropLastX = pos.x;
            cropLastY = pos.y;
            cropCanvas.setPointerCapture(event.pointerId);
        });
        cropCanvas.addEventListener('pointermove', event => {
            if (!cropDragging) return;
            const pos = pointerPosition(event);
            cropOffsetX += pos.x - cropLastX;
            cropOffsetY += pos.y - cropLastY;
            cropLastX = pos.x;
            cropLastY = pos.y;
            drawProfileCrop();
        });
        cropCanvas.addEventListener('pointerup', () => { cropDragging = false; });
        cropCanvas.addEventListener('pointercancel', () => { cropDragging = false; });
    }

    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (!input || !icon) return;
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            btn.setAttribute('aria-label', 'Hide password');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            btn.setAttribute('aria-label', 'Show password');
        }
    }
</script>
@endsection
