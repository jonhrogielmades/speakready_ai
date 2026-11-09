<?php

namespace App\Services;

/** Conservative text signals, not verification that a candidate's claims are true. */
final class AnswerEvidenceSignals
{
    private const ACTION = '(?:lead|led|own|owned|build|built|create|created|resolve|resolved|solve|solved|fix|fixed|improve|improved|reduce|reduced|increase|increased|deliver|delivered|design|designed|implement|implemented|organize|organized|manage|managed|test|tested|analyze|analyzed|coordinate|coordinated|decide|decided|handle|handled|support|supported|communicate|communicated|verify|verified|check|checked|plan|planned|inspect|inspected|diagnose|diagnosed|review|reviewed|prioritize|prioritized|explain|explained|validate|validated|measure|measured|compare|compared|document|documented|escalate|escalated|write|wrote|prepare|prepared|train|trained|assist|assisted|propose|proposed|research|researched|configure|configured|deploy|deployed|investigate|investigated|monitor|monitored|report|reported|present|presented|negotiate|negotiated|mentor|mentored|facilitate|facilitated|maintain|maintained|migrate|migrated|automate|automated|optimize|optimized|launch|launched|process|processed|schedule|scheduled|delegate|delegated|select|selected|evaluate|evaluated|gather|gathered|contact|contacted|collaborate|collaborated|update|updated|identify|identified|recommend|recommended|listen|listened|ask|asked|share|shared)';

    private const RESULT = '(?:achieved|improved|reduced|increased|delivered|received|thanked|saved|resolved|completed|finished|passed|learned|learnt|met|exceeded|dropped|rose|fell|grew|recovered|restored|prevented|avoided|failed|missed|remained|stayed|became)';

    public static function analyze(string $answer, bool $pastEvent = false): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+|\r?\n/u', trim($answer), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $personal = false;
        $team = false;
        $result = false;
        $metric = false;
        $actionCount = 0;
        $excerpts = [];
        $resultExcerpts = [];

        foreach ($sentences as $sentence) {
            $text = str_replace(['’', '‘'], "'", $sentence);
            $personalCount = self::actionCount($text, 'I', $pastEvent);
            $teamCount = self::actionCount($text, 'we', $pastEvent);
            $personalHere = $personalCount > 0;
            $teamHere = $teamCount > 0;
            $actionCount += $personalCount + $teamCount;
            $personal = $personal || $personalHere;
            $team = $team || $teamHere;
            if ($personalHere || $teamHere) {
                $excerpts[] = trim($sentence);
            }

            // A date, team size, deadline, or the word "result" alone is not an outcome.
            foreach (preg_split('/[;]|\bbut\b/iu', $text) ?: [] as $clause) {
                if (self::hasReportedResult($clause)) {
                    $result = true;
                    $resultExcerpts[] = trim($sentence);
                    $metric = $metric || preg_match('/\b\d+(?:[.,]\d+)?(?:\s*%|\b)|\b(?:percent|hours?|days?|minutes?|seconds?|pesos?)\b/iu', $clause) === 1;
                }
            }
        }

        return [
            'has_personal_action' => $personal,
            'has_team_action' => $team,
            'action_count' => $actionCount,
            'has_result' => $result,
            'has_metric' => $metric,
            'supporting_excerpts' => array_slice(array_values(array_unique($excerpts)), 0, 3),
            'result_excerpts' => array_values(array_unique($resultExcerpts)),
        ];
    }

    private static function actionCount(string $text, string $subject, bool $pastEvent): int
    {
        $modal = $pastEvent ? '' : '(?:(?:would|will|can|could|plan to|try to)\s+)?';
        $pattern = '/\b'.$subject.'\s+(?:(?:personally|also|then|first|carefully|directly)\s+)?(?:have\s+|had\s+)?'.$modal.self::ACTION.'\b/iu';
        if (! preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) {
            return 0;
        }
        $count = 0;
        foreach ($matches[0] as [$match, $offset]) {
            $prefix = substr($text, 0, $offset);
            // Do not count a conditional example as work the candidate performed.
            if ($pastEvent && preg_match('/\b(?:if|suppose|imagine)\b[^.!?;]*$/iu', $prefix)) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    private static function hasReportedResult(string $clause): bool
    {
        // Keep plans, intended benefits, unknown outcomes, and hypothetical results out.
        if (preg_match('/\b(?:would|will|could|might|may|should|hope|hoping|aim|aiming|intend|intended|plan to|planned to|want to|wanted to|expect to|expected to|if|suppose|imagine)\b|\b(?:goal|target)\s+(?:is|was|remains?)\s+to\b/iu', $clause)) {
            return false;
        }
        if (preg_match('/\b(?:do not|don\x27t|did not|didn\x27t|have not|haven\x27t|cannot|can\x27t)\s+(?:yet\s+)?(?:know|remember|recall|measure|measured|verify|verified)\b|\b(?:results?|outcomes?|impact)\s+(?:is|are|was|were|has been|have been|remains?|remained)\s+(?:(?:still|currently|yet)\s+)?(?:unknown|unclear|unavailable|pending|not\s+(?:yet\s+)?(?:known|clear|available|measured|verified|determined)|to be determined)\b/iu', $clause)) {
            return false;
        }

        return preg_match('/\b'.self::RESULT.'\b/iu', $clause) === 1
            || preg_match('/\b(?:as a result|this led to|which led to)\b[,\s]+\S+(?:\s+\S+){2,}/iu', $clause) === 1
            || preg_match('/\b(?:result|outcome|impact|lesson)\s+(?:was|is|has been)\s+(?!unknown\b|unclear\b|pending\b)\S+/iu', $clause) === 1
            || preg_match('/\b(?:did not|didn\x27t)\s+(?:improve|reduce|increase|resolve|finish|complete|recover|meet|work)\b/iu', $clause) === 1
            || preg_match('/\b(?:was|were|is|are)\s+(?:now\s+|still\s+)?(?:faster|slower|stable|working|successful|unsuccessful|unresolved|satisfied|happy|able to)\b/iu', $clause) === 1;
    }
}
