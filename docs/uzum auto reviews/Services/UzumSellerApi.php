<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;

class UzumSellerApi
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeout;

    public function __construct(string $token = null)
    {
        $this->baseUrl = config('uzum.api_base_url', 'https://api-seller.uzum.uz/api/seller-openapi');
        $this->token = $token ?? config('uzum.api_token', '');
        $this->timeout = config('uzum.api_timeout', 30);
    }

    /**
     * Создать экземпляр с конкретным токеном (для мультимагазинности)
     */
    public static function forToken(string $token): self
    {
        return new self($token);
    }

    // ─────────────────────────────────────────────
    // SHOP
    // ─────────────────────────────────────────────

    /**
     * Получить список собственных магазинов
     */
    public function getShops(): array
    {
        return $this->get('/v1/shops');
    }

    // ─────────────────────────────────────────────
    // FBS ORDERS
    // ─────────────────────────────────────────────

    /**
     * Получить FBS/DBS заказы по статусу
     */
    public function getFbsOrders(
        array $shopIds,
        string $status = 'CREATED',
        ?string $scheme = null,
        ?int $dateFrom = null,
        ?int $dateTo = null,
        int $page = 0,
        int $size = 20
    ): array {
        $params = [
            'shopIds' => $shopIds,
            'status'  => $status,
            'page'    => $page,
            'size'    => $size,
        ];

        if ($scheme) $params['scheme'] = $scheme;
        if ($dateFrom) $params['dateFrom'] = $dateFrom;
        if ($dateTo) $params['dateTo'] = $dateTo;

        return $this->get('/v2/fbs/orders', $params);
    }

    /**
     * Получить количество заказов
     */
    public function getFbsOrdersCount(
        array $shopIds = [],
        string $status = 'CREATED',
        ?int $dateFrom = null,
        ?int $dateTo = null
    ): array {
        $params = [
            'status' => $status,
        ];

        if (!empty($shopIds)) $params['shopIds'] = $shopIds;
        if ($dateFrom) $params['dateFrom'] = $dateFrom;
        if ($dateTo) $params['dateTo'] = $dateTo;

        return $this->get('/v2/fbs/orders/count', $params);
    }

    /**
     * Получить информацию о заказе
     */
    public function getFbsOrder(int $orderId): array
    {
        return $this->get("/v1/fbs/order/{$orderId}");
    }

    /**
     * Подтвердить заказ (CREATED → PACKING)
     */
    public function confirmOrder(int $orderId): array
    {
        return $this->post("/v1/fbs/order/{$orderId}/confirm");
    }

    /**
     * Отменить заказ
     */
    public function cancelOrder(int $orderId, string $reason, ?string $comment = null): array
    {
        $body = [
            'reason' => $reason,
        ];

        if ($comment) $body['comment'] = $comment;

        return $this->post("/v1/fbs/order/{$orderId}/cancel", $body);
    }

    /**
     * Получить причины возврата
     */
    public function getReturnReasons(): array
    {
        return $this->get('/v1/fbs/order/return-reasons');
    }

    /**
     * Получить этикетку заказа
     */
    public function getOrderLabel(int $orderId, string $size = 'LARGE'): array
    {
        return $this->get("/v1/fbs/order/{$orderId}/labels/print", ['size' => $size]);
    }

    // ─────────────────────────────────────────────
    // SKU STOCKS (FBS/DBS)
    // ─────────────────────────────────────────────

    /**
     * Получить остатки FBS/DBS
     */
    public function getSkuStocks(): array
    {
        return $this->get('/v2/fbs/sku/stocks');
    }

    /**
     * Обновить остатки FBS/DBS
     */
    public function updateSkuStocks(array $items): array
    {
        return $this->post('/v2/fbs/sku/stocks', ['skuStocks' => $items]);
    }

    // ─────────────────────────────────────────────
    // PRODUCTS & PRICES
    // ─────────────────────────────────────────────

    /**
     * Получить товары магазина
     */
    public function getProducts(
        int $shopId,
        int $page = 0,
        int $size = 20,
        string $filter = 'ALL',
        string $sortBy = 'DEFAULT',
        string $order = 'ASC',
        ?string $searchQuery = null,
        ?string $productRank = null
    ): array {
        $params = compact('page', 'size', 'filter', 'sortBy', 'order');

        if ($searchQuery) $params['searchQuery'] = $searchQuery;
        if ($productRank) $params['productRank'] = $productRank;

        return $this->get("/v1/product/shop/{$shopId}", $params);
    }

    /**
     * Обновить цены SKU
     */
    public function updatePrices(int $shopId, array $priceData): array
    {
        return $this->post("/v1/product/{$shopId}/sendPriceData", $priceData);
    }

    // ─────────────────────────────────────────────
    // FINANCE
    // ─────────────────────────────────────────────

    /**
     * Получить заказы (финансы)
     */
    public function getFinanceOrders(
        array $shopIds,
        int $page = 0,
        int $size = 20,
        bool $group = false,
        ?int $dateFrom = null,
        ?int $dateTo = null,
        ?array $statuses = null
    ): array {
        $params = [
            'shopIds' => $shopIds,
            'page'    => $page,
            'size'    => $size,
            'group'   => $group,
        ];

        if ($dateFrom) $params['dateFrom'] = $dateFrom;
        if ($dateTo) $params['dateTo'] = $dateTo;
        if ($statuses) $params['statuses'] = $statuses;

        return $this->get('/v1/finance/orders', $params);
    }

    /**
     * Получить расходы
     */
    public function getFinanceExpenses(
        int $page = 0,
        int $size = 20,
        ?array $shopIds = null,
        ?int $dateFrom = null,
        ?int $dateTo = null,
        ?array $sources = null
    ): array {
        $params = compact('page', 'size');

        if ($shopIds) $params['shopIds'] = $shopIds;
        if ($dateFrom) $params['dateFrom'] = $dateFrom;
        if ($dateTo) $params['dateTo'] = $dateTo;
        if ($sources) $params['sources'] = $sources;

        return $this->get('/v1/finance/expenses', $params);
    }

    // ─────────────────────────────────────────────
    // INVOICES
    // ─────────────────────────────────────────────

    public function getInvoices(int $page = 0, int $size = 50): array
    {
        return $this->get('/v1/invoice', compact('page', 'size'));
    }

    public function getReturns(?int $returnId = null, int $page = 0, int $size = 50): array
    {
        $params = compact('page', 'size');
        if ($returnId) $params['returnId'] = $returnId;

        return $this->get('/v1/return', $params);
    }

    // ─────────────────────────────────────────────
    // FBS INVOICES
    // ─────────────────────────────────────────────

    public function getFbsInvoices(array $statuses, int $page = 0, int $size = 20): array
    {
        return $this->get('/v1/fbs/invoice', [
            'statuses' => $statuses,
            'page'     => $page,
            'size'     => $size,
        ]);
    }

    public function createFbsInvoice(array $data): array
    {
        return $this->post('/v1/fbs/invoice', $data);
    }

    public function cancelFbsInvoice(int $invoiceId): array
    {
        return $this->post("/v1/fbs/invoice/{$invoiceId}/cancel");
    }

    // ─────────────────────────────────────────────
    // REVIEWS (Seller Panel API)
    // ─────────────────────────────────────────────
    // Base: https://api-seller.uzum.uz/api/seller/product-reviews

    /**
     * Получить список отзывов
     *
     * ⚠️ Несмотря на "получение" — это POST-запрос с query params.
     * POST /api/seller/product-reviews?page=0&size=20
     * Body: {} (пустой JSON) или фильтры
     *
     * Response: { payload: [ { reviewId, rating, content, customerName, product: {...}, replyStatus, ... } ] }
     */
    public function getReviews(
        int $page = 0,
        int $size = 20,
        array $filters = []
    ): array {
        $url = 'https://api-seller.uzum.uz/api/seller/product-reviews';

        return $this->postWithQueryParams($url, [
            'page' => $page,
            'size' => $size,
        ], $filters ?: (object) []); // пустой объект {} если нет фильтров
    }

    /**
     * Получить детали одного отзыва
     *
     * GET /api/seller/product-reviews/review/{reviewId}
     */
    public function getReview(int $reviewId): array
    {
        return $this->request(
            'GET',
            "/api/seller/product-reviews/review/{$reviewId}",
            baseUrl: 'https://api-seller.uzum.uz'
        );
    }

    /**
     * Ответить на отзыв (или несколько сразу)
     *
     * POST /api/seller/product-reviews/reply/create
     * Body: [ { "reviewId": 123, "content": "Спасибо!" } ]
     *
     * ⚠️ Тело — МАССИВ объектов (batch reply)
     */
    public function replyToReview(int $reviewId, string $text): array
    {
        return $this->request(
            'POST',
            '/api/seller/product-reviews/reply/create',
            body: [
                [
                    'reviewId' => $reviewId,
                    'content'  => $text,
                ],
            ],
            baseUrl: 'https://api-seller.uzum.uz'
        );
    }

    /**
     * Ответить на несколько отзывов сразу (batch)
     *
     * @param array $replies [ ['reviewId' => 123, 'content' => 'Спасибо!'], ... ]
     */
    public function replyToReviewsBatch(array $replies): array
    {
        return $this->request(
            'POST',
            '/api/seller/product-reviews/reply/create',
            body: $replies,
            baseUrl: 'https://api-seller.uzum.uz'
        );
    }

    // ─────────────────────────────────────────────
    // HTTP CLIENT
    // ─────────────────────────────────────────────

    protected function get(string $path, array $params = [], ?string $baseUrl = null): array
    {
        return $this->request('GET', $path, $params, [], $baseUrl);
    }

    protected function post(string $path, array|object $body = [], ?string $baseUrl = null): array
    {
        return $this->request('POST', $path, [], $body, $baseUrl);
    }

    /**
     * POST с query params + JSON body
     * Нужен для эндпоинта отзывов: POST /api/seller/product-reviews?page=0&size=20 + body
     */
    protected function postWithQueryParams(string $fullUrl, array $queryParams, array|object $body): array
    {
        $url = $fullUrl . '?' . http_build_query($queryParams);

        try {
            $request = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->timeout($this->timeout);

            $response = $request->post($url, $body);

            $this->logRateLimit($fullUrl, $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json(),
                    'status'  => $response->status(),
                ];
            }

            Log::warning('Uzum API error', [
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success' => false,
                'error'   => $response->json('message', 'Unknown error'),
                'code'    => $response->json('code', null),
                'status'  => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Uzum API request exception', [
                'url'     => $url,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'status'  => $e->getCode(),
            ];
        }
    }

    protected function request(
        string $method,
        string $path,
        array $params = [],
        array|object $body = [],
        ?string $baseUrl = null
    ): array {
        // Поддержка абсолютных URL (для review endpoints)
        $url = str_starts_with($path, 'http')
            ? $path
            : ($baseUrl ?? $this->baseUrl) . $path;

        try {
            $request = Http::withHeaders([
                'Authorization' => "Bearer {$this->token}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])->timeout($this->timeout);

            /** @var Response $response */
            $response = match ($method) {
                'GET'  => $request->get($url, $params),
                'POST' => $request->post($url, $body),
                default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
            };

            // Логируем rate-limit информацию
            $this->logRateLimit($path, $response);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json(),
                    'status'  => $response->status(),
                ];
            }

            Log::warning('Uzum API error', [
                'path'   => $path,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success' => false,
                'error'   => $response->json('message', 'Unknown error'),
                'code'    => $response->json('code', null),
                'status'  => $response->status(),
            ];

        } catch (RequestException $e) {
            Log::error('Uzum API request exception', [
                'path'    => $path,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'status'  => $e->getCode(),
            ];
        }
    }

    protected function logRateLimit(string $path, Response $response): void
    {
        $remaining = $response->header('x-ratelimit-remaining');
        $perDay = $response->header('x-ratelimit-remaining-per-day');

        if ($remaining !== null && (int) $remaining < 5) {
            Log::warning("Uzum API rate limit low: {$path}", [
                'remaining'     => $remaining,
                'remaining_day' => $perDay,
            ]);
        }
    }
}
