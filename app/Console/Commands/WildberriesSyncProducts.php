<?php

// file: app/Console/Commands/WildberriesSyncProducts.php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesProductService;
use Illuminate\Console\Command;

class WildberriesSyncProducts extends Command
{
    protected $signature = 'wb:sync-products
                            {account_id : ID аккаунта Wildberries или all для всех активных}
                            {--search= : Поиск по тексту (артикул, название)}
                            {--with-photo : Только товары с фото}
                            {--without-photo : Только товары без фото}';

    protected $description = 'Синхронизация карточек товаров Wildberries (Content API)';

    public function handle(): int
    {
        $accountArg = $this->argument('account_id');

        $accounts = collect();
        if (strtolower((string) $accountArg) === 'all') {
            $accounts = MarketplaceAccount::where('marketplace', 'wb')
                ->where('is_active', true)
                ->get();

            if ($accounts->isEmpty()) {
                $this->warn('Нет активных аккаунтов Wildberries для синхронизации');

                return self::SUCCESS;
            }
        } else {
            $accountId = (int) $accountArg;
            $account = MarketplaceAccount::find($accountId);

            if (! $account) {
                $this->error("Аккаунт с ID {$accountId} не найден");

                return self::FAILURE;
            }

            if (! $account->isWildberries()) {
                $this->error("Аккаунт #{$accountId} не является Wildberries");

                return self::FAILURE;
            }

            $accounts = collect([$account]);
        }

        foreach ($accounts as $account) {
            $this->info("Синхронизация товаров для аккаунта: {$account->name}");

            // Create sync log
            $syncLog = MarketplaceSyncLog::create([
                'marketplace_account_id' => $account->id,
                'type' => 'products',
                'status' => 'running',
                'started_at' => now(),
            ]);

            try {
                $httpClient = new WildberriesHttpClient($account);
                $service = new WildberriesProductService($httpClient);

                // Build filters
                $filters = [];

                if ($search = $this->option('search')) {
                    $filters['textSearch'] = $search;
                }

                if ($this->option('with-photo')) {
                    $filters['withPhoto'] = 1;
                } elseif ($this->option('without-photo')) {
                    $filters['withPhoto'] = 0;
                }

                $this->info('Загрузка карточек из WB Content API...');

                $result = $service->syncProducts($account, $filters);

                // Update sync log
                $syncLog->update([
                    'status' => empty($result['errors']) ? 'success' : 'warning',
                    'finished_at' => now(),
                    'message' => sprintf(
                        'Синхронизировано: %d (создано: %d, обновлено: %d)',
                        $result['synced'],
                        $result['created'],
                        $result['updated']
                    ),
                    'details' => $result,
                ]);

                // Output results
                $this->newLine();
                $this->info('✓ Синхронизация завершена!');
                $this->table(
                    ['Метрика', 'Значение'],
                    [
                        ['Всего синхронизировано', $result['synced']],
                        ['Создано новых', $result['created']],
                        ['Обновлено', $result['updated']],
                        ['Ошибок', count($result['errors'])],
                    ]
                );

                if (! empty($result['errors'])) {
                    $this->newLine();
                    $this->warn('Ошибки при синхронизации:');
                    foreach (array_slice($result['errors'], 0, 10) as $error) {
                        $this->line("  - nmID {$error['nm_id']}: {$error['error']}");
                    }

                    if (count($result['errors']) > 10) {
                        $this->line('  ... и ещё '.(count($result['errors']) - 10).' ошибок');
                    }
                }

                // Продолжаем к следующему аккаунту
            } catch (\Exception $e) {
                $syncLog->update([
                    'status' => 'error',
                    'finished_at' => now(),
                    'message' => $e->getMessage(),
                ]);

                $this->error("Ошибка синхронизации: {$e->getMessage()} (аккаунт {$account->name})");

                // Переходим к следующему аккаунту, не падаем целиком
                continue;
            }
        }

        return self::SUCCESS;
    }
}
