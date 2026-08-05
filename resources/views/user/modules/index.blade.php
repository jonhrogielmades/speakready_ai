@extends(isset($isMobile) && $isMobile ? 'layouts.app-mobile' : 'layouts.app')
@section('title', 'Philippines Interview Modules')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/' . (($isMobile ?? false) ? 'mobile' : 'desktop') . '/user/modules/index.css?v=1') }}" data-page-style="user-modules-index">
@endpush

@section('content')
@include('partials.page-hero-styles')

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
