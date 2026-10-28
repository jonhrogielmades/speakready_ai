<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginLogoutActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_user_login_creates_admin_activity_entry(): void
    {
        $user = User::factory()->create([
            'name' => 'Ready User',
            'username' => 'ready-user',
            'email' => 'ready@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->post(route('login'), [
            'username' => 'ready-user',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'user_logged_in',
            'description' => 'Ready User (ready-user) logged in.',
        ]);

        $this->assertTrue(
            $user->fresh()->notifications()->get()->contains(
                fn ($notification): bool => ($notification->data['title'] ?? null) === 'New Sign-In'
            )
        );
    }

    public function test_user_logout_creates_admin_activity_entry(): void
    {
        $user = User::factory()->create([
            'name' => 'Ready User',
            'email' => 'ready@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'user_logged_out',
            'description' => 'Ready User (ready@example.com) logged out.',
        ]);

        $this->assertTrue(
            $user->fresh()->notifications()->get()->contains(
                fn ($notification): bool => ($notification->data['title'] ?? null) === 'Signed Out'
            )
        );
    }
}
