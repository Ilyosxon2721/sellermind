<?php

namespace App\Console\Commands;

use App\Services\Finance\MarketplaceExpensesSyncService;
use App\Models\Company;
use Illuminate\Console\Command;

class SyncMarketplaceExpensesToFinance extends Command
{
    protected $signature = 'finance:sync-marketplace-expenses
                            {--company= : Specific company ID}
                            {--days=30 : Sync expenses for last N days}
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--marketplace= : Specific marketplace (uzum, wb, ozon) or empty for all}';

    protected $description = 'Sync marketplace expenses (commission, logistics, storage, ads) to FinanceTransaction';

    public function handle(MarketplaceExpensesSyncService $service): int
    {
        $companyId = $this->option('company');
        $days = (int) $this->option('days');
        $marketplace = $this->option('marketplace');

        // Определяем период
        if ($this->option('from') && $this->option('to')) {
            $from = \Carbon\Carbon::parse($this->option('from'))->startOfDay();
            $to = \Carbon\Carbon::parse($this->option('to'))->endOfDay();
        } else {
            $from = now()->subDays($days)->startOfDay();
            $to = now()->endOfDay();
        }

        $marketplaceText = $marketplace ? strtoupper($marketplace) : 'ALL MARKETPLACES';
        $this->info("Syncing {$marketplaceText} expenses from {$from->format('Y-m-d')} to {$to->format('Y-m-d')}");

        // Получаем компании
        $query = Company::query();
        if ($companyId) {
            $query->where('id', $companyId);
        }
        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->warn('No companies found');
            return Command::SUCCESS;
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;
        $totalAmount = 0;
        $byMarketplace = [];

        foreach ($companies as $company) {
            $this->line('');
            $this->info("Processing company: {$company->name} (ID: {$company->id})");

            try {
                // Если указан конкретный маркетплейс - синхронизируем только его
                if ($marketplace) {
                    $result = match (strtolower($marketplace)) {
                        'uzum' => $service->syncUzumExpenses($company->id, $from, $to),
                        'wb', 'wildberries' => $service->syncWildberriesExpenses($company->id, $from, $to),
                        'ozon' => $service->syncOzonExpenses($company->id, $from, $to),
                        default => throw new \Exception("Unknown marketplace: {$marketplace}"),
                    };

                    $this->outputSingleResult($result);

                    $totalCreated += $result['created'];
                    $totalUpdated += $result['updated'];
                    $totalSkipped += $result['skipped'];
                    $totalErrors += $result['errors'];
                    $totalAmount += $result['total_amount'];
                } else {
                    // Синхронизируем все маркетплейсы
                    $results = $service->syncAllMarketplaces($company->id, $from, $to);

                    // Выводим результаты по каждому маркетплейсу
                    foreach (['uzum', 'wb', 'ozon'] as $mp) {
                        if (isset($results[$mp])) {
                            $mpResult = $results[$mp];
                            $this->line("  [{$mp}] Created: {$mpResult['created']}, Updated: {$mpResult['updated']}, Skipped: {$mpResult['skipped']}, Errors: {$mpResult['errors']}");
                            if ($mpResult['total_amount'] > 0) {
                                $this->line("        Amount: " . number_format($mpResult['total_amount'], 0, ',', ' ') . " UZS");
                            }
                            $byMarketplace[$mp] = ($byMarketplace[$mp] ?? 0) + $mpResult['total_amount'];
                        }
                    }

                    $total = $results['total'];
                    $totalCreated += $total['created'];
                    $totalUpdated += $total['updated'];
                    $totalSkipped += $total['skipped'];
                    $totalErrors += $total['errors'];
                    $totalAmount += $total['total_amount'];
                }
            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                $totalErrors++;
            }
        }

        $this->line('');
        $this->info("=== TOTAL ===");
        $this->info("Created: {$totalCreated}, Updated: {$totalUpdated}, Skipped: {$totalSkipped}, Errors: {$totalErrors}");
        $this->info("Total amount: " . number_format($totalAmount, 0, ',', ' ') . " UZS");

        if (!empty($byMarketplace)) {
            $this->line('By marketplace:');
            foreach ($byMarketplace as $mp => $amount) {
                $this->line("  {$mp}: " . number_format($amount, 0, ',', ' ') . " UZS");
            }
        }

        return Command::SUCCESS;
    }

    protected function outputSingleResult(array $result): void
    {
        $this->info("  Created: {$result['created']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}, Errors: {$result['errors']}");
        $this->info("  Total amount: " . number_format($result['total_amount'], 0, ',', ' ') . " UZS");

        if (!empty($result['by_category'])) {
            $this->line('  By category:');
            foreach ($result['by_category'] as $cat => $amount) {
                $this->line("    {$cat}: " . number_format($amount, 0, ',', ' ') . " UZS");
            }
        }
    }
}
