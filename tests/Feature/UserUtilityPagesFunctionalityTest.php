<?php

namespace Tests\Feature;

use App\Models\LearningModule;
use App\Models\User;
use App\Notifications\UserActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserUtilityPagesFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_profile_and_password_actions_update_current_user(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'password' => Hash::make('current-password'),
        ]);

        $this->actingAs($user)
            ->post(route('user.account.profile'), [
                'name' => 'Updated Candidate',
                'email' => 'updated-candidate@example.com',
                'target_position' => 'Data Analyst',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Candidate',
            'email' => 'updated-candidate@example.com',
            'target_position' => 'Data Analyst',
        ]);

        $this->actingAs($user->fresh())
            ->post(route('user.account.password'), [
                'current_password' => 'current-password',
                'new_password' => 'new-password',
                'confirm_password' => 'new-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Password updated successfully.');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_account_delete_soft_deletes_user_and_logs_out(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('user.account.delete'))
            ->assertRedirect('/')
            ->assertSessionHas('success', 'Your account has been deleted.');

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_notification_actions_are_scoped_to_the_current_user(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $user->notify(new UserActivityNotification('First Notice', 'Review your first update.'));
        $user->notify(new UserActivityNotification('Second Notice', 'Review your second update.'));
        $otherUser->notify(new UserActivityNotification('Other Notice', 'This belongs to another user.'));

        $ownNotification = $user->notifications()->latest()->firstOrFail();
        $otherNotification = $otherUser->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('user.notifications.fetch'))
            ->assertOk()
            ->assertJsonPath('unreadCount', 2)
            ->assertJsonCount(2, 'notifications');

        $this->actingAs($user)
            ->post(route('user.notifications.read', $ownNotification->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($ownNotification->fresh()->read_at);

        $this->actingAs($user)
            ->post(route('user.notifications.read', $otherNotification->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('user.notifications.readAll'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());

        $this->actingAs($user)
            ->delete(route('user.notifications.delete', $ownNotification->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['id' => $ownNotification->id]);

        $this->actingAs($user)
            ->delete(route('user.notifications.delete', $otherNotification->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('user.notifications.clearAll'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $user->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);
    }

    public function test_modules_filters_search_and_pagination_remain_functional(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        for ($index = 1; $index <= 13; $index++) {
            LearningModule::create([
                'title' => "STAR Practice {$index}",
                'description' => 'Structured answer practice.',
                'status' => 'published',
                'category' => 'Interview Skills',
            ]);
        }

        LearningModule::create([
            'title' => 'Hidden STAR Draft',
            'description' => 'Should not be visible.',
            'status' => 'draft',
            'category' => 'Interview Skills',
        ]);

        LearningModule::create([
            'title' => 'Voice Basics',
            'description' => 'Different category.',
            'status' => 'published',
            'category' => 'Voice',
        ]);

        $response = $this->actingAs($user)
            ->get(route('user.modules.index', [
                'category' => 'Interview Skills',
                'search' => 'STAR',
            ]));

        $response->assertOk()
            ->assertSee('STAR Practice 1')
            ->assertDontSee('Hidden STAR Draft')
            ->assertDontSee('Voice Basics')
            ->assertSee('category=Interview%20Skills', false)
            ->assertSee('search=STAR', false)
            ->assertViewHas('modules', fn ($modules) => $modules->total() === 13);
    }
}
