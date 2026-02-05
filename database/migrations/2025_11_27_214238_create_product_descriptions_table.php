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
        Schema::create('product_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace', 20);   // uzum, wb, ozon, ym, universal
            $table->string('language', 5);       // ru, uz
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->longText('full_description')->nullable();
            $table->json('bullets')->nullable();     // массив строк
            $table->json('attributes')->nullable();  // key:value
            $table->json('keywords')->nullable();    // массив ключей
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['product_id', 'marketplace', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_descriptions');
    }
};
