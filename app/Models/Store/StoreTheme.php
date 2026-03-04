<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreTheme extends Model
{
    protected $fillable = [
        'store_id',
        'template',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'text_color',
        'heading_font',
        'body_font',
        'header_style',
        'header_bg_color',
        'header_text_color',
        'show_search',
        'show_cart',
        'show_phone',
        'hero_enabled',
        'hero_title',
        'hero_subtitle',
        'hero_image',
        'hero_button_text',
        'hero_button_url',
        'products_per_page',
        'product_card_style',
        'show_quick_view',
        'show_add_to_cart',
        'footer_style',
        'footer_bg_color',
        'footer_text_color',
        'footer_text',
        'custom_css',
    ];

    protected $casts = [
        'show_search' => 'boolean',
        'show_cart' => 'boolean',
        'show_phone' => 'boolean',
        'hero_enabled' => 'boolean',
        'show_quick_view' => 'boolean',
        'show_add_to_cart' => 'boolean',
        'products_per_page' => 'integer',
    ];

    /**
     * Возвращает валидный шаблон с fallback на 'default'
     */
    public function resolvedTemplate(): string
    {
        $tpl = $this->template ?: 'default';

        return is_dir(resource_path("views/storefront/themes/{$tpl}")) ? $tpl : 'default';
    }

    // ==================
    // Relationships
    // ==================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
