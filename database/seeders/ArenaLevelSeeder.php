<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ArenaLevel;

class ArenaLevelSeeder extends Seeder
{
    public function run()
    {
        $levels = [
            [
                'level_number' => 1,
                'title' => 'The Basics: "Tell Me About Yourself"',
                'description' => 'You mastered the perfect elevator pitch. Your pacing and structure were excellent!',
                'mission_text' => 'Answer the prompt: "Tell me about yourself." Focus on brevity and impact.',
                'target_position' => 'General',
                'difficulty' => 'beginner',
                'required_score' => 80,
                'xp_reward' => 500,
                'energy_cost' => 1,
            ],
            [
                'level_number' => 2,
                'title' => 'Behavioral Mastery: STAR Method',
                'description' => 'Learn how to structure your behavioral interview answers effectively using Situation, Task, Action, and Result.',
                'mission_text' => 'Answer the prompt: "Tell me about a time you had to work with a difficult team member." You must score at least 80% on clarity and STAR structure to unlock Level 3.',
                'target_position' => 'General',
                'difficulty' => 'intermediate',
                'required_score' => 80,
                'xp_reward' => 1000,
                'energy_cost' => 1,
            ],
            [
                'level_number' => 3,
                'title' => 'The Curveballs',
                'description' => 'Master tricky questions like "What is your biggest weakness?" and handle high-pressure scenarios.',
                'mission_text' => 'Answer the prompt: "What is your biggest weakness?"',
                'target_position' => 'General',
                'difficulty' => 'hard',
                'required_score' => 85,
                'xp_reward' => 1500,
                'energy_cost' => 1,
            ],
            [
                'level_number' => 4,
                'title' => 'Final Boss: 15-Min Mock Interview',
                'description' => 'Put everything you\'ve learned to the test in a full, simulated interview environment.',
                'mission_text' => 'A comprehensive 5-question rapid-fire round.',
                'target_position' => 'General',
                'difficulty' => 'expert',
                'required_score' => 90,
                'xp_reward' => 3000,
                'energy_cost' => 2,
            ]
        ];

        foreach ($levels as $level) {
            ArenaLevel::updateOrCreate(
                ['level_number' => $level['level_number']],
                $level
            );
        }
    }
}
