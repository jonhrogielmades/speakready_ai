<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'category_id',
        'question_text',
        'difficulty',
        'interview_session_id',
        'type',
        'status',
        'expected_guide',
        'mapped_skills',
        'source_name',
        'source_url',
        'source_type',
    ];

    protected $casts = [
        'mapped_skills' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function answers()
    {
        return $this->hasMany(InterviewAnswer::class);
    }

}
