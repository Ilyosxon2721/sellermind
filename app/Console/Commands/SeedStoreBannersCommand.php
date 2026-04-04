<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Store\Store;
use App\Models\Store\StoreBanner;
use Illuminate\Console\Command;

/**
 * Добавить шаблонные баннеры для магазинов без баннеров
 */
final class SeedStoreBannersCommand extends Command
{
    protected $signature = 'store:seed-banners {--store= : ID конкретного магазина} {--force : Добавить даже если баннеры уже есть}';

    protected $description = 'Добавить шаблонные промо-баннеры для магазинов без баннеров';

    public function handle(): int
    {
        $query = Store::query();

        if ($storeId = $this->option('store')) {
            $query->where('id', (int) $storeId);
        }

        $stores = $query->get();
        $count = 0;

        foreach ($stores as $store) {
            if (! $this->option('force') && $store->banners()->count() > 0) {
                $this->line("  Пропуск: {$store->name} (ID {$store->id}) — баннеры уже есть");
                continue;
            }

            $this->createDefaultBanners($store);
            $count++;
            $this->info("  Добавлены баннеры: {$store->name} (ID {$store->id})");
        }

        $this->info("Готово. Баннеры добавлены для {$count} магазинов.");

        return self::SUCCESS;
    }

    private function createDefaultBanners(Store $store): void
    {
        $banners = [
            [
                'title' => 'Добро пожаловать в ' . $store->name,
                'subtitle' => 'Лучшие товары по выгодным ценам с доставкой',
                'button_text' => 'В каталог',
                'url' => '/store/' . $store->slug . '/catalog',
                'text_color' => '#ffffff',
                'display_mode' => 'overlay',
                'image' => '/banners/default-banner-1.svg',
                'position' => 0,
                'is_active' => true,
            ],
            [
                'title' => 'Скидки до 50%',
                'subtitle' => 'Успейте купить по лучшей цене',
                'button_text' => 'Смотреть акции',
                'url' => '/store/' . $store->slug . '/catalog?sort=popular',
                'text_color' => '#ffffff',
                'display_mode' => 'overlay',
                'image' => '/banners/default-banner-2.svg',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Бесплатная доставка',
                'subtitle' => 'При заказе от 200 000 сум',
                'button_text' => 'Подробнее',
                'url' => '/store/' . $store->slug . '/catalog',
                'text_color' => '#ffffff',
                'display_mode' => 'overlay',
                'image' => '/banners/default-banner-3.svg',
                'position' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $banner) {
            $banner['store_id'] = $store->id;
            StoreBanner::create($banner);
        }
    }
}
