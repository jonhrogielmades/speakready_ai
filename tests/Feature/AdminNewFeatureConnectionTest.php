<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\GameProgress;
use App\Models\GameLevel;
use App\Models\LearningModule;
use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNewFeatureConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_learning_games_do_not_render_on_user_side(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
        $category = $this->category(['type' => 'game']);

        $this->gameLevel($category, ['title' => 'Visible Confidence Sprint', 'level_number' => 1, 'is_hidden' => false]);
        $this->gameLevel($category, ['title' => 'Hidden Draft Challenge', 'level_number' => 2, 'is_hidden' => true]);

        $this->actingAs($user)
            ->get(route('user.learning', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee('Visible Confidence Sprint')
            ->assertDontSee('Hidden Draft Challenge');
    }

    public function test_admin_settings_toggles_are_enforced_on_user_features(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id]);

        Setting::setVal('aic_enable', false, 'general', 'boolean');
        $this->actingAs($user)
            ->get(route('user.coach'))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($user)
            ->postJson(route('user.coach.chat'), ['message' => 'Help me prepare.', 'history' => []])
            ->assertStatus(403)
            ->assertJsonPath('error', 'coach_disabled');

        Setting::setVal('vr_recording', false, 'general', 'boolean');
        $this->actingAs($user)
            ->get(route('user.drills.voice'))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($user)
            ->postJson(route('user.drills.voice.save'), ['category' => 'Behavioral'])
            ->assertStatus(403);

        Setting::setVal('ll_modules', false, 'general', 'boolean');
        $this->actingAs($user)
            ->get(route('user.modules.index'))
            ->assertRedirect(route('dashboard'));

        Setting::setVal('acc_registration', false, 'general', 'boolean');
        $this->post(route('register'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_user_side_updates_are_visible_to_admin_activity_and_user_details(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create([
            'name' => 'Visible Candidate',
            'email' => 'visible@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);
        Profile::create([
            'user_id' => $user->id,
            'leadership_xp' => 600,
        ]);
        $module = LearningModule::create([
            'title' => 'Interview Basics',
            'description' => 'Practice module',
            'category' => 'Communication',
            'difficulty' => 'Beginner',
            'status' => 'published',
        ]);
        $gameCategory = $this->category(['title' => 'Certificate Path', 'type' => 'game']);
        $gameLevel = $this->gameLevel($gameCategory, [
            'title' => 'Final Confidence Challenge',
            'required_score' => 80,
        ]);
        GameProgress::create([
            'user_id' => $user->id,
            'game_level_id' => $gameLevel->id,
            'status' => 'completed',
            'best_score' => 90,
        ]);

        $this->actingAs($user)
            ->post(route('user.language.update'), [
                'preferred_language' => 'ceb',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->postJson(route('user.drills.voice.save'), [
                'category' => 'Leadership',
                'prompt' => 'Tell me about a leadership moment.',
                'transcript' => 'I led a team project and improved the delivery process.',
                'duration_seconds' => 30,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module), [
                'progress_percentage' => 100,
                'quiz_score' => 92,
                'learning_hours' => 1.5,
            ])
            ->assertRedirect(route('user.modules.show', $module));

        $this->actingAs($user)
            ->postJson(route('user.coach.chat'), [
                'message' => 'Who developed SpeakReady AI?',
                'history' => [],
            ])
            ->assertOk()
            ->assertJsonPath('title', 'Who developed SpeakReady AI?');

        $this->actingAs($user)
            ->postJson(route('user.skills.unlock'), [
                'perk_id' => 'energy_efficiency',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->get(route('user.game.certificate.download', $gameCategory))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'language_updated',
            'description' => 'Visible Candidate changed preferred language to Cebuano.',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'voice_rehearsal_saved',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'learning_module_progress_updated',
            'description' => 'Visible Candidate updated learning progress for Interview Basics to 100%.',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'ai_coach_conversation_started',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'perk_unlocked',
            'description' => 'Visible Candidate unlocked the Energy Efficiency skill perk.',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'learning_game_certificate_issued',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('User Updates')
            ->assertSee('Visible Candidate updated learning progress for Interview Basics to 100%.');

        $activityResponse = $this->actingAs($admin)
            ->getJson(route('admin.api.latest-activities'))
            ->assertOk()
            ->assertJsonPath('new_count', ActivityLog::count());

        $this->assertStringContainsString(
            'Visible Candidate changed preferred language to Cebuano.',
            $activityResponse->json('html')
        );

        $this->actingAs($admin)
            ->getJson(route('admin.users.show', $user))
            ->assertOk()
            ->assertJsonPath('user.preferred_language_label', 'Cebuano')
            ->assertJsonPath('stats.learning_completed', 1)
            ->assertJsonPath('stats.voice_rehearsals', 1)
            ->assertJsonPath('stats.experience_points', 10)
            ->assertJsonPath('stats.certificates', 1)
            ->assertJsonPath('stats.coach_conversations', 1)
            ->assertJsonPath('stats.unlocked_perks', 1)
            ->assertJsonFragment(['module' => 'Interview Basics'])
            ->assertJsonFragment(['category' => 'Leadership'])
            ->assertJsonFragment(['title' => 'Who developed SpeakReady AI?'])
            ->assertJsonFragment(['name' => 'Energy Efficiency'])
            ->assertJsonFragment(['path' => 'Certificate Path']);
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Behavioral '.uniqid(),
            'description' => 'Practice category',
            'status' => 'active',
            'type' => 'core',
        ], $overrides));
    }

    private function gameLevel(Category $category, array $overrides = []): GameLevel
    {
        return GameLevel::create(array_merge([
            'category_id' => $category->id,
            'level_number' => 1,
            'title' => 'Practice Challenge',
            'description' => 'Practice clear responses.',
            'mission_text' => '1. Introduce yourself clearly.',
            'target_position' => 'Better Communication',
            'difficulty' => 'beginner',
            'required_score' => 60,
            'xp_reward' => 100,
            'energy_cost' => 1,
            'is_hidden' => false,
        ], $overrides));
    }
}
