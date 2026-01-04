<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Warehouses
        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->boolean('is_default')->default(false);
                $table->string('address')->nullable();
                $table->timestamps();
                $table->index('company_id');
                $table->unique(['company_id', 'name']);
            });
        }

        // Warehouse locations (optional, but создаём таблицу)
        if (!Schema::hasTable('warehouse_locations')) {
            Schema::create('warehouse_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('code');
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['warehouse_id', 'code']);
            });
        }

        // Units
        if (!Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Products table is created by dedicated migration 2025_11_27_214215_create_products_table.php

        // SKUs
        if (!Schema::hasTable('skus')) {
            Schema::create('skus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->unsignedBigInteger('company_id');
                $table->string('sku_code');
                $table->string('barcode_ean13')->nullable();
                $table->json('attributes_json')->nullable();
                $table->integer('weight_g')->nullable();
                $table->integer('length_mm')->nullable();
                $table->integer('width_mm')->nullable();
                $table->integer('height_mm')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index('company_id');
                $table->unique(['company_id', 'sku_code']);
                $table->index('barcode_ean13');
            });
        }

        // Inventory documents
        if (!Schema::hasTable('inventory_documents')) {
            Schema::create('inventory_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('doc_no');
                $table->string('type');
                $table->string('status');
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->unsignedBigInteger('warehouse_to_id')->nullable();
                $table->string('reason')->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('reversed_document_id')->nullable();
                $table->text('comment')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'doc_no']);
                $table->index(['company_id', 'type', 'status']);
                $table->index('warehouse_id');
                $table->index('reversed_document_id');
            });
        }

        // Inventory document lines
        if (!Schema::hasTable('inventory_document_lines')) {
            Schema::create('inventory_document_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('inventory_documents')->cascadeOnDelete();
                $table->foreignId('sku_id')->constrained('skus');
                $table->decimal('qty', 18, 3);
                $table->foreignId('unit_id')->constrained('units');
                $table->unsignedBigInteger('location_id')->nullable();
                $table->unsignedBigInteger('location_to_id')->nullable();
                $table->decimal('unit_cost', 18, 2)->nullable();
                $table->decimal('total_cost', 18, 2)->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->index('document_id');
                $table->index('sku_id');
            });
        }

        // Stock ledger
        if (!Schema::hasTable('stock_ledger')) {
            Schema::create('stock_ledger', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->dateTime('occurred_at');
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('location_id')->nullable();
                $table->unsignedBigInteger('sku_id');
                $table->decimal('qty_delta', 18, 3);
                $table->decimal('cost_delta', 18, 2)->default(0);
                $table->unsignedBigInteger('document_id')->nullable();
                $table->unsignedBigInteger('document_line_id')->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'warehouse_id', 'sku_id', 'occurred_at'], 'ledger_company_wh_sku');
                $table->index('document_id');
                $table->index('sku_id');
            });
        }

        // Stock reservations
        if (!Schema::hasTable('stock_reservations')) {
            Schema::create('stock_reservations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('sku_id');
                $table->decimal('qty', 18, 3);
                $table->string('status');
                $table->string('reason');
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'warehouse_id', 'sku_id', 'status'], 'reservations_company_wh_sku_status');
                $table->index(['source_type', 'source_id']);
            });
        }

        // Channel SKU maps
        if (Schema::hasTable('channels') && !Schema::hasTable('channel_sku_maps')) {
            Schema::create('channel_sku_maps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
                $table->foreignId('sku_id')->constrained('skus')->cascadeOnDelete();
                $table->string('external_sku_id');
                $table->string('external_offer_id')->nullable();
                $table->string('barcode')->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->unique(['channel_id', 'external_sku_id']);
                $table->index('sku_id');
            });
        }

        // Channel orders
        if (Schema::hasTable('channels') && !Schema::hasTable('channel_orders')) {
            Schema::create('channel_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
                $table->string('external_order_id');
                $table->string('status')->nullable();
                $table->json('payload_json')->nullable();
                $table->timestamp('created_at_channel')->nullable();
                $table->timestamps();
                $table->unique(['channel_id', 'external_order_id']);
            });
        }

        if (Schema::hasTable('channels') && !Schema::hasTable('channel_order_items')) {
            Schema::create('channel_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_order_id')->constrained('channel_orders')->cascadeOnDelete();
                $table->string('external_sku_id');
                $table->unsignedBigInteger('sku_id')->nullable();
                $table->decimal('qty', 18, 3)->default(0);
                $table->decimal('price', 18, 2)->default(0);
                $table->json('payload_json')->nullable();
                $table->timestamps();
                $table->index('channel_order_id');
                $table->index('sku_id');
            });
        }

        // Processed events
        if (Schema::hasTable('channels') && !Schema::hasTable('processed_events')) {
            Schema::create('processed_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
                $table->string('external_event_id');
                $table->string('type');
                $table->string('payload_hash')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                $table->unique(['channel_id', 'external_event_id', 'type'], 'processed_event_unique');
            });
        }

        // Company settings (добавляем недостающие поля)
        if (Schema::hasTable('company_settings')) {
            Schema::table('company_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('company_settings', 'allow_negative_stock')) {
                    $table->boolean('allow_negative_stock')->default(false);
                }
                if (!Schema::hasColumn('company_settings', 'costing_method')) {
                    $table->string('costing_method')->default('AVG');
                }
                if (!Schema::hasColumn('company_settings', 'locations_enabled')) {
                    $table->boolean('locations_enabled')->default(false);
                }
            });
        } else {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->boolean('allow_negative_stock')->default(false);
                $table->string('costing_method')->default('AVG');
                $table->boolean('locations_enabled')->default(false);
                $table->timestamps();
                $table->unique('company_id');
            });
        }
    }

    public function down(): void
    {
        // В обратном порядке
        Schema::dropIfExists('processed_events');
        Schema::dropIfExists('channel_order_items');
        Schema::dropIfExists('channel_orders');
        Schema::dropIfExists('channel_sku_maps');
        Schema::dropIfExists('stock_reservations');
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('inventory_document_lines');
        Schema::dropIfExists('inventory_documents');
        Schema::dropIfExists('skus');
        Schema::dropIfExists('units');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouses');
        // company_settings не трогаем, чтобы не удалять существующие настройки
    }
};
