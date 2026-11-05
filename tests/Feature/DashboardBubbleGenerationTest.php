<?php

namespace Tests\Feature;

use App\Jobs\GenerateDashboardBubbleMessages;
use App\Models\AiProvider;
use App\Models\Setting;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DashboardBubbleGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_fallback_without_calling_provider_and_queues_only_once(): void
    {
        $this->configureProvider();
        Queue::fake();
        Http::fake();
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('dashboardBubbleMessages', AIService::fallbackDashboardBubbleMessages());
        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        Http::assertNothingSent();
        Queue::assertPushedTimes(GenerateDashboardBubbleMessages::class, 1);
        Queue::assertPushed(GenerateDashboardBubbleMessages::class, function ($job): bool {
            return $job->provider === 'openai'
                && $job->context['completed_sessions'] === 0
                && str_starts_with($job->cacheKey, 'dashboard-bubble-messages:v3:openai:');
        });
    }

    public function test_queued_job_populates_cache_and_future_dashboard_uses_generated_messages(): void
    {
        $this->configureProvider();
        Queue::fake();
        $messages = [
            ['line' => 'Practice one interview answer today and track your progress.', 'action' => 'Start a focused mock interview.'],
            ['line' => 'Your next practice session can build more confidence.', 'action' => 'Ask AI Coach for a practice plan.'],
            ['line' => 'Review your feedback and choose one skill to sharpen.', 'action' => 'Open your latest feedback.'],
            ['line' => 'Small steps today can improve interview readiness.', 'action' => 'Keep your practice streak going.'],
        ];
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'finish_reason' => 'stop',
                    'message' => ['content' => json_encode(['messages' => $messages])],
                ]],
            ]),
        ]);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $job = null;
        Queue::assertPushed(GenerateDashboardBubbleMessages::class, function ($queued) use (&$job): bool {
            $job = $queued;

            return true;
        });
        $this->assertInstanceOf(GenerateDashboardBubbleMessages::class, $job);
        $this->assertNull(Cache::get($job->cacheKey));
        Http::assertNothingSent();

        $job->handle();
        $this->assertSame($messages, Cache::get($job->cacheKey));
        Http::assertSentCount(1);

        $job->handle();
        Http::assertSentCount(1);
        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('dashboardBubbleMessages', $messages);
        Queue::assertPushedTimes(GenerateDashboardBubbleMessages::class, 1);
    }

    private function configureProvider(): void
    {
        config([
            'queue.default' => 'database',
            'services.openai.model' => 'gpt-4o-mini',
        ]);
        Setting::setVal('aic_enable', true, 'ai', 'boolean');
        AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
    }
}
