<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PwaRememberedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_login_remembers_the_device_by_default(): void
    {
        $user = User::factory()->create([
            'email' => 'pwa-user@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'pwa-user@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertAuthenticatedAs($user);
    }

    public function test_pwa_start_url_sends_authenticated_user_to_dashboard(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_password_login_can_opt_out_of_remembering_the_device(): void
    {
        User::factory()->create([
            'email' => 'shared-device@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'shared-device@example.com',
            'password' => 'password',
            'remember' => '0',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertCookieMissing(Auth::guard()->getRecallerName());
    }
}
