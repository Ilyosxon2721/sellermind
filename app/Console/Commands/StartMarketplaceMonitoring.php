<?php

namespace App\Console\Commands;

use App\Jobs\Marketplace\MonitorMarketplaceChangesJob;
use App\Models\MarketplaceAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StartMarketplaceMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:start-monitoring
                            {--account= : ID конкретного аккаунта для мониторинга}
                            {--marketplace= : Тип маркетплейса (wb, uzum, ozon, ym)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запустить мониторинг маркетплейсов (заказы, товары, цены)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Запуск мониторинга маркетплейсов...');
        $this->newLine();

        // Получаем аккаунты для мониторинга
        $query = MarketplaceAccount::where('is_active', true);

        if ($accountId = $this->option('account')) {
            $query->where('id', $accountId);
        }

        if ($marketplace = $this->option('marketplace')) {
            $query->where('marketplace', $marketplace);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->error('❌ Не найдено активных аккаунтов для мониторинга');

            return self::FAILURE;
        }

        $this->info("Найдено аккаунтов: {$accounts->count()}");
        $this->newLine();

        // Создаём таблицу с информацией об аккаунтах
        $tableData = [];
        foreach ($accounts as $account) {
            $tableData[] = [
                'ID' => $account->id,
                'Маркетплейс' => strtoupper($account->marketplace),
                'Название' => $account->name ?: $account->getDisplayName(),
                'Статус' => $account->is_active ? '✅ Активен' : '❌ Неактивен',
            ];
        }

        $this->table(
            ['ID', 'Маркетплейс', 'Название', 'Статус'],
            $tableData
        );

        $this->newLine();

        // Запускаем мониторинг для каждого аккаунта
        $successCount = 0;
        $failCount = 0;

        foreach ($accounts as $account) {
            try {
                MonitorMarketplaceChangesJob::dispatch($account);

                $this->line("✅ Мониторинг запущен для: {$account->marketplace} - {$account->getDisplayName()}");
                $successCount++;
            } catch (\Throwable $e) {
                $this->error("❌ Ошибка при запуске мониторинга для аккаунта {$account->id}: {$e->getMessage()}");
                Log::error('Ошибка запуска мониторинга маркетплейса', [
                    'account_id' => $account->id,
                    'marketplace' => $account->marketplace,
                    'error' => $e->getMessage(),
                ]);
                $failCount++;
            }
        }

        $this->newLine();
        $this->info('📊 Итого:');
        $this->info("   Успешно запущено: {$successCount}");

        if ($failCount > 0) {
            $this->warn("   С ошибками: {$failCount}");
        }

        $this->newLine();
        $this->info('📋 Запущенные джобы:');
        $this->line('   🔄 MonitorOrdersJob - проверка заказов каждую минуту');
        $this->line('   📦 MonitorProductsJob - синхронизация товаров каждый час');
        $this->line('   💰 MonitorPricesJob - обновление цен каждые 2 часа');

        $this->newLine();
        $this->comment('💡 Мониторинг работает в фоновом режиме через систему очередей');
        $this->comment('   Для просмотра логов: tail -f storage/logs/laravel.log | grep -i monitoring');
        $this->comment('   Для проверки очереди: php artisan queue:monitor database');

        return self::SUCCESS;
    }
}
