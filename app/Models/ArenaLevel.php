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
        'ai_persona',
        'ai_custom_prompt',
        'time_limit_seconds',
        'banned_words',
        'target_tone',
        'custom_badge_name',
        'skill_xp_type',
        'skill_xp_amount',
        'prerequisite_level_id',
        'is_hidden'
    ];

    public function progress()
    {
        return $this->hasMany(ArenaProgress::class);
    }

    public function learningModules()
    {
        return $this->belongsToMany(LearningModule::class, 'learning_module_arena_level');
    }

    public function prerequisiteLevel()
    {
        return $this->belongsTo(ArenaLevel::class, 'prerequisite_level_id');
    }
}
