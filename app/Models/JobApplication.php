<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'job_title',
        'status',
        'interview_stage',
        'interview_date',
        'source_url',
        'resume_text',
        'job_description',
        'competency_map',
        'match_score',
        'evidence_match_score',
        'matched_keywords',
        'missing_keywords',
        'evidence_matches',
        'evidence_gaps',
        'future_skills',
        'smart_plan',
        'notes',
    ];

    protected $casts = [
        'interview_date' => 'date',
        'match_score' => 'integer',
        'evidence_match_score' => 'integer',
        'matched_keywords' => 'array',
        'missing_keywords' => 'array',
        'evidence_matches' => 'array',
        'evidence_gaps' => 'array',
        'competency_map' => 'array',
        'future_skills' => 'array',
        'smart_plan' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(InterviewSession::class);
    }

    public function planItems()
    {
        return $this->hasMany(PracticePlanItem::class);
    }

    public function readinessProfile()
    {
        return $this->hasOne(ReadinessProfile::class);
    }

    public function outcomes()
    {
        return $this->hasMany(InterviewOutcome::class);
    }
}
