<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSessionProviderPreview extends Model
{
    protected $fillable = [
        'interview_session_id',
        'provider_key',
        'input_fingerprint',
        'session_feedback',
    ];

    protected $casts = [
        'session_feedback' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }
}
