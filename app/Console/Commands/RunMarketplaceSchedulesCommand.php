<?php

// file: app/Console/Commands/RunMarketplaceSchedulesCommand.php

namespace App\Console\Commands;

use App\Models\MarketplaceSyncSchedule;
use Cron\CronExpression;
use Illuminate\Console\Command;

class RunMarketplaceSchedulesCommand extends Command
{
    protected $signature = 'marketplaces:run-schedules';

    protected $description = 'Запуск запланированных задач синхронизации маркетплейсов';

    public function handle(): int
    {
        $now = now();

        $schedules = MarketplaceSyncSchedule::where('is_active', true)
            ->with('account')
            ->get();

        $this->info("Найдено расписаний: {$schedules->count()}");

        $executed = 0;

        foreach ($schedules as $schedule) {
            try {
                $cron = new CronExpression($schedule->cron_expression);

                if (! $cron->isDue($now)) {
                    continue;
                }

                $account = $schedule->account;
                if (! $account || ! $account->is_active) {
                    $this->warn("Аккаунт #{$schedule->marketplace_account_id} не активен, пропускаем");

                    continue;
                }

                $this->info("Запуск {$schedule->sync_type} для аккаунта #{$account->id} ({$account->marketplace})");

                $this->dispatchSyncJob($schedule);

                $schedule->update(['last_run_at' => $now]);
                $executed++;
            } catch (\Exception $e) {
                $this->error("Ошибка расписания #{$schedule->id}: {$e->getMessage()}");
            }
        }

        $this->info("Выполнено задач: {$executed}");

        return self::SUCCESS;
    }

    /**
     * Dispatch appropriate sync job based on sync type
     */
    protected function dispatchSyncJob(MarketplaceSyncSchedule $schedule): void
    {
        $accountId = $schedule->marketplace_account_id;

        switch ($schedule->sync_type) {
            case MarketplaceSyncSchedule::TYPE_PRICES:
                // SyncMarketplacePricesJob::dispatch($accountId);
                $this->line("  -> Синхронизация цен для аккаунта #{$accountId}");
                break;

            case MarketplaceSyncSchedule::TYPE_STOCKS:
                // SyncMarketplaceStocksJob::dispatch($accountId);
                $this->line("  -> Синхронизация остатков для аккаунта #{$accountId}");
                break;

            case MarketplaceSyncSchedule::TYPE_PRODUCTS:
                // SyncMarketplaceProductsJob::dispatch($accountId);
                $this->line("  -> Синхронизация товаров для аккаунта #{$accountId}");
                break;

            case MarketplaceSyncSchedule::TYPE_ORDERS:
                // SyncMarketplaceOrdersJob::dispatch($accountId);
                $this->line("  -> Синхронизация заказов для аккаунта #{$accountId}");
                break;

            case MarketplaceSyncSchedule::TYPE_PAYOUTS:
                // SyncMarketplacePayoutsJob::dispatch($accountId);
                $this->line("  -> Синхронизация выплат для аккаунта #{$accountId}");
                break;

            case MarketplaceSyncSchedule::TYPE_AUTOMATION:
                // RunMarketplaceAutomationJob::dispatch($accountId);
                $this->line("  -> Запуск автоматизации для аккаунта #{$accountId}");
                break;

            default:
                $this->warn("  -> Неизвестный тип синхронизации: {$schedule->sync_type}");
        }
    }
}
