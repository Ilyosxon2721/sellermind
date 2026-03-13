<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api\Plugins;

use App\Services\Uzum\Api\UzumApi;
use App\Services\Uzum\Api\UzumEndpoints;

/**
 * Плагин: Товары и каталог
 */
final class ProductPlugin
{
    public function __construct(
        private readonly UzumApi $api,
    ) {}

    /**
     * Получить список товаров магазина (одна страница)
     */
    public function list(int $shopId, int $page = 0, int $size = 100, array $filters = []): array
    {
        $query = array_merge(['page' => $page, 'size' => $size], $filters);

        $response = $this->api->call(
            UzumEndpoints::PRODUCT_LIST,
            params: ['shopId' => $shopId],
            query: $query,
        );

        return $response['payload']['productList'] ?? [];
    }

    /**
     * Получить ВСЕ товары магазина (с пагинацией)
     */
    public function all(int $shopId, int $pageSize = 100): array
    {
        return $this->api->paginate(
            UzumEndpoints::PRODUCT_LIST,
            query: [],
            dataKey: 'productList',
            pageSize: $pageSize,
            params: ['shopId' => $shopId],
        );
    }

    /**
     * Обновить цены SKU
     */
    public function updatePrices(array $skuPriceList): array
    {
        return $this->api->call(
            UzumEndpoints::PRODUCT_PRICE_UPDATE,
            body: ['skuPriceList' => $skuPriceList],
        );
    }
}
