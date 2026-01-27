<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Warehouse\Sku as WarehouseSku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;

/**
 * Модель элемента продажи (товарная позиция)
 *
 * @property int $id
 * @property int $sale_id
 * @property int|null $product_variant_id
 * @property string|null $sku
 * @property string $product_name
 * @property string|null $variant_name
 * @property float $quantity
 * @property float $unit_price
 * @property float $discount_percent
 * @property float $discount_amount
 * @property float $tax_percent
 * @property float $tax_amount
 * @property float $subtotal
 * @property float $total
 * @property float|null $cost_price
 * @property bool $stock_deducted
 * @property \Carbon\Carbon|null $stock_deducted_at
 * @property array|null $metadata
 */
class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_variant_id',
        'sku',
        'product_name',
        'variant_name',
        'quantity',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_percent',
        'tax_amount',
        'subtotal',
        'total',
        'cost_price',
        'stock_deducted',
        'stock_deducted_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_deducted' => 'boolean',
        'stock_deducted_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Business logic methods
     */

    /**
     * Рассчитать все суммы для позиции
     */
    public function calculateTotals(): void
    {
        // Подитог (количество * цена)
        $this->subtotal = $this->quantity * $this->unit_price;

        // Скидка
        if ($this->discount_percent > 0) {
            $this->discount_amount = $this->subtotal * ($this->discount_percent / 100);
        }

        // Сумма после скидки
        $amountAfterDiscount = $this->subtotal - $this->discount_amount;

        // Налог
        if ($this->tax_percent > 0) {
            $this->tax_amount = $amountAfterDiscount * ($this->tax_percent / 100);
        }

        // Итоговая сумма
        $this->total = $amountAfterDiscount + $this->tax_amount;
    }

    /**
     * Списать остатки товара со склада
     */
    public function deductStock(): bool
    {
        if ($this->stock_deducted || !$this->product_variant_id) {
            return false;
        }

        $variant = $this->productVariant;
        if (!$variant) {
            return false;
        }

        // Списываем остатки
        $result = $variant->decrementStock((int)$this->quantity);

        if ($result) {
            // Create warehouse stock ledger entry
            $this->createWarehouseStockLedger($variant, -(int)$this->quantity, 'offline_sale');

            $this->update([
                'stock_deducted' => true,
                'stock_deducted_at' => now(),
            ]);

            \Log::info('Stock deducted for sale item', [
                'sale_item_id' => $this->id,
                'sale_id' => $this->sale_id,
                'product_variant_id' => $this->product_variant_id,
                'quantity' => $this->quantity,
            ]);
        }

        return $result;
    }

    /**
     * Вернуть остатки товара на склад (при отмене)
     */
    public function returnStock(): bool
    {
        if (!$this->stock_deducted || !$this->product_variant_id) {
            return false;
        }

        $variant = $this->productVariant;
        if (!$variant) {
            return false;
        }

        // Возвращаем остатки
        $result = $variant->incrementStock((int)$this->quantity);

        if ($result) {
            // Create warehouse stock ledger entry (positive to return stock)
            $this->createWarehouseStockLedger($variant, (int)$this->quantity, 'offline_sale_return');

            $this->update([
                'stock_deducted' => false,
                'stock_deducted_at' => null,
            ]);

            \Log::info('Stock returned for sale item', [
                'sale_item_id' => $this->id,
                'sale_id' => $this->sale_id,
                'product_variant_id' => $this->product_variant_id,
                'quantity' => $this->quantity,
            ]);
        }

        return $result;
    }

    /**
     * Получить маржу (прибыль) по позиции
     */
    public function getMargin(): float
    {
        if (!$this->cost_price) {
            return 0;
        }

        $totalCost = $this->cost_price * $this->quantity;
        return $this->total - $totalCost;
    }

    /**
     * Получить процент маржи
     */
    public function getMarginPercent(): float
    {
        if ($this->total == 0) {
            return 0;
        }

        return ($this->getMargin() / $this->total) * 100;
    }

    /**
     * Create warehouse stock ledger entry for offline sale
     * 
     * @param ProductVariant $variant
     * @param int $qtyDelta Quantity change (negative for sale, positive for return)
     * @param string $sourceType 'offline_sale' or 'offline_sale_return'
     * @return void
     */
    protected function createWarehouseStockLedger(
        ProductVariant $variant,
        int $qtyDelta,
        string $sourceType
    ): void {
        try {
            // Find or create warehouse SKU
            $warehouseSku = WarehouseSku::firstOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'company_id' => $this->sale->company_id,
                ],
                [
                    'product_id' => $variant->product_id,
                    'sku_code' => $variant->sku,
                    'barcode_ean13' => $variant->barcode,
                    'is_active' => true,
                ]
            );

            // Determine warehouse from sale or use default
            $warehouseId = $this->sale->warehouse_id ?? $this->getDefaultWarehouse($this->sale->company_id);

            if (!$warehouseId) {
                \Log::warning('SaleItem: No warehouse found for stock ledger entry', [
                    'sale_item_id' => $this->id,
                    'variant_id' => $variant->id,
                ]);
                return;
            }

            // Create stock ledger entry
            StockLedger::create([
                'company_id' => $this->sale->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $warehouseId,
                'location_id' => null,
                'sku_id' => $warehouseSku->id,
                'qty_delta' => $qtyDelta,
                'cost_delta' => 0,
                'currency_code' => 'UZS',
                'document_id' => null,
                'document_line_id' => null,
                'source_type' => $sourceType,
                'source_id' => $this->id,
                'created_by' => $this->sale->created_by,
            ]);

            \Log::info('SaleItem: Warehouse stock ledger entry created', [
                'sale_item_id' => $this->id,
                'warehouse_sku_id' => $warehouseSku->id,
                'warehouse_id' => $warehouseId,
                'qty_delta' => $qtyDelta,
                'source_type' => $sourceType,
            ]);

        } catch (\Throwable $e) {
            \Log::error('SaleItem: Failed to create warehouse stock ledger', [
                'sale_item_id' => $this->id,
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get default warehouse for company
     * 
     * @param int $companyId
     * @return int|null
     */
    protected function getDefaultWarehouse(int $companyId): ?int
    {
        try {
            $warehouse = Warehouse::where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();

            if ($warehouse) {
                return $warehouse->id;
            }

            // Create default warehouse if none exists
            $warehouse = Warehouse::create([
                'company_id' => $companyId,
                'name' => 'Склад по умолчанию',
                'code' => 'DEFAULT',
                'is_active' => true,
            ]);

            return $warehouse->id;
        } catch (\Throwable $e) {
            \Log::error('SaleItem: Failed to get/create default warehouse', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Создать позицию из ProductVariant
     */
    public static function createFromVariant(
        ProductVariant $variant,
        float $quantity = 1,
        ?float $unitPrice = null,
        float $discountPercent = 0
    ): self {
        $item = new self();
        $item->product_variant_id = $variant->id;
        $item->sku = $variant->sku;
        $item->product_name = $variant->product->name ?? 'Unknown';
        $item->variant_name = $variant->option_values_summary;
        $item->quantity = $quantity;
        $item->unit_price = $unitPrice ?? $variant->price_default ?? 0;
        $item->discount_percent = $discountPercent;
        $item->cost_price = $variant->purchase_price;
        $item->tax_percent = 0; // По умолчанию без налога

        $item->calculateTotals();

        return $item;
    }
}
