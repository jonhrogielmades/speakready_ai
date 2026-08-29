@php
    $conciseness = is_array($report['conciseness'] ?? null) ? $report['conciseness'] : [];
    $repeatedWords = is_array($conciseness['repeated_words'] ?? null) ? $conciseness['repeated_words'] : [];
    $panelClass = trim((string) ($panelClass ?? ''));
    $animationDelay = $animationDelay ?? null;
@endphp

<div class="row mb-4">
    <div class="col-12 {{ $animationDelay ? 'animate-fade-up' : '' }}" @if($animationDelay) style="animation-delay: {{ $animationDelay }};" @endif>
        <div class="{{ $panelClass }}" style="background:rgba(245,158,11,.045) !important;border:1px solid rgba(245,158,11,.20) !important;border-radius:18px;padding:24px;">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                <div>
                    <h5 style="color:var(--tx);font-weight:800;margin-bottom:6px;"><i class="fa-solid fa-scissors me-2" style="color:#f59e0b;"></i>Conciseness Check</h5>
                    <p style="color:var(--tx3);font-size:.9rem;line-height:1.5;margin:0;">{{ $conciseness['trim_target'] ?? 'Keep each answer direct and specific.' }}</p>
                </div>
                <span class="badge align-self-start" style="background:rgba(245,158,11,.14);color:#f59e0b;border:1px solid rgba(245,158,11,.26);padding:8px 11px;">{{ $conciseness['band'] ?? 'Not checked' }}</span>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div style="color:var(--tx3);font-size:.76rem;font-weight:800;text-transform:uppercase;margin-bottom:6px;">Average Answer</div>
                    <div style="color:var(--tx);font-size:1.15rem;font-weight:800;">{{ (int) ($conciseness['average_words'] ?? 0) }} words</div>
                </div>
                <div class="col-md-8">
                    <div style="color:var(--tx3);font-size:.76rem;font-weight:800;text-transform:uppercase;margin-bottom:8px;">Repeated Words</div>
                    @if(!empty($repeatedWords))
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($repeatedWords as $word)
                                <span class="badge" style="background:rgba(59,130,246,.10);color:#3b82f6;border:1px solid rgba(59,130,246,.22);padding:7px 10px;">{{ $word['word'] ?? '' }} x{{ (int) ($word['count'] ?? 0) }}</span>
                            @endforeach
                        </div>
                    @else
                        <div style="color:var(--tx2);font-size:.9rem;">No heavy repetition found.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
