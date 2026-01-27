<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Связь между вариантом товара и карточкой на маркетплейсе
 * 
 * @property int $id
 * @property int $company_id
 * @property int $product_variant_id
 * @property int $marketplace_product_id
 * @property int $marketplace_account_id
 * @property string|null $external_offer_id
 * @property string|null $external_sku
 * @property bool $is_active
 * @property bool $sync_stock_enabled
 * @property bool $sync_price_enabled
 * @property int|null $last_stock_synced
 * @property float|null $last_price_synced
 * @property \Carbon\Carbon|null $last_synced_at
 * @property string|null $last_sync_status
 * @property string|null $last_sync_error
 */
class VariantMarketplaceLink extends Model
{
    protected $fillable = [
        'company_id',
        'product_variant_id',
        'marketplace_product_id',
        'marketplace_account_id',
        'marketplace_code',
        'external_offer_id',
        'external_sku_id',
        'external_sku',
        'marketplace_barcode', // Баркод товара на маркетплейсе (может отличаться от внутреннего)
        'is_active',
        'sync_stock_enabled',
        'sync_price_enabled',
        'last_stock_synced',
        'last_price_synced',
        'last_synced_at',
        'last_sync_status',
        'last_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sync_stock_enabled' => 'boolean',
            'sync_price_enabled' => 'boolean',
            'last_stock_synced' => 'integer',
            'last_price_synced' => 'decimal:2',
            'last_synced_at' => 'datetime',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function marketplaceProduct(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Получить текущий остаток в зависимости от режима синхронизации
     * Поддержка базового (1:1) и суммированного режимов
     */
    public function getCurrentStock(): int
    {
        $account = $this->account;
        $companyId = $account->company_id ?? null;

        if (!$companyId) {
            return $this->variant?->stock_default ?? 0;
        }

        // Get sync mode from account settings
        $syncMode = $account->credentials_json['stock_sync_mode'] ?? $account->credentials_json['sync_mode'] ?? 'basic';

        // Get variant SKU
        $variant = $this->variant;
        $variantSku = $variant?->sku;
        if (!$variantSku) {
            return $variant?->stock_default ?? 0;
        }

        // Try to find SKU in warehouse system
        $skuId = \DB::table('skus')
            ->where('company_id', $companyId)
            ->where('sku_code', $variantSku)
            ->value('id');

        // If no SKU in warehouse system, try by barcode
        if (!$skuId && $variant?->barcode) {
            $skuId = \DB::table('skus')
                ->where('company_id', $companyId)
                ->where('barcode_ean13', $variant->barcode)
                ->value('id');
        }

        // If still no SKU, fallback to stock_default
        if (!$skuId) {
            \Log::debug('No SKU in warehouse system, using stock_default', [
                'variant_sku' => $variantSku,
                'variant_id' => $variant?->id,
                'stock_default' => $variant?->stock_default,
            ]);
            return $variant?->stock_default ?? 0;
        }

        if ($syncMode === 'aggregated') {
            // Суммированный режим: сумма с выбранных складов
            return $this->getAggregatedStock($companyId, $skuId, $account);
        } else {
            // Базовый режим: остаток со склада из настроек или общий
            // ВАЖНО: local_warehouse_id - это ЛОКАЛЬНЫЙ склад для расчёта остатков
            // warehouse_id - это склад МАРКЕТПЛЕЙСА для API (напр. Ozon warehouse ID)
            // Не путать! Для локального расчёта используем local_warehouse_id
            $localWarehouseId = $account->credentials_json['local_warehouse_id'] ?? null;

            // Проверяем что local_warehouse_id существует в наших складах
            if ($localWarehouseId) {
                $exists = \DB::table('warehouses')
                    ->where('id', $localWarehouseId)
                    ->where('company_id', $companyId)
                    ->exists();
                if (!$exists) {
                    $localWarehouseId = null;
                }
            }

            return $this->getBasicStock($companyId, $skuId, $localWarehouseId);
        }
    }
    
    /**
     * Получить суммированный остаток с выбранных складов (aggregated mode)
     */
    protected function getAggregatedStock($companyId, $skuId, $account): int
    {
        $sourceWarehouseIds = $account->credentials_json['source_warehouse_ids'] ?? [];
        
        if (empty($sourceWarehouseIds)) {
            // Если склады не выбраны, берём со всех
            \Log::warning('Aggregated mode but no source warehouses selected', [
                'account_id' => $account->id
            ]);
            return $this->variant?->stock_default ?? 0;
        }
        
        // Sum stock from selected warehouses
        $totalStock = \DB::table('stock_ledger')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId)
            ->whereIn('warehouse_id', $sourceWarehouseIds)
            ->sum('qty_delta');
        
        // Subtract reservations from selected warehouses
        // Status ACTIVE = active reservation (pending/confirmed are legacy)
        $reservedQty = \DB::table('stock_reservations')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId)
            ->whereIn('warehouse_id', $sourceWarehouseIds)
            ->whereIn('status', ['ACTIVE', 'pending', 'confirmed'])
            ->sum('qty');
        
        $availableStock = max(0, $totalStock - $reservedQty);
        
        \Log::debug('Aggregated stock calculated', [
            'sku' => $this->variant?->sku,
            'source_warehouses' => $sourceWarehouseIds,
            'total' => $totalStock,
            'reserved' => $reservedQty,
            'available' => $availableStock
        ]);
        
        return (int) $availableStock;
    }
    
    /**
     * Получить остаток для базового режима (basic mode)
     * Если указан warehouseId - берёт с конкретного склада, иначе общий
     */
    protected function getBasicStock($companyId, $skuId, $warehouseId = null): int
    {
        $query = \DB::table('stock_ledger')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId);

        // Status ACTIVE = active reservation (pending/confirmed are legacy)
        $reserveQuery = \DB::table('stock_reservations')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId)
            ->whereIn('status', ['ACTIVE', 'pending', 'confirmed']);

        // Если указан конкретный склад
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
            $reserveQuery->where('warehouse_id', $warehouseId);
        }

        $totalStock = $query->sum('qty_delta');
        $reservedQty = $reserveQuery->sum('qty');

        return (int) max(0, $totalStock - $reservedQty);
    }

    /**
     * Отметить успешную синхронизацию
     */
    public function markSynced(int $stock, ?float $price = null): void
    {
        $this->update([
            'last_stock_synced' => $stock,
            'last_price_synced' => $price,
            'last_synced_at' => now(),
            'last_sync_status' => 'success',
            'last_sync_error' => null,
        ]);
    }

    /**
     * Отметить ошибку синхронизации
     */
    public function markFailed(string $error): void
    {
        $this->update([
            'last_synced_at' => now(),
            'last_sync_status' => 'error',
            'last_sync_error' => $error,
        ]);
    }

    /**
     * Нужна ли синхронизация?
     */
    public function needsSync(): bool
    {
        if (!$this->is_active || !$this->sync_stock_enabled) {
            return false;
        }

        return $this->last_stock_synced !== $this->getCurrentStock();
    }

    /**
     * Scope: только активные связи
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: только с включённой синхронизацией остатков
     */
    public function scopeWithStockSync($query)
    {
        return $query->where('sync_stock_enabled', true);
    }

    /**
     * Scope: для компании
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: для аккаунта маркетплейса
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }
}
