<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('risment_credential_id')->nullable()->after('sync_settings');
            $table->string('source', 20)->default('manual')->after('risment_credential_id');

            $table->index('risment_credential_id');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->dropIndex(['risment_credential_id']);
            $table->dropColumn(['risment_credential_id', 'source']);
        });
    }
};
