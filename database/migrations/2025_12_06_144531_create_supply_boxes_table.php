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
        Schema::create('supply_boxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_id')->comment('ID поставки');
            $table->string('box_number', 100)->comment('Номер короба');
            $table->string('sticker_path')->nullable()->comment('Путь к стикеру короба');
            $table->timestamps();

            $table->foreign('supply_id')->references('id')->on('supplies')->onDelete('cascade');
            $table->index('supply_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_boxes');
    }
};
