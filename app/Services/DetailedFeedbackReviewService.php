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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DetailedFeedbackReviewService
{
 private const REVIEW_VALIDATION_SCHEMA = 'detailed_review_validation_v1';

 private const MINIMUM_RELIABILITY_PERCENT = 95;

 public function __construct(
 private readonly TrustworthyAssessmentService $assessment,
 private readonly EvidenceBasedCoachingService $coaching
 ) {
 }

 public function refreshWithProvidersIfNeeded(InterviewSession $session,?string $preferredProvider = null): bool
 {
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

 try {
 $feedback = AIService::generateFeedback(
 $sessionData,
 $answersData,
 $provider,
 false,
 false,
 $this->reviewRefreshRuntimeOptions()
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
 if ($session->status !== 'completed' || (int) ($session->game_level_id?? 0) > 0) {
 return false;
 }

 $session->loadMissing(['answers.question', 'feedback', 'score']);

 if ($this->apiProviderRefreshBlocked($session)) {
 return false;
 }

 if (! $session->feedback instanceof Feedback || ! $session->score instanceof Score) {
 return true;
 }

 $summarySource = trim((string) data_get($session->feedback->coaching_summary?? [], 'overall_summary_source', ''));
 if ($summarySource !== 'ai_provider_validated') {
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

 if ($this->sessionNeedsApiProviderVisibleSummary($session)) {
 return true;
 }

 foreach ($this->originalAnswers($session) as $answer) {
 if (! $answer instanceof InterviewAnswer) {
 continue;
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

 private function persistProviderFeedback(InterviewSession $session, Collection $answers, array $feedback, string $providerKey, array $validation): void
 {
 DB::transaction(function () use ($session, $answers, $feedback, $providerKey, $validation): void {
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
 if (! $answer instanceof InterviewAnswer) {
 continue;
 }

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
 $evidence = $this->assessment->answerEvidence($answerText, $item['ai_feedback']?? null, $answer->question);
 $rubric = $this->assessment->rubricLevel($answerScore);
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
 if (data_get($coachingFeedback, 'feedback_quality.status') !== 'verified'
 || (int) data_get($coachingFeedback, 'feedback_quality.completeness_percent', 0) !== 100) {
 throw new \RuntimeException("Detailed review coaching checks failed for answer {$answer->id}.");
 }

 $answer->forceFill([
 'ai_feedback' => trim((string) ($item['ai_feedback']?? '')),
 'better_sample_answer' => trim((string) ($item['better_sample_answer']?? '')),
 'follow_up_question' => trim((string) ($item['follow_up_question']?? '')),
 'clarity_score' => $clarity,
 'relevance_score' => $relevance,
 'grammar_score' => $grammar,
 'score' => $answerScore,
 'scoring_confidence' => $this->scoreValue($item['scoring_confidence']?? 0),
 'evidence_map' => $evidence,
 'rubric_level' => $rubric['level'],
 'recommendation_text' => $rubric['next_level'],
 'improved_answer_source' => 'candidate_facts',
 'coaching_feedback' => $coachingFeedback,
 'ai_provider' => $providerKey,
 ])->save();
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
 $starScore = $totals['star_applicable_count'] > 0
 ? $this->scoreValue($totals['star_method_score'] / $totals['star_applicable_count'])
 : 0;
 $metadata = $this->assessment->sessionMetadata($session, $evaluatedAnswers, $metrics, $starScore);
 $overall = $this->scoreValue($totals['score'] / $count);
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
 'delivery_stability_score' => $metadata['delivery_stability'],
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
 $summary['overall_summary_source'] = 'ai_provider_validated';
 }

 return $summary;
 }

 private function summaryReviewContext(array $reviewContext, array $validation = []): array
 {
 return [
 'schema_version' => trim((string) ($reviewContext['schema_version']?? 'interview_feedback_v9')),
 'rubric_name' => trim((string) ($reviewContext['rubric_name']?? 'SpeakReady detailed interview feedback rubric')),
 'rubric_version' => trim((string) ($reviewContext['rubric_version']?? 'score-v'.TrustworthyAssessmentService::SCORE_VERSION)),
 'score_version' => (int) ($reviewContext['score_version']?? TrustworthyAssessmentService::SCORE_VERSION),
 'coaching_version' => (int) ($reviewContext['coaching_version']?? EvidenceBasedCoachingService::VERSION),
 'provider_key' => AIService::normalizeProviderKey($reviewContext['provider_key']?? null),
 'provider_source' => trim((string) ($reviewContext['provider_source']?? 'ai_provider')),
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
 $isSkipped = (bool) ($item['is_skipped']?? false);
 $isTooShort = (bool) ($item['is_too_short']?? false);
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
 && $qualityChecks !== []
 && collect($qualityChecks)->every(fn ($passed): bool => $passed === true);
 $scoreHandlingIsSafe = $isScoreable
 ? $reliability !== null && $reliability >= $requiredReliability
 : (bool) ($item['insufficient_data']?? false)
 && $this->scoreValue($item['scoring_confidence']?? 100) <= ($isSkipped? 0: 25)
 && $this->scoreValue($item['score']?? 100) <= ($isSkipped? 0: 10);
 $visibleFeedbackIsUsable = $isScoreable
 ? AIService::visibleReviewFeedbackIsProviderUsable((string) ($item['ai_feedback']?? ''), $this->answerData($answer))
 : trim((string) ($item['ai_feedback']?? '')) !== '';
 $possibleAnswerIsUsable = ! $isScoreable
 || AIService::visibleReviewPossibleAnswerIsProviderUsable((string) ($item['better_sample_answer']?? ''), $this->answerData($answer));

 $answerChecks[$id] = [
 'complete' => true,
 'provider_evidence_validated' => ($item['evaluation_source']?? null) === 'ai_evidence_validated',
 'all_safeguards_passed' => $qualityVerified,
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
 'provider_repair_not_applied' => ($feedback['_repair_applied']?? null) === false,
 'exact_answer_count' => count($items) === count($expectedIds),
 'unique_answer_ids' => count($suppliedIds) === count(array_unique($suppliedIds)) && ! in_array('', $suppliedIds, true),
 'exact_answer_coverage' => $sortedExpectedIds === $sortedSuppliedIds,
 'every_answer_verified' => count($answerChecks) === count($expectedIds)
 && collect($answerChecks)->every(fn (array $checks): bool => collect($checks)->every(fn ($passed): bool => $passed === true)),
 'aggregate_safeguards_passed' => ($aggregateQuality['status']?? null) === 'verified'
 && (int) ($aggregateQuality['completeness_percent']?? 0) === 100
 && (int) ($aggregateQuality['checks_total']?? 0) > 0
 && (int) ($aggregateQuality['checks_passed']?? -1) === (int) ($aggregateQuality['checks_total']?? 0)
 && is_numeric($aggregateQuality['reliability_percent']?? null),
 'scoreable_reliability_threshold_met' => $scoreableReliabilities === []
 || $minimumScoreableReliability >= $requiredReliability,
 'review_context_matches_provider' => ($reviewContext['schema_version']?? null) === 'interview_feedback_v9'
 && ($reviewContext['provider_source']?? null) === 'ai_provider'
 && AIService::normalizeProviderKey($reviewContext['provider_key']?? null) === $providerKey,
 'review_context_quality_matches' => ($contextQuality['status']?? null) === ($aggregateQuality['status']?? null)
 && (int) ($contextQuality['completeness_percent']?? -1) === (int) ($aggregateQuality['completeness_percent']?? -2)
 && is_numeric($contextQuality['reliability_percent']?? null)
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
 'answers_expected' => count($expectedIds),
 'answers_validated' => count(array_filter($answerChecks, fn (array $item): bool => ($item['complete']?? false) === true)),
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
 'answers_expected' => max(0, (int) ($validation['answers_expected']?? 0)),
 'answers_validated' => max(0, (int) ($validation['answers_validated']?? 0)),
 'scoreable_answers' => max(0, (int) ($validation['scoreable_answers']?? 0)),
 'insufficient_answers' => max(0, (int) ($validation['insufficient_answers']?? 0)),
 'checks' => is_array($validation['checks']?? null)? $validation['checks']: [],
 'validated_at' => trim((string) ($validation['validated_at']?? '')),
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
 $preferred = AIService::normalizeProviderKey($preferredProvider);
 if ($this->canCallApiProvider($preferred)) {
 return $preferred;
 }

 $default = AIService::defaultProviderKey();
 if ($this->canCallApiProvider($default)) {
 return $default;
 }

 foreach (AIService::supportedProviderOptions() as $option) {
 $key = AIService::normalizeProviderKey($option['key']?? null);
 if (($option['enabled']?? false) && $this->canCallApiProvider($key)) {
 return $key;
 }
 }

 return null;
 }

 private function reviewRefreshRuntimeOptions(): array
 {
 return [
 'timeout_seconds' => (int) RuntimeConfiguration::get('services.ai_feedback_review.timeout_seconds', 'AI_FEEDBACK_REVIEW_REFRESH_TIMEOUT', 30),
 'deadline_seconds' => (int) RuntimeConfiguration::get('services.ai_feedback_review.deadline_seconds', 'AI_FEEDBACK_REVIEW_REFRESH_DEADLINE_SECONDS', 60),
 'max_attempts' => (int) RuntimeConfiguration::get('services.ai_feedback_review.max_attempts', 'AI_FEEDBACK_REVIEW_REFRESH_ATTEMPTS', 2),
 'http_attempts' => (int) RuntimeConfiguration::get('services.ai_feedback_review.http_attempts', 'AI_FEEDBACK_REVIEW_REFRESH_HTTP_ATTEMPTS', 2),
 'retry_delay_ms' => (int) RuntimeConfiguration::get('services.ai_feedback_review.retry_delay_ms', 'AI_FEEDBACK_REVIEW_REFRESH_RETRY_DELAY_MS', 250),
 ];
 }

 private function apiProviderRefreshBlocked(InterviewSession $session): bool
 {
 $blockedUntil = trim((string) data_get($session->action_plan?? [], '_provider_refresh.blocked_until', ''));
 if ($blockedUntil === '') {
 return false;
 }

 try {
 return now()->lt(Carbon::parse($blockedUntil));
 } catch (\Throwable) {
 return false;
 }
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
 $qualityVerified = ($feedbackQuality['status']?? null) === 'verified'
 && (int) ($feedbackQuality['completeness_percent']?? 0) === 100
 && is_numeric($feedbackQuality['reliability_percent']?? null)
 && $this->scoreValue($feedbackQuality['reliability_percent']) >= $this->minimumReliabilityPercent();

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

 private function canCallApiProvider(?string $provider): bool
 {
 $provider = AIService::normalizeProviderKey($provider);

 return $provider !== ''
 && ! in_array($provider, ['local', 'localmodel'], true)
 && AIService::providerIsConfigured($provider);
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

 return InterviewAnswer::with('question')
 ->where('interview_session_id', $session->id)
 ->whereNull('retry_of_answer_id')
 ->get();
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
