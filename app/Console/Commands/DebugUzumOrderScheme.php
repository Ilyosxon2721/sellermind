<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\UzumOrder;
use App\Services\Uzum\Api\UzumApiManager;
use App\Services\Uzum\Api\UzumEndpoints;
use Illuminate\Console\Command;

/**
 * Диагностика: показать delivery_type заказов и проверить что возвращает API с scheme=DBS
 */
class DebugUzumOrderScheme extends Command
{
    protected $signature = 'uzum:debug-order-scheme
        {accountId : ID аккаунта}
        {--order= : Конкретный внешний ID заказа для проверки raw_payload}
        {--fix : Исправить delivery_type по результатам API-запроса}';

    protected $description = 'Диагностика определения FBS/DBS схемы заказов Uzum';

    public function handle(): int
    {
        $account = MarketplaceAccount::find($this->argument('accountId'));
        if (! $account) {
            $this->error('Аккаунт не найден');
            return self::FAILURE;
        }

        // Если указан конкретный заказ — показать его raw_payload
        if ($orderId = $this->option('order')) {
            return $this->inspectOrder($account, $orderId);
        }

        // Иначе — показать статистику и проверить API
        return $this->runDiagnostic($account);
    }

    private function inspectOrder(MarketplaceAccount $account, string $orderId): int
    {
        $order = UzumOrder::where('marketplace_account_id', $account->id)
            ->where('external_order_id', $orderId)
            ->first();

        if (! $order) {
            $this->error("Заказ #{$orderId} не найден в БД");
            return self::FAILURE;
        }

        $this->info("=== Заказ #{$orderId} ===");
        $this->line("delivery_type в БД: <comment>{$order->delivery_type}</comment>");

        $raw = is_string($order->raw_payload)
            ? json_decode($order->raw_payload, true)
            : $order->raw_payload;

        $this->line("Ключи raw_payload верхнего уровня:");
        $this->line('  ' . implode(', ', array_keys($raw ?? [])));

        $fields = ['scheme', 'deliveryType', 'deliveryScheme', 'orderType', 'type', 'fulfillmentType', 'logisticScheme'];
        foreach ($fields as $field) {
            $val = $raw[$field] ?? null;
            if ($val !== null) {
                $this->line("  {$field}: <info>{$val}</info>");
            } else {
                $this->line("  {$field}: <comment>не найдено</comment>");
            }
        }

        // Проверить deliveryInfo
        $deliveryInfo = $raw['deliveryInfo'] ?? [];
        if ($deliveryInfo) {
            $this->line("Ключи deliveryInfo: " . implode(', ', array_keys($deliveryInfo)));
        }

        return self::SUCCESS;
    }

    private function runDiagnostic(MarketplaceAccount $account): int
    {
        // 1. Статистика в БД
        $total = UzumOrder::where('marketplace_account_id', $account->id)->count();
        $byType = UzumOrder::where('marketplace_account_id', $account->id)
            ->selectRaw('delivery_type, COUNT(*) as cnt')
            ->groupBy('delivery_type')
            ->pluck('cnt', 'delivery_type');

        $this->info("=== БД: Заказы аккаунта #{$account->id} ===");
        $this->line("Всего: {$total}");
        foreach ($byType as $type => $cnt) {
            $this->line("  {$type}: {$cnt}");
        }

        // 2. Проверить API с scheme=DBS
        $this->newLine();
        $this->info("=== API запрос с scheme=DBS ===");

        try {
            $uzum = new UzumApiManager($account);

            // Получить shop IDs
            $shopIds = [];
            $credJson = $account->credentials_json ?? [];
            if (! empty($credJson['shop_ids'])) {
                $shopIds = array_values(array_filter(array_map('intval', $credJson['shop_ids'])));
            } elseif ($account->shop_id) {
                $shopIds = array_filter(array_map('intval', explode(',', (string) $account->shop_id)));
            }

            if (empty($shopIds)) {
                $this->warn('Нет shop_ids, пробую получить из API...');
                $shopIds = $uzum->shops()->ids();
            }

            $this->line('Shop IDs: ' . implode(', ', $shopIds));

            $dbsOrderIds = [];

            foreach ($shopIds as $shopId) {
                foreach (['CREATED', 'PACKING', 'PENDING_DELIVERY', 'DELIVERING', 'ACCEPTED_AT_DP',
                          'DELIVERED_TO_CUSTOMER_DELIVERY_POINT', 'COMPLETED', 'CANCELED', 'RETURNED'] as $status) {
                    $response = $uzum->api()->call(
                        UzumEndpoints::FBS_ORDERS_LIST,
                        query: [
                            'shopIds' => $shopId,
                            'status'  => $status,
                            'scheme'  => 'DBS',
                            'page'    => 0,
                            'size'    => 100,
                        ],
                    );

                    $payload = $response['payload'] ?? [];
                    $orders  = $payload['orders'] ?? $payload['list'] ?? $payload['orderList'] ?? $response['orderList'] ?? [];

                    foreach ($orders as $o) {
                        $id = (string) ($o['id'] ?? '');
                        if ($id) {
                            $dbsOrderIds[] = $id;
                        }
                    }

                    if (count($orders) > 0) {
                        $this->line("  shop={$shopId} status={$status} scheme=DBS: " . count($orders) . " заказов");
                        // Показать первый ключи первого заказа
                        $this->line("  Ключи первого заказа: " . implode(', ', array_keys($orders[0])));
                        if (isset($orders[0]['scheme'])) {
                            $this->line("  scheme поле: {$orders[0]['scheme']}");
                        }
                    }
                }
            }

            $dbsOrderIds = array_unique($dbsOrderIds);
            $this->newLine();
            $this->line("Итого DBS заказов из API: " . count($dbsOrderIds));

            if (! empty($dbsOrderIds)) {
                // Сколько в БД помечены неверно
                $wrongInDb = UzumOrder::where('marketplace_account_id', $account->id)
                    ->whereIn('external_order_id', $dbsOrderIds)
                    ->where('delivery_type', '!=', 'DBS')
                    ->count();

                $this->line("Из них в БД с неверным delivery_type (не DBS): {$wrongInDb}");

                if ($this->option('fix') && $wrongInDb > 0) {
                    UzumOrder::where('marketplace_account_id', $account->id)
                        ->whereIn('external_order_id', $dbsOrderIds)
                        ->update(['delivery_type' => 'DBS']);

                    $this->info("✓ Исправлено {$wrongInDb} заказов → delivery_type = DBS");
                } elseif ($wrongInDb > 0) {
                    $this->warn("Запустите с --fix чтобы исправить {$wrongInDb} заказов");
                    $this->line("IDs: " . implode(', ', array_slice($dbsOrderIds, 0, 10)) . (count($dbsOrderIds) > 10 ? '...' : ''));
                }
            }
        } catch (\Throwable $e) {
            $this->error('Ошибка API: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
