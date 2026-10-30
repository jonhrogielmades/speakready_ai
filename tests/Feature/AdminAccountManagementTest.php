<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\PasswordRecovery;
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
                'new_password' => 'NewAdminPass123!',
                'confirm_password' => 'NewAdminPass123!',
            ])
            ->assertRedirect(route('admin.account'))
            ->assertSessionHas('success', 'Admin password updated successfully.');

        $this->assertTrue(Hash::check('NewAdminPass123!', $admin->refresh()->password));
        $this->assertDatabaseHas(ActivityLog::class, [
            'user_id' => $admin->id,
            'action' => 'admin_password_changed',
        ]);
    }

    public function test_admin_can_rotate_their_password_recovery_code(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('current-password'),
            'is_admin' => true,
            'status' => 'active',
            'password_recovery_code_hash' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.account.recovery-code'), [
            'current_password' => 'current-password',
        ]);

        $response
            ->assertRedirect(route('admin.account'))
            ->assertSessionHas('recovery_code', fn ($code): bool => preg_match('/^[A-Z0-9]{4}(?:-[A-Z0-9]{4}){3}$/', (string) $code) === 1);

        $this->assertTrue(PasswordRecovery::check(
            (string) $response->getSession()->get('recovery_code'),
            $admin->fresh()->password_recovery_code_hash
        ));
    }

    public function test_google_admin_cannot_use_local_email_password_or_recovery_controls(): void
    {
        $admin = User::factory()->google()->create([
            'email' => 'google-admin@example.com',
            'password' => Hash::make('unchanged-google-password'),
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.account'))
            ->assertOk()
            ->assertSee('Google Sign-in')
            ->assertSee('Google Account Email')
            ->assertDontSee('id="adminAccountPasswordForm"', false)
            ->assertDontSee(route('admin.account.recovery-code'), false);

        $this->post(route('admin.account.profile'), [
            'name' => $admin->name,
            'email' => 'replacement-admin@example.com',
            'target_position' => null,
        ])->assertSessionHasErrors('email');

        $this->post(route('admin.account.password'), [
            'current_password' => 'unchanged-google-password',
            'new_password' => 'NewLocalPassword1!',
            'confirm_password' => 'NewLocalPassword1!',
        ])->assertSessionHasErrors('current_password');

        $this->post(route('admin.account.recovery-code'), [
            'current_password' => 'unchanged-google-password',
        ])->assertSessionHasErrors('recovery_code');

        $admin->refresh();
        $this->assertSame('google-admin@example.com', $admin->email);
        $this->assertTrue(Hash::check('unchanged-google-password', $admin->password));
        $this->assertNull($admin->password_recovery_code_hash);
    }
}
