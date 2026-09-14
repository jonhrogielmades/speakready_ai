@php
    $learningRecords = collect($learningProgress ?? []);
    $learningItems = $learningRecords
        ->filter(fn ($progress) => $progress && $progress->learningModule)
        ->take(3)
        ->values();
    $learningTotal = $learningRecords->count();
    $learningCompleted = $learningRecords
        ->filter(fn ($progress) => (int) ($progress->progress_percentage ?? 0) >= 100)
        ->count();
    $learningAverage = $learningRecords->isNotEmpty()
        ? (int) round($learningRecords->avg(fn ($progress) => (int) ($progress->progress_percentage ?? 0)))
        : 0;
    $percent = fn ($value) => max(0, min(100, (int) round((float) $value)));
    $moduleColors = ['#0ea5e9', '#7c3aed', '#10b981'];
    $progressCategoryItems = collect($categoryPerf ?? []);
    $progressCategoryColors = ['#22c55e', '#3b82f6', '#06b6d4', '#f59e0b', '#8b5cf6'];
@endphp

<div class="progress-live-grid" style="gap: 18px !important; margin-top: 18px !important; margin-bottom: 18px !important;">
    <div class="progress-live-card" id="learning-progress">
        <div class="learning-panel" style="--panel-accent:#0ea5e9;">
            <div class="learning-heading">
                <div class="learning-heading-icon"><i class="fa-solid fa-book-open"></i></div>
                <div>
                    <h5 class="learning-title">Learning Progress</h5>
                    <p class="learning-subtitle">Module work tied to your interview readiness.</p>
                </div>
            </div>

            @if($learningItems->isNotEmpty())
                <div class="learning-list">
                    @foreach($learningItems as $item)
                        @php
                            $module = $item->learningModule;
                            $itemPercent = $percent($item->progress_percentage ?? 0);
                        @endphp
                        <a href="{{ route('user.modules.show', $module->id) }}" class="learning-module" style="--module-color: {{ $moduleColors[$loop->index % count($moduleColors)] }}; text-decoration: none;">
                            <div class="learning-module-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                            <div>
                                <div class="learning-module-top">
                                    <span class="learning-module-title">{{ $module->title }}</span>
                                    <span class="learning-percent">{{ $itemPercent }}%</span>
                                </div>
                                <div class="learning-track">
                                    <div class="learning-fill" style="--learning-progress: {{ $itemPercent }}%;"></div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="learning-summary">
                    <div class="learning-summary-icon"><i class="fa-solid fa-chart-simple"></i></div>
                    <div>
                        <div class="learning-summary-value">{{ $learningAverage }}%</div>
                        <div class="learning-summary-label">{{ $learningCompleted }}/{{ $learningTotal }} modules completed</div>
                    </div>
                </div>
            @else
                <div class="skill-empty-state">
                    <div>
                        <div class="skill-empty-icon"><i class="fa-solid fa-book-open-reader"></i></div>
                        <p class="skill-empty-text">Open a learning module to connect lessons with your progress.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="progress-live-card animate-fade-up" id="category-performance-summary" style="animation-delay: 0.55s;">
        <div class="premium-panel progress-category-panel" style="--panel-accent:#10b981;">
            <div class="progress-panel-heading">
                <div class="progress-panel-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div>
                    <h5 class="progress-panel-title">Category Performance</h5>
                    <p class="progress-panel-subtitle">Where your interview scores are strongest.</p>
                </div>
            </div>

            @if($progressCategoryItems->isNotEmpty())
                <div class="progress-category-list">
                    @foreach($progressCategoryItems as $categoryName => $score)
                        @php
                            $categoryScore = max(0, min(100, (int) round($score)));
                            $categoryColor = $progressCategoryColors[$loop->index % count($progressCategoryColors)];
                        @endphp
                        <div class="progress-category-row" style="--category-color: {{ $categoryColor }}; --category-value: {{ $categoryScore }}%;">
                            <div class="progress-category-top">
                                <span class="progress-category-name">{{ $categoryName }}</span>
                                <span class="progress-category-score">{{ $categoryScore }}%</span>
                            </div>
                            <div class="progress-category-track" aria-hidden="true"><span></span></div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="skill-empty-state progress-category-empty">
                    <div>
                        <div class="skill-empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                        <p class="skill-empty-text">Complete an interview session to unlock category performance.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
