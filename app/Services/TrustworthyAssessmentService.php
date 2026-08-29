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

    public function answerEvidence(string $answer, ?string $feedback = null, Question|array|null $question = null): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($answer), -1, PREG_SPLIT_NO_EMPTY) ?: [];
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
        if ($resultRequired && ! $hasResult) {
            $missing[] = 'A clear result, effect, or lesson';
        }
        if ($personalActionRequired && ! $hasPersonalAction) {
            $missing[] = 'Your own action';
        }

        return [
            'supporting_excerpts' => $evidence,
            'missing_evidence' => $missing,
            'feedback_basis' => $feedback ? mb_substr($feedback, 0, 500) : null,
            'question_text' => $questionText,
            'question_intent' => $questionIntent,
            'star_applicable' => $starApplicable,
            'result_required' => $resultRequired,
            'personal_action_required' => $personalActionRequired,
            'has_result' => $hasResult,
            'has_personal_action' => $hasPersonalAction,
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
        $resultPrompt = in_array('A clear result, effect, or lesson', $missing, true)
            ? '[Add only a true result, or say that no number was recorded.]'
            : '[Restate only the result already present in your answer.]';

        if ($evidence['star_applicable'] ?? false) {
            return "Answer draft based on your facts - keep only details you can check:\n"
                ."Source answer: {$excerpt}\n"
                ."Situation/Task: [Briefly identify the context and your responsibility using only facts in your answer.]\n"
                ."Action: [Restate the specific action you personally took from the source answer.]\n"
                ."Result: {$resultPrompt}";
        }

        $questionText = trim((string) ($evidence['question_text'] ?? ''));
        $questionLabel = $questionText !== '' ? ' for "'.mb_substr($questionText, 0, 180).'"' : '';
        $intent = (string) ($evidence['question_intent'] ?? 'direct_evidence');
        $intentScaffold = match ($intent) {
            'strength' => [
                'Direct response: [Name only the strength supported by your source answer.]',
                'Proof: [Use the best true example already present.]',
                'Role connection: [Explain the role connection without adding an unsupported result.]',
            ],
            'strength_and_weakness' => [
                'Strength: [Name and support one true job strength.]',
                'Development area: [Name only the real weakness stated in the source answer.]',
                'Improvement: [Restate the improvement action and sign of progress already present.]',
            ],
            'weakness' => [
                'Development area: [State the real, manageable weakness from the source answer.]',
                'Effect: [Explain only the real effect already described.]',
                'Improvement: [Restate the clear improvement habit and progress already shown.]',
            ],
            'salary_expectation' => [
                'Direct response: [State only the range or flexibility actually supported by your source answer.]',
                'Basis: [Connect it to experience, responsibilities, or conditions without inventing market data.]',
                'Close: [State only the work-friendly openness already expressed.]',
            ],
            'motivation', 'role_fit' => [
                'Direct response: [State the specific reason or role fit supported by your source answer.]',
                'Proof: [Connect one skill, experience, or goal already present.]',
                'Contribution/next step: [Use only the contribution or career direction already stated.]',
            ],
            'self_introduction' => [
                'Present: [State the current professional or educational focus already provided.]',
                'Past: [Select only the experience that best fits the role.]',
                'Next step: [Restate the truthful role connection already present.]',
            ],
            'career_transition' => [
                'Reason: [State the reason briefly and clearly.]',
                'Learning: [Use only the lesson or need already described.]',
                'Next step: [Connect it to what you truthfully seek next.]',
            ],
            'technical' => [
                'Direct response: [State the technical conclusion already supported by the source.]',
                'Reasoning: [Organize only the diagnostic or reasoning steps already present.]',
                'Verification/tradeoff: [Restate only a verification step or tradeoff already mentioned.]',
            ],
            'situational' => [
                'Goal and constraints: [Use only the goal or constraint stated in the source.]',
                'Ordered action: [Organize the steps already proposed.]',
                'Success check: [Restate only how the answer says success would be checked.]',
            ],
            default => [
                'Direct response: [Answer the exact question in one sentence using only facts in your answer.]',
                'Supporting detail: [Organize the reasoning or actions already present in the source answer.]',
                'Result or lesson: '.$resultPrompt,
            ],
        };

        $evidencePrompt = ($evidence['result_required'] ?? true)
            ? $resultPrompt
            : '[Restate only the reasoning, check step, or result already present; do not invent one.]';

        if ($intent === 'direct_evidence') {
            $intentScaffold[2] = 'Result or lesson: '.$evidencePrompt;
        }

        return "Answer draft based on your facts{$questionLabel} - keep only details you can check:\n"
            ."Source answer: {$excerpt}\n"
            .implode("\n", $intentScaffold);
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

    public function sessionMetadata(InterviewSession $session, Collection $answers, array $metrics, int $starScore, int $jobEvidenceScore): array
    {
        $answerEvidence = $answers->mapWithKeys(function (InterviewAnswer $answer) {
            return [$answer->id => $this->answerEvidence($answer->answer_text ?? '', $answer->ai_feedback, $answer->question)];
        })->all();
        $answered = $answers->where('is_skipped', false)->filter(fn ($answer) => trim((string) $answer->answer_text) !== '')->count();
        $confidence = min(95, max(20, 30 + ($answered * 10)));
        if ($answers->contains(fn ($answer) => empty($answer->ai_feedback))) {
            $confidence = max(20, $confidence - 25);
        }
        $answerConfidences = $answers->pluck('scoring_confidence')->filter(fn ($value) => is_numeric($value) && (int) $value > 0);
        if ($answerConfidences->isNotEmpty()) {
            $confidence = min($confidence, (int) round($answerConfidences->avg()));
        }
        $deliveryScores = $answers->pluck('delivery_stability_score')->filter(fn ($value) => $value !== null);
        $starApplicable = $answers->contains(fn ($answer) => QuestionIntentService::starApplicable($answer->question));
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
                    '1' => 'Not enough detail',
                    '2' => 'Some detail',
                    '3' => 'Good job detail',
                    '4' => 'Strong detail with your action and result',
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
