<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = ['category_id', 'question_text', 'difficulty', 'interview_session_id', 'type', 'status', 'expected_guide', 'mapped_skills'];

    protected $casts = [
        'mapped_skills' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

}
