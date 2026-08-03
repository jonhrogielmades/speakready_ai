<?php

namespace App\Services;

use App\Models\GameLevel;
use Illuminate\Support\Str;

class LearningGameScoringService
{
    private const SCORECARD_VERSION = 1;

    private const OWNERSHIP_PATTERN = '\b(?:I|my|me|we|our)\b';

    private const ACTION_PATTERN = '\b(?:led|owned|built|created|resolved|solved|fixed|improved|reduced|increased|delivered|designed|implemented|organized|managed|tested|analyzed|coordinated|handled|supported|communicated|verified|checked|planned|explained|documented|escalated|prepared|trained|assisted|proposed|researched|deployed|investigated|reported|presented|negotiated|facilitated|processed|scheduled|prioritized|recommended)\b';

    private const RESULT_PATTERN = '\b(?:result|outcome|impact|achieved|improved|reduced|increased|delivered|saved|resolved|completed|finished|passed|learned|success|met|exceeded|faster|within|\d+(?:\.\d+)?%?|percent)\b';

    public function scoreSession(GameLevel $level, array $answersData): array
    {
        $perQuestion = collect($answersData)
            ->map(fn (array $answer) => $this->scoreAnswer($answer, $level))
            ->values()
            ->all();

        $score = (int) round(collect($perQuestion)->avg('score') ?? 0);
        $passed = $score >= (int) $level->required_score;
        $averages = [
            'clarity' => (int) round(collect($perQuestion)->avg('clarity_score') ?? 0),
            'relevance' => (int) round(collect($perQuestion)->avg('relevance_score') ?? 0),
            'confidence' => (int) round(collect($perQuestion)->avg('confidence_score') ?? 0),
            'grammar' => (int) round(collect($perQuestion)->avg('grammar_score') ?? 0),
            'professionalism' => (int) round(collect($perQuestion)->avg('professionalism_score') ?? 0),
            'star_method' => (int) round(collect($perQuestion)->avg('star_method_score') ?? 0),
            'goal_coverage' => (int) round(collect($perQuestion)->avg('criteria_score') ?? 0),
        ];

        return [
            'score' => $score,
            'status' => $passed ? 'passed' : 'failed',
            'points_to_goal' => max(0, (int) $level->required_score - $score),
            'per_question' => $perQuestion,
            'averages' => $averages,
            'scorecard_version' => self::SCORECARD_VERSION,
            'ai_feedback_scorecard' => $this->scorecard($level, $perQuestion, $score, $passed, $averages),
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

        $criteriaScore = $this->criteriaScore($answerText, $level->guidance_checklist ?? []);
        $starScore = $this->starScore($answerText);
        $keywordScore = $this->keywordOverlapScore($answerText, implode(' ', array_filter([
            $questionText,
            $level->skill_focus,
            $level->learning_objective,
            $level->guidance_checklist_text,
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
        $confidence = $this->confidenceScore($answerText, $wordCount, $fillerPenalty, $answer, $starScore, $criteriaScore);

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
        if ($confidence < 70) {
            $goalNotes[] = 'State your ownership, decision, and result with more specific evidence.';
        }
        if ($goalNotes === []) {
            $goalNotes[] = 'This answer meets the main goal signals for the level.';
        }

        $metricScores = [
            'clarity' => $clarity,
            'relevance' => $relevance,
            'confidence' => $confidence,
            'grammar' => $grammar,
            'professionalism' => $professionalism,
            'goal_coverage' => $criteriaScore,
            'star_method' => $starScore,
        ];
        asort($metricScores);
        $lowestMetric = (string) array_key_first($metricScores);

        return [
            'id' => $answer['id'] ?? null,
            'question_index' => (int) ($answer['question_index'] ?? 0),
            'score' => $score,
            'clarity_score' => $clarity,
            'relevance_score' => $relevance,
            'confidence_score' => $confidence,
            'grammar_score' => $grammar,
            'professionalism_score' => $professionalism,
            'star_method_score' => $starScore,
            'criteria_score' => $criteriaScore,
            'banned_words' => $bannedHits,
            'goal_notes' => implode(' ', $goalNotes),
            'star_applicable' => $this->starIsApplicable($answer, $level),
            'scorecard_feedback' => $this->feedbackForMetric($lowestMetric, (int) $metricScores[$lowestMetric], $score),
            'weakest_metric' => $lowestMetric,
            'evidence_flags' => [
                'word_count' => $wordCount,
                'has_ownership' => $this->hasOwnership($answerText),
                'has_action' => $this->hasAction($answerText),
                'has_result' => $this->hasResult($answerText),
                'camera_included' => false,
            ],
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
            'confidence_score' => 0,
            'grammar_score' => 0,
            'professionalism_score' => 0,
            'star_method_score' => 0,
            'criteria_score' => 0,
            'banned_words' => [],
            'goal_notes' => 'No answer was submitted for this prompt.',
            'star_applicable' => false,
            'scorecard_feedback' => 'Submit a complete answer before relying on this challenge score.',
            'weakest_metric' => 'relevance',
            'evidence_flags' => [
                'word_count' => 0,
                'has_ownership' => false,
                'has_action' => false,
                'has_result' => false,
                'camera_included' => false,
            ],
        ];
    }

    private function scorecard(GameLevel $level, array $perQuestion, int $score, bool $passed, array $averages): array
    {
        $metrics = [];
        foreach (['clarity', 'relevance', 'confidence', 'grammar', 'professionalism', 'goal_coverage', 'star_method'] as $metric) {
            $metricScore = (int) ($averages[$metric] ?? 0);
            if ($metric === 'star_method' && ! collect($perQuestion)->contains(fn (array $item): bool => (bool) ($item['star_applicable'] ?? false))) {
                continue;
            }

            $metrics[$metric] = [
                'label' => $this->metricLabel($metric),
                'score' => $metricScore,
                'level' => $this->metricLevel($metricScore),
                'feedback' => $this->metricFeedback($metric, $metricScore),
                'weight' => $this->metricWeight($metric),
            ];
        }

        $rankedMetrics = $metrics;
        uasort($rankedMetrics, fn (array $a, array $b): int => ((int) $a['score']) <=> ((int) $b['score']));
        $weakestKey = (string) array_key_first($rankedMetrics);
        $strongestKey = collect($metrics)->sortByDesc('score')->keys()->first();
        $priorityActions = collect($rankedMetrics)
            ->filter(fn (array $metric): bool => (int) $metric['score'] < 80)
            ->take(3)
            ->map(fn (array $metric, string $key): string => $this->priorityAction($key))
            ->values()
            ->all();

        if ($priorityActions === []) {
            $priorityActions[] = 'Try a harder follow-up and keep the same clear evidence.';
        }

        $questionFeedback = collect($perQuestion)
            ->map(fn (array $item): array => [
                'question_index' => (int) ($item['question_index'] ?? 0),
                'score' => (int) ($item['score'] ?? 0),
                'weakest_metric' => $item['weakest_metric'] ?? null,
                'weakest_label' => $this->metricLabel((string) ($item['weakest_metric'] ?? 'relevance')),
                'feedback' => $item['scorecard_feedback'] ?? ($item['goal_notes'] ?? 'Review this answer before retrying.'),
            ])
            ->values()
            ->all();

        $reliabilityScore = $this->scoreReliability($perQuestion);

        return [
            'title' => 'AI Feedback Scorecard',
            'version' => self::SCORECARD_VERSION,
            'overall_score' => $score,
            'required_score' => (int) $level->required_score,
            'status' => $passed ? 'passed' : 'needs_retry',
            'summary' => $this->scorecardSummary($passed, $score, $strongestKey, $weakestKey),
            'metrics' => $metrics,
            'priority_actions' => $priorityActions,
            'question_feedback' => $questionFeedback,
            'reliability_score' => $reliabilityScore,
            'reliability_band' => $this->reliabilityBand($reliabilityScore),
            'evidence_policy' => 'Scores are based only on submitted challenge answers, level goals, transcript timing, and text evidence. Optional camera estimates are excluded.',
            'guidance_note' => 'Use this as coaching guidance, not a guarantee of real hiring performance.',
            'body_language_included' => false,
        ];
    }

    private function confidenceScore(string $answerText, int $wordCount, int $fillerPenalty, array $answer, int $starScore, int $criteriaScore): int
    {
        if ($wordCount === 0) {
            return 0;
        }

        $score = 34 + min(28, (int) round($wordCount * 1.2));
        $score += $this->hasOwnership($answerText) ? 10 : -8;
        $score += $this->hasAction($answerText) ? 10 : -8;
        $score += $this->hasResult($answerText) ? 8 : 0;
        $score += $criteriaScore >= 70 ? 6 : 0;
        $score += $starScore >= 75 ? 4 : 0;
        $score -= $wordCount < 18 ? 14 : 0;
        $score -= $fillerPenalty;

        $voiceDuration = (int) ($answer['voice_duration'] ?? 0);
        $wpm = (int) ($answer['wpm'] ?? 0);
        $pauseCount = (int) ($answer['pause_count'] ?? 0);
        $spokenFillerCount = (int) ($answer['filler_words_count'] ?? 0);

        if ($voiceDuration > 0 || $wpm > 0 || $pauseCount > 0 || $spokenFillerCount > 0) {
            if ($wpm >= 100 && $wpm <= 170) {
                $score += 8;
            } elseif ($wpm > 0 && ($wpm < 80 || $wpm > 210)) {
                $score -= 10;
            }

            $score -= min(16, (int) round(($pauseCount / max(1, $wordCount)) * 120));
            $score -= min(14, (int) round(($spokenFillerCount / max(1, $wordCount)) * 150));
        }

        return $this->clamp($score);
    }

    private function scoreReliability(array $perQuestion): int
    {
        $total = count($perQuestion);
        if ($total === 0) {
            return 0;
        }

        $answered = collect($perQuestion)->filter(fn (array $item): bool => (int) ($item['score'] ?? 0) > 0)->count();
        $avgWords = (int) round(collect($perQuestion)->avg('evidence_flags.word_count') ?? 0);
        $shortAnswers = collect($perQuestion)->filter(fn (array $item): bool => (int) data_get($item, 'evidence_flags.word_count', 0) > 0 && (int) data_get($item, 'evidence_flags.word_count', 0) < 15)->count();
        $evidenceCoverage = collect($perQuestion)->avg(function (array $item): int {
            return collect([
                data_get($item, 'evidence_flags.has_ownership', false),
                data_get($item, 'evidence_flags.has_action', false),
                data_get($item, 'evidence_flags.has_result', false),
            ])->filter()->count();
        }) ?? 0;

        $score = 25
            + (int) round(($answered / max(1, $total)) * 35)
            + min(20, $avgWords)
            + (int) round(($evidenceCoverage / 3) * 15)
            + ($total >= 2 ? 5 : 0)
            - ($shortAnswers * 8);

        return min(95, $this->clamp($score));
    }

    private function scorecardSummary(bool $passed, int $score, ?string $strongestKey, string $weakestKey): string
    {
        $strongest = $strongestKey ? Str::lower($this->metricLabel($strongestKey)) : 'evidence';
        $weakest = Str::lower($this->metricLabel($weakestKey));

        if ($passed) {
            return "Passed with {$score}%. Your strongest signal is {$strongest}; keep improving {$weakest} for harder Philippine interview follow-ups.";
        }

        return "Scored {$score}%. Focus first on {$weakest}, then retry with a more complete example and clearer result.";
    }

    private function feedbackForMetric(string $metric, int $metricScore, int $overallScore): string
    {
        if ($overallScore === 0) {
            return 'Submit an answer with enough detail to score.';
        }

        return match ($metric) {
            'clarity' => 'Make the answer easier to follow: answer directly, then add one specific example.',
            'relevance' => 'Tie the response more closely to the exact question and challenge checklist.',
            'confidence' => 'State your ownership, decision, and result more decisively using evidence you can verify.',
            'grammar' => 'Use complete sentences and remove repeated filler words or broken phrasing.',
            'professionalism' => 'Keep the tone accountable, respectful, and ready for a Philippine hiring conversation.',
            'goal_coverage' => 'Cover more of the level goals before ending the answer.',
            'star_method' => 'Complete the Situation, Task, Action, and Result parts of the story.',
            default => $metricScore >= 80 ? 'Keep this level of evidence.' : 'Add more specific evidence before retrying.',
        };
    }

    private function metricFeedback(string $metric, int $score): string
    {
        if ($score >= 85) {
            return 'Strong signal. Maintain this under follow-up questions.';
        }

        if ($score >= 70) {
            return 'Usable signal. Add one sharper proof point to lift it.';
        }

        return $this->priorityAction($metric);
    }

    private function priorityAction(string $metric): string
    {
        return match ($metric) {
            'clarity' => 'Open with a direct answer, then organize the example in two or three steps.',
            'relevance' => 'Mirror the question keywords and connect the answer to the role or scenario.',
            'confidence' => 'Use first-person ownership and name the action or decision you took.',
            'grammar' => 'Rewrite the answer into complete, concise sentences before practicing aloud.',
            'professionalism' => 'Use respectful, accountable wording and avoid casual filler.',
            'goal_coverage' => 'Check every success criterion and add the missing evidence.',
            'star_method' => 'Add the missing STAR part, especially the action or result.',
            default => 'Add specific, truthful evidence that answers the prompt.',
        };
    }

    private function metricLabel(string $metric): string
    {
        return match ($metric) {
            'clarity' => 'Clarity',
            'relevance' => 'Relevance',
            'confidence' => 'Confidence',
            'grammar' => 'Grammar',
            'professionalism' => 'Professionalism',
            'goal_coverage' => 'Goal Coverage',
            'star_method' => 'STAR Method',
            default => Str::headline(str_replace('_', ' ', $metric)),
        };
    }

    private function metricLevel(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Strong',
            $score >= 70 => 'Competent',
            $score >= 50 => 'Needs Work',
            default => 'Limited',
        };
    }

    private function metricWeight(string $metric): int
    {
        return match ($metric) {
            'relevance' => 34,
            'clarity' => 24,
            'grammar', 'professionalism', 'goal_coverage', 'star_method' => 14,
            'confidence' => 0,
            default => 0,
        };
    }

    private function reliabilityBand(int $score): string
    {
        return match (true) {
            $score >= 85 => 'High',
            $score >= 65 => 'Moderate',
            default => 'Limited',
        };
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

    private function hasOwnership(string $text): bool
    {
        return (bool) preg_match('/'.self::OWNERSHIP_PATTERN.'/i', $text);
    }

    private function hasAction(string $text): bool
    {
        return (bool) preg_match('/'.self::ACTION_PATTERN.'/i', $text);
    }

    private function hasResult(string $text): bool
    {
        return (bool) preg_match('/'.self::RESULT_PATTERN.'/i', $text);
    }

    private function clamp($value, int $min = 0, int $max = 100): int
    {
        return max($min, min($max, (int) round($value)));
    }
}
