<?php

namespace Tests\Feature;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Jobs\GenerateAnswerProviderFeedback;
use App\Jobs\GenerateInterviewFeedback;
use App\Models\Category;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
