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
        if (Schema::hasTable('reviews')) {
            // Table exists, add only new columns if missing
            Schema::table('reviews', function (Blueprint $table) {
                if (!Schema::hasColumn('reviews', 'is_published')) {
                    $table->boolean('is_published')->default(false)->after('status');
                }
            });
            return;
        }

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('marketplace_account_id')->nullable()->constrained()->onDelete('set null');

            // Review source info
            $table->string('marketplace')->nullable(); // wildberries, ozon, etc
            $table->string('external_review_id')->nullable();
            $table->string('external_order_id')->nullable();

            // Review content
            $table->string('customer_name')->nullable();
            $table->integer('rating'); // 1-5
            $table->text('review_text');
            $table->json('photos')->nullable();
            $table->timestamp('review_date')->nullable();

            // Response
            $table->text('response_text')->nullable();
            $table->timestamp('response_date')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_ai_generated')->default(false);
            $table->foreignId('template_id')->nullable()->constrained('review_templates')->onDelete('set null');

            // Status
            $table->enum('status', ['pending', 'responded', 'ignored'])->default('pending');
            $table->boolean('is_published')->default(false);

            // Sentiment analysis
            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable();
            $table->json('keywords')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['marketplace_account_id', 'status']);
            $table->index(['rating', 'status']);
            $table->index('external_review_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
