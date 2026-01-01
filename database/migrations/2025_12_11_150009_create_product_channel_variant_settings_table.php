<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_channel_variant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->string('external_offer_id', 100)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('old_price', 12, 2)->nullable();
            $table->integer('stock')->nullable();
            $table->enum('status', ['draft', 'pending', 'published', 'error'])->default('draft');
            $table->timestamp('last_synced_at')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->unique(
                ['product_variant_id', 'channel_id'],
                'pcvs_variant_channel_unique'
            );
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_channel_variant_settings');
    }
};
