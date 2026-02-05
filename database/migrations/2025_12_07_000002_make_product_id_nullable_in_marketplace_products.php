<?php

// file: database/migrations/2025_12_07_000002_make_product_id_nullable_in_marketplace_products.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite не поддерживает MODIFY — пропускаем при тестах.
            return;
        }

        Schema::table('marketplace_products', function (Blueprint $table) {
            // Удаляем связанные ограничения перед изменением столбца
            $table->dropForeign(['product_id']);
            $table->dropUnique('mp_acc_prod_unique');
        });

        // Делаем product_id nullable, чтобы можно было отвязывать товар
        DB::statement('ALTER TABLE marketplace_products MODIFY product_id BIGINT UNSIGNED NULL');

        Schema::table('marketplace_products', function (Blueprint $table) {
            // При удалении товара сбрасываем связь, но запись МП оставляем
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();

            // Оставляем уникальность связки аккаунт + локальный товар (null допускается)
            $table->unique(['marketplace_account_id', 'product_id'], 'mp_acc_prod_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropUnique('mp_acc_prod_unique');
        });

        // Возвращаем not null и каскадное удаление
        DB::statement('ALTER TABLE marketplace_products MODIFY product_id BIGINT UNSIGNED NOT NULL');

        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();

            $table->unique(['marketplace_account_id', 'product_id'], 'mp_acc_prod_unique');
        });
    }
};
