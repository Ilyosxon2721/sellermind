<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Таблица для хранения пула анонимных JWT токенов Uzum API
     */
    public function up(): void
    {
        Schema::create('uzum_token_pool', function (Blueprint $table) {
            $table->id();

            // Токен и идентификатор
            $table->text('token')->comment('Bearer JWT-токен от Uzum');
            $table->char('iid', 36)->comment('X-Iid UUID для заголовка');

            // Метаданные токена
            $table->timestamp('expires_at')->index()->comment('Время истечения токена');
            $table->boolean('is_active')->default(true)->index()->comment('Активен ли токен');
            $table->unsignedInteger('requests_count')->default(0)->comment('Количество запросов через токен');

            // Timestamps
            $table->timestamps();

            // Индекс для быстрого поиска активных токенов
            $table->index(['is_active', 'expires_at'], 'active_valid_tokens_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uzum_token_pool');
    }
};
