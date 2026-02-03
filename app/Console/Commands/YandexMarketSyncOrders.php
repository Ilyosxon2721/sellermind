<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\YandexMarketOrder;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use App\Services\Marketplaces\YandexMarket\YandexMarketHttpClient;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class YandexMarketSyncOrders extends Command
{
    protected $signature = 'ym:sync-orders
                            {--account= : ID аккаунта Yandex Market}
                            {--days=7 : Количество дней для синхронизации (по умолчанию 7)}';

    protected $description = 'Синхронизация заказов Yandex Market';

    public function handle(): int
    {
        $accountId = $this->option('account');
        $days = (int) $this->option('days');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->where('marketplace', 'ym')->get()
            : MarketplaceAccount::where('marketplace', 'ym')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных Yandex Market аккаунтов');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            try {
                $this->syncOrders($account, $days);
            } catch (\Exception $e) {
                $this->error("Ошибка синхронизации: {$e->getMessage()}");
                Log::error('YandexMarket orders sync failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function syncOrders(MarketplaceAccount $account, int $days): void
    {
        $httpClient = app(YandexMarketHttpClient::class);
        $client = new YandexMarketClient($httpClient);
        $orderStockService = new OrderStockService;

        $this->line('  Начинаем синхронизацию заказов...');
        $startTime = microtime(true);

        $from = now()->subDays($days);
        $to = now();

        $this->line("  Период: {$from->format('Y-m-d')} - {$to->format('Y-m-d')}");

        // Получить заказы из YM API
        $orders = $client->fetchOrders($account, $from, $to);

        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = 0;
        $stockProcessed = 0;

        foreach ($orders as $orderData) {
            try {
                $orderId = $orderData['external_order_id'] ?? null;
                if (! $orderId) {
                    continue;
                }

                // Сохраняем старый статус для обработки остатков
                $existingOrder = YandexMarketOrder::where('marketplace_account_id', $account->id)
                    ->where('order_id', $orderId)
                    ->first();
                $oldStatus = $existingOrder?->status;

                $newStatus = $orderData['status'] ?? 'unknown';

                // Найти или создать заказ
                $order = YandexMarketOrder::updateOrCreate(
                    [
                        'marketplace_account_id' => $account->id,
                        'order_id' => $orderId,
                    ],
                    [
                        'status' => $newStatus,
                        'status_normalized' => $orderData['status_normalized'] ?? null,
                        'substatus' => $orderData['substatus'] ?? null,
                        'total_price' => $orderData['total_amount'] ?? 0,
                        'currency' => $orderData['currency'] ?? 'RUR',
                        'customer_name' => $orderData['customer_name'] ?? null,
                        'customer_phone' => $orderData['customer_phone'] ?? null,
                        'delivery_type' => $orderData['delivery_type'] ?? null,
                        'delivery_service' => $orderData['delivery_service'] ?? null,
                        'items_count' => count($orderData['items'] ?? []),
                        'order_data' => $orderData['raw_payload'] ?? $orderData,
                        'created_at_ym' => isset($orderData['ordered_at']) ? \Carbon\Carbon::parse($orderData['ordered_at']) : null,
                    ]
                );

                $wasCreated = $order->wasRecentlyCreated;

                if ($wasCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                $synced++;

                // Обработка изменения остатков через OrderStockService
                if ($wasCreated || $oldStatus !== $newStatus) {
                    try {
                        $items = $orderStockService->getOrderItems($order, 'ym');
                        $stockResult = $orderStockService->processOrderStatusChange(
                            $account,
                            $order,
                            $oldStatus,
                            $newStatus,
                            $items
                        );

                        if (($stockResult['action'] ?? 'none') !== 'none') {
                            $stockProcessed++;
                        }

                        Log::info('YM order stock processed', [
                            'order_id' => $order->id,
                            'ym_order_id' => $orderId,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'stock_result' => $stockResult,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to process YM order stock', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to sync YM order', [
                    'account_id' => $account->id,
                    'order_id' => $orderData['external_order_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                    'order_data' => json_encode($orderData),
                ]);
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->info("  ✓ Синхронизация завершена за {$duration} сек");
        $this->line("    Всего обработано: {$synced}");
        $this->line("    Новых заказов: {$created}");
        $this->line("    Обновлено: {$updated}");
        $this->line("    Остатки обработаны: {$stockProcessed}");

        if ($errors > 0) {
            $this->warn("    Ошибок: {$errors}");
        }

        Log::info('YandexMarket orders synced', [
            'account_id' => $account->id,
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'stock_processed' => $stockProcessed,
            'errors' => $errors,
            'duration' => $duration,
        ]);
    }
}
