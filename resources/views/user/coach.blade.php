@extends($isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Readiness Coach')

@section('content')
@php
    $coachUser = Auth::user();
    $coachUserInitial = $coachUser ? strtoupper(substr((string) $coachUser->name, 0, 1)) : 'U';
    $coachUserPhotoUrl = null;

    if ($coachUser?->profile_photo_path) {
        $coachPhotoPath = $coachUser->profile_photo_path;
        $coachUserPhotoUrl = Str::startsWith($coachPhotoPath, ['http://', 'https://', 'data:'])
            ? $coachPhotoPath
            : asset('storage/' . $coachPhotoPath);
    }
@endphp
<style>
    /* Chat specific styles */
    .chat-container {
        display: flex;
        height: min(620px, calc(100vh - 238px));
        min-height: 390px;
        max-width: 980px;
        margin: 0 auto;
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }
    .chat-sidebar { width: 280px; border-right: 1px solid var(--bd); display: flex; flex-direction: column; background: linear-gradient(180deg, rgba(255,255,255,0.02) 0%, transparent 100%); }
    .chat-main { flex-grow: 1; display: flex; flex-direction: column; position: relative; min-height: 0; }
    .coach-chat-header {
        padding: 15px 22px;
        border-bottom: 1px solid var(--bd);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        background: rgba(255,255,255,0.02);
    }
    .coach-chat-title {
        margin: 0;
        color: var(--tx);
        font-size: 0.88rem;
        font-weight: 800;
        line-height: 1.14;
        letter-spacing: 0;
        max-width: 260px;
    }
    .coach-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 7px;
        color: #10b981;
        font-size: 0.82rem;
        font-weight: 600;
    }
    .coach-status::before {
        content: "";
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: currentColor;
        box-shadow: 0 0 0 4px rgba(16,185,129,0.12);
    }
    .chat-messages { flex-grow: 1; overflow-y: auto; padding: 22px; display: flex; flex-direction: column; gap: 18px; }

    .chat-bubble { max-width: 80%; padding: 16px 20px; border-radius: 20px; font-size: .95rem; line-height: 1.5; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .bubble-ai { background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(139,92,246,0.05) 100%); border: 1px solid rgba(139,92,246,0.2); border-bottom-left-radius: 4px; color: var(--tx); align-self: flex-start; box-shadow: inset 0 2px 10px rgba(255,255,255,0.05); }
    .bubble-user { background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%); color: #fff; border-bottom-right-radius: 4px; align-self: flex-end; border: none; }
    .ai-response { display: grid; gap: 10px; line-height: 1.62; }
    .ai-response p { margin: 0; }
    .ai-response strong { color: var(--tx); font-weight: 800; }
    .ai-response em { color: var(--tx2); font-style: italic; }
    .ai-response .ai-section-title {
        display: block;
        margin-top: 2px;
        color: #60a5fa;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .ai-response ul, .ai-response ol {
        margin: 0;
        padding-left: 1.15rem;
        display: grid;
        gap: 7px;
    }
    .ai-response li { padding-left: 2px; }
    .ai-response code {
        padding: 1px 5px;
        border-radius: 6px;
        background: rgba(255,255,255,0.08);
        color: var(--tx);
        font-size: .88em;
    }

    .coach-msg-row { display: flex; gap: 14px; }
    .coach-avatar {
        width: 34px;
        height: 34px;
        background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
        border-radius: 10px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 8px 18px rgba(37,99,235,0.18);
    }
    .coach-avatar::after {
        content: "";
        position: absolute;
        right: -2px;
        bottom: -2px;
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #22c55e;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.18);
        pointer-events: none;
    }
    .coach-user-avatar {
        width: 34px;
        height: 34px;
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--tx);
        flex-shrink: 0;
        font-weight: 700;
        padding: 0;
        overflow: hidden;
        border: 1px solid var(--bd);
    }
    .coach-user-avatar::after {
        content: "";
        position: absolute;
        right: -2px;
        bottom: -2px;
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #22c55e;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.18);
        pointer-events: none;
        z-index: 2;
    }
    .coach-user-avatar-img {
        position: relative;
        z-index: 1;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: inherit;
    }
    .chat-input-area { padding: 12px 14px 10px; border-top: 1px solid var(--bd); background: rgba(255,255,255,0.02); flex-shrink: 0; }
    .chat-input-wrapper { display: flex; align-items: center; background: var(--bg3); border: 1px solid var(--bd); border-radius: 15px; padding: 7px 8px 7px 13px; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    .chat-input-wrapper:focus-within { border-color: var(--pur) !important; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15); background: var(--sf); }
    .chat-textarea { flex-grow: 1; min-width: 0; background: transparent; border: none; color: var(--tx); resize: none; height: 22px; max-height: 88px; overflow: hidden; padding: 2px 0; outline: none; font-family: "Space Grotesk", sans-serif; font-size: 0.74rem; line-height: 1.28; }
    .chat-send-btn { background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%); color: #fff; border: none; width: 34px; height: 34px; border-radius: 11px; display: flex; align-items: center; justify-content: center; margin-left: 9px; margin-bottom: 0; cursor: pointer; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); flex-shrink: 0; box-shadow: 0 4px 15px rgba(139,92,246,0.3); }
    .chat-send-btn:hover { transform: scale(1.05) translateY(-2px); box-shadow: 0 6px 20px rgba(139,92,246,0.5); }
    .chat-file-input { display: none; }
    .chat-attachment-btn {
        width: 32px;
        height: 32px;
        border: 1px solid rgba(37, 99, 235, 0.45);
        border-radius: 10px;
        background: linear-gradient(135deg, #dbeafe, #7dd3fc);
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        margin-right: 7px;
        box-shadow: 0 6px 14px rgba(37, 99, 235, 0.2);
        transition: 0.2s;
    }
    .chat-attachment-btn:hover,
    .chat-attachment-btn:focus-visible {
        background: linear-gradient(135deg, #bfdbfe, #67e8f9);
        color: #082f49;
        outline: none;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.28);
    }
    .chat-attachment-btn i,
    .chat-attachment-btn i::before,
    .chat-attachment-btn i::after {
        color: #0f172a !important;
        -webkit-text-fill-color: #0f172a !important;
        opacity: 1 !important;
    }
    .chat-attachment-preview {
        display: none;
        flex-wrap: wrap;
        gap: 7px;
        margin-bottom: 8px;
    }
    .chat-attachment-preview.has-files { display: flex; }
    .chat-attachment-chip {
        max-width: min(100%, 260px);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 8px;
        border-radius: 10px;
        border: 1px solid rgba(96,165,250,0.26);
        background: rgba(59,130,246,0.08);
        color: var(--tx);
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .chat-attachment-chip span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .chat-attachment-chip small {
        color: var(--tx3);
        font-size: 0.64rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .chat-attachment-remove {
        border: 0;
        background: transparent;
        color: var(--tx3);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        padding: 0;
        border-radius: 6px;
        flex: 0 0 auto;
    }
    .chat-attachment-remove:hover {
        background: rgba(239,68,68,0.12);
        color: #ef4444;
    }
    .chat-attachment-bubble {
        display: grid;
        gap: 5px;
        margin-top: 8px;
    }
    .chat-attachment-bubble-item {
        display: flex;
        align-items: center;
        gap: 7px;
        max-width: 100%;
        padding: 6px 8px;
        border-radius: 9px;
        background: rgba(255,255,255,0.16);
        color: inherit;
        font-size: 0.72rem;
        line-height: 1.2;
    }
    .chat-attachment-bubble-item span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .coach-disclaimer {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-align: center;
        margin-top: 6px;
        color: var(--tx3);
        font-size: 0.64rem;
        line-height: 1.25;
    }
    .coach-disclaimer i {
        color: #94a3b8;
        font-size: 0.78rem;
        flex: 0 0 auto;
    }

    .history-item { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; transition: 0.2s; color: var(--tx3); font-size: .9rem; display: flex; align-items: center; }
    .history-item:hover, .history-item.active { background: rgba(255,255,255,0.05); color: var(--tx); }
    .history-item i { margin-right: 12px; opacity: 0.7; }
    .coach-actions { position: relative; }
    .coach-actions-toggle {
        width: 36px;
        height: 36px;
        border: 1px solid transparent;
        border-radius: 10px;
        background: transparent;
        color: var(--tx3);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
    }
    .coach-actions-toggle:hover, .coach-actions-toggle[aria-expanded="true"] {
        background: rgba(255,255,255,0.06);
        border-color: var(--bd);
        color: var(--tx);
    }
    .coach-actions-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        z-index: 30;
        width: min(320px, calc(100vw - 28px));
        max-height: min(70dvh, 520px);
        padding: 6px;
        border: 1px solid var(--bd);
        border-radius: 12px;
        background: var(--sf);
        box-shadow: 0 18px 45px rgba(0,0,0,0.22);
        display: none;
        overflow-y: auto;
    }
    .coach-actions-menu.open { display: block; }
    .coach-actions-item {
        width: 100%;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--tx);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        font-size: .88rem;
        text-align: left;
        transition: 0.2s;
    }
    .coach-actions-item:hover { background: rgba(255,255,255,0.06); }
    .coach-actions-item i {
        width: 16px;
        text-align: center;
        color: var(--tx3);
    }
    .coach-actions-item.danger { color: #ef4444; }
    .coach-actions-item.danger i { color: #ef4444; }
    .coach-actions-divider {
        height: 1px;
        background: var(--bd);
        margin: 6px;
    }
    .coach-actions-heading {
        padding: 8px 10px 5px;
        color: var(--tx3);
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    .coach-actions-history {
        width: 100%;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--tx2);
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        text-align: left;
        transition: 0.2s;
    }
    .coach-actions-history:hover {
        background: rgba(255,255,255,0.06);
        color: var(--tx);
    }
    .coach-actions-history i {
        width: 16px;
        color: var(--tx3);
        text-align: center;
        flex: 0 0 16px;
    }
    .coach-actions-history span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.82rem;
    }
    .coach-actions-empty {
        padding: 8px 10px 10px;
        color: var(--tx3);
        font-size: 0.78rem;
    }

    /* Mobile-specific: full height chat within the mobile layout */
    @media (max-width: 767px) {
        #ai-coach-page {
            height: auto !important;
        }
        #ai-coach-page .sr-page-hero {
            min-height: 86px;
            max-width: 460px;
            margin: 0 auto 10px;
            border-radius: 16px;
        }
        #ai-coach-page .sr-page-hero-inner {
            min-height: 86px;
            padding: 11px 92px 11px 13px;
        }
        #ai-coach-page .sr-page-hero-title {
            font-size: clamp(0.94rem, 5.2vw, 1.08rem) !important;
            gap: 7px;
            line-height: 1.14;
        }
        #ai-coach-page .sr-page-hero-title svg {
            width: 19px;
            height: 19px;
        }
        #ai-coach-page .sr-page-hero-subtitle {
            font-size: 0.69rem;
            line-height: 1.35;
        }
        #ai-coach-page .sr-page-hero-art {
            width: 96px;
            right: -4px;
            bottom: -2px;
        }
        #mob-content #ai-coach-page .chat-container,
        .chat-container {
            width: min(100%, 460px);
            height: calc(100dvh - var(--mob-top-h, 56px) - var(--mob-nav-h, 78px) - 116px) !important;
            min-height: 340px;
            border-radius: 16px !important;
            flex-direction: column !important;
        }
        .chat-sidebar { display: none !important; }
        .coach-chat-header {
            padding: 12px 14px !important;
        }
        .coach-chat-title {
            font-size: 0.82rem;
            max-width: 210px;
        }
        .coach-status { font-size: 0.82rem; margin-top: 6px; }
        #mob-content #ai-coach-page .chat-messages,
        .chat-messages { padding: 12px !important; gap: 10px !important; }
        .coach-msg-row { gap: 10px; }
        .coach-avatar,
        .coach-user-avatar { width: 32px; height: 32px; border-radius: 9px; }
        #mob-content #ai-coach-page .chat-input-area,
        .chat-input-area { padding: 8px 10px 8px !important; }
        #mob-content #ai-coach-page .chat-bubble,
        .chat-bubble { max-width: calc(100% - 42px) !important; padding: 11px 13px !important; font-size: 0.84rem !important; line-height: 1.48 !important; border-radius: 16px !important; }
        .ai-response { gap: 8px; line-height: 1.55; }
        #mob-content #ai-coach-page .chat-input-wrapper,
        .chat-input-wrapper {
            padding: 7px 8px 7px 12px !important;
            border-radius: 13px !important;
        }
        .chat-textarea {
            height: 20px;
            font-size: 0.66rem;
            line-height: 1.25;
        }
        #mob-content #ai-coach-page .chat-send-btn,
        .chat-send-btn {
            width: 36px !important;
            height: 36px !important;
            border-radius: 12px !important;
            margin-left: 8px !important;
            margin-bottom: 0 !important;
        }
        .chat-attachment-btn {
            width: 34px;
            height: 34px;
            margin-right: 6px;
        }
        .coach-disclaimer {
            margin-top: 5px;
            font-size: 0.58rem;
            padding: 0 2px;
        }
    }

    @media (max-width: 390px) {
        #ai-coach-page .sr-page-hero-inner {
            padding-right: 78px;
        }
        #ai-coach-page .sr-page-hero-art {
            width: 84px;
        }
        .coach-chat-title {
            font-size: 0.76rem;
            max-width: 190px;
        }
        #mob-content #ai-coach-page .chat-container,
        .chat-container {
            min-height: 330px !important;
        }
        #mob-content #ai-coach-page .chat-bubble,
        .chat-bubble {
            font-size: 0.8rem !important;
        }
    }
    
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
    /* Compact hero size shared with Progress, Setup, and Feedback. */
    #ai-coach-page .sr-page-hero.coach-progress-hero {
        grid-template-columns: 30px minmax(0, 1fr) !important;
        gap: 8px !important;
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin-bottom: 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
    }
    #ai-coach-page .coach-hero-icon {
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-title {
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin: 0 0 3px !important;
        white-space: nowrap !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
        max-width: 13.5rem !important;
        max-height: none !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-art {
        width: 72px !important;
        right: -5px !important;
        bottom: -2px !important;
    }
    @media (max-width: 390px) {
        #ai-coach-page .sr-page-hero.coach-progress-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }
        #ai-coach-page .coach-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            font-size: 0.46rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            width: 66px !important;
        }
    }
    /* Final compact hero override after all Coach breakpoints. */
    #ai-coach-page .sr-page-hero.coach-progress-hero {
        grid-template-columns: 30px minmax(0, 1fr) !important;
        gap: 8px !important;
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin: 0 0 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
    }
    #ai-coach-page .coach-hero-icon {
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-title {
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin: 0 0 3px !important;
        white-space: nowrap !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
        max-width: 13.5rem !important;
        max-height: none !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-art {
        width: 72px !important;
        right: -5px !important;
        bottom: -2px !important;
    }
    @media (max-width: 390px) {
        #ai-coach-page .sr-page-hero.coach-progress-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }
        #ai-coach-page .coach-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            font-size: 0.46rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            width: 66px !important;
        }
    }
    /* Final compact hero override after all Coach breakpoints. */
    #ai-coach-page .sr-page-hero.coach-progress-hero {
        grid-template-columns: 30px minmax(0, 1fr) !important;
        gap: 8px !important;
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin: 0 0 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
    }
    #ai-coach-page .coach-hero-icon {
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-title {
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin: 0 0 3px !important;
        white-space: nowrap !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
        max-width: 13.5rem !important;
        max-height: none !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-art {
        width: 72px !important;
        right: -5px !important;
        bottom: -2px !important;
    }
    @media (max-width: 390px) {
        #ai-coach-page .sr-page-hero.coach-progress-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }
        #ai-coach-page .coach-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            font-size: 0.46rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            width: 66px !important;
        }
    }

    /* SaaSPro mobile polish for Coach. */
    @media (max-width: 767px) {
        body #mob-content {
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.08) 0, rgba(20, 184, 166, 0.035) 260px, transparent 520px),
                var(--bg) !important;
        }

        body #mob-content > .db-content {
            padding: 12px 12px 18px !important;
        }

        html body #ai-coach-page {
            --coach-pro-panel: rgba(255, 255, 255, 0.98);
            --coach-pro-field: rgba(255, 255, 255, 0.96);
            --coach-pro-soft: #f8fafc;
            --coach-pro-border: rgba(15, 23, 42, 0.1);
            --coach-pro-title: #0f172a;
            --coach-pro-text: #334155;
            --coach-pro-muted: #64748b;
            --coach-pro-accent: #2563eb;
            --coach-pro-accent-2: #0891b2;
            --coach-pro-success: #059669;
            --coach-pro-danger: #dc2626;
            --coach-pro-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 12px 28px rgba(15, 23, 42, 0.07);
            display: flex !important;
            flex-direction: column !important;
            gap: 10px !important;
            max-width: 520px;
            height: auto !important;
            margin: 0 auto !important;
            padding: 0 0 16px !important;
            color: var(--coach-pro-title) !important;
        }

        html[data-theme="dark"] body #ai-coach-page,
        :root:not(.lm) body #ai-coach-page,
        body.dm #ai-coach-page,
        .dm #ai-coach-page {
            --coach-pro-panel: rgba(15, 23, 42, 0.94);
            --coach-pro-field: rgba(30, 41, 59, 0.9);
            --coach-pro-soft: rgba(51, 65, 85, 0.78);
            --coach-pro-border: rgba(148, 163, 184, 0.24);
            --coach-pro-title: #f8fafc;
            --coach-pro-text: #e2e8f0;
            --coach-pro-muted: #cbd5e1;
            --coach-pro-accent: #93c5fd;
            --coach-pro-accent-2: #67e8f9;
            --coach-pro-success: #86efac;
            --coach-pro-danger: #fca5a5;
            --coach-pro-shadow: 0 1px 0 rgba(148, 163, 184, 0.08), 0 18px 36px rgba(0, 0, 0, 0.26);
        }

        html body #ai-coach-page .sr-page-hero.coach-progress-hero {
            display: grid !important;
            grid-template-columns: 30px minmax(0, 1fr) !important;
            align-items: center !important;
            gap: 8px !important;
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

        html[data-theme="dark"] body #ai-coach-page .sr-page-hero.coach-progress-hero,
        :root:not(.lm) body #ai-coach-page .sr-page-hero.coach-progress-hero,
        body.dm #ai-coach-page .sr-page-hero.coach-progress-hero,
        .dm #ai-coach-page .sr-page-hero.coach-progress-hero {
            background:
                linear-gradient(115deg, rgba(30, 64, 175, 0.96), rgba(15, 118, 110, 0.9)),
                #1e3a8a !important;
            box-shadow: 0 18px 34px rgba(0, 0, 0, 0.3) !important;
        }

        html body #ai-coach-page .sr-page-hero.coach-progress-hero::before {
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

        html body #ai-coach-page .sr-page-hero.coach-progress-hero::after {
            display: none !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-inner,
        html body #ai-coach-page .coach-progress-hero .sr-page-hero-copy {
            display: contents !important;
            min-height: 0 !important;
            padding: 0 !important;
        }

        html body #ai-coach-page .coach-hero-icon {
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

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-copy > div:last-child {
            min-width: 0;
            position: relative;
            z-index: 1;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-title {
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

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-title svg {
            display: none !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
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

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            display: block !important;
            width: 72px !important;
            height: auto !important;
            right: -5px !important;
            bottom: -2px !important;
            opacity: 0.98 !important;
            filter: drop-shadow(0 10px 16px rgba(15, 23, 42, 0.22));
            pointer-events: none;
        }

        html body #mob-content #ai-coach-page .chat-container,
        html body #ai-coach-page .chat-container {
            width: 100% !important;
            height: calc(100dvh - var(--mob-top-h, 56px) - var(--mob-nav-h, 78px) - 108px) !important;
            min-height: 360px !important;
            max-height: 680px !important;
            margin: 0 !important;
            border: 1px solid var(--coach-pro-border) !important;
            border-radius: 8px !important;
            background: var(--coach-pro-panel) !important;
            box-shadow: var(--coach-pro-shadow) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            overflow: hidden !important;
            flex-direction: column !important;
        }

        html body #ai-coach-page .chat-sidebar {
            display: none !important;
        }

        html body #ai-coach-page .chat-main {
            min-height: 0 !important;
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.04), transparent 120px),
                var(--coach-pro-panel) !important;
        }

        html body #ai-coach-page .coach-chat-header {
            min-height: 44px !important;
            padding: 7px 8px !important;
            border-color: var(--coach-pro-border) !important;
            background: var(--coach-pro-panel) !important;
            gap: 8px !important;
        }

        html body #ai-coach-page .coach-status {
            min-height: 28px !important;
            margin-top: 0 !important;
            padding: 5px 8px !important;
            border: 1px solid rgba(16, 185, 129, 0.22) !important;
            border-radius: 8px !important;
            background: rgba(16, 185, 129, 0.1) !important;
            color: var(--coach-pro-success) !important;
            font-size: 0.62rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
            white-space: nowrap;
        }

        html body #ai-coach-page .coach-status::before {
            width: 7px !important;
            height: 7px !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.13) !important;
        }

        html body #ai-coach-page .coach-actions {
            position: relative;
        }

        html body #ai-coach-page .coach-actions-toggle {
            width: 32px !important;
            height: 32px !important;
            border: 1px solid var(--coach-pro-border) !important;
            border-radius: 8px !important;
            background: var(--coach-pro-field) !important;
            color: var(--coach-pro-title) !important;
            box-shadow: none !important;
        }

        html body #ai-coach-page .coach-actions-toggle:hover,
        html body #ai-coach-page .coach-actions-toggle[aria-expanded="true"] {
            background: var(--coach-pro-soft) !important;
            color: var(--coach-pro-accent) !important;
        }

        html body #ai-coach-page .coach-actions-menu {
            top: calc(100% + 6px) !important;
            right: 0 !important;
            width: min(312px, calc(100vw - 24px)) !important;
            max-height: min(62dvh, 430px) !important;
            padding: 6px !important;
            border: 1px solid var(--coach-pro-border) !important;
            border-radius: 8px !important;
            background: var(--coach-pro-panel) !important;
            box-shadow: var(--coach-pro-shadow) !important;
            color: var(--coach-pro-title) !important;
        }

        html body #ai-coach-page .coach-actions-heading {
            padding: 7px 8px 4px !important;
            color: var(--coach-pro-muted) !important;
            font-size: 0.56rem !important;
            font-weight: 900 !important;
            line-height: 1.1 !important;
        }

        html body #ai-coach-page .coach-actions-divider {
            background: var(--coach-pro-border) !important;
            margin: 5px !important;
        }

        html body #ai-coach-page .coach-actions-item,
        html body #ai-coach-page .coach-actions-history {
            min-height: 34px !important;
            padding: 8px 9px !important;
            border-radius: 8px !important;
            color: var(--coach-pro-title) !important;
            background: transparent !important;
            font-size: 0.7rem !important;
            line-height: 1.15 !important;
        }

        html body #ai-coach-page .coach-actions-item:hover,
        html body #ai-coach-page .coach-actions-history:hover {
            background: var(--coach-pro-soft) !important;
        }

        html body #ai-coach-page .coach-actions-item i,
        html body #ai-coach-page .coach-actions-history i {
            color: var(--coach-pro-accent) !important;
        }

        html body #ai-coach-page .coach-actions-item.danger,
        html body #ai-coach-page .coach-actions-item.danger i {
            color: var(--coach-pro-danger) !important;
        }

        html body #ai-coach-page .coach-actions-history span {
            color: inherit !important;
            font-size: 0.7rem !important;
        }

        html body #ai-coach-page .coach-actions-empty {
            padding: 8px 9px !important;
            color: var(--coach-pro-muted) !important;
            font-size: 0.66rem !important;
        }

        html body #mob-content #ai-coach-page .chat-messages,
        html body #ai-coach-page .chat-messages {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            padding: 9px !important;
            gap: 8px !important;
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.035), transparent 80px),
                var(--coach-pro-panel) !important;
            scrollbar-width: thin;
        }

        html body #ai-coach-page #chatDateBadge {
            margin: 0 0 4px !important;
        }

        html body #ai-coach-page #chatDateBadge .db-badge {
            min-height: 22px !important;
            padding: 4px 8px !important;
            border: 1px solid var(--coach-pro-border) !important;
            border-radius: 8px !important;
            background: var(--coach-pro-soft) !important;
            color: var(--coach-pro-muted) !important;
            font-size: 0.56rem !important;
            font-weight: 900 !important;
        }

        html body #ai-coach-page .coach-msg-row,
        html body #ai-coach-page .dynamic-msg {
            align-items: flex-end !important;
            gap: 8px !important;
            margin-top: 8px !important;
        }

        html body #ai-coach-page #welcomeMsg {
            margin-top: 0 !important;
        }

        html body #ai-coach-page .coach-avatar,
        html body #ai-coach-page .coach-user-avatar {
            width: 28px !important;
            height: 28px !important;
            min-width: 28px !important;
            border-radius: 8px !important;
            font-size: 0.72rem !important;
        }

        html body #ai-coach-page .coach-avatar {
            border: 1px solid rgba(37, 99, 235, 0.18) !important;
            background: linear-gradient(135deg, var(--coach-pro-accent), var(--coach-pro-accent-2)) !important;
            color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18) !important;
        }

        html body #ai-coach-page .coach-user-avatar {
            border: 1px solid var(--coach-pro-border) !important;
            background: var(--coach-pro-soft) !important;
            color: var(--coach-pro-title) !important;
            font-weight: 900 !important;
        }

        html body #ai-coach-page .coach-user-avatar img {
            border-radius: 8px !important;
        }

        html body #ai-coach-page .coach-user-avatar-img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: inherit !important;
        }

        html body #mob-content #ai-coach-page .chat-bubble,
        html body #ai-coach-page .chat-bubble {
            max-width: calc(100% - 36px) !important;
            padding: 9px 10px !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            font-size: 0.74rem !important;
            line-height: 1.42 !important;
            overflow-wrap: anywhere !important;
        }

        html body #ai-coach-page .bubble-ai {
            border: 1px solid var(--coach-pro-border) !important;
            border-bottom-left-radius: 3px !important;
            background: var(--coach-pro-field) !important;
            color: var(--coach-pro-text) !important;
        }

        html body #ai-coach-page .bubble-user {
            border: 0 !important;
            border-bottom-right-radius: 3px !important;
            background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
            color: #ffffff !important;
        }

        html body #ai-coach-page .ai-response {
            display: grid !important;
            gap: 7px !important;
            color: var(--coach-pro-text) !important;
            line-height: 1.45 !important;
        }

        html body #ai-coach-page .ai-response :is(p, li) {
            color: var(--coach-pro-text) !important;
        }

        html body #ai-coach-page .ai-response strong {
            color: var(--coach-pro-title) !important;
        }

        html body #ai-coach-page .ai-response em {
            color: var(--coach-pro-muted) !important;
        }

        html body #ai-coach-page .ai-response .ai-section-title {
            color: var(--coach-pro-accent) !important;
            font-size: 0.62rem !important;
            line-height: 1.2 !important;
        }

        html body #ai-coach-page .ai-response :is(ul, ol) {
            gap: 5px !important;
            padding-left: 1rem !important;
        }

        html body #ai-coach-page .ai-response code {
            border: 1px solid var(--coach-pro-border) !important;
            background: var(--coach-pro-soft) !important;
            color: var(--coach-pro-title) !important;
        }

        html body #ai-coach-page #typingIndicator .chat-bubble {
            min-height: 34px !important;
            padding: 9px 10px !important;
        }

        html body #ai-coach-page #typingIndicator [style*="width:8px"] {
            width: 6px !important;
            height: 6px !important;
            background: var(--coach-pro-accent) !important;
            opacity: 0.7;
            animation-name: coachTypingPulse !important;
        }

        html body #mob-content #ai-coach-page .chat-input-area,
        html body #ai-coach-page .chat-input-area {
            flex: 0 0 auto !important;
            padding: 8px 8px 7px !important;
            border-color: var(--coach-pro-border) !important;
            background: var(--coach-pro-panel) !important;
        }

        html body #mob-content #ai-coach-page .chat-input-wrapper,
        html body #ai-coach-page .chat-input-wrapper {
            min-height: 42px !important;
            padding: 6px 6px 6px 10px !important;
            border: 1px solid var(--coach-pro-border) !important;
            border-radius: 8px !important;
            background: var(--coach-pro-field) !important;
            box-shadow: none !important;
        }

        html body #ai-coach-page .chat-input-wrapper:focus-within {
            border-color: rgba(37, 99, 235, 0.54) !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
            background: var(--coach-pro-panel) !important;
        }

        html body #ai-coach-page .chat-textarea {
            height: 22px !important;
            max-height: 86px !important;
            padding: 3px 0 !important;
            color: var(--coach-pro-title) !important;
            font-size: 0.72rem !important;
            font-weight: 700 !important;
            line-height: 1.25 !important;
        }

        html body #ai-coach-page .chat-textarea::placeholder {
            color: var(--coach-pro-muted) !important;
            font-weight: 400 !important;
            opacity: 1;
        }

        html body #mob-content #ai-coach-page .chat-send-btn,
        html body #ai-coach-page .chat-send-btn {
            width: 32px !important;
            height: 32px !important;
            min-width: 32px !important;
            border-radius: 8px !important;
            margin: 0 0 0 7px !important;
            background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
            color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22) !important;
        }

        html body #ai-coach-page .chat-send-btn:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.26) !important;
        }

        html body #ai-coach-page .coach-disclaimer {
            margin-top: 5px !important;
            padding: 0 2px !important;
            color: var(--coach-pro-muted) !important;
            font-size: 0.54rem !important;
            line-height: 1.18 !important;
            gap: 5px !important;
        }

        html body #ai-coach-page .coach-disclaimer i {
            color: var(--coach-pro-accent) !important;
            font-size: 0.64rem !important;
        }
    }

    @media (max-width: 390px) {
        html body #ai-coach-page .sr-page-hero.coach-progress-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            padding: 8px 66px 8px 10px !important;
        }

        html body #ai-coach-page .coach-hero-icon {
            width: 26px !important;
            height: 26px !important;
            min-width: 26px !important;
            font-size: 0.7rem !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            max-width: 10.8rem !important;
            font-size: 0.46rem !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            width: 66px !important;
            right: -6px !important;
        }

        html body #mob-content #ai-coach-page .chat-container,
        html body #ai-coach-page .chat-container {
            height: calc(100dvh - var(--mob-top-h, 56px) - var(--mob-nav-h, 78px) - 104px) !important;
            min-height: 350px !important;
        }
    }

    @media (max-width: 360px) {
        html body #mob-content #ai-coach-page .chat-container,
        html body #ai-coach-page .chat-container {
            min-height: 330px !important;
        }

        html body #ai-coach-page .coach-actions-menu {
            width: min(296px, calc(100vw - 20px)) !important;
        }

        html body #mob-content #ai-coach-page .chat-bubble,
        html body #ai-coach-page .chat-bubble {
            font-size: 0.7rem !important;
        }

        html body #ai-coach-page .coach-disclaimer {
            font-size: 0.5rem !important;
        }
    }

    @keyframes coachTypingPulse {
        0%, 100% {
            opacity: 0.35;
            transform: translateY(0);
        }
        50% {
            opacity: 1;
            transform: translateY(-2px);
        }
    }
</style>
@include('partials.page-hero-styles')
<style>
    #ai-coach-page .sr-page-hero.coach-progress-hero {
        --coach-hero-title: #1d4ed8;
        --coach-hero-text: #334155;
        --coach-icon-bg: rgba(239, 246, 255, 0.92);
        --coach-icon-border: rgba(147, 197, 253, 0.42);
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
    :root:not(.lm) #ai-coach-page .sr-page-hero.coach-progress-hero,
    .dm #ai-coach-page .sr-page-hero.coach-progress-hero {
        --coach-hero-title: #93c5fd;
        --coach-hero-text: #e2e8f0;
        --coach-icon-bg: rgba(59, 130, 246, 0.2);
        --coach-icon-border: rgba(147, 197, 253, 0.32);
        background:
            radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
            linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
        border-color: rgba(147, 197, 253, 0.28);
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-inner,
    #ai-coach-page .coach-progress-hero .sr-page-hero-copy {
        display: contents !important;
        min-height: 0 !important;
        padding: 0 !important;
    }
    #ai-coach-page .coach-hero-icon {
        box-sizing: border-box;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 34px !important;
        height: 34px !important;
        border: 1px solid var(--coach-icon-border) !important;
        border-radius: 10px !important;
        background: var(--coach-icon-bg) !important;
        color: var(--coach-hero-title) !important;
        font-size: 0.9rem !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-title {
        display: block !important;
        color: var(--coach-hero-title) !important;
        background: none !important;
        -webkit-text-fill-color: var(--coach-hero-title) !important;
        font-size: 1rem !important;
        line-height: 1.08 !important;
        margin: 0 0 4px !important;
        font-weight: 950 !important;
        text-transform: uppercase;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-title svg {
        display: none;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
        color: var(--coach-hero-text) !important;
        font-size: 0.66rem !important;
        line-height: 1.32;
        max-width: 14rem;
        margin: 0;
        font-weight: 500;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-art {
        display: block;
        width: 62px;
        right: 8px;
        bottom: 4px;
        opacity: 0.92;
        filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16));
    }
    @media (max-width: 767px) {
        #ai-coach-page .sr-page-hero.coach-progress-hero {
            min-height: 74px !important;
            grid-template-columns: 36px minmax(0, 1fr) !important;
            gap: 9px !important;
            max-width: none;
            padding: 8px 64px 8px 12px !important;
            margin: 0 0 12px;
        }
        #ai-coach-page .coach-hero-icon {
            width: 32px !important;
            height: 32px !important;
            font-size: 0.82rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-title {
            font-size: 0.88rem !important;
            line-height: 1.08;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            font-size: 0.64rem !important;
            max-width: 12.5rem;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            width: 56px;
            right: -4px;
            bottom: 5px;
        }
    }
    @media (max-width: 390px) {
        #ai-coach-page .sr-page-hero.coach-progress-hero {
            padding-right: 58px !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-title {
            font-size: 0.82rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            font-size: 0.59rem !important;
            max-width: 10.6rem;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            width: 48px;
        }
    }

    @media (max-width: 991px) {
        #ai-coach-page {
            --coach-saas-radius: 12px;
            --coach-saas-gap: 8px;
            --coach-saas-border: rgba(37, 99, 235, 0.14);
            --coach-saas-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            --coach-saas-card: rgba(248, 250, 252, 0.78);
            --coach-saas-muted: #475569;
            display: flex;
            flex-direction: column;
            gap: var(--coach-saas-gap);
            height: auto !important;
            padding-inline: 0 !important;
            padding-bottom: 10px !important;
        }
        html[data-theme="dark"] #ai-coach-page,
        :root:not(.lm) #ai-coach-page,
        .dm #ai-coach-page {
            --coach-saas-border: rgba(147, 197, 253, 0.18);
            --coach-saas-shadow: 0 12px 26px rgba(0, 0, 0, 0.26);
            --coach-saas-card: rgba(255, 255, 255, 0.045);
            --coach-saas-muted: #cbd5e1;
        }
        #ai-coach-page .sr-page-hero.coach-progress-hero {
            min-height: 84px !important;
            grid-template-columns: 34px minmax(0, 1fr) !important;
            gap: 9px !important;
            padding: 10px 72px 11px 12px !important;
            margin: 0 !important;
            border-radius: var(--coach-saas-radius) !important;
            border-color: var(--coach-saas-border) !important;
            box-shadow: var(--coach-saas-shadow) !important;
            overflow: hidden !important;
        }
        #ai-coach-page .coach-hero-icon {
            width: 32px !important;
            height: 32px !important;
            border-radius: 10px !important;
            font-size: 0.82rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-title {
            font-size: 0.86rem !important;
            line-height: 1.12 !important;
            margin-bottom: 4px !important;
            white-space: normal !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            max-width: 12rem !important;
            max-height: 2.7em !important;
            overflow: hidden !important;
            font-size: 0.62rem !important;
            line-height: 1.34 !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            width: 58px !important;
            right: -4px !important;
            bottom: 6px !important;
        }
        #mob-content #ai-coach-page .chat-container,
        #ai-coach-page .chat-container {
            width: 100% !important;
            height: calc(100dvh - var(--mob-top-h, 56px) - var(--mob-nav-h, 78px) - 104px) !important;
            min-height: 430px !important;
            max-height: none !important;
            margin: 0 !important;
            border-radius: var(--coach-saas-radius) !important;
            border-color: var(--coach-saas-border) !important;
            background: var(--sf) !important;
            box-shadow: var(--coach-saas-shadow) !important;
            overflow: hidden !important;
        }
        #ai-coach-page .chat-sidebar {
            display: none !important;
        }
        #ai-coach-page .chat-main {
            min-height: 0;
        }
        #ai-coach-page .coach-chat-header {
            min-height: 46px;
            padding: 8px 10px !important;
            border-color: var(--coach-saas-border) !important;
            background: color-mix(in srgb, var(--sf) 90%, transparent);
        }
        #ai-coach-page .coach-status {
            min-height: 28px;
            margin-top: 0 !important;
            padding: 5px 8px;
            border: 1px solid rgba(16, 185, 129, 0.16);
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.08);
            font-size: 0.66rem !important;
            font-weight: 900;
        }
        #ai-coach-page .coach-status::before {
            width: 7px;
            height: 7px;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.12);
        }
        #ai-coach-page .coach-actions-toggle {
            width: 34px !important;
            height: 34px !important;
            border-radius: 10px !important;
            border-color: var(--coach-saas-border) !important;
            background: var(--coach-saas-card) !important;
        }
        #ai-coach-page .coach-actions-menu {
            top: calc(100% + 6px);
            right: 0;
            width: min(318px, calc(100vw - 24px));
            max-height: min(62dvh, 430px);
            padding: 6px;
            border-radius: 12px;
            border-color: var(--coach-saas-border);
            box-shadow: var(--coach-saas-shadow);
        }
        #ai-coach-page .coach-actions-item,
        #ai-coach-page .coach-actions-history {
            min-height: 34px;
            padding: 8px 9px;
            border-radius: 8px;
            font-size: 0.72rem;
        }
        #ai-coach-page .coach-actions-heading {
            padding: 7px 8px 4px;
            font-size: 0.58rem;
        }
        #mob-content #ai-coach-page .chat-messages,
        #ai-coach-page .chat-messages {
            padding: 10px !important;
            gap: 8px !important;
        }
        #ai-coach-page #chatDateBadge {
            margin-bottom: 4px !important;
        }
        #ai-coach-page .coach-msg-row,
        #ai-coach-page .dynamic-msg {
            gap: 8px !important;
            margin-top: 8px !important;
        }
        #ai-coach-page .coach-avatar,
        #ai-coach-page .coach-user-avatar {
            width: 30px !important;
            height: 30px !important;
            border-radius: 9px !important;
            font-size: 0.78rem !important;
        }
        #mob-content #ai-coach-page .chat-bubble,
        #ai-coach-page .chat-bubble {
            max-width: calc(100% - 38px) !important;
            padding: 10px 11px !important;
            border-radius: 12px !important;
            font-size: 0.78rem !important;
            line-height: 1.42 !important;
            box-shadow: none !important;
        }
        #ai-coach-page .bubble-ai {
            background: var(--coach-saas-card) !important;
            border-color: var(--coach-saas-border) !important;
        }
        #ai-coach-page .bubble-user {
            background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
        }
        #ai-coach-page .ai-response {
            gap: 7px;
            line-height: 1.46;
        }
        #ai-coach-page .ai-response .ai-section-title {
            font-size: 0.64rem;
        }
        #ai-coach-page .ai-response :is(ul, ol) {
            gap: 5px;
            padding-left: 1rem;
        }
        #mob-content #ai-coach-page .chat-input-area,
        #ai-coach-page .chat-input-area {
            padding: 8px 9px 7px !important;
            border-color: var(--coach-saas-border) !important;
            background: color-mix(in srgb, var(--sf) 92%, transparent);
        }
        #mob-content #ai-coach-page .chat-input-wrapper,
        #ai-coach-page .chat-input-wrapper {
            min-height: 44px;
            padding: 7px 7px 7px 10px !important;
            border-radius: 12px !important;
            border-color: var(--coach-saas-border) !important;
            background: var(--coach-saas-card) !important;
            box-shadow: none !important;
        }
        #ai-coach-page .chat-textarea {
            height: 22px;
            max-height: 86px;
            font-size: 0.72rem !important;
            line-height: 1.25 !important;
        }
        #mob-content #ai-coach-page .chat-send-btn,
        #ai-coach-page .chat-send-btn {
            width: 34px !important;
            height: 34px !important;
            border-radius: 10px !important;
            margin-left: 7px !important;
            box-shadow: none !important;
        }
        #ai-coach-page .coach-disclaimer {
            margin-top: 5px !important;
            gap: 5px;
            font-size: 0.56rem !important;
            line-height: 1.18 !important;
            color: var(--coach-saas-muted) !important;
        }
    }
    /* Final compact hero override after all Coach breakpoints. */
    #ai-coach-page .sr-page-hero.coach-progress-hero {
        grid-template-columns: 30px minmax(0, 1fr) !important;
        gap: 8px !important;
        min-height: 69px !important;
        padding: 8px 72px 8px 10px !important;
        margin: 0 0 10px !important;
        border-radius: 8px !important;
        box-shadow: 0 5px 14px rgba(37, 99, 235, 0.1) !important;
    }
    #ai-coach-page .coach-hero-icon {
        width: 28px !important;
        height: 28px !important;
        border-radius: 8px !important;
        font-size: 0.8rem !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-title {
        font-size: 0.72rem !important;
        line-height: 1.15 !important;
        margin: 0 0 3px !important;
        white-space: nowrap !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
        max-width: 13.5rem !important;
        max-height: none !important;
        font-size: 0.49rem !important;
        line-height: 1.32 !important;
    }
    #ai-coach-page .coach-progress-hero .sr-page-hero-art {
        width: 72px !important;
        right: -5px !important;
        bottom: -2px !important;
    }
    @media (max-width: 390px) {
        #ai-coach-page .sr-page-hero.coach-progress-hero {
            grid-template-columns: 28px minmax(0, 1fr) !important;
            gap: 7px !important;
            padding: 8px 66px 8px 9px !important;
        }
        #ai-coach-page .coach-hero-icon {
            width: 27px !important;
            height: 27px !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-title {
            font-size: 0.68rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            font-size: 0.46rem !important;
        }
        #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            width: 66px !important;
        }
    }

    @media (min-width: 992px) {
        html body #ai-coach-page {
            --coach-desktop-radius: 12px;
            --coach-desktop-gap: 12px;
            --coach-desktop-shell-height: calc(100vh - var(--nav, 70px) - clamp(36px, 4.8vw, 64px));
            --coach-desktop-border: rgba(15, 23, 42, 0.12);
            --coach-desktop-card: rgba(255, 255, 255, 0.98);
            --coach-desktop-field: rgba(248, 250, 252, 0.92);
            --coach-desktop-soft: #f8fafc;
            --coach-desktop-title: #0f172a;
            --coach-desktop-text: #334155;
            --coach-desktop-muted: #64748b;
            --coach-desktop-accent: #2563eb;
            --coach-desktop-accent-2: #0891b2;
            --coach-desktop-success: #059669;
            --coach-desktop-danger: #dc2626;
            --coach-desktop-shadow: 0 10px 24px rgba(15, 23, 42, 0.07);
            width: 100% !important;
            max-width: none !important;
            height: var(--coach-desktop-shell-height) !important;
            min-height: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: var(--coach-desktop-gap) !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
            color: var(--coach-desktop-title) !important;
        }

        html body.user-desktop-shell:has(#ai-coach-page),
        html body.user-desktop-shell:has(#ai-coach-page) .db-main {
            overflow: hidden !important;
        }

        html body.user-desktop-shell #userAppContent:has(#ai-coach-page) {
            height: calc(100vh - var(--nav, 70px)) !important;
            overflow: hidden !important;
        }

        html[data-theme="dark"] body #ai-coach-page,
        :root:not(.lm) body #ai-coach-page,
        body.dm #ai-coach-page,
        .dm #ai-coach-page {
            --coach-desktop-border: rgba(148, 163, 184, 0.24);
            --coach-desktop-card: rgba(15, 23, 42, 0.96);
            --coach-desktop-field: rgba(30, 41, 59, 0.92);
            --coach-desktop-soft: rgba(51, 65, 85, 0.78);
            --coach-desktop-title: #f8fafc;
            --coach-desktop-text: #e2e8f0;
            --coach-desktop-muted: #cbd5e1;
            --coach-desktop-accent: #93c5fd;
            --coach-desktop-accent-2: #67e8f9;
            --coach-desktop-success: #86efac;
            --coach-desktop-danger: #fca5a5;
            --coach-desktop-shadow: 0 14px 30px rgba(0, 0, 0, 0.24);
        }

        html body #ai-coach-page .sr-page-hero.coach-progress-hero {
            flex: 0 0 auto !important;
            position: relative !important;
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) 180px !important;
            align-items: center !important;
            min-height: 116px !important;
            max-height: none !important;
            height: auto !important;
            gap: 14px !important;
            margin: 0 !important;
            padding: 18px 178px 18px 20px !important;
            border-radius: var(--coach-desktop-radius) !important;
            border-color: rgba(191, 219, 254, 0.86) !important;
            background:
                radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.12), transparent 35%),
                linear-gradient(142deg, rgba(255,255,255,0.98) 0%, rgba(248,250,252,0.96) 62%, rgba(239,246,255,0.92) 100%) !important;
            box-shadow: 0 10px 26px rgba(37, 99, 235, 0.12) !important;
            overflow: hidden !important;
        }

        html[data-theme="dark"] body #ai-coach-page .sr-page-hero.coach-progress-hero,
        :root:not(.lm) body #ai-coach-page .sr-page-hero.coach-progress-hero,
        body.dm #ai-coach-page .sr-page-hero.coach-progress-hero,
        .dm #ai-coach-page .sr-page-hero.coach-progress-hero {
            background:
                radial-gradient(circle at 86% 18%, rgba(37, 99, 235, 0.26), transparent 35%),
                linear-gradient(142deg, #0f172a 0%, #111827 58%, #1e293b 100%) !important;
            border-color: rgba(147, 197, 253, 0.28) !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-inner,
        html body #ai-coach-page .coach-progress-hero .sr-page-hero-copy {
            display: flex !important;
            align-items: center !important;
            min-height: 0 !important;
            padding: 0 !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-copy {
            gap: 12px !important;
            max-width: 780px !important;
        }

        html body #ai-coach-page .coach-hero-icon {
            width: 44px !important;
            height: 44px !important;
            min-width: 44px !important;
            flex: 0 0 44px !important;
            border-radius: 12px !important;
            border: 1px solid rgba(147, 197, 253, 0.42) !important;
            background: rgba(239, 246, 255, 0.92) !important;
            color: #1d4ed8 !important;
            -webkit-text-fill-color: #1d4ed8 !important;
            font-size: 1.05rem !important;
            box-shadow: none !important;
        }

        html[data-theme="dark"] body #ai-coach-page .coach-hero-icon,
        :root:not(.lm) body #ai-coach-page .coach-hero-icon,
        body.dm #ai-coach-page .coach-hero-icon,
        .dm #ai-coach-page .coach-hero-icon {
            border-color: rgba(147, 197, 253, 0.32) !important;
            background: rgba(59, 130, 246, 0.2) !important;
            color: #93c5fd !important;
            -webkit-text-fill-color: #93c5fd !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-title {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            margin: 0 0 5px !important;
            color: var(--coach-desktop-title) !important;
            -webkit-text-fill-color: var(--coach-desktop-title) !important;
            background: none !important;
            font-size: clamp(1.12rem, 1.08vw, 1.45rem) !important;
            line-height: 1.12 !important;
            font-weight: 900 !important;
            text-transform: none !important;
            white-space: normal !important;
            overflow: visible !important;
            text-overflow: clip !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-title svg {
            display: none !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            display: block !important;
            max-width: 640px !important;
            max-height: none !important;
            color: #334155 !important;
            -webkit-text-fill-color: #334155 !important;
            font-size: 0.84rem !important;
            line-height: 1.42 !important;
            font-weight: 600 !important;
            overflow: visible !important;
        }

        html[data-theme="dark"] body #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle,
        :root:not(.lm) body #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle,
        body.dm #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle,
        .dm #ai-coach-page .coach-progress-hero .sr-page-hero-subtitle {
            color: #e2e8f0 !important;
            -webkit-text-fill-color: #e2e8f0 !important;
        }

        html body #ai-coach-page .coach-progress-hero .sr-page-hero-art {
            display: block !important;
            position: absolute !important;
            right: 12px !important;
            bottom: -10px !important;
            width: clamp(140px, 12vw, 174px) !important;
            max-width: none !important;
            opacity: 0.96 !important;
            filter: drop-shadow(0 14px 22px rgba(37, 99, 235, 0.16)) !important;
        }

        html body #ai-coach-page .chat-container {
            display: grid !important;
            grid-template-columns: 260px minmax(0, 1fr) !important;
            width: 100% !important;
            max-width: none !important;
            flex: 1 1 auto !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            border: 1px solid var(--coach-desktop-border) !important;
            border-radius: var(--coach-desktop-radius) !important;
            background: var(--coach-desktop-card) !important;
            box-shadow: var(--coach-desktop-shadow) !important;
            overflow: hidden !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }

        html body #ai-coach-page .chat-sidebar {
            display: flex !important;
            min-height: 0 !important;
            width: auto !important;
            min-width: 0 !important;
            overflow: hidden !important;
            border-right: 1px solid var(--coach-desktop-border) !important;
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.04), transparent 180px),
                var(--coach-desktop-card) !important;
        }

        html body #ai-coach-page .chat-main {
            display: flex !important;
            flex-direction: column !important;
            min-width: 0 !important;
            min-height: 0 !important;
            overflow: hidden !important;
            background:
                linear-gradient(180deg, rgba(37, 99, 235, 0.035), transparent 120px),
                var(--coach-desktop-card) !important;
        }

        html body #ai-coach-page #conversationsList {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overscroll-behavior: contain !important;
        }

        html body #ai-coach-page .chat-sidebar > div:first-child {
            display: flex !important;
            justify-content: center !important;
            padding: 10px 12px !important;
            border-color: var(--coach-desktop-border) !important;
        }

        html body #ai-coach-page .chat-sidebar .btn {
            width: 100% !important;
            min-width: 0 !important;
            min-height: 38px !important;
            padding: 8px 10px !important;
            border-radius: 9px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 7px !important;
            box-sizing: border-box !important;
            font-size: 0.72rem !important;
            font-weight: 900 !important;
            line-height: 1.1 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        html body #ai-coach-page .chat-sidebar .btn i {
            margin: 0 !important;
            flex: 0 0 auto !important;
        }

        html body #ai-coach-page .chat-sidebar > div:first-child > .btn {
            width: auto !important;
            min-width: 156px !important;
            max-width: 178px !important;
            min-height: 32px !important;
            padding: 6px 10px !important;
            border-radius: 8px !important;
            flex: 0 0 auto !important;
            font-size: 0.68rem !important;
        }

        html body #ai-coach-page .history-item {
            min-height: 40px !important;
            padding: 9px 12px !important;
            border-color: var(--coach-desktop-border) !important;
            color: var(--coach-desktop-muted) !important;
            font-size: 0.72rem !important;
        }

        html body #ai-coach-page .history-item:hover,
        html body #ai-coach-page .history-item.active {
            background: var(--coach-desktop-soft) !important;
            color: var(--coach-desktop-title) !important;
        }

        html body #ai-coach-page #conversationsList [style*="letter-spacing:1px"] {
            padding: 12px 12px 6px !important;
            color: var(--coach-desktop-muted) !important;
            font-size: 0.56rem !important;
            letter-spacing: 0 !important;
        }

        html body #ai-coach-page .coach-chat-header {
            min-height: 46px !important;
            padding: 8px 10px !important;
            border-color: var(--coach-desktop-border) !important;
            background: var(--coach-desktop-card) !important;
        }

        html body #ai-coach-page .coach-status {
            min-height: 28px !important;
            margin-top: 0 !important;
            padding: 5px 8px !important;
            border: 1px solid rgba(16, 185, 129, 0.18) !important;
            border-radius: 999px !important;
            background: rgba(16, 185, 129, 0.08) !important;
            color: var(--coach-desktop-success) !important;
            font-size: 0.66rem !important;
            font-weight: 900 !important;
            line-height: 1 !important;
        }

        html body #ai-coach-page .coach-status::before {
            width: 7px !important;
            height: 7px !important;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.12) !important;
        }

        html body #ai-coach-page .coach-actions-toggle {
            width: 34px !important;
            height: 34px !important;
            border-radius: 9px !important;
            border: 1px solid var(--coach-desktop-border) !important;
            background: var(--coach-desktop-field) !important;
            color: var(--coach-desktop-title) !important;
            box-shadow: none !important;
        }

        html body #ai-coach-page .coach-actions-toggle:hover,
        html body #ai-coach-page .coach-actions-toggle[aria-expanded="true"] {
            background: var(--coach-desktop-soft) !important;
            color: var(--coach-desktop-accent) !important;
        }

        html body #ai-coach-page .coach-actions-menu {
            top: calc(100% + 6px) !important;
            right: 0 !important;
            width: min(320px, calc(100vw - 40px)) !important;
            max-height: min(70vh, 500px) !important;
            border: 1px solid var(--coach-desktop-border) !important;
            border-radius: 10px !important;
            background: var(--coach-desktop-card) !important;
            box-shadow: var(--coach-desktop-shadow) !important;
        }

        html body #ai-coach-page .coach-actions-item,
        html body #ai-coach-page .coach-actions-history {
            min-height: 34px !important;
            padding: 8px 9px !important;
            border-radius: 8px !important;
            color: var(--coach-desktop-title) !important;
            font-size: 0.72rem !important;
            line-height: 1.15 !important;
        }

        html body #ai-coach-page .coach-actions-item:hover,
        html body #ai-coach-page .coach-actions-history:hover {
            background: var(--coach-desktop-soft) !important;
        }

        html body #ai-coach-page .coach-actions-item.danger,
        html body #ai-coach-page .coach-actions-item.danger i {
            color: var(--coach-desktop-danger) !important;
        }

        html body #ai-coach-page .chat-messages {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            overscroll-behavior: contain !important;
            padding: 14px !important;
            gap: 10px !important;
            background: transparent !important;
        }

        html body #ai-coach-page .coach-msg-row,
        html body #ai-coach-page .dynamic-msg {
            align-items: flex-end !important;
            gap: 9px !important;
            margin-top: 9px !important;
        }

        html body #ai-coach-page #welcomeMsg {
            margin-top: 0 !important;
        }

        html body #ai-coach-page .coach-avatar,
        html body #ai-coach-page .coach-user-avatar {
            width: 30px !important;
            height: 30px !important;
            min-width: 30px !important;
            border-radius: 9px !important;
            font-size: 0.76rem !important;
        }

        html body #ai-coach-page .coach-avatar {
            background: linear-gradient(135deg, var(--coach-desktop-accent), var(--coach-desktop-accent-2)) !important;
            color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18) !important;
        }

    html body #ai-coach-page .coach-user-avatar {
        border: 1px solid var(--coach-desktop-border) !important;
        background: var(--coach-desktop-soft) !important;
        color: var(--coach-desktop-title) !important;
        font-weight: 900 !important;
        position: relative !important;
        overflow: visible !important;
    }

        html body #ai-coach-page .chat-bubble {
            max-width: min(76%, 620px) !important;
            padding: 10px 12px !important;
            border-radius: 10px !important;
            box-shadow: none !important;
            font-size: 0.78rem !important;
            line-height: 1.44 !important;
            overflow-wrap: anywhere !important;
        }

        html body #ai-coach-page .bubble-ai {
            border: 1px solid var(--coach-desktop-border) !important;
            border-bottom-left-radius: 4px !important;
            background: var(--coach-desktop-field) !important;
            color: var(--coach-desktop-text) !important;
        }

        html body #ai-coach-page .bubble-user {
            border-bottom-right-radius: 4px !important;
            background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
            color: #ffffff !important;
        }

        html body #ai-coach-page .ai-response {
            gap: 7px !important;
            line-height: 1.46 !important;
            color: var(--coach-desktop-text) !important;
        }

        html body #ai-coach-page .ai-response :is(p, li) {
            color: var(--coach-desktop-text) !important;
        }

        html body #ai-coach-page .ai-response strong {
            color: var(--coach-desktop-title) !important;
        }

        html body #ai-coach-page .ai-response em {
            color: var(--coach-desktop-muted) !important;
        }

        html body #ai-coach-page .ai-response .ai-section-title {
            color: var(--coach-desktop-accent) !important;
            font-size: 0.64rem !important;
        }

        html body #ai-coach-page .chat-input-area {
            flex: 0 0 auto !important;
            padding: 10px !important;
            border-color: var(--coach-desktop-border) !important;
            background: var(--coach-desktop-card) !important;
        }

        html body #ai-coach-page .chat-input-wrapper {
            min-height: 42px !important;
            padding: 6px 6px 6px 10px !important;
            border: 1px solid var(--coach-desktop-border) !important;
            border-radius: 10px !important;
            background: var(--coach-desktop-field) !important;
            box-shadow: none !important;
        }

        html body #ai-coach-page .chat-input-wrapper:focus-within {
            border-color: rgba(37, 99, 235, 0.54) !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
            background: var(--coach-desktop-card) !important;
        }

        html body #ai-coach-page .chat-textarea {
            height: 22px !important;
            max-height: 86px !important;
            padding: 3px 0 !important;
            color: var(--coach-desktop-title) !important;
            font-size: 0.74rem !important;
            font-weight: 700 !important;
            line-height: 1.25 !important;
        }

        html body #ai-coach-page .chat-textarea::placeholder {
            color: var(--coach-desktop-muted) !important;
            font-weight: 400 !important;
            opacity: 1 !important;
        }

        html body #ai-coach-page .chat-send-btn {
            width: 52px !important;
            height: 32px !important;
            min-width: 52px !important;
            margin: 0 0 0 7px !important;
            border-radius: 9px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: linear-gradient(135deg, #2563eb, #06b6d4) !important;
            color: #ffffff !important;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22) !important;
        }

        html body #ai-coach-page .chat-attachment-btn {
            width: 32px !important;
            height: 32px !important;
            border: 1px solid rgba(96, 165, 250, 0.56) !important;
            border-radius: 9px !important;
            margin-right: 5px !important;
            background: linear-gradient(135deg, #dbeafe, #7dd3fc) !important;
            color: #0f172a !important;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.24) !important;
        }

        html body #ai-coach-page .chat-attachment-btn:hover,
        html body #ai-coach-page .chat-attachment-btn:focus-visible {
            background: linear-gradient(135deg, #bfdbfe, #67e8f9) !important;
            color: #082f49 !important;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.34) !important;
        }

        html body #ai-coach-page .chat-attachment-btn i,
        html body #ai-coach-page .chat-attachment-btn i::before,
        html body #ai-coach-page .chat-attachment-btn i::after {
            color: #0f172a !important;
            -webkit-text-fill-color: #0f172a !important;
            opacity: 1 !important;
        }

        html body #ai-coach-page .chat-attachment-chip {
            border-color: var(--coach-desktop-border) !important;
            background: rgba(37, 99, 235, 0.07) !important;
            color: var(--coach-desktop-title) !important;
        }

        html body #ai-coach-page .coach-disclaimer {
            margin-top: 5px !important;
            color: var(--coach-desktop-muted) !important;
            font-size: 0.56rem !important;
            line-height: 1.18 !important;
            gap: 5px !important;
        }
    }

    @media (min-width: 992px) and (max-width: 1320px) {
        html body #ai-coach-page .chat-container {
            grid-template-columns: 230px minmax(0, 1fr) !important;
        }

        html body #ai-coach-page .chat-bubble {
            max-width: min(82%, 560px) !important;
        }
    }

    body.user-desktop-shell #ai-coach-page .chat-sidebar > div:first-child {
        display: flex !important;
        justify-content: center !important;
        padding: 10px 12px !important;
    }

    body.user-desktop-shell #ai-coach-page .chat-sidebar > div:first-child > .btn {
        width: auto !important;
        min-width: 156px !important;
        max-width: 178px !important;
        min-height: 32px !important;
        padding: 6px 10px !important;
        border-radius: 8px !important;
        flex: 0 0 auto !important;
        font-size: 0.68rem !important;
    }

    /* Final bot avatar contrast guard for both day and night themes. */
    html body #ai-coach-page .coach-avatar {
        background: linear-gradient(135deg, #dbeafe, #7dd3fc) !important;
        border: 1px solid rgba(96, 165, 250, 0.62) !important;
        color: #0f172a !important;
        -webkit-text-fill-color: #0f172a !important;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22) !important;
        opacity: 1 !important;
        position: relative !important;
        overflow: visible !important;
    }

    html body #ai-coach-page .coach-avatar i,
    html body #ai-coach-page .coach-avatar i::before,
    html body #ai-coach-page .coach-avatar i::after {
        color: #0f172a !important;
        -webkit-text-fill-color: #0f172a !important;
        opacity: 1 !important;
    }

    html body #ai-coach-page .coach-avatar::after {
        content: "" !important;
        position: absolute !important;
        right: -2px !important;
        bottom: -2px !important;
        width: 10px !important;
        height: 10px !important;
        border-radius: 999px !important;
        background: #22c55e !important;
        border: 2px solid #ffffff !important;
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.18) !important;
        opacity: 1 !important;
        pointer-events: none !important;
    }

    html body #ai-coach-page .coach-user-avatar {
        position: relative !important;
        overflow: visible !important;
        padding: 0 !important;
    }

    html body #ai-coach-page .coach-user-avatar::after {
        content: "" !important;
        position: absolute !important;
        right: -2px !important;
        bottom: -2px !important;
        width: 10px !important;
        height: 10px !important;
        border-radius: 999px !important;
        background: #22c55e !important;
        border: 2px solid #ffffff !important;
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.18) !important;
        opacity: 1 !important;
        pointer-events: none !important;
        z-index: 2 !important;
    }

    html body #ai-coach-page .coach-user-avatar-img {
        position: relative !important;
        z-index: 1 !important;
        display: block !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: inherit !important;
    }
</style>

<div class="db-section active" id="ai-coach-page" style="height:100%">
    <div class="sr-page-hero coach-progress-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <div class="coach-hero-icon"><i class="fa-solid fa-headset"></i></div>
                <div>
                    <h4 class="sr-page-hero-title text-gradient-primary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a7 7 0 0 0-7 7v3a4 4 0 0 0 4 4h1v-6H7v-1a5 5 0 0 1 10 0v1h-3v6h1a4 4 0 0 0 4-4v-3a7 7 0 0 0-7-7Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 21h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        Philippines Readiness Coach
                    </h4>
                    <p class="sr-page-hero-subtitle">Ask for advice, resume feedback, and focused practice guidance.</p>
                </div>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="coachPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="coachBlue" x1="62" y1="38" x2="164" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#coachPanel)" stroke="#BFDBFE" stroke-width="3"/><rect x="64" y="53" width="92" height="56" rx="20" fill="url(#coachBlue)"/><circle cx="92" cy="79" r="7" fill="#EFF6FF"/><circle cx="128" cy="79" r="7" fill="#EFF6FF"/><path d="M92 96h36" stroke="#EFF6FF" stroke-width="6" stroke-linecap="round"/><path d="M110 53V38" stroke="#2563EB" stroke-width="6" stroke-linecap="round"/><circle cx="110" cy="34" r="8" fill="#22C55E"/><path d="M156 70h22v24h-13l-9 9V70Z" fill="#BAE6FD"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="chat-container animate-fade-up">
        
        <!-- Sidebar History -->
        <div class="chat-sidebar d-none d-md-flex" id="coach-sidebar">
            <div style="padding:20px; border-bottom:1px solid var(--bd);">
                <button class="btn btn-outline-primary w-100" style="border-radius:12px;font-weight:600" onclick="newConversation()">
                    <i class="fa-solid fa-plus me-2"></i> New Conversation
                </button>
            </div>
            <div style="overflow-y:auto; flex-grow:1" id="conversationsList">
                <div style="padding:16px 16px 8px; font-size:.75rem; font-weight:700; color:var(--tx3); text-transform:uppercase; letter-spacing:1px">Recent</div>
                @forelse($recentConversations as $conv)
                    <div class="history-item" id="conv-{{ $conv->id }}">
                        <div class="d-flex align-items-center flex-grow-1" onclick="loadConversation({{ $conv->id }})">
                            <i class="fa-regular fa-message"></i> 
                            <span class="text-truncate" style="max-width: 150px;">{{ $conv->title ?: 'New Conversation' }}</span>
                        </div>
                        <button class="btn btn-link text-danger p-0 ms-2" onclick="deleteConversation({{ $conv->id }})">
                            <i class="fa-solid fa-trash-can" style="margin:0;"></i>
                        </button>
                    </div>
                @empty
                    <div style="padding:0 16px; font-size:.8rem; color:var(--tx3);">No recent conversations</div>
                @endforelse
                
                <div style="padding:16px 16px 8px; font-size:.75rem; font-weight:700; color:var(--tx3); text-transform:uppercase; letter-spacing:1px; margin-top: 10px;">Older</div>
                @forelse($olderConversations as $conv)
                    <div class="history-item" id="conv-{{ $conv->id }}">
                        <div class="d-flex align-items-center flex-grow-1" onclick="loadConversation({{ $conv->id }})">
                            <i class="fa-regular fa-message"></i> 
                            <span class="text-truncate" style="max-width: 150px;">{{ $conv->title ?: 'New Conversation' }}</span>
                        </div>
                        <button class="btn btn-link text-danger p-0 ms-2" onclick="deleteConversation({{ $conv->id }})">
                            <i class="fa-solid fa-trash-can" style="margin:0;"></i>
                        </button>
                    </div>
                @empty
                    <div style="padding:0 16px; font-size:.8rem; color:var(--tx3);">No older conversations</div>
                @endforelse
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-main">
            <!-- Header -->
            <div class="coach-chat-header">
                <div class="d-flex align-items-center">
                    <div>
                        <span class="coach-status">Online</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="coach-actions" id="coachActions">
                        <button class="coach-actions-toggle" id="coachActionsToggle" type="button" aria-label="Open conversation actions" aria-expanded="false" aria-controls="coachActionsMenu" onclick="toggleCoachActions(event)">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <div class="coach-actions-menu" id="coachActionsMenu" role="menu" aria-labelledby="coachActionsToggle">
                            <button class="coach-actions-item" type="button" role="menuitem" onclick="newConversation(); closeCoachActions();">
                                <i class="fa-solid fa-plus"></i>
                                <span>New conversation</span>
                            </button>
                            <div class="coach-actions-divider"></div>
                            <div class="coach-actions-heading">Recent history</div>
                            @forelse($recentConversations as $conv)
                                <button class="coach-actions-history" type="button" role="menuitem" onclick="loadConversation({{ $conv->id }}); closeCoachActions();">
                                    <i class="fa-regular fa-message"></i>
                                    <span>{{ $conv->title ?: 'New Conversation' }}</span>
                                </button>
                            @empty
                                <div class="coach-actions-empty">No recent conversations</div>
                            @endforelse
                            <div class="coach-actions-heading">Older history</div>
                            @forelse($olderConversations as $conv)
                                <button class="coach-actions-history" type="button" role="menuitem" onclick="loadConversation({{ $conv->id }}); closeCoachActions();">
                                    <i class="fa-regular fa-clock"></i>
                                    <span>{{ $conv->title ?: 'New Conversation' }}</span>
                                </button>
                            @empty
                                <div class="coach-actions-empty">No older conversations</div>
                            @endforelse
                            <div class="coach-actions-divider"></div>
                            <button class="coach-actions-item danger" type="button" role="menuitem" onclick="deleteCurrentConversation();">
                                <i class="fa-solid fa-trash-can"></i>
                                <span>Delete convo</span>
                            </button>
                            <button class="coach-actions-item danger" type="button" role="menuitem" onclick="clearCoachHistory();">
                                <i class="fa-solid fa-broom"></i>
                                <span>Clear all history</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="chat-messages" id="chatBox">
                <div class="text-center" style="margin-bottom:16px" id="chatDateBadge">
                    <span class="db-badge" style="background:rgba(255,255,255,0.05);color:var(--tx3)">Today</span>
                </div>

                <div class="coach-msg-row" id="welcomeMsg">
                    <div class="coach-avatar">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="chat-bubble bubble-ai">
                        Hello {{ Auth::user()->name }}! I can use your competency map and verified story index to explain scores, rehearse truthful answers, and prepare your next job-specific practice step. I will never invent experience for you.
                    </div>
                </div>
                
                <div id="typingIndicator" class="d-none coach-msg-row">
                     <div class="coach-avatar">
                        <i class="fa-solid fa-robot"></i>
                    </div>
                    <div class="chat-bubble bubble-ai" style="padding:12px 20px;display:flex;align-items:center;gap:4px">
                        <div style="width:8px;height:8px;background:rgba(255,255,255,0.5);border-radius:50%;animation:pulse 1.5s infinite"></div>
                        <div style="width:8px;height:8px;background:rgba(255,255,255,0.5);border-radius:50%;animation:pulse 1.5s infinite .2s"></div>
                        <div style="width:8px;height:8px;background:rgba(255,255,255,0.5);border-radius:50%;animation:pulse 1.5s infinite .4s"></div>
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="chat-input-area" id="coach-input-area">
                <div class="chat-attachment-preview" id="chatAttachmentPreview" aria-live="polite"></div>
                <div class="chat-input-wrapper">
                    <input class="chat-file-input" id="coachFiles" type="file" multiple accept=".pdf,.doc,.docx,.txt,.rtf,.csv,.png,.jpg,.jpeg,.webp">
                    <button class="chat-attachment-btn" type="button" aria-label="Attach interview file" title="Attach resume, certificate, PDF, DOCX, or image" onclick="document.getElementById('coachFiles').click()">
                        <i class="fa-solid fa-paperclip"></i>
                    </button>
                    <textarea class="chat-textarea" id="chatMsg" rows="1" placeholder="Ask about interviews, resumes, certificates..." oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                    <button class="chat-send-btn" onclick="sendMsg()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
                <div class="coach-disclaimer">
                    <i class="fa-regular fa-circle-info" aria-hidden="true"></i>
                    The coach can make mistakes. Verify advice and keep every personal claim truthful.
                </div>
            </div>
        </div>
    </div>

    <script>
        let coachChatHistory = [];
        let currentConversationId = null;
        const initialCoachPrompt = @json((string) request('ask', ''));
        let coachSelectedFiles = [];
        let coachSending = false;
        const coachAllowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'csv', 'png', 'jpg', 'jpeg', 'webp'];
        const coachMaxFiles = 3;
        const coachMaxFileBytes = 5 * 1024 * 1024;
        const coachUserPhotoUrl = @json($coachUserPhotoUrl);
        const coachUserInitial = @json($coachUserInitial);

        function toggleCoachActions(event) {
            event.stopPropagation();
            const menu = document.getElementById('coachActionsMenu');
            const toggle = document.getElementById('coachActionsToggle');
            const willOpen = !menu.classList.contains('open');

            menu.classList.toggle('open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        }

        function closeCoachActions() {
            const menu = document.getElementById('coachActionsMenu');
            const toggle = document.getElementById('coachActionsToggle');

            if (menu) menu.classList.remove('open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }

        function handleCoachFiles(input) {
            const incomingFiles = Array.from(input.files || []);
            const acceptedFiles = [];

            incomingFiles.forEach(file => {
                const extension = (file.name.split('.').pop() || '').toLowerCase();

                if (!coachAllowedExtensions.includes(extension)) {
                    alert(`${file.name} is not a supported interview file type.`);
                    return;
                }

                if (file.size > coachMaxFileBytes) {
                    alert(`${file.name} is larger than the 5MB upload limit.`);
                    return;
                }

                acceptedFiles.push(file);
            });

            const availableSlots = Math.max(0, coachMaxFiles - coachSelectedFiles.length);
            coachSelectedFiles = coachSelectedFiles.concat(acceptedFiles.slice(0, availableSlots));
            if (acceptedFiles.length > availableSlots) {
                alert('You can attach up to 3 files at a time.');
            }

            input.value = '';
            renderCoachAttachments();
        }

        function renderCoachAttachments() {
            const preview = document.getElementById('chatAttachmentPreview');
            if (!preview) return;

            if (!coachSelectedFiles.length) {
                preview.classList.remove('has-files');
                preview.innerHTML = '';
                return;
            }

            preview.classList.add('has-files');
            preview.innerHTML = coachSelectedFiles.map((file, index) => `
                <div class="chat-attachment-chip">
                    <i class="fa-solid ${coachFileIcon(file.name)}" aria-hidden="true"></i>
                    <span title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span>
                    <small>${formatFileSize(file.size)}</small>
                    <button class="chat-attachment-remove" type="button" aria-label="Remove ${escapeHtml(file.name)}" onclick="removeCoachAttachment(${index})">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            `).join('');
        }

        function removeCoachAttachment(index) {
            coachSelectedFiles.splice(index, 1);
            renderCoachAttachments();
        }

        function clearCoachAttachments() {
            coachSelectedFiles = [];
            renderCoachAttachments();
        }

        function coachFileIcon(fileName) {
            const extension = (fileName.split('.').pop() || '').toLowerCase();
            if (extension === 'pdf') return 'fa-file-pdf';
            if (['doc', 'docx', 'rtf', 'txt'].includes(extension)) return 'fa-file-lines';
            if (extension === 'csv') return 'fa-file-csv';
            if (['png', 'jpg', 'jpeg', 'webp'].includes(extension)) return 'fa-file-image';
            return 'fa-file';
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return `${bytes} B`;
            if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1).replace(/\.0$/, '')} KB`;
            return `${(bytes / (1024 * 1024)).toFixed(1).replace(/\.0$/, '')} MB`;
        }

        function renderBubbleAttachments(files) {
            if (!files.length) return '';

            return `
                <div class="chat-attachment-bubble">
                    ${files.map(file => `
                        <div class="chat-attachment-bubble-item">
                            <i class="fa-solid ${coachFileIcon(file.name)}" aria-hidden="true"></i>
                            <span>${escapeHtml(file.name)}</span>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function renderCoachUserAvatar() {
            if (coachUserPhotoUrl) {
                return `<img class="coach-user-avatar-img" src="${escapeHtml(coachUserPhotoUrl)}" alt="Avatar">`;
            }

            return escapeHtml(coachUserInitial || 'U');
        }

        async function sendMsg() {
            const ta = document.getElementById('chatMsg');
            const box = document.getElementById('chatBox');
            const text = ta.value.trim();
            const files = coachSelectedFiles.slice();
            const displayText = text || (files.length ? 'Please review the attached interview file(s).' : '');
            if(!displayText || coachSending) return;
            coachSending = true;

            // Create user bubble
            const userMsgDiv = document.createElement('div');
            userMsgDiv.className = 'd-flex justify-content-end mt-3 dynamic-msg';
            userMsgDiv.style.gap = '12px';
            userMsgDiv.innerHTML = `
                    <div class="chat-bubble bubble-user">
                        ${escapeHtml(displayText).replace(/\n/g, '<br>')}
                        ${renderBubbleAttachments(files)}
                    </div>
                    <div class="coach-user-avatar">
                        ${renderCoachUserAvatar()}
                    </div>
            `;
            box.insertBefore(userMsgDiv, document.getElementById('typingIndicator'));
            
            ta.value = '';
            ta.style.height = '';
            clearCoachAttachments();
            box.scrollTop = box.scrollHeight;
            
            // Show typing
            const typing = document.getElementById('typingIndicator');
            typing.classList.remove('d-none');
            typing.classList.add('d-flex');
            box.scrollTop = box.scrollHeight;

            try {
                const formData = new FormData();
                formData.append('message', displayText);
                formData.append('history', JSON.stringify(coachChatHistory));
                if (currentConversationId) {
                    formData.append('conversation_id', currentConversationId);
                }
                files.forEach(file => formData.append('coach_attachments[]', file));

                // Call AI Backend
                const response = await fetch('{{ route("user.coach.chat") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                if (!response.ok) {
                    const errorPayload = await response.json().catch(() => null);
                    throw new Error(errorPayload?.message || 'Network response was not ok');
                }

                const data = await response.json();
                const aiResponse = data.response;
                
                if (data.conversation_id && !currentConversationId) {
                    currentConversationId = data.conversation_id;
                    // Add to sidebar if we just created it
                    const recentDiv = document.querySelector('#conversationsList > div:nth-child(2)'); // The first 'No recent conversations' or item
                    if (recentDiv && recentDiv.textContent.includes('No recent')) {
                        recentDiv.outerHTML = ''; // Remove empty message
                    }
                    
                    const newItem = document.createElement('div');
                    newItem.className = 'history-item active';
                    newItem.id = 'conv-' + data.conversation_id;
                    newItem.innerHTML = `
                        <div class="d-flex align-items-center flex-grow-1" onclick="loadConversation(${data.conversation_id})">
                            <i class="fa-regular fa-message"></i> 
                            <span class="text-truncate" style="max-width: 150px;">${escapeHtml(data.title || 'New Conversation')}</span>
                        </div>
                        <button class="btn btn-link text-danger p-0 ms-2" onclick="deleteConversation(${data.conversation_id})">
                            <i class="fa-solid fa-trash-can" style="margin:0;"></i>
                        </button>
                    `;
                    document.getElementById('conversationsList').insertBefore(newItem, document.querySelector('#conversationsList > div:nth-child(1)').nextSibling);
                }

                // Update History
                const historyContent = files.length
                    ? `${displayText}\n\nAttached interview file(s):\n${files.map(file => `- ${file.name}`).join('\n')}`
                    : displayText;
                coachChatHistory.push({ role: 'user', content: historyContent });
                coachChatHistory.push({ role: 'ai', content: aiResponse });

                // Remove typing indicator
                typing.classList.remove('d-flex');
                typing.classList.add('d-none');

                // Add AI Message
                const aiMsgDiv = document.createElement('div');
                aiMsgDiv.className = 'coach-msg-row mt-3 dynamic-msg';
                aiMsgDiv.innerHTML = `
                        <div class="coach-avatar">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div class="chat-bubble bubble-ai">
                            ${formatMarkdown(aiResponse)}
                        </div>
                `;
                box.insertBefore(aiMsgDiv, typing);
                box.scrollTop = box.scrollHeight;
            } catch (error) {
                console.error('Error:', error);
                typing.classList.remove('d-flex');
                typing.classList.add('d-none');
                
                const errorMsgDiv = document.createElement('div');
                errorMsgDiv.className = 'coach-msg-row mt-3 dynamic-msg';
                errorMsgDiv.innerHTML = `
                        <div class="coach-avatar">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div class="chat-bubble bubble-ai" style="color:#ef4444; border-color:#ef4444">
                            ${escapeHtml(error.message || 'Sorry, I encountered an error communicating with the AI. Please try again later.')}
                        </div>
                `;
                box.insertBefore(errorMsgDiv, typing);
                box.scrollTop = box.scrollHeight;
            } finally {
                coachSending = false;
            }
        }
        
        function escapeHtml(unsafe) {
            return String(unsafe || '')
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        function formatInlineMarkdown(text) {
            return escapeHtml(text)
                .replace(/`([^`]+)`/g, '<code>$1</code>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>');
        }

        function flushList(listItems, ordered, parts) {
            if (!listItems.length) return;

            const tag = ordered ? 'ol' : 'ul';
            parts.push(`<${tag}>${listItems.map(item => `<li>${formatInlineMarkdown(item)}</li>`).join('')}</${tag}>`);
            listItems.length = 0;
        }

        function formatMarkdown(text) {
            const normalized = String(text || '')
                .replace(/\r\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trim();

            if (!normalized) return '<div class="ai-response"><p>No response yet.</p></div>';

            const parts = [];
            const listItems = [];
            let listOrdered = false;

            normalized.split('\n').forEach(rawLine => {
                const line = rawLine.trim();

                if (!line) {
                    flushList(listItems, listOrdered, parts);
                    return;
                }

                const bullet = line.match(/^[-*]\s+(.+)$/);
                const numbered = line.match(/^\d+[.)]\s+(.+)$/);

                if (bullet || numbered) {
                    const ordered = Boolean(numbered);

                    if (listItems.length && ordered !== listOrdered) {
                        flushList(listItems, listOrdered, parts);
                    }

                    listOrdered = ordered;
                    listItems.push((bullet || numbered)[1]);
                    return;
                }

                flushList(listItems, listOrdered, parts);

                const plainHeading = line.match(/^\*\*([^*]{2,60})\*\*:?\s*$/);
                const colonHeading = line.match(/^([A-Za-z][A-Za-z\s/&-]{2,48}):$/);

                if (plainHeading || colonHeading) {
                    parts.push(`<span class="ai-section-title">${formatInlineMarkdown((plainHeading || colonHeading)[1])}</span>`);
                    return;
                }

                parts.push(`<p>${formatInlineMarkdown(line)}</p>`);
            });

            flushList(listItems, listOrdered, parts);

            return `<div class="ai-response">${parts.join('')}</div>`;
        }
        
        function newConversation() {
            // Reset state
            coachChatHistory = [];
            currentConversationId = null;
            
            // Remove active classes from sidebar
            document.querySelectorAll('.history-item').forEach(el => el.classList.remove('active'));

            // Remove all dynamic messages
            document.querySelectorAll('.dynamic-msg').forEach(e => e.remove());
            clearCoachAttachments();
            
            // Focus the input
            const ta = document.getElementById('chatMsg');
            ta.value = '';
            ta.style.height = '';
            ta.focus();
        }

        async function loadConversation(id) {
            try {
                const response = await fetch(`/coach/conversation/${id}`, {
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                if (!response.ok) throw new Error('Failed to load conversation');
                
                const data = await response.json();
                
                // Update State
                currentConversationId = data.conversation.id;
                coachChatHistory = [];
                
                // Update UI active state
                document.querySelectorAll('.history-item').forEach(el => el.classList.remove('active'));
                const activeItem = document.getElementById('conv-' + id);
                if (activeItem) activeItem.classList.add('active');
                
                // Clear chatbox
                document.querySelectorAll('.dynamic-msg').forEach(e => e.remove());
                
                const box = document.getElementById('chatBox');
                const typing = document.getElementById('typingIndicator');
                
                // Render messages
                data.conversation.messages.forEach(msg => {
                    coachChatHistory.push({ role: msg.role, content: msg.content });
                    
                    const msgDiv = document.createElement('div');
                    if (msg.role === 'user') {
                        msgDiv.className = 'd-flex justify-content-end mt-3 dynamic-msg';
                        msgDiv.style.gap = '12px';
                        msgDiv.innerHTML = `
                                <div class="chat-bubble bubble-user">${escapeHtml(msg.content).replace(/\n/g, '<br>')}</div>
                                <div class="coach-user-avatar">
                                    ${renderCoachUserAvatar()}
                                </div>
                        `;
                    } else {
                        msgDiv.className = 'coach-msg-row mt-3 dynamic-msg';
                        msgDiv.innerHTML = `
                                <div class="coach-avatar">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <div class="chat-bubble bubble-ai">
                                    ${formatMarkdown(msg.content)}
                                </div>
                        `;
                    }
                    box.insertBefore(msgDiv, typing);
                });
                box.scrollTop = box.scrollHeight;

            } catch (error) {
                console.error(error);
                alert('Could not load conversation');
            }
        }

        async function deleteConversation(id) {
            closeCoachActions();
            if (!confirm('Are you sure you want to delete this conversation?')) return;
            
            try {
                const response = await fetch(`/coach/conversation/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                
                if (!response.ok) throw new Error('Failed to delete conversation');
                
                // Remove from UI
                const item = document.getElementById('conv-' + id);
                if (item) item.remove();
                
                // If it was the active conversation, start a new one
                if (currentConversationId === id) {
                    newConversation();
                }
            } catch (error) {
                console.error(error);
                alert('Could not delete conversation');
            }
        }

        function deleteCurrentConversation() {
            if (!currentConversationId) {
                closeCoachActions();
                alert('No active conversation to delete.');
                return;
            }

            deleteConversation(currentConversationId);
        }

        async function clearCoachHistory() {
            closeCoachActions();

            if (!confirm('Are you sure you want to clear all AI Coach conversation history?')) return;

            try {
                const response = await fetch('{{ route("user.coach.clear") }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                if (!response.ok) throw new Error('Failed to clear conversations');

                document.querySelectorAll('.history-item').forEach(item => item.remove());
                const list = document.getElementById('conversationsList');
                if (list) {
                    const recentHeading = list.querySelector('div:first-child');
                    const olderHeading = Array.from(list.children).find(child => child.textContent.trim() === 'Older');
                    const recentEmpty = document.createElement('div');
                    recentEmpty.style.cssText = 'padding:0 16px; font-size:.8rem; color:var(--tx3);';
                    recentEmpty.textContent = 'No recent conversations';

                    const olderEmpty = document.createElement('div');
                    olderEmpty.style.cssText = 'padding:0 16px; font-size:.8rem; color:var(--tx3);';
                    olderEmpty.textContent = 'No older conversations';

                    if (recentHeading && !recentHeading.nextElementSibling?.textContent.includes('No recent')) {
                        recentHeading.insertAdjacentElement('afterend', recentEmpty);
                    }
                    if (olderHeading && !olderHeading.nextElementSibling?.textContent.includes('No older')) {
                        olderHeading.insertAdjacentElement('afterend', olderEmpty);
                    }
                }
                newConversation();
            } catch (error) {
                console.error(error);
                alert('Could not clear conversation history');
            }
        }

        document.getElementById('chatMsg').addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMsg();
            }
        });

        document.getElementById('coachFiles')?.addEventListener('change', function () {
            handleCoachFiles(this);
        });

        document.addEventListener('click', function (event) {
            const actions = document.getElementById('coachActions');
            if (actions && !actions.contains(event.target)) {
                closeCoachActions();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeCoachActions();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const prompt = String(initialCoachPrompt || '').trim();
            const input = document.getElementById('chatMsg');

            if (!prompt || !input) {
                return;
            }

            input.value = prompt;
            input.style.height = '';
            input.style.height = input.scrollHeight + 'px';
            window.setTimeout(sendMsg, 250);
        });
    </script>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof window.createSpeakReadyTour !== 'function') return;

        const stepsMobile = [
            { element: '#chatBox', popover: { title: 'Coach Messages', description: 'Your AI Coach responds here with interview advice, resume tips, and practice guidance.', side: 'bottom', align: 'center' }},
            { element: '#coach-input-area', popover: { title: 'Ask Anything', description: 'Type a question or prompt here, then press Enter to send it.', side: 'top', align: 'center' }}
        ];

        const stepsDesktop = [
            { element: '#coach-sidebar', popover: { title: 'Conversation History', description: 'Start a new chat or return to an earlier coaching conversation.', side: 'right', align: 'start' }},
            { element: '#chatBox', popover: { title: 'Coach Messages', description: 'Your AI Coach responds here with interview advice, resume tips, and practice guidance.', side: 'bottom', align: 'center' }},
            { element: '#coach-input-area', popover: { title: 'Ask Anything', description: 'Type a question or prompt here, then press Enter to send it.', side: 'top', align: 'center' }}
        ];

        window.createSpeakReadyTour({
            completionKey: 'onboarding_completed_coach',
            serverDetectedMobile: @json($isMobile),
            stepsMobile,
            stepsDesktop,
            autoStartDelay: 500,
        });
    });
</script>
@endpush
@endsection
