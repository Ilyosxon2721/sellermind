<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы для хранения подписок на Web Push уведомления
     */
    public function up(): void
    {
        // Удаляем таблицу если она осталась от неудачной миграции
        Schema::dropIfExists('push_subscriptions');

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            $table->text('endpoint');
            $table->char('endpoint_hash', 64)->unique(); // SHA-256 hash для unique индекса
            $table->string('public_key', 255);
            $table->string('auth_token', 255);
            $table->string('content_encoding', 50)->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Откат миграции
     */
    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
