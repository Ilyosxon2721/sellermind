<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace', 20); // uzum, wb, ozon, ym
            $table->text('credentials'); // encrypted JSON with tokens
            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'marketplace']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_accounts');
    }
};
