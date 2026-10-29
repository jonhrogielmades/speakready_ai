<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserOnboardingProgress;
use App\Notifications\RecoveryEmailChangeAlert;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class RecoveryEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_registration_sends_a_verification_link_without_connecting_google(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Verification Candidate',
            'username' => 'verification-candidate',
            'email' => 'verification.candidate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('terms.acceptance.show'))
            ->assertSessionHas('registration_success', true)
            ->assertSessionHas('recovery_code');

        $user = User::where('username', 'verification-candidate')->firstOrFail();

        $this->assertSame(User::AUTH_METHOD_LOCAL, $user->auth_method);
        $this->assertNull($user->google_id);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(UserOnboardingProgress::forUser($user)->toStateArray($user)['email_verification_applicable']);
        $this->assertFalse(UserOnboardingProgress::forUser($user)->toStateArray($user)['email_verified']);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_google_registration_is_verified_without_sending_a_recovery_email_link(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();
        $this->mockGoogleCallback([
            'id' => 'verification-candidate-google-id',
            'name' => 'Verification Candidate',
            'email' => 'verification.candidate@gmail.com',
            'avatar' => null,
        ]);

        $this->withSession(['google_auth_intent' => 'register'])
            ->get(route('auth.google.callback', ['code' => 'test-code']))
            ->assertRedirect(route('terms.acceptance.show'));

        $user = User::where('email', 'verification.candidate@gmail.com')->firstOrFail();

        $this->assertSame(User::AUTH_METHOD_GOOGLE, $user->auth_method);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('verification-candidate-google-id', $user->google_id);
        $this->assertFalse(UserOnboardingProgress::forUser($user)->toStateArray($user)['email_verification_applicable']);
        $this->assertTrue(UserOnboardingProgress::forUser($user)->toStateArray($user)['email_verified']);
        $this->assertAuthenticatedAs($user);
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    public function test_signed_verification_link_completes_step_four_and_returns_to_dashboard(): void
    {
        $user = User::factory()->unverified()->create([
            'username' => 'signed-verification-user',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->email),
            ]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('dashboard', ['onboarding' => 'first-steps']))
            ->assertSessionHas('success', 'Recovery email verified. Continue your remaining setup steps.');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $onboarding = UserOnboardingProgress::forUser($user)->toStateArray($user);
        $this->assertTrue($onboarding['email_verification_applicable']);
        $this->assertTrue($onboarding['email_verified']);
        $this->assertFalse($onboarding['first_steps_complete']);

        $response = $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1 of 5 complete')
            ->assertSee('4. Verify your recovery email');
        $this->assertMatchesRegularExpression(
            '/<section\b(?=[^>]*\bis-done\b)(?=[^>]*\bdata-sr-onboarding-step="email_verified")[^>]*>/',
            $response->getContent()
        );
    }

    public function test_signed_verification_link_works_without_an_authenticated_session(): void
    {
        $user = User::factory()->unverified()->create([
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->email),
            ]
        );

        $this->get($verificationUrl)
            ->assertRedirect('/')
            ->assertSessionHas('success', 'Recovery email verified. You can now sign in and use email password recovery.');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertGuest();
    }

    public function test_recovery_email_change_stays_pending_and_alerts_the_verified_old_address(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $user = User::factory()->create([
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'email' => 'verified-old@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('current-password'),
        ]);

        $this->actingAs($user)
            ->post(route('user.account.profile'), [
                'name' => $user->name,
                'email' => 'pending-new@example.com',
                'target_position' => $user->target_position,
                'current_password' => 'current-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated. Verify your new recovery email before using email password recovery.');

        $updated = $user->fresh();
        $this->assertSame('verified-old@example.com', $updated->email);
        $this->assertNotNull($updated->email_verified_at);
        $this->assertSame('pending-new@example.com', $updated->pending_recovery_email);
        $this->assertNotNull($updated->pending_recovery_email_requested_at);

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            fn ($notification, array $channels, User $notifiable): bool => $notifiable->email === 'pending-new@example.com'
        );
        Notification::assertSentOnDemand(
            RecoveryEmailChangeAlert::class,
            fn (RecoveryEmailChangeAlert $notification, array $channels, $notifiable): bool =>
                $notification->stage === 'requested'
                && $notifiable->routeNotificationFor('mail') === 'verified-old@example.com'
        );
    }

    public function test_verifying_pending_recovery_email_atomically_promotes_it_and_alerts_old_address(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'email' => 'verified-old@example.com',
            'email_verified_at' => now(),
            'pending_recovery_email' => 'verified-new@example.com',
            'pending_recovery_email_requested_at' => now(),
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1('verified-new@example.com'),
            ]
        );

        $this->actingAs($user)->get($verificationUrl)->assertRedirect();

        $updated = $user->fresh();
        $this->assertSame('verified-new@example.com', $updated->email);
        $this->assertNotNull($updated->email_verified_at);
        $this->assertNull($updated->pending_recovery_email);
        $this->assertNull($updated->pending_recovery_email_requested_at);

        Notification::assertSentOnDemand(
            RecoveryEmailChangeAlert::class,
            fn (RecoveryEmailChangeAlert $notification, array $channels, $notifiable): bool =>
                $notification->stage === 'completed'
                && $notifiable->routeNotificationFor('mail') === 'verified-old@example.com'
        );
    }

    public function test_current_verified_address_remains_the_only_reset_address_while_change_is_pending(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'email' => 'still-active@example.com',
            'email_verified_at' => now(),
            'pending_recovery_email' => 'not-active-yet@example.com',
            'pending_recovery_email_requested_at' => now(),
        ]);

        $this->post(route('password.email'), ['email' => 'still-active@example.com'])
            ->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => 'not-active-yet@example.com'])
            ->assertSessionHas('status');

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_user_can_resend_recovery_email_verification(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Verification link sent. Check your inbox and spam folder.');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_recovery_email_verification_can_be_sent_through_brevo(): void
    {
        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.endpoint' => 'https://api.brevo.test/v3/smtp/email',
            'mail.from.address' => 'noreply@speakready.test',
            'mail.from.name' => 'SpeakReady AI',
        ]);
        Http::fake([
            'api.brevo.test/*' => Http::response(['messageId' => 'verification-message'], 201),
        ]);

        $user = User::factory()->unverified()->create([
            'email' => 'brevo-verification@example.com',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertSessionHas('status', 'Verification link sent. Check your inbox and spam folder.');

        Http::assertSent(function ($request) use ($user): bool {
            return $request->url() === 'https://api.brevo.test/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-brevo-key')
                && data_get($request->data(), 'to.0.email') === $user->email
                && str_contains((string) data_get($request->data(), 'textContent'), 'Verify your SpeakReady AI recovery email')
                && str_contains((string) data_get($request->data(), 'textContent'), '/email/verify/');
        });
    }

    public function test_unverified_recovery_email_cannot_receive_recovery_codes(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'username' => 'unverified-recovery-user',
            'email' => 'unverified-recovery@example.com',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $this->post(route('password.recovery.send'), [
            'username' => $user->username,
            'email' => $user->email,
        ])->assertSessionHasNoErrors()
            ->assertSessionHas(
                'status',
                'If the username and verified recovery email match a local account, a recovery code will be sent shortly.'
            )
            ->assertSessionHas('recovery_code_sent', true);

        Mail::assertNothingSent();
    }

    public function test_unverified_recovery_email_cannot_receive_password_reset_links(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'unverified-reset@example.com',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'password' => Hash::make('password'),
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status', 'If a verified local account matches that email, a password reset link will be sent shortly.');

        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_google_account_cannot_use_local_recovery_email_verification_endpoints(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'google_id' => 'google-verification-guard-id',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertSessionHas('error', 'Google Login accounts do not use local recovery-email verification.');

        Notification::assertNothingSent();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->email),
            ]
        );

        $this->get($verificationUrl)->assertNotFound();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_account_page_offers_resend_for_unverified_local_recovery_email(): void
    {
        $user = User::factory()->unverified()->create([
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $this->actingAs($user)
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('Email verification not completed')
            ->assertSee(route('verification.send'), false)
            ->assertSee('Verify');
    }

    private function mockGoogleCallback(array $attributes): void
    {
        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-client-secret',
            'services.google.redirect' => route('auth.google.callback'),
        ]);

        $googleUser = SocialiteUser::fake($attributes);
        $googleUser->setRaw($attributes);

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('setHttpClient')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
