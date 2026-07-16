<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GameLevel;

class GameLevelSeeder extends Seeder
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
                'skill_focus' => 'Clarity',
                'learning_objective' => 'Build a concise interview introduction that connects your background, strengths, and target role.',
                'success_criteria' => "1. Open with your current role or background.\n2. Mention one or two relevant strengths.\n3. Connect your experience to the opportunity.\n4. Keep the answer focused and professional.",
                'retry_hint' => 'Trim unrelated details and use a simple present-past-future structure.',
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
                'skill_focus' => 'STAR Method',
                'learning_objective' => 'Practice a behavioral answer with clear Situation, Task, Action, and Result evidence.',
                'success_criteria' => "1. Set the situation briefly.\n2. Explain your responsibility.\n3. Describe specific actions you took.\n4. End with a result, impact, or lesson.",
                'retry_hint' => 'Make the Action and Result parts more specific before retrying.',
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
                'skill_focus' => 'Professionalism',
                'learning_objective' => 'Answer a difficult personal question honestly while protecting your credibility.',
                'success_criteria' => "1. Name a real but manageable weakness.\n2. Avoid blaming others.\n3. Explain what you are doing to improve.\n4. Keep the tone confident and accountable.",
                'retry_hint' => 'Choose a weakness that is not central to the role, then show your improvement plan.',
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
                'skill_focus' => 'Interview Readiness',
                'learning_objective' => 'Combine clarity, relevance, STAR structure, and professional delivery across a longer interview flow.',
                'success_criteria' => "1. Answer each question directly.\n2. Use evidence from real experience.\n3. Include results for behavioral answers.\n4. Keep pacing steady under time pressure.\n5. Stay professional from start to finish.",
                'retry_hint' => 'Review your lowest scoring answer first, then repeat the full round with shorter, more specific responses.',
                'difficulty' => 'expert',
                'required_score' => 90,
                'xp_reward' => 3000,
                'energy_cost' => 2,
            ]
        ];

        foreach ($levels as $level) {
            GameLevel::updateOrCreate(
                ['level_number' => $level['level_number']],
                $level
            );
        }
    }
}
