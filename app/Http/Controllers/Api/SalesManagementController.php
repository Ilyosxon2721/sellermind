<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Counterparty;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse\Warehouse;
use App\Services\SaleReservationService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Контроллер для управления продажами (CRUD)
 */
class SalesManagementController extends Controller
{
    use HasCompanyScope;

    public function __construct(
        protected SaleService $saleService,
        protected SaleReservationService $reservationService
    ) {}

    /**
     * Получить список продаж
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $query = Sale::query()
            ->with(['items', 'counterparty', 'createdBy'])
            ->where('company_id', $companyId);

        // Фильтры
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($source = $request->get('source')) {
            $query->where('source', $source);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($counterpartyId = $request->get('counterparty_id')) {
            $query->where('counterparty_id', $counterpartyId);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search = $request->get('search')) {
            $query->search($this->escapeLike($search));
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = min((int) $request->get('per_page', 20), 100);
        $sales = $query->paginate($perPage);

        return response()->json([
            'data' => $sales->items(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
                'last_page' => $sales->lastPage(),
            ],
        ]);
    }

    /**
     * Получить одну продажу
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $sale = Sale::query()
            ->with(['items.productVariant', 'counterparty', 'createdBy', 'confirmedBy'])
            ->byCompany($companyId)
            ->findOrFail($id);

        return response()->json([
            'data' => $sale,
            'margin' => $sale->getMargin(),
            'margin_percent' => $sale->getMarginPercent(),
        ]);
    }

    /**
     * Создать новую продажу
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:marketplace,manual,pos',
            'source' => 'nullable|in:uzum,wb,ozon,ym,manual,pos',
            'counterparty_id' => 'nullable|exists:counterparties,id',
            'currency' => 'nullable|in:UZS,USD,RUB',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.product_name' => 'required_without:items.*.product_variant_id|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sale = $this->saleService->createSale($validator->validated());

            return response()->json([
                'message' => 'Sale created successfully',
                'data' => $sale,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to create sale', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['items']),
                'items_count' => count($request->input('items', [])),
            ]);

            return response()->json([
                'message' => 'Failed to create sale',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить продажу
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        // Можно обновлять только черновики
        if ($sale->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft sales can be updated',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'counterparty_id' => 'nullable|exists:counterparties,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sale->update($request->only(['counterparty_id', 'notes', 'metadata']));

        return response()->json([
            'message' => 'Sale updated successfully',
            'data' => $sale->fresh(['items', 'counterparty']),
        ]);
    }

    /**
     * Удалить продажу
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        // Можно удалять только черновики
        if ($sale->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft sales can be deleted',
            ], 403);
        }

        $sale->delete();

        return response()->json([
            'message' => 'Sale deleted successfully',
        ]);
    }

    /**
     * Подтвердить продажу
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        $deductStock = $request->boolean('deduct_stock', true);

        try {
            $sale = $this->saleService->confirmSale($sale, $deductStock);

            return response()->json([
                'message' => 'Sale confirmed successfully',
                'data' => $sale,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to confirm sale',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Завершить продажу
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        try {
            $sale = $this->saleService->completeSale($sale);

            return response()->json([
                'message' => 'Sale completed successfully',
                'data' => $sale,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to complete sale',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Отменить продажу
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        try {
            $sale = $this->saleService->cancelSale($sale);

            return response()->json([
                'message' => 'Sale cancelled successfully',
                'data' => $sale,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel sale',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Отгрузить товары (финализировать резерв и синхронизировать с маркетплейсами)
     */
    public function ship(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'exists:sale_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $itemIds = $request->get('item_ids');
            $results = $this->reservationService->shipStock($sale, $itemIds);

            return response()->json([
                'message' => 'Items shipped successfully',
                'results' => $results,
                'data' => $sale->fresh(['items']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to ship items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить информацию о резервах продажи
     */
    public function getReservations(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        $reservations = $this->reservationService->getActiveReservations($sale);
        $isFullyShipped = $this->reservationService->isFullyShipped($sale);

        return response()->json([
            'data' => [
                'reservations' => $reservations,
                'is_fully_shipped' => $isFullyShipped,
            ],
        ]);
    }

    /**
     * Добавить позицию в продажу
     */
    public function addItem(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($id);

        if ($sale->status !== 'draft') {
            return response()->json([
                'message' => 'Cannot add items to non-draft sale',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'product_name' => 'required_without:product_variant_id|string|max:255',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $item = $this->saleService->addItemToSale($sale, $validator->validated());
            $sale->recalculateTotals();

            return response()->json([
                'message' => 'Item added successfully',
                'data' => $item,
                'sale' => $sale->fresh(['items']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить позицию продажи
     */
    public function updateItem(Request $request, int $saleId, int $itemId): JsonResponse
    {
        $companyId = $this->getCompanyId();

        // Проверяем что продажа принадлежит компании пользователя
        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($saleId);

        $item = SaleItem::where('sale_id', $saleId)->findOrFail($itemId);

        if ($item->sale->status !== 'draft') {
            return response()->json([
                'message' => 'Cannot update items in non-draft sale',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'nullable|numeric|min:0.001',
            'unit_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $item = $this->saleService->updateSaleItem($item, $validator->validated());

            return response()->json([
                'message' => 'Item updated successfully',
                'data' => $item,
                'sale' => $item->sale->fresh(['items']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить позицию из продажи
     */
    public function deleteItem(Request $request, int $saleId, int $itemId): JsonResponse
    {
        $companyId = $this->getCompanyId();

        // Проверяем что продажа принадлежит компании пользователя
        $sale = Sale::query()
            ->byCompany($companyId)
            ->findOrFail($saleId);

        $item = SaleItem::where('sale_id', $saleId)->findOrFail($itemId);

        if ($item->sale->status !== 'draft') {
            return response()->json([
                'message' => 'Cannot delete items from non-draft sale',
            ], 403);
        }

        try {
            $this->saleService->removeSaleItem($item);

            return response()->json([
                'message' => 'Item deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить статистику продаж
     */
    public function statistics(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'type' => $request->get('type'),
            'source' => $request->get('source'),
            'status' => $request->get('status'),
        ];

        $stats = $this->saleService->getSalesStatistics($companyId, $filters);

        return response()->json($stats);
    }

    /**
     * Получить список контрагентов для выбора
     */
    public function getCounterparties(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $counterparties = Counterparty::query()
            ->where('company_id', $companyId)
            ->where('is_customer', true)
            ->where('is_active', true)
            ->when($request->get('search'), fn ($q, $search) => $q->search($this->escapeLike($search)))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'short_name', 'inn', 'phone']);

        return response()->json(['data' => $counterparties]);
    }

    /**
     * Получить список товаров для выбора (с доступными остатками по складу)
     */
    public function getProducts(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $search = $request->get('search') ? $this->escapeLike($request->get('search')) : null;
        $warehouseId = $request->get('warehouse_id') ? (int) $request->get('warehouse_id') : null;

        $products = $this->saleService->getProductsForSale($companyId, $search, $warehouseId);

        return response()->json(['data' => $products]);
    }

    /**
     * Получить список складов для пользователя
     */
    public function getWarehouses(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        // Get warehouses directly from Warehouse model
        $warehouses = Warehouse::select([
            'warehouses.id as id',
            'warehouses.name as name',
            'warehouses.code as code',
            'warehouses.address as address',
        ])
            ->where('warehouses.company_id', $companyId)
            ->where('warehouses.is_active', true)
            ->orderBy('warehouses.is_default', 'desc')
            ->orderBy('warehouses.name')
            ->get();

        return response()->json(['data' => $warehouses]);
    }
}
