<?php

namespace App\Services;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Score;
use App\Models\Setting;
use App\Support\RuntimeConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DetailedFeedbackReviewService
{
 private const REVIEW_VALIDATION_SCHEMA = 'detailed_review_validation_v2';

 private const MINIMUM_RELIABILITY_PERCENT = 95;

 public function __construct(
 private readonly TrustworthyAssessmentService $assessment,
 private readonly EvidenceBasedCoachingService $coaching
 ) {
 }

 public function refreshWithProvidersIfNeeded(InterviewSession $session,?string $preferredProvider = null, ?int $deadlineSeconds = null): bool
 {
 // Start from persisted inputs, including database defaults and the latest
 // transcript, rather than relationships cached by an earlier request.
 if ($session->exists) {
 $session->refresh();
 }
 if (! $this->shouldRefreshWithProvider($session)) {
 return false;
 }

 $provider = $this->configuredProvider($preferredProvider);
 if ($provider === null) {
 return false;
 }

 $answers = $this->originalAnswers($session);
 if ($answers->isEmpty()) {
 return false;
 }

 $sessionData = $this->sessionData($session);
 $answersData = $this->answersData($answers);
 $runtimeOptions = $this->reviewRefreshRuntimeOptions($deadlineSeconds);
 // Keep the final evidence gate inside the provider cascade so a rejected
 // OpenAI review can be corrected or replaced by the next configured API.
 $runtimeOptions['response_validator'] = fn (array $candidate, string $candidateProvider): bool =>
 ($this->providerFeedbackValidation($answers, $candidate, $candidateProvider)['status'] ?? null) === 'verified';

 try {
 $feedback = AIService::generateFeedback(
 $sessionData,
 $answersData,
 $provider,
 false,
 false,
 $runtimeOptions
 );
 } catch (AiFeedbackProviderFailureException $error) {
 Log::warning('Detailed review API provider refresh failed; keeping saved feedback.', [
 'session_id' => $session->id,
 'user_id' => $session->user_id,
 'provider' => $provider,
 'provider_count' => $error->providerCount(),
 'providers_attempted' => $error->attemptedProviders(),
 ]);

 return false;
 } catch (\Throwable $error) {
 Log::warning('Detailed review API provider refresh crashed; keeping saved feedback.', [
 'session_id' => $session->id,
 'user_id' => $session->user_id,
 'provider' => $provider,
 'error_type' => $error::class,
 'message' => Str::limit($error->getMessage(), 300),
 ]);

 return false;
 }

 $providerKey = AIService::normalizeProviderKey($feedback['_provider_key']?? $provider);
 if ($providerKey === '' || in_array($providerKey, ['local', 'localmodel'], true)) {
 return false;
 }

 $validation = $this->providerFeedbackValidation($answers, $feedback, $providerKey);
 if (($validation['status']?? null) !== 'verified') {
 Log::warning('Detailed review API response failed the final reliability gate; keeping saved feedback.', [
 'session_id' => $session->id,
 'user_id' => $session->user_id,
 'provider' => $providerKey,
 'required_reliability_percent' => $validation['required_reliability_percent']?? null,
 'minimum_scoreable_reliability_percent' => $validation['minimum_scoreable_reliability_percent']?? null,
 'reasons' => $validation['reasons']?? [],
 'failed_answer_checks' => collect((array) ($validation['answer_checks']?? []))
 ->map(fn (array $checks): array => array_keys(array_filter(
 $checks,
 fn ($passed): bool => $passed!== true
 )))
 ->filter()
 ->all(),
 ]);

 return false;
 }

 try {
 $this->persistProviderFeedback($session, $answers, $feedback, $providerKey, $validation);
 } catch (\Throwable $error) {
 Log::warning('Detailed review API response could not be persisted safely; keeping saved feedback.', [
 'session_id' => $session->id,
 'user_id' => $session->user_id,
 'provider' => $providerKey,
 'error_type' => $error::class,
 'message' => Str::limit($error->getMessage(), 300),
 ]);

 return false;
 }

 return true;
 }

 public function shouldRefreshWithProvider(InterviewSession $session): bool
 {
 if ($session->status !== 'completed' || (bool) data_get($session->action_plan ?? [], 'ended_early', false)) {
 return false;
 }

 $session->loadMissing(['answers.question', 'feedback', 'score']);

 if (! $session->feedback instanceof Feedback || ! $session->score instanceof Score) {
 return true;
 }

 $summarySource = trim((string) data_get($session->feedback->coaching_summary?? [], 'overall_summary_source', ''));
 $repairedSummaryFields = (array) data_get($session->feedback->coaching_summary?? [], 'review_context.session_feedback_repaired_fields', []);
 $summarySourceVerified = $summarySource === 'ai_provider_validated' && $repairedSummaryFields === [];
 if (! $summarySourceVerified) {
 return true;
 }

 $validationStatus = trim((string) data_get(
 $session->feedback->coaching_summary?? [],
 'review_context.detailed_review_validation.status',
 ''
 ));
 if ($validationStatus !== 'verified') {
 return true;
 }

 $savedInputFingerprint = (string) data_get(
 $session->feedback->coaching_summary?? [],
 'review_context.detailed_review_validation.input_fingerprint',
 ''
 );
 if ($savedInputFingerprint === ''
 || ! hash_equals($savedInputFingerprint, $this->reviewInputFingerprint($session, $this->originalAnswers($session)))) {
 return true;
 }

 // A verified report can still reflect an older scoring policy. Refresh it
 // when the review rules change, even if its response schema is unchanged.
 $storedPolicyVersion = (int) data_get(
 $session->feedback->coaching_summary?? [],
 'review_context.review_policy_version',
 0
 );
 if ($storedPolicyVersion < AIService::REVIEW_POLICY_VERSION) {
 return true;
 }

 if ($this->sessionNeedsApiProviderVisibleSummary($session)) {
 return true;
 }

 foreach ($this->originalAnswers($session) as $answer) {
 if (! $answer instanceof InterviewAnswer) {
 continue;
 }

 $state = $this->answerReviewProviderState($answer);
 if (! $state['skipped'] && (! $state['feedback_ready'] || (! $state['insufficient'] && ! $state['possible_answer_ready']))) {
 return true;
 }

 $answerProvider = AIService::normalizeProviderKey($answer->ai_provider);
 $evaluationSource = trim((string) data_get($answer->coaching_feedback?? [], 'content_alignment.evaluation_source', ''));
 if ($answerProvider === ''
 || in_array($answerProvider, ['local', 'localmodel'], true)
 || $evaluationSource !== 'ai_evidence_validated'
 || $this->answerNeedsApiProviderVisibleFeedback($answer)
 || $this->answerNeedsApiProviderPossibleAnswer($answer)
 || trim((string) ($answer->ai_feedback?? '')) === ''
 ) {
 return true;
 }
 }

 return false;
 }

 /** Validate the first provider response while the interview is being analyzed. */
 public function providerResponseValidator(InterviewSession $session): \Closure
 {
 $answers = $this->originalAnswersQuery($session)->get();
 return fn (array $feedback, string $provider): bool =>
 ($this->providerFeedbackValidation($answers, $feedback, $provider)['status'] ?? null) === 'verified';
 }

 public function providerAnswerInputs(Collection $answers): array
 {
 return $this->answersData($answers);
 }

 /** Certify the response already generated during finalization; no new API call. */
 public function acceptGeneratedProviderReview(InterviewSession $session, array $feedback, array $generatedAnswers, array $generatedContext): void
 {
 $session->refresh();
 $answers = $this->originalAnswersQuery($session)->get();
 $currentAnswers = collect($this->answersData($answers))->sortBy('id')->values()->all();
 $generatedAnswers = collect($generatedAnswers)->sortBy('id')->values()->all();
 $contextKeys = ['target_position', 'difficulty', 'interview_focus', 'ai_assistance_level', 'assessment_mode', 'accommodation_profile'];
 if ($session->status !== 'completed'
 || $currentAnswers !== $generatedAnswers
 || array_intersect_key($this->sessionData($session), array_flip($contextKeys)) !== array_intersect_key($generatedContext, array_flip($contextKeys))) {
 throw new \RuntimeException('Interview inputs changed before the generated review could be saved.');
 }
 $provider = AIService::normalizeProviderKey($feedback['_provider_key'] ?? null);
 $validation = $this->providerFeedbackValidation($answers, $feedback, $provider);
 if (($validation['status'] ?? null) !== 'verified') {
 throw new \RuntimeException('The generated interview review did not pass all saved-answer checks.');
 }
 // Finalization has already calculated scores, game rewards and progress.
 // Save the verified prose and its fingerprints without applying those twice.
 $this->persistProviderFeedback($session, $answers, $feedback, $provider, $validation, true);
 if ($this->shouldRefreshWithProvider($session->fresh())) {
 throw new \RuntimeException('The saved interview review is not ready for every question.');
 }
 }

 private function persistProviderFeedback(InterviewSession $session, Collection $answers, array $feedback, string $providerKey, array $validation, bool $preserveScores = false): void
 {
 $inputFingerprint = $this->reviewInputFingerprint($session, $answers);
 DB::transaction(function () use ($session, $feedback, $providerKey, $validation, $inputFingerprint, $preserveScores): void {
 // The provider request can take seconds. Never attach its findings to an
 // answer, transcript, question, or session context that changed meanwhile.
 $currentSession = InterviewSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
 $currentAnswers = $this->originalAnswersQuery($session)
 ->orderBy('id')
 ->lockForUpdate()
 ->get();
 if ($currentSession->status !== 'completed'
 || ! hash_equals($inputFingerprint, $this->reviewInputFingerprint($currentSession, $currentAnswers))) {
 throw new \RuntimeException('Detailed review inputs changed while provider feedback was being generated.');
 }
 $session = $currentSession;
 $answers = $currentAnswers;
 $validation['input_fingerprint'] = $inputFingerprint;
 $feedbackById = collect($feedback['per_question_feedback']?? [])
 ->filter(fn ($item): bool => is_array($item) && isset($item['id']))
 ->keyBy(fn (array $item): string => (string) $item['id']);

 $totals = [
 'clarity_score' => 0,
 'relevance_score' => 0,
 'grammar_score' => 0,
 'professionalism_score' => 0,
 'score' => 0,
 'star_method_score' => 0,
 'star_applicable_count' => 0,
 ];

 foreach ($answers as $answer) {
 $item = $feedbackById->get((string) $answer->id);
 if (! is_array($item)) {
 throw new \RuntimeException("Validated feedback item {$answer->id} disappeared before persistence.");
 }

 $clarity = $this->scoreValue($item['clarity_score']?? 0);
 $relevance = $this->scoreValue($item['relevance_score']?? 0);
 $grammar = $this->scoreValue($item['grammar_score']?? 0);
 $professionalism = $this->scoreValue($item['professionalism_score']?? 0);
 $answerScore = $this->scoreValue($item['score']?? round(($clarity + $relevance + $grammar + $professionalism) / 4));

 $totals['clarity_score'] += $clarity;
 $totals['relevance_score'] += $relevance;
 $totals['grammar_score'] += $grammar;
 $totals['professionalism_score'] += $professionalism;
 $totals['score'] += $answerScore;
 if ((bool) ($item['star_applicable']?? false)) {
 $totals['star_method_score'] += $this->scoreValue($item['star_method_score']?? 0);
 $totals['star_applicable_count']++;
 }

 $answerText = $this->answerContent($answer);
 $coachingFeedback = $this->coaching->forAnswer(
 $answerText,
 $answer->question,
 $this->coachingMetricsFromAnswer($answer, $item),
 is_array($answer->observation_data)? $answer->observation_data: []
 );
 $coachingFeedback = $this->withPossibleAnswerProviderMetadata(
 $coachingFeedback,
 $providerKey,
 is_scalar($item['better_sample_answer_source']?? null)? (string) $item['better_sample_answer_source']: null,
 is_scalar($item['better_sample_answer_provider']?? null)? (string) $item['better_sample_answer_provider']: null
 );
 $coachingFeedback['provider_narrative'] = [
 'verified' => true,
 'input_fingerprint' => $this->answerInputFingerprint($answer),
 'feedback_hash' => hash('sha256', trim((string) ($item['ai_feedback'] ?? ''))),
 'possible_answer_hash' => hash('sha256', trim((string) ($item['better_sample_answer'] ?? ''))),
 ];
 if (data_get($coachingFeedback, 'feedback_quality.status') !== 'verified'
 || (int) data_get($coachingFeedback, 'feedback_quality.completeness_percent', 0) !== 100) {
 throw new \RuntimeException("Detailed review coaching checks failed for answer {$answer->id}.");
 }

 $narrativeFields = [
 'ai_feedback' => trim((string) ($item['ai_feedback']?? '')),
 'better_sample_answer' => trim((string) ($item['better_sample_answer']?? '')),
 'follow_up_question' => trim((string) ($item['follow_up_question']?? '')),
 'coaching_feedback' => $coachingFeedback,
 'ai_provider' => $providerKey,
 ];
 if ($preserveScores || (int) ($session->game_level_id ?? 0) > 0) {
 // Game results use their own rubric. A review refresh updates prose only.
 $answer->forceFill($narrativeFields)->save();
 continue;
 }
 $evidence = $this->assessment->answerEvidence($answerText, $item['ai_feedback']?? null, $answer->question);
 $rubric = $this->assessment->rubricLevel($answerScore);
 $answer->forceFill(array_merge($narrativeFields, [
 'clarity_score' => $clarity,
 'relevance_score' => $relevance,
 'grammar_score' => $grammar,
 'score' => $answerScore,
 'scoring_confidence' => $this->scoreValue($item['scoring_confidence']?? 0),
 'evidence_map' => $evidence,
 'rubric_level' => $rubric['level'],
 'recommendation_text' => $rubric['next_level'],
 'improved_answer_source' => 'candidate_facts',
 ]))->save();
 }

 $count = max(1, $answers->count());
 $metrics = [
 'clarity' => (int) round($totals['clarity_score'] / $count),
 'relevance' => (int) round($totals['relevance_score'] / $count),
 'grammar' => (int) round($totals['grammar_score'] / $count),
 'professionalism' => (int) round($totals['professionalism_score'] / $count),
 ];

 $evaluatedAnswers = $this->originalAnswers($session->fresh(['answers.question']) ?? $session);
 $sessionFeedback = is_array($feedback['session_feedback']?? null)? $feedback['session_feedback']: [];
 if ($preserveScores || (int) ($session->game_level_id ?? 0) > 0) {
 Feedback::updateOrCreate(['interview_session_id' => $session->id], [
 'strengths' => trim((string) ($sessionFeedback['strengths'] ?? '')),
 'weaknesses' => trim((string) ($sessionFeedback['weaknesses'] ?? '')),
 'improvement_suggestions' => trim((string) ($sessionFeedback['improvement_suggestions'] ?? '')),
 'coaching_summary' => $this->sessionCoachingSummary($evaluatedAnswers, $sessionFeedback, (array) ($feedback['review_context'] ?? []), $validation),
 ]);
 return;
 }
 $starScore = $totals['star_applicable_count'] > 0
 ? $this->scoreValue($totals['star_method_score'] / $totals['star_applicable_count'])
 : 0;
 $metadata = $this->assessment->sessionMetadata($session, $evaluatedAnswers, $metrics, $starScore);
 $answerAverage = $this->scoreValue($totals['score'] / $count);
 // A provider refresh must not make the stored total more generous than
 // either the evidence-capped answer average or the versioned rubric total.
 $overall = min($answerAverage, $this->scoreValue($metadata['overall']?? $answerAverage));
 $metadata['overall'] = $overall;
 $metadata['readiness_band'] = $this->assessment->readinessBand($overall);

 $score = Score::updateOrCreate([
 'interview_session_id' => $session->id,
 ], [
 'score_version' => TrustworthyAssessmentService::SCORE_VERSION,
 'assessment_mode' => $session->assessment_mode,
 'clarity_score' => $metrics['clarity'],
 'relevance_score' => $metrics['relevance'],
 'grammar_score' => $metrics['grammar'],
 'professionalism_score' => $metrics['professionalism'],
 'body_language_score' => 0,
 'confidence_score' => 0,
 'overall_readiness_score' => $overall,
 'readiness_band' => $metadata['readiness_band'],
 'scoring_confidence' => $metadata['scoring_confidence'],
 'star_method_score' => $starScore,
 'evidence_map' => $metadata['evidence_map'],
 'rubric' => $metadata['rubric'],
 'body_language_included' => false,
 ]);

 Feedback::updateOrCreate([
 'interview_session_id' => $session->id,
 ], [
 'strengths' => trim((string) ($sessionFeedback['strengths']?? '')),
 'weaknesses' => trim((string) ($sessionFeedback['weaknesses']?? '')),
 'improvement_suggestions' => trim((string) ($sessionFeedback['improvement_suggestions']?? '')),
 'coaching_summary' => $this->sessionCoachingSummary(
 $evaluatedAnswers,
 $sessionFeedback,
 is_array($feedback['review_context']?? null)? $feedback['review_context']: [],
 $validation
 ),
 ]);

 if ($session->readinessScoreEligible()) {
 Profile::where('user_id', $session->user_id)->update(['readiness_score' => $score->overall_readiness_score]);
 }
 });
 }

 private function sessionCoachingSummary(Collection $answers, array $sessionFeedback, array $reviewContext = [], array $validation = []): array
 {
 $summary = $this->coaching->sessionSummary($answers->values());
 if ($reviewContext !== [] || $validation !== []) {
 $summary['review_context'] = $this->summaryReviewContext($reviewContext, $validation);
 }
 $overallSummary = trim((string) ($sessionFeedback['overall_summary']?? ''));
 if ($overallSummary !== '') {
 $summary['overall_summary'] = Str::limit($overallSummary, 700, '');
 $summary['overall_summary_source'] = in_array('overall_summary', (array) ($reviewContext['session_feedback_repaired_fields']?? []), true)
 ? 'local_evidence_validated'
 : 'ai_provider_validated';
 }

 return $summary;
 }

 private function summaryReviewContext(array $reviewContext, array $validation = []): array
 {
 return [
 'schema_version' => trim((string) ($reviewContext['schema_version']?? 'interview_feedback_v9')),
 'review_policy_version' => (int) ($reviewContext['review_policy_version']?? 0),
 'rubric_name' => trim((string) ($reviewContext['rubric_name']?? 'SpeakReady detailed interview feedback rubric')),
 'rubric_version' => trim((string) ($reviewContext['rubric_version']?? 'score-v'.TrustworthyAssessmentService::SCORE_VERSION)),
 'score_version' => (int) ($reviewContext['score_version']?? TrustworthyAssessmentService::SCORE_VERSION),
 'coaching_version' => (int) ($reviewContext['coaching_version']?? EvidenceBasedCoachingService::VERSION),
 'provider_key' => AIService::normalizeProviderKey($reviewContext['provider_key']?? null),
 'provider_source' => trim((string) ($reviewContext['provider_source']?? 'ai_provider')),
 'session_feedback_source' => trim((string) ($reviewContext['session_feedback_source']?? 'ai_provider')),
 'session_feedback_repaired_fields' => array_values((array) ($reviewContext['session_feedback_repaired_fields']?? [])),
 'coaching_repaired_answer_ids' => array_values((array) ($reviewContext['coaching_repaired_answer_ids']?? [])),
 'repair_strategy' => trim((string) ($reviewContext['repair_strategy']?? '')),
 'repair_scope' => trim((string) ($reviewContext['repair_scope']?? '')),
 'provider_confidence' => trim((string) ($reviewContext['provider_confidence']?? 'medium')),
 'insufficient_data' => (bool) ($reviewContext['insufficient_data']?? false),
 'evidence_policy' => trim((string) ($reviewContext['evidence_policy']?? 'Scores and comments use only saved answer evidence, the exact question, and explicit missing requirements.')),
 'confidence_policy' => trim((string) ($reviewContext['confidence_policy']?? 'Confidence is based on answer detail, evidence quotes, missing data, and score checks.')),
 'rubric_dimensions' => is_array($reviewContext['rubric_dimensions']?? null)? $reviewContext['rubric_dimensions']: [],
 'coverage' => is_array($reviewContext['coverage']?? null)? $reviewContext['coverage']: [],
 'risk_flags' => array_values(array_filter(array_map(
 fn ($flag): string => is_scalar($flag)? trim((string) $flag): '',
 (array) ($reviewContext['risk_flags']?? [])
 ))),
 'feedback_quality' => is_array($reviewContext['feedback_quality']?? null)? $reviewContext['feedback_quality']: [],
 'detailed_review_validation' => $this->summaryReviewValidation($validation),
 'limitation' => trim((string) ($reviewContext['limitation']?? 'This is AI coaching feedback checked against saved answer evidence, not a human hiring decision.')),
 ];
 }

 private function providerFeedbackValidation(Collection $answers, array $feedback, string $providerKey): array
 {
 $expectedIds = $answers
 ->filter(fn ($answer): bool => $answer instanceof InterviewAnswer)
 ->map(fn (InterviewAnswer $answer): string => (string) $answer->id)
 ->values()
 ->all();
 $items = is_array($feedback['per_question_feedback']?? null)
 ? array_values(array_filter($feedback['per_question_feedback'], fn ($item): bool => is_array($item)))
 : [];
 $suppliedIds = array_map(
 fn (array $item): string => is_scalar($item['id']?? null)? (string) $item['id']: '',
 $items
 );
 $sortedExpectedIds = $expectedIds;
 $sortedSuppliedIds = $suppliedIds;
 sort($sortedExpectedIds, SORT_STRING);
 sort($sortedSuppliedIds, SORT_STRING);

 $aggregateQuality = is_array($feedback['feedback_quality']?? null)? $feedback['feedback_quality']: [];
 $reviewContext = is_array($feedback['review_context']?? null)? $feedback['review_context']: [];
 $contextQuality = is_array($reviewContext['feedback_quality']?? null)? $reviewContext['feedback_quality']: [];
 $sessionFeedback = is_array($feedback['session_feedback']?? null)? $feedback['session_feedback']: [];
 $answersData = $this->answersData($answers);
 $requiredReliability = $this->minimumReliabilityPercent();
 $scoreableReliabilities = [];
 $answerChecks = [];

 $itemsById = collect($items)->keyBy(fn (array $item): string => (string) ($item['id']?? ''));
 foreach ($answers as $answer) {
 if (! $answer instanceof InterviewAnswer) {
 continue;
 }

 $id = (string) $answer->id;
 $item = $itemsById->get($id);
 if (! is_array($item)) {
 $answerChecks[$id] = ['complete' => false];

 continue;
 }

 $quality = is_array($item['feedback_quality']?? null)? $item['feedback_quality']: [];
 $qualityChecks = is_array($quality['checks']?? null)? $quality['checks']: [];
 $answerData = $this->answerData($answer);
 $eligibility = AIService::feedbackAnswerEligibility($answerData);
 $isSkipped = $eligibility['is_skipped'];
 $isTooShort = $eligibility['is_too_short'];
 $isScoreable = ! $isSkipped && ! $isTooShort;
 $reliability = is_numeric($quality['reliability_percent']?? null)
 ? $this->scoreValue($quality['reliability_percent'])
 : null;
 if ($isScoreable && $reliability !== null) {
 $scoreableReliabilities[] = $reliability;
 }

 $qualityVerified = ($quality['status']?? null) === 'verified'
 && (int) ($quality['completeness_percent']?? 0) === 100
 && (int) ($quality['checks_total']?? 0) > 0
 && (int) ($quality['checks_passed']?? -1) === (int) ($quality['checks_total']?? 0)
 && (int) ($quality['checks_total']?? 0) === count($qualityChecks)
 && collect($qualityChecks)->every(fn ($passed): bool => $passed === true);
 // Reliability is a confidence/coverage signal, not a correctness rate.
 // A calibrated score can legitimately have limited coverage while its
 // provider narrative passes every evidence, provenance, and safety check.
 $scoreHandlingIsSafe = $isScoreable
 ? is_numeric($quality['reliability_percent']?? null)
 && is_finite((float) $quality['reliability_percent'])
 && (float) $quality['reliability_percent'] >= 0
 && (float) $quality['reliability_percent'] <= 100
 : (bool) ($item['insufficient_data']?? false)
 && $this->scoreValue($item['scoring_confidence']?? 100) <= ($isSkipped? 0: 25)
 && $this->scoreValue($item['score']?? 100) <= ($isSkipped? 0: 10);
 $visibleFeedbackIsUsable = AIService::visibleReviewFeedbackIsProviderUsable((string) ($item['ai_feedback']?? ''), $answerData);
 $possibleAnswerIsUsable = ($isTooShort && trim((string) ($item['better_sample_answer'] ?? '')) === '')
 || AIService::visibleReviewPossibleAnswerIsProviderUsable((string) ($item['better_sample_answer']?? ''), $answerData);
 $quotes = $item['evidence_quotes']?? null;
 $normalizedAnswerText = trim((string) preg_replace('/\s+/u', ' ', $this->answerContent($answer)));
 $evidenceIsVerbatim = is_array($quotes) && array_is_list($quotes)
 && ($isSkipped? $quotes === []: $quotes !== [] && collect($quotes)->every(
 fn ($quote): bool => is_string($quote) && trim($quote) !== ''
 && str_contains($normalizedAnswerText, trim((string) preg_replace('/\s+/u', ' ', $quote)))
 ));
 $numericScoresAreSafe = collect([
 'score', 'clarity_score', 'relevance_score', 'grammar_score',
 'professionalism_score', 'star_method_score', 'scoring_confidence',
 ])->every(function (string $field) use ($item, $isSkipped, $isTooShort): bool {
 $value = $item[$field]?? null;
 if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < 0 || (float) $value > 100) {
 return false;
 }
 // An unscored answer must not silently contribute high dimension scores.
 return ! $isSkipped && ! $isTooShort
 || (float) $value <= ($isSkipped? 0: ($field === 'scoring_confidence'? 25: 10));
 });

 $answerChecks[$id] = [
 'complete' => true,
 'provider_evidence_validated' => ($item['evaluation_source']?? null) === 'ai_evidence_validated',
 'provider_narrative_source' => ($item['answer_review_source'] ?? null) === 'ai_provider'
 && ($isSkipped || trim((string) ($item['better_sample_answer'] ?? '')) === '' || ($item['better_sample_answer_source'] ?? null) === 'ai_provider'),
 'all_safeguards_passed' => $qualityVerified,
 'eligibility_matches_saved_answer' => ($item['is_skipped']?? null) === $isSkipped
 && ($item['is_too_short']?? null) === $isTooShort,
 'evidence_quotes_verbatim' => $evidenceIsVerbatim,
 'numeric_scores_safe' => $numericScoresAreSafe,
 'score_handling_safe' => $scoreHandlingIsSafe,
 'visible_feedback_usable' => $visibleFeedbackIsUsable,
 'possible_answer_usable' => $possibleAnswerIsUsable,
 ];
 }

 $minimumScoreableReliability = $scoreableReliabilities !== []? min($scoreableReliabilities): null;
 $checks = [
 'api_provider_supported' => $this->isApiProviderKey($providerKey),
 'provider_metadata_matches' => AIService::normalizeProviderKey($feedback['_provider_key']?? null) === $providerKey,
 'strict_provider_validation_passed' => ($feedback['_strict_validation_passed']?? null) === true,
 'provider_narratives_verified' => ($feedback['_provider_narratives_verified'] ?? null) === true,
 'provider_repair_safe' => ($feedback['_repair_applied']?? null) === false,
 'exact_answer_count' => count($items) === count($expectedIds),
 'unique_answer_ids' => count($suppliedIds) === count(array_unique($suppliedIds)) && ! in_array('', $suppliedIds, true),
 'exact_answer_coverage' => $sortedExpectedIds === $sortedSuppliedIds,
 'every_answer_verified' => count($answerChecks) === count($expectedIds)
 && collect($answerChecks)->every(fn (array $checks): bool => collect($checks)->every(fn ($passed): bool => $passed === true)),
 'aggregate_safeguards_passed' => ($aggregateQuality['status']?? null) === 'verified'
 && (int) ($aggregateQuality['completeness_percent']?? 0) === 100
 && (int) ($aggregateQuality['checks_total']?? 0) > 0
 && (int) ($aggregateQuality['checks_passed']?? -1) === (int) ($aggregateQuality['checks_total']?? 0)
 && is_numeric($aggregateQuality['reliability_percent']?? null)
 && is_finite((float) $aggregateQuality['reliability_percent'])
 && (float) $aggregateQuality['reliability_percent'] >= 0
 && (float) $aggregateQuality['reliability_percent'] <= 100,
 'review_context_matches_provider' => ($reviewContext['schema_version']?? null) === 'interview_feedback_v9'
 && ($reviewContext['review_policy_version']?? null) === AIService::REVIEW_POLICY_VERSION
 && ($reviewContext['provider_source']?? null) === 'ai_provider'
 && AIService::normalizeProviderKey($reviewContext['provider_key']?? null) === $providerKey,
 'review_context_quality_matches' => ($contextQuality['status']?? null) === ($aggregateQuality['status']?? null)
 && (int) ($contextQuality['completeness_percent']?? -1) === (int) ($aggregateQuality['completeness_percent']?? -2)
 && is_numeric($contextQuality['reliability_percent']?? null)
 && is_finite((float) $contextQuality['reliability_percent'])
 && (float) $contextQuality['reliability_percent'] >= 0
 && (float) $contextQuality['reliability_percent'] <= 100
 && $this->scoreValue($contextQuality['reliability_percent']) === $this->scoreValue($aggregateQuality['reliability_percent']?? -1),
 'review_context_coverage_complete' => (int) data_get($reviewContext, 'coverage.answer_count', -1) === count($expectedIds)
 && (int) data_get($reviewContext, 'coverage.provider_validated_count', -1) === count($expectedIds),
 'session_feedback_usable' => AIService::visibleSessionFeedbackIsProviderUsable($sessionFeedback, $answersData),
 ];
 $failedChecks = array_keys(array_filter($checks, fn (bool $passed): bool => ! $passed));

 return [
 'schema_version' => self::REVIEW_VALIDATION_SCHEMA,
 'status' => $failedChecks === []? 'verified': 'rejected',
 'required_reliability_percent' => $requiredReliability,
 'aggregate_reliability_percent' => is_numeric($aggregateQuality['reliability_percent']?? null)
 ? $this->scoreValue($aggregateQuality['reliability_percent'])
 : null,
 'minimum_scoreable_reliability_percent' => $minimumScoreableReliability,
 'scoreable_reliability_threshold_met' => $scoreableReliabilities === []
 || $minimumScoreableReliability >= $requiredReliability,
 'acceptance_basis' => 'provider_provenance_and_required_evidence_safeguards',
 'answers_expected' => count($expectedIds),
 'answers_validated' => count(array_filter($answerChecks, fn (array $item): bool => $item['complete'] === true)),
 'scoreable_answers' => count($scoreableReliabilities),
 'insufficient_answers' => count($expectedIds) - count($scoreableReliabilities),
 'checks' => $checks,
 'answer_checks' => $answerChecks,
 'reasons' => $failedChecks,
 'validated_at' => now()->toIso8601String(),
 ];
 }

 private function summaryReviewValidation(array $validation): array
 {
 if ($validation === []) {
 return [];
 }

 return [
 'schema_version' => trim((string) ($validation['schema_version']?? self::REVIEW_VALIDATION_SCHEMA)),
 'status' => trim((string) ($validation['status']?? 'rejected')),
 'required_reliability_percent' => $this->scoreValue($validation['required_reliability_percent']?? self::MINIMUM_RELIABILITY_PERCENT),
 'aggregate_reliability_percent' => is_numeric($validation['aggregate_reliability_percent']?? null)
 ? $this->scoreValue($validation['aggregate_reliability_percent'])
 : null,
 'minimum_scoreable_reliability_percent' => is_numeric($validation['minimum_scoreable_reliability_percent']?? null)
 ? $this->scoreValue($validation['minimum_scoreable_reliability_percent'])
 : null,
 'scoreable_reliability_threshold_met' => (bool) ($validation['scoreable_reliability_threshold_met']?? false),
 'acceptance_basis' => (string) ($validation['acceptance_basis']?? 'provider_provenance_and_required_evidence_safeguards'),
 'answers_expected' => max(0, (int) ($validation['answers_expected']?? 0)),
 'answers_validated' => max(0, (int) ($validation['answers_validated']?? 0)),
 'scoreable_answers' => max(0, (int) ($validation['scoreable_answers']?? 0)),
 'insufficient_answers' => max(0, (int) ($validation['insufficient_answers']?? 0)),
 'checks' => is_array($validation['checks']?? null)? $validation['checks']: [],
 'validated_at' => trim((string) ($validation['validated_at']?? '')),
 'input_fingerprint' => trim((string) ($validation['input_fingerprint']?? '')),
 'limitation' => 'This confirms required evidence and scoring safeguards passed. It does not guarantee perfect accuracy or replace human review.',
 ];
 }

 private function minimumReliabilityPercent(): int
 {
 return max(
 self::MINIMUM_RELIABILITY_PERCENT,
 min(100, (int) config('services.ai_feedback_review.min_reliability_percent', self::MINIMUM_RELIABILITY_PERCENT))
 );
 }

 private function configuredProvider(?string $preferredProvider):?string
 {
 return AIService::detailedReviewProviderPriority()[0] ?? null;
 }

 private function reviewRefreshRuntimeOptions(?int $deadlineSeconds = null): array
 {
 return [
 'require_provider_narratives' => true,
 'allow_provider_completion' => true,
 'timeout_seconds' => (int) RuntimeConfiguration::get('services.ai_feedback_review.timeout_seconds', 'AI_FEEDBACK_REVIEW_REFRESH_TIMEOUT', 30),
 'deadline_seconds' => max(3, min($deadlineSeconds ?? 120, (int) RuntimeConfiguration::get('services.ai_feedback_review.deadline_seconds', 'AI_FEEDBACK_REVIEW_REFRESH_DEADLINE_SECONDS', 120))),
 'max_attempts' => (int) RuntimeConfiguration::get('services.ai_feedback_review.max_attempts', 'AI_FEEDBACK_REVIEW_REFRESH_ATTEMPTS', 2),
 'http_attempts' => (int) RuntimeConfiguration::get('services.ai_feedback_review.http_attempts', 'AI_FEEDBACK_REVIEW_REFRESH_HTTP_ATTEMPTS', 2),
 'retry_delay_ms' => (int) RuntimeConfiguration::get('services.ai_feedback_review.retry_delay_ms', 'AI_FEEDBACK_REVIEW_REFRESH_RETRY_DELAY_MS', 250),
 ];
 }

 public function hasConfiguredProvider(): bool
 {
 return $this->configuredProvider(null) !== null;
 }

 public function answerReviewProviderState(InterviewAnswer $answer, bool $inputsStale = false): array
 {
 $input = $this->answerData($answer);
 $eligibility = AIService::feedbackAnswerEligibility($input);
 $coaching = is_array($answer->coaching_feedback) ? $answer->coaching_feedback : [];
 $provenance = (array) ($coaching['provider_narrative'] ?? []);
 $provider = AIService::normalizeProviderKey($answer->ai_provider);
 $verified = ! $inputsStale && $this->isApiProviderKey($provider)
 && ($provenance['verified'] ?? false) === true
 && hash_equals((string) ($provenance['input_fingerprint'] ?? ''), $this->answerInputFingerprint($answer));
 $feedback = trim((string) $answer->ai_feedback);
 $possible = trim((string) $answer->better_sample_answer);
 $feedbackReady = $verified
 && data_get($coaching, 'content_alignment.answer_review_source') === 'ai_provider'
 && AIService::normalizeProviderKey(data_get($coaching, 'content_alignment.answer_review_provider')) === $provider
 && hash_equals((string) ($provenance['feedback_hash'] ?? ''), hash('sha256', $feedback))
 && AIService::visibleReviewFeedbackIsProviderUsable($feedback, $input);
 $possibleReady = $verified && $possible !== ''
 && data_get($coaching, 'content_alignment.possible_answer_source') === 'ai_provider'
 && AIService::normalizeProviderKey(data_get($coaching, 'content_alignment.possible_answer_provider')) === $provider
 && hash_equals((string) ($provenance['possible_answer_hash'] ?? ''), hash('sha256', $possible))
 && AIService::visibleReviewPossibleAnswerIsProviderUsable($possible, $input);

 // A completed report may intentionally use the deterministic local path when
 // every API provider is unavailable. Keep older locally stored answer notes
 // hidden, but allow this explicitly marked recovery output to be useful now.
 $localFallback = ! $inputsStale && ($coaching['local_fallback'] ?? false) === true;
 if ($localFallback) {
 $local = AIService::localReviewQuestionFeedback($input);
 $localFeedback = trim((string) ($local['ai_feedback'] ?? ''));
 $localPossible = trim((string) ($local['better_sample_answer'] ?? ''));

 return [
 'feedback_ready' => $localFeedback !== '',
 'possible_answer_ready' => ! $eligibility['is_skipped'] && ! $eligibility['is_too_short'] && $localPossible !== '',
 'feedback' => $localFeedback,
 'possible_answer' => $localPossible,
 'skipped' => $eligibility['is_skipped'],
 'insufficient' => $eligibility['is_too_short'],
 'local_fallback' => true,
 'source' => 'local_fallback',
 ];
 }

 return [
 'feedback_ready' => $feedbackReady,
 'possible_answer_ready' => $possibleReady,
 'feedback' => $feedbackReady ? $feedback : '',
 'possible_answer' => $possibleReady ? $possible : '',
 'skipped' => $eligibility['is_skipped'],
 'insufficient' => $eligibility['is_too_short'],
 ];
 }

 private function answerInputFingerprint(InterviewAnswer $answer): string
 {
 return hash('sha256', json_encode($this->answerData($answer), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
 }

 private function answerNeedsApiProviderVisibleFeedback(InterviewAnswer $answer): bool
 {
 if ((bool) ($answer->is_skipped?? false) || $this->answerContent($answer) === '') {
 return false;
 }

 $coachingFeedback = is_array($answer->coaching_feedback?? null)? $answer->coaching_feedback: [];
 $source = trim((string) data_get($coachingFeedback, 'content_alignment.answer_review_source', ''));
 $provider = AIService::normalizeProviderKey(data_get($coachingFeedback, 'content_alignment.answer_review_provider', $answer->ai_provider));

 return $source !== 'ai_provider'
 || ! $this->isApiProviderKey($provider)
 || ! AIService::visibleReviewFeedbackIsProviderUsable(
 (string) ($answer->ai_feedback?? ''),
 $this->answerData($answer)
 );
 }

 private function answerNeedsApiProviderPossibleAnswer(InterviewAnswer $answer): bool
 {
 if ((bool) ($answer->is_skipped?? false) || $this->answerContent($answer) === '') {
 return false;
 }

 if (TranscriptService::wordCount($this->answerContent($answer)) < 10) {
 return false;
 }

 if (trim((string) ($answer->better_sample_answer?? '')) === '') {
 return true;
 }

 $coachingFeedback = is_array($answer->coaching_feedback?? null)? $answer->coaching_feedback: [];
 $source = trim((string) data_get($coachingFeedback, 'content_alignment.possible_answer_source', ''));
 $provider = AIService::normalizeProviderKey(data_get($coachingFeedback, 'content_alignment.possible_answer_provider', $answer->ai_provider));

 $feedbackQuality = is_array($coachingFeedback['feedback_quality']?? null)
 ? $coachingFeedback['feedback_quality']
 : [];
 $qualityChecks = is_array($feedbackQuality['checks']?? null)? $feedbackQuality['checks']: [];
 $qualityVerified = ($feedbackQuality['status']?? null) === 'verified'
 && (int) ($feedbackQuality['completeness_percent']?? 0) === 100
 && (int) ($feedbackQuality['checks_total']?? 0) > 0
 && (int) ($feedbackQuality['checks_passed']?? -1) === (int) ($feedbackQuality['checks_total']?? 0)
 && (int) ($feedbackQuality['checks_total']?? 0) === count($qualityChecks)
 && collect($qualityChecks)->every(fn ($passed): bool => $passed === true)
 && is_numeric($feedbackQuality['reliability_percent']?? null)
 && is_finite((float) $feedbackQuality['reliability_percent'])
 && (float) $feedbackQuality['reliability_percent'] >= 0
 && (float) $feedbackQuality['reliability_percent'] <= 100;

 return $source !== 'ai_provider'
 || ! $this->isApiProviderKey($provider)
 || ! $qualityVerified
 || ! AIService::visibleReviewPossibleAnswerIsProviderUsable(
 (string) ($answer->better_sample_answer?? ''),
 $this->answerData($answer)
 );
 }

 private function sessionNeedsApiProviderVisibleSummary(InterviewSession $session): bool
 {
 if (! $session->feedback instanceof Feedback) {
 return true;
 }

 $summary = is_array($session->feedback->coaching_summary?? null)? $session->feedback->coaching_summary: [];
 $providerSource = trim((string) data_get($summary, 'review_context.provider_source', ''));
 $providerKey = AIService::normalizeProviderKey(data_get($summary, 'review_context.provider_key', null));

 if ($providerSource !== '' && $providerSource !== 'ai_provider') {
 return true;
 }

 if ($providerKey !== '' && ! $this->isApiProviderKey($providerKey)) {
 return true;
 }

 return ! AIService::visibleSessionFeedbackIsProviderUsable([
 'overall_summary' => data_get($summary, 'overall_summary', ''),
 'strengths' => $session->feedback->strengths,
 'weaknesses' => $session->feedback->weaknesses,
 'improvement_suggestions' => $session->feedback->improvement_suggestions,
 ], $this->answersData($this->originalAnswers($session)));
 }

 private function isApiProviderKey(?string $provider): bool
 {
 $provider = AIService::normalizeProviderKey($provider);

 return $provider !== ''
 && ! in_array($provider, ['local', 'localmodel'], true)
 && AIService::providerIsSupported($provider);
 }

 private function withPossibleAnswerProviderMetadata(array $coachingFeedback, string $providerKey,?string $sourceKey = null,?string $answerProviderKey = null): array
 {
 $providerKey = AIService::normalizeProviderKey($answerProviderKey?: $providerKey);
 $sourceKey = trim((string) $sourceKey);
 $source = match ($sourceKey !== ''? $sourceKey: $providerKey) {
 'ai_provider', 'ai_evidence_validated' => 'ai_provider',
 'local_trained_model', 'localmodel' => 'local_trained_model',
 'local_evidence', 'local' => 'local_evidence',
 default => $providerKey!== '' && ! in_array($providerKey, ['local', 'localmodel'], true)? 'ai_provider': 'local_evidence',
 };

 if (! is_array($coachingFeedback['content_alignment']?? null)) {
 $coachingFeedback['content_alignment'] = [];
 }

 $coachingFeedback['content_alignment']['possible_answer_source'] = $source;
 $coachingFeedback['content_alignment']['possible_answer_provider'] = $providerKey;
 $evaluationSource = trim((string) ($coachingFeedback['content_alignment']['evaluation_source']?? ''));
 $answerReviewSource = match ($evaluationSource) {
 'ai_evidence_validated' => 'ai_provider',
 'local_trained_model' => 'local_trained_model',
 'local_evidence', 'local_fallback' => 'local_evidence',
 default => $source,
 };
 if ($answerReviewSource === 'ai_provider') {
 $coachingFeedback['content_alignment']['answer_review_source'] = $answerReviewSource;
 $coachingFeedback['content_alignment']['answer_review_provider'] = $providerKey;
 foreach (['missing_points', 'improvement_focus'] as $field) {
 if (($coachingFeedback['content_alignment'][$field.'_source']?? null) === 'ai_provider') {
 $coachingFeedback['content_alignment'][$field.'_provider'] = $providerKey;
 }
 }
 }

 return $coachingFeedback;
 }

 private function sessionData(InterviewSession $session): array
 {
 $session->loadMissing('user');

 return [
 'target_position' => $session->target_position,
 'difficulty' => $session->difficulty,
 'interview_focus' => $session->interview_focus,
 'country' => 'General',
 'ai_assistance_level' => $session->ai_assistance_level,
 'assessment_mode' => $session->assessment_mode,
 'accommodation_profile' => $session->accommodation_profile,
 'target_language' => Setting::languageConfig(Setting::preferredLanguageFor($session->user)),
 ];
 }

 private function answersData(Collection $answers): array
 {
 return $answers->map(fn (InterviewAnswer $answer): array => $this->answerData($answer))->values()->all();
 }

 private function answerData(InterviewAnswer $answer): array
 {
 return [
 'id' => $answer->id,
 'question' => $answer->question->question_text?? '',
 'question_type' => $answer->question->type?? null,
 'answer' => $this->answerContent($answer) ?: ((bool) ($answer->is_skipped?? false)? '(Skipped or no answer)': ''),
 'is_skipped' => (bool) ($answer->is_skipped?? false),
 'expected_guide' => $answer->question->expected_guide?? null,
 'mapped_skills' => $answer->question->mapped_skills?? [],
 'response_mode' => $answer->response_mode?? 'text',
 'voice_duration' => (int) ($answer->voice_duration?? 0),
 'uses_asr_transcript' => $this->answerContentUsesAsrFormatting($answer),
 ];
 }

 private function answerContentUsesAsrFormatting(InterviewAnswer $answer): bool
 {
 $mode = Str::lower(trim((string) ($answer->response_mode?? 'text')));
 if ($mode === 'voice') {
 return true;
 }

 if (! in_array($mode, ['hybrid', 'voice_and_text'], true)) {
 return false;
 }

 $answerText = trim((string) ($answer->answer_text?? ''));
 $deliveryTranscript = trim((string) ($answer->delivery_transcript?? ''));

 if ($deliveryTranscript!== '' && $answerText === $deliveryTranscript) {
 return true;
 }

 return $answerText === ''
 && ($deliveryTranscript!== '' || trim((string) ($answer->voice_recording_path?? ''))!== '');
 }

 private function originalAnswers(InterviewSession $session): Collection
 {
 if ($session->relationLoaded('answers')) {
 return $session->answers
 ->filter(fn ($answer): bool => $answer instanceof InterviewAnswer && ($answer->retry_of_answer_id?? null) === null)
 ->values();
 }

 return $this->originalAnswersQuery($session)->get();
 }

 /** @return \Illuminate\Database\Eloquent\Builder<InterviewAnswer> */
 private function originalAnswersQuery(InterviewSession $session): \Illuminate\Database\Eloquent\Builder
 {
 return InterviewAnswer::with('question')
 ->where('interview_session_id', $session->id)
 ->whereNull('retry_of_answer_id');
 }

 public function reviewInputsMatchSavedValidation(InterviewSession $session): bool
 {
 $session->loadMissing(['feedback', 'answers.question']);
 $saved = (string) data_get($session->feedback?->coaching_summary ?? [],
 'review_context.detailed_review_validation.input_fingerprint', '');

 return $saved === '' || hash_equals($saved, $this->reviewInputFingerprint($session, $this->originalAnswers($session)));
 }

 private function reviewInputFingerprint(InterviewSession $session, Collection $answers): string
 {
 $inputs = [
 'session' => $session->only([
 'id', 'user_id', 'status', 'game_level_id', 'target_position', 'difficulty',
 'interview_focus', 'ai_assistance_level', 'assessment_mode', 'accommodation_profile',
 ]),
 'answers' => $answers->sortBy('id')->map(fn (InterviewAnswer $answer): array => [
 'content' => $this->answerData($answer),
 'observations' => array_merge($answer->only([
 'question_id', 'delivery_transcript', 'observation_data', 'pronunciation_analysis',
 'pronunciation_score', 'voice_recording_path',
 ]), [
 'wpm' => (int) ($answer->wpm?? 0),
 'filler_words_count' => (int) ($answer->filler_words_count?? 0),
 'pause_count' => (int) ($answer->pause_count?? 0),
 'timed_out' => (bool) ($answer->timed_out?? false),
 'elapsed_seconds' => (int) ($answer->elapsed_seconds?? 0),
 ]),
 ])->values()->all(),
 ];

 return hash('sha256', json_encode($inputs, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
 }

 private function coachingMetricsFromAnswer(InterviewAnswer $answer, array $feedback): array
 {
 return [
 'answer_id' => $answer->id,
 'response_mode' => $answer->response_mode?? 'text',
 'voice_duration' => $answer->voice_duration?? 0,
 'wpm' => $answer->wpm?? 0,
 'filler_words_count' => $answer->filler_words_count?? 0,
 'pause_count' => $answer->pause_count?? 0,
 'delivery_transcript' => $answer->delivery_transcript,
 'pronunciation_analysis' => $answer->pronunciation_analysis,
 'pronunciation_score' => $answer->pronunciation_score,
 'scoring_confidence' => $this->scoreValue($feedback['scoring_confidence']?? $answer->scoring_confidence?? 0),
 'relevance_score' => $this->scoreValue($feedback['relevance_score']?? 0),
 'evidence_quotes' => is_array($feedback['evidence_quotes']?? null)? $feedback['evidence_quotes']: [],
 'missing_evidence' => is_array($feedback['missing_evidence']?? null)? $feedback['missing_evidence']: [],
 'evaluation_source' => is_scalar($feedback['evaluation_source']?? null)? trim((string) $feedback['evaluation_source']): null,
 'answer_alignment' => is_scalar($feedback['answer_alignment']?? null)? trim((string) $feedback['answer_alignment']): null,
 'question_focus' => is_scalar($feedback['question_focus']?? null)? trim((string) $feedback['question_focus']): null,
 'feedback_quality' => is_array($feedback['feedback_quality']?? null)? $feedback['feedback_quality']: [],
 'provider_coaching' => is_array($feedback['provider_coaching']?? null)? $feedback['provider_coaching']: [],
 'is_skipped' => (bool) ($feedback['is_skipped']?? $answer->is_skipped?? false),
 'is_too_short' => (bool) ($feedback['is_too_short']?? false),
 ];
 }

 private function answerContent(InterviewAnswer $answer): string
 {
 $answerText = trim((string) ($answer->answer_text?? ''));

 return $answerText !== ''? $answerText: trim((string) ($answer->delivery_transcript?? ''));
 }

 private function scoreValue(mixed $value): int
 {
 return max(0, min(100, (int) round(is_numeric($value)? (float) $value: 0)));
 }
}
