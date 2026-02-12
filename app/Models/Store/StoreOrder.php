<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreOrder extends Model
{
    // ==================
    // Константы статусов заказа
    // ==================

    const STATUS_NEW = 'new';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_PROCESSING = 'processing';

    const STATUS_SHIPPED = 'shipped';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_CANCELLED = 'cancelled';

    // ==================
    // Константы статусов оплаты
    // ==================

    const PAYMENT_PENDING = 'pending';

    const PAYMENT_PAID = 'paid';

    const PAYMENT_FAILED = 'failed';

    const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'store_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_method_id',
        'delivery_address',
        'delivery_city',
        'delivery_comment',
        'delivery_price',
        'payment_method_id',
        'payment_status',
        'payment_id',
        'subtotal',
        'discount',
        'total',
        'status',
        'customer_note',
        'admin_note',
        'sellermind_order_id',
    ];

    protected $casts = [
        'delivery_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'SM-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -5));
            }
        });
    }

    // ==================
    // Relationships
    // ==================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(StoreDeliveryMethod::class, 'delivery_method_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(StorePaymentMethod::class, 'payment_method_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StoreOrderItem::class, 'order_id');
    }
}
