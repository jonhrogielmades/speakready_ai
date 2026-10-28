<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

        $this->assertStringContainsString("const CACHE_NAME = 'speakready-pwa-v44';", $worker);
        $this->assertStringContainsString("const CLIENT_REFRESH_PARAM = 'sr_sw_refresh';", $worker);
        $this->assertStringContainsString("const CLIENT_REFRESH_VALUE = 'pwa-v44';", $worker);
        $this->assertStringContainsString("const NOTIFICATION_ICON = '/img/notification-icon-192.png?v=3';", $worker);
        $this->assertStringContainsString("const NOTIFICATION_BADGE = '/img/notification-badge.png?v=4';", $worker);
        $this->assertStringContainsString("const DEFAULT_NOTIFICATION_ACTIONS", $worker);
        $this->assertStringContainsString('notificationVibration(payload)', $worker);
        $this->assertStringContainsString('requireInteraction: payload.requireInteraction === true', $worker);
        $this->assertStringNotContainsString('SPEAKREADY_PUSH_NOTIFICATION_RECEIVED', $worker);
        $this->assertStringNotContainsString('SHOW_ADMIN_ACTIVITY_NOTIFICATION', $worker);
        $this->assertStringContainsString("self.clients.matchAll({ type: 'window', includeUncontrolled: true })", $worker);
        $this->assertStringContainsString('client.navigate(nextUrl)', $worker);
        $this->assertStringContainsString('refreshControlledClients()', $worker);
    }

    public function test_pwa_notification_badge_uses_transparent_logo_mask(): void
    {
        $badgePath = public_path('img/notification-badge.png');
        $badgeSize = getimagesize($badgePath);

        $this->assertSame([96, 96], array_slice($badgeSize, 0, 2));

        $badge = imagecreatefrompng($badgePath);
        $transparentPixels = 0;
        $opaquePixels = 0;

        for ($y = 0; $y < imagesy($badge); $y++) {
            for ($x = 0; $x < imagesx($badge); $x++) {
                $alpha = (imagecolorat($badge, $x, $y) >> 24) & 0x7F;

                if ($alpha > 100) {
                    $transparentPixels++;
                } else {
                    $opaquePixels++;
                }
            }
        }

        imagedestroy($badge);

        $this->assertGreaterThan(4000, $transparentPixels);
        $this->assertGreaterThan(2000, $opaquePixels);
        $this->assertLessThan(5000, $opaquePixels);
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

    public function test_required_email_verification_blocks_login_without_auto_verifying(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();
        Setting::setVal('acc_verify_email', true, 'account', 'boolean');

        $user = User::factory()->unverified()->create([
            'username' => 'legacy-local-user',
            'email' => 'legacy-local@example.com',
            'google_id' => null,
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->post(route('login'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertSessionHasErrors([
            'username' => 'Recovery email verification is required before you can sign in. Check your inbox for a new verification link.',
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertGuest();
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

    public function test_local_login_accepts_only_a_local_username_not_a_recovery_email_or_google_account(): void
    {
        $localUser = User::factory()->create([
            'username' => 'username-only-user',
            'email' => 'recovery-only@example.com',
            'password' => Hash::make('correct-password'),
        ]);
        $googleUser = User::factory()->google()->create([
            'username' => 'google-generated-username',
            'email' => 'google-only@example.com',
            'password' => Hash::make('known-test-password'),
        ]);

        $this->post(route('login'), [
            'username' => $localUser->email,
            'password' => 'correct-password',
        ])->assertSessionHasErrors('username');

        $this->post(route('login'), [
            'username' => $googleUser->username,
            'password' => 'known-test-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_google_account_can_request_reactivation_by_its_google_email_without_enabling_email_login(): void
    {
        $googleUser = User::factory()->google()->create([
            'email' => 'inactive-google@example.com',
            'status' => 'inactive',
        ]);

        $this->post(route('request.reactivation'), [
            'username' => $googleUser->email,
        ])->assertSessionHas('success', 'Your reactivation request has been sent to the admin.');

        $this->assertNotNull($googleUser->fresh()->reactivation_requested_at);

        $this->post(route('login'), [
            'username' => $googleUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('username');
    }

    public function test_registration_creates_a_separate_local_account_and_shows_success_only_on_terms_page(): void
    {
        config([
            'services.brevo.api_key' => null,
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);
        Notification::fake();
        Socialite::shouldReceive('driver')->never();

        $registration = [
            'name' => 'New Interview User',
            'username' => 'new-interview-user',
            'email' => 'new-interview-user@outlook.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $registration);

        $response->assertRedirect(route('terms.acceptance.show'))
            ->assertSessionHas('registration_success', true)
            ->assertSessionHas('success', 'Registration successful. Welcome to SpeakReady AI!')
            ->assertSessionHas('recovery_code')
            ->assertSessionMissing('google_auth_intent')
            ->assertSessionMissing('google_registration_pending');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'New Interview User',
            'username' => 'new-interview-user',
            'email' => 'new-interview-user@outlook.com',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);
        $user = User::where('username', 'new-interview-user')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotNull($user->password_recovery_code_hash);
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
        ]);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get(route('terms.acceptance.show'))
            ->assertOk()
            ->assertSee('Review the Terms and Conditions')
            ->assertSee('id="srTermsSuccessModal"', false)
            ->assertSee('data-terms-success-modal', false)
            ->assertSee('Registration successful. Welcome to SpeakReady AI!')
            ->assertDontSee('terms-flash', false);

        $this->post(route('terms.acceptance.store'), [
            'terms_accepted' => '1',
            'recovery_code_saved' => '1',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionMissing('registration_success')
            ->assertSessionMissing('success');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('id="srFlashModal"', false)
            ->assertDontSee('Registration successful. Welcome to SpeakReady AI!');
    }

    public function test_registration_requires_a_valid_recovery_email_and_accepts_non_google_providers(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $invalidEmails = [
            'not-an-email',
            'candidate@localhost',
            'candidate@example',
        ];

        foreach ($invalidEmails as $index => $email) {
            $username = 'invalid-email-user-'.$index;

            $this->from('/')
                ->post(route('register'), [
                    'name' => 'Recovery Email Candidate',
                    'username' => $username,
                    'email' => $email,
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ])
                ->assertSessionHasErrors([
                    'email' => 'Enter a valid recovery email address.',
                ]);

            $this->assertDatabaseMissing('users', [
                'username' => $username,
            ]);
        }

        $this->post(route('register'), [
            'name' => 'Valid Recovery Candidate',
            'username' => 'valid-recovery-email-user',
            'email' => '  Valid.Recovery@OUTLOOK.COM  ',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertRedirect(route('terms.acceptance.show'));

        $user = User::where('username', 'valid-recovery-email-user')->firstOrFail();

        $this->assertSame('valid.recovery@outlook.com', $user->email);
        $this->assertSame(User::AUTH_METHOD_LOCAL, $user->auth_method);
        $this->assertNull($user->google_id);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
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
            ->assertSee('id="signupEmail"', false)
            ->assertSee('pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"', false)
            ->assertSee('Enter a valid recovery email address.')
            ->assertSee('This address is used only for password recovery. We will email a verification link to confirm you own it.')
            ->assertDontSee('id="signupEmailFeedback"', false)
            ->assertDontSee('Gmail recovery email')
            ->assertSee('action="'.route('register').'"', false)
            ->assertSee(route('auth.google.register'), false)
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

    public function test_google_login_marks_its_own_google_account_email_as_verified(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'google-unverified@example.com',
            'google_id' => 'google-unverified-id',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
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

    public function test_google_login_does_not_auto_register_a_matching_local_recovery_email(): void
    {
        $user = User::factory()->unverified()->create([
            'username' => 'matching-local-google-user',
            'email' => 'matching-local-google@example.com',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'status' => 'active',
        ]);

        $this->mockGoogleCallback([
            'id' => 'selected-google-account-id',
            'name' => 'Matching Google Account',
            'email' => 'matching-local-google@example.com',
            'avatar' => null,
        ]);

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect('/?auth_modal=login&auth_alert=google_login_unregistered')
            ->assertSessionHasErrors([
                'email' => 'This Google account is not registered. Please register first.',
            ]);

        $updatedUser = $user->fresh();
        $this->assertNull($updatedUser->google_id);
        $this->assertNull($updatedUser->email_verified_at);
        $this->assertSame(User::AUTH_METHOD_LOCAL, $updatedUser->auth_method);
        $this->assertGuest();
    }

    public function test_legacy_local_account_with_a_stale_google_id_still_uses_only_local_authentication(): void
    {
        $localUser = User::factory()->create([
            'username' => 'legacy-separated-local',
            'email' => 'legacy-separated-local@example.com',
            'password' => Hash::make('local-password'),
            'google_id' => 'stale-google-id',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);
        ActivityLog::create([
            'user_id' => $localUser->id,
            'action' => 'user_registered',
            'description' => "{$localUser->name} ({$localUser->username}) registered a new local account.",
            'ip_address' => '127.0.0.1',
        ]);

        $this->mockGoogleCallback([
            'id' => 'stale-google-id',
            'name' => 'Stale Google Identity',
            'email' => $localUser->email,
            'avatar' => null,
        ]);

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect('/?auth_modal=login&auth_alert=google_login_unregistered')
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login'), [
            'username' => $localUser->username,
            'password' => 'local-password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('user.account'))
            ->assertOk()
            ->assertSee('id="accountPasswordForm"', false)
            ->assertSee('id="password-recovery-code"', false);

        $this->post(route('user.account.delete'), [
            'delete_password' => 'local-password',
        ])->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $localUser->id]);
    }

    public function test_google_registration_can_use_a_local_recovery_email_as_a_separate_account(): void
    {
        $localUser = User::factory()->unverified()->create([
            'username' => 'local-recovery-owner',
            'email' => 'owned-recovery@example.com',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $this->mockGoogleCallback([
            'id' => 'separate-google-registration-id',
            'name' => 'Separate Google Registration',
            'email' => $localUser->email,
            'avatar' => null,
        ]);

        $this->withSession(['google_auth_intent' => 'register'])
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect(route('terms.acceptance.show'))
            ->assertSessionHas('registration_success', true);

        $googleUser = User::where('google_id', 'separate-google-registration-id')->firstOrFail();
        $this->assertSame($localUser->email, $googleUser->email);
        $this->assertSame(User::AUTH_METHOD_GOOGLE, $googleUser->auth_method);
        $this->assertSame(1, User::where('email', $localUser->email)->where('auth_method', User::AUTH_METHOD_LOCAL)->count());
        $this->assertSame(1, User::where('email', $localUser->email)->where('auth_method', User::AUTH_METHOD_GOOGLE)->count());
        $this->assertNull($localUser->fresh()->google_id);

        $googleUser->forceFill([
            'terms_accepted_at' => now(),
            'terms_version' => User::TERMS_VERSION,
        ])->save();
        Auth::logout();

        $this->mockGoogleCallback([
            'id' => 'separate-google-registration-id',
            'name' => 'Separate Google Registration',
            'email' => $localUser->email,
            'avatar' => null,
        ]);

        $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($googleUser->fresh());
    }

    public function test_local_registration_can_use_a_google_account_email_as_recovery_email(): void
    {
        $googleUser = User::factory()->google()->create([
            'email' => 'owned-google-email@example.com',
        ]);

        $this->post(route('register'), [
            'name' => 'Local Collision Candidate',
            'username' => 'local-collision-candidate',
            'email' => $googleUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('terms.acceptance.show'));

        $localUser = User::where('username', 'local-collision-candidate')->firstOrFail();
        $this->assertSame($googleUser->email, $localUser->email);
        $this->assertSame(User::AUTH_METHOD_LOCAL, $localUser->auth_method);
        $this->assertSame(1, User::where('email', $googleUser->email)->where('auth_method', User::AUTH_METHOD_LOCAL)->count());
        $this->assertSame(1, User::where('email', $googleUser->email)->where('auth_method', User::AUTH_METHOD_GOOGLE)->count());
        $this->assertSame(User::AUTH_METHOD_GOOGLE, $googleUser->fresh()->auth_method);
        $this->assertAuthenticatedAs($localUser);
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
            ->assertSessionHas('registration_success', true)
            ->assertSessionMissing('recovery_code');

        $user = User::where('email', 'google-register@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('google-register-id', $user->google_id);
        $this->assertSame(User::AUTH_METHOD_GOOGLE, $user->auth_method);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password_recovery_code_hash);
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
            'recovery_code_saved' => '1',
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
