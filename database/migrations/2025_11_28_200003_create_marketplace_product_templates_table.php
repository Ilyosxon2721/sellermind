<?php
// file: database/migrations/2025_11_28_200003_create_marketplace_product_templates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_product_templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->nullable()
                ->constrained('marketplace_accounts')
                ->nullOnDelete();

            $table->string('marketplace', 50); // wb, ozon, uzum, ym

            // можно привязывать к внутренней категории
            $table->foreignId('internal_category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();

            $table->string('name', 255); // название шаблона для пользователя

            // Шаблон заголовка, например: "{{brand}} {{product_type}} {{key_feature}}"
            $table->text('title_template')->nullable();

            // Шаблон описания (markdown/text)
            $table->longText('description_template')->nullable();

            // Настройки атрибутов (какие включать, в каком порядке)
            $table->json('attributes_config')->nullable();

            $table->timestamps();

            $table->index(['marketplace', 'internal_category_id'], 'mp_templates_mp_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_templates');
    }
};
