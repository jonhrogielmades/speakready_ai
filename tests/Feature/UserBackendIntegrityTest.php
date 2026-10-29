<?php

namespace Tests\Feature;

use App\Jobs\RefreshDetailedFeedbackReview;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserBackendIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_feedback_record_can_exist_for_an_interview_session(): void
    {
        $session = $this->completedSession();

        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Clear structure.',
        ]);

        $this->expectException(QueryException::class);

        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Duplicate record.',
        ]);
    }

    public function test_retry_attempt_numbers_are_unique_for_each_original_answer(): void
    {
        $session = $this->completedSession();
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'answer_text' => 'Original answer',
            'response_mode' => 'text',
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'retry_of_answer_id' => $answer->id,
            'attempt_number' => 2,
            'answer_text' => 'First retry',
            'response_mode' => 'text',
        ]);

        $this->expectException(QueryException::class);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'retry_of_answer_id' => $answer->id,
            'attempt_number' => 2,
            'answer_text' => 'Conflicting retry',
            'response_mode' => 'text',
        ]);
    }

    public function test_user_history_queries_have_the_expected_composite_indexes(): void
    {
        $this->assertContains(
            'interview_sessions_user_status_created_idx',
            $this->indexNames('interview_sessions')
        );
        $this->assertContains(
            'chatbot_conversations_user_updated_idx',
            $this->indexNames('chatbot_conversations')
        );
        $this->assertContains(
            'game_sessions_user_status_created_idx',
            $this->indexNames('game_sessions')
        );
    }

    public function test_sensitive_and_ai_backed_user_routes_have_dedicated_middleware(): void
    {
        $translationMiddleware = Route::getRoutes()
            ->getByName('user.language.translate')
            ?->gatherMiddleware() ?? [];
        $passwordResetMiddleware = Route::getRoutes()
            ->getByName('password.update')
            ?->gatherMiddleware() ?? [];
        $accountMiddleware = Route::getRoutes()
            ->getByName('user.account')
            ?->gatherMiddleware() ?? [];

        $this->assertContains('throttle:ai-translation', $translationMiddleware);
        $this->assertContains('throttle:auth', $passwordResetMiddleware);
        $this->assertContains('auth.session', $accountMiddleware);
    }

    public function test_opening_a_completed_review_queues_refresh_work_once_per_cooldown(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'difficulty' => 'medium',
            'target_position' => 'Software Engineer',
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk();

        $this->get(route('user.review', $session))
            ->assertOk();

        Queue::assertPushed(
            RefreshDetailedFeedbackReview::class,
            fn (RefreshDetailedFeedbackReview $job): bool => $job->sessionId === $session->id
                && $job->userId === $user->id
        );
        Queue::assertPushedTimes(RefreshDetailedFeedbackReview::class, 1);
    }

    private function completedSession(): InterviewSession
    {
        return InterviewSession::create([
            'user_id' => User::factory()->create()->id,
            'difficulty' => 'medium',
            'target_position' => 'Software Engineer',
            'status' => 'completed',
        ]);
    }

    /** @return array<int, string> */
    private function indexNames(string $table): array
    {
        return collect(Schema::getIndexes($table))
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }
}
