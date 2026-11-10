<?php

namespace Tests\Feature;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProviderOnlyReviewGenerationTest extends TestCase
{
    use RefreshDatabase;

    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
            'GEMINI_MODEL' => 'gemini-test',
            'GROQ_API_KEY' => '',
            'GROQ_API_URL' => 'https://api.groq.com/openai/v1',
            'GROQ_MODEL' => 'groq-test',
            'COHERE_API_KEY' => '',
            'COHERE_API_URL' => 'https://api.cohere.com/v2/chat',
            'COHERE_MODEL' => 'cohere-test',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'openai,gemini',
            'AI_FEEDBACK_MAX_PROVIDERS' => '2',
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

    public function test_strict_review_preserves_provider_prose_without_local_additions(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        Http::fake(Http::response($this->openAiResponse($payload)));

        $review = $this->generate($answer);
        $item = $review['per_question_feedback'][0];
        $this->assertTrue($review['_provider_narratives_verified']);
        $this->assertTrue($review['review_context']['provider_narratives_verified']);
        $this->assertSame('ai_provider', $item['answer_review_source']);
        $this->assertSame('ai_provider', $item['better_sample_answer_source']);
        foreach (['ai_feedback', 'better_sample_answer', 'follow_up_question'] as $field) {
            $this->assertSame($payload['per_question_feedback'][0][$field], $item[$field]);
        }
        $this->assertSame($payload['per_question_feedback'][0]['coaching'], $item['provider_coaching']);
        foreach ($payload['session_feedback'] as $field => $text) {
            $this->assertSame($text, $review['session_feedback'][$field]);
        }
        Http::assertSentCount(1);
    }

    public function test_invalid_coaching_cannot_be_locally_repaired_in_provider_only_mode(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        $payload['per_question_feedback'][0]['coaching']['impact'] = 'You earned a promotion and increased revenue by 50%.';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $this->expectException(AiFeedbackProviderFailureException::class);
        $this->generate($answer);
    }

    public function test_calibrated_scores_do_not_leave_a_valid_provider_review_with_conflicting_alignment(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        foreach (['score', 'clarity_score', 'relevance_score', 'grammar_score', 'professionalism_score'] as $field) {
            $payload['per_question_feedback'][0][$field] = 20;
        }
        $payload['per_question_feedback'][0]['answer_alignment'] = 'not_addressed';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $review = $this->generate($answer);
        $item = $review['per_question_feedback'][0];
        $this->assertTrue($item['score_calibration']['adjustment_applied']);
        $this->assertTrue($item['feedback_quality']['checks']['alignment_cross_checked']);
        $this->assertSame('verified', $item['feedback_quality']['status']);
        $this->assertSame($payload['per_question_feedback'][0]['ai_feedback'], $item['ai_feedback']);
    }

    public function test_strict_review_canonicalizes_provider_alignment_before_rejecting_the_response(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        $payload['per_question_feedback'][0]['relevance_score'] = 86;
        $payload['per_question_feedback'][0]['answer_alignment'] = 'not_addressed';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $review = $this->generate($answer);
        $item = $review['per_question_feedback'][0];

        $this->assertTrue($review['_provider_narratives_verified']);
        $this->assertSame('directly_addressed', $item['answer_alignment']);
        $this->assertSame('directly_addressed', $item['provider_answer_alignment']);
        $this->assertSame($payload['per_question_feedback'][0]['ai_feedback'], $item['ai_feedback']);
        Http::assertSentCount(1);
    }

    public function test_visible_provider_feedback_does_not_require_duplicate_impact_keywords(): void
    {
        $answer = [
            'id' => 73,
            'question' => 'How would you respond to an upset customer?',
            'question_type' => 'Technical',
            'answer' => 'I listened to the customer and checked the order status before sharing the delivery update.',
        ];
        $feedback = 'You stated "I listened to the customer and checked the order status". Keep that sequence and add the follow-up.';
        $this->assertTrue(AIService::visibleReviewFeedbackIsProviderUsable($feedback, $answer));
        $this->assertFalse(AIService::visibleReviewFeedbackIsProviderUsable(
            'You earned a promotion and increased revenue by 50%.', $answer
        ));
        $this->assertFalse(AIService::visibleReviewFeedbackIsProviderUsable(
            'Your nervous personality proves you are dishonest.', $answer
        ));
    }

    public function test_invalid_summary_cannot_be_locally_repaired_in_provider_only_mode(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        $payload['session_feedback']['overall_summary'] = 'You earned a promotion and increased revenue by 50%.';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $this->expectException(AiFeedbackProviderFailureException::class);
        $this->generate($answer);
    }

    public function test_strict_review_fails_over_to_another_api_provider(): void
    {
        $answer = $this->answer();
        $valid = $this->payload($answer);
        $invalid = $valid;
        $invalid['per_question_feedback'][0]['coaching']['impact'] = 'You earned a promotion and increased revenue by 50%.';
        Http::fake(function ($request) use ($valid, $invalid) {
            if (str_contains($request->url(), 'api.openai.com')) {
                return Http::response($this->openAiResponse($invalid));
            }

            return Http::response(['candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [['text' => json_encode($valid)]]],
            ]]]);
        });
        $review = $this->generate($answer, false);
        $this->assertSame('gemini', $review['_provider_key']);
        $this->assertSame(['openai', 'gemini'], $review['_providers_attempted']);
        $this->assertTrue($review['_provider_narratives_verified']);
        $this->assertFalse($review['_repair_applied']);
        $this->assertSame($valid['per_question_feedback'][0]['ai_feedback'], $review['per_question_feedback'][0]['ai_feedback']);
        Http::assertSentCount(2);
    }

    public function test_strict_review_completes_only_the_missing_question_with_provider_calls(): void
    {
        $first = $this->answer();
        $second = array_replace($first, [
            'id' => 72,
            'answer' => 'I checked the database server load, reviewed query latency, and inspected the execution plan before changing the index.',
        ]);
        $partial = $this->payload($first);
        $secondPayload = $this->payload($second);
        $hosts = [];
        Http::fake(function ($request, array $options) use ($partial, $secondPayload, &$hosts) {
            $hosts[] = parse_url($request->url(), PHP_URL_HOST);
            $this->assertGreaterThan(0, $options['timeout']);
            if (count($hosts) === 2) {
                return Http::response($this->geminiResponse($partial));
            }
            return Http::response($this->openAiResponse(count($hosts) === 1 ? $partial : $secondPayload));
        });

        $review = AIService::generateFeedback(['target_position' => 'Database Developer'], [$first, $second], 'openai', false, false, [
            'require_provider_narratives' => true, 'allow_provider_completion' => true,
            'deadline_seconds' => 30, 'max_attempts' => 1, 'http_attempts' => 1, 'retry_delay_ms' => 0,
        ]);

        $this->assertSame(['api.openai.com', 'generativelanguage.googleapis.com', 'api.openai.com'], $hosts);
        $this->assertTrue($review['_provider_completion_applied']);
        $this->assertFalse($review['_repair_applied']);
        $this->assertTrue($review['_provider_narratives_verified']);
        $this->assertSame([71, 72], array_column($review['per_question_feedback'], 'id'));
        $this->assertSame($partial['per_question_feedback'][0]['ai_feedback'], $review['per_question_feedback'][0]['ai_feedback']);
        $this->assertSame($secondPayload['per_question_feedback'][0]['ai_feedback'], $review['per_question_feedback'][1]['ai_feedback']);
        foreach ($partial['session_feedback'] as $field => $text) {
            $this->assertSame($text, $review['session_feedback'][$field]);
        }
        Http::assertSentCount(3);
    }

    public function test_strict_review_retries_invalid_session_notes_with_an_api_without_rewriting_valid_answers(): void
    {
        $answer = $this->answer();
        $valid = $this->payload($answer);
        $invalid = $valid;
        $invalid['session_feedback']['overall_summary'] = 'You earned a promotion and increased revenue by 50%.';
        $count = 0;
        Http::fake(function ($request) use ($valid, $invalid, &$count) {
            $count++;
            if ($count === 2) {
                return Http::response($this->geminiResponse($invalid));
            }
            if ($count === 3) {
                $this->assertSame('interview_session_feedback', data_get($request->data(), 'response_format.json_schema.name'));
                return Http::response($this->openAiResponse(['session_feedback' => $valid['session_feedback']]));
            }
            return Http::response($this->openAiResponse($invalid));
        });

        $review = $this->generate($answer, false, 'openai', ['allow_provider_completion' => true]);
        $this->assertFalse($review['_repair_applied']);
        $this->assertTrue($review['_provider_completion_applied']);
        foreach ($valid['session_feedback'] as $field => $text) {
            $this->assertSame($text, $review['session_feedback'][$field]);
        }
        $this->assertSame($valid['per_question_feedback'][0]['ai_feedback'], $review['per_question_feedback'][0]['ai_feedback']);
        Http::assertSentCount(3);
    }

    public function test_provider_completion_cannot_bypass_the_final_review_validator(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        $invalid = $payload;
        $invalid['session_feedback']['overall_summary'] = 'You earned a promotion and increased revenue by 50%.';
        Http::fakeSequence()
            ->push($this->openAiResponse($invalid))
            ->push($this->openAiResponse(['session_feedback' => $payload['session_feedback']]));
        $validatedCompletion = false;

        try {
            $this->generate($answer, true, 'openai', [
                'allow_provider_completion' => true,
                'response_validator' => function (array $review) use (&$validatedCompletion): bool {
                    $validatedCompletion = $review['_provider_completion_applied'] ?? false;
                    return false;
                },
            ]);
            $this->fail('A failed final safeguard must never become a completed review.');
        } catch (AiFeedbackProviderFailureException) {
            $this->assertTrue($validatedCompletion);
        }
        Http::assertSentCount(2);
    }

    public function test_short_answer_keeps_the_provider_note_and_can_leave_the_draft_empty(): void
    {
        $answer = array_replace($this->answer(), ['answer' => 'Index.']);
        $payload = $this->payload($answer);
        foreach (['score', 'clarity_score', 'relevance_score', 'grammar_score', 'professionalism_score'] as $field) {
            $payload['per_question_feedback'][0][$field] = 5;
        }
        $payload['per_question_feedback'][0]['answer_alignment'] = 'insufficient_evidence';
        $payload['per_question_feedback'][0]['ai_feedback'] = 'The saved answer "Index" is too short to assess the database query approach. Add the exact check and what it showed.';
        $payload['per_question_feedback'][0]['better_sample_answer'] = '';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $review = $this->generate($answer);
        $item = $review['per_question_feedback'][0];
        $this->assertSame($payload['per_question_feedback'][0]['ai_feedback'], $item['ai_feedback']);
        $this->assertSame('', $item['better_sample_answer']);
        $this->assertSame('none', $item['better_sample_answer_source']);
        $this->assertSame('ai_provider', $item['answer_review_source']);
        $this->assertTrue($item['feedback_quality']['checks']['next_attempt_actionable']);
        $this->assertSame('verified', $item['feedback_quality']['status']);
    }

    public function test_strict_review_starts_with_openai_despite_preference_and_reordered_priority(): void
    {
        $this->setEnvironment('AI_FEEDBACK_PROVIDER_PRIORITY', 'gemini,openai');
        $this->setEnvironment('AI_FEEDBACK_MAX_PROVIDERS', '1');
        $answer = $this->answer();
        Http::fake(Http::response($this->openAiResponse($this->payload($answer))));

        $review = $this->generate($answer, false, 'gemini');

        $this->assertSame('openai', $review['_provider_key']);
        $this->assertSame(['openai'], $review['_providers_attempted']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.openai.com'));
        Http::assertSentCount(1);
    }

    public function test_strict_review_honors_the_dedicated_detailed_review_priority_and_cap(): void
    {
        $this->setEnvironment('AI_DETAILED_REVIEW_PROVIDER_PRIORITY', 'gemini,openai');
        $this->setEnvironment('AI_DETAILED_REVIEW_MAX_PROVIDERS', '1');

        $this->assertSame(['gemini'], AIService::detailedReviewProviderPriority());
    }

    public function test_strict_review_tries_all_configured_providers_in_order_despite_general_provider_cap(): void
    {
        $this->setEnvironment('AI_FEEDBACK_PROVIDER_PRIORITY', 'cohere,groq,gemini,openai');
        $this->setEnvironment('AI_FEEDBACK_MAX_PROVIDERS', '1');
        $this->setEnvironment('GROQ_API_KEY', 'groq_test_token');
        $this->setEnvironment('COHERE_API_KEY', 'cohere_test_token');
        $answer = $this->answer();
        $payload = $this->payload($answer);
        $hosts = [];
        Http::fake(function ($request) use ($payload, &$hosts) {
            $hosts[] = parse_url($request->url(), PHP_URL_HOST);
            if (str_contains($request->url(), 'api.cohere.com')) {
                return Http::response([
                    'finish_reason' => 'COMPLETE',
                    'message' => ['content' => [['type' => 'text', 'text' => json_encode($payload)]]],
                ]);
            }

            return Http::response(['error' => ['message' => 'Temporarily unavailable']], 503);
        });

        $review = $this->generate($answer, false, 'cohere');

        $this->assertSame(['api.openai.com', 'generativelanguage.googleapis.com', 'api.groq.com', 'api.cohere.com'], $hosts);
        $this->assertSame(['openai', 'gemini', 'groq', 'cohere'], $review['_providers_attempted']);
        $this->assertSame('cohere', $review['_provider_key']);
        $this->assertTrue($review['_provider_narratives_verified']);
        Http::assertSentCount(4);
    }

    public function test_strict_review_skips_providers_without_credentials(): void
    {
        $this->setEnvironment('OPENAI_API_KEY', '');
        $answer = $this->answer();
        Http::fake(Http::response($this->geminiResponse($this->payload($answer))));

        $this->assertSame(['gemini'], AIService::detailedReviewProviderPriority());
        $review = $this->generate($answer, false);

        $this->assertSame('gemini', $review['_provider_key']);
        $this->assertSame(['gemini'], $review['_providers_attempted']);
        Http::assertSentCount(1);
    }

    public function test_explicit_provider_only_review_still_targets_the_named_provider(): void
    {
        $answer = $this->answer();
        Http::fake(Http::response($this->geminiResponse($this->payload($answer))));

        $review = $this->generate($answer, true, 'gemini');

        $this->assertSame('gemini', $review['_provider_key']);
        $this->assertSame(['gemini'], $review['_providers_attempted']);
        Http::assertSentCount(1);
    }

    public function test_strict_review_fails_without_sending_empty_credentials_when_no_api_is_configured(): void
    {
        $this->setEnvironment('OPENAI_API_KEY', '');
        $this->setEnvironment('GEMINI_API_KEY', '');
        Http::fake();
        $this->assertSame([], AIService::detailedReviewProviderPriority());

        $this->expectException(AiFeedbackProviderFailureException::class);
        try {
            $this->generate($this->answer(), false);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_strict_review_automatically_requests_corrected_provider_prose_after_validation_failure(): void
    {
        $answer = $this->answer();
        $valid = $this->payload($answer);
        $invalid = $valid;
        $invalid['per_question_feedback'][0]['coaching']['impact'] = 'You earned a promotion and increased revenue by 50%.';
        $prompts = [];
        Http::fake(function ($request) use ($valid, $invalid, &$prompts) {
            $prompts[] = data_get($request->data(), 'messages.1.content');

            return Http::response($this->openAiResponse(count($prompts) === 1 ? $invalid : $valid));
        });

        $review = $this->generate($answer, false, 'openai', ['max_attempts' => 2]);

        $this->assertSame('openai', $review['_provider_key']);
        $this->assertSame(['openai'], $review['_providers_attempted']);
        $this->assertStringContainsString('YOUR PREVIOUS REVIEW FAILED VALIDATION', $prompts[1]);
        $this->assertSame($valid['per_question_feedback'][0]['coaching'], $review['per_question_feedback'][0]['provider_coaching']);
        $this->assertTrue($review['_provider_narratives_verified']);
        $this->assertFalse($review['_repair_applied']);
        Http::assertSentCount(2);
    }

    public function test_final_saved_answer_validation_failure_continues_to_gemini_in_the_same_review_call(): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        Http::fake(fn ($request) => Http::response(str_contains($request->url(), 'api.openai.com')
            ? $this->openAiResponse($payload)
            : $this->geminiResponse($payload)));
        $validatedProviders = [];

        $review = $this->generate($answer, false, 'openai', [
            'response_validator' => function (array $normalized, string $provider) use (&$validatedProviders): bool {
                $this->assertSame($provider, $normalized['_provider_key']);
                $this->assertTrue($normalized['_provider_narratives_verified']);
                $validatedProviders[] = $provider;

                return $provider === 'gemini';
            },
        ]);

        $this->assertSame(['openai', 'gemini'], $validatedProviders);
        $this->assertSame(['openai', 'gemini'], $review['_providers_attempted']);
        $this->assertSame('gemini', $review['_provider_key']);
        $this->assertFalse($review['_repair_applied']);
        Http::assertSentCount(2);
    }

    public function test_other_feedback_still_honors_the_requested_provider(): void
    {
        $answer = $this->answer();
        Http::fake(Http::response($this->geminiResponse($this->payload($answer))));

        $review = $this->generate($answer, false, 'gemini', ['require_provider_narratives' => false]);

        $this->assertSame('gemini', $review['_provider_key']);
        $this->assertSame(['gemini'], $review['_providers_attempted']);
        Http::assertSentCount(1);
    }

    #[DataProvider('terminalHttpStatuses')]
    public function test_strict_review_moves_to_gemini_after_terminal_http_failure_without_repeating_the_prompt(int $status): void
    {
        $answer = $this->answer();
        $payload = $this->payload($answer);
        $hosts = [];
        Http::fake(function ($request) use ($payload, $status, &$hosts) {
            $hosts[] = parse_url($request->url(), PHP_URL_HOST);
            if (str_contains($request->url(), 'api.openai.com')) {
                return Http::response(['error' => ['message' => 'Request unavailable']], $status, ['Retry-After' => '60']);
            }

            return Http::response($this->geminiResponse($payload));
        });

        $review = $this->generate($answer, false, 'openai', ['max_attempts' => 2]);

        $this->assertSame(['api.openai.com', 'generativelanguage.googleapis.com'], $hosts);
        $this->assertSame('gemini', $review['_provider_key']);
        Http::assertSentCount(2);
    }

    public static function terminalHttpStatuses(): array
    {
        return ['bad request' => [400], 'invalid key' => [401], 'forbidden' => [403], 'missing model' => [404], 'rate limited' => [429]];
    }

    private function generate(array $answer, bool $providerOnly = true, string $provider = 'openai', array $runtimeOptions = []): array
    {
        return AIService::generateFeedback(['target_position' => 'Database Developer'], [$answer], $provider, $providerOnly, false, array_replace([
            'require_provider_narratives' => true,
            'deadline_seconds' => 20,
            'max_attempts' => 1,
            'http_attempts' => 1,
            'retry_delay_ms' => 0,
        ], $runtimeOptions));
    }

    public function test_skipped_response_keeps_the_provider_note_without_inventing_a_draft(): void
    {
        $answer = array_replace($this->answer(), ['answer' => '', 'is_skipped' => true]);
        $payload = $this->payload($answer);
        foreach (['score', 'clarity_score', 'relevance_score', 'grammar_score', 'professionalism_score'] as $field) {
            $payload['per_question_feedback'][0][$field] = 0;
        }
        $payload['per_question_feedback'][0]['answer_alignment'] = 'skipped';
        $payload['per_question_feedback'][0]['evidence_quotes'] = [];
        $payload['per_question_feedback'][0]['ai_feedback'] = 'No database query answer was saved. The skipped response cannot support a diagnosis review.';
        $payload['per_question_feedback'][0]['better_sample_answer'] = '';
        $payload['session_feedback']['strengths'] = 'No saved database query answer supports an observed strength.';
        Http::fake(Http::response($this->openAiResponse($payload)));

        $review = $this->generate($answer);
        $item = $review['per_question_feedback'][0];
        $this->assertSame($payload['per_question_feedback'][0]['ai_feedback'], $item['ai_feedback']);
        $this->assertSame('', $item['better_sample_answer']);
        $this->assertSame('none', $item['better_sample_answer_source']);
        $this->assertTrue($item['is_skipped']);
    }

    private function answer(): array
    {
        return [
            'id' => 71,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
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

    private function setEnvironment(string $key, string $value): void
    {
        if (! array_key_exists($key, $this->originalEnv)) {
            $this->originalEnv[$key] = getenv($key);
        }
        putenv("{$key}={$value}");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }

    private function payload(array $answer): array
    {
        $question = $answer['question'];
        $evidence = preg_split('/\s+(?:before|after)\s+/i', rtrim($answer['answer'], '.'), 2)[0];
        return [
            'per_question_feedback' => [[
                'id' => $answer['id'], 'score' => 82, 'clarity_score' => 80, 'relevance_score' => 86,
                'grammar_score' => 82, 'professionalism_score' => 80, 'star_applicable' => false, 'star_method_score' => 0,
                'evidence_quotes' => [$evidence], 'question_focus' => $question, 'answer_alignment' => 'directly_addressed', 'missing_criteria' => [],
                'ai_feedback' => 'For "'.$question.'", you stated "'.$evidence.'", which directly supports a relevant database query approach.',
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
}
