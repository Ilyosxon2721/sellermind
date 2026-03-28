<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Company;
use App\Models\OzonOrder;
use App\Models\Sale;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Models\WildberriesOrder;
use App\Models\YandexMarketOrder;
use Illuminate\View\View;

/**
 * Контроллер печати заказов маркетплейсов (чек, счёт-фактура, накладная).
 * Поддерживает: Uzum, Wildberries, Ozon, Yandex Market, ручные продажи (Sale).
 */
final class OrderPrintController extends Controller
{
    use HasCompanyScope;

    /**
     * Печать чека
     */
    public function receipt(string $id): View
    {
        [$order, $company] = $this->loadOrder($id);

        return view('sales.print.marketplace-receipt', compact('order', 'company'));
    }

    /**
     * Печать счёт-фактуры
     */
    public function invoice(string $id): View
    {
        [$order, $company] = $this->loadOrder($id);

        return view('sales.print.marketplace-invoice', compact('order', 'company'));
    }

    /**
     * Печать накладной
     */
    public function waybill(string $id): View
    {
        [$order, $company] = $this->loadOrder($id);

        return view('sales.print.marketplace-waybill', compact('order', 'company'));
    }

    /**
     * Загрузить и нормализовать заказ по составному ID (marketplace_id).
     *
     * @return array{0: array<string, mixed>, 1: Company}
     */
    private function loadOrder(string $compositeId): array
    {
        $parts = explode('_', $compositeId, 2);

        if (count($parts) !== 2 || $parts[1] === '') {
            abort(404, 'Неверный формат ID заказа');
        }

        [$marketplace, $orderId] = $parts;
        $companyId = $this->getCompanyId();

        $normalized = match ($marketplace) {
            'uzum' => $this->loadUzumOrder($orderId, $companyId),
            'wb' => $this->loadWildberriesOrder($orderId, $companyId),
            'ozon' => $this->loadOzonOrder($orderId, $companyId),
            'ym' => $this->loadYandexMarketOrder($orderId, $companyId),
            'sale' => $this->loadSaleOrder($orderId, $companyId),
            default => abort(404, 'Неизвестный маркетплейс: ' . $marketplace),
        };

        $company = Company::findOrFail($companyId);

        return [$normalized, $company];
    }

    /**
     * Загрузить и нормализовать заказ Uzum
     *
     * @return array<string, mixed>
     */
    private function loadUzumOrder(string $orderId, int $companyId): array
    {
        $order = UzumOrder::with(['account', 'items'])
            ->whereHas('account', fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($orderId);

        $items = $order->items?->map(fn ($item) => [
            'name' => $item->product_title ?? $item->name,
            'sku' => $item->sku,
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->price,
            'total' => (float) (($item->total_price ?? $item->price) * ($item->quantity ?? 1)),
        ])->toArray() ?? [];

        return [
            'order_number' => $order->external_order_id,
            'marketplace' => 'uzum',
            'marketplace_label' => 'Uzum Market',
            'date' => $order->resolvedOrderedAt() ?? $order->created_at,
            'status' => $order->status_normalized ?? $order->status,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_address' => $order->delivery_address,
            'total_amount' => (float) $order->total_amount,
            'currency' => $order->currency ?? 'UZS',
            'items' => $items,
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'notes' => null,
        ];
    }

    /**
     * Загрузить и нормализовать заказ Wildberries.
     * Сначала ищем в WildberriesOrder, затем в WbOrder (fallback).
     *
     * @return array<string, mixed>
     */
    private function loadWildberriesOrder(string $orderId, int $companyId): array
    {
        // Сначала пробуем WildberriesOrder (основная таблица с реальными данными)
        $order = WildberriesOrder::with('account')
            ->whereHas('account', fn ($q) => $q->where('company_id', $companyId))
            ->find($orderId);

        if ($order) {
            return $this->normalizeWildberriesOrder($order);
        }

        // Фолбэк на WbOrder (устаревшая модель)
        $order = WbOrder::with('account')
            ->whereHas('account', fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($orderId);

        return $this->normalizeWbOrder($order);
    }

    /**
     * Нормализовать WildberriesOrder — каждая строка это один товар
     *
     * @return array<string, mixed>
     */
    private function normalizeWildberriesOrder(WildberriesOrder $order): array
    {
        $totalAmount = (float) ($order->for_pay ?? $order->finished_price ?? $order->total_price ?? 0);

        return [
            'order_number' => $order->srid ?? $order->order_id,
            'marketplace' => 'wb',
            'marketplace_label' => 'Wildberries',
            'date' => $order->order_date ?? $order->created_at,
            'status' => $order->wb_status ?? $order->status,
            'customer_name' => $order->region_name,
            'customer_phone' => null,
            'delivery_address' => implode(', ', array_filter([
                $order->country_name,
                $order->oblast_okrug_name,
                $order->region_name,
            ])),
            'total_amount' => $totalAmount,
            'currency' => 'RUB',
            'items' => [
                [
                    'name' => $order->subject ?? $order->category ?? 'Товар',
                    'sku' => $order->supplier_article ?? $order->barcode,
                    'quantity' => 1,
                    'price' => $totalAmount,
                    'total' => $totalAmount,
                ],
            ],
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'notes' => null,
        ];
    }

    /**
     * Нормализовать WbOrder (устаревшая модель)
     *
     * @return array<string, mixed>
     */
    private function normalizeWbOrder(WbOrder $order): array
    {
        $totalAmount = (float) ($order->total_amount ?? $order->price ?? 0);

        return [
            'order_number' => $order->external_order_id ?? $order->rid,
            'marketplace' => 'wb',
            'marketplace_label' => 'Wildberries',
            'date' => $order->ordered_at ?? $order->created_at,
            'status' => $order->wb_status ?? $order->status,
            'customer_name' => $order->customer_name,
            'customer_phone' => null,
            'delivery_address' => $order->delivery_address,
            'total_amount' => $totalAmount,
            'currency' => $order->currency ?? 'RUB',
            'items' => [
                [
                    'name' => $order->subject ?? $order->article ?? 'Товар',
                    'sku' => $order->sku ?? $order->article,
                    'quantity' => 1,
                    'price' => $totalAmount,
                    'total' => $totalAmount,
                ],
            ],
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'notes' => null,
        ];
    }

    /**
     * Загрузить и нормализовать заказ Ozon
     *
     * @return array<string, mixed>
     */
    private function loadOzonOrder(string $orderId, int $companyId): array
    {
        $order = OzonOrder::with('account')
            ->whereHas('account', fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($orderId);

        $products = $order->getProductsList();

        $items = array_map(fn (array $product) => [
            'name' => $product['name'] ?? $product['offer_id'] ?? 'Товар',
            'sku' => $product['offer_id'] ?? $product['sku'] ?? null,
            'quantity' => (int) ($product['quantity'] ?? 1),
            'price' => (float) ($product['price'] ?? 0),
            'total' => (float) ($product['price'] ?? 0) * (int) ($product['quantity'] ?? 1),
        ], $products);

        return [
            'order_number' => $order->posting_number ?? $order->order_id,
            'marketplace' => 'ozon',
            'marketplace_label' => 'Ozon',
            'date' => $order->created_at_ozon ?? $order->created_at,
            'status' => $order->getNormalizedStatus(),
            'customer_name' => $order->customer_name ?? ($order->customer_data['name'] ?? null),
            'customer_phone' => null,
            'delivery_address' => $order->delivery_address,
            'total_amount' => (float) ($order->total_price ?? 0),
            'currency' => 'RUB',
            'items' => $items,
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'notes' => null,
        ];
    }

    /**
     * Загрузить и нормализовать заказ Yandex Market
     *
     * @return array<string, mixed>
     */
    private function loadYandexMarketOrder(string $orderId, int $companyId): array
    {
        $order = YandexMarketOrder::with('account')
            ->whereHas('account', fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($orderId);

        // Извлекаем товары из order_data (JSON)
        $orderData = $order->order_data ?? [];
        $rawItems = $orderData['items'] ?? [];

        $items = array_map(fn (array $item) => [
            'name' => $item['offerName'] ?? $item['offerId'] ?? 'Товар',
            'sku' => $item['offerId'] ?? $item['shopSku'] ?? null,
            'quantity' => (int) ($item['count'] ?? $item['quantity'] ?? 1),
            'price' => (float) ($item['price'] ?? 0),
            'total' => (float) ($item['price'] ?? 0) * (int) ($item['count'] ?? $item['quantity'] ?? 1),
        ], $rawItems);

        return [
            'order_number' => (string) $order->order_id,
            'marketplace' => 'ym',
            'marketplace_label' => 'Yandex Market',
            'date' => $order->created_at_ym ?? $order->created_at,
            'status' => $order->getNormalizedStatus(),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'delivery_address' => null,
            'total_amount' => (float) ($order->total_price ?? 0),
            'currency' => 'RUB',
            'items' => $items,
            'account_name' => $order->account?->name ?? $order->account?->getDisplayName(),
            'notes' => null,
        ];
    }

    /**
     * Загрузить и нормализовать ручную продажу (Sale)
     *
     * @return array<string, mixed>
     */
    private function loadSaleOrder(string $orderId, int $companyId): array
    {
        $sale = Sale::with(['items.productVariant', 'counterparty', 'createdBy', 'warehouse', 'company'])
            ->where('company_id', $companyId)
            ->findOrFail($orderId);

        $items = $sale->items
            ->filter(fn ($item) => ! ($item->metadata['is_expense'] ?? false))
            ->map(fn ($item) => [
                'name' => $item->product_name,
                'sku' => $item->productVariant?->sku ?? $item->sku,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ])->values()->toArray();

        return [
            'order_number' => $sale->sale_number,
            'marketplace' => 'sale',
            'marketplace_label' => 'Ручная продажа',
            'date' => $sale->confirmed_at ?? $sale->created_at,
            'status' => $sale->status,
            'customer_name' => $sale->counterparty?->name,
            'customer_phone' => $sale->counterparty?->phone,
            'delivery_address' => $sale->counterparty?->address,
            'total_amount' => (float) $sale->total_amount,
            'currency' => $sale->currency ?? 'UZS',
            'items' => $items,
            'account_name' => null,
            'notes' => $sale->notes,
        ];
    }
}
