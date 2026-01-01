<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('code', 50); // size, color
            $table->string('name', 100); // Размер, Цвет
            $table->string('type', 20)->default('select'); // select, color
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('global_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('global_option_id')->constrained()->onDelete('cascade');
            $table->string('value', 100); // S, M, L / Красный, Синий
            $table->string('code', 50)->nullable(); // s, m, l / red, blue
            $table->string('color_hex', 7)->nullable(); // #FF0000
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['global_option_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_option_values');
        Schema::dropIfExists('global_options');
    }
};
