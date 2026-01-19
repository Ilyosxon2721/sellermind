<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use App\Models\UzumFinanceOrder;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Models\WildberriesOrder;
use App\Models\YandexMarketOrder;
use App\Models\Warehouse\ChannelOrder;
use App\Services\CurrencyConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }
    /**
     * Get all sales from all sources with filters
     *
     * @param string $date_mode - "order_date" (по дате заказа) или "completion_date" (по дате выкупа)
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        // Setup currency service for company
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company) {
                $this->currencyService->forCompany($company);
            }
        }

        $dateFrom = $request->get('date_from', now()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $marketplace = $request->get('marketplace');
        $status = $request->get('status');
        $search = $request->get('search');
        $dateMode = $request->get('date_mode', 'order_date'); // order_date | completion_date
        $perPage = min((int) $request->get('per_page', 20), 100);

        // Build unified orders collection
        $orders = collect();

        // Get Uzum orders
        if (!$marketplace || $marketplace === 'uzum') {
            $uzumOrders = $this->getUzumOrders($companyId, $dateFrom, $dateTo, $status, $search, $dateMode);
            $orders = $orders->merge($uzumOrders);
        }

        // Get WB orders (RUB amounts will be converted)
        if (!$marketplace || $marketplace === 'wb') {
            $wbOrders = $this->getWbOrders($companyId, $dateFrom, $dateTo, $status, $search, $dateMode);
            $orders = $orders->merge($wbOrders);
        }

        // Get OZON orders
        if (!$marketplace || $marketplace === 'ozon') {
            $ozonOrders = $this->getOzonOrders($companyId, $dateFrom, $dateTo, $status, $search, $dateMode);
            $orders = $orders->merge($ozonOrders);
        }

        // Get Yandex Market orders
        if (!$marketplace || $marketplace === 'ym') {
            $ymOrders = $this->getYandexMarketOrders($companyId, $dateFrom, $dateTo, $status, $search, $dateMode);
            $orders = $orders->merge($ymOrders);
        }

        // Get manual/channel orders
        if (!$marketplace || $marketplace === 'manual') {
            $manualOrders = $this->getManualOrders($companyId, $dateFrom, $dateTo, $status, $search);
            $orders = $orders->merge($manualOrders);
        }

        // Sort by date descending
        $orders = $orders->sortByDesc('created_at')->values();

        // Calculate stats before pagination
        $stats = $this->calculateStats($orders);

        // Paginate
        $page = (int) $request->get('page', 1);
        $total = $orders->count();
        $paginatedOrders = $orders->forPage($page, $perPage)->values();

        // Get currency info
        $displayCurrency = $this->currencyService->getDisplayCurrency();
        $currencySymbol = $this->currencyService->getCurrencySymbol();

        return response()->json([
            'data' => $paginatedOrders,
            'stats' => $stats,
            'date_mode' => $dateMode,
            'currency' => [
                'code' => $displayCurrency,
                'symbol' => $currencySymbol,
            ],
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, ceil($total / $perPage)),
            ]
        ]);
    }
    
    /**
     * Get single order details
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        if (!$companyId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Parse ID format: marketplace_id (e.g., uzum_1787, wb_123)
        $parts = explode('_', $id, 2);
        if (count($parts) !== 2) {
            return response()->json(['error' => 'Invalid order ID format'], 400);
        }

        [$marketplace, $orderId] = $parts;

        $order = null;

        switch ($marketplace) {
            case 'uzum':
                $order = UzumOrder::with('account', 'items')
                    ->whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->find($orderId);
                if ($order) {
                    $order = $this->formatUzumOrderDetails($order);
                }
                break;

            case 'wb':
                // Try WildberriesOrder first (has real data)
                $order = WildberriesOrder::with('account')
                    ->whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->find($orderId);
                if ($order) {
                    $order = $this->formatWildberriesOrderDetails($order);
                } else {
                    // Fallback to WbOrder
                    $order = WbOrder::with('account')
                        ->whereHas('account', fn($q) => $q->where('company_id', $companyId))
                        ->find($orderId);
                    if ($order) {
                        $order = $this->formatWbOrderDetails($order);
                    }
                }
                break;

            case 'ozon':
                $order = OzonOrder::with('account', 'items')
                    ->whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->find($orderId);
                if ($order) {
                    $order = $this->formatOzonOrderDetails($order);
                }
                break;

            case 'ym':
                $order = YandexMarketOrder::with('account')
                    ->whereHas('account', fn($q) => $q->where('company_id', $companyId))
                    ->find($orderId);
                if ($order) {
                    $order = $this->formatYmOrderDetails($order);
                }
                break;

            case 'manual':
                $order = ChannelOrder::whereNull('channel_id')->find($orderId);
                if ($order) {
                    $order = $this->formatManualOrderDetails($order);
                }
                break;
        }

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json(['data' => $order]);
    }

    /**
     * Format Uzum order for details view
     */
    private function formatUzumOrderDetails($order): array
    {
        return [
            'id' => 'uzum_' . $order->id,
            'order_number' => $order->external_order_id,
            'marketplace' => 'uzum',
            'marketplace_label' => 'Uzum Market',
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'status' => $this->normalizeStatus($order->status_normalized),
            'status_label' => $this->getStatusLabel($order->status_normalized),
            'raw_status' => $order->uzum_status ?? $order->status,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_address' => $order->delivery_address,
            'total_amount' => (float) $order->total_amount,
            'currency' => $order->currency ?? 'UZS',
            'created_at' => $order->ordered_at?->toIso8601String(),
            'created_at_formatted' => $order->ordered_at?->format('d.m.Y H:i'),
            'items' => $order->items?->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->product_title ?? $item->name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
            ])->toArray() ?? [],
        ];
    }

    /**
     * Format WB order for details view
     */
    private function formatWbOrderDetails($order): array
    {
        return [
            'id' => 'wb_' . $order->id,
            'order_number' => $order->external_order_id ?? $order->rid,
            'marketplace' => 'wb',
            'marketplace_label' => 'Wildberries',
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'status' => $this->normalizeStatus($order->status_normalized ?? $order->status),
            'status_label' => $this->getStatusLabel($order->status_normalized ?? $order->status),
            'raw_status' => $order->wb_status,
            'supplier_status' => $order->supplier_status,
            'customer_name' => $order->customer_name,
            'delivery_address' => $order->delivery_address,
            'total_amount' => (float) ($order->total_amount ?? $order->price ?? 0),
            'currency' => $order->currency ?? 'RUB',
            'created_at' => $order->ordered_at?->toIso8601String(),
            'created_at_formatted' => $order->ordered_at?->format('d.m.Y H:i'),
            'article' => $order->article,
            'sku' => $order->sku,
            'brand' => $order->brand,
            'subject' => $order->subject,
            'warehouse' => $order->warehouse_name,
            'supply_id' => $order->supply_id,
        ];
    }

    /**
     * Format WildberriesOrder for details view
     */
    private function formatWildberriesOrderDetails(WildberriesOrder $order): array
    {
        $status = $this->normalizeWildberriesStatus($order);

        return [
            'id' => 'wb_' . $order->id,
            'order_number' => $order->srid ?? $order->order_id,
            'marketplace' => 'wb',
            'marketplace_label' => 'Wildberries',
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'status' => $status,
            'status_label' => $this->getStatusLabel($status),
            'raw_status' => $order->wb_status ?? $order->status,
            'customer_name' => $order->region_name,
            'delivery_address' => implode(', ', array_filter([
                $order->country_name,
                $order->oblast_okrug_name,
                $order->region_name,
            ])),
            'total_amount' => (float) ($order->for_pay ?? $order->finished_price ?? $order->total_price ?? 0),
            'currency' => 'RUB',
            'created_at' => $order->order_date?->toIso8601String(),
            'created_at_formatted' => $order->order_date?->format('d.m.Y H:i'),
            'article' => $order->supplier_article,
            'sku' => $order->barcode,
            'brand' => $order->brand,
            'subject' => $order->subject,
            'category' => $order->category,
            'warehouse' => $order->warehouse_name,
            'warehouse_type' => $order->warehouse_type,
            'supply_id' => $order->supply_id,
            'is_cancel' => $order->is_cancel,
            'is_return' => $order->is_return,
            'is_realization' => $order->is_realization,
            'price' => (float) $order->price,
            'discount_percent' => $order->discount_percent,
            'commission_percent' => (float) $order->commission_percent,
            'spp' => (float) $order->spp,
        ];
    }

    /**
     * Format Ozon order for details view
     */
    private function formatOzonOrderDetails($order): array
    {
        $isCompleted = $order->isSold();
        $isCancelled = $order->isCancelled();

        return [
            'id' => 'ozon_' . $order->id,
            'order_number' => $order->posting_number ?? $order->order_id,
            'marketplace' => 'ozon',
            'marketplace_label' => 'Ozon',
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'status' => $order->getNormalizedStatus(),
            'status_label' => $order->getStatusLabel(),
            'status_category' => $isCompleted ? 'completed' : ($isCancelled ? 'cancelled' : 'transit'),
            'is_revenue' => $isCompleted,
            'raw_status' => $order->status,
            'stock_status' => $order->stock_status,
            'customer_name' => $order->customer_name ?? ($order->customer_data['name'] ?? null),
            'delivery_address' => $order->delivery_address,
            'total_amount' => (float) ($order->total_price ?? 0),
            'currency' => 'RUB',
            'created_at' => $order->created_at_ozon?->toIso8601String(),
            'created_at_formatted' => $order->created_at_ozon?->format('d.m.Y H:i'),
            'stock_sold_at' => $order->stock_sold_at?->toIso8601String(),
            'products' => $order->getProductsList(),
        ];
    }

    /**
     * Format YM order for details view
     */
    private function formatYmOrderDetails($order): array
    {
        $isCompleted = $order->isSold();
        $isCancelled = $order->isCancelled();

        return [
            'id' => 'ym_' . $order->id,
            'order_number' => $order->order_id,
            'marketplace' => 'ym',
            'marketplace_label' => 'Yandex Market',
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'status' => $order->getNormalizedStatus(),
            'status_label' => $order->getStatusLabel(),
            'status_category' => $isCompleted ? 'completed' : ($isCancelled ? 'cancelled' : 'transit'),
            'is_revenue' => $isCompleted,
            'raw_status' => $order->status,
            'substatus' => $order->substatus,
            'substatus_label' => $order->getSubstatusLabel(),
            'stock_status' => $order->stock_status,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_type' => $order->delivery_type,
            'delivery_service' => $order->delivery_service,
            'total_amount' => (float) ($order->total_price ?? 0),
            'currency' => 'RUB',
            'created_at' => $order->created_at_ym?->toIso8601String(),
            'created_at_formatted' => $order->created_at_ym?->format('d.m.Y H:i'),
            'stock_sold_at' => $order->stock_sold_at?->toIso8601String(),
            'items_count' => $order->items_count,
        ];
    }

    /**
     * Format manual order for details view
     */
    private function formatManualOrderDetails($order): array
    {
        return [
            'id' => 'manual_' . $order->id,
            'order_number' => $order->external_order_id,
            'marketplace' => 'manual',
            'marketplace_label' => 'Ручной заказ',
            'status' => $this->normalizeStatus($order->status),
            'status_label' => $this->getStatusLabel($order->status),
            'raw_status' => $order->status,
            'customer_name' => $order->payload_json['customer_name'] ?? null,
            'total_amount' => (float) ($order->payload_json['total_amount'] ?? 0),
            'currency' => $order->payload_json['currency'] ?? 'UZS',
            'notes' => $order->payload_json['notes'] ?? null,
            'created_at' => ($order->created_at_channel ?? $order->created_at)?->toIso8601String(),
            'created_at_formatted' => ($order->created_at_channel ?? $order->created_at)?->format('d.m.Y H:i'),
        ];
    }

    /**
     * Get status label in Russian
     */
    private function getStatusLabel(?string $status): string
    {
        if (!$status) return 'Неизвестно';

        $labels = [
            'new' => 'Новый',
            'processing' => 'В обработке',
            'transit' => 'В транзите',
            'shipped' => 'Отправлен',
            'delivered' => 'Продан',
            'completed' => 'Продан',
            'cancelled' => 'Отменён',
            'in_assembly' => 'В сборке',
            'in_delivery' => 'В доставке',
            'accepted_uzum' => 'Принят Uzum',
            'in_supply' => 'В поставке',
            'waiting_pickup' => 'Ждёт выдачи',
            'awaiting_pickup' => 'В ПВЗ',
            'issued' => 'Выдан',
            'returns' => 'Возврат',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Create manual order/sale
     */
    public function storeManual(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:100',
            'customer_name' => 'nullable|string|max:255',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $companyId = $this->getCompanyId($request);
        
        // Create manual order in channel_orders table
        $order = ChannelOrder::create([
            'channel_id' => null, // null = manual entry
            'external_order_id' => $validated['order_number'],
            'status' => 'new',
            'payload_json' => [
                'customer_name' => $validated['customer_name'] ?? null,
                'total_amount' => $validated['total_amount'],
                'currency' => $validated['currency'] ?? 'UZS',
                'notes' => $validated['notes'] ?? null,
                'is_manual' => true,
                'company_id' => $companyId,
            ],
            'created_at_channel' => now(),
        ]);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => $order->external_order_id,
                'marketplace' => 'manual',
                'total_amount' => $validated['total_amount'],
                'currency' => $validated['currency'] ?? 'UZS',
                'status' => 'new',
                'created_at' => $order->created_at,
            ]
        ], 201);
    }
    
    /**
     * Get Uzum orders from Finance API (all types: FBO/FBS/DBS/EDBS)
     */
    private function getUzumOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search, string $dateMode = 'order_date'): \Illuminate\Support\Collection
    {
        if (!$companyId) {
            return collect();
        }

        // Try UzumFinanceOrder first (contains all order types for analytics)
        try {
            $hasFinanceOrders = UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))->exists();

            if ($hasFinanceOrders) {
                return $this->getUzumFinanceOrders($companyId, $dateFrom, $dateTo, $status, $search);
            }
        } catch (\Exception $e) {
            // Table might not exist yet, fall back to UzumOrder
        }

        // Fallback to UzumOrder (FBS only)
        $query = UzumOrder::query()
            ->whereHas('account', fn($query) => $query->where('company_id', $companyId))
            ->whereDate('ordered_at', '>=', $dateFrom)
            ->whereDate('ordered_at', '<=', $dateTo);

        if ($status) {
            $query->whereIn('status_normalized', $this->mapStatusToDbStatuses($status));
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('external_order_id', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        return $query->get()->map(fn($order) => [
            'id' => 'uzum_' . $order->id,
            'order_number' => $order->external_order_id,
            'created_at' => $order->ordered_at?->toIso8601String(),
            'marketplace' => 'uzum',
            'customer_name' => $order->customer_name,
            'total_amount' => (float) $order->total_amount,
            'currency' => $order->currency ?? 'UZS',
            'status' => $this->normalizeStatus($order->status_normalized),
            'raw_status' => $order->status,
        ]);
    }

    /**
     * Get Uzum Finance orders (all types: FBO/FBS/DBS/EDBS)
     *
     * Логика определения статуса по данным из БД:
     * - TO_WITHDRAW → delivered (продажа, деньги выведены = полноценный доход)
     * - PROCESSING + date_issued NOT NULL → delivered (продажа, товар выкуплен = доход)
     * - PROCESSING + date_issued NULL → transit (в транзите)
     * - CANCELED + date_issued NOT NULL → returned (возврат после выкупа)
     * - CANCELED + date_issued NULL → cancelled (отмена до выкупа)
     *
     * Логика фильтрации по дате:
     * - Для TO_WITHDRAW: по date_issued (дата вывода денег)
     * - Для PROCESSING + date_issued: по date_issued (дата выкупа)
     * - Для CANCELED с date_issued: по date_issued (дата возврата)
     * - Для остальных: по order_date (дата заказа)
     */
    private function getUzumFinanceOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search): \Illuminate\Support\Collection
    {
        $query = UzumFinanceOrder::query()
            ->whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->where(function($q) use ($dateFrom, $dateTo) {
                // Продажи (TO_WITHDRAW) - фильтруем по date_issued
                $q->where(function($sub) use ($dateFrom, $dateTo) {
                    $sub->where('status', 'TO_WITHDRAW')
                        ->whereDate('date_issued', '>=', $dateFrom)
                        ->whereDate('date_issued', '<=', $dateTo);
                })
                // Продажи (PROCESSING с date_issued) - фильтруем по date_issued (дата выкупа)
                ->orWhere(function($sub) use ($dateFrom, $dateTo) {
                    $sub->where('status', 'PROCESSING')
                        ->whereNotNull('date_issued')
                        ->whereDate('date_issued', '>=', $dateFrom)
                        ->whereDate('date_issued', '<=', $dateTo);
                })
                // Возвраты (CANCELED с date_issued) - по date_issued
                ->orWhere(function($sub) use ($dateFrom, $dateTo) {
                    $sub->where('status', 'CANCELED')
                        ->whereNotNull('date_issued')
                        ->whereDate('date_issued', '>=', $dateFrom)
                        ->whereDate('date_issued', '<=', $dateTo);
                })
                // В транзите (PROCESSING без date_issued) - по дате заказа
                ->orWhere(function($sub) use ($dateFrom, $dateTo) {
                    $sub->where('status', 'PROCESSING')
                        ->whereNull('date_issued')
                        ->whereDate('order_date', '>=', $dateFrom)
                        ->whereDate('order_date', '<=', $dateTo);
                })
                // Отмены (CANCELED без date_issued) - по дате заказа
                ->orWhere(function($sub) use ($dateFrom, $dateTo) {
                    $sub->where('status', 'CANCELED')
                        ->whereNull('date_issued')
                        ->whereDate('order_date', '>=', $dateFrom)
                        ->whereDate('order_date', '<=', $dateTo);
                });
            });

        if ($status) {
            // Map status filter
            $query->where(function($q) use ($status) {
                switch ($status) {
                    case 'delivered':
                    case 'completed':
                        // Продажа: TO_WITHDRAW или PROCESSING с date_issued
                        $q->where('status', 'TO_WITHDRAW')
                          ->orWhere(function($sub) {
                              $sub->where('status', 'PROCESSING')
                                  ->whereNotNull('date_issued');
                          });
                        break;
                    case 'transit':
                    case 'processing':
                        // В транзите: PROCESSING без date_issued
                        $q->where('status', 'PROCESSING')
                          ->whereNull('date_issued');
                        break;
                    case 'returned':
                        // Возврат: CANCELED с date_issued (был выкуплен, потом вернули)
                        $q->where('status', 'CANCELED')
                          ->whereNotNull('date_issued');
                        break;
                    case 'cancelled':
                        // Отмена: CANCELED без date_issued (отменили до выкупа)
                        $q->where('status', 'CANCELED')
                          ->whereNull('date_issued');
                        break;
                    default:
                        $q->whereIn('status_normalized', $this->mapStatusToDbStatuses($status));
                }
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhere('sku_title', 'like', "%{$search}%");
            });
        }

        return $query->with('account')->get()->map(function($order) {
            // Цены в UzumFinanceOrder хранятся в сумах
            $totalAmount = $order->sell_price * $order->amount;
            $sellerProfit = $order->seller_profit;

            // Логика определения статуса по реальным данным:
            // - TO_WITHDRAW → продажа (delivered) - деньги выведены = ДОХОД
            // - PROCESSING + date_issued → продажа (delivered) - товар выкуплен = ДОХОД
            // - PROCESSING + no date_issued → в транзите (transit)
            // - CANCELED + date_issued → возврат (returned) - был выкуплен, потом вернули
            // - CANCELED + no date_issued → отмена (cancelled)
            $isToWithdraw = $order->status === 'TO_WITHDRAW';
            $isProcessing = $order->status === 'PROCESSING';
            $isCanceled = $order->status === 'CANCELED';
            $hasDateIssued = $order->date_issued !== null;

            // Продажа = TO_WITHDRAW или (PROCESSING + date_issued)
            $isCompleted = $isToWithdraw || ($isProcessing && $hasDateIssued);
            $isTransit = $isProcessing && !$hasDateIssued;          // В транзите
            $isReturned = $isCanceled && $hasDateIssued;            // Возврат (после выкупа)
            $isCancelled = $isCanceled && !$hasDateIssued;          // Отмена (до выкупа)

            // Для возвратов сумма = sell_price * amount_returns
            $displayAmount = $isReturned
                ? $order->sell_price * $order->amount_returns
                : $totalAmount;

            // Определяем нормализованный статус
            $normalizedStatus = 'transit';
            if ($isCompleted) $normalizedStatus = 'delivered';
            elseif ($isReturned) $normalizedStatus = 'returned';
            elseif ($isCancelled) $normalizedStatus = 'cancelled';

            // Определяем категорию статуса
            $statusCategory = 'transit';
            if ($isCompleted) $statusCategory = 'completed';
            elseif ($isReturned || $isCancelled) $statusCategory = 'cancelled';

            return [
                'id' => 'uzum_finance_' . $order->id,
                'order_number' => (string) $order->order_id,
                'created_at' => $order->order_date?->toIso8601String(),
                'completion_date' => $order->date_issued?->toIso8601String(),
                'marketplace' => 'uzum',
                'account_name' => $order->account?->name ?? $order->account?->getDisplayName() ?? 'Uzum',
                'customer_name' => $order->sku_title,
                'total_amount' => $displayAmount,
                'seller_profit' => $sellerProfit,
                'currency' => 'UZS',
                'status' => $normalizedStatus,
                'status_category' => $statusCategory,
                'is_revenue' => $isCompleted,           // TO_WITHDRAW или PROCESSING+date_issued = доход
                'is_return' => $isReturned,
                'is_cancelled' => $isCancelled,
                'raw_status' => $order->status,
                'quantity' => $order->amount,
                'returns' => $order->amount_returns,
                'product_image' => $order->product_image_url,
            ];
        });
    }

    /**
     * Normalize Uzum Finance order status
     *
     * TO_WITHDRAW → delivered (продажа, деньги выведены)
     * COMPLETED → delivered (продажа)
     * PROCESSING + date_issued → delivered (продажа, товар выкуплен)
     * PROCESSING без date_issued → transit (в транзите)
     * CANCELED → cancelled
     *
     * NOTE: Эта функция не учитывает date_issued, поэтому для точного
     * определения статуса используйте логику в getUzumFinanceOrders()
     */
    private function normalizeUzumFinanceStatus(?string $status): string
    {
        if (!$status) return 'transit';

        return match (strtoupper($status)) {
            'PROCESSING' => 'transit', // Может быть продажей если есть date_issued
            'COMPLETED', 'TO_WITHDRAW' => 'delivered',
            'CANCELED' => 'cancelled',
            default => 'transit',
        };
    }
    
    /**
     * Get WB orders
     *
     * Логика определения статуса по данным WB:
     * - is_realization=true AND is_cancel=false → completed (продажа)
     * - is_realization=true AND is_cancel=true → returned (возврат после выкупа)
     * - is_realization=false AND is_cancel=true → cancelled (отмена до выкупа)
     * - is_realization=false AND is_cancel=false → transit (в транзите)
     *
     * Логика фильтрации по дате:
     * - Для продаж (is_realization=true, is_cancel=false): по last_change_date
     * - Для возвратов (is_realization=true, is_cancel=true): по last_change_date
     * - Для остальных: по order_date
     */
    private function getWbOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search, string $dateMode = 'order_date'): \Illuminate\Support\Collection
    {
        if (!$companyId) {
            return collect();
        }

        try {
            $query = WildberriesOrder::query()
                ->whereHas('account', fn($query) => $query->where('company_id', $companyId))
                ->where(function($q) use ($dateFrom, $dateTo) {
                    // Продажи (is_realization=true, is_cancel=false) - по last_change_date
                    $q->where(function($sub) use ($dateFrom, $dateTo) {
                        $sub->where('is_realization', true)
                            ->where('is_cancel', false)
                            ->whereDate('last_change_date', '>=', $dateFrom)
                            ->whereDate('last_change_date', '<=', $dateTo);
                    })
                    // Возвраты (is_realization=true, is_cancel=true) - по last_change_date
                    ->orWhere(function($sub) use ($dateFrom, $dateTo) {
                        $sub->where('is_realization', true)
                            ->where('is_cancel', true)
                            ->whereDate('last_change_date', '>=', $dateFrom)
                            ->whereDate('last_change_date', '<=', $dateTo);
                    })
                    // В транзите (is_realization=false, is_cancel=false) - по order_date
                    ->orWhere(function($sub) use ($dateFrom, $dateTo) {
                        $sub->where('is_realization', false)
                            ->where('is_cancel', false)
                            ->whereDate('order_date', '>=', $dateFrom)
                            ->whereDate('order_date', '<=', $dateTo);
                    })
                    // Отмены (is_realization=false, is_cancel=true) - по order_date
                    ->orWhere(function($sub) use ($dateFrom, $dateTo) {
                        $sub->where('is_realization', false)
                            ->where('is_cancel', true)
                            ->whereDate('order_date', '>=', $dateFrom)
                            ->whereDate('order_date', '<=', $dateTo);
                    });
                });

            if ($status) {
                $query->where(function($q) use ($status) {
                    switch ($status) {
                        case 'delivered':
                        case 'completed':
                            // Продажа: is_realization=true AND is_cancel=false
                            $q->where('is_realization', true)
                              ->where('is_cancel', false);
                            break;
                        case 'returned':
                            // Возврат: is_realization=true AND is_cancel=true
                            $q->where('is_realization', true)
                              ->where('is_cancel', true);
                            break;
                        case 'cancelled':
                            // Отмена + Возврат для общего фильтра "отменённые"
                            $q->where('is_cancel', true);
                            break;
                        case 'transit':
                        case 'processing':
                            // В транзите: is_realization=false AND is_cancel=false
                            $q->where('is_realization', false)
                              ->where('is_cancel', false);
                            break;
                        default:
                            $dbStatuses = $this->mapStatusToDbStatuses($status);
                            $q->whereIn('status', $dbStatuses)
                              ->orWhereIn('wb_status', $dbStatuses);
                    }
                });
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('srid', 'like', "%{$search}%")
                      ->orWhere('order_id', 'like', "%{$search}%")
                      ->orWhere('supplier_article', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            }

            return $query->with('account')->get()->map(function($order) {
                $amountRub = (float) ($order->for_pay ?? $order->finished_price ?? $order->total_price ?? 0);
                $amountConverted = $this->currencyService->convertFromRub($amountRub);

                // Определяем статус по флагам:
                // - is_realization=true, is_cancel=false → продажа (completed)
                // - is_realization=true, is_cancel=true → возврат (returned)
                // - is_realization=false, is_cancel=true → отмена (cancelled)
                // - is_realization=false, is_cancel=false → в транзите (transit)
                $isCompleted = $order->is_realization && !$order->is_cancel;
                $isReturned = $order->is_realization && $order->is_cancel;
                $isCancelled = !$order->is_realization && $order->is_cancel;
                $isTransit = !$order->is_realization && !$order->is_cancel;

                // Нормализованный статус
                $normalizedStatus = 'transit';
                if ($isCompleted) $normalizedStatus = 'completed';
                elseif ($isReturned) $normalizedStatus = 'returned';
                elseif ($isCancelled) $normalizedStatus = 'cancelled';

                // Категория статуса
                $statusCategory = 'transit';
                if ($isCompleted) $statusCategory = 'completed';
                elseif ($isReturned || $isCancelled) $statusCategory = 'cancelled';

                return [
                    'id' => 'wb_' . $order->id,
                    'order_number' => (string) ($order->srid ?? $order->order_id ?? $order->id),
                    'created_at' => $order->order_date?->toIso8601String(),
                    'completion_date' => ($isCompleted || $isReturned) ? $order->last_change_date?->toIso8601String() : null,
                    'marketplace' => 'wb',
                    'account_name' => $order->account?->name ?? $order->account?->getDisplayName() ?? 'Wildberries',
                    'customer_name' => $order->region_name,
                    'total_amount' => $amountConverted,
                    'total_amount_rub' => $amountRub,
                    'original_currency' => 'RUB',
                    'currency' => $this->currencyService->getDisplayCurrency(),
                    'status' => $normalizedStatus,
                    'status_category' => $statusCategory,
                    'is_revenue' => $isCompleted,           // Только продажи = доход
                    'is_return' => $isReturned,             // Возврат после выкупа
                    'is_cancelled' => $isCancelled,         // Отмена до выкупа
                    'raw_status' => $order->wb_status ?? $order->status,
                ];
            });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Normalize WildberriesOrder status based on flags and status fields
     */
    private function normalizeWildberriesStatus(WildberriesOrder $order): string
    {
        // Check cancellation/return flags first
        if ($order->is_cancel) {
            return 'cancelled';
        }
        if ($order->is_return) {
            return 'cancelled';
        }

        // Check if completed sale
        if ($order->is_realization) {
            return 'completed';
        }

        // Map wb_status
        $wbStatus = $order->wb_status;
        if ($wbStatus) {
            $statusMap = [
                'waiting' => 'new',
                'sorted' => 'processing',
                'sold' => 'completed',
                'delivered' => 'completed',
                'canceled' => 'cancelled',
                'canceled_by_client' => 'cancelled',
                'defect' => 'cancelled',
                'ready_for_pickup' => 'processing',
                'on_way_to_client' => 'processing',
            ];

            if (isset($statusMap[$wbStatus])) {
                return $statusMap[$wbStatus];
            }
        }

        // Fallback to status field
        return $this->normalizeStatus($order->status ?? 'new');
    }
    
    /**
     * Get OZON orders
     *
     * Статусы:
     * - isSold() = true → delivered (продажа, доход)
     * - isInTransit() = true → transit (в транзите, ещё не доход)
     * - isAwaitingPickup() = true → awaiting_pickup (в ПВЗ)
     * - isCancelled() = true → cancelled
     *
     * Логика фильтрации по дате:
     * - Для проданных: по stock_sold_at (дата продажи)
     * - Для остальных: по created_at_ozon (дата заказа)
     */
    private function getOzonOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search, string $dateMode = 'order_date'): \Illuminate\Support\Collection
    {
        if (!$companyId) {
            return collect();
        }

        try {
            $query = OzonOrder::query()
                ->whereHas('account', fn($query) => $query->where('company_id', $companyId))
                ->where(function($q) use ($dateFrom, $dateTo) {
                    // Проданные товары - фильтруем по дате продажи (stock_sold_at)
                    $q->where(function($sub) use ($dateFrom, $dateTo) {
                        $sub->whereIn('stock_status', ['sold'])
                            ->whereNotNull('stock_sold_at')
                            ->whereDate('stock_sold_at', '>=', $dateFrom)
                            ->whereDate('stock_sold_at', '<=', $dateTo);
                    })
                    // Остальные - по дате заказа
                    ->orWhere(function($sub) use ($dateFrom, $dateTo) {
                        $sub->where(function($inner) {
                            $inner->whereNull('stock_status')
                                ->orWhereNotIn('stock_status', ['sold']);
                        })
                        ->whereDate('created_at_ozon', '>=', $dateFrom)
                        ->whereDate('created_at_ozon', '<=', $dateTo);
                    });
                });

            // Apply status filter using scopes
            if ($status) {
                switch ($status) {
                    case 'delivered':
                    case 'completed':
                        $query->sold();
                        break;
                    case 'transit':
                    case 'processing':
                        $query->inTransit();
                        break;
                    case 'awaiting_pickup':
                        $query->awaitingPickup();
                        break;
                    case 'cancelled':
                        $query->cancelled();
                        break;
                    default:
                        $query->whereIn('status', $this->mapStatusToDbStatuses($status));
                }
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('posting_number', 'like', "%{$search}%")
                      ->orWhere('order_id', 'like', "%{$search}%");
                });
            }

            return $query->with('account')->get()->map(function($order) {
                $amountRub = (float) ($order->total_price ?? 0);
                $amountConverted = $this->currencyService->convertFromRub($amountRub);

                // Определяем категорию статуса через методы модели
                $isCompleted = $order->isSold();
                $isCancelled = $order->isCancelled();
                $isAwaitingPickup = $order->isAwaitingPickup();

                // Определяем status_category
                $statusCategory = 'transit';
                if ($isCompleted) {
                    $statusCategory = 'completed';
                } elseif ($isCancelled) {
                    $statusCategory = 'cancelled';
                } elseif ($isAwaitingPickup) {
                    $statusCategory = 'awaiting_pickup';
                }

                return [
                    'id' => 'ozon_' . $order->id,
                    'order_number' => (string) ($order->posting_number ?? $order->order_id),
                    'created_at' => $order->created_at_ozon?->toIso8601String(),
                    'completion_date' => $order->stock_sold_at?->toIso8601String(),
                    'marketplace' => 'ozon',
                    'account_name' => $order->account?->name ?? $order->account?->getDisplayName() ?? 'Ozon',
                    'customer_name' => $order->customer_name ?? ($order->customer_data['name'] ?? null),
                    'total_amount' => $amountConverted,
                    'total_amount_rub' => $amountRub,
                    'original_currency' => 'RUB',
                    'currency' => $this->currencyService->getDisplayCurrency(),
                    'status' => $order->getNormalizedStatus(),
                    'status_category' => $statusCategory,
                    'is_revenue' => $isCompleted, // Только sold заказы считаем как доход
                    'is_awaiting_pickup' => $isAwaitingPickup,
                    'raw_status' => $order->status,
                    'stock_status' => $order->stock_status,
                ];
            });
        } catch (\Exception $e) {
            \Log::error('OZON orders fetch error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get Yandex Market orders
     *
     * Статусы:
     * - isSold() = true → delivered (продажа, доход)
     * - isInTransit() = true → transit (в транзите, ещё не доход)
     * - isAwaitingPickup() = true → awaiting_pickup (в ПВЗ)
     * - isCancelled() = true → cancelled
     *
     * Логика фильтрации по дате:
     * - Для проданных: по stock_sold_at (дата продажи)
     * - Для остальных: по created_at_ym (дата заказа)
     */
    private function getYandexMarketOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search, string $dateMode = 'order_date'): \Illuminate\Support\Collection
    {
        if (!$companyId) {
            return collect();
        }

        try {
            $query = YandexMarketOrder::query()
                ->whereHas('account', fn($query) => $query->where('company_id', $companyId))
                ->where(function($q) use ($dateFrom, $dateTo) {
                    // Проданные товары - фильтруем по дате продажи (stock_sold_at)
                    $q->where(function($sub) use ($dateFrom, $dateTo) {
                        $sub->whereIn('stock_status', ['sold'])
                            ->whereNotNull('stock_sold_at')
                            ->whereDate('stock_sold_at', '>=', $dateFrom)
                            ->whereDate('stock_sold_at', '<=', $dateTo);
                    })
                    // Остальные - по дате заказа
                    ->orWhere(function($sub) use ($dateFrom, $dateTo) {
                        $sub->where(function($inner) {
                            $inner->whereNull('stock_status')
                                ->orWhereNotIn('stock_status', ['sold']);
                        })
                        ->whereDate('created_at_ym', '>=', $dateFrom)
                        ->whereDate('created_at_ym', '<=', $dateTo);
                    });
                });

            // Apply status filter using scopes
            if ($status) {
                switch ($status) {
                    case 'delivered':
                    case 'completed':
                        $query->sold();
                        break;
                    case 'transit':
                    case 'processing':
                        $query->inTransit();
                        break;
                    case 'awaiting_pickup':
                        $query->awaitingPickup();
                        break;
                    case 'cancelled':
                        $query->cancelled();
                        break;
                    default:
                        $query->whereIn('status', $this->mapStatusToDbStatuses($status));
                }
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            }

            return $query->with('account')->get()->map(function($order) {
                $amountRub = (float) ($order->total_price ?? 0);
                $amountConverted = $this->currencyService->convertFromRub($amountRub);

                // Определяем категорию статуса через методы модели
                $isCompleted = $order->isSold();
                $isCancelled = $order->isCancelled();
                $isAwaitingPickup = $order->isAwaitingPickup();

                // Определяем status_category
                $statusCategory = 'transit';
                if ($isCompleted) {
                    $statusCategory = 'completed';
                } elseif ($isCancelled) {
                    $statusCategory = 'cancelled';
                } elseif ($isAwaitingPickup) {
                    $statusCategory = 'awaiting_pickup';
                }

                return [
                    'id' => 'ym_' . $order->id,
                    'order_number' => (string) $order->order_id,
                    'created_at' => $order->created_at_ym?->toIso8601String(),
                    'completion_date' => $order->stock_sold_at?->toIso8601String(),
                    'marketplace' => 'ym',
                    'account_name' => $order->account?->name ?? $order->account?->getDisplayName() ?? 'Yandex Market',
                    'customer_name' => $order->customer_name,
                    'total_amount' => $amountConverted,
                    'total_amount_rub' => $amountRub,
                    'original_currency' => 'RUB',
                    'currency' => $this->currencyService->getDisplayCurrency(),
                    'status' => $order->getNormalizedStatus(),
                    'status_category' => $statusCategory,
                    'is_revenue' => $isCompleted, // Только sold заказы считаем как доход
                    'is_awaiting_pickup' => $isAwaitingPickup,
                    'raw_status' => $order->status,
                    'stock_status' => $order->stock_status,
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Yandex Market orders fetch error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get manual/channel orders
     */
    private function getManualOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search): \Illuminate\Support\Collection
    {
        if (!$companyId) {
            return collect();
        }

        try {
            // Check if table exists
            if (!DB::getSchemaBuilder()->hasTable('channel_orders')) {
                return collect();
            }

            $query = ChannelOrder::query()
                ->whereNull('channel_id') // Manual orders have no channel
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->where(function($q) use ($companyId) {
                    $q->whereJsonContains('payload_json->company_id', $companyId)
                      ->orWhereJsonContains('payload_json->is_manual', true);
                });

            if ($status) {
                $query->whereIn('status', $this->mapStatusToDbStatuses($status));
            }

            if ($search) {
                $query->where('external_order_id', 'like', "%{$search}%");
            }

            return $query->get()->map(fn($order) => [
                'id' => 'manual_' . $order->id,
                'order_number' => (string) $order->external_order_id,
                'created_at' => ($order->created_at_channel ?? $order->created_at)?->toIso8601String(),
                'marketplace' => 'manual',
                'account_name' => 'Ручной заказ',
                'customer_name' => $order->payload_json['customer_name'] ?? null,
                'total_amount' => (float) ($order->payload_json['total_amount'] ?? 0),
                'currency' => $order->payload_json['currency'] ?? 'UZS',
                'status' => $this->normalizeStatus($order->status),
                'is_revenue' => true, // Ручные заказы всегда считаем как доход
                'raw_status' => $order->status,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Manual orders fetch skipped: ' . $e->getMessage());
            return collect();
        }
    }
    
    /**
     * Calculate statistics
     *
     * Категории:
     * - completed (is_revenue=true): Продажи - полноценный доход
     * - transit: В транзите - ещё не доход
     * - awaiting_pickup: В ПВЗ - ожидает выкупа
     * - cancelled: Отменённые - не считаем
     */
    private function calculateStats(\Illuminate\Support\Collection $orders): array
    {
        $totalOrders = $orders->count();

        // Фильтруем по категориям статуса
        $completedOrders = $orders->filter(fn($o) => ($o['is_revenue'] ?? false) === true);
        $awaitingPickupOrders = $orders->filter(fn($o) =>
            ($o['is_awaiting_pickup'] ?? false) === true ||
            ($o['status_category'] ?? '') === 'awaiting_pickup'
        );
        $transitOrders = $orders->filter(fn($o) =>
            ($o['is_revenue'] ?? false) === false &&
            ($o['is_return'] ?? false) === false &&
            ($o['is_cancelled'] ?? false) === false &&
            ($o['is_awaiting_pickup'] ?? false) === false &&
            ($o['status_category'] ?? '') !== 'awaiting_pickup' &&
            !in_array($o['status'], ['cancelled', 'canceled', 'returned'])
        );
        $cancelledOrders = $orders->filter(fn($o) =>
            in_array($o['status'], ['cancelled', 'canceled', 'returned']) ||
            ($o['is_return'] ?? false) === true ||
            ($o['is_cancelled'] ?? false) === true
        );

        // Продажи (ДОХОД) - только завершённые
        $salesCount = $completedOrders->count();
        $salesAmount = $completedOrders->sum('total_amount');

        // В транзите (ещё не доход)
        $transitCount = $transitOrders->count();
        $transitAmount = $transitOrders->sum('total_amount');

        // В ПВЗ (ожидает выкупа)
        $awaitingPickupCount = $awaitingPickupOrders->count();
        $awaitingPickupAmount = $awaitingPickupOrders->sum('total_amount');

        // Отменённые
        $cancelledCount = $cancelledOrders->count();
        $cancelledAmount = $cancelledOrders->sum('total_amount');

        // Общие суммы для обратной совместимости
        $totalAmount = $orders->sum('total_amount');
        $totalRevenue = $salesAmount; // Теперь доход = только завершённые

        // Потенциальный доход (в пути + в ПВЗ)
        $potentialRevenue = $transitAmount + $awaitingPickupAmount;
        // Подтверждённый доход (выкуплено)
        $confirmedRevenue = $salesAmount;

        $marketplaceOrders = $orders->filter(fn($o) => $o['marketplace'] !== 'manual')->count();
        $manualOrders = $orders->filter(fn($o) => $o['marketplace'] === 'manual')->count();

        $avgOrderValue = $salesCount > 0 ? round($salesAmount / $salesCount) : 0;

        // Все маркетплейсы - всегда показываем все 5, даже с 0 заказами
        $allMarketplaces = ['uzum', 'wb', 'ozon', 'ym', 'manual'];
        $labels = ['uzum' => 'Uzum', 'wb' => 'WB', 'ozon' => 'Ozon', 'ym' => 'YM', 'manual' => 'Ручные'];

        $ordersByMarketplace = $orders->groupBy('marketplace');

        $byMarketplace = collect($allMarketplaces)->map(function($mp) use ($ordersByMarketplace, $labels) {
            $group = $ordersByMarketplace->get($mp, collect());

            $completed = $group->filter(fn($o) => ($o['is_revenue'] ?? false) === true);
            $awaitingPickup = $group->filter(fn($o) =>
                ($o['is_awaiting_pickup'] ?? false) === true ||
                ($o['status_category'] ?? '') === 'awaiting_pickup'
            );
            $transit = $group->filter(fn($o) =>
                ($o['is_revenue'] ?? false) === false &&
                ($o['is_return'] ?? false) === false &&
                ($o['is_cancelled'] ?? false) === false &&
                ($o['is_awaiting_pickup'] ?? false) === false &&
                ($o['status_category'] ?? '') !== 'awaiting_pickup' &&
                !in_array($o['status'], ['cancelled', 'canceled', 'returned'])
            );
            $cancelled = $group->filter(fn($o) =>
                in_array($o['status'], ['cancelled', 'canceled', 'returned']) ||
                ($o['is_return'] ?? false) === true ||
                ($o['is_cancelled'] ?? false) === true
            );

            return [
                'name' => $mp,
                'label' => $labels[$mp] ?? $mp,
                'count' => $group->count(),
                // Продажи (доход)
                'salesCount' => $completed->count(),
                'salesAmount' => $completed->sum('total_amount'),
                // В транзите
                'transitCount' => $transit->count(),
                'transitAmount' => $transit->sum('total_amount'),
                // В ПВЗ
                'awaitingPickupCount' => $awaitingPickup->count(),
                'awaitingPickupAmount' => $awaitingPickup->sum('total_amount'),
                // Отменённые
                'cancelledCount' => $cancelled->count(),
                'cancelledAmount' => $cancelled->sum('total_amount'),
                // Для обратной совместимости
                'amount' => $completed->sum('total_amount'),
            ];
        })->values()->toArray();

        return [
            'totalOrders' => $totalOrders,
            'totalAmount' => $totalAmount,

            // Продажи (ДОХОД) - только завершённые
            'salesCount' => $salesCount,
            'salesAmount' => $salesAmount,
            'totalRevenue' => $totalRevenue, // = salesAmount для обратной совместимости

            // В транзите (ещё не доход)
            'transitCount' => $transitCount,
            'transitAmount' => $transitAmount,

            // В ПВЗ (ожидает выкупа)
            'awaitingPickupCount' => $awaitingPickupCount,
            'awaitingPickupAmount' => $awaitingPickupAmount,

            // Потенциальный / Подтверждённый доход
            'potentialRevenue' => $potentialRevenue,
            'confirmedRevenue' => $confirmedRevenue,

            // Отменённые
            'cancelledOrders' => $cancelledCount,
            'cancelledAmount' => $cancelledAmount,

            'avgOrderValue' => $avgOrderValue,
            'marketplaceOrders' => $marketplaceOrders,
            'manualOrders' => $manualOrders,
            'byMarketplace' => $byMarketplace,
        ];
    }
    
    /**
     * Get company ID from request
     */
    private function getCompanyId(Request $request): ?int
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        // Try direct company_id first, then fallback to companies relationship
        return $user->company_id ?? $user->companies()->first()?->id;
    }
    
    /**
     * Map frontend status to DB status
     */
    /**
     * Get array of DB statuses that match the frontend filter status
     */
    private function mapStatusToDbStatuses(string $status): array
    {
        return match ($status) {
            'new' => ['new', 'created', 'pending', 'waiting'],
            'processing' => ['processing', 'accepted', 'confirmed', 'in_work', 'assembling', 'in_assembly', 'accepted_uzum', 'in_supply'],
            'transit' => ['transit', 'processing', 'shipped', 'sent', 'in_delivery', 'delivering', 'waiting_pickup'], // В транзите
            'shipped' => ['shipped', 'sent', 'in_delivery', 'delivering', 'waiting_pickup'],
            'delivered' => ['delivered', 'completed', 'done', 'received', 'issued'], // Продажи (доход)
            'completed' => ['delivered', 'completed', 'done', 'received', 'issued'], // Алиас для delivered
            'cancelled' => ['cancelled', 'canceled', 'rejected', 'returned', 'returns'],
            default => [$status],
        };
    }

    private function mapStatus(string $status): string
    {
        // Keep for backwards compatibility - returns single status
        $statuses = $this->mapStatusToDbStatuses($status);
        return $statuses[0] ?? $status;
    }
    
    /**
     * Normalize status for frontend
     */
    private function normalizeStatus(?string $status): string
    {
        if (!$status) return 'new';
        
        $status = strtolower($status);
        
        // Map various statuses to standard ones
        if (in_array($status, ['new', 'created', 'pending', 'waiting'])) {
            return 'new';
        }
        if (in_array($status, ['processing', 'accepted', 'confirmed', 'in_work', 'assembling', 'in_assembly', 'accepted_uzum', 'in_supply'])) {
            return 'processing';
        }
        if (in_array($status, ['shipped', 'sent', 'in_delivery', 'delivering', 'waiting_pickup'])) {
            return 'shipped';
        }
        if (in_array($status, ['delivered', 'completed', 'done', 'received', 'issued'])) {
            return 'delivered';
        }
        if (in_array($status, ['cancelled', 'canceled', 'rejected', 'returned', 'returns'])) {
            return 'cancelled';
        }
        
        return 'processing'; // Default to processing instead of new
    }

    /**
     * Получить статус синхронизации финансовых заказов Uzum
     */
    public function syncStatus(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        if (!$companyId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Получаем все аккаунты Uzum для компании
        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'uzum')
            ->where('is_active', true)
            ->get();

        $syncStatuses = [];
        $hasActiveSync = false;

        foreach ($accounts as $account) {
            $status = \App\Jobs\SyncUzumFinanceOrdersJob::getSyncStatus($account->id);

            if ($status) {
                $syncStatuses[] = array_merge($status, [
                    'account_name' => $account->name ?? $account->getDisplayName(),
                ]);

                if (in_array($status['status'] ?? '', ['running', 'rate_limited'])) {
                    $hasActiveSync = true;
                }
            }
        }

        return response()->json([
            'has_active_sync' => $hasActiveSync,
            'syncs' => $syncStatuses,
        ]);
    }

    /**
     * Запустить синхронизацию финансовых заказов Uzum вручную
     */
    public function triggerSync(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        if (!$companyId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $fullSync = $request->boolean('full_sync', false);
        $days = (int) $request->get('days', 90);
        $accountId = $request->get('account_id');

        // Получаем аккаунты Uzum для компании
        $query = MarketplaceAccount::where('company_id', $companyId)
            ->where('marketplace', 'uzum')
            ->where('is_active', true);

        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет активных аккаунтов Uzum',
            ], 404);
        }

        $dispatched = 0;
        foreach ($accounts as $account) {
            // Проверяем, нет ли уже активной синхронизации
            $status = \App\Jobs\SyncUzumFinanceOrdersJob::getSyncStatus($account->id);
            if ($status && in_array($status['status'] ?? '', ['running', 'rate_limited'])) {
                continue; // Пропускаем, уже синхронизируется
            }

            \App\Jobs\SyncUzumFinanceOrdersJob::dispatch($account, $fullSync, $days);
            $dispatched++;
        }

        return response()->json([
            'success' => true,
            'message' => $dispatched > 0
                ? "Синхронизация запущена для {$dispatched} аккаунтов"
                : 'Синхронизация уже выполняется',
            'dispatched' => $dispatched,
        ]);
    }
}
