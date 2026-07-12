<?php

namespace App\Services;

final class CsvExportService
{
    public static function writeRow($stream, array $values): void
    {
        fputcsv($stream, array_map([self::class, 'safeCell'], $values));
    }

    public static function safeCell($value): string
    {
        $value = $value === null ? '' : (string) $value;

        if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }
}
