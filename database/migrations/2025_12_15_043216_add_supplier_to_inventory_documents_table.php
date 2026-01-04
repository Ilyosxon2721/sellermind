<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_documents', function (Blueprint $table) {
            // Using unsignedBigInteger instead of foreignId to avoid migration order issues
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('source_doc_no')->nullable();
            $table->index('supplier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_documents', function (Blueprint $table) {
            $table->dropIndex(['supplier_id']);
            $table->dropColumn(['supplier_id', 'source_doc_no']);
        });
    }
};
