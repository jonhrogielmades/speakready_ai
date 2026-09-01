<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_account_page_renders_profile_and_security_forms(): void
    {
        $admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.account'))
            ->assertOk()
            ->assertSee('Admin Account')
            ->assertSee('Profile Details')
            ->assertSee('Security & Password', false)
            ->assertSee(route('admin.account.profile'), false)
            ->assertSee(route('admin.account.password'), false)
            ->assertDontSee('Delete Account');
    }

    public function test_admin_can_update_their_own_account_profile(): void
    {
        $admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.account.profile'), [
                'name' => 'Updated Admin',
                'email' => 'updated-admin@example.com',
                'target_position' => 'System Administrator',
            ])
            ->assertRedirect(route('admin.account'))
            ->assertSessionHas('success', 'Admin account updated successfully.');

        $admin->refresh();

        $this->assertSame('Updated Admin', $admin->name);
        $this->assertSame('updated-admin@example.com', $admin->email);
        $this->assertSame('System Administrator', $admin->target_position);
        $this->assertDatabaseHas(ActivityLog::class, [
            'user_id' => $admin->id,
            'action' => 'admin_profile_updated',
        ]);
    }

    public function test_admin_can_update_their_own_password(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('old-password'),
            'is_admin' => true,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.account.password'), [
                'current_password' => 'old-password',
                'new_password' => 'new-password-123',
                'confirm_password' => 'new-password-123',
            ])
            ->assertRedirect(route('admin.account'))
            ->assertSessionHas('success', 'Admin password updated successfully.');

        $this->assertTrue(Hash::check('new-password-123', $admin->refresh()->password));
        $this->assertDatabaseHas(ActivityLog::class, [
            'user_id' => $admin->id,
            'action' => 'admin_password_changed',
        ]);
    }
}
