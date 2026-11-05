<?php

namespace Tests\Feature;

use App\Jobs\SendGoogleWelcomeEmail;
use App\Mail\GoogleWelcomeMail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleWelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_google_sign_in_queues_one_welcome_email_and_repeat_sign_in_does_not(): void
    {
        Queue::fake();
        $this->mockGoogleCallback([
            'id' => 'new-google-id',
            'name' => 'New Google User',
            'email' => 'new.google.user@gmail.com',
            'avatar' => null,
        ], 2);

        $this->googleCallback()->assertRedirect(route('terms.acceptance.show'));

        $user = User::where('google_id', 'new-google-id')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        Queue::assertPushed(SendGoogleWelcomeEmail::class, fn ($job) => $job->userId === $user->id);

        $this->googleCallback()->assertRedirect(route('terms.acceptance.show'));

        $this->assertSame(1, User::where('google_id', 'new-google-id')->count());
        Queue::assertPushed(SendGoogleWelcomeEmail::class, 1);
    }

    public function test_restored_google_account_does_not_get_another_welcome_email(): void
    {
        Queue::fake();
        $user = User::factory()->google()->create([
            'google_id' => 'restored-google-id',
            'email' => 'restored@gmail.com',
        ]);
        $user->delete();
        $this->mockGoogleCallback([
            'id' => 'restored-google-id',
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => null,
        ]);

        $this->googleCallback()->assertRedirect(route('dashboard'));

        $this->assertNull($user->fresh()->deleted_at);
        Queue::assertNotPushed(SendGoogleWelcomeEmail::class);
    }

    public function test_disabled_registration_and_failed_google_callback_do_not_queue_welcome_email(): void
    {
        Queue::fake();
        Setting::setVal('acc_registration', false, 'account', 'boolean');
        $this->mockGoogleCallback([
            'id' => 'registration-disabled-id',
            'name' => 'Registration Disabled',
            'email' => 'disabled@gmail.com',
            'avatar' => null,
        ]);

        $this->googleCallback()->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users', ['google_id' => 'registration-disabled-id']);
        Queue::assertNotPushed(SendGoogleWelcomeEmail::class);

        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'));
        Queue::assertNotPushed(SendGoogleWelcomeEmail::class);
    }

    public function test_queued_job_uses_smtp_fallback_with_public_policy_links(): void
    {
        config(['services.brevo.api_key' => null]);
        Mail::fake();
        $user = User::factory()->google()->create([
            'name' => 'Policy Reader',
            'email' => 'policy.reader@gmail.com',
        ]);

        app()->call([new SendGoogleWelcomeEmail((int) $user->id), 'handle']);

        Mail::assertSent(GoogleWelcomeMail::class, function (GoogleWelcomeMail $mail) use ($user): bool {
            $html = $mail->render();

            return $mail->hasTo($user->email)
                && str_contains($html, route('legal.privacy'))
                && str_contains($html, route('legal.terms'))
                && str_contains($html, route('legal.security'));
        });
    }

    public function test_queued_job_uses_brevo_with_html_and_plain_text_content(): void
    {
        config([
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.endpoint' => 'https://api.brevo.test/v3/smtp/email',
            'mail.from.address' => 'noreply@speakready.test',
        ]);
        Http::fake(['api.brevo.test/*' => Http::response(['messageId' => 'sent'], 201)]);
        Mail::fake();
        $user = User::factory()->google()->create([
            'email' => 'brevo.reader@gmail.com',
        ]);

        app()->call([new SendGoogleWelcomeEmail((int) $user->id), 'handle']);

        Http::assertSent(fn ($request) =>
            data_get($request->data(), 'to.0.email') === $user->email
            && str_contains((string) data_get($request->data(), 'htmlContent'), route('legal.privacy'))
            && str_contains((string) data_get($request->data(), 'htmlContent'), route('legal.terms'))
            && str_contains((string) data_get($request->data(), 'textContent'), route('legal.security'))
        );
        Mail::assertNothingSent();
    }

    public function test_mail_delivery_failure_does_not_cancel_first_google_sign_in(): void
    {
        config([
            'queue.default' => 'sync',
            'services.brevo.api_key' => 'test-brevo-key',
            'services.brevo.endpoint' => 'https://api.brevo.test/v3/smtp/email',
        ]);
        Http::fake(['api.brevo.test/*' => Http::response(['message' => 'Unavailable'], 503)]);
        $this->mockGoogleCallback([
            'id' => 'mail-failure-google-id',
            'name' => 'Mail Failure User',
            'email' => 'mail.failure@gmail.com',
            'avatar' => null,
        ]);

        $this->googleCallback()
            ->assertRedirect(route('terms.acceptance.show'))
            ->assertSessionHasNoErrors();

        $user = User::where('google_id', 'mail-failure-google-id')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        Http::assertSentCount(1);
    }

    private function googleCallback()
    {
        return $this->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback', ['code' => 'test-code']));
    }

    private function mockGoogleCallback(array $attributes, int $times = 1): void
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
        $provider->shouldReceive('user')->times($times)->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->times($times)
            ->with('google')
            ->andReturn($provider);
    }
}
