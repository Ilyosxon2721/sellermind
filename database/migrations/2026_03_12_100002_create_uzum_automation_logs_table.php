<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создать таблицы логов автоматизации Uzum Market
     */
    public function up(): void
    {
        // Лог автоподтверждений заказов
        Schema::create('uzum_order_confirm_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->onDelete('cascade');
            $table->bigInteger('uzum_order_id');
            $table->string('status'); // confirmed, failed
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['marketplace_account_id', 'status']);
        });

        // Лог автоответов на отзывы
        Schema::create('uzum_review_reply_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->onDelete('cascade');
            $table->bigInteger('uzum_review_id');
            $table->tinyInteger('rating')->nullable();
            $table->text('review_text')->nullable();
            $table->text('reply_text')->nullable();
            $table->string('product_name')->nullable();
            $table->string('status'); // sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_account_id', 'uzum_review_id'], 'uzum_reply_logs_account_review_unique');
        });
    }

    /**
     * Удалить таблицы логов автоматизации Uzum Market
     */
    public function down(): void
    {
        Schema::dropIfExists('uzum_review_reply_logs');
        Schema::dropIfExists('uzum_order_confirm_logs');
    }
};
