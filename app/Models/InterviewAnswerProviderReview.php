<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewAnswerProviderReview extends Model
{
    protected $fillable = [
        'interview_session_id',
        'interview_answer_id',
        'status',
        'input_fingerprint',
        'provider_key',
        'processing_token',
        'attempts',
        'failure_code',
        'payload',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function answer()
    {
        return $this->belongsTo(InterviewAnswer::class, 'interview_answer_id');
    }

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }
}
