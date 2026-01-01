<?php
// file: database/migrations/2025_11_28_200005_create_marketplace_stocks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_product_id')
                ->constrained('marketplace_products')
                ->cascadeOnDelete();

            $table->string('warehouse_code', 100); // например: "wb_fbo_msk", "uzum_kokand"
            $table->string('warehouse_name', 255)->nullable();

            $table->integer('stock')->default(0);
            $table->integer('reserved_stock')->default(0);

            $table->timestamps();

            $table->unique(['marketplace_product_id', 'warehouse_code'], 'mp_stock_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_stocks');
    }
};
