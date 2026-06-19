<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleQuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_quiz_id',
        'type',
        'question_text',
        'options',
        'correct_answer',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(ModuleQuiz::class, 'module_quiz_id');
    }
}
