<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoiceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'speaking_pace',
        'clarity_score',
        'confidence_score',
        'filler_words',
        'category',
        'prompt',
        'transcript',
        'ai_feedback_strengths',
        'ai_feedback_weaknesses',
        'ai_improved_answer',
        'duration_seconds',
        'wpm'
    ];
}
