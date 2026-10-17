<?php

namespace Tests\Unit;

use App\Services\AiProviderReliabilityService;
use Tests\TestCase;

class AiProviderReliabilityServiceTest extends TestCase
{
    public function test_reliability_thresholds_cannot_be_configured_below_95_percent(): void
    {
        config()->set('services.ai_providers.min_quality_percent', 10);
        config()->set('services.ai_providers.min_schema_percent', 80);

        $thresholds = app(AiProviderReliabilityService::class)->thresholds();

        $this->assertSame(95, $thresholds['quality_score']);
        $this->assertSame(95, $thresholds['schema_score']);
    }

    public function test_provider_gate_requires_every_metric_and_minimum_evidence(): void
    {
        $policy = app(AiProviderReliabilityService::class);
        $metrics = [
            'quality_score' => 100,
            'accuracy_score' => 100,
            'reliability_score' => 100,
            'schema_score' => 100,
            'safety_score' => 100,
            'result_success_rate' => 100,
            'successful_evidence_count' => 2,
        ];

        $insufficient = $policy->evaluate($metrics);
        $this->assertFalse($insufficient['passed']);
        $this->assertContains('minimum_evidence', $insufficient['failed_checks']);

        $metrics['successful_evidence_count'] = 3;
        $metrics['accuracy_score'] = 94;
        $belowTarget = $policy->evaluate($metrics);
        $this->assertFalse($belowTarget['passed']);
        $this->assertContains('accuracy_score', $belowTarget['failed_checks']);

        $metrics['accuracy_score'] = 95;
        $passed = $policy->evaluate($metrics);
        $this->assertTrue($passed['passed']);
        $this->assertFalse($passed['accuracy_guaranteed']);

        $metrics['accuracy_score'] = 94.999;
        $fractionalNearMiss = $policy->evaluate($metrics);
        $this->assertFalse($fractionalNearMiss['passed']);
        $this->assertContains('accuracy_score', $fractionalNearMiss['failed_checks']);
    }

    public function test_only_https_official_provider_endpoints_are_trusted_by_default(): void
    {
        $policy = app(AiProviderReliabilityService::class);

        $this->assertTrue($policy->validateEndpoint('openai', 'https://api.openai.com/v1')['valid']);
        $this->assertTrue($policy->validateEndpoint('gemini', 'https://generativelanguage.googleapis.com/v1beta')['valid']);
        $this->assertFalse($policy->validateEndpoint('openai', 'http://api.openai.com/v1')['valid']);
        $this->assertFalse($policy->validateEndpoint('openai', 'https://api.groq.com/openai/v1')['valid']);
        $this->assertFalse($policy->validateEndpoint('openai', 'https://127.0.0.1/v1')['valid']);
        $this->assertFalse($policy->validateEndpoint('openai', 'https://api.openai.com/v1?api_key=secret')['valid']);
    }
}
