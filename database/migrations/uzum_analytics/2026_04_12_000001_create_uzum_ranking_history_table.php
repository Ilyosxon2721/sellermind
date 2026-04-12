<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uzum_ranking_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('category_id')->index();
            $table->string('shop_slug', 255)->index();
            $table->unsignedSmallInteger('rank_by_orders');
            $table->unsignedSmallInteger('rank_by_revenue');
            $table->unsignedSmallInteger('rank_by_reviews');
            $table->unsignedSmallInteger('rank_by_rating');
            $table->unsignedSmallInteger('total_shops');
            $table->unsignedInteger('our_orders')->default(0);
            $table->unsignedBigInteger('our_revenue')->default(0);
            $table->unsignedInteger('our_reviews')->default(0);
            $table->decimal('our_rating', 3, 2)->default(0);
            $table->unsignedInteger('category_total_orders')->default(0);
            $table->decimal('market_share_pct', 5, 2)->default(0);
            $table->timestamp('recorded_at')->index();
            $table->timestamps();
            $table->index(['company_id', 'category_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzum_ranking_history');
    }
};
