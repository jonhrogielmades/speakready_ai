<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = [
        'user_id', 'readiness_score', 'total_sessions', 'experience_points', 
        'current_streak', 'badges_earned', 'leadership_xp', 'communication_xp',
        'technical_xp', 'problem_solving_xp', 'unlocked_perks'
    ];

    protected $casts = [
        'unlocked_perks' => 'array',
        'badges_earned' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hasPerk($perk)
    {
        if (empty($this->unlocked_perks)) {
            return false;
        }
        return in_array($perk, $this->unlocked_perks);
    }
}
