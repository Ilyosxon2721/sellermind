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
            'url' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $banner->update($data);

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
     * Найти магазин текущей компании
     */
    private function findStore(int $storeId): Store
    {
        $companyId = $this->getCompanyId();

        return Store::where('company_id', $companyId)->findOrFail($storeId);
    }
}
