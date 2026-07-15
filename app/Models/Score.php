<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $fillable = [
        'interview_session_id',
        'score_version',
        'assessment_mode',
        'clarity_score',
        'relevance_score',
        'grammar_score',
        'professionalism_score',
        'confidence_score',
        'delivery_stability_score',
        'overall_readiness_score',
        'readiness_band',
        'scoring_confidence',
        'body_language_score',
        'ats_match_score',
        'job_evidence_match_score',
        'star_method_score',
        'evidence_map',
        'rubric',
        'body_language_included',
    ];

    protected $casts = [
        'score_version' => 'integer',
        'scoring_confidence' => 'integer',
        'evidence_map' => 'array',
        'rubric' => 'array',
        'body_language_included' => 'boolean',
    ];

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }
}
