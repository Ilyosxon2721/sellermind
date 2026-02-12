<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Store\Store;
use App\Models\Store\StorePaymentMethod;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление способами оплаты магазина
 */
final class StorePaymentController extends Controller
{
    use ApiResponder, HasCompanyScope;

    /**
     * Список способов оплаты
     */
    public function index(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $methods = StorePaymentMethod::where('store_id', $store->id)
            ->orderBy('position')
            ->get();

        return $this->successResponse($methods);
    }

    /**
     * Создать способ оплаты
     */
    public function store(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['store_id'] = $store->id;

        if (! isset($data['position'])) {
            $data['position'] = StorePaymentMethod::where('store_id', $store->id)->max('position') + 1;
        }

        $method = StorePaymentMethod::create($data);

        return $this->successResponse($method)->setStatusCode(201);
    }

    /**
     * Показать способ оплаты
     */
    public function show(int $storeId, int $id): JsonResponse
    {
        $store = $this->findStore($storeId);

        $method = StorePaymentMethod::where('store_id', $store->id)->findOrFail($id);

        return $this->successResponse($method);
    }

    /**
     * Обновить способ оплаты
     */
    public function update(int $storeId, int $id, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $method = StorePaymentMethod::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'type' => ['sometimes', 'string', 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $method->update($data);

        return $this->successResponse($method);
    }

    /**
     * Удалить способ оплаты
     */
    public function destroy(int $storeId, int $id): JsonResponse
    {
        $store = $this->findStore($storeId);

        $method = StorePaymentMethod::where('store_id', $store->id)->findOrFail($id);
        $method->delete();

        return $this->successResponse(['message' => 'Способ оплаты удалён']);
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
