<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_level_id',
        'status',
        'difficulty',
        'target_position',
        'num_questions',
        'response_mode',
        'interview_focus',
        'company_persona',
        'time_limit',
        'questions',
        'accommodation_profile',
        'duration_seconds',
        'notes',
        'current_question_index',
        'session_state',
        'score',
        'required_score',
        'result_status',
        'goal_breakdown',
        'xp_earned',
        'energy_spent',
        'energy_remaining',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'questions' => 'array',
        'accommodation_profile' => 'array',
        'session_state' => 'array',
        'goal_breakdown' => 'array',
        'duration_seconds' => 'integer',
        'current_question_index' => 'integer',
        'score' => 'integer',
        'required_score' => 'integer',
        'xp_earned' => 'integer',
        'energy_spent' => 'integer',
        'energy_remaining' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function level()
    {
        return $this->belongsTo(GameLevel::class, 'game_level_id');
    }

    public function answers()
    {
        return $this->hasMany(GameAnswer::class);
    }
}
