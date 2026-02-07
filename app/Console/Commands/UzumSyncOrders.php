<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\UzumOrder;
use App\Models\UzumOrderItem;
use App\Services\Marketplaces\MarketplaceHttpClient;
use App\Services\Marketplaces\UzumClient;
use App\Services\Stock\OrderStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UzumSyncOrders extends Command
{
    protected $signature = 'uzum:sync-orders
                            {--account= : ID аккаунта Uzum}
                            {--days=7 : Количество дней для синхронизации (по умолчанию 7)}';

    protected $description = 'Синхронизация заказов Uzum Market';

    protected OrderStockService $orderStockService;

    public function __construct(OrderStockService $orderStockService)
    {
        parent::__construct();
        $this->orderStockService = $orderStockService;
    }

    public function handle(): int
    {
        $accountId = $this->option('account');
        $days = (int) $this->option('days');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->where('marketplace', 'uzum')->get()
            : MarketplaceAccount::where('marketplace', 'uzum')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных Uzum Market аккаунтов');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            try {
                $this->syncOrders($account, $days);
            } catch (\Exception $e) {
                $this->error("Ошибка синхронизации: {$e->getMessage()}");
                Log::error('Uzum orders sync failed', [
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
        $httpClient = app(MarketplaceHttpClient::class);
        $issueDetector = app(\App\Services\Marketplaces\IssueDetectorService::class);
        $client = new UzumClient($httpClient, $issueDetector);

        $this->line('  Начинаем синхронизацию заказов...');
        $startTime = microtime(true);

        $from = now()->subDays($days);
        $to = now();

        $this->line("  Период: {$from->format('Y-m-d')} - {$to->format('Y-m-d')}");

        // Получить заказы из Uzum API
        $orders = $client->fetchOrders($account, $from, $to);

        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($orders as $orderData) {
            try {
                $externalOrderId = $orderData['external_order_id'] ?? $orderData['order_id'] ?? null;

                if (! $externalOrderId) {
                    throw new \Exception('Missing external_order_id in order data');
                }

                // Сохраняем старый статус ДО updateOrCreate
                // (после updateOrCreate getOriginal() возвращает уже новый статус)
                $existingOrder = UzumOrder::where('marketplace_account_id', $account->id)
                    ->where('external_order_id', $externalOrderId)
                    ->first();
                $oldStatus = $existingOrder?->status;

                // Найти или создать заказ
                $order = UzumOrder::updateOrCreate(
                    [
                        'marketplace_account_id' => $account->id,
                        'external_order_id' => $externalOrderId,
                    ],
                    [
                        'status' => $orderData['status'] ?? 'unknown',
                        'status_normalized' => $orderData['status_normalized'] ?? null,
                        'delivery_type' => $orderData['delivery_type'] ?? null,
                        'shop_id' => $orderData['shop_id'] ?? null,
                        'customer_name' => $orderData['customer']['name'] ?? null,
                        'customer_phone' => $orderData['customer']['phone'] ?? null,
                        'total_amount' => $orderData['total_amount'] ?? 0,
                        'currency' => $orderData['currency'] ?? 'UZS',
                        'ordered_at' => isset($orderData['ordered_at']) && is_numeric($orderData['ordered_at'])
                            ? \Carbon\Carbon::createFromTimestampMs($orderData['ordered_at'], config('app.timezone'))
                            : ($orderData['ordered_at'] ?? now()),
                        'delivered_at' => isset($orderData['delivered_at']) && is_numeric($orderData['delivered_at'])
                            ? \Carbon\Carbon::createFromTimestampMs($orderData['delivered_at'], config('app.timezone'))
                            : ($orderData['delivered_at'] ?? null),
                        'delivery_address_full' => $orderData['customer']['address'] ?? null,
                        'delivery_city' => $orderData['customer']['city'] ?? null,
                        'delivery_street' => $orderData['customer']['street'] ?? null,
                        'delivery_home' => $orderData['customer']['home'] ?? null,
                        'delivery_flat' => $orderData['customer']['flat'] ?? null,
                        'raw_payload' => $orderData['raw_payload'] ?? $orderData,
                    ]
                );

                $wasRecentlyCreated = $order->wasRecentlyCreated;
                $newStatus = $order->status;

                // Синхронизировать товары в заказе
                if (! empty($orderData['items'])) {
                    // Удалить старые товары
                    $order->items()->delete();

                    // Создать новые товары
                    foreach ($orderData['items'] as $item) {
                        UzumOrderItem::create([
                            'uzum_order_id' => $order->id,
                            'external_offer_id' => $item['external_offer_id'] ?? $item['skuId'] ?? null,
                            'name' => $item['name'] ?? null,
                            'quantity' => $item['quantity'] ?? 1,
                            'price' => $item['price'] ?? 0,
                            'total_price' => $item['total_price'] ?? ($item['price'] * $item['quantity']),
                            'raw_payload' => $item['raw_payload'] ?? $item,
                        ]);
                    }
                }

                // Обработка остатков (резервы, списание, возврат)
                if ($wasRecentlyCreated || $oldStatus !== $newStatus) {
                    try {
                        $items = $this->orderStockService->getOrderItems($order, 'uzum');
                        $stockResult = $this->orderStockService->processOrderStatusChange(
                            $account,
                            $order,
                            $oldStatus,
                            $newStatus,
                            $items
                        );

                        if ($stockResult['action'] !== 'none') {
                            Log::info('Uzum order stock processed', [
                                'order_id' => $order->id,
                                'external_order_id' => $externalOrderId,
                                'old_status' => $oldStatus,
                                'new_status' => $newStatus,
                                'action' => $stockResult['action'] ?? 'unknown',
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to process Uzum order stock', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Страховка: если заказ отменён или продан, но резерв всё ещё активен
                $this->ensureStuckReservationsProcessed($account, $order);

                if ($wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                $synced++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to sync Uzum order', [
                    'account_id' => $account->id,
                    'external_order_id' => $orderData['external_order_id'] ?? 'unknown',
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

        if ($errors > 0) {
            $this->warn("    Ошибок: {$errors}");
        }

        Log::info('Uzum orders synced', [
            'account_id' => $account->id,
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'duration' => $duration,
        ]);
    }

    /**
     * Страховка: если заказ в финальном статусе (отменён/продан),
     * но stock_status всё ещё 'reserved' — принудительно обработать.
     */
    protected function ensureStuckReservationsProcessed(MarketplaceAccount $account, UzumOrder $order): void
    {
        $stockStatus = $order->stock_status ?? 'none';

        if ($stockStatus !== 'reserved') {
            return;
        }

        $status = $order->status;
        $cancelledStatuses = OrderStockService::CANCELLED_STATUSES['uzum'] ?? [];
        $soldStatuses = OrderStockService::SOLD_STATUSES['uzum'] ?? [];

        $isCancelled = in_array($status, $cancelledStatuses, true);
        $isSold = in_array($status, $soldStatuses, true);

        if (! $isCancelled && ! $isSold) {
            return;
        }

        Log::info('UzumSyncOrders: Found stuck reservation, processing', [
            'order_id' => $order->id,
            'external_order_id' => $order->external_order_id,
            'order_status' => $status,
            'stock_status' => $stockStatus,
            'action' => $isCancelled ? 'release' : 'sold',
        ]);

        try {
            $items = $this->orderStockService->getOrderItems($order, 'uzum');
            $this->orderStockService->processOrderStatusChange(
                $account,
                $order,
                null,
                $status,
                $items
            );
        } catch (\Throwable $e) {
            Log::error('UzumSyncOrders: Failed to process stuck reservation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
