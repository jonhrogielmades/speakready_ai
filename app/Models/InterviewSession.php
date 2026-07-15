<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    protected $fillable = [
        'user_id',
        'job_application_id',
        'interview_pack_id',
        'category_id',
        'difficulty',
        'target_position',
        'resume_text',
        'job_description',
        'num_questions',
        'coach_focus_mode',
        'response_mode',
        'interview_focus',
        'company_persona',
        'interviewer_strictness',
        'time_limit',
        'question_types',
        'ai_assistance_level',
        'live_feedback_mode',
        'assessment_mode',
        'interview_format',
        'accommodation_profile',
        'score_eligible',
        'pressure_mode',
        'status',
        'notes',
        'duration_seconds',
        'current_question_index',
        'session_state',
        'action_plan',
        'is_archived',
        'flag_reason',
        'share_token',
        'share_expires_at',
        'share_password_hash',
        'share_permissions',
        'share_hide_sensitive',
        'is_public',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'is_public' => 'boolean',
        'pressure_mode' => 'boolean',
        'duration_seconds' => 'integer',
        'current_question_index' => 'integer',
        'num_questions' => 'integer',
        'time_limit' => 'integer',
        'action_plan' => 'array',
        'accommodation_profile' => 'array',
        'score_eligible' => 'boolean',
        'share_expires_at' => 'datetime',
        'share_permissions' => 'array',
        'share_hide_sensitive' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function interviewPack()
    {
        return $this->belongsTo(InterviewPack::class);
    }

    public function answers()
    {
        return $this->hasMany(InterviewAnswer::class);
    }

    public function score()
    {
        return $this->hasOne(Score::class);
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class);
    }

    public function mentorReviewComments()
    {
        return $this->hasMany(MentorReviewComment::class);
    }

    public function outcomes()
    {
        return $this->hasMany(InterviewOutcome::class);
    }

    public function shareIsActive(): bool
    {
        return $this->is_public && (!$this->share_expires_at || $this->share_expires_at->isFuture());
    }
}
