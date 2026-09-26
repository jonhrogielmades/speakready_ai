@php
 $coachingFeedback = is_array($answer->coaching_feedback ?? null) ? $answer->coaching_feedback : [];
 $coachingRepair = app(\App\Support\FeedbackCoachingRepair::class);
 if ($coachingRepair->answerCoachingNeedsRepair($coachingFeedback)) {
 $coachingFeedback = $coachingRepair->buildAnswerCoaching(
 $answer,
 (isset($sessionRecord) && $sessionRecord instanceof \App\Models\InterviewSession) ? $sessionRecord : null
 );
 }
 $contentAlignment = is_array($coachingFeedback['content_alignment'] ?? null) ? $coachingFeedback['content_alignment'] : [];
 $evidenceMap = is_array($answer->evidence_map ?? null) ? $answer->evidence_map : [];
 $starAnalysis = is_array($answer->star_analysis ?? null) ? $answer->star_analysis : [];
 $listItems = static function ($items, int $limit = 2): array {
 if (! is_array($items)) {
 return [];
 }

 return array_slice(array_values(array_filter(array_map(
 fn ($item) => is_scalar($item) ? trim((string) $item) : '',
 $items
 ))), 0, $limit);
 };
 $questionSource = $answer->question ?? $answer;
 $reviewFeedbackText = static fn ($value): string => review_feedback_without_question_text(is_scalar($value) ? (string) $value : '', $questionSource);
 $reviewFeedbackItems = static function ($items, int $limit = 2) use ($listItems, $reviewFeedbackText): array {
 return array_values(array_filter(array_map($reviewFeedbackText, $listItems($items, $limit))));
 };
 $answerText = trim((string) ($answer->answer_text ?? ''));
 $hasVoiceRecording = trim((string) ($answer->voice_recording_path ?? '')) !== '';
 $hasVoiceEvidence = trim((string) ($answer->delivery_transcript ?? '')) !== '';
 $isVoiceOnlyAnswer = strtolower((string) ($answer->response_mode ?? '')) === 'voice' && $hasVoiceRecording;
 $answerDisplay = $answerText !== ''
 ? $answerText
 : ($isVoiceOnlyAnswer && $hasVoiceEvidence
 ? 'Voice answer saved. Feedback is based on the saved voice session.'
 : ($hasVoiceRecording ? 'Transcript unavailable. Listen to the saved voice answer above.' : 'No answer text was saved.'));
 $whatWorked = $reviewFeedbackText($contentAlignment['what_worked'] ?? '');
 $missingPoints = $reviewFeedbackItems($contentAlignment['missing_points'] ?? ($evidenceMap['missing_evidence'] ?? []), 2);
 $nextAttemptSteps = $reviewFeedbackItems($contentAlignment['next_attempt_steps'] ?? [], 2);
 $supportingExcerpts = $listItems($contentAlignment['evidence_quotes'] ?? ($evidenceMap['supporting_excerpts'] ?? []), 1);
 $improvementFocus = $reviewFeedbackText($contentAlignment['improvement_focus'] ?? '');
 $impactExplanation = $reviewFeedbackText($contentAlignment['impact'] ?? '');
 if ($improvementFocus === '' && ! empty($missingPoints)) {
 $improvementFocus = $missingPoints[0];
 }
 if ($improvementFocus === '') {
 $improvementFocus = $reviewFeedbackText($starAnalysis['suggestion'] ?? '');
 }
 if ($improvementFocus === '') {
 $improvementFocus = 'Add one specific example, action, or result.';
 }
 $nextPractice = $reviewFeedbackText($contentAlignment['action'] ?? '');
 if ($nextPractice === '' && ! empty($nextAttemptSteps)) {
 $nextPractice = $nextAttemptSteps[0];
 }
 if ($nextPractice === '') {
 $nextPractice = $reviewFeedbackText($answer->recommendation_text ?? '');
 }
 if ($nextPractice === '') {
 $nextPractice = 'Try again with one clear example and one result.';
 }
 $betterAnswer = review_better_answer_text((string) ($answer->better_sample_answer ?? ''), $answer, $questionSource);
 $rubricLevel = trim((string) ($answer->rubric_level ?? ''));
 $alignmentStatus = strtolower(str_replace([' ', '-'], '_', trim((string) ($contentAlignment['status'] ?? ''))));
 $scoreUnavailable = in_array($alignmentStatus, ['insufficient_evidence', 'not_evaluated', 'skipped'], true);
 $cameraDetectionOn = (isset($sessionRecord) && $sessionRecord instanceof \App\Models\InterviewSession)
 ? (bool) data_get($sessionRecord->accommodation_profile, 'camera_detection', data_get($sessionRecord->accommodation_profile, 'camera_coaching', false))
 : false;
 $cameraFeedback = is_array($coachingFeedback['camera_feedback'] ?? null) ? $coachingFeedback['camera_feedback'] : [];
 $cameraStatus = strtolower(trim((string) ($cameraFeedback['status'] ?? '')));
 $cameraVisible = in_array($cameraStatus, ['measured', 'insufficient_data'], true)
 || ($cameraDetectionOn && $cameraStatus === 'not_measured' && trim((string) ($cameraFeedback['observation'] ?? '')) !== '');
 $removeHandFeedback = static function (string $text): string {
 $clean = preg_replace('/(?:^|\s+)[^.!?]*(?:hand|hands|gesture|gestures)[^.!?]*[.!?]/iu', ' ', $text) ?? $text;

 return trim(preg_replace('/\s+/u', ' ', $clean) ?? $clean);
 };
 $cameraObservation = trim((string) ($cameraFeedback['observation'] ?? ''));
 if ($cameraObservation === '') {
 $cameraObservation = $cameraStatus === 'insufficient_data'
 ? 'Camera feedback was attempted, but there was not enough camera data for a full note.'
 : 'Camera detection was not measured for this answer.';
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
 $cameraTip = trim((string) ($cameraFeedback['tip'] ?? ''));
 if ($cameraTip === '') {
 $cameraTip = 'Use steady front light and keep your face and shoulders in the preview when possible.';
 }
 if (preg_match('/\b(?:hand|hands|gesture|gestures)\b/iu', $cameraTip) === 1) {
 $cameraTip = 'Use steady front light and keep your face and shoulders in the preview when possible.';
 }
 $scoringConfidence = is_numeric(data_get($contentAlignment, 'scoring_confidence'))
 ? max(0, min(100, (int) round((float) data_get($contentAlignment, 'scoring_confidence'))))
 : (is_numeric($answer->scoring_confidence ?? null) ? max(0, min(100, (int) round((float) $answer->scoring_confidence))) : null);
 $confidenceLabel = match (true) {
 $scoreUnavailable && $scoringConfidence === null => 'Not enough evidence',
 $scoringConfidence === null => 'Confidence pending',
 $scoringConfidence >= 80 => 'High confidence',
 $scoringConfidence >= 55 => 'Medium confidence',
 default => 'Low confidence',
 };
 $confidenceColor = match (true) {
 $scoreUnavailable && $scoringConfidence === null => '#64748b',
 $scoringConfidence === null => '#64748b',
 $scoringConfidence >= 80 => '#10b981',
 $scoringConfidence >= 55 => '#2563eb',
 default => '#f59e0b',
 };
 $confidenceNote = match (true) {
 $scoreUnavailable => 'This answer needs more usable detail before the score should be trusted strongly.',
 $scoringConfidence === null => 'The app did not store a confidence value for this answer.',
 $scoringConfidence >= 80 => 'Enough answer detail was available for a stable review.',
 $scoringConfidence >= 55 => 'The review is useful, but one or more details were limited.',
 default => 'Treat this as a coaching hint and retry with more complete detail.',
 };
 $feedbackQuality = is_numeric(data_get($coachingFeedback, 'feedback_quality.completeness_percent'))
 ? max(0, min(100, (int) round((float) data_get($coachingFeedback, 'feedback_quality.completeness_percent'))))
 : null;
 $evaluationSource = trim((string) data_get($contentAlignment, 'evaluation_source', ''));
 $evaluationSourceLabel = match ($evaluationSource) {
 'local_evidence' => 'Local evidence check',
 'local_fallback' => 'Fallback evidence check',
 'provider' => 'AI provider check',
 '' => 'Saved review',
 default => \Illuminate\Support\Str::headline(str_replace('_', ' ', $evaluationSource)),
 };
 $successCheck = $reviewFeedbackText($contentAlignment['success_check'] ?? '');
 if ($successCheck === '') {
 $successCheck = 'A reviewer can find the direct answer, the supporting detail, and the result or lesson.';
 }
 $limitationNote = $reviewFeedbackText($contentAlignment['limitation'] ?? '');
 if ($limitationNote === '') {
 $limitationNote = 'This review uses only the saved answer, question, and measurable practice data.';
 }
@endphp

<div class="review-answer-simple">
 @if($scoreUnavailable)
 <div class="review-answer-status-row">
 <span class="retry-chip">Not scored</span>
 </div>
 @endif

 <section class="review-evidence-card" style="--confidence-color: {{ $confidenceColor }};">
 <div class="review-evidence-head">
 <div>
 <span class="review-evidence-kicker">Evidence & reliability</span>
 <strong>{{ $confidenceLabel }}</strong>
 </div>
 <div class="review-evidence-badges">
 @if($scoringConfidence !== null)
 <span>{{ $scoringConfidence }}% confidence</span>
 @endif
 @if($feedbackQuality !== null)
 <span>{{ $feedbackQuality }}% checked</span>
 @endif
 <span>{{ $evaluationSourceLabel }}</span>
 </div>
 </div>
 <p class="review-evidence-note">{{ $confidenceNote }}</p>
 <div class="review-evidence-grid">
 <div>
 <span>Evidence used</span>
 @if(!empty($supportingExcerpts))
 <p>"{{ $supportingExcerpts[0] }}"</p>
 @else
 <p>No direct quote was saved for this answer. Use the full answer below as the review source.</p>
 @endif
 </div>
 <div>
 <span>Missing or weak</span>
 @if(!empty($missingPoints))
 <ul>
 @foreach($missingPoints as $item)
 <li>{{ $item }}</li>
 @endforeach
 </ul>
 @else
 <p>No major missing point was stored. Keep the answer focused and add stronger proof if you retry.</p>
 @endif
 </div>
 <div>
 <span>Success check</span>
 <p>{{ $successCheck }}</p>
 </div>
 </div>
 <p class="review-evidence-limitation">{{ $limitationNote }}</p>
 </section>

 <div class="review-answer-summary-grid">
 @if($whatWorked !== '')
 <section class="review-answer-section">
 <div class="review-block-title review-title-success"><i class="fa-solid fa-circle-check"></i><span>What Worked</span></div>
 <p>{{ $whatWorked }}</p>
 </section>
 @endif

 <section class="review-answer-section">
 <div class="review-block-title review-title-warning"><i class="fa-solid fa-bullseye"></i><span>What To Improve</span></div>
 <p>{{ $improvementFocus }}</p>
 </section>

 @if($impactExplanation !== '')
 <section class="review-answer-section">
 <div class="review-block-title"><i class="fa-solid fa-chart-line"></i><span>Why It Matters</span></div>
 <p>{{ $impactExplanation }}</p>
 </section>
 @endif

 <section class="review-answer-section">
 <div class="review-block-title"><i class="fa-solid fa-location-arrow"></i><span>Next Practice</span></div>
 <p>{{ $nextPractice }}</p>
 </section>

 @if($cameraVisible)
 <section class="review-answer-section review-answer-section-wide review-camera-card">
 <div class="review-block-title"><i class="fa-solid fa-video"></i><span>Camera Coaching Note</span></div>
 <p>{{ $cameraObservation }}</p>
 <p class="review-camera-note">{{ $cameraTip }} Browser estimate only. Not part of readiness score.</p>
 </section>
 @endif
 </div>

 <div class="review-answer-example-grid">
 <section>
 <span>Your Answer</span>
 <p>{{ $answerDisplay }}</p>
 </section>
 <section class="review-better-example">
 <span>Better Example</span>
 <p>{{ $betterAnswer }}</p>
 </section>
 </div>

 @if($rubricLevel !== '' || !empty($supportingExcerpts) || !empty($missingPoints))
 <div class="review-answer-proof">
 @if($rubricLevel !== '')
 <span class="retry-chip">{{ $rubricLevel }}</span>
 @endif
 @foreach($supportingExcerpts as $item)
 <span><strong>Keep:</strong> {{ $item }}</span>
 @endforeach
 @foreach($missingPoints as $item)
 <span><strong>Add:</strong> {{ $item }}</span>
 @endforeach
 </div>
 @endif
</div>
