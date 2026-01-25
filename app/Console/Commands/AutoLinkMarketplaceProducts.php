<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\AutoLinkService;
use Illuminate\Console\Command;

class AutoLinkMarketplaceProducts extends Command
{
    protected $signature = 'marketplace:auto-link
        {--account= : ID конкретного аккаунта}
        {--company= : ID компании (привязать для всех аккаунтов компании)}
        {--all : Привязать для всех активных аккаунтов}';

    protected $description = 'Автоматически привязать товары маркетплейсов к внутренним вариантам по баркоду, SKU или артикулу';

    public function __construct(
        protected AutoLinkService $autoLinkService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════');
        $this->info('🔗 Автоматическая привязка товаров маркетплейсов');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();

        $accountId = $this->option('account');
        $companyId = $this->option('company');
        $all = $this->option('all');

        if (!$accountId && !$companyId && !$all) {
            $this->error('Укажите --account=ID, --company=ID или --all');
            return self::FAILURE;
        }

        $startTime = microtime(true);
        $totalStats = [
            'total_products' => 0,
            'already_linked' => 0,
            'linked_by_barcode' => 0,
            'linked_by_sku' => 0,
            'linked_by_article' => 0,
            'not_linked' => 0,
            'errors' => 0,
        ];

        if ($accountId) {
            $account = MarketplaceAccount::find($accountId);
            if (!$account) {
                $this->error("Аккаунт #{$accountId} не найден");
                return self::FAILURE;
            }

            $this->processAccount($account, $totalStats);
        } elseif ($companyId) {
            $accounts = MarketplaceAccount::where('company_id', $companyId)
                ->where('is_active', true)
                ->get();

            if ($accounts->isEmpty()) {
                $this->warn("Нет активных аккаунтов для компании #{$companyId}");
                return self::SUCCESS;
            }

            $this->info("Найдено {$accounts->count()} аккаунтов для компании #{$companyId}");
            $this->newLine();

            foreach ($accounts as $account) {
                $this->processAccount($account, $totalStats);
            }
        } else {
            // --all
            $accounts = MarketplaceAccount::where('is_active', true)->get();

            if ($accounts->isEmpty()) {
                $this->warn('Нет активных аккаунтов');
                return self::SUCCESS;
            }

            $this->info("Найдено {$accounts->count()} активных аккаунтов");
            $this->newLine();

            foreach ($accounts as $account) {
                $this->processAccount($account, $totalStats);
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        // Итоговая статистика
        $this->newLine();
        $this->info('═══════════════════════════════════════════════');
        $this->info('📊 Итоговая статистика:');
        $this->info('═══════════════════════════════════════════════');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Всего товаров', $totalStats['total_products']],
                ['Уже привязаны', $totalStats['already_linked']],
                ['Привязано по баркоду', $totalStats['linked_by_barcode']],
                ['Привязано по SKU', $totalStats['linked_by_sku']],
                ['Привязано по артикулу', $totalStats['linked_by_article']],
                ['Не удалось привязать', $totalStats['not_linked']],
                ['Ошибки', $totalStats['errors']],
            ]
        );

        $newlyLinked = $totalStats['linked_by_barcode'] + $totalStats['linked_by_sku'] + $totalStats['linked_by_article'];
        $this->newLine();
        $this->info("✅ Новых привязок создано: {$newlyLinked}");
        $this->info("⏱️  Время выполнения: {$duration} сек.");

        return self::SUCCESS;
    }

    protected function processAccount(MarketplaceAccount $account, array &$totalStats): void
    {
        $this->line("📦 Аккаунт #{$account->id}: {$account->name} ({$account->marketplace})");

        $stats = $this->autoLinkService->autoLinkForAccount($account);

        // Суммируем статистику
        foreach ($stats as $key => $value) {
            $totalStats[$key] += $value;
        }

        $newlyLinked = $stats['linked_by_barcode'] + $stats['linked_by_sku'] + $stats['linked_by_article'];

        if ($newlyLinked > 0) {
            $this->info("   ✓ Привязано: {$newlyLinked} (баркод: {$stats['linked_by_barcode']}, SKU: {$stats['linked_by_sku']}, артикул: {$stats['linked_by_article']})");
        } else {
            $this->comment("   → Новых привязок нет (уже привязано: {$stats['already_linked']}, не найдено: {$stats['not_linked']})");
        }

        $this->newLine();
    }
}
