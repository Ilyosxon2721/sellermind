<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->after('product_id');
            $table->string('risment_variant_id')->nullable()->after('height_mm');
            $table->index('risment_variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex(['risment_variant_id']);
            $table->dropColumn(['risment_variant_id', 'name']);
        });
    }
};
