<?php

namespace App\Console\Commands;

use App\Services\AIService;
use Illuminate\Console\Command;
use Throwable;

class CheckAiFeedbackProviders extends Command
{
    protected $signature = 'ai:check-feedback-providers
        {--provider=* : Provider key to check. Defaults to every supported provider.}
        {--json : Output machine-readable JSON.}';

    protected $description = 'Call hosted AI feedback providers and verify strict evidence-linked feedback.';

    public function handle(): int
    {
        $providers = $this->providersToCheck();

        if ($providers === []) {
            $this->error('No supported feedback providers were requested.');

            return self::FAILURE;
        }

        $results = [];
        foreach ($providers as $provider) {
            $results[] = $this->checkProvider($provider);
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode([
                'checked_at' => now()->toIso8601String(),
                'results' => $results,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Provider', 'Status', 'Latency ms', 'Report provider', 'Message'],
                array_map(static fn (array $result): array => [
                    $result['provider'],
                    $result['status'],
                    $result['latency_ms'],
                    $result['report_provider'] ?? '',
                    $result['message'],
                ], $results)
            );
        }

        return collect($results)->every(static fn (array $result): bool => $result['status'] === 'pass')
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function providersToCheck(): array
    {
        $requested = (array) $this->option('provider');
        if ($requested === []) {
            $requested = array_map(
                static fn (array $provider): string => (string) $provider['key'],
                AIService::supportedProviderOptions()
            );
        }

        $providers = [];
        foreach ($requested as $provider) {
            $normalized = AIService::normalizeProviderKey($provider);
            if ($normalized !== '' && AIService::providerIsSupported($normalized)) {
                $providers[] = $normalized;
            }
        }

        return array_values(array_unique($providers));
    }

    private function checkProvider(string $provider): array
    {
        $startedAt = microtime(true);

        if (! AIService::providerIsConfigured($provider)) {
            return [
                'provider' => $provider,
                'status' => 'fail',
                'latency_ms' => 0,
                'message' => 'Provider is not configured with credentials.',
            ];
        }

        try {
            $feedback = AIService::generateFeedback(
                $this->sessionData(),
                $this->answersData(),
                $provider,
                true,
                false
            );

            $item = $feedback['per_question_feedback'][0] ?? [];
            $message = 'Strict hosted feedback passed validation.';
            if (is_array($item) && isset($item['score'], $item['answer_alignment'])) {
                $message.= ' score='.$item['score'].' alignment='.$item['answer_alignment'];
            }

            return [
                'provider' => $provider,
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($startedAt),
                'report_provider' => $feedback['_provider_key'] ?? $provider,
                'message' => $message,
            ];
        } catch (Throwable $error) {
            return [
                'provider' => $provider,
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($startedAt),
                'message' => $this->safeErrorMessage($error),
            ];
        }
    }

    private function sessionData(): array
    {
        return [
            'target_position' => 'Customer Support Specialist',
            'difficulty' => 'Medium',
            'interview_focus' => 'Behavioral interview practice',
            'country' => 'United States',
            'target_language' => null,
            'dataset' => [
                'provider' => 'health_check_fixture',
                'source' => 'api_provider_validation',
                'version' => '1',
            ],
            'dataset_context' => 'Behavioral customer support questions should check for a real situation, the candidate action, and the customer or business result.',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function answersData(): array
    {
        return [[
            'id' => 900001,
            'question_type' => 'behavioral',
            'question' => 'Tell me about a time you handled an upset customer and what result you achieved.',
            'expected_guide' => 'A strong answer explains the customer issue, the candidate action, and the final result.',
            'mapped_skills' => ['customer communication', 'problem solving', 'ownership'],
            'question_source' => [
                'provider' => 'health_check_fixture',
                'dataset' => 'api_provider_validation',
                'record_id' => 'feedback-live-001',
            ],
            'answer' => 'At my last retail job, a customer was upset because an online discount did not apply at checkout. I listened first, checked the promotion rules, called my supervisor for approval, and adjusted the price while explaining the reason clearly. The customer stayed calm, completed the purchase, and later thanked our team for handling it respectfully.',
        ]];
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    private function safeErrorMessage(Throwable $error): string
    {
        $message = $error->getMessage();
        $message = preg_replace('/([?&](?:key|api_key|token)=)[^&\s"]+/i', '$1[redacted]', $message) ?? $message;
        $message = preg_replace('/(Bearer\s+)[A-Za-z0-9._-]+/i', '$1[redacted]', $message) ?? $message;
        $message = preg_replace(
            '/(["\']?(?:api[_-]?key|token|password|authorization)["\']?\s*[:=]\s*["\']?)[^"\'\s,}]+/i',
            '$1[redacted]',
            $message
        ) ?? $message;

        return mb_substr($message, 0, 500);
    }
}
