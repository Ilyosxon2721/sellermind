<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Покупатели витрины (регистрация по телефону/email)
     */
    public function up(): void
    {
        Schema::create('store_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('phone', 50)->unique();
            $table->string('email')->nullable();
            $table->string('password_hash')->nullable();

            // Адрес по умолчанию
            $table->string('default_city')->nullable();
            $table->text('default_address')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'phone']);
        });

        // Привязка заказов к покупателям
        Schema::table('store_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('store_customer_id')->nullable()->after('store_id');
            $table->index('store_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropIndex(['store_customer_id']);
            $table->dropColumn('store_customer_id');
        });

        Schema::dropIfExists('store_customers');
    }
};
