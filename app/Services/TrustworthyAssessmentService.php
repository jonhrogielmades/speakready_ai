<?php

namespace App\Services;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use Illuminate\Support\Collection;

class TrustworthyAssessmentService
{
 public const SCORE_VERSION = 5;

 private const ACTION_VERB_PATTERN = '(?:lead|led|own|owned|build|built|create|created|resolve|resolved|solve|solved|fix|fixed|improve|improved|reduce|reduced|increase|increased|deliver|delivered|design|designed|implement|implemented|organize|organized|manage|managed|test|tested|analyze|analyzed|coordinate|coordinated|decide|decided|handle|handled|support|supported|communicate|communicated|verify|verified|check|checked|plan|planned|inspect|inspected|diagnose|diagnosed|review|reviewed|prioritize|prioritized|explain|explained|validate|validated|measure|measured|compare|compared|document|documented|escalate|escalated|write|wrote|prepare|prepared|train|trained|assist|assisted|propose|proposed|research|researched|configure|configured|deploy|deployed|investigate|investigated|monitor|monitored|report|reported|present|presented|negotiate|negotiated|mentor|mentored|facilitate|facilitated|maintain|maintained|migrate|migrated|automate|automated|optimize|optimized|launch|launched|process|processed|schedule|scheduled|delegate|delegated|select|selected|evaluate|evaluated|gather|gathered|contact|contacted|collaborate|collaborated|update|updated|identify|identified|recommend|recommended)';

 private const RESULT_SIGNAL_PATTERN = '(?:as a result|this led to|which led to|result(?:ed)?|outcome|impact|achiev(?:e|ed|ement)|improv(?:e|ed|ement)|reduc(?:e|ed|tion)|increas(?:e|ed)|deliver(?:ed)?|sav(?:e|ed)|faster|slower|resolv(?:e|ed)|complet(?:e|ed)|finish(?:ed)?|pass(?:ed)?|learn(?:ed)?|lesson|success(?:ful|fully)?|met the|exceeded)';

 public function deliveryStability(string $answerText, int $wpm, int $fillerWords, int $pauseCount, int $voiceDuration):?int
 {
 if ($voiceDuration <= 0 || TranscriptService::wordCount($answerText) === 0) {
 return null;
 }

 $words = max(1, TranscriptService::wordCount($answerText));
 $score = 100;
 $score -= min(35, (int) round(($fillerWords / $words) * 250));
 $score -= min(25, (int) round(($pauseCount / $words) * 160));
 if ($wpm < 80 || $wpm > 200) {
 $score -= 20;
 } elseif ($wpm < 100 || $wpm > 170) {
 $score -= 8;
 }
 if ($words < 20) {
 $score -= 15;
 }

 return max(0, min(100, $score));
 }

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
 $sentences = preg_split('/(?<=[.!?])\s+/', trim($answer), -1, PREG_SPLIT_NO_EMPTY)?: [];
 $evidence = collect($sentences)->filter(function (string $sentence) {
 return preg_match('/\b(I|we|my|our)\b/i', $sentence)
 && preg_match('/\b'.self::ACTION_VERB_PATTERN.'\b/i', $sentence);
 })->take(3)->values()->all();

 $starApplicable = QuestionIntentService::starApplicable($question);
 $questionIntent = QuestionIntentService::classify($question);
 $questionText = QuestionIntentService::text($question);
 $resultRequired = $question === null || QuestionIntentService::requiresResult($question);
 $personalActionRequired = $question === null || QuestionIntentService::requiresPersonalAction($question);
 $hasResult = preg_match('/\b(?:'.self::RESULT_SIGNAL_PATTERN.'|\d+(?:\.\d+)?%?|percent|hours?|days?|minutes?|seconds?)\b/i', $answer) === 1;
 $hasPersonalAction = preg_match('/\bI\s+(?:personally\s+)?(?:(?:would|will|can|could|plan to|try to)\s+)?'.self::ACTION_VERB_PATTERN.'\b/i', $answer) === 1;

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
 $answerSentence = $this->sentenceText(mb_substr($clean, 0, 700));
 $questionText = trim((string) ($evidence['question_text']?? ''));

 if ($evidence['star_applicable']?? false) {
 return $this->starParagraphRevision($answerSentence, $questionText);
 }

 $intent = (string) ($evidence['question_intent']?? 'direct_evidence');

 $closing = match ($intent) {
 'strength' => 'I would use that strength to support the team and handle the role responsibilities with care.',
 'strength_and_weakness' => 'I would keep the strength clear, name the development area honestly, and connect both points to how I work.',
 'weakness' => 'I am continuing to improve it by staying consistent with the progress I described.',
 'salary_expectation' => 'I am open to discussing a fair range based on the role, the responsibilities, and the experience I shared.',
 'motivation', 'role_fit' => 'That is why the role fits my interests, my experience, and the contribution I want to make.',
 'self_introduction' => 'This background gives me a clear starting point for the role and shows the experience I can bring.',
 'career_transition' => 'This explains my reason clearly and connects it to the next step I am aiming for.',
 'technical' => 'I would explain the reason for each step and check the result before moving on.',
 'situational' => 'I would stay focused on the main issue, take the step I described, and check whether it solved the problem.',
 default => 'I would keep the answer focused, direct, and connected to the role.',
 };

 return trim($answerSentence.' '.$closing);
 }

 private function starParagraphRevision(string $answerSentence, string $questionText): string
 {
 $context = $this->starContextSentence($questionText);
 $task = 'My task was to understand the situation, take responsibility for my part, and respond clearly.';
 $action = preg_match('/\b(?:I|we|my|our)\b/iu', $answerSentence) === 1
 ? $answerSentence
 : 'I would explain it this way: '.$answerSentence;
 $result = $this->starResultSentence($questionText);

 return trim($context.' '.$task.' '.$action.' '.$result);
 }

 private function starContextSentence(string $questionText): string
 {
 $question = mb_strtolower($questionText, 'UTF-8');

 return match (true) {
 preg_match('/\b(?:customer|client|complaint|concern|upset|angry|irate)\b/u', $question) === 1 => 'In a customer situation, I needed to understand the concern before responding.',
 preg_match('/\b(?:team|collaborat|coworker|colleague)\b/u', $question) === 1 => 'In a team situation, I needed to work with others and keep the goal clear.',
 preg_match('/\b(?:project|assignment|task)\b/u', $question) === 1 => 'During a project or task, I needed to stay organized and focus on the expected outcome.',
 preg_match('/\b(?:pressure|deadline|urgent|stress)\b/u', $question) === 1 => 'During a time-sensitive situation, I needed to stay calm and choose the next useful step.',
 preg_match('/\b(?:conflict|difficult|challenge|problem)\b/u', $question) === 1 => 'In a challenging situation, I needed to understand the problem and respond carefully.',
 default => 'In that situation, I needed to understand what was happening and respond in a clear way.',
 };
 }

 private function starResultSentence(string $questionText): string
 {
 $question = mb_strtolower($questionText, 'UTF-8');

 return match (true) {
 preg_match('/\b(?:customer|client|complaint|concern|upset|angry|irate)\b/u', $question) === 1 => 'This helped me give a clearer next step and handle the concern in an organized way.',
 preg_match('/\b(?:team|collaborat|coworker|colleague)\b/u', $question) === 1 => 'This helped the team move forward with clearer direction.',
 preg_match('/\b(?:project|assignment|task)\b/u', $question) === 1 => 'This helped me move the work forward in a more organized way.',
 preg_match('/\b(?:pressure|deadline|urgent|stress)\b/u', $question) === 1 => 'This helped me stay focused and continue working through the pressure.',
 preg_match('/\b(?:conflict|difficult|challenge|problem)\b/u', $question) === 1 => 'This helped me handle the challenge with a clearer process and a better lesson for next time.',
 default => 'This helped me turn the situation into a clearer lesson about how I work.',
 };
 }

 private function sentenceText(string $text): string
 {
 $text = trim(preg_replace('/\s+/', ' ', $text)?? $text);
 if ($text === '') {
 return '';
 }

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
 $confidence = min($confidence, (int) round($answerConfidences->avg()));
 }
 $deliveryScores = $answers->pluck('delivery_stability_score')->filter(fn ($value) => $value!== null);
 $starApplicable = $answers->contains(fn ($answer) => QuestionIntentService::starApplicable($answer->question));
 $languageScoring =! ((bool) data_get($session->accommodation_profile, 'separate_language_scoring', false));
 $overall = $this->overallScore([
 'clarity' => $metrics['clarity'],
 'relevance' => $metrics['relevance'],
 'grammar' => $metrics['grammar'],
 'professionalism' => $metrics['professionalism'],
 'star' => $starScore,
 ], $starApplicable, $languageScoring);

 return [
 'overall' => $overall,
 'readiness_band' => $this->readinessBand($overall),
 'scoring_confidence' => $confidence,
 'delivery_stability' => (int) round($deliveryScores->avg()?? 0),
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
 'delivery_stability_included' => false,
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
