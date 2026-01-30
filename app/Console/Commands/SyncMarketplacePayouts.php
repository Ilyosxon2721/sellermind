<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Finance\MarketplacePayoutSyncService;
use Illuminate\Console\Command;

class SyncMarketplacePayouts extends Command
{
    protected $signature = 'finance:sync-payouts
                            {--company= : ID компании (если не указан - все активные)}
                            {--from= : Дата начала периода (Y-m-d)}
                            {--to= : Дата конца периода (Y-m-d)}
                            {--marketplace= : Конкретный маркетплейс (uzum, wb, ozon)}';

    protected $description = 'Синхронизировать выплаты маркетплейсов в кассы';

    public function handle(MarketplacePayoutSyncService $service): int
    {
        $companyId = $this->option('company');
        $from = $this->option('from');
        $to = $this->option('to');
        $marketplace = $this->option('marketplace');

        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::where('is_active', true)->get();
        }

        if ($companies->isEmpty()) {
            $this->error('Компании не найдены');
            return 1;
        }

        $this->info("Синхронизация выплат маркетплейсов...");
        $this->info("Период: " . ($from ?? 'начало') . " - " . ($to ?? 'сегодня'));
        $this->newLine();

        $totalResults = [
            'payouts_created' => 0,
            'payouts_updated' => 0,
            'payouts_skipped' => 0,
            'transactions_created' => 0,
            'total_amount' => 0,
            'errors' => 0,
        ];

        foreach ($companies as $company) {
            $this->info("Компания: {$company->name} (ID: {$company->id})");

            try {
                if ($marketplace === 'uzum' || !$marketplace) {
                    $result = $service->syncUzum($company->id, $from, $to);
                    $this->displayResult('Uzum', $result);
                    $this->mergeResults($totalResults, $result);
                }

                // Можно добавить другие маркетплейсы
                // if ($marketplace === 'wb' || !$marketplace) {
                //     $result = $service->syncWildberries($company->id, $from, $to);
                //     $this->displayResult('Wildberries', $result);
                // }

            } catch (\Exception $e) {
                $this->error("Ошибка: {$e->getMessage()}");
                $totalResults['errors']++;
            }

            $this->newLine();
        }

        $this->info("=== ИТОГО ===");
        $this->table(
            ['Показатель', 'Значение'],
            [
                ['Выплат создано', $totalResults['payouts_created']],
                ['Выплат обновлено', $totalResults['payouts_updated']],
                ['Выплат пропущено', $totalResults['payouts_skipped']],
                ['Транзакций создано', $totalResults['transactions_created']],
                ['Общая сумма', number_format($totalResults['total_amount'], 0, '', ' ') . ' UZS'],
                ['Ошибок', $totalResults['errors']],
            ]
        );

        return 0;
    }

    protected function displayResult(string $marketplace, array $result): void
    {
        $this->line("  {$marketplace}:");
        $this->line("    Выплат: создано {$result['payouts_created']}, обновлено {$result['payouts_updated']}, пропущено {$result['payouts_skipped']}");
        $this->line("    Транзакций создано: {$result['transactions_created']}");
        $this->line("    Сумма: " . number_format($result['total_amount'], 0, '', ' ') . " UZS");

        if ($result['errors'] > 0) {
            $this->warn("    Ошибок: {$result['errors']}");
        }
    }

    protected function mergeResults(array &$target, array $source): void
    {
        $target['payouts_created'] += $source['payouts_created'];
        $target['payouts_updated'] += $source['payouts_updated'];
        $target['payouts_skipped'] += $source['payouts_skipped'];
        $target['transactions_created'] += $source['transactions_created'];
        $target['total_amount'] += $source['total_amount'];
        $target['errors'] += $source['errors'];
    }
}
