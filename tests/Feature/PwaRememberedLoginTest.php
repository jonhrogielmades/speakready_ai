<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
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

    public function test_password_login_reports_email_and_password_mismatches(): void
    {
        User::factory()->create([
            'email' => 'known-user@example.com',
            'password' => Hash::make('correct-password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->post(route('login'), [
            'email' => 'missing-user@example.com',
            'password' => 'correct-password',
        ])->assertSessionHasErrors([
            'email' => 'The email address do not match our records.',
        ]);

        $this->post(route('login'), [
            'email' => 'known-user@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'password' => 'The password do not match our records.',
        ]);

        $this->post(route('login'), [
            'email' => 'missing-user@example.com',
            'password' => 'not-a-known-password',
        ])->assertSessionHasErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);

        $this->assertGuest();
    }

    public function test_registration_logs_user_in_and_flashes_success_alert(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Interview User',
            'email' => 'new-interview-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('terms.acceptance.show'))
            ->assertSessionHas('registration_success', true)
            ->assertSessionHas('success', 'Registration successful. Welcome to SpeakReady AI!');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'New Interview User',
            'email' => 'new-interview-user@example.com',
        ]);
        $this->assertDatabaseHas('profiles', [
            'user_id' => User::where('email', 'new-interview-user@example.com')->value('id'),
        ]);

        $this->get(route('terms.acceptance.show'))
            ->assertOk()
            ->assertSee('Review the Terms and Conditions')
            ->assertSee('id="srTermsSuccessModal"', false)
            ->assertSee('data-terms-success-modal', false)
            ->assertSee('Registration successful. Welcome to SpeakReady AI!')
            ->assertDontSee('terms-flash', false);

        $this->post(route('terms.acceptance.store'), [
            'terms_accepted' => '1',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHas('registration_success', true)
            ->assertSessionHas('success', 'Registration successful. Welcome to SpeakReady AI!');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="srFlashModal"', false)
            ->assertSee('data-sr-flash-modal', false)
            ->assertSee('Registration successful. Welcome to SpeakReady AI!');
    }

    public function test_guest_auth_modal_uses_loading_overlay_for_registration(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('id="loginForm"', false)
            ->assertSee('id="signupForm"', false)
            ->assertSee('name="email"', false)
            ->assertSee('id="loginEmail"', false)
            ->assertSee('id="signupEmail"', false)
            ->assertSee('Email address', false)
            ->assertDontSee('name="identifier"', false)
            ->assertDontSee('id="loginIdentifier"', false)
            ->assertDontSee('id="signupIdentifier"', false)
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

    public function test_google_login_reports_missing_configuration_before_redirect(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => route('auth.google.callback'),
        ]);

        $this->get(route('auth.google.login'))
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in is not configured correctly. Please contact the administrator.',
            ]);
    }

    public function test_google_callback_reports_cancelled_authorization(): void
    {
        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in was cancelled. Please choose a Google account and allow access to continue.',
            ]);
    }

    public function test_google_callback_reports_network_timeout_clearly(): void
    {
        $this->mockGoogleCallbackFailure(new \RuntimeException(
            'cURL error 28: Operation timed out after 20000 milliseconds with 0 bytes received'
        ));

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback'))
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in could not reach Google in time. Please check your connection and try again.',
            ]);
    }

    public function test_google_login_uses_picture_fallback_for_profile_photo(): void
    {
        $user = User::factory()->create([
            'email' => 'google-picture@example.com',
            'google_id' => 'google-picture-id',
            'profile_photo_path' => null,
        ]);

        $avatarUrl = 'https://lh3.googleusercontent.com/a/google-picture-id=s160-c';

        $this->mockGoogleCallback([
            'id' => 'google-picture-id',
            'name' => 'Google Picture',
            'email' => 'google-picture@example.com',
            'avatar' => null,
        ], [
            'picture' => '//lh3.googleusercontent.com/a/google-picture-id=s160-c',
        ]);

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($avatarUrl, $user->fresh()->profile_photo_path);

        $this->actingAs($user->fresh())
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('src="'.$avatarUrl.'"', false);
    }

    public function test_google_login_marks_matching_email_as_verified(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'google-unverified@example.com',
            'google_id' => 'google-unverified-id',
            'status' => 'active',
        ]);

        $this->mockGoogleCallback([
            'id' => 'google-unverified-id',
            'name' => 'Google Verified',
            'email' => 'google-unverified@example.com',
            'avatar' => null,
        ]);

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user->fresh())
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('Email Status')
            ->assertSee('Verified')
            ->assertDontSee('Email verification not completed');
    }

    public function test_google_registration_creates_verified_email_account(): void
    {
        $this->mockGoogleCallback([
            'id' => 'google-register-id',
            'name' => 'Google Register',
            'email' => 'google-register@example.com',
            'avatar' => null,
        ]);

        $this->withSession(['google_auth_intent' => 'register'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('terms.acceptance.show'))
            ->assertSessionHas('registration_success', true);

        $user = User::where('email', 'google-register@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('google-register-id', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_google_login_does_not_replace_uploaded_profile_photo(): void
    {
        $uploadedPhoto = 'data:image/png;base64,manual-upload';

        $user = User::factory()->create([
            'email' => 'manual-photo@example.com',
            'google_id' => 'manual-photo-id',
            'profile_photo_path' => $uploadedPhoto,
        ]);

        $this->mockGoogleCallback([
            'id' => 'manual-photo-id',
            'name' => 'Manual Photo',
            'email' => 'manual-photo@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/a/manual-photo-id=s160-c',
        ]);

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($uploadedPhoto, $user->fresh()->profile_photo_path);
    }

    private function mockGoogleCallback(array $attributes, array $raw = []): void
    {
        $this->configureGoogleOAuth();

        $googleUser = SocialiteUser::fake($attributes);
        $googleUser->setRaw(array_merge($attributes, $raw));

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('setHttpClient')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }

    private function mockGoogleCallbackFailure(\Throwable $exception): void
    {
        $this->configureGoogleOAuth();

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('setHttpClient')->andReturnSelf();
        $provider->shouldReceive('user')->andThrow($exception);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }

    private function configureGoogleOAuth(): void
    {
        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-client-secret',
            'services.google.redirect' => route('auth.google.callback'),
        ]);
    }
}
