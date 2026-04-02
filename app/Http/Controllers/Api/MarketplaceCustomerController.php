<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceCustomer;
use App\Services\Marketplaces\MarketplaceCustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceCustomerController extends Controller
{
    use HasCompanyScope;
    use HasPaginatedResponse;

    public function __construct(
        protected MarketplaceCustomerService $customerService,
    ) {}

    /**
     * Список клиентов из маркетплейсов
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $query = MarketplaceCustomer::query()
            ->byCompany($companyId)
            ->search($request->get('search'));

        if ($request->get('source')) {
            $query->bySource($request->get('source'));
        }

        $sortBy = $request->get('sort_by', 'last_order_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['name', 'phone', 'orders_count', 'total_spent', 'first_order_at', 'last_order_at', 'created_at'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $this->getPerPage($request);
        $customers = $query->paginate($perPage);

        return response()->json([
            'data' => $customers->items(),
            'meta' => $this->paginationMeta($customers),
        ]);
    }

    /**
     * Детали клиента
     */
    public function show(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $customer = MarketplaceCustomer::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        return response()->json(['data' => $customer]);
    }

    /**
     * Обновить заметки клиента
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $customer = MarketplaceCustomer::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|nullable|string|max:100',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'data' => $customer->fresh(),
            'message' => 'Клиент обновлён',
        ]);
    }

    /**
     * Удалить клиента
     */
    public function destroy(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $customer = MarketplaceCustomer::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Клиент удалён',
        ]);
    }

    /**
     * Извлечь клиентов из существующих DBS заказов для конкретного аккаунта
     */
    public function extractFromOrders(Request $request, int $accountId): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $account = MarketplaceAccount::where('company_id', $companyId)
            ->findOrFail($accountId);

        $stats = $this->customerService->extractFromExistingOrders($account);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => "Извлечено клиентов: {$stats['created']} новых, {$stats['updated']} обновлено, {$stats['skipped']} пропущено",
        ]);
    }

    /**
     * Статистика клиентской базы
     */
    public function stats(): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $baseQuery = MarketplaceCustomer::byCompany($companyId);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'by_source' => [
                'uzum' => (clone $baseQuery)->bySource('uzum')->count(),
                'wb' => (clone $baseQuery)->bySource('wb')->count(),
                'ozon' => (clone $baseQuery)->bySource('ozon')->count(),
                'ym' => (clone $baseQuery)->bySource('ym')->count(),
            ],
            'total_spent' => (clone $baseQuery)->sum('total_spent'),
            'avg_orders' => round((float) (clone $baseQuery)->avg('orders_count'), 1),
        ];

        return response()->json(['data' => $stats]);
    }
}
