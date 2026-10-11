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
            'username' => 'pwa-user',
            'email' => 'pwa-user@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'pwa-user',
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

    public function test_pwa_mobile_start_url_sends_authenticated_user_to_mobile_dashboard(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/?sr_layout=mobile&sr_is_mobile=1&sr_pwa=1')
            ->assertRedirect(route('dashboard', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']));
    }

    public function test_pwa_manifest_starts_in_mobile_layout(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);

        $this->assertSame('/?sr_layout=mobile&sr_is_mobile=1&sr_pwa=1', $manifest['start_url'] ?? null);
    }

    public function test_pwa_service_worker_cache_version_is_bumped_for_notification_icon_refresh(): void
    {
        $worker = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("const CACHE_NAME = 'speakready-pwa-v39';", $worker);
        $this->assertStringContainsString("const CLIENT_REFRESH_PARAM = 'sr_sw_refresh';", $worker);
        $this->assertStringContainsString("const CLIENT_REFRESH_VALUE = 'pwa-v39';", $worker);
        $this->assertStringContainsString("const NOTIFICATION_ICON = '/img/notification-icon-192.png?v=3';", $worker);
        $this->assertStringContainsString("const NOTIFICATION_BADGE = '/img/notification-badge.png?v=3';", $worker);
        $this->assertStringContainsString("self.clients.matchAll({ type: 'window', includeUncontrolled: true })", $worker);
        $this->assertStringContainsString('client.navigate(nextUrl)', $worker);
        $this->assertStringContainsString('refreshControlledClients()', $worker);
    }

    public function test_password_login_can_opt_out_of_remembering_the_device(): void
    {
        User::factory()->create([
            'username' => 'shared-device',
            'email' => 'shared-device@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $response = $this->post(route('login'), [
            'username' => 'shared-device',
            'password' => 'password',
            'remember' => '0',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertCookieMissing(Auth::guard()->getRecallerName());
    }

    public function test_password_login_reports_unregistered_accounts_and_password_mismatches(): void
    {
        User::factory()->create([
            'username' => 'known-user',
            'email' => 'known-user@example.com',
            'password' => Hash::make('correct-password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->post(route('login'), [
            'username' => 'missing-user',
            'password' => 'correct-password',
        ])->assertSessionHasErrors([
            'username' => 'This account is not registered. Please register first.',
        ]);

        $this->post(route('login'), [
            'username' => 'known-user',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'password' => 'The password do not match our records.',
        ]);

        $this->post(route('login'), [
            'username' => 'missing-user',
            'password' => 'not-a-known-password',
        ])->assertSessionHasErrors([
            'username' => 'This account is not registered. Please register first.',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('This account is not registered. Please register first.')
            ->assertSee('id="fLogin"', false);

        $this->assertGuest();
    }

    public function test_registration_logs_user_in_and_flashes_success_alert(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'New Interview User',
            'username' => 'new-interview-user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('terms.acceptance.show'))
            ->assertSessionHas('registration_success', true)
            ->assertSessionHas('success', 'Registration successful. Welcome to SpeakReady AI!');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'New Interview User',
            'username' => 'new-interview-user',
            'email' => null,
        ]);
        $this->assertDatabaseHas('profiles', [
            'user_id' => User::where('username', 'new-interview-user')->value('id'),
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
            ->assertSee('name="username"', false)
            ->assertSee('id="loginUsername"', false)
            ->assertSee('id="signupUsername"', false)
            ->assertSee('Username', false)
            ->assertDontSee('id="loginEmail"', false)
            ->assertDontSee('id="signupEmail"', false)
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

    public function test_registration_validation_alert_stays_on_register_tab_when_name_is_missing(): void
    {
        $this->post(route('register'), [
            'auth_tab' => 'signup',
            'name' => '',
            'username' => 'missing-name-user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('name')
            ->assertSessionHas('_old_input.auth_tab', 'signup');

        $this->get('/')
            ->assertOk()
            ->assertSee('id="tabSignup" role="tab" aria-selected="true"', false)
            ->assertSee('The name field is required.');
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

    public function test_google_register_reports_missing_configuration_on_register_tab(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => route('auth.google.callback'),
        ]);

        $this->get(route('auth.google.register'))
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in is not configured correctly. Please contact the administrator.',
            ])
            ->assertSessionHas('_old_input.auth_tab', 'signup');

        $this->get('/')
            ->assertOk()
            ->assertSee('id="tabSignup" role="tab" aria-selected="true"', false)
            ->assertSee('Google sign-in is not configured correctly. Please contact the administrator.');
    }

    public function test_google_callback_reports_cancelled_authorization(): void
    {
        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in was cancelled. Please choose a Google account and allow access to continue.',
            ]);
    }

    public function test_google_register_callback_errors_open_register_tab(): void
    {
        $this->withSession(['google_auth_intent' => 'register'])
            ->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in was cancelled. Please choose a Google account and allow access to continue.',
            ])
            ->assertSessionHas('_old_input.auth_tab', 'signup');

        $this->get('/')
            ->assertOk()
            ->assertSee('id="tabSignup" role="tab" aria-selected="true"', false)
            ->assertSee('Google sign-in was cancelled. Please choose a Google account and allow access to continue.');
    }

    public function test_google_callback_requires_an_authorization_code(): void
    {
        $this->configureGoogleOAuth();

        $this->get(route('auth.google.callback'))
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in expired. Please start Google login again.',
            ]);
    }

    public function test_google_callback_errors_preserve_mobile_layout_hint(): void
    {
        $this->configureGoogleOAuth();

        $this->withSession(['google_auth_layout' => 'mobile'])
            ->get(route('auth.google.callback'))
            ->assertRedirect('/?sr_layout=mobile&sr_is_mobile=1')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in expired. Please start Google login again.',
            ]);
    }

    public function test_google_callback_reports_network_timeout_clearly(): void
    {
        $this->mockGoogleCallbackFailure(new \RuntimeException(
            'cURL error 28: Operation timed out after 20000 milliseconds with 0 bytes received'
        ));

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect('/')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in could not reach Google in time. Please check your connection and try again.',
            ]);
    }

    public function test_google_login_reports_unregistered_account_on_login_tab(): void
    {
        $this->mockGoogleCallback([
            'id' => 'missing-google-login-id',
            'name' => 'Missing Google Login',
            'email' => 'missing-google-login@example.com',
            'avatar' => null,
        ]);

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect('/?auth_modal=login&auth_alert=google_login_unregistered')
            ->assertSessionHasErrors([
                'email' => 'This Google account is not registered. Please register first.',
            ])
            ->assertSessionHas('_old_input.auth_tab', 'login')
            ->assertSessionHas('_old_input.email', 'missing-google-login@example.com');

        $this->get('/?auth_modal=login&auth_alert=google_login_unregistered')
            ->assertOk()
            ->assertSee('id="tabLogin" role="tab" aria-selected="true"', false)
            ->assertSee("swTab('login')", false)
            ->assertSee('This Google account is not registered. Please register first.');
    }

    public function test_google_unregistered_query_fallback_opens_login_modal_without_flashed_errors(): void
    {
        $this->get('/?auth_modal=login&auth_alert=google_login_unregistered')
            ->assertOk()
            ->assertSee('id="tabLogin" role="tab" aria-selected="true"', false)
            ->assertSee("swTab('login')", false)
            ->assertSee('This Google account is not registered. Please register first.');
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
            ->get(route('auth.google.callback', ['code' => 'test-code']))
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
            ->get(route('auth.google.callback', ['code' => 'test-code']))
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
            ->get(route('auth.google.callback', ['code' => 'test-code']))
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
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect(route('dashboard'));

        $this->assertSame($uploadedPhoto, $user->fresh()->profile_photo_path);
    }

    public function test_google_login_preserves_mobile_layout_after_oauth_callback(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile-google-login@example.com',
            'google_id' => 'mobile-google-login-id',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->mockGoogleCallback([
            'id' => 'mobile-google-login-id',
            'name' => 'Mobile Google Login',
            'email' => 'mobile-google-login@example.com',
            'avatar' => null,
        ]);

        $desktopLikeUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withSession([
            'google_auth_intent' => 'login',
            'google_auth_layout' => 'mobile',
        ])
            ->withHeader('User-Agent', $desktopLikeUserAgent)
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect(route('dashboard', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']));

        $this->assertAuthenticatedAs($user);

        $this->get(route('dashboard', ['sr_layout' => 'mobile']))
            ->assertOk()
            ->assertSee('class="user-mobile-shell mobile-shell"', false)
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertDontSee('class="db-sidebar"', false);
    }

    public function test_google_registration_preserves_mobile_layout_after_oauth_callback(): void
    {
        $this->mockGoogleCallback([
            'id' => 'mobile-google-register-id',
            'name' => 'Mobile Google Register',
            'email' => 'mobile-google-register@example.com',
            'avatar' => null,
        ]);

        $desktopLikeUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';

        $this->withSession([
            'google_auth_intent' => 'register',
            'google_auth_layout' => 'mobile',
        ])
            ->withHeader('User-Agent', $desktopLikeUserAgent)
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect(route('terms.acceptance.show', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']))
            ->assertSessionHas('registration_success', true);

        $this->assertAuthenticated();

        $this->get(route('terms.acceptance.show', ['sr_layout' => 'mobile']))
            ->assertOk()
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('terms-mobile-submit', false);
    }

    public function test_google_login_uses_signed_oauth_state_when_session_layout_is_stale(): void
    {
        $user = User::factory()->create([
            'email' => 'state-mobile-login@example.com',
            'google_id' => 'state-mobile-login-id',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $desktopLikeUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';
        $state = $this->captureGoogleRedirectState(
            route('auth.google.login', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']),
            $desktopLikeUserAgent
        );

        $this->mockGoogleCallback([
            'id' => 'state-mobile-login-id',
            'name' => 'State Mobile Login',
            'email' => 'state-mobile-login@example.com',
            'avatar' => null,
        ]);

        $this->withSession([
            'google_auth_intent' => 'login',
            'google_auth_layout' => 'desktop',
            'sr_layout_preference' => 'desktop',
        ])
            ->withHeader('User-Agent', $desktopLikeUserAgent)
            ->get(route('auth.google.callback', ['code' => 'test-code', 'state' => $state]))
            ->assertRedirect(route('dashboard', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']));

        $this->assertAuthenticatedAs($user);
    }

    public function test_google_registration_uses_signed_oauth_state_when_session_intent_is_stale(): void
    {
        $desktopLikeUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';
        $state = $this->captureGoogleRedirectState(
            route('auth.google.register', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']),
            $desktopLikeUserAgent
        );

        $this->mockGoogleCallback([
            'id' => 'state-mobile-register-id',
            'name' => 'State Mobile Register',
            'email' => 'state-mobile-register@example.com',
            'avatar' => null,
        ]);

        $this->withSession([
            'google_auth_intent' => 'login',
            'google_auth_layout' => 'desktop',
            'sr_layout_preference' => 'desktop',
        ])
            ->withHeader('User-Agent', $desktopLikeUserAgent)
            ->get(route('auth.google.callback', ['code' => 'test-code', 'state' => $state]))
            ->assertRedirect(route('terms.acceptance.show', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']))
            ->assertSessionHas('registration_success', true);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'state-mobile-register@example.com',
            'google_id' => 'state-mobile-register-id',
        ]);

        $this->post(route('terms.acceptance.store'), [
            'terms_accepted' => '1',
        ])->assertRedirect(route('dashboard', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']));
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

    private function captureGoogleRedirectState(string $url, string $userAgent): string
    {
        $this->configureGoogleOAuth();

        $state = null;
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function (array $parameters) use (&$state): bool {
                $state = $parameters['state'] ?? null;

                return is_string($state) && str_starts_with($state, 'sr.');
            }))
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.test/oauth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->withHeader('User-Agent', $userAgent)
            ->get($url)
            ->assertRedirect('https://accounts.google.test/oauth');

        $this->assertIsString($state);
        $this->assertNotSame('', $state);

        return $state;
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
