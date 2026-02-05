<?php

// file: database/migrations/2025_11_28_130002_create_vpc_snapshots_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpc_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpc_session_id')->constrained('vpc_sessions')->cascadeOnDelete();

            $table->string('image_path'); // относительный путь или S3 key
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpc_snapshots');
    }
};
