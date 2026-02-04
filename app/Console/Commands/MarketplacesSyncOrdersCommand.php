<?php

// file: app/Console/Commands/MarketplacesSyncOrdersCommand.php

namespace App\Console\Commands;

use App\Services\Marketplaces\Sync\OrdersSyncService;
use Illuminate\Console\Command;

class MarketplacesSyncOrdersCommand extends Command
{
    protected $signature = 'marketplaces:sync-orders
        {--marketplace=all : Маркетплейс (wb|ozon|uzum|ym|all)}
        {--days=7 : Количество дней назад для синхронизации}
        {--account= : ID конкретного аккаунта}';

    protected $description = 'Синхронизация заказов с маркетплейсов';

    public function __construct(
        protected OrdersSyncService $syncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $marketplace = $this->option('marketplace');
        $daysBack = (int) $this->option('days');
        $accountId = $this->option('account');

        $this->info('=== Синхронизация заказов маркетплейсов ===');
        $this->newLine();
        $this->line("Маркетплейс: {$marketplace}");
        $this->line("Период: {$daysBack} дней назад");

        if ($accountId) {
            $this->line("Аккаунт: #{$accountId}");
        }

        $this->newLine();

        $startTime = microtime(true);

        if ($accountId) {
            // Sync specific account
            $account = \App\Models\MarketplaceAccount::find($accountId);

            if (! $account) {
                $this->error("Аккаунт #{$accountId} не найден.");

                return self::FAILURE;
            }

            $this->info("Синхронизация аккаунта: {$account->name} ({$account->marketplace})...");
            $result = $this->syncService->syncAccountOrders($account, $daysBack);
            $results = [$accountId => $result];
        } else {
            // Sync all accounts
            $this->info('Запуск синхронизации...');
            $results = $this->syncService->syncAll($marketplace, $daysBack);
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Display results
        $this->newLine();
        $this->info('=== Результаты ===');

        $totalSuccess = 0;
        $totalErrors = 0;
        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($results as $accId => $result) {
            if ($result['success']) {
                $totalSuccess++;
                $stats = $result['stats'] ?? [];
                $totalCreated += $stats['created'] ?? 0;
                $totalUpdated += $stats['updated'] ?? 0;

                $this->line("  Аккаунт #{$accId}: <fg=green>OK</> - создано {$stats['created']}, обновлено {$stats['updated']}");
            } else {
                $totalErrors++;
                $error = $result['error'] ?? 'Unknown error';
                $this->line("  Аккаунт #{$accId}: <fg=red>ERROR</> - ".mb_substr($error, 0, 60));
            }
        }

        $this->newLine();
        $this->info("Итого: {$totalSuccess} успешно, {$totalErrors} ошибок");
        $this->info("Заказов: {$totalCreated} создано, {$totalUpdated} обновлено");
        $this->info("Время выполнения: {$duration} сек.");

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
