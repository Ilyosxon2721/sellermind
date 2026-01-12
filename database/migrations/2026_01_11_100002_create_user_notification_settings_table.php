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
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Notification types
            $table->boolean('notify_low_stock')->default(true);
            $table->boolean('notify_new_order')->default(true);
            $table->boolean('notify_order_cancelled')->default(true);
            $table->boolean('notify_price_changes')->default(false);
            $table->boolean('notify_bulk_operations')->default(true);
            $table->boolean('notify_marketplace_sync')->default(true);
            $table->boolean('notify_critical_errors')->default(true);

            // Channels
            $table->boolean('channel_telegram')->default(true);
            $table->boolean('channel_email')->default(true);
            $table->boolean('channel_database')->default(true);

            // Preferences
            $table->integer('low_stock_threshold')->default(10);
            $table->boolean('notify_only_business_hours')->default(false);
            $table->time('business_hours_start')->nullable();
            $table->time('business_hours_end')->nullable();

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};
