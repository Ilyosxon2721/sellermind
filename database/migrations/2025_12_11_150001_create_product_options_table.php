<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('name', 255);
            $table->enum('type', ['select', 'color', 'text', 'number']);
            $table->boolean('is_variant_dimension')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'code']);
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
