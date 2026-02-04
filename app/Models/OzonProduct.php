<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OzonProduct extends Model
{
    protected $table = 'ozon_products';

    protected $fillable = [
        'marketplace_account_id',
        'product_id',
        'external_product_id',
        'external_offer_id',
        'barcode',
        'name',
        'category_id',
        'price',
        'old_price',
        'premium_price',
        'stock',
        'status',
        'images',
        'attributes',
        'description',
        'vat',
        'width',
        'height',
        'depth',
        'weight',
        'visible',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'attributes' => 'array',
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'premium_price' => 'decimal:2',
            'stock' => 'integer',
            'category_id' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'depth' => 'integer',
            'weight' => 'integer',
            'visible' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the marketplace account
     */
    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Get the local product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get variant marketplace links for this Ozon product
     */
    public function variantLinks(): HasMany
    {
        return $this->hasMany(VariantMarketplaceLink::class, 'marketplace_product_id')
            ->where('marketplace_code', 'ozon');
    }

    /**
     * Get the primary image URL
     */
    public function getPrimaryImageAttribute(): ?string
    {
        $images = $this->images;

        // If images is still a string (shouldn't happen with cast, but just in case)
        if (is_string($images)) {
            $images = json_decode($images, true);
        }

        if (! is_array($images) || empty($images)) {
            return null;
        }

        return $images[0] ?? null;
    }

    /**
     * Check if product is linked to a local product/variant
     */
    public function isLinked(): bool
    {
        return $this->variantLinks()->where('is_active', true)->exists();
    }

    /**
     * Get linked variants
     */
    public function getLinkedVariants()
    {
        return $this->variantLinks()
            ->where('is_active', true)
            ->with('variant')
            ->get()
            ->pluck('variant');
    }

    /**
     * Search products by query
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('external_offer_id', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%")
                ->orWhere('external_product_id', 'like', "%{$search}%");
        });
    }

    /**
     * Filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filter by visibility
     */
    public function scopeVisible($query, bool $visible = true)
    {
        return $query->where('visible', $visible);
    }

    /**
     * Only linked products
     */
    public function scopeLinked($query)
    {
        return $query->whereHas('variantLinks', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Only unlinked products
     */
    public function scopeUnlinked($query)
    {
        return $query->whereDoesntHave('variantLinks', function ($q) {
            $q->where('is_active', true);
        });
    }
}
