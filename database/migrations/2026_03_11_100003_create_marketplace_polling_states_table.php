<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создать таблицу состояний поллинга
     */
    public function up(): void
    {
        Schema::create('marketplace_polling_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->string('marketplace', 50);
            $table->string('endpoint', 255);
            $table->string('last_cursor', 255)->nullable();
            $table->timestamp('last_poll_at')->nullable();
            $table->unsignedInteger('poll_interval_sec')->default(30);
            $table->unsignedInteger('consecutive_errors')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'marketplace', 'endpoint'], 'idx_polling_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_polling_states');
    }
};
