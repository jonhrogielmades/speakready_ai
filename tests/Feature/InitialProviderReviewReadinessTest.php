<?php

namespace Tests\Feature;

use App\Jobs\GenerateInterviewFeedback;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Models\Setting;
use App\Models\User;
use App\Services\DetailedFeedbackReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InitialProviderReviewReadinessTest extends TestCase
{
    use RefreshDatabase;

    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
            'GEMINI_MODEL' => 'gemini-test',
            'GROQ_API_KEY' => '', 'COHERE_API_KEY' => '',
            'AI_FEEDBACK_FINALIZATION_ATTEMPTS' => '1',
            'AI_FEEDBACK_FINALIZATION_HTTP_ATTEMPTS' => '1',
            'AI_FEEDBACK_FINALIZATION_RETRY_DELAY_MS' => '0',
        ] as $key => $value) {
            $this->originalEnv[$key] = getenv($key);
            putenv("{$key}={$value}");
            $_ENV[$key] = $_SERVER[$key] = $value;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $_SERVER[$key] = $value;
            }
        }
        parent::tearDown();
    }

    public function test_finish_saves_the_interview_and_queues_provider_review_without_waiting_for_an_api(): void
    {
        [$user, $session] = $this->fixture();

        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202)->assertJsonPath('review_ready', false)
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertSame('completed', $session->fresh()->status);
        $this->assertSame('pending', $session->fresh()->feedback_status);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
        Queue::assertPushed(GenerateInterviewFeedback::class, 1);
        Http::assertNothingSent();
    }

    public function test_queued_provider_review_persists_only_after_saved_answer_validation(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $payload = $this->payload($answer);
        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202)->assertJsonPath('review_ready', false);
        Http::fake(Http::response($this->openAiResponse($payload)));
        $this->app->call([new GenerateInterviewFeedback($session->id, $user->id), 'handle']);

        $this->assertSame('verified', $session->fresh()->feedback_status);
        $this->assertSame('openai', $answer->fresh()->ai_provider);
        $this->assertSame(1, Score::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());
        $this->assertFalse($this->app->make(DetailedFeedbackReviewService::class)->shouldRefreshWithProvider($session->fresh()));
        Http::assertSentCount(1);
    }

    public function test_provider_outage_never_publishes_local_feedback_as_a_ready_ai_review(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202)->assertJsonPath('review_ready', false)
            ->assertJsonPath('redirect_url', route('user.review', $session));
        Http::fake(Http::response(['error' => ['message' => 'Too many requests']], 429));
        $job = new GenerateInterviewFeedback($session->id, $user->id);
        try {
            $this->app->call([$job, 'handle']);
        } catch (\Throwable $error) {
            $job->failed($error);
        }

        $this->assertSame('completed', $session->fresh()->status);
        $savedAnswer = $answer->fresh();
        $this->assertSame($answer->answer_text, $savedAnswer->answer_text);
        $this->assertNotSame('verified', $session->fresh()->feedback_status);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
        $this->assertContains($savedAnswer->ai_provider, [null, '']);
        $this->assertEmpty($savedAnswer->ai_feedback);
        $this->assertFalse((bool) data_get($savedAnswer->coaching_feedback, 'local_fallback'));
    }

    public function test_repeated_finish_does_not_dispatch_a_duplicate_provider_review(): void
    {
        [$user, $session] = $this->fixture();
        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202)->assertJsonPath('review_ready', false);
        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202)->assertJsonPath('review_ready', false);
        Queue::assertPushed(GenerateInterviewFeedback::class, 1);
        Http::assertNothingSent();
    }

    public function test_changed_answer_is_not_certified_with_stale_provider_output(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $payload = $this->payload($answer);
        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202);
        $changed = 'I observed the database query investigation but did not inspect the execution plan myself.';
        InterviewAnswer::whereKey($answer->id)->update(['answer_text' => $changed]);
        Http::fake(Http::response($this->openAiResponse($payload)));
        try {
            $this->app->call([new GenerateInterviewFeedback($session->id, $user->id), 'handle']);
        } catch (\Throwable) {
            // An outdated provider response must leave the review unverified.
        }

        $this->assertNotSame('verified', $session->fresh()->feedback_status);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
    }

    public function test_disabled_ai_evaluation_saves_answers_without_dispatching_or_exposing_a_review(): void
    {
        Setting::updateOrCreate(['key' => 'int_ai_eval'], [
            'value' => '0', 'type' => 'boolean', 'group' => 'interview',
        ]);
        [$user, $session] = $this->fixture();

        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202)->assertJsonPath('review_ready', false)
            ->assertJsonPath('feedback_status', 'failed');

        $this->assertSame('completed', $session->fresh()->status);
        $this->assertSame('ai_evaluation_disabled', $session->fresh()->feedback_failure_code);
        Queue::assertNotPushed(GenerateInterviewFeedback::class);
        $this->app->call([new GenerateInterviewFeedback($session->id, $user->id), 'handle']);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
        Http::assertNothingSent();
    }

    private function fixture(): array
    {
        $user = User::factory()->create(['status' => 'active', 'is_admin' => false]);
        $category = Category::create(['title' => 'Technical']);
        $session = InterviewSession::create([
            'user_id' => $user->id, 'category_id' => $category->id, 'difficulty' => 'medium',
            'target_position' => 'Database Developer', 'num_questions' => 2, 'response_mode' => 'text',
            'status' => 'in_progress',
        ]);
        $question = Question::create([
            'category_id' => $category->id, 'interview_session_id' => $session->id,
            'question_text' => 'How would you diagnose a slow database query?',
            'type' => 'Technical', 'difficulty' => 'medium', 'status' => 'active',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id, 'question_id' => $question->id,
            'answer_text' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
            'response_mode' => 'text',
        ]);
        return [$user, $session, $answer];
    }

    private function payload(InterviewAnswer $answer): array
    {
        $question = $answer->question->question_text;
        $evidence = preg_split('/\s+(?:before|after)\s+/i', rtrim($answer->answer_text, '.'), 2)[0];
        return [
            'per_question_feedback' => [[
                'id' => $answer->id, 'score' => 82, 'clarity_score' => 80, 'relevance_score' => 86,
                'grammar_score' => 82, 'professionalism_score' => 80, 'star_applicable' => false, 'star_method_score' => 0,
                'evidence_quotes' => [$evidence], 'question_focus' => $question, 'answer_alignment' => 'directly_addressed', 'missing_criteria' => [],
                'ai_feedback' => 'For "'.$question.'", you stated "'.$evidence.'", which directly supports a relevant database query approach. This helps the interviewer assess the database query diagnosis.',
                'better_sample_answer' => $evidence.'. I would keep those same diagnostic steps and connect them to the role requirement. I would add the true final result after confirming it.',
                'follow_up_question' => 'What final result or detail from this answer would make it stronger?',
                'coaching' => [
                    'keep' => 'Keep the detail "'.$evidence.'" for "'.$question.'".',
                    'improve' => 'Add the final database query result or check for "'.$question.'".',
                    'impact' => 'The missing database query result limits how well the interviewer can judge whether the approach worked.',
                    'next_try' => 'Answer "'.$question.'" by linking the database query steps to the verified result.',
                    'next_attempt_steps' => ['Start with the database query symptom you would check first.', 'Use "'.$evidence.'" as support.'],
                    'success_check' => 'The retry links the database query diagnosis to a verified result.',
                ],
            ]],
            'session_feedback' => [
                'overall_summary' => 'Across the database query answer, the response used specific database query details. The next focus is to add the final result.',
                'strengths' => 'The AI review used saved answer details such as "'.$evidence.'" to identify what worked.',
                'weaknesses' => 'The database query answer could add the final result from the same work.',
                'improvement_suggestions' => 'Keep the database query steps and add the outcome only if it is true.',
            ],
        ];
    }

    private function openAiResponse(array $payload): array
    {
        return ['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode($payload)]]]];
    }

    private function geminiResponse(array $payload): array
    {
        return ['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($payload)]]]]]];
    }
}
