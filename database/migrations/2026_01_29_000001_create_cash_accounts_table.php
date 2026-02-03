<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Добавить поля для маркетплейсов в cash_accounts
        if (Schema::hasTable('cash_accounts')) {
            Schema::table('cash_accounts', function (Blueprint $table) {
                // Проверяем и добавляем недостающие колонки
                if (! Schema::hasColumn('cash_accounts', 'marketplace_account_id')) {
                    $table->unsignedBigInteger('marketplace_account_id')->nullable()->after('card_number');
                }
                if (! Schema::hasColumn('cash_accounts', 'marketplace')) {
                    $table->string('marketplace')->nullable()->after('marketplace_account_id');
                }
                if (! Schema::hasColumn('cash_accounts', 'bik')) {
                    $table->string('bik')->nullable()->after('account_number');
                }
                if (! Schema::hasColumn('cash_accounts', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('is_active');
                }
            });

            // Изменяем enum type для добавления 'marketplace' и 'other'
            // В MySQL нужно ALTER COLUMN
            \DB::statement("ALTER TABLE cash_accounts MODIFY COLUMN type ENUM('cash', 'bank', 'card', 'ewallet', 'marketplace', 'other') DEFAULT 'cash'");
        }

        // 2. Добавить поля в cash_transactions если нужны
        if (Schema::hasTable('cash_transactions')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('cash_transactions', 'operation')) {
                    $table->string('operation', 32)->nullable()->after('type');
                }
                if (! Schema::hasColumn('cash_transactions', 'balance_before')) {
                    $table->decimal('balance_before', 18, 2)->default(0)->after('balance_after');
                }
                if (! Schema::hasColumn('cash_transactions', 'source_type')) {
                    $table->string('source_type', 64)->nullable()->after('transfer_from_transaction_id');
                }
                if (! Schema::hasColumn('cash_transactions', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                }
            });
        }

        // 3. Marketplace Payouts - Выплаты маркетплейсов
        if (! Schema::hasTable('marketplace_payouts')) {
            Schema::create('marketplace_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('marketplace_account_id');
                $table->string('marketplace'); // uzum, wb, ozon, ym

                $table->string('payout_id')->nullable(); // ID выплаты в маркетплейсе
                $table->date('payout_date');
                $table->date('period_from')->nullable();
                $table->date('period_to')->nullable();

                // Суммы в валюте маркетплейса
                $table->decimal('gross_amount', 18, 2)->default(0); // Брутто (продажи)
                $table->decimal('commission', 18, 2)->default(0);
                $table->decimal('logistics', 18, 2)->default(0);
                $table->decimal('storage', 18, 2)->default(0);
                $table->decimal('advertising', 18, 2)->default(0);
                $table->decimal('penalties', 18, 2)->default(0);
                $table->decimal('returns', 18, 2)->default(0);
                $table->decimal('other_deductions', 18, 2)->default(0);
                $table->decimal('net_amount', 18, 2)->default(0); // К выплате

                $table->string('currency_code', 8)->default('UZS');
                $table->decimal('exchange_rate', 18, 6)->default(1);
                $table->decimal('amount_base', 18, 2)->default(0); // В базовой валюте

                // Связь с движением денег
                $table->unsignedBigInteger('cash_transaction_id')->nullable();

                $table->enum('status', ['pending', 'received', 'reconciled'])->default('pending');
                $table->json('raw_data')->nullable(); // Оригинальные данные из API
                $table->timestamps();

                $table->index('company_id');
                $table->index('marketplace_account_id');
                $table->index(['company_id', 'payout_date']);
                $table->index(['marketplace', 'payout_id']);
                $table->unique(['marketplace_account_id', 'payout_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_payouts');

        if (Schema::hasTable('cash_accounts')) {
            Schema::table('cash_accounts', function (Blueprint $table) {
                $table->dropColumn(['marketplace_account_id', 'marketplace', 'bik', 'sort_order']);
            });
        }

        if (Schema::hasTable('cash_transactions')) {
            Schema::table('cash_transactions', function (Blueprint $table) {
                $table->dropColumn(['operation', 'balance_before', 'source_type', 'source_id']);
            });
        }
    }
};
