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
 $answerText = trim((string) ($answer->answer_text ?? ''));
 $hasVoiceRecording = trim((string) ($answer->voice_recording_path ?? '')) !== '';
 $hasVoiceEvidence = trim((string) ($answer->delivery_transcript ?? '')) !== '';
 $isVoiceOnlyAnswer = strtolower((string) ($answer->response_mode ?? '')) === 'voice' && $hasVoiceRecording;
 $answerDisplay = $answerText !== ''
 ? $answerText
 : ($isVoiceOnlyAnswer && $hasVoiceEvidence
 ? 'Voice answer saved. Feedback is based on the saved voice session.'
 : ($hasVoiceRecording ? 'Transcript unavailable. Listen to the saved voice answer above.' : 'No answer text was saved.'));
 $feedbackText = trim((string) ($answer->ai_feedback ?: 'No feedback was generated for this answer.'));
 $whatWorked = trim((string) ($contentAlignment['what_worked'] ?? ''));
 $missingPoints = $listItems($contentAlignment['missing_points'] ?? ($evidenceMap['missing_evidence'] ?? []), 2);
 $nextAttemptSteps = $listItems($contentAlignment['next_attempt_steps'] ?? [], 2);
 $supportingExcerpts = $listItems($contentAlignment['evidence_quotes'] ?? ($evidenceMap['supporting_excerpts'] ?? []), 1);
 $improvementFocus = trim((string) ($contentAlignment['improvement_focus'] ?? ''));
 if ($improvementFocus === '' && ! empty($missingPoints)) {
 $improvementFocus = $missingPoints[0];
 }
 if ($improvementFocus === '') {
 $improvementFocus = trim((string) ($starAnalysis['suggestion'] ?? ''));
 }
 if ($improvementFocus === '') {
 $improvementFocus = 'Add one specific example, action, or result.';
 }
 $nextPractice = trim((string) ($contentAlignment['action'] ?? ''));
 if ($nextPractice === '' && ! empty($nextAttemptSteps)) {
 $nextPractice = $nextAttemptSteps[0];
 }
 if ($nextPractice === '') {
 $nextPractice = trim((string) ($answer->recommendation_text ?? ''));
 }
 if ($nextPractice === '') {
 $nextPractice = 'Try again with one clear example and one result.';
 }
 $betterAnswer = trim((string) ($answer->better_sample_answer ?: 'No better example was generated for this response.'));
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
 $formatCameraPercent = static function ($value): ?string {
 if (! is_numeric($value)) {
 return null;
 }

 return max(0, min(100, (int) round((float) $value))) . '%';
 };
 $formatCameraScore = static function ($value): ?string {
 if (! is_numeric($value)) {
 return null;
 }

 return max(0, min(100, (int) round((float) $value))) . '/100';
 };
 $removeHandFeedback = static function (string $text): string {
 $clean = preg_replace('/(?:^|\s+)[^.!?]*(?:hand|hands|gesture|gestures)[^.!?]*[.!?]/iu', ' ', $text) ?? $text;

 return trim(preg_replace('/\s+/u', ' ', $clean) ?? $clean);
 };
 $cameraEvidence = is_array($cameraFeedback['evidence'] ?? null) ? $cameraFeedback['evidence'] : [];
 $cameraMetrics = [];
 foreach ([
 ['Face in frame', $formatCameraPercent($cameraEvidence['face_visibility_percent'] ?? null)],
 ['Eye contact', $formatCameraPercent($cameraEvidence['camera_facing_percent'] ?? null)],
 ['Shoulders', $formatCameraPercent($cameraEvidence['shoulders_level_percent'] ?? null)],
 ['Posture', $formatCameraPercent($cameraEvidence['upright_posture_percent'] ?? null)],
 ['Movement', $formatCameraScore($cameraEvidence['average_movement_score'] ?? null)],
 ] as $metric) {
 if ($metric[1] !== null) {
 $cameraMetrics[] = ['label' => $metric[0], 'value' => $metric[1]];
 }
 }
 if (empty($cameraMetrics) && (int) ($cameraEvidence['sample_count'] ?? 0) > 0) {
 $cameraMetrics[] = ['label' => 'Samples', 'value' => (string) (int) $cameraEvidence['sample_count']];
 }
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
@endphp

<div class="review-answer-simple">
 @if($scoreUnavailable)
 <div class="review-answer-status-row">
 <span class="retry-chip">Not scored</span>
 </div>
 @endif

 <div class="review-answer-summary-grid">
 <section class="review-answer-section review-answer-section-wide">
 <div class="review-block-title"><i class="fa-solid fa-comment-medical"></i><span>Overall Feedback</span></div>
 <p>{{ $feedbackText }}</p>
 </section>

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

 <section class="review-answer-section">
 <div class="review-block-title"><i class="fa-solid fa-location-arrow"></i><span>Next Practice</span></div>
 <p>{{ $nextPractice }}</p>
 </section>

 @if($cameraVisible)
 <section class="review-answer-section review-answer-section-wide review-camera-card">
 <div class="review-block-title"><i class="fa-solid fa-video"></i><span>Camera Feedback</span></div>
 <p>{{ $cameraObservation }}</p>
 @if(!empty($cameraMetrics))
 <div class="review-camera-chips">
 @foreach($cameraMetrics as $metric)
 <span class="review-camera-chip"><strong>{{ $metric['label'] }}</strong><span>{{ $metric['value'] }}</span></span>
 @endforeach
 </div>
 @endif
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
