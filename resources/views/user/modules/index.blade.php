@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Interview Modules')

@section('content')
<style>
    .module-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 24px;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s;
    }
    .module-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1), inset 0 1px 1px rgba(255, 255, 255, 0.08);
        border-color: rgba(139,92,246,0.5);
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

    @media (max-width: 767px) {
        #interview-modules-page .sr-page-actions {
            display: block !important;
            margin-bottom: 12px !important;
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
            border: 1px solid var(--bd);
            border-radius: 12px;
            background: var(--bg3);
            color: var(--tx);
            padding: 10px 12px;
            font-weight: 700;
            font-size: 0.86rem;
            outline: none;
        }
        #interview-modules-page .module-card {
            border-radius: 14px !important;
            min-height: auto;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        #interview-modules-page .module-card:hover {
            transform: none;
        }
        #interview-modules-page .module-card > div:first-child {
            height: 104px !important;
        }
        #interview-modules-page .module-card > div:first-child > div[style*="top:15px"] {
            top: 10px !important;
            left: 10px !important;
            right: 10px;
            flex-wrap: wrap;
        }
        #interview-modules-page .module-card > div:first-child > div[style*="bottom:-25px"] {
            width: 46px !important;
            height: 46px !important;
            bottom: -21px !important;
            left: 14px !important;
            border-radius: 14px !important;
            font-size: 1.2rem !important;
        }
        #interview-modules-page .module-card > div:last-child {
            padding: 32px 14px 14px !important;
        }
        #interview-modules-page .module-card h5 {
            font-size: 0.98rem;
            line-height: 1.25;
            margin-bottom: 8px !important;
        }
        #interview-modules-page .module-card p {
            font-size: 0.8rem !important;
            line-height: 1.45;
            margin-bottom: 14px !important;
        }
        #interview-modules-page .module-card .mt-auto {
            display: grid !important;
            grid-template-columns: 1fr;
            gap: 10px;
            align-items: stretch !important;
        }
        #interview-modules-page .module-card .btn {
            width: 100%;
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    }
</style>
@include('partials.page-hero-styles')

<div class="db-section active" id="interview-modules-page">
    <div class="sr-page-hero">
        <div class="sr-page-hero-inner">
            <div class="sr-page-hero-copy">
                <h4 class="sr-page-hero-title text-gradient-primary">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h10a3 3 0 0 1 3 3v13H8a3 3 0 0 0-3 3V4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 8h7M8 12h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Interview Modules
                </h4>
                <p class="sr-page-hero-subtitle">Learn key concepts, review study materials, and prepare for your interviews.</p>
            </div>
        </div>
        <svg class="sr-page-hero-art" viewBox="0 0 220 150" aria-hidden="true">
            <defs><linearGradient id="modulePanel" x1="36" y1="18" x2="176" y2="128"><stop stop-color="#DBEAFE"/><stop offset="1" stop-color="#ECFEFF"/></linearGradient><linearGradient id="moduleBlue" x1="58" y1="40" x2="168" y2="116"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#06B6D4"/></linearGradient></defs>
            <rect x="34" y="22" width="152" height="106" rx="18" fill="url(#modulePanel)" stroke="#BFDBFE" stroke-width="3"/><path d="M66 48h82a12 12 0 0 1 12 12v50H78a12 12 0 0 0-12 12V48Z" fill="url(#moduleBlue)"/><path d="M83 67h55M83 84h43" stroke="#EFF6FF" stroke-width="7" stroke-linecap="round"/><circle cx="160" cy="48" r="18" fill="#22C55E"/><path d="M153 48l5 5 10-12" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M30 134c34-11 72-11 108 0s58 8 78-3" fill="none" stroke="#93C5FD" stroke-width="5" stroke-linecap="round" opacity=".5"/>
        </svg>
    </div>
    <div class="sr-page-actions">
        <form action="{{ route('user.modules.index') }}" method="GET" class="db-top-search" style="width:100%; max-width:300px; background:var(--bg3);border:1px solid var(--bd); margin:0; border-radius:12px; padding:10px 16px;">
            <i class="fa-solid fa-magnifying-glass" style="color:var(--tx3)"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search modules..." style="width:100%; background:transparent; border:none; color:var(--tx); outline:none;">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
        </form>
    </div>

    <!-- Sub-Navigation -->
    <div class="module-topic-select-wrap">
        <select id="moduleTopicSelect" class="module-topic-select" aria-label="Select module topic">
            <option value="{{ route('user.modules.index', array_filter(['search' => request('search')])) }}" {{ !request('category') ? 'selected' : '' }}>All Topics</option>
            @foreach($categories as $category)
                <option value="{{ route('user.modules.index', array_filter(['category' => $category, 'search' => request('search')])) }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
            @endforeach
        </select>
    </div>

    <div id="nav-pills-container" class="mb-4 pb-2 d-flex flex-wrap gap-2">
        <a href="{{ route('user.modules.index') }}" class="ll-nav-pill {{ !request('category') ? 'active' : '' }}" style="margin:0;"><i class="fa-solid fa-layer-group"></i> All Topics</a>
        @foreach($categories as $category)
            <a href="{{ route('user.modules.index', ['category' => $category]) }}" class="ll-nav-pill {{ request('category') == $category ? 'active' : '' }}" style="margin:0;"><i class="fa-solid fa-folder"></i> {{ $category }}</a>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        @forelse($modules as $index => $module)
            <div class="col-12 col-md-6 col-lg-4 animate-fade-up" style="animation-delay: {{ $index * 0.1 }}s">
                <div class="module-card">
                    <div style="height:140px; background:linear-gradient(135deg, rgba(59,130,246,0.15) 0%, rgba(139,92,246,0.15) 100%); position:relative; overflow:hidden;">
                        <div style="position:absolute; top:-50%; left:-50%; width:200%; height:200%; background:radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%); pointer-events:none;"></div>
                        <div style="position:absolute; top:15px; left:15px; display:flex; gap:8px; z-index:2;">
                            <span class="badge" style="background:rgba(255,255,255,0.95); color:#1e293b; font-weight:700; box-shadow:0 4px 10px rgba(0,0,0,0.1);"><i class="fa-solid fa-tag me-1 text-primary"></i> {{ ucfirst($module->type) }}</span>
                            @if($module->difficulty)
                                <span class="badge" style="background:rgba(255,255,255,0.9); color:{{ $module->difficulty == 'beginner' ? '#10b981' : ($module->difficulty == 'intermediate' ? '#f59e0b' : '#ef4444') }}; font-weight:700;">
                                    {{ ucfirst($module->difficulty) }}
                                </span>
                            @endif
                        </div>
                        <div style="position:absolute; bottom:-25px; left:20px; width:56px; height:56px; background:var(--sf); border:2px solid var(--pur); border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:1.6rem; color:var(--pur); box-shadow: 0 8px 20px rgba(139,92,246,0.25); z-index:2;">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                    </div>
                    <div style="padding:40px 20px 20px; flex:1; display:flex; flex-direction:column;">
                        <h5 style="color:var(--tx); font-weight:700; margin-bottom:10px;">{{ $module->title }}</h5>
                        <p style="color:var(--tx3); font-size:0.9rem; margin-bottom:20px;">
                            {{ \Illuminate\Support\Str::limit($module->description, 100) }}
                        </p>
                        
                        <div class="mt-auto pt-3" style="border-top:1px solid var(--bd); display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-size:0.8rem; color:var(--tx2); font-weight:600;">
                                <i class="fa-solid fa-eye me-1"></i> {{ number_format($module->views) }} views
                            </div>
                            <a href="{{ route('user.modules.show', $module->id) }}" class="btn btn-sm btn-shine" style="background:var(--dash-primary, #60a5fa); color:#fff; border-radius:10px; font-weight:600; box-shadow:0 4px 15px rgba(96,165,250,0.3); border:none; padding:8px 16px;">
                                Start Learning <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5" style="background:var(--bg2); border-radius:16px; border:1px solid var(--bd);">
                    <i class="fa-solid fa-folder-open fa-3x mb-3" style="color:var(--bd)"></i>
                    <h5 style="color:var(--tx3)">No modules found in this category.</h5>
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
        if (!topicSelect) return;

        topicSelect.addEventListener('change', function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });
    });
</script>
@endpush
@endsection

