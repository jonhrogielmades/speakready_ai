<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $fillable = ['interview_session_id', 'clarity_score', 'relevance_score', 'grammar_score', 'professionalism_score', 'overall_readiness_score', 'ats_match_score', 'star_method_score'];

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'interview_session_id');
    }
}
