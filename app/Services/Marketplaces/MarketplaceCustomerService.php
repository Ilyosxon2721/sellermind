<?php

declare(strict_types=1);

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceCustomer;
use App\Models\MarketplaceCustomerOrder;
use App\Models\OzonOrder;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для извлечения клиентов из DBS заказов маркетплейсов.
 *
 * DBS (Delivery by Seller) заказы содержат контактные данные клиентов,
 * которые сохраняются в таблицу marketplace_customers для пополнения клиентской базы.
 *
 * Дедупликация: заказы отслеживаются через таблицу marketplace_customer_orders,
 * повторная синхронизация того же заказа НЕ увеличивает счётчик заказов.
 */
final class MarketplaceCustomerService
{
    /**
     * WB deliveryType: 1 = FBS, 2 = DBS, 6 = EDBS
     */
    private const WB_DBS_DELIVERY_TYPES = ['dbs', 'DBS', '2'];

    /**
     * Узум: DBS и EDBS содержат данные клиента
     */
    private const UZUM_DBS_DELIVERY_TYPES = ['DBS', 'EDBS'];

    /**
     * Извлечь и сохранить клиента из заказа Uzum (DBS/EDBS)
     */
    public function extractFromUzumOrder(MarketplaceAccount $account, UzumOrder $order): ?MarketplaceCustomer
    {
        if (! $this->isUzumDbsOrder($order)) {
            return null;
        }

        $phone = $this->normalizePhone($order->customer_phone);
        if (! $phone || ! $order->customer_name) {
            return null;
        }

        $address = $this->buildUzumAddress($order);

        return $this->upsertCustomer(
            companyId: $account->company_id,
            phone: $phone,
            name: $order->customer_name,
            source: 'uzum',
            address: $address,
            city: $order->delivery_city,
            orderAmount: (float) ($order->total_amount ?? 0),
            orderAt: $order->ordered_at,
            orderType: UzumOrder::class,
            orderId: $order->id,
            externalOrderId: $order->external_order_id,
            orderStatus: $order->status,
            currency: $order->currency ?? 'UZS',
        );
    }

    /**
     * Извлечь и сохранить клиента из заказа WB (DBS)
     */
    public function extractFromWbOrder(MarketplaceAccount $account, WbOrder $order): ?MarketplaceCustomer
    {
        if (! $this->isWbDbsOrder($order)) {
            return null;
        }

        $phone = $this->normalizePhone($order->customer_phone);
        if (! $phone || ! $order->customer_name) {
            return null;
        }

        return $this->upsertCustomer(
            companyId: $account->company_id,
            phone: $phone,
            name: $order->customer_name,
            source: 'wb',
            address: null,
            city: null,
            orderAmount: (float) ($order->total_amount ?? 0),
            orderAt: $order->ordered_at,
            orderType: WbOrder::class,
            orderId: $order->id,
            externalOrderId: $order->external_order_id,
            orderStatus: $order->status,
            currency: $order->currency ?? 'RUB',
        );
    }

    /**
     * Извлечь и сохранить клиента из заказа Ozon (DBS/FBS с данными клиента)
     */
    public function extractFromOzonOrder(MarketplaceAccount $account, OzonOrder $order): ?MarketplaceCustomer
    {
        $phone = $this->normalizePhone($order->customer_phone);
        if (! $phone || ! $order->customer_name) {
            return null;
        }

        return $this->upsertCustomer(
            companyId: $account->company_id,
            phone: $phone,
            name: $order->customer_name,
            source: 'ozon',
            address: $order->delivery_address,
            city: null,
            orderAmount: (float) ($order->total_price ?? 0),
            orderAt: $order->created_at_ozon,
            orderType: OzonOrder::class,
            orderId: $order->id,
            externalOrderId: $order->posting_number ?? $order->order_id,
            orderStatus: $order->status,
            currency: $order->currency ?? 'RUB',
        );
    }

    /**
     * Универсальный метод: извлечь клиента из любого маркетплейс-заказа
     */
    public function extractFromOrder(MarketplaceAccount $account, $order): ?MarketplaceCustomer
    {
        return match (true) {
            $order instanceof UzumOrder => $this->extractFromUzumOrder($account, $order),
            $order instanceof WbOrder => $this->extractFromWbOrder($account, $order),
            $order instanceof OzonOrder => $this->extractFromOzonOrder($account, $order),
            default => null,
        };
    }

    /**
     * Массовое извлечение клиентов из существующих DBS заказов аккаунта
     */
    public function extractFromExistingOrders(MarketplaceAccount $account): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        $query = match ($account->marketplace) {
            'uzum' => UzumOrder::where('marketplace_account_id', $account->id)
                ->whereIn('delivery_type', self::UZUM_DBS_DELIVERY_TYPES)
                ->whereNotNull('customer_phone')
                ->whereNotNull('customer_name'),
            'wb' => WbOrder::where('marketplace_account_id', $account->id)
                ->whereNotNull('customer_phone')
                ->whereNotNull('customer_name'),
            'ozon' => OzonOrder::where('marketplace_account_id', $account->id)
                ->whereNotNull('customer_phone')
                ->whereNotNull('customer_name'),
            default => null,
        };

        if (! $query) {
            return $stats;
        }

        // Сортируем по дате для корректной хронологии
        $sortColumn = $account->marketplace === 'ozon' ? 'created_at_ozon' : 'ordered_at';
        $query->orderBy($sortColumn)->chunk(100, function ($orders) use ($account, &$stats) {
            foreach ($orders as $order) {
                try {
                    $customer = $this->extractFromOrder($account, $order);
                    if ($customer) {
                        $customer->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
                    } else {
                        $stats['skipped']++;
                    }
                } catch (\Throwable $e) {
                    $stats['skipped']++;
                    Log::warning('Ошибка извлечения клиента из заказа', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        return $stats;
    }

    /**
     * Получить все заказы клиента с товарами
     *
     * @return Collection<MarketplaceCustomerOrder>
     */
    public function getCustomerOrders(MarketplaceCustomer $customer): Collection
    {
        $customerOrders = $customer->customerOrders()
            ->orderByDesc('ordered_at')
            ->get();

        // Подгружаем оригинальные заказы с items
        return $customerOrders->map(function (MarketplaceCustomerOrder $co) {
            $order = $this->loadOriginalOrder($co->order_type, $co->order_id);

            // Обновляем кэшированный статус если изменился
            if ($order && $order->status !== $co->status) {
                $co->update(['status' => $order->status]);
                $co->status = $order->status;
            }

            $items = $order ? $this->getOrderItems($order) : [];

            return [
                'id' => $co->id,
                'external_order_id' => $co->external_order_id,
                'source' => $co->source,
                'source_label' => $this->getSourceLabel($co->source),
                'status' => $co->status,
                'status_label' => $co->getStatusLabel(),
                'is_cancelled' => $co->isCancelled(),
                'total_amount' => $co->total_amount,
                'currency' => $co->currency,
                'ordered_at' => $co->ordered_at?->toIso8601String(),
                'items' => $items,
            ];
        });
    }

    /**
     * Создать или обновить клиента.
     *
     * Ключевая логика дедупликации:
     * - Клиент ищется по phone + company_id + source
     * - Заказ ищется в marketplace_customer_orders по order_type + order_id
     * - Если заказ уже привязан — только обновляем статус, НЕ увеличиваем счётчик
     * - Если заказ новый — привязываем и увеличиваем счётчик
     */
    private function upsertCustomer(
        int $companyId,
        string $phone,
        string $name,
        string $source,
        ?string $address,
        ?string $city,
        float $orderAmount,
        ?\DateTimeInterface $orderAt,
        ?string $orderType,
        ?int $orderId,
        ?string $externalOrderId,
        ?string $orderStatus,
        string $currency = 'UZS',
    ): MarketplaceCustomer {
        return DB::transaction(function () use (
            $companyId, $phone, $name, $source, $address, $city,
            $orderAmount, $orderAt, $orderType, $orderId,
            $externalOrderId, $orderStatus, $currency,
        ) {
            // 1. Найти или создать клиента
            $customer = MarketplaceCustomer::where('company_id', $companyId)
                ->where('phone', $phone)
                ->where('source', $source)
                ->first();

            $isNewCustomer = ! $customer;

            if ($isNewCustomer) {
                $customer = MarketplaceCustomer::create([
                    'company_id' => $companyId,
                    'phone' => $phone,
                    'name' => $name,
                    'source' => $source,
                    'address' => $address,
                    'city' => $city,
                    'orders_count' => 0,
                    'total_spent' => 0,
                    'first_order_at' => $orderAt ?? now(),
                    'last_order_at' => $orderAt ?? now(),
                    'last_order_type' => $orderType,
                    'last_order_id' => $orderId,
                ]);
            }

            // 2. Проверить, привязан ли уже этот заказ
            if ($orderType && $orderId) {
                $existingLink = MarketplaceCustomerOrder::where('marketplace_customer_id', $customer->id)
                    ->where('order_type', $orderType)
                    ->where('order_id', $orderId)
                    ->first();

                if ($existingLink) {
                    // Заказ уже привязан — только обновляем статус
                    $existingLink->update(['status' => $orderStatus]);

                    // Обновляем имя и адрес если есть новые данные
                    $this->updateCustomerInfo($customer, $name, $address, $city);

                    $customer->wasRecentlyCreated = false;

                    return $customer;
                }

                // 3. Привязать новый заказ
                MarketplaceCustomerOrder::create([
                    'marketplace_customer_id' => $customer->id,
                    'order_type' => $orderType,
                    'order_id' => $orderId,
                    'external_order_id' => $externalOrderId,
                    'source' => $source,
                    'status' => $orderStatus,
                    'total_amount' => $orderAmount,
                    'currency' => $currency,
                    'ordered_at' => $orderAt,
                ]);

                // 4. Увеличить счётчик и сумму (только для НОВОГО заказа)
                $customer->update([
                    'orders_count' => $customer->orders_count + 1,
                    'total_spent' => (float) $customer->total_spent + $orderAmount,
                    'last_order_at' => $orderAt ?? now(),
                    'last_order_type' => $orderType,
                    'last_order_id' => $orderId,
                ]);
            }

            // 5. Обновляем контактные данные
            $this->updateCustomerInfo($customer, $name, $address, $city);

            $customer->wasRecentlyCreated = $isNewCustomer;

            return $customer;
        });
    }

    /**
     * Обновить контактную информацию клиента
     */
    private function updateCustomerInfo(MarketplaceCustomer $customer, string $name, ?string $address, ?string $city): void
    {
        $updateData = [];

        if ($name) {
            $updateData['name'] = $name;
        }
        if ($address && ! $customer->address) {
            $updateData['address'] = $address;
        }
        if ($city && ! $customer->city) {
            $updateData['city'] = $city;
        }

        if ($updateData) {
            $customer->update($updateData);
        }
    }

    /**
     * Загрузить оригинальный заказ из БД
     */
    private function loadOriginalOrder(string $orderType, int $orderId)
    {
        return match ($orderType) {
            UzumOrder::class => UzumOrder::with('items')->find($orderId),
            WbOrder::class => WbOrder::with('items')->find($orderId),
            OzonOrder::class => OzonOrder::find($orderId),
            default => null,
        };
    }

    /**
     * Получить товары заказа в унифицированном формате
     */
    private function getOrderItems($order): array
    {
        if ($order instanceof UzumOrder) {
            return $order->items->map(fn ($item) => [
                'name' => $item->name,
                'sku' => $item->external_offer_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total_price' => $item->total_price,
            ])->toArray();
        }

        if ($order instanceof WbOrder) {
            return $order->items->map(fn ($item) => [
                'name' => $item->name,
                'sku' => $item->external_offer_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total_price' => $item->total_price,
            ])->toArray();
        }

        if ($order instanceof OzonOrder) {
            // У Ozon товары хранятся в JSON поле products
            $products = $order->getProductsList();

            return array_map(fn ($p) => [
                'name' => $p['name'] ?? '',
                'sku' => $p['offer_id'] ?? $p['sku'] ?? null,
                'quantity' => $p['quantity'] ?? 1,
                'price' => $p['price'] ?? 0,
                'total_price' => ($p['price'] ?? 0) * ($p['quantity'] ?? 1),
            ], $products);
        }

        return [];
    }

    /**
     * Проверка: заказ Uzum типа DBS/EDBS
     */
    private function isUzumDbsOrder(UzumOrder $order): bool
    {
        return in_array($order->delivery_type, self::UZUM_DBS_DELIVERY_TYPES, true);
    }

    /**
     * Проверка: заказ WB типа DBS
     *
     * WB deliveryType: 1 = FBS, 2 = DBS, 6 = EDBS
     * Данные клиента доступны только для DBS заказов
     */
    private function isWbDbsOrder(WbOrder $order): bool
    {
        $deliveryType = $order->wb_delivery_type;

        if (! $deliveryType) {
            return false;
        }

        return in_array((string) $deliveryType, self::WB_DBS_DELIVERY_TYPES, true);
    }

    /**
     * Нормализация номера телефона
     */
    private function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        // Убираем все нецифровые символы
        $digits = preg_replace('/\D/', '', $phone);

        if (! $digits || strlen($digits) < 9) {
            return null;
        }

        // Добавляем + если начинается с кода страны
        if (strlen($digits) >= 11) {
            return '+' . $digits;
        }

        return $digits;
    }

    /**
     * Собрать полный адрес Uzum из отдельных полей
     */
    private function buildUzumAddress(UzumOrder $order): ?string
    {
        if ($order->delivery_address_full) {
            return $order->delivery_address_full;
        }

        $parts = array_filter([
            $order->delivery_city,
            $order->delivery_street,
            $order->delivery_home ? 'д. ' . $order->delivery_home : null,
            $order->delivery_flat ? 'кв. ' . $order->delivery_flat : null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    /**
     * Название маркетплейса
     */
    private function getSourceLabel(string $source): string
    {
        return match ($source) {
            'uzum' => 'Uzum Market',
            'wb' => 'Wildberries',
            'ozon' => 'Ozon',
            'ym' => 'Yandex Market',
            default => $source,
        };
    }
}
