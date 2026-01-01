<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_variant_id
 * @property int $channel_id
 * @property string|null $external_offer_id
 * @property float|null $price
 * @property float|null $old_price
 * @property int|null $stock
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property array|null $extra
 * @property-read ProductVariant $variant
 * @property-read Channel $channel
 */
class ProductChannelVariantSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_variant_id',
        'channel_id',
        'external_offer_id',
        'price',
        'old_price',
        'stock',
        'status',
        'last_synced_at',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'stock' => 'integer',
            'last_synced_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
