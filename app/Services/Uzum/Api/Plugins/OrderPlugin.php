<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api\Plugins;

use App\Services\Uzum\Api\UzumApi;
use App\Services\Uzum\Api\UzumEndpoints;

/**
 * Плагин: FBS заказы
 */
final class OrderPlugin
{
    /** Все возможные статусы FBS заказа */
    public const STATUSES = [
        'CREATED',
        'PACKING',
        'PENDING_DELIVERY',
        'DELIVERING',
        'DELIVERED',
        'ACCEPTED_AT_DP',
        'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
        'COMPLETED',
        'CANCELED',
        'PENDING_CANCELLATION',
        'RETURNED',
    ];

    public function __construct(
        private readonly UzumApi $api,
    ) {}

    /**
     * Получить список заказов (одна страница)
     */
    public function list(string $shopIds, ?string $status = null, int $page = 0, int $size = 50, array $extra = []): array
    {
        $query = array_merge(['shopIds' => $shopIds, 'page' => $page, 'size' => $size], $extra);
        if ($status) {
            $query['status'] = $status;
        }

        $response = $this->api->call(UzumEndpoints::FBS_ORDERS_LIST, query: $query);

        return $response['orderList'] ?? $response['payload']['orderList'] ?? [];
    }

    /**
     * Получить ВСЕ заказы по статусу (с пагинацией)
     */
    public function all(string $shopIds, ?string $status = null, int $pageSize = 50): array
    {
        $query = ['shopIds' => $shopIds];
        if ($status) {
            $query['status'] = $status;
        }

        return $this->api->paginate(
            UzumEndpoints::FBS_ORDERS_LIST,
            query: $query,
            dataKey: 'orderList',
            pageSize: $pageSize,
        );
    }

    /**
     * Получить количество заказов по статусам
     */
    public function count(string $shopIds, ?string $status = null): array
    {
        $query = ['shopIds' => $shopIds];
        if ($status) {
            $query['status'] = $status;
        }

        return $this->api->call(UzumEndpoints::FBS_ORDERS_COUNT, query: $query);
    }

    /**
     * Получить детали заказа
     */
    public function detail(int $orderId): array
    {
        $response = $this->api->call(
            UzumEndpoints::FBS_ORDER_DETAIL,
            params: ['orderId' => $orderId],
        );

        return $response['payload'] ?? $response;
    }

    /**
     * Подтвердить заказ
     */
    public function confirm(int $orderId): array
    {
        $response = $this->api->call(
            UzumEndpoints::FBS_ORDER_CONFIRM,
            params: ['orderId' => $orderId],
        );

        return $response['payload'] ?? $response;
    }

    /**
     * Отменить заказ
     */
    public function cancel(int $orderId, ?int $reasonId = null, ?string $reason = null): array
    {
        $body = [];
        if ($reasonId !== null) {
            $body['reasonId'] = $reasonId;
        }
        if ($reason !== null) {
            $body['reason'] = $reason;
        }

        $response = $this->api->call(
            UzumEndpoints::FBS_ORDER_CANCEL,
            params: ['orderId' => $orderId],
            body: $body,
        );

        return $response['payload'] ?? $response;
    }

    /**
     * Получить этикетку заказа (base64 PDF)
     */
    public function label(int $orderId, string $size = 'LARGE'): array
    {
        return $this->api->call(
            UzumEndpoints::FBS_ORDER_LABEL,
            params: ['orderId' => $orderId],
            query: ['size' => $size],
        );
    }

}
