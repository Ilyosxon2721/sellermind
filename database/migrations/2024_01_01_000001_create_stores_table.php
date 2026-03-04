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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // Основное
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('favicon', 500)->nullable();
            
            // Домен
            $table->string('custom_domain')->unique()->nullable();
            $table->boolean('domain_verified')->default(false);
            $table->boolean('ssl_enabled')->default(false);
            
            // Настройки
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            $table->boolean('maintenance_mode')->default(false);
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords', 500)->nullable();
            
            // Контакты
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->json('working_hours')->nullable();
            
            // Соцсети
            $table->string('instagram')->nullable();
            $table->string('telegram')->nullable();
            $table->string('facebook')->nullable();
            
            // Настройки магазина
            $table->string('currency', 10)->default('UZS');
            $table->decimal('min_order_amount', 15, 2)->default(0);
            
            $table->timestamps();
            
            $table->index('slug');
            $table->index('custom_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
