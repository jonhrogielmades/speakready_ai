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
    $recommendations = collect($moduleRecommendations ?? [])->take(3)->values();
    $planItems = collect($practicePlan ?? [])->take(4)->values();
    $safeCssColor = fn ($value, $fallback = '#3b82f6') => preg_match('/^#[0-9a-fA-F]{3,8}$/', trim((string) $value)) ? trim((string) $value) : $fallback;
    $percent = fn ($value) => max(0, min(100, (int) round((float) $value)));
    $moduleColors = ['#0ea5e9', '#7c3aed', '#10b981'];
@endphp

@if($planItems->isNotEmpty())
<div class="row g-4 mb-4" id="personalized-practice-plan">
    <div class="col-12">
        <div class="premium-panel practice-plan-panel" style="--panel-accent:#10b981;">
            <div class="practice-plan-heading">
                <div class="practice-plan-heading-icon"><i class="fa-solid fa-route"></i></div>
                <div>
                    <h5 class="practice-plan-heading-title">Personalized Practice Plan</h5>
                    <p class="practice-plan-heading-text">Next steps from your latest interview and module activity.</p>
                </div>
            </div>
            <div class="practice-plan-list">
                @foreach($planItems as $item)
                    <a href="{{ $item->url ?? route('interview.setup') }}" class="practice-plan-row" style="--plan-color: {{ $safeCssColor($item->color ?? null) }};">
                        <div class="practice-plan-icon"><i class="fa-solid {{ $item->icon ?? 'fa-clipboard-list' }}"></i></div>
                        <div class="practice-plan-copy">
                            <div class="practice-plan-top">
                                <span class="practice-plan-step">{{ $item->day ?? 'Next' }}</span>
                                <span class="practice-plan-title">{{ $item->title ?? 'Practice step' }}</span>
                            </div>
                            <p class="practice-plan-text">{{ $item->action ?? $item->reason ?? 'Complete one focused practice step.' }}</p>
                            @if(! empty($item->tasks))
                                <ul class="practice-plan-tasks">
                                    @foreach(array_slice((array) $item->tasks, 0, 2) as $task)
                                        <li><i class="fa-solid fa-check"></i><span>{{ $task }}</span></li>
                                    @endforeach
                                </ul>
                            @endif
                            <div class="practice-plan-footer">
                                <span class="practice-plan-pill"><i class="fa-regular fa-clock"></i>{{ (int) ($item->minutes ?? 10) }} min</span>
                                <span class="practice-plan-link">{{ $item->cta ?? 'Open' }} <i class="fa-solid fa-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-4 mb-4 progress-live-grid">
    <div class="col-12 col-lg-6 progress-live-card" id="learning-progress">
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

    <div class="col-12 col-lg-6 progress-live-card" id="recommended-next">
        <div class="recommend-panel" style="--panel-accent:#7c3aed;">
            <div class="recommend-heading">
                <div class="recommend-heading-icon"><i class="fa-solid fa-compass"></i></div>
                <div>
                    <h5 class="recommend-title">Recommended Next</h5>
                    <p class="recommend-subtitle">Modules selected from your latest progress signals.</p>
                </div>
            </div>
            <div class="recommend-list">
                @forelse($recommendations as $recommendation)
                    <a href="{{ $recommendation->url ?? route('user.modules.index') }}" class="recommend-item" style="--panel-accent: {{ $safeCssColor($recommendation->color ?? null, '#7c3aed') }};">
                        <div class="recommend-item-icon"><i class="fa-solid {{ $recommendation->icon ?? 'fa-lightbulb' }}"></i></div>
                        <div>
                            <div class="recommend-item-title">{{ $recommendation->text ?? $recommendation->skill ?? 'Recommended module' }}</div>
                            <div class="recommend-item-text">{{ $recommendation->reason ?? 'This matches your current interview practice needs.' }}</div>
                        </div>
                        <div class="recommend-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                    </a>
                @empty
                    <a href="{{ route('user.modules.index') }}" class="recommend-item">
                        <div class="recommend-item-icon"><i class="fa-solid fa-book"></i></div>
                        <div>
                            <div class="recommend-item-title">Explore learning modules</div>
                            <div class="recommend-item-text">Published modules will appear here as recommendations.</div>
                        </div>
                        <div class="recommend-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                    </a>
                @endforelse
            </div>
        </div>
    </div>
</div>
