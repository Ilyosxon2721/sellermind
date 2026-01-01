<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // cards_bulk, descriptions_update, images_bulk, etc.
            $table->enum('status', ['pending', 'in_progress', 'done', 'failed'])->default('pending');
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->unsignedTinyInteger('progress')->default(0); // 0-100
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_tasks');
    }
};
