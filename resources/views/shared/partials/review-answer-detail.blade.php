@php
 $coachingFeedback = is_array($answer->coaching_feedback ?? null) ? $answer->coaching_feedback : [];
 $normalizeReviewProviderKey = static function ($provider): string {
 $provider = strtolower(trim((string) $provider));
 $provider = str_replace([' ', '_', '-'], '', $provider);

 return match ($provider) {
 'openai', 'chatgpt', 'gpt' => 'openai',
 'gemini', 'google', 'googlegemini' => 'gemini',
 'groq' => 'groq',
 'cohere' => 'cohere',
 'local', 'localmodel' => 'local',
 default => '',
 };
 };
 $answerProvider = $normalizeReviewProviderKey($answer->ai_provider ?? '');
 $storedEvaluationSource = trim((string) data_get($coachingFeedback, 'content_alignment.evaluation_source', ''));
 $reviewIsApiBacked = $storedEvaluationSource === 'ai_evidence_validated'
 || in_array($answerProvider, ['openai', 'gemini', 'groq', 'cohere'], true);
 if (! $reviewIsApiBacked && in_array($storedEvaluationSource, ['local_fallback', 'local_evidence', 'stored_evidence_assessment'], true)) {
 $coachingFeedback = [];
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
 $sampleAnswer = trim((string) data_get($coachingFeedback, 'review_sample_answer.text', ''));
 $sampleAnswerSource = trim((string) data_get($coachingFeedback, 'review_sample_answer.source', ''));
 $sampleAnswerProvider = $normalizeReviewProviderKey(data_get($coachingFeedback, 'review_sample_answer.provider', $answerProvider));
 if ($sampleAnswer !== '' && $sampleAnswerSource !== 'ai' && ! in_array($sampleAnswerProvider, ['openai', 'gemini', 'groq', 'cohere'], true)) {
 $sampleAnswer = '';
 }
 if ($sampleAnswer === '' && $reviewIsApiBacked) {
 $savedSampleAnswer = trim((string) ($answer->better_sample_answer ?? ''));
 if ($savedSampleAnswer !== '') {
 $cleanSavedSampleAnswer = function_exists('review_feedback_without_question_text')
 ? review_feedback_without_question_text($savedSampleAnswer, $questionSource)
 : $savedSampleAnswer;
 $cleanSavedSampleAnswer = trim(preg_replace('/\s+/u', ' ', $cleanSavedSampleAnswer) ?? $cleanSavedSampleAnswer);
 $cleanSavedSampleLooksLikeQuestion = function_exists('review_text_looks_like_question')
 ? review_text_looks_like_question($cleanSavedSampleAnswer, $questionSource)
 : false;
 if ($cleanSavedSampleLooksLikeQuestion) {
 $sampleAnswer = '';
 } else {
 $sampleAnswer = $cleanSavedSampleAnswer;
 $sampleAnswer = trim(preg_replace('/\s+/u', ' ', $sampleAnswer) ?? $sampleAnswer);
 $sampleAnswerLooksLikeQuestion = function_exists('review_text_looks_like_question')
 ? review_text_looks_like_question($sampleAnswer, $questionSource)
 : false;
 if ($sampleAnswer === ''
 || $sampleAnswerLooksLikeQuestion
 || preg_match('/\[[^\]]+\]/u', $sampleAnswer) === 1
 || preg_match('/\b(?:response-based possible answer is unavailable|saved answer is too short|does not contain enough response detail)\b/iu', $sampleAnswer) === 1) {
 $sampleAnswer = '';
 }
 }
 }
 }
 $whatWorked = review_feedback_with_sentence_range($reviewFeedbackText($contentAlignment['what_worked'] ?? ''), 'worked');
 $savedAiFeedbackText = $reviewIsApiBacked ? $reviewFeedbackText($answer->ai_feedback ?? '') : '';
 if ($whatWorked === '' && $savedAiFeedbackText !== '') {
 $whatWorked = review_feedback_with_sentence_range($savedAiFeedbackText, 'feedback');
 }
 $impactText = review_feedback_with_sentence_range($reviewFeedbackText($contentAlignment['impact'] ?? $contentAlignment['observation'] ?? ''), 'impact');
 $missingPoints = $reviewFeedbackItems($contentAlignment['missing_points'] ?? [], 2);
 $supportingExcerpts = $listItems($contentAlignment['evidence_quotes'] ?? [], 1);
 $improvementFocus = $reviewFeedbackText($contentAlignment['improvement_focus'] ?? '');
 if ($improvementFocus === '' && ! empty($missingPoints)) {
 $improvementFocus = $missingPoints[0];
 }
 $improvementFocus = review_feedback_with_sentence_range($improvementFocus, 'improve');
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
 'ai_evidence_validated' => 'Validated AI provider check',
 'provider' => 'AI provider check',
 'local_evidence', 'local_fallback', 'stored_evidence_assessment' => '',
 '' => $reviewIsApiBacked ? 'Saved AI review' : '',
 default => \Illuminate\Support\Str::headline(str_replace('_', ' ', $evaluationSource)),
 };
 $answerProviderLabel = match ($answerProvider) {
 'openai' => 'OpenAI evidence',
 'gemini' => 'Gemini evidence',
 'groq' => 'Groq evidence',
 'cohere' => 'Cohere evidence',
 default => '',
 };
 $successCheck = $reviewFeedbackText($contentAlignment['success_check'] ?? '');
 $successCheck = review_feedback_with_sentence_range($successCheck, 'success');
 $limitationNote = $reviewFeedbackText($contentAlignment['limitation'] ?? '');
 $limitationNote = review_feedback_with_sentence_range($limitationNote, 'limitation');
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
 @if($answerProviderLabel !== '')
 <span>{{ $answerProviderLabel }}</span>
 @endif
 @if($evaluationSourceLabel !== '')
 <span>{{ $evaluationSourceLabel }}</span>
 @endif
 </div>
 </div>
 <p class="review-evidence-note">{{ $confidenceNote }}</p>
 <div class="review-evidence-grid">
 <div>
 <span>Evidence used</span>
 @if(!empty($supportingExcerpts))
 <p>"{{ $supportingExcerpts[0] }}"</p>
 @else
 <p>No API evidence quote was saved for this answer. Use the full answer below as the saved source.</p>
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
 <p>No API-saved missing point was stored for this answer.</p>
 @endif
 </div>
 @if($successCheck !== '')
 <div>
 <span>Success check</span>
 <p>{{ $successCheck }}</p>
 </div>
 @endif
 </div>
 @if($limitationNote !== '')
 <p class="review-evidence-limitation">{{ $limitationNote }}</p>
 @endif
 </section>

 <div class="review-answer-summary-grid">
 @if($whatWorked !== '')
 <section class="review-answer-section">
 <div class="review-block-title review-title-success"><i class="fa-solid fa-circle-check"></i><span>What Worked</span></div>
 <p>{{ $whatWorked }}</p>
 </section>
 @endif

 @if($improvementFocus !== '')
 <section class="review-answer-section">
 <div class="review-block-title review-title-warning"><i class="fa-solid fa-bullseye"></i><span>What To Improve</span></div>
 <p>{{ $improvementFocus }}</p>
 </section>
 @endif

 @if($impactText !== '')
 <section class="review-answer-section">
 <div class="review-block-title"><i class="fa-solid fa-circle-info"></i><span>Why It Matters</span></div>
 <p>{{ $impactText }}</p>
 </section>
 @endif

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
 @if($sampleAnswer !== '')
 <section>
 <span>Sample Answer</span>
 <p>{{ $sampleAnswer }}</p>
 </section>
 @endif
 </div>

</div>
