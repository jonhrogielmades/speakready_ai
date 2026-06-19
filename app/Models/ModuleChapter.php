<?php

namespace App\Models;

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
}
