<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class AiSpeechTranscriptionException extends RuntimeException
{
    public function __construct(
        string $message,
        private string $provider,
        private ?int $statusCode = null,
        private ?int $retryAfterSeconds = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    public function retryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }

    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }
}
