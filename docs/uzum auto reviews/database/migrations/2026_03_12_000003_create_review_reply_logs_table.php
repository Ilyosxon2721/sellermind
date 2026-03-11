<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_reply_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('uzum_shop_id')->index();
            $table->bigInteger('uzum_review_id')->unique();
            $table->tinyInteger('rating')->default(0);
            $table->text('review_text')->nullable();
            $table->text('reply_text');
            $table->string('product_name')->nullable();
            $table->string('status'); // sent, failed, pending_approval
            $table->text('error_message')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reply_logs');
    }
};
