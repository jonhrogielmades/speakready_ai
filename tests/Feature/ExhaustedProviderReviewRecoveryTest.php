<?php

namespace Tests\Feature;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Jobs\GenerateAnswerProviderFeedback;
use App\Jobs\GenerateInterviewFeedback;
use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewAnswerProviderReview;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use App\Services\DetailedFeedbackReviewService;
use App\Services\PreparedAnswerReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ExhaustedProviderReviewRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_failed_provider_review_queues_one_answer_review_per_missing_answer(): void
    {
        [$session, $answerIds] = $this->completedInterviewWithTwoAnswers();
        $job = new GenerateInterviewFeedback($session->id, $session->user_id);
        $session->update(['feedback_processing_token' => $job->runToken]);

        $job->failed($this->providerFailure());

        $this->assertSame('failed', $session->fresh()->feedback_status);
        $this->assertSame('provider_rate_limit', $session->fresh()->feedback_failure_code);
        Queue::assertPushed(GenerateAnswerProviderFeedback::class, count($answerIds));

        $queued = Queue::pushed(GenerateAnswerProviderFeedback::class);
        $this->assertEqualsCanonicalizing($answerIds, $queued->map(fn (GenerateAnswerProviderFeedback $answer): int => $answer->answerId)->all());
        foreach ($queued as $answerJob) {
            $this->assertSame($session->id, $answerJob->sessionId);
            $this->assertSame('review-answers', $answerJob->queue);
        }
    }

    public function test_stale_failed_job_does_not_queue_answer_reviews(): void
    {
        [$session] = $this->completedInterviewWithTwoAnswers();
        $job = new GenerateInterviewFeedback($session->id, $session->user_id);
        $session->update(['feedback_processing_token' => 'newer-review-run-token']);

        $job->failed($this->providerFailure());

        $this->assertSame('pending', $session->fresh()->feedback_status);
        $this->assertSame('newer-review-run-token', $session->fresh()->feedback_processing_token);
        Queue::assertNotPushed(GenerateAnswerProviderFeedback::class);
    }

    public function test_first_generic_full_review_failure_queues_missing_answers_before_queue_retries(): void
    {
        [$session, $answerIds] = $this->completedInterviewWithTwoAnswers();
        $this->mock(DetailedFeedbackReviewService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('shouldRefreshWithProvider')->twice()->andReturn(true);
            $mock->shouldReceive('refreshWithProvidersIfNeeded')->once()
                ->andThrow(new RuntimeException('Provider response failed final validation.'));
        });

        $job = new GenerateInterviewFeedback($session->id, $session->user_id);
        try {
            $this->app->call([$job, 'handle']);
            $this->fail('The full provider failure should make the queue retry.');
        } catch (RuntimeException $error) {
            $this->assertSame('Provider response failed final validation.', $error->getMessage());
        }

        $this->assertSame('pending', $session->fresh()->feedback_status);
        $this->assertSame(1, (int) $session->fresh()->feedback_attempts);
        $queued = Queue::pushed(GenerateAnswerProviderFeedback::class);
        $this->assertEqualsCanonicalizing($answerIds, $queued->map(fn (GenerateAnswerProviderFeedback $answer): int => $answer->answerId)->all());
    }

    public function test_terminal_generic_failure_still_queues_missing_answer_recovery(): void
    {
        [$session, $answerIds] = $this->completedInterviewWithTwoAnswers();
        $job = new GenerateInterviewFeedback($session->id, $session->user_id);
        $session->update(['feedback_processing_token' => $job->runToken]);
        $waitKey = 'feedback-answer-recovery-wait:'.$session->id;
        Cache::put($waitKey, true, now()->addMinutes(10));

        $job->failed(new RuntimeException('Provider response failed final validation.'));

        $this->assertSame('failed', $session->fresh()->feedback_status);
        $this->assertSame('provider_review_failed', $session->fresh()->feedback_failure_code);
        $this->assertFalse(Cache::has($waitKey));
        $queued = Queue::pushed(GenerateAnswerProviderFeedback::class);
        $this->assertEqualsCanonicalizing($answerIds, $queued->map(fn (GenerateAnswerProviderFeedback $answer): int => $answer->answerId)->all());
    }

    public function test_manual_retry_with_prior_attempts_starts_answer_recovery_on_its_first_failure(): void
    {
        [$session, $answerIds] = $this->completedInterviewWithTwoAnswers();
        $session->forceFill(['feedback_attempts' => 4])->save();
        $this->mock(DetailedFeedbackReviewService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('shouldRefreshWithProvider')->twice()->andReturn(true);
            $mock->shouldReceive('refreshWithProvidersIfNeeded')->once()
                ->andThrow(new RuntimeException('Provider response failed final validation.'));
        });

        try {
            $this->app->call([new GenerateInterviewFeedback($session->id, $session->user_id), 'handle']);
            $this->fail('The full provider failure should make the queue retry.');
        } catch (RuntimeException $error) {
            $this->assertSame('Provider response failed final validation.', $error->getMessage());
        }

        $this->assertSame(5, (int) $session->fresh()->feedback_attempts);
        $queued = Queue::pushed(GenerateAnswerProviderFeedback::class);
        $this->assertEqualsCanonicalizing($answerIds, $queued->map(fn (GenerateAnswerProviderFeedback $answer): int => $answer->answerId)->all());
    }

    public function test_first_failure_waits_for_current_answer_jobs_without_queuing_duplicates(): void
    {
        [$session, $answerIds] = $this->completedInterviewWithTwoAnswers();
        foreach ($answerIds as $answerId) {
            InterviewAnswerProviderReview::create([
                'interview_session_id' => $session->id,
                'interview_answer_id' => $answerId,
                'status' => 'processing',
                'input_fingerprint' => 'current-fingerprint',
            ]);
        }
        DB::table('interview_sessions')->where('id', $session->id)
            ->update(['updated_at' => now()->subMinutes(4)]);
        $answers = InterviewAnswer::with('question')->whereIn('id', $answerIds)->get();
        $this->mock(PreparedAnswerReviewService::class, function (MockInterface $mock) use ($answerIds, $answers): void {
            $mock->shouldReceive('progressForSession')->once()->andReturn([
                'total' => 2, 'verified' => 0, 'pending' => 0, 'processing' => 2, 'failed' => 0,
            ]);
            $mock->shouldReceive('hasPendingPreparationsForSession')->once()->andReturn(true);
            $mock->shouldReceive('preparedReviewsForSession')->twice()->andReturn(null);
            $mock->shouldReceive('missingAnswerIdsForSession')->once()->andReturn($answerIds);
            $mock->shouldReceive('originalAnswers')->once()->andReturn($answers);
            $mock->shouldReceive('inputFingerprint')->twice()->andReturn('current-fingerprint');
        });
        $this->mock(DetailedFeedbackReviewService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('shouldRefreshWithProvider')->twice()->andReturn(true);
            $mock->shouldReceive('refreshWithProvidersIfNeeded')->once()
                ->andThrow(new RuntimeException('Full provider response rejected.'));
        });

        try {
            $this->app->call([new GenerateInterviewFeedback($session->id, $session->user_id), 'handle']);
            $this->fail('The full provider failure should make the queue retry.');
        } catch (RuntimeException $error) {
            $this->assertSame('Full provider response rejected.', $error->getMessage());
        }

        Queue::assertNotPushed(GenerateAnswerProviderFeedback::class);
        $waitKey = 'feedback-answer-recovery-wait:'.$session->id;
        $this->assertTrue(Cache::has($waitKey));
        Cache::forget($waitKey);
    }

    private function completedInterviewWithTwoAnswers(): array
    {
        $user = User::factory()->create(['status' => 'active', 'is_admin' => false]);
        $category = Category::create(['title' => 'Technical']);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Database Developer',
            'num_questions' => 2,
            'response_mode' => 'text',
            'status' => 'completed',
            'feedback_status' => 'pending',
        ]);

        $answerIds = [];
        foreach ([
            'How would you diagnose a slow database query?',
            'How would you check a failed deployment?',
        ] as $questionText) {
            $question = Question::create([
                'category_id' => $category->id,
                'interview_session_id' => $session->id,
                'question_text' => $questionText,
                'type' => 'Technical',
                'difficulty' => 'medium',
                'status' => 'active',
            ]);
            $answerIds[] = InterviewAnswer::create([
                'interview_session_id' => $session->id,
                'question_id' => $question->id,
                'answer_text' => 'I checked the saved evidence, traced the cause, and confirmed the next step before changing anything.',
                'response_mode' => 'text',
            ])->id;
        }

        return [$session, $answerIds];
    }

    private function providerFailure(): AiFeedbackProviderFailureException
    {
        return new AiFeedbackProviderFailureException(
            ['openai'],
            ['openai'],
            failureReasons: ['openai' => ['rate_limited']]
        );
    }
}
