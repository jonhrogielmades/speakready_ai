@php
    $prepLoaderKind = $prepLoaderKind ?? 'default';
    $prepLoaderTitle = $prepLoaderTitle ?? 'Preparing Your';
    $prepLoaderTitleAccent = $prepLoaderTitleAccent ?? 'Interview...';
    $prepLoaderDescription = $prepLoaderDescription ?? 'This will just take a few moments.';
    $prepLoaderChecklistLabel = $prepLoaderChecklistLabel ?? 'Prepared interview content';
    $prepLoaderTip = $prepLoaderTip ?? 'Great practice leads to <strong>great performance!</strong>';
    $prepLoaderRows = $prepLoaderRows ?? [
        ['key' => 'details', 'icon' => 'fa-regular fa-file-lines', 'title' => 'Details configured', 'detail' => 'Scenario and target role ready'],
        ['key' => 'structure', 'icon' => 'fa-solid fa-list-check', 'title' => 'Structure configured', 'detail' => 'Question count and timing ready'],
        ['key' => 'camera', 'icon' => 'fa-solid fa-video', 'title' => 'Camera configured', 'detail' => 'Camera preference confirmed'],
        ['key' => 'coaching', 'icon' => 'fa-solid fa-comments', 'title' => 'Coaching configured', 'detail' => 'Feedback mode and question mix ready'],
        ['key' => 'response', 'icon' => 'fa-solid fa-microphone-lines', 'title' => 'Response configured', 'detail' => 'Answer mode ready'],
    ];
@endphp

<div id="setupTransitionOverlay" class="finish-transition-overlay interview-prep-overlay" role="dialog" aria-modal="true" aria-live="polite" aria-atomic="true" aria-labelledby="setupLoadingTitle" aria-describedby="setupLoadingDescription" data-related-prep-loader="{{ $prepLoaderKind }}">
 <div class="interview-prep-shell">
 <span class="interview-prep-logo" aria-hidden="true">
 <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="">
 </span>

 <div class="interview-prep-art" aria-hidden="true">
 <span class="prep-art-ring"></span>
 <span class="prep-art-doc">
 <span class="prep-art-clip"></span>
 <span class="prep-art-avatar"><i class="fa-solid fa-user"></i></span>
 <span class="prep-art-line prep-art-line-1"></span>
 <span class="prep-art-line prep-art-line-2"></span>
 <span class="prep-art-line prep-art-line-3"></span>
 <span class="prep-art-bars"><span></span><span></span><span></span></span>
 </span>
 <span class="prep-art-badge prep-art-badge-left"><i class="fa-solid fa-brain"></i></span>
 <span class="prep-art-badge prep-art-badge-right"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
 <span class="prep-art-lens"><i class="fa-solid fa-check"></i></span>
 <span class="prep-art-handle"></span>
 </div>

 <div class="interview-prep-copy">
 <h4 id="setupLoadingTitle" class="interview-prep-title">{{ $prepLoaderTitle }} <span>{{ $prepLoaderTitleAccent }}</span></h4>
 <p id="setupLoadingDescription" class="interview-prep-subtitle">{{ $prepLoaderDescription }}</p>
 </div>

 <div class="interview-prep-progress" aria-label="Preparing interview progress">
 <div class="interview-prep-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="19">
 <span id="setupLoadingProgressBar" class="interview-prep-progress-bar"></span>
 </div>
 <strong id="setupLoadingPercent" class="interview-prep-progress-percent">19%</strong>
 </div>

 <div class="interview-prep-checklist" id="setupLoadingChecklist" aria-label="{{ $prepLoaderChecklistLabel }}">
 @foreach($prepLoaderRows as $prepLoaderRow)
 <div class="interview-prep-check-row" data-loading-item="{{ $prepLoaderRow['key'] }}">
 <span class="interview-prep-row-icon"><i class="{{ $prepLoaderRow['icon'] }}"></i></span>
 <span class="interview-prep-row-copy">
 <strong data-loading-title>{{ $prepLoaderRow['title'] }}</strong>
 <small data-loading-detail>{{ $prepLoaderRow['detail'] }}</small>
 </span>
 <span class="interview-prep-row-check" aria-label="Pending"><i class="fa-solid fa-check"></i></span>
 </div>
 @endforeach
 </div>

 <div class="interview-prep-tip">
 <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
 <span>{!! $prepLoaderTip !!}</span>
 </div>
 </div>
</div>
