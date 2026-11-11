<?php

namespace App\Support;

use App\Models\InterviewSession;
use App\Services\AIService;
use App\Services\DetailedFeedbackReviewService;
use App\Services\EvidenceBasedCoachingService;
use Illuminate\Support\Collection;

class FeedbackReportPresenter
{
 public static function forSession(InterviewSession $session): array
 {
 $hasSavedFingerprint = trim((string) data_get($session->feedback?->coaching_summary?? [], 'review_context.detailed_review_validation.input_fingerprint', '')) !== '';
 $isStale = $hasSavedFingerprint && ! app(DetailedFeedbackReviewService::class)->reviewInputsMatchSavedValidation($session);
 if ($isStale) {
 $session = self::currentInputReview($session);
 }
 $feedback = $session->feedback;
 $strengths = self::reviewText($feedback->strengths?? '');
 $weaknesses = self::reviewText($feedback->weaknesses?? '');
 $suggestions = self::reviewText($feedback->improvement_suggestions?? '');
 $score = $session->score;
 $overall = is_numeric($score?->overall_readiness_score?? null)? self::score($score->overall_readiness_score): null;
 $focus = self::primaryFocus($session);
 $categoryBreakdown = self::categoryBreakdown($session);
 $answers = self::answers($session);
 $providerSessionFeedbackTrusted = self::hasTrustedProviderSessionFeedback($feedback)
 && ! $isStale
 && self::providerSessionFeedbackIsUsable($feedback, $answers);
 $providerStrengthItems = self::nonEmptyItems(self::bulletItems($strengths, '', 4, null));
 $answerStrengthItems = self::generalizedAnswerReviewItems(self::answerReviewStrengthItems($answers), $answers->count());
 $strengthItems = $providerSessionFeedbackTrusted && $providerStrengthItems!== []
 ? $providerStrengthItems
 : $answerStrengthItems;
 if ($strengthItems === []) {
 $strengthItems = self::fallbackStrengthItems($answers);
 }
 $providerWeaknessItems = self::nonEmptyItems(self::mergedItems(
 self::bulletItems($weaknesses, '', 4, null),
 self::bulletItems($suggestions, '', 2, null)
 ));
 $answerWeaknessItems = self::generalizedAnswerReviewItems(self::answerReviewWeaknessItems($answers), $answers->count());
 $weaknessItems = $providerSessionFeedbackTrusted && $providerWeaknessItems!== []
 ? $providerWeaknessItems
 : $answerWeaknessItems;
 if ($weaknessItems === []) {
 $weaknessItems = self::fallbackNextStepItems($answers);
 }
 $safeSuggestions = $providerSessionFeedbackTrusted? $suggestions: implode(' ', array_map(self::sentence(...), $weaknessItems));
 $suggestionItems = self::bulletItems($safeSuggestions, '', 3, 130);

 return [
 'is_stale' => $isStale,
 'strengths' => implode('. ', $strengthItems),
 'weaknesses' => implode('. ', $weaknessItems),
 'suggestions' => $safeSuggestions,
 'category_breakdown' => $categoryBreakdown,
 'strength_items' => $strengthItems,
 'weakness_items' => $weaknessItems,
 'suggestion_items' => $suggestionItems,
 'overview' => [
 'summary' => self::overallSummary($session, $overall, $categoryBreakdown, $strengthItems, $weaknessItems, $safeSuggestions, $focus, $providerSessionFeedbackTrusted),
 'focus_label' => $focus['label'],
 'focus_score' => $focus['score'],
 'focus_advice' => $focus['advice'],
 ],
 'conciseness' => self::concisenessStats(self::answerTexts($session)),
 ];
 }

 private static function currentInputReview(InterviewSession $session): InterviewSession
 {
 // Prepare only a display snapshot. Saved evaluations and scores remain intact
 // until a background review successfully validates the current inputs.
 $current = clone $session;
 $coachingService = new EvidenceBasedCoachingService();
 $answers = self::answers($session)->map(function ($answer) use ($coachingService) {
 $copy = clone $answer;
 $input = self::providerAnswerData(collect([$answer]))[0];
 $local = AIService::localReviewQuestionFeedback($input);
 $coaching = $coachingService->forAnswer(self::answerContent($answer), $answer->question, $local);
 $copy->setRawAttributes(array_merge($answer->getAttributes(), [
 'coaching_feedback' => json_encode($coaching, JSON_THROW_ON_ERROR),
 'evidence_map' => null,
 'score' => null,
 ]), true);

 return $copy;
 });
 $current->setRelation('answers', $answers);
 $current->setRelation('score', null);
 // Session priorities and prose may also describe the previous answer snapshot.
 $current->setRelation('feedback', null);

 return $current;
 }

 public static function categoryBreakdown(InterviewSession $session): array
 {
 $score = $session->score;
 if (! $score) {
 return [];
 }

 $recordedScores = $score->getAttributes();
 $metrics = [
 [
 'name' => 'Fluency & Clarity',
 'score' => $recordedScores['clarity_score']?? null,
 'color' => '#3b82f6',
 ],
 [
 'name' => 'Answer Match',
 'score' => $recordedScores['relevance_score']?? null,
 'color' => '#10b981',
 ],
 [
 'name' => 'Grammar',
 'score' => $recordedScores['grammar_score']?? null,
 'color' => '#8b5cf6',
 ],
 [
 'name' => 'Professional Tone',
 'score' => $recordedScores['professionalism_score']?? null,
 'color' => '#f59e0b',
 ],
 ];

 if (self::hasRecordedConfidence($recordedScores['confidence_score']?? null)) {
 $metrics[] = [
 'name' => 'Confidence',
 'score' => $recordedScores['confidence_score'],
 'color' => '#0ea5e9',
 ];
 }

 return collect($metrics)
 ->filter(fn (array $metric): bool => is_numeric($metric['score']))
 ->map(fn (array $metric): array => [
 'name' => $metric['name'],
 'score' => self::score($metric['score']),
 'color' => $metric['color'],
 ])
 ->values()
 ->all();
 }

 private static function bulletItems(string $text, string $fallback, int $limit = 4, ?int $characterLimit = 150): array
 {
 $clean = self::cleanText($text);
 if ($clean === '') {
 return [$fallback];
 }

 $parts = preg_split('/(?:\r?\n|;\s+|(?<=[.!?])\s+)/u', $clean, -1, PREG_SPLIT_NO_EMPTY)?: [$clean];
 $items = [];
 $seen = [];

 foreach ($parts as $part) {
 $item = $characterLimit === null? self::cleanText($part): self::limitText($part, $characterLimit);
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

 return $items!== []? $items: [$characterLimit === null? $clean: self::limitText($clean, $characterLimit)];
 }

 private static function nonEmptyItems(array $items): array
 {
 return array_values(array_filter(array_map(
 fn ($item): string => self::cleanText((string) $item),
 $items
 ), fn (string $item): bool => $item!== ''));
 }

 private static function hasTrustedProviderSessionFeedback(mixed $feedback): bool
 {
 if (! is_object($feedback)) {
 return false;
 }

 $summary = is_array($feedback->coaching_summary?? null)? $feedback->coaching_summary: [];
 $validationStatus = data_get($summary, 'review_context.detailed_review_validation.status');
 if ($validationStatus !== null && $validationStatus !== 'verified') {
 return false;
 }
 $source = trim((string) data_get($summary, 'overall_summary_source', ''));
 $repairedFields = (array) data_get($summary, 'review_context.session_feedback_repaired_fields', []);
 $localFallback = (bool) data_get($summary, 'review_context.local_fallback', false);
 $sourceTrusted = $source === 'ai_provider_validated'
 || ($localFallback && in_array($source, ['local_evidence', 'local_trained_model', 'local_evidence_validated'], true))
 || ($source === 'local_evidence_validated'
 && in_array('overall_summary', $repairedFields, true)
 && data_get($summary, 'review_context.detailed_review_validation.status') === 'verified');
 if (! $sourceTrusted) {
 return false;
 }

 $providerKey = strtolower(trim((string) data_get($summary, 'review_context.provider_key', '')));
 if ($localFallback) {
 return in_array($providerKey, ['local', 'localmodel'], true)
 && in_array(trim((string) data_get($summary, 'review_context.provider_source', '')), ['', 'local_evidence', 'local_trained_model'], true);
 }
 if (in_array($providerKey, ['local', 'localmodel'], true)) {
 return false;
 }

 $providerSource = trim((string) data_get($summary, 'review_context.provider_source', ''));

 return $providerSource === '' || $providerSource === 'ai_provider';
 }

 private static function providerSessionFeedbackIsUsable(mixed $feedback, Collection $answers): bool
 {
 if (! is_object($feedback)) {
 return false;
 }

 $summary = is_array($feedback->coaching_summary?? null)? $feedback->coaching_summary: [];
 $expectedAnswers = data_get($summary, 'review_context.detailed_review_validation.answers_expected');
 if ($expectedAnswers !== null && (! is_numeric($expectedAnswers) || (int) $expectedAnswers !== $answers->count())) {
 return false;
 }

 return AIService::visibleSessionFeedbackIsProviderUsable([
 'overall_summary' => data_get($summary, 'overall_summary', ''),
 'strengths' => $feedback->strengths?? '',
 'weaknesses' => $feedback->weaknesses?? '',
 'improvement_suggestions' => $feedback->improvement_suggestions?? '',
 ], self::providerAnswerData($answers));
 }

 private static function mergedItems(array ...$groups): array
 {
 $items = [];
 $seen = [];
 foreach ($groups as $group) {
 foreach ($group as $item) {
 $text = self::cleanText((string) $item);
 if ($text === '') {
 continue;
 }

 $key = mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text)?? $text, 'UTF-8');
 if (isset($seen[$key])) {
 continue;
 }

 $seen[$key] = true;
 $items[] = $text;
 if (count($items) >= 4) {
 return $items;
 }
 }
 }

 return $items;
 }

 private static function answerReviewStrengthItems(Collection $answers): array
 {
 $items = [];
 foreach ($answers->values() as $index => $answer) {
 if ((bool) ($answer->is_skipped?? false) || self::answerContent($answer) === '') {
 continue;
 }

 $questionSource = $answer->question?? $answer;
 $coaching = is_array($answer->coaching_feedback?? null)? $answer->coaching_feedback: [];
 $alignment = is_array(data_get($coaching, 'content_alignment'))? data_get($coaching, 'content_alignment'): [];
 $evidenceMap = is_array($answer->evidence_map?? null)? $answer->evidence_map: [];
 $label = 'Answer '.($index + 1);
 $added = false;

 foreach ([data_get($alignment, 'what_worked'), data_get($alignment, 'keep')] as $candidate) {
 $text = self::groundedAnswerReviewText($candidate, $answer);
 if ($text !== '') {
 $items[] = $label.': '.$text;
 $added = true;
 break;
 }
 }

 if (! $added) {
 $excerpt = self::reviewText(data_get($alignment, 'evidence_quotes.0', data_get($evidenceMap, 'supporting_excerpts.0', '')), $questionSource);
 if ($excerpt !== '' && str_contains(self::cleanText(self::answerContent($answer)), $excerpt)) {
 $items[] = $label.': includes usable answer evidence, "'.$excerpt.'".';
 $added = true;
 }
 }

 if (count($items) >= 4) {
 break;
 }
 }

 return self::mergedItems($items);
 }

 private static function fallbackStrengthItems(Collection $answers): array
 {
 foreach ($answers as $answer) {
 $question = self::questionForFallback($answer);
 $text = self::answerContent($answer);
 if ($text === '') {
 continue;
 }

 $excerpt = self::limitText($text, 130);
 $input = self::providerAnswerData(collect([$answer]))[0];
 $eligibility = AIService::feedbackAnswerEligibility($input);
 if ((bool) ($eligibility['is_too_short'] ?? false)) {
 return ['For “'.$question.'”, the saved response “'.$excerpt.'” is too limited to identify a reliable strength.'];
 }

 return ['For “'.$question.'”, the saved response includes “'.$excerpt.'”. This is the detail available to review; no further strength was verified.'];
 }

 if ($answers->isNotEmpty()) {
 return ['For “'.self::questionForFallback($answers->first()).'”, no answer was submitted, so a strength cannot be identified.'];
 }

 return ['No answer was saved, so a strength cannot be identified.'];
 }

 private static function fallbackNextStepItems(Collection $answers): array
 {
 foreach ($answers as $answer) {
 $question = self::questionForFallback($answer);
 $text = self::answerContent($answer);
 if ($text === '') {
 continue;
 }

 $excerpt = self::limitText($text, 110);
 $input = self::providerAnswerData(collect([$answer]))[0];
 $eligibility = AIService::feedbackAnswerEligibility($input);
 if ((bool) ($eligibility['is_too_short'] ?? false)) {
 return ['For “'.$question.'”, expand “'.$excerpt.'” into a direct answer with a true supporting detail.'];
 }

 $local = AIService::localReviewQuestionFeedback($input);
 $followUp = self::cleanText((string) ($local['follow_up_question'] ?? ''));
 if ($followUp !== '') {
 $context = str_contains(mb_strtolower($followUp), mb_strtolower($question))
 ? ''
 : 'For “'.$question.'”, ';
 return [$context.($context === ''? 'After': 'after').' “'.$excerpt.'”, consider: '.$followUp];
 }

 return ['For “'.$question.'”, check whether “'.$excerpt.'” covers every requested point before adding any further detail.'];
 }

 if ($answers->isNotEmpty()) {
 return ['For “'.self::questionForFallback($answers->first()).'”, submit a direct answer before reviewing a possible improvement.'];
 }

 return ['Submit an answer before reviewing a possible improvement.'];
 }

 private static function questionForFallback(mixed $answer): string
 {
 return self::limitText(trim((string) ($answer->question->question_text ?? '')) ?: 'this question', 150);
 }

 private static function answerReviewWeaknessItems(Collection $answers): array
 {
 $items = [];
 foreach ($answers->values() as $index => $answer) {
 $questionSource = $answer->question?? $answer;
 $coaching = is_array($answer->coaching_feedback?? null)? $answer->coaching_feedback: [];
 $alignment = is_array(data_get($coaching, 'content_alignment'))? data_get($coaching, 'content_alignment'): [];
 $evidenceMap = is_array($answer->evidence_map?? null)? $answer->evidence_map: [];
 $label = 'Answer '.($index + 1);

 if ((bool) ($answer->is_skipped?? false)) {
 $items[] = $label.': no answer was submitted for “'.self::questionForFallback($answer).'”, so there is no response to check.';
 continue;
 }

 $candidates = array_merge(
 [data_get($alignment, 'improvement_focus')],
 (array) data_get($alignment, 'missing_points', data_get($evidenceMap, 'missing_evidence', [])),
 [data_get($alignment, 'action')],
 (array) data_get($alignment, 'next_attempt_steps', [])
 );
 foreach ($candidates as $candidate) {
 $text = self::groundedAnswerReviewText($candidate, $answer);
 if ($text !== '') {
 $items[] = $label.': '.$text;
 break;
 }
 }

 if (count($items) >= 4) {
 break;
 }
 }

 return self::mergedItems($items);
 }

 private static function groundedAnswerReviewText(mixed $candidate, mixed $answer): string
 {
 $text = self::reviewText($candidate, $answer->question?? $answer);
 if ($text === '') {
 return '';
 }
 $input = self::providerAnswerData(collect([$answer]))[0];

 // A replaced note failed the shared grounding checks. Do not relabel the
 // replacement's general feedback as a saved strength or missing point.
 return AIService::groundedReviewFeedbackText($text, $input) === $text? $text: '';
 }

 private static function reviewText(mixed $text, mixed $questionSource = null, ?int $limit = null): string
 {
 if (! is_scalar($text)) {
 return '';
 }

 $clean = self::cleanText((string) $text);
 if ($clean === '') {
 return '';
 }

 if (function_exists('review_feedback_without_question_text')) {
 $clean = review_feedback_without_question_text($clean, $questionSource);
 }

 return $limit === null? $clean: self::limitText($clean, $limit);
 }

 private static function generalizedAnswerReviewItems(array $items, int $answerCount): array
 {
 $patterns = [];
 foreach ($items as $item) {
 $pattern = self::generalizeAnswerReviewItem((string) $item, $answerCount);
 if ($pattern === '' || in_array($pattern, $patterns, true)) {
 continue;
 }

 $patterns[] = $pattern;
 if (count($patterns) >= 4) {
 break;
 }
 }

 return $patterns;
 }

 private static function answerReviewPattern(array $items, int $answerCount): string
 {
 $patterns = [];
 foreach ($items as $item) {
 $pattern = self::generalizeAnswerReviewItem((string) $item, $answerCount);
 if ($pattern === '' || in_array($pattern, $patterns, true)) {
 continue;
 }

 $patterns[] = $pattern;
 if (count($patterns) >= 2) {
 break;
 }
 }

 return self::humanList($patterns);
 }

 private static function generalizeAnswerReviewItem(string $item, int $answerCount = 0): string
 {
 $text = self::cleanText($item);
 $text = preg_replace('/^Answer\s+\d+\s*:\s*/iu', '', $text)?? $text;
 $text = trim($text, " \t\n\r\0\x0B.?!;");
 if ($text === '') {
 return '';
 }

 $verbs = 'names|explains|includes|shows|gives|uses|mentions|states|adds|identifies';
 $scope = $answerCount === 1? 'the saved answer': 'one saved answer';

 $text = preg_replace_callback(
 '/^(?:the\s+)?(?:saved\s+)?answer\s+(' . $verbs . ')\b/iu',
 static fn (array $matches): string => $scope.' '.$matches[1],
 $text
 )?? $text;
 $text = preg_replace('/^(?:the\s+)?(?:saved\s+)?answer\s+needs?\b/iu', $scope.' needs', $text)?? $text;
 $text = preg_replace('/^this\s+answer\s+/iu', $scope.' ', $text)?? $text;
 $text = preg_replace_callback('/^(add|use|include)\s+/iu',
 static fn (array $matches): string => 'A next attempt could '.mb_strtolower($matches[1]).' ',
 $text)?? $text;

 return self::sentenceCase($text);
 }

 private static function sentenceCase(string $text): string
 {
 $clean = self::cleanText($text);
 if ($clean === '') {
 return '';
 }

 if (preg_match('/^\p{Ll}/u', $clean) === 1) {
 return mb_strtoupper(mb_substr($clean, 0, 1, 'UTF-8'), 'UTF-8').mb_substr($clean, 1, null, 'UTF-8');
 }

 return $clean;
 }

 private static function overallSummary(
 InterviewSession $session,
 ?int $overall,
 array $categoryBreakdown,
 array $strengthItems,
 array $weaknessItems,
 string $suggestions,
 array $focus,
 bool $providerSessionFeedbackTrusted = false
 ): string {
 $answers = self::answers($session);
 $answerCount = $answers->count();
 if ($answerCount <= 0) {
 return 'No saved answer text was available for this session yet. The review cannot identify reliable strengths or gaps without submitted responses. Complete one answer, then practice again with one direct point and one true example.';
 }

 $summary = is_array($session->feedback?->coaching_summary?? null)? $session->feedback->coaching_summary: [];
 $providerSummary = self::reviewText(data_get($summary, 'overall_summary'), null, 700);
 if ($providerSummary!== '' && $providerSessionFeedbackTrusted) {
 return $providerSummary;
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
 $contentSentence = self::contentOverviewSentence(self::contentOverview($answers));
 if ($contentSentence!== '') {
 $sentences[] = $contentSentence;
 }

 $scoreSentence = self::scoreContextSentence($overall, $categoryBreakdown);
 if ($scoreSentence!== '') {
 $sentences[] = $scoreSentence;
 } elseif ($overall === null) {
 $sentences[] = 'The readiness score is still pending, so the safest next step is to use the answer notes instead of guessing performance.';
 }

 $strengthPattern = self::answerReviewPattern($strengthItems, $answerCount);
 if ($strengthPattern!== '') {
 $sentences[] = self::sentence('Strength noted in '.($answerCount === 1? 'the saved answer': 'an answer review').': '.$strengthPattern);
 }

 $weaknessPattern = self::answerReviewPattern($weaknessItems, $answerCount);
 $suggestion = self::firstEvidenceItem($suggestions);
 if ($weaknessPattern!== '') {
 $sentences[] = self::sentence('Next focus from '.($answerCount === 1? 'the saved answer': 'an answer review').': '.$weaknessPattern);
 }

 $prioritySentence = $providerSessionFeedbackTrusted
 ? self::priorityContextSentence((array) data_get($summary, 'priority_actions', []))
 : '';
 if ($prioritySentence!== '') {
 $sentences[] = $prioritySentence;
 } elseif ($suggestion!== '') {
 $sentences[] = self::sentence('Practice focus: '.$suggestion);
 } else {
 $focusLabel = trim((string) ($focus['label']?? 'Answer Structure'))?: 'Answer Structure';
 $focusAdvice = trim((string) ($focus['advice']?? 'Use one idea, one example, and one result.'))?: 'Use one idea, one example, and one result.';
 $sentences[] = self::sentence('Practice focus for '.$focusLabel.': '.$focusAdvice);
 }

 return implode(' ', array_slice(array_values(array_filter($sentences)), 0, 6));
 }

 private static function contentOverview(Collection $answers): array
 {
 $overview = [];
 foreach ($answers as $answer) {
 $status = trim((string) data_get($answer->coaching_feedback?? [], 'content_alignment.status', ''));
 if ((bool) ($answer->is_skipped?? false)) {
 $status = 'skipped';
 } elseif (self::answerContent($answer) === '') {
 $status = 'not_evaluated';
 }
 $status = match ($status) {
 'directly_answered', 'directly_addressed' => 'directly_answered',
 'partially_answered', 'partially_addressed' => 'partially_answered',
 'low_relevance', 'not_addressed' => 'low_relevance',
 'insufficient_evidence' => 'insufficient_evidence',
 'skipped' => 'skipped',
 default => 'not_evaluated',
 };
 $overview[$status] = ($overview[$status]?? 0) + 1;
 }

 return $overview;
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

 $area = self::cleanText((string) ($priority['area']?? 'Top focus'));
 $observation = rtrim(self::cleanText(review_feedback_without_question_text((string) ($priority['observation']?? ''))), " \t\n\r\0\x0B.?!;");
 $action = rtrim(self::cleanText(review_feedback_without_question_text((string) ($priority['action']?? ''))), " \t\n\r\0\x0B.?!;");
 $parts = [];
 $parts[] = 'Top focus: '.($area!== ''? $area: 'answer practice');
 if ($observation!== '') {
 $parts[] = $observation;
 }
 if ($action!== '') {
 $parts[] = 'practice focus: '.$action;
 }

 if (count($parts) > 1 || $area!== '') {
 return self::sentence(implode('; ', $parts));
 }
 }

 return '';
 }

 private static function firstEvidenceItem(string $text): string
 {
 $item = trim((string) (self::bulletItems($text, '', 1, null)[0]?? ''));

 return self::cleanText(review_feedback_without_question_text($item));
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

 private static function providerAnswerData(Collection $answers): array
 {
 return $answers->values()->map(fn ($answer): array => [
 'id' => $answer->id?? null,
 'question' => $answer->question->question_text?? '',
 'question_text' => $answer->question->question_text?? '',
 'question_type' => $answer->question->type?? null,
 'answer' => self::answerContent($answer) ?: ((bool) ($answer->is_skipped?? false)? '(Skipped or no answer)': ''),
 'is_skipped' => (bool) ($answer->is_skipped?? false),
 'expected_guide' => $answer->question->expected_guide?? null,
 'mapped_skills' => $answer->question->mapped_skills?? [],
 'response_mode' => $answer->response_mode?? 'text',
 'delivery_transcript' => $answer->delivery_transcript?? '',
 ])->all();
 }

 private static function answers(InterviewSession $session): Collection
 {
 $answers = $session->relationLoaded('answers')? $session->answers: $session->answers()->whereNull('retry_of_answer_id')->with('question')->orderBy('id')->get();

 return collect($answers)
 ->filter(fn ($answer): bool => ($answer->retry_of_answer_id?? null) === null)
 ->values();
 }

 private static function hasRecordedConfidence(mixed $score): bool
 {
 return is_numeric($score) && self::score($score) > 0;
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
