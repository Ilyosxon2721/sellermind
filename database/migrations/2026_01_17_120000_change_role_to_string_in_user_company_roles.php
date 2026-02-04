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
        // Change ENUM to VARCHAR to allow custom role names
        DB::statement("ALTER TABLE user_company_roles MODIFY COLUMN role VARCHAR(100) NOT NULL DEFAULT 'manager'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to ENUM (will truncate non-matching values)
        DB::statement("ALTER TABLE user_company_roles MODIFY COLUMN role ENUM('owner', 'manager') NOT NULL DEFAULT 'manager'");
    }
};
