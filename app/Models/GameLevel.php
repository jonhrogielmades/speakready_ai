<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'level_number',
        'title',
        'description',
        'mission_text',
        'target_position',
        'skill_focus',
        'learning_objective',
        'success_criteria',
        'retry_hint',
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
        return $this->hasMany(GameProgress::class);
    }

    public function learningModules()
    {
        return $this->belongsToMany(LearningModule::class, 'learning_module_game_level');
    }

    public function prerequisiteLevel()
    {
        return $this->belongsTo(GameLevel::class, 'prerequisite_level_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getParsedQuestionsAttribute()
    {
        if (! $this->mission_text) {
            return ["Please begin your response."];
        }

        $questions = $this->parseNumberedLines($this->mission_text);

        if (empty($questions)) {
            $questions = ["Please begin your response."];
        }

        return $questions;
    }

    public function getParsedSuccessCriteriaAttribute(): array
    {
        if (! $this->success_criteria) {
            return [];
        }

        return $this->parseNumberedLines($this->success_criteria);
    }

    private function parseNumberedLines(string $text): array
    {
        $normalizedText = str_replace(["\r\n", "\r", '\n'], "\n", $text);
        $normalizedText = preg_replace('/\s+(\d+[\.\)]|[-*])\s+/', "\n$1 ", $normalizedText);

        $lines = array_filter(array_map('trim', explode("\n", $normalizedText)));
        $questions = [];
        foreach ($lines as $line) {
            $cleanLine = trim(preg_replace('/^(\d+[\.\)]|[-*])\s*/', '', $line));
            if (!empty($cleanLine)) {
                $questions[] = $cleanLine;
            }
        }

        return $questions;
    }
}
