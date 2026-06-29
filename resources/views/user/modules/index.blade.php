@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')

@section('content')
<style>
    .module-card {
        background: var(--sf);
        border: 1px solid var(--bd);
        border-radius: 18px;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: 0.3s;
    }
    .module-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        border-color: rgba(59,130,246,0.4);
    }
    .ll-nav-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 30px;
        background: var(--sf);
        color: var(--tx2);
        border: 1px solid var(--bd);
        text-decoration: none;
        font-weight: 500;
        transition: 0.3s;
    }
    @media (max-width: 576px) {
        .ll-nav-pill {
            padding: 6px 14px;
            font-size: 0.85rem;
            gap: 5px;
        }
    }
    .ll-nav-pill:hover, .ll-nav-pill.active {
        background: var(--pur);
        color: #fff;
        border-color: var(--pur);
        box-shadow: 0 4px 15px rgba(59,130,246,0.3);
    }
</style>

<div class="db-section active">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h3 style="font-weight:800;color:var(--tx);margin:0; font-family:'Poppins', sans-serif; text-transform:uppercase;">Interview Modules</h3>
            </div>
            <p style="color:var(--tx3);margin-top:5px; font-weight:500;">Learn key concepts, review study materials, and prepare for your interviews.</p>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap mt-3 mt-sm-0" style="flex: 1; min-width: 250px; justify-content: flex-end;">
            <form action="{{ route('user.modules.index') }}" method="GET" class="db-top-search" style="width:100%; max-width:300px; background:var(--sf);border:1px solid var(--bd); margin:0;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search modules..." style="width:100%;">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
            </form>
        </div>
    </div>

    <!-- Sub-Navigation -->
    <div id="nav-pills-container" class="mb-4 pb-2 d-flex flex-wrap gap-2">
        <a href="{{ route('user.modules.index') }}" class="ll-nav-pill {{ !request('category') ? 'active' : '' }}" style="margin:0;"><i class="fa-solid fa-layer-group"></i> All Topics</a>
        @foreach($categories as $category)
            <a href="{{ route('user.modules.index', ['category' => $category]) }}" class="ll-nav-pill {{ request('category') == $category ? 'active' : '' }}" style="margin:0;"><i class="fa-solid fa-folder"></i> {{ $category }}</a>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        @forelse($modules as $module)
            <div class="col-12 col-md-6 col-lg-4">
                <div class="module-card">
                    <div style="height:140px; background:linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(52,211,153,0.1) 100%); position:relative;">
                        <div style="position:absolute; top:15px; left:15px; display:flex; gap:8px;">
                            <span class="badge" style="background:rgba(255,255,255,0.9); color:#1e293b; font-weight:700;"><i class="fa-solid fa-tag me-1 text-primary"></i> {{ ucfirst($module->type) }}</span>
                            @if($module->difficulty)
                                <span class="badge" style="background:rgba(255,255,255,0.9); color:{{ $module->difficulty == 'beginner' ? '#10b981' : ($module->difficulty == 'intermediate' ? '#f59e0b' : '#ef4444') }}; font-weight:700;">
                                    {{ ucfirst($module->difficulty) }}
                                </span>
                            @endif
                        </div>
                        <div style="position:absolute; bottom:-25px; left:20px; width:50px; height:50px; background:var(--sf); border:2px solid var(--bd); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:var(--pur);">
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
                            <a href="{{ route('user.modules.show', $module->id) }}" class="btn btn-sm" style="background:var(--pur); color:#fff; border-radius:8px; font-weight:600;">
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
@endsection
