<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceHttpClient;
use App\Services\Marketplaces\OzonClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OzonSyncCatalog extends Command
{
    protected $signature = 'ozon:sync-catalog
                            {--account= : ID аккаунта OZON}';

    protected $description = 'Синхронизация каталога товаров OZON (импорт новых товаров)';

    public function handle(): int
    {
        $accountId = $this->option('account');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->get()
            : MarketplaceAccount::where('marketplace', 'ozon')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных OZON аккаунтов');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("Аккаунт #{$account->id} ({$account->name})");

            try {
                $this->syncCatalog($account);
            } catch (\Exception $e) {
                $this->error("Ошибка синхронизации: {$e->getMessage()}");
                Log::error('OZON catalog sync failed', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }

    protected function syncCatalog(MarketplaceAccount $account): void
    {
        $httpClient = app(MarketplaceHttpClient::class);
        $client = new OzonClient($httpClient);

        $this->line('  Начинаем синхронизацию каталога...');
        $startTime = microtime(true);

        $result = $client->syncCatalog($account);

        $duration = round(microtime(true) - $startTime, 2);

        $synced = $result['synced'] ?? 0;
        $created = $result['created'] ?? 0;
        $updated = $result['updated'] ?? 0;

        $this->info("  ✓ Синхронизация завершена за {$duration} сек");
        $this->line("    Всего обработано: {$synced}");
        $this->line("    Новых товаров: {$created}");
        $this->line("    Обновлено: {$updated}");

        Log::info('OZON catalog synced', [
            'account_id' => $account->id,
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'duration' => $duration,
        ]);
    }
}
