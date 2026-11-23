<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSessionLocalFeedbackPreview extends Model
{
    protected $fillable = [
        'interview_session_id',
        'input_fingerprint',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
