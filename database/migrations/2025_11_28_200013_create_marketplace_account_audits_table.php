<?php
// file: database/migrations/2025_11_28_200013_create_marketplace_account_audits_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_account_audits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('event', 50); // created, updated, deleted, credentials_changed, synced, error
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();

            $table->timestamp('created_at');

            $table->index(['marketplace_account_id', 'created_at'], 'mp_audits_account_idx');
            $table->index(['user_id', 'created_at']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_account_audits');
    }
};
