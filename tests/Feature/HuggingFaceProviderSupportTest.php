<?php

namespace Tests\Feature;

use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HuggingFaceProviderSupportTest extends TestCase
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

    public function test_hugging_face_structured_provider_uses_router_token_and_model(): void
    {
        $this->setEnvValue('HUGGINGFACE_API_KEY', 'hf_test_token');
        $this->setEnvValue('HUGGINGFACE_API_URL', 'https://router.huggingface.co/v1');
        $this->setEnvValue('HUGGINGFACE_MODEL', 'openai/gpt-oss-120b:fireworks-ai');

        Http::fake([
            'router.huggingface.co/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => '{"questions":["Tell me about your interview preparation."]}',
                    ],
                ]],
            ], 200),
        ]);

        $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
        $providerMethod->setAccessible(true);

        $response = $providerMethod->invoke(null, 'hf', 'Return JSON.');

        $this->assertSame(['Tell me about your interview preparation.'], $response['questions']);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://router.huggingface.co/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer hf_test_token')
                && data_get($request->data(), 'model') === 'openai/gpt-oss-120b:fireworks-ai'
                && data_get($request->data(), 'response_format.type') === 'json_object'
                && data_get($request->data(), 'messages.1.content') === 'Return JSON.';
        });
    }

    public function test_all_structured_providers_use_supported_api_shapes(): void
    {
        $cases = [
            'openai' => [
                'env' => [
                    'OPENAI_API_KEY' => 'openai_test_token',
                    'OPENAI_API_URL' => 'https://api.openai.com/v1',
                    'OPENAI_MODEL' => 'gpt-test',
                ],
                'fake' => 'api.openai.com/*',
                'url' => 'https://api.openai.com/v1/chat/completions',
                'model' => 'gpt-test',
                'token' => 'openai_test_token',
                'response' => $this->openAiCompatibleResponse('openai'),
            ],
            'gemini' => [
                'env' => [
                    'GEMINI_API_KEY' => 'gemini_test_token',
                    'GEMINI_API_URL' => 'https://generativelanguage.googleapis.com/v1beta',
                    'GEMINI_MODEL' => 'gemini-test',
                ],
                'fake' => 'generativelanguage.googleapis.com/*',
                'url' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-test:generateContent?key=gemini_test_token',
                'model' => 'gemini-test',
                'token' => 'gemini_test_token',
                'response' => [
                    'candidates' => [[
                        'content' => [
                            'parts' => [[
                                'text' => '{"provider":"gemini"}',
                            ]],
                        ],
                    ]],
                ],
            ],
            'cohere' => [
                'env' => [
                    'COHERE_API_KEY' => 'cohere_test_token',
                    'COHERE_API_URL' => 'https://api.cohere.com/v2/chat',
                    'COHERE_MODEL' => 'command-r7b-12-2024',
                ],
                'fake' => 'api.cohere.com/*',
                'url' => 'https://api.cohere.com/v2/chat',
                'model' => 'command-r7b-12-2024',
                'token' => 'cohere_test_token',
                'response' => [
                    'message' => [
                        'content' => [[
                            'type' => 'text',
                            'text' => '{"provider":"cohere"}',
                        ]],
                    ],
                ],
            ],
            'groq' => [
                'env' => [
                    'GROQ_API_KEY' => 'groq_test_token',
                    'GROQ_API_URL' => 'https://api.groq.com/openai/v1',
                    'GROQ_MODEL' => 'llama-3.1-8b-instant',
                ],
                'fake' => 'api.groq.com/*',
                'url' => 'https://api.groq.com/openai/v1/chat/completions',
                'model' => 'llama-3.1-8b-instant',
                'token' => 'groq_test_token',
                'response' => $this->openAiCompatibleResponse('groq'),
            ],
            'openrouter' => [
                'env' => [
                    'OPENROUTER_API_KEY' => 'openrouter_test_token',
                    'OPENROUTER_API_URL' => 'https://openrouter.ai/api/v1',
                    'OPENROUTER_MODEL' => 'openrouter/free',
                ],
                'fake' => 'openrouter.ai/*',
                'url' => 'https://openrouter.ai/api/v1/chat/completions',
                'model' => 'openrouter/free',
                'token' => 'openrouter_test_token',
                'response' => $this->openAiCompatibleResponse('openrouter'),
            ],
            'huggingface' => [
                'env' => [
                    'HUGGINGFACE_API_KEY' => 'hf_test_token',
                    'HUGGINGFACE_API_URL' => 'https://router.huggingface.co/v1',
                    'HUGGINGFACE_MODEL' => 'openai/gpt-oss-120b:cerebras',
                ],
                'fake' => 'router.huggingface.co/*',
                'url' => 'https://router.huggingface.co/v1/chat/completions',
                'model' => 'openai/gpt-oss-120b:cerebras',
                'token' => 'hf_test_token',
                'response' => $this->openAiCompatibleResponse('huggingface'),
            ],
            'claude' => [
                'env' => [
                    'ANTHROPIC_API_KEY' => 'claude_test_token',
                    'ANTHROPIC_API_URL' => 'https://api.anthropic.com/v1/messages',
                    'ANTHROPIC_MODEL' => 'claude-haiku-4-5-20251001',
                    'ANTHROPIC_VERSION' => '2023-06-01',
                ],
                'fake' => 'api.anthropic.com/*',
                'url' => 'https://api.anthropic.com/v1/messages',
                'model' => 'claude-haiku-4-5-20251001',
                'token' => 'claude_test_token',
                'response' => [
                    'content' => [[
                        'type' => 'text',
                        'text' => '{"provider":"claude"}',
                    ]],
                ],
            ],
            'wisdomgate' => [
                'env' => [
                    'WISDOMGATE_API_KEY' => 'wisdomgate_test_token',
                    'WISDOMGATE_API_URL' => 'https://wisgate.ai/v1',
                    'WISDOMGATE_MODEL' => 'gpt-5-nano',
                ],
                'fake' => 'wisgate.ai/*',
                'url' => 'https://wisgate.ai/v1/chat/completions',
                'model' => 'gpt-5-nano',
                'token' => 'wisdomgate_test_token',
                'response' => $this->openAiCompatibleResponse('wisdomgate'),
            ],
        ];

        $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
        $providerMethod->setAccessible(true);

        foreach ($cases as $provider => $case) {
            foreach ($case['env'] as $key => $value) {
                $this->setEnvValue($key, $value);
            }

            Http::fake([
                $case['fake'] => Http::response($case['response'], 200),
            ]);

            $response = $providerMethod->invoke(null, $provider, 'Return JSON.');

            $this->assertSame($provider, $response['provider']);
            $this->assertStructuredRequestWasSent($provider, $case['url'], $case['model'], $case['token']);
        }
    }

    public function test_database_configured_non_openai_provider_is_used(): void
    {
        $this->setEnvValue('WISDOMGATE_API_KEY', '');
        $this->setEnvValue('WISDOMGATE_API_URL', 'https://unused.example/v1');
        $this->setEnvValue('WISDOMGATE_MODEL', 'gpt-5-nano');

        \App\Models\AiProvider::create([
            'name' => 'WisGate',
            'api_endpoint' => 'https://custom.wisgate.test/v1',
            'api_key' => Crypt::encryptString('db_wisgate_token'),
            'status' => 'active',
        ]);

        Http::fake([
            'custom.wisgate.test/*' => Http::response($this->openAiCompatibleResponse('wisdomgate'), 200),
        ]);

        $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
        $providerMethod->setAccessible(true);

        $response = $providerMethod->invoke(null, 'wisdomgate', 'Return JSON.');

        $this->assertSame('wisdomgate', $response['provider']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://custom.wisgate.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer db_wisgate_token'));
    }

    private function openAiCompatibleResponse(string $provider): array
    {
        return [
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => [
                    'content' => json_encode(['provider' => $provider]),
                ],
            ]],
        ];
    }

    private function assertStructuredRequestWasSent(string $provider, string $url, string $model, string $token): void
    {
        Http::assertSent(function ($request) use ($provider, $url, $model, $token): bool {
            if ($request->url() !== $url) {
                return false;
            }

            if ($provider === 'gemini') {
                return data_get($request->data(), 'generationConfig.responseMimeType') === 'application/json'
                    && data_get($request->data(), 'contents.0.parts.0.text') === 'Return JSON.';
            }

            if ($provider === 'claude') {
                return $request->hasHeader('x-api-key', $token)
                    && $request->hasHeader('anthropic-version', '2023-06-01')
                    && data_get($request->data(), 'model') === $model
                    && data_get($request->data(), 'messages.0.content') === 'Return JSON.';
            }

            return $request->hasHeader('Authorization', "Bearer {$token}")
                && data_get($request->data(), 'model') === $model
                && data_get($request->data(), 'response_format.type') === 'json_object'
                && data_get($request->data(), 'messages.'.($provider === 'cohere' ? '0' : '1').'.content') === 'Return JSON.';
        });
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
