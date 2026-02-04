<?php

// file: app/Console/Commands/WildberriesSyncStocks.php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesStockService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class WildberriesSyncStocks extends Command
{
    protected $signature = 'wb:sync-stocks
                            {account_id : ID аккаунта Wildberries}
                            {--from= : Дата начала синхронизации (YYYY-MM-DD)}';

    protected $description = 'Синхронизация остатков Wildberries (Statistics API)';

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

        $fromOption = $this->option('from');
        $from = $fromOption ? Carbon::parse($fromOption) : null;

        $this->info("Синхронизация остатков для аккаунта: {$account->name}");
        $this->info('Дата начала: '.($from ? $from->format('Y-m-d') : 'вчера'));

        // Create sync log
        $syncLog = MarketplaceSyncLog::create([
            'marketplace_account_id' => $account->id,
            'type' => 'stocks',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $httpClient = new WildberriesHttpClient($account);
            $service = new WildberriesStockService($httpClient);

            $this->info('Загрузка остатков из WB Statistics API...');

            $result = $service->syncStocks($account, $from);

            // Update sync log
            $syncLog->update([
                'status' => empty($result['errors']) ? 'success' : 'warning',
                'finished_at' => now(),
                'message' => sprintf(
                    'Синхронизировано: %d, складов создано: %d, товаров обновлено: %d',
                    $result['synced'],
                    $result['warehouses_created'],
                    $result['products_updated']
                ),
                'details' => $result,
            ]);

            // Output results
            $this->newLine();
            $this->info('✓ Синхронизация остатков завершена!');
            $this->table(
                ['Метрика', 'Значение'],
                [
                    ['Записей обработано', $result['synced']],
                    ['Складов создано', $result['warehouses_created']],
                    ['Товаров обновлено', $result['products_updated']],
                    ['Ошибок', count($result['errors'])],
                ]
            );

            if (! empty($result['errors'])) {
                $this->newLine();
                $this->warn('Ошибки при синхронизации:');
                foreach (array_slice($result['errors'], 0, 5) as $error) {
                    $nmId = $error['nm_id'] ?? 'unknown';
                    $msg = $error['error'] ?? 'Unknown error';
                    $this->line("  - nmID {$nmId}: {$msg}");
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
