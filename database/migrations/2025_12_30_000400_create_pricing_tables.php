<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pricing_scenarios')) {
            Schema::create('pricing_scenarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('target_margin_percent', 18, 6)->default(0.3);
                $table->decimal('target_profit_fixed', 18, 2)->default(0);
                $table->decimal('promo_reserve_percent', 18, 6)->default(0);
                $table->string('tax_mode', 24)->default('NONE');
                $table->decimal('vat_percent', 18, 6)->default(0);
                $table->decimal('profit_tax_percent', 18, 6)->default(0);
                $table->string('rounding_mode', 16)->default('UP');
                $table->decimal('rounding_step', 18, 2)->default(1000);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
                $table->index('company_id');
                $table->unique(['company_id', 'name']);
            });
        }

        if (!Schema::hasTable('pricing_channel_overrides')) {
            Schema::create('pricing_channel_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('scenario_id');
                $table->string('channel_code', 32);
                $table->decimal('override_target_margin_percent', 18, 6)->nullable();
                $table->decimal('override_promo_reserve_percent', 18, 6)->nullable();
                $table->decimal('override_rounding_step', 18, 2)->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'scenario_id', 'channel_code'], 'pricing_channel_override_unique');
            });
        }

        if (!Schema::hasTable('pricing_sku_overrides')) {
            Schema::create('pricing_sku_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('scenario_id');
                $table->unsignedBigInteger('sku_id');
                $table->decimal('cost_override', 18, 2)->nullable();
                $table->decimal('min_profit_fixed', 18, 2)->nullable();
                $table->decimal('target_margin_percent', 18, 6)->nullable();
                $table->decimal('promo_reserve_percent', 18, 6)->nullable();
                $table->boolean('is_excluded')->default(false);
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'scenario_id', 'sku_id'], 'pricing_sku_override_unique');
                $table->index('sku_id');
            });
        }

        if (!Schema::hasTable('price_calculations')) {
            Schema::create('price_calculations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->dateTime('calculated_at');
                $table->unsignedBigInteger('scenario_id');
                $table->string('channel_code', 32);
                $table->unsignedBigInteger('sku_id');
                $table->decimal('unit_cost', 18, 2)->default(0);
                $table->string('currency_code', 8)->default('UZS');
                $table->decimal('min_price', 18, 2)->default(0);
                $table->decimal('recommended_price', 18, 2)->default(0);
                $table->json('breakdown_json')->nullable();
                $table->string('confidence', 16)->default('LOW');
                $table->timestamps();
                $table->unique(['company_id', 'scenario_id', 'channel_code', 'sku_id'], 'price_calc_unique');
                $table->index('sku_id');
            });
        }

        if (!Schema::hasTable('price_publish_jobs')) {
            Schema::create('price_publish_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('scenario_id');
                $table->string('channel_code', 32);
                $table->string('status', 24)->default('DRAFT');
                $table->json('payload_json');
                $table->json('result_json')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('finished_at')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'status']);
                $table->index('channel_code');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('price_publish_jobs');
        Schema::dropIfExists('price_calculations');
        Schema::dropIfExists('pricing_sku_overrides');
        Schema::dropIfExists('pricing_channel_overrides');
        Schema::dropIfExists('pricing_scenarios');
    }
};
