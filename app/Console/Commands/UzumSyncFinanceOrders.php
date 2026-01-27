<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\UzumFinanceOrder;
use App\Services\Marketplaces\UzumClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UzumSyncFinanceOrders extends Command
{
    protected $signature = 'uzum:sync-finance-orders
                            {--account= : Specific account ID to sync}
                            {--days=90 : Sync orders for last N days (0 = all time)}
                            {--pages=0 : Max pages to fetch per shop (0 = unlimited)}
                            {--full : Full sync - ignore days limit, fetch all orders}';

    protected $description = 'Sync Uzum finance orders for analytics (all order types: FBO/FBS/DBS/EDBS)';

    public function handle(UzumClient $client): int
    {
        $accountId = $this->option('account');
        $days = (int) $this->option('days');
        $maxPages = (int) $this->option('pages');
        $fullSync = $this->option('full');

        // Если --full, игнорируем days
        $dateFrom = null;
        if (!$fullSync && $days > 0) {
            $dateFrom = now()->subDays($days)->startOfDay();
            $this->info("Syncing orders from last {$days} days (since {$dateFrom->format('Y-m-d')})");
        } else {
            $this->info("Full sync mode - fetching all orders");
        }

        $query = MarketplaceAccount::where('marketplace', 'uzum')
            ->where('is_active', true);

        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('No active Uzum accounts found.');
            return Command::SUCCESS;
        }

        $this->info("Syncing finance orders for {$accounts->count()} Uzum account(s)...");

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalErrors = 0;

        foreach ($accounts as $account) {
            $this->line('');
            $this->info("Processing account: {$account->name} (ID: {$account->id})");

            try {
                $result = $this->syncAccountFinanceOrders($client, $account, $maxPages, $dateFrom);

                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
                $totalErrors += $result['errors'];

                $this->info("  Created: {$result['created']}, Updated: {$result['updated']}, Errors: {$result['errors']}");
            } catch (\Throwable $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error('UzumSyncFinanceOrders account failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $totalErrors++;
            }
        }

        $this->line('');
        $this->info("Sync completed. Total - Created: {$totalCreated}, Updated: {$totalUpdated}, Errors: {$totalErrors}");

        return Command::SUCCESS;
    }

    protected function syncAccountFinanceOrders(UzumClient $client, MarketplaceAccount $account, int $maxPages, ?\Carbon\Carbon $dateFrom = null): array
    {
        $created = 0;
        $updated = 0;
        $errors = 0;
        $totalProcessed = 0;

        // Получаем список магазинов
        $shops = $client->fetchShops($account);
        $shopIds = array_column($shops, 'id');

        $this->line("  Found " . count($shopIds) . " shops");

        // Конвертируем дату в timestamp (миллисекунды) для API
        $dateFromMs = $dateFrom ? $dateFrom->getTimestampMs() : null;

        foreach ($shopIds as $shopId) {
            $page = 0;
            $pagesLoaded = 0;
            $shopCreated = 0;
            $shopUpdated = 0;
            $itemsCount = 0;

            do {
                try {
                    $response = $client->fetchFinanceOrders($account, [$shopId], $page, 100, false, $dateFromMs);
                    $items = $response['orderItems'] ?? [];
                    $totalElements = $response['totalElements'] ?? 0;

                    if ($page === 0 && $totalElements > 0) {
                        $shopName = collect($shops)->firstWhere('id', $shopId)['name'] ?? $shopId;
                        $this->line("  Shop {$shopName}: {$totalElements} orders");
                    }

                    // Обрабатываем пачку сразу, не накапливая в памяти
                    foreach ($items as $item) {
                        try {
                            $data = $client->mapFinanceOrderData($item);

                            if (!$data['uzum_id']) {
                                $errors++;
                                continue;
                            }

                            $order = UzumFinanceOrder::updateOrCreate(
                                [
                                    'marketplace_account_id' => $account->id,
                                    'uzum_id' => $data['uzum_id'],
                                ],
                                array_merge($data, [
                                    'marketplace_account_id' => $account->id,
                                ])
                            );

                            if ($order->wasRecentlyCreated) {
                                $shopCreated++;
                            } else {
                                $shopUpdated++;
                            }
                            $totalProcessed++;
                        } catch (\Throwable $e) {
                            $errors++;
                            Log::warning('UzumSyncFinanceOrders item failed', [
                                'account_id' => $account->id,
                                'shop_id' => $shopId,
                                'item_id' => $item['id'] ?? null,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $pagesLoaded++;
                    $page++;

                    // Показываем прогресс каждые 10 страниц
                    if ($pagesLoaded % 10 === 0) {
                        $this->line("    Page {$page}, processed: {$totalProcessed}");
                    }

                    // Задержка чтобы не превысить rate limit
                    if (!empty($items)) {
                        usleep(200000); // 200ms
                    }

                    // Проверка лимита страниц
                    if ($maxPages > 0 && $pagesLoaded >= $maxPages) {
                        break;
                    }

                    // Сохраняем количество для проверки продолжения
                    $itemsCount = count($items);

                    // Освобождаем память
                    unset($items, $response);
                    gc_collect_cycles();

                } catch (\Throwable $e) {
                    Log::warning('UzumSyncFinanceOrders page failed', [
                        'account_id' => $account->id,
                        'shop_id' => $shopId,
                        'page' => $page,
                        'error' => $e->getMessage(),
                    ]);
                    $this->warn("    Error on page {$page}: " . $e->getMessage());
                    break;
                }
            } while ($itemsCount === 100);

            $created += $shopCreated;
            $updated += $shopUpdated;

            if ($shopCreated > 0 || $shopUpdated > 0) {
                $this->info("    Completed: +{$shopCreated} new, ~{$shopUpdated} updated");
            }
        }

        $this->line("  Total processed: {$totalProcessed}");

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
}
