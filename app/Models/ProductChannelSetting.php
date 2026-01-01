<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int $channel_id
 * @property string|null $external_product_id
 * @property string|null $category_external_id
 * @property string|null $name_override
 * @property string|null $description_override
 * @property string|null $brand_external_id
 * @property string|null $brand_external_name
 * @property bool $is_enabled
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property array|null $extra
 * @property-read Product $product
 * @property-read Channel $channel
 */
class ProductChannelSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'channel_id',
        'external_product_id',
        'category_external_id',
        'name_override',
        'description_override',
        'brand_external_id',
        'brand_external_name',
        'is_enabled',
        'status',
        'last_synced_at',
        'last_sync_status_message',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
