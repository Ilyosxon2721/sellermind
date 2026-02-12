<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Store\Store;
use App\Models\Store\StoreDeliveryMethod;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление способами доставки магазина
 */
final class StoreDeliveryController extends Controller
{
    use ApiResponder, HasCompanyScope;

    /**
     * Список способов доставки
     */
    public function index(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $methods = StoreDeliveryMethod::where('store_id', $store->id)
            ->orderBy('position')
            ->get();

        return $this->successResponse($methods);
    }

    /**
     * Создать способ доставки
     */
    public function store(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'free_from' => ['nullable', 'numeric', 'min:0'],
            'min_days' => ['nullable', 'integer', 'min:0'],
            'max_days' => ['nullable', 'integer', 'min:0', 'gte:min_days'],
            'zones' => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['store_id'] = $store->id;

        if (! isset($data['position'])) {
            $data['position'] = StoreDeliveryMethod::where('store_id', $store->id)->max('position') + 1;
        }

        $method = StoreDeliveryMethod::create($data);

        return $this->successResponse($method)->setStatusCode(201);
    }

    /**
     * Показать способ доставки
     */
    public function show(int $storeId, int $id): JsonResponse
    {
        $store = $this->findStore($storeId);

        $method = StoreDeliveryMethod::where('store_id', $store->id)->findOrFail($id);

        return $this->successResponse($method);
    }

    /**
     * Обновить способ доставки
     */
    public function update(int $storeId, int $id, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $method = StoreDeliveryMethod::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', 'max:50'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'free_from' => ['nullable', 'numeric', 'min:0'],
            'min_days' => ['nullable', 'integer', 'min:0'],
            'max_days' => ['nullable', 'integer', 'min:0', 'gte:min_days'],
            'zones' => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $method->update($data);

        return $this->successResponse($method);
    }

    /**
     * Удалить способ доставки
     */
    public function destroy(int $storeId, int $id): JsonResponse
    {
        $store = $this->findStore($storeId);

        $method = StoreDeliveryMethod::where('store_id', $store->id)->findOrFail($id);
        $method->delete();

        return $this->successResponse(['message' => 'Способ доставки удалён']);
    }

    /**
     * Изменить порядок способов доставки
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
            StoreDeliveryMethod::where('store_id', $store->id)
                ->where('id', $item['id'])
                ->update(['position' => $item['position']]);
        }

        $methods = StoreDeliveryMethod::where('store_id', $store->id)
            ->orderBy('position')
            ->get();

        return $this->successResponse($methods);
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
