<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('restrict');
            
            $table->enum('status', ['active', 'trial', 'expired', 'cancelled', 'pending'])->default('trial');
            
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Платежи
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->string('payment_method')->nullable();  // click, payme, uzum, bank_transfer
            $table->string('payment_reference')->nullable();
            
            // Использование (кэш для быстрого доступа)
            $table->integer('current_products_count')->default(0);
            $table->integer('current_orders_count')->default(0);
            $table->integer('current_ai_requests')->default(0);
            $table->timestamp('usage_reset_at')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
