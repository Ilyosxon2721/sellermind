<?php
// file: database/migrations/2025_11_28_200004_create_marketplace_pricing_rules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_pricing_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            // Правило может быть привязано к категории или конкретному товару
            $table->foreignId('internal_category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            // режим: fixed_margin, target_roi, copy_from_channel, custom
            $table->string('mode', 30)->default('fixed_margin');

            // фиксированная наценка в %, например 50%
            $table->decimal('margin_percent', 8, 2)->nullable();

            // целевая рентабельность (ROI), если mode = target_roi
            $table->decimal('target_roi_percent', 8, 2)->nullable();

            // источник цены, если mode = copy_from_channel
            $table->string('price_source', 50)->nullable(); // например: "uzum", "ozon", "instagram"

            // ограничения
            $table->decimal('min_price', 15, 2)->nullable();
            $table->decimal('max_price', 15, 2)->nullable();

            // правило округления: none, to_1, to_10, to_50, to_100, to_9_99 и т.п.
            $table->string('rounding_rule', 50)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['marketplace_account_id', 'internal_category_id', 'product_id'], 'mp_pricing_scope_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_pricing_rules');
    }
};
