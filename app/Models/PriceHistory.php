<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * История изменений цен товарных вариантов
 *
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property string $channel
 * @property float $price
 * @property float|null $old_price
 * @property int|null $changed_by
 * @property \Carbon\Carbon $changed_at
 */
final class PriceHistory extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'product_variant_id',
        'channel',
        'price',
        'old_price',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'changed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Записать изменение цены варианта
     */
    public static function record(
        ProductVariant $variant,
        float $newPrice,
        ?float $oldPrice,
        string $channel = 'default',
        ?int $changedBy = null
    ): self {
        return self::create([
            'company_id' => $variant->company_id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'channel' => $channel,
            'price' => $newPrice,
            'old_price' => $oldPrice,
            'changed_by' => $changedBy ?? auth()->id(),
            'changed_at' => now(),
        ]);
    }
}
