<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    protected $fillable = [
        'user_id',
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
        'time_limit',
        'question_types',
        'ai_assistance_level',
        'status',
        'notes',
        'duration_seconds',
        'is_archived',
        'flag_reason',
        'share_token',
        'is_public',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'is_public' => 'boolean',
        'duration_seconds' => 'integer',
        'num_questions' => 'integer',
        'time_limit' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
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
}
