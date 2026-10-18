<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_subscription_is_normalized_persisted_and_idempotent(): void
    {
        $this->from('/')
            ->post(route('newsletter.subscribe'), [
                'email' => '  Reader@Example.COM ',
            ])
            ->assertRedirect('/')
            ->assertSessionHas('newsletter_success');

        $this->assertDatabaseHas('newsletter_subscriptions', [
            'email' => 'reader@example.com',
            'status' => 'subscribed',
            'source' => 'website_footer',
        ]);

        $this->from('/')
            ->post(route('newsletter.subscribe'), [
                'email' => 'reader@example.com',
            ])
            ->assertRedirect('/')
            ->assertSessionHas('newsletter_success');

        $this->assertDatabaseCount('newsletter_subscriptions', 1);
    }

    public function test_invalid_newsletter_email_is_not_persisted(): void
    {
        $this->from('/')
            ->post(route('newsletter.subscribe'), [
                'email' => 'not-an-email',
            ])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email', null, 'newsletter');

        $this->assertDatabaseCount('newsletter_subscriptions', 0);
    }
}
