<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uzum_shops', function (Blueprint $table) {
            // OAuth2 session tokens (для seller panel API — отзывы и т.д.)
            $table->text('session_token')->nullable()->after('api_token');
            $table->text('refresh_token')->nullable()->after('session_token');
            $table->timestamp('token_expires_at')->nullable()->after('refresh_token');

            // Seller credentials (encrypted) для авто-ре-логина
            $table->text('seller_email')->nullable()->after('token_expires_at');
            $table->text('seller_password')->nullable()->after('seller_email');

            // Seller profile info
            $table->bigInteger('seller_id')->nullable()->after('seller_password');
        });
    }

    public function down(): void
    {
        Schema::table('uzum_shops', function (Blueprint $table) {
            $table->dropColumn([
                'session_token',
                'refresh_token',
                'token_expires_at',
                'seller_email',
                'seller_password',
                'seller_id',
            ]);
        });
    }
};
