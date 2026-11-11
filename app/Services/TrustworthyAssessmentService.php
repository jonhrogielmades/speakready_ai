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

 // A possible answer must sound like something the candidate can say aloud.
 // The question and evidence tell the caller what to coach separately; they
 // cannot supply facts for this draft. Keep every saved claim and correction
 // in order, and split only clear action lists that share an explicit subject.
 $sentences = $this->answerSentences($clean);
 $revision = [];
 foreach ($sentences as $sentence) {
 $parts = $this->spokenActionParts($sentence);
 if (count($revision) + count($parts) > 6) {
 $parts = [$sentence];
 }
 foreach ($parts as $part) {
 $revision[] = $this->sentenceText($part);
 }
 }

 return implode(' ', $revision);
 }

 /** @return array<int, string> */
 private function spokenActionParts(string $sentence): array
 {
 $body = trim($sentence);
 $body = preg_replace('/[.!?]$/u', '', $body)?? $body;
 $fragments = preg_split('/,\s+/u', $body) ?: [];
 if (count($fragments) < 3 || count($fragments) > 5) {
 return [$sentence];
 }
 if (preg_match('/^(?:and|then)\s+/iu', $fragments[array_key_last($fragments)]) !== 1) {
 return [$sentence];
 }
 if (preg_match('/^(I|We)\s+((?:would|will|could|can)\s+)?(.+)$/iu', $fragments[0], $subject) !== 1) {
 return [$sentence];
 }

 $actionVerbs = 'listen(?:ed)?|check(?:ed)?|explain(?:ed)?|give|gave|inspect(?:ed)?|compare(?:d)?|verify|verified|review(?:ed)?|share|shared|document(?:ed)?|design(?:ed)?|test(?:ed)?|coordinate(?:d)?|contact(?:ed)?|ask(?:ed)?|update(?:d)?|prepare(?:d)?|send|sent|write|wrote|analy[sz](?:e|ed)|prioriti[sz](?:e|ed)|resolve(?:d)?|fix(?:ed)?|manage(?:d)?|support(?:ed)?';
 if (preg_match('/^(?:'.$actionVerbs.')\b/iu', trim($subject[3])) !== 1) {
 return [$sentence];
 }

 $parts = [$fragments[0]];
 foreach (array_slice($fragments, 1) as $fragment) {
 $action = preg_replace('/^(?:and|then)\s+/iu', '', trim($fragment))?? trim($fragment);
 if (preg_match('/^(?:'.$actionVerbs.')\b/iu', $action) !== 1) {
 return [$sentence];
 }
 $parts[] = $subject[1].' '.$subject[2].$action;
 }

 return $parts;
 }

 /**
  * @return array<int, string>
  */
 private function answerSentences(string $text): array
 {
 // Decimal numbers and URLs contain periods, not sentence boundaries.
 return preg_split('/(?<=[.!?])\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
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
