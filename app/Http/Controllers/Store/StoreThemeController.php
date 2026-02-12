<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Store\Store;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление темой/дизайном магазина
 */
final class StoreThemeController extends Controller
{
    use ApiResponder, HasCompanyScope;

    /**
     * Получить тему магазина
     */
    public function show(int $storeId): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $store = Store::where('company_id', $companyId)->findOrFail($storeId);

        return $this->successResponse($store->theme);
    }

    /**
     * Обновить или создать тему магазина (upsert)
     */
    public function update(int $storeId, Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $store = Store::where('company_id', $companyId)->findOrFail($storeId);

        $data = $request->validate([
            'template' => ['sometimes', 'string', 'max:50'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'text_color' => ['nullable', 'string', 'max:20'],
            'heading_font' => ['nullable', 'string', 'max:100'],
            'body_font' => ['nullable', 'string', 'max:100'],
            'header_style' => ['nullable', 'string', 'max:50'],
            'header_bg_color' => ['nullable', 'string', 'max:20'],
            'header_text_color' => ['nullable', 'string', 'max:20'],
            'show_search' => ['sometimes', 'boolean'],
            'show_cart' => ['sometimes', 'boolean'],
            'show_phone' => ['sometimes', 'boolean'],
            'hero_enabled' => ['sometimes', 'boolean'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:500'],
            'hero_image' => ['nullable', 'string', 'max:500'],
            'hero_button_text' => ['nullable', 'string', 'max:100'],
            'hero_button_url' => ['nullable', 'string', 'max:500'],
            'products_per_page' => ['nullable', 'integer', 'min:4', 'max:100'],
            'product_card_style' => ['nullable', 'string', 'max:50'],
            'show_quick_view' => ['sometimes', 'boolean'],
            'show_add_to_cart' => ['sometimes', 'boolean'],
            'footer_style' => ['nullable', 'string', 'max:50'],
            'footer_bg_color' => ['nullable', 'string', 'max:20'],
            'footer_text_color' => ['nullable', 'string', 'max:20'],
            'footer_text' => ['nullable', 'string'],
            'custom_css' => ['nullable', 'string', 'max:10000'],
        ]);

        $theme = $store->theme()->updateOrCreate(
            ['store_id' => $store->id],
            $data,
        );

        return $this->successResponse($theme);
    }
}
