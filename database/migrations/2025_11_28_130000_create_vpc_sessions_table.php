<?php
// file: database/migrations/2025_11_28_130000_create_vpc_sessions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpc_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            // опциональная связь с задачей агента
            $table->foreignId('agent_task_id')->nullable()->constrained('agent_tasks')->nullOnDelete();

            $table->string('name', 255)->nullable(); // человекочитаемое имя сессии

            // статус жизненного цикла ВПК
            $table->string('status', 20)->default('creating');
            // creating, ready, running, paused, stopped, error

            // режим управления
            $table->string('control_mode', 20)->default('AGENT_CONTROL');
            // AGENT_CONTROL, USER_CONTROL, PAUSED

            // endpoint и токен для реального подключения к VNC/WebRTC (заглушки)
            $table->string('endpoint')->nullable();      // например, ws://host:port
            $table->string('display_token')->nullable(); // токен для доступа к видео

            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpc_sessions');
    }
};
