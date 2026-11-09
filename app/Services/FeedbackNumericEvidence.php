<?php

namespace App\Services;

/** Checks stated quantities against the answer, not the truth of the answer itself. */
final class FeedbackNumericEvidence
{
    public static function hasUnsupportedQuantity(string $feedback, string $answer): bool
    {
        $feedback = self::normalize($feedback);
        $answer = self::normalize($answer);

        // Matching a number elsewhere in an answer does not support changing its
        // unit: "5 days, 10 people" must never validate "10 days, 5 people".
        $answerNumbers = self::numbers($answer);
        foreach (self::numbers($feedback) as $number) {
            if (! in_array($number, $answerNumbers, true)) {
                return true;
            }
        }

        $answerQuantities = self::quantities($answer);
        foreach (self::quantities($feedback) as $quantity) {
            if (! in_array($quantity, $answerQuantities, true)) {
                return true;
            }
        }

        return false;
    }

    private static function normalize(string $text): string
    {
        $text = mb_strtolower(str_replace(['’', '−'], ["'", '-'], $text));
        $text = preg_replace('/(?<=\d),(?=\d{3}(?:\D|$))/u', '', $text) ?? $text;
        $text = preg_replace('/\b(?:percent|per cent)\b/u', '%', $text) ?? $text;
        $text = preg_replace('/(?:₱|\bpesos?\b)/u', 'php', $text) ?? $text;

        return preg_replace('/\s+/u', ' ', trim($text)) ?? $text;
    }

    private static function numbers(string $text): array
    {
        preg_match_all('/(?<![\pL\pN])[-+]?\d+(?:\.\d+)?/u', $text, $matches);

        return array_values(array_unique(array_merge(
            array_map(self::number(...), $matches[0]),
            array_map(static fn (string $quantity): string => explode('|', $quantity, 2)[0], self::quantities($text)),
        )));
    }

    private static function quantities(string $text): array
    {
        $word = '(?:zero|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|twenty|thirty|forty|fifty|sixty|seventy|eighty|ninety|hundred|thousand|million)';
        $number = '(?:[-+]?\d+(?:\.\d+)?|'.$word.'(?:[ -]+(?:and[ -]+)?'.$word.')*)';
        $unit = '(?:%|percentage points?|milliseconds?|msecs?|ms|seconds?|secs?|s|minutes?|mins?|hours?|hrs?|h|days?|weeks?|months?|years?|people|persons?|employees?|team members?|members?|customers?|clients?|users?|projects?|teams?|php|usd|dollars?)';
        preg_match_all('/(?<![\pL\pN])('.$number.')\s*(?:-\s*)?('.$unit.')(?![\pL\pN])/u', $text, $matches, PREG_SET_ORDER);
        $quantities = [];
        foreach ($matches as $match) {
            $quantities[] = self::number($match[1]).'|'.self::unit($match[2]);
        }
        preg_match_all('/(?:\b(php|usd)|([$]))\s*('.$number.')(?![\pL\pN])/u', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $quantities[] = self::number($match[3]).'|'.self::unit($match[1] !== '' ? $match[1] : $match[2]);
        }

        return array_values(array_unique($quantities));
    }

    private static function number(string $number): string
    {
        if (is_numeric($number)) {
            return (string) (float) $number;
        }
        $words = ['zero' => 0, 'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6,
            'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10, 'eleven' => 11, 'twelve' => 12,
            'thirteen' => 13, 'fourteen' => 14, 'fifteen' => 15, 'sixteen' => 16, 'seventeen' => 17,
            'eighteen' => 18, 'nineteen' => 19, 'twenty' => 20, 'thirty' => 30, 'forty' => 40,
            'fifty' => 50, 'sixty' => 60, 'seventy' => 70, 'eighty' => 80, 'ninety' => 90];
        $total = 0;
        $part = 0;
        foreach (preg_split('/[ -]+/', $number) ?: [] as $word) {
            if ($word === 'hundred') {
                $part = max(1, $part) * 100;
            } elseif ($word === 'thousand' || $word === 'million') {
                $total += max(1, $part) * ($word === 'thousand' ? 1000 : 1000000);
                $part = 0;
            } else {
                $part += $words[$word] ?? 0;
            }
        }

        return (string) ($total + $part);
    }

    private static function unit(string $unit): string
    {
        return match ($unit) {
            'people', 'person', 'persons' => 'person',
            'team member', 'team members' => 'member',
            'ms', 'msec', 'msecs' => 'millisecond',
            's', 'sec', 'secs' => 'second',
            'min', 'mins' => 'minute',
            'h', 'hr', 'hrs' => 'hour',
            'usd', '$', 'dollar', 'dollars' => 'usd',
            default => rtrim($unit, 's'),
        };
    }
}
