<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Replenishment settings
        if (! Schema::hasTable('replenishment_settings')) {
            Schema::create('replenishment_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('sku_id');
                $table->boolean('is_enabled')->default(true);
                $table->string('policy', 16)->default('ROP');
                $table->decimal('reorder_point', 18, 3)->nullable();
                $table->decimal('min_qty', 18, 3)->nullable();
                $table->decimal('max_qty', 18, 3)->nullable();
                $table->decimal('safety_stock', 18, 3)->default(0);
                $table->integer('lead_time_days')->default(7);
                $table->integer('review_period_days')->default(7);
                $table->integer('demand_window_days')->default(30);
                $table->decimal('rounding_step', 18, 3)->default(1);
                $table->decimal('min_order_qty', 18, 3)->default(0);
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'warehouse_id', 'sku_id'], 'replenishment_setting_unique');
                $table->index('supplier_id');
            });
        }

        // Replenishment snapshots (history/cache)
        if (! Schema::hasTable('replenishment_snapshots')) {
            Schema::create('replenishment_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('sku_id');
                $table->dateTime('calculated_at');
                $table->decimal('on_hand', 18, 3)->default(0);
                $table->decimal('reserved', 18, 3)->default(0);
                $table->decimal('available', 18, 3)->default(0);
                $table->decimal('avg_daily_demand', 18, 6)->default(0);
                $table->integer('lead_time_days')->default(0);
                $table->decimal('safety_stock', 18, 3)->default(0);
                $table->decimal('reorder_qty', 18, 3)->default(0);
                $table->string('risk_level', 16)->nullable();
                $table->date('next_stockout_date')->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'calculated_at'], 'replenishment_snap_company_calc');
                $table->index('warehouse_id');
                $table->index('sku_id');
            });
        }

        // Demand events (optional)
        if (! Schema::hasTable('demand_events')) {
            Schema::create('demand_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('sku_id');
                $table->dateTime('occurred_at');
                $table->decimal('qty', 18, 3);
                $table->string('source_type', 32);
                $table->unsignedBigInteger('source_id')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'warehouse_id', 'sku_id', 'occurred_at'], 'demand_events_idx');
            });
        }

        // Minimal Purchase Core tables (only if отсутствуют)
        if (! Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('po_no')->unique();
                $table->string('status')->default('DRAFT');
                $table->date('expected_date')->nullable();
                $table->decimal('total_amount', 18, 2)->default(0);
                $table->timestamps();
                $table->index(['company_id', 'warehouse_id']);
            });
        }

        if (! Schema::hasTable('purchase_order_lines')) {
            Schema::create('purchase_order_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_order_id');
                $table->unsignedBigInteger('sku_id');
                $table->decimal('qty', 18, 3);
                $table->decimal('unit_cost', 18, 2)->default(0);
                $table->decimal('total_cost', 18, 2)->default(0);
                $table->timestamps();
                $table->index('purchase_order_id');
                $table->index('sku_id');
            });
        }

        if (! Schema::hasTable('goods_receipt_lines')) {
            Schema::create('goods_receipt_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('purchase_order_id')->nullable();
                $table->unsignedBigInteger('sku_id');
                $table->decimal('qty', 18, 3);
                $table->decimal('unit_cost', 18, 2)->default(0);
                $table->decimal('total_cost', 18, 2)->default(0);
                $table->timestamps();
                $table->index('purchase_order_id');
                $table->index('sku_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('demand_events');
        Schema::dropIfExists('replenishment_snapshots');
        Schema::dropIfExists('replenishment_settings');
    }
};
