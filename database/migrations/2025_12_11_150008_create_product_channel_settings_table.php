<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_channel_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->string('external_product_id', 100)->nullable();
            $table->string('category_external_id', 100)->nullable();
            $table->string('name_override', 255)->nullable();
            $table->mediumText('description_override')->nullable();
            $table->string('brand_external_id', 100)->nullable();
            $table->string('brand_external_name', 255)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->enum('status', ['draft', 'pending', 'published', 'error'])->default('draft');
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_sync_status_message')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'channel_id']);
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_channel_settings');
    }
};
