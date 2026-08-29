@php
    $overview = is_array($report['overview'] ?? null) ? $report['overview'] : [];
    $suggestionItems = array_slice(array_values(array_filter($report['suggestion_items'] ?? [])), 0, 3);
    $panelClass = trim((string) ($panelClass ?? ''));
    $animationDelay = $animationDelay ?? null;
@endphp

<div class="row mb-4">
    <div class="col-12 {{ $animationDelay ? 'animate-fade-up' : '' }}" @if($animationDelay) style="animation-delay: {{ $animationDelay }};" @endif>
        <div class="{{ $panelClass }} feedback-hero-panel" style="background:linear-gradient(145deg, rgba(59,130,246,.10), rgba(139,92,246,.08)) !important;border:1px solid rgba(59,130,246,.20) !important;border-radius:18px;padding:28px;">
            <div class="row g-3 w-100 feedback-hero-grid">
                <div class="col-md-4">
                    <h5 style="color:var(--tx);font-weight:800;margin-bottom:10px;"><i class="fa-solid fa-clipboard-check me-2 text-primary"></i>Overall Summary</h5>
                    <p style="color:var(--tx);font-size:.96rem;line-height:1.58;margin:0;text-align:left;text-justify:auto;">{{ $overview['summary'] ?? 'Feedback is ready. Review the focus area and retry one answer.' }}</p>
                </div>
                <div class="col-md-4">
                    <h5 style="color:var(--tx);font-weight:800;margin-bottom:10px;"><i class="fa-solid fa-bullseye me-2" style="color:#f59e0b;"></i>Key Focus</h5>
                    <p style="color:var(--tx);font-size:.96rem;line-height:1.58;margin:0;text-align:left;text-justify:auto;">
                        <strong>{{ $overview['focus_label'] ?? 'Answer Structure' }}{{ is_numeric($overview['focus_score'] ?? null) ? ' '.$overview['focus_score'].'%' : '' }}:</strong>
                        {{ $overview['focus_advice'] ?? 'Use one idea, one example, and one result.' }}
                    </p>
                </div>
                <div class="col-md-4 feedback-hero-actions" style="border-left:1px solid rgba(59,130,246,.20);">
                    <h5 style="color:var(--tx);font-weight:800;margin-bottom:10px;"><i class="fa-solid fa-location-arrow me-2 text-primary"></i>Next Step</h5>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0" style="color:var(--tx);font-size:.94rem;line-height:1.5;">
                        @foreach($suggestionItems as $item)
                            <li class="d-flex gap-2">
                                <i class="fa-solid fa-arrow-right mt-1" style="color:#3b82f6;font-size:.74rem;"></i>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
