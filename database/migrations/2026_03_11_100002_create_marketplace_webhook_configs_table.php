<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создать таблицу конфигураций вебхуков
     */
    public function up(): void
    {
        Schema::create('marketplace_webhook_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->string('marketplace', 50);
            $table->string('webhook_uuid', 36)->unique();
            $table->string('secret_key', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_received_at')->nullable();
            $table->unsignedInteger('events_count')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'marketplace']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_webhook_configs');
    }
};
