<?php

namespace App\Services;

use Illuminate\Support\Str;

class MissionScoringService
{
    public const VERSION = 'mission_rules_v1';

    /**
     * @param  array<string, mixed>  $mission
     * @return array{score: int, status: string, summary: string, word_count: int, feedback: array<int, string>, breakdown: array<string, array<string, int|bool>>, score_version: string}
     */
    public function score(array $mission, string $answer): array
    {
        $answer = trim($answer);
        $normalized = $this->normalize($answer);
        $words = $normalized === '' ? 0 : count(preg_split('/\s+/u', $normalized) ?: []);
        $feedback = [];

        $lengthPoints = $words >= 45 && $words <= 150 ? 24 : ($words > 0 ? 12 : 0);
        $lengthMet = $lengthPoints === 24;
        $feedback[] = $lengthMet
            ? 'Interview use: your answer length is practical for a spoken interview response.'
            : ($words < 45
                ? 'Interview use: add one concrete detail so the answer has enough proof.'
                : 'Interview use: tighten the answer so it stays clear under time pressure.');

        $evidenceMet = $this->hasAny($normalized, [
            'because', 'for example', 'example', 'when', 'during', 'project', 'internship', 'school', 'work', 'client',
        ]);
        $feedback[] = $evidenceMet
            ? 'Interview use: evidence is present, which makes the answer easier for an interviewer to trust.'
            : 'Interview use: add one specific example, project, class, client, or work situation.';

        $outcomeMet = $this->hasAny($normalized, [
            'result', 'improved', 'reduced', 'increased', 'learned', 'completed', 'solved', 'helped', 'successful',
        ]);
        $feedback[] = $outcomeMet
            ? 'Interview use: the answer includes an outcome or lesson, so it shows growth or impact.'
            : 'Interview use: close with a result, lesson, or next action.';

        $toneMet = $this->toneSignal((string) ($mission['intent'] ?? ''), $normalized);
        $intent = Str::lower(trim((string) ($mission['intent'] ?? 'appropriate')));
        $feedback[] = $toneMet
            ? "Interview use: {$intent} intention is showing in the wording."
            : "Interview use: adjust wording so it sounds more {$intent} without inventing facts.";

        $nextActionMet = $this->hasAny($normalized, [
            'i will', 'next', 'contribute', 'support', 'help', 'apply', 'continue', 'moving forward',
        ]);
        $feedback[] = $nextActionMet
            ? 'Interview use: the ending gives a forward direction the interviewer can remember.'
            : 'Interview use: end with what you will do, contribute, or improve next.';

        $breakdown = [
            'answer_length' => ['points' => $lengthPoints, 'maximum' => 24, 'met' => $lengthMet],
            'evidence' => ['points' => $evidenceMet ? 22 : 0, 'maximum' => 22, 'met' => $evidenceMet],
            'outcome' => ['points' => $outcomeMet ? 20 : 0, 'maximum' => 20, 'met' => $outcomeMet],
            'tone' => ['points' => $toneMet ? 20 : 0, 'maximum' => 20, 'met' => $toneMet],
            'next_action' => ['points' => $nextActionMet ? 14 : 0, 'maximum' => 14, 'met' => $nextActionMet],
        ];
        $score = collect($breakdown)->sum('points');
        $status = $score >= 80 ? 'Mission Ready' : ($score >= 60 ? 'Almost There' : 'Needs Practice');
        $title = trim((string) ($mission['title'] ?? 'Mission')) ?: 'Mission';

        return [
            'score' => $score,
            'status' => $status,
            'summary' => "{$title} checked {$words} words against interview-ready structure, evidence, result, tone, and next action. Saved to your account.",
            'word_count' => $words,
            'feedback' => $feedback,
            'breakdown' => $breakdown,
            'score_version' => self::VERSION,
        ];
    }

    private function normalize(string $value): string
    {
        $value = Str::lower($value);
        $value = preg_replace("/[^\p{L}\p{N}\s']/u", ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /** @param array<int, string> $terms */
    private function hasAny(string $text, array $terms): bool
    {
        foreach ($terms as $term) {
            $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($term, '/').'(?![\p{L}\p{N}])/u';
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }

    private function toneSignal(string $intent, string $normalized): bool
    {
        return match (Str::lower(trim($intent))) {
            'confident' => $this->hasAny($normalized, ['i can', 'i have', 'i handled', 'i led', 'i built', 'i improved', 'my experience']),
            'calm' => $this->hasAny($normalized, ['understand', 'appreciate', 'clarify', 'resolve', 'next step', 'will check', 'help']),
            'persuasive' => $this->hasAny($normalized, ['because', 'benefit', 'result', 'evidence', 'support', 'improve', 'reduce', 'increase']),
            'accountable' => $this->hasAny($normalized, ['learned', 'changed', 'improved', 'now', 'feedback', 'mistake', 'weakness']),
            default => $normalized !== '',
        };
    }
}
