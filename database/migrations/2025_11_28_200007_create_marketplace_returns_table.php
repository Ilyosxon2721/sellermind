<?php

// file: database/migrations/2025_11_28_200007_create_marketplace_returns_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_returns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_order_id')
                ->constrained('marketplace_orders')
                ->cascadeOnDelete();

            $table->foreignId('marketplace_order_item_id')
                ->nullable()
                ->constrained('marketplace_order_items')
                ->nullOnDelete();

            $table->string('external_return_id', 100)->nullable();
            $table->string('reason_code', 100)->nullable();
            $table->string('reason_text')->nullable();

            $table->integer('quantity')->default(1);

            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();

            $table->timestamp('returned_at')->nullable();

            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['marketplace_order_id', 'external_return_id'], 'mp_returns_order_ext_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_returns');
    }
};
