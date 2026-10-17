<?php

namespace Tests\Unit;

use App\Services\AlgorithmReliabilityService;
use Tests\TestCase;

class AlgorithmReliabilityServiceTest extends TestCase
{
    public function test_every_required_metric_and_evidence_count_must_pass(): void
    {
        $service = new AlgorithmReliabilityService;

        $passed = $service->evaluate('validated_model', [
            'quality' => 96,
            'accuracy' => 95,
            'reliability' => 98,
        ], evidenceCount: 200, minimumEvidenceCount: 100);

        $this->assertTrue($passed['passed']);
        $this->assertTrue($passed['eligible_for_automated_decision']);
        $this->assertFalse($passed['accuracy_guaranteed']);
        $this->assertSame('qualified', $passed['status']);

        $blocked = $service->evaluate('under_validated_model', [
            'quality' => 99,
            'accuracy' => 94,
            'reliability' => 99,
        ], evidenceCount: 99, minimumEvidenceCount: 100);

        $this->assertFalse($blocked['passed']);
        $this->assertFalse($blocked['eligible_for_automated_decision']);
        $this->assertTrue($blocked['decision_support_only']);
        $this->assertContains('accuracy', $blocked['failed_checks']);
        $this->assertContains('minimum_evidence', $blocked['failed_checks']);

        $roundedUp = $service->evaluate('near_miss', [
            'quality' => 100,
            'accuracy' => 94.99,
            'reliability' => 100,
        ]);
        $this->assertFalse($roundedUp['passed'], 'A metric below 95 must not pass by integer rounding.');

        $fractionalNearMiss = $service->evaluate('fractional_near_miss', [
            'quality' => 100,
            'accuracy' => 94.999,
            'reliability' => 100,
        ]);
        $this->assertFalse($fractionalNearMiss['passed'], 'A fractional value below 95 must fail closed.');
    }

    public function test_configuration_cannot_lower_any_percentage_below_95(): void
    {
        config()->set('services.algorithms', [
            'min_quality_percent' => 50,
            'min_accuracy_percent' => 60,
            'min_reliability_percent' => 70,
            'min_confidence_percent' => 80,
            'min_evidence_count' => 100,
        ]);

        $service = new AlgorithmReliabilityService;

        $this->assertSame([
            'quality' => 95,
            'accuracy' => 95,
            'reliability' => 95,
            'confidence' => 95,
        ], $service->thresholds());
    }

    public function test_missing_or_non_finite_metrics_fail_closed(): void
    {
        $result = (new AlgorithmReliabilityService)->evaluate('invalid_metrics', [
            'quality' => INF,
            'reliability' => 100,
        ], ['quality', 'accuracy', 'reliability']);

        $this->assertFalse($result['passed']);
        $this->assertNull($result['metrics']['quality']);
        $this->assertNull($result['metrics']['accuracy']);
        $this->assertContains('quality', $result['failed_checks']);
        $this->assertContains('accuracy', $result['failed_checks']);
    }
}
