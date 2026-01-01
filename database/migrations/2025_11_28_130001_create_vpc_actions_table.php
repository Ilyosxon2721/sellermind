<?php
// file: database/migrations/2025_11_28_130001_create_vpc_actions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpc_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpc_session_id')->constrained('vpc_sessions')->cascadeOnDelete();

            $table->string('source', 20); // agent | user
            $table->string('action_type', 50); // open_url, click, type, scroll, screenshot, etc.
            $table->json('payload')->nullable(); // параметры команд

            $table->timestamp('created_at')->useCurrent();

            $table->index(['vpc_session_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpc_actions');
    }
};
