<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uzum_tracked_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->comment('ID товара в Uzum');
            $table->string('title', 500)->nullable()->comment('Кэшированное название товара');
            $table->string('shop_slug', 255)->nullable()->comment('Slug магазина конкурента');
            $table->boolean('alert_enabled')->default(true);
            $table->unsignedTinyInteger('alert_threshold_pct')->default(5)->comment('Порог % изменения цены для Telegram алерта');
            $table->decimal('last_price', 12, 2)->nullable()->comment('Последняя зафиксированная цена (сум)');
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzum_tracked_products');
    }
};
