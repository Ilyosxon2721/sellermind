<?php

// file: database/migrations/2025_11_28_300003_create_wildberries_warehouses_and_stocks_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create wildberries_warehouses and wildberries_stocks tables.
     */
    public function up(): void
    {
        // Warehouses table
        Schema::create('wildberries_warehouses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            // WB warehouse data
            $table->unsignedBigInteger('warehouse_id')->nullable();    // WB warehouse ID
            $table->string('warehouse_name', 255);                     // Название склада
            $table->string('warehouse_type', 50)->nullable();          // Тип склада (FBO/FBS)
            $table->string('office_id', 50)->nullable();               // ID офиса

            // Address
            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes (shortened names)
            $table->unique(['marketplace_account_id', 'warehouse_id'], 'wb_wh_acc_wh_unique');
            $table->index(['marketplace_account_id', 'warehouse_name'], 'wb_wh_acc_name_idx');
        });

        // Stocks table
        Schema::create('wildberries_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            $table->foreignId('wildberries_product_id')
                ->constrained('wildberries_products')
                ->cascadeOnDelete();

            $table->foreignId('wildberries_warehouse_id')
                ->constrained('wildberries_warehouses')
                ->cascadeOnDelete();

            // Stock quantities
            $table->integer('quantity')->default(0);                   // Доступное количество
            $table->integer('quantity_full')->default(0);              // Полное количество (с резервом)

            // In transit
            $table->integer('in_way_to_client')->default(0);           // В пути к клиенту
            $table->integer('in_way_from_client')->default(0);         // В пути от клиента (возврат)

            // Reserved
            $table->integer('reserved')->default(0);                   // Зарезервировано

            // WB tracking
            $table->string('sku', 50)->nullable();                     // SKU для этого склада
            $table->timestamp('last_change_date')->nullable();

            $table->timestamps();

            // Indexes (shortened names)
            $table->unique(['wildberries_product_id', 'wildberries_warehouse_id'], 'wb_stock_unique');
            $table->index(['marketplace_account_id', 'wildberries_product_id'], 'wb_stock_acc_prod_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wildberries_stocks');
        Schema::dropIfExists('wildberries_warehouses');
    }
};
