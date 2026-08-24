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
            'username' => 'pwa_user',
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

    public function test_password_login_accepts_username_identifier(): void
    {
        $user = User::factory()->create([
            'username' => 'ready_candidate',
            'email' => 'candidate@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $response = $this->post(route('login'), [
            'login' => 'Ready_Candidate',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
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
            'username' => 'shared_device',
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

    public function test_registration_logs_user_in_and_flashes_success_alert(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Interview User',
            'identifier' => 'new-interview-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('registration_success', true)
            ->assertSessionHas('success', 'Registration successful. Welcome to SpeakReady AI!');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'new_interview_user',
            'email' => 'new-interview-user@example.com',
        ]);
        $this->assertDatabaseHas('profiles', [
            'user_id' => User::where('email', 'new-interview-user@example.com')->value('id'),
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="srFlashModal"', false)
            ->assertSee('data-sr-flash-modal', false)
            ->assertSee('Registration successful. Welcome to SpeakReady AI!');
    }

    public function test_registration_accepts_username_only_identifier(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Username Only User',
            'identifier' => 'username_only_user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('registration_success', true);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Username Only User',
            'username' => 'username_only_user',
            'email' => null,
        ]);
    }

    public function test_guest_auth_modal_uses_loading_overlay_for_registration(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('id="loginForm"', false)
            ->assertSee('id="signupForm"', false)
            ->assertSee('name="login"', false)
            ->assertSee('name="identifier"', false)
            ->assertSee('Username or email address', false)
            ->assertSee('id="signupIdentifier"', false)
            ->assertDontSee('id="signupUsername"', false)
            ->assertSee('id="loginTransitionOverlay"', false)
            ->assertSee('id="authTransitionTitle"', false)
            ->assertSee('Creating your account...', false)
            ->assertSee("showLoginTransition('register')", false)
            ->assertSee('id="pageTransitionOverlay"', false)
            ->assertSee('window.SpeakReadyPageTransition', false)
            ->assertSee('data-auth-transition="google"', false)
            ->assertSee('Connecting to Google...', false)
            ->assertSee('border-right-color: rgba(14, 165, 233, 0.78);', false);
    }
}
