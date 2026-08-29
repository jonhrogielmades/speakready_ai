<?php

namespace App\Support;

use App\Models\InterviewSession;
use Illuminate\Support\Collection;

class FeedbackReportPresenter
{
    public static function forSession(InterviewSession $session): array
    {
        $feedback = $session->feedback;
        $strengths = trim((string) ($feedback->strengths ?? ''));
        $weaknesses = trim((string) ($feedback->weaknesses ?? ''));
        $suggestions = trim((string) ($feedback->improvement_suggestions ?? ''));
        $score = $session->score;
        $overall = is_numeric($score?->overall_readiness_score ?? null)
            ? self::score($score->overall_readiness_score)
            : null;
        $focus = self::primaryFocus($session);

        return [
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'suggestions' => $suggestions,
            'strength_items' => self::bulletItems($strengths, 'No strengths were generated for this session.'),
            'weakness_items' => self::bulletItems($weaknesses, 'No weak points were made for this session.'),
            'suggestion_items' => self::bulletItems($suggestions, 'Retry one answer with a clearer structure.', 3, 130),
            'overview' => [
                'summary' => self::overallSummary($overall),
                'focus_label' => $focus['label'],
                'focus_score' => $focus['score'],
                'focus_advice' => $focus['advice'],
            ],
            'conciseness' => self::concisenessStats(self::answerTexts($session)),
        ];
    }

    private static function bulletItems(string $text, string $fallback, int $limit = 4, int $characterLimit = 150): array
    {
        $clean = self::cleanText($text);
        if ($clean === '') {
            return [$fallback];
        }

        $parts = preg_split('/(?:\r?\n|;\s+|(?<=[.!?])\s+)/u', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [$clean];
        $items = [];
        $seen = [];

        foreach ($parts as $part) {
            $item = self::limitText($part, $characterLimit);
            if ($item === '') {
                continue;
            }

            $key = mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $item) ?? $item, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $items[] = $item;
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items !== [] ? $items : [self::limitText($clean, $characterLimit)];
    }

    private static function overallSummary(?int $overall): string
    {
        return match (true) {
            $overall === null => 'Feedback is ready. Review the focus area and retry one answer.',
            $overall >= 85 => 'Strong readiness. Keep the answers specific, direct, and easy to follow.',
            $overall >= 70 => 'Good foundation. Sharpen the weakest skill and add clearer proof.',
            $overall >= 50 => 'Promising start. Focus on direct answers, structure, and stronger examples.',
            default => 'Keep practicing. Answer each question directly and add one real example.',
        };
    }

    private static function primaryFocus(InterviewSession $session): array
    {
        $score = $session->score;
        $metrics = [
            [
                'label' => 'Clarity',
                'score' => $score?->clarity_score,
                'advice' => 'Use shorter sentences and put the main answer first.',
            ],
            [
                'label' => 'Answer Match',
                'score' => $score?->relevance_score,
                'advice' => 'Answer the exact question first, then add one useful example.',
            ],
            [
                'label' => 'Grammar',
                'score' => $score?->grammar_score,
                'advice' => 'Use simple sentence patterns and remove repeated wording.',
            ],
            [
                'label' => 'Tone',
                'score' => $score?->professionalism_score,
                'advice' => 'Keep the wording direct, respectful, and role-focused.',
            ],
        ];

        $jobScore = $score?->job_evidence_match_score;
        if (is_numeric($jobScore) && ((int) $jobScore > 0 || trim((string) ($session->job_description ?? '')) !== '')) {
            $metrics[] = [
                'label' => 'Job Detail Match',
                'score' => $jobScore,
                'advice' => 'Connect one answer detail to the role or company need.',
            ];
        }

        $deliveryMeasured = (int) data_get($session->feedback?->coaching_summary ?? [], 'coverage.delivery_measured', 0) > 0
            || self::answers($session)->contains(fn ($answer) => data_get($answer->coaching_feedback ?? [], 'delivery.status') === 'measured');
        if ($deliveryMeasured && is_numeric($score?->delivery_stability_score ?? null)) {
            $metrics[] = [
                'label' => 'Speaking Steadiness',
                'score' => $score->delivery_stability_score,
                'advice' => 'Pause between ideas and reduce filler words.',
            ];
        }

        $metrics = array_values(array_filter($metrics, fn (array $metric): bool => is_numeric($metric['score'] ?? null)));
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
        $averageWords = $answerCount > 0 ? (int) round($totalWords / $answerCount) : 0;
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
            foreach ($matches[0] ?? [] as $word) {
                $word = trim($word, "'");
                if (mb_strlen($word, 'UTF-8') < 4 || isset($stopWords[$word])) {
                    continue;
                }

                $counts[$word] = ($counts[$word] ?? 0) + 1;
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
            ->filter(fn ($answer): bool => ! (bool) ($answer->is_skipped ?? false) && trim((string) ($answer->answer_text ?? '')) !== '')
            ->map(fn ($answer): string => (string) $answer->answer_text)
            ->values();
    }

    private static function answers(InterviewSession $session): Collection
    {
        $answers = $session->relationLoaded('answers')
            ? $session->answers
            : $session->answers()->whereNull('retry_of_answer_id')->get();

        return $answers instanceof Collection ? $answers : collect($answers);
    }

    private static function wordCount(string $text): int
    {
        preg_match_all('/[\p{L}\p{N}\']+/u', $text, $matches);

        return count($matches[0] ?? []);
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

    private static function score(mixed $score): int
    {
        return max(0, min(100, (int) round((float) $score)));
    }
}
