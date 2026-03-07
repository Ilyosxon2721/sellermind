<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class MarketplaceCommissionSeeder extends Seeder
{
    /**
     * Заполнение справочников комиссий маркетплейсов.
     *
     * Таблицы: marketplace_categories, marketplace_commissions,
     * marketplace_logistics, marketplace_acquiring.
     */
    public function run(): void
    {
        // Очищаем таблицы (отключаем FK для truncate)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('marketplace_commissions')->truncate();
        DB::table('marketplace_logistics')->truncate();
        DB::table('marketplace_acquiring')->truncate();
        DB::table('marketplace_categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->seedWildberries();
        $this->seedOzon();
        $this->seedYandex();
        $this->seedUzum();
        $this->seedLogistics();
        $this->seedAcquiring();
    }

    /**
     * Wildberries: 12 категорий, единый тариф FBO
     */
    private function seedWildberries(): void
    {
        $categories = [
            'Одежда' => 34.5,
            'Обувь' => 34.5,
            'Электроника' => 27.5,
            'Бытовая техника' => 22.5,
            'Товары для дома' => 24.5,
            'Косметика' => 29.5,
            'Продукты' => 19.5,
            'Детские товары' => 24.5,
            'Спорт' => 26.5,
            'Автотовары' => 26.5,
            'Книги' => 19.5,
            'Зоотовары' => 21.5,
        ];

        $now = now();
        $sortOrder = 1;

        foreach ($categories as $name => $commission) {
            $categoryId = DB::table('marketplace_categories')->insertGetId([
                'marketplace' => 'wildberries',
                'category_id' => 'wb_'.$sortOrder,
                'name' => $name,
                'parent_id' => null,
                'path' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('marketplace_commissions')->insert([
                'marketplace' => 'wildberries',
                'category_id' => $categoryId,
                'fulfillment_type' => 'fbo',
                'commission_percent' => $commission,
                'commission_min' => null,
                'commission_max' => null,
                'price_ranges' => null,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sortOrder++;
        }
    }

    /**
     * Ozon: 9 категорий, FBO + FBS, специальные тарифы для дешёвых товаров
     */
    private function seedOzon(): void
    {
        // Категория => [FBO %, FBS %]
        $categories = [
            'Одежда/Fashion' => [25.0, 29.0],
            'Электроника' => [12.5, 16.5],
            'Бытовая техника' => [15.0, 19.0],
            'Товары для дома' => [18.0, 22.0],
            'Косметика' => [21.0, 25.0],
            'Продукты' => [12.0, 16.0],
            'Детские товары' => [16.0, 20.0],
            'Зоотовары' => [15.0, 19.0],
            'Книги' => [14.0, 18.0],
        ];

        $priceRanges = json_encode([
            ['from' => 0, 'to' => 100, 'percent' => 14],
            ['from' => 101, 'to' => 300, 'percent' => 20],
        ], JSON_UNESCAPED_UNICODE);

        $now = now();
        $sortOrder = 1;

        foreach ($categories as $name => [$fbo, $fbs]) {
            $categoryId = DB::table('marketplace_categories')->insertGetId([
                'marketplace' => 'ozon',
                'category_id' => 'ozon_'.$sortOrder,
                'name' => $name,
                'parent_id' => null,
                'path' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // FBO
            DB::table('marketplace_commissions')->insert([
                'marketplace' => 'ozon',
                'category_id' => $categoryId,
                'fulfillment_type' => 'fbo',
                'commission_percent' => $fbo,
                'commission_min' => null,
                'commission_max' => null,
                'price_ranges' => $priceRanges,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // FBS
            DB::table('marketplace_commissions')->insert([
                'marketplace' => 'ozon',
                'category_id' => $categoryId,
                'fulfillment_type' => 'fbs',
                'commission_percent' => $fbs,
                'commission_min' => null,
                'commission_max' => null,
                'price_ranges' => $priceRanges,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sortOrder++;
        }
    }

    /**
     * Yandex Market: 9 категорий, FBY + FBS + DBS
     */
    private function seedYandex(): void
    {
        // Категория => [FBY %, FBS/DBS %]
        $categories = [
            'Одежда и обувь' => [6.0, 10.0],
            'Электроника' => [5.0, 9.0],
            'Бытовая техника' => [7.5, 11.5],
            'Товары для дома' => [6.5, 10.5],
            'Косметика' => [7.5, 11.5],
            'Продукты' => [4.0, 8.0],
            'Детские товары' => [4.0, 8.0],
            'Книги' => [14.0, 18.0],
            'Спорт и отдых' => [7.0, 11.0],
        ];

        $now = now();
        $sortOrder = 1;

        foreach ($categories as $name => [$fby, $fbsDbs]) {
            $categoryId = DB::table('marketplace_categories')->insertGetId([
                'marketplace' => 'yandex',
                'category_id' => 'yandex_'.$sortOrder,
                'name' => $name,
                'parent_id' => null,
                'path' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // FBY
            DB::table('marketplace_commissions')->insert([
                'marketplace' => 'yandex',
                'category_id' => $categoryId,
                'fulfillment_type' => 'fbo',
                'commission_percent' => $fby,
                'commission_min' => null,
                'commission_max' => null,
                'price_ranges' => null,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // FBS
            DB::table('marketplace_commissions')->insert([
                'marketplace' => 'yandex',
                'category_id' => $categoryId,
                'fulfillment_type' => 'fbs',
                'commission_percent' => $fbsDbs,
                'commission_min' => null,
                'commission_max' => null,
                'price_ranges' => null,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // DBS
            DB::table('marketplace_commissions')->insert([
                'marketplace' => 'yandex',
                'category_id' => $categoryId,
                'fulfillment_type' => 'dbs',
                'commission_percent' => $fbsDbs,
                'commission_min' => null,
                'commission_max' => null,
                'price_ranges' => null,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sortOrder++;
        }
    }

    /**
     * Uzum Market: 12 категорий, единый тип FBO
     */
    private function seedUzum(): void
    {
        $categories = [
            'Электроника' => 5.5,
            'Бытовая техника крупная' => 8.0,
            'Одежда' => 17.5,
            'Обувь' => 16.5,
            'Косметика' => 15.0,
            'Товары для дома' => 12.5,
            'Детские товары' => 15.0,
            'Продукты' => 10.0,
            'Книги' => 10.0,
            'Спорт' => 13.5,
            'Зоотовары' => 12.5,
            'Ювелирные изделия' => 30.0,
        ];

        $now = now();
        $sortOrder = 1;

        foreach ($categories as $name => $commission) {
            $categoryId = DB::table('marketplace_categories')->insertGetId([
                'marketplace' => 'uzum',
                'category_id' => 'uzum_'.$sortOrder,
                'name' => $name,
                'parent_id' => null,
                'path' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('marketplace_commissions')->insert([
                'marketplace' => 'uzum',
                'category_id' => $categoryId,
                'fulfillment_type' => 'fbo',
                'commission_percent' => $commission,
                'commission_min' => null,
                'commission_max' => null,
                'price_ranges' => null,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sortOrder++;
        }
    }

    /**
     * Базовые тарифы логистики (фиксированная ставка за доставку)
     */
    private function seedLogistics(): void
    {
        $now = now();

        // Маркетплейс => [FBO rate, FBS rate, валюта]
        $rates = [
            'wildberries' => [50, 80, 'RUB'],
            'ozon' => [80, 120, 'RUB'],
            'yandex' => [60, 90, 'RUB'],
            'uzum' => [5000, 8000, 'UZS'],
        ];

        $rows = [];

        foreach ($rates as $marketplace => [$fboRate, $fbsRate, $currency]) {
            // FBO — доставка
            $rows[] = [
                'marketplace' => $marketplace,
                'fulfillment_type' => 'fbo',
                'logistics_type' => 'delivery',
                'region' => null,
                'volume_from' => null,
                'volume_to' => null,
                'weight_from' => null,
                'weight_to' => null,
                'rate' => $fboRate,
                'rate_type' => 'fixed',
                'currency' => $currency,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // FBS — доставка
            $rows[] = [
                'marketplace' => $marketplace,
                'fulfillment_type' => 'fbs',
                'logistics_type' => 'delivery',
                'region' => null,
                'volume_from' => null,
                'volume_to' => null,
                'weight_from' => null,
                'weight_to' => null,
                'rate' => $fbsRate,
                'rate_type' => 'fixed',
                'currency' => $currency,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('marketplace_logistics')->insert($rows);
    }

    /**
     * Ставки эквайринга по маркетплейсам
     */
    private function seedAcquiring(): void
    {
        $now = now();

        $rates = [
            'wildberries' => 2.0,
            'ozon' => 1.0,
            'yandex' => 1.8,
            'uzum' => 0.0,
        ];

        $rows = [];

        foreach ($rates as $marketplace => $rate) {
            $rows[] = [
                'marketplace' => $marketplace,
                'payout_frequency' => null,
                'rate_percent' => $rate,
                'effective_from' => '2025-01-01',
                'effective_to' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('marketplace_acquiring')->insert($rows);
    }
}
