<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Подписки на Telegram-уведомления о заказах.
 * Позволяет фильтровать по маркетплейсу и аккаунту.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('chat_id');
            $table->string('marketplace')->nullable(); // null = все маркетплейсы
            $table->foreignId('marketplace_account_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('notify_new')->default(true);
            $table->boolean('notify_status')->default(true);
            $table->boolean('notify_cancel')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('daily_summary')->default(false);
            $table->time('summary_time')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['marketplace', 'marketplace_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_subscriptions');
    }
};
