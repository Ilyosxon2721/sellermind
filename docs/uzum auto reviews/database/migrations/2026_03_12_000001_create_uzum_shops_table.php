<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uzum_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->bigInteger('uzum_shop_id')->unique();
            $table->string('name');
            $table->text('api_token')->nullable();

            // Авто-подтверждение
            $table->boolean('auto_confirm_enabled')->default(false);

            // Авто-ответ на отзывы
            $table->boolean('auto_reply_enabled')->default(false);
            $table->string('review_tone')->default('friendly'); // friendly, professional, casual

            $table->timestamps();

            $table->index(['user_id', 'auto_confirm_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzum_shops');
    }
};
