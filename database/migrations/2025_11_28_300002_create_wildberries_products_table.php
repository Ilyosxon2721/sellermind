<?php

// file: database/migrations/2025_11_28_300002_create_wildberries_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create wildberries_products table for storing WB product cards (карточки товаров).
     */
    public function up(): void
    {
        Schema::create('wildberries_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            // Link to local product (if exists)
            $table->unsignedBigInteger('local_product_id')->nullable();

            // WB identifiers
            $table->unsignedBigInteger('nm_id')->nullable();           // Номенклатура ID (главный идентификатор)
            $table->unsignedBigInteger('imt_id')->nullable();          // IMT ID (ID карточки)
            $table->unsignedBigInteger('chrt_id')->nullable();         // Характеристика ID (для размеров)

            // Product data
            $table->string('vendor_code', 255)->nullable();            // vendorCode (артикул поставщика)
            $table->string('supplier_article', 255)->nullable();       // supplierArticle (артикул)
            $table->string('barcode', 50)->nullable();                 // Штрихкод
            $table->string('title', 500)->nullable();                  // Название товара
            $table->text('description')->nullable();                   // Описание

            // Category info
            $table->string('subject_name', 255)->nullable();           // Предмет (категория)
            $table->unsignedInteger('subject_id')->nullable();         // ID предмета
            $table->string('brand', 255)->nullable();                  // Бренд

            // Size/Color
            $table->string('tech_size', 50)->nullable();               // Размер
            $table->string('color', 100)->nullable();                  // Цвет

            // Pricing
            $table->decimal('price', 12, 2)->nullable();               // Цена без скидки
            $table->unsignedInteger('discount_percent')->nullable();   // Процент скидки
            $table->decimal('price_with_discount', 12, 2)->nullable(); // Цена со скидкой
            $table->decimal('spp', 5, 2)->nullable();                  // СПП (скидка постоянного покупателя)

            // Stock
            $table->integer('stock_total')->default(0);                // Общий остаток

            // Media
            $table->json('photos')->nullable();                        // Фотографии [{url, is_main}]
            $table->json('videos')->nullable();                        // Видео

            // Characteristics
            $table->json('characteristics')->nullable();               // Характеристики [{id, name, value}]

            // Status
            $table->boolean('is_active')->default(true);
            $table->string('moderation_status', 50)->nullable();       // Статус модерации

            // Raw API data
            $table->json('raw_data')->nullable();

            // Sync tracking
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            // Indexes (shortened names to avoid MySQL 64 char limit)
            $table->index(['marketplace_account_id', 'nm_id'], 'wb_prod_acc_nm_idx');
            $table->index(['marketplace_account_id', 'vendor_code'], 'wb_prod_acc_vendor_idx');
            $table->index(['marketplace_account_id', 'supplier_article'], 'wb_prod_acc_supplier_idx');
            $table->index(['marketplace_account_id', 'barcode'], 'wb_prod_acc_barcode_idx');
            $table->index(['marketplace_account_id', 'subject_id'], 'wb_prod_acc_subject_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wildberries_products');
    }
};
