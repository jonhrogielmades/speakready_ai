<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'question_index',
        'question_text',
        'answer_text',
        'is_skipped',
        'response_mode',
        'elapsed_seconds',
        'wpm',
        'voice_duration',
        'filler_words_count',
        'pause_count',
        'confidence_score',
        'eye_contact_score',
        'posture_score',
        'goal_score',
        'clarity_score',
        'relevance_score',
        'grammar_score',
        'professionalism_score',
        'star_method_score',
        'goal_breakdown',
        'goal_notes',
    ];

    protected $casts = [
        'is_skipped' => 'boolean',
        'goal_breakdown' => 'array',
        'question_index' => 'integer',
        'elapsed_seconds' => 'integer',
        'wpm' => 'integer',
        'voice_duration' => 'integer',
        'filler_words_count' => 'integer',
        'pause_count' => 'integer',
        'confidence_score' => 'integer',
        'eye_contact_score' => 'integer',
        'posture_score' => 'integer',
        'goal_score' => 'integer',
        'clarity_score' => 'integer',
        'relevance_score' => 'integer',
        'grammar_score' => 'integer',
        'professionalism_score' => 'integer',
        'star_method_score' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }
}
