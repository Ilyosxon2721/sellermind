<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Store\Store;
use App\Models\Store\StorePromocode;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление промокодами магазина
 */
final class StorePromocodeController extends Controller
{
    use ApiResponder, HasCompanyScope;

    /**
     * Список промокодов магазина
     */
    public function index(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $promocodes = StorePromocode::where('store_id', $store->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($promocodes);
    }

    /**
     * Создать промокод
     */
    public function store(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'string', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Проверяем уникальность кода в рамках магазина
        $exists = StorePromocode::where('store_id', $store->id)
            ->where('code', $data['code'])
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'Промокод с таким кодом уже существует',
                'duplicate_code',
                'code',
                422
            );
        }

        $data['store_id'] = $store->id;
        $promocode = StorePromocode::create($data);

        return $this->successResponse($promocode)->setStatusCode(201);
    }

    /**
     * Показать промокод
     */
    public function show(int $storeId, int $id): JsonResponse
    {
        $store = $this->findStore($storeId);

        $promocode = StorePromocode::where('store_id', $store->id)->findOrFail($id);

        return $this->successResponse($promocode);
    }

    /**
     * Обновить промокод
     */
    public function update(int $storeId, int $id, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $promocode = StorePromocode::where('store_id', $store->id)->findOrFail($id);

        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['sometimes', 'string', 'in:percent,fixed'],
            'value' => ['sometimes', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Проверяем уникальность кода при изменении
        if (isset($data['code']) && $data['code'] !== $promocode->code) {
            $exists = StorePromocode::where('store_id', $store->id)
                ->where('code', $data['code'])
                ->where('id', '!=', $promocode->id)
                ->exists();

            if ($exists) {
                return $this->errorResponse(
                    'Промокод с таким кодом уже существует',
                    'duplicate_code',
                    'code',
                    422
                );
            }
        }

        $promocode->update($data);

        return $this->successResponse($promocode->fresh());
    }

    /**
     * Удалить промокод
     */
    public function destroy(int $storeId, int $id): JsonResponse
    {
        $store = $this->findStore($storeId);

        $promocode = StorePromocode::where('store_id', $store->id)->findOrFail($id);
        $promocode->delete();

        return $this->successResponse(['message' => 'Промокод удалён']);
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
