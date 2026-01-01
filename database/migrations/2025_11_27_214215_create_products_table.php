<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('article', 100);
            $table->string('brand_name', 255)->nullable();
            $table->foreignId('category_id')->nullable()->index();
            $table->text('description_short')->nullable();
            $table->mediumText('description_full')->nullable();
            $table->string('country_of_origin', 100)->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('unit', 50)->nullable();
            $table->text('care_instructions')->nullable();
            $table->text('composition')->nullable();
            $table->integer('package_weight_g')->nullable();
            $table->integer('package_length_mm')->nullable();
            $table->integer('package_width_mm')->nullable();
            $table->integer('package_height_mm')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_archived')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'article']);
            $table->index(['company_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
