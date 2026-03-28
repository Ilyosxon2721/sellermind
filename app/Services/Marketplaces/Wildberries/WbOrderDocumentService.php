<?php

declare(strict_types=1);

namespace App\Services\Marketplaces\Wildberries;

use App\Models\WbOrder;
use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Log;

/**
 * Сервис генерации печатных документов для заказов WB.
 *
 * Поддерживаемые документы:
 * - Чек (receipt) — краткая информация о заказе для покупателя
 * - Товарная накладная (waybill) — документ передачи товара
 * - Счёт-фактура (invoice) — финансовый документ
 */
final class WbOrderDocumentService
{
    /**
     * Подготовить данные для печати чека
     */
    public function getReceiptData(WbOrder $order): array
    {
        $account = $order->account;
        $company = $account->company;
        $items = $order->items;

        return [
            'document_type' => 'receipt',
            'document_number' => $this->generateDocNumber('ЧК', $order),
            'document_date' => $order->ordered_at ?? $order->created_at,
            'company' => $this->getCompanyData($company),
            'order' => $this->getOrderData($order),
            'items' => $this->getItemsData($order),
            'totals' => $this->getTotalsData($order),
        ];
    }

    /**
     * Подготовить данные для печати товарной накладной
     */
    public function getWaybillData(WbOrder $order): array
    {
        $account = $order->account;
        $company = $account->company;

        return [
            'document_type' => 'waybill',
            'document_number' => $this->generateDocNumber('ТН', $order),
            'document_date' => $order->ordered_at ?? $order->created_at,
            'company' => $this->getCompanyData($company),
            'buyer' => $this->getBuyerData($order),
            'order' => $this->getOrderData($order),
            'items' => $this->getItemsData($order),
            'totals' => $this->getTotalsData($order),
            'delivery' => [
                'type' => $order->wb_delivery_type ?? 'FBS',
                'warehouse_id' => $order->warehouse_id,
                'supply_id' => $order->supply_id,
            ],
        ];
    }

    /**
     * Подготовить данные для печати счёт-фактуры
     */
    public function getInvoiceData(WbOrder $order): array
    {
        $account = $order->account;
        $company = $account->company;

        return [
            'document_type' => 'invoice',
            'document_number' => $this->generateDocNumber('СФ', $order),
            'document_date' => $order->ordered_at ?? $order->created_at,
            'company' => $this->getCompanyData($company),
            'buyer' => $this->getBuyerData($order),
            'order' => $this->getOrderData($order),
            'items' => $this->getItemsData($order),
            'totals' => $this->getTotalsData($order),
        ];
    }

    /**
     * Массовая генерация данных для нескольких заказов
     */
    public function getBulkDocumentData(array $orderIds, MarketplaceAccount $account, string $type): array
    {
        $orders = WbOrder::where('marketplace_account_id', $account->id)
            ->whereIn('id', $orderIds)
            ->with('items')
            ->get();

        $documents = [];
        foreach ($orders as $order) {
            try {
                $documents[] = match ($type) {
                    'receipt' => $this->getReceiptData($order),
                    'waybill' => $this->getWaybillData($order),
                    'invoice' => $this->getInvoiceData($order),
                };
            } catch (\Throwable $e) {
                Log::warning('Ошибка генерации документа для заказа WB', [
                    'order_id' => $order->id,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $documents;
    }

    /**
     * Данные компании-продавца
     */
    private function getCompanyData($company): array
    {
        $settings = $company->settings ?? [];

        return [
            'name' => $company->name ?? 'SellerMind',
            'address' => $settings['address'] ?? null,
            'phone' => $settings['phone'] ?? null,
            'inn' => $settings['inn'] ?? null,
            'kpp' => $settings['kpp'] ?? null,
            'ogrn' => $settings['ogrn'] ?? null,
            'bank_name' => $settings['bank_name'] ?? null,
            'bank_account' => $settings['bank_account'] ?? null,
            'bank_bik' => $settings['bank_bik'] ?? null,
            'bank_corr' => $settings['bank_corr'] ?? null,
        ];
    }

    /**
     * Данные покупателя из заказа WB
     */
    private function getBuyerData(WbOrder $order): array
    {
        return [
            'name' => $order->customer_name ?? 'Розничный покупатель (Wildberries)',
            'phone' => $order->customer_phone ?? null,
        ];
    }

    /**
     * Основные данные заказа
     */
    private function getOrderData(WbOrder $order): array
    {
        return [
            'id' => $order->id,
            'external_order_id' => $order->external_order_id,
            'article' => $order->article,
            'sku' => $order->sku,
            'nm_id' => $order->nm_id,
            'status' => $order->status,
            'ordered_at' => $order->ordered_at,
            'delivery_type' => $order->wb_delivery_type ?? 'FBS',
            'warehouse_id' => $order->warehouse_id,
            'currency' => $order->currency ?? 'RUB',
        ];
    }

    /**
     * Позиции заказа (товары)
     */
    private function getItemsData(WbOrder $order): array
    {
        $items = $order->items;

        if ($items->isEmpty()) {
            return [[
                'name' => $order->article ?? "Товар WB #{$order->nm_id}",
                'sku' => $order->sku ?? $order->article,
                'quantity' => 1,
                'price' => (float) ($order->total_amount ?? $order->price ?? 0),
                'total' => (float) ($order->total_amount ?? $order->price ?? 0),
            ]];
        }

        return $items->map(fn ($item) => [
            'name' => $item->name ?? "Товар WB",
            'sku' => $item->external_offer_id,
            'quantity' => (int) $item->quantity,
            'price' => (float) $item->price,
            'total' => (float) $item->total_price,
        ])->toArray();
    }

    /**
     * Итоговые суммы
     */
    private function getTotalsData(WbOrder $order): array
    {
        $items = $this->getItemsData($order);
        $subtotal = array_sum(array_column($items, 'total'));
        $itemsCount = array_sum(array_column($items, 'quantity'));

        return [
            'subtotal' => $subtotal,
            'total' => (float) ($order->total_amount ?? $subtotal),
            'currency' => $order->currency ?? 'RUB',
            'items_count' => $itemsCount,
        ];
    }

    /**
     * Генерация номера документа: ЧК-WB-12345
     */
    private function generateDocNumber(string $prefix, WbOrder $order): string
    {
        return "{$prefix}-WB-{$order->external_order_id}";
    }
}
