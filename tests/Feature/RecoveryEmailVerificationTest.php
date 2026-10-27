<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RecoveryEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_registration_requires_real_recovery_email_verification_without_blocking_access(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Verification Candidate',
            'username' => 'verification-candidate',
            'email' => 'verification.candidate@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('terms.acceptance.show'));

        $user = User::where('username', 'verification-candidate')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signed_verification_link_enables_email_recovery(): void
    {
        $user = User::factory()->unverified()->create([
            'username' => 'signed-verification-user',
            'google_id' => null,
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
            ->assertRedirect(route('user.account'))
            ->assertSessionHas('success', 'Recovery email verified. Email password recovery is now available.');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_signed_verification_link_works_without_an_authenticated_session(): void
    {
        $user = User::factory()->unverified()->create([
            'google_id' => null,
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

    public function test_user_can_resend_recovery_email_verification(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'google_id' => null,
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
        ]);

        $this->post(route('password.recovery.send'), [
            'username' => $user->username,
            'email' => $user->email,
        ])->assertSessionHasErrors([
            'email' => 'Verify this recovery email from your account before requesting an emailed recovery code.',
        ]);

        Mail::assertNothingSent();
    }

    public function test_unverified_recovery_email_cannot_receive_password_reset_links(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'unverified-reset@example.com',
            'google_id' => null,
            'password' => Hash::make('password'),
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status', 'If a verified local account matches that email, a password reset link will be sent shortly.');

        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_account_page_offers_resend_for_unverified_local_recovery_email(): void
    {
        $user = User::factory()->unverified()->create([
            'google_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('Email verification not completed')
            ->assertSee(route('verification.send'), false)
            ->assertSee('Verify');
    }
}
