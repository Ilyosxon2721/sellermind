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
            // Note: unique index mp_acc_shop_ext_prod_unique already created in create_marketplace_products_table
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
            $indexes = \DB::select("SHOW INDEX FROM {$table}");
            return array_unique(array_column($indexes, 'Key_name'));
        } catch (\Throwable $e) {
            return [];
        }
    }
};
