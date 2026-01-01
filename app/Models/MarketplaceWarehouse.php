<?php
// file: app/Models/MarketplaceWarehouse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\Marketplaces\Wildberries\WildberriesStockService;
use App\Models\Warehouse\Warehouse;

class MarketplaceWarehouse extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'wildberries_warehouse_id',
        'marketplace_warehouse_id', // ID from marketplace API (e.g., WB /api/v3/warehouses)
        'local_warehouse_id',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'wildberries_warehouse_id' => 'integer',
        'marketplace_warehouse_id' => 'integer',
        'local_warehouse_id' => 'integer',
        'is_active' => 'boolean',
    ];

    // ========== Relationships ==========

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function localWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'local_warehouse_id');
    }

    // ========== Static Methods ==========

    /**
     * Get available WB warehouses for mapping (from Marketplace API)
     * 
     * @param MarketplaceAccount $account
     * @return array
     */
    public static function getAvailableWbWarehouses(MarketplaceAccount $account): array
    {
        if ($account->marketplace !== 'wb') {
            return [];
        }

        try {
            $stockService = app(WildberriesStockService::class, ['httpClient' => app(\App\Services\Marketplaces\Wildberries\WildberriesHttpClient::class, ['account' => $account])]);
            return $stockService->getWarehouses($account);
        } catch (\Exception $e) {
            \Log::error('Failed to get WB warehouses', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Sync WB warehouses from Marketplace API
     * Creates or updates warehouse mappings with marketplace_warehouse_id
     * 
     * @param MarketplaceAccount $account
     * @return array
     */
    public static function syncFromMarketplace(MarketplaceAccount $account): array
    {
        if ($account->marketplace !== 'wb') {
            return ['synced' => 0, 'errors' => ['Account is not a Wildberries account']];
        }

        $synced = 0;
        $errors = [];

        try {
            $warehouses = self::getAvailableWbWarehouses($account);

            foreach ($warehouses as $whData) {
                try {
                    $warehouseId = $whData['id'] ?? null;
                    $name = $whData['name'] ?? 'Unknown';

                    if (!$warehouseId) {
                        continue;
                    }

                    self::updateOrCreate(
                        [
                            'marketplace_account_id' => $account->id,
                            'marketplace_warehouse_id' => $warehouseId,
                        ],
                        [
                            'name' => $name,
                            'type' => self::getWarehouseType($whData['deliveryType'] ?? null),
                            'is_active' => true,
                        ]
                    );

                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = "Error syncing warehouse {$name}: " . $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Failed to sync warehouses: ' . $e->getMessage();
        }

        return [
            'synced' => $synced,
            'errors' => $errors,
        ];
    }

    /**
     * Get warehouse type from WB deliveryType
     */
    protected static function getWarehouseType(?int $deliveryType): string
    {
        return match ($deliveryType) {
            1 => 'FBS',
            2 => 'DBS',
            6 => 'EDBS',
            5 => 'C&C',
            default => 'FBS',
        };
    }

    /**
     * Get mapped marketplace warehouse ID for a local warehouse
     */
    public static function getMappedWarehouseId(MarketplaceAccount $account, ?int $localWarehouseId): ?int
    {
        if (!$localWarehouseId) {
            return null;
        }

        $mapping = self::where('marketplace_account_id', $account->id)
            ->where('local_warehouse_id', $localWarehouseId)
            ->where('is_active', true)
            ->first();

        return $mapping?->marketplace_warehouse_id;
    }
}
