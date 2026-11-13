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
    $cameraFeedbackItems = $sessionRecord->answers
        ->map(fn ($answer) => data_get($answer->coaching_feedback ?? [], 'camera_feedback'))
        ->filter(fn ($camera): bool => is_array($camera)
            && in_array((string) ($camera['status'] ?? ''), ['measured', 'insufficient_data'], true));
    $camera = $cameraFeedbackItems->first(fn (array $item): bool => ($item['status'] ?? '') === 'measured')
        ?? $cameraFeedbackItems->first();
    $cameraSummary = null;
    if (is_array($camera)) {
        $cameraObservation = is_scalar($camera['observation'] ?? null)
            ? trim((string) $camera['observation'])
            : '';
        if ($cameraObservation === '') {
            $cameraObservation = 'Camera feedback was attempted, but there was not enough camera data for a full note.';
        }
        $cameraObservation = str_replace(
            ['the head looked camera-facing', 'camera-facing'],
            ['head direction looked toward the camera', 'toward the camera'],
            $cameraObservation
        );
        $cameraObservation = trim(preg_replace(
            '/(?:^|\s+)[^.!?]*(?:hand|hands|gesture|gestures)[^.!?]*[.!?]/iu',
            ' ',
            $cameraObservation
        ) ?? $cameraObservation);
        if ($cameraObservation === '') {
            $cameraObservation = 'Camera notes describe the visible cues that could be measured in frame.';
        }
        $cameraTip = is_scalar($camera['tip'] ?? null)
            ? trim((string) $camera['tip'])
            : '';
        if ($cameraTip === '' || preg_match('/\b(?:hand|hands|gesture|gestures)\b/iu', $cameraTip) === 1) {
            $cameraTip = 'Use steady front light and keep your face and shoulders in the preview when possible.';
        }
        $cameraSummary = [
            'observation' => \Illuminate\Support\Str::limit($cameraObservation, 240),
            'tip' => \Illuminate\Support\Str::limit($cameraTip, 160),
        ];
    }
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

        @if($cameraSummary !== null)
        <section class="review-quick-block review-quick-block-wide review-camera-card" aria-labelledby="ai-feedback-camera-title">
            <div class="review-block-title" id="ai-feedback-camera-title"><i class="fa-solid fa-video" aria-hidden="true"></i><span>Camera Coaching Note</span></div>
            <p>{{ $cameraSummary['observation'] }}</p>
            <p class="review-camera-note">{{ $cameraSummary['tip'] }} Browser estimate only. Not part of readiness score.</p>
        </section>
        @endif
    </div>
</section>
@else
<div class="alert mb-4" role="status" style="background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.24);color:var(--tx);border-radius:14px;">
    A validated AI provider review is not available for this session yet.
</div>
@endif
