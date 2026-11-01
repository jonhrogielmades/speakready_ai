<?php

namespace Tests\Feature;

use App\Mail\PasswordRecoveryCodeMail;
use App\Models\User;
use App\Support\PasswordRecovery;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_pages_are_available(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Reset your password');

        $this->get(route('password.reset', [
            'token' => 'test-token',
            'email' => 'person@example.com',
        ]))
            ->assertOk()
            ->assertSee('Create a new password')
            ->assertSee('person@example.com');
    }

    public function test_forgot_password_page_uses_username_and_recovery_email(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('name="email"', false)
            ->assertSee(route('password.recovery.send'), false)
            ->assertSee('Send Recovery Code');
    }

    public function test_username_recovery_resets_a_local_account_without_email_and_invalidates_the_code(): void
    {
        $recoveryCode = 'ABCD-EFGH-IJKL-MNOP';
        $user = User::factory()->create([
            'username' => 'local-recovery-user',
            'email' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'password' => Hash::make('old-password'),
            'password_recovery_code_hash' => PasswordRecovery::hash($recoveryCode),
            'password_recovery_code_generated_at' => now(),
            'pending_recovery_email' => 'unverified-change@example.com',
            'pending_recovery_email_requested_at' => now(),
        ]);

        $response = $this->post(route('password.recovery.reset'), [
            'username' => ' LOCAL-RECOVERY-USER ',
            'recovery_code' => 'abcdefghijklmnop',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response
            ->assertRedirect('/')
            ->assertSessionHas('success', 'Your password has been reset. You can now sign in with your username.');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertNull($user->fresh()->password_recovery_code_hash);
        $this->assertNull($user->fresh()->pending_recovery_email);
        $this->assertNull($user->fresh()->pending_recovery_email_requested_at);

        $this->from(route('password.request'))->post(route('password.recovery.reset'), [
            'username' => 'local-recovery-user',
            'recovery_code' => $recoveryCode,
            'password' => 'AnotherPassword123!',
            'password_confirmation' => 'AnotherPassword123!',
        ])->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('recovery_code');
    }

    public function test_local_user_can_request_a_new_recovery_code_with_username_and_matching_email(): void
    {
        config(['services.brevo.api_key' => null]);
        Mail::fake();

        $user = User::factory()->create([
            'username' => 'email-recovery-user',
            'email' => 'recovery@example.com',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'password_recovery_code_hash' => PasswordRecovery::hash('OLD-CODE-ONLY-1234'),
            'password_recovery_code_generated_at' => now()->subDay(),
            'password_recovery_code_expires_at' => null,
        ]);

        $response = $this->post(route('password.recovery.send'), [
            'username' => ' EMAIL-RECOVERY-USER ',
            'email' => ' RECOVERY@EXAMPLE.COM ',
        ]);

        $response
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'If the username and verified recovery email match a local account, a recovery code will be sent shortly.')
            ->assertSessionHas('recovery_code_sent', true);

        Mail::assertSent(PasswordRecoveryCodeMail::class, function (PasswordRecoveryCodeMail $mail) use ($user): bool {
            return $mail->hasTo($user->email)
                && preg_match('/^[A-Z0-9]{4}(?:-[A-Z0-9]{4}){3}$/', $mail->recoveryCode) === 1;
        });

        $user->refresh();
        $sentMail = null;
        Mail::assertSent(PasswordRecoveryCodeMail::class, function (PasswordRecoveryCodeMail $mail) use (&$sentMail): bool {
            $sentMail = $mail;

            return true;
        });

        $this->assertNotNull($user->password_recovery_code_expires_at);
        $this->assertTrue(PasswordRecovery::check($sentMail->recoveryCode, $user->password_recovery_code_hash));
    }

    public function test_recovery_code_request_does_not_reveal_whether_an_account_exists(): void
    {
        Mail::fake();

        $this->post(route('password.recovery.send'), [
            'username' => 'unknown-user',
            'email' => 'unknown@example.com',
        ])->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas(
                'status',
                'If the username and verified recovery email match a local account, a recovery code will be sent shortly.'
            )
            ->assertSessionHas('recovery_code_sent', true);

        Mail::assertNothingSent();
    }

    public function test_recovery_code_email_renders_without_laravel_markdown_components(): void
    {
        $user = User::factory()->create([
            'name' => 'Recovery Candidate',
            'email' => 'candidate@example.com',
        ]);

        $renderedMail = (new PasswordRecoveryCodeMail($user, 'ABCD-EFGH-IJKL-MNOP', 15))->render();

        $this->assertStringContainsString('Password recovery code', $renderedMail);
        $this->assertStringContainsString('ABCD-EFGH-IJKL-MNOP', $renderedMail);
        $this->assertStringContainsString('Recovery Candidate', $renderedMail);
    }

    public function test_failed_recovery_code_delivery_does_not_leave_an_usable_code(): void
    {
        config(['services.brevo.api_key' => null]);

        $user = User::factory()->create([
            'username' => 'failed-delivery-user',
            'email' => 'failed-delivery@example.com',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'password_recovery_code_hash' => null,
            'password_recovery_code_generated_at' => null,
            'password_recovery_code_expires_at' => null,
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('SMTP authentication failed'));

        $this->post(route('password.recovery.send'), [
            'username' => $user->username,
            'email' => $user->email,
        ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        $user->refresh();
        $this->assertNull($user->password_recovery_code_hash);
        $this->assertNull($user->password_recovery_code_generated_at);
        $this->assertNull($user->password_recovery_code_expires_at);
    }

    public function test_local_recovery_code_can_be_sent_through_brevo(): void
    {
        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.endpoint' => 'https://api.brevo.test/v3/smtp/email',
            'mail.from.address' => 'capstonespeakreadyai@gmail.com',
            'mail.from.name' => 'SpeakReady AI',
        ]);

        Http::fake([
            'api.brevo.test/*' => Http::response(['messageId' => 'recovery-message'], 201),
        ]);

        $user = User::factory()->create([
            'username' => 'brevo-recovery-user',
            'email' => 'brevo-recovery@example.com',
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $this->post(route('password.recovery.send'), [
            'username' => $user->username,
            'email' => $user->email,
        ])->assertSessionHas('recovery_code_sent', true);

        Http::assertSent(function ($request) use ($user): bool {
            return $request->url() === 'https://api.brevo.test/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-brevo-key')
                && data_get($request->data(), 'to.0.email') === $user->email
                && str_contains((string) data_get($request->data(), 'textContent'), 'recovery code');
        });
    }

    public function test_google_account_does_not_receive_a_local_recovery_code(): void
    {
        config(['services.brevo.api_key' => null]);
        Mail::fake();

        $user = User::factory()->create([
            'username' => 'google-only-recovery',
            'email' => 'google@example.com',
            'google_id' => 'google-id-123',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
        ]);

        $this->post(route('password.recovery.send'), [
            'username' => $user->username,
            'email' => $user->email,
        ])->assertSessionHas(
            'status',
            'If the username and verified recovery email match a local account, a recovery code will be sent shortly.'
        )->assertSessionHas('recovery_code_sent', true);

        Mail::assertNothingSent();
    }

    public function test_email_requested_recovery_code_expires(): void
    {
        $user = User::factory()->create([
            'username' => 'expired-recovery-user',
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'password' => Hash::make('old-password'),
            'password_recovery_code_hash' => PasswordRecovery::hash('ABCD-EFGH-IJKL-MNOP'),
            'password_recovery_code_generated_at' => now()->subMinutes(16),
            'password_recovery_code_expires_at' => now()->subMinute(),
        ]);

        $this->from(route('password.request'))->post(route('password.recovery.reset'), [
            'username' => $user->username,
            'recovery_code' => 'ABCD-EFGH-IJKL-MNOP',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('recovery_code');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_authenticated_user_can_rotate_their_recovery_code(): void
    {
        $user = User::factory()->create([
            'username' => 'rotate-recovery-user',
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'password' => Hash::make('current-password'),
            'password_recovery_code_hash' => null,
        ]);

        $response = $this->actingAs($user)->post(route('user.account.recovery-code'), [
            'current_password' => 'current-password',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('recovery_code', fn ($code): bool => preg_match('/^[A-Z0-9]{4}(?:-[A-Z0-9]{4}){3}$/', (string) $code) === 1);

        $newCode = (string) $response->getSession()->get('recovery_code');
        $this->assertTrue(PasswordRecovery::check($newCode, $user->fresh()->password_recovery_code_hash));
    }

    public function test_authenticated_google_user_cannot_generate_a_local_recovery_code(): void
    {
        $user = User::factory()->create([
            'username' => 'google-recovery-user',
            'google_id' => 'google-user-123',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
            'password' => Hash::make('unavailable-google-password'),
            'password_recovery_code_hash' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('user.account'))
            ->post(route('user.account.recovery-code'));

        $response
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'recovery_code' => 'Google Login accounts do not use local password recovery codes.',
            ])
            ->assertSessionMissing('recovery_code');

        $this->assertNull($user->fresh()->password_recovery_code_hash);
    }

    public function test_google_account_page_hides_local_password_and_recovery_controls(): void
    {
        $user = User::factory()->google()->create([
            'username' => 'google-security-ui',
            'email' => 'google-security-ui@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('Google Sign-in')
            ->assertSee('This is a Google Login account.')
            ->assertDontSee('id="accountPasswordForm"', false)
            ->assertDontSee('id="password-recovery-code"', false);
    }

    public function test_google_account_cannot_change_a_local_password(): void
    {
        $user = User::factory()->google()->create([
            'username' => 'google-password-update',
            'password' => Hash::make('unchanged-google-password'),
        ]);

        $this->actingAs($user)
            ->post(route('user.account.password'), [
                'current_password' => 'unchanged-google-password',
                'new_password' => 'NewLocalPassword1!',
                'confirm_password' => 'NewLocalPassword1!',
            ])
            ->assertSessionHasErrors([
                'current_password' => 'Google Login accounts do not have a local password.',
            ]);

        $this->assertTrue(Hash::check('unchanged-google-password', $user->fresh()->password));
    }

    public function test_google_account_cannot_replace_its_google_email_with_a_recovery_email(): void
    {
        $user = User::factory()->google()->create([
            'email' => 'fixed-google-email@example.com',
        ]);

        $this->actingAs($user)
            ->post(route('user.account.profile'), [
                'name' => $user->name,
                'email' => 'replacement-recovery@example.com',
                'target_position' => $user->target_position,
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('fixed-google-email@example.com', $user->fresh()->email);
    }

    public function test_google_account_cannot_use_password_based_account_deletion(): void
    {
        $user = User::factory()->google()->create();

        $this->actingAs($user)
            ->post(route('user.account.delete'), [
                'delete_password' => 'password',
            ])
            ->assertSessionHasErrors([
                'account_delete' => 'Confirm this account through Google before deleting it.',
            ]);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_authenticated_google_user_cannot_acknowledge_a_local_recovery_code(): void
    {
        $user = User::factory()->create([
            'username' => 'google-recovery-ack-user',
            'google_id' => 'google-user-ack-123',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['recovery_code' => 'ABCD-EFGH-IJKL-MNOP'])
            ->from(route('user.account'))
            ->post(route('user.account.recovery-code.acknowledge'), [
                'recovery_code_saved' => '1',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'recovery_code_saved' => 'Google Login accounts do not use local password recovery codes.',
            ]);
    }

    public function test_google_user_cannot_reset_a_password_with_a_local_recovery_code(): void
    {
        $recoveryCode = 'ABCD-EFGH-IJKL-MNOP';
        $user = User::factory()->create([
            'username' => 'google-local-reset-user',
            'google_id' => 'google-local-reset-id',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
            'password' => Hash::make('unchanged-google-password'),
            'password_recovery_code_hash' => PasswordRecovery::hash($recoveryCode),
            'password_recovery_code_generated_at' => now(),
            'password_recovery_code_expires_at' => null,
        ]);

        $this->from(route('password.request'))->post(route('password.recovery.reset'), [
            'username' => $user->username,
            'recovery_code' => $recoveryCode,
            'password' => 'NewLocalPassword123!',
            'password_confirmation' => 'NewLocalPassword123!',
        ])->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('recovery_code');

        $user->refresh();
        $this->assertTrue(Hash::check('unchanged-google-password', $user->password));
        $this->assertTrue(PasswordRecovery::check($recoveryCode, $user->password_recovery_code_hash));
    }

    public function test_google_account_cannot_use_email_reset_links(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $user = User::factory()->google()->create([
            'email' => 'google-reset-link@example.com',
            'password' => Hash::make('unchanged-google-password'),
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status', 'If a verified local account matches that email, a password reset link will be sent shortly.');

        Notification::assertNothingSent();

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewLocalPassword1!',
            'password_confirmation' => 'NewLocalPassword1!',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('unchanged-google-password', $user->fresh()->password));
    }

    public function test_email_reset_targets_the_local_account_when_google_uses_the_same_email(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $localUser = User::factory()->create([
            'email' => 'shared-account-email@example.com',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);
        $googleUser = User::factory()->google()->create([
            'email' => $localUser->email,
        ]);

        $this->post(route('password.email'), [
            'email' => $localUser->email,
        ])->assertSessionHas('status', 'We have emailed your password reset link.');

        Notification::assertSentTo($localUser, ResetPassword::class);
        Notification::assertNotSentTo($googleUser, ResetPassword::class);
    }

    public function test_forgot_password_page_can_send_a_reset_link(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $user = User::factory()->create([
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status', 'We have emailed your password reset link.');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_normalizes_email_before_sending_reset_link(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'person@example.com',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $response = $this->post(route('password.email'), [
            'email' => '  PERSON@EXAMPLE.COM  ',
        ]);

        $response->assertSessionHas('status', 'We have emailed your password reset link.');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_can_send_reset_link_with_brevo_api(): void
    {
        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.endpoint' => 'https://api.brevo.test/v3/smtp/email',
            'mail.from.address' => 'capstonespeakreadyai@gmail.com',
            'mail.from.name' => 'SpeakReady AI',
        ]);

        Http::fake([
            'api.brevo.test/*' => Http::response(['messageId' => 'test-message'], 201),
        ]);

        $user = User::factory()->create([
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');

        Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'https://api.brevo.test/v3/smtp/email'
                && $request->hasHeader('api-key', 'test-brevo-key')
                && data_get($request->data(), 'sender.email') === 'capstonespeakreadyai@gmail.com'
                && data_get($request->data(), 'to.0.email') === $user->email
                && str_contains(data_get($request->data(), 'htmlContent'), '/reset-password/');
        });
    }

    public function test_forgot_password_handles_mail_transport_failure(): void
    {
        $user = User::factory()->create([
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with([
                'email' => $user->email,
                'auth_method' => User::AUTH_METHOD_LOCAL,
            ])
            ->andThrow(new RuntimeException('SMTP blocked'));

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'password' => Hash::make('old-password'),
            'pending_recovery_email' => 'unverified-change@example.com',
            'pending_recovery_email_requested_at' => now(),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success', 'Your password has been reset.');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        $this->assertNull($user->fresh()->pending_recovery_email);
        $this->assertNull($user->fresh()->pending_recovery_email_requested_at);
    }

    public function test_reset_password_normalizes_email_and_consumes_token(): void
    {
        $user = User::factory()->create([
            'email' => 'person@example.com',
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => '  PERSON@EXAMPLE.COM  ',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect('/');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));

        $response = $this->from(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]))->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'AnotherPassword123!',
            'password_confirmation' => 'AnotherPassword123!',
        ]);

        $response->assertRedirect(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));
        $response->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }
}
