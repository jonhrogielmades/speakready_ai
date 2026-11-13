<?php

namespace Tests\Feature;

use App\Jobs\RefreshDetailedFeedbackReview;
use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\User;
use App\Services\DetailedFeedbackReviewService;
use App\Support\DetailedReviewProgress;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AutomaticDetailedReviewProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_sync_hosting_retries_once_within_the_same_deadline_without_local_fallback(): void
    {
        config(['queue.default' => 'sync']);
        $session = $this->reviewSession($this->user());
        $budgets = [];
        $service = Mockery::mock(DetailedFeedbackReviewService::class);
        $service->shouldReceive('refreshWithProvidersIfNeeded')->twice()
            ->withArgs(function ($actual, $provider, $deadlineSeconds) use ($session, &$budgets): bool {
                $budgets[] = $deadlineSeconds;

                return $actual->is($session) && $provider === null
                    && is_int($deadlineSeconds) && $deadlineSeconds >= 5 && $deadlineSeconds <= 130;
            })->andReturn(false, true);
        $service->shouldReceive('hasConfiguredProvider')->once()->andReturn(true);
        $service->shouldReceive('shouldRefreshWithProvider')->once()->andReturn(true);
        $service->shouldNotReceive('refreshIfNeeded');

        (new RefreshDetailedFeedbackReview($session->id, $session->user_id))->handle($service);

        $this->assertCount(2, $budgets);
        $this->assertLessThanOrEqual($budgets[0], $budgets[1]);
        $this->assertSame('ready', Cache::get('feedback-review-status:'.$session->id)['status']);
        $this->assertRunningLockReleased($session);
        Http::assertNothingSent();
    }

    public function test_dispatch_after_response_sync_job_gets_recovery_even_with_an_async_default(): void
    {
        config(['queue.default' => 'database']);
        $session = $this->reviewSession($this->user());
        $service = $this->pendingReviewService();
        $service->shouldReceive('refreshWithProvidersIfNeeded')->twice()->andReturn(false, true);
        $job = new RefreshDetailedFeedbackReview($session->id, $session->user_id);
        $job->setJob(Mockery::mock(SyncJob::class));

        $job->handle($service);

        $this->assertSame('ready', Cache::get('feedback-review-status:'.$session->id)['status']);
        $this->assertRunningLockReleased($session);
    }

    public function test_exhausted_sync_attempts_become_failed_and_release_the_running_lock(): void
    {
        config(['queue.default' => 'sync']);
        $session = $this->reviewSession($this->user());
        $service = $this->pendingReviewService();
        $service->shouldReceive('refreshWithProvidersIfNeeded')->twice()->andReturn(false);

        $this->assertPendingAttemptThrows(new RefreshDetailedFeedbackReview($session->id, $session->user_id), $service);

        $this->assertSame('failed', DetailedReviewProgress::status($session->id));
        $this->assertRunningLockReleased($session);
    }

    public function test_async_attempt_waits_for_worker_retry_and_last_attempt_becomes_failed(): void
    {
        config(['queue.default' => 'database']);
        $session = $this->reviewSession($this->user());
        $service = $this->pendingReviewService();
        $service->shouldReceive('refreshWithProvidersIfNeeded')->twice()->andReturn(false);

        foreach ([1 => 'waiting', 2 => 'failed'] as $attempt => $expectedStatus) {
            $queued = Mockery::mock(QueueJob::class);
            $queued->shouldReceive('attempts')->once()->andReturn($attempt);
            $job = new RefreshDetailedFeedbackReview($session->id, $session->user_id);
            $job->setJob($queued);

            $this->assertPendingAttemptThrows($job, $service);

            $this->assertSame($expectedStatus, DetailedReviewProgress::status($session->id));
            $this->assertRunningLockReleased($session);
        }
    }

    public function test_job_reports_unavailable_without_retrying_or_generating_local_prose(): void
    {
        $session = $this->reviewSession($this->user());
        $service = Mockery::mock(DetailedFeedbackReviewService::class);
        $service->shouldReceive('refreshWithProvidersIfNeeded')->once()->andReturn(false);
        $service->shouldReceive('hasConfiguredProvider')->once()->andReturn(false);
        $service->shouldNotReceive('shouldRefreshWithProvider');
        $service->shouldNotReceive('refreshIfNeeded');

        (new RefreshDetailedFeedbackReview($session->id, $session->user_id))->handle($service);

        $this->assertSame('unavailable', DetailedReviewProgress::status($session->id));
        $this->assertRunningLockReleased($session);
    }

    public function test_worker_failure_callback_exposes_recovery_instead_of_staying_processing(): void
    {
        $session = $this->reviewSession($this->user());
        DetailedReviewProgress::mark($session->id, 'processing');

        (new RefreshDetailedFeedbackReview($session->id, $session->user_id))->failed(new \RuntimeException('Worker failed'));

        $this->assertSame('failed', DetailedReviewProgress::status($session->id));
    }

    public function test_pending_status_get_is_read_only_and_never_schedules_provider_work(): void
    {
        $user = $this->user();
        $session = $this->reviewSession($user, [
            'action_plan' => ['headline' => 'Keep the saved plan.'],
            'feedback_status' => 'pending',
        ]);
        $before = $session->fresh()->getAttributes();
        $this->mockStatusService(true, true);
        DetailedReviewProgress::mark($session->id, 'waiting');
        $progressBefore = Cache::get('feedback-review-status:'.$session->id);

        $this->actingAs($user)->getJson(route('user.review.status', $session->id))
            ->assertOk()
            ->assertExactJson(['status' => 'pending', 'retry_after_ms' => 5000])
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertSame($before, $session->fresh()->getAttributes());
        $this->assertSame($progressBefore, Cache::get('feedback-review-status:'.$session->id));
        $this->assertFalse(Cache::has('feedback-review-refresh:'.$session->id));
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_verified_status_requires_saved_review_validation_not_a_cached_ready_marker(): void
    {
        $user = $this->user();
        $session = $this->reviewSession($user);
        DetailedReviewProgress::mark($session->id, 'ready');
        $this->mockStatusService(true, true);

        $this->actingAs($user)->getJson(route('user.review.status', $session->id))
            ->assertOk()->assertJsonPath('status', 'failed');

        $this->mockStatusService(false, false);
        $this->actingAs($user)->getJson(route('user.review.status', $session->id))
            ->assertOk()->assertJsonPath('status', 'verified');

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_status_get_uses_durable_failed_and_pending_states(): void
    {
        $user = $this->user();
        $session = $this->reviewSession($user, ['feedback_status' => 'failed']);
        DetailedReviewProgress::mark($session->id, 'failed');
        $this->mockStatusService(true, true);
        $this->actingAs($user)->getJson(route('user.review.status', $session->id))
            ->assertOk()->assertJsonPath('status', 'failed');

        $session->forceFill(['feedback_status' => 'pending'])->save();
        $this->mockStatusService(true, false);
        $this->actingAs($user)->getJson(route('user.review.status', $session->id))
            ->assertOk()->assertJsonPath('status', 'pending');

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_status_is_private_and_rejects_incomplete_or_ended_sessions(): void
    {
        $owner = $this->user();
        $privateSession = $this->reviewSession($owner);
        $this->mock(DetailedFeedbackReviewService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('shouldRefreshWithProvider');
            $mock->shouldNotReceive('refreshWithProvidersIfNeeded');
        });
        $this->actingAs($this->user())->getJson(route('user.review.status', $privateSession->id))->assertNotFound();

        foreach ([
            ['status' => 'in_progress'],
            ['status' => 'ended'],
            ['action_plan' => ['ended_early' => true]],
        ] as $attributes) {
            $session = $this->reviewSession($owner, $attributes);
            $this->actingAs($owner)->getJson(route('user.review.status', $session->id))->assertStatus(409);
        }

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_expired_or_corrupt_progress_cannot_leave_the_ui_polling_forever(): void
    {
        $session = $this->reviewSession($this->user());
        DetailedReviewProgress::mark($session->id, 'processing', 10);
        $this->assertSame('processing', DetailedReviewProgress::status($session->id));

        $this->travel(10)->seconds();
        $this->assertTrue(Cache::has('feedback-review-status:'.$session->id));
        $this->assertSame('failed', DetailedReviewProgress::status($session->id));
        $this->travelBack();

        Cache::put('feedback-review-status:'.$session->id, 'broken', now()->addMinutes(5));
        $this->assertSame('failed', DetailedReviewProgress::status($session->id));
        DetailedReviewProgress::mark($session->id, 'unexpected');
        $this->assertSame('failed', DetailedReviewProgress::status($session->id));
    }

    public function test_manual_retry_during_active_processing_does_not_duplicate_queued_work(): void
    {
        $user = $this->user();
        $this->mock(DetailedFeedbackReviewService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('hasConfiguredProvider')->andReturn(true);
            $mock->shouldReceive('shouldRefreshWithProvider')->andReturn(true);
            $mock->shouldNotReceive('refreshWithProvidersIfNeeded');
        });

        $session = $this->reviewSession($user, [
            'action_plan' => ['headline' => 'Preserved plan.'],
            'feedback_status' => 'processing',
        ]);
        $before = $session->fresh()->getAttributes();

        $this->actingAs($user)->post(route('user.review.refresh', $session->id))
            ->assertRedirect(route('user.review', $session->id))
            ->assertSessionHas('success');

        $this->assertSame($before, $session->fresh()->getAttributes());

        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_legacy_provider_backoff_does_not_hide_a_review_that_needs_provider_refresh(): void
    {
        $session = $this->reviewSession($this->user(), [
            'action_plan' => ['_provider_refresh' => ['blocked_until' => now()->addHour()->toIso8601String()]],
        ]);

        $this->assertTrue($this->app->make(DetailedFeedbackReviewService::class)->shouldRefreshWithProvider($session));

        Http::assertNothingSent();
    }

    private function pendingReviewService(): MockInterface
    {
        $service = Mockery::mock(DetailedFeedbackReviewService::class);
        $service->shouldReceive('hasConfiguredProvider')->andReturn(true);
        $service->shouldReceive('shouldRefreshWithProvider')->andReturn(true);
        $service->shouldNotReceive('refreshIfNeeded');

        return $service;
    }

    private function mockStatusService(bool $pending, bool $available): void
    {
        $this->mock(DetailedFeedbackReviewService::class, function (MockInterface $mock) use ($pending, $available): void {
            $mock->shouldReceive('shouldRefreshWithProvider')->andReturn($pending);
            $mock->shouldReceive('hasConfiguredProvider')->andReturn($available);
            $mock->shouldNotReceive('refreshWithProvidersIfNeeded');
            $mock->shouldNotReceive('refreshIfNeeded');
        });
    }

    private function assertPendingAttemptThrows(RefreshDetailedFeedbackReview $job, DetailedFeedbackReviewService $service): void
    {
        try {
            $job->handle($service);
            $this->fail('A pending provider attempt must be surfaced to the queue worker.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('AI detailed review is still pending after the provider attempt.', $exception->getMessage());
        }
    }

    private function assertRunningLockReleased(InterviewSession $session): void
    {
        $lock = Cache::lock('feedback-review-running:'.$session->id, 1);
        $this->assertTrue($lock->get());
        $lock->release();
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
