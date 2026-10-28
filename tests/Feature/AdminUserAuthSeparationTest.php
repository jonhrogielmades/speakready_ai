<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserAuthSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_change_a_google_accounts_email_or_assign_a_local_password(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $googleUser = User::factory()->google()->create([
            'name' => 'Google Candidate',
            'email' => 'google-candidate@example.com',
            'password' => Hash::make('original-random-google-secret'),
            'is_admin' => false,
            'status' => 'active',
        ]);
        $originalPasswordHash = $googleUser->getRawOriginal('password');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $googleUser), [
                'name' => 'Tampered Google Candidate',
                'email' => 'replacement@example.com',
                'password' => 'AttemptedLocalPass123!',
                'role' => 'user',
                'status' => 'active',
            ])
            ->assertSessionHasErrors(['email', 'password']);

        $googleUser->refresh();

        $this->assertSame('Google Candidate', $googleUser->name);
        $this->assertSame('google-candidate@example.com', $googleUser->email);
        $this->assertSame($originalPasswordHash, $googleUser->getRawOriginal('password'));
    }

    public function test_admin_can_still_change_a_local_accounts_email_and_password(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $localUser = User::factory()->create([
            'email' => 'local-candidate@example.com',
            'password' => Hash::make('old-local-password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $localUser), [
                'name' => 'Updated Local Candidate',
                'email' => 'updated-local@example.com',
                'password' => 'UpdatedLocalPass123!',
                'role' => 'user',
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $localUser->refresh();

        $this->assertSame('updated-local@example.com', $localUser->email);
        $this->assertTrue(Hash::check('UpdatedLocalPass123!', $localUser->password));
    }

    public function test_admin_can_update_noncredential_fields_for_a_google_account(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        $googleUser = User::factory()->google()->create([
            'name' => 'Google Candidate',
            'email' => 'google-metadata@example.com',
            'password' => Hash::make('original-random-google-secret'),
            'is_admin' => false,
            'status' => 'active',
        ]);
        $originalPasswordHash = $googleUser->getRawOriginal('password');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $googleUser), [
                'name' => 'Updated Google Candidate',
                'email' => $googleUser->email,
                'role' => 'user',
                'status' => 'inactive',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $googleUser->refresh();

        $this->assertSame('Updated Google Candidate', $googleUser->name);
        $this->assertSame('inactive', $googleUser->status);
        $this->assertSame('google-metadata@example.com', $googleUser->email);
        $this->assertSame($originalPasswordHash, $googleUser->getRawOriginal('password'));
    }

    public function test_desktop_and_mobile_admin_user_views_expose_google_account_locking_hooks(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
        User::factory()->google()->create([
            'email' => 'managed-google@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        foreach (['desktop', 'mobile'] as $layout) {
            $this->actingAs($admin)
                ->get(route('admin.users.index', ['sr_layout' => $layout]))
                ->assertOk()
                ->assertSee('data-user-auth-method="google"', false)
                ->assertSee('id="editUserGoogleAuthNotice" hidden', false)
                ->assertSee('id="editUserPasswordField"', false)
                ->assertSee('emailInput.readOnly = isGoogleAccount;', false)
                ->assertSee('passwordField.hidden = isGoogleAccount;', false)
                ->assertSee('passwordInput.disabled = isGoogleAccount;', false);
        }
    }
}
