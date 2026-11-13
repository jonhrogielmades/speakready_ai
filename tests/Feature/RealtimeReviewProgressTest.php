<?php

namespace Tests\Feature;

use App\Jobs\GenerateInterviewFeedback;
use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\User;
use App\Services\DetailedFeedbackReviewService;
use App\Services\PreparedAnswerReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class RealtimeReviewProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_final_job_waits_for_preparing_answers_before_full_provider_request(): void
    {
        [$user, $session] = $this->completedSession();
        $this->mock(PreparedAnswerReviewService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('progressForSession')->once()->andReturn([
                'total' => 3, 'verified' => 1, 'pending' => 1, 'processing' => 1, 'failed' => 0,
            ]);
            $mock->shouldReceive('preparedReviewsForSession')->once()->andReturn(null);
            $mock->shouldReceive('hasPendingPreparationsForSession')->once()->andReturn(true);
        });
        $this->mock(DetailedFeedbackReviewService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('shouldRefreshWithProvider')->twice()->andReturn(true);
            $mock->shouldNotReceive('refreshWithProvidersIfNeeded');
            $mock->shouldNotReceive('finalizeFromPreparedAnswers');
        });

        $this->app->call([new GenerateInterviewFeedback($session->id, $user->id), 'handle']);

        $this->assertSame('pending', $session->fresh()->feedback_status);
        $this->assertSame(0, (int) $session->fresh()->feedback_attempts);
        Queue::assertPushed(GenerateInterviewFeedback::class, 1);
    }

    public function test_review_status_reports_live_answer_progress(): void
    {
        [$user, $session] = $this->completedSession();
        $this->mock(DetailedFeedbackReviewService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('shouldRefreshWithProvider')->twice()->andReturn(true);
        });
        $this->mock(PreparedAnswerReviewService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('progressForSession')->twice()->andReturn([
                'total' => 3, 'verified' => 2, 'pending' => 0, 'processing' => 1, 'failed' => 0,
            ]);
        });

        $this->actingAs($user)->getJson(route('user.review.status', $session->id))
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('retry_after_ms', 2000)
            ->assertJsonPath('message', 'AI feedback prepared for 2 of 3 answers. Reviewing the rest now.');

        $session->forceFill(['feedback_status' => 'failed'])->save();
        $this->actingAs($user)->getJson(route('user.review.status', $session->id))
            ->assertOk()
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('message', 'Provider AI feedback for 2 of 3 answers is available below. Retry the final AI summary.');

        Queue::assertNothingPushed();
    }

    /** @return array{User, InterviewSession} */
    private function completedSession(): array
    {
        $user = User::factory()->create(['status' => 'active', 'is_admin' => false]);
        $category = Category::create(['title' => 'Behavioral']);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Support Specialist',
            'status' => 'completed',
            'feedback_status' => 'pending',
            'feedback_attempts' => 0,
        ]);

        return [$user, $session];
    }
}
