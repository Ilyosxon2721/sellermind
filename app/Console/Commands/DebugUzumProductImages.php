<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use Illuminate\Console\Command;

/**
 * Диагностика: показать поля изображений в raw_payload Uzum товаров
 */
class DebugUzumProductImages extends Command
{
    protected $signature = 'uzum:debug-product-images
        {accountId : ID аккаунта}
        {--fix : Исправить preview_image из raw_payload}
        {--limit=5 : Сколько товаров показать}';

    protected $description = 'Диагностика полей изображений в Uzum товарах';

    public function handle(): int
    {
        $account = MarketplaceAccount::find($this->argument('accountId'));
        if (! $account) {
            $this->error('Аккаунт не найден');
            return self::FAILURE;
        }

        $total = MarketplaceProduct::where('marketplace_account_id', $account->id)->count();
        $withImage = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereNotNull('preview_image')
            ->where('preview_image', '!=', '')
            ->count();

        $this->info("=== Товары аккаунта #{$account->id} ===");
        $this->line("Всего: {$total}, с preview_image: {$withImage}, без: " . ($total - $withImage));

        // Показать несколько примеров
        $limit = (int) $this->option('limit');
        $products = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereNotNull('raw_payload')
            ->limit($limit)
            ->get();

        foreach ($products as $product) {
            $this->newLine();
            $this->info("--- Товар #{$product->id} (ext: {$product->external_product_id}) ---");
            $this->line("preview_image в БД: " . ($product->preview_image ?: '<null>'));

            $raw = $product->raw_payload;
            if (! is_array($raw)) {
                $this->warn('raw_payload не является массивом');
                continue;
            }

            $this->line("Ключи raw_payload: " . implode(', ', array_keys($raw)));

            // Проверить все возможные image-поля
            $imageFields = ['image', 'previewImg', 'photo', 'thumbnail', 'mainImage',
                            'coverImage', 'photoUrl', 'imageUrl', 'previewImage',
                            'photos', 'photoGallery', 'images', 'galleryImages', 'media'];

            $this->line("Image-поля:");
            foreach ($imageFields as $field) {
                if (isset($raw[$field])) {
                    $val = $raw[$field];
                    if (is_array($val)) {
                        $this->line("  {$field}: [array, " . count($val) . " элем, первый: " . json_encode($val[0] ?? null) . "]");
                    } else {
                        $this->line("  {$field}: <info>{$val}</info>");
                    }
                }
            }

            // Проверить skuList[0] image-поля
            if (! empty($raw['skuList'][0])) {
                $sku0 = $raw['skuList'][0];
                $this->line("skuList[0] ключи: " . implode(', ', array_keys($sku0)));
                $skuImageFields = ['image', 'photo', 'skuImage', 'imageUrl', 'photoUrl', 'thumbnail', 'previewImg'];
                foreach ($skuImageFields as $field) {
                    if (isset($sku0[$field])) {
                        $this->line("  skuList[0].{$field}: <info>{$sku0[$field]}</info>");
                    }
                }
            }
        }

        // --fix: обновить preview_image из raw_payload
        if ($this->option('fix')) {
            $this->newLine();
            $this->info('=== Исправление preview_image из raw_payload ===');
            $this->fixPreviewImages($account->id);
        }

        return self::SUCCESS;
    }

    private function fixPreviewImages(int $accountId): void
    {
        $products = MarketplaceProduct::where('marketplace_account_id', $accountId)
            ->whereNotNull('raw_payload')
            ->get();

        $fixed = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $raw = $product->raw_payload;
            if (! is_array($raw)) {
                $skipped++;
                continue;
            }

            // Попробовать все возможные поля
            $img = $raw['image'] ?? $raw['previewImg'] ?? $raw['photo'] ?? $raw['thumbnail']
                ?? $raw['mainImage'] ?? $raw['coverImage'] ?? $raw['photoUrl'] ?? $raw['imageUrl']
                ?? null;

            // Из skuList[0]
            if (! $img && ! empty($raw['skuList'][0])) {
                $sku0 = $raw['skuList'][0];
                $img = $sku0['image'] ?? $sku0['photo'] ?? $sku0['skuImage'] ?? $sku0['imageUrl'] ?? null;
            }

            // Из photos/photoGallery/images (массивы)
            if (! $img) {
                foreach (['photos', 'photoGallery', 'images', 'galleryImages'] as $field) {
                    if (! empty($raw[$field]) && is_array($raw[$field])) {
                        $first = $raw[$field][0];
                        $img = is_string($first) ? $first : ($first['url'] ?? $first['photo'] ?? $first['src'] ?? null);
                        if ($img) break;
                    }
                }
            }

            if (! $img) {
                $skipped++;
                continue;
            }

            // Если это ID (без http и слэша) — конструируем URL
            if (! str_contains((string)$img, '/') && ! str_starts_with((string)$img, 'http')) {
                $img = "https://images.uzum.uz/{$img}/t_product_540_high.jpg";
            }

            if ($product->preview_image !== $img) {
                $product->update(['preview_image' => $img]);
                $fixed++;
            } else {
                $skipped++;
            }
        }

        $this->info("Исправлено: {$fixed}, пропущено: {$skipped}");
    }
}
