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
        Schema::table('supplies', function (Blueprint $table) {
            $table->string('cargo_type', 50)->nullable()->index()->comment('Габаритный тип поставки');
            $table->integer('boxes_count')->default(0)->comment('Количество коробов');
            $table->timestamp('delivery_started_at')->nullable()->comment('Фактическое начало доставки');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn(['cargo_type', 'boxes_count', 'delivery_started_at']);
        });
    }
};
