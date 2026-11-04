<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read \Illuminate\Database\Eloquent\Collection<int, ModuleQuizQuestion> $questions */
class ModuleQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_module_id',
        'title',
        'passing_score',
    ];

    public function learningModule()
    {
        return $this->belongsTo(LearningModule::class);
    }

    /** @return HasMany<ModuleQuizQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(ModuleQuizQuestion::class)->orderBy('id');
    }
}
