<?php

namespace App\Support;

use App\Models\LearningModule;

class ModuleLessonContent
{
    public static function hasContent(?string $content, ?string $videoUrl): bool
    {
        return ModuleVideoUrl::safeExternal($videoUrl) !== null || trim(strip_tags(html_entity_decode((string) $content))) !== '';
    }

    public static function hasLesson(LearningModule $module): bool
    {
        return $module->chapters->contains(
            fn ($chapter): bool => self::hasContent($chapter->content, $chapter->video_url)
        );
    }
}
