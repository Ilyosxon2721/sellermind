<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для добавления недостающих индексов в таблицы SellerMind.
 *
 * Каждая операция обёрнута в try/catch, чтобы миграция не падала
 * если индекс уже существует или колонка отсутствует.
 *
 * НЕ добавляем FK constraints на stock_ledger — только индексы,
 * чтобы не сломать существующие данные.
 */
return new class extends Migration
{
    /**
     * Проверить, существует ли индекс в таблице
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $results = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        return count($results) > 0;
    }

    public function up(): void
    {
        // 1. sales — добавить составной индекс для timeline запросов
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table): void {
                // warehouse_id индекс уже существует (добавлен в add_warehouse_id_to_sales_table),
                // но проверяем на всякий случай
                if (Schema::hasColumn('sales', 'warehouse_id')) {
                    try {
                        if (! $this->indexExists('sales', 'sales_warehouse_id_index')) {
                            $table->index('warehouse_id', 'sales_warehouse_id_index');
                        }
                    } catch (\Throwable) {
                        // Индекс уже существует — пропускаем
                    }
                }

                // Составной индекс для запросов типа "продажи компании за период"
                try {
                    if (! $this->indexExists('sales', 'sales_company_created_index')) {
                        $table->index(['company_id', 'created_at'], 'sales_company_created_index');
                    }
                } catch (\Throwable) {
                    // Индекс уже существует — пропускаем
                }
            });
        }

        // 2. finance_debts — составной индекс для фильтрации по статусу и дате
        if (Schema::hasTable('finance_debts')) {
            Schema::table('finance_debts', function (Blueprint $table): void {
                try {
                    if (! $this->indexExists('finance_debts', 'finance_debts_company_status_due_index')) {
                        $table->index(
                            ['company_id', 'status', 'due_date'],
                            'finance_debts_company_status_due_index'
                        );
                    }
                } catch (\Throwable) {
                    // Индекс уже существует — пропускаем
                }
            });
        }

        // 3. stock_ledger — отдельные индексы для warehouse_id и company_id
        //    (составной ledger_company_wh_sku уже есть, но отдельные ускорят простые фильтрации)
        if (Schema::hasTable('stock_ledger')) {
            Schema::table('stock_ledger', function (Blueprint $table): void {
                try {
                    if (! $this->indexExists('stock_ledger', 'stock_ledger_warehouse_id_index')) {
                        $table->index('warehouse_id', 'stock_ledger_warehouse_id_index');
                    }
                } catch (\Throwable) {
                    // Индекс уже существует — пропускаем
                }

                try {
                    if (! $this->indexExists('stock_ledger', 'stock_ledger_company_id_index')) {
                        $table->index('company_id', 'stock_ledger_company_id_index');
                    }
                } catch (\Throwable) {
                    // Индекс уже существует — пропускаем
                }
            });
        }

        // 4. stock_reservations — отдельные индексы для company_id и warehouse_id
        //    (составной reservations_company_wh_sku_status уже есть, но отдельные полезны)
        if (Schema::hasTable('stock_reservations')) {
            Schema::table('stock_reservations', function (Blueprint $table): void {
                try {
                    if (! $this->indexExists('stock_reservations', 'stock_reservations_company_id_index')) {
                        $table->index('company_id', 'stock_reservations_company_id_index');
                    }
                } catch (\Throwable) {
                    // Индекс уже существует — пропускаем
                }

                try {
                    if (! $this->indexExists('stock_reservations', 'stock_reservations_warehouse_id_index')) {
                        $table->index('warehouse_id', 'stock_reservations_warehouse_id_index');
                    }
                } catch (\Throwable) {
                    // Индекс уже существует — пропускаем
                }
            });
        }

        // 5. cash_transactions — дополнительные индексы для type и category_id
        if (Schema::hasTable('cash_transactions')) {
            Schema::table('cash_transactions', function (Blueprint $table): void {
                // Отдельный индекс по type (составной ['company_id', 'type'] уже есть,
                // но отдельный полезен для агрегатных запросов без фильтра по company)
                if (Schema::hasColumn('cash_transactions', 'type')) {
                    try {
                        if (! $this->indexExists('cash_transactions', 'cash_transactions_type_index')) {
                            $table->index('type', 'cash_transactions_type_index');
                        }
                    } catch (\Throwable) {
                        // Индекс уже существует — пропускаем
                    }
                }

                // category_id может иметь FK индекс автоматически, но добавим явный
                if (Schema::hasColumn('cash_transactions', 'category_id')) {
                    try {
                        if (! $this->indexExists('cash_transactions', 'cash_transactions_category_id_index')) {
                            $table->index('category_id', 'cash_transactions_category_id_index');
                        }
                    } catch (\Throwable) {
                        // Индекс уже существует (FK автоматически создал) — пропускаем
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // sales
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table): void {
                try {
                    if ($this->indexExists('sales', 'sales_company_created_index')) {
                        $table->dropIndex('sales_company_created_index');
                    }
                } catch (\Throwable) {
                    // Индекс не существует — пропускаем
                }

                // Не удаляем sales_warehouse_id_index — он мог быть создан другой миграцией
            });
        }

        // finance_debts
        if (Schema::hasTable('finance_debts')) {
            Schema::table('finance_debts', function (Blueprint $table): void {
                try {
                    if ($this->indexExists('finance_debts', 'finance_debts_company_status_due_index')) {
                        $table->dropIndex('finance_debts_company_status_due_index');
                    }
                } catch (\Throwable) {
                    // Индекс не существует — пропускаем
                }
            });
        }

        // stock_ledger
        if (Schema::hasTable('stock_ledger')) {
            Schema::table('stock_ledger', function (Blueprint $table): void {
                try {
                    if ($this->indexExists('stock_ledger', 'stock_ledger_warehouse_id_index')) {
                        $table->dropIndex('stock_ledger_warehouse_id_index');
                    }
                } catch (\Throwable) {
                    // Индекс не существует — пропускаем
                }

                try {
                    if ($this->indexExists('stock_ledger', 'stock_ledger_company_id_index')) {
                        $table->dropIndex('stock_ledger_company_id_index');
                    }
                } catch (\Throwable) {
                    // Индекс не существует — пропускаем
                }
            });
        }

        // stock_reservations
        if (Schema::hasTable('stock_reservations')) {
            Schema::table('stock_reservations', function (Blueprint $table): void {
                try {
                    if ($this->indexExists('stock_reservations', 'stock_reservations_company_id_index')) {
                        $table->dropIndex('stock_reservations_company_id_index');
                    }
                } catch (\Throwable) {
                    // Индекс не существует — пропускаем
                }

                try {
                    if ($this->indexExists('stock_reservations', 'stock_reservations_warehouse_id_index')) {
                        $table->dropIndex('stock_reservations_warehouse_id_index');
                    }
                } catch (\Throwable) {
                    // Индекс не существует — пропускаем
                }
            });
        }

        // cash_transactions
        if (Schema::hasTable('cash_transactions')) {
            Schema::table('cash_transactions', function (Blueprint $table): void {
                try {
                    if ($this->indexExists('cash_transactions', 'cash_transactions_type_index')) {
                        $table->dropIndex('cash_transactions_type_index');
                    }
                } catch (\Throwable) {
                    // Индекс не существует — пропускаем
                }

                try {
                    if ($this->indexExists('cash_transactions', 'cash_transactions_category_id_index')) {
                        $table->dropIndex('cash_transactions_category_id_index');
                    }
                } catch (\Throwable) {
                    // Индекс не существует — пропускаем
                }
            });
        }
    }
};
