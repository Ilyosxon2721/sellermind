<?php

namespace App\Models\Store;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCategory extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'category_id',
        'custom_name',
        'custom_description',
        'custom_image',
        'position',
        'is_visible',
        'show_in_menu',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'show_in_menu' => 'boolean',
        'position' => 'integer',
    ];

    // ==================
    // Relationships
    // ==================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
