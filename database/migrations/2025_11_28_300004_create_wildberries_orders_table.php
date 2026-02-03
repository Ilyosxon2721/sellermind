<?php

// file: database/migrations/2025_11_28_300004_create_wildberries_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create wildberries_orders table for storing WB orders data.
     */
    public function up(): void
    {
        Schema::create('wildberries_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            // WB identifiers - srid is the unique key from Statistics API
            $table->string('srid', 100)->nullable();                   // Уникальный ID записи (для статистики)
            $table->unsignedBigInteger('order_id')->nullable();        // ID заказа
            $table->unsignedBigInteger('odid')->nullable();            // ID записи о заказе (для Marketplace API)
            $table->string('rid', 100)->nullable();                    // Рид

            // Product info
            $table->unsignedBigInteger('nm_id')->nullable();
            $table->string('supplier_article', 255)->nullable();
            $table->string('barcode', 50)->nullable();
            $table->string('tech_size', 50)->nullable();
            $table->string('brand', 255)->nullable();
            $table->string('subject', 255)->nullable();
            $table->string('category', 255)->nullable();

            // Warehouse
            $table->string('warehouse_name', 255)->nullable();
            $table->string('warehouse_type', 50)->nullable();          // FBO / FBS

            // Status
            $table->string('status', 50)->nullable();                  // Internal normalized status
            $table->string('wb_status', 50)->nullable();               // Original WB status

            // Order flags
            $table->boolean('is_cancel')->default(false);
            $table->boolean('is_return')->default(false);
            $table->boolean('is_realization')->default(false);         // Реализация

            // Pricing (in RUB, stored as-is from API - may be in kopecks or rubles depending on endpoint)
            $table->decimal('price', 12, 2)->nullable();               // Цена без скидки
            $table->unsignedInteger('discount_percent')->nullable();   // Скидка %
            $table->decimal('total_price', 12, 2)->nullable();         // Цена со скидкой
            $table->decimal('finished_price', 12, 2)->nullable();      // Финальная цена
            $table->decimal('for_pay', 12, 2)->nullable();             // К перечислению продавцу
            $table->decimal('spp', 5, 2)->nullable();                  // СПП

            // Commission
            $table->decimal('commission_percent', 5, 2)->nullable();   // Комиссия %

            // Region info
            $table->string('region_name', 255)->nullable();
            $table->string('oblast_okrug_name', 255)->nullable();
            $table->string('country_name', 100)->nullable();

            // Dates
            $table->timestamp('order_date')->nullable();               // Дата заказа
            $table->timestamp('cancel_date')->nullable();              // Дата отмены
            $table->timestamp('last_change_date')->nullable();         // Последнее изменение

            // FBS specific
            $table->string('sticker', 255)->nullable();                // Стикер для сборки
            $table->string('delivery_type', 50)->nullable();           // Тип доставки

            // Income ID (для отчёта о продажах)
            $table->unsignedBigInteger('income_id')->nullable();

            // Raw data
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Indexes (shortened names)
            $table->unique(['marketplace_account_id', 'srid'], 'wb_ord_acc_srid_unique');
            $table->index(['marketplace_account_id', 'order_date'], 'wb_ord_acc_date_idx');
            $table->index(['marketplace_account_id', 'order_id'], 'wb_ord_acc_oid_idx');
            $table->index(['marketplace_account_id', 'nm_id'], 'wb_ord_acc_nm_idx');
            $table->index(['marketplace_account_id', 'status'], 'wb_ord_acc_status_idx');
            $table->index(['marketplace_account_id', 'is_cancel'], 'wb_ord_acc_cancel_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wildberries_orders');
    }
};
