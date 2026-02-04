<?php

// file: database/migrations/2025_11_28_200002_create_marketplace_attribute_mappings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_attribute_mappings', function (Blueprint $table) {
            $table->id();

            $table->string('marketplace', 50); // wb, ozon, uzum, ym

            $table->string('internal_attribute_code', 100); // например: color, size, material
            $table->string('external_attribute_id', 100)->nullable();
            $table->string('external_attribute_name', 255)->nullable();

            // режим маппинга значений: simple, dictionary, custom
            $table->string('value_mode', 20)->default('simple');

            // словари значений: {"серый": "Gray", "черный":"Black"}
            $table->json('value_mapping')->nullable();

            $table->json('extra')->nullable();

            $table->timestamps();

            $table->unique(['marketplace', 'internal_attribute_code'], 'mp_attr_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_attribute_mappings');
    }
};
