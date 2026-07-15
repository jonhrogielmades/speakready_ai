<?php

namespace App\Services;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use Illuminate\Support\Collection;

class TrustworthyAssessmentService
{
    public const SCORE_VERSION = 2;

    public function deliveryStability(string $answerText, int $wpm, int $fillerWords, int $pauseCount, int $voiceDuration): ?int
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
            'clarity' => .25,
            'relevance' => .35,
            'professionalism' => .20,
            'grammar' => $languageScoringEnabled ? .10 : 0,
            'star' => $starApplicable ? .10 : 0,
        ];
        $weightTotal = array_sum($weights);
        if ($weightTotal <= 0) {
            return 0;
        }

        $score = 0;
        foreach ($weights as $key => $weight) {
            $score += max(0, min(100, (int) ($metrics[$key] ?? 0))) * ($weight / $weightTotal);
        }

        return (int) round($score);
    }

    public function answerEvidence(string $answer, ?string $feedback = null): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($answer), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $evidence = collect($sentences)->filter(function (string $sentence) {
            return preg_match('/\b(I|we|my|our)\b/i', $sentence)
                && preg_match('/\b(created|built|led|resolved|improved|reduced|increased|delivered|designed|implemented|organized|managed|tested|analyzed|coordinated|achieved|learned)\b/i', $sentence);
        })->take(3)->values()->all();

        $missing = [];
        if (! preg_match('/\b\d+(?:\.\d+)?%?|\bpercent\b|\bresult|\boutcome|\bimpact|\bachieved|\breduced|\bincreased|\bimproved/i', $answer)) {
            $missing[] = 'A specific result, outcome, or measurable impact';
        }
        if (! preg_match('/\bI\s+(?:personally\s+)?(?:created|built|led|resolved|improved|reduced|increased|delivered|designed|implemented|organized|managed|tested|analyzed|coordinated|decided)/i', $answer)) {
            $missing[] = 'A clear statement of your personal action or ownership';
        }

        return [
            'supporting_excerpts' => $evidence,
            'missing_evidence' => $missing,
            'feedback_basis' => $feedback ? mb_substr($feedback, 0, 500) : null,
        ];
    }

    public function groundedRevisionTemplate(string $answer, ?array $evidence = null): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $answer) ?? '');
        if ($clean === '') {
            return '';
        }

        $evidence ??= $this->answerEvidence($clean);
        $excerpt = mb_substr($clean, 0, 700);
        $missing = $evidence['missing_evidence'] ?? [];
        $resultPrompt = in_array('A specific result, outcome, or measurable impact', $missing, true)
            ? '[Add only a truthful, verified result, or state that no metric was recorded.]'
            : '[Restate only the result already present in your answer.]';

        return "Fact-grounded revision template — preserve only details you can verify:\n"
            ."Situation/Task: [Briefly identify the context and your responsibility from your experience.]\n"
            ."Action: {$excerpt}\n"
            ."Result: {$resultPrompt}";
    }

    public function rubricLevel(int $score): array
    {
        return match (true) {
            $score >= 85 => ['level' => '4 - Strong evidence', 'next_level' => 'Maintain evidence quality under harder follow-ups.'],
            $score >= 70 => ['level' => '3 - Competent', 'next_level' => 'Add clearer ownership, constraints, tradeoffs, and measurable impact.'],
            $score >= 50 => ['level' => '2 - Partial evidence', 'next_level' => 'Answer the question directly and provide one complete, specific example.'],
            default => ['level' => '1 - Insufficient evidence', 'next_level' => 'Provide enough relevant detail for a job-related assessment.'],
        };
    }

    public function sessionMetadata(InterviewSession $session, Collection $answers, array $metrics, int $starScore, int $jobEvidenceScore): array
    {
        $answerEvidence = $answers->mapWithKeys(function (InterviewAnswer $answer) {
            return [$answer->id => $this->answerEvidence($answer->answer_text ?? '', $answer->ai_feedback)];
        })->all();
        $answered = $answers->where('is_skipped', false)->filter(fn ($answer) => trim((string) $answer->answer_text) !== '')->count();
        $confidence = min(95, max(20, 30 + ($answered * 10)));
        if ($answers->contains(fn ($answer) => empty($answer->ai_feedback))) {
            $confidence = max(20, $confidence - 25);
        }
        $deliveryScores = $answers->pluck('delivery_stability_score')->filter(fn ($value) => $value !== null);
        $starApplicable = $answers->contains(fn ($answer) => strtolower((string) $answer->question?->type) === 'behavioral');
        $languageScoring = ! ((bool) data_get($session->accommodation_profile, 'separate_language_scoring', false));
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
            'delivery_stability' => (int) round($deliveryScores->avg() ?? 0),
            'job_evidence_match' => $jobEvidenceScore,
            'evidence_map' => $answerEvidence,
            'rubric' => [
                'version' => self::SCORE_VERSION,
                'scale' => [
                    '1' => 'Insufficient evidence',
                    '2' => 'Partial evidence',
                    '3' => 'Competent job-related evidence',
                    '4' => 'Strong evidence with ownership and impact',
                ],
                'weights' => [
                    'clarity' => 25,
                    'relevance' => 35,
                    'professionalism' => 20,
                    'grammar' => $languageScoring ? 10 : 0,
                    'star_when_applicable' => $starApplicable ? 10 : 0,
                ],
                'body_language_included' => false,
                'delivery_stability_included' => false,
            ],
        ];
    }

    public function readinessBand(int $score): string
    {
        return $score >= 80 ? 'Ready for Simulation' : ($score >= 60 ? 'Nearly Ready' : 'Developing');
    }
}
