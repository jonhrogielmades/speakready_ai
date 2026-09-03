<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\Score;
use App\Models\User;
use App\Notifications\UserActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    public function test_account_page_repairs_missing_notification_and_activity_tables(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->dropAccountNotificationTables();

        $this->actingAs($user)
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('id="accountProfileForm"', false)
            ->assertSee('id="accountPasswordForm"', false)
            ->assertSee('id="accountDeleteForm"', false)
            ->assertSee('data-sr-confirm-form', false)
            ->assertDontSee('onsubmit="return confirm', false);

        $this->assertAccountNotificationSchemaReady();
    }

    public function test_account_updates_repair_missing_tables_and_record_activity_notifications(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'password' => Hash::make('current-password'),
        ]);

        $this->dropAccountNotificationTables();

        $this->actingAs($user)
            ->post(route('user.account.profile'), [
                'name' => 'Schema Safe Candidate',
                'email' => 'schema-safe@example.com',
                'target_position' => 'Network Administrator',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated successfully.');

        $this->assertAccountNotificationSchemaReady();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'profile_updated',
        ]);
        $this->assertTrue(
            $user->fresh()->notifications()->get()->contains(
                fn ($notification): bool => ($notification->data['title'] ?? null) === 'Profile Updated'
            )
        );

        $this->actingAs($user->fresh())
            ->post(route('user.account.password'), [
                'current_password' => 'current-password',
                'new_password' => 'new-password',
                'confirm_password' => 'new-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Password updated successfully.');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'password_changed',
        ]);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertTrue(
            $user->fresh()->notifications()->get()->contains(
                fn ($notification): bool => ($notification->data['title'] ?? null) === 'Password Changed'
            )
        );
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

    public function test_notifications_page_shows_current_users_full_activity_history(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        for ($i = 1; $i <= 18; $i++) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'practice_activity_'.$i,
                'description' => 'Candidate activity item '.$i,
            ]);
        }

        ActivityLog::create([
            'user_id' => $otherUser->id,
            'action' => 'other_activity',
            'description' => 'Other user activity should stay private.',
        ]);
        $latestActivity = ActivityLog::where('user_id', $user->id)
            ->where('action', 'practice_activity_18')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('user.notifications'))
            ->assertOk()
            ->assertSee('id="notificationActionStatus"', false)
            ->assertSee(route('user.activities.clearAll'), false)
            ->assertSee(route('user.activities.delete', $latestActivity->id), false)
            ->assertSee("deleteActivityLog('".$latestActivity->id."')", false)
            ->assertSee('function notificationJsonRequest', false)
            ->assertSee("'Accept': 'application/json'", false)
            ->assertSee('Activity history')
            ->assertSee('Candidate activity item 1')
            ->assertSee('Candidate activity item 18')
            ->assertSee('18 total')
            ->assertDontSee('Other user activity should stay private.');
    }

    public function test_notifications_page_repairs_missing_notification_and_activity_tables(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->dropAccountNotificationTables();

        $this->actingAs($user)
            ->get(route('user.notifications'))
            ->assertOk()
            ->assertSee('id="notificationActionStatus"', false)
            ->assertSee('You have no notifications at the moment.')
            ->assertSee('No account activity has been recorded yet.')
            ->assertSee('function notificationJsonRequest', false);

        $this->assertAccountNotificationSchemaReady();
    }

    public function test_activity_history_actions_are_scoped_to_the_current_user(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $ownActivity = ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'profile_updated',
            'description' => 'User updated their profile.',
        ]);

        $otherActivity = ActivityLog::create([
            'user_id' => $otherUser->id,
            'action' => 'login',
            'description' => 'Other user logged in.',
        ]);

        $this->actingAs($user)
            ->delete(route('user.activities.delete', $otherActivity->id))
            ->assertNotFound();

        $this->assertDatabaseHas('activity_logs', ['id' => $otherActivity->id]);

        $this->actingAs($user)
            ->delete(route('user.activities.delete', $ownActivity->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('activity_logs', ['id' => $ownActivity->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $otherActivity->id]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'language_updated',
            'description' => 'User changed preferred language.',
        ]);

        $this->actingAs($user)
            ->delete(route('user.activities.clearAll'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('activity_logs', ['user_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $otherActivity->id]);
    }

    public function test_activity_clear_action_repairs_missing_activity_logs_table(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        Schema::disableForeignKeyConstraints();
        try {
            Schema::dropIfExists('activity_logs');
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->actingAs($user)
            ->delete(route('user.activities.clearAll'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertAccountNotificationSchemaReady();
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

        LearningModule::create([
            'title' => 'Literal 100% Completion Module',
            'description' => 'Percent signs should be treated as text.',
            'status' => 'published',
            'category' => 'Interview Skills',
        ]);

        LearningModule::create([
            'title' => 'Literal 100x Completion Module',
            'description' => 'This should not match a percent search.',
            'status' => 'published',
            'category' => 'Interview Skills',
        ]);

        $this->actingAs($user)
            ->get(route('user.modules.index', ['search' => '100%']))
            ->assertOk()
            ->assertSee('Literal 100% Completion Module')
            ->assertDontSee('Literal 100x Completion Module');
    }

    private function dropAccountNotificationTables(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('activity_logs');
            Schema::dropIfExists('notifications');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function assertAccountNotificationSchemaReady(): void
    {
        $this->assertTrue(Schema::hasTable('notifications'));
        foreach (['id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'created_at', 'updated_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('notifications', $column), "Missing notifications.{$column}");
        }

        $this->assertTrue(Schema::hasTable('activity_logs'));
        foreach (['user_id', 'action', 'description', 'ip_address', 'read_at', 'created_at', 'updated_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('activity_logs', $column), "Missing activity_logs.{$column}");
        }

        foreach (['is_admin', 'google_id', 'status', 'reactivation_requested_at', 'profile_photo_path', 'target_position', 'preferred_language', 'deleted_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "Missing users.{$column}");
        }
    }
}
