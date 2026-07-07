<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewAnswer extends Model
{
    protected $fillable = [
        'interview_session_id',
        'question_id',
        'answer_text',
        'response_mode',
        'ai_feedback',
        'better_sample_answer',
        'follow_up_question',
        'score',
        'is_skipped',
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
        'is_skipped' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function interviewSession()
    {
        return $this->belongsTo(InterviewSession::class);
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
