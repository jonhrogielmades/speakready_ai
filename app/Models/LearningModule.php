<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ModuleChapter> $chapters
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ModuleResource> $resources
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ModuleQuiz> $quizzes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ModulePracticeActivity> $activities
 */
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

    /** @return HasMany<ModuleChapter, $this> */
    public function chapters(): HasMany
    {
        return $this->hasMany(ModuleChapter::class)->orderBy('order')->orderBy('id');
    }

    /** @return HasMany<ModuleResource, $this> */
    public function resources(): HasMany
    {
        return $this->hasMany(ModuleResource::class)->orderBy('id');
    }

    /** @return HasMany<ModuleQuiz, $this> */
    public function quizzes(): HasMany
    {
        return $this->hasMany(ModuleQuiz::class)->orderBy('id');
    }

    /** @return HasMany<ModulePracticeActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(ModulePracticeActivity::class)->orderBy('id');
    }

    public function gameLevels()
    {
        return $this->belongsToMany(GameLevel::class, 'learning_module_game_level');
    }
}
