<?php

declare(strict_types=1);

namespace App\Services\Uzum;

use App\Models\MarketplaceAccount;
use App\Models\UzumOrder;
use App\Models\UzumOrderItem;
use App\Services\Marketplaces\MarketplaceCustomerService;
use App\Services\Stock\OrderStockService;
use App\Services\Uzum\Api\UzumApiManager;
use App\Services\Uzum\Api\UzumEndpoints;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Синхронизация FBS заказов Uzum через UzumApiManager
 */
final class UzumOrderSyncService
{
    private OrderStockService $stockService;

    private MarketplaceCustomerService $customerService;

    public function __construct(
        ?OrderStockService $stockService = null,
        ?MarketplaceCustomerService $customerService = null,
    ) {
        $this->stockService = $stockService ?? new OrderStockService;
        $this->customerService = $customerService ?? new MarketplaceCustomerService;
    }

    /** Маппинг статусов Uzum API → внутренние */
    private const STATUS_MAP = [
        'CREATED' => 'new',
        'PACKING' => 'in_assembly',
        'PENDING_DELIVERY' => 'in_supply',
        'DELIVERING' => 'accepted_uzum',
        'ACCEPTED_AT_DP' => 'accepted_uzum',
        'DELIVERED' => 'accepted_uzum',
        'DELIVERED_TO_CUSTOMER_DELIVERY_POINT' => 'waiting_pickup',
        'COMPLETED' => 'issued',
        'CANCELED' => 'cancelled',
        'PENDING_CANCELLATION' => 'cancelled',
        'RETURNED' => 'returns',
    ];

    /** Статусы-группы для фронтенда */
    private const STATUS_GROUPS = [
        'new' => 'new',
        'in_assembly' => 'assembling',
        'in_supply' => 'in_supply',
        'accepted_uzum' => 'accepted_uzum',
        'waiting_pickup' => 'waiting_pickup',
        'issued' => 'issued',
        'cancelled' => 'canceled',
        'returns' => 'returns',
    ];

    /** Активные статусы — загружаем без даты */
    private const ACTIVE_STATUSES = [
        'CREATED', 'PACKING', 'PENDING_DELIVERY',
        'DELIVERING', 'ACCEPTED_AT_DP', 'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
    ];

    /** Статусы с расширенным окном (90 дней) */
    private const EXTENDED_WINDOW_STATUSES = [
        'CANCELED', 'PENDING_CANCELLATION', 'RETURNED',
    ];

    /**
     * Полная синхронизация заказов для аккаунта
     *
     * @return array{total: int, created: int, updated: int, errors: int}
     */
    public function sync(MarketplaceAccount $account, int $daysBack = 7): array
    {
        $stats = ['total' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0];

        $uzum = new UzumApiManager($account);

        // Получаем shop IDs
        $shopIds = $this->resolveShopIds($account, $uzum);
        if (empty($shopIds)) {
            Log::info("UzumOrderSync: нет магазинов для аккаунта #{$account->id}");

            return $stats;
        }

        $fromMs = now()->subDays($daysBack)->startOfDay()->getTimestamp() * 1000;
        $extendedFromMs = now()->subDays(90)->getTimestamp() * 1000;

        $allStatuses = array_keys(self::STATUS_MAP);

        foreach ($allStatuses as $apiStatus) {
            $isActive = in_array($apiStatus, self::ACTIVE_STATUSES);
            $isExtended = in_array($apiStatus, self::EXTENDED_WINDOW_STATUSES);

            foreach ($shopIds as $shopId) {
                try {
                    $this->syncStatusForShop(
                        $uzum, $account, (string) $shopId, $apiStatus,
                        $isActive, $isExtended, $fromMs, $extendedFromMs, $stats
                    );
                } catch (\Throwable $e) {
                    Log::warning("UzumOrderSync: ошибка статуса {$apiStatus} магазина {$shopId}", [
                        'account_id' => $account->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info("UzumOrderSync: аккаунт #{$account->id}", $stats);

        return $stats;
    }

    /**
     * Синхронизация заказов по одному статусу и магазину
     */
    private function syncStatusForShop(
        UzumApiManager $uzum,
        MarketplaceAccount $account,
        string $shopId,
        string $apiStatus,
        bool $isActive,
        bool $isExtended,
        int $fromMs,
        int $extendedFromMs,
        array &$stats,
    ): void {
        $page = 0;
        $size = 50;

        do {
            $response = $uzum->api()->call(
                UzumEndpoints::FBS_ORDERS_LIST,
                query: [
                    'shopIds' => $shopId,
                    'status' => $apiStatus,
                    'page' => $page,
                    'size' => $size,
                ],
            );

            $payload = $response['payload'] ?? [];
            $orders = $payload['orders'] ?? $payload['list'] ?? $payload['orderList'] ?? $response['orderList'] ?? [];

            if (empty($orders)) {
                break;
            }

            $stopStatus = false;

            foreach ($orders as $orderData) {
                $dateCreated = $orderData['dateCreated'] ?? null;

                // Фильтрация по дате
                if (! $isActive && is_numeric($dateCreated)) {
                    $threshold = $isExtended ? $extendedFromMs : $fromMs;
                    if ((int) $dateCreated < $threshold) {
                        $stopStatus = true;

                        continue;
                    }
                }

                $stats['total']++;

                try {
                    $mapped = $this->mapOrderData($orderData);
                    $result = $this->persistOrder($account, $mapped);
                    $stats[$result]++;
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    Log::warning('UzumOrderSync: ошибка сохранения заказа', [
                        'order_id' => $orderData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $page++;
            usleep(300_000); // 300ms между страницами

        } while (! $stopStatus && count($orders) === $size && $page < 100);
    }

    /**
     * Подтвердить заказ
     */
    public function confirmOrder(MarketplaceAccount $account, int $orderId): array
    {
        $uzum = new UzumApiManager($account);
        $result = $uzum->orders()->confirm($orderId);

        // confirm возвращает простой ответ, не полный объект заказа —
        // получаем детали отдельно и обновляем БД
        try {
            $detail = $uzum->orders()->detail($orderId);
            if (!empty($detail) && isset($detail['id'])) {
                $mapped = $this->mapOrderData($detail);
                $this->persistOrder($account, $mapped);
            }
        } catch (\Throwable $e) {
            Log::warning('confirmOrder: не удалось обновить статус заказа в БД', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Отменить заказ
     */
    public function cancelOrder(MarketplaceAccount $account, int $orderId): array
    {
        $uzum = new UzumApiManager($account);
        $result = $uzum->orders()->cancel($orderId);

        // cancel тоже возвращает простой ответ — получаем детали отдельно
        try {
            $detail = $uzum->orders()->detail($orderId);
            if (!empty($detail) && isset($detail['id'])) {
                $mapped = $this->mapOrderData($detail);
                $this->persistOrder($account, $mapped);
            }
        } catch (\Throwable $e) {
            Log::warning('cancelOrder: не удалось обновить статус заказа в БД', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Получить этикетку заказа (PDF base64)
     */
    public function getOrderLabel(MarketplaceAccount $account, int $orderId, string $size = 'LARGE'): ?array
    {
        $uzum = new UzumApiManager($account);
        $response = $uzum->orders()->label($orderId, $size);

        $doc = $response['payload']['document'] ?? null;
        if (! $doc) {
            return null;
        }

        return [
            'binary' => base64_decode($doc),
            'base64' => $doc,
        ];
    }

    // ─── МАППИНГ ────────────────────────────────────────────────

    /**
     * Маппинг данных заказа из Uzum API в формат БД
     */
    public function mapOrderData(array $orderData): array
    {
        $items = [];
        foreach ($orderData['orderItems'] ?? [] as $item) {
            $externalOfferId = $item['skuId'] ?? $item['productId'] ?? $item['id'] ?? '';

            $items[] = [
                'external_offer_id' => (string) $externalOfferId,
                'name' => $item['skuTitle'] ?? $item['productTitle'] ?? $item['title'] ?? null,
                'quantity' => $item['amount'] ?? 1,
                'price' => isset($item['sellerPrice']) ? (float) $item['sellerPrice'] : (isset($item['price']) ? (float) $item['price'] : null),
                'total_price' => isset($item['sellerPrice'])
                    ? ((float) $item['sellerPrice']) * ($item['amount'] ?? 1)
                    : (isset($item['price']) ? ((float) $item['price']) * ($item['amount'] ?? 1) : null),
                'raw_payload' => $item,
            ];
        }

        $statusNormalized = self::STATUS_MAP[strtoupper($orderData['status'] ?? '')] ?? strtolower($orderData['status'] ?? '');
        $deliveryInfo = $orderData['deliveryInfo'] ?? [];
        $address = $deliveryInfo['address'] ?? [];

        return [
            'external_order_id' => isset($orderData['id']) ? (string) $orderData['id'] : null,
            'status' => $statusNormalized,
            'status_normalized' => $statusNormalized,
            'uzum_status' => $orderData['status'] ?? null,
            'shop_id' => $orderData['shopId'] ?? null,
            'customer_name' => $deliveryInfo['customerFullname'] ?? null,
            'customer_phone' => $deliveryInfo['customerPhone'] ?? null,
            'total_amount' => isset($orderData['price']) ? (float) $orderData['price'] : 0,
            'currency' => 'UZS',
            'ordered_at' => $orderData['dateCreated'] ?? null,
            'delivered_at' => $orderData['dateDelivered'] ?? null,
            'items' => $items,
            'raw_payload' => $orderData,
            // Uzum API возвращает поле scheme: 'FBS'|'DBS'|'EDBS' в теле каждого заказа.
            // Также проверяем deliveryType и deliveryMode как альтернативные имена.
            // Если поле отсутствует — передаём null, чтобы не перезаписывать существующий delivery_type.
            'delivery_type' => $this->detectDeliveryType($orderData),
            'delivery_address_full' => $address['fullAddress'] ?? null,
            'delivery_city' => $address['city'] ?? null,
            'delivery_street' => $address['street'] ?? null,
            'delivery_home' => $address['house'] ?? null,
            'delivery_flat' => $address['apartment'] ?? null,
            'delivery_longitude' => $address['longitude'] ?? null,
            'delivery_latitude' => $address['latitude'] ?? null,
        ];
    }

    /**
     * Определить тип доставки из данных заказа Uzum API
     *
     * Проверяет несколько полей: scheme, deliveryType, deliveryMode, shippingType.
     * Возвращает null если определить не удалось.
     */
    private function detectDeliveryType(array $orderData): ?string
    {
        // Прямые поля типа доставки
        foreach (['scheme', 'deliveryType', 'deliveryMode', 'shippingType', 'type'] as $field) {
            if (! empty($orderData[$field])) {
                $value = strtoupper($orderData[$field]);
                if (in_array($value, ['FBS', 'DBS', 'EDBS', 'FBO'])) {
                    return $value;
                }
            }
        }

        // Проверка вложенных полей
        $deliveryInfo = $orderData['deliveryInfo'] ?? [];
        if (! empty($deliveryInfo['deliveryType'])) {
            $value = strtoupper($deliveryInfo['deliveryType']);
            if (in_array($value, ['FBS', 'DBS', 'EDBS', 'FBO'])) {
                return $value;
            }
        }
        if (! empty($deliveryInfo['type'])) {
            $value = strtoupper($deliveryInfo['type']);
            if (in_array($value, ['FBS', 'DBS', 'EDBS', 'FBO'])) {
                return $value;
            }
        }

        // Эвристика: если есть полный адрес доставки с улицей/домом — вероятно DBS
        $address = $deliveryInfo['address'] ?? [];
        if (! empty($address['street']) && ! empty($address['house'])) {
            return 'DBS';
        }

        return null;
    }

    // ─── ПЕРСИСТЕНЦИЯ ───────────────────────────────────────────

    /**
     * Сохранить/обновить заказ в БД
     */
    public function persistOrder(MarketplaceAccount $account, array $orderData): string
    {
        $externalOrderId = $orderData['external_order_id'] ?? null;
        if (! $externalOrderId) {
            throw new \RuntimeException('Missing external_order_id');
        }

        $existingOrder = UzumOrder::where('marketplace_account_id', $account->id)
            ->where('external_order_id', $externalOrderId)
            ->first();

        $oldStatus = $existingOrder?->status;

        // Парсим дату заказа
        $orderedAt = $this->parseTimestamp($orderData['ordered_at'] ?? null);
        $deliveredAt = $this->parseTimestamp($orderData['delivered_at'] ?? null);

        // delivery_type: если API явно вернул scheme — используем его.
        // Если нет (null) — для нового заказа дефолт 'FBS', для существующего — сохраняем текущий.
        $newDeliveryType = $orderData['delivery_type'] ?? null;
        $deliveryType = $newDeliveryType
            ?? ($existingOrder?->delivery_type ?? 'FBS');

        $payload = [
            'marketplace_account_id' => $account->id,
            'external_order_id' => $externalOrderId,
            'status' => $orderData['status'] ?? 'new',
            'status_normalized' => $orderData['status_normalized'] ?? $orderData['status'] ?? 'new',
            'uzum_status' => $orderData['uzum_status'] ?? null,
            'delivery_type' => $deliveryType,
            'shop_id' => $orderData['shop_id'] ?? null,
            'customer_name' => $orderData['customer_name'] ?? null,
            'customer_phone' => $orderData['customer_phone'] ?? null,
            'total_amount' => $orderData['total_amount'] ?? 0,
            'currency' => $orderData['currency'] ?? 'UZS',
            'ordered_at' => $orderedAt,
            'delivered_at' => $deliveredAt,
            'delivery_address_full' => $orderData['delivery_address_full'] ?? null,
            'delivery_city' => $orderData['delivery_city'] ?? null,
            'delivery_street' => $orderData['delivery_street'] ?? null,
            'delivery_home' => $orderData['delivery_home'] ?? null,
            'delivery_flat' => $orderData['delivery_flat'] ?? null,
            'delivery_longitude' => $orderData['delivery_longitude'] ?? null,
            'delivery_latitude' => $orderData['delivery_latitude'] ?? null,
            'raw_payload' => $orderData['raw_payload'] ?? $orderData,
        ];

        $result = DB::transaction(function () use ($existingOrder, $payload, $orderData) {
            if ($existingOrder) {
                $existingOrder->update($payload);
                $order = $existingOrder;
                $resultType = 'updated';
            } else {
                $order = UzumOrder::create($payload);
                $resultType = 'created';
            }

            // Синхронизация товаров заказа
            $this->syncOrderItems($order, $orderData['items'] ?? []);

            return ['result' => $resultType, 'order' => $order];
        });

        // Обработка остатков
        $order = $result['order'];
        $newStatus = $order->status;

        if ($result['result'] === 'created' || $oldStatus !== $newStatus) {
            $this->processStockChange($account, $order, $oldStatus, $newStatus);
        }

        // Проверяем зависшие резервы
        $this->ensureCancelledStockReleased($account, $order);

        // Извлекаем данные клиента из DBS/EDBS заказа в клиентскую базу
        try {
            $this->customerService->extractFromOrder($account, $order);
        } catch (\Throwable $e) {
            Log::warning('Ошибка извлечения клиента из заказа Uzum', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result['result'];
    }

    /**
     * Синхронизация позиций заказа
     */
    private function syncOrderItems(UzumOrder $order, array $items): void
    {
        $order->items()->delete();

        foreach ($items as $itemData) {
            UzumOrderItem::create([
                'uzum_order_id' => $order->id,
                'external_offer_id' => $itemData['external_offer_id'] ?? null,
                'name' => $itemData['name'] ?? null,
                'quantity' => $itemData['quantity'] ?? 1,
                'price' => $itemData['price'] ?? 0,
                'total_price' => $itemData['total_price'] ?? ($itemData['price'] ?? 0) * ($itemData['quantity'] ?? 1),
                'raw_payload' => $itemData['raw_payload'] ?? $itemData,
            ]);
        }
    }

    // ─── ВСПОМОГАТЕЛЬНЫЕ ────────────────────────────────────────

    /**
     * Парсинг timestamp (миллисекунды или строка)
     */
    private function parseTimestamp(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $ts = (int) $value;
                $tz = config('app.timezone');

                return $ts > 1e12
                    ? Carbon::createFromTimestampMs($ts, $tz)
                    : Carbon::createFromTimestamp($ts, $tz);
            }

            return Carbon::parse($value);
        } catch (\Exception) {
            return now();
        }
    }

    /**
     * Получить shop IDs для аккаунта
     */
    private function resolveShopIds(MarketplaceAccount $account, UzumApiManager $uzum): array
    {
        // 1. Из credentials_json
        $credentialsJson = $account->credentials_json ?? [];
        if (! empty($credentialsJson['shop_ids']) && is_array($credentialsJson['shop_ids'])) {
            return array_values(array_filter(array_map('intval', $credentialsJson['shop_ids'])));
        }

        // 2. Из account->shop_id
        if ($account->shop_id) {
            $ids = array_filter(array_map('intval', explode(',', (string) $account->shop_id)));
            if (! empty($ids)) {
                return array_values($ids);
            }
        }

        // 3. Из БД
        $dbIds = DB::table('marketplace_shops')
            ->where('marketplace_account_id', $account->id)
            ->pluck('external_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->toArray();

        if (! empty($dbIds)) {
            return $dbIds;
        }

        // 4. Из API
        try {
            return $uzum->shops()->ids();
        } catch (\Throwable $e) {
            Log::warning("UzumOrderSync: не удалось получить магазины #{$account->id}", [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Обработка изменения статуса заказа для остатков
     */
    private function processStockChange(
        MarketplaceAccount $account,
        UzumOrder $order,
        ?string $oldStatus,
        string $newStatus,
    ): void {
        if ($oldStatus === $newStatus) {
            return;
        }

        try {
            $items = $this->stockService->getOrderItems($order, 'uzum');
            $this->stockService->processOrderStatusChange($account, $order, $oldStatus, $newStatus, $items);
        } catch (\Throwable $e) {
            Log::error('UzumOrderSync: ошибка обработки остатков', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Освободить зависшие резервы для отменённых заказов
     */
    private function ensureCancelledStockReleased(MarketplaceAccount $account, UzumOrder $order): void
    {
        if (($order->stock_status ?? 'none') !== 'reserved') {
            return;
        }

        $cancelledStatuses = OrderStockService::CANCELLED_STATUSES['uzum'] ?? [];
        $soldStatuses = OrderStockService::SOLD_STATUSES['uzum'] ?? [];

        if (! in_array($order->status, $cancelledStatuses) && ! in_array($order->status, $soldStatuses)) {
            return;
        }

        try {
            $items = $this->stockService->getOrderItems($order, 'uzum');
            $this->stockService->processOrderStatusChange($account, $order, null, $order->status, $items);
        } catch (\Throwable $e) {
            Log::error('UzumOrderSync: ошибка освобождения зависшего резерва', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Маппинг внутреннего статуса в группу для фронтенда
     */
    public static function statusGroup(?string $status): ?string
    {
        return self::STATUS_GROUPS[$status] ?? null;
    }

    /**
     * Маппинг внутреннего статуса → внешние статусы Uzum API
     */
    public static function internalToExternalStatuses(?array $internalStatuses): array
    {
        if (! $internalStatuses) {
            return array_keys(self::STATUS_MAP);
        }

        $reverse = [];
        foreach (self::STATUS_MAP as $external => $internal) {
            $reverse[$internal][] = $external;
        }

        $result = [];
        foreach ($internalStatuses as $st) {
            $result = array_merge($result, $reverse[$st] ?? []);
        }

        return $result ?: array_keys(self::STATUS_MAP);
    }
}
