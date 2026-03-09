<?php

declare(strict_types=1);

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use DateTimeInterface;

/**
 * Единый интерфейс для всех клиентов маркетплейсов
 *
 * Реализации:
 * - WildberriesClient (wb)
 * - OzonClient (ozon)
 * - UzumClient (uzum)
 * - YandexMarketClient (ym)
 */
interface MarketplaceClientInterface
{
    /**
     * Получить код маркетплейса (wb, ozon, uzum, ym)
     */
    public function getMarketplaceCode(): string;

    /**
     * Быстрая проверка доступности API (health-check)
     *
     * Использует легковесный endpoint для проверки валидности credentials.
     * Возвращает время ответа для мониторинга.
     *
     * @return array{success: bool, message: string, response_time_ms: int|null, data?: array}
     */
    public function ping(MarketplaceAccount $account): array;

    /**
     * Полная проверка подключения к API маркетплейса
     *
     * В отличие от ping(), может выполнять дополнительные проверки
     * (например, доступ к конкретным endpoints).
     *
     * @return array{success: bool, message: string, data?: array}
     */
    public function testConnection(MarketplaceAccount $account): array;

    /**
     * Синхронизация товаров (создание/обновление карточек на маркетплейсе)
     *
     * @param  MarketplaceProduct[]  $products  Массив товаров для синхронизации
     */
    public function syncProducts(MarketplaceAccount $account, array $products): void;

    /**
     * Синхронизация цен на маркетплейсе
     *
     * @param  MarketplaceProduct[]  $products  Массив товаров с обновлёнными ценами
     */
    public function syncPrices(MarketplaceAccount $account, array $products): void;

    /**
     * Синхронизация остатков на маркетплейсе
     *
     * @param  MarketplaceProduct[]  $products  Массив товаров с обновлёнными остатками
     */
    public function syncStocks(MarketplaceAccount $account, array $products): void;

    /**
     * Получение заказов с маркетплейса за период
     *
     * Возвращает массив заказов в унифицированном формате:
     * - external_order_id: string - ID заказа на маркетплейсе
     * - status: string - статус заказа (new, in_assembly, in_delivery, completed, cancelled)
     * - customer_name: ?string - имя покупателя
     * - customer_phone: ?string - телефон покупателя
     * - total_amount: float - сумма заказа
     * - currency: string - валюта
     * - ordered_at: string - дата создания заказа
     * - items: array - позиции заказа
     * - raw_payload: array - оригинальные данные от API
     *
     * @return array<int, array<string, mixed>> Массив заказов
     */
    public function fetchOrders(MarketplaceAccount $account, DateTimeInterface $from, DateTimeInterface $to): array;

    /**
     * Получение информации о товаре по внешнему ID
     *
     * @param  string  $externalId  ID товара на маркетплейсе (nmId для WB, product_id для Ozon, etc.)
     * @return array<string, mixed>|null Данные товара или null если не найден
     */
    public function getProductInfo(MarketplaceAccount $account, string $externalId): ?array;
}
