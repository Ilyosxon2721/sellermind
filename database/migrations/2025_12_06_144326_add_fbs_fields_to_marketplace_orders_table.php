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
            // Метаданные - добавляем только те, которых нет
            if (!Schema::hasColumn('marketplace_orders', 'required_meta')) {
                $table->json('required_meta')->nullable()->comment('Обязательные метаданные из requiredMeta');
            }
            if (!Schema::hasColumn('marketplace_orders', 'optional_meta')) {
                $table->json('optional_meta')->nullable()->comment('Опциональные метаданные из optionalMeta');
            }
            // meta_sgtin, meta_uin, meta_imei, meta_gtin, meta_expiration_date уже существуют

            // Статусы и история
            // supplier_status, wb_status_group уже существуют
            if (!Schema::hasColumn('marketplace_orders', 'status_history')) {
                $table->json('status_history')->nullable()->comment('История изменения статусов');
            }

            // Тип груза - wb_cargo_type уже существует как tinyint, но нам нужен string
            // Оставляем как есть, используем существующее поле

            // Стикеры
            if (!Schema::hasColumn('marketplace_orders', 'sticker_path')) {
                $table->string('sticker_path')->nullable()->comment('Путь к файлу стикера');
            }
            if (!Schema::hasColumn('marketplace_orders', 'sticker_generated_at')) {
                $table->timestamp('sticker_generated_at')->nullable()->comment('Когда сгенерирован стикер');
            }

            // Даты
            if (!Schema::hasColumn('marketplace_orders', 'cancel_dt')) {
                $table->timestamp('cancel_dt')->nullable()->index()->comment('Дата отмены заказа');
            }

            // Адрес доставки
            if (!Schema::hasColumn('marketplace_orders', 'delivery_address_full')) {
                $table->text('delivery_address_full')->nullable()->comment('Полный адрес доставки');
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_province')) {
                $table->string('delivery_province')->nullable()->comment('Область');
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_area')) {
                $table->string('delivery_area')->nullable()->comment('Район');
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_city')) {
                $table->string('delivery_city')->nullable()->comment('Город');
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_street')) {
                $table->string('delivery_street')->nullable()->comment('Улица');
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_home')) {
                $table->string('delivery_home', 50)->nullable()->comment('Дом');
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_flat')) {
                $table->string('delivery_flat', 50)->nullable()->comment('Квартира');
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_entrance')) {
                $table->string('delivery_entrance', 50)->nullable()->comment('Подъезд');
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_longitude')) {
                $table->decimal('delivery_longitude', 10, 7)->nullable()->comment('Долгота');
            }
            if (!Schema::hasColumn('marketplace_orders', 'delivery_latitude')) {
                $table->decimal('delivery_latitude', 10, 7)->nullable()->comment('Широта');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            // Удаляем только те колонки, которые были добавлены этой миграцией
            $columnsToCheck = [
                'required_meta',
                'optional_meta',
                'status_history',
                'sticker_path',
                'sticker_generated_at',
                'cancel_dt',
                'delivery_address_full',
                'delivery_province',
                'delivery_area',
                'delivery_city',
                'delivery_street',
                'delivery_home',
                'delivery_flat',
                'delivery_entrance',
                'delivery_longitude',
                'delivery_latitude',
            ];

            $columnsToDrop = [];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('marketplace_orders', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
