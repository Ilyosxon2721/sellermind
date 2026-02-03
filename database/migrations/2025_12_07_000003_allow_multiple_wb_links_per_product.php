<?php

// file: database/migrations/2025_12_07_000003_allow_multiple_wb_links_per_product.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            // убираем уникальность только по аккаунту+product_id
            $table->dropUnique('mp_acc_prod_unique');
        });

        Schema::table('marketplace_products', function (Blueprint $table) {
            // новая уникальность с учетом external_product_id, чтобы один локальный мог иметь несколько карточек WB
            $table->unique(['marketplace_account_id', 'product_id', 'external_product_id'], 'mp_acc_prod_ext_unique');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->dropUnique('mp_acc_prod_ext_unique');
        });

        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->unique(['marketplace_account_id', 'product_id'], 'mp_acc_prod_unique');
        });
    }
};
