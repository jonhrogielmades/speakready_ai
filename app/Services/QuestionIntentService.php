<?php

namespace App\Services;

use App\Models\Question;

final class QuestionIntentService
{
    public static function classify(Question|array|null $question): string
    {
        $text = mb_strtolower(self::text($question));
        $context = mb_strtolower(self::context($question));
        $type = mb_strtolower(self::type($question));

        // Exact question wording takes priority over broad guides or coarse
        // stored type labels.
        if (preg_match('/\btell me about yourself\b|\bwalk me through (?:your|the) background\b/i', $text)) {
            return 'self_introduction';
        }
        if (preg_match('/\bwhy should (?:we|an? .+ employer) hire you\b|\bwhy (?:are|would) you (?:be )?(?:a )?(?:good )?fit\b/i', $text)) {
            return 'role_fit';
        }
        if (preg_match('/\bwhy (?:do|did|would) you (?:want|choose|apply|join)\b|\bwhat interests you\b/i', $text)) {
            return 'motivation';
        }
        if (preg_match('/\b(?:salary|compensation|pay)\b.*\b(?:expect(?:ation|ations|ed|ing)?|range|desired|seeking)\b|\b(?:expect(?:ed|ing)?|desired|seeking)\b.*\b(?:salary|compensation|pay)\b/i', $text)) {
            return 'salary_expectation';
        }
        if (preg_match('/\bwhy did you leave\b|\bwhy are you leaving\b|\blooking for in your next\b/i', $text)) {
            return 'career_transition';
        }
        if (preg_match('/\bwhere do you see yourself\b|\bcareer (?:goal|path|direction)\b/i', $text)) {
            return 'career_goal';
        }
        if (preg_match('/\bwork setup\b|\bonsite\b.*\bhybrid\b|\bremote\b.*\bshifting\b/i', $text)) {
            return 'work_setup';
        }

        $strengthSource = $text !== '' ? $text : $context;
        $hasStrength = preg_match('/\bstrengths?\b|\bstrongest\b|\bkalakasan\b|\bkusog\b/iu', $strengthSource) === 1;
        $hasWeakness = preg_match('/\bweakness(?:es)?\b|\bdevelopment area\b|\barea (?:for|of) improvement\b|\bkahinaan\b|\bkahuyang\b/iu', $strengthSource) === 1;
        if ($hasStrength && $hasWeakness) {
            return 'strength_and_weakness';
        }
        if ($hasStrength) {
            return 'strength';
        }
        if ($hasWeakness) {
            return 'weakness';
        }

        if ($type === 'technical') {
            return 'technical';
        }

        // Cover common event nouns as well as explicit "time" or "situation"
        // wording, without misclassifying direct prompts such as "describe your
        // leadership style" as behavioral.
        $experiential = preg_match(
            '/\b(tell me about|describe|share|give (?:me )?an example|walk me through)\b.*\b(time|situation|experience|project|incident|challenge|mistake|conflict|case|handoff|outage|deadline|decision|failure|success|problem|issue|achievement|change|disagreement|pressure|setback)\b|\bexample of how you\b/i',
            $text
        ) === 1;
        $starGuide = preg_match('/\buse STAR\b|\bSTAR Method\b|\bsituation[, ]+task[, ]+action[, ]+(?:and )?result\b/i', $context) === 1;
        if ($experiential || $starGuide) {
            return 'behavioral';
        }
        if (preg_match('/\bhow would you\b|\bwhat would you do\b|\bimagine\b|\bsuppose\b/i', $text)) {
            return 'situational';
        }

        return 'direct_evidence';
    }

    public static function starApplicable(Question|array|null $question): bool
    {
        return self::classify($question) === 'behavioral';
    }

    public static function requiresPersonalAction(Question|array|null $question): bool
    {
        return self::starApplicable($question);
    }

    public static function requiresResult(Question|array|null $question): bool
    {
        if (self::starApplicable($question)) {
            return true;
        }

        return preg_match(
            '/\b(tell me about|describe|share|give me an example|walk me through)\b.*\b(time|situation|experience|project|case|incident|challenge|mistake)\b/i',
            self::text($question)
        ) === 1;
    }

    public static function context(Question|array|null $question): string
    {
        $mappedSkills = $question instanceof Question
            ? $question->mapped_skills
            : ($question['mapped_skills'] ?? []);
        if (is_string($mappedSkills)) {
            $decoded = json_decode($mappedSkills, true);
            $mappedSkills = is_array($decoded)
                ? $decoded
                : preg_split('/\s*,\s*/', $mappedSkills, -1, PREG_SPLIT_NO_EMPTY);
        }
        $mappedSkills = array_values(array_filter(array_map(
            fn ($skill): string => is_scalar($skill) ? trim((string) $skill) : '',
            is_array($mappedSkills) ? $mappedSkills : []
        )));

        return trim((string) preg_replace('/\s+/u', ' ', implode(' ', array_filter([
            self::text($question),
            self::expectedGuide($question),
            implode(' ', $mappedSkills),
        ]))));
    }

    public static function text(Question|array|null $question): string
    {
        return trim((string) ($question instanceof Question
            ? $question->question_text
            : ($question['question'] ?? $question['question_text'] ?? '')));
    }

    public static function expectedGuide(Question|array|null $question): string
    {
        return trim((string) ($question instanceof Question
            ? $question->expected_guide
            : ($question['expected_guide'] ?? $question['expected_answer_guide'] ?? '')));
    }

    public static function type(Question|array|null $question): string
    {
        return trim((string) ($question instanceof Question
            ? $question->type
            : ($question['question_type'] ?? $question['type'] ?? '')));
    }
}
