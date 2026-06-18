<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = ['interview_session_id', 'strengths', 'weaknesses', 'improvement_suggestions'];
}
