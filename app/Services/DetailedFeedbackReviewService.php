<?php

namespace App\Services;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Score;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DetailedFeedbackReviewService
{
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
 true,
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

 $this->persistProviderFeedback($session, $answers, $feedback, $providerKey);

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

 foreach ($this->originalAnswers($session) as $answer) {
 if (! $answer instanceof InterviewAnswer) {
 continue;
 }

 $answerProvider = AIService::normalizeProviderKey($answer->ai_provider);
 $evaluationSource = trim((string) data_get($answer->coaching_feedback?? [], 'content_alignment.evaluation_source', ''));
 if ($answerProvider === ''
 || in_array($answerProvider, ['local', 'localmodel'], true)
 || $evaluationSource !== 'ai_evidence_validated'
 || $this->answerNeedsApiProviderPossibleAnswer($answer)
 || trim((string) ($answer->ai_feedback?? '')) === ''
 ) {
 return true;
 }
 }

 return false;
 }

 private function persistProviderFeedback(InterviewSession $session, Collection $answers, array $feedback, string $providerKey): void
 {
 DB::transaction(function () use ($session, $answers, $feedback, $providerKey): void {
 $feedbackById = collect($feedback['per_question_feedback']?? [])
 ->filter(fn ($item): bool => is_array($item) && isset($item['id']))
 ->keyBy(fn (array $item): string => (string) $item['id']);

 $totals = [
 'clarity_score' => 0,
 'relevance_score' => 0,
 'grammar_score' => 0,
 'professionalism_score' => 0,
 ];

 foreach ($answers as $answer) {
 if (! $answer instanceof InterviewAnswer) {
 continue;
 }

 $item = $feedbackById->get((string) $answer->id);
 if (! is_array($item)) {
 continue;
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

 $answer->forceFill([
 'ai_feedback' => trim((string) ($item['ai_feedback']?? '')),
 'better_sample_answer' => trim((string) ($item['better_sample_answer']?? '')),
 'follow_up_question' => trim((string) ($item['follow_up_question']?? '')),
 'clarity_score' => $clarity,
 'relevance_score' => $relevance,
 'grammar_score' => $grammar,
 'score' => $answerScore,
 'scoring_confidence' => $this->scoreValue($item['scoring_confidence']?? 80),
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
 $starScore = $this->scoreValue($sessionFeedback['star_method_score']?? 0);
 $metadata = $this->assessment->sessionMetadata($session, $evaluatedAnswers, $metrics, $starScore);
 $overall = array_key_exists('overall_readiness_score', $sessionFeedback)
 ? $this->scoreValue($sessionFeedback['overall_readiness_score'])
 : $metadata['overall'];
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
 'coaching_summary' => $this->sessionCoachingSummary($evaluatedAnswers, $sessionFeedback),
 ]);

 if ($session->readinessScoreEligible()) {
 Profile::where('user_id', $session->user_id)->update(['readiness_score' => $score->overall_readiness_score]);
 }
 });
 }

 private function sessionCoachingSummary(Collection $answers, array $sessionFeedback): array
 {
 $summary = $this->coaching->sessionSummary($answers->values());
 $overallSummary = trim((string) ($sessionFeedback['overall_summary']?? ''));
 if ($overallSummary !== '') {
 $summary['overall_summary'] = Str::limit($overallSummary, 700, '');
 $summary['overall_summary_source'] = 'ai_provider_validated';
 }

 return $summary;
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
 'timeout_seconds' => (int) env('AI_FEEDBACK_REVIEW_REFRESH_TIMEOUT', env('AI_FEEDBACK_TIMEOUT', 30)),
 'deadline_seconds' => (int) env('AI_FEEDBACK_REVIEW_REFRESH_DEADLINE_SECONDS', env('AI_FEEDBACK_DEADLINE_SECONDS', 60)),
 'max_attempts' => (int) env('AI_FEEDBACK_REVIEW_REFRESH_ATTEMPTS', env('AI_FEEDBACK_ATTEMPTS', 2)),
 'http_attempts' => (int) env('AI_FEEDBACK_REVIEW_REFRESH_HTTP_ATTEMPTS', env('AI_FEEDBACK_HTTP_ATTEMPTS', 2)),
 'retry_delay_ms' => (int) env('AI_FEEDBACK_REVIEW_REFRESH_RETRY_DELAY_MS', env('AI_FEEDBACK_RETRY_DELAY_MS', 250)),
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

 private function answerNeedsApiProviderPossibleAnswer(InterviewAnswer $answer): bool
 {
 if ((bool) ($answer->is_skipped?? false) || $this->answerContent($answer) === '') {
 return false;
 }

 if (trim((string) ($answer->better_sample_answer?? '')) === '') {
 return true;
 }

 $coachingFeedback = is_array($answer->coaching_feedback?? null)? $answer->coaching_feedback: [];
 $source = trim((string) data_get($coachingFeedback, 'content_alignment.possible_answer_source', ''));
 $provider = AIService::normalizeProviderKey(data_get($coachingFeedback, 'content_alignment.possible_answer_provider', $answer->ai_provider));

 return $source !== 'ai_provider' || ! $this->isApiProviderKey($provider);
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
 return $answers->map(fn (InterviewAnswer $answer): array => [
 'id' => $answer->id,
 'question' => $answer->question->question_text?? '',
 'question_type' => $answer->question->type?? null,
 'answer' => $this->answerContent($answer) ?: ((bool) ($answer->is_skipped?? false)? '(Skipped or no answer)': ''),
 'is_skipped' => (bool) ($answer->is_skipped?? false),
 'expected_guide' => $answer->question->expected_guide?? null,
 'mapped_skills' => $answer->question->mapped_skills?? [],
 ])->values()->all();
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
