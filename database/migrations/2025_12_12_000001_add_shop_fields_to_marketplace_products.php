<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        Schema::table('marketplace_products', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_products', 'shop_id')) {
                $table->string('shop_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('marketplace_products', 'title')) {
                $table->string('title')->nullable()->after('shop_id');
            }
            if (!Schema::hasColumn('marketplace_products', 'category')) {
                $table->string('category')->nullable()->after('title');
            }
            if (!Schema::hasColumn('marketplace_products', 'preview_image')) {
                $table->string('preview_image')->nullable()->after('category');
            }
            if (!Schema::hasColumn('marketplace_products', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('preview_image');
            }

            // Добавляем уникальный индекс по аккаунту/магазину/внешнему id товара
            $indexes = $this->listIndexes('marketplace_products');
            if (!in_array('mp_acc_shop_ext_prod_unique', $indexes, true)) {
                $table->unique(
                    ['marketplace_account_id', 'shop_id', 'external_product_id'],
                    'mp_acc_shop_ext_prod_unique'
                );
            }
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        Schema::table('marketplace_products', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_products', 'raw_payload')) {
                $table->dropColumn('raw_payload');
            }
            if (Schema::hasColumn('marketplace_products', 'preview_image')) {
                $table->dropColumn('preview_image');
            }
            if (Schema::hasColumn('marketplace_products', 'category')) {
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('marketplace_products', 'title')) {
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('marketplace_products', 'shop_id')) {
                $table->dropColumn('shop_id');
            }
        });
    }

    /**
     * Get existing indexes for table (MySQL only, safe fallback otherwise)
     */
    protected function listIndexes(string $table): array
    {
        try {
            $connection = Schema::getConnection()->getDoctrineSchemaManager();
            return array_keys($connection->listTableIndexes($table));
        } catch (\Throwable $e) {
            return [];
        }
    }
};
