<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('counterparty_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');

            // Sale info
            $table->string('sale_number')->nullable(); // Номер продажи
            $table->enum('sale_type', ['retail', 'wholesale', 'direct'])->default('retail'); // Тип: розница, опт, прямая
            $table->enum('status', ['draft', 'confirmed', 'shipped', 'delivered', 'cancelled', 'returned'])->default('draft');

            // Customer info (for retail without counterparty)
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();

            // Financial
            $table->decimal('subtotal', 18, 2)->default(0); // Сумма без скидки
            $table->decimal('discount_amount', 18, 2)->default(0); // Скидка
            $table->decimal('total_amount', 18, 2)->default(0); // Итого
            $table->string('currency_code', 3)->default('UZS');

            // Payment
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->decimal('paid_amount', 18, 2)->default(0);
            $table->string('payment_method')->nullable(); // cash, card, transfer, etc.

            // Dates
            $table->date('sale_date');
            $table->date('shipped_date')->nullable();
            $table->date('delivered_date')->nullable();

            // Stock tracking
            $table->string('stock_status')->nullable(); // pending, reserved, sold, released
            $table->timestamp('stock_reserved_at')->nullable();
            $table->timestamp('stock_sold_at')->nullable();
            $table->timestamp('stock_released_at')->nullable();

            // Meta
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'sale_date']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'sale_type']);
            $table->index(['company_id', 'counterparty_id']);
        });

        Schema::create('offline_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offline_sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('sku_id')->nullable()->constrained('skus')->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');

            // Item info
            $table->string('sku_code')->nullable();
            $table->string('product_name')->nullable();
            $table->text('description')->nullable();

            // Quantity and pricing
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0); // Цена за единицу
            $table->decimal('unit_cost', 18, 2)->default(0); // Себестоимость за единицу
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0); // quantity * unit_price - discount

            $table->timestamps();

            $table->index(['offline_sale_id', 'sku_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sale_items');
        Schema::dropIfExists('offline_sales');
    }
};
