<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создать таблицу событий маркетплейсов
     */
    public function up(): void
    {
        Schema::create('marketplace_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('store_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->string('marketplace', 50);
            $table->string('event_type', 100);
            $table->string('external_id', 255)->nullable();
            $table->string('entity_type', 50);
            $table->string('entity_id', 255);
            $table->json('payload');
            $table->json('normalized_data')->nullable();
            $table->string('status', 20)->default('received');
            $table->tinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['marketplace', 'external_id'], 'idx_dedup');
            $table->index(['store_id', 'event_type', 'created_at'], 'idx_store_type');
            $table->index(['status', 'attempts'], 'idx_status');
            $table->index(['marketplace', 'entity_type', 'entity_id'], 'idx_entity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_events');
    }
};
