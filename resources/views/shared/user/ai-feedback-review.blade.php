@php
    // The controller has already checked the saved report against current answers.
    // This view adds a provenance check before displaying any AI review prose.
    $savedFeedback = $sessionRecord->feedback;
    $savedSummary = is_array($savedFeedback?->coaching_summary) ? $savedFeedback->coaching_summary : [];
    $reviewContext = (array) ($savedSummary['review_context'] ?? []);
    $providerKey = \App\Services\AIService::normalizeProviderKey($reviewContext['provider_key'] ?? null);
    $isProviderReport = ! ($providerReviewPending ?? true)
        && data_get($reviewContext, 'detailed_review_validation.status') === 'verified'
        && ($savedSummary['overall_summary_source'] ?? null) === 'ai_provider_validated'
        && ($reviewContext['provider_source'] ?? null) === 'ai_provider'
        && ($reviewContext['session_feedback_source'] ?? null) === 'ai_provider'
        && ! (bool) ($reviewContext['local_fallback'] ?? false)
        && $providerKey !== ''
        && ! in_array($providerKey, ['local', 'localmodel'], true)
        && \App\Services\AIService::providerIsSupported($providerKey);

    $providerNames = ['openai' => 'OpenAI', 'gemini' => 'Gemini', 'groq' => 'Groq', 'cohere' => 'Cohere'];
    $providerName = $providerNames[$providerKey] ?? ucfirst($providerKey);
    $overallSummary = review_feedback_without_question_text(trim((string) ($savedSummary['overall_summary'] ?? '')));
    $strengths = review_feedback_without_question_text(trim((string) ($savedFeedback?->strengths ?? '')));
    $weaknesses = review_feedback_without_question_text(trim((string) ($savedFeedback?->weaknesses ?? '')));
    $suggestions = review_feedback_without_question_text(trim((string) ($savedFeedback?->improvement_suggestions ?? '')));
    $score = is_numeric($sessionRecord->score?->overall_readiness_score)
        ? max(0, min(100, (int) round($sessionRecord->score->overall_readiness_score)))
        : null;
    $scoreColor = $score === null ? '#64748b' : ($score >= 80 ? '#10b981' : ($score >= 60 ? '#3b82f6' : '#f59e0b'));
    $scoreBreakdown = is_array($report['category_breakdown'] ?? null) ? $report['category_breakdown'] : [];
@endphp

@if($isProviderReport)
<section class="review-quick-panel premium-panel animate-fade-up" aria-labelledby="ai-feedback-review-title" style="animation-delay:.1s;">
    <div class="review-quick-head">
        <div>
            <div class="review-kicker">Feedback Detailed Review · Session summary: {{ $providerName }}</div>
            <h4 id="ai-feedback-review-title" style="margin:0 0 .35rem;color:var(--tx);font-weight:800;">AI Feedback Review</h4>
            <span style="color:var(--tx3);font-size:.88rem;">Overall Review · Provider response validated against your saved answers</span>
        </div>
        <div class="review-overall-score" style="--review-score-color: {{ $scoreColor }};">
            <strong>{{ $score === null ? 'Pending' : $score.'%' }}</strong>
            <span>{{ $sessionRecord->score?->readiness_band ?: 'Overall score' }}</span>
        </div>
    </div>

    <div class="review-quick-grid">
        <section class="review-quick-block review-quick-block-wide" aria-labelledby="ai-feedback-overall-title">
            <div class="review-block-title" id="ai-feedback-overall-title"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i><span>All Answer Review Summary</span></div>
            <p>{{ $overallSummary !== '' ? $overallSummary : 'The provider did not supply a usable summary.' }}</p>
        </section>

        @if($scoreBreakdown !== [])
        <section class="review-quick-block review-quick-block-wide review-score-breakdown" aria-labelledby="ai-feedback-scores-title">
            <div class="review-block-title" id="ai-feedback-scores-title"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i><span>Score Breakdown</span></div>
            <div class="review-score-grid">
                @foreach($scoreBreakdown as $metric)
                    @if(is_array($metric) && is_numeric($metric['score'] ?? null))
                    <div class="review-score-item" style="--metric-color: {{ $metric['color'] ?? '#3b82f6' }};">
                        <span>{{ $metric['name'] ?? 'Skill' }}</span>
                        <strong>{{ max(0, min(100, (int) round($metric['score']))) }}%</strong>
                    </div>
                    @endif
                @endforeach
            </div>
        </section>
        @endif

        @if($strengths !== '')
        <section class="review-quick-block" aria-labelledby="ai-feedback-strengths-title">
            <div class="review-block-title review-title-success" id="ai-feedback-strengths-title"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Strengths</span></div>
            <p>{{ $strengths }}</p>
        </section>
        @endif

        @if($weaknesses !== '')
        <section class="review-quick-block" aria-labelledby="ai-feedback-weaknesses-title">
            <div class="review-block-title review-title-warning" id="ai-feedback-weaknesses-title"><i class="fa-solid fa-bullseye" aria-hidden="true"></i><span>Weaknesses</span></div>
            <p>{{ $weaknesses }}</p>
        </section>
        @endif

        @if($suggestions !== '')
        <section class="review-quick-block review-quick-block-wide" aria-labelledby="ai-feedback-next-step-title">
            <div class="review-block-title" id="ai-feedback-next-step-title"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i><span>What to practice next</span></div>
            <p>{{ $suggestions }}</p>
        </section>
        @endif
    </div>
</section>
@else
<div class="alert mb-4" role="status" style="background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.24);color:var(--tx);border-radius:14px;">
    A validated AI provider review is not available for this session yet.
</div>
@endif
