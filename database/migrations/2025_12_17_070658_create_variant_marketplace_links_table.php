<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_marketplace_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('marketplace_product_id');
            $table->unsignedBigInteger('marketplace_account_id');
            $table->string('external_offer_id')->nullable()->comment('SKU/offerId на маркетплейсе');
            $table->string('external_sku')->nullable()->comment('Артикул поставщика');
            $table->boolean('is_active')->default(true);
            $table->boolean('sync_stock_enabled')->default(true)->comment('Синхронизировать остатки');
            $table->boolean('sync_price_enabled')->default(false)->comment('Синхронизировать цены');
            $table->integer('last_stock_synced')->nullable();
            $table->decimal('last_price_synced', 12, 2)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_status')->nullable()->comment('success/error');
            $table->text('last_sync_error')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            $table->foreign('marketplace_product_id')->references('id')->on('marketplace_products')->onDelete('cascade');
            $table->foreign('marketplace_account_id')->references('id')->on('marketplace_accounts')->onDelete('cascade');

            $table->unique(['product_variant_id', 'marketplace_product_id'], 'variant_mp_unique');
            $table->index(['company_id', 'is_active'], 'vml_company_active_idx');
            $table->index(['marketplace_account_id', 'sync_stock_enabled'], 'vml_account_sync_idx');
        });

        // Таблица логов синхронизации
        Schema::create('stock_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('variant_marketplace_link_id')->nullable();
            $table->unsignedBigInteger('marketplace_account_id');
            $table->string('external_offer_id')->nullable();
            $table->string('action')->comment('push/pull');
            $table->integer('stock_before')->nullable();
            $table->integer('stock_after')->nullable();
            $table->string('status')->comment('pending/success/error');
            $table->text('error_message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['marketplace_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_sync_logs');
        Schema::dropIfExists('variant_marketplace_links');
    }
};
