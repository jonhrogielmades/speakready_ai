<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewsletterSubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_RESPONSE = 'If this address can be subscribed, a confirmation link has been sent. Please check your inbox.';

    public function test_footer_subscription_is_normalized_pending_and_idempotent(): void
    {
        Notification::fake();

        $this->from('/')
            ->post(route('newsletter.subscribe'), ['email' => '  Reader@Example.COM '])
            ->assertRedirect('/')
            ->assertSessionHas('newsletter_success', self::GENERIC_RESPONSE);

        $subscription = NewsletterSubscription::query()->sole();
        $this->assertSame('reader@example.com', $subscription->email);
        $this->assertSame(NewsletterSubscription::STATUS_PENDING, $subscription->status);
        $this->assertSame('website_footer', $subscription->source);
        $this->assertNotNull($subscription->confirmation_expires_at);
        $this->assertNull($subscription->confirmed_at);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $subscription->confirmation_token_hash);

        $firstHash = $subscription->confirmation_token_hash;

        $this->from('/')
            ->post(route('newsletter.subscribe'), ['email' => 'reader@example.com'])
            ->assertRedirect('/')
            ->assertSessionHas('newsletter_success', self::GENERIC_RESPONSE);

        $this->assertDatabaseCount('newsletter_subscriptions', 1);
        $this->assertNotSame($firstHash, $subscription->fresh()->confirmation_token_hash);
        Notification::assertSentOnDemandTimes(NewsletterConfirmation::class, 2);
    }

    public function test_existing_subscriber_receives_the_same_public_response_without_a_new_message(): void
    {
        Notification::fake();
        NewsletterSubscription::create([
            'email' => 'member@example.com',
            'status' => NewsletterSubscription::STATUS_SUBSCRIBED,
            'source' => 'website_footer',
            'subscribed_at' => now()->subWeek(),
            'confirmed_at' => now()->subWeek(),
        ]);

        $this->from('/')
            ->post(route('newsletter.subscribe'), ['email' => 'member@example.com'])
            ->assertRedirect('/')
            ->assertSessionHas('newsletter_success', self::GENERIC_RESPONSE);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('newsletter_subscriptions', 1);
    }

    public function test_confirmation_promotes_pending_subscription_atomically_and_is_idempotent(): void
    {
        Notification::fake();

        $this->from('/')
            ->post(route('newsletter.subscribe'), ['email' => 'reader@example.com'])
            ->assertRedirect('/');

        $notification = $this->capturedConfirmationNotification();
        $token = Crypt::decryptString($notification->encryptedToken);
        $subscription = NewsletterSubscription::query()->sole();

        $this->assertNotSame($token, $subscription->confirmation_token_hash);
        $this->assertSame(hash('sha256', $token), $subscription->confirmation_token_hash);
        $this->assertStringNotContainsString($token, serialize($notification));

        $this->get(route('newsletter.confirm', ['token' => $token]))
            ->assertRedirect('/')
            ->assertSessionHas('newsletter_success');

        $confirmed = $subscription->fresh();
        $this->assertSame(NewsletterSubscription::STATUS_SUBSCRIBED, $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);
        $confirmedAt = $confirmed->confirmed_at->toISOString();

        $this->get(route('newsletter.confirm', ['token' => $token]))
            ->assertRedirect('/')
            ->assertSessionHas('newsletter_success');

        $this->assertSame($confirmedAt, $subscription->fresh()->confirmed_at->toISOString());
    }

    public function test_expired_confirmation_does_not_activate_subscription(): void
    {
        $token = Str::random(64);
        $subscription = NewsletterSubscription::create([
            'email' => 'expired@example.com',
            'status' => NewsletterSubscription::STATUS_PENDING,
            'source' => 'website_footer',
            'confirmation_token_hash' => hash('sha256', $token),
            'confirmation_expires_at' => now()->subMinute(),
            'subscribed_at' => now()->subDay(),
        ]);

        $this->get(route('newsletter.confirm', ['token' => $token]))
            ->assertRedirect('/')
            ->assertSessionHasErrors('email', null, 'newsletter');

        $this->assertSame(NewsletterSubscription::STATUS_PENDING, $subscription->fresh()->status);
        $this->assertNull($subscription->fresh()->confirmed_at);
    }

    public function test_invalid_confirmation_token_is_rejected_without_changing_subscribers(): void
    {
        NewsletterSubscription::create([
            'email' => 'pending@example.com',
            'status' => NewsletterSubscription::STATUS_PENDING,
            'source' => 'website_footer',
            'confirmation_token_hash' => hash('sha256', Str::random(64)),
            'confirmation_expires_at' => now()->addHour(),
            'subscribed_at' => now(),
        ]);

        $this->get(route('newsletter.confirm', ['token' => Str::random(64)]))
            ->assertRedirect('/')
            ->assertSessionHasErrors('email', null, 'newsletter');

        $this->assertDatabaseMissing('newsletter_subscriptions', [
            'email' => 'pending@example.com',
            'status' => NewsletterSubscription::STATUS_SUBSCRIBED,
        ]);
    }

    public function test_invalid_newsletter_email_is_not_persisted(): void
    {
        Notification::fake();

        $this->from('/')
            ->post(route('newsletter.subscribe'), ['email' => 'not-an-email'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email', null, 'newsletter');

        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        Notification::assertNothingSent();
    }

    private function capturedConfirmationNotification(): NewsletterConfirmation
    {
        $captured = null;

        Notification::assertSentOnDemand(
            NewsletterConfirmation::class,
            function (NewsletterConfirmation $notification) use (&$captured): bool {
                $captured = $notification;

                return true;
            }
        );

        $this->assertInstanceOf(NewsletterConfirmation::class, $captured);

        return $captured;
    }
}
