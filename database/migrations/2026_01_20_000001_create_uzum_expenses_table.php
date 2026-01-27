<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uzum_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->cascadeOnDelete();

            // Identifiers from Uzum Finance Expenses API
            $table->unsignedBigInteger('uzum_id')->comment('id from API');
            $table->unsignedBigInteger('shop_id');

            // Expense info
            $table->string('name')->comment('Expense name/description');
            $table->string('source', 100)->comment('Category source: Marketing, Logistika, Ombor, Uzum Market, Obuna');
            $table->string('source_normalized', 50)->nullable()->comment('Normalized: advertising, logistics, storage, penalties, commission');

            // Financial data (in UZS)
            $table->bigInteger('payment_price')->default(0)->comment('Original payment price');
            $table->bigInteger('amount')->default(0)->comment('Actual amount charged');

            // Dates
            $table->timestamp('date_created')->nullable()->comment('When expense was created');
            $table->timestamp('date_service')->nullable()->comment('When service was provided');

            // Status
            $table->string('status', 50)->nullable()->comment('Payment status');

            // Raw data
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['marketplace_account_id', 'uzum_id'], 'uzum_expenses_account_uzum_id_unique');
            $table->index(['marketplace_account_id', 'source']);
            $table->index(['marketplace_account_id', 'date_service']);
            $table->index(['marketplace_account_id', 'source_normalized']);
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzum_expenses');
    }
};
