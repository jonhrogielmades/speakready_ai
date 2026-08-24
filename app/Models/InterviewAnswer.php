<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class InterviewAnswer extends Model
{
    protected $fillable = [
        'interview_session_id',
        'retry_of_answer_id',
        'attempt_number',
        'question_id',
        'answer_text',
        'delivery_transcript',
        'transcript_timeline',
        'paste_event_count',
        'pasted_character_count',
        'ai_generated_likelihood',
        'answer_integrity_flags',
        'observation_data',
        'pronunciation_analysis',
        'pronunciation_score',
        'coaching_feedback',
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
        'delivery_stability_score',
        'self_reported_confidence',
        'scoring_confidence',
        'grammar_score',
        'audit_status',
        'flagged_reason',
        'star_analysis',
        'recommendation_text',
        'evidence_map',
        'rubric_level',
        'improved_answer_source',
    ];

    protected $casts = [
        'star_analysis' => 'array',
        'transcript_timeline' => 'array',
        'answer_integrity_flags' => 'array',
        'observation_data' => 'array',
        'pronunciation_analysis' => 'array',
        'coaching_feedback' => 'array',
        'is_skipped' => 'boolean',
        'timed_out' => 'boolean',
        'evidence_map' => 'array',
    ];

    public static function hasColumn(string $column): bool
    {
        static $columns = null;

        $columns ??= Schema::hasTable('interview_answers')
            ? array_flip(Schema::getColumnListing('interview_answers'))
            : [];

        return isset($columns[$column]);
    }

    public function setAttribute($key, $value)
    {
        if ($this->isFillable($key) && ! self::hasColumn($key)) {
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

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
