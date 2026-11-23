<?php

namespace Tests\Feature;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Jobs\GenerateAnswerProviderFeedback;
use App\Jobs\GenerateInterviewFeedback;
use App\Jobs\GenerateSessionProviderPreview;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\InterviewSessionProviderPreview;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use App\Services\AIService;
use App\Services\ProviderSessionPreviewService;
use App\Services\DetailedFeedbackReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProviderSessionPreviewTest extends TestCase
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
            'GEMINI_API_KEY' => '', 'GROQ_API_KEY' => '', 'COHERE_API_KEY' => '',
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

    public function test_job_saves_one_compact_provider_overall_review_without_creating_a_full_report(): void
    {
        [$session] = $this->completedSession();
        $summary = $this->summary();
        Http::fake(Http::response($this->openAiResponse(['session_feedback' => $summary])));

        $this->app->call([new GenerateSessionProviderPreview($session->id, $session->user_id), 'handle']);

        $row = InterviewSessionProviderPreview::where('interview_session_id', $session->id)->firstOrFail();
        $this->assertSame('openai', $row->provider_key);
        $this->assertSame($summary, $row->session_feedback);
        $this->assertSame(
            ['provider_key' => 'openai', 'session_feedback' => $summary],
            app(ProviderSessionPreviewService::class)->forSession($session)
        );
        $this->assertSame(0, Feedback::count());
        $this->assertSame(0, Score::count());
        $this->app->call([new GenerateSessionProviderPreview($session->id, $session->user_id), 'handle']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool =>
            data_get($request->data(), 'response_format.json_schema.name') === 'interview_session_feedback'
            && data_get($request->data(), 'max_tokens') === 2048
            && array_keys((array) data_get($request->data(), 'response_format.json_schema.schema.properties', [])) === ['session_feedback']
        );
    }

    public function test_finish_queues_immediate_preview_before_a_short_delayed_full_review(): void
    {
        [$session] = $this->completedSession();
        $session->forceFill(['status' => 'in_progress', 'feedback_status' => null])->save();

        $this->actingAs($session->user)
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202);

        Queue::assertPushed(GenerateSessionProviderPreview::class, 1);
        Queue::assertPushed(GenerateInterviewFeedback::class, fn ($job): bool =>
            $job->delay instanceof \DateTimeInterface && $job->delay->getTimestamp() >= time() + 8
        );
        $types = array_keys(Queue::pushedJobs());
        $this->assertTrue(
            array_search(GenerateSessionProviderPreview::class, $types, true)
            < array_search(GenerateInterviewFeedback::class, $types, true)
        );
        Http::assertNothingSent();
    }

    public function test_current_provider_preview_finishes_prepared_report_without_a_second_summary_request(): void
    {
        [$session, $answer] = $this->completedSession();
        Http::fakeSequence()
            ->push($this->openAiResponse($this->answerPayload($answer)))
            ->push($this->openAiResponse(['session_feedback' => $this->summary()]));

        $this->app->call([new GenerateAnswerProviderFeedback($session->id, $answer->id), 'handle']);
        $this->app->call([new GenerateSessionProviderPreview($session->id, $session->user_id), 'handle']);
        Http::assertSentCount(2);

        $this->assertTrue(app(DetailedFeedbackReviewService::class)->finalizeFromPreparedAnswers($session, true));
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Score::where('interview_session_id', $session->id)->count());
        $this->assertNull(app(ProviderSessionPreviewService::class)->forSession($session));
        $this->app->call([new GenerateSessionProviderPreview($session->id, $session->user_id), 'handle']);
        Http::assertSentCount(2);
    }

    public function test_saved_answer_change_invalidates_provider_preview(): void
    {
        [$session, $answer] = $this->completedSession();
        Http::fake(Http::response($this->openAiResponse(['session_feedback' => $this->summary()])));
        app(ProviderSessionPreviewService::class)->generateAndSave($session);

        $answer->update(['answer_text' => 'I only watched someone else inspect the query plan.']);

        $this->assertNull(app(ProviderSessionPreviewService::class)->forSession($session));
    }

    public function test_answer_change_while_provider_is_running_cannot_save_stale_preview(): void
    {
        [$session, $answer] = $this->completedSession();
        Http::fake(function () use ($answer) {
            $answer->update(['answer_text' => 'I only watched someone else inspect the query plan.']);
            return Http::response($this->openAiResponse(['session_feedback' => $this->summary()]));
        });

        $this->assertFalse(app(ProviderSessionPreviewService::class)->generateAndSave($session));
        $this->assertSame(0, InterviewSessionProviderPreview::count());
    }

    public function test_invalid_provider_summary_is_rejected(): void
    {
        [$session] = $this->completedSession();
        $summary = $this->summary();
        $summary['strengths'] = 'You resolved the database query perfectly.';
        Http::fake(Http::response($this->openAiResponse(['session_feedback' => $summary])));

        try {
            app(ProviderSessionPreviewService::class)->generateAndSave($session);
            $this->fail('Expected the provider summary to fail validation.');
        } catch (AiFeedbackProviderFailureException) {
            $this->assertSame(0, InterviewSessionProviderPreview::count());
        }
    }

    public function test_compact_summary_prompt_includes_late_answers_in_a_long_session(): void
    {
        $answers = [];
        for ($number = 1; $number <= 9; $number++) {
            $answers[] = [
                'id' => $number,
                'question' => 'How would you diagnose a slow database query?',
                'answer' => str_repeat('I inspected the query plan, compared row estimates, and verified index usage. ', 30),
                'is_skipped' => false,
            ];
        }
        $answers[8]['question'] = 'How did you document the final release?';
        $answers[8]['answer'] = 'I documented the final release notes and reviewed deployment logs.';
        Http::fake(Http::response($this->openAiResponse(['session_feedback' => $this->summary()])));

        AIService::generateStandaloneSessionSummary(['target_position' => 'Database Developer'], $answers);

        Http::assertSent(fn ($request): bool =>
            str_contains((string) data_get($request->data(), 'messages.1.content'), 'I documented the final release notes')
        );
    }

    /** @return array{InterviewSession,InterviewAnswer} */
    private function completedSession(): array
    {
        $user = User::factory()->create(['status' => 'active', 'is_admin' => false]);
        $category = Category::create(['title' => 'Technical']);
        $session = InterviewSession::create([
            'user_id' => $user->id, 'category_id' => $category->id,
            'difficulty' => 'medium', 'target_position' => 'Database Developer',
            'num_questions' => 1, 'response_mode' => 'text',
            'status' => 'completed', 'feedback_status' => 'pending',
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

        return [$session, $answer];
    }

    /** @return array<string,string> */
    private function summary(): array
    {
        return [
            'overall_summary' => 'The database query answer gave specific query plan and index usage steps. The next focus is explaining the final result of those checks.',
            'strengths' => 'The answer names concrete steps such as "I inspected the query plan, compared row estimates, and verified index usage", which helps an interviewer understand the database query approach.',
            'weaknesses' => 'The database query answer does not state what the row estimate or index checks showed.',
            'improvement_suggestions' => 'Keep the query plan and index details, then add the true finding from those checks.',
        ];
    }

    private function answerPayload(InterviewAnswer $answer): array
    {
        $question = $answer->question->question_text;
        $evidence = 'I inspected the query plan, compared row estimates, and verified index usage';

        return ['per_question_feedback' => [[
            'id' => $answer->id, 'score' => 82, 'clarity_score' => 80,
            'relevance_score' => 86, 'grammar_score' => 82, 'professionalism_score' => 80,
            'star_applicable' => false, 'star_method_score' => 0,
            'evidence_quotes' => [$evidence], 'question_focus' => $question,
            'answer_alignment' => 'directly_addressed', 'missing_criteria' => [],
            'ai_feedback' => 'For "'.$question.'", you stated "'.$evidence.'", which directly supports a relevant database query approach. This helps the interviewer assess the database query diagnosis.',
            'better_sample_answer' => $evidence.'. I would keep those same diagnostic steps and connect them to the role requirement. I would add the true final result after confirming it.',
            'follow_up_question' => 'What final result or detail from this answer would make it stronger?',
            'coaching' => [
                'keep' => 'Keep the detail "'.$evidence.'" for "'.$question.'".',
                'improve' => 'Add the final database query result or check for "'.$question.'".',
                'impact' => 'The missing database query result limits how well the interviewer can judge whether the approach worked.',
                'next_try' => 'Answer "'.$question.'" by linking the database query steps to the verified result.',
                'next_attempt_steps' => [
                    'Start with the database query symptom you would check first.',
                    'Use "'.$evidence.'" as support.',
                ],
                'success_check' => 'The retry links the database query diagnosis to a verified result.',
            ],
        ]]];
    }

    private function openAiResponse(array $payload): array
    {
        return ['choices' => [[
            'finish_reason' => 'stop',
            'message' => ['content' => json_encode($payload)],
        ]]];
    }
}
