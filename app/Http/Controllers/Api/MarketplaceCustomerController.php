<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceCustomer;
use App\Models\OzonOrder;
use App\Models\UzumOrder;
use App\Models\WbOrder;
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
     * Детали клиента с количеством заказов
     */
    public function show(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $customer = MarketplaceCustomer::query()
            ->byCompany($companyId)
            ->withCount('customerOrders')
            ->findOrFail($id);

        return response()->json(['data' => $customer]);
    }

    /**
     * История заказов клиента с товарами
     *
     * Возвращает все заказы клиента: что купил, какой статус, какие товары.
     * Отменённые заказы помечены флагом is_cancelled.
     */
    public function orders(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $customer = MarketplaceCustomer::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        $orders = $this->customerService->getCustomerOrders($customer);

        // Считаем сводку
        $totalOrders = $orders->count();
        $cancelledOrders = $orders->where('is_cancelled', true)->count();
        $completedOrders = $totalOrders - $cancelledOrders;
        $totalSpent = $orders->where('is_cancelled', false)->sum('total_amount');

        return response()->json([
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'source' => $customer->source,
                ],
                'summary' => [
                    'total_orders' => $totalOrders,
                    'completed_orders' => $completedOrders,
                    'cancelled_orders' => $cancelledOrders,
                    'total_spent' => round($totalSpent, 2),
                ],
                'orders' => $orders->values(),
            ],
        ]);
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
     * Извлечь клиентов из существующих DBS заказов по всем аккаунтам компании.
     * Возвращает детализацию по каждому аккаунту для диагностики.
     */
    public function extractAll(): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $accounts = MarketplaceAccount::where('company_id', $companyId)->get();

        $totals = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'accounts' => 0];
        $details = [];

        foreach ($accounts as $account) {
            $accountName = $account->name ?? $account->login ?? "#{$account->id}";
            $detail = [
                'account_id' => $account->id,
                'marketplace' => $account->marketplace,
                'name' => $accountName,
                'orders_total' => 0,
                'eligible' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];

            // Считаем подходящие заказы (диагностика)
            try {
                if ($account->marketplace === 'uzum') {
                    $base = UzumOrder::where('marketplace_account_id', $account->id);
                    $detail['orders_total'] = (clone $base)->count();
                    $detail['eligible'] = (clone $base)
                        ->whereIn('delivery_type', ['DBS', 'EDBS'])
                        ->whereNotNull('customer_phone')
                        ->whereNotNull('customer_name')
                        ->count();
                } elseif ($account->marketplace === 'wb') {
                    $base = WbOrder::where('marketplace_account_id', $account->id);
                    $detail['orders_total'] = (clone $base)->count();
                    $detail['eligible'] = (clone $base)
                        ->whereNotNull('customer_phone')
                        ->whereNotNull('customer_name')
                        ->count();
                } elseif ($account->marketplace === 'ozon' && \Illuminate\Support\Facades\Schema::hasTable('ozon_orders')) {
                    $base = OzonOrder::where('marketplace_account_id', $account->id);
                    $detail['orders_total'] = (clone $base)->count();
                    $detail['eligible'] = (clone $base)
                        ->whereNotNull('customer_phone')
                        ->whereNotNull('customer_name')
                        ->count();
                }

                $stats = $this->customerService->extractFromExistingOrders($account);
                $detail['created'] = $stats['created'];
                $detail['updated'] = $stats['updated'];
                $detail['skipped'] = $stats['skipped'];

                $totals['created'] += $stats['created'];
                $totals['updated'] += $stats['updated'];
                $totals['skipped'] += $stats['skipped'];
                $totals['accounts']++;
            } catch (\Throwable $e) {
                $detail['error'] = $e->getMessage();
                \Illuminate\Support\Facades\Log::warning('Ошибка бекфилла клиентов из заказов', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $details[] = $detail;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => $totals,
                'accounts' => $details,
            ],
            'message' => "Обработано аккаунтов: {$totals['accounts']}. Извлечено клиентов: {$totals['created']} новых, {$totals['updated']} обновлено, {$totals['skipped']} пропущено",
        ]);
    }

    /**
     * Диагностика: сколько заказов по каждому аккаунту и сколько из них подходят под критерии
     * извлечения клиентов (есть customer_phone/customer_name и нужный delivery_type).
     *
     * Используется для отладки: если "Извлечь из заказов" возвращает 0 — тут видно почему.
     */
    public function diagnose(): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $accounts = MarketplaceAccount::where('company_id', $companyId)->get();

        $report = [];
        foreach ($accounts as $account) {
            $row = [
                'account_id' => $account->id,
                'marketplace' => $account->marketplace,
                'name' => $account->name ?? $account->login ?? "#{$account->id}",
                'orders_total' => 0,
                'with_phone' => 0,
                'with_name' => 0,
                'with_phone_and_name' => 0,
                'dbs_eligible' => 0,
                'delivery_types' => [],
            ];

            if ($account->marketplace === 'uzum') {
                $base = UzumOrder::where('marketplace_account_id', $account->id);
                $row['orders_total'] = (clone $base)->count();
                $row['with_phone'] = (clone $base)->whereNotNull('customer_phone')->count();
                $row['with_name'] = (clone $base)->whereNotNull('customer_name')->count();
                $row['with_phone_and_name'] = (clone $base)->whereNotNull('customer_phone')->whereNotNull('customer_name')->count();
                $row['dbs_eligible'] = (clone $base)
                    ->whereIn('delivery_type', ['DBS', 'EDBS'])
                    ->whereNotNull('customer_phone')
                    ->whereNotNull('customer_name')
                    ->count();
                $row['delivery_types'] = (clone $base)
                    ->selectRaw('delivery_type, COUNT(*) as cnt')
                    ->groupBy('delivery_type')
                    ->pluck('cnt', 'delivery_type')
                    ->toArray();
            } elseif ($account->marketplace === 'wb') {
                $base = WbOrder::where('marketplace_account_id', $account->id);
                $row['orders_total'] = (clone $base)->count();
                $row['with_phone'] = (clone $base)->whereNotNull('customer_phone')->count();
                $row['with_name'] = (clone $base)->whereNotNull('customer_name')->count();
                $row['with_phone_and_name'] = (clone $base)->whereNotNull('customer_phone')->whereNotNull('customer_name')->count();
                $row['dbs_eligible'] = $row['with_phone_and_name'];
                $row['delivery_types'] = (clone $base)
                    ->selectRaw('wb_delivery_type as delivery_type, COUNT(*) as cnt')
                    ->groupBy('wb_delivery_type')
                    ->pluck('cnt', 'delivery_type')
                    ->toArray();
            } elseif ($account->marketplace === 'ozon' && \Illuminate\Support\Facades\Schema::hasTable('ozon_orders')) {
                $base = OzonOrder::where('marketplace_account_id', $account->id);
                $row['orders_total'] = (clone $base)->count();
                $row['with_phone'] = (clone $base)->whereNotNull('customer_phone')->count();
                $row['with_name'] = (clone $base)->whereNotNull('customer_name')->count();
                $row['with_phone_and_name'] = (clone $base)->whereNotNull('customer_phone')->whereNotNull('customer_name')->count();
                $row['dbs_eligible'] = $row['with_phone_and_name'];
            } else {
                $row['note'] = 'маркетплейс не поддерживается извлечением клиентов';
            }

            $report[] = $row;
        }

        return response()->json([
            'success' => true,
            'data' => $report,
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
