<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    protected $guarded = [];

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
