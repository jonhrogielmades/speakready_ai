<?php

namespace App\Support;

use App\Models\LearningModule;
use App\Models\LearningProgress;
use Illuminate\Support\Str;

class ModuleChapterGate
{
    public const SECONDS_PER_CHAPTER = 300;

    public static function enabled(LearningModule $module): bool
    {
        return collect($module->mapped_skills ?? [])
            ->contains(fn ($skill): bool => is_string($skill) && Str::startsWith($skill, 'ai_module_spec:'));
    }

    public static function requiredSeconds(LearningModule $module): int
    {
        return max(0, $module->chapters->count() - 1) * self::SECONDS_PER_CHAPTER;
    }

    public static function unlockedCount(LearningModule $module, ?LearningProgress $progress): int
    {
        $chapterCount = $module->chapters->count();
        if (! self::enabled($module) || ($progress !== null && (int) $progress->progress_percentage >= 100)) {
            return $chapterCount;
        }

        $seconds = $progress !== null ? (int) $progress->chapter_gate_seconds : 0;

        return min($chapterCount, 1 + intdiv(max(0, $seconds), self::SECONDS_PER_CHAPTER));
    }
}
