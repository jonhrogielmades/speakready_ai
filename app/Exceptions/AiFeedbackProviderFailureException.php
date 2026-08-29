<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class AiFeedbackProviderFailureException extends RuntimeException
{
    /**
     * @param  array<int, string>  $providers
     * @param  array<int, string>  $attemptedProviders
     */
    public function __construct(
        private array $providers,
        private array $attemptedProviders = [],
        ?string $message = null,
        ?Throwable $previous = null
    ) {
        $this->providers = $this->cleanProviderList($providers);
        $this->attemptedProviders = $this->cleanProviderList($attemptedProviders);

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

    public function userMessage(): string
    {
        $count = $this->providerCount();

        if ($count <= 0) {
            return 'No configured AI feedback provider is available. Your answers were saved, but no AI feedback report was created.';
        }

        return "All {$count} configured AI feedback providers failed, timed out, or returned invalid feedback. Your answers were saved, but no AI feedback report was created. Please retry after checking the AI provider connections.";
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
}
