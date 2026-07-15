<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class InterviewOutcome extends Model
{
    protected $fillable = [
        'user_id', 'job_application_id', 'interview_session_id', 'interview_date',
        'interview_format', 'stage', 'result', 'questions_asked', 'surprise_topics',
        'useful_story_ids', 'recruiter_feedback', 'reflection', 'confidence_before',
        'confidence_after', 'allow_anonymous_learning',
    ];

    protected $casts = [
        'interview_date' => 'date',
        'questions_asked' => 'array',
        'surprise_topics' => 'array',
        'useful_story_ids' => 'array',
        'confidence_before' => 'integer',
        'confidence_after' => 'integer',
        'allow_anonymous_learning' => 'boolean',
    ];

    public static function tableExists(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasTable('interview_outcomes');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (! self::tableExists()) {
            abort(404);
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function interviewSession()
    {
        return $this->belongsTo(InterviewSession::class);
    }
}
