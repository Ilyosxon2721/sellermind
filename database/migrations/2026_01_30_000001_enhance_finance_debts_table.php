<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_debts', function (Blueprint $table) {
            $table->string('purpose', 20)->default('debt')->after('type');
            $table->unsignedBigInteger('counterparty_entity_id')->nullable()->after('counterparty_id');
            $table->unsignedBigInteger('cash_account_id')->nullable()->after('notes');
            $table->dateTime('written_off_at')->nullable()->after('status');
            $table->unsignedBigInteger('written_off_by')->nullable()->after('written_off_at');
            $table->text('written_off_reason')->nullable()->after('written_off_by');

            $table->index(['company_id', 'purpose']);
            $table->index('counterparty_entity_id');
        });

        Schema::table('finance_debt_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_account_id')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('finance_debts', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'purpose']);
            $table->dropIndex(['counterparty_entity_id']);
            $table->dropColumn([
                'purpose',
                'counterparty_entity_id',
                'cash_account_id',
                'written_off_at',
                'written_off_by',
                'written_off_reason',
            ]);
        });

        Schema::table('finance_debt_payments', function (Blueprint $table) {
            $table->dropColumn('cash_account_id');
        });
    }
};
