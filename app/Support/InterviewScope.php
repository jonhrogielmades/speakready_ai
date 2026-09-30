<?php

namespace App\Support;

class InterviewScope
{
    public const DEFAULT_CATEGORY = 'Job Interview';
    public const DEFAULT_LABEL = 'Job Interviews';
    public const DEFAULT_FOCUS = 'Job Interview';

    public static function isSupportedCoreCategory(mixed $category): bool
    {
        return self::isSupportedCategoryTitle((string) ($category->title ?? ''));
    }

    public static function isSupportedCategoryTitle(?string $categoryTitle): bool
    {
        $title = self::normalizedTitle($categoryTitle);

        if ($title === '' || self::isExcludedCategoryTitle($title)) {
            return false;
        }

        return str_contains($title, 'job interview')
            || str_contains($title, 'general job');
    }

    public static function categoryLabel(?string $categoryTitle = null): string
    {
        return self::DEFAULT_LABEL;
    }

    public static function focus(?string $categoryTitle = null): string
    {
        return self::DEFAULT_FOCUS;
    }

    public static function sourceSummaryFallback(): string
    {
        return 'job interview sources';
    }

    private static function normalizedTitle(?string $categoryTitle): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', str_replace('/', ' / ', (string) $categoryTitle)) ?? ''));
    }

    private static function isExcludedCategoryTitle(string $title): bool
    {
        return str_contains($title, 'bpo')
            || str_contains($title, 'customer')
            || str_contains($title, 'programming')
            || str_contains($title, 'technical')
            || str_contains($title, 'scholar')
            || str_contains($title, 'school admission')
            || str_contains($title, 'college admission')
            || str_contains($title, 'admission interview')
            || preg_match('/\bit\b/', $title);
    }
}
