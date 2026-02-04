<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('autopricing_policies')) {
            Schema::create('autopricing_policies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->string('channel_code', 32)->nullable();
                $table->unsignedBigInteger('scenario_id');
                $table->string('mode', 24)->default('SUGGEST_ONLY');
                $table->integer('priority')->default(100);
                $table->integer('cooldown_hours')->default(24);
                $table->integer('max_changes_per_day')->default(50);
                $table->decimal('max_delta_percent', 18, 6)->default(0.10);
                $table->decimal('max_delta_amount', 18, 2)->default(0);
                $table->boolean('min_price_guard')->default(true);
                $table->boolean('max_price_guard')->default(false);
                $table->decimal('max_price_value', 18, 2)->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'is_active']);
                $table->index(['company_id', 'channel_code']);
                $table->index('priority');
            });
        }

        if (! Schema::hasTable('autopricing_rules')) {
            Schema::create('autopricing_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('policy_id');
                $table->string('scope_type', 24); // GLOBAL/CATEGORY/SKU
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->string('rule_type', 32);
                $table->json('params_json')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(100);
                $table->timestamps();
                $table->index(['policy_id', 'is_active']);
                $table->index(['scope_type', 'scope_id']);
                $table->index('priority');
            });
        }

        if (! Schema::hasTable('autopricing_proposals')) {
            Schema::create('autopricing_proposals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->dateTime('calculated_at');
                $table->unsignedBigInteger('policy_id');
                $table->string('channel_code', 32);
                $table->unsignedBigInteger('sku_id');
                $table->decimal('current_price', 18, 2)->nullable();
                $table->decimal('min_price', 18, 2);
                $table->decimal('recommended_price', 18, 2)->nullable();
                $table->decimal('proposed_price', 18, 2);
                $table->decimal('delta_amount', 18, 2);
                $table->decimal('delta_percent', 18, 6);
                $table->string('status', 24)->default('NEW');
                $table->json('reasons_json')->nullable();
                $table->json('safety_flags_json')->nullable();
                $table->unsignedBigInteger('applied_job_id')->nullable();
                $table->dateTime('applied_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'calculated_at']);
                $table->index('policy_id');
                $table->unique(['company_id', 'channel_code', 'sku_id', 'calculated_at'], 'autopricing_proposal_unique');
            });
        }

        if (! Schema::hasTable('autopricing_daily_counters')) {
            Schema::create('autopricing_daily_counters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->date('date');
                $table->unsignedBigInteger('policy_id');
                $table->string('channel_code', 32);
                $table->integer('changes_count')->default(0);
                $table->timestamps();
                $table->unique(['company_id', 'date', 'policy_id', 'channel_code'], 'ap_daily_counter_unique');
            });
        }

        if (! Schema::hasTable('autopricing_change_log')) {
            Schema::create('autopricing_change_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('proposal_id');
                $table->string('channel_code', 32);
                $table->unsignedBigInteger('sku_id');
                $table->decimal('old_price', 18, 2)->nullable();
                $table->decimal('new_price', 18, 2)->nullable();
                $table->unsignedBigInteger('applied_by')->nullable();
                $table->boolean('applied_by_system')->default(false);
                $table->string('method', 32)->default('MANUAL_APPROVE');
                $table->json('payload_json')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'channel_code', 'sku_id']);
                $table->index('proposal_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('autopricing_change_log');
        Schema::dropIfExists('autopricing_daily_counters');
        Schema::dropIfExists('autopricing_proposals');
        Schema::dropIfExists('autopricing_rules');
        Schema::dropIfExists('autopricing_policies');
    }
};
