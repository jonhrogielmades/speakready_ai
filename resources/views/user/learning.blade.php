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
@php
    $gameResult = session('game_result');
@endphp

<div class="db-section active" id="learning-games-page">
    <!-- Header & Navigation -->
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 15h10l2 3a2 2 0 0 0 3-2l-1-5a6 6 0 0 0-6-5H9a6 6 0 0 0-6 5l-1 5a2 2 0 0 0 3 2l2-3Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 11h4M10 9v4M16 10h.01M18 13h.01" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Philippines Interview Challenges
                </h4>
                <p class="sr-page-hero-subtitle">Complete Philippines interview challenges, earn XP, and strengthen practical answer skills.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="gamesPanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="gamesBlue" x1="58" y1="40" x2="166" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#gamesPanel)" stroke="#BFDBFE" stroke-width="3"/><path d="M67 84c5-26 18-36 43-36s38 10 43 36l4 22c2 12-11 18-18 8l-10-14H91l-10 14c-7 10-20 4-18-8l4-22Z" fill="url(#gamesBlue)"/><path d="M82 80h23M94 69v23M132 74h.01M146 88h.01" stroke="#EFF6FF" stroke-width="7" stroke-linecap="round"/><circle cx="164" cy="43" r="17" fill="#F59E0B"/><path d="m164 33 3 7 8 1-6 5 2 8-7-4-7 4 2-8-6-5 8-1 3-7Z" fill="#fff"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="sr-page-actions">
        <div id="tour-search" class="db-top-search" style="width:100%; max-width:300px; background:var(--bg3);border:1px solid var(--bd); margin:0; border-radius:12px; padding:10px 16px;">
            <i class="fa-solid fa-magnifying-glass" style="color:var(--tx3)"></i>
            <input type="text" placeholder="Search challenges, skills, scenarios..." style="width:100%; background:transparent; border:none; color:var(--tx); outline:none;">
        </div>
        <a id="btn-skill-tree" href="{{ route('user.skills') }}" class="btn btn-sm d-inline-flex align-items-center justify-content-center" style="background:var(--bg3); border:1px solid var(--bd); color:var(--tx2); border-radius:10px; font-weight:600; white-space:nowrap;"><i class="fa-solid fa-tree me-1" style="color:#10b981"></i> <span>Skill Tree</span></a>
    </div>

    <!-- Sub-Navigation -->
    <div class="learning-category-select-wrap">
        <select id="learningCategorySelect" class="learning-category-select" aria-label="Select challenge path">
            @foreach($categories as $category)
                <option value="{{ route('user.learning', ['category_id' => $category->id]) }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
            @endforeach
        </select>
    </div>

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
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 style="font-weight:700;color:var(--tx);margin:0">Challenge Journey</h5>
                <span class="badge" style="background:rgba(245,158,11,0.1);color:#f59e0b;font-size:0.85rem;padding:8px 15px;border-radius:10px;"><i class="fa-solid fa-heart me-1"></i> {{ $currentEnergy }} / {{ $maxEnergy }} Lives</span>
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
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <div style="font-size:0.75rem;color:{{ $status === 'completed' ? '#34d399' : ($status === 'active' ? 'var(--pur)' : 'var(--tx3)') }};font-weight:700;margin-bottom:5px;text-transform:uppercase">Level {{ $level->level_number }}</div>
                                        <h5 style="color:var(--tx);font-weight:700;margin:0">{{ $level->title }}</h5>
                                    </div>
                                    @if($status === 'completed')
                                        <div class="score-badge"><i class="fa-solid fa-star"></i> Score: {{ $score }}%</div>
                                    @elseif($status === 'active')
                                        <div class="requirement-badge"><i class="fa-solid fa-bullseye"></i> Goal: {{ $level->required_score }}%+</div>
                                    @else
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
                                    <div style="background:var(--bg3);border-radius:10px;padding:15px;margin-bottom:20px;border:1px solid var(--bd)">
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
                                        <button type="submit" class="btn btn-shine" style="background:var(--dash-primary, #60a5fa);color:#fff;border:none;box-shadow:0 4px 15px rgba(96,165,250,0.4);border-radius:12px;font-weight:600;padding:10px 25px"><i class="fa-solid fa-play me-2"></i> Start Challenge</button>
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

                    @if(!empty($gameResult['goal_breakdown']['averages']))
                        <div class="mb-4" style="border:1px solid var(--bd);border-radius:12px;padding:14px;background:var(--bg3);">
                            <div style="font-weight:900;color:var(--tx);margin-bottom:10px;">Goal Score Breakdown</div>
                            <div class="row g-2 game-result-breakdown-grid">
                                @foreach($gameResult['goal_breakdown']['averages'] as $label => $value)
                                    <div class="col-6 col-md-4">
                                        <div class="game-result-breakdown-card">
                                            <div style="font-size:0.72rem;color:var(--tx3);font-weight:800;text-transform:uppercase;">{{ str_replace('_', ' ', $label) }}</div>
                                            <div style="font-size:1.05rem;color:var(--tx);font-weight:900;">{{ (int) $value }}%</div>
                                        </div>
                                    </div>
                                @endforeach
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
