<?php

namespace Tests\Unit;

use App\Support\RuntimeConfiguration;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Tests\TestCase;

class AiDeploymentConfigurationTest extends TestCase
{
    public function test_ai_provider_and_feedback_settings_have_cached_config_entries(): void
    {
        foreach (['openai', 'gemini', 'groq', 'cohere'] as $provider) {
            $settings = config("services.{$provider}");

            $this->assertIsArray($settings);
            $this->assertArrayHasKey('api_key', $settings);
            $this->assertArrayHasKey('api_endpoint', $settings);
            $this->assertArrayHasKey('model', $settings);
        }

        foreach ([
            'services.ai_feedback.provider_priority',
            'services.ai_feedback.max_providers',
            'services.ai_feedback.timeout_seconds',
            'services.ai_feedback.deadline_seconds',
            'services.ai_feedback.max_attempts',
            'services.ai_feedback.http_attempts',
            'services.ai_feedback.retry_delay_ms',
            'services.ai_feedback.max_output_tokens',
            'services.ai_transcription.finish_recovery_timeout_seconds',
            'services.ai_transcription.local_fallback_reserve_seconds',
            'services.ai_transcription.rate_limit_per_minute',
            'services.ai_tts.onboarding_cache_ttl_seconds',
            'services.ai_tts.onboarding_failure_cache_ttl_seconds',
            'services.ai_tts.onboarding_deadline_seconds',
            'services.ai_tts.onboarding_rate_limit_per_minute',
            'services.ai_tts.onboarding_rate_limit_per_day',
            'services.ai_feedback_finalization.timeout_seconds',
            'services.ai_feedback_review.retry_cooldown_minutes',
            'services.ai_feedback_review.min_reliability_percent',
        ] as $key) {
            $segments = explode('.', $key);
            $leaf = array_pop($segments);
            $parent = config(implode('.', $segments));

            $this->assertIsArray($parent, "Missing configuration parent for {$key}.");
            $this->assertArrayHasKey($leaf, $parent, "Missing cached configuration entry {$key}.");
        }

        $this->assertSame('cache_locks', config('cache.stores.database.lock_table'));
    }

    public function test_runtime_environment_override_is_limited_to_tests(): void
    {
        $environmentKey = 'SPEAKREADY_TEST_RUNTIME_CONFIG_OVERRIDE';
        $previous = getenv($environmentKey);
        $previousTimeout = config('services.ai_feedback.timeout_seconds');
        config(['services.ai_feedback.timeout_seconds' => 41]);

        try {
            putenv($environmentKey);
            unset($_ENV[$environmentKey], $_SERVER[$environmentKey]);
            $this->assertSame(41, RuntimeConfiguration::get(
                'services.ai_feedback.timeout_seconds',
                $environmentKey,
                9
            ));

            putenv("{$environmentKey}=17");
            $_ENV[$environmentKey] = '17';
            $_SERVER[$environmentKey] = '17';
            $this->assertSame('17', RuntimeConfiguration::get(
                'services.ai_feedback.timeout_seconds',
                $environmentKey,
                9
            ));
        } finally {
            config(['services.ai_feedback.timeout_seconds' => $previousTimeout]);
            if ($previous === false) {
                putenv($environmentKey);
                unset($_ENV[$environmentKey], $_SERVER[$environmentKey]);
            } else {
                putenv("{$environmentKey}={$previous}");
                $_ENV[$environmentKey] = $previous;
                $_SERVER[$environmentKey] = $previous;
            }
        }
    }

    public function test_transcription_rate_limiter_reads_cached_configuration(): void
    {
        $previous = config('services.ai_transcription.rate_limit_per_minute');

        try {
            config(['services.ai_transcription.rate_limit_per_minute' => 7]);

            $resolver = $this->app->make(RateLimiter::class)->limiter('ai-transcribe');

            $this->assertIsCallable($resolver);

            $limits = $resolver(Request::create('/interview/transcribe', 'POST', server: [
                'REMOTE_ADDR' => '203.0.113.25',
            ]));

            $this->assertIsArray($limits);
            $this->assertSame([7, 14], array_map(
                static fn ($limit): int => $limit->maxAttempts,
                $limits
            ));
        } finally {
            config(['services.ai_transcription.rate_limit_per_minute' => $previous]);
        }
    }

    public function test_onboarding_voice_rate_limiter_uses_its_own_cached_configuration(): void
    {
        $previous = config('services.ai_tts.onboarding_rate_limit_per_minute');
        $previousDaily = config('services.ai_tts.onboarding_rate_limit_per_day');

        try {
            config([
                'services.ai_tts.onboarding_rate_limit_per_minute' => 9,
                'services.ai_tts.onboarding_rate_limit_per_day' => 75,
            ]);

            $resolver = $this->app->make(RateLimiter::class)->limiter('ai-voice');

            $this->assertIsCallable($resolver);

            $limits = $resolver(Request::create('/onboarding/speech', 'POST', server: [
                'REMOTE_ADDR' => '203.0.113.26',
            ]));

            $this->assertIsArray($limits);
            $this->assertSame([9, 18, 75], array_map(
                static fn ($limit): int => $limit->maxAttempts,
                $limits
            ));
        } finally {
            config([
                'services.ai_tts.onboarding_rate_limit_per_minute' => $previous,
                'services.ai_tts.onboarding_rate_limit_per_day' => $previousDaily,
            ]);
        }
    }

    public function test_container_examples_forward_reliability_controls_and_share_the_gemini_model(): void
    {
        $compose = file_get_contents(base_path('docker-compose.yml'));
        $dokploy = file_get_contents(base_path('dokploy.env.example'));
        $environment = file_get_contents(base_path('.env.example'));

        $this->assertIsString($compose);
        $this->assertIsString($dokploy);
        $this->assertIsString($environment);

        foreach ([
            'AI_TTS_ONBOARDING_CACHE_TTL_SECONDS',
            'AI_TTS_ONBOARDING_FAILURE_CACHE_TTL_SECONDS',
            'AI_TTS_ONBOARDING_DEADLINE_SECONDS',
            'AI_TTS_ONBOARDING_RATE_LIMIT_PER_MINUTE',
            'AI_TTS_ONBOARDING_RATE_LIMIT_PER_DAY',
            'AI_TRANSCRIPTION_PROOF_TTL_SECONDS',
            'AI_TRANSCRIPTION_FINISH_RECOVERY_TIMEOUT',
            'AI_TRANSCRIPTION_LOCAL_FALLBACK_RESERVE_SECONDS',
            'AI_FEEDBACK_REVIEW_RETRY_COOLDOWN_MINUTES',
            'AI_FEEDBACK_REVIEW_MIN_RELIABILITY_PERCENT',
        ] as $name) {
            $this->assertStringContainsString("{$name}:", $compose);
            $this->assertStringContainsString("{$name}=", $dokploy);
            $this->assertStringContainsString("{$name}=", $environment);
        }

        $this->assertStringContainsString(
            'GEMINI_TRANSCRIPTION_MODEL: "${GEMINI_TRANSCRIPTION_MODEL:-gemini-4-argon}"',
            $compose
        );
        $this->assertStringContainsString('GEMINI_TRANSCRIPTION_MODEL=gemini-4-argon', $dokploy);
        $this->assertStringContainsString('GEMINI_TRANSCRIPTION_MODEL=gemini-4-argon', $environment);

        $this->assertStringContainsString(
            'LOCAL_FEEDBACK_MODEL_MAX_VALIDATION_MAE: "${LOCAL_FEEDBACK_MODEL_MAX_VALIDATION_MAE:-5}"',
            $compose
        );
        $this->assertStringContainsString('LOCAL_FEEDBACK_MODEL_MAX_VALIDATION_MAE=5', $dokploy);
        $this->assertStringContainsString('LOCAL_FEEDBACK_MODEL_MAX_VALIDATION_MAE=5', $environment);
    }
}
