<?php

namespace App\Telegram;

class TelegramRateLimitException extends \RuntimeException
{
    public function __construct(
        public readonly int $retryAfter,
    ) {
        parent::__construct("Telegram rate limit: retry after {$retryAfter}s");
    }
}
