<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewPack extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'company',
        'role_family',
        'difficulty',
        'interview_focus',
        'company_persona',
        'question_types',
        'sample_questions',
        'description',
        'pressure_mode',
        'status',
    ];

    protected $casts = [
        'question_types' => 'array',
        'sample_questions' => 'array',
        'pressure_mode' => 'boolean',
    ];

    public function sessions()
    {
        return $this->hasMany(InterviewSession::class);
    }
}
