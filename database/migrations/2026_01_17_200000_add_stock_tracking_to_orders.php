<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляет поля для отслеживания резервирования и продажи товаров
 *
 * stock_status:
 *   - none: остаток не обрабатывался
 *   - reserved: товар зарезервирован (new, in_assembly)
 *   - sold: товар продан (in_delivery+)
 *   - released: резерв отменён (cancelled до отправки)
 *   - returned: возврат после продажи (требует ручной обработки)
 */
return new class extends Migration
{
    public function up(): void
    {
        // WB Orders
        if (Schema::hasTable('wb_orders')) {
            Schema::table('wb_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('wb_orders', 'stock_status')) {
                    $table->string('stock_status', 20)->default('none')->after('raw_payload')
                        ->comment('none, reserved, sold, released, returned');
                }
                if (!Schema::hasColumn('wb_orders', 'stock_reserved_at')) {
                    $table->timestamp('stock_reserved_at')->nullable()->after('stock_status');
                }
                if (!Schema::hasColumn('wb_orders', 'stock_sold_at')) {
                    $table->timestamp('stock_sold_at')->nullable()->after('stock_reserved_at');
                }
                if (!Schema::hasColumn('wb_orders', 'stock_released_at')) {
                    $table->timestamp('stock_released_at')->nullable()->after('stock_sold_at');
                }
            });
        }

        // Uzum Orders
        if (Schema::hasTable('uzum_orders')) {
            Schema::table('uzum_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('uzum_orders', 'stock_status')) {
                    $table->string('stock_status', 20)->default('none')->after('raw_payload')
                        ->comment('none, reserved, sold, released, returned');
                }
                if (!Schema::hasColumn('uzum_orders', 'stock_reserved_at')) {
                    $table->timestamp('stock_reserved_at')->nullable()->after('stock_status');
                }
                if (!Schema::hasColumn('uzum_orders', 'stock_sold_at')) {
                    $table->timestamp('stock_sold_at')->nullable()->after('stock_reserved_at');
                }
                if (!Schema::hasColumn('uzum_orders', 'stock_released_at')) {
                    $table->timestamp('stock_released_at')->nullable()->after('stock_sold_at');
                }
            });
        }

        // Ozon Orders
        if (Schema::hasTable('ozon_orders')) {
            Schema::table('ozon_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('ozon_orders', 'stock_status')) {
                    $table->string('stock_status', 20)->default('none')->after('order_data')
                        ->comment('none, reserved, sold, released, returned');
                }
                if (!Schema::hasColumn('ozon_orders', 'stock_reserved_at')) {
                    $table->timestamp('stock_reserved_at')->nullable()->after('stock_status');
                }
                if (!Schema::hasColumn('ozon_orders', 'stock_sold_at')) {
                    $table->timestamp('stock_sold_at')->nullable()->after('stock_reserved_at');
                }
                if (!Schema::hasColumn('ozon_orders', 'stock_released_at')) {
                    $table->timestamp('stock_released_at')->nullable()->after('stock_sold_at');
                }
            });
        }

        // Таблица для отслеживания возвратов (для ручной обработки)
        if (!Schema::hasTable('marketplace_returns')) {
            Schema::create('marketplace_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('marketplace_account_id')->constrained()->cascadeOnDelete();

                // Тип заказа и ID
                $table->string('order_type', 20); // wb, uzum, ozon
                $table->unsignedBigInteger('order_id'); // ID в соответствующей таблице
                $table->string('external_order_id')->index();

                // Статус обработки возврата
                $table->string('status', 20)->default('pending')
                    ->comment('pending, processed, rejected');

                // Действие при обработке
                $table->string('action', 20)->nullable()
                    ->comment('return_to_stock, write_off, null');

                // Информация о возврате
                $table->text('return_reason')->nullable();
                $table->timestamp('returned_at')->nullable();

                // Обработка
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->text('process_notes')->nullable();

                $table->timestamps();

                $table->index(['order_type', 'order_id']);
                $table->index(['company_id', 'status']);
            });
        }

        // Таблица для детального логирования операций с остатками
        if (!Schema::hasTable('order_stock_logs')) {
            Schema::create('order_stock_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('marketplace_account_id')->constrained()->cascadeOnDelete();

                // Заказ
                $table->string('order_type', 20); // wb, uzum, ozon
                $table->unsignedBigInteger('order_id');
                $table->string('external_order_id');

                // Товар
                $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
                $table->string('external_sku')->nullable();
                $table->string('barcode')->nullable();

                // Операция
                $table->string('action', 20); // reserve, release, sell, return
                $table->integer('quantity');
                $table->integer('stock_before')->nullable();
                $table->integer('stock_after')->nullable();

                // Результат
                $table->boolean('success')->default(true);
                $table->text('error_message')->nullable();

                $table->timestamps();

                $table->index(['order_type', 'order_id']);
                $table->index(['product_variant_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        // WB Orders
        if (Schema::hasTable('wb_orders')) {
            Schema::table('wb_orders', function (Blueprint $table) {
                $table->dropColumn(['stock_status', 'stock_reserved_at', 'stock_sold_at', 'stock_released_at']);
            });
        }

        // Uzum Orders
        if (Schema::hasTable('uzum_orders')) {
            Schema::table('uzum_orders', function (Blueprint $table) {
                $table->dropColumn(['stock_status', 'stock_reserved_at', 'stock_sold_at', 'stock_released_at']);
            });
        }

        // Ozon Orders
        if (Schema::hasTable('ozon_orders')) {
            Schema::table('ozon_orders', function (Blueprint $table) {
                $table->dropColumn(['stock_status', 'stock_reserved_at', 'stock_sold_at', 'stock_released_at']);
            });
        }

        Schema::dropIfExists('order_stock_logs');
        Schema::dropIfExists('marketplace_returns');
    }
};
