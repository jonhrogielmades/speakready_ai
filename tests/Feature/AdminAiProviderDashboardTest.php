<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\AiProviderLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AdminAiProviderDashboardTest extends TestCase
{
    use RefreshDatabase;

    private array $originalProviderEnvironment = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->providerEnvironmentKeys() as $environmentKey) {
            $this->originalProviderEnvironment[$environmentKey] = getenv($environmentKey);
            putenv("{$environmentKey}=");
            $_ENV[$environmentKey] = '';
            $_SERVER[$environmentKey] = '';
        }

        config([
            'services.openai.api_key' => '',
            'services.gemini.api_key' => '',
            'services.groq.api_key' => '',
            'services.cohere.api_key' => '',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->originalProviderEnvironment as $environmentKey => $value) {
            if ($value === false) {
                putenv($environmentKey);
                unset($_ENV[$environmentKey], $_SERVER[$environmentKey]);
            } else {
                putenv("{$environmentKey}={$value}");
                $_ENV[$environmentKey] = $value;
                $_SERVER[$environmentKey] = $value;
            }
        }

        parent::tearDown();
    }

    public function test_provider_dashboard_removes_unneeded_panels_on_desktop_and_mobile(): void
    {
        $admin = $this->admin();
        $clients = [
            [
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126 Safari/537.36',
                'stylesheet' => 'css/desktop/admin/ai/providers.css?v=9',
            ],
            [
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Version/17.5 Mobile/15E148 Safari/604.1',
                'stylesheet' => 'css/mobile/admin/ai/providers.css?v=10',
            ],
        ];

        foreach ($clients as $client) {
            $this->actingAs($admin)
                ->withHeader('User-Agent', $client['user_agent'])
                ->get(route('admin.ai.providers'))
                ->assertOk()
                ->assertSee($client['stylesheet'], false)
                ->assertSee('Providers Status &amp; Usage', false)
                ->assertSee('Refresh Usage')
                ->assertSee('data-provider-usage-grid', false)
                ->assertSee('Add AI Provider')
                ->assertSee('Add Your First Provider')
                ->assertSee('ai-provider-empty-state', false)
                ->assertSee('id="addProviderModal"', false)
                ->assertSee('data-provider-add-form', false)
                ->assertSee('data-provider-option="openai"', false)
                ->assertSee('data-provider-option="gemini"', false)
                ->assertSee('data-provider-option="groq"', false)
                ->assertSee('data-provider-option="cohere"', false)
                ->assertSee('data-endpoint="https://api.openai.com/v1"', false)
                ->assertSee('data-endpoint="https://generativelanguage.googleapis.com/v1beta"', false)
                ->assertSee('data-endpoint="https://api.groq.com/openai/v1"', false)
                ->assertSee('data-endpoint="https://api.cohere.com/v2/chat"', false)
                ->assertSee('js/admin-ai-providers.js?v=1', false)
                ->assertDontSee('Requests by Module')
                ->assertDontSee('id="moduleUsageTable"', false)
                ->assertDontSee('Cost Monitoring')
                ->assertDontSee('Monthly Cost')
                ->assertDontSee('Primary Provider')
                ->assertDontSee('Fallback Provider')
                ->assertDontSee('ai-provider-role-grid', false)
                ->assertDontSee('OpenAI Process Connections')
                ->assertDontSee('id="openAiProcessTable"', false);
        }
    }

    public function test_admin_can_add_an_encrypted_provider_and_see_it_in_the_table(): void
    {
        $admin = $this->admin();
        $plainTextKey = 'openai-dashboard-secret-key';

        $this->actingAs($admin)
            ->post(route('admin.ai.providers.store'), [
                '_provider_form' => 'add',
                'name' => 'openai',
                'api_endpoint' => 'https://api.openai.com/v1',
                'api_key' => $plainTextKey,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.ai.providers'))
            ->assertSessionHas('message', 'AI Provider added successfully.');

        $provider = AiProvider::query()->sole();

        $this->assertSame('OpenAI', $provider->name);
        $this->assertSame('https://api.openai.com/v1', $provider->api_endpoint);
        $this->assertSame('active', $provider->status);
        $this->assertTrue($provider->is_primary);
        $this->assertFalse($provider->is_fallback);
        $this->assertNotSame($plainTextKey, $provider->getRawOriginal('api_key'));
        $this->assertSame($plainTextKey, Crypt::decryptString($provider->getRawOriginal('api_key')));

        $this->actingAs($admin)
            ->get(route('admin.ai.providers'))
            ->assertOk()
            ->assertSee('OpenAI')
            ->assertSee('data-provider-key="openai"', false)
            ->assertDontSee('data-provider-option="openai"', false)
            ->assertDontSee('No providers registered yet.');
    }

    public function test_add_provider_validation_reopens_the_modal_without_flashing_the_api_key(): void
    {
        $admin = $this->admin();
        $plainTextKey = 'never-flash-this-provider-key';

        $response = $this->actingAs($admin)
            ->post(route('admin.ai.providers.store'), [
                '_provider_form' => 'add',
                'name' => 'openai',
                'api_endpoint' => 'https://api.groq.com/openai/v1',
                'api_key' => $plainTextKey,
                'status' => 'active',
            ]);

        $response->assertRedirect()->assertSessionHasErrors('api_endpoint');
        $this->assertArrayNotHasKey('api_key', session()->getOldInput());
        $this->assertDatabaseCount('ai_providers', 0);

        $this->actingAs($admin)
            ->get(route('admin.ai.providers'))
            ->assertOk()
            ->assertSee('data-reopen-on-error="true"', false)
            ->assertSee('The endpoint host does not match the selected official AI provider.')
            ->assertDontSee($plainTextKey);
    }

    public function test_first_active_provider_added_becomes_the_routing_primary_after_an_inactive_entry(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.ai.providers.store'), [
            '_provider_form' => 'add',
            'name' => 'openai',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => 'inactive-openai-key',
            'status' => 'inactive',
        ])->assertRedirect(route('admin.ai.providers'));

        $this->actingAs($admin)->post(route('admin.ai.providers.store'), [
            '_provider_form' => 'add',
            'name' => 'gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => 'active-gemini-key',
            'status' => 'active',
        ])->assertRedirect(route('admin.ai.providers'));

        $this->assertFalse(AiProvider::where('name', 'OpenAI')->firstOrFail()->is_primary);
        $this->assertTrue(AiProvider::where('name', 'Gemini')->firstOrFail()->is_primary);
    }

    public function test_base_validation_never_flashes_a_provider_api_key(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.ai.providers.store'), [
                '_provider_form' => 'add',
                'name' => 'openai',
                'api_endpoint' => 'https://api.openai.com/v1',
                'api_key' => 'provider-key-that-must-not-be-flashed',
                'status' => 'unsupported-status',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertArrayNotHasKey('api_key', session()->getOldInput());
        $this->assertDatabaseCount('ai_providers', 0);
    }

    public function test_provider_status_and_usage_combines_linked_and_legacy_logs_accurately(): void
    {
        $admin = $this->admin();
        $openAi = $this->provider('OpenAI', 'https://api.openai.com/v1', 'active');
        $this->provider('Gemini', 'https://generativelanguage.googleapis.com/v1beta', 'inactive');
        $this->provider('Groq', 'https://api.groq.com/openai/v1', 'active', 'invalid-encrypted-key');

        $now = now();
        AiProviderLog::query()->insert([
            [
                'provider_id' => $openAi->id,
                'module' => 'feedback_generation',
                'endpoint' => 'openai',
                'response_time_ms' => 100,
                'tokens_used' => null,
                'cost' => null,
                'status' => 'success',
                'error_message' => null,
                'created_at' => $now->copy()->subMinutes(2),
                'updated_at' => $now->copy()->subMinutes(2),
            ],
            [
                'provider_id' => null,
                'module' => 'question_generation',
                'endpoint' => 'openai',
                'response_time_ms' => 300,
                'tokens_used' => null,
                'cost' => null,
                'status' => 'failed',
                'error_message' => 'Test failure',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'provider_id' => $openAi->id,
                'module' => 'feedback_generation',
                'endpoint' => 'openai',
                'response_time_ms' => 900,
                'tokens_used' => null,
                'cost' => null,
                'status' => 'success',
                'error_message' => null,
                'created_at' => today()->subSecond(),
                'updated_at' => today()->subSecond(),
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.ai.providers'));

        $response
            ->assertOk()
            ->assertSee('id="provider-openai-requests-today">2', false)
            ->assertSee('id="provider-openai-successful-today">1', false)
            ->assertSee('id="provider-openai-failed-today">1', false)
            ->assertSee('50.00%')
            ->assertSee('200ms')
            ->assertSee('Needs Attention')
            ->assertSee('Not Setup');

        $response->assertViewHas('providerStats', function ($providerStats): bool {
            $openAi = $providerStats->firstWhere('key', 'openai');
            $gemini = $providerStats->firstWhere('key', 'gemini');
            $groq = $providerStats->firstWhere('key', 'groq');
            $cohere = $providerStats->firstWhere('key', 'cohere');

            return $openAi?->status === 'active'
                && $openAi->requests_today === 2
                && $openAi->successful_requests === 1
                && $openAi->failed_requests === 1
                && $openAi->avg_response_time === 200
                && $openAi->success_rate === 50.0
                && $openAi->last_activity_at !== null
                && $gemini?->status === 'inactive'
                && $groq?->status === 'misconfigured'
                && $cohere?->status === 'unconfigured';
        });
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
    }

    private function provider(string $name, string $endpoint, string $status, ?string $encryptedKey = null): AiProvider
    {
        return AiProvider::create([
            'name' => $name,
            'api_endpoint' => $endpoint,
            'api_key' => $encryptedKey ?? Crypt::encryptString(strtolower($name).'-test-key'),
            'status' => $status,
        ]);
    }

    private function providerEnvironmentKeys(): array
    {
        return [
            'OPENAI_API_KEY',
            'GEMINI_API_KEY',
            'GROQ_API_KEY',
            'COHERE_API_KEY',
        ];
    }
}
