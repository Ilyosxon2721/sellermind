<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfflineSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'counterparty_id',
        'warehouse_id',
        'sale_number',
        'sale_type',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'subtotal',
        'discount_amount',
        'total_amount',
        'currency_code',
        'payment_status',
        'paid_amount',
        'payment_method',
        'sale_date',
        'shipped_date',
        'delivered_date',
        'stock_status',
        'stock_reserved_at',
        'stock_sold_at',
        'stock_released_at',
        'notes',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'sale_date' => 'date',
            'shipped_date' => 'date',
            'delivered_date' => 'date',
            'stock_reserved_at' => 'datetime',
            'stock_sold_at' => 'datetime',
            'stock_released_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    // Sale types
    public const TYPE_RETAIL = 'retail';

    public const TYPE_WHOLESALE = 'wholesale';

    public const TYPE_DIRECT = 'direct';

    // Statuses
    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_RETURNED = 'returned';

    // Payment statuses
    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_PAID = 'paid';

    // ========== Relationships ==========

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfflineSaleItem::class);
    }

    // ========== Scopes ==========

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('sale_date', [$from, $to]);
    }

    public function scopeSold($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeReturned($query)
    {
        return $query->where('status', self::STATUS_RETURNED);
    }

    public function scopeRetail($query)
    {
        return $query->where('sale_type', self::TYPE_RETAIL);
    }

    public function scopeWholesale($query)
    {
        return $query->where('sale_type', self::TYPE_WHOLESALE);
    }

    public function scopeDirect($query)
    {
        return $query->where('sale_type', self::TYPE_DIRECT);
    }

    // ========== Helpers ==========

    public function isSold(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_CONFIRMED => 'Подтверждён',
            self::STATUS_SHIPPED => 'Отгружен',
            self::STATUS_DELIVERED => 'Доставлен',
            self::STATUS_CANCELLED => 'Отменён',
            self::STATUS_RETURNED => 'Возвращён',
            default => $this->status ?? 'Неизвестен',
        };
    }

    public function getSaleTypeLabel(): string
    {
        return match ($this->sale_type) {
            self::TYPE_RETAIL => 'Розница',
            self::TYPE_WHOLESALE => 'Опт',
            self::TYPE_DIRECT => 'Прямая продажа',
            default => $this->sale_type ?? 'Неизвестен',
        };
    }

    public function getPaymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_UNPAID => 'Не оплачен',
            self::PAYMENT_PARTIAL => 'Частично оплачен',
            self::PAYMENT_PAID => 'Оплачен',
            default => $this->payment_status ?? 'Неизвестен',
        };
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('line_total');
        $this->subtotal = $subtotal;
        $this->total_amount = $subtotal - $this->discount_amount;
        $this->save();
    }

    public function getItemsCount(): int
    {
        return (int) $this->items()->sum('quantity');
    }
}
