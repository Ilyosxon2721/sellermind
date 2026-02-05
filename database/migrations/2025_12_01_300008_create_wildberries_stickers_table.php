<?php

// file: database/migrations/2025_12_01_300008_create_wildberries_stickers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create wildberries_stickers table for storing WB order stickers.
     */
    public function up(): void
    {
        Schema::create('wildberries_stickers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            // Sticker info
            $table->string('batch_id', 100)->nullable();        // Batch ID for grouped stickers
            $table->json('order_ids');                          // Array of order IDs in this sticker file

            // File info
            $table->string('file_path', 500);                   // Path to saved sticker file
            $table->string('format', 10)->default('pdf');       // pdf, png, svg
            $table->unsignedBigInteger('file_size')->nullable(); // File size in bytes

            // Sticker type
            $table->string('type', 50)->default('standard');    // standard, cross-border
            $table->unsignedInteger('width')->default(58);      // Width in mm
            $table->unsignedInteger('height')->default(40);     // Height in mm

            // Status
            $table->boolean('is_printed')->default(false);
            $table->timestamp('printed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['marketplace_account_id', 'created_at'], 'wb_stick_acc_created_idx');
            $table->index(['marketplace_account_id', 'is_printed'], 'wb_stick_acc_printed_idx');
            $table->index(['marketplace_account_id', 'batch_id'], 'wb_stick_acc_batch_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wildberries_stickers');
    }
};
