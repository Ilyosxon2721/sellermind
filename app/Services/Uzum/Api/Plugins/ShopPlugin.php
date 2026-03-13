<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api\Plugins;

use App\Services\Uzum\Api\UzumApi;
use App\Services\Uzum\Api\UzumEndpoints;

/**
 * Плагин: Магазины
 */
final class ShopPlugin
{
    public function __construct(
        private readonly UzumApi $api,
    ) {}

    /**
     * Получить список магазинов продавца
     */
    public function list(): array
    {
        $response = $this->api->cachedCall(UzumEndpoints::SHOPS_LIST, ttl: 900);

        return $response['payload'] ?? $response;
    }

    /**
     * Получить ID всех магазинов
     */
    public function ids(): array
    {
        $shops = $this->list();
        $ids = [];
        foreach ($shops as $shop) {
            if (isset($shop['id'])) {
                $ids[] = (int) $shop['id'];
            }
        }

        // Также проверить shop_id из аккаунта
        $accountShopId = $this->api->getAccount()->shop_id;
        if ($accountShopId && ! in_array((int) $accountShopId, $ids, true)) {
            array_unshift($ids, (int) $accountShopId);
        }

        return $ids;
    }
}
