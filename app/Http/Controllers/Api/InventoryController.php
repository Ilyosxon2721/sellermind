<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use HasCompanyScope;

    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    /**
     * List all inventories
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $query = Inventory::query()
            ->byCompany($companyId)
            ->with(['warehouse', 'createdByUser']);

        if ($request->get('warehouse_id')) {
            $query->byWarehouse($request->get('warehouse_id'));
        }

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $inventories = $query->orderByDesc('date')->paginate($perPage);

        return response()->json([
            'data' => $inventories->items(),
            'meta' => [
                'current_page' => $inventories->currentPage(),
                'last_page' => $inventories->lastPage(),
                'per_page' => $inventories->perPage(),
                'total' => $inventories->total(),
            ],
        ]);
    }

    /**
     * Get single inventory with items
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $inventory = Inventory::query()
            ->byCompany($companyId)
            ->with(['warehouse', 'items.product', 'createdByUser'])
            ->findOrFail($id);

        return response()->json(['data' => $inventory]);
    }

    /**
     * Create new inventory
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'date' => 'required|date',
            'type' => 'in:full,partial',
            'notes' => 'nullable|string|max:2000',
            'product_ids' => 'array', // Для частичной инвентаризации
        ]);

        $validated['company_id'] = $companyId;
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'draft';

        $inventory = Inventory::create($validated);

        // Добавляем товары
        $this->inventoryService->addInventoryItems($inventory, $validated['product_ids'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $inventory->load(['items.product', 'warehouse']),
            'message' => 'Инвентаризация создана',
        ], 201);
    }

    /**
     * Update inventory (start, add items)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $inventory = Inventory::byCompany($companyId)->findOrFail($id);

        if ($inventory->is_applied) {
            return response()->json(['error' => 'Инвентаризация уже применена'], 400);
        }

        $validated = $request->validate([
            'status' => 'in:draft,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:2000',
        ]);

        $inventory->update($validated);

        if ($inventory->status === 'completed') {
            $inventory->calculateResults();
        }

        return response()->json([
            'success' => true,
            'data' => $inventory->fresh(['items.product', 'warehouse']),
            'message' => 'Инвентаризация обновлена',
        ]);
    }

    /**
     * Update inventory item (set actual quantity)
     */
    public function updateItem(Request $request, int $id, int $itemId): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $inventory = Inventory::byCompany($companyId)->findOrFail($id);

        if ($inventory->is_applied) {
            return response()->json(['error' => 'Инвентаризация уже применена'], 400);
        }

        $item = $inventory->items()->findOrFail($itemId);

        $validated = $request->validate([
            'actual_quantity' => 'required|numeric|min:0',
            'discrepancy_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $item->actual_quantity = $validated['actual_quantity'];
        $item->discrepancy_reason = $validated['discrepancy_reason'] ?? null;
        $item->notes = $validated['notes'] ?? null;
        $item->calculateDifference();
        $item->save();

        return response()->json([
            'success' => true,
            'data' => $item->fresh('product'),
            'message' => 'Позиция обновлена',
        ]);
    }

    /**
     * Batch update items
     */
    public function updateItems(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $inventory = Inventory::byCompany($companyId)->findOrFail($id);

        if ($inventory->is_applied) {
            return response()->json(['error' => 'Инвентаризация уже применена'], 400);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:inventory_items,id',
            'items.*.actual_quantity' => 'required|numeric|min:0',
            'items.*.discrepancy_reason' => 'nullable|string|max:255',
        ]);

        foreach ($validated['items'] as $itemData) {
            $item = $inventory->items()->find($itemData['id']);
            if ($item) {
                $item->actual_quantity = $itemData['actual_quantity'];
                $item->discrepancy_reason = $itemData['discrepancy_reason'] ?? null;
                $item->calculateDifference();
                $item->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Позиции обновлены',
        ]);
    }

    /**
     * Complete inventory and calculate results
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $inventory = Inventory::byCompany($companyId)->findOrFail($id);

        if ($inventory->is_applied) {
            return response()->json(['error' => 'Инвентаризация уже применена'], 400);
        }

        // Проверяем, что все позиции подсчитаны
        $pendingCount = $inventory->items()->where('status', 'pending')->count();
        if ($pendingCount > 0) {
            return response()->json([
                'error' => "Не подсчитано {$pendingCount} позиций",
            ], 400);
        }

        $inventory->status = 'completed';
        $inventory->save();
        $inventory->calculateResults();

        return response()->json([
            'success' => true,
            'data' => $inventory->fresh(['items.product']),
            'message' => 'Инвентаризация завершена',
        ]);
    }

    /**
     * Apply inventory results to stock
     */
    public function apply(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $inventory = Inventory::byCompany($companyId)->findOrFail($id);

        if ($inventory->status !== 'completed') {
            return response()->json(['error' => 'Инвентаризация не завершена'], 400);
        }

        if ($inventory->is_applied) {
            return response()->json(['error' => 'Результаты уже применены'], 400);
        }

        $success = $inventory->applyResults();

        if ($success) {
            return response()->json([
                'success' => true,
                'data' => $inventory->fresh(),
                'message' => 'Результаты применены к остаткам',
            ]);
        }

        return response()->json(['error' => 'Ошибка применения результатов'], 500);
    }

    /**
     * Delete inventory
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $inventory = Inventory::byCompany($companyId)->findOrFail($id);

        if ($inventory->is_applied) {
            return response()->json(['error' => 'Нельзя удалить применённую инвентаризацию'], 400);
        }

        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Инвентаризация удалена',
        ]);
    }

    /**
     * Get warehouses for inventory creation
     */
    public function warehouses(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $warehouses = \App\Models\Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'address']);

        return response()->json(['data' => $warehouses]);
    }
}
