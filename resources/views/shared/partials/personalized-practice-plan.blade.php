@php
    $practicePlanItems = collect($practicePlan ?? [])->take(4)->values();
    $practicePlanSafeCssColor = fn ($value, $fallback = '#3b82f6') => preg_match('/^#[0-9a-fA-F]{3,8}$/', trim((string) $value)) ? trim((string) $value) : $fallback;
@endphp

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
            @if($practicePlanItems->isNotEmpty())
                <div class="practice-plan-list">
                    @foreach($practicePlanItems as $item)
                        <a href="{{ $item->url ?? route('interview.setup') }}" class="practice-plan-row" style="--plan-color: {{ $practicePlanSafeCssColor($item->color ?? null) }};">
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
            @else
                <div class="skill-empty-state">
                    <div>
                        <div class="skill-empty-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <p class="skill-empty-text">Complete a scored interview to generate your practice plan.</p>
                        <a href="{{ route('interview.setup') }}" class="btn btn-outline-primary activity-cta compact"><i class="fa-solid fa-play"></i> Start Practice</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
