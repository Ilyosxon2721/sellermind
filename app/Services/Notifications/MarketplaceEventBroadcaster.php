<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Events\MarketplaceEventReceived;
use App\Models\MarketplaceEvent;
use Illuminate\Support\Facades\Log;

final class MarketplaceEventBroadcaster
{
    /**
     * Отправить событие через WebSocket всем пользователям компании
     */
    public function broadcast(MarketplaceEvent $event): void
    {
        try {
            $account = $event->store;
            if (! $account) {
                return;
            }

            $companyId = $account->company_id ?? null;
            if (! $companyId) {
                return;
            }

            broadcast(new MarketplaceEventReceived($event, $companyId));
        } catch (\Throwable $e) {
            // Не падаем если WebSocket недоступен
            Log::warning('WebSocket broadcast failed', [
                'uuid' => $event->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
