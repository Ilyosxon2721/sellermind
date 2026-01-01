<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use App\Services\Marketplaces\OzonClient;
use App\Services\Marketplaces\MarketplaceHttpClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OzonSyncOrders extends Command
{
    protected $signature = 'ozon:sync-orders
                            {--account= : ID аккаунта OZON}
                            {--days=7 : Количество дней для синхронизации (по умолчанию 7)}';

    protected $description = 'Синхронизация заказов OZON';

    public function handle(): int
    {
        $accountId = $this->option('account');
        $days = (int) $this->option('days');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->where('marketplace', 'ozon')->get()
            : MarketplaceAccount::where('marketplace', 'ozon')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных OZON аккаунтов');
            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            try {
                $this->syncOrders($account, $days);
            } catch (\Exception $e) {
                $this->error("Ошибка синхронизации: {$e->getMessage()}");
                Log::error('OZON orders sync failed', [
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
        $client = new OzonClient($httpClient);

        $this->line('  Начинаем синхронизацию заказов...');
        $startTime = microtime(true);

        $from = now()->subDays($days);
        $to = now();

        $this->line("  Период: {$from->format('Y-m-d')} - {$to->format('Y-m-d')}");

        // Получить заказы из OZON API
        $orders = $client->fetchOrders($account, $from, $to);

        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($orders as $orderData) {
            try {
                // Извлечь товары из заказа
                $products = [];
                foreach ($orderData['items'] ?? [] as $item) {
                    $products[] = [
                        'sku' => $item['external_product_id'] ?? $item['sku'] ?? null,
                        'name' => $item['name'] ?? null,
                        'quantity' => $item['quantity'] ?? 0,
                        'price' => $item['price'] ?? 0,
                        'offer_id' => $item['sku'] ?? null,
                    ];
                }

                // Получить данные из raw_payload, если доступны
                $rawPayload = $orderData['raw_payload'] ?? $orderData;
                $postingNumber = $orderData['external_order_id'] ?? $rawPayload['posting_number'] ?? null;
                $orderId = $rawPayload['order_id'] ?? $rawPayload['order_number'] ?? $postingNumber;

                if (!$postingNumber) {
                    throw new \Exception('Missing posting_number in order data');
                }

                // Найти или создать заказ
                $order = OzonOrder::updateOrCreate(
                    [
                        'marketplace_account_id' => $account->id,
                        'posting_number' => $postingNumber,
                    ],
                    [
                        'order_id' => $orderId,
                        'status' => $rawPayload['status'] ?? $orderData['status'] ?? 'unknown',
                        'substatus' => $rawPayload['substatus'] ?? null,
                        'total_price' => $orderData['total_amount'] ?? 0,
                        'currency' => $orderData['currency'] ?? 'RUB',
                        'order_data' => $rawPayload,
                        'products' => $products,
                        'customer_name' => $orderData['customer']['name'] ?? null,
                        'customer_phone' => $rawPayload['customer']['phone'] ?? null,
                        'delivery_address' => $orderData['customer']['address'] ?? null,
                        'delivery_method' => $orderData['delivery_type'] ?? null,
                        'in_process_at' => $orderData['ordered_at'] ?? now(),
                        'shipment_date' => $orderData['delivery_date'] ?? null,
                        'created_at_ozon' => $rawPayload['created_at'] ?? $orderData['ordered_at'] ?? now(),
                    ]
                );

                if ($order->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                $synced++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Failed to sync OZON order', [
                    'account_id' => $account->id,
                    'posting_number' => $orderData['external_order_id'] ?? 'unknown',
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

        Log::info('OZON orders synced', [
            'account_id' => $account->id,
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'duration' => $duration,
        ]);
    }
}
