<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArenaLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_number',
        'title',
        'description',
        'mission_text',
        'target_position',
        'difficulty',
        'required_score',
        'xp_reward',
        'energy_cost',
    ];

    public function progress()
    {
        return $this->hasMany(ArenaProgress::class);
    }
}
