<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'address_comment')) {
                $table->text('address_comment')->nullable()->after('address');
            }
            if (!Schema::hasColumn('warehouses', 'comment')) {
                $table->text('comment')->nullable()->after('address_comment');
            }
            if (!Schema::hasColumn('warehouses', 'group_name')) {
                $table->string('group_name')->nullable()->after('code');
            }
            if (!Schema::hasColumn('warehouses', 'external_code')) {
                $table->string('external_code')->nullable()->after('group_name');
            }
            if (!Schema::hasColumn('warehouses', 'meta_json')) {
                $table->json('meta_json')->nullable()->after('external_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['address_comment', 'comment', 'group_name', 'external_code', 'meta_json']);
        });
    }
};
