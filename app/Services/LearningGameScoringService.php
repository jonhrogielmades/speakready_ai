<?php

namespace App\Services;

use App\Models\GameLevel;
use Illuminate\Support\Str;

class LearningGameScoringService
{
    public function scoreSession(GameLevel $level, array $answersData): array
    {
        $perQuestion = collect($answersData)
            ->map(fn (array $answer) => $this->scoreAnswer($answer, $level))
            ->values()
            ->all();

        $score = (int) round(collect($perQuestion)->avg('score') ?? 0);
        $passed = $score >= (int) $level->required_score;

        return [
            'score' => $score,
            'status' => $passed ? 'passed' : 'failed',
            'points_to_goal' => max(0, (int) $level->required_score - $score),
            'per_question' => $perQuestion,
            'averages' => [
                'clarity' => (int) round(collect($perQuestion)->avg('clarity_score') ?? 0),
                'relevance' => (int) round(collect($perQuestion)->avg('relevance_score') ?? 0),
                'grammar' => (int) round(collect($perQuestion)->avg('grammar_score') ?? 0),
                'professionalism' => (int) round(collect($perQuestion)->avg('professionalism_score') ?? 0),
                'star_method' => (int) round(collect($perQuestion)->avg('star_method_score') ?? 0),
                'goal_coverage' => (int) round(collect($perQuestion)->avg('criteria_score') ?? 0),
            ],
        ];
    }

    public function scoreAnswer(array $answer, GameLevel $level): array
    {
        $answerText = trim((string) ($answer['answer'] ?? ''));
        $questionText = (string) ($answer['question'] ?? '');
        $isSkipped = (bool) ($answer['is_skipped'] ?? false) || $answerText === '' || $answerText === '(Skipped or no answer)';

        if ($isSkipped) {
            return $this->emptyScore($answer);
        }

        $wordCount = TranscriptService::wordCount($answerText);
        $sentenceCount = max(1, preg_match_all('/[.!?]+/', $answerText) ?: 1);
        $fillerCount = preg_match_all('/\b(um|uh|like|you know|basically|actually|literally|sort of|kind of)\b/i', $answerText) ?: 0;
        $fillerPenalty = min(18, $fillerCount * 4);
        $bannedHits = $this->bannedWordHits($answerText, (string) ($level->banned_words ?? ''));

        $criteriaScore = $this->criteriaScore($answerText, $level->parsed_success_criteria ?? []);
        $starScore = $this->starScore($answerText);
        $keywordScore = $this->keywordOverlapScore($answerText, implode(' ', array_filter([
            $questionText,
            $level->skill_focus,
            $level->learning_objective,
            $level->success_criteria,
        ])));

        $clarity = $this->clamp(
            25
            + min(38, $wordCount * 1.6)
            + ($sentenceCount >= 2 ? 10 : 0)
            + (preg_match('/\b(first|then|because|therefore|so|finally|result|outcome)\b/i', $answerText) ? 8 : 0)
            - ($wordCount < 15 ? 14 : 0)
            - $fillerPenalty
        );

        $relevance = $this->clamp(
            25
            + $keywordScore
            + round($criteriaScore * 0.32)
            + ($wordCount >= 25 ? 10 : 0)
            - (count($bannedHits) > 0 ? 8 : 0)
        );

        $grammar = $this->clamp(
            45
            + min(30, $wordCount)
            + (preg_match('/^[A-Z]/', $answerText) ? 8 : 0)
            + (preg_match('/[.!?]$/', $answerText) ? 8 : 0)
            - $fillerPenalty
            - ($this->hasRepeatedAdjacentWords($answerText) ? 10 : 0)
        );

        $professionalism = $this->clamp(
            72
            + ($wordCount >= 30 ? 8 : 0)
            + $this->targetToneBonus($answerText, (string) ($level->target_tone ?? ''))
            - $fillerPenalty
            - (count($bannedHits) * 14)
            - ($wordCount < 12 ? 18 : 0)
        );

        $gameStructure = max($criteriaScore, $starScore);
        $score = $this->clamp(
            round(($clarity * 0.24) + ($relevance * 0.34) + ($grammar * 0.14) + ($professionalism * 0.14) + ($gameStructure * 0.14))
        );

        $goalNotes = [];
        if ($criteriaScore < 70) {
            $goalNotes[] = 'Add clearer evidence for the level goal checklist.';
        }
        if ($starScore < 70 && $this->starIsApplicable($answer, $level)) {
            $goalNotes[] = 'Use Situation, Task, Action, and Result more completely.';
        }
        if (count($bannedHits) > 0) {
            $goalNotes[] = 'Avoid banned words or phrases: '.implode(', ', $bannedHits).'.';
        }
        if ($goalNotes === []) {
            $goalNotes[] = 'This answer meets the main goal signals for the level.';
        }

        return [
            'id' => $answer['id'] ?? null,
            'question_index' => (int) ($answer['question_index'] ?? 0),
            'score' => $score,
            'clarity_score' => $clarity,
            'relevance_score' => $relevance,
            'grammar_score' => $grammar,
            'professionalism_score' => $professionalism,
            'star_method_score' => $starScore,
            'criteria_score' => $criteriaScore,
            'banned_words' => $bannedHits,
            'goal_notes' => implode(' ', $goalNotes),
            'star_applicable' => $this->starIsApplicable($answer, $level),
        ];
    }

    private function emptyScore(array $answer): array
    {
        return [
            'id' => $answer['id'] ?? null,
            'question_index' => (int) ($answer['question_index'] ?? 0),
            'score' => 0,
            'clarity_score' => 0,
            'relevance_score' => 0,
            'grammar_score' => 0,
            'professionalism_score' => 0,
            'star_method_score' => 0,
            'criteria_score' => 0,
            'banned_words' => [],
            'goal_notes' => 'No answer was submitted for this prompt.',
            'star_applicable' => false,
        ];
    }

    private function criteriaScore(string $answerText, array $criteria): int
    {
        $criteria = array_values(array_filter($criteria));
        if ($criteria === []) {
            return $this->starScore($answerText);
        }

        $scores = [];
        foreach ($criteria as $criterion) {
            $keywords = $this->keywords((string) $criterion);
            if ($keywords === []) {
                continue;
            }

            $matched = 0;
            foreach ($keywords as $keyword) {
                if (preg_match('/\b'.preg_quote($keyword, '/').'\w*\b/i', $answerText)) {
                    $matched++;
                }
            }

            $scores[] = min(100, (int) round(($matched / max(1, count($keywords))) * 100));
        }

        return $scores === [] ? 0 : $this->clamp(array_sum($scores) / count($scores));
    }

    private function keywordOverlapScore(string $answerText, string $referenceText): int
    {
        $answerKeywords = $this->keywords($answerText);
        $referenceKeywords = $this->keywords($referenceText);

        if ($referenceKeywords === []) {
            return min(35, TranscriptService::wordCount($answerText));
        }

        $matched = count(array_intersect($answerKeywords, $referenceKeywords));

        return $this->clamp(($matched / max(1, count($referenceKeywords))) * 55, 0, 55);
    }

    private function keywords(string $text): array
    {
        $stopWords = [
            'about', 'after', 'again', 'also', 'answer', 'because', 'before', 'being', 'could', 'during',
            'their', 'there', 'these', 'those', 'through', 'using', 'what', 'when', 'where', 'which',
            'while', 'with', 'would', 'your', 'youre', 'challenge', 'level', 'interview',
        ];

        preg_match_all('/[a-zA-Z][a-zA-Z\-]{3,}/', Str::lower($text), $matches);

        return array_values(array_unique(array_diff($matches[0] ?? [], $stopWords)));
    }

    private function starIsApplicable(array $answer, GameLevel $level): bool
    {
        return str_contains(Str::lower((string) ($answer['question_type'] ?? '')), 'behavioral')
            || str_contains(Str::lower((string) ($level->skill_focus ?? '')), 'star')
            || (bool) preg_match('/\b(describe|tell me about|time when|example|situation)\b/i', (string) ($answer['question'] ?? ''));
    }

    private function starScore(string $answerText): int
    {
        $signals = 0;
        $signals += preg_match('/\b(situation|context|background|when|while|during)\b/i', $answerText) ? 1 : 0;
        $signals += preg_match('/\b(task|responsibility|goal|needed|objective|role)\b/i', $answerText) ? 1 : 0;
        $signals += preg_match('/\b(action|built|created|led|implemented|organized|managed|resolved|improved|coordinated|decided)\b/i', $answerText) ? 1 : 0;
        $signals += preg_match('/\b(result|outcome|impact|increased|reduced|improved|achieved|delivered|\d+%?|\bpercent\b)\b/i', $answerText) ? 1 : 0;

        return $signals * 25;
    }

    private function bannedWordHits(string $answerText, string $bannedWords): array
    {
        $words = preg_split('/[,;\n]+/', $bannedWords, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hits = [];

        foreach ($words as $word) {
            $word = trim($word);
            if ($word !== '' && preg_match('/\b'.preg_quote($word, '/').'\b/i', $answerText)) {
                $hits[] = $word;
            }
        }

        return array_values(array_unique($hits));
    }

    private function targetToneBonus(string $answerText, string $tone): int
    {
        $tone = Str::lower(trim($tone));
        if ($tone === '') {
            return 0;
        }

        return match (true) {
            str_contains($tone, 'confident') => preg_match('/\b(I led|I built|I decided|I improved|I delivered|I can|I will)\b/i', $answerText) ? 8 : -6,
            str_contains($tone, 'empathetic') => preg_match('/\b(team|customer|stakeholder|listen|support|understand)\b/i', $answerText) ? 8 : -6,
            str_contains($tone, 'professional') => preg_match('/\b(collaborated|prioritized|communicated|resolved|delivered)\b/i', $answerText) ? 8 : -4,
            default => 0,
        };
    }

    private function hasRepeatedAdjacentWords(string $text): bool
    {
        return (bool) preg_match('/\b(\w+)\s+\1\b/i', $text);
    }

    private function clamp($value, int $min = 0, int $max = 100): int
    {
        return max($min, min($max, (int) round($value)));
    }
}
