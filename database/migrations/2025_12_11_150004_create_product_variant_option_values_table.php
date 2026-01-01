<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained('product_option_values')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['product_variant_id', 'product_option_value_id'],
                'pvov_variant_option_unique'
            );
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_option_values');
    }
};
