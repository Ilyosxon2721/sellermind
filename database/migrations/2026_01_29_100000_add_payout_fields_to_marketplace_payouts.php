<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_payouts', function (Blueprint $table) {
            // Добавляем недостающие поля
            if (!Schema::hasColumn('marketplace_payouts', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'marketplace')) {
                $table->string('marketplace', 32)->nullable()->after('marketplace_account_id');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'payout_id')) {
                $table->string('payout_id', 100)->nullable()->after('marketplace');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'payout_date')) {
                $table->date('payout_date')->nullable()->after('payout_id');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'gross_amount')) {
                $table->decimal('gross_amount', 18, 2)->default(0)->after('period_to');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'commission')) {
                $table->decimal('commission', 18, 2)->default(0)->after('gross_amount');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'logistics')) {
                $table->decimal('logistics', 18, 2)->default(0)->after('commission');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'storage')) {
                $table->decimal('storage', 18, 2)->default(0)->after('logistics');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'advertising')) {
                $table->decimal('advertising', 18, 2)->default(0)->after('storage');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'penalties')) {
                $table->decimal('penalties', 18, 2)->default(0)->after('advertising');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'returns')) {
                $table->decimal('returns', 18, 2)->default(0)->after('penalties');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'other_deductions')) {
                $table->decimal('other_deductions', 18, 2)->default(0)->after('returns');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'net_amount')) {
                $table->decimal('net_amount', 18, 2)->default(0)->after('other_deductions');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'currency_code')) {
                $table->string('currency_code', 8)->default('UZS')->after('net_amount');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'exchange_rate')) {
                $table->decimal('exchange_rate', 18, 6)->default(1)->after('currency_code');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'amount_base')) {
                $table->decimal('amount_base', 18, 2)->default(0)->after('exchange_rate');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'cash_transaction_id')) {
                $table->unsignedBigInteger('cash_transaction_id')->nullable()->after('amount_base');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'status')) {
                $table->string('status', 32)->default('pending')->after('cash_transaction_id');
            }
            if (!Schema::hasColumn('marketplace_payouts', 'raw_data')) {
                $table->json('raw_data')->nullable()->after('status');
            }
        });

        // Добавляем индексы
        Schema::table('marketplace_payouts', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_payouts', 'payout_id_idx')) {
                $table->index(['marketplace_account_id', 'payout_id'], 'mp_payouts_account_payout_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_payouts', function (Blueprint $table) {
            $table->dropColumn([
                'company_id', 'marketplace', 'payout_id', 'payout_date',
                'gross_amount', 'commission', 'logistics', 'storage',
                'advertising', 'penalties', 'returns', 'other_deductions',
                'net_amount', 'currency_code', 'exchange_rate', 'amount_base',
                'cash_transaction_id', 'status', 'raw_data'
            ]);
        });
    }
};
