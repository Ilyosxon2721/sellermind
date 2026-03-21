<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api;

/**
 * Центральный реестр всех эндпоинтов Uzum Seller API.
 *
 * Swagger: https://api-seller.uzum.uz/api/seller-openapi/swagger/api-docs
 * Base URL: https://api-seller.uzum.uz/api/seller-openapi
 *
 * Каждый эндпоинт — массив с ключами:
 *   method  — HTTP метод (GET, POST, PUT, PATCH, DELETE)
 *   path    — путь относительно base_url (с плейсхолдерами {param})
 *   desc    — описание на русском
 *   params  — допустимые query-параметры (для GET)
 *   body    — структура тела запроса (для POST/PUT)
 */
final class UzumEndpoints
{
    // ─── МАГАЗИНЫ ──────────────────────────────────────────────
    public const SHOPS_LIST = [
        'method' => 'GET',
        'path' => '/v1/shops',
        'desc' => 'Получение списка собственных магазинов',
    ];

    // ─── ТОВАРЫ / КАТАЛОГ ──────────────────────────────────────
    public const PRODUCT_LIST = [
        'method' => 'GET',
        'path' => '/v1/product/shop/{shopId}',
        'desc' => 'Получение SKU по ID магазина',
        'params' => ['searchQuery', 'sortBy', 'order', 'size', 'page', 'productRank', 'filter'],
    ];

    public const PRODUCT_IMPORT = [
        'method' => 'POST',
        'path' => '/v1/product/import',
        'desc' => 'Создание/импорт нового товара',
    ];

    public const PRODUCT_UPDATE = [
        'method' => 'PUT',
        'path' => '/v1/product/{productId}',
        'desc' => 'Обновление существующего товара',
    ];

    public const PRODUCT_PRICE_UPDATE = [
        'method' => 'POST',
        'path' => '/v1/product/{shopId}/sendPriceData',
        'desc' => 'Изменение цен SKU (официальный Swagger-эндпоинт)',
    ];

    public const FBS_PRICE_UPDATE = [
        'method' => 'PUT',
        'path' => '/v2/fbs/sku/price',
        'desc' => 'Обновление цены FBS SKU',
    ];

    // ─── FBS ЗАКАЗЫ ────────────────────────────────────────────
    public const FBS_ORDERS_LIST = [
        'method' => 'GET',
        'path' => '/v2/fbs/orders',
        'desc' => 'Получение заказов продавца',
        'params' => ['shopIds', 'status', 'scheme', 'dateFrom', 'dateTo', 'page', 'size'],
    ];

    public const FBS_ORDERS_COUNT = [
        'method' => 'GET',
        'path' => '/v2/fbs/orders/count',
        'desc' => 'Получить количество заказов по статусам',
        'params' => ['shopIds', 'status', 'dateFrom', 'dateTo'],
    ];

    public const FBS_ORDER_DETAIL = [
        'method' => 'GET',
        'path' => '/v1/fbs/order/{orderId}',
        'desc' => 'Получение информации о заказе',
    ];

    public const FBS_ORDER_CONFIRM = [
        'method' => 'POST',
        'path' => '/v1/fbs/order/{orderId}/confirm',
        'desc' => 'Подтверждение заказа',
    ];

    public const FBS_ORDER_CANCEL = [
        'method' => 'POST',
        'path' => '/v1/fbs/order/{orderId}/cancel',
        'desc' => 'Отмена заказа',
        'body' => ['reasonId' => 'int', 'reason' => 'string'],
    ];

    public const FBS_ORDER_LABEL = [
        'method' => 'GET',
        'path' => '/v1/fbs/order/{orderId}/labels/print',
        'desc' => 'Получить этикетку для FBS заказа (PDF)',
        'params' => ['size'], // LARGE | BIG
    ];

    public const FBS_ORDER_RETURN_REASONS = [
        'method' => 'GET',
        'path' => '/v1/fbs/order/return-reasons',
        'desc' => 'Получение причин возврата',
    ];

    // ─── FBS/DBS ОСТАТКИ ───────────────────────────────────────
    public const FBS_STOCKS_GET = [
        'method' => 'GET',
        'path' => '/v2/fbs/sku/stocks',
        'desc' => 'Получить доступные остатки FBS и DBS',
    ];

    public const FBS_STOCKS_UPDATE = [
        'method' => 'POST',
        'path' => '/v2/fbs/sku/stocks',
        'desc' => 'Обновить FBS и DBS остатки',
        'body' => ['skuAmountList' => [['skuId' => 'int', 'amount' => 'int']]],
    ];

    // ─── FBS НАКЛАДНЫЕ ─────────────────────────────────────────
    public const FBS_INVOICE_LIST = [
        'method' => 'GET',
        'path' => '/v1/fbs/invoice',
        'desc' => 'Получить все накладные для FBS',
        'params' => ['statuses', 'size', 'page'],
    ];

    public const FBS_INVOICE_CREATE = [
        'method' => 'POST',
        'path' => '/v1/fbs/invoice',
        'desc' => 'Создание накладной',
    ];

    public const FBS_INVOICE_DETAIL = [
        'method' => 'GET',
        'path' => '/v1/fbs/invoice/{invoiceId}',
        'desc' => 'Получить накладную по идентификатору',
    ];

    public const FBS_INVOICE_ORDERS = [
        'method' => 'GET',
        'path' => '/v1/fbs/invoice/{invoiceId}/orders',
        'desc' => 'Получить FBS заказы по ID накладной',
    ];

    public const FBS_INVOICE_CLOSING_DOCS = [
        'method' => 'GET',
        'path' => '/v1/fbs/invoice/{invoiceId}/closing-documents',
        'desc' => 'Печать акта приёмки (PDF)',
    ];

    public const FBS_INVOICE_UPDATE_CONTENT = [
        'method' => 'POST',
        'path' => '/v1/fbs/invoice/{invoiceId}/update-content',
        'desc' => 'Изменение состава накладной',
    ];

    // ─── ПОСТАВКИ (FBO) ───────────────────────────────────────
    public const SHOP_INVOICE_LIST = [
        'method' => 'GET',
        'path' => '/v1/shop/{shopId}/invoice',
        'desc' => 'Получение накладных поставки по ID магазина',
    ];

    public const SHOP_INVOICE_PRODUCTS = [
        'method' => 'GET',
        'path' => '/v1/shop/{shopId}/invoice/products',
        'desc' => 'Получение состава накладной',
    ];

    // ─── ВОЗВРАТЫ ──────────────────────────────────────────────
    public const SHOP_RETURN_LIST = [
        'method' => 'GET',
        'path' => '/v1/shop/{shopId}/return',
        'desc' => 'Получение накладных возврата',
    ];

    public const SHOP_RETURN_DETAIL = [
        'method' => 'GET',
        'path' => '/v1/shop/{shopId}/return/{returnId}',
        'desc' => 'Получение состава накладной возврата',
    ];

    public const RETURN_LIST = [
        'method' => 'GET',
        'path' => '/v1/return',
        'desc' => 'Получение возвратов продавца',
    ];

    // ─── ФИНАНСЫ ───────────────────────────────────────────────
    public const FINANCE_ORDERS = [
        'method' => 'GET',
        'path' => '/v1/finance/orders',
        'desc' => 'Получение списка финансовых заказов',
        'params' => ['shopIds', 'statuses', 'dateFrom', 'dateTo', 'group', 'page', 'size'],
    ];

    public const FINANCE_EXPENSES = [
        'method' => 'GET',
        'path' => '/v1/finance/expenses',
        'desc' => 'Получение списка расходов продавца',
        'params' => ['shopIds', 'dateFrom', 'dateTo', 'sources', 'page', 'size'],
    ];

    // ─── ОТЗЫВЫ (недокументированный API, /api/seller/) ───────
    public const REVIEWS_LIST = [
        'method' => 'POST',
        'path' => '/api/seller/product-reviews',
        'desc' => 'Получение списка отзывов (undocumented)',
        'base_override' => 'https://api-seller.uzum.uz',
        'auth' => 'oauth',
        'params' => ['page', 'size'],
    ];

    public const REVIEW_DETAIL = [
        'method' => 'GET',
        'path' => '/api/seller/product-reviews/review/{reviewId}',
        'desc' => 'Получение деталей отзыва (undocumented)',
        'base_override' => 'https://api-seller.uzum.uz',
        'auth' => 'oauth',
    ];

    public const REVIEW_REPLY = [
        'method' => 'POST',
        'path' => '/api/seller/product-reviews/reply/create',
        'desc' => 'Ответ на отзыв (undocumented)',
        'base_override' => 'https://api-seller.uzum.uz',
        'auth' => 'oauth',
    ];

    // ─── АВТОРИЗАЦИЯ (OAuth2) ──────────────────────────────────
    public const OAUTH_TOKEN = [
        'method' => 'POST',
        'path' => '/api/oauth/token',
        'desc' => 'Получение/обновление токена (OAuth2)',
        'base_override' => 'https://api-seller.uzum.uz',
    ];

    /**
     * Подставить параметры в path: {orderId} → 12345
     */
    public static function buildPath(array $endpoint, array $params = []): string
    {
        $path = $endpoint['path'];
        foreach ($params as $key => $value) {
            $path = str_replace("{{$key}}", (string) $value, $path);
        }

        return $path;
    }

    /**
     * Получить базовый URL для эндпоинта
     */
    public static function getBaseUrl(array $endpoint): string
    {
        return $endpoint['base_override']
            ?? config('uzum.base_url', 'https://api-seller.uzum.uz/api/seller-openapi');
    }

    /**
     * Получить полный URL для эндпоинта
     */
    public static function buildUrl(array $endpoint, array $params = []): string
    {
        $base = rtrim(self::getBaseUrl($endpoint), '/');
        $path = self::buildPath($endpoint, $params);

        return $base.'/'.ltrim($path, '/');
    }
}
