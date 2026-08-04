<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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

    public function test_forgot_password_page_can_send_a_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');
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

        $user = User::factory()->create();

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
        $user = User::factory()->create();

        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => $user->email])
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
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }
}
