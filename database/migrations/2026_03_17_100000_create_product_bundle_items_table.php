<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем флаг is_bundle в products
        if (! Schema::hasColumn('products', 'is_bundle')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_bundle')->default(false)->after('is_archived');
            });
        }

        // Таблица компонентов комплекта
        Schema::create('product_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('bundle_product_id');
            $table->unsignedBigInteger('component_variant_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('bundle_product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('component_variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('cascade');

            $table->unique(['bundle_product_id', 'component_variant_id'], 'bundle_component_unique');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bundle_items');

        if (Schema::hasColumn('products', 'is_bundle')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('is_bundle');
            });
        }
    }
};
