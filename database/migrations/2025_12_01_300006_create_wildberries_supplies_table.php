<?php

// file: database/migrations/2025_12_01_300006_create_wildberries_supplies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create wildberries_supplies table for storing WB FBS supplies data.
     */
    public function up(): void
    {
        Schema::create('wildberries_supplies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            // Supply identifiers
            $table->string('supply_id', 100)->unique();         // WB Supply ID (UUID)
            $table->string('name', 255);                        // Supply name

            // Status
            $table->string('status', 50)->default('created');   // created, in_progress, delivered, cancelled
            $table->boolean('is_large_cargo')->default(false); // КГТ (крупногабаритный товар)
            $table->boolean('is_cross_border')->default(false); // Cross-border supply

            // Orders count
            $table->unsignedInteger('orders_count')->default(0);

            // Dates
            $table->timestamp('created_at_wb')->nullable();     // Created date from WB
            $table->timestamp('closed_at')->nullable();         // Closed/delivered date
            $table->timestamp('cancelled_at')->nullable();      // Cancelled date

            // Raw data from WB
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['marketplace_account_id', 'status'], 'wb_sup_acc_status_idx');
            $table->index(['marketplace_account_id', 'created_at'], 'wb_sup_acc_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wildberries_supplies');
    }
};
