<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ozon_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->onDelete('cascade');
            $table->string('warehouse_id')->index(); // from Ozon API
            $table->string('name');
            $table->string('type')->nullable(); // fbs, fbo, express
            $table->boolean('is_active')->default(true);
            $table->boolean('has_entrusting')->default(false); // Can work with entrusting
            $table->boolean('can_print_act_in_advance')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_account_id', 'warehouse_id'], 'ozon_warehouses_account_wh_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ozon_warehouses');
    }
};
