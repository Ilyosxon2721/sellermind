<?php

namespace App\Exceptions;

use RuntimeException;

class RateLimitException extends RuntimeException
{
    protected int $retryAfter;

    public function __construct(string $message = 'Rate limit exceeded', int $code = 429, int $retryAfter = 60)
    {
        parent::__construct($message, $code);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Get user-friendly message for display
     */
    public function getUserMessage(): string
    {
        return $this->getMessage();
    }
}
