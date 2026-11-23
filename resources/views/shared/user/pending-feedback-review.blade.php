@php
    $providerPreview = app(\App\Services\ProviderSessionPreviewService::class)->forSession($sessionRecord);
    $providerSummary = is_array($providerPreview['session_feedback'] ?? null)
        ? $providerPreview['session_feedback'] : null;
    $localSummary = $providerSummary === null
        ? ($localFeedbackReport['session_feedback'] ?? null) : null;
    $summary = $providerSummary ?? $localSummary;
    $isProviderPreview = $providerSummary !== null;
    $providerKey = \App\Services\AIService::normalizeProviderKey($providerPreview['provider_key'] ?? null);
    $providerNames = ['openai' => 'OpenAI', 'gemini' => 'Gemini', 'groq' => 'Groq', 'cohere' => 'Cohere'];
    $providerName = $providerNames[$providerKey] ?? ucfirst($providerKey);
@endphp

@if($summary !== null)
<section class="review-quick-panel premium-panel animate-fade-up" aria-labelledby="pending-feedback-review-title" style="animation-delay:.1s;">
    <div class="review-quick-head">
        <div>
            <div class="review-kicker">{{ $isProviderPreview ? "AI provider session review · {$providerName}" : 'Preliminary local feedback · Current saved answers' }}</div>
            <h4 id="pending-feedback-review-title" style="margin:0 0 .35rem;color:var(--tx);font-weight:800;">{{ $isProviderPreview ? 'AI Feedback Review' : 'Overall Feedback Preview' }}</h4>
            <span style="color:var(--tx3);font-size:.88rem;">{{ $isProviderPreview
                ? 'Provider-written overall feedback is validated against your saved answers. The full report is still being prepared.'
                : (($providerReviewStatus ?? null) === 'failed'
                    ? 'The AI provider review is unavailable right now. This preview uses your saved answers and is not a verified readiness score.'
                    : 'The AI provider review continues in the background. This preview uses your saved answers and is not a verified readiness score.') }}</span>
        </div>
    </div>

    <div class="review-quick-grid">
        <section class="review-quick-block review-quick-block-wide" aria-labelledby="pending-feedback-overall-title">
            <div class="review-block-title" id="pending-feedback-overall-title"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i><span>Overall Answer Summary</span></div>
            <p>{{ $summary['overall_summary'] }}</p>
        </section>
        <section class="review-quick-block" aria-labelledby="pending-feedback-strengths-title">
            <div class="review-block-title review-title-success" id="pending-feedback-strengths-title"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Strengths</span></div>
            <p>{{ $summary['strengths'] }}</p>
        </section>
        <section class="review-quick-block" aria-labelledby="pending-feedback-weaknesses-title">
            <div class="review-block-title review-title-warning" id="pending-feedback-weaknesses-title"><i class="fa-solid fa-bullseye" aria-hidden="true"></i><span>Weaknesses</span></div>
            <p>{{ $summary['weaknesses'] }}</p>
        </section>
        <section class="review-quick-block review-quick-block-wide" aria-labelledby="pending-feedback-next-step-title">
            <div class="review-block-title" id="pending-feedback-next-step-title"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i><span>What to Practice Next</span></div>
            <p>{{ $summary['improvement_suggestions'] }}</p>
        </section>
    </div>
</section>
@endif
