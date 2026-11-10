<?php

namespace Tests\Feature;

use App\Jobs\RefreshDetailedFeedbackReview;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use App\Services\DetailedFeedbackReviewService;
use App\Services\AiProviderEvaluationService;
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

    public function test_missing_question_review_is_completed_before_finish_reports_ready(): void
    {
        [$user, $session, $first] = $this->fixture();
        $second = InterviewAnswer::create([
            'interview_session_id' => $session->id, 'question_id' => $first->question_id,
            'answer_text' => 'I checked the database server load, reviewed query latency, and inspected the execution plan before changing the index.',
            'response_mode' => 'text',
        ]);
        $partial = $this->payload($first);
        $secondPayload = $this->payload($second);
        $hosts = [];
        Http::fake(function ($request) use ($session, $partial, $secondPayload, &$hosts) {
            $hosts[] = parse_url($request->url(), PHP_URL_HOST);
            $this->assertSame('processing', $session->fresh()->status);
            $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
            if (count($hosts) === 2) {
                return Http::response($this->geminiResponse($partial));
            }
            return Http::response($this->openAiResponse(count($hosts) === 1 ? $partial : $secondPayload));
        });

        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()->assertJsonPath('review_ready', true)
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertSame(['api.openai.com', 'generativelanguage.googleapis.com', 'api.openai.com'], $hosts);
        $this->assertSame('completed', $session->fresh()->status);
        $service = $this->app->make(DetailedFeedbackReviewService::class);
        $this->assertFalse($service->shouldRefreshWithProvider($session->fresh()));
        foreach ([$first->fresh(), $second->fresh()] as $answer) {
            $state = $service->answerReviewProviderState($answer);
            $this->assertTrue($state['feedback_ready']);
            $this->assertTrue($state['possible_answer_ready']);
        }
        foreach (['desktop', 'mobile'] as $layout) {
            $this->get(route('user.review', $session).'?sr_layout='.$layout)
                ->assertOk()->assertViewIs($layout.'.user.review')->assertSee($first->fresh()->ai_feedback)
                ->assertSee($second->fresh()->better_sample_answer)
                ->assertDontSee('AI feedback is not ready for this question yet.');
        }
        Http::assertSentCount(3);
        Queue::assertNotPushed(RefreshDetailedFeedbackReview::class);
    }

    public function test_changed_answer_is_not_certified_with_stale_provider_output(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $payload = $this->payload($answer);
        $changed = 'I observed the database query investigation but did not inspect the execution plan myself.';
        Http::fake(function () use ($answer, $changed, $payload) {
            InterviewAnswer::whereKey($answer->id)->update(['answer_text' => $changed]);
            return Http::response($this->openAiResponse($payload));
        });

        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(503)->assertJsonMissingPath('redirect_url');

        $this->assertSame('in_progress', $session->fresh()->status);
        $this->assertSame($changed, $answer->fresh()->answer_text);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
        $this->assertFalse((bool) data_get($answer->fresh()->coaching_feedback, 'provider_narrative.verified'));
        Http::assertSentCount(1);
    }

    public function test_provider_failure_completes_with_local_feedback_fallback(): void
    {
        [$user, $session, $answer] = $this->fixture();
        Http::fake(Http::response(['error' => ['message' => 'Too many requests']], 429));

        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()->assertJsonPath('review_ready', true)
            ->assertJsonPath('redirect_url', route('user.review', $session));

        $this->assertSame('completed', $session->fresh()->status);
        $savedAnswer = $answer->fresh();
        $this->assertSame($answer->answer_text, $savedAnswer->answer_text);
        $this->assertSame(1, Score::where('interview_session_id', $session->id)->count());
        $this->assertSame(1, Feedback::where('interview_session_id', $session->id)->count());
        $this->assertContains($savedAnswer->ai_provider, ['local', 'localmodel']);
        $this->assertNotEmpty($savedAnswer->ai_feedback);
        $this->assertTrue((bool) data_get($savedAnswer->coaching_feedback, 'local_fallback'));
        foreach (['desktop', 'mobile'] as $layout) {
            $this->actingAs($user)->get(route('user.review', $session).'?sr_layout='.$layout)
                ->assertOk()->assertViewIs($layout.'.user.review')
                ->assertSee('Local answer feedback is ready.')
                ->assertSee('Your report is based on saved-answer checks while the AI providers recover.')
                ->assertDontSee('AI feedback is not ready for this question yet.');
        }
        Http::assertSentCount(2);
    }

    public function test_existing_completed_report_is_verified_before_finish_returns_ready(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $session->update(['status' => 'completed']);
        Http::fake(Http::response($this->openAiResponse($this->payload($answer))));

        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()->assertJsonPath('review_ready', true);

        $this->assertFalse($this->app->make(DetailedFeedbackReviewService::class)->shouldRefreshWithProvider($session->fresh()));
        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()->assertJsonPath('review_ready', true);
        Http::assertSentCount(1);
    }

    public function test_old_provider_ranking_cannot_block_the_configured_strict_review_cascade(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $this->mock(AiProviderEvaluationService::class, function ($mock): void {
            $mock->shouldReceive('bestProviderKeyForInterviewTask')->andReturn(null);
            $mock->shouldReceive('hasProviderEvidenceForInterviewTask')->andReturn(true);
        });
        Http::fake(Http::response($this->openAiResponse($this->payload($answer))));

        $this->actingAs($user)->withSession(['active_interview_feedback_provider' => 'local'])
            ->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertOk()->assertJsonPath('review_ready', true);

        $this->assertSame('openai', $answer->fresh()->ai_provider);
        Http::assertSentCount(1);
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
