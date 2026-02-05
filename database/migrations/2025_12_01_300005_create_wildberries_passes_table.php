<?php

// file: database/migrations/2025_12_01_300005_create_wildberries_passes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create wildberries_passes table for storing WB warehouse passes data.
     */
    public function up(): void
    {
        Schema::create('wildberries_passes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            // Pass identifiers
            $table->string('pass_id', 100)->unique();           // WB Pass ID
            $table->string('office_id', 100)->nullable();       // Warehouse/Office ID

            // Person info
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('patronymic', 100)->nullable();

            // Vehicle info (optional)
            $table->string('car_model', 100)->nullable();
            $table->string('car_number', 50)->nullable();

            // Pass dates
            $table->date('date_from');
            $table->date('date_to');

            // Status
            $table->string('status', 50)->default('active');    // active, expired, cancelled

            // Raw data from WB
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['marketplace_account_id', 'status'], 'wb_pass_acc_status_idx');
            $table->index(['marketplace_account_id', 'date_to'], 'wb_pass_acc_dateto_idx');
            $table->index(['marketplace_account_id', 'office_id'], 'wb_pass_acc_office_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wildberries_passes');
    }
};
