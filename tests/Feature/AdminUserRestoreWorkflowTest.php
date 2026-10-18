<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRestoreWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_and_restore_a_soft_deleted_user_with_an_audit_record(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $activeUser = User::factory()->create([
            'name' => 'Active Account Marker',
            'is_admin' => false,
            'status' => 'active',
        ]);
        $deletedAdmin = User::factory()->create([
            'name' => 'Recoverable Admin Marker',
            'is_admin' => true,
            'status' => 'suspended',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $deletedAdmin), [
                'delete_type' => 'soft',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User deleted successfully');

        $this->assertSoftDeleted('users', ['id' => $deletedAdmin->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-deleted-users-toggle', false)
            ->assertSee(route('admin.users.index', ['trashed' => 1]), false)
            ->assertSee($activeUser->name)
            ->assertDontSee($deletedAdmin->name);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['trashed' => 1]))
            ->assertOk()
            ->assertSee('data-deleted-users-notice', false)
            ->assertSee('Date Deleted')
            ->assertSee($deletedAdmin->name)
            ->assertSee(route('admin.users.restore', $deletedAdmin->id), false)
            ->assertDontSee($activeUser->name);

        $this->actingAs($admin)
            ->post(route('admin.users.restore', $deletedAdmin->id))
            ->assertRedirect(route('admin.users.index', ['trashed' => 1]))
            ->assertSessionHas('success', 'User restored successfully');

        $this->assertNotSoftDeleted('users', ['id' => $deletedAdmin->id]);
        $this->assertTrue((bool) $deletedAdmin->refresh()->is_admin);
        $this->assertSame('suspended', $deletedAdmin->status);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'admin_user_restored',
            'description' => "Restored administrator account {$deletedAdmin->name} (#{$deletedAdmin->id}).",
        ]);
        $this->assertTrue(
            ActivityLog::importantForAdmin()
                ->where('action', 'admin_user_restored')
                ->where('user_id', $admin->id)
                ->exists()
        );
    }

    public function test_deleted_user_filters_and_restore_action_work_in_the_mobile_admin_view(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $matchingUser = User::factory()->create([
            'name' => 'Mobile Restore Match',
            'is_admin' => false,
            'status' => 'inactive',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Mobile Restore Other',
            'is_admin' => false,
            'status' => 'inactive',
        ]);
        $matchingUser->delete();
        $otherUser->delete();

        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphoneUserAgent)
            ->get(route('admin.users.index', [
                'trashed' => 1,
                'search' => 'Mobile Restore Match',
            ]))
            ->assertOk()
            ->assertSee('class="admin-mobile-shell mobile-shell"', false)
            ->assertSee('data-deleted-users-notice', false)
            ->assertSee('data-label="Deleted"', false)
            ->assertSee('aria-label="Restore Mobile Restore Match"', false)
            ->assertSee($matchingUser->name)
            ->assertDontSee($otherUser->name)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_restore_only_accepts_visible_soft_deleted_accounts(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $activeUser = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $protectedAdmin = User::factory()->create([
            'name' => 'Protected Default Admin',
            'email' => 'admin@speakreadyai.online',
            'is_admin' => true,
            'status' => 'active',
        ]);
        $protectedAdmin->delete();

        $this->actingAs($admin)
            ->post(route('admin.users.restore', $activeUser->id))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['trashed' => 1]))
            ->assertOk()
            ->assertDontSee($protectedAdmin->name);

        $this->actingAs($admin)
            ->post(route('admin.users.restore', $protectedAdmin->id))
            ->assertNotFound();

        $this->assertSoftDeleted('users', ['id' => $protectedAdmin->id]);
        $this->assertDatabaseMissing('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'admin_user_restored',
        ]);
    }
}
