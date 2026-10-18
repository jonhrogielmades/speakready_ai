<?php

namespace Tests\Feature;

use App\Models\LearningModule;
use App\Models\LearningProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningModuleReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_quiz_review_is_complete_and_scored_by_the_server(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'Reliable Module Quiz',
            'description' => 'Server-authoritative quiz progress.',
            'category' => 'Interview Skills',
            'difficulty' => 'beginner',
            'type' => 'article',
            'status' => 'published',
        ]);
        $quiz = $module->quizzes()->create(['title' => 'Readiness check', 'passing_score' => 70]);
        $first = $quiz->questions()->create([
            'type' => 'multiple_choice',
            'question_text' => 'Which answer is strongest?',
            'options' => ['A', 'B'],
            'correct_answer' => 'A',
        ]);
        $second = $quiz->questions()->create([
            'type' => 'true_false',
            'question_text' => 'Specific evidence is useful.',
            'options' => ['true', 'false'],
            'correct_answer' => 'true',
        ]);

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module->id), ['milestone' => 'started'])
            ->assertRedirect(route('user.modules.show', $module->id));

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module->id), ['milestone' => 'quiz_reviewed'])
            ->assertSessionHasErrors('quiz_answers');

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module->id), [
                'milestone' => 'quiz_reviewed',
                'quiz_answers' => [
                    $first->id => 'A',
                    $second->id => 'false',
                ],
            ])
            ->assertRedirect(route('user.modules.show', $module->id));

        $this->assertDatabaseHas('learning_progress', [
            'user_id' => $user->id,
            'learning_module_id' => $module->id,
            'progress_percentage' => 75,
            'quiz_score' => 50,
            'status' => 'in_progress',
        ]);

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module->id), ['milestone' => 'completed'])
            ->assertSessionHasErrors('milestone');

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module->id), [
                'milestone' => 'quiz_reviewed',
                'quiz_answers' => [
                    $first->id => 'A',
                    $second->id => 'true',
                ],
            ])
            ->assertRedirect(route('user.modules.show', $module->id));

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module->id), ['milestone' => 'completed'])
            ->assertRedirect(route('user.modules.show', $module->id));

        $this->assertDatabaseHas('learning_progress', [
            'user_id' => $user->id,
            'learning_module_id' => $module->id,
            'progress_percentage' => 100,
            'quiz_score' => 100,
            'status' => 'completed',
        ]);
        $this->assertSame(1, LearningProgress::where('user_id', $user->id)
            ->where('learning_module_id', $module->id)
            ->count());
    }

    public function test_module_position_generation_rejects_unknown_roles_without_saved_content(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('user.modules.position'), [
                'target_position' => 'Unsupported Position '.str_repeat('x', 8),
            ])
            ->assertSessionHasErrors('target_position');

        $this->assertDatabaseCount('learning_modules', 0);
    }
}
