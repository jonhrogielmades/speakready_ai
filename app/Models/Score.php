<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

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

    public static function hasColumn(string $column): bool
    {
        static $columns = null;

        $columns ??= Schema::hasTable('scores')
            ? array_flip(Schema::getColumnListing('scores'))
            : [];

        return isset($columns[$column]);
    }

    public function scopeReadinessEligible($query)
    {
        if (Schema::hasColumn('interview_sessions', 'assessment_mode')) {
            return $query->whereHas('session', fn ($session) => $session->readinessEligible());
        }

        if (Schema::hasColumn('scores', 'assessment_mode')) {
            return $query->where(function ($inner) {
                $inner->where('assessment_mode', 'legacy');

                if (Schema::hasColumn('interview_sessions', 'score_eligible')) {
                    $inner->orWhereHas('session', fn ($session) => $session->where('score_eligible', true));
                }
            });
        }

        return $query;
    }

    public function setAttribute($key, $value)
    {
        if ($this->isFillable($key) && ! self::hasColumn($key)) {
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function getAssessmentModeAttribute($value): string
    {
        return $value ?: 'legacy';
    }

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }
}
