@php
    $practiceAiRecommendations = collect($aiRecommendations ?? $moduleRecommendations ?? [])->take(3)->values();
    $practiceAiSafeCssColor = fn ($value, $fallback = '#f59e0b') => preg_match('/^#[0-9a-fA-F]{3,8}$/', trim((string) $value)) ? trim((string) $value) : $fallback;
    $practiceAiSafeIcon = fn ($value, $fallback = 'fa-lightbulb') => preg_match('/^fa-[a-z0-9-]+$/', trim((string) $value)) ? trim((string) $value) : $fallback;
@endphp

<div class="row g-4 mb-4 practice-ai-recommendations" id="practice-ai-recommendations">
    <div class="col-12">
        <div class="recommend-panel practice-ai-panel" style="--panel-accent:#f59e0b;">
            <div class="recommend-heading practice-ai-heading">
                <div class="recommend-heading-icon"><i class="fa-solid fa-lightbulb"></i></div>
                <div>
                    <h5 class="recommend-title">AI Recommendations</h5>
                    <p class="recommend-subtitle">Next actions based on your performance.</p>
                </div>
                <span class="practice-ai-badge">Personalized for you</span>
            </div>

            @if($practiceAiRecommendations->isNotEmpty())
                <div class="recommend-list practice-ai-list">
                    @foreach($practiceAiRecommendations as $recommendation)
                        <a href="{{ $recommendation->url ?? route('user.modules.index') }}" class="recommend-item practice-ai-item" style="--panel-accent: {{ $practiceAiSafeCssColor($recommendation->color ?? null) }};">
                            <div class="recommend-item-icon"><i class="fa-solid {{ $practiceAiSafeIcon($recommendation->icon ?? null) }}"></i></div>
                            <div>
                                <div class="recommend-item-title">{{ $recommendation->text ?? $recommendation->skill ?? 'Recommended next step' }}</div>
                                <div class="recommend-item-text">{{ $recommendation->reason ?? 'This matches your current interview practice needs.' }}</div>
                            </div>
                            <div class="recommend-arrow"><i class="fa-solid fa-chevron-right"></i></div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="skill-empty-state practice-ai-empty">
                    <div>
                        <div class="skill-empty-icon"><i class="fa-solid fa-lightbulb"></i></div>
                        <p class="skill-empty-text">Complete an interview to get tailored recommendations.</p>
                        <a href="{{ route('interview.setup') }}" class="btn btn-outline-primary activity-cta compact"><i class="fa-solid fa-play"></i> Start Practice</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
