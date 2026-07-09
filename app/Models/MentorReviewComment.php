<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MentorReviewComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_session_id',
        'reviewer_name',
        'reviewer_email',
        'rating',
        'comment',
        'visibility',
        'ip_address',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function interviewSession()
    {
        return $this->belongsTo(InterviewSession::class);
    }
}
