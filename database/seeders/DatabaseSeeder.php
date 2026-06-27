<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@speakreadyai.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
                'is_admin' => true,
            ]
        );

        // Create Regular User
        \App\Models\User::firstOrCreate(
            ['email' => 'user@speakreadyai.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'is_admin' => false,
            ]
        );

        // Create Categories
        $cat1 = \App\Models\Category::firstOrCreate(['title' => 'Software Engineering']);
        $cat2 = \App\Models\Category::firstOrCreate(['title' => 'Customer Service']);
        $cat3 = \App\Models\Category::firstOrCreate(['title' => 'Management']);

        // Create Questions for Software Engineering
        $questions = [
            ['question_text' => 'Describe a situation where you faced a tough technical challenge and how you overcame it.', 'difficulty' => 'hard', 'type' => 'Behavioral'],
            ['question_text' => 'What is your preferred tech stack and why?', 'difficulty' => 'medium', 'type' => 'Technical'],
            ['question_text' => 'How do you handle disagreements with team members on technical decisions?', 'difficulty' => 'medium', 'type' => 'Situational'],
            ['question_text' => 'Tell me about a time you missed a deadline.', 'difficulty' => 'hard', 'type' => 'Behavioral'],
            ['question_text' => 'Can you explain the difference between a process and a thread?', 'difficulty' => 'easy', 'type' => 'Technical'],
            ['question_text' => 'How do you ensure the quality of your code?', 'difficulty' => 'medium', 'type' => 'Technical'],
            ['question_text' => 'Describe a time you had to learn a new technology quickly.', 'difficulty' => 'medium', 'type' => 'Behavioral'],
            ['question_text' => 'Where do you see your career in 5 years?', 'difficulty' => 'easy', 'type' => 'Personal'],
        ];

        foreach ($questions as $q) {
            \App\Models\Question::firstOrCreate([
                'category_id' => $cat1->id,
                'question_text' => $q['question_text']
            ], $q);
        }
    }
}
