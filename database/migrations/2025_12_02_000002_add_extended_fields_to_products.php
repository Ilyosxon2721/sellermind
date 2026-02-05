<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product schema already contains required descriptive and dimensional fields.
    }

    public function down(): void
    {
        // No-op.
    }
};
