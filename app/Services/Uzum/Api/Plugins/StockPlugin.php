<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api\Plugins;

use App\Services\Uzum\Api\UzumApi;
use App\Services\Uzum\Api\UzumEndpoints;

/**
 * Плагин: Остатки FBS/DBS
 */
final class StockPlugin
{
    public function __construct(
        private readonly UzumApi $api,
    ) {}

    /**
     * Получить текущие остатки
     */
    public function get(): array
    {
        return $this->api->cachedCall(UzumEndpoints::FBS_STOCKS_GET, ttl: 300);
    }

    /**
     * Обновить остатки (массово)
     *
     * @param  array  $items  [['skuId' => int, 'amount' => int, 'barcode' => string|null], ...]
     */
    public function update(array $items): array
    {
        return $this->api->call(
            UzumEndpoints::FBS_STOCKS_UPDATE,
            body: ['skuAmountList' => $items],
        );
    }

    /**
     * Обновить остаток одного SKU
     * Uzum требует ВСЕ поля: skuId, skuTitle, productTitle, barcode, amount, fbsLinked, dbsLinked
     */
    public function updateOne(
        int $skuId,
        int $amount,
        string $barcode,
        string $skuTitle,
        string $productTitle,
        bool $fbs = true,
        bool $dbs = false,
    ): array {
        return $this->update([[
            'skuId' => $skuId,
            'skuTitle' => $skuTitle,
            'productTitle' => $productTitle,
            'barcode' => $barcode,
            'amount' => $amount,
            'fbsLinked' => $fbs,
            'dbsLinked' => $dbs,
        ]]);
    }
}
