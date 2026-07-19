<?php

namespace App\Services;

final class TranscriptService
{
    private const DUPLICATE_SAFE_WORDS = [
        'i', "i'm", 'the', 'a', 'an', 'and', 'to', 'of', 'for', 'in', 'on', 'it', 'is', 'was',
        'were', 'am', 'are', 'my', 'we', 'you', 'that', 'this', 'with', 'um', 'uh', 'like',
    ];

    public static function clean(?string $transcript): string
    {
        $text = trim((string) $transcript);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return self::collapseAdjacentDuplicates($text);
    }

    public static function wordCount(?string $transcript): int
    {
        preg_match_all('/\b[\pL\pN][\pL\pN\'\x{2019}-]*\b/u', (string) $transcript, $matches);

        return count($matches[0] ?? []);
    }

    public static function countFillerWords(?string $transcript): int
    {
        return array_sum(self::fillerWordBreakdown($transcript));
    }

    /**
     * Return transcript-matched filler candidates as canonical phrase => count.
     *
     * These are transcript matches, not claims about the speaker's intent. Some
     * phrases (for example, "like") can be meaningful in context, so callers
     * must label the result as transcript-detected coaching evidence.
     */
    public static function fillerWordBreakdown(?string $transcript): array
    {
        preg_match_all(
            '/\b(?:you\s+know|i\s+mean|sort\s+of|kind\s+of|um+|uh+|erm+|hmm+|like|actually|basically|literally)\b/iu',
            (string) $transcript,
            $matches
        );

        $breakdown = [];
        foreach (($matches[0] ?? []) as $match) {
            $canonical = self::canonicalFillerWord((string) $match);
            if ($canonical === null) {
                continue;
            }

            $breakdown[$canonical] = ($breakdown[$canonical] ?? 0) + 1;
        }

        arsort($breakdown);

        return $breakdown;
    }

    public static function canonicalFillerWord(?string $word): ?string
    {
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower(trim((string) $word), 'UTF-8')
            : strtolower(trim((string) $word));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return match (true) {
            preg_match('/^um+$/u', $normalized) === 1 => 'um',
            preg_match('/^uh+$/u', $normalized) === 1 => 'uh',
            preg_match('/^erm+$/u', $normalized) === 1 => 'erm',
            preg_match('/^hmm+$/u', $normalized) === 1 => 'hmm',
            in_array($normalized, [
                'you know', 'i mean', 'sort of', 'kind of', 'like', 'actually', 'basically', 'literally',
            ], true) => $normalized,
            default => null,
        };
    }

    private static function collapseAdjacentDuplicates(string $text): string
    {
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! $words || count($words) < 2) {
            return trim($text);
        }

        $index = 0;
        while ($index < count($words)) {
            $collapsed = false;
            $maxWindow = min(12, intdiv(count($words) - $index, 2));

            for ($size = $maxWindow; $size >= 1; $size--) {
                $first = self::phraseKey(array_slice($words, $index, $size));
                $second = self::phraseKey(array_slice($words, $index + $size, $size));

                if ($first !== '' && $first === $second && self::shouldCollapseDuplicate($size, $first)) {
                    array_splice($words, $index + $size, $size);
                    $index = max(0, $index - $size);
                    $collapsed = true;
                    break;
                }
            }

            if (! $collapsed) {
                $index++;
            }
        }

        return trim(implode(' ', $words));
    }

    private static function phraseKey(array $words): string
    {
        $normalized = array_map(static function ($word): string {
            $word = function_exists('mb_strtolower')
                ? mb_strtolower((string) $word, 'UTF-8')
                : strtolower((string) $word);

            return preg_replace("/[^\p{L}\p{N}'\x{2019}]+/u", '', $word) ?? '';
        }, $words);

        return trim(implode(' ', array_filter($normalized, fn ($word) => $word !== '')));
    }

    private static function shouldCollapseDuplicate(int $wordCount, string $phraseKey): bool
    {
        // Repeated hesitation sounds and discourse markers can be genuine
        // delivery evidence. Do not erase them while cleaning duplicated
        // browser-recognition segments.
        if (self::isFillerOnlyPhrase($phraseKey)) {
            return false;
        }

        if ($wordCount >= 2) {
            return true;
        }

        $characterCount = function_exists('mb_strlen') ? mb_strlen($phraseKey, 'UTF-8') : strlen($phraseKey);

        return $characterCount > 2 || in_array($phraseKey, self::DUPLICATE_SAFE_WORDS, true);
    }

    private static function isFillerOnlyPhrase(string $phrase): bool
    {
        return preg_match(
            '/^(?:(?:you know|i mean|sort of|kind of|um+|uh+|erm+|hmm+|like|actually|basically|literally)(?:\s+|$))+$/iu',
            trim($phrase)
        ) === 1;
    }
}
