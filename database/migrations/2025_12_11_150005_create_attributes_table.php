<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 255);
            $table->enum('type', ['string', 'number', 'bool', 'enum', 'multiselect']);
            $table->json('allowed_values')->nullable();
            $table->string('unit', 50)->nullable();
            $table->boolean('is_variant_level')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
