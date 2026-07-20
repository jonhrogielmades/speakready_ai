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

    public function getGuidanceChecklistAttribute(): array
    {
        $criteria = $this->parsed_success_criteria;

        if ($criteria !== []) {
            return $criteria;
        }

        return self::fallbackSuccessCriteria(
            (string) ($this->skill_focus ?? ''),
            (string) ($this->title ?? ''),
            (string) ($this->difficulty ?? '')
        );
    }

    public function getGuidanceChecklistTextAttribute(): string
    {
        $lines = [];
        foreach ($this->guidance_checklist as $index => $criterion) {
            $lines[] = ($index + 1).'. '.$criterion;
        }

        return implode("\n", $lines);
    }

    public static function fallbackSuccessCriteria(string $skillFocus = '', string $title = '', string $difficulty = ''): array
    {
        $context = strtolower($skillFocus.' '.$title.' '.$difficulty);

        if (str_contains($context, 'star') || str_contains($context, 'behavior') || str_contains($context, 'conflict')) {
            return [
                'Set the situation briefly.',
                'Explain your responsibility or goal.',
                'Describe the specific action you took.',
                'End with a result, impact, or lesson.',
            ];
        }

        if (str_contains($context, 'professional') || str_contains($context, 'weakness') || str_contains($context, 'curveball') || str_contains($context, 'hr')) {
            return [
                'Answer the question directly and honestly.',
                'Keep the tone respectful and accountable.',
                'Explain what you learned or changed.',
                'Connect the answer back to readiness for the role.',
            ];
        }

        if (str_contains($context, 'clarity') || str_contains($context, 'about yourself') || str_contains($context, 'introduction')) {
            return [
                'Open with your current role, course, training, or background.',
                'Mention one or two strengths relevant to the opportunity.',
                'Connect your experience to the role or panel question.',
                'Keep the answer focused, respectful, and professional.',
            ];
        }

        if (str_contains($context, 'readiness') || str_contains($context, 'final') || str_contains($context, 'mock')) {
            return [
                'Answer each question directly.',
                'Use specific evidence from school, work, internship, freelance, or project experience.',
                'Include a result, lesson, or next step when relevant.',
                'Keep pacing steady and stay professional from start to finish.',
            ];
        }

        return [
            'Answer the interview question directly.',
            'Use one concrete example or proof point.',
            'Explain your action or decision clearly.',
            'Include a result, lesson, or next step.',
            'Keep the tone professional and appropriate for Philippine interviews.',
        ];
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
