<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
        'interview_session_id',
        'strengths',
        'weaknesses',
        'improvement_suggestions',
        'coaching_summary',
    ];

    protected $casts = [
        'coaching_summary' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }
}
