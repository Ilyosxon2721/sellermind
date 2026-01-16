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
        Schema::table('companies', function (Blueprint $table) {
            // Поля для фулфилмент-клиентов
            $table->boolean('is_fulfillment_client')->default(true)->after('name');
            $table->string('client_type')->nullable()->after('is_fulfillment_client'); // 'fbs', 'fbo', 'both'
            $table->string('risment_subscription_plan')->nullable();
            $table->date('subscription_expires_at')->nullable();
            $table->decimal('monthly_storage_limit', 10, 2)->nullable()->comment('Лимит хранения в м³');
            $table->json('service_settings')->nullable()->comment('Настройки услуг');
            $table->string('risment_webhook_url')->nullable()->comment('URL для вебхуков');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'is_fulfillment_client',
                'client_type',
                'risment_subscription_plan',
                'subscription_expires_at',
                'monthly_storage_limit',
                'service_settings',
                'risment_webhook_url',
            ]);
        });
    }
};
