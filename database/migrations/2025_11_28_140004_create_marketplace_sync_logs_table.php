<?php
// file: database/migrations/2025_11_28_140004_create_marketplace_sync_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_sync_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();

            $table->string('type', 50);   // products, prices, stocks, orders, reports
            $table->string('status', 20); // pending, running, success, error

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->text('message')->nullable(); // краткое описание или ошибка
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            $table->timestamps();

            $table->index(['marketplace_account_id', 'type', 'status'], 'mp_sync_log_idx');
            $table->index(['marketplace_account_id', 'created_at'], 'mp_sync_log_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_sync_logs');
    }
};
