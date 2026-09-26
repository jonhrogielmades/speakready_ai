<?php

namespace App\Support;

use App\Models\InterviewSession;
use Illuminate\Support\Collection;

class FeedbackReportPresenter
{
 public static function forSession(InterviewSession $session): array
 {
 $feedback = $session->feedback;
 $strengths = trim((string) ($feedback->strengths?? ''));
 $weaknesses = trim((string) ($feedback->weaknesses?? ''));
 $suggestions = trim((string) ($feedback->improvement_suggestions?? ''));
 $score = $session->score;
 $overall = is_numeric($score?->overall_readiness_score?? null)? self::score($score->overall_readiness_score): null;
 $focus = self::primaryFocus($session);
 $categoryBreakdown = self::categoryBreakdown($session);
 $strengthItems = self::bulletItems($strengths, 'No strengths were generated for this session.');
 $weaknessItems = self::bulletItems($weaknesses, 'No focus areas were generated for this session.');
 $suggestionItems = self::bulletItems($suggestions, 'Practice one answer again with a clearer structure.', 3, 130);

 return [
 'strengths' => $strengths,
 'weaknesses' => $weaknesses,
 'suggestions' => $suggestions,
 'category_breakdown' => $categoryBreakdown,
 'strength_items' => $strengthItems,
 'weakness_items' => $weaknessItems,
 'suggestion_items' => $suggestionItems,
 'overview' => [
 'summary' => self::overallSummary($session, $overall, $categoryBreakdown, $strengths, $weaknesses, $suggestions, $focus),
 'focus_label' => $focus['label'],
 'focus_score' => $focus['score'],
 'focus_advice' => $focus['advice'],
 ],
 'conciseness' => self::concisenessStats(self::answerTexts($session)),
 ];
 }

 public static function categoryBreakdown(InterviewSession $session): array
 {
 $score = $session->score;
 if (! $score) {
 return [];
 }

 $metrics = [
 [
 'name' => 'Fluency & Clarity',
 'score' => $score->clarity_score,
 'color' => '#3b82f6',
 ],
 [
 'name' => 'Answer Match',
 'score' => $score->relevance_score,
 'color' => '#10b981',
 ],
 [
 'name' => 'Grammar',
 'score' => $score->grammar_score,
 'color' => '#8b5cf6',
 ],
 [
 'name' => 'Professional Tone',
 'score' => $score->professionalism_score,
 'color' => '#f59e0b',
 ],
 ];

 if (self::hasRecordedConfidence($score->confidence_score?? null)) {
 $metrics[] = [
 'name' => 'Confidence',
 'score' => $score->confidence_score,
 'color' => '#0ea5e9',
 ];
 }

 $jobEvidenceScore = $score->job_evidence_match_score?? null;
 if (is_numeric($jobEvidenceScore) && ((int) $jobEvidenceScore > 0 || trim((string) ($session->job_description?? ''))!== '')) {
 $metrics[] = [
 'name' => 'Role Evidence',
 'score' => $jobEvidenceScore,
 'color' => '#14b8a6',
 ];
 }

 if (self::hasMeasuredDelivery($session) && is_numeric($score->delivery_stability_score?? null)) {
 $metrics[] = [
 'name' => 'Pacing',
 'score' => $score->delivery_stability_score,
 'color' => '#f59e0b',
 ];
 }

 return collect($metrics)
 ->filter(fn (array $metric): bool => is_numeric($metric['score']?? null))
 ->map(fn (array $metric): array => [
 'name' => $metric['name'],
 'score' => self::score($metric['score']),
 'color' => $metric['color'],
 ])
 ->values()
 ->all();
 }

 private static function bulletItems(string $text, string $fallback, int $limit = 4, int $characterLimit = 150): array
 {
 $clean = self::cleanText($text);
 if ($clean === '') {
 return [$fallback];
 }

 $parts = preg_split('/(?:\r?\n|;\s+|(?<=[.!?])\s+)/u', $clean, -1, PREG_SPLIT_NO_EMPTY)?: [$clean];
 $items = [];
 $seen = [];

 foreach ($parts as $part) {
 $item = self::limitText($part, $characterLimit);
 if ($item === '') {
 continue;
 }

 $key = mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $item)?? $item, 'UTF-8');
 if (isset($seen[$key])) {
 continue;
 }

 $seen[$key] = true;
 $items[] = $item;
 if (count($items) >= $limit) {
 break;
 }
 }

 return $items!== []? $items: [self::limitText($clean, $characterLimit)];
 }

 private static function overallSummary(
 InterviewSession $session,
 ?int $overall,
 array $categoryBreakdown,
 string $strengths,
 string $weaknesses,
 string $suggestions,
 array $focus
 ): string {
 $answers = self::answers($session);
 $answerCount = $answers->count();
 if ($answerCount <= 0) {
 return 'No saved answer text was available for this session yet. The review cannot identify reliable strengths or gaps without submitted responses. Complete one answer, then practice again with one direct point and one true example.';
 }

 $answeredCount = $answers
 ->filter(fn ($answer): bool => ! (bool) ($answer->is_skipped?? false) && self::answerContent($answer)!== '')
 ->count();
 $skippedCount = $answers->filter(fn ($answer): bool => (bool) ($answer->is_skipped?? false))->count();
 $answerLabel = $answerCount === 1? 'the 1 answer review': 'all '.$answerCount.' answer reviews';
 $answerScope = 'This overall review is based on '.$answerLabel.' in this session and does not assume details outside the saved '.($answerCount === 1? 'answer': 'answers');
 if ($skippedCount > 0 || $answeredCount !== $answerCount) {
 $parts = [];
 if ($answeredCount > 0) {
 $parts[] = $answeredCount.' '.($answeredCount === 1? 'answered response': 'answered responses');
 }
 if ($skippedCount > 0) {
 $parts[] = $skippedCount.' '.($skippedCount === 1? 'skipped answer': 'skipped answers');
 }
 $unansweredCount = max(0, $answerCount - $answeredCount - $skippedCount);
 if ($unansweredCount > 0) {
 $parts[] = $unansweredCount.' '.($unansweredCount === 1? 'saved response without usable text': 'saved responses without usable text');
 }
 if ($parts!== []) {
 $answerScope .= ', including '.self::humanList($parts);
 }
 }

 $sentences = [self::sentence($answerScope)];
 $summary = is_array($session->feedback?->coaching_summary?? null)? $session->feedback->coaching_summary: [];
 $contentSentence = self::contentOverviewSentence((array) data_get($summary, 'content_overview', []));
 if ($contentSentence!== '') {
 $sentences[] = $contentSentence;
 }

 $scoreSentence = self::scoreContextSentence($overall, $categoryBreakdown);
 if ($scoreSentence!== '') {
 $sentences[] = $scoreSentence;
 } elseif ($overall === null) {
 $sentences[] = 'The readiness score is still pending, so the safest next step is to use the answer notes instead of guessing performance.';
 }

 $strength = self::firstEvidenceItem($strengths, 190);
 if ($strength!== '') {
 $sentences[] = self::sentence('What worked best: '.$strength);
 }

 $prioritySentence = self::priorityContextSentence((array) data_get($summary, 'priority_actions', []));
 if ($prioritySentence!== '') {
 $sentences[] = $prioritySentence;
 } else {
 $weakness = self::firstEvidenceItem($weaknesses, 190);
 $suggestion = self::firstEvidenceItem($suggestions, 170);
 if ($weakness!== '' && $suggestion!== '') {
 $sentences[] = self::sentence('Main improvement: '.$weakness.'; next practice: '.$suggestion);
 } elseif ($weakness!== '') {
 $sentences[] = self::sentence('Main improvement: '.$weakness);
 } elseif ($suggestion!== '') {
 $sentences[] = self::sentence('Next practice: '.$suggestion);
 } else {
 $focusLabel = trim((string) ($focus['label']?? 'Answer Structure'))?: 'Answer Structure';
 $focusAdvice = trim((string) ($focus['advice']?? 'Use one idea, one example, and one result.'))?: 'Use one idea, one example, and one result.';
 $sentences[] = self::sentence('Next practice for '.$focusLabel.': '.$focusAdvice);
 }
 }

 return implode(' ', array_slice(array_values(array_filter($sentences)), 0, 5));
 }

 private static function contentOverviewSentence(array $overview): string
 {
 $items = [];
 foreach ([
 'directly_answered' => 'directly answered',
 'partially_answered' => 'answered partly',
 'low_relevance' => 'had low match',
 'insufficient_evidence' => 'needed more detail',
 'skipped' => 'skipped',
 'not_evaluated' => 'not checked',
 ] as $key => $label) {
 $count = max(0, (int) ($overview[$key]?? 0));
 if ($count > 0) {
 $items[] = $count.' '.$label;
 }
 }

 if ($items === []) {
 return '';
 }

 return self::sentence('Answer-match checks show '.self::humanList($items));
 }

 private static function scoreContextSentence(?int $overall, array $categoryBreakdown): string
 {
 $metrics = collect($categoryBreakdown)
 ->filter(fn ($metric): bool => is_array($metric) && is_numeric($metric['score']?? null) && trim((string) ($metric['name']?? ''))!== '')
 ->map(fn (array $metric): array => [
 'name' => trim((string) $metric['name']),
 'score' => self::score($metric['score']),
 ])
 ->sortBy('score')
 ->values();

 if ($metrics->isEmpty()) {
 return $overall === null? '': 'Overall readiness is '.$overall.'%.';
 }

 $lowest = $metrics->first();
 $highest = $metrics->last();
 if ($overall === null) {
 if ($metrics->count() === 1) {
 return self::sentence('The recorded '.$lowest['name'].' score is '.$lowest['score'].'%');
 }

 return self::sentence('The lowest recorded area is '.$lowest['name'].' at '.$lowest['score'].'%, while the highest is '.$highest['name'].' at '.$highest['score'].'%');
 }

 if ($metrics->count() === 1 || $lowest['name'] === $highest['name']) {
 return self::sentence('Overall readiness is '.$overall.'%, with '.$lowest['name'].' recorded at '.$lowest['score'].'%');
 }

 return self::sentence('Overall readiness is '.$overall.'%, with '.$lowest['name'].' as the lowest recorded area at '.$lowest['score'].'% and '.$highest['name'].' as the highest at '.$highest['score'].'%');
 }

 private static function priorityContextSentence(array $priorities): string
 {
 foreach ($priorities as $priority) {
 if (! is_array($priority)) {
 continue;
 }

 $area = self::limitText((string) ($priority['area']?? 'Top focus'), 80);
 $observation = rtrim(self::limitText(review_feedback_without_question_text((string) ($priority['observation']?? '')), 150), " \t\n\r\0\x0B.?!;");
 $action = rtrim(self::limitText(review_feedback_without_question_text((string) ($priority['action']?? '')), 170), " \t\n\r\0\x0B.?!;");
 $parts = [];
 $parts[] = 'Top focus: '.($area!== ''? $area: 'answer practice');
 if ($observation!== '') {
 $parts[] = $observation;
 }
 if ($action!== '') {
 $parts[] = 'next practice: '.$action;
 }

 if (count($parts) > 1 || $area!== '') {
 return self::sentence(implode('; ', $parts));
 }
 }

 return '';
 }

 private static function firstEvidenceItem(string $text, int $limit): string
 {
 $item = trim((string) (self::bulletItems($text, '', 1, $limit)[0]?? ''));

 return self::limitText(review_feedback_without_question_text($item), $limit);
 }

 private static function sentence(string $text): string
 {
 $clean = self::cleanText($text);
 if ($clean === '') {
 return '';
 }

 $clean = rtrim($clean, " \t\n\r\0\x0B");

 return preg_match('/[.!?][\'"]?$/u', $clean) === 1? $clean: $clean.'.';
 }

 private static function humanList(array $items): string
 {
 $items = array_values(array_filter(array_map(
 fn ($item): string => trim((string) $item),
 $items
 )));
 $count = count($items);
 if ($count === 0) {
 return '';
 }
 if ($count === 1) {
 return $items[0];
 }
 if ($count === 2) {
 return $items[0].' and '.$items[1];
 }

 $last = array_pop($items);

 return implode(', ', $items).', and '.$last;
 }

 private static function primaryFocus(InterviewSession $session): array
 {
 $score = $session->score;
 $metrics = [
 [
 'label' => 'Fluency & Clarity',
 'score' => $score?->clarity_score,
 'advice' => 'Use shorter sentences and put the main answer first.',
 ],
 [
 'label' => 'Answer Match',
 'score' => $score?->relevance_score,
 'advice' => 'Answer directly first, then add one useful example.',
 ],
 [
 'label' => 'Grammar',
 'score' => $score?->grammar_score,
 'advice' => 'Use simple sentence patterns and remove repeated wording.',
 ],
 [
 'label' => 'Professional Tone',
 'score' => $score?->professionalism_score,
 'advice' => 'Keep the wording direct, respectful, and role-focused.',
 ],
 ];

 $jobScore = $score?->job_evidence_match_score;
 if (is_numeric($jobScore) && ((int) $jobScore > 0 || trim((string) ($session->job_description?? ''))!== '')) {
 $metrics[] = [
 'label' => 'Role Evidence',
 'score' => $jobScore,
 'advice' => 'Connect one answer detail to the role or company need.',
 ];
 }

 if (self::hasMeasuredDelivery($session) && is_numeric($score?->delivery_stability_score?? null)) {
 $metrics[] = [
 'label' => 'Pacing',
 'score' => $score->delivery_stability_score,
 'advice' => 'Pause between ideas and reduce filler words.',
 ];
 }

 $metrics = array_values(array_filter($metrics, fn (array $metric): bool => is_numeric($metric['score']?? null)));
 if ($metrics === []) {
 return [
 'label' => 'Answer Structure',
 'score' => null,
 'advice' => 'Use one idea, one example, and one result.',
 ];
 }

 usort($metrics, fn (array $left, array $right): int => self::score($left['score']) <=> self::score($right['score']));
 $focus = $metrics[0];

 return [
 'label' => $focus['label'],
 'score' => self::score($focus['score']),
 'advice' => $focus['advice'],
 ];
 }

 private static function concisenessStats(Collection $answerTexts): array
 {
 $wordCounts = $answerTexts->map(fn (string $text): int => self::wordCount($text));
 $totalWords = (int) $wordCounts->sum();
 $answerCount = max(0, $answerTexts->count());
 $averageWords = $answerCount > 0? (int) round($totalWords / $answerCount): 0;
 $repeatedWords = self::repeatedWords($answerTexts);

 $band = match (true) {
 $answerCount === 0 => 'No answers',
 $averageWords > 120 => 'Wordy',
 $averageWords >= 70 => 'Moderate',
 default => 'Concise',
 };

 return [
 'answer_count' => $answerCount,
 'total_words' => $totalWords,
 'average_words' => $averageWords,
 'band' => $band,
 'repeated_words' => $repeatedWords,
 'trim_target' => match ($band) {
 'Wordy' => 'Cut repeated words and keep one point, one example, and one result.',
 'Moderate' => 'Tighten long sentences and remove repeated phrases.',
 'Concise' => 'Good length. Keep the wording direct and specific.',
 default => 'Add complete answers before checking repetition.',
 },
 ];
 }

 private static function repeatedWords(Collection $answerTexts): array
 {
 $stopWords = array_flip([
 'about', 'after', 'again', 'also', 'answer', 'because', 'before', 'being', 'could',
 'from', 'have', 'into', 'like', 'more', 'question', 'that', 'their', 'them', 'then',
 'there', 'these', 'they', 'this', 'those', 'very', 'were', 'what', 'when', 'where',
 'which', 'while', 'will', 'with', 'would', 'your', 'youre',
 ]);
 $counts = [];

 foreach ($answerTexts as $text) {
 preg_match_all('/[\p{L}\p{N}\']+/u', mb_strtolower($text, 'UTF-8'), $matches);
 foreach ($matches[0]?? [] as $word) {
 $word = trim($word, "'");
 if (mb_strlen($word, 'UTF-8') < 4 || isset($stopWords[$word])) {
 continue;
 }

 $counts[$word] = ($counts[$word]?? 0) + 1;
 }
 }

 arsort($counts);
 $items = [];
 foreach ($counts as $word => $count) {
 if ($count < 3) {
 break;
 }

 $items[] = ['word' => $word, 'count' => $count];
 if (count($items) >= 5) {
 break;
 }
 }

 return $items;
 }

 private static function answerTexts(InterviewSession $session): Collection
 {
 return self::answers($session)
 ->map(fn ($answer): string => self::answerContent($answer))
 ->filter(fn (string $answerText): bool => $answerText!== '')
 ->values();
 }

 private static function answerContent($answer): string
 {
 if ((bool) ($answer->is_skipped?? false)) {
 return '';
 }

 $answerText = trim((string) ($answer->answer_text?? ''));
 if ($answerText!== '') {
 return $answerText;
 }

 return trim((string) ($answer->delivery_transcript?? ''));
 }

 private static function answers(InterviewSession $session): Collection
 {
 $answers = $session->relationLoaded('answers')? $session->answers: $session->answers()->whereNull('retry_of_answer_id')->get();

 return $answers instanceof Collection? $answers: collect($answers);
 }

 private static function hasRecordedConfidence(mixed $score): bool
 {
 return is_numeric($score) && self::score($score) > 0;
 }

 private static function hasMeasuredDelivery(InterviewSession $session): bool
 {
 if ((int) data_get($session->feedback?->coaching_summary?? [], 'coverage.delivery_measured', 0) > 0) {
 return true;
 }

 return self::answers($session)->contains(function ($answer): bool {
 if (data_get($answer->coaching_feedback?? [], 'delivery.status') === 'measured') {
 return true;
 }

 $responseMode = strtolower(trim((string) ($answer->response_mode?? '')));

 return in_array($responseMode, ['voice', 'hybrid', 'voice_and_text'], true)
 && (int) ($answer->voice_duration?? 0) > 0
 && $answer->delivery_stability_score!== null;
 });
 }

 private static function wordCount(string $text): int
 {
 preg_match_all('/[\p{L}\p{N}\']+/u', $text, $matches);

 return count($matches[0]?? []);
 }

 private static function cleanText(string $text): string
 {
 return trim(preg_replace('/\s+/u', ' ', $text)?? '');
 }

 private static function limitText(string $text, int $limit): string
 {
 $clean = self::cleanText($text);
 if ($clean === '' || mb_strlen($clean, 'UTF-8') <= $limit) {
 return $clean;
 }

 return rtrim(mb_substr($clean, 0, max(1, $limit - 3), 'UTF-8'), " \t\n\r\0\x0B.,;:").'...';
 }

 private static function score(mixed $score): int
 {
 return max(0, min(100, (int) round((float) $score)));
 }
}
