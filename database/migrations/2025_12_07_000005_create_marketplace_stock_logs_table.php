<?php
// file: database/migrations/2025_12_07_000005_create_marketplace_stock_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->foreignId('marketplace_product_id')->nullable()->constrained('marketplace_products')->nullOnDelete();
            $table->unsignedBigInteger('wildberries_warehouse_id')->nullable();
            $table->string('direction', 20); // push|pull
            $table->string('status', 20);    // success|error
            $table->json('payload')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_stock_logs');
    }
};
