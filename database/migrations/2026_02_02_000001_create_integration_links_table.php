<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('external_system', 50)->default('risment');
            $table->string('external_user_id')->nullable();
            $table->string('link_token', 128)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'external_system', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_links');
    }
};
