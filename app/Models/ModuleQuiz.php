<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function questions()
    {
        return $this->hasMany(ModuleQuizQuestion::class);
    }
}
