<?php

// file: app/Console/Commands/WildberriesSyncOrders.php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class WildberriesSyncOrders extends Command
{
    protected $signature = 'wb:sync-orders
                            {account_id : ID аккаунта Wildberries}
                            {--from= : Дата начала синхронизации (YYYY-MM-DD)}
                            {--days=7 : Количество дней для синхронизации (по умолчанию 7)}
                            {--new-only : Загрузить только новые FBS заказы (Marketplace API)}';

    protected $description = 'Синхронизация заказов Wildberries';

    public function handle(): int
    {
        $accountId = (int) $this->argument('account_id');

        $account = MarketplaceAccount::find($accountId);

        if (! $account) {
            $this->error("Аккаунт с ID {$accountId} не найден");

            return self::FAILURE;
        }

        if (! $account->isWildberries()) {
            $this->error("Аккаунт #{$accountId} не является Wildberries");

            return self::FAILURE;
        }

        $this->info("Синхронизация заказов для аккаунта: {$account->name}");

        // Create sync log
        $syncLog = MarketplaceSyncLog::create([
            'marketplace_account_id' => $account->id,
            'type' => 'orders',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $httpClient = new WildberriesHttpClient($account);
            $service = new WildberriesOrderService($httpClient);

            if ($this->option('new-only')) {
                // Fetch only new FBS orders from Marketplace API
                $this->info('Загрузка новых FBS заказов (Marketplace API)...');
                $result = $service->fetchNewOrders($account);
            } else {
                // Full sync from Statistics API
                $fromOption = $this->option('from');
                $days = (int) $this->option('days');

                $from = $fromOption
                    ? Carbon::parse($fromOption)
                    : now()->subDays($days);

                $this->info('Загрузка заказов с '.$from->format('Y-m-d').' (Statistics API)...');
                $result = $service->syncOrders($account, $from);
            }

            // Update sync log
            $syncLog->update([
                'status' => empty($result['errors']) ? 'success' : 'warning',
                'finished_at' => now(),
                'message' => sprintf(
                    'Синхронизировано: %d (создано: %d, обновлено: %d)',
                    $result['synced'],
                    $result['created'],
                    $result['updated'] ?? 0
                ),
                'details' => $result,
            ]);

            // Output results
            $this->newLine();
            $this->info('✓ Синхронизация заказов завершена!');
            $this->table(
                ['Метрика', 'Значение'],
                [
                    ['Всего синхронизировано', $result['synced']],
                    ['Создано новых', $result['created']],
                    ['Обновлено', $result['updated'] ?? 0],
                    ['Ошибок', count($result['errors'])],
                ]
            );

            if (! empty($result['errors'])) {
                $this->newLine();
                $this->warn('Ошибки при синхронизации:');
                foreach (array_slice($result['errors'], 0, 5) as $error) {
                    $id = $error['srid'] ?? $error['order_id'] ?? 'unknown';
                    $msg = $error['error'] ?? 'Unknown error';
                    $this->line("  - {$id}: {$msg}");
                }
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $syncLog->update([
                'status' => 'error',
                'finished_at' => now(),
                'message' => $e->getMessage(),
            ]);

            $this->error("Ошибка синхронизации: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
