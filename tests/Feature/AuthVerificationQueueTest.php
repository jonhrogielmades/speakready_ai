<?php

namespace Tests\Feature;

use App\Jobs\SendRecoveryEmailVerification;
use App\Models\Setting;
use App\Models\User;
use App\Services\RecoveryEmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthVerificationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_queues_verification_without_waiting_for_mail_delivery(): void
    {
        config(['services.brevo.api_key' => 'test-brevo-key']);
        Queue::fake();
        Http::fake();

        $this->post(route('register.submit'), [
            'name' => 'New Candidate',
            'username' => 'new-candidate',
            'email' => 'candidate@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ])->assertRedirect(route('terms.acceptance.show'));

        $user = User::where('username', 'new-candidate')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        Queue::assertPushed(SendRecoveryEmailVerification::class, fn ($job) => $job->userId === $user->id);
        Http::assertNothingSent();
    }

    public function test_required_verification_login_queues_mail_before_rejecting_sign_in(): void
    {
        config(['services.brevo.api_key' => 'test-brevo-key']);
        Setting::setVal('acc_verify_email', true, 'account', 'boolean');
        Queue::fake();
        Http::fake();

        $user = User::factory()->unverified()->create([
            'username' => 'unverified-candidate',
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'password' => Hash::make('StrongPass1!'),
        ]);

        $this->post(route('login.submit'), [
            'username' => $user->username,
            'password' => 'StrongPass1!',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
        Queue::assertPushed(SendRecoveryEmailVerification::class, fn ($job) => $job->userId === $user->id);
        Http::assertNothingSent();
    }

    public function test_queued_job_delivers_the_current_verification_link(): void
    {
        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.endpoint' => 'https://api.brevo.test/v3/smtp/email',
            'mail.from.address' => 'noreply@speakready.test',
        ]);
        Http::fake(['api.brevo.test/*' => Http::response(['messageId' => 'sent'], 201)]);

        $user = User::factory()->unverified()->create([
            'email' => 'current@example.com',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        (new SendRecoveryEmailVerification((int) $user->id))
            ->handle(app(RecoveryEmailVerification::class));

        Http::assertSent(fn ($request) =>
            data_get($request->data(), 'to.0.email') === 'current@example.com'
            && str_contains((string) data_get($request->data(), 'textContent'), '/email/verify/')
        );
    }
}
