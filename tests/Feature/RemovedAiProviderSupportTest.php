<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RemovedAiProviderSupportTest extends TestCase
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

 public function test_removed_ai_provider_aliases_are_unsupported(): void
 {
 foreach ([
 'anthropic',
 'claude',
 'OpenRouter',
 'openrouter',
 'WisdomGate',
 'wisgate',
 'Hugging Face',
 'HuggingFace',
 'hf',
 ] as $provider) {
 $this->assertSame('', AIService::normalizeProviderKey($provider));
 $this->assertFalse(AIService::providerIsSupported($provider));
 $this->assertFalse(AIService::providerIsConfigured($provider));
 }
 }

 public function test_supported_provider_options_only_include_active_provider_pool(): void
 {
 AiProvider::create([
 'name' => 'WisGate',
 'api_endpoint' => 'https://custom.wisgate.test/v1',
 'api_key' => Crypt::encryptString('db_wisgate_token'),
 'status' => 'active',
 ]);

 $this->assertSame(
 ['openai', 'gemini', 'groq', 'cohere'],
 array_column(AIService::supportedProviderOptions(), 'key')
 );
 $this->assertFalse(AIService::providerIsConfigured('WisGate'));
 $this->assertNull(AiProvider::safeActiveProviderName());
 }

 public function test_removed_provider_names_are_ignored_in_priority_env_values(): void
 {
 foreach ([
 'OPENAI_API_KEY' => 'openai_test_token',
 'GEMINI_API_KEY' => 'gemini_test_token',
 'GROQ_API_KEY' => 'groq_test_token',
 'COHERE_API_KEY' => 'cohere_test_token',
 'INTERVIEW_CHATBOT_DEFAULT_PROVIDER' => 'huggingface',
 'INTERVIEW_CHATBOT_PROVIDER_PRIORITY' => 'huggingface,openrouter,gemini,wisdomgate,claude,groq,cohere',
 'AI_FEEDBACK_PROVIDER_PRIORITY' => 'huggingface,openrouter,gemini,wisdomgate,claude,groq,cohere',
 'AI_DEFAULT_PROVIDER_PRIORITY' => 'huggingface,openrouter,gemini,wisdomgate,claude,groq,cohere',
 'AI_FEEDBACK_MAX_PROVIDERS' => '8',
 ] as $key => $value) {
 $this->setEnvValue($key, $value);
 }

 $providerPriority = new \ReflectionMethod(AIService::class, 'providerPriorityList');
 $providerPriority->setAccessible(true);
 $feedbackPriority = new \ReflectionMethod(AIService::class, 'feedbackProviderPriority');
 $feedbackPriority->setAccessible(true);

 $expected = ['gemini', 'groq', 'cohere', 'openai'];

 $this->assertSame('gemini', AIService::defaultProviderKey());
 $this->assertSame($expected, $providerPriority->invoke(null, 'huggingface'));
 $this->assertSame($expected, $feedbackPriority->invoke(null, 'openrouter'));
 }

 public function test_removed_providers_cannot_call_structured_generation(): void
 {
 Http::fake();

 $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
 $providerMethod->setAccessible(true);

 foreach (['claude', 'openrouter', 'wisdomgate', 'huggingface'] as $provider) {
 try {
 $providerMethod->invoke(null, $provider, 'Return JSON.');
 $this->fail("Expected {$provider} to be rejected.");
 } catch (\RuntimeException $exception) {
 $this->assertSame('Unsupported AI provider requested.', $exception->getMessage());
 }
 }

 Http::assertNothingSent();
 }

 public function test_remaining_structured_providers_use_supported_api_shapes(): void
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

 public function test_all_structured_providers_receive_a_machine_enforced_schema(): void
 {
 $schema = [
 'type' => 'json_schema',
 'json_schema' => [
 'name' => 'provider_contract',
 'strict' => true,
 'schema' => [
 'type' => 'object',
 'additionalProperties' => false,
 'properties' => [
 'provider' => ['type' => 'string'],
 ],
 'required' => ['provider'],
 ],
 ],
 ];
 $cases = [
 'openai' => ['OPENAI_API_KEY', 'openai_test_token', 'api.openai.com/*', $this->openAiCompatibleResponse('openai')],
 'gemini' => ['GEMINI_API_KEY', 'gemini_test_token', 'generativelanguage.googleapis.com/*', [
 'candidates' => [[
 'finishReason' => 'STOP',
 'content' => ['parts' => [['text' => '{"provider":"gemini"}']]],
 ]],
 ]],
 'groq' => ['GROQ_API_KEY', 'groq_test_token', 'api.groq.com/*', $this->openAiCompatibleResponse('groq')],
 'cohere' => ['COHERE_API_KEY', 'cohere_test_token', 'api.cohere.com/*', [
 'finish_reason' => 'COMPLETE',
 'message' => ['content' => [['type' => 'text', 'text' => '{"provider":"cohere"}']]],
 ]],
 ];
 $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
 $providerMethod->setAccessible(true);

 foreach ($cases as $provider => [$envKey, $token, $fake, $response]) {
 $this->setEnvValue($envKey, $token);
 Http::fake([$fake => Http::response($response, 200)]);

 $result = $providerMethod->invoke(null, $provider, 'Return the provider contract.', [
 'response_format' => $schema,
 ]);

 $this->assertSame($provider, $result['provider']);
 Http::assertSent(function ($request) use ($provider): bool {
 if ($provider === 'gemini') {
 return data_get($request->data(), 'generationConfig.responseJsonSchema.properties.provider.type') === 'string'
 || data_get($request->data(), 'generationConfig.responseFormat.text.schema.properties.provider.type') === 'string';
 }

 if ($provider === 'cohere') {
 return data_get($request->data(), 'response_format.type') === 'json_object'
 && data_get($request->data(), 'response_format.schema.properties.provider.type') === 'string'
 && data_get($request->data(), 'safety_mode') === 'STRICT';
 }

 return data_get($request->data(), 'response_format.type') === 'json_schema'
 && data_get($request->data(), 'response_format.json_schema.strict') === true
 && data_get($request->data(), 'response_format.json_schema.schema.properties.provider.type') === 'string';
 });
 }
 }

 public function test_provider_does_not_retry_authentication_errors(): void
 {
 $this->setEnvValue('OPENAI_API_KEY', 'openai_test_token');
 $this->setEnvValue('AI_PROVIDER_RETRY_DELAY_MS', '0');
 $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
 $providerMethod->setAccessible(true);

 Http::fakeSequence()
 ->push(['error' => ['message' => 'invalid key']], 401)
 ->push($this->openAiCompatibleResponse('openai'), 200);

 $failed = false;
 try {
 $providerMethod->invoke(null, 'openai', 'Return JSON.', ['attempts' => 2]);
 } catch (\Throwable) {
 $failed = true;
 }

 $this->assertTrue($failed, 'Expected the authentication error to fail without retrying.');
 $this->assertCount(1, Http::recorded());
 }

 public function test_provider_retries_transient_errors(): void
 {
 $this->setEnvValue('OPENAI_API_KEY', 'openai_test_token');
 $this->setEnvValue('AI_PROVIDER_RETRY_DELAY_MS', '0');
 $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
 $providerMethod->setAccessible(true);

 Http::fakeSequence()
 ->push(['error' => ['message' => 'temporarily unavailable']], 503)
 ->push($this->openAiCompatibleResponse('openai'), 200);

 $result = $providerMethod->invoke(null, 'openai', 'Return JSON.', ['attempts' => 2]);

 $this->assertSame('openai', $result['provider']);
 $this->assertCount(2, Http::recorded());
 }

 public function test_openai_retries_with_json_object_when_a_recognised_schema_error_is_returned(): void
 {
 $this->setEnvValue('OPENAI_API_KEY', 'openai_test_token');
 $this->setEnvValue('OPENAI_JSON_MODE_FALLBACK', 'true');
 $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
 $providerMethod->setAccessible(true);
 $schema = $this->providerContractSchema();

 Http::fakeSequence()
 ->push(['error' => ['message' => 'response_format json_schema is unsupported']], 400)
 ->push($this->openAiCompatibleResponse('openai'), 200);

 $result = $providerMethod->invoke(null, 'openai', 'Return JSON.', [
 'attempts' => 1,
 'response_format' => $schema,
 ]);

 $this->assertSame('openai', $result['provider']);
 $requests = collect(Http::recorded())->map(fn (array $entry) => $entry[0])->values();
 $this->assertCount(2, $requests);
 $this->assertSame('json_schema', data_get($requests[0]->data(), 'response_format.type'));
 $this->assertSame('json_object', data_get($requests[1]->data(), 'response_format.type'));
 }

 public function test_openai_does_not_use_json_fallback_for_an_unrecognised_client_error(): void
 {
 $this->setEnvValue('OPENAI_API_KEY', 'openai_test_token');
 $this->setEnvValue('OPENAI_JSON_MODE_FALLBACK', 'true');
 $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
 $providerMethod->setAccessible(true);

 Http::fakeSequence()
 ->push(['error' => ['message' => 'invalid project configuration']], 400)
 ->push($this->openAiCompatibleResponse('openai'), 200);

 try {
 $providerMethod->invoke(null, 'openai', 'Return JSON.', [
 'attempts' => 1,
 'response_format' => $this->providerContractSchema(),
 ]);
 $this->fail('Expected an unrelated HTTP 400 response to fail without JSON-mode fallback.');
 } catch (\Throwable $exception) {
 $this->assertNotInstanceOf(\PHPUnit\Framework\AssertionFailedError::class, $exception);
 }

 $this->assertCount(1, Http::recorded());
 }

 public function test_openai_does_not_use_json_fallback_when_it_is_disabled(): void
 {
 $this->setEnvValue('OPENAI_API_KEY', 'openai_test_token');
 $this->setEnvValue('OPENAI_JSON_MODE_FALLBACK', 'false');
 $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
 $providerMethod->setAccessible(true);

 Http::fakeSequence()
 ->push(['error' => ['message' => 'response_format json_schema is unsupported']], 422)
 ->push($this->openAiCompatibleResponse('openai'), 200);

 $failed = false;
 try {
 $providerMethod->invoke(null, 'openai', 'Return JSON.', [
 'attempts' => 1,
 'response_format' => $this->providerContractSchema(),
 ]);
 } catch (\Throwable) {
 $failed = true;
 }

 $this->assertTrue($failed);
 $this->assertCount(1, Http::recorded());
 }

 public function test_json_fallback_does_not_start_when_provider_slice_has_less_than_one_second_left(): void
 {
 $this->setEnvValue('OPENAI_API_KEY', 'openai_test_token');
 $this->setEnvValue('OPENAI_JSON_MODE_FALLBACK', 'true');
 $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
 $providerMethod->setAccessible(true);

 Http::fakeSequence()
 ->push(['error' => ['message' => 'response_format json_schema is unsupported']], 400)
 ->push($this->openAiCompatibleResponse('openai'), 200);

 $failed = false;
 try {
 $providerMethod->invoke(null, 'openai', 'Return JSON.', [
 'attempts' => 1,
 'timeout_seconds' => 5,
 'request_deadline_at' => microtime(true) + 0.5,
 'response_format' => $this->providerContractSchema(),
 ]);
 } catch (\Throwable) {
 $failed = true;
 }

 $this->assertTrue($failed);
 $this->assertCount(1, Http::recorded());
 }

 public function test_openai_compatible_provider_retries_with_json_object_after_schema_error(): void
 {
 $this->setEnvValue('GROQ_API_KEY', 'groq_test_token');
 config()->set('services.ai_providers.json_mode_fallback', true);
 $providerMethod = new \ReflectionMethod(AIService::class, 'callStructuredProvider');
 $providerMethod->setAccessible(true);

 Http::fakeSequence()
 ->push(['error' => ['message' => 'structured output schema is not supported']], 422)
 ->push($this->openAiCompatibleResponse('groq'), 200);

 $result = $providerMethod->invoke(null, 'groq', 'Return JSON.', [
 'attempts' => 1,
 'response_format' => $this->providerContractSchema(),
 ]);

 $this->assertSame('groq', $result['provider']);
 $requests = collect(Http::recorded())->map(fn (array $entry) => $entry[0])->values();
 $this->assertCount(2, $requests);
 $this->assertSame('json_schema', data_get($requests[0]->data(), 'response_format.type'));
 $this->assertSame('json_object', data_get($requests[1]->data(), 'response_format.type'));
 }

 public function test_openai_reasoning_models_use_supported_generation_parameters(): void
 {
 config()->set('services.openai.reasoning_effort', 'high');
 $parameters = new \ReflectionMethod(AIService::class, 'openAiGenerationParameters');
 $parameters->setAccessible(true);

 $reasoning = $parameters->invoke(null, 'gpt-6-astra', 4096, 0.2);
 $reasoningWithSmallBudget = $parameters->invoke(null, 'gpt-6-astra', 1000, 0.2);
 $legacy = $parameters->invoke(null, 'gpt-4.1-mini', 2048, 0.3);

 $this->assertSame([
 'reasoning_effort' => 'high',
 'max_completion_tokens' => 4096,
 ], $reasoning);
 $this->assertSame(4096, $reasoningWithSmallBudget['max_completion_tokens']);
 $this->assertSame([
 'temperature' => 0.3,
 'max_tokens' => 2048,
 ], $legacy);
 }

 public function test_gemini_four_uses_high_thinking_without_ignored_sampling_parameters(): void
 {
 config()->set('services.gemini.thinking_level', 'high');
 $parameters = new \ReflectionMethod(AIService::class, 'geminiGenerationConfig');
 $parameters->setAccessible(true);

 $argon = $parameters->invoke(null, 'gemini-4-argon', 4096, 0.2, true);
 $flash = $parameters->invoke(null, 'gemini-3.8-flash', 4096, 0.2, true);
 $jsonParameters = new \ReflectionMethod(AIService::class, 'geminiJsonResponseConfig');
 $jsonParameters->setAccessible(true);
 $argonJson = $jsonParameters->invoke(null, $argon, 'gemini-4-argon', [
 'type' => 'object',
 'properties' => ['provider' => ['type' => 'string']],
 ]);

 $this->assertSame(32768, $argon['maxOutputTokens']);
 $this->assertSame(['thinkingLevel' => 'high'], $argon['thinkingConfig']);
 $this->assertArrayNotHasKey('temperature', $argon);
 $this->assertArrayNotHasKey('topP', $argon);
 $this->assertSame(8192, $flash['maxOutputTokens']);
 $this->assertSame(0.2, $flash['temperature']);
 $this->assertSame(0.85, $flash['topP']);
 $this->assertSame(
 'string',
 data_get($argonJson, 'responseJsonSchema.properties.provider.type')
 );
 $this->assertSame('application/json', $argonJson['responseMimeType']);
 $this->assertArrayNotHasKey('responseFormat', $argonJson);
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

 private function providerContractSchema(): array
 {
 return [
 'type' => 'json_schema',
 'json_schema' => [
 'name' => 'provider_contract',
 'strict' => true,
 'schema' => [
 'type' => 'object',
 'additionalProperties' => false,
 'properties' => [
 'provider' => ['type' => 'string'],
 ],
 'required' => ['provider'],
 ],
 ],
 ];
 }

 private function assertStructuredRequestWasSent(string $provider, string $url, string $model, string $token): void
 {
 Http::assertSent(function ($request) use ($provider, $url, $model, $token): bool {
 if ($request->url()!== $url) {
 return false;
 }

 if ($provider === 'gemini') {
 return data_get($request->data(), 'generationConfig.responseMimeType') === 'application/json'
 && data_get($request->data(), 'contents.0.parts.0.text') === 'Return JSON.';
 }

 return $request->hasHeader('Authorization', "Bearer {$token}")
 && data_get($request->data(), 'model') === $model
 && data_get($request->data(), 'response_format.type') === 'json_object'
 && data_get($request->data(), 'messages.'.($provider === 'cohere'? '0': '1').'.content') === 'Return JSON.';
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
