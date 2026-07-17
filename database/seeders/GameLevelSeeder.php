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
                'title' => 'PH Basics: "Tell Me About Yourself"',
                'description' => 'Practice a concise introduction for Philippine HR screening, campus interviews, or first-round job interviews.',
                'mission_text' => 'Answer the prompt: "Tell me about yourself and why this role in the Philippines fits your next step." Focus on brevity, role fit, and evidence.',
                'target_position' => 'Philippine Interview Candidate',
                'skill_focus' => 'Clarity',
                'learning_objective' => 'Build a concise Philippine interview introduction that connects your background, strengths, and target role.',
                'success_criteria' => "1. Open with your current role, course, training, or background.\n2. Mention one or two strengths relevant to the Philippine role.\n3. Connect your experience to the opportunity.\n4. Keep the answer focused, respectful, and professional.",
                'retry_hint' => 'Trim unrelated details and use a simple present-past-future structure tied to the Philippine role.',
                'difficulty' => 'beginner',
                'required_score' => 80,
                'xp_reward' => 500,
                'energy_cost' => 1,
            ],
            [
                'level_number' => 2,
                'title' => 'PH Behavioral Mastery: STAR Method',
                'description' => 'Practice STAR answers for Philippine workplace, BPO, internship, or school leadership scenarios.',
                'mission_text' => 'Answer the prompt: "Tell me about a time you had to work with a difficult teammate, customer, or stakeholder." You must score at least 80% on clarity and STAR structure to unlock Level 3.',
                'target_position' => 'Philippine Interview Candidate',
                'skill_focus' => 'STAR Method',
                'learning_objective' => 'Practice a Philippine behavioral answer with clear Situation, Task, Action, and Result evidence.',
                'success_criteria' => "1. Set the local work, school, internship, or customer context briefly.\n2. Explain your responsibility.\n3. Describe specific actions you took.\n4. End with a result, impact, customer outcome, or lesson.",
                'retry_hint' => 'Make the Action and Result parts more specific before retrying, especially your own contribution and local impact.',
                'difficulty' => 'intermediate',
                'required_score' => 80,
                'xp_reward' => 1000,
                'energy_cost' => 1,
            ],
            [
                'level_number' => 3,
                'title' => 'PH HR Curveballs',
                'description' => 'Practice tricky Philippine HR questions about weakness, salary expectations, availability, and work setup.',
                'mission_text' => 'Answer the prompt: "What is your biggest weakness, and how are you improving it for this role?"',
                'target_position' => 'Philippine Interview Candidate',
                'skill_focus' => 'Professionalism',
                'learning_objective' => 'Answer difficult Philippine HR screening questions honestly while protecting your credibility.',
                'success_criteria' => "1. Name a real but manageable weakness.\n2. Avoid blaming others or previous employers.\n3. Explain what you are doing to improve.\n4. Keep the tone respectful, confident, and accountable.",
                'retry_hint' => 'Choose a weakness that is not central to the role, then show your improvement plan and readiness for the Philippine work setup.',
                'difficulty' => 'hard',
                'required_score' => 85,
                'xp_reward' => 1500,
                'energy_cost' => 1,
            ],
            [
                'level_number' => 4,
                'title' => 'Final Boss: 15-Min Philippine Mock Interview',
                'description' => 'Put everything you have learned to the test in a Philippine-style HR and role-fit interview flow.',
                'mission_text' => "A comprehensive 5-question rapid-fire round covering self-introduction, role fit, behavioral evidence, salary or work-setup expectations, and final motivation.",
                'target_position' => 'Philippine Interview Candidate',
                'skill_focus' => 'Philippine Interview Readiness',
                'learning_objective' => 'Combine clarity, relevance, STAR structure, and professional delivery across a longer Philippine interview flow.',
                'success_criteria' => "1. Answer each question directly.\n2. Use evidence from real school, work, internship, BPO, freelance, or project experience.\n3. Include results for behavioral answers.\n4. Keep pacing steady under time pressure.\n5. Stay respectful and professional from start to finish.",
                'retry_hint' => 'Review your lowest scoring answer first, then repeat the full round with shorter, more specific Philippine-context responses.',
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
