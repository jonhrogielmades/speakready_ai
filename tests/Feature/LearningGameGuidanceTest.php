<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameLevel;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningGameGuidanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_learning_guidance_for_a_game_level(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $category = $this->category(['type' => 'game']);

        $this->actingAs($admin)
            ->post(route('admin.game.store'), [
                'category_id' => $category->id,
                'level_number' => 1,
                'title' => 'STAR Evidence Sprint',
                'description' => 'Practice a structured behavioral answer.',
                'mission_text' => "1. Tell me about a time you solved a team conflict.\n2. What was the result?",
                'target_position' => 'Customer Support',
                'skill_focus' => 'STAR Method',
                'learning_objective' => 'Give a behavioral answer with clear context, ownership, action, and result.',
                'success_criteria' => "1. State the situation.\n2. Explain your task.\n3. Describe your action.\n4. Include a measurable result.",
                'retry_hint' => 'Make the action and result more specific before retrying.',
                'difficulty' => 'intermediate',
                'required_score' => 80,
                'xp_reward' => 500,
                'energy_cost' => 1,
                'ai_persona' => 'Supportive Coach',
                'time_limit_seconds' => 120,
                'target_tone' => 'Confident',
                'skill_xp_type' => 'Communication',
                'skill_xp_amount' => 25,
            ])
            ->assertRedirect(route('admin.game'));

        $this->assertDatabaseHas('game_levels', [
            'title' => 'STAR Evidence Sprint',
            'skill_focus' => 'STAR Method',
            'learning_objective' => 'Give a behavioral answer with clear context, ownership, action, and result.',
            'retry_hint' => 'Make the action and result more specific before retrying.',
        ]);
    }

    public function test_learning_guidance_renders_for_learners_and_live_game_sessions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'energy' => 3]);
        $category = $this->category(['type' => 'game']);
        $level = $this->gameLevel($category);

        $this->actingAs($user)
            ->get(route('user.learning', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee('STAR Method')
            ->assertSee('Give a behavioral answer with clear context, ownership, action, and result.')
            ->assertSee('Success checklist')
            ->assertSee('Include a measurable result.');

        $this->actingAs($user)
            ->post(route('user.game.start', $level))
            ->assertRedirect(route('user.game.match'));

        $session = InterviewSession::where('user_id', $user->id)->latest()->firstOrFail();

        $this->assertStringContainsString('LEARNING GAME CONTEXT', $session->interview_focus);
        $this->assertStringContainsString('Skill focus: STAR Method', $session->interview_focus);
        $this->assertStringContainsString('Success criteria:', $session->interview_focus);
        $this->assertDatabaseHas('questions', [
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about a time you solved a team conflict.',
        ]);

        $this->actingAs($user)
            ->withSession([
                'active_interview_id' => $session->id,
                'game_level_id' => $level->id,
            ])
            ->get(route('user.game.match'))
            ->assertOk()
            ->assertSee('Challenge Brief')
            ->assertSee('STAR Method')
            ->assertSee('Include a measurable result.')
            ->assertSee('Make the action and result more specific before retrying.');
    }

    public function test_success_criteria_are_parsed_as_a_clean_checklist(): void
    {
        $level = new GameLevel([
            'success_criteria' => "1. Answer directly.\n2. Use a real example.\n- Mention the result.",
        ]);

        $this->assertSame([
            'Answer directly.',
            'Use a real example.',
            'Mention the result.',
        ], $level->parsed_success_criteria);
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Behavioral',
            'description' => 'Behavioral practice',
            'status' => 'active',
            'type' => 'core',
        ], $overrides));
    }

    private function gameLevel(Category $category, array $overrides = []): GameLevel
    {
        return GameLevel::create(array_merge([
            'category_id' => $category->id,
            'level_number' => 1,
            'title' => 'STAR Evidence Sprint',
            'description' => 'Practice a structured behavioral answer.',
            'mission_text' => "1. Tell me about a time you solved a team conflict.\n2. What was the result?",
            'target_position' => 'Customer Support',
            'skill_focus' => 'STAR Method',
            'learning_objective' => 'Give a behavioral answer with clear context, ownership, action, and result.',
            'success_criteria' => "1. State the situation.\n2. Explain your task.\n3. Describe your action.\n4. Include a measurable result.",
            'retry_hint' => 'Make the action and result more specific before retrying.',
            'difficulty' => 'intermediate',
            'required_score' => 80,
            'xp_reward' => 500,
            'energy_cost' => 1,
            'is_hidden' => false,
        ], $overrides));
    }
}
