<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfflineSaleController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $query = OfflineSale::byCompany($companyId)
            ->with(['counterparty:id,name', 'warehouse:id,name', 'creator:id,name']);

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->sale_type) {
            $query->where('sale_type', $request->sale_type);
        }
        if ($request->counterparty_id) {
            $query->where('counterparty_id', $request->counterparty_id);
        }
        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->from) {
            $query->whereDate('sale_date', '>=', $request->from);
        }
        if ($request->to) {
            $query->whereDate('sale_date', '<=', $request->to);
        }
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($search = $request->get('query')) {
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_phone', 'like', '%' . $search . '%');
            });
        }

        $perPage = min(max((int) ($request->per_page ?? 50), 1), 200);
        $page = max((int) ($request->page ?? 1), 1);

        $paginator = $query->orderByDesc('sale_date')->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->successResponse([
            'items' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $sale = OfflineSale::byCompany($companyId)
            ->with(['counterparty', 'warehouse', 'creator', 'items.sku', 'items.product'])
            ->findOrFail($id);

        return $this->successResponse($sale);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $data = $request->validate([
            'counterparty_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'sale_number' => ['nullable', 'string', 'max:100'],
            'sale_type' => ['required', 'in:retail,wholesale,direct'],
            'status' => ['nullable', 'in:draft,confirmed,shipped,delivered,cancelled,returned'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'payment_status' => ['nullable', 'in:unpaid,partial,paid'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'sale_date' => ['required', 'date'],
            'shipped_date' => ['nullable', 'date'],
            'delivered_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.sku_id' => ['nullable', 'integer'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.sku_code' => ['nullable', 'string'],
            'items.*.product_name' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['company_id'] = $companyId;
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? OfflineSale::STATUS_DRAFT;
        $data['currency_code'] = $data['currency_code'] ?? 'UZS';

        // Generate sale number if not provided
        if (empty($data['sale_number'])) {
            $data['sale_number'] = $this->generateSaleNumber($companyId);
        }

        $items = $data['items'] ?? [];
        unset($data['items']);

        $sale = DB::transaction(function () use ($data, $items) {
            $sale = OfflineSale::create($data);

            foreach ($items as $itemData) {
                $itemData['offline_sale_id'] = $sale->id;
                $itemData['line_total'] = $this->calculateLineTotal($itemData);
                OfflineSaleItem::create($itemData);
            }

            $sale->recalculateTotals();
            return $sale->fresh(['items']);
        });

        return $this->successResponse($sale);
    }

    public function update($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $sale = OfflineSale::byCompany($companyId)->findOrFail($id);

        // Only allow editing draft sales
        if ($sale->status !== OfflineSale::STATUS_DRAFT) {
            return $this->errorResponse('Can only edit draft sales', 'invalid_state', null, 422);
        }

        $data = $request->validate([
            'counterparty_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'sale_number' => ['nullable', 'string', 'max:100'],
            'sale_type' => ['nullable', 'in:retail,wholesale,direct'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'sale_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.sku_id' => ['nullable', 'integer'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.sku_code' => ['nullable', 'string'],
            'items.*.product_name' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = $data['items'] ?? null;
        unset($data['items']);

        $sale = DB::transaction(function () use ($sale, $data, $items) {
            $sale->update($data);

            if ($items !== null) {
                // Replace all items
                $sale->items()->delete();
                foreach ($items as $itemData) {
                    $itemData['offline_sale_id'] = $sale->id;
                    $itemData['line_total'] = $this->calculateLineTotal($itemData);
                    OfflineSaleItem::create($itemData);
                }
            }

            $sale->recalculateTotals();
            return $sale->fresh(['items']);
        });

        return $this->successResponse($sale);
    }

    public function confirm($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $sale = OfflineSale::byCompany($companyId)->findOrFail($id);

        if ($sale->status !== OfflineSale::STATUS_DRAFT) {
            return $this->errorResponse('Can only confirm draft sales', 'invalid_state', null, 422);
        }

        $sale->update(['status' => OfflineSale::STATUS_CONFIRMED]);

        return $this->successResponse($sale->fresh());
    }

    public function deliver($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $sale = OfflineSale::byCompany($companyId)->findOrFail($id);

        if (!in_array($sale->status, [OfflineSale::STATUS_CONFIRMED, OfflineSale::STATUS_SHIPPED])) {
            return $this->errorResponse('Sale must be confirmed or shipped', 'invalid_state', null, 422);
        }

        $sale->update([
            'status' => OfflineSale::STATUS_DELIVERED,
            'delivered_date' => now(),
            'stock_status' => 'sold',
            'stock_sold_at' => now(),
        ]);

        return $this->successResponse($sale->fresh());
    }

    public function cancel($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $sale = OfflineSale::byCompany($companyId)->findOrFail($id);

        if ($sale->status === OfflineSale::STATUS_CANCELLED) {
            return $this->errorResponse('Sale already cancelled', 'invalid_state', null, 422);
        }

        $sale->update([
            'status' => OfflineSale::STATUS_CANCELLED,
            'stock_status' => 'released',
            'stock_released_at' => now(),
        ]);

        return $this->successResponse($sale->fresh());
    }

    public function markPaid($id, Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $sale = OfflineSale::byCompany($companyId)->findOrFail($id);

        $data = $request->validate([
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $newPaidAmount = $sale->paid_amount + $data['paid_amount'];
        $paymentStatus = OfflineSale::PAYMENT_UNPAID;

        if ($newPaidAmount >= $sale->total_amount) {
            $paymentStatus = OfflineSale::PAYMENT_PAID;
            $newPaidAmount = $sale->total_amount;
        } elseif ($newPaidAmount > 0) {
            $paymentStatus = OfflineSale::PAYMENT_PARTIAL;
        }

        $sale->update([
            'paid_amount' => $newPaidAmount,
            'payment_status' => $paymentStatus,
            'payment_method' => $data['payment_method'] ?? $sale->payment_method,
        ]);

        return $this->successResponse($sale->fresh());
    }

    public function destroy($id)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $sale = OfflineSale::byCompany($companyId)->findOrFail($id);

        if ($sale->status !== OfflineSale::STATUS_DRAFT) {
            return $this->errorResponse('Can only delete draft sales', 'invalid_state', null, 422);
        }

        $sale->delete();

        return $this->successResponse(['deleted' => true]);
    }

    public function summary(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        if (!$companyId) {
            return $this->errorResponse('No company', 'forbidden', null, 403);
        }

        $from = $request->from ? \Carbon\Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->to ? \Carbon\Carbon::parse($request->to) : now()->endOfMonth();

        $query = OfflineSale::byCompany($companyId)->inPeriod($from, $to);

        // All sales
        $allSales = (clone $query)->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')->first();

        // Delivered (sold)
        $soldSales = (clone $query)->where('status', OfflineSale::STATUS_DELIVERED)
            ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')->first();

        // Cancelled
        $cancelledSales = (clone $query)->where('status', OfflineSale::STATUS_CANCELLED)
            ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')->first();

        // Returned
        $returnedSales = (clone $query)->where('status', OfflineSale::STATUS_RETURNED)
            ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')->first();

        // By type
        $byType = (clone $query)->where('status', OfflineSale::STATUS_DELIVERED)
            ->selectRaw('sale_type, COUNT(*) as cnt, SUM(total_amount) as amount')
            ->groupBy('sale_type')
            ->get()
            ->keyBy('sale_type');

        return $this->successResponse([
            'period' => ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')],
            'orders' => ['count' => (int) ($allSales?->cnt ?? 0), 'amount' => (float) ($allSales?->amount ?? 0)],
            'sold' => ['count' => (int) ($soldSales?->cnt ?? 0), 'amount' => (float) ($soldSales?->amount ?? 0)],
            'cancelled' => ['count' => (int) ($cancelledSales?->cnt ?? 0), 'amount' => (float) ($cancelledSales?->amount ?? 0)],
            'returns' => ['count' => (int) ($returnedSales?->cnt ?? 0), 'amount' => (float) ($returnedSales?->amount ?? 0)],
            'by_type' => [
                'retail' => ['count' => (int) ($byType['retail']?->cnt ?? 0), 'amount' => (float) ($byType['retail']?->amount ?? 0)],
                'wholesale' => ['count' => (int) ($byType['wholesale']?->cnt ?? 0), 'amount' => (float) ($byType['wholesale']?->amount ?? 0)],
                'direct' => ['count' => (int) ($byType['direct']?->cnt ?? 0), 'amount' => (float) ($byType['direct']?->amount ?? 0)],
            ],
        ]);
    }

    protected function generateSaleNumber(int $companyId): string
    {
        $lastSale = OfflineSale::byCompany($companyId)
            ->whereYear('created_at', now()->year)
            ->orderByDesc('id')
            ->first();

        $lastNumber = 0;
        if ($lastSale && preg_match('/(\d+)$/', $lastSale->sale_number, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return 'OS-' . now()->format('Y') . '-' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    }

    protected function calculateLineTotal(array $item): float
    {
        $subtotal = ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
        $discountAmount = $item['discount_amount'] ?? 0;
        if (!$discountAmount && isset($item['discount_percent'])) {
            $discountAmount = $subtotal * ($item['discount_percent'] / 100);
        }
        return max(0, $subtotal - $discountAmount);
    }
}
