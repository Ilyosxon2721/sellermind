<?php

// file: database/migrations/2025_11_28_200009_create_marketplace_payout_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_payout_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_payout_id')
                ->constrained('marketplace_payouts')
                ->cascadeOnDelete();

            $table->foreignId('marketplace_order_id')
                ->nullable()
                ->constrained('marketplace_orders')
                ->nullOnDelete();

            $table->foreignId('marketplace_order_item_id')
                ->nullable()
                ->constrained('marketplace_order_items')
                ->nullOnDelete();

            // Тип операции: sale, return, commission, logistics, storage, adv, penalty, other
            $table->string('operation_type', 50);

            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();

            $table->string('description')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['marketplace_payout_id', 'operation_type'], 'mp_payout_items_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_payout_items');
    }
};
