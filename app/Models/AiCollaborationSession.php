<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCollaborationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'role',
        'industry',
        'difficulty',
        'scenario_type',
        'scenario_brief',
        'source_material',
        'expected_output',
        'conversation_log',
        'final_recommendation',
        'candidate_reflection',
        'ai_feedback',
        'overall_score',
        'prompt_quality_score',
        'critical_thinking_score',
        'verification_score',
        'structure_score',
        'communication_score',
        'status',
        'provider',
        'completed_at',
    ];

    protected $casts = [
        'conversation_log' => 'array',
        'ai_feedback' => 'array',
        'overall_score' => 'integer',
        'prompt_quality_score' => 'integer',
        'critical_thinking_score' => 'integer',
        'verification_score' => 'integer',
        'structure_score' => 'integer',
        'communication_score' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
