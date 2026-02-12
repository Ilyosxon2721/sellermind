<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Store\Store;
use App\Models\Store\StorePage;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление страницами магазина (О нас, Доставка, Контакты и т.д.)
 */
final class StorePageController extends Controller
{
    use ApiResponder, HasCompanyScope;

    /**
     * Список страниц магазина
     */
    public function index(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $pages = StorePage::where('store_id', $store->id)
            ->orderBy('position')
            ->get();

        return $this->successResponse($pages);
    }

    /**
     * Создать страницу
     */
    public function store(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'show_in_menu' => ['sometimes', 'boolean'],
            'show_in_footer' => ['sometimes', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['store_id'] = $store->id;

        if (! isset($data['position'])) {
            $data['position'] = StorePage::where('store_id', $store->id)->max('position') + 1;
        }

        $page = StorePage::create($data);

        return $this->successResponse($page)->setStatusCode(201);
    }

    /**
     * Показать страницу
     */
    public function show(int $storeId, int $id): JsonResponse
    {
        $store = $this->findStore($storeId);

        $page = StorePage::where('store_id', $store->id)->findOrFail($id);

        return $this->successResponse($page);
    }

    /**
     * Обновить страницу
     */
    public function update(int $storeId, int $id, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $page = StorePage::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'show_in_menu' => ['sometimes', 'boolean'],
            'show_in_footer' => ['sometimes', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $page->update($data);

        return $this->successResponse($page);
    }

    /**
     * Удалить страницу
     */
    public function destroy(int $storeId, int $id): JsonResponse
    {
        $store = $this->findStore($storeId);

        $page = StorePage::where('store_id', $store->id)->findOrFail($id);
        $page->delete();

        return $this->successResponse(['message' => 'Страница удалена']);
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
