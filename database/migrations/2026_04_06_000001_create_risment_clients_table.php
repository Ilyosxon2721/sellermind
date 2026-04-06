<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risment_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('risment_account_id')->nullable()->comment('ID аккаунта клиента в RISMENT');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        // Добавляем risment_client_id в risment_api_tokens
        Schema::table('risment_api_tokens', function (Blueprint $table) {
            $table->foreignId('risment_client_id')->nullable()->after('company_id')
                ->constrained('risment_clients')->nullOnDelete();
            $table->index('risment_client_id');
        });

        // Добавляем risment_client_id в risment_webhook_endpoints
        Schema::table('risment_webhook_endpoints', function (Blueprint $table) {
            $table->foreignId('risment_client_id')->nullable()->after('company_id')
                ->constrained('risment_clients')->nullOnDelete();
            $table->index('risment_client_id');
        });

        // Добавляем risment_client_id в integration_links
        Schema::table('integration_links', function (Blueprint $table) {
            $table->foreignId('risment_client_id')->nullable()->after('company_id')
                ->constrained('risment_clients')->nullOnDelete();
            $table->index('risment_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('integration_links', function (Blueprint $table) {
            $table->dropForeign(['risment_client_id']);
            $table->dropColumn('risment_client_id');
        });

        Schema::table('risment_webhook_endpoints', function (Blueprint $table) {
            $table->dropForeign(['risment_client_id']);
            $table->dropColumn('risment_client_id');
        });

        Schema::table('risment_api_tokens', function (Blueprint $table) {
            $table->dropForeign(['risment_client_id']);
            $table->dropColumn('risment_client_id');
        });

        Schema::dropIfExists('risment_clients');
    }
};
