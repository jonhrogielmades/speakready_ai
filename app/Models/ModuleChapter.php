<?php

namespace App\Models;

use App\Support\ModuleChapterHtml;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleChapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_module_id',
        'title',
        'content',
        'video_url',
        'video_duration',
        'order',
    ];

    public function learningModule()
    {
        return $this->belongsTo(LearningModule::class);
    }

    protected function content(): Attribute
    {
        return Attribute::make(
            set: fn (mixed $value): string => ModuleChapterHtml::sanitize($value),
        );
    }
}
