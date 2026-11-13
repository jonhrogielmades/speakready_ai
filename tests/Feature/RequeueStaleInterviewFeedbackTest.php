<?php

namespace Tests\Feature;

use App\Jobs\GenerateInterviewFeedback;
use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RequeueStaleInterviewFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_provider_reviews_are_requeued_while_fresh_and_exhausted_reviews_are_left_alone(): void
    {
        Queue::fake();
        $user = User::factory()->create(['status' => 'active', 'is_admin' => false]);
        $category = Category::create(['title' => 'Technical']);
        $pending = $this->makeReviewSession($user, $category, 'pending', 1);
        $processing = $this->makeReviewSession($user, $category, 'processing', 2);
        $failed = $this->makeReviewSession($user, $category, 'failed', 3);
        $fresh = $this->makeReviewSession($user, $category, 'pending', 0);
        $exhausted = $this->makeReviewSession($user, $category, 'failed', 9);
        $exhaustedProcessing = $this->makeReviewSession($user, $category, 'processing', 9);

        $this->backdate($pending, 4);
        $this->backdate($failed, 16);
        $this->backdate($exhausted, 20);
        $processing->forceFill(['feedback_started_at' => now()->subMinutes(6)])->save();
        $exhaustedProcessing->forceFill(['feedback_started_at' => now()->subMinutes(6)])->save();

        $this->artisan('feedback:requeue-stale')->assertSuccessful()
            ->expectsOutput('Requeued 3 provider feedback reviews.');

        Queue::assertPushed(GenerateInterviewFeedback::class, 3);
        $queuedIds = Queue::pushed(GenerateInterviewFeedback::class)
            ->map(fn (GenerateInterviewFeedback $job): int => $job->sessionId)
            ->sort()->values()->all();
        $this->assertSame(collect([$pending->id, $processing->id, $failed->id])->sort()->values()->all(), $queuedIds);
        $this->assertSame('pending', $processing->fresh()->feedback_status);
        $this->assertSame('pending', $failed->fresh()->feedback_status);
        $this->assertSame('pending', $fresh->fresh()->feedback_status);
        $this->assertSame('failed', $exhausted->fresh()->feedback_status);
        $this->assertSame('failed', $exhaustedProcessing->fresh()->feedback_status);
    }

    private function makeReviewSession(User $user, Category $category, string $status, int $attempts): InterviewSession
    {
        return InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Database Developer',
            'status' => 'completed',
            'feedback_status' => $status,
            'feedback_attempts' => $attempts,
        ]);
    }

    private function backdate(InterviewSession $session, int $minutes): void
    {
        $session->timestamps = false;
        $session->forceFill(['updated_at' => now()->subMinutes($minutes)])->save();
        $session->timestamps = true;
    }
}
