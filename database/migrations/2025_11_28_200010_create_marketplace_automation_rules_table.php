<?php
// file: database/migrations/2025_11_28_200010_create_marketplace_automation_rules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_automation_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            $table->string('name', 255);

            // Тип события: low_stock, no_sales, high_return_rate, competitor_price_drop, etc.
            $table->string('event_type', 50);

            // Условия в виде JSON (пороговые значения, фильтры по категориям/товарам)
            $table->json('conditions_json')->nullable();

            // Тип действия: notify, adjust_price, create_agent_task, etc.
            $table->string('action_type', 50);

            // Параметры действия
            $table->json('action_params_json')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['marketplace_account_id', 'event_type', 'is_active'], 'mp_rules_account_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_automation_rules');
    }
};
