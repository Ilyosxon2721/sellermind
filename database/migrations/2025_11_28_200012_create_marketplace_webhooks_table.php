<?php

// file: database/migrations/2025_11_28_200012_create_marketplace_webhooks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_webhooks', function (Blueprint $table) {
            $table->id();

            $table->string('marketplace', 50);
            $table->foreignId('marketplace_account_id')
                ->nullable()
                ->constrained('marketplace_accounts')
                ->nullOnDelete();

            $table->string('event_type', 100)->nullable();
            $table->string('status', 20)->default('new'); // new, processed, error

            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['marketplace', 'status']);
            $table->index(['marketplace_account_id', 'event_type'], 'mp_webhooks_account_event_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_webhooks');
    }
};
