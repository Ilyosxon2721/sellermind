<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->text('value_string')->nullable();
            $table->decimal('value_number', 12, 3)->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->index(['product_id']);
            $table->index(['product_variant_id']);
            $table->index(['company_id', 'attribute_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE product_attribute_values ADD CONSTRAINT chk_product_attribute_values_target '.
                'CHECK ( (product_id IS NOT NULL AND product_variant_id IS NULL) '.
                'OR (product_id IS NULL AND product_variant_id IS NOT NULL) )'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
