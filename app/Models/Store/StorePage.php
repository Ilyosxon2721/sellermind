<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StorePage extends Model
{
    protected $fillable = [
        'store_id',
        'title',
        'slug',
        'content',
        'show_in_menu',
        'show_in_footer',
        'position',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'show_in_menu' => 'boolean',
        'show_in_footer' => 'boolean',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }

            // Уникальный slug в рамках магазина
            $originalSlug = $page->slug;
            $count = 1;
            while (static::where('store_id', $page->store_id)->where('slug', $page->slug)->exists()) {
                $page->slug = $originalSlug.'-'.$count++;
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
}
