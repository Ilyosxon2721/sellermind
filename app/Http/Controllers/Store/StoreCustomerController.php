<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Models\Store\Store;
use App\Models\Store\StoreCustomer;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление клиентами магазина (admin)
 */
final class StoreCustomerController extends Controller
{
    use ApiResponder, HasCompanyScope, HasPaginatedResponse;

    /**
     * Список клиентов магазина
     */
    public function index(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);
        $perPage = $this->getPerPage($request);

        $query = StoreCustomer::where('store_id', $store->id)
            ->withCount('orders');

        if ($request->filled('search')) {
            $search = $this->escapeLike($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate($perPage);

        return $this->successResponse($customers->items(), $this->paginationMeta($customers));
    }

    /**
     * Показать клиента с заказами
     */
    public function show(int $storeId, int $customerId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $customer = StoreCustomer::where('store_id', $store->id)
            ->withCount('orders')
            ->findOrFail($customerId);

        $recentOrders = $customer->orders()->with('items')->limit(10)->get();

        return $this->successResponse([
            'customer' => $customer,
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * Обновить клиента (заблокировать, изменить данные)
     */
    public function update(int $storeId, int $customerId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $customer = StoreCustomer::where('store_id', $store->id)->findOrFail($customerId);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $customer->update($data);

        return $this->successResponse($customer->fresh());
    }

    /**
     * Статистика клиентов
     */
    public function stats(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $total = StoreCustomer::where('store_id', $store->id)->count();
        $active = StoreCustomer::where('store_id', $store->id)->where('is_active', true)->count();
        $thisMonth = StoreCustomer::where('store_id', $store->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return $this->successResponse([
            'total' => $total,
            'active' => $active,
            'new_this_month' => $thisMonth,
        ]);
    }

    private function findStore(int $storeId): Store
    {
        return Store::where('company_id', $this->getCompanyId())->findOrFail($storeId);
    }
}
