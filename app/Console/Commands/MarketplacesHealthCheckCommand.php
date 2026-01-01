<?php
// file: app/Console/Commands/MarketplacesHealthCheckCommand.php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceClientFactory;
use Illuminate\Console\Command;

class MarketplacesHealthCheckCommand extends Command
{
    protected $signature = 'marketplaces:health-check
        {--marketplace=all : Маркетплейс (wb|ozon|uzum|ym|all)}
        {--account= : ID конкретного аккаунта для проверки}';

    protected $description = 'Проверка подключения к API маркетплейсов (health-check)';

    public function __construct(
        protected MarketplaceClientFactory $clientFactory
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $marketplace = $this->option('marketplace');
        $accountId = $this->option('account');

        $this->info('=== Marketplace Health Check ===');
        $this->newLine();

        // Build query
        $query = MarketplaceAccount::where('is_active', true);

        if ($accountId) {
            $query->where('id', $accountId);
        } elseif ($marketplace !== 'all') {
            $query->where('marketplace', $marketplace);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('Активных аккаунтов не найдено.');
            return self::SUCCESS;
        }

        $this->info("Найдено аккаунтов: {$accounts->count()}");
        $this->newLine();

        $results = [];

        foreach ($accounts as $account) {
            $result = $this->checkAccount($account);
            $results[] = $result;
        }

        // Summary table
        $this->newLine();
        $this->info('=== Результаты ===');
        $this->table(
            ['ID', 'Маркетплейс', 'Аккаунт', 'Статус', 'Сообщение'],
            array_map(function ($r) {
                return [
                    $r['id'],
                    $r['marketplace'],
                    $r['name'],
                    $r['success'] ? '<fg=green>OK</>' : '<fg=red>ERROR</>',
                    mb_substr($r['message'], 0, 50),
                ];
            }, $results)
        );

        // Count results
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $errorCount = count($results) - $successCount;

        $this->newLine();
        $this->info("Успешно: {$successCount}, Ошибок: {$errorCount}");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function checkAccount(MarketplaceAccount $account): array
    {
        $label = $this->clientFactory->getMarketplaceLabel($account->marketplace);
        $this->line("Проверка [{$label}] {$account->name}...");

        try {
            $client = $this->clientFactory->forAccount($account);
            $startTime = microtime(true);
            $pingResult = $client->ping($account);
            $endTime = microtime(true);

            $responseTimeMs = round(($endTime - $startTime) * 1000);

            if ($pingResult['success']) {
                $this->info("  ✓ OK ({$responseTimeMs}ms)");
            } else {
                $this->error("  ✗ ERROR: {$pingResult['message']}");
            }

            return [
                'id' => $account->id,
                'marketplace' => $label,
                'name' => $account->name,
                'success' => $pingResult['success'],
                'message' => $pingResult['message'],
                'response_time_ms' => $responseTimeMs,
            ];
        } catch (\Throwable $e) {
            $this->error("  ✗ EXCEPTION: {$e->getMessage()}");

            return [
                'id' => $account->id,
                'marketplace' => $label,
                'name' => $account->name,
                'success' => false,
                'message' => $e->getMessage(),
                'response_time_ms' => null,
            ];
        }
    }
}
