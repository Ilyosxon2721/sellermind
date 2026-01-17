<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
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
        $perPage = min((int) $request->get('per_page', 20), 100);

        // Build unified orders collection
        $orders = collect();

        // Get Uzum orders
        if (!$marketplace || $marketplace === 'uzum') {
            $uzumOrders = $this->getUzumOrders($companyId, $dateFrom, $dateTo, $status, $search);
            $orders = $orders->merge($uzumOrders);
        }

        // Get WB orders (RUB amounts will be converted)
        if (!$marketplace || $marketplace === 'wb') {
            $wbOrders = $this->getWbOrders($companyId, $dateFrom, $dateTo, $status, $search);
            $orders = $orders->merge($wbOrders);
        }

        // Get OZON orders
        if (!$marketplace || $marketplace === 'ozon') {
            $ozonOrders = $this->getOzonOrders($companyId, $dateFrom, $dateTo, $status, $search);
            $orders = $orders->merge($ozonOrders);
        }

        // Get Yandex Market orders
        if (!$marketplace || $marketplace === 'ym') {
            $ymOrders = $this->getYandexMarketOrders($companyId, $dateFrom, $dateTo, $status, $search);
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
        return [
            'id' => 'ozon_' . $order->id,
            'order_number' => $order->posting_number ?? $order->order_id,
            'marketplace' => 'ozon',
            'marketplace_label' => 'Ozon',
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'status' => $this->normalizeStatus($order->status),
            'status_label' => $this->getStatusLabel($order->status),
            'raw_status' => $order->status,
            'customer_name' => $order->customer_name ?? ($order->customer_data['name'] ?? null),
            'delivery_address' => $order->delivery_address,
            'total_amount' => (float) $order->total_amount,
            'currency' => 'RUB',
            'created_at' => $order->created_at_ozon?->toIso8601String(),
            'created_at_formatted' => $order->created_at_ozon?->format('d.m.Y H:i'),
            'items' => $order->items?->map(fn($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
            ])->toArray() ?? [],
        ];
    }

    /**
     * Format YM order for details view
     */
    private function formatYmOrderDetails($order): array
    {
        return [
            'id' => 'ym_' . $order->id,
            'order_number' => $order->order_id,
            'marketplace' => 'ym',
            'marketplace_label' => 'Yandex Market',
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'status' => $this->normalizeStatus($order->status),
            'status_label' => $this->getStatusLabel($order->status),
            'raw_status' => $order->status,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_address' => $order->delivery_address,
            'total_amount' => (float) $order->total_amount,
            'currency' => 'RUB',
            'created_at' => $order->created_at_ym?->toIso8601String(),
            'created_at_formatted' => $order->created_at_ym?->format('d.m.Y H:i'),
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
            'shipped' => 'Отправлен',
            'delivered' => 'Доставлен',
            'cancelled' => 'Отменён',
            'in_assembly' => 'В сборке',
            'in_delivery' => 'В доставке',
            'completed' => 'Выполнен',
            'accepted_uzum' => 'Принят Uzum',
            'in_supply' => 'В поставке',
            'waiting_pickup' => 'Ждёт выдачи',
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
     * Get Uzum orders
     */
    private function getUzumOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search): \Illuminate\Support\Collection
    {
        if (!$companyId) {
            return collect();
        }

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
     * Get WB orders
     */
    private function getWbOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search): \Illuminate\Support\Collection
    {
        if (!$companyId) {
            return collect();
        }

        try {
            // Use WildberriesOrder model (wildberries_orders table) which has real data
            $query = WildberriesOrder::query()
                ->whereHas('account', fn($query) => $query->where('company_id', $companyId))
                ->whereDate('order_date', '>=', $dateFrom)
                ->whereDate('order_date', '<=', $dateTo);

            if ($status) {
                // Map status filter to WildberriesOrder fields
                $query->where(function($q) use ($status) {
                    $dbStatuses = $this->mapStatusToDbStatuses($status);
                    $q->whereIn('status', $dbStatuses)
                      ->orWhereIn('wb_status', $dbStatuses);

                    // Handle cancelled status via is_cancel flag
                    if ($status === 'cancelled') {
                        $q->orWhere('is_cancel', true);
                    }
                    // Handle completed status
                    if ($status === 'completed') {
                        $q->orWhere(function($sub) {
                            $sub->where('is_realization', true)
                                ->where('is_cancel', false)
                                ->where('is_return', false);
                        });
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

            return $query->get()->map(function($order) {
                $amountRub = (float) ($order->for_pay ?? $order->finished_price ?? $order->total_price ?? 0);
                $amountConverted = $this->currencyService->convertFromRub($amountRub);

                return [
                    'id' => 'wb_' . $order->id,
                    'order_number' => $order->srid ?? $order->order_id ?? $order->id,
                    'created_at' => $order->order_date?->toIso8601String(),
                    'marketplace' => 'wb',
                    'customer_name' => $order->region_name,
                    'total_amount' => $amountConverted,
                    'total_amount_rub' => $amountRub,
                    'original_currency' => 'RUB',
                    'currency' => $this->currencyService->getDisplayCurrency(),
                    'status' => $this->normalizeWildberriesStatus($order),
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
     */
    private function getOzonOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search): \Illuminate\Support\Collection
    {
        if (!$companyId) {
            return collect();
        }

        try {
            $query = OzonOrder::query()
                ->whereHas('account', fn($query) => $query->where('company_id', $companyId))
                ->whereDate('created_at_ozon', '>=', $dateFrom)
                ->whereDate('created_at_ozon', '<=', $dateTo);

            if ($status) {
                $query->whereIn('status', $this->mapStatusToDbStatuses($status));
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('posting_number', 'like', "%{$search}%")
                      ->orWhere('order_id', 'like', "%{$search}%");
                });
            }

            return $query->get()->map(fn($order) => [
                'id' => 'ozon_' . $order->id,
                'order_number' => $order->posting_number ?? $order->order_id,
                'created_at' => $order->created_at_ozon?->toIso8601String(),
                'marketplace' => 'ozon',
                'customer_name' => $order->customer_name ?? ($order->customer_data['name'] ?? null),
                'total_amount' => (float) $order->total_amount,
                'currency' => 'RUB',
                'status' => $this->normalizeStatus($order->status),
                'raw_status' => $order->status,
                'items' => $order->items ? $order->items->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ])->toArray() : [],
            ]);
        } catch (\Exception $e) {
            \Log::error('OZON orders fetch error: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get Yandex Market orders
     */
    private function getYandexMarketOrders(?int $companyId, string $dateFrom, string $dateTo, ?string $status, ?string $search): \Illuminate\Support\Collection
    {
        if (!$companyId) {
            return collect();
        }

        try {
            $query = YandexMarketOrder::query()
                ->whereHas('account', fn($query) => $query->where('company_id', $companyId))
                ->whereDate('created_at_ym', '>=', $dateFrom)
                ->whereDate('created_at_ym', '<=', $dateTo);

            if ($status) {
                $query->whereIn('status', $this->mapStatusToDbStatuses($status));
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            }

            return $query->get()->map(fn($order) => [
                'id' => 'ym_' . $order->id,
                'order_number' => $order->order_id,
                'created_at' => $order->created_at_ym?->toIso8601String(),
                'marketplace' => 'ym',
                'customer_name' => $order->customer_name,
                'total_amount' => (float) $order->total_amount,
                'currency' => 'RUB',
                'status' => $this->normalizeStatus($order->status),
                'raw_status' => $order->status,
            ]);
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
                'order_number' => $order->external_order_id,
                'created_at' => ($order->created_at_channel ?? $order->created_at)?->toIso8601String(),
                'marketplace' => 'manual',
                'customer_name' => $order->payload_json['customer_name'] ?? null,
                'total_amount' => (float) ($order->payload_json['total_amount'] ?? 0),
                'currency' => $order->payload_json['currency'] ?? 'UZS',
                'status' => $this->normalizeStatus($order->status),
                'raw_status' => $order->status,
            ]);
        } catch (\Exception $e) {
            \Log::warning('Manual orders fetch skipped: ' . $e->getMessage());
            return collect();
        }
    }
    
    /**
     * Calculate statistics
     */
    private function calculateStats(\Illuminate\Support\Collection $orders): array
    {
        $totalOrders = $orders->count();

        // Separate cancelled orders
        $cancelledOrders = $orders->filter(fn($o) => $o['status'] === 'cancelled');
        $validOrders = $orders->filter(fn($o) => $o['status'] !== 'cancelled');

        $cancelledCount = $cancelledOrders->count();
        $cancelledAmount = $cancelledOrders->sum('total_amount');

        // Revenue excludes cancelled orders
        $totalRevenue = $validOrders->sum('total_amount');
        $totalAmount = $orders->sum('total_amount'); // Keep for backwards compatibility

        $marketplaceOrders = $orders->filter(fn($o) => $o['marketplace'] !== 'manual')->count();
        $manualOrders = $orders->filter(fn($o) => $o['marketplace'] === 'manual')->count();

        $avgOrderValue = $validOrders->count() > 0 ? round($totalRevenue / $validOrders->count()) : 0;

        $byMarketplace = $orders->groupBy('marketplace')->map(function($group, $mp) {
            $labels = ['uzum' => 'Uzum', 'wb' => 'WB', 'ozon' => 'Ozon', 'ym' => 'YM', 'manual' => 'Ручные'];
            $validGroup = $group->filter(fn($o) => $o['status'] !== 'cancelled');
            return [
                'name' => $mp,
                'label' => $labels[$mp] ?? $mp,
                'count' => $group->count(),
                'amount' => $validGroup->sum('total_amount'),
                'cancelledCount' => $group->filter(fn($o) => $o['status'] === 'cancelled')->count(),
            ];
        })->values()->toArray();

        return [
            'totalOrders' => $totalOrders,
            'totalAmount' => $totalAmount,
            'totalRevenue' => $totalRevenue,
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
            'shipped' => ['shipped', 'sent', 'in_delivery', 'delivering', 'waiting_pickup'],
            'delivered' => ['delivered', 'completed', 'done', 'received', 'issued'],
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
}
