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
        Schema::table('marketplace_orders', function (Blueprint $table) {
            // Основные идентификаторы WB
            $table->unsignedBigInteger('wb_order_id')->nullable()->after('external_order_id')->index()->comment('WB id (id)');
            $table->string('wb_order_uid')->nullable()->after('wb_order_id')->comment('orderUid');
            $table->string('wb_rid')->nullable()->after('wb_order_uid')->comment('rid');

            // Товар
            $table->unsignedBigInteger('wb_nm_id')->nullable()->after('wb_rid')->index()->comment('nmId');
            $table->unsignedBigInteger('wb_chrt_id')->nullable()->after('wb_nm_id')->index()->comment('chrtId');
            $table->string('wb_article')->nullable()->after('wb_chrt_id')->comment('article');
            $table->json('wb_skus')->nullable()->after('wb_article')->comment('skus[]');

            // Логистика
            $table->unsignedBigInteger('wb_warehouse_id')->nullable()->after('supply_id')->index()->comment('warehouseId');
            $table->unsignedBigInteger('wb_office_id')->nullable()->after('wb_warehouse_id')->index()->comment('officeId');
            $table->json('wb_offices')->nullable()->after('wb_office_id')->comment('offices[]');

            $table->string('wb_delivery_type', 50)->nullable()->after('wb_offices')->comment('deliveryType (fbs, dbs, wbgo)');
            $table->unsignedTinyInteger('wb_cargo_type')->nullable()->after('wb_delivery_type')->comment('cargoType');
            $table->boolean('wb_is_zero_order')->default(false)->after('wb_cargo_type')->comment('isZeroOrder');
            $table->boolean('wb_is_b2b')->default(false)->after('wb_is_zero_order')->comment('options.isB2b');

            // Адрес доставки
            $table->string('wb_address_full', 500)->nullable()->after('wb_is_b2b')->comment('address.fullAddress');
            $table->decimal('wb_address_lat', 10, 6)->nullable()->after('wb_address_full')->comment('latitude');
            $table->decimal('wb_address_lng', 10, 6)->nullable()->after('wb_address_lat')->comment('longitude');

            // Финансы (цены в копейках)
            $table->bigInteger('wb_price')->nullable()->after('wb_address_lng')->comment('price в копейках');
            $table->bigInteger('wb_final_price')->nullable()->after('wb_price')->comment('finalPrice в копейках');
            $table->bigInteger('wb_converted_price')->nullable()->after('wb_final_price')->comment('convertedPrice');
            $table->bigInteger('wb_converted_final_price')->nullable()->after('wb_converted_price')->comment('convertedFinalPrice');
            $table->bigInteger('wb_sale_price')->nullable()->after('wb_converted_final_price')->comment('salePrice в копейках');
            $table->bigInteger('wb_scan_price')->nullable()->after('wb_sale_price')->comment('scanPrice в копейках');
            $table->integer('wb_currency_code')->nullable()->after('wb_scan_price')->comment('currencyCode (643=RUB)');
            $table->integer('wb_converted_currency_code')->nullable()->after('wb_currency_code')->comment('convertedCurrencyCode');

            // Даты
            $table->date('wb_ddate')->nullable()->after('wb_converted_currency_code')->comment('ddate - плановая дата');
            $table->dateTimeTz('wb_created_at_utc')->nullable()->after('wb_ddate')->comment('createdAt из WB');

            // Метаданные и комментарии
            $table->json('wb_required_meta')->nullable()->after('wb_created_at_utc')->comment('requiredMeta[]');
            $table->json('wb_optional_meta')->nullable()->after('wb_required_meta')->comment('optionalMeta[]');
            $table->text('wb_comment')->nullable()->after('wb_optional_meta')->comment('comment от клиента');

            // Группировка для UI (новое поле согласно ТЗ)
            $table->string('wb_status_group', 50)->nullable()->after('wb_status')->index()->comment('new, assembling, shipping, archive, canceled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            // Удаляем индексы
            $table->dropIndex(['wb_order_id']);
            $table->dropIndex(['wb_nm_id']);
            $table->dropIndex(['wb_chrt_id']);
            $table->dropIndex(['wb_warehouse_id']);
            $table->dropIndex(['wb_office_id']);
            $table->dropIndex(['wb_status_group']);

            // Удаляем колонки
            $table->dropColumn([
                'wb_order_id', 'wb_order_uid', 'wb_rid',
                'wb_nm_id', 'wb_chrt_id', 'wb_article', 'wb_skus',
                'wb_warehouse_id', 'wb_office_id', 'wb_offices',
                'wb_delivery_type', 'wb_cargo_type', 'wb_is_zero_order', 'wb_is_b2b',
                'wb_address_full', 'wb_address_lat', 'wb_address_lng',
                'wb_price', 'wb_final_price', 'wb_converted_price', 'wb_converted_final_price',
                'wb_sale_price', 'wb_scan_price', 'wb_currency_code', 'wb_converted_currency_code',
                'wb_ddate', 'wb_created_at_utc',
                'wb_required_meta', 'wb_optional_meta', 'wb_comment',
                'wb_status_group',
            ]);
        });
    }
};
