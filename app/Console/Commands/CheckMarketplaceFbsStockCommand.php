<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Notifications\MarketplaceLowFbsNotification;
use Illuminate\Console\Command;

/**
 * Проверяет FBS-остатки маркетплейсов и уведомляет когда прогноз < threshold дней.
 */
class CheckMarketplaceFbsStockCommand extends Command
{
    protected $signature = 'marketplace:check-fbs-stock
        {--company= : ID компании (по умолчанию — все)}
        {--threshold=7 : Минимальный прогноз в днях (по умолчанию 7)}
        {--dry-run : Только показать, не отправлять уведомления}';

    protected $description = 'Проверить FBS-остатки маркетплейсов и отправить уведомления о критическом уровне';

    public function handle(): int
    {
        $threshold  = (int) $this->option('threshold');
        $companyId  = $this->option('company');
        $isDryRun   = $this->option('dry-run');

        $accountsQuery = MarketplaceAccount::query()
            ->where('is_active', true)
            ->with(['company.users.notificationSettings']);

        if ($companyId) {
            $accountsQuery->whereHas('company', fn ($q) => $q->where('id', $companyId));
        }

        $total = 0;

        foreach ($accountsQuery->cursor() as $account) {
            $products = MarketplaceProduct::where('marketplace_account_id', $account->id)
                ->where('status', 'active')
                ->where('stock_fbs', '>', 0)
                ->where('quantity_sold', '>', 0)
                ->select(['id', 'marketplace_account_id', 'title', 'external_product_id', 'stock_fbs', 'quantity_sold'])
                ->get();

            foreach ($products as $product) {
                $dailyRate = (float) $product->quantity_sold / 30;
                $daysLeft  = (int) round($product->stock_fbs / $dailyRate);

                if ($daysLeft >= $threshold) {
                    continue;
                }

                // Уведомляем всех пользователей компании с включёнными уведомлениями
                foreach ($account->company->users as $user) {
                    $settings = $user->notificationSettings;
                    if ($settings && ! $settings->notify_low_stock) {
                        continue;
                    }

                    if ($isDryRun) {
                        $this->line(sprintf(
                            '[DRY-RUN] %s | %s | FBS: %d шт. | Прогноз: %d дн.',
                            strtoupper($account->marketplace),
                            ($product->title ?? $product->external_product_id ?? '?'),
                            $product->stock_fbs,
                            $daysLeft
                        ));
                    } else {
                        $user->notify(new MarketplaceLowFbsNotification($product, $daysLeft, $product->stock_fbs));
                    }
                }

                $total++;
            }
        }

        $this->info(
            $isDryRun
                ? "[DRY-RUN] Найдено {$total} товаров с критическим FBS-остатком (< {$threshold} дн.)"
                : "Отправлено уведомлений для {$total} товаров с критическим FBS-остатком"
        );

        return self::SUCCESS;
    }
}
