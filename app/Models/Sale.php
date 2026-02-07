<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель продажи
 *
 * @property int $id
 * @property int $company_id
 * @property string $sale_number
 * @property string $type
 * @property string|null $source
 * @property int|null $counterparty_id
 * @property string|null $marketplace_order_type
 * @property int|null $marketplace_order_id
 * @property float $subtotal
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $total_amount
 * @property string $currency
 * @property string $status
 * @property \Carbon\Carbon|null $confirmed_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property string|null $notes
 * @property array|null $metadata
 * @property int|null $created_by
 * @property int|null $confirmed_by
 */
class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'sale_number',
        'type',
        'source',
        'counterparty_id',
        'warehouse_id',
        'marketplace_order_type',
        'marketplace_order_id',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'notes',
        'metadata',
        'created_by',
        'confirmed_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Убрано из $appends — вычисляется в контроллере/API Resource при необходимости
    // protected $appends = ['status_label', 'type_label'];

    /**
     * Relationships
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse\Warehouse::class);
    }

    /**
     * Polymorphic relation to marketplace order
     */
    public function marketplaceOrder(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'marketplace_order_type', 'marketplace_order_id');
    }

    /**
     * Scopes
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeManual($query)
    {
        return $query->where('type', 'manual');
    }

    public function scopeMarketplace($query)
    {
        return $query->where('type', 'marketplace');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByCounterparty($query, int $counterpartyId)
    {
        return $query->where('counterparty_id', $counterpartyId);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('sale_number', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('counterparty', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Черновик',
            'confirmed' => 'Подтверждена',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена',
            default => $this->status,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'marketplace' => 'С маркетплейса',
            'manual' => 'Ручная',
            'pos' => 'POS',
            default => $this->type,
        };
    }

    /**
     * Business logic methods
     */

    /**
     * Генерация уникального номера продажи
     */
    public static function generateSaleNumber(string $type = 'manual'): string
    {
        $prefix = match ($type) {
            'manual' => 'MAN',
            'pos' => 'POS',
            'marketplace' => 'MP',
            default => 'SALE',
        };

        $date = now()->format('ymd');
        $lastSale = static::where('sale_number', 'like', "{$prefix}-{$date}-%")
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastSale) {
            $parts = explode('-', $lastSale->sale_number);
            $sequence = isset($parts[2]) ? (int) $parts[2] + 1 : 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Подтвердить продажу
     */
    public function confirm(?int $userId = null): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => $userId ?? auth()->id(),
        ]);

        return true;
    }

    /**
     * Завершить продажу
     */
    public function complete(): bool
    {
        if (! in_array($this->status, ['draft', 'confirmed'])) {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Отменить продажу
     */
    public function cancel(): bool
    {
        if ($this->status === 'cancelled') {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        // Вернуть остатки товаров
        foreach ($this->items as $item) {
            if ($item->stock_deducted && $item->product_variant_id) {
                $item->returnStock();
            }
        }

        return true;
    }

    /**
     * Пересчитать итоговые суммы
     */
    public function recalculateTotals(): void
    {
        $subtotal = 0;
        $discountAmount = 0;
        $taxAmount = 0;

        foreach ($this->items as $item) {
            $subtotal += $item->subtotal;
            $discountAmount += $item->discount_amount;
            $taxAmount += $item->tax_amount;
        }

        $this->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal - $discountAmount + $taxAmount,
        ]);
    }

    /**
     * Получить маржу (прибыль)
     */
    public function getMargin(): float
    {
        $totalCost = $this->items->sum(function ($item) {
            return ($item->cost_price ?? 0) * $item->quantity;
        });

        return $this->total_amount - $totalCost;
    }

    /**
     * Получить процент маржи
     */
    public function getMarginPercent(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return ($this->getMargin() / $this->total_amount) * 100;
    }
}
