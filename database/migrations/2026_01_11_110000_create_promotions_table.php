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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->string('name');
            $table->text('description')->nullable();

            // Promotion type
            $table->enum('type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->decimal('discount_value', 10, 2);

            // Dates
            $table->timestamp('start_date');
            $table->timestamp('end_date');

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_automatic')->default(false);

            // Conditions (JSON)
            // Example: {"min_days_no_sale": 30, "min_stock": 5, "max_discount": 50}
            $table->json('conditions')->nullable();

            // Notification settings
            $table->boolean('notify_before_expiry')->default(true);
            $table->integer('notify_days_before')->default(3);
            $table->timestamp('expiry_notification_sent_at')->nullable();

            // Stats
            $table->integer('products_count')->default(0);
            $table->decimal('total_revenue_impact', 12, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
            $table->index(['end_date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
