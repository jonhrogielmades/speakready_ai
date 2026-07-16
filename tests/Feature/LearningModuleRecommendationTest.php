<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\LearningProgress;
use App\Models\Score;
use App\Models\User;
use App\Services\LearningRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningModuleRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_skill_score_recommends_matching_learning_module(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Communication');
        $clarityModule = $this->module('Clear Answer Structure', 'Build clarity with concise, organized interview answers.', ['clarity']);
        $this->module('Grammar Essentials', 'Improve grammar, sentence control, and word choice.', ['grammar']);

        $session = $this->completedSessionFor($user, $category);
        Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => 45,
            'relevance_score' => 88,
            'grammar_score' => 92,
            'professionalism_score' => 86,
            'confidence_score' => 84,
            'overall_readiness_score' => 72,
        ]);
        Feedback::create([
            'interview_session_id' => $session->id,
            'weaknesses' => 'The answer was unclear and too wordy.',
            'improvement_suggestions' => 'Use a clearer structure and shorter points.',
        ]);

        $recommendations = app(LearningRecommendationService::class)->forUser($user->id, 2);

        $this->assertCount(2, $recommendations);
        $this->assertSame($clarityModule->id, $recommendations->first()->module->id);
        $this->assertSame('Clarity', $recommendations->first()->skill);
    }

    public function test_modules_page_shows_personalized_recommendations(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Communication');
        $this->module('Clear Answer Structure', 'Build clarity with concise, organized interview answers.', ['clarity']);

        $session = $this->completedSessionFor($user, $category);
        Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => 50,
            'relevance_score' => 85,
            'grammar_score' => 80,
            'professionalism_score' => 82,
            'confidence_score' => 78,
            'overall_readiness_score' => 71,
        ]);

        $this->actingAs($user)
            ->get(route('user.modules.index'))
            ->assertOk()
            ->assertSee('Recommended For You')
            ->assertSee('Clear Answer Structure')
            ->assertSee('Clarity score', false);
    }

    public function test_user_can_record_module_completion_progress(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module('STAR Method Basics', 'Practice situation, task, action, and result answers.', ['star_method']);

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module->id), [
                'progress_percentage' => 100,
            ])
            ->assertRedirect(route('user.modules.show', $module->id));

        $this->assertDatabaseHas('learning_progress', [
            'user_id' => $user->id,
            'learning_module_id' => $module->id,
            'status' => 'completed',
            'progress_percentage' => 100,
        ]);

        $progress = LearningProgress::where('user_id', $user->id)
            ->where('learning_module_id', $module->id)
            ->first();

        $this->assertNotNull($progress);
        $this->assertSame(100, (int) $progress->progress_percentage);
    }

    private function category(string $title): Category
    {
        return Category::create([
            'title' => $title,
            'description' => "{$title} questions",
            'status' => 'active',
            'type' => 'core',
        ]);
    }

    private function module(string $title, string $description, array $skills): LearningModule
    {
        return LearningModule::create([
            'title' => $title,
            'description' => $description,
            'category' => 'Communication',
            'difficulty' => 'beginner',
            'type' => 'article',
            'status' => 'published',
            'mapped_skills' => $skills,
        ]);
    }

    private function completedSessionFor(User $user, Category $category): InterviewSession
    {
        return InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'completed',
        ]);
    }
}
