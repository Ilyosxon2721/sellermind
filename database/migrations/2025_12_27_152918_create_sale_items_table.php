<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('set null');

            // Информация о товаре (сохраняем на момент продажи)
            $table->string('sku')->nullable(); // SKU товара
            $table->string('product_name'); // Название товара
            $table->string('variant_name')->nullable(); // Название варианта (размер, цвет и т.д.)

            // Количество и цены
            $table->decimal('quantity', 10, 3)->default(1); // Количество
            $table->decimal('unit_price', 15, 2); // Цена за единицу
            $table->decimal('discount_percent', 5, 2)->default(0); // Процент скидки
            $table->decimal('discount_amount', 15, 2)->default(0); // Сумма скидки
            $table->decimal('tax_percent', 5, 2)->default(0); // Процент налога
            $table->decimal('tax_amount', 15, 2)->default(0); // Сумма налога
            $table->decimal('subtotal', 15, 2); // Подитог (quantity * unit_price)
            $table->decimal('total', 15, 2); // Итого с учетом скидки и налога

            // Себестоимость (для расчета маржи)
            $table->decimal('cost_price', 15, 2)->nullable(); // Закупочная цена

            // Флаг списания остатков
            $table->boolean('stock_deducted')->default(false); // Списаны ли остатки
            $table->timestamp('stock_deducted_at')->nullable(); // Когда списаны

            // Дополнительная информация
            $table->json('metadata')->nullable(); // Доп. данные (баркод, артикул и т.д.)

            $table->timestamps();

            // Индексы
            $table->index(['sale_id']);
            $table->index(['product_variant_id']);
            $table->index(['sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
