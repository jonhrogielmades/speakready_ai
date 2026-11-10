<?php

namespace App\Services;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use Illuminate\Support\Collection;

class TrustworthyAssessmentService
{
 public const SCORE_VERSION = 5;

 public function overallScore(array $metrics, bool $starApplicable, bool $languageScoringEnabled = true): int
 {
 $weights = [
 'clarity' =>.25,
 'relevance' =>.35,
 'professionalism' =>.20,
 'grammar' => $languageScoringEnabled?.10: 0,
 'star' => $starApplicable?.10: 0,
 ];
 $weightTotal = array_sum($weights);
 if ($weightTotal <= 0) {
 return 0;
 }

 $score = 0;
 foreach ($weights as $key => $weight) {
 $score += max(0, min(100, (int) ($metrics[$key]?? 0))) * ($weight / $weightTotal);
 }

 return (int) round($score);
 }

 public function answerEvidence(string $answer,?string $feedback = null, Question|array|null $question = null): array
 {
 $starApplicable = QuestionIntentService::starApplicable($question);
 $signals = AnswerEvidenceSignals::analyze($answer, $starApplicable);
 $evidence = $signals['supporting_excerpts'];
 $questionIntent = QuestionIntentService::classify($question);
 $questionText = QuestionIntentService::text($question);
 $resultRequired = $question === null || QuestionIntentService::requiresResult($question);
 $personalActionRequired = $question === null || QuestionIntentService::requiresPersonalAction($question);
 $hasResult = $signals['has_result'];
 $hasPersonalAction = $signals['has_personal_action'];

 $missing = [];
 if ($resultRequired &&! $hasResult) {
 $missing[] = 'A clear result, effect, or lesson';
 }
 if ($personalActionRequired &&! $hasPersonalAction) {
 $missing[] = 'Your own action';
 }

 return [
 'supporting_excerpts' => $evidence,
 'missing_evidence' => $missing,
 'feedback_basis' => $feedback? mb_substr($feedback, 0, 500): null,
 'question_text' => $questionText,
 'question_intent' => $questionIntent,
 'star_applicable' => $starApplicable,
 'result_required' => $resultRequired,
 'personal_action_required' => $personalActionRequired,
 'has_result' => $hasResult,
 'has_personal_action' => $hasPersonalAction,
 ];
 }

 public function groundedRevisionTemplate(string $answer,?array $evidence = null): string
 {
 $clean = trim(preg_replace('/\s+/', ' ', $answer)?? '');
 if ($clean === '') {
 return '';
 }

 $evidence??= $this->answerEvidence($clean);
 $sentences = $this->answerSentences($clean);
 $resultSentence = $this->resultSentenceFrom($sentences);
 // Keep the full response in its original order: a later qualification or
 // correction can change the meaning of an earlier action or result.
 $answerSentence = $this->sentenceText($clean, PHP_INT_MAX);
 $questionText = trim((string) ($evidence['question_text']?? ''));

 if ($evidence['star_applicable']?? false) {
 return $this->starParagraphRevision($answerSentence, $questionText, $resultSentence, $evidence);
 }

 $intent = (string) ($evidence['question_intent']?? 'direct_evidence');

 [$support, $closing] = match ($intent) {
 'strength' => [
 'This gives a clearer proof point for the strength I named.',
 'I would connect that strength to how I can support the team and handle the role responsibilities.',
 ],
 'strength_and_weakness' => [
 'This keeps the strength and development area clear without adding details I did not give.',
 'I would connect both points to how I work and how I am improving.',
 ],
 'weakness' => [
 'This names the development area honestly and keeps the focus on progress.',
 'I would explain the habit I am building and stay within the example I already gave.',
 ],
 'salary_expectation' => [
 'This keeps the answer open and tied to the responsibilities already discussed.',
 'I would avoid naming a number unless I can support it with a real range.',
 ],
 'motivation', 'role_fit' => [
 'This connects my answer to the role without adding unsupported details.',
 'I would keep the response focused on the experience and contribution I already described.',
 ],
 'self_introduction' => [
 'This gives the interviewer a clearer view of my background.',
 'I would connect that background to the role using only details I can explain truthfully.',
 ],
 'career_transition' => [
 'This explains my reason in a direct and simple way.',
 'I would connect it to the next step I am aiming for without adding details I did not share.',
 ],
 'technical' => [
 'This answers the question by showing the checks I would make before changing the solution.',
 'I would explain what each check tells me and how I would confirm the issue is resolved.',
 ],
 'situational' => [
 'This shows the action I would take and keeps the response practical.',
 $resultSentence !== ''
 ? 'I would keep the true result clear so the interviewer can see what happened next.'
 : 'I would finish by adding the true result or next step, because my saved answer does not include it yet.',
 ],
 default => [
 'This keeps the main point clear and easy to follow.',
 'I would keep the answer focused, direct, and connected to the role.',
 ],
 };

 if ($resultSentence !== '' && ! str_contains($answerSentence, $resultSentence)) {
 return trim($answerSentence.' '.$support.' '.$resultSentence.' '.$closing);
 }

 return trim($answerSentence.' '.$support.' '.$closing);
 }

 private function starParagraphRevision(string $answerSentence, string $questionText, string $resultSentence = '', array $evidence = []): string
 {
 // The question describes what was asked, not what the candidate experienced.
 // Never manufacture a situation or responsibility from that prompt.
 $context = 'I would make the real situation clear using only details I can confirm.';
 $task = 'I would explain my actual responsibility and keep it separate from the team\'s work.';
 $action = preg_match('/\b(?:I|we|my|our)\b/iu', $answerSentence) === 1
 ? $answerSentence
 : 'I would explain it this way: '.$answerSentence;
 $result = $resultSentence !== ''
 ? $this->sentenceText($resultSentence)
 : $this->starResultSentence($questionText, (bool) ($evidence['result_required']?? true));
 if ($result !== '' && str_contains(mb_strtolower($action, 'UTF-8'), mb_strtolower($result, 'UTF-8'))) {
 $result = '';
 }

 return trim($action.' '.$result.' '.$context.' '.$task);
 }

 private function starResultSentence(string $questionText, bool $resultRequired = true): string
 {
 $question = mb_strtolower($questionText, 'UTF-8');

 if ($resultRequired) {
 return match (true) {
 preg_match('/\b(?:customer|client|complaint|concern|upset|angry|irate)\b/u', $question) === 1 => 'I would finish by adding the true customer result or lesson, because my saved answer does not include it yet.',
 preg_match('/\b(?:team|collaborat|coworker|colleague)\b/u', $question) === 1 => 'I would finish by adding the true team result or lesson, because my saved answer does not include it yet.',
 preg_match('/\b(?:project|assignment|task|deadline)\b/u', $question) === 1 => 'I would finish by adding the true project result or lesson, because my saved answer does not include it yet.',
 default => 'I would finish by adding the true result or lesson, because my saved answer does not include it yet.',
 };
 }

 return 'I would keep the answer tied to what actually happened without adding an outcome I cannot confirm.';
 }

 /**
  * @return array<int, string>
  */
 private function answerSentences(string $text): array
 {
 // Decimal numbers and URLs contain periods, not sentence boundaries.
 return preg_split('/(?<=[.!?])\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
 }

 private function resultSentenceFrom(array $sentences): string
 {
 foreach (array_reverse($sentences) as $sentence) {
 if (AnswerEvidenceSignals::analyze($sentence)['has_result']) {
 return $this->sentenceText($sentence);
 }
 }

 return '';
 }

 private function sentenceText(string $text, int $maxSentences = 1): string
 {
 $text = trim(preg_replace('/\s+/', ' ', $text)?? $text);
 if ($text === '') {
 return '';
 }

 $sentences = array_slice($this->answerSentences($text), 0, max(1, $maxSentences));

 $text = trim(implode(' ', $sentences));
 $text = preg_replace('/\bi\b/u', 'I', $text)?? $text;
 $text = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($text, 1, null, 'UTF-8');

 if (preg_match('/[.!?]$/u', $text)!== 1) {
 $text.= '.';
 }

 return $text;
 }

 public function rubricLevel(int $score): array
 {
 return match (true) {
 $score >= 85 => ['level' => '4 - Strong detail', 'next_level' => 'Keep this level of detail under harder follow-ups.'],
 $score >= 70 => ['level' => '3 - Good', 'next_level' => 'Add clearer ownership, limits, tradeoffs, and result.'],
 $score >= 50 => ['level' => '2 - Some detail', 'next_level' => 'Answer the question directly and provide one complete, specific example.'],
 default => ['level' => '1 - Not enough detail', 'next_level' => 'Provide enough clear detail for a job answer.'],
 };
 }

 public function sessionMetadata(InterviewSession $session, Collection $answers, array $metrics, int $starScore): array
 {
 $answerEvidence = $answers->mapWithKeys(function (InterviewAnswer $answer) {
 return [$answer->id => $this->answerEvidence($this->answerContent($answer), $answer->ai_feedback, $answer->question)];
 })->all();
 $answered = $answers->where('is_skipped', false)->filter(fn ($answer) => $answer instanceof InterviewAnswer && $this->answerContent($answer)!== '')->count();
 $confidence = min(95, max(20, 30 + ($answered * 10)));
 if ($answers->contains(fn ($answer) => empty($answer->ai_feedback))) {
 $confidence = max(20, $confidence - 25);
 }
 $answerConfidences = $answers->pluck('scoring_confidence')->filter(fn ($value) => is_numeric($value) && (int) $value > 0);
 if ($answerConfidences->isNotEmpty()) {
 $confidence = min($confidence, (int) floor((float) $answerConfidences->avg() + PHP_FLOAT_EPSILON));
 }
 $starApplicable = $answers->contains(fn ($answer) => QuestionIntentService::starApplicable($answer->question));
 $languageScoring =! ((bool) data_get($session->accommodation_profile, 'separate_language_scoring', false));
 $overall = $this->overallScore([
 'clarity' => $metrics['clarity'],
 'relevance' => $metrics['relevance'],
 'grammar' => $metrics['grammar'],
 'professionalism' => $metrics['professionalism'],
 'star' => $starScore,
 ], $starApplicable, $languageScoring);
 $qualityGate = app(AlgorithmReliabilityService::class)->evaluate(
 'trustworthy_assessment_rubric',
 ['reliability' => $confidence],
 ['reliability'],
 $answered,
 1,
 'answer_evidence_coverage_and_scoring_confidence'
 );

 return [
 'overall' => $overall,
 'readiness_band' => $this->readinessBand($overall),
 'scoring_confidence' => $confidence,
 'algorithm_quality' => $qualityGate,
 'eligible_for_automated_decision' => $qualityGate['passed'],
 'accuracy_guaranteed' => false,
 'evidence_map' => $answerEvidence,
 'rubric' => [
 'version' => self::SCORE_VERSION,
 'scale' => [
 '1' => 'Not enough detail',
 '2' => 'Some detail',
 '3' => 'Good job detail',
 '4' => 'Strong detail with your action and result',
 ],
 'weights' => [
 'clarity' => 25,
 'relevance' => 35,
 'professionalism' => 20,
 'grammar' => $languageScoring? 10: 0,
 'star_when_applicable' => $starApplicable? 10: 0,
 ],
 'body_language_included' => false,
 'algorithm_quality' => $qualityGate,
 'accuracy_guaranteed' => false,
 ],
 ];
 }

 private function answerContent(InterviewAnswer $answer): string
 {
 $answerText = trim((string) ($answer->answer_text?? ''));
 if ($answerText!== '') {
 return $answerText;
 }

 return trim((string) ($answer->delivery_transcript?? ''));
 }

 public function readinessBand(int $score): string
 {
 return $score >= 80? 'Ready for Simulation': ($score >= 60? 'Nearly Ready': 'Developing');
 }
}
