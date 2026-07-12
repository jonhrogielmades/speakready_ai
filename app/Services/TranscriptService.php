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
        preg_match_all('/\b(?:you\s+know|i\s+mean|um+|uh+|erm+|hmm+|like|actually|basically|literally)\b/iu', (string) $transcript, $matches);

        return count($matches[0] ?? []);
    }

    private static function collapseAdjacentDuplicates(string $text): string
    {
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$words || count($words) < 2) {
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

            if (!$collapsed) {
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
        if ($wordCount >= 2) {
            return true;
        }

        $characterCount = function_exists('mb_strlen') ? mb_strlen($phraseKey, 'UTF-8') : strlen($phraseKey);

        return $characterCount > 2 || in_array($phraseKey, self::DUPLICATE_SAFE_WORDS, true);
    }
}
