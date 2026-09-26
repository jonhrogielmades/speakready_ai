@php
 $overview = is_array($report['overview'] ?? null) ? $report['overview'] : [];
 $categoryBreakdown = is_array($report['category_breakdown'] ?? null) ? $report['category_breakdown'] : [];
 $limitText = static function (string $text, int $limit = 210): string {
 $clean = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
 if ($clean === '' || mb_strlen($clean, 'UTF-8') <= $limit) {
 return $clean;
 }

 return rtrim(mb_substr($clean, 0, max(1, $limit - 3), 'UTF-8'), " \t\n\r\0\x0B.,;:") . '...';
 };
 $shortList = static function ($items, int $limit = 3, int $textLimit = 130) use ($limitText): array {
 if (! is_array($items)) {
 return [];
 }

 return array_slice(array_values(array_filter(array_map(
 fn ($item) => is_scalar($item) ? $limitText(review_feedback_without_question_text((string) $item), $textLimit) : '',
 $items
 ))), 0, $limit);
 };
 $scoreItems = array_slice(array_values(array_filter($categoryBreakdown, 'is_array')), 0, 6);
 $strengthItems = $shortList($report['strength_items'] ?? [], 3);
 $weaknessItems = $shortList($report['weakness_items'] ?? [], 3);
 $suggestionItems = $shortList($report['suggestion_items'] ?? [], 2, 150);
 $fallbackSummary = is_scalar($overview['summary'] ?? null)
 ? review_feedback_without_question_text((string) $overview['summary'])
 : 'Feedback is ready. Review one focus area and practice again.';
 $fallbackFocusAdvice = is_scalar($overview['focus_advice'] ?? null)
 ? trim((string) $overview['focus_advice'])
 : 'Practice one answer again with a clear example.';
 if ($fallbackFocusAdvice === '') {
 $fallbackFocusAdvice = 'Practice one answer again with a clear example.';
 }
 $fallbackFocusLabel = is_scalar($overview['focus_label'] ?? null)
 ? trim((string) $overview['focus_label'])
 : 'Practice Focus';
 if ($fallbackFocusLabel === '') {
 $fallbackFocusLabel = 'Practice Focus';
 }
 $score = $sessionRecord->score;
 $overallScore = is_numeric($score?->overall_readiness_score ?? null)
 ? max(0, min(100, (int) round($score->overall_readiness_score)))
 : null;
 $rating = $score?->readiness_band ?: ($overallScore === null ? 'Pending' : ($overallScore >= 80 ? 'Ready for Simulation' : ($overallScore >= 60 ? 'Nearly Ready' : 'Developing')));
 $scoreColor = $overallScore === null ? '#64748b' : ($overallScore >= 80 ? '#10b981' : ($overallScore >= 60 ? '#3b82f6' : '#f59e0b'));
 $actionPriorities = collect($actionPriorities ?? [])->filter(fn ($item) => is_array($item))->values();
 $priorityAction = $actionPriorities->first(fn ($item) => trim((string) ($item['task'] ?? '')) !== '');
 $primarySuggestion = $priorityAction
 ? trim((string) ($priorityAction['task'] ?? ''))
 : ($suggestionItems[0] ?? $fallbackFocusAdvice);
 $primarySuggestion = $limitText(review_feedback_without_question_text($primarySuggestion), 170);
 $primarySuggestionLabel = $priorityAction
 ? $feedbackReportSkillLabel($priorityAction['skill'] ?? null)
 : $fallbackFocusLabel;
 $recommendedPath = collect($recommendedPaths ?? [])->first(fn ($item) => is_array($item) && trim((string) ($item['url'] ?? '')) !== '');
 $practiceUrl = is_array($recommendedPath) ? (string) $recommendedPath['url'] : route('interview.setup');
 $practiceLabel = is_array($recommendedPath) && trim((string) ($recommendedPath['label'] ?? '')) !== ''
 ? preg_replace('/^Interview\s+/i', '', (string) $recommendedPath['label'])
 : 'Practice again';
 $answers = $sessionRecord->relationLoaded('answers') ? $sessionRecord->answers : collect();
 $removeHandFeedback = static function (string $text): string {
 $clean = preg_replace('/(?:^|\s+)[^.!?]*(?:hand|hands|gesture|gestures)[^.!?]*[.!?]/iu', ' ', $text) ?? $text;

 return trim(preg_replace('/\s+/u', ' ', $clean) ?? $clean);
 };
 $cameraFeedbackItems = $answers
 ->map(function ($answer): array {
 $camera = data_get($answer->coaching_feedback ?? [], 'camera_feedback');

 return is_array($camera) ? $camera : [];
 })
 ->filter(fn (array $camera): bool => in_array((string) ($camera['status'] ?? ''), ['measured', 'insufficient_data'], true))
 ->values();
 $cameraSummary = null;
 if ($cameraFeedbackItems->isNotEmpty()) {
 $camera = $cameraFeedbackItems->first(fn (array $item): bool => ($item['status'] ?? '') === 'measured') ?? $cameraFeedbackItems->first();
 $cameraObservation = is_scalar($camera['observation'] ?? null)
 ? trim((string) $camera['observation'])
 : 'Camera feedback was attempted, but there was not enough camera data for a full note.';
 if ($cameraObservation === '') {
 $cameraObservation = 'Camera feedback was attempted, but there was not enough camera data for a full note.';
 }
 $cameraObservation = str_replace(
 ['the head looked camera-facing', 'camera-facing'],
 ['head direction looked toward the camera', 'toward the camera'],
 $cameraObservation
 );
 $cameraObservation = $removeHandFeedback($cameraObservation);
 if ($cameraObservation === '') {
 $cameraObservation = 'Camera feedback was measured from face, posture, and movement cues.';
 }
 $cameraTip = is_scalar($camera['tip'] ?? null)
 ? trim((string) $camera['tip'])
 : 'Use steady front light and keep your face and shoulders in the preview when possible.';
 if ($cameraTip === '') {
 $cameraTip = 'Use steady front light and keep your face and shoulders in the preview when possible.';
 }
 if (preg_match('/\b(?:hand|hands|gesture|gestures)\b/iu', $cameraTip) === 1) {
 $cameraTip = 'Use steady front light and keep your face and shoulders in the preview when possible.';
 }
 $cameraSummary = [
 'observation' => $limitText($cameraObservation, 240),
 'tip' => $limitText($cameraTip, 160),
 ];
 }
 $overallSummary = $limitText($fallbackSummary, 700);
@endphp

<section class="review-quick-panel premium-panel animate-fade-up" aria-labelledby="review-quick-title" style="animation-delay:.1s;">
 <div class="review-quick-head">
 <div>
 <div class="review-kicker">Feedback Detailed Review</div>
 <h5 id="review-quick-title">Overall Review</h5>
 </div>
 <div class="review-overall-score" style="--review-score-color: {{ $scoreColor }};">
 <strong>{{ $overallScore === null ? 'Pending' : $overallScore.'%' }}</strong>
 <span>{{ $rating }}</span>
 </div>
 </div>

 <div class="review-quick-grid">
 <section class="review-quick-block review-quick-block-wide">
 <div class="review-block-title"><i class="fa-solid fa-clipboard-check"></i><span>All Answer Review Summary</span></div>
 <p>{{ $overallSummary }}</p>
 </section>

 <section class="review-quick-block review-score-breakdown">
 <div class="review-block-title"><i class="fa-solid fa-chart-simple"></i><span>Score Breakdown</span></div>
 @if(!empty($scoreItems))
 <div class="review-score-grid">
 @foreach($scoreItems as $item)
 <div class="review-score-item" style="--metric-color: {{ $item['color'] ?? '#3b82f6' }};">
 <span>{{ $item['name'] ?? 'Skill' }}</span>
 <strong>{{ (int) ($item['score'] ?? 0) }}%</strong>
 </div>
 @endforeach
 </div>
 @else
 <p>No score breakdown yet.</p>
 @endif
 </section>

 <section class="review-quick-block">
 <div class="review-block-title review-title-success"><i class="fa-solid fa-circle-check"></i><span>Strengths</span></div>
 @if(!empty($strengthItems))
 <ul class="review-short-list">
 @foreach($strengthItems as $item)
 <li>{{ $item }}</li>
 @endforeach
 </ul>
 @else
 <p>Keep your clearest answer style.</p>
 @endif
 </section>

 <section class="review-quick-block">
 <div class="review-block-title review-title-warning"><i class="fa-solid fa-bullseye"></i><span>Weaknesses</span></div>
 @if(!empty($weaknessItems))
 <ul class="review-short-list">
 @foreach($weaknessItems as $item)
 <li>{{ $item }}</li>
 @endforeach
 </ul>
 @else
 <p>Add one specific action, example, or result.</p>
 @endif
 <p><strong>{{ $primarySuggestionLabel }}:</strong> {{ $primarySuggestion }}</p>
 <a href="{{ $practiceUrl }}" class="btn btn-primary btn-sm review-practice-btn">
 <i class="fa-solid fa-rotate-right"></i>{{ $practiceLabel }}
 </a>
 </section>

 @if($cameraSummary)
 <section class="review-quick-block review-quick-block-wide review-camera-card">
 <div class="review-block-title"><i class="fa-solid fa-video"></i><span>Camera Coaching Note</span></div>
 <p>{{ $cameraSummary['observation'] }}</p>
 <p class="review-camera-note">{{ $cameraSummary['tip'] }} Browser estimate only. Not part of readiness score.</p>
 </section>
 @endif

 </div>
</section>
