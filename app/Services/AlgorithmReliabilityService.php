<?php

namespace App\Services;

use Illuminate\Container\Container;

/**
 * Shared, fail-closed quality policy for non-provider algorithms.
 *
 * A passing gate means that declared, measurable validation criteria met the
 * configured production floor. It never means that an individual prediction
 * or coaching outcome is guaranteed to be correct.
 */
final class AlgorithmReliabilityService
{
    public const VERSION = 'algorithm_reliability_v1';

    public const HARD_MINIMUM_PERCENT = 95;

    /**
     * @param  array<string, mixed>  $metrics
     * @param  array<int, string>  $requiredMetrics
     * @return array<string, mixed>
     */
    public function evaluate(
        string $algorithm,
        array $metrics,
        array $requiredMetrics = ['quality', 'accuracy', 'reliability'],
        int $evidenceCount = 1,
        int $minimumEvidenceCount = 1,
        string $scoreBasis = 'measured_validation_metrics'
    ): array {
        $thresholds = $this->thresholds();
        $normalizedMetrics = [];
        $checks = [];

        foreach (array_values(array_unique($requiredMetrics)) as $metric) {
            if (! array_key_exists($metric, $thresholds)) {
                $checks[$metric] = false;
                $normalizedMetrics[$metric] = null;

                continue;
            }

            $value = $this->percentage($metrics[$metric] ?? null);
            $normalizedMetrics[$metric] = $value;
            $checks[$metric] = $value !== null && $value >= $thresholds[$metric];
        }

        $minimumEvidenceCount = max(1, $minimumEvidenceCount);
        $evidenceCount = max(0, $evidenceCount);
        $checks['minimum_evidence'] = $evidenceCount >= $minimumEvidenceCount;
        $failedChecks = array_keys(array_filter($checks, fn (bool $passed): bool => ! $passed));
        $passed = $failedChecks === [];

        return [
            'version' => self::VERSION,
            'algorithm' => trim($algorithm),
            'passed' => $passed,
            'status' => $passed ? 'qualified' : 'blocked',
            'eligible_for_automated_decision' => $passed,
            'decision_support_only' => ! $passed,
            'accuracy_guaranteed' => false,
            'verification_required' => true,
            'score_basis' => trim($scoreBasis) ?: 'measured_validation_metrics',
            'metrics' => $normalizedMetrics,
            'thresholds' => array_intersect_key($thresholds, array_flip($requiredMetrics)),
            'checks' => $checks,
            'failed_checks' => $failedChecks,
            'evidence_count' => $evidenceCount,
            'minimum_evidence_count' => $minimumEvidenceCount,
        ];
    }

    /** @return array{quality: int, accuracy: int, reliability: int, confidence: int} */
    public function thresholds(): array
    {
        return [
            'quality' => $this->configuredPercentage('min_quality_percent'),
            'accuracy' => $this->configuredPercentage('min_accuracy_percent'),
            'reliability' => $this->configuredPercentage('min_reliability_percent'),
            'confidence' => $this->configuredPercentage('min_confidence_percent'),
        ];
    }

    public function minimumReliabilityPercent(): int
    {
        return $this->thresholds()['reliability'];
    }

    public function minimumEvidenceCount(): int
    {
        return max(1, min(100000, (int) $this->configuration('services.algorithms.min_evidence_count', 100)));
    }

    private function configuredPercentage(string $key): int
    {
        return max(
            self::HARD_MINIMUM_PERCENT,
            min(100, (int) $this->configuration('services.algorithms.'.$key, self::HARD_MINIMUM_PERCENT))
        );
    }

    private function configuration(string $key, mixed $default): mixed
    {
        $container = Container::getInstance();
        if (! $container->bound('config')) {
            return $default;
        }

        return $container->make('config')->get($key, $default);
    }

    private function percentage(mixed $value): ?float
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            return null;
        }

        $bounded = max(0.0, min(100.0, (float) $value));

        // Never let display rounding promote a value below the policy floor.
        return floor(($bounded + PHP_FLOAT_EPSILON) * 100) / 100;
    }
}
