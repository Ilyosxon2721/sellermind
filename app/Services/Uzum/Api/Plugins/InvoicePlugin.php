<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api\Plugins;

use App\Services\Uzum\Api\UzumApi;
use App\Services\Uzum\Api\UzumEndpoints;

/**
 * Плагин: Накладные FBS + Поставки FBO + Возвраты
 */
final class InvoicePlugin
{
    public function __construct(
        private readonly UzumApi $api,
    ) {}

    // ─── FBS НАКЛАДНЫЕ ─────────────────────────────────────────

    /**
     * Список FBS накладных
     */
    public function fbsList(array $statuses = [], int $page = 0, int $size = 20): array
    {
        $query = ['page' => $page, 'size' => $size];
        if (! empty($statuses)) {
            $query['statuses'] = implode(',', $statuses);
        }

        return $this->api->call(UzumEndpoints::FBS_INVOICE_LIST, query: $query);
    }

    /**
     * Создать FBS накладную
     */
    public function fbsCreate(array $data): array
    {
        return $this->api->call(UzumEndpoints::FBS_INVOICE_CREATE, body: $data);
    }

    /**
     * Детали FBS накладной
     */
    public function fbsDetail(int $invoiceId): array
    {
        return $this->api->call(
            UzumEndpoints::FBS_INVOICE_DETAIL,
            params: ['invoiceId' => $invoiceId],
        );
    }

    /**
     * Заказы в FBS накладной
     */
    public function fbsOrders(int $invoiceId): array
    {
        return $this->api->call(
            UzumEndpoints::FBS_INVOICE_ORDERS,
            params: ['invoiceId' => $invoiceId],
        );
    }

    /**
     * Акт приёмки FBS накладной (PDF)
     */
    public function fbsClosingDocs(int $invoiceId): array
    {
        return $this->api->call(
            UzumEndpoints::FBS_INVOICE_CLOSING_DOCS,
            params: ['invoiceId' => $invoiceId],
        );
    }

    /**
     * Изменить состав FBS накладной
     */
    public function fbsUpdateContent(int $invoiceId, array $data): array
    {
        return $this->api->call(
            UzumEndpoints::FBS_INVOICE_UPDATE_CONTENT,
            params: ['invoiceId' => $invoiceId],
            body: $data,
        );
    }

    // ─── ПОСТАВКИ FBO ──────────────────────────────────────────

    /**
     * Накладные поставки магазина (FBO)
     */
    public function shopInvoices(int $shopId): array
    {
        return $this->api->call(
            UzumEndpoints::SHOP_INVOICE_LIST,
            params: ['shopId' => $shopId],
        );
    }

    /**
     * Состав накладной поставки
     */
    public function shopInvoiceProducts(int $shopId): array
    {
        return $this->api->call(
            UzumEndpoints::SHOP_INVOICE_PRODUCTS,
            params: ['shopId' => $shopId],
        );
    }

    // ─── ВОЗВРАТЫ ──────────────────────────────────────────────

    /**
     * Накладные возврата магазина
     */
    public function returns(int $shopId): array
    {
        return $this->api->call(
            UzumEndpoints::SHOP_RETURN_LIST,
            params: ['shopId' => $shopId],
        );
    }

    /**
     * Детали возврата
     */
    public function returnDetail(int $shopId, int $returnId): array
    {
        return $this->api->call(
            UzumEndpoints::SHOP_RETURN_DETAIL,
            params: ['shopId' => $shopId, 'returnId' => $returnId],
        );
    }

    /**
     * Все возвраты продавца
     */
    public function allReturns(): array
    {
        return $this->api->call(UzumEndpoints::RETURN_LIST);
    }
}
