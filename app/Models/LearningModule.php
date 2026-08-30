<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningModule extends Model
{
    protected $fillable = [
        'title', 
        'description', 
        'type', 
        'career_path',
        'url',
        'category',
        'difficulty',
        'status',
        'views',
        'is_featured',
        'mapped_skills',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'mapped_skills' => 'array',
    ];

    public function chapters()
    {
        return $this->hasMany(ModuleChapter::class)->orderBy('order')->orderBy('id');
    }

    public function resources()
    {
        return $this->hasMany(ModuleResource::class)->orderBy('id');
    }

    public function quizzes()
    {
        return $this->hasMany(ModuleQuiz::class)->orderBy('id');
    }

    public function activities()
    {
        return $this->hasMany(ModulePracticeActivity::class)->orderBy('id');
    }

    public function gameLevels()
    {
        return $this->belongsToMany(GameLevel::class, 'learning_module_game_level');
    }
}
