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
        Schema::create('marketplace_account_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Тип проблемы
            $table->enum('type', [
                'token_invalid',          // Недействительный токен
                'token_expired',          // Токен истёк
                'insufficient_permissions', // Недостаточно прав
                'shop_access_denied',     // Нет доступа к магазинам
                'api_error',              // Ошибка API
                'rate_limit',             // Превышен лимит запросов
                'sync_failed',            // Ошибка синхронизации
                'connection_failed',      // Ошибка подключения
            ]);

            // Серьёзность проблемы
            $table->enum('severity', ['critical', 'warning', 'info'])->default('warning');

            // Заголовок и описание проблемы
            $table->string('title');
            $table->text('description')->nullable();

            // Детали ошибки (JSON)
            $table->json('error_details')->nullable();

            // HTTP код ошибки
            $table->integer('http_status')->nullable();

            // Код ошибки от API маркетплейса
            $table->string('error_code')->nullable();

            // Статус проблемы
            $table->enum('status', ['active', 'resolved', 'ignored'])->default('active');

            // Когда проблема была решена
            $table->timestamp('resolved_at')->nullable();

            // Сколько раз проблема повторялась
            $table->integer('occurrences')->default(1);

            // Последний раз когда проблема встретилась
            $table->timestamp('last_occurred_at');

            $table->timestamps();

            // Индексы
            $table->index(['marketplace_account_id', 'status']);
            $table->index(['company_id', 'severity', 'status']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_account_issues');
    }
};
