<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\MarketplacePollingState;

final class PollingLockManager
{
    /**
     * Захватить блокировку. Возвращает false если уже заблокировано.
     */
    public function acquire(MarketplacePollingState $state): bool
    {
        // Освободить устаревшую блокировку
        if ($state->isStale()) {
            $this->release($state);
        }

        if ($state->is_locked) {
            return false;
        }

        $state->update([
            'is_locked' => true,
            'locked_at' => now(),
        ]);

        return true;
    }

    /**
     * Освободить блокировку
     */
    public function release(MarketplacePollingState $state): void
    {
        $state->update([
            'is_locked' => false,
            'locked_at' => null,
        ]);
    }
}
