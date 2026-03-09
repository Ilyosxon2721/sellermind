<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\ProductVariant;
use App\Notifications\LowStockNotification;
use Illuminate\Console\Command;

class CheckLowStockCommand extends Command
{
    protected $signature = 'stock:check-low
        {--company= : ID компании (по умолчанию — все)}
        {--dry-run : Только показать, не отправлять}';

    protected $description = 'Проверить остатки и отправить уведомления о низком уровне';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $isDryRun = $this->option('dry-run');

        $query = Company::query()
            ->with(['users.notificationSettings'])
            ->whereHas('users', function ($q) {
                $q->whereHas('notificationSettings', function ($q2) {
                    $q2->where('notify_low_stock', true);
                });
            });

        if ($companyId) {
            $query->where('id', $companyId);
        }

        $total = 0;

        foreach ($query->cursor() as $company) {
            foreach ($company->users as $user) {
                $settings = $user->notificationSettings;
                if (! $settings || ! $settings->notify_low_stock) {
                    continue;
                }

                $threshold = $settings->low_stock_threshold ?? 10;

                $variants = ProductVariant::query()
                    ->whereHas('product', fn ($q) => $q->where('company_id', $company->id))
                    ->where('stock_default', '>', 0)
                    ->where('stock_default', '<=', $threshold)
                    ->where('is_deleted', false)
                    ->with('product:id,name')
                    ->get();

                foreach ($variants as $variant) {
                    if ($isDryRun) {
                        $this->line('[DRY-RUN] '.$variant->product->name.' | SKU: '.$variant->sku.' | Остаток: '.$variant->stock_default.' <= '.$threshold);
                    } else {
                        $user->notify(new LowStockNotification($variant, $variant->stock_default));
                    }
                    $total++;
                }
            }
        }

        $this->info(
            $isDryRun
                ? '[DRY-RUN] Найдено '.$total.' вариантов с низким остатком'
                : 'Отправлено '.$total.' уведомлений о низком остатке'
        );

        return self::SUCCESS;
    }
}
