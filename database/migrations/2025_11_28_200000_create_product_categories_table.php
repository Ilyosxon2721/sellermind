<?php
// file: database/migrations/2025_11_28_200000_create_product_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();

            $table->string('name', 255);
            $table->string('slug', 255)->nullable();
            $table->text('description')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['company_id', 'parent_id']);
            $table->index(['company_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
