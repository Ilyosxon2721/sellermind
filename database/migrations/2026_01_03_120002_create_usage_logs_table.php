<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('type');  // api_call, ai_request, order_sync, product_sync
            $table->string('action')->nullable();  // Детальное действие
            $table->integer('count')->default(1);
            
            $table->json('metadata')->nullable();  // Дополнительные данные
            
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['company_id', 'type', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_logs');
    }
};
