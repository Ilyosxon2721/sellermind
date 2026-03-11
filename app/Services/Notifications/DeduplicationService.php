<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Enums\MarketplaceType;
use App\Models\MarketplaceEvent;
use Illuminate\Support\Facades\Cache;

final class DeduplicationService
{
    private int $cacheTtlMinutes = 10;

    /**
     * Проверить является ли событие дубликатом
     */
    public function isDuplicate(MarketplaceType $marketplace, string $externalId): bool
    {
        $cacheKey = $this->cacheKey($marketplace, $externalId);

        // Быстрая проверка через Redis
        if (Cache::has($cacheKey)) {
            return true;
        }

        // Медленная проверка через БД
        $exists = MarketplaceEvent::where('marketplace', $marketplace->value)
            ->where('external_id', $externalId)
            ->exists();

        if ($exists) {
            Cache::put($cacheKey, true, now()->addMinutes($this->cacheTtlMinutes));

            return true;
        }

        return false;
    }

    /**
     * Пометить событие как обработанное (кэш)
     */
    public function markProcessed(MarketplaceType $marketplace, string $externalId): void
    {
        Cache::put(
            $this->cacheKey($marketplace, $externalId),
            true,
            now()->addMinutes($this->cacheTtlMinutes)
        );
    }

    private function cacheKey(MarketplaceType $marketplace, string $externalId): string
    {
        return "dedup:{$marketplace->value}:{$externalId}";
    }
}
