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
        $syncMode = $account->credentials_json['sync_mode'] ?? 'basic';
        
        // Get variant SKU
        $variantSku = $this->variant?->sku;
        if (!$variantSku) {
            return $this->variant?->stock_default ?? 0;
        }
        
        // Get SKU ID
        $skuId = \DB::table('skus')
            ->where('company_id', $companyId)
            ->where('sku_code', $variantSku)
            ->value('id');
        
        if (!$skuId) {
            return $this->variant?->stock_default ?? 0;
        }
        
        if ($syncMode === 'aggregated') {
            // Суммированный режим: сумма с выбранных складов
            return $this->getAggregatedStock($companyId, $skuId, $account);
        } else {
            // Базовый режим: остаток с конкретного склада (для 1:1 маппинга)
            // В этом режиме остаток берётся из склада, который замаплен на WB склад
            // Это будет определяться в WildberriesStockService
            return $this->getBasicStock($companyId, $skuId);
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
        $reservedQty = \DB::table('stock_reservations')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId)
            ->whereIn('warehouse_id', $sourceWarehouseIds)
            ->whereIn('status', ['pending', 'confirmed'])
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
     * Возвращает общий остаток, конкретный склад определяется при синхронизации
     */
    protected function getBasicStock($companyId, $skuId): int
    {
        // В базовом режиме возвращаем общий остаток
        // Конкретный склад будет определён в WildberriesStockService при синхронизации
        $totalStock = \DB::table('stock_ledger')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId)
            ->sum('qty_delta');
        
        $reservedQty = \DB::table('stock_reservations')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('qty');
        
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
