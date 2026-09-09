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
