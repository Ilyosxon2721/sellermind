<?php

namespace App\Console\Commands;

use App\Models\VariantMarketplaceLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixUzumVariantLinks extends Command
{
    protected $signature = 'uzum:fix-variant-links
                            {--account= : ID аккаунта Uzum}
                            {--dry-run : Показать что будет изменено без применения}';

    protected $description = 'Исправить привязки Uzum: добавить external_sku_id по баркоду';

    public function handle(): int
    {
        $accountId = $this->option('account');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - изменения не будут применены');
        }

        $this->info('Поиск Uzum привязок без external_sku_id...');

        $query = VariantMarketplaceLink::query()
            ->where('marketplace_code', 'uzum')
            ->where('is_active', true)
            ->whereNull('external_sku_id');

        if ($accountId) {
            $query->where('marketplace_account_id', $accountId);
        }

        $links = $query->with(['variant', 'marketplaceProduct'])->get();

        $this->info("Найдено привязок для исправления: {$links->count()}");

        $fixed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($links as $link) {
            try {
                $product = $link->marketplaceProduct;
                if (! $product) {
                    $this->warn("  Пропущен link #{$link->id}: нет marketplaceProduct");
                    $skipped++;

                    continue;
                }

                $skuList = $product->raw_payload['skuList'] ?? [];
                if (empty($skuList)) {
                    $this->warn("  Пропущен link #{$link->id}: пустой skuList");
                    $skipped++;

                    continue;
                }

                $variant = $link->variant;
                if (! $variant) {
                    $this->warn("  Пропущен link #{$link->id}: нет варианта");
                    $skipped++;

                    continue;
                }

                // Ищем SKU по баркоду варианта или marketplace_barcode
                $matchedSku = null;
                $barcodeToMatch = $link->marketplace_barcode ?? $variant->barcode;

                foreach ($skuList as $sku) {
                    $skuBarcode = isset($sku['barcode']) ? (string) $sku['barcode'] : null;

                    // Совпадение по баркоду
                    if ($skuBarcode && $barcodeToMatch && $skuBarcode === $barcodeToMatch) {
                        $matchedSku = $sku;
                        break;
                    }
                }

                // Если не нашли по баркоду и есть только один SKU - берём его
                if (! $matchedSku && count($skuList) === 1) {
                    $matchedSku = $skuList[0];
                }

                if (! $matchedSku) {
                    $this->warn("  Пропущен link #{$link->id}: не найден matching SKU для barcode {$barcodeToMatch}");
                    $this->line('    Доступные баркоды: '.implode(', ', array_column($skuList, 'barcode')));
                    $skipped++;

                    continue;
                }

                $skuId = isset($matchedSku['skuId']) ? (string) $matchedSku['skuId'] : null;
                $skuBarcode = isset($matchedSku['barcode']) ? (string) $matchedSku['barcode'] : null;

                if (! $skuId) {
                    $this->warn("  Пропущен link #{$link->id}: SKU не имеет skuId");
                    $skipped++;

                    continue;
                }

                $this->line("  Link #{$link->id}: {$variant->sku} -> skuId={$skuId}, barcode={$skuBarcode}");

                if (! $dryRun) {
                    $link->update([
                        'external_sku_id' => $skuId,
                        'marketplace_barcode' => $skuBarcode ?? $link->marketplace_barcode,
                    ]);
                }

                $fixed++;

            } catch (\Throwable $e) {
                $this->error("  Ошибка link #{$link->id}: {$e->getMessage()}");
                $errors++;
                Log::error('FixUzumVariantLinks: Failed to fix link', [
                    'link_id' => $link->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('Результаты:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Исправлено', $fixed],
                ['Пропущено', $skipped],
                ['Ошибок', $errors],
            ]
        );

        if ($dryRun && $fixed > 0) {
            $this->newLine();
            $this->warn('Запустите без --dry-run для применения изменений');
        }

        return self::SUCCESS;
    }
}
