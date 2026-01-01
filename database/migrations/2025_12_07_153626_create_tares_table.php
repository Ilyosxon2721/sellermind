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
        Schema::create('tares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained('supplies')->onDelete('cascade');
            $table->string('external_tare_id')->nullable()->comment('ID коробки из WB');
            $table->string('barcode')->nullable()->comment('Штрихкод коробки');
            $table->integer('orders_count')->default(0)->comment('Количество заказов в коробке');
            $table->timestamps();

            $table->index('supply_id');
            $table->index('external_tare_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tares');
    }
};
