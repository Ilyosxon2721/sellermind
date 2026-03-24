<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\VariantMarketplaceLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Диагностика и исправление связей VariantMarketplaceLink для Uzum.
 *
 * Проблема: при привязке без external_sku_id сохранялся неправильный marketplace_barcode
 * (всегда skuList[0], а не нужного варианта).
 *
 * Эта команда:
 * 1. Находит все активные Uzum-связи без external_sku_id или с подозрительным marketplace_barcode
 * 2. Подбирает правильный skuId и barcode из raw_payload['skuList'] по артикулу варианта
 * 3. Обновляет поля external_sku_id и marketplace_barcode
 */
class UzumFixVariantLinks extends Command
{
    protected $signature = 'uzum:fix-variant-links
                            {--account= : ID аккаунта Uzum (если не указан — все аккаунты)}
                            {--barcode= : Конкретный баркод для диагностики}
                            {--dry-run : Только показать что будет изменено, без записи}';

    protected $description = 'Исправить marketplace_barcode и external_sku_id в VariantMarketplaceLink для Uzum';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $specificBarcode = $this->option('barcode');
        $accountId = $this->option('account');

        $accounts = $accountId
            ? MarketplaceAccount::where('id', $accountId)->where('marketplace', 'uzum')->get()
            : MarketplaceAccount::where('marketplace', 'uzum')->where('is_active', true)->get();

        if ($accounts->isEmpty()) {
            $this->warn('Нет активных Uzum аккаунтов');
            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info("=== Аккаунт #{$account->id} ({$account->name}) ===");
            $this->fixLinksForAccount($account, $dryRun, $specificBarcode);
        }

        return self::SUCCESS;
    }

    private function fixLinksForAccount(MarketplaceAccount $account, bool $dryRun, ?string $specificBarcode): void
    {
        // Получаем все активные связи для этого аккаунта
        $query = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $account->id)
            ->where('is_active', true)
            ->with(['variant', 'marketplaceProduct']);

        $links = $query->get();
        $this->line("  Всего связей: {$links->count()}");

        $fixed = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($links as $link) {
            $variant = $link->variant;
            $mpProduct = $link->marketplaceProduct;

            if (! $variant || ! $mpProduct) {
                $skipped++;
                continue;
            }

            $skuList = $mpProduct->raw_payload['skuList'] ?? [];
            if (empty($skuList)) {
                $this->line("  ⚠ Нет skuList для продукта #{$mpProduct->id} ({$mpProduct->title})");
                $skipped++;
                continue;
            }

            // Если ищем конкретный баркод — показываем диагностику
            if ($specificBarcode) {
                $this->diagnoseBarcode($specificBarcode, $link, $skuList);
                continue;
            }

            // Ищем нужный SKU в skuList по артикулу или баркоду варианта
            $matchedSku = $this->findMatchingSku($skuList, $variant);

            if (! $matchedSku) {
                // Если один SKU в списке — берём его
                if (count($skuList) === 1) {
                    $matchedSku = $skuList[0];
                } else {
                    $skipped++;
                    continue;
                }
            }

            $correctSkuId = isset($matchedSku['skuId']) ? (string) $matchedSku['skuId'] : null;
            $correctBarcode = isset($matchedSku['barcode']) ? (string) $matchedSku['barcode'] : null;

            $needsUpdate = ($link->external_sku_id !== $correctSkuId && $correctSkuId)
                || ($link->marketplace_barcode !== $correctBarcode && $correctBarcode);

            if (! $needsUpdate) {
                $skipped++;
                continue;
            }

            $this->line("  Вариант SKU: {$variant->sku}");
            $this->line("    Было:  external_sku_id={$link->external_sku_id}, marketplace_barcode={$link->marketplace_barcode}");
            $this->line("    Будет: external_sku_id={$correctSkuId}, marketplace_barcode={$correctBarcode}");

            if (! $dryRun) {
                try {
                    $updateData = [];
                    if ($correctSkuId) $updateData['external_sku_id'] = $correctSkuId;
                    if ($correctBarcode) $updateData['marketplace_barcode'] = $correctBarcode;
                    $link->update($updateData);
                    $fixed++;
                } catch (\Throwable $e) {
                    $this->error("    Ошибка: {$e->getMessage()}");
                    $errors++;
                }
            } else {
                $fixed++;
            }
        }

        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        $this->info("  {$prefix}Исправлено: {$fixed}, Пропущено: {$skipped}, Ошибок: {$errors}");
    }

    private function findMatchingSku(array $skuList, $variant): ?array
    {
        // 1. По внутреннему баркоду варианта
        if ($variant->barcode) {
            foreach ($skuList as $sku) {
                if (isset($sku['barcode']) && (string) $sku['barcode'] === (string) $variant->barcode) {
                    return $sku;
                }
            }
        }

        // 2. По артикулу варианта (offerId или vendorCode)
        if ($variant->sku) {
            foreach ($skuList as $sku) {
                $offerMatch = isset($sku['offerId']) && $sku['offerId'] === $variant->sku;
                $vendorMatch = isset($sku['vendorCode']) && $sku['vendorCode'] === $variant->sku;
                if ($offerMatch || $vendorMatch) {
                    return $sku;
                }
            }
        }

        return null;
    }

    private function diagnoseBarcode(string $barcode, VariantMarketplaceLink $link, array $skuList): void
    {
        $this->info("  === Диагностика баркода {$barcode} ===");
        $this->line("  Вариант: {$link->variant?->sku} (ID: {$link->variant?->id})");
        $this->line("  Продукт: {$link->marketplaceProduct?->title}");
        $this->line("  marketplace_barcode в БД: {$link->marketplace_barcode}");
        $this->line("  external_sku_id в БД: {$link->external_sku_id}");
        $this->line("  skuList (" . count($skuList) . " SKU):");

        foreach ($skuList as $i => $sku) {
            $match = isset($sku['barcode']) && (string) $sku['barcode'] === $barcode ? ' ← СОВПАДЕНИЕ' : '';
            $this->line("    [{$i}] skuId={$sku['skuId']}, barcode={$sku['barcode']}{$match}");
        }

        $barcodeFound = collect($skuList)->first(fn($s) => isset($s['barcode']) && (string)$s['barcode'] === $barcode);
        if ($barcodeFound) {
            $this->info("  ✓ Баркод найден в skuList! skuId={$barcodeFound['skuId']}");
            if ((string)$link->external_sku_id !== (string)$barcodeFound['skuId']) {
                $this->error("  ✗ external_sku_id НЕ совпадает — вот почему заказы не списываются!");
                $this->line("  Запустите без --barcode и --dry-run чтобы исправить.");
            }
        } else {
            $this->error("  ✗ Баркод {$barcode} НЕ найден в skuList!");
        }
    }
}
