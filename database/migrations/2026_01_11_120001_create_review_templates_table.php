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
        Schema::create('review_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();
            $table->text('template_text');

            // Template parameters
            $table->enum('category', [
                'positive',
                'negative_quality',
                'negative_delivery',
                'negative_size',
                'neutral',
                'question',
                'complaint'
            ]);
            $table->json('rating_range')->nullable(); // e.g., [1, 3] for low ratings
            $table->json('keywords')->nullable();

            // Usage stats
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['company_id', 'category']);
            $table->index(['is_system', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_templates');
    }
};
