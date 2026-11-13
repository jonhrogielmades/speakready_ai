<?php

namespace Tests\Feature;

use App\Jobs\GenerateInterviewFeedback;
use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\User;
use App\Services\DetailedFeedbackReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class DetailedReviewProviderRetryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
    }

    public function test_owner_can_queue_one_provider_retry_despite_legacy_backoff(): void
    {
        $user = $this->user();
        $session = $this->reviewSession($user, ['feedback_status' => 'pending']);
        $blockedUntil = now()->addMinutes(10)->toIso8601String();
        $session->update(['action_plan' => [
            '_provider_refresh' => ['blocked_until' => $blockedUntil],
            'headline' => 'Keep this practice plan.',
        ]]);
        Cache::put('feedback-review-refresh:'.$session->id, true, now()->addMinutes(15));
        $this->providerAvailable(true);

        $this->actingAs($user)->post(route('user.review.refresh', $session->id))
            ->assertRedirect(route('user.review', $session->id));

        Queue::assertPushed(GenerateInterviewFeedback::class, fn ($job): bool =>
            $job->sessionId === (int) $session->id && $job->userId === (int) $user->id
        );
        Queue::assertPushed(GenerateInterviewFeedback::class, 1);
        $this->assertSame($blockedUntil, data_get($session->fresh()->action_plan, '_provider_refresh.blocked_until'));
        $this->assertSame('Keep this practice plan.', data_get($session->fresh()->action_plan, 'headline'));
        $this->assertSame('pending', $session->fresh()->feedback_status);
        $this->assertNotEmpty($session->fresh()->feedback_processing_token);
        $this->assertTrue(Cache::has('feedback-review-refresh:'.$session->id));
        $this->assertTrue(Cache::has('feedback-review-manual-retry:'.$session->id));
        Http::assertNothingSent();
    }

    public function test_immediate_repeat_is_rate_limited_without_queueing_another_review(): void
    {
        $user = $this->user();
        $session = $this->reviewSession($user, ['feedback_status' => 'failed']);
        $this->providerAvailable(true);

        $this->actingAs($user)->post(route('user.review.refresh', $session->id))->assertRedirect();
        $this->actingAs($user)->post(route('user.review.refresh', $session->id))->assertStatus(429);

        Queue::assertPushed(GenerateInterviewFeedback::class, 1);
        Http::assertNothingSent();
    }

    public function test_another_users_session_is_private_and_cannot_be_retried(): void
    {
        $owner = $this->user();
        $session = $this->reviewSession($owner);
        $blockedUntil = now()->addMinutes(10)->toIso8601String();
        $session->update(['action_plan' => ['_provider_refresh' => ['blocked_until' => $blockedUntil]]]);

        $this->actingAs($this->user())->post(route('user.review.refresh', $session->id))->assertNotFound();

        Queue::assertNothingPushed();
        $this->assertSame($blockedUntil, data_get($session->fresh()->action_plan, '_provider_refresh.blocked_until'));
        Http::assertNothingSent();
    }

    public function test_only_completed_non_ended_sessions_can_be_retried(): void
    {
        $user = $this->user();
        foreach ([
            ['status' => 'in_progress'],
            ['status' => 'ended'],
            ['status' => 'completed', 'action_plan' => ['ended_early' => true]],
        ] as $attributes) {
            $session = $this->reviewSession($user, $attributes);
            $this->actingAs($user)->post(route('user.review.refresh', $session->id))->assertStatus(409);
        }

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_unconfigured_provider_preserves_backoff_and_does_not_queue_a_local_substitute(): void
    {
        $user = $this->user();
        $session = $this->reviewSession($user);
        $blockedUntil = now()->addMinutes(10)->toIso8601String();
        $session->update(['action_plan' => ['_provider_refresh' => ['blocked_until' => $blockedUntil]]]);
        $this->providerAvailable(false);

        $this->actingAs($user)->post(route('user.review.refresh', $session->id))
            ->assertRedirect(route('user.review', $session->id))
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
        $this->assertSame($blockedUntil, data_get($session->fresh()->action_plan, '_provider_refresh.blocked_until'));
        Http::assertNothingSent();
    }

    private function providerAvailable(bool $available): void
    {
        $this->mock(DetailedFeedbackReviewService::class, function (MockInterface $mock) use ($available): void {
            $mock->shouldReceive('hasConfiguredProvider')->andReturn($available);
            if ($available) {
                $mock->shouldReceive('shouldRefreshWithProvider')->andReturn(true);
            }
        });
    }

    private function user(): User
    {
        return User::factory()->create(['status' => 'active', 'is_admin' => false]);
    }

    private function reviewSession(User $user, array $attributes = []): InterviewSession
    {
        $category = Category::create(['title' => 'Behavioral']);

        return InterviewSession::create(array_replace([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Support Specialist',
            'status' => 'completed',
        ], $attributes));
    }
}
