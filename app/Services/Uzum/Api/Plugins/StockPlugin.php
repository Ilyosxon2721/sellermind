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
     * Uzum требует fbsLinked/dbsLinked для прохождения schema validation
     */
    public function updateOne(int $skuId, int $amount, ?string $barcode = null, bool $fbs = true, bool $dbs = false): array
    {
        $item = [
            'skuId' => $skuId,
            'amount' => $amount,
            'fbsLinked' => $fbs,
            'dbsLinked' => $dbs,
        ];
        if ($barcode) {
            $item['barcode'] = $barcode;
        }

        return $this->update([$item]);
    }
}
