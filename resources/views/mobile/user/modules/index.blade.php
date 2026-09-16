@extends('mobile.layouts.app')
@section('title', 'Interview Modules')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/mobile/user/modules/index.css?v=2') }}" data-page-style="user-modules-index">
@endpush

@section('content')
@include('mobile.partials.page-hero-styles')
@php
 $selectedModulePosition = $selectedModulePosition?? '';
 $modulePositionOptions = collect($modulePositionOptions?? []);
 $modulePositionValue = old('target_position', $selectedModulePosition);
 $showModulePositionModal = (bool) ($showModulePositionModal?? false);
 $usingGeneralModuleFallback = (bool) ($usingGeneralModuleFallback?? false);
@endphp

<div class="db-section active" id="interview-modules-page">
 <div class="modules-hero" aria-labelledby="modules-hero-title">
 <span class="modules-hero-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24"><path d="M6 4.5h9.5A2.5 2.5 0 0 1 18 7v12.5H7.5A2.5 2.5 0 0 1 5 17V6.5A2 2 0 0 1 7 4.5Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8 8h7M8 11.5h7M8 15h4.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7.5 19.5A2.5 2.5 0 0 1 10 17h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
 </span>
 <div class="modules-hero-copy">
 <h1 id="modules-hero-title" class="modules-hero-title">Interview Modules</h1>
 <p class="modules-hero-subtitle">Open action modules that tell you what to prepare, write, rehearse, revise, and check before your interview.</p>
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
 <div class="module-position-strip">
 <div class="module-position-summary">
 <span class="module-position-icon"><i class="fa-solid fa-user-tie"></i></span>
 <div>
 <div class="module-position-label">Target Position</div>
 <strong>{{ $selectedModulePosition!== ''? $selectedModulePosition: 'Choose your position' }}</strong>
 </div>
 </div>
 <button type="button" class="module-position-change-btn" data-bs-toggle="modal" data-bs-target="#modulePositionModal">
 <i class="fa-solid fa-pen-to-square"></i>
 <span>{{ $selectedModulePosition!== ''? 'Change': 'Choose' }}</span>
 </button>
 </div>
 @if($usingGeneralModuleFallback)
 <div class="module-position-note">
 <i class="fa-solid fa-circle-info"></i>
 <span>Showing broad interview modules while role-specific modules for {{ $selectedModulePosition }} are unavailable.</span>
 </div>
 @endif
 @php
 $currentCategory = $selectedCategory?? request('category', '');
 $currentSearch = $search?? request('search', '');
 $hasModuleFilters = trim((string) $currentCategory)!== '' || trim((string) $currentSearch)!== '';
 @endphp
 <form id="moduleFiltersForm" class="module-filter-bar" action="{{ route('user.modules.index') }}" method="GET" role="search">
 <div class="module-search-shell">
 <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
 <input id="moduleSearchInput" class="module-search-input" type="search" name="search" value="{{ $currentSearch }}" placeholder="Search modules, skills, or topics" autocomplete="off" aria-label="Search interview modules">
 </div>
 <div class="module-topic-select-shell">
 <select id="moduleTopicSelect" name="category" class="module-topic-select" aria-label="Select module topic">
 <option value="" {{ $currentCategory === ''? 'selected': '' }}>All Topics</option>
 @foreach($categories as $category)
 <option value="{{ $category }}" {{ $currentCategory === $category? 'selected': '' }}>{{ $category }}</option>
 @endforeach
 </select>
 </div>
 <button type="submit" class="module-filter-submit"><i class="fa-solid fa-filter" aria-hidden="true"></i><span>Search</span></button>
 @if($hasModuleFilters)
 <a id="moduleClearFilters" href="{{ route('user.modules.index') }}" class="module-filter-clear">Clear</a>
 @endif
 </form>

 @if((isset($moduleRecommendations) && $moduleRecommendations->count() > 0) || (isset($learningPaths) && $learningPaths->count() > 0))
 <div class="module-smart-row">
 @if(isset($moduleRecommendations) && $moduleRecommendations->count() > 0)
 <section class="module-smart-panel" aria-labelledby="module-recommendations-title">
 <div class="module-smart-head">
 <div>
 <h5 id="module-recommendations-title" class="module-smart-title"><i class="fa-solid fa-wand-magic-sparkles me-2" style="color:#f59e0b"></i>Recommended For You</h5>
 <p class="module-smart-subtitle">Suggested from your latest interview scores, feedback, and module progress.</p>
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
 <p class="module-smart-subtitle">Track completion by topic so your interview preparation stays ordered.</p>
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

<div class="modal fade module-position-modal" id="modulePositionModal" tabindex="-1" aria-labelledby="modulePositionModalTitle" aria-hidden="true" data-show-on-load="{{ ($showModulePositionModal || $errors->has('target_position'))? 'true': 'false' }}" data-require-choice="{{ $selectedModulePosition === ''? 'true': 'false' }}">
 <div class="modal-dialog modal-dialog-centered">
 <form action="{{ route('user.modules.position') }}" method="POST" class="modal-content">
 @csrf
 <input type="hidden" name="category" value="{{ $currentCategory }}">
 <input type="hidden" name="search" value="{{ $currentSearch }}">
 <div class="modal-header">
 <div>
 <div class="module-position-kicker">Interview Modules</div>
 <h5 class="modal-title" id="modulePositionModalTitle">What position are you applying for?</h5>
 </div>
 @if($selectedModulePosition!== '')
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 @endif
 </div>
 <div class="modal-body">
 <label for="moduleTargetPosition" class="form-label">Target position</label>
 <select class="form-control module-position-input @error('target_position') is-invalid @enderror" id="moduleTargetPosition" name="target_position" required>
 <option value="" disabled {{ $modulePositionValue === ''? 'selected': '' }}>Choose a target position</option>
 @if($modulePositionValue!== '' && ! $modulePositionOptions->contains(fn ($positionOption): bool => strcasecmp((string) $positionOption, (string) $modulePositionValue) === 0))
 <option value="{{ $modulePositionValue }}" selected>{{ $modulePositionValue }}</option>
 @endif
 @foreach($modulePositionOptions as $positionOption)
 <option value="{{ $positionOption }}" {{ strcasecmp((string) $positionOption, (string) $modulePositionValue) === 0? 'selected': '' }}>{{ $positionOption }}</option>
 @endforeach
 </select>
 @error('target_position')
 <div class="invalid-feedback">{{ $message }}</div>
 @enderror

 </div>
 <div class="modal-footer">
 @if($selectedModulePosition!== '')
 <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
 @endif
 <button type="submit" class="btn btn-primary">
 <i class="fa-solid fa-filter me-1"></i> View Related Modules
 </button>
 </div>
 </form>
 </div>
</div>
@push('scripts')
<script>
 document.addEventListener('DOMContentLoaded', function () {
 const topicSelect = document.getElementById('moduleTopicSelect');
 const filtersForm = document.getElementById('moduleFiltersForm');
 if (topicSelect && filtersForm) {
 topicSelect.addEventListener('change', function () {
 filtersForm.submit();
 });
 }

 const positionModal = document.getElementById('modulePositionModal');
 if (positionModal && window.bootstrap && bootstrap.Modal && positionModal.dataset.showOnLoad === 'true') {
 const requireChoice = positionModal.dataset.requireChoice === 'true';
 new bootstrap.Modal(positionModal, {
 backdrop: requireChoice? 'static': true,
 keyboard:!requireChoice
 }).show();
 }

 });
</script>
@endpush
@endsection
