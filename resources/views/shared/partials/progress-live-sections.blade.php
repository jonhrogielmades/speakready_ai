@php
    $progressCategoryItems = collect($categoryPerf ?? []);
    $progressCategoryColors = ['#22c55e', '#3b82f6', '#06b6d4', '#f59e0b', '#8b5cf6'];
@endphp

<div class="progress-live-grid">
    <div class="progress-live-card" id="skill-tracker">
        <div class="premium-panel progress-chart-panel" style="height:100%; --panel-accent:#8b5cf6;">
            <div class="progress-panel-heading">
                <div class="progress-panel-icon"><i class="fa-solid fa-chart-simple"></i></div>
                <div>
                    <h5 class="progress-panel-title">Skill Improvement Tracker</h5>
                    <p class="progress-panel-subtitle">Track your progress in key interview skills.</p>
                </div>
            </div>

            @if(count($skillComparison) > 0)
                @foreach($skillComparison as $metric)
                    <div class="skill-metric-row">
                        <div class="skill-metric-top">
                            <span class="skill-metric-label">{{ $metric['label'] }}</span>
                            <span class="skill-metric-value">{{ $metric['previous'] }}% <i class="fa-solid fa-arrow-right mx-1" style="font-size:0.8em"></i> {{ $metric['current'] }}%
                                @if($metric['delta'] >= 0)
                                    <span class="text-success ms-1">(+{{ $metric['delta'] }}%)</span>
                                @else
                                    <span class="text-danger ms-1">({{ $metric['delta'] }}%)</span>
                                @endif
                            </span>
                        </div>
                        <div class="skill-metric-bar">
                            <div class="skill-metric-fill" role="progressbar" style="width: {{ $metric['bar'] }}%;"></div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="skill-empty-state">
                    <div>
                        <div class="skill-empty-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        <p class="skill-empty-text">Complete multiple practice interviews to track your specific skill improvements.</p>
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
