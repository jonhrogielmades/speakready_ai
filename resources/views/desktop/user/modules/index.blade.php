@extends('desktop.layouts.app')
@section('title', 'Interview Modules')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/modules/index.css?v=10') }}" data-page-style="user-modules-index">
@endpush

@section('content')
@include('desktop.partials.page-hero-styles')
@php
 $selectedModulePosition = $selectedModulePosition?? '';
 $modulePositionOptions = collect($modulePositionOptions?? []);
 $modulePositionValue = old('target_position', $selectedModulePosition);
 $showModulePositionModal = (bool) ($showModulePositionModal?? false);
 $usingGeneralModuleFallback = (bool) ($usingGeneralModuleFallback?? false);
@endphp

<div class="db-section active" id="interview-modules-page">
 <div class="sr-page-hero modules-page-hero" aria-labelledby="modules-hero-title">
 <div class="sr-page-hero-inner">
 <div class="sr-page-hero-copy">
 <div class="modules-page-hero-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><circle cx="8" cy="6" r="2" fill="#eff6ff" stroke="currentColor" stroke-width="2"/><circle cx="15" cy="12" r="2" fill="#eff6ff" stroke="currentColor" stroke-width="2"/><circle cx="11" cy="18" r="2" fill="#eff6ff" stroke="currentColor" stroke-width="2"/></svg>
 </div>
 <div>
 <h4 id="modules-hero-title" class="sr-page-hero-title text-gradient-primary">
 Interview Modules
 </h4>
 <p class="sr-page-hero-subtitle">Open action modules that tell you what to prepare, write, rehearse, revise, and check before your interview.</p>
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
 <div class="modal-dialog modal-dialog-centered module-position-dialog">
 <form action="{{ route('user.modules.position') }}" method="POST" class="modal-content">
 @csrf
 <input type="hidden" name="category" value="{{ $currentCategory }}">
 <input type="hidden" name="search" value="{{ $currentSearch }}">
 <div class="modal-header">
 <button type="button" class="btn-close module-position-close-right" data-bs-dismiss="modal" aria-label="Close"></button>
 <div class="module-position-title-wrap">
 <span class="module-position-title-icon" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></span>
 <div class="module-position-heading">
 <div class="module-position-kicker">Interview Modules</div>
 <h5 class="modal-title" id="modulePositionModalTitle">What position are you applying for?</h5>
 </div>
 </div>
 </div>
 <div class="modal-body">
 <div class="module-position-field">
 <label for="moduleTargetPosition" class="form-label">Target position</label>
 <div class="module-position-select-shell" data-position-select>
 <input type="hidden" id="moduleTargetPosition" name="target_position" value="{{ $modulePositionValue }}" data-position-select-input>
 <button type="button" class="module-position-select-button @error('target_position') is-invalid @enderror" id="moduleTargetPositionButton" aria-haspopup="listbox" aria-expanded="false" aria-controls="moduleTargetPositionMenu">
 <span data-position-select-label>{{ $modulePositionValue!== ''? $modulePositionValue: 'Choose a target position' }}</span>
 <span class="module-position-select-icon" aria-hidden="true"><i class="fa-solid fa-chevron-down"></i></span>
 </button>
 <div class="module-position-dropdown" id="moduleTargetPositionMenu" role="listbox" aria-labelledby="moduleTargetPositionButton">
 @if($modulePositionValue!== '' && ! $modulePositionOptions->contains(fn ($positionOption): bool => strcasecmp((string) $positionOption, (string) $modulePositionValue) === 0))
 <button type="button" class="module-position-option is-selected" role="option" aria-selected="true" data-position-option="{{ $modulePositionValue }}">{{ $modulePositionValue }}</button>
 @endif
 @foreach($modulePositionOptions as $positionOption)
 @php
 $moduleOptionSelected = strcasecmp((string) $positionOption, (string) $modulePositionValue) === 0;
 @endphp
 <button type="button" class="module-position-option {{ $moduleOptionSelected? 'is-selected': '' }}" role="option" aria-selected="{{ $moduleOptionSelected? 'true': 'false' }}" data-position-option="{{ $positionOption }}">{{ $positionOption }}</button>
 @endforeach
 </div>
 </div>
 @error('target_position')
 <div class="invalid-feedback d-block">{{ $message }}</div>
 @enderror

 </div>
 </div>
 <div class="modal-footer module-position-actions">
 @if($selectedModulePosition!== '')
 <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
 @endif
 <button type="submit" class="btn btn-primary module-position-submit-btn">
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

 document.querySelectorAll('[data-position-select]').forEach(selectShell => {
 const input = selectShell.querySelector('[data-position-select-input]');
 const button = selectShell.querySelector('.module-position-select-button');
 const label = selectShell.querySelector('[data-position-select-label]');
 const options = Array.from(selectShell.querySelectorAll('[data-position-option]'));
 const form = selectShell.closest('form');
 if (!input || !button || !label) return;

 const closeMenu = () => {
 selectShell.classList.remove('is-open');
 button.setAttribute('aria-expanded', 'false');
 };

 const openMenu = () => {
 selectShell.classList.add('is-open');
 button.setAttribute('aria-expanded', 'true');
 };

 button.addEventListener('click', event => {
 event.stopPropagation();
 selectShell.classList.contains('is-open')? closeMenu(): openMenu();
 });

 options.forEach(option => {
 option.addEventListener('click', () => {
 input.value = option.dataset.positionOption || option.textContent.trim();
 label.textContent = input.value || 'Choose a target position';
 button.classList.remove('is-invalid');
 options.forEach(candidate => {
 const isSelected = candidate === option;
 candidate.classList.toggle('is-selected', isSelected);
 candidate.setAttribute('aria-selected', isSelected? 'true': 'false');
 });
 closeMenu();
 button.focus();
 });
 });

 document.addEventListener('click', event => {
 if (!selectShell.contains(event.target)) closeMenu();
 });

 document.addEventListener('keydown', event => {
 if (event.key === 'Escape') closeMenu();
 });

 if (form) {
 form.addEventListener('submit', event => {
 if (input.value.trim() !== '') return;
 event.preventDefault();
 button.classList.add('is-invalid');
 openMenu();
 button.focus();
 });
 }
 });

 });

 document.addEventListener('DOMContentLoaded', function() {
 if (typeof window.createSpeakReadyTour!== 'function') return;

 const stepsMobile = [
 { element: '#modulePositionModal.show .modal-content', popover: { title: 'Choose Your Module Focus', description: 'Pick the target position so modules can match the interview path you are preparing for.', side: 'bottom', align: 'center' }},
 { element: '#modulePositionModal.show #moduleTargetPositionButton, #modulePositionModal.show #moduleTargetPosition', popover: { title: 'Target Position', description: 'Select the role or program you want these modules to support.', side: 'bottom', align: 'start' }},
 { element: '#modulePositionModal.show .module-position-submit-btn, #modulePositionModal.show .modal-footer .btn-primary', popover: { title: 'View Related Modules', description: 'Load modules connected to that target position.', side: 'top', align: 'center' }},
 { element: 'body:not(.modal-open) #interview-modules-page .modules-page-hero, body:not(.modal-open) #interview-modules-page .modules-hero', popover: { title: 'Interview Modules', description: 'Use modules for focused preparation tasks like planning examples, improving structure, and polishing interview answers.', side: 'bottom', align: 'center' }},
 { element: 'body:not(.modal-open) .module-position-strip', popover: { title: 'Target Position', description: 'This controls which role-specific modules appear. Change it whenever your interview target changes.', side: 'bottom', align: 'center' }},
 { element: 'body:not(.modal-open) #moduleFiltersForm', popover: { title: 'Find A Module', description: 'Search by topic or skill, or filter by module category to narrow the list.', side: 'bottom', align: 'center' }},
 { element: 'body:not(.modal-open) .module-smart-row', popover: { title: 'Smart Suggestions', description: 'Recommendations and learning paths pull from your interview performance and module progress when available.', side: 'top', align: 'center' }},
 { element: 'body:not(.modal-open) .modules-card-grid', popover: { title: 'Module Library', description: 'Browse the module cards to find concrete preparation work for your next interview.', side: 'top', align: 'center' }},
 { element: 'body:not(.modal-open) .module-card-link', popover: { title: 'Open Action Module', description: 'Open a module to work through its preparation actions and update your progress.', side: 'top', align: 'center' }}
 ];

 const stepsDesktop = [
 { element: '#modulePositionModal.show .modal-content', popover: { title: 'Choose Your Module Focus', description: 'Pick the target position so modules can match the interview path you are preparing for.', side: 'bottom', align: 'center' }},
 { element: '#modulePositionModal.show #moduleTargetPositionButton, #modulePositionModal.show #moduleTargetPosition', popover: { title: 'Target Position', description: 'Select the role or program you want these modules to support.', side: 'bottom', align: 'start' }},
 { element: '#modulePositionModal.show .module-position-submit-btn, #modulePositionModal.show .modal-footer .btn-primary', popover: { title: 'View Related Modules', description: 'Load modules connected to that target position.', side: 'top', align: 'center' }},
 { element: 'body:not(.modal-open) #interview-modules-page .modules-page-hero, body:not(.modal-open) #interview-modules-page .modules-hero', popover: { title: 'Interview Modules', description: 'Use modules for focused preparation tasks like planning examples, improving structure, and polishing interview answers.', side: 'bottom', align: 'center' }},
 { element: 'body:not(.modal-open) .module-position-strip', popover: { title: 'Target Position', description: 'This controls which role-specific modules appear. Change it whenever your interview target changes.', side: 'bottom', align: 'center' }},
 { element: 'body:not(.modal-open) #moduleFiltersForm', popover: { title: 'Find A Module', description: 'Search by topic or skill, or filter by module category to narrow the list.', side: 'bottom', align: 'center' }},
 { element: 'body:not(.modal-open) .module-smart-row', popover: { title: 'Smart Suggestions', description: 'Recommendations and learning paths pull from your interview performance and module progress when available.', side: 'top', align: 'center' }},
 { element: 'body:not(.modal-open) .modules-card-grid', popover: { title: 'Module Library', description: 'Browse the module cards to find concrete preparation work for your next interview.', side: 'top', align: 'center' }},
 { element: 'body:not(.modal-open) .module-card-link', popover: { title: 'Open Action Module', description: 'Open a module to work through its preparation actions and update your progress.', side: 'top', align: 'center' }}
 ];

 window.createSpeakReadyTour({
 completionKey: 'onboarding_completed_interview_modules',
 serverDetectedMobile: false,
 stepsMobile,
 stepsDesktop,
 autoStart: false,
 autoStartDelay: 500,
 });
 });
</script>
@endpush
@endsection
