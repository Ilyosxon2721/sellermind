<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_stock_returns', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('marketplace_account_id');

            $table->string('order_type', 20); // wb, uzum, ozon
            $table->unsignedBigInteger('order_id');
            $table->string('external_order_id', 100)->nullable();

            $table->string('status', 20)->default('pending'); // pending, processed, rejected
            $table->string('action', 30)->nullable(); // return_to_stock, write_off

            $table->string('return_reason')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('process_notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status'], 'osr_company_status_idx');
            $table->index(['order_type', 'order_id'], 'osr_order_idx');
            $table->index('marketplace_account_id');

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('marketplace_account_id')->references('id')->on('marketplace_accounts')->cascadeOnDelete();
            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_stock_returns');
    }
};
