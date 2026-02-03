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
        if (Schema::hasTable('wb_orders')) {
            Schema::table('wb_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('wb_orders', 'rid')) {
                    $table->string('rid')->nullable()->after('external_order_id');
                }
                if (! Schema::hasColumn('wb_orders', 'order_uid')) {
                    $table->string('order_uid')->nullable()->after('rid');
                }
                if (! Schema::hasColumn('wb_orders', 'nm_id')) {
                    $table->string('nm_id')->nullable()->after('order_uid')->index();
                }
                if (! Schema::hasColumn('wb_orders', 'chrt_id')) {
                    $table->string('chrt_id')->nullable()->after('nm_id');
                }
                if (! Schema::hasColumn('wb_orders', 'article')) {
                    $table->string('article')->nullable()->after('chrt_id')->index();
                }
                if (! Schema::hasColumn('wb_orders', 'sku')) {
                    $table->string('sku')->nullable()->after('article')->index();
                }
                if (! Schema::hasColumn('wb_orders', 'price')) {
                    $table->integer('price')->nullable()->after('total_amount')->comment('Price in kopecks');
                }
                if (! Schema::hasColumn('wb_orders', 'scan_price')) {
                    $table->integer('scan_price')->nullable()->after('price')->comment('Scan price in kopecks');
                }
                if (! Schema::hasColumn('wb_orders', 'converted_price')) {
                    $table->bigInteger('converted_price')->nullable()->after('scan_price');
                }
                if (! Schema::hasColumn('wb_orders', 'currency_code')) {
                    $table->string('currency_code')->nullable()->after('currency');
                }
                if (! Schema::hasColumn('wb_orders', 'converted_currency_code')) {
                    $table->string('converted_currency_code')->nullable()->after('currency_code');
                }
                if (! Schema::hasColumn('wb_orders', 'cargo_type')) {
                    $table->integer('cargo_type')->nullable()->after('wb_delivery_type');
                }
                if (! Schema::hasColumn('wb_orders', 'office')) {
                    $table->string('office')->nullable()->after('warehouse_id')->comment('Delivery office/location');
                }
                if (! Schema::hasColumn('wb_orders', 'is_b2b')) {
                    $table->boolean('is_b2b')->default(false)->after('cargo_type');
                }
                if (! Schema::hasColumn('wb_orders', 'is_zero_order')) {
                    $table->boolean('is_zero_order')->default(false)->after('is_b2b');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wb_orders')) {
            Schema::table('wb_orders', function (Blueprint $table) {
                $columns = [
                    'rid', 'order_uid', 'nm_id', 'chrt_id', 'article', 'sku',
                    'price', 'scan_price', 'converted_price',
                    'currency_code', 'converted_currency_code',
                    'cargo_type', 'office', 'is_b2b', 'is_zero_order',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('wb_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
