<?php

declare(strict_types=1);

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceCustomer;
use App\Models\OzonOrder;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для извлечения клиентов из DBS заказов маркетплейсов.
 *
 * DBS (Delivery by Seller) заказы содержат контактные данные клиентов,
 * которые сохраняются в таблицу marketplace_customers для пополнения клиентской базы.
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

        $query->orderBy('ordered_at')->chunk(100, function ($orders) use ($account, &$stats) {
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
     * Создать или обновить клиента
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
    ): MarketplaceCustomer {
        $existing = MarketplaceCustomer::where('company_id', $companyId)
            ->where('phone', $phone)
            ->where('source', $source)
            ->first();

        if ($existing) {
            // Обновляем: увеличиваем счётчик заказов и сумму
            $updateData = [
                'orders_count' => $existing->orders_count + 1,
                'total_spent' => (float) $existing->total_spent + $orderAmount,
                'last_order_at' => $orderAt ?? now(),
                'last_order_type' => $orderType,
                'last_order_id' => $orderId,
            ];

            // Обновляем имя и адрес если данные свежее
            if ($name) {
                $updateData['name'] = $name;
            }
            if ($address && ! $existing->address) {
                $updateData['address'] = $address;
            }
            if ($city && ! $existing->city) {
                $updateData['city'] = $city;
            }

            $existing->update($updateData);
            $existing->wasRecentlyCreated = false;

            return $existing;
        }

        $customer = MarketplaceCustomer::create([
            'company_id' => $companyId,
            'phone' => $phone,
            'name' => $name,
            'source' => $source,
            'address' => $address,
            'city' => $city,
            'orders_count' => 1,
            'total_spent' => $orderAmount,
            'first_order_at' => $orderAt ?? now(),
            'last_order_at' => $orderAt ?? now(),
            'last_order_type' => $orderType,
            'last_order_id' => $orderId,
        ]);

        $customer->wasRecentlyCreated = true;

        return $customer;
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
}
