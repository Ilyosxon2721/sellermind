<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'deleted' to the status ENUM
        DB::statement("ALTER TABLE finance_transactions MODIFY COLUMN status ENUM('draft', 'confirmed', 'cancelled', 'deleted') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any 'deleted' records to 'cancelled'
        DB::table('finance_transactions')
            ->where('status', 'deleted')
            ->update(['status' => 'cancelled']);

        // Then revert the ENUM
        DB::statement("ALTER TABLE finance_transactions MODIFY COLUMN status ENUM('draft', 'confirmed', 'cancelled') DEFAULT 'draft'");
    }
};
