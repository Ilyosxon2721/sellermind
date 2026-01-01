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
        Schema::table('dialogs', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('category');
            $table->timestamp('hidden_at')->nullable()->after('is_private');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dialogs', function (Blueprint $table) {
            $table->dropColumn(['is_private', 'hidden_at']);
        });
    }
};
