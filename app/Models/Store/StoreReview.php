<?php

declare(strict_types=1);

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Отзыв покупателя на товар витрины
 */
class StoreReview extends Model
{
    protected $fillable = [
        'store_id',
        'store_product_id',
        'store_order_id',
        'author_name',
        'author_email',
        'author_phone',
        'rating',
        'text',
        'pros',
        'cons',
        'is_approved',
        'is_featured',
        'admin_reply',
        'admin_replied_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'admin_replied_at' => 'datetime',
    ];

    // ==================
    // Relationships
    // ==================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function storeProduct(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(StoreOrder::class, 'store_order_id');
    }
}
