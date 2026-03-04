<?php

namespace App\Models\Store;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Store extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'logo',
        'favicon',
        'custom_domain',
        'domain_verified',
        'ssl_enabled',
        'is_active',
        'is_published',
        'maintenance_mode',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'phone',
        'email',
        'address',
        'working_hours',
        'instagram',
        'telegram',
        'facebook',
        'currency',
        'min_order_amount',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'domain_verified' => 'boolean',
        'ssl_enabled' => 'boolean',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'maintenance_mode' => 'boolean',
        'min_order_amount' => 'decimal:2',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            if (empty($store->slug)) {
                $store->slug = Str::slug($store->name);
            }

            // Ensure unique slug
            $originalSlug = $store->slug;
            $count = 1;
            while (static::where('slug', $store->slug)->exists()) {
                $store->slug = $originalSlug.'-'.$count++;
            }
        });

        // Create default theme when store is created
        static::created(function ($store) {
            $store->theme()->create([
                'template' => 'default',
                'hero_title' => $store->name,
                'hero_subtitle' => $store->description,
            ]);
        });
    }

    /**
     * Get the URL for the store
     */
    public function getUrlAttribute(): string
    {
        if ($this->custom_domain && $this->domain_verified) {
            $protocol = $this->ssl_enabled ? 'https' : 'http';

            return "{$protocol}://{$this->custom_domain}";
        }

        return config('app.url').'/store/'.$this->slug;
    }

    /**
     * Check if store is accessible
     */
    public function isAccessible(): bool
    {
        return $this->is_active && $this->is_published && ! $this->maintenance_mode;
    }

    // ==================
    // Relationships
    // ==================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function theme(): HasOne
    {
        return $this->hasOne(StoreTheme::class);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(StoreBanner::class)->orderBy('position');
    }

    public function activeBanners(): HasMany
    {
        return $this->hasMany(StoreBanner::class)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('position');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(StoreCategory::class)->orderBy('position');
    }

    public function visibleCategories(): HasMany
    {
        return $this->hasMany(StoreCategory::class)
            ->where('is_visible', true)
            ->orderBy('position');
    }

    public function products(): HasMany
    {
        return $this->hasMany(StoreProduct::class)->orderBy('position');
    }

    public function visibleProducts(): HasMany
    {
        return $this->hasMany(StoreProduct::class)
            ->where('is_visible', true)
            ->orderBy('position');
    }

    public function featuredProducts(): HasMany
    {
        return $this->hasMany(StoreProduct::class)
            ->where('is_visible', true)
            ->where('is_featured', true)
            ->orderBy('position');
    }

    public function deliveryMethods(): HasMany
    {
        return $this->hasMany(StoreDeliveryMethod::class)->orderBy('position');
    }

    public function activeDeliveryMethods(): HasMany
    {
        return $this->hasMany(StoreDeliveryMethod::class)
            ->where('is_active', true)
            ->orderBy('position');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(StorePaymentMethod::class)->orderBy('position');
    }

    public function activePaymentMethods(): HasMany
    {
        return $this->hasMany(StorePaymentMethod::class)
            ->where('is_active', true)
            ->orderBy('position');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class)->latest();
    }

    public function pages(): HasMany
    {
        return $this->hasMany(StorePage::class)->orderBy('position');
    }

    public function activePages(): HasMany
    {
        return $this->hasMany(StorePage::class)
            ->where('is_active', true)
            ->orderBy('position');
    }

    public function promocodes(): HasMany
    {
        return $this->hasMany(StorePromocode::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(StoreAnalytics::class);
    }
}
