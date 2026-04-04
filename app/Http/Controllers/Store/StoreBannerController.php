<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Store\Store;
use App\Models\Store\StoreBanner;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Управление баннерами магазина
 */
final class StoreBannerController extends Controller
{
    use ApiResponder, HasCompanyScope;

    /**
     * Список баннеров магазина
     */
    public function index(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $banners = StoreBanner::where('store_id', $store->id)
            ->orderBy('position')
            ->get();

        return $this->successResponse($banners);
    }

    /**
     * Создать баннер
     */
    public function store(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $data = $request->validate([
            'image' => ['required', 'string', 'max:500'],
            'image_mobile' => ['nullable', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'text_color' => ['nullable', 'string', 'max:7'],
            'display_mode' => ['nullable', 'string', 'in:overlay,split,image_only,text_below'],
            'url' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $data['store_id'] = $store->id;

        if (! isset($data['position'])) {
            $data['position'] = StoreBanner::where('store_id', $store->id)->max('position') + 1;
        }

        $banner = StoreBanner::create($data);

        Cache::forget("storefront:home:{$store->slug}");

        return $this->successResponse($banner)->setStatusCode(201);
    }

    /**
     * Обновить баннер
     */
    public function update(int $storeId, int $bannerId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $banner = StoreBanner::where('store_id', $store->id)->findOrFail($bannerId);

        $data = $request->validate([
            'image' => ['sometimes', 'string', 'max:500'],
            'image_mobile' => ['nullable', 'string', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'text_color' => ['nullable', 'string', 'max:7'],
            'display_mode' => ['nullable', 'string', 'in:overlay,split,image_only,text_below'],
            'url' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $banner->update($data);

        Cache::forget("storefront:home:{$store->slug}");

        return $this->successResponse($banner);
    }

    /**
     * Удалить баннер
     */
    public function destroy(int $storeId, int $bannerId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $banner = StoreBanner::where('store_id', $store->id)->findOrFail($bannerId);
        $banner->delete();

        Cache::forget("storefront:home:{$store->slug}");

        return $this->successResponse(['message' => 'Баннер удалён']);
    }

    /**
     * Изменить порядок баннеров
     */
    public function reorder(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.position' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['items'] as $item) {
            StoreBanner::where('store_id', $store->id)
                ->where('id', $item['id'])
                ->update(['position' => $item['position']]);
        }

        $banners = StoreBanner::where('store_id', $store->id)
            ->orderBy('position')
            ->get();

        return $this->successResponse($banners);
    }

    /**
     * Загрузить изображение для баннера
     */
    public function uploadImage(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('image')->store(
            "banners/{$store->id}",
            'public'
        );

        return $this->successResponse([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
        ]);
    }

    /**
     * Найти магазин текущей компании
     */
    private function findStore(int $storeId): Store
    {
        $companyId = $this->getCompanyId();

        return Store::where('company_id', $companyId)->findOrFail($storeId);
    }
}
