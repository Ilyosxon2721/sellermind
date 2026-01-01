<?php
// file: database/migrations/2025_12_01_300007_create_wildberries_documents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create wildberries_documents table for storing WB financial documents.
     */
    public function up(): void
    {
        Schema::create('wildberries_documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            // Document identifiers
            $table->string('document_id', 100)->unique();       // WB Document ID
            $table->string('document_number', 100)->nullable(); // Document number
            $table->string('category', 100)->nullable();        // Document category

            // Document type
            $table->string('type', 50)->nullable();             // act, upd, invoice, etc.
            $table->string('format', 10)->default('pdf');       // pdf, xlsx, etc.

            // Document info
            $table->date('document_date')->nullable();          // Document date
            $table->decimal('amount', 12, 2)->nullable();       // Document amount
            $table->string('currency', 10)->default('RUB');     // Currency

            // File storage
            $table->string('file_path', 500)->nullable();       // Path to saved file
            $table->unsignedBigInteger('file_size')->nullable(); // File size in bytes

            // Status
            $table->string('status', 50)->default('active');    // active, archived
            $table->boolean('is_downloaded')->default(false);

            // Raw data from WB
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['marketplace_account_id', 'category'], 'wb_doc_acc_cat_idx');
            $table->index(['marketplace_account_id', 'document_date'], 'wb_doc_acc_date_idx');
            $table->index(['marketplace_account_id', 'type'], 'wb_doc_acc_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wildberries_documents');
    }
};
