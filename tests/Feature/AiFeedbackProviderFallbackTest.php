<?php

namespace Tests\Feature;

use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AiFeedbackProviderFallbackTest extends TestCase
{
    use RefreshDatabase;

    private array $originalEnvValues = [];

    protected function tearDown(): void
    {
        foreach ($this->originalEnvValues as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        parent::tearDown();
    }

    public function test_feedback_timeout_uses_the_full_provider_slice_and_leaves_http_retries_opportunistic(): void
    {
        $method = new \ReflectionMethod(AIService::class, 'fairRequestTimeoutSeconds');
        $method->setAccessible(true);

        $this->assertSame(15, $method->invoke(null, 120.0, 8, 45));
        $this->assertSame(45, $method->invoke(null, 120.0, 1, 45));
        $this->assertSame(1, $method->invoke(null, 3.0, 8, 45));
    }

    #[DataProvider('documentedFeedbackDeadlineProvider')]
    public function test_documented_feedback_deadlines_provide_one_viable_slice_per_provider(
        float $deadlineSeconds,
        int $configuredTimeoutSeconds,
        int $expectedProviderTimeoutSeconds
    ): void {
        $method = new \ReflectionMethod(AIService::class, 'fairRequestTimeoutSeconds');
        $method->setAccessible(true);

        $this->assertSame(
            $expectedProviderTimeoutSeconds,
            $method->invoke(null, $deadlineSeconds, 4, $configuredTimeoutSeconds)
        );
    }

    public static function documentedFeedbackDeadlineProvider(): array
    {
        return [
            'generic feedback' => [150.0, 45, 37],
            'detailed review refresh' => [60.0, 30, 15],
            'interview finalization and game enrichment' => [90.0, 30, 22],
        ];
    }

    public function test_feedback_cascade_applies_provider_level_slices_and_continues_to_the_next_provider(): void
    {
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
            'GEMINI_MODEL' => 'gemini-test',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'openai,gemini',
            'AI_FEEDBACK_MAX_PROVIDERS' => '2',
            'AI_PROVIDER_RETRY_DELAY_MS' => '0',
            'AI_LIVE_TESTS' => 'false',
        ] as $key => $value) {
            $this->setEnvValue($key, $value);
        }

        $answer = [
            'id' => 70,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
        ];
        $validPayload = $this->validFeedbackPayload($answer);
        $invalidPayload = $validPayload;
        $invalidPayload['per_question_feedback'][0]['id'] = 999;
        $timeouts = [];

        Http::fake(function ($request, array $options) use ($invalidPayload, $validPayload, &$timeouts) {
            $provider = str_contains($request->url(), 'api.openai.com') ? 'openai' : 'gemini';
            $timeouts[$provider] = (float) ($options['timeout'] ?? 0);

            if ($provider === 'openai') {
                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => json_encode($invalidPayload)],
                    ]],
                ]);
            }

            return Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => json_encode($validPayload)]]],
                ]],
            ]);
        });

        $feedback = AIService::generateFeedback(
            ['target_position' => 'Database Developer', 'difficulty' => 'Medium'],
            [$answer],
            'openai',
            false,
            false,
            [
                'timeout_seconds' => 45,
                'deadline_seconds' => 30,
                'max_attempts' => 1,
                'http_attempts' => 2,
                'retry_delay_ms' => 0,
            ]
        );

        $this->assertSame('gemini', $feedback['_provider_key']);
        $this->assertSame(['openai', 'gemini'], $feedback['_providers_attempted']);
        $this->assertGreaterThanOrEqual(14.0, $timeouts['openai']);
        $this->assertLessThanOrEqual(15.0, $timeouts['openai']);
        $this->assertGreaterThan($timeouts['openai'], $timeouts['gemini']);
        $this->assertLessThanOrEqual(30.0, $timeouts['gemini']);
    }

    public function test_immediate_transient_retry_uses_leftover_provider_slice_without_blocking_the_next_provider(): void
    {
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
            'GEMINI_MODEL' => 'gemini-test',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'openai,gemini',
            'AI_FEEDBACK_MAX_PROVIDERS' => '2',
            'AI_PROVIDER_RETRY_DELAY_MS' => '0',
            'AI_LIVE_TESTS' => 'false',
        ] as $key => $value) {
            $this->setEnvValue($key, $value);
        }

        $answer = [
            'id' => 701,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
        ];
        $validPayload = $this->validFeedbackPayload($answer);
        $invalidPayload = $validPayload;
        $invalidPayload['per_question_feedback'][0]['id'] = 999;
        $requestCounts = ['openai' => 0, 'gemini' => 0];
        $timeouts = ['openai' => [], 'gemini' => []];

        Http::fake(function ($request, array $options) use (
            $invalidPayload,
            $validPayload,
            &$requestCounts,
            &$timeouts
        ) {
            $provider = str_contains($request->url(), 'api.openai.com') ? 'openai' : 'gemini';
            $requestCounts[$provider]++;
            $timeouts[$provider][] = (float) ($options['timeout'] ?? 0);

            if ($provider === 'openai' && $requestCounts[$provider] === 1) {
                return Http::response(['error' => ['message' => 'temporarily unavailable']], 503);
            }

            if ($provider === 'openai') {
                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => json_encode($invalidPayload)],
                    ]],
                ]);
            }

            return Http::response([
                'candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => json_encode($validPayload)]]],
                ]],
            ]);
        });

        $feedback = AIService::generateFeedback(
            ['target_position' => 'Database Developer', 'difficulty' => 'Medium'],
            [$answer],
            'openai',
            false,
            false,
            [
                'timeout_seconds' => 45,
                'deadline_seconds' => 30,
                'max_attempts' => 1,
                'http_attempts' => 2,
                'retry_delay_ms' => 0,
            ]
        );

        $this->assertSame('gemini', $feedback['_provider_key']);
        $this->assertSame(['openai', 'gemini'], $feedback['_providers_attempted']);
        $this->assertSame(['openai' => 2, 'gemini' => 1], $requestCounts);
        $this->assertCount(2, $timeouts['openai']);
        $this->assertSame($timeouts['openai'][0], $timeouts['openai'][1]);
        $this->assertGreaterThanOrEqual(14.0, $timeouts['openai'][0]);
        $this->assertLessThanOrEqual(15.0, $timeouts['openai'][0]);
        $this->assertCount(1, $timeouts['gemini']);
        $this->assertGreaterThan($timeouts['openai'][0], $timeouts['gemini'][0]);
        $this->assertLessThanOrEqual(30.0, $timeouts['gemini'][0]);
    }

    public function test_feedback_retry_after_delay_is_bounded_inside_the_provider_attempt_slice(): void
    {
        $method = new \ReflectionMethod(AIService::class, 'boundedProviderRetryDelayMilliseconds');
        $method->setAccessible(true);

        $deadlineAt = microtime(true) + 3.0;
        $bounded = $method->invoke(null, 30000, $deadlineAt, 2);

        $this->assertGreaterThanOrEqual(800, $bounded);
        $this->assertLessThanOrEqual(1000, $bounded);
        $this->assertSame(30000, $method->invoke(null, 30000, null, 2));
    }

    public function test_semantically_invalid_openai_feedback_falls_back_to_strict_gemini_feedback_without_local_repair(): void
    {
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
            'GEMINI_MODEL' => 'gemini-test',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'openai,gemini',
            'AI_FEEDBACK_MAX_PROVIDERS' => '2',
            'AI_FEEDBACK_MAX_OUTPUT_TOKENS' => '16384',
            'AI_PROVIDER_RETRY_DELAY_MS' => '0',
            'AI_LIVE_TESTS' => 'false',
        ] as $key => $value) {
            $this->setEnvValue($key, $value);
        }

        $answer = [
            'id' => 71,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
        ];
        $validPayload = $this->validFeedbackPayload($answer);
        $invalidPayload = $validPayload;
        $invalidPayload['per_question_feedback'][0]['evidence_quotes'] = ['I increased revenue by 50%.'];
        $invalidPayload['per_question_feedback'][0]['ai_feedback'] = 'You stated "I increased revenue by 50%.", which proves the database change produced a large business result.';

        Http::fake(function ($request) use ($invalidPayload, $validPayload) {
            if (str_contains($request->url(), 'api.openai.com')) {
                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => json_encode($invalidPayload)],
                    ]],
                ], 200);
            }

            if (str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return Http::response([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => [
                            'parts' => [[
                                'text' => json_encode($validPayload),
                            ]],
                        ],
                    ]],
                ], 200);
            }

            return Http::response(['error' => 'Unexpected provider request.'], 500);
        });

        $feedback = AIService::generateFeedback(
            [
                'target_position' => 'Database Developer',
                'difficulty' => 'Medium',
                'interview_focus' => 'Technical diagnosis',
                'country' => 'General',
            ],
            [$answer],
            'openai',
            false,
            false,
            [
                'timeout_seconds' => 5,
                'deadline_seconds' => 30,
                'max_attempts' => 1,
                'http_attempts' => 1,
                'retry_delay_ms' => 0,
            ]
        );

        $this->assertSame('gemini', $feedback['_provider_key']);
        $this->assertSame(['openai', 'gemini'], $feedback['_providers_attempted']);
        $this->assertTrue($feedback['_strict_validation_passed']);
        $this->assertFalse($feedback['_repair_applied']);
        $this->assertSame('gemini', data_get($feedback, 'review_context.provider_key'));
        $this->assertSame('ai_provider', data_get($feedback, 'review_context.provider_source'));
        $this->assertSame('ai_evidence_validated', data_get($feedback, 'per_question_feedback.0.evaluation_source'));
        $this->assertSame('ai_provider', data_get($feedback, 'per_question_feedback.0.better_sample_answer_source'));
        $this->assertSame('gemini', data_get($feedback, 'per_question_feedback.0.better_sample_answer_provider'));
        $this->assertSame(
            ['I inspected the query plan, compared row estimates, and verified index usage'],
            data_get($feedback, 'per_question_feedback.0.evidence_quotes')
        );
        $this->assertStringContainsString('query plan', data_get($feedback, 'per_question_feedback.0.ai_feedback'));
        $this->assertStringNotContainsString('50%', data_get($feedback, 'per_question_feedback.0.ai_feedback'));

        $requests = collect(Http::recorded())
            ->map(fn (array $entry) => $entry[0])
            ->values();
        $urls = $requests->map(fn ($request): string => $request->url())->all();

        $this->assertCount(2, $urls);
        $this->assertStringContainsString('api.openai.com', $urls[0]);
        $this->assertStringContainsString('generativelanguage.googleapis.com', $urls[1]);
        $this->assertSame(16384, data_get($requests[0]->data(), 'max_tokens'));
        $this->assertSame(16384, data_get($requests[1]->data(), 'generationConfig.maxOutputTokens'));
    }

    public function test_feedback_cascade_reaches_cohere_after_three_invalid_providers_and_preserves_output_budget(): void
    {
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
            'GEMINI_MODEL' => 'gemini-test',
            'GROQ_API_KEY' => 'groq_test_token',
            'GROQ_API_URL' => 'https://api.groq.com/openai/v1',
            'GROQ_MODEL' => 'groq-test',
            'COHERE_API_KEY' => 'cohere_test_token',
            'COHERE_API_URL' => 'https://api.cohere.com',
            'COHERE_MODEL' => 'cohere-test',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'openai,gemini,groq,cohere',
            'AI_FEEDBACK_MAX_PROVIDERS' => '4',
            'AI_FEEDBACK_MAX_OUTPUT_TOKENS' => '12288',
            'AI_PROVIDER_RETRY_DELAY_MS' => '0',
            'AI_LIVE_TESTS' => 'false',
        ] as $key => $value) {
            $this->setEnvValue($key, $value);
        }

        $answer = [
            'id' => 72,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
        ];
        $validPayload = $this->validFeedbackPayload($answer);
        $invalidPayload = $validPayload;
        $invalidPayload['per_question_feedback'][0]['id'] = 999;

        Http::fake(function ($request) use ($invalidPayload, $validPayload) {
            return match (true) {
                str_contains($request->url(), 'api.openai.com') => Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => json_encode($invalidPayload)],
                    ]],
                ]),
                str_contains($request->url(), 'generativelanguage.googleapis.com') => Http::response([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => json_encode($invalidPayload)]]],
                    ]],
                ]),
                str_contains($request->url(), 'api.groq.com') => Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => json_encode($invalidPayload)],
                    ]],
                ]),
                str_contains($request->url(), 'api.cohere.com') => Http::response([
                    'finish_reason' => 'COMPLETE',
                    'message' => ['content' => [[
                        'type' => 'text',
                        'text' => json_encode($validPayload),
                    ]]],
                ]),
                default => Http::response(['error' => 'Unexpected provider request.'], 500),
            };
        });

        $feedback = AIService::generateFeedback(
            [
                'target_position' => 'Database Developer',
                'difficulty' => 'Medium',
                'interview_focus' => 'Technical diagnosis',
                'country' => 'General',
            ],
            [$answer],
            'openai',
            false,
            false,
            [
                'timeout_seconds' => 5,
                'deadline_seconds' => 30,
                'max_attempts' => 1,
                'http_attempts' => 1,
                'retry_delay_ms' => 0,
            ]
        );

        $this->assertSame('cohere', $feedback['_provider_key']);
        $this->assertSame(['openai', 'gemini', 'groq', 'cohere'], $feedback['_providers_attempted']);
        $this->assertTrue($feedback['_strict_validation_passed']);
        $this->assertFalse($feedback['_repair_applied']);

        $requests = collect(Http::recorded())->map(fn (array $entry) => $entry[0])->values();
        $this->assertCount(4, $requests);
        $this->assertSame(12288, data_get($requests[0]->data(), 'max_tokens'));
        $this->assertSame(12288, data_get($requests[1]->data(), 'generationConfig.maxOutputTokens'));
        $this->assertSame(12288, data_get($requests[2]->data(), 'max_tokens'));
        $this->assertSame(12288, data_get($requests[3]->data(), 'max_tokens'));
    }

    #[DataProvider('uncleanOpenAiCompletionProvider')]
    public function test_openai_unclean_or_unconfirmed_completion_fails_over_to_strict_gemini_feedback(string $failureType): void
    {
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
            'GEMINI_MODEL' => 'gemini-test',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'openai,gemini',
            'AI_FEEDBACK_MAX_PROVIDERS' => '2',
            'AI_PROVIDER_RETRY_DELAY_MS' => '0',
            'AI_LIVE_TESTS' => 'false',
        ] as $key => $value) {
            $this->setEnvValue($key, $value);
        }

        $answer = [
            'id' => 71,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
        ];
        $validPayload = $this->validFeedbackPayload($answer);

        Http::fake(function ($request) use ($failureType, $validPayload) {
            if (str_contains($request->url(), 'api.openai.com')) {
                $choice = [
                    'message' => [
                        'refusal' => $failureType === 'refusal' ? 'I cannot provide this response.' : '',
                        'content' => json_encode($validPayload),
                    ],
                ];
                if ($failureType !== 'missing_finish_reason') {
                    $choice['finish_reason'] = $failureType === 'truncated' ? 'length' : 'stop';
                }

                return Http::response([
                    'choices' => [$choice],
                ], 200);
            }

            if (str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                return Http::response([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => ['parts' => [['text' => json_encode($validPayload)]]],
                    ]],
                ], 200);
            }

            return Http::response(['error' => 'Unexpected provider request.'], 500);
        });

        $feedback = AIService::generateFeedback(
            [
                'target_position' => 'Database Developer',
                'difficulty' => 'Medium',
                'interview_focus' => 'Technical diagnosis',
                'country' => 'General',
            ],
            [$answer],
            'openai',
            false,
            false,
            [
                'timeout_seconds' => 5,
                'deadline_seconds' => 30,
                'max_attempts' => 1,
                'http_attempts' => 1,
                'retry_delay_ms' => 0,
            ]
        );

        $this->assertSame('gemini', $feedback['_provider_key']);
        $this->assertSame(['openai', 'gemini'], $feedback['_providers_attempted']);
        $this->assertTrue($feedback['_strict_validation_passed']);
        $this->assertFalse($feedback['_repair_applied']);
        $this->assertCount(2, Http::recorded());
    }

    public static function uncleanOpenAiCompletionProvider(): array
    {
        return [
            'refusal' => ['refusal'],
            'truncated response' => ['truncated'],
            'missing finish reason' => ['missing_finish_reason'],
        ];
    }

    public function test_per_answer_recovery_uses_the_malformed_provider_with_the_largest_valid_subset(): void
    {
        foreach ([
            'OPENAI_API_KEY' => 'openai_test_token',
            'OPENAI_API_URL' => 'https://api.openai.com/v1',
            'OPENAI_FEEDBACK_MODEL' => 'gpt-4o-mini',
            'GEMINI_API_KEY' => 'gemini_test_token',
            'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
            'GEMINI_MODEL' => 'gemini-test',
            'AI_FEEDBACK_PROVIDER_PRIORITY' => 'openai,gemini',
            'AI_FEEDBACK_MAX_PROVIDERS' => '2',
            'AI_PROVIDER_RETRY_DELAY_MS' => '0',
            'AI_LIVE_TESTS' => 'false',
        ] as $key => $value) {
            $this->setEnvValue($key, $value);
        }

        $answers = [
            [
                'id' => 71,
                'question_type' => 'Technical',
                'question' => 'How would you diagnose a slow database query?',
                'answer' => 'I inspected the query plan, compared row estimates, and verified index usage before changing the query.',
                'feedback_focus' => 'database query',
            ],
            [
                'id' => 72,
                'question_type' => 'Technical',
                'question' => 'How would you investigate an API timeout?',
                'answer' => 'I checked the request logs, isolated the slow dependency, and added a bounded retry after confirming the failure mode.',
                'feedback_focus' => 'API dependency',
            ],
            [
                'id' => 73,
                'question_type' => 'Technical',
                'question' => 'How would you debug a narrow-screen layout problem?',
                'answer' => 'I reproduced the layout at narrow widths, inspected the flex container, and adjusted the wrapping rule after verifying the breakpoint.',
                'feedback_focus' => 'layout wrapping',
            ],
        ];
        $payloads = array_map(fn (array $answer): array => $this->validFeedbackPayload($answer), $answers);

        $openAiCandidate = $payloads[0];
        $geminiCandidate = $payloads[0];
        $geminiCandidate['per_question_feedback'][] = $payloads[1]['per_question_feedback'][0];
        $openAiCalls = 0;
        $geminiCalls = 0;

        Http::fake(function ($request) use (
            &$openAiCalls,
            &$geminiCalls,
            $openAiCandidate,
            $geminiCandidate,
            $payloads
        ) {
            if (str_contains($request->url(), 'api.openai.com')) {
                $payload = $openAiCalls++ === 0 ? $openAiCandidate : $payloads[min($openAiCalls - 1, 2)];

                return Http::response([
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['content' => json_encode($payload)],
                    ]],
                ], 200);
            }

            if (str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                $payload = $geminiCalls++ === 0 ? $geminiCandidate : $payloads[2];

                return Http::response([
                    'candidates' => [[
                        'finishReason' => 'STOP',
                        'content' => [
                            'parts' => [[
                                'text' => json_encode($payload),
                            ]],
                        ],
                    ]],
                ], 200);
            }

            return Http::response(['error' => 'Unexpected provider request.'], 500);
        });

        $feedback = AIService::generateFeedback(
            [
                'target_position' => 'Software Developer',
                'difficulty' => 'Medium',
                'interview_focus' => 'Technical diagnosis',
                'country' => 'General',
            ],
            $answers,
            'openai',
            false,
            true,
            [
                'timeout_seconds' => 5,
                'deadline_seconds' => 30,
                'max_attempts' => 1,
                'http_attempts' => 1,
                'retry_delay_ms' => 0,
            ]
        );

        $this->assertSame('gemini', $feedback['_provider_key']);
        $this->assertSame(['openai', 'gemini'], $feedback['_providers_attempted']);
        $this->assertTrue($feedback['_strict_validation_passed']);
        $this->assertTrue($feedback['_repair_applied']);
        $this->assertSame('provider_per_answer_completion', $feedback['_repair_strategy']);
        $this->assertTrue($feedback['_per_answer_provider_retry']);
        $this->assertSame([71, 72, 73], array_column($feedback['per_question_feedback'], 'id'));
        $this->assertSame(1, $openAiCalls);
        $this->assertSame(2, $geminiCalls);
        $this->assertCount(3, Http::recorded());
    }

    private function validFeedbackPayload(array $answer): array
    {
        $question = $answer['question'];
        $answerText = rtrim($answer['answer'], '.');
        $evidence = preg_split('/\s+(?:before|after)\s+/i', $answerText, 2)[0] ?? $answerText;
        $focus = $answer['feedback_focus'] ?? 'database query';

        return [
            'per_question_feedback' => [[
                'id' => $answer['id'],
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
                'ai_feedback' => 'For "'.$question.'", you stated "'.$evidence.'", which directly supports a relevant '.$focus.' approach.',
                'better_sample_answer' => $evidence.'. I would keep those same diagnostic steps and connect them to the role requirement. I would add the true final result after confirming it.',
                'follow_up_question' => 'What final result or detail from this answer would make it stronger?',
                'coaching' => [
                    'keep' => 'Keep the detail "'.$evidence.'" for "'.$question.'".',
                    'improve' => 'Add the final '.$focus.' result or check for "'.$question.'".',
                    'impact' => 'The missing '.$focus.' result limits how well the interviewer can judge whether the approach worked.',
                    'next_try' => 'Answer "'.$question.'" by linking the '.$focus.' steps to the verified result.',
                    'next_attempt_steps' => [
                        'Start with the '.$focus.' symptom you would check first.',
                        'Use "'.$evidence.'" as support.',
                    ],
                    'success_check' => 'The retry links the '.$focus.' diagnosis to a verified result.',
                ],
            ]],
            'session_feedback' => [
                'overall_summary' => 'Across the '.$focus.' answer, the response used specific '.$focus.' details. The next focus is to add the final result.',
                'strengths' => 'The AI review used saved answer details such as "'.$evidence.'" to identify what worked.',
                'weaknesses' => 'The '.$focus.' answer could add the final result from the same work.',
                'improvement_suggestions' => 'Keep the '.$focus.' steps and add the outcome only if it is true.',
            ],
            'review_context' => [
                'rubric_name' => 'SpeakReady detailed interview feedback rubric',
                'rubric_version' => 'v9',
                'evidence_policy' => 'Scores and comments use only exact candidate-answer evidence and explicit missing question requirements.',
                'confidence_policy' => 'Confidence is based on answer detail, evidence quotes, missing data, and score support.',
                'provider_confidence' => 'high',
                'insufficient_data' => false,
                'risk_flags' => ['missing_results'],
            ],
        ];
    }

    private function setEnvValue(string $key, string $value): void
    {
        if (! array_key_exists($key, $this->originalEnvValues)) {
            $this->originalEnvValues[$key] = getenv($key);
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
