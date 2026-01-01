<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketplace_shops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marketplace_account_id');
            $table->string('external_id')->index();
            $table->string('name')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_account_id', 'external_id'], 'uniq_account_shop');
            $table->foreign('marketplace_account_id')
                ->references('id')
                ->on('marketplace_accounts')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_shops');
    }
};
