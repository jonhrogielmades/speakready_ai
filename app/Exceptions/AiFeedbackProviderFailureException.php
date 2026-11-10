<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class AiFeedbackProviderFailureException extends RuntimeException
{
    /**
     * @param  array<int, string>  $providers
     * @param  array<int, string>  $attemptedProviders
     * @param  array<string, array<int, string>|string>  $failureReasons
     */
    public function __construct(
        private array $providers,
        private array $attemptedProviders = [],
        ?string $message = null,
        ?Throwable $previous = null,
        private array $failureReasons = []
    ) {
        $this->providers = $this->cleanProviderList($providers);
        $this->attemptedProviders = $this->cleanProviderList($attemptedProviders);
        $this->failureReasons = $this->cleanFailureReasons($failureReasons);

        parent::__construct($message ?: $this->userMessage(), 0, $previous);
    }

    /**
     * @return array<int, string>
     */
    public function providers(): array
    {
        return $this->providers;
    }

    /**
     * @return array<int, string>
     */
    public function attemptedProviders(): array
    {
        return $this->attemptedProviders;
    }

    public function providerCount(): int
    {
        return count($this->providers);
    }

    public function attemptedProviderCount(): int
    {
        return count($this->attemptedProviders);
    }

    /**
     * Return safe, non-secret failure categories collected during the provider
     * cascade. This is intentionally limited to diagnostics that are useful to
     * the UI and deployment logs; provider response bodies and credentials are
     * never exposed here.
     *
     * @return array<string, array<int, string>>
     */
    public function failureReasons(): array
    {
        return $this->failureReasons;
    }

    public function failureCode(): string
    {
        if ($this->providerCount() === 0) {
            return 'no_provider_credentials';
        }

        $reasons = [];
        foreach ($this->failureReasons as $providerReasons) {
            $reasons = array_merge($reasons, $providerReasons);
        }
        $reasons = array_values(array_unique($reasons));
        $priority = [
            'authentication' => 'provider_authentication',
            'model_not_found' => 'provider_model',
            'rate_limited' => 'provider_rate_limit',
            'timeout' => 'provider_timeout',
            'network' => 'provider_network',
            'circuit_open' => 'provider_circuit_open',
            'validation' => 'provider_response_rejected',
            'invalid_response' => 'provider_invalid_response',
            'provider_server' => 'provider_server_error',
            'request_rejected' => 'provider_request_rejected',
        ];

        foreach ($priority as $reason => $code) {
            if (in_array($reason, $reasons, true)) {
                return $code;
            }
        }

        return 'ai_feedback_providers_failed';
    }

    public function userMessage(): string
    {
        $count = $this->providerCount();

        if ($count <= 0) {
            return 'AI feedback is not configured on this server. Add at least one provider API key in Dokploy, redeploy, and retry the report. Your answers were saved.';
        }

        return match ($this->failureCode()) {
            'provider_authentication' => "The configured AI feedback provider credentials were rejected. Check the API keys in Dokploy, redeploy, and retry. Your answers were saved.",
            'provider_model' => "The configured AI feedback model was rejected by the provider. Check each provider model name in Dokploy, redeploy, and retry. Your answers were saved.",
            'provider_rate_limit' => "The AI feedback providers are rate-limited right now. Wait briefly and retry the report. Your answers were saved.",
            'provider_timeout', 'provider_network' => "The AI feedback providers did not finish before the server deadline. Retry the report; your answers were saved.",
            'provider_circuit_open' => "The AI feedback provider circuit is cooling down after repeated failures. Wait briefly and retry the report. Your answers were saved.",
            'provider_response_rejected', 'provider_invalid_response' => "The AI feedback providers returned a response that did not pass the saved-answer checks. Retry the report so another provider attempt can complete it. Your answers were saved.",
            default => "All {$count} configured AI feedback providers failed to return a verified report. Retry the report; your answers were saved.",
        };
    }

    /**
     * @param  array<int, mixed>  $providers
     * @return array<int, string>
     */
    private function cleanProviderList(array $providers): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn ($provider): string => trim((string) $provider),
            $providers
        ))));
    }

    /**
     * @param  array<string, array<int, string>|string>  $reasons
     * @return array<string, array<int, string>>
     */
    private function cleanFailureReasons(array $reasons): array
    {
        $clean = [];
        foreach ($reasons as $provider => $providerReasons) {
            $provider = trim((string) $provider);
            if ($provider === '') {
                continue;
            }

            $values = is_array($providerReasons) ? $providerReasons : [$providerReasons];
            $values = array_values(array_unique(array_filter(array_map(
                static fn ($reason): string => trim((string) $reason),
                $values
            ))));
            if ($values !== []) {
                $clean[$provider] = $values;
            }
        }

        return $clean;
    }
}
