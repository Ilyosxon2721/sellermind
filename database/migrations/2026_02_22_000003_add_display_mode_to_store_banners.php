<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_banners', function (Blueprint $table) {
            $table->string('display_mode', 20)->default('overlay')->after('text_color');
        });
    }

    public function down(): void
    {
        Schema::table('store_banners', function (Blueprint $table) {
            $table->dropColumn('display_mode');
        });
    }
};
