<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляем флаг is_bundle_variant в product_variants.
 *
 * Комплект (Product с is_bundle=true) автоматически получает один
 * "виртуальный" ProductVariant с этим флагом. Такой вариант:
 *  - хранит sku/barcode/price/описание для листинга на маркетплейсе;
 *  - stock_default обновляется автоматически при изменении компонентов
 *    (см. ProductVariantObserver);
 *  - при списании каскадно уменьшает остатки компонентов.
 *
 * Благодаря этому bundle бесшовно переиспользует всю существующую
 * инфраструктуру variant_marketplace_links / StockUpdated.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_variants', 'is_bundle_variant')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->boolean('is_bundle_variant')
                    ->default(false)
                    ->after('is_deleted')
                    ->comment('true — виртуальный вариант комплекта (is_bundle=true у продукта)');

                $table->index('is_bundle_variant');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variants', 'is_bundle_variant')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropIndex(['is_bundle_variant']);
                $table->dropColumn('is_bundle_variant');
            });
        }
    }
};
