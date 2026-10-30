<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\GameProgress;
use App\Models\GameLevel;
use App\Models\InterviewSession;
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

        Setting::setVal('ll_modules', false, 'general', 'boolean');
        $this->actingAs($user)
            ->get(route('user.modules.index'))
            ->assertRedirect(route('dashboard'));

        Setting::setVal('role_user_perm_0', false, 'roles', 'boolean');
        $this->actingAs($user)
            ->get(route('interview.setup'))
            ->assertRedirect(route('dashboard'));

        Setting::setVal('role_user_perm_0', true, 'roles', 'boolean');
        Setting::setVal('role_user_perm_3', false, 'roles', 'boolean');
        $category = $this->category();
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get(route('user.sessions.export', $session))
            ->assertForbidden();

        Setting::setVal('role_user_perm_2', false, 'roles', 'boolean');
        $this->actingAs($user)
            ->post(route('user.account.delete'))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);

        Setting::setVal('acc_registration', false, 'general', 'boolean');
        $this->post(route('register'), [
            'name' => 'Blocked User',
            'username' => 'blocked-user',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ])->assertSessionHasErrors('username');

        $this->assertDatabaseMissing('users', ['username' => 'blocked-user']);
    }

    public function test_admin_created_user_facing_content_notifies_active_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $activeUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $inactiveUser = User::factory()->create(['is_admin' => false, 'status' => 'inactive']);
        $otherAdmin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $gameCategory = $this->category(['title' => 'Confidence Challenges', 'type' => 'game']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'title' => 'Behavioral Readiness',
                'type' => 'core',
                'description' => 'Fresh behavioral interview practice.',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.modules.store'), [
                'title' => 'Salary Negotiation Sprint',
                'category' => 'Career Growth',
                'difficulty' => 'Intermediate',
                'description' => 'Prepare clear salary expectation answers.',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.game.store'), $this->gamePayload($gameCategory, [
                'level_number' => 7,
                'title' => 'Confidence Challenge',
            ]))
            ->assertRedirect(route('admin.game'));

        $notifications = $activeUser->fresh()->notifications()->get();

        $this->assertTrue($notifications->contains(
            fn ($notification): bool => ($notification->data['title'] ?? null) === 'New Interview Category'
                && str_contains((string) ($notification->data['message'] ?? ''), 'Behavioral Readiness')
        ));
        $this->assertTrue($notifications->contains(
            fn ($notification): bool => ($notification->data['title'] ?? null) === 'New Learning Module'
                && str_contains((string) ($notification->data['message'] ?? ''), 'Salary Negotiation Sprint')
        ));
        $this->assertTrue($notifications->contains(
            fn ($notification): bool => ($notification->data['title'] ?? null) === 'New Learning Challenge'
                && str_contains((string) ($notification->data['message'] ?? ''), 'Confidence Challenge')
        ));

        $this->assertSame(0, $inactiveUser->fresh()->notifications()->count());
        $this->assertSame(0, $otherAdmin->fresh()->notifications()->count());
    }

    public function test_draft_or_hidden_admin_content_does_not_notify_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $gameCategory = $this->category(['title' => 'Hidden Games', 'type' => 'game']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'title' => 'Inactive Practice',
                'type' => 'core',
                'description' => 'Draft category.',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.modules.store'), [
                'title' => 'Draft Learning Module',
                'category' => 'Drafts',
                'difficulty' => 'Beginner',
                'description' => 'Draft content.',
                'status' => 'draft',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.game.store'), $this->gamePayload($gameCategory, [
                'level_number' => 8,
                'title' => 'Hidden Challenge',
                'is_hidden' => '1',
            ]))
            ->assertRedirect(route('admin.game'));

        $this->assertSame(0, $user->fresh()->notifications()->count());
    }

    public function test_making_admin_content_available_notifies_users_once(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category([
            'title' => 'Fresh Interview Path',
            'status' => 'inactive',
            'type' => 'core',
        ]);
        $gameCategory = $this->category(['title' => 'Visible Games', 'type' => 'game']);
        $hiddenLevel = $this->gameLevel($gameCategory, [
            'level_number' => 9,
            'title' => 'Visibility Challenge',
            'is_hidden' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'title' => 'Fresh Interview Path',
                'type' => 'core',
                'description' => 'Fresh practice path.',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.game.update', $hiddenLevel), $this->gamePayload($gameCategory, [
                'level_number' => 9,
                'title' => 'Visibility Challenge',
            ]))
            ->assertRedirect(route('admin.game'));

        $notifications = $user->fresh()->notifications()->get();

        $this->assertTrue($notifications->contains(
            fn ($notification): bool => ($notification->data['title'] ?? null) === 'New Interview Category'
                && str_contains((string) ($notification->data['message'] ?? ''), 'Fresh Interview Path')
        ));
        $this->assertTrue($notifications->contains(
            fn ($notification): bool => ($notification->data['title'] ?? null) === 'New Learning Challenge'
                && str_contains((string) ($notification->data['message'] ?? ''), 'Visibility Challenge')
        ));

        $notificationCount = $user->fresh()->notifications()->count();

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category->fresh()), [
                'title' => 'Fresh Interview Path',
                'type' => 'core',
                'description' => 'Fresh practice path updated.',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->put(route('admin.game.update', $hiddenLevel->fresh()), $this->gamePayload($gameCategory, [
                'level_number' => 9,
                'title' => 'Visibility Challenge Updated',
            ]))
            ->assertRedirect(route('admin.game'));

        $this->assertSame($notificationCount, $user->fresh()->notifications()->count());
    }

    public function test_publishing_draft_module_notifies_users_once(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'STAR Answer Builder',
            'category' => 'Interview Skills',
            'difficulty' => 'Beginner',
            'description' => 'Draft STAR content.',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.modules.update', $module), [
                'title' => 'STAR Answer Builder',
                'category' => 'Interview Skills',
                'difficulty' => 'Beginner',
                'description' => 'Published STAR content.',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.modules'));

        $this->assertTrue($user->fresh()->notifications()->get()->contains(
            fn ($notification): bool => ($notification->data['title'] ?? null) === 'New Learning Module'
                && str_contains((string) ($notification->data['message'] ?? ''), 'STAR Answer Builder')
        ));

        $notificationCount = $user->fresh()->notifications()->count();

        $this->actingAs($admin)
            ->put(route('admin.modules.update', $module->fresh()), [
                'title' => 'STAR Answer Builder',
                'category' => 'Interview Skills',
                'difficulty' => 'Beginner',
                'description' => 'Published STAR content updated.',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.modules'));

        $this->assertSame($notificationCount, $user->fresh()->notifications()->count());
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
            ->post(route('user.modules.progress', $module), [
                'milestone' => 'started',
            ])
            ->assertRedirect(route('user.modules.show', $module));

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module), [
                'milestone' => 'completed',
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
            ->assertJsonPath('new_count', ActivityLog::importantForAdmin()->whereNull('read_at')->count());

        $this->assertStringNotContainsString('Visible Candidate changed preferred language to Cebuano.', $activityResponse->json('html'));
        $this->assertStringNotContainsString('Visible Candidate unlocked the Energy Efficiency skill perk.', $activityResponse->json('html'));

        $this->actingAs($admin)
            ->getJson(route('admin.users.show', $user))
            ->assertOk()
            ->assertJsonPath('user.preferred_language_label', 'Cebuano')
            ->assertJsonPath('stats.learning_completed', 1)
            ->assertJsonPath('stats.experience_points', 0)
            ->assertJsonPath('stats.certificates', 1)
            ->assertJsonPath('stats.coach_conversations', 1)
            ->assertJsonPath('stats.unlocked_perks', 1)
            ->assertJsonFragment(['module' => 'Interview Basics'])
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

    private function gamePayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'level_number' => 1,
            'title' => 'Practice Challenge',
            'description' => 'Practice clear responses.',
            'mission_text' => '1. Introduce yourself clearly.',
            'target_position' => 'Better Communication',
            'skill_focus' => 'Confidence',
            'learning_objective' => 'Practice confident answers.',
            'success_criteria' => '1. Answer clearly.',
            'retry_hint' => 'Try a clearer example.',
            'difficulty' => 'beginner',
            'required_score' => 60,
            'xp_reward' => 100,
            'energy_cost' => 1,
            'ai_persona' => 'Friendly interviewer',
            'ai_custom_prompt' => '',
            'time_limit_seconds' => 0,
            'banned_words' => '',
            'target_tone' => 'professional',
            'custom_badge_name' => '',
            'skill_xp_type' => 'communication',
            'skill_xp_amount' => 10,
        ], $overrides);
    }
}
