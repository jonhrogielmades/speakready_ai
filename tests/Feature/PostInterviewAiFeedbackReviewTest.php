<?php

namespace Tests\Feature;

use App\Jobs\GenerateInterviewFeedback;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PostInterviewAiFeedbackReviewTest extends TestCase
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
            'GEMINI_API_KEY' => '',
            'GROQ_API_KEY' => '',
            'COHERE_API_KEY' => '',
            'AI_DETAILED_REVIEW_PROVIDER_PRIORITY' => 'openai',
            'AI_DETAILED_REVIEW_MAX_PROVIDERS' => '1',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'openai',
            'AI_FEEDBACK_MAX_PROVIDERS' => '1',
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

    public function test_pending_review_does_not_show_a_saved_summary_as_provider_feedback(): void
    {
        [$user, $session] = $this->fixture();

        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202)
            ->assertJsonPath('redirect_url', route('user.review', $session));

        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Stale provider summary must stay hidden.',
            'weaknesses' => 'Stale provider summary must stay hidden.',
            'improvement_suggestions' => 'Stale provider summary must stay hidden.',
            'coaching_summary' => ['overall_summary' => 'Stale provider summary must stay hidden.'],
        ]);

        foreach (['desktop', 'mobile'] as $device) {
            $response = $this->actingAs($user)
                ->withHeader('User-Agent', $this->userAgent($device))
                ->get(route('user.review', $session));

            $response->assertOk()
                ->assertSee('Your AI answer reviews are being prepared.')
                ->assertDontSee('AI Feedback Review')
                ->assertDontSee('Stale provider summary must stay hidden.');
        }

        Http::assertNothingSent();
    }

    public function test_verified_provider_report_renders_ai_feedback_review_after_interview(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202);

        Http::fake(Http::response([
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => json_encode($this->providerPayload($answer))],
            ]],
        ]));
        $this->app->call([new GenerateInterviewFeedback($session->id, $user->id), 'handle']);

        $this->assertSame('verified', $session->fresh()->feedback_status);

        foreach (['desktop', 'mobile'] as $device) {
            $this->actingAs($user)
                ->withHeader('User-Agent', $this->userAgent($device))
                ->get(route('user.review', $session))
                ->assertOk()
                ->assertSee('AI Feedback Review')
                ->assertSee('Overall Review')
                ->assertSee('Across the database query answer, the response used specific database query details.');
        }

        Http::assertSentCount(1);
    }

    public function test_tampered_local_fallback_answer_cannot_keep_a_completed_ai_review_visible(): void
    {
        [$user, $session, $answer] = $this->fixture();
        $this->actingAs($user)->postJson(route('interview.finish'), ['session_id' => $session->id])
            ->assertStatus(202);

        Http::fake(Http::response([
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => json_encode($this->providerPayload($answer))],
            ]],
        ]));
        $this->app->call([new GenerateInterviewFeedback($session->id, $user->id), 'handle']);
        $this->assertSame('verified', $session->fresh()->feedback_status);

        $answer->refresh();
        $coaching = $answer->coaching_feedback;
        $coaching['local_fallback'] = true;
        $answer->update([
            'ai_feedback' => 'Tampered local narrative must never appear as provider feedback.',
            'coaching_feedback' => $coaching,
        ]);

        foreach (['desktop', 'mobile'] as $device) {
            $this->actingAs($user)
                ->withHeader('User-Agent', $this->userAgent($device))
                ->get(route('user.review', $session))
                ->assertOk()
                ->assertSee('Retry AI review')
                ->assertSee('Provider feedback will appear after the AI review passes validation.')
                ->assertDontSee('AI Feedback Review')
                ->assertDontSee('Across the database query answer, the response used specific database query details.')
                ->assertDontSee('Tampered local narrative must never appear as provider feedback.');
        }
    }

    private function fixture(): array
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
            'status' => 'in_progress',
        ]);
        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'How would you diagnose a slow database query?',
            'type' => 'Technical',
            'difficulty' => 'medium',
            'status' => 'active',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
            'response_mode' => 'text',
        ]);

        return [$user, $session, $answer];
    }

    private function providerPayload(InterviewAnswer $answer): array
    {
        $question = $answer->question->question_text;
        $evidence = 'I inspected the query plan, compared row estimates, and verified index usage';

        return [
            'per_question_feedback' => [[
                'id' => $answer->id,
                'score' => 82,
                'clarity_score' => 80,
                'relevance_score' => 86,
                'grammar_score' => 82,
                'professionalism_score' => 80,
                'star_applicable' => false,
                'star_method_score' => 0,
                'evidence_quotes' => [$evidence],
                'question_focus' => $question,
                'answer_alignment' => 'directly_addressed',
                'missing_criteria' => [],
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
            ]],
            'session_feedback' => [
                'overall_summary' => 'Across the database query answer, the response used specific database query details. The next focus is to add the final result.',
                'strengths' => 'The AI review used saved answer details such as "'.$evidence.'" to identify what worked.',
                'weaknesses' => 'The database query answer could add the final result from the same work.',
                'improvement_suggestions' => 'Keep the database query steps and add the outcome only if it is true.',
            ],
        ];
    }

    private function userAgent(string $device): string
    {
        return $device === 'mobile'
            ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148'
            : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36';
    }
}
