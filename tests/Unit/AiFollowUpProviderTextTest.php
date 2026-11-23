<?php

namespace Tests\Unit;

use App\Models\AiProvider;
use App\Models\InterviewSession;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiFollowUpProviderTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_accepts_complete_text_split_across_provider_blocks(): void
    {
        foreach ([
            ['OpenAI', 'https://api.openai.com/v1/chat/completions/'],
            ['Gemini', 'https://generativelanguage.googleapis.com/v1beta'],
            ['Groq', 'https://api.groq.com/openai/v1'],
        ] as [$name, $endpoint]) {
            AiProvider::create([
                'name' => $name,
                'api_endpoint' => $endpoint,
                'api_key' => Crypt::encryptString('test-'.$name.'-key'),
                'status' => 'active',
            ]);
        }

        $question = 'How did your deployment checks improve reliability for the Developer role?';
        $first = 'How did your deployment checks ';
        $second = 'improve reliability for the Developer role?';

        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => [
                    ['type' => 'text', 'text' => $first],
                    ['type' => 'text', 'text' => $second],
                ]],
            ]]]),
            'generativelanguage.googleapis.com/*' => Http::response(['candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['parts' => [
                    ['thought' => true, 'text' => 'Internal reasoning must not appear in the interview question.'],
                    ['text' => $first],
                    ['text' => $second],
                ]],
            ]]]),
            'api.groq.com/*' => Http::response(['choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => [
                    ['type' => 'text', 'text' => $first],
                    ['type' => 'text', 'text' => $second],
                ]],
            ]]]),
        ]);

        foreach (['openai', 'gemini', 'groq'] as $provider) {
            $this->assertSame($question, AIService::chatMessage(
                'Ask one interview question.', [], $provider, null,
                ['max_providers' => 1, 'timeout_seconds' => 2, 'attempts' => 1]
            ));
        }

        Http::assertSentCount(3);
    }

    public function test_gemini_quota_retry_info_cools_down_chat_and_fails_over(): void
    {
        config(['services.ai_providers.rate_limit_cooldown_in_tests' => true]);
        Cache::forget('ai:chat:rate-limit:gemini');

        foreach ([
            ['Gemini', 'https://generativelanguage.googleapis.com/v1beta'],
            ['OpenAI', 'https://api.openai.com/v1/chat/completions/'],
        ] as [$name, $endpoint]) {
            AiProvider::create([
                'name' => $name,
                'api_endpoint' => $endpoint,
                'api_key' => Crypt::encryptString('test-'.$name.'-key'),
                'status' => 'active',
            ]);
        }

        $question = 'How did your deployment checks improve reliability for the Developer role?';
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['details' => [[
                    '@type' => 'type.googleapis.com/google.rpc.RetryInfo',
                    'retryDelay' => '3897s',
                ]]],
            ], 429),
            'api.openai.com/*' => Http::response(['choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => $question],
            ]]]),
        ]);

        $options = ['max_providers' => 2, 'timeout_seconds' => 2, 'attempts' => 1];
        $this->assertSame($question, AIService::chatMessage('Ask one interview question.', [], 'gemini', null, $options));
        $this->assertGreaterThanOrEqual(time() + 3890, Cache::get('ai:chat:rate-limit:gemini'));
        $this->assertSame($question, AIService::chatMessage('Ask another question.', [], 'gemini', null, $options));
        Http::assertSentCount(3);

        Cache::forget('ai:chat:rate-limit:gemini');
    }

    public function test_live_follow_up_skips_cooling_provider_before_provider_limit(): void
    {
        config([
            'services.ai_providers.rate_limit_cooldown_in_tests' => true,
            'services.interview_follow_up.max_providers' => 1,
            'services.ai_providers.interview_provider_priority' => 'openai,groq',
        ]);
        foreach ([
            ['OpenAI', 'https://api.openai.com/v1/chat/completions/'],
            ['Groq', 'https://api.groq.com/openai/v1'],
        ] as [$name, $endpoint]) {
            AiProvider::create([
                'name' => $name,
                'api_endpoint' => $endpoint,
                'api_key' => Crypt::encryptString('test-'.$name.'-key'),
                'status' => 'active',
            ]);
        }

        $question = 'How did your deployment checks improve reliability for the Developer role?';
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'should be skipped'], 503),
            'api.groq.com/*' => Http::response(['choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => $question],
            ]]]),
        ]);
        Cache::put('ai:chat:rate-limit:openai', time() + 120, 120);
        Cache::put('ai:chat:rate-limit:gemini', time() + 120, 120);

        try {
            $answer = 'I improved deployment checks so our software releases became more reliable.';
            $usedProvider = null;
            $this->assertSame($question, AIService::generateChatReply(
                new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']),
                [['question' => 'Tell me about your work.', 'answer' => $answer]],
                $answer, 'openai', false, null, [], null, null,
                fn (string $candidate): bool => str_contains($candidate, 'deployment checks'),
                $usedProvider
            ));
            $this->assertSame('groq', $usedProvider);
            Http::assertSentCount(1);
            Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'api.groq.com'));
        } finally {
            Cache::forget('ai:chat:rate-limit:openai');
            Cache::forget('ai:chat:rate-limit:gemini');
        }
    }

    public function test_live_follow_up_repairs_answer_mismatch_before_trying_another_provider(): void
    {
        config([
            'services.interview_follow_up.max_retries' => 1,
            'services.interview_follow_up.max_providers' => 2,
            'services.ai_providers.interview_provider_priority' => 'openai,groq',
        ]);
        foreach ([
            ['OpenAI', 'https://api.openai.com/v1/chat/completions/'],
            ['Groq', 'https://api.groq.com/openai/v1'],
        ] as [$name, $endpoint]) {
            AiProvider::create([
                'name' => $name,
                'api_endpoint' => $endpoint,
                'api_key' => Crypt::encryptString('test-'.$name.'-key'),
                'status' => 'active',
            ]);
        }

        $question = 'How did your deployment checks improve reliability for the Developer role?';
        $replies = ['What is your favorite color?', $question];
        $prompts = [];
        Http::fake([
            'api.openai.com/*' => function ($request) use (&$replies, &$prompts) {
                $prompts[] = (string) data_get($request->data(), 'messages.1.content', '');

                return Http::response(['choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['content' => array_shift($replies)],
                ]]]);
            },
            'api.groq.com/*' => Http::response(['error' => 'should not be called'], 503),
        ]);

        $answer = 'I improved deployment checks so our software releases became more reliable.';
        $usedProvider = null;
        $this->assertSame($question, AIService::generateChatReply(
            new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']),
            [['question' => 'Tell me about your work.', 'answer' => $answer]],
            $answer, 'openai', false, null, [], null, null,
            fn (string $candidate): bool|string => str_contains($candidate, 'deployment checks') ? true : 'answer_anchor',
            $usedProvider
        ));
        $this->assertSame('openai', $usedProvider);
        $this->assertCount(2, $prompts);
        $this->assertStringContainsString('previous response could not be used', $prompts[1]);
        $this->assertStringContainsString('SPECIFIC REPAIR: Put one concrete word or action from the latest answer in the QUESTION itself.', $prompts[1]);
        Http::assertSentCount(2);
    }

    public function test_follow_up_sweeps_every_configured_provider_before_failing(): void
    {
        config([
            'services.interview_follow_up.max_retries' => 1,
            'services.interview_follow_up.max_providers' => 4,
            'services.interview_follow_up.timeout' => 2,
            'services.ai_providers.interview_provider_priority' => 'openai,gemini,groq,cohere',
        ]);
        foreach ([
            ['OpenAI', 'https://api.openai.com/v1/chat/completions/'],
            ['Gemini', 'https://generativelanguage.googleapis.com/v1beta'],
            ['Groq', 'https://api.groq.com/openai/v1'],
            ['Cohere', 'https://api.cohere.com/v2/chat'],
        ] as [$name, $endpoint]) {
            AiProvider::create([
                'name' => $name,
                'api_endpoint' => $endpoint,
                'api_key' => Crypt::encryptString('test-'.$name.'-key'),
                'status' => 'active',
            ]);
        }

        $question = 'How did your deployment checks improve reliability for the Developer role?';
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'unavailable'], 503),
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'unavailable'], 503),
            'api.groq.com/*' => Http::response(['error' => 'unavailable'], 503),
            'api.cohere.com/*' => Http::response([
                'finish_reason' => 'COMPLETE',
                'message' => ['content' => [['type' => 'text', 'text' => $question]]],
            ]),
        ]);

        $session = new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']);
        $answer = 'I improved deployment checks so our software releases became more reliable.';
        $usedProvider = null;
        $this->assertSame($question, AIService::generateChatReply(
            $session, [['question' => 'Tell me about your work.', 'answer' => $answer]],
            $answer, 'openai', false, null, [], null, null,
            fn (string $candidate): bool => str_contains($candidate, 'deployment checks'),
            $usedProvider
        ));
        $this->assertSame('cohere', $usedProvider);
        Http::assertSentCount(4);
    }

    public function test_live_follow_up_caps_output_and_reserves_time_for_each_provider(): void
    {
        config([
            'services.ai_chat.max_tokens' => 1000,
            'services.openai.model' => 'gpt-4o-mini',
            'services.gemini.model' => 'gemini-2.0-flash',
            'services.interview_follow_up.max_retries' => 1,
            'services.interview_follow_up.max_providers' => 4,
            'services.interview_follow_up.timeout' => 20,
            'services.interview_follow_up.deadline_seconds' => 60,
            'services.ai_providers.interview_provider_priority' => 'openai,gemini,groq,cohere',
        ]);

        foreach ([
            ['OpenAI', 'https://api.openai.com/v1/chat/completions/'],
            ['Gemini', 'https://generativelanguage.googleapis.com/v1beta'],
            ['Groq', 'https://api.groq.com/openai/v1'],
            ['Cohere', 'https://api.cohere.com/v2/chat'],
        ] as [$name, $endpoint]) {
            AiProvider::create([
                'name' => $name,
                'api_endpoint' => $endpoint,
                'api_key' => Crypt::encryptString('test-'.$name.'-key'),
                'status' => 'active',
            ]);
        }

        $requests = [];
        $recordFailure = function (string $provider) use (&$requests) {
            return function ($request, array $options) use (&$requests, $provider) {
                $requests[$provider] = ['body' => $request->data(), 'timeout' => $options['timeout'] ?? null];

                return Http::response(['error' => 'temporarily unavailable'], 503);
            };
        };
        $question = 'How did your deployment checks improve reliability for the Developer role?';
        Http::fake([
            'api.openai.com/*' => $recordFailure('openai'),
            'generativelanguage.googleapis.com/*' => $recordFailure('gemini'),
            'api.groq.com/*' => $recordFailure('groq'),
            'api.cohere.com/*' => function ($request, array $options) use (&$requests, $question) {
                $requests['cohere'] = ['body' => $request->data(), 'timeout' => $options['timeout'] ?? null];

                return Http::response([
                    'finish_reason' => 'COMPLETE',
                    'message' => ['content' => [['type' => 'text', 'text' => $question]]],
                ]);
            },
        ]);

        $answer = 'I improved deployment checks so our software releases became more reliable.';
        $usedProvider = null;
        $previousOpenAiModel = getenv('OPENAI_MODEL');
        $previousGeminiModel = getenv('GEMINI_MODEL');
        putenv('OPENAI_MODEL');
        putenv('GEMINI_MODEL');
        try {
            $reply = AIService::generateChatReply(
                new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']),
                [['question' => 'Tell me about your work.', 'answer' => $answer]],
                $answer, 'openai', false, null, [], null, null,
                fn (string $candidate): bool => str_contains($candidate, 'deployment checks'),
                $usedProvider
            );
        } finally {
            putenv($previousOpenAiModel === false ? 'OPENAI_MODEL' : 'OPENAI_MODEL='.$previousOpenAiModel);
            putenv($previousGeminiModel === false ? 'GEMINI_MODEL' : 'GEMINI_MODEL='.$previousGeminiModel);
        }
        $this->assertSame($question, $reply);
        $this->assertSame('cohere', $usedProvider);
        $this->assertSame(['openai', 'gemini', 'groq', 'cohere'], array_keys($requests));
        $this->assertSame(20, $requests['openai']['timeout']);
        foreach ($requests as $provider => $sent) {
            $this->assertGreaterThanOrEqual(2, $sent['timeout'], $provider);
            $this->assertLessThanOrEqual(20, $sent['timeout'], $provider);
            $this->assertSame(384, match ($provider) {
                'gemini' => data_get($sent['body'], 'generationConfig.maxOutputTokens'),
                'openai' => $sent['body']['max_tokens'] ?? $sent['body']['max_completion_tokens'] ?? null,
                default => $sent['body']['max_tokens'] ?? null,
            }, $provider);
        }
        Http::assertSentCount(4);
    }

    public function test_later_provider_keeps_a_slot_after_first_provider_uses_its_full_budget(): void
    {
        config([
            'services.interview_follow_up.max_retries' => 1,
            'services.interview_follow_up.max_providers' => 4,
            'services.interview_follow_up.timeout' => 20,
            'services.interview_follow_up.deadline_seconds' => 20,
            'services.ai_providers.interview_provider_priority' => 'openai,gemini,groq,cohere',
        ]);
        foreach ([
            ['OpenAI', 'https://api.openai.com/v1/chat/completions/'],
            ['Gemini', 'https://generativelanguage.googleapis.com/v1beta'],
            ['Groq', 'https://api.groq.com/openai/v1'],
            ['Cohere', 'https://api.cohere.com/v2/chat'],
        ] as [$name, $endpoint]) {
            AiProvider::create([
                'name' => $name,
                'api_endpoint' => $endpoint,
                'api_key' => Crypt::encryptString('test-'.$name.'-key'),
                'status' => 'active',
            ]);
        }

        $requestTimeouts = [];
        $question = 'How did your deployment checks improve reliability for the Developer role?';
        Http::fake([
            'api.openai.com/*' => function ($request, array $options) use (&$requestTimeouts) {
                $requestTimeouts['openai'] = (int) ($options['timeout'] ?? 0);
                usleep($requestTimeouts['openai'] * 1000000);

                return Http::response(['error' => 'slow temporary failure'], 503);
            },
            'generativelanguage.googleapis.com/*' => function ($request, array $options) use (&$requestTimeouts, $question) {
                $requestTimeouts['gemini'] = (int) ($options['timeout'] ?? 0);

                return Http::response(['candidates' => [[
                    'finishReason' => 'STOP',
                    'content' => ['parts' => [['text' => $question]]],
                ]]]);
            },
            'api.groq.com/*' => Http::response(['error' => 'should not be called'], 503),
            'api.cohere.com/*' => Http::response(['error' => 'should not be called'], 503),
        ]);

        $answer = 'I improved deployment checks so our software releases became more reliable.';
        $usedProvider = null;
        $this->assertSame($question, AIService::generateChatReply(
            new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']),
            [['question' => 'Tell me about your work.', 'answer' => $answer]],
            $answer, 'openai', false, null, [], null, null,
            fn (string $candidate): bool => str_contains($candidate, 'deployment checks'),
            $usedProvider
        ));
        $this->assertSame('gemini', $usedProvider);
        $this->assertGreaterThanOrEqual(2, $requestTimeouts['openai']);
        $this->assertLessThanOrEqual(6, $requestTimeouts['openai']);
        $this->assertGreaterThanOrEqual(2, $requestTimeouts['gemini']);
        Http::assertSentCount(2);
    }

    public function test_follow_up_repairs_an_invalid_question_after_provider_sweep(): void
    {
        config([
            'services.interview_follow_up.max_retries' => 2,
            'services.interview_follow_up.max_providers' => 1,
            'services.interview_follow_up.timeout' => 2,
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $question = 'How did your deployment checks improve reliability for the Developer role?';
        $replies = ['What is your favorite color?', $question];
        $prompts = [];
        Http::fake(['api.openai.com/*' => function ($request) use (&$replies, &$prompts) {
            $prompts[] = (string) data_get($request->data(), 'messages.1.content', '');

            return Http::response(['choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => array_shift($replies)],
            ]]]);
        }]);

        $session = new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']);
        $answer = 'I improved deployment checks so our software releases became more reliable.';
        $usedProvider = null;
        $this->assertSame($question, AIService::generateChatReply(
            $session, [['question' => 'Tell me about your work.', 'answer' => $answer]],
            $answer, 'openai', false, null, [], null, 'Technical',
            fn (string $candidate): bool => str_contains($candidate, 'deployment checks'),
            $usedProvider
        ));
        $this->assertSame('openai', $usedProvider);
        $this->assertCount(2, $prompts);
        $this->assertStringContainsString('previous response could not be used', $prompts[1]);
        $this->assertStringContainsString('concrete method, tool, diagnosis, tradeoff, or verification step', $prompts[1]);
        Http::assertSentCount(2);
    }

    public function test_single_online_provider_keeps_configured_semantic_retries(): void
    {
        config([
            'services.interview_follow_up.max_retries' => 4,
            'services.interview_follow_up.max_providers' => 1,
            'services.interview_follow_up.timeout' => 2,
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $question = 'How did your deployment checks improve reliability for the Developer role?';
        $replies = [
            'What is your favorite color?',
            'What is your favorite color?',
            'What is your favorite color?',
            $question,
        ];
        Http::fake(['api.openai.com/*' => function () use (&$replies) {
            return Http::response(['choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => array_shift($replies)],
            ]]]);
        }]);

        $session = new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']);
        $answer = 'I improved deployment checks so our software releases became more reliable.';
        $usedProvider = null;
        $this->assertSame($question, AIService::generateChatReply(
            $session, [['question' => 'Tell me about your work.', 'answer' => $answer]],
            $answer, 'openai', false, null, [], null, null,
            fn (string $candidate): bool => str_contains($candidate, 'deployment checks'),
            $usedProvider
        ));
        $this->assertSame('openai', $usedProvider);
        Http::assertSentCount(4);
    }

    public function test_single_provider_recovers_from_a_transient_http_failure(): void
    {
        config([
            'services.interview_follow_up.max_retries' => 4,
            'services.interview_follow_up.max_providers' => 1,
            'services.interview_follow_up.timeout' => 2,
            'services.interview_follow_up.http_attempts' => 1,
        ]);
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1/chat/completions/',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
        ]);

        $question = 'How did your deployment checks improve reliability for the Developer role?';
        $calls = 0;
        Http::fake(['api.openai.com/*' => function () use (&$calls, $question) {
            $calls++;
            return $calls < 3
                ? Http::response(['error' => 'temporary failure'], 503)
                : Http::response(['choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['content' => $question],
                ]]]);
        }]);

        $session = new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']);
        $answer = 'I improved deployment checks so our software releases became more reliable.';
        $usedProvider = null;
        $this->assertSame($question, AIService::generateChatReply(
            $session, [['question' => 'Tell me about your work.', 'answer' => $answer]],
            $answer, 'openai', false, null, [], null, null,
            fn (string $candidate): bool => str_contains($candidate, 'deployment checks'),
            $usedProvider
        ));
        $this->assertSame('openai', $usedProvider);
        Http::assertSentCount(3);
    }

    public function test_follow_up_repairs_rejected_questions_across_multiple_providers(): void
    {
        config([
            'services.interview_follow_up.max_retries' => 2,
            'services.interview_follow_up.max_providers' => 2,
            'services.interview_follow_up.timeout' => 2,
            'services.ai_providers.interview_provider_priority' => 'groq,openai',
        ]);
        foreach ([
            ['OpenAI', 'https://api.openai.com/v1/chat/completions/'],
            ['Groq', 'https://api.groq.com/openai/v1'],
        ] as [$name, $endpoint]) {
            AiProvider::create([
                'name' => $name,
                'api_endpoint' => $endpoint,
                'api_key' => Crypt::encryptString('test-'.$name.'-key'),
                'status' => 'active',
            ]);
        }

        $invalid = 'What is your favorite color?';
        $valid = 'How did your deployment checks improve reliability for the Developer role?';
        $counts = ['openai' => 0, 'groq' => 0];
        $prompts = ['openai' => [], 'groq' => []];
        Http::fake([
            'api.openai.com/*' => function ($request) use (&$counts, &$prompts, $invalid, $valid) {
                $counts['openai']++;
                $prompts['openai'][] = (string) data_get($request->data(), 'messages.1.content', '');

                return Http::response(['choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['content' => $counts['openai'] === 1 ? $invalid : $valid],
                ]]]);
            },
            'api.groq.com/*' => function ($request) use (&$counts, &$prompts, $invalid) {
                $counts['groq']++;
                $prompts['groq'][] = (string) data_get($request->data(), 'messages.1.content', '');

                return Http::response(['choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['content' => $invalid],
                ]]]);
            },
        ]);

        $session = new InterviewSession(['target_position' => 'Developer', 'difficulty' => 'medium']);
        $answer = 'I improved deployment checks so our software releases became more reliable.';
        $usedProvider = null;
        $this->assertSame($valid, AIService::generateChatReply(
            $session, [['question' => 'Tell me about your work.', 'answer' => $answer]],
            $answer, 'groq', false, null, [], null, 'Technical',
            fn (string $candidate): bool => str_contains($candidate, 'deployment checks'),
            $usedProvider
        ));
        $this->assertSame('openai', $usedProvider);
        $this->assertSame(['openai' => 2, 'groq' => 2], $counts);
        $this->assertStringContainsString('UNUSABLE PREVIOUS OUTPUT', $prompts['openai'][1]);
        $this->assertStringContainsString('UNUSABLE PREVIOUS OUTPUT', $prompts['groq'][1]);
        Http::assertSentCount(4);
    }
}
