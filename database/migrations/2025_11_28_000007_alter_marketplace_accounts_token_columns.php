<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Encrypted tokens easily exceed 255 chars, switch to TEXT
        if (Schema::hasTable('marketplace_accounts')) {
            if (Schema::hasColumn('marketplace_accounts', 'api_key')) {
                DB::statement('ALTER TABLE marketplace_accounts MODIFY api_key TEXT NULL');
            }
            if (Schema::hasColumn('marketplace_accounts', 'client_secret')) {
                DB::statement('ALTER TABLE marketplace_accounts MODIFY client_secret TEXT NULL');
            }
            if (Schema::hasColumn('marketplace_accounts', 'oauth_token')) {
                DB::statement('ALTER TABLE marketplace_accounts MODIFY oauth_token TEXT NULL');
            }
            if (Schema::hasColumn('marketplace_accounts', 'oauth_refresh_token')) {
                DB::statement('ALTER TABLE marketplace_accounts MODIFY oauth_refresh_token TEXT NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketplace_accounts')) {
            if (Schema::hasColumn('marketplace_accounts', 'api_key')) {
                DB::statement('ALTER TABLE marketplace_accounts MODIFY api_key VARCHAR(255) NULL');
            }
            if (Schema::hasColumn('marketplace_accounts', 'client_secret')) {
                DB::statement('ALTER TABLE marketplace_accounts MODIFY client_secret VARCHAR(255) NULL');
            }
            if (Schema::hasColumn('marketplace_accounts', 'oauth_token')) {
                DB::statement('ALTER TABLE marketplace_accounts MODIFY oauth_token VARCHAR(255) NULL');
            }
            if (Schema::hasColumn('marketplace_accounts', 'oauth_refresh_token')) {
                DB::statement('ALTER TABLE marketplace_accounts MODIFY oauth_refresh_token VARCHAR(255) NULL');
            }
        }
    }
};
