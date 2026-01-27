<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wb_orders', function (Blueprint $table) {
            // Add missing columns for WB Marketplace API data
            if (!Schema::hasColumn('wb_orders', 'rid')) {
                $table->string('rid')->nullable()->after('external_order_id');
            }
            if (!Schema::hasColumn('wb_orders', 'order_uid')) {
                $table->string('order_uid')->nullable()->after('rid');
            }
            if (!Schema::hasColumn('wb_orders', 'nm_id')) {
                $table->bigInteger('nm_id')->nullable()->after('order_uid');
            }
            if (!Schema::hasColumn('wb_orders', 'chrt_id')) {
                $table->bigInteger('chrt_id')->nullable()->after('nm_id');
            }
            if (!Schema::hasColumn('wb_orders', 'article')) {
                $table->string('article')->nullable()->after('chrt_id');
            }
            if (!Schema::hasColumn('wb_orders', 'sku')) {
                $table->string('sku')->nullable()->after('article');
            }
            if (!Schema::hasColumn('wb_orders', 'product_name')) {
                $table->string('product_name')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('wb_orders', 'photo_url')) {
                $table->string('photo_url', 500)->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('wb_orders', 'supply_id')) {
                $table->string('supply_id')->nullable()->after('warehouse_id');
            }
            if (!Schema::hasColumn('wb_orders', 'tare_id')) {
                $table->unsignedBigInteger('tare_id')->nullable()->after('supply_id');
            }
            if (!Schema::hasColumn('wb_orders', 'office')) {
                $table->string('office')->nullable()->after('tare_id');
            }
            if (!Schema::hasColumn('wb_orders', 'cargo_type')) {
                $table->integer('cargo_type')->nullable()->after('wb_delivery_type');
            }
            if (!Schema::hasColumn('wb_orders', 'price')) {
                $table->bigInteger('price')->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('wb_orders', 'scan_price')) {
                $table->bigInteger('scan_price')->nullable()->after('price');
            }
            if (!Schema::hasColumn('wb_orders', 'converted_price')) {
                $table->bigInteger('converted_price')->nullable()->after('scan_price');
            }
            if (!Schema::hasColumn('wb_orders', 'currency_code')) {
                $table->integer('currency_code')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('wb_orders', 'converted_currency_code')) {
                $table->integer('converted_currency_code')->nullable()->after('currency_code');
            }
            if (!Schema::hasColumn('wb_orders', 'is_b2b')) {
                $table->boolean('is_b2b')->default(false)->after('converted_currency_code');
            }
            if (!Schema::hasColumn('wb_orders', 'is_zero_order')) {
                $table->boolean('is_zero_order')->default(false)->after('is_b2b');
            }

            // Add index for nm_id for faster lookups
            if (!Schema::hasIndex('wb_orders', 'wb_orders_nm_id_index')) {
                $table->index('nm_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wb_orders', function (Blueprint $table) {
            $columns = [
                'rid', 'order_uid', 'nm_id', 'chrt_id', 'article', 'sku',
                'product_name', 'photo_url', 'supply_id', 'tare_id', 'office',
                'cargo_type', 'price', 'scan_price', 'converted_price',
                'currency_code', 'converted_currency_code', 'is_b2b', 'is_zero_order'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('wb_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
