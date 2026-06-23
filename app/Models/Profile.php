<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = ['user_id', 'readiness_score', 'total_sessions', 'experience_points', 'current_streak', 'badges_earned'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
