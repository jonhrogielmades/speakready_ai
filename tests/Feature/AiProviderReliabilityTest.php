<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\AiProviderLog;
use App\Models\User;
use App\Services\AiProviderReliabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_store_untrusted_or_duplicate_provider_configuration(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.ai.providers.store'), [
                'name' => 'OpenAI',
                'api_endpoint' => 'https://api.groq.com/openai/v1',
                'api_key' => 'secret-key',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('api_endpoint');

        $this->assertDatabaseCount('ai_providers', 0);

        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('existing-key'),
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.ai.providers.store'), [
                'name' => 'GPT',
                'api_endpoint' => 'https://api.openai.com/v1',
                'api_key' => 'second-key',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('ai_providers', 1);
    }

    public function test_connection_test_requires_a_schema_validated_nonce_response(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        Http::fake(function ($request) {
            $prompt = (string) data_get($request->data(), 'messages.1.content', '');
            preg_match('/nonce ([a-z0-9]+)/', $prompt, $match);

            return Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['content' => json_encode([
                        'status' => 'ready',
                        'nonce' => $match[1] ?? 'wrong',
                    ])],
                ]],
            ], 200);
        });

        $this->actingAs($admin)
            ->post(route('admin.ai.providers.test', $provider))
            ->assertRedirect()
            ->assertSessionHas('message');

        $this->assertDatabaseHas('ai_provider_logs', [
            'provider_id' => $provider->id,
            'module' => 'provider_connection_test',
            'status' => 'success',
        ]);
    }

    public function test_connection_test_rejects_a_well_formed_response_with_the_wrong_nonce(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
        ]);

        Http::fake(Http::response([
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => json_encode([
                    'status' => 'ready',
                    'nonce' => 'wrong-nonce',
                ])],
            ]],
        ], 200));

        $this->actingAs($admin)
            ->post(route('admin.ai.providers.test', $provider))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('ai_provider_logs', [
            'provider_id' => $provider->id,
            'module' => 'provider_connection_test',
            'status' => 'failed',
        ]);
    }

    public function test_circuit_breaker_opens_after_consecutive_failures_and_closes_after_success(): void
    {
        config()->set('services.ai_providers.circuit_breaker_enabled', true);
        config()->set('services.ai_providers.circuit_failure_threshold', 3);
        config()->set('services.ai_providers.circuit_cooldown_seconds', 120);

        foreach (range(1, 3) as $index) {
            AiProviderLog::create([
                'module' => 'feedback_generation',
                'endpoint' => 'openai',
                'response_time_ms' => 500 + $index,
                'status' => 'failed',
                'created_at' => now(),
            ]);
        }

        $policy = app(AiProviderReliabilityService::class);
        $this->assertTrue($policy->circuitState('openai', 'feedback_generation')['open']);

        AiProviderLog::create([
            'module' => 'feedback_generation',
            'endpoint' => 'openai',
            'response_time_ms' => 450,
            'status' => 'success',
            'created_at' => now(),
        ]);

        $this->assertFalse($policy->circuitState('openai', 'feedback_generation')['open']);
    }
}
