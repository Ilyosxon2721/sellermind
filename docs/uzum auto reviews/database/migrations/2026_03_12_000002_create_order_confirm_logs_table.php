<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_confirm_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('uzum_order_id')->index();
            $table->string('status'); // confirmed, failed
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_confirm_logs');
    }
};
