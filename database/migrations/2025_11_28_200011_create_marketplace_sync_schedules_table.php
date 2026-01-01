<?php
// file: database/migrations/2025_11_28_200011_create_marketplace_sync_schedules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_sync_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            // Тип синхронизации: products, prices, stocks, orders, payouts, analytics, automation
            $table->string('sync_type', 50);

            // Cron-выражение: "*/5 * * * *" (каждые 5 минут)
            $table->string('cron_expression', 100);

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_run_at')->nullable();

            $table->timestamps();

            $table->unique(['marketplace_account_id', 'sync_type'], 'mp_sync_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_sync_schedules');
    }
};
