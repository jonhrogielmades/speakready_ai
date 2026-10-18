<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissionAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'generation_key',
        'goal',
        'mission_key',
        'mission_payload',
        'source',
        'provider',
        'status',
        'answer_text',
        'score',
        'result_status',
        'score_version',
        'scoring_breakdown',
        'feedback',
        'submitted_at',
    ];

    protected $casts = [
        'mission_payload' => 'array',
        'score' => 'integer',
        'scoring_breakdown' => 'array',
        'feedback' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
