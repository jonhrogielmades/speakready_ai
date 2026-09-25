<?php

namespace App\Support;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class FeedbackEvidencePresenter
{
    public static function forSession(?InterviewSession $session): ?object
    {
        if (! $session) {
            return null;
        }

        $answers = self::answers($session);
        $answerCards = $answers
            ->values()
            ->map(fn (InterviewAnswer $answer, int $index): object => self::answerCard($session, $answer, $index))
            ->values();

        return (object) [
            'reliability' => self::sessionReliability($session, $answerCards),
            'proof_stats' => self::proofStats($session, $answerCards),
            'next_action' => self::nextAction($session, $answerCards),
            'recurring_focus' => self::recurringFocus($session, $answerCards),
            'answers' => $answerCards,
        ];
    }

    private static function answerCard(InterviewSession $session, InterviewAnswer $answer, int $index): object
    {
        $coaching = is_array($answer->coaching_feedback ?? null) ? $answer->coaching_feedback : [];
        $alignment = is_array(data_get($coaching, 'content_alignment')) ? data_get($coaching, 'content_alignment') : [];
        $evidenceMap = is_array($answer->evidence_map ?? null) ? $answer->evidence_map : [];
        $question = $answer->question ?? $answer;
        $answerText = self::answerContent($answer);
        $questionText = trim((string) ($answer->question->question_text ?? data_get($alignment, 'question', '')));
        $status = self::alignmentStatus($answer, $alignment);
        $statusLabel = self::statusLabel($status, data_get($alignment, 'status_label'));
        $confidence = self::scoreValue(data_get($alignment, 'scoring_confidence', $answer->scoring_confidence));
        $qualityPercent = self::scoreValue(data_get($coaching, 'feedback_quality.completeness_percent'));
        $evidenceQuotes = self::feedbackTextList(data_get($alignment, 'evidence_quotes', data_get($evidenceMap, 'supporting_excerpts', [])), $question, 3, 240);
        if ($evidenceQuotes === [] && $answerText !== '' && ! (bool) ($answer->is_skipped ?? false)) {
            $evidenceQuotes[] = self::limitText($answerText, 220);
        }

        $missingPoints = self::feedbackTextList(data_get($alignment, 'missing_points', data_get($evidenceMap, 'missing_evidence', [])), $question, 3, 180);
        $nextSteps = self::feedbackTextList(data_get($alignment, 'next_attempt_steps', []), $question, 4, 190);
        $nextPractice = self::cleanFeedback(data_get($alignment, 'action', $answer->recommendation_text ?? ''), $question);
        if ($nextPractice === '' && $nextSteps !== []) {
            $nextPractice = $nextSteps[0];
        }

        $improvement = self::cleanFeedback(data_get($alignment, 'improvement_focus', ''), $question);
        if ($improvement === '' && $missingPoints !== []) {
            $improvement = $missingPoints[0];
        }
        if ($improvement === '') {
            $improvement = self::cleanFeedback($answer->recommendation_text ?? '', $question);
        }
        if ($improvement === '') {
            $improvement = 'Add a direct answer, one specific detail, and a true result or lesson.';
        }

        $whatWorked = self::cleanFeedback(data_get($alignment, 'what_worked', ''), $question);
        $impact = self::cleanFeedback(data_get($alignment, 'impact', ''), $question);
        $successCheck = self::cleanFeedback(data_get($alignment, 'success_check', ''), $question);
        $feedback = self::cleanFeedback($answer->ai_feedback ?: data_get($alignment, 'observation', ''), $question);
        $betterAnswer = self::betterAnswer((string) ($answer->better_sample_answer ?? ''), $answer, $question);
        $hasVoiceRecording = trim((string) ($answer->voice_recording_path ?? '')) !== '';
        $isVoiceOnlyAnswer = $hasVoiceRecording && strtolower((string) ($answer->response_mode ?? '')) === 'voice';

        return (object) [
            'number' => $index + 1,
            'label' => 'Answer '.($index + 1),
            'question' => $questionText !== '' ? $questionText : 'Interview question '.($index + 1),
            'answer' => self::answerDisplay($answer, $answerText, $hasVoiceRecording, $isVoiceOnlyAnswer),
            'score' => self::scoreValue($answer->score),
            'score_label' => self::scoreLabel($answer, $status),
            'status' => $status,
            'status_label' => $statusLabel,
            'status_color' => self::statusColor($status),
            'confidence' => $confidence,
            'confidence_label' => self::confidenceLabel($confidence, $status),
            'confidence_color' => self::confidenceColor($confidence, $status),
            'confidence_note' => self::confidenceNote($confidence, $status),
            'feedback_quality' => $qualityPercent,
            'quality_label' => $qualityPercent === null ? 'Checks pending' : $qualityPercent.'% checked',
            'rubric_level' => trim((string) ($answer->rubric_level ?? '')),
            'evaluation_source' => self::evaluationSource(data_get($alignment, 'evaluation_source')),
            'evidence_quotes' => $evidenceQuotes,
            'evidence_quote' => $evidenceQuotes[0] ?? '',
            'missing_points' => $missingPoints,
            'next_steps' => $nextSteps,
            'what_worked' => $whatWorked,
            'feedback' => $feedback !== '' ? $feedback : 'No feedback was generated for this answer yet.',
            'improvement' => $improvement,
            'impact' => $impact,
            'next_practice' => $nextPractice !== '' ? $nextPractice : 'Try again with a direct opening and one true supporting detail.',
            'success_check' => $successCheck !== '' ? $successCheck : 'A reviewer can find the direct answer, the supporting detail, and the result or lesson.',
            'limitation' => self::cleanFeedback(data_get($alignment, 'limitation', ''), $question) ?: 'This review uses only the saved answer, question, and measurable practice data.',
            'better_answer' => $betterAnswer,
            'review_url' => route('user.review', $session->id),
            'has_voice_recording' => $hasVoiceRecording,
            'is_voice_only_answer' => $isVoiceOnlyAnswer,
            'voice_recording_url' => $hasVoiceRecording ? route('interview.answer.voiceRecording', $answer) : null,
        ];
    }

    private static function sessionReliability(InterviewSession $session, Collection $answerCards): object
    {
        $scoreConfidence = self::scoreValue($session->score?->scoring_confidence);
        $answerConfidence = $answerCards
            ->pluck('confidence')
            ->filter(fn ($value): bool => is_numeric($value))
            ->values();
        $confidence = $scoreConfidence ?? ($answerConfidence->isNotEmpty() ? (int) round($answerConfidence->avg()) : null);
        $lowCount = $answerCards->filter(fn (object $answer): bool => $answer->confidence === null || $answer->confidence < 55)->count();
        $answerCount = $answerCards->count();
        $coverage = is_array($session->feedback?->coaching_summary ?? null) ? $session->feedback->coaching_summary : [];
        $quality = self::scoreValue(data_get($coverage, 'feedback_quality.completeness_percent'));

        return (object) [
            'score' => $confidence,
            'label' => self::confidenceLabel($confidence, $answerCount === 0 ? 'not_evaluated' : 'directly_answered'),
            'color' => self::confidenceColor($confidence, $answerCount === 0 ? 'not_evaluated' : 'directly_answered'),
            'description' => self::sessionReliabilityDescription($confidence, $lowCount, $answerCount),
            'low_confidence_count' => $lowCount,
            'quality_label' => $quality === null ? 'Quality checks pending' : $quality.'% quality checks',
            'version_label' => 'Rubric v'.(int) ($session->score?->score_version ?? 0),
        ];
    }

    private static function proofStats(InterviewSession $session, Collection $answerCards): object
    {
        $coverage = is_array($session->feedback?->coaching_summary ?? null) ? $session->feedback->coaching_summary : [];
        $answers = $answerCards->count();
        $withEvidence = $answerCards->filter(fn (object $answer): bool => $answer->evidence_quotes !== [])->count();
        $missingPoints = $answerCards->sum(fn (object $answer): int => count($answer->missing_points));
        $needsPractice = $answerCards->filter(fn (object $answer): bool => in_array($answer->status, [
            'partially_answered',
            'low_relevance',
            'insufficient_evidence',
            'skipped',
            'not_evaluated',
        ], true) || ($answer->score !== null && $answer->score < 70))->count();

        return (object) [
            'answers' => $answers,
            'with_evidence' => $withEvidence,
            'missing_points' => $missingPoints,
            'needs_practice' => $needsPractice,
            'delivery_measured' => max(0, (int) data_get($coverage, 'coverage.delivery_measured', data_get($coverage, 'delivery_measured', 0))),
            'camera_measured' => max(0, (int) data_get($coverage, 'coverage.camera_measured', data_get($coverage, 'camera_measured', 0))),
        ];
    }

    private static function nextAction(InterviewSession $session, Collection $answerCards): object
    {
        foreach ((array) data_get($session->feedback?->coaching_summary ?? [], 'priority_actions', []) as $priority) {
            if (! is_array($priority)) {
                continue;
            }

            $action = self::cleanFeedback($priority['action'] ?? '');
            if ($action === '') {
                continue;
            }

            return (object) [
                'area' => self::cleanFeedback($priority['area'] ?? 'Top practice focus') ?: 'Top practice focus',
                'action' => self::limitText($action, 210),
                'evidence' => self::limitText(self::cleanFeedback($priority['observation'] ?? ''), 210),
                'success_check' => self::limitText(self::cleanFeedback($priority['success_check'] ?? ''), 210),
                'question_number' => null,
                'review_url' => route('user.review', $session->id),
            ];
        }

        $candidate = $answerCards
            ->sortBy(fn (object $answer): int => $answer->score ?? 999)
            ->first(fn (object $answer): bool => $answer->next_practice !== '');

        if ($candidate) {
            return (object) [
                'area' => 'Question '.$candidate->number.' - '.$candidate->status_label,
                'action' => $candidate->next_practice,
                'evidence' => $candidate->evidence_quote !== '' ? 'Evidence: '.$candidate->evidence_quote : $candidate->feedback,
                'success_check' => $candidate->success_check,
                'question_number' => $candidate->number,
                'review_url' => $candidate->review_url,
            ];
        }

        return (object) [
            'area' => 'Start with one complete answer',
            'action' => 'Complete a scored practice interview, then review the answer evidence before practicing again.',
            'evidence' => '',
            'success_check' => 'The next report shows saved answers with evidence quotes and clear next steps.',
            'question_number' => null,
            'review_url' => route('interview.setup'),
        ];
    }

    private static function recurringFocus(InterviewSession $session, Collection $answerCards): Collection
    {
        $items = collect();
        foreach ((array) data_get($session->feedback?->coaching_summary ?? [], 'priority_actions', []) as $priority) {
            if (! is_array($priority)) {
                continue;
            }

            $area = self::cleanFeedback($priority['area'] ?? '');
            $action = self::cleanFeedback($priority['action'] ?? '');
            if ($area === '' && $action === '') {
                continue;
            }

            $items->push((object) [
                'area' => $area !== '' ? $area : 'Practice priority',
                'count' => max(1, (int) ($priority['affected_count'] ?? 1)),
                'action' => self::limitText($action, 170),
            ]);

            if ($items->count() >= 3) {
                return $items;
            }
        }

        return $answerCards
            ->filter(fn (object $answer): bool => $answer->missing_points !== [] || in_array($answer->status, ['partially_answered', 'low_relevance', 'insufficient_evidence'], true))
            ->map(fn (object $answer): object => (object) [
                'area' => 'Question '.$answer->number.' - '.$answer->status_label,
                'count' => max(1, count($answer->missing_points)),
                'action' => $answer->next_practice,
            ])
            ->take(3)
            ->values();
    }

    private static function answers(InterviewSession $session): Collection
    {
        $answers = $session->relationLoaded('answers')
            ? $session->answers
            : $session->answers()->whereNull('retry_of_answer_id')->with('question')->orderBy('id')->get();

        return collect($answers)
            ->filter(fn ($answer): bool => $answer instanceof InterviewAnswer && $answer->retry_of_answer_id === null)
            ->values();
    }

    private static function alignmentStatus(InterviewAnswer $answer, array $alignment): string
    {
        $status = trim((string) ($alignment['status'] ?? ''));
        if ($status !== '') {
            return $status;
        }

        if ((bool) ($answer->is_skipped ?? false)) {
            return 'skipped';
        }

        $answerText = self::answerContent($answer);
        if ($answerText === '') {
            return 'not_evaluated';
        }

        return is_numeric($answer->score ?? null) ? 'directly_answered' : 'not_evaluated';
    }

    private static function statusLabel(string $status, mixed $storedLabel = null): string
    {
        $label = is_scalar($storedLabel) ? trim((string) $storedLabel) : '';
        if ($label !== '') {
            return match (strtolower($label)) {
                'directly answered' => 'Answered directly',
                'partially answered' => 'Answered partly',
                'low relevance' => 'Low match',
                'not enough evidence' => 'Not enough detail',
                'not evaluated' => 'Not checked',
                default => $label,
            };
        }

        return match ($status) {
            'directly_answered' => 'Answered directly',
            'partially_answered' => 'Answered partly',
            'low_relevance' => 'Low match',
            'insufficient_evidence' => 'Not enough detail',
            'skipped' => 'Skipped',
            default => 'Not checked',
        };
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            'directly_answered' => '#10b981',
            'partially_answered', 'insufficient_evidence' => '#f59e0b',
            'low_relevance', 'skipped' => '#ef4444',
            default => '#64748b',
        };
    }

    private static function confidenceLabel(?int $confidence, string $status): string
    {
        if (in_array($status, ['skipped', 'insufficient_evidence', 'not_evaluated'], true) && $confidence === null) {
            return 'Not enough evidence';
        }

        return match (true) {
            $confidence === null => 'Confidence pending',
            $confidence >= 80 => 'High confidence',
            $confidence >= 55 => 'Medium confidence',
            default => 'Low confidence',
        };
    }

    private static function confidenceColor(?int $confidence, string $status): string
    {
        if (in_array($status, ['skipped', 'insufficient_evidence', 'not_evaluated'], true) && $confidence === null) {
            return '#64748b';
        }

        return match (true) {
            $confidence === null => '#64748b',
            $confidence >= 80 => '#10b981',
            $confidence >= 55 => '#2563eb',
            default => '#f59e0b',
        };
    }

    private static function confidenceNote(?int $confidence, string $status): string
    {
        if (in_array($status, ['skipped', 'insufficient_evidence'], true)) {
            return 'The answer needs more usable detail before the score should be trusted strongly.';
        }

        return match (true) {
            $confidence === null => 'The app did not store a confidence value for this answer.',
            $confidence >= 80 => 'Enough answer detail was available for a stable review.',
            $confidence >= 55 => 'The review is usable, but one or more details were limited.',
            default => 'Treat this as a coaching hint and retry with more complete detail.',
        };
    }

    private static function sessionReliabilityDescription(?int $confidence, int $lowCount, int $answerCount): string
    {
        if ($answerCount === 0) {
            return 'No saved answers are available, so feedback cannot be checked against answer evidence.';
        }

        $prefix = match (true) {
            $confidence === null => 'Confidence is not stored for this session.',
            $confidence >= 80 => 'The session feedback has strong answer evidence.',
            $confidence >= 55 => 'The session feedback is usable, with some uncertainty.',
            default => 'The session feedback should be treated as a coaching draft.',
        };

        if ($lowCount > 0) {
            return $prefix.' '.$lowCount.' '.Str::plural('answer', $lowCount).' need more evidence or clearer scoring.';
        }

        return $prefix.' Every answer has enough evidence for the displayed coaching notes.';
    }

    private static function scoreLabel(InterviewAnswer $answer, string $status): string
    {
        if ((bool) ($answer->is_skipped ?? false) || $status === 'skipped') {
            return 'Skipped';
        }

        $score = self::scoreValue($answer->score);

        return $score === null ? 'Not scored' : $score.'%';
    }

    private static function evaluationSource(mixed $source): string
    {
        $source = is_scalar($source) ? trim((string) $source) : '';

        return match ($source) {
            'local_evidence' => 'Local evidence check',
            'local_fallback' => 'Fallback evidence check',
            'provider' => 'AI provider check',
            '' => 'Saved review',
            default => Str::headline(str_replace('_', ' ', $source)),
        };
    }

    private static function answerDisplay(InterviewAnswer $answer, string $answerText, bool $hasVoiceRecording, bool $isVoiceOnlyAnswer): string
    {
        if ($answerText !== '') {
            return self::limitText($answerText, 260);
        }

        if ($isVoiceOnlyAnswer) {
            return 'Voice answer saved. Feedback is based on the saved voice session.';
        }

        return $hasVoiceRecording ? 'Transcript unavailable. Listen to the saved voice answer.' : 'No answer text recorded.';
    }

    private static function answerContent(InterviewAnswer $answer): string
    {
        $answerText = self::cleanText((string) ($answer->answer_text ?? ''));
        if ($answerText !== '') {
            return $answerText;
        }

        return self::cleanText((string) ($answer->delivery_transcript ?? ''));
    }

    private static function textList(mixed $items, int $limit = 3, int $characterLimit = 180): array
    {
        if (! is_array($items)) {
            return [];
        }

        $results = [];
        foreach ($items as $item) {
            $text = is_scalar($item) ? self::limitText((string) $item, $characterLimit) : '';
            if ($text === '' || in_array($text, $results, true)) {
                continue;
            }

            $results[] = $text;
            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    private static function feedbackTextList(mixed $items, mixed $questionSource = null, int $limit = 3, int $characterLimit = 180): array
    {
        $results = [];
        foreach (self::textList($items, $limit, $characterLimit) as $item) {
            $text = self::cleanFeedback($item, $questionSource);
            if ($text === '' || in_array($text, $results, true)) {
                continue;
            }

            $results[] = $text;
            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    private static function cleanFeedback(mixed $text, mixed $questionSource = null): string
    {
        $text = is_scalar($text) ? self::cleanText((string) $text) : '';
        if ($text === '') {
            return '';
        }

        if (function_exists('review_feedback_without_question_text')) {
            return self::cleanText(review_feedback_without_question_text($text, $questionSource));
        }

        return $text;
    }

    private static function betterAnswer(string $text, InterviewAnswer $answer, mixed $questionSource): string
    {
        $text = self::cleanText($text);
        if ($text === '') {
            return 'No improved draft was generated. Retry with a direct answer, one specific detail, and a true result or lesson.';
        }

        if (function_exists('review_better_answer_text')) {
            return self::cleanText(review_better_answer_text($text, $answer, $questionSource));
        }

        return $text;
    }

    private static function scoreValue(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return max(0, min(100, (int) round((float) $value)));
    }

    private static function cleanText(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private static function limitText(string $text, int $limit): string
    {
        $clean = self::cleanText($text);
        if ($clean === '' || mb_strlen($clean, 'UTF-8') <= $limit) {
            return $clean;
        }

        return rtrim(mb_substr($clean, 0, max(1, $limit - 3), 'UTF-8'), " \t\n\r\0\x0B.,;:").'...';
    }
}
