<?php

namespace App\Services;

use App\Models\AiProviderLog;
use Illuminate\Support\Facades\Schema;

/**
 * Fail-closed policy for hosted AI providers.
 *
 * Percentages produced by this policy are measured quality-gate results. They
 * are not claims that probabilistic model output is factually accurate 95% of
 * the time. Semantic output still has to pass the task-specific validators.
 */
final class AiProviderReliabilityService
{
    public const VERSION = 'ai_provider_reliability_v1';

    private const DEFAULT_MINIMUM_PERCENT = 95;

    private const OFFICIAL_HOSTS = [
        'openai' => ['api.openai.com'],
        'gemini' => ['generativelanguage.googleapis.com'],
        'groq' => ['api.groq.com'],
        'cohere' => ['api.cohere.com', 'api.cohere.ai'],
    ];

    /**
     * @return array<string, int>
     */
    public function thresholds(): array
    {
        return [
            'quality_score' => $this->percentage('min_quality_percent', 95),
            'accuracy_score' => $this->percentage('min_accuracy_percent', 95),
            'reliability_score' => $this->percentage('min_reliability_percent', 95),
            'schema_score' => $this->percentage('min_schema_percent', 100),
            'safety_score' => $this->percentage('min_safety_percent', 100),
            'result_success_rate' => $this->percentage('min_success_rate_percent', 95),
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>
     */
    public function evaluate(array $metrics, bool $requireMinimumEvidence = true): array
    {
        $thresholds = $this->thresholds();
        $checks = [];
        $normalizedMetrics = [];

        foreach ($thresholds as $metric => $minimum) {
            $value = $this->score($metrics[$metric] ?? null);
            $normalizedMetrics[$metric] = $value;
            $checks[$metric] = $value !== null && $value >= $minimum;
        }

        $minimumEvidence = $requireMinimumEvidence ? $this->minimumBenchmarkSamples() : 1;
        $evidenceCount = max(0, (int) ($metrics['successful_evidence_count'] ?? 1));
        $checks['minimum_evidence'] = $evidenceCount >= $minimumEvidence;

        $failedChecks = array_keys(array_filter($checks, fn (bool $passed): bool => ! $passed));

        return [
            'version' => self::VERSION,
            'passed' => $failedChecks === [],
            'status' => $failedChecks === [] ? 'passed' : 'blocked',
            'accuracy_guaranteed' => false,
            'score_basis' => 'measured_quality_gates_not_factual_accuracy_guarantee',
            'metrics' => $normalizedMetrics,
            'thresholds' => $thresholds,
            'checks' => $checks,
            'failed_checks' => $failedChecks,
            'successful_evidence_count' => $evidenceCount,
            'minimum_evidence_count' => $minimumEvidence,
        ];
    }

    /**
     * @return array{open: bool, failure_count: int, threshold: int, retry_after_seconds: int}
     */
    public function circuitState(string $provider, string $module): array
    {
        $threshold = max(2, min(10, (int) config('services.ai_providers.circuit_failure_threshold', 3)));
        $closed = [
            'open' => false,
            'failure_count' => 0,
            'threshold' => $threshold,
            'retry_after_seconds' => 0,
        ];

        if (! filter_var(config('services.ai_providers.circuit_breaker_enabled', true), FILTER_VALIDATE_BOOLEAN)
            || trim($provider) === ''
            || trim($module) === ''
            || ! Schema::hasTable('ai_provider_logs')) {
            return $closed;
        }

        $cooldown = max(15, min(3600, (int) config('services.ai_providers.circuit_cooldown_seconds', 120)));

        try {
            $logs = AiProviderLog::query()
                ->where('endpoint', $provider)
                ->where('module', $module)
                ->where('created_at', '>=', now()->subSeconds($cooldown))
                ->latest('id')
                ->limit($threshold)
                ->get(['status', 'created_at']);
        } catch (\Throwable) {
            return $closed;
        }

        $consecutiveFailures = $logs
            ->takeUntil(fn (AiProviderLog $log): bool => $log->status === 'success')
            ->count();

        if ($consecutiveFailures < $threshold || $logs->isEmpty()) {
            return array_merge($closed, ['failure_count' => $consecutiveFailures]);
        }

        $lastFailureAt = $logs->first()?->created_at;
        $retryAfter = $lastFailureAt
            ? max(0, $lastFailureAt->copy()->addSeconds($cooldown)->getTimestamp() - now()->getTimestamp())
            : 0;

        return [
            'open' => $retryAfter > 0,
            'failure_count' => $consecutiveFailures,
            'threshold' => $threshold,
            'retry_after_seconds' => $retryAfter,
        ];
    }

    /**
     * @return array{valid: bool, reason: string, host: string}
     */
    public function validateEndpoint(string $provider, string $endpoint): array
    {
        $provider = strtolower(trim($provider));
        $endpoint = trim($endpoint);
        $parts = parse_url($endpoint);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return ['valid' => false, 'reason' => 'The provider endpoint must use HTTPS.', 'host' => $host];
        }

        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            return ['valid' => false, 'reason' => 'The provider endpoint must use an approved provider hostname.', 'host' => $host];
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            return ['valid' => false, 'reason' => 'The provider endpoint cannot contain credentials or a URL fragment.', 'host' => $host];
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : 443;
        if ($port !== 443) {
            return ['valid' => false, 'reason' => 'The provider endpoint must use the standard HTTPS port.', 'host' => $host];
        }

        $query = strtolower((string) ($parts['query'] ?? ''));
        if ($query !== '' && preg_match('/(?:^|&)(?:key|api_key|token|access_token)=/', $query) === 1) {
            return ['valid' => false, 'reason' => 'Keep API keys out of the endpoint URL.', 'host' => $host];
        }

        if (filter_var(config('services.ai_providers.allow_custom_endpoints', false), FILTER_VALIDATE_BOOLEAN)) {
            return ['valid' => true, 'reason' => '', 'host' => $host];
        }

        $approvedHosts = self::OFFICIAL_HOSTS[$provider] ?? [];
        if (! in_array($host, $approvedHosts, true)) {
            return [
                'valid' => false,
                'reason' => 'The endpoint host does not match the selected official AI provider.',
                'host' => $host,
            ];
        }

        return ['valid' => true, 'reason' => '', 'host' => $host];
    }

    public function minimumBenchmarkSamples(): int
    {
        return max(1, min(20, (int) config('services.ai_providers.min_benchmark_samples', 3)));
    }

    private function percentage(string $key, int $default): int
    {
        return max(
            self::DEFAULT_MINIMUM_PERCENT,
            min(100, (int) config('services.ai_providers.'.$key, $default))
        );
    }

    private function score(mixed $value): ?float
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            return null;
        }

        $bounded = max(0.0, min(100.0, (float) $value));

        return floor(($bounded + PHP_FLOAT_EPSILON) * 100) / 100;
    }
}
