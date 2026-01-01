<?php
// file: database/migrations/2025_11_28_200001_create_marketplace_category_mappings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_category_mappings', function (Blueprint $table) {
            $table->id();

            $table->string('marketplace', 50); // wb, ozon, uzum, ym

            $table->string('external_category_id', 100);
            $table->string('external_category_name', 255)->nullable();

            $table->foreignId('internal_category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();

            $table->json('extra')->nullable(); // любые доп. поля МП

            $table->timestamps();

            $table->unique(['marketplace', 'external_category_id'], 'mp_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_category_mappings');
    }
};
