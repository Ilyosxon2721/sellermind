<?php

// file: app/Models/MarketplaceCategoryMapping.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceCategoryMapping extends Model
{
    protected $fillable = [
        'marketplace',
        'external_category_id',
        'external_category_name',
        'internal_category_id',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'extra' => 'array',
        ];
    }

    public function internalCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'internal_category_id');
    }

    /**
     * Find mapping by marketplace and external category ID
     */
    public static function findMapping(string $marketplace, string $externalCategoryId): ?self
    {
        return self::where('marketplace', $marketplace)
            ->where('external_category_id', $externalCategoryId)
            ->first();
    }

    /**
     * Get all mappings for a marketplace
     */
    public static function getForMarketplace(string $marketplace): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('marketplace', $marketplace)->get();
    }
}
