<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewAnswer extends Model
{
    protected $fillable = [
        'interview_session_id',
        'retry_of_answer_id',
        'attempt_number',
        'question_id',
        'answer_text',
        'transcript_timeline',
        'response_mode',
        'ai_feedback',
        'better_sample_answer',
        'follow_up_question',
        'score',
        'is_skipped',
        'timed_out',
        'elapsed_seconds',
        'voice_duration',
        'wpm',
        'filler_words_count',
        'pause_count',
        'eye_contact_score',
        'posture_score',
        'clarity_score',
        'relevance_score',
        'confidence_score',
        'grammar_score',
        'audit_status',
        'flagged_reason',
        'star_analysis',
        'recommendation_text',
    ];

    protected $casts = [
        'star_analysis' => 'array',
        'transcript_timeline' => 'array',
        'is_skipped' => 'boolean',
        'timed_out' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function interviewSession()
    {
        return $this->belongsTo(InterviewSession::class);
    }

    public function originalAnswer()
    {
        return $this->belongsTo(self::class, 'retry_of_answer_id');
    }

    public function retryAttempts()
    {
        return $this->hasMany(self::class, 'retry_of_answer_id')->orderBy('attempt_number');
    }

    public function auditLogs()
    {
        return $this->hasMany(FeedbackAuditLog::class);
    }

    public function complaints()
    {
        return $this->hasMany(FeedbackComplaint::class);
    }

    public function scopeFlagged($query)
    {
        return $query->where('audit_status', 'flagged');
    }

    public function scopePending($query)
    {
        return $query->where('audit_status', 'under_review');
    }
}
