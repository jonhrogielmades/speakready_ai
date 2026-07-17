<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameLevel;
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
        Profile::create(['user_id' => $user->id, 'energy' => 3]);
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
