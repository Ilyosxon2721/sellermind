<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\CashShift;
use App\Models\OfflineSale;
use App\Services\PosService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Контроллер POS-терминала.
 * Управление кассовыми сменами, быстрыми продажами и кассовыми операциями.
 */
final class PosController extends Controller
{
    use ApiResponder;
    use HasCompanyScope;

    public function __construct(
        private readonly PosService $posService,
    ) {}

    // ========== Управление сменами ==========

    /**
     * Открыть новую кассовую смену
     */
    public function openShift(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validator = Validator::make($request->all(), [
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'opening_balance' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->first(),
                'validation_error',
                null,
                422
            );
        }

        try {
            $shift = $this->posService->openShift(
                $companyId,
                (int) Auth::id(),
                $validator->validated()
            );

            return $this->successResponse($shift);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 'shift_error', null, 422);
        }
    }

    /**
     * Закрыть текущую кассовую смену
     */
    public function closeShift(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validator = Validator::make($request->all(), [
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->first(),
                'validation_error',
                null,
                422
            );
        }

        $shift = $this->getCurrentOpenShift($companyId);
        if (! $shift) {
            return $this->errorResponse('Нет открытой смены', 'no_open_shift', null, 404);
        }

        try {
            $data = $validator->validated();
            $closedShift = $this->posService->closeShift(
                $shift,
                (int) Auth::id(),
                (float) $data['closing_balance'],
                $data['notes'] ?? null
            );

            return $this->successResponse($closedShift);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 'shift_error', null, 422);
        }
    }

    /**
     * Получить текущую открытую смену с итогами
     */
    public function currentShift(): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $shift = $this->getCurrentOpenShift($companyId);
        if (! $shift) {
            return $this->successResponse(null);
        }

        // Загружаем связанные данные для отображения
        $shift->load(['openedBy:id,name', 'cashAccount:id,name,type,balance']);

        // Добавляем сводку по смене
        $salesInShift = OfflineSale::where('company_id', $companyId)
            ->where('created_by', $shift->opened_by)
            ->where('metadata->pos_shift_id', $shift->id)
            ->where('status', OfflineSale::STATUS_DELIVERED)
            ->get();

        $cashSales = (float) $salesInShift->where('payment_method', 'cash')->sum('total_amount');

        $summary = [
            'sales_count' => $salesInShift->count(),
            'total_sales' => (float) $salesInShift->sum('total_amount'),
            'cash_sales' => $cashSales,
            'card_sales' => (float) $salesInShift->where('payment_method', 'card')->sum('total_amount'),
            'transfer_sales' => (float) $salesInShift->where('payment_method', 'transfer')->sum('total_amount'),
            'mixed_sales' => (float) $salesInShift->where('payment_method', 'mixed')->sum('total_amount'),
            'expected_balance' => (float) $shift->opening_balance + $cashSales
                + (float) $shift->total_cash_in - (float) $shift->total_cash_out
                - (float) $shift->total_refunds,
        ];

        return $this->successResponse([
            'shift' => $shift,
            'summary' => $summary,
        ]);
    }

    // ========== Быстрая продажа ==========

    /**
     * Быстрая продажа через POS-терминал (всё в одном запросе)
     */
    public function quickSell(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.sku_id' => 'nullable|exists:skus,id',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'required|in:cash,card,transfer,mixed',
            'paid_amount' => 'required|numeric|min:0',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->first(),
                'validation_error',
                null,
                422
            );
        }

        $shift = $this->getCurrentOpenShift($companyId);
        if (! $shift) {
            return $this->errorResponse(
                'Нет открытой смены. Откройте смену перед продажей.',
                'no_open_shift',
                null,
                422
            );
        }

        try {
            $sale = $this->posService->quickSell($shift, $validator->validated());

            return $this->successResponse($sale);
        } catch (\RuntimeException $e) {
            Log::error('POS: Ошибка быстрой продажи', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($e->getMessage(), 'sale_error', null, 422);
        } catch (\Throwable $e) {
            Log::error('POS: Критическая ошибка быстрой продажи', [
                'shift_id' => $shift->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Ошибка при оформлении продажи: ' . $e->getMessage(),
                'internal_error',
                null,
                500
            );
        }
    }

    // ========== Поиск товаров ==========

    /**
     * Поиск товаров для POS-терминала (по имени, SKU, штрихкоду)
     */
    public function getProducts(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $query = $request->get('query', $request->get('q'));
        $warehouseId = $request->integer('warehouse_id') ?: null;
        $limit = min($request->integer('limit', 30), 100);

        $products = $this->posService->searchProducts($companyId, $query, $warehouseId, $limit);

        return $this->successResponse($products);
    }

    /**
     * Получить категории товаров
     */
    public function getCategories(): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $categories = $this->posService->getCategories($companyId);

        return $this->successResponse($categories);
    }

    /**
     * Получить товары по категории
     */
    public function getProductsByCategory(Request $request, int $categoryId): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $products = $this->posService->getProductsByCategory($companyId, $categoryId, $warehouseId);

        return $this->successResponse($products);
    }

    // ========== Кассовые операции ==========

    /**
     * Внесение наличных в кассу
     */
    public function cashIn(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->first(),
                'validation_error',
                null,
                422
            );
        }

        $shift = $this->getCurrentOpenShift($companyId);
        if (! $shift) {
            return $this->errorResponse('Нет открытой смены', 'no_open_shift', null, 422);
        }

        try {
            $data = $validator->validated();
            $this->posService->cashIn($shift, (float) $data['amount'], $data['description']);

            $freshShift = $shift->fresh();

            return $this->successResponse([
                'message' => 'Внесение выполнено',
                'amount' => (float) $data['amount'],
                'shift_balance' => $freshShift->getExpectedBalance(),
            ]);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 'cash_error', null, 422);
        }
    }

    /**
     * Изъятие наличных из кассы
     */
    public function cashOut(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                $validator->errors()->first(),
                'validation_error',
                null,
                422
            );
        }

        $shift = $this->getCurrentOpenShift($companyId);
        if (! $shift) {
            return $this->errorResponse('Нет открытой смены', 'no_open_shift', null, 422);
        }

        try {
            $data = $validator->validated();
            $this->posService->cashOut($shift, (float) $data['amount'], $data['description']);

            $freshShift = $shift->fresh();

            return $this->successResponse([
                'message' => 'Изъятие выполнено',
                'amount' => (float) $data['amount'],
                'shift_balance' => $freshShift->getExpectedBalance(),
            ]);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 'cash_error', null, 422);
        }
    }

    // ========== Отчёты ==========

    /**
     * Z-отчёт (итоги смены)
     */
    public function shiftReport(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        // Можно запросить отчёт по конкретной смене или по текущей
        $shiftId = $request->integer('shift_id');

        if ($shiftId) {
            $shift = CashShift::where('company_id', $companyId)
                ->findOrFail($shiftId);
        } else {
            $shift = $this->getCurrentOpenShift($companyId);
            if (! $shift) {
                return $this->errorResponse('Нет открытой смены', 'no_open_shift', null, 404);
            }
        }

        // Собираем детальную статистику по продажам смены
        $sales = OfflineSale::where('company_id', $companyId)
            ->where('metadata->pos_shift_id', $shift->id)
            ->get();

        $deliveredSales = $sales->where('status', OfflineSale::STATUS_DELIVERED);
        $cancelledSales = $sales->where('status', OfflineSale::STATUS_CANCELLED);

        // Разбивка по способам оплаты
        $byPaymentMethod = [
            'cash' => (float) $deliveredSales->where('payment_method', 'cash')->sum('total_amount'),
            'card' => (float) $deliveredSales->where('payment_method', 'card')->sum('total_amount'),
            'transfer' => (float) $deliveredSales->where('payment_method', 'transfer')->sum('total_amount'),
            'mixed' => (float) $deliveredSales->where('payment_method', 'mixed')->sum('total_amount'),
        ];

        $report = [
            'shift_id' => $shift->id,
            'status' => $shift->status,
            'cashier' => $shift->openedBy?->name ?? 'Неизвестен',
            'opened_at' => $shift->opened_at?->toIso8601String(),
            'closed_at' => $shift->closed_at?->toIso8601String(),
            'opening_balance' => (float) $shift->opening_balance,
            'closing_balance' => $shift->closing_balance ? (float) $shift->closing_balance : null,
            'expected_balance' => $shift->getExpectedBalance(),
            'difference' => $shift->closing_balance !== null
                ? $shift->getDifference()
                : null,
            'sales' => [
                'count' => $deliveredSales->count(),
                'total' => (float) $deliveredSales->sum('total_amount'),
                'average' => $deliveredSales->count() > 0
                    ? round((float) $deliveredSales->sum('total_amount') / $deliveredSales->count(), 2)
                    : 0,
                'by_payment_method' => $byPaymentMethod,
            ],
            'cancelled' => [
                'count' => $cancelledSales->count(),
                'total' => (float) $cancelledSales->sum('total_amount'),
            ],
            'cash_in' => (float) $shift->total_cash_in,
            'cash_out' => (float) $shift->total_cash_out,
            'total_discount' => (float) $deliveredSales->sum('discount_amount'),
        ];

        return $this->successResponse($report);
    }

    /**
     * Последние продажи из текущей смены
     */
    public function recentSales(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $shift = $this->getCurrentOpenShift($companyId);
        if (! $shift) {
            return $this->successResponse([]);
        }

        $limit = min($request->integer('limit', 20), 50);

        $sales = OfflineSale::where('company_id', $companyId)
            ->where('metadata->pos_shift_id', $shift->id)
            ->with(['items'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $this->successResponse($sales);
    }

    // ========== Вспомогательные методы ==========

    /**
     * Получить текущую открытую смену для пользователя
     */
    private function getCurrentOpenShift(int $companyId): ?CashShift
    {
        return CashShift::where('company_id', $companyId)
            ->where('opened_by', Auth::id())
            ->where('status', CashShift::STATUS_OPEN)
            ->first();
    }
}
