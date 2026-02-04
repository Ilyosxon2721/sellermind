<?php

// file: database/migrations/2025_12_07_000004_create_marketplace_warehouses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('wildberries_warehouse_id')->nullable();
            $table->unsignedBigInteger('local_warehouse_id')->nullable(); // опционально: ссылка на нашу таблицу складов, если появится
            $table->string('name')->nullable();
            $table->string('type', 20)->nullable(); // FBS/FBO/etc
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['marketplace_account_id', 'wildberries_warehouse_id'], 'mp_wh_wb_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_warehouses');
    }
};
